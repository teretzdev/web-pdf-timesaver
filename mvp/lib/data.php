<?php
declare(strict_types=1);

namespace WebPdfTimeSaver\Mvp;

require_once __DIR__ . '/../../config/db_env.php';
require_once __DIR__ . '/logger.php';

wpts_apply_db_local_overrides();

final class DataStore {
	private string $path;
	private array $db;
	private ?Logger $logger;
	private ?\PDO $pdo = null;
	/** False when MySQL is used but information_schema shows no form_location (migration skipped/failed). */
	private bool $mysqlFormTemplatesHasFormLocation = false;

	public function __construct(string $path, ?Logger $logger = null) {
		$this->path = $path;
		$this->logger = $logger ?? new Logger();
		
		try {
			if (!is_dir(dirname($path))) {
				if (!mkdir(dirname($path), 0755, true)) {
					$this->logger->error('Failed to create data directory: ' . dirname($path));
					throw new \RuntimeException('Failed to create data directory');
				}
			}
			$snapshot = $this->loadFromJsonFile();
			$this->initializeMySqlForPhaseOne();
			if ($this->pdo) {
				$this->maybeMigrateJsonSnapshotToMysql($snapshot);
				$this->maybeMigrateJsonPhaseOneTables($snapshot);
				$this->hydrateMemoryFromMysql();
			} else {
				$this->db = $snapshot;
			}
		} catch (\Throwable $e) {
			$this->logger->error('DataStore initialization failed: ' . $e->getMessage());
			throw $e;
		}
	}

	private function initializeMySqlForPhaseOne(): void {
		try {
			$p = wpts_mysql_params();

			if ($p['disabled']) {
				$this->logger->info('MySQL Phase 1 store disabled by DB_ENABLED flag');
				return;
			}

			if ($p['incomplete']) {
				$msg = 'MySQL Phase 1 store not initialized due to incomplete credentials';
				$this->logger->warning($msg);
				if (wpts_db_required()) {
					throw new \RuntimeException($msg);
				}
				return;
			}

			$this->pdo = $this->connectMysqlWithFallback($p);

			$this->bootstrapPhaseOneTables();
			$this->bootstrapMvpEntityTables();
			$this->logger->info('MySQL Phase 1 store initialized', [
				'host' => $p['host'],
				'port' => $p['port'],
				'database' => $p['database'],
			]);
		} catch (\Throwable $e) {
			$this->pdo = null;
			if (wpts_db_required()) {
				throw $e;
			}
			$this->logger->warning('MySQL Phase 1 store unavailable, falling back to JSON', [
				'error' => $e->getMessage(),
			]);
		}
	}

	/** @param array{host:string,port:int,database:string,user:string,password:string} $p */
	private function connectMysqlWithFallback(array $p): \PDO {
		$options = [
			\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
			\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
			\PDO::ATTR_EMULATE_PREPARES => false,
			\PDO::ATTR_TIMEOUT => 3,
		];
		$errors = [];
		foreach ($this->buildMysqlCandidateDsns($p) as $label => $dsn) {
			try {
				$pdo = new \PDO($dsn, $p['user'], $p['password'], $options);
				$this->logger->info('MySQL Phase 1 connection established', [
					'candidate' => $label,
					'dsn' => $dsn,
				]);
				return $pdo;
			} catch (\Throwable $e) {
				$errors[] = $label . ': ' . $e->getMessage();
			}
		}
		throw new \RuntimeException('MySQL connection attempts failed: ' . implode(' | ', $errors));
	}

	/**
	 * @param array{host:string,port:int,database:string} $p
	 * @return array<string,string> candidateLabel => DSN
	 */
	private function buildMysqlCandidateDsns(array $p): array {
		$database = (string)$p['database'];
		$configuredHost = trim((string)$p['host']);
		$configuredPort = (int)$p['port'];
		$candidates = [];

		$addHost = static function (array &$dst, string $label, string $host, int $port, string $database): void {
			$host = trim($host);
			if ($host === '') {
				return;
			}
			$dst[$label] = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $database);
		};
		$addSocket = static function (array &$dst, string $label, string $socketPath, string $database): void {
			$socketPath = trim($socketPath);
			if ($socketPath === '') {
				return;
			}
			$dst[$label] = sprintf('mysql:unix_socket=%s;dbname=%s;charset=utf8mb4', $socketPath, $database);
		};

		$addHost($candidates, 'configured', $configuredHost, $configuredPort, $database);
		foreach (['localhost', '127.0.0.1', 'MySQL', 'mysql', 'mariadb', 'db'] as $hostAlias) {
			$addHost($candidates, 'host:' . $hostAlias, $hostAlias, $configuredPort > 0 ? $configuredPort : 3306, $database);
		}

		foreach ([
			(string)ini_get('pdo_mysql.default_socket'),
			(string)ini_get('mysqli.default_socket'),
			'/var/run/mysqld/mysqld.sock',
			'/run/mysqld/mysqld.sock',
			'/var/run/mysql/mysql.sock',
			'/var/lib/mysql/mysql.sock',
			'/tmp/mysql.sock',
		] as $socketPath) {
			$addSocket($candidates, 'socket:' . $socketPath, $socketPath, $database);
		}

		// De-duplicate DSNs while preserving insertion order.
		$seen = [];
		$out = [];
		foreach ($candidates as $label => $dsn) {
			if (isset($seen[$dsn])) {
				continue;
			}
			$seen[$dsn] = true;
			$out[$label] = $dsn;
		}
		return $out;
	}

	private function bootstrapPhaseOneTables(): void {
		if (!$this->pdo) {
			return;
		}

		$this->pdo->exec(
			'CREATE TABLE IF NOT EXISTS form_templates (
				id VARCHAR(64) NOT NULL PRIMARY KEY,
				template_id VARCHAR(191) NOT NULL UNIQUE,
				form_name VARCHAR(255) NOT NULL,
				source_file_name VARCHAR(255) NOT NULL DEFAULT \'\',
				detected_firm_name VARCHAR(512) NOT NULL DEFAULT \'\',
				scope VARCHAR(32) NOT NULL DEFAULT \'global\',
				created_at DATETIME NOT NULL,
				updated_at DATETIME NOT NULL
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
		);
		$this->ensureFormTemplatesDetectedFirmColumn();
		$this->ensureFormTemplatesFormLocationColumn();
		$this->mysqlFormTemplatesHasFormLocation = $this->detectFormTemplatesFormLocationColumn();
		if ($this->pdo && !$this->mysqlFormTemplatesHasFormLocation) {
			$this->logger->warning('form_templates.form_location is missing; form location will not persist in MySQL until migration succeeds');
		}

		$this->pdo->exec(
			'CREATE TABLE IF NOT EXISTS firm_field_alignments (
				id VARCHAR(64) NOT NULL PRIMARY KEY,
				firm_id VARCHAR(191) NOT NULL,
				template_id VARCHAR(191) NOT NULL,
				positions_json LONGTEXT NOT NULL,
				created_at DATETIME NOT NULL,
				updated_at DATETIME NOT NULL,
				UNIQUE KEY uniq_firm_template (firm_id, template_id)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
		);

		$this->pdo->exec(
			'CREATE TABLE IF NOT EXISTS form_custom_fields (
				id VARCHAR(64) NOT NULL PRIMARY KEY,
				link_id VARCHAR(191) NOT NULL UNIQUE,
				display_name VARCHAR(255) NOT NULL,
				field_type VARCHAR(64) NOT NULL DEFAULT \'text\',
				matching_tag VARCHAR(255) NOT NULL DEFAULT \'\',
				value_text TEXT NOT NULL,
				location ENUM(\'firm\', \'client\', \'court\', \'case\') NOT NULL,
				created_at DATETIME NOT NULL,
				updated_at DATETIME NOT NULL
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
		);
		$this->ensureFormCustomFieldsColumns();
		$this->ensureCourtTables();
		$this->ensureAttorneyTables();
	}

	private function allowedFieldManagerLocations(): array {
		return ['firm', 'attorney', 'client', 'court', 'case'];
	}

	private function ensureCourtTables(): void {
		if (!$this->pdo) {
			return;
		}
		$this->pdo->exec(
			'CREATE TABLE IF NOT EXISTS court_locations (
				id VARCHAR(64) NOT NULL PRIMARY KEY,
				court_system VARCHAR(16) NOT NULL DEFAULT \'state\',
				state VARCHAR(8) NOT NULL DEFAULT \'CA\',
				county VARCHAR(128) NOT NULL DEFAULT \'\',
				court_name VARCHAR(512) NOT NULL,
				street VARCHAR(512) NOT NULL DEFAULT \'\',
				mailing_address VARCHAR(512) NOT NULL DEFAULT \'\',
				city VARCHAR(128) NOT NULL DEFAULT \'\',
				state_code VARCHAR(8) NOT NULL DEFAULT \'CA\',
				zip VARCHAR(16) NOT NULL DEFAULT \'\',
				phone VARCHAR(64) NOT NULL DEFAULT \'\',
				source VARCHAR(64) NOT NULL DEFAULT \'\',
				source_id VARCHAR(128) NOT NULL DEFAULT \'\',
				updated_at DATETIME NOT NULL,
				KEY idx_court_loc_county (county),
				KEY idx_court_loc_city (city),
				KEY idx_court_loc_zip (zip),
				KEY idx_court_loc_system (court_system)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
		);
		$this->pdo->exec(
			'CREATE TABLE IF NOT EXISTS court_departments (
				id VARCHAR(64) NOT NULL PRIMARY KEY,
				location_id VARCHAR(64) NOT NULL,
				department VARCHAR(64) NOT NULL DEFAULT \'\',
				floor VARCHAR(32) NOT NULL DEFAULT \'\',
				room VARCHAR(64) NOT NULL DEFAULT \'\',
				phone VARCHAR(64) NOT NULL DEFAULT \'\',
				source VARCHAR(64) NOT NULL DEFAULT \'\',
				updated_at DATETIME NOT NULL,
				KEY idx_court_dept_loc (location_id),
				KEY idx_court_dept_dept (department)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
		);
		$this->ensureFormCustomFieldsLocationCourtEnum();
		$this->ensureCourtSystemColumn();
	}

	private function ensureAttorneyTables(): void {
		if (!$this->pdo) {
			return;
		}
		$this->pdo->exec(
			'CREATE TABLE IF NOT EXISTS mvp_attorneys (
				id VARCHAR(64) NOT NULL PRIMARY KEY,
				display_name VARCHAR(512) NOT NULL DEFAULT \'\',
				field_values_json LONGTEXT NOT NULL,
				created_at DATETIME NOT NULL,
				updated_at DATETIME NULL
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
		);
	}

	private function ensureCourtSystemColumn(): void {
		if (!$this->pdo) {
			return;
		}
		try {
			$dbName = $this->pdo->query('SELECT DATABASE()')->fetchColumn();
			if (!$dbName) {
				return;
			}
			$stmt = $this->pdo->prepare(
				'SELECT COUNT(*) FROM information_schema.COLUMNS
				 WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :tbl AND COLUMN_NAME = :col'
			);
			$stmt->execute([':db' => (string)$dbName, ':tbl' => 'court_locations', ':col' => 'court_system']);
			if ((int)$stmt->fetchColumn() > 0) {
				return;
			}
			$this->pdo->exec(
				"ALTER TABLE court_locations ADD COLUMN court_system VARCHAR(16) NOT NULL DEFAULT 'state' AFTER id"
			);
			$this->pdo->exec('ALTER TABLE court_locations ADD KEY idx_court_loc_system (court_system)');
		} catch (\Throwable $e) {
			$this->logger->warning('court_locations court_system migration failed: ' . $e->getMessage());
		}
	}

	/** @param array<string, mixed> $loc */
	private function normalizeCourtLocationRow(array $loc): array {
		$system = strtolower(trim((string)($loc['courtSystem'] ?? $loc['court_system'] ?? 'state')));
		if (!in_array($system, ['state', 'federal'], true)) {
			$system = 'state';
		}
		$id = (string)($loc['id'] ?? '');
		return [
			'id' => $id,
			'courtSystem' => $system,
			'state' => (string)($loc['state'] ?? ($system === 'federal' ? 'US' : 'CA')),
			'county' => (string)($loc['county'] ?? ''),
			'courtName' => (string)($loc['courtName'] ?? $loc['court_name'] ?? ''),
			'street' => (string)($loc['street'] ?? ''),
			'mailingAddress' => (string)($loc['mailingAddress'] ?? $loc['mailing_address'] ?? ''),
			'city' => (string)($loc['city'] ?? ''),
			'stateCode' => (string)($loc['stateCode'] ?? $loc['state_code'] ?? ($system === 'federal' ? '' : 'CA')),
			'zip' => (string)($loc['zip'] ?? ''),
			'phone' => (string)($loc['phone'] ?? ''),
			'source' => (string)($loc['source'] ?? ''),
			'sourceId' => (string)($loc['sourceId'] ?? $loc['source_id'] ?? ''),
			'departments' => is_array($loc['departments'] ?? null) ? $loc['departments'] : [],
		];
	}

	private function ensureFormCustomFieldsLocationCourtEnum(): void {
		if (!$this->pdo) {
			return;
		}
		try {
			$stmt = $this->pdo->query(
				"SELECT COLUMN_TYPE FROM information_schema.COLUMNS
				 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'form_custom_fields' AND COLUMN_NAME = 'location'"
			);
			$row = $stmt ? $stmt->fetch(\PDO::FETCH_ASSOC) : false;
			$type = strtolower((string)($row['COLUMN_TYPE'] ?? ''));
			if ($type !== '' && strpos($type, 'attorney') === false) {
				$this->pdo->exec(
					"ALTER TABLE form_custom_fields MODIFY location ENUM('firm','attorney','client','court','case') NOT NULL"
				);
			}
		} catch (\Throwable $e) {
			$this->logger->warning('form_custom_fields court enum migration failed: ' . $e->getMessage());
		}
	}

	private function ensureFormTemplatesDetectedFirmColumn(): void {
		if (!$this->pdo) {
			return;
		}
		try {
			$dbName = $this->pdo->query('SELECT DATABASE()')->fetchColumn();
			if (!$dbName) {
				return;
			}
			$stmt = $this->pdo->prepare(
				'SELECT COUNT(*) FROM information_schema.COLUMNS
				 WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :tbl AND COLUMN_NAME = :col'
			);
			$stmt->execute([':db' => (string)$dbName, ':tbl' => 'form_templates', ':col' => 'detected_firm_name']);
			if ((int) $stmt->fetchColumn() > 0) {
				return;
			}
			$this->pdo->exec(
				'ALTER TABLE form_templates ADD COLUMN detected_firm_name VARCHAR(512) NOT NULL DEFAULT \'\' AFTER source_file_name'
			);
		} catch (\Throwable $e) {
			$this->logger->warning('form_templates migration detected_firm_name failed: ' . $e->getMessage());
		}
	}

	private function ensureFormTemplatesFormLocationColumn(): void {
		if (!$this->pdo) {
			return;
		}
		try {
			$dbName = $this->pdo->query('SELECT DATABASE()')->fetchColumn();
			if (!$dbName) {
				return;
			}
			$stmt = $this->pdo->prepare(
				'SELECT COUNT(*) FROM information_schema.COLUMNS
				 WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :tbl AND COLUMN_NAME = :col'
			);
			$stmt->execute([':db' => (string)$dbName, ':tbl' => 'form_templates', ':col' => 'form_location']);
			if ((int) $stmt->fetchColumn() > 0) {
				return;
			}
			$this->pdo->exec(
				'ALTER TABLE form_templates ADD COLUMN form_location VARCHAR(1024) NOT NULL DEFAULT \'\' AFTER detected_firm_name'
			);
		} catch (\Throwable $e) {
			$this->logger->warning('form_templates migration form_location failed: ' . $e->getMessage());
		}
	}

	private function schemaColumnExists(string $table, string $column): bool {
		if (!$this->pdo) {
			return false;
		}
		try {
			$dbName = $this->pdo->query('SELECT DATABASE()')->fetchColumn();
			if (!$dbName) {
				return false;
			}
			$stmt = $this->pdo->prepare(
				'SELECT COUNT(*) FROM information_schema.COLUMNS
				 WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :tbl AND COLUMN_NAME = :col'
			);
			$stmt->execute([':db' => (string)$dbName, ':tbl' => $table, ':col' => $column]);
			return (int) $stmt->fetchColumn() > 0;
		} catch (\Throwable $e) {
			$this->logger->warning('schemaColumnExists failed: ' . $e->getMessage());
			return false;
		}
	}

	/**
	 * Whether form_location is usable for SELECT/INSERT. information_schema is unreliable on some hosts
	 * (permissions, schema name mismatch), so we fall back to SHOW COLUMNS / a zero-row SELECT.
	 */
	private function detectFormTemplatesFormLocationColumn(): bool {
		if (!$this->pdo) {
			return false;
		}
		if ($this->schemaColumnExists('form_templates', 'form_location')) {
			return true;
		}
		try {
			$st = $this->pdo->query('SHOW COLUMNS FROM `form_templates` LIKE \'form_location\'');
			if ($st && $st->fetch()) {
				return true;
			}
		} catch (\Throwable $e) {
			$this->logger->warning('SHOW COLUMNS form_location failed: ' . $e->getMessage());
		}
		try {
			$this->pdo->query('SELECT `form_location` FROM `form_templates` LIMIT 0');
			return true;
		} catch (\Throwable $e) {
			return false;
		}
	}

	private function ensureFormCustomFieldsColumns(): void {
		if (!$this->pdo) {
			return;
		}
		try {
			$dbName = $this->pdo->query('SELECT DATABASE()')->fetchColumn();
			if (!$dbName) {
				return;
			}
			$hasColumn = function (string $column) use ($dbName): bool {
				$stmt = $this->pdo->prepare(
					'SELECT COUNT(*) FROM information_schema.COLUMNS
					 WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :tbl AND COLUMN_NAME = :col'
				);
				$stmt->execute([':db' => (string)$dbName, ':tbl' => 'form_custom_fields', ':col' => $column]);
				return (int)$stmt->fetchColumn() > 0;
			};
			if (!$hasColumn('field_type')) {
				$this->pdo->exec('ALTER TABLE form_custom_fields ADD COLUMN field_type VARCHAR(64) NOT NULL DEFAULT \'text\' AFTER display_name');
			}
			if (!$hasColumn('matching_tag')) {
				$this->pdo->exec('ALTER TABLE form_custom_fields ADD COLUMN matching_tag VARCHAR(255) NOT NULL DEFAULT \'\' AFTER field_type');
			}
			if (!$hasColumn('is_system')) {
				$this->pdo->exec('ALTER TABLE form_custom_fields ADD COLUMN is_system TINYINT(1) NOT NULL DEFAULT 0 AFTER location');
			}
		} catch (\Throwable $e) {
			$this->logger->warning('form_custom_fields migration failed: ' . $e->getMessage());
		}
	}

	/** Clients, projects, documents, field values, vault files — Phase 1 checklist: DB-backed app data when PDO connects. */
	private function bootstrapMvpEntityTables(): void {
		if (!$this->pdo) {
			return;
		}
		$ddl = [
			'mvp_clients' => 'CREATE TABLE IF NOT EXISTS mvp_clients (
				id VARCHAR(64) NOT NULL PRIMARY KEY,
				display_name VARCHAR(512) NOT NULL DEFAULT \'\',
				email VARCHAR(512) NOT NULL DEFAULT \'\',
				phone VARCHAR(128) NOT NULL DEFAULT \'\',
				company VARCHAR(512) NOT NULL DEFAULT \'\',
				address TEXT NOT NULL,
				notes TEXT NOT NULL,
				status VARCHAR(64) NOT NULL DEFAULT \'\',
				created_at DATETIME NOT NULL,
				updated_at DATETIME NULL
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
			'mvp_projects' => 'CREATE TABLE IF NOT EXISTS mvp_projects (
				id VARCHAR(64) NOT NULL PRIMARY KEY,
				client_id VARCHAR(64) NOT NULL DEFAULT \'\',
				name VARCHAR(512) NOT NULL,
				status VARCHAR(64) NOT NULL DEFAULT \'in_progress\',
				created_at DATETIME NOT NULL,
				updated_at DATETIME NULL
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
			'mvp_project_documents' => 'CREATE TABLE IF NOT EXISTS mvp_project_documents (
				id VARCHAR(64) NOT NULL PRIMARY KEY,
				project_id VARCHAR(64) NOT NULL,
				template_id VARCHAR(191) NOT NULL,
				status VARCHAR(64) NOT NULL DEFAULT \'in_progress\',
				created_at DATETIME NOT NULL,
				updated_at DATETIME NULL,
				extra_json LONGTEXT NULL
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
			'mvp_field_values' => 'CREATE TABLE IF NOT EXISTS mvp_field_values (
				id VARCHAR(64) NOT NULL PRIMARY KEY,
				project_document_id VARCHAR(64) NOT NULL,
				field_key VARCHAR(512) NOT NULL,
				value_text LONGTEXT NOT NULL,
				updated_at DATETIME NOT NULL,
				KEY idx_fv_pd (project_document_id)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
			'mvp_client_templates' => 'CREATE TABLE IF NOT EXISTS mvp_client_templates (
				id VARCHAR(64) NOT NULL PRIMARY KEY,
				client_id VARCHAR(64) NOT NULL,
				template_id VARCHAR(191) NOT NULL,
				label VARCHAR(512) NOT NULL DEFAULT \'\',
				created_at DATETIME NOT NULL,
				UNIQUE KEY uq_ct (client_id, template_id(100))
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
			'mvp_client_field_mappings' => 'CREATE TABLE IF NOT EXISTS mvp_client_field_mappings (
				id VARCHAR(64) NOT NULL PRIMARY KEY,
				client_id VARCHAR(64) NOT NULL,
				template_id VARCHAR(191) NOT NULL,
				mapping_json LONGTEXT NOT NULL,
				created_at DATETIME NOT NULL,
				updated_at DATETIME NOT NULL,
				UNIQUE KEY uq_cfm (client_id, template_id(100))
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
			'mvp_activities' => 'CREATE TABLE IF NOT EXISTS mvp_activities (
				id VARCHAR(64) NOT NULL PRIMARY KEY,
				type VARCHAR(64) NOT NULL,
				message TEXT NOT NULL,
				meta_json LONGTEXT NOT NULL,
				created_at DATETIME NOT NULL,
				KEY idx_act_created (created_at)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
			'mvp_client_files' => 'CREATE TABLE IF NOT EXISTS mvp_client_files (
				id VARCHAR(64) NOT NULL PRIMARY KEY,
				client_id VARCHAR(64) NOT NULL,
				project_id VARCHAR(64) NOT NULL DEFAULT \'\',
				filename VARCHAR(512) NOT NULL,
				original_name VARCHAR(512) NOT NULL,
				mime_type VARCHAR(255) NOT NULL DEFAULT \'\',
				size_bytes INT NOT NULL DEFAULT 0,
				uploaded_at DATETIME NOT NULL,
				KEY idx_cf_client (client_id)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
			'mvp_app_settings' => 'CREATE TABLE IF NOT EXISTS mvp_app_settings (
				setting_key VARCHAR(191) NOT NULL PRIMARY KEY,
				setting_value LONGTEXT NOT NULL,
				updated_at DATETIME NOT NULL
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
			'mvp_attorneys' => 'CREATE TABLE IF NOT EXISTS mvp_attorneys (
				id VARCHAR(64) NOT NULL PRIMARY KEY,
				display_name VARCHAR(512) NOT NULL DEFAULT \'\',
				field_values_json LONGTEXT NOT NULL,
				created_at DATETIME NOT NULL,
				updated_at DATETIME NULL
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
		];
		foreach ($ddl as $sql) {
			$this->pdo->exec($sql);
		}
	}

	private function emptyDbTemplate(): array {
		return [
			'clients' => [],
			'projects' => [],
			'projectDocuments' => [],
			'fieldValues' => [],
			'clientFiles' => [],
			'clientTemplates' => [],
			'clientFieldMappings' => [],
			'formTemplates' => [],
			'firmFieldAlignments' => [],
			'formCustomFields' => [],
			'appSettings' => [],
			'activities' => [],
			'attorneys' => [],
		];
	}

	private function snapshotHasMvpEntityData(array $s): bool {
		foreach (['clients', 'projects', 'projectDocuments', 'fieldValues', 'clientFiles', 'clientTemplates', 'clientFieldMappings', 'activities'] as $k) {
			if (!empty($s[$k]) && is_array($s[$k])) {
				return true;
			}
		}
		return false;
	}

	private function mvpMysqlEntityRowCount(): int {
		if (!$this->pdo) {
			return 0;
		}
		$tables = [
			'mvp_clients',
			'mvp_projects',
			'mvp_project_documents',
			'mvp_field_values',
			'mvp_client_templates',
			'mvp_client_field_mappings',
			'mvp_activities',
			'mvp_client_files',
		];
		$n = 0;
		foreach ($tables as $t) {
			$n += (int)$this->pdo->query('SELECT COUNT(*) FROM `' . $t . '`')->fetchColumn();
		}
		return $n;
	}

	/** One-time import from mvp.json when MySQL entity tables are empty (checklist: move off JSON when DB available). */
	private function maybeMigrateJsonSnapshotToMysql(array $snapshot): void {
		if (!$this->pdo || $this->mvpMysqlEntityRowCount() > 0 || !$this->snapshotHasMvpEntityData($snapshot)) {
			return;
		}
		$this->db = array_merge($this->emptyDbTemplate(), $snapshot);
		$this->syncMemoryToMysql();
		$this->logger->info('Migrated MVP entity snapshot from JSON file into MySQL', ['path' => $this->path]);
	}

	/**
	 * One-time import for Phase 1 tables that are not part of syncMemoryToMysql().
	 * Needed when MySQL becomes available after JSON had been used in production.
	 */
	private function maybeMigrateJsonPhaseOneTables(array $snapshot): void {
		if (!$this->pdo) {
			return;
		}
		$nowIso = date(DATE_ATOM);
		// Migrate form custom fields if the MySQL table is empty.
		$customRows = $snapshot['formCustomFields'] ?? [];
		if ($this->tableRowCount('form_custom_fields') === 0 && is_array($customRows) && !empty($customRows)) {
			$stmt = $this->pdo->prepare(
				'INSERT INTO form_custom_fields (id, link_id, display_name, field_type, matching_tag, value_text, location, is_system, created_at, updated_at)
				 VALUES (:id, :link_id, :display_name, :field_type, :matching_tag, :value_text, :location, :is_system, :created_at, :updated_at)
				 ON DUPLICATE KEY UPDATE
				 display_name = VALUES(display_name),
				 field_type = VALUES(field_type),
				 matching_tag = VALUES(matching_tag),
				 value_text = VALUES(value_text),
				 location = VALUES(location),
				 is_system = VALUES(is_system),
				 updated_at = VALUES(updated_at)'
			);
			$inserted = 0;
			foreach ($customRows as $row) {
				if (!is_array($row)) {
					continue;
				}
				$id = preg_replace('/[^a-zA-Z0-9_-]/', '', trim((string)($row['id'] ?? '')));
				$linkId = preg_replace('/[^a-z0-9_]/', '_', strtolower(trim((string)($row['linkId'] ?? ''))));
				if ($id === '' || $linkId === '') {
					continue;
				}
				$location = strtolower(trim((string)($row['location'] ?? 'firm')));
				if (!in_array($location, $this->allowedFieldManagerLocations(), true)) {
					$location = 'firm';
				}
				$displayName = trim((string)($row['displayName'] ?? ''));
				if ($displayName === '') {
					$displayName = $linkId;
				}
				$fieldType = $this->normalizeFieldType((string)($row['fieldType'] ?? 'text'));
				$matchingTag = mb_substr(trim((string)($row['matchingTag'] ?? '')), 0, 255);
				if ($matchingTag === '') {
					$matchingTag = $linkId;
				}
				$createdAt = (string)($row['createdAt'] ?? $nowIso);
				$updatedAt = (string)($row['updatedAt'] ?? $createdAt);
				$stmt->execute([
					':id' => $id,
					':link_id' => $linkId,
					':display_name' => $displayName,
					':field_type' => $fieldType,
					':matching_tag' => $matchingTag,
					':value_text' => (string)($row['value'] ?? ''),
					':location' => $location,
					':is_system' => !empty($row['isSystem']) ? 1 : 0,
					':created_at' => $this->toSqlDate($createdAt),
					':updated_at' => $this->toSqlDate($updatedAt),
				]);
				$inserted++;
			}
			if ($inserted > 0) {
				$this->logger->info('Migrated form_custom_fields snapshot into MySQL', ['rows' => $inserted]);
			}
		}

		// Migrate app settings if MySQL table is empty.
		$settings = $snapshot['appSettings'] ?? [];
		if ($this->tableRowCount('mvp_app_settings') === 0 && is_array($settings) && !empty($settings)) {
			$stmt = $this->pdo->prepare(
				'INSERT INTO mvp_app_settings (setting_key, setting_value, updated_at)
				 VALUES (:k, :v, :u)
				 ON DUPLICATE KEY UPDATE
				 setting_value = VALUES(setting_value),
				 updated_at = VALUES(updated_at)'
			);
			$inserted = 0;
			foreach ($settings as $key => $value) {
				$k = trim((string)$key);
				if ($k === '') {
					continue;
				}
				$stmt->execute([
					':k' => $k,
					':v' => (string)$value,
					':u' => $this->toSqlDate($nowIso),
				]);
				$inserted++;
			}
			if ($inserted > 0) {
				$this->logger->info('Migrated appSettings snapshot into MySQL', ['rows' => $inserted]);
			}
		}
	}

	private function tableRowCount(string $table): int {
		if (!$this->pdo) {
			return 0;
		}
		try {
			return (int)$this->pdo->query('SELECT COUNT(*) FROM `' . $table . '`')->fetchColumn();
		} catch (\Throwable $e) {
			$this->logger->warning('tableRowCount failed for ' . $table . ': ' . $e->getMessage());
			return 0;
		}
	}

	private function hydrateMemoryFromMysql(): void {
		if (!$this->pdo) {
			return;
		}
		$base = $this->emptyDbTemplate();

		$st = $this->pdo->query(
			'SELECT id, display_name, email, phone, company, address, notes, status, created_at, updated_at FROM mvp_clients ORDER BY created_at'
		);
		foreach ($st->fetchAll() as $r) {
			$base['clients'][] = [
				'id' => (string)$r['id'],
				'displayName' => (string)$r['display_name'],
				'email' => (string)$r['email'],
				'phone' => (string)$r['phone'],
				'company' => (string)$r['company'],
				'address' => (string)$r['address'],
				'notes' => (string)$r['notes'],
				'status' => (string)$r['status'],
				'createdAt' => $this->toIsoDate((string)$r['created_at']),
				'updatedAt' => $r['updated_at'] !== null ? $this->toIsoDate((string)$r['updated_at']) : date(DATE_ATOM),
			];
		}

		try {
			$st = $this->pdo->query(
				'SELECT id, display_name, field_values_json, created_at, updated_at FROM mvp_attorneys ORDER BY display_name, created_at'
			);
			foreach ($st->fetchAll() as $r) {
				$fieldValues = json_decode((string)($r['field_values_json'] ?? ''), true);
				$base['attorneys'][] = [
					'id' => (string)$r['id'],
					'displayName' => (string)$r['display_name'],
					'fieldValues' => is_array($fieldValues) ? $fieldValues : [],
					'createdAt' => $this->toIsoDate((string)$r['created_at']),
					'updatedAt' => $r['updated_at'] !== null ? $this->toIsoDate((string)$r['updated_at']) : date(DATE_ATOM),
				];
			}
		} catch (\Throwable $e) {
			$this->logger->warning('hydrate attorneys failed: ' . $e->getMessage());
		}

		$st = $this->pdo->query(
			'SELECT id, client_id, name, status, created_at, updated_at FROM mvp_projects ORDER BY created_at'
		);
		foreach ($st->fetchAll() as $r) {
			$row = [
				'id' => (string)$r['id'],
				'clientId' => (string)$r['client_id'],
				'name' => (string)$r['name'],
				'status' => (string)$r['status'],
				'createdAt' => $this->toIsoDate((string)$r['created_at']),
			];
			if ($r['updated_at'] !== null) {
				$row['updatedAt'] = $this->toIsoDate((string)$r['updated_at']);
			}
			$base['projects'][] = $row;
		}

		$st = $this->pdo->query(
			'SELECT id, project_id, template_id, status, created_at, updated_at, extra_json FROM mvp_project_documents ORDER BY created_at'
		);
		$core = ['id', 'projectId', 'templateId', 'status', 'createdAt', 'updatedAt'];
		foreach ($st->fetchAll() as $r) {
			$row = [
				'id' => (string)$r['id'],
				'projectId' => (string)$r['project_id'],
				'templateId' => (string)$r['template_id'],
				'status' => (string)$r['status'],
				'createdAt' => $this->toIsoDate((string)$r['created_at']),
			];
			if ($r['updated_at'] !== null) {
				$row['updatedAt'] = $this->toIsoDate((string)$r['updated_at']);
			}
			$extra = json_decode((string)($r['extra_json'] ?? ''), true);
			if (is_array($extra)) {
				foreach ($extra as $k => $v) {
					if (!in_array((string)$k, $core, true)) {
						$row[(string)$k] = $v;
					}
				}
			}
			$base['projectDocuments'][] = $row;
		}

		$st = $this->pdo->query(
			'SELECT id, project_document_id, field_key, value_text, updated_at FROM mvp_field_values'
		);
		foreach ($st->fetchAll() as $r) {
			$base['fieldValues'][] = [
				'id' => (string)$r['id'],
				'projectDocumentId' => (string)$r['project_document_id'],
				'key' => (string)$r['field_key'],
				'value' => (string)$r['value_text'],
				'updatedAt' => $this->toIsoDate((string)$r['updated_at']),
			];
		}

		$st = $this->pdo->query(
			'SELECT id, client_id, template_id, label, created_at FROM mvp_client_templates'
		);
		foreach ($st->fetchAll() as $r) {
			$base['clientTemplates'][] = [
				'id' => (string)$r['id'],
				'clientId' => (string)$r['client_id'],
				'templateId' => (string)$r['template_id'],
				'label' => (string)$r['label'],
				'createdAt' => $this->toIsoDate((string)$r['created_at']),
			];
		}

		$st = $this->pdo->query(
			'SELECT id, client_id, template_id, mapping_json, created_at, updated_at FROM mvp_client_field_mappings'
		);
		foreach ($st->fetchAll() as $r) {
			$map = json_decode((string)$r['mapping_json'], true);
			$base['clientFieldMappings'][] = [
				'id' => (string)$r['id'],
				'clientId' => (string)$r['client_id'],
				'templateId' => (string)$r['template_id'],
				'mapping' => is_array($map) ? $map : [],
				'createdAt' => $this->toIsoDate((string)$r['created_at']),
				'updatedAt' => $this->toIsoDate((string)$r['updated_at']),
			];
		}

		$st = $this->pdo->query(
			'SELECT id, type, message, meta_json, created_at FROM mvp_activities ORDER BY created_at DESC LIMIT 500'
		);
		foreach (array_reverse($st->fetchAll()) as $r) {
			$meta = json_decode((string)$r['meta_json'], true);
			$base['activities'][] = [
				'id' => (string)$r['id'],
				'type' => (string)$r['type'],
				'message' => (string)$r['message'],
				'meta' => is_array($meta) ? $meta : [],
				'createdAt' => $this->toIsoDate((string)$r['created_at']),
			];
		}

		$st = $this->pdo->query(
			'SELECT id, client_id, project_id, filename, original_name, mime_type, size_bytes, uploaded_at FROM mvp_client_files'
		);
		foreach ($st->fetchAll() as $r) {
			$base['clientFiles'][] = [
				'id' => (string)$r['id'],
				'clientId' => (string)$r['client_id'],
				'projectId' => (string)$r['project_id'],
				'filename' => (string)$r['filename'],
				'originalName' => (string)$r['original_name'],
				'mimeType' => (string)$r['mime_type'],
				'size' => (int)$r['size_bytes'],
				'uploadedAt' => $this->toIsoDate((string)$r['uploaded_at']),
			];
		}

		$this->db = $base;
	}

	/** Persist entity arrays to MySQL (form_templates / alignments / custom fields stay in Phase 1 tables only). */
	private function syncMemoryToMysql(): void {
		if (!$this->pdo) {
			return;
		}
		$this->pdo->beginTransaction();
		try {
			foreach ([
				'mvp_field_values',
				'mvp_project_documents',
				'mvp_projects',
				'mvp_client_files',
				'mvp_client_templates',
				'mvp_client_field_mappings',
				'mvp_activities',
				'mvp_attorneys',
				'mvp_clients',
			] as $t) {
				$this->pdo->exec('DELETE FROM `' . $t . '`');
			}

			$insClient = $this->pdo->prepare(
				'INSERT INTO mvp_clients (id, display_name, email, phone, company, address, notes, status, created_at, updated_at)
				 VALUES (:id, :display_name, :email, :phone, :company, :address, :notes, :status, :created_at, :updated_at)'
			);
			foreach ($this->db['clients'] ?? [] as $c) {
				$insClient->execute([
					':id' => (string)($c['id'] ?? ''),
					':display_name' => (string)($c['displayName'] ?? ''),
					':email' => (string)($c['email'] ?? ''),
					':phone' => (string)($c['phone'] ?? ''),
					':company' => (string)($c['company'] ?? ''),
					':address' => (string)($c['address'] ?? ''),
					':notes' => (string)($c['notes'] ?? ''),
					':status' => (string)($c['status'] ?? ''),
					':created_at' => $this->toSqlDate((string)($c['createdAt'] ?? date(DATE_ATOM))),
					':updated_at' => isset($c['updatedAt']) ? $this->toSqlDate((string)$c['updatedAt']) : null,
				]);
			}

			$insAttorney = $this->pdo->prepare(
				'INSERT INTO mvp_attorneys (id, display_name, field_values_json, created_at, updated_at)
				 VALUES (:id, :display_name, :field_values_json, :created_at, :updated_at)'
			);
			foreach ($this->db['attorneys'] ?? [] as $a) {
				$fieldValues = is_array($a['fieldValues'] ?? null) ? $a['fieldValues'] : [];
				$insAttorney->execute([
					':id' => (string)($a['id'] ?? ''),
					':display_name' => (string)($a['displayName'] ?? ''),
					':field_values_json' => json_encode($fieldValues, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
					':created_at' => $this->toSqlDate((string)($a['createdAt'] ?? date(DATE_ATOM))),
					':updated_at' => isset($a['updatedAt']) ? $this->toSqlDate((string)$a['updatedAt']) : null,
				]);
			}

			$insProj = $this->pdo->prepare(
				'INSERT INTO mvp_projects (id, client_id, name, status, created_at, updated_at)
				 VALUES (:id, :client_id, :name, :status, :created_at, :updated_at)'
			);
			foreach ($this->db['projects'] ?? [] as $p) {
				$insProj->execute([
					':id' => (string)($p['id'] ?? ''),
					':client_id' => (string)($p['clientId'] ?? ''),
					':name' => (string)($p['name'] ?? ''),
					':status' => (string)($p['status'] ?? 'in_progress'),
					':created_at' => $this->toSqlDate((string)($p['createdAt'] ?? date(DATE_ATOM))),
					':updated_at' => isset($p['updatedAt']) ? $this->toSqlDate((string)$p['updatedAt']) : null,
				]);
			}

			$coreDoc = ['id', 'projectId', 'templateId', 'status', 'createdAt', 'updatedAt'];
			$insDoc = $this->pdo->prepare(
				'INSERT INTO mvp_project_documents (id, project_id, template_id, status, created_at, updated_at, extra_json)
				 VALUES (:id, :project_id, :template_id, :status, :created_at, :updated_at, :extra_json)'
			);
			foreach ($this->db['projectDocuments'] ?? [] as $d) {
				$extra = [];
				foreach ($d as $k => $v) {
					if (!in_array((string)$k, $coreDoc, true)) {
						$extra[(string)$k] = $v;
					}
				}
				$insDoc->execute([
					':id' => (string)($d['id'] ?? ''),
					':project_id' => (string)($d['projectId'] ?? ''),
					':template_id' => (string)($d['templateId'] ?? ''),
					':status' => (string)($d['status'] ?? 'in_progress'),
					':created_at' => $this->toSqlDate((string)($d['createdAt'] ?? date(DATE_ATOM))),
					':updated_at' => isset($d['updatedAt']) ? $this->toSqlDate((string)$d['updatedAt']) : null,
					':extra_json' => $extra === [] ? null : json_encode($extra, JSON_UNESCAPED_SLASHES),
				]);
			}

			$insFv = $this->pdo->prepare(
				'INSERT INTO mvp_field_values (id, project_document_id, field_key, value_text, updated_at)
				 VALUES (:id, :project_document_id, :field_key, :value_text, :updated_at)'
			);
			foreach ($this->db['fieldValues'] ?? [] as $fv) {
				$insFv->execute([
					':id' => (string)($fv['id'] ?? ''),
					':project_document_id' => (string)($fv['projectDocumentId'] ?? ''),
					':field_key' => (string)($fv['key'] ?? ''),
					':value_text' => (string)($fv['value'] ?? ''),
					':updated_at' => $this->toSqlDate((string)($fv['updatedAt'] ?? date(DATE_ATOM))),
				]);
			}

			$insCt = $this->pdo->prepare(
				'INSERT INTO mvp_client_templates (id, client_id, template_id, label, created_at)
				 VALUES (:id, :client_id, :template_id, :label, :created_at)'
			);
			foreach ($this->db['clientTemplates'] ?? [] as $r) {
				$insCt->execute([
					':id' => (string)($r['id'] ?? ''),
					':client_id' => (string)($r['clientId'] ?? ''),
					':template_id' => (string)($r['templateId'] ?? ''),
					':label' => (string)($r['label'] ?? ''),
					':created_at' => $this->toSqlDate((string)($r['createdAt'] ?? date(DATE_ATOM))),
				]);
			}

			$insCfm = $this->pdo->prepare(
				'INSERT INTO mvp_client_field_mappings (id, client_id, template_id, mapping_json, created_at, updated_at)
				 VALUES (:id, :client_id, :template_id, :mapping_json, :created_at, :updated_at)'
			);
			foreach ($this->db['clientFieldMappings'] ?? [] as $r) {
				$insCfm->execute([
					':id' => (string)($r['id'] ?? ''),
					':client_id' => (string)($r['clientId'] ?? ''),
					':template_id' => (string)($r['templateId'] ?? ''),
					':mapping_json' => json_encode($r['mapping'] ?? [], JSON_UNESCAPED_SLASHES),
					':created_at' => $this->toSqlDate((string)($r['createdAt'] ?? date(DATE_ATOM))),
					':updated_at' => $this->toSqlDate((string)($r['updatedAt'] ?? date(DATE_ATOM))),
				]);
			}

			$insAct = $this->pdo->prepare(
				'INSERT INTO mvp_activities (id, type, message, meta_json, created_at)
				 VALUES (:id, :type, :message, :meta_json, :created_at)'
			);
			foreach ($this->db['activities'] ?? [] as $a) {
				$insAct->execute([
					':id' => (string)($a['id'] ?? ''),
					':type' => (string)($a['type'] ?? ''),
					':message' => (string)($a['message'] ?? ''),
					':meta_json' => json_encode($a['meta'] ?? [], JSON_UNESCAPED_SLASHES),
					':created_at' => $this->toSqlDate((string)($a['createdAt'] ?? date(DATE_ATOM))),
				]);
			}

			$insCf = $this->pdo->prepare(
				'INSERT INTO mvp_client_files (id, client_id, project_id, filename, original_name, mime_type, size_bytes, uploaded_at)
				 VALUES (:id, :client_id, :project_id, :filename, :original_name, :mime_type, :size_bytes, :uploaded_at)'
			);
			foreach ($this->db['clientFiles'] ?? [] as $f) {
				$insCf->execute([
					':id' => (string)($f['id'] ?? ''),
					':client_id' => (string)($f['clientId'] ?? ''),
					':project_id' => (string)($f['projectId'] ?? ''),
					':filename' => (string)($f['filename'] ?? ''),
					':original_name' => (string)($f['originalName'] ?? ''),
					':mime_type' => (string)($f['mimeType'] ?? ''),
					':size_bytes' => (int)($f['size'] ?? 0),
					':uploaded_at' => $this->toSqlDate((string)($f['uploadedAt'] ?? date(DATE_ATOM))),
				]);
			}

			$this->pdo->commit();
		} catch (\Throwable $e) {
			$this->pdo->rollBack();
			throw $e;
		}
	}

	private function toSqlDate(string $isoDate): string {
		$ts = strtotime($isoDate);
		return $ts !== false ? date('Y-m-d H:i:s', $ts) : date('Y-m-d H:i:s');
	}

	private function toIsoDate(string $sqlDate): string {
		$ts = strtotime($sqlDate);
		return $ts !== false ? date(DATE_ATOM, $ts) : date(DATE_ATOM);
	}

	private function loadFromJsonFile(): array {
		$emptyDb = $this->emptyDbTemplate();

		if (!file_exists($this->path)) {
			$this->logger->info('Data file does not exist, starting with empty database', ['path' => $this->path]);
			return $emptyDb;
		}

		$raw = @file_get_contents($this->path);
		if ($raw === false) {
			$this->logger->error('Failed to read data file', ['path' => $this->path]);
			return $emptyDb;
		}

		$data = json_decode($raw, true);
		if (json_last_error() !== JSON_ERROR_NONE) {
			$this->logger->error('JSON decode error in data file', [
				'path' => $this->path,
				'error' => json_last_error_msg(),
			]);
			$backupPath = $this->path . '.corrupted.' . date('Y-m-d_H-i-s');
			@copy($this->path, $backupPath);
			$this->logger->info('Backed up corrupted file', ['backup' => $backupPath]);
			return $emptyDb;
		}

		$this->logger->debug('Data loaded successfully', [
			'clients' => count($data['clients'] ?? []),
			'projects' => count($data['projects'] ?? []),
			'documents' => count($data['projectDocuments'] ?? []),
		]);

		return array_merge($emptyDb, $data ?? []);
	}

	private function save(): void {
		if ($this->pdo) {
			try {
				$this->syncMemoryToMysql();
			} catch (\Throwable $e) {
				$this->logger->error('MySQL entity sync failed: ' . $e->getMessage());
			}
			return;
		}
		try {
			$encoded = json_encode($this->db, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
			if ($encoded === false) {
				$this->logger->error('JSON encode failed', ['error' => json_last_error_msg()]);
				throw new \RuntimeException('Failed to encode data to JSON');
			}
			
			$tmp = $this->path . '.tmp';
			if (file_put_contents($tmp, $encoded, LOCK_EX) === false) {
				$this->logger->error('Failed to write temporary file', ['path' => $tmp]);
				throw new \RuntimeException('Failed to write data file');
			}
			
			if (!@rename($tmp, $this->path)) {
				$this->logger->error('Failed to rename temporary file', ['from' => $tmp, 'to' => $this->path]);
				throw new \RuntimeException('Failed to save data file');
			}
			
			$this->logger->debug('Data saved successfully');
		} catch (\Throwable $e) {
			$this->logger->error('Save operation failed: ' . $e->getMessage());
			// Don't re-throw - allow application to continue even if save fails
		}
	}

	private function newId(string $prefix): string {
		try {
			$rand = bin2hex(random_bytes(8));
		} catch (\Throwable $e) {
			$rand = bin2hex(openssl_random_pseudo_bytes(8) ?: md5(uniqid((string)microtime(true), true)));
		}
		return $prefix . '_' . substr($rand, 0, 12);
	}

	public function getDataPath(): string {
		return $this->path;
	}

	/** Row counts in MySQL when connected (null if JSON fallback). */
	public function getMvpEntityCountsFromDb(): ?array {
		if (!$this->pdo) {
			return null;
		}
		$mvp = [
			'clients' => 'mvp_clients',
			'projects' => 'mvp_projects',
			'project_documents' => 'mvp_project_documents',
			'field_values' => 'mvp_field_values',
			'client_templates' => 'mvp_client_templates',
			'client_field_mappings' => 'mvp_client_field_mappings',
			'activities' => 'mvp_activities',
			'client_files' => 'mvp_client_files',
		];
		$out = [];
		foreach ($mvp as $label => $table) {
			$out[$label] = (int)$this->pdo->query('SELECT COUNT(*) FROM `' . $table . '`')->fetchColumn();
		}
		$out['form_templates'] = (int)$this->pdo->query('SELECT COUNT(*) FROM form_templates')->fetchColumn();
		$out['firm_field_alignments'] = (int)$this->pdo->query('SELECT COUNT(*) FROM firm_field_alignments')->fetchColumn();
		$out['form_custom_fields'] = (int)$this->pdo->query('SELECT COUNT(*) FROM form_custom_fields')->fetchColumn();
		return $out;
	}

	/** Client vault file rows (persisted via {@see save()} → MySQL or JSON). */
	public function getClientFiles(): array {
		return array_values($this->db['clientFiles'] ?? []);
	}

	public function addClientFileRecord(array $fileRecord): void {
		if (trim((string)($fileRecord['id'] ?? '')) === '') {
			return;
		}
		if (!isset($this->db['clientFiles'])) {
			$this->db['clientFiles'] = [];
		}
		$this->db['clientFiles'][] = $fileRecord;
		$this->save();
	}

	/** @return array<string, mixed>|null Removed row, or null if not found */
	public function removeClientFileById(string $fileId): ?array {
		$removed = null;
		foreach ($this->db['clientFiles'] ?? [] as $idx => $file) {
			if (($file['id'] ?? '') === $fileId) {
				$removed = $file;
				unset($this->db['clientFiles'][$idx]);
				break;
			}
		}
		if ($removed === null) {
			return null;
		}
		$this->db['clientFiles'] = array_values($this->db['clientFiles']);
		$this->save();
		return $removed;
	}

	/** Optional backup of entity arrays to the legacy JSON path (MySQL remains authoritative when connected). */
	public function exportMvpEntitySnapshotToJsonFile(): void {
		$t = $this->emptyDbTemplate();
		foreach (array_keys($t) as $k) {
			$t[$k] = $this->db[$k] ?? [];
		}
		$encoded = json_encode($t, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
		if ($encoded === false) {
			return;
		}
		$tmp = $this->path . '.tmp';
		if (file_put_contents($tmp, $encoded, LOCK_EX) === false) {
			return;
		}
		@rename($tmp, $this->path);
	}

	// Clients
	public function getClients(): array { return $this->db['clients']; }
	public function createClient(string $displayName, string $email = '', string $phone = '', string $company = '', string $address = '', string $notes = ''): array {
		$client = [ 'id' => $this->newId('c'), 'displayName' => $displayName, 'email' => $email, 'phone' => $phone, 'company' => $company, 'address' => $address, 'notes' => $notes, 'createdAt' => date(DATE_ATOM), 'updatedAt' => date(DATE_ATOM) ];
		$this->db['clients'][] = $client; $this->save(); return $client;
	}
	public function getClient(string $id): ?array { foreach ($this->db['clients'] as $c) if (($c['id'] ?? '') === $id) return $c; return null; }
	public function updateClient(string $id, string $displayName, string $email = '', string $phone = '', string $company = '', string $address = '', string $notes = ''): ?array {
		foreach ($this->db['clients'] as &$c) {
			if (($c['id'] ?? '') === $id) {
				$c['displayName'] = $displayName;
				$c['email'] = $email;
				$c['phone'] = $phone;
				$c['company'] = $company;
				$c['address'] = $address;
				$c['notes'] = $notes;
				$c['updatedAt'] = date(DATE_ATOM);
				$updated = $c;
				$this->save();
				return $updated;
			}
		}
		return null;
	}

	// Attorneys (firm roster — values keyed by Field Manager attorney field ids)
	public function getAttorneys(): array {
		return array_values($this->db['attorneys'] ?? []);
	}

	public function getAttorney(string $id): ?array {
		$id = preg_replace('/[^a-zA-Z0-9_-]/', '', trim($id));
		if ($id === '') {
			return null;
		}
		foreach ($this->db['attorneys'] ?? [] as $row) {
			if (($row['id'] ?? '') === $id) {
				return $row;
			}
		}
		return null;
	}

	/** @param array<string, string> $fieldValues */
	public function deriveAttorneyDisplayName(array $fieldValues): string {
		$rows = $this->getFieldManagerCustomFields('attorney');
		foreach ($rows as $row) {
			if (!is_array($row)) {
				continue;
			}
			$linkId = strtolower(trim((string)($row['linkId'] ?? '')));
			if ($linkId !== 'attorney_name') {
				continue;
			}
			$fid = preg_replace('/[^a-zA-Z0-9_-]/', '', trim((string)($row['id'] ?? '')));
			$name = trim((string)($fieldValues[$fid] ?? ''));
			if ($name !== '') {
				return $name;
			}
		}
		foreach ($fieldValues as $v) {
			$text = trim((string)$v);
			if ($text !== '') {
				return $text;
			}
		}
		return 'Untitled Attorney';
	}

	/** @param array<string, string> $fieldValues */
	public function createAttorney(array $fieldValues = []): array {
		$sanitized = [];
		foreach ($fieldValues as $k => $v) {
			$key = preg_replace('/[^a-zA-Z0-9_-]/', '', trim((string)$k));
			if ($key === '') {
				continue;
			}
			$sanitized[$key] = trim((string)$v);
		}
		$attorney = [
			'id' => $this->newId('att'),
			'displayName' => $this->deriveAttorneyDisplayName($sanitized),
			'fieldValues' => $sanitized,
			'createdAt' => date(DATE_ATOM),
			'updatedAt' => date(DATE_ATOM),
		];
		if (!isset($this->db['attorneys']) || !is_array($this->db['attorneys'])) {
			$this->db['attorneys'] = [];
		}
		$this->db['attorneys'][] = $attorney;
		$this->save();
		return $attorney;
	}

	/** @param array<string, string> $fieldValues */
	public function updateAttorney(string $id, array $fieldValues): ?array {
		$id = preg_replace('/[^a-zA-Z0-9_-]/', '', trim($id));
		if ($id === '') {
			return null;
		}
		$sanitized = [];
		foreach ($fieldValues as $k => $v) {
			$key = preg_replace('/[^a-zA-Z0-9_-]/', '', trim((string)$k));
			if ($key === '') {
				continue;
			}
			$sanitized[$key] = trim((string)$v);
		}
		foreach ($this->db['attorneys'] ?? [] as &$row) {
			if (($row['id'] ?? '') !== $id) {
				continue;
			}
			$row['fieldValues'] = $sanitized;
			$row['displayName'] = $this->deriveAttorneyDisplayName($sanitized);
			$row['updatedAt'] = date(DATE_ATOM);
			$updated = $row;
			$this->save();
			return $updated;
		}
		return null;
	}

	public function deleteAttorney(string $id): bool {
		$id = preg_replace('/[^a-zA-Z0-9_-]/', '', trim($id));
		if ($id === '') {
			return false;
		}
		$before = count($this->db['attorneys'] ?? []);
		$this->db['attorneys'] = array_values(array_filter(
			$this->db['attorneys'] ?? [],
			static fn(array $row): bool => ($row['id'] ?? '') !== $id
		));
		if (count($this->db['attorneys']) === $before) {
			return false;
		}
		$this->save();
		return true;
	}

	public function getProjectsByClient(string $clientId): array {
		return array_values(array_filter($this->db['projects'], fn($p) => ($p['clientId'] ?? '') === $clientId));
	}

	public function createProjectForClient(string $clientId, string $name): array {
		$proj = [ 'id' => $this->newId('p'), 'clientId' => $clientId, 'name' => $name, 'status' => 'in_progress', 'createdAt' => date(DATE_ATOM) ];
		$this->db['projects'][] = $proj; $this->save(); return $proj;
	}

	public function getProjects(): array { return $this->db['projects']; }
	public function getProject(string $id): ?array {
		foreach ($this->db['projects'] as $p) if ($p['id'] === $id) return $p; return null;
	}
	public function createProject(string $name): array {
		$proj = [ 'id' => $this->newId('p'), 'clientId' => '', 'name' => $name, 'status' => 'in_progress', 'createdAt' => date(DATE_ATOM) ];
		$this->db['projects'][] = $proj; $this->save(); return $proj;
	}

	public function assignClientToProject(string $projectId, string $clientId): ?array {
		foreach ($this->db['projects'] as &$p) {
			if ($p['id'] === $projectId) { $p['clientId'] = $clientId; $p['updatedAt'] = date(DATE_ATOM); $this->save(); return $p; }
		}
		return null;
	}

	public function getProjectDocuments(string $projectId): array {
		return array_values(array_filter($this->db['projectDocuments'], fn($d) => $d['projectId'] === $projectId));
	}
	public function getProjectDocumentById(string $id): ?array {
		foreach ($this->db['projectDocuments'] as $d) if ($d['id'] === $id) return $d; return null;
	}
	public function addProjectDocument(string $projectId, string $templateId): array {
		$doc = [ 'id' => $this->newId('pd'), 'projectId' => $projectId, 'templateId' => $templateId, 'status' => 'in_progress', 'createdAt' => date(DATE_ATOM) ];
		$this->db['projectDocuments'][] = $doc; 
		$this->touchProject($projectId);
		
		// Automatically initialize field values for the new document
		$this->initializeDocumentFields($doc['id'], $templateId);
		
		$this->save(); 
		return $doc;
	}

	/** @return array<string,mixed>|null */
	public function findProjectDocumentByTemplateId(string $projectId, string $templateId): ?array {
		$projectId = trim($projectId);
		$templateId = trim($templateId);
		if ($projectId === '' || $templateId === '') {
			return null;
		}
		foreach ($this->db['projectDocuments'] as $doc) {
			if (!is_array($doc)) {
				continue;
			}
			if ((string)($doc['projectId'] ?? '') !== $projectId) {
				continue;
			}
			if ((string)($doc['templateId'] ?? '') !== $templateId) {
				continue;
			}
			return $doc;
		}
		return null;
	}

	public function getFieldValues(string $projectDocumentId): array {
		$isDebug = getenv('MVP_DEBUG_LOG') === '1';
		$logFile = __DIR__ . '/../../logs/pdf_debug.log';
		if ($isDebug) {
			file_put_contents($logFile, date('Y-m-d H:i:s') . ' DATA DEBUG: Getting field values for PD ID: ' . $projectDocumentId . PHP_EOL, FILE_APPEND);
			file_put_contents($logFile, date('Y-m-d H:i:s') . ' DATA DEBUG: Total field values in DB: ' . count($this->db['fieldValues']) . PHP_EOL, FILE_APPEND);
		}
		
		$out = [];
		foreach ($this->db['fieldValues'] as $fv) {
			if ($isDebug) {
				file_put_contents($logFile, date('Y-m-d H:i:s') . ' DATA DEBUG: Checking field value: ' . json_encode($fv) . PHP_EOL, FILE_APPEND);
			}
			if ($fv['projectDocumentId'] === $projectDocumentId) {
				$out[$fv['key']] = $fv['value'];
				if ($isDebug) {
					file_put_contents($logFile, date('Y-m-d H:i:s') . ' DATA DEBUG: MATCH! Added: ' . $fv['key'] . ' = ' . $fv['value'] . PHP_EOL, FILE_APPEND);
				}
			}
		}
		
		if ($isDebug) {
			file_put_contents($logFile, date('Y-m-d H:i:s') . ' DATA DEBUG: Final output: ' . json_encode($out) . PHP_EOL, FILE_APPEND);
		}
		return $out;
	}
	public function saveFieldValues(string $projectDocumentId, array $kv): void {
		$isDebug = getenv('MVP_DEBUG_LOG') === '1';
		$logFile = __DIR__ . '/../../logs/pdf_debug.log';
		if ($isDebug) {
			file_put_contents($logFile, date('Y-m-d H:i:s') . ' SAVE VALUES: PD ID: ' . $projectDocumentId . PHP_EOL, FILE_APPEND);
			file_put_contents($logFile, date('Y-m-d H:i:s') . ' SAVE VALUES: Input data: ' . json_encode($kv) . PHP_EOL, FILE_APPEND);
			file_put_contents($logFile, date('Y-m-d H:i:s') . ' SAVE VALUES: Existing field values before: ' . count($this->db['fieldValues']) . PHP_EOL, FILE_APPEND);
		}
		
		// remove ALL existing field values for this project document
		$oldCount = count($this->db['fieldValues']);
		$this->db['fieldValues'] = array_values(array_filter($this->db['fieldValues'], fn($fv) => $fv['projectDocumentId'] !== $projectDocumentId));
		$newCount = count($this->db['fieldValues']);
		if ($isDebug) {
			file_put_contents($logFile, date('Y-m-d H:i:s') . ' SAVE VALUES: Removed ' . ($oldCount - $newCount) . ' existing values' . PHP_EOL, FILE_APPEND);
		}
		
		// add new field values
		$addedCount = 0;
		foreach ($kv as $k => $v) {
			// Only save non-empty values or explicitly set empty values (like unchecked checkboxes)
			if ($v !== '' || array_key_exists($k, $kv)) {
				$newFieldValue = [ 'id' => $this->newId('fv'), 'projectDocumentId' => $projectDocumentId, 'key' => $k, 'value' => $v, 'updatedAt' => date(DATE_ATOM) ];
				$this->db['fieldValues'][] = $newFieldValue;
				if ($isDebug) {
					file_put_contents($logFile, date('Y-m-d H:i:s') . ' SAVE VALUES: Added field: ' . json_encode($newFieldValue) . PHP_EOL, FILE_APPEND);
				}
				$addedCount++;
			}
		}
		
		if ($isDebug) {
			file_put_contents($logFile, date('Y-m-d H:i:s') . ' SAVE VALUES: Added ' . $addedCount . ' new values' . PHP_EOL, FILE_APPEND);
			file_put_contents($logFile, date('Y-m-d H:i:s') . ' SAVE VALUES: Total field values after: ' . count($this->db['fieldValues']) . PHP_EOL, FILE_APPEND);
		}
		
		// touch parent project updatedAt
		$projectId = null;
		foreach ($this->db['projectDocuments'] as $d) if ($d['id'] === $projectDocumentId) { $projectId = $d['projectId']; break; }
		if ($projectId) { $this->touchProject($projectId); }
		
		if ($isDebug) {
			file_put_contents($logFile, date('Y-m-d H:i:s') . ' SAVE VALUES: Saving to database...' . PHP_EOL, FILE_APPEND);
		}
		$this->save();
		if ($isDebug) {
			file_put_contents($logFile, date('Y-m-d H:i:s') . ' SAVE VALUES: Database saved successfully' . PHP_EOL, FILE_APPEND);
		}
	}

	/** Update a project's display name and touch updatedAt. */
	public function updateProjectName(string $projectId, string $newName): ?array {
		foreach ($this->db['projects'] as &$p) {
			if ($p['id'] === $projectId) {
				$p['name'] = $newName;
				$p['updatedAt'] = date(DATE_ATOM);
				$updated = $p;
				$this->save();
				return $updated;
			}
		}
		return null;
	}

	/** @return array<string, mixed> */
	public function getProjectViewConfig(string $projectId): array {
		$projectId = preg_replace('/[^a-zA-Z0-9_-]/', '', trim($projectId));
		if ($projectId === '') {
			return [];
		}
		$raw = $this->getAppSettingValue('project_view_config_' . $projectId, '');
		if ($raw === '') {
			return [];
		}
		$decoded = json_decode($raw, true);
		return is_array($decoded) ? $decoded : [];
	}

	/** @param array<string, mixed> $config */
	public function saveProjectViewConfig(string $projectId, array $config): array {
		$projectId = preg_replace('/[^a-zA-Z0-9_-]/', '', trim($projectId));
		if ($projectId === '') {
			return [];
		}
		$current = $this->getProjectViewConfig($projectId);
		$merged = array_merge($current, $config);
		$merged['updatedAt'] = date(DATE_ATOM);
		$this->setAppSettingValue(
			'project_view_config_' . $projectId,
			(string)json_encode($merged, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
		);
		$this->touchProject($projectId);
		return $merged;
	}

	public function updateProjectStatus(string $projectId, string $status): bool {
		foreach ($this->db['projects'] as &$p) {
			if (($p['id'] ?? '') === $projectId) {
				$p['status'] = $status;
				$p['updatedAt'] = date(DATE_ATOM);
				$this->save();
				return true;
			}
		}
		return false;
	}

	public function updateClientStatus(string $clientId, string $status): bool {
		foreach ($this->db['clients'] as &$c) {
			if (($c['id'] ?? '') === $clientId) {
				$c['status'] = $status;
				$c['updatedAt'] = date(DATE_ATOM);
				$this->save();
				return true;
			}
		}
		return false;
	}

	/** Delete a projectDocument and its associated fieldValues. */
	public function deleteProjectDocument(string $projectDocumentId): void {
		$projectId = null;
		foreach ($this->db['projectDocuments'] as $idx => $d) {
			if ($d['id'] === $projectDocumentId) {
				$projectId = $d['projectId'];
				unset($this->db['projectDocuments'][$idx]);
				break;
			}
		}
		$this->db['projectDocuments'] = array_values($this->db['projectDocuments']);
		$this->db['fieldValues'] = array_values(array_filter($this->db['fieldValues'], fn($fv) => $fv['projectDocumentId'] !== $projectDocumentId));
		if ($projectId) { $this->touchProject($projectId); }
		$this->save();
	}

	/** Delete a project and all nested documents/field values. */
	public function deleteProjectDeep(string $projectId): void {
		$docIds = array_column(
			array_filter($this->db['projectDocuments'], fn($d) => ($d['projectId'] ?? '') === $projectId),
			'id'
		);
		$this->db['projects'] = array_values(array_filter($this->db['projects'], fn($p) => ($p['id'] ?? '') !== $projectId));
		$this->db['projectDocuments'] = array_values(array_filter($this->db['projectDocuments'], fn($d) => ($d['projectId'] ?? '') !== $projectId));
		$this->db['fieldValues'] = array_values(array_filter($this->db['fieldValues'], fn($fv) => !in_array(($fv['projectDocumentId'] ?? ''), $docIds, true)));
		$this->save();
	}

	public function deleteClientDeep(string $clientId): void {
		$deletedProjectIds = array_column(
			array_filter($this->db['projects'], fn($p) => ($p['clientId'] ?? '') === $clientId),
			'id'
		);

		$this->db['clients'] = array_values(array_filter($this->db['clients'], fn($c) => ($c['id'] ?? '') !== $clientId));
		$this->db['projects'] = array_values(array_filter($this->db['projects'], fn($p) => ($p['clientId'] ?? '') !== $clientId));
		$this->db['projectDocuments'] = array_values(array_filter(
			$this->db['projectDocuments'],
			fn($pd) => !in_array(($pd['projectId'] ?? ''), $deletedProjectIds, true)
		));

		$remainingPdIds = array_column($this->db['projectDocuments'], 'id');
		$this->db['fieldValues'] = array_values(array_filter(
			$this->db['fieldValues'],
			fn($fv) => in_array(($fv['projectDocumentId'] ?? ''), $remainingPdIds, true)
		));
		$this->save();
	}

	/** @param array<string, mixed> $updates */
	public function updateProjectDocument(string $projectDocumentId, array $updates): bool {
		$projectId = null;
		foreach ($this->db['projectDocuments'] as &$d) {
			if (($d['id'] ?? '') === $projectDocumentId) {
				foreach ($updates as $k => $v) {
					$d[(string)$k] = $v;
				}
				$d['updatedAt'] = date(DATE_ATOM);
				$projectId = (string)($d['projectId'] ?? '');
				break;
			}
		}

		if ($projectId === null) {
			return false;
		}

		if ($projectId !== '') {
			$this->touchProject($projectId);
		}
		$this->save();
		return true;
	}

	/** Duplicate a project with its documents and field values. Returns the new project. */
	public function duplicateProjectDeep(string $projectId): ?array {
		$orig = null;
		foreach ($this->db['projects'] as $p) if ($p['id'] === $projectId) { $orig = $p; break; }
		if (!$orig) { return null; }
		$copy = $orig;
		$copy['id'] = $this->newId('p');
		$copy['name'] = ($orig['name'] ?? 'Untitled Project') . ' (Copy)';
		$copy['status'] = $orig['status'] ?? 'in_progress';
		$copy['createdAt'] = date(DATE_ATOM);
		$copy['updatedAt'] = $copy['createdAt'];
		$this->db['projects'][] = $copy;

		// Map old PD id -> new PD id
		$idMap = [];
		foreach ($this->db['projectDocuments'] as $d) {
			if ($d['projectId'] === $projectId) {
				$newDoc = $d;
				$newDoc['id'] = $this->newId('pd');
				$newDoc['projectId'] = $copy['id'];
				$newDoc['status'] = $d['status'] ?? 'in_progress';
				$newDoc['createdAt'] = date(DATE_ATOM);
				unset($newDoc['outputPath'], $newDoc['signedPath']);
				$this->db['projectDocuments'][] = $newDoc;
				$idMap[$d['id']] = $newDoc['id'];
			}
		}

		foreach ($this->db['fieldValues'] as $fv) {
			if (isset($idMap[$fv['projectDocumentId']])) {
				$this->db['fieldValues'][] = [
					'id' => $this->newId('fv'),
					'projectDocumentId' => $idMap[$fv['projectDocumentId']],
					'key' => $fv['key'],
					'value' => $fv['value'],
					'updatedAt' => date(DATE_ATOM)
				];
			}
		}

		$this->save();
		return $copy;
	}

	/**
	 * Automatically initialize field values for a new document
	 */
	private function initializeDocumentFields(string $projectDocumentId, string $templateId): void {
		$this->logger->info('Initializing fields for new document', [
			'projectDocumentId' => $projectDocumentId,
			'templateId' => $templateId
		]);
		
		// Get PDF path directly from template ID mapping (avoiding chicken-and-egg problem)
		$pdfPath = $this->getPdfPathForTemplate($templateId);
		
		if (empty($pdfPath) || !file_exists($pdfPath)) {
			$this->logger->warning('PDF file not found for field initialization', ['pdfPath' => $pdfPath, 'templateId' => $templateId]);
			return;
		}
		
		// Extract fields using the existing pipeline
		try {
			require_once __DIR__ . '/pdf_field_extractor.php';
			$extractor = new \WebPdfTimeSaver\Mvp\PdfFieldExtractor();
			$fields = $extractor->extractFieldPositions($pdfPath);
			
			if (empty($fields)) {
				$this->logger->warning('No fields extracted for initialization', ['templateId' => $templateId]);
				return;
			}
			
			// Generate/update positions file
			$dataDir = dirname($this->path);
			$positionFile = $dataDir . '/' . $templateId . '_positions.json';
			
			// Ensure data directory exists
			if (!is_dir($dataDir)) {
				mkdir($dataDir, 0755, true);
			}
			
			// Save positions to file
			$json = json_encode($fields, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
			file_put_contents($positionFile, $json);
			
			// Create field mapping from PDF field names to simple names
			$fieldMapping = $this->createFieldMapping($fields, $templateId);
			
			// Initialize empty field values for mapped fields
			$fieldsAdded = 0;
			foreach ($fieldMapping as $simpleKey => $pdfKey) {
				// Only add if not already exists
				$exists = false;
				foreach ($this->db['fieldValues'] as $fv) {
					if ($fv['projectDocumentId'] === $projectDocumentId && $fv['key'] === $simpleKey) {
						$exists = true;
						break;
					}
				}
				
				if (!$exists) {
					$this->db['fieldValues'][] = [
						'id' => $this->newId('fv'),
						'projectDocumentId' => $projectDocumentId,
						'key' => $simpleKey,
						'value' => '',
						'updatedAt' => date(DATE_ATOM)
					];
					$fieldsAdded++;
				}
			}
			
			$this->logger->info('Field initialization completed', [
				'projectDocumentId' => $projectDocumentId,
				'fieldsExtracted' => count($fields),
				'fieldsMapped' => count($fieldMapping),
				'fieldsAdded' => $fieldsAdded,
				'positionFile' => $positionFile
			]);
			
		} catch (\Exception $e) {
			$this->logger->error('Field initialization failed', [
				'projectDocumentId' => $projectDocumentId,
				'templateId' => $templateId,
				'error' => $e->getMessage()
			]);
		}
	}
	
	/**
	 * Get PDF path for template ID (direct mapping to avoid template registry dependency)
	 */
	private function getPdfPathForTemplate(string $templateId): string {
		$templatePdfPaths = [
			't_fl105_gc120' => __DIR__ . '/../../temp/fl105_decrypted.pdf',
			'fl100' => __DIR__ . '/../../temp/fl100_template.pdf',
			'fl105' => __DIR__ . '/../../temp/fl105_template.pdf',
			'w9' => __DIR__ . '/../../temp/w9_template.pdf'
		];
		
		return $templatePdfPaths[$templateId] ?? '';
	}
	
	/**
	 * Create field mapping from PDF field names to simple names
	 * Uses UniversalFieldMapper for dynamic, semantic-based mapping
	 */
	private function createFieldMapping(array $pdfFields, string $templateId): array {
		require_once __DIR__ . '/universal_field_mapper.php';
		require_once __DIR__ . '/field_analyzer.php';
		
		$mapper = new \WebPdfTimeSaver\Mvp\UniversalFieldMapper();
		$analyzer = new \WebPdfTimeSaver\Mvp\FieldAnalyzer();
		
		// Extract PDF field names
		$pdfFieldNames = array_keys($pdfFields);
		
		// Generate simple field names based on semantic analysis
		$mapping = [];
		foreach ($pdfFieldNames as $pdfFieldName) {
			// Analyze the PDF field name to generate a simple key
			$analysis = $analyzer->analyzeFieldName($pdfFieldName);
			$normalizedName = $analysis['normalizedName'];
			
			// Use normalized name as the simple key (it's already cleaned and simplified)
			$simpleKey = $normalizedName;
			
			// Ensure uniqueness - if key already exists, append number
			$originalKey = $simpleKey;
			$counter = 1;
			while (isset($mapping[$simpleKey])) {
				$simpleKey = $originalKey . '_' . $counter;
				$counter++;
			}
			
			$mapping[$simpleKey] = $pdfFieldName;
		}
		
		// Apply saved mapping preferences if they exist
		$savedMappings = $mapper->loadMappingPreferences($templateId);
		if (!empty($savedMappings)) {
			// Merge saved mappings (they're in reverse format: simpleKey => pdfFieldName)
			// But we need pdfFieldName => simpleKey, so reverse them
			$reversedSavedMappings = [];
			foreach ($savedMappings as $simpleKey => $pdfFieldName) {
				if (in_array($pdfFieldName, $pdfFieldNames)) {
					$reversedSavedMappings[$pdfFieldName] = $simpleKey;
				}
			}
			
			// Override with saved mappings
			foreach ($reversedSavedMappings as $pdfFieldName => $simpleKey) {
				// Remove old mapping with this PDF field name
				$mapping = array_filter($mapping, function($value) use ($pdfFieldName) {
					return $value !== $pdfFieldName;
				});
				// Add new mapping
				$mapping[$simpleKey] = $pdfFieldName;
			}
		}
		
		return $mapping;
	}

	private function touchProject(string $projectId): void {
		foreach ($this->db['projects'] as &$p) if ($p['id'] === $projectId) { $p['updatedAt'] = date(DATE_ATOM); break; }
	}


	private function getProjectIdFromDocument(string $projectDocumentId): ?string {
		foreach ($this->db['projectDocuments'] as $d) {
			if ($d['id'] === $projectDocumentId) {
				return $d['projectId'];
			}
		}
		return null;
	}

	/**
	 * Templates with no clientTemplates row are global (visible to every client).
	 * Rows tie a templateId to one client; that template is only visible on that client's matters.
	 */
	public function isTemplateVisibleToClient(?string $clientId, string $templateId): bool {
		$rows = array_values(array_filter($this->db['clientTemplates'] ?? [], static fn($r) => ($r['templateId'] ?? '') === $templateId));
		if ($rows === []) {
			return true;
		}
		if ($clientId === null || $clientId === '') {
			return false;
		}
		foreach ($rows as $r) {
			if (($r['clientId'] ?? '') === $clientId) {
				return true;
			}
		}
		return false;
	}

	public function registerClientTemplate(string $clientId, string $templateId, string $label = ''): void {
		if ($clientId === '' || $templateId === '') {
			return;
		}
		foreach ($this->db['clientTemplates'] ?? [] as $r) {
			if (($r['clientId'] ?? '') === $clientId && ($r['templateId'] ?? '') === $templateId) {
				return;
			}
		}
		$this->db['clientTemplates'][] = [
			'id' => $this->newId('ct'),
			'clientId' => $clientId,
			'templateId' => $templateId,
			'label' => $label,
			'createdAt' => date(DATE_ATOM),
		];
		$this->save();
	}

	/** @return array<string, string> */
	public function getClientFieldMapping(string $clientId, string $templateId): array {
		foreach ($this->db['clientFieldMappings'] ?? [] as $row) {
			if (($row['clientId'] ?? '') === $clientId && ($row['templateId'] ?? '') === $templateId) {
				$mapping = $row['mapping'] ?? [];
				return is_array($mapping) ? $mapping : [];
			}
		}
		return [];
	}

	/** @param array<string, string> $mapping */
	public function saveClientFieldMapping(string $clientId, string $templateId, array $mapping): void {
		$clean = [];
		foreach ($mapping as $fieldKey => $clientProp) {
			$k = trim((string)$fieldKey);
			$v = trim((string)$clientProp);
			if ($k !== '' && $v !== '') {
				$clean[$k] = $v;
			}
		}
		if (!isset($this->db['clientFieldMappings']) || !is_array($this->db['clientFieldMappings'])) {
			$this->db['clientFieldMappings'] = [];
		}
		foreach ($this->db['clientFieldMappings'] as &$row) {
			if (($row['clientId'] ?? '') === $clientId && ($row['templateId'] ?? '') === $templateId) {
				$row['mapping'] = $clean;
				$row['updatedAt'] = date(DATE_ATOM);
				unset($row);
				$this->save();
				return;
			}
		}
		unset($row);
		$this->db['clientFieldMappings'][] = [
			'id' => $this->newId('cfm'),
			'clientId' => $clientId,
			'templateId' => $templateId,
			'mapping' => $clean,
			'createdAt' => date(DATE_ATOM),
			'updatedAt' => date(DATE_ATOM),
		];
		$this->save();
	}

	/** @param array<string, array> $templatesById */
	public function filterTemplatesByClientVisibility(array $templatesById, ?string $clientId): array {
		return array_filter(
			$templatesById,
			fn($tpl, $templateId) => $this->isTemplateVisibleToClient($clientId, (string)$templateId),
			ARRAY_FILTER_USE_BOTH
		);
	}

	/** @param array<string, mixed> $meta */
	public function recordActivity(string $type, string $message, array $meta = []): void {
		$this->db['activities'][] = [
			'id' => $this->newId('act'),
			'type' => $type,
			'message' => $message,
			'meta' => $meta,
			'createdAt' => date(DATE_ATOM),
		];
		if (count($this->db['activities']) > 500) {
			$this->db['activities'] = array_slice($this->db['activities'], -500);
		}
		$this->save();
	}

	public function getRecentActivities(int $limit = 50): array {
		$items = $this->db['activities'] ?? [];
		usort($items, fn($a, $b) => strtotime((string)($b['createdAt'] ?? '')) <=> strtotime((string)($a['createdAt'] ?? '')));
		return array_slice($items, 0, max(1, $limit));
	}

	public function isMysqlPhaseOneConnected(): bool {
		return $this->pdo instanceof \PDO;
	}

	/**
	 * @return array<string, mixed>|null
	 */
	public function getGlobalFormTemplate(string $templateId): ?array {
		$templateId = trim($templateId);
		if ($templateId === '') {
			return null;
		}
		if ($this->pdo) {
			$formLocSel = $this->mysqlFormTemplatesHasFormLocation ? ', form_location' : '';
			$stmt = $this->pdo->prepare(
				'SELECT template_id, form_name, source_file_name, detected_firm_name' . $formLocSel . ', scope, created_at, updated_at
				 FROM form_templates WHERE template_id = :tid LIMIT 1'
			);
			$stmt->execute([':tid' => $templateId]);
			$row = $stmt->fetch();
			if (!$row) {
				return null;
			}
			return [
				'templateId' => (string)($row['template_id'] ?? ''),
				'formName' => (string)($row['form_name'] ?? ''),
				'sourceFileName' => (string)($row['source_file_name'] ?? ''),
				'detectedFirmName' => (string)($row['detected_firm_name'] ?? ''),
				'formLocation' => $this->mysqlFormTemplatesHasFormLocation ? (string)($row['form_location'] ?? '') : '',
				'scope' => (string)($row['scope'] ?? 'global'),
				'createdAt' => $this->toIsoDate((string)($row['created_at'] ?? '')),
				'updatedAt' => $this->toIsoDate((string)($row['updated_at'] ?? '')),
			];
		}
		foreach ($this->db['formTemplates'] ?? [] as $row) {
			if (($row['templateId'] ?? '') === $templateId) {
				return [
					'templateId' => (string)($row['templateId'] ?? ''),
					'formName' => (string)($row['formName'] ?? ''),
					'sourceFileName' => (string)($row['sourceFileName'] ?? ''),
					'detectedFirmName' => (string)($row['detectedFirmName'] ?? ''),
					'formLocation' => (string)($row['formLocation'] ?? ''),
					'scope' => (string)($row['scope'] ?? 'global'),
					'createdAt' => (string)($row['createdAt'] ?? ''),
					'updatedAt' => (string)($row['updatedAt'] ?? ''),
				];
			}
		}
		return null;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public function getGlobalFormTemplates(): array {
		if ($this->pdo) {
			$formLocSel = $this->mysqlFormTemplatesHasFormLocation ? ', form_location' : '';
			$stmt = $this->pdo->query(
				'SELECT template_id, form_name, source_file_name, detected_firm_name' . $formLocSel . ', scope, created_at, updated_at
				 FROM form_templates
				 ORDER BY updated_at DESC, created_at DESC, template_id ASC'
			);
			$rows = $stmt ? $stmt->fetchAll() : [];
			$out = [];
			foreach ($rows as $row) {
				$out[] = [
					'templateId' => (string)($row['template_id'] ?? ''),
					'formName' => (string)($row['form_name'] ?? ''),
					'sourceFileName' => (string)($row['source_file_name'] ?? ''),
					'detectedFirmName' => (string)($row['detected_firm_name'] ?? ''),
					'formLocation' => $this->mysqlFormTemplatesHasFormLocation ? (string)($row['form_location'] ?? '') : '',
					'scope' => (string)($row['scope'] ?? 'global'),
					'createdAt' => $this->toIsoDate((string)($row['created_at'] ?? '')),
					'updatedAt' => $this->toIsoDate((string)($row['updated_at'] ?? '')),
				];
			}
			return $out;
		}

		$rows = array_values($this->db['formTemplates'] ?? []);
		usort($rows, static function (array $a, array $b): int {
			$au = strtotime((string)($a['updatedAt'] ?? $a['createdAt'] ?? ''));
			$bu = strtotime((string)($b['updatedAt'] ?? $b['createdAt'] ?? ''));
			if ($au !== $bu) {
				return $bu <=> $au;
			}
			return strcmp((string)($a['templateId'] ?? ''), (string)($b['templateId'] ?? ''));
		});

		$out = [];
		foreach ($rows as $row) {
			$out[] = [
				'templateId' => (string)($row['templateId'] ?? ''),
				'formName' => (string)($row['formName'] ?? ''),
				'sourceFileName' => (string)($row['sourceFileName'] ?? ''),
				'detectedFirmName' => (string)($row['detectedFirmName'] ?? ''),
				'formLocation' => (string)($row['formLocation'] ?? ''),
				'scope' => (string)($row['scope'] ?? 'global'),
				'createdAt' => (string)($row['createdAt'] ?? ''),
				'updatedAt' => (string)($row['updatedAt'] ?? ''),
			];
		}
		return $out;
	}

	/** @return array<int, string> */
	private function normalizeFormSetTemplateIds(array $templateIds): array {
		$seen = [];
		$out = [];
		foreach ($templateIds as $value) {
			$tid = preg_replace('/[^a-zA-Z0-9_-]/', '', trim((string)$value));
			if ($tid === '' || isset($seen[$tid])) {
				continue;
			}
			$seen[$tid] = true;
			$out[] = $tid;
		}
		return $out;
	}

	/** @return array<int, array<string, mixed>> */
	private function loadGlobalFormSetsRaw(): array {
		$raw = $this->getAppSettingValue('global_form_sets', '');
		if ($raw === '') {
			return [];
		}
		$decoded = json_decode($raw, true);
		if (!is_array($decoded)) {
			return [];
		}
		$out = [];
		foreach ($decoded as $idx => $row) {
			if (!is_array($row)) {
				continue;
			}
			$idRaw = trim((string)($row['id'] ?? ''));
			$id = $idRaw !== '' ? preg_replace('/[^a-zA-Z0-9_-]/', '', $idRaw) : ('fset_' . ($idx + 1));
			$name = trim((string)($row['name'] ?? ''));
			if ($name === '') {
				$name = 'Untitled Form Set';
			}
			$out[] = [
				'id' => $id !== '' ? $id : ('fset_' . ($idx + 1)),
				'name' => mb_substr($name, 0, 255),
				'templateIds' => $this->normalizeFormSetTemplateIds(is_array($row['templateIds'] ?? null) ? $row['templateIds'] : []),
				'isBuiltIn' => !empty($row['isBuiltIn']),
				'createdAt' => (string)($row['createdAt'] ?? ''),
				'updatedAt' => (string)($row['updatedAt'] ?? ''),
			];
		}
		return $out;
	}

	/** @param array<int, array<string, mixed>> $rows */
	private function saveGlobalFormSetsRaw(array $rows): void {
		$this->setAppSettingValue('global_form_sets', (string)json_encode(array_values($rows), JSON_UNESCAPED_SLASHES));
	}

	/** @return array<int, array<string, mixed>> */
	public function getGlobalFormSetPresets(): array {
		$templateIds = [];
		foreach ($this->getGlobalFormTemplates() as $row) {
			$tid = preg_replace('/[^a-zA-Z0-9_-]/', '', trim((string)($row['templateId'] ?? '')));
			if ($tid !== '') {
				$templateIds[] = $tid;
			}
		}
		$templateIds = $this->normalizeFormSetTemplateIds($templateIds);
		return [
			[
				'id' => 'preset_all_global_forms',
				'name' => 'All Global Forms',
				'templateIds' => $templateIds,
				'isBuiltIn' => true,
			],
		];
	}

	/** @return array<int, array<string, mixed>> */
	public function getGlobalFormSets(): array {
		$rows = $this->loadGlobalFormSetsRaw();
		usort($rows, static function (array $a, array $b): int {
			$aBuiltIn = !empty($a['isBuiltIn']);
			$bBuiltIn = !empty($b['isBuiltIn']);
			if ($aBuiltIn !== $bBuiltIn) {
				return $aBuiltIn ? -1 : 1;
			}
			return strcasecmp((string)($a['name'] ?? ''), (string)($b['name'] ?? ''));
		});
		return $rows;
	}

	/** @return array<string, mixed>|null */
	public function getGlobalFormSet(string $id): ?array {
		$id = preg_replace('/[^a-zA-Z0-9_-]/', '', trim($id));
		if ($id === '') {
			return null;
		}
		foreach ($this->loadGlobalFormSetsRaw() as $row) {
			if ((string)($row['id'] ?? '') === $id) {
				return $row;
			}
		}
		return null;
	}

	/** @param array<int, string> $templateIds */
	public function upsertGlobalFormSet(string $name, array $templateIds, string $id = '', bool $isBuiltIn = false): array {
		$name = trim($name);
		if ($name === '') {
			$name = 'Untitled Form Set';
		}
		$name = mb_substr($name, 0, 255);
		$id = preg_replace('/[^a-zA-Z0-9_-]/', '', trim($id));
		$now = date(DATE_ATOM);
		$normalizedTemplateIds = $this->normalizeFormSetTemplateIds($templateIds);
		$rows = $this->loadGlobalFormSetsRaw();
		if ($id !== '') {
			foreach ($rows as &$row) {
				if ((string)($row['id'] ?? '') !== $id) {
					continue;
				}
				$row['name'] = $name;
				$row['templateIds'] = $normalizedTemplateIds;
				$row['isBuiltIn'] = !empty($row['isBuiltIn']) || $isBuiltIn;
				$row['updatedAt'] = $now;
				$this->saveGlobalFormSetsRaw($rows);
				return $row;
			}
			unset($row);
		}
		$newRow = [
			'id' => $id !== '' ? $id : $this->newId('fset'),
			'name' => $name,
			'templateIds' => $normalizedTemplateIds,
			'isBuiltIn' => $isBuiltIn,
			'createdAt' => $now,
			'updatedAt' => $now,
		];
		$rows[] = $newRow;
		$this->saveGlobalFormSetsRaw($rows);
		return $newRow;
	}

	public function deleteGlobalFormSet(string $id): bool {
		$id = preg_replace('/[^a-zA-Z0-9_-]/', '', trim($id));
		if ($id === '') {
			return false;
		}
		$rows = $this->loadGlobalFormSetsRaw();
		$next = [];
		$deleted = false;
		foreach ($rows as $row) {
			$rowId = (string)($row['id'] ?? '');
			if ($rowId !== $id) {
				$next[] = $row;
				continue;
			}
			// Guard built-in presets from deletion.
			if (!empty($row['isBuiltIn'])) {
				$next[] = $row;
				continue;
			}
			$deleted = true;
		}
		if (!$deleted) {
			return false;
		}
		$this->saveGlobalFormSetsRaw($next);
		return true;
	}

	/**
	 * Phase 1 form manager: keep a global form registry keyed by template ID.
	 */
	public function upsertGlobalFormTemplate(string $templateId, string $formName, string $sourceFileName = '', string $detectedFirmName = '', string $formLocation = ''): void {
		$templateId = trim($templateId);
		if ($templateId === '') {
			return;
		}
		$formName = trim($formName) !== '' ? trim($formName) : $templateId;
		$detectedFirmName = trim($detectedFirmName);
		if (strlen($detectedFirmName) > 512) {
			$detectedFirmName = substr($detectedFirmName, 0, 512);
		}
		$formLocation = trim($formLocation);
		if (strlen($formLocation) > 1024) {
			$formLocation = substr($formLocation, 0, 1024);
		}
		$now = date(DATE_ATOM);

		if ($this->pdo) {
			$sqlNow = $this->toSqlDate($now);
			if ($this->mysqlFormTemplatesHasFormLocation) {
				$stmt = $this->pdo->prepare(
					'INSERT INTO form_templates (id, template_id, form_name, source_file_name, detected_firm_name, form_location, scope, created_at, updated_at)
					 VALUES (:id, :template_id, :form_name, :source_file_name, :detected_firm_name, :form_location, \'global\', :created_at, :updated_at)
					 ON DUPLICATE KEY UPDATE
					 form_name = VALUES(form_name),
					 source_file_name = VALUES(source_file_name),
					 detected_firm_name = IF(VALUES(detected_firm_name) <> \'\', VALUES(detected_firm_name), detected_firm_name),
					 form_location = VALUES(form_location),
					 scope = \'global\',
					 updated_at = VALUES(updated_at)'
				);
				$stmt->execute([
					':id' => $this->newId('ft'),
					':template_id' => $templateId,
					':form_name' => $formName,
					':source_file_name' => trim($sourceFileName),
					':detected_firm_name' => $detectedFirmName,
					':form_location' => $formLocation,
					':created_at' => $sqlNow,
					':updated_at' => $sqlNow,
				]);
			} else {
				$stmt = $this->pdo->prepare(
					'INSERT INTO form_templates (id, template_id, form_name, source_file_name, detected_firm_name, scope, created_at, updated_at)
					 VALUES (:id, :template_id, :form_name, :source_file_name, :detected_firm_name, \'global\', :created_at, :updated_at)
					 ON DUPLICATE KEY UPDATE
					 form_name = VALUES(form_name),
					 source_file_name = VALUES(source_file_name),
					 detected_firm_name = IF(VALUES(detected_firm_name) <> \'\', VALUES(detected_firm_name), detected_firm_name),
					 scope = \'global\',
					 updated_at = VALUES(updated_at)'
				);
				$stmt->execute([
					':id' => $this->newId('ft'),
					':template_id' => $templateId,
					':form_name' => $formName,
					':source_file_name' => trim($sourceFileName),
					':detected_firm_name' => $detectedFirmName,
					':created_at' => $sqlNow,
					':updated_at' => $sqlNow,
				]);
			}
			return;
		}

		foreach ($this->db['formTemplates'] as &$row) {
			if (($row['templateId'] ?? '') === $templateId) {
				$row['formName'] = $formName;
				$row['sourceFileName'] = trim($sourceFileName);
				$row['scope'] = 'global';
				$row['updatedAt'] = $now;
				$row['formLocation'] = $formLocation;
				if ($detectedFirmName !== '') {
					$row['detectedFirmName'] = $detectedFirmName;
				}
				$this->save();
				return;
			}
		}
		$this->db['formTemplates'][] = [
			'id' => $this->newId('ft'),
			'templateId' => $templateId,
			'formName' => $formName,
			'sourceFileName' => trim($sourceFileName),
			'detectedFirmName' => $detectedFirmName,
			'formLocation' => $formLocation,
			'scope' => 'global',
			'createdAt' => $now,
			'updatedAt' => $now,
		];
		$this->save();
	}

	/**
	 * Delete a global form template and related template-scoped records.
	 */
	public function deleteGlobalFormTemplate(string $templateId): bool {
		$templateId = trim($templateId);
		if ($templateId === '') {
			return false;
		}

		if ($this->pdo) {
			$deletedTemplate = false;
			$this->pdo->beginTransaction();
			try {
				$stmt = $this->pdo->prepare('DELETE FROM form_templates WHERE template_id = :template_id');
				$stmt->execute([':template_id' => $templateId]);
				$deletedTemplate = ((int)$stmt->rowCount() > 0);

				$cleanupStatements = [
					'DELETE FROM firm_field_alignments WHERE template_id = :template_id',
					'DELETE FROM mvp_client_templates WHERE template_id = :template_id',
					'DELETE FROM mvp_client_field_mappings WHERE template_id = :template_id',
					// Legacy table names kept for backward compatibility on older installs.
					'DELETE FROM client_templates WHERE template_id = :template_id',
					'DELETE FROM client_field_mappings WHERE template_id = :template_id',
				];
				foreach ($cleanupStatements as $sql) {
					try {
						$st = $this->pdo->prepare($sql);
						$st->execute([':template_id' => $templateId]);
					} catch (\Throwable $e) {
						// Optional cleanup tables can be absent in some environments.
						$this->logger->warning('deleteGlobalFormTemplate cleanup warning: ' . $e->getMessage());
					}
				}
				$this->pdo->commit();
				return $deletedTemplate;
			} catch (\Throwable $e) {
				if ($this->pdo->inTransaction()) {
					$this->pdo->rollBack();
				}
				throw $e;
			}
		}

		$deletedTemplate = false;
		$this->db['formTemplates'] = array_values(array_filter(
			$this->db['formTemplates'] ?? [],
			static function (array $row) use ($templateId, &$deletedTemplate): bool {
				$keep = ((string)($row['templateId'] ?? '') !== $templateId);
				if (!$keep) {
					$deletedTemplate = true;
				}
				return $keep;
			}
		));
		$this->db['firmFieldAlignments'] = array_values(array_filter(
			$this->db['firmFieldAlignments'] ?? [],
			static fn(array $row): bool => ((string)($row['templateId'] ?? '') !== $templateId)
		));
		$this->db['clientTemplates'] = array_values(array_filter(
			$this->db['clientTemplates'] ?? [],
			static fn(array $row): bool => ((string)($row['templateId'] ?? '') !== $templateId)
		));
		$this->db['clientFieldMappings'] = array_values(array_filter(
			$this->db['clientFieldMappings'] ?? [],
			static fn(array $row): bool => ((string)($row['templateId'] ?? '') !== $templateId)
		));
		$this->save();
		return $deletedTemplate;
	}

	/**
	 * Normalize global form registry data and remove orphaned template links.
	 *
	 * @return array<string, int>
	 */
	public function cleanupGlobalFormDatabase(): array {
		$summary = [
			'removedInvalidTemplates' => 0,
			'removedDuplicateTemplates' => 0,
			'removedOrphanClientTemplates' => 0,
			'removedOrphanClientFieldMappings' => 0,
			'removedOrphanFirmAlignments' => 0,
			'updatedFormSets' => 0,
			'removedFormSetTemplateRefs' => 0,
			'removedDuplicateFormSets' => 0,
		];

		if ($this->pdo) {
			try {
				$summary['removedInvalidTemplates'] = (int)$this->pdo->exec(
					'DELETE FROM form_templates WHERE template_id IS NULL OR TRIM(template_id) = \'\''
				);
			} catch (\Throwable $e) {
				$this->logger->warning('cleanupGlobalFormDatabase template cleanup warning: ' . $e->getMessage());
			}

			$validTemplateIds = [];
			try {
				$st = $this->pdo->query('SELECT template_id FROM form_templates');
				foreach (($st ? $st->fetchAll() : []) as $row) {
					$tid = preg_replace('/[^a-zA-Z0-9_-]/', '', trim((string)($row['template_id'] ?? '')));
					if ($tid !== '') {
						$validTemplateIds[] = $tid;
					}
				}
			} catch (\Throwable $e) {
				$this->logger->warning('cleanupGlobalFormDatabase valid template scan warning: ' . $e->getMessage());
			}
			$validTemplateIds = array_values(array_unique($validTemplateIds));

			$summary['removedOrphanClientTemplates'] =
				$this->cleanupMysqlTemplateLinkedTable('mvp_client_templates', $validTemplateIds) +
				$this->cleanupMysqlTemplateLinkedTable('client_templates', $validTemplateIds);
			$summary['removedOrphanClientFieldMappings'] =
				$this->cleanupMysqlTemplateLinkedTable('mvp_client_field_mappings', $validTemplateIds) +
				$this->cleanupMysqlTemplateLinkedTable('client_field_mappings', $validTemplateIds);
			$summary['removedOrphanFirmAlignments'] =
				$this->cleanupMysqlTemplateLinkedTable('firm_field_alignments', $validTemplateIds);

			$formSetSummary = $this->cleanupGlobalFormSetsByTemplateIds($validTemplateIds);
			$summary['updatedFormSets'] = (int)$formSetSummary['updatedFormSets'];
			$summary['removedFormSetTemplateRefs'] = (int)$formSetSummary['removedFormSetTemplateRefs'];
			$summary['removedDuplicateFormSets'] = (int)$formSetSummary['removedDuplicateFormSets'];

			return $summary;
		}

		$formsRaw = array_values($this->db['formTemplates'] ?? []);
		$bestByTemplate = [];
		foreach ($formsRaw as $row) {
			if (!is_array($row)) {
				$summary['removedInvalidTemplates']++;
				continue;
			}
			$tid = preg_replace('/[^a-zA-Z0-9_-]/', '', trim((string)($row['templateId'] ?? '')));
			if ($tid === '') {
				$summary['removedInvalidTemplates']++;
				continue;
			}
			$row['templateId'] = $tid;
			if (!isset($bestByTemplate[$tid])) {
				$bestByTemplate[$tid] = $row;
				continue;
			}
			$summary['removedDuplicateTemplates']++;
			$existing = (array)$bestByTemplate[$tid];
			$existingTs = strtotime((string)($existing['updatedAt'] ?? $existing['createdAt'] ?? '')) ?: 0;
			$rowTs = strtotime((string)($row['updatedAt'] ?? $row['createdAt'] ?? '')) ?: 0;
			if ($rowTs >= $existingTs) {
				$bestByTemplate[$tid] = $row;
			}
		}
		$this->db['formTemplates'] = array_values($bestByTemplate);
		$validSet = array_flip(array_keys($bestByTemplate));

		$beforeClientTemplates = count($this->db['clientTemplates'] ?? []);
		$this->db['clientTemplates'] = array_values(array_filter(
			$this->db['clientTemplates'] ?? [],
			static fn(array $row): bool => isset($validSet[(string)($row['templateId'] ?? '')])
		));
		$summary['removedOrphanClientTemplates'] = $beforeClientTemplates - count($this->db['clientTemplates']);

		$beforeMappings = count($this->db['clientFieldMappings'] ?? []);
		$this->db['clientFieldMappings'] = array_values(array_filter(
			$this->db['clientFieldMappings'] ?? [],
			static fn(array $row): bool => isset($validSet[(string)($row['templateId'] ?? '')])
		));
		$summary['removedOrphanClientFieldMappings'] = $beforeMappings - count($this->db['clientFieldMappings']);

		$beforeAlignments = count($this->db['firmFieldAlignments'] ?? []);
		$this->db['firmFieldAlignments'] = array_values(array_filter(
			$this->db['firmFieldAlignments'] ?? [],
			static fn(array $row): bool => isset($validSet[(string)($row['templateId'] ?? '')])
		));
		$summary['removedOrphanFirmAlignments'] = $beforeAlignments - count($this->db['firmFieldAlignments']);

		$formSetSummary = $this->cleanupGlobalFormSetsByTemplateIds(array_keys($bestByTemplate));
		$summary['updatedFormSets'] = (int)$formSetSummary['updatedFormSets'];
		$summary['removedFormSetTemplateRefs'] = (int)$formSetSummary['removedFormSetTemplateRefs'];
		$summary['removedDuplicateFormSets'] = (int)$formSetSummary['removedDuplicateFormSets'];

		$this->save();
		return $summary;
	}

	/** @param array<int, string> $validTemplateIds */
	private function cleanupMysqlTemplateLinkedTable(string $table, array $validTemplateIds): int {
		if (!$this->pdo) {
			return 0;
		}
		$params = [];
		$where = 'template_id IS NULL OR TRIM(template_id) = \'\'';
		if (!empty($validTemplateIds)) {
			$placeholders = [];
			foreach (array_values($validTemplateIds) as $idx => $tid) {
				$key = ':tid_' . $idx;
				$placeholders[] = $key;
				$params[$key] = $tid;
			}
			$where .= ' OR template_id NOT IN (' . implode(', ', $placeholders) . ')';
		}

		try {
			$countSql = 'SELECT COUNT(*) FROM `' . $table . '` WHERE ' . $where;
			$countStmt = $this->pdo->prepare($countSql);
			$countStmt->execute($params);
			$count = (int)$countStmt->fetchColumn();
			if ($count <= 0) {
				return 0;
			}
			$deleteSql = 'DELETE FROM `' . $table . '` WHERE ' . $where;
			$deleteStmt = $this->pdo->prepare($deleteSql);
			$deleteStmt->execute($params);
			return (int)$deleteStmt->rowCount();
		} catch (\Throwable $e) {
			// Some environments still use older schema subsets.
			$this->logger->warning('cleanupMysqlTemplateLinkedTable warning: ' . $e->getMessage(), ['table' => $table]);
			return 0;
		}
	}

	/**
	 * @param array<int, string> $validTemplateIds
	 * @return array{updatedFormSets:int,removedFormSetTemplateRefs:int,removedDuplicateFormSets:int}
	 */
	private function cleanupGlobalFormSetsByTemplateIds(array $validTemplateIds): array {
		$valid = array_flip($this->normalizeFormSetTemplateIds($validTemplateIds));
		$rows = $this->loadGlobalFormSetsRaw();
		$next = [];
		$seenIds = [];
		$updatedRows = 0;
		$removedRefs = 0;
		$removedDupes = 0;

		foreach ($rows as $idx => $row) {
			if (!is_array($row)) {
				continue;
			}
			$idRaw = trim((string)($row['id'] ?? ''));
			$id = $idRaw !== '' ? preg_replace('/[^a-zA-Z0-9_-]/', '', $idRaw) : ('fset_' . ($idx + 1));
			if ($id === '') {
				$id = 'fset_' . ($idx + 1);
			}
			if (isset($seenIds[$id])) {
				$removedDupes++;
				continue;
			}
			$seenIds[$id] = true;

			$existingIds = $this->normalizeFormSetTemplateIds(is_array($row['templateIds'] ?? null) ? $row['templateIds'] : []);
			$filteredIds = [];
			foreach ($existingIds as $tid) {
				if (isset($valid[$tid])) {
					$filteredIds[] = $tid;
					continue;
				}
				$removedRefs++;
			}
			if ($filteredIds !== $existingIds) {
				$updatedRows++;
			}
			$row['id'] = $id;
			$row['templateIds'] = $filteredIds;
			$next[] = $row;
		}

		if ($updatedRows > 0 || $removedDupes > 0 || count($next) !== count($rows)) {
			$this->saveGlobalFormSetsRaw($next);
		}

		return [
			'updatedFormSets' => $updatedRows,
			'removedFormSetTemplateRefs' => $removedRefs,
			'removedDuplicateFormSets' => $removedDupes,
		];
	}

	/**
	 * Phase 1: save alignment positions by firm + template for future proofing.
	 *
	 * @param array<string, mixed> $positions
	 */
	public function saveFirmFieldAlignment(string $firmId, string $templateId, array $positions): void {
		$firmId = trim($firmId) !== '' ? trim($firmId) : 'default_firm';
		$templateId = trim($templateId);
		if ($templateId === '') {
			return;
		}
		$now = date(DATE_ATOM);

		if ($this->pdo) {
			$sqlNow = $this->toSqlDate($now);
			$stmt = $this->pdo->prepare(
				'INSERT INTO firm_field_alignments (id, firm_id, template_id, positions_json, created_at, updated_at)
				 VALUES (:id, :firm_id, :template_id, :positions_json, :created_at, :updated_at)
				 ON DUPLICATE KEY UPDATE
				 positions_json = VALUES(positions_json),
				 updated_at = VALUES(updated_at)'
			);
			$stmt->execute([
				':id' => $this->newId('ffa'),
				':firm_id' => $firmId,
				':template_id' => $templateId,
				':positions_json' => (string)json_encode($positions, JSON_UNESCAPED_SLASHES),
				':created_at' => $sqlNow,
				':updated_at' => $sqlNow,
			]);
			return;
		}

		foreach ($this->db['firmFieldAlignments'] as &$row) {
			if (($row['firmId'] ?? '') === $firmId && ($row['templateId'] ?? '') === $templateId) {
				$row['positions'] = $positions;
				$row['updatedAt'] = $now;
				$this->save();
				return;
			}
		}
		$this->db['firmFieldAlignments'][] = [
			'id' => $this->newId('ffa'),
			'firmId' => $firmId,
			'templateId' => $templateId,
			'positions' => $positions,
			'createdAt' => $now,
			'updatedAt' => $now,
		];
		$this->save();
	}

	/**
	 * @return array<int, array<string, string>>
	 */
	public function getFormCustomFields(): array {
		if ($this->pdo) {
			$stmt = $this->pdo->query(
				'SELECT id, link_id, display_name, field_type, matching_tag, value_text, location, is_system, created_at, updated_at
				 FROM form_custom_fields
				 ORDER BY location, display_name'
			);
			$rows = $stmt ? $stmt->fetchAll() : [];
			if (!empty($rows)) {
				$mappedRows = array_map(function (array $row): array {
					$fieldType = $this->normalizeFieldType((string)($row['field_type'] ?? 'text'));
					$matchingTag = trim((string)($row['matching_tag'] ?? ''));
					if ($matchingTag === '') {
						$matchingTag = (string)($row['link_id'] ?? '');
					}
					return [
						'id' => (string)($row['id'] ?? ''),
						'linkId' => (string)($row['link_id'] ?? ''),
						'displayName' => (string)($row['display_name'] ?? ''),
						'fieldType' => $fieldType,
						'matchingTag' => $matchingTag,
						'value' => (string)($row['value_text'] ?? ''),
						'location' => (string)($row['location'] ?? 'firm'),
						'isSystem' => (bool)($row['is_system'] ?? false),
						'createdAt' => $this->toIsoDate((string)($row['created_at'] ?? '')),
						'updatedAt' => $this->toIsoDate((string)($row['updated_at'] ?? '')),
					];
				}, $rows);
				return $this->ensureProtectedCatalogFields($mappedRows);
			}
		}

		$existing = $this->db['formCustomFields'] ?? [];
		if (!empty($existing)) {
			return $this->ensureProtectedCatalogFields($existing);
		}

		$seed = [
			[
				'id' => $this->newId('fcf'),
				'linkId' => 'firm_name',
				'displayName' => 'Firm Name',
				'fieldType' => 'text',
				'matchingTag' => 'firm_name',
				'value' => '',
				'location' => 'firm',
				'isSystem' => true,
				'createdAt' => date(DATE_ATOM),
			],
			[
				'id' => $this->newId('fcf'),
				'linkId' => 'client_full_name',
				'displayName' => 'Client Full Name',
				'fieldType' => 'text',
				'matchingTag' => 'client_full_name',
				'value' => '',
				'location' => 'client',
				'isSystem' => true,
				'createdAt' => date(DATE_ATOM),
			],
			[
				'id' => $this->newId('fcf'),
				'linkId' => 'case_number',
				'displayName' => 'Case Number',
				'fieldType' => 'text',
				'matchingTag' => 'case_number',
				'value' => '',
				'location' => 'case',
				'isSystem' => true,
				'createdAt' => date(DATE_ATOM),
			],
			[
				'id' => $this->newId('fcf'),
				'linkId' => 'court_county',
				'displayName' => 'County',
				'fieldType' => 'text',
				'matchingTag' => 'crtcounty',
				'value' => '',
				'location' => 'court',
				'isSystem' => true,
				'createdAt' => date(DATE_ATOM),
			],
			[
				'id' => $this->newId('fcf'),
				'linkId' => 'court_street',
				'displayName' => 'Street Address',
				'fieldType' => 'text',
				'matchingTag' => 'street',
				'value' => '',
				'location' => 'court',
				'isSystem' => true,
				'createdAt' => date(DATE_ATOM),
			],
			[
				'id' => $this->newId('fcf'),
				'linkId' => 'court_mailing_address',
				'displayName' => 'Mailing Address',
				'fieldType' => 'text',
				'matchingTag' => 'mailingadd',
				'value' => '',
				'location' => 'court',
				'isSystem' => true,
				'createdAt' => date(DATE_ATOM),
			],
			[
				'id' => $this->newId('fcf'),
				'linkId' => 'court_city_zip',
				'displayName' => 'City / State / ZIP',
				'fieldType' => 'text',
				'matchingTag' => 'cityzip',
				'value' => '',
				'location' => 'court',
				'isSystem' => true,
				'createdAt' => date(DATE_ATOM),
			],
			[
				'id' => $this->newId('fcf'),
				'linkId' => 'court_branch',
				'displayName' => 'Branch / Department',
				'fieldType' => 'text',
				'matchingTag' => 'branch',
				'value' => '',
				'location' => 'court',
				'isSystem' => true,
				'createdAt' => date(DATE_ATOM),
			],
			[
				'id' => $this->newId('fcf'),
				'linkId' => 'court_name',
				'displayName' => 'Court Name',
				'fieldType' => 'text',
				'matchingTag' => 'courtname',
				'value' => '',
				'location' => 'court',
				'isSystem' => true,
				'createdAt' => date(DATE_ATOM),
			],
			[
				'id' => $this->newId('fcf'),
				'linkId' => 'court_city',
				'displayName' => 'City',
				'fieldType' => 'text',
				'matchingTag' => 'courtcity',
				'value' => '',
				'location' => 'court',
				'isSystem' => true,
				'createdAt' => date(DATE_ATOM),
			],
			[
				'id' => $this->newId('fcf'),
				'linkId' => 'court_state',
				'displayName' => 'State',
				'fieldType' => 'text',
				'matchingTag' => 'courtstate',
				'value' => '',
				'location' => 'court',
				'isSystem' => true,
				'createdAt' => date(DATE_ATOM),
			],
			[
				'id' => $this->newId('fcf'),
				'linkId' => 'court_zip',
				'displayName' => 'ZIP',
				'fieldType' => 'text',
				'matchingTag' => 'courtzip',
				'value' => '',
				'location' => 'court',
				'isSystem' => true,
				'createdAt' => date(DATE_ATOM),
			],
			[
				'id' => $this->newId('fcf'),
				'linkId' => 'court_phone',
				'displayName' => 'Phone',
				'fieldType' => 'phone',
				'matchingTag' => 'courtphone',
				'value' => '',
				'location' => 'court',
				'isSystem' => true,
				'createdAt' => date(DATE_ATOM),
			],
			[
				'id' => $this->newId('fcf'),
				'linkId' => 'court_department',
				'displayName' => 'Department',
				'fieldType' => 'text',
				'matchingTag' => 'courtdept',
				'value' => '',
				'location' => 'court',
				'isSystem' => true,
				'createdAt' => date(DATE_ATOM),
			],
			[
				'id' => $this->newId('fcf'),
				'linkId' => 'court_room',
				'displayName' => 'Room',
				'fieldType' => 'text',
				'matchingTag' => 'courtroom',
				'value' => '',
				'location' => 'court',
				'isSystem' => true,
				'createdAt' => date(DATE_ATOM),
			],
			[
				'id' => $this->newId('fcf'),
				'linkId' => 'court_floor',
				'displayName' => 'Floor',
				'fieldType' => 'text',
				'matchingTag' => 'courtfloor',
				'value' => '',
				'location' => 'court',
				'isSystem' => true,
				'createdAt' => date(DATE_ATOM),
			],
		];
		if ($this->pdo) {
			$sql = 'INSERT INTO form_custom_fields (id, link_id, display_name, field_type, matching_tag, value_text, location, is_system, created_at, updated_at)
			        VALUES (:id, :link_id, :display_name, :field_type, :matching_tag, :value_text, :location, :is_system, :created_at, :updated_at)
			        ON DUPLICATE KEY UPDATE
			        display_name = VALUES(display_name),
			        field_type = VALUES(field_type),
			        matching_tag = VALUES(matching_tag),
			        value_text = VALUES(value_text),
			        location = VALUES(location),
			        is_system = VALUES(is_system),
			        updated_at = VALUES(updated_at)';
			$stmt = $this->pdo->prepare($sql);
			foreach ($seed as $row) {
				$stmt->execute([
					':id' => (string)$row['id'],
					':link_id' => (string)$row['linkId'],
					':display_name' => (string)$row['displayName'],
					':field_type' => (string)$row['fieldType'],
					':matching_tag' => (string)$row['matchingTag'],
					':value_text' => (string)$row['value'],
					':location' => (string)$row['location'],
					':is_system' => !empty($row['isSystem']) ? 1 : 0,
					':created_at' => $this->toSqlDate((string)$row['createdAt']),
					':updated_at' => $this->toSqlDate((string)($row['createdAt'] ?? date(DATE_ATOM))),
				]);
			}
			return $this->ensureProtectedCatalogFields($seed);
		}

		$this->db['formCustomFields'] = $seed;
		$this->save();
		return $this->ensureProtectedCatalogFields($seed);
	}

	/**
	 * Add or update a catalog custom field (Firm / Client / Case) for Form Management.
	 */
	public function upsertFormCustomFieldRow(string $linkId, string $displayName, string $location, string $value = '', string $fieldType = 'text', string $matchingTag = '', bool $isSystem = false): void {
		$linkId = preg_replace('/[^a-z0-9_]/', '_', strtolower(trim($linkId)));
		if ($linkId === '') {
			return;
		}
		$displayName = trim($displayName) !== '' ? trim($displayName) : $linkId;
		$fieldType = $this->normalizeFieldType($fieldType);
		// When matching_tag is omitted, keep the existing tag for this link_id (Form Management saves
		// only display/value/location). Defaulting to link_id would break protected rows (e.g. link
		// client_email must keep matching_tag "email").
		$rawMatching = trim($matchingTag);
		if ($rawMatching === '') {
			$existingForLink = null;
			foreach ($this->getFormCustomFields() as $r) {
				if (strtolower(trim((string)($r['linkId'] ?? ''))) === $linkId) {
					$existingForLink = $r;
					break;
				}
			}
			$prevTag = $existingForLink !== null ? trim((string)($existingForLink['matchingTag'] ?? '')) : '';
			$matchingTag = $prevTag !== '' ? mb_substr($prevTag, 0, 255) : $linkId;
		} else {
			$matchingTag = mb_substr($rawMatching, 0, 255);
		}
		$location = strtolower(trim($location));
		if (!in_array($location, $this->allowedFieldManagerLocations(), true)) {
			$location = 'firm';
		}
		$now = date(DATE_ATOM);
		if ($this->pdo) {
			$sqlNow = $this->toSqlDate($now);
			$stmt = $this->pdo->prepare(
				'INSERT INTO form_custom_fields (id, link_id, display_name, field_type, matching_tag, value_text, location, is_system, created_at, updated_at)
				 VALUES (:id, :link_id, :display_name, :field_type, :matching_tag, :value_text, :location, :is_system, :created_at, :updated_at)
				 ON DUPLICATE KEY UPDATE
				 display_name = VALUES(display_name),
				 field_type = VALUES(field_type),
				 matching_tag = VALUES(matching_tag),
				 value_text = VALUES(value_text),
				 location = VALUES(location),
				 is_system = IF(form_custom_fields.is_system = 1, 1, VALUES(is_system)),
				 updated_at = VALUES(updated_at)'
			);
			$stmt->execute([
				':id' => $this->newId('fcf'),
				':link_id' => $linkId,
				':display_name' => $displayName,
				':field_type' => $fieldType,
				':matching_tag' => $matchingTag,
				':value_text' => $value,
				':location' => $location,
				':is_system' => $isSystem ? 1 : 0,
				':created_at' => $sqlNow,
				':updated_at' => $sqlNow,
			]);
			return;
		}
		$found = false;
		foreach ($this->db['formCustomFields'] as &$row) {
			if (($row['linkId'] ?? '') === $linkId) {
				$row['displayName'] = $displayName;
				$row['fieldType'] = $fieldType;
				$row['matchingTag'] = $matchingTag;
				$row['value'] = $value;
				$row['location'] = $location;
				$row['isSystem'] = (bool)($row['isSystem'] ?? false) || $isSystem;
				$row['updatedAt'] = $now;
				$found = true;
				break;
			}
		}
		unset($row);
		if (!$found) {
			$this->db['formCustomFields'][] = [
				'id' => $this->newId('fcf'),
				'linkId' => $linkId,
				'displayName' => $displayName,
				'fieldType' => $fieldType,
				'matchingTag' => $matchingTag,
				'value' => $value,
				'location' => $location,
				'isSystem' => $isSystem,
				'createdAt' => $now,
				'updatedAt' => $now,
			];
		}
		$this->save();
	}

	/**
	 * @return array<int, array<string, string>>
	 */
	public function getFieldManagerCustomFields(?string $location = null): array {
		$all = $this->getFormCustomFields();
		if ($location === null || trim($location) === '') {
			return $all;
		}
		$location = strtolower(trim($location));
		if (!in_array($location, $this->allowedFieldManagerLocations(), true)) {
			return $all;
		}
		return array_values(array_filter($all, static fn(array $row): bool => (($row['location'] ?? '') === $location)));
	}

	public function upsertFieldManagerCustomField(string $displayName, string $fieldType, string $matchingTag, string $location, string $id = '', ?string $catalogSampleValue = null): array {
		$location = strtolower(trim($location));
		if (!in_array($location, $this->allowedFieldManagerLocations(), true)) {
			$location = 'firm';
		}
		$displayName = trim($displayName);
		$fieldType = $this->normalizeFieldType($fieldType);
		$matchingTag = mb_substr(trim($matchingTag), 0, 255);
		if ($displayName === '') {
			$displayName = 'Untitled Field';
		}
		if ($matchingTag === '') {
			$matchingTag = preg_replace('/[^a-z0-9_]/', '_', strtolower($displayName));
		}
		$id = preg_replace('/[^a-zA-Z0-9_-]/', '', trim($id));
		$existingById = null;
		if ($id !== '') {
			foreach ($this->getFormCustomFields() as $existingRow) {
				if (($existingRow['id'] ?? '') === $id) {
					$existingById = $existingRow;
					break;
				}
			}
		}

		// For existing rows, keep stable link_id and only update editable metadata.
		if ($id !== '' && $existingById !== null) {
			$linkId = strtolower(trim((string)($existingById['linkId'] ?? '')));
			if ($this->pdo) {
				$sqlNow = $this->toSqlDate(date(DATE_ATOM));
				$sql = 'UPDATE form_custom_fields
					 SET display_name = :display_name,
					     field_type = :field_type,
					     matching_tag = :matching_tag,
					     location = :location,
					     updated_at = :updated_at';
				$params = [
					':display_name' => $displayName,
					':field_type' => $fieldType,
					':matching_tag' => $matchingTag,
					':location' => $location,
					':updated_at' => $sqlNow,
					':id' => $id,
				];
				if ($catalogSampleValue !== null) {
					$sql .= ', value_text = :value_text';
					$params[':value_text'] = mb_substr((string)$catalogSampleValue, 0, 8000);
				}
				$sql .= ' WHERE id = :id';
				$stmt = $this->pdo->prepare($sql);
				$stmt->execute($params);
				$rowCount = $stmt->rowCount();
				$this->logger->info('upsertFieldManagerCustomField UPDATE (mysql)', [
					'id' => $id,
					'linkId' => $linkId,
					'displayName' => $displayName,
					'location' => $location,
					'rowCount' => $rowCount,
				]);
				// rowCount can legitimately be 0 when no column actually changes (PDO/MySQL default).
				// Verify the row exists; if it doesn't, the WHERE clause didn't match and we must surface
				// the failure rather than silently fall through to the new-row branch.
				$verify = $this->pdo->prepare('SELECT id FROM form_custom_fields WHERE id = :id');
				$verify->execute([':id' => $id]);
				if ($verify->fetchColumn() === false) {
					throw new \RuntimeException('Custom field UPDATE matched no row for id ' . $id);
				}
			} else {
				if (!isset($this->db['formCustomFields']) || !is_array($this->db['formCustomFields'])) {
					$this->db['formCustomFields'] = [];
				}
				$jsonHit = false;
				// IMPORTANT: do NOT iterate "$this->db['formCustomFields'] ?? [] as &$row" — the null-coalesce
				// expression yields a temporary value, so reference modifications never reach the property.
				foreach ($this->db['formCustomFields'] as &$row) {
					if (($row['id'] ?? '') !== $id) {
						continue;
					}
					$row['displayName'] = $displayName;
					$row['fieldType'] = $fieldType;
					$row['matchingTag'] = $matchingTag;
					$row['location'] = $location;
					$row['linkId'] = $linkId;
					$row['isSystem'] = (bool)($row['isSystem'] ?? false);
					if ($catalogSampleValue !== null) {
						$row['value'] = mb_substr((string)$catalogSampleValue, 0, 8000);
					}
					$row['updatedAt'] = date(DATE_ATOM);
					$jsonHit = true;
					break;
				}
				unset($row);
				if (!$jsonHit) {
					throw new \RuntimeException('Custom field UPDATE matched no row in JSON store for id ' . $id);
				}
				$this->save();
				$this->logger->info('upsertFieldManagerCustomField UPDATE (json)', [
					'id' => $id,
					'linkId' => $linkId,
					'displayName' => $displayName,
					'location' => $location,
				]);
			}
			foreach ($this->getFormCustomFields() as $row) {
				if (($row['id'] ?? '') === $id) {
					return $row;
				}
			}
			// Update succeeded (verified above) but post-read couldn't locate the row. Surface this rather
			// than fall through to the new-row branch (which would create a duplicate link_id).
			throw new \RuntimeException('Custom field UPDATE succeeded but row could not be re-read for id ' . $id);
		}

		// New rows are created by unique link_id.
		$linkSource = preg_replace('/[^a-z0-9_]/', '_', strtolower($matchingTag));
		$linkBase = $location . '_' . trim((string)$linkSource, '_');
		if ($linkBase === $location . '_') {
			$linkBase = $location . '_' . preg_replace('/[^a-z0-9_]/', '_', strtolower($displayName));
		}
		$linkId = $this->uniqueFieldManagerLinkId($linkBase, '');
		$initialCatalog = (string)($catalogSampleValue ?? '');
		$this->upsertFormCustomFieldRow($linkId, $displayName, $location, $initialCatalog, $fieldType, $matchingTag);
		foreach ($this->getFormCustomFields() as $row) {
			if (($row['linkId'] ?? '') === $linkId && ($row['location'] ?? '') === $location) {
				return $row;
			}
		}
		return [
			'id' => $this->newId('fcf'),
			'linkId' => $linkId,
			'displayName' => $displayName,
			'fieldType' => $fieldType,
			'matchingTag' => $matchingTag,
			'location' => $location,
			'isSystem' => false,
		];
	}

	/**
	 * Diagnostic: return raw SELECT row for a given id (PDO branch) and the JSON-store row for the same id.
	 * Used by the field-manager diag endpoint to confirm reads vs writes are aligned.
	 *
	 * @return array{id: string, mysqlRow: array<string, mixed>|null, jsonRow: array<string, mixed>|null, pdoActive: bool}
	 */
	public function diagnoseFormCustomFieldRow(string $id): array {
		$id = preg_replace('/[^a-zA-Z0-9_-]/', '', trim($id));
		$out = [
			'id' => $id,
			'mysqlRow' => null,
			'jsonRow' => null,
			'pdoActive' => $this->pdo !== null,
		];
		if ($id === '') {
			return $out;
		}
		if ($this->pdo) {
			try {
				$stmt = $this->pdo->prepare(
					'SELECT id, link_id, display_name, field_type, matching_tag, value_text, location, is_system, created_at, updated_at
					 FROM form_custom_fields WHERE id = :id'
				);
				$stmt->execute([':id' => $id]);
				$row = $stmt->fetch();
				$out['mysqlRow'] = is_array($row) ? $row : null;
			} catch (\Throwable $e) {
				$this->logger->warning('diagnoseFormCustomFieldRow query failed: ' . $e->getMessage());
			}
		}
		foreach ($this->db['formCustomFields'] ?? [] as $row) {
			if ((string)($row['id'] ?? '') === $id) {
				$out['jsonRow'] = $row;
				break;
			}
		}
		return $out;
	}

	public function deleteFieldManagerCustomField(string $id): bool {
		$id = preg_replace('/[^a-zA-Z0-9_-]/', '', trim($id));
		if ($id === '') {
			return false;
		}
		if ($this->pdo) {
			$current = $this->getFormCustomFields();
			foreach ($current as $row) {
				if ((string)($row['id'] ?? '') === $id && !empty($row['isSystem'])) {
					return false;
				}
			}
			$stmt = $this->pdo->prepare('DELETE FROM form_custom_fields WHERE id = :id');
			$stmt->execute([':id' => $id]);
			$rowCount = $stmt->rowCount();
			$this->logger->info('deleteFieldManagerCustomField (mysql)', ['id' => $id, 'rowCount' => $rowCount]);
			return $rowCount > 0;
		}
		foreach ($this->db['formCustomFields'] ?? [] as $row) {
			if ((string)($row['id'] ?? '') === $id && !empty($row['isSystem'])) {
				return false;
			}
		}
		$before = count($this->db['formCustomFields'] ?? []);
		$this->db['formCustomFields'] = array_values(array_filter(
			$this->db['formCustomFields'] ?? [],
			static fn(array $row): bool => (($row['id'] ?? '') !== $id)
		));
		if (count($this->db['formCustomFields']) !== $before) {
			$this->save();
			return true;
		}
		return false;
	}

	/**
	 * @return array<int, array<string, string>>
	 */
	public function getFirmDefaultFields(): array {
		$rows = array_values(array_filter(
			$this->getFormCustomFields(),
			static fn(array $row): bool => strtolower((string)($row['location'] ?? '')) === 'firm'
		));
		foreach ($rows as &$row) {
			$fieldId = preg_replace('/[^a-zA-Z0-9_-]/', '', trim((string)($row['id'] ?? '')));
			if ($fieldId === '') {
				continue;
			}
			$persisted = $this->getAppSettingValue('firm_default_' . $fieldId, '__WPTS_MISSING__');
			if ($persisted !== '__WPTS_MISSING__') {
				$row['value'] = $persisted;
			}
		}
		unset($row);
		return $rows;
	}

	/**
	 * Update only the default value_text/value for a custom field row.
	 *
	 * @return array<string, string>|null
	 */
	public function updateFormCustomFieldValueById(string $id, string $value): ?array {
		$id = preg_replace('/[^a-zA-Z0-9_-]/', '', trim($id));
		if ($id === '') {
			return null;
		}
		$value = mb_substr((string)$value, 0, 8000);
		if ($this->pdo) {
			$now = $this->toSqlDate(date(DATE_ATOM));
			$stmt = $this->pdo->prepare(
				'UPDATE form_custom_fields
				 SET value_text = :value_text,
				     updated_at = :updated_at
				 WHERE id = :id'
			);
			$stmt->execute([
				':value_text' => $value,
				':updated_at' => $now,
				':id' => $id,
			]);
			$this->logger->info('updateFormCustomFieldValueById (mysql)', [
				'id' => $id,
				'rowCount' => $stmt->rowCount(),
			]);
			$verify = $this->pdo->prepare('SELECT id FROM form_custom_fields WHERE id = :id');
			$verify->execute([':id' => $id]);
			if ($verify->fetchColumn() === false) {
				return null;
			}
			$all = $this->getFormCustomFields();
			foreach ($all as $row) {
				if ((string)($row['id'] ?? '') === $id) {
					if (strtolower((string)($row['location'] ?? '')) === 'firm') {
						$this->setAppSettingValue('firm_default_' . $id, $value);
					}
					return $row;
				}
			}
			return null;
		}
		if (!isset($this->db['formCustomFields']) || !is_array($this->db['formCustomFields'])) {
			$this->db['formCustomFields'] = [];
		}
		foreach ($this->db['formCustomFields'] as &$row) {
			if ((string)($row['id'] ?? '') !== $id) {
				continue;
			}
			$row['value'] = $value;
			$row['updatedAt'] = date(DATE_ATOM);
			$updated = $row;
			if (strtolower((string)($row['location'] ?? '')) === 'firm') {
				$this->setAppSettingValue('firm_default_' . $id, $value);
			}
			unset($row);
			$this->save();
			return $updated;
		}
		unset($row);
		return null;
	}

	/** @return array<string, string> */
	public function getClientCustomFieldValues(string $clientId): array {
		$clientId = preg_replace('/[^a-zA-Z0-9_-]/', '', trim((string)$clientId));
		if ($clientId === '') {
			return [];
		}
		$raw = $this->getAppSettingValue('client_custom_fields_' . $clientId, '{}');
		$decoded = json_decode($raw, true);
		if (!is_array($decoded)) {
			return [];
		}
		$out = [];
		foreach ($decoded as $fieldId => $value) {
			$key = preg_replace('/[^a-zA-Z0-9_-]/', '', trim((string)$fieldId));
			if ($key === '') {
				continue;
			}
			$out[$key] = (string)$value;
		}
		return $out;
	}

	/** @param array<string, string> $values */
	public function saveClientCustomFieldValues(string $clientId, array $values): void {
		$clientId = preg_replace('/[^a-zA-Z0-9_-]/', '', trim((string)$clientId));
		if ($clientId === '') {
			return;
		}
		$clean = [];
		foreach ($values as $fieldId => $value) {
			$key = preg_replace('/[^a-zA-Z0-9_-]/', '', trim((string)$fieldId));
			if ($key === '') {
				continue;
			}
			$clean[$key] = mb_substr((string)$value, 0, 8000);
		}
		$this->setAppSettingValue('client_custom_fields_' . $clientId, (string)json_encode($clean, JSON_UNESCAPED_SLASHES));
	}

	public function getFormImporterMatchingMode(): string {
		$value = strtolower(trim($this->getAppSettingValue('form_importer_matching_mode', 'exact')));
		return in_array($value, ['exact', 'regex'], true) ? $value : 'exact';
	}

	public function setFormImporterMatchingMode(string $mode): string {
		$mode = strtolower(trim($mode));
		if (!in_array($mode, ['exact', 'regex'], true)) {
			$mode = 'exact';
		}
		$this->setAppSettingValue('form_importer_matching_mode', $mode);
		return $mode;
	}

	/** @return array<int, array<string, mixed>> */
	public function getFormImporterAliases(): array {
		$default = $this->defaultFormImporterAliases();
		$raw = $this->getAppSettingValue('form_importer_aliases', '');
		if ($raw === '') {
			$this->setAppSettingValue('form_importer_aliases', (string)json_encode($default, JSON_UNESCAPED_SLASHES));
			return $default;
		}
		$decoded = json_decode($raw, true);
		if (!is_array($decoded)) {
			$this->setAppSettingValue('form_importer_aliases', (string)json_encode($default, JSON_UNESCAPED_SLASHES));
			return $default;
		}
		$normalized = [];
		foreach ($decoded as $idx => $row) {
			if (!is_array($row)) {
				continue;
			}
			$linkId = preg_replace('/[^a-z0-9_]/', '_', strtolower(trim((string)($row['linkId'] ?? ''))));
			$pattern = trim((string)($row['pattern'] ?? ''));
			if ($linkId === '' || $pattern === '') {
				continue;
			}
			$idRaw = trim((string)($row['id'] ?? ''));
			$id = $idRaw !== '' ? preg_replace('/[^a-zA-Z0-9_-]/', '', $idRaw) : ('alias_' . ($idx + 1));
			$normalized[] = [
				'id' => $id !== '' ? $id : ('alias_' . ($idx + 1)),
				'linkId' => $linkId,
				'pattern' => $pattern,
				'componentType' => $this->normalizeFormImporterAliasComponentType((string)($row['componentType'] ?? $row['component_type'] ?? 'any')),
				'priority' => $this->normalizeFormImporterAliasPriority($row['priority'] ?? null),
				'scopeType' => $this->normalizeFormImporterAliasScopeType((string)($row['scopeType'] ?? $row['scope_type'] ?? 'global')),
				'scopeValue' => $this->normalizeFormImporterAliasScopeValue((string)($row['scopeValue'] ?? $row['scope_value'] ?? '')),
				'pageMode' => $this->normalizeFormImporterAliasPageMode((string)($row['pageMode'] ?? $row['page_mode'] ?? 'any')),
				'pageValue' => $this->normalizeFormImporterAliasPageValue((string)($row['pageValue'] ?? $row['page_value'] ?? '')),
				'numberMode' => $this->normalizeFormImporterAliasNumberMode((string)($row['numberMode'] ?? $row['number_mode'] ?? 'any')),
				'numberValue' => $this->normalizeFormImporterAliasNumberValue((string)($row['numberValue'] ?? $row['number_value'] ?? '')),
				'requiresValue' => !empty($row['requiresValue']),
				'enabled' => array_key_exists('enabled', $row) ? !empty($row['enabled']) : true,
				'description' => mb_substr(trim((string)($row['description'] ?? '')), 0, 255),
				'stats' => $this->normalizeFormImporterAliasStats(is_array($row['stats'] ?? null) ? $row['stats'] : []),
			];
			if (!in_array($normalized[count($normalized) - 1]['pageMode'], ['only', 'except'], true)) {
				$normalized[count($normalized) - 1]['pageValue'] = '';
			}
			if (!in_array($normalized[count($normalized) - 1]['numberMode'], ['only', 'except'], true)) {
				$normalized[count($normalized) - 1]['numberValue'] = '';
			}
		}
		if ($normalized === []) {
			$this->setAppSettingValue('form_importer_aliases', (string)json_encode($default, JSON_UNESCAPED_SLASHES));
			return $default;
		}
		$normalized = $this->maybeMigrateFormImporterAliases($normalized);
		$normalized = $this->maybeMigrateReadableFormImporterAliasIds($normalized);
		$normalized = $this->maybeMigrateFormImporterAliasComponentTypes($normalized);
		$normalized = $this->maybeMigrateFormImporterAliasGovernanceFields($normalized);
		$normalized = $this->maybeMigrateFormImporterAliasPageFields($normalized);
		$normalized = $this->maybeMigrateFormImporterAliasNumberFields($normalized);
		return $this->maybeMigrateFormImporterAliasAddressOnlyEnabled($normalized);
	}

	/** @param array<int, array<string, mixed>> $aliases */
	public function setFormImporterAliases(array $aliases): array {
		$normalized = [];
		foreach ($aliases as $idx => $row) {
			if (!is_array($row)) {
				continue;
			}
			$linkId = preg_replace('/[^a-z0-9_]/', '_', strtolower(trim((string)($row['linkId'] ?? ''))));
			$pattern = trim((string)($row['pattern'] ?? ''));
			if ($linkId === '' || $pattern === '') {
				continue;
			}
			$idRaw = trim((string)($row['id'] ?? ''));
			$id = $idRaw !== '' ? preg_replace('/[^a-zA-Z0-9_-]/', '', $idRaw) : ('alias_' . ($idx + 1));
			$normalized[] = [
				'id' => $id !== '' ? $id : ('alias_' . ($idx + 1)),
				'linkId' => $linkId,
				'pattern' => $pattern,
				'componentType' => $this->normalizeFormImporterAliasComponentType((string)($row['componentType'] ?? $row['component_type'] ?? 'any')),
				'priority' => $this->normalizeFormImporterAliasPriority($row['priority'] ?? null),
				'scopeType' => $this->normalizeFormImporterAliasScopeType((string)($row['scopeType'] ?? $row['scope_type'] ?? 'global')),
				'scopeValue' => $this->normalizeFormImporterAliasScopeValue((string)($row['scopeValue'] ?? $row['scope_value'] ?? '')),
				'pageMode' => $this->normalizeFormImporterAliasPageMode((string)($row['pageMode'] ?? $row['page_mode'] ?? 'any')),
				'pageValue' => $this->normalizeFormImporterAliasPageValue((string)($row['pageValue'] ?? $row['page_value'] ?? '')),
				'numberMode' => $this->normalizeFormImporterAliasNumberMode((string)($row['numberMode'] ?? $row['number_mode'] ?? 'any')),
				'numberValue' => $this->normalizeFormImporterAliasNumberValue((string)($row['numberValue'] ?? $row['number_value'] ?? '')),
				'requiresValue' => !empty($row['requiresValue']),
				'enabled' => array_key_exists('enabled', $row) ? !empty($row['enabled']) : true,
				'description' => mb_substr(trim((string)($row['description'] ?? '')), 0, 255),
				'stats' => $this->normalizeFormImporterAliasStats(is_array($row['stats'] ?? null) ? $row['stats'] : []),
			];
			if (!in_array($normalized[count($normalized) - 1]['pageMode'], ['only', 'except'], true)) {
				$normalized[count($normalized) - 1]['pageValue'] = '';
			}
			if (!in_array($normalized[count($normalized) - 1]['numberMode'], ['only', 'except'], true)) {
				$normalized[count($normalized) - 1]['numberValue'] = '';
			}
		}
		if ($normalized === []) {
			$normalized = $this->defaultFormImporterAliases();
		}
		$this->setAppSettingValue('form_importer_aliases', (string)json_encode($normalized, JSON_UNESCAPED_SLASHES));
		return $normalized;
	}

	/** @return array<int, array<string, mixed>> */
	private function defaultFormImporterAliases(): array {
		return [
			[
				'id' => 'alias_firm_name',
				'linkId' => 'firm_name',
				'pattern' => '(atty.?firm|attorney.?firm|law.?firm|firm.?name)',
				'componentType' => 'text',
				'priority' => 100,
				'scopeType' => 'global',
				'scopeValue' => '',
				'pageMode' => 'any',
				'pageValue' => '',
				'numberMode' => 'any',
				'numberValue' => '',
				'requiresValue' => true,
				'enabled' => true,
				'description' => 'Firm name fallback aliases',
				'stats' => ['hits' => 0, 'manualOverrides' => 0, 'lastMatchedAt' => ''],
			],
			[
				'id' => 'alias_case_number',
				'linkId' => 'case_number',
				'pattern' => '(case.?number|case.?no|case.?num|casenumber)',
				'componentType' => 'text',
				'priority' => 100,
				'scopeType' => 'global',
				'scopeValue' => '',
				'pageMode' => 'any',
				'pageValue' => '',
				'numberMode' => 'any',
				'numberValue' => '',
				'requiresValue' => false,
				'enabled' => true,
				'description' => 'Case number fallback aliases',
				'stats' => ['hits' => 0, 'manualOverrides' => 0, 'lastMatchedAt' => ''],
			],
			[
				'id' => 'alias_client_full_name',
				'linkId' => 'client_full_name',
				'pattern' => '(party1|party_1|client.?name|full.?name)',
				'componentType' => 'text',
				'priority' => 100,
				'scopeType' => 'global',
				'scopeValue' => '',
				'pageMode' => 'any',
				'pageValue' => '',
				'numberMode' => 'any',
				'numberValue' => '',
				'requiresValue' => false,
				'enabled' => true,
				'description' => 'Primary party / full-name fallback aliases',
				'stats' => ['hits' => 0, 'manualOverrides' => 0, 'lastMatchedAt' => ''],
			],
			[
				'id' => 'alias_petitioner_name',
				'linkId' => 'client_full_name',
				'pattern' => '(petitioner|petitioners?)',
				'componentType' => 'text',
				'priority' => 100,
				'scopeType' => 'global',
				'scopeValue' => '',
				'pageMode' => 'any',
				'pageValue' => '',
				'numberMode' => 'any',
				'numberValue' => '',
				'requiresValue' => false,
				'enabled' => true,
				'description' => 'Petitioner label fallback aliases',
				'stats' => ['hits' => 0, 'manualOverrides' => 0, 'lastMatchedAt' => ''],
			],
		];
	}

	/** @param array<int, array<string, mixed>> $aliases */
	private function maybeMigrateFormImporterAliases(array $aliases): array {
		$migrationKey = 'form_importer_aliases_migrated_v2';
		if ($this->getAppSettingValue($migrationKey, '') === '1') {
			return $aliases;
		}
		$defaults = $this->defaultFormImporterAliases();
		$existingById = [];
		foreach ($aliases as $row) {
			$id = trim((string)($row['id'] ?? ''));
			if ($id !== '') {
				$existingById[$id] = true;
			}
		}
		$changed = false;
		foreach ($defaults as $row) {
			$id = trim((string)($row['id'] ?? ''));
			if ($id === '' || isset($existingById[$id])) {
				continue;
			}
			$aliases[] = $row;
			$existingById[$id] = true;
			$changed = true;
		}
		if ($changed) {
			$this->setAppSettingValue('form_importer_aliases', (string)json_encode($aliases, JSON_UNESCAPED_SLASHES));
		}
		$this->setAppSettingValue($migrationKey, '1');
		return $aliases;
	}

	/** @param array<int, array<string, mixed>> $aliases */
	private function maybeMigrateReadableFormImporterAliasIds(array $aliases): array {
		$migrationKey = 'form_importer_aliases_migrated_v4_ids';
		if ($this->getAppSettingValue($migrationKey, '') === '1') {
			return $aliases;
		}
		$changed = false;
		$used = [];
		$renamed = [];
		foreach ($aliases as $idx => $row) {
			if (!is_array($row)) {
				continue;
			}
			$rawId = trim((string)($row['id'] ?? ''));
			$currentId = $rawId !== '' ? preg_replace('/[^a-zA-Z0-9_-]/', '', $rawId) : '';
			$nextId = $currentId;
			if ($this->shouldRegenerateFormImporterAliasId($currentId)) {
				$nextId = $this->buildReadableFormImporterAliasId($row, (int)$idx, $used);
				if ($nextId !== $currentId) {
					$changed = true;
				}
			}
			if ($nextId === '') {
				$nextId = $this->buildReadableFormImporterAliasId($row, (int)$idx, $used);
				$changed = true;
			}
			$finalId = $this->makeUniqueAliasId($nextId, $used);
			if ($finalId !== $nextId || $finalId !== $currentId) {
				$changed = true;
			}
			$row['id'] = $finalId;
			$renamed[] = $row;
		}
		if ($renamed === []) {
			$renamed = $aliases;
		}
		if ($changed) {
			$this->setAppSettingValue('form_importer_aliases', (string)json_encode($renamed, JSON_UNESCAPED_SLASHES));
		}
		$this->setAppSettingValue($migrationKey, '1');
		return $renamed;
	}

	/** @param array<int, array<string, mixed>> $aliases */
	private function maybeMigrateFormImporterAliasComponentTypes(array $aliases): array {
		$migrationKey = 'form_importer_aliases_migrated_v5_component_type';
		if ($this->getAppSettingValue($migrationKey, '') === '1') {
			return $aliases;
		}
		$changed = false;
		foreach ($aliases as &$row) {
			if (!is_array($row)) {
				continue;
			}
			$current = $this->normalizeFormImporterAliasComponentType((string)($row['componentType'] ?? ''));
			$id = strtolower(trim((string)($row['id'] ?? '')));
			if ($current === 'any' && in_array($id, ['alias_firm_name', 'alias_case_number', 'alias_client_full_name', 'alias_petitioner_name'], true)) {
				$row['componentType'] = 'text';
				$changed = true;
				continue;
			}
			if (!array_key_exists('componentType', $row) || $current !== (string)($row['componentType'] ?? '')) {
				$row['componentType'] = $current;
				$changed = true;
			}
		}
		unset($row);
		if ($changed) {
			$this->setAppSettingValue('form_importer_aliases', (string)json_encode($aliases, JSON_UNESCAPED_SLASHES));
		}
		$this->setAppSettingValue($migrationKey, '1');
		return $aliases;
	}

	/** @param array<int, array<string, mixed>> $aliases */
	private function maybeMigrateFormImporterAliasGovernanceFields(array $aliases): array {
		$migrationKey = 'form_importer_aliases_migrated_v6_governance';
		if ($this->getAppSettingValue($migrationKey, '') === '1') {
			return $aliases;
		}
		$changed = false;
		foreach ($aliases as &$row) {
			if (!is_array($row)) {
				continue;
			}
			$priority = $this->normalizeFormImporterAliasPriority($row['priority'] ?? null);
			$scopeType = $this->normalizeFormImporterAliasScopeType((string)($row['scopeType'] ?? 'global'));
			$scopeValue = $this->normalizeFormImporterAliasScopeValue((string)($row['scopeValue'] ?? ''));
			if ($scopeType === 'global') {
				$scopeValue = '';
			}
			$stats = $this->normalizeFormImporterAliasStats(is_array($row['stats'] ?? null) ? $row['stats'] : []);
			if (!array_key_exists('priority', $row) || (int)$row['priority'] !== $priority) {
				$row['priority'] = $priority;
				$changed = true;
			}
			if (!array_key_exists('scopeType', $row) || (string)$row['scopeType'] !== $scopeType) {
				$row['scopeType'] = $scopeType;
				$changed = true;
			}
			if (!array_key_exists('scopeValue', $row) || (string)$row['scopeValue'] !== $scopeValue) {
				$row['scopeValue'] = $scopeValue;
				$changed = true;
			}
			if (!array_key_exists('stats', $row) || !is_array($row['stats']) || $row['stats'] !== $stats) {
				$row['stats'] = $stats;
				$changed = true;
			}
		}
		unset($row);
		if ($changed) {
			$this->setAppSettingValue('form_importer_aliases', (string)json_encode($aliases, JSON_UNESCAPED_SLASHES));
		}
		$this->setAppSettingValue($migrationKey, '1');
		return $aliases;
	}

	/** @param array<int, array<string, mixed>> $aliases */
	private function maybeMigrateFormImporterAliasPageFields(array $aliases): array {
		$migrationKey = 'form_importer_aliases_migrated_v7_pages';
		if ($this->getAppSettingValue($migrationKey, '') === '1') {
			return $aliases;
		}
		$changed = false;
		foreach ($aliases as &$row) {
			if (!is_array($row)) {
				continue;
			}
			$pageMode = $this->normalizeFormImporterAliasPageMode((string)($row['pageMode'] ?? 'any'));
			$pageValue = $this->normalizeFormImporterAliasPageValue((string)($row['pageValue'] ?? ''));
			if (!in_array($pageMode, ['only', 'except'], true)) {
				$pageValue = '';
			}
			if (!array_key_exists('pageMode', $row) || (string)$row['pageMode'] !== $pageMode) {
				$row['pageMode'] = $pageMode;
				$changed = true;
			}
			if (!array_key_exists('pageValue', $row) || (string)$row['pageValue'] !== $pageValue) {
				$row['pageValue'] = $pageValue;
				$changed = true;
			}
		}
		unset($row);
		if ($changed) {
			$this->setAppSettingValue('form_importer_aliases', (string)json_encode($aliases, JSON_UNESCAPED_SLASHES));
		}
		$this->setAppSettingValue($migrationKey, '1');
		return $aliases;
	}

	/** @param array<int, array<string, mixed>> $aliases */
	private function maybeMigrateFormImporterAliasNumberFields(array $aliases): array {
		$migrationKey = 'form_importer_aliases_migrated_v8_numbers';
		if ($this->getAppSettingValue($migrationKey, '') === '1') {
			return $aliases;
		}
		$changed = false;
		foreach ($aliases as &$row) {
			if (!is_array($row)) {
				continue;
			}
			$numberMode = $this->normalizeFormImporterAliasNumberMode((string)($row['numberMode'] ?? 'any'));
			$numberValue = $this->normalizeFormImporterAliasNumberValue((string)($row['numberValue'] ?? ''));
			if (!in_array($numberMode, ['only', 'except'], true)) {
				$numberValue = '';
			}
			if (!array_key_exists('numberMode', $row) || (string)$row['numberMode'] !== $numberMode) {
				$row['numberMode'] = $numberMode;
				$changed = true;
			}
			if (!array_key_exists('numberValue', $row) || (string)$row['numberValue'] !== $numberValue) {
				$row['numberValue'] = $numberValue;
				$changed = true;
			}
		}
		unset($row);
		if ($changed) {
			$this->setAppSettingValue('form_importer_aliases', (string)json_encode($aliases, JSON_UNESCAPED_SLASHES));
		}
		$this->setAppSettingValue($migrationKey, '1');
		return $aliases;
	}

	/** @param array<int, array<string, mixed>> $aliases */
	private function maybeMigrateFormImporterAliasAddressOnlyEnabled(array $aliases): array {
		$migrationKey = 'form_importer_aliases_migrated_v9_address_only_enabled';
		if ($this->getAppSettingValue($migrationKey, '') === '1') {
			return $aliases;
		}
		$changed = false;
		foreach ($aliases as &$row) {
			if (!is_array($row)) {
				continue;
			}
			$linkId = strtolower(trim((string)($row['linkId'] ?? '')));
			$shouldEnable = $linkId !== '' && strpos($linkId, 'address') !== false;
			$currentEnabled = !array_key_exists('enabled', $row) || !empty($row['enabled']);
			if ($currentEnabled !== $shouldEnable) {
				$row['enabled'] = $shouldEnable;
				$changed = true;
			}
		}
		unset($row);
		if ($changed) {
			$this->setAppSettingValue('form_importer_aliases', (string)json_encode($aliases, JSON_UNESCAPED_SLASHES));
		}
		$this->setAppSettingValue($migrationKey, '1');
		return $aliases;
	}

	private function shouldRegenerateFormImporterAliasId(string $id): bool {
		$id = strtolower(trim($id));
		if ($id === '') {
			return true;
		}
		if (strpos($id, 'alias_') !== 0) {
			return true;
		}
		// Preserve known stable defaults.
		$keep = [
			'alias_firm_name' => true,
			'alias_case_number' => true,
			'alias_client_full_name' => true,
			'alias_petitioner_name' => true,
		];
		if (isset($keep[$id])) {
			return false;
		}
		// Upgrade obvious machine-generated IDs from older UI builders.
		if (preg_match('/^alias_[a-z0-9]{6,12}$/', $id) === 1) {
			return true;
		}
		if (preg_match('/^alias_[a-z0-9_]+_[a-z0-9]{4}$/', $id) === 1) {
			return true;
		}
		if (preg_match('/^alias_\d+$/', $id) === 1) {
			return true;
		}
		return false;
	}

	private function normalizeFormImporterAliasComponentType(string $value): string {
		$v = strtolower(trim($value));
		if (in_array($v, ['text', 'textarea', 'checkable', 'any'], true)) {
			return $v;
		}
		return 'any';
	}

	/** @param mixed $value */
	private function normalizeFormImporterAliasPriority($value): int {
		$priority = is_numeric($value) ? (int)$value : 100;
		if ($priority < 1) {
			return 1;
		}
		if ($priority > 9999) {
			return 9999;
		}
		return $priority;
	}

	private function normalizeFormImporterAliasScopeType(string $value): string {
		$v = strtolower(trim($value));
		if (in_array($v, ['global', 'form_family', 'template'], true)) {
			return $v;
		}
		return 'global';
	}

	private function normalizeFormImporterAliasScopeValue(string $value): string {
		$v = strtolower(trim($value));
		$v = preg_replace('/[^a-z0-9_-]/', '_', $v);
		$v = trim((string)$v, '_');
		return mb_substr($v, 0, 120);
	}

	private function normalizeFormImporterAliasPageMode(string $value): string {
		$v = strtolower(trim($value));
		if (in_array($v, ['any', 'first', 'last', 'only', 'except'], true)) {
			return $v;
		}
		return 'any';
	}

	private function normalizeFormImporterAliasPageValue(string $value): string {
		$v = strtolower(trim($value));
		$tokens = preg_split('/[^0-9]+/', $v) ?: [];
		$seen = [];
		foreach ($tokens as $token) {
			if ($token === '') {
				continue;
			}
			$n = (int)$token;
			if ($n < 1 || $n > 9999) {
				continue;
			}
			$seen[$n] = true;
		}
		return implode(',', array_keys($seen));
	}

	private function normalizeFormImporterAliasNumberMode(string $value): string {
		$v = strtolower(trim($value));
		if (in_array($v, ['any', 'first', 'last', 'only', 'except'], true)) {
			return $v;
		}
		return 'any';
	}

	private function normalizeFormImporterAliasNumberValue(string $value): string {
		$v = strtolower(trim($value));
		$tokens = preg_split('/[^0-9]+/', $v) ?: [];
		$seen = [];
		foreach ($tokens as $token) {
			if ($token === '') {
				continue;
			}
			$n = (int)$token;
			if ($n < 1 || $n > 9999) {
				continue;
			}
			$seen[$n] = true;
		}
		return implode(',', array_keys($seen));
	}

	/** @param array<string, mixed> $stats */
	private function normalizeFormImporterAliasStats(array $stats): array {
		$hits = is_numeric($stats['hits'] ?? null) ? max(0, (int)$stats['hits']) : 0;
		$manualOverrides = is_numeric($stats['manualOverrides'] ?? null) ? max(0, (int)$stats['manualOverrides']) : 0;
		$lastMatchedAt = trim((string)($stats['lastMatchedAt'] ?? ''));
		return [
			'hits' => $hits,
			'manualOverrides' => $manualOverrides,
			'lastMatchedAt' => $lastMatchedAt,
		];
	}

	/** @param array<string, mixed> $row */
	private function buildReadableFormImporterAliasId(array $row, int $idx, array $used): string {
		$description = strtolower(trim((string)($row['description'] ?? '')));
		$linkId = strtolower(trim((string)($row['linkId'] ?? 'entry')));
		$baseRaw = $description !== '' ? $description : $linkId;
		$base = preg_replace('/[^a-z0-9]+/', '_', $baseRaw);
		$base = trim((string)$base, '_');
		if ($base === '') {
			$base = 'entry_' . ($idx + 1);
		}
		$base = mb_substr($base, 0, 48);
		return $this->makeUniqueAliasId('alias_' . $base, $used);
	}

	/** @param array<string, bool> $used */
	private function makeUniqueAliasId(string $candidate, array &$used): string {
		$base = strtolower(trim($candidate));
		$base = preg_replace('/[^a-z0-9_]/', '_', $base);
		$base = trim((string)$base, '_');
		if ($base === '') {
			$base = 'alias_entry';
		}
		if (strpos($base, 'alias_') !== 0) {
			$base = 'alias_' . $base;
		}
		if (!isset($used[$base])) {
			$used[$base] = true;
			return $base;
		}
		$counter = 2;
		while ($counter < 10000) {
			$next = $base . '_' . $counter;
			if (!isset($used[$next])) {
				$used[$next] = true;
				return $next;
			}
			$counter++;
		}
		$fallback = $base . '_' . date('His');
		$used[$fallback] = true;
		return $fallback;
	}

	private function getAppSettingValue(string $key, string $default = ''): string {
		$key = trim($key);
		if ($key === '') {
			return $default;
		}
		if ($this->pdo) {
			$stmt = $this->pdo->prepare('SELECT setting_value FROM mvp_app_settings WHERE setting_key = :k LIMIT 1');
			$stmt->execute([':k' => $key]);
			$row = $stmt->fetch();
			if (is_array($row) && array_key_exists('setting_value', $row)) {
				return (string)$row['setting_value'];
			}
			return $default;
		}
		$settings = $this->db['appSettings'] ?? [];
		if (is_array($settings) && array_key_exists($key, $settings)) {
			return (string)$settings[$key];
		}
		return $default;
	}

	private function setAppSettingValue(string $key, string $value): void {
		$key = trim($key);
		if ($key === '') {
			return;
		}
		if ($this->pdo) {
			$now = $this->toSqlDate(date(DATE_ATOM));
			$stmt = $this->pdo->prepare(
				'INSERT INTO mvp_app_settings (setting_key, setting_value, updated_at)
				 VALUES (:k, :v, :u)
				 ON DUPLICATE KEY UPDATE
				 setting_value = VALUES(setting_value),
				 updated_at = VALUES(updated_at)'
			);
			$stmt->execute([
				':k' => $key,
				':v' => $value,
				':u' => $now,
			]);
			return;
		}
		if (!isset($this->db['appSettings']) || !is_array($this->db['appSettings'])) {
			$this->db['appSettings'] = [];
		}
		$this->db['appSettings'][$key] = $value;
		$this->save();
	}

	private function uniqueFieldManagerLinkId(string $base, string $excludeId = ''): string {
		$base = trim(strtolower($base));
		$base = preg_replace('/[^a-z0-9_]/', '_', $base);
		$base = trim((string)$base, '_');
		if ($base === '') {
			$base = 'field';
		}
		$existing = $this->getFormCustomFields();
		$byLink = [];
		foreach ($existing as $row) {
			$linkId = strtolower(trim((string)($row['linkId'] ?? '')));
			if ($linkId === '') {
				continue;
			}
			$byLink[$linkId] = (string)($row['id'] ?? '');
		}
		if (!isset($byLink[$base]) || ($excludeId !== '' && $byLink[$base] === $excludeId)) {
			return $base;
		}
		$counter = 2;
		while ($counter < 10000) {
			$candidate = $base . '_' . $counter;
			if (!isset($byLink[$candidate]) || ($excludeId !== '' && $byLink[$candidate] === $excludeId)) {
				return $candidate;
			}
			$counter++;
		}
		return $base . '_' . date('His');
	}

	/** @param array<int, array<string, mixed>> $rows */
	private function ensureProtectedCatalogFields(array $rows): array {
		return $this->ensureProtectedFirmFields(
			$this->ensureProtectedAttorneyFields(
				$this->ensureProtectedCourtFields(
					$this->ensureProtectedClientFields($rows)
				)
			)
		);
	}

	/** @param array<int, array<string, mixed>> $rows */
	private function ensureProtectedFirmFields(array $rows): array {
		$protected = [
			'firm_name' => ['displayName' => 'Firm Name', 'fieldType' => 'text', 'matchingTag' => 'AttyInfo*AttyFirm'],
			'firm_street' => ['displayName' => 'Street Address', 'fieldType' => 'text', 'matchingTag' => 'AttyStreet'],
			'firm_city' => ['displayName' => 'City', 'fieldType' => 'text', 'matchingTag' => 'AttyCity'],
			'firm_state' => ['displayName' => 'State', 'fieldType' => 'text', 'matchingTag' => 'AttyState'],
			'firm_zip' => ['displayName' => 'Zip Code', 'fieldType' => 'text', 'matchingTag' => 'AttyZip'],
			'firm_phone' => ['displayName' => 'Phone', 'fieldType' => 'phone', 'matchingTag' => 'AttyInfo_?_Phone'],
			'firm_fax' => ['displayName' => 'Fax Number', 'fieldType' => 'phone', 'matchingTag' => 'AttyInfo_?_Fax'],
			'firm_email' => ['displayName' => 'EMail Address', 'fieldType' => 'email', 'matchingTag' => 'AttyInfo_?_Email'],
			'firm_attorney_name' => ['displayName' => 'Attorney Name', 'fieldType' => 'text', 'matchingTag' => 'AttyName'],
			'firm_bar_number' => ['displayName' => 'State Bar Number', 'fieldType' => 'text', 'matchingTag' => 'BarNo'],
		];

		$byLink = [];
		$byTag = [];
		$insertedMissingProtectedField = false;
		foreach ($rows as $idx => $row) {
			$linkId = strtolower(trim((string)($row['linkId'] ?? '')));
			if ($linkId !== '') {
				$byLink[$linkId] = $idx;
			}
			$tag = strtolower(trim((string)($row['matchingTag'] ?? '')));
			if ($tag !== '') {
				$byTag[$tag] = $idx;
			}
		}

		foreach ($protected as $linkId => $cfg) {
			$tagKey = strtolower((string)$cfg['matchingTag']);
			if (array_key_exists($linkId, $byLink) || array_key_exists($tagKey, $byTag)) {
				if (array_key_exists($linkId, $byLink)) {
					$idx = $byLink[$linkId];
					$rows[$idx]['isSystem'] = true;
					$rows[$idx]['location'] = 'firm';
					if (trim((string)($rows[$idx]['displayName'] ?? '')) === '') {
						$rows[$idx]['displayName'] = $cfg['displayName'];
					}
					if (trim((string)($rows[$idx]['fieldType'] ?? '')) === '') {
						$rows[$idx]['fieldType'] = $cfg['fieldType'];
					}
					$currentTag = trim((string)($rows[$idx]['matchingTag'] ?? ''));
					$currentLinkId = trim((string)($rows[$idx]['linkId'] ?? ''));
					if ($currentTag === '' || strcasecmp($currentTag, $currentLinkId) === 0) {
						$rows[$idx]['matchingTag'] = $cfg['matchingTag'];
					}
				}
				continue;
			}
			$this->upsertFormCustomFieldRow($linkId, $cfg['displayName'], 'firm', '', $cfg['fieldType'], $cfg['matchingTag'], true);
			$insertedMissingProtectedField = true;
		}

		if ($insertedMissingProtectedField) {
			return $this->getFormCustomFields();
		}

		return $rows;
	}

	/** @param array<int, array<string, mixed>> $rows */
	private function ensureProtectedAttorneyFields(array $rows): array {
		$protected = [
			'attorney_name' => ['displayName' => 'Attorney Name', 'fieldType' => 'text', 'matchingTag' => 'AttyName'],
			'attorney_bar_number' => ['displayName' => 'State Bar Number', 'fieldType' => 'text', 'matchingTag' => 'BarNo'],
			'attorney_firm' => ['displayName' => 'Firm Name', 'fieldType' => 'text', 'matchingTag' => 'AttyInfo*AttyFirm'],
			'attorney_street' => ['displayName' => 'Street Address', 'fieldType' => 'text', 'matchingTag' => 'AttyStreet'],
			'attorney_city' => ['displayName' => 'City', 'fieldType' => 'text', 'matchingTag' => 'AttyCity'],
			'attorney_state' => ['displayName' => 'State', 'fieldType' => 'text', 'matchingTag' => 'AttyState'],
			'attorney_zip' => ['displayName' => 'Zip Code', 'fieldType' => 'text', 'matchingTag' => 'AttyZip'],
			'attorney_phone' => ['displayName' => 'Phone', 'fieldType' => 'phone', 'matchingTag' => 'AttyInfo_?_Phone'],
			'attorney_fax' => ['displayName' => 'Fax Number', 'fieldType' => 'phone', 'matchingTag' => 'AttyInfo_?_Fax'],
			'attorney_email' => ['displayName' => 'EMail Address', 'fieldType' => 'email', 'matchingTag' => 'AttyInfo_?_Email'],
		];

		$byLink = [];
		$insertedMissingProtectedField = false;
		foreach ($rows as $idx => $row) {
			$linkId = strtolower(trim((string)($row['linkId'] ?? '')));
			if ($linkId !== '') {
				$byLink[$linkId] = $idx;
			}
		}

		foreach ($protected as $linkId => $cfg) {
			if (array_key_exists($linkId, $byLink)) {
				$idx = $byLink[$linkId];
				$rows[$idx]['isSystem'] = true;
				$rows[$idx]['location'] = 'attorney';
				if (trim((string)($rows[$idx]['displayName'] ?? '')) === '') {
					$rows[$idx]['displayName'] = $cfg['displayName'];
				}
				if (trim((string)($rows[$idx]['fieldType'] ?? '')) === '') {
					$rows[$idx]['fieldType'] = $cfg['fieldType'];
				}
				$currentTag = trim((string)($rows[$idx]['matchingTag'] ?? ''));
				$currentLinkId = trim((string)($rows[$idx]['linkId'] ?? ''));
				if ($currentTag === '' || strcasecmp($currentTag, $currentLinkId) === 0) {
					$rows[$idx]['matchingTag'] = $cfg['matchingTag'];
				}
				continue;
			}
			$this->upsertFormCustomFieldRow($linkId, $cfg['displayName'], 'attorney', '', $cfg['fieldType'], $cfg['matchingTag'], true);
			$insertedMissingProtectedField = true;
		}

		if ($insertedMissingProtectedField) {
			return $this->getFormCustomFields();
		}

		return $rows;
	}

	/** @param array<int, array<string, mixed>> $rows */
	private function ensureProtectedCourtFields(array $rows): array {
		$protected = [
			'court_county' => ['displayName' => 'County', 'fieldType' => 'text', 'matchingTag' => 'crtcounty'],
			'court_street' => ['displayName' => 'Street Address', 'fieldType' => 'text', 'matchingTag' => 'street'],
			'court_mailing_address' => ['displayName' => 'Mailing Address', 'fieldType' => 'text', 'matchingTag' => 'mailingadd'],
			'court_city_zip' => ['displayName' => 'City / State / ZIP', 'fieldType' => 'text', 'matchingTag' => 'cityzip'],
			'court_branch' => ['displayName' => 'Branch / Department', 'fieldType' => 'text', 'matchingTag' => 'branch'],
			'court_name' => ['displayName' => 'Court Name', 'fieldType' => 'text', 'matchingTag' => 'courtname'],
			'court_city' => ['displayName' => 'City', 'fieldType' => 'text', 'matchingTag' => 'courtcity'],
			'court_state' => ['displayName' => 'State', 'fieldType' => 'text', 'matchingTag' => 'courtstate'],
			'court_zip' => ['displayName' => 'ZIP', 'fieldType' => 'text', 'matchingTag' => 'courtzip'],
			'court_phone' => ['displayName' => 'Phone', 'fieldType' => 'phone', 'matchingTag' => 'courtphone'],
			'court_department' => ['displayName' => 'Department', 'fieldType' => 'text', 'matchingTag' => 'courtdept'],
			'court_room' => ['displayName' => 'Room', 'fieldType' => 'text', 'matchingTag' => 'courtroom'],
			'court_floor' => ['displayName' => 'Floor', 'fieldType' => 'text', 'matchingTag' => 'courtfloor'],
		];

		$byLink = [];
		$insertedMissingProtectedField = false;
		foreach ($rows as $idx => $row) {
			$linkId = strtolower(trim((string)($row['linkId'] ?? '')));
			if ($linkId !== '') {
				$byLink[$linkId] = $idx;
			}
		}

		foreach ($protected as $linkId => $cfg) {
			if (array_key_exists($linkId, $byLink)) {
				$idx = $byLink[$linkId];
				$rows[$idx]['isSystem'] = true;
				$rows[$idx]['location'] = 'court';
				if (trim((string)($rows[$idx]['displayName'] ?? '')) === '') {
					$rows[$idx]['displayName'] = $cfg['displayName'];
				}
				if (trim((string)($rows[$idx]['fieldType'] ?? '')) === '') {
					$rows[$idx]['fieldType'] = $cfg['fieldType'];
				}
				$currentTag = trim((string)($rows[$idx]['matchingTag'] ?? ''));
				$currentLinkId = trim((string)($rows[$idx]['linkId'] ?? ''));
				if ($currentTag === '' || strcasecmp($currentTag, $currentLinkId) === 0) {
					$rows[$idx]['matchingTag'] = $cfg['matchingTag'];
				}
				continue;
			}
			$this->upsertFormCustomFieldRow($linkId, $cfg['displayName'], 'court', '', $cfg['fieldType'], $cfg['matchingTag'], true);
			$insertedMissingProtectedField = true;
		}

		if ($insertedMissingProtectedField) {
			return $this->getFormCustomFields();
		}

		return $rows;
	}

	/** @param array<int, array<string, mixed>> $rows */
	private function ensureProtectedClientFields(array $rows): array {
		$protected = [
			'client_display_name' => ['displayName' => 'Display Name', 'fieldType' => 'text', 'matchingTag' => 'display_name'],
			'client_email' => ['displayName' => 'Email', 'fieldType' => 'email', 'matchingTag' => 'email'],
			'client_phone' => ['displayName' => 'Phone', 'fieldType' => 'phone', 'matchingTag' => 'phone'],
			'client_company' => ['displayName' => 'Company Name', 'fieldType' => 'text', 'matchingTag' => 'company'],
			'client_address' => ['displayName' => 'Address', 'fieldType' => 'text', 'matchingTag' => 'address'],
			'client_first_name' => ['displayName' => 'First Name', 'fieldType' => 'text', 'matchingTag' => 'first_name'],
			'client_middle_name' => ['displayName' => 'Middle Name', 'fieldType' => 'text', 'matchingTag' => 'middle_name'],
			'client_last_name' => ['displayName' => 'Last Name', 'fieldType' => 'text', 'matchingTag' => 'last_name'],
			'client_city' => ['displayName' => 'City', 'fieldType' => 'text', 'matchingTag' => 'city'],
			'client_state' => ['displayName' => 'State', 'fieldType' => 'text', 'matchingTag' => 'state'],
			'client_zip' => ['displayName' => 'ZIP', 'fieldType' => 'text', 'matchingTag' => 'zip'],
		];

		$byLink = [];
		$insertedMissingProtectedField = false;
		foreach ($rows as $idx => $row) {
			$linkId = strtolower(trim((string)($row['linkId'] ?? '')));
			if ($linkId !== '') {
				$byLink[$linkId] = $idx;
			}
		}

		foreach ($protected as $linkId => $cfg) {
			if (array_key_exists($linkId, $byLink)) {
				$idx = $byLink[$linkId];
				$rows[$idx]['isSystem'] = true;
				$rows[$idx]['location'] = 'client';
				if (trim((string)($rows[$idx]['displayName'] ?? '')) === '') {
					$rows[$idx]['displayName'] = $cfg['displayName'];
				}
				if (trim((string)($rows[$idx]['fieldType'] ?? '')) === '') {
					$rows[$idx]['fieldType'] = $cfg['fieldType'];
				}
				$currentTag = trim((string)($rows[$idx]['matchingTag'] ?? ''));
				$currentLinkId = trim((string)($rows[$idx]['linkId'] ?? ''));
				if ($currentTag === '' || strcasecmp($currentTag, $currentLinkId) === 0) {
					$rows[$idx]['matchingTag'] = $cfg['matchingTag'];
				}
				continue;
			}
			$this->upsertFormCustomFieldRow($linkId, $cfg['displayName'], 'client', '', $cfg['fieldType'], $cfg['matchingTag'], true);
			$insertedMissingProtectedField = true;
		}

		if ($insertedMissingProtectedField) {
			return $this->getFormCustomFields();
		}

		return $rows;
	}

	private function normalizeFieldType(string $fieldType): string {
		$raw = strtolower(trim($fieldType));
		if ($raw === 'sample_text') {
			$raw = 'text';
		}
		$allowed = ['text', 'number', 'date', 'checkbox', 'select', 'email', 'phone'];
		return in_array($raw, $allowed, true) ? $raw : 'text';
	}

	private function courtsJsonPath(): string {
		return dirname(dirname(__DIR__)) . '/data/courts_ca.json';
	}

	/** @return array{locations: array<int, array<string, mixed>>} */
	private function loadCourtsJsonSnapshot(): array {
		$path = $this->courtsJsonPath();
		if (!is_file($path)) {
			return ['locations' => []];
		}
		$decoded = json_decode((string)file_get_contents($path), true);
		if (!is_array($decoded)) {
			return ['locations' => []];
		}
		$locations = is_array($decoded['locations'] ?? null) ? $decoded['locations'] : [];
		return ['locations' => array_values($locations)];
	}

	/** @return array<int, array<string, mixed>> */
	public function getCourtLocationsWithDepartments(): array {
		if ($this->pdo) {
			$this->ensureCourtTables();
			$locStmt = $this->pdo->query(
				'SELECT id, court_system, state, county, court_name, street, mailing_address, city, state_code, zip, phone, source, source_id, updated_at
				 FROM court_locations ORDER BY court_system, county, court_name'
			);
			$locations = $locStmt ? ($locStmt->fetchAll(\PDO::FETCH_ASSOC) ?: []) : [];
			if (!empty($locations)) {
				$deptStmt = $this->pdo->query(
					'SELECT id, location_id, department, floor, room, phone, source, updated_at FROM court_departments ORDER BY location_id, department'
				);
				$depts = $deptStmt ? ($deptStmt->fetchAll(\PDO::FETCH_ASSOC) ?: []) : [];
				$byLoc = [];
				foreach ($depts as $d) {
					$lid = (string)($d['location_id'] ?? '');
					if ($lid === '') {
						continue;
					}
					$byLoc[$lid][] = [
						'id' => (string)($d['id'] ?? ''),
						'department' => (string)($d['department'] ?? ''),
						'floor' => (string)($d['floor'] ?? ''),
						'room' => (string)($d['room'] ?? ''),
						'phone' => (string)($d['phone'] ?? ''),
					];
				}
				$out = [];
				foreach ($locations as $loc) {
					$id = (string)($loc['id'] ?? '');
					$out[] = $this->normalizeCourtLocationRow([
						'id' => $id,
						'courtSystem' => (string)($loc['court_system'] ?? 'state'),
						'state' => (string)($loc['state'] ?? 'CA'),
						'county' => (string)($loc['county'] ?? ''),
						'courtName' => (string)($loc['court_name'] ?? ''),
						'street' => (string)($loc['street'] ?? ''),
						'mailingAddress' => (string)($loc['mailing_address'] ?? ''),
						'city' => (string)($loc['city'] ?? ''),
						'stateCode' => (string)($loc['state_code'] ?? 'CA'),
						'zip' => (string)($loc['zip'] ?? ''),
						'phone' => (string)($loc['phone'] ?? ''),
						'source' => (string)($loc['source'] ?? ''),
						'sourceId' => (string)($loc['source_id'] ?? ''),
						'departments' => $byLoc[$id] ?? [],
					]);
				}
				return $out;
			}
		}
		$snap = $this->loadCourtsJsonSnapshot();
		$rows = is_array($snap['locations'] ?? null) ? $snap['locations'] : [];
		$out = [];
		foreach ($rows as $loc) {
			if (!is_array($loc)) {
				continue;
			}
			$out[] = $this->normalizeCourtLocationRow($loc);
		}
		return $out;
	}

	/**
	 * Search local court directory copy.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function searchCourts(string $query, int $limit = 25, string $courtSystem = ''): array {
		$query = strtolower(trim($query));
		$limit = max(1, min(100, $limit));
		if ($query === '') {
			return [];
		}
		$tokens = preg_split('/\s+/', $query) ?: [];
		$tokens = array_values(array_filter($tokens, static fn($t) => $t !== ''));
		if ($tokens === []) {
			return [];
		}
		$systemFilter = strtolower(trim($courtSystem));
		if (!in_array($systemFilter, ['state', 'federal'], true)) {
			$systemFilter = '';
		}

		$locations = $this->getCourtLocationsWithDepartments();
		$matches = [];
		foreach ($locations as $loc) {
			if (!is_array($loc)) {
				continue;
			}
			if ($systemFilter !== '' && strtolower((string)($loc['courtSystem'] ?? 'state')) !== $systemFilter) {
				continue;
			}
			$hay = strtolower(implode(' ', [
				(string)($loc['courtName'] ?? ''),
				(string)($loc['county'] ?? ''),
				(string)($loc['street'] ?? ''),
				(string)($loc['mailingAddress'] ?? ''),
				(string)($loc['city'] ?? ''),
				(string)($loc['zip'] ?? ''),
				(string)($loc['phone'] ?? ''),
			]));
			foreach ((array)($loc['departments'] ?? []) as $dept) {
				if (!is_array($dept)) {
					continue;
				}
				$hay .= ' ' . strtolower(implode(' ', [
					(string)($dept['department'] ?? ''),
					(string)($dept['floor'] ?? ''),
					(string)($dept['room'] ?? ''),
					(string)($dept['phone'] ?? ''),
				]));
			}
			$ok = true;
			foreach ($tokens as $tok) {
				if (strpos($hay, $tok) === false) {
					$ok = false;
					break;
				}
			}
			if ($ok) {
				$matches[] = $loc;
			}
			if (count($matches) >= $limit) {
				break;
			}
		}
		return array_map(fn($row) => $this->normalizeCourtLocationRow($row), $matches);
	}

	/** @param array<int, array<string, mixed>> $locations */
	public function importCourtLocationsSnapshot(array $locations): int {
		$written = 0;
		if ($this->pdo) {
			$this->ensureCourtTables();
			$sqlNow = $this->toSqlDate(date(DATE_ATOM));
			$locStmt = $this->pdo->prepare(
				'INSERT INTO court_locations (id, court_system, state, county, court_name, street, mailing_address, city, state_code, zip, phone, source, source_id, updated_at)
				 VALUES (:id, :court_system, :state, :county, :court_name, :street, :mailing_address, :city, :state_code, :zip, :phone, :source, :source_id, :updated_at)
				 ON DUPLICATE KEY UPDATE
				 court_system = VALUES(court_system), state = VALUES(state), county = VALUES(county), court_name = VALUES(court_name),
				 street = VALUES(street), mailing_address = VALUES(mailing_address), city = VALUES(city),
				 state_code = VALUES(state_code), zip = VALUES(zip), phone = VALUES(phone),
				 source = VALUES(source), source_id = VALUES(source_id), updated_at = VALUES(updated_at)'
			);
			$deptStmt = $this->pdo->prepare(
				'INSERT INTO court_departments (id, location_id, department, floor, room, phone, source, updated_at)
				 VALUES (:id, :location_id, :department, :floor, :room, :phone, :source, :updated_at)
				 ON DUPLICATE KEY UPDATE
				 location_id = VALUES(location_id), department = VALUES(department), floor = VALUES(floor),
				 room = VALUES(room), phone = VALUES(phone), source = VALUES(source), updated_at = VALUES(updated_at)'
			);
			foreach ($locations as $loc) {
				if (!is_array($loc)) {
					continue;
				}
				$id = preg_replace('/[^a-zA-Z0-9_-]/', '', trim((string)($loc['id'] ?? '')));
				if ($id === '') {
					$id = $this->newId('cl');
				}
				$norm = $this->normalizeCourtLocationRow($loc);
				$locStmt->execute([
					':id' => $id,
					':court_system' => mb_substr((string)($norm['courtSystem'] ?? 'state'), 0, 16),
					':state' => mb_substr((string)($norm['state'] ?? 'CA'), 0, 8),
					':county' => mb_substr((string)($norm['county'] ?? ''), 0, 128),
					':court_name' => mb_substr((string)($norm['courtName'] ?? ''), 0, 512),
					':street' => mb_substr((string)($norm['street'] ?? ''), 0, 512),
					':mailing_address' => mb_substr((string)($norm['mailingAddress'] ?? ''), 0, 512),
					':city' => mb_substr((string)($norm['city'] ?? ''), 0, 128),
					':state_code' => mb_substr((string)($norm['stateCode'] ?? 'CA'), 0, 8),
					':zip' => mb_substr((string)($norm['zip'] ?? ''), 0, 16),
					':phone' => mb_substr((string)($norm['phone'] ?? ''), 0, 64),
					':source' => mb_substr((string)($norm['source'] ?? 'import'), 0, 64),
					':source_id' => mb_substr((string)($norm['sourceId'] ?? ''), 0, 128),
					':updated_at' => $sqlNow,
				]);
				$written++;
				foreach ((array)($loc['departments'] ?? []) as $dept) {
					if (!is_array($dept)) {
						continue;
					}
					$did = preg_replace('/[^a-zA-Z0-9_-]/', '', trim((string)($dept['id'] ?? '')));
					if ($did === '') {
						$did = $this->newId('cd');
					}
					$deptStmt->execute([
						':id' => $did,
						':location_id' => $id,
						':department' => mb_substr((string)($dept['department'] ?? ''), 0, 64),
						':floor' => mb_substr((string)($dept['floor'] ?? ''), 0, 32),
						':room' => mb_substr((string)($dept['room'] ?? ''), 0, 64),
						':phone' => mb_substr((string)($dept['phone'] ?? ''), 0, 64),
						':source' => mb_substr((string)($dept['source'] ?? 'import'), 0, 64),
						':updated_at' => $sqlNow,
					]);
				}
			}
			return $written;
		}
		$path = $this->courtsJsonPath();
		$normalized = array_map(fn($loc) => is_array($loc) ? $this->normalizeCourtLocationRow($loc) : $loc, $locations);
		@file_put_contents($path, json_encode(['locations' => array_values($normalized)], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		return count($normalized);
	}
}


