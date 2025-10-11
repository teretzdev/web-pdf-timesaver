<?php
/**
 * Simple FL-100 PDF generation using existing MVP system
 */

// Test data
$testData = [
    'attorney_name' => 'John Michael Smith, Esq.',
    'attorney_firm' => 'Smith & Associates Family Law',
    'attorney_address' => '1234 Legal Plaza, Suite 500',
    'attorney_city_state_zip' => 'Los Angeles, CA 90210',
    'attorney_phone' => '(555) 123-4567',
    'attorney_email' => 'jsmith@smithlaw.com',
    'attorney_bar_number' => '123456',
    'case_number' => 'FL-2024-001234',
    'court_county' => 'Los Angeles',
    'court_address' => '111 N Hill St, Los Angeles, CA 90012',
    'petitioner_name' => 'Sarah Elizabeth Johnson',
    'respondent_name' => 'Michael David Johnson',
    'petitioner_address' => '123 Main Street, Los Angeles, CA 90210',
    'petitioner_phone' => '(555) 987-6543',
    'respondent_address' => '456 Oak Avenue, Los Angeles, CA 90211',
    'marriage_date' => '06/15/2010',
    'separation_date' => '03/20/2024',
    'marriage_location' => 'Las Vegas, Nevada',
    'grounds_for_dissolution' => 'Irreconcilable differences',
    'dissolution_type' => 'Dissolution of Marriage',
    'property_division' => '1',
    'spousal_support' => '1',
    'attorney_fees' => '1',
    'name_change' => '0',
    'has_children' => 'Yes',
    'children_count' => '2',
    'additional_info' => 'Request for temporary custody orders.',
    'attorney_signature' => 'John M. Smith',
    'signature_date' => '10/09/2025'
];

// Simulate POST request to MVP system
$_POST = $testData;
$_GET['route'] = 'api/fill';
$_GET['template'] = 't_fl100_gc120';

// Include the MVP system
chdir('mvp');
include 'index.php';
