<?php
require_once 'vendor/autoload.php';
require_once 'mvp/lib/logger.php';
require_once 'mvp/lib/fl100_test_data_generator.php';
require_once 'mvp/lib/pdf_form_filler.php';

use WebPdfTimeSaver\Mvp\PdfFormFiller;
use WebPdfTimeSaver\Mvp\FL100TestDataGenerator;
use WebPdfTimeSaver\Mvp\Logger;

echo "=== Verifying Improved Field Positions Using Existing Codebase ===\n\n";

// Initialize the PDF form filler
$pdfFiller = new PdfFormFiller();

// Generate test data
$testData = FL100TestDataGenerator::generateCompleteTestData();
echo "✓ Generated test data with " . count($testData) . " fields\n";

// Create template configuration
$template = [
    'id' => 'fl100_improved_test',
    'name' => 'FL-100 Improved Positions Test',
    'pageCount' => 3,
    'fields' => []
];

echo "✓ Template configured for 3 pages\n";

// Use the existing fillPdfFormWithPositions method
echo "✓ Using existing fillPdfFormWithPositions method...\n";

try {
    $result = $pdfFiller->fillPdfFormWithPositions($template, $testData, 't_fl100_gc120');
    
    if ($result['success']) {
        echo "✓ PDF generated successfully!\n";
        echo "✓ Filename: " . $result['filename'] . "\n";
        echo "✓ Path: " . $result['path'] . "\n";
        
        // Verify the file exists and get size
        if (file_exists($result['path'])) {
            $fileSize = filesize($result['path']);
            echo "✓ File size: " . number_format($fileSize) . " bytes\n";
            
            // Copy to a more accessible location for viewing
            $viewablePath = 'improved_fl100_verification.pdf';
            copy($result['path'], $viewablePath);
            echo "✓ Copied to: $viewablePath\n";
            
        } else {
            echo "✗ Generated file not found at: " . $result['path'] . "\n";
        }
    } else {
        echo "✗ PDF generation failed\n";
        if (isset($result['error'])) {
            echo "Error: " . $result['error'] . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "✗ Exception: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Verification Complete ===\n";
echo "This PDF was generated using the existing codebase with the improved field positions.\n";
echo "It should show the FL-100 form with fields properly distributed across pages.\n";
?>
