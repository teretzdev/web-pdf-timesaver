<?php
$templateFields = is_array($template['fields'] ?? null) ? $template['fields'] : [];
?>

<div class="pdftimesaver-card">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
        <div>
            <h2 style="margin:0;">Client Field Mapping</h2>
            <p style="margin:4px 0 0 0;color:#6c757d;"><?php echo htmlspecialchars((string)($client['displayName'] ?? 'Client')); ?></p>
        </div>
        <a href="?route=client&id=<?php echo htmlspecialchars((string)$client['id']); ?>" class="pdftimesaver-btn-secondary">← Back to Client</a>
    </div>
</div>

<div class="pdftimesaver-card" style="margin-top:12px;">
    <form method="get" action="">
        <input type="hidden" name="route" value="client-mapping">
        <input type="hidden" name="id" value="<?php echo htmlspecialchars((string)$client['id']); ?>">
        <label class="pdftimesaver-form-label" for="template-select">Template</label>
        <div style="display:flex;gap:8px;align-items:center;max-width:700px;">
            <select id="template-select" name="templateId" class="pdftimesaver-input" style="flex:1;">
                <?php foreach ($availableTemplates as $tid => $tpl): ?>
                    <?php $optId = (string)($tpl['id'] ?? $tid); ?>
                    <option value="<?php echo htmlspecialchars($optId); ?>" <?php echo $optId === (string)$templateId ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars((string)($tpl['code'] ?? $optId) . ' — ' . (string)($tpl['name'] ?? '')); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="pdftimesaver-btn-secondary">Load</button>
        </div>
    </form>
</div>

<div class="pdftimesaver-card" style="margin-top:12px;">
    <h3 style="margin-top:0;">Map template fields to client profile</h3>
    <p style="color:#6c757d;">These mappings are applied when adding a new document for this client and template.</p>
    <?php if (empty($templateFields)): ?>
        <p style="color:#856404;">No template fields found. Run extraction for this template first.</p>
    <?php else: ?>
    <form method="post" action="?route=actions/save-client-field-mapping">
        <input type="hidden" name="clientId" value="<?php echo htmlspecialchars((string)$client['id']); ?>">
        <input type="hidden" name="templateId" value="<?php echo htmlspecialchars((string)$templateId); ?>">
        <div class="table-responsive">
            <table class="pdftimesaver-table">
                <thead>
                    <tr>
                        <th>Template Field</th>
                        <th>Fill From Client</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($templateFields as $field): ?>
                    <?php $fieldKey = (string)($field['key'] ?? ''); if ($fieldKey === '') { continue; } ?>
                    <tr>
                        <td><code><?php echo htmlspecialchars($fieldKey); ?></code></td>
                        <td>
                            <?php $current = (string)($mapping[$fieldKey] ?? ''); ?>
                            <select class="pdftimesaver-input" name="mapping[<?php echo htmlspecialchars($fieldKey); ?>]" style="max-width:280px;">
                                <option value="">(no mapping)</option>
                                <option value="displayName" <?php echo $current === 'displayName' ? 'selected' : ''; ?>>Client Name</option>
                                <option value="email" <?php echo $current === 'email' ? 'selected' : ''; ?>>Client Email</option>
                                <option value="phone" <?php echo $current === 'phone' ? 'selected' : ''; ?>>Client Phone</option>
                            </select>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div style="margin-top:12px;">
            <button type="submit" class="pdftimesaver-btn">Save Field Mappings</button>
        </div>
    </form>
    <?php endif; ?>
</div>

