<?php
declare(strict_types=1);

require __DIR__ . '/../mvp/templates/registry.php';

use WebPdfTimeSaver\Mvp\TemplateRegistry;

$failures = 0;
function assert_contains(string $haystack, string $needle, string $msg) { global $failures; if (strpos($haystack, $needle) === false) { echo "FAIL: $msg\n"; $failures++; } else { echo "."; } }

$templates = TemplateRegistry::load();
$tpl = reset($templates);

// Render populate view
$projectDocument = [ 'id' => 'pd_test', 'projectId' => 'p_test', 'templateId' => $tpl['id'] ?? 't' ];
$template = $tpl;
$values = [ 'attorney.name' => 'RON REITSHSTEIN', 'attorney.firm' => 'YOUNGMAN REITSHSTEIN, PLC' ];

ob_start();
include __DIR__ . '/../mvp/views/populate.php';
$populateHtml = ob_get_clean();

assert_contains($populateHtml, 'Populate —', 'Populate heading present');
assert_contains($populateHtml, 'Attorney', 'Attorney panel present');
assert_contains($populateHtml, 'name="attorney.name"', 'Attorney name input present');
assert_contains($populateHtml, 'name="attorney.firm"', 'Attorney firm input present');

// Render project view with signed section and controls
$project = [ 'id' => 'p_test', 'name' => 'BHBA EVENT (JOHN DOE)' ];
$documents = [
    [ 'id' => 'pd1', 'projectId' => 'p_test', 'templateId' => $tpl['id'] ?? 't', 'status' => 'signed', 'signedPath' => __DIR__ . '/../output/signed_dummy.pdf' ],
];

ob_start();
include __DIR__ . '/../mvp/views/project.php';
$projectHtml = ob_get_clean();

assert_contains($projectHtml, 'Duplicate', 'Duplicate button present');
assert_contains($projectHtml, 'Add/remove documents', 'Add/remove control present');
assert_contains($projectHtml, 'Signed documents', 'Signed documents section present');

echo "\n\n" . ($failures === 0 ? 'UI RENDER TEST PASSED' : (string)$failures . ' FAILURES') . "\n";
exit($failures === 0 ? 0 : 1);




