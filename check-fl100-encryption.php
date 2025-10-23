<?php
/**
 * Check FL-100 PDF Encryption Status
 * Verifies if the PDF is encrypted and analyzes its structure
 */

require_once __DIR__ . '/vendor/autoload.php';

use Smalot\PdfParser\Parser;

$pdfPath = __DIR__ . '/uploads/FL-100__With_Children_tortsewtga.pdf';

echo "=== FL-100 PDF Encryption Check ===\n\n";

// Check if file exists
if (!file_exists($pdfPath)) {
    die("ERROR: File not found at: $pdfPath\n");
}

echo "File: " . basename($pdfPath) . "\n";
echo "Size: " . number_format(filesize($pdfPath)) . " bytes\n";
echo "Path: $pdfPath\n\n";

// Method 1: Try with PdfParser
echo "--- Method 1: PdfParser Analysis ---\n";
try {
    $parser = new Parser();
    $pdf = $parser->parseFile($pdfPath);
    
    $details = $pdf->getDetails();
    
    echo "✓ PDF parsed successfully (NOT encrypted or user password not required)\n\n";
    
    echo "PDF Details:\n";
    foreach ($details as $key => $value) {
        if (is_array($value)) {
            echo "  $key: " . json_encode($value) . "\n";
        } else {
            echo "  $key: $value\n";
        }
    }
    
    echo "\nPages: " . count($pdf->getPages()) . "\n";
    
    // Try to extract text from first page
    $pages = $pdf->getPages();
    if (count($pages) > 0) {
        $firstPage = $pages[0];
        $text = substr($firstPage->getText(), 0, 200);
        echo "\nFirst 200 chars of text:\n" . $text . "...\n";
    }
    
} catch (Exception $e) {
    echo "✗ PdfParser failed: " . $e->getMessage() . "\n";
    echo "  (This might indicate encryption or parsing issues)\n";
}

// Method 2: Check with FPDI (attempts to read PDF structure)
echo "\n--- Method 2: FPDI Analysis ---\n";
try {
    require_once __DIR__ . '/vendor/setasign/fpdi/src/autoload.php';
    
    $pdf = new \setasign\Fpdi\Fpdi();
    $pageCount = $pdf->setSourceFile($pdfPath);
    
    echo "✓ FPDI can read the PDF (NOT encrypted)\n";
    echo "  Pages detected: $pageCount\n";
    
    // Import first page
    $tplId = $pdf->importPage(1);
    $size = $pdf->getTemplateSize($tplId);
    
    echo "  First page size: {$size['width']} x {$size['height']} pts\n";
    echo "  Orientation: " . ($size['orientation'] ?? 'N/A') . "\n";
    
} catch (Exception $e) {
    echo "✗ FPDI failed: " . $e->getMessage() . "\n";
    echo "  (This might indicate encryption)\n";
}

// Method 3: Check for encryption markers in raw file
echo "\n--- Method 3: Raw File Analysis ---\n";
$rawContent = file_get_contents($pdfPath, false, null, 0, 1024);

if (strpos($rawContent, '/Encrypt') !== false) {
    echo "⚠ WARNING: Found '/Encrypt' in PDF header\n";
    echo "  This PDF may have encryption metadata\n";
} else {
    echo "✓ No '/Encrypt' marker found in PDF header\n";
    echo "  PDF is likely unencrypted\n";
}

if (strpos($rawContent, '%PDF-') !== false) {
    preg_match('/%PDF-(\d+\.\d+)/', $rawContent, $matches);
    echo "  PDF Version: " . ($matches[1] ?? 'Unknown') . "\n";
}

// Method 4: Try pdftk if available
echo "\n--- Method 4: PDFtk Analysis (if available) ---\n";
$pdftkPath = __DIR__ . '/pdftk_installer.exe';
if (file_exists($pdftkPath) || shell_exec('where pdftk 2>nul')) {
    $cmd = "pdftk \"$pdfPath\" dump_data 2>&1";
    $output = shell_exec($cmd);
    
    if ($output && strpos($output, 'Error') === false) {
        echo "✓ PDFtk can read the PDF\n";
        
        // Check for encryption info
        if (preg_match('/InfoKey: Encrypted\nInfoValue: (.+)/i', $output, $matches)) {
            echo "  Encryption: " . $matches[1] . "\n";
        } else {
            echo "  No encryption info found\n";
        }
        
        // Get number of pages
        if (preg_match('/NumberOfPages: (\d+)/', $output, $matches)) {
            echo "  Pages: " . $matches[1] . "\n";
        }
    } else {
        echo "✗ PDFtk not available or failed\n";
    }
} else {
    echo "⊘ PDFtk not found in system\n";
}

echo "\n=== CONCLUSION ===\n";
echo "If all methods above succeeded, the PDF is NOT encrypted.\n";
echo "If methods failed with encryption errors, the PDF IS encrypted.\n";

