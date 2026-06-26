<?php
$path = __DIR__ . '/mvp/views/../../output/mvp_20251003_022842_t_fl100_gc120.pdf';
echo "Path: $path\n";
echo "Realpath: " . (realpath($path) ?: 'FALSE') . "\n";
echo "Exists: " . (file_exists($path) ? 'YES' : 'NO') . "\n";
echo "Dir: " . __DIR__ . "\n";
$viewDir = __DIR__ . '/mvp/views';
echo "View Dir: $viewDir\n";
$fromView = $viewDir . '/../../output/mvp_20251003_022842_t_fl100_gc120.pdf';
echo "From view: $fromView\n";
echo "From view realpath: " . (realpath($fromView) ?: 'FALSE') . "\n";
echo "From view exists: " . (file_exists($fromView) ? 'YES' : 'NO') . "\n";


