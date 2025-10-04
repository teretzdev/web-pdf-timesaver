<?php
/**
 * Test script to verify PDF positions are working correctly
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../mvp/lib/pdf_form_filler.php';
require_once __DIR__ . '/../mvp/lib/field_position_loader.php';
require_once __DIR__ . '/../mvp/lib/logger.php';

use WebPdfTimeSaver\Mvp\PdfFormFiller;
use WebPdfTimeSaver\Mvp\FieldPositionLoader;
use WebPdfTimeSaver\Mvp\Logger;

echo "Testing PDF Positions\n";
echo "=====================\n\n";

// Test data
$testValues = [
    'case_number' => 'TEST-2025-001',
    'attorney_name' => 'John Doe',
    'attorney_bar_number' => '123456',
    'attorney_firm' => 'Doe & Associates Law Firm',
    'attorney_address' => '123 Main Street, Suite 100',
    'attorney_city' => 'Los Angeles',
    'attorney_state' => 'CA',
    'attorney_zip' => '90001',
    'attorney_phone' => '(555) 123-4567',
    'attorney_email' => 'john.doe@lawfirm.com',
    'court_county' => 'Los Angeles',
    'court_branch' => 'Stanley Mosk Courthouse',
    'petitioner_name' => 'Jane Smith',
    'petitioner_first_name' => 'Jane',
    'petitioner_last_name' => 'Smith',
    'respondent_name' => 'Robert Johnson',
    'respondent_first_name' => 'Robert',
    'respondent_last_name' => 'Johnson',
    'dissolution_marriage' => 'checked',
    'marriage_date' => '01/15/2010',
    'separation_date' => '06/01/2024',
    'child_name' => 'Emma Johnson',
    'child_birthdate' => '03/20/2015',
    'child_sex' => 'F',
    'date_signed' => date('m/d/Y')
];

// Template configuration
$template = [
    'id' => 't_fl100_gc120',
    'name' => 'FL-100 Test',
    'fields' => array_map(function($key) {
        return ['key' => $key, 'type' => 'text'];
    }, array_keys($testValues))
];

// Initialize services
$logger = new Logger();
$formFiller = new PdfFormFiller(__DIR__ . '/../output', __DIR__ . '/../uploads', $logger);

echo "Filling PDF with test data...\n";

try {
    // Test with positioned fields
    $result = $formFiller->fillPdfFormWithPositions($template, $testValues, 't_fl100_gc120');
    
    if ($result['success'] ?? false) {
        echo "✅ PDF created successfully!\n";
        echo "   File: " . ($result['file'] ?? $result['filename'] ?? 'unknown') . "\n";
        echo "   Path: " . ($result['path'] ?? 'unknown') . "\n";
        echo "   Used positions: " . ($result['used_positions'] ?? 0) . "\n";
    } else {
        echo "❌ Failed to create PDF\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

// Check positions file
echo "\nChecking positions file...\n";
$positionLoader = new FieldPositionLoader(__DIR__ . '/../data');
$positions = $positionLoader->loadFieldPositions('t_fl100_gc120');

if (!empty($positions)) {
    echo "Found " . count($positions) . " field positions:\n";
    foreach ($positions as $field => $info) {
        echo "  - $field: x={$info['x']}, y={$info['y']}, type={$info['type']}\n";
    }
} else {
    echo "❌ No positions found\n";
}

echo "\nTest complete!\n";