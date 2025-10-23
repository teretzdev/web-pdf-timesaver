<?php
/**
 * Generate test background images
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/mvp/lib/pdf_field_extractor.php';

echo "Generating test background images...\n\n";

$extractor = new \WebPdfTimeSaver\Mvp\PdfFieldExtractor();

// Generate W-9 backgrounds
echo "1. Generating W-9 backgrounds...\n";
try {
    $result = $extractor->extractAndGenerateBackgrounds(
        __DIR__ . '/uploads/w9.pdf',
        'test_w9',
        __DIR__ . '/uploads'
    );
    echo "   ✅ Generated " . count($result['backgrounds']) . " backgrounds\n";
    echo "   ✅ Extracted " . count($result['fields']) . " fields\n";
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// Generate FL-100 backgrounds
echo "\n2. Generating FL-100 backgrounds...\n";
try {
    $result = $extractor->extractAndGenerateBackgrounds(
        __DIR__ . '/uploads/fl100.pdf',
        'test_fl100',
        __DIR__ . '/uploads'
    );
    echo "   ✅ Generated " . count($result['backgrounds']) . " backgrounds\n";
    echo "   ✅ Extracted " . count($result['fields']) . " fields\n";
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

echo "\n✅ Background generation complete!\n";
?>
