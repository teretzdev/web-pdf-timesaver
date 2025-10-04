<?php
declare(strict_types=1);

namespace WebPdfTimeSaver\Mvp;

final class DataStore {
	private string $path;
	private array $db;

	public function __construct(string $path) {
		$this->path = $path;
		if (!is_dir(dirname($path))) {
			mkdir(dirname($path), 0777, true);
		}
		$this->db = $this->load();
	}

	private function load(): array {
		if (!file_exists($this->path)) {
			return [ 'clients' => [], 'projects' => [], 'projectDocuments' => [], 'fieldValues' => [], 'customFields' => [] ];
		}
		$raw = @file_get_contents($this->path);
		if ($raw === false) { return [ 'clients' => [], 'projects' => [], 'projectDocuments' => [], 'fieldValues' => [], 'customFields' => [] ]; }
		$data = json_decode($raw, true);
		if (json_last_error() !== JSON_ERROR_NONE) {
			// If the JSON is corrupted, don't blow up: return empty skeleton and preserve existing file.
			return [ 'clients' => [], 'projects' => [], 'projectDocuments' => [], 'fieldValues' => [], 'customFields' => [] ];
		}
		return array_merge([ 'clients' => [], 'projects' => [], 'projectDocuments' => [], 'fieldValues' => [], 'customFields' => [] ], $data ?? []);
	}

	private function save(): void {
		$encoded = json_encode($this->db, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
		if ($encoded === false) { return; }
		$tmp = $this->path . '.tmp';
		if (file_put_contents($tmp, $encoded, LOCK_EX) === false) { return; }
		@rename($tmp, $this->path);
	}

	private function newId(string $prefix): string {
		try {
			$rand = bin2hex(random_bytes(8));
		} catch (\Throwable $e) {
			$rand = bin2hex(openssl_random_pseudo_bytes(8) ?: md5(uniqid((string)microtime(true), true)));
		}
		return $prefix . '_' . substr($rand, 0, 12);
	}

	// Clients
	public function getClients(): array { return $this->db['clients']; }
	public function createClient(string $displayName, string $email = '', string $phone = ''): array {
		$client = [ 'id' => $this->newId('c'), 'displayName' => $displayName, 'email' => $email, 'phone' => $phone, 'createdAt' => date(DATE_ATOM), 'updatedAt' => date(DATE_ATOM) ];
		$this->db['clients'][] = $client; $this->save(); return $client;
	}
	public function getClient(string $id): ?array { foreach ($this->db['clients'] as $c) if (($c['id'] ?? '') === $id) return $c; return null; }

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
		$this->save(); 
		return $doc;
	}

	public function getFieldValues(string $projectDocumentId): array {
		$logFile = __DIR__ . '/../../logs/pdf_debug.log';
		file_put_contents($logFile, date('Y-m-d H:i:s') . ' DATA DEBUG: Getting field values for PD ID: ' . $projectDocumentId . PHP_EOL, FILE_APPEND);
		file_put_contents($logFile, date('Y-m-d H:i:s') . ' DATA DEBUG: Total field values in DB: ' . count($this->db['fieldValues']) . PHP_EOL, FILE_APPEND);
		
		$out = [];
		foreach ($this->db['fieldValues'] as $fv) {
			file_put_contents($logFile, date('Y-m-d H:i:s') . ' DATA DEBUG: Checking field value: ' . json_encode($fv) . PHP_EOL, FILE_APPEND);
			if ($fv['projectDocumentId'] === $projectDocumentId) {
				$out[$fv['key']] = $fv['value'];
				file_put_contents($logFile, date('Y-m-d H:i:s') . ' DATA DEBUG: MATCH! Added: ' . $fv['key'] . ' = ' . $fv['value'] . PHP_EOL, FILE_APPEND);
			}
		}
		
		file_put_contents($logFile, date('Y-m-d H:i:s') . ' DATA DEBUG: Final output: ' . json_encode($out) . PHP_EOL, FILE_APPEND);
		return $out;
	}
	public function saveFieldValues(string $projectDocumentId, array $kv): void {
		$logFile = __DIR__ . '/../../logs/pdf_debug.log';
		file_put_contents($logFile, date('Y-m-d H:i:s') . ' SAVE VALUES: PD ID: ' . $projectDocumentId . PHP_EOL, FILE_APPEND);
		file_put_contents($logFile, date('Y-m-d H:i:s') . ' SAVE VALUES: Input data: ' . json_encode($kv) . PHP_EOL, FILE_APPEND);
		file_put_contents($logFile, date('Y-m-d H:i:s') . ' SAVE VALUES: Existing field values before: ' . count($this->db['fieldValues']) . PHP_EOL, FILE_APPEND);
		
		// remove ALL existing field values for this project document
		$oldCount = count($this->db['fieldValues']);
		$this->db['fieldValues'] = array_values(array_filter($this->db['fieldValues'], fn($fv) => $fv['projectDocumentId'] !== $projectDocumentId));
		$newCount = count($this->db['fieldValues']);
		file_put_contents($logFile, date('Y-m-d H:i:s') . ' SAVE VALUES: Removed ' . ($oldCount - $newCount) . ' existing values' . PHP_EOL, FILE_APPEND);
		
		// add new field values
		$addedCount = 0;
		foreach ($kv as $k => $v) {
			// Only save non-empty values or explicitly set empty values (like unchecked checkboxes)
			if ($v !== '' || array_key_exists($k, $kv)) {
				$newFieldValue = [ 'id' => $this->newId('fv'), 'projectDocumentId' => $projectDocumentId, 'key' => $k, 'value' => $v, 'updatedAt' => date(DATE_ATOM) ];
				$this->db['fieldValues'][] = $newFieldValue;
				file_put_contents($logFile, date('Y-m-d H:i:s') . ' SAVE VALUES: Added field: ' . json_encode($newFieldValue) . PHP_EOL, FILE_APPEND);
				$addedCount++;
			}
		}
		
		file_put_contents($logFile, date('Y-m-d H:i:s') . ' SAVE VALUES: Added ' . $addedCount . ' new values' . PHP_EOL, FILE_APPEND);
		file_put_contents($logFile, date('Y-m-d H:i:s') . ' SAVE VALUES: Total field values after: ' . count($this->db['fieldValues']) . PHP_EOL, FILE_APPEND);
		
		// touch parent project updatedAt
		$projectId = null;
		foreach ($this->db['projectDocuments'] as $d) if ($d['id'] === $projectDocumentId) { $projectId = $d['projectId']; break; }
		if ($projectId) { $this->touchProject($projectId); }
		
		file_put_contents($logFile, date('Y-m-d H:i:s') . ' SAVE VALUES: Saving to database...' . PHP_EOL, FILE_APPEND);
		$this->save();
		file_put_contents($logFile, date('Y-m-d H:i:s') . ' SAVE VALUES: Database saved successfully' . PHP_EOL, FILE_APPEND);
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

	private function touchProject(string $projectId): void {
		foreach ($this->db['projects'] as &$p) if ($p['id'] === $projectId) { $p['updatedAt'] = date(DATE_ATOM); break; }
	}

	// Custom Fields Management
	public function getCustomFields(string $projectDocumentId): array {
		$fields = array_values(array_filter($this->db['customFields'], fn($cf) => $cf['projectDocumentId'] === $projectDocumentId));
		// Sort by order field, with fallback to creation order
		usort($fields, function($a, $b) {
			$orderA = $a['order'] ?? 999;
			$orderB = $b['order'] ?? 999;
			return $orderA <=> $orderB;
		});
		return $fields;
	}

	public function addCustomField(string $projectDocumentId, string $label, string $type = 'text', string $placeholder = '', bool $required = false): array {
		// Get the next order number for this document
		$existingFields = $this->getCustomFields($projectDocumentId);
		$nextOrder = count($existingFields);
		
		$field = [
			'id' => $this->newId('cf'),
			'projectDocumentId' => $projectDocumentId,
			'label' => $label,
			'type' => $type,
			'placeholder' => $placeholder,
			'required' => $required,
			'order' => $nextOrder,
			'createdAt' => date(DATE_ATOM),
			'updatedAt' => date(DATE_ATOM)
		];
		$this->db['customFields'][] = $field;
		$this->touchProject($this->getProjectIdFromDocument($projectDocumentId));
		$this->save();
		return $field;
	}

	public function updateCustomField(string $fieldId, string $label, string $type = 'text', string $placeholder = '', bool $required = false): ?array {
		foreach ($this->db['customFields'] as &$field) {
			if ($field['id'] === $fieldId) {
				$field['label'] = $label;
				$field['type'] = $type;
				$field['placeholder'] = $placeholder;
				$field['required'] = $required;
				$field['updatedAt'] = date(DATE_ATOM);
				$this->touchProject($this->getProjectIdFromDocument($field['projectDocumentId']));
				$this->save();
				return $field;
			}
		}
		return null;
	}

	public function deleteCustomField(string $fieldId): bool {
		$projectDocumentId = null;
		foreach ($this->db['customFields'] as $idx => $field) {
			if ($field['id'] === $fieldId) {
				$projectDocumentId = $field['projectDocumentId'];
				unset($this->db['customFields'][$idx]);
				break;
			}
		}
		if ($projectDocumentId) {
			$this->db['customFields'] = array_values($this->db['customFields']);
			// Also remove any field values for this custom field
			$this->db['fieldValues'] = array_values(array_filter($this->db['fieldValues'], fn($fv) => !str_starts_with($fv['key'], 'custom_' . $fieldId)));
			$this->touchProject($this->getProjectIdFromDocument($projectDocumentId));
			$this->save();
			return true;
		}
		return false;
	}

	public function updateCustomFieldOrder(string $projectDocumentId, array $fieldIds): bool {
		foreach ($this->db['customFields'] as &$field) {
			if ($field['projectDocumentId'] === $projectDocumentId) {
				$index = array_search($field['id'], $fieldIds);
				if ($index !== false) {
					$field['order'] = $index;
					$field['updatedAt'] = date(DATE_ATOM);
				}
			}
		}
		$this->touchProject($this->getProjectIdFromDocument($projectDocumentId));
		$this->save();
		return true;
	}

	private function getProjectIdFromDocument(string $projectDocumentId): ?string {
		foreach ($this->db['projectDocuments'] as $d) {
			if ($d['id'] === $projectDocumentId) {
				return $d['projectId'];
			}
		}
		return null;
	}
}


