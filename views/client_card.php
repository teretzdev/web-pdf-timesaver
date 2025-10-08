<?php
/**
 * Client Card Component
 * Displays a client in a card format similar to Clio's interface
 */
if (!function_exists('renderClientCard')) {
function renderClientCard($client, $projectCount = 0, $lastModified = null) {
    $clientId = $client['id'] ?? '';
    $displayName = $client['displayName'] ?? 'Unnamed Client';
    $email = $client['email'] ?? '';
    $phone = $client['phone'] ?? '';
    $status = $client['status'] ?? 'active';
    
    // Format last modified date
    $lastModifiedText = 'No projects';
    if ($lastModified) {
        $date = new DateTime($lastModified);
        $lastModifiedText = 'last modified on ' . $date->format('m/d/y');
    } elseif ($projectCount > 0) {
        $lastModifiedText = 'No recent activity';
    }
    
    // Format project count
    $projectText = $projectCount === 1 ? '1 Project' : $projectCount . ' Projects';
    if ($projectCount === 0) {
        $projectText = 'No projects';
    }
    
    $statusClass = $status === 'archived' ? 'archived' : 'active';
    $statusText = $status === 'archived' ? 'Archived' : 'Active';
    
    echo '<div class="client-card ' . $statusClass . '" data-client-id="' . htmlspecialchars($clientId) . '">';
    echo '<div class="client-card-content">';
    echo '<div class="client-info">';
    echo '<h3 class="client-name">' . htmlspecialchars($displayName) . '</h3>';
    echo '<div class="client-meta">';
    echo '<span class="project-count">' . $projectText . '</span>';
    echo '<span class="separator">, </span>';
    echo '<span class="last-modified">' . $lastModifiedText . '</span>';
    echo '</div>';
    if ($email || $phone) {
        echo '<div class="client-contact">';
        if ($email) {
            echo '<span class="contact-item">📧 ' . htmlspecialchars($email) . '</span>';
        }
        if ($phone) {
            echo '<span class="contact-item">📞 ' . htmlspecialchars($phone) . '</span>';
        }
        echo '</div>';
    }
    echo '</div>';
    echo '<div class="client-actions">';
    echo '<div class="client-status">';
    echo '<select class="status-select" data-client-id="' . htmlspecialchars($clientId) . '">';
    echo '<option value="active" ' . ($status === 'active' ? 'selected' : '') . '>Active</option>';
    echo '<option value="archived" ' . ($status === 'archived' ? 'selected' : '') . '>Archived</option>';
    echo '</select>';
    echo '</div>';
    echo '<div class="action-buttons">';
    echo '<button class="btn btn-sm btn-secondary manage-access" data-client-id="' . htmlspecialchars($clientId) . '">Manage user access</button>';
    echo '<button class="btn btn-sm btn-danger delete-client" data-client-id="' . htmlspecialchars($clientId) . '">Delete client</button>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
}
}
?>

<style>
/* Dark mode hooks */
[data-theme] .client-card { background-color: var(--bg-color, #fff); }
[data-theme="dark"] .client-card { background-color: var(--bg-secondary, #2d2d2d); }

/* Mobile CSS features */
.client-card, .btn, .status-select {
    -webkit-tap-highlight-color: rgba(0,0,0,0);
    -webkit-touch-callout: none;
    -webkit-user-select: none;
    user-select: none;
}

.client-card {
    background: var(--bg-secondary, #fff);
    background-color: var(--bg-color, #fff);
    border: 1px solid var(--border-color, #eef2f7);
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 16px;
    transition: all 0.2s ease;
    cursor: pointer;
    position: relative;
    -webkit-overflow-scrolling: touch;
    overscroll-behavior: contain;
    will-change: transform;
}

.client-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); transform: translateY(-2px); }

.client-card.archived { opacity: 0.7; background: #f8f9fa; }

.client-card-content { display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; }

.client-info { flex: 1; }

.client-name { margin: 0 0 8px 0; font-size: 18px; font-weight: 600; color: var(--text-color, #1a2b3b); }

.client-meta { color: #65748b; font-size: 14px; margin-bottom: 8px; }

.client-contact { display: flex; flex-direction: column; gap: 4px; }

.contact-item { font-size: 16px; line-height: 1.5; color: #65748b; }

.client-actions { display: flex; flex-direction: column; align-items: flex-end; gap: 12px; }

.client-status { display: flex; align-items: center; }

.status-select { padding: 6px 12px; border: 1px solid #d7dce3; border-radius: 4px; font-size: 16px; min-height: 44px; background: #fff; }

.action-buttons { display: flex; gap: 8px; }

.btn-sm { padding: 12px; font-size: 16px; min-height: 44px; min-width: 44px; border-radius: 4px; }

.btn-danger { background: #dc3545; color: #fff; }

.btn-danger:hover { background: #c82333; }

.btn-secondary { background: #6c757d; color: #fff; border: 1px solid #6c757d; }

.btn-secondary:hover { background: #5a6268; border-color: #545b62; }

.separator { color: #65748b; }

/* Responsive */
@media (max-width: 768px) { .client-card-content { flex-direction: column; gap: 16px; } .client-actions { flex-direction: row; justify-content: space-between; width: 100%; } }
@media (max-width: 1024px) { .client-card-content { gap: 16px; } }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle client card clicks
    document.querySelectorAll('.client-card').forEach(card => {
        card.addEventListener('click', function(e) {
            if (e.target.tagName === 'BUTTON' || e.target.tagName === 'SELECT' || e.target.tagName === 'A') { return; }
            const clientId = this.getAttribute('data-client-id');
            if (clientId) { window.location.href = '?route=client&id=' + clientId; }
        });
        // Touch gestures
        let touchStartX = 0, touchStartY = 0;
        card.addEventListener('touchstart', function(e){ const t=e.touches[0]; touchStartX=t.clientX; touchStartY=t.clientY; }, {passive:true});
        card.addEventListener('touchmove', function(e){ /* touchmove */ }, {passive:true});
        card.addEventListener('touchend', function(e){ const t=e.changedTouches[0]; const dx=(t.clientX-touchStartX); const dy=(t.clientY-touchStartY); if (Math.abs(dx)>50 && Math.abs(dy)<30) { /* swipe */ } });
    });
    
    // Handle status changes
    document.querySelectorAll('.status-select').forEach(select => {
        select.addEventListener('change', function() {
            const clientId = this.getAttribute('data-client-id');
            const newStatus = this.value;
            const form = document.createElement('form');
            form.method = 'POST'; form.action = '?route=actions/update-client-status';
            const clientIdInput = document.createElement('input'); clientIdInput.type = 'hidden'; clientIdInput.name = 'clientId'; clientIdInput.value = clientId;
            const statusInput = document.createElement('input'); statusInput.type = 'hidden'; statusInput.name = 'status'; statusInput.value = newStatus;
            form.appendChild(clientIdInput); form.appendChild(statusInput); document.body.appendChild(form); form.submit();
        });
    });
    
    document.querySelectorAll('.manage-access').forEach(button => { button.addEventListener('click', function(e) { e.stopPropagation(); alert('User access management feature coming soon!'); }); });
    document.querySelectorAll('.delete-client').forEach(button => { button.addEventListener('click', function(e) { e.stopPropagation(); const clientId = this.getAttribute('data-client-id'); if (confirm('Are you sure you want to delete this client? This will also delete all associated projects and documents. This action cannot be undone.')) { const form = document.createElement('form'); form.method = 'POST'; form.action = '?route=actions/delete-client'; const clientIdInput = document.createElement('input'); clientIdInput.type = 'hidden'; clientIdInput.name = 'clientId'; clientIdInput.value = clientId; form.appendChild(clientIdInput); document.body.appendChild(form); form.submit(); } }); });
});
</script>
