<?php
/**
 * FL-100 demo script aligned with current PdfFormFiller API.
 */

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/mvp/lib/pdf_form_filler.php';
require_once __DIR__ . '/mvp/lib/fl100_test_data_generator.php';

use WebPdfTimeSaver\Mvp\FL100TestDataGenerator;
use WebPdfTimeSaver\Mvp\PdfFormFiller;

$templateId = 't_fl100_gc120';
$positionsFile = __DIR__ . '/data/' . $templateId . '_positions.json';
$outputDir = __DIR__ . '/output';

echo "FL-100 Demo (Current API)\n";
echo "=========================\n\n";

if (!file_exists($positionsFile)) {
    echo "Missing positions file: {$positionsFile}\n";
    echo "Run extraction first, then rerun this demo.\n";
    exit(1);
}

$positions = json_decode((string)file_get_contents($positionsFile), true);
if (!is_array($positions) || $positions === []) {
    echo "Positions file exists but is invalid or empty: {$positionsFile}\n";
    exit(1);
}

echo "Loaded positions: " . count($positions) . "\n";

if (!is_dir($outputDir) && !mkdir($outputDir, 0755, true) && !is_dir($outputDir)) {
    echo "Failed to create output directory: {$outputDir}\n";
    exit(1);
}

$template = [
    'id' => $templateId,
    'pageCount' => 1,
];
$testData = FL100TestDataGenerator::generateCompleteTestData();

echo "Generated test fields: " . count($testData) . "\n";
echo "Generating PDF...\n";

try {
    $pdfFiller = new PdfFormFiller($outputDir, __DIR__ . '/uploads');
    $result = $pdfFiller->fillPdfFormWithPositions($template, $testData, $templateId);

    if (($result['success'] ?? false) !== true) {
        echo "Generation failed: " . ($result['error'] ?? 'Unknown error') . "\n";
        exit(1);
    }

    $outputPath = $result['path'] ?? '';
    if ($outputPath === '' || !file_exists($outputPath)) {
        echo "Generation reported success but output file was not found.\n";
        exit(1);
    }

    echo "Success.\n";
    echo "File: " . ($result['filename'] ?? basename($outputPath)) . "\n";
    echo "Path: {$outputPath}\n";
    echo "Fields placed: " . ($result['fields_placed'] ?? 'N/A') . "\n";
    echo "Pages: " . ($result['pages'] ?? 'N/A') . "\n";
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
