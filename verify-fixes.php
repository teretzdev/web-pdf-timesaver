<?php
echo "=== VERIFYING TEST FIXES ===\n\n";

// Check background images
echo "1. Background Images:\n";
$bg1 = 'C:\xampp\htdocs\Web-PDFTimeSaver\uploads\test_w9_page1_background.png';
$bg2 = 'C:\xampp\htdocs\Web-PDFTimeSaver\uploads\test_fl100_page1_background.png';

if (file_exists($bg1)) {
    echo "  ✅ test_w9_page1_background.png exists\n";
} else {
    echo "  ❌ test_w9_page1_background.png missing\n";
}

if (file_exists($bg2)) {
    echo "  ✅ test_fl100_page1_background.png exists\n";
} else {
    echo "  ❌ test_fl100_page1_background.png missing\n";
}

// Check position file
echo "\n2. Position File:\n";
$posFile = 'C:\xampp\htdocs\Web-PDFTimeSaver\data\t_fl100_gc120_positions.json';
if (file_exists($posFile)) {
    echo "  ✅ t_fl100_gc120_positions.json exists\n";
    $data = json_decode(file_get_contents($posFile), true);
    echo "  ℹ️  Contains " . count($data) . " fields\n";
} else {
    echo "  ❌ t_fl100_gc120_positions.json missing\n";
}

// Check visual editor
echo "\n3. Visual Editor:\n";
$editorFile = 'C:\xampp\htdocs\Web-PDFTimeSaver\mvp\visual-field-editor.php';
if (file_exists($editorFile)) {
    $content = file_get_contents($editorFile);
    if (strpos($content, 'extractAndGenerateBackgrounds') !== false) {
        echo "  ✅ extractAndGenerateBackgrounds found\n";
    } else {
        echo "  ❌ extractAndGenerateBackgrounds missing\n";
    }
    
    if (strpos($content, 'PdfFieldExtractor') !== false) {
        echo "  ✅ PdfFieldExtractor found\n";
    } else {
        echo "  ❌ PdfFieldExtractor missing\n";
    }
} else {
    echo "  ❌ Visual editor file missing\n";
}

echo "\n=== VERIFICATION COMPLETE ===\n";
echo "Refresh the browser test suite to see updated results\n";
?>
