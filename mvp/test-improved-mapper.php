<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/lib/improved_field_mapper.php';
require_once __DIR__ . '/lib/field_position_loader.php';
require_once __DIR__ . '/lib/fl100_test_data_generator.php';

$loader = new \WebPdfTimeSaver\Mvp\FieldPositionLoader();
$positions = $loader->loadFieldPositions('t_fl100_gc120');
$testData = \WebPdfTimeSaver\Mvp\Fl100TestDataGenerator::generateCompleteTestData();

echo "=== TESTING IMPROVED FIELD MAPPER ===\n\n";
echo "Total positions: " . count($positions) . "\n";
echo "Total test data fields: " . count(array_filter($testData, fn($v) => !empty($v))) . "\n\n";

$mappings = \WebPdfTimeSaver\Mvp\ImprovedFieldMapper::mapAllFields($testData, $positions);

echo "✅ Mapped Fields (" . count($mappings) . "):\n";
foreach ($mappings as $test => $pdf) {
    $pos = $positions[$pdf];
    echo "  $test -> " . basename($pdf) . " (Page {$pos['page']}, X:{$pos['x']}, Y:{$pos['y']})\n";
}

echo "\n❌ Unmapped Fields:\n";
foreach ($testData as $test => $value) {
    if (empty($value)) continue;
    if (!isset($mappings[$test])) {
        echo "  - $test: $value\n";
        // Find similar fields
        $normalized = strtolower(str_replace(['_', '-', ' '], '', $test));
        $parts = preg_split('/[^a-z0-9]+/', $normalized);
        echo "    Looking for fields containing: " . implode(', ', array_slice($parts, 0, 3)) . "\n";
        $similar = [];
        foreach (array_keys($positions) as $pdfField) {
            $pdfNormalized = strtolower(str_replace(['_', '-', ' ', '[', ']', '.'], '', $pdfField));
            foreach ($parts as $part) {
                if (strlen($part) > 3 && strpos($pdfNormalized, $part) !== false) {
                    $similar[] = basename($pdfField);
                    if (count($similar) >= 3) break 2;
                }
            }
        }
        if (!empty($similar)) {
            echo "    Similar fields found: " . implode(', ', array_unique($similar)) . "\n";
        }
    }
}

