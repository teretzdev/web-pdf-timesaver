<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../mvp/lib/data.php';
require_once __DIR__ . '/../mvp/lib/pdf_form_filler.php';
require_once __DIR__ . '/../mvp/lib/fl100_test_data_generator.php';
require_once __DIR__ . '/../mvp/lib/field_position_loader.php';
require_once __DIR__ . '/../mvp/templates/registry.php';

use WebPdfTimeSaver\Mvp\DataStore;
use WebPdfTimeSaver\Mvp\PdfFormFiller;
use WebPdfTimeSaver\Mvp\FL100TestDataGenerator;
use WebPdfTimeSaver\Mvp\FieldPositionLoader;
use WebPdfTimeSaver\Mvp\TemplateRegistry;

echo "🎯 Testing Field Position Editor Integration\n";
echo "==========================================\n\n";

// Initialize components
$data = new DataStore(__DIR__ . '/../data/mvp.json');
$pdfFiller = new PdfFormFiller();
$positionLoader = new FieldPositionLoader();

// Get FL-100 template
$templates = TemplateRegistry::load();
$template = $templates['t_fl100_gc120'];
if (!$template) {
    echo "❌ Error: FL-100 template not found\n";
    exit(1);
}

echo "✅ FL-100 template loaded\n";

// Generate test data
$testData = FL100TestDataGenerator::generateCompleteTestData();
echo "✅ Test data generated with " . count($testData) . " fields\n";

// Check if positions exist
if ($positionLoader->hasPositions('t_fl100_gc120')) {
    echo "✅ Field positions found - using positioned layout\n";
    
    // Load positions
    $positions = $positionLoader->loadFieldPositions('t_fl100_gc120');
    echo "📋 Loaded " . count($positions) . " field positions\n";
    
    // Generate PDF with positioned fields
    $result = $pdfFiller->fillPdfFormWithPositions($template, $testData, 't_fl100_gc120');
    
    if ($result['success']) {
        echo "✅ PDF generated with positioned fields: " . $result['file'] . "\n";
        echo "📍 Used " . $result['used_positions'] . " positioned fields\n";
    } else {
        echo "❌ Error generating positioned PDF\n";
    }
} else {
    echo "⚠️  No field positions found - using default layout\n";
    
    // Generate PDF with default positioning
    $result = $pdfFiller->fillPdfForm($template, $testData);
    
    if (isset($result['success']) && $result['success']) {
        echo "✅ PDF generated with default positioning: " . $result['file'] . "\n";
    } else {
        echo "❌ Error generating PDF\n";
        if (isset($result['error'])) {
            echo "   Error: {$result['error']}\n";
        }
    }
}

echo "\n🌐 Field Editor Web Interface:\n";
echo "   URL: http://localhost:3001\n";
echo "   Features:\n";
echo "   - Drag and drop field positioning\n";
echo "   - Save/load field positions\n";
echo "   - Export/import configurations\n";
echo "   - Grid overlay for precise positioning\n";
echo "   - Zoom controls for detailed placement\n";

echo "\n📝 Instructions:\n";
echo "1. Open http://localhost:3001 in your browser\n";
echo "2. Drag fields from the left panel to position them on the form\n";
echo "3. Click and drag positioned fields to fine-tune their placement\n";
echo "4. Use the grid overlay for precise positioning\n";
echo "5. Save your positions when satisfied\n";
echo "6. Run this test again to generate PDF with your custom positions\n";

echo "\n🎯 Field Position Editor Test Complete!\n";
