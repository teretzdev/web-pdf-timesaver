<?php
/**
 * Regenerate positions for FL-100 - Browser accessible
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/lib/pdf_field_extractor.php';

$templateId = 't_fl100_gc120';
$pdfFile = __DIR__ . '/../uploads/fl100.pdf';

if (!file_exists($pdfFile)) {
    die('<h1>Error</h1><p>FL-100 PDF not found at: ' . htmlspecialchars($pdfFile) . '</p>');
}

// Delete old positions file
$oldPositionsFile = __DIR__ . '/../data/' . $templateId . '_positions.json';
if (file_exists($oldPositionsFile)) {
    unlink($oldPositionsFile);
    echo '<p>✅ Deleted old positions file</p>';
}

// Copy PDF to expected location
$targetFile = __DIR__ . '/../uploads/' . $templateId . '.pdf';
copy($pdfFile, $targetFile);
echo '<p>✅ Copied PDF to ' . htmlspecialchars(basename($targetFile)) . '</p>';

// Extract positions
try {
    $extractor = new \WebPdfTimeSaver\Mvp\PdfFieldExtractor();
    $result = $extractor->extractAndGenerateBackgrounds(
        $targetFile,
        $templateId,
        __DIR__ . '/../uploads'
    );
    
    $fields = $result['fields'] ?? [];
    $backgrounds = $result['backgrounds'] ?? [];
    $positionFile = $result['positionFile'] ?? null;
    
    echo '<h1>✅ Position Extraction Complete!</h1>';
    echo '<p><strong>Fields extracted:</strong> ' . count($fields) . '</p>';
    echo '<p><strong>Background images:</strong> ' . count($backgrounds) . '</p>';
    
    if ($positionFile) {
        echo '<p><strong>Position file:</strong> <code>' . htmlspecialchars($positionFile) . '</code></p>';
    }
    
    if (!empty($fields)) {
        echo '<h2>Extracted Fields:</h2>';
        echo '<table border="1" cellpadding="5" style="border-collapse: collapse;">';
        echo '<tr><th>Field Name</th><th>Type</th><th>Page</th><th>Position (X, Y)</th><th>Size (W × H)</th></tr>';
        foreach ($fields as $fieldName => $fieldData) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($fieldName) . '</td>';
            echo '<td>' . htmlspecialchars($fieldData['type'] ?? 'text') . '</td>';
            echo '<td>' . htmlspecialchars((string)($fieldData['page'] ?? 1)) . '</td>';
            echo '<td>(' . number_format($fieldData['x'] ?? 0, 1) . ', ' . number_format($fieldData['y'] ?? 0, 1) . ')</td>';
            echo '<td>' . number_format($fieldData['width'] ?? 0, 1) . ' × ' . number_format($fieldData['height'] ?? 0, 1) . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
    
    if (!empty($backgrounds)) {
        echo '<h2>Background Images Generated:</h2>';
        echo '<ul>';
        foreach ($backgrounds as $page => $bgFile) {
            echo '<li>Page ' . $page . ': <code>' . htmlspecialchars(basename($bgFile)) . '</code></li>';
        }
        echo '</ul>';
    }
    
    echo '<p><a href="verify-positions-simple.php?action=generate&template=' . urlencode($templateId) . '">Test the new positions</a></p>';
    
} catch (\Exception $e) {
    echo '<h1>❌ Error</h1>';
    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
}
