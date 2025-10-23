<?php
/**
 * Download FL-105/GC-120 (Declaration Under UCCJEA) form
 */

echo "Downloading FL-105/GC-120 form...\n";

$url = 'https://www.courts.ca.gov/documents/fl105.pdf';
$outputFile = __DIR__ . '/uploads/fl105.pdf';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

$pdfData = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($httpCode === 200 && $pdfData !== false && strlen($pdfData) > 1000) {
    if (file_put_contents($outputFile, $pdfData)) {
        echo "✅ SUCCESS: Downloaded FL-105 to $outputFile\n";
        echo "File size: " . number_format(strlen($pdfData)) . " bytes\n";
        
        // Copy to XAMPP
        $xamppFile = 'C:\xampp\htdocs\Web-PDFTimeSaver\uploads\fl105.pdf';
        if (copy($outputFile, $xamppFile)) {
            echo "✅ Copied to XAMPP: $xamppFile\n";
        }
    } else {
        echo "❌ ERROR: Failed to save FL-105 file\n";
    }
} else {
    echo "❌ ERROR: Failed to download FL-105 (HTTP $httpCode)\n";
    if ($error) {
        echo "CURL Error: $error\n";
    }
}
?>
