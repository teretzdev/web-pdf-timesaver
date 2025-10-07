<?php 
$tpl = $template; 

// Get project and client info
$project = null;
$client = null;
if (!empty($projectDocument['projectId'])) {
    $project = $store->getProject($projectDocument['projectId']);
    if ($project && !empty($project['clientId']) && $store && method_exists($store, 'getClient')) {
        $client = $store->getClient($project['clientId']);
    }
}
?>

<div class="clio-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <div>
            <h2 style="margin: 0 0 6px 0; color: #2c3e50; font-size: 24px; font-weight: 700;">
                <?php echo htmlspecialchars(($tpl['code'] ?? '') . ' — ' . ($tpl['name'] ?? '')); ?>
            </h2>
            <p style="margin: 0; color: #6c757d; font-size: 14px;">Complete the form fields below</p>
        </div>
        <div style="display: flex; gap: 8px;">
            <button type="submit" form="populate-form" class="clio-btn">Save Form</button>
            <a href="?route=project&id=<?php echo htmlspecialchars($projectDocument['projectId']); ?>" class="clio-btn-secondary">Cancel</a>
        </div>
    </div>
</div>

<?php if (isset($_GET['saved']) && $_GET['saved'] === '1'): ?>
    <div class="clio-card" style="background: #d4edda; border-color: #c3e6cb;">
        <div style="display: flex; align-items: center; gap: 8px; color: #155724;">
            <span>✅</span>
            <span>Form data saved successfully!</span>
        </div>
    </div>
<?php endif; ?>

<form method="post" action="?route=actions/save-fields" id="populate-form">
    <input type="hidden" name="projectDocumentId" value="<?php echo htmlspecialchars($projectDocument['id']); ?>">

    <?php if (!empty($tpl['panels'])): ?>
        <?php foreach ($tpl['panels'] as $panel): ?>
            <div class="clio-card">
                <h3 style="margin: 0 0 20px 0; color: #2c3e50; font-size: 18px; font-weight: 600;">
                    <?php echo htmlspecialchars($panel['label']); ?>
                </h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                    <?php foreach ($tpl['fields'] as $field): if (($field['panelId'] ?? '') !== $panel['id']) continue; ?>
                        <div class="clio-form-group">
                            <label class="clio-form-label">
                                <?php echo htmlspecialchars($field['label']); ?>
                                <?php if (!empty($field['required'])): ?>
                                    <span style="color: #dc3545;">*</span>
                                <?php endif; ?>
                            </label>
                            <?php 
                            $type = $field['type'] ?? 'text'; 
                            $val = $values[$field['key']] ?? ''; 
                            $placeholder = $field['placeholder'] ?? '';
                            $required = !empty($field['required']) ? 'required' : '';
                            ?>
                            <?php if ($type === 'textarea'): ?>
                                <textarea 
                                    name="<?php echo htmlspecialchars($field['key']); ?>" 
                                    rows="3" 
                                    placeholder="<?php echo htmlspecialchars($placeholder); ?>" 
                                    <?php echo $required; ?> 
                                    class="clio-input" 
                                    style="resize: vertical;">
<?php echo htmlspecialchars((string)$val); ?></textarea>
                            <?php elseif ($type === 'number' || $type === 'date'): ?>
                                <input 
                                    type="<?php echo $type === 'number' ? 'number' : 'date'; ?>" 
                                    name="<?php echo htmlspecialchars($field['key']); ?>" 
                                    value="<?php echo htmlspecialchars((string)$val); ?>" 
                                    placeholder="<?php echo htmlspecialchars($placeholder); ?>" 
                                    <?php echo $required; ?> 
                                    class="clio-input">
                            <?php elseif ($type === 'checkbox'): ?>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                    <input type="hidden" name="<?php echo htmlspecialchars($field['key']); ?>" value="0">
                                    <input 
                                        type="checkbox" 
                                        name="<?php echo htmlspecialchars($field['key']); ?>" 
                                        value="1" 
                                        <?php echo !empty($val) ? 'checked' : ''; ?> 
                                        <?php echo $required; ?>>
                                    <span><?php echo !empty($val) ? 'Yes' : 'No'; ?></span>
                                </label>
                            <?php elseif ($type === 'select' && !empty($field['options']) && is_array($field['options'])): ?>
                                <select 
                                    name="<?php echo htmlspecialchars($field['key']); ?>" 
                                    <?php echo $required; ?> 
                                    class="clio-input">
                                    <option value=""><?php echo htmlspecialchars($placeholder ?: 'Select an option'); ?></option>
                                    <?php foreach ($field['options'] as $opt): ?>
                                        <option value="<?php echo htmlspecialchars((string)$opt); ?>" <?php echo ((string)$val) === (string)$opt ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars((string)$opt); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <input 
                                    type="text" 
                                    name="<?php echo htmlspecialchars($field['key']); ?>" 
                                    value="<?php echo htmlspecialchars((string)$val); ?>" 
                                    placeholder="<?php echo htmlspecialchars($placeholder); ?>" 
                                    <?php echo $required; ?> 
                                    class="clio-input">
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Custom Fields Section -->
    <?php if (!empty($customFields)): ?>
        <div class="clio-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="margin: 0; color: #2c3e50; font-size: 18px; font-weight: 600;">Additional Fields</h3>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                <?php foreach ($customFields as $customField): ?>
                    <div class="clio-form-group">
                        <label class="clio-form-label">
                            <?php echo htmlspecialchars($customField['label']); ?>
                            <?php if (!empty($customField['required'])): ?>
                                <span style="color: #dc3545;">*</span>
                            <?php endif; ?>
                        </label>
                        <?php 
                        $customKey = 'custom_' . $customField['id'];
                        $customVal = $values[$customKey] ?? '';
                        $customType = $customField['type'] ?? 'text';
                        $customPlaceholder = $customField['placeholder'] ?? '';
                        $customRequired = !empty($customField['required']) ? 'required' : '';
                        ?>
                        <?php if ($customType === 'textarea'): ?>
                            <textarea 
                                name="<?php echo htmlspecialchars($customKey); ?>" 
                                rows="3" 
                                placeholder="<?php echo htmlspecialchars($customPlaceholder); ?>" 
                                <?php echo $customRequired; ?> 
                                class="clio-input" 
                                style="resize: vertical;">
<?php echo htmlspecialchars((string)$customVal); ?></textarea>
                        <?php elseif ($customType === 'number' || $customType === 'date'): ?>
                            <input 
                                type="<?php echo $customType === 'number' ? 'number' : 'date'; ?>" 
                                name="<?php echo htmlspecialchars($customKey); ?>" 
                                value="<?php echo htmlspecialchars((string)$customVal); ?>" 
                                placeholder="<?php echo htmlspecialchars($customPlaceholder); ?>" 
                                <?php echo $customRequired; ?> 
                                class="clio-input">
                        <?php elseif ($customType === 'checkbox'): ?>
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                <input type="hidden" name="<?php echo htmlspecialchars($customKey); ?>" value="0">
                                <input 
                                    type="checkbox" 
                                    name="<?php echo htmlspecialchars($customKey); ?>" 
                                    value="1" 
                                    <?php echo !empty($customVal) ? 'checked' : ''; ?> 
                                    <?php echo $customRequired; ?>>
                                <span><?php echo !empty($customVal) ? 'Yes' : 'No'; ?></span>
                            </label>
                        <?php else: ?>
                            <input 
                                type="text" 
                                name="<?php echo htmlspecialchars($customKey); ?>" 
                                value="<?php echo htmlspecialchars((string)$customVal); ?>" 
                                placeholder="<?php echo htmlspecialchars($customPlaceholder); ?>" 
                                <?php echo $customRequired; ?> 
                                class="clio-input">
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <div style="display: flex; gap: 8px; margin-top: 24px;">
        <button type="submit" class="clio-btn">Save Changes</button>
        <a href="?route=actions/generate&pd=<?php echo htmlspecialchars($projectDocument['id']); ?>" class="clio-btn-secondary">Generate PDF</a>
        <a href="?route=project&id=<?php echo htmlspecialchars($projectDocument['projectId']); ?>" class="clio-btn-secondary">Back to Matter</a>
    </div>
</form>

<script>
// Add brightness effect when typing
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('input[type="text"], input[type="email"], input[type="tel"], input[type="number"], input[type="date"], textarea, select');
    
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.classList.add('keystroke-bright');
        });
        
        input.addEventListener('blur', function() {
            this.classList.remove('keystroke-bright');
        });
        
        input.addEventListener('input', function() {
            this.classList.add('keystroke-bright');
            setTimeout(() => {
                this.classList.remove('keystroke-bright');
            }, 200);
        });
    });
});
</script>