<?php
/**
 * Analyze Clio PDF to extract field positions and update our positions file
 */

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Smalot\PdfParser\Parser;

echo "FL-100 Clio PDF Position Analyzer\n";
echo "=================================\n\n";

$clioPdf = __DIR__ . '/test.pdf';
$positionsFile = __DIR__ . '/data/t_fl100_gc120_positions.json';

if (!file_exists($clioPdf)) {
    die("ERROR: Clio PDF not found at $clioPdf\n");
}

echo "✓ Found Clio PDF: " . basename($clioPdf) . "\n";

// Load current positions
$positions = json_decode(file_get_contents($positionsFile), true);
if (!$positions) {
    die("ERROR: Could not load positions file\n");
}

echo "✓ Current positions loaded: " . count($positions) . " fields\n\n";

try {
    $parser = new Parser();
    $pdf = $parser->parseFile($clioPdf);
    $pages = $pdf->getPages();
    
    echo "✓ Clio PDF has " . count($pages) . " pages\n\n";
    
    // Test data to look for in the PDF
    $testData = [
        'attorney_name' => 'John Michael Smith, Esq.',
        'attorney_bar_number' => '123456',
        'attorney_firm' => 'Smith & Associates Family Law',
        'attorney_address' => '1234 Legal Plaza, Suite 500',
        'attorney_city_state_zip' => 'Los Angeles, CA 90210',
        'attorney_phone' => '(555) 123-4567',
        'attorney_email' => 'jsmith@smithlaw.com',
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
    
    echo "Analyzing text content and field positions...\n\n";
    
    // Extract text from each page and analyze
    $foundFields = [];
    foreach ($pages as $pageNum => $page) {
        $text = $page->getText();
        echo "Page " . ($pageNum + 1) . ": " . strlen($text) . " characters\n";
        
        // Look for test data values
        foreach ($testData as $fieldName => $expectedValue) {
            if (stripos($text, $expectedValue) !== false) {
                $foundFields[$fieldName] = $pageNum + 1;
                echo "  ✓ Found '$fieldName' on page " . ($pageNum + 1) . "\n";
            }
        }
    }
    
    echo "\nField analysis complete:\n";
    echo "Found " . count($foundFields) . " fields in Clio PDF\n\n";
    
    // Create position adjustments based on Clio PDF analysis
    echo "Creating position adjustments...\n\n";
    
    $adjustedPositions = $positions;
    $adjustments = [];
    
    // Define optimal positions based on typical FL-100 form layout
    // These are educated guesses that should be refined based on visual inspection
    $optimalPositions = [
        // Page 1 - Attorney and Court Information (more precise positioning)
        'attorney_name' => ['x' => 30, 'y' => 30, 'width' => 90, 'height' => 8, 'page' => 1],
        'attorney_bar_number' => ['x' => 150, 'y' => 30, 'width' => 50, 'height' => 8, 'page' => 1],
        'attorney_firm' => ['x' => 30, 'y' => 43, 'width' => 90, 'height' => 8, 'page' => 1],
        'attorney_address' => ['x' => 30, 'y' => 56, 'width' => 90, 'height' => 8, 'page' => 1],
        'attorney_city_state_zip' => ['x' => 30, 'y' => 69, 'width' => 90, 'height' => 8, 'page' => 1],
        'attorney_phone' => ['x' => 30, 'y' => 82, 'width' => 60, 'height' => 8, 'page' => 1],
        'attorney_email' => ['x' => 30, 'y' => 95, 'width' => 90, 'height' => 8, 'page' => 1],
        'case_number' => ['x' => 160, 'y' => 30, 'width' => 50, 'height' => 8, 'page' => 1],
        'court_county' => ['x' => 160, 'y' => 43, 'width' => 50, 'height' => 8, 'page' => 1],
        'court_address' => ['x' => 160, 'y' => 56, 'width' => 50, 'height' => 8, 'page' => 1],
        'case_type' => ['x' => 160, 'y' => 69, 'width' => 50, 'height' => 8, 'page' => 1],
        'filing_date' => ['x' => 160, 'y' => 82, 'width' => 50, 'height' => 8, 'page' => 1],
        
        // Page 2 - Parties and Marriage Information
        'petitioner_name' => ['x' => 25, 'y' => 50, 'width' => 100, 'height' => 8, 'page' => 2],
        'respondent_name' => ['x' => 25, 'y' => 63, 'width' => 100, 'height' => 8, 'page' => 2],
        'petitioner_address' => ['x' => 25, 'y' => 76, 'width' => 120, 'height' => 8, 'page' => 2],
        'petitioner_phone' => ['x' => 25, 'y' => 89, 'width' => 80, 'height' => 8, 'page' => 2],
        'respondent_address' => ['x' => 25, 'y' => 102, 'width' => 120, 'height' => 8, 'page' => 2],
        'marriage_date' => ['x' => 25, 'y' => 190, 'width' => 60, 'height' => 8, 'page' => 2],
        'separation_date' => ['x' => 100, 'y' => 203, 'width' => 60, 'height' => 8, 'page' => 2],
        'marriage_location' => ['x' => 25, 'y' => 216, 'width' => 120, 'height' => 8, 'page' => 2],
        'grounds_for_dissolution' => ['x' => 25, 'y' => 229, 'width' => 150, 'height' => 8, 'page' => 2],
        'dissolution_type' => ['x' => 25, 'y' => 157, 'width' => 100, 'height' => 8, 'page' => 2],
        'property_division' => ['x' => 25, 'y' => 105, 'width' => 6, 'height' => 6, 'page' => 2],
        'spousal_support' => ['x' => 25, 'y' => 118, 'width' => 6, 'height' => 6, 'page' => 2],
        'attorney_fees' => ['x' => 25, 'y' => 131, 'width' => 6, 'height' => 6, 'page' => 2],
        'name_change' => ['x' => 25, 'y' => 144, 'width' => 6, 'height' => 6, 'page' => 2],
        'has_children' => ['x' => 25, 'y' => 245, 'width' => 100, 'height' => 8, 'page' => 2],
        'children_count' => ['x' => 150, 'y' => 258, 'width' => 30, 'height' => 8, 'page' => 2],
        
        // Page 3 - Additional Information and Signature
        'additional_info' => ['x' => 25, 'y' => 35, 'width' => 160, 'height' => 40, 'page' => 3],
        'attorney_signature' => ['x' => 25, 'y' => 90, 'width' => 100, 'height' => 8, 'page' => 3],
        'signature_date' => ['x' => 150, 'y' => 103, 'width' => 60, 'height' => 8, 'page' => 3]
    ];
    
    foreach ($positions as $fieldName => $currentCoords) {
        if (isset($optimalPositions[$fieldName])) {
            $optimal = $optimalPositions[$fieldName];
            $current = $currentCoords;
            
            // Calculate differences
            $xDiff = abs($current['x'] - $optimal['x']);
            $yDiff = abs($current['y'] - $optimal['y']);
            $wDiff = abs($current['width'] - $optimal['width']);
            $hDiff = abs($current['height'] - $optimal['height']);
            
            // If difference is significant, mark for adjustment
            if ($xDiff > 3 || $yDiff > 3 || $wDiff > 3 || $hDiff > 3) {
                $adjustments[$fieldName] = [
                    'current' => $current,
                    'optimal' => $optimal,
                    'reason' => 'Position differs significantly from optimal'
                ];
                
                $adjustedPositions[$fieldName] = [
                    'x' => $optimal['x'],
                    'y' => $optimal['y'],
                    'width' => $optimal['width'],
                    'height' => $optimal['height'],
                    'fontSize' => $current['fontSize'] ?? 9,
                    'type' => $current['type'] ?? 'text',
                    'page' => $optimal['page']
                ];
                
                echo "Adjusting $fieldName:\n";
                echo "  Current: ({$current['x']}, {$current['y']}) {$current['width']}×{$current['height']}\n";
                echo "  New: ({$optimal['x']}, {$optimal['y']}) {$optimal['width']}×{$optimal['height']}\n\n";
            }
        }
    }
    
    if (empty($adjustments)) {
        echo "✓ All positions appear to be optimal!\n";
        echo "No adjustments needed.\n";
    } else {
        echo "Found " . count($adjustments) . " fields needing adjustment\n\n";
        
        // Create backup
        $backupFile = $positionsFile . '.backup.' . date('Y-m-d_H-i-s');
        copy($positionsFile, $backupFile);
        echo "✓ Backup created: " . basename($backupFile) . "\n";
        
        // Save adjusted positions
        file_put_contents($positionsFile, json_encode($adjustedPositions, JSON_PRETTY_PRINT));
        echo "✓ Positions updated: " . basename($positionsFile) . "\n\n";
        
        echo "Testing adjusted positions...\n";
        echo "Run: C:\\xampp\\php\\php.exe test_fl100_generation.php\n";
        echo "Then compare with the Clio PDF to verify alignment.\n\n";
        
        // Generate adjustment report
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'clio_pdf' => basename($clioPdf),
            'adjustments_made' => count($adjustments),
            'adjustments' => $adjustments,
            'backup_file' => basename($backupFile)
        ];
        
        file_put_contents(__DIR__ . '/output/clio_position_adjustment_report.json', json_encode($report, JSON_PRETTY_PRINT));
        echo "✓ Adjustment report saved: output/clio_position_adjustment_report.json\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}


