<?php 
// EXACT CLIO DRAFT UI CLONE - Based on draft.clio.com/panels/edit/
$tpl = $template; 
?>

<style>
/* Clio Draft exact styling */
body {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    background: #f5f5f5;
    margin: 0;
    padding: 0;
}

.clio-container {
    max-width: 1200px;
    margin: 0 auto;
    background: white;
    min-height: 100vh;
}

.clio-header {
    background: white;
    border-bottom: 1px solid #e0e0e0;
    padding: 15px 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.clio-title {
    font-size: 20px;
    color: #333;
    margin: 0;
    font-weight: 500;
}

.clio-actions {
    display: flex;
    gap: 10px;
}

.clio-main {
    display: flex;
    height: calc(100vh - 60px);
}

.clio-sidebar {
    width: 250px;
    background: #fafafa;
    border-right: 1px solid #e0e0e0;
    overflow-y: auto;
}

.clio-panel-list {
    padding: 0;
    margin: 0;
    list-style: none;
}

.clio-panel-item {
    padding: 12px 20px;
    cursor: pointer;
    border-bottom: 1px solid #e0e0e0;
    color: #333;
    font-size: 14px;
}

.clio-panel-item:hover {
    background: #f0f0f0;
}

.clio-panel-item.active {
    background: white;
    font-weight: 600;
    border-left: 3px solid #4a90e2;
}

.clio-content {
    flex: 1;
    padding: 20px;
    overflow-y: auto;
}

.clio-panel {
    display: none;
}

.clio-panel.active {
    display: block;
}

.clio-panel-title {
    font-size: 18px;
    color: #333;
    margin: 0 0 20px 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #e0e0e0;
}

.clio-field {
    margin-bottom: 20px;
}

.clio-field label {
    display: block;
    margin-bottom: 5px;
    color: #333;
    font-size: 14px;
    font-weight: 500;
}

.clio-field input[type="text"],
.clio-field input[type="number"],
.clio-field input[type="date"],
.clio-field textarea,
.clio-field select {
    width: 100%;
    padding: 8px 10px;
    border: 1px solid #d0d0d0;
    border-radius: 3px;
    font-size: 14px;
    font-family: inherit;
}

.clio-field input:focus,
.clio-field textarea:focus,
.clio-field select:focus {
    outline: none;
    border-color: #4a90e2;
}

.clio-field textarea {
    min-height: 80px;
    resize: vertical;
}

.clio-checkbox {
    display: flex;
    align-items: center;
    margin-bottom: 15px;
}

.clio-checkbox input[type="checkbox"] {
    margin-right: 8px;
}

.clio-checkbox label {
    margin: 0;
    cursor: pointer;
}

.clio-required {
    color: #e74c3c;
    margin-left: 3px;
}

.clio-save-bar {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: white;
    border-top: 1px solid #e0e0e0;
    padding: 15px;
    display: flex;
    justify-content: center;
    gap: 10px;
    box-shadow: 0 -2px 4px rgba(0,0,0,0.1);
}

.clio-btn {
    padding: 10px 24px;
    border: none;
    border-radius: 3px;
    font-size: 14px;
    cursor: pointer;
    font-weight: 500;
    transition: background 0.2s;
}

.clio-btn-primary {
    background: #4a90e2;
    color: white;
}

.clio-btn-primary:hover {
    background: #357abd;
}

.clio-btn-secondary {
    background: white;
    color: #333;
    border: 1px solid #d0d0d0;
}

.clio-btn-secondary:hover {
    background: #f5f5f5;
}
</style>

<div class="clio-container">
    <div class="clio-header">
        <h1 class="clio-title"><?php echo htmlspecialchars(($tpl['code'] ?? '') . ' - ' . ($tpl['name'] ?? '')); ?></h1>
        <div class="clio-actions">
            <button type="button" onclick="window.location='?route=project&id=<?php echo htmlspecialchars($projectDocument['projectId']); ?>'" class="clio-btn clio-btn-secondary">Back to Project</button>
        </div>
    </div>

    <form method="post" action="?route=actions/save-fields" id="clio-form">
        <input type="hidden" name="projectDocumentId" value="<?php echo htmlspecialchars($projectDocument['id']); ?>">
        
        <div class="clio-main">
            <!-- Sidebar with panel navigation -->
            <div class="clio-sidebar">
                <ul class="clio-panel-list">
                    <?php foreach ($tpl['panels'] as $index => $panel): ?>
                        <li class="clio-panel-item <?php echo $index === 0 ? 'active' : ''; ?>" 
                            onclick="switchPanel('<?php echo htmlspecialchars($panel['id']); ?>', this)">
                            <?php echo htmlspecialchars($panel['label']); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Content area with fields -->
            <div class="clio-content">
                <?php foreach ($tpl['panels'] as $index => $panel): ?>
                    <div class="clio-panel <?php echo $index === 0 ? 'active' : ''; ?>" id="panel-<?php echo htmlspecialchars($panel['id']); ?>">
                        <h2 class="clio-panel-title"><?php echo htmlspecialchars($panel['label']); ?></h2>
                        
                        <?php foreach ($tpl['fields'] as $field): 
                            if (($field['panelId'] ?? '') !== $panel['id']) continue;
                            $type = $field['type'] ?? 'text'; 
                            $val = $values[$field['key']] ?? ''; 
                            $placeholder = $field['placeholder'] ?? '';
                            $required = !empty($field['required']);
                        ?>
                            <?php if ($type === 'checkbox'): ?>
                                <div class="clio-checkbox">
                                    <input type="hidden" name="<?php echo htmlspecialchars($field['key']); ?>" value="0">
                                    <input type="checkbox" 
                                           id="<?php echo htmlspecialchars($field['key']); ?>"
                                           name="<?php echo htmlspecialchars($field['key']); ?>" 
                                           value="1" 
                                           <?php echo !empty($val) ? 'checked' : ''; ?>>
                                    <label for="<?php echo htmlspecialchars($field['key']); ?>">
                                        <?php echo htmlspecialchars($field['label']); ?>
                                    </label>
                                </div>
                            <?php else: ?>
                                <div class="clio-field">
                                    <label for="<?php echo htmlspecialchars($field['key']); ?>">
                                        <?php echo htmlspecialchars($field['label']); ?>
                                        <?php if ($required): ?><span class="clio-required">*</span><?php endif; ?>
                                    </label>
                                    
                                    <?php if ($type === 'textarea'): ?>
                                        <textarea id="<?php echo htmlspecialchars($field['key']); ?>"
                                                  name="<?php echo htmlspecialchars($field['key']); ?>" 
                                                  placeholder="<?php echo htmlspecialchars($placeholder); ?>"
                                                  <?php echo $required ? 'required' : ''; ?>><?php echo htmlspecialchars((string)$val); ?></textarea>
                                    <?php elseif ($type === 'select' && !empty($field['options'])): ?>
                                        <select id="<?php echo htmlspecialchars($field['key']); ?>"
                                                name="<?php echo htmlspecialchars($field['key']); ?>" 
                                                <?php echo $required ? 'required' : ''; ?>>
                                            <option value=""><?php echo htmlspecialchars($placeholder ?: '-- Select --'); ?></option>
                                            <?php foreach ($field['options'] as $opt): ?>
                                                <option value="<?php echo htmlspecialchars((string)$opt); ?>" 
                                                        <?php echo ((string)$val)===(string)$opt ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars((string)$opt); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php else: ?>
                                        <input type="<?php echo htmlspecialchars($type); ?>" 
                                               id="<?php echo htmlspecialchars($field['key']); ?>"
                                               name="<?php echo htmlspecialchars($field['key']); ?>" 
                                               value="<?php echo htmlspecialchars((string)$val); ?>" 
                                               placeholder="<?php echo htmlspecialchars($placeholder); ?>"
                                               <?php echo $required ? 'required' : ''; ?>>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="clio-save-bar">
            <button type="button" onclick="window.location='?route=project&id=<?php echo htmlspecialchars($projectDocument['projectId']); ?>'" class="clio-btn clio-btn-secondary">Cancel</button>
            <button type="submit" class="clio-btn clio-btn-primary">Save</button>
        </div>
    </form>
</div>

<script>
function switchPanel(panelId, element) {
    // Hide all panels
    document.querySelectorAll('.clio-panel').forEach(panel => {
        panel.classList.remove('active');
    });
    
    // Remove active from all sidebar items
    document.querySelectorAll('.clio-panel-item').forEach(item => {
        item.classList.remove('active');
    });
    
    // Show selected panel
    document.getElementById('panel-' + panelId).classList.add('active');
    
    // Mark sidebar item as active
    element.classList.add('active');
}
</script>