<?php
declare(strict_types=1);

/**
 * Optional local MySQL overrides (e.g. SSH tunnel: 127.0.0.1:3307).
 * Copy db.local.example.php to db.local.php (gitignored). Real env vars always win.
 */
function wpts_apply_db_local_overrides(): void {
	$localFile = __DIR__ . '/db.local.php';
	if (!is_file($localFile)) {
		return;
	}
	$local = require $localFile;
	if (!is_array($local)) {
		return;
	}
	foreach ($local as $key => $value) {
		if (!is_string($key) || $key === '') {
			continue;
		}
		if (getenv($key) !== false) {
			continue;
		}
		if ($value === null) {
			continue;
		}
		$str = is_bool($value) ? ($value ? '1' : '0') : (string)$value;
		if ($str === '') {
			continue;
		}
		putenv($key . '=' . $str);
		$_ENV[$key] = $str;
	}
}

/**
 * Resolved MySQL connection parameters (same defaults as DataStore / DESIGN SPECS).
 *
 * @return array{host: string, port: int, database: string, user: string, password: string, disabled: bool, incomplete: bool}
 */
function wpts_mysql_params(): array {
	// Default to the server's MariaDB service alias. If an environment does not resolve "MySQL",
	// DataStore now tries fallback hosts/sockets automatically.
	$defaultHost = 'MySQL';
	$rawHost = trim((string)(getenv('DB_HOST') ?: ''));
	$host = $rawHost !== '' ? $rawHost : $defaultHost;
	$port = (int)(getenv('DB_PORT') ?: 3306);
	$dbName = trim((string)(getenv('DB_NAME') ?: 'LawDocumentManager.com'));
	$user = trim((string)(getenv('DB_USER') ?: 'ldm'));
	$pass = (string)(getenv('DB_PASSWORD') ?: '3294459786827563');
	$enabled = getenv('DB_ENABLED');
	$disabled = $enabled !== false && in_array(strtolower((string)$enabled), ['0', 'false', 'off', 'no'], true);

	return [
		'host' => $host,
		'port' => $port,
		'database' => $dbName,
		'user' => $user,
		'password' => $pass,
		'disabled' => $disabled,
		'incomplete' => $host === '' || $dbName === '' || $user === '',
	];
}

function wpts_db_required(): bool {
	$v = getenv('DB_REQUIRED');
	if ($v === false || $v === '') {
		return false;
	}
	return in_array(strtolower((string)$v), ['1', 'true', 'yes', 'on'], true);
}
