<?php
echo "Simple Test Starting...\n";

// Test 1: Basic PHP
echo "PHP Version: " . PHP_VERSION . "\n";

// Test 2: File existence
$files = [
    'mvp/index.php',
    'mvp/views/layout_header.php',
    'demo-working-autofill.php',
    'uploads/w9.pdf',
    'uploads/fl100.pdf'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✅ $file exists\n";
    } else {
        echo "❌ $file missing\n";
    }
}

// Test 3: HTTP request
echo "\nTesting HTTP endpoints...\n";
$urls = [
    'http://localhost/Web-PDFTimeSaver/mvp/?route=dashboard',
    'http://localhost/Web-PDFTimeSaver/demo-working-autofill.php'
];

foreach ($urls as $url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200) {
        echo "✅ $url (HTTP $httpCode)\n";
    } else {
        echo "❌ $url (HTTP $httpCode)\n";
    }
}

echo "\nTest complete!\n";
?>

