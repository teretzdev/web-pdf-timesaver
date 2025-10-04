<?php
/**
 * Visual PDF Inspector - Creates a test PDF with all fields filled to verify positions
 * Shows all 4 pages of FL-100 with field positions marked
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../mvp/lib/pdf_form_filler.php';
require_once __DIR__ . '/../mvp/lib/field_position_loader.php';
require_once __DIR__ . '/../mvp/lib/logger.php';

use setasign\Fpdi\Fpdi;
use WebPdfTimeSaver\Mvp\PdfFormFiller;
use WebPdfTimeSaver\Mvp\FieldPositionLoader;
use WebPdfTimeSaver\Mvp\Logger;

echo "==============================================\n";
echo "   FL-100 Visual PDF Inspector\n";
echo "==============================================\n\n";

$uploadsDir = __DIR__ . '/../uploads';
$outputDir = __DIR__ . '/../output';
$dataDir = __DIR__ . '/../data';

// Check if FL-100 template exists
$fl100Template = $uploadsDir . '/fl100.pdf';
if (!file_exists($fl100Template)) {
    echo "⚠️  WARNING: FL-100 template not found at: $fl100Template\n";
    echo "   Please ensure fl100.pdf is in the uploads directory\n\n";
} else {
    echo "✅ FL-100 template found\n";
    
    // Check page count
    try {
        $pdfCheck = new Fpdi();
        $pageCount = $pdfCheck->setSourceFile($fl100Template);
        echo "✅ FL-100 has $pageCount pages\n";
        if ($pageCount < 4) {
            echo "⚠️  WARNING: FL-100 should have 4 pages, but only has $pageCount\n";
        }
    } catch (Exception $e) {
        echo "❌ Could not read FL-100: " . $e->getMessage() . "\n";
    }
}

echo "\nCreating visual inspection PDFs...\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

// 1. Create a PDF with grid overlay on FL-100
echo "\n1️⃣ Creating Grid Overlay PDF...\n";
$gridPdf = new Fpdi();

try {
    if (file_exists($fl100Template)) {
        $pageCount = $gridPdf->setSourceFile($fl100Template);
        
        for ($pageNum = 1; $pageNum <= $pageCount; $pageNum++) {
            echo "   Processing page $pageNum/$pageCount...\n";
            
            // Import page
            $tplId = $gridPdf->importPage($pageNum);
            $size = $gridPdf->getTemplateSize($tplId);
            $gridPdf->AddPage('P', [$size['width'], $size['height']]);
            
            // Add FL-100 as background
            $gridPdf->useTemplate($tplId, 0, 0, $size['width'], $size['height']);
            
            // Add semi-transparent grid (every 10mm)
            $gridPdf->SetDrawColor(100, 100, 200);
            $gridPdf->SetLineWidth(0.1);
            $gridPdf->SetAlpha(0.3);
            
            // Vertical lines
            for ($x = 0; $x <= $size['width']; $x += 10) {
                $gridPdf->Line($x, 0, $x, $size['height']);
            }
            
            // Horizontal lines
            for ($y = 0; $y <= $size['height']; $y += 10) {
                $gridPdf->Line(0, $y, $size['width'], $y);
            }
            
            // Add page number and size info
            $gridPdf->SetAlpha(1);
            $gridPdf->SetFont('Arial', 'B', 8);
            $gridPdf->SetTextColor(255, 0, 0);
            $gridPdf->SetXY(5, 5);
            $gridPdf->Cell(50, 4, "Page $pageNum of $pageCount", 0, 0, 'L');
            $gridPdf->SetXY(5, 10);
            $gridPdf->Cell(50, 4, sprintf("Size: %.1f x %.1f mm", $size['width'], $size['height']), 0, 0, 'L');
            
            // Add coordinate markers every 20mm
            $gridPdf->SetFont('Arial', '', 6);
            $gridPdf->SetTextColor(0, 0, 255);
            for ($x = 20; $x <= $size['width']; $x += 20) {
                $gridPdf->SetXY($x - 5, 2);
                $gridPdf->Cell(10, 3, $x, 0, 0, 'C');
            }
            for ($y = 20; $y <= $size['height']; $y += 20) {
                $gridPdf->SetXY(2, $y - 1.5);
                $gridPdf->Cell(10, 3, $y, 0, 0, 'L');
            }
        }
        
        $gridFile = $outputDir . '/inspect_grid_' . date('His') . '.pdf';
        $gridPdf->Output('F', $gridFile);
        echo "   ✅ Grid overlay PDF created: " . basename($gridFile) . "\n";
        
    } else {
        echo "   ❌ FL-100 template not found\n";
    }
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// 2. Create a PDF with field positions marked
echo "\n2️⃣ Creating Field Position Markers PDF...\n";

$markerPdf = new Fpdi();
$loader = new FieldPositionLoader($dataDir);
$positions = $loader->loadFieldPositions('t_fl100_gc120');

echo "   Found " . count($positions) . " field positions\n";

try {
    if (file_exists($fl100Template)) {
        $pageCount = $markerPdf->setSourceFile($fl100Template);
        
        // Group positions by page
        $pageFields = [];
        foreach ($positions as $fieldName => $info) {
            $page = $info['page'] ?? 1;
            if (!isset($pageFields[$page])) {
                $pageFields[$page] = [];
            }
            $pageFields[$page][$fieldName] = $info;
        }
        
        for ($pageNum = 1; $pageNum <= $pageCount; $pageNum++) {
            echo "   Page $pageNum: " . count($pageFields[$pageNum] ?? []) . " fields\n";
            
            // Import page
            $tplId = $markerPdf->importPage($pageNum);
            $size = $markerPdf->getTemplateSize($tplId);
            $markerPdf->AddPage('P', [$size['width'], $size['height']]);
            
            // Add FL-100 as background
            $markerPdf->useTemplate($tplId, 0, 0, $size['width'], $size['height']);
            
            // Draw field markers for this page
            if (isset($pageFields[$pageNum])) {
                foreach ($pageFields[$pageNum] as $fieldName => $info) {
                    $x = $info['x'] ?? 0;
                    $y = $info['y'] ?? 0;
                    $width = $info['width'] ?? 50;
                    $height = $info['height'] ?? 5;
                    $type = $info['type'] ?? 'text';
                    
                    // Set color based on type
                    $colors = [
                        'text' => [0, 100, 200],
                        'checkbox' => [200, 0, 0],
                        'date' => [0, 150, 0],
                        'signature' => [150, 0, 150],
                        'email' => [0, 150, 150],
                        'phone' => [200, 100, 0],
                    ];
                    $color = $colors[$type] ?? [0, 0, 0];
                    
                    // Draw field rectangle
                    $markerPdf->SetDrawColor($color[0], $color[1], $color[2]);
                    $markerPdf->SetLineWidth(0.5);
                    $markerPdf->Rect($x, $y, $width, $height);
                    
                    // Add field label
                    $markerPdf->SetFont('Arial', '', 6);
                    $markerPdf->SetTextColor($color[0], $color[1], $color[2]);
                    $markerPdf->SetXY($x, $y - 2.5);
                    $markerPdf->Cell($width, 2, $fieldName, 0, 0, 'L');
                }
            }
            
            // Add legend on each page
            $markerPdf->SetFont('Arial', 'B', 7);
            $markerPdf->SetXY($size['width'] - 50, 10);
            $markerPdf->SetTextColor(0, 0, 0);
            $markerPdf->Cell(40, 4, 'Field Types:', 0, 1, 'L');
            
            $legendY = 15;
            $types = ['text' => 'Text', 'checkbox' => 'Checkbox', 'date' => 'Date', 
                      'signature' => 'Signature', 'email' => 'Email', 'phone' => 'Phone'];
            $markerPdf->SetFont('Arial', '', 6);
            
            foreach ($types as $type => $label) {
                $color = $colors[$type] ?? [0, 0, 0];
                $markerPdf->SetXY($size['width'] - 50, $legendY);
                $markerPdf->SetDrawColor($color[0], $color[1], $color[2]);
                $markerPdf->SetFillColor($color[0], $color[1], $color[2]);
                $markerPdf->Rect($size['width'] - 50, $legendY, 2, 2, 'F');
                $markerPdf->SetXY($size['width'] - 47, $legendY);
                $markerPdf->SetTextColor(0, 0, 0);
                $markerPdf->Cell(35, 2, $label, 0, 1, 'L');
                $legendY += 3;
            }
        }
        
        $markerFile = $outputDir . '/inspect_markers_' . date('His') . '.pdf';
        $markerPdf->Output('F', $markerFile);
        echo "   ✅ Field markers PDF created: " . basename($markerFile) . "\n";
        
    } else {
        echo "   ❌ FL-100 template not found\n";
    }
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// 3. Create a filled test PDF
echo "\n3️⃣ Creating Filled Test PDF...\n";

$testData = [
    // Page 1 - Attorney and Court Info
    'case_number' => '2025-FL-001234',
    'attorney_name' => 'John Smith, Esq.',
    'attorney_bar_number' => 'CA-123456',
    'attorney_firm' => 'Smith & Associates Law Firm',
    'attorney_address' => '123 Legal Street, Suite 500',
    'attorney_city' => 'Los Angeles',
    'attorney_state' => 'CA',
    'attorney_zip' => '90001',
    'attorney_phone' => '(555) 123-4567',
    'attorney_fax' => '(555) 123-4568',
    'attorney_email' => 'jsmith@lawfirm.com',
    'attorney_for' => 'Petitioner',
    
    'court_name' => 'Superior Court of California',
    'court_county' => 'Los Angeles',
    'court_street' => '111 North Hill Street',
    'court_mailing' => '111 North Hill Street',
    'court_city_zip' => 'Los Angeles, CA 90012',
    'court_branch' => 'Stanley Mosk Courthouse',
    
    'petitioner_name' => 'Jane Marie Doe',
    'petitioner_first_name' => 'Jane',
    'petitioner_last_name' => 'Doe',
    'respondent_name' => 'Robert James Smith',
    'respondent_first_name' => 'Robert',
    'respondent_last_name' => 'Smith',
    
    // Checkboxes
    'dissolution_marriage' => 'checked',
    'petition_dissolution' => 'checked',
    
    // Page 2 - Children Info
    'minor_children' => 'checked',
    'child1_name' => 'Emma Doe Smith',
    'child1_birthdate' => '03/15/2015',
    'child1_age' => '9',
    'child1_sex' => 'F',
    
    // Dates
    'date_signed' => date('m/d/Y'),
];

$template = [
    'id' => 't_fl100_gc120',
    'name' => 'FL-100 Test'
];

try {
    $logger = new Logger();
    $formFiller = new PdfFormFiller($outputDir, $uploadsDir, $logger);
    
    // Fill using positioned fields
    $result = $formFiller->fillPdfFormWithPositions($template, $testData, 't_fl100_gc120');
    
    if ($result['success'] ?? false) {
        echo "   ✅ Filled test PDF created: " . ($result['file'] ?? 'unknown') . "\n";
        echo "   📍 Used " . ($result['used_positions'] ?? 0) . " field positions\n";
    } else {
        echo "   ❌ Failed to create filled PDF\n";
    }
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// 4. Position accuracy report
echo "\n4️⃣ Position Accuracy Report\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$pageGroups = [];
foreach ($positions as $field => $info) {
    $page = $info['page'] ?? 1;
    if (!isset($pageGroups[$page])) {
        $pageGroups[$page] = [];
    }
    $pageGroups[$page][] = $field;
}

foreach ($pageGroups as $page => $fields) {
    echo "\n📄 Page $page (" . count($fields) . " fields):\n";
    foreach ($fields as $field) {
        $info = $positions[$field];
        printf("   • %-30s at (%6.1f, %6.1f) size %5.1f x %4.1f mm [%s]\n",
            $field,
            $info['x'] ?? 0,
            $info['y'] ?? 0,
            $info['width'] ?? 0,
            $info['height'] ?? 0,
            $info['type'] ?? 'text'
        );
    }
}

// Summary
echo "\n📊 Summary:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Total fields defined: " . count($positions) . "\n";
echo "Pages with fields: " . count($pageGroups) . "\n";

$typeCount = [];
foreach ($positions as $info) {
    $type = $info['type'] ?? 'text';
    $typeCount[$type] = ($typeCount[$type] ?? 0) + 1;
}

echo "\nField types:\n";
foreach ($typeCount as $type => $count) {
    echo "   • $type: $count\n";
}

echo "\n✨ Visual inspection complete!\n";
echo "\nCheck these files in $outputDir:\n";
echo "  1. inspect_grid_*.pdf     - Grid overlay for measurements\n";
echo "  2. inspect_markers_*.pdf  - Field position markers\n";
echo "  3. mvp_*_positioned.pdf   - Filled test PDF\n";
echo "\nUse these to verify field positions are correct.\n";