<?php
/**
 * Debug Position Issues
 * Check what positions are being used and why they're wrong
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/mvp/lib/field_position_loader.php';

use WebPdfTimeSaver\Mvp\FieldPositionLoader;

echo "==========================================\n";
echo "Position Debug Analysis\n";
echo "==========================================\n\n";

$loader = new FieldPositionLoader();
$templateId = 't_fl100_gc120';

// Load positions
$positions = $loader->loadFieldPositions($templateId);

echo "Loaded positions file: data/{$templateId}_positions.json\n";
echo "Total positions: " . count($positions) . "\n\n";

if (empty($positions)) {
    die("❌ No positions found!\n");
}

// Analyze coordinate system
echo "==========================================\n";
echo "Coordinate System Analysis\n";
echo "==========================================\n";

$sampleFields = array_slice($positions, 0, 10, true);
$xValues = [];
$yValues = [];

foreach ($positions as $fieldName => $pos) {
    $xValues[] = (float)($pos['x'] ?? 0);
    $yValues[] = (float)($pos['y'] ?? 0);
}

$maxX = max($xValues);
$maxY = max($yValues);
$minX = min($xValues);
$minY = min($yValues);

echo "X Range: {$minX} to {$maxX}\n";
echo "Y Range: {$minY} to {$maxY}\n";
echo "\n";

// Determine coordinate system
// FPDF uses points: 0-612 width, 0-792 height (US Letter)
// If values are 0-210 range, likely mm (A4 = 210mm)
// If values are 0-600 range, likely points

if ($maxX < 250 && $maxY < 300) {
    echo "⚠️  COORDINATE SYSTEM ISSUE DETECTED!\n";
    echo "   Coordinates appear to be in MILLIMETERS (0-210mm range)\n";
    echo "   FPDF SetXY expects POINTS (0-612pt range)\n";
    echo "   Conversion needed: 1mm = 2.834645669 points\n";
    echo "\n";
    echo "   Example: x=120mm → x=340pt\n";
    echo "   Current positions would place text OFF the page!\n";
} elseif ($maxX > 500 || $maxY > 700) {
    echo "✅ Coordinates appear to be in POINTS (correct for FPDF)\n";
    echo "   X range suggests points (0-612pt for US Letter)\n";
} else {
    echo "⚠️  Uncertain coordinate system\n";
    echo "   Values don't clearly match mm or points\n";
}

echo "\n";

// Show sample positions
echo "==========================================\n";
echo "Sample Positions\n";
echo "==========================================\n";

foreach ($sampleFields as $fieldName => $pos) {
    $x = (float)($pos['x'] ?? 0);
    $y = (float)($pos['y'] ?? 0);
    echo "{$fieldName}:\n";
    echo "   Position: ({$x}, {$y})\n";
    
    if ($maxX < 250) {
        // Likely mm, show conversion
        $xPoints = $x * 2.834645669;
        $yPoints = $y * 2.834645669;
        echo "   Converted to points: ({$xPoints}, {$yPoints})\n";
    }
    echo "\n";
}

// Check if positions file is using wrong format
echo "==========================================\n";
echo "Position File Format Check\n";
echo "==========================================\n";

$positionsFile = __DIR__ . '/data/' . $templateId . '_positions.json';
if (file_exists($positionsFile)) {
    $fileSize = filesize($positionsFile);
    $fileContent = file_get_contents($positionsFile);
    $firstLine = explode("\n", $fileContent)[0];
    
    echo "File: {$positionsFile}\n";
    echo "Size: {$fileSize} bytes\n";
    echo "First line: {$firstLine}\n";
    echo "\n";
    
    // Check for coordinate system indicators
    if (preg_match('/"x":\s*(\d+\.?\d*)/', $fileContent, $matches)) {
        $sampleX = (float)$matches[1];
        echo "Sample X value: {$sampleX}\n";
        
        if ($sampleX < 250) {
            echo "⚠️  PROBLEM: Coordinates are in MILLIMETERS but FPDF needs POINTS!\n";
            echo "\n";
            echo "SOLUTION:\n";
            echo "   Positions need to be converted from mm to points\n";
            echo "   OR positions file needs to use points instead of mm\n";
        }
    }
}

echo "\n";
echo "==========================================\n";
echo "Recommendation\n";
echo "==========================================\n";

if ($maxX < 250) {
    echo "❌ PROBLEM IDENTIFIED:\n";
    echo "   Position file uses MILLIMETERS but FPDF expects POINTS\n";
    echo "\n";
    echo "FIX OPTIONS:\n";
    echo "1. Convert positions file from mm to points\n";
    echo "2. Update PDF generation to convert mm to points\n";
    echo "3. Use Visual Field Editor to regenerate positions in points\n";
    echo "\n";
    echo "Conversion formula: points = mm * 2.834645669\n";
} else {
    echo "✅ Coordinates appear correct for FPDF (points)\n";
    echo "   Issue may be elsewhere (field matching, page size, etc.)\n";
}

