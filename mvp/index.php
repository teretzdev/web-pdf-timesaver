<?php
// Minimal MVP router (non-breaking; lives under /mvp)

declare(strict_types=1);

require __DIR__ . '/lib/data.php';
require __DIR__ . '/templates/registry.php';
require __DIR__ . '/lib/fill_service.php';
require __DIR__ . '/lib/logger.php';

use WebPdfTimeSaver\Mvp\DataStore;
use WebPdfTimeSaver\Mvp\TemplateRegistry;
use WebPdfTimeSaver\Mvp\FillService;

$store = new DataStore(__DIR__ . '/../data/mvp.json');
$templates = TemplateRegistry::load();
$fill = new FillService();
$logger = new \WebPdfTimeSaver\Mvp\Logger();

$route = $_GET['route'] ?? 'projects';

function render(string $view, array $vars = []): void {
	extract($vars);
	include __DIR__ . '/views/layout_header.php';
	include __DIR__ . "/views/{$view}.php";
	include __DIR__ . '/views/layout_footer.php';
}

// Seed demo data for easy navigation when empty
try {
    $needsSeed = count($store->getProjects()) === 0;
} catch (\Throwable $e) { $needsSeed = false; }
if ($needsSeed) {
    // Create a demo client and project with one document
    $client = method_exists($store, 'createClient') ? $store->createClient('John Doe', 'john@example.com', '(555) 123-4567') : null;
    $proj = $store->createProject('BHBA EVENT (JOHN DOE)');
    $tplId = array_key_first($templates);
    if ($tplId) { $doc = $store->addProjectDocument($proj['id'], (string)$tplId); }
}

switch ($route) {
case 'projects':
    $projects = $store->getProjects();
    $q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
    $status = isset($_GET['status']) ? (string)$_GET['status'] : '';
    $sort = isset($_GET['sort']) ? (string)$_GET['sort'] : 'updated_desc';

    if ($q !== '') {
        $projects = array_values(array_filter($projects, function($p) use ($q) {
            return stripos($p['name'] ?? '', $q) !== false;
        }));
    }
    if ($status !== '') {
        $projects = array_values(array_filter($projects, function($p) use ($status) {
            return ($p['status'] ?? '') === $status;
        }));
    }
    usort($projects, function($a, $b) use ($sort) {
        $an = strtolower($a['name'] ?? '');
        $bn = strtolower($b['name'] ?? '');
        $au = strtotime($a['updatedAt'] ?? $a['createdAt'] ?? 'now');
        $bu = strtotime($b['updatedAt'] ?? $b['createdAt'] ?? 'now');
        switch ($sort) {
            case 'name_asc': return $an <=> $bn;
            case 'name_desc': return $bn <=> $an;
            case 'updated_asc': return $au <=> $bu;
            case 'updated_desc': default: return $bu <=> $au;
        }
    });

    render('projects', [ 'projects' => $projects, 'filters' => [ 'q' => $q, 'status' => $status, 'sort' => $sort ] ]);
    break;

	case 'clients':
		$clients = method_exists($store, 'getClients') ? $store->getClients() : [];
		render('clients', [ 'clients' => $clients ]);
		break;

	case 'client':
		$cid = (string)($_GET['id'] ?? '');
		$client = method_exists($store, 'getClient') ? $store->getClient($cid) : null;
		if (!$client) { header('Location: ?route=clients'); exit; }
		$projects = method_exists($store, 'getProjectsByClient') ? $store->getProjectsByClient($cid) : [];
		render('client', [ 'client' => $client, 'projects' => $projects, 'templates' => $templates ]);
		break;

	case 'project':
		$id = (string)($_GET['id'] ?? '');
		$project = $store->getProject($id);
		if (!$project) {
			header('HTTP/1.1 404 Not Found');
			echo 'Project not found';
			exit;
		}
		$docs = $store->getProjectDocuments($id);
		usort($docs, function($a, $b) { return strtotime($b['createdAt'] ?? '') <=> strtotime($a['createdAt'] ?? ''); });
		$allClients = method_exists($store, 'getClients') ? $store->getClients() : [];
		render('project', [ 'project' => $project, 'documents' => $docs, 'templates' => $templates, 'clients' => $allClients ]);
		break;

	case 'actions/update-project-status':
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ?route=projects'); exit; }
		$id = (string)($_POST['id'] ?? '');
		$status = (string)($_POST['status'] ?? 'in_progress');
		$ref = new \ReflectionClass($store);
		$prop = $ref->getProperty('db');
		$prop->setAccessible(true);
		$db = $prop->getValue($store);
		foreach ($db['projects'] as &$p) if ($p['id'] === $id) { $p['status'] = $status; $p['updatedAt'] = date(DATE_ATOM); break; }
		@file_put_contents(__DIR__ . '/../data/mvp.json', json_encode($db, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
		header('Location: ?route=projects');
		exit;

	case 'populate':
		$pdId = (string)($_GET['pd'] ?? '');
		$projDoc = $store->getProjectDocumentById($pdId);
		if (!$projDoc) {
			header('HTTP/1.1 404 Not Found');
			echo 'Document not found';
			exit;
		}
		$template = $templates[$projDoc['templateId']] ?? null;
		$values = $store->getFieldValues($pdId);
		render('populate', [ 'projectDocument' => $projDoc, 'template' => $template, 'values' => $values ]);
		break;

	case 'actions/create-project':
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ?route=projects'); exit; }
		$name = trim((string)($_POST['name'] ?? 'Untitled Project'));
		$clientId = (string)($_POST['clientId'] ?? '');
		$project = $clientId !== '' && method_exists($store, 'createProjectForClient') ? $store->createProjectForClient($clientId, $name) : $store->createProject($name);
		header('Location: ?route=project&id=' . urlencode($project['id']));
		exit;

	case 'actions/add-document':
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ?route=projects'); exit; }
		$projectId = (string)($_POST['projectId'] ?? '');
		$templateId = (string)($_POST['templateId'] ?? '');
		$store->addProjectDocument($projectId, $templateId);
		header('Location: ?route=project&id=' . urlencode($projectId));
		exit;

	case 'actions/create-client':
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ?route=clients'); exit; }
		$displayName = trim((string)($_POST['displayName'] ?? ''));
		$email = trim((string)($_POST['email'] ?? ''));
		$phone = trim((string)($_POST['phone'] ?? ''));
		if ($displayName !== '' && method_exists($store, 'createClient')) { $store->createClient($displayName, $email, $phone); }
		header('Location: ?route=clients');
		exit;

	case 'actions/update-project-name':
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ?route=projects'); exit; }
		$projectId = (string)($_POST['id'] ?? '');
		$newName = trim((string)($_POST['name'] ?? ''));
		if ($projectId !== '' && $newName !== '') { $store->updateProjectName($projectId, $newName); }
		header('Location: ?route=project&id=' . urlencode($projectId));
		exit;

	case 'actions/assign-client':
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ?route=projects'); exit; }
		$projectId = (string)($_POST['projectId'] ?? '');
		$clientId = (string)($_POST['clientId'] ?? '');
		if ($projectId !== '' && $clientId !== '' && method_exists($store, 'assignClientToProject')) { $store->assignClientToProject($projectId, $clientId); }
		header('Location: ?route=project&id=' . urlencode($projectId));
		exit;

	case 'actions/save-fields':
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ?route=projects'); exit; }
		$pdId = (string)($_POST['projectDocumentId'] ?? '');
		$data = $_POST;
		unset($data['projectDocumentId']);
		$store->saveFieldValues($pdId, $data);
		header('Location: ?route=populate&pd=' . urlencode($pdId));
		exit;

	case 'actions/generate':
		$pdId = (string)($_GET['pd'] ?? '');
		$projDoc = $store->getProjectDocumentById($pdId);
		if (!$projDoc) { header('Location: ?route=projects'); exit; }
		$template = $templates[$projDoc['templateId']] ?? null;
		$values = $store->getFieldValues($pdId);
		try {
			$result = $fill->generateSimplePdf($template ?? [], $values);
		} catch (\Throwable $e) {
			$logger->error('PDF generation failed for pd=' . $pdId . ' : ' . $e->getMessage());
			header('Location: ?route=project&id=' . urlencode($projDoc['projectId']));
			exit;
		}
		// persist path and status
		$projDoc['status'] = 'ready_to_sign';
		$projDoc['outputPath'] = $result['path'];
		// naive update
		$docs = $store->getProjectDocuments($projDoc['projectId']);
		// replace in DB
		$ref = new \ReflectionClass($store);
		$prop = $ref->getProperty('db');
		$prop->setAccessible(true);
		$db = $prop->getValue($store);
		foreach ($db['projectDocuments'] as &$d) if ($d['id'] === $pdId) { $d = $projDoc; break; }
		foreach ($db['projects'] as &$p) if ($p['id'] === $projDoc['projectId']) { $p['updatedAt'] = date(DATE_ATOM); break; }
		file_put_contents(__DIR__ . '/../data/mvp.json', json_encode($db, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
		header('Location: ?route=project&id=' . urlencode($projDoc['projectId']));
		exit;

	case 'actions/download':
		$pdId = (string)($_GET['pd'] ?? '');
		$projDoc = $store->getProjectDocumentById($pdId);
		$path = $projDoc['outputPath'] ?? '';
		if (!$path || !file_exists($path)) { header('Location: ?route=project&id=' . urlencode($projDoc['projectId'])); exit; }
		header('Content-Type: application/pdf');
		// Use basename and fallback filename; ensure safe header
		$fn = basename($path) ?: 'document.pdf';
		header('Content-Disposition: attachment; filename="' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $fn) . '"');
		$read = readfile($path);
		if ($read === false) { http_response_code(500); echo 'Failed to read file.'; }
		exit;

	case 'actions/update-doc-status':
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ?route=projects'); exit; }
		$pdId = (string)($_POST['id'] ?? '');
		$status = (string)($_POST['status'] ?? 'in_progress');
		$ref = new \ReflectionClass($store);
		$prop = $ref->getProperty('db');
		$prop->setAccessible(true);
		$db = $prop->getValue($store);
		$projectId = '';
		foreach ($db['projectDocuments'] as &$d) if ($d['id'] === $pdId) { $d['status'] = $status; $projectId = $d['projectId']; break; }
		foreach ($db['projects'] as &$p) if ($p['id'] === $projectId) { $p['updatedAt'] = date(DATE_ATOM); break; }
		file_put_contents(__DIR__ . '/../data/mvp.json', json_encode($db, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
		header('Location: ?route=project&id=' . urlencode($projectId));
		exit;

	case 'actions/remove-document':
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ?route=projects'); exit; }
		$pdId = (string)($_POST['id'] ?? '');
		$doc = $store->getProjectDocumentById($pdId);
		$projectId = $doc['projectId'] ?? '';
		if ($pdId !== '') { $store->deleteProjectDocument($pdId); }
		header('Location: ?route=project&id=' . urlencode($projectId));
		exit;

	case 'actions/duplicate-project':
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ?route=projects'); exit; }
		$projectId = (string)($_POST['id'] ?? '');
		$copy = $store->duplicateProjectDeep($projectId);
		$redirectId = $copy['id'] ?? '';
		header('Location: ?route=project&id=' . urlencode($redirectId !== '' ? $redirectId : $projectId));
		exit;

	case 'actions/sign':
		$pdId = (string)($_GET['pd'] ?? '');
		$projDoc = $store->getProjectDocumentById($pdId);
		if (!$projDoc) { header('Location: ?route=projects'); exit; }
		$path = $projDoc['outputPath'] ?? '';
		if (!$path || !file_exists($path)) { header('Location: ?route=project&id=' . urlencode($projDoc['projectId'])); exit; }
		try {
			$result = $fill->stampSigned($path);
		} catch (\Throwable $e) {
			$logger->error('PDF signing failed for pd=' . $pdId . ' path=' . $path . ' : ' . $e->getMessage());
			header('Location: ?route=project&id=' . urlencode($projDoc['projectId']));
			exit;
		}
		$ref = new \ReflectionClass($store);
		$prop = $ref->getProperty('db');
		$prop->setAccessible(true);
		$db = $prop->getValue($store);
		foreach ($db['projectDocuments'] as &$d) if ($d['id'] === $pdId) { $d['status'] = 'signed'; $d['signedPath'] = $result['path']; }
		foreach ($db['projects'] as &$p) if ($p['id'] === $projDoc['projectId']) { $p['updatedAt'] = date(DATE_ATOM); }
		file_put_contents(__DIR__ . '/../data/mvp.json', json_encode($db, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
		header('Location: ?route=project&id=' . urlencode($projDoc['projectId']));
		exit;

	case 'support':
		render('support');
		break;

	default:
		header('Location: ?route=projects');
		exit;
}


