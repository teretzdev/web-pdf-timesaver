<?php
/**
 * Panel Editor View - Clio-style workflow for editing form panels
 * Provides drag-and-drop panel management, field editing, and live preview
 */

$template = $template ?? [];
$templateId = $templateId ?? '';
$projectDocumentId = $_GET['pd'] ?? '';
?>

<div class="panel-editor-container">
    <!-- Header with actions -->
    <div class="editor-header">
        <div class="header-left">
            <h2>
                <span class="icon">✏️</span>
                Panel Editor — <?php echo htmlspecialchars($template['code'] ?? ''); ?> <?php echo htmlspecialchars($template['name'] ?? ''); ?>
            </h2>
            <div class="breadcrumb">
                <?php if ($projectDocumentId): ?>
                    <a href="?route=populate&pd=<?php echo htmlspecialchars($projectDocumentId); ?>">← Back to Form</a>
                <?php else: ?>
                    <a href="?route=templates">← Back to Templates</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="header-actions">
            <button id="save-panels-btn" class="btn btn-primary">
                <span class="icon">💾</span> Save Changes
            </button>
            <button id="preview-form-btn" class="btn btn-secondary">
                <span class="icon">👁️</span> Preview
            </button>
            <button id="reset-panels-btn" class="btn btn-danger">
                <span class="icon">↺</span> Reset
            </button>
        </div>
    </div>

    <!-- Main editor layout -->
    <div class="editor-layout">
        <!-- Left sidebar - Panel list -->
        <div class="panel-sidebar">
            <div class="sidebar-header">
                <h3>Panels</h3>
                <button id="add-panel-btn" class="btn btn-sm btn-primary">
                    + Add Panel
                </button>
            </div>
            <div id="panel-list" class="panel-list">
                <?php foreach ($template['panels'] ?? [] as $panel): ?>
                    <div class="panel-item" data-panel-id="<?php echo htmlspecialchars($panel['id']); ?>">
                        <div class="panel-drag-handle">⋮⋮</div>
                        <div class="panel-info">
                            <span class="panel-name"><?php echo htmlspecialchars($panel['label']); ?></span>
                            <span class="field-count">
                                <?php 
                                $fieldCount = 0;
                                foreach ($template['fields'] ?? [] as $field) {
                                    if (($field['panelId'] ?? '') === $panel['id']) {
                                        $fieldCount++;
                                    }
                                }
                                echo $fieldCount . ' fields';
                                ?>
                            </span>
                        </div>
                        <div class="panel-actions">
                            <button class="edit-panel-btn" title="Edit panel">✏️</button>
                            <button class="delete-panel-btn" title="Delete panel">🗑️</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Center - Field editor -->
        <div class="field-editor">
            <div class="editor-tabs">
                <button class="tab-btn active" data-tab="fields">Fields</button>
                <button class="tab-btn" data-tab="properties">Properties</button>
                <button class="tab-btn" data-tab="validation">Validation</button>
                <button class="tab-btn" data-tab="mapping">PDF Mapping</button>
            </div>

            <!-- Fields tab -->
            <div id="fields-tab" class="tab-content active">
                <div class="fields-toolbar">
                    <h3>Panel Fields</h3>
                    <div class="field-actions">
                        <button id="add-field-btn" class="btn btn-sm btn-primary">+ Add Field</button>
                        <button id="import-fields-btn" class="btn btn-sm btn-secondary">↓ Import</button>
                        <button id="bulk-edit-btn" class="btn btn-sm btn-secondary">⚙️ Bulk Edit</button>
                    </div>
                </div>
                
                <div id="fields-list" class="fields-list">
                    <!-- Fields will be dynamically loaded here -->
                </div>
            </div>

            <!-- Properties tab -->
            <div id="properties-tab" class="tab-content">
                <div class="properties-form">
                    <h3>Panel Properties</h3>
                    <div class="form-group">
                        <label>Panel ID</label>
                        <input type="text" id="panel-id" class="form-control" readonly>
                    </div>
                    <div class="form-group">
                        <label>Panel Label</label>
                        <input type="text" id="panel-label" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Display Order</label>
                        <input type="number" id="panel-order" class="form-control" min="0">
                    </div>
                    <div class="form-group">
                        <label>Visibility Condition</label>
                        <select id="panel-visibility" class="form-control">
                            <option value="always">Always visible</option>
                            <option value="conditional">Conditional</option>
                            <option value="hidden">Hidden</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="panel-collapsible">
                            Collapsible panel
                        </label>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="panel-required">
                            Required panel
                        </label>
                    </div>
                </div>
            </div>

            <!-- Validation tab -->
            <div id="validation-tab" class="tab-content">
                <div class="validation-rules">
                    <h3>Validation Rules</h3>
                    <div id="validation-rules-list">
                        <!-- Validation rules will be loaded here -->
                    </div>
                    <button id="add-validation-rule" class="btn btn-sm btn-primary">+ Add Rule</button>
                </div>
            </div>

            <!-- PDF Mapping tab -->
            <div id="mapping-tab" class="tab-content">
                <div class="pdf-mapping">
                    <h3>PDF Field Mapping</h3>
                    <div class="mapping-info">
                        <p>Map form fields to PDF positions or form fields.</p>
                    </div>
                    <div id="pdf-mappings-list">
                        <!-- PDF mappings will be loaded here -->
                    </div>
                    <button id="auto-map-fields" class="btn btn-sm btn-primary">🔄 Auto-Map Fields</button>
                </div>
            </div>
        </div>

        <!-- Right sidebar - Properties panel -->
        <div class="properties-sidebar">
            <div class="sidebar-header">
                <h3>Field Properties</h3>
            </div>
            <div id="field-properties" class="field-properties">
                <div class="empty-state">
                    Select a field to view its properties
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Field Editor Modal -->
<div id="field-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="field-modal-title">Add Field</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <form id="field-form">
                <div class="form-row">
                    <div class="form-group">
                        <label>Field Key *</label>
                        <input type="text" id="field-key" class="form-control" required>
                        <small>Unique identifier for the field</small>
                    </div>
                    <div class="form-group">
                        <label>Field Label *</label>
                        <input type="text" id="field-label" class="form-control" required>
                        <small>Display label for the field</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Field Type *</label>
                        <select id="field-type" class="form-control" required>
                            <option value="text">Text</option>
                            <option value="textarea">Textarea</option>
                            <option value="number">Number</option>
                            <option value="date">Date</option>
                            <option value="select">Dropdown</option>
                            <option value="checkbox">Checkbox</option>
                            <option value="radio">Radio buttons</option>
                            <option value="file">File upload</option>
                            <option value="signature">Signature</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Placeholder</label>
                        <input type="text" id="field-placeholder" class="form-control">
                    </div>
                </div>

                <div class="form-row" id="field-options-row" style="display: none;">
                    <div class="form-group full-width">
                        <label>Options (one per line)</label>
                        <textarea id="field-options" class="form-control" rows="4"></textarea>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="field-required">
                            Required field
                        </label>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="field-readonly">
                            Read-only field
                        </label>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group full-width">
                        <label>Default Value</label>
                        <input type="text" id="field-default" class="form-control">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group full-width">
                        <label>Validation Pattern (regex)</label>
                        <input type="text" id="field-pattern" class="form-control">
                        <small>e.g., ^\d{3}-\d{3}-\d{4}$ for phone numbers</small>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button id="save-field-btn" class="btn btn-primary">Save Field</button>
            <button class="btn btn-secondary modal-close">Cancel</button>
        </div>
    </div>
</div>

<!-- Panel Editor Modal -->
<div id="panel-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="panel-modal-title">Add Panel</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <form id="panel-form">
                <div class="form-group">
                    <label>Panel ID *</label>
                    <input type="text" id="new-panel-id" class="form-control" required>
                    <small>Unique identifier (lowercase, no spaces)</small>
                </div>
                <div class="form-group">
                    <label>Panel Label *</label>
                    <input type="text" id="new-panel-label" class="form-control" required>
                    <small>Display name for the panel</small>
                </div>
                <div class="form-group">
                    <label>Panel Order</label>
                    <input type="number" id="new-panel-order" class="form-control" min="0" value="0">
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button id="save-panel-btn" class="btn btn-primary">Save Panel</button>
            <button class="btn btn-secondary modal-close">Cancel</button>
        </div>
    </div>
</div>

<style>
/* Panel Editor Styles */
.panel-editor-container {
    display: flex;
    flex-direction: column;
    height: calc(100vh - 60px);
    background: #f5f7fa;
}

.editor-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    background: white;
    border-bottom: 1px solid #e1e5e9;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.header-left h2 {
    margin: 0;
    color: #2c3e50;
    display: flex;
    align-items: center;
    gap: 10px;
}

.header-left .icon {
    font-size: 24px;
}

.breadcrumb {
    margin-top: 8px;
}

.breadcrumb a {
    color: #0b6bcb;
    text-decoration: none;
    font-size: 14px;
}

.header-actions {
    display: flex;
    gap: 10px;
}

.editor-layout {
    flex: 1;
    display: flex;
    overflow: hidden;
}

/* Panel Sidebar */
.panel-sidebar {
    width: 280px;
    background: white;
    border-right: 1px solid #e1e5e9;
    display: flex;
    flex-direction: column;
}

.sidebar-header {
    padding: 15px;
    border-bottom: 1px solid #e1e5e9;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.sidebar-header h3 {
    margin: 0;
    font-size: 16px;
    color: #2c3e50;
}

.panel-list {
    flex: 1;
    overflow-y: auto;
    padding: 10px;
}

.panel-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px;
    margin-bottom: 8px;
    background: #f8f9fa;
    border: 1px solid #e1e5e9;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s;
}

.panel-item:hover {
    background: #e6f3ff;
    border-color: #0b6bcb;
}

.panel-item.active {
    background: #e6f3ff;
    border-color: #0b6bcb;
    box-shadow: 0 2px 4px rgba(11,107,203,0.2);
}

.panel-drag-handle {
    cursor: grab;
    color: #6c757d;
    font-size: 14px;
    user-select: none;
}

.panel-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.panel-name {
    font-weight: 500;
    color: #2c3e50;
}

.field-count {
    font-size: 12px;
    color: #6c757d;
}

.panel-actions {
    display: flex;
    gap: 5px;
}

.panel-actions button {
    background: none;
    border: none;
    cursor: pointer;
    padding: 4px;
    font-size: 14px;
    opacity: 0.7;
    transition: opacity 0.2s;
}

.panel-actions button:hover {
    opacity: 1;
}

/* Field Editor */
.field-editor {
    flex: 1;
    display: flex;
    flex-direction: column;
    background: white;
}

.editor-tabs {
    display: flex;
    border-bottom: 2px solid #e1e5e9;
    padding: 0 20px;
}

.tab-btn {
    background: none;
    border: none;
    padding: 15px 20px;
    font-size: 14px;
    font-weight: 500;
    color: #6c757d;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    transition: all 0.2s;
}

.tab-btn:hover {
    color: #2c3e50;
}

.tab-btn.active {
    color: #0b6bcb;
    border-bottom-color: #0b6bcb;
}

.tab-content {
    display: none;
    flex: 1;
    padding: 20px;
    overflow-y: auto;
}

.tab-content.active {
    display: block;
}

.fields-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.fields-toolbar h3 {
    margin: 0;
    color: #2c3e50;
}

.field-actions {
    display: flex;
    gap: 10px;
}

.fields-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.field-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 15px;
    background: #f8f9fa;
    border: 1px solid #e1e5e9;
    border-radius: 6px;
    transition: all 0.2s;
}

.field-item:hover {
    background: #f0f8ff;
    border-color: #0b6bcb;
}

.field-item.selected {
    background: #e6f3ff;
    border-color: #0b6bcb;
    box-shadow: 0 2px 4px rgba(11,107,203,0.2);
}

.field-drag-handle {
    cursor: grab;
    color: #6c757d;
    font-size: 14px;
    user-select: none;
}

.field-info {
    flex: 1;
    display: grid;
    grid-template-columns: 1fr 1fr 100px;
    gap: 15px;
    align-items: center;
}

.field-key {
    font-family: monospace;
    font-size: 13px;
    color: #495057;
    background: white;
    padding: 4px 8px;
    border-radius: 4px;
}

.field-label {
    font-weight: 500;
    color: #2c3e50;
}

.field-type {
    font-size: 13px;
    color: #6c757d;
    background: #e9ecef;
    padding: 4px 8px;
    border-radius: 4px;
    text-align: center;
}

.field-item-actions {
    display: flex;
    gap: 5px;
}

/* Properties Sidebar */
.properties-sidebar {
    width: 320px;
    background: white;
    border-left: 1px solid #e1e5e9;
    display: flex;
    flex-direction: column;
}

.field-properties {
    flex: 1;
    padding: 20px;
    overflow-y: auto;
}

.empty-state {
    text-align: center;
    color: #6c757d;
    padding: 40px 20px;
}

/* Form Styles */
.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    font-size: 13px;
    font-weight: 500;
    color: #495057;
    margin-bottom: 5px;
}

.form-control {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #e1e5e9;
    border-radius: 4px;
    font-size: 14px;
    transition: all 0.2s;
}

.form-control:focus {
    outline: none;
    border-color: #0b6bcb;
    box-shadow: 0 0 0 3px rgba(11,107,203,0.1);
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

/* Modal Styles */
.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-content {
    background: white;
    border-radius: 8px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    max-width: 600px;
    width: 90%;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #e1e5e9;
}

.modal-header h3 {
    margin: 0;
    color: #2c3e50;
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    color: #6c757d;
    cursor: pointer;
}

.modal-body {
    flex: 1;
    padding: 20px;
    overflow-y: auto;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 20px;
    border-top: 1px solid #e1e5e9;
}

/* Button Styles */
.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.btn-primary {
    background: #0b6bcb;
    color: white;
}

.btn-primary:hover {
    background: #0954a5;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
}

.btn-danger {
    background: #dc3545;
    color: white;
}

.btn-danger:hover {
    background: #c82333;
}

.btn-sm {
    padding: 4px 8px;
    font-size: 12px;
}

/* Drag and Drop Styles */
.dragging {
    opacity: 0.5;
}

.drag-over {
    border-color: #0b6bcb;
    background: #f0f8ff;
}

/* Responsive adjustments */
@media (max-width: 1200px) {
    .properties-sidebar {
        display: none;
    }
}

@media (max-width: 768px) {
    .editor-layout {
        flex-direction: column;
    }
    
    .panel-sidebar {
        width: 100%;
        height: 200px;
        border-right: none;
        border-bottom: 1px solid #e1e5e9;
    }
}
</style>

<script>
// Panel Editor JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Template data
    const templateId = '<?php echo htmlspecialchars($templateId); ?>';
    const projectDocumentId = '<?php echo htmlspecialchars($projectDocumentId ?? ''); ?>';
    let currentTemplate = <?php echo json_encode($template); ?>;
    let selectedPanelId = null;
    let selectedFieldKey = null;
    let editingField = null;
    let editingPanel = null;

    // Initialize the first panel as selected
    const firstPanel = document.querySelector('.panel-item');
    if (firstPanel) {
        firstPanel.classList.add('active');
        selectedPanelId = firstPanel.dataset.panelId;
        loadPanelFields(selectedPanelId);
    }

    // Panel selection
    document.addEventListener('click', function(e) {
        if (e.target.closest('.panel-item') && !e.target.closest('.panel-actions')) {
            const panelItem = e.target.closest('.panel-item');
            document.querySelectorAll('.panel-item').forEach(p => p.classList.remove('active'));
            panelItem.classList.add('active');
            selectedPanelId = panelItem.dataset.panelId;
            loadPanelFields(selectedPanelId);
            loadPanelProperties(selectedPanelId);
        }
    });

    // Tab switching
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const tab = this.dataset.tab;
            document.querySelectorAll('.tab-btn').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            this.classList.add('active');
            document.getElementById(tab + '-tab').classList.add('active');
        });
    });

    // Add panel button
    document.getElementById('add-panel-btn').addEventListener('click', function() {
        editingPanel = null;
        document.getElementById('panel-modal-title').textContent = 'Add Panel';
        document.getElementById('new-panel-id').value = '';
        document.getElementById('new-panel-label').value = '';
        document.getElementById('new-panel-order').value = currentTemplate.panels ? currentTemplate.panels.length : 0;
        document.getElementById('panel-modal').style.display = 'flex';
    });

    // Save panel
    document.getElementById('save-panel-btn').addEventListener('click', function() {
        const panelId = document.getElementById('new-panel-id').value;
        const panelLabel = document.getElementById('new-panel-label').value;
        const panelOrder = parseInt(document.getElementById('new-panel-order').value) || 0;

        if (!panelId || !panelLabel) {
            alert('Please fill in all required fields');
            return;
        }

        if (!currentTemplate.panels) {
            currentTemplate.panels = [];
        }

        if (editingPanel) {
            // Update existing panel
            const panel = currentTemplate.panels.find(p => p.id === editingPanel);
            if (panel) {
                panel.label = panelLabel;
                panel.order = panelOrder;
            }
        } else {
            // Add new panel
            currentTemplate.panels.push({
                id: panelId,
                label: panelLabel,
                order: panelOrder
            });
        }

        document.getElementById('panel-modal').style.display = 'none';
        refreshPanelList();
    });

    // Edit panel
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('edit-panel-btn')) {
            const panelItem = e.target.closest('.panel-item');
            const panelId = panelItem.dataset.panelId;
            const panel = currentTemplate.panels.find(p => p.id === panelId);
            
            if (panel) {
                editingPanel = panelId;
                document.getElementById('panel-modal-title').textContent = 'Edit Panel';
                document.getElementById('new-panel-id').value = panel.id;
                document.getElementById('new-panel-id').readOnly = true;
                document.getElementById('new-panel-label').value = panel.label;
                document.getElementById('new-panel-order').value = panel.order || 0;
                document.getElementById('panel-modal').style.display = 'flex';
            }
        }
    });

    // Delete panel
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('delete-panel-btn')) {
            if (!confirm('Are you sure you want to delete this panel? All fields in this panel will be removed.')) {
                return;
            }

            const panelItem = e.target.closest('.panel-item');
            const panelId = panelItem.dataset.panelId;
            
            // Remove panel
            currentTemplate.panels = currentTemplate.panels.filter(p => p.id !== panelId);
            
            // Remove fields in this panel
            currentTemplate.fields = currentTemplate.fields.filter(f => f.panelId !== panelId);
            
            refreshPanelList();
        }
    });

    // Add field button
    document.getElementById('add-field-btn').addEventListener('click', function() {
        if (!selectedPanelId) {
            alert('Please select a panel first');
            return;
        }

        editingField = null;
        document.getElementById('field-modal-title').textContent = 'Add Field';
        document.getElementById('field-form').reset();
        document.getElementById('field-modal').style.display = 'flex';
    });

    // Field type change
    document.getElementById('field-type').addEventListener('change', function() {
        const optionsRow = document.getElementById('field-options-row');
        if (['select', 'radio'].includes(this.value)) {
            optionsRow.style.display = 'block';
        } else {
            optionsRow.style.display = 'none';
        }
    });

    // Save field
    document.getElementById('save-field-btn').addEventListener('click', function() {
        const fieldData = {
            key: document.getElementById('field-key').value,
            label: document.getElementById('field-label').value,
            type: document.getElementById('field-type').value,
            panelId: selectedPanelId,
            placeholder: document.getElementById('field-placeholder').value,
            required: document.getElementById('field-required').checked,
            readonly: document.getElementById('field-readonly').checked,
            default: document.getElementById('field-default').value,
            pattern: document.getElementById('field-pattern').value
        };

        // Handle options for select/radio fields
        if (['select', 'radio'].includes(fieldData.type)) {
            const optionsText = document.getElementById('field-options').value;
            if (optionsText) {
                fieldData.options = optionsText.split('\n').filter(o => o.trim());
            }
        }

        if (!fieldData.key || !fieldData.label) {
            alert('Please fill in all required fields');
            return;
        }

        if (!currentTemplate.fields) {
            currentTemplate.fields = [];
        }

        if (editingField) {
            // Update existing field
            const fieldIndex = currentTemplate.fields.findIndex(f => f.key === editingField);
            if (fieldIndex !== -1) {
                currentTemplate.fields[fieldIndex] = fieldData;
            }
        } else {
            // Add new field
            currentTemplate.fields.push(fieldData);
        }

        document.getElementById('field-modal').style.display = 'none';
        loadPanelFields(selectedPanelId);
        refreshPanelFieldCounts();
    });

    // Modal close buttons
    document.querySelectorAll('.modal-close').forEach(btn => {
        btn.addEventListener('click', function() {
            this.closest('.modal').style.display = 'none';
        });
    });

    // Close modal on outside click
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
            }
        });
    });

    // Save panels button
    document.getElementById('save-panels-btn').addEventListener('click', function() {
        savePanelConfiguration();
    });

    // Preview form button
    document.getElementById('preview-form-btn').addEventListener('click', function() {
        previewForm();
    });

    // Reset panels button
    document.getElementById('reset-panels-btn').addEventListener('click', function() {
        if (confirm('Are you sure you want to reset all panels? This will discard all unsaved changes.')) {
            location.reload();
        }
    });

    // Helper functions
    function loadPanelFields(panelId) {
        const fieldsContainer = document.getElementById('fields-list');
        const fields = currentTemplate.fields ? currentTemplate.fields.filter(f => f.panelId === panelId) : [];
        
        if (fields.length === 0) {
            fieldsContainer.innerHTML = '<div class="empty-state">No fields in this panel. Click "Add Field" to create one.</div>';
            return;
        }

        fieldsContainer.innerHTML = fields.map(field => `
            <div class="field-item" data-field-key="${field.key}">
                <div class="field-drag-handle">⋮⋮</div>
                <div class="field-info">
                    <div class="field-key">${field.key}</div>
                    <div class="field-label">${field.label}</div>
                    <div class="field-type">${field.type}</div>
                </div>
                <div class="field-item-actions">
                    <button class="edit-field-btn" title="Edit field">✏️</button>
                    <button class="duplicate-field-btn" title="Duplicate field">📋</button>
                    <button class="delete-field-btn" title="Delete field">🗑️</button>
                </div>
            </div>
        `).join('');

        // Add event listeners for field actions
        fieldsContainer.querySelectorAll('.field-item').forEach(item => {
            item.addEventListener('click', function(e) {
                if (!e.target.closest('.field-item-actions')) {
                    selectField(this.dataset.fieldKey);
                }
            });
        });

        // Edit field
        fieldsContainer.querySelectorAll('.edit-field-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                const fieldKey = this.closest('.field-item').dataset.fieldKey;
                editField(fieldKey);
            });
        });

        // Duplicate field
        fieldsContainer.querySelectorAll('.duplicate-field-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                const fieldKey = this.closest('.field-item').dataset.fieldKey;
                duplicateField(fieldKey);
            });
        });

        // Delete field
        fieldsContainer.querySelectorAll('.delete-field-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                if (confirm('Are you sure you want to delete this field?')) {
                    const fieldKey = this.closest('.field-item').dataset.fieldKey;
                    deleteField(fieldKey);
                }
            });
        });
    }

    function loadPanelProperties(panelId) {
        const panel = currentTemplate.panels.find(p => p.id === panelId);
        if (!panel) return;

        document.getElementById('panel-id').value = panel.id;
        document.getElementById('panel-label').value = panel.label;
        document.getElementById('panel-order').value = panel.order || 0;
        document.getElementById('panel-visibility').value = panel.visibility || 'always';
        document.getElementById('panel-collapsible').checked = panel.collapsible || false;
        document.getElementById('panel-required').checked = panel.required || false;
    }

    function selectField(fieldKey) {
        document.querySelectorAll('.field-item').forEach(item => {
            item.classList.remove('selected');
        });
        
        const fieldItem = document.querySelector(`[data-field-key="${fieldKey}"]`);
        if (fieldItem) {
            fieldItem.classList.add('selected');
            selectedFieldKey = fieldKey;
            loadFieldProperties(fieldKey);
        }
    }

    function loadFieldProperties(fieldKey) {
        const field = currentTemplate.fields.find(f => f.key === fieldKey);
        if (!field) return;

        const propertiesContainer = document.getElementById('field-properties');
        propertiesContainer.innerHTML = `
            <div class="property-group">
                <label>Field Key</label>
                <input type="text" class="form-control" value="${field.key}" readonly>
            </div>
            <div class="property-group">
                <label>Field Label</label>
                <input type="text" class="form-control" value="${field.label}" id="prop-label">
            </div>
            <div class="property-group">
                <label>Field Type</label>
                <select class="form-control" id="prop-type">
                    <option value="text" ${field.type === 'text' ? 'selected' : ''}>Text</option>
                    <option value="textarea" ${field.type === 'textarea' ? 'selected' : ''}>Textarea</option>
                    <option value="number" ${field.type === 'number' ? 'selected' : ''}>Number</option>
                    <option value="date" ${field.type === 'date' ? 'selected' : ''}>Date</option>
                    <option value="select" ${field.type === 'select' ? 'selected' : ''}>Dropdown</option>
                    <option value="checkbox" ${field.type === 'checkbox' ? 'selected' : ''}>Checkbox</option>
                </select>
            </div>
            <div class="property-group">
                <label>Placeholder</label>
                <input type="text" class="form-control" value="${field.placeholder || ''}" id="prop-placeholder">
            </div>
            <div class="property-group">
                <label>
                    <input type="checkbox" id="prop-required" ${field.required ? 'checked' : ''}>
                    Required field
                </label>
            </div>
            <div class="property-group">
                <label>
                    <input type="checkbox" id="prop-readonly" ${field.readonly ? 'checked' : ''}>
                    Read-only field
                </label>
            </div>
            <div class="property-group">
                <button class="btn btn-primary btn-sm" onclick="saveFieldProperties('${fieldKey}')">
                    Save Properties
                </button>
            </div>
        `;
    }

    window.saveFieldProperties = function(fieldKey) {
        const field = currentTemplate.fields.find(f => f.key === fieldKey);
        if (!field) return;

        field.label = document.getElementById('prop-label').value;
        field.type = document.getElementById('prop-type').value;
        field.placeholder = document.getElementById('prop-placeholder').value;
        field.required = document.getElementById('prop-required').checked;
        field.readonly = document.getElementById('prop-readonly').checked;

        loadPanelFields(selectedPanelId);
        alert('Field properties saved');
    };

    function editField(fieldKey) {
        const field = currentTemplate.fields.find(f => f.key === fieldKey);
        if (!field) return;

        editingField = fieldKey;
        document.getElementById('field-modal-title').textContent = 'Edit Field';
        document.getElementById('field-key').value = field.key;
        document.getElementById('field-key').readOnly = true;
        document.getElementById('field-label').value = field.label;
        document.getElementById('field-type').value = field.type;
        document.getElementById('field-placeholder').value = field.placeholder || '';
        document.getElementById('field-required').checked = field.required || false;
        document.getElementById('field-readonly').checked = field.readonly || false;
        document.getElementById('field-default').value = field.default || '';
        document.getElementById('field-pattern').value = field.pattern || '';
        
        if (field.options) {
            document.getElementById('field-options').value = field.options.join('\n');
            document.getElementById('field-options-row').style.display = 'block';
        }

        document.getElementById('field-modal').style.display = 'flex';
    }

    function duplicateField(fieldKey) {
        const field = currentTemplate.fields.find(f => f.key === fieldKey);
        if (!field) return;

        const newField = {...field};
        newField.key = field.key + '_copy';
        newField.label = field.label + ' (Copy)';
        
        currentTemplate.fields.push(newField);
        loadPanelFields(selectedPanelId);
        refreshPanelFieldCounts();
    }

    function deleteField(fieldKey) {
        currentTemplate.fields = currentTemplate.fields.filter(f => f.key !== fieldKey);
        loadPanelFields(selectedPanelId);
        refreshPanelFieldCounts();
    }

    function refreshPanelList() {
        const panelList = document.getElementById('panel-list');
        panelList.innerHTML = '';
        
        if (currentTemplate.panels) {
            currentTemplate.panels.sort((a, b) => (a.order || 0) - (b.order || 0));
            currentTemplate.panels.forEach(panel => {
                const fieldCount = currentTemplate.fields ? 
                    currentTemplate.fields.filter(f => f.panelId === panel.id).length : 0;
                
                const panelItem = document.createElement('div');
                panelItem.className = 'panel-item';
                panelItem.dataset.panelId = panel.id;
                if (panel.id === selectedPanelId) {
                    panelItem.classList.add('active');
                }
                
                panelItem.innerHTML = `
                    <div class="panel-drag-handle">⋮⋮</div>
                    <div class="panel-info">
                        <span class="panel-name">${panel.label}</span>
                        <span class="field-count">${fieldCount} fields</span>
                    </div>
                    <div class="panel-actions">
                        <button class="edit-panel-btn" title="Edit panel">✏️</button>
                        <button class="delete-panel-btn" title="Delete panel">🗑️</button>
                    </div>
                `;
                
                panelList.appendChild(panelItem);
            });
        }
    }

    function refreshPanelFieldCounts() {
        document.querySelectorAll('.panel-item').forEach(item => {
            const panelId = item.dataset.panelId;
            const fieldCount = currentTemplate.fields ? 
                currentTemplate.fields.filter(f => f.panelId === panelId).length : 0;
            const countSpan = item.querySelector('.field-count');
            if (countSpan) {
                countSpan.textContent = fieldCount + ' fields';
            }
        });
    }

    function savePanelConfiguration() {
        const data = new FormData();
        data.append('templateId', templateId);
        data.append('configuration', JSON.stringify(currentTemplate));
        
        fetch('?route=actions/save-panel-configuration', {
            method: 'POST',
            body: data
        }).then(response => response.json())
        .then(result => {
            if (result.success) {
                alert('Panel configuration saved successfully!');
            } else {
                alert('Failed to save panel configuration');
            }
        }).catch(error => {
            console.error('Error saving panel configuration:', error);
            alert('Error saving panel configuration');
        });
    }

    function previewForm() {
        if (projectDocumentId) {
            window.open('?route=populate&pd=' + projectDocumentId + '&preview=1', '_blank');
        } else {
            window.open('?route=template-preview&id=' + templateId, '_blank');
        }
    }

    // Initialize drag and drop for panels
    initializePanelDragDrop();
    
    // Initialize drag and drop for fields
    initializeFieldDragDrop();
    
    function initializePanelDragDrop() {
        let draggedPanel = null;
        
        document.addEventListener('dragstart', function(e) {
            if (e.target.closest('.panel-drag-handle')) {
                draggedPanel = e.target.closest('.panel-item');
                draggedPanel.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
            }
        });
        
        document.addEventListener('dragend', function(e) {
            if (draggedPanel) {
                draggedPanel.classList.remove('dragging');
                draggedPanel = null;
                updatePanelOrder();
            }
        });
        
        document.addEventListener('dragover', function(e) {
            if (!draggedPanel) return;
            e.preventDefault();
            
            const panelList = document.getElementById('panel-list');
            const afterElement = getDragAfterElement(panelList, e.clientY);
            
            if (afterElement == null) {
                panelList.appendChild(draggedPanel);
            } else {
                panelList.insertBefore(draggedPanel, afterElement);
            }
        });
    }
    
    function initializeFieldDragDrop() {
        let draggedField = null;
        
        document.addEventListener('dragstart', function(e) {
            if (e.target.closest('.field-drag-handle')) {
                draggedField = e.target.closest('.field-item');
                draggedField.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
            }
        });
        
        document.addEventListener('dragend', function(e) {
            if (draggedField) {
                draggedField.classList.remove('dragging');
                draggedField = null;
                updateFieldOrder();
            }
        });
        
        document.addEventListener('dragover', function(e) {
            if (!draggedField) return;
            e.preventDefault();
            
            const fieldsList = document.getElementById('fields-list');
            const afterElement = getDragAfterElement(fieldsList, e.clientY);
            
            if (afterElement == null) {
                fieldsList.appendChild(draggedField);
            } else {
                fieldsList.insertBefore(draggedField, afterElement);
            }
        });
    }
    
    function getDragAfterElement(container, y) {
        const draggableElements = [...container.querySelectorAll(':not(.dragging)')];
        
        return draggableElements.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;
            
            if (offset < 0 && offset > closest.offset) {
                return { offset: offset, element: child };
            } else {
                return closest;
            }
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    }
    
    function updatePanelOrder() {
        const panelItems = document.querySelectorAll('.panel-item');
        panelItems.forEach((item, index) => {
            const panelId = item.dataset.panelId;
            const panel = currentTemplate.panels.find(p => p.id === panelId);
            if (panel) {
                panel.order = index;
            }
        });
    }
    
    function updateFieldOrder() {
        const fieldItems = document.querySelectorAll('.field-item');
        const orderedKeys = Array.from(fieldItems).map(item => item.dataset.fieldKey);
        
        // Reorder fields in the current panel
        const panelFields = currentTemplate.fields.filter(f => f.panelId === selectedPanelId);
        const otherFields = currentTemplate.fields.filter(f => f.panelId !== selectedPanelId);
        
        const reorderedPanelFields = [];
        orderedKeys.forEach(key => {
            const field = panelFields.find(f => f.key === key);
            if (field) {
                reorderedPanelFields.push(field);
            }
        });
        
        currentTemplate.fields = [...otherFields, ...reorderedPanelFields];
    }
});
</script>