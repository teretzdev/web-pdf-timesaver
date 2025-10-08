<h2>Templates</h2>

<div class="row" style="gap: 16px; margin-bottom: 16px;">
    <a href="?route=projects" class="btn secondary">Back to Projects</a>
    <a href="?route=clients" class="btn secondary">Clients</a>
    <a href="?route=support" class="btn secondary">Help & Support</a>
</div>

<div class="panel">
    <h3>Available Templates</h3>
    <p class="muted">Manage document templates and their fields. Templates define the structure and fields for your legal documents.</p>
</div>

<table class="table">
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
                    <span class="muted"><?php echo count($template['fields'] ?? []); ?> fields</span>
                </td>
                <td>
                    <span class="muted"><?php echo count($template['panels'] ?? []); ?> panels</span>
                </td>
                <td>
                    <a href="?route=template-edit&id=<?php echo htmlspecialchars($template['id']); ?>" class="btn secondary">Edit Fields</a>
                    <a href="?route=projects" class="btn secondary">Use Template</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php if (empty($templates)): ?>
    <div class="panel">
        <div style="text-align: center; color: #666; padding: 40px;">
            <h3>No Templates Available</h3>
            <p>Templates are defined in the system configuration. Contact your administrator to add new templates.</p>
        </div>
    </div>
<?php endif; ?>

<div class="panel">
    <h3>Template Information</h3>
    <div class="grid">
        <div>
            <h4>What are Templates?</h4>
            <p class="muted">Templates define the structure of your legal documents, including:</p>
            <ul class="muted">
                <li>Form fields and their types</li>
                <li>Panel organization</li>
                <li>PDF mapping information</li>
                <li>Validation rules</li>
            </ul>
        </div>
        <div>
            <h4>Field Types</h4>
            <p class="muted">Supported field types include:</p>
            <ul class="muted">
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


























