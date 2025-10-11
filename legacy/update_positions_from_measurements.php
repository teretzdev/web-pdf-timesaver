<?php
/**
 * Update FL-100 positions based on manual measurements
 * This script will prompt for measured coordinates and update the positions file
 */

echo "FL-100 Position Update Tool\n";
echo "==========================\n\n";

$positionsFile = __DIR__ . '/data/t_fl100_gc120_positions.json';

// Load current positions
$positions = json_decode(file_get_contents($positionsFile), true);
if (!$positions) {
    die("❌ Could not load positions file\n");
}

echo "Current positions loaded: " . count($positions) . " fields\n\n";

echo "Enter measured coordinates for each field:\n";
echo "(Press Enter to keep current value, type 'skip' to skip a field)\n\n";

$updatedPositions = $positions;
$updatedCount = 0;

foreach ($positions as $fieldName => $coords) {
    $page = $coords['page'] ?? 1;
    
    echo "Field: $fieldName (Page $page)\n";
    echo "Current: X={$coords['x']}, Y={$coords['y']}, W={$coords['width']}, H={$coords['height']}\n";
    
    // Get new X coordinate
    $newX = readline("New X (mm): ");
    if ($newX === 'skip') {
        echo "Skipped\n\n";
        continue;
    }
    if ($newX !== '') {
        $updatedPositions[$fieldName]['x'] = floatval($newX);
        $updatedCount++;
    }
    
    // Get new Y coordinate
    $newY = readline("New Y (mm): ");
    if ($newY !== '') {
        $updatedPositions[$fieldName]['y'] = floatval($newY);
        $updatedCount++;
    }
    
    // Get new width
    $newW = readline("New Width (mm): ");
    if ($newW !== '') {
        $updatedPositions[$fieldName]['width'] = floatval($newW);
        $updatedCount++;
    }
    
    // Get new height
    $newH = readline("New Height (mm): ");
    if ($newH !== '') {
        $updatedPositions[$fieldName]['height'] = floatval($newH);
        $updatedCount++;
    }
    
    echo "Updated: X={$updatedPositions[$fieldName]['x']}, Y={$updatedPositions[$fieldName]['y']}, W={$updatedPositions[$fieldName]['width']}, H={$updatedPositions[$fieldName]['height']}\n\n";
}

if ($updatedCount > 0) {
    // Create backup
    $backupFile = $positionsFile . '.backup.' . date('Y-m-d_H-i-s');
    copy($positionsFile, $backupFile);
    echo "✅ Backup created: " . basename($backupFile) . "\n";
    
    // Save updated positions
    file_put_contents($positionsFile, json_encode($updatedPositions, JSON_PRETTY_PRINT));
    echo "✅ Positions updated: $updatedCount changes made\n";
    echo "✅ File saved: " . basename($positionsFile) . "\n\n";
    
    echo "Next steps:\n";
    echo "1. Test: C:\\xampp\\php\\php.exe test_fl100_generation.php\n";
    echo "2. Check logs: Get-Content logs\\pdf_debug.log | Select-Object -Last 20\n";
    echo "3. Verify: Open the generated PDF and check alignment\n\n";
} else {
    echo "No changes made.\n";
}


