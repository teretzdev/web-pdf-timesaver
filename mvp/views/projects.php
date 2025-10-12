<div class="clio-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
        <div>
            <h2 style="margin: 0 0 6px 0; color: #2c3e50; font-size: 24px; font-weight: 700;">All Matters</h2>
            <p style="margin: 0; color: #6c757d; font-size: 14px;"><?php echo count($projects); ?> matters</p>
        </div>
        <div class="button-group" style="display: flex; gap: 12px;">
            <a href="?route=dashboard" class="clio-btn-secondary">
                <span>←</span>
                <span>Back to Dashboard</span>
            </a>
        </div>
    </div>
</div>

<?php if (empty($projects)): ?>
    <div class="clio-card" style="text-align: center; padding: 60px 20px;">
        <div style="font-size: 48px; margin-bottom: 16px;">📁</div>
        <h3 style="margin: 0 0 8px 0; color: #2c3e50; font-size: 20px;">No matters found</h3>
        <p style="margin: 0; color: #6c757d; font-size: 16px;">Matters are created within client accounts. <a href="?route=clients" style="color: #1976d2; text-decoration: none;">Go to clients</a> to create your first matter.</p>
    </div>
<?php else: ?>
    <div class="clio-card">
        <div class="table-responsive">
            <table class="clio-table">
            <thead>
                <tr>
                    <th>Matter</th>
                    <th>Client</th>
                    <th>Status</th>
                    <th>Last Modified</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($projects as $project): ?>
                    <?php
                    // Get client info for this project
                    $client = null;
                    if (!empty($project['clientId']) && $store && method_exists($store, 'getClient')) {
                        $client = $store->getClient($project['clientId']);
                    }
                    ?>
                    <tr>
                        <td>
                            <div style="font-weight: 600; color: #2c3e50;">
                                <a href="?route=project&id=<?php echo htmlspecialchars($project['id']); ?>" style="color: #1976d2; text-decoration: none;">
                                    <?php echo htmlspecialchars($project['name']); ?>
                                </a>
                            </div>
                        </td>
                        <td>
                            <?php if ($client): ?>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <div style="width: 32px; height: 32px; border-radius: 50%; background: #1976d2; color: white; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 12px;">
                                        <?php echo strtoupper(substr($client['displayName'] ?? 'C', 0, 1)); ?>
                                    </div>
                                    <span style="color: #495057;"><?php echo htmlspecialchars($client['displayName']); ?></span>
                                </div>
                            <?php else: ?>
                                <span style="color: #6c757d;">No client assigned</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="clio-status clio-status-<?php echo str_replace('_', '-', $project['status'] ?? 'in-progress'); ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $project['status'] ?? 'in_progress')); ?>
                            </span>
                        </td>
                        <td>
                            <?php 
                            $date = new DateTime($project['updatedAt'] ?? $project['createdAt'] ?? 'now');
                            echo $date->format('M j, Y');
                            ?>
                        </td>
                        <td>
                            <div class="button-group" style="display: flex; gap: 8px;">
                                <a href="?route=project&id=<?php echo htmlspecialchars($project['id']); ?>" class="clio-btn-secondary" style="padding: 6px 12px; font-size: 12px;">View</a>
                                <form method="post" action="?route=actions/update-project-status" style="display: inline;">
                                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($project['id']); ?>">
                                    <input type="hidden" name="status" value="<?php echo ($project['status'] ?? 'in_progress') === 'in_progress' ? 'completed' : 'in_progress'; ?>">
                                    <button type="submit" class="clio-btn-secondary" style="padding: 6px 12px; font-size: 12px;">
                                        <?php echo ($project['status'] ?? 'in_progress') === 'in_progress' ? 'Complete' : 'Reopen'; ?>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
<?php endif; ?>


