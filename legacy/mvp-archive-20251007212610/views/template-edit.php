<h2>Edit Template — <?php echo htmlspecialchars($template['code'] ?? ''); ?> <?php echo htmlspecialchars($template['name'] ?? ''); ?></h2>

<div class="row" style="gap: 16px; margin-bottom: 16px;">
    <a href="?route=templates" class="btn secondary">Back to Templates</a>
    <a href="?route=projects" class="btn secondary">Projects</a>
</div>

<div class="panel">
    <h3>Template Information</h3>
    <div class="grid">
        <div>
            <strong>Code:</strong> <?php echo htmlspecialchars($template['code'] ?? ''); ?>
        </div>
        <div>
            <strong>Name:</strong> <?php echo htmlspecialchars($template['name'] ?? ''); ?>
        </div>
        <div>
            <strong>Version:</strong> <?php echo htmlspecialchars($template['version'] ?? '1.0'); ?>
        </div>
        <div>
            <strong>Fields:</strong> <?php echo count($template['fields'] ?? []); ?>
        </div>
    </div>
</div>

<div class="panel">
    <h3>Panels</h3>
    <p class="muted">Panels organize fields into logical groups in the form interface.</p>
    
    <?php if (!empty($template['panels'])): ?>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Label</th>
                    <th>Order</th>
                    <th>Fields</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($template['panels'] as $panel): ?>
                    <tr>
                        <td><code><?php echo htmlspecialchars($panel['id']); ?></code></td>
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
    <?php else: ?>
        <div class="muted">No panels defined for this template.</div>
    <?php endif; ?>
</div>

<div class="panel">
    <h3>Fields</h3>
    <p class="muted">Fields define the data that can be entered for this template.</p>
    
    <?php if (!empty($template['fields'])): ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Key</th>
                    <th>Label</th>
                    <th>Type</th>
                    <th>Panel</th>
                    <th>Required</th>
                    <th>PDF Target</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($template['fields'] as $field): ?>
                    <tr>
                        <td><code><?php echo htmlspecialchars($field['key']); ?></code></td>
                        <td><?php echo htmlspecialchars($field['label']); ?></td>
                        <td>
                            <span style="background: #eef2f7; padding: 2px 6px; border-radius: 4px; font-size: 12px;">
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
                                <span style="color: #dc3545;">Required</span>
                            <?php else: ?>
                                <span class="muted">Optional</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($field['pdfTarget']['formField'])): ?>
                                <code><?php echo htmlspecialchars($field['pdfTarget']['formField']); ?></code>
                            <?php elseif (!empty($field['pdfTarget']['page'])): ?>
                                Page <?php echo htmlspecialchars($field['pdfTarget']['page']); ?>, 
                                (<?php echo htmlspecialchars($field['pdfTarget']['x'] ?? 0); ?>, 
                                <?php echo htmlspecialchars($field['pdfTarget']['y'] ?? 0); ?>)
                            <?php else: ?>
                                <span class="muted">Not mapped</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="muted">No fields defined for this template.</div>
    <?php endif; ?>
</div>

<div class="panel">
    <h3>Field Details</h3>
    <p class="muted">Detailed view of all fields with their properties.</p>
    
    <?php if (!empty($template['fields'])): ?>
        <?php foreach ($template['fields'] as $field): ?>
            <div style="border: 1px solid #eef2f7; border-radius: 8px; padding: 16px; margin-bottom: 12px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                    <h4 style="margin: 0;"><?php echo htmlspecialchars($field['label']); ?></h4>
                    <span style="background: #eef2f7; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                        <?php echo htmlspecialchars($field['type'] ?? 'text'); ?>
                    </span>
                </div>
                
                <div class="grid">
                    <div>
                        <strong>Key:</strong> <code><?php echo htmlspecialchars($field['key']); ?></code>
                    </div>
                    <div>
                        <strong>Panel:</strong> 
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
                    </div>
                    <div>
                        <strong>Required:</strong> 
                        <?php echo !empty($field['required']) ? 'Yes' : 'No'; ?>
                    </div>
                    <div>
                        <strong>Placeholder:</strong> 
                        <?php echo htmlspecialchars($field['placeholder'] ?? 'None'); ?>
                    </div>
                </div>
                
                <?php if (!empty($field['options'])): ?>
                    <div style="margin-top: 8px;">
                        <strong>Options:</strong>
                        <ul style="margin: 4px 0 0 0; padding-left: 20px;">
                            <?php foreach ($field['options'] as $option): ?>
                                <li><?php echo htmlspecialchars($option); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($field['pdfTarget'])): ?>
                    <div style="margin-top: 8px;">
                        <strong>PDF Mapping:</strong>
                        <?php if (!empty($field['pdfTarget']['formField'])): ?>
                            Form field: <code><?php echo htmlspecialchars($field['pdfTarget']['formField']); ?></code>
                        <?php elseif (!empty($field['pdfTarget']['page'])): ?>
                            Position: Page <?php echo htmlspecialchars($field['pdfTarget']['page']); ?>, 
                            (<?php echo htmlspecialchars($field['pdfTarget']['x'] ?? 0); ?>, 
                            <?php echo htmlspecialchars($field['pdfTarget']['y'] ?? 0); ?>)
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="panel">
    <h3>Usage</h3>
    <p class="muted">This template can be used in projects to create documents with the fields defined above.</p>
    <a href="?route=projects" class="btn">Create New Project</a>
</div>
























