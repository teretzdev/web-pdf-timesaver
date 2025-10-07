<?php
// Simple test populate page without breadcrumb
$projectDocument = $projectDocument ?? null;
$project = $project ?? null;
$client = $client ?? null;
$tpl = $tpl ?? $templates[$projectDocument['templateId']] ?? null;
$fieldValues = $fieldValues ?? [];
?>

<div class="populate-header">
    <h2>Populate — <?php echo htmlspecialchars(($tpl['code'] ?? '') . ' ' . ($tpl['name'] ?? '')); ?></h2>
    <div style="background: #f0f0f0; padding: 10px; margin: 10px 0; font-family: monospace; font-size: 12px;">
        <strong>Debug Info:</strong><br>
        Template ID: <?php echo htmlspecialchars($projectDocument['templateId'] ?? 'NONE'); ?><br>
        Template Found: <?php echo $tpl ? 'YES' : 'NO'; ?><br>
        Template Code: <?php echo htmlspecialchars($tpl['code'] ?? 'NONE'); ?><br>
        Template Name: <?php echo htmlspecialchars($tpl['name'] ?? 'NONE'); ?><br>
        Fields Count: <?php echo count($tpl['fields'] ?? []); ?><br>
        Panels Count: <?php echo count($tpl['panels'] ?? []); ?><br>
        Field Values: <?php echo json_encode($fieldValues); ?><br>
        All Templates: <?php echo json_encode(array_keys($templates ?? [])); ?><br>
        Template Registry: <?php echo json_encode($templates ?? []); ?><br>
        Template Lookup: <?php echo json_encode($templates[$projectDocument['templateId']] ?? 'NOT_FOUND'); ?><br>
        Template Variable: <?php echo json_encode($tpl); ?>
    </div>
    <div class="populate-actions">
        <button type="submit" form="populate-form" class="btn">
            <span class="btn-icon">💾</span>
            <span>Save Form</span>
        </button>
    </div>
</div>

<form id="populate-form" method="post" action="?route=actions/save-fields">
    <input type="hidden" name="projectDocumentId" value="<?php echo htmlspecialchars($projectDocument['id'] ?? ''); ?>">
    
    <div class="populate-panels">
        <?php foreach (($tpl['panels'] ?? []) as $panel): ?>
            <div class="populate-panel" data-panel-id="<?php echo htmlspecialchars($panel['id']); ?>">
                <h3 class="panel-title"><?php echo htmlspecialchars($panel['label']); ?></h3>
                <div class="panel-fields">
                    <?php foreach (($tpl['fields'] ?? []) as $field): ?>
                        <?php if (($field['panelId'] ?? '') === $panel['id']): ?>
                            <div class="field-group">
                                <label for="<?php echo htmlspecialchars($field['key']); ?>" class="field-label">
                                    <?php echo htmlspecialchars($field['label']); ?>
                                    <?php if (!empty($field['required'])): ?>
                                        <span class="required">*</span>
                                    <?php endif; ?>
                                </label>
                                
                                <?php if (($field['type'] ?? 'text') === 'select'): ?>
                                    <select name="<?php echo htmlspecialchars($field['key']); ?>" id="<?php echo htmlspecialchars($field['key']); ?>" class="field-input">
                                        <option value="">Select...</option>
                                        <?php foreach (($field['options'] ?? []) as $option): ?>
                                            <option value="<?php echo htmlspecialchars($option); ?>" <?php echo (($fieldValues[$field['key']] ?? '') === $option) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($option); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php else: ?>
                                    <input type="<?php echo htmlspecialchars($field['type'] ?? 'text'); ?>" 
                                           name="<?php echo htmlspecialchars($field['key']); ?>" 
                                           id="<?php echo htmlspecialchars($field['key']); ?>" 
                                           class="field-input"
                                           placeholder="<?php echo htmlspecialchars($field['placeholder'] ?? ''); ?>"
                                           value="<?php echo htmlspecialchars($fieldValues[$field['key']] ?? ''); ?>"
                                           <?php echo !empty($field['required']) ? 'required' : ''; ?>>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div class="populate-actions-bottom">
        <button type="submit" class="btn btn-primary">
            <span class="btn-icon">💾</span>
            <span>Save Form</span>
        </button>
        <a href="?route=actions/generate&pd=<?php echo htmlspecialchars($projectDocument['id'] ?? ''); ?>" class="btn btn-secondary">
            <span class="btn-icon">📄</span>
            <span>Generate PDF</span>
        </a>
    </div>
</form>
