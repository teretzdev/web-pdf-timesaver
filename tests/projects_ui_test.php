<?php
declare(strict_types=1);

require __DIR__ . '/../mvp/lib/data.php';
require __DIR__ . '/../mvp/templates/registry.php';

use WebPdfTimeSaver\Mvp\DataStore;
use WebPdfTimeSaver\Mvp\TemplateRegistry;

$failures = 0;
function a_true($c,$m){global $failures; if(!$c){echo "FAIL: $m\n";$failures++;} else { echo "."; }}
function contains($h,$n,$m){a_true(strpos($h,$n)!==false,$m);} 

$store = new DataStore(__DIR__ . '/../data/mvp_test.json');
$templates = TemplateRegistry::load();

// seed 2 projects
$p1 = $store->createProject('Alpha Project');
$p2 = $store->createProject('Zeta Project');

// Render projects view
$projects = $store->getProjects();
$filters = [ 'q' => '', 'status' => '', 'sort' => 'updated_desc' ];
ob_start();
include __DIR__ . '/../mvp/views/projects.php';
$html = ob_get_clean();

contains($html, 'Search projects', 'Search input present');
contains($html, 'name="status"', 'Status filter present');
contains($html, 'name="sort"', 'Sort filter present');
contains($html, 'Add new project', 'Add project present');
contains($html, '?route=project&id=', 'Project links present');

echo "\n\n" . ($failures === 0 ? 'PROJECTS UI TEST PASSED' : (string)$failures . ' FAILURES') . "\n";
exit($failures === 0 ? 0 : 1);




