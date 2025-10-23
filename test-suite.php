<?php
/**
 * Comprehensive Test Suite for Web-PDFTimeSaver
 * Runs all tests and displays results in browser
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$tests = [];
$passed = 0;
$failed = 0;

function runTest($name, $testFunction) {
    global $tests, $passed, $failed;
    
    try {
        $result = $testFunction();
        if ($result === true) {
            $tests[] = ['name' => $name, 'status' => 'pass', 'message' => 'PASSED'];
            $passed++;
        } else {
            $tests[] = ['name' => $name, 'status' => 'fail', 'message' => $result];
            $failed++;
        }
    } catch (Exception $e) {
        $tests[] = ['name' => $name, 'status' => 'fail', 'message' => 'ERROR: ' . $e->getMessage()];
        $failed++;
    }
}

// Test 1: File System Check
runTest("File System Check", function() {
    $requiredFiles = [
        'mvp/index.php',
        'mvp/views/layout_header.php',
        'mvp/lib/pdf_field_extractor.php',
        'mvp/lib/pdf_form_filler.php',
        'mvp/lib/field_position_loader.php',
        'demo-working-autofill.php',
        'uploads/fl100.pdf',
        'uploads/w9.pdf'
    ];
    
    foreach ($requiredFiles as $file) {
        if (!file_exists($file)) {
            return "Missing file: $file";
        }
    }
    
    return true;
});

// Test 2: PHP Dependencies
runTest("PHP Dependencies", function() {
    $requiredClasses = [
        'Smalot\PdfParser\Parser',
        'WebPdfTimeSaver\Mvp\PdfFieldExtractor',
        'WebPdfTimeSaver\Mvp\PdfFormFiller',
        'WebPdfTimeSaver\Mvp\FieldPositionLoader'
    ];
    
    foreach ($requiredClasses as $class) {
        if (!class_exists($class)) {
            return "Missing class: $class";
        }
    }
    
    return true;
});

// Test 3: PDF Field Extractor - W-9
runTest("PDF Field Extractor - W-9", function() {
    require_once __DIR__ . '/mvp/lib/pdf_field_extractor.php';
    
    $extractor = new \WebPdfTimeSaver\Mvp\PdfFieldExtractor();
    $result = $extractor->extractAndGenerateBackgrounds(
        __DIR__ . '/uploads/w9.pdf',
        'test_w9',
        __DIR__ . '/uploads'
    );
    
    if (empty($result['fields'])) {
        return "No fields extracted from W-9";
    }
    
    $fieldCount = count($result['fields']);
    if ($fieldCount < 10) {
        return "Expected at least 10 fields, got $fieldCount";
    }
    
    if (empty($result['backgrounds'])) {
        return "No background images generated";
    }
    
    return true;
});

// Test 4: PDF Field Extractor - FL-100
runTest("PDF Field Extractor - FL-100", function() {
    require_once __DIR__ . '/mvp/lib/pdf_field_extractor.php';
    
    $extractor = new \WebPdfTimeSaver\Mvp\PdfFieldExtractor();
    $result = $extractor->extractAndGenerateBackgrounds(
        __DIR__ . '/uploads/fl100.pdf',
        'test_fl100',
        __DIR__ . '/uploads'
    );
    
    if (empty($result['backgrounds'])) {
        return "No background images generated for FL-100";
    }
    
    return true;
});

// Test 5: Navigation Structure
runTest("Navigation Structure", function() {
    $layoutFile = __DIR__ . '/mvp/views/layout_header.php';
    $content = file_get_contents($layoutFile);
    
    if (strpos($content, 'demo-working-autofill') === false) {
        return "Missing demo-working-autofill route in navigation";
    }
    
    if (strpos($content, 'Auto Field Detection') === false) {
        return "Missing 'Auto Field Detection' label in navigation";
    }
    
    return true;
});

// Test 6: Route Handlers
runTest("Route Handlers", function() {
    $indexFile = __DIR__ . '/mvp/index.php';
    $content = file_get_contents($indexFile);
    
    if (strpos($content, "case 'demo-working-autofill':") === false) {
        return "Missing demo-working-autofill route handler";
    }
    
    if (strpos($content, "header('Location: ../demo-working-autofill.php');") === false) {
        return "Missing redirect logic for demo route";
    }
    
    return true;
});

// Test 7: Background Image Generation
runTest("Background Image Generation", function() {
    $uploadDir = __DIR__ . '/uploads';
    $backgroundFiles = glob($uploadDir . '/*_background.png');
    
    if (empty($backgroundFiles)) {
        return "No background images found";
    }
    
    $count = count($backgroundFiles);
    if ($count < 2) {
        return "Expected at least 2 background images, found $count";
    }
    
    // Check if images are valid
    foreach ($backgroundFiles as $file) {
        $size = getimagesize($file);
        if ($size === false) {
            return "Invalid image: " . basename($file);
        }
    }
    
    return true;
});

// Test 8: Position Files
runTest("Position Files", function() {
    $dataDir = __DIR__ . '/data';
    $positionFiles = glob($dataDir . '/*_positions.json');
    
    if (empty($positionFiles)) {
        return "No position files found";
    }
    
    foreach ($positionFiles as $file) {
        $content = file_get_contents($file);
        $data = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return "Invalid JSON in " . basename($file);
        }
        
        if (empty($data)) {
            return "Empty position data in " . basename($file);
        }
    }
    
    return true;
});

// Test 9: Demo Page Functionality
runTest("Demo Page Functionality", function() {
    $demoFile = __DIR__ . '/demo-working-autofill.php';
    
    if (!file_exists($demoFile)) {
        return "Demo page file not found";
    }
    
    $content = file_get_contents($demoFile);
    
    if (strpos($content, 'universal-process') === false) {
        return "Missing universal-process endpoint call";
    }
    
    if (strpos($content, 'displayResults') === false) {
        return "Missing results display function";
    }
    
    if (strpos($content, 'Load W-9 Demo') === false) {
        return "Missing W-9 demo button";
    }
    
    return true;
});

// Test 10: Visual Field Editor
runTest("Visual Field Editor", function() {
    $editorFile = __DIR__ . '/mvp/visual-field-editor.php';
    
    if (!file_exists($editorFile)) {
        return "Visual field editor file not found";
    }
    
    $content = file_get_contents($editorFile);
    
    if (strpos($content, 'extractAndGenerateBackgrounds') === false) {
        return "Missing auto-generation logic in visual editor";
    }
    
    if (strpos($content, 'PdfFieldExtractor') === false) {
        return "Missing PdfFieldExtractor usage in visual editor";
    }
    
    return true;
});

// Test 11: HTTP Endpoints (Basic Check)
runTest("HTTP Endpoints", function() {
    $baseUrl = 'http://localhost/Web-PDFTimeSaver';
    
    $endpoints = [
        '/mvp/?route=dashboard',
        '/demo-working-autofill.php'
    ];
    
    foreach ($endpoints as $endpoint) {
        $url = $baseUrl . $endpoint;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return "CURL error for $url: $error";
        }
        
        if ($httpCode !== 200) {
            return "HTTP $httpCode for $url";
        }
        
        if (empty($response)) {
            return "Empty response from $url";
        }
    }
    
    return true;
});

// Test 12: Universal Processor Endpoint
runTest("Universal Processor Endpoint", function() {
    $url = 'http://localhost/Web-PDFTimeSaver/mvp/?route=actions/universal-process';
    
    // Test with form data
    $postData = [
        'template_id' => 'test_endpoint'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return "CURL error: $error";
    }
    
    if ($httpCode !== 200) {
        return "HTTP $httpCode";
    }
    
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return "Invalid JSON response: " . substr($response, 0, 200);
    }
    
    if (!isset($data['success'])) {
        return "Missing 'success' field in response";
    }
    
    return true;
});

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Suite Results</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 20px;
            background: #f5f6fa;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .summary {
            background: #e9ecef;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .test-result {
            padding: 10px;
            margin: 5px 0;
            border-radius: 4px;
            font-family: monospace;
        }
        .pass { background: #d4edda; color: #155724; }
        .fail { background: #f8d7da; color: #721c24; }
        .info { background: #d1ecf1; color: #0c5460; }
        .refresh-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            margin: 10px 0;
        }
        .refresh-btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Test Suite Results</h1>
        <p>Comprehensive testing of Web-PDFTimeSaver functionality</p>
        
        <div class="summary">
            <h3>Test Summary</h3>
            <p>Total Tests: <?php echo count($tests); ?></p>
            <p>Passed: <?php echo $passed; ?></p>
            <p>Failed: <?php echo $failed; ?></p>
            <p>Success Rate: <?php echo count($tests) > 0 ? round(($passed / count($tests)) * 100, 1) : 0; ?>%</p>
            
            <?php if ($failed === 0): ?>
                <div class="test-result pass">✅ ALL TESTS PASSED - SYSTEM IS WORKING CORRECTLY</div>
            <?php else: ?>
                <div class="test-result fail">❌ SOME TESTS FAILED - SYSTEM NEEDS ATTENTION</div>
            <?php endif; ?>
        </div>
        
        <button class="refresh-btn" onclick="location.reload()">🔄 Refresh Tests</button>
        
        <h3>Test Results</h3>
        <?php foreach ($tests as $test): ?>
            <div class="test-result <?php echo $test['status']; ?>">
                <strong><?php echo $test['status'] === 'pass' ? '✅' : '❌'; ?> <?php echo htmlspecialchars($test['name']); ?></strong>
                <br><?php echo htmlspecialchars($test['message']); ?>
            </div>
        <?php endforeach; ?>
        
        <div class="summary">
            <h3>Next Steps</h3>
            <?php if ($failed === 0): ?>
                <p>🎉 All tests passed! The system is working correctly.</p>
                <p>You can now use:</p>
                <ul>
                    <li><a href="mvp/?route=demo-working-autofill">🚀 Auto Field Detection</a></li>
                    <li><a href="mvp/?route=universal-processor">🤖 Universal Processor</a></li>
                    <li><a href="mvp/?route=extract-fields">🔧 Field Extractor</a></li>
                </ul>
            <?php else: ?>
                <p>⚠️ Some tests failed. Please check the failed tests above and fix the issues.</p>
                <p>Common issues:</p>
                <ul>
                    <li>Missing files - ensure all required files are present</li>
                    <li>PHP dependencies - check if Composer packages are installed</li>
                    <li>HTTP endpoints - verify XAMPP is running and accessible</li>
                    <li>File permissions - ensure uploads directory is writable</li>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
