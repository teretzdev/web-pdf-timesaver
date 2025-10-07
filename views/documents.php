<div class="clio-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <div>
            <h2 style="margin: 0 0 6px 0; color: #2c3e50; font-size: 24px; font-weight: 700;">Documents</h2>
            <p style="margin: 0; color: #6c757d; font-size: 14px;"><?php echo count($documents); ?> documents</p>
        </div>
        <div>
            <a href="?route=dashboard" class="clio-btn-secondary">
                <span>←</span>
                <span>Back to Dashboard</span>
            </a>
        </div>
    </div>
</div>

<?php if (empty($documents)): ?>
    <div class="clio-card" style="text-align: center; padding: 60px 20px;">
        <div style="font-size: 48px; margin-bottom: 16px;">📄</div>
        <h3 style="margin: 0 0 8px 0; color: #2c3e50; font-size: 20px;">No documents found</h3>
        <p style="margin: 0; color: #6c757d; font-size: 16px;">Documents will appear here when you create them in your matters.</p>
    </div>
<?php else: ?>
    <div class="clio-card">
        <table class="clio-table">
            <thead>
                <tr>
                    <th>Document</th>
                    <th>Matter</th>
                    <th>Client</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($documents as $doc): ?>
                    <?php
                    $template = $templates[$doc['templateId']] ?? null;
                    $templateName = $template ? ($template['code'] . ' — ' . $template['name']) : $doc['templateId'];
                    ?>
                    <tr>
                        <td>
                            <div style="font-weight: 600; color: #2c3e50;">
                                <?php echo htmlspecialchars($templateName); ?>
                            </div>
                        </td>
                        <td>
                            <a href="?route=project&id=<?php echo htmlspecialchars($doc['project']['id']); ?>" style="color: #1976d2; text-decoration: none;">
                                <?php echo htmlspecialchars($doc['project']['name']); ?>
                            </a>
                        </td>
                        <td>
                            <?php if ($doc['client']): ?>
                                <span style="color: #495057;"><?php echo htmlspecialchars($doc['client']['displayName']); ?></span>
                            <?php else: ?>
                                <span style="color: #6c757d;">No client assigned</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="clio-status clio-status-<?php echo str_replace('_', '-', $doc['status'] ?? 'in-progress'); ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $doc['status'] ?? 'in_progress')); ?>
                            </span>
                        </td>
                        <td>
                            <?php 
                            $date = new DateTime($doc['createdAt'] ?? 'now');
                            echo $date->format('M j, Y');
                            ?>
                        </td>
                        <td>
                            <div style="display: flex; gap: 8px;">
                                <?php if (!empty($doc['signedPath'])): ?>
                                    <a href="?route=actions/download-signed&pd=<?php echo htmlspecialchars($doc['id']); ?>" class="clio-btn" style="padding: 6px 12px; font-size: 12px;">Download Signed</a>
                                <?php elseif (!empty($doc['outputPath'])): ?>
                                    <a href="?route=actions/download&pd=<?php echo htmlspecialchars($doc['id']); ?>" class="clio-btn" style="padding: 6px 12px; font-size: 12px;">Download</a>
                                <?php else: ?>
                                    <a href="?route=populate&pd=<?php echo htmlspecialchars($doc['id']); ?>" class="clio-btn-secondary" style="padding: 6px 12px; font-size: 12px;">Complete</a>
                                <?php endif; ?>
                                <a href="?route=project&id=<?php echo htmlspecialchars($doc['project']['id']); ?>" class="clio-btn-secondary" style="padding: 6px 12px; font-size: 12px;">View Matter</a>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
