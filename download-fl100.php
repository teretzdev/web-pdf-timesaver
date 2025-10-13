<?php
// Download official FL-100 from California Courts
$url = 'https://www.courts.ca.gov/documents/fl100.pdf';
$destination = __DIR__ . '/uploads/fl100_official_download.pdf';

echo "Downloading FL-100 from California Courts...\n";
echo "URL: $url\n\n";

$content = @file_get_contents($url);

if ($content === false) {
    echo "❌ Failed to download from official source\n";
    echo "Trying alternative source...\n";
    
    // Try alternative URL
    $url = 'https://www.courts.ca.gov/documents/fl100.pdf';
    $content = @file_get_contents($url);
}

if ($content === false) {
    echo "❌ Download failed\n";
    echo "Please manually download from: https://www.courts.ca.gov/forms.htm\n";
    exit(1);
}

file_put_contents($destination, $content);
$size = filesize($destination);
echo "✅ Downloaded: " . number_format($size) . " bytes (" . round($size/1024, 1) . " KB)\n";
echo "Saved to: $destination\n";

// Also copy to XAMPP uploads
$xamppDest = 'C:/xampp/htdocs/Web-PDFTimeSaver/uploads/fl100_official_download.pdf';
if (copy($destination, $xamppDest)) {
    echo "✅ Copied to XAMPP: $xamppDest\n";
}


