<?php
/**
 * Direct test of Universal Processor logic
 */

require_once 'vendor/autoload.php';
require_once 'mvp/lib/pdf_field_extractor.php';

use WebPdfTimeSaver\Mvp\PdfFieldExtractor;
use Smalot\PdfParser\Parser;

echo "=== Universal Processor Test ===\n\n";

// Test with existing PDF
$testPdf = __DIR__ . '/uploads/fl100.pdf';

if (!file_exists($testPdf)) {
    echo "❌ Test PDF not found: $testPdf\n";
    exit(1);
}

echo "✅ Found test PDF: $testPdf\n";
echo "File size: " . filesize($testPdf) . " bytes\n\n";

// STEP 1: Check if PDF has fillable fields
echo "STEP 1: Detecting fillable fields...\n";
$parser = new Parser();
$hasFields = false;
$fieldCount = 0;

try {
    $pdf = $parser->parseFile($testPdf);
    $pages = $pdf->getPages();
    echo "  Pages: " . count($pages) . "\n";
    
    foreach ($pages as $pageNum => $page) {
        echo "  Page " . ($pageNum + 1) . ": ";
        $annotations = $page->get('Annots');
        if ($annotations) {
            $annotArray = $annotations->getContent();
            if (is_array($annotArray)) {
                $pageFieldCount = 0;
                foreach ($annotArray as $annot) {
                    if (is_object($annot) && $annot->get('T')) {
                        $fieldCount++;
                        $pageFieldCount++;
                        $hasFields = true;
                    }
                }
                echo "$pageFieldCount fields\n";
            } else {
                echo "No fields\n";
            }
        } else {
            echo "No annotations\n";
        }
    }
    
    echo "\n  Total fields detected: $fieldCount\n";
    echo "  Has fillable fields: " . ($hasFields ? 'YES' : 'NO') . "\n\n";
    
} catch (Exception $e) {
    echo "  ❌ Parser error: " . $e->getMessage() . "\n\n";
}

// STEP 2: Extract field positions
echo "STEP 2: Extracting field positions...\n";
try {
    $extractor = new PdfFieldExtractor();
    $result = $extractor->extractAndGenerateBackgrounds(
        $testPdf,
        'test_form',
        __DIR__ . '/uploads'
    );
    
    echo "  Fields extracted: " . count($result['fields']) . "\n";
    echo "  Backgrounds generated: " . $result['backgrounds'] . "\n";
    echo "  Position file: " . $result['positionFile'] . "\n\n";
    
    if (!empty($result['fields'])) {
        echo "STEP 3: Field details (first 5)...\n";
        $count = 0;
        foreach ($result['fields'] as $name => $field) {
            if ($count >= 5) break;
            echo "  - $name:\n";
            echo "    Type: {$field['type']}\n";
            echo "    Page: {$field['page']}\n";
            echo "    Position: X={$field['x']}, Y={$field['y']}\n";
            echo "    Size: W={$field['width']}, H={$field['height']}\n";
            $count++;
        }
    }
    
    echo "\n✅ SUCCESS! Universal processor is working!\n";
    
} catch (Exception $e) {
    echo "  ❌ Extraction error: " . $e->getMessage() . "\n";
    echo "  Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

