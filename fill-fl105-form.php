<?php
/**
 * Fill FL-105 form with test data
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/mvp/lib/pdf_form_filler.php';
require_once __DIR__ . '/mvp/lib/field_position_loader.php';

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="FL-105_filled.pdf"');

try {
    $data = json_decode($_POST['data'] ?? '{}', true);
    
    if (empty($data)) {
        throw new Exception('No data provided');
    }
    
    $templateId = 't_fl105';
    $pdfFile = __DIR__ . '/uploads/fl105.pdf';
    $outputFile = __DIR__ . '/output/FL-105_filled_' . time() . '.pdf';
    
    // Create output directory if it doesn't exist
    if (!is_dir(__DIR__ . '/output')) {
        mkdir(__DIR__ . '/output', 0755, true);
    }
    
    // Try to decrypt PDF first using qpdf
    $decryptedFile = __DIR__ . '/temp/fl105_decrypted_' . time() . '.pdf';
    $qpdfPath = __DIR__ . '/bin/qpdf/bin/qpdf.bat';
    
    if (file_exists($qpdfPath)) {
        $decryptCmd = escapeshellcmd($qpdfPath) . ' --decrypt ' . escapeshellarg($pdfFile) . ' ' . escapeshellarg($decryptedFile);
        $decryptResult = shell_exec($decryptCmd . ' 2>&1');
        
        if (file_exists($decryptedFile) && filesize($decryptedFile) > 0) {
            $pdfFile = $decryptedFile; // Use decrypted version
        }
    }
    
    $filler = new \WebPdfTimeSaver\Mvp\PdfFormFiller();
    $result = $filler->fillPdfFormWithPositions($templateId, $data, $pdfFile, $outputFile);
    
    if ($result && file_exists($outputFile)) {
        readfile($outputFile);
        unlink($outputFile); // Clean up
        
        // Clean up decrypted file if it was created
        if (isset($decryptedFile) && file_exists($decryptedFile)) {
            unlink($decryptedFile);
        }
    } else {
        throw new Exception('Failed to generate PDF');
    }
    
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
