<?php
// Download a sample W-9 form for testing
$url = 'https://www.irs.gov/pub/irs-pdf/fw9.pdf';
$outputFile = __DIR__ . '/uploads/w9.pdf';

echo "Downloading W-9 form from IRS...\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$pdfData = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 && $pdfData !== false) {
    if (file_put_contents($outputFile, $pdfData)) {
        echo "SUCCESS: Downloaded W-9 to $outputFile\n";
        echo "File size: " . number_format(strlen($pdfData)) . " bytes\n";
    } else {
        echo "ERROR: Failed to save W-9 file\n";
    }
} else {
    echo "ERROR: Failed to download W-9 (HTTP $httpCode)\n";
}
?>
