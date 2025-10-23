<?php
/**
 * Quick Diagnostic Script
 * Tests core functionality quickly
 */

echo "=== QUICK DIAGNOSTIC ===\n\n";

// Test 1: Basic PHP
echo "1. PHP Version: " . PHP_VERSION . "\n";

// Test 2: Required files
echo "\n2. File Check:\n";
$files = [
    'mvp/index.php' => 'Main MVP index',
    'mvp/views/layout_header.php' => 'Navigation header',
    'demo-working-autofill.php' => 'Demo page',
    'uploads/w9.pdf' => 'W-9 test PDF',
    'uploads/fl100.pdf' => 'FL-100 test PDF'
];

foreach ($files as $file => $desc) {
    if (file_exists($file)) {
        echo "  ✅ $desc ($file)\n";
    } else {
        echo "  ❌ $desc ($file) - MISSING\n";
    }
}

// Test 3: PHP classes
echo "\n3. PHP Classes:\n";
$classes = [
    'Smalot\PdfParser\Parser' => 'PDF Parser',
    'WebPdfTimeSaver\Mvp\PdfFieldExtractor' => 'Field Extractor',
    'WebPdfTimeSaver\Mvp\PdfFormFiller' => 'Form Filler'
];

foreach ($classes as $class => $desc) {
    if (class_exists($class)) {
        echo "  ✅ $desc ($class)\n";
    } else {
        echo "  ❌ $desc ($class) - MISSING\n";
    }
}

// Test 4: Background images
echo "\n4. Background Images:\n";
$bgFiles = glob('uploads/*_background.png');
if ($bgFiles) {
    echo "  ✅ Found " . count($bgFiles) . " background images\n";
    foreach (array_slice($bgFiles, 0, 3) as $file) {
        echo "    - " . basename($file) . "\n";
    }
} else {
    echo "  ❌ No background images found\n";
}

// Test 5: Position files
echo "\n5. Position Files:\n";
$posFiles = glob('data/*_positions.json');
if ($posFiles) {
    echo "  ✅ Found " . count($posFiles) . " position files\n";
    foreach ($posFiles as $file) {
        $data = json_decode(file_get_contents($file), true);
        $fieldCount = count($data);
        echo "    - " . basename($file) . " ($fieldCount fields)\n";
    }
} else {
    echo "  ❌ No position files found\n";
}

// Test 6: HTTP endpoints
echo "\n6. HTTP Endpoints:\n";
$endpoints = [
    'http://localhost/Web-PDFTimeSaver/mvp/?route=dashboard',
    'http://localhost/Web-PDFTimeSaver/demo-working-autofill.php'
];

foreach ($endpoints as $url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        echo "  ✅ $url (HTTP $httpCode)\n";
    } else {
        echo "  ❌ $url (HTTP $httpCode)\n";
    }
}

// Test 7: Navigation
echo "\n7. Navigation Check:\n";
$layoutFile = 'mvp/views/layout_header.php';
if (file_exists($layoutFile)) {
    $content = file_get_contents($layoutFile);
    if (strpos($content, 'demo-working-autofill') !== false) {
        echo "  ✅ Demo route found in navigation\n";
    } else {
        echo "  ❌ Demo route missing from navigation\n";
    }
    
    if (strpos($content, 'Auto Field Detection') !== false) {
        echo "  ✅ Auto Field Detection label found\n";
    } else {
        echo "  ❌ Auto Field Detection label missing\n";
    }
} else {
    echo "  ❌ Layout header file missing\n";
}

// Test 8: Route handlers
echo "\n8. Route Handlers:\n";
$indexFile = 'mvp/index.php';
if (file_exists($indexFile)) {
    $content = file_get_contents($indexFile);
    if (strpos($content, "case 'demo-working-autofill':") !== false) {
        echo "  ✅ Demo route handler found\n";
    } else {
        echo "  ❌ Demo route handler missing\n";
    }
} else {
    echo "  ❌ Index file missing\n";
}

echo "\n=== DIAGNOSTIC COMPLETE ===\n";
echo "Open http://localhost/Web-PDFTimeSaver/test-suite.php for detailed results\n";
?>
