<?php
/**
 * FL-100 Form Workflow Verification Test
 * This script verifies that the FL-100 form is properly configured
 * and working in the workflow system as a 1:1 translation.
 */

require_once __DIR__ . '/../mvp/lib/data.php';
require_once __DIR__ . '/../mvp/templates/registry.php';
require_once __DIR__ . '/../mvp/lib/fl100_test_data_generator.php';

use WebPdfTimeSaver\Mvp\DataStore;
use WebPdfTimeSaver\Mvp\TemplateRegistry;
use WebPdfTimeSaver\Mvp\FL100TestDataGenerator;

echo "===========================================\n";
echo "FL-100 Form Workflow Verification Test\n";
echo "===========================================\n\n";

// 1. Load template registry
echo "1. Loading Template Registry...\n";
$templates = TemplateRegistry::load();
$fl100Template = $templates['t_fl100_gc120'] ?? null;

if (!$fl100Template) {
    die("ERROR: FL-100 template not found in registry!\n");
}
echo "✓ FL-100 template found\n";
echo "  - Code: " . $fl100Template['code'] . "\n";
echo "  - Name: " . $fl100Template['name'] . "\n";
echo "  - Panels: " . count($fl100Template['panels']) . "\n";
echo "  - Fields: " . count($fl100Template['fields']) . "\n\n";

// 2. Verify all panels are present
echo "2. Verifying Panels...\n";
$expectedPanels = [
    'attorney' => 'Attorney',
    'court' => 'Court',
    'parties' => 'Parties',
    'marriage' => 'Marriage Information',
    'relief' => 'Relief Requested',
    'children' => 'Children',
    'additional' => 'Additional Information'
];

foreach ($expectedPanels as $id => $label) {
    $found = false;
    foreach ($fl100Template['panels'] as $panel) {
        if ($panel['id'] === $id && $panel['label'] === $label) {
            $found = true;
            break;
        }
    }
    echo $found ? "  ✓ Panel '$label' found\n" : "  ✗ Panel '$label' missing\n";
}
echo "\n";

// 3. Verify all required fields are present
echo "3. Verifying Required Fields...\n";
$requiredFields = [
    'attorney_name' => 'Attorney Information',
    'attorney_firm' => 'Attorney Information',
    'attorney_address' => 'Attorney Information',
    'attorney_city_state_zip' => 'Attorney Information',
    'attorney_phone' => 'Attorney Information',
    'attorney_email' => 'Attorney Information',
    'attorney_bar_number' => 'Attorney Information',
    'case_number' => 'Court Information',
    'court_county' => 'Court Information',
    'court_address' => 'Court Information',
    'case_type' => 'Court Information',
    'filing_date' => 'Court Information',
    'petitioner_name' => 'Parties Information',
    'respondent_name' => 'Parties Information',
    'petitioner_address' => 'Parties Information',
    'petitioner_phone' => 'Parties Information',
    'respondent_address' => 'Parties Information',
    'marriage_date' => 'Marriage Information',
    'separation_date' => 'Marriage Information',
    'marriage_location' => 'Marriage Information',
    'grounds_for_dissolution' => 'Marriage Information',
    'dissolution_type' => 'Marriage Information',
    'property_division' => 'Relief Requested',
    'spousal_support' => 'Relief Requested',
    'attorney_fees' => 'Relief Requested',
    'name_change' => 'Relief Requested',
    'has_children' => 'Children Information',
    'children_count' => 'Children Information',
    'additional_info' => 'Additional Information',
    'attorney_signature' => 'Additional Information',
    'signature_date' => 'Additional Information'
];

$fieldCount = 0;
$missingFields = [];
foreach ($requiredFields as $key => $section) {
    $found = false;
    foreach ($fl100Template['fields'] as $field) {
        if ($field['key'] === $key) {
            $found = true;
            $fieldCount++;
            break;
        }
    }
    if (!$found) {
        $missingFields[] = "$key ($section)";
    }
}

echo "  Found $fieldCount/" . count($requiredFields) . " required fields\n";
if (!empty($missingFields)) {
    echo "  Missing fields:\n";
    foreach ($missingFields as $field) {
        echo "    - $field\n";
    }
} else {
    echo "  ✓ All required fields present\n";
}
echo "\n";

// 4. Test data generation
echo "4. Testing Data Generation...\n";
$testData = FL100TestDataGenerator::generateCompleteTestData();
$validation = FL100TestDataGenerator::validateCompleteData($testData);

echo "  Generated test data with " . count($testData) . " fields\n";
echo "  Validation results:\n";
echo "    - Complete: " . ($validation['is_complete'] ? 'Yes' : 'No') . "\n";
echo "    - Populated fields: " . $validation['populated_fields'] . "/" . $validation['total_fields'] . "\n";
if (!empty($validation['missing_fields'])) {
    echo "    - Missing: " . implode(', ', $validation['missing_fields']) . "\n";
}
if (!empty($validation['null_fields'])) {
    echo "    - Null: " . implode(', ', $validation['null_fields']) . "\n";
}
echo "\n";

// 5. Test workflow integration
echo "5. Testing Workflow Integration...\n";
$testStore = new DataStore(__DIR__ . '/../data/test_fl100_verification.json');

// Create test project
$project = $testStore->createProject('FL-100 Verification Test');
echo "  ✓ Created test project: " . $project['id'] . "\n";

// Add FL-100 document
$document = $testStore->addProjectDocument($project['id'], 't_fl100_gc120');
echo "  ✓ Added FL-100 document: " . $document['id'] . "\n";

// Save test field values
$testStore->saveFieldValues($document['id'], $testData);
$savedValues = $testStore->getFieldValues($document['id']);
echo "  ✓ Saved " . count($savedValues) . " field values\n";

// Verify values were saved correctly
$saveErrors = [];
foreach ($testData as $key => $value) {
    if (!isset($savedValues[$key]) || $savedValues[$key] != $value) {
        $saveErrors[] = $key;
    }
}

if (empty($saveErrors)) {
    echo "  ✓ All values saved and retrieved correctly\n";
} else {
    echo "  ✗ Errors with fields: " . implode(', ', $saveErrors) . "\n";
}

// Clean up test data
@unlink(__DIR__ . '/../data/test_fl100_verification.json');
echo "\n";

// 6. Field type verification
echo "6. Verifying Field Types...\n";
$fieldTypes = [
    'text' => 0,
    'date' => 0,
    'select' => 0,
    'checkbox' => 0,
    'textarea' => 0,
    'number' => 0
];

foreach ($fl100Template['fields'] as $field) {
    $type = $field['type'] ?? 'text';
    $fieldTypes[$type] = ($fieldTypes[$type] ?? 0) + 1;
}

echo "  Field type distribution:\n";
foreach ($fieldTypes as $type => $count) {
    if ($count > 0) {
        echo "    - $type: $count fields\n";
    }
}
echo "\n";

// 7. Panel field distribution
echo "7. Panel Field Distribution...\n";
$panelFields = [];
foreach ($fl100Template['fields'] as $field) {
    $panelId = $field['panelId'] ?? 'none';
    $panelFields[$panelId] = ($panelFields[$panelId] ?? 0) + 1;
}

foreach ($fl100Template['panels'] as $panel) {
    $count = $panelFields[$panel['id']] ?? 0;
    echo "  - " . $panel['label'] . ": $count fields\n";
}
echo "\n";

// 8. PDF mapping verification
echo "8. Verifying PDF Mappings...\n";
$mappedFields = 0;
$unmappedFields = [];
foreach ($fl100Template['fields'] as $field) {
    if (!empty($field['pdfTarget'])) {
        $mappedFields++;
    } else {
        $unmappedFields[] = $field['key'];
    }
}

echo "  Mapped fields: $mappedFields/" . count($fl100Template['fields']) . "\n";
if (!empty($unmappedFields)) {
    echo "  Unmapped fields: " . implode(', ', $unmappedFields) . "\n";
}
echo "\n";

// Summary
echo "===========================================\n";
echo "VERIFICATION SUMMARY\n";
echo "===========================================\n";
$allChecks = [
    "Template loaded" => $fl100Template !== null,
    "All panels present" => count($fl100Template['panels']) === 7,
    "All required fields present" => empty($missingFields),
    "Test data generation works" => $validation['is_complete'],
    "Workflow integration works" => empty($saveErrors),
    "PDF mappings configured" => $mappedFields === count($fl100Template['fields'])
];

$passed = 0;
$failed = 0;
foreach ($allChecks as $check => $result) {
    if ($result) {
        echo "✓ $check\n";
        $passed++;
    } else {
        echo "✗ $check\n";
        $failed++;
    }
}

echo "\n";
echo "Results: $passed passed, $failed failed\n";
echo "\n";

if ($failed === 0) {
    echo "SUCCESS: FL-100 form is properly configured as a 1:1 translation!\n";
    echo "The form is ready for use in the workflow system.\n";
} else {
    echo "WARNING: Some verifications failed. Please review the details above.\n";
}
echo "\n";