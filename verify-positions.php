<?php
/**
 * Verify FL-100 Positions
 * Check that positions are being used correctly
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/mvp/lib/field_position_loader.php';
require_once __DIR__ . '/mvp/lib/fl100_test_data_generator.php';

use WebPdfTimeSaver\Mvp\FieldPositionLoader;
use WebPdfTimeSaver\Mvp\FL100TestDataGenerator;

echo "==========================================\n";
echo "Position Verification\n";
echo "==========================================\n\n";

$loader = new FieldPositionLoader();
$templateId = 't_fl100_gc120';

// Load positions
$positions = $loader->loadFieldPositions($templateId);
echo "✅ Loaded " . count($positions) . " positions from data/{$templateId}_positions.json\n\n";

// Load test data
$testData = FL100TestDataGenerator::generateCompleteTestData();
echo "✅ Loaded " . count($testData) . " test data fields\n\n";

// Verify positions exist for test data fields
echo "==========================================\n";
echo "Position Coverage Check\n";
echo "==========================================\n\n";

$missingPositions = [];
$hasPositions = [];

foreach ($testData as $fieldKey => $value) {
    if (isset($positions[$fieldKey])) {
        $hasPositions[] = $fieldKey;
        $pos = $positions[$fieldKey];
        echo "✅ {$fieldKey}: ({$pos['x']}, {$pos['y']}) mm, page {$pos['page']}\n";
    } else {
        $missingPositions[] = $fieldKey;
        echo "❌ {$fieldKey}: NO POSITION\n";
    }
}

echo "\n";
echo "==========================================\n";
echo "Summary\n";
echo "==========================================\n\n";
echo "Fields with positions: " . count($hasPositions) . "\n";
echo "Fields missing positions: " . count($missingPositions) . "\n";
echo "Coverage: " . round((count($hasPositions) / count($testData)) * 100, 1) . "%\n\n";

if (!empty($missingPositions)) {
    echo "Missing positions for:\n";
    foreach ($missingPositions as $field) {
        echo "  - {$field}\n";
    }
    echo "\n";
}

// Check coordinate ranges
echo "==========================================\n";
echo "Coordinate Range Check\n";
echo "==========================================\n\n";

$xValues = [];
$yValues = [];

foreach ($positions as $fieldName => $pos) {
    $xValues[] = (float)($pos['x'] ?? 0);
    $yValues[] = (float)($pos['y'] ?? 0);
}

$minX = min($xValues);
$maxX = max($xValues);
$minY = min($yValues);
$maxY = max($yValues);

echo "X Range: {$minX} to {$maxX} mm\n";
echo "Y Range: {$minY} to {$maxY} mm\n";
echo "Page Size: 215.9 × 279.4 mm (US Letter)\n\n";

if ($maxX > 215.9) {
    echo "⚠️  WARNING: Some X coordinates exceed page width!\n";
}
if ($maxY > 279.4) {
    echo "⚠️  WARNING: Some Y coordinates exceed page height!\n";
}

if ($maxX <= 215.9 && $maxY <= 279.4) {
    echo "✅ All coordinates are within page bounds\n";
}

echo "\n";
echo "==========================================\n";
echo "Sample Positions\n";
echo "==========================================\n\n";

$sampleFields = ['court_county', 'case_number', 'attorney_name', 'petitioner_name', 'attorney_signature'];
foreach ($sampleFields as $field) {
    if (isset($positions[$field])) {
        $pos = $positions[$field];
        echo "{$field}:\n";
        echo "  Position: ({$pos['x']}, {$pos['y']}) mm\n";
        echo "  Page: {$pos['page']}\n";
        echo "  Width: " . ($pos['width'] ?? 'N/A') . " mm\n";
        echo "  Height: " . ($pos['height'] ?? 'N/A') . " mm\n";
        echo "  Font Size: " . ($pos['fontSize'] ?? 'N/A') . " pt\n";
        echo "\n";
    }
}

echo "==========================================\n";
echo "Verification Complete\n";
echo "==========================================\n";

