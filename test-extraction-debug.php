<?php
/**
 * Test extraction and capture all output to file
 */

declare(strict_types=1);

// Capture all output
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/mvp/lib/auto_position_extractor.php';

use WebPdfTimeSaver\Mvp\AutoPositionExtractor;

$testPdf = __DIR__ . '/uploads/w9.pdf';
$templateId = 't_w9_browser_debug';

echo "Testing extraction for: $testPdf\n";
echo "Template ID: $templateId\n\n";

if (!file_exists($testPdf)) {
    die("PDF not found: $testPdf\n");
}

$extractor = new AutoPositionExtractor();

if (!$extractor->isAvailable()) {
    die("Node.js not available\n");
}

echo "Node.js available, starting extraction...\n\n";

$result = $extractor->extractPositions($testPdf, $templateId);

echo "\n=== RESULTS ===\n";
echo "Success: " . ($result['success'] ? 'true' : 'false') . "\n";
echo "Fields count: " . count($result['fields'] ?? []) . "\n";
echo "Method: " . ($result['method'] ?? 'unknown') . "\n";
echo "Methods used: " . (isset($result['methodsUsed']) ? implode(', ', $result['methodsUsed']) : 'none') . "\n";
echo "Errors: " . (count($result['errors'] ?? []) > 0 ? implode(', ', $result['errors']) : 'none') . "\n";

// Check if files were created
$dataDir = __DIR__ . '/data';
$detailsFile = $dataDir . '/' . $templateId . '_extraction_details.json';
$positionFile = $dataDir . '/' . $templateId . '_positions.json';

echo "\n=== FILES ===\n";
echo "Details file exists: " . (file_exists($detailsFile) ? 'YES' : 'NO') . "\n";
echo "Position file exists: " . (file_exists($positionFile) ? 'YES' : 'NO') . "\n";

if (file_exists($detailsFile)) {
    $details = json_decode(file_get_contents($detailsFile), true);
    echo "Details file fields: " . count($details['fields'] ?? []) . "\n";
    echo "Details file success: " . ($details['success'] ?? 'unknown') . "\n";
}

if (file_exists($positionFile)) {
    $positions = json_decode(file_get_contents($positionFile), true);
    echo "Position file fields: " . count($positions ?? []) . "\n";
}

$output = ob_get_clean();
file_put_contents(__DIR__ . '/test-extraction-output.txt', $output);
echo $output;


