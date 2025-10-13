<?php
/**
 * Generate a test PDF using the hybrid auto-fill method RIGHT NOW
 */

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/mvp/lib/pdf_field_extractor.php';
require_once __DIR__ . '/mvp/lib/pdf_form_filler.php';
require_once __DIR__ . '/mvp/lib/logger.php';
require_once __DIR__ . '/mvp/templates/registry.php';
require_once __DIR__ . '/mvp/lib/fl100_test_data_generator.php';

use WebPdfTimeSaver\Mvp\PdfFieldExtractor;
use WebPdfTimeSaver\Mvp\PdfFormFiller;
use WebPdfTimeSaver\Mvp\Logger;
use WebPdfTimeSaver\Mvp\TemplateRegistry;

echo "═══════════════════════════════════════════════════════════════\n";
echo "  🚀 HYBRID AUTO-FILL PDF GENERATOR\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$pdfFile = __DIR__ . '/uploads/fl100.pdf';
$templateId = 't_fl100_gc120';

if (!file_exists($pdfFile)) {
    die("❌ Error: FL-100 PDF not found at: $pdfFile\n");
}

echo "✓ Found FL-100 PDF\n";

// Step 1: Extract fields and generate backgrounds
echo "\n📋 Step 1: Extracting field positions and generating backgrounds...\n";
echo str_repeat("-", 63) . "\n";

$extractor = new PdfFieldExtractor();
$result = $extractor->extractAndGenerateBackgrounds(
    $pdfFile,
    $templateId,
    __DIR__ . '/uploads'
);

$fields = $result['fields'];
$backgrounds = $result['backgrounds'];
$positionFile = $result['positionFile'];

if (!empty($fields)) {
    echo "✓ Extracted " . count($fields) . " fields\n";
    echo "\nField preview:\n";
    $count = 0;
    foreach ($fields as $name => $data) {
        if ($count++ < 5) {
            echo sprintf("  • %-30s [%s] Page %d\n", $name, $data['type'], $data['page']);
        }
    }
    if (count($fields) > 5) {
        echo "  ... and " . (count($fields) - 5) . " more fields\n";
    }
} else {
    echo "⚠ No fields extracted (PDF may be encrypted)\n";
}

if (!empty($backgrounds)) {
    echo "\n✓ Generated " . count($backgrounds) . " background images:\n";
    foreach ($backgrounds as $page => $file) {
        echo "  • Page $page: " . basename($file) . "\n";
    }
}

if ($positionFile) {
    echo "\n✓ Position file: " . basename($positionFile) . "\n";
}

// Step 2: Load template and generate test data
echo "\n📝 Step 2: Preparing test data...\n";
echo str_repeat("-", 63) . "\n";

$templates = TemplateRegistry::load();
$template = $templates[$templateId] ?? null;

if (!$template) {
    die("❌ Error: Template '$templateId' not found\n");
}

echo "✓ Loaded template: " . ($template['code'] ?? '') . " — " . ($template['name'] ?? '') . "\n";

$testData = \WebPdfTimeSaver\Mvp\FL100TestDataGenerator::generateCompleteTestData();
echo "✓ Generated test data with " . count($testData) . " fields\n";

// Step 3: Generate PDF
echo "\n🎨 Step 3: Generating filled PDF...\n";
echo str_repeat("-", 63) . "\n";

$logger = new Logger();
$filler = new PdfFormFiller(__DIR__ . '/output', __DIR__ . '/uploads', $logger);
$filler->setContext(['test' => true, 'method' => 'hybrid-autofill', 'timestamp' => date('Y-m-d H:i:s')]);

try {
    // Use the positioned method with extracted positions
    $generatedPdf = $filler->fillPdfFormWithPositions($template, $testData, $templateId);
    
    echo "✓ PDF generated successfully!\n";
    echo "\n📄 Output Details:\n";
    echo "  • Filename: " . $generatedPdf['filename'] . "\n";
    echo "  • Path: " . $generatedPdf['path'] . "\n";
    echo "  • Fields placed: " . ($generatedPdf['fields_placed'] ?? 'N/A') . "\n";
    echo "  • Pages: " . ($generatedPdf['pages'] ?? 'N/A') . "\n";
    
    // Step 4: Open the PDF
    echo "\n🔍 Step 4: Opening PDF...\n";
    echo str_repeat("-", 63) . "\n";
    
    $pdfPath = $generatedPdf['path'];
    
    if (file_exists($pdfPath)) {
        // Open PDF in default viewer (Windows)
        if (PHP_OS_FAMILY === 'Windows') {
            $cmd = 'start "" "' . $pdfPath . '"';
            exec($cmd);
            echo "✓ Opening PDF in default viewer...\n";
        } else {
            echo "PDF saved to: $pdfPath\n";
            echo "Please open it manually.\n";
        }
    } else {
        echo "❌ Error: Generated PDF not found at: $pdfPath\n";
    }
    
    echo "\n═══════════════════════════════════════════════════════════════\n";
    echo "  ✅ SUCCESS! PDF Generated with Hybrid Auto-Fill Method\n";
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "\nFile location:\n";
    echo "  " . $pdfPath . "\n\n";
    
} catch (\Exception $e) {
    echo "❌ Error generating PDF: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

