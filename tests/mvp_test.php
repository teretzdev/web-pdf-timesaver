<?php
declare(strict_types=1);

require __DIR__ . '/../mvp/lib/data.php';
require __DIR__ . '/../mvp/templates/registry.php';
require __DIR__ . '/../mvp/lib/fill_service.php';

use WebPdfTimeSaver\Mvp\DataStore;
use WebPdfTimeSaver\Mvp\TemplateRegistry;
use WebPdfTimeSaver\Mvp\FillService;

$failures = 0;
function assert_true($cond, $msg) {
	global $failures; if (!$cond) { echo "FAIL: $msg\n"; $failures++; } else { echo "."; }
}

$store = new DataStore(__DIR__ . '/../data/mvp_test.json');
$templates = TemplateRegistry::load();
$fill = new FillService(__DIR__ . '/../output');

// Projects
$p = $store->createProject('Test Project');
assert_true(!empty($p['id']), 'Project id created');
assert_true($store->getProject($p['id']) !== null, 'Project retrievable');

// Add document from registry
$tplId = array_key_first($templates);
$doc = $store->addProjectDocument($p['id'], $tplId);
assert_true(!empty($doc['id']), 'Project document created');

// Save fields
$store->saveFieldValues($doc['id'], [ 'attorney.name' => 'Test Atty', 'court.branch' => 'Branch' ]);
$vals = $store->getFieldValues($doc['id']);
assert_true(($vals['attorney.name'] ?? '') === 'Test Atty', 'Field value saved');

// Generate PDF
$pdf = $fill->generateSimplePdf($templates[$tplId], $vals);
assert_true(file_exists($pdf['path']), 'PDF generated');

// Update project name
$updated = $store->updateProjectName($p['id'], 'Test Project Renamed');
assert_true(($updated['name'] ?? '') === 'Test Project Renamed', 'Project renamed');

// Duplicate project deep
$copy = $store->duplicateProjectDeep($p['id']);
assert_true(!empty($copy) && ($copy['id'] ?? '') !== ($p['id'] ?? ''), 'Project duplicated');
// new project should have a cloned doc
$copyDocs = $store->getProjectDocuments($copy['id']);
assert_true(count($copyDocs) === 1, 'Duplicated project has 1 document');

// Remove document from original project
$store->deleteProjectDocument($doc['id']);
$afterDocs = $store->getProjectDocuments($p['id']);
assert_true(count($afterDocs) === 0, 'Document removed from project');

// Sign flow (placeholder)
$sig = $fill->stampSigned($pdf['path']);
assert_true(file_exists($sig['path']), 'Signed PDF generated');

echo "\n\n" . ($failures === 0 ? 'ALL TESTS PASSED' : (string)$failures . ' FAILURES') . "\n";
exit($failures === 0 ? 0 : 1);


