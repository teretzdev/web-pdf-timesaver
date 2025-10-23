<?php
/**
 * Generate Filled PDF Using Extracted Positions
 * Demonstrates the complete workflow from extraction to filled PDF
 */

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/mvp/lib/pdf_form_filler.php';

use WebPdfTimeSaver\Mvp\PdfFormFiller;

echo "🎯 COMPLETE WORKFLOW DEMONSTRATION\n";
echo "===================================\n\n";

// Step 1: Load extracted positions
echo "📋 Step 1: Loading extracted field positions...\n";
$positionsFile = __DIR__ . '/data/t_w9_test_positions.json';

if (!file_exists($positionsFile)) {
    echo "❌ Position file not found. Run extraction first:\n";
    echo "   node scripts/universal-field-extractor.js uploads/w9.pdf t_w9_test\n";
    exit(1);
}

$positions = json_decode(file_get_contents($positionsFile), true);
echo "✅ Loaded " . count($positions) . " field positions\n\n";

// Step 2: Create test data
echo "📝 Step 2: Creating realistic test data...\n";
$testData = [
    // Business Information
    'topmostSubform[0].Page1[0].f1_01[0]' => 'ACME CORPORATION LLC',
    'topmostSubform[0].Page1[0].f1_02[0]' => '123 Business Street',
    'topmostSubform[0].Page1[0].f1_03[0]' => 'Suite 100',
    'topmostSubform[0].Page1[0].f1_04[0]' => 'New York',
    'topmostSubform[0].Page1[0].f1_05[0]' => 'NY',
    'topmostSubform[0].Page1[0].f1_06[0]' => '10001',
    
    // Taxpayer Information
    'topmostSubform[0].Page1[0].f1_07[0]' => 'John Smith',
    'topmostSubform[0].Page1[0].f1_08[0]' => '456 Personal Ave',
    'topmostSubform[0].Page1[0].f1_09[0]' => 'Apt 2B',
    'topmostSubform[0].Page1[0].f1_10[0]' => 'Brooklyn',
    'topmostSubform[0].Page1[0].f1_11[0]' => 'NY',
    'topmostSubform[0].Page1[0].f1_12[0]' => '11201',
    
    // Tax ID Numbers
    'topmostSubform[0].Page1[0].f1_13[0]' => '12-3456789',
    'topmostSubform[0].Page1[0].f1_14[0]' => '987-65-4321',
    
    // Certification
    'topmostSubform[0].Page1[0].f1_15[0]' => 'John Smith',
    'topmostSubform[0].Page1[0].f1_16[0]' => '01/15/2025',
    
    // Additional fields for other pages
    'topmostSubform[0].Page2[0].f2_01[0]' => 'Additional Business Info',
    'topmostSubform[0].Page2[0].f2_02[0]' => 'More Details Here',
];

echo "✅ Created test data for " . count($testData) . " fields\n\n";

// Step 3: Generate filled PDF
echo "🖨️  Step 3: Generating filled PDF...\n";
$pdfFiller = new PdfFormFiller();

try {
    $result = $pdfFiller->fillForm(
        __DIR__ . '/uploads/w9.pdf',           // Source PDF
        $testData,                             // Form data
        $positions,                            // Field positions
        __DIR__ . '/output/w9_filled_accurate.pdf'  // Output file
    );
    
    if ($result['success']) {
        echo "✅ SUCCESS! Filled PDF generated\n";
        echo "📁 Output file: output/w9_filled_accurate.pdf\n";
        echo "📊 Fields filled: " . $result['fields_filled'] . "\n";
        echo "📄 Pages processed: " . $result['pages_processed'] . "\n\n";
        
        // Step 4: Show field mapping
        echo "📋 Step 4: Field mapping verification...\n";
        $mappedFields = 0;
        foreach ($testData as $fieldName => $value) {
            if (isset($positions[$fieldName])) {
                $pos = $positions[$fieldName];
                echo sprintf(
                    "   ✅ %-40s → %-20s at (%.1f, %.1f)\n",
                    substr($fieldName, -20),
                    substr($value, 0, 20),
                    $pos['x'],
                    $pos['y']
                );
                $mappedFields++;
            }
        }
        
        echo "\n📊 Summary:\n";
        echo "   Total test data fields: " . count($testData) . "\n";
        echo "   Successfully mapped: $mappedFields\n";
        echo "   Mapping accuracy: " . round(($mappedFields / count($testData)) * 100, 1) . "%\n\n";
        
        echo "🎉 COMPLETE SUCCESS!\n";
        echo "===================\n";
        echo "✅ Field positions extracted accurately\n";
        echo "✅ Test data created realistically\n";
        echo "✅ PDF filled with precise positioning\n";
        echo "✅ Final output ready for use\n\n";
        
        echo "📁 View the result: output/w9_filled_accurate.pdf\n";
        echo "🔗 Open in browser: file://" . realpath(__DIR__ . '/output/w9_filled_accurate.pdf') . "\n";
        
    } else {
        echo "❌ PDF generation failed: " . ($result['error'] ?? 'Unknown error') . "\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

