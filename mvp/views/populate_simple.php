<?php 
// SIMPLE FL-100 FORM - 1:1 CLIO CLONE WITHOUT IMPROVEMENTS
$tpl = $template; 
?>

<h2><?php echo htmlspecialchars(($tpl['code'] ?? '') . ' ' . ($tpl['name'] ?? '')); ?></h2>

<form method="post" action="?route=actions/save-fields" id="populate-form">
    <input type="hidden" name="projectDocumentId" value="<?php echo htmlspecialchars($projectDocument['id']); ?>">

    <?php if (!empty($tpl['panels'])): ?>
        <?php foreach ($tpl['panels'] as $panel): ?>
            <div class="panel">
                <h3><?php echo htmlspecialchars($panel['label']); ?></h3>
                <div class="grid">
                    <?php foreach ($tpl['fields'] as $field): if (($field['panelId'] ?? '') !== $panel['id']) continue; ?>
                        <div>
                            <label><?php echo htmlspecialchars($field['label']); ?></label>
                            <?php 
                            $type = $field['type'] ?? 'text'; 
                            $val = $values[$field['key']] ?? ''; 
                            $placeholder = $field['placeholder'] ?? '';
                            $required = !empty($field['required']) ? 'required' : '';
                            ?>
                            
                            <?php if ($type === 'textarea'): ?>
                                <textarea name="<?php echo htmlspecialchars($field['key']); ?>" rows="3" placeholder="<?php echo htmlspecialchars($placeholder); ?>" <?php echo $required; ?>><?php echo htmlspecialchars((string)$val); ?></textarea>
                            <?php elseif ($type === 'number'): ?>
                                <input type="number" name="<?php echo htmlspecialchars($field['key']); ?>" value="<?php echo htmlspecialchars((string)$val); ?>" placeholder="<?php echo htmlspecialchars($placeholder); ?>" <?php echo $required; ?>>
                            <?php elseif ($type === 'date'): ?>
                                <input type="date" name="<?php echo htmlspecialchars($field['key']); ?>" value="<?php echo htmlspecialchars((string)$val); ?>" <?php echo $required; ?>>
                            <?php elseif ($type === 'checkbox'): ?>
                                <input type="hidden" name="<?php echo htmlspecialchars($field['key']); ?>" value="0">
                                <input type="checkbox" name="<?php echo htmlspecialchars($field['key']); ?>" value="1" <?php echo !empty($val)?'checked':''; ?> <?php echo $required; ?>>
                            <?php elseif ($type === 'select' && !empty($field['options'])): ?>
                                <select name="<?php echo htmlspecialchars($field['key']); ?>" <?php echo $required; ?>>
                                    <option value=""><?php echo htmlspecialchars($placeholder ?: 'Select...'); ?></option>
                                    <?php foreach ($field['options'] as $opt): ?>
                                        <option value="<?php echo htmlspecialchars((string)$opt); ?>" <?php echo ((string)$val)===(string)$opt?'selected':''; ?>><?php echo htmlspecialchars((string)$opt); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <input type="text" name="<?php echo htmlspecialchars($field['key']); ?>" value="<?php echo htmlspecialchars((string)$val); ?>" placeholder="<?php echo htmlspecialchars($placeholder); ?>" <?php echo $required; ?>>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <div style="margin-top: 20px;">
        <button type="submit" class="btn">Save</button>
        <a href="?route=project&id=<?php echo htmlspecialchars($projectDocument['projectId']); ?>" class="btn secondary">Cancel</a>
    </div>
</form>

<style>
/* Basic Clio-like styling - exact clone, no extras */
.panel {
    background: #fff;
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.panel h3 {
    margin: 0 0 20px 0;
    color: #2c3e50;
    font-size: 18px;
    font-weight: 600;
    border-bottom: 1px solid #e1e5e9;
    padding-bottom: 10px;
}

.grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.grid > div {
    display: flex;
    flex-direction: column;
}

.grid label {
    margin-bottom: 6px;
    color: #495057;
    font-size: 14px;
    font-weight: 500;
}

input[type="text"],
input[type="number"],
input[type="date"],
textarea,
select {
    padding: 8px 12px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 14px;
    font-family: inherit;
    width: 100%;
}

textarea {
    resize: vertical;
}

input[type="checkbox"] {
    width: auto;
    margin-right: 8px;
}

.btn {
    padding: 10px 20px;
    background: #0b6bcb;
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 14px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    margin-right: 10px;
}

.btn.secondary {
    background: #6c757d;
}

.btn:hover {
    opacity: 0.9;
}

/* Required field indicator */
input[required],
textarea[required],
select[required] {
    border-left: 3px solid #dc3545;
}
</style>