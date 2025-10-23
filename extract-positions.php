<?php
/**
 * CLI Tool for Automatic PDF Position Extraction
 * Usage: php extract-positions.php <pdf-file> <template-id>
 * Example: php extract-positions.php uploads/w9.pdf t_w9_auto
 */

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/mvp/lib/pdf_field_extractor.php';
require_once __DIR__ . '/mvp/lib/auto_position_extractor.php';

use WebPdfTimeSaver\Mvp\PdfFieldExtractor;
use WebPdfTimeSaver\Mvp\AutoPositionExtractor;

// Check arguments
if ($argc < 3) {
    echo "Usage: php extract-positions.php <pdf-file> <template-id>\n";
    echo "Example: php extract-positions.php uploads/w9.pdf t_w9_auto\n";
    echo "\n";
    echo "This tool automatically extracts field positions from PDFs using:\n";
    echo "1. Node.js + pdf-lib (primary method)\n";
    echo "2. PHP PdfParser (fallback)\n";
    echo "3. pdftk (last resort)\n";
    exit(1);
}

$pdfFile = $argv[1];
$templateId = $argv[2];

if (!file_exists($pdfFile)) {
    echo "Error: PDF file not found: $pdfFile\n";
    exit(1);
}

echo "===============================================\n";
echo "  Automatic PDF Position Extraction\n";
echo "===============================================\n";
echo "PDF: $pdfFile\n";
echo "Template: $templateId\n";
echo str_repeat("-", 60) . "\n";

// Check system status
echo "🔍 Checking system status...\n";
$autoExtractor = new AutoPositionExtractor();
$status = $autoExtractor->getStatus();

echo "Node.js: " . ($status['nodejs_available'] ? "✅ Available" : "❌ Not found") . "\n";
echo "Script: " . ($status['script_available'] ? "✅ Available" : "❌ Not found") . "\n";
echo "qpdf: " . ($status['qpdf_available'] ? "✅ Available" : "⚠️  Not found") . "\n";
echo "";

// Extract fields
echo "🚀 Starting extraction...\n";
$extractor = new PdfFieldExtractor();

try {
    $fields = $extractor->extractFieldPositions($pdfFile);
    
    if (empty($fields)) {
        echo "❌ No fields extracted from PDF\n";
        echo "\nPossible causes:\n";
        echo "  - PDF is password-protected (try unlocking first)\n";
        echo "  - PDF has no fillable form fields\n";
        echo "  - PDF is corrupted or incompatible\n";
        echo "  - All extraction methods failed\n";
        exit(1);
    }
    
    echo "✅ Successfully extracted " . count($fields) . " fields!\n";
    echo "\n";
    
    // Display fields
    echo "📋 Extracted fields:\n";
    $count = 0;
    foreach ($fields as $fieldName => $fieldData) {
        $count++;
        if ($count <= 10 || $count % 10 === 0) {
            echo sprintf(
                "  %-30s  Type: %-10s  Page: %d  Pos: (%.1f, %.1f)  Size: %.1f x %.1f\n",
                $fieldName,
                $fieldData['type'],
                $fieldData['page'],
                $fieldData['x'],
                $fieldData['y'],
                $fieldData['width'],
                $fieldData['height']
            );
        }
    }
    
    if (count($fields) > 10) {
        echo "  ... and " . (count($fields) - 10) . " more fields\n";
    }
    
    // Save position file
    echo "\n💾 Saving position file...\n";
    $dataDir = __DIR__ . '/data';
    if (!file_exists($dataDir)) {
        mkdir($dataDir, 0755, true);
    }
    
    $positionFile = $dataDir . '/' . $templateId . '_positions.json';
    $json = json_encode($fields, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    file_put_contents($positionFile, $json);
    
    echo "✅ Position file saved: $positionFile\n";
    
    // Generate backgrounds if needed
    echo "\n🖼️  Generating backgrounds...\n";
    try {
        $result = $extractor->extractAndGenerateBackgrounds(
            $pdfFile,
            $templateId,
            __DIR__ . '/uploads'
        );
        
        if (!empty($result['backgrounds'])) {
            echo "✅ Generated " . count($result['backgrounds']) . " background images\n";
            foreach ($result['backgrounds'] as $page => $bgFile) {
                echo "  Page $page: " . basename($bgFile) . "\n";
            }
        } else {
            echo "⚠️  No backgrounds generated\n";
        }
        
    } catch (\Exception $e) {
        echo "⚠️  Background generation failed: " . $e->getMessage() . "\n";
    }
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "✅ EXTRACTION COMPLETE!\n";
    echo "\nNext steps:\n";
    echo "1. Check position file: $positionFile\n";
    echo "2. Test in MVP: Navigate to Field Extractor\n";
    echo "3. Create document with template: $templateId\n";
    echo "4. Fill form and generate PDF\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\nTroubleshooting:\n";
    echo "1. Make sure Node.js is installed\n";
    echo "2. Check if PDF is password-protected\n";
    echo "3. Verify PDF contains fillable form fields\n";
    echo "4. Check logs for detailed error information\n";
    exit(1);
}
