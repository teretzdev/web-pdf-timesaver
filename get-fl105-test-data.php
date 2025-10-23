<?php
/**
 * Helper to get FL-105 test data as JSON
 */

require_once __DIR__ . '/mvp/lib/fl105_test_data_generator.php';

header('Content-Type: application/json');

$data = \WebPdfTimeSaver\Mvp\FL105TestDataGenerator::generateCompleteTestData();

echo json_encode($data, JSON_PRETTY_PRINT);
?>
