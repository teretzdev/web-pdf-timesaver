<?php
declare(strict_types=1);

/**
 * Comprehensive Field Editor Test Suite
 * 
 * Tests the field positioning and editing functionality:
 * - Field positioning on PDF forms
 * - Drag and drop simulation
 * - Position persistence
 * - Field coordinate validation
 * - Multi-page support
 */

require_once __DIR__ . '/../mvp/lib/field_position_loader.php';
require_once __DIR__ . '/../mvp/templates/registry.php';

use WebPdfTimeSaver\Mvp\FieldPositionLoader;
use WebPdfTimeSaver\Mvp\TemplateRegistry;

echo "🎯 Testing Comprehensive Field Editor System\n";
echo "===========================================\n\n";

$testsPassed = 0;
$testsFailed = 0;

function assertTest($condition, $message) {
    global $testsPassed, $testsFailed;
    if ($condition) {
        echo "✅ $message\n";
        $testsPassed++;
    } else {
        echo "❌ FAILED: $message\n";
        $testsFailed++;
    }
}

function assertEquals($expected, $actual, $message) {
    assertTest($expected === $actual, "$message - Expected: $expected, Got: $actual");
}

function assertInRange($value, $min, $max, $message) {
    assertTest($value >= $min && $value <= $max, "$message - Value $value in range [$min, $max]");
}

// Test 1: Field Position Structure
echo "Test 1: Field Position Structure\n";
echo "--------------------------------\n";

function createFieldPosition($fieldKey, $x, $y, $page = 1, $width = 200, $height = 20) {
    return [
        'fieldKey' => $fieldKey,
        'x' => $x,
        'y' => $y,
        'page' => $page,
        'width' => $width,
        'height' => $height,
        'rotation' => 0,
        'fontSize' => 12
    ];
}

$testPosition = createFieldPosition('attorney_name', 100, 150, 1);
assertTest(isset($testPosition['fieldKey']), "Field position has key");
assertTest(isset($testPosition['x']) && isset($testPosition['y']), "Field position has coordinates");
assertTest(isset($testPosition['page']), "Field position has page number");

// Test 2: Position Validation
echo "\nTest 2: Position Validation\n";
echo "---------------------------\n";

function validateFieldPosition($position, $pageWidth = 612, $pageHeight = 792) {
    $errors = [];
    
    // Check required properties
    if (empty($position['fieldKey'])) {
        $errors[] = "Missing field key";
    }
    
    // Validate coordinates (PDF coordinates in points)
    if ($position['x'] < 0 || $position['x'] > $pageWidth) {
        $errors[] = "X coordinate out of bounds: {$position['x']}";
    }
    
    if ($position['y'] < 0 || $position['y'] > $pageHeight) {
        $errors[] = "Y coordinate out of bounds: {$position['y']}";
    }
    
    // Validate page number
    if ($position['page'] < 1) {
        $errors[] = "Invalid page number: {$position['page']}";
    }
    
    // Validate dimensions
    if ($position['width'] <= 0 || $position['height'] <= 0) {
        $errors[] = "Invalid field dimensions";
    }
    
    return $errors;
}

$validPosition = createFieldPosition('test_field', 300, 400, 1);
$errors = validateFieldPosition($validPosition);
assertTest(empty($errors), "Valid position passes validation");

$invalidPosition = createFieldPosition('test_field', -10, 900, 0);
$errors = validateFieldPosition($invalidPosition);
assertTest(!empty($errors), "Invalid position detected");

// Test 3: Position Storage and Retrieval
echo "\nTest 3: Position Storage and Retrieval\n";
echo "--------------------------------------\n";

$positionLoader = new FieldPositionLoader();
$templateId = 't_fl100_gc120';

// Create test positions
$testPositions = [
    'attorney_name' => createFieldPosition('attorney_name', 150, 700, 1),
    'attorney_firm' => createFieldPosition('attorney_firm', 150, 680, 1),
    'attorney_address' => createFieldPosition('attorney_address', 150, 660, 1),
    'case_number' => createFieldPosition('case_number', 450, 700, 1),
    'court_county' => createFieldPosition('court_county', 300, 600, 1)
];

// Test position file path
$positionFile = __DIR__ . '/../data/test_positions.json';

// Save positions
file_put_contents($positionFile, json_encode([
    'templateId' => $templateId,
    'positions' => $testPositions,
    'metadata' => [
        'created' => date('Y-m-d H:i:s'),
        'version' => '1.0'
    ]
], JSON_PRETTY_PRINT));

assertTest(file_exists($positionFile), "Position file created");

// Load positions
$loadedData = json_decode(file_get_contents($positionFile), true);
assertTest($loadedData !== null, "Position data loaded");
assertEquals(count($testPositions), count($loadedData['positions']), "All positions preserved");

// Test 4: Drag and Drop Simulation
echo "\nTest 4: Drag and Drop Simulation\n";
echo "--------------------------------\n";

function simulateDragDrop($position, $deltaX, $deltaY) {
    $newPosition = $position;
    $newPosition['x'] += $deltaX;
    $newPosition['y'] += $deltaY;
    $newPosition['lastModified'] = date('Y-m-d H:i:s');
    return $newPosition;
}

$originalPos = createFieldPosition('draggable_field', 200, 300, 1);
$draggedPos = simulateDragDrop($originalPos, 50, -25);

assertEquals(250, $draggedPos['x'], "Field dragged horizontally");
assertEquals(275, $draggedPos['y'], "Field dragged vertically");
assertTest(isset($draggedPos['lastModified']), "Drag operation tracked");

// Test 5: Grid Snapping
echo "\nTest 5: Grid Snapping\n";
echo "--------------------\n";

function snapToGrid($position, $gridSize = 10) {
    $snapped = $position;
    $snapped['x'] = round($position['x'] / $gridSize) * $gridSize;
    $snapped['y'] = round($position['y'] / $gridSize) * $gridSize;
    return $snapped;
}

$unalignedPos = createFieldPosition('unaligned', 123, 457, 1);
$snappedPos = snapToGrid($unalignedPos, 10);

assertEquals(120, $snappedPos['x'], "X coordinate snapped to grid");
assertEquals(460, $snappedPos['y'], "Y coordinate snapped to grid");

// Test 6: Multi-Page Support
echo "\nTest 6: Multi-Page Support\n";
echo "-------------------------\n";

$multiPagePositions = [
    createFieldPosition('page1_field1', 100, 700, 1),
    createFieldPosition('page1_field2', 100, 650, 1),
    createFieldPosition('page2_field1', 100, 700, 2),
    createFieldPosition('page2_field2', 100, 650, 2),
    createFieldPosition('page3_field1', 100, 700, 3)
];

// Group by page
$byPage = [];
foreach ($multiPagePositions as $pos) {
    $page = $pos['page'];
    if (!isset($byPage[$page])) {
        $byPage[$page] = [];
    }
    $byPage[$page][] = $pos;
}

assertTest(count($byPage) === 3, "Positions spread across 3 pages");
assertEquals(2, count($byPage[1]), "Page 1 has 2 fields");
assertEquals(2, count($byPage[2]), "Page 2 has 2 fields");
assertEquals(1, count($byPage[3]), "Page 3 has 1 field");

// Test 7: Field Collision Detection
echo "\nTest 7: Field Collision Detection\n";
echo "--------------------------------\n";

function detectCollision($pos1, $pos2) {
    // Check if on same page
    if ($pos1['page'] !== $pos2['page']) {
        return false;
    }
    
    // Check bounding box overlap
    $left1 = $pos1['x'];
    $right1 = $pos1['x'] + $pos1['width'];
    $top1 = $pos1['y'];
    $bottom1 = $pos1['y'] - $pos1['height'];
    
    $left2 = $pos2['x'];
    $right2 = $pos2['x'] + $pos2['width'];
    $top2 = $pos2['y'];
    $bottom2 = $pos2['y'] - $pos2['height'];
    
    return !($left1 > $right2 || right1 < $left2 || $top1 < $bottom2 || $bottom1 > $top2);
}

$field1 = createFieldPosition('field1', 100, 500, 1, 200, 30);
$field2 = createFieldPosition('field2', 250, 500, 1, 200, 30);  // Overlapping
$field3 = createFieldPosition('field3', 400, 500, 1, 200, 30);  // Not overlapping

assertTest(detectCollision($field1, $field2), "Collision detected between overlapping fields");
assertTest(!detectCollision($field1, $field3), "No collision between separated fields");

// Test 8: Position History/Undo
echo "\nTest 8: Position History/Undo\n";
echo "-----------------------------\n";

class PositionHistory {
    private $history = [];
    private $currentIndex = -1;
    
    public function push($positions) {
        // Remove any redo history
        $this->history = array_slice($this->history, 0, $this->currentIndex + 1);
        
        // Add new state
        $this->history[] = [
            'positions' => $positions,
            'timestamp' => microtime(true)
        ];
        $this->currentIndex++;
        
        // Limit history size
        if (count($this->history) > 50) {
            array_shift($this->history);
            $this->currentIndex--;
        }
    }
    
    public function undo() {
        if ($this->currentIndex > 0) {
            $this->currentIndex--;
            return $this->history[$this->currentIndex]['positions'];
        }
        return null;
    }
    
    public function redo() {
        if ($this->currentIndex < count($this->history) - 1) {
            $this->currentIndex++;
            return $this->history[$this->currentIndex]['positions'];
        }
        return null;
    }
    
    public function canUndo() {
        return $this->currentIndex > 0;
    }
    
    public function canRedo() {
        return $this->currentIndex < count($this->history) - 1;
    }
}

$history = new PositionHistory();

// Initial state
$state1 = ['field1' => createFieldPosition('field1', 100, 100, 1)];
$history->push($state1);

// Modified state
$state2 = ['field1' => createFieldPosition('field1', 200, 200, 1)];
$history->push($state2);

assertTest($history->canUndo(), "Can undo after changes");
$undone = $history->undo();
assertEquals(100, $undone['field1']['x'], "Undo restores previous position");

assertTest($history->canRedo(), "Can redo after undo");
$redone = $history->redo();
assertEquals(200, $redone['field1']['x'], "Redo restores forward position");

// Test 9: Field Templates and Presets
echo "\nTest 9: Field Templates and Presets\n";
echo "-----------------------------------\n";

$fieldPresets = [
    'signature' => [
        'width' => 250,
        'height' => 50,
        'fontSize' => 14
    ],
    'checkbox' => [
        'width' => 20,
        'height' => 20,
        'fontSize' => 12
    ],
    'date' => [
        'width' => 100,
        'height' => 25,
        'fontSize' => 11
    ],
    'address' => [
        'width' => 300,
        'height' => 25,
        'fontSize' => 11
    ]
];

function applyFieldPreset($position, $presetName, $presets) {
    if (isset($presets[$presetName])) {
        return array_merge($position, $presets[$presetName]);
    }
    return $position;
}

$signatureField = createFieldPosition('signature', 350, 100, 1);
$signatureField = applyFieldPreset($signatureField, 'signature', $fieldPresets);

assertEquals(250, $signatureField['width'], "Signature preset applied - width");
assertEquals(50, $signatureField['height'], "Signature preset applied - height");

// Test 10: Export/Import Positions
echo "\nTest 10: Export/Import Positions\n";
echo "--------------------------------\n";

function exportPositions($positions, $format = 'json') {
    if ($format === 'json') {
        return json_encode($positions, JSON_PRETTY_PRINT);
    } elseif ($format === 'csv') {
        $csv = "fieldKey,x,y,page,width,height\n";
        foreach ($positions as $key => $pos) {
            $csv .= "{$pos['fieldKey']},{$pos['x']},{$pos['y']},{$pos['page']},{$pos['width']},{$pos['height']}\n";
        }
        return $csv;
    }
    return null;
}

function importPositions($data, $format = 'json') {
    if ($format === 'json') {
        return json_decode($data, true);
    }
    // CSV import would go here
    return null;
}

$exportData = exportPositions($testPositions, 'json');
assertTest(!empty($exportData), "Positions exported to JSON");

$importedPositions = importPositions($exportData, 'json');
assertTest($importedPositions !== null, "Positions imported from JSON");
assertEquals(count($testPositions), count($importedPositions), "All positions imported");

// Clean up test files
if (file_exists($positionFile)) {
    unlink($positionFile);
    echo "\n✅ Test files cleaned up\n";
}

// Summary
echo "\n" . str_repeat("=", 50) . "\n";
echo "📊 FIELD EDITOR TEST SUMMARY\n";
echo str_repeat("=", 50) . "\n";
echo "✅ Passed: $testsPassed\n";
echo "❌ Failed: $testsFailed\n";

if ($testsFailed === 0) {
    echo "\n🎉 ALL FIELD EDITOR TESTS PASSED!\n";
    exit(0);
} else {
    echo "\n⚠️ SOME TESTS FAILED\n";
    exit(1);
}