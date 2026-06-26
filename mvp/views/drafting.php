<?php
// Drafting/Edit Interface - Visual PDF Editor with Header Bar
// This replaces the old preview.php and implements the full multi-stage workflow

$tpl = $template;
$pdId = htmlspecialchars($projectDocument['id']);
$projectId = htmlspecialchars($projectDocument['projectId']);

// Get client and project info for breadcrumbs
$project = $store->getProject($projectId);
$clientId = $project['clientId'] ?? '';
$clientName = 'Client';
$projectName = $project['name'] ?? 'Project';

if ($clientId && method_exists($store, 'getClient')) {
    $client = $store->getClient($clientId);
    if ($client) {
        $clientName = $client['displayName'] ?? 'Client';
    }
}

// Check if PDF exists
$pdfPath = null;
$pdfUrl = null;
$hasPdf = false;

if (!empty($projectDocument['outputPath'])) {
    $pdfPath = __DIR__ . '/../../output/' . $projectDocument['outputPath'];
    $basePath = function_exists('getBasePath') ? getBasePath() : '/';
    $pdfUrl = $basePath . 'output/' . $projectDocument['outputPath'];
    $hasPdf = file_exists($pdfPath);
}
?>

<!-- Drafting Header Bar -->
<div class="pdftimesaver-drafting-header">
    <div class="header-left">
        <a href="?route=populate&pd=<?= $pdId ?>" class="pdftimesaver-btn-secondary">← Back to populate</a>
        <button class="pdftimesaver-btn-secondary" id="insert-field-btn">Insert</button>
    </div>
    <div class="header-center">
        <nav class="breadcrumb">
            <?php if ($clientId): ?>
                <a href="?route=client&id=<?= htmlspecialchars($clientId) ?>"><?= htmlspecialchars($clientName) ?></a>
                <span>→</span>
            <?php endif; ?>
            <a href="?route=project&id=<?= $projectId ?>"><?= htmlspecialchars($projectName) ?></a>
            <span>→</span>
            <span class="breadcrumb-current"><?= htmlspecialchars(formatTemplateDisplayLabel($tpl, (string)($projectDocument['templateId'] ?? ''))) ?></span>
        </nav>
    </div>
    <div class="header-right">
        <select class="status-dropdown" id="project-status-select" data-project-id="<?= $projectId ?>">
            <option value="in_progress" <?= ($project['status'] ?? '') === 'in_progress' ? 'selected' : '' ?>>In progress</option>
            <option value="review" <?= ($project['status'] ?? '') === 'review' ? 'selected' : '' ?>>Review</option>
            <option value="completed" <?= ($project['status'] ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
        </select>
        <button class="pdftimesaver-btn" id="download-btn">Download</button>
        <button class="pdftimesaver-btn" id="sign-btn">Sign</button>
    </div>
</div>

<!-- Main Content Layout -->
<div class="drafting-layout">
    <!-- Document List Sidebar -->
    <div class="document-list-panel">
        <h3>Documents (<?= count($projectDocumentsWithTemplates) ?>)</h3>
        <button class="pdftimesaver-btn-secondary" id="add-remove-btn">Add/Remove</button>
        
        <ul class="document-list" id="document-list">
            <?php 
            foreach ($projectDocumentsWithTemplates as $item): 
                $doc = $item['doc'];
                $docTemplate = $item['template'];
                $isCurrentDoc = $doc['id'] === $projectDocument['id'];
                $templateName = formatTemplateDisplayLabel($docTemplate, (string)($doc['templateId'] ?? ''));
            ?>
                <li class="document-item <?= $isCurrentDoc ? 'active' : '' ?>" 
                    data-document-id="<?= htmlspecialchars($doc['id']) ?>">
                    <div class="document-info">
                        <div class="document-name"><?= htmlspecialchars($templateName) ?></div>
                        <div class="document-status">
                            <span class="status-indicator status-<?= str_replace('_', '-', $doc['status'] ?? 'in-progress') ?>">
                                <?= ucfirst(str_replace('_', ' ', $doc['status'] ?? 'in_progress')) ?>
                            </span>
                        </div>
                    </div>
                    <?php if (!$isCurrentDoc): ?>
                        <div class="document-actions">
                            <a href="?route=drafting&pd=<?= htmlspecialchars($doc['id']) ?>" 
                               class="action-link">Edit</a>
                        </div>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <!-- Main Content Area -->
    <div class="drafting-main-content">
        <!-- Workflow Instructions -->
        <div class="workflow-instructions">
            <p><strong>Document Drafting Interface</strong></p>
            <p>Use the 'Download' button to download/print your document.</p>
            <p>Use the 'Sign' button to sign the documents or send them out to collect signatures electronically.</p>
        </div>

        <!-- PDF Preview -->
        <div class="pdf-preview-container">
            <div class="pdf-container" id="pdf-container">
                <?php if ($hasPdf): ?>
                    <iframe 
                        id="pdf-iframe"
                        src="<?= htmlspecialchars($pdfUrl) ?>" 
                        title="PDF Preview"
                        style="border: none; width: 100%; height: 800px;">
                    </iframe>
                    
                    <!-- Field Overlay for Custom Fields -->
                    <div class="pdf-field-overlay" id="pdf-field-overlay">
                        <?php 
                        // Get custom fields for this template
                        $customFields = $customFieldManager->getCustomFields($projectDocument['templateId']);
                        foreach ($customFields as $field): 
                            $fieldValue = $values[$field['key']] ?? '';
                        ?>
                            <div class="pdf-field custom-field" 
                                 style="left: <?= $field['x'] ?>px; top: <?= $field['y'] ?>px; width: <?= $field['width'] ?>px; height: <?= $field['height'] ?>px;"
                                 data-field-key="<?= htmlspecialchars($field['key']) ?>"
                                 data-field-type="<?= htmlspecialchars($field['type']) ?>">
                                
                                <?php if ($field['type'] === 'textarea'): ?>
                                    <textarea class="pdf-field-input" 
                                              name="<?= htmlspecialchars($field['key']) ?>"
                                              placeholder="<?= htmlspecialchars($field['label']) ?>"
                                              onchange="updateFieldValue('<?= htmlspecialchars($field['key']) ?>', this.value)"><?= htmlspecialchars($fieldValue) ?></textarea>
                                <?php elseif ($field['type'] === 'checkbox'): ?>
                                    <input type="checkbox" 
                                           class="pdf-field-input" 
                                           name="<?= htmlspecialchars($field['key']) ?>"
                                           <?= $fieldValue ? 'checked' : '' ?>
                                           onchange="updateFieldValue('<?= htmlspecialchars($field['key']) ?>', this.checked ? '1' : '0')">
                                <?php elseif ($field['type'] === 'date'): ?>
                                    <input type="date" 
                                           class="pdf-field-input" 
                                           name="<?= htmlspecialchars($field['key']) ?>"
                                           value="<?= htmlspecialchars($fieldValue) ?>"
                                           onchange="updateFieldValue('<?= htmlspecialchars($field['key']) ?>', this.value)">
                                <?php elseif ($field['type'] === 'number'): ?>
                                    <input type="number" 
                                           class="pdf-field-input" 
                                           name="<?= htmlspecialchars($field['key']) ?>"
                                           value="<?= htmlspecialchars($fieldValue) ?>"
                                           onchange="updateFieldValue('<?= htmlspecialchars($field['key']) ?>', this.value)">
                                <?php else: ?>
                                    <input type="text" 
                                           class="pdf-field-input" 
                                           name="<?= htmlspecialchars($field['key']) ?>"
                                           value="<?= htmlspecialchars($fieldValue) ?>"
                                           placeholder="<?= htmlspecialchars($field['label']) ?>"
                                           onchange="updateFieldValue('<?= htmlspecialchars($field['key']) ?>', this.value)">
                                <?php endif; ?>
                                
                                <!-- Custom field indicator -->
                                <div class="custom-field-indicator" title="Custom Field">
                                    <span style="font-size: 10px; color: #007bff; font-weight: bold;">C</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; padding: 40px; text-align: center;">
                        <div style="font-size: 48px; margin-bottom: 16px;">📄</div>
                        <h3 style="margin: 0 0 8px 0; color: #6c757d; font-size: 18px;">No PDF Generated Yet</h3>
                        <p style="margin: 0 0 20px 0; color: #6c757d; font-size: 14px;">Generate a PDF to see the visual editor.</p>
                        <a href="?route=actions/generate&pd=<?= $pdId ?>" class="pdftimesaver-btn">Generate PDF</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Document Info -->
        <div class="pdftimesaver-card">
            <h3 style="margin: 0 0 16px 0; font-size: 16px; color: #2c3e50;">Document Details</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                <div>
                    <div style="font-size: 12px; color: #6c757d; margin-bottom: 4px;">Template</div>
                    <div style="font-weight: 600; color: #2c3e50;">
                        <?= htmlspecialchars(formatTemplateDisplayLabel($tpl, (string)($projectDocument['templateId'] ?? ''))) ?>
                    </div>
                </div>
                <div>
                    <div style="font-size: 12px; color: #6c757d; margin-bottom: 4px;">Status</div>
                    <div>
                        <span class="pdftimesaver-status pdftimesaver-status-<?= str_replace('_', '-', $projectDocument['status'] ?? 'in-progress') ?>">
                            <?= ucfirst(str_replace('_', ' ', $projectDocument['status'] ?? 'in_progress')) ?>
                        </span>
                    </div>
                </div>
                <?php if ($hasPdf): ?>
                    <div>
                        <div style="font-size: 12px; color: #6c757d; margin-bottom: 4px;">Generated</div>
                        <div style="font-size: 13px; color: #2c3e50;">
                            <?= date('M j, Y \a\t g:i A', filemtime($pdfPath)) ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Client Vault Sidebar -->
    <div class="client-vault-panel">
        <h3>Client Vault</h3>
        
        <div class="file-upload-zone" id="file-upload-zone">
            <p>To upload files drag them here</p>
            <a href="#" class="browse-link" id="browse-link">Browse</a>
            <input type="file" id="file-input" multiple accept=".pdf,.doc,.docx,.txt" style="display: none;">
        </div>

        <ul class="file-list" id="file-list">
            <!-- Files will be loaded here via AJAX -->
        </ul>
    </div>
</div>

<!-- Insert Custom Field Modal -->
<div id="insert-field-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Insert Custom Field</h3>
            <button class="modal-close" onclick="closeInsertModal()">&times;</button>
        </div>
        <form id="custom-field-form" onsubmit="addCustomField(event)">
            <div class="pdftimesaver-form-group">
                <label class="pdftimesaver-form-label">Field Label *</label>
                <input type="text" name="label" placeholder="Enter field label" required class="pdftimesaver-input" autofocus>
            </div>
            <div class="pdftimesaver-form-group">
                <label class="pdftimesaver-form-label">Field Type *</label>
                <select name="type" required class="pdftimesaver-input">
                    <option value="text">Text Input</option>
                    <option value="textarea">Text Area</option>
                    <option value="checkbox">Checkbox</option>
                    <option value="date">Date</option>
                    <option value="number">Number</option>
                </select>
            </div>
            <div class="button-group" style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 20px;">
                <button type="button" class="pdftimesaver-btn-secondary" onclick="closeInsertModal()">Cancel</button>
                <button type="submit" class="pdftimesaver-btn">Add Field</button>
            </div>
        </form>
    </div>
</div>


