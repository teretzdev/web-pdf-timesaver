<?php
/**
 * Test script to simulate browser upload and verify ensemble extraction works
 */

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/mvp/lib/auto_position_extractor.php';
require_once __DIR__ . '/mvp/lib/pdf_field_extractor.php';

use WebPdfTimeSaver\Mvp\AutoPositionExtractor;
use WebPdfTimeSaver\Mvp\PdfFieldExtractor;

echo "🧪 Testing Browser Upload Flow\n";
echo "==============================\n\n";

// Test PDFs
$testPdfs = [
    ['name' => 'W-9', 'path' => __DIR__ . '/uploads/w9.pdf', 'templateId' => 't_w9_browser_test'],
    ['name' => 'FL-100', 'path' => __DIR__ . '/uploads/FL-100_decrypted.pdf', 'templateId' => 't_fl100_browser_test'],
    ['name' => 'FL-105', 'path' => __DIR__ . '/uploads/fl105.pdf', 'templateId' => 't_fl105_browser_test'],
];

foreach ($testPdfs as $test) {
    echo "\n📄 Testing {$test['name']}...\n";
    echo "   Path: {$test['path']}\n";
    
    if (!file_exists($test['path'])) {
        echo "   ⚠️  PDF not found, skipping\n";
        continue;
    }
    
    // Test 1: Direct AutoPositionExtractor (like api/extract-fields.php)
    echo "\n   Test 1: Direct AutoPositionExtractor (api/extract-fields.php flow)\n";
    try {
        $autoExtractor = new AutoPositionExtractor();
        if (!$autoExtractor->isAvailable()) {
            echo "   ❌ Node.js not available\n";
            continue;
        }
        
        $result = $autoExtractor->extractPositions($test['path'], $test['templateId']);
        
        if (!empty($result['fields'])) {
            echo "   ✅ SUCCESS: Extracted " . count($result['fields']) . " fields\n";
            echo "   📊 Method: " . ($result['method'] ?? 'unknown') . "\n";
            if (isset($result['methodsUsed']) && is_array($result['methodsUsed'])) {
                echo "   📊 Methods used: " . implode(', ', $result['methodsUsed']) . "\n";
            }
        } else {
            echo "   ❌ FAILED: No fields extracted\n";
            if (!empty($result['errors'])) {
                echo "   Errors: " . implode(', ', $result['errors']) . "\n";
            }
        }
    } catch (\Exception $e) {
        echo "   ❌ ERROR: " . $e->getMessage() . "\n";
    }
    
    // Test 2: PdfFieldExtractor (like handleUniversalProcess)
    echo "\n   Test 2: PdfFieldExtractor (handleUniversalProcess flow)\n";
    try {
        $extractor = new PdfFieldExtractor();
        $fields = $extractor->extractFieldPositions($test['path'], $test['templateId']);
        
        if (!empty($fields)) {
            echo "   ✅ SUCCESS: Extracted " . count($fields) . " fields\n";
        } else {
            echo "   ❌ FAILED: No fields extracted\n";
        }
    } catch (\Exception $e) {
        echo "   ❌ ERROR: " . $e->getMessage() . "\n";
    }
}

echo "\n\n✅ Browser upload flow test complete!\n";

