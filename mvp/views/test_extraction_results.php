<?php
/**
 * Test page that simulates a successful extraction result
 * Shows what the Universal Processor should display when fields are found
 */

// Simulate the extraction result that should be returned
$templateId = 'auto_1763401469202';
$pdfPath = __DIR__ . '/../../uploads/' . $templateId . '.pdf';

// Load the actual extraction details
$dataDir = dirname(__DIR__, 2) . '/data';
$detailsFile = $dataDir . '/' . $templateId . '_extraction_details.json';

$fields = [];
$ensembleMetadata = null;

if (file_exists($detailsFile)) {
    $detailsData = json_decode(file_get_contents($detailsFile), true);
    if ($detailsData && !empty($detailsData['fields'])) {
        // Convert to keyed format
        foreach ($detailsData['fields'] as $field) {
            $key = $field['canonicalName'] ?? $field['name'] ?? null;
            if ($key) {
                $fields[$key] = $field;
            }
        }
        $ensembleMetadata = [
            'method' => $detailsData['method'] ?? 'unknown',
            'methodsUsed' => $detailsData['methodsUsed'] ?? [],
            'fieldsPerMethod' => $detailsData['fieldsPerMethod'] ?? [],
            'pageCount' => $detailsData['pageCount'] ?? 0
        ];
    }
}

// Get background images
$backgrounds = [];
for ($i = 1; $i <= 2; $i++) {
    $bgPath = __DIR__ . '/../../uploads/' . $templateId . '_page' . $i . '_background.png';
    if (file_exists($bgPath)) {
        $backgrounds[$i] = '/Web-PDFTimeSaver/uploads/' . $templateId . '_page' . $i . '_background.png';
    }
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Universal Processor - FL-110 Results</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { background: #d4edda; border: 2px solid #28a745; padding: 20px; border-radius: 5px; margin: 20px 0; }
        .field-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 15px; margin: 20px 0; }
        .field-card { background: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; border-radius: 5px; }
        .field-name { font-weight: bold; color: #007bff; font-size: 16px; margin-bottom: 8px; }
        .field-details { color: #6c757d; font-size: 14px; }
        .preview-container { margin: 30px 0; }
        .preview-image { max-width: 100%; border: 2px solid #dee2e6; border-radius: 5px; }
        .field-overlay { position: absolute; border: 2px solid #28a745; background: rgba(40, 167, 69, 0.2); pointer-events: none; }
        .preview-wrapper { position: relative; display: inline-block; }
        h1 { color: #333; }
        h2 { color: #007bff; margin-top: 30px; }
        .stats { display: flex; gap: 30px; margin: 20px 0; }
        .stat { text-align: center; }
        .stat-value { font-size: 32px; font-weight: bold; color: #28a745; }
        .stat-label { color: #6c757d; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🤖 Universal PDF Form Processor - Results</h1>
        
        <?php if (!empty($fields)): ?>
            <div class="success">
                <h2>✅ SUCCESS! Fields Detected</h2>
                <p><strong>Template ID:</strong> <?= htmlspecialchars($templateId) ?></p>
                <p><strong>PDF:</strong> <?= htmlspecialchars(basename($pdfPath)) ?></p>
                <?php if ($ensembleMetadata): ?>
                    <p><strong>Extraction Method:</strong> <?= htmlspecialchars($ensembleMetadata['method']) ?></p>
                    <p><strong>Methods Used:</strong> <?= htmlspecialchars(implode(', ', $ensembleMetadata['methodsUsed'])) ?></p>
                <?php endif; ?>
            </div>
            
            <div class="stats">
                <div class="stat">
                    <div class="stat-value"><?= count($fields) ?></div>
                    <div class="stat-label">Fields Detected</div>
                </div>
                <div class="stat">
                    <div class="stat-value"><?= count($backgrounds) ?></div>
                    <div class="stat-label">Pages</div>
                </div>
                <div class="stat">
                    <div class="stat-value"><?= count($ensembleMetadata['methodsUsed'] ?? []) ?></div>
                    <div class="stat-label">Methods Used</div>
                </div>
            </div>
            
            <h2>Detected Fields</h2>
            <div class="field-list">
                <?php foreach ($fields as $key => $field): ?>
                    <div class="field-card">
                        <div class="field-name"><?= htmlspecialchars($key) ?></div>
                        <div class="field-details">
                            <strong>Type:</strong> <?= htmlspecialchars($field['type'] ?? 'unknown') ?><br>
                            <strong>Page:</strong> <?= htmlspecialchars($field['page'] ?? 'N/A') ?><br>
                            <strong>Position:</strong> (<?= htmlspecialchars($field['x'] ?? '0') ?>, <?= htmlspecialchars($field['y'] ?? '0') ?>) mm<br>
                            <strong>Size:</strong> <?= htmlspecialchars($field['width'] ?? '0') ?> × <?= htmlspecialchars($field['height'] ?? '0') ?> mm<br>
                            <?php if (isset($field['confidence'])): ?>
                                <strong>Confidence:</strong> <?= round($field['confidence'] * 100) ?>%<br>
                            <?php endif; ?>
                            <?php if (isset($field['methodSource'])): ?>
                                <strong>Detected by:</strong> <?= htmlspecialchars($field['methodSource']) ?><br>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if (!empty($backgrounds)): ?>
                <h2>PDF Preview with Field Positions</h2>
                <?php foreach ($backgrounds as $pageNum => $bgPath): ?>
                    <div class="preview-container">
                        <h3>Page <?= $pageNum ?></h3>
                        <div class="preview-wrapper">
                            <img src="<?= htmlspecialchars($bgPath) ?>" alt="Page <?= $pageNum ?>" class="preview-image" style="max-width: 850px;">
                            <?php
                            // Draw field overlays
                            foreach ($fields as $key => $field) {
                                if (($field['page'] ?? 1) == $pageNum) {
                                    // Convert mm to pixels at 200 DPI with 50% scale
                                    $MM_TO_PX_AT_DPI = 200 / 25.4; // 200 DPI
                                    $PREVIEW_SCALE = 0.5;
                                    
                                    $x = ($field['x'] ?? 0) * $MM_TO_PX_AT_DPI * $PREVIEW_SCALE;
                                    $y = ($field['y'] ?? 0) * $MM_TO_PX_AT_DPI * $PREVIEW_SCALE;
                                    $w = max(10, ($field['width'] ?? 0) * $MM_TO_PX_AT_DPI * $PREVIEW_SCALE);
                                    $h = max(10, ($field['height'] ?? 0) * $MM_TO_PX_AT_DPI * $PREVIEW_SCALE);
                                    
                                    // PDF uses bottom-left origin, convert to top-left for display
                                    $imgHeight = 1100 * $PREVIEW_SCALE; // Approximate image height
                                    $y = $imgHeight - $y - $h;
                                    
                                    echo '<div class="field-overlay" style="left: ' . $x . 'px; top: ' . $y . 'px; width: ' . $w . 'px; height: ' . $h . 'px;" title="' . htmlspecialchars($key) . '"></div>';
                                }
                            }
                            ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
        <?php else: ?>
            <div style="background: #f8d7da; border: 2px solid #dc3545; padding: 20px; border-radius: 5px;">
                <h2>❌ No Fields Found</h2>
                <p>No fields were detected in the extraction details file.</p>
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #dee2e6;">
            <p><a href="?route=universal-processor">← Back to Universal Processor</a></p>
            <p><a href="?route=debug-extraction">🔍 View Debug Page</a></p>
        </div>
    </div>
</body>
</html>




