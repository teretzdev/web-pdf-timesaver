<?php
/**
 * Automated FL-100 Position Fixer
 * Analyzes the generated PDF and automatically adjusts field positions
 */

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Smalot\PdfParser\Parser;

echo "FL-100 Automated Position Fixer\n";
echo "================================\n\n";

// File paths
$outputDir = __DIR__ . '/output';
// Find newest positioned FL-100 PDF
$pdfFiles = glob($outputDir . '/mvp_*_t_fl100_gc120_positioned.pdf');
if (empty($pdfFiles)) {
    die("ERROR: No FL-100 PDF found. Run: C:\\xampp\\php\\php.exe test_fl100_generation.php\n");
}
usort($pdfFiles, function($a, $b) { return filemtime($b) <=> filemtime($a); });
$currentPdf = $pdfFiles[0];
$positionsFile = __DIR__ . '/data/t_fl100_gc120_positions.json';

echo "✓ Found PDF: " . basename($currentPdf) . "\n";
echo "✓ Positions file: " . basename($positionsFile) . "\n\n";

// Load current positions
$positions = json_decode(file_get_contents($positionsFile), true);
if (!$positions) {
    die("ERROR: Could not load positions file\n");
}

echo "Analyzing PDF structure...\n";

try {
    $parser = new Parser();
    $pdf = $parser->parseFile($currentPdf);
    $pages = $pdf->getPages();
    
    echo "✓ PDF has " . count($pages) . " pages\n";
    
    // Extract text and analyze positioning
    $textAnalysis = [];
    foreach ($pages as $pageNum => $page) {
        $text = $page->getText();
        $textAnalysis[$pageNum + 1] = $text;
        echo "✓ Page " . ($pageNum + 1) . ": " . strlen($text) . " characters\n";
    }
    
    echo "\nAnalyzing field content and positioning...\n";
    
    // Test data for comparison
    $testData = [
        'attorney_name' => 'John Michael Smith, Esq.',
        'attorney_bar_number' => '123456',
        'attorney_firm' => 'Smith & Associates Family Law',
        'case_number' => 'FL-2024-001234',
        'petitioner_name' => 'Sarah Elizabeth Johnson',
        'respondent_name' => 'Michael David Johnson',
        'marriage_date' => '06/15/2010',
        'separation_date' => '03/20/2024',
        'marriage_location' => 'Las Vegas, Nevada',
        'grounds_for_dissolution' => 'Irreconcilable differences',
        'dissolution_type' => 'Dissolution of Marriage',
        'additional_info' => 'Request for temporary custody orders.',
        'attorney_signature' => 'John M. Smith',
        'signature_date' => '10/09/2025'
    ];
    
    // Check which fields appear in the PDF
    $foundFields = [];
    foreach ($testData as $fieldName => $expectedValue) {
        foreach ($textAnalysis as $pageNum => $text) {
            if (stripos($text, $expectedValue) !== false) {
                $foundFields[$fieldName] = $pageNum;
                echo "✓ Found '$fieldName' on page $pageNum\n";
                break;
            }
        }
    }
    
    echo "\nField positioning analysis:\n";
    echo "==========================\n\n";
    
    // Analyze ALL positions and suggest improvements
    $improvements = [];
    
    // Define optimal positions for all fields based on FL-100 form layout
    $optimalPositions = [
        // Page 1 - Attorney and Court Information
        'attorney_name' => ['x' => 35, 'y' => 28, 'width' => 85, 'height' => 8, 'page' => 1],
        'attorney_bar_number' => ['x' => 145, 'y' => 28, 'width' => 60, 'height' => 8, 'page' => 1],
        'attorney_firm' => ['x' => 35, 'y' => 41, 'width' => 85, 'height' => 8, 'page' => 1],
        'attorney_address' => ['x' => 35, 'y' => 54, 'width' => 85, 'height' => 8, 'page' => 1],
        'attorney_city_state_zip' => ['x' => 35, 'y' => 67, 'width' => 85, 'height' => 8, 'page' => 1],
        'attorney_phone' => ['x' => 35, 'y' => 80, 'width' => 60, 'height' => 8, 'page' => 1],
        'attorney_email' => ['x' => 35, 'y' => 93, 'width' => 85, 'height' => 8, 'page' => 1],
        'case_number' => ['x' => 155, 'y' => 28, 'width' => 60, 'height' => 8, 'page' => 1],
        'court_county' => ['x' => 155, 'y' => 41, 'width' => 60, 'height' => 8, 'page' => 1],
        'court_address' => ['x' => 155, 'y' => 54, 'width' => 60, 'height' => 8, 'page' => 1],
        'case_type' => ['x' => 155, 'y' => 67, 'width' => 60, 'height' => 8, 'page' => 1],
        'filing_date' => ['x' => 155, 'y' => 80, 'width' => 60, 'height' => 8, 'page' => 1],
        
        // Page 2 - Parties and Marriage Information
        'petitioner_name' => ['x' => 30, 'y' => 45, 'width' => 100, 'height' => 8, 'page' => 2],
        'respondent_name' => ['x' => 30, 'y' => 58, 'width' => 100, 'height' => 8, 'page' => 2],
        'petitioner_address' => ['x' => 30, 'y' => 71, 'width' => 120, 'height' => 8, 'page' => 2],
        'petitioner_phone' => ['x' => 30, 'y' => 84, 'width' => 80, 'height' => 8, 'page' => 2],
        'respondent_address' => ['x' => 30, 'y' => 97, 'width' => 120, 'height' => 8, 'page' => 2],
        'marriage_date' => ['x' => 30, 'y' => 185, 'width' => 60, 'height' => 8, 'page' => 2],
        'separation_date' => ['x' => 105, 'y' => 198, 'width' => 60, 'height' => 8, 'page' => 2],
        'marriage_location' => ['x' => 30, 'y' => 211, 'width' => 120, 'height' => 8, 'page' => 2],
        'grounds_for_dissolution' => ['x' => 30, 'y' => 224, 'width' => 150, 'height' => 8, 'page' => 2],
        'dissolution_type' => ['x' => 30, 'y' => 152, 'width' => 100, 'height' => 8, 'page' => 2],
        'property_division' => ['x' => 30, 'y' => 100, 'width' => 6, 'height' => 6, 'page' => 2],
        'spousal_support' => ['x' => 30, 'y' => 113, 'width' => 6, 'height' => 6, 'page' => 2],
        'attorney_fees' => ['x' => 30, 'y' => 126, 'width' => 6, 'height' => 6, 'page' => 2],
        'name_change' => ['x' => 30, 'y' => 139, 'width' => 6, 'height' => 6, 'page' => 2],
        'has_children' => ['x' => 30, 'y' => 240, 'width' => 100, 'height' => 8, 'page' => 2],
        'children_count' => ['x' => 145, 'y' => 253, 'width' => 30, 'height' => 8, 'page' => 2],
        
        // Page 3 - Additional Information and Signature
        'additional_info' => ['x' => 30, 'y' => 30, 'width' => 160, 'height' => 40, 'page' => 3],
        'attorney_signature' => ['x' => 30, 'y' => 85, 'width' => 100, 'height' => 8, 'page' => 3],
        'signature_date' => ['x' => 145, 'y' => 98, 'width' => 60, 'height' => 8, 'page' => 3]
    ];
    
    foreach ($positions as $fieldName => $coords) {
        $page = $coords['page'] ?? 1;
        $foundOnPage = $foundFields[$fieldName] ?? null;
        
        $status = "OK";
        $suggestion = "Position is correct";
        
        // Check if we have optimal position defined
        if (isset($optimalPositions[$fieldName])) {
            $optimal = $optimalPositions[$fieldName];
            
            // Calculate differences
            $xDiff = abs($coords['x'] - $optimal['x']);
            $yDiff = abs($coords['y'] - $optimal['y']);
            $wDiff = abs($coords['width'] - $optimal['width']);
            $hDiff = abs($coords['height'] - $optimal['height']);
            
            if ($xDiff > 2 || $yDiff > 2 || $wDiff > 2 || $hDiff > 2) {
                $status = "NEEDS_ADJUSTMENT";
                $suggestion = "Adjust to optimal position: ({$optimal['x']}, {$optimal['y']}) {$optimal['width']}×{$optimal['height']}";
            }
        } else {
            $status = "NO_OPTIMAL_DEFINED";
            $suggestion = "No optimal position defined for this field";
        }
        
        // Special checks for checkboxes
        if ($coords['type'] === 'checkbox' && $coords['width'] > 8) {
            $status = "NEEDS_ADJUSTMENT";
            $suggestion = "Checkbox size should be smaller (6×6mm)";
        }
        
        // Check page assignment
        if ($foundOnPage && $foundOnPage !== $page) {
            $status = "WRONG_PAGE";
            $suggestion = "Field appears on page $foundOnPage but positioned for page $page";
        }
        
        $improvements[$fieldName] = [
            'current' => $coords,
            'status' => $status,
            'suggestion' => $suggestion,
            'found_on_page' => $foundOnPage,
            'optimal' => $optimalPositions[$fieldName] ?? null
        ];
        
        printf("%-25s %s: (%3.0f, %3.0f) %s\n", 
            $fieldName, 
            $status,
            $coords['x'], 
            $coords['y'],
            $suggestion
        );
    }
    
    // Filter to only include fields that actually need adjustment
    $fieldsNeedingAdjustment = array_filter($improvements, function($improvement) {
        return $improvement['status'] === 'NEEDS_ADJUSTMENT' || $improvement['status'] === 'WRONG_PAGE';
    });
    
    if (empty($fieldsNeedingAdjustment)) {
        echo "\n✓ All field positions appear to be correctly positioned!\n";
        echo "No automatic adjustments needed.\n";
    } else {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "AUTOMATIC POSITION ADJUSTMENTS\n";
        echo str_repeat("=", 60) . "\n\n";
        
        $adjustedPositions = $positions;
        
        foreach ($fieldsNeedingAdjustment as $fieldName => $improvement) {
            $current = $improvement['current'];
            $optimal = $improvement['optimal'];
            
            if ($optimal) {
                $newCoords = [
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
                echo "  New: ({$newCoords['x']}, {$newCoords['y']}) {$newCoords['width']}×{$newCoords['height']}\n";
                echo "  Reason: {$improvement['suggestion']}\n\n";
                
                $adjustedPositions[$fieldName] = $newCoords;
            } else {
                echo "Skipping $fieldName: No optimal position defined\n";
            }
        }
        
        // Save adjusted positions
        $backupFile = $positionsFile . '.backup.' . date('Y-m-d_H-i-s');
        copy($positionsFile, $backupFile);
        echo "✓ Backup created: " . basename($backupFile) . "\n";
        
        file_put_contents($positionsFile, json_encode($adjustedPositions, JSON_PRETTY_PRINT));
        echo "✓ Positions updated: " . basename($positionsFile) . "\n\n";
        
        echo "Testing adjusted positions...\n";
        echo "Run: C:\\xampp\\php\\php.exe test_fl100_generation.php\n";
        echo "Then compare the new PDF with the previous one.\n\n";
        
        // Generate a summary report
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'original_pdf' => basename($currentPdf),
            'adjustments_made' => count($improvements),
            'improvements' => $improvements,
            'backup_file' => basename($backupFile)
        ];
        
        file_put_contents(__DIR__ . '/output/position_adjustment_report.json', json_encode($report, JSON_PRETTY_PRINT));
        echo "✓ Adjustment report saved: output/position_adjustment_report.json\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
