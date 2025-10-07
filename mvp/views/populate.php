<?php 
$tpl = $template; 

// Get project and client info for breadcrumbs
$project = null;
$client = null;
if (!empty($projectDocument['projectId'])) {
    $project = $store->getProject($projectDocument['projectId']);
    if ($project && !empty($project['clientId']) && $store && method_exists($store, 'getClient')) {
        $client = $store->getClient($project['clientId']);
    }
}
?>

<?php if (isset($_GET['saved']) && $_GET['saved'] === '1'): ?>
    <div class="clio-card" style="background: #d4edda; color: #155724; margin-bottom: 16px;">
        ✅ Form data saved successfully!
    </div>
<?php endif; ?>

<div class="clio-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h3 style="margin: 0; color: #2c3e50; font-size: 20px;">Document: <?php echo htmlspecialchars(($tpl['code'] ?? '') . ' ' . ($tpl['name'] ?? '')); ?></h3>
        <div style="display: flex; gap: 12px;">
            <button type="submit" form="populate-form" class="clio-btn">Save Form</button>
            <a href="?route=project&id=<?php echo htmlspecialchars($projectDocument['projectId']); ?>" class="clio-btn-secondary">Back to Matter</a>
        </div>
    </div>
</div>

<form method="post" action="?route=actions/save-fields" id="populate-form">
    <input type="hidden" name="projectDocumentId" value="<?php echo htmlspecialchars($projectDocument['id']); ?>">

    <?php if (!empty($tpl['panels'])): ?>
        <?php foreach ($tpl['panels'] as $panel): ?>
            <div class="clio-card">
                <h3 style="margin: 0 0 20px 0; color: #2c3e50; font-size: 18px; font-weight: 600;"><?php echo htmlspecialchars($panel['label']); ?></h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                    <?php foreach ($tpl['fields'] as $field): if (($field['panelId'] ?? '') !== $panel['id']) continue; ?>
                        <div class="clio-form-group">
                            <label class="clio-form-label">
                                <?php echo htmlspecialchars($field['label']); ?>
                            </label>
                            <?php $type = $field['type'] ?? 'text'; $val = $values[$field['key']] ?? ''; ?>
                            <?php 
                            $placeholder = $field['placeholder'] ?? '';
                            $required = !empty($field['required']) ? 'required' : '';
                            ?>
                            <?php if ($type === 'textarea'): ?>
                                <textarea name="<?php echo htmlspecialchars($field['key']); ?>" rows="3" placeholder="<?php echo htmlspecialchars($placeholder); ?>" <?php echo $required; ?> class="clio-input"><?php echo htmlspecialchars((string)$val); ?></textarea>
                            <?php elseif ($type === 'number' || $type === 'date'): ?>
                                <input type="<?php echo $type==='number'?'number':'date'; ?>" name="<?php echo htmlspecialchars($field['key']); ?>" value="<?php echo htmlspecialchars((string)$val); ?>" placeholder="<?php echo htmlspecialchars($placeholder); ?>" <?php echo $required; ?> class="clio-input">
                            <?php elseif ($type === 'checkbox'): ?>
                                <label style="display:flex; align-items:center; gap:8px;">
                                    <input type="hidden" name="<?php echo htmlspecialchars($field['key']); ?>" value="0">
                                    <input type="checkbox" name="<?php echo htmlspecialchars($field['key']); ?>" value="1" <?php echo !empty($val)?'checked':''; ?> <?php echo $required; ?>>
                                    <span><?php echo !empty($val) ? 'Yes' : 'No'; ?></span>
                                </label>
                            <?php elseif ($type === 'select' && !empty($field['options']) && is_array($field['options'])): ?>
                                <select name="<?php echo htmlspecialchars($field['key']); ?>" <?php echo $required; ?> class="clio-input">
                                    <option value=""><?php echo htmlspecialchars($placeholder ?: 'Select an option'); ?></option>
                                    <?php foreach ($field['options'] as $opt): ?>
                                        <option value="<?php echo htmlspecialchars((string)$opt); ?>" <?php echo ((string)$val)===(string)$opt?'selected':''; ?>><?php echo htmlspecialchars((string)$opt); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <input type="text" name="<?php echo htmlspecialchars($field['key']); ?>" value="<?php echo htmlspecialchars((string)$val); ?>" placeholder="<?php echo htmlspecialchars($placeholder); ?>" <?php echo $required; ?> class="clio-input">
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Custom Fields Section -->
    <div class="panel" style="margin-top: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h3 style="color: #0b6bcb; margin: 0;">Custom Fields</h3>
            <button type="button" id="add-custom-field-btn" class="btn secondary" style="font-size: 12px; padding: 6px 12px;">
                + Add Custom Field
            </button>
        </div>
        
        <!-- Custom Fields Container -->
        <div id="custom-fields-container" class="grid">
            <?php if (!empty($customFields)): ?>
                <?php foreach ($customFields as $customField): ?>
                    <div class="clio-form-group" data-field-id="<?php echo htmlspecialchars($customField['id']); ?>">
                        <label class="clio-form-label"><?php echo htmlspecialchars($customField['label']); ?></label>
                        <?php 
                        $customKey = 'custom_' . $customField['id'];
                        $customVal = $values[$customKey] ?? '';
                        $customType = $customField['type'] ?? 'text';
                        $customPlaceholder = $customField['placeholder'] ?? '';
                        $customRequired = !empty($customField['required']) ? 'required' : '';
                        ?>
                        <?php if ($customType === 'textarea'): ?>
                            <textarea name="<?php echo htmlspecialchars($customKey); ?>" rows="3" placeholder="<?php echo htmlspecialchars($customPlaceholder); ?>" <?php echo $customRequired; ?> class="clio-input"><?php echo htmlspecialchars((string)$customVal); ?></textarea>
                        <?php elseif ($customType === 'number' || $customType === 'date'): ?>
                            <input type="<?php echo $customType==='number'?'number':'date'; ?>" name="<?php echo htmlspecialchars($customKey); ?>" value="<?php echo htmlspecialchars((string)$customVal); ?>" placeholder="<?php echo htmlspecialchars($customPlaceholder); ?>" <?php echo $customRequired; ?> class="clio-input">
                        <?php elseif ($customType === 'checkbox'): ?>
                            <label style="display:flex; align-items:center; gap:8px;">
                                <input type="hidden" name="<?php echo htmlspecialchars($customKey); ?>" value="0">
                                <input type="checkbox" name="<?php echo htmlspecialchars($customKey); ?>" value="1" <?php echo !empty($customVal)?'checked':''; ?> <?php echo $customRequired; ?>>
                                <span><?php echo !empty($customVal) ? 'Yes' : 'No'; ?></span>
                            </label>
                        <?php else: ?>
                            <input type="text" name="<?php echo htmlspecialchars($customKey); ?>" value="<?php echo htmlspecialchars((string)$customVal); ?>" placeholder="<?php echo htmlspecialchars($customPlaceholder); ?>" <?php echo $customRequired; ?> class="clio-input">
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

</form>
}

input[type="text"]:focus, input[type="number"]:focus, input[type="date"]:focus, textarea:focus, select:focus {
    outline: none;
    border-color: #0b6bcb !important;
    background: #e6f3ff !important;
    box-shadow: 0 4px 8px rgba(11,107,203,0.2) !important;
    transform: translateY(-1px);
}

input[type="text"]:hover, input[type="number"]:hover, input[type="date"]:hover, textarea:hover, select:hover {
    border-color: #0b6bcb !important;
    background: #f8fcff !important;
}

/* Required field indicator */
input[required], textarea[required], select[required] {
    border-left: 4px solid #dc3545 !important;
}

/* Field label styling */
.muted {
    font-weight: 600;
    color: #495057 !important;
    margin-bottom: 8px !important;
}

/* Panel styling */
.panel {
    background: #ffffff;
    border: 1px solid #e9ecef;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.panel h3 {
    color: #0b6bcb;
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 8px;
    margin-bottom: 16px;
}

/* Button styling */
.btn {
    transition: all 0.3s ease;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(11,107,203,0.3);
}

/* Grid layout improvements */
.grid {
    gap: 16px;
}

.grid > div {
    background: #f8f9fa;
    padding: 16px;
    border-radius: 8px;
    border: 1px solid #e9ecef;
}

/* Drag and Drop Styling */
.draggable {
    transition: all 0.3s ease;
    cursor: move;
}

.draggable:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.draggable.drag-over {
    border: 2px dashed #28a745;
    background: #f0fff0;
    transform: scale(1.02);
}

.drag-handle {
    transition: color 0.3s ease;
}

.drag-handle:hover {
    color: #28a745 !important;
}

.draggable.dragging {
    opacity: 0.5;
    transform: rotate(2deg);
}
// Auto-hide success message after 3 seconds
document.addEventListener('DOMContentLoaded', function() {
    const successMsg = document.querySelector('[style*="background: #d4edda"]');
    if (successMsg) {
        setTimeout(function() {
            successMsg.style.transition = 'opacity 0.5s';
            successMsg.style.opacity = '0';
            setTimeout(function() {
                successMsg.remove();
            }, 500);
        }, 3000);
    }
    
    // Add focus effects to form fields
    const formFields = document.querySelectorAll('input, textarea, select');
    formFields.forEach(field => {
        field.addEventListener('focus', function() {
            this.parentElement.style.transform = 'scale(1.02)';
            this.parentElement.style.transition = 'transform 0.2s ease';
        });
        
        field.addEventListener('blur', function() {
            this.parentElement.style.transform = 'scale(1)';
        });
    });
    
    // Handle revert button clicks
    const revertButtons = document.querySelectorAll('.revert-btn');
    revertButtons.forEach(button => {
        button.addEventListener('click', function() {
            const fieldName = this.getAttribute('data-field');
            const originalValue = this.getAttribute('data-original');
            
            // Find the corresponding form field
            const field = document.querySelector(`[name="${fieldName}"]`);
            if (field) {
                if (field.type === 'checkbox') {
                    // Handle checkbox
                    field.checked = originalValue === '1';
                    // Update the display text
                    const span = field.parentElement.querySelector('span');
                    if (span) {
                        span.textContent = field.checked ? 'Yes' : 'No';
                    }
                } else if (field.tagName === 'SELECT') {
                    // Handle select dropdown
                    field.value = originalValue;
                } else {
                    // Handle text, textarea, number, date inputs
                    field.value = originalValue;
                }
                
                // Visual feedback
                this.style.background = '#28a745';
                this.textContent = '✓ Reverted';
                setTimeout(() => {
                    this.style.background = '#dc3545';
                    this.textContent = '↶ Revert';
                }, 1500);
                
                // Trigger change event to update any dependent UI
                field.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
        
        // Hover effects for revert buttons
        button.addEventListener('mouseenter', function() {
            this.style.opacity = '1';
        });
        
        button.addEventListener('mouseleave', function() {
            this.style.opacity = '0.7';
        });
    });
    
    // Custom Fields Management
    const addCustomFieldBtn = document.getElementById('add-custom-field-btn');
    const customFieldsContainer = document.getElementById('custom-fields-container');
    
    if (addCustomFieldBtn) {
        addCustomFieldBtn.addEventListener('click', function() {
            showAddCustomFieldModal();
        });
    }
    
    // Handle delete custom field buttons
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('delete-custom-field-btn')) {
            const fieldId = e.target.getAttribute('data-field-id');
            if (confirm('Are you sure you want to delete this custom field? This action cannot be undone.')) {
                deleteCustomField(fieldId);
            }
        }
        
        if (e.target.classList.contains('edit-custom-field-btn')) {
            const fieldId = e.target.getAttribute('data-field-id');
            showEditCustomFieldModal(fieldId);
        }
    });
    
    function showAddCustomFieldModal() {
        const modal = createCustomFieldModal('Add Custom Field', '', 'text', '', false);
        document.body.appendChild(modal);
    }
    
    function showEditCustomFieldModal(fieldId) {
        const fieldElement = document.querySelector(`[data-field-id="${fieldId}"]`);
        if (!fieldElement) return;
        
        const label = fieldElement.querySelector('.muted span').textContent;
        const input = fieldElement.querySelector('input, textarea, select');
        const type = input.tagName === 'TEXTAREA' ? 'textarea' : 
                    input.tagName === 'SELECT' ? 'select' : 
                    input.type === 'checkbox' ? 'checkbox' : 
                    input.type === 'number' ? 'number' : 
                    input.type === 'date' ? 'date' : 'text';
        const placeholder = input.placeholder || '';
        const required = input.hasAttribute('required');
        
        const modal = createCustomFieldModal('Edit Custom Field', label, type, placeholder, required, fieldId);
        document.body.appendChild(modal);
    }
    
    function createCustomFieldModal(title, label, type, placeholder, required, fieldId = null) {
        const modal = document.createElement('div');
        modal.style.cssText = `
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
            background: rgba(0,0,0,0.5); display: flex; align-items: center; 
            justify-content: center; z-index: 1000;
        `;
        
        modal.innerHTML = `
            <div style="background: white; padding: 24px; border-radius: 8px; width: 400px; max-width: 90vw;">
                <h3 style="margin: 0 0 16px 0; color: #0b6bcb;">${title}</h3>
                <form id="custom-field-form">
                    <div style="margin-bottom: 12px;">
                        <label style="display: block; margin-bottom: 4px; font-weight: 600;">Field Label:</label>
                        <input type="text" name="label" value="${label}" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                    <div style="margin-bottom: 12px;">
                        <label style="display: block; margin-bottom: 4px; font-weight: 600;">Field Type:</label>
                        <select name="type" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                            <option value="text" ${type === 'text' ? 'selected' : ''}>Text</option>
                            <option value="textarea" ${type === 'textarea' ? 'selected' : ''}>Textarea</option>
                            <option value="number" ${type === 'number' ? 'selected' : ''}>Number</option>
                            <option value="date" ${type === 'date' ? 'selected' : ''}>Date</option>
                            <option value="checkbox" ${type === 'checkbox' ? 'selected' : ''}>Checkbox</option>
                        </select>
                    </div>
                    <div style="margin-bottom: 12px;">
                        <label style="display: block; margin-bottom: 4px; font-weight: 600;">Placeholder:</label>
                        <input type="text" name="placeholder" value="${placeholder}" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    </div>
                    <div style="margin-bottom: 16px;">
                        <label style="display: flex; align-items: center; gap: 8px;">
                            <input type="checkbox" name="required" ${required ? 'checked' : ''}>
                            <span>Required field</span>
                        </label>
                    </div>
                    <div style="display: flex; gap: 8px; justify-content: flex-end;">
                        <button type="button" class="btn secondary" onclick="this.closest('.modal').remove()">Cancel</button>
                        <button type="submit" class="btn">${fieldId ? 'Update' : 'Add'} Field</button>
                    </div>
                </form>
            </div>
        `;
        
        modal.className = 'modal';
        
        const form = modal.querySelector('#custom-field-form');
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(form);
            const data = {
                label: formData.get('label'),
                type: formData.get('type'),
                placeholder: formData.get('placeholder'),
                required: formData.has('required')
            };
            
            if (fieldId) {
                updateCustomField(fieldId, data);
            } else {
                addCustomField(data);
            }
            
            modal.remove();
        });
        
        return modal;
    }
    
    function addCustomField(data) {
        const formData = new FormData();
        formData.append('projectDocumentId', '<?php echo htmlspecialchars($projectDocument['id']); ?>');
        formData.append('label', data.label);
        formData.append('type', data.type);
        formData.append('placeholder', data.placeholder);
        if (data.required) formData.append('required', '1');
        
        fetch('?route=actions/add-custom-field', {
            method: 'POST',
            body: formData
        }).then(() => {
            location.reload();
        });
    }
    
    function updateCustomField(fieldId, data) {
        const formData = new FormData();
        formData.append('fieldId', fieldId);
        formData.append('projectDocumentId', '<?php echo htmlspecialchars($projectDocument['id']); ?>');
        formData.append('label', data.label);
        formData.append('type', data.type);
        formData.append('placeholder', data.placeholder);
        if (data.required) formData.append('required', '1');
        
        fetch('?route=actions/update-custom-field', {
            method: 'POST',
            body: formData
        }).then(() => {
            location.reload();
        });
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
    
    // Drag and Drop Functionality
    let draggedElement = null;
    let draggedIndex = null;
    
    // Add drag event listeners to all draggable elements
    function initializeDragAndDrop() {
        const draggableElements = document.querySelectorAll('.draggable');
        
        draggableElements.forEach((element, index) => {
            element.addEventListener('dragstart', handleDragStart);
            element.addEventListener('dragend', handleDragEnd);
            element.addEventListener('dragover', handleDragOver);
            element.addEventListener('drop', handleDrop);
            element.addEventListener('dragenter', handleDragEnter);
            element.addEventListener('dragleave', handleDragLeave);
        });
    }
    
    function handleDragStart(e) {
        draggedElement = this;
        draggedIndex = Array.from(this.parentNode.children).indexOf(this);
        this.style.opacity = '0.5';
        this.style.transform = 'rotate(2deg)';
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/html', this.outerHTML);
    }
    
    function handleDragEnd(e) {
        this.style.opacity = '1';
        this.style.transform = 'rotate(0deg)';
        this.classList.remove('drag-over');
        draggedElement = null;
        draggedIndex = null;
    }
    
    function handleDragOver(e) {
        if (e.preventDefault) {
            e.preventDefault();
        }
        e.dataTransfer.dropEffect = 'move';
        return false;
    }
    
    function handleDragEnter(e) {
        this.classList.add('drag-over');
    }
    
    function handleDragLeave(e) {
        this.classList.remove('drag-over');
    }
    
    function handleDrop(e) {
        if (e.stopPropagation) {
            e.stopPropagation();
        }
        
        if (draggedElement !== this) {
            const dropIndex = Array.from(this.parentNode.children).indexOf(this);
            
            // Move the element in the DOM
            if (draggedIndex < dropIndex) {
                this.parentNode.insertBefore(draggedElement, this.nextSibling);
            } else {
                this.parentNode.insertBefore(draggedElement, this);
            }
            
            // Update the order in the database
            updateFieldOrder();
        }
        
        this.classList.remove('drag-over');
        return false;
    }
    
    function updateFieldOrder() {
        const fieldIds = Array.from(document.querySelectorAll('.custom-field-item')).map(el => el.getAttribute('data-field-id'));
        
        const formData = new FormData();
        formData.append('projectDocumentId', '<?php echo htmlspecialchars($projectDocument['id']); ?>');
        fieldIds.forEach((id, index) => {
            formData.append('fieldIds[]', id);
        });
        
        fetch('?route=actions/update-custom-field-order', {
            method: 'POST',
            body: formData
        }).then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Field order updated successfully');
            }
        }).catch(error => {
            console.error('Error updating field order:', error);
        });
    }
    
    // Initialize drag and drop when page loads
    initializeDragAndDrop();
    
    // Re-initialize drag and drop after adding new fields
    const originalAddCustomField = addCustomField;
    addCustomField = function(data) {
        originalAddCustomField(data).then(() => {
            setTimeout(() => {
                initializeDragAndDrop();
            }, 100);
        });
    };
});