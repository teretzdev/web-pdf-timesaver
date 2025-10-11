<?php
// Render breadcrumb navigation
// Breadcrumb navigation disabled for now
?>

<div class="clio-card">
<div class="client-header" role="region" aria-label="Client header">
    <div class="client-info">
        <h1><a href="?route=client&id=<?php echo htmlspecialchars($client['id']); ?>" class="client-name-link"><?php echo htmlspecialchars($client['displayName'] ?? 'Client'); ?></a></h1>
        <div class="client-meta">
            <?php if (!empty($client['email'])): ?>
                <span class="meta-item">📧 <?php echo htmlspecialchars($client['email']); ?></span>
            <?php endif; ?>
            <?php if (!empty($client['phone'])): ?>
                <span class="meta-item">📞 <?php echo htmlspecialchars($client['phone']); ?></span>
            <?php endif; ?>
        </div>
    </div>
    <div class="client-actions">
        <a href="?route=clients" class="clio-btn-secondary" aria-label="Back to clients">← Back to Clients</a>
    </div>
</div>

<div class="client-tabs" role="tablist" aria-label="Client tabs">
    <div class="tab-nav">
        <a href="?route=client&id=<?php echo htmlspecialchars($client['id']); ?>" class="tab-link active" role="tab" aria-selected="true">Projects (<?php echo count($projects); ?>)</a>
    </div>
</div>

</div>

<div class="clio-card">
<div class="projects-section">
    <div class="projects-header">
        <h2>Projects</h2>
        <button class="clio-btn" id="add-project-btn">Add new project</button>
    </div>
    
    <?php if (empty($projects)): ?>
        <div class="empty-state">
            <div class="empty-icon">📁</div>
            <h3>No projects yet</h3>
            <p>Create your first project for this client to get started.</p>
            <button class="clio-btn" onclick="document.getElementById('add-project-btn').click()">Add your first project</button>
        </div>
    <?php else: ?>
        <div class="projects-list">
            <?php foreach ($projects as $project): ?>
                <div class="project-card">
                    <div class="project-info">
                        <h3 class="project-name">
                            <a href="?route=project&id=<?php echo htmlspecialchars($project['id']); ?>">
                                <?php echo htmlspecialchars($project['name']); ?>
                            </a>
                        </h3>
                        <div class="project-meta">
                            <span class="last-modified">
                                last modified on <?php 
                                $date = new DateTime($project['updatedAt'] ?? $project['createdAt'] ?? 'now');
                                echo $date->format('m/d/y');
                                ?>
                            </span>
                        </div>
                    </div>
                    <div class="project-actions">
                        <div class="project-status">
                            <select class="status-select" data-project-id="<?php echo htmlspecialchars($project['id']); ?>">
                                <option value="in_progress" <?php echo ($project['status'] ?? 'in_progress') === 'in_progress' ? 'selected' : ''; ?>>In progress</option>
                                <option value="review" <?php echo ($project['status'] ?? 'in_progress') === 'review' ? 'selected' : ''; ?>>Review</option>
                                <option value="completed" <?php echo ($project['status'] ?? 'in_progress') === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            </select>
                        </div>
                        <div class="action-buttons">
                            <button class="clio-btn-secondary btn-sm btn-danger delete-project" data-project-id="<?php echo htmlspecialchars($project['id']); ?>">
                                Delete project
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
</div>

<!-- Add Project Modal -->
<div id="add-project-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Add New Project</h3>
            <button class="modal-close" onclick="closeAddProjectModal()">&times;</button>
        </div>
        <form method="post" action="?route=actions/create-project" class="modal-body">
            <input type="hidden" name="clientId" value="<?php echo htmlspecialchars($client['id']); ?>">
            <div class="form-group">
                <label for="project-name">Project Name *</label>
                <input type="text" id="project-name" name="name" placeholder="Enter project name" required>
            </div>
            <div class="modal-footer">
                <button type="button" class="clio-btn-secondary" onclick="closeAddProjectModal()">Cancel</button>
                <button type="submit" class="clio-btn">Add Project</button>
            </div>
        </form>
    </div>
</div>

<style>
.client-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eef2f7;
}

.client-info h1 {
    margin: 0 0 8px 0;
    color: #1a2b3b;
    font-size: 28px;
    font-weight: 600;
}

.client-name-link {
    color: #1a2b3b;
    text-decoration: none;
    font-weight: 600;
}

.client-name-link:hover {
    color: #0b6bcb;
    text-decoration: underline;
}

.client-meta {
    display: flex;
    gap: 16px;
    color: #65748b;
    font-size: 14px;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 4px;
}

.client-tabs {
    margin-bottom: 30px;
}

.tab-nav {
    display: flex;
    gap: 0;
    border-bottom: 1px solid #eef2f7;
}

.tab-link {
    padding: 12px 20px;
    text-decoration: none;
    color: #65748b;
    border-bottom: 2px solid transparent;
    font-weight: 500;
    transition: all 0.2s ease;
}

.tab-link:hover {
    color: #1a2b3b;
    background: #f6f7fb;
}

.tab-link.active {
    color: #0b6bcb;
    border-bottom-color: #0b6bcb;
}

.projects-section {
    background: #fff;
    border-radius: 8px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.projects-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.projects-header h2 {
    margin: 0;
    color: #1a2b3b;
    font-size: 20px;
    font-weight: 600;
}

.projects-list {
    display: flex;
    flex-direction: column;
    gap: 0;
}

.project-card {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 0;
    border-bottom: 1px solid #eef2f7;
    transition: all 0.2s ease;
}

.project-card:last-child {
    border-bottom: none;
}

.project-card:hover {
    background: #f6f7fb;
    margin: 0 -24px;
    padding: 16px 24px;
    border-radius: 6px;
}

.project-info {
    flex: 1;
}

.project-name {
    margin: 0 0 4px 0;
    font-size: 16px;
    font-weight: 600;
}

.project-name a {
    color: #1a2b3b;
    text-decoration: none;
}

.project-name a:hover {
    color: #0b6bcb;
    text-decoration: underline;
}

.project-meta {
    color: #65748b;
    font-size: 14px;
}

.project-actions {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 12px;
}

.project-status {
    display: flex;
    align-items: center;
}

.status-select {
    padding: 6px 12px;
    border: 1px solid #d7dce3;
    border-radius: 4px;
    font-size: 12px;
    background: #fff;
}

.action-buttons {
    display: flex;
    gap: 8px;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 12px;
    border-radius: 4px;
}

.btn-danger {
    background: #dc3545;
    color: #fff;
}

.btn-danger:hover {
    background: #c82333;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #65748b;
}

.empty-icon {
    font-size: 48px;
    margin-bottom: 16px;
}

.empty-state h3 {
    margin: 0 0 8px 0;
    color: #1a2b3b;
    font-size: 20px;
}

.empty-state p {
    margin: 0 0 24px 0;
    font-size: 16px;
}

/* Responsive */
@media (max-width: 768px) {
    .client-header {
        flex-direction: column;
        gap: 16px;
    }
    
    .client-meta {
        flex-direction: column;
        gap: 8px;
    }
    
    .projects-header {
        flex-direction: column;
        align-items: stretch;
        gap: 16px;
    }
    
    .project-card {
        flex-direction: column;
        align-items: stretch;
        gap: 12px;
    }
    
    .project-actions {
        justify-content: flex-end;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add project button
    document.getElementById('add-project-btn').addEventListener('click', function() {
        document.getElementById('add-project-modal').style.display = 'flex';
    });
    
    // Handle delete project buttons
    document.querySelectorAll('.delete-project').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const projectId = this.getAttribute('data-project-id');
            
            if (confirm('Are you sure you want to delete this project? This action cannot be undone.')) {
                // Here you would typically make an AJAX call to delete the project
                console.log('Deleting project', projectId);
                
                // Remove the project card from the DOM
                const card = this.closest('.project-card');
                card.remove();
            }
        });
    });
});

function closeAddProjectModal() {
    document.getElementById('add-project-modal').style.display = 'none';
}

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    const modal = document.getElementById('add-project-modal');
    if (e.target === modal) {
        closeAddProjectModal();
    }
});
</script>


