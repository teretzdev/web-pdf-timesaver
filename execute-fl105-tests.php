<?php
/**
 * Execute FL-105 tests and generate filled PDF
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/mvp/lib/fl105_test_data_generator.php';
require_once __DIR__ . '/mvp/lib/pdf_field_extractor.php';
require_once __DIR__ . '/mvp/lib/pdf_form_filler.php';

echo "=== EXECUTING FL-105 TESTS ===\n\n";

// Step 1: Load test data
echo "1. Loading FL-105 test data...\n";
$testData = \WebPdfTimeSaver\Mvp\FL105TestDataGenerator::generateCompleteTestData();
echo "   ✅ Loaded " . count($testData) . " test fields\n";
echo "   Sample data:\n";
echo "   - Petitioner: " . $testData['petitioner'] . "\n";
echo "   - Respondent: " . $testData['respondent'] . "\n";
echo "   - Case Number: " . $testData['case_number'] . "\n";
echo "   - Child 1: " . $testData['child_1_name'] . " (DOB: " . $testData['child_1_birthdate'] . ")\n";
echo "   - Child 2: " . $testData['child_2_name'] . " (DOB: " . $testData['child_2_birthdate'] . ")\n\n";

// Step 2: Extract field positions from FL-105
echo "2. Extracting field positions from FL-105...\n";
$pdfFile = __DIR__ . '/uploads/fl105.pdf';

if (!file_exists($pdfFile)) {
    echo "   ❌ ERROR: FL-105 PDF not found at $pdfFile\n";
    exit(1);
}

$extractor = new \WebPdfTimeSaver\Mvp\PdfFieldExtractor();
try {
    $extractResult = $extractor->extractAndGenerateBackgrounds(
        $pdfFile,
        'fl105',
        __DIR__ . '/uploads'
    );
    
    echo "   ✅ Extracted " . count($extractResult['fields']) . " fields\n";
    echo "   ✅ Generated " . count($extractResult['backgrounds']) . " background images\n";
    echo "   ✅ Position file: " . ($extractResult['positionFile'] ?? 'Not saved') . "\n";
    
    if (!empty($extractResult['fields'])) {
        echo "   Sample extracted fields:\n";
        foreach (array_slice($extractResult['fields'], 0, 5) as $field) {
            echo "   - {$field['name']}: {$field['type']} at ({$field['x']}, {$field['y']})\n";
        }
    }
    
} catch (Exception $e) {
    echo "   ⚠️  Extraction failed: " . $e->getMessage() . "\n";
    echo "   ℹ️  This is expected for password-protected PDFs\n";
    echo "   ℹ️  Will use background overlay method instead\n";
}

echo "\n";

// Step 3: Generate filled PDF
echo "3. Generating filled FL-105 PDF...\n";

// Create output directory if needed
if (!is_dir(__DIR__ . '/output')) {
    mkdir(__DIR__ . '/output', 0755, true);
}

// Load templates
require_once __DIR__ . '/mvp/templates/registry.php';
$templates = \WebPdfTimeSaver\Mvp\TemplateRegistry::load();

// Create FL-105 template if it doesn't exist
$templateId = 't_fl105';
if (!isset($templates[$templateId])) {
    echo "   ℹ️  Creating FL-105 template in registry...\n";
    $templates[$templateId] = [
        'id' => $templateId,
        'name' => 'FL-105/GC-120 - Declaration Under UCCJEA',
        'pdfFile' => 'fl105.pdf',
        'pageCount' => 1,
        'panels' => [
            ['id' => 'case_info', 'label' => 'Case Information'],
            ['id' => 'children', 'label' => 'Children Information'],
            ['id' => 'residence', 'label' => 'Residence History'],
            ['id' => 'declaration', 'label' => 'Declaration']
        ]
    ];
}

$filler = new \WebPdfTimeSaver\Mvp\PdfFormFiller();
try {
    $result = $filler->fillPdfFormWithPositions(
        $templates[$templateId],
        $testData,
        $templateId
    );
    
    $outputFile = $result['path'] ?? $result['outputPath'] ?? null;
    
    if ($outputFile && file_exists($outputFile)) {
        $fileSize = filesize($outputFile);
        echo "   ✅ PDF generated successfully!\n";
        echo "   ✅ File: $outputFile\n";
        echo "   ✅ Size: " . number_format($fileSize) . " bytes\n";
        
        // Copy to XAMPP for web access
        $xamppOutput = 'C:\xampp\htdocs\Web-PDFTimeSaver\output\FL-105_filled_test.pdf';
        if (copy($outputFile, $xamppOutput)) {
            echo "   ✅ Copied to XAMPP: $xamppOutput\n";
            echo "   🌐 Access at: http://localhost/Web-PDFTimeSaver/output/FL-105_filled_test.pdf\n";
        }
    } else {
        echo "   ❌ Failed to generate PDF\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
    echo "   Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n";

// Step 4: Verify background images
echo "4. Verifying background images...\n";
$backgrounds = glob(__DIR__ . '/uploads/*fl105*_background.png');
if (!empty($backgrounds)) {
    echo "   ✅ Found " . count($backgrounds) . " background images:\n";
    foreach ($backgrounds as $bg) {
        $size = getimagesize($bg);
        echo "   - " . basename($bg) . " ({$size[0]}x{$size[1]})\n";
    }
} else {
    echo "   ⚠️  No FL-105 background images found\n";
}

echo "\n";

// Step 5: Verify position file
echo "5. Verifying position file...\n";
$posFile = __DIR__ . '/data/t_fl105_positions.json';
if (file_exists($posFile)) {
    $positions = json_decode(file_get_contents($posFile), true);
    echo "   ✅ Position file exists\n";
    echo "   ✅ Contains " . count($positions) . " field positions\n";
} else {
    echo "   ⚠️  Position file not found: $posFile\n";
    echo "   ℹ️  Will be auto-generated on first visual editor use\n";
}

echo "\n=== FL-105 TESTS COMPLETE ===\n";
echo "\nNext steps:\n";
echo "1. Open visual editor: http://localhost/Web-PDFTimeSaver/mvp/visual-field-editor.php?template=fl105\n";
echo "2. View demo page: http://localhost/Web-PDFTimeSaver/test-fl105-demo.php\n";
echo "3. View filled PDF: http://localhost/Web-PDFTimeSaver/output/FL-105_filled_test.pdf\n";
echo "4. Run test suite: http://localhost/Web-PDFTimeSaver/browser-test-suite.html\n";
?>
