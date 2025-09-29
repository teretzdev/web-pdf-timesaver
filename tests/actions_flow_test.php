<?php
declare(strict_types=1);

require __DIR__ . '/../mvp/lib/data.php';
require __DIR__ . '/../mvp/templates/registry.php';
require __DIR__ . '/../mvp/lib/fill_service.php';

use WebPdfTimeSaver\Mvp\DataStore;
use WebPdfTimeSaver\Mvp\TemplateRegistry;
use WebPdfTimeSaver\Mvp\FillService;

$failures = 0;
function t($c,$m){global $failures; if(!$c){echo "FAIL: $m\n";$failures++;} else { echo "."; }}

$store = new DataStore(__DIR__ . '/../data/mvp_test.json');
$templates = TemplateRegistry::load();
$fill = new FillService(__DIR__ . '/../output');

$p = $store->createProject('Flow Project');
$tplId = array_key_first($templates);
$doc = $store->addProjectDocument($p['id'], $tplId);

// rename project
$store->updateProjectName($p['id'], 'Flow Project Renamed');
$new = $store->getProject($p['id']);
t(($new['name'] ?? '') === 'Flow Project Renamed', 'Project renamed via action');

// set status to ready_to_sign via generate
$store->saveFieldValues($doc['id'], [ 'attorney.name' => 'Ron', 'court.branch' => 'Branch' ]);
$values = $store->getFieldValues($doc['id']);
$res = $fill->generateSimplePdf($templates[$tplId], $values);
t(file_exists($res['path']), 'Generated output exists');

// placeholder sign
$signed = $fill->stampSigned($res['path']);
t(file_exists($signed['path']), 'Signed output exists');

echo "\n\n" . ($failures === 0 ? 'ACTIONS FLOW TEST PASSED' : (string)$failures . ' FAILURES') . "\n";
exit($failures === 0 ? 0 : 1);




