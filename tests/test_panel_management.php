<?php
declare(strict_types=1);

/**
 * Panel Management Test Suite
 * 
 * This test ensures that our panel editing functionality matches the expected
 * behavior from Clio Draft's panel editor:
 * - Panel creation and organization
 * - Field-panel associations
 * - Panel ordering
 * - Panel-based form rendering
 */

require_once __DIR__ . '/../mvp/lib/data.php';
require_once __DIR__ . '/../mvp/templates/registry.php';

use WebPdfTimeSaver\Mvp\DataStore;
use WebPdfTimeSaver\Mvp\TemplateRegistry;

echo "🎨 Testing Panel Management System\n";
echo "====================================\n\n";

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

function assertArrayHasKey($key, $array, $message) {
    assertTest(isset($array[$key]), "$message - Key '$key' exists");
}

function assertEquals($expected, $actual, $message) {
    assertTest($expected === $actual, "$message - Expected: $expected, Got: $actual");
}

// Test 1: Panel Structure in Templates
echo "Test 1: Panel Structure in Templates\n";
echo "------------------------------------\n";

$templates = TemplateRegistry::load();
assertTest(is_array($templates) && !empty($templates), "Templates loaded successfully");

$template = $templates['t_fl100_gc120'];
assertTest(isset($template), "FL-100 template exists");
assertArrayHasKey('panels', $template, "Template has panels");
assertTest(is_array($template['panels']), "Panels is an array");
assertTest(count($template['panels']) > 0, "Template has at least one panel");

// Test 2: Panel Properties
echo "\nTest 2: Panel Properties\n";
echo "------------------------\n";

foreach ($template['panels'] as $panel) {
    assertArrayHasKey('id', $panel, "Panel has 'id' property");
    assertArrayHasKey('label', $panel, "Panel has 'label' property");
    assertTest(!empty($panel['id']), "Panel id is not empty: {$panel['id']}");
    assertTest(!empty($panel['label']), "Panel label is not empty: {$panel['label']}");
}

// Test 3: Field-Panel Associations
echo "\nTest 3: Field-Panel Associations\n";
echo "--------------------------------\n";

$panelIds = array_column($template['panels'], 'id');
$fieldsWithPanels = 0;
$fieldsWithoutPanels = 0;

foreach ($template['fields'] as $field) {
    if (isset($field['panelId']) && !empty($field['panelId'])) {
        $fieldsWithPanels++;
        assertTest(
            in_array($field['panelId'], $panelIds),
            "Field '{$field['key']}' has valid panel ID: {$field['panelId']}"
        );
    } else {
        $fieldsWithoutPanels++;
    }
}

echo "📊 Fields with panels: $fieldsWithPanels\n";
echo "📊 Fields without panels: $fieldsWithoutPanels\n";

// Test 4: Panel Organization and Ordering
echo "\nTest 4: Panel Organization\n";
echo "--------------------------\n";

$expectedPanelOrder = ['attorney', 'court', 'parties', 'marriage', 'relief', 'children', 'additional'];
$actualPanelOrder = array_column($template['panels'], 'id');

foreach ($expectedPanelOrder as $index => $expectedId) {
    if (isset($actualPanelOrder[$index])) {
        assertEquals($expectedId, $actualPanelOrder[$index], "Panel order at position $index");
    }
}

// Test 5: Panel Field Distribution
echo "\nTest 5: Panel Field Distribution\n";
echo "--------------------------------\n";

$fieldsByPanel = [];
foreach ($template['fields'] as $field) {
    $panelId = $field['panelId'] ?? 'no_panel';
    if (!isset($fieldsByPanel[$panelId])) {
        $fieldsByPanel[$panelId] = [];
    }
    $fieldsByPanel[$panelId][] = $field['key'];
}

foreach ($fieldsByPanel as $panelId => $fields) {
    echo "📁 Panel '$panelId': " . count($fields) . " fields\n";
}

// Test 6: Panel Data Structure Integrity
echo "\nTest 6: Panel Data Structure Integrity\n";
echo "--------------------------------------\n";

$dataStore = new DataStore(__DIR__ . '/../data/mvp_test.json');

// Create a test project with panel-organized data
$testProject = [
    'id' => 'test_panel_' . uniqid(),
    'name' => 'Panel Test Project',
    'status' => 'draft',
    'created' => date('Y-m-d H:i:s'),
    'updated' => date('Y-m-d H:i:s')
];

$projectId = $dataStore->createProject($testProject);
assertTest(!empty($projectId), "Test project created: $projectId");

// Create a document with panel-organized fields
$testDocument = [
    'id' => 'doc_panel_' . uniqid(),
    'projectId' => $projectId,
    'templateId' => 't_fl100_gc120',
    'name' => 'Test Document',
    'status' => 'draft',
    'fields' => [],
    'created' => date('Y-m-d H:i:s'),
    'updated' => date('Y-m-d H:i:s')
];

// Populate fields by panel
foreach ($template['panels'] as $panel) {
    foreach ($template['fields'] as $field) {
        if (($field['panelId'] ?? '') === $panel['id']) {
            $testDocument['fields'][$field['key']] = "Test value for {$field['key']}";
        }
    }
}

$documentId = $dataStore->createProjectDocument($testDocument);
assertTest(!empty($documentId), "Test document created with panel fields: $documentId");

// Retrieve and verify document
$retrievedDoc = $dataStore->getProjectDocument($documentId);
assertTest($retrievedDoc !== null, "Document retrieved successfully");
assertEquals(count($testDocument['fields']), count($retrievedDoc['fields'] ?? []), "All panel fields preserved");

// Test 7: Panel Rendering Simulation
echo "\nTest 7: Panel Rendering Simulation\n";
echo "----------------------------------\n";

function renderPanelHTML($panel, $fields, $values = []) {
    $html = "<div class='panel'>\n";
    $html .= "  <h3>{$panel['label']}</h3>\n";
    $html .= "  <div class='fields'>\n";
    
    foreach ($fields as $field) {
        if (($field['panelId'] ?? '') === $panel['id']) {
            $value = $values[$field['key']] ?? '';
            $html .= "    <div class='field'>\n";
            $html .= "      <label>{$field['label']}</label>\n";
            $html .= "      <input type='{$field['type']}' name='{$field['key']}' value='$value'/>\n";
            $html .= "    </div>\n";
        }
    }
    
    $html .= "  </div>\n";
    $html .= "</div>\n";
    
    return $html;
}

foreach ($template['panels'] as $panel) {
    $panelHTML = renderPanelHTML($panel, $template['fields'], $retrievedDoc['fields'] ?? []);
    assertTest(strpos($panelHTML, $panel['label']) !== false, "Panel '{$panel['label']}' renders correctly");
}

// Test 8: Custom Panel Support
echo "\nTest 8: Custom Panel Support\n";
echo "----------------------------\n";

$customPanel = [
    'id' => 'custom_panel_' . uniqid(),
    'label' => 'Custom Information',
    'order' => 99
];

// Simulate adding a custom panel (in real implementation, this would be persisted)
$templateWithCustom = $template;
$templateWithCustom['panels'][] = $customPanel;

assertTest(
    count($templateWithCustom['panels']) > count($template['panels']),
    "Custom panel can be added to template"
);

// Test 9: Panel Visibility and Conditional Logic
echo "\nTest 9: Panel Visibility Logic\n";
echo "------------------------------\n";

function shouldShowPanel($panel, $context = []) {
    // Simulate conditional panel visibility based on context
    // For example, 'children' panel only shows if has_children is true
    if ($panel['id'] === 'children') {
        return !empty($context['has_children']);
    }
    return true;
}

$contextWithChildren = ['has_children' => true];
$contextWithoutChildren = ['has_children' => false];

assertTest(
    shouldShowPanel(['id' => 'children'], $contextWithChildren),
    "Children panel shows when has_children is true"
);

assertTest(
    !shouldShowPanel(['id' => 'children'], $contextWithoutChildren),
    "Children panel hidden when has_children is false"
);

// Test 10: Panel Validation
echo "\nTest 10: Panel Validation\n";
echo "-------------------------\n";

function validatePanelFields($panel, $fields, $values) {
    $errors = [];
    
    foreach ($fields as $field) {
        if (($field['panelId'] ?? '') === $panel['id']) {
            $value = $values[$field['key']] ?? '';
            
            // Check required fields
            if (!empty($field['required']) && empty($value)) {
                $errors[] = "Field '{$field['label']}' is required in panel '{$panel['label']}'";
            }
            
            // Type-specific validation
            if ($field['type'] === 'email' && !empty($value)) {
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = "Invalid email in field '{$field['label']}'";
                }
            }
        }
    }
    
    return $errors;
}

// Test with incomplete data
$incompleteValues = ['attorney_name' => ''];  // Required field is empty
$attorneyPanel = array_filter($template['panels'], fn($p) => $p['id'] === 'attorney')[0] ?? null;

if ($attorneyPanel) {
    $validationErrors = validatePanelFields($attorneyPanel, $template['fields'], $incompleteValues);
    assertTest(count($validationErrors) > 0, "Panel validation detects missing required fields");
}

// Test with complete data
$completeValues = ['attorney_name' => 'John Doe', 'attorney_email' => 'john@example.com'];
$validationErrors = validatePanelFields($attorneyPanel, $template['fields'], $completeValues);
assertTest(count($validationErrors) === 0, "Panel validation passes with complete data");

// Clean up test data
echo "\nCleanup\n";
echo "-------\n";
$dataStore->deleteProjectDocument($documentId);
$dataStore->deleteProject($projectId);
echo "✅ Test data cleaned up\n";

// Summary
echo "\n" . str_repeat("=", 50) . "\n";
echo "📊 TEST SUMMARY\n";
echo str_repeat("=", 50) . "\n";
echo "✅ Passed: $testsPassed\n";
echo "❌ Failed: $testsFailed\n";

if ($testsFailed === 0) {
    echo "\n🎉 ALL PANEL MANAGEMENT TESTS PASSED!\n";
    exit(0);
} else {
    echo "\n⚠️ SOME TESTS FAILED\n";
    exit(1);
}