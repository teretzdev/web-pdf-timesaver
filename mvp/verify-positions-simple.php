<?php
/**
 * Simple Position Verification - Actually Useful Version
 * 
 * Generates a debug PDF showing expected positions as green boxes,
 * then overlays actual text. You can visually see if they align.
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '1');

try {
    require_once __DIR__ . '/../vendor/autoload.php';
    require_once __DIR__ . '/lib/position_debug_generator.php';
    require_once __DIR__ . '/lib/fl100_test_data_generator.php';
    require_once __DIR__ . '/lib/pdf_form_filler.php';
} catch (\Throwable $e) {
    die("Error loading files: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
}

use WebPdfTimeSaver\Mvp\PositionDebugGenerator;
use WebPdfTimeSaver\Mvp\PdfFormFiller;
use WebPdfTimeSaver\Mvp\FL100TestDataGenerator;

/**
 * Get the base path of the application dynamically
 * Determines the base path from the current request URI
 * Returns path like '/Web-PDFTimeSaver/' or '/' depending on installation
 */
function getBasePath(): string {
    // Get script name (e.g., '/Web-PDFTimeSaver/mvp/index.php' or '/mvp/index.php')
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    
    // Remove '/mvp/verify-positions-simple.php' from the end to get base path
    $basePath = dirname(dirname($scriptName));
    
    // Normalize: convert '\' to '/' for Windows
    $basePath = str_replace('\\', '/', $basePath);
    
    // Remove trailing slashes
    $basePath = rtrim($basePath, '/');
    
    // If base path is root, return '/'
    if ($basePath === '' || $basePath === '.' || $basePath === '/' || $basePath === '/mvp') {
        return '/';
    }
    
    // Ensure base path ends with a slash (PHP 7.x compatible check)
    if ($basePath !== '/' && substr($basePath, -1) !== '/') {
        $basePath .= '/';
    }
    
    return $basePath;
}

$templateId = $_GET['template'] ?? 't_fl100_gc120';
$action = $_GET['action'] ?? 'form';

if ($action === 'generate') {
    // Check if this is an AJAX request
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    
    if ($isAjax) {
        header('Content-Type: application/json');
    }
    
    // Enable error reporting for debugging
    error_reporting(E_ALL);
    ini_set('display_errors', $isAjax ? '0' : '1'); // Show errors if not AJAX
    
    try {
        // Load positions - try multiple paths
        $positionsFile = null;
        $possiblePaths = [
            __DIR__ . '/../data/' . $templateId . '_positions.json',
            realpath(__DIR__ . '/../data/' . $templateId . '_positions.json'),
            dirname(__DIR__) . '/data/' . $templateId . '_positions.json',
        ];
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                $positionsFile = $path;
                break;
            }
        }
        if (!$positionsFile || !file_exists($positionsFile)) {
            throw new \RuntimeException("Position file not found. Tried: " . implode(', ', $possiblePaths));
        }
        
        $positions = json_decode(file_get_contents($positionsFile), true);
        $testData = FL100TestDataGenerator::generateCompleteTestData();
        
        // Generate actual PDF
        $filler = new PdfFormFiller();
        $template = ['id' => $templateId, 'fields' => []];
        foreach ($positions as $fieldName => $pos) {
            $template['fields'][] = ['key' => $fieldName, 'type' => $pos['type'] ?? 'text', 'label' => $fieldName];
        }
        
        // Use fillPdfFormWithPositions to ensure positions from JSON are used
        $result = $filler->fillPdfFormWithPositions($template, $testData, $templateId);
        $actualPdf = $result['path'];
        
        // Generate debug PDF
        $debugDir = __DIR__ . '/../uploads/verification';
        if (!is_dir($debugDir)) {
            mkdir($debugDir, 0755, true);
        }
        
        $debugGenerator = new PositionDebugGenerator();
        $timestamp = date('Ymd_His');
        $debugPdf = $debugDir . '/debug_' . $templateId . '_' . $timestamp . '.pdf';
        $debugGenerator->generateDebugPdf($templateId, $positions, $testData, $debugPdf);
        
        // Generate comparison PDF
        $comparisonPdf = $debugDir . '/comparison_' . $templateId . '_' . $timestamp . '.pdf';
        $debugGenerator->generateComparisonPdf($templateId, $positions, $testData, $actualPdf, $comparisonPdf);
        
        // Convert absolute paths to web-accessible URLs
        // Get project root directory
        $projectRoot = str_replace('\\', '/', realpath(__DIR__ . '/..'));
        
        // Convert to relative URLs from project root
        $debugPdfPath = str_replace('\\', '/', $debugPdf);
        $actualPdfPath = str_replace('\\', '/', $actualPdf);
        $comparisonPdfPath = str_replace('\\', '/', $comparisonPdf);
        
        // Extract relative paths (remove project root)
        $debugPdfUrl = str_replace($projectRoot . '/', '', $debugPdfPath);
        $actualPdfUrl = str_replace($projectRoot . '/', '', $actualPdfPath);
        $comparisonPdfUrl = str_replace($projectRoot . '/', '', $comparisonPdfPath);
        
        // Remove 'mvp/' prefix if present (since we're in mvp directory)
        $debugPdfUrl = preg_replace('#^mvp/#', '', $debugPdfUrl);
        $actualPdfUrl = preg_replace('#^mvp/#', '', $actualPdfUrl);
        $comparisonPdfUrl = preg_replace('#^mvp/#', '', $comparisonPdfUrl);
        
        // Get base path dynamically
        $basePath = getBasePath();
        
        // Ensure paths start with correct web root
        if (strpos($debugPdfUrl, 'uploads/') === 0) {
            $debugPdfUrl = $basePath . $debugPdfUrl;
        }
        if (strpos($actualPdfUrl, 'output/') === 0) {
            $actualPdfUrl = $basePath . $actualPdfUrl;
        }
        if (strpos($comparisonPdfUrl, 'uploads/') === 0) {
            $comparisonPdfUrl = $basePath . $comparisonPdfUrl;
        }
        
        // Ensure files exist
        if (!file_exists($debugPdf)) {
            throw new \RuntimeException("Failed to create debug PDF at: $debugPdf");
        }
        if (!file_exists($comparisonPdf)) {
            throw new \RuntimeException("Failed to create comparison PDF at: $comparisonPdf");
        }
        
        if ($isAjax) {
            echo json_encode([
                'success' => true,
                'debug_pdf' => $debugPdfUrl,
                'actual_pdf' => $actualPdfUrl,
                'comparison_pdf' => $comparisonPdfUrl,
                'instructions' => [
                    '1. Open the debug PDF - green boxes show where text SHOULD be',
                    '2. Compare with actual PDF - see if text aligns with green boxes',
                    '3. If misaligned, adjust positions in JSON file',
                    '4. Comparison PDF shows expected (page 1) vs actual (page 2) side-by-side'
                ]
            ]);
        } else {
            // Non-AJAX: show HTML page with links
            ?>
            <!DOCTYPE html>
            <html>
            <head>
                <title>Debug PDFs Generated</title>
                <style>
                    body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
                    .btn { display: inline-block; padding: 12px 24px; background: #28a745; color: white; text-decoration: none; border-radius: 6px; margin: 10px 10px 10px 0; }
                    .btn:hover { background: #218838; }
                </style>
            </head>
            <body>
                <h1>✅ Debug PDFs Generated!</h1>
                <p><a href="<?= htmlspecialchars($debugPdfUrl) ?>" class="btn" target="_blank">Open Debug PDF</a></p>
                <p><a href="<?= htmlspecialchars($actualPdfUrl) ?>" class="btn" target="_blank">Open Actual PDF</a></p>
                <p><a href="<?= htmlspecialchars($comparisonPdfUrl) ?>" class="btn" target="_blank">Open Comparison PDF</a></p>
                <h3>Instructions:</h3>
                <ol>
                    <li>Open the debug PDF - green boxes show where text SHOULD be</li>
                    <li>Compare with actual PDF - see if text aligns with green boxes</li>
                    <li>If misaligned, adjust positions in JSON file</li>
                    <li>Comparison PDF shows expected (page 1) vs actual (page 2) side-by-side</li>
                </ol>
            </body>
            </html>
            <?php
        }
        
    } catch (\Exception $e) {
        if ($isAjax) {
            http_response_code(500);
            $errorDetails = [
                'success' => false,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ];
            error_log("Position verification error: " . json_encode($errorDetails));
            echo json_encode($errorDetails);
        } else {
            echo "<h1>Error</h1>";
            echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p>File: " . htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "</p>";
            echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        }
    }
    exit;
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Simple Position Verification</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .btn { padding: 12px 24px; background: #667eea; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; }
        .btn:hover { background: #5568d3; }
        .result { margin-top: 20px; padding: 15px; background: #f0f0f0; border-radius: 6px; }
        .result a { display: inline-block; margin: 5px 10px 5px 0; padding: 8px 16px; background: #28a745; color: white; text-decoration: none; border-radius: 4px; }
        .instructions { margin-top: 20px; padding: 15px; background: #e7f3ff; border-left: 4px solid #2196F3; }
        .instructions ol { margin: 10px 0; padding-left: 25px; }
    </style>
</head>
<body>
    <h1>Simple Position Verification</h1>
    <p>This generates a debug PDF with green boxes showing where text SHOULD be. Compare it with the actual PDF to see if positions are accurate.</p>
    
    <form id="verify-form" method="GET" action="">
        <input type="hidden" name="action" value="generate">
        <label>Template ID: <input type="text" name="template" value="<?= htmlspecialchars($templateId) ?>" required></label>
        <button type="submit" class="btn">Generate Debug PDF</button>
    </form>
    
    <div id="result"></div>
    
    <div class="instructions">
        <h3>How to Use:</h3>
        <ol>
            <li><strong>Debug PDF</strong> - Shows green boxes where text should be, with actual text overlaid</li>
            <li><strong>Actual PDF</strong> - The generated PDF with text</li>
            <li><strong>Comparison PDF</strong> - Side-by-side: expected (page 1) vs actual (page 2)</li>
            <li>If green boxes don't align with text, adjust positions in the JSON file</li>
        </ol>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('verify-form');
            if (!form) return;
            
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                const result = document.getElementById('result');
                if (!result) return;
                
                result.innerHTML = '<p>Generating...</p>';
                
                const templateInput = form.querySelector('input[name="template"]');
                const template = templateInput ? templateInput.value : 't_fl100_gc120';
                
                try {
                    const response = await fetch('?action=generate&template=' + encodeURIComponent(template));
                    const data = await response.json();
                    
                    if (data.success) {
                        result.innerHTML = `
                            <h3>✅ Debug PDFs Generated!</h3>
                            <p><a href="${data.debug_pdf}" target="_blank">Open Debug PDF</a></p>
                            <p><a href="${data.actual_pdf}" target="_blank">Open Actual PDF</a></p>
                            <p><a href="${data.comparison_pdf}" target="_blank">Open Comparison PDF</a></p>
                            <div class="instructions">
                                <strong>Instructions:</strong>
                                <ol>
                                    ${data.instructions.map(i => '<li>' + i + '</li>').join('')}
                                </ol>
                            </div>
                        `;
                    } else {
                        result.innerHTML = '<p style="color: red;">Error: ' + (data.error || 'Unknown error') + '</p>';
                    }
                } catch (error) {
                    result.innerHTML = '<p style="color: red;">Error: ' + error.message + '</p>';
                }
            });
        });
    </script>
</body>
</html>

