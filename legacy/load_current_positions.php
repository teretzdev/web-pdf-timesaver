<?php
/**
 * Load current FL-100 positions and generate a comparison report
 */

$positionsFile = __DIR__ . '/data/t_fl100_gc120_positions.json';

if (!file_exists($positionsFile)) {
    die("ERROR: Positions file not found at $positionsFile\n");
}

$positions = json_decode(file_get_contents($positionsFile), true);

if (!$positions) {
    die("ERROR: Could not parse positions JSON\n");
}

echo "FL-100 Current Field Positions\n";
echo "==============================\n\n";

echo "Total fields: " . count($positions) . "\n\n";

// Group by page
$byPage = [];
foreach ($positions as $fieldName => $coords) {
    $page = $coords['page'] ?? 1;
    $byPage[$page][] = [
        'name' => $fieldName,
        'x' => $coords['x'],
        'y' => $coords['y'],
        'width' => $coords['width'],
        'height' => $coords['height'],
        'type' => $coords['type'] ?? 'text'
    ];
}

foreach ($byPage as $pageNum => $fields) {
    echo "PAGE $pageNum (" . count($fields) . " fields):\n";
    echo str_repeat("-", 50) . "\n";
    
    foreach ($fields as $field) {
        printf("%-25s %s: (%3.0f, %3.0f) %3.0f×%3.0f\n", 
            $field['name'], 
            $field['type'],
            $field['x'], 
            $field['y'], 
            $field['width'], 
            $field['height']
        );
    }
    echo "\n";
}

echo "Measurement Instructions:\n";
echo "========================\n\n";
echo "1. Open your generated PDF: output/mvp_20251010_015345_t_fl100_gc120_positioned.pdf\n";
echo "2. Open field_position_measurement_tool.html in your browser\n";
echo "3. Load both PDFs in the measurement tool\n";
echo "4. Compare field positions visually\n";
echo "5. Use Adobe Acrobat's measurement tool to get exact coordinates\n";
echo "6. Enter corrected coordinates in the measurement tool\n";
echo "7. Export the updated position data\n";
echo "8. Replace data/t_fl100_gc120_positions.json with the corrected version\n\n";

echo "Coordinate System:\n";
echo "- Origin (0,0) is at TOP-LEFT corner\n";
echo "- X increases to the RIGHT\n";
echo "- Y increases DOWNWARD\n";
echo "- Units are in MILLIMETERS\n";
echo "- Page size: 215.9mm × 279.4mm (US Letter)\n\n";

echo "Common Issues to Check:\n";
echo "=======================\n";
echo "• Attorney fields (top-left) - should align with form lines\n";
echo "• Case number (top-right) - should be inside the case number box\n";
echo "• Party names - should align with petitioner/respondent lines\n";
echo "• Checkboxes - should be centered on checkbox circles\n";
echo "• Signature fields - should align with signature lines\n\n";

// Generate a quick reference for the measurement tool
$quickRef = [];
foreach ($positions as $fieldName => $coords) {
    $quickRef[$fieldName] = [
        'x' => $coords['x'],
        'y' => $coords['y'],
        'width' => $coords['width'],
        'height' => $coords['height'],
        'fontSize' => $coords['fontSize'] ?? 9,
        'type' => $coords['type'] ?? 'text',
        'page' => $coords['page'] ?? 1
    ];
}

file_put_contents(__DIR__ . '/current_positions_reference.json', json_encode($quickRef, JSON_PRETTY_PRINT));
echo "✓ Current positions saved to: current_positions_reference.json\n";
echo "✓ Use this file to verify your measurements\n\n";

echo "Next Steps:\n";
echo "===========\n";
echo "1. Run: start chrome field_position_measurement_tool.html\n";
echo "2. Load both PDFs in the measurement tool\n";
echo "3. Measure and adjust field positions\n";
echo "4. Export corrected positions\n";
echo "5. Test with: C:\\xampp\\php\\php.exe test_fl100_generation.php\n";
