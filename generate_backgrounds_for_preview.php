<?php
/**
 * Generate background images for the already uploaded FL-110 PDF
 * This runs automatically - no user interaction needed
 */

require_once __DIR__ . '/mvp/lib/pdf_field_extractor.php';

use WebPdfTimeSaver\Mvp\PdfFieldExtractor;

$templateId = 'auto_1763401469202';
$projectRoot = __DIR__;
$pdfPath = $projectRoot . '/uploads/' . $templateId . '.pdf';
$uploadsDir = $projectRoot . '/uploads';

if (!file_exists($pdfPath)) {
    die("ERROR: PDF not found at: $pdfPath\n");
}

echo "Generating backgrounds for: $templateId\n";
echo "PDF Path: $pdfPath\n";
echo "Output Dir: $uploadsDir\n\n";

try {
    $extractor = new PdfFieldExtractor();
    
    // This will generate backgrounds even if fields already exist
    $result = $extractor->extractAndGenerateBackgrounds($pdfPath, $templateId, $uploadsDir);
    
    $backgrounds = $result['backgrounds'] ?? [];
    $fields = $result['fields'] ?? [];
    
    echo "SUCCESS!\n";
    echo "Fields: " . count($fields) . "\n";
    echo "Backgrounds generated: " . count($backgrounds) . "\n\n";
    
    foreach ($backgrounds as $pageNum => $bgPath) {
        if (file_exists($bgPath)) {
            echo "✓ Page $pageNum: " . basename($bgPath) . "\n";
        } else {
            echo "✗ Page $pageNum: NOT FOUND at $bgPath\n";
        }
    }
    
} catch (Exception $e) {
    die("ERROR: " . $e->getMessage() . "\n");
}


