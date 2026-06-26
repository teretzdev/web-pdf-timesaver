<?php
/**
 * Debug Extraction Page
 * Tests extraction with already uploaded PDF without requiring user interaction
 */

require_once __DIR__ . '/../lib/pdf_field_extractor.php';

use WebPdfTimeSaver\Mvp\PdfFieldExtractor;

// Use the already uploaded FL-110 PDF
$templateId = 'auto_1763401469202';

// Fix path resolution - use absolute paths from project root
$projectRoot = dirname(__DIR__, 2); // Go up from mvp/views to project root
$pdfPath = $projectRoot . '/uploads/' . $templateId . '.pdf';
$uploadsDir = $projectRoot . '/uploads';
$dataDir = $projectRoot . '/data';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Extraction Debug Test</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #d4d4d4; }
        .section { background: #252526; padding: 15px; margin: 10px 0; border-left: 3px solid #007acc; }
        .success { border-color: #4ec9b0; }
        .error { border-color: #f48771; }
        .warning { border-color: #dcdcaa; }
        h2 { margin-top: 0; color: #4ec9b0; }
        pre { background: #1e1e1e; padding: 10px; overflow-x: auto; }
        .field { background: #2d2d30; padding: 5px; margin: 5px 0; }
        .key { color: #4ec9b0; }
        .value { color: #ce9178; }
    </style>
</head>
<body>
    <h1>🔍 Extraction Debug Test</h1>
    
    <?php
    echo "<div class='section'>";
    echo "<h2>Test Configuration</h2>";
    echo "<p><strong>Template ID:</strong> $templateId</p>";
    echo "<p><strong>PDF Path:</strong> $pdfPath</p>";
    echo "<p><strong>PDF Exists:</strong> " . (file_exists($pdfPath) ? '✅ YES' : '❌ NO') . "</p>";
    if (file_exists($pdfPath)) {
        echo "<p><strong>PDF Size:</strong> " . number_format(filesize($pdfPath) / 1024, 2) . " KB</p>";
        echo "<p><strong>PDF Modified:</strong> " . date('Y-m-d H:i:s', filemtime($pdfPath)) . "</p>";
    }
    echo "</div>";
    
    if (!file_exists($pdfPath)) {
        echo "<div class='section error'>";
        echo "<h2>❌ Error</h2>";
        echo "<p>PDF file not found: $pdfPath</p>";
        echo "<p>Please ensure the PDF was uploaded first.</p>";
        echo "</div>";
        exit;
    }
    
    // Check extraction details file
    $detailsFile = $dataDir . '/' . $templateId . '_extraction_details.json';
    echo "<div class='section'>";
    echo "<h2>Extraction Details File</h2>";
    echo "<p><strong>Path:</strong> $detailsFile</p>";
    echo "<p><strong>Exists:</strong> " . (file_exists($detailsFile) ? '✅ YES' : '❌ NO') . "</p>";
    
    if (file_exists($detailsFile)) {
        $detailsContent = file_get_contents($detailsFile);
        $detailsData = json_decode($detailsContent, true);
        
        if ($detailsData) {
            echo "<p><strong>Success:</strong> " . ($detailsData['success'] ? '✅ YES' : '❌ NO') . "</p>";
            echo "<p><strong>Method:</strong> " . ($detailsData['method'] ?? 'unknown') . "</p>";
            echo "<p><strong>Fields in file:</strong> " . count($detailsData['fields'] ?? []) . "</p>";
            echo "<p><strong>Methods Used:</strong> " . implode(', ', $detailsData['methodsUsed'] ?? []) . "</p>";
            
            if (!empty($detailsData['fields'])) {
                echo "<h3>Fields from Details File:</h3>";
                foreach ($detailsData['fields'] as $field) {
                    $name = $field['name'] ?? $field['canonicalName'] ?? 'unnamed';
                    $type = $field['type'] ?? 'unknown';
                    echo "<div class='field'>";
                    echo "<span class='key'>$name</span> ($type) - ";
                    echo "x: " . ($field['x'] ?? 'N/A') . ", y: " . ($field['y'] ?? 'N/A');
                    echo "</div>";
                }
            }
        }
    }
    echo "</div>";
    
    // Run extraction
    echo "<div class='section'>";
    echo "<h2>Running Extraction...</h2>";
    
    try {
        $extractor = new PdfFieldExtractor();
        
        echo "<p>Calling extractAndGenerateBackgrounds()...</p>";
        flush();
        
        $startTime = microtime(true);
        $extractResult = $extractor->extractAndGenerateBackgrounds($pdfPath, $templateId, $uploadsDir);
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        
        echo "<p><strong>Extraction completed in {$duration}ms</strong></p>";
        
        $fields = $extractResult['fields'] ?? [];
        $backgrounds = $extractResult['backgrounds'] ?? [];
        $ensembleMetadata = $extractResult['ensembleMetadata'] ?? null;
        
        echo "<h3>Results:</h3>";
        echo "<p><strong>Fields returned:</strong> " . count($fields) . "</p>";
        echo "<p><strong>Backgrounds:</strong> " . count($backgrounds) . "</p>";
        echo "<p><strong>Ensemble Metadata:</strong> " . ($ensembleMetadata ? '✅ PRESENT' : '❌ NULL') . "</p>";
        
        if ($ensembleMetadata) {
            echo "<p><strong>Method:</strong> " . ($ensembleMetadata['method'] ?? 'unknown') . "</p>";
            echo "<p><strong>Methods Used:</strong> " . count($ensembleMetadata['methodsUsed'] ?? []) . "</p>";
        }
        
        if (!empty($fields)) {
            echo "<div class='section success'>";
            echo "<h3>✅ Fields Found:</h3>";
            foreach ($fields as $key => $field) {
                echo "<div class='field'>";
                echo "<span class='key'>$key</span> - ";
                echo "Type: " . ($field['type'] ?? 'unknown') . ", ";
                echo "Page: " . ($field['page'] ?? 'N/A') . ", ";
                echo "Position: (" . ($field['x'] ?? 'N/A') . ", " . ($field['y'] ?? 'N/A') . ")";
                echo "</div>";
            }
            echo "</div>";
        } else {
            echo "<div class='section error'>";
            echo "<h3>❌ No Fields Returned</h3>";
            echo "<p>Extraction completed but no fields were returned.</p>";
            
            // Debug info
            echo "<h4>Debug Info:</h4>";
            echo "<pre>";
            echo "Extract result keys: " . implode(', ', array_keys($extractResult)) . "\n";
            echo "Fields in result: " . (isset($extractResult['fields']) ? count($extractResult['fields']) : 'NOT SET') . "\n";
            if (isset($extractResult['fields'])) {
                echo "Fields type: " . gettype($extractResult['fields']) . "\n";
                if (is_array($extractResult['fields'])) {
                    echo "Fields is_array: YES\n";
                    echo "Fields count: " . count($extractResult['fields']) . "\n";
                } else {
                    echo "Fields is_array: NO\n";
                    echo "Fields value: " . var_export($extractResult['fields'], true) . "\n";
                }
            }
            echo "</pre>";
            echo "</div>";
        }
        
        // Show raw result for debugging
        echo "<div class='section'>";
        echo "<h3>Raw Extract Result (JSON):</h3>";
        echo "<pre>" . json_encode([
            'fields_count' => count($fields),
            'backgrounds_count' => count($backgrounds),
            'has_ensemble_metadata' => !empty($ensembleMetadata),
            'field_keys' => array_keys($fields),
            'ensemble_method' => $ensembleMetadata['method'] ?? null,
            'ensemble_methods_used' => $ensembleMetadata['methodsUsed'] ?? null
        ], JSON_PRETTY_PRINT) . "</pre>";
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div class='section error'>";
        echo "<h3>❌ Error</h3>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        echo "</div>";
    }
    
    echo "</div>";
    
    // Check actual data directory locations
    echo "<div class='section'>";
    echo "<h2>Data Directory Path Resolution</h2>";
    
    $testPaths = [
        'Workspace data dir' => 'C:/Users/Shadow/Web-PDFTimeSaver/data',
        'XAMPP data dir' => 'C:/xampp/htdocs/Web-PDFTimeSaver/data',
        'Relative from mvp/lib' => __DIR__ . '/../../data',
        'Relative from mvp/views' => dirname(__DIR__, 2) . '/data',
    ];
    
    foreach ($testPaths as $label => $path) {
        $exists = is_dir($path);
        $realpath = $exists ? realpath($path) : null;
        $detailsFile = $path . '/' . $templateId . '_extraction_details.json';
        $fileExists = file_exists($detailsFile);
        
        echo "<p><strong>$label:</strong></p>";
        echo "<ul>";
        echo "<li>Path: $path</li>";
        echo "<li>Exists: " . ($exists ? '✅ YES' : '❌ NO') . "</li>";
        if ($realpath) {
            echo "<li>Realpath: $realpath</li>";
        }
        echo "<li>Details file exists: " . ($fileExists ? '✅ YES' : '❌ NO') . "</li>";
        if ($fileExists) {
            $fileData = json_decode(file_get_contents($detailsFile), true);
            echo "<li>Fields in file: " . count($fileData['fields'] ?? []) . "</li>";
        }
        echo "</ul>";
    }
    echo "</div>";
    
    // Check error log for recent entries
    $errorLogPath = ini_get('error_log');
    if (!$errorLogPath) {
        $errorLogPath = 'C:/xampp/apache/logs/error.log';
    }
    
    if (file_exists($errorLogPath)) {
        echo "<div class='section'>";
        echo "<h2>Recent Error Log Entries</h2>";
        echo "<p><strong>Log file:</strong> $errorLogPath</p>";
        echo "<p><strong>Last modified:</strong> " . date('Y-m-d H:i:s', filemtime($errorLogPath)) . "</p>";
        
        $lines = file($errorLogPath);
        $recent = array_slice($lines, -200);
        $relevant = array_filter($recent, function($line) use ($templateId) {
            return stripos($line, $templateId) !== false || 
                   stripos($line, 'extract') !== false ||
                   stripos($line, 'AutoPosition') !== false ||
                   stripos($line, '=== EXTRACTION') !== false ||
                   stripos($line, 'CONVERTING FIELDS') !== false ||
                   stripos($line, 'RETURNING FIELDS') !== false ||
                   stripos($line, 'Data dir') !== false ||
                   stripos($line, 'details file') !== false;
        });
        
        if (!empty($relevant)) {
            echo "<pre>" . htmlspecialchars(implode('', array_slice($relevant, -100))) . "</pre>";
        } else {
            echo "<p>No relevant log entries found in last 200 lines.</p>";
            echo "<p>Showing last 20 lines of log:</p>";
            echo "<pre>" . htmlspecialchars(implode('', array_slice($lines, -20))) . "</pre>";
        }
        echo "</div>";
    } else {
        echo "<div class='section warning'>";
        echo "<h2>Error Log Not Found</h2>";
        echo "<p>Could not find error log at: $errorLogPath</p>";
        echo "<p>PHP error_log setting: " . (ini_get('error_log') ?: 'not set (using default)') . "</p>";
        echo "</div>";
    }
    ?>
    
    <div class="section">
        <h2>🔧 Actions</h2>
        <p><a href="?route=universal-processor" style="color: #4ec9b0;">← Back to Universal Processor</a></p>
        <p><a href="?" style="color: #4ec9b0;">← Back to Dashboard</a></p>
    </div>
</body>
</html>

