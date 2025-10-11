<?php
// Get filter parameters
$status = $_GET['status'] ?? 'active';
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'name_asc';

// Filter and sort clients
$filteredClients = $clients;

// Apply status filter
if ($status !== 'all') {
    $filteredClients = array_filter($filteredClients, function($client) use ($status) {
        return ($client['status'] ?? 'active') === $status;
    });
}

// Apply search filter
if ($search !== '') {
    $filteredClients = array_filter($filteredClients, function($client) use ($search) {
        $name = strtolower($client['displayName'] ?? '');
        $email = strtolower($client['email'] ?? '');
        $searchLower = strtolower($search);
        return strpos($name, $searchLower) !== false || strpos($email, $searchLower) !== false;
    });
}

// Sort clients
usort($filteredClients, function($a, $b) use ($sort, $store) {
    switch ($sort) {
        case 'name_desc':
            return strcasecmp($b['displayName'] ?? '', $a['displayName'] ?? '');
        case 'projects_desc':
            $aProjects = ($store && method_exists($store, 'getProjectsByClient')) ? count($store->getProjectsByClient($a['id'])) : 0;
            $bProjects = ($store && method_exists($store, 'getProjectsByClient')) ? count($store->getProjectsByClient($b['id'])) : 0;
            return $bProjects - $aProjects;
        case 'modified_desc':
            $aModified = $a['updatedAt'] ?? $a['createdAt'] ?? '';
            $bModified = $b['updatedAt'] ?? $b['createdAt'] ?? '';
            return strcmp($bModified, $aModified);
        case 'status_asc':
            $aStatus = $a['status'] ?? 'active';
            $bStatus = $b['status'] ?? 'active';
            return strcmp($aStatus, $bStatus);
        case 'clio_contacts':
            // Sort by email presence (clio contacts have emails)
            $aHasEmail = !empty($a['email']);
            $bHasEmail = !empty($b['email']);
            return $bHasEmail - $aHasEmail;
        case 'name_asc':
        default:
            return strcasecmp($a['displayName'] ?? '', $b['displayName'] ?? '');
    }
});

// Count clients by status
$activeCount = count(array_filter($clients, fn($c) => ($c['status'] ?? 'active') === 'active'));
$archivedCount = count(array_filter($clients, fn($c) => ($c['status'] ?? 'active') === 'archived'));
?>

<div class="clio-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <div style="display: flex; align-items: center; gap: 16px;">
            <div style="position: relative;">
                <input type="text" id="client-search" placeholder="Search clients..." value="<?php echo htmlspecialchars($search); ?>" class="clio-input" style="width: 300px; padding-left: 36px;">
                <span style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #6c757d;">🔍</span>
            </div>
            
            <div style="display: flex; background: #f8f9fa; border-radius: 4px; border: 1px solid #e1e5e9;">
                <a href="?route=clients&status=active&search=<?php echo urlencode($search); ?>&sort=<?php echo urlencode($sort); ?>" 
                   style="padding: 8px 16px; text-decoration: none; color: #6c757d; font-size: 14px; font-weight: 500; border-radius: 3px; transition: all 0.2s ease; <?php echo $status === 'active' ? 'background: #ffffff; color: #1976d2; box-shadow: 0 1px 2px rgba(0,0,0,0.1);' : ''; ?>">
                    Active (<?php echo $activeCount; ?>)
                </a>
                <a href="?route=clients&status=archived&search=<?php echo urlencode($search); ?>&sort=<?php echo urlencode($sort); ?>" 
                   style="padding: 8px 16px; text-decoration: none; color: #6c757d; font-size: 14px; font-weight: 500; border-radius: 3px; transition: all 0.2s ease; <?php echo $status === 'archived' ? 'background: #ffffff; color: #1976d2; box-shadow: 0 1px 2px rgba(0,0,0,0.1);' : ''; ?>">
                    Archived (<?php echo $archivedCount; ?>)
                </a>
            </div>
        </div>
        
        <div style="display: flex; gap: 12px; align-items: center;">
            <a href="?route=dashboard" class="clio-btn-secondary">
                <span>←</span>
                <span>Back to Dashboard</span>
            </a>
            <button class="clio-btn" id="add-client-btn">
                <span>+</span>
                <span>Add Client</span>
            </button>
        </div>
    </div>
</div>

<?php if (empty($filteredClients)): ?>
    <div class="clio-card" style="text-align: center; padding: 60px 20px;">
        <div style="font-size: 48px; margin-bottom: 16px;">👥</div>
        <h3 style="margin: 0 0 8px 0; color: #2c3e50; font-size: 20px;">No clients found</h3>
        <p style="margin: 0 0 24px 0; color: #6c757d; font-size: 16px;">
            <?php if ($search !== ''): ?>
                No clients match your search criteria.
            <?php elseif ($status === 'archived'): ?>
                No archived clients.
            <?php else: ?>
                Get started by adding your first client.
            <?php endif; ?>
        </p>
        <?php if ($search === '' && $status === 'active'): ?>
            <button class="clio-btn" onclick="document.getElementById('add-client-btn').click()">Add your first client</button>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="clio-card">
        <table class="clio-table">
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Matters</th>
                    <th>Last Modified</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($filteredClients as $client): ?>
                    <?php
                    // Get project count for this client
                    $clientProjects = ($store && method_exists($store, 'getProjectsByClient')) ? $store->getProjectsByClient($client['id']) : [];
                    $projectCount = count($clientProjects);
                    
                    // Get last modified date
                    $lastModified = null;
                    if (!empty($clientProjects)) {
                        $lastModified = max(array_column($clientProjects, 'updatedAt'));
                        if (!$lastModified) {
                            $lastModified = max(array_column($clientProjects, 'createdAt'));
                        }
                    }
                    ?>
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="width: 40px; height: 40px; border-radius: 50%; background: #1976d2; color: white; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 16px;">
                                    <?php echo strtoupper(substr($client['displayName'] ?? 'C', 0, 1)); ?>
                                </div>
                                <div>
                                    <div style="font-weight: 600; color: #2c3e50;"><?php echo htmlspecialchars($client['displayName'] ?? 'Unknown Client'); ?></div>
                                </div>
                            </div>
                        </td>
                        <td><?php echo htmlspecialchars($client['email'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($client['phone'] ?? ''); ?></td>
                        <td><?php echo $projectCount; ?></td>
                        <td>
                            <?php if ($lastModified): ?>
                                <?php 
                                $date = new DateTime($lastModified);
                                echo $date->format('M j, Y');
                                ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="clio-status clio-status-<?php echo ($client['status'] ?? 'active') === 'active' ? 'active' : 'archived'; ?>">
                                <?php echo ucfirst($client['status'] ?? 'active'); ?>
                            </span>
                        </td>
                        <td>
                            <div style="display: flex; gap: 8px;">
                                <a href="?route=client&id=<?php echo htmlspecialchars($client['id']); ?>" class="clio-btn-secondary" style="padding: 6px 12px; font-size: 12px;">View</a>
                                <form method="post" action="?route=actions/update-client-status" style="display: inline;">
                                    <input type="hidden" name="clientId" value="<?php echo htmlspecialchars($client['id']); ?>">
                                    <input type="hidden" name="status" value="<?php echo ($client['status'] ?? 'active') === 'active' ? 'archived' : 'active'; ?>">
                                    <button type="submit" class="clio-btn-secondary" style="padding: 6px 12px; font-size: 12px;">
                                        <?php echo ($client['status'] ?? 'active') === 'active' ? 'Archive' : 'Activate'; ?>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<!-- Add Client Modal -->
<div id="add-client-modal" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="add-client-modal-title" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center; z-index: 2000;">
    <div style="background: #fff; border-radius: 8px; width: 500px; max-width: 90vw; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 20px 24px; border-bottom: 1px solid #e1e5e9;">
            <h3 id="add-client-modal-title" style="margin: 0; color: #2c3e50; font-size: 18px; font-weight: 600;">Add New Client</h3>
            <button aria-label="Close add client modal" onclick="closeAddClientModal()" style="background: none; border: none; font-size: 24px; color: #6c757d; cursor: pointer; padding: 0; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;">&times;</button>
        </div>
        <form method="post" action="?route=actions/create-client" style="padding: 24px;">
            <fieldset>
                <legend class="clio-form-label" style="margin-bottom: 8px;">Client Details</legend>
                <div class="clio-form-group">
                    <label for="client-name" class="clio-form-label">Client Name *</label>
                    <input aria-required="true" type="text" id="client-name" name="displayName" placeholder="Enter client name" required class="clio-input">
                </div>
                <div class="clio-form-group">
                    <label for="client-email" class="clio-form-label">Email</label>
                    <input type="email" id="client-email" name="email" placeholder="Enter email address" class="clio-input">
                </div>
                <div class="clio-form-group">
                    <label for="client-phone" class="clio-form-label">Phone</label>
                    <input type="tel" id="client-phone" name="phone" placeholder="Enter phone number" class="clio-input">
                </div>
            </fieldset>
            <div style="display: flex; justify-content: flex-end; gap: 12px; padding-top: 20px; border-top: 1px solid #e1e5e9; margin-top: 20px;">
                <button type="button" class="clio-btn-secondary" onclick="closeAddClientModal()">Cancel</button>
                <button type="submit" class="clio-btn">Add Client</button>
            </div>
        </form>
    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add client button
    document.getElementById('add-client-btn').addEventListener('click', function() {
        var modal = document.getElementById('add-client-modal');
        modal.style.display = 'flex';
        modal.setAttribute('aria-hidden', 'false');
    });
    
    // Search functionality with debounce
    let searchTimeout;
    document.getElementById('client-search').addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const searchValue = this.value;
        
        searchTimeout = setTimeout(function() {
            const currentUrl = new URL(window.location);
            currentUrl.searchParams.set('search', searchValue);
            window.location.href = currentUrl.toString();
        }, 800); // Wait 800ms after user stops typing
    });
    
    // Also allow search on Enter key
    document.getElementById('client-search').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            clearTimeout(searchTimeout);
            const currentUrl = new URL(window.location);
            currentUrl.searchParams.set('search', this.value);
            window.location.href = currentUrl.toString();
        }
    });
    
    // Sort functionality (guard if control present)
    var sortEl = document.getElementById('sort-select');
    if (sortEl) {
        sortEl.addEventListener('change', function() {
            const sort = this.value;
            const currentUrl = new URL(window.location);
            currentUrl.searchParams.set('sort', sort);
            window.location.href = currentUrl.toString();
        });
    }
});

function closeAddClientModal() {
    var modal = document.getElementById('add-client-modal');
    modal.style.display = 'none';
    modal.setAttribute('aria-hidden', 'true');
}

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    const modal = document.getElementById('add-client-modal');
    if (e.target === modal) {
        closeAddClientModal();
    }
});
</script>




