<?php
/**
 * FL-100 Field Detection Test
 * Demonstrates automatic field extraction from the official FL-100 PDF
 */

require_once __DIR__ . '/mvp/lib/pdf_field_extractor.php';

use MVP\Lib\PdfFieldExtractor;

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║         FL-100 Auto Field Detection Demo                      ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

$pdfPath = __DIR__ . '/uploads/fl100_official_download.pdf';

if (!file_exists($pdfPath)) {
    echo "❌ Error: FL-100 not found at: $pdfPath\n";
    echo "Run: php download-fl100.php first\n";
    exit(1);
}

$fileSize = filesize($pdfPath);
echo "📄 PDF File: " . basename($pdfPath) . "\n";
echo "📦 File Size: " . number_format($fileSize) . " bytes (" . round($fileSize/1024, 1) . " KB)\n\n";

echo "⏳ Extracting fields...\n";
$startTime = microtime(true);

try {
    $extractor = new PdfFieldExtractor();
    $result = $extractor->extractFieldsAndGenerateBackground($pdfPath);
    
    $duration = round((microtime(true) - $startTime) * 1000);
    
    if ($result['success']) {
        echo "✅ Extraction complete! ({$duration}ms)\n\n";
        
        // Statistics
        echo "═══════════════════════════════════════════════════════════════\n";
        echo " STATISTICS\n";
        echo "═══════════════════════════════════════════════════════════════\n";
        
        $fields = $result['fields'];
        $totalFields = count($fields);
        $textFields = 0;
        $checkboxes = 0;
        $radioButtons = 0;
        $otherFields = 0;
        
        foreach ($fields as $field) {
            $type = strtolower($field['type'] ?? '');
            if (strpos($type, 'text') !== false) {
                $textFields++;
            } elseif (strpos($type, 'check') !== false) {
                $checkboxes++;
            } elseif (strpos($type, 'radio') !== false) {
                $radioButtons++;
            } else {
                $otherFields++;
            }
        }
        
        echo "Total Fields:    $totalFields\n";
        echo "├─ Text Fields:  $textFields\n";
        echo "├─ Checkboxes:   $checkboxes\n";
        echo "├─ Radio Buttons: $radioButtons\n";
        echo "└─ Other:        $otherFields\n\n";
        
        // Background generation
        if (!empty($result['background_images'])) {
            echo "═══════════════════════════════════════════════════════════════\n";
            echo " BACKGROUND IMAGES\n";
            echo "═══════════════════════════════════════════════════════════════\n";
            foreach ($result['background_images'] as $page => $path) {
                if (file_exists($path)) {
                    $imgSize = filesize($path);
                    echo "Page $page: " . basename($path) . " (" . round($imgSize/1024, 1) . " KB)\n";
                }
            }
            echo "\n";
        }
        
        // Sample fields (first 10)
        echo "═══════════════════════════════════════════════════════════════\n";
        echo " SAMPLE FIELDS (first 10)\n";
        echo "═══════════════════════════════════════════════════════════════\n";
        
        $sampleFields = array_slice($fields, 0, 10);
        foreach ($sampleFields as $i => $field) {
            $num = $i + 1;
            echo "\n[$num] {$field['name']}\n";
            echo "    Type: {$field['type']}\n";
            echo "    Position: X={$field['x']}mm, Y={$field['y']}mm\n";
            echo "    Size: {$field['width']}mm × {$field['height']}mm\n";
        }
        
        if ($totalFields > 10) {
            echo "\n... and " . ($totalFields - 10) . " more fields\n";
        }
        
        // JSON output path
        if (!empty($result['json_path'])) {
            echo "\n═══════════════════════════════════════════════════════════════\n";
            echo " OUTPUT FILES\n";
            echo "═══════════════════════════════════════════════════════════════\n";
            echo "📋 JSON: {$result['json_path']}\n";
            
            if (file_exists($result['json_path'])) {
                $jsonSize = filesize($result['json_path']);
                echo "    Size: " . number_format($jsonSize) . " bytes\n";
            }
        }
        
        echo "\n╔════════════════════════════════════════════════════════════════╗\n";
        echo "║  ✅ SUCCESS! FL-100 fields extracted and ready to use!        ║\n";
        echo "╚════════════════════════════════════════════════════════════════╝\n";
        
    } else {
        echo "❌ Extraction failed: {$result['message']}\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

