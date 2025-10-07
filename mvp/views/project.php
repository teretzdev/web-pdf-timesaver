<?php
// Get client info if assigned
$client = null;
if (!empty($project['clientId']) && $store && method_exists($store, 'getClient')) {
    $client = $store->getClient($project['clientId']);
}
?>

<div class="clio-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <div>
            <h2 style="margin: 0 0 6px 0; color: #2c3e50; font-size: 24px; font-weight: 700;">
                <?php echo htmlspecialchars($project['name']); ?>
            </h2>
            <p style="margin: 0; color: #6c757d; font-size: 14px;">
                <?php if ($client): ?>
                    Client: <a href="?route=client&id=<?php echo htmlspecialchars($client['id']); ?>" style="color: #1976d2; text-decoration: none;"><?php echo htmlspecialchars($client['displayName']); ?></a>
                <?php else: ?>
                    No client assigned
                <?php endif; ?>
            </p>
        </div>
        <div style="display: flex; gap: 8px;">
            <form method="post" action="?route=actions/duplicate-project" style="display: inline;">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($project['id']); ?>">
                <button class="clio-btn-secondary" type="submit">Duplicate Matter</button>
            </form>
            <a href="?route=projects" class="clio-btn-secondary">← Back to Matters</a>
        </div>
    </div>
</div>

<div class="clio-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <h3 style="margin: 0; color: #2c3e50; font-size: 20px; font-weight: 600;">Documents</h3>
        <form method="post" action="?route=actions/add-document" style="display: flex; align-items: center; gap: 8px;">
            <input type="hidden" name="projectId" value="<?php echo htmlspecialchars($project['id']); ?>">
            <select name="templateId" required class="clio-input" style="width: auto;">
                <option value="">Select template...</option>
                <?php foreach ($templates as $tpl): ?>
                    <option value="<?php echo htmlspecialchars($tpl['id']); ?>">
                        <?php echo htmlspecialchars($tpl['code'] . ' — ' . $tpl['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button class="clio-btn" type="submit">Add Document</button>
        </form>
    </div>

    <?php if (empty($documents)): ?>
        <div style="text-align: center; padding: 40px; color: #6c757d;">
            <div style="font-size: 48px; margin-bottom: 16px;">📄</div>
            <p>No documents yet. Select a template above to add your first document.</p>
        </div>
    <?php else: ?>
        <table class="clio-table">
            <thead>
                <tr>
                    <th>Document</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($documents as $d): ?>
                    <?php $tpl = $templates[$d['templateId']] ?? ['code' => $d['templateId'], 'name' => '']; ?>
                    <tr>
                        <td>
                            <div style="font-weight: 600; color: #2c3e50;">
                                <?php echo htmlspecialchars(($tpl['code'] ?? '') . ' — ' . ($tpl['name'] ?? '')); ?>
                            </div>
                        </td>
                        <td>
                            <span class="clio-status clio-status-<?php echo str_replace('_', '-', $d['status'] ?? 'in-progress'); ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $d['status'] ?? 'in_progress')); ?>
                            </span>
                        </td>
                        <td>
                            <?php 
                            $date = new DateTime($d['createdAt'] ?? 'now');
                            echo $date->format('M j, Y');
                            ?>
                        </td>
                        <td>
                            <div style="display: flex; gap: 8px;">
                                <?php if (!empty($d['signedPath'])): ?>
                                    <a href="?route=actions/download-signed&pd=<?php echo htmlspecialchars($d['id']); ?>" class="clio-btn" style="padding: 6px 12px; font-size: 12px;">Download Signed</a>
                                <?php elseif (!empty($d['outputPath'])): ?>
                                    <a href="?route=actions/download&pd=<?php echo htmlspecialchars($d['id']); ?>" class="clio-btn" style="padding: 6px 12px; font-size: 12px;">Download</a>
                                    <a href="?route=actions/sign&pd=<?php echo htmlspecialchars($d['id']); ?>" class="clio-btn-secondary" style="padding: 6px 12px; font-size: 12px;">Sign</a>
                                <?php else: ?>
                                    <a href="?route=populate&pd=<?php echo htmlspecialchars($d['id']); ?>" class="clio-btn" style="padding: 6px 12px; font-size: 12px;">Complete</a>
                                <?php endif; ?>
                                
                                <?php if (empty($d['signedPath'])): ?>
                                    <a href="?route=populate&pd=<?php echo htmlspecialchars($d['id']); ?>" class="clio-btn-secondary" style="padding: 6px 12px; font-size: 12px;">Edit</a>
                                    <form method="post" action="?route=actions/remove-document" style="display: inline;">
                                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($d['id']); ?>">
                                        <button type="submit" class="clio-btn-secondary" style="padding: 6px 12px; font-size: 12px; background: #dc3545; color: white; border-color: #dc3545;" onclick="return confirm('Remove this document?')">Remove</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- Matter Information -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 24px; margin-top: 24px;">
    <div class="clio-card">
        <h4 style="margin: 0 0 16px 0; color: #2c3e50; font-size: 16px; font-weight: 600;">Matter Details</h4>
        <div style="margin-bottom: 12px;">
            <div style="color: #6c757d; font-size: 12px; margin-bottom: 4px;">Status</div>
            <div>
                <span class="clio-status clio-status-<?php echo str_replace('_', '-', $project['status'] ?? 'in-progress'); ?>">
                    <?php echo ucfirst(str_replace('_', ' ', $project['status'] ?? 'in_progress')); ?>
                </span>
            </div>
        </div>
        <div style="margin-bottom: 12px;">
            <div style="color: #6c757d; font-size: 12px; margin-bottom: 4px;">Created</div>
            <div style="font-weight: 500; color: #2c3e50;">
                <?php 
                $created = new DateTime($project['createdAt'] ?? 'now');
                echo $created->format('M j, Y');
                ?>
            </div>
        </div>
        <div style="margin-bottom: 12px;">
            <div style="color: #6c757d; font-size: 12px; margin-bottom: 4px;">Last Updated</div>
            <div style="font-weight: 500; color: #2c3e50;">
                <?php 
                $updated = new DateTime($project['updatedAt'] ?? $project['createdAt'] ?? 'now');
                echo $updated->format('M j, Y');
                ?>
            </div>
        </div>
    </div>
    
    <?php if (!$client): ?>
    <div class="clio-card">
        <h4 style="margin: 0 0 16px 0; color: #2c3e50; font-size: 16px; font-weight: 600;">Assign Client</h4>
        <form method="post" action="?route=actions/assign-client">
            <input type="hidden" name="projectId" value="<?php echo htmlspecialchars($project['id']); ?>">
            <select name="clientId" required class="clio-input" style="margin-bottom: 12px;">
                <option value="">Select client...</option>
                <?php foreach ($clients as $c): ?>
                    <option value="<?php echo htmlspecialchars($c['id']); ?>">
                        <?php echo htmlspecialchars($c['displayName']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="clio-btn" style="width: 100%;">Assign Client</button>
        </form>
    </div>
    <?php endif; ?>
</div>