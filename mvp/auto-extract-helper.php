<?php
/**
 * Helper page to auto-trigger field extraction
 * This simulates the form submission for browser automation
 */

// Check if file exists
$pdfFile = __DIR__ . '/../uploads/fl100.pdf';
if (!file_exists($pdfFile)) {
    die('Error: fl100.pdf not found at ' . $pdfFile);
}

$templateId = 't_fl100_gc120';

// Copy file to expected location
$targetFile = __DIR__ . '/../uploads/' . $templateId . '.pdf';
copy($pdfFile, $targetFile);

// Now trigger extraction
require_once __DIR__ . '/lib/pdf_field_extractor.php';

try {
    $extractor = new \WebPdfTimeSaver\Mvp\PdfFieldExtractor();
    $result = $extractor->extractAndGenerateBackgrounds(
        $targetFile,
        $templateId,
        __DIR__ . '/../uploads'
    );
    
    $fields = $result['fields'] ?? [];
    $backgrounds = $result['backgrounds'] ?? [];
    $positionFile = $result['positionFile'] ?? null;
    
    // Redirect to the extract fields page with success message
    $message = urlencode('Successfully extracted ' . count($fields) . ' fields and generated ' . count($backgrounds) . ' background images.');
    header('Location: ?route=extract-fields&success=' . $message);
    exit;
    
} catch (\Exception $e) {
    $error = urlencode('Error: ' . $e->getMessage());
    header('Location: ?route=extract-fields&error=' . $error);
    exit;
}
