<?php
declare(strict_types=1);

/**
 * Form Population and Data Persistence Test Suite
 * 
 * Tests the form filling and data management functionality similar to Clio Draft:
 * - Form data entry and validation
 * - Data persistence across sessions
 * - Custom field support
 * - Auto-save functionality
 * - Data export/import
 */

require_once __DIR__ . '/../mvp/lib/data.php';
require_once __DIR__ . '/../mvp/templates/registry.php';

use WebPdfTimeSaver\Mvp\DataStore;
use WebPdfTimeSaver\Mvp\TemplateRegistry;

echo "📋 Testing Form Population and Data Persistence\n";
echo "==============================================\n\n";

$testsPassed = 0;
$testsFailed = 0;

function assertTest($condition, $message) {
    global $testsPassed, $testsFailed;
    if ($condition) {
        echo "✅ $message\n";
        $testsPassed++;
    } else {
        echo "❌ FAILED: $message\n";
        $testsFailed++;
    }
}

function assertEquals($expected, $actual, $message) {
    assertTest($expected === $actual, "$message - Expected: '$expected', Got: '$actual'");
}

function assertNotEmpty($value, $message) {
    assertTest(!empty($value), "$message - Value should not be empty");
}

// Initialize test environment
$dataStore = new DataStore(__DIR__ . '/../data/mvp_test.json');
$templates = TemplateRegistry::load();
$template = $templates['t_fl100_gc120'];

// Test 1: Form Data Entry
echo "Test 1: Form Data Entry\n";
echo "-----------------------\n";

function populateFormData($template) {
    $formData = [];
    
    foreach ($template['fields'] as $field) {
        $value = '';
        
        switch ($field['type']) {
            case 'text':
                $value = "Test " . $field['label'];
                break;
            case 'email':
                $value = "test@example.com";
                break;
            case 'tel':
                $value = "(555) 123-4567";
                break;
            case 'number':
                $value = "42";
                break;
            case 'date':
                $value = date('Y-m-d');
                break;
            case 'checkbox':
                $value = true;
                break;
            case 'select':
                if (!empty($field['options'])) {
                    $value = $field['options'][0];
                }
                break;
            case 'textarea':
                $value = "Test description for " . $field['label'];
                break;
        }
        
        $formData[$field['key']] = $value;
    }
    
    return $formData;
}

$testFormData = populateFormData($template);
assertNotEmpty($testFormData, "Form data generated");
assertTest(count($testFormData) === count($template['fields']), "All fields populated");

// Test 2: Data Validation
echo "\nTest 2: Data Validation\n";
echo "-----------------------\n";

function validateFormData($formData, $template) {
    $errors = [];
    
    foreach ($template['fields'] as $field) {
        $value = $formData[$field['key']] ?? null;
        
        // Required field validation
        if (!empty($field['required']) && empty($value)) {
            $errors[] = "Field '{$field['label']}' is required";
        }
        
        // Type-specific validation
        if (!empty($value)) {
            switch ($field['type']) {
                case 'email':
                    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $errors[] = "Invalid email format for '{$field['label']}'";
                    }
                    break;
                case 'number':
                    if (!is_numeric($value)) {
                        $errors[] = "Invalid number format for '{$field['label']}'";
                    }
                    break;
                case 'date':
                    $date = DateTime::createFromFormat('Y-m-d', $value);
                    if (!$date || $date->format('Y-m-d') !== $value) {
                        $errors[] = "Invalid date format for '{$field['label']}'";
                    }
                    break;
                case 'select':
                    if (!empty($field['options']) && !in_array($value, $field['options'])) {
                        $errors[] = "Invalid option for '{$field['label']}'";
                    }
                    break;
            }
        }
        
        // Custom validation rules
        if (!empty($field['validation'])) {
            // Pattern validation
            if (!empty($field['validation']['pattern'])) {
                if (!preg_match($field['validation']['pattern'], (string)$value)) {
                    $errors[] = "Value doesn't match pattern for '{$field['label']}'";
                }
            }
            
            // Min/max length
            if (isset($field['validation']['minLength'])) {
                if (strlen((string)$value) < $field['validation']['minLength']) {
                    $errors[] = "Value too short for '{$field['label']}'";
                }
            }
        }
    }
    
    return $errors;
}

$validationErrors = validateFormData($testFormData, $template);
assertTest(empty($validationErrors), "Valid form data passes validation");

// Test with invalid data
$invalidData = $testFormData;
$invalidData['attorney_email'] = 'invalid-email';
$validationErrors = validateFormData($invalidData, $template);
assertTest(!empty($validationErrors), "Invalid data detected by validation");

// Test 3: Data Persistence
echo "\nTest 3: Data Persistence\n";
echo "------------------------\n";

// Create a project for testing
$project = [
    'id' => 'test_project_' . uniqid(),
    'name' => 'Form Population Test Project',
    'clientId' => 'test_client_001',
    'status' => 'active',
    'created' => date('Y-m-d H:i:s'),
    'updated' => date('Y-m-d H:i:s')
];

$projectId = $dataStore->createProject($project);
assertNotEmpty($projectId, "Project created for testing");

// Create a document with form data
$document = [
    'id' => 'test_doc_' . uniqid(),
    'projectId' => $projectId,
    'templateId' => 't_fl100_gc120',
    'name' => 'Test Form Document',
    'status' => 'draft',
    'fields' => $testFormData,
    'created' => date('Y-m-d H:i:s'),
    'updated' => date('Y-m-d H:i:s')
];

$documentId = $dataStore->createProjectDocument($document);
assertNotEmpty($documentId, "Document created with form data");

// Retrieve and verify
$retrievedDoc = $dataStore->getProjectDocument($documentId);
assertTest($retrievedDoc !== null, "Document retrieved from storage");
assertEquals(count($testFormData), count($retrievedDoc['fields'] ?? []), "All form data persisted");

// Test 4: Custom Fields
echo "\nTest 4: Custom Fields\n";
echo "--------------------\n";

function addCustomField($document, $key, $label, $value, $type = 'text') {
    if (!isset($document['customFields'])) {
        $document['customFields'] = [];
    }
    
    $document['customFields'][] = [
        'id' => 'custom_' . uniqid(),
        'key' => $key,
        'label' => $label,
        'type' => $type,
        'value' => $value,
        'addedAt' => date('Y-m-d H:i:s')
    ];
    
    return $document;
}

$docWithCustom = $retrievedDoc;
$docWithCustom = addCustomField($docWithCustom, 'special_notes', 'Special Notes', 'This is a custom field');
$docWithCustom = addCustomField($docWithCustom, 'case_priority', 'Case Priority', 'High', 'select');

assertTest(isset($docWithCustom['customFields']), "Custom fields added");
assertEquals(2, count($docWithCustom['customFields']), "Multiple custom fields supported");

// Update document with custom fields
$dataStore->updateProjectDocument($documentId, $docWithCustom);
$updatedDoc = $dataStore->getProjectDocument($documentId);
assertTest(!empty($updatedDoc['customFields']), "Custom fields persisted");

// Test 5: Auto-save Simulation
echo "\nTest 5: Auto-save Simulation\n";
echo "----------------------------\n";

class AutoSaveManager {
    private $store;
    private $saveInterval = 30; // seconds
    private $lastSave = null;
    
    public function __construct($store) {
        $this->store = $store;
    }
    
    public function shouldSave() {
        if ($this->lastSave === null) {
            return true;
        }
        
        return (time() - $this->lastSave) >= $this->saveInterval;
    }
    
    public function saveIfNeeded($documentId, $data) {
        if ($this->shouldSave()) {
            $data['autoSaved'] = true;
            $data['autoSaveTime'] = date('Y-m-d H:i:s');
            $this->store->updateProjectDocument($documentId, $data);
            $this->lastSave = time();
            return true;
        }
        return false;
    }
    
    public function forceSave($documentId, $data) {
        $data['autoSaved'] = false;
        $data['saveTime'] = date('Y-m-d H:i:s');
        $this->store->updateProjectDocument($documentId, $data);
        $this->lastSave = time();
        return true;
    }
}

$autoSave = new AutoSaveManager($dataStore);
$saved = $autoSave->saveIfNeeded($documentId, $updatedDoc);
assertTest($saved, "Auto-save triggered on first call");

$saved = $autoSave->forceSave($documentId, $updatedDoc);
assertTest($saved, "Manual save completed");

// Test 6: Form Data Merging
echo "\nTest 6: Form Data Merging\n";
echo "-------------------------\n";

function mergeFormData($existing, $updates, $overwrite = false) {
    $merged = $existing;
    
    foreach ($updates as $key => $value) {
        if ($overwrite || empty($existing[$key])) {
            $merged[$key] = $value;
        }
    }
    
    return $merged;
}

$originalData = [
    'attorney_name' => 'John Doe',
    'attorney_email' => 'john@example.com',
    'case_number' => ''
];

$updateData = [
    'attorney_email' => 'newemail@example.com',
    'case_number' => '2024-001',
    'court_county' => 'Los Angeles'
];

$mergedData = mergeFormData($originalData, $updateData, false);
assertEquals('John Doe', $mergedData['attorney_name'], "Existing non-empty data preserved");
assertEquals('2024-001', $mergedData['case_number'], "Empty field updated");
assertEquals('Los Angeles', $mergedData['court_county'], "New field added");

$overwrittenData = mergeFormData($originalData, $updateData, true);
assertEquals('newemail@example.com', $overwrittenData['attorney_email'], "Existing data overwritten when requested");

// Test 7: Data Export/Import
echo "\nTest 7: Data Export/Import\n";
echo "--------------------------\n";

function exportFormData($document, $format = 'json') {
    if ($format === 'json') {
        return json_encode([
            'documentId' => $document['id'],
            'templateId' => $document['templateId'],
            'fields' => $document['fields'],
            'customFields' => $document['customFields'] ?? [],
            'exported' => date('Y-m-d H:i:s')
        ], JSON_PRETTY_PRINT);
    } elseif ($format === 'csv') {
        $csv = "Field,Value\n";
        foreach ($document['fields'] as $key => $value) {
            $csv .= "\"$key\",\"$value\"\n";
        }
        return $csv;
    }
    return null;
}

function importFormData($data, $format = 'json') {
    if ($format === 'json') {
        $imported = json_decode($data, true);
        if ($imported) {
            $imported['imported'] = date('Y-m-d H:i:s');
            return $imported;
        }
    }
    return null;
}

$exportedJson = exportFormData($updatedDoc, 'json');
assertNotEmpty($exportedJson, "Form data exported to JSON");

$importedData = importFormData($exportedJson, 'json');
assertTest($importedData !== null, "Form data imported from JSON");
assertEquals($documentId, $importedData['documentId'], "Document ID preserved in export/import");

// Test 8: Field History Tracking
echo "\nTest 8: Field History Tracking\n";
echo "------------------------------\n";

function trackFieldChange($document, $fieldKey, $oldValue, $newValue) {
    if (!isset($document['fieldHistory'])) {
        $document['fieldHistory'] = [];
    }
    
    $document['fieldHistory'][] = [
        'fieldKey' => $fieldKey,
        'oldValue' => $oldValue,
        'newValue' => $newValue,
        'changedAt' => date('Y-m-d H:i:s'),
        'changedBy' => 'system' // Would be user ID in real app
    ];
    
    return $document;
}

$docWithHistory = $updatedDoc;
$oldEmail = $docWithHistory['fields']['attorney_email'] ?? '';
$docWithHistory = trackFieldChange($docWithHistory, 'attorney_email', $oldEmail, 'updated@example.com');
$docWithHistory['fields']['attorney_email'] = 'updated@example.com';

assertTest(isset($docWithHistory['fieldHistory']), "Field history tracking initialized");
assertTest(count($docWithHistory['fieldHistory']) > 0, "Field change recorded in history");

// Test 9: Batch Operations
echo "\nTest 9: Batch Operations\n";
echo "-----------------------\n";

function batchUpdateDocuments($store, $documentIds, $updates) {
    $results = [
        'success' => [],
        'failed' => []
    ];
    
    foreach ($documentIds as $docId) {
        try {
            $doc = $store->getProjectDocument($docId);
            if ($doc) {
                $doc['fields'] = array_merge($doc['fields'] ?? [], $updates);
                $doc['batchUpdated'] = date('Y-m-d H:i:s');
                $store->updateProjectDocument($docId, $doc);
                $results['success'][] = $docId;
            } else {
                $results['failed'][] = $docId;
            }
        } catch (Exception $e) {
            $results['failed'][] = $docId;
        }
    }
    
    return $results;
}

// Create additional test documents
$doc2Id = $dataStore->createProjectDocument([
    'id' => 'test_doc2_' . uniqid(),
    'projectId' => $projectId,
    'templateId' => 't_fl100_gc120',
    'name' => 'Test Document 2',
    'fields' => []
]);

$batchUpdates = [
    'batch_field' => 'Batch Update Value',
    'batch_timestamp' => date('Y-m-d H:i:s')
];

$batchResults = batchUpdateDocuments($dataStore, [$documentId, $doc2Id], $batchUpdates);
assertTest(count($batchResults['success']) === 2, "Batch update successful for all documents");

// Test 10: Data Completeness Calculation
echo "\nTest 10: Data Completeness\n";
echo "--------------------------\n";

function calculateCompleteness($document, $template) {
    $requiredFields = array_filter($template['fields'], fn($f) => !empty($f['required']));
    $totalRequired = count($requiredFields);
    
    if ($totalRequired === 0) {
        return 100;
    }
    
    $completed = 0;
    foreach ($requiredFields as $field) {
        if (!empty($document['fields'][$field['key']])) {
            $completed++;
        }
    }
    
    return round(($completed / $totalRequired) * 100);
}

$completeness = calculateCompleteness($updatedDoc, $template);
assertTest($completeness >= 0 && $completeness <= 100, "Completeness percentage calculated: {$completeness}%");

// Test with empty document
$emptyDoc = ['fields' => []];
$emptyCompleteness = calculateCompleteness($emptyDoc, $template);
assertEquals(0, $emptyCompleteness, "Empty document has 0% completeness");

// Test with fully populated document
$fullDoc = ['fields' => $testFormData];
$fullCompleteness = calculateCompleteness($fullDoc, $template);
assertTest($fullCompleteness > 0, "Populated document has > 0% completeness");

// Clean up test data
echo "\nCleanup\n";
echo "-------\n";
$dataStore->deleteProjectDocument($documentId);
$dataStore->deleteProjectDocument($doc2Id);
$dataStore->deleteProject($projectId);
echo "✅ Test data cleaned up\n";

// Summary
echo "\n" . str_repeat("=", 50) . "\n";
echo "📊 FORM POPULATION TEST SUMMARY\n";
echo str_repeat("=", 50) . "\n";
echo "✅ Passed: $testsPassed\n";
echo "❌ Failed: $testsFailed\n";

if ($testsFailed === 0) {
    echo "\n🎉 ALL FORM POPULATION TESTS PASSED!\n";
    exit(0);
} else {
    echo "\n⚠️ SOME TESTS FAILED\n";
    exit(1);
}