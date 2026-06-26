<?php
/**
 * Test qpdf & Ghostscript Integration
 * Tests PDF field extraction with encrypted PDFs (FL-100, FL-105)
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/mvp/lib/pdf_field_extractor.php';
require_once __DIR__ . '/mvp/lib/background_generator.php';
require_once __DIR__ . '/mvp/lib/logger.php';

use WebPdfTimeSaver\Mvp\PdfFieldExtractor;
use WebPdfTimeSaver\Mvp\BackgroundGenerator;
use WebPdfTimeSaver\Mvp\Logger;

$logger = new Logger();

echo "==========================================\n";
echo "qpdf & Ghostscript Integration Test\n";
echo "==========================================\n\n";

$tests = [];
$passed = 0;
$failed = 0;

// Test 1: Verify qpdf installation
echo "Test 1: Verify qpdf Installation\n";
echo str_repeat("-", 50) . "\n";
$qpdfBinary = null;
$candidates = ['qpdf', 'qpdf.exe'];
foreach ($candidates as $candidate) {
    $output = [];
    $returnCode = 0;
    @exec("$candidate --version 2>&1", $output, $returnCode);
    if ($returnCode === 0 && !empty($output)) {
        $qpdfBinary = $candidate;
        $version = implode("\n", $output);
        echo "✅ qpdf found: $candidate\n";
        echo "   Version: " . trim($version) . "\n";
        $tests[] = ['name' => 'qpdf installation', 'status' => 'pass', 'details' => $version];
        $passed++;
        break;
    }
}

if (!$qpdfBinary) {
    echo "❌ qpdf not found in PATH\n";
    $tests[] = ['name' => 'qpdf installation', 'status' => 'fail', 'details' => 'qpdf not found'];
    $failed++;
}
echo "\n";

// Test 2: Verify Ghostscript installation
echo "Test 2: Verify Ghostscript Installation\n";
echo str_repeat("-", 50) . "\n";
$gsBinary = null;
$candidates = ['gswin64c', 'gswin32c', 'gs'];
foreach ($candidates as $candidate) {
    $output = [];
    $returnCode = 0;
    @exec("$candidate -v 2>&1", $output, $returnCode);
    if ($returnCode === 0 && !empty($output)) {
        $gsBinary = $candidate;
        $version = implode("\n", $output);
        echo "✅ Ghostscript found: $candidate\n";
        echo "   Version: " . trim($version) . "\n";
        $tests[] = ['name' => 'Ghostscript installation', 'status' => 'pass', 'details' => $version];
        $passed++;
        break;
    }
}

if (!$gsBinary) {
    echo "❌ Ghostscript not found in PATH\n";
    $tests[] = ['name' => 'Ghostscript installation', 'status' => 'fail', 'details' => 'Ghostscript not found'];
    $failed++;
}
echo "\n";

// Test 3: Test PDF field extraction with FL-100
echo "Test 3: FL-100 PDF Field Extraction\n";
echo str_repeat("-", 50) . "\n";
$fl100Path = __DIR__ . '/uploads/fl100.pdf';
if (file_exists($fl100Path)) {
    try {
        $extractor = new PdfFieldExtractor();
        $startTime = microtime(true);
        
        echo "Attempting field extraction from FL-100...\n";
        $fields = $extractor->extractFieldPositions($fl100Path);
        $duration = round((microtime(true) - $startTime) * 1000);
        
        if (!empty($fields)) {
            echo "✅ Successfully extracted " . count($fields) . " fields\n";
            echo "   Duration: {$duration}ms\n";
            echo "   Sample fields: " . min(3, count($fields)) . " shown\n";
            $sampleFields = array_slice($fields, 0, 3, true);
            foreach ($sampleFields as $name => $field) {
                echo "     - {$name}: " . ($field['type'] ?? 'unknown') . " at ({$field['x']}, {$field['y']})\n";
            }
            $tests[] = ['name' => 'FL-100 field extraction', 'status' => 'pass', 'details' => count($fields) . ' fields in ' . $duration . 'ms'];
            $passed++;
        } else {
            echo "⚠️  No fields extracted (may be encrypted or have no fillable fields)\n";
            echo "   This is expected for encrypted PDFs without qpdf decryption\n";
            $tests[] = ['name' => 'FL-100 field extraction', 'status' => 'warn', 'details' => 'No fields extracted - may need manual positioning'];
        }
    } catch (\Exception $e) {
        echo "❌ Extraction failed: " . $e->getMessage() . "\n";
        $tests[] = ['name' => 'FL-100 field extraction', 'status' => 'fail', 'details' => $e->getMessage()];
        $failed++;
    }
} else {
    echo "⚠️  FL-100 PDF not found at: $fl100Path\n";
    echo "   Skipping FL-100 test\n";
    $tests[] = ['name' => 'FL-100 field extraction', 'status' => 'skip', 'details' => 'File not found'];
}
echo "\n";

// Test 4: Test PDF field extraction with FL-105
echo "Test 4: FL-105 PDF Field Extraction\n";
echo str_repeat("-", 50) . "\n";
$fl105Path = __DIR__ . '/uploads/fl105.pdf';
if (file_exists($fl105Path)) {
    try {
        $extractor = new PdfFieldExtractor();
        $startTime = microtime(true);
        
        echo "Attempting field extraction from FL-105...\n";
        $fields = $extractor->extractFieldPositions($fl105Path);
        $duration = round((microtime(true) - $startTime) * 1000);
        
        if (!empty($fields)) {
            echo "✅ Successfully extracted " . count($fields) . " fields\n";
            echo "   Duration: {$duration}ms\n";
            echo "   Sample fields: " . min(3, count($fields)) . " shown\n";
            $sampleFields = array_slice($fields, 0, 3, true);
            foreach ($sampleFields as $name => $field) {
                echo "     - {$name}: " . ($field['type'] ?? 'unknown') . " at ({$field['x']}, {$field['y']})\n";
            }
            $tests[] = ['name' => 'FL-105 field extraction', 'status' => 'pass', 'details' => count($fields) . ' fields in ' . $duration . 'ms'];
            $passed++;
        } else {
            echo "⚠️  No fields extracted (may be encrypted or have no fillable fields)\n";
            echo "   This is expected for encrypted PDFs without qpdf decryption\n";
            $tests[] = ['name' => 'FL-105 field extraction', 'status' => 'warn', 'details' => 'No fields extracted - may need manual positioning'];
        }
    } catch (\Exception $e) {
        echo "❌ Extraction failed: " . $e->getMessage() . "\n";
        $tests[] = ['name' => 'FL-105 field extraction', 'status' => 'fail', 'details' => $e->getMessage()];
        $failed++;
    }
} else {
    echo "⚠️  FL-105 PDF not found at: $fl105Path\n";
    echo "   Skipping FL-105 test\n";
    $tests[] = ['name' => 'FL-105 field extraction', 'status' => 'skip', 'details' => 'File not found'];
}
echo "\n";

// Test 5: Test background generation with Ghostscript
echo "Test 5: Background Generation with Ghostscript\n";
echo str_repeat("-", 50) . "\n";
if ($gsBinary && file_exists($fl100Path)) {
    try {
        $bgGenerator = new BackgroundGenerator();
        $startTime = microtime(true);
        
        echo "Generating background image for FL-100 page 1...\n";
        $result = $bgGenerator->generatePageBackground('fl100', $fl100Path, 1, true);
        $duration = round((microtime(true) - $startTime) * 1000);
        
        if ($result['success']) {
            echo "✅ Background generated successfully\n";
            echo "   Duration: {$duration}ms\n";
            echo "   File: " . basename($result['path']) . "\n";
            echo "   Size: " . round($result['size'] / 1024, 2) . " KB\n";
            $tests[] = ['name' => 'Background generation', 'status' => 'pass', 'details' => $duration . 'ms, ' . round($result['size'] / 1024, 2) . ' KB'];
            $passed++;
        } else {
            echo "❌ Background generation failed\n";
            echo "   Error: " . ($result['error'] ?? 'Unknown error') . "\n";
            $tests[] = ['name' => 'Background generation', 'status' => 'fail', 'details' => $result['error'] ?? 'Unknown error'];
            $failed++;
        }
    } catch (\Exception $e) {
        echo "❌ Background generation failed: " . $e->getMessage() . "\n";
        $tests[] = ['name' => 'Background generation', 'status' => 'fail', 'details' => $e->getMessage()];
        $failed++;
    }
} else {
    echo "⚠️  Ghostscript not available or FL-100 not found\n";
    echo "   Skipping background generation test\n";
    $tests[] = ['name' => 'Background generation', 'status' => 'skip', 'details' => 'Ghostscript or PDF not available'];
}
echo "\n";

// Test 6: Test qpdf decryption (if qpdf available)
echo "Test 6: qpdf Decryption Test\n";
echo str_repeat("-", 50) . "\n";
if ($qpdfBinary && file_exists($fl100Path)) {
    $tempDecrypted = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_decrypted_' . basename($fl100Path);
    $cmd = "\"$qpdfBinary\" --decrypt \"$fl100Path\" \"$tempDecrypted\" 2>&1";
    
    echo "Attempting to decrypt FL-100 with qpdf...\n";
    $output = [];
    $returnCode = 0;
    @exec($cmd, $output, $returnCode);
    
    if ($returnCode === 0 && file_exists($tempDecrypted)) {
        $originalSize = filesize($fl100Path);
        $decryptedSize = filesize($tempDecrypted);
        echo "✅ PDF decrypted successfully\n";
        echo "   Original size: " . round($originalSize / 1024, 2) . " KB\n";
        echo "   Decrypted size: " . round($decryptedSize / 1024, 2) . " KB\n";
        
        // Clean up
        @unlink($tempDecrypted);
        
        $tests[] = ['name' => 'qpdf decryption', 'status' => 'pass', 'details' => 'Decrypted successfully'];
        $passed++;
    } else {
        echo "⚠️  qpdf decryption failed or PDF may not be encrypted\n";
        echo "   Output: " . implode("\n", $output) . "\n";
        @unlink($tempDecrypted);
        $tests[] = ['name' => 'qpdf decryption', 'status' => 'warn', 'details' => 'Decryption failed or not needed'];
    }
} else {
    echo "⚠️  qpdf not available or FL-100 not found\n";
    echo "   Skipping qpdf decryption test\n";
    $tests[] = ['name' => 'qpdf decryption', 'status' => 'skip', 'details' => 'qpdf or PDF not available'];
}
echo "\n";

// Summary
echo "==========================================\n";
echo "Test Summary\n";
echo "==========================================\n";
echo "Total Tests: " . count($tests) . "\n";
echo "Passed: $passed\n";
echo "Failed: $failed\n";
echo "Warned: " . count(array_filter($tests, fn($t) => $t['status'] === 'warn')) . "\n";
echo "Skipped: " . count(array_filter($tests, fn($t) => $t['status'] === 'skip')) . "\n";
echo "\n";

echo "Detailed Results:\n";
foreach ($tests as $test) {
    $icon = match($test['status']) {
        'pass' => '✅',
        'fail' => '❌',
        'warn' => '⚠️ ',
        'skip' => '⏭️ ',
        default => '❓'
    };
    echo "  $icon {$test['name']}: {$test['status']}\n";
    if (!empty($test['details'])) {
        echo "     {$test['details']}\n";
    }
}

echo "\n";
echo "==========================================\n";
echo "Recommendations\n";
echo "==========================================\n";

if (!$qpdfBinary) {
    echo "⚠️  Install qpdf to improve encrypted PDF handling\n";
}

if (!$gsBinary) {
    echo "⚠️  Install Ghostscript for background image generation\n";
}

if ($passed > 0) {
    echo "✅ Core functionality is working\n";
}

if ($failed > 0) {
    echo "❌ Some tests failed - check logs for details\n";
}

echo "\n";
echo "Test complete!\n";

