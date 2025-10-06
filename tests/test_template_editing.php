<?php
declare(strict_types=1);

/**
 * Template Editing Test Suite
 * 
 * Tests template management functionality similar to Clio Draft:
 * - Template creation and modification
 * - Field management within templates
 * - Panel organization
 * - Template versioning
 * - Template cloning
 */

require_once __DIR__ . '/../mvp/lib/data.php';
require_once __DIR__ . '/../mvp/templates/registry.php';

use WebPdfTimeSaver\Mvp\DataStore;
use WebPdfTimeSaver\Mvp\TemplateRegistry;

echo "📝 Testing Template Editing System\n";
echo "==================================\n\n";

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

function assertNotNull($value, $message) {
    assertTest($value !== null, "$message - Value is not null");
}

function assertEquals($expected, $actual, $message) {
    assertTest($expected === $actual, "$message - Expected: $expected, Got: $actual");
}

function assertContains($needle, $haystack, $message) {
    assertTest(in_array($needle, $haystack), "$message - Contains expected value");
}

// Test 1: Template Structure Validation
echo "Test 1: Template Structure Validation\n";
echo "-------------------------------------\n";

function validateTemplateStructure($template) {
    $required = ['id', 'code', 'name', 'panels', 'fields'];
    $missing = [];
    
    foreach ($required as $field) {
        if (!isset($template[$field])) {
            $missing[] = $field;
        }
    }
    
    return ['valid' => empty($missing), 'missing' => $missing];
}

$templates = TemplateRegistry::load();
$template = $templates['t_fl100_gc120'] ?? null;
assertNotNull($template, "Base template loaded");

$validation = validateTemplateStructure($template);
assertTest($validation['valid'], "Template has all required fields");

// Test 2: Field Type Support
echo "\nTest 2: Field Type Support\n";
echo "--------------------------\n";

$supportedFieldTypes = ['text', 'textarea', 'number', 'date', 'checkbox', 'select', 'radio', 'email', 'tel'];
$usedTypes = array_unique(array_column($template['fields'], 'type'));

echo "📋 Field types in use: " . implode(', ', $usedTypes) . "\n";
foreach ($usedTypes as $type) {
    assertContains($type, $supportedFieldTypes, "Field type '$type' is supported");
}

// Test 3: Field Property Management
echo "\nTest 3: Field Property Management\n";
echo "---------------------------------\n";

function createField($key, $label, $type, $panelId, $options = []) {
    $field = [
        'key' => $key,
        'label' => $label,
        'type' => $type,
        'panelId' => $panelId
    ];
    
    // Add optional properties
    foreach (['required', 'placeholder', 'options', 'pdfTarget'] as $optProp) {
        if (isset($options[$optProp])) {
            $field[$optProp] = $options[$optProp];
        }
    }
    
    return $field;
}

$testField = createField(
    'test_field',
    'Test Field',
    'text',
    'attorney',
    ['required' => true, 'placeholder' => 'Enter test value']
);

assertTest(isset($testField['key']), "Field created with key");
assertTest($testField['required'] === true, "Optional property 'required' set correctly");
assertTest($testField['placeholder'] === 'Enter test value', "Optional property 'placeholder' set correctly");

// Test 4: Template Modification Simulation
echo "\nTest 4: Template Modification\n";
echo "-----------------------------\n";

function cloneTemplate($template, $newId, $newName) {
    $clone = $template;
    $clone['id'] = $newId;
    $clone['name'] = $newName;
    $clone['version'] = '1.0-clone';
    $clone['clonedFrom'] = $template['id'];
    $clone['clonedAt'] = date('Y-m-d H:i:s');
    return $clone;
}

$clonedTemplate = cloneTemplate($template, 't_fl100_clone', 'FL-100 Clone');
assertTest($clonedTemplate['id'] !== $template['id'], "Template cloned with new ID");
assertTest($clonedTemplate['clonedFrom'] === $template['id'], "Clone tracks original template");

// Test 5: Panel Management in Templates
echo "\nTest 5: Panel Management\n";
echo "------------------------\n";

function addPanelToTemplate(&$template, $panelId, $panelLabel, $order = 999) {
    $newPanel = [
        'id' => $panelId,
        'label' => $panelLabel,
        'order' => $order
    ];
    
    $template['panels'][] = $newPanel;
    return $newPanel;
}

function removePanelFromTemplate(&$template, $panelId) {
    // Remove panel
    $template['panels'] = array_values(array_filter(
        $template['panels'],
        fn($p) => $p['id'] !== $panelId
    ));
    
    // Update affected fields
    foreach ($template['fields'] as &$field) {
        if (($field['panelId'] ?? '') === $panelId) {
            $field['panelId'] = '';  // Orphaned fields
        }
    }
}

$testTemplate = $clonedTemplate;
$originalPanelCount = count($testTemplate['panels']);

// Add a custom panel
$customPanel = addPanelToTemplate($testTemplate, 'custom_test', 'Custom Test Panel');
assertTest(count($testTemplate['panels']) === $originalPanelCount + 1, "Panel added to template");

// Remove the panel
removePanelFromTemplate($testTemplate, 'custom_test');
assertTest(count($testTemplate['panels']) === $originalPanelCount, "Panel removed from template");

// Test 6: Field Ordering Within Panels
echo "\nTest 6: Field Ordering\n";
echo "----------------------\n";

function getFieldsForPanel($template, $panelId) {
    return array_filter(
        $template['fields'],
        fn($f) => ($f['panelId'] ?? '') === $panelId
    );
}

function reorderFields(&$fields, $order) {
    $reordered = [];
    foreach ($order as $key) {
        foreach ($fields as $field) {
            if ($field['key'] === $key) {
                $reordered[] = $field;
                break;
            }
        }
    }
    return $reordered;
}

$attorneyFields = getFieldsForPanel($template, 'attorney');
$originalCount = count($attorneyFields);
assertTest($originalCount > 0, "Attorney panel has fields");

$fieldKeys = array_column($attorneyFields, 'key');
$reversedOrder = array_reverse($fieldKeys);
$reorderedFields = reorderFields($attorneyFields, $reversedOrder);
assertEquals($originalCount, count($reorderedFields), "Field reordering preserves all fields");

// Test 7: Template Validation Rules
echo "\nTest 7: Template Validation Rules\n";
echo "---------------------------------\n";

function validateTemplate($template) {
    $errors = [];
    
    // Check for duplicate field keys
    $fieldKeys = array_column($template['fields'], 'key');
    $uniqueKeys = array_unique($fieldKeys);
    if (count($fieldKeys) !== count($uniqueKeys)) {
        $errors[] = "Duplicate field keys detected";
    }
    
    // Check for duplicate panel IDs
    $panelIds = array_column($template['panels'], 'id');
    $uniquePanels = array_unique($panelIds);
    if (count($panelIds) !== count($uniquePanels)) {
        $errors[] = "Duplicate panel IDs detected";
    }
    
    // Check for orphaned fields
    foreach ($template['fields'] as $field) {
        if (!empty($field['panelId']) && !in_array($field['panelId'], $panelIds)) {
            $errors[] = "Field '{$field['key']}' references non-existent panel '{$field['panelId']}'";
        }
    }
    
    // Check required field properties
    foreach ($template['fields'] as $field) {
        if (empty($field['key'])) {
            $errors[] = "Field missing required 'key' property";
        }
        if (empty($field['label'])) {
            $errors[] = "Field '{$field['key']}' missing required 'label' property";
        }
    }
    
    return $errors;
}

$validationErrors = validateTemplate($template);
assertTest(empty($validationErrors), "Template passes all validation rules");

// Test 8: Field Dependencies
echo "\nTest 8: Field Dependencies\n";
echo "--------------------------\n";

function addFieldDependency(&$field, $dependsOn, $condition) {
    $field['dependencies'] = [
        'field' => $dependsOn,
        'condition' => $condition
    ];
}

$dependentField = createField('spouse_name', 'Spouse Name', 'text', 'marriage');
addFieldDependency($dependentField, 'marital_status', 'married');

assertTest(
    isset($dependentField['dependencies']),
    "Field dependency added"
);
assertTest(
    $dependentField['dependencies']['field'] === 'marital_status',
    "Dependency references correct field"
);

// Test 9: Template Import/Export Simulation
echo "\nTest 9: Template Import/Export\n";
echo "------------------------------\n";

function exportTemplate($template) {
    // Simulate export to JSON
    return json_encode($template, JSON_PRETTY_PRINT);
}

function importTemplate($json) {
    // Simulate import from JSON
    $imported = json_decode($json, true);
    
    // Validate imported structure
    if (!$imported || !isset($imported['id'])) {
        return null;
    }
    
    // Add import metadata
    $imported['importedAt'] = date('Y-m-d H:i:s');
    
    return $imported;
}

$exported = exportTemplate($template);
assertTest(!empty($exported), "Template exported to JSON");

$imported = importTemplate($exported);
assertNotNull($imported, "Template imported from JSON");
assertEquals($template['id'], $imported['id'], "Imported template preserves ID");
assertEquals(count($template['fields']), count($imported['fields']), "Imported template preserves all fields");

// Test 10: Template Versioning
echo "\nTest 10: Template Versioning\n";
echo "----------------------------\n";

function createTemplateVersion($template, $version, $changes = []) {
    $versioned = $template;
    $versioned['version'] = $version;
    $versioned['versionHistory'] = $template['versionHistory'] ?? [];
    $versioned['versionHistory'][] = [
        'version' => $template['version'] ?? '1.0',
        'timestamp' => date('Y-m-d H:i:s'),
        'changes' => $changes
    ];
    return $versioned;
}

$v2Template = createTemplateVersion($template, '2.0', ['Added new fields', 'Updated panel structure']);
assertTest($v2Template['version'] === '2.0', "Template version updated");
assertTest(isset($v2Template['versionHistory']), "Version history maintained");

// Test 11: Bulk Field Operations
echo "\nTest 11: Bulk Field Operations\n";
echo "------------------------------\n";

function bulkUpdateFields(&$template, $panelId, $updates) {
    $updated = 0;
    foreach ($template['fields'] as &$field) {
        if (($field['panelId'] ?? '') === $panelId) {
            foreach ($updates as $prop => $value) {
                $field[$prop] = $value;
            }
            $updated++;
        }
    }
    return $updated;
}

$testTemplate2 = $template;
$updatedCount = bulkUpdateFields($testTemplate2, 'attorney', ['required' => true]);
assertTest($updatedCount > 0, "Bulk update applied to $updatedCount fields");

// Verify bulk update
$attorneyFields = getFieldsForPanel($testTemplate2, 'attorney');
$allRequired = true;
foreach ($attorneyFields as $field) {
    if (empty($field['required'])) {
        $allRequired = false;
        break;
    }
}
assertTest($allRequired, "All attorney fields marked as required");

// Test 12: Template Metadata
echo "\nTest 12: Template Metadata\n";
echo "--------------------------\n";

function addTemplateMetadata(&$template, $metadata) {
    $template['metadata'] = array_merge($template['metadata'] ?? [], $metadata);
}

addTemplateMetadata($template, [
    'author' => 'System',
    'lastModified' => date('Y-m-d H:i:s'),
    'category' => 'Legal Forms',
    'tags' => ['divorce', 'family-law', 'california']
]);

assertTest(isset($template['metadata']), "Metadata added to template");
assertTest($template['metadata']['category'] === 'Legal Forms', "Template categorized correctly");
assertContains('family-law', $template['metadata']['tags'], "Template tagged appropriately");

// Summary
echo "\n" . str_repeat("=", 50) . "\n";
echo "📊 TEMPLATE EDITING TEST SUMMARY\n";
echo str_repeat("=", 50) . "\n";
echo "✅ Passed: $testsPassed\n";
echo "❌ Failed: $testsFailed\n";

if ($testsFailed === 0) {
    echo "\n🎉 ALL TEMPLATE EDITING TESTS PASSED!\n";
    exit(0);
} else {
    echo "\n⚠️ SOME TESTS FAILED\n";
    exit(1);
}