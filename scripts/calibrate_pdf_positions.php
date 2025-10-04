<?php
/**
 * Visual calibration tool for PDF field positions
 * Creates a PDF with position markers to help calibrate exact field locations
 */

require_once __DIR__ . '/../vendor/autoload.php';

use setasign\Fpdi\Fpdi;

echo "PDF Position Calibration Tool\n";
echo "==============================\n\n";

$outputDir = __DIR__ . '/../output';
$uploadsDir = __DIR__ . '/../uploads';
$dataDir = __DIR__ . '/../data';

// Create calibration PDF
$pdf = new Fpdi();

// Try to use FL-100 as background
$templatePdf = $uploadsDir . '/fl100.pdf';
$hasBackground = false;

try {
    if (file_exists($templatePdf)) {
        $pageCount = $pdf->setSourceFile($templatePdf);
        $tplId = $pdf->importPage(1);
        $size = $pdf->getTemplateSize($tplId);
        $pdf->AddPage('P', [$size['width'], $size['height']]);
        $pdf->useTemplate($tplId, 0, 0, $size['width'], $size['height']);
        $hasBackground = true;
        echo "Using FL-100 as background\n";
    } else {
        $pdf->AddPage();
        echo "No FL-100 template found, using blank page\n";
    }
} catch (Exception $e) {
    $pdf->AddPage();
    echo "Could not use FL-100 as background: " . $e->getMessage() . "\n";
}

// Add grid for reference (every 10mm)
$pdf->SetDrawColor(200, 200, 200);
$pdf->SetLineWidth(0.1);
for ($x = 0; $x <= 210; $x += 10) {
    $pdf->Line($x, 0, $x, 297);
}
for ($y = 0; $y <= 297; $y += 10) {
    $pdf->Line(0, $y, 210, $y);
}

// Add coordinate labels
$pdf->SetFont('Arial', '', 6);
$pdf->SetTextColor(150, 150, 150);
for ($x = 0; $x <= 210; $x += 20) {
    $pdf->SetXY($x, 2);
    $pdf->Cell(10, 3, $x, 0, 0, 'L');
}
for ($y = 10; $y <= 290; $y += 20) {
    $pdf->SetXY(2, $y);
    $pdf->Cell(10, 3, $y, 0, 0, 'L');
}

// Load current positions
$positionsFile = $dataDir . '/t_fl100_gc120_positions.json';
if (file_exists($positionsFile)) {
    $positions = json_decode(file_get_contents($positionsFile), true);
    echo "Loaded " . count($positions) . " field positions\n";
} else {
    echo "No positions file found\n";
    $positions = [];
}

// Draw field position markers
$pdf->SetLineWidth(0.5);
$fieldColors = [
    'text' => [0, 0, 255],      // Blue for text fields
    'checkbox' => [255, 0, 0],   // Red for checkboxes
    'date' => [0, 255, 0],       // Green for dates
    'signature' => [255, 0, 255], // Magenta for signatures
    'email' => [0, 128, 255],    // Light blue for email
    'phone' => [255, 128, 0],    // Orange for phone
];

foreach ($positions as $fieldName => $info) {
    $x = $info['x'] ?? 0;
    $y = $info['y'] ?? 0;
    $width = $info['width'] ?? 50;
    $height = $info['height'] ?? 5;
    $type = $info['type'] ?? 'text';
    $page = $info['page'] ?? 1;
    
    // Skip if not on page 1 for this calibration
    if ($page != 1) continue;
    
    // Set color based on field type
    $color = $fieldColors[$type] ?? [0, 0, 0];
    $pdf->SetDrawColor($color[0], $color[1], $color[2]);
    $pdf->SetTextColor($color[0], $color[1], $color[2]);
    
    // Draw field rectangle
    $pdf->Rect($x, $y, $width, $height);
    
    // Add field name label
    $pdf->SetFont('Arial', '', 7);
    $pdf->SetXY($x, $y - 3);
    $pdf->Cell($width, 3, $fieldName, 0, 0, 'L');
    
    // Add coordinates label
    $pdf->SetFont('Arial', '', 5);
    $pdf->SetXY($x, $y + $height + 0.5);
    $pdf->Cell($width, 2, "({$x}, {$y})", 0, 0, 'L');
}

// Add legend
$pdf->SetXY(10, 270);
$pdf->SetFont('Arial', 'B', 8);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(40, 5, 'Field Type Legend:', 0, 1, 'L');
$pdf->SetFont('Arial', '', 7);

$legendY = 275;
foreach ($fieldColors as $type => $color) {
    $pdf->SetXY(10, $legendY);
    $pdf->SetDrawColor($color[0], $color[1], $color[2]);
    $pdf->SetFillColor($color[0], $color[1], $color[2]);
    $pdf->Rect(10, $legendY, 3, 3, 'F');
    $pdf->SetXY(15, $legendY);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(30, 3, ucfirst($type), 0, 1, 'L');
    $legendY += 4;
}

// Save calibration PDF
$outputFile = $outputDir . '/calibration_' . date('Ymd_His') . '.pdf';
$pdf->Output('F', $outputFile);

echo "\n✅ Calibration PDF created: $outputFile\n";
echo "\nThis PDF shows:\n";
echo "- Grid lines every 10mm\n";
echo "- Coordinate labels\n";
echo "- Current field positions as colored rectangles\n";
echo "- Field names and coordinates\n";
echo "\nUse this to verify and adjust field positions in the JSON file.\n";

// Suggest better positions based on common FL-100 layout
echo "\n📝 Suggested adjustments for common FL-100 fields:\n";
$suggestions = [
    'case_number' => ['x' => 142, 'y' => 27, 'width' => 46, 'height' => 6],
    'attorney_name' => ['x' => 20, 'y' => 58, 'width' => 100, 'height' => 5],
    'attorney_bar_number' => ['x' => 125, 'y' => 58, 'width' => 65, 'height' => 5],
    'attorney_firm' => ['x' => 20, 'y' => 67, 'width' => 170, 'height' => 5],
    'attorney_address' => ['x' => 20, 'y' => 76, 'width' => 170, 'height' => 5],
    'attorney_city_state_zip' => ['x' => 20, 'y' => 85, 'width' => 170, 'height' => 5],
    'attorney_phone' => ['x' => 20, 'y' => 94, 'width' => 80, 'height' => 5],
    'attorney_email' => ['x' => 105, 'y' => 94, 'width' => 85, 'height' => 5],
    'court_county' => ['x' => 50, 'y' => 122, 'width' => 70, 'height' => 5],
    'petitioner_name' => ['x' => 20, 'y' => 148, 'width' => 170, 'height' => 5],
    'respondent_name' => ['x' => 20, 'y' => 162, 'width' => 170, 'height' => 5],
];

foreach ($suggestions as $field => $suggested) {
    if (isset($positions[$field])) {
        $current = $positions[$field];
        if ($current['x'] != $suggested['x'] || $current['y'] != $suggested['y']) {
            echo "  $field: Move from ({$current['x']}, {$current['y']}) to ({$suggested['x']}, {$suggested['y']})\n";
        }
    }
}

echo "\nDone!\n";