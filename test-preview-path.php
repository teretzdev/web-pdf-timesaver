<?php
// Test the exact path resolution that preview.php uses
$viewDir = __DIR__ . '/mvp/views';
echo "View Dir: $viewDir\n";
echo "__DIR__ would be: $viewDir\n";

$projectRoot = dirname(dirname($viewDir));
echo "Project Root: $projectRoot\n";

$pdfPath = $projectRoot . DIRECTORY_SEPARATOR . 'output' . DIRECTORY_SEPARATOR . 'mvp_20251003_022842_t_fl100_gc120.pdf';
echo "PDF Path: $pdfPath\n";
echo "Exists: " . (file_exists($pdfPath) ? 'YES' : 'NO') . "\n";
echo "Realpath: " . (realpath($pdfPath) ?: 'FALSE') . "\n";

