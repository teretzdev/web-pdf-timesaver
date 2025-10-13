<?php
/**
 * Complete Test Suite - Extract Positions & Generate PDF with Visual Display
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/lib/pdf_field_extractor.php';
require_once __DIR__ . '/lib/pdf_form_filler.php';
require_once __DIR__ . '/lib/logger.php';
require_once __DIR__ . '/templates/registry.php';
require_once __DIR__ . '/lib/fl100_test_data_generator.php';

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
    <title>Hybrid Auto-Fill Test Suite</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 40px; border-radius: 12px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
        h1 { color: #333; margin-bottom: 10px; font-size: 32px; }
        .subtitle { color: #666; margin-bottom: 40px; font-size: 16px; }
        .test-section { margin: 30px 0; padding: 25px; background: #f8f9fa; border-radius: 8px; border-left: 5px solid #007bff; }
        .test-section h2 { color: #007bff; margin-bottom: 20px; font-size: 20px; display: flex; align-items: center; gap: 10px; }
        .test-section h2 .icon { font-size: 24px; }
        .success { border-left-color: #28a745; background: #d4edda; }
        .success h2 { color: #28a745; }
        .error { border-left-color: #dc3545; background: #f8d7da; }
        .error h2 { color: #dc3545; }
        .warning { border-left-color: #ffc107; background: #fff3cd; }
        .warning h2 { color: #ff6b00; }
        .info { border-left-color: #17a2b8; background: #d1ecf1; }
        .info h2 { color: #17a2b8; }
        
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-top: 15px; }
        .stat-card { background: white; padding: 15px; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .stat-card .label { font-size: 12px; color: #666; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-card .value { font-size: 28px; font-weight: bold; color: #333; margin-top: 5px; }
        
        .field-table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 13px; }
        .field-table th { background: #007bff; color: white; padding: 10px; text-align: left; font-weight: 600; }
        .field-table td { padding: 8px; border-bottom: 1px solid #ddd; }
        .field-table tr:hover { background: #f0f0f0; }
        .field-table code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-size: 12px; }
        
        .btn { display: inline-block; padding: 14px 28px; background: #28a745; color: white; text-decoration: none; border-radius: 6px; font-weight: 600; margin: 15px 10px 10px 0; transition: all 0.3s; border: none; cursor: pointer; font-size: 16px; }
        .btn:hover { background: #218838; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3); }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .btn-primary:hover { box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4); }
        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #5a6268; }
        
        .pdf-viewer { margin-top: 30px; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .pdf-viewer iframe { width: 100%; height: 800px; border: 1px solid #ddd; border-radius: 4px; }
        
        .loading { text-align: center; padding: 60px; }
        .spinner { border: 5px solid #f3f3f3; border-top: 5px solid #667eea; border-radius: 50%; width: 60px; height: 60px; animation: spin 1s linear infinite; margin: 0 auto 20px; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        
        .badge { display: inline-block; padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
        .badge-success { background: #d4edda; color: #155724; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-info { background: #d1ecf1; color: #0c5460; }
        
        pre { background: #f4f4f4; padding: 15px; border-radius: 6px; overflow-x: auto; font-size: 12px; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Hybrid Auto-Fill Test Suite</h1>
        <p class="subtitle">Complete extraction, positioning, and PDF generation with visual inspection</p>

        <?php if (!isset($_GET['run'])): ?>
            
            <div class="test-section info">
                <h2><span class="icon">📋</span> Test Suite Overview</h2>
                <p style="margin-bottom: 15px;">This comprehensive test will:</p>
                <ol style="margin-left: 20px; line-height: 2;">
                    <li><strong>Extract field positions</strong> from FL-100.pdf using PdfParser (gets REAL coordinates from PDF annotations)</li>
                    <li><strong>Convert PDF coordinates</strong> from bottom-left origin to top-left origin for FPDF</li>
                    <li><strong>Generate background images</strong> for each page using Ghostscript</li>
                    <li><strong>Create comprehensive test data</strong> for all form fields</li>
                    <li><strong>Generate a filled PDF</strong> using the extracted positions</li>
                    <li><strong>Display the PDF</strong> in an embedded viewer for visual inspection</li>
                </ol>
                
                <div class="grid" style="margin-top: 25px;">
                    <div class="stat-card">
                        <div class="label">Test Method</div>
                        <div class="value" style="font-size: 16px;">Hybrid Autofill</div>
                    </div>
                    <div class="stat-card">
                        <div class="label">Coordinate System</div>
                        <div class="value" style="font-size: 16px;">PDF → FPDF</div>
                    </div>
                    <div class="stat-card">
                        <div class="label">Template</div>
                        <div class="value" style="font-size: 16px;">FL-100</div>
                    </div>
                </div>
            </div>

            <button onclick="window.location.href='?run=1'" class="btn btn-primary">▶️ Run Complete Test Suite</button>
            <a href="index.php" class="btn btn-secondary">← Back to Dashboard</a>

        <?php else: ?>
            
            <div class="loading" id="loading">
                <div class="spinner"></div>
                <p style="font-size: 18px; color: #667eea;">Running comprehensive tests...</p>
            </div>

            <?php
            $pdfFile = __DIR__ . '/../uploads/fl100.pdf';
            $templateId = 't_fl100_gc120';
            $testStartTime = microtime(true);

            try {
                if (!file_exists($pdfFile)) {
                    throw new \Exception('FL-100 PDF not found at: ' . $pdfFile);
                }

                // TEST 1: Extract fields and generate backgrounds
                echo '<div class="test-section">';
                echo '<h2><span class="icon">🔍</span> Test 1: Field Extraction & Background Generation</h2>';
                
                $extractor = new PdfFieldExtractor();
                $extractStart = microtime(true);
                $result = $extractor->extractAndGenerateBackgrounds($pdfFile, $templateId, __DIR__ . '/../uploads');
                $extractTime = round((microtime(true) - $extractStart) * 1000);
                
                $fields = $result['fields'];
                $backgrounds = $result['backgrounds'];
                $positionFile = $result['positionFile'];

                echo '<div class="grid">';
                echo '<div class="stat-card"><div class="label">Fields Extracted</div><div class="value">' . count($fields) . '</div></div>';
                echo '<div class="stat-card"><div class="label">Pages Generated</div><div class="value">' . count($backgrounds) . '</div></div>';
                echo '<div class="stat-card"><div class="label">Extraction Time</div><div class="value">' . $extractTime . 'ms</div></div>';
                echo '</div>';

                if (!empty($fields)) {
                    echo '<h3 style="margin-top: 20px; color: #333;">Extracted Fields (First 10):</h3>';
                    echo '<table class="field-table">';
                    echo '<thead><tr><th>Field Name</th><th>Type</th><th>Page</th><th>Position (X, Y)</th><th>Size (W x H)</th></tr></thead>';
                    echo '<tbody>';
                    $count = 0;
                    foreach ($fields as $name => $data) {
                        if ($count++ >= 10) break;
                        echo '<tr>';
                        echo '<td><code>' . htmlspecialchars($name) . '</code></td>';
                        echo '<td><span class="badge badge-info">' . $data['type'] . '</span></td>';
                        echo '<td>' . $data['page'] . '</td>';
                        echo '<td>(' . round($data['x'], 1) . ', ' . round($data['y'], 1) . ')</td>';
                        echo '<td>' . round($data['width'], 1) . ' x ' . round($data['height'], 1) . '</td>';
                        echo '</tr>';
                    }
                    if (count($fields) > 10) {
                        echo '<tr><td colspan="5" style="text-align:center; color: #666;"><em>... and ' . (count($fields) - 10) . ' more fields</em></td></tr>';
                    }
                    echo '</tbody></table>';
                }

                if ($positionFile) {
                    echo '<p style="margin-top: 15px;"><strong>📄 Position file saved:</strong> <code>' . basename($positionFile) . '</code></p>';
                }
                
                echo '</div>';

                // TEST 2: Load template and generate test data
                echo '<div class="test-section">';
                echo '<h2><span class="icon">📝</span> Test 2: Template Loading & Test Data Generation</h2>';
                
                $templates = TemplateRegistry::load();
                $template = $templates[$templateId] ?? null;

                if (!$template) {
                    throw new \Exception('Template not found: ' . $templateId);
                }

                echo '<p><strong>✅ Template loaded:</strong> <code>' . htmlspecialchars($template['code'] ?? '') . ' — ' . htmlspecialchars($template['name'] ?? '') . '</code></p>';
                
                $testData = \WebPdfTimeSaver\Mvp\FL100TestDataGenerator::generateCompleteTestData();
                
                echo '<div class="grid" style="margin-top: 15px;">';
                echo '<div class="stat-card"><div class="label">Test Fields Generated</div><div class="value">' . count($testData) . '</div></div>';
                echo '<div class="stat-card"><div class="label">Template Fields</div><div class="value">' . count($template['fields'] ?? []) . '</div></div>';
                echo '</div>';
                
                echo '<h3 style="margin-top: 20px; color: #333;">Sample Test Data:</h3>';
                echo '<pre>' . json_encode(array_slice($testData, 0, 10), JSON_PRETTY_PRINT) . '</pre>';
                
                echo '</div>';

                // TEST 3: Generate filled PDF
                echo '<div class="test-section">';
                echo '<h2><span class="icon">🎨</span> Test 3: PDF Generation with Positioned Fields</h2>';
                
                $logger = new Logger();
                $filler = new PdfFormFiller(__DIR__ . '/../output', __DIR__ . '/../uploads', $logger);
                $filler->setContext([
                    'test' => true, 
                    'method' => 'hybrid-autofill-test-suite',
                    'timestamp' => date('Y-m-d H:i:s'),
                    'coordinate_conversion' => 'PDF→FPDF'
                ]);

                $fillStart = microtime(true);
                $generatedPdf = $filler->fillPdfFormWithPositions($template, $testData, $templateId);
                $fillTime = round((microtime(true) - $fillStart) * 1000);
                
                echo '<div class="grid">';
                echo '<div class="stat-card"><div class="label">Generation Time</div><div class="value">' . $fillTime . 'ms</div></div>';
                echo '<div class="stat-card"><div class="label">Fields Placed</div><div class="value">' . ($generatedPdf['fields_placed'] ?? 'N/A') . '</div></div>';
                echo '<div class="stat-card"><div class="label">Pages Created</div><div class="value">' . ($generatedPdf['pages'] ?? 'N/A') . '</div></div>';
                echo '</div>';
                
                echo '<p style="margin-top: 15px;"><strong>📄 Generated PDF:</strong> <code>' . htmlspecialchars($generatedPdf['filename']) . '</code></p>';
                
                echo '</div>';

                // TEST SUMMARY
                $totalTime = round((microtime(true) - $testStartTime) * 1000);
                echo '<div class="test-section success">';
                echo '<h2><span class="icon">✅</span> Test Suite Complete!</h2>';
                echo '<div class="grid">';
                echo '<div class="stat-card"><div class="label">Total Time</div><div class="value">' . $totalTime . 'ms</div></div>';
                echo '<div class="stat-card"><div class="label">Status</div><div class="value" style="font-size: 20px; color: #28a745;">SUCCESS</div></div>';
                echo '</div>';
                
                $relativePath = '../output/' . $generatedPdf['filename'];
                echo '<div style="margin-top: 20px;">';
                echo '<a href="' . htmlspecialchars($relativePath) . '" target="_blank" class="btn">📥 Download PDF</a>';
                echo '<a href="' . htmlspecialchars($relativePath) . '" target="_blank" class="btn">🔍 Open in New Tab</a>';
                echo '<button onclick="toggleViewer()" class="btn btn-primary">👁️ Toggle Embedded Viewer</button>';
                echo '<a href="?" class="btn btn-secondary">🔄 Run Again</a>';
                echo '</div>';
                
                // Embedded PDF Viewer
                echo '<div class="pdf-viewer" id="pdfViewer" style="display: block;">';
                echo '<h3 style="margin-bottom: 15px; color: #333;">📄 Generated PDF Preview:</h3>';
                echo '<iframe src="' . htmlspecialchars($relativePath) . '#zoom=100"></iframe>';
                echo '</div>';
                
                echo '</div>';

            } catch (\Exception $e) {
                echo '<div class="test-section error">';
                echo '<h2><span class="icon">❌</span> Test Failed</h2>';
                echo '<p><strong>Error:</strong></p>';
                echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
                echo '<p style="margin-top: 15px;"><strong>Stack Trace:</strong></p>';
                echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
                echo '<a href="?" class="btn">← Try Again</a>';
                echo '</div>';
            }
            ?>

            <script>
                // Auto-hide loading spinner
                document.addEventListener('DOMContentLoaded', function() {
                    const loading = document.getElementById('loading');
                    if (loading) {
                        loading.style.display = 'none';
                    }
                });
                
                // Toggle PDF viewer
                function toggleViewer() {
                    const viewer = document.getElementById('pdfViewer');
                    if (viewer) {
                        viewer.style.display = viewer.style.display === 'none' ? 'block' : 'none';
                    }
                }
            </script>

        <?php endif; ?>
    </div>
</body>
</html>

