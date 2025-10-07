<div class="clio-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <div>
            <h2 style="margin: 0 0 6px 0; color: #2c3e50; font-size: 24px; font-weight: 700;">
                <?php echo htmlspecialchars($template['code'] ?? ''); ?> — <?php echo htmlspecialchars($template['name'] ?? ''); ?>
            </h2>
            <p style="margin: 0; color: #6c757d; font-size: 14px;">Template details and field configuration</p>
        </div>
        <div>
            <a href="?route=templates" class="clio-btn-secondary">← Back to Templates</a>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 24px; margin-bottom: 32px;">
    <div class="clio-card">
        <h3 style="margin: 0 0 16px 0; color: #2c3e50; font-size: 18px; font-weight: 600;">Template Info</h3>
        <div style="margin-bottom: 12px;">
            <div style="color: #6c757d; font-size: 12px; margin-bottom: 4px;">Code</div>
            <div style="font-weight: 600; color: #2c3e50;"><?php echo htmlspecialchars($template['code'] ?? ''); ?></div>
        </div>
        <div style="margin-bottom: 12px;">
            <div style="color: #6c757d; font-size: 12px; margin-bottom: 4px;">Version</div>
            <div style="font-weight: 600; color: #2c3e50;"><?php echo htmlspecialchars($template['version'] ?? '1.0'); ?></div>
        </div>
    </div>
    
    <div class="clio-card">
        <h3 style="margin: 0 0 16px 0; color: #2c3e50; font-size: 18px; font-weight: 600;">Structure</h3>
        <div style="margin-bottom: 12px;">
            <div style="color: #6c757d; font-size: 12px; margin-bottom: 4px;">Total Fields</div>
            <div style="font-weight: 600; color: #2c3e50;"><?php echo count($template['fields'] ?? []); ?> fields</div>
        </div>
        <div style="margin-bottom: 12px;">
            <div style="color: #6c757d; font-size: 12px; margin-bottom: 4px;">Panels</div>
            <div style="font-weight: 600; color: #2c3e50;"><?php echo count($template['panels'] ?? []); ?> panels</div>
        </div>
    </div>
</div>

<?php if (!empty($template['panels'])): ?>
<div class="clio-card">
    <h3 style="margin: 0 0 24px 0; color: #2c3e50; font-size: 20px; font-weight: 600;">Panels</h3>
    <table class="clio-table">
        <thead>
            <tr>
                <th>Panel Name</th>
                <th>Order</th>
                <th>Fields</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($template['panels'] as $panel): ?>
                <tr>
                    <td><?php echo htmlspecialchars($panel['label']); ?></td>
                    <td><?php echo htmlspecialchars($panel['order'] ?? 0); ?></td>
                    <td>
                        <?php 
                        $fieldCount = 0;
                        foreach ($template['fields'] ?? [] as $field) {
                            if (($field['panelId'] ?? '') === $panel['id']) {
                                $fieldCount++;
                            }
                        }
                        echo $fieldCount;
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php if (!empty($template['fields'])): ?>
<div class="clio-card">
    <h3 style="margin: 0 0 24px 0; color: #2c3e50; font-size: 20px; font-weight: 600;">Fields</h3>
    <table class="clio-table">
        <thead>
            <tr>
                <th>Field Name</th>
                <th>Type</th>
                <th>Panel</th>
                <th>Required</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($template['fields'] as $field): ?>
                <tr>
                    <td>
                        <div style="font-weight: 600; color: #2c3e50;">
                            <?php echo htmlspecialchars($field['label']); ?>
                        </div>
                        <div style="font-size: 12px; color: #6c757d;">
                            <?php echo htmlspecialchars($field['key']); ?>
                        </div>
                    </td>
                    <td>
                        <span style="background: #f8f9fa; color: #495057; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                            <?php echo htmlspecialchars($field['type'] ?? 'text'); ?>
                        </span>
                    </td>
                    <td>
                        <?php 
                        $panelLabel = '';
                        foreach ($template['panels'] ?? [] as $panel) {
                            if ($panel['id'] === ($field['panelId'] ?? '')) {
                                $panelLabel = $panel['label'];
                                break;
                            }
                        }
                        echo htmlspecialchars($panelLabel ?: 'No panel');
                        ?>
                    </td>
                    <td>
                        <?php if (!empty($field['required'])): ?>
                            <span style="color: #dc3545; font-weight: 500;">Required</span>
                        <?php else: ?>
                            <span style="color: #6c757d;">Optional</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>