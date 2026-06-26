<?php
/**
 * Generate a filled FL-100 PDF with test data using corrected positions
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/lib/pdf_form_filler.php';
require_once __DIR__ . '/lib/fl100_test_data_generator.php';
require_once __DIR__ . '/lib/field_position_loader.php';

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "=== GENERATING FILLED FL-100 PDF ===\n\n";

try {
    // Create minimal template structure
    $template = [
        'id' => 't_fl100_gc120',
        'code' => 'fl100',
        'name' => 'FL-100 Petition',
        'pageCount' => 3
    ];
    
    echo "✅ Template: " . $template['id'] . "\n";
    echo "   Pages: " . $template['pageCount'] . "\n\n";
    
    // Generate test data
    echo "📝 Generating test data...\n";
    $testData = \WebPdfTimeSaver\Mvp\Fl100TestDataGenerator::generateCompleteTestData();
    echo "✅ Generated " . count($testData) . " test data fields\n\n";
    
    // Show sample data
    echo "Sample test data:\n";
    $sampleFields = ['attorney_name', 'attorney_firm', 'case_number', 'petitioner_name', 'respondent_name', 'separation_date'];
    foreach ($sampleFields as $field) {
        if (isset($testData[$field]) && !empty($testData[$field])) {
            echo "  - $field: " . $testData[$field] . "\n";
        }
    }
    echo "\n";
    
    // Fill PDF
    echo "📄 Filling PDF with positions...\n";
    $filler = new \WebPdfTimeSaver\Mvp\PdfFormFiller();
    $result = $filler->fillPdfFormWithPositions($template, $testData, 't_fl100_gc120');
    
    if (!isset($result['path']) || !file_exists($result['path'])) {
        throw new \Exception("Failed to generate PDF - file not found");
    }
    
    echo "✅ PDF generated successfully!\n";
    echo "   File: " . $result['path'] . "\n";
    echo "   Filename: " . $result['filename'] . "\n";
    if (isset($result['fields_placed'])) {
        echo "   Fields placed: " . $result['fields_placed'] . "\n";
    }
    if (isset($result['pages'])) {
        echo "   Pages: " . $result['pages'] . "\n";
    }
    echo "\n";
    
    // Convert to web-accessible path
    $webPath = str_replace('\\', '/', $result['path']);
    $webPath = str_replace($_SERVER['DOCUMENT_ROOT'] ?? '', '', $webPath);
    $webPath = ltrim($webPath, '/');
    
    // If we're in a subdirectory, adjust path
    $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    if ($scriptDir && $scriptDir !== '/') {
        $webPath = $scriptDir . '/' . $webPath;
    }
    
    $fullUrl = 'http://localhost/' . $webPath;
    if (isset($_SERVER['HTTP_HOST'])) {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $fullUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/' . $webPath;
    }
    
    echo "🌐 Web URL: $fullUrl\n";
    echo "📁 Full path: " . realpath($result['path']) . "\n\n";
    
    // Output JSON for browser to read
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'pdf_path' => $result['path'],
        'pdf_filename' => $result['filename'],
        'web_url' => $fullUrl,
        'fields_placed' => $result['fields_placed'] ?? 0,
        'pages' => $result['pages'] ?? 1
    ], JSON_PRETTY_PRINT);
    
} catch (\Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
    echo "\n\nERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

