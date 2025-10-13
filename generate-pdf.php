<?php
/**
 * Standalone PDF Generator - Root Level Access
 */

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/mvp/lib/pdf_field_extractor.php';
require_once __DIR__ . '/mvp/lib/pdf_form_filler.php';
require_once __DIR__ . '/mvp/lib/logger.php';
require_once __DIR__ . '/mvp/templates/registry.php';
require_once __DIR__ . '/mvp/lib/fl100_test_data_generator.php';

use WebPdfTimeSaver\Mvp\PdfFieldExtractor;
use WebPdfTimeSaver\Mvp\PdfFormFiller;
use WebPdfTimeSaver\Mvp\Logger;
use WebPdfTimeSaver\Mvp\TemplateRegistry;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate Test PDF - Hybrid Auto-Fill</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; margin-bottom: 10px; }
        .subtitle { color: #666; margin-bottom: 30px; }
        .section { margin: 30px 0; padding: 20px; background: #f9f9f9; border-radius: 6px; border-left: 4px solid #007bff; }
        .section h2 { color: #007bff; margin-bottom: 15px; font-size: 18px; }
        .success { border-left-color: #28a745; }
        .success h2 { color: #28a745; }
        .error { border-left-color: #dc3545; background: #fff5f5; }
        .error h2 { color: #dc3545; }
        .info { border-left-color: #17a2b8; }
        .info h2 { color: #17a2b8; }
        .field-list { margin: 10px 0; }
        .field-item { padding: 8px; background: white; margin: 5px 0; border-radius: 4px; font-size: 13px; font-family: monospace; }
        .btn { display: inline-block; padding: 12px 24px; background: #28a745; color: white; text-decoration: none; border-radius: 6px; font-weight: 600; margin: 10px 10px 10px 0; transition: background 0.2s; }
        .btn:hover { background: #218838; }
        .btn-primary { background: #007bff; }
        .btn-primary:hover { background: #0056b3; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-size: 13px; }
        .loading { text-align: center; padding: 40px; }
        .spinner { border: 4px solid #f3f3f3; border-top: 4px solid #007bff; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto 20px; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Hybrid Auto-Fill PDF Generator</h1>
        <p class="subtitle">Generate a test PDF using automatic field extraction and positioning</p>

        <?php if (!isset($_GET['generate'])): ?>
            
            <div class="section info">
                <h2>📋 What This Does</h2>
                <p>This tool will:</p>
                <ol style="margin: 15px 0 0 20px; line-height: 1.8;">
                    <li>Extract field positions from FL-100.pdf</li>
                    <li>Generate background images for each page</li>
                    <li>Create test data for all fields</li>
                    <li>Generate a filled PDF using extracted positions</li>
                    <li>Show download link</li>
                </ol>
            </div>

            <a href="?generate=1" class="btn btn-primary">▶️ Generate PDF Now</a>
            <a href="mvp/" class="btn">← Back to Dashboard</a>

        <?php else: ?>
            
            <div class="loading">
                <div class="spinner"></div>
                <p>Generating your PDF...</p>
            </div>

            <?php
            $pdfFile = __DIR__ . '/uploads/fl100.pdf';
            $templateId = 't_fl100_gc120';

            try {
                if (!file_exists($pdfFile)) {
                    throw new \Exception('FL-100 PDF not found at: ' . $pdfFile);
                }

                // Step 1: Extract fields and generate backgrounds
                echo '<div class="section">';
                echo '<h2>📋 Step 1: Extracting Fields & Generating Backgrounds</h2>';
                
                $extractor = new PdfFieldExtractor();
                $result = $extractor->extractAndGenerateBackgrounds($pdfFile, $templateId, __DIR__ . '/uploads');
                
                $fields = $result['fields'];
                $backgrounds = $result['backgrounds'];
                $positionFile = $result['positionFile'];

                if (!empty($fields)) {
                    echo '<p>✅ <strong>Extracted ' . count($fields) . ' fields</strong></p>';
                    echo '<div class="field-list">';
                    $count = 0;
                    foreach ($fields as $name => $data) {
                        if ($count++ < 5) {
                            echo '<div class="field-item">' . htmlspecialchars($name) . ' [' . $data['type'] . '] Page ' . $data['page'] . '</div>';
                        }
                    }
                    if (count($fields) > 5) {
                        echo '<div class="field-item">... and ' . (count($fields) - 5) . ' more fields</div>';
                    }
                    echo '</div>';
                } else {
                    echo '<p>⚠️ No fields extracted (PDF may be encrypted)</p>';
                }

                if (!empty($backgrounds)) {
                    echo '<p style="margin-top: 15px;">✅ <strong>Generated ' . count($backgrounds) . ' background images</strong></p>';
                }

                if ($positionFile) {
                    echo '<p style="margin-top: 15px;">✅ <strong>Position file created:</strong> <code>' . basename($positionFile) . '</code></p>';
                }
                
                echo '</div>';

                // Step 2: Load template and generate test data
                echo '<div class="section">';
                echo '<h2>📝 Step 2: Preparing Test Data</h2>';
                
                $templates = TemplateRegistry::load();
                $template = $templates[$templateId] ?? null;

                if (!$template) {
                    throw new \Exception('Template not found: ' . $templateId);
                }

                echo '<p>✅ Template: <strong>' . htmlspecialchars($template['code'] ?? '') . ' — ' . htmlspecialchars($template['name'] ?? '') . '</strong></p>';
                
                $testData = \WebPdfTimeSaver\Mvp\FL100TestDataGenerator::generateCompleteTestData();
                echo '<p>✅ Generated test data with <strong>' . count($testData) . ' fields</strong></p>';
                
                echo '</div>';

                // Step 3: Generate PDF
                echo '<div class="section">';
                echo '<h2>🎨 Step 3: Generating Filled PDF</h2>';
                
                $logger = new Logger();
                $filler = new PdfFormFiller(__DIR__ . '/output', __DIR__ . '/uploads', $logger);
                $filler->setContext(['test' => true, 'method' => 'hybrid-autofill', 'timestamp' => date('Y-m-d H:i:s')]);

                $generatedPdf = $filler->fillPdfFormWithPositions($template, $testData, $templateId);
                
                echo '<p>✅ <strong>PDF generated successfully!</strong></p>';
                echo '<p style="margin-top: 10px;">📄 <strong>Filename:</strong> <code>' . htmlspecialchars($generatedPdf['filename']) . '</code></p>';
                echo '<p><strong>Fields placed:</strong> ' . ($generatedPdf['fields_placed'] ?? 'N/A') . '</p>';
                echo '<p><strong>Pages:</strong> ' . ($generatedPdf['pages'] ?? 'N/A') . '</p>';
                
                echo '</div>';

                // Success!
                $relativePath = 'output/' . $generatedPdf['filename'];
                echo '<div class="section success">';
                echo '<h2>✅ Success!</h2>';
                echo '<p style="margin-bottom: 20px;">Your PDF has been generated using the hybrid auto-fill method.</p>';
                echo '<a href="' . htmlspecialchars($relativePath) . '" target="_blank" class="btn">📥 Download PDF</a>';
                echo '<a href="' . htmlspecialchars($relativePath) . '" target="_blank" class="btn">👁️ View PDF</a>';
                echo '<a href="?" class="btn btn-primary">🔄 Generate Another</a>';
                echo '<script>window.open("' . htmlspecialchars($relativePath) . '", "_blank");</script>';
                echo '</div>';

            } catch (\Exception $e) {
                echo '<div class="section error">';
                echo '<h2>❌ Error</h2>';
                echo '<p><strong>Failed to generate PDF:</strong></p>';
                echo '<p style="margin-top: 10px;"><code>' . htmlspecialchars($e->getMessage()) . '</code></p>';
                echo '<a href="?" class="btn">← Try Again</a>';
                echo '</div>';
            }
            ?>

            <script>
                // Auto-remove loading spinner after content loads
                document.addEventListener('DOMContentLoaded', function() {
                    const loading = document.querySelector('.loading');
                    if (loading) {
                        loading.style.display = 'none';
                    }
                });
            </script>

        <?php endif; ?>
    </div>
</body>
</html>

