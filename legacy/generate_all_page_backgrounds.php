<?php
/**
 * Generate background images for all pages of FL-100 PDF
 */

echo "=== FL-100 Multi-Page Background Generator ===\n\n";

$pdfFile = __DIR__ . '/uploads/fl100.pdf';
$outputDir = __DIR__ . '/uploads';

if (!file_exists($pdfFile)) {
    die("ERROR: FL-100 PDF not found at $pdfFile\n");
}

// Find Ghostscript binary
$gsCandidates = [
    'gswin64c',
    'gswin32c',
    'gs',
    __DIR__ . '/gs1000w64.exe'
];

$gsBinary = null;
foreach ($gsCandidates as $candidate) {
    exec("where $candidate 2>nul", $output, $returnCode);
    if ($returnCode === 0 && !empty($output)) {
        $gsBinary = $output[0];
        break;
    }
    if (file_exists($candidate)) {
        $gsBinary = $candidate;
        break;
    }
}

if (!$gsBinary) {
    die("ERROR: Ghostscript not found. Install Ghostscript or place gs1000w64.exe in project root.\n");
}

echo "Using Ghostscript: $gsBinary\n\n";

// Generate background for each page
for ($page = 1; $page <= 3; $page++) {
    $outputFile = $outputDir . "/fl100_page{$page}_background.png";
    
    echo "Generating page $page background...\n";
    
    $cmd = "\"{$gsBinary}\" -dSAFER -dNOPAUSE -dBATCH -sDEVICE=pnggray -r200 " .
           "-dFirstPage={$page} -dLastPage={$page} " .
           "-sOutputFile=\"{$outputFile}\" \"{$pdfFile}\" 2>&1";
    
    $output = [];
    $returnCode = 0;
    exec($cmd, $output, $returnCode);
    
    if ($returnCode === 0 && file_exists($outputFile)) {
        $size = filesize($outputFile);
        echo "  ✓ Page $page generated: " . number_format($size) . " bytes\n";
        echo "  → $outputFile\n\n";
    } else {
        echo "  ✗ Failed to generate page $page\n";
        echo "  Error: " . implode("\n", $output) . "\n\n";
    }
}

echo "=== Summary ===\n";
echo "Check uploads/ directory for:\n";
echo "  - fl100_page1_background.png\n";
echo "  - fl100_page2_background.png\n";
echo "  - fl100_page3_background.png\n";

