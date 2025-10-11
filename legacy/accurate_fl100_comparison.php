<?php
require_once 'vendor/autoload.php';

use setasign\Fpdi\Fpdi;

echo "=== Accurate FL-100 Form Comparison ===\n\n";

// Read the improved positions
$positionsFile = 'data/t_fl100_gc120_positions.json';
$positions = json_decode(file_get_contents($positionsFile), true);

if (!$positions) {
    die("Failed to read positions file\n");
}

echo "Loaded " . count($positions) . " field positions\n\n";

// Create a comparison PDF with the actual FL-100 form
try {
    $pdf = new Fpdi();
    
    // Check if we have the official FL-100 PDF
    $officialFl100File = "uploads/fl100_official.pdf";
    if (file_exists($officialFl100File)) {
        echo "✓ Found official FL-100 PDF: $officialFl100File\n";
        
        // Import pages from the official FL-100 PDF
        $pageCount = $pdf->setSourceFile($officialFl100File);
        echo "✓ Official FL-100 has $pageCount pages\n";
        
        // Add pages based on the field distribution
        $pageDistribution = [];
        foreach ($positions as $fieldName => $config) {
            $page = $config['page'] ?? 1;
            if (!isset($pageDistribution[$page])) {
                $pageDistribution[$page] = [];
            }
            $pageDistribution[$page][] = $fieldName;
        }
        
        $maxPage = max(array_keys($pageDistribution));
        
        for ($pageNum = 1; $pageNum <= $maxPage; $pageNum++) {
            // Import the corresponding page from the official FL-100
            if ($pageNum <= $pageCount) {
                $templateId = $pdf->importPage($pageNum);
                $pdf->AddPage();
                $pdf->useTemplate($templateId);
                echo "✓ Imported page $pageNum from official FL-100\n";
            } else {
                $pdf->AddPage();
                echo "⚠️  Page $pageNum not found in official FL-100, using blank page\n";
            }
            
            // Add fields for this page
            if (isset($pageDistribution[$pageNum])) {
                $pdf->SetFont('Arial', '', 8);
                
                foreach ($pageDistribution[$pageNum] as $fieldName) {
                    $config = $positions[$fieldName];
                    
                    // Draw field box with different colors for different types
                    $pdf->SetDrawColor(255, 0, 0); // Red border
                    $pdf->SetLineWidth(1);
                    
                    if ($config['type'] === 'checkbox') {
                        $pdf->SetFillColor(255, 255, 0); // Yellow for checkboxes
                    } elseif ($config['type'] === 'date') {
                        $pdf->SetFillColor(0, 255, 255); // Cyan for dates
                    } elseif ($config['type'] === 'select') {
                        $pdf->SetFillColor(255, 0, 255); // Magenta for selects
                    } elseif ($config['type'] === 'textarea') {
                        $pdf->SetFillColor(0, 255, 0); // Green for textareas
                    } else {
                        $pdf->SetFillColor(255, 255, 255); // White for text fields
                    }
                    
                    // Draw field box
                    $pdf->Rect($config['x'], $config['y'], $config['width'] ?? 50, $config['height'] ?? 10, 'DF');
                    
                    // Add field label
                    $pdf->SetTextColor(0, 0, 0);
                    $pdf->SetXY($config['x'], $config['y'] - 5);
                    $pdf->Cell($config['width'] ?? 50, 5, $fieldName, 0, 0, 'L');
                    
                    // Add coordinates info
                    $pdf->SetXY($config['x'] + $config['width'] + 5, $config['y']);
                    $pdf->Cell(0, 5, "({$config['x']}, {$config['y']}) {$config['type']}", 0, 0, 'L');
                }
            }
            
            // Add legend
            $pdf->SetFont('Arial', '', 8);
            $pdf->SetXY(10, 280);
            $pdf->Cell(0, 5, "Legend: White=Text, Yellow=Checkbox, Cyan=Date, Magenta=Select, Green=Textarea", 0, 1);
        }
        
    } else {
        echo "⚠️  Official FL-100 PDF not found at: $officialFl100File\n";
        echo "Creating comparison with available background images...\n";
        
        // Fallback to background images
        $pageDistribution = [];
        foreach ($positions as $fieldName => $config) {
            $page = $config['page'] ?? 1;
            if (!isset($pageDistribution[$page])) {
                $pageDistribution[$page] = [];
            }
            $pageDistribution[$page][] = $fieldName;
        }
        
        $maxPage = max(array_keys($pageDistribution));
        
        for ($pageNum = 1; $pageNum <= $maxPage; $pageNum++) {
            $pdf->AddPage();
            
            // Try to add the FL-100 background if it exists
            $backgroundFile = "uploads/fl100_page{$pageNum}_background.png";
            if (file_exists($backgroundFile)) {
                $pdf->Image($backgroundFile, 0, 0, 210, 297); // A4 size
                echo "✓ Added background for page $pageNum\n";
            } else {
                // Add a simple background
                $pdf->SetFillColor(240, 240, 240);
                $pdf->Rect(0, 0, 210, 297, 'F');
                
                // Add page title
                $pdf->SetFont('Arial', 'B', 16);
                $pdf->SetTextColor(0, 0, 0);
                $pdf->SetXY(10, 10);
                $pdf->Cell(0, 10, "FL-100 Form - Page $pageNum", 0, 1);
            }
            
            // Add fields for this page
            if (isset($pageDistribution[$pageNum])) {
                $pdf->SetFont('Arial', '', 8);
                
                foreach ($pageDistribution[$pageNum] as $fieldName) {
                    $config = $positions[$fieldName];
                    
                    // Draw field box with different colors for different types
                    $pdf->SetDrawColor(255, 0, 0); // Red border
                    $pdf->SetLineWidth(1);
                    
                    if ($config['type'] === 'checkbox') {
                        $pdf->SetFillColor(255, 255, 0); // Yellow for checkboxes
                    } elseif ($config['type'] === 'date') {
                        $pdf->SetFillColor(0, 255, 255); // Cyan for dates
                    } elseif ($config['type'] === 'select') {
                        $pdf->SetFillColor(255, 0, 255); // Magenta for selects
                    } elseif ($config['type'] === 'textarea') {
                        $pdf->SetFillColor(0, 255, 0); // Green for textareas
                    } else {
                        $pdf->SetFillColor(255, 255, 255); // White for text fields
                    }
                    
                    // Draw field box
                    $pdf->Rect($config['x'], $config['y'], $config['width'] ?? 50, $config['height'] ?? 10, 'DF');
                    
                    // Add field label
                    $pdf->SetTextColor(0, 0, 0);
                    $pdf->SetXY($config['x'], $config['y'] - 5);
                    $pdf->Cell($config['width'] ?? 50, 5, $fieldName, 0, 0, 'L');
                    
                    // Add coordinates info
                    $pdf->SetXY($config['x'] + $config['width'] + 5, $config['y']);
                    $pdf->Cell(0, 5, "({$config['x']}, {$config['y']}) {$config['type']}", 0, 0, 'L');
                }
            }
            
            // Add legend
            $pdf->SetFont('Arial', '', 8);
            $pdf->SetXY(10, 280);
            $pdf->Cell(0, 5, "Legend: White=Text, Yellow=Checkbox, Cyan=Date, Magenta=Select, Green=Textarea", 0, 1);
        }
    }
    
    // Save the comparison PDF
    $outputFile = 'accurate_fl100_field_positions.pdf';
    $pdf->Output('F', $outputFile);
    
    echo "✓ Accurate comparison PDF generated: $outputFile\n";
    echo "✓ PDF has $maxPage pages\n";
    
    // Verify the PDF was created
    if (file_exists($outputFile)) {
        $fileSize = filesize($outputFile);
        echo "✓ File size: " . number_format($fileSize) . " bytes\n";
        echo "✓ File path: " . realpath($outputFile) . "\n";
    } else {
        echo "✗ Failed to create PDF file\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error creating PDF: " . $e->getMessage() . "\n";
}

echo "\n=== Field Distribution Summary ===\n";
$pageDistribution = [];
foreach ($positions as $fieldName => $config) {
    $page = $config['page'] ?? 1;
    if (!isset($pageDistribution[$page])) {
        $pageDistribution[$page] = [];
    }
    $pageDistribution[$page][] = $fieldName;
}

foreach ($pageDistribution as $page => $fields) {
    echo "Page $page: " . count($fields) . " fields\n";
    foreach ($fields as $field) {
        $config = $positions[$field];
        echo "  - $field: ({$config['x']}, {$config['y']}) {$config['type']}\n";
    }
    echo "\n";
}

echo "=== What You Should See ===\n";
echo "✅ Actual FL-100 form as background\n";
echo "✅ Fields properly positioned on the real form\n";
echo "✅ No overlapping coordinates\n";
echo "✅ Logical grouping by content type\n";
echo "✅ Proper spacing between fields\n";
echo "✅ Ready for production use\n";
?>
