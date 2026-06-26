<?php
/**
 * Field Mapping Summary Report
 * Shows which test data fields map to which PDF fields
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/lib/improved_field_mapper.php';
require_once __DIR__ . '/lib/field_position_loader.php';
require_once __DIR__ . '/lib/fl100_test_data_generator.php';

echo "=== FL-100 FIELD MAPPING SUMMARY ===\n\n";

$loader = new \WebPdfTimeSaver\Mvp\FieldPositionLoader();
$positions = $loader->loadFieldPositions('t_fl100_gc120');
$testData = \WebPdfTimeSaver\Mvp\Fl100TestDataGenerator::generateCompleteTestData();

echo "Total test data fields: " . count(array_filter($testData, fn($v) => !empty($v))) . "\n";
echo "Total extracted PDF fields: " . count($positions) . "\n\n";

$mappings = \WebPdfTimeSaver\Mvp\ImprovedFieldMapper::mapAllFields($testData, $positions);

echo "✅ MAPPED FIELDS (" . count($mappings) . "):\n";
echo str_repeat("=", 80) . "\n";
foreach ($mappings as $test => $pdf) {
    $pos = $positions[$pdf];
    $type = $pos['type'] ?? 'text';
    echo sprintf("%-30s -> %-50s (Type: %s, Page: %d)\n", 
        substr($test, 0, 30), 
        basename($pdf), 
        $type,
        $pos['page'] ?? 1
    );
}

echo "\n❌ UNMAPPED FIELDS (" . (count(array_filter($testData, fn($v) => !empty($v))) - count($mappings)) . "):\n";
echo str_repeat("=", 80) . "\n";
foreach ($testData as $test => $value) {
    if (empty($value)) continue;
    if (!isset($mappings[$test])) {
        echo sprintf("%-30s : %s\n", $test, substr($value, 0, 50));
        echo "   Reason: Field not found in PDF extraction\n";
        echo "   Possible causes:\n";
        echo "     - Field doesn't exist as form field in FL-100 PDF\n";
        echo "     - Field exists but wasn't extracted by pdf-lib\n";
        echo "     - Field needs to be mapped differently\n";
        echo "\n";
    }
}

echo "\n📊 STATISTICS:\n";
echo str_repeat("=", 80) . "\n";
$mappedCount = count($mappings);
$totalCount = count(array_filter($testData, fn($v) => !empty($v)));
$coverage = $totalCount > 0 ? round(($mappedCount / $totalCount) * 100, 1) : 0;
echo "Mapped: $mappedCount / $totalCount ($coverage%)\n";
echo "Unmapped: " . ($totalCount - $mappedCount) . " / $totalCount\n";

