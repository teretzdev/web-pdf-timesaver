<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../lib/data.php';
require_once __DIR__ . '/../lib/pdf_form_filler.php';
require_once __DIR__ . '/../lib/fl100_test_data_generator.php';
require_once __DIR__ . '/../templates/registry.php';

use WebPdfTimeSaver\Mvp\DataStore;
use WebPdfTimeSaver\Mvp\PdfFormFiller;
use WebPdfTimeSaver\Mvp\FL100TestDataGenerator;
use WebPdfTimeSaver\Mvp\TemplateRegistry;

echo "🎯 Testing ATTORNEY SECTION ONLY\n";
echo "================================\n\n";

// Initialize components
$data = new DataStore(__DIR__ . '/../data/mvp.json');
$pdfFiller = new PdfFormFiller();

// Get FL-100 template
$templates = TemplateRegistry::load();
$template = $templates['t_fl100_gc120'];

// Generate test data with ONLY attorney fields
$testData = [
    'attorney_name' => 'John Michael Smith, Esq.',
    'attorney_bar_number' => '123456',
    'attorney_firm' => 'Smith & Associates Law Firm',
    'attorney_address' => '123 Main Street, Suite 100',
    'attorney_city_state_zip' => 'Los Angeles, CA 90210',
    'attorney_phone' => '(555) 123-4567',
    'attorney_email' => 'jsmith@smithlaw.com'
];

echo "📋 Attorney Test Data:\n";
foreach ($testData as $field => $value) {
    echo "   {$field}: {$value}\n";
}

echo "\n📄 Generating PDF with ATTORNEY SECTION ONLY...\n";
$result = $pdfFiller->fillPdfForm($template, $testData);

if (isset($result['success']) && $result['success']) {
    echo "✅ PDF generated successfully: {$result['file']}\n";
    echo "📍 File path: {$result['path']}\n";
    echo "\n🔍 Please check the PDF to verify attorney field positioning:\n";
    echo "   - Attorney Name should be at (40, 50)\n";
    echo "   - Bar Number should be at (150, 50)\n";
    echo "   - Firm Name should be at (40, 60)\n";
    echo "   - Address should be at (40, 70)\n";
    echo "   - City/State/ZIP should be at (40, 80)\n";
    echo "   - Phone should be at (40, 90)\n";
    echo "   - Email should be at (120, 90)\n";
} else {
    echo "❌ Error generating PDF\n";
}

echo "\n🎯 Attorney Section Test Complete!\n";
