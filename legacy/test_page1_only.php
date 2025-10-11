<?php
/**
 * Test FL-100 Page 1 Positioning Only
 * Focuses on getting page 1 fields accurate before moving to pages 2-3
 */

require_once __DIR__ . '/mvp/lib/data.php';
require_once __DIR__ . '/mvp/templates/registry.php';
require_once __DIR__ . '/mvp/lib/fill_service.php';
require_once __DIR__ . '/mvp/lib/logger.php';

use WebPdfTimeSaver\Mvp\DataStore;
use WebPdfTimeSaver\Mvp\TemplateRegistry;
use WebPdfTimeSaver\Mvp\FillService;
use WebPdfTimeSaver\Mvp\Logger;

echo "=== FL-100 PAGE 1 POSITION TEST ===\n\n";

$store = new DataStore(__DIR__ . '/data/mvp.json');
$templates = TemplateRegistry::load();
$logger = new Logger();
$fill = new FillService(__DIR__ . '/output', $logger);

$template = $templates['t_fl100_gc120'];
echo "Template: " . $template['code'] . " - " . $template['name'] . "\n";
echo "Focusing on: PAGE 1 ONLY\n\n";

// Test data for PAGE 1 fields only
$testData = [
    // Top-left attorney box
    'attorney_name' => '[ATTORNEY NAME]',
    'attorney_firm' => '[FIRM NAME]',
    'attorney_address' => '[STREET ADDRESS]',
    'attorney_city_state_zip' => '[CITY, STATE ZIP]',
    'attorney_phone' => '[PHONE]',
    'attorney_email' => '[EMAIL]',
    
    // Top-right bar number
    'attorney_bar_number' => '[BAR#]',
    
    // Court header section  
    'court_county' => '[COUNTY]',
    'court_address' => '[COURT ADDRESS]',
    'case_type' => '[BRANCH NAME]',
    
    // Top-right case number box
    'case_number' => '[CASE NUMBER]',
    
    // Petitioner/Respondent section
    'petitioner_name' => '[PETITIONER]',
    'respondent_name' => '[RESPONDENT]',
    
    // Petition For checkboxes
    'property_division' => '1',     // Dissolution checkbox
    'spousal_support' => '1',        // Legal Separation checkbox  
    'attorney_fees' => '1',          // Nullity checkbox
    
    // Legal Relationship section
    'name_change' => '1',            // We are married checkbox
    
    // Statistical Facts section
    'marriage_date' => '[DATE MARRIED]',
    'separation_date' => '[DATE SEP]',
    
    // Residence section
    'marriage_location' => '[PLACE MARRIED]',
    'grounds_for_dissolution' => '[GROUNDS]',
    'dissolution_type' => '[DISSOLUTION TYPE]',
    
    // Minor Children section
    'has_children' => '[YES/NO]',
    'children_count' => '[#]'
];

echo "Page 1 Fields: " . count($testData) . "\n\n";

$posFile = __DIR__ . '/data/t_fl100_gc120_positions.json';
$positions = json_decode(file_get_contents($posFile), true);

echo "Current Page 1 Field Positions:\n";
echo str_repeat('=', 80) . "\n";
printf("%-30s %8s %8s %8s %10s\n", "FIELD", "X (mm)", "Y (mm)", "SIZE", "SECTION");
echo str_repeat('-', 80) . "\n";

foreach ($testData as $key => $value) {
    if (isset($positions[$key])) {
        $pos = $positions[$key];
        $page = $pos['page'] ?? 1;
        if ($page == 1) {
            printf("%-30s %8.1f %8.1f %8d %10s\n", 
                $key, 
                $pos['x'], 
                $pos['y'], 
                $pos['fontSize'],
                substr($value, 0, 10)
            );
        }
    }
}
echo str_repeat('=', 80) . "\n\n";

// Generate PDF
echo "Generating Page 1 test PDF...\n";
$result = $fill->generateSimplePdf($template, $testData, ['test' => true]);

echo "\n✓ PDF Generated: " . $result['filename'] . "\n";
echo "✓ Fields placed: " . $result['fields_placed'] . "\n";
echo "✓ File size: " . number_format(filesize($result['path'])) . " bytes\n\n";

echo "NEXT STEPS:\n";
echo "1. Open the PDF: " . $result['path'] . "\n";
echo "2. Check each [LABELED] field position\n";
echo "3. Note which fields need adjustment\n";
echo "4. Tell me adjustments needed (e.g., 'attorney_name move right 5mm, down 2mm')\n";
echo "5. I'll update positions and regenerate\n";

