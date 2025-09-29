<?php
declare(strict_types=1);

require __DIR__ . '/../mvp/templates/registry.php';

use WebPdfTimeSaver\Mvp\TemplateRegistry;

$failures = 0;
function ok($c,$m){global $failures; if(!$c){echo "FAIL: $m\n";$failures++;} else { echo "."; }}

$templates = TemplateRegistry::load();
ok(is_array($templates) && count($templates) >= 1, 'Templates registry non-empty');
$tpl = reset($templates);
ok(isset($tpl['id'],$tpl['code'],$tpl['name'],$tpl['fields']), 'Template has id/code/name/fields');
ok(isset($tpl['panels']) && is_array($tpl['panels']) && count($tpl['panels'])>=1, 'Template has panels');
foreach ($tpl['fields'] as $f) {
    ok(isset($f['key'],$f['label'],$f['type']), 'Field has key/label/type');
    ok(isset($f['panelId']), 'Field has panelId');
}

echo "\n\n" . ($failures === 0 ? 'REGISTRY SCHEMA TEST PASSED' : (string)$failures . ' FAILURES') . "\n";
exit($failures === 0 ? 0 : 1);




