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
			return [ 'clients' => [], 'projects' => [], 'projectDocuments' => [], 'fieldValues' => [] ];
		}
		$raw = @file_get_contents($this->path);
		if ($raw === false) { return [ 'clients' => [], 'projects' => [], 'projectDocuments' => [], 'fieldValues' => [] ]; }
		$data = json_decode($raw, true);
		if (json_last_error() !== JSON_ERROR_NONE) {
			// If the JSON is corrupted, don't blow up: return empty skeleton and preserve existing file.
			return [ 'clients' => [], 'projects' => [], 'projectDocuments' => [], 'fieldValues' => [] ];
		}
		return array_merge([ 'clients' => [], 'projects' => [], 'projectDocuments' => [], 'fieldValues' => [] ], $data ?? []);
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
		$out = [];
		foreach ($this->db['fieldValues'] as $fv) if ($fv['projectDocumentId'] === $projectDocumentId) $out[$fv['key']] = $fv['value'];
		return $out;
	}
	public function saveFieldValues(string $projectDocumentId, array $kv): void {
		// remove existing
		$this->db['fieldValues'] = array_values(array_filter($this->db['fieldValues'], fn($fv) => $fv['projectDocumentId'] !== $projectDocumentId || !array_key_exists($fv['key'], $kv)));
		// add new
		foreach ($kv as $k => $v) {
			$this->db['fieldValues'][] = [ 'id' => $this->newId('fv'), 'projectDocumentId' => $projectDocumentId, 'key' => $k, 'value' => $v, 'updatedAt' => date(DATE_ATOM) ];
		}
		// touch parent project updatedAt
		$projectId = null;
		foreach ($this->db['projectDocuments'] as $d) if ($d['id'] === $projectDocumentId) { $projectId = $d['projectId']; break; }
		if ($projectId) { $this->touchProject($projectId); }
		$this->save();
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
}


