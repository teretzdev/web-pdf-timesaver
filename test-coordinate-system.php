<?php
/**
 * Test Coordinate System Issues
 * Find out what's actually wrong with the positions
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/mvp/lib/field_position_loader.php';

use WebPdfTimeSaver\Mvp\FieldPositionLoader;

echo "==========================================\n";
echo "Coordinate System Investigation\n";
echo "==========================================\n\n";

$loader = new FieldPositionLoader();
$templateId = 't_fl100_gc120';
$positions = $loader->loadFieldPositions($templateId);

// Page dimensions
$PAGE_WIDTH_MM = 215.9;
$PAGE_HEIGHT_MM = 279.4;
$PAGE_WIDTH_PT = 612;  // US Letter width in points
$PAGE_HEIGHT_PT = 792; // US Letter height in points
$MM_TO_PT = 2.834645669;

echo "Page Dimensions:\n";
echo "  In mm: {$PAGE_WIDTH_MM} × {$PAGE_HEIGHT_MM}\n";
echo "  In points: {$PAGE_WIDTH_PT} × {$PAGE_HEIGHT_PT}\n";
echo "  Conversion factor: {$MM_TO_PT} points/mm\n\n";

// Check if positions are within page bounds
echo "==========================================\n";
echo "Position Analysis\n";
echo "==========================================\n\n";

$outOfBounds = [];
$sampleFields = ['court_county', 'case_number', 'attorney_name', 'petitioner_name'];

foreach ($sampleFields as $fieldName) {
    if (!isset($positions[$fieldName])) {
        continue;
    }
    
    $pos = $positions[$fieldName];
    $x_mm = (float)($pos['x'] ?? 0);
    $y_mm = (float)($pos['y'] ?? 0);
    $x_pt = $x_mm * $MM_TO_PT;
    $y_pt = $y_mm * $MM_TO_PT;
    
    echo "{$fieldName}:\n";
    echo "  Original (mm): ({$x_mm}, {$y_mm})\n";
    echo "  Converted (pt): ({$x_pt}, {$y_pt})\n";
    
    // Check bounds
    $xInBounds = ($x_pt >= 0 && $x_pt <= $PAGE_WIDTH_PT);
    $yInBounds = ($y_pt >= 0 && $y_pt <= $PAGE_HEIGHT_PT);
    
    echo "  X in bounds: " . ($xInBounds ? 'YES' : 'NO') . " (0-{$PAGE_WIDTH_PT})\n";
    echo "  Y in bounds: " . ($yInBounds ? 'YES' : 'NO') . " (0-{$PAGE_HEIGHT_PT})\n";
    
    if (!$xInBounds || !$yInBounds) {
        $outOfBounds[] = $fieldName;
    }
    
    echo "\n";
}

// Check Y-axis issue
echo "==========================================\n";
echo "Y-Axis Origin Check\n";
echo "==========================================\n\n";

echo "FPDF uses TOP-LEFT origin (0,0 at top-left, Y increases downward)\n";
echo "PDF standard uses BOTTOM-LEFT origin (0,0 at bottom-left, Y increases upward)\n\n";

echo "Current positions assume:\n";
$testY = (float)($positions['court_county']['y'] ?? 0);
echo "  court_county Y = {$testY}mm from top\n";
echo "  If this is correct for top-left origin, field should be near top of page\n";
echo "  If field appears at bottom, positions might be in bottom-left coordinates\n\n";

// Check what the actual problem might be
echo "==========================================\n";
echo "Possible Issues\n";
echo "==========================================\n\n";

echo "1. Y-axis flip needed?\n";
echo "   If positions are from bottom-left: y_fpdf = page_height_pt - y_position_pt\n";
echo "   Test: " . ($PAGE_HEIGHT_PT - ($testY * $MM_TO_PT)) . " points from top\n\n";

echo "2. Page size mismatch?\n";
echo "   Code uses: AddPage('P', [215.9, 279.4]) - this is in mm\n";
echo "   FPDF converts internally, but SetXY always uses points\n\n";

echo "3. Background image alignment?\n";
echo "   Background: Image($bgImage, 0, 0, 215.9, 279.4)\n";
echo "   This places image at (0,0) with size 215.9×279.4 mm\n";
echo "   But coordinates might not align with background\n\n";

// Test actual converted coordinates
echo "==========================================\n";
echo "Coordinate Conversion Test\n";
echo "==========================================\n\n";

foreach (['court_county', 'attorney_name', 'petitioner_name'] as $fieldName) {
    if (!isset($positions[$fieldName])) continue;
    
    $pos = $positions[$fieldName];
    $x = (float)($pos['x'] ?? 0);
    $y = (float)($pos['y'] ?? 0);
    
    // Current conversion (assumes top-left, mm)
    $x_pt = $x * $MM_TO_PT;
    $y_pt = $y * $MM_TO_PT;
    
    // Alternative: Y-axis flip (if positions are from bottom)
    $y_flipped = $PAGE_HEIGHT_PT - ($y * $MM_TO_PT);
    
    echo "{$fieldName}:\n";
    echo "  Current: ({$x_pt}, {$y_pt}) points\n";
    echo "  Y-flipped: ({$x_pt}, {$y_flipped}) points\n";
    echo "  Original mm: ({$x}, {$y})\n";
    echo "\n";
}

