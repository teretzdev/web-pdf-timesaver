<?php $tpl = $template; ?>
<h2>Preview — <?php echo htmlspecialchars(($tpl['code'] ?? '') . ' ' . ($tpl['name'] ?? '')); ?></h2>

<div class="row" style="gap: 16px; margin-bottom: 16px;">
    <a href="?route=populate&pd=<?php echo htmlspecialchars($projectDocument['id']); ?>" class="btn secondary">Edit Fields</a>
    <a href="?route=actions/generate&pd=<?php echo htmlspecialchars($projectDocument['id']); ?>" class="btn">Generate PDF</a>
    <a href="?route=project&id=<?php echo htmlspecialchars($projectDocument['projectId']); ?>" class="btn secondary">Back to Project</a>
</div>

<div class="panel">
    <h3>Document Preview</h3>
    <div style="background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 20px; min-height: 400px;">
        <?php if (!empty($tpl)): ?>
            <div style="font-family: 'Times New Roman', serif; line-height: 1.6; color: #333;">
                <!-- Document Header -->
                <div style="text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 15px;">
                    <h1 style="font-size: 18px; font-weight: bold; margin: 0;">
                        <?php echo htmlspecialchars($tpl['code'] ?? ''); ?>
                    </h1>
                    <h2 style="font-size: 16px; font-weight: normal; margin: 5px 0 0 0;">
                        <?php echo htmlspecialchars($tpl['name'] ?? ''); ?>
                    </h2>
                </div>

                <!-- Document Content -->
                <?php if (!empty($tpl['panels'])): ?>
                    <?php foreach ($tpl['panels'] as $panel): ?>
                        <div style="margin-bottom: 25px;">
                            <h3 style="font-size: 14px; font-weight: bold; margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.5px;">
                                <?php echo htmlspecialchars($panel['label']); ?>
                            </h3>
                            
                            <?php foreach ($tpl['fields'] as $field): ?>
                                <?php if (($field['panelId'] ?? '') !== $panel['id']) continue; ?>
                                <?php $val = $values[$field['key']] ?? ''; ?>
                                <div style="margin-bottom: 8px; display: flex; align-items: flex-start;">
                                    <div style="min-width: 150px; font-weight: bold; margin-right: 10px;">
                                        <?php echo htmlspecialchars($field['label']); ?>:
                                    </div>
                                    <div style="flex: 1; border-bottom: 1px solid #333; min-height: 20px; padding-bottom: 2px;">
                                        <?php if (!empty($val)): ?>
                                            <?php echo htmlspecialchars((string)$val); ?>
                                        <?php else: ?>
                                            <span style="color: #999; font-style: italic;">[Not filled]</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- Document Footer -->
                <div style="margin-top: 40px; border-top: 1px solid #ccc; padding-top: 15px; font-size: 12px; color: #666;">
                    <div style="display: flex; justify-content: space-between;">
                        <div>Generated: <?php echo date('F j, Y \a\t g:i A'); ?></div>
                        <div>Status: <?php echo htmlspecialchars($projectDocument['status'] ?? 'in_progress'); ?></div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div style="text-align: center; color: #666; padding: 40px;">
                <p>No template found for this document.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="panel">
    <h3>Field Summary</h3>
    <div class="grid">
        <?php if (!empty($tpl['fields'])): ?>
            <?php foreach ($tpl['fields'] as $field): ?>
                <div style="border: 1px solid #eef2f7; border-radius: 6px; padding: 12px;">
                    <div style="font-weight: bold; margin-bottom: 4px;">
                        <?php echo htmlspecialchars($field['label']); ?>
                    </div>
                    <div style="color: #65748b; font-size: 12px; margin-bottom: 6px;">
                        <?php echo htmlspecialchars($field['key']); ?> • <?php echo htmlspecialchars($field['type'] ?? 'text'); ?>
                    </div>
                    <div style="background: #f8f9fa; padding: 8px; border-radius: 4px; min-height: 20px;">
                        <?php $val = $values[$field['key']] ?? ''; ?>
                        <?php if (!empty($val)): ?>
                            <?php echo htmlspecialchars((string)$val); ?>
                        <?php else: ?>
                            <span style="color: #999; font-style: italic;">Empty</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="color: #666;">No fields defined for this template.</div>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($projectDocument['outputPath']) && file_exists($projectDocument['outputPath'])): ?>
    <div class="panel">
        <h3>Generated PDF</h3>
        <div class="row" style="gap: 12px;">
            <a href="?route=actions/download&pd=<?php echo htmlspecialchars($projectDocument['id']); ?>" class="btn">Download PDF</a>
            <a href="?route=actions/sign&pd=<?php echo htmlspecialchars($projectDocument['id']); ?>" class="btn secondary">Sign Document</a>
        </div>
        <div class="muted" style="margin-top: 8px;">
            Generated: <?php echo date('F j, Y \a\t g:i A', filemtime($projectDocument['outputPath'])); ?>
        </div>
    </div>
<?php endif; ?>


























