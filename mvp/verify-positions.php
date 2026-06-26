<?php
/**
 * Position Verification Tool
 * 
 * Usage: php verify-positions.php [template_id] [pdf_path]
 * 
 * This tool verifies 100% accuracy of text layer positions by:
 * 1. Loading expected positions from JSON file
 * 2. Generating a test PDF with known values
 * 3. Extracting actual text positions from the PDF
 * 4. Comparing expected vs actual positions
 * 5. Generating a detailed report and visual overlay
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/lib/position_verifier.php';
require_once __DIR__ . '/lib/fl100_test_data_generator.php';
require_once __DIR__ . '/lib/pdf_form_filler.php';
require_once __DIR__ . '/lib/field_position_loader.php';

use WebPdfTimeSaver\Mvp\PositionVerifier;
use WebPdfTimeSaver\Mvp\PdfFormFiller;
use WebPdfTimeSaver\Mvp\FieldPositionLoader;
use WebPdfTimeSaver\Mvp\FL100TestDataGenerator;

// Get template ID from command line or default
$templateId = $argv[1] ?? 't_fl100_gc120';
$pdfPath = $argv[2] ?? null;

echo "=== Position Verification Tool ===\n\n";
echo "Template ID: $templateId\n";

// Load expected positions
$positionsFile = __DIR__ . '/../data/' . $templateId . '_positions.json';
if (!file_exists($positionsFile)) {
    die("ERROR: Position file not found: $positionsFile\n");
}

$expectedPositions = json_decode(file_get_contents($positionsFile), true);
if (!$expectedPositions) {
    die("ERROR: Could not parse position file\n");
}

echo "Loaded " . count($expectedPositions) . " expected positions\n";

// Generate test data
$testData = FL100TestDataGenerator::generateCompleteTestData();
echo "Generated test data for " . count($testData) . " fields\n\n";

// Generate PDF if not provided
if (!$pdfPath) {
    echo "Generating test PDF...\n";
    
    $filler = new PdfFormFiller();
    $template = [
        'id' => $templateId,
        'fields' => []
    ];
    
    // Convert positions to template fields format
    foreach ($expectedPositions as $fieldName => $position) {
        $template['fields'][] = [
            'key' => $fieldName,
            'type' => $position['type'] ?? 'text',
            'label' => $fieldName
        ];
    }
    
    try {
        $result = $filler->fillPdfForm($template, $testData);
        $pdfPath = $result['path'] ?? null;
        
        if (!$pdfPath || !file_exists($pdfPath)) {
            die("ERROR: Failed to generate PDF\n");
        }
        
        echo "Generated PDF: $pdfPath\n\n";
    } catch (\Exception $e) {
        die("ERROR: Failed to generate PDF: " . $e->getMessage() . "\n");
    }
} else {
    if (!file_exists($pdfPath)) {
        die("ERROR: PDF file not found: $pdfPath\n");
    }
    echo "Using provided PDF: $pdfPath\n\n";
}

// Run verification
echo "Running verification...\n";
$verifier = new PositionVerifier();

try {
    $report = $verifier->verifyPdfPositions($pdfPath, $expectedPositions, $testData);
    
    // Display results
    echo "\n=== Verification Results ===\n";
    echo "Overall Accuracy: " . $report['overallAccuracy'] . "%\n";
    echo "Status: " . $report['summary']['status'] . "\n";
    echo "Fields Verified: " . $report['fieldsVerified'] . "\n";
    echo "Fields Matched: " . $report['fieldsMatched'] . "\n";
    echo "Fields Mismatched: " . $report['fieldsMismatched'] . "\n";
    echo "Fields Missing: " . $report['fieldsMissing'] . "\n";
    
    if (!empty($report['issues'])) {
        echo "\n=== Issues Found ===\n";
        foreach ($report['issues'] as $issue) {
            echo "[" . $issue['severity'] . "] " . $issue['message'] . "\n";
        }
    }
    
    // Generate visual overlay
    $overlayPath = __DIR__ . '/../uploads/verification/overlay_' . basename($pdfPath, '.pdf') . '.html';
    $verifier->generateVisualOverlay($pdfPath, $expectedPositions, $testData, $overlayPath);
    echo "\nVisual overlay generated: $overlayPath\n";
    echo "Open this file in a browser to see expected vs actual positions\n";
    
    // Save detailed report
    $reportPath = __DIR__ . '/../uploads/verification/report_' . date('Y-m-d_His') . '.json';
    $reportDir = dirname($reportPath);
    if (!is_dir($reportDir)) {
        mkdir($reportDir, 0755, true);
    }
    file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT));
    echo "Detailed report saved: $reportPath\n";
    
    // Exit code based on status
    if ($report['summary']['status'] === 'PASS') {
        echo "\n✅ VERIFICATION PASSED\n";
        exit(0);
    } elseif ($report['summary']['status'] === 'WARNING') {
        echo "\n⚠️  VERIFICATION PASSED WITH WARNINGS\n";
        exit(0);
    } else {
        echo "\n❌ VERIFICATION FAILED\n";
        exit(1);
    }
    
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

