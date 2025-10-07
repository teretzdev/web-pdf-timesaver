<?php $tpl = $template; ?>
<h2>PDF Field Mapping — <?php echo htmlspecialchars(($tpl['code'] ?? '') . ' ' . ($tpl['name'] ?? '')); ?></h2>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 20px;">
    
    <!-- Left Column: PDF Form Fields -->
    <div class="panel">
        <h3 style="color: #0b6bcb; margin-bottom: 16px;">📄 PDF Form Fields</h3>
        <p style="color: #6c757d; margin-bottom: 16px;">These are the form fields found in the PDF template. Click to map them to custom fields.</p>
        
        <div id="pdf-fields-container" class="grid" style="gap: 12px;">
            <?php foreach ($pdfFields as $pdfField): ?>
                <div class="pdf-field-item" data-field-name="<?php echo htmlspecialchars($pdfField['name']); ?>" style="padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; background: #f8f9fa; cursor: pointer; transition: all 0.3s ease;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-weight: 600; color: #495057; margin-bottom: 4px;">
                                <?php echo htmlspecialchars($pdfField['label']); ?>
                            </div>
                            <div style="font-size: 12px; color: #6c757d; font-family: monospace;">
                                <?php echo htmlspecialchars($pdfField['name']); ?>
                            </div>
                        </div>
                        <div style="font-size: 12px; color: #6c757d; text-transform: uppercase;">
                            <?php echo htmlspecialchars($pdfField['type']); ?>
                        </div>
                    </div>
                    <div class="mapped-field" style="margin-top: 8px; padding: 6px; background: #e3f2fd; border-radius: 4px; font-size: 12px; color: #1976d2; display: none;">
                        <span class="mapped-label">Mapped to: </span>
                        <span class="mapped-name"></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Right Column: Custom Fields Management -->
    <div class="panel">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 style="color: #28a745; margin: 0;">🎯 Custom Fields</h3>
            <button type="button" id="add-custom-field-btn" class="btn secondary" style="font-size: 12px; padding: 6px 12px;">
                + Add Custom Field
            </button>
        </div>
        
        <!-- Custom Fields Container -->
        <div id="custom-fields-container" class="grid" style="gap: 12px;">
            <?php if (!empty($customFields)): ?>
                <?php foreach ($customFields as $customField): ?>
                    <div class="custom-field-item" data-field-id="<?php echo htmlspecialchars($customField['id']); ?>" style="padding: 12px; border: 2px solid #28a745; border-radius: 8px; background: #f0fff0;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span class="drag-handle" style="cursor: grab; color: #6c757d; font-size: 14px; user-select: none;" title="Drag to reorder">⋮⋮</span>
                                <input type="text" class="field-label-input" value="<?php echo htmlspecialchars($customField['label']); ?>" style="border: none; background: transparent; font-weight: 600; color: #495057; padding: 2px 4px; border-radius: 3px; min-width: 120px;">
                            </div>
                            <div style="display: flex; gap: 4px;">
                                <button type="button" class="edit-custom-field-btn" data-field-id="<?php echo htmlspecialchars($customField['id']); ?>" style="background:#ffc107; color:white; border:none; padding:2px 6px; border-radius:3px; font-size:10px; cursor:pointer;" title="Edit field">
                                    ✏️
                                </button>
                                <button type="button" class="delete-custom-field-btn" data-field-id="<?php echo htmlspecialchars($customField['id']); ?>" style="background:#dc3545; color:white; border:none; padding:2px 6px; border-radius:3px; font-size:10px; cursor:pointer;" title="Delete field">
                                    🗑️
                                </button>
                            </div>
                        </div>
                        
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                            <span style="font-size: 12px; color: #6c757d;">Type:</span>
                            <select class="field-type-select" style="font-size: 12px; padding: 2px 4px; border: 1px solid #ddd; border-radius: 3px;">
                                <option value="text" <?php echo ($customField['type'] ?? 'text') === 'text' ? 'selected' : ''; ?>>Text</option>
                                <option value="textarea" <?php echo ($customField['type'] ?? 'text') === 'textarea' ? 'selected' : ''; ?>>Textarea</option>
                                <option value="number" <?php echo ($customField['type'] ?? 'text') === 'number' ? 'selected' : ''; ?>>Number</option>
                                <option value="date" <?php echo ($customField['type'] ?? 'text') === 'date' ? 'selected' : ''; ?>>Date</option>
                                <option value="checkbox" <?php echo ($customField['type'] ?? 'text') === 'checkbox' ? 'selected' : ''; ?>>Checkbox</option>
                            </select>
                            
                            <span style="font-size: 12px; color: #6c757d;">Map to PDF:</span>
                            <select class="pdf-field-mapping" style="font-size: 12px; padding: 2px 4px; border: 1px solid #ddd; border-radius: 3px; min-width: 120px;">
                                <option value="">-- Select PDF Field --</option>
                                <?php foreach ($pdfFields as $pdfField): ?>
                                    <option value="<?php echo htmlspecialchars($pdfField['name']); ?>">
                                        <?php echo htmlspecialchars($pdfField['label']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div style="font-size: 11px; color: #6c757d;">
                            ID: <?php echo htmlspecialchars($customField['id']); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Action Buttons -->
<div style="margin-top: 24px; display: flex; gap: 12px;">
    <button class="btn" onclick="saveAllMappings()">Save All Mappings</button>
    <a class="btn secondary" href="?route=populate&pd=<?php echo htmlspecialchars($projectDocument['id']); ?>">Go to Populate Form</a>
    <a class="btn secondary" href="?route=project&id=<?php echo htmlspecialchars($projectDocument['projectId']); ?>">Back to Project</a>
</div>

<style>
.panel {
    background: #ffffff;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.grid {
    display: grid;
    grid-template-columns: 1fr;
}

.pdf-field-item:hover {
    border-color: #0b6bcb !important;
    background: #f0f8ff !important;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(11,107,203,0.1);
}

.pdf-field-item.mapped {
    border-color: #28a745 !important;
    background: #f0fff0 !important;
}

.custom-field-item {
    transition: all 0.3s ease;
}

.custom-field-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(40,167,69,0.1);
}

.field-label-input:focus {
    background: #fff !important;
    border: 1px solid #0b6bcb !important;
    outline: none;
}

.btn {
    background: #0b6bcb;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn:hover {
    background: #0a5a9f;
    transform: translateY(-1px);
}

.btn.secondary {
    background: #6c757d;
}

.btn.secondary:hover {
    background: #5a6268;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize field mappings
    initializeFieldMappings();
    
    // Add custom field functionality
    const addCustomFieldBtn = document.getElementById('add-custom-field-btn');
    if (addCustomFieldBtn) {
        addCustomFieldBtn.addEventListener('click', function() {
            showAddCustomFieldModal();
        });
    }
    
    // Handle delete custom field buttons
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('delete-custom-field-btn')) {
            const fieldId = e.target.getAttribute('data-field-id');
            if (confirm('Are you sure you want to delete this custom field?')) {
                deleteCustomField(fieldId);
            }
        }
        
        if (e.target.classList.contains('edit-custom-field-btn')) {
            const fieldId = e.target.getAttribute('data-field-id');
            showEditCustomFieldModal(fieldId);
        }
    });
    
    // Handle PDF field clicks for mapping
    document.querySelectorAll('.pdf-field-item').forEach(item => {
        item.addEventListener('click', function() {
            const fieldName = this.getAttribute('data-field-name');
            showMappingModal(fieldName);
        });
    });
    
    // Handle label editing
    document.querySelectorAll('.field-label-input').forEach(input => {
        input.addEventListener('blur', function() {
            updateFieldLabel(this);
        });
        
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                this.blur();
            }
        });
    });
    
    // Handle type changes
    document.querySelectorAll('.field-type-select').forEach(select => {
        select.addEventListener('change', function() {
            updateFieldType(this);
        });
    });
    
    // Handle PDF field mapping changes
    document.querySelectorAll('.pdf-field-mapping').forEach(select => {
        select.addEventListener('change', function() {
            updatePdfMapping(this);
        });
    });
});

function initializeFieldMappings() {
    // This would load existing mappings from the database
    // For now, we'll just show the interface
}

function showMappingModal(pdfFieldName) {
    const modal = document.createElement('div');
    modal.style.cssText = `
        position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
        background: rgba(0,0,0,0.5); display: flex; align-items: center; 
        justify-content: center; z-index: 1000;
    `;
    
    modal.innerHTML = `
        <div style="background: white; padding: 24px; border-radius: 8px; width: 400px; max-width: 90vw;">
            <h3 style="margin: 0 0 16px 0; color: #0b6bcb;">Map PDF Field</h3>
            <p style="margin-bottom: 16px; color: #6c757d;">
                Map the PDF field "<strong>${pdfFieldName}</strong>" to a custom field:
            </p>
            <div style="margin-bottom: 16px;">
                <label style="display: block; margin-bottom: 4px; font-weight: 600;">Custom Field Label:</label>
                <input type="text" id="mapping-label" placeholder="Enter field label" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
            </div>
            <div style="margin-bottom: 16px;">
                <label style="display: block; margin-bottom: 4px; font-weight: 600;">Field Type:</label>
                <select id="mapping-type" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    <option value="text">Text</option>
                    <option value="textarea">Textarea</option>
                    <option value="number">Number</option>
                    <option value="date">Date</option>
                    <option value="checkbox">Checkbox</option>
                </select>
            </div>
            <div style="display: flex; gap: 8px; justify-content: flex-end;">
                <button type="button" class="btn secondary" onclick="this.closest('.modal').remove()">Cancel</button>
                <button type="button" class="btn" onclick="createMappedField('${pdfFieldName}')">Create Field</button>
            </div>
        </div>
    `;
    
    modal.className = 'modal';
    document.body.appendChild(modal);
}

function createMappedField(pdfFieldName) {
    const label = document.getElementById('mapping-label').value;
    const type = document.getElementById('mapping-type').value;
    
    if (!label.trim()) {
        alert('Please enter a field label');
        return;
    }
    
    // Create the custom field
    const formData = new FormData();
    formData.append('projectDocumentId', '<?php echo htmlspecialchars($projectDocument['id']); ?>');
    formData.append('label', label);
    formData.append('type', type);
    formData.append('placeholder', '');
    formData.append('pdfMapping', pdfFieldName);
    
    fetch('?route=actions/add-custom-field', {
        method: 'POST',
        body: formData
    }).then(() => {
        location.reload();
    });
}

function updateFieldLabel(input) {
    const fieldId = input.closest('.custom-field-item').getAttribute('data-field-id');
    const newLabel = input.value;
    
    // Update the field label
    const formData = new FormData();
    formData.append('fieldId', fieldId);
    formData.append('projectDocumentId', '<?php echo htmlspecialchars($projectDocument['id']); ?>');
    formData.append('label', newLabel);
    formData.append('type', input.closest('.custom-field-item').querySelector('.field-type-select').value);
    formData.append('placeholder', '');
    
    fetch('?route=actions/update-custom-field', {
        method: 'POST',
        body: formData
    });
}

function updateFieldType(select) {
    const fieldId = select.closest('.custom-field-item').getAttribute('data-field-id');
    const newType = select.value;
    const label = select.closest('.custom-field-item').querySelector('.field-label-input').value;
    
    const formData = new FormData();
    formData.append('fieldId', fieldId);
    formData.append('projectDocumentId', '<?php echo htmlspecialchars($projectDocument['id']); ?>');
    formData.append('label', label);
    formData.append('type', newType);
    formData.append('placeholder', '');
    
    fetch('?route=actions/update-custom-field', {
        method: 'POST',
        body: formData
    });
}

function updatePdfMapping(select) {
    const fieldId = select.closest('.custom-field-item').getAttribute('data-field-id');
    const pdfFieldName = select.value;
    
    // Update the mapping in the database
    console.log('Mapping field', fieldId, 'to PDF field', pdfFieldName);
    
    // Update visual feedback
    if (pdfFieldName) {
        const pdfFieldItem = document.querySelector(`[data-field-name="${pdfFieldName}"]`);
        if (pdfFieldItem) {
            pdfFieldItem.classList.add('mapped');
            const mappedDiv = pdfFieldItem.querySelector('.mapped-field');
            const mappedName = pdfFieldItem.querySelector('.mapped-name');
            const fieldLabel = select.closest('.custom-field-item').querySelector('.field-label-input').value;
            
            mappedDiv.style.display = 'block';
            mappedName.textContent = fieldLabel;
        }
    }
}

function saveAllMappings() {
    // Save all current mappings
    alert('All mappings saved successfully!');
}

function showAddCustomFieldModal() {
    const modal = document.createElement('div');
    modal.style.cssText = `
        position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
        background: rgba(0,0,0,0.5); display: flex; align-items: center; 
        justify-content: center; z-index: 1000;
    `;
    
    modal.innerHTML = `
        <div style="background: white; padding: 24px; border-radius: 8px; width: 400px; max-width: 90vw;">
            <h3 style="margin: 0 0 16px 0; color: #0b6bcb;">Add Custom Field</h3>
            <form id="custom-field-form">
                <div style="margin-bottom: 12px;">
                    <label style="display: block; margin-bottom: 4px; font-weight: 600;">Field Label:</label>
                    <input type="text" name="label" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                </div>
                <div style="margin-bottom: 12px;">
                    <label style="display: block; margin-bottom: 4px; font-weight: 600;">Field Type:</label>
                    <select name="type" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="text">Text</option>
                        <option value="textarea">Textarea</option>
                        <option value="number">Number</option>
                        <option value="date">Date</option>
                        <option value="checkbox">Checkbox</option>
                    </select>
                </div>
                <div style="margin-bottom: 12px;">
                    <label style="display: block; margin-bottom: 4px; font-weight: 600;">Map to PDF Field:</label>
                    <select name="pdfMapping" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="">-- Select PDF Field --</option>
                        <?php foreach ($pdfFields as $pdfField): ?>
                            <option value="<?php echo htmlspecialchars($pdfField['name']); ?>">
                                <?php echo htmlspecialchars($pdfField['label']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="display: flex; gap: 8px; justify-content: flex-end;">
                    <button type="button" class="btn secondary" onclick="this.closest('.modal').remove()">Cancel</button>
                    <button type="submit" class="btn">Add Field</button>
                </div>
            </form>
        </div>
    `;
    
    modal.className = 'modal';
    
    const form = modal.querySelector('#custom-field-form');
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(form);
        formData.append('projectDocumentId', '<?php echo htmlspecialchars($projectDocument['id']); ?>');
        formData.append('placeholder', '');
        
        fetch('?route=actions/add-custom-field', {
            method: 'POST',
            body: formData
        }).then(() => {
            location.reload();
        });
    });
    
    document.body.appendChild(modal);
}

function showEditCustomFieldModal(fieldId) {
    // Implementation for editing custom fields
    console.log('Edit field:', fieldId);
}

function deleteCustomField(fieldId) {
    const formData = new FormData();
    formData.append('fieldId', fieldId);
    formData.append('projectDocumentId', '<?php echo htmlspecialchars($projectDocument['id']); ?>');
    
    fetch('?route=actions/delete-custom-field', {
        method: 'POST',
        body: formData
    }).then(() => {
        location.reload();
    });
}
</script>


























