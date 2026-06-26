<?php
/**
 * Detailed Position Accuracy Test
 * Compare extracted positions with reference after proper conversion
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/mvp/lib/field_name_mapper.php';

use WebPdfTimeSaver\Mvp\FieldNameMapper;

echo "==========================================\n";
echo "Detailed Position Accuracy Analysis\n";
echo "==========================================\n\n";

// Load positions
$extractedFile = __DIR__ . '/data/temp_1762518209_positions.json';
$referenceFile = __DIR__ . '/data/t_fl100_gc120_positions.json';

$extracted = json_decode(file_get_contents($extractedFile), true);
$reference = json_decode(file_get_contents($referenceFile), true);

// Normalize positions
$normalized = FieldNameMapper::normalizePositions($extracted, $reference);

echo "Comparing Normalized Positions with Reference:\n";
echo str_repeat("-", 80) . "\n\n";

$matches = 0;
$close = 0;
$misaligned = 0;
$missing = 0;
$tolerance = 15; // 15 points tolerance (about 5mm)
$closeTolerance = 30; // 30 points for "close" matches

$comparisons = [];

foreach ($reference as $refName => $refData) {
    $refX = (float)$refData['x'];
    $refY = (float)$refData['y'];
    
    if (isset($normalized[$refName])) {
        $normData = $normalized[$refName];
        $normX = (float)$normData['x'];
        $normY = (float)$normData['y'];
        
        $diffX = abs($refX - $normX);
        $diffY = abs($refY - $normY);
        $distance = sqrt($diffX * $diffX + $diffY * $diffY);
        
        $comparisons[] = [
            'field' => $refName,
            'ref' => ['x' => $refX, 'y' => $refY],
            'norm' => ['x' => $normX, 'y' => $normY],
            'diff' => ['x' => round($diffX, 2), 'y' => round($diffY, 2)],
            'distance' => round($distance, 2),
            'original' => $normData['original_name'] ?? $refName
        ];
        
        if ($diffX <= $tolerance && $diffY <= $tolerance) {
            $matches++;
        } elseif ($distance <= $closeTolerance) {
            $close++;
        } else {
            $misaligned++;
        }
    } else {
        $missing++;
        $comparisons[] = [
            'field' => $refName,
            'ref' => ['x' => $refX, 'y' => $refY],
            'norm' => null,
            'diff' => null,
            'distance' => null,
            'original' => null
        ];
    }
}

// Sort by distance (best matches first)
usort($comparisons, function($a, $b) {
    if ($a['distance'] === null) return 1;
    if ($b['distance'] === null) return -1;
    return $a['distance'] <=> $b['distance'];
});

echo "Results Summary:\n";
echo "✅ Exact matches (within {$tolerance}pt): {$matches}\n";
echo "⚠️  Close matches (within {$closeTolerance}pt): {$close}\n";
echo "❌ Misaligned (> {$closeTolerance}pt): {$misaligned}\n";
echo "❌ Missing: {$missing}\n";
echo "Total reference fields: " . count($reference) . "\n\n";

$accuracy = count($reference) > 0 ? (($matches + $close) / count($reference)) * 100 : 0;
echo "Overall Accuracy: " . number_format($accuracy, 1) . "%\n";
echo "   (Exact: " . number_format(($matches / count($reference)) * 100, 1) . "%)\n\n";

echo "==========================================\n";
echo "Best Matches (Top 10)\n";
echo "==========================================\n";
foreach (array_slice($comparisons, 0, 10) as $comp) {
    if ($comp['norm']) {
        $icon = $comp['distance'] <= $tolerance ? '✅' : ($comp['distance'] <= $closeTolerance ? '⚠️ ' : '❌');
        echo "$icon {$comp['field']}\n";
        echo "   Ref: ({$comp['ref']['x']}, {$comp['ref']['y']})\n";
        echo "   Norm: ({$comp['norm']['x']}, {$comp['norm']['y']})\n";
        echo "   Diff: ({$comp['diff']['x']}, {$comp['diff']['y']}) distance: {$comp['distance']}pt\n";
        echo "   Original: {$comp['original']}\n\n";
    }
}

echo "==========================================\n";
echo "Missing Fields\n";
echo "==========================================\n";
$missingFields = array_filter($comparisons, fn($c) => $c['norm'] === null);
foreach ($missingFields as $comp) {
    echo "❌ {$comp['field']}\n";
    echo "   Ref: ({$comp['ref']['x']}, {$comp['ref']['y']})\n";
    echo "   Status: Not found in extracted positions\n\n";
}

echo "==========================================\n";
echo "Analysis\n";
echo "==========================================\n";

if ($accuracy >= 70) {
    echo "✅ Position accuracy is GOOD ({$accuracy}%)\n";
    echo "   The extracted positions are usable with minor adjustments.\n";
} elseif ($accuracy >= 50) {
    echo "⚠️  Position accuracy is MODERATE ({$accuracy}%)\n";
    echo "   Some positions need manual adjustment.\n";
} else {
    echo "❌ Position accuracy is LOW ({$accuracy}%)\n";
    echo "   Manual position adjustment recommended.\n";
}

echo "\n";
echo "Note: Extracted positions are from text-layer analysis (estimated).\n";
echo "Reference positions are manually calibrated (precise).\n";
echo "Differences are expected - text labels may not align with form fields.\n";

