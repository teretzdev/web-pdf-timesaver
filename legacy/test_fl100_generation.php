<?php
/**
 * Test FL-100 PDF generation using the existing MVP system
 */

declare(strict_types=1);

// Set up environment
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/mvp/';

// Test data
$testData = [
    'attorney_name' => 'John Michael Smith, Esq.',
    'attorney_firm' => 'Smith & Associates Family Law',
    'attorney_address' => '1234 Legal Plaza, Suite 500',
    'attorney_city_state_zip' => 'Los Angeles, CA 90210',
    'attorney_phone' => '(555) 123-4567',
    'attorney_email' => 'jsmith@smithlaw.com',
    'attorney_bar_number' => '123456',
    'case_number' => 'FL-2024-001234',
    'court_county' => 'Los Angeles',
    'court_address' => '111 N Hill St, Los Angeles, CA 90012',
    'petitioner_name' => 'Sarah Elizabeth Johnson',
    'respondent_name' => 'Michael David Johnson',
    'petitioner_address' => '123 Main Street, Los Angeles, CA 90210',
    'petitioner_phone' => '(555) 987-6543',
    'respondent_address' => '456 Oak Avenue, Los Angeles, CA 90211',
    'marriage_date' => '06/15/2010',
    'separation_date' => '03/20/2024',
    'marriage_location' => 'Las Vegas, Nevada',
    'grounds_for_dissolution' => 'Irreconcilable differences',
    'dissolution_type' => 'Dissolution of Marriage',
    'property_division' => '1',
    'spousal_support' => '1',
    'attorney_fees' => '1',
    'name_change' => '0',
    'has_children' => 'Yes',
    'children_count' => '2',
    'additional_info' => 'Request for temporary custody orders.',
    'attorney_signature' => 'John M. Smith',
    'signature_date' => '10/09/2025'
];

echo "Testing FL-100 PDF Generation\n";
echo "==============================\n\n";

// Include MVP system
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/mvp/lib/data.php';
require __DIR__ . '/mvp/templates/registry.php';
require __DIR__ . '/mvp/lib/fill_service.php';
require __DIR__ . '/mvp/lib/pdf_field_service.php';

use WebPdfTimeSaver\Mvp\DataStore;
use WebPdfTimeSaver\Mvp\TemplateRegistry;
use WebPdfTimeSaver\Mvp\FillService;
use WebPdfTimeSaver\Mvp\PdfFieldService;
use WebPdfTimeSaver\Mvp\Logger;

try {
    // Initialize services
    $store = new DataStore(__DIR__ . '/data/mvp.json');
    $templates = TemplateRegistry::load();
    $logger = new Logger();
    $fill = new FillService(__DIR__ . '/output', $logger);
    $pdfFieldService = new PdfFieldService();
    
    // Get FL-100 template
    $template = $templates['t_fl100_gc120'] ?? null;
    if (!$template) {
        die("ERROR: FL-100 template not found\n");
    }
    
    echo "✓ Template loaded: " . $template['name'] . "\n";
    echo "✓ Fields: " . count($template['fields']) . "\n";
    echo "✓ Test data: " . count($testData) . " values\n\n";
    
    // Generate PDF
    echo "Generating PDF...\n";
    $result = $fill->generateSimplePdf($template, $testData, ['test' => true]);
    
    if ($result && isset($result['filename'])) {
        $outputPath = __DIR__ . '/output/' . $result['filename'];
        if (file_exists($outputPath)) {
            $fileSize = filesize($outputPath);
            echo "✓ SUCCESS! PDF generated successfully\n";
            echo "  File: $outputPath\n";
            echo "  Size: " . number_format($fileSize) . " bytes\n";
            echo "  Filename: " . $result['filename'] . "\n\n";
            
            echo "Next steps:\n";
            echo "1. Open this PDF: $outputPath\n";
            echo "2. Go to http://draft.clio.com and fill FL-100 with same test data\n";
            echo "3. Download reference PDF as 'fl100_draft_clio_reference.pdf'\n";
            echo "4. Run: C:\\xampp\\php\\php.exe compare_fl100_pdfs.php\n";
        } else {
            echo "✗ ERROR: PDF file not found at expected location\n";
            echo "Result: " . print_r($result, true) . "\n";
        }
    } else {
        echo "✗ ERROR: PDF generation failed\n";
        echo "Result: " . print_r($result, true) . "\n";
    }
    
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
