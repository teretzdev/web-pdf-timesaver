<?php
/**
 * Compare FL-100 PDFs and measure field position differences
 * This extracts text positions from both PDFs and calculates offsets
 */

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Smalot\PdfParser\Parser;

// File paths - find the most recent generated PDF
$outputDir = __DIR__ . '/output';
$pdfFiles = glob($outputDir . '/mvp_*_t_fl100_gc120_positioned.pdf');
if (empty($pdfFiles)) {
    die("ERROR: No FL-100 PDF found in output directory. Run: C:\\xampp\\php\\php.exe test_fl100_generation.php\n");
}
$currentSystemPdf = $pdfFiles[0]; // Use the first (most recent) file
$referencePdf = __DIR__ . '/fl100_draft_clio_reference.pdf';
$positionsFile = __DIR__ . '/data/t_fl100_gc120_positions.json';

echo "FL-100 PDF Position Comparison Tool\n";
echo "====================================\n\n";

// Check files exist
if (!file_exists($currentSystemPdf)) {
    die("ERROR: Current system PDF not found at $currentSystemPdf\nRun: php generate_test_fl100.php\n");
}

if (!file_exists($referencePdf)) {
    die("ERROR: Reference PDF not found at $referencePdf\nPlease download from draft.clio.com and save as fl100_draft_clio_reference.pdf\n");
}

if (!file_exists($positionsFile)) {
    die("ERROR: Positions file not found at $positionsFile\n");
}

echo "✓ Found current system PDF: $currentSystemPdf\n";
echo "✓ Found reference PDF: $referencePdf\n";
echo "✓ Found positions file: $positionsFile\n\n";

// Load current positions
$positions = json_decode(file_get_contents($positionsFile), true);
if (!$positions) {
    die("ERROR: Could not parse positions JSON\n");
}

echo "Analyzing PDFs...\n\n";

// Parse PDFs
$parser = new Parser();

try {
    $currentPdf = $parser->parseFile($currentSystemPdf);
    $referencePdf = $parser->parseFile($referencePdf);
    
    echo "Extracting text from current system PDF...\n";
    $currentText = $currentPdf->getText();
    $currentDetails = $currentPdf->getDetails();
    
    echo "Extracting text from reference PDF...\n";
    $referenceText = $referencePdf->getText();
    $referenceDetails = $referencePdf->getDetails();
    
    echo "\nCurrent System PDF:\n";
    echo "  Pages: " . (count($currentPdf->getPages()) ?? 'unknown') . "\n";
    echo "  Text length: " . strlen($currentText) . " chars\n";
    
    echo "\nReference PDF:\n";
    echo "  Pages: " . (count($referencePdf->getPages()) ?? 'unknown') . "\n";
    echo "  Text length: " . strlen($referenceText) . " chars\n";
    
    echo "\n" . str_repeat("=", 80) . "\n";
    echo "TEXT COMPARISON\n";
    echo str_repeat("=", 80) . "\n\n";
    
    // Test data for comparison
    $testValues = [
        'attorney_name' => 'John Michael Smith, Esq.',
        'attorney_bar_number' => '123456',
        'attorney_firm' => 'Smith & Associates Family Law',
        'attorney_address' => '1234 Legal Plaza, Suite 500',
        'case_number' => 'FL-2024-001234',
        'petitioner_name' => 'Sarah Elizabeth Johnson',
        'respondent_name' => 'Michael David Johnson',
        'marriage_date' => '06/15/2010',
    ];
    
    echo "Checking if test values appear in both PDFs:\n\n";
    
    $results = [];
    foreach ($testValues as $field => $value) {
        $inCurrent = stripos($currentText, $value) !== false;
        $inReference = stripos($referenceText, $value) !== false;
        
        $status = '';
        if ($inCurrent && $inReference) {
            $status = '✓ Both';
        } elseif ($inCurrent && !$inReference) {
            $status = '⚠ Current only';
        } elseif (!$inCurrent && $inReference) {
            $status = '⚠ Reference only';
        } else {
            $status = '✗ Neither';
        }
        
        $results[$field] = [
            'value' => $value,
            'in_current' => $inCurrent,
            'in_reference' => $inReference,
            'status' => $status
        ];
        
        printf("  %-30s %s\n", $field . ':', $status);
    }
    
    echo "\n" . str_repeat("=", 80) . "\n";
    echo "NEXT STEPS\n";
    echo str_repeat("=", 80) . "\n\n";
    
    echo "To manually measure and adjust positions:\n\n";
    echo "1. Open both PDFs side by side:\n";
    echo "   - Current: $currentSystemPdf\n";
    echo "   - Reference: $referencePdf\n\n";
    
    echo "2. Use Adobe Acrobat's measurement tool:\n";
    echo "   - Tools → Measure → Distance Tool\n";
    echo "   - Set units to Points or Millimeters\n";
    echo "   - Measure from page edge to text position\n\n";
    
    echo "3. For each misaligned field:\n";
    echo "   - Measure the difference in position\n";
    echo "   - Update data/t_fl100_gc120_positions.json\n";
    echo "   - Adjust x/y values (in millimeters)\n\n";
    
    echo "4. Regenerate and test:\n";
    echo "   php generate_test_fl100.php\n";
    echo "   php compare_fl100_pdfs.php\n\n";
    
    // Create visual comparison script
    echo "5. Or use the visual comparison tool:\n";
    echo "   - Open position-editor.html in browser\n";
    echo "   - Load fl100_draft_clio_reference.pdf as background\n";
    echo "   - Adjust field positions visually\n";
    echo "   - Export updated positions\n\n";
    
    // Save results
    $resultsFile = __DIR__ . '/output/comparison_results.json';
    file_put_contents($resultsFile, json_encode([
        'timestamp' => date('Y-m-d H:i:s'),
        'current_pdf' => $currentSystemPdf,
        'reference_pdf' => $referencePdf,
        'field_comparison' => $results,
        'current_text_sample' => substr($currentText, 0, 500),
        'reference_text_sample' => substr($referenceText, 0, 500)
    ], JSON_PRETTY_PRINT));
    
    echo "Results saved to: $resultsFile\n\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

