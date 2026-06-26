<?php
/**
 * Test FL-110 extraction to diagnose why no fields are detected
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/mvp/lib/pdf_field_extractor.php';
require_once __DIR__ . '/mvp/lib/auto_position_extractor.php';

use WebPdfTimeSaver\Mvp\PdfFieldExtractor;
use WebPdfTimeSaver\Mvp\AutoPositionExtractor;
use Smalot\PdfParser\Parser;

echo "=== FL-110 Extraction Diagnostic Test ===\n\n";

// Find FL-110 PDF - check multiple locations
$fl110Path = null;
$possibleDirs = [
    __DIR__ . '/uploads',
    'C:/xampp/htdocs/Web-PDFTimeSaver/uploads',
    'C:/xampp/htdocs/Web-PDFTimeSaver/mvp/../uploads'
];

// First try the specific file from browser
$specificFile = 'C:/xampp/htdocs/Web-PDFTimeSaver/uploads/auto_1763401055911.pdf';
if (file_exists($specificFile)) {
    $fl110Path = $specificFile;
    echo "Found uploaded file: " . basename($fl110Path) . "\n";
} else {
    // Try searching in all directories
    foreach ($possibleDirs as $dir) {
        if (!is_dir($dir)) continue;
        
        $files = glob($dir . '/*FL-110*.pdf');
        if (empty($files)) {
            $files = glob($dir . '/*fl110*.pdf');
        }
        if (empty($files)) {
            // Try auto_ files (recent uploads)
            $autoFiles = glob($dir . '/auto_*.pdf');
            if (!empty($autoFiles)) {
                // Get most recent
                usort($autoFiles, function($a, $b) {
                    return filemtime($b) - filemtime($a);
                });
                $fl110Path = $autoFiles[0];
                echo "Using most recent auto-upload from $dir: " . basename($fl110Path) . "\n";
                break;
            }
        } else {
            $fl110Path = $files[0];
            echo "Found FL-110 in $dir: " . basename($fl110Path) . "\n";
            break;
        }
    }
}

if (!$fl110Path || !file_exists($fl110Path)) {
    die("ERROR: FL-110 PDF not found. Please upload it first.\n");
}

echo "PDF Path: $fl110Path\n";
echo "File size: " . filesize($fl110Path) . " bytes\n\n";

// STEP 1: Check if PDF is encrypted
echo "STEP 1: Checking PDF encryption...\n";
$parser = new Parser();
try {
    $pdf = $parser->parseFile($fl110Path);
    echo "  ✅ PDF parsed successfully (not encrypted or decrypted)\n";
    $pages = $pdf->getPages();
    echo "  Pages: " . count($pages) . "\n";
} catch (Exception $e) {
    echo "  ⚠️  PDF parsing error: " . $e->getMessage() . "\n";
    echo "  This might indicate encryption. Will try decryption.\n";
}

// STEP 2: Check for fillable fields with PdfParser
echo "\nSTEP 2: Checking for fillable fields (PdfParser)...\n";
$hasFields = false;
$fieldCount = 0;
try {
    $pdf = $parser->parseFile($fl110Path);
    $pages = $pdf->getPages();
    
    foreach ($pages as $pageNum => $page) {
        $annotations = $page->get('Annots');
        if ($annotations) {
            $annotArray = $annotations->getContent();
            if (is_array($annotArray)) {
                foreach ($annotArray as $annot) {
                    if (is_object($annot) && $annot->get('T')) {
                        $fieldCount++;
                        $hasFields = true;
                    }
                }
            }
        }
    }
    echo "  Fields found: $fieldCount\n";
    echo "  Has fillable fields: " . ($hasFields ? 'YES' : 'NO') . "\n";
} catch (Exception $e) {
    echo "  ❌ Error: " . $e->getMessage() . "\n";
}

// STEP 3: Try Node.js ensemble extraction
echo "\nSTEP 3: Trying Node.js ensemble extraction...\n";
$autoExtractor = new AutoPositionExtractor();
if ($autoExtractor->isAvailable()) {
    echo "  ✅ Node.js available\n";
    $templateId = 't_fl110_test_' . time();
    echo "  Template ID: $templateId\n";
    echo "  Running extraction...\n";
    
    $result = $autoExtractor->extractPositions($fl110Path, $templateId);
    
    echo "\n  Extraction Results:\n";
    echo "    Success: " . ($result['success'] ? 'YES' : 'NO') . "\n";
    echo "    Fields: " . count($result['fields'] ?? []) . "\n";
    echo "    Method: " . ($result['method'] ?? 'unknown') . "\n";
    
    if (isset($result['methodsUsed']) && is_array($result['methodsUsed'])) {
        echo "    Methods used: " . implode(', ', $result['methodsUsed']) . "\n";
    }
    
    if (!empty($result['errors'])) {
        echo "    Errors: " . implode(', ', $result['errors']) . "\n";
    }
    
    if (!empty($result['fields'])) {
        echo "\n  Sample fields:\n";
        foreach (array_slice($result['fields'], 0, 5) as $field) {
            $name = $field['name'] ?? $field['canonicalName'] ?? 'unnamed';
            echo "    - $name: " . ($field['type'] ?? 'unknown') . " at (" . ($field['x'] ?? 0) . ", " . ($field['y'] ?? 0) . ")\n";
        }
    }
} else {
    echo "  ❌ Node.js not available\n";
}

// STEP 4: Try PdfFieldExtractor (includes static field detection)
echo "\nSTEP 4: Trying PdfFieldExtractor (with static field detection)...\n";
$extractor = new PdfFieldExtractor();
$templateId = 't_fl110_test_' . time();
$result = $extractor->extractAndGenerateBackgrounds($fl110Path, $templateId, $uploadsDir);

echo "  Fields extracted: " . count($result['fields'] ?? []) . "\n";
echo "  Backgrounds generated: " . count($result['backgrounds'] ?? []) . "\n";

if (!empty($result['fields'])) {
    echo "\n  Sample fields:\n";
    $fields = $result['fields'];
    $count = 0;
    foreach ($fields as $key => $field) {
        if ($count++ >= 5) break;
        echo "    - $key: " . ($field['type'] ?? 'unknown') . " at (" . ($field['x'] ?? 0) . ", " . ($field['y'] ?? 0) . ")\n";
    }
}

echo "\n=== Test Complete ===\n";

