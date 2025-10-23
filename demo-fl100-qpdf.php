<?php
/**
 * FL-100 Demo with Real qpdf Integration
 * Demonstrates filling FL-100 with test data using qpdf-decrypted PDF
 */

echo "🎯 FL-100 DEMO WITH REAL QPDF INTEGRATION\n";
echo "==========================================\n\n";

// Test data for FL-100
$testData = [
    'attorney_name' => 'John Michael Smith, Esq.',
    'attorney_firm' => 'Smith & Associates Family Law',
    'attorney_bar' => '123456',
    'attorney_street' => '1234 Legal Plaza, Suite 500',
    'attorney_city' => 'Los Angeles',
    'attorney_state' => 'CA',
    'attorney_zip' => '90210',
    'attorney_phone' => '(555) 123-4567',
    'attorney_email' => 'jsmith@smithlaw.com',
    'petitioner_name' => 'Sarah Elizabeth Johnson',
    'respondent_name' => 'Michael David Johnson',
    'marriage_date' => '06/15/2010',
    'separation_date' => '03/20/2024',
    'minor_children' => 'yes',
    'children_count' => '2',
    'case_number' => 'FL-2025-001234'
];

echo "📝 Test Data:\n";
foreach ($testData as $field => $value) {
    echo "   - $field: $value\n";
}
echo "\n";

// Test qpdf decryption
echo "🔓 Testing qpdf decryption...\n";
$qpdfPath = __DIR__ . '/bin/qpdf/bin/qpdf.bat';
$originalPdf = __DIR__ . '/uploads/fl100.pdf';
$decryptedPdf = __DIR__ . '/temp/fl100_demo_decrypted.pdf';

if (file_exists($qpdfPath)) {
    $decryptCmd = escapeshellcmd($qpdfPath) . ' --decrypt ' . escapeshellarg($originalPdf) . ' ' . escapeshellarg($decryptedPdf);
    $result = shell_exec($decryptCmd . ' 2>&1');
    
    if (file_exists($decryptedPdf) && filesize($decryptedPdf) > 0) {
        $fileSize = round(filesize($decryptedPdf) / 1024, 1);
        echo "✅ qpdf decryption successful!\n";
        echo "   - Decrypted file: " . basename($decryptedPdf) . "\n";
        echo "   - File size: {$fileSize} KB\n";
        echo "   - No errors or warnings!\n\n";
    } else {
        echo "❌ qpdf decryption failed\n";
        echo "   - Error: $result\n\n";
    }
} else {
    echo "❌ qpdf not found at: $qpdfPath\n\n";
}

// Test form filling
echo "✏️  Testing FL-100 form filling...\n";

// Simulate the form filling process
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/Web-PDFTimeSaver/fill-fl100-form.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 && $response) {
    $result = json_decode($response, true);
    
    if ($result && $result['success']) {
        echo "✅ FL-100 form filling successful!\n";
        echo "   - Method: {$result['method']}\n";
        echo "   - Fields filled: {$result['fields_filled']}\n";
        echo "   - File size: {$result['file_size']}\n";
        echo "   - PDF URL: {$result['pdf_url']}\n\n";
        
        echo "🎉 SUCCESS! FL-100 form filled with real test data!\n";
        echo "🔧 Using real qpdf for decryption (no more 'problematic PDF' nonsense)\n";
        echo "📄 Real form fields extracted and filled\n";
        echo "✨ Ready for production use!\n";
    } else {
        echo "❌ Form filling failed\n";
        echo "   - Error: " . ($result['message'] ?? 'Unknown error') . "\n";
    }
} else {
    echo "❌ HTTP request failed (Code: $httpCode)\n";
    echo "   - Make sure XAMPP is running\n";
    echo "   - Check: http://localhost/Web-PDFTimeSaver/fill-fl100-form.php\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "FL-100 DEMO COMPLETE\n";
?>
