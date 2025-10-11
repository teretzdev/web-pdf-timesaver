<div style="margin-bottom: 20px;">
    <h2 style="margin: 0 0 20px 0; color: #333; font-size: 20px;">Dashboard</h2>
    <div style="display: flex; gap: 20px; margin-bottom: 20px;">
        <span><?php echo count($projects); ?> Matters</span>
        <span><?php echo count($clients); ?> Clients</span>
        <span><?php echo count($recentDocuments); ?> Documents</span>
    </div>
</div>

<div>
    <h3 style="margin: 0 0 15px 0; color: #333; font-size: 16px;">Recent Documents</h3>
    <?php if (empty($recentDocuments)): ?>
        <p style="color: #666;">No documents yet. <a href="?route=projects">Create your first matter</a> to get started.</p>
    <?php else: ?>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 1px solid #ddd;">
                    <th style="text-align: left; padding: 8px 0; font-weight: normal;">Document</th>
                    <th style="text-align: left; padding: 8px 0; font-weight: normal;">Matter</th>
                    <th style="text-align: left; padding: 8px 0; font-weight: normal;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentDocuments as $doc): ?>
                    <?php
                    $template = $templates[$doc['templateId']] ?? null;
                    $templateName = $template ? ($template['code'] . ' — ' . $template['name']) : $doc['templateId'];
                    ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 8px 0;">
                            <?php echo htmlspecialchars($templateName); ?>
                        </td>
                        <td style="padding: 8px 0;">
                            <a href="?route=project&id=<?php echo htmlspecialchars($doc['project']['id']); ?>">
                                <?php echo htmlspecialchars($doc['project']['name']); ?>
                            </a>
                        </td>
                        <td style="padding: 8px 0;">
                            <?php if (!empty($doc['signedPath'])): ?>
                                <a href="?route=actions/download-signed&pd=<?php echo htmlspecialchars($doc['id']); ?>">Download</a>
                            <?php elseif (!empty($doc['outputPath'])): ?>
                                <a href="?route=actions/download&pd=<?php echo htmlspecialchars($doc['id']); ?>">Download</a>
                            <?php else: ?>
                                <a href="?route=populate&pd=<?php echo htmlspecialchars($doc['id']); ?>">Complete</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>


