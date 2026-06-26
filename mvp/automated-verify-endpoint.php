<?php
/**
 * Standalone Automated Verification Endpoint
 * Access via: ?route=automated-verify&template_id=t_fl100_gc120
 * Or directly: automated-verify-endpoint.php?template_id=t_fl100_gc120
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/lib/automated_verification_pipeline.php';
require_once __DIR__ . '/lib/fl100_test_data_generator.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$templateId = $_GET['template_id'] ?? 't_fl100_gc120';

try {
    $pipeline = new \WebPdfTimeSaver\Mvp\AutomatedVerificationPipeline();
    $results = $pipeline->verify($templateId);
    
    echo json_encode($results, JSON_PRETTY_PRINT);
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}

