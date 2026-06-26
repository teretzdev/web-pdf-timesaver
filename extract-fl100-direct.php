<?php
/**
 * Direct extraction of FL-100 fields from decrypted PDF
 * Uses PHP Smalot\PdfParser which can handle XFA forms
 */

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/mvp/lib/pdf_field_extractor.php';

$templateId = 't_fl100_gc120';
$decryptedPath = __DIR__ . '/temp/test_decrypt.pdf';

if (!file_exists($decryptedPath)) {
    die("ERROR: Decrypted PDF not found. Please decrypt first.\n");
}

echo "Extracting fields from decrypted PDF: $decryptedPath\n";
echo "Template ID: $templateId\n\n";

// Use the original encrypted PDF - the extractor will handle decryption
$originalPath = __DIR__ . '/uploads/fl100.pdf';
if (!file_exists($originalPath)) {
    die("ERROR: Original PDF not found: $originalPath\n");
}

$extractor = new \WebPdfTimeSaver\Mvp\PdfFieldExtractor();

// Force use of qpdf decryption + PHP parser (skip Node.js)
// We'll manually decrypt and then use PHP parser
$qpdfPath = 'C:\\Program Files\\qpdf 12.2.0\\bin\\qpdf.exe';
$tempDecrypted = __DIR__ . '/temp/fl100_decrypted_' . time() . '.pdf';

echo "Decrypting PDF...\n";
exec("\"$qpdfPath\" --decrypt \"$originalPath\" \"$tempDecrypted\" 2>&1", $output, $returnCode);

if ($returnCode !== 0 || !file_exists($tempDecrypted)) {
    die("ERROR: Failed to decrypt PDF\n");
}

echo "Decrypted PDF created: $tempDecrypted\n\n";

// Now use PHP parser on decrypted PDF
$reflection = new ReflectionClass($extractor);
$method = $reflection->getMethod('extractUsingPdfParser');
$method->setAccessible(true);
$fields = $method->invoke($extractor, $tempDecrypted);

// Cleanup
@unlink($tempDecrypted);

echo "Fields extracted: " . count($fields) . "\n\n";

if (count($fields) > 0) {
    // Save to positions file
    $positionFile = __DIR__ . "/data/{$templateId}_positions.json";
    file_put_contents($positionFile, json_encode($fields, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    
    echo "✅ Positions saved to: $positionFile\n\n";
    
    echo "First 10 fields:\n";
    $count = 0;
    foreach ($fields as $name => $data) {
        if ($count++ >= 10) break;
        echo "  $name: x={$data['x']}, y={$data['y']}, page={$data['page']}\n";
    }
} else {
    echo "❌ No fields extracted!\n";
}

