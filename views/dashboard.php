<div class="clio-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <div>
            <h2 style="margin: 0 0 6px 0; color: #2c3e50; font-size: 24px; font-weight: 700;">Dashboard</h2>
            <p style="margin: 0; color: #6c757d; font-size: 14px;">Overview of your practice</p>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 24px; margin-bottom: 32px;">
    <div class="clio-card">
        <h3 style="margin: 0 0 16px 0; color: #2c3e50; font-size: 18px; font-weight: 600;">Active Matters</h3>
        <div style="font-size: 32px; font-weight: 700; color: #1976d2; margin-bottom: 8px;"><?php echo count($projects); ?></div>
        <p style="margin: 0; color: #6c757d; font-size: 14px;">Total matters in progress</p>
    </div>
    
    <div class="clio-card">
        <h3 style="margin: 0 0 16px 0; color: #2c3e50; font-size: 18px; font-weight: 600;">Clients</h3>
        <div style="font-size: 32px; font-weight: 700; color: #1976d2; margin-bottom: 8px;"><?php echo count($clients); ?></div>
        <p style="margin: 0; color: #6c757d; font-size: 14px;">Total active clients</p>
    </div>
    
    <div class="clio-card">
        <h3 style="margin: 0 0 16px 0; color: #2c3e50; font-size: 18px; font-weight: 600;">Documents</h3>
        <div style="font-size: 32px; font-weight: 700; color: #1976d2; margin-bottom: 8px;"><?php echo count($recentDocuments); ?></div>
        <p style="margin: 0; color: #6c757d; font-size: 14px;">Recent documents</p>
    </div>
</div>

<div class="clio-card">
    <h3 style="margin: 0 0 24px 0; color: #2c3e50; font-size: 20px; font-weight: 600;">Recent Documents</h3>
    <?php if (empty($recentDocuments)): ?>
        <div style="text-align: center; padding: 40px; color: #6c757d;">
            <div style="font-size: 48px; margin-bottom: 16px;">📄</div>
            <p>No documents yet. <a href="?route=projects" style="color: #1976d2;">Create your first matter</a> to get started.</p>
        </div>
    <?php else: ?>
        <table class="clio-table">
            <thead>
                <tr>
                    <th>Document</th>
                    <th>Matter</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentDocuments as $doc): ?>
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
                                    <a href="?route=actions/download-signed&pd=<?php echo htmlspecialchars($doc['id']); ?>" class="clio-btn" style="padding: 6px 12px; font-size: 12px;">Download</a>
                                <?php elseif (!empty($doc['outputPath'])): ?>
                                    <a href="?route=actions/download&pd=<?php echo htmlspecialchars($doc['id']); ?>" class="clio-btn" style="padding: 6px 12px; font-size: 12px;">Download</a>
                                <?php else: ?>
                                    <a href="?route=populate&pd=<?php echo htmlspecialchars($doc['id']); ?>" class="clio-btn-secondary" style="padding: 6px 12px; font-size: 12px;">Complete</a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>


