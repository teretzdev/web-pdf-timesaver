<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../mvp/lib/fill_service.php';
require __DIR__ . '/../mvp/templates/registry.php';

use WebPdfTimeSaver\Mvp\FillService;
use WebPdfTimeSaver\Mvp\TemplateRegistry;
use Smalot\PdfParser\Parser;

$failures = 0;
function t_assert($cond, $msg) { global $failures; if (!$cond) { echo "FAIL: $msg\n"; $failures++; } else { echo "."; } }

$templates = TemplateRegistry::load();
$tpl = reset($templates);
$values = [
    'attorney.name' => 'RON REITSHSTEIN',
    'attorney.firm' => 'YOUNGMAN REITSHSTEIN, PLC',
    'court.branch' => 'STANLEY MOSK COURTHOUSE',
    'petitioner.name' => 'JOHN DOE',
    'respondent.name' => 'JANE DOE'
];

$service = new FillService(__DIR__ . '/../output');
$result = $service->generateSimplePdf($tpl, $values);
t_assert(file_exists($result['path']), 'Generated PDF exists');

$parser = new Parser();
$pdf = $parser->parseFile($result['path']);
$text = $pdf->getText();

foreach ($values as $k => $v) {
    t_assert(stripos($text, $v) !== false, 'PDF contains value for ' . $k);
}

echo "\n\n" . ($failures === 0 ? 'PDF EXPORT TEST PASSED' : (string)$failures . ' FAILURES') . "\n";
exit($failures === 0 ? 0 : 1);


