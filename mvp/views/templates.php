<h2>Templates</h2>

<div class="clio-card" style="display: flex; gap: 16px; margin-bottom: 16px;">
    <a href="?route=projects" class="clio-btn-secondary">Back to Matters</a>
    <a href="?route=clients" class="clio-btn-secondary">Clients</a>
</div>

<div class="clio-card">
    <h3 style="margin: 0 0 16px 0;">Available Templates</h3>
    <p style="color: #6c757d; margin-bottom: 20px;">Manage document templates and their fields. Templates define the structure and fields for your legal documents.</p>

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
                    <strong><?php echo htmlspecialchars($template['code'] ?? ''); ?></strong>
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
                    <a href="?route=template-edit&id=<?php echo htmlspecialchars($template['id']); ?>" class="clio-btn-secondary">Edit Fields</a>
                    <a href="?route=projects" class="clio-btn-secondary">Use Template</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>

<?php if (empty($templates)): ?>
    <div class="clio-card" style="text-align: center; padding: 60px 20px;">
        <div style="font-size: 48px; margin-bottom: 16px;">📝</div>
        <h3 style="margin: 0 0 8px 0;">No Templates Available</h3>
        <p style="color: #6c757d;">Templates are defined in the system configuration. Contact your administrator to add new templates.</p>
    </div>
<?php endif; ?>

<div class="clio-card">
    <h3 style="margin: 0 0 16px 0;">Template Information</h3>
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
        <div>
            <h4 style="color: #2c3e50; margin-bottom: 12px;">What are Templates?</h4>
            <p style="color: #6c757d; margin-bottom: 12px;">Templates define the structure of your legal documents, including:</p>
            <ul style="color: #6c757d;">
                <li>Form fields and their types</li>
                <li>Panel organization</li>
                <li>PDF mapping information</li>
                <li>Validation rules</li>
            </ul>
        </div>
        <div>
            <h4 style="color: #2c3e50; margin-bottom: 12px;">Field Types</h4>
            <p style="color: #6c757d; margin-bottom: 12px;">Supported field types include:</p>
            <ul style="color: #6c757d;">
                <li><strong>Text:</strong> Single-line text input</li>
                <li><strong>Textarea:</strong> Multi-line text input</li>
                <li><strong>Number:</strong> Numeric input</li>
                <li><strong>Date:</strong> Date picker</li>
                <li><strong>Checkbox:</strong> Boolean checkbox</li>
                <li><strong>Select:</strong> Dropdown selection</li>
            </ul>
        </div>
    </div>
</div>


























