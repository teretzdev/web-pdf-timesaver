<?php
/**
 * Direct test of FL-110 extraction with the actual uploaded file
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/mvp/lib/pdf_field_extractor.php';

use WebPdfTimeSaver\Mvp\PdfFieldExtractor;

echo "=== Testing FL-110 Direct Extraction ===\n\n";

$pdfPath = 'C:/xampp/htdocs/Web-PDFTimeSaver/uploads/auto_1763401469202.pdf';
$templateId = 'auto_1763401469202';

if (!file_exists($pdfPath)) {
    die("ERROR: PDF not found: $pdfPath\n");
}

echo "PDF: $pdfPath\n";
echo "Template ID: $templateId\n";
echo "File exists: " . (file_exists($pdfPath) ? 'YES' : 'NO') . "\n\n";

$extractor = new PdfFieldExtractor();
$result = $extractor->extractAndGenerateBackgrounds($pdfPath, $templateId, 'C:/xampp/htdocs/Web-PDFTimeSaver/uploads');

echo "\n=== RESULTS ===\n";
echo "Fields extracted: " . count($result['fields'] ?? []) . "\n";
echo "Backgrounds: " . count($result['backgrounds'] ?? []) . "\n";
echo "Ensemble metadata: " . ($result['ensembleMetadata'] ? 'YES' : 'NO') . "\n";

if ($result['ensembleMetadata']) {
    echo "  Method: " . ($result['ensembleMetadata']['method'] ?? 'unknown') . "\n";
    echo "  Methods used: " . count($result['ensembleMetadata']['methodsUsed'] ?? []) . "\n";
}

if (!empty($result['fields'])) {
    echo "\nFields found:\n";
    $count = 0;
    foreach ($result['fields'] as $key => $field) {
        if ($count++ >= 5) break;
        echo "  - $key: " . ($field['type'] ?? 'unknown') . " at (" . ($field['x'] ?? 0) . ", " . ($field['y'] ?? 0) . ")\n";
    }
} else {
    echo "\n❌ NO FIELDS FOUND\n";
    
    // Check if details file exists
    $detailsFile = __DIR__ . '/data/' . $templateId . '_extraction_details.json';
    echo "\nChecking details file: $detailsFile\n";
    if (file_exists($detailsFile)) {
        echo "  ✅ Details file exists\n";
        $details = json_decode(file_get_contents($detailsFile), true);
        if ($details && isset($details['fields'])) {
            echo "  Fields in details file: " . count($details['fields']) . "\n";
        }
    } else {
        echo "  ❌ Details file does NOT exist\n";
        echo "  This means the Node.js extraction didn't run or failed\n";
    }
}

echo "\n=== Test Complete ===\n";

