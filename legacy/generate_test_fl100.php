<?php
/**
 * Generate FL-100 PDF with test data for comparison
 */

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/mvp/lib/pdf_form_filler.php';
require __DIR__ . '/mvp/lib/logger.php';

use WebPdfTimeSaver\Mvp\PdfFormFiller;
use WebPdfTimeSaver\Mvp\Logger;

// Test data from FL100_README.md
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

$logger = new Logger();
$filler = new PdfFormFiller(__DIR__ . '/output', __DIR__ . '/uploads', $logger);

// Load template from registry
require __DIR__ . '/mvp/templates/registry.php';
$templates = \WebPdfTimeSaver\Mvp\TemplateRegistry::load();
$template = $templates['t_fl100_gc120'];

$positionsFile = __DIR__ . '/data/t_fl100_gc120_positions.json';

echo "Generating FL-100 PDF with current system positions...\n";
echo "Template: FL-100 (Petition—Marriage/Domestic Partnership)\n";
echo "Positions: $positionsFile\n";
echo "Output: output/ directory\n\n";

if (!file_exists($positionsFile)) {
    die("ERROR: Positions file not found at $positionsFile\n");
}

try {
    $result = $filler->fillPdfFormWithPositions(
        $template,
        $testData,
        't_fl100_gc120'
    );
    
    if ($result && isset($result['path']) && file_exists($result['path'])) {
        $fileSize = filesize($result['path']);
        echo "✓ SUCCESS! PDF generated successfully\n";
        echo "  File: " . $result['path'] . "\n";
        echo "  Size: " . number_format($fileSize) . " bytes\n\n";
        
        echo "Next steps:\n";
        echo "1. Open this PDF: " . $result['path'] . "\n";
        echo "2. Go to http://draft.clio.com and fill FL-100 with same test data\n";
        echo "3. Download reference PDF as 'fl100_draft_clio_reference.pdf'\n";
        echo "4. Run: C:\\xampp\\php\\php.exe compare_fl100_pdfs.php\n";
    } else {
        echo "✗ ERROR: PDF generation failed\n";
        echo "Result: " . print_r($result, true) . "\n";
        if (file_exists(__DIR__ . '/logs/pdf_debug.log')) {
            echo "\nCheck logs/pdf_debug.log for details\n";
        }
    }
} catch (Exception $e) {
    echo "✗ ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

