<?php
// Minimal MVP router (non-breaking; lives under /mvp)

declare(strict_types=1);

require_once __DIR__ . '/lib/data.php';
require_once __DIR__ . '/templates/registry.php';
require_once __DIR__ . '/lib/fill_service.php';
require_once __DIR__ . '/lib/pdf_field_service.php';
require_once __DIR__ . '/lib/logger.php';

use WebPdfTimeSaver\Mvp\DataStore;
use WebPdfTimeSaver\Mvp\TemplateRegistry;
use WebPdfTimeSaver\Mvp\FillService;
use WebPdfTimeSaver\Mvp\PdfFieldService;

// Initialize logger FIRST so it can be passed to all services
$logger = new \WebPdfTimeSaver\Mvp\Logger();
$store = new DataStore(__DIR__ . '/../data/mvp.json', $logger);
$templates = TemplateRegistry::load();
$fill = new FillService(__DIR__ . '/../output', $logger);
$pdfFieldService = new PdfFieldService();
// Toggle verbose debug logging with env MVP_DEBUG_LOG=1
$isDebug = getenv('MVP_DEBUG_LOG') === '1';

// Input validation and sanitization helpers
function sanitizeString(string $input, int $maxLength = 255): string {
    $sanitized = strip_tags(trim($input));
    return mb_substr($sanitized, 0, $maxLength);
}

function sanitizeId(string $input): string {
    // IDs should only contain alphanumeric, underscore, and hyphen
    return preg_replace('/[^a-zA-Z0-9_-]/', '', $input);
}

function validateEmail(string $email): string {
    $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '';
}

function validatePhone(string $phone): string {
    // Remove all non-numeric characters except + and spaces
    return preg_replace('/[^0-9+\s()-]/', '', $phone);
}

function validateDate(string $date): string {
    // Basic date validation
    $timestamp = strtotime($date);
    return $timestamp !== false ? date('Y-m-d', $timestamp) : '';
}

function validateRoute(string $route): string {
    // Routes should only contain lowercase letters, numbers, hyphens, and slashes
    $sanitized = preg_replace('/[^a-z0-9\/-]/', '', strtolower($route));
    return mb_substr($sanitized, 0, 100);
}

$route = validateRoute($_GET['route'] ?? 'dashboard');

// Handle API routes
if (strpos($route, 'api/') === 0) {
    $apiRoute = substr($route, 4); // Remove 'api/' prefix
    
    switch ($apiRoute) {
        case 'positions/update':
            handlePositionUpdate();
            break;
        default:
            http_response_code(404);
            echo json_encode(['error' => 'API endpoint not found']);
            exit;
    }
}

function render(string $view, array $vars = []): void {
	global $store, $templates, $fill, $pdfFieldService, $logger;
	$vars['store'] = $store;
	$vars['templates'] = $templates;
	$vars['fill'] = $fill;
	$vars['pdfFieldService'] = $pdfFieldService;
	$vars['logger'] = $logger;
	extract($vars);
	include __DIR__ . '/views/layout_header.php';
	include __DIR__ . "/views/{$view}.php";
	include __DIR__ . '/views/layout_footer.php';
}

function handlePositionUpdate(): void {
    header('Content-Type: application/json');
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $input = file_get_contents('php://input');
    $positions = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
        return;
    }
    
    if (!is_array($positions)) {
        http_response_code(400);
        echo json_encode(['error' => 'Positions must be an array']);
        return;
    }
    
    try {
        $positionsFile = __DIR__ . '/../data/t_fl100_gc120_positions.json';
        
        // Backup current positions
        if (file_exists($positionsFile)) {
            $backupFile = $positionsFile . '.backup.' . date('Y-m-d_H-i-s');
            copy($positionsFile, $backupFile);
        }
        
        // Save new positions
        $result = file_put_contents($positionsFile, json_encode($positions, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        
        if ($result === false) {
            throw new Exception('Failed to write positions file');
        }
        
        // Log the update
        $logger = new \WebPdfTimeSaver\Mvp\Logger();
        $logger->info('Position update', [
            'fieldCount' => count($positions),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Positions updated successfully',
            'fieldCount' => count($positions),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
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
case 'dashboard':
	$projects = $store->getProjects();
	$clients = method_exists($store, 'getClients') ? $store->getClients() : [];
	$recentDocuments = [];
	foreach ($projects as $project) {
		$docs = $store->getProjectDocuments($project['id']);
		foreach ($docs as $doc) {
			$doc['project'] = $project;
			$recentDocuments[] = $doc;
		}
	}
	usort($recentDocuments, function($a, $b) {
		return strtotime($b['createdAt'] ?? '') <=> strtotime($a['createdAt'] ?? '');
	});
	$recentDocuments = array_slice($recentDocuments, 0, 5);
	render('dashboard', [ 'projects' => $projects, 'clients' => $clients, 'recentDocuments' => $recentDocuments, 'templates' => $templates ]);
	break;

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
		$cid = sanitizeId((string)($_GET['id'] ?? ''));
		$client = method_exists($store, 'getClient') ? $store->getClient($cid) : null;
		if (!$client) { header('Location: ?route=clients'); exit; }
		$projects = method_exists($store, 'getProjectsByClient') ? $store->getProjectsByClient($cid) : [];
		render('client', [ 'client' => $client, 'projects' => $projects, 'templates' => $templates ]);
		break;

	case 'project':
		$id = sanitizeId((string)($_GET['id'] ?? ''));
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
		header('Location: ?route=dashboard');
		exit;

	case 'populate':
        $logFile = __DIR__ . '/../logs/pdf_debug.log';
        $pdId = sanitizeId((string)($_GET['pd'] ?? ''));
        if ($isDebug) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' POPULATE: Accessing populate form for PD ID: ' . $pdId . PHP_EOL, FILE_APPEND);
        }
		
		$projDoc = $store->getProjectDocumentById($pdId);
        if (!$projDoc) {
            if ($isDebug) {
                file_put_contents($logFile, date('Y-m-d H:i:s') . ' POPULATE: Project document not found' . PHP_EOL, FILE_APPEND);
            }
			header('HTTP/1.1 404 Not Found');
			echo 'Document not found';
			exit;
		}
		
        $template = $templates[$projDoc['templateId']] ?? null;
        if ($isDebug) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' POPULATE: Template ID: ' . ($projDoc['templateId'] ?? 'NONE') . PHP_EOL, FILE_APPEND);
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' POPULATE: Template found: ' . ($template ? 'YES' : 'NO') . PHP_EOL, FILE_APPEND);
        }
		
        $values = $store->getFieldValues($pdId);
        $customFields = $store->getCustomFields($pdId);
        
        if ($isDebug) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' POPULATE: Rendering populate form with values: ' . json_encode($values) . PHP_EOL, FILE_APPEND);
        }
		render('populate', [ 'projectDocument' => $projDoc, 'template' => $template, 'values' => $values, 'customFields' => $customFields ]);
		break;

	case 'populate_test':
		$pdId = sanitizeId((string)($_GET['pd'] ?? ''));
		$projDoc = $store->getProjectDocumentById($pdId);
		if (!$projDoc) {
			header('HTTP/1.1 404 Not Found');
			echo 'Document not found';
			exit;
		}
		$template = $templates[$projDoc['templateId']] ?? null;
		error_log("Template lookup for " . $projDoc['templateId'] . ": " . ($template ? 'FOUND' : 'NOT_FOUND'));
		$values = $store->getFieldValues($pdId);
		$project = $store->getProject($projDoc['projectId']);
		$client = null;
		if ($project && !empty($project['clientId']) && method_exists($store, 'getClient')) {
			$client = $store->getClient($project['clientId']);
		}
		render('populate_test', [ 'projectDocument' => $projDoc, 'template' => $template, 'fieldValues' => $values, 'project' => $project, 'client' => $client, 'templates' => $templates ]);
		break;

	case 'actions/create-project':
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ?route=projects'); exit; }
		$name = sanitizeString((string)($_POST['name'] ?? 'Untitled Project'), 200);
		$clientId = sanitizeId((string)($_POST['clientId'] ?? ''));
		$project = $clientId !== '' && method_exists($store, 'createProjectForClient') ? $store->createProjectForClient($clientId, $name) : $store->createProject($name);
		header('Location: ?route=project&id=' . urlencode($project['id']));
		exit;

	case 'actions/add-document':
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ?route=projects'); exit; }
		$projectId = sanitizeId((string)($_POST['projectId'] ?? ''));
		$templateId = sanitizeId((string)($_POST['templateId'] ?? ''));
		$store->addProjectDocument($projectId, $templateId);
		header('Location: ?route=project&id=' . urlencode($projectId));
		exit;

	case 'actions/create-client':
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ?route=clients'); exit; }
		$displayName = sanitizeString((string)($_POST['displayName'] ?? ''), 200);
		$email = validateEmail((string)($_POST['email'] ?? ''));
		$phone = validatePhone((string)($_POST['phone'] ?? ''));
		if ($displayName !== '' && method_exists($store, 'createClient')) { $store->createClient($displayName, $email, $phone); }
		header('Location: ?route=clients');
		exit;

	case 'actions/update-client-status':
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ?route=clients'); exit; }
		$clientId = (string)($_POST['clientId'] ?? '');
		$status = (string)($_POST['status'] ?? 'active');
		if ($clientId !== '') {
			$ref = new \ReflectionClass($store);
			$prop = $ref->getProperty('db');
			$prop->setAccessible(true);
			$db = $prop->getValue($store);
			foreach ($db['clients'] as &$c) {
				if ($c['id'] === $clientId) {
					$c['status'] = $status;
					$c['updatedAt'] = date(DATE_ATOM);
					break;
				}
			}
			file_put_contents(__DIR__ . '/../data/mvp.json', json_encode($db, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
		}
		header('Location: ?route=clients');
		exit;

	case 'actions/delete-client':
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ?route=clients'); exit; }
		$clientId = (string)($_POST['clientId'] ?? '');
		if ($clientId !== '') {
			$ref = new \ReflectionClass($store);
			$prop = $ref->getProperty('db');
			$prop->setAccessible(true);
			$db = $prop->getValue($store);
			
			// Remove client
			$db['clients'] = array_values(array_filter($db['clients'], fn($c) => $c['id'] !== $clientId));
			
			// Remove projects for this client
			$db['projects'] = array_values(array_filter($db['projects'], fn($p) => $p['clientId'] !== $clientId));
			
			// Remove project documents for deleted projects
			$deletedProjectIds = array_column(array_filter($db['projects'], fn($p) => $p['clientId'] === $clientId), 'id');
			$db['projectDocuments'] = array_values(array_filter($db['projectDocuments'], fn($pd) => !in_array($pd['projectId'], $deletedProjectIds)));
			
			file_put_contents(__DIR__ . '/../data/mvp.json', json_encode($db, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
		}
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
        $logFile = __DIR__ . '/../logs/pdf_debug.log';
        if ($isDebug) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' SAVE FIELDS: Request method: ' . $_SERVER['REQUEST_METHOD'] . PHP_EOL, FILE_APPEND);
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' SAVE FIELDS: POST data: ' . json_encode($_POST) . PHP_EOL, FILE_APPEND);
        }
		
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') { 
            if ($isDebug) {
                file_put_contents($logFile, date('Y-m-d H:i:s') . ' SAVE FIELDS: Not POST request, redirecting' . PHP_EOL, FILE_APPEND);
            }
			header('Location: ?route=projects'); 
			exit; 
		}
		
		$pdId = sanitizeId((string)($_POST['projectDocumentId'] ?? ''));
        if ($isDebug) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' SAVE FIELDS: PD ID: ' . $pdId . PHP_EOL, FILE_APPEND);
        }
		
		$data = $_POST;
		unset($data['projectDocumentId']);
        if ($isDebug) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' SAVE FIELDS: Data to save: ' . json_encode($data) . PHP_EOL, FILE_APPEND);
        }
		
		$store->saveFieldValues($pdId, $data);
        if ($isDebug) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' SAVE FIELDS: Values saved successfully' . PHP_EOL, FILE_APPEND);
        }
		
		header('Location: ?route=populate&pd=' . urlencode($pdId) . '&saved=1');
		exit;

	case 'actions/generate':
		$pdId = sanitizeId((string)($_GET['pd'] ?? ''));
		$projDoc = $store->getProjectDocumentById($pdId);
		if (!$projDoc) { header('Location: ?route=dashboard'); exit; }
		$template = $templates[$projDoc['templateId']] ?? null;
		$values = $store->getFieldValues($pdId);
		
		// Debug: Log what we're working with
		$logFile = __DIR__ . '/../logs/pdf_debug.log';
        if ($isDebug) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' GENERATE DEBUG: PD ID: ' . $pdId . PHP_EOL, FILE_APPEND);
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' GENERATE DEBUG: Template: ' . json_encode($template) . PHP_EOL, FILE_APPEND);
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' GENERATE DEBUG: Values: ' . json_encode($values) . PHP_EOL, FILE_APPEND);
        }
		
        try {
            $result = $fill->generateSimplePdf($template ?? [], $values, ['pdId' => $pdId]);
            $logger->info('actions/generate success: ' . json_encode($result), ['pdId' => $pdId]);
		} catch (\Throwable $e) {
            error_log('PDF generation failed for pd=' . $pdId . ' : ' . $e->getMessage());
            $logger->error('PDF generation failed for pd=' . $pdId . ' : ' . $e->getMessage(), ['pdId' => $pdId]);
			header('Location: ?route=project&id=' . urlencode($projDoc['projectId']));
			exit;
		}
		// persist path and status
		$projDoc['status'] = 'ready_to_sign';
		$projDoc['outputPath'] = $result['filename']; // Store relative path only
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
		header('Location: ?route=actions/download&pd=' . urlencode($pdId));
		exit;

	case 'preview':
		$pdId = (string)($_GET['pd'] ?? '');
		$projDoc = $store->getProjectDocumentById($pdId);
		if (!$projDoc) {
			header('HTTP/1.1 404 Not Found');
			echo 'Document not found';
			exit;
		}
		$template = $templates[$projDoc['templateId']] ?? null;
		$values = $store->getFieldValues($pdId);
		render('preview', [ 'projectDocument' => $projDoc, 'template' => $template, 'values' => $values ]);
		break;

	case 'pdf-preview':
		$pdId = (string)($_GET['pd'] ?? '');
		$projDoc = $store->getProjectDocumentById($pdId);
		if (!$projDoc) {
			header('HTTP/1.1 404 Not Found');
			echo 'Document not found';
			exit;
		}
		$template = $templates[$projDoc['templateId']] ?? null;
		$values = $store->getFieldValues($pdId);
		$customFields = $store->getCustomFields($pdId);
		
		// Get PDF form fields (for now using sample data)
		$pdfFields = $pdfFieldService->getSamplePdfFields();
		
		render('pdf-preview', [ 
			'projectDocument' => $projDoc, 
			'template' => $template, 
			'values' => $values,
			'customFields' => $customFields,
			'pdfFields' => $pdfFields
		]);
		break;

	case 'actions/download':
		$pdId = (string)($_GET['pd'] ?? '');
		$projDoc = $store->getProjectDocumentById($pdId);
		$filename = $projDoc['outputPath'] ?? '';
		
		// Debug: Log download attempt
		error_log("Download Debug - PD ID: " . $pdId);
		error_log("Filename: " . $filename);
		error_log("Project Document: " . json_encode($projDoc));
		
		if (!$filename) { 
			error_log("No filename found for document");
			header('Location: ?route=documents'); 
			exit; 
		}
		
		// Security: Build path within output directory
		$outputDir = realpath(__DIR__ . '/../output');
		$path = $outputDir . DIRECTORY_SEPARATOR . basename($filename);
		
		error_log("Output Dir: " . $outputDir);
		error_log("Full Path: " . $path);
		error_log("File exists: " . (file_exists($path) ? 'YES' : 'NO'));
		
		if (!file_exists($path)) { 
			error_log("File does not exist at path: " . $path);
			header('Location: ?route=documents'); 
			exit; 
		}
		
		header('Content-Type: application/pdf');
		header('Content-Disposition: attachment; filename="' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename) . '"');
		readfile($path);
		exit;

	case 'actions/download-signed':
		$pdId = (string)($_GET['pd'] ?? '');
		$projDoc = $store->getProjectDocumentById($pdId);
		$filename = $projDoc['signedPath'] ?? '';
		
		if (!$filename) { 
			header('Location: ?route=documents'); 
			exit; 
		}
		
		// Security: Build path within output directory
		$outputDir = realpath(__DIR__ . '/../output');
		$path = $outputDir . DIRECTORY_SEPARATOR . basename($filename);
		
		if (!file_exists($path)) { 
			header('Location: ?route=documents'); 
			exit; 
		}
		
		header('Content-Type: application/pdf');
		header('Content-Disposition: attachment; filename="' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename) . '"');
		readfile($path);
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
		if (!$projDoc) { header('Location: ?route=documents'); exit; }
		$filename = $projDoc['outputPath'] ?? '';
		if (!$filename) { header('Location: ?route=documents'); exit; }
		
		// Build full path
		$outputDir = realpath(__DIR__ . '/../output');
		$path = $outputDir . DIRECTORY_SEPARATOR . basename($filename);
		
		if (!file_exists($path)) { header('Location: ?route=documents'); exit; }
		try {
			$result = $fill->stampSigned($path);
		} catch (\Throwable $e) {
			$logger->error('PDF signing failed for pd=' . $pdId . ' path=' . $path . ' : ' . $e->getMessage());
			header('Location: ?route=documents');
			exit;
		}
		$ref = new \ReflectionClass($store);
		$prop = $ref->getProperty('db');
		$prop->setAccessible(true);
		$db = $prop->getValue($store);
		foreach ($db['projectDocuments'] as &$d) if ($d['id'] === $pdId) { $d['status'] = 'signed'; $d['signedPath'] = $result['filename']; }
		foreach ($db['projects'] as &$p) if ($p['id'] === $projDoc['projectId']) { $p['updatedAt'] = date(DATE_ATOM); }
		file_put_contents(__DIR__ . '/../data/mvp.json', json_encode($db, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
		header('Location: ?route=documents');
		exit;

	case 'actions/add-custom-field':
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ?route=projects'); exit; }
		$pdId = (string)($_POST['projectDocumentId'] ?? '');
		$label = trim((string)($_POST['label'] ?? ''));
		$type = (string)($_POST['type'] ?? 'text');
		$placeholder = trim((string)($_POST['placeholder'] ?? ''));
		$required = !empty($_POST['required']);
		if ($pdId !== '' && $label !== '') {
			$store->addCustomField($pdId, $label, $type, $placeholder, $required);
		}
		header('Location: ?route=populate&pd=' . urlencode($pdId));
		exit;

	case 'actions/update-custom-field':
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ?route=projects'); exit; }
		$fieldId = (string)($_POST['fieldId'] ?? '');
		$label = trim((string)($_POST['label'] ?? ''));
		$type = (string)($_POST['type'] ?? 'text');
		$placeholder = trim((string)($_POST['placeholder'] ?? ''));
		$required = !empty($_POST['required']);
		$pdId = (string)($_POST['projectDocumentId'] ?? '');
		if ($fieldId !== '' && $label !== '') {
			$store->updateCustomField($fieldId, $label, $type, $placeholder, $required);
		}
		header('Location: ?route=populate&pd=' . urlencode($pdId));
		exit;

	case 'actions/delete-custom-field':
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ?route=projects'); exit; }
		$fieldId = (string)($_POST['fieldId'] ?? '');
		$pdId = (string)($_POST['projectDocumentId'] ?? '');
		if ($fieldId !== '') {
			$store->deleteCustomField($fieldId);
		}
		header('Location: ?route=populate&pd=' . urlencode($pdId));
		exit;

	case 'actions/update-custom-field-order':
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ?route=projects'); exit; }
		$pdId = (string)($_POST['projectDocumentId'] ?? '');
		$fieldIds = $_POST['fieldIds'] ?? [];
		if ($pdId !== '' && is_array($fieldIds)) {
			$store->updateCustomFieldOrder($pdId, $fieldIds);
		}
		header('Content-Type: application/json');
		echo json_encode(['success' => true]);
		exit;

	case 'documents':
		// Get all documents across all projects
		$allDocuments = [];
		$projects = $store->getProjects();
		foreach ($projects as $project) {
			$docs = $store->getProjectDocuments($project['id']);
			foreach ($docs as $doc) {
				$doc['project'] = $project;
				$doc['client'] = null;
				if (!empty($project['clientId']) && method_exists($store, 'getClient')) {
					$doc['client'] = $store->getClient($project['clientId']);
				}
				$allDocuments[] = $doc;
			}
		}
		// Sort by creation date (newest first)
		usort($allDocuments, function($a, $b) {
			return strtotime($b['createdAt'] ?? '') <=> strtotime($a['createdAt'] ?? '');
		});
		render('documents', [ 'documents' => $allDocuments, 'templates' => $templates ]);
		break;

	case 'templates':
		render('templates', [ 'templates' => $templates ]);
		break;

	case 'template-edit':
		$templateId = (string)($_GET['id'] ?? '');
		$template = $templates[$templateId] ?? null;
		if (!$template) {
			header('Location: ?route=templates');
			exit;
		}
		render('template-edit', [ 'template' => $template, 'templateId' => $templateId ]);
		break;

	case 'activities':
		render('activities');
		break;

	case 'bills':
		render('bills');
		break;

	case 'reports':
		render('reports');
		break;

	case 'settings':
		render('settings');
		break;

	case 'support':
		render('support');
		break;

	default:
		header('Location: ?route=dashboard');
		exit;
}


