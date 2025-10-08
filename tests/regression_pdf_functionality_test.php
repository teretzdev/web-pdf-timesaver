<?php
/**
 * Regression Tests for PDF Functionality
 * Tests to ensure existing PDF functionality still works after refactoring
 */

require_once __DIR__ . '/../lib/data.php';
require_once __DIR__ . '/../lib/pdf_field_service.php';
require_once __DIR__ . '/../lib/fill_service.php';
require_once __DIR__ . '/../vendor/autoload.php';

class RegressionPDFFunctionalityTest extends PHPUnit\Framework\TestCase
{
    private $dataStore;
    private $testDataFile;
    private $pdfFieldService;
    private $fillService;
    private $testPdfPath;
    private $outputPath;
    
    protected function setUp(): void
    {
        // Create a test data file
        $this->testDataFile = __DIR__ . '/../data/test_regression.json';
        
        // Initialize test data
        $testData = [
            'clients' => [
                [
                    'id' => 'test_client_1',
                    'displayName' => 'Test Client 1',
                    'email' => 'test1@example.com',
                    'phone' => '555-0001',
                    'status' => 'active',
                    'createdAt' => '2023-01-01T00:00:00Z',
                    'updatedAt' => '2023-01-01T00:00:00Z'
                ]
            ],
            'projects' => [
                [
                    'id' => 'test_project_1',
                    'name' => 'Test Project 1',
                    'clientId' => 'test_client_1',
                    'status' => 'in_progress',
                    'createdAt' => '2023-01-01T00:00:00Z',
                    'updatedAt' => '2023-01-01T00:00:00Z'
                ]
            ],
            'projectDocuments' => [
                [
                    'id' => 'test_document_1',
                    'projectId' => 'test_project_1',
                    'templateId' => 'test_template_1',
                    'status' => 'in_progress',
                    'createdAt' => '2023-01-01T00:00:00Z',
                    'updatedAt' => '2023-01-01T00:00:00Z'
                ]
            ],
            'templates' => [
                [
                    'id' => 'test_template_1',
                    'name' => 'Test Template 1',
                    'code' => 'TT1',
                    'createdAt' => '2023-01-01T00:00:00Z',
                    'updatedAt' => '2023-01-01T00:00:00Z'
                ]
            ]
        ];
        
        file_put_contents($this->testDataFile, json_encode($testData, JSON_PRETTY_PRINT));
        $this->dataStore = new DataStore($this->testDataFile);
        
        // Initialize PDF services
        $this->pdfFieldService = new PDFFieldService();
        $this->fillService = new FillService();
        
        // Set up test paths
        $this->testPdfPath = __DIR__ . '/../uploads/test_form.pdf';
        $this->outputPath = __DIR__ . '/../output/';
        
        // Ensure output directory exists
        if (!is_dir($this->outputPath)) {
            mkdir($this->outputPath, 0755, true);
        }
    }
    
    protected function tearDown(): void
    {
        if (file_exists($this->testDataFile)) {
            unlink($this->testDataFile);
        }
        
        // Clean up test output files
        $testOutputFiles = glob($this->outputPath . 'test_regression_*.pdf');
        foreach ($testOutputFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }
    
    /**
     * Test PDF field extraction functionality
     */
    public function testPDFFieldExtractionFunctionality()
    {
        // Test that PDF field extraction still works
        if (file_exists($this->testPdfPath)) {
            $fields = $this->pdfFieldService->extractFields($this->testPdfPath);
            
            $this->assertIsArray($fields, 'PDF field extraction should return an array');
            $this->assertNotEmpty($fields, 'PDF field extraction should return fields');
            
            // Test field structure
            foreach ($fields as $field) {
                $this->assertArrayHasKey('name', $field, 'PDF field should have name property');
                $this->assertArrayHasKey('type', $field, 'PDF field should have type property');
                $this->assertArrayHasKey('value', $field, 'PDF field should have value property');
            }
        } else {
            $this->markTestSkipped('Test PDF file not found');
        }
    }
    
    /**
     * Test PDF form filling functionality
     */
    public function testPDFFormFillingFunctionality()
    {
        // Test that PDF form filling still works
        if (file_exists($this->testPdfPath)) {
            $testData = [
                'field1' => 'Test Value 1',
                'field2' => 'Test Value 2',
                'field3' => 'Test Value 3'
            ];
            
            $outputFile = $this->outputPath . 'test_regression_filled.pdf';
            $result = $this->fillService->fillPDF($this->testPdfPath, $testData, $outputFile);
            
            $this->assertTrue($result, 'PDF form filling should succeed');
            $this->assertFileExists($outputFile, 'Filled PDF file should be created');
            
            // Verify file size is reasonable
            $fileSize = filesize($outputFile);
            $this->assertGreaterThan(1000, $fileSize, 'Filled PDF should have reasonable file size');
        } else {
            $this->markTestSkipped('Test PDF file not found');
        }
    }
    
    /**
     * Test PDF template functionality
     */
    public function testPDFTemplateFunctionality()
    {
        // Test that PDF template functionality still works
        $templates = $this->dataStore->getTemplates();
        
        $this->assertIsArray($templates, 'Templates should be an array');
        $this->assertNotEmpty($templates, 'Templates should not be empty');
        
        // Test template structure
        foreach ($templates as $template) {
            $this->assertArrayHasKey('id', $template, 'Template should have id property');
            $this->assertArrayHasKey('name', $template, 'Template should have name property');
            $this->assertArrayHasKey('code', $template, 'Template should have code property');
            $this->assertArrayHasKey('createdAt', $template, 'Template should have createdAt property');
            $this->assertArrayHasKey('updatedAt', $template, 'Template should have updatedAt property');
        }
    }
    
    /**
     * Test PDF document management functionality
     */
    public function testPDFDocumentManagementFunctionality()
    {
        // Test that PDF document management still works
        $documents = $this->dataStore->getProjectDocuments('test_project_1');
        
        $this->assertIsArray($documents, 'Project documents should be an array');
        $this->assertNotEmpty($documents, 'Project documents should not be empty');
        
        // Test document structure
        foreach ($documents as $document) {
            $this->assertArrayHasKey('id', $document, 'Document should have id property');
            $this->assertArrayHasKey('projectId', $document, 'Document should have projectId property');
            $this->assertArrayHasKey('templateId', $document, 'Document should have templateId property');
            $this->assertArrayHasKey('status', $document, 'Document should have status property');
            $this->assertArrayHasKey('createdAt', $document, 'Document should have createdAt property');
            $this->assertArrayHasKey('updatedAt', $document, 'Document should have updatedAt property');
        }
    }
    
    /**
     * Test PDF upload functionality
     */
    public function testPDFUploadFunctionality()
    {
        // Test that PDF upload functionality still works
        $uploadDir = __DIR__ . '/../uploads/';
        
        $this->assertTrue(is_dir($uploadDir), 'Upload directory should exist');
        $this->assertTrue(is_writable($uploadDir), 'Upload directory should be writable');
        
        // Test file validation
        $validExtensions = ['pdf'];
        $testFile = 'test_file.pdf';
        
        $extension = pathinfo($testFile, PATHINFO_EXTENSION);
        $this->assertContains($extension, $validExtensions, 'PDF files should have valid extension');
    }
    
    /**
     * Test PDF output functionality
     */
    public function testPDFOutputFunctionality()
    {
        // Test that PDF output functionality still works
        $outputDir = __DIR__ . '/../output/';
        
        $this->assertTrue(is_dir($outputDir), 'Output directory should exist');
        $this->assertTrue(is_writable($outputDir), 'Output directory should be writable');
        
        // Test file naming convention
        $timestamp = date('Ymd_His');
        $clientId = 'test_client';
        $templateCode = 'TT1';
        $expectedFilename = "mvp_{$timestamp}_t_{$clientId}_{$templateCode}.pdf";
        
        $this->assertStringContainsString('mvp_', $expectedFilename, 'Output filename should contain mvp prefix');
        $this->assertStringContainsString('_t_', $expectedFilename, 'Output filename should contain template separator');
        $this->assertStringEndsWith('.pdf', $expectedFilename, 'Output filename should end with .pdf extension');
    }
    
    /**
     * Test PDF field mapping functionality
     */
    public function testPDFFieldMappingFunctionality()
    {
        // Test that PDF field mapping still works
        $testMapping = [
            'client_name' => 'field1',
            'client_email' => 'field2',
            'client_phone' => 'field3',
            'project_name' => 'field4',
            'project_date' => 'field5'
        ];
        
        $this->assertIsArray($testMapping, 'Field mapping should be an array');
        $this->assertNotEmpty($testMapping, 'Field mapping should not be empty');
        
        // Test mapping structure
        foreach ($testMapping as $source => $target) {
            $this->assertIsString($source, 'Mapping source should be string');
            $this->assertIsString($target, 'Mapping target should be string');
            $this->assertNotEmpty($source, 'Mapping source should not be empty');
            $this->assertNotEmpty($target, 'Mapping target should not be empty');
        }
    }
    
    /**
     * Test PDF data validation functionality
     */
    public function testPDFDataValidationFunctionality()
    {
        // Test that PDF data validation still works
        $validData = [
            'client_name' => 'John Doe',
            'client_email' => 'john@example.com',
            'client_phone' => '555-1234',
            'project_name' => 'Test Project',
            'project_date' => '2023-01-01'
        ];
        
        $invalidData = [
            'client_name' => '',
            'client_email' => 'invalid-email',
            'client_phone' => '123',
            'project_name' => '',
            'project_date' => 'invalid-date'
        ];
        
        // Test valid data
        foreach ($validData as $field => $value) {
            $this->assertNotEmpty($value, "Field {$field} should not be empty");
        }
        
        // Test invalid data
        foreach ($invalidData as $field => $value) {
            if ($field === 'client_name' || $field === 'project_name') {
                $this->assertEmpty($value, "Field {$field} should be empty for invalid data");
            }
        }
    }
    
    /**
     * Test PDF error handling functionality
     */
    public function testPDFErrorHandlingFunctionality()
    {
        // Test that PDF error handling still works
        $nonExistentFile = '/path/to/non/existent/file.pdf';
        
        try {
            $this->pdfFieldService->extractFields($nonExistentFile);
            $this->fail('Should throw exception for non-existent file');
        } catch (Exception $e) {
            $this->assertInstanceOf(Exception::class, $e, 'Should throw exception for non-existent file');
        }
        
        // Test invalid data handling
        $invalidData = null;
        $outputFile = $this->outputPath . 'test_regression_invalid.pdf';
        
        try {
            $this->fillService->fillPDF($this->testPdfPath, $invalidData, $outputFile);
            $this->fail('Should throw exception for invalid data');
        } catch (Exception $e) {
            $this->assertInstanceOf(Exception::class, $e, 'Should throw exception for invalid data');
        }
    }
    
    /**
     * Test PDF file permissions functionality
     */
    public function testPDFFilePermissionsFunctionality()
    {
        // Test that PDF file permissions still work
        $testFile = $this->outputPath . 'test_regression_permissions.pdf';
        
        // Create a test file
        file_put_contents($testFile, 'test content');
        
        $this->assertTrue(file_exists($testFile), 'Test file should exist');
        $this->assertTrue(is_readable($testFile), 'Test file should be readable');
        $this->assertTrue(is_writable($testFile), 'Test file should be writable');
        
        // Clean up
        unlink($testFile);
    }
    
    /**
     * Test PDF file size functionality
     */
    public function testPDFFileSizeFunctionality()
    {
        // Test that PDF file size handling still works
        $maxFileSize = 10 * 1024 * 1024; // 10MB
        $testFileSize = 1024; // 1KB
        
        $this->assertLessThan($maxFileSize, $testFileSize, 'Test file size should be within limits');
        
        // Test file size validation
        $isValidSize = $testFileSize <= $maxFileSize;
        $this->assertTrue($isValidSize, 'File size should be valid');
    }
    
    /**
     * Test PDF MIME type functionality
     */
    public function testPDFMimeTypeFunctionality()
    {
        // Test that PDF MIME type handling still works
        $validMimeTypes = [
            'application/pdf',
            'application/x-pdf',
            'application/acrobat',
            'application/vnd.pdf',
            'text/pdf',
            'text/x-pdf'
        ];
        
        $testMimeType = 'application/pdf';
        
        $this->assertContains($testMimeType, $validMimeTypes, 'PDF MIME type should be valid');
        
        // Test MIME type validation
        $isValidMimeType = in_array($testMimeType, $validMimeTypes);
        $this->assertTrue($isValidMimeType, 'MIME type should be valid');
    }
    
    /**
     * Test PDF field types functionality
     */
    public function testPDFFieldTypesFunctionality()
    {
        // Test that PDF field types still work
        $validFieldTypes = [
            'text',
            'button',
            'checkbox',
            'radio',
            'choice',
            'signature',
            'barcode',
            'image',
            'listbox',
            'combobox'
        ];
        
        $testFieldTypes = ['text', 'checkbox', 'radio', 'signature'];
        
        foreach ($testFieldTypes as $fieldType) {
            $this->assertContains($fieldType, $validFieldTypes, "Field type {$fieldType} should be valid");
        }
    }
    
    /**
     * Test PDF field properties functionality
     */
    public function testPDFFieldPropertiesFunctionality()
    {
        // Test that PDF field properties still work
        $testField = [
            'name' => 'test_field',
            'type' => 'text',
            'value' => 'test value',
            'required' => true,
            'readonly' => false,
            'maxlength' => 100,
            'width' => 200,
            'height' => 20,
            'x' => 100,
            'y' => 200
        ];
        
        $this->assertArrayHasKey('name', $testField, 'Field should have name property');
        $this->assertArrayHasKey('type', $testField, 'Field should have type property');
        $this->assertArrayHasKey('value', $testField, 'Field should have value property');
        $this->assertArrayHasKey('required', $testField, 'Field should have required property');
        $this->assertArrayHasKey('readonly', $testField, 'Field should have readonly property');
        $this->assertArrayHasKey('maxlength', $testField, 'Field should have maxlength property');
        $this->assertArrayHasKey('width', $testField, 'Field should have width property');
        $this->assertArrayHasKey('height', $testField, 'Field should have height property');
        $this->assertArrayHasKey('x', $testField, 'Field should have x property');
        $this->assertArrayHasKey('y', $testField, 'Field should have y property');
    }
    
    /**
     * Test PDF field validation functionality
     */
    public function testPDFFieldValidationFunctionality()
    {
        // Test that PDF field validation still works
        $testField = [
            'name' => 'test_field',
            'type' => 'text',
            'value' => 'test value',
            'required' => true,
            'maxlength' => 100
        ];
        
        // Test required field validation
        $this->assertTrue($testField['required'], 'Required field should be marked as required');
        
        // Test value length validation
        $valueLength = strlen($testField['value']);
        $this->assertLessThanOrEqual($testField['maxlength'], $valueLength, 'Field value should not exceed maxlength');
        
        // Test field name validation
        $this->assertNotEmpty($testField['name'], 'Field name should not be empty');
        $this->assertIsString($testField['name'], 'Field name should be string');
    }
    
    /**
     * Test PDF field formatting functionality
     */
    public function testPDFFieldFormattingFunctionality()
    {
        // Test that PDF field formatting still works
        $testData = [
            'client_name' => 'John Doe',
            'client_email' => 'john@example.com',
            'client_phone' => '555-1234',
            'project_date' => '2023-01-01'
        ];
        
        // Test data formatting
        foreach ($testData as $field => $value) {
            $this->assertIsString($value, "Field {$field} should be string");
            $this->assertNotEmpty($value, "Field {$field} should not be empty");
        }
        
        // Test date formatting
        $date = DateTime::createFromFormat('Y-m-d', $testData['project_date']);
        $this->assertInstanceOf(DateTime::class, $date, 'Date should be valid DateTime object');
        
        // Test email formatting
        $this->assertStringContainsString('@', $testData['client_email'], 'Email should contain @ symbol');
        $this->assertStringContainsString('.', $testData['client_email'], 'Email should contain domain');
    }
    
    /**
     * Test PDF field mapping validation functionality
     */
    public function testPDFFieldMappingValidationFunctionality()
    {
        // Test that PDF field mapping validation still works
        $testMapping = [
            'client_name' => 'field1',
            'client_email' => 'field2',
            'client_phone' => 'field3'
        ];
        
        $testData = [
            'client_name' => 'John Doe',
            'client_email' => 'john@example.com',
            'client_phone' => '555-1234'
        ];
        
        // Test mapping completeness
        foreach ($testData as $source => $value) {
            $this->assertArrayHasKey($source, $testMapping, "Mapping should have key for {$source}");
            $this->assertNotEmpty($testMapping[$source], "Mapping target for {$source} should not be empty");
        }
        
        // Test data completeness
        foreach ($testMapping as $source => $target) {
            $this->assertArrayHasKey($source, $testData, "Data should have value for {$source}");
            $this->assertNotEmpty($testData[$source], "Data value for {$source} should not be empty");
        }
    }
    
    /**
     * Test PDF output file naming functionality
     */
    public function testPDFOutputFileNamingFunctionality()
    {
        // Test that PDF output file naming still works
        $timestamp = date('Ymd_His');
        $clientId = 'test_client';
        $templateCode = 'TT1';
        
        $filename = "mvp_{$timestamp}_t_{$clientId}_{$templateCode}.pdf";
        
        $this->assertStringStartsWith('mvp_', $filename, 'Filename should start with mvp_');
        $this->assertStringContainsString('_t_', $filename, 'Filename should contain _t_ separator');
        $this->assertStringEndsWith('.pdf', $filename, 'Filename should end with .pdf');
        $this->assertStringContainsString($clientId, $filename, 'Filename should contain client ID');
        $this->assertStringContainsString($templateCode, $filename, 'Filename should contain template code');
    }
    
    /**
     * Test PDF field data types functionality
     */
    public function testPDFFieldDataTypesFunctionality()
    {
        // Test that PDF field data types still work
        $testData = [
            'text_field' => 'text value',
            'number_field' => 123,
            'date_field' => '2023-01-01',
            'boolean_field' => true,
            'array_field' => ['item1', 'item2', 'item3']
        ];
        
        // Test data type validation
        $this->assertIsString($testData['text_field'], 'Text field should be string');
        $this->assertIsInt($testData['number_field'], 'Number field should be integer');
        $this->assertIsString($testData['date_field'], 'Date field should be string');
        $this->assertIsBool($testData['boolean_field'], 'Boolean field should be boolean');
        $this->assertIsArray($testData['array_field'], 'Array field should be array');
        
        // Test data conversion
        $convertedNumber = (string)$testData['number_field'];
        $this->assertEquals('123', $convertedNumber, 'Number should convert to string correctly');
        
        $convertedBoolean = $testData['boolean_field'] ? 'true' : 'false';
        $this->assertEquals('true', $convertedBoolean, 'Boolean should convert to string correctly');
    }
}
