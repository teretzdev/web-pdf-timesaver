<?php
/**
 * Run Master Verification on All Templates
 * Usage: php mvp/run-master-verification.php
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/lib/field_position_loader.php';
require_once __DIR__ . '/lib/fl100_test_data_generator.php';
require_once __DIR__ . '/lib/field_name_mapper.php';
require_once __DIR__ . '/lib/pdf_form_filler.php';
require_once __DIR__ . '/lib/position_debug_generator.php';
require_once __DIR__ . '/lib/automated_verification_pipeline.php';
require_once __DIR__ . '/lib/master_verification_report.php';

echo "===========================================\n";
echo "MASTER VERIFICATION - ALL TEMPLATES\n";
echo "===========================================\n";
echo "\n";

$report = new \WebPdfTimeSaver\Mvp\MasterVerificationReport();
$results = $report->generateMasterReport();

echo "✅ MASTER REPORT GENERATED!\n";
echo "\n";
echo "Summary:\n";
echo "  Total Templates: " . $results['results']['summary']['total'] . "\n";
echo "  Passed: " . $results['results']['summary']['passed'] . "\n";
echo "  Failed: " . $results['results']['summary']['failed'] . "\n";
echo "\n";
echo "Reports:\n";
echo "  HTML: " . $results['html_path'] . "\n";
echo "  JSON: " . $results['json_path'] . "\n";
echo "\n";

// Show detailed results
echo "Template Status:\n";
foreach ($results['results']['templates'] as $templateId => $template) {
    $icon = $template['status'] === 'PASS' ? '✅' : '❌';
    echo "  $icon $templateId: " . $template['status'];
    if (isset($template['field_count'])) {
        echo " ({$template['field_count']} fields, " . number_format($template['mapping_rate'], 1) . "% mapped)";
    }
    echo "\n";
}

echo "\n";
echo "Opening master report...\n";
if (file_exists($results['html_path'])) {
    exec('start "" "' . $results['html_path'] . '"');
}

