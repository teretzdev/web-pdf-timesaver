<?php
// Render breadcrumb navigation
$client = null;
if (!empty($project['clientId']) && $store && method_exists($store, 'getClient')) {
    $client = $store->getClient($project['clientId']);
}

// Breadcrumb navigation disabled for now
?>

<div class="clio-card">
<div class="project-header">
    <div class="project-info">
        <div class="project-name-section">
            <h1 class="project-name"><?php echo htmlspecialchars($project['name']); ?></h1>
            <a href="#" class="edit-link" id="edit-project-name">Edit</a>
        </div>
        <div class="project-meta">
            <?php if ($client): ?>
                <span class="client-link">
                    Client: <a href="?route=client&id=<?php echo htmlspecialchars($client['id']); ?>"><?php echo htmlspecialchars($client['displayName']); ?></a>
                </span>
            <?php else: ?>
                <span class="client-link">No client assigned</span>
            <?php endif; ?>
        </div>
    </div>
    <div class="project-actions">
        <form method="post" action="?route=actions/duplicate-project" style="display: inline;">
            <input type="hidden" name="id" value="<?php echo htmlspecialchars($project['id']); ?>">
            <button class="clio-btn-secondary" type="submit">Duplicate</button>
        </form>
    </div>
</div>
</div>

<div class="clio-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <h2 style="margin: 0; color: #2c3e50; font-size: 24px; font-weight: 700;">Documents</h2>
        <form method="post" action="?route=actions/add-document" style="display: inline;">
            <input type="hidden" name="projectId" value="<?php echo htmlspecialchars($project['id']); ?>">
            <select name="templateId" required style="margin-right: 8px; padding: 8px 12px; border: 1px solid #dee2e6; border-radius: 4px;">
                <?php foreach ($templates as $tpl): ?>
                    <option value="<?php echo htmlspecialchars($tpl['id']); ?>"><?php echo htmlspecialchars($tpl['code'] . ' — ' . $tpl['name']); ?></option>
                <?php endforeach; ?>
            </select>
            <button class="clio-btn" type="submit">Add Document</button>
        </form>
    </div>
</div>

<div class="clio-card">
    <table class="clio-table">
        <thead>
            <tr>
                <th>Document</th>
                <th>Status</th>
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
                        <div class="dropdown">
                            <button class="dropdown-btn" onclick="toggleDropdown('dropdown-<?php echo htmlspecialchars($d['id']); ?>')">
                                Actions ▼
                            </button>
                            <div class="dropdown-content" id="dropdown-<?php echo htmlspecialchars($d['id']); ?>">
                                <?php if (!empty($d['signedPath'])): ?>
                                    <a href="?route=actions/download-signed&pd=<?php echo htmlspecialchars($d['id']); ?>">Download Signed</a>
                                <?php elseif (!empty($d['outputPath'])): ?>
                                    <a href="?route=actions/download&pd=<?php echo htmlspecialchars($d['id']); ?>">Download</a>
                                <?php else: ?>
                                    <a href="?route=populate&pd=<?php echo htmlspecialchars($d['id']); ?>">Complete</a>
                                <?php endif; ?>
                                <a href="?route=populate&pd=<?php echo htmlspecialchars($d['id']); ?>">Edit</a>
                                <a href="?route=actions/remove-document&pd=<?php echo htmlspecialchars($d['id']); ?>" onclick="return confirm('Remove this document?')">Remove</a>
                            </div>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="clio-card">
    <a href="?route=projects" class="clio-btn-secondary">← Back to Matters</a>
</div>

<style>
.project-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eef2f7;
}

.project-info {
    flex: 1;
}

.project-name-section {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 8px;
}

.project-name {
    font-size: 24px;
    font-weight: 600;
    color: #1a2b3b;
    margin: 0;
}

.edit-link {
    color: #0b6bcb;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
}

.edit-link:hover {
    text-decoration: underline;
}


.project-meta {
    color: #65748b;
    font-size: 14px;
}

.client-link a {
    color: #0b6bcb;
    text-decoration: none;
    font-weight: 500;
}

.client-link a:hover {
    text-decoration: underline;
}

.project-actions {
    display: flex;
    align-items: center;
    gap: 12px;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 12px;
    border-radius: 4px;
}

/* Dropdown Styles */
.dropdown {
    position: relative;
    display: inline-block;
}

.dropdown-btn {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 6px 12px;
    font-size: 12px;
    color: #495057;
    cursor: pointer;
    transition: all 0.2s ease;
}

.dropdown-btn:hover {
    background: #e9ecef;
    border-color: #adb5bd;
}

.dropdown-content {
    display: none;
    position: absolute;
    background: white;
    min-width: 120px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border: 1px solid #dee2e6;
    border-radius: 4px;
    z-index: 1000;
    right: 0;
    top: 100%;
}

.dropdown-content a {
    color: #495057;
    padding: 8px 12px;
    text-decoration: none;
    display: block;
    font-size: 12px;
    transition: background-color 0.2s ease;
}

.dropdown-content a:hover {
    background-color: #f8f9fa;
}

.dropdown-content a:first-child {
    border-top-left-radius: 4px;
    border-top-right-radius: 4px;
}

.dropdown-content a:last-child {
    border-bottom-left-radius: 4px;
    border-bottom-right-radius: 4px;
}

/* Responsive */
@media (max-width: 768px) {
    .project-header {
        flex-direction: column;
        gap: 16px;
    }
    
    .project-name-form {
        flex-direction: column;
        align-items: stretch;
        gap: 8px;
    }
    
    .project-name-input {
        min-width: auto;
        width: 100%;
    }
    
    .project-actions {
        justify-content: flex-end;
    }
}
</style>

<script>
// Dropdown functionality
function toggleDropdown(dropdownId) {
    // Close all other dropdowns
    const allDropdowns = document.querySelectorAll('.dropdown-content');
    allDropdowns.forEach(dropdown => {
        if (dropdown.id !== dropdownId) {
            dropdown.style.display = 'none';
        }
    });
    
    // Toggle current dropdown
    const dropdown = document.getElementById(dropdownId);
    if (dropdown.style.display === 'block') {
        dropdown.style.display = 'none';
    } else {
        dropdown.style.display = 'block';
    }
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(event) {
    if (!event.target.matches('.dropdown-btn')) {
        const dropdowns = document.querySelectorAll('.dropdown-content');
        dropdowns.forEach(dropdown => {
            dropdown.style.display = 'none';
        });
    }
});

document.addEventListener('DOMContentLoaded', function() {
    
    // Handle edit project name
    const editLink = document.getElementById('edit-project-name');
    const projectName = document.querySelector('.project-name');
    
    if (editLink && projectName) {
        editLink.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Create input field
            const input = document.createElement('input');
            input.type = 'text';
            input.value = projectName.textContent;
            input.className = 'project-name-input';
            input.style.cssText = 'font-size: 24px; font-weight: 600; color: #1a2b3b; border: 2px solid #0b6bcb; background: #fff; padding: 8px 12px; border-radius: 6px; min-width: 300px;';
            
            // Replace name with input
            projectName.style.display = 'none';
            editLink.style.display = 'none';
            projectName.parentNode.insertBefore(input, projectName);
            input.focus();
            input.select();
            
            // Handle save
            const saveEdit = function() {
                const newName = input.value.trim();
                if (newName && newName !== projectName.textContent) {
                    // Create form and submit
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '?route=actions/update-project-name';
                    
                    const idInput = document.createElement('input');
                    idInput.type = 'hidden';
                    idInput.name = 'id';
                    idInput.value = '<?php echo htmlspecialchars($project['id']); ?>';
                    
                    const nameInput = document.createElement('input');
                    nameInput.type = 'hidden';
                    nameInput.name = 'name';
                    nameInput.value = newName;
                    
                    form.appendChild(idInput);
                    form.appendChild(nameInput);
                    document.body.appendChild(form);
                    form.submit();
                } else {
                    // Cancel edit
                    input.remove();
                    projectName.style.display = 'block';
                    editLink.style.display = 'inline';
                }
            };
            
            // Handle enter key
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    saveEdit();
                } else if (e.key === 'Escape') {
                    e.preventDefault();
                    input.remove();
                    projectName.style.display = 'block';
                    editLink.style.display = 'inline';
                }
            });
            
            // Handle blur
            input.addEventListener('blur', saveEdit);
        });
    }
});
</script>

