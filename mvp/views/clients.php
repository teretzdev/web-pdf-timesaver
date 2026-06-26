<?php
// Get filter parameters
$status = strtolower(trim((string)($_GET['status'] ?? 'active')));
if (!in_array($status, ['active', 'archived', 'all'], true)) {
    $status = 'active';
}
$search = trim((string)($_GET['search'] ?? ''));
$sort = $_GET['sort'] ?? 'name_asc';

// Filter and sort clients
$filteredClients = $clients;

// Apply status filter
if ($status !== 'all') {
    $filteredClients = array_filter($filteredClients, function($client) use ($status) {
        $clientStatus = strtolower(trim((string)($client['status'] ?? 'active')));
        if ($clientStatus === '') {
            $clientStatus = 'active';
        }
        return $clientStatus === $status;
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
        case 'pdftimesaver_contacts':
            // Sort by email presence (PDFTimeSaver contacts have emails)
            $aHasEmail = !empty($a['email']);
            $bHasEmail = !empty($b['email']);
            return $bHasEmail - $aHasEmail;
        case 'name_asc':
        default:
            return strcasecmp($a['displayName'] ?? '', $b['displayName'] ?? '');
    }
});

// Count clients by status
$activeCount = count(array_filter($clients, function ($c) {
    $clientStatus = strtolower(trim((string)($c['status'] ?? 'active')));
    if ($clientStatus === '') {
        $clientStatus = 'active';
    }
    return $clientStatus === 'active';
}));
$archivedCount = count(array_filter($clients, function ($c) {
    $clientStatus = strtolower(trim((string)($c['status'] ?? 'active')));
    return $clientStatus === 'archived';
}));
?>
<style>
    /* Stacking: keep row links above any stray fixed layers (e.g. overlays) */
    .client-cell-identity {
        position: relative;
        z-index: 2;
    }
</style>

<div class="pdftimesaver-card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <div style="display: flex; align-items: center; gap: 16px;">
            <div style="position: relative;">
                <input type="text" id="client-search" placeholder="Search clients..." value="<?php echo htmlspecialchars($search); ?>" class="pdftimesaver-input" style="width: 300px; padding-left: 36px;">
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
        
        <div class="button-group" style="display: flex; gap: 12px; align-items: center;">
            <a href="?route=dashboard" class="pdftimesaver-btn-secondary">
                <span>←</span>
                <span>Back to Dashboard</span>
            </a>
        </div>
    </div>
    <p style="margin: 0; color: #64748b; font-size: 13px;">Tip: Use the Actions column to archive or permanently delete a client.</p>
</div>

<div class="pdftimesaver-card wpts-form-shell" id="add-client-form-section" style="margin-top: 12px;">
    <h3 class="wpts-form-title">Add New Client</h3>
    <p class="wpts-form-help">Create a client directly here. The Add Client button scrolls to this form.</p>
    <form method="post" action="?route=actions/create-client" class="wpts-form-grid">
        <div class="wpts-form-grid wpts-form-grid-2">
            <div class="wpts-form-group">
                <label for="client-name" class="pdftimesaver-form-label">Display Name</label>
                <input type="text" id="client-name" name="displayName" placeholder="Enter client name" class="pdftimesaver-input">
            </div>
            <div class="wpts-form-group">
                <label for="client-company" class="pdftimesaver-form-label">Company Name</label>
                <input type="text" id="client-company" name="company" placeholder="Enter company name" class="pdftimesaver-input">
            </div>
            <div class="wpts-form-group">
                <label for="client-email" class="pdftimesaver-form-label">Email</label>
                <input type="email" id="client-email" name="email" placeholder="Enter email address" class="pdftimesaver-input">
            </div>
            <div class="wpts-form-group">
                <label for="client-phone" class="pdftimesaver-form-label">Phone</label>
                <input type="tel" id="client-phone" name="phone" placeholder="Enter phone number" class="pdftimesaver-input">
            </div>
        </div>
        <div class="wpts-form-actions">
            <button type="submit" class="pdftimesaver-btn">Create Client</button>
        </div>
    </form>
</div>

<?php if (empty($filteredClients)): ?>
    <div class="pdftimesaver-card" style="text-align: center; padding: 60px 20px;">
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
            <button type="button" class="pdftimesaver-btn" data-scroll-add-client="1">Add your first client</button>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="pdftimesaver-card">
        <div class="table-responsive">
            <table class="pdftimesaver-table">
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
                        $updatedDates = array_values(array_filter(array_column($clientProjects, 'updatedAt')));
                        $createdDates = array_values(array_filter(array_column($clientProjects, 'createdAt')));

                        if (!empty($updatedDates)) {
                            $lastModified = max($updatedDates);
                        } elseif (!empty($createdDates)) {
                            $lastModified = max($createdDates);
                        }
                    }
                    $clientViewHref = '?route=client&id=' . urlencode((string)($client['id'] ?? ''));
                    ?>
                    <tr>
                        <td>
                            <a class="client-cell-identity" href="<?php echo htmlspecialchars($clientViewHref); ?>" aria-label="View <?php echo htmlspecialchars((string)($client['displayName'] ?? 'Client')); ?>" style="display: flex; align-items: center; gap: 12px; text-decoration: none; color: inherit;">
                                <span style="width: 40px; height: 40px; border-radius: 50%; background: #1976d2; color: white; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 16px; flex-shrink: 0;">
                                    <?php echo strtoupper(substr((string)($client['displayName'] ?? 'C'), 0, 1)); ?>
                                </span>
                                <span style="font-weight: 600; color: #2c3e50;"><?php echo htmlspecialchars($client['displayName'] ?? 'Unknown Client'); ?></span>
                            </a>
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
                            <span class="pdftimesaver-status pdftimesaver-status-<?php echo ($client['status'] ?? 'active') === 'active' ? 'active' : 'archived'; ?>">
                                <?php echo ucfirst($client['status'] ?? 'active'); ?>
                            </span>
                        </td>
                        <td>
                            <div class="button-group" style="display: flex; gap: 8px;">
                                <a href="<?php echo htmlspecialchars($clientViewHref); ?>" class="pdftimesaver-btn-secondary pdftimesaver-btn-sm">View</a>
                                <form method="post" action="?route=actions/update-client-status" style="display: inline;">
                                    <input type="hidden" name="clientId" value="<?php echo htmlspecialchars($client['id']); ?>">
                                    <input type="hidden" name="status" value="<?php echo ($client['status'] ?? 'active') === 'active' ? 'archived' : 'active'; ?>">
                                    <button type="submit" class="pdftimesaver-btn-secondary pdftimesaver-btn-sm">
                                        <?php echo ($client['status'] ?? 'active') === 'active' ? 'Archive' : 'Activate'; ?>
                                    </button>
                                </form>
                                <form method="post" action="?route=actions/delete-client" style="display: inline;" onsubmit="return confirm('Delete this client? This cannot be undone.');">
                                    <input type="hidden" name="clientId" value="<?php echo htmlspecialchars($client['id']); ?>">
                                    <button type="submit" class="pdftimesaver-btn-secondary pdftimesaver-btn-sm" style="border-color: #fecaca; color: #b91c1c;">
                                        Delete
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validate display/company minimum before submit
    var addClientForm = document.querySelector('#add-client-form-section form[action*="actions/create-client"]');
    if (addClientForm) {
        addClientForm.addEventListener('submit', function(event) {
            var nameEl = document.getElementById('client-name');
            var companyEl = document.getElementById('client-company');
            var displayName = (nameEl && nameEl.value ? nameEl.value : '').trim();
            var companyName = (companyEl && companyEl.value ? companyEl.value : '').trim();
            if (!displayName && !companyName) {
                event.preventDefault();
                alert('Enter a display name or company name.');
                return;
            }
            if (!displayName && companyName && nameEl) {
                nameEl.value = companyName;
            }
            if (addClientForm.dataset.submitting === '1') {
                event.preventDefault();
                return;
            }
            addClientForm.dataset.submitting = '1';
            var submitBtn = addClientForm.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Adding...';
            }
        });
    }

    // Jump-to-add-client handlers for any CTA buttons.
    document.querySelectorAll('[data-scroll-add-client]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var formSection = document.getElementById('add-client-form-section');
            var nameEl = document.getElementById('client-name');
            if (formSection) {
                formSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
            window.setTimeout(function() {
                if (nameEl) {
                    nameEl.focus();
                }
            }, 250);
        });
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
</script>




