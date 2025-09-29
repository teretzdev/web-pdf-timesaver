<?php $tpl = $template; ?>
<h2>Populate — <?php echo htmlspecialchars(($tpl['code'] ?? '') . ' ' . ($tpl['name'] ?? '')); ?></h2>

<form method="post" action="?route=actions/save-fields">
    <input type="hidden" name="projectDocumentId" value="<?php echo htmlspecialchars($projectDocument['id']); ?>">

    <?php if (!empty($tpl['panels'])): ?>
        <?php foreach ($tpl['panels'] as $panel): ?>
            <div class="panel">
                <h3><?php echo htmlspecialchars($panel['label']); ?></h3>
                <div class="grid">
                    <?php foreach ($tpl['fields'] as $field): if (($field['panelId'] ?? '') !== $panel['id']) continue; ?>
                        <div>
                            <div class="muted" style="margin-bottom:6px;">
                                <?php echo htmlspecialchars($field['label']); ?>
                            </div>
                            <?php $type = $field['type'] ?? 'text'; $val = $values[$field['key']] ?? ''; ?>
                            <?php if ($type === 'textarea'): ?>
                                <textarea name="<?php echo htmlspecialchars($field['key']); ?>" rows="3" style="width:100%; padding:10px; border:1px solid #d7dce3; border-radius:8px;"><?php echo htmlspecialchars((string)$val); ?></textarea>
                            <?php elseif ($type === 'number' || $type === 'date'): ?>
                                <input type="<?php echo $type==='number'?'number':'date'; ?>" name="<?php echo htmlspecialchars($field['key']); ?>" value="<?php echo htmlspecialchars((string)$val); ?>" style="width:100%; padding:10px; border:1px solid #d7dce3; border-radius:8px;">
                            <?php elseif ($type === 'checkbox'): ?>
                                <input type="checkbox" name="<?php echo htmlspecialchars($field['key']); ?>" value="1" <?php echo !empty($val)?'checked':''; ?>>
                            <?php elseif ($type === 'select' && !empty($field['options']) && is_array($field['options'])): ?>
                                <select name="<?php echo htmlspecialchars($field['key']); ?>" style="width:100%; padding:10px; border:1px solid #d7dce3; border-radius:8px;">
                                    <?php foreach ($field['options'] as $opt): ?>
                                        <option value="<?php echo htmlspecialchars((string)$opt); ?>" <?php echo ((string)$val)===(string)$opt?'selected':''; ?>><?php echo htmlspecialchars((string)$opt); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <input type="text" name="<?php echo htmlspecialchars($field['key']); ?>" value="<?php echo htmlspecialchars((string)$val); ?>">
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <button class="btn" type="submit">Save</button>
    <a class="btn secondary" href="?route=project&id=<?php echo htmlspecialchars($projectDocument['projectId']); ?>">Back to project</a>
</form>

