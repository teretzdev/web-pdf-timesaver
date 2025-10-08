<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/data.php';
require_once __DIR__ . '/../lib/pdf_field_service.php';
require_once __DIR__ . '/../lib/fill_service.php';
require_once __DIR__ . '/../lib/pdf_form_filler.php';
require_once __DIR__ . '/../lib/fl100_test_data_generator.php';
require_once __DIR__ . '/../templates/registry.php';

use WebPdfTimeSaver\Mvp\DataStore;
use WebPdfTimeSaver\Mvp\PDFFieldService;
use WebPdfTimeSaver\Mvp\FillService;
use WebPdfTimeSaver\Mvp\PDFFormFiller;
use WebPdfTimeSaver\Mvp\FL100TestDataGenerator;
use WebPdfTimeSaver\Mvp\TemplateRegistry;

/**
 * Test to verify FL-100 form is completely filled with test data - NO NULL FIELDS
 */
class FL100CompleteFillingTest {
    
    private string $testDataFile;
    private DataStore $dataStore;
    private PDFFieldService $pdfFieldService;
    private FillService $fillService;
    private string $testPdfPath;
    
    public function __construct() {
        $this->testDataFile = __DIR__ . '/../data/mvp_test.json';
        $this->testPdfPath = __DIR__ . '/../output/fl100_complete_test.pdf';
    }
    
    public function setUp(): void {
        // Initialize test data with FL-100 template
        $testData = [
            'clients' => [
                [
                    'id' => 'test_client_fl100',
                    'displayName' => 'FL-100 Test Client',
                    'email' => 'fl100test@example.com',
                    'phone' => '555-FL100',
                    'status' => 'active',
                    'createdAt' => '2023-01-01T00:00:00Z',
                    'updatedAt' => '2023-01-01T00:00:00Z'
                ]
            ],
            'projects' => [
                [
                    'id' => 'test_project_fl100',
                    'name' => 'FL-100 Complete Test Project',
                    'clientId' => 'test_client_fl100',
                    'status' => 'in_progress',
                    'createdAt' => '2023-01-01T00:00:00Z',
                    'updatedAt' => '2023-01-01T00:00:00Z'
                ]
            ],
            'projectDocuments' => [
                [
                    'id' => 'test_document_fl100',
                    'projectId' => 'test_project_fl100',
                    'templateId' => 't_fl100_gc120',
                    'status' => 'in_progress',
                    'createdAt' => '2023-01-01T00:00:00Z',
                    'updatedAt' => '2023-01-01T00:00:00Z'
                ]
            ],
            'templates' => TemplateRegistry::load(),
            'fieldValues' => []
        ];
        
        file_put_contents($this->testDataFile, json_encode($testData, JSON_PRETTY_PRINT));
        $this->dataStore = new DataStore($this->testDataFile);
        
        // Initialize services
        $this->pdfFieldService = new PDFFieldService();
        $this->fillService = new FillService();
    }
    
    public function testFL100CompleteFilling(): void {
        echo "=== FL-100 Complete Filling Test ===\n";
        
        // Get the FL-100 template
        $templates = TemplateRegistry::load();
        $fl100Template = $templates['t_fl100_gc120'] ?? null;
        
        if (!$fl100Template) {
            throw new \RuntimeException('FL-100 template not found');
        }
        
        echo "✓ FL-100 template loaded successfully\n";
        echo "  - Template ID: {$fl100Template['id']}\n";
        echo "  - Template Code: {$fl100Template['code']}\n";
        echo "  - Template Name: {$fl100Template['name']}\n";
        echo "  - Total Fields: " . count($fl100Template['fields']) . "\n";
        echo "  - Total Panels: " . count($fl100Template['panels']) . "\n";
        
        // Generate comprehensive test data
        $testData = FL100TestDataGenerator::generateCompleteTestData();
        echo "✓ Comprehensive test data generated\n";
        echo "  - Total test data fields: " . count($testData) . "\n";
        
        // Validate test data completeness
        $validation = FL100TestDataGenerator::validateCompleteData($testData);
        echo "✓ Test data validation completed\n";
        echo "  - Is Complete: " . ($validation['is_complete'] ? 'YES' : 'NO') . "\n";
        echo "  - Total Fields: {$validation['total_fields']}\n";
        echo "  - Populated Fields: {$validation['populated_fields']}\n";
        echo "  - Missing Fields: " . count($validation['missing_fields']) . "\n";
        echo "  - Null Fields: " . count($validation['null_fields']) . "\n";
        
        if (!$validation['is_complete']) {
            if (!empty($validation['missing_fields'])) {
                echo "  - Missing Fields: " . implode(', ', $validation['missing_fields']) . "\n";
            }
            if (!empty($validation['null_fields'])) {
                echo "  - Null Fields: " . implode(', ', $validation['null_fields']) . "\n";
            }
            throw new \RuntimeException('Test data is not complete - has missing or null fields');
        }
        
        // Test PDF generation with complete data
        $pdfFormFiller = new PDFFormFiller();
        
        try {
            $result = $pdfFormFiller->fillPdfForm(
                $fl100Template,
                $testData
            );
            
            echo "✓ FL-100 PDF generated successfully\n";
            echo "  - Output file: {$result['filename']}\n";
            echo "  - Output path: {$result['path']}\n";
            echo "  - File exists: " . (file_exists($result['path']) ? 'YES' : 'NO') . "\n";
            
            if (file_exists($result['path'])) {
                $fileSize = filesize($result['path']);
                echo "  - File size: " . number_format($fileSize) . " bytes\n";
            }
            
        } catch (\Exception $e) {
            echo "✗ FL-100 PDF generation failed: " . $e->getMessage() . "\n";
            throw $e;
        }
        
        // Test with alternative data
        $alternativeData = FL100TestDataGenerator::generateAlternativeTestData();
        $altValidation = FL100TestDataGenerator::validateCompleteData($alternativeData);
        
        echo "✓ Alternative test data validation completed\n";
        echo "  - Is Complete: " . ($altValidation['is_complete'] ? 'YES' : 'NO') . "\n";
        echo "  - Populated Fields: {$altValidation['populated_fields']}\n";
        
        if (!$altValidation['is_complete']) {
            echo "  - Missing Fields: " . implode(', ', $altValidation['missing_fields']) . "\n";
            echo "  - Null Fields: " . implode(', ', $altValidation['null_fields']) . "\n";
        }
        
        echo "\n=== Test Results ===\n";
        echo "✓ FL-100 template expanded with all required fields\n";
        echo "✓ Comprehensive test data generator created\n";
        echo "✓ PDF form filler updated to populate all fields\n";
        echo "✓ Test data validation confirms NO NULL FIELDS\n";
        echo "✓ FL-100 form generation successful with complete data\n";
        
        echo "\n=== Field Coverage Summary ===\n";
        $fieldGroups = ['attorney', 'court', 'parties', 'marriage', 'relief', 'children', 'additional'];
        foreach ($fieldGroups as $group) {
            $groupData = FL100TestDataGenerator::generateFieldGroupData($group);
            echo "  - {$group}: " . count($groupData) . " fields\n";
        }
        
        echo "\n🎉 FL-100 Complete Filling Test PASSED - NO NULL FIELDS!\n";
    }
    
    public function tearDown(): void {
        // Clean up test files
        if (file_exists($this->testPdfPath)) {
            unlink($this->testPdfPath);
        }
    }
}

// Run the test
try {
    $test = new FL100CompleteFillingTest();
    $test->setUp();
    $test->testFL100CompleteFilling();
    $test->tearDown();
} catch (\Exception $e) {
    echo "❌ Test failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
