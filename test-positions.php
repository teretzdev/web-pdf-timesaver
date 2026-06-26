<?php
// Simple test to check if the positions file can be read
$positionsFile = __DIR__ . '/data/t_fl105_gc120_positions.json';

echo "Testing positions file access\n";
echo "File: $positionsFile\n";
echo "Exists: " . (file_exists($positionsFile) ? 'YES' : 'NO') . "\n";

if (file_exists($positionsFile)) {
    $content = file_get_contents($positionsFile);
    echo "Content length: " . strlen($content) . " bytes\n";
    
    $positions = json_decode($content, true);
    echo "JSON decode success: " . (is_array($positions) ? 'YES' : 'NO') . "\n";
    
    if (is_array($positions)) {
        echo "Field count: " . count($positions) . "\n";
        echo "First field: " . array_key_first($positions) . "\n";
    } else {
        echo "JSON error: " . json_last_error_msg() . "\n";
    }
}

