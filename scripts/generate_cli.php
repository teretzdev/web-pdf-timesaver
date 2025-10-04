<?php
declare(strict_types=1);

// Simple CLI to generate a PDF and print result + metrics

require __DIR__ . '/../mvp/lib/logger.php';
require __DIR__ . '/../mvp/lib/pdf_form_filler.php';

use WebPdfTimeSaver\Mvp\Logger;
use WebPdfTimeSaver\Mvp\PdfFormFiller;

$logger = new Logger(__DIR__ . '/../logs/app.log');
$filler = new PdfFormFiller(__DIR__ . '/../output', __DIR__ . '/../uploads', $logger);
$filler->setContext(['pdId' => 'cli-test']);

$template = ['id' => 't_fl100_gc120'];
$values = [
    'attorney_name' => 'CLI Test',
    'case_number' => 'CLI-12345',
    'petitioner_name' => 'Alice',
    'respondent_name' => 'Bob'
];

try {
    $result = $filler->fillPdfForm($template, $values);
    $path = $result['path'] ?? ($result['outputPath'] ?? null);
    $size = ($path && file_exists($path)) ? filesize($path) : 0;
    $out = [
        'ok' => true,
        'result' => $result,
        'sizeBytes' => $size
    ];
    echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
} catch (Throwable $e) {
    $out = [ 'ok' => false, 'error' => $e->getMessage() ];
    echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit(1);
}












