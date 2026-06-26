<?php
/**
 * Web-based Position Verification Interface
 * Provides a user-friendly interface to verify text layer positions
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/lib/position_verifier.php';
require_once __DIR__ . '/lib/fl100_test_data_generator.php';
require_once __DIR__ . '/lib/w9_test_data_generator.php';
require_once __DIR__ . '/lib/pdf_form_filler.php';

use WebPdfTimeSaver\Mvp\PositionVerifier;
use WebPdfTimeSaver\Mvp\PdfFormFiller;
use WebPdfTimeSaver\Mvp\FL100TestDataGenerator;

$templateId = $_GET['template'] ?? 't_fl100_gc120';
$action = $_POST['action'] ?? $_GET['action'] ?? 'form';

// Handle verification request
if ($action === 'verify' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    try {
        $templateId = $_POST['template_id'] ?? 't_fl100_gc120';
        $pdfPath = $_POST['pdf_path'] ?? null;
        
        // Load expected positions
        $positionsFile = __DIR__ . '/../data/' . $templateId . '_positions.json';
        if (!file_exists($positionsFile)) {
            throw new \RuntimeException("Position file not found: $positionsFile");
        }
        
        $expectedPositions = json_decode(file_get_contents($positionsFile), true);
        if (!$expectedPositions) {
            throw new \RuntimeException("Could not parse position file");
        }
        
        // Generate test data (pick dataset based on template)
        if (str_contains($templateId, 'w9')) {
            $testData = \WebPdfTimeSaver\Mvp\W9TestDataGenerator::generateCompleteTestData();
        } else {
            $testData = FL100TestDataGenerator::generateCompleteTestData();
        }
        
        // Generate PDF if not provided
        if (!$pdfPath) {
            $filler = new PdfFormFiller();
            $template = [
                'id' => $templateId,
                'fields' => []
            ];
            
            foreach ($expectedPositions as $fieldName => $position) {
                $template['fields'][] = [
                    'key' => $fieldName,
                    'type' => $position['type'] ?? 'text',
                    'label' => $fieldName
                ];
            }
            
            $result = $filler->fillPdfForm($template, $testData);
            $pdfPath = $result['path'] ?? null;
            
            if (!$pdfPath || !file_exists($pdfPath)) {
                throw new \RuntimeException("Failed to generate PDF");
            }
        } else {
            if (!file_exists($pdfPath)) {
                throw new \RuntimeException("PDF file not found: $pdfPath");
            }
        }
        
        // Run verification
        $verifier = new PositionVerifier();
        $report = $verifier->verifyPdfPositions($pdfPath, $expectedPositions, $testData);
        
        // Generate visual overlay
        // Write overlays inside the web root (mvp/uploads/verification) so the built-in server can serve them
        $overlayPath = __DIR__ . '/uploads/verification/overlay_' . basename($pdfPath, '.pdf') . '_' . time() . '.html';
        $overlayDir = dirname($overlayPath);
        if (!is_dir($overlayDir)) {
            mkdir($overlayDir, 0755, true);
        }
        $verifier->generateVisualOverlay($pdfPath, $expectedPositions, $testData, $overlayPath);
        
        // Save report
        $reportPath = $overlayDir . '/report_' . date('Y-m-d_His') . '.json';
        file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT));
        
        // Make URLs reachable from the /mvp dev server (document root = mvp/)
        $overlayRel = str_replace(__DIR__ . '/', '', $overlayPath);
        $reportRel = str_replace(__DIR__ . '/', '', $reportPath);
        // Serve from /mvp docroot; use absolute-from-docroot URLs to avoid relative nesting issues
        $overlayUrl = '/uploads/verification/' . basename($overlayRel);
        $reportUrl = '/uploads/verification/' . basename($reportRel);
        
        echo json_encode([
            'success' => true,
            'report' => $report,
            'overlay_url' => $overlayUrl,
            'report_url' => $reportUrl
        ]);
        
    } catch (\Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

// Get available templates
$templatesDir = __DIR__ . '/../data';
$templates = [];
if (is_dir($templatesDir)) {
    foreach (glob($templatesDir . '/*_positions.json') as $file) {
        $templateName = basename($file, '_positions.json');
        $templates[] = $templateName;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Position Verification Tool</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 32px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        select, input[type="text"] {
            width: 100%;
            padding: 10px;
            border: 2px solid #e1e4e8;
            border-radius: 6px;
            font-size: 14px;
        }
        .btn {
            padding: 12px 24px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn:hover {
            background: #5568d3;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        .btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .results {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            display: none;
        }
        .results.show {
            display: block;
        }
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        .status-pass {
            background: #d4edda;
            color: #155724;
        }
        .status-warning {
            background: #fff3cd;
            color: #856404;
        }
        .status-fail {
            background: #f8d7da;
            color: #721c24;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .stat-card {
            background: white;
            padding: 15px;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stat-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .stat-value {
            font-size: 28px;
            font-weight: bold;
            color: #333;
            margin-top: 5px;
        }
        .issues-list {
            margin-top: 20px;
        }
        .issue-item {
            padding: 10px;
            margin-bottom: 10px;
            background: white;
            border-left: 4px solid #dc3545;
            border-radius: 4px;
        }
        .issue-item.warning {
            border-left-color: #ffc107;
        }
        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }
        .loading.show {
            display: block;
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .links {
            margin-top: 20px;
        }
        .links a {
            display: inline-block;
            margin-right: 10px;
            padding: 8px 16px;
            background: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
        }
        .links a:hover {
            background: #218838;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Position Verification Tool</h1>
        <p class="subtitle">Verify 100% accuracy of text layer positions in generated PDFs</p>
        
        <form id="verify-form">
            <div class="form-group">
                <label for="template">Template ID:</label>
                <select id="template" name="template_id" required>
                    <?php foreach ($templates as $tmpl): ?>
                        <option value="<?= htmlspecialchars($tmpl) ?>" <?= $tmpl === $templateId ? 'selected' : '' ?>>
                            <?= htmlspecialchars($tmpl) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="pdf_path">PDF Path (optional - leave empty to generate test PDF):</label>
                <input type="text" id="pdf_path" name="pdf_path" placeholder="e.g., uploads/mvp_20250101_120000_t_fl100_gc120.pdf">
            </div>
            
            <button type="submit" class="btn" id="verify-btn">Run Verification</button>
        </form>
        
        <div class="loading" id="loading">
            <div class="spinner"></div>
            <p style="margin-top: 10px;">Running verification...</p>
        </div>
        
        <div class="results" id="results">
            <h2>Verification Results</h2>
            <div id="status-badge"></div>
            <div class="stats" id="stats"></div>
            <div class="issues-list" id="issues"></div>
            <div class="links" id="links"></div>
        </div>
    </div>
    
    <script>
        document.getElementById('verify-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const form = e.target;
            const btn = document.getElementById('verify-btn');
            const loading = document.getElementById('loading');
            const results = document.getElementById('results');
            
            btn.disabled = true;
            loading.classList.add('show');
            results.classList.remove('show');
            
            const formData = new FormData(form);
            formData.append('action', 'verify');
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    displayResults(data.report, data.overlay_url, data.report_url);
                } else {
                    alert('Error: ' + (data.error || 'Unknown error'));
                }
            } catch (error) {
                alert('Error: ' + error.message);
            } finally {
                btn.disabled = false;
                loading.classList.remove('show');
            }
        });
        
        function displayResults(report, overlayUrl, reportUrl) {
            const statusBadge = document.getElementById('status-badge');
            const stats = document.getElementById('stats');
            const issues = document.getElementById('issues');
            const links = document.getElementById('links');
            const results = document.getElementById('results');
            
            // Status badge
            const statusClass = report.summary.status === 'PASS' ? 'status-pass' : 
                               report.summary.status === 'WARNING' ? 'status-warning' : 'status-fail';
            statusBadge.innerHTML = `<span class="status-badge ${statusClass}">${report.summary.status}: ${report.summary.message}</span>`;
            
            // Stats
            stats.innerHTML = `
                <div class="stat-card">
                    <div class="stat-label">Overall Accuracy</div>
                    <div class="stat-value">${report.overallAccuracy}%</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Fields Verified</div>
                    <div class="stat-value">${report.fieldsVerified}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Fields Matched</div>
                    <div class="stat-value">${report.fieldsMatched}</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Issues Found</div>
                    <div class="stat-value">${report.issues.length}</div>
                </div>
            `;
            
            // Issues
            if (report.issues.length > 0) {
                issues.innerHTML = '<h3>Issues Found:</h3>' + 
                    report.issues.map(issue => `
                        <div class="issue-item ${issue.severity === 'warning' ? 'warning' : ''}">
                            <strong>${issue.field}</strong>: ${issue.message}
                        </div>
                    `).join('');
            } else {
                issues.innerHTML = '<p style="color: #28a745; font-weight: 600;">✅ No issues found! All positions are accurate.</p>';
            }
            
            // Links
            links.innerHTML = `
                <a href="${overlayUrl}" target="_blank">View Visual Overlay</a>
                <a href="${reportUrl}" target="_blank">Download Detailed Report</a>
            `;
            
            results.classList.add('show');
        }
    </script>
</body>
</html>

