<?php
/**
 * Automatic Extraction and Verification Script
 * Usage: php mvp/auto-extract-and-verify.php [template_id] [pdf_path]
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/lib/pdf_field_extractor.php';
require_once __DIR__ . '/lib/automated_verification_pipeline.php';
require_once __DIR__ . '/lib/field_position_loader.php';
require_once __DIR__ . '/lib/fl100_test_data_generator.php';
require_once __DIR__ . '/lib/field_name_mapper.php';
require_once __DIR__ . '/lib/pdf_form_filler.php';
require_once __DIR__ . '/lib/position_debug_generator.php';

$templateId = $argv[1] ?? 't_fl100_gc120';
$pdfPath = $argv[2] ?? __DIR__ . '/../uploads/fl100.pdf';

echo "===========================================\n";
echo "AUTOMATIC EXTRACTION AND VERIFICATION\n";
echo "===========================================\n";
echo "\n";
echo "Template: $templateId\n";
echo "PDF: $pdfPath\n";
echo "\n";

if (!file_exists($pdfPath)) {
    die("ERROR: PDF file not found: $pdfPath\n");
}

// Step 1: Extract fields
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "STEP 1: FIELD EXTRACTION\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "\n";

$extractor = new \WebPdfTimeSaver\Mvp\PdfFieldExtractor();
$result = $extractor->extractAndGenerateBackgrounds($pdfPath, $templateId, __DIR__ . '/../uploads', true);

echo "✅ Extraction completed!\n";
echo "   Fields extracted: " . count($result['fields']) . "\n";
echo "   Backgrounds generated: " . count($result['backgrounds']) . "\n";
echo "   Position file: " . ($result['positionFile'] ? basename($result['positionFile']) : 'N/A') . "\n";
echo "\n";

// Step 2: Automatic verification (already done in extractAndGenerateBackgrounds if autoVerify=true)
if (isset($result['verification'])) {
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "STEP 2: AUTOMATIC VERIFICATION\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "\n";
    
    $verify = $result['verification'];
    echo "✅ Verification completed!\n";
    echo "   Overall Status: " . $verify['overall_status'] . "\n";
    echo "   Total Tests: " . $verify['summary']['total_tests'] . "\n";
    echo "   Passed: " . $verify['summary']['passed'] . "\n";
    echo "   Failed: " . $verify['summary']['failed'] . "\n";
    echo "\n";
    
    // Show test details
    echo "Test Results:\n";
    foreach ($verify['tests'] as $testName => $testResult) {
        $icon = ($testResult['passed'] ?? false) ? '✅' : '❌';
        echo "   $icon " . ucfirst(str_replace('_', ' ', $testName)) . ": " . ($testResult['message'] ?? '') . "\n";
    }
    echo "\n";
    
    if (isset($verify['report']['html_path'])) {
        echo "📄 Report: " . $verify['report']['html_path'] . "\n";
    }
} else {
    // Run verification separately if not already done
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "STEP 2: VERIFICATION\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "\n";
    
    $pipeline = new \WebPdfTimeSaver\Mvp\AutomatedVerificationPipeline();
    $verifyResults = $pipeline->verify($templateId);
    
    echo "✅ Verification completed!\n";
    echo "   Overall Status: " . $verifyResults['overall_status'] . "\n";
    echo "   Total Tests: " . $verifyResults['summary']['total_tests'] . "\n";
    echo "   Passed: " . $verifyResults['summary']['passed'] . "\n";
    echo "   Failed: " . $verifyResults['summary']['failed'] . "\n";
    echo "\n";
    
    if (isset($verifyResults['report']['html_path'])) {
        echo "📄 Report: " . $verifyResults['report']['html_path'] . "\n";
    }
}

echo "\n";
echo "===========================================\n";
echo "✅ EXTRACTION AND VERIFICATION COMPLETE!\n";
echo "===========================================\n";

