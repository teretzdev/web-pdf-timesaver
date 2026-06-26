<?php
/**
 * Show Preview Working - Automatically displays the FL-110 PDF with field overlays
 * Uses the already uploaded PDF - NO USER INTERACTION REQUIRED
 */

// Use rescan template ID for extraction details if available, but PDF uses original ID
$originalTemplateId = 'auto_1763401469202';
$rescanTemplateId = 'auto_1763401469202_rescan';
$workspaceRoot = dirname(__DIR__, 2);

// Check if rescan exists - prefer rescan for extraction details
$rescanDetailsFile = $workspaceRoot . '/data/' . $rescanTemplateId . '_extraction_details.json';
$extractionTemplateId = file_exists($rescanDetailsFile) ? $rescanTemplateId : $originalTemplateId;
$templateId = $originalTemplateId; // PDF always uses original ID

// Check both workspace and XAMPP locations
$xamppRoot = dirname(__DIR__, 3); // Go up from mvp/views to htdocs, then Web-PDFTimeSaver

$pdfPath = null;
$dataDir = null;
$uploadsDir = null;

// Try XAMPP location first (where the PDF actually is)
if (file_exists($xamppRoot . '/uploads/' . $templateId . '.pdf')) {
    $pdfPath = $xamppRoot . '/uploads/' . $templateId . '.pdf';
    $dataDir = $xamppRoot . '/data';
    $uploadsDir = $xamppRoot . '/uploads';
} elseif (file_exists($workspaceRoot . '/uploads/' . $templateId . '.pdf')) {
    $pdfPath = $workspaceRoot . '/uploads/' . $templateId . '.pdf';
    $dataDir = $workspaceRoot . '/data';
    $uploadsDir = $workspaceRoot . '/uploads';
} else {
    // Try absolute XAMPP path
    $xamppAbsolute = 'C:/xampp/htdocs/Web-PDFTimeSaver';
    if (file_exists($xamppAbsolute . '/uploads/' . $templateId . '.pdf')) {
        $pdfPath = $xamppAbsolute . '/uploads/' . $templateId . '.pdf';
        $dataDir = $xamppAbsolute . '/data';
        $uploadsDir = $xamppAbsolute . '/uploads';
    }
}

// Load extraction details - check multiple locations, prefer rescan
$possibleDataDirs = [
    $dataDir,
    $workspaceRoot . '/data',
    'C:/Users/Shadow/Web-PDFTimeSaver/data',
    'C:/xampp/htdocs/Web-PDFTimeSaver/data'
];

$detailsFile = null;
// First try rescan
foreach ($possibleDataDirs as $dir) {
    $testFile = $dir . '/' . $extractionTemplateId . '_extraction_details.json';
    if (file_exists($testFile)) {
        $detailsFile = $testFile;
        break;
    }
}
// Fallback to original if rescan not found
if (!$detailsFile) {
    foreach ($possibleDataDirs as $dir) {
        $testFile = $dir . '/' . $originalTemplateId . '_extraction_details.json';
        if (file_exists($testFile)) {
            $detailsFile = $testFile;
            break;
        }
    }
}

$fields = [];
$ensembleMetadata = null;
$verificationResults = null;

if ($detailsFile && file_exists($detailsFile)) {
    $detailsData = json_decode(file_get_contents($detailsFile), true);
    if ($detailsData && !empty($detailsData['fields'])) {
        foreach ($detailsData['fields'] as $field) {
            $key = $field['canonicalName'] ?? $field['name'] ?? null;
            if ($key) {
                $fields[$key] = $field;
            }
        }
        $ensembleMetadata = [
            'method' => $detailsData['method'] ?? 'unknown',
            'methodsUsed' => $detailsData['methodsUsed'] ?? [],
            'fieldsPerMethod' => $detailsData['fieldsPerMethod'] ?? []
        ];
        
        // Run verification
        $PAGE_HEIGHT_MM = 279.4;
        $PAGE_WIDTH_MM = 215.9;
        $verificationResults = [
            'valid' => [],
            'suspicious' => [],
            'invalid' => []
        ];
        
        foreach ($fields as $key => $field) {
            $yTopLeft = $PAGE_HEIGHT_MM - $field['y'] - $field['height'];
            $xValid = $field['x'] >= 0 && $field['x'] <= $PAGE_WIDTH_MM;
            $yValid = $yTopLeft >= 0 && $yTopLeft <= $PAGE_HEIGHT_MM;
            $withinBounds = $xValid && $yValid;
            
            $nearTop = $yTopLeft < 20;
            $nearBottom = $yTopLeft > ($PAGE_HEIGHT_MM - 20);
            $nearLeft = $field['x'] < 20;
            $nearRight = $field['x'] > ($PAGE_WIDTH_MM - 20);
            $isSuspicious = $nearTop || $nearBottom || $nearLeft || $nearRight;
            $lowConfidence = ($field['confidence'] ?? 0) < 0.7;
            
            // Flag checkboxes from OCR as false positives (they're usually visual symbols, not form fields)
            $isFalsePositive = ($field['type'] ?? '') === 'checkbox' && 
                              ($field['methodSource'] ?? '') === 'ocr-field-detection';
            
            // Flag very small fields as suspicious (likely symbols, not form fields)
            $isVerySmall = ($field['width'] ?? 0) < 10 && ($field['height'] ?? 0) < 10;
            
            $analysis = [
                'name' => $key,
                'withinBounds' => $withinBounds,
                'isSuspicious' => $isSuspicious,
                'lowConfidence' => $lowConfidence,
                'location' => []
            ];
            
            if ($nearTop) $analysis['location'][] = 'near top';
            if ($nearBottom) $analysis['location'][] = 'near bottom';
            if ($nearLeft) $analysis['location'][] = 'near left';
            if ($nearRight) $analysis['location'][] = 'near right';
            
            if (!$withinBounds) {
                $verificationResults['invalid'][] = $analysis;
            } elseif ($isFalsePositive || $isSuspicious || $lowConfidence || $isVerySmall) {
                $verificationResults['suspicious'][] = $analysis;
                if ($isFalsePositive) {
                    $analysis['reason'] = 'False positive: OCR detected visual symbol, not actual checkbox form field';
                } elseif ($isVerySmall) {
                    $analysis['reason'] = 'Very small size: likely a symbol or decorative element, not a form field';
                }
            } else {
                $verificationResults['valid'][] = $analysis;
            }
        }
    }
}

// Get background images
$backgrounds = [];
if ($uploadsDir) {
    for ($i = 1; $i <= 2; $i++) {
        $bgPath = $uploadsDir . '/' . $templateId . '_page' . $i . '_background.png';
        if (file_exists($bgPath)) {
            $backgrounds[$i] = '/Web-PDFTimeSaver/uploads/' . $templateId . '_page' . $i . '_background.png';
        }
    }
    
    // If backgrounds don't exist but PDF does, generate them
    if (empty($backgrounds) && $pdfPath && file_exists($pdfPath)) {
        require_once __DIR__ . '/../lib/pdf_field_extractor.php';
        
        try {
            $extractor = new \WebPdfTimeSaver\Mvp\PdfFieldExtractor();
            $result = $extractor->extractAndGenerateBackgrounds($pdfPath, $templateId, $uploadsDir);
            $generatedBackgrounds = $result['backgrounds'] ?? [];
            
            // Reload backgrounds
            for ($i = 1; $i <= 2; $i++) {
                $bgPath = $uploadsDir . '/' . $templateId . '_page' . $i . '_background.png';
                if (file_exists($bgPath)) {
                    $backgrounds[$i] = '/Web-PDFTimeSaver/uploads/' . $templateId . '_page' . $i . '_background.png';
                }
            }
        } catch (Exception $e) {
            // Silently fail - will show error below
        }
    }
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Preview Working - FL-110 Fields</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { background: #d4edda; border: 2px solid #28a745; padding: 20px; border-radius: 5px; margin: 20px 0; }
        h1 { color: #333; }
        h2 { color: #007bff; margin-top: 30px; }
        
        /* Preview styles matching universal_processor.php */
        .tech-preview {
            margin-top: 30px;
            padding: 24px;
            background: #f8f9fa;
            border-radius: 12px;
            border: 1px solid #e1e4e8;
        }
        
        .tech-preview-pages {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }
        
        .tech-preview-page-title {
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }
        
        .tech-preview-canvas {
            position: relative;
            display: inline-block;
            background: white;
            padding: 10px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
            max-width: 100%;
            overflow: auto;
        }
        
        .tech-preview-bg {
            display: block;
            width: 850px;
            max-width: 100%;
            height: auto;
        }
        
        .tech-preview-overlay-layer {
            position: absolute;
            top: 10px;
            left: 10px;
            pointer-events: none;
            width: 850px;
            height: 1100px;
        }
        
        .tech-preview-field {
            position: absolute;
            border: 2px solid #28a745;
            background: rgba(40, 167, 69, 0.15);
            border-radius: 3px;
            box-sizing: border-box;
        }
        
        .tech-preview-field-label {
            position: absolute;
            top: -18px;
            left: 0;
            background: #28a745;
            color: white;
            padding: 2px 6px;
            font-size: 10px;
            border-radius: 3px;
            white-space: nowrap;
            font-weight: 600;
            font-family: Arial, "Helvetica Neue", Helvetica, "Segoe UI", sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            text-rendering: optimizeLegibility;
        }
        
        .field-list { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 15px; margin: 20px 0; }
        .field-card { background: #f8f9fa; border: 1px solid #dee2e6; padding: 15px; border-radius: 5px; }
        .field-name { font-weight: bold; color: #007bff; font-size: 16px; margin-bottom: 8px; }
        .field-details { color: #6c757d; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>✅ Preview Working - FL-110 PDF with Field Overlays</h1>
        
        <?php if (!$pdfPath): ?>
            <div style="background: #f8d7da; border: 2px solid #dc3545; padding: 20px; border-radius: 5px;">
                <h2>❌ PDF Not Found</h2>
                <p>Could not locate the PDF file. Checked:</p>
                <ul>
                    <li><?= htmlspecialchars($workspaceRoot . '/uploads/' . $templateId . '.pdf') ?></li>
                    <li><?= htmlspecialchars($xamppRoot . '/uploads/' . $templateId . '.pdf') ?></li>
                    <li>C:/xampp/htdocs/Web-PDFTimeSaver/uploads/<?= htmlspecialchars($templateId) ?>.pdf</li>
                </ul>
            </div>
        <?php else: ?>
        <div class="success">
            <h2>Visual Elements Detected: <?= count($fields) ?></h2>
            <p><strong>⚠️ Important:</strong> FL-110 is a static PDF with no fillable form fields. These detections are from OCR/visual methods and may include false positives:</p>
            <ul style="margin: 10px 0; padding-left: 20px;">
                <li>Text fields may be labels or static text</li>
                <li>Checkboxes may be visual symbols (☐, □) or decorative elements, not actual form fields</li>
                <li>Positions may not align with actual form fields since there are none</li>
            </ul>
            <p><strong>Template ID:</strong> <?= htmlspecialchars($templateId) ?></p>
            <p><strong>PDF Path:</strong> <?= htmlspecialchars($pdfPath) ?></p>
            <?php if ($ensembleMetadata): ?>
                <p><strong>Method:</strong> <?= htmlspecialchars($ensembleMetadata['method']) ?></p>
                <p><strong>Methods Used:</strong> <?= htmlspecialchars(implode(', ', $ensembleMetadata['methodsUsed'])) ?></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($fields)): ?>
            <?php if ($verificationResults): ?>
                <div style="background: #fff3cd; border: 2px solid #ffc107; padding: 20px; border-radius: 5px; margin: 20px 0;">
                    <h2>🔍 Field Verification Results</h2>
                    <p><strong>Total detections:</strong> <?= count($fields) ?></p>
                    <p><strong>⚠️ All are false positives:</strong> FL-110 has no fillable form fields</p>
                    <p><strong>✅ Valid form fields:</strong> 0</p>
                    <p><strong>⚠️ False positives:</strong> <?= count($verificationResults['suspicious']) + count($verificationResults['valid']) ?></p>
                    <p><strong>❌ Invalid:</strong> <?= count($verificationResults['invalid']) ?></p>
                    
                    <?php 
                    $allSuspicious = array_merge($verificationResults['suspicious'] ?? [], $verificationResults['valid'] ?? []);
                    if (!empty($allSuspicious)): ?>
                        <p style="margin-top: 10px;"><strong>⚠️ All detected "fields" are false positives (visual elements, not form fields):</strong></p>
                        <ul>
                            <?php foreach ($allSuspicious as $susp): ?>
                                <li>
                                    <strong><?= htmlspecialchars($susp['name']) ?></strong>:
                                    <?php if (!empty($susp['reason'])): ?>
                                        <?= htmlspecialchars($susp['reason']) ?>
                                    <?php else: ?>
                                        Visual detection - not an actual form field
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <p style="margin-top: 10px; color: #856404; font-weight: bold;">
                            ⚠️ FL-110 is a static PDF with no fillable form fields. These detections are visual elements (text labels, symbols) incorrectly identified as form fields.
                        </p>
                    <?php endif; ?>
                    
                    <?php if (!empty($verificationResults['invalid'])): ?>
                        <p style="margin-top: 10px;"><strong>❌ Invalid fields (outside page bounds):</strong></p>
                        <ul>
                            <?php foreach ($verificationResults['invalid'] as $inv): ?>
                                <li><?= htmlspecialchars($inv['name']) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <h2>Detected Fields</h2>
            <div class="field-list">
                <?php foreach ($fields as $key => $field): 
                    $isValid = $verificationResults && in_array($key, array_column($verificationResults['valid'], 'name'));
                    $isSuspicious = $verificationResults && in_array($key, array_column($verificationResults['suspicious'], 'name'));
                    $isInvalid = $verificationResults && in_array($key, array_column($verificationResults['invalid'], 'name'));
                    $statusClass = $isInvalid ? 'invalid' : ($isSuspicious ? 'suspicious' : ($isValid ? 'valid' : ''));
                ?>
                    <div class="field-card <?= $statusClass ?>" style="<?= $isInvalid ? 'border-color: #dc3545;' : ($isSuspicious ? 'border-color: #ffc107;' : ($isValid ? 'border-color: #28a745;' : '')) ?>">
                        <div class="field-name">
                            <?= htmlspecialchars($key) ?>
                            <?php if ($isValid): ?>
                                <span style="color: #28a745; font-size: 12px;">✅ Valid</span>
                            <?php elseif ($isSuspicious): ?>
                                <span style="color: #ffc107; font-size: 12px;">⚠️ Suspicious</span>
                            <?php elseif ($isInvalid): ?>
                                <span style="color: #dc3545; font-size: 12px;">❌ Invalid</span>
                            <?php endif; ?>
                        </div>
                        <div class="field-details">
                            <strong>Type:</strong> <?= htmlspecialchars($field['type'] ?? 'unknown') ?><br>
                            <strong>Page:</strong> <?= htmlspecialchars($field['page'] ?? 'N/A') ?><br>
                            <strong>Position:</strong> (<?= htmlspecialchars($field['x'] ?? '0') ?>, <?= htmlspecialchars($field['y'] ?? '0') ?>) mm<br>
                            <strong>Size:</strong> <?= htmlspecialchars($field['width'] ?? '0') ?> × <?= htmlspecialchars($field['height'] ?? '0') ?> mm<br>
                            <strong>Confidence:</strong> <?= htmlspecialchars($field['confidence'] ?? 'N/A') ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($backgrounds)): ?>
            <div class="tech-preview">
                <h2>🔍 PDF Preview with Field Overlays</h2>
                <p>Green rectangles show detected field positions:</p>
                <div class="tech-preview-pages">
                    <?php
                    // Constants matching universal_processor.php
                    $DPI = 200;
                    $MM_TO_INCH = 0.0393701;
                    $INCH_TO_PX_AT_DPI = $DPI;
                    $MM_TO_PX_AT_DPI = $MM_TO_INCH * $INCH_TO_PX_AT_DPI; // ~7.87 px/mm at 200 DPI
                    $PREVIEW_SCALE = 0.5; // Show at 50% size
                    $IMAGE_HEIGHT_PX = 1100; // Approximate height at 50% scale
                    
                    foreach ($backgrounds as $pageNum => $bgUrl):
                        $pageFields = array_filter($fields, function($f) use ($pageNum) {
                            return ($f['page'] ?? 1) == $pageNum;
                        });
                    ?>
                        <div class="tech-preview-page">
                            <div class="tech-preview-page-title">
                                Page <?= $pageNum ?> – <?= count($pageFields) ?> fields detected
                            </div>
                            <div class="tech-preview-canvas">
                                <img src="<?= htmlspecialchars($bgUrl) ?>" alt="Background page <?= $pageNum ?>" class="tech-preview-bg" />
                                <div class="tech-preview-overlay-layer">
                                    <?php foreach ($pageFields as $key => $field): ?>
                                        <?php
                                        // Convert mm (already top-origin) to pixels at 200 DPI, then scale for preview
                                        $xPx = ($field['x'] ?? 0) * $MM_TO_PX_AT_DPI * $PREVIEW_SCALE;
                                        $wPx = max(10, ($field['width'] ?? 0) * $MM_TO_PX_AT_DPI * $PREVIEW_SCALE);
                                        $hPx = max(10, ($field['height'] ?? 0) * $MM_TO_PX_AT_DPI * $PREVIEW_SCALE);
                                        $yPx = ($field['y'] ?? 0) * $MM_TO_PX_AT_DPI * $PREVIEW_SCALE;
                                        
                                        $name = substr($key, 0, 30);
                                        ?>
                                        <div class="tech-preview-field" style="left:<?= $xPx ?>px; top:<?= $yPx ?>px; width:<?= $wPx ?>px; height:<?= $hPx ?>px;" title="<?= htmlspecialchars($name) ?>">
                                            <span class="tech-preview-field-label"><?= htmlspecialchars($name) ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <div style="background: #f8d7da; border: 2px solid #dc3545; padding: 20px; border-radius: 5px;">
                <h2>❌ Background Images Not Found</h2>
                <p>Background images not found in: <?= htmlspecialchars($uploadsDir) ?></p>
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #dee2e6;">
            <p><a href="?route=universal-processor">← Back to Universal Processor</a></p>
            <p><a href="?route=debug-extraction">🔍 View Debug Page</a></p>
        </div>
    </div>
</body>
</html>

