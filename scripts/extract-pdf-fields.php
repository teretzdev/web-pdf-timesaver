<?php
/**
 * Extract form field positions from a fillable PDF
 * Usage: php scripts/extract-pdf-fields.php <pdf-file> <template-id>
 * Example: php scripts/extract-pdf-fields.php uploads/fl100.pdf t_fl100_gc120
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../mvp/lib/pdf_field_extractor.php';

use WebPdfTimeSaver\Mvp\PdfFieldExtractor;

// Check arguments
if ($argc < 3) {
    echo "Usage: php scripts/extract-pdf-fields.php <pdf-file> <template-id>\n";
    echo "Example: php scripts/extract-pdf-fields.php uploads/fl100.pdf t_fl100_gc120\n";
    exit(1);
}

$pdfFile = $argv[1];
$templateId = $argv[2];

if (!file_exists($pdfFile)) {
    echo "Error: PDF file not found: $pdfFile\n";
    exit(1);
}

echo "Extracting fields from: $pdfFile\n";
echo "Template ID: $templateId\n";
echo str_repeat("-", 60) . "\n";

$extractor = new PdfFieldExtractor();

try {
    echo "Using hybrid approach: extracting fields + generating background images...\n\n";
    
    // Use hybrid approach: extract fields AND generate backgrounds
    $result = $extractor->extractAndGenerateBackgrounds(
        $pdfFile,
        $templateId,
        __DIR__ . '/../uploads'
    );
    
    $fields = $result['fields'];
    $backgrounds = $result['backgrounds'];
    $positionFile = $result['positionFile'];
    
    if (empty($fields)) {
        echo "Warning: No fields extracted from PDF\n";
        if (!empty($backgrounds)) {
            echo "✓ Generated " . count($backgrounds) . " background images\n";
            echo "  Fields could not be extracted (PDF may be encrypted),\n";
            echo "  but backgrounds are ready for manual positioning.\n";
        } else {
            echo "This could mean:\n";
            echo "  - PDF is encrypted/password protected (use unlock-pdf.php first)\n";
            echo "  - PDF has no fillable form fields\n";
            echo "  - pdftk is not installed or not in PATH\n";
        }
        exit(1);
    }
    
    echo "Found " . count($fields) . " form fields:\n\n";
    
    // Display fields
    foreach ($fields as $fieldName => $fieldData) {
        echo sprintf(
            "  %-30s  Type: %-10s  Page: %d  Pos: (%.1f, %.1f)  Size: %.1f x %.1f\n",
            $fieldName,
            $fieldData['type'],
            $fieldData['page'],
            $fieldData['x'],
            $fieldData['y'],
            $fieldData['width'],
            $fieldData['height']
        );
    }
    
    echo "\n" . str_repeat("-", 60) . "\n";
    
    // Show results
    if ($positionFile) {
        echo "✓ Position file generated: $positionFile\n";
    }
    
    if (!empty($backgrounds)) {
        echo "✓ Generated " . count($backgrounds) . " background images:\n";
        foreach ($backgrounds as $page => $bgFile) {
            echo "  Page $page: " . basename($bgFile) . "\n";
        }
    }
    
    echo "\n✓ Template ready! You can now:\n";
    echo "  1. Create a new document with template '$templateId'\n";
    echo "  2. Fill out the form\n";
    echo "  3. Generate PDF with auto-positioned fields\n";
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "\nTroubleshooting:\n";
    echo "  1. Make sure pdftk is installed and in your PATH\n";
    echo "  2. Check if the PDF is password protected (needs to be unlocked first)\n";
    echo "  3. Verify the PDF contains fillable form fields\n";
    exit(1);
}

