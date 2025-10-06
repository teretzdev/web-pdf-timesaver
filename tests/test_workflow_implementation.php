<?php
/**
 * Test script for verifying the Clio-style workflow implementation
 * Tests all components: panel editor, workflow manager, and form filling
 */

require_once __DIR__ . '/../mvp/lib/data.php';
require_once __DIR__ . '/../mvp/lib/workflow_manager.php';
require_once __DIR__ . '/../mvp/templates/registry.php';
require_once __DIR__ . '/../vendor/autoload.php';

use WebPdfTimeSaver\Mvp\DataStore;
use WebPdfTimeSaver\Mvp\WorkflowManager;
use WebPdfTimeSaver\Mvp\TemplateRegistry;

class WorkflowImplementationTest {
    private DataStore $dataStore;
    private WorkflowManager $workflowManager;
    private array $templates;
    private string $testDataFile;
    
    public function __construct() {
        $this->testDataFile = __DIR__ . '/../data/test_workflow.json';
        $this->templates = TemplateRegistry::load();
    }
    
    public function setUp(): void {
        // Initialize test data
        $testData = [
            'clients' => [
                [
                    'id' => 'workflow_test_client',
                    'displayName' => 'Workflow Test Client',
                    'email' => 'workflow@test.com',
                    'phone' => '555-WORK',
                    'status' => 'active',
                    'createdAt' => date(DATE_ATOM),
                    'updatedAt' => date(DATE_ATOM)
                ]
            ],
            'projects' => [
                [
                    'id' => 'workflow_test_project',
                    'name' => 'Workflow Test Project',
                    'clientId' => 'workflow_test_client',
                    'status' => 'in_progress',
                    'createdAt' => date(DATE_ATOM),
                    'updatedAt' => date(DATE_ATOM)
                ]
            ],
            'projectDocuments' => [
                [
                    'id' => 'workflow_test_document',
                    'projectId' => 'workflow_test_project',
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
        
        file_put_contents($this->testDataFile, json_encode($testData, JSON_PRETTY_PRINT));
        $this->dataStore = new DataStore($this->testDataFile);
        $this->workflowManager = new WorkflowManager($this->dataStore, $this->templates);
    }
    
    public function testPanelConfiguration(): bool {
        echo "=== Testing Panel Configuration ===\n";
        
        $template = $this->templates['t_fl100_gc120'] ?? null;
        if (!$template) {
            echo "❌ FL-100 template not found\n";
            return false;
        }
        
        echo "✓ Template loaded successfully\n";
        echo "  - Template ID: {$template['id']}\n";
        echo "  - Template Name: {$template['name']}\n";
        
        // Test panel structure
        $panels = $template['panels'] ?? [];
        if (empty($panels)) {
            echo "❌ No panels found in template\n";
            return false;
        }
        
        echo "✓ Found " . count($panels) . " panels:\n";
        foreach ($panels as $panel) {
            $fieldCount = 0;
            foreach ($template['fields'] ?? [] as $field) {
                if (($field['panelId'] ?? '') === $panel['id']) {
                    $fieldCount++;
                }
            }
            echo "  - {$panel['label']}: {$fieldCount} fields\n";
        }
        
        // Test panel configuration save
        $configFile = __DIR__ . '/../data/panel_configs/t_fl100_gc120.json';
        $configDir = dirname($configFile);
        
        if (!is_dir($configDir)) {
            mkdir($configDir, 0777, true);
        }
        
        $saved = file_put_contents($configFile, json_encode($template, JSON_PRETTY_PRINT));
        if ($saved === false) {
            echo "❌ Failed to save panel configuration\n";
            return false;
        }
        
        echo "✓ Panel configuration saved successfully\n";
        return true;
    }
    
    public function testWorkflowCreation(): bool {
        echo "\n=== Testing Workflow Creation ===\n";
        
        $projectDocumentId = 'workflow_test_document';
        $workflow = $this->workflowManager->createWorkflow($projectDocumentId);
        
        if (isset($workflow['error'])) {
            echo "❌ Failed to create workflow: {$workflow['error']}\n";
            return false;
        }
        
        echo "✓ Workflow created successfully\n";
        echo "  - Workflow ID: {$workflow['id']}\n";
        echo "  - Document ID: {$workflow['projectDocumentId']}\n";
        echo "  - Template ID: {$workflow['templateId']}\n";
        echo "  - Status: {$workflow['status']}\n";
        
        // Test workflow state persistence
        $loadedWorkflow = $this->workflowManager->loadWorkflowState($workflow['id']);
        if (!$loadedWorkflow) {
            echo "❌ Failed to load workflow state\n";
            return false;
        }
        
        echo "✓ Workflow state persisted and loaded successfully\n";
        
        return true;
    }
    
    public function testWorkflowStatus(): bool {
        echo "\n=== Testing Workflow Status ===\n";
        
        $projectDocumentId = 'workflow_test_document';
        $status = $this->workflowManager->getWorkflowStatus($projectDocumentId);
        
        if (isset($status['error'])) {
            echo "❌ Failed to get workflow status: {$status['error']}\n";
            return false;
        }
        
        echo "✓ Workflow status retrieved successfully\n";
        echo "  - Overall Progress: {$status['overallProgress']}%\n";
        echo "  - Total Panels: {$status['totalPanels']}\n";
        echo "  - Completed Panels: {$status['completedPanels']}\n";
        echo "  - Can Generate PDF: " . ($status['canGenerate'] ? 'Yes' : 'No') . "\n";
        
        if ($status['currentStep']) {
            echo "  - Current Step: {$status['currentStep']['label']} (Panel {$status['currentStep']['index']})\n";
        }
        
        return true;
    }
    
    public function testFieldValidation(): bool {
        echo "\n=== Testing Field Validation ===\n";
        
        $testCases = [
            [
                'field' => ['type' => 'email', 'required' => true],
                'value' => 'test@example.com',
                'expectedValid' => true
            ],
            [
                'field' => ['type' => 'email', 'required' => true],
                'value' => 'invalid-email',
                'expectedValid' => false
            ],
            [
                'field' => ['type' => 'number', 'required' => true],
                'value' => '123',
                'expectedValid' => true
            ],
            [
                'field' => ['type' => 'number', 'required' => true],
                'value' => 'abc',
                'expectedValid' => false
            ],
            [
                'field' => ['type' => 'date', 'required' => false],
                'value' => '2024-01-15',
                'expectedValid' => true
            ],
            [
                'field' => ['type' => 'text', 'required' => true],
                'value' => '',
                'expectedValid' => false
            ]
        ];
        
        $allPassed = true;
        foreach ($testCases as $index => $test) {
            $result = $this->workflowManager->validateField($test['field'], $test['value']);
            $passed = $result['valid'] === $test['expectedValid'];
            
            if ($passed) {
                echo "✓ Test case " . ($index + 1) . " passed\n";
            } else {
                echo "❌ Test case " . ($index + 1) . " failed\n";
                echo "  - Expected: " . ($test['expectedValid'] ? 'valid' : 'invalid') . "\n";
                echo "  - Got: " . ($result['valid'] ? 'valid' : 'invalid') . "\n";
                if (!$result['valid']) {
                    echo "  - Message: {$result['message']}\n";
                }
                $allPassed = false;
            }
        }
        
        return $allPassed;
    }
    
    public function testWorkflowProgression(): bool {
        echo "\n=== Testing Workflow Progression ===\n";
        
        $projectDocumentId = 'workflow_test_document';
        
        // Create a new workflow
        $workflow = $this->workflowManager->createWorkflow($projectDocumentId);
        if (isset($workflow['error'])) {
            echo "❌ Failed to create workflow for progression test\n";
            return false;
        }
        
        echo "✓ Workflow created for progression test\n";
        
        // Simulate completing panels
        $template = $this->templates['t_fl100_gc120'] ?? null;
        $panels = $template['panels'] ?? [];
        
        foreach ($panels as $index => $panel) {
            // Simulate saving field values for this panel
            $fieldValues = [];
            foreach ($template['fields'] ?? [] as $field) {
                if (($field['panelId'] ?? '') === $panel['id']) {
                    // Generate mock data based on field type
                    switch ($field['type'] ?? 'text') {
                        case 'email':
                            $fieldValues[$field['key']] = 'test@example.com';
                            break;
                        case 'number':
                            $fieldValues[$field['key']] = '12345';
                            break;
                        case 'date':
                            $fieldValues[$field['key']] = '2024-01-15';
                            break;
                        case 'checkbox':
                            $fieldValues[$field['key']] = '1';
                            break;
                        case 'select':
                            $fieldValues[$field['key']] = $field['options'][0] ?? 'Option 1';
                            break;
                        default:
                            $fieldValues[$field['key']] = 'Test Value ' . $field['key'];
                    }
                }
            }
            
            // Save field values
            $this->dataStore->saveFieldValues($projectDocumentId, $fieldValues);
            
            // Complete the panel
            $updatedWorkflow = $this->workflowManager->completePanel($workflow['id'], $panel['id']);
            if (isset($updatedWorkflow['error'])) {
                echo "❌ Failed to complete panel {$panel['label']}\n";
                return false;
            }
            
            echo "✓ Completed panel: {$panel['label']}\n";
        }
        
        // Check final status
        $finalStatus = $this->workflowManager->getWorkflowStatus($projectDocumentId);
        
        echo "\n✓ Workflow progression complete\n";
        echo "  - Final Progress: {$finalStatus['overallProgress']}%\n";
        echo "  - Can Generate PDF: " . ($finalStatus['canGenerate'] ? 'Yes' : 'No') . "\n";
        
        if ($finalStatus['overallProgress'] !== 100) {
            echo "❌ Expected 100% progress, got {$finalStatus['overallProgress']}%\n";
            return false;
        }
        
        if (!$finalStatus['canGenerate']) {
            echo "❌ Expected to be able to generate PDF after completing all panels\n";
            return false;
        }
        
        return true;
    }
    
    public function testWorkflowAnalytics(): bool {
        echo "\n=== Testing Workflow Analytics ===\n";
        
        $projectDocumentId = 'workflow_test_document';
        $workflow = $this->workflowManager->getWorkflowByDocument($projectDocumentId);
        
        if (!$workflow) {
            echo "❌ No workflow found for document\n";
            return false;
        }
        
        $analytics = $this->workflowManager->getWorkflowAnalytics($workflow['id']);
        
        if (isset($analytics['error'])) {
            echo "❌ Failed to get workflow analytics: {$analytics['error']}\n";
            return false;
        }
        
        echo "✓ Workflow analytics generated successfully\n";
        echo "  - Elapsed Time: {$analytics['elapsedTime']}\n";
        echo "  - Completion Rate: {$analytics['completionRate']}\n";
        echo "  - Panels Completed: {$analytics['panelsCompleted']}\n";
        echo "  - Panels Skipped: {$analytics['panelsSkipped']}\n";
        echo "  - Average Time Per Panel: {$analytics['averageTimePerPanel']}\n";
        echo "  - Bottlenecks Found: " . count($analytics['bottlenecks']) . "\n";
        
        return true;
    }
    
    public function testWorkflowReport(): bool {
        echo "\n=== Testing Workflow Report Generation ===\n";
        
        $projectDocumentId = 'workflow_test_document';
        $workflow = $this->workflowManager->getWorkflowByDocument($projectDocumentId);
        
        if (!$workflow) {
            echo "❌ No workflow found for document\n";
            return false;
        }
        
        $report = $this->workflowManager->generateWorkflowReport($workflow['id']);
        
        if (isset($report['error'])) {
            echo "❌ Failed to generate workflow report: {$report['error']}\n";
            return false;
        }
        
        echo "✓ Workflow report generated successfully\n";
        echo "  - Workflow ID: {$report['workflow']['id']}\n";
        echo "  - Status Overview: {$report['status']['overallProgress']}% complete\n";
        echo "  - Recommendations: " . count($report['recommendations']) . "\n";
        
        foreach ($report['recommendations'] as $rec) {
            echo "    - [{$rec['priority']}] {$rec['message']}\n";
        }
        
        // Save report to file
        $reportFile = __DIR__ . '/../data/workflow_report_' . date('Ymd_His') . '.json';
        file_put_contents($reportFile, json_encode($report, JSON_PRETTY_PRINT));
        echo "✓ Report saved to: " . basename($reportFile) . "\n";
        
        return true;
    }
    
    public function tearDown(): void {
        // Clean up test data
        if (file_exists($this->testDataFile)) {
            unlink($this->testDataFile);
        }
        
        // Clean up test workflow files
        $workflowDir = __DIR__ . '/../data/workflows/';
        if (is_dir($workflowDir)) {
            $files = glob($workflowDir . 'workflow_*.json');
            foreach ($files as $file) {
                unlink($file);
            }
        }
    }
    
    public function runAllTests(): void {
        echo "========================================\n";
        echo "   Clio-Style Workflow Implementation Test\n";
        echo "========================================\n\n";
        
        $this->setUp();
        
        $tests = [
            'testPanelConfiguration',
            'testWorkflowCreation',
            'testWorkflowStatus',
            'testFieldValidation',
            'testWorkflowProgression',
            'testWorkflowAnalytics',
            'testWorkflowReport'
        ];
        
        $passed = 0;
        $failed = 0;
        
        foreach ($tests as $test) {
            if ($this->$test()) {
                $passed++;
            } else {
                $failed++;
            }
        }
        
        echo "\n========================================\n";
        echo "Test Results:\n";
        echo "  ✓ Passed: {$passed}\n";
        echo "  ❌ Failed: {$failed}\n";
        echo "========================================\n";
        
        if ($failed === 0) {
            echo "\n🎉 All tests passed! The Clio-style workflow implementation is working correctly.\n";
        } else {
            echo "\n⚠️ Some tests failed. Please review the implementation.\n";
        }
        
        $this->tearDown();
    }
}

// Run the tests
$tester = new WorkflowImplementationTest();
$tester->runAllTests();