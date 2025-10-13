<?php
// Download IRS W-9 fillable form for testing
$url = 'https://www.irs.gov/pub/irs-pdf/fw9.pdf';
$destination = __DIR__ . '/uploads/test_w9_fillable.pdf';

echo "Downloading IRS W-9 form...\n";
$content = file_get_contents($url);

if ($content === false) {
    echo "Failed to download\n";
    exit(1);
}

file_put_contents($destination, $content);
echo "✅ Downloaded: " . filesize($destination) . " bytes\n";
echo "Saved to: $destination\n";


