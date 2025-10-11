<?php
/**
 * Simple Clio PDF Analysis
 */

echo "Analyzing Clio PDF (test.pdf)...\n";

$clioPdf = __DIR__ . '/test.pdf';
if (!file_exists($clioPdf)) {
    die("test.pdf not found\n");
}

echo "✓ Found test.pdf\n";

// Try to extract text using simple method
try {
    $parser = new Smalot\PdfParser\Parser();
    $pdf = $parser->parseFile($clioPdf);
    $text = $pdf->getText();
    
    echo "✓ Extracted " . strlen($text) . " characters\n\n";
    
    // Look for our test data
    $testValues = [
        'John Michael Smith, Esq.',
        '123456',
        'Smith & Associates Family Law',
        'FL-2024-001234',
        'Sarah Elizabeth Johnson',
        'Michael David Johnson',
        '06/15/2010',
        '03/20/2024',
        'Las Vegas, Nevada',
        'Irreconcilable differences',
        'Request for temporary custody orders',
        'John M. Smith',
        '10/09/2025'
    ];
    
    echo "Checking for test data in Clio PDF:\n";
    foreach ($testValues as $value) {
        if (stripos($text, $value) !== false) {
            echo "✓ Found: $value\n";
        } else {
            echo "✗ Missing: $value\n";
        }
    }
    
    echo "\nText sample from Clio PDF:\n";
    echo substr($text, 0, 500) . "...\n\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "Next steps:\n";
echo "1. Compare this text with our generated PDF\n";
echo "2. Use field_position_measurement_tool.html to measure positions\n";
echo "3. Update positions based on measurements\n";


