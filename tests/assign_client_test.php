<?php
declare(strict_types=1);

require __DIR__ . '/../mvp/lib/data.php';
require __DIR__ . '/../mvp/templates/registry.php';

use WebPdfTimeSaver\Mvp\DataStore;

$failures = 0;
function ac($c,$m){global $failures; if(!$c){echo "FAIL: $m\n";$failures++;} else { echo "."; }}

$store = new DataStore(__DIR__ . '/../data/mvp_test.json');
$client = $store->createClient('Test Client','test@example.com','555-0000');
$proj = $store->createProject('Client Assign Project');
$updated = $store->assignClientToProject($proj['id'], $client['id']);
ac(($updated['clientId'] ?? '') === ($client['id'] ?? ''), 'Client assigned to project');

echo "\n\n" . ($failures === 0 ? 'ASSIGN CLIENT TEST PASSED' : (string)$failures . ' FAILURES') . "\n";
exit($failures === 0 ? 0 : 1);




