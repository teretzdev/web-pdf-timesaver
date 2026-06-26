<?php
/**
 * Regenerate Test PDFs - Regression Test
 * Generates FL-100, FL-105, and W-9 PDFs to verify no regression
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

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Regenerate Test PDFs - Regression Test</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; margin-bottom: 10px; }
        .subtitle { color: #666; margin-bottom: 30px; }
        .pdf-result { margin: 30px 0; padding: 20px; background: #f9f9f9; border-radius: 6px; border-left: 4px solid #007bff; }
        .pdf-result.success { border-left-color: #28a745; }
        .pdf-result.error { border-left-color: #dc3545; background: #fff5f5; }
        .pdf-result h2 { color: #007bff; margin-bottom: 15px; font-size: 18px; }
        .pdf-result.success h2 { color: #28a745; }
        .pdf-result.error h2 { color: #dc3545; }
        .info-row { margin: 8px 0; padding: 8px; background: white; border-radius: 4px; }
        .info-label { font-weight: 600; display: inline-block; width: 150px; }
        .btn { display: inline-block; padding: 10px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 6px; font-weight: 600; margin: 5px 5px 5px 0; }
        .btn:hover { background: #218838; }
        .btn-primary { background: #007bff; }
        .btn-primary:hover { background: #0056b3; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-size: 13px; }
        .summary { margin: 30px 0; padding: 20px; background: #e7f3ff; border-radius: 6px; border-left: 4px solid #007bff; }
        .summary h2 { color: #007bff; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 Regenerate Test PDFs - Regression Test</h1>
        <p class="subtitle">Testing FL-100, FL-105, and W-9 PDF generation with universal fill strategy</p>

        <?php
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
        
        // Check for W-9 (try multiple template ID variants)
        $w9TemplateId = null;
        $w9PositionsFile = null;
        $w9Candidates = ['t_w9', 't_w9_test', 't_w9_verification'];
        foreach ($w9Candidates as $candidate) {
            if (file_exists($dataDir . '/' . $candidate . '_positions.json')) {
                $w9TemplateId = $candidate;
                $w9PositionsFile = $dataDir . '/' . $candidate . '_positions.json';
                break;
            }
        }
        
        if ($w9TemplateId) {
            $availableTemplates[$w9TemplateId] = [
                'name' => 'W-9 (IRS Form)',
                'testData' => W9TestDataGenerator::generateCompleteTestData(),
                'pdfFile' => __DIR__ . '/../uploads/w9.pdf'
            ];
        }
        
        // Use available templates, or fall back to defaults if none found
        if (empty($availableTemplates)) {
            // Fallback to known templates even if positions don't exist
            $availableTemplates = [
                't_fl100_gc120' => [
                    'name' => 'FL-100 (GC-120)',
                    'testData' => FL100TestDataGenerator::generateCompleteTestData(),
                    'pdfFile' => __DIR__ . '/../uploads/fl100.pdf'
                ],
                't_fl105_gc120' => [
                    'name' => 'FL-105 (GC-120)',
                    'testData' => FL105TestDataGenerator::generateCompleteTestData(),
                    'pdfFile' => __DIR__ . '/../uploads/fl105.pdf'
                ]
            ];
            
            // Try to find W-9 template ID
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
        }
        
        $pdfs = $availableTemplates;
        
        $results = [];
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($pdfs as $templateId => $config) {
            echo "<div class='pdf-result'>";
            echo "<h2>📄 {$config['name']} ({$templateId})</h2>";
            
            try {
                // Check if PDF file exists
                if (!file_exists($config['pdfFile'])) {
                    throw new \Exception("PDF file not found: " . basename($config['pdfFile']));
                }
                
                echo "<div class='info-row'><span class='info-label'>PDF File:</span> " . basename($config['pdfFile']) . " (" . number_format(filesize($config['pdfFile']) / 1024, 2) . " KB)</div>";
                
                // Load template dynamically
                $template = TemplateRegistry::getTemplate($templateId);
                if (!$template || empty($template['fields'])) {
                    throw new \Exception("Template not found or has no fields for: {$templateId}");
                }
                
                echo "<div class='info-row'><span class='info-label'>Template Fields:</span> " . count($template['fields']) . " fields</div>";
                echo "<div class='info-row'><span class='info-label'>Template Panels:</span> " . count($template['panels']) . " panels</div>";
                echo "<div class='info-row'><span class='info-label'>Test Data:</span> " . count($config['testData']) . " values</div>";
                
                // Generate PDF using universal fillPdfForm method
                echo "<div class='info-row'><span class='info-label'>Status:</span> <strong>Generating...</strong></div>";
                
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
                    throw new \Exception("Generated PDF is too small ({$fileSize} bytes) - likely empty or corrupted");
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
                echo "<div class='info-row'><span class='info-label'>✅ Result:</span> <strong>SUCCESS</strong></div>";
                echo "<div class='info-row'><span class='info-label'>Method:</span> " . htmlspecialchars($result['method'] ?? 'unknown') . "</div>";
                echo "<div class='info-row'><span class='info-label'>Filename:</span> <code>" . htmlspecialchars($result['filename'] ?? basename($result['path'])) . "</code></div>";
                echo "<div class='info-row'><span class='info-label'>File Size:</span> " . number_format($fileSize / 1024, 2) . " KB</div>";
                echo "<div class='info-row'><span class='info-label'>Pages:</span> " . ($pageCount ?? 'N/A') . "</div>";
                echo "<div class='info-row'><span class='info-label'>Duration:</span> {$duration} ms</div>";
                
                if (isset($result['fieldsFilled'])) {
                    echo "<div class='info-row'><span class='info-label'>Fields Filled:</span> " . $result['fieldsFilled'] . "</div>";
                }
                if (isset($result['fields_placed'])) {
                    echo "<div class='info-row'><span class='info-label'>Fields Placed:</span> " . $result['fields_placed'] . "</div>";
                }
                
                // Download link
                $relativePath = 'output/' . basename($result['path']);
                echo "<div style='margin-top: 15px;'>";
                echo "<a href='../{$relativePath}' target='_blank' class='btn'>📥 Download PDF</a> ";
                echo "<a href='../{$relativePath}' target='_blank' class='btn btn-primary'>👁️ View PDF</a>";
                echo "</div>";
                
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
                
                // Remove success class temporarily to show as processing
                echo "<script>setTimeout(function(){ document.querySelector('.pdf-result:last-child').classList.add('success'); }, 100);</script>";
                
            } catch (\Throwable $e) {
                echo "<div class='info-row'><span class='info-label'>❌ Error:</span> <strong>" . htmlspecialchars($e->getMessage()) . "</strong></div>";
                
                if (isset($e->getTrace()[0])) {
                    echo "<div class='info-row'><span class='info-label'>File:</span> " . htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "</div>";
                }
                
                $results[$templateId] = [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
                $errorCount++;
                
                echo "<script>setTimeout(function(){ document.querySelector('.pdf-result:last-child').classList.add('error'); }, 100);</script>";
            }
            
            echo "</div>";
        }
        
        // Summary
        echo "<div class='summary'>";
        echo "<h2>📊 Summary</h2>";
        echo "<div class='info-row'><span class='info-label'>Total PDFs:</span> " . count($pdfs) . "</div>";
        echo "<div class='info-row'><span class='info-label'>✅ Successful:</span> <strong style='color: #28a745;'>{$successCount}</strong></div>";
        echo "<div class='info-row'><span class='info-label'>❌ Failed:</span> <strong style='color: #dc3545;'>{$errorCount}</strong></div>";
        
        if ($successCount === count($pdfs)) {
            echo "<div style='margin-top: 15px; padding: 15px; background: #d4edda; border-radius: 4px; color: #155724;'>";
            echo "✅ <strong>All PDFs generated successfully! No regression detected.</strong>";
            echo "</div>";
        } else {
            echo "<div style='margin-top: 15px; padding: 15px; background: #f8d7da; border-radius: 4px; color: #721c24;'>";
            echo "⚠️ <strong>Some PDFs failed to generate. Please check the errors above.</strong>";
            echo "</div>";
        }
        echo "</div>";
        
        // Detailed results JSON (for debugging)
        echo "<div class='pdf-result' style='margin-top: 30px;'>";
        echo "<h2>🔍 Detailed Results (JSON)</h2>";
        echo "<pre style='background: white; padding: 15px; border-radius: 4px; overflow-x: auto;'>";
        echo htmlspecialchars(json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        echo "</pre>";
        echo "</div>";
        ?>
        
        <div style="margin-top: 30px;">
            <a href="?" class="btn">🔄 Regenerate All</a>
            <a href="index.php" class="btn btn-primary">← Back to Dashboard</a>
        </div>
    </div>
</body>
</html>

