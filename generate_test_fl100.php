<?php
// Generate a test FL-100 PDF for comparison with Clio output

require_once __DIR__ . '/mvp/lib/data.php';
require_once __DIR__ . '/mvp/templates/registry.php';
require_once __DIR__ . '/mvp/lib/fl100_test_data_generator.php';
require_once __DIR__ . '/mvp/lib/fill_service.php';
require_once __DIR__ . '/mvp/lib/logger.php';

use WebPdfTimeSaver\Mvp\DataStore;
use WebPdfTimeSaver\Mvp\TemplateRegistry;
use WebPdfTimeSaver\Mvp\FL100TestDataGenerator;
use WebPdfTimeSaver\Mvp\FillService;
use WebPdfTimeSaver\Mvp\Logger;

echo "Generating test FL-100 PDF...\n";

// Initialize services
$logger = new Logger();
$store = new DataStore(__DIR__ . '/data/test_comparison.json');
$templates = TemplateRegistry::load();
$fill = new FillService(__DIR__ . '/output', $logger);

// Get FL-100 template
$fl100Template = $templates['t_fl100_gc120'] ?? null;
if (!$fl100Template) {
    die("ERROR: FL-100 template not found!\n");
}

// Generate complete test data
$testData = FL100TestDataGenerator::generateCompleteTestData();
echo "Generated test data with " . count($testData) . " fields\n";

// Create a test project and document
$project = $store->createProject('FL-100 PDF Comparison Test');
$document = $store->addProjectDocument($project['id'], 't_fl100_gc120');
echo "Created test project and document\n";

// Save the test data
$store->saveFieldValues($document['id'], $testData);
echo "Saved test data to document\n";

// Generate the PDF
try {
    $result = $fill->generateSimplePdf($fl100Template, $testData, [
        'pdId' => $document['id'],
        'filename_prefix' => 'our_system_fl100_test'
    ]);
    
    echo "\n✅ SUCCESS! PDF generated:\n";
    echo "   File: " . $result['filename'] . "\n";
    echo "   Path: " . $result['path'] . "\n";
    
    // Create comparison info file
    $comparisonInfo = [
        'generated_at' => date('Y-m-d H:i:s'),
        'our_system_pdf' => $result['path'],
        'test_data_used' => $testData,
        'template' => 't_fl100_gc120',
        'document_id' => $document['id'],
        'project_id' => $project['id']
    ];
    
    file_put_contents(__DIR__ . '/output/fl100_comparison_info.json', json_encode($comparisonInfo, JSON_PRETTY_PRINT));
    echo "\nComparison info saved to: output/fl100_comparison_info.json\n";
    
} catch (Exception $e) {
    die("ERROR generating PDF: " . $e->getMessage() . "\n");
}

// Clean up test data file
@unlink(__DIR__ . '/data/test_comparison.json');

echo "\n📋 Test data used:\n";
echo "=====================================\n";
foreach ($testData as $key => $value) {
    if (is_string($value) && !empty($value)) {
        echo sprintf("%-30s: %s\n", $key, $value);
    }
}

echo "\n🔍 To compare with Clio PDF:\n";
echo "1. Upload a Clio-generated FL-100 PDF to /workspace/uploads/\n";
echo "2. Our system PDF is at: " . $result['path'] . "\n";
echo "3. Use the PDF comparison script to analyze differences\n";