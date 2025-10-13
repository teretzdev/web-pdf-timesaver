<?php
// Test the universal processor endpoint
require_once __DIR__ . '/mvp/lib/pdf_field_extractor.php';

echo "Testing Universal Processor...\n\n";

// Test with W-9 file
$pdfFile = __DIR__ . '/uploads/w9.pdf';
if (!file_exists($pdfFile)) {
    echo "ERROR: W-9 file not found at $pdfFile\n";
    exit(1);
}

echo "1. Testing with W-9 PDF...\n";
$extractor = new \WebPdfTimeSaver\Mvp\PdfFieldExtractor();
try {
    $result = $extractor->extractAndGenerateBackgrounds(
        $pdfFile,
        'test_w9',
        __DIR__ . '/uploads'
    );
    
    echo "SUCCESS: Extracted " . count($result['fields']) . " fields\n";
    echo "Backgrounds: " . count($result['backgrounds']) . " generated\n";
    echo "Position file: " . ($result['positionFile'] ?? 'Not saved') . "\n\n";
    
    // Show first few fields
    if (!empty($result['fields'])) {
        echo "Sample fields:\n";
        foreach (array_slice($result['fields'], 0, 3) as $field) {
            echo "- {$field['name']}: {$field['type']} at ({$field['x']}, {$field['y']})\n";
        }
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n\n";

// Test with FL-100 file
$pdfFile2 = __DIR__ . '/uploads/fl100.pdf';
if (!file_exists($pdfFile2)) {
    echo "ERROR: FL-100 file not found at $pdfFile2\n";
    exit(1);
}

echo "2. Testing with FL-100 PDF...\n";
try {
    $result2 = $extractor->extractAndGenerateBackgrounds(
        $pdfFile2,
        'test_fl100',
        __DIR__ . '/uploads'
    );
    
    echo "SUCCESS: Extracted " . count($result2['fields']) . " fields\n";
    echo "Backgrounds: " . count($result2['backgrounds']) . " generated\n";
    echo "Position file: " . ($result2['positionFile'] ?? 'Not saved') . "\n\n";
    
    // Show first few fields
    if (!empty($result2['fields'])) {
        echo "Sample fields:\n";
        foreach (array_slice($result2['fields'], 0, 3) as $field) {
            echo "- {$field['name']}: {$field['type']} at ({$field['x']}, {$field['y']})\n";
        }
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n\n";

// Test coordinate conversion
echo "3. Testing coordinate conversion...\n";
$testCoords = [
    ['x' => 100, 'y' => 200, 'width' => 50, 'height' => 20],
    ['x' => 0, 'y' => 0, 'width' => 100, 'height' => 10]
];

foreach ($testCoords as $coord) {
    $converted = $extractor->parseRect($coord, 792); // Standard letter page height
    echo "Original: ({$coord['x']}, {$coord['y']}, {$coord['width']}, {$coord['height']})\n";
    echo "Converted: ({$converted['x']}, {$converted['y']}, {$converted['width']}, {$converted['height']})\n\n";
}

echo "Testing complete!\n";
?>
