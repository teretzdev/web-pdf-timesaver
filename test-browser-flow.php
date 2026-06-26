<?php
/**
 * Test the complete browser upload flow
 * This simulates what happens when a PDF is uploaded via browser
 */

declare(strict_types=1);

// Capture output
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/vendor/autoload.php';

// Simulate file upload (for reference, but we're testing extraction directly)
$_SERVER['REQUEST_METHOD'] = 'POST';
$_FILES['pdf'] = [
    'name' => 'w9.pdf',
    'type' => 'application/pdf',
    'tmp_name' => __DIR__ . '/uploads/w9.pdf',
    'error' => UPLOAD_ERR_OK,
    'size' => filesize(__DIR__ . '/uploads/w9.pdf')
];
$_POST['template_id'] = 't_w9_browser_flow_test';

echo "=== Testing Browser Upload Flow ===\n\n";
echo "Simulating upload of: w9.pdf\n";
echo "Template ID: t_w9_browser_flow_test\n\n";

// Check if file exists
if (!file_exists($_FILES['pdf']['tmp_name'])) {
    die("ERROR: Test PDF not found: " . $_FILES['pdf']['tmp_name'] . "\n");
}

// Run the extraction (this will output JSON)
try {
    // Include the extract-fields.php logic
    require_once __DIR__ . '/mvp/lib/auto_position_extractor.php';
    
    $extractor = new \WebPdfTimeSaver\Mvp\AutoPositionExtractor();
    
    if (!$extractor->isAvailable()) {
        die("ERROR: Node.js not available\n");
    }
    
    echo "Node.js available, running extraction...\n\n";
    
    $result = $extractor->extractPositions($_FILES['pdf']['tmp_name'], $_POST['template_id']);
    
    echo "\n=== EXTRACTION RESULTS ===\n";
    echo "Success: " . ($result['success'] ? 'YES' : 'NO') . "\n";
    echo "Fields: " . count($result['fields'] ?? []) . "\n";
    echo "Method: " . ($result['method'] ?? 'unknown') . "\n";
    
    // Check if ensemble was used
    $isEnsemble = false;
    if (isset($result['method']) && strpos($result['method'], 'ensemble') !== false) {
        $isEnsemble = true;
        echo "✅ ENSEMBLE EXTRACTION DETECTED!\n";
    }
    
    if (isset($result['methodsUsed']) && is_array($result['methodsUsed'])) {
        $methodsCount = count($result['methodsUsed']);
        echo "Methods used (" . $methodsCount . "): " . implode(', ', $result['methodsUsed']) . "\n";
        
        if ($methodsCount > 1) {
            echo "✅ Multiple methods used - Ensemble is working!\n";
        }
    }
    
    if (isset($result['fieldsPerMethod']) && is_array($result['fieldsPerMethod'])) {
        echo "\nFields per method:\n";
        foreach ($result['fieldsPerMethod'] as $method => $count) {
            echo "  - $method: $count fields\n";
        }
    }
    
    if (!empty($result['errors'])) {
        echo "\nErrors: " . implode(', ', $result['errors']) . "\n";
    }
    
    if (!empty($result['warnings'])) {
        echo "\nWarnings: " . implode(', ', $result['warnings']) . "\n";
    }
    
    // Check files
    $dataDir = __DIR__ . '/data';
    $detailsFile = $dataDir . '/' . $_POST['template_id'] . '_extraction_details.json';
    $positionFile = $dataDir . '/' . $_POST['template_id'] . '_positions.json';
    
    echo "\n=== FILES CREATED ===\n";
    echo "Details file: " . (file_exists($detailsFile) ? 'YES' : 'NO') . "\n";
    echo "Position file: " . (file_exists($positionFile) ? 'YES' : 'NO') . "\n";
    
    if (file_exists($detailsFile)) {
        $details = json_decode(file_get_contents($detailsFile), true);
        echo "Details file fields: " . count($details['fields'] ?? []) . "\n";
    }
    
    if (file_exists($positionFile)) {
        $positions = json_decode(file_get_contents($positionFile), true);
        echo "Position file fields: " . count($positions ?? []) . "\n";
    }
    
    if (empty($result['fields'])) {
        echo "\n❌ FAILED: No fields extracted!\n";
        exit(1);
    } else {
        echo "\n✅ SUCCESS: " . count($result['fields']) . " fields extracted!\n";
        
        // Verify ensemble metadata
        if ($isEnsemble) {
            echo "✅ Ensemble extraction confirmed - multiple methods combined results\n";
        } elseif (isset($result['methodsUsed']) && count($result['methodsUsed']) > 1) {
            echo "✅ Multiple methods used (ensemble-like behavior)\n";
        } else {
            echo "⚠️  Only single method used - ensemble may not be active\n";
        }
    }
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

$output = ob_get_clean();
file_put_contents(__DIR__ . '/browser-flow-test-output.txt', $output);
echo $output;


