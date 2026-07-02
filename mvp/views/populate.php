<?php 
$tpl = $template; 
// STATIC POPULATE FILE - NO DYNAMIC CONTENT
// Note: pdftimesaver-content-body wrapper is already included in layout_header.php
?>
<?php $projectDocumentsWithTemplates = is_array($projectDocumentsWithTemplates ?? null) ? $projectDocumentsWithTemplates : []; ?>
<?php
$previewBackgrounds = is_array($previewBackgrounds ?? null) ? $previewBackgrounds : [];
$fieldPositions = is_array($fieldPositions ?? null) ? $fieldPositions : [];
$populatePreviewStatus = is_array($populatePreviewStatus ?? null) ? $populatePreviewStatus : [];
$previewPageCount = max(1, (int)($previewPageCount ?? 1));
$hideFallbackFieldGrid = !empty($populatePreviewStatus['missingAssets']);
$hasInteractivePreview = !empty($previewBackgrounds) && !empty($fieldPositions);
if ((string)($_GET['displayMode'] ?? 'single') === 'all' && !empty($projectDocumentsWithTemplates ?? [])) {
    $hasInteractivePreview = true;
}
?>
<?php
require_once dirname(__DIR__) . '/lib/field_metrics.php';
$fieldMetricsJs = \WebPdfTimeSaver\Mvp\FieldMetrics::jsConfig();
$currentFormIndex = null;
$projectFormCount = count($projectDocumentsWithTemplates);
$displayMode = (string)($_GET['displayMode'] ?? 'single');
if ($displayMode !== 'all') {
    $displayMode = 'single';
}
foreach ($projectDocumentsWithTemplates as $idx => $entry) {
    $doc = is_array($entry['doc'] ?? null) ? $entry['doc'] : [];
    $isCurrent = !empty($entry['isCurrent']);
    if ($isCurrent || (string)($doc['id'] ?? '') === (string)($projectDocument['id'] ?? '')) {
        $currentFormIndex = $idx;
        break;
    }
}
$prevTemplateId = '';
$nextTemplateId = '';
if ($currentFormIndex !== null) {
    if ($currentFormIndex > 0) {
        $prevEntry = $projectDocumentsWithTemplates[$currentFormIndex - 1] ?? [];
        $prevDoc = is_array($prevEntry['doc'] ?? null) ? $prevEntry['doc'] : [];
        $prevTemplateId = (string)($prevDoc['templateId'] ?? '');
    }
    if ($currentFormIndex < ($projectFormCount - 1)) {
        $nextEntry = $projectDocumentsWithTemplates[$currentFormIndex + 1] ?? [];
        $nextDoc = is_array($nextEntry['doc'] ?? null) ? $nextEntry['doc'] : [];
        $nextTemplateId = (string)($nextDoc['templateId'] ?? '');
    }
}
$isLastForm = ($nextTemplateId === '');
$populateProjectName = trim((string)($projectDocument['projectName'] ?? ''));
if ($populateProjectName === '' && !empty($projectDocument['projectId']) && isset($store) && $store && method_exists($store, 'getProject')) {
    $projectRow = $store->getProject((string)$projectDocument['projectId']);
    if (is_array($projectRow)) {
        $populateProjectName = trim((string)($projectRow['name'] ?? ''));
    }
}
if ($populateProjectName === '') {
    $populateProjectName = 'Project';
}
$tempCustomFields = [];
$tempCustomFieldsRaw = (string)($values['temporary_custom_fields_json'] ?? '[]');
$decodedTempFields = json_decode($tempCustomFieldsRaw, true);
if (is_array($decodedTempFields)) {
    foreach ($decodedTempFields as $row) {
        if (!is_array($row)) {
            continue;
        }
        $rawTempW = (float)($row['width'] ?? 45);
        $rawTempH = (float)($row['height'] ?? 3.18);
        // Legacy saves stored font pt (7–12) or Form Manager default (10) as box height mm.
        if ($rawTempH >= 6) {
            $rawTempH = 3.18;
        }
        if ($rawTempW < 8 || $rawTempW > 175) {
            $rawTempW = 45;
        }
        $tempCustomFields[] = [
            'id' => (string)($row['id'] ?? ''),
            'label' => (string)($row['label'] ?? ''),
            'value' => (string)($row['value'] ?? ''),
            'page' => max(1, min(50, (int)($row['page'] ?? 1))),
            'left' => max(0, min(210, (float)($row['left'] ?? 20))),
            'top' => max(0, min(273, (float)($row['top'] ?? 20))),
            'width' => max(2, min(205, $rawTempW)),
            'height' => max(2, min(6, $rawTempH)),
            'fontPt' => max(4, min(24, (float)($row['fontPt'] ?? ($row['pt'] ?? ($fieldMetricsJs['DEFAULT_FONT_PX'] ?? 13))))),
            'fontSize' => max(0, min(32, (int)($row['fontSize'] ?? 0))),
            'fontColor' => (string)($row['fontColor'] ?? '#000000'),
            'fontStyle' => strtoupper(preg_replace('/[^BIUS]/', '', (string)($row['fontStyle'] ?? ''))),
        ];
    }
}
?>
<div class="populate-top-strip">
    <div class="populate-header-row">
        <?php if (!empty($projectDocumentsWithTemplates)): ?>
            <?php $currentTemplateId = (string)($projectDocument['templateId'] ?? ''); ?>
            <form method="post" action="?route=actions/open-project-form" class="populate-header-controls">
                <input type="hidden" name="projectId" value="<?php echo htmlspecialchars((string)$projectDocument['projectId']); ?>">
                <label style="font-size:13px; color:#4d5b68;">Document:</label>
                <span class="populate-current-doc"><?php echo htmlspecialchars(formatTemplateDisplayLabel($tpl, (string)($projectDocument['templateId'] ?? ''))); ?></span>
                <label for="project-form-select" style="font-size:13px; color:#4d5b68;">Form:</label>
                <select id="project-form-select" name="templateId" class="pdftimesaver-input" style="min-width:220px;" onchange="this.form.submit()">
                    <?php foreach ($projectDocumentsWithTemplates as $entry): ?>
                        <?php
                        $doc = is_array($entry['doc'] ?? null) ? $entry['doc'] : [];
                        $docTpl = is_array($entry['template'] ?? null) ? $entry['template'] : [];
                        $templateId = (string)($doc['templateId'] ?? '');
                        $docLabel = formatTemplateDisplayLabel($docTpl, $templateId);
                        ?>
                        <option value="<?php echo htmlspecialchars($templateId); ?>" <?php echo $templateId === $currentTemplateId ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($docLabel); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <label for="display-mode-select" style="font-size:13px; color:#4d5b68;">Display:</label>
                <select id="display-mode-select" class="pdftimesaver-input" style="min-width:180px;">
                    <option value="single" <?php echo $displayMode === 'single' ? 'selected' : ''; ?>>Single Form Mode</option>
                    <option value="all" <?php echo $displayMode === 'all' ? 'selected' : ''; ?>>All Forms Mode</option>
                </select>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($populatePreviewStatus['missingAssets'])): ?>
    <div class="pdftimesaver-alert pdftimesaver-alert-warning" style="margin-bottom: 12px;">
        <strong>Preview files are missing for this form.</strong>
        <div style="margin-top: 4px;">
            <?php foreach ((array)($populatePreviewStatus['missingReasons'] ?? []) as $reason): ?>
                <div><?php echo htmlspecialchars((string)$reason); ?></div>
            <?php endforeach; ?>
            <div style="margin-top: 4px;">This usually happens after a form or extraction file was removed. Re-import or re-extract this form to restore the visual preview.</div>
        </div>
    </div>
<?php endif; ?>

<style>
/* Workflow progress styles now handled in layout_header.php */
.populate-size-controls {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 8px;
}
.populate-size-btn {
    min-width: 32px;
    min-height: 32px;
    border: 1px solid #ced4da;
    background: #fff;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
}
.populate-size-meta {
    font-size: 12px;
    color: #495057;
}
.populate-style-controls {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 8px;
    flex-wrap: wrap;
}
.populate-inline-size {
    height: 32px;
    padding: 0 6px;
    border: 1px solid #ced4da;
    border-radius: 6px;
    background: #fff;
    font-size: 12px;
    color: #495057;
    cursor: pointer;
}
.populate-inline-color {
    width: 34px;
    height: 32px;
    padding: 2px;
    border: 1px solid #ced4da;
    border-radius: 6px;
    background: #fff;
    cursor: pointer;
}
.populate-inline-style {
    min-width: 32px;
    min-height: 32px;
    border: 1px solid #ced4da;
    background: #fff;
    border-radius: 6px;
    cursor: pointer;
    font-size: 13px;
    line-height: 1;
    color: #495057;
}
.populate-inline-style:hover { background: #f1f5f9; }
.populate-inline-style[aria-pressed="true"] {
    background: #1e3a5f;
    color: #fff;
    border-color: #1e3a5f;
}
.populate-overflow-warning {
    color: #d93025 !important;
    border-color: #d93025 !important;
}
.populate-all-form-item {
    border: 1px solid #d8e3f0;
    border-radius: 10px;
    padding: 12px;
    background: #f8fbff;
    margin-bottom: 10px;
}
.populate-temp-fields-wrap {
    position: relative;
    min-height: 180px;
    border: 1px dashed #bcc7d4;
    border-radius: 10px;
    background: #fafcff;
    margin-top: 10px;
    overflow: hidden;
}
.populate-temp-field {
    position: absolute;
    border: 1px solid #9db3cc;
    border-radius: 8px;
    background: #ffffff;
    box-shadow: 0 1px 4px rgba(0,0,0,0.08);
    resize: both;
    overflow: auto;
    min-width: 180px;
    min-height: 110px;
    padding: 8px;
    cursor: move;
}
.populate-temp-field input,
.populate-temp-field textarea {
    width: 100%;
    box-sizing: border-box;
}
.populate-temp-field textarea {
    min-height: 50px;
    resize: vertical;
}
.populate-temp-field-remove {
    margin-top: 6px;
}
.populate-panel-nav {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
    margin: 0 0 14px 0;
    padding: 10px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: #f8fafc;
}
.populate-panel-nav-group {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
.populate-panel-nav-meta {
    font-size: 12px;
    color: #475569;
}
.populate-header-row {
    display: flex;
    justify-content: flex-start;
    align-items: center;
    margin: 0;
    flex-wrap: nowrap;
    gap: 6px;
}
.populate-header-controls {
    display: flex;
    align-items: center;
    flex-wrap: nowrap;
    gap: 4px;
    margin: 0;
    overflow-x: visible;
    padding: 0;
}
.populate-current-doc {
    display: inline-block;
    flex: 0 0 auto;
    white-space: nowrap;
    font-size: 13px;
    color: #1e293b;
    font-weight: 600;
    line-height: 1.2;
}
.populate-top-strip {
    margin: 0 0 4px 0;
    padding: 0;
    border: 0;
    background: transparent;
}
body.route-populate-scroll-lock .pdftimesaver-content-header {
    padding-bottom: 8px;
}
body.route-populate-scroll-lock .pdftimesaver-content-title {
    margin-bottom: 0;
}
/* Populate: keep only the left preview scrollbar; remove outer/right page scroll. */
body.route-populate-scroll-lock .pdftimesaver-main-content {
    height: 100vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}
body.route-populate-scroll-lock .pdftimesaver-content-header {
    flex: 0 0 auto;
}
body.route-populate-scroll-lock .pdftimesaver-content-body {
    flex: 1 1 auto;
    min-height: 0;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    position: relative;
    gap: 8px;
}
body.route-populate-scroll-lock .populate-top-card {
    flex: 0 0 auto;
}
body.route-populate-scroll-lock .fillout-preview-card {
    flex: 1 1 auto;
    min-height: 0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    padding-bottom: 0;
}
body.route-populate-scroll-lock .fillout-layout {
    flex: 1 1 auto;
    min-height: 0;
    align-items: stretch;
    overflow: hidden;
}
body.route-populate-scroll-lock .populate-action-bar-wrap {
    position: static;
    margin: 8px 0 0 0;
    z-index: 35;
    background: #fff;
}
/* Interactive PDF preview (fill-out) */
.fillout-preview-card {
    --fillout-empty-field-bg: #eef1f5;
    padding: 0;
    overflow: visible;
}
.fillout-preview-toolbar {
    display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
    padding: 12px 16px; border-bottom: 1px solid #e2e8f0; background: #f8fafc;
}
.fillout-preview-hint { font-size: 12px; color: #64748b; margin: 0; }
.fillout-preview {
    display: flex; flex-direction: column; align-items: stretch; gap: 26px;
    background: #eef2f7; padding: 20px; max-height: none; overflow: visible;
}
.fillout-page {
    position: relative; width: 100%;
    box-shadow: 0 2px 12px rgba(0,0,0,0.18); background: #fff;
}
.fillout-preview:not(.fillout-ready) .fillout-field { visibility: hidden; }
.fillout-bg { display: block; width: 100%; height: auto; background: #fff; user-select: none; -webkit-user-drag: none; }
.fillout-overlay { position: absolute; top: 0; left: 0; pointer-events: none; }
.fillout-field {
    position: absolute; overflow: hidden; pointer-events: auto;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    border-radius: 4px;
    box-sizing: border-box;
    box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.04);
}
.fillout-field .js-overlay-input {
    position: absolute; left: 0; top: 0; right: 0; bottom: 0;
    width: 100%; height: 100%; box-sizing: border-box;
    border: none; background: transparent;
    padding: 1px 2px; margin: 0; line-height: 1.05; border-radius: 0;
    color: #0f172a; font-weight: 400;
    overflow: hidden; text-overflow: ellipsis;
    z-index: 2;
    /* Sharpen overlay text at small sizes across Chromium/Windows. */
    font-family: Arial, "Helvetica Neue", Helvetica, "Segoe UI", sans-serif;
    letter-spacing: 0;
    font-kerning: normal;
    font-variant-ligatures: none;
    font-feature-settings: "kern" 1;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    text-rendering: geometricPrecision;
}
.fillout-field-value {
    /* Single-layer display: input shows text; span kept in DOM for sync/measurement only */
    display: none;
}
.fillout-field .js-overlay-input:focus {
    outline: none;
    background: rgba(255, 255, 255, 0.55);
    z-index: 5;
}
.fillout-field .js-overlay-input.populate-overflow-warning { color: #d93025 !important; }
.fillout-field input[type="hidden"] {
    position: absolute; width: 0; height: 0; opacity: 0; pointer-events: none;
}
.fillout-field .js-overlay-checkbox {
    position: absolute; left: 0; top: 0; right: 0; bottom: 0;
    width: 100%; height: 100%; margin: 0; padding: 0;
    opacity: 0; cursor: pointer; z-index: 3;
    appearance: none; -webkit-appearance: none;
    border: none; outline: none; background: transparent; color: transparent;
}
.fillout-field.is-checkbox-field {
    border-color: transparent;
    background: transparent;
    box-shadow: none;
    border-radius: 0;
}
.fillout-field.is-checkbox-field.is-selected {
    border-color: #2563eb;
    background: rgba(37, 99, 235, 0.12);
    box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.35);
}
.fillout-field.is-checkbox-checked::after {
    content: "X";
    position: absolute; inset: 0;
    display: flex; align-items: center; justify-content: center;
    color: #0f172a; font-weight: 700; line-height: 1;
    font-size: var(--fillout-check-size, clamp(8px, 85%, 14px));
    pointer-events: none; z-index: 1;
}
.fillout-field.is-empty-field {
    background: var(--fillout-empty-field-bg);
}
.fillout-field.is-selected {
    border-color: #2563eb;
    background: rgba(37, 99, 235, 0.12);
    box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.35);
    z-index: 6;
}
.fillout-field.is-selected .js-overlay-input { background: rgba(255, 255, 255, 0.55); }
.fillout-layout {
    display: flex;
    gap: 12px;
    align-items: start;
}
.fillout-preview-main {
    min-width: 0;
    flex: 1;
    min-height: 0;
    overflow: auto;
}
.fillout-sidebar {
    background: #f8fafc;
    border: 1px solid #dbe4ef;
    border-radius: 8px;
    display: flex;
    flex-direction: column;
    position: sticky;
    top: 12px;
    height: calc(100vh - 24px);
    max-height: calc(100vh - 24px);
    align-self: start;
    z-index: 20;
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.08);
    width: 300px;
    min-width: 300px;
}
.fillout-layout.is-panel-open .fillout-sidebar {
    display: flex;
}
@media (min-width: 961px) {
    .fillout-layout.is-panel-open .fillout-sidebar {
        position: sticky;
        top: 12px;
        width: 300px;
        min-width: 300px;
        height: calc(100vh - 24px);
        max-height: calc(100vh - 24px);
    }
}
.fillout-sidebar-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    padding: 12px 14px;
    border-bottom: 1px solid #e2e8f0;
    background: #fff;
}
.fillout-sidebar-header h4 {
    margin: 0;
    font-size: 15px;
    color: #1e3a5f;
}
.fillout-sidebar-body {
    padding: 12px 14px 16px;
    overflow: auto;
    flex: 1;
    min-height: 0;
    padding-bottom: 96px;
}
.fillout-sidebar-footer {
    margin-top: 0;
    padding: 12px 14px 16px;
    border-top: 1px solid #e2e8f0;
    background: #fff;
    flex-shrink: 0;
    position: sticky;
    bottom: 0;
    z-index: 3;
}
.fillout-sidebar-footer.js-fillout-custombox-section {
    margin-top: 0;
}
.fillout-sidebar .pdftimesaver-btn-secondary,
.fillout-sidebar .js-fillout-preset-apply {
    background: #2563eb;
    border: 1px solid #2563eb;
    color: #fff;
}
.fillout-sidebar .pdftimesaver-btn-secondary:hover,
.fillout-sidebar .js-fillout-preset-apply:hover {
    background: #1d4ed8;
    border-color: #1d4ed8;
}
.fillout-field-status {
    font-size: 12px;
    color: #64748b;
    line-height: 1.45;
    margin: 0;
}
.fillout-sidebar-label {
    display: block;
    font-size: 11px;
    color: #475569;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    margin: 0 0 4px 0;
}
.fillout-section-heading {
    display: flex;
    align-items: center;
    gap: 6px;
}
.fillout-help-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 16px;
    height: 16px;
    border: 1px solid #94a3b8;
    border-radius: 50%;
    font-size: 11px;
    font-weight: 600;
    line-height: 1;
    color: #64748b;
    background: #fff;
    cursor: help;
    user-select: none;
    vertical-align: middle;
    position: relative;
    flex-shrink: 0;
}
.fillout-help-icon::after {
    content: attr(data-tip);
    position: absolute;
    left: 50%;
    top: auto;
    bottom: calc(100% + 8px);
    transform: translateX(-50%);
    min-width: 220px;
    max-width: 280px;
    padding: 8px 10px;
    border-radius: 8px;
    background: #0f172a;
    color: #fff;
    font-size: 12px;
    line-height: 1.35;
    box-shadow: 0 8px 20px rgba(15, 23, 42, 0.25);
    opacity: 0;
    pointer-events: none;
    z-index: 2000;
    white-space: normal;
    overflow-wrap: anywhere;
    word-break: break-word;
}
.fillout-help-icon:hover::after,
.fillout-help-icon:focus-visible::after {
    opacity: 1;
}
.fillout-sidebar-checkbox-note {
    font-size: 12px;
    color: #64748b;
    margin: 8px 0 0 0;
}
.fillout-color-row { display: flex; align-items: center; gap: 10px; }
.fillout-color-row input[type="color"] {
    width: 46px; height: 34px; padding: 0; border: 1px solid #cbd5e1;
    border-radius: 6px; background: #fff; cursor: pointer;
}
.fillout-palette {
    display: flex;
    flex-wrap: nowrap;
    gap: 4px;
    overflow: hidden;
}
.fillout-swatch {
    width: 20px; height: 20px; border-radius: 4px; cursor: pointer;
    border: 1px solid rgba(15,23,42,0.18); padding: 0;
    flex: 0 0 auto;
}
.fillout-swatch:hover { transform: scale(1.08); }
.fillout-swatch[aria-pressed="true"] {
    outline: 2px solid #2563eb; outline-offset: 1px;
}
.fillout-format-row { display: flex; gap: 8px; }
.fillout-format-btn {
    min-width: 38px; height: 34px; border: 1px solid #cbd5e1; background: #fff;
    color: #334155; border-radius: 6px; cursor: pointer; font-size: 15px;
}
.fillout-format-btn:hover { background: #f1f5f9; }
.fillout-format-btn[aria-pressed="true"] {
    background: #2563eb; color: #fff; border-color: #2563eb;
}
.fillout-sidebar-section {
    margin-top: 18px; padding-top: 16px; border-top: 1px solid #e2e8f0;
}
@media (max-width: 960px) {
    .fillout-layout { display: flex; flex-direction: column; }
    .fillout-preview-main { order: 2; }
    .fillout-layout.is-panel-open .fillout-sidebar {
        order: 1;
        position: static;
        top: 8px;
        z-index: 7;
        max-height: 55vh;
        border: 1px solid #dbe4ef;
        border-radius: 8px;
        box-shadow: 0 6px 18px rgba(15, 23, 42, 0.12);
        margin-bottom: 12px;
        width: auto;
        min-width: 0;
    }
}
.fillout-unplaced { padding: 16px; }
.fillout-page-label { font-size: 12px; color: #64748b; padding: 4px 0; align-self: flex-start; }
/* Custom Input Box — same shell as .fillout-field; purple tint only. */
.fillout-temp-field {
    border-color: #7c3aed;
    background: rgba(124, 58, 237, 0.10);
    z-index: 25;
}
.fillout-temp-field.is-empty-field {
    background: var(--fillout-empty-field-bg);
}
.fillout-temp-field.is-selected {
    border-color: #7c3aed;
    background: rgba(124, 58, 237, 0.12);
    box-shadow: 0 0 0 2px rgba(124, 58, 237, 0.35);
}
.fillout-temp-field.is-selected .js-overlay-input { background: rgba(255, 255, 255, 0.55); }
.fillout-temp-move-handle {
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 6px;
    cursor: move;
    background: rgba(124, 58, 237, 0.4);
    z-index: 8;
    display: none;
}
.fillout-temp-field.is-selected .fillout-temp-move-handle { display: block; }
.fillout-temp-resize-handle {
    position: absolute;
    right: 0; bottom: 0;
    width: 10px; height: 10px;
    cursor: nwse-resize;
    background: #7c3aed;
    border-radius: 2px 0 0 0;
    z-index: 8;
    display: none;
}
.fillout-temp-field.is-selected .fillout-temp-resize-handle { display: block; }
.populate-action-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}
.populate-action-bar-left,
.populate-action-bar-right {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}
.populate-action-bar select.populate-action-select {
    width: auto;
    min-width: 150px;
    min-height: 42px;
    height: 42px;
}
/* Make every action button on this page blue (primary), per request. */
.populate-action-bar .pdftimesaver-btn,
.populate-action-bar .pdftimesaver-btn-action,
.populate-action-bar .pdftimesaver-btn-secondary {
    background: #2563eb;
    border: 1px solid #2563eb;
    color: #fff;
}
.populate-action-bar .pdftimesaver-btn:hover,
.populate-action-bar .pdftimesaver-btn-action:hover,
.populate-action-bar .pdftimesaver-btn-secondary:hover {
    background: #1d4ed8;
    border-color: #1d4ed8;
}
/* Custom Input Box add button (sidebar) blue too. */
#add-temp-field-btn.pdftimesaver-btn-secondary {
    background: #2563eb;
    border: 1px solid #2563eb;
    color: #fff;
}
#add-temp-field-btn.pdftimesaver-btn-secondary:hover {
    background: #1d4ed8;
    border-color: #1d4ed8;
}
.populate-all-form-item-actions {
    margin-top: 10px;
    display: flex;
    justify-content: flex-end;
}
</style>

<form method="post" action="?route=actions/save-fields" id="populate-form">
    <input type="hidden" name="projectDocumentId" value="<?php echo htmlspecialchars($projectDocument['id']); ?>">
    <input type="hidden" name="temporary_custom_fields_json" id="temporary-custom-fields-json" value="<?php echo htmlspecialchars(json_encode($tempCustomFields)); ?>">

    <?php
    // Shared per-field style helpers for the fallback (non-overlay) field grids so
    // the size dropdown / color / Bold-Underline-Strikeout controls stay consistent
    // with the interactive PDF editor and persist into the generated PDF.
    $ptsFieldStyleCss = static function (int $size, string $color, string $style): string {
        $css = 'font-size: ' . $size . 'px;';
        if (preg_match('/^#[0-9a-fA-F]{6}$/', $color)) { $css .= ' color: ' . $color . ';'; }
        if (strpos($style, 'B') !== false) { $css .= ' font-weight: 700;'; }
        $deco = [];
        if (strpos($style, 'U') !== false) { $deco[] = 'underline'; }
        if (strpos($style, 'S') !== false) { $deco[] = 'line-through'; }
        if ($deco) { $css .= ' text-decoration: ' . implode(' ', $deco) . ';'; }
        return $css;
    };
    $filloutMinFontPx = 4;
    // Keep populate defaults aligned to canonical pt->px conversion used by
    // import/edit/export flows (1pt = 96/72 CSS px).
    $filloutDefaultFontPx = (int)max($filloutMinFontPx, (int)round($fieldMetricsJs['DEFAULT_FONT_PX'] ?? 13));
    $ptsStyleControls = static function (int $size, string $color, string $style) use ($filloutMinFontPx): void {
        $pickerColor = preg_match('/^#[0-9a-fA-F]{6}$/', $color) ? $color : '#000000';
        ?>
        <div class="populate-style-controls">
            <select class="populate-inline-size js-inline-size" aria-label="Font size">
                <?php for ($fs = $filloutMinFontPx; $fs <= 24; $fs++): ?>
                    <option value="<?php echo $fs; ?>"<?php echo $fs === $size ? ' selected' : ''; ?>><?php echo $fs; ?>px</option>
                <?php endfor; ?>
            </select>
            <input type="color" class="populate-inline-color js-inline-color" value="<?php echo htmlspecialchars($pickerColor); ?>" aria-label="Font color" title="Font color">
            <button type="button" class="populate-inline-style js-inline-style" data-style="B" title="Bold" aria-pressed="<?php echo strpos($style, 'B') !== false ? 'true' : 'false'; ?>" style="font-weight:700;">B</button>
            <button type="button" class="populate-inline-style js-inline-style" data-style="U" title="Underline" aria-pressed="<?php echo strpos($style, 'U') !== false ? 'true' : 'false'; ?>" style="text-decoration:underline;">U</button>
            <button type="button" class="populate-inline-style js-inline-style" data-style="S" title="Strikeout" aria-pressed="<?php echo strpos($style, 'S') !== false ? 'true' : 'false'; ?>" style="text-decoration:line-through;">S</button>
        </div>
        <?php
    };
    $isTechnicalPopulateLabel = static function (string $value): bool {
        $v = strtolower(trim($value));
        if ($v === '') { return true; }
        if (preg_match('/^(fl|auto)[ _-]?\d+/', $v)) { return true; }
        if (strpos($v, 'page') !== false && strpos($v, 'caption') !== false) { return true; }
        if (strpos($v, '_ft_') !== false || strpos($v, '_cb_') !== false || strpos($v, '_tf_') !== false) { return true; }
        return false;
    };
    $friendlyPopulateLabelFromKey = static function (string $fieldKey): string {
        $tokens = preg_split('/[^a-z0-9]+/i', strtolower($fieldKey));
        if (!is_array($tokens)) { $tokens = []; }
        $skip = ['fl', 'auto', 'page', 'caption', 'captionp1', 'captionp2', 'sf', 'ft', 'cb', 'tf', 'dt', 'li', 'list'];
        $out = [];
        foreach ($tokens as $tok) {
            if ($tok === '' || ctype_digit($tok)) { continue; }
            if (in_array($tok, $skip, true)) { continue; }
            if (preg_match('/^page\\d+$/', $tok)) { continue; }
            if ($tok === 'attyinfo') { $tok = 'attorney'; }
            if ($tok === 'courtinfo') { $tok = 'court'; }
            $out[] = $tok;
            if (count($out) >= 3) { break; }
        }
        if (empty($out)) { return 'Field'; }
        return ucwords(trim(implode(' ', $out)));
    };
    $resolvePopulateFieldLabel = static function (array $field, string $fieldKey) use ($isTechnicalPopulateLabel, $friendlyPopulateLabelFromKey): string {
        $candidates = [
            (string)($field['displayName'] ?? ''),
            (string)($field['label'] ?? ''),
            (string)($field['placeholder'] ?? ''),
            (string)($field['matchingTag'] ?? ''),
            (string)($field['linkId'] ?? ''),
        ];
        foreach ($candidates as $candidate) {
            $candidate = trim($candidate);
            if ($candidate === '' || $isTechnicalPopulateLabel($candidate)) { continue; }
            return $candidate;
        }
        return $friendlyPopulateLabelFromKey($fieldKey);
    };
    $normalizePopulatePlaceholder = static function (string $placeholder) use ($isTechnicalPopulateLabel): string {
        $placeholder = trim($placeholder);
        if ($placeholder === '' || $isTechnicalPopulateLabel($placeholder)) {
            return 'Enter value';
        }
        return $placeholder;
    };
    ?>

    <?php if ($hasInteractivePreview): ?>
        <?php
        $renderPages = [];
        $renderFieldTemplates = [];
        $unplacedFields = [];

        $normKey = static function (string $s): string {
            return strtolower((string)preg_replace('/[^a-z0-9]/i', '', $s));
        };
        $loadPreviewAssets = static function (string $templateId, array $docTpl) use ($normKey): array {
            $positions = [];
            $pageCount = max(1, (int)($docTpl['pageCount'] ?? 1));
            $positionsPath = __DIR__ . '/../data/' . $templateId . '_positions.json';
            if (is_file($positionsPath)) {
                $rawPos = json_decode((string)file_get_contents($positionsPath), true);
                if (is_array($rawPos)) {
                    $posByNorm = [];
                    $maxPage = 1;
                    foreach ($rawPos as $pKey => $pRow) {
                        if (!is_array($pRow)) { continue; }
                        $page = max(1, (int)($pRow['page'] ?? 1));
                        $maxPage = max($maxPage, $page);
                        $entry = [
                            'page' => $page,
                            'x' => (float)($pRow['x'] ?? 0),
                            'y' => (float)($pRow['y'] ?? 0),
                            'width' => (float)($pRow['width'] ?? 0),
                            'height' => (float)($pRow['height'] ?? 0),
                            'fontSize' => (float)($pRow['fontSize'] ?? 9),
                            'type' => (string)($pRow['type'] ?? 'text'),
                        ];
                        $orig = (string)($pRow['originalName'] ?? ($pRow['name'] ?? $pKey));
                        $posByNorm[$normKey($orig)] = $entry;
                        $posByNorm[$normKey((string)$pKey)] = $entry;
                    }
                    $pageCount = max($pageCount, $maxPage);
                    foreach ((array)($docTpl['fields'] ?? []) as $fld) {
                        $fk = (string)($fld['key'] ?? '');
                        if ($fk === '') { continue; }
                        $orig = (string)($fld['metadata']['originalName'] ?? '');
                        if ($orig !== '' && isset($posByNorm[$normKey($orig)])) {
                            $positions[$fk] = $posByNorm[$normKey($orig)];
                        } elseif (isset($posByNorm[$normKey($fk)])) {
                            $positions[$fk] = $posByNorm[$normKey($fk)];
                        }
                    }
                }
            }
            $backgrounds = [];
            $normTpl = $normKey($templateId);
            $bgSources = [
                ['dir' => dirname(__DIR__) . '/uploads', 'urlPrefix' => 'uploads/'],
                ['dir' => dirname(dirname(__DIR__)) . '/uploads', 'urlPrefix' => '../uploads/'],
            ];
            $bestByPage = [];
            foreach ($bgSources as $src) {
                $uploadsDir = (string)($src['dir'] ?? '');
                $urlPrefix = (string)($src['urlPrefix'] ?? '');
                if (!is_dir($uploadsDir)) {
                    continue;
                }
                foreach ((array)(glob($uploadsDir . '/*_page*_background.png') ?: []) as $bgPath) {
                    $base = basename((string)$bgPath);
                    if (preg_match('/^(.*)_page(\d+)_background\.png$/i', $base, $m) !== 1) {
                        continue;
                    }
                    if ($normKey((string)$m[1]) !== $normTpl) {
                        continue;
                    }
                    $pageNum = (int)$m[2];
                    if ($pageNum < 1) {
                        continue;
                    }
                    $size = @getimagesize($bgPath);
                    $area = (is_array($size) && isset($size[0], $size[1])) ? ((int)$size[0] * (int)$size[1]) : 0;
                    $mtime = (int)@filemtime($bgPath);
                    $current = $bestByPage[$pageNum] ?? null;
                    $isBetter = $current === null
                        || $area > (int)($current['area'] ?? 0)
                        || ($area === (int)($current['area'] ?? 0) && $mtime > (int)($current['mtime'] ?? 0));
                    if ($isBetter) {
                        $bestByPage[$pageNum] = [
                            'url' => $urlPrefix . $base,
                            'area' => $area,
                            'mtime' => $mtime,
                        ];
                    }
                }
            }
            foreach ($bestByPage as $pageNum => $meta) {
                $backgrounds[(int)$pageNum] = (string)($meta['url'] ?? '');
            }
            if (!empty($backgrounds)) {
                ksort($backgrounds);
            }
            return [$backgrounds, $positions, $pageCount];
        };

        $allModeTemplates = [];
        if ($displayMode === 'all' && !empty($projectDocumentsWithTemplates)) {
            foreach ($projectDocumentsWithTemplates as $entry) {
                $doc = is_array($entry['doc'] ?? null) ? $entry['doc'] : [];
                $docTpl = is_array($entry['template'] ?? null) ? $entry['template'] : [];
                $templateId = (string)($doc['templateId'] ?? '');
                if ($templateId === '' || empty($docTpl['fields'])) { continue; }
                [$docBackgrounds, $docFieldPositions, $docPageCount] = $loadPreviewAssets($templateId, $docTpl);
                // Always ensure current document can render in All mode using the already
                // prepared preview payload, even if per-doc asset discovery misses.
                if ((string)$templateId === (string)($projectDocument['templateId'] ?? '')) {
                    if (empty($docBackgrounds) && !empty($previewBackgrounds)) {
                        $docBackgrounds = (array)$previewBackgrounds;
                    }
                    if (empty($docFieldPositions) && !empty($fieldPositions)) {
                        $docFieldPositions = (array)$fieldPositions;
                    }
                    if ($docPageCount < 1) {
                        $docPageCount = max(1, (int)($previewPageCount ?? 1));
                    }
                }
                if (empty($docBackgrounds)) { continue; }
                $renderFieldTemplates[] = $docTpl;
                $allModeTemplates[] = $templateId;
                $docFieldsByPage = [];
                $docPlaced = [];
                foreach ((array)$docTpl['fields'] as $field) {
                    $fk = (string)($field['key'] ?? '');
                    if ($fk === '' || !isset($docFieldPositions[$fk])) { continue; }
                    $pos = $docFieldPositions[$fk];
                    $pg = max(1, (int)($pos['page'] ?? 1));
                    $docFieldsByPage[$pg][] = ['field' => $field, 'pos' => $pos];
                    $docPlaced[$fk] = true;
                }
                foreach ((array)$docTpl['fields'] as $field) {
                    $fk = (string)($field['key'] ?? '');
                    if ($fk === '' || isset($docPlaced[$fk])) { continue; }
                    $unplacedFields[] = $field;
                }
                $docLabel = formatTemplateDisplayLabel($docTpl, $templateId);
                for ($p = 1; $p <= $docPageCount; $p++) {
                    $bg = (string)($docBackgrounds[$p] ?? '');
                    if ($bg === '') { continue; }
                    $renderPages[] = [
                        'bg' => $bg,
                        'label' => $docLabel . ' — Page ' . $p,
                        'entries' => (array)($docFieldsByPage[$p] ?? []),
                    ];
                }
            }
        } else {
            $fieldsByPage = [];
            $placedKeys = [];
            foreach ((array)$tpl['fields'] as $field) {
                $fk = (string)($field['key'] ?? '');
                if ($fk === '' || !isset($fieldPositions[$fk])) { continue; }
                $pos = $fieldPositions[$fk];
                $pg = max(1, (int)($pos['page'] ?? 1));
                $fieldsByPage[$pg][] = ['field' => $field, 'pos' => $pos];
                $placedKeys[$fk] = true;
            }
            foreach ((array)$tpl['fields'] as $field) {
                $fk = (string)($field['key'] ?? '');
                if ($fk === '' || isset($placedKeys[$fk])) { continue; }
                $unplacedFields[] = $field;
            }
            $renderFieldTemplates[] = $tpl;
            for ($p = 1; $p <= $previewPageCount; $p++) {
                $bg = (string)($previewBackgrounds[$p] ?? '');
                if ($bg === '') { continue; }
                $renderPages[] = [
                    'bg' => $bg,
                    'label' => 'Page ' . $p,
                    'entries' => (array)($fieldsByPage[$p] ?? []),
                ];
            }
        }
        // Build category -> [{key,label,value}] groups so a box can be connected to
        // an existing (preset) field's saved value. Categories come from the panel a
        // field belongs to; fields with no panel are intentionally hidden.
        $panelLabelById = [];
        foreach ($renderFieldTemplates as $renderTpl) {
            foreach ((array)($renderTpl['panels'] ?? []) as $panel) {
                $pid = (string)($panel['id'] ?? '');
                if ($pid !== '') { $panelLabelById[$pid] = (string)($panel['label'] ?? $pid); }
            }
        }
        $presetFieldGroups = [];
        $presetSeen = [];
        foreach ($renderFieldTemplates as $renderTpl) {
            foreach ((array)$renderTpl['fields'] as $field) {
                $fk = (string)($field['key'] ?? '');
                if ($fk === '' || isset($presetSeen[$fk])) { continue; }
                if ((string)($field['type'] ?? 'text') === 'checkbox') { continue; }
                $pid = (string)($field['panelId'] ?? '');
                if ($pid === '') { continue; }
                $cat = $panelLabelById[$pid] ?? $pid;
                $presetFieldGroups[$cat][] = [
                    'key' => $fk,
                    'label' => (string)($field['label'] ?? $fk),
                    'value' => (string)($values[$fk] ?? ''),
                ];
                $presetSeen[$fk] = true;
            }
        }
        $managerPresetGroups = is_array($populateManagerPresetGroups ?? null) ? $populateManagerPresetGroups : [];
        // If Field Manager groups are available, use them as the source of truth so
        // Populate reflects manager mapping choices consistently.
        if (!empty($managerPresetGroups)) {
            $presetFieldGroups = $managerPresetGroups;
        }
        $inferPresetPointer = static function (array $field, string $fieldKey, array $groups): string {
            $normalizeLink = static function ($raw): string {
                return strtolower(trim((string)$raw));
            };
            $normalizeToken = static function ($raw): string {
                return strtolower((string)preg_replace('/[^a-z0-9]+/i', '', (string)$raw));
            };
            $findByLinkId = static function (string $linkId) use ($groups, $normalizeLink): string {
                $norm = $normalizeLink($linkId);
                if ($norm === '') { return ''; }
                foreach ($groups as $category => $items) {
                    foreach ((array)$items as $item) {
                        $itemKey = (string)($item['key'] ?? '');
                        if ($itemKey === '') { continue; }
                        $itemLink = $normalizeLink((string)($item['linkId'] ?? ''));
                        if ($itemLink !== '' && $itemLink === $norm) {
                            return (string)$category . '::' . $itemKey;
                        }
                    }
                }
                return '';
            };
            $match = $findByLinkId((string)($field['linkId'] ?? ''));
            if ($match !== '') {
                return $match;
            }
            $normFieldKey = $normalizeToken((string)($field['key'] ?? $fieldKey));
            if ($normFieldKey === '') {
                return '';
            }
            foreach ($groups as $category => $items) {
                foreach ((array)$items as $item) {
                    $itemKey = (string)($item['key'] ?? '');
                    if ($itemKey === '') { continue; }
                    $tokens = [
                        $normalizeToken((string)($item['matchingTag'] ?? '')),
                        $normalizeToken((string)($item['linkId'] ?? '')),
                        $normalizeToken((string)$itemKey),
                    ];
                    foreach ($tokens as $token) {
                        if ($token === '' || strlen($token) < 4) { continue; }
                        if (strpos($normFieldKey, $token) !== false) {
                            return (string)$category . '::' . $itemKey;
                        }
                    }
                }
            }
            return '';
        };
        ?>
        <div class="pdftimesaver-card fillout-preview-card">
            <div class="fillout-preview-toolbar">
                <strong style="color:#2c3e50;">Interactive form</strong>
                <p class="fillout-preview-hint">Type directly onto the document. Changes autosave. Click a field to open its properties panel.</p>
            </div>
            <div class="fillout-layout" id="filloutLayout">
            <div class="fillout-preview-main">
            <div class="fillout-preview" id="fillout-preview">
                <?php foreach ($renderPages as $pageIdx => $renderPage): ?>
                    <?php
                    $bg = (string)($renderPage['bg'] ?? '');
                    $pageLabel = (string)($renderPage['label'] ?? ('Page ' . ((int)$pageIdx + 1)));
                    $pageNum = (int)$pageIdx + 1;
                    ?>
                    <div class="fillout-page" data-page="<?php echo $pageNum; ?>">
                        <img class="fillout-bg" id="fillout-bg-<?php echo $pageNum; ?>" src="<?php echo htmlspecialchars($bg); ?>" alt="<?php echo htmlspecialchars($pageLabel); ?>">
                        <div class="fillout-overlay" id="fillout-overlay-<?php echo $pageNum; ?>">
                            <?php foreach ((array)($renderPage['entries'] ?? []) as $entry): ?>
                                <?php
                                $field = $entry['field'];
                                $pos = $entry['pos'];
                                $type = (string)($field['type'] ?? 'text');
                                $fieldKey = (string)($field['key'] ?? '');
                                $val = $values[$fieldKey] ?? '';
                                $label = (string)($field['label'] ?? $fieldKey);
                                $fontSizeKey = '_font_size__' . $fieldKey;
                                $storedFontSize = isset($values[$fontSizeKey]) ? (int)$values[$fontSizeKey] : 0;
                                $fontColorKey = '_font_color__' . $fieldKey;
                                $storedFontColor = isset($values[$fontColorKey]) && $values[$fontColorKey] !== '' ? (string)$values[$fontColorKey] : '#000000';
                                $fontStyleKey = '_font_style__' . $fieldKey;
                                $storedFontStyle = isset($values[$fontStyleKey]) ? strtoupper((string)$values[$fontStyleKey]) : '';
                                $pointerKey = '_preset_pointer__' . $fieldKey;
                                $storedPointer = isset($values[$pointerKey]) ? (string)$values[$pointerKey] : '';
                                if ($storedPointer === '') {
                                    $storedPointer = $inferPresetPointer((array)$field, $fieldKey, (array)$presetFieldGroups);
                                }
                                $styleCss = 'color:' . $storedFontColor . ';';
                                if (strpos($storedFontStyle, 'B') !== false) { $styleCss .= 'font-weight:700;'; }
                                $deco = [];
                                if (strpos($storedFontStyle, 'U') !== false) { $deco[] = 'underline'; }
                                if (strpos($storedFontStyle, 'S') !== false) { $deco[] = 'line-through'; }
                                if (!empty($deco)) { $styleCss .= 'text-decoration:' . implode(' ', $deco) . ';'; }
                                $seededPx = (float)($pos['fontSize'] ?? ($fieldMetricsJs['DEFAULT_FONT_PX'] ?? 13));
                                $fallbackFontPx = (int)max($filloutMinFontPx, min(32, round($seededPx)));
                                $displayFontPx = $storedFontSize > 0 ? $storedFontSize : $fallbackFontPx;
                                $styleCss = 'font-size: ' . (int)$displayFontPx . 'px;' . $styleCss;
                                ?>
                                <div class="fillout-field js-overlay-field<?php echo $type === 'checkbox' ? ' is-checkbox-field' : ''; ?>"
                                    data-field-label="<?php echo htmlspecialchars($label); ?>"
                                    data-field-key="<?php echo htmlspecialchars($fieldKey); ?>"
                                    data-field-link-id="<?php echo htmlspecialchars((string)($field['linkId'] ?? '')); ?>"
                                    data-x="<?php echo htmlspecialchars((string)($pos['x'] ?? 0)); ?>"
                                    data-y="<?php echo htmlspecialchars((string)($pos['y'] ?? 0)); ?>"
                                    data-w="<?php echo htmlspecialchars((string)($pos['width'] ?? 0)); ?>"
                                    data-h="<?php echo htmlspecialchars((string)($pos['height'] ?? 0)); ?>"
                                    data-pt="<?php echo htmlspecialchars((string)($pos['fontSize'] ?? $fieldMetricsJs['DEFAULT_FONT_PX'])); ?>"
                                    title="<?php echo htmlspecialchars($label); ?>">
                                    <?php if ($type === 'checkbox'): ?>
                                        <input type="hidden" name="<?php echo htmlspecialchars($fieldKey); ?>" value="0">
                                        <input type="checkbox" class="js-overlay-checkbox" name="<?php echo htmlspecialchars($fieldKey); ?>" value="1" <?php echo !empty($val) ? 'checked' : ''; ?> aria-label="<?php echo htmlspecialchars($label); ?>">
                                    <?php else: ?>
                                        <span class="fillout-field-value" aria-hidden="true"><?php echo htmlspecialchars((string)$val); ?></span>
                                        <input type="text" name="<?php echo htmlspecialchars($fieldKey); ?>"
                                            value="<?php echo htmlspecialchars((string)$val); ?>"
                                            class="js-resizable-input js-overlay-input"
                                            data-font-size-key="<?php echo htmlspecialchars($fontSizeKey); ?>"
                                            data-font-color-key="<?php echo htmlspecialchars($fontColorKey); ?>"
                                            data-font-style-key="<?php echo htmlspecialchars($fontStyleKey); ?>"
                                            style="<?php echo htmlspecialchars($styleCss); ?>"
                                            aria-label="<?php echo htmlspecialchars($label); ?>"
                                            autocomplete="off">
                                        <input type="hidden" name="<?php echo htmlspecialchars($fontSizeKey); ?>" value="<?php echo (int)$storedFontSize; ?>">
                                        <input type="hidden" name="<?php echo htmlspecialchars($fontColorKey); ?>" value="<?php echo htmlspecialchars($storedFontColor); ?>">
                                        <input type="hidden" name="<?php echo htmlspecialchars($fontStyleKey); ?>" value="<?php echo htmlspecialchars($storedFontStyle); ?>">
                                        <input type="hidden" name="<?php echo htmlspecialchars($pointerKey); ?>" value="<?php echo htmlspecialchars($storedPointer); ?>">
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            </div>
            <aside class="fillout-sidebar" id="filloutFieldSidebar" aria-label="Field properties" data-preset-groups="<?php echo htmlspecialchars(json_encode($presetFieldGroups), ENT_QUOTES); ?>">
                <div class="fillout-sidebar-header">
                    <h4>Field properties</h4>
                </div>
                <div class="fillout-sidebar-body">
                    <p class="fillout-field-status js-fillout-field-status">No field selected. Click a field on the preview to edit its properties.</p>
                    <div class="js-fillout-sidebar-props" hidden>
                        <div class="js-fillout-font-section">
                            <label class="fillout-sidebar-label" for="js-fillout-size-select">Font size</label>
                            <select id="js-fillout-size-select" class="pdftimesaver-input js-fillout-size-select">
                                <?php for ($fs = $filloutMinFontPx; $fs <= 24; $fs++): ?>
                                    <option value="<?php echo $fs; ?>"<?php echo $fs === $filloutDefaultFontPx ? ' selected' : ''; ?>><?php echo $fs; ?> px</option>
                                <?php endfor; ?>
                            </select>

                            <label class="fillout-sidebar-label" style="margin-top:14px;">Font color</label>
                            <div class="fillout-palette js-fillout-palette">
                                <?php
                                $paletteColors = ['#000000', '#1d4ed8', '#dc2626', '#15803d', '#b45309', '#7c3aed', '#0891b2', '#475569', '#1f2937'];
                                foreach ($paletteColors as $pc): ?>
                                    <button type="button" class="fillout-swatch js-fillout-swatch" data-color="<?php echo htmlspecialchars($pc); ?>" aria-label="Color swatch" style="background: <?php echo htmlspecialchars($pc); ?>;"></button>
                                <?php endforeach; ?>
                            </div>

                            <label class="fillout-sidebar-label" style="margin-top:14px;">Formatting</label>
                            <div class="fillout-format-row">
                                <button type="button" class="fillout-format-btn js-fillout-bold" data-style="B" title="Bold" aria-pressed="false" style="font-weight:700;">B</button>
                                <button type="button" class="fillout-format-btn js-fillout-italic" data-style="I" title="Italic" aria-pressed="false" style="font-style:italic;">I</button>
                                <button type="button" class="fillout-format-btn js-fillout-underline" data-style="U" title="Underline" aria-pressed="false" style="text-decoration:underline;">U</button>
                                <button type="button" class="fillout-format-btn js-fillout-strike" data-style="S" title="Strikeout" aria-pressed="false" style="text-decoration:line-through;">S</button>
                            </div>

                            <div class="fillout-sidebar-section">
                                <label class="fillout-sidebar-label fillout-section-heading">
                                    Connect to saved field
                                    <span class="fillout-help-icon" tabindex="0" aria-label="Connect to saved field help" data-tip="Copy a value from another preset field into this box. You can edit it afterward without changing the original.">?</span>
                                </label>
                                <select class="pdftimesaver-input js-fillout-preset-category" aria-label="Field category" style="margin-bottom:8px;">
                                    <option value="">Select category&hellip;</option>
                                </select>
                                <select class="pdftimesaver-input js-fillout-preset-field" aria-label="Preset field" style="margin-bottom:8px;" disabled>
                                    <option value="">Select field&hellip;</option>
                                </select>
                            </div>
                        </div>
                        <p class="fillout-sidebar-checkbox-note js-fillout-checkbox-note" hidden>Checkboxes have no font settings.</p>
                    </div>
                </div>
                <div class="fillout-sidebar-footer js-fillout-custombox-section">
                    <label class="fillout-sidebar-label fillout-section-heading">
                        Custom Input Box
                        <span class="fillout-help-icon" tabindex="0" aria-label="Custom input box help" data-tip="Add a free-form text box, then click on the document to place it. Drag the top bar to move; drag the corner to resize.">?</span>
                    </label>
                    <div style="display:flex; gap:8px;">
                        <button type="button" id="add-temp-field-btn" class="pdftimesaver-btn-secondary" style="flex:1;">Add Custom Field</button>
                        <button type="button" class="pdftimesaver-btn-secondary js-fillout-custom-remove" style="width:42px; min-width:42px; padding:0;" aria-label="Remove custom box" title="Remove custom box" disabled>&#128465;</button>
                    </div>
                </div>
            </aside>
            </div>
        </div>
    <?php elseif (!$hideFallbackFieldGrid && !empty($tpl['panels'])): ?>
        <?php foreach ($tpl['panels'] as $panel): ?>
            <?php
            $panelFields = [];
            foreach ((array)$tpl['fields'] as $candidateField) {
                if ((string)($candidateField['panelId'] ?? '') === (string)($panel['id'] ?? '')) {
                    $panelFields[] = $candidateField;
                }
            }
            $panelFieldCount = count($panelFields);
            ?>
            <div class="pdftimesaver-card">
                <h3 style="margin: 0 0 20px 0; color: #2c3e50; font-size: 18px; font-weight: 600;">
                    <?php
                    $panelTitle = (string)($panel['label'] ?? '');
                    echo htmlspecialchars($isTechnicalPopulateLabel($panelTitle) ? 'Fields' : $panelTitle);
                    ?>
                </h3>
                <div class="grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                    <?php foreach ($panelFields as $fieldIdx => $field): ?>
                        <?php
                        $type = $field['type'] ?? 'text';
                        $fieldKey = (string)($field['key'] ?? '');
                        $val = $values[$fieldKey] ?? '';
                        $placeholder = $normalizePopulatePlaceholder((string)($field['placeholder'] ?? ''));
                        $fieldLabel = $resolvePopulateFieldLabel((array)$field, $fieldKey);
                        $required = !empty($field['required']) ? 'required' : '';
                        $fontSizeKey = '_font_size__' . $fieldKey;
                        $storedFontSize = isset($values[$fontSizeKey]) ? (int)$values[$fontSizeKey] : $filloutDefaultFontPx;
                        $storedFontSize = max($filloutMinFontPx, min(24, $storedFontSize));
                        $fontColorKey = '_font_color__' . $fieldKey;
                        $storedFontColor = isset($values[$fontColorKey]) && $values[$fontColorKey] !== '' ? (string)$values[$fontColorKey] : '#000000';
                        $fontStyleKey = '_font_style__' . $fieldKey;
                        $storedFontStyle = isset($values[$fontStyleKey]) ? strtoupper((string)$values[$fontStyleKey]) : '';
                        $fieldStyleCss = $ptsFieldStyleCss($storedFontSize, $storedFontColor, $storedFontStyle);
                        ?>
                        <div class="pdftimesaver-form-group js-panel-field" data-panel-field-index="<?php echo (int)$fieldIdx; ?>">
                            <label class="pdftimesaver-form-label">
                                <?php echo htmlspecialchars($fieldLabel); ?>
                            </label>
                            <?php if ($type === 'textarea'): ?>
                                <textarea
                                    name="<?php echo htmlspecialchars($fieldKey); ?>"
                                    rows="3"
                                    placeholder="<?php echo htmlspecialchars($placeholder); ?>"
                                    <?php echo $required; ?>
                                    class="pdftimesaver-input js-resizable-input"
                                    data-font-size-key="<?php echo htmlspecialchars($fontSizeKey); ?>"
                                    data-font-color-key="<?php echo htmlspecialchars($fontColorKey); ?>"
                                    data-font-style-key="<?php echo htmlspecialchars($fontStyleKey); ?>"
                                    data-font-style="<?php echo htmlspecialchars($storedFontStyle); ?>"
                                    style="<?php echo $fieldStyleCss; ?>"
                                ><?php echo htmlspecialchars((string)$val); ?></textarea>
                            <?php elseif ($type === 'number' || $type === 'date'): ?>
                                <input
                                    type="<?php echo $type==='number'?'number':'date'; ?>"
                                    name="<?php echo htmlspecialchars($fieldKey); ?>"
                                    value="<?php echo htmlspecialchars((string)$val); ?>"
                                    placeholder="<?php echo htmlspecialchars($placeholder); ?>"
                                    <?php echo $required; ?>
                                    class="pdftimesaver-input js-resizable-input"
                                    data-font-size-key="<?php echo htmlspecialchars($fontSizeKey); ?>"
                                    data-font-color-key="<?php echo htmlspecialchars($fontColorKey); ?>"
                                    data-font-style-key="<?php echo htmlspecialchars($fontStyleKey); ?>"
                                    data-font-style="<?php echo htmlspecialchars($storedFontStyle); ?>"
                                    style="<?php echo $fieldStyleCss; ?>"
                                >
                            <?php elseif ($type === 'checkbox'): ?>
                                <label style="display:flex; align-items:center; gap:8px;">
                                    <input type="hidden" name="<?php echo htmlspecialchars($fieldKey); ?>" value="0">
                                    <input type="checkbox" name="<?php echo htmlspecialchars($fieldKey); ?>" value="1" <?php echo !empty($val)?'checked':''; ?> <?php echo $required; ?>>
                                    <span><?php echo !empty($val) ? 'Yes' : 'No'; ?></span>
                                </label>
                            <?php elseif ($type === 'select' && !empty($field['options']) && is_array($field['options'])): ?>
                                <select
                                    name="<?php echo htmlspecialchars($fieldKey); ?>"
                                    <?php echo $required; ?>
                                    class="pdftimesaver-input js-resizable-input"
                                    data-font-size-key="<?php echo htmlspecialchars($fontSizeKey); ?>"
                                    data-font-color-key="<?php echo htmlspecialchars($fontColorKey); ?>"
                                    data-font-style-key="<?php echo htmlspecialchars($fontStyleKey); ?>"
                                    data-font-style="<?php echo htmlspecialchars($storedFontStyle); ?>"
                                    style="<?php echo $fieldStyleCss; ?>"
                                >
                                    <option value=""><?php echo htmlspecialchars($placeholder ?: 'Select an option'); ?></option>
                                    <?php foreach ($field['options'] as $opt): ?>
                                        <option value="<?php echo htmlspecialchars((string)$opt); ?>" <?php echo ((string)$val)===(string)$opt?'selected':''; ?>><?php echo htmlspecialchars((string)$opt); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <input
                                    type="text"
                                    name="<?php echo htmlspecialchars($fieldKey); ?>"
                                    value="<?php echo htmlspecialchars((string)$val); ?>"
                                    placeholder="<?php echo htmlspecialchars($placeholder); ?>"
                                    <?php echo $required; ?>
                                    class="pdftimesaver-input js-resizable-input"
                                    data-font-size-key="<?php echo htmlspecialchars($fontSizeKey); ?>"
                                    data-font-color-key="<?php echo htmlspecialchars($fontColorKey); ?>"
                                    data-font-style-key="<?php echo htmlspecialchars($fontStyleKey); ?>"
                                    data-font-style="<?php echo htmlspecialchars($storedFontStyle); ?>"
                                    style="<?php echo $fieldStyleCss; ?>"
                                >
                            <?php endif; ?>
                            <?php if ($type !== 'checkbox'): ?>
                                <input type="hidden" name="<?php echo htmlspecialchars($fontSizeKey); ?>" value="<?php echo (int)$storedFontSize; ?>">
                                <input type="hidden" name="<?php echo htmlspecialchars($fontColorKey); ?>" value="<?php echo htmlspecialchars($storedFontColor); ?>">
                                <input type="hidden" name="<?php echo htmlspecialchars($fontStyleKey); ?>" value="<?php echo htmlspecialchars($storedFontStyle); ?>">
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php elseif (!$hideFallbackFieldGrid): ?>
        <div class="pdftimesaver-card">
            <h3 style="margin: 0 0 20px 0; color: #2c3e50; font-size: 18px; font-weight: 600;">
                Document Fields
            </h3>
            <div class="grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                <?php
                $allFields = [];
                foreach ($values as $key => $value) {
                    if (strpos((string)$key, '_font_size__') === 0
                        || strpos((string)$key, '_font_color__') === 0
                        || strpos((string)$key, '_font_style__') === 0
                        || $key === 'temporary_custom_fields_json') {
                        continue;
                    }
                    $allFields[$key] = ucwords(str_replace(['_', '-'], ' ', (string)$key));
                }
                if (empty($allFields) && !empty($tpl['fields'])) {
                    foreach ($tpl['fields'] as $field) {
                        $fieldKey = (string)($field['key'] ?? '');
                        if ($fieldKey === '') {
                            continue;
                        }
                        $allFields[$fieldKey] = $field['label'] ?? ucwords(str_replace(['_', '-'], ' ', $fieldKey));
                    }
                }
                foreach ($allFields as $key => $label):
                    $value = $values[$key] ?? '';
                    $fontSizeKey = '_font_size__' . $key;
                    $storedFontSize = isset($values[$fontSizeKey]) ? (int)$values[$fontSizeKey] : $filloutDefaultFontPx;
                    $storedFontSize = max($filloutMinFontPx, min(24, $storedFontSize));
                    $fontColorKey = '_font_color__' . $key;
                    $storedFontColor = isset($values[$fontColorKey]) && $values[$fontColorKey] !== '' ? (string)$values[$fontColorKey] : '#000000';
                    $fontStyleKey = '_font_style__' . $key;
                    $storedFontStyle = isset($values[$fontStyleKey]) ? strtoupper((string)$values[$fontStyleKey]) : '';
                    $fieldStyleCss = $ptsFieldStyleCss($storedFontSize, $storedFontColor, $storedFontStyle);
                ?>
                    <div class="pdftimesaver-form-group">
                        <label class="pdftimesaver-form-label">
                            <?php echo htmlspecialchars($isTechnicalPopulateLabel((string)$label) ? $friendlyPopulateLabelFromKey((string)$key) : (string)$label); ?>
                        </label>
                        <input type="text" name="<?php echo htmlspecialchars((string)$key); ?>" value="<?php echo htmlspecialchars((string)$value); ?>" class="pdftimesaver-input js-resizable-input" data-font-size-key="<?php echo htmlspecialchars($fontSizeKey); ?>" data-font-color-key="<?php echo htmlspecialchars($fontColorKey); ?>" data-font-style-key="<?php echo htmlspecialchars($fontStyleKey); ?>" data-font-style="<?php echo htmlspecialchars($storedFontStyle); ?>" style="<?php echo $fieldStyleCss; ?>">
                        <input type="hidden" name="<?php echo htmlspecialchars($fontSizeKey); ?>" value="<?php echo (int)$storedFontSize; ?>">
                        <input type="hidden" name="<?php echo htmlspecialchars($fontColorKey); ?>" value="<?php echo htmlspecialchars($storedFontColor); ?>">
                        <input type="hidden" name="<?php echo htmlspecialchars($fontStyleKey); ?>" value="<?php echo htmlspecialchars($storedFontStyle); ?>">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="pdftimesaver-card">
            <h3 style="margin: 0 0 10px 0; color: #2c3e50; font-size: 18px; font-weight: 600;">Preview unavailable</h3>
            <p style="margin: 0; color: #64748b;">
                This form cannot be shown in Populate because required preview files are missing.
                Re-import or re-extract this template to restore the normal visual editor.
            </p>
        </div>
    <?php endif; ?>

    <?php if (!$hasInteractivePreview): ?>
    <div class="pdftimesaver-card">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
            <h3 style="margin:0; color:#2c3e50; font-size:18px; font-weight:600;">Custom Input Boxes</h3>
            <button type="button" id="add-temp-field-btn" class="pdftimesaver-btn-secondary">Add Custom Field</button>
        </div>
        <p class="wpts-form-help" style="margin-top:8px;">Add a free-form text box. Use &times; on a box to delete it.</p>
        <div id="temp-fields-wrap" class="populate-temp-fields-wrap"></div>
    </div>
    <?php endif; ?>

</form>

    <div class="pdftimesaver-card populate-action-bar-wrap">
        <div class="populate-action-bar">
            <div class="populate-action-bar-left">
                <button type="button" id="populate-back-btn" class="pdftimesaver-btn pdftimesaver-btn-action" data-prev-template="<?php echo htmlspecialchars($prevTemplateId); ?>">Back</button>
            </div>
            <div class="populate-action-bar-right">
                <select id="export-scope-select" class="pdftimesaver-input populate-action-select" aria-label="Export scope">
                    <option value="this">This Form</option>
                    <option value="all-zip">All Forms Zipped</option>
                    <option value="all-merged">All Forms Merged</option>
                </select>
                <button type="button" id="export-action-btn" class="pdftimesaver-btn pdftimesaver-btn-action">Export</button>
                <button type="button" id="populate-next-btn" class="pdftimesaver-btn pdftimesaver-btn-action"<?php echo ($displayMode === 'single' && !$isLastForm) ? '' : ' style="display:none;"'; ?>>Next</button>
                <button type="button" id="populate-complete-btn" class="pdftimesaver-btn pdftimesaver-btn-action"<?php echo ($displayMode === 'all' || $isLastForm) ? '' : ' style="display:none;"'; ?>>Complete</button>
                <button type="button" id="populate-finish-btn" class="pdftimesaver-btn pdftimesaver-btn-action"<?php echo ($displayMode === 'all' || $isLastForm) ? '' : ' style="display:none;"'; ?>>Finish</button>
            </div>
        </div>
    </div>

<script>
(function () {
    var form = document.getElementById('populate-form');
    if (!form) return;
    var t;
    var minSize = 4;
    var FIELD_METRICS = <?php echo json_encode($fieldMetricsJs, JSON_UNESCAPED_SLASHES); ?>;
    var defaultFontPx = <?php echo (int)$filloutDefaultFontPx; ?>;
    var maxSize = 24;
    var exportBtn = document.getElementById('export-action-btn');
    var exportScope = document.getElementById('export-scope-select');
    var thisFormOptionTemplate = exportScope ? exportScope.querySelector('option[value="this"]') : null;
    var displayModeSelect = document.getElementById('display-mode-select');
    var nextBtn = document.getElementById('populate-next-btn');
    var completeBtn = document.getElementById('populate-complete-btn');
    var finishBtn = document.getElementById('populate-finish-btn');
    var tempWrap = document.getElementById('temp-fields-wrap');
    var tempInput = document.getElementById('temporary-custom-fields-json');
    var addTempBtn = document.getElementById('add-temp-field-btn');
    var projectId = <?php echo json_encode((string)$projectDocument['projectId']); ?>;
    var currentPdId = <?php echo json_encode((string)$projectDocument['id']); ?>;
    var mergedPopulateTitle = <?php echo json_encode(trim($populateProjectName) !== '' ? ('Populate - ' . $populateProjectName) : 'Populate'); ?>;
    var nextTemplateId = <?php echo json_encode($nextTemplateId); ?>;
    var hasNextForm = <?php echo $nextTemplateId !== '' ? 'true' : 'false'; ?>;
    var tempFields = [];
    var contentTitleEl = document.querySelector('.pdftimesaver-content-title');
    if (contentTitleEl && mergedPopulateTitle) {
        contentTitleEl.textContent = mergedPopulateTitle;
    }

    function getAutosaveStatusEl() {
        var el = document.getElementById('autosave-status');
        if (!el) {
            el = document.createElement('div');
            el.id = 'autosave-status';
            el.setAttribute('style', 'position:fixed; right:18px; bottom:18px; font-size:12px; color:#fff; background:#0f766e; border-radius:999px; padding:6px 10px; z-index:9999; box-shadow:0 6px 16px rgba(15,23,42,0.28); display:none;');
            document.body.appendChild(el);
        }
        return el;
    }

    function showSaved(msg) {
        if (!msg || msg === 'Saved') {
            return;
        }
        var el = getAutosaveStatusEl();
        el.style.display = '';
        el.style.background = '#0f766e';
        el.textContent = msg;
        setTimeout(function () {
            if (el.textContent === msg) {
                el.textContent = '';
                el.style.display = 'none';
            }
        }, 2000);
    }

    function showSaveError(msg) {
        var el = getAutosaveStatusEl();
        el.style.display = '';
        el.style.background = '#b91c1c';
        el.textContent = msg || 'Autosave failed';
        setTimeout(function () {
            if (el.textContent === (msg || 'Autosave failed')) {
                el.textContent = '';
                el.style.display = 'none';
            }
        }, 4000);
    }

    function autoSaveNow() {
        var values = {};
        var elements = form && form.elements ? Array.prototype.slice.call(form.elements) : [];
        elements.forEach(function (el) {
            if (!el || !el.name || el.disabled) { return; }
            if (el.type === 'radio' && !el.checked) { return; }
            if (el.type === 'checkbox') {
                values[el.name] = el.checked ? '1' : '0';
                return;
            }
            values[el.name] = String(el.value == null ? '' : el.value);
        });
        var payload = {
            ajax: 1,
            projectDocumentId: currentPdId,
            values: values
        };
        return fetch(form.action, {
            method: 'POST',
            body: JSON.stringify(payload),
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        })
            .then(function (r) { return r.text(); })
            .then(function (rawText) {
                var text = String(rawText || '');
                var json = null;
                try {
                    json = JSON.parse(text);
                } catch (e) {
                    var match = text.match(/(\{[\s\S]*\})\s*$/);
                    if (match && match[1]) {
                        try { json = JSON.parse(match[1]); } catch (_e) { json = null; }
                    }
                }
                if (!json && /"success"\s*:\s*true/i.test(text)) {
                    json = { success: true };
                }
                return json;
            })
            .then(function (j) {
                if (j && j.success) {
                    showSaved('Saved');
                    return true;
                }
                showSaveError('Autosave failed');
                return false;
            })
            .catch(function () {
                showSaveError('Autosave failed');
                return false;
            });
    }

    function clamp(n, low, high) {
        return Math.max(low, Math.min(high, n));
    }

    function overlaySeedFontPx(fontPx, fieldEl, fallbackPx) {
        var px = parseFloat(fontPx || '0') || FIELD_METRICS.DEFAULT_FONT_PX || 13;
        return clamp(parseInt(Math.round(px), 10) || fallbackPx || defaultFontPx, minSize, maxSize);
    }

    function getStoredOverlayFontPx(input) {
        if (!input) { return 0; }
        if (input.classList.contains('js-temp-value')) {
            var tempId = input.getAttribute('data-temp-id');
            var tf = tempId ? tempFieldById(tempId) : null;
            return (tf && tf.fontSize >= minSize) ? tf.fontSize : 0;
        }
        var key = input.getAttribute('data-font-size-key') || '';
        var hidden = key ? findHiddenFontSizeInput(key) : null;
        return hidden ? parseInt(hidden.value || '0', 10) : 0;
    }

    function applyOverlayFieldFont(input, fEl, h) {
        if (!input || !fEl) { return; }
        var stored = getStoredOverlayFontPx(input);
        var seededPx = parseFloat(fEl.getAttribute('data-pt') || '') || FIELD_METRICS.DEFAULT_FONT_PX || 13;
        var fallbackPx = overlaySeedFontPx(seededPx, fEl, defaultFontPx);
        var chosenPx = (stored >= minSize) ? stored : fallbackPx;
        // Keep preview font faithful to the chosen size; do not silently shrink
        // on selection/layout changes. Overflow handling still runs on input edits.
        var displayPx = clamp(chosenPx, minSize, maxSize);
        input.style.fontSize = displayPx + 'px';
        setPreferredFontSize(input, chosenPx);
        syncFilloutFieldValueSpan(fEl, input);
    }

    // Legacy repair: an earlier build stamped every field with the same default
    // font size (15), which made many imported fields oversized.
    // Only reset when *all* stored sizes are that default value.
    function normalizeLegacyOverlayDefaultSizes() {
        var textInputs = Array.prototype.slice.call(form.querySelectorAll('.js-resizable-input:not(.js-temp-value)'));
        if (textInputs.length < 12) { return; }
        var withStored = [];
        var defaultStored = 0;
        textInputs.forEach(function (input) {
            var stored = getStoredOverlayFontPx(input);
            if (stored < minSize) { return; }
            withStored.push({ input: input, stored: stored });
            if (stored === defaultFontPx) {
                defaultStored += 1;
            }
        });
        if (!withStored.length) { return; }
        var defaultRatio = defaultStored / withStored.length;
        // Trigger repair when the dataset is clearly legacy-skewed toward a single
        // stamped default size, while preserving genuinely curated documents.
        if (defaultRatio < 0.85) { return; }
        var legacyFallback = clamp(FIELD_METRICS.DEFAULT_FONT_PX || 13, minSize, maxSize);
        withStored.forEach(function (row) {
            var key = row.input.getAttribute('data-font-size-key') || '';
            var hidden = key ? findHiddenFontSizeInput(key) : null;
            if (hidden) { hidden.value = '0'; }
            row.input.style.fontSize = legacyFallback + 'px';
            setPreferredFontSize(row.input, legacyFallback);
        });
    }

    function getInputFontSize(el) {
        if (el && el.classList && el.classList.contains('js-overlay-input')) {
            var storedOverlay = getStoredOverlayFontPx(el);
            if (storedOverlay >= minSize) {
                return clamp(storedOverlay, minSize, maxSize);
            }
        }
        var raw = window.getComputedStyle(el).fontSize || (defaultFontPx + 'px');
        var n = parseInt(raw, 10);
        if (!n || n < minSize) return defaultFontPx;
        return clamp(n, minSize, maxSize);
    }

    function getPreferredFontSize(el) {
        var raw = parseInt(el.getAttribute('data-preferred-font-size') || '', 10);
        if (!raw || raw < minSize) {
            raw = getInputFontSize(el);
            el.setAttribute('data-preferred-font-size', String(raw));
        }
        return clamp(raw, minSize, maxSize);
    }

    function setPreferredFontSize(el, size) {
        el.setAttribute('data-preferred-font-size', String(clamp(size, minSize, maxSize)));
    }

    function findHiddenFontSizeInput(key) {
        if (!key) return null;
        return form.querySelector('input[type="hidden"][name="' + CSS.escape(key) + '"]');
    }

    function updateSizeMeta(container, size) {
        var meta = container ? container.querySelector('.js-size-meta') : null;
        if (meta) meta.textContent = size + 'px';
    }

    function saveFontSizeToHidden(el, size) {
        var tempId = el.getAttribute('data-temp-id');
        if (tempId) {
            updateTempField(tempId, { fontSize: size });
            syncTempFields();
            return;
        }
        var key = el.getAttribute('data-font-size-key');
        var hidden = findHiddenFontSizeInput(key);
        if (hidden) hidden.value = String(size);
    }

    function setInputFontSize(el, nextSize) {
        var size = clamp(nextSize, minSize, maxSize);
        el.style.fontSize = size + 'px';
        saveFontSizeToHidden(el, size);
        var group = el.closest('.pdftimesaver-form-group');
        updateSizeMeta(group, size);
        var fieldEl = el.closest('.fillout-field');
        if (fieldEl) { syncFilloutFieldValueSpan(fieldEl, el); }
    }

    function estimateOverflow(el) {
        if (el.tagName === 'TEXTAREA') {
            return (el.scrollHeight > el.clientHeight + 2) || (el.scrollWidth > el.clientWidth + 2);
        }
        var val = String(el.value || '');
        if (!val) return false;
        var approxCharPx = getInputFontSize(el) * 0.55;
        var maxChars = Math.max(1, Math.floor(el.clientWidth / approxCharPx));
        return val.length > maxChars;
    }

    function autoShrinkIfOverflow(el) {
        if (!el.classList.contains('js-resizable-input')) return;
        var preferred = getPreferredFontSize(el);
        var current = parseInt(window.getComputedStyle(el).fontSize, 10) || preferred;
        var adjusted = clamp(current, minSize, maxSize);
        // Step down progressively while typing so it does not jump abruptly.
        var steps = 0;
        while (estimateOverflow(el) && adjusted > minSize && steps < 3) {
            adjusted -= 1;
            el.style.fontSize = adjusted + 'px';
            steps += 1;
        }
        if (estimateOverflow(el) && adjusted <= minSize) {
            el.classList.add('populate-overflow-warning');
        } else {
            el.classList.remove('populate-overflow-warning');
        }
        var fitted = clamp(parseInt(window.getComputedStyle(el).fontSize, 10) || adjusted, minSize, maxSize);
        setPreferredFontSize(el, fitted);
        saveFontSizeToHidden(el, fitted);
        var fieldEl = el.closest('.fillout-field');
        if (fieldEl) { syncFilloutFieldValueSpan(fieldEl, el); }
        refreshFontSizeIndicator(el);
    }

    function enforceNoOverflow(el) {
        if (!el || !el.classList || !el.classList.contains('js-resizable-input')) return;
        autoShrinkIfOverflow(el);
        if (el.classList.contains('populate-overflow-warning')) {
            var fallback = el.getAttribute('data-last-fit-value');
            if (fallback !== null && fallback !== undefined) {
                el.value = fallback;
            }
            autoShrinkIfOverflow(el);
            el.classList.add('populate-overflow-warning');
            return;
        }
        el.setAttribute('data-last-fit-value', String(el.value || ''));
    }

    // Keep every font-size indicator (sidebar dropdown + inline grid meta/select) in
    // sync with the input's actual rendered size, e.g. after auto-shrink from
    // over-typing -- otherwise the indicator looks "stuck".
    function refreshFontSizeIndicator(el) {
        if (!el) return;
        var actual = parseInt(window.getComputedStyle(el).fontSize, 10) || 0;
        var clamped = clamp(actual || minSize, minSize, maxSize);
        var group = el.closest('.pdftimesaver-form-group');
        if (group) {
            updateSizeMeta(group, actual || clamped);
            var inlineSel = group.querySelector('.js-inline-size');
            if (inlineSel) inlineSel.value = String(clamped);
        }
        if (filloutSidebar) {
            var sizeSel = filloutSidebar.querySelector('.js-fillout-size-select');
            if (sizeSel) sizeSel.value = String(clamped);
        }
    }

    function parseTempFields() {
        try {
            var parsed = JSON.parse(tempInput.value || '[]');
            if (Array.isArray(parsed)) {
                tempFields = parsed;
            } else {
                tempFields = [];
            }
        } catch (e) {
            tempFields = [];
        }
        tempFields = tempFields.map(function (f) { return normalizeTempField(f); });
    }

    function syncTempFields() {
        tempInput.value = JSON.stringify(tempFields);
    }

    var PAGE_W_MM_T = 215.9;
    var PAGE_H_MM_T = 279.4;
    // Match a real single-line text overlay on this form (same mm/pt as PDF preview).
    function getReferenceOverlayTextField() {
        if (typeof filloutSelectedFieldEl !== 'undefined' && filloutSelectedFieldEl
            && !filloutSelectedFieldEl.classList.contains('fillout-temp-field')
            && !filloutSelectedFieldEl.querySelector('.js-overlay-checkbox')) {
            var sw = parseFloat(filloutSelectedFieldEl.dataset.w || '0');
            var sh = parseFloat(filloutSelectedFieldEl.dataset.h || '0');
            if (sw >= 12 && sh >= 2 && sh <= 5.5) { return filloutSelectedFieldEl; }
        }
        var found = null;
        document.querySelectorAll('.fillout-field.js-overlay-field:not(.fillout-temp-field)').forEach(function (fEl) {
            if (found || fEl.querySelector('.js-overlay-checkbox')) { return; }
            var w = parseFloat(fEl.dataset.w || '0');
            var h = parseFloat(fEl.dataset.h || '0');
            if (w >= 30 && h >= 2 && h <= 5.5) { found = fEl; }
        });
        return found;
    }
    function metricsFromOverlayField(fEl) {
        return {
            width: parseFloat(fEl.dataset.w || '45') || 45,
            height: parseFloat(fEl.dataset.h || '3.18') || 3.18,
            fontPt: parseFloat(fEl.dataset.pt || String(FIELD_METRICS.DEFAULT_FONT_PX || 13)) || (FIELD_METRICS.DEFAULT_FONT_PX || 13)
        };
    }
    function getDefaultTempFieldMetrics() {
        var ref = getReferenceOverlayTextField();
        return ref ? metricsFromOverlayField(ref) : { width: 45, height: 3.18, fontPt: (FIELD_METRICS.DEFAULT_FONT_PX || 13) };
    }
    function getTempPlacementDefaults() {
        return getDefaultTempFieldMetrics();
    }
    function repairStoredTempFieldSizes() {
        if (!tempFields.length) { return false; }
        var before = JSON.stringify(tempFields);
        tempFields = tempFields.map(function (f) { return normalizeTempField(f); });
        if (JSON.stringify(tempFields) === before) { return false; }
        syncTempFields();
        return true;
    }
    function sanitizeTempBoxMm(width, height, metrics) {
        metrics = metrics || getDefaultTempFieldMetrics();
        var w = parseFloat(width);
        var h = parseFloat(height);
        if (!isFinite(w) || w < 8 || w > 175) { w = metrics.width; }
        // Heights >= 6mm are almost always pt (7–12) saved as mm — real PDF boxes are ~3–4mm tall.
        if (!isFinite(h) || h < 1.5 || h >= 6) { h = metrics.height; }
        return { width: w, height: h };
    }
    function clampNum(v, lo, hi, dflt) {
        var n = parseFloat(v);
        if (!isFinite(n)) { n = dflt; }
        return Math.max(lo, Math.min(hi, n));
    }
    function previewActive() { return !!document.querySelector('.fillout-page'); }
    function getTempPageOverlay(page) { return document.getElementById('fillout-overlay-' + page); }
    function getTempPageScale(page) {
        var img = document.getElementById('fillout-bg-' + page);
        if (!img) { return null; }
        var w = img.clientWidth || img.naturalWidth;
        var h = img.clientHeight || img.naturalHeight;
        if (!w || !h) { return null; }
        return { scaleX: w / PAGE_W_MM_T, scaleY: h / PAGE_H_MM_T };
    }
    function buildTempInputStyleCss(f) {
        var css = 'color:' + String(f.fontColor || '#000000') + ';';
        var style = String(f.fontStyle || '').toUpperCase();
        if (style.indexOf('B') !== -1) { css += 'font-weight:700;'; }
        if (style.indexOf('I') !== -1) { css += 'font-style:italic;'; }
        var deco = [];
        if (style.indexOf('U') !== -1) { deco.push('underline'); }
        if (style.indexOf('S') !== -1) { deco.push('line-through'); }
        if (deco.length) { css += 'text-decoration:' + deco.join(' ') + ';'; }
        return css;
    }
    function normalizeTempField(f) {
        f = f || {};
        var metrics = getDefaultTempFieldMetrics();
        var box = sanitizeTempBoxMm(f.width, f.height, metrics);
        return {
            id: String(f.id || ('tmp_' + Date.now() + '_' + Math.floor(Math.random() * 100000))),
            label: String(f.label || 'Custom Input Box'),
            value: String(f.value || ''),
            page: Math.max(1, Math.min(50, parseInt(f.page || 1, 10) || 1)),
            left: clampNum(f.left, 0, 210, 20),
            top: clampNum(f.top, 0, 273, 20),
            width: clampNum(box.width, 2, 205, metrics.width),
            height: clampNum(box.height, 2, 6, metrics.height),
            fontPt: clampNum(f.fontPt != null ? f.fontPt : f.pt, 4, 24, metrics.fontPt),
            fontSize: clamp(parseInt(f.fontSize || 0, 10) || 0, 0, maxSize),
            fontColor: String(f.fontColor || '#000000'),
            fontStyle: String(f.fontStyle || '').toUpperCase().replace(/[^BIUS]/g, '')
        };
    }
    function tempFieldById(id) {
        for (var i = 0; i < tempFields.length; i++) {
            var n = normalizeTempField(tempFields[i]);
            if (n.id === id) { return n; }
        }
        return null;
    }

    function updateTempField(id, patch) {
        tempFields = tempFields.map(function (f) {
            var n = normalizeTempField(f);
            if (n.id !== id) { return f; }
            return normalizeTempField(Object.assign({}, n, patch));
        });
        syncTempFields();
    }

    function removeTempField(id) {
        tempFields = tempFields.filter(function (f) { return normalizeTempField(f).id !== id; });
        syncTempFields();
        if (filloutSelectedFieldEl && filloutSelectedFieldEl.getAttribute('data-temp-id') === id) {
            clearFilloutFieldSelection();
        }
        renderTempFields();
        autoSaveNow();
    }

    function renderTempFields() {
        if (previewActive()) {
            if (tempWrap) { tempWrap.style.display = 'none'; }
            renderTempFieldsOnPreview();
            return;
        }
        if (tempWrap) { tempWrap.style.display = ''; }
        renderTempFieldsLegacy();
    }

    function renderTempFieldsOnPreview() {
        document.querySelectorAll('.fillout-overlay .fillout-temp-field').forEach(function (n) { n.remove(); });
        tempFields = tempFields.map(function (raw) { return normalizeTempField(raw); });
        syncTempFields();
        tempFields.forEach(function (f) {
            var overlay = getTempPageOverlay(f.page) || getTempPageOverlay(1);
            if (!overlay) { return; }
            var el = document.createElement('div');
            el.className = 'fillout-field fillout-temp-field js-overlay-field';
            el.setAttribute('data-temp-id', f.id);
            el.setAttribute('data-field-label', f.label);
            el.setAttribute('title', f.label);
            el.setAttribute('data-x', String(f.left));
            el.setAttribute('data-y', String(f.top));
            el.setAttribute('data-w', String(f.width));
            el.setAttribute('data-h', String(f.height));
            el.setAttribute('data-pt', String(f.fontPt));
            el.innerHTML =
                '<span class="fillout-field-value" aria-hidden="true">' + String(f.value).replace(/</g, '&lt;') + '</span>' +
                '<input type="text" class="js-resizable-input js-overlay-input js-temp-value" data-temp-id="' + String(f.id).replace(/"/g, '&quot;') + '"' +
                ' data-font-style="' + String(f.fontStyle || '').replace(/"/g, '&quot;') + '"' +
                ' value="' + String(f.value).replace(/"/g, '&quot;') + '"' +
                ' style="' + buildTempInputStyleCss(f).replace(/"/g, '&quot;') + '"' +
                ' aria-label="' + String(f.label).replace(/"/g, '&quot;') + '" autocomplete="off">';
            overlay.appendChild(el);
            wireTempFieldOnPreview(el);
        });
        if (typeof layoutFilloutPreview === 'function') { layoutFilloutPreview(); }
    }

    function wireTempFieldOnPreview(el) {
        var id = el.getAttribute('data-temp-id');
        var input = el.querySelector('.js-temp-value');
        if (!input) { return; }
        input.addEventListener('input', function () {
            updateTempField(id, { value: input.value || '' });
            syncFilloutFieldValueSpan(el, input);
            autoShrinkIfOverflow(input);
            clearTimeout(t);
            t = setTimeout(autoSaveNow, 900);
        });
        syncFilloutFieldValueSpan(el, input);
        installTempFieldDragResize(el);
    }

    var filloutTempActive = null;
    function installFilloutTempDocListeners() {
        if (window._filloutTempDocListeners) { return; }
        window._filloutTempDocListeners = true;
        document.addEventListener('mousemove', function (ev) {
            if (!filloutTempActive) { return; }
            var d = filloutTempActive;
            var dx = ev.clientX - d.startX;
            var dy = ev.clientY - d.startY;
            if (d.mode === 'move') {
                d.el.style.left = (d.baseLeft + dx) + 'px';
                d.el.style.top = (d.baseTop + dy) + 'px';
            } else if (d.mode === 'resize') {
                var nw = Math.max(12, d.baseW + dx);
                var nh = Math.max(8, d.baseH + dy);
                d.el.style.width = nw + 'px';
                d.el.style.height = nh + 'px';
                if (d.input) {
                    var fitPx = Math.max(minSize, Math.floor(nh) - 2);
                    d.input.style.fontSize = Math.min(fitPx, maxSize) + 'px';
                    autoShrinkIfOverflow(d.input);
                }
            }
        });
        document.addEventListener('mouseup', function () {
            if (!filloutTempActive) { return; }
            commitTempFieldGeometry(filloutTempActive.el);
            filloutTempActive = null;
        });
    }

    function commitTempFieldGeometry(el) {
        if (!el) { return; }
        var id = el.getAttribute('data-temp-id');
        var pageEl = el.closest('.fillout-page');
        if (!id || !pageEl) { return; }
        var page = parseInt(pageEl.getAttribute('data-page') || '1', 10) || 1;
        var sc = getTempPageScale(page);
        if (!sc) { return; }
        var leftPx = parseFloat(el.style.left) || 0;
        var topPx = parseFloat(el.style.top) || 0;
        var wPx = parseFloat(el.style.width) || 0;
        var hPx = parseFloat(el.style.height) || 0;
        var metrics = getDefaultTempFieldMetrics();
        var patch = {
            page: page,
            left: clampNum(leftPx / sc.scaleX, 0, 210, 20),
            top: clampNum(topPx / sc.scaleY, 0, 273, 20),
            width: clampNum(wPx / sc.scaleX, 2, 205, metrics.width),
            height: clampNum(hPx / sc.scaleY, 2, 6, metrics.height)
        };
        updateTempField(id, patch);
        el.setAttribute('data-x', String(patch.left));
        el.setAttribute('data-y', String(patch.top));
        el.setAttribute('data-w', String(patch.width));
        el.setAttribute('data-h', String(patch.height));
        autoSaveNow();
    }

    function installTempFieldDragResize(el) {
        if (!el || el.getAttribute('data-temp-drag-bound') === '1') { return; }
        el.setAttribute('data-temp-drag-bound', '1');
        installFilloutTempDocListeners();
        var moveHandle = document.createElement('div');
        moveHandle.className = 'fillout-temp-move-handle';
        moveHandle.title = 'Drag to move';
        el.appendChild(moveHandle);
        var resizeHandle = document.createElement('div');
        resizeHandle.className = 'fillout-temp-resize-handle';
        resizeHandle.title = 'Drag to resize';
        el.appendChild(resizeHandle);
        var input = el.querySelector('.js-temp-value');
        moveHandle.addEventListener('mousedown', function (ev) {
            if (ev.button !== 0) { return; }
            ev.preventDefault();
            ev.stopPropagation();
            selectFilloutOverlayField(el);
            filloutTempActive = {
                mode: 'move',
                el: el,
                input: input,
                startX: ev.clientX,
                startY: ev.clientY,
                baseLeft: parseFloat(el.style.left) || 0,
                baseTop: parseFloat(el.style.top) || 0
            };
        });
        resizeHandle.addEventListener('mousedown', function (ev) {
            if (ev.button !== 0) { return; }
            ev.preventDefault();
            ev.stopPropagation();
            selectFilloutOverlayField(el);
            filloutTempActive = {
                mode: 'resize',
                el: el,
                input: input,
                startX: ev.clientX,
                startY: ev.clientY,
                baseW: parseFloat(el.style.width) || 0,
                baseH: parseFloat(el.style.height) || 0
            };
        });
    }

    function renderTempFieldsLegacy() {
        if (!tempWrap) { return; }
        tempWrap.innerHTML = '';
        tempFields.forEach(function (raw) {
            var f = normalizeTempField(raw);
            var el = document.createElement('div');
            el.className = 'pdftimesaver-form-group';
            el.style.margin = '10px';
            el.innerHTML =
                '<label class="pdftimesaver-form-label">' + String(f.label).replace(/</g, '&lt;') + '</label>' +
                '<input type="text" class="pdftimesaver-input js-temp-value" value="' + String(f.value).replace(/"/g, '&quot;') + '" placeholder="Type value" style="font-size:' + f.fontSize + 'px;">' +
                '<div class="populate-size-controls"><button type="button" class="pdftimesaver-btn-secondary js-temp-remove">Remove</button></div>';
            tempWrap.appendChild(el);
            el.querySelector('.js-temp-value').addEventListener('input', function (ev) {
                updateTempField(f.id, { value: ev.target.value || '' });
                clearTimeout(t);
                t = setTimeout(autoSaveNow, 900);
            });
            el.querySelector('.js-temp-remove').addEventListener('click', function () {
                removeTempField(f.id);
            });
        });
    }

    function buildExportUrl(exportChoice, pdId) {
        var choice = exportChoice || 'this';
        var scope = 'this';
        var format = 'pdf';
        if (choice === 'all-zip') {
            scope = 'all';
            format = 'zip';
        } else if (choice === 'all-merged') {
            scope = 'all';
            format = 'merged';
        }
        return '?route=actions/export-project-forms&projectId=' + encodeURIComponent(projectId) +
            '&pd=' + encodeURIComponent(pdId || currentPdId) +
            '&scope=' + encodeURIComponent(scope) +
            '&format=' + encodeURIComponent(format);
    }

    function triggerExportDownload(url) {
        var href = String(url || '').trim();
        if (!href) { return; }
        var a = document.createElement('a');
        a.href = href;
        // Keep Fill Out state in current tab; downloads/opening happen off-tab.
        a.target = '_blank';
        a.rel = 'noopener noreferrer';
        document.body.appendChild(a);
        a.click();
        a.remove();
    }

    function updateActionBarState() {
        var mode = displayModeSelect ? displayModeSelect.value : 'single';
        var isAllMode = mode === 'all';
        var isLast = !hasNextForm;

        if (exportScope) {
            var thisFormOption = exportScope.querySelector('option[value="this"]');
            // In All Forms mode, remove "This Form" from the UI entirely so it
            // cannot be perceived as selectable.
            if (isAllMode) {
                if (thisFormOption) {
                    thisFormOption.remove();
                    thisFormOption = null;
                }
            } else if (!thisFormOption && thisFormOptionTemplate) {
                exportScope.insertBefore(thisFormOptionTemplate, exportScope.firstChild);
                thisFormOption = thisFormOptionTemplate;
            }
            if (thisFormOption) {
                thisFormOption.disabled = false;
            }
            if (isAllMode || isLast) {
                if (exportScope.value === 'this') {
                    exportScope.value = 'all-zip';
                }
            } else if (exportScope.value !== 'this') {
                exportScope.value = 'this';
            }
            // Keep export mode selectable so users can switch zip vs merged in All Forms mode.
            exportScope.disabled = false;
        }
        if (nextBtn) {
            nextBtn.style.display = (!isAllMode && hasNextForm) ? '' : 'none';
        }
        if (completeBtn) {
            completeBtn.style.display = (isAllMode || isLast) ? '' : 'none';
        }
        if (finishBtn) {
            finishBtn.style.display = (isAllMode || isLast) ? '' : 'none';
        }
    }

    function updateDefaultExportScope() {
        updateActionBarState();
    }

    if (displayModeSelect) {
        displayModeSelect.addEventListener('change', function () {
            var url = new URL(window.location.href);
            url.searchParams.set('displayMode', displayModeSelect.value === 'all' ? 'all' : 'single');
            window.location.assign(url.toString());
        });
    }

    updateActionBarState();

    var placingTempField = false;
    var tempPlaceHint = null;
    var tempPlaceGhost = null;

    function exitTempPlacement() {
        placingTempField = false;
        document.querySelectorAll('.fillout-page').forEach(function (pg) { pg.style.cursor = ''; });
        if (tempPlaceHint && tempPlaceHint.parentNode) { tempPlaceHint.parentNode.removeChild(tempPlaceHint); }
        if (tempPlaceGhost && tempPlaceGhost.parentNode) { tempPlaceGhost.parentNode.removeChild(tempPlaceGhost); }
        tempPlaceHint = null;
        tempPlaceGhost = null;
        document.removeEventListener('mousemove', moveTempGhost, true);
    }

    function moveTempGhost(ev) {
        if (!placingTempField || !tempPlaceGhost) { return; }
        var pageEl = ev.target.closest ? ev.target.closest('.fillout-page') : null;
        if (!pageEl) { tempPlaceGhost.style.display = 'none'; return; }
        var page = parseInt(pageEl.getAttribute('data-page') || '1', 10) || 1;
        var sc = getTempPageScale(page);
        if (!sc) { tempPlaceGhost.style.display = 'none'; return; }
        var overlay = getTempPageOverlay(page);
        if (!overlay) { tempPlaceGhost.style.display = 'none'; return; }
        if (tempPlaceGhost.parentNode !== overlay) { overlay.appendChild(tempPlaceGhost); }
        var rect = overlay.getBoundingClientRect();
        var defs = getTempPlacementDefaults();
        var wPx = defs.width * sc.scaleX;
        var hPx = defs.height * sc.scaleY;
        var x = Math.max(0, Math.min(rect.width - wPx, ev.clientX - rect.left - wPx / 2));
        var y = Math.max(0, Math.min(rect.height - hPx, ev.clientY - rect.top - hPx / 2));
        tempPlaceGhost.style.display = '';
        tempPlaceGhost.style.left = x + 'px';
        tempPlaceGhost.style.top = y + 'px';
        tempPlaceGhost.style.width = wPx + 'px';
        tempPlaceGhost.style.height = hPx + 'px';
    }

    function startTempPlacement() {
        if (!previewActive()) {
            // Legacy fallback: just append a default box.
            var row = normalizeTempField(getTempPlacementDefaults());
            tempFields.push(row);
            syncTempFields();
            renderTempFields();
            autoSaveNow();
            return;
        }
        if (placingTempField) { exitTempPlacement(); return; }
        placingTempField = true;
        document.querySelectorAll('.fillout-page').forEach(function (pg) { pg.style.cursor = 'crosshair'; });
        // Ghost box that follows the cursor (attaches to mouse like Form Manager).
        tempPlaceGhost = document.createElement('div');
        tempPlaceGhost.className = 'fillout-field fillout-temp-field';
        tempPlaceGhost.style.pointerEvents = 'none';
        tempPlaceGhost.style.opacity = '0.65';
        tempPlaceGhost.style.display = 'none';
        tempPlaceGhost.innerHTML = '<input type="text" class="js-overlay-input" readonly tabindex="-1" style="width:100%;height:100%;border:none;background:transparent;padding:1px 2px;margin:0;line-height:1.05;pointer-events:none;">';
        tempPlaceHint = document.createElement('div');
        tempPlaceHint.textContent = 'Click on the document to drop your Custom Input Box (Esc to cancel)';
        tempPlaceHint.setAttribute('style', 'position:fixed;top:16px;left:50%;transform:translateX(-50%);z-index:9999;background:#1e3a5f;color:#fff;padding:8px 16px;border-radius:8px;font-size:13px;box-shadow:0 2px 10px rgba(0,0,0,0.25);');
        document.body.appendChild(tempPlaceHint);
        document.addEventListener('mousemove', moveTempGhost, true);
    }

    function placeTempFieldFromEvent(ev) {
        var pageEl = ev.target.closest('.fillout-page');
        if (!pageEl) { return; }
        var page = parseInt(pageEl.getAttribute('data-page') || '1', 10) || 1;
        var img = document.getElementById('fillout-bg-' + page);
        var sc = getTempPageScale(page);
        if (!img || !sc) { exitTempPlacement(); return; }
        var rect = img.getBoundingClientRect();
        // Drop so the box is centered on the cursor (matches the ghost preview).
        var defs = getTempPlacementDefaults();
        var px = ev.clientX - rect.left - (defs.width * sc.scaleX) / 2;
        var py = ev.clientY - rect.top - (defs.height * sc.scaleY) / 2;
        var mmLeft = clampNum(px / sc.scaleX, 0, 205, 20);
        var mmTop = clampNum(py / sc.scaleY, 0, 270, 20);
        var row = normalizeTempField({
            page: page,
            left: mmLeft,
            top: mmTop,
            width: defs.width,
            height: defs.height,
            fontPt: defs.fontPt
        });
        tempFields.push(row);
        syncTempFields();
        renderTempFields();
        autoSaveNow();
        exitTempPlacement();
    }

    if (addTempBtn) {
        addTempBtn.addEventListener('click', startTempPlacement);
    }

    document.addEventListener('click', function (ev) {
        if (!placingTempField) { return; }
        if (ev.target.closest && ev.target.closest('#add-temp-field-btn')) { return; }
        if (ev.target.closest && ev.target.closest('.fillout-page')) {
            ev.preventDefault();
            ev.stopPropagation();
            placeTempFieldFromEvent(ev);
        }
    }, true);

    document.addEventListener('keydown', function (ev) {
        if (placingTempField && ev.key === 'Escape') { exitTempPlacement(); }
    });

    if (exportBtn && exportScope) {
        exportBtn.addEventListener('click', function () {
            triggerExportDownload(buildExportUrl(exportScope.value || 'this', currentPdId));
        });
    }

    document.querySelectorAll('.js-export-this-form').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var pdId = String(btn.getAttribute('data-pd-id') || '').trim();
            if (!pdId) return;
            triggerExportDownload(buildExportUrl('this', pdId));
        });
    });

    if (nextBtn) {
        nextBtn.addEventListener('click', function () {
            var tid = String(nextTemplateId || '').trim();
            if (!tid) return;
            autoSaveNow().then(function () {
                var post = document.createElement('form');
                post.method = 'POST';
                post.action = '?route=actions/open-project-form';
                post.innerHTML =
                    '<input type="hidden" name="projectId" value="' + String(projectId).replace(/"/g, '&quot;') + '">' +
                    '<input type="hidden" name="templateId" value="' + tid.replace(/"/g, '&quot;') + '">';
                document.body.appendChild(post);
                post.submit();
            });
        });
    }

    var backBtn = document.getElementById('populate-back-btn');
    if (backBtn) {
        backBtn.addEventListener('click', function () {
            var tid = String(backBtn.getAttribute('data-prev-template') || '').trim();
            autoSaveNow().then(function () {
                if (!tid) {
                    window.location.href = '?route=project&id=' + encodeURIComponent(projectId);
                    return;
                }
                var post = document.createElement('form');
                post.method = 'POST';
                post.action = '?route=actions/open-project-form';
                post.innerHTML =
                    '<input type="hidden" name="projectId" value="' + String(projectId).replace(/"/g, '&quot;') + '">' +
                    '<input type="hidden" name="templateId" value="' + tid.replace(/"/g, '&quot;') + '">';
                document.body.appendChild(post);
                post.submit();
            });
        });
    }

    if (completeBtn) {
        completeBtn.addEventListener('click', function () {
            autoSaveNow().then(function () {
                var fd = new FormData();
                fd.append('documentId', currentPdId);
                fd.append('status', 'completed');
                return fetch('?route=actions/update-document-status', { method: 'POST', body: fd })
                    .then(function (r) { return r.json(); })
                    .then(function (j) {
                        if (j && j.success) {
                            showSaved('Complete');
                        } else {
                            showSaveError('Complete failed');
                        }
                    });
            }).catch(function () { showSaveError('Complete failed'); });
        });
    }

    if (finishBtn) {
        finishBtn.addEventListener('click', function () {
            autoSaveNow().then(function () {
                window.location.href = '?route=project&id=' + encodeURIComponent(projectId);
            });
        });
    }

    parseTempFields();
    syncTempFields();
    renderTempFields();
    normalizeLegacyOverlayDefaultSizes();
    form.querySelectorAll('.js-resizable-input').forEach(function (el) {
        setPreferredFontSize(el, getInputFontSize(el));
        el.setAttribute('data-last-fit-value', el.value);
        refreshFontSizeIndicator(el);
    });

    function initPanelNavigation() {
        var navigators = document.querySelectorAll('.js-panel-navigator');
        navigators.forEach(function (nav) {
            var card = nav.closest('.pdftimesaver-card');
            if (!card) return;
            var fields = Array.prototype.slice.call(card.querySelectorAll('.js-panel-field'));
            if (!fields.length) return;
            var pageSize = parseInt(nav.getAttribute('data-page-size') || '8', 10) || 8;
            var shouldPaginate = fields.length > pageSize;
            var page = 0;
            var focusMode = false;
            var focusIndex = 0;
            var pageMeta = nav.querySelector('.js-panel-page-meta');
            var focusMeta = nav.querySelector('.js-panel-focus-meta');
            var prevPageBtn = nav.querySelector('.js-panel-prev-page');
            var nextPageBtn = nav.querySelector('.js-panel-next-page');
            var prevFieldBtn = nav.querySelector('.js-panel-prev-field');
            var nextFieldBtn = nav.querySelector('.js-panel-next-field');
            var focusToggleBtn = nav.querySelector('.js-panel-focus-toggle');
            var totalPages = Math.max(1, Math.ceil(fields.length / pageSize));

            function render() {
                var start = page * pageSize;
                var end = start + pageSize;
                fields.forEach(function (el, idx) {
                    if (focusMode) {
                        el.style.display = (idx === focusIndex) ? '' : 'none';
                        return;
                    }
                    if (!shouldPaginate) {
                        el.style.display = '';
                        return;
                    }
                    el.style.display = (idx >= start && idx < end) ? '' : 'none';
                });
                if (shouldPaginate) {
                    pageMeta.textContent = 'Page ' + (page + 1) + ' of ' + totalPages + ' (' + fields.length + ' fields)';
                } else {
                    pageMeta.textContent = 'All ' + fields.length + ' fields shown';
                }
                focusMeta.textContent = 'Field ' + (focusIndex + 1) + ' of ' + fields.length;
                prevPageBtn.disabled = !shouldPaginate || focusMode || page <= 0;
                nextPageBtn.disabled = !shouldPaginate || focusMode || page >= (totalPages - 1);
                prevFieldBtn.disabled = !focusMode || focusIndex <= 0;
                nextFieldBtn.disabled = !focusMode || focusIndex >= (fields.length - 1);
                focusToggleBtn.textContent = focusMode ? 'Grid View' : 'Focused View';
            }

            prevPageBtn.addEventListener('click', function () {
                page = Math.max(0, page - 1);
                focusIndex = page * pageSize;
                render();
            });
            nextPageBtn.addEventListener('click', function () {
                page = Math.min(totalPages - 1, page + 1);
                focusIndex = page * pageSize;
                render();
            });
            focusToggleBtn.addEventListener('click', function () {
                focusMode = !focusMode;
                if (!focusMode) {
                    page = Math.floor(focusIndex / pageSize);
                }
                render();
            });
            prevFieldBtn.addEventListener('click', function () {
                focusIndex = Math.max(0, focusIndex - 1);
                page = Math.floor(focusIndex / pageSize);
                render();
            });
            nextFieldBtn.addEventListener('click', function () {
                focusIndex = Math.min(fields.length - 1, focusIndex + 1);
                page = Math.floor(focusIndex / pageSize);
                render();
            });
            render();
        });
    }

    initPanelNavigation();

    form.addEventListener('input', function (ev) {
        var target = ev.target;
        if (target && target.classList && target.classList.contains('js-resizable-input')) {
            enforceNoOverflow(target);
        } else if (target && target.classList && target.classList.contains('js-inline-color')) {
            var grp = target.closest('.pdftimesaver-form-group');
            var colInput = grp ? grp.querySelector('.js-resizable-input') : null;
            if (colInput) { applyInputColor(colInput, target.value); }
        }
        clearTimeout(t);
        t = setTimeout(autoSaveNow, 900);
    });

    form.addEventListener('change', function (ev) {
        var sizeSel = ev.target.closest ? ev.target.closest('.js-inline-size') : null;
        if (!sizeSel) return;
        var group = sizeSel.closest('.pdftimesaver-form-group');
        var input = group ? group.querySelector('.js-resizable-input') : null;
        if (!input) return;
        var size = clamp(parseInt(sizeSel.value, 10) || defaultFontPx, minSize, maxSize);
        setPreferredFontSize(input, size);
        setInputFontSize(input, size);
        autoShrinkIfOverflow(input);
        autoSaveNow();
    });

    form.addEventListener('blur', function (ev) {
        var target = ev.target;
        if (!target || !form.contains(target)) return;
        if (target.classList && target.classList.contains('js-resizable-input')) {
            enforceNoOverflow(target);
        }
        autoSaveNow();
    }, true);

    form.addEventListener('click', function (ev) {
        var styleBtn = ev.target.closest('.js-inline-style');
        if (!styleBtn) return;
        var group = styleBtn.closest('.pdftimesaver-form-group');
        var input = group ? group.querySelector('.js-resizable-input') : null;
        if (!input) return;
        var flag = (styleBtn.getAttribute('data-style') || '').toUpperCase();
        if (!flag) return;
        var cur = getInputStyleString(input);
        if (cur.indexOf(flag) !== -1) {
            cur = cur.replace(flag, '');
        } else {
            cur += flag;
        }
        applyInputStyle(input, cur);
        styleBtn.setAttribute('aria-pressed', cur.indexOf(flag) !== -1 ? 'true' : 'false');
        autoSaveNow();
    });

    function syncFilloutFieldValueSpan(fieldEl, input) {
        if (!fieldEl || !input || input.type === 'checkbox') { return; }
        var span = fieldEl.querySelector('.fillout-field-value');
        if (!span) {
            span = document.createElement('span');
            span.className = 'fillout-field-value';
            span.setAttribute('aria-hidden', 'true');
            fieldEl.insertBefore(span, input);
        }
        span.textContent = input.value || '';
        var inputPx = parseInt(window.getComputedStyle(input).fontSize, 10) || 7;
        span.style.fontSize = inputPx + 'px';
        span.style.color = input.classList.contains('populate-overflow-warning')
            ? '#d93025'
            : (input.style.color || '#0f172a');
        span.style.fontWeight = input.style.fontWeight || '500';
        span.style.fontStyle = input.style.fontStyle || 'normal';
        span.style.textDecoration = input.style.textDecoration || 'none';
        span.style.opacity = (input.value || '') ? '1' : '0';
        fieldEl.classList.toggle('is-empty-field', String(input.value || '').trim() === '');
    }

    function wireFilloutValueSpan(input) {
        if (!input || !input.classList.contains('js-overlay-input')) { return; }
        var fieldEl = input.closest('.fillout-field');
        if (!fieldEl) { return; }
        var sync = function () { syncFilloutFieldValueSpan(fieldEl, input); };
        input.addEventListener('input', sync);
        input.addEventListener('change', sync);
        sync();
    }

    document.querySelectorAll('.fillout-field .js-overlay-input').forEach(wireFilloutValueSpan);

    function syncFilloutCheckboxField(fieldEl) {
        if (!fieldEl) { return; }
        var cb = fieldEl.querySelector('.js-overlay-checkbox');
        if (!cb) { return; }
        fieldEl.classList.toggle('is-checkbox-checked', !!cb.checked);
        fieldEl.classList.toggle('is-empty-field', !cb.checked);
        var h = fieldEl.offsetHeight || 0;
        if (h > 0) {
            fieldEl.style.setProperty('--fillout-check-size', Math.max(8, Math.min(14, Math.round(h * 0.85))) + 'px');
        }
    }

    function wireFilloutCheckbox(cb) {
        if (!cb || cb.type !== 'checkbox') { return; }
        var fieldEl = cb.closest('.fillout-field');
        if (!fieldEl) { return; }
        fieldEl.classList.add('is-checkbox-field');
        var sync = function () {
            syncFilloutCheckboxField(fieldEl);
            autoSaveNow();
        };
        cb.addEventListener('change', sync);
        syncFilloutCheckboxField(fieldEl);
    }

    document.querySelectorAll('.fillout-field .js-overlay-checkbox').forEach(wireFilloutCheckbox);
    var PAGE_W_MM = 215.9;
    var PAGE_H_MM = 279.4;
    var DEFAULT_OVERLAY_H_MM = 3.18;
    var DEFAULT_OVERLAY_W_MM = 45;
    var filloutSidebar = document.getElementById('filloutFieldSidebar');
    var filloutLayout = document.getElementById('filloutLayout');
    var filloutSelectedFieldEl = null;
    var filloutSelectedInput = null;

    function setFilloutSidebarOpen(open) {
        if (!filloutSidebar) { return; }
        // Keep the properties panel (including Add Custom Field) persistently visible.
        var show = true;
        if (filloutLayout) {
            filloutLayout.classList.toggle('is-panel-open', show);
        }
        filloutSidebar.setAttribute('aria-hidden', show ? 'false' : 'true');
        scheduleFilloutLayout();
        setTimeout(scheduleFilloutLayout, 180);
    }

    function clearFilloutFieldSelection() {
        if (placingTempField) { exitTempPlacement(); }
        if (filloutSelectedFieldEl) {
            filloutSelectedFieldEl.classList.remove('is-selected');
        }
        filloutSelectedFieldEl = null;
        filloutSelectedInput = null;
        if (!filloutSidebar) return;
        var status = filloutSidebar.querySelector('.js-fillout-field-status');
        var props = filloutSidebar.querySelector('.js-fillout-sidebar-props');
        if (status) {
            status.hidden = false;
            status.textContent = 'No field selected. Click a field on the preview to edit its properties.';
        }
        if (props) props.hidden = true;
        var customRemoveBtn = filloutSidebar.querySelector('.js-fillout-custom-remove');
        if (customRemoveBtn) customRemoveBtn.disabled = true;
        setFilloutSidebarOpen(false);
    }

    function normHex(c) {
        c = String(c || '').trim();
        if (/^#[0-9a-fA-F]{6}$/.test(c)) { return c.toLowerCase(); }
        return '#000000';
    }

    function getInputStyleString(input) {
        return String(input.getAttribute('data-font-style') || input.dataset.fontStyle || '').toUpperCase();
    }

    function applyInputStyle(input, styleStr) {
        styleStr = String(styleStr || '').toUpperCase().replace(/[^BIUS]/g, '');
        input.setAttribute('data-font-style', styleStr);
        input.style.fontWeight = styleStr.indexOf('B') !== -1 ? '700' : '400';
        input.style.fontStyle = styleStr.indexOf('I') !== -1 ? 'italic' : 'normal';
        var deco = [];
        if (styleStr.indexOf('U') !== -1) deco.push('underline');
        if (styleStr.indexOf('S') !== -1) deco.push('line-through');
        input.style.textDecoration = deco.length ? deco.join(' ') : 'none';
        var tempId = input.getAttribute('data-temp-id');
        if (tempId) {
            updateTempField(tempId, { fontStyle: styleStr });
            syncTempFields();
            var fieldEl = input.closest('.fillout-field');
            if (fieldEl) { syncFilloutFieldValueSpan(fieldEl, input); }
            return;
        }
        var key = input.getAttribute('data-font-style-key');
        var hidden = findHiddenFontSizeInput(key);
        if (hidden) hidden.value = styleStr;
    }

    function applyInputColor(input, hex) {
        hex = normHex(hex);
        input.style.color = hex;
        var tempId = input.getAttribute('data-temp-id');
        if (tempId) {
            updateTempField(tempId, { fontColor: hex });
            syncTempFields();
            var fieldEl = input.closest('.fillout-field');
            if (fieldEl) { syncFilloutFieldValueSpan(fieldEl, input); }
            return;
        }
        var key = input.getAttribute('data-font-color-key');
        var hidden = findHiddenFontSizeInput(key);
        if (hidden) hidden.value = hex;
    }

    function getInputColor(input) {
        var tempId = input.getAttribute('data-temp-id');
        if (tempId) {
            var tf = tempFieldById(tempId);
            if (tf && tf.fontColor) { return normHex(tf.fontColor); }
        }
        var key = input.getAttribute('data-font-color-key');
        var hidden = findHiddenFontSizeInput(key);
        if (hidden && hidden.value) { return normHex(hidden.value); }
        return '#000000';
    }

    function syncSidebarControls(input) {
        if (!filloutSidebar || !input) return;
        var sizeSel = filloutSidebar.querySelector('.js-fillout-size-select');
        if (sizeSel) { sizeSel.value = String(getInputFontSize(input)); }
        var styleStr = getInputStyleString(input);
        ['bold:B', 'italic:I', 'underline:U', 'strike:S'].forEach(function (pair) {
            var parts = pair.split(':');
            var btn = filloutSidebar.querySelector('.js-fillout-' + parts[0]);
            if (btn) btn.setAttribute('aria-pressed', styleStr.indexOf(parts[1]) !== -1 ? 'true' : 'false');
        });
        // Reflect current color on the palette swatches.
        var curColor = getInputColor(input);
        filloutSidebar.querySelectorAll('.js-fillout-swatch').forEach(function (sw) {
            sw.setAttribute('aria-pressed', normHex(sw.getAttribute('data-color')) === curColor ? 'true' : 'false');
        });
    }

    // Safety-net sync: ensure the right-panel pointer dropdowns always reflect
    // the selected field's saved pointer value, even if connector-local sync misses.
    function syncPresetConnectorFallbackForInput(input) {
        if (!filloutSidebar || !form || !input || input.type === 'checkbox' || !input.name) { return; }
        var catSel = filloutSidebar.querySelector('.js-fillout-preset-category');
        var fieldSel = filloutSidebar.querySelector('.js-fillout-preset-field');
        if (!catSel || !fieldSel) { return; }
        var pointerName = '_preset_pointer__' + input.name;
        var pointerHidden = form.querySelector('input[type="hidden"][name="' + CSS.escape(pointerName) + '"]');
        var pointer = pointerHidden ? String(pointerHidden.value || '') : '';
        var splitAt = pointer.indexOf('::');
        if (splitAt <= 0) { return; }
        var rawCategory = pointer.slice(0, splitAt);
        var fieldKey = pointer.slice(splitAt + 2);
        if (!rawCategory || !fieldKey) { return; }
        var groups = {};
        try { groups = JSON.parse(filloutSidebar.getAttribute('data-preset-groups') || '{}') || {}; } catch (e) { groups = {}; }
        var categoryKeys = Object.keys(groups || {});
        if (!categoryKeys.length) { return; }
        var category = categoryKeys.find(function (k) { return k === rawCategory; })
            || categoryKeys.find(function (k) { return String(k || '').toLowerCase() === String(rawCategory || '').toLowerCase(); })
            || '';
        if (!category) { return; }

        // Ensure category option exists.
        if (!catSel.querySelector('option[value="' + CSS.escape(category) + '"]')) {
            var catOpt = document.createElement('option');
            catOpt.value = category;
            catOpt.textContent = category;
            catSel.appendChild(catOpt);
        }
        catSel.value = category;

        // Rebuild field options for this category and select the pointed field key.
        fieldSel.innerHTML = '<option value="">Select field…</option>';
        var list = Array.isArray(groups[category]) ? groups[category] : [];
        list.forEach(function (item) {
            var key = String((item && item.key) || '');
            if (!key) { return; }
            var opt = document.createElement('option');
            opt.value = key;
            var label = String((item && item.label) || key);
            var value = String((item && item.value) || '');
            opt.textContent = label + (value ? ' — ' + (value.length > 24 ? value.slice(0, 24) + '…' : value) : ' (empty)');
            if (key === fieldKey) {
                opt.selected = true;
            }
            fieldSel.appendChild(opt);
        });
        fieldSel.disabled = list.length === 0;
    }

    function updateFilloutSidebarForInput(input, fieldEl, label) {
        if (!filloutSidebar || !input) return;
        if (filloutSelectedFieldEl && filloutSelectedFieldEl !== fieldEl) {
            filloutSelectedFieldEl.classList.remove('is-selected');
        }
        filloutSelectedFieldEl = fieldEl || null;
        filloutSelectedInput = input;
        if (fieldEl) fieldEl.classList.add('is-selected');

        var status = filloutSidebar.querySelector('.js-fillout-field-status');
        var props = filloutSidebar.querySelector('.js-fillout-sidebar-props');
        var fontSection = filloutSidebar.querySelector('.js-fillout-font-section');
        var checkboxNote = filloutSidebar.querySelector('.js-fillout-checkbox-note');

        if (status) status.hidden = true;
        if (props) props.hidden = false;

        var isCheckbox = input.type === 'checkbox';
        if (fontSection) fontSection.hidden = isCheckbox;
        if (checkboxNote) checkboxNote.hidden = !isCheckbox;
        var customRemoveBtn = filloutSidebar.querySelector('.js-fillout-custom-remove');
        if (customRemoveBtn) {
            customRemoveBtn.disabled = !(fieldEl && fieldEl.classList.contains('fillout-temp-field'));
        }
        if (!isCheckbox) {
            syncSidebarControls(input);
        }
        if (typeof syncFilloutPresetConnectorSelection === 'function') {
            syncFilloutPresetConnectorSelection();
        }
        if (!isCheckbox) {
            syncPresetConnectorFallbackForInput(input);
        }
        setFilloutSidebarOpen(true);
    }

    function selectFilloutOverlayField(fieldEl) {
        if (!fieldEl) return;
        var input = fieldEl.querySelector('.js-overlay-input') || fieldEl.querySelector('.js-overlay-checkbox');
        if (!input) return;
        var label = fieldEl.getAttribute('data-field-label') || fieldEl.getAttribute('title') || '';
        updateFilloutSidebarForInput(input, fieldEl, label);
    }

    function setFilloutSelectedFontSize(size) {
        if (!filloutSelectedInput || filloutSelectedInput.type === 'checkbox') return;
        size = clamp(parseInt(size, 10) || defaultFontPx, minSize, maxSize);
        setPreferredFontSize(filloutSelectedInput, size);
        saveFontSizeToHidden(filloutSelectedInput, size);
        var fieldEl = filloutSelectedInput.closest('.fillout-field');
        // Honor the user's explicit size choice first; overflow handling below can
        // still reduce the rendered size when needed.
        filloutSelectedInput.style.fontSize = clamp(size, minSize, maxSize) + 'px';
        syncFilloutFieldValueSpan(fieldEl, filloutSelectedInput);
        autoShrinkIfOverflow(filloutSelectedInput);
        syncSidebarControls(filloutSelectedInput);
        autoSaveNow();
    }

    function toggleFilloutSelectedStyle(flag) {
        if (!filloutSelectedInput || filloutSelectedInput.type === 'checkbox') return;
        var cur = getInputStyleString(filloutSelectedInput);
        if (cur.indexOf(flag) !== -1) {
            cur = cur.replace(flag, '');
        } else {
            cur += flag;
        }
        applyInputStyle(filloutSelectedInput, cur);
        syncSidebarControls(filloutSelectedInput);
        autoSaveNow();
    }

    if (filloutSidebar) {
        setFilloutSidebarOpen(false);
        var filloutPreviewRoot = document.getElementById('fillout-preview');
        if (filloutPreviewRoot) {
            filloutPreviewRoot.addEventListener('focusin', function (ev) {
                var fieldEl = ev.target.closest('.js-overlay-field');
                if (fieldEl) selectFilloutOverlayField(fieldEl);
            });
            filloutPreviewRoot.addEventListener('click', function (ev) {
                var fieldEl = ev.target.closest('.js-overlay-field');
                if (fieldEl) selectFilloutOverlayField(fieldEl);
            });
        }
        document.addEventListener('mousedown', function (ev) {
            if (!filloutLayout || !filloutLayout.classList.contains('is-panel-open')) { return; }
            if (placingTempField) { return; }
            if (ev.target.closest('#filloutFieldSidebar')) { return; }
            if (ev.target.closest('.js-overlay-field')) { return; }
            clearFilloutFieldSelection();
        });
        var filloutMain = document.querySelector('.fillout-preview-main');
        if (filloutMain) {
            filloutMain.addEventListener('focusin', function (ev) {
                if (!ev.target.classList || !ev.target.classList.contains('js-resizable-input')) return;
                if (ev.target.closest('.js-overlay-field')) return;
                updateFilloutSidebarForInput(ev.target, null, ev.target.getAttribute('data-field-label') || '');
            });
        }
        var sizeSel = filloutSidebar.querySelector('.js-fillout-size-select');
        if (sizeSel) { sizeSel.addEventListener('change', function () { setFilloutSelectedFontSize(sizeSel.value); }); }
        // Color palette swatches replace the raw color picker.
        filloutSidebar.querySelectorAll('.js-fillout-swatch').forEach(function (sw) {
            sw.addEventListener('click', function () {
                if (!filloutSelectedInput || filloutSelectedInput.type === 'checkbox') return;
                var hex = normHex(sw.getAttribute('data-color'));
                applyInputColor(filloutSelectedInput, hex);
                syncSidebarControls(filloutSelectedInput);
                autoSaveNow();
            });
        });
        var boldBtn = filloutSidebar.querySelector('.js-fillout-bold');
        var italicBtn = filloutSidebar.querySelector('.js-fillout-italic');
        var underlineBtn = filloutSidebar.querySelector('.js-fillout-underline');
        var strikeBtn = filloutSidebar.querySelector('.js-fillout-strike');
        if (boldBtn) boldBtn.addEventListener('click', function () { toggleFilloutSelectedStyle('B'); });
        if (italicBtn) italicBtn.addEventListener('click', function () { toggleFilloutSelectedStyle('I'); });
        if (underlineBtn) underlineBtn.addEventListener('click', function () { toggleFilloutSelectedStyle('U'); });
        if (strikeBtn) strikeBtn.addEventListener('click', function () { toggleFilloutSelectedStyle('S'); });
        var customRemoveBtn = filloutSidebar.querySelector('.js-fillout-custom-remove');
        if (customRemoveBtn) {
            customRemoveBtn.addEventListener('click', function () {
                if (!filloutSelectedFieldEl || !filloutSelectedFieldEl.classList.contains('fillout-temp-field')) { return; }
                var tempId = filloutSelectedFieldEl.getAttribute('data-temp-id');
                if (!tempId) { return; }
                removeTempField(tempId);
                clearFilloutFieldSelection();
            });
        }
        wireFilloutPresetConnector();
    }

    var syncFilloutPresetConnectorSelection = null;
    // --- Connect-to-saved-field: category + field dropdowns copy a preset value ---
    function wireFilloutPresetConnector() {
        if (!filloutSidebar) return;
        var groups = {};
        try { groups = JSON.parse(filloutSidebar.getAttribute('data-preset-groups') || '{}') || {}; } catch (e) { groups = {}; }
        var catSel = filloutSidebar.querySelector('.js-fillout-preset-category');
        var fieldSel = filloutSidebar.querySelector('.js-fillout-preset-field');
        if (!catSel || !fieldSel) return;
        var POINTER_PREFIX = '_preset_pointer__';
        function pointerNameForInput(input) {
            if (!input || !input.name) { return ''; }
            return POINTER_PREFIX + input.name;
        }
        function findPointerHidden(input) {
            var pointerName = pointerNameForInput(input);
            if (!pointerName || !form) { return null; }
            return form.querySelector('input[type="hidden"][name="' + CSS.escape(pointerName) + '"]');
        }
        function ensurePointerHidden(input) {
            if (!form) { return null; }
            var pointerName = pointerNameForInput(input);
            if (!pointerName) { return null; }
            var hidden = findPointerHidden(input);
            if (hidden) { return hidden; }
            hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = pointerName;
            hidden.value = '';
            form.appendChild(hidden);
            return hidden;
        }
        function encodePointer(category, fieldKey) {
            if (!category || !fieldKey) { return ''; }
            return String(category) + '::' + String(fieldKey);
        }
        function decodePointer(raw) {
            var text = String(raw || '');
            var idx = text.indexOf('::');
            if (idx <= 0) { return { category: '', fieldKey: '' }; }
            return {
                category: text.slice(0, idx),
                fieldKey: text.slice(idx + 2)
            };
        }
        function getPointer(input) {
            var hidden = findPointerHidden(input);
            return hidden ? String(hidden.value || '') : '';
        }
        function setPointer(input, category, fieldKey) {
            var hidden = ensurePointerHidden(input);
            if (!hidden) { return; }
            hidden.value = encodePointer(category, fieldKey);
        }
        function clearPointer(input) {
            var hidden = ensurePointerHidden(input);
            if (!hidden) { return; }
            hidden.value = '';
        }
        function getCategoryList(category) {
            return groups[category] || [];
        }
        function resolveCategoryKey(rawCategory) {
            var raw = String(rawCategory || '').trim();
            if (!raw) { return ''; }
            if (Object.prototype.hasOwnProperty.call(groups, raw)) {
                return raw;
            }
            var rawNorm = raw.toLowerCase();
            var keys = Object.keys(groups || {});
            for (var i = 0; i < keys.length; i++) {
                if (String(keys[i] || '').toLowerCase() === rawNorm) {
                    return keys[i];
                }
            }
            return '';
        }
        function normalizeLinkId(raw) {
            return String(raw || '').toLowerCase().trim();
        }
        function normalizeToken(raw) {
            return String(raw || '').toLowerCase().replace(/[^a-z0-9]+/g, '');
        }
        function findPresetByKey(category, fieldKey) {
            var resolvedCategory = resolveCategoryKey(category);
            var list = getCategoryList(resolvedCategory);
            for (var i = 0; i < list.length; i++) {
                var item = list[i];
                if (String(item && item.key || '') === String(fieldKey || '')) {
                    return {
                        category: resolvedCategory,
                        item: item
                    };
                }
            }
            return null;
        }
        function findPresetByLinkId(linkId) {
            var norm = normalizeLinkId(linkId);
            if (!norm) { return null; }
            var categoryKeys = Object.keys(groups || {});
            for (var i = 0; i < categoryKeys.length; i++) {
                var category = categoryKeys[i];
                var list = getCategoryList(category);
                for (var j = 0; j < list.length; j++) {
                    var item = list[j] || {};
                    var itemLinkId = normalizeLinkId(item.linkId);
                    if (itemLinkId && itemLinkId === norm) {
                        return {
                            category: category,
                            fieldKey: String(item.key || '')
                        };
                    }
                }
            }
            return null;
        }
        function findPresetByFieldKey(fieldKey) {
            var normKey = normalizeToken(fieldKey);
            if (!normKey) { return null; }
            var categoryKeys = Object.keys(groups || {});
            for (var i = 0; i < categoryKeys.length; i++) {
                var category = categoryKeys[i];
                var list = getCategoryList(category);
                for (var j = 0; j < list.length; j++) {
                    var item = list[j] || {};
                    var candidates = [
                        normalizeToken(item.matchingTag),
                        normalizeToken(item.linkId),
                        normalizeToken(item.key)
                    ];
                    for (var k = 0; k < candidates.length; k++) {
                        var token = candidates[k];
                        if (!token || token.length < 4) { continue; }
                        if (normKey.indexOf(token) !== -1) {
                            return {
                                category: category,
                                fieldKey: String(item.key || '')
                            };
                        }
                    }
                }
            }
            return null;
        }
        function renderFieldOptions(category, selectedFieldKey) {
            fieldSel.innerHTML = '<option value="">Select field\u2026</option>';
            var list = getCategoryList(category);
            list.forEach(function (f) {
                var fieldKey = String(f && f.key || '');
                if (!fieldKey) { return; }
                var opt = document.createElement('option');
                opt.value = fieldKey;
                opt.textContent = f.label + (f.value ? ' \u2014 ' + (f.value.length > 24 ? f.value.slice(0, 24) + '\u2026' : f.value) : ' (empty)');
                if (selectedFieldKey && fieldKey === selectedFieldKey) {
                    opt.selected = true;
                }
                fieldSel.appendChild(opt);
            });
            fieldSel.disabled = list.length === 0;
        }
        Object.keys(groups).forEach(function (cat) {
            var opt = document.createElement('option');
            opt.value = cat; opt.textContent = cat;
            catSel.appendChild(opt);
        });
        syncFilloutPresetConnectorSelection = function () {
            if (!filloutSelectedInput || filloutSelectedInput.type === 'checkbox') {
                catSel.value = '';
                renderFieldOptions('', '');
                return;
            }
            var parsed = decodePointer(getPointer(filloutSelectedInput));
            var resolvedPointerCategory = resolveCategoryKey(parsed.category);
            if (resolvedPointerCategory) {
                catSel.value = resolvedPointerCategory;
                renderFieldOptions(resolvedPointerCategory, parsed.fieldKey);
                // If the saved pointer's field key no longer exists in that category,
                // clear the stale selection instead of silently showing a wrong link.
                var hasSelected = !!fieldSel.querySelector('option[value="' + CSS.escape(String(parsed.fieldKey || '')) + '"]');
                if (!parsed.fieldKey || hasSelected) {
                    return;
                }
            }
            // If no explicit pointer is saved yet, infer from the selected field's
            // Field Manager linkId (e.g. AttyName -> Attorney Name).
            var fieldEl = filloutSelectedInput.closest('.fillout-field');
            var inferred = findPresetByLinkId(fieldEl ? fieldEl.getAttribute('data-field-link-id') : '');
            if (!inferred) {
                inferred = findPresetByFieldKey(fieldEl ? (fieldEl.getAttribute('data-field-key') || filloutSelectedInput.name) : filloutSelectedInput.name);
            }
            if (inferred && inferred.category && inferred.fieldKey) {
                catSel.value = inferred.category;
                renderFieldOptions(inferred.category, inferred.fieldKey);
                setPointer(filloutSelectedInput, inferred.category, inferred.fieldKey);
                return;
            }
            catSel.value = '';
            renderFieldOptions('', '');
        };
        catSel.addEventListener('change', function () {
            if (!filloutSelectedInput || filloutSelectedInput.type === 'checkbox') {
                catSel.value = '';
                renderFieldOptions('', '');
                return;
            }
            renderFieldOptions(catSel.value, '');
            clearPointer(filloutSelectedInput);
            autoSaveNow();
        });
        fieldSel.addEventListener('change', function () {
            if (!filloutSelectedInput || filloutSelectedInput.type === 'checkbox') return;
            var selectedFieldKey = String(fieldSel.value || '');
            if (!catSel.value || !selectedFieldKey) {
                clearPointer(filloutSelectedInput);
                autoSaveNow();
                return;
            }
            var match = findPresetByKey(catSel.value, selectedFieldKey);
            if (!match || !match.item) return;
            var f = match.item;
            // Copy the preset value into the selected box only; the original field's
            // saved value is untouched (we never write back to f.key).
            filloutSelectedInput.value = f.value || '';
            setPointer(filloutSelectedInput, match.category || catSel.value, selectedFieldKey);
            filloutSelectedInput.dispatchEvent(new Event('input', { bubbles: true }));
            filloutSelectedInput.focus();
            autoSaveNow();
        });
        syncFilloutPresetConnectorSelection();
    }

    function layoutFilloutPreview() {
        var pages = document.querySelectorAll('.fillout-page');
        if (!pages.length) return;
        var laidOut = false;
        pages.forEach(function (page) {
            var p = page.getAttribute('data-page');
            var img = document.getElementById('fillout-bg-' + p);
            var overlay = document.getElementById('fillout-overlay-' + p);
            if (!img || !overlay) return;

            // Match the Forms Manager / universal processor: size overlay to the
            // rendered background image and scale X/Y independently.
            var displayWidth = img.clientWidth || img.naturalWidth || page.clientWidth || 0;
            var displayHeight = img.clientHeight || img.naturalHeight || 0;
            if (!displayWidth) return;
            if (!displayHeight) {
                displayHeight = displayWidth * (PAGE_H_MM / PAGE_W_MM);
            }
            overlay.style.width = displayWidth + 'px';
            overlay.style.height = displayHeight + 'px';
            var scaleX = displayWidth / PAGE_W_MM;
            var scaleY = displayHeight / PAGE_H_MM;

            Array.prototype.slice.call(overlay.querySelectorAll('.js-overlay-field')).forEach(function (fEl) {
                var input = fEl.querySelector('.js-overlay-input');
                var mx = parseFloat(fEl.dataset.x || '0');
                var my = parseFloat(fEl.dataset.y || '0');
                var mw = Math.max(0, parseFloat(fEl.dataset.w || '0'));
                var mh = parseFloat(fEl.dataset.h || '0');
                if (!mh || mh < 0.5) {
                    mh = DEFAULT_OVERLAY_H_MM;
                    fEl.dataset.h = String(mh);
                }
                if (!mw || mw < 0.5) {
                    mw = DEFAULT_OVERLAY_W_MM;
                    fEl.dataset.w = String(mw);
                }
                var x = mx * scaleX;
                var y = my * scaleY;
                var w = Math.max(5, mw * scaleX);
                var h = Math.max(5, mh * scaleY);
                fEl.style.left = x + 'px';
                fEl.style.top = y + 'px';
                fEl.style.width = w + 'px';
                fEl.style.height = h + 'px';
                var checkbox = fEl.querySelector('.js-overlay-checkbox');
                if (checkbox) {
                    syncFilloutCheckboxField(fEl);
                    return;
                }
                if (!input) return;
                applyOverlayFieldFont(input, fEl, h);
            });
            laidOut = true;
        });
        if (filloutSelectedInput && filloutSidebar && filloutSelectedInput.type !== 'checkbox') {
            syncSidebarControls(filloutSelectedInput);
        }
        var previewRoot = document.getElementById('fillout-preview');
        if (previewRoot && laidOut) { previewRoot.classList.add('fillout-ready'); }
    }

    var filloutResizeTimer;
    function scheduleFilloutLayout() {
        clearTimeout(filloutResizeTimer);
        filloutResizeTimer = setTimeout(layoutFilloutPreview, 80);
    }
    function enforcePopulatePreviewScrollLock() {
        if (!document.body || !document.body.classList.contains('route-populate-scroll-lock')) {
            return;
        }
        var mainContent = document.querySelector('.pdftimesaver-main-content');
        var contentBody = document.querySelector('.pdftimesaver-content-body');
        var topStrip = document.querySelector('.populate-top-strip');
        var previewCard = document.querySelector('.fillout-preview-card');
        var filloutLayout = document.querySelector('.fillout-layout');
        var filloutSidebar = document.querySelector('.fillout-sidebar');
        var previewMain = document.querySelector('.fillout-preview-main');
        var actionBarWrap = document.querySelector('.populate-action-bar-wrap');
        if (!mainContent || !contentBody || !previewMain) {
            return;
        }

        // Keep page-level (right) scrollbar disabled for populate; left preview owns scrolling.
        document.documentElement.style.overflow = 'hidden';
        document.body.style.overflow = 'hidden';

        mainContent.style.height = (window.innerHeight || document.documentElement.clientHeight || 0) + 'px';
        mainContent.style.overflow = 'hidden';
        mainContent.style.display = 'flex';
        mainContent.style.flexDirection = 'column';

        // Let CSS own layout; clear fragile inline overrides from prior iterations.
        contentBody.style.position = '';

        if (previewCard) {
            previewCard.style.marginBottom = '';
            previewCard.style.paddingBottom = '';
        }
        if (filloutLayout) {
            filloutLayout.style.alignItems = '';
            filloutLayout.style.overflow = 'hidden';
        }
        if (filloutSidebar) {
            filloutSidebar.style.maxHeight = '';
        }
        if (actionBarWrap) {
            actionBarWrap.style.position = '';
            actionBarWrap.style.left = '';
            actionBarWrap.style.right = '';
            actionBarWrap.style.bottom = '';
            actionBarWrap.style.marginTop = '';
            actionBarWrap.style.zIndex = '';
        }

        var contentBodyHeight = contentBody.clientHeight || 0;
        var topStripHeight = topStrip ? topStrip.offsetHeight : 0;
        var actionBarHeight = actionBarWrap ? actionBarWrap.offsetHeight : 0;
        var bodyStyles = window.getComputedStyle(contentBody);
        var paddingY = (parseFloat(bodyStyles.paddingTop || '0') || 0) + (parseFloat(bodyStyles.paddingBottom || '0') || 0);
        var bodyGap = parseFloat(bodyStyles.rowGap || bodyStyles.gap || '0') || 0;
        var footerSafetyBuffer = 16;
        var availablePreviewHeight = Math.max(140, Math.floor(contentBodyHeight - paddingY - topStripHeight - actionBarHeight - (bodyGap * 2) - footerSafetyBuffer));
        if (previewCard) {
            previewCard.style.height = availablePreviewHeight + 'px';
            previewCard.style.maxHeight = availablePreviewHeight + 'px';
            previewCard.style.flex = '0 0 ' + availablePreviewHeight + 'px';
        }
        if (filloutLayout) {
            filloutLayout.style.height = '100%';
        }
        previewMain.style.height = '100%';
        previewMain.style.maxHeight = '100%';
        previewMain.style.minHeight = '0';
        previewMain.style.flex = '1 1 auto';
        previewMain.style.overflowY = 'auto';
        previewMain.style.overflowX = 'hidden';
    }
    if (document.querySelector('.fillout-page')) {
        document.querySelectorAll('.fillout-bg').forEach(function (img) {
            if (img.complete) { return; }
            img.addEventListener('load', scheduleFilloutLayout);
        });
        window.addEventListener('resize', function () {
            enforcePopulatePreviewScrollLock();
            scheduleFilloutLayout();
        });
        var previewMain = document.querySelector('.fillout-preview-main');
        if (previewMain && typeof ResizeObserver !== 'undefined') {
            var filloutResizeObserver = new ResizeObserver(function () {
                enforcePopulatePreviewScrollLock();
                scheduleFilloutLayout();
            });
            filloutResizeObserver.observe(previewMain);
            document.querySelectorAll('.fillout-bg').forEach(function (img) {
                filloutResizeObserver.observe(img);
            });
        }
        enforcePopulatePreviewScrollLock();
        layoutFilloutPreview();
        // Re-run after images size; fix any legacy temp boxes saved with pt-as-mm heights.
        setTimeout(function () {
            enforcePopulatePreviewScrollLock();
            layoutFilloutPreview();
            if (repairStoredTempFieldSizes()) {
                renderTempFields();
                layoutFilloutPreview();
                autoSaveNow();
            }
        }, 150);
        setTimeout(function () {
            enforcePopulatePreviewScrollLock();
            layoutFilloutPreview();
        }, 600);
    }
})();
</script>