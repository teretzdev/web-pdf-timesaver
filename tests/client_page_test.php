<?php
declare(strict_types=1);

require __DIR__ . '/../mvp/lib/data.php';
require __DIR__ . '/../mvp/templates/registry.php';

use WebPdfTimeSaver\Mvp\DataStore;

$failures = 0;
function ct($c,$m){global $failures; if(!$c){echo "FAIL: $m\n";$failures++;} else { echo "."; }}

$store = new DataStore(__DIR__ . '/../data/mvp_test.json');
$client = $store->createClient('Client Tabs','x@y.z','555');
$templates = [];
$projects = $store->getProjectsByClient($client['id']);
$clientVar = $client; $projectsVar = $projects; // alias

$client2 = $client; $projects2 = $projects; $templates2 = $templates; $client = $clientVar; $projects = $projectsVar; $templates = $templates2; // satisfy include

ob_start(); include __DIR__ . '/../mvp/views/client.php'; $html = ob_get_clean();

ct(strpos($html, 'Projects') !== false, 'Client page shows Projects tab');
ct(strpos($html, 'Add new project') !== false, 'Client page has add project');

echo "\n\n" . ($failures === 0 ? 'CLIENT PAGE TEST PASSED' : (string)$failures . ' FAILURES') . "\n";
exit($failures === 0 ? 0 : 1);


