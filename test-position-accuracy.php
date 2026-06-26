<?php
/**
 * Test Position Accuracy
 * Compare extracted positions with known good reference positions
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "==========================================\n";
echo "Position Accuracy Verification\n";
echo "==========================================\n\n";

// Load reference positions (known good)
$referenceFile = __DIR__ . '/data/t_fl100_gc120_positions.json';
$extractedFile = __DIR__ . '/data/temp_1762518209_positions.json';

if (!file_exists($referenceFile)) {
    die("❌ Reference file not found: $referenceFile\n");
}

if (!file_exists($extractedFile)) {
    die("❌ Extracted file not found: $extractedFile\n");
}

$reference = json_decode(file_get_contents($referenceFile), true);
$extracted = json_decode(file_get_contents($extractedFile), true);

if (!$reference || !$extracted) {
    die("❌ Failed to parse JSON files\n");
}

echo "Reference positions: " . count($reference) . " fields\n";
echo "Extracted positions: " . count($extracted) . " fields\n\n";

// Note: Coordinates may be in different units (mm vs points)
// Reference uses points (typically), extracted uses mm
// Conversion: 1 point = 0.352778 mm

function convertMmToPoints(float $mm): float {
    return $mm * 2.834645669; // 1mm = 2.834645669 points
}

function convertPointsToMm(float $points): float {
    return $points / 2.834645669;
}

$tolerance = 5; // 5mm or ~14 points tolerance
$matches = 0;
$misaligned = 0;
$missing = 0;
$extra = 0;
$comparisons = [];

echo "Comparing positions (tolerance: {$tolerance}mm)...\n";
echo str_repeat("-", 80) . "\n";

// Compare common fields
$referenceKeys = array_keys($reference);
$extractedKeys = array_keys($extracted);

// Check for matches by field name similarity
foreach ($referenceKeys as $refKey) {
    $refPos = $reference[$refKey];
    $refX = (float)($refPos['x'] ?? 0);
    $refY = (float)($refPos['y'] ?? 0);
    
    // Try to find matching extracted field
    $found = false;
    $bestMatch = null;
    $bestDistance = PHP_FLOAT_MAX;
    
    // Normalize field names for comparison (remove underscores, lowercase)
    $refKeyNormalized = strtolower(str_replace(['_', '-'], '', $refKey));
    
    foreach ($extractedKeys as $extKey) {
        $extKeyNormalized = strtolower(str_replace(['_', '-'], '', $extKey));
        
        // Check if field names match (exact or partial)
        if ($refKeyNormalized === $extKeyNormalized || 
            strpos($refKeyNormalized, $extKeyNormalized) !== false ||
            strpos($extKeyNormalized, $refKeyNormalized) !== false) {
            
            $extPos = $extracted[$extKey];
            $extX = (float)($extPos['x'] ?? 0);
            $extY = (float)($extPos['y'] ?? 0);
            
            // Convert extracted mm to points if needed, or compare in same units
            // Assume reference is in points (typical), extracted might be in mm
            $extXPoints = $extX > 100 ? $extX : convertMmToPoints($extX); // If < 100, likely mm
            $extYPoints = $extY > 100 ? $extY : convertMmToPoints($extY);
            
            $distance = sqrt(pow($refX - $extXPoints, 2) + pow($refY - $extYPoints, 2));
            
            if ($distance < $bestDistance) {
                $bestMatch = $extKey;
                $bestDistance = $distance;
            }
        }
    }
    
    if ($bestMatch && $bestDistance < ($tolerance * 2.834645669)) { // Convert tolerance to points
        $extPos = $extracted[$bestMatch];
        $extX = (float)($extPos['x'] ?? 0);
        $extY = (float)($extPos['y'] ?? 0);
        $extXPoints = $extX > 100 ? $extX : convertMmToPoints($extX);
        $extYPoints = $extY > 100 ? $extY : convertMmToPoints($extY);
        
        $diffX = abs($refX - $extXPoints);
        $diffY = abs($refY - $extYPoints);
        
        if ($diffX <= ($tolerance * 2.834645669) && $diffY <= ($tolerance * 2.834645669)) {
            $matches++;
            $comparisons[] = [
                'status' => 'match',
                'ref_field' => $refKey,
                'ext_field' => $bestMatch,
                'ref_pos' => ['x' => $refX, 'y' => $refY],
                'ext_pos' => ['x' => $extX, 'y' => $extY],
                'diff' => ['x' => round($diffX, 2), 'y' => round($diffY, 2)]
            ];
        } else {
            $misaligned++;
            $comparisons[] = [
                'status' => 'misaligned',
                'ref_field' => $refKey,
                'ext_field' => $bestMatch,
                'ref_pos' => ['x' => $refX, 'y' => $refY],
                'ext_pos' => ['x' => $extX, 'y' => $extY],
                'diff' => ['x' => round($diffX, 2), 'y' => round($diffY, 2)]
            ];
        }
        $found = true;
    } else {
        $missing++;
        $comparisons[] = [
            'status' => 'missing',
            'ref_field' => $refKey,
            'ext_field' => null,
            'ref_pos' => ['x' => $refX, 'y' => $refY],
            'ext_pos' => null,
            'diff' => null
        ];
    }
}

// Find extra fields in extracted
foreach ($extractedKeys as $extKey) {
    $found = false;
    foreach ($referenceKeys as $refKey) {
        $refKeyNormalized = strtolower(str_replace(['_', '-'], '', $refKey));
        $extKeyNormalized = strtolower(str_replace(['_', '-'], '', $extKey));
        if ($refKeyNormalized === $extKeyNormalized || 
            strpos($refKeyNormalized, $extKeyNormalized) !== false ||
            strpos($extKeyNormalized, $refKeyNormalized) !== false) {
            $found = true;
            break;
        }
    }
    if (!$found) {
        $extra++;
    }
}

echo "\n";
echo "==========================================\n";
echo "Accuracy Results\n";
echo "==========================================\n";
echo "Total Reference Fields: " . count($reference) . "\n";
echo "Total Extracted Fields: " . count($extracted) . "\n";
echo "Matches: $matches\n";
echo "Misaligned: $misaligned\n";
echo "Missing: $missing\n";
echo "Extra: $extra\n";
echo "\n";

$accuracy = count($reference) > 0 ? ($matches / count($reference)) * 100 : 0;
echo "Accuracy: " . number_format($accuracy, 1) . "%\n";
echo "\n";

// Show sample comparisons
echo "Sample Comparisons:\n";
echo str_repeat("-", 80) . "\n";
$sampleCount = 0;
foreach ($comparisons as $comp) {
    if ($sampleCount >= 10) break;
    
    $icon = match($comp['status']) {
        'match' => '✅',
        'misaligned' => '⚠️ ',
        'missing' => '❌',
        default => '❓'
    };
    
    echo "$icon {$comp['ref_field']}\n";
    if ($comp['ext_field']) {
        echo "   Ref: ({$comp['ref_pos']['x']}, {$comp['ref_pos']['y']})\n";
        echo "   Ext: ({$comp['ext_pos']['x']}, {$comp['ext_pos']['y']})\n";
        if ($comp['diff']) {
            echo "   Diff: ({$comp['diff']['x']}, {$comp['diff']['y']}) points\n";
        }
    } else {
        echo "   Ref: ({$comp['ref_pos']['x']}, {$comp['ref_pos']['y']})\n";
        echo "   Ext: NOT FOUND\n";
    }
    echo "\n";
    $sampleCount++;
}

// Check verification report if available
$verificationFile = __DIR__ . '/data/temp_1762518209_verification_report.json';
if (file_exists($verificationFile)) {
    echo "\n";
    echo "==========================================\n";
    echo "Auto-Verification Report\n";
    echo "==========================================\n";
    $verification = json_decode(file_get_contents($verificationFile), true);
    if ($verification && isset($verification['summary'])) {
        $summary = $verification['summary'];
        echo "Fields: " . ($summary['fields'] ?? 'N/A') . "\n";
        echo "Average Score: " . ($summary['avgScore'] ?? 'N/A') . "\n";
        echo "Warnings: " . ($summary['warns'] ?? 'N/A') . "\n";
        echo "Fails: " . ($summary['fails'] ?? 'N/A') . "\n";
        echo "Verdict: " . ($summary['pass'] ? 'PASS' : 'FAIL/WARN') . "\n";
    }
}

echo "\n";
echo "==========================================\n";
echo "Conclusion\n";
echo "==========================================\n";

if ($accuracy >= 80) {
    echo "✅ Position accuracy is GOOD ({$accuracy}%)\n";
} elseif ($accuracy >= 60) {
    echo "⚠️  Position accuracy is MODERATE ({$accuracy}%)\n";
    echo "   Some fields may need manual adjustment\n";
} else {
    echo "❌ Position accuracy is LOW ({$accuracy}%)\n";
    echo "   Manual position adjustment recommended\n";
}

echo "\n";
echo "Note: Coordinate systems may differ (points vs mm)\n";
echo "Extracted positions use text-layer analysis (estimated positions)\n";
echo "Reference positions use manual calibration (precise positions)\n";

