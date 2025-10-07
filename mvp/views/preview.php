<?php $tpl = $template; ?>
<div class="clio-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <div>
            <h2 style="margin: 0 0 6px 0; color: #2c3e50; font-size: 24px; font-weight: 700;">
                Preview: <?php echo htmlspecialchars(($tpl['code'] ?? '') . ' — ' . ($tpl['name'] ?? '')); ?>
            </h2>
            <p style="margin: 0; color: #6c757d; font-size: 14px;">Document preview with current field values</p>
        </div>
        <div style="display: flex; gap: 8px;">
            <a href="?route=populate&pd=<?php echo htmlspecialchars($projectDocument['id']); ?>" class="clio-btn-secondary">Edit Fields</a>
            <a href="?route=actions/generate&pd=<?php echo htmlspecialchars($projectDocument['id']); ?>" class="clio-btn">Generate PDF</a>
        </div>
    </div>
</div>

<div class="clio-card">
    <div style="background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 30px; min-height: 400px;">
        <?php if (!empty($tpl)): ?>
            <div style="font-family: 'Times New Roman', serif; line-height: 1.8; color: #2c3e50; max-width: 800px; margin: 0 auto;">
                <!-- Document Header -->
                <div style="text-align: center; margin-bottom: 40px; border-bottom: 2px solid #2c3e50; padding-bottom: 20px;">
                    <h1 style="font-size: 20px; font-weight: bold; margin: 0;">
                        <?php echo htmlspecialchars($tpl['code'] ?? ''); ?>
                    </h1>
                    <h2 style="font-size: 16px; font-weight: normal; margin: 10px 0 0 0; color: #495057;">
                        <?php echo htmlspecialchars($tpl['name'] ?? ''); ?>
                    </h2>
                </div>

                <!-- Document Content -->
                <?php if (!empty($tpl['panels'])): ?>
                    <?php foreach ($tpl['panels'] as $panel): ?>
                        <div style="margin-bottom: 30px;">
                            <h3 style="font-size: 14px; font-weight: bold; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 1px; color: #2c3e50;">
                                <?php echo htmlspecialchars($panel['label']); ?>
                            </h3>
                            
                            <div style="margin-left: 20px;">
                                <?php foreach ($tpl['fields'] as $field): ?>
                                    <?php if (($field['panelId'] ?? '') !== $panel['id']) continue; ?>
                                    <?php $val = $values[$field['key']] ?? ''; ?>
                                    <div style="margin-bottom: 12px; display: flex; align-items: flex-start;">
                                        <div style="min-width: 180px; font-weight: 600; margin-right: 15px; color: #2c3e50;">
                                            <?php echo htmlspecialchars($field['label']); ?>:
                                        </div>
                                        <div style="flex: 1; border-bottom: 1px dotted #6c757d; min-height: 22px; padding-bottom: 2px;">
                                            <?php if (!empty($val)): ?>
                                                <span style="color: #2c3e50;"><?php echo htmlspecialchars((string)$val); ?></span>
                                            <?php else: ?>
                                                <span style="color: #adb5bd; font-style: italic;">[Empty]</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- Document Footer -->
                <div style="margin-top: 50px; padding-top: 20px; border-top: 1px solid #dee2e6;">
                    <p style="text-align: center; color: #6c757d; font-size: 12px;">
                        Generated on <?php echo date('F j, Y'); ?>
                    </p>
                </div>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 60px 20px;">
                <div style="font-size: 48px; margin-bottom: 16px;">📄</div>
                <h3 style="margin: 0 0 8px 0; color: #2c3e50; font-size: 20px;">No Template Available</h3>
                <p style="margin: 0; color: #6c757d; font-size: 16px;">Unable to preview this document.</p>
            </div>
        <?php endif; ?>
    </div>
    
    <div style="display: flex; gap: 8px; margin-top: 24px;">
        <a href="?route=populate&pd=<?php echo htmlspecialchars($projectDocument['id']); ?>" class="clio-btn-secondary">← Back to Edit</a>
        <a href="?route=project&id=<?php echo htmlspecialchars($projectDocument['projectId']); ?>" class="clio-btn-secondary">Back to Matter</a>
    </div>
</div>