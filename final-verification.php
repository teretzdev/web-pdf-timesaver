<?php
/**
 * Final Verification Script
 * Tests the actual working system
 */

echo "=== FINAL VERIFICATION ===\n\n";

// Test 1: Check if demo page loads
echo "1. Testing Demo Page:\n";
$demoUrl = 'http://localhost/Web-PDFTimeSaver/demo-working-autofill.php';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $demoUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200) {
    echo "  ✅ Demo page loads (HTTP $httpCode)\n";
    if (strpos($response, 'Auto Field Detection Demo') !== false) {
        echo "  ✅ Demo page has correct title\n";
    } else {
        echo "  ❌ Demo page missing title\n";
    }
} else {
    echo "  ❌ Demo page failed (HTTP $httpCode)\n";
}

// Test 2: Check navigation
echo "\n2. Testing Navigation:\n";
$navUrl = 'http://localhost/Web-PDFTimeSaver/mvp/?route=dashboard';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $navUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200) {
    echo "  ✅ Dashboard loads (HTTP $httpCode)\n";
    if (strpos($response, 'demo-working-autofill') !== false) {
        echo "  ✅ Demo route found in navigation\n";
    } else {
        echo "  ❌ Demo route missing from navigation\n";
    }
    if (strpos($response, 'Auto Field Detection') !== false) {
        echo "  ✅ Auto Field Detection label found\n";
    } else {
        echo "  ❌ Auto Field Detection label missing\n";
    }
} else {
    echo "  ❌ Dashboard failed (HTTP $httpCode)\n";
}

// Test 3: Check route handler
echo "\n3. Testing Route Handler:\n";
$routeUrl = 'http://localhost/Web-PDFTimeSaver/mvp/?route=demo-working-autofill';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $routeUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
curl_close($ch);

if ($httpCode == 200) {
    echo "  ✅ Route handler works (HTTP $httpCode)\n";
    if (strpos($finalUrl, 'demo-working-autofill.php') !== false) {
        echo "  ✅ Redirects to demo page\n";
    } else {
        echo "  ❌ Does not redirect to demo page\n";
    }
} else {
    echo "  ❌ Route handler failed (HTTP $httpCode)\n";
}

// Test 4: Check universal processor endpoint
echo "\n4. Testing Universal Processor:\n";
$processorUrl = 'http://localhost/Web-PDFTimeSaver/mvp/?route=actions/universal-process';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $processorUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, ['template_id' => 'test_verification']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200) {
    echo "  ✅ Universal processor responds (HTTP $httpCode)\n";
    $data = json_decode($response, true);
    if ($data && isset($data['success'])) {
        echo "  ✅ Returns valid JSON response\n";
        echo "  ℹ️  Response: " . ($data['success'] ? 'SUCCESS' : 'FAILED') . "\n";
        if (isset($data['message'])) {
            echo "  ℹ️  Message: " . $data['message'] . "\n";
        }
    } else {
        echo "  ❌ Invalid JSON response\n";
    }
} else {
    echo "  ❌ Universal processor failed (HTTP $httpCode)\n";
}

// Test 5: Check background images
echo "\n5. Testing Background Images:\n";
$bgFiles = glob('uploads/*_background.png');
if ($bgFiles) {
    echo "  ✅ Found " . count($bgFiles) . " background images\n";
    foreach (array_slice($bgFiles, 0, 3) as $file) {
        if (file_exists($file)) {
            echo "  ✅ " . basename($file) . " exists\n";
        } else {
            echo "  ❌ " . basename($file) . " missing\n";
        }
    }
} else {
    echo "  ❌ No background images found\n";
}

// Test 6: Check position files
echo "\n6. Testing Position Files:\n";
$posFiles = glob('data/*_positions.json');
if ($posFiles) {
    echo "  ✅ Found " . count($posFiles) . " position files\n";
    foreach ($posFiles as $file) {
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
            $fieldCount = count($data);
            echo "  ✅ " . basename($file) . " ($fieldCount fields)\n";
        } else {
            echo "  ❌ " . basename($file) . " missing\n";
        }
    }
} else {
    echo "  ❌ No position files found\n";
}

echo "\n=== VERIFICATION COMPLETE ===\n";
echo "Open http://localhost/Web-PDFTimeSaver/test-suite.php for detailed results\n";
echo "Open http://localhost/Web-PDFTimeSaver/demo-working-autofill.php to test the demo\n";
echo "Open http://localhost/Web-PDFTimeSaver/mvp/?route=demo-working-autofill to test navigation\n";
?>
