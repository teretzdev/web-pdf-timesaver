<?php
// Layout header is already included by the render() function in index.php
$clients = $clients ?? [];
$prefillClientId = $prefillClientId ?? '';
$prefillTemplateId = isset($prefillTemplateId) ? (string)$prefillTemplateId : '';
$prefillTemplateRecord = is_array($prefillTemplateRecord ?? null) ? $prefillTemplateRecord : null;
$viewMode = $viewMode ?? 'universal';
$isFormManagement = in_array($viewMode, ['form-management', 'form-new'], true);
$isFormNew = ($viewMode === 'form-new');
$formCustomFields = is_array($formCustomFields ?? null) ? $formCustomFields : [];
$formImporterAliases = is_array($formImporterAliases ?? null) ? $formImporterAliases : [];
$managedFormTemplates = is_array($managedFormTemplates ?? null) ? $managedFormTemplates : [];
$customFieldMatchingMode = strtolower(trim((string)($customFieldMatchingMode ?? 'exact')));
if (!in_array($customFieldMatchingMode, ['exact', 'regex'], true)) {
    $customFieldMatchingMode = 'exact';
}
require_once dirname(__DIR__) . '/lib/field_metrics.php';
$fontConfigPath = dirname(__DIR__, 2) . '/config/fonts.php';
$fontConfig = is_file($fontConfigPath) ? require $fontConfigPath : [];
$availableFonts = is_array($fontConfig['availableFonts'] ?? null) && !empty($fontConfig['availableFonts'])
    ? $fontConfig['availableFonts']
    : ['Arial', 'Helvetica', 'Times', 'Courier', 'Symbol', 'ZapfDingbats'];
$fieldMetricsJs = \WebPdfTimeSaver\Mvp\FieldMetrics::jsConfig();
?>

<style>
.processor-container {
    max-width: none;
    width: 100%;
    margin: 0;
}

@media (min-width: 769px) {
    .processor-container {
        width: calc(100% + 40px);
        margin-left: -20px;
        margin-right: -20px;
    }
}

.diagnostics-panel {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    padding: 24px;
    margin-bottom: 30px;
}

.diagnostics-toggle-wrap {
    margin-bottom: 14px;
    display: flex;
    justify-content: flex-start;
}

.diagnostics-header {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    gap: 12px;
    align-items: center;
    margin-bottom: 16px;
}

.diagnostics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 12px;
}

.diag-item {
    border: 1px solid #e1e4e8;
    border-radius: 8px;
    padding: 12px;
    position: relative;
    background: #fdfdfd;
}

.diag-item.fail {
    border-color: #f5c6cb;
    background: #fff5f5;
}

.diag-label {
    font-size: 13px;
    text-transform: uppercase;
    color: #6c757d;
    letter-spacing: 0.5px;
    margin-bottom: 4px;
    font-weight: 600;
}

.diag-value {
    font-size: 16px;
    font-weight: 600;
    color: #212529;
}

.diag-desc {
    font-size: 12px;
    color: #6c757d;
    margin-top: 6px;
}

.status-pill {
    position: absolute;
    top: 12px;
    right: 12px;
    padding: 3px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-pill.ok {
    background: #d4edda;
    color: #155724;
}

.status-pill.fail {
    background: #f8d7da;
    color: #721c24;
}

.diag-error {
    color: #b71c1c;
    margin: 0;
}

.raw-response {
    margin-top: 12px;
}

.raw-response pre {
    background: #111827;
    color: #f8fafc;
    padding: 12px;
    border-radius: 6px;
    white-space: pre-wrap;
    max-height: 300px;
    overflow: auto;
}
    
    .hero-section {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 40px;
        border-radius: 12px;
        margin-bottom: 30px;
        text-align: center;
    }
    
    .hero-section h1 {
        margin: 0 0 10px 0;
        font-size: 32px;
    }
    
    .hero-section p {
        margin: 0;
        font-size: 18px;
        opacity: 0.9;
    }
    
    .upload-section {
        background: white;
        padding: 28px;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }
    
    .upload-area {
        border: 3px dashed #667eea;
        border-radius: 12px;
        padding: 60px 40px;
        text-align: center;
        background: #f8f9fa;
        transition: all 0.3s;
        cursor: pointer;
        margin: 20px 0;
    }
    
    .upload-area:hover {
        border-color: #764ba2;
        background: #e7f3ff;
    }
    
    .upload-area.dragover {
        background: #d4edda;
        border-color: #28a745;
        transform: scale(1.02);
    }
    
    .upload-area.file-selected {
        border-color: #28a745;
        background: #d4edda;
    }
    
    .upload-icon {
        font-size: 64px;
        margin-bottom: 20px;
    }
    
    .file-input {
        display: none;
    }
    
    .template-id-input {
        width: 100%;
        padding: 12px;
        border: 2px solid #e1e4e8;
        border-radius: 6px;
        font-size: 14px;
        margin-bottom: 20px;
    }
    
    .submit-btn {
        width: 100%;
        padding: 16px;
        background: #667eea;
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 18px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .submit-btn:hover:not(:disabled) {
        background: #5568d3;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }
    
    .submit-btn:disabled {
        background: #ccc;
        cursor: not-allowed;
    }
    
    .browse-btn {
        padding: 12px 24px;
        background: #667eea;
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .browse-btn:hover {
        background: #5568d3;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }
    
    .browse-btn:active {
        transform: translateY(0);
    }
    
    .results-section {
        background: white;
        padding: 28px;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        display: none;
        overflow: hidden;
        box-sizing: border-box;
    }
    
    .results-section.success {
        border-left: 5px solid #28a745;
    }
    
    .results-section.error {
        border-left: 5px solid #dc3545;
    }
    
    .loading {
        text-align: center;
        padding: 40px;
    }
    
    .spinner {
        border: 4px solid #f3f3f3;
        border-top: 4px solid #667eea;
        border-radius: 50%;
        width: 50px;
        height: 50px;
        animation: spin 1s linear infinite;
        margin: 0 auto 20px;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .field-table {
        width: 100%;
        border-collapse: collapse;
        margin: 20px 0;
        font-size: 13px;
    }
    
    .field-table th {
        background: #667eea;
        color: white;
        padding: 12px;
        text-align: left;
        font-weight: 600;
    }
    
    .field-table td {
        padding: 10px 12px;
        border-bottom: 1px solid #e1e4e8;
    }
    
    .field-table tr:hover {
        background: #f8f9fa;
    }
    
    .badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
    }
    
    .badge-success {
        background: #d4edda;
        color: #155724;
    }
    
    .badge-warning {
        background: #fff3cd;
        color: #856404;
    }
    
    .badge-info {
        background: #d1ecf1;
        color: #0c5460;
    }
    
    .stat-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin: 20px 0;
    }
    
    .stat-card {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        text-align: center;
    }
    
    .stat-value {
        font-size: 32px;
        font-weight: 700;
        color: #667eea;
        margin: 10px 0;
    }
    
    .stat-label {
        font-size: 14px;
        color: #666;
    }
    
    .json-output {
        background: #f4f4f4;
        padding: 15px;
        border-radius: 6px;
        overflow-x: auto;
        font-size: 12px;
        font-family: 'Courier New', monospace;
        margin-top: 20px;
    }
    
    .feature-list {
        list-style: none;
        padding: 0;
        margin: 20px 0;
    }
    
    .feature-list li {
        padding: 10px 0 10px 30px;
        position: relative;
    }
    
    .feature-list li:before {
        content: "✓";
        position: absolute;
        left: 0;
        color: #28a745;
        font-weight: bold;
        font-size: 18px;
    }
    
    .file-info {
        margin-top: 15px;
        padding: 10px;
        background: #d4edda;
        border-radius: 6px;
        color: #155724;
    }

    /* Client-facing technical preview */
    .tech-preview {
        margin-top: 30px;
        padding: 24px;
        background: #f8f9fa;
        border-radius: 12px;
        border: 1px solid #e1e4e8;
        overflow: hidden;
        box-sizing: border-box;
    }

    .tech-preview h3 {
        margin-top: 0;
        margin-bottom: 10px;
    }

    .tech-preview p {
        margin-top: 0;
        margin-bottom: 20px;
        color: #555;
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

    .tech-preview-guidance {
        margin: 0 0 16px 0;
        padding: 10px 12px;
        border-radius: 6px;
        border: 1px solid #cce5ff;
        background: #e8f4ff;
        color: #1f4e79;
        font-size: 13px;
    }

    .tech-preview-controls {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 16px;
        flex-wrap: wrap;
    }

    .tech-preview-visibility-options {
        display: inline-flex;
        flex-wrap: wrap;
        gap: 10px 14px;
        align-items: center;
    }

    .tech-preview-visibility-options label {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        font-size: 12px;
        font-weight: 600;
        color: #334155;
        cursor: pointer;
        user-select: none;
    }

    .tech-preview-toggle {
        border: 1px solid #667eea;
        background: #667eea;
        color: #fff;
        border-radius: 6px;
        padding: 8px 12px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
    }

    .tech-preview-toggle:hover {
        background: #5a6ed4;
        border-color: #5a6ed4;
    }

    .tech-preview-legend {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
        font-size: 12px;
        color: #3d4a57;
    }

    .tech-preview-legend-title {
        font-size: 12px;
        font-weight: 700;
        color: #1f2937;
        margin-right: 2px;
    }

    .tech-preview-legend-item {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 8px;
        border: 1px solid #e2e8f0;
        border-radius: 999px;
        background: #ffffff;
    }

    .tech-preview-legend-swatch {
        width: 16px;
        height: 16px;
        border-radius: 50%;
        border: 2px solid transparent;
        box-shadow: inset 0 0 0 1px rgba(255,255,255,0.65), 0 0 0 1px rgba(15,23,42,0.08);
    }

    .tech-preview-canvas {
        position: relative;
        display: block;
        background: white;
        padding: 0;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.06);
        width: 100%;
        max-width: 100%;
        overflow-x: auto;
        overflow-y: hidden;
    }

    .tech-preview-bg {
        display: block;
        width: clamp(1000px, 80vw, 1500px);
        max-width: none;
        height: auto;
    }

    .tech-preview-overlay-layer {
        position: absolute;
        top: 0;
        left: 0;
        pointer-events: none;
        width: 100%;
        height: 100%;
    }

    .tech-preview-field {
        position: absolute;
        border: 1px solid #2563eb;
        background: rgba(37, 99, 235, 0.10);
        border-radius: 3px;
        box-sizing: border-box;
        box-shadow: none;
        animation: none;
        opacity: 1;
        transition: border-color 0.12s ease, background 0.12s ease;
    }

    .tech-preview-field.confidence-high,
    .tech-preview-field.confidence-medium,
    .tech-preview-field.confidence-low,
    .tech-preview-field.confidence-unknown,
    .tech-preview-field:hover,
    .tech-preview-field:focus-visible,
    .tech-preview-field.is-selected {
        border-color: #2563eb;
        background: rgba(37, 99, 235, 0.12);
        opacity: 1;
    }
    .tech-preview-field.is-auto-mapped-custom,
    .tech-preview-field.is-auto-mapped-custom:hover,
    .tech-preview-field.is-auto-mapped-custom:focus-visible,
    .tech-preview-field.is-auto-mapped-custom.is-selected {
        border-color: #7c3aed;
        background: rgba(124, 58, 237, 0.12);
        opacity: 1;
    }

    @keyframes techPreviewPulse {
        0%, 100% {
            box-shadow: 0 0 0 1px rgba(255, 255, 255, 0.9), 0 0 0 3px rgba(255, 59, 48, 0.2);
        }
        50% {
            box-shadow: 0 0 0 1px rgba(255, 255, 255, 0.95), 0 0 0 5px rgba(255, 59, 48, 0.34);
        }
    }

    .wizard-steps {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        list-style: none;
        padding: 0;
        margin: 0 0 24px 0;
        justify-content: center;
    }
    .wizard-steps li {
        padding: 8px 14px;
        border-radius: 999px;
        background: #e9ecef;
        color: #495057;
        font-size: 13px;
        font-weight: 600;
    }
    .wizard-steps li.active {
        background: #667eea;
        color: #fff;
    }
    .wizard-steps li.done {
        background: #d4edda;
        color: #155724;
    }
    .wizard-pane {
        display: none;
    }
    .wizard-pane.is-active {
        display: block;
    }
    .wizard-nav {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        justify-content: flex-start;
        align-items: center;
        margin-top: 20px;
        padding-top: 16px;
        border-top: 1px solid #e1e4e8;
    }
    .wizard-pane[data-wizard-pane="2"] #wizardGenerateStatus {
        margin-top: 6px !important;
    }
    .wizard-pane[data-wizard-pane="2"] .wizard-nav {
        margin-top: 10px;
        padding-top: 10px;
    }
    .form-search-section {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 14px;
        margin-bottom: 14px;
    }
    .form-search-controls {
        display: flex;
        gap: 8px;
        align-items: center;
        margin-bottom: 8px;
    }
    .form-search-controls .template-id-input {
        margin-bottom: 0;
    }
    .form-search-list {
        margin-top: 10px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #fff;
        max-height: 320px;
        overflow: auto;
    }
    .form-search-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        padding: 10px 12px;
        border-bottom: 1px solid #eef2f7;
        cursor: pointer;
        transition: background 0.15s ease;
    }
    .form-search-row:hover {
        background: #f8fafc;
    }
    .form-search-row.is-loading {
        cursor: wait;
        opacity: 0.75;
    }
    .form-search-row:last-child {
        border-bottom: 0;
    }
    .form-search-label {
        font-size: 13px;
        color: #0f172a;
        font-weight: 600;
    }
    .form-search-meta {
        font-size: 12px;
        color: #64748b;
    }
    .form-search-empty {
        padding: 12px;
        color: #64748b;
        font-size: 13px;
    }
    .form-search-status {
        margin-top: 10px;
        font-size: 13px;
        color: #475569;
        min-height: 18px;
    }
    .form-search-status.is-loading {
        color: #1d4ed8;
    }
    .form-search-status.is-error {
        color: #b91c1c;
    }
    .form-search-status.is-success {
        color: #166534;
    }
    .form-search-btn-loading {
        opacity: 0.8;
    }
    .form-search-btn-loading::before {
        content: "";
        display: inline-block;
        width: 10px;
        height: 10px;
        margin-right: 6px;
        border: 2px solid currentColor;
        border-top-color: transparent;
        border-radius: 50%;
        animation: formSearchSpin 0.7s linear infinite;
        vertical-align: -1px;
    }
    @keyframes formSearchSpin {
        to { transform: rotate(360deg); }
    }
    .form-edit-header-actions {
        display: flex;
        justify-content: flex-end;
        margin: 0 0 10px 0;
    }
    .tech-preview-field.is-draggable {
        pointer-events: auto;
        cursor: grab;
    }
    .tech-preview-field.is-draggable:active {
        cursor: grabbing;
    }
    .detected-firm-banner {
        margin: 0 0 14px 0;
        padding: 12px 14px;
        background: #ecfdf5;
        border: 1px solid #6ee7b7;
        border-radius: 8px;
        color: #065f46;
        font-size: 14px;
    }
    .detected-firm-banner strong {
        font-weight: 700;
    }
    .tech-preview-field.has-detected-firm {
        box-shadow: 0 0 0 1px rgba(5, 150, 105, 0.35) inset;
    }
    .tech-preview-field-value {
        position: absolute;
        left: 0;
        top: 0;
        right: 0;
        bottom: 0;
        padding: 1px 2px;
        max-height: 5em;
        font-size: 9px;
        line-height: 1.05;
        color: #0f172a;
        overflow: hidden;
        text-overflow: ellipsis;
        display: -webkit-box;
        -webkit-line-clamp: 4;
        -webkit-box-orient: vertical;
        white-space: normal;
        pointer-events: none;
        font-weight: 400;
        word-break: break-word;
        font-family: Arial, "Helvetica Neue", Helvetica, "Segoe UI", sans-serif;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
        text-rendering: optimizeLegibility;
    }
    .tech-preview-field--fill-suggested .tech-preview-field-value {
        color: #334155;
        font-style: italic;
        font-weight: 500;
    }
    .tech-preview-field--fill-saved .tech-preview-field-value {
        color: #0f172a;
        font-style: normal;
        font-weight: 600;
    }
    .tech-preview-field--fill-session .tech-preview-field-value {
        color: #0c4a6e;
        font-style: normal;
        font-weight: 700;
    }
    .tech-preview-field--fill-custom .tech-preview-field-value {
        color: #6d28d9;
        font-style: normal;
        font-weight: 600;
    }
    .tech-preview-field.is-custom-value-locked {
        box-shadow: 0 0 0 1px rgba(109, 40, 217, 0.35) inset;
    }
    #fieldValueInput.is-custom-field-locked {
        background: #f5f3ff;
        color: #4c1d95;
    }
    .tech-preview-field.is-checked::after {
        content: "✓";
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #0f172a;
        font-size: 12px;
        font-weight: 700;
        pointer-events: none;
    }
    .page-padding-controls {
        margin: 10px 0 12px 0;
        padding: 10px 12px;
        border: 1px solid #d6dbe3;
        border-radius: 8px;
        background: #f8fafc;
    }
    .page-padding-title-row {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .page-padding-help {
        width: 20px;
        height: 20px;
        border-radius: 999px;
        border: 1px solid #cbd5e1;
        background: #fff;
        color: #334155;
        font-size: 12px;
        font-weight: 700;
        cursor: help;
        line-height: 1;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .page-padding-row {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: end;
        margin-top: 8px;
    }
    .page-padding-input-group {
        display: flex;
        flex-direction: column;
        gap: 4px;
        min-width: 90px;
    }
    .page-padding-input-group label {
        font-size: 12px;
        color: #3d4a57;
        font-weight: 600;
    }
    .page-padding-input-group input {
        padding: 6px 8px;
        border: 1px solid #cbd5e1;
        border-radius: 4px;
        font-size: 12px;
        width: 100%;
        box-sizing: border-box;
    }
    .page-padding-btn {
        padding: 8px 10px;
        border-radius: 6px;
        border: 1px solid #667eea;
        background: #667eea;
        color: #fff;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
    }
    .page-padding-btn.secondary {
        background: #fff;
        color: #334155;
        border-color: #cbd5e1;
    }
    .page-padding-note {
        margin-top: 8px;
        font-size: 12px;
        color: #475569;
    }
    .form-custom-fields-card {
        background: #fff;
        padding: 24px;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        margin-bottom: 30px;
    }
    .form-custom-fields-card h3 {
        margin: 0 0 10px 0;
    }
    .form-custom-fields-card p {
        margin: 0 0 12px 0;
        color: #586069;
    }
    .form-custom-fields-card .cfc-input,
    .form-custom-fields-card .cfc-select {
        width: 100%;
        max-width: 260px;
        padding: 6px 8px;
        font-size: 13px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        box-sizing: border-box;
    }
    .form-custom-fields-card .field-table td {
        vertical-align: middle;
    }
    .form-custom-fields-card .cfc-row-status {
        white-space: nowrap;
        font-size: 12px;
        color: #166534;
    }
    .form-custom-fields-add {
        margin-top: 20px;
        padding-top: 16px;
        border-top: 1px solid #e5e7eb;
    }
    .form-custom-fields-add h4 {
        margin: 0 0 12px 0;
        font-size: 15px;
    }
    .form-custom-fields-add-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        gap: 12px;
        align-items: end;
    }
    .form-custom-fields-add-grid label {
        display: flex;
        flex-direction: column;
        gap: 4px;
        font-size: 12px;
        font-weight: 600;
        color: #475569;
    }
    .form-custom-fields-add-grid input,
    .form-custom-fields-add-grid select {
        padding: 8px 10px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        font-size: 14px;
    }
    #formCustomFieldsSaveStatus {
        min-height: 1.25em;
        font-size: 13px;
        margin-top: 10px;
    }
    .form-manager-layout {
        display: grid !important;
        grid-template-columns: minmax(0, 1fr) var(--fm-sidebar-width, 320px);
        gap: 12px;
        align-items: start;
        position: relative;
        --fm-sidebar-width: 320px;
    }
    .form-manager-layout .tech-preview-bg {
        width: 100% !important;
        max-width: 100% !important;
        min-width: 0 !important;
    }
    .form-manager-layout .tech-preview-overlay-layer {
        max-width: 100%;
        overflow: hidden;
    }
    .results-section {
        min-width: 0;
        max-width: 100%;
        overflow: hidden;
    }
    .form-manager-sidebar {
        background: #f8fafc;
        border: 1px solid #dbe4ef;
        border-radius: 10px;
        padding: 0;
        position: sticky;
        top: 12px;
        align-self: start;
        min-width: 0;
        max-width: 100%;
        display: flex;
        flex-direction: column;
        max-height: calc(100vh - 24px);
        overflow: hidden;
        box-shadow: 0 4px 14px rgba(15, 23, 42, 0.08);
    }
    .form-manager-sidebar-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
        padding: 10px 12px;
        border-bottom: 1px solid #e2e8f0;
        background: #fff;
        border-radius: 10px 10px 0 0;
        flex-shrink: 0;
        z-index: 2;
    }
    .form-manager-sidebar-header h4 {
        margin: 0;
        font-size: 15px;
    }
    .form-manager-sidebar-close {
        border: 1px solid #cbd5e1;
        background: #f8fafc;
        color: #334155;
        height: 30px;
        border-radius: 8px;
        font-size: 12px;
        line-height: 1;
        cursor: pointer;
        font-weight: 600;
        padding: 0 10px;
    }
    .form-manager-sidebar-close:hover {
        background: #e2e8f0;
        color: #0f172a;
    }
    .form-manager-sidebar-body {
        padding: 10px 12px 14px;
        flex: 1;
        min-height: 0;
        overflow-y: auto;
        overflow-x: hidden;
        -webkit-overflow-scrolling: touch;
    }
    .form-manager-sidebar-footer {
        padding: 10px 12px 12px;
        border-top: 1px solid #e2e8f0;
        background: #fff;
        flex-shrink: 0;
    }
    .field-internal-key {
        font-size: 11px;
        color: #64748b;
        word-break: break-all;
        margin: 0 0 8px 0;
    }
    .field-internal-key code {
        font-size: 11px;
        background: #f1f5f9;
        padding: 2px 6px;
        border-radius: 4px;
    }
    .form-manager-sidebar h4 {
        margin: 0;
        color: #1e3a5f;
        font-size: 15px;
    }
    .form-manager-panel {
        border: 1px solid #d6dde8;
        border-radius: 8px;
        padding: 8px;
        margin-bottom: 8px;
        background: #fff;
    }
    .form-manager-panel.is-disabled {
        opacity: 0.62;
    }
    .form-manager-panel h5 {
        margin: 0 0 8px 0;
        font-size: 12px;
        color: #334155;
    }
    .form-manager-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 6px;
    }
    .form-manager-sidebar label {
        display: block;
        font-size: 11px;
        color: #475569;
        margin-bottom: 3px;
        font-weight: 600;
    }
    .form-manager-sidebar input,
    .form-manager-sidebar select {
        width: 100%;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        padding: 4px 5px;
        font-size: 12px;
        margin-bottom: 5px;
        background: #fff;
    }
    .field-selection-status {
        font-size: 11px;
        margin-bottom: 6px;
        color: #1e293b;
        font-weight: 600;
    }
    .form-style-flags {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 6px;
    }
    .form-style-flags label {
        display: flex;
        align-items: center;
        gap: 6px;
        margin: 0;
        padding: 2px 0;
        font-weight: 500;
    }
    .form-style-flags input[type="checkbox"] {
        width: auto;
        margin: 0;
    }
    .tech-preview-field.is-selected {
        outline: 2px solid #2563eb;
        outline-offset: 2px;
    }
    .wizard-export-row {
        display: flex;
        flex-wrap: wrap;
        align-items: end;
        gap: 12px;
        margin: 18px 0 10px 0;
    }
    .wizard-export-row .submit-btn {
        width: auto;
        padding: 12px 18px;
        max-width: none;
    }
    .wizard-export-row .browse-btn {
        width: auto;
        max-width: none;
    }
    .form-insert-dock-wrap {
        width: 100%;
    }
    .form-insert-dock {
        background: #ffffff;
        border: 1px solid #d6dde8;
        border-radius: 12px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.18);
        padding: 10px 12px;
        width: 100%;
        box-sizing: border-box;
    }
    .form-insert-dock p {
        margin: 8px 0 0 0;
        font-size: 12px;
        color: #64748b;
        line-height: 1.35;
    }
    @media (max-width: 900px) {
        .form-manager-layout {
            grid-template-columns: 1fr !important;
        }
        .form-manager-sidebar {
            position: static;
            max-height: min(70vh, 520px);
            top: auto;
        }
        .tech-preview-bg {
            width: 100%;
            max-width: 100%;
            min-width: 0;
        }
        .form-insert-dock-wrap {
            margin-top: 0;
        }
        .form-insert-dock {
            width: 100%;
        }
    }
</style>

<div class="processor-container">
    <div class="hero-section">
        <div class="upload-icon"><?php echo $isFormManagement ? '🗂️' : '🤖'; ?></div>
        <h1><?php echo $isFormManagement ? ($isFormNew ? 'Add New Form' : 'Forms Manager') : 'Universal PDF Form Processor'; ?></h1>
        <p><?php echo $isFormManagement
            ? 'Upload and prepare forms in a guided workflow'
            : 'Auto-detect fillable fields from ANY PDF form — step-by-step wizard'; ?></p>
    </div>

    <ol class="wizard-steps" id="wizardStepLabels" aria-label="Progress">
        <li class="active" data-wizard-label="1"><?php echo $isFormManagement ? ($isFormNew ? '1. Upload form' : '1. Search form') : '1. Upload'; ?></li>
        <li data-wizard-label="2"><?php echo $isFormManagement ? '2. Align &amp; fill' : '2. Analyze &amp; fill'; ?></li>
        <?php if (!$isFormManagement): ?>
        <li data-wizard-label="3">3. Download</li>
        <?php endif; ?>
    </ol>

    <div class="wizard-pane is-active" data-wizard-pane="1">
    <?php if (!$isFormManagement): ?>
    <div class="diagnostics-toggle-wrap">
        <button type="button" class="browse-btn" id="toggleDiagnosticsBtn" aria-expanded="false">Show server requirements check</button>
    </div>
    <div class="diagnostics-panel" id="diagnosticsPanel" hidden>
        <div class="diagnostics-header">
            <div>
                <h2 style="margin:0;">Server Requirements Check</h2>
                <p id="diagnosticsSummary" style="margin:4px 0 0 0; color:#6c757d;">Expand this panel to run diagnostics.</p>
            </div>
            <button type="button" class="browse-btn" id="refreshDiagnosticsBtn">↻ Re-check</button>
        </div>
        <div id="diagnosticsContent" class="diagnostics-grid">
            <p class="diag-error" style="color:#6c757d;">Diagnostics have not been run yet.</p>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (!$isFormNew && $isFormManagement): ?>
    <div class="form-search-section">
        <h2 style="margin-top:0;">Search Forms</h2>
        <p style="color:#64748b; margin: 0 0 10px 0;">Find a form to modify, or add a new form.</p>
        <div class="form-search-controls">
            <input
                type="text"
                id="formSearchInput"
                class="template-id-input"
                placeholder="Search by form number, template ID, or form name"
                autocomplete="off"
            />
            <button type="button" class="browse-btn" id="formSearchBtn">Search</button>
            <button type="button" class="browse-btn" id="formBrowseBtn">Browse</button>
        </div>
        <div id="formSearchList" class="form-search-list"></div>
        <div id="formSearchStatus" class="form-search-status" aria-live="polite"></div>
        <div style="margin-top: 12px;">
            <button type="button" class="browse-btn wizard-action-btn" id="addNewFormBtn">Add New Form</button>
        </div>
    </div>
    <?php endif; ?>

    <div class="upload-section" id="uploadFormSection"<?php echo (!$isFormNew && $isFormManagement) ? ' style="display:none;"' : ''; ?>>
        <?php if (!$isFormManagement): ?>
        <h2>How It Works</h2>
        <ul class="feature-list">
                <li><strong>Upload any PDF form</strong> - Court forms, legal documents, business forms, etc.</li>
                <li><strong>Click to fill</strong> - Select a field on the preview and type the value in the side panel—no separate values screen.</li>
                <li><strong>Auto-detection</strong> - Automatically finds fillable form fields and their exact positions</li>
                <li><strong>Coordinate extraction</strong> - Extracts X, Y, width, height, and field types</li>
                <li><strong>Background generation</strong> - Creates high-quality background images for overlay</li>
                <li><strong>Smart fallback</strong> - Works with encrypted PDFs using manual positioning</li>
                <li><strong>Scalable</strong> - Process hundreds of different forms without manual configuration</li>
        </ul>
        <?php endif; ?>
        
        <form id="processorForm" enctype="multipart/form-data">
            <div id="uploadInlineError" style="display:none;color:#b71c1c;margin:0 0 12px 0;font-weight:600;"></div>
            <?php if ($isFormManagement): ?>
            <input type="hidden" name="template_id" value="">
            <?php else: ?>
            <input 
                type="text" 
                name="template_id" 
                class="template-id-input" 
                placeholder="Template ID (optional - auto-generated if empty)" 
            />
            <?php endif; ?>
            
            <input 
                type="file" 
                id="pdfFile" 
                name="pdf_file" 
                accept=".pdf"
                class="file-input"
            />
            
            <div class="upload-area" id="uploadZone">
                <div class="upload-icon">📄</div>
                <h3 style="margin-bottom: 10px;">Click here to browse or drag and drop a form into this box.</h3>
                <?php if (!$isFormManagement): ?>
                <p style="color: #666; margin: 0;">Any court form, legal document, or fillable PDF</p>
                <?php endif; ?>
                <div id="fileInfo" class="file-info" style="display: none;"></div>
            </div>
            <?php if ($isFormManagement): ?>
            <div style="margin:12px 0 0 0;">
                <label for="existingServerPdfSelect" style="display:block;font-weight:600;margin:0 0 6px 0;color:#334155;">Or use existing server PDF</label>
                <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                    <select id="existingServerPdfSelect" class="pdftimesaver-input" style="min-width:280px;flex:1;">
                        <option value="">Select existing PDF...</option>
                    </select>
                    <button type="button" class="browse-btn" id="existingServerPdfRefreshBtn">Refresh</button>
                </div>
                <div id="existingServerPdfStatus" class="form-search-status" style="margin-top:6px;" aria-live="polite"></div>
            </div>
            <?php endif; ?>

            <button type="submit" class="submit-btn" id="submitBtn"><?php echo $isFormManagement ? 'Next: Analyze form fields' : 'Next: Analyze &amp; extract fields'; ?></button>
        </form>
    </div>

    </div>

    <div class="wizard-pane" data-wizard-pane="2">
        <div class="upload-section" style="padding-top: 20px;">
            <h2 style="margin-top:0;"><?php echo $isFormManagement ? 'Field Alignment Editor' : 'Extraction results'; ?></h2>
            <p style="color:#666;"><?php echo $isFormManagement
                ? 'Click a field on the preview to open the panel—set the value that will go on the PDF, then adjust position, size, and type as needed. Changes save automatically.'
                : 'Click a field to edit its value in the panel; drag highlights to move boxes. Everything saves automatically.'; ?></p>
            <?php if ($isFormManagement): ?>
            <div class="form-edit-header-actions">
                <button type="button" id="deleteCurrentFormBtn" class="form-delete-icon-btn" title="Delete this form" aria-label="Delete this form">🗑️</button>
            </div>
            <?php endif; ?>
            <?php if ($isFormManagement): ?>
            <div class="form-manager-panel" style="margin-bottom: 12px;">
                <h5 style="margin-bottom: 10px;">Form identity</h5>
                <div class="form-manager-grid">
                    <div>
                        <label for="formNumberInput">Form Number</label>
                        <input type="text" id="formNumberInput" placeholder="e.g. FL-200" autocomplete="off">
                    </div>
                    <div>
                        <label for="formNameInput">Form Name</label>
                        <input type="text" id="formNameInput" placeholder="e.g. Petition to Establish Parental Relationship" autocomplete="off">
                    </div>
                    <div>
                        <label for="globalFormFontFamilySelect">Global font (all fields)</label>
                        <select id="globalFormFontFamilySelect">
                            <?php foreach ($availableFonts as $font): ?>
                                <option value="<?php echo htmlspecialchars((string)$font); ?>"<?php echo strcasecmp((string)$font, 'Times') === 0 ? ' selected' : ''; ?>><?php echo htmlspecialchars((string)$font); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="globalFormFontSizeInput">Global font size (px)</label>
                        <input type="number" id="globalFormFontSizeInput" step="1" min="<?php echo (int)($fieldMetricsJs['MIN_FONT_PX'] ?? 8); ?>" max="<?php echo (int)($fieldMetricsJs['MAX_FONT_PX'] ?? 32); ?>" value="<?php echo (int)($fieldMetricsJs['DEFAULT_FONT_PX'] ?? 13); ?>">
                    </div>
                </div>
                <p id="formSearchKeyHint" style="display:none;margin:8px 0 0 0;font-size:12px;color:#475569;">
                    Search listing uses: <strong id="formSearchKeyHintValue"></strong>
                </p>
            </div>
            <?php endif; ?>
            <div class="form-manager-layout" id="formManagerLayout">
                <div class="results-section" id="results" style="display:block;"></div>
                <aside class="form-manager-sidebar" id="formManagerSidebar">
                    <div class="form-manager-sidebar-header">
                        <h4>Field properties</h4>
                        <button type="button" class="form-manager-sidebar-close" id="closeFieldSidebarBtn" aria-label="Clear selected field" title="Clear selection">Clear</button>
                    </div>
                    <div class="form-manager-sidebar-body">
                    <div class="field-selection-status" id="selectedFieldStatus">No field selected. Click a field on the preview to edit its properties.</div>
                    <p class="field-internal-key" id="fieldInternalKeyRow" hidden>Key: <code id="fieldInternalKeyDisplay"></code></p>
                    <p id="fieldValueLockedNote" class="field-value-locked-note" hidden style="font-size:12px; color:#5b21b6; margin:6px 0 10px 0;">This value comes from a custom field stored in the database (not editable here).</p>

                    <div class="form-manager-panel" id="fieldMappingPanel">
                        <label for="customFieldLocationSelect">Custom field location</label>
                        <select id="customFieldLocationSelect">
                            <option value="firm">For the Firm</option>
                            <option value="client">For the Client</option>
                            <option value="court">For the Court</option>
                            <option value="case">For the Case</option>
                        </select>
                        <label for="customFieldSelect">Assign custom field</label>
                        <select id="customFieldSelect"></select>
                        <p id="customFieldAutoMatchNote" hidden style="font-size:12px; color:#7c3aed; margin:6px 0 2px 0; font-weight:600;">PDF field ↔ Field Manager: matched by catalog matching tag.</p>
                        <button type="button" class="browse-btn" id="suggestAliasBtn" style="width:100%; margin-top:8px;">Suggest alias from this mapping</button>
                        <button type="button" id="deleteSelectedFieldBtn" style="width:100%; margin-top:8px; padding:10px 12px; border:1px solid #dc2626; border-radius:6px; background:#fee2e2; color:#991b1b; font-weight:600; cursor:pointer;">Delete selected field</button>
                    </div>

                    <div class="form-manager-panel" id="fieldEditorPanel">
                        <h5>Field Editor</h5>
                        <input type="hidden" id="fieldLabelInput" value="">
                        <div class="form-manager-grid">
                            <div>
                                <label for="fieldXInput">X coordinate</label>
                                <input type="number" id="fieldXInput" step="0.1">
                            </div>
                            <div>
                                <label for="fieldYInput">Y coordinate</label>
                                <input type="number" id="fieldYInput" step="0.1">
                            </div>
                            <div>
                                <label for="fieldWidthInput">Field width</label>
                                <input type="number" id="fieldWidthInput" step="0.1">
                            </div>
                            <div>
                                <label for="fieldHeightInput">Field height</label>
                                <input type="number" id="fieldHeightInput" step="0.1">
                            </div>
                        </div>
                        <label for="fieldTypeSelect">Field type</label>
                        <select id="fieldTypeSelect">
                            <option value="text">Text</option>
                            <option value="checkbox">Checkbox</option>
                            <option value="radio">Radio</option>
                            <option value="number">Number</option>
                            <option value="date">Date</option>
                            <option value="select">Select</option>
                        </select>
                        <div id="fieldValueTextRow">
                            <label for="fieldValueInput">Value (on generated PDF)</label>
                            <input type="text" id="fieldValueInput" placeholder="Field value">
                        </div>
                        <div id="fieldValueCheckboxRow" style="display:none;">
                            <label style="display:flex; align-items:center; gap:8px;">
                                <input type="checkbox" id="fieldValueCheckbox" style="width:auto; margin-bottom:0;">
                                Value (checked)
                            </label>
                        </div>
                        <label for="fieldFontFamilySelect">Font</label>
                        <select id="fieldFontFamilySelect">
                            <?php foreach ($availableFonts as $font): ?>
                                <option value="<?php echo htmlspecialchars((string)$font); ?>"><?php echo htmlspecialchars((string)$font); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label for="fieldFontSizeInput">Font size (px)</label>
                        <select id="fieldFontSizeInput" class="pdftimesaver-input">
                            <?php
                            $fieldSidebarMinFontPx = (int)round((($fieldMetricsJs['MIN_FONT_PT'] ?? 6) * 96) / 72);
                            $fieldSidebarMaxFontPx = 24; // Match Fill Out Forms sidebar dropdown behavior.
                            for ($fs = $fieldSidebarMinFontPx; $fs <= $fieldSidebarMaxFontPx; $fs++): ?>
                                <option value="<?php echo $fs; ?>"<?php echo $fs === 13 ? ' selected' : ''; ?>><?php echo $fs; ?> px</option>
                            <?php endfor; ?>
                        </select>
                        <label for="fieldFontColorInput">Font color</label>
                        <input type="color" id="fieldFontColorInput" value="#000000">
                        <label>Text style</label>
                        <div class="form-style-flags">
                            <label><input type="checkbox" id="fieldBoldInput"> Bold</label>
                            <label><input type="checkbox" id="fieldItalicInput"> Italic</label>
                            <label><input type="checkbox" id="fieldUnderlineInput"> Underline</label>
                            <label><input type="checkbox" id="fieldStrikeInput"> Strike through</label>
                        </div>
                    </div>
                    <div id="formLocationFieldWrap" class="form-manager-panel">
                        <label for="formLocationInput">Form location</label>
                        <textarea id="formLocationInput" name="form_location" rows="2" placeholder="Jurisdiction, county, court / venue — optional. Included in form search." autocomplete="off" style="width:100%;box-sizing:border-box;min-height:56px;resize:vertical;font:inherit;"></textarea>
                    </div>

                    </div>
                    <?php if ($isFormManagement): ?>
                    <div class="form-manager-sidebar-footer">
                        <div class="form-insert-dock-wrap">
                            <div id="formInsertFieldToolbar" class="form-insert-dock" style="display:none;" role="complementary" aria-label="Insert field dock">
                                <button type="button" class="browse-btn" id="insertManualFieldBtn" style="width:auto;">Add New Field</button>
                                <p>Adds a field on page 1. Uses selected field settings when available.</p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </aside>
            </div>
            <div id="wizardGenerateStatus" style="margin-top:16px;"></div>
            <div class="wizard-nav">
                <button type="button" class="browse-btn wizard-action-btn" id="wizardBackTo1">← Back</button>
                <div style="display:flex; flex-wrap:wrap; gap:12px; align-items:center; margin-left:auto;">
                    <?php if ($isFormManagement): ?>
                    <select id="wizardExportModeSelect" aria-label="Export mode" title="Export mode" style="min-width:160px;padding:8px 10px;border-radius:6px;border:1px solid #cbd5e1;margin-bottom:0;">
                        <option value="test" selected>Test Form</option>
                        <option value="actual">Actual Form</option>
                    </select>
                    <?php else: ?>
                    <label style="display:flex; align-items:center; gap:8px; font-size:14px; color:#334155; cursor:pointer; user-select:none;">
                        <input type="checkbox" id="wizardShowSampleData" checked>
                        Show sample data on export
                    </label>
                    <?php endif; ?>
                    <button type="button" class="browse-btn wizard-action-btn" id="wizardGeneratePdfBtn"><?php echo $isFormManagement ? 'Export' : 'Generate PDF'; ?></button>
                    <?php if ($isFormManagement): ?>
                    <button type="button" class="browse-btn wizard-action-btn" id="wizardFinishedBtn" title="Reset wizard (or redirect if opened from another flow)">Finished</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if (!$isFormManagement): ?>
    <div class="wizard-pane" data-wizard-pane="3">
        <div class="upload-section">
            <h2 style="margin-top:0;">Download</h2>
            <div id="wizardDownloadArea"></div>
            <div class="wizard-nav" style="margin-top:12px; flex-wrap:wrap; gap:10px;">
                <button type="button" class="browse-btn" data-action="wizard-finish-bundle" title="Download positions, background paths, and PDF reference as one JSON file">Finish — save template JSON</button>
            </div>
            <div class="wizard-nav">
                <button type="button" class="browse-btn" id="wizardBackToValues">← Back to editor</button>
                <button type="button" class="browse-btn" id="wizardRestartBtn">Start over</button>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('processorForm');
    const formTemplatePrefill = <?php echo json_encode([
        'templateId' => $prefillTemplateId,
        'detectedFirmName' => trim((string)(is_array($prefillTemplateRecord) ? ($prefillTemplateRecord['detectedFirmName'] ?? '') : '')),
    ], JSON_UNESCAPED_SLASHES); ?>;
    const managedFormTemplates = <?php echo json_encode(array_values(array_map(static function (array $row): array {
        $tid = (string)($row['templateId'] ?? '');
        return [
            'templateId' => $tid,
            'formName' => (string)($row['formName'] ?? $tid),
            'sourceFileName' => (string)($row['sourceFileName'] ?? ''),
            'formLocation' => (string)($row['formLocation'] ?? ''),
        ];
    }, $managedFormTemplates)), JSON_UNESCAPED_SLASHES); ?>;
    const formsManagerFinishRedirect = <?php echo json_encode((string)($_GET['finish_redirect'] ?? '')); ?>;
    const wizardState = {
        step: 1,
        templateId: '',
        pendingRegistryCommit: false,
        sourceFileName: '',
        positionsMap: {},
        positionsSaved: false,
        selectedFieldKey: '',
        fieldDefaults: {},
        customFieldAssignments: {},
        detectedFirmName: '',
        formNumber: '',
        formName: '',
        formLocation: '',
        /** Per-page padding translate (mm) derived from padding spinboxes; Apply is absolute vs. drag baseline. */
        pagePaddingDeltaMm: {},
        /** Per-field XY in mm before page-padding translate. */
        fieldUnpaddedMm: {}
    };
    let techPreviewResizeHooked = false;
    if (form && formTemplatePrefill.templateId) {
        const tidEl = form.querySelector('[name="template_id"]');
        if (tidEl && !String(tidEl.value || '').trim()) {
            tidEl.value = formTemplatePrefill.templateId;
        }
    }
    if (formTemplatePrefill.detectedFirmName) {
        wizardState.detectedFirmName = formTemplatePrefill.detectedFirmName;
    }
    const results = document.getElementById('results');
    const submitBtn = document.getElementById('submitBtn');
    const pdfFileInput = document.getElementById('pdfFile');
    const browseBtn = document.getElementById('browseBtn');
    const uploadZone = document.getElementById('uploadZone');
    const uploadFormSection = document.getElementById('uploadFormSection');
    const fileInfo = document.getElementById('fileInfo');
    const existingServerPdfSelect = document.getElementById('existingServerPdfSelect');
    const existingServerPdfRefreshBtn = document.getElementById('existingServerPdfRefreshBtn');
    const existingServerPdfStatus = document.getElementById('existingServerPdfStatus');
    const formSearchInput = document.getElementById('formSearchInput');
    const formSearchBtn = document.getElementById('formSearchBtn');
    const formBrowseBtn = document.getElementById('formBrowseBtn');
    const formSearchList = document.getElementById('formSearchList');
    const formSearchStatus = document.getElementById('formSearchStatus');
    const addNewFormBtn = document.getElementById('addNewFormBtn');
    const deleteCurrentFormBtn = document.getElementById('deleteCurrentFormBtn');
    const formNumberInput = document.getElementById('formNumberInput');
    const formNameInput = document.getElementById('formNameInput');
    const formLocationInput = document.getElementById('formLocationInput');
    const refreshDiagnosticsBtn = document.getElementById('refreshDiagnosticsBtn');
    const toggleDiagnosticsBtn = document.getElementById('toggleDiagnosticsBtn');
    const diagnosticsPanel = document.getElementById('diagnosticsPanel');
    let diagnosticsLoaded = false;
    let formSearchLoadingTemplateId = '';
    let formSearchBrowseMode = false;
    
    function openPdfPicker() {
        if (!pdfFileInput) return;
        try {
            if (typeof pdfFileInput.showPicker === 'function') {
                pdfFileInput.showPicker();
                return;
            }
        } catch (err) {
            // Fall back to click() for browsers that block showPicker().
        }

        const prev = {
            display: pdfFileInput.style.display,
            position: pdfFileInput.style.position,
            left: pdfFileInput.style.left,
            top: pdfFileInput.style.top,
            opacity: pdfFileInput.style.opacity,
            zIndex: pdfFileInput.style.zIndex,
            pointerEvents: pdfFileInput.style.pointerEvents
        };

        // Some browsers ignore click() on display:none file inputs.
        pdfFileInput.style.display = 'block';
        pdfFileInput.style.position = 'fixed';
        pdfFileInput.style.left = '-9999px';
        pdfFileInput.style.top = '0';
        pdfFileInput.style.opacity = '0';
        pdfFileInput.style.zIndex = '-1';
        pdfFileInput.style.pointerEvents = 'none';

        pdfFileInput.click();

        setTimeout(() => {
            pdfFileInput.style.display = prev.display;
            pdfFileInput.style.position = prev.position;
            pdfFileInput.style.left = prev.left;
            pdfFileInput.style.top = prev.top;
            pdfFileInput.style.opacity = prev.opacity;
            pdfFileInput.style.zIndex = prev.zIndex;
            pdfFileInput.style.pointerEvents = prev.pointerEvents;
        }, 0);
    }

    function setExistingServerPdfStatus(message, tone = '') {
        if (!existingServerPdfStatus) return;
        existingServerPdfStatus.textContent = String(message || '');
        existingServerPdfStatus.classList.remove('is-loading', 'is-error', 'is-success');
        if (tone === 'loading') existingServerPdfStatus.classList.add('is-loading');
        if (tone === 'error') existingServerPdfStatus.classList.add('is-error');
        if (tone === 'success') existingServerPdfStatus.classList.add('is-success');
    }

    async function loadExistingServerPdfs() {
        if (!existingServerPdfSelect) return;
        setExistingServerPdfStatus('Loading PDFs...', 'loading');
        const previousValue = String(existingServerPdfSelect.value || '');
        try {
            const response = await fetch('?route=actions/list-pdfs', { cache: 'no-store' });
            const payload = await response.json();
            const rows = Array.isArray(payload?.pdfs) ? payload.pdfs : [];
            existingServerPdfSelect.innerHTML = '<option value="">Select existing PDF...</option>';
            rows
                .sort((a, b) => Number(b?.modified || 0) - Number(a?.modified || 0))
                .forEach((row) => {
                    const path = String(row?.path || '').trim();
                    if (!path) return;
                    const name = String(row?.name || path).trim();
                    const option = document.createElement('option');
                    option.value = path;
                    option.textContent = name;
                    existingServerPdfSelect.appendChild(option);
                });
            if (previousValue && Array.from(existingServerPdfSelect.options).some((o) => String(o.value) === previousValue)) {
                existingServerPdfSelect.value = previousValue;
            }
            setExistingServerPdfStatus(rows.length
                ? `Loaded ${rows.length} server PDF${rows.length === 1 ? '' : 's'}.`
                : 'No server PDFs found.', rows.length ? 'success' : '');
        } catch (err) {
            setExistingServerPdfStatus(err?.message || 'Failed to load server PDFs.', 'error');
        }
    }

    // Browse button - open native file picker
    browseBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        openPdfPicker();
    });
    
    // Upload zone click - open native file picker
    uploadZone?.addEventListener('click', () => {
        openPdfPicker();
    });
    existingServerPdfRefreshBtn?.addEventListener('click', () => {
        void loadExistingServerPdfs();
    });
    existingServerPdfSelect?.addEventListener('change', () => {
        if (!existingServerPdfSelect) return;
        if (String(existingServerPdfSelect.value || '').trim() !== '') {
            if (pdfFileInput) pdfFileInput.value = '';
            if (uploadZone) uploadZone.classList.remove('file-selected');
            if (fileInfo) {
                fileInfo.textContent = '';
                fileInfo.style.display = 'none';
            }
            setExistingServerPdfStatus('Using selected server PDF.', 'success');
        } else {
            setExistingServerPdfStatus('', '');
        }
    });
    
    // Drag and drop handlers
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        uploadZone?.addEventListener(eventName, preventDefaults, false);
    });
    
    uploadZone?.addEventListener('dragenter', () => {
        uploadZone.classList.add('dragover');
    });
    
    uploadZone?.addEventListener('dragleave', () => {
        uploadZone.classList.remove('dragover');
    });
    
    uploadZone?.addEventListener('drop', (e) => {
        uploadZone.classList.remove('dragover');
        const files = e.dataTransfer.files;
        if (files.length > 0 && files[0].type === 'application/pdf') {
            pdfFileInput.files = files;
            handleFileSelect(files[0]);
        }
    });
    
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    // Handle file selection
    pdfFileInput.addEventListener('change', function() {
        if (this.files && this.files.length > 0) {
            handleFileSelect(this.files[0]);
        }
    });

    if (formSearchInput) {
        formSearchInput.addEventListener('input', () => {
            formSearchBrowseMode = false;
            renderManagedFormSearchList();
        });
        formSearchInput.addEventListener('keydown', (ev) => {
            if (ev.key !== 'Enter') return;
            ev.preventDefault();
            formSearchBrowseMode = false;
            renderManagedFormSearchList();
        });
    }
    if (formSearchBtn) {
        formSearchBtn.addEventListener('click', () => {
            formSearchBrowseMode = false;
            renderManagedFormSearchList();
        });
    }
    if (formBrowseBtn) {
        formBrowseBtn.addEventListener('click', () => {
            if (formSearchInput) formSearchInput.value = '';
            formSearchBrowseMode = true;
            renderManagedFormSearchList();
        });
    }
    if (addNewFormBtn) {
        addNewFormBtn.addEventListener('click', () => {
            window.location.assign('?route=form-new');
        });
    }
    if (deleteCurrentFormBtn) {
        deleteCurrentFormBtn.addEventListener('click', async () => {
            const tid = String(wizardState.templateId || '').trim();
            if (!tid) {
                alert('Open a saved form first.');
                return;
            }
            if (!window.confirm(`Delete form "${tid}"? This cannot be undone.`)) {
                return;
            }
            const prev = deleteCurrentFormBtn.textContent;
            deleteCurrentFormBtn.disabled = true;
            deleteCurrentFormBtn.textContent = '…';
            try {
                const res = await fetch('?route=api/form-management/delete-template', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ template_id: tid }),
                });
                const payload = await res.json();
                if (!res.ok || !payload.success) {
                    throw new Error(payload.error || payload.message || `HTTP ${res.status}`);
                }
                const idx = managedFormTemplates.findIndex((row) => String(row?.templateId || '') === tid);
                if (idx >= 0) {
                    managedFormTemplates.splice(idx, 1);
                }
                resetWizardToStart();
                setUploadFormVisible(false);
                renderManagedFormSearchList();
                setFormSearchStatus(`Deleted "${tid}".`, 'success');
            } catch (err) {
                alert(err?.message || 'Failed to delete form.');
            } finally {
                deleteCurrentFormBtn.disabled = false;
                deleteCurrentFormBtn.textContent = prev || '🗑️';
            }
        });
    }
    formNumberInput?.addEventListener('input', () => {
        wizardState.formNumber = String(formNumberInput.value || '').trim();
        wizardState.positionsSaved = false;
        updateFormSearchKeyHint();
        schedulePositionsAutoSave();
    });
    formNameInput?.addEventListener('input', () => {
        wizardState.formName = String(formNameInput.value || '').trim();
        wizardState.positionsSaved = false;
        updateFormSearchKeyHint();
        schedulePositionsAutoSave();
    });
    formLocationInput?.addEventListener('input', () => {
        wizardState.formLocation = String(formLocationInput.value || '').trim();
        wizardState.positionsSaved = false;
        updateFormSearchKeyHint();
        schedulePositionsAutoSave();
    });
    if (formSearchList) {
        formSearchList.addEventListener('click', async (ev) => {
            const rowEl = ev.target && ev.target.closest ? ev.target.closest('[data-action="open-existing-form"]') : null;
            if (!rowEl) return;
            const tid = String(rowEl.getAttribute('data-template-id') || '').trim();
            if (!tid) return;
            const row = (managedFormTemplates || []).find((item) => String(item.templateId || '') === tid);
            if (!row) return;
            formSearchLoadingTemplateId = tid;
            renderManagedFormSearchList();
            try {
                await openExistingManagedForm(row);
            } finally {
                formSearchLoadingTemplateId = '';
                renderManagedFormSearchList();
            }
        });
        formSearchList.addEventListener('keydown', (ev) => {
            if (ev.key !== 'Enter' && ev.key !== ' ') return;
            const rowEl = ev.target && ev.target.closest ? ev.target.closest('[data-action="open-existing-form"]') : null;
            if (!rowEl) return;
            ev.preventDefault();
            rowEl.click();
        });
    }
    renderManagedFormSearchList();
    
    function handleFileSelect(file) {
        const uploadErr = document.getElementById('uploadInlineError');
        if (uploadErr) { uploadErr.style.display = 'none'; uploadErr.textContent = ''; }
        if (file.type !== 'application/pdf') {
            if (uploadErr) {
                uploadErr.style.display = 'block';
                uploadErr.textContent = 'Please select a PDF file.';
            }
            return;
        }
        
        uploadZone.classList.add('file-selected');
        fileInfo.style.display = 'block';
        fileInfo.textContent = `Selected: ${file.name} (${(file.size / 1024).toFixed(1)} KB)`;
        if (existingServerPdfSelect) {
            existingServerPdfSelect.value = '';
            setExistingServerPdfStatus('Using local upload.', 'success');
        }
    }

    function setUploadFormVisible(visible) {
        if (!uploadFormSection) return;
        uploadFormSection.style.display = visible ? '' : 'none';
    }

    function setFormSearchStatus(message, tone = '') {
        if (!formSearchStatus) return;
        formSearchStatus.textContent = String(message || '');
        formSearchStatus.classList.remove('is-loading', 'is-error', 'is-success');
        if (tone === 'loading') formSearchStatus.classList.add('is-loading');
        if (tone === 'error') formSearchStatus.classList.add('is-error');
        if (tone === 'success') formSearchStatus.classList.add('is-success');
    }

    function formSearchDisplayLabel(row) {
        const tid = String(row?.templateId || '').trim();
        const name = String(row?.formName || '').trim();
        if (!tid) return '';
        if (!name || name.toLowerCase() === tid.toLowerCase()) {
            return tid;
        }
        return name;
    }

    function composeStoredFormIdentityName(templateId, formNumber, formName, fallbackName = '') {
        const tid = String(templateId || '').trim();
        const num = String(formNumber || '').trim().toUpperCase();
        const name = String(formName || '').trim();
        const fallback = String(fallbackName || '').trim();
        let resolved = name || fallback;
        if (num) {
            resolved = resolved ? `${num} - ${resolved}` : num;
        }
        if (!resolved) {
            resolved = tid;
        }
        return resolved;
    }

    function syncManagedTemplateRowLocal(templateId, patch = {}) {
        const tid = String(templateId || '').trim();
        if (!tid || !Array.isArray(managedFormTemplates)) return;
        const idx = managedFormTemplates.findIndex((row) => String(row?.templateId || '').trim() === tid);
        if (idx < 0) return;
        const row = managedFormTemplates[idx] && typeof managedFormTemplates[idx] === 'object'
            ? managedFormTemplates[idx]
            : {};
        const incomingNum = Object.prototype.hasOwnProperty.call(patch, 'formNumber')
            ? String(patch.formNumber || '').trim()
            : deriveInitialFormIdentity(tid, String(row.formName || '')).number;
        const incomingName = Object.prototype.hasOwnProperty.call(patch, 'formName')
            ? String(patch.formName || '').trim()
            : deriveInitialFormIdentity(tid, String(row.formName || '')).name;
        const nextLabel = composeStoredFormIdentityName(tid, incomingNum, incomingName, String(row.formName || ''));
        const nextLoc = Object.prototype.hasOwnProperty.call(patch, 'formLocation')
            ? String(patch.formLocation || '').trim()
            : String(row.formLocation || '').trim();
        managedFormTemplates[idx] = {
            ...row,
            templateId: tid,
            formName: nextLabel,
            formLocation: nextLoc,
        };
    }

    function normalizeUnicodeDashes(value) {
        let text = String(value || '');
        text = text.replace(/\u2014|\u2013/g, ' - ');
        text = text.replace(/\s*-\s*/g, ' - ');
        text = text.replace(/\s+/g, ' ').trim();
        return text.replace(/^[\s-]+|[\s-]+$/g, '');
    }

    function deriveFormNumberFromTemplateId(templateId) {
        const raw = String(templateId || '').trim();
        if (!raw) return '';
        const normalized = raw.replace(/_/g, '-');
        const match = normalized.match(/([a-z]{1,4}-\d{1,4})/i);
        return match && match[1] ? String(match[1]).toUpperCase() : '';
    }

    function deriveInitialFormIdentity(templateId, rawFormName) {
        const tid = String(templateId || '').trim();
        const incoming = normalizeUnicodeDashes(String(rawFormName || '').trim());
        let number = '';
        let name = '';
        if (incoming && incoming.toLowerCase() !== tid.toLowerCase()) {
            const prefixMatch = incoming.match(/^([A-Z]{1,4}-\d{1,4})\s*-\s*(.+)$/i)
                || incoming.match(/^([A-Z]{1,4}-\d{1,4})\s+(.+)$/i);
            if (prefixMatch) {
                number = String(prefixMatch[1] || '').trim().toUpperCase();
                name = normalizeUnicodeDashes(String(prefixMatch[2] || '').trim());
            } else {
                name = incoming;
            }
        }
        if (!number) {
            number = deriveFormNumberFromTemplateId(tid);
        }
        if (number && name) {
            const stripPrefix = name.match(new RegExp('^' + number.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '\\s*-\\s*(.+)$', 'i'));
            if (stripPrefix) {
                name = normalizeUnicodeDashes(String(stripPrefix[1] || '').trim());
            }
        }
        return { number, name };
    }

    function matchesManagedFormSearch(row, term) {
        const q = String(term || '').trim().toLowerCase();
        if (!q) return true;
        const haystack = [
            row?.templateId,
            row?.formName,
            row?.sourceFileName,
            row?.formLocation
        ].map((v) => String(v || '').toLowerCase());
        return haystack.some((v) => v.includes(q));
    }

    async function openExistingManagedForm(row) {
        const tid = String(row?.templateId || '').trim();
        if (!tid || !form) return;
        if (isFormManagement && wizardState.step !== 2) {
            syncFormManagerHistoryStep(2, 'push');
            formManagerHistoryStep = 2;
            wizardState.step = 2;
        }
        const tidInput = form.querySelector('[name="template_id"]');
        if (tidInput) {
            tidInput.value = tid;
        }
        setUploadFormVisible(true);

        const uploadErr = document.getElementById('uploadInlineError');
        if (uploadErr) {
            uploadErr.style.display = 'none';
            uploadErr.textContent = '';
        }
        setFormSearchStatus(`Loading form "${tid}"...`, 'loading');

        try {
            const res = await fetch(`?route=actions/form-template-editor-data&template_id=${encodeURIComponent(tid)}`, { cache: 'no-store' });
            const payload = await res.json();
            if (!res.ok || !payload.success) {
                throw new Error(payload.message || `HTTP ${res.status}`);
            }
            results.style.display = 'block';
            showWizardStep(2);
            renderEditorFromTemplateData(payload);
            setFormSearchStatus(`Loaded "${tid}".`, 'success');
            return;
        } catch (err) {
            if (uploadErr) {
                uploadErr.style.display = 'block';
                uploadErr.textContent = `Could not load saved editor data for "${tid}". Upload the PDF again to continue editing.`;
            }
            const errMsg = (err && err.message) ? String(err.message) : 'Failed to load form.';
            setFormSearchStatus(`Could not load "${tid}": ${errMsg}`, 'error');
            return;
        }
    }

    function renderManagedFormSearchList() {
        if (!formSearchList) return;
        const term = formSearchInput ? formSearchInput.value : '';
        const q = String(term || '').trim();
        if (!formSearchBrowseMode && !q) {
            formSearchList.innerHTML = '<div class="form-search-empty">Type to search forms, or click Browse.</div>';
            setFormSearchStatus('', '');
            return;
        }
        const rows = (Array.isArray(managedFormTemplates) ? managedFormTemplates : [])
            .filter((row) => formSearchBrowseMode ? true : matchesManagedFormSearch(row, term));
        if (!rows.length) {
            formSearchList.innerHTML = '<div class="form-search-empty">' + (formSearchBrowseMode ? 'No forms found in database.' : 'No matching forms found.') + '</div>';
            setFormSearchStatus(formSearchBrowseMode ? 'Browse mode: showing all forms.' : '', '');
            return;
        }
        setFormSearchStatus(formSearchBrowseMode ? `Browse mode: showing ${rows.length} form${rows.length === 1 ? '' : 's'}.` : '', 'success');
        formSearchList.innerHTML = rows.map((row) => {
            const templateId = String(row?.templateId || '').trim();
            const label = formSearchDisplayLabel(row);
            const sourceName = String(row?.sourceFileName || '').trim();
            const loc = String(row?.formLocation || '').trim();
            const metaParts = [];
            if (sourceName) metaParts.push(`Source: ${sourceName}`);
            if (loc) metaParts.push(loc);
            const meta = metaParts.length ? metaParts.join(' · ') : 'Stored template';
            const isLoading = formSearchLoadingTemplateId !== '' && formSearchLoadingTemplateId === templateId;
            return `
                <div class="form-search-row${isLoading ? ' is-loading' : ''}" data-action="open-existing-form" data-template-id="${escapeAttr(templateId)}" role="button" tabindex="0" aria-label="Open ${escapeAttr(label)}">
                    <div>
                        <div class="form-search-label">${escapeHtml(label)}</div>
                        <div class="form-search-meta">${escapeHtml(meta)}</div>
                    </div>
                    <div class="form-search-meta">${isLoading ? '<span class="form-search-btn-loading">Loading...</span>' : 'Open'}</div>
                </div>
            `;
        }).join('');
    }
    
    function setDiagnosticsVisibility(visible) {
        if (!diagnosticsPanel || !toggleDiagnosticsBtn) return;
        diagnosticsPanel.hidden = !visible;
        toggleDiagnosticsBtn.setAttribute('aria-expanded', visible ? 'true' : 'false');
        toggleDiagnosticsBtn.textContent = visible ? 'Hide server requirements check' : 'Show server requirements check';
    }

    if (toggleDiagnosticsBtn && diagnosticsPanel) {
        toggleDiagnosticsBtn.addEventListener('click', () => {
            const willShow = diagnosticsPanel.hidden;
            setDiagnosticsVisibility(willShow);
            if (willShow && !diagnosticsLoaded) {
                diagnosticsLoaded = true;
                loadDiagnostics(false);
            }
        });
        setDiagnosticsVisibility(false);
    }

    if (refreshDiagnosticsBtn) {
        refreshDiagnosticsBtn.addEventListener('click', () => {
            if (diagnosticsPanel && diagnosticsPanel.hidden) {
                setDiagnosticsVisibility(true);
            }
            diagnosticsLoaded = true;
            loadDiagnostics(true);
        });
    }
    const isFormManagement = <?php echo $isFormManagement ? 'true' : 'false'; ?>;
    const isFormNew = <?php echo $isFormNew ? 'true' : 'false'; ?>;
    let formManagerHistoryStep = null;
    let suppressFormManagerHistory = false;

    function updateFormSearchKeyHint() {
        const hint = document.getElementById('formSearchKeyHint');
        const hintVal = document.getElementById('formSearchKeyHintValue');
        if (!hint || !hintVal) return;
        const num = formNumberInput ? String(formNumberInput.value || '').trim() : '';
        const name = formNameInput ? String(formNameInput.value || '').trim() : '';
        const loc = formLocationInput ? String(formLocationInput.value || '').trim() : '';
        if (!num && !name && !loc) {
            hint.style.display = 'none';
            return;
        }
        hintVal.textContent = [num, name, loc].filter(Boolean).join(' — ');
        hint.style.display = '';
    }

    if (isFormManagement && !isFormNew) {
        setUploadFormVisible(false);
    }
    if (isFormManagement && formTemplatePrefill.templateId) {
        const preloadRow = (managedFormTemplates || []).find((row) => String(row.templateId || '') === formTemplatePrefill.templateId);
        if (preloadRow) {
            openExistingManagedForm(preloadRow);
        } else {
            setUploadFormVisible(true);
        }
    }
    if (isFormManagement) {
        const stepFromState = (window.history.state && Number(window.history.state.formManagerStep) === 2) ? 2 : 1;
        const stepFromUrl = (new URL(window.location.href).searchParams.get('fm_step') === '2') ? 2 : 1;
        const initialStep = stepFromState === 2 || stepFromUrl === 2 ? 2 : 1;
        suppressFormManagerHistory = true;
        showWizardStep(initialStep);
        suppressFormManagerHistory = false;
        syncFormManagerHistoryStep(initialStep, 'replace');
        window.addEventListener('popstate', (ev) => {
            const fromState = (ev && ev.state && Number(ev.state.formManagerStep) === 2) ? 2 : 1;
            const fromUrl = (new URL(window.location.href).searchParams.get('fm_step') === '2') ? 2 : 1;
            const targetStep = fromState === 2 || fromUrl === 2 ? 2 : 1;
            suppressFormManagerHistory = true;
            showWizardStep(targetStep);
            suppressFormManagerHistory = false;
            if (targetStep === 1 && !isFormNew) {
                setUploadFormVisible(false);
                clearUploadInlineError();
            }
        });
        void loadExistingServerPdfs();
    }
    const currentFirmIdForAlign = <?php echo json_encode(isset($currentFirmId) ? (string)$currentFirmId : 'default_firm'); ?>;
    const customFieldMatchingMode = <?php echo json_encode($customFieldMatchingMode, JSON_UNESCAPED_SLASHES); ?>;
    const formImporterAliases = <?php echo json_encode($formImporterAliases, JSON_UNESCAPED_SLASHES); ?>;
    const customFieldCatalog = <?php
        $catalog = [];
        foreach ($formCustomFields as $row) {
            $catalog[] = [
                'linkId' => (string)($row['linkId'] ?? ''),
                'displayName' => (string)($row['displayName'] ?? ''),
                'fieldType' => strtolower((string)($row['fieldType'] ?? 'text')),
                'matchingTag' => (string)($row['matchingTag'] ?? ($row['linkId'] ?? '')),
                'location' => strtolower((string)($row['location'] ?? 'firm')),
                'value' => (string)($row['value'] ?? ''),
            ];
        }
        echo json_encode($catalog, JSON_UNESCAPED_SLASHES);
    ?>;
    const initialCustomFieldCatalog = JSON.parse(JSON.stringify(customFieldCatalog));
    const autoDetectedLinkCache = new Map();
    let autoDetectedLinkCacheVersion = 0;
    let autoDetectedLinkCacheHits = 0;
    let autoDetectedLinkCacheMisses = 0;

    function invalidateAutoDetectedLinkCache() {
        autoDetectedLinkCache.clear();
        autoDetectedLinkCacheVersion += 1;
        autoDetectedLinkCacheHits = 0;
        autoDetectedLinkCacheMisses = 0;
    }

    function getCatalogEntryByLinkId(linkId) {
        const id = String(linkId || '').trim();
        return customFieldCatalog.find((e) => String(e.linkId || '') === id);
    }

    function isKnownCustomFieldLinkId(linkId) {
        return !!getCatalogEntryByLinkId(linkId);
    }

    function normalizeMatchingToken(raw) {
        return String(raw || '')
            .trim()
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '_')
            .replace(/^_+|_+$/g, '');
    }

    function normalizeMatchingPattern(raw) {
        return String(raw || '')
            .trim()
            .toLowerCase()
            .replace(/[^a-z0-9*?#]+/g, '_')
            .replace(/^_+|_+$/g, '');
    }

    function wildcardPatternToRegex(pattern) {
        const escaped = String(pattern || '')
            .replace(/[.+^${}()|[\]\\]/g, '\\$&')
            .replace(/\*/g, '.*')
            .replace(/\?/g, '.')
            .replace(/#/g, '\\d+');
        return new RegExp('^' + escaped + '$', 'i');
    }

    function getManualCustomFieldAssignment(key) {
        const assign = wizardState.customFieldAssignments[key];
        if (!assign || assign.isManual !== true) {
            return null;
        }
        return assign;
    }

    function getEffectiveCustomFieldLinkId(key, meta) {
        const manualAssign = getManualCustomFieldAssignment(key);
        if (manualAssign) {
            const manualLinkId = String(manualAssign.linkId || '').trim();
            return isKnownCustomFieldLinkId(manualLinkId) ? manualLinkId : '';
        }
        const metaLinkedId = String(meta?.customFieldLinkId || '').trim();
        if (metaLinkedId && isKnownCustomFieldLinkId(metaLinkedId)) {
            return metaLinkedId;
        }
        return getAutoDetectedCustomFieldLinkId(key, meta);
    }

    function normalizeCatalogComponentType(value) {
        const v = String(value || '').trim().toLowerCase();
        if (!v) return 'any';
        if (v.includes('check') || v.includes('radio') || v.includes('bool')) {
            return 'checkable';
        }
        if (v.includes('textarea') || v.includes('multi')) {
            return 'textarea';
        }
        return 'text';
    }

    function resolvePositionComponentType(meta) {
        const raw = String(meta?.type || meta?.fieldType || '').trim().toLowerCase();
        if (raw && (raw.includes('check') || raw.includes('radio') || raw.includes('option') || raw.includes('choice'))) {
            return 'checkable';
        }
        if (raw && (raw.includes('textarea') || raw.includes('multi'))) {
            return 'textarea';
        }
        return 'text';
    }

    function catalogEntrySupportsComponent(entry, meta) {
        const target = normalizeCatalogComponentType(entry?.fieldType || entry?.field_type || 'text');
        if (target === 'any') {
            return true;
        }
        const actual = resolvePositionComponentType(meta);
        if (target === 'text') {
            return actual === 'text' || actual === 'textarea';
        }
        return actual === target;
    }

    function matchingTagMatches(keyText, tagText) {
        const rawCandidate = String(keyText || '').trim();
        const rawTag = String(tagText || '').trim();
        if (!rawCandidate || !rawTag) return false;
        const candidate = normalizeMatchingToken(rawCandidate);
        const tag = normalizeMatchingToken(rawTag);
        if (/[*?#]/.test(rawTag)) {
            const rawRegex = wildcardPatternToRegex(rawTag.toLowerCase());
            if (rawRegex.test(rawCandidate.toLowerCase())) {
                return true;
            }
            const normRegex = wildcardPatternToRegex(normalizeMatchingPattern(rawTag));
            if (candidate && normRegex.test(candidate)) {
                return true;
            }
        }
        if (!candidate || !tag) return false;
        if (candidate === tag || candidate.includes(tag)) {
            return true;
        }
        const tokens = tag.split('_').filter(Boolean);
        if (!tokens.length) return false;
        return tokens.every((token) => candidate.includes(token));
    }

    function getAutoDetectedCustomFieldLinkId(key, meta) {
        const rawKey = String(key || '').trim();
        if (!rawKey) {
            return '';
        }
        const cacheKey = [
            autoDetectedLinkCacheVersion,
            String(customFieldMatchingMode || 'exact').toLowerCase(),
            rawKey,
            String(meta?.name || ''),
            String(meta?.canonicalName || ''),
            String(meta?.fieldName || ''),
            String(meta?.label || ''),
            String(meta?.type || meta?.fieldType || ''),
        ].join('|');
        if (autoDetectedLinkCache.has(cacheKey)) {
            autoDetectedLinkCacheHits += 1;
            return autoDetectedLinkCache.get(cacheKey) || '';
        }
        autoDetectedLinkCacheMisses += 1;
        const candidates = [rawKey];
        const extras = [meta?.name, meta?.canonicalName, meta?.fieldName, meta?.label];
        for (const ex of extras) {
            const s = String(ex || '').trim();
            if (s) candidates.push(s);
        }
        let best = null;
        for (const entry of (Array.isArray(customFieldCatalog) ? customFieldCatalog : [])) {
            if (!entry) {
                continue;
            }
            const linkId = String(entry.linkId || '').trim();
            const matchingTag = String(entry.matchingTag || linkId || '').trim();
            if (!linkId || !matchingTag || !isKnownCustomFieldLinkId(linkId)) {
                continue;
            }
            if (!catalogEntrySupportsComponent(entry, meta)) {
                continue;
            }
            let matched = false;
            if (String(customFieldMatchingMode || '').toLowerCase() === 'regex') {
                for (const candidate of candidates) {
                    try {
                        const regex = new RegExp(matchingTag, 'i');
                        if (regex.test(candidate)) {
                            matched = true;
                            break;
                        }
                    } catch (e) {
                        matched = false;
                    }
                }
            }
            if (!matched) {
                for (const candidate of candidates) {
                    if (matchingTagMatches(candidate, matchingTag)) {
                        matched = true;
                        break;
                    }
                }
            }
            if (!matched) {
                continue;
            }
            const tagNorm = normalizeMatchingToken(matchingTag);
            const score = (tagNorm.split('_').filter(Boolean).length * 25) + (tagNorm.length * 10);
            if (!best || score > best.score) {
                best = { linkId, score };
            }
        }
        const resolved = best ? best.linkId : '';
        autoDetectedLinkCache.set(cacheKey, resolved);
        return resolved;
    }

    function isAutoMatchedCustomField(key, meta) {
        if (getManualCustomFieldAssignment(key)) {
            return false;
        }
        const explicitLinkId = String(meta?.customFieldLinkId || '').trim();
        if (explicitLinkId && isKnownCustomFieldLinkId(explicitLinkId)) {
            return true;
        }
        return !!getAutoDetectedCustomFieldLinkId(key, meta);
    }

    function getCustomFieldOverrideValue(key, meta) {
        const linkId = getEffectiveCustomFieldLinkId(key, meta);
        if (!linkId) {
            return null;
        }
        const entry = getCatalogEntryByLinkId(linkId);
        const v = String(entry?.value ?? '').trim();
        if (v === '') {
            return null;
        }
        return v;
    }

    function isCustomFieldLocked(key, meta) {
        return getCustomFieldOverrideValue(key, meta) !== null;
    }

    function getExportIncludeSamples() {
        const modeSel = document.getElementById('wizardExportModeSelect');
        if (modeSel) {
            return modeSel.value === 'test';
        }
        const el = document.getElementById('wizardShowSampleData');
        return !!(el && el.checked);
    }

    function getPreviewHideSampleData() {
        const el = document.getElementById('previewHideSampleData');
        return !!(el && el.checked);
    }

    function getPreviewHideLinkedData() {
        const el = document.getElementById('previewHideLinkedData');
        return !!(el && el.checked);
    }

    function getNormalizedScopeValue(raw) {
        return String(raw || '')
            .trim()
            .toLowerCase()
            .replace(/[^a-z0-9_-]+/g, '_')
            .replace(/^_+|_+$/g, '')
            .slice(0, 120);
    }

    function resolveTemplateFamilyFromId(templateId) {
        const normalized = String(templateId || '').trim().toLowerCase();
        if (!normalized) return '';
        let match = normalized.match(/^(fl[_-]?\d{2,3})/i);
        if (match && match[1]) return getNormalizedScopeValue(String(match[1]).replace(/-/g, '_'));
        match = normalized.match(/^(w[_-]?\d{1,2})/i);
        if (match && match[1]) return getNormalizedScopeValue(String(match[1]).replace(/-/g, '_'));
        match = normalized.match(/^([a-z]+[_-]?\d{1,3})/i);
        if (match && match[1]) return getNormalizedScopeValue(String(match[1]).replace(/-/g, '_'));
        return '';
    }

    const selectedFieldStatus = document.getElementById('selectedFieldStatus');
    const formManagerLayout = document.getElementById('formManagerLayout');
    const formManagerSidebar = document.getElementById('formManagerSidebar');
    const closeFieldSidebarBtn = document.getElementById('closeFieldSidebarBtn');
    const positionAutoSaveStatus = document.getElementById('positionAutoSaveStatus');
    const positionAutoSaveStatusPanel = document.getElementById('positionAutoSaveStatusPanel');
    const fieldLabelInput = document.getElementById('fieldLabelInput');
    const fieldXInput = document.getElementById('fieldXInput');
    const fieldYInput = document.getElementById('fieldYInput');
    const fieldWidthInput = document.getElementById('fieldWidthInput');
    const fieldHeightInput = document.getElementById('fieldHeightInput');
    const fieldTypeSelect = document.getElementById('fieldTypeSelect');
    const fieldValueInput = document.getElementById('fieldValueInput');
    const fieldValueCheckbox = document.getElementById('fieldValueCheckbox');
    const fieldValueTextRow = document.getElementById('fieldValueTextRow');
    const fieldValueCheckboxRow = document.getElementById('fieldValueCheckboxRow');
    const fieldFontFamilySelect = document.getElementById('fieldFontFamilySelect');
    const fieldFontSizeInput = document.getElementById('fieldFontSizeInput');
    const fieldFontColorInput = document.getElementById('fieldFontColorInput');
    const fieldBoldInput = document.getElementById('fieldBoldInput');
    const fieldItalicInput = document.getElementById('fieldItalicInput');
    const fieldUnderlineInput = document.getElementById('fieldUnderlineInput');
    const fieldStrikeInput = document.getElementById('fieldStrikeInput');
    const customFieldLocationSelect = document.getElementById('customFieldLocationSelect');
    const customFieldSelect = document.getElementById('customFieldSelect');
    const customFieldAutoMatchNote = document.getElementById('customFieldAutoMatchNote');
    const suggestAliasBtn = document.getElementById('suggestAliasBtn');
    const deleteSelectedFieldBtn = document.getElementById('deleteSelectedFieldBtn');
    const fieldValueLockedNote = document.getElementById('fieldValueLockedNote');
    const fieldEditorPanel = document.getElementById('fieldEditorPanel');
    const fieldMappingPanel = document.getElementById('fieldMappingPanel');
    let autoSaveTimer = null;
    let autoSaveInFlight = false;
    let autoSaveQueued = false;
    let autoSavePromise = null;
    let lastAutoSaveError = '';
    const cfcAutoSaveTimers = Object.create(null);
    const cfcAutoSaveInFlight = Object.create(null);
    const cfcAutoSaveQueued = Object.create(null);
    const cfcSavedSignatures = Object.create(null);
    const SIDEBAR_WIDTH_KEY = 'formManagerSidebarWidthPx';
    const SIDEBAR_MIN_WIDTH = 160;
    const SIDEBAR_MAX_WIDTH = 520;

    function clampSidebarWidth(value) {
        const n = Number(value);
        if (!Number.isFinite(n)) return 200;
        return Math.max(SIDEBAR_MIN_WIDTH, Math.min(SIDEBAR_MAX_WIDTH, n));
    }

    function applySidebarWidth(widthPx) {
        if (!formManagerLayout) return;
        const width = clampSidebarWidth(widthPx);
        formManagerLayout.style.setProperty('--fm-sidebar-width', `${width}px`);
    }

    function loadPersistedSidebarWidth() {
        let width = 200;
        try {
            const saved = window.localStorage.getItem(SIDEBAR_WIDTH_KEY);
            if (saved !== null) width = clampSidebarWidth(saved);
        } catch (e) {
            // Ignore storage access issues.
        }
        applySidebarWidth(width);
    }

    function persistSidebarWidth(widthPx) {
        try {
            window.localStorage.setItem(SIDEBAR_WIDTH_KEY, String(clampSidebarWidth(widthPx)));
        } catch (e) {
            // Ignore storage access issues.
        }
    }

    function setFieldSidebarVisible(_visible) {
        // Sidebar stays visible in form-management step 2 by design.
        if (!formManagerLayout || !formManagerSidebar) return;
        formManagerLayout.classList.remove('properties-hidden');
        formManagerSidebar.classList.remove('is-hidden');
    }

    function setFieldEditingEnabled(enabled) {
        const isEnabled = !!enabled;
        const controls = [
            customFieldLocationSelect,
            customFieldSelect,
            suggestAliasBtn,
            deleteSelectedFieldBtn,
            fieldXInput,
            fieldYInput,
            fieldWidthInput,
            fieldHeightInput,
            fieldTypeSelect,
            fieldValueInput,
            fieldValueCheckbox,
            fieldFontFamilySelect,
            fieldFontSizeInput,
            fieldFontColorInput,
            fieldBoldInput,
            fieldItalicInput,
            fieldUnderlineInput,
            fieldStrikeInput
        ];
        controls.forEach((el) => {
            if (!el) return;
            el.disabled = !isEnabled;
        });
        [fieldEditorPanel, fieldMappingPanel].forEach((panel) => {
            if (!panel) return;
            panel.classList.toggle('is-disabled', !isEnabled);
        });
    }

    function setAutoSaveStatus(message, isError = false) {
        const color = isError ? '#b91c1c' : '#64748b';
        if (positionAutoSaveStatus) {
            positionAutoSaveStatus.textContent = message;
            positionAutoSaveStatus.style.color = color;
        }
        if (positionAutoSaveStatusPanel) {
            positionAutoSaveStatusPanel.textContent = message;
            positionAutoSaveStatusPanel.style.color = color;
        }
    }

    function escapeAttr(s) {
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/'/g, '&#39;');
    }

    function applyCustomFieldCatalogFromServer(catalog) {
        if (!Array.isArray(catalog)) {
            return;
        }
        const normalized = catalog.map((row) => ({
            linkId: String(row.linkId || ''),
            displayName: String(row.displayName || ''),
            fieldType: String(row.fieldType || 'text').toLowerCase(),
            matchingTag: String(row.matchingTag || row.linkId || ''),
            location: String(row.location || 'firm').toLowerCase(),
            value: String(row.value ?? ''),
        }));
        customFieldCatalog.length = 0;
        normalized.forEach((r) => customFieldCatalog.push(r));
        initialCustomFieldCatalog.length = 0;
        normalized.forEach((r) => initialCustomFieldCatalog.push({ ...r }));
        invalidateAutoDetectedLinkCache();
    }

    function renderFormCustomFieldsEditorTable() {
        const tbody = document.getElementById('formCustomFieldsEditorBody');
        if (!tbody) {
            return;
        }
        tbody.innerHTML = customFieldCatalog.map((row) => {
            const lid = String(row.linkId || '');
            const loc = String(row.location || 'firm').toLowerCase();
            const dn = escapeAttr(String(row.displayName || ''));
            const val = escapeAttr(String(row.value || ''));
            const lidAttr = escapeAttr(lid);
            return `<tr data-link-id="${lidAttr}">
                <td><code>${escapeHtml(lid)}</code></td>
                <td><input type="text" class="cfc-input" data-cfc="display_name" value="${dn}" aria-label="Display name"></td>
                <td><input type="text" class="cfc-input" data-cfc="value" value="${val}" aria-label="Value for PDF"></td>
                <td>
                    <select class="cfc-select" data-cfc="location" aria-label="Location">
                        <option value="firm"${loc === 'firm' ? ' selected' : ''}>Firm</option>
                        <option value="client"${loc === 'client' ? ' selected' : ''}>Client</option>
                        <option value="court"${loc === 'court' ? ' selected' : ''}>Court</option>
                        <option value="case"${loc === 'case' ? ' selected' : ''}>Case</option>
                    </select>
                </td>
                <td class="cfc-row-status" data-cfc-status>Saved</td>
            </tr>`;
        }).join('');
    }

    function rebuildCustomFieldSavedSignatures() {
        Object.keys(cfcSavedSignatures).forEach((key) => {
            delete cfcSavedSignatures[key];
        });
        customFieldCatalog.forEach((row) => {
            const linkId = String(row.linkId || '').trim();
            if (!linkId) return;
            cfcSavedSignatures[linkId] = JSON.stringify({
                linkId: linkId,
                displayName: String(row.displayName || '').trim(),
                location: String(row.location || 'firm').toLowerCase(),
                value: String(row.value ?? ''),
            });
        });
    }

    function getCustomFieldPayloadFromRow(tr) {
        if (!tr) return { payload: null, error: 'Missing row.' };
        const linkId = String(tr.dataset?.linkId || '').trim();
        const displayInp = tr.querySelector('[data-cfc="display_name"]');
        const valueInp = tr.querySelector('[data-cfc="value"]');
        const locSel = tr.querySelector('[data-cfc="location"]');
        const payload = {
            linkId: linkId,
            displayName: String(displayInp?.value || '').trim(),
            value: String(valueInp?.value || ''),
            location: String(locSel?.value || 'firm').toLowerCase(),
        };
        if (!payload.linkId) {
            return { payload, error: 'Missing link ID.' };
        }
        if (!payload.displayName) {
            return { payload, error: 'Display name is required.' };
        }
        return { payload, error: '' };
    }

    function customFieldPayloadSignature(payload) {
        return JSON.stringify({
            linkId: String(payload.linkId || '').trim(),
            displayName: String(payload.displayName || '').trim(),
            location: String(payload.location || 'firm').toLowerCase(),
            value: String(payload.value ?? ''),
        });
    }

    function findCustomFieldRowByLinkId(linkId) {
        const rows = document.querySelectorAll('#formCustomFieldsEditorBody tr[data-link-id]');
        for (const row of rows) {
            if (String(row.dataset?.linkId || '') === String(linkId || '')) {
                return row;
            }
        }
        return null;
    }

    function setCustomFieldRowStatus(tr, message, isError = false) {
        if (!tr) return;
        const statusEl = tr.querySelector('[data-cfc-status]');
        if (!statusEl) return;
        statusEl.textContent = message || '';
        statusEl.style.color = isError ? '#b13d3d' : '#166534';
    }

    function scheduleCustomFieldAutoSave(tr, delayMs = 700) {
        if (!tr) return;
        const linkId = String(tr.dataset?.linkId || '').trim();
        if (!linkId) return;
        if (cfcAutoSaveTimers[linkId]) {
            window.clearTimeout(cfcAutoSaveTimers[linkId]);
        }
        cfcAutoSaveTimers[linkId] = window.setTimeout(() => {
            delete cfcAutoSaveTimers[linkId];
            void autoSaveCustomFieldRow(tr);
        }, Math.max(120, delayMs));
        setCustomFieldRowStatus(tr, 'Unsaved', false);
    }

    async function autoSaveCustomFieldRow(tr) {
        if (!tr) return;
        const { payload, error } = getCustomFieldPayloadFromRow(tr);
        if (!payload) return;
        if (error) {
            setCustomFieldRowStatus(tr, 'Needs display name', true);
            setFormCustomFieldsStatus(error, true);
            return;
        }
        const signature = customFieldPayloadSignature(payload);
        if (cfcSavedSignatures[payload.linkId] === signature) {
            setCustomFieldRowStatus(tr, 'Saved', false);
            return;
        }
        if (cfcAutoSaveInFlight[payload.linkId]) {
            cfcAutoSaveQueued[payload.linkId] = true;
            setCustomFieldRowStatus(tr, 'Queued…', false);
            return;
        }
        cfcAutoSaveInFlight[payload.linkId] = true;
        setCustomFieldRowStatus(tr, 'Saving…', false);
        try {
            await persistCustomFieldRow(payload.linkId, payload.displayName, payload.location, payload.value);
            cfcSavedSignatures[payload.linkId] = signature;
            const latestRow = findCustomFieldRowByLinkId(payload.linkId);
            if (latestRow) {
                setCustomFieldRowStatus(latestRow, 'Saved', false);
            }
            setFormCustomFieldsStatus('Autosaved row: ' + payload.linkId + '.', false);
        } catch (e) {
            const latestRow = findCustomFieldRowByLinkId(payload.linkId) || tr;
            setCustomFieldRowStatus(latestRow, 'Save failed', true);
            setFormCustomFieldsStatus(e.message || String(e), true);
        } finally {
            cfcAutoSaveInFlight[payload.linkId] = false;
            if (cfcAutoSaveQueued[payload.linkId]) {
                delete cfcAutoSaveQueued[payload.linkId];
                const latestRow = findCustomFieldRowByLinkId(payload.linkId);
                if (latestRow) {
                    scheduleCustomFieldAutoSave(latestRow, 220);
                }
            }
        }
    }

    function setFormCustomFieldsStatus(msg, isError) {
        const el = document.getElementById('formCustomFieldsSaveStatus');
        if (!el) {
            return;
        }
        el.textContent = msg || '';
        el.style.color = isError ? '#b13d3d' : '#166534';
    }

    async function persistCustomFieldRow(linkId, displayName, location, value) {
        const res = await fetch('?route=api/form-management/upsert-custom-field', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                link_id: linkId,
                display_name: displayName,
                location,
                value: value ?? '',
            }),
        });
        const j = await res.json();
        if (!res.ok || !j.success) {
            throw new Error(j.error || j.message || 'Save failed');
        }
        if (Array.isArray(j.catalog)) {
            applyCustomFieldCatalogFromServer(j.catalog);
            renderFormCustomFieldsEditorTable();
            rebuildCustomFieldSavedSignatures();
        }
        const locFilter = customFieldLocationSelect?.value || 'firm';
        const selKey = wizardState.selectedFieldKey;
        const selLink = selKey ? (wizardState.customFieldAssignments[selKey]?.linkId || getEffectiveCustomFieldLinkId(selKey, wizardState.positionsMap[selKey] || {})) : '';
        populateCustomFieldOptions(locFilter, selLink || '');
        refreshAllPreviewFieldValues();
        if (wizardState.selectedFieldKey) {
            syncSelectedFieldInputs();
        }
    }

    function syncFormManagerHistoryStep(step, mode = 'push') {
        if (!isFormManagement || !window.history || !window.history.pushState || !window.history.replaceState) {
            return;
        }
        const nextStep = step === 2 ? 2 : 1;
        const stepUrl = new URL(window.location.href);
        stepUrl.searchParams.set('fm_step', String(nextStep));
        const prevState = (window.history.state && typeof window.history.state === 'object') ? window.history.state : {};
        const nextState = Object.assign({}, prevState, { formManagerStep: nextStep });
        if (mode === 'replace') {
            window.history.replaceState(nextState, '', stepUrl.toString());
        } else {
            window.history.pushState(nextState, '', stepUrl.toString());
        }
    }

    function showWizardStep(n) {
        let step = parseInt(n, 10) || 1;
        if (isFormManagement && step > 2) {
            step = 2;
        }
        if (isFormManagement && !suppressFormManagerHistory) {
            if (formManagerHistoryStep === null) {
                syncFormManagerHistoryStep(step, 'replace');
            } else if (formManagerHistoryStep !== step) {
                syncFormManagerHistoryStep(step, 'push');
            }
        }
        formManagerHistoryStep = step;
        wizardState.step = step;
        document.querySelectorAll('[data-wizard-pane]').forEach(p => {
            const pn = parseInt(p.getAttribute('data-wizard-pane'), 10);
            p.classList.toggle('is-active', pn === step);
        });
        document.querySelectorAll('#wizardStepLabels [data-wizard-label]').forEach(li => {
            const sn = parseInt(li.getAttribute('data-wizard-label'), 10);
            li.classList.remove('active', 'done');
            if (sn < step) li.classList.add('done');
            if (sn === step) li.classList.add('active');
        });
        const insertToolbar = document.getElementById('formInsertFieldToolbar');
        if (insertToolbar) {
            insertToolbar.style.display = (isFormManagement && step === 2) ? '' : 'none';
        }
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function clearUploadInlineError() {
        const uploadErr = document.getElementById('uploadInlineError');
        if (uploadErr) {
            uploadErr.style.display = 'none';
            uploadErr.textContent = '';
        }
    }

    function goToFormSearchStep() {
        showWizardStep(1);
        if (isFormManagement && !isFormNew) {
            setUploadFormVisible(false);
            clearUploadInlineError();
        }
    }

    const FIELD_METRICS = <?php echo json_encode($fieldMetricsJs, JSON_UNESCAPED_SLASHES); ?>;
    const UI_DEFAULT_FONT_PX = Number(FIELD_METRICS.DEFAULT_FONT_PX || 13);
    const IMPORTER_DEFAULT_FONT_FAMILY = 'Times';
    const IMPORTER_TYPOGRAPHY_PREF_KEY = 'formImporterGlobalTypography';
    const UI_MIN_FONT_PX = Math.max(1, Number(FIELD_METRICS.MIN_FONT_PX || 8));
    const UI_MAX_FONT_PX = Math.max(UI_MIN_FONT_PX, Number(FIELD_METRICS.MAX_FONT_PX || 32));
    const LEGACY_FONT_MIGRATION_MAX_PX = 13;
    const LEGACY_FONT_MIGRATION_MODERN_MIN_PX = 17;

    function clampFieldMetric(value, min, max) {
        return Math.max(min, Math.min(max, value));
    }

    function normalizeSidebarFontSizePx(pxValue) {
        const rounded = Math.round(getNumericInputValue(pxValue || UI_DEFAULT_FONT_PX));
        const options = fieldFontSizeInput ? Array.from(fieldFontSizeInput.options || []) : [];
        if (!options.length) {
            return rounded;
        }
        const numeric = options
            .map((opt) => parseInt(String(opt.value || ''), 10))
            .filter((n) => Number.isFinite(n));
        if (!numeric.length) {
            return rounded;
        }
        const min = Math.min(...numeric);
        const max = Math.max(...numeric);
        return clampFieldMetric(rounded, min, max);
    }

    function normalizeGlobalFontSizePx(pxValue) {
        const rounded = Math.round(getNumericInputValue(pxValue || UI_DEFAULT_FONT_PX));
        return Math.round(clampFieldMetric(rounded, Math.ceil(UI_MIN_FONT_PX), Math.floor(UI_MAX_FONT_PX)));
    }

    function normalizeGlobalFontFamilyValue(rawFamily, selectEl) {
        if (!selectEl) {
            return String(rawFamily || IMPORTER_DEFAULT_FONT_FAMILY);
        }
        const options = Array.from(selectEl.options || []).map((o) => String(o.value || '')).filter(Boolean);
        if (!options.length) {
            return String(rawFamily || IMPORTER_DEFAULT_FONT_FAMILY);
        }
        const wanted = String(rawFamily || '').trim();
        if (wanted) {
            const exact = options.find((value) => value === wanted);
            if (exact) return exact;
            const ci = options.find((value) => value.toLowerCase() === wanted.toLowerCase());
            if (ci) return ci;
        }
        const defaultOpt = options.find((value) => value.toLowerCase() === IMPORTER_DEFAULT_FONT_FAMILY.toLowerCase());
        return defaultOpt || options[0];
    }

    function readPersistedGlobalTypographyPreference(globalFontSelect) {
        const fallback = {
            fontFamily: normalizeGlobalFontFamilyValue(IMPORTER_DEFAULT_FONT_FAMILY, globalFontSelect),
            fontSizePx: normalizeGlobalFontSizePx(UI_DEFAULT_FONT_PX),
        };
        try {
            const raw = String(window.localStorage.getItem(IMPORTER_TYPOGRAPHY_PREF_KEY) || '').trim();
            if (!raw) {
                return fallback;
            }
            const parsed = JSON.parse(raw);
            return {
                fontFamily: normalizeGlobalFontFamilyValue(parsed?.fontFamily, globalFontSelect),
                fontSizePx: normalizeGlobalFontSizePx(parsed?.fontSizePx),
            };
        } catch (_) {
            return fallback;
        }
    }

    function persistGlobalTypographyPreference(globalFontSelect, globalFontSizeInput) {
        if (!globalFontSelect || !globalFontSizeInput) return;
        const payload = {
            fontFamily: normalizeGlobalFontFamilyValue(globalFontSelect.value, globalFontSelect),
            fontSizePx: normalizeGlobalFontSizePx(globalFontSizeInput.value),
        };
        try {
            window.localStorage.setItem(IMPORTER_TYPOGRAPHY_PREF_KEY, JSON.stringify(payload));
        } catch (_) {
            // Ignore storage issues.
        }
    }

    function applyGlobalTypographyControls(globalFontSelect, globalFontSizeInput, family, sizePx) {
        if (!globalFontSelect || !globalFontSizeInput) return;
        globalFontSelect.value = normalizeGlobalFontFamilyValue(family, globalFontSelect);
        globalFontSizeInput.value = String(normalizeGlobalFontSizePx(sizePx));
    }

    function resolveTemplateIdForFinishRedirect() {
        const direct = String(wizardState.templateId || '').trim();
        if (direct) return direct;
        const sourceFile = String(wizardState.sourceFileName || '').trim().toLowerCase();
        if (sourceFile) {
            const bySource = (Array.isArray(managedFormTemplates) ? managedFormTemplates : []).find((row) => {
                return String(row?.sourceFileName || '').trim().toLowerCase() === sourceFile
                    && String(row?.templateId || '').trim() !== '';
            });
            if (bySource) {
                return String(bySource.templateId || '').trim();
            }
        }
        const formNumber = String(wizardState.formNumber || '').trim().toLowerCase();
        const formName = String(wizardState.formName || '').trim().toLowerCase();
        const byIdentity = (Array.isArray(managedFormTemplates) ? managedFormTemplates : []).find((row) => {
            const tid = String(row?.templateId || '').trim();
            if (!tid) return false;
            const rowName = String(row?.formName || '').trim().toLowerCase();
            const numberMatch = formNumber !== '' && (tid.toLowerCase().includes(formNumber.replace('-', '_')) || tid.toLowerCase().includes(formNumber.replace('-', '')));
            const nameMatch = formName !== '' && rowName.includes(formName);
            return numberMatch || nameMatch;
        });
        return byIdentity ? String(byIdentity.templateId || '').trim() : '';
    }

    function normalizeFinishRedirectUrl(rawDest) {
        let dest = String(rawDest || '').trim();
        if (!dest) return '';
        if (!dest.includes('?route=') && !dest.startsWith('?') && !/^https?:\/\//i.test(dest)) {
            dest = '?' + dest.replace(/^\/+/, '');
        }
        if (dest.startsWith('%')) {
            try {
                const decoded = decodeURIComponent(dest);
                if (decoded) {
                    dest = decoded;
                }
            } catch (_) {
                // Keep raw value if decode fails.
            }
        }
        try {
            // Resolve against the current app path (e.g. /mvp/), not origin alone — otherwise
            // ?route=... becomes https://host/?route=... and hits the router default (dashboard).
            const url = /^https?:\/\//i.test(dest)
                ? new URL(dest)
                : new URL(dest, window.location.origin + window.location.pathname);
            const route = String(url.searchParams.get('route') || '').trim();
            if (!route) {
                return '';
            }
            return url.toString();
        } catch (_) {
            return '';
        }
    }

    function normalizeImportedFontSize(meta, fallbackSize = FIELD_METRICS.DEFAULT_FONT_PX || 13) {
        const fallback = clampFieldMetric(
            getNumericInputValue(fallbackSize || FIELD_METRICS.DEFAULT_FONT_PX || 13),
            FIELD_METRICS.MIN_FONT_PX || 8,
            FIELD_METRICS.MAX_FONT_PX || 32
        );
        const rawSize = getNumericInputValue(meta?.fontSize);
        if (!Number.isFinite(rawSize) || rawSize <= 0) {
            return fallback;
        }
        // Preserve explicit user-chosen sizing exactly (within absolute metric limits).
        if (String(meta?.fontSizeSource || '').toLowerCase() === 'user') {
            return clampFieldMetric(
                rawSize,
                FIELD_METRICS.MIN_FONT_PX || 8,
                FIELD_METRICS.MAX_FONT_PX || 32
            );
        }
        const hasExtractionSignature = !!(
            meta
            && typeof meta === 'object'
            && (
                Object.prototype.hasOwnProperty.call(meta, 'methodSource')
                || Object.prototype.hasOwnProperty.call(meta, 'method')
                || Object.prototype.hasOwnProperty.call(meta, 'confidence')
                || Object.prototype.hasOwnProperty.call(meta, 'sources')
            )
        );
        if (!hasExtractionSignature) {
            return clampFieldMetric(
                rawSize,
                FIELD_METRICS.MIN_FONT_PX || 8,
                FIELD_METRICS.MAX_FONT_PX || 32
            );
        }
        const fieldType = String(meta?.type || meta?.fieldType || '').toLowerCase();
        // Extractors can infer oversized fonts from large boxes. Keep imported defaults
        // anchored to the app standard until a user explicitly changes the size.
        if (fieldType !== 'checkbox' && fieldType !== 'radio') {
            return fallback;
        }
        const minPx = 7;
        const maxPx = 16;
        return clampFieldMetric(rawSize, minPx, maxPx);
    }

    function migrateFieldFontSizeToPx(meta) {
        if (!meta || typeof meta !== 'object') return;
        const unit = String(meta.fontSizeUnit || '').toLowerCase();
        if (unit === 'px') {
            return;
        }
        const raw = getNumericInputValue(meta.fontSize);
        if (!Number.isFinite(raw) || raw <= 0) {
            meta.fontSize = FIELD_METRICS.DEFAULT_FONT_PX || 13;
            meta.fontSizeUnit = 'px';
            return;
        }
        // Legacy positions stored pt without fontSizeUnit (typical range 5–24).
        if (raw <= 24) {
            meta.fontSize = Math.round(raw * 96 / 72);
        }
        meta.fontSizeUnit = 'px';
    }

    function coerceFieldGeometry(f) {
        const o = Object.assign({}, f && typeof f === 'object' ? f : {});
        if (o.x === undefined || o.x === null) {
            if (o.left != null) o.x = o.left;
            else if (o.posX != null) o.x = o.posX;
        }
        if (o.y === undefined || o.y === null) {
            if (o.top != null) o.y = o.top;
            else if (o.posY != null) o.y = o.posY;
        }
        if (o.width === undefined || o.width === null) {
            if (o.w != null) o.width = o.w;
        }
        if (o.height === undefined || o.height === null) {
            if (o.h != null) o.height = o.h;
        }
        ['x', 'y', 'width', 'height'].forEach((prop) => {
            if (o[prop] !== undefined && o[prop] !== null && o[prop] !== '') {
                const n = Number(o[prop]);
                if (Number.isFinite(n)) {
                    o[prop] = n;
                }
            }
        });
        if (o.fontSize !== undefined && o.fontSize !== null && o.fontSize !== '') {
            migrateFieldFontSizeToPx(o);
            o.fontSize = normalizeImportedFontSize(o, o.fontSize);
        }
        return o;
    }

    function getFontMigrationDecisionKey(templateId) {
        return `wpts:font-migration:${String(templateId || '').trim() || 'unknown-template'}`;
    }

    function shouldPromptLegacyFontMigration(positionsMap) {
        const values = [];
        Object.keys(positionsMap || {}).forEach((key) => {
            const meta = positionsMap[key];
            if (!meta || typeof meta !== 'object') return;
            if (String(meta.fontSizeSource || '').toLowerCase() === 'user') {
                return;
            }
            migrateFieldFontSizeToPx(meta);
            const raw = getNumericInputValue(meta.fontSize);
            if (Number.isFinite(raw) && raw > 0) {
                values.push(raw);
            }
        });
        if (!values.length) return false;
        const legacyCount = values.filter((v) => v <= LEGACY_FONT_MIGRATION_MAX_PX).length;
        const modernCount = values.filter((v) => v >= LEGACY_FONT_MIGRATION_MODERN_MIN_PX).length;
        return legacyCount > 0 && modernCount === 0 && (legacyCount / values.length) >= 0.6;
    }

    function applyLegacyFontMigration(positionsMap, targetPx) {
        let touched = 0;
        const target = normalizeGlobalFontSizePx(targetPx || FIELD_METRICS.DEFAULT_FONT_PX || 13);
        Object.keys(positionsMap || {}).forEach((key) => {
            const meta = positionsMap[key];
            if (!meta || typeof meta !== 'object') return;
            if (String(meta.fontSizeSource || '').toLowerCase() === 'user') {
                return;
            }
            migrateFieldFontSizeToPx(meta);
            const raw = getNumericInputValue(meta.fontSize);
            if (!Number.isFinite(raw) || raw <= LEGACY_FONT_MIGRATION_MAX_PX) {
                meta.fontSize = target;
                meta.fontSizeUnit = 'px';
                meta.fontSizeSource = 'user';
                touched += 1;
            }
        });
        return touched;
    }

    function canonicalizeFieldKey(raw, fallback = '') {
        const text = String(raw || '').trim();
        if (!text) return String(fallback || '').trim();
        const canonical = text
            .replace(/\[(\d+)\]/g, '_$1')
            .replace(/[^A-Za-z0-9]+/g, '_')
            .replace(/_+/g, '_')
            .replace(/^_+|_+$/g, '')
            .trim();
        return canonical || String(fallback || '').trim();
    }

    /**
     * Must match server + preview DOM data-field-key. phpObjectKey is the JSON object property name when fields are a map.
     */
    function stableExtractFieldKey(f, index, phpObjectKey) {
        const ff = f && typeof f === 'object' ? f : {};
        if (phpObjectKey !== undefined && phpObjectKey !== null && String(phpObjectKey).trim() !== '') {
            const fromObjectKey = canonicalizeFieldKey(phpObjectKey);
            if (fromObjectKey) return fromObjectKey;
        }
        const named = String(ff.canonicalName || ff.name || ff.fieldName || '').trim();
        const canonical = canonicalizeFieldKey(named);
        if (canonical) return canonical;
        const safeIdx = (typeof index === 'number' && !Number.isNaN(index)) ? index : 0;
        return `field_${safeIdx}`;
    }

    function normalizeFieldsToKeyedMap(fields) {
        const out = {};
        if (!fields) return out;
        if (Array.isArray(fields)) {
            fields.forEach((f, i) => {
                const k = stableExtractFieldKey(f, i, undefined);
                const base = coerceFieldGeometry(f || {});
                const ff = f && typeof f === 'object' ? f : {};
                const rawName = String(ff.name || ff.fieldName || ff.canonicalName || k).trim() || k;
                out[k] = Object.assign({}, base, {
                    name: rawName,
                    canonicalName: canonicalizeFieldKey(ff.canonicalName || rawName, k) || k
                });
            });
            return out;
        }
        Object.entries(fields).forEach(([name, f], i) => {
            const ff = f && typeof f === 'object' ? f : {};
            const k = stableExtractFieldKey(ff, i, name);
            const base = coerceFieldGeometry(ff);
            const rawName = String(ff.name || ff.fieldName || name || k).trim() || k;
            out[k] = Object.assign({}, base, {
                name: rawName,
                canonicalName: canonicalizeFieldKey(ff.canonicalName || rawName || k, k) || k
            });
        });
        return out;
    }

    function hydrateFieldDefaultsFromPositionsMap() {
        wizardState.fieldDefaults = {};
        Object.entries(wizardState.positionsMap).forEach(([k, meta]) => {
            if (!meta || typeof meta !== 'object') return;
            if (!Object.prototype.hasOwnProperty.call(meta, 'defaultValue')) return;
            const v = meta.defaultValue;
            if (v == null || String(v).trim() === '') return;
            wizardState.fieldDefaults[k] = { value: String(v) };
        });
    }

    function fieldKeyLooksLikeAttorneyFirm(key) {
        const s = String(key || '').toLowerCase();
        return /attyfirm|firmname|firm_name|attorney_firm|attorney.?firm/.test(s);
    }

    function applyDetectedFirmToWizardState(firmText) {
        const firm = String(firmText || '').trim();
        if (!firm) return;
        wizardState.detectedFirmName = firm;
        Object.keys(wizardState.positionsMap).forEach((k) => {
            if (!fieldKeyLooksLikeAttorneyFirm(k)) return;
            const meta = wizardState.positionsMap[k];
            if (!meta || typeof meta !== 'object') return;
            meta.defaultValue = firm;
            wizardState.fieldDefaults[k] = { value: firm };
        });
        refreshAllPreviewFieldValues();
    }

    function getNumericInputValue(value) {
        const n = Number(value);
        return Number.isFinite(n) ? n : 0;
    }

    function toHexColor(value) {
        if (Array.isArray(value) && value.length >= 3) {
            const r = Math.max(0, Math.min(255, Number(value[0]) || 0));
            const g = Math.max(0, Math.min(255, Number(value[1]) || 0));
            const b = Math.max(0, Math.min(255, Number(value[2]) || 0));
            return `#${[r, g, b].map((n) => n.toString(16).padStart(2, '0')).join('')}`;
        }
        if (typeof value === 'string') {
            const hex = value.trim();
            if (/^#[0-9a-f]{6}$/i.test(hex)) return hex;
            if (/^#[0-9a-f]{3}$/i.test(hex)) {
                return '#' + hex.slice(1).split('').map((c) => c + c).join('');
            }
            if (/^\d+\s*,\s*\d+\s*,\s*\d+$/.test(hex)) {
                const parts = hex.split(',').map((p) => Math.max(0, Math.min(255, Number(p.trim()) || 0)));
                return `#${parts.map((n) => n.toString(16).padStart(2, '0')).join('')}`;
            }
        }
        return '#000000';
    }

    function hexToRgbArray(hexValue) {
        const hex = String(hexValue || '#000000').trim();
        const normalized = /^#[0-9a-f]{6}$/i.test(hex)
            ? hex
            : /^#[0-9a-f]{3}$/i.test(hex)
                ? '#' + hex.slice(1).split('').map((c) => c + c).join('')
                : '#000000';
        return [
            parseInt(normalized.slice(1, 3), 16),
            parseInt(normalized.slice(3, 5), 16),
            parseInt(normalized.slice(5, 7), 16)
        ];
    }

    function deriveFieldLabel(key, meta) {
        const candidates = [
            key,
            meta?.canonicalName,
            meta?.name,
            meta?.fieldName,
            meta?.label,
            meta?.displayName
        ];
        for (const candidate of candidates) {
            if (candidate === null || candidate === undefined) continue;
            const canonical = canonicalizeFieldKey(candidate);
            if (canonical) {
                return canonical;
            }
            const raw = String(candidate).trim();
            if (raw !== '') {
                return raw;
            }
        }
        return canonicalizeFieldKey(key, 'field') || 'field';
    }

    function escapeRegex(raw) {
        return String(raw).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    function cleanLabelToken(rawLabel, token) {
        const text = String(token || '').trim();
        if (!text) return String(rawLabel || '');
        const pattern = new RegExp(escapeRegex(text), 'ig');
        return String(rawLabel || '')
            .replace(pattern, ' ')
            .replace(/\s+/g, ' ')
            .replace(/^[\s\-_:]+|[\s\-_:]+$/g, '')
            .trim();
    }

    function getCustomFieldsByLocation(location) {
        const norm = String(location || 'firm').toLowerCase();
        return customFieldCatalog.filter((entry) => String(entry.location || '').toLowerCase() === norm);
    }

    function populateCustomFieldOptions(location, selectedLinkId = '') {
        if (!customFieldSelect) return;
        const list = getCustomFieldsByLocation(location);
        const opts = ['<option value="">-- none --</option>'];
        list.forEach((entry) => {
            const suffix = String(entry.matchingTag || entry.linkId || '').trim();
            const label = `${entry.displayName || entry.linkId} (${suffix})`;
            const sel = selectedLinkId === entry.linkId ? ' selected' : '';
            opts.push(`<option value="${escapeAttr(entry.linkId)}"${sel}>${escapeHtml(label)}</option>`);
        });
        customFieldSelect.innerHTML = opts.join('');
    }

    function clearFieldSelectionVisual() {
        results.querySelectorAll('.tech-preview-field.is-selected').forEach((el) => el.classList.remove('is-selected'));
    }

    function renderSelectedFieldStatus(msg) {
        if (selectedFieldStatus) {
            selectedFieldStatus.textContent = msg;
        }
    }

    function resetPropertiesPanelPrompt() {
        if (selectedFieldStatus) {
            selectedFieldStatus.style.display = 'none';
        }
        const keyRow = document.getElementById('fieldInternalKeyRow');
        if (keyRow) keyRow.hidden = true;
        if (fieldValueLockedNote) fieldValueLockedNote.hidden = true;
        if (customFieldAutoMatchNote) customFieldAutoMatchNote.hidden = true;
        setFieldEditingEnabled(false);
    }

    function updateCustomFieldAutoMatchNote(key, assignment, effectiveLink) {
        if (!customFieldAutoMatchNote) return;
        const isManual = !!(assignment && assignment.isManual);
        const linkId = String(effectiveLink || '').trim();
        if (isManual || !linkId) {
            customFieldAutoMatchNote.hidden = true;
            return;
        }
        const entry = getCatalogEntryByLinkId(linkId);
        const tag = String(entry?.matchingTag || linkId).trim();
        const label = String(entry?.displayName || linkId).trim();
        customFieldAutoMatchNote.textContent = `Catalog link: matching tag “${tag}” → “${label}”.`;
        customFieldAutoMatchNote.hidden = false;
    }

    function getSelectedFieldMeta() {
        const key = wizardState.selectedFieldKey;
        if (!key) return null;
        return wizardState.positionsMap[key] || null;
    }

    function syncSelectedFieldInputs() {
        const key = wizardState.selectedFieldKey;
        const fieldEl = getFieldElementByKey(key);
        let meta = getSelectedFieldMeta();
        if (key && fieldEl && !meta) {
            wizardState.positionsMap[key] = coerceFieldGeometry({
                name: key,
                canonicalName: key,
                x: parseFloat(fieldEl.dataset.x),
                y: parseFloat(fieldEl.dataset.y),
                width: parseFloat(fieldEl.dataset.w),
                height: parseFloat(fieldEl.dataset.h),
                page: parseInt(fieldEl.dataset.page || '1', 10),
            });
            meta = wizardState.positionsMap[key];
        }
        if (key && meta && fieldEl) {
            const dx = parseFloat(fieldEl.dataset.x);
            const dy = parseFloat(fieldEl.dataset.y);
            const dw = parseFloat(fieldEl.dataset.w);
            const dh = parseFloat(fieldEl.dataset.h);
            if (Number.isFinite(dx)) meta.x = dx;
            if (Number.isFinite(dy)) meta.y = dy;
            if (Number.isFinite(dw)) meta.width = dw;
            if (Number.isFinite(dh)) meta.height = dh;
        }
        if (!key || !meta) {
            resetPropertiesPanelPrompt();
            return;
        }
        setFieldSidebarVisible(true);
        setFieldEditingEnabled(true);
        if (selectedFieldStatus) {
            selectedFieldStatus.style.display = 'none';
        }
        const keyRow = document.getElementById('fieldInternalKeyRow');
        const keyDisp = document.getElementById('fieldInternalKeyDisplay');
        if (keyRow && keyDisp) {
            keyDisp.textContent = key;
            keyRow.hidden = false;
        }
        if (fieldLabelInput) fieldLabelInput.value = deriveFieldLabel(key, meta);
        if (fieldXInput) fieldXInput.value = String(getNumericInputValue(meta.x));
        if (fieldYInput) fieldYInput.value = String(getNumericInputValue(meta.y));
        if (fieldWidthInput) fieldWidthInput.value = String(getNumericInputValue(meta.width));
        if (fieldHeightInput) fieldHeightInput.value = String(getNumericInputValue(meta.height));
        const selectedType = String(meta.type || meta.fieldType || 'text');
        if (fieldTypeSelect) fieldTypeSelect.value = selectedType;
        const locked = isCustomFieldLocked(key, meta);
        setFieldValueEditorMode(selectedType, getDisplayedFillValue(key, meta), locked);
        if (fieldValueLockedNote) fieldValueLockedNote.hidden = !locked;
        if (fieldFontFamilySelect) fieldFontFamilySelect.value = String(meta.fontFamily || 'Arial');
        if (fieldFontSizeInput) {
            fieldFontSizeInput.value = String(normalizeSidebarFontSizePx(getNumericInputValue(meta.fontSize || (FIELD_METRICS.DEFAULT_FONT_PX || 13))));
        }
        if (fieldFontColorInput) fieldFontColorInput.value = toHexColor(meta.fontColor);
        const style = String(meta.fontStyle || '').toUpperCase();
        if (fieldBoldInput) fieldBoldInput.checked = !!meta.isBold || style.includes('B');
        if (fieldItalicInput) fieldItalicInput.checked = !!meta.isItalic || style.includes('I');
        if (fieldUnderlineInput) fieldUnderlineInput.checked = !!meta.isUnderline || style.includes('U');
        if (fieldStrikeInput) fieldStrikeInput.checked = !!meta.isStrikethrough || style.includes('S');
        const assignment = wizardState.customFieldAssignments[key] || {
            location: String(meta.customFieldLocation || 'firm'),
            linkId: String(meta.customFieldLinkId || ''),
            isManual: false,
        };
        const effectiveLink = getEffectiveCustomFieldLinkId(key, meta);
        const linkedEntry = getCatalogEntryByLinkId(assignment.linkId || effectiveLink);
        let resolvedLocation = String(assignment.location || '').toLowerCase();
        if (!assignment.isManual && linkedEntry && linkedEntry.location) {
            resolvedLocation = String(linkedEntry.location || '').toLowerCase();
        }
        if (!resolvedLocation || !['firm', 'client', 'court', 'case'].includes(resolvedLocation)) {
            resolvedLocation = 'firm';
        }
        assignment.location = resolvedLocation;
        wizardState.customFieldAssignments[key] = assignment;
        if (customFieldLocationSelect) customFieldLocationSelect.value = resolvedLocation;
        populateCustomFieldOptions(resolvedLocation, assignment.linkId || effectiveLink);
        updateCustomFieldAutoMatchNote(key, assignment, assignment.linkId || effectiveLink);
    }

    function getFieldElementByKey(key) {
        if (!key) return null;
        let match = null;
        results.querySelectorAll('.tech-preview-field').forEach((el) => {
            if (!match && String(el.dataset.fieldKey || '') === String(key)) {
                match = el;
            }
        });
        return match;
    }

    function isCheckableFieldType(fieldType) {
        const t = String(fieldType || '').toLowerCase();
        return t === 'checkbox' || t === 'radio';
    }

    function setFieldValueEditorMode(fieldType, currentValue = '', readOnly = false) {
        const isCheckbox = isCheckableFieldType(fieldType);
        if (fieldValueTextRow) fieldValueTextRow.style.display = isCheckbox ? 'none' : '';
        if (fieldValueCheckboxRow) fieldValueCheckboxRow.style.display = isCheckbox ? '' : 'none';
        if (isCheckbox && fieldValueCheckbox) {
            const norm = String(currentValue || '').toLowerCase();
            fieldValueCheckbox.checked = norm === '1' || norm === 'true' || norm === 'yes' || norm === 'on';
            fieldValueCheckbox.disabled = !!readOnly;
        } else if (fieldValueInput) {
            fieldValueInput.value = String(currentValue ?? '');
            fieldValueInput.readOnly = !!readOnly;
            fieldValueInput.classList.toggle('is-custom-field-locked', !!readOnly);
            fieldValueInput.disabled = !!readOnly;
        }
    }

    function isTruthyCheckboxValue(value) {
        const norm = String(value ?? '').trim().toLowerCase();
        return norm === '1' || norm === 'true' || norm === 'yes' || norm === 'on';
    }

    /**
     * PDF export value for this field (same rules as collectWizardValues).
     * Custom-field catalog values override session edits and samples when present.
     */
    function resolveExportValueForField(key, meta, opts) {
        opts = opts || {};
        const includeSamples = opts.includeSamples !== false;
        if (!meta || typeof meta !== 'object') {
            return '';
        }
        const customVal = getCustomFieldOverrideValue(key, meta);
        if (customVal !== null) {
            const type = String(meta.type || meta.fieldType || '').toLowerCase();
            if (type === 'checkbox' || type === 'radio') {
                return isTruthyCheckboxValue(customVal) ? '1' : '';
            }
            return String(customVal);
        }
        if (Object.prototype.hasOwnProperty.call(wizardState.fieldDefaults, key)) {
            const fd = wizardState.fieldDefaults[key]?.value;
            if (fd !== undefined && fd !== null) {
                return String(fd);
            }
        }
        if (meta.defaultValue != null && String(meta.defaultValue) !== '') {
            return String(meta.defaultValue);
        }
        if (!includeSamples) {
            return '';
        }
        const suggested = suggestDefaultForFieldName(key, meta);
        const type = String(meta?.type || meta?.fieldType || '').toLowerCase();
        if (type === 'checkbox' || type === 'radio') {
            return String(suggested) === '1' ? '1' : '';
        }
        if (suggested !== undefined && suggested !== null && String(suggested) !== '') {
            return String(suggested);
        }
        return '';
    }

    /**
     * Preview + sidebar display: follows export rules including sample toggle.
     */
    function getDisplayedFillValue(key, meta) {
        if (!meta || typeof meta !== 'object') {
            const sampleOn = getExportIncludeSamples();
            return sampleOn ? String(suggestDefaultForFieldName(key, {}) || '') : '';
        }
        return resolveExportValueForField(key, meta, { includeSamples: getExportIncludeSamples() });
    }

    /** Why the fill text looks the way it does (sample vs saved template vs your edit). */
    function getFillValueSource(key, meta) {
        if (!meta || typeof meta !== 'object') {
            return 'suggested';
        }
        if (getCustomFieldOverrideValue(key, meta) !== null) {
            return 'custom';
        }
        const fieldType = String(meta?.type || meta?.fieldType || '').toLowerCase();
        const isCheckable = isCheckableFieldType(fieldType);
        const checkboxUserSet = !!meta?.checkboxUserSet;
        if (Object.prototype.hasOwnProperty.call(wizardState.fieldDefaults, key)) {
            const fd = wizardState.fieldDefaults[key]?.value;
            if (fd !== undefined && fd !== null) {
                if (isCheckable && !checkboxUserSet) {
                    const hasMetaDefault = meta.defaultValue != null && String(meta.defaultValue).trim() !== '';
                    if (hasMetaDefault && isTruthyCheckboxValue(fd) === isTruthyCheckboxValue(meta.defaultValue)) {
                        return 'saved';
                    }
                }
                const metaDefault = meta.defaultValue;
                if (metaDefault != null && String(metaDefault) !== '' && String(fd) === String(metaDefault)) {
                    return 'saved';
                }
                return 'session';
            }
        }
        if (meta.defaultValue != null && String(meta.defaultValue) !== '') {
            return 'saved';
        }
        if (!getExportIncludeSamples()) {
            return 'none';
        }
        const suggested = suggestDefaultForFieldName(key, meta);
        const type = String(meta?.type || meta?.fieldType || '').toLowerCase();
        if (type === 'checkbox' || type === 'radio') {
            return String(suggested) === '1' ? 'suggested' : 'none';
        }
        if (suggested !== undefined && suggested !== null && String(suggested) !== '') {
            return 'suggested';
        }
        return 'none';
    }

    function applyPreviewValueToFieldEl(fieldEl, key, meta) {
        if (!fieldEl || !meta) return;
        let span = fieldEl.querySelector('.tech-preview-field-value');
        if (!span) {
            span = document.createElement('span');
            span.className = 'tech-preview-field-value';
            span.setAttribute('aria-hidden', 'true');
            fieldEl.appendChild(span);
        }
        const fontFamily = normalizePreviewFontFamily(meta.fontFamily);
        const fontSize = normalizeImportedFontSize(meta, FIELD_METRICS.DEFAULT_FONT_PX || 13);
        const fontColor = Array.isArray(meta.fontColor) && meta.fontColor.length >= 3
            ? `rgb(${Math.max(0, Math.min(255, parseInt(meta.fontColor[0], 10) || 0))}, ${Math.max(0, Math.min(255, parseInt(meta.fontColor[1], 10) || 0))}, ${Math.max(0, Math.min(255, parseInt(meta.fontColor[2], 10) || 0))})`
            : '#0f172a';
        const style = String(meta.fontStyle || '').toUpperCase();
        const isBold = !!meta.isBold || style.includes('B');
        const isItalic = !!meta.isItalic || style.includes('I');
        const isUnderline = !!meta.isUnderline || style.includes('U');
        const isStrike = !!meta.isStrikethrough || style.includes('S');
        const fontSizePx = Math.max(FIELD_METRICS.MIN_PREVIEW_PX || 4, fontSize);
        const displayedHeightPx = Math.max(1, fieldEl.getBoundingClientRect().height || parseFloat(fieldEl.style.height || '0') || 0);
        const padPx = Math.max(1, Math.min(3, Math.round(displayedHeightPx * 0.08)));
        // Keep the preview text style in sync with field properties edits.
        span.style.fontFamily = `${fontFamily}, "Helvetica Neue", Helvetica, "Segoe UI", Arial, sans-serif`;
        span.style.fontSize = `${fontSizePx}px`;
        span.style.padding = `${padPx}px ${padPx + 1}px`;
        span.style.color = fontColor;
        span.style.textRendering = 'optimizeLegibility';
        span.style.WebkitFontSmoothing = 'antialiased';
        span.style.MozOsxFontSmoothing = 'grayscale';
        // Always set explicit text styling so fill-source classes cannot mask property toggles.
        span.style.fontWeight = isBold ? '700' : '400';
        span.style.fontStyle = isItalic ? 'italic' : 'normal';
        span.style.textDecoration = [isUnderline ? 'underline' : '', isStrike ? 'line-through' : ''].filter(Boolean).join(' ') || 'none';
        const t = String(meta.type || meta.fieldType || 'text').toLowerCase();
        const displayVal = getDisplayedFillValue(key, meta);
        const src = getFillValueSource(key, meta);
        fieldEl.classList.remove(
            'tech-preview-field--fill-suggested',
            'tech-preview-field--fill-saved',
            'tech-preview-field--fill-session',
            'tech-preview-field--fill-custom'
        );
        fieldEl.classList.toggle('is-custom-value-locked', isCustomFieldLocked(key, meta));
        fieldEl.classList.toggle('is-auto-mapped-custom', isAutoMatchedCustomField(key, meta));
        if (src === 'suggested') fieldEl.classList.add('tech-preview-field--fill-suggested');
        else if (src === 'saved') fieldEl.classList.add('tech-preview-field--fill-saved');
        else if (src === 'session') fieldEl.classList.add('tech-preview-field--fill-session');
        else if (src === 'custom') fieldEl.classList.add('tech-preview-field--fill-custom');

        if (isCheckableFieldType(t)) {
            span.textContent = '';
            span.style.display = 'none';
            span.removeAttribute('title');
            // Imported checkbox defaults are often effectively sample/seed values.
            // When users hide sample data, suppress both suggested and saved checks
            // unless the checkbox was explicitly changed in-session.
            const checkboxUserSet = !!meta?.checkboxUserSet;
            const hideSampleCheck = getPreviewHideSampleData()
                && !checkboxUserSet
                && (src === 'suggested' || src === 'saved' || src === 'session');
            const hideLinkedCheck = getPreviewHideLinkedData() && src === 'custom';
            const shouldRenderCheck = !(hideSampleCheck || hideLinkedCheck) && isTruthyCheckboxValue(displayVal);
            fieldEl.classList.toggle('is-checked', shouldRenderCheck);
            fieldEl.title = shouldRenderCheck
                ? `${key} — checked (export)`
                : `${key} — unchecked`;
        } else {
            span.style.display = '';
            fieldEl.classList.remove('is-checked');
            const hideSampleText = getPreviewHideSampleData() && src === 'suggested';
            const hideLinkedText = getPreviewHideLinkedData() && src === 'custom';
            const renderedValue = (hideSampleText || hideLinkedText) ? '' : displayVal;
            span.textContent = renderedValue;
            const full = renderedValue.trim() === '' ? `${key} — (empty)` : `${key}: ${renderedValue}`;
            span.title = full;
            fieldEl.title = full;
        }
    }

    function normalizePreviewFontFamily(rawFamily) {
        const family = String(rawFamily || '').trim();
        if (!family) return 'Arial';
        // Inter tends to look jagged at tiny overlay px sizes; prefer hinted system sans.
        if (/^inter$/i.test(family)) return 'Arial';
        return family;
    }

    function refreshPreviewValueForKey(key) {
        const meta = wizardState.positionsMap[key];
        const el = getFieldElementByKey(key);
        if (el && meta) applyPreviewValueToFieldEl(el, key, meta);
    }

    function refreshAllPreviewFieldValues() {
        if (!results) return;
        results.querySelectorAll('.tech-preview-field[data-field-key]').forEach((fieldEl) => {
            const key = String(fieldEl.dataset.fieldKey || '');
            if (!key) return;
            const meta = wizardState.positionsMap[key];
            if (!meta || typeof meta !== 'object') {
                fieldEl.classList.remove('is-checked');
                const sp = fieldEl.querySelector('.tech-preview-field-value');
                if (sp) sp.textContent = '';
                return;
            }
            applyPreviewValueToFieldEl(fieldEl, key, meta);
        });
    }

    function getConfidenceClassForPreview(confidence) {
        if (!Number.isFinite(confidence)) return 'confidence-unknown';
        if (confidence >= 0.8) return 'confidence-high';
        if (confidence >= 0.6) return 'confidence-medium';
        return 'confidence-low';
    }

    function layoutTechPreviewForImage(img) {
        if (!img || !results) return;
        const id = String(img.id || '');
        const m = id.match(/preview-bg-(\d+)/);
        if (!m) return;
        const pageNum = parseInt(m[1], 10);
        const overlay = document.getElementById(`preview-overlay-${pageNum}`);
        if (!overlay) return;
        const displayWidth = img.clientWidth || img.naturalWidth;
        const displayHeight = img.clientHeight || img.naturalHeight;
        overlay.style.width = displayWidth + 'px';
        overlay.style.height = displayHeight + 'px';
        const scaleX = displayWidth / 215.9;
        const scaleY = displayHeight / 279.4;
        overlay.querySelectorAll('.tech-preview-field').forEach((fieldEl) => {
            const x = parseFloat(fieldEl.dataset.x || '0') * scaleX;
            const y = parseFloat(fieldEl.dataset.y || '0') * scaleY;
            const w = Math.max(5, parseFloat(fieldEl.dataset.w || '0') * scaleX);
            const h = Math.max(5, parseFloat(fieldEl.dataset.h || '0') * scaleY);
            const confidence = parseFloat(fieldEl.dataset.confidence || 'NaN');
            const confidenceClass = getConfidenceClassForPreview(confidence);
            fieldEl.classList.remove('confidence-high', 'confidence-medium', 'confidence-low', 'confidence-unknown');
            fieldEl.classList.add(confidenceClass);
            fieldEl.style.left = `${x}px`;
            fieldEl.style.top = `${y}px`;
            fieldEl.style.width = `${w}px`;
            fieldEl.style.height = `${h}px`;
        });
        refreshAllPreviewFieldValues();
    }

    function relayoutAllTechPreviewPages() {
        if (!results) return;
        results.querySelectorAll('.tech-preview-bg').forEach((img) => {
            if (img.complete && (img.clientWidth || img.naturalWidth)) {
                layoutTechPreviewForImage(img);
            }
        });
    }

    function ensureTechPreviewResizeListener() {
        if (techPreviewResizeHooked) return;
        techPreviewResizeHooked = true;
        const onResize = () => {
            window.requestAnimationFrame(() => relayoutAllTechPreviewPages());
        };
        window.addEventListener('resize', onResize);
        if (window.visualViewport) {
            window.visualViewport.addEventListener('resize', onResize);
            window.visualViewport.addEventListener('scroll', onResize);
        }
    }

    function initWizardPaddingBaselines() {
        wizardState.fieldUnpaddedMm = Object.create(null);
        wizardState.pagePaddingDeltaMm = Object.create(null);
        Object.entries(wizardState.positionsMap).forEach(([k, meta]) => {
            if (!meta || typeof meta !== 'object') return;
            const p = parseInt(meta.page, 10) || 1;
            if (!wizardState.pagePaddingDeltaMm[p]) {
                wizardState.pagePaddingDeltaMm[p] = { dx: 0, dy: 0 };
            }
            wizardState.fieldUnpaddedMm[k] = {
                x: getNumericInputValue(meta.x),
                y: getNumericInputValue(meta.y),
            };
        });
    }

    function syncFieldUnpaddedFromPositions(key) {
        const meta = wizardState.positionsMap[key];
        if (!meta || typeof meta !== 'object') return;
        const pageNum = parseInt(meta.page, 10) || 1;
        const pad = wizardState.pagePaddingDeltaMm[pageNum] || { dx: 0, dy: 0 };
        if (!wizardState.fieldUnpaddedMm[key]) {
            wizardState.fieldUnpaddedMm[key] = { x: 0, y: 0 };
        }
        wizardState.fieldUnpaddedMm[key].x = getNumericInputValue(meta.x) - pad.dx;
        wizardState.fieldUnpaddedMm[key].y = getNumericInputValue(meta.y) - pad.dy;
    }

    let manualPlacementMode = null;

    function buildManualFieldDraft() {
        const selectedMeta = getSelectedFieldMeta();
        const gff = document.getElementById('globalFormFontFamilySelect');
        const gfs = document.getElementById('globalFormFontSizeInput');
        const defaultWidthMm = 45;
        const defaultHeightMm = 10;
        const widthMm = selectedMeta ? Math.max(2, getNumericInputValue(selectedMeta.width || defaultWidthMm)) : defaultWidthMm;
        const heightMm = selectedMeta ? Math.max(2, getNumericInputValue(selectedMeta.height || defaultHeightMm)) : defaultHeightMm;
        const fontFamily = selectedMeta?.fontFamily
            ? String(selectedMeta.fontFamily)
            : (gff ? normalizeGlobalFontFamilyValue(gff.value, gff) : IMPORTER_DEFAULT_FONT_FAMILY);
        const fontSize = selectedMeta?.fontSize
            ? normalizeGlobalFontSizePx(selectedMeta.fontSize)
            : normalizeGlobalFontSizePx(gfs ? gfs.value : UI_DEFAULT_FONT_PX);
        const type = String(selectedMeta?.type || selectedMeta?.fieldType || 'text').toLowerCase();
        return {
            widthMm,
            heightMm,
            type: type || 'text',
            fontFamily,
            fontSize,
            fontColor: Array.isArray(selectedMeta?.fontColor) ? selectedMeta.fontColor : hexToRgbArray('#000000'),
            isBold: !!selectedMeta?.isBold,
            isItalic: !!selectedMeta?.isItalic,
            isUnderline: !!selectedMeta?.isUnderline,
            isStrikethrough: !!selectedMeta?.isStrikethrough,
            fontStyle: String(selectedMeta?.fontStyle || ''),
        };
    }

    function createManualFieldElement(key, pageNum, xMm, yMm, wMm, hMm) {
        const fieldEl = document.createElement('div');
        fieldEl.className = 'tech-preview-field confidence-medium';
        fieldEl.dataset.fieldKey = key;
        fieldEl.dataset.page = String(pageNum);
        fieldEl.dataset.x = String(xMm);
        fieldEl.dataset.y = String(yMm);
        fieldEl.dataset.w = String(wMm);
        fieldEl.dataset.h = String(hMm);
        fieldEl.dataset.confidence = '0.7';
        fieldEl.innerHTML = '<span class="tech-preview-field-value" aria-hidden="true"></span>';
        return fieldEl;
    }

    function placeManualFieldAt(pageNum, mmX, mmY, draft) {
        const overlay = results.querySelector('#preview-overlay-' + pageNum);
        const img = results.querySelector('#preview-bg-' + pageNum);
        if (!overlay || !img) {
            return false;
        }
        const pageWidthMm = 215.9;
        const pageHeightMm = 279.4;
        const marginMm = 2;
        const wMm = Math.max(2, draft.widthMm || 45);
        const hMm = Math.max(2, draft.heightMm || 10);
        const xBase = Math.max(marginMm, Math.min(pageWidthMm - wMm - marginMm, mmX));
        const yBase = Math.max(marginMm, Math.min(pageHeightMm - hMm - marginMm, mmY));
        if (!wizardState.pagePaddingDeltaMm[pageNum]) {
            wizardState.pagePaddingDeltaMm[pageNum] = { dx: 0, dy: 0 };
        }
        const pad = wizardState.pagePaddingDeltaMm[pageNum];
        const key = 'manual_' + Date.now();
        wizardState.fieldUnpaddedMm[key] = { x: xBase, y: yBase };
        const xWithPad = Number((xBase + (pad.dx || 0)).toFixed(3));
        const yWithPad = Number((yBase + (pad.dy || 0)).toFixed(3));
        wizardState.positionsMap[key] = coerceFieldGeometry({
            name: key,
            canonicalName: key,
            page: pageNum,
            x: xWithPad,
            y: yWithPad,
            width: wMm,
            height: hMm,
            type: draft.type,
            fieldType: draft.type,
            fontFamily: draft.fontFamily,
            fontSize: draft.fontSize,
            fontSizeUnit: 'px',
            fontColor: draft.fontColor,
            isBold: draft.isBold,
            isItalic: draft.isItalic,
            isUnderline: draft.isUnderline,
            isStrikethrough: draft.isStrikethrough,
            fontStyle: draft.fontStyle,
        });
        const fieldEl = createManualFieldElement(key, pageNum, xWithPad, yWithPad, wMm, hMm);
        overlay.appendChild(fieldEl);
        layoutTechPreviewForImage(img);
        bindPreviewFieldInteractions(fieldEl, results);
        wizardState.positionsSaved = false;
        selectFieldElement(fieldEl);
        schedulePositionsAutoSave();
        return true;
    }

    function stopManualFieldPlacementMode() {
        if (!manualPlacementMode || !results) return;
        results.querySelectorAll('.tech-preview-overlay-layer').forEach((overlay) => {
            overlay.removeEventListener('mousemove', manualPlacementMode.onMouseMove);
            overlay.removeEventListener('mouseleave', manualPlacementMode.onMouseLeave);
            overlay.removeEventListener('click', manualPlacementMode.onClick, true);
            overlay.style.pointerEvents = '';
            overlay.style.cursor = '';
        });
        if (manualPlacementMode.onKeyDown) {
            document.removeEventListener('keydown', manualPlacementMode.onKeyDown, true);
        }
        if (manualPlacementMode.ghostEl && manualPlacementMode.ghostEl.parentNode) {
            manualPlacementMode.ghostEl.remove();
        }
        manualPlacementMode = null;
        if (selectedFieldStatus) {
            selectedFieldStatus.style.display = 'none';
        }
    }

    function startManualFieldPlacementMode() {
        if (!results || !wizardState.templateId) {
            alert('Open a template in the editor first.');
            return;
        }
        const overlays = Array.from(results.querySelectorAll('.tech-preview-overlay-layer'));
        if (!overlays.length) {
            alert('Preview is not available yet.');
            return;
        }
        stopManualFieldPlacementMode();
        const draft = buildManualFieldDraft();
        const ghostEl = document.createElement('div');
        ghostEl.className = 'tech-preview-field confidence-medium';
        ghostEl.style.pointerEvents = 'none';
        ghostEl.style.opacity = '0.55';
        ghostEl.style.display = 'none';
        ghostEl.dataset.page = '0';
        ghostEl.innerHTML = '<span class="tech-preview-field-value" aria-hidden="true"></span>';
        manualPlacementMode = {
            draft,
            ghostEl,
            onMouseMove: null,
            onMouseLeave: null,
            onClick: null,
            onKeyDown: null,
        };
        if (selectedFieldStatus) {
            selectedFieldStatus.style.display = 'none';
        }
        manualPlacementMode.onMouseMove = (ev) => {
            const overlay = ev.currentTarget;
            const pageNum = parseInt(String(overlay.id || '').replace('preview-overlay-', ''), 10) || 1;
            const img = results.querySelector('#preview-bg-' + pageNum);
            if (!img) return;
            const rect = overlay.getBoundingClientRect();
            const displayWidth = img.clientWidth || img.getBoundingClientRect().width;
            const displayHeight = img.clientHeight || img.getBoundingClientRect().height;
            const scaleX = displayWidth / 215.9;
            const scaleY = displayHeight / 279.4;
            if (!scaleX || !scaleY) return;
            const wPx = Math.max(5, draft.widthMm * scaleX);
            const hPx = Math.max(5, draft.heightMm * scaleY);
            const relX = Math.max(0, Math.min(rect.width - wPx, ev.clientX - rect.left - (wPx / 2)));
            const relY = Math.max(0, Math.min(rect.height - hPx, ev.clientY - rect.top - (hPx / 2)));
            if (ghostEl.parentNode !== overlay) {
                ghostEl.remove();
                overlay.appendChild(ghostEl);
            }
            ghostEl.style.display = '';
            ghostEl.style.left = `${relX}px`;
            ghostEl.style.top = `${relY}px`;
            ghostEl.style.width = `${wPx}px`;
            ghostEl.style.height = `${hPx}px`;
            ghostEl.dataset.page = String(pageNum);
        };
        manualPlacementMode.onMouseLeave = () => {
            ghostEl.style.display = 'none';
        };
        manualPlacementMode.onClick = (ev) => {
            ev.preventDefault();
            ev.stopPropagation();
            const overlay = ev.currentTarget;
            const pageNum = parseInt(String(overlay.id || '').replace('preview-overlay-', ''), 10) || 1;
            const img = results.querySelector('#preview-bg-' + pageNum);
            if (!img) return;
            const rect = overlay.getBoundingClientRect();
            const displayWidth = img.clientWidth || img.getBoundingClientRect().width;
            const displayHeight = img.clientHeight || img.getBoundingClientRect().height;
            const scaleX = displayWidth / 215.9;
            const scaleY = displayHeight / 279.4;
            if (!scaleX || !scaleY) return;
            const wPx = Math.max(5, draft.widthMm * scaleX);
            const hPx = Math.max(5, draft.heightMm * scaleY);
            const relX = Math.max(0, Math.min(rect.width - wPx, ev.clientX - rect.left - (wPx / 2)));
            const relY = Math.max(0, Math.min(rect.height - hPx, ev.clientY - rect.top - (hPx / 2)));
            const xMm = relX / scaleX;
            const yMm = relY / scaleY;
            if (placeManualFieldAt(pageNum, xMm, yMm, draft)) {
                stopManualFieldPlacementMode();
            }
        };
        manualPlacementMode.onKeyDown = (ev) => {
            if (ev.key === 'Escape') {
                ev.preventDefault();
                stopManualFieldPlacementMode();
            }
        };
        overlays.forEach((overlay) => {
            // Overlays are normally pointer-events:none; enable hit-testing during placement mode
            // so users can place fields anywhere, not only when hovering existing field boxes.
            overlay.style.pointerEvents = 'auto';
            overlay.style.cursor = 'crosshair';
            overlay.appendChild(ghostEl);
            overlay.addEventListener('mousemove', manualPlacementMode.onMouseMove);
            overlay.addEventListener('mouseleave', manualPlacementMode.onMouseLeave);
            overlay.addEventListener('click', manualPlacementMode.onClick, true);
        });
        document.addEventListener('keydown', manualPlacementMode.onKeyDown, true);
    }

    function applyGlobalFormTypography() {
        const gff = document.getElementById('globalFormFontFamilySelect');
        const gfs = document.getElementById('globalFormFontSizeInput');
        if (!gff || !gfs) return;
        const fam = normalizeGlobalFontFamilyValue(gff.value, gff);
        const sz = normalizeGlobalFontSizePx(gfs.value);
        Object.keys(wizardState.positionsMap).forEach((k) => {
            const m = wizardState.positionsMap[k];
            if (!m || typeof m !== 'object') return;
            m.fontFamily = fam;
            m.fontSize = sz;
            m.fontSizeUnit = 'px';
            m.fontSizeSource = 'user';
        });
        if (wizardState.selectedFieldKey) {
            syncSelectedFieldInputs();
        }
        refreshAllPreviewFieldValues();
        wizardState.positionsSaved = false;
        schedulePositionsAutoSave();
    }

    function selectFieldElement(el) {
        if (!el) return;
        const key = String(el.dataset.fieldKey || '');
        if (!key) return;
        clearFieldSelectionVisual();
        el.classList.add('is-selected');
        wizardState.selectedFieldKey = key;
        if (!wizardState.positionsMap[key]) {
            wizardState.positionsMap[key] = coerceFieldGeometry({
                name: key,
                canonicalName: key,
                x: parseFloat(el.dataset.x),
                y: parseFloat(el.dataset.y),
                width: parseFloat(el.dataset.w),
                height: parseFloat(el.dataset.h),
                page: parseInt(el.dataset.page || '1', 10),
            });
        }
        setFieldSidebarVisible(true);
        syncSelectedFieldInputs();
    }

    function updateSelectedFieldOverlay() {
        const key = wizardState.selectedFieldKey;
        const meta = getSelectedFieldMeta();
        if (!key || !meta) return;
        const fieldEl = getFieldElementByKey(key);
        if (!fieldEl) return;
        const pageNum = parseInt(fieldEl.dataset.page || '1', 10);
        const img = results.querySelector('#preview-bg-' + pageNum);
        if (!img) return;
        const displayWidth = img.clientWidth || img.getBoundingClientRect().width;
        const displayHeight = img.clientHeight || img.getBoundingClientRect().height;
        const scaleX = displayWidth / 215.9;
        const scaleY = displayHeight / 279.4;
        const left = getNumericInputValue(meta.x) * scaleX;
        const top = getNumericInputValue(meta.y) * scaleY;
        const width = Math.max(5, getNumericInputValue(meta.width) * scaleX);
        const height = Math.max(5, getNumericInputValue(meta.height) * scaleY);
        fieldEl.dataset.x = String(meta.x);
        fieldEl.dataset.y = String(meta.y);
        fieldEl.dataset.w = String(meta.width);
        fieldEl.dataset.h = String(meta.height);
        fieldEl.style.left = `${left}px`;
        fieldEl.style.top = `${top}px`;
        fieldEl.style.width = `${width}px`;
        fieldEl.style.height = `${height}px`;
        refreshPreviewValueForKey(key);
    }

    function applySelectedFieldInputValues() {
        const key = wizardState.selectedFieldKey;
        const meta = getSelectedFieldMeta();
        if (!key || !meta) return;
        meta.x = getNumericInputValue(fieldXInput ? fieldXInput.value : meta.x);
        meta.y = getNumericInputValue(fieldYInput ? fieldYInput.value : meta.y);
        meta.width = getNumericInputValue(fieldWidthInput ? fieldWidthInput.value : meta.width);
        meta.height = getNumericInputValue(fieldHeightInput ? fieldHeightInput.value : meta.height);
        meta.type = String(fieldTypeSelect ? fieldTypeSelect.value : (meta.type || 'text'));
        meta.fontFamily = String(fieldFontFamilySelect ? fieldFontFamilySelect.value : (meta.fontFamily || 'Arial'));
        meta.fontSize = normalizeGlobalFontSizePx(fieldFontSizeInput ? fieldFontSizeInput.value : (meta.fontSize || (FIELD_METRICS.DEFAULT_FONT_PX || 13)));
        meta.fontSizeUnit = 'px';
        meta.fontSizeSource = 'user';
        meta.fontColor = hexToRgbArray(fieldFontColorInput ? fieldFontColorInput.value : '#000000');
        meta.isBold = !!fieldBoldInput?.checked;
        meta.isItalic = !!fieldItalicInput?.checked;
        meta.isUnderline = !!fieldUnderlineInput?.checked;
        meta.isStrikethrough = !!fieldStrikeInput?.checked;
        const styleCodes = [];
        if (meta.isBold) styleCodes.push('B');
        if (meta.isItalic) styleCodes.push('I');
        if (meta.isUnderline) styleCodes.push('U');
        if (meta.isStrikethrough) styleCodes.push('S');
        meta.fontStyle = styleCodes.join('');
        const previousAssignment = wizardState.customFieldAssignments[key] || {};
        const assignment = {
            location: customFieldLocationSelect?.value || 'firm',
            linkId: customFieldSelect?.value || '',
            isManual: !!previousAssignment.isManual,
        };
        wizardState.customFieldAssignments[key] = assignment;
        meta.customFieldLocation = assignment.location;
        meta.customFieldLinkId = assignment.linkId;

        const locked = isCustomFieldLocked(key, meta);
        const currentType = String(meta.type || meta.fieldType || '').toLowerCase();
        const isCheckable = isCheckableFieldType(currentType);
        const previousCheckboxValue = isCheckable
            ? (isTruthyCheckboxValue(meta.defaultValue) ? '1' : '')
            : '';
        if (!locked) {
            let panelValue = '';
            if (isCheckable) {
                panelValue = fieldValueCheckbox?.checked ? '1' : '';
            } else if (fieldValueInput) {
                panelValue = String(fieldValueInput.value);
            }
            wizardState.fieldDefaults[key] = { value: panelValue };
            meta.defaultValue = panelValue;
            if (isCheckable && panelValue !== previousCheckboxValue) {
                meta.checkboxUserSet = true;
            }
        } else {
            const ov = getCustomFieldOverrideValue(key, meta);
            if (ov !== null) {
                meta.defaultValue = String(ov);
            }
            delete wizardState.fieldDefaults[key];
        }

        syncFieldUnpaddedFromPositions(key);
        wizardState.positionsSaved = false;
        updateSelectedFieldOverlay();
        schedulePositionsAutoSave();
    }

    async function savePositionsToServer(silent = false) {
        if (!wizardState.templateId) {
            return false;
        }
        if (autoSaveInFlight) {
            autoSaveQueued = true;
            if (autoSavePromise) {
                try {
                    return await autoSavePromise;
                } catch (e) {
                    return false;
                }
            }
            return false;
        }
        autoSaveInFlight = true;
        autoSavePromise = (async () => {
        if (silent) {
            setAutoSaveStatus('Saving…');
        }
        try {
            const res = await fetch('?route=api/positions/update', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    template_id: wizardState.templateId,
                    positions: wizardState.positionsMap,
                    firm_id: currentFirmIdForAlign,
                    form_number: String(wizardState.formNumber || '').trim(),
                    form_name: String(wizardState.formName || '').trim(),
                    form_location: String(wizardState.formLocation || '').trim()
                })
            });
            const raw = await res.text();
            let j = null;
            try {
                j = raw ? JSON.parse(raw) : {};
            } catch (e) {
                throw new Error(`Save failed (HTTP ${res.status}): ${String(raw || '').slice(0, 250)}`);
            }
            if (!res.ok || j.error) {
                throw new Error(j.error || j.message || 'Save failed');
            }
            wizardState.positionsSaved = true;
            syncManagedTemplateRowLocal(wizardState.templateId, {
                formNumber: String(j?.form_number || wizardState.formNumber || '').trim(),
                formName: String(j?.form_name || wizardState.formName || '').trim(),
                formLocation: String(j?.form_location || wizardState.formLocation || '').trim(),
            });
            renderManagedFormSearchList();
            lastAutoSaveError = '';
            setAutoSaveStatus('Saved');
            return true;
        } catch (err) {
            lastAutoSaveError = String(err?.message || err || 'Unknown auto-save error');
            setAutoSaveStatus('Auto-save failed', true);
            if (!silent) {
                alert(err.message || String(err));
            }
            return false;
        } finally {
            autoSaveInFlight = false;
            if (autoSaveQueued) {
                autoSaveQueued = false;
                schedulePositionsAutoSave();
            }
        }
        })();
        try {
            return await autoSavePromise;
        } finally {
            autoSavePromise = null;
        }
    }

    async function finalizeCurrentTemplateRegistration() {
        if (!wizardState.pendingRegistryCommit) {
            return true;
        }
        if (!wizardState.templateId) {
            throw new Error('Template ID is missing. Re-upload the PDF and try again.');
        }
        if (Object.keys(wizardState.positionsMap || {}).length > 0 && !wizardState.positionsSaved) {
            if (autoSaveTimer) {
                window.clearTimeout(autoSaveTimer);
                autoSaveTimer = null;
            }
            const saved = await savePositionsToServer(true);
            if (!saved) {
                throw new Error(lastAutoSaveError || 'Could not save latest template edits.');
            }
        }
        const res = await fetch('?route=api/form-management/finalize-template', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                template_id: String(wizardState.templateId || '').trim(),
                form_number: String(wizardState.formNumber || '').trim(),
                form_name: String(wizardState.formName || '').trim(),
                form_location: String(wizardState.formLocation || '').trim(),
                source_file_name: String(wizardState.sourceFileName || '').trim(),
                detected_firm_name: String(wizardState.detectedFirmName || '').trim()
            })
        });
        const payload = await res.json();
        if (!res.ok || !payload.success) {
            throw new Error(payload.error || payload.message || `Finalize failed (HTTP ${res.status})`);
        }
        wizardState.pendingRegistryCommit = false;
        const existingIdx = managedFormTemplates.findIndex((row) => String(row?.templateId || '').trim() === String(wizardState.templateId || '').trim());
        if (existingIdx < 0) {
            managedFormTemplates.unshift({
                templateId: String(wizardState.templateId || '').trim(),
                formName: String(payload?.template?.formName || wizardState.formName || wizardState.templateId || '').trim(),
                sourceFileName: String(payload?.template?.sourceFileName || wizardState.sourceFileName || '').trim(),
                formLocation: String(payload?.template?.formLocation || wizardState.formLocation || '').trim(),
            });
        } else {
            syncManagedTemplateRowLocal(wizardState.templateId, {
                formNumber: String(payload?.form_number || wizardState.formNumber || '').trim(),
                formName: String(payload?.form_name || wizardState.formName || '').trim(),
                formLocation: String(payload?.form_location || wizardState.formLocation || '').trim(),
            });
        }
        renderManagedFormSearchList();
        return true;
    }

    function schedulePositionsAutoSave() {
        if (!wizardState.templateId) return;
        if (autoSaveTimer) {
            window.clearTimeout(autoSaveTimer);
        }
        setAutoSaveStatus('Unsaved changes');
        autoSaveTimer = window.setTimeout(() => {
            autoSaveTimer = null;
            savePositionsToServer(true);
        }, 700);
    }

    function applyPagePaddingToFields(rootEl, pageNum, padPx) {
        const img = rootEl.querySelector('#preview-bg-' + pageNum);
        const overlay = rootEl.querySelector('#preview-overlay-' + pageNum);
        if (!img || !overlay) {
            return { applied: 0, dxMm: 0, dyMm: 0 };
        }

        const displayWidth = img.clientWidth || img.getBoundingClientRect().width;
        const displayHeight = img.clientHeight || img.getBoundingClientRect().height;
        const scaleX = displayWidth / 215.9;
        const scaleY = displayHeight / 279.4;
        if (!scaleX || !scaleY) {
            return { applied: 0, dxMm: 0, dyMm: 0 };
        }

        const dxPx = padPx.left - padPx.right;
        const dyPx = padPx.top - padPx.bottom;
        const dxMm = dxPx / scaleX;
        const dyMm = dyPx / scaleY;

        wizardState.pagePaddingDeltaMm[pageNum] = { dx: dxMm, dy: dyMm };

        let applied = 0;
        overlay.querySelectorAll('.tech-preview-field').forEach((fieldEl) => {
            const fieldPage = parseInt(fieldEl.dataset.page || '1', 10);
            if (fieldPage !== pageNum) return;
            const key = fieldEl.dataset.fieldKey || '';
            if (!key || !wizardState.positionsMap[key]) return;
            if (!wizardState.fieldUnpaddedMm[key]) {
                syncFieldUnpaddedFromPositions(key);
            }
            const base = wizardState.fieldUnpaddedMm[key];
            if (!base) return;
            const nextXmm = base.x + dxMm;
            const nextYmm = base.y + dyMm;
            wizardState.positionsMap[key].x = Number(nextXmm.toFixed(3));
            wizardState.positionsMap[key].y = Number(nextYmm.toFixed(3));
            fieldEl.dataset.x = String(wizardState.positionsMap[key].x);
            fieldEl.dataset.y = String(wizardState.positionsMap[key].y);
            applied++;
        });

        if (applied > 0) {
            wizardState.positionsSaved = false;
            schedulePositionsAutoSave();
        }

        layoutTechPreviewForImage(img);
        return { applied, dxMm, dyMm };
    }

    let wpsDragDocListeners = false;
    let wpsActiveDrag = null;
    function bindPreviewFieldInteractions(el, rootEl) {
        if (!el || String(el.dataset.wpsBound || '') === '1') return;
        el.dataset.wpsBound = '1';
        el.classList.add('is-draggable');
        el.addEventListener('click', (ev) => {
            ev.preventDefault();
            selectFieldElement(el);
        });
        el.addEventListener('mousedown', (ev) => {
            if (ev.button !== 0) return;
            ev.preventDefault();
            selectFieldElement(el);
            const pageNum = parseInt(el.dataset.page || '1', 10);
            const img = rootEl.querySelector('#preview-bg-' + pageNum);
            if (!img) return;
            const displayWidth = img.clientWidth || img.getBoundingClientRect().width;
            const displayHeight = img.clientHeight || img.getBoundingClientRect().height;
            const startX = ev.clientX;
            const startY = ev.clientY;
            const baseLeft = parseFloat(el.style.left) || 0;
            const baseTop = parseFloat(el.style.top) || 0;
            wpsActiveDrag = { el, rootEl, startX, startY, baseLeft, baseTop };
        });
    }
    function installPositionDragHandlers(rootEl) {
        rootEl.querySelectorAll('.tech-preview-field').forEach((el) => bindPreviewFieldInteractions(el, rootEl));
        if (wpsDragDocListeners) return;
        wpsDragDocListeners = true;
        document.addEventListener('mousemove', (ev) => {
            if (!wpsActiveDrag) return;
            const d = wpsActiveDrag;
            const dx = ev.clientX - d.startX;
            const dy = ev.clientY - d.startY;
            d.el.style.left = (d.baseLeft + dx) + 'px';
            d.el.style.top = (d.baseTop + dy) + 'px';
            const dragKey = d.el.dataset.fieldKey;
            const pageNum = parseInt(d.el.dataset.page || '1', 10);
            const img = d.rootEl.querySelector('#preview-bg-' + pageNum);
            if (dragKey && img && wizardState.positionsMap[dragKey] && wizardState.selectedFieldKey === dragKey) {
                const displayWidth = img.clientWidth || img.naturalWidth;
                const displayHeight = img.clientHeight || img.naturalHeight;
                const scaleX = displayWidth / 215.9;
                const scaleY = displayHeight / 279.4;
                const xMm = (parseFloat(d.el.style.left) || 0) / scaleX;
                const yMm = (parseFloat(d.el.style.top) || 0) / scaleY;
                wizardState.positionsMap[dragKey].x = xMm;
                wizardState.positionsMap[dragKey].y = yMm;
                d.el.dataset.x = String(xMm);
                d.el.dataset.y = String(yMm);
                if (fieldXInput) fieldXInput.value = String(Number(xMm.toFixed(3)));
                if (fieldYInput) fieldYInput.value = String(Number(yMm.toFixed(3)));
            }
        });
        document.addEventListener('mouseup', () => {
            if (!wpsActiveDrag) return;
            const el = wpsActiveDrag.el;
            const rootEl2 = wpsActiveDrag.rootEl;
            const key = el.dataset.fieldKey;
            const pageNum = parseInt(el.dataset.page || '1', 10);
            const img = rootEl2.querySelector('#preview-bg-' + pageNum);
            if (key && img && wizardState.positionsMap[key]) {
                const displayWidth = img.clientWidth || img.naturalWidth;
                const displayHeight = img.clientHeight || img.naturalHeight;
                const scaleX = displayWidth / 215.9;
                const scaleY = displayHeight / 279.4;
                const xMm = (parseFloat(el.style.left) || 0) / scaleX;
                const yMm = (parseFloat(el.style.top) || 0) / scaleY;
                wizardState.positionsMap[key].x = xMm;
                wizardState.positionsMap[key].y = yMm;
                el.dataset.x = String(xMm);
                el.dataset.y = String(yMm);
                syncFieldUnpaddedFromPositions(key);
                wizardState.positionsSaved = false;
                schedulePositionsAutoSave();
                if (wizardState.selectedFieldKey === key) {
                    syncSelectedFieldInputs();
                }
            }
            wpsActiveDrag = null;
        });
    }

    function suggestDefaultForFieldName(name, fieldMeta = {}) {
        const type = String(fieldMeta.type || fieldMeta.fieldType || '').toLowerCase();
        if (type === 'checkbox' || type === 'radio') return '1';
        const firm = String(wizardState.detectedFirmName || '').trim();
        if (firm && fieldKeyLooksLikeAttorneyFirm(name)) {
            return firm;
        }
        const lower = String(name).toLowerCase();
        const checkTypes = ['checkbox', 'check', 'radio', 'radiobutton', 'button', 'btn', 'option', 'choice', 'select', 'dropdown', 'toggle'];
        if (checkTypes.includes(type) || lower.includes('checkbox') || lower.includes('check')) return '1';
        return 'Sample';
    }

    function collectWizardValues() {
        const includeSamples = getExportIncludeSamples();
        const out = {};
        Object.entries(wizardState.positionsMap).forEach(([k, meta]) => {
            const v = resolveExportValueForField(k, meta, { includeSamples });
            if (v !== '') {
                out[k] = v;
            }
        });
        return out;
    }

    if (formManagerSidebar) {
        loadPersistedSidebarWidth();
        setFieldSidebarVisible(true);
        setFieldEditingEnabled(false);
        populateCustomFieldOptions('firm', '');
        customFieldLocationSelect?.addEventListener('change', () => {
            const selected = customFieldLocationSelect.value || 'firm';
            populateCustomFieldOptions(selected, '');
            const key = wizardState.selectedFieldKey;
            if (key) {
                const prev = wizardState.customFieldAssignments[key] || { linkId: '' };
                wizardState.customFieldAssignments[key] = {
                    location: selected,
                    linkId: prev.linkId || '',
                    isManual: true,
                };
                wizardState.positionsSaved = false;
                applySelectedFieldInputValues();
                syncSelectedFieldInputs();
            }
        });
        customFieldSelect?.addEventListener('change', () => {
            const key = wizardState.selectedFieldKey;
            if (!key) return;
            wizardState.customFieldAssignments[key] = {
                location: customFieldLocationSelect?.value || 'firm',
                linkId: customFieldSelect.value || '',
                isManual: true,
            };
            wizardState.positionsSaved = false;
            applySelectedFieldInputValues();
            syncSelectedFieldInputs();
        });
        if (suggestAliasBtn) {
            suggestAliasBtn.disabled = true;
            suggestAliasBtn.style.display = 'none';
        }
        suggestAliasBtn?.addEventListener('click', async () => {
            const key = wizardState.selectedFieldKey;
            if (!key) {
                alert('Select a field first.');
                return;
            }
            const assignment = wizardState.customFieldAssignments[key] || {};
            const linkId = String(assignment.linkId || customFieldSelect?.value || '').trim();
            if (!linkId) {
                alert('Assign a custom field first, then suggest an alias.');
                return;
            }
            const meta = wizardState.positionsMap[key] || {};
            const componentType = resolvePositionComponentType(meta);
            const templateId = String(wizardState.templateId || '').trim();
            const description = `Suggested from manual mapping: ${String(meta.label || key).trim()}`;
            suggestAliasBtn.disabled = true;
            try {
                const response = await fetch('?route=api/form-importer/suggest-alias', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        field_key: key,
                        link_id: linkId,
                        template_id: templateId,
                        component_type: componentType,
                        description: description,
                    }),
                });
                const payload = await response.json();
                if (!response.ok || !payload.success) {
                    throw new Error(payload.error || 'Failed to create suggested alias');
                }
                setAutoSaveStatus('Suggested alias draft created (disabled) in Alias Manager.');
            } catch (error) {
                alert(error.message || 'Failed to create alias suggestion.');
            } finally {
                suggestAliasBtn.disabled = false;
            }
        });
        deleteSelectedFieldBtn?.addEventListener('click', async () => {
            const key = wizardState.selectedFieldKey;
            if (!key) {
                alert('Select a field to delete.');
                return;
            }
            const label = deriveFieldLabel(key, wizardState.positionsMap[key] || {});
            if (!window.confirm(`Delete selected field "${label}"? This cannot be undone.`)) {
                return;
            }
            delete wizardState.positionsMap[key];
            delete wizardState.fieldDefaults[key];
            delete wizardState.customFieldAssignments[key];
            delete wizardState.fieldUnpaddedMm[key];
            const fieldEl = getFieldElementByKey(key);
            if (fieldEl) fieldEl.remove();
            wizardState.selectedFieldKey = '';
            wizardState.positionsSaved = false;
            clearFieldSelectionVisual();
            resetPropertiesPanelPrompt();
            // Keep the right panel visible after delete so users can continue editing without it disappearing.
            setFieldSidebarVisible(true);
            await savePositionsToServer(true);
            setAutoSaveStatus('Saved');
        });
        [
            fieldValueInput,
            fieldValueCheckbox,
            fieldXInput,
            fieldYInput,
            fieldWidthInput,
            fieldHeightInput,
            fieldTypeSelect,
            fieldFontFamilySelect,
            fieldFontSizeInput,
            fieldFontColorInput,
            fieldBoldInput,
            fieldItalicInput,
            fieldUnderlineInput,
            fieldStrikeInput
        ].forEach((el) => {
            el?.addEventListener('input', applySelectedFieldInputValues);
            el?.addEventListener('change', applySelectedFieldInputValues);
        });

        // Page 2 removed the upload-step custom-fields editor UI.

    }

    closeFieldSidebarBtn?.addEventListener('click', () => {
        wizardState.selectedFieldKey = '';
        clearFieldSelectionVisual();
        resetPropertiesPanelPrompt();
        setFieldSidebarVisible(true);
    });

    function renderEditorFromTemplateData(data) {
        results.className = 'results-section success';

        let html = '';
        let fieldsArrayForPageMap = [];

        // Technical Preview: simple, client-friendly preview
        if (data.data.background_paths) {
            const backgroundPaths = data.data.background_paths;

            // Normalize fields into an array with stable canonical keys matching normalizeFieldsToKeyedMap().
            const fieldsArray = data.data.fields
                ? (Array.isArray(data.data.fields)
                    ? data.data.fields.map((f, i) => {
                        const ff = f && typeof f === 'object' ? f : {};
                        const stableKey = stableExtractFieldKey(ff, i, undefined);
                        const rawName = String(ff.name || ff.fieldName || ff.canonicalName || stableKey).trim() || stableKey;
                        const canon = canonicalizeFieldKey(ff.canonicalName || rawName, stableKey) || stableKey;
                        return Object.assign({}, ff, { name: rawName, canonicalName: canon });
                    })
                    : Object.entries(data.data.fields).map(([name, field], i) => {
                        const ff = field && typeof field === 'object' ? field : {};
                        const stableKey = stableExtractFieldKey(ff, i, name);
                        const rawName = String(ff.name || ff.fieldName || name || stableKey).trim() || stableKey;
                        const canon = canonicalizeFieldKey(ff.canonicalName || rawName, stableKey) || stableKey;
                        return Object.assign({}, ff, { name: rawName, canonicalName: canon });
                    }))
                : [];

            fieldsArrayForPageMap = fieldsArray;

            const fieldsByPage = {};
            fieldsArray.forEach((field, globalIdx) => {
                const page = field.page || 1;
                if (!fieldsByPage[page]) fieldsByPage[page] = [];
                fieldsByPage[page].push(Object.assign({}, field, { _previewGlobalIdx: globalIdx }));
            });

            const templateIdRaw = data.data.template_id || '';
            const templateForEditor = templateIdRaw.replace(/^t_/, '');
            const firmFromResponse = String(data.data.detected_firm_name || '').trim();

            html += `
                <div class="tech-preview">
                    <h3>🔍 Technical Preview – PDF Background & Field Positions</h3>
                    ${firmFromResponse ? `<div class="detected-firm-banner"><strong>Firm name</strong> (from PDF scan / database): ${escapeHtml(firmFromResponse)}</div>` : ''}
                    <p>Preview shows page backgrounds and detected fields. Drag highlighted boxes to adjust positions; coordinates and field edits save automatically.</p>
                    <div class="tech-preview-controls">
                        <div class="tech-preview-legend" role="group" aria-label="Input box color legend">
                            <span class="tech-preview-legend-title">Input box color legend:</span>
                            <span class="tech-preview-legend-item" title="Fields found by PDF extraction; not yet mapped to your Field Manager catalog."><span class="tech-preview-legend-swatch" style="background: rgba(37,99,235,0.20); border-color: #2563eb;"></span>Unmapped input box</span>
                            <span class="tech-preview-legend-item" title="Input box auto-matched to a Field Manager catalog entry using matching tags."><span class="tech-preview-legend-swatch" style="background: rgba(124,58,237,0.24); border-color: #7c3aed;"></span>Catalog-linked input box</span>
                        </div>
                        <div class="tech-preview-visibility-options" role="group" aria-label="Preview text visibility options">
                            <label><input type="checkbox" id="previewHideSampleData"> Hide Sample Data</label>
                            <label><input type="checkbox" id="previewHideLinkedData"> Hide Linked Data</label>
                        </div>
                    </div>
                    <div class="tech-preview-pages">
            `;

            Object.keys(backgroundPaths)
                .sort((a, b) => parseInt(a) - parseInt(b))
                .forEach(pageNum => {
                    const bgPath = backgroundPaths[pageNum];
                    if (!bgPath) return;

                    // Always route uploaded assets through backend endpoint to avoid docroot/layout issues.
                    let bgUrl = String(bgPath || '').trim();
                    if (bgUrl.startsWith('uploads/')) {
                        // Already published to mvp/uploads and can be loaded directly.
                    } else {
                    const parts = bgUrl.split(/[/\\]/).filter(Boolean);
                    const basename = parts.length ? parts[parts.length - 1] : '';
                    if (basename && !bgUrl.startsWith('?route=')) {
                        bgUrl = `?route=actions/uploads-asset&file=${encodeURIComponent(basename)}`;
                    }
                    }

                    const pageFields = fieldsByPage[pageNum] || [];

                    html += `
                        <div class="tech-preview-page">
                            <div class="tech-preview-page-title">Page ${pageNum} ${pageFields.length ? `· Fields detected: ${pageFields.length}` : '· No fields detected yet (background ready for manual mapping)'}</div>
                            <div class="page-padding-controls" data-padding-controls-page="${pageNum}">
                                <div class="page-padding-title-row">
                                    <div style="font-size:12px;font-weight:700;color:#1e293b;">Page ${pageNum} padding / offset (px)</div>
                                    <button type="button" class="page-padding-help" title="Use positive or negative pixel values; only this page is affected." aria-label="Page ${pageNum} padding help">?</button>
                                </div>
                                <div class="page-padding-row">
                                    <div class="page-padding-input-group">
                                        <label for="page-pad-top-${pageNum}">Top</label>
                                        <input id="page-pad-top-${pageNum}" type="number" step="1" value="0" data-pad-page="${pageNum}" data-pad-edge="top" />
                                    </div>
                                    <div class="page-padding-input-group">
                                        <label for="page-pad-left-${pageNum}">Left</label>
                                        <input id="page-pad-left-${pageNum}" type="number" step="1" value="0" data-pad-page="${pageNum}" data-pad-edge="left" />
                                    </div>
                                    <div class="page-padding-input-group">
                                        <label for="page-pad-bottom-${pageNum}">Bottom</label>
                                        <input id="page-pad-bottom-${pageNum}" type="number" step="1" value="0" data-pad-page="${pageNum}" data-pad-edge="bottom" />
                                    </div>
                                    <div class="page-padding-input-group">
                                        <label for="page-pad-right-${pageNum}">Right</label>
                                        <input id="page-pad-right-${pageNum}" type="number" step="1" value="0" data-pad-page="${pageNum}" data-pad-edge="right" />
                                    </div>
                                    <button type="button" class="page-padding-btn page-padding-apply" data-pad-page="${pageNum}">Apply to page ${pageNum}</button>
                                    <button type="button" class="page-padding-btn secondary page-padding-reset" data-pad-page="${pageNum}">Reset inputs</button>
                                </div>
                                <div class="page-padding-note" id="page-padding-note-${pageNum}"></div>
                            </div>
                            <div class="tech-preview-canvas">
                                <img src="${bgUrl}" alt="Background page ${pageNum}" class="tech-preview-bg" id="preview-bg-${pageNum}" />
                                <div class="tech-preview-overlay-layer" id="preview-overlay-${pageNum}">
                    `;

                    if (pageFields.length) {
                        pageFields.forEach((field) => {
                            const fieldKey = String(field.canonicalName || field.name || '').trim()
                                || stableExtractFieldKey(field, field._previewGlobalIdx, undefined);
                            const confidenceRaw = field.confidence;
                            const confidenceValue = Number.isFinite(Number(confidenceRaw)) ? Number(confidenceRaw) : '';
                            const showFirmSample = Boolean(firmFromResponse && fieldKeyLooksLikeAttorneyFirm(fieldKey));
                            const titleText = showFirmSample ? `${fieldKey} — ${firmFromResponse}` : fieldKey;
                            html += `
                                <div class="tech-preview-field${showFirmSample ? ' has-detected-firm' : ''}"
                                     data-field-key="${escapeAttr(fieldKey)}"
                                     data-page="${pageNum}"
                                     data-x="${field.x || 0}"
                                     data-y="${field.y || 0}"
                                     data-w="${field.width || 0}"
                                     data-h="${field.height || 0}"
                                     data-confidence="${confidenceValue}"
                                     title="${escapeAttr(titleText)}"><span class="tech-preview-field-value" aria-hidden="true"></span></div>
                            `;
                        });
                    }

                    html += `
                                </div>
                            </div>
                        </div>
                    `;
                });

            html += `
                    </div>
                </div>
            `;

            if (!fieldsArray.length && templateForEditor) {
                html += `
                    <div style="margin-top: 20px; padding: 16px; background: #fff3cd; border-radius: 8px; border: 1px solid #ffeeba;">
                        <strong style="display:block; margin-bottom:8px;">No fields were detected automatically for this form.</strong>
                        <p style="margin:0 0 12px 0; color:#856404;">You can still map this form once in the Visual Field Editor – we’ll reuse those positions automatically for future uploads of the same PDF.</p>
                        <a href="visual-field-editor.php?template=${encodeURIComponent(templateForEditor)}"
                           target="_blank"
                           style="display:inline-block; padding:10px 18px; background:#667eea; color:#fff; text-decoration:none; border-radius:6px; font-weight:600; font-size:14px;">
                            📝 Open Visual Field Editor for this form
                        </a>
                    </div>
                `;
            }
        }

        results.innerHTML = html;

        wizardState.templateId = data.data.template_id || '';
        wizardState.pendingRegistryCommit = Boolean(data?.data?.registry_pending_finish);
        wizardState.sourceFileName = String(data?.data?.source_file_name || '').trim();
        const serverFormNumber = String(data?.data?.form_number || '').trim();
        const serverFormName = String(
            data?.data?.form_name
            || data?.data?.detected_form_title
            || data?.data?.registered_form_name
            || ''
        ).trim();
        const identity = deriveInitialFormIdentity(
            wizardState.templateId,
            serverFormName
        );
        wizardState.formNumber = serverFormNumber || identity.number || '';
        wizardState.formName = String(data?.data?.form_name || identity.name || '').trim();
        if (formNumberInput) {
            formNumberInput.value = wizardState.formNumber;
        }
        if (formNameInput) {
            formNameInput.value = wizardState.formName;
        }
        wizardState.formLocation = String(data?.data?.form_location || '').trim();
        if (formLocationInput) {
            formLocationInput.value = wizardState.formLocation;
        }
        syncManagedTemplateRowLocal(wizardState.templateId, {
            formNumber: wizardState.formNumber,
            formName: wizardState.formName,
            formLocation: wizardState.formLocation,
        });
        updateFormSearchKeyHint();
        wizardState.positionsMap = normalizeFieldsToKeyedMap(data.data.fields || {});
        const keyToPage = Object.create(null);
        fieldsArrayForPageMap.forEach((field, globalIdx) => {
            const fieldKey = String(field.canonicalName || field.name || '').trim()
                || stableExtractFieldKey(field, globalIdx, undefined);
            if (fieldKey) {
                keyToPage[fieldKey] = parseInt(field.page, 10) || 1;
            }
        });
        Object.keys(wizardState.positionsMap).forEach((k) => {
            const meta = wizardState.positionsMap[k];
            if (!meta || typeof meta !== 'object') return;
            if (keyToPage[k] != null) {
                meta.page = keyToPage[k];
            } else if (meta.page == null || meta.page === undefined) {
                meta.page = 1;
            }
        });
        let fontMigrationTouched = 0;
        const defaultFontPx = getNumericInputValue(FIELD_METRICS.DEFAULT_FONT_PX || 13);
        if (wizardState.templateId && shouldPromptLegacyFontMigration(wizardState.positionsMap)) {
            const decisionKey = getFontMigrationDecisionKey(wizardState.templateId);
            let decision = '';
            try {
                decision = String(window.localStorage.getItem(decisionKey) || '');
            } catch (_) {
                decision = '';
            }
            if (!decision) {
                const doUpgrade = window.confirm('This template appears to use legacy 7-10 pt import text sizing. Upgrade this template to the 13 px standard now?');
                decision = doUpgrade ? 'upgrade' : 'keep';
                try {
                    window.localStorage.setItem(decisionKey, decision);
                } catch (_) {
                    // Ignore storage failures (private mode/storage disabled).
                }
            }
            if (decision === 'upgrade') {
                fontMigrationTouched = applyLegacyFontMigration(wizardState.positionsMap, defaultFontPx);
            }
        }
        initWizardPaddingBaselines();
        hydrateFieldDefaultsFromPositionsMap();
        const firmResolvedEarly = String(data.data.detected_firm_name || wizardState.detectedFirmName || '').trim();
        if (firmResolvedEarly) {
            wizardState.detectedFirmName = firmResolvedEarly;
            applyDetectedFirmToWizardState(firmResolvedEarly);
        } else {
            refreshAllPreviewFieldValues();
        }
        if (fontMigrationTouched > 0) {
            wizardState.positionsSaved = false;
            setAutoSaveStatus(`Unsaved changes (upgraded ${fontMigrationTouched} fields to ${defaultFontPx} px)`);
            schedulePositionsAutoSave();
        } else {
            // Loaded positions come from server state, so start as clean.
            // This avoids forcing a redundant save before first export.
            wizardState.positionsSaved = true;
            setAutoSaveStatus('Saved');
        }
        wizardState.selectedFieldKey = '';

        ensureTechPreviewResizeListener();
        document.querySelectorAll('.tech-preview-bg').forEach((img) => {
            if (img.complete) {
                layoutTechPreviewForImage(img);
            } else {
                img.addEventListener('load', () => layoutTechPreviewForImage(img));
            }
        });
        requestAnimationFrame(() => {
            requestAnimationFrame(() => refreshAllPreviewFieldValues());
        });

        installPositionDragHandlers(results);
        resetPropertiesPanelPrompt();
        setFieldSidebarVisible(true);
        showWizardStep(2);
        updateFormSearchKeyHint();
        const gfs = document.getElementById('globalFormFontSizeInput');
        const gff = document.getElementById('globalFormFontFamilySelect');
        if (gff && gfs) {
            const firstMeta = wizardState.positionsMap[Object.keys(wizardState.positionsMap)[0]];
            const isFreshImport = Boolean(data?.data?.registry_pending_finish);
            if (isFreshImport) {
                const pref = readPersistedGlobalTypographyPreference(gff);
                applyGlobalTypographyControls(gff, gfs, pref.fontFamily, pref.fontSizePx);
                applyGlobalFormTypography();
            } else if (firstMeta && typeof firstMeta === 'object') {
                const ff = String(firstMeta.fontFamily || '').trim();
                const sizePx = normalizeGlobalFontSizePx(getNumericInputValue(firstMeta.fontSize || UI_DEFAULT_FONT_PX));
                applyGlobalTypographyControls(gff, gfs, ff, sizePx);
            } else {
                applyGlobalTypographyControls(gff, gfs, IMPORTER_DEFAULT_FONT_FAMILY, UI_DEFAULT_FONT_PX);
            }
        }

        results.querySelectorAll('.page-padding-apply').forEach((btn) => {
            btn.addEventListener('click', () => {
                const pageNum = parseInt(btn.getAttribute('data-pad-page') || '1', 10);
                const readPad = (edge) => {
                    const input = results.querySelector(`input[data-pad-page="${pageNum}"][data-pad-edge="${edge}"]`);
                    return getNumericInputValue(input ? input.value : 0);
                };
                const padPx = {
                    top: readPad('top'),
                    left: readPad('left'),
                    bottom: readPad('bottom'),
                    right: readPad('right')
                };
                const outcome = applyPagePaddingToFields(results, pageNum, padPx);
                const note = document.getElementById(`page-padding-note-${pageNum}`);
                if (note) {
                    if (outcome.applied > 0) {
                        note.textContent = `Applied to ${outcome.applied} fields on page ${pageNum}. Shift: ${outcome.dxMm.toFixed(2)}mm X, ${outcome.dyMm.toFixed(2)}mm Y. Changes will save automatically.`;
                    } else {
                        note.textContent = `No fields updated for page ${pageNum}.`;
                    }
                }
            });
        });

        results.querySelectorAll('.page-padding-reset').forEach((btn) => {
            btn.addEventListener('click', () => {
                const pageNum = parseInt(btn.getAttribute('data-pad-page') || '1', 10);
                ['top', 'left', 'bottom', 'right'].forEach((edge) => {
                    const input = results.querySelector(`input[data-pad-page="${pageNum}"][data-pad-edge="${edge}"]`);
                    if (input) input.value = '0';
                });
                const note = document.getElementById(`page-padding-note-${pageNum}`);
                if (note) {
                    note.textContent = `Inputs reset for page ${pageNum}; applying zero offset.`;
                }
                const zeroPad = { top: 0, left: 0, bottom: 0, right: 0 };
                const outcome = applyPagePaddingToFields(results, pageNum, zeroPad);
                if (note && outcome.applied > 0) {
                    note.textContent = `Padding cleared on page ${pageNum} for ${outcome.applied} fields.`;
                }
            });
        });
        results.querySelector('#previewHideSampleData')?.addEventListener('change', () => {
            refreshAllPreviewFieldValues();
        });
        results.querySelector('#previewHideLinkedData')?.addEventListener('change', () => {
            refreshAllPreviewFieldValues();
        });
    }
    
    // Form submission
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        const hasLocalFile = !!(pdfFileInput.files && pdfFileInput.files.length > 0);
        const selectedServerPdf = String(existingServerPdfSelect?.value || '').trim();
        if (!hasLocalFile && selectedServerPdf === '') {
            const uploadErr = document.getElementById('uploadInlineError');
            if (uploadErr) {
                uploadErr.style.display = 'block';
                uploadErr.textContent = 'Please select a local PDF or choose an existing server PDF.';
            }
            return;
        }
        
        submitBtn.disabled = true;
        submitBtn.textContent = '⏳ Processing...';

        showWizardStep(2);
        results.style.display = 'block';
        results.className = 'results-section';
        results.innerHTML = `
            <div class="loading">
                <div class="spinner"></div>
                <p>Analyzing PDF and extracting field positions...</p>
            </div>
        `;
        
        const formData = new FormData();
        const templateIdInput = (form.querySelector('[name="template_id"]')?.value || '').trim();
        if (templateIdInput !== '') {
            formData.append('template_id', templateIdInput);
        }
        if (hasLocalFile) {
            formData.append('pdf_file', pdfFileInput.files[0]);
        } else if (selectedServerPdf !== '') {
            formData.append('selected_pdf_path', selectedServerPdf);
        }
        const clientSel = document.getElementById('clientIdSelect');
        if (clientSel && clientSel.value) {
            formData.append('client_id', clientSel.value);
        }
        
        let rawResponse = '';
        try {
            const response = await fetch('?route=actions/universal-process', {
                method: 'POST',
                body: formData
            });
            
            rawResponse = await response.text();
            let data;
            try {
                data = JSON.parse(rawResponse);
            } catch (parseError) {
                throw new Error(`Unexpected server response (HTTP ${response.status}). ${rawResponse.slice(0, 400)}`);
            }
            
            if (response.ok && data.success) {
                renderEditorFromTemplateData(data);
            } else {
                throw new Error(data.message || `Server returned status ${response.status}`);
            }
        } catch (error) {
            results.className = 'results-section error';
            const rawSnippet = rawResponse && rawResponse.trim().length > 0
                ? `<details class="raw-response"><summary>Show server response</summary><pre>${escapeHtml(rawResponse.slice(0, 1500))}${rawResponse.length > 1500 ? '…' : ''}</pre></details>`
                : '';
            results.innerHTML = `
                <h2>❌ Error</h2>
                <p>${escapeHtml(error.message)}</p>
                ${rawSnippet}
                <p style="margin-top: 20px;">This could mean:</p>
                <ul>
                    <li>The PDF is corrupted or invalid</li>
                    <li>The PDF format is not supported</li>
                    <li>Server configuration issue (check PHP extensions)</li>
                </ul>
                <p style="margin-top: 16px;">Need help? Contact support with this error message.</p>
            `;
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = isFormManagement ? 'Next: Analyze form fields' : 'Next: Analyze & extract fields';
        }
    });

    document.getElementById('wizardBackTo1')?.addEventListener('click', () => goToFormSearchStep());
    document.getElementById('wizardBackToValues')?.addEventListener('click', () => showWizardStep(2));

    document.getElementById('wizardGeneratePdfBtn')?.addEventListener('click', async () => {
        const statusEl = document.getElementById('wizardGenerateStatus');
        const btn = document.getElementById('wizardGeneratePdfBtn');
        if (!wizardState.templateId || !statusEl || !btn) return;
        if (Object.keys(wizardState.positionsMap).length > 0 && !wizardState.positionsSaved) {
            if (autoSaveTimer) {
                window.clearTimeout(autoSaveTimer);
                autoSaveTimer = null;
            }
            setAutoSaveStatus('Saving before generating…');
            let saved = false;
            for (let attempt = 0; attempt < 4 && !saved; attempt++) {
                if (attempt > 0) await new Promise((r) => setTimeout(r, 250));
                saved = await savePositionsToServer(true);
            }
            if (!saved) {
                const detail = lastAutoSaveError ? ` ${escapeHtml(lastAutoSaveError)}` : '';
                statusEl.innerHTML = `<span style="color:#b91c1c;">Could not save template data yet.${detail}</span>`;
                return;
            }
        }
        btn.disabled = true;
        statusEl.innerHTML = '<span style="color:#1d4ed8;">Generating PDF…</span>';
        try {
            statusEl.innerHTML = '<span style="color:#1d4ed8;">Preparing export values…</span>';
            const vals = collectWizardValues();
            statusEl.innerHTML = '<span style="color:#1d4ed8;">Sending export request…</span>';
            const body = new URLSearchParams();
            body.set('template_id', wizardState.templateId);
            body.set('values', JSON.stringify(vals));
            body.set('show_sample_data', getExportIncludeSamples() ? '1' : '0');
            const response = await fetch('?route=actions/universal-generate', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body.toString()
            });
            const payload = await response.json();
            if (!response.ok || !payload.success) {
                throw new Error(payload.message || 'Generate failed');
            }
            if (isFormManagement) {
                if (payload.downloadUrl) {
                    window.location.assign(String(payload.downloadUrl));
                }
                statusEl.innerHTML = `<span style="color:#166534;">${escapeHtml(payload.message || 'Export ready.')}</span>`;
            } else {
                const dl = document.getElementById('wizardDownloadArea');
                if (dl) {
                    dl.innerHTML = `
                        <p style="color:#166534;">${escapeHtml(payload.message || 'Done')}</p>
                        <div style="display:flex;flex-wrap:wrap;gap:12px;align-items:center;margin-top:10px;">
                            <a href="${escapeHtml(payload.downloadUrl)}" class="browse-btn wizard-action-btn" style="display:inline-flex;text-decoration:none;">⬇️ Download PDF</a>
                            <button type="button" class="browse-btn wizard-action-btn" data-action="wizard-finish-bundle">Finish — save template JSON</button>
                        </div>
                    `;
                }
                statusEl.innerHTML = '<span style="color:#166534;">Export ready. Use Download PDF.</span>';
                showWizardStep(3);
            }
        } catch (err) {
            statusEl.innerHTML = `<span style="color:#b91c1c;">${escapeHtml(err.message || String(err))}</span>`;
        } finally {
            btn.disabled = false;
        }
    });

    document.body.addEventListener('click', async (ev) => {
        const trigger = ev.target && ev.target.closest && ev.target.closest('[data-action="wizard-finish-bundle"]');
        if (!trigger) return;
        ev.preventDefault();
        const statusEl = document.getElementById('wizardGenerateStatus');
        if (!wizardState.templateId) {
            if (statusEl) statusEl.innerHTML = '<span style="color:#b91c1c;">Analyze a PDF first so a template ID exists.</span>';
            return;
        }
        if (!statusEl) return;
        if (Object.keys(wizardState.positionsMap).length > 0 && !wizardState.positionsSaved) {
            if (autoSaveTimer) {
                window.clearTimeout(autoSaveTimer);
                autoSaveTimer = null;
            }
            setAutoSaveStatus('Saving before export…');
            let saved = false;
            for (let attempt = 0; attempt < 4 && !saved; attempt++) {
                if (attempt > 0) await new Promise((r) => setTimeout(r, 250));
                saved = await savePositionsToServer(true);
            }
            if (!saved) {
                const detail = lastAutoSaveError ? ` ${escapeHtml(lastAutoSaveError)}` : '';
                statusEl.innerHTML = `<span style="color:#b91c1c;">Could not save template data yet.${detail}</span>`;
                return;
            }
        }
        trigger.disabled = true;
        document.querySelectorAll('[data-action="wizard-finish-bundle"]').forEach((b) => { b.disabled = true; });
        statusEl.innerHTML = '<span style="color:#1d4ed8;">Preparing template bundle…</span>';
        try {
            const body = new URLSearchParams();
            body.set('template_id', wizardState.templateId);
            const res = await fetch('?route=actions/template-finish-bundle', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body.toString(),
            });
            if (!res.ok) {
                const errText = await res.text();
                let msg = errText;
                try {
                    const j = JSON.parse(errText);
                    msg = j.message || msg;
                } catch (e2) { /* ignore */ }
                throw new Error(msg || `HTTP ${res.status}`);
            }
            const blob = await res.blob();
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `${wizardState.templateId}_template_bundle.json`;
            a.rel = 'noopener';
            document.body.appendChild(a);
            a.click();
            a.remove();
            URL.revokeObjectURL(url);
            statusEl.innerHTML = '<span style="color:#166534;">Template bundle downloaded (positions, background paths, PDF file reference).</span>';
        } catch (err) {
            statusEl.innerHTML = `<span style="color:#b91c1c;">${escapeHtml(err.message || String(err))}</span>`;
        } finally {
            document.querySelectorAll('[data-action="wizard-finish-bundle"]').forEach((b) => { b.disabled = false; });
        }
    });

    document.getElementById('wizardShowSampleData')?.addEventListener('change', () => {
        refreshAllPreviewFieldValues();
        if (wizardState.selectedFieldKey) {
            syncSelectedFieldInputs();
        }
    });

    function resetWizardToStart() {
        wizardState.templateId = '';
        wizardState.pendingRegistryCommit = false;
        wizardState.sourceFileName = '';
        wizardState.formNumber = '';
        wizardState.formName = '';
        wizardState.formLocation = '';
        wizardState.positionsMap = {};
        wizardState.pagePaddingDeltaMm = {};
        wizardState.fieldUnpaddedMm = {};
        wizardState.positionsSaved = false;
        wizardState.selectedFieldKey = '';
        wizardState.fieldDefaults = {};
        wizardState.customFieldAssignments = {};
        wizardState.detectedFirmName = formTemplatePrefill.detectedFirmName || '';
        if (results) {
            results.innerHTML = '';
        }
        stopManualFieldPlacementMode();
        document.getElementById('wizardDownloadArea')?.replaceChildren();
        const gs = document.getElementById('wizardGenerateStatus');
        if (gs) gs.innerHTML = '';
        if (pdfFileInput) {
            pdfFileInput.value = '';
        }
        if (uploadZone) {
            uploadZone.classList.remove('file-selected', 'dragover');
        }
        if (fileInfo) {
            fileInfo.textContent = '';
            fileInfo.style.display = 'none';
        }
        if (existingServerPdfSelect) {
            existingServerPdfSelect.value = '';
            setExistingServerPdfStatus('', '');
        }
        if (formSearchInput) {
            formSearchInput.value = '';
        }
        formSearchLoadingTemplateId = '';
        setFormSearchStatus('', '');
        renderManagedFormSearchList();
        if (!isFormNew) {
            setUploadFormVisible(false);
        }
        clearUploadInlineError();
        const tidInput = form.querySelector('[name="template_id"]');
        if (tidInput) {
            tidInput.value = '';
        }
        if (formNumberInput) formNumberInput.value = '';
        if (formNameInput) formNameInput.value = '';
        if (formLocationInput) formLocationInput.value = '';
        const tb = document.getElementById('formInsertFieldToolbar');
        if (tb) tb.style.display = 'none';
        const modeSel = document.getElementById('wizardExportModeSelect');
        if (modeSel) modeSel.value = 'test';
        customFieldCatalog.length = 0;
        initialCustomFieldCatalog.forEach((row) => {
            customFieldCatalog.push({ ...row });
        });
        invalidateAutoDetectedLinkCache();
        populateCustomFieldOptions('firm', '');
        const sampleCb = document.getElementById('wizardShowSampleData');
        if (sampleCb) sampleCb.checked = true;
        const hideSampleCb = document.getElementById('previewHideSampleData');
        if (hideSampleCb) hideSampleCb.checked = false;
        const hideLinkedCb = document.getElementById('previewHideLinkedData');
        if (hideLinkedCb) hideLinkedCb.checked = false;
        clearFieldSelectionVisual();
        resetPropertiesPanelPrompt();
        setFieldSidebarVisible(true);
        updateFormSearchKeyHint();
        showWizardStep(1);
    }

    document.getElementById('wizardRestartBtn')?.addEventListener('click', resetWizardToStart);

    document.getElementById('wizardFinishedBtn')?.addEventListener('click', () => {
        const finishBtn = document.getElementById('wizardFinishedBtn');
        const run = async () => {
            const prevLabel = finishBtn ? String(finishBtn.textContent || 'Finished') : 'Finished';
            if (finishBtn) {
                finishBtn.disabled = true;
                finishBtn.textContent = 'Saving...';
            }
            try {
                await finalizeCurrentTemplateRegistration();
            } catch (err) {
                alert(err?.message || 'Failed to finish form import.');
                if (finishBtn) {
                    finishBtn.disabled = false;
                    finishBtn.textContent = prevLabel;
                }
                return;
            }
            if (finishBtn) {
                finishBtn.disabled = false;
                finishBtn.textContent = prevLabel;
            }
        const runtimeFinishRedirect = new URL(window.location.href).searchParams.get('finish_redirect') || '';
        const dest = normalizeFinishRedirectUrl(formsManagerFinishRedirect || runtimeFinishRedirect || '');
        if (dest !== '') {
            const importedTemplateId = resolveTemplateIdForFinishRedirect();
            const url = new URL(dest, window.location.origin);
            if (importedTemplateId) {
                url.searchParams.set('imported_template_id', importedTemplateId);
            }
            window.location.assign(url.toString());
            return;
        }
        if (isFormNew) {
            window.location.assign('?route=form-management&fm_step=1');
            return;
        }
        resetWizardToStart();
        };
        run();
    });

    const globalFontFamilyControl = document.getElementById('globalFormFontFamilySelect');
    const globalFontSizeControl = document.getElementById('globalFormFontSizeInput');
    const onGlobalTypographyChange = () => {
        if (globalFontFamilyControl && globalFontSizeControl) {
            applyGlobalTypographyControls(
                globalFontFamilyControl,
                globalFontSizeControl,
                globalFontFamilyControl.value,
                globalFontSizeControl.value
            );
            persistGlobalTypographyPreference(globalFontFamilyControl, globalFontSizeControl);
        }
        applyGlobalFormTypography();
    };
    globalFontFamilyControl?.addEventListener('change', onGlobalTypographyChange);
    globalFontSizeControl?.addEventListener('change', onGlobalTypographyChange);
    document.getElementById('wizardExportModeSelect')?.addEventListener('change', () => {
        refreshAllPreviewFieldValues();
        if (wizardState.selectedFieldKey) {
            syncSelectedFieldInputs();
        }
    });
    document.getElementById('insertManualFieldBtn')?.addEventListener('click', startManualFieldPlacementMode);
    
    async function loadDiagnostics(forceRefresh) {
        const content = document.getElementById('diagnosticsContent');
        const summary = document.getElementById('diagnosticsSummary');
        if (!content) return;
        
        if (summary) summary.textContent = forceRefresh ? 'Re-checking environment...' : 'Detecting environment...';
        content.innerHTML = '<p class="diag-error" style="color:#6c757d;">Checking requirements...</p>';
        
        try {
            const response = await fetch('?route=actions/universal-diagnostics', { cache: 'no-store' });
            const raw = await response.text();
            let data;
            try {
                data = JSON.parse(raw);
            } catch (parseErr) {
                throw new Error(raw.trim() || 'Unable to parse diagnostics response.');
            }
            if (!response.ok || !data.success) {
                throw new Error(data.message || `Server returned status ${response.status}`);
            }
            
            const status = data.status || {};
            const rows = [];
            
            if (status.php) {
                rows.push(renderDiagItem('PHP Version', status.php.version || 'unknown', !!status.php.version_ok, `SAPI: ${status.php.sapi || 'n/a'}`));
            }
            if (status.node) {
                rows.push(renderDiagItem(
                    'Node.js',
                    status.node.version || status.node.path || 'not detected',
                    !!status.node.available,
                    status.node.path ? `Path: ${status.node.path}` : 'Required for ensemble extraction'
                ));
            }
            if (status.qpdf) {
                rows.push(renderDiagItem(
                    'qpdf',
                    status.qpdf.available ? 'Detected' : 'Missing',
                    !!status.qpdf.available,
                    status.qpdf.available ? 'PDF decryption enabled' : 'Required to open encrypted PDFs'
                ));
            }
            if (status.paths) {
                Object.keys(status.paths).forEach(label => {
                    const info = status.paths[label];
                    rows.push(renderDiagItem(
                        `${label.charAt(0).toUpperCase() + label.slice(1)} dir`,
                        info.path || 'n/a',
                        !!info.writable,
                        info.writable ? 'Writable' : 'Needs write permission'
                    ));
                });
            }
            if (status.extensions) {
                status.extensions.forEach(ext => {
                    rows.push(renderDiagItem(
                        `PHP extension: ${ext.name}`,
                        ext.loaded ? 'Loaded' : 'Missing',
                        !!ext.loaded
                    ));
                });
            }
            if (status.functions) {
                status.functions.forEach(fn => {
                    rows.push(renderDiagItem(
                        `PHP function: ${fn.name}()`,
                        fn.enabled ? 'Enabled' : 'Disabled',
                        !!fn.enabled,
                        fn.enabled ? '' : 'Enable in php.ini (disable_functions)'
                    ));
                });
            }
            
            content.innerHTML = rows.join('');
            if (summary) {
                if (data.requirements && data.requirements.length) {
                    summary.innerHTML = `<span class="status-pill fail">Issues detected</span> ${escapeHtml(data.requirements.join(' • '))}`;
                } else {
                    summary.innerHTML = `<span class="status-pill ok">All good</span> Environment looks ready.`;
                }
            }
        } catch (err) {
            if (summary) summary.textContent = 'Unable to check requirements.';
            const message = typeof err === 'object' ? err.message : String(err);
            content.innerHTML = `<p class="diag-error">Diagnostics error: ${escapeHtml(message)}</p>`;
        }
    }
    
    function renderDiagItem(label, value, ok, description) {
        return `
            <div class="diag-item ${ok ? 'ok' : 'fail'}">
                <div class="diag-label">${escapeHtml(label)}</div>
                <div class="diag-value">${escapeHtml(value || 'unknown')}</div>
                <span class="status-pill ${ok ? 'ok' : 'fail'}">${ok ? 'OK' : 'Issue'}</span>
                ${description ? `<div class="diag-desc">${escapeHtml(description)}</div>` : ''}
            </div>
        `;
    }
    
    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = String(str);
        return div.innerHTML;
    }
});
</script>
