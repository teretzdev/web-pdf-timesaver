<?php
declare(strict_types=1);

require __DIR__ . '/../mvp/lib/data.php';
require __DIR__ . '/../mvp/templates/registry.php';

use WebPdfTimeSaver\Mvp\DataStore;
use WebPdfTimeSaver\Mvp\TemplateRegistry;

$failures = 0;
function ok_dom($c,$m){global $failures; if(!$c){echo "FAIL: $m\n";$failures++;} else { echo "."; }}
function load_dom(string $html): DOMXPath { $dom = new DOMDocument('1.0','UTF-8'); libxml_use_internal_errors(true); $dom->loadHTML('<?xml encoding="utf-8" ?>'.$html); libxml_clear_errors(); return new DOMXPath($dom); }

$store = new DataStore(__DIR__ . '/../data/mvp_test.json');
$templates = TemplateRegistry::load();

// Seed a project with a document
$project = $store->createProject('DOM Project');
$tplId = array_key_first($templates);
$store->addProjectDocument($project['id'], $tplId);

// 1) Projects view DOM checks
$projects = $store->getProjects();
$filters = [ 'q' => '', 'status' => '', 'sort' => 'updated_desc' ];
ob_start(); include __DIR__ . '/../mvp/views/projects.php'; $projHtml = ob_get_clean();
$xp = load_dom($projHtml);
ok_dom($xp->query("//input[@name='q']")->length === 1, 'Projects has search input');
ok_dom($xp->query("//select[@name='status']")->length >= 1, 'Projects has status filter');
ok_dom($xp->query("//select[@name='sort']")->length === 1, 'Projects has sort filter');
ok_dom($xp->query("//form[@action='?route=actions/create-project']//input[@name='name']")->length === 1, 'Projects has add project form');

// 2) Project view DOM checks
$documents = $store->getProjectDocuments($project['id']);
ob_start(); include __DIR__ . '/../mvp/views/project.php'; $projectHtml = ob_get_clean();
$xp = load_dom($projectHtml);
ok_dom($xp->query("//form[@action='?route=actions/update-project-name']//input[@name='name']")->length === 1, 'Project has editable name');
ok_dom($xp->query("//form[@action='?route=actions/duplicate-project']")->length === 1, 'Project has duplicate button');
ok_dom($xp->query("//summary[contains(text(),'Add/remove documents')]" )->length >= 1, 'Project has add/remove control');
ok_dom($xp->query("//table//select[@name='status']")->length >= 1, 'Project has document status selector');
ok_dom($xp->query("//form[@action='?route=actions/remove-document']//button")->length >= 1, 'Project has remove doc button');

// 3) Populate view DOM checks
$pd = $documents[0];
$projectDocument = $pd;
$template = $templates[$pd['templateId']];
$values = [];
ob_start(); include __DIR__ . '/../mvp/views/populate.php'; $popHtml = ob_get_clean();
$xp = load_dom($popHtml);
ok_dom($xp->query("//form[@action='?route=actions/save-fields']//input[@type='hidden' and @name='projectDocumentId']")->length === 1, 'Populate form posts with hidden id');
ok_dom($xp->query("//div[h3[contains(text(),'Attorney')]]//input[@name='attorney.name']")->length === 1, 'Populate has attorney.name field');

echo "\n\n" . ($failures === 0 ? 'DOM ASSERTIONS TEST PASSED' : (string)$failures . ' FAILURES') . "\n";
exit($failures === 0 ? 0 : 1);


