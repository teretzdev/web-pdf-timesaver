<?php
/**
 * Quick test script to verify Node.js detection works on Linux
 * Run: php test-node-detection.php
 */

require_once __DIR__ . '/mvp/lib/auto_position_extractor.php';

echo "=== Node.js Detection Test ===\n\n";

$extractor = new \WebPdfTimeSaver\Mvp\AutoPositionExtractor();
$status = $extractor->getStatus();

echo "Node.js Available: " . ($status['nodejs_available'] ? 'YES ✅' : 'NO ❌') . "\n";
echo "Node.js Path: " . ($status['nodejs_path'] ?: 'NOT FOUND') . "\n";
echo "Script Available: " . ($status['script_available'] ? 'YES ✅' : 'NO ❌') . "\n";
echo "Script Path: " . ($status['script_path'] ?: 'NOT FOUND') . "\n";
echo "qpdf Available: " . ($status['qpdf_available'] ? 'YES ✅' : 'NO ❌') . "\n\n";

if ($status['nodejs_available'] && $status['nodejs_path']) {
    echo "Testing Node.js execution:\n";
    $output = [];
    $returnCode = 0;
    exec(escapeshellarg($status['nodejs_path']) . ' --version 2>&1', $output, $returnCode);
    if ($returnCode === 0 && !empty($output[0])) {
        echo "  Version: " . trim($output[0]) . " ✅\n";
    } else {
        echo "  Failed to execute Node.js ❌\n";
    }
}

echo "\n=== Test Complete ===\n";

