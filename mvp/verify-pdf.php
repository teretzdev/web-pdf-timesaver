<?php
/**
 * Standalone PDF Verification Script
 * Run via: php verify-pdf.php t_fl100_gc120
 * Or via web: ?route=automated-verify&template_id=t_fl100_gc120
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/lib/field_position_loader.php';
require_once __DIR__ . '/lib/fl100_test_data_generator.php';
require_once __DIR__ . '/lib/field_name_mapper.php';
require_once __DIR__ . '/lib/pdf_form_filler.php';
require_once __DIR__ . '/lib/position_debug_generator.php';
require_once __DIR__ . '/lib/automated_verification_pipeline.php';

$templateId = $argv[1] ?? $_GET['template_id'] ?? 't_fl100_gc120';

echo "=== AUTOMATED PDF VERIFICATION PIPELINE ===\n";
echo "Template ID: $templateId\n";
echo "Starting verification...\n\n";

$pipeline = new \WebPdfTimeSaver\Mvp\AutomatedVerificationPipeline();
$results = $pipeline->verify($templateId);

echo "\n=== VERIFICATION RESULTS ===\n";
echo "Overall Status: " . $results['overall_status'] . "\n";
echo "Total Tests: " . $results['summary']['total_tests'] . "\n";
echo "Passed: " . $results['summary']['passed'] . "\n";
echo "Failed: " . $results['summary']['failed'] . "\n\n";

echo "=== TEST DETAILS ===\n";
foreach ($results['tests'] as $testName => $testResult) {
    $icon = $testResult['passed'] ? '✅' : '❌';
    echo "$icon $testName: " . ($testResult['message'] ?? '') . "\n";
    
    if (!empty($testResult['issues'])) {
        foreach ($testResult['issues'] as $issue) {
            echo "  ⚠️  $issue\n";
        }
    }
}

if (isset($results['report']['html_path'])) {
    echo "\n📄 Full report: " . $results['report']['html_path'] . "\n";
}

exit($results['overall_status'] === 'PASS' ? 0 : 1);

