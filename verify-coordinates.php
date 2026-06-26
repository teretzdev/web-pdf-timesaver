<?php
/**
 * Verify Coordinates - Check actual values used during PDF generation
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/mvp/lib/field_position_loader.php';
require_once __DIR__ . '/mvp/lib/fl100_test_data_generator.php';
require_once __DIR__ . '/mvp/lib/pdf_form_filler.php';
require_once __DIR__ . '/mvp/lib/logger.php';
require_once __DIR__ . '/mvp/templates/registry.php';

use WebPdfTimeSaver\Mvp\FieldPositionLoader;
use WebPdfTimeSaver\Mvp\FL100TestDataGenerator;
use WebPdfTimeSaver\Mvp\PdfFormFiller;
use WebPdfTimeSaver\Mvp\Logger;
use WebPdfTimeSaver\Mvp\TemplateRegistry;

echo "==========================================\n";
echo "Coordinate Verification\n";
echo "==========================================\n\n";

$loader = new FieldPositionLoader();
$templateId = 't_fl100_gc120';

// Load positions
$positions = $loader->loadFieldPositions($templateId);
echo "✅ Loaded " . count($positions) . " positions\n\n";

// Load test data
$testData = FL100TestDataGenerator::generateCompleteTestData();

// Load template
$template = TemplateRegistry::getTemplate($templateId);

echo "==========================================\n";
echo "Coordinate Values Used in PDF Generation\n";
echo "==========================================\n\n";

// Simulate what happens in placeFieldsForPage
foreach ($testData as $fieldKey => $value) {
    if (empty($value)) {
        continue;
    }
    
    if (!isset($positions[$fieldKey])) {
        continue;
    }
    
    $position = $positions[$fieldKey];
    $x = (float)($position['x'] ?? 0);
    $y = (float)($position['y'] ?? 0);
    $width = (float)($position['width'] ?? 100);
    $height = (float)($position['height'] ?? 5);
    $page = (int)($position['page'] ?? 1);
    
    echo "Field: {$fieldKey}\n";
    echo "  Value: " . substr($value, 0, 30) . (strlen($value) > 30 ? '...' : '') . "\n";
    echo "  Position (mm): x={$x}, y={$y}\n";
    echo "  Size (mm): width={$width}, height={$height}\n";
    echo "  Page: {$page}\n";
    
    // Verify coordinate is within page bounds
    $PAGE_WIDTH_MM = 215.9;
    $PAGE_HEIGHT_MM = 279.4;
    
    $xValid = ($x >= 0 && $x <= $PAGE_WIDTH_MM);
    $yValid = ($y >= 0 && $y <= $PAGE_HEIGHT_MM);
    
    if (!$xValid) {
        echo "  ❌ X coordinate OUT OF BOUNDS (0-{$PAGE_WIDTH_MM})\n";
    }
    if (!$yValid) {
        echo "  ❌ Y coordinate OUT OF BOUNDS (0-{$PAGE_HEIGHT_MM})\n";
    }
    if ($xValid && $yValid) {
        echo "  ✅ Coordinates within page bounds\n";
    }
    
    // Check if field would overflow page
    if (($x + $width) > $PAGE_WIDTH_MM) {
        echo "  ⚠️  WARNING: Field width extends beyond page (x + width = " . ($x + $width) . " > {$PAGE_WIDTH_MM})\n";
    }
    if (($y + $height) > $PAGE_HEIGHT_MM) {
        echo "  ⚠️  WARNING: Field height extends beyond page (y + height = " . ($y + $height) . " > {$PAGE_HEIGHT_MM})\n";
    }
    
    echo "\n";
}

echo "==========================================\n";
echo "Coordinate Range Summary\n";
echo "==========================================\n\n";

$xValues = [];
$yValues = [];
$widthValues = [];
$heightValues = [];

foreach ($positions as $fieldName => $pos) {
    $xValues[] = (float)($pos['x'] ?? 0);
    $yValues[] = (float)($pos['y'] ?? 0);
    $widthValues[] = (float)($pos['width'] ?? 100);
    $heightValues[] = (float)($pos['height'] ?? 5);
}

$minX = min($xValues);
$maxX = max($xValues);
$minY = min($yValues);
$maxY = max($yValues);
$minWidth = min($widthValues);
$maxWidth = max($widthValues);
$minHeight = min($heightValues);
$maxHeight = max($heightValues);

echo "X coordinates: {$minX} to {$maxX} mm\n";
echo "Y coordinates: {$minY} to {$maxY} mm\n";
echo "Width: {$minWidth} to {$maxWidth} mm\n";
echo "Height: {$minHeight} to {$maxHeight} mm\n";
echo "Page size: 215.9 × 279.4 mm\n\n";

// Check for potential issues
$issues = [];

if ($maxX > 215.9) {
    $issues[] = "X coordinates exceed page width";
}
if ($maxY > 279.4) {
    $issues[] = "Y coordinates exceed page height";
}

// Check if any field extends beyond page
foreach ($positions as $fieldName => $pos) {
    $x = (float)($pos['x'] ?? 0);
    $y = (float)($pos['y'] ?? 0);
    $width = (float)($pos['width'] ?? 100);
    $height = (float)($pos['height'] ?? 5);
    
    if (($x + $width) > 215.9) {
        $issues[] = "Field '{$fieldName}' extends beyond page width (x + width = " . ($x + $width) . ")";
    }
    if (($y + $height) > 279.4) {
        $issues[] = "Field '{$fieldName}' extends beyond page height (y + height = " . ($y + $height) . ")";
    }
}

if (empty($issues)) {
    echo "✅ All coordinates are valid and within page bounds\n";
} else {
    echo "⚠️  Issues found:\n";
    foreach ($issues as $issue) {
        echo "  - {$issue}\n";
    }
}

echo "\n";
echo "==========================================\n";
echo "FPDF Coordinate System Verification\n";
echo "==========================================\n\n";

echo "Page creation: AddPage('P', [215.9, 279.4])\n";
echo "  → This uses mm dimensions\n";
echo "  → FPDF SetXY() will also use mm when page is in mm\n";
echo "  → No conversion needed!\n\n";

echo "Example coordinate usage:\n";
echo "  SetXY(120, 45) → 120mm from left, 45mm from top\n";
echo "  This matches the position file values directly\n\n";

echo "==========================================\n";
echo "Verification Complete\n";
echo "==========================================\n";

