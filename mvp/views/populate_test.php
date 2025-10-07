<?php
// Test populate page for debugging
$projectDocument = $projectDocument ?? null;
$project = $project ?? null;
$client = $client ?? null;
$template = $template ?? $templates[$projectDocument['templateId']] ?? null;
$fieldValues = $fieldValues ?? [];
?>

<div class="clio-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <div>
            <h2 style="margin: 0 0 6px 0; color: #2c3e50; font-size: 24px; font-weight: 700;">
                Test Mode: <?php echo htmlspecialchars(($template['code'] ?? '') . ' — ' . ($template['name'] ?? '')); ?>
            </h2>
            <p style="margin: 0; color: #6c757d; font-size: 14px;">Debug template populate form</p>
        </div>
        <div style="display: flex; gap: 8px;">
            <button type="submit" form="populate-form" class="clio-btn">Save Form</button>
            <a href="?route=project&id=<?php echo htmlspecialchars($projectDocument['projectId'] ?? ''); ?>" class="clio-btn-secondary">Exit Test Mode</a>
        </div>
    </div>
</div>

<div class="clio-card" style="background: #f8f9fa;">
    <h4 style="margin: 0 0 12px 0; color: #495057; font-size: 14px;">Debug Information</h4>
    <div style="font-family: monospace; font-size: 12px; color: #6c757d; line-height: 1.6;">
        Template Found: <strong><?php echo $template ? 'YES' : 'NO'; ?></strong><br>
        Template ID: <?php echo htmlspecialchars($projectDocument['templateId'] ?? 'NONE'); ?><br>
        Fields: <?php echo count($template['fields'] ?? []); ?> | Panels: <?php echo count($template['panels'] ?? []); ?>
    </div>
</div>

<form id="populate-form" method="post" action="?route=actions/save-fields">
    <input type="hidden" name="projectDocumentId" value="<?php echo htmlspecialchars($projectDocument['id'] ?? ''); ?>">
    
    <?php if (!empty($template['panels'])): ?>
        <?php foreach ($template['panels'] as $panel): ?>
            <div class="clio-card">
                <h3 style="margin: 0 0 20px 0; color: #2c3e50; font-size: 18px; font-weight: 600;">
                    <?php echo htmlspecialchars($panel['label']); ?>
                </h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                    <?php foreach ($template['fields'] as $field): if (($field['panelId'] ?? '') !== $panel['id']) continue; ?>
                        <div class="clio-form-group">
                            <label class="clio-form-label">
                                <?php echo htmlspecialchars($field['label']); ?>
                                <?php if (!empty($field['required'])): ?>
                                    <span style="color: #dc3545;">*</span>
                                <?php endif; ?>
                            </label>
                            
                            <?php if (($field['type'] ?? 'text') === 'select'): ?>
                                <select name="<?php echo htmlspecialchars($field['key']); ?>" class="clio-input" <?php echo !empty($field['required']) ? 'required' : ''; ?>>
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
                                       class="clio-input"
                                       placeholder="<?php echo htmlspecialchars($field['placeholder'] ?? ''); ?>"
                                       value="<?php echo htmlspecialchars($fieldValues[$field['key']] ?? ''); ?>"
                                       <?php echo !empty($field['required']) ? 'required' : ''; ?>>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <div style="display: flex; gap: 8px; margin-top: 24px;">
        <button type="submit" class="clio-btn">Save Form</button>
        <a href="?route=actions/generate&pd=<?php echo htmlspecialchars($projectDocument['id'] ?? ''); ?>" class="clio-btn-secondary">Generate PDF</a>
        <a href="?route=project&id=<?php echo htmlspecialchars($projectDocument['projectId'] ?? ''); ?>" class="clio-btn-secondary">Back to Matter</a>
    </div>
</form>