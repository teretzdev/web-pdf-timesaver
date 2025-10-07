<?php
/**
 * Comprehensive Test Suite for Clio-Style Drafting Implementation
 * Tests EVERY component to ensure complete functionality
 */

require_once __DIR__ . '/../mvp/lib/data.php';
require_once __DIR__ . '/../mvp/lib/drafting_manager.php';
require_once __DIR__ . '/../mvp/templates/registry.php';

use WebPdfTimeSaver\Mvp\DataStore;
use WebPdfTimeSaver\Mvp\DraftingManager;
use WebPdfTimeSaver\Mvp\TemplateRegistry;

class ComprehensiveDraftingTest {
    private DataStore $dataStore;
    private DraftingManager $draftingManager;
    private array $templates;
    private string $testDataFile;
    private array $testResults = [];
    private int $passCount = 0;
    private int $failCount = 0;
    
    public function __construct() {
        $this->testDataFile = __DIR__ . '/../data/test_comprehensive_drafting.json';
        $this->templates = TemplateRegistry::load();
        echo "\n=====================================\n";
        echo "   COMPREHENSIVE DRAFTING TEST SUITE\n";
        echo "=====================================\n\n";
    }
    
    public function setUp(): void {
        echo "[SETUP] Initializing test environment...\n";
        
        // Create test data structure
        $testData = [
            'clients' => [
                [
                    'id' => 'test_client_drafting',
                    'displayName' => 'Test Drafting Client',
                    'email' => 'drafting@test.com',
                    'phone' => '555-DRAFT',
                    'status' => 'active',
                    'createdAt' => date(DATE_ATOM),
                    'updatedAt' => date(DATE_ATOM)
                ]
            ],
            'projects' => [
                [
                    'id' => 'test_project_drafting',
                    'name' => 'Test Drafting Project',
                    'clientId' => 'test_client_drafting',
                    'status' => 'in_progress',
                    'createdAt' => date(DATE_ATOM),
                    'updatedAt' => date(DATE_ATOM)
                ]
            ],
            'projectDocuments' => [
                [
                    'id' => 'test_doc_drafting',
                    'projectId' => 'test_project_drafting',
                    'templateId' => 't_fl100_gc120',
                    'status' => 'in_progress',
                    'createdAt' => date(DATE_ATOM),
                    'updatedAt' => date(DATE_ATOM)
                ]
            ],
            'templates' => $this->templates,
            'fieldValues' => [],
            'customFields' => []
        ];
        
        // Ensure directories exist
        $dirs = [
            __DIR__ . '/../data/panel_configs',
            __DIR__ . '/../data/draft_sessions',
            __DIR__ . '/../logs'
        ];
        
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
                echo "  ✓ Created directory: $dir\n";
            }
        }
        
        file_put_contents($this->testDataFile, json_encode($testData, JSON_PRETTY_PRINT));
        $this->dataStore = new DataStore($this->testDataFile);
        $this->draftingManager = new DraftingManager($this->dataStore, $this->templates);
        
        echo "  ✓ Test environment ready\n\n";
    }
    
    private function test(string $name, callable $testFunc): void {
        echo "Testing: $name\n";
        try {
            $result = $testFunc();
            if ($result === true) {
                echo "  ✅ PASSED\n";
                $this->passCount++;
                $this->testResults[$name] = 'PASSED';
            } else {
                echo "  ❌ FAILED: $result\n";
                $this->failCount++;
                $this->testResults[$name] = "FAILED: $result";
            }
        } catch (\Exception $e) {
            echo "  ❌ ERROR: " . $e->getMessage() . "\n";
            $this->failCount++;
            $this->testResults[$name] = "ERROR: " . $e->getMessage();
        }
        echo "\n";
    }
    
    // ========== COMPONENT 1: Template Structure ==========
    
    public function testTemplateStructure(): void {
        echo "\n[1] TESTING TEMPLATE STRUCTURE\n";
        echo "─────────────────────────────\n";
        
        $this->test("FL-100 template exists", function() {
            $template = $this->templates['t_fl100_gc120'] ?? null;
            return $template !== null ? true : "Template not found";
        });
        
        $this->test("Template has correct structure", function() {
            $template = $this->templates['t_fl100_gc120'];
            $required = ['id', 'code', 'name', 'panels', 'fields'];
            foreach ($required as $field) {
                if (!isset($template[$field])) {
                    return "Missing required field: $field";
                }
            }
            return true;
        });
        
        $this->test("Template has 7 panels", function() {
            $template = $this->templates['t_fl100_gc120'];
            $panelCount = count($template['panels'] ?? []);
            return $panelCount === 7 ? true : "Expected 7 panels, found $panelCount";
        });
        
        $this->test("All panels have required properties", function() {
            $template = $this->templates['t_fl100_gc120'];
            foreach ($template['panels'] as $panel) {
                if (!isset($panel['id']) || !isset($panel['label'])) {
                    return "Panel missing required properties";
                }
            }
            return true;
        });
        
        $this->test("Template has 31 fields", function() {
            $template = $this->templates['t_fl100_gc120'];
            $fieldCount = count($template['fields'] ?? []);
            return $fieldCount === 31 ? true : "Expected 31 fields, found $fieldCount";
        });
    }
    
    // ========== COMPONENT 2: Drafting Manager ==========
    
    public function testDraftingManager(): void {
        echo "\n[2] TESTING DRAFTING MANAGER\n";
        echo "───────────────────────────\n";
        
        $this->test("Create draft session", function() {
            $session = $this->draftingManager->createDraftSession('test_doc_drafting');
            if (isset($session['error'])) {
                return $session['error'];
            }
            return isset($session['id']) ? true : "Session ID not created";
        });
        
        $this->test("Draft session persists", function() {
            $session = $this->draftingManager->getDraftSessionByDocument('test_doc_drafting');
            return $session !== null ? true : "Session not found";
        });
        
        $this->test("Get drafting status", function() {
            $status = $this->draftingManager->getDraftingStatus('test_doc_drafting');
            if (isset($status['error'])) {
                return $status['error'];
            }
            return isset($status['overallProgress']) ? true : "Status missing progress";
        });
        
        $this->test("Initial progress is 0%", function() {
            $status = $this->draftingManager->getDraftingStatus('test_doc_drafting');
            return $status['overallProgress'] === 0 ? true : "Expected 0%, got {$status['overallProgress']}%";
        });
        
        $this->test("Has 7 panels in status", function() {
            $status = $this->draftingManager->getDraftingStatus('test_doc_drafting');
            $panelCount = count($status['panels'] ?? []);
            return $panelCount === 7 ? true : "Expected 7 panels, got $panelCount";
        });
    }
    
    // ========== COMPONENT 3: Field Validation ==========
    
    public function testFieldValidation(): void {
        echo "\n[3] TESTING FIELD VALIDATION\n";
        echo "───────────────────────────\n";
        
        $this->test("Required field validation", function() {
            $field = ['type' => 'text', 'required' => true];
            $result = $this->draftingManager->validateField($field, '');
            return !$result['valid'] ? true : "Should fail for empty required field";
        });
        
        $this->test("Email validation - valid", function() {
            $field = ['type' => 'email', 'required' => false];
            $result = $this->draftingManager->validateField($field, 'test@example.com');
            return $result['valid'] ? true : "Valid email should pass";
        });
        
        $this->test("Email validation - invalid", function() {
            $field = ['type' => 'email', 'required' => false];
            $result = $this->draftingManager->validateField($field, 'not-an-email');
            return !$result['valid'] ? true : "Invalid email should fail";
        });
        
        $this->test("Number validation", function() {
            $field = ['type' => 'number', 'required' => false];
            $validResult = $this->draftingManager->validateField($field, '123');
            $invalidResult = $this->draftingManager->validateField($field, 'abc');
            return $validResult['valid'] && !$invalidResult['valid'] ? true : "Number validation failed";
        });
        
        $this->test("Date validation", function() {
            $field = ['type' => 'date', 'required' => false];
            $validResult = $this->draftingManager->validateField($field, '2024-01-15');
            $invalidResult = $this->draftingManager->validateField($field, 'not-a-date');
            return $validResult['valid'] && !$invalidResult['valid'] ? true : "Date validation failed";
        });
        
        $this->test("Pattern validation", function() {
            $field = ['type' => 'text', 'pattern' => '^\d{3}-\d{3}-\d{4}$'];
            $validResult = $this->draftingManager->validateField($field, '555-123-4567');
            $invalidResult = $this->draftingManager->validateField($field, '123456789');
            return $validResult['valid'] && !$invalidResult['valid'] ? true : "Pattern validation failed";
        });
    }
    
    // ========== COMPONENT 4: Progress Tracking ==========
    
    public function testProgressTracking(): void {
        echo "\n[4] TESTING PROGRESS TRACKING\n";
        echo "────────────────────────────\n";
        
        $this->test("Save field values", function() {
            $values = [
                'attorney_name' => 'John Smith',
                'attorney_bar_number' => '123456'
            ];
            $this->dataStore->saveFieldValues('test_doc_drafting', $values);
            $saved = $this->dataStore->getFieldValues('test_doc_drafting');
            return $saved['attorney_name'] === 'John Smith' ? true : "Values not saved";
        });
        
        $this->test("Progress updates with field completion", function() {
            // Fill all attorney fields
            $attorneyFields = [
                'attorney_name' => 'John Smith, Esq.',
                'attorney_firm' => 'Smith & Associates',
                'attorney_address' => '123 Legal St',
                'attorney_city_state_zip' => 'Los Angeles, CA 90210',
                'attorney_phone' => '555-123-4567',
                'attorney_email' => 'john@smithlaw.com',
                'attorney_bar_number' => '123456'
            ];
            $this->dataStore->saveFieldValues('test_doc_drafting', $attorneyFields);
            
            $status = $this->draftingManager->getDraftingStatus('test_doc_drafting');
            return $status['overallProgress'] > 0 ? true : "Progress should increase";
        });
        
        $this->test("Panel completion detection", function() {
            $status = $this->draftingManager->getDraftingStatus('test_doc_drafting');
            $attorneyPanel = null;
            foreach ($status['panels'] as $panel) {
                if ($panel['id'] === 'attorney') {
                    $attorneyPanel = $panel;
                    break;
                }
            }
            return $attorneyPanel && $attorneyPanel['status'] === 'complete' ? true : "Attorney panel should be complete";
        });
        
        $this->test("Cannot generate PDF when incomplete", function() {
            $status = $this->draftingManager->getDraftingStatus('test_doc_drafting');
            return !$status['canGenerate'] ? true : "Should not be able to generate incomplete form";
        });
        
        $this->test("Can generate PDF when complete", function() {
            // Fill ALL required fields
            $allFields = $this->fillAllRequiredFields();
            $this->dataStore->saveFieldValues('test_doc_drafting', $allFields);
            
            $status = $this->draftingManager->getDraftingStatus('test_doc_drafting');
            return $status['canGenerate'] ? true : "Should be able to generate complete form";
        });
    }
    
    // ========== COMPONENT 5: Panel Management ==========
    
    public function testPanelManagement(): void {
        echo "\n[5] TESTING PANEL MANAGEMENT\n";
        echo "───────────────────────────\n";
        
        $this->test("Complete panel tracking", function() {
            $session = $this->draftingManager->getDraftSessionByDocument('test_doc_drafting');
            $updated = $this->draftingManager->completePanel($session['id'], 'attorney');
            return !isset($updated['error']) ? true : $updated['error'];
        });
        
        $this->test("Skip panel functionality", function() {
            $session = $this->draftingManager->getDraftSessionByDocument('test_doc_drafting');
            $updated = $this->draftingManager->skipPanel($session['id'], 'children');
            return !isset($updated['error']) ? true : $updated['error'];
        });
        
        $this->test("Current step detection", function() {
            $status = $this->draftingManager->getDraftingStatus('test_doc_drafting');
            return isset($status['currentStep']) ? true : "Current step not detected";
        });
        
        $this->test("Next step detection", function() {
            $status = $this->draftingManager->getDraftingStatus('test_doc_drafting');
            return isset($status['nextStep']) || $status['overallProgress'] === 100 ? true : "Next step not detected";
        });
    }
    
    // ========== COMPONENT 6: Custom Fields ==========
    
    public function testCustomFields(): void {
        echo "\n[6] TESTING CUSTOM FIELDS\n";
        echo "────────────────────────\n";
        
        $this->test("Add custom field", function() {
            $this->dataStore->addCustomField('test_doc_drafting', 'Custom Label', 'text', 'Placeholder', true);
            $customFields = $this->dataStore->getCustomFields('test_doc_drafting');
            return count($customFields) > 0 ? true : "Custom field not added";
        });
        
        $this->test("Update custom field", function() {
            $customFields = $this->dataStore->getCustomFields('test_doc_drafting');
            if (empty($customFields)) {
                return "No custom fields to update";
            }
            $field = $customFields[0];
            $this->dataStore->updateCustomField($field['id'], 'Updated Label', 'textarea', 'New placeholder', false);
            $updated = $this->dataStore->getCustomFields('test_doc_drafting');
            return $updated[0]['label'] === 'Updated Label' ? true : "Field not updated";
        });
        
        $this->test("Delete custom field", function() {
            $customFields = $this->dataStore->getCustomFields('test_doc_drafting');
            if (empty($customFields)) {
                return "No custom fields to delete";
            }
            $fieldId = $customFields[0]['id'];
            $this->dataStore->deleteCustomField($fieldId);
            $remaining = $this->dataStore->getCustomFields('test_doc_drafting');
            return count($remaining) === 0 ? true : "Field not deleted";
        });
    }
    
    // ========== COMPONENT 7: Analytics ==========
    
    public function testAnalytics(): void {
        echo "\n[7] TESTING ANALYTICS\n";
        echo "───────────────────\n";
        
        $this->test("Generate analytics", function() {
            $session = $this->draftingManager->getDraftSessionByDocument('test_doc_drafting');
            if (!$session) {
                return "No session found";
            }
            $analytics = $this->draftingManager->getDraftingAnalytics($session['id']);
            return !isset($analytics['error']) ? true : $analytics['error'];
        });
        
        $this->test("Analytics has metrics", function() {
            $session = $this->draftingManager->getDraftSessionByDocument('test_doc_drafting');
            $analytics = $this->draftingManager->getDraftingAnalytics($session['id']);
            $required = ['elapsedTime', 'completionRate', 'panelsCompleted', 'panelsSkipped'];
            foreach ($required as $metric) {
                if (!isset($analytics[$metric])) {
                    return "Missing metric: $metric";
                }
            }
            return true;
        });
        
        $this->test("Bottleneck identification", function() {
            $session = $this->draftingManager->getDraftSessionByDocument('test_doc_drafting');
            $analytics = $this->draftingManager->getDraftingAnalytics($session['id']);
            return isset($analytics['bottlenecks']) ? true : "Bottlenecks not identified";
        });
    }
    
    // ========== COMPONENT 8: Report Generation ==========
    
    public function testReportGeneration(): void {
        echo "\n[8] TESTING REPORT GENERATION\n";
        echo "────────────────────────────\n";
        
        $this->test("Generate drafting report", function() {
            $session = $this->draftingManager->getDraftSessionByDocument('test_doc_drafting');
            if (!$session) {
                return "No session found";
            }
            $report = $this->draftingManager->generateDraftingReport($session['id']);
            return !isset($report['error']) ? true : $report['error'];
        });
        
        $this->test("Report has all sections", function() {
            $session = $this->draftingManager->getDraftSessionByDocument('test_doc_drafting');
            $report = $this->draftingManager->generateDraftingReport($session['id']);
            $required = ['drafting', 'status', 'analytics', 'recommendations'];
            foreach ($required as $section) {
                if (!isset($report[$section])) {
                    return "Missing section: $section";
                }
            }
            return true;
        });
        
        $this->test("Recommendations generated", function() {
            $session = $this->draftingManager->getDraftSessionByDocument('test_doc_drafting');
            $report = $this->draftingManager->generateDraftingReport($session['id']);
            return is_array($report['recommendations']) ? true : "Recommendations not array";
        });
    }
    
    // ========== COMPONENT 9: File I/O ==========
    
    public function testFileOperations(): void {
        echo "\n[9] TESTING FILE OPERATIONS\n";
        echo "──────────────────────────\n";
        
        $this->test("Save panel configuration", function() {
            $config = [
                'panels' => $this->templates['t_fl100_gc120']['panels'],
                'fields' => $this->templates['t_fl100_gc120']['fields']
            ];
            $configFile = __DIR__ . '/../data/panel_configs/test_config.json';
            $saved = file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT));
            return $saved !== false ? true : "Failed to save config";
        });
        
        $this->test("Load panel configuration", function() {
            $configFile = __DIR__ . '/../data/panel_configs/test_config.json';
            if (!file_exists($configFile)) {
                return "Config file not found";
            }
            $config = json_decode(file_get_contents($configFile), true);
            return isset($config['panels']) ? true : "Invalid config structure";
        });
        
        $this->test("Draft session persistence", function() {
            $session = $this->draftingManager->getDraftSessionByDocument('test_doc_drafting');
            $sessionFile = __DIR__ . '/../data/draft_sessions/' . $session['id'] . '.json';
            return file_exists($sessionFile) ? true : "Session file not created";
        });
    }
    
    // ========== COMPONENT 10: UI Routes ==========
    
    public function testUIRoutes(): void {
        echo "\n[10] TESTING UI ROUTES\n";
        echo "─────────────────────\n";
        
        $routes = [
            'drafting' => 'Drafting view route',
            'drafting-editor' => 'Drafting editor route',
            'actions/save-draft-fields' => 'Save draft fields action',
            'actions/save-panel-configuration' => 'Save panel config action'
        ];
        
        foreach ($routes as $route => $description) {
            $this->test($description, function() use ($route) {
                // Since we can't actually test HTTP routes in PHP CLI,
                // we verify the route handlers exist in index.php
                $indexContent = file_get_contents(__DIR__ . '/../mvp/index.php');
                $routeExists = strpos($indexContent, "case '$route':") !== false;
                return $routeExists ? true : "Route not found in index.php";
            });
        }
    }
    
    // ========== COMPONENT 11: Data Integrity ==========
    
    public function testDataIntegrity(): void {
        echo "\n[11] TESTING DATA INTEGRITY\n";
        echo "──────────────────────────\n";
        
        $this->test("Field values persist correctly", function() {
            $testValues = [
                'test_field_1' => 'Value 1',
                'test_field_2' => 'Value 2',
                'test_field_3' => 'Value 3'
            ];
            $this->dataStore->saveFieldValues('test_doc_drafting', $testValues);
            $retrieved = $this->dataStore->getFieldValues('test_doc_drafting');
            
            foreach ($testValues as $key => $value) {
                if (!isset($retrieved[$key]) || $retrieved[$key] !== $value) {
                    return "Value mismatch for $key";
                }
            }
            return true;
        });
        
        $this->test("Project document relationship", function() {
            $doc = $this->dataStore->getProjectDocumentById('test_doc_drafting');
            return $doc && $doc['projectId'] === 'test_project_drafting' ? true : "Document-project relationship broken";
        });
        
        $this->test("Client-project relationship", function() {
            $project = $this->dataStore->getProject('test_project_drafting');
            return $project && $project['clientId'] === 'test_client_drafting' ? true : "Client-project relationship broken";
        });
    }
    
    // ========== COMPONENT 12: Error Handling ==========
    
    public function testErrorHandling(): void {
        echo "\n[12] TESTING ERROR HANDLING\n";
        echo "──────────────────────────\n";
        
        $this->test("Handle invalid document ID", function() {
            $status = $this->draftingManager->getDraftingStatus('invalid_id');
            return isset($status['error']) ? true : "Should return error for invalid ID";
        });
        
        $this->test("Handle missing template", function() {
            // Create doc with invalid template
            $testDoc = [
                'id' => 'test_invalid_template',
                'projectId' => 'test_project_drafting',
                'templateId' => 'invalid_template_id',
                'status' => 'in_progress',
                'createdAt' => date(DATE_ATOM),
                'updatedAt' => date(DATE_ATOM)
            ];
            
            // Add to data store
            $data = json_decode(file_get_contents($this->testDataFile), true);
            $data['projectDocuments'][] = $testDoc;
            file_put_contents($this->testDataFile, json_encode($data, JSON_PRETTY_PRINT));
            $this->dataStore = new DataStore($this->testDataFile);
            
            $session = $this->draftingManager->createDraftSession('test_invalid_template');
            return isset($session['error']) ? true : "Should error on missing template";
        });
        
        $this->test("Handle invalid field validation", function() {
            $field = ['type' => 'invalid_type'];
            $result = $this->draftingManager->validateField($field, 'test');
            return $result['valid'] ? true : "Unknown types should pass by default";
        });
    }
    
    // ========== Helper Methods ==========
    
    private function fillAllRequiredFields(): array {
        return [
            // Attorney Information
            'attorney_name' => 'John Smith, Esq.',
            'attorney_firm' => 'Smith & Associates',
            'attorney_address' => '123 Legal Plaza',
            'attorney_city_state_zip' => 'Los Angeles, CA 90210',
            'attorney_phone' => '555-123-4567',
            'attorney_email' => 'john@smithlaw.com',
            'attorney_bar_number' => '123456',
            
            // Court Information
            'case_number' => 'FL-2024-001234',
            'court_county' => 'Los Angeles',
            'court_address' => '111 N Hill St',
            'case_type' => 'Dissolution',
            'filing_date' => '2024-01-15',
            
            // Parties
            'petitioner_name' => 'Sarah Johnson',
            'respondent_name' => 'Michael Johnson',
            'petitioner_address' => '456 Main St',
            'petitioner_phone' => '555-987-6543',
            'respondent_address' => '789 Oak Ave',
            
            // Marriage
            'marriage_date' => '2010-06-15',
            'separation_date' => '2024-01-01',
            'marriage_location' => 'Las Vegas, NV',
            'grounds_for_dissolution' => 'Irreconcilable differences',
            'dissolution_type' => 'Dissolution of Marriage',
            
            // Relief
            'property_division' => 'Yes',
            'spousal_support' => 'No',
            'attorney_fees' => 'Yes',
            'name_change' => 'No',
            
            // Children
            'has_children' => 'No',
            'children_count' => '0',
            
            // Additional
            'additional_info' => 'None',
            'attorney_signature' => 'John Smith',
            'signature_date' => '2024-01-15'
        ];
    }
    
    public function tearDown(): void {
        echo "\n[CLEANUP] Removing test files...\n";
        
        // Clean up test data file
        if (file_exists($this->testDataFile)) {
            unlink($this->testDataFile);
            echo "  ✓ Removed test data file\n";
        }
        
        // Clean up draft sessions
        $sessionFiles = glob(__DIR__ . '/../data/draft_sessions/draft_*.json');
        foreach ($sessionFiles as $file) {
            unlink($file);
        }
        echo "  ✓ Removed draft session files\n";
        
        // Clean up test config
        $testConfig = __DIR__ . '/../data/panel_configs/test_config.json';
        if (file_exists($testConfig)) {
            unlink($testConfig);
            echo "  ✓ Removed test config\n";
        }
        
        echo "  ✓ Cleanup complete\n";
    }
    
    public function generateReport(): void {
        echo "\n=====================================\n";
        echo "         TEST RESULTS SUMMARY\n";
        echo "=====================================\n\n";
        
        echo "Total Tests: " . ($this->passCount + $this->failCount) . "\n";
        echo "✅ Passed: $this->passCount\n";
        echo "❌ Failed: $this->failCount\n";
        echo "Success Rate: " . round(($this->passCount / ($this->passCount + $this->failCount)) * 100, 2) . "%\n\n";
        
        if ($this->failCount > 0) {
            echo "Failed Tests:\n";
            echo "─────────────\n";
            foreach ($this->testResults as $name => $result) {
                if (strpos($result, 'FAILED') !== false || strpos($result, 'ERROR') !== false) {
                    echo "• $name\n";
                    echo "  → $result\n";
                }
            }
            echo "\n";
        }
        
        // Generate detailed report file
        $reportFile = __DIR__ . '/../drafting_test_report_' . date('Y-m-d_H-i-s') . '.txt';
        $reportContent = "COMPREHENSIVE DRAFTING TEST REPORT\n";
        $reportContent .= "Generated: " . date('Y-m-d H:i:s') . "\n\n";
        $reportContent .= "Summary:\n";
        $reportContent .= "- Total Tests: " . ($this->passCount + $this->failCount) . "\n";
        $reportContent .= "- Passed: $this->passCount\n";
        $reportContent .= "- Failed: $this->failCount\n";
        $reportContent .= "- Success Rate: " . round(($this->passCount / ($this->passCount + $this->failCount)) * 100, 2) . "%\n\n";
        $reportContent .= "Detailed Results:\n";
        $reportContent .= str_repeat("─", 50) . "\n";
        
        foreach ($this->testResults as $name => $result) {
            $reportContent .= "$name: $result\n";
        }
        
        file_put_contents($reportFile, $reportContent);
        echo "📄 Detailed report saved to: " . basename($reportFile) . "\n\n";
        
        if ($this->failCount === 0) {
            echo "🎉 ALL TESTS PASSED! The Clio-style drafting implementation is fully functional!\n";
        } else {
            echo "⚠️ Some tests failed. Please review the failures above.\n";
        }
    }
    
    public function runAllTests(): void {
        $startTime = microtime(true);
        
        $this->setUp();
        
        // Run all component tests
        $this->testTemplateStructure();
        $this->testDraftingManager();
        $this->testFieldValidation();
        $this->testProgressTracking();
        $this->testPanelManagement();
        $this->testCustomFields();
        $this->testAnalytics();
        $this->testReportGeneration();
        $this->testFileOperations();
        $this->testUIRoutes();
        $this->testDataIntegrity();
        $this->testErrorHandling();
        
        $this->tearDown();
        
        $elapsedTime = round(microtime(true) - $startTime, 2);
        
        $this->generateReport();
        
        echo "⏱️  Total execution time: {$elapsedTime} seconds\n";
    }
}

// Execute the comprehensive test suite
echo "Starting comprehensive test of all drafting components...\n";
$tester = new ComprehensiveDraftingTest();
$tester->runAllTests();