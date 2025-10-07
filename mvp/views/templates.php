<div class="clio-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <div>
            <h2 style="margin: 0 0 6px 0; color: #2c3e50; font-size: 24px; font-weight: 700;">Templates</h2>
            <p style="margin: 0; color: #6c757d; font-size: 14px;">Manage document templates and their fields</p>
        </div>
        <div style="display: flex; gap: 8px;">
            <a href="?route=projects" class="clio-btn-secondary">← Back to Matters</a>
        </div>
    </div>
</div>

<div class="clio-card">
    <table class="clio-table">
        <thead>
            <tr>
                <th>Code</th>
                <th>Name</th>
                <th>Version</th>
                <th>Fields</th>
                <th>Panels</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($templates as $template): ?>
                <tr>
                    <td>
                        <strong style="color: #2c3e50;"><?php echo htmlspecialchars($template['code'] ?? ''); ?></strong>
                    </td>
                    <td><?php echo htmlspecialchars($template['name'] ?? ''); ?></td>
                    <td><?php echo htmlspecialchars($template['version'] ?? '1.0'); ?></td>
                    <td>
                        <span style="color: #6c757d;"><?php echo count($template['fields'] ?? []); ?> fields</span>
                    </td>
                    <td>
                        <span style="color: #6c757d;"><?php echo count($template['panels'] ?? []); ?> panels</span>
                    </td>
                    <td>
                        <div style="display: flex; gap: 8px;">
                            <a href="?route=template-edit&id=<?php echo htmlspecialchars($template['id']); ?>" class="clio-btn-secondary" style="padding: 6px 12px; font-size: 12px;">View Details</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if (empty($templates)): ?>
        <div style="text-align: center; padding: 60px 20px;">
            <div style="font-size: 48px; margin-bottom: 16px;">📋</div>
            <h3 style="margin: 0 0 8px 0; color: #2c3e50; font-size: 20px;">No Templates Available</h3>
            <p style="margin: 0; color: #6c757d; font-size: 16px;">Templates are defined in the system configuration.</p>
        </div>
    <?php endif; ?>
</div>


























