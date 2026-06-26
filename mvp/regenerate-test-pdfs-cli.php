<?php
/**
 * CLI Script to Regenerate Test PDFs - Regression Test
 * Usage: php mvp/regenerate-test-pdfs-cli.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/lib/fill_service.php';
require_once __DIR__ . '/lib/logger.php';
require_once __DIR__ . '/templates/registry.php';
require_once __DIR__ . '/lib/fl100_test_data_generator.php';
require_once __DIR__ . '/lib/fl105_test_data_generator.php';
require_once __DIR__ . '/lib/w9_test_data_generator.php';

use WebPdfTimeSaver\Mvp\FillService;
use WebPdfTimeSaver\Mvp\TemplateRegistry;
use WebPdfTimeSaver\Mvp\Logger;
use WebPdfTimeSaver\Mvp\FL100TestDataGenerator;
use WebPdfTimeSaver\Mvp\FL105TestDataGenerator;
use WebPdfTimeSaver\Mvp\W9TestDataGenerator;
use setasign\Fpdi\Fpdi;

echo "=== Regenerate Test PDFs - Regression Test ===\n\n";

$logger = new Logger();
$fillService = new FillService(__DIR__ . '/../output', $logger);

// Check which templates have position files available
$dataDir = __DIR__ . '/../data';
$availableTemplates = [];

// Check for FL-100
if (file_exists($dataDir . '/t_fl100_gc120_positions.json')) {
    $availableTemplates['t_fl100_gc120'] = [
        'name' => 'FL-100 (GC-120)',
        'testData' => FL100TestDataGenerator::generateCompleteTestData(),
        'pdfFile' => __DIR__ . '/../uploads/fl100.pdf'
    ];
}

// Check for FL-105
if (file_exists($dataDir . '/t_fl105_gc120_positions.json')) {
    $availableTemplates['t_fl105_gc120'] = [
        'name' => 'FL-105 (GC-120)',
        'testData' => FL105TestDataGenerator::generateCompleteTestData(),
        'pdfFile' => __DIR__ . '/../uploads/fl105.pdf'
    ];
}

// Check for W-9
$w9Files = glob($dataDir . '/t_w9*_positions.json');
if (!empty($w9Files)) {
    $w9File = basename($w9Files[0]);
    $w9TemplateId = str_replace('_positions.json', '', $w9File);
    $availableTemplates[$w9TemplateId] = [
        'name' => 'W-9 (IRS Form)',
        'testData' => W9TestDataGenerator::generateCompleteTestData(),
        'pdfFile' => __DIR__ . '/../uploads/w9.pdf'
    ];
}

if (empty($availableTemplates)) {
    echo "❌ No templates found with position files!\n";
    echo "Please ensure position files exist in: $dataDir\n";
    exit(1);
}

echo "Found " . count($availableTemplates) . " template(s) to test:\n";
foreach ($availableTemplates as $templateId => $config) {
    echo "  - {$config['name']} ($templateId)\n";
}
echo "\n";

$results = [];
$successCount = 0;
$errorCount = 0;

foreach ($availableTemplates as $templateId => $config) {
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📄 Testing: {$config['name']} ({$templateId})\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    try {
        // Check if PDF file exists
        if (!file_exists($config['pdfFile'])) {
            throw new \Exception("PDF file not found: " . basename($config['pdfFile']));
        }
        
        $pdfSize = filesize($config['pdfFile']);
        echo "✓ PDF File: " . basename($config['pdfFile']) . " (" . number_format($pdfSize / 1024, 2) . " KB)\n";
        
        // Load template dynamically
        echo "Loading template...\n";
        $template = TemplateRegistry::getTemplate($templateId);
        if (!$template || empty($template['fields'])) {
            throw new \Exception("Template not found or has no fields");
        }
        
        echo "✓ Template loaded: " . count($template['fields']) . " fields, " . count($template['panels']) . " panels\n";
        echo "✓ Test data: " . count($config['testData']) . " values\n";
        
        // Generate PDF using universal fillPdfForm method
        echo "Generating PDF...\n";
        $startTime = microtime(true);
        $result = $fillService->generateSimplePdf($template, $config['testData'], [
            'test' => true,
            'templateId' => $templateId,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        $duration = round((microtime(true) - $startTime) * 1000);
        
        // Verify result
        if (!isset($result['path']) || !file_exists($result['path'])) {
            throw new \Exception("Generated PDF file not found");
        }
        
        $fileSize = filesize($result['path']);
        if ($fileSize < 1024) {
            throw new \Exception("Generated PDF is too small ({$fileSize} bytes)");
        }
        
        // Check page count
        $pageCount = null;
        try {
            $probe = new Fpdi();
            $pageCount = $probe->setSourceFile($result['path']);
        } catch (\Throwable $e) {
            // Page count check failed, but continue
        }
        
        // Success!
        echo "✅ SUCCESS!\n";
        echo "   Method: " . ($result['method'] ?? 'unknown') . "\n";
        echo "   Filename: " . ($result['filename'] ?? basename($result['path'])) . "\n";
        echo "   File Size: " . number_format($fileSize / 1024, 2) . " KB\n";
        echo "   Pages: " . ($pageCount ?? 'N/A') . "\n";
        echo "   Duration: {$duration} ms\n";
        
        if (isset($result['fieldsFilled'])) {
            echo "   Fields Filled: " . $result['fieldsFilled'] . "\n";
        }
        if (isset($result['fields_placed'])) {
            echo "   Fields Placed: " . $result['fields_placed'] . "\n";
        }
        
        $results[$templateId] = [
            'success' => true,
            'filename' => $result['filename'] ?? basename($result['path']),
            'path' => $result['path'],
            'size' => $fileSize,
            'pages' => $pageCount,
            'method' => $result['method'] ?? 'unknown',
            'duration' => $duration
        ];
        $successCount++;
        
    } catch (\Throwable $e) {
        echo "❌ FAILED!\n";
        echo "   Error: " . $e->getMessage() . "\n";
        echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
        
        $results[$templateId] = [
            'success' => false,
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ];
        $errorCount++;
    }
    
    echo "\n";
}

// Summary
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📊 SUMMARY\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Total PDFs: " . count($availableTemplates) . "\n";
echo "✅ Successful: {$successCount}\n";
echo "❌ Failed: {$errorCount}\n";
echo "\n";

if ($successCount === count($availableTemplates)) {
    echo "✅ All PDFs generated successfully! No regression detected.\n";
    echo "\nGenerated PDFs:\n";
    foreach ($results as $templateId => $result) {
        if ($result['success']) {
            echo "  - {$templateId}: " . basename($result['path']) . " (" . number_format($result['size'] / 1024, 2) . " KB, " . ($result['pages'] ?? 'N/A') . " pages, " . $result['method'] . ")\n";
        }
    }
    exit(0);
} else {
    echo "⚠️  Some PDFs failed to generate. Please check the errors above.\n";
    echo "\nErrors:\n";
    foreach ($results as $templateId => $result) {
        if (!$result['success']) {
            echo "  - {$templateId}: " . $result['error'] . "\n";
        }
    }
    exit(1);
}










