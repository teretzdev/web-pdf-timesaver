<?php
/**
 * Test Field Name Mapping
 * Test the field name mapper with FL-100 extracted positions
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/mvp/lib/field_name_mapper.php';

use WebPdfTimeSaver\Mvp\FieldNameMapper;

echo "==========================================\n";
echo "Field Name Mapping Test\n";
echo "==========================================\n\n";

// Load extracted and reference positions
$extractedFile = __DIR__ . '/data/temp_1762518209_positions.json';
$referenceFile = __DIR__ . '/data/t_fl100_gc120_positions.json';

if (!file_exists($extractedFile)) {
    die("❌ Extracted file not found: $extractedFile\n");
}

if (!file_exists($referenceFile)) {
    die("❌ Reference file not found: $referenceFile\n");
}

$extracted = json_decode(file_get_contents($extractedFile), true);
$reference = json_decode(file_get_contents($referenceFile), true);

if (!$extracted || !$reference) {
    die("❌ Failed to parse JSON files\n");
}

echo "Extracted fields: " . count($extracted) . "\n";
echo "Reference fields: " . count($reference) . "\n\n";

// Test individual mappings
echo "Testing Individual Field Mappings:\n";
echo str_repeat("-", 80) . "\n";

$sampleFields = array_slice($extracted, 0, 10, true);
foreach ($sampleFields as $extractedName => $fieldData) {
    $mappedName = FieldNameMapper::mapToReference($extractedName, $reference);
    $icon = $mappedName ? '✅' : '❌';
    echo "$icon {$extractedName}\n";
    if ($mappedName) {
        echo "   → {$mappedName}\n";
    } else {
        echo "   → (no mapping found)\n";
    }
    echo "\n";
}

// Create mapping report
echo "==========================================\n";
echo "Mapping Report\n";
echo "==========================================\n";
$report = FieldNameMapper::createMappingReport($extracted, $reference);

echo "Total Extracted: " . $report['total_extracted'] . "\n";
echo "Total Reference: " . $report['total_reference'] . "\n";
echo "Mapped: " . $report['mapped'] . "\n";
echo "Unmapped: " . $report['unmapped'] . "\n";
echo "Mapping Rate: " . number_format($report['mapping_rate'], 1) . "%\n\n";

// Show sample mappings
echo "Sample Mappings:\n";
echo str_repeat("-", 80) . "\n";
foreach (array_slice($report['mappings'], 0, 10) as $mapping) {
    echo "✅ {$mapping['extracted']}\n";
    echo "   → {$mapping['reference']}\n\n";
}

// Show unmapped fields
if (!empty($report['unmapped_fields'])) {
    echo "Unmapped Fields (" . count($report['unmapped_fields']) . "):\n";
    echo str_repeat("-", 80) . "\n";
    foreach (array_slice($report['unmapped_fields'], 0, 10) as $unmapped) {
        echo "❌ {$unmapped}\n";
    }
    if (count($report['unmapped_fields']) > 10) {
        echo "... and " . (count($report['unmapped_fields']) - 10) . " more\n";
    }
}

// Test position normalization
echo "\n";
echo "==========================================\n";
echo "Position Normalization Test\n";
echo "==========================================\n";
$normalized = FieldNameMapper::normalizePositions($extracted, $reference);

echo "Normalized fields: " . count($normalized) . "\n";
echo "Sample normalized positions:\n";
echo str_repeat("-", 80) . "\n";

$sampleNormalized = array_slice($normalized, 0, 5, true);
foreach ($sampleNormalized as $fieldName => $fieldData) {
    echo "✅ {$fieldName}\n";
    echo "   Position: ({$fieldData['x']}, {$fieldData['y']})\n";
    echo "   Size: {$fieldData['width']} x {$fieldData['height']}\n";
    echo "   Type: {$fieldData['type']}\n";
    if (isset($fieldData['original_name'])) {
        echo "   Original: {$fieldData['original_name']}\n";
    }
    if (isset($fieldData['mapped']) && $fieldData['mapped']) {
        echo "   Status: ✅ Mapped\n";
    }
    echo "\n";
}

// Compare with reference
echo "==========================================\n";
echo "Accuracy Comparison\n";
echo "==========================================\n";

$matches = 0;
$tolerance = 10; // 10 points tolerance

foreach ($reference as $refName => $refData) {
    if (isset($normalized[$refName])) {
        $normData = $normalized[$refName];
        $refX = (float)$refData['x'];
        $refY = (float)$refData['y'];
        $normX = (float)$normData['x'];
        $normY = (float)$normData['y'];
        
        $diffX = abs($refX - $normX);
        $diffY = abs($refY - $normY);
        
        if ($diffX <= $tolerance && $diffY <= $tolerance) {
            $matches++;
        }
    }
}

$accuracy = count($reference) > 0 ? ($matches / count($reference)) * 100 : 0;
echo "Reference fields matched: {$matches}/" . count($reference) . "\n";
echo "Position accuracy: " . number_format($accuracy, 1) . "%\n";
echo "Tolerance: {$tolerance} points\n\n";

if ($accuracy >= 70) {
    echo "✅ Mapping accuracy is GOOD\n";
} elseif ($accuracy >= 50) {
    echo "⚠️  Mapping accuracy is MODERATE\n";
} else {
    echo "❌ Mapping accuracy is LOW\n";
}

echo "\n";
echo "Test complete!\n";

