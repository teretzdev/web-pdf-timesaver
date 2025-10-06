<?php
/**
 * Workflow View - Clio-style step-by-step form filling interface
 */

require_once __DIR__ . '/../lib/workflow_manager.php';

use WebPdfTimeSaver\Mvp\WorkflowManager;

$projectDocumentId = $_GET['pd'] ?? '';
$template = $template ?? [];
$values = $values ?? [];
$customFields = $customFields ?? [];

// Initialize workflow manager
$workflowManager = new WorkflowManager($store, $templates);

// Get or create workflow
$workflow = $workflowManager->getWorkflowByDocument($projectDocumentId);
if (!$workflow) {
    $workflow = $workflowManager->createWorkflow($projectDocumentId);
}

// Get workflow status
$workflowStatus = $workflowManager->getWorkflowStatus($projectDocumentId);

// Get current panel
$currentPanelIndex = $_GET['panel'] ?? $workflow['currentPanelIndex'] ?? 0;
$panels = $workflowStatus['panels'] ?? [];
$currentPanel = $panels[$currentPanelIndex] ?? null;

// Get template fields for current panel
$currentPanelFields = [];
if ($currentPanel && !empty($template['fields'])) {
    $currentPanelFields = array_filter($template['fields'], function($field) use ($currentPanel) {
        return ($field['panelId'] ?? '') === $currentPanel['id'];
    });
}
?>

<div class="workflow-container">
    <!-- Workflow Header -->
    <div class="workflow-header">
        <div class="workflow-title">
            <h2><?php echo htmlspecialchars($template['name'] ?? 'Form Workflow'); ?></h2>
            <div class="workflow-breadcrumb">
                <?php if ($project ?? null): ?>
                    <a href="?route=project&id=<?php echo htmlspecialchars($project['id']); ?>">← Back to Project</a>
                <?php else: ?>
                    <a href="?route=populate&pd=<?php echo htmlspecialchars($projectDocumentId); ?>">← Back to Form</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="workflow-actions">
            <button id="save-progress-btn" class="btn btn-secondary">
                <span class="icon">💾</span> Save Progress
            </button>
            <button id="preview-document-btn" class="btn btn-secondary">
                <span class="icon">👁️</span> Preview
            </button>
            <?php if ($workflowStatus['canGenerate']): ?>
                <a href="?route=actions/generate&pd=<?php echo htmlspecialchars($projectDocumentId); ?>" class="btn btn-primary">
                    <span class="icon">📄</span> Generate PDF
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Progress Bar -->
    <div class="workflow-progress">
        <div class="progress-bar">
            <div class="progress-fill" style="width: <?php echo $workflowStatus['overallProgress']; ?>%"></div>
        </div>
        <div class="progress-text">
            <?php echo $workflowStatus['overallProgress']; ?>% Complete
            (<?php echo $workflowStatus['completedPanels']; ?> of <?php echo $workflowStatus['totalPanels']; ?> sections)
        </div>
    </div>

    <!-- Workflow Steps -->
    <div class="workflow-layout">
        <!-- Left Sidebar - Steps Navigation -->
        <div class="workflow-sidebar">
            <h3>Form Sections</h3>
            <div class="workflow-steps">
                <?php foreach ($panels as $index => $panel): ?>
                    <div class="workflow-step <?php 
                        echo $index == $currentPanelIndex ? 'active' : '';
                        echo ' status-' . $panel['status'];
                    ?>" data-panel-index="<?php echo $index; ?>">
                        <div class="step-indicator">
                            <?php if ($panel['status'] === 'complete'): ?>
                                <span class="icon success">✓</span>
                            <?php elseif ($panel['status'] === 'incomplete' || count($panel['errors']) > 0): ?>
                                <span class="icon error">!</span>
                            <?php elseif ($panel['status'] === 'in_progress'): ?>
                                <span class="icon progress"><?php echo $panel['progress']; ?>%</span>
                            <?php else: ?>
                                <span class="step-number"><?php echo $index + 1; ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="step-content">
                            <div class="step-title"><?php echo htmlspecialchars($panel['label']); ?></div>
                            <div class="step-subtitle">
                                <?php echo $panel['completedFields']; ?> of <?php echo $panel['totalFields']; ?> fields
                            </div>
                            <?php if (count($panel['errors']) > 0): ?>
                                <div class="step-errors"><?php echo count($panel['errors']); ?> errors</div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Main Content - Current Step Form -->
        <div class="workflow-content">
            <?php if ($currentPanel): ?>
                <div class="panel-header">
                    <h3><?php echo htmlspecialchars($currentPanel['label']); ?></h3>
                    <div class="panel-progress">
                        <div class="mini-progress">
                            <div class="mini-progress-fill" style="width: <?php echo $currentPanel['progress']; ?>%"></div>
                        </div>
                        <span><?php echo $currentPanel['completedFields']; ?> of <?php echo $currentPanel['totalFields']; ?> completed</span>
                    </div>
                </div>

                <?php if (count($currentPanel['errors']) > 0): ?>
                    <div class="panel-errors">
                        <h4>⚠️ Please fix the following issues:</h4>
                        <ul>
                            <?php foreach ($currentPanel['errors'] as $error): ?>
                                <li><?php echo htmlspecialchars($error['message']); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form id="workflow-form" method="post" action="?route=actions/save-workflow-fields">
                    <input type="hidden" name="projectDocumentId" value="<?php echo htmlspecialchars($projectDocumentId); ?>">
                    <input type="hidden" name="workflowId" value="<?php echo htmlspecialchars($workflow['id']); ?>">
                    <input type="hidden" name="currentPanel" value="<?php echo htmlspecialchars($currentPanel['id']); ?>">
                    
                    <div class="workflow-fields">
                        <?php foreach ($currentPanelFields as $field): ?>
                            <?php 
                            $fieldValue = $values[$field['key']] ?? '';
                            $fieldError = null;
                            foreach ($currentPanel['errors'] as $error) {
                                if ($error['field'] === $field['key']) {
                                    $fieldError = $error['message'];
                                    break;
                                }
                            }
                            ?>
                            <div class="workflow-field <?php echo $fieldError ? 'has-error' : ''; ?>">
                                <label for="field-<?php echo htmlspecialchars($field['key']); ?>">
                                    <?php echo htmlspecialchars($field['label']); ?>
                                    <?php if (!empty($field['required'])): ?>
                                        <span class="required">*</span>
                                    <?php endif; ?>
                                </label>
                                
                                <?php if ($fieldError): ?>
                                    <div class="field-error"><?php echo htmlspecialchars($fieldError); ?></div>
                                <?php endif; ?>
                                
                                <?php 
                                $inputId = 'field-' . htmlspecialchars($field['key']);
                                $inputName = htmlspecialchars($field['key']);
                                $placeholder = htmlspecialchars($field['placeholder'] ?? '');
                                $required = !empty($field['required']) ? 'required' : '';
                                ?>
                                
                                <?php if ($field['type'] === 'textarea'): ?>
                                    <textarea 
                                        id="<?php echo $inputId; ?>"
                                        name="<?php echo $inputName; ?>"
                                        rows="4"
                                        placeholder="<?php echo $placeholder; ?>"
                                        <?php echo $required; ?>
                                        class="form-control"><?php echo htmlspecialchars((string)$fieldValue); ?></textarea>
                                <?php elseif ($field['type'] === 'select' && !empty($field['options'])): ?>
                                    <select 
                                        id="<?php echo $inputId; ?>"
                                        name="<?php echo $inputName; ?>"
                                        <?php echo $required; ?>
                                        class="form-control">
                                        <option value=""><?php echo $placeholder ?: 'Select an option'; ?></option>
                                        <?php foreach ($field['options'] as $option): ?>
                                            <option value="<?php echo htmlspecialchars($option); ?>" 
                                                <?php echo $fieldValue == $option ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($option); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php elseif ($field['type'] === 'checkbox'): ?>
                                    <label class="checkbox-label">
                                        <input type="hidden" name="<?php echo $inputName; ?>" value="0">
                                        <input 
                                            type="checkbox"
                                            id="<?php echo $inputId; ?>"
                                            name="<?php echo $inputName; ?>"
                                            value="1"
                                            <?php echo !empty($fieldValue) ? 'checked' : ''; ?>
                                            <?php echo $required; ?>>
                                        <span>Yes</span>
                                    </label>
                                <?php else: ?>
                                    <input 
                                        type="<?php echo htmlspecialchars($field['type'] ?? 'text'); ?>"
                                        id="<?php echo $inputId; ?>"
                                        name="<?php echo $inputName; ?>"
                                        value="<?php echo htmlspecialchars((string)$fieldValue); ?>"
                                        placeholder="<?php echo $placeholder; ?>"
                                        <?php echo $required; ?>
                                        class="form-control">
                                <?php endif; ?>
                                
                                <?php if (!empty($field['helpText'])): ?>
                                    <small class="help-text"><?php echo htmlspecialchars($field['helpText']); ?></small>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Navigation Buttons -->
                    <div class="workflow-navigation">
                        <?php if ($currentPanelIndex > 0): ?>
                            <button type="button" class="btn btn-secondary" onclick="navigateToPanel(<?php echo $currentPanelIndex - 1; ?>)">
                                ← Previous
                            </button>
                        <?php endif; ?>
                        
                        <div class="nav-right">
                            <button type="submit" class="btn btn-primary">
                                Save & Continue
                            </button>
                            
                            <?php if ($currentPanelIndex < count($panels) - 1): ?>
                                <button type="button" class="btn btn-secondary" onclick="navigateToPanel(<?php echo $currentPanelIndex + 1; ?>)">
                                    Skip →
                                </button>
                            <?php else: ?>
                                <?php if ($workflowStatus['canGenerate']): ?>
                                    <button type="button" class="btn btn-success" onclick="generateDocument()">
                                        ✓ Complete & Generate
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>
            <?php else: ?>
                <div class="empty-state">
                    <h3>No panels available</h3>
                    <p>This form doesn't have any panels configured yet.</p>
                    <a href="?route=panel-editor&pd=<?php echo htmlspecialchars($projectDocumentId); ?>" class="btn btn-primary">
                        Configure Panels
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Right Sidebar - Help & Info -->
        <div class="workflow-help">
            <div class="help-section">
                <h4>Quick Tips</h4>
                <ul>
                    <li>Fields marked with * are required</li>
                    <li>Save your progress frequently</li>
                    <li>You can skip sections and return later</li>
                    <li>Green checkmarks indicate completed sections</li>
                </ul>
            </div>
            
            <?php if ($workflowStatus['nextStep']): ?>
                <div class="help-section">
                    <h4>Next Section</h4>
                    <p><?php echo htmlspecialchars($workflowStatus['nextStep']['label']); ?></p>
                    <button class="btn btn-sm" onclick="navigateToPanel(<?php echo $workflowStatus['nextStep']['index']; ?>)">
                        Go to Next Section →
                    </button>
                </div>
            <?php endif; ?>
            
            <div class="help-section">
                <h4>Need Help?</h4>
                <p>Contact support if you have questions about any field.</p>
                <button class="btn btn-sm btn-secondary" onclick="showHelp()">
                    Get Help
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Workflow Styles */
.workflow-container {
    display: flex;
    flex-direction: column;
    min-height: calc(100vh - 60px);
    background: #f5f7fa;
}

.workflow-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    background: white;
    border-bottom: 1px solid #e1e5e9;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.workflow-title h2 {
    margin: 0;
    color: #2c3e50;
}

.workflow-breadcrumb {
    margin-top: 5px;
}

.workflow-breadcrumb a {
    color: #0b6bcb;
    text-decoration: none;
    font-size: 14px;
}

.workflow-actions {
    display: flex;
    gap: 10px;
}

/* Progress Bar */
.workflow-progress {
    padding: 20px;
    background: white;
    border-bottom: 1px solid #e1e5e9;
}

.progress-bar {
    height: 8px;
    background: #e9ecef;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 10px;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #0b6bcb, #0954a5);
    transition: width 0.3s ease;
}

.progress-text {
    font-size: 14px;
    color: #6c757d;
    text-align: center;
}

/* Layout */
.workflow-layout {
    flex: 1;
    display: flex;
    overflow: hidden;
}

/* Sidebar */
.workflow-sidebar {
    width: 280px;
    background: white;
    border-right: 1px solid #e1e5e9;
    overflow-y: auto;
    padding: 20px;
}

.workflow-sidebar h3 {
    margin: 0 0 20px 0;
    font-size: 16px;
    color: #2c3e50;
}

.workflow-steps {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.workflow-step {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
}

.workflow-step:hover {
    background: #f8f9fa;
    border-color: #0b6bcb;
}

.workflow-step.active {
    background: #e6f3ff;
    border-color: #0b6bcb;
    box-shadow: 0 2px 4px rgba(11,107,203,0.2);
}

.step-indicator {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: #e9ecef;
    font-weight: 600;
}

.workflow-step.status-complete .step-indicator {
    background: #28a745;
    color: white;
}

.workflow-step.status-incomplete .step-indicator,
.workflow-step.status-not_started .step-indicator {
    background: #ffc107;
    color: white;
}

.workflow-step.status-in_progress .step-indicator {
    background: #0b6bcb;
    color: white;
    font-size: 11px;
}

.step-content {
    flex: 1;
}

.step-title {
    font-weight: 500;
    color: #2c3e50;
    margin-bottom: 2px;
}

.step-subtitle {
    font-size: 12px;
    color: #6c757d;
}

.step-errors {
    font-size: 12px;
    color: #dc3545;
    margin-top: 2px;
}

/* Main Content */
.workflow-content {
    flex: 1;
    padding: 20px;
    overflow-y: auto;
    background: white;
}

.panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 15px;
    border-bottom: 1px solid #e1e5e9;
}

.panel-header h3 {
    margin: 0;
    color: #2c3e50;
}

.panel-progress {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 14px;
    color: #6c757d;
}

.mini-progress {
    width: 100px;
    height: 6px;
    background: #e9ecef;
    border-radius: 3px;
    overflow: hidden;
}

.mini-progress-fill {
    height: 100%;
    background: #0b6bcb;
    transition: width 0.3s ease;
}

.panel-errors {
    background: #fff5f5;
    border: 1px solid #f5c6cb;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 20px;
}

.panel-errors h4 {
    margin: 0 0 10px 0;
    color: #dc3545;
    font-size: 14px;
}

.panel-errors ul {
    margin: 0;
    padding-left: 20px;
}

.panel-errors li {
    color: #721c24;
    font-size: 13px;
}

/* Form Fields */
.workflow-fields {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.workflow-field {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.workflow-field label {
    font-weight: 500;
    color: #495057;
    font-size: 14px;
}

.required {
    color: #dc3545;
    margin-left: 4px;
}

.form-control {
    padding: 10px 12px;
    border: 1px solid #e1e5e9;
    border-radius: 6px;
    font-size: 14px;
    transition: all 0.2s;
}

.form-control:focus {
    outline: none;
    border-color: #0b6bcb;
    box-shadow: 0 0 0 3px rgba(11,107,203,0.1);
}

.workflow-field.has-error .form-control {
    border-color: #dc3545;
}

.field-error {
    color: #dc3545;
    font-size: 12px;
}

.help-text {
    color: #6c757d;
    font-size: 12px;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
}

/* Navigation */
.workflow-navigation {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e1e5e9;
}

.nav-right {
    display: flex;
    gap: 10px;
}

/* Help Sidebar */
.workflow-help {
    width: 250px;
    background: white;
    border-left: 1px solid #e1e5e9;
    padding: 20px;
    overflow-y: auto;
}

.help-section {
    margin-bottom: 25px;
}

.help-section h4 {
    margin: 0 0 10px 0;
    font-size: 14px;
    color: #2c3e50;
}

.help-section ul {
    margin: 0;
    padding-left: 20px;
}

.help-section li {
    font-size: 13px;
    color: #6c757d;
    margin-bottom: 5px;
}

.help-section p {
    font-size: 13px;
    color: #6c757d;
    margin: 0 0 10px 0;
}

/* Buttons */
.btn {
    padding: 10px 16px;
    border: none;
    border-radius: 6px;
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

.btn-success {
    background: #28a745;
    color: white;
}

.btn-success:hover {
    background: #218838;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 12px;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px 20px;
}

.empty-state h3 {
    color: #2c3e50;
    margin-bottom: 10px;
}

.empty-state p {
    color: #6c757d;
    margin-bottom: 20px;
}

/* Responsive */
@media (max-width: 1200px) {
    .workflow-help {
        display: none;
    }
}

@media (max-width: 768px) {
    .workflow-layout {
        flex-direction: column;
    }
    
    .workflow-sidebar {
        width: 100%;
        max-height: 200px;
        border-right: none;
        border-bottom: 1px solid #e1e5e9;
    }
}
</style>

<script>
// Workflow JavaScript
function navigateToPanel(panelIndex) {
    window.location.href = '?route=workflow&pd=<?php echo htmlspecialchars($projectDocumentId); ?>&panel=' + panelIndex;
}

function generateDocument() {
    if (confirm('Are you ready to generate the PDF document?')) {
        window.location.href = '?route=actions/generate&pd=<?php echo htmlspecialchars($projectDocumentId); ?>';
    }
}

function showHelp() {
    alert('Help system coming soon. For now, please contact support.');
}

// Step navigation
document.addEventListener('DOMContentLoaded', function() {
    // Click on step to navigate
    document.querySelectorAll('.workflow-step').forEach(step => {
        step.addEventListener('click', function() {
            const panelIndex = this.dataset.panelIndex;
            navigateToPanel(panelIndex);
        });
    });
    
    // Save progress button
    document.getElementById('save-progress-btn')?.addEventListener('click', function() {
        document.getElementById('workflow-form').submit();
    });
    
    // Preview button
    document.getElementById('preview-document-btn')?.addEventListener('click', function() {
        window.open('?route=preview&pd=<?php echo htmlspecialchars($projectDocumentId); ?>', '_blank');
    });
    
    // Auto-save on field change
    let saveTimeout;
    document.querySelectorAll('.form-control').forEach(field => {
        field.addEventListener('change', function() {
            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(function() {
                // Auto-save logic (optional)
                console.log('Auto-saving...');
            }, 2000);
        });
    });
    
    // Keyboard navigation
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey || e.metaKey) {
            if (e.key === 's') {
                e.preventDefault();
                document.getElementById('workflow-form').submit();
            }
        }
    });
});
</script>