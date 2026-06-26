<?php
// Render breadcrumb navigation
// Breadcrumb navigation disabled for now
$clientFieldRows = is_array($clientFieldRows ?? null) ? array_values($clientFieldRows) : [];
$clientCustomFieldValues = is_array($clientCustomFieldValues ?? null) ? $clientCustomFieldValues : [];

/** Client Field Manager rows that map to the fixed profile block — never shown again under Custom Fields (checklist §2). */
$clientProfileReservedLinkIds = [
    'client_display_name',
    'client_email',
    'client_phone',
    'client_company',
    'client_address',
    'client_full_name',
    'client_first_name',
    'client_middle_name',
    'client_last_name',
    'client_city',
    'client_state',
    'client_zip',
];
$reservedLinkLookup = array_fill_keys($clientProfileReservedLinkIds, true);

$dynamicLabelMap = [];
$dynamicClientFields = [];
$systemClientFieldByLink = [];
foreach ($clientFieldRows as $fieldRow) {
    $fieldId = (string)($fieldRow['id'] ?? '');
    if ($fieldId === '') {
        continue;
    }
    $linkId = strtolower(trim((string)($fieldRow['linkId'] ?? '')));
    $matchingTag = strtolower(trim((string)($fieldRow['matchingTag'] ?? '')));
    $displayName = trim((string)($fieldRow['displayName'] ?? ''));
    if ($displayName === '') {
        $displayName = 'Custom Field';
    }
    if ($linkId !== '') {
        $dynamicLabelMap[$linkId] = $displayName;
    }
    if ($matchingTag !== '') {
        $dynamicLabelMap[$matchingTag] = $displayName;
    }
    if (!empty($fieldRow['isSystem'])) {
        if ($linkId !== '') {
            $systemClientFieldByLink[$linkId] = [
                'id' => $fieldId,
                'displayName' => $displayName,
                'fieldType' => strtolower((string)($fieldRow['fieldType'] ?? 'text')),
                'value' => (string)($clientCustomFieldValues[$fieldId] ?? ''),
            ];
        }
    } else {
        if ($linkId !== '' && isset($reservedLinkLookup[$linkId])) {
            // Allow non-system "client_full_name" in Custom Fields so legacy rows stay editable
            if ($linkId !== 'client_full_name' || !empty($fieldRow['isSystem'])) {
                continue;
            }
        }
        $dynamicClientFields[] = [
            'id' => $fieldId,
            'displayName' => $displayName,
            'fieldType' => strtolower((string)($fieldRow['fieldType'] ?? 'text')),
            'value' => (string)($clientCustomFieldValues[$fieldId] ?? ''),
        ];
    }
}

$labelDisplayName = $dynamicLabelMap['client_display_name']
    ?? ($dynamicLabelMap['display_name'] ?? 'Display Name');
$labelEmail = $dynamicLabelMap['client_email'] ?? ($dynamicLabelMap['email'] ?? 'Email');
$labelPhone = $dynamicLabelMap['client_phone'] ?? ($dynamicLabelMap['phone'] ?? 'Phone');
$labelCompany = $dynamicLabelMap['client_company'] ?? ($dynamicLabelMap['company'] ?? 'Company');
$labelAddress = $dynamicLabelMap['client_address'] ?? ($dynamicLabelMap['address'] ?? 'Address');

$clientPhoneRaw = trim((string)($client['phone'] ?? ''));
$clientPhoneTelHref = '';
if ($clientPhoneRaw !== '') {
    $compact = preg_replace('/[^\d+]/', '', $clientPhoneRaw);
    if ($compact !== '') {
        $clientPhoneTelHref = 'tel:' . $compact;
    }
}

$clientEmailRaw = trim((string)($client['email'] ?? ''));
$returnToRaw = isset($_GET['returnTo']) ? (string)$_GET['returnTo'] : '';
$returnTo = (strpos($returnToRaw, '?route=') === 0) ? $returnToRaw : '';

$fixedSystemFieldLabels = [
    'client_full_name' => 'Full Name',
    'client_first_name' => 'First Name',
    'client_middle_name' => 'Middle Name',
    'client_last_name' => 'Last Name',
    'client_city' => 'City',
    'client_state' => 'State',
    'client_zip' => 'ZIP',
];
$fixedSystemFields = [];
foreach ($fixedSystemFieldLabels as $linkId => $fallbackLabel) {
    $row = $systemClientFieldByLink[$linkId] ?? null;
    if (!is_array($row)) {
        continue;
    }
    $fixedSystemFields[$linkId] = [
        'id' => (string)($row['id'] ?? ''),
        'label' => trim((string)($row['displayName'] ?? '')) !== '' ? (string)$row['displayName'] : $fallbackLabel,
        'value' => (string)($row['value'] ?? ''),
    ];
}
?>

<div class="pdftimesaver-card">
<div class="client-header" role="region" aria-label="Client header">
    <div class="client-info">
        <h1><a href="?route=client&id=<?php echo htmlspecialchars($client['id']); ?>" class="client-name-link"><?php echo htmlspecialchars($client['displayName'] ?? 'Client'); ?></a></h1>
        <div class="client-meta">
            <?php if ($clientEmailRaw !== ''): ?>
                <span class="meta-item">📧 <?php echo htmlspecialchars($clientEmailRaw); ?></span>
            <?php endif; ?>
            <?php if ($clientPhoneRaw !== ''): ?>
                <span class="meta-item">📞
                    <?php if ($clientPhoneTelHref !== ''): ?>
                        <a href="<?php echo htmlspecialchars($clientPhoneTelHref); ?>"><?php echo htmlspecialchars($clientPhoneRaw); ?></a>
                    <?php else: ?>
                        <?php echo htmlspecialchars($clientPhoneRaw); ?>
                    <?php endif; ?>
                </span>
            <?php endif; ?>
        </div>
    </div>
    <div class="client-actions" style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;">
        <a href="<?php echo htmlspecialchars($returnTo !== '' ? $returnTo : '?route=clients'); ?>" class="pdftimesaver-btn-secondary" aria-label="Back"><?php echo $returnTo !== '' ? '← Back to Project' : '← Back to Clients'; ?></a>
        <form method="post" action="?route=actions/delete-client" style="display:inline;margin:0;" onsubmit="return confirm('Delete this client? This cannot be undone.');">
            <input type="hidden" name="clientId" value="<?php echo htmlspecialchars($client['id']); ?>">
            <button type="submit" class="pdftimesaver-btn-secondary" style="border-color:#fecaca;color:#b91c1c;" aria-label="Delete this client">Delete Client</button>
        </form>
    </div>
</div>

<div class="client-tabs" role="tablist" aria-label="Client tabs">
    <div class="tab-nav">
        <a href="?route=client&id=<?php echo htmlspecialchars($client['id']); ?>" class="tab-link active" role="tab" aria-selected="true" title="Number of projects (cases) linked to this client">Projects (<?php echo count($projects); ?>)</a>
    </div>
    <p style="margin: 10px 0 0 0; font-size: 13px; color: #64748b; line-height: 1.45; max-width: 52rem;">
        The number in parentheses is how many <strong>projects</strong> this client has—each project holds documents and workflow for a matter. Use <strong>Add new project</strong> below to create another.
    </p>
</div>

</div>

<div class="pdftimesaver-card wpts-form-shell" style="margin-bottom: 16px;">
    <h3 class="wpts-form-title">Client Profile</h3>
    <p class="wpts-form-help">Edits save automatically (shortly after you change a field or leave it).</p>
    <form method="post" action="?route=actions/update-client-profile" novalidate class="client-profile-form" style="display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:12px;align-items:end;">
        <input type="hidden" name="clientId" value="<?php echo htmlspecialchars($client['id']); ?>">
        <input type="hidden" name="returnTo" value="<?php echo htmlspecialchars($returnTo); ?>">
        <!-- §2.3 Display name on its own full-width row (core client name, not a dynamic Field Manager row). -->
        <div style="grid-column: 1 / -1;">
            <label class="pdftimesaver-form-label" for="client-display-name"><?php echo htmlspecialchars($labelDisplayName); ?></label>
            <input id="client-display-name" class="pdftimesaver-input" name="displayName" value="<?php echo htmlspecialchars((string)($client['displayName'] ?? '')); ?>">
        </div>
        <!-- Optional: Field Manager "client full name" system row (single line), when configured. -->
        <?php if (!empty($fixedSystemFields['client_full_name']['id'])): ?>
        <div style="grid-column: 1 / -1;">
            <label class="pdftimesaver-form-label" for="client-full-name-line"><?php echo htmlspecialchars((string)($fixedSystemFields['client_full_name']['label'] ?? 'Full Name')); ?></label>
            <input id="client-full-name-line" class="pdftimesaver-input" name="<?php echo 'systemClientFields[' . htmlspecialchars((string)$fixedSystemFields['client_full_name']['id']) . ']'; ?>" value="<?php echo htmlspecialchars((string)($fixedSystemFields['client_full_name']['value'] ?? '')); ?>">
        </div>
        <?php endif; ?>
        <!-- §2.4–2.6 Permanent name line: first / middle / last only (own sub-row). -->
        <div style="grid-column: 1 / -1; display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px;">
            <div>
                <label class="pdftimesaver-form-label" for="client-first-name"><?php echo htmlspecialchars((string)($fixedSystemFields['client_first_name']['label'] ?? 'First Name')); ?></label>
                <input id="client-first-name" class="pdftimesaver-input" name="<?php echo !empty($fixedSystemFields['client_first_name']['id']) ? ('systemClientFields[' . htmlspecialchars((string)$fixedSystemFields['client_first_name']['id']) . ']') : ''; ?>" value="<?php echo htmlspecialchars((string)($fixedSystemFields['client_first_name']['value'] ?? '')); ?>">
            </div>
            <div>
                <label class="pdftimesaver-form-label" for="client-middle-name"><?php echo htmlspecialchars((string)($fixedSystemFields['client_middle_name']['label'] ?? 'Middle Name')); ?></label>
                <input id="client-middle-name" class="pdftimesaver-input" name="<?php echo !empty($fixedSystemFields['client_middle_name']['id']) ? ('systemClientFields[' . htmlspecialchars((string)$fixedSystemFields['client_middle_name']['id']) . ']') : ''; ?>" value="<?php echo htmlspecialchars((string)($fixedSystemFields['client_middle_name']['value'] ?? '')); ?>">
            </div>
            <div>
                <label class="pdftimesaver-form-label" for="client-last-name"><?php echo htmlspecialchars((string)($fixedSystemFields['client_last_name']['label'] ?? 'Last Name')); ?></label>
                <input id="client-last-name" class="pdftimesaver-input" name="<?php echo !empty($fixedSystemFields['client_last_name']['id']) ? ('systemClientFields[' . htmlspecialchars((string)$fixedSystemFields['client_last_name']['id']) . ']') : ''; ?>" value="<?php echo htmlspecialchars((string)($fixedSystemFields['client_last_name']['value'] ?? '')); ?>">
            </div>
        </div>
        <div style="grid-column: 1 / -1; display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px;">
            <div>
                <label class="pdftimesaver-form-label" for="client-email"><?php echo htmlspecialchars($labelEmail); ?></label>
                <input id="client-email" class="pdftimesaver-input" type="email" name="email" value="<?php echo htmlspecialchars((string)($client['email'] ?? '')); ?>">
            </div>
            <div>
                <label class="pdftimesaver-form-label" for="client-phone"><?php echo htmlspecialchars($labelPhone); ?></label>
                <input id="client-phone" class="pdftimesaver-input" name="phone" value="<?php echo htmlspecialchars((string)($client['phone'] ?? '')); ?>">
            </div>
            <div>
                <label class="pdftimesaver-form-label" for="client-company"><?php echo htmlspecialchars($labelCompany); ?></label>
                <input id="client-company" class="pdftimesaver-input" name="company" value="<?php echo htmlspecialchars((string)($client['company'] ?? '')); ?>">
            </div>
        </div>
        <!-- §2.7 Address full width -->
        <div style="grid-column: 1 / -1;">
            <label class="pdftimesaver-form-label" for="client-address"><?php echo htmlspecialchars($labelAddress); ?></label>
            <input id="client-address" class="pdftimesaver-input" name="address" value="<?php echo htmlspecialchars((string)($client['address'] ?? '')); ?>">
        </div>
        <!-- §2.8–2.10 City / State / ZIP -->
        <div style="grid-column: 1 / -1; display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px;">
            <div>
                <label class="pdftimesaver-form-label" for="client-city"><?php echo htmlspecialchars((string)($fixedSystemFields['client_city']['label'] ?? 'City')); ?></label>
                <input id="client-city" class="pdftimesaver-input" name="<?php echo !empty($fixedSystemFields['client_city']['id']) ? ('systemClientFields[' . htmlspecialchars((string)$fixedSystemFields['client_city']['id']) . ']') : ''; ?>" value="<?php echo htmlspecialchars((string)($fixedSystemFields['client_city']['value'] ?? '')); ?>">
            </div>
            <div>
                <label class="pdftimesaver-form-label" for="client-state"><?php echo htmlspecialchars((string)($fixedSystemFields['client_state']['label'] ?? 'State')); ?></label>
                <input id="client-state" class="pdftimesaver-input" name="<?php echo !empty($fixedSystemFields['client_state']['id']) ? ('systemClientFields[' . htmlspecialchars((string)$fixedSystemFields['client_state']['id']) . ']') : ''; ?>" value="<?php echo htmlspecialchars((string)($fixedSystemFields['client_state']['value'] ?? '')); ?>">
            </div>
            <div>
                <label class="pdftimesaver-form-label" for="client-zip"><?php echo htmlspecialchars((string)($fixedSystemFields['client_zip']['label'] ?? 'ZIP')); ?></label>
                <input id="client-zip" class="pdftimesaver-input" name="<?php echo !empty($fixedSystemFields['client_zip']['id']) ? ('systemClientFields[' . htmlspecialchars((string)$fixedSystemFields['client_zip']['id']) . ']') : ''; ?>" value="<?php echo htmlspecialchars((string)($fixedSystemFields['client_zip']['value'] ?? '')); ?>">
            </div>
        </div>
        <!-- §2.1 Notes: fixed textarea only — never listed under Custom Fields. -->
        <div style="grid-column: 1 / -1;">
            <label class="pdftimesaver-form-label" for="client-notes">Notes</label>
            <textarea id="client-notes" class="pdftimesaver-input" name="notes" rows="3"><?php echo htmlspecialchars((string)($client['notes'] ?? '')); ?></textarea>
        </div>
        <!-- §2.12 Custom Fields: non-reserved dynamic Field Manager rows only -->
        <div class="wpts-section-box" style="grid-column: 1 / -1; margin-top: 8px;">
            <h4 class="wpts-section-box-title">Custom Fields</h4>
            <?php if (empty($dynamicClientFields)): ?>
                <p style="margin: 0; color: #64748b;">No custom client fields yet. Add them in Field Manager under Client Fields.</p>
            <?php else: ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fit,minmax(220px,1fr)); gap: 12px;">
                    <?php foreach ($dynamicClientFields as $dynamicField): ?>
                        <div style="border:1px solid #cdd9ea; border-radius:8px; padding:10px; background:#ffffff;">
                            <div style="margin-bottom:8px;">
                                <label class="pdftimesaver-form-label" for="custom-field-<?php echo htmlspecialchars($dynamicField['id']); ?>" style="margin:0;">
                                    <?php echo htmlspecialchars($dynamicField['displayName']); ?>
                                </label>
                            </div>
                            <?php if ($dynamicField['fieldType'] === 'checkbox'): ?>
                                <label style="display:flex;align-items:center;gap:8px;">
                                    <input type="checkbox" id="custom-field-<?php echo htmlspecialchars($dynamicField['id']); ?>" name="customFields[<?php echo htmlspecialchars($dynamicField['id']); ?>]" value="1" <?php echo !empty($dynamicField['value']) ? 'checked' : ''; ?>>
                                    <span style="font-size:13px;color:#475569;">Checked</span>
                                </label>
                            <?php else: ?>
                                <input id="custom-field-<?php echo htmlspecialchars($dynamicField['id']); ?>" class="pdftimesaver-input" name="customFields[<?php echo htmlspecialchars($dynamicField['id']); ?>]" value="<?php echo htmlspecialchars($dynamicField['value']); ?>" type="<?php echo $dynamicField['fieldType'] === 'number' ? 'number' : ($dynamicField['fieldType'] === 'date' ? 'date' : ($dynamicField['fieldType'] === 'email' ? 'email' : ($dynamicField['fieldType'] === 'phone' ? 'tel' : 'text'))); ?>">
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p style="margin:12px 0 0 0; color:#64748b; font-size:12px;">Only extra fields from Field Manager appear here (not display name, notes, email, or the permanent identity/address block).</p>
            <?php endif; ?>
        </div>
    </form>
    <div id="client-profile-status" style="margin-top:10px; min-height:18px; font-size:12px; color:#0f766e;" aria-live="polite"></div>
</div>

<div class="pdftimesaver-card">
<div class="projects-section">
    <div class="projects-header">
        <h2>Projects</h2>
        <button class="pdftimesaver-btn" id="add-project-btn">Add new project</button>
    </div>
    
    <?php if (empty($projects)): ?>
        <div class="empty-state">
            <div class="empty-icon">📁</div>
            <h3>No projects yet</h3>
            <p>Create your first project for this client to get started.</p>
            <button class="pdftimesaver-btn" onclick="document.getElementById('add-project-btn').click()">Add your first project</button>
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
                            <button class="pdftimesaver-btn-secondary btn-sm btn-danger delete-project" data-project-id="<?php echo htmlspecialchars($project['id']); ?>">
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
                <button type="button" class="pdftimesaver-btn-secondary" onclick="closeAddProjectModal()">Cancel</button>
                <button type="submit" class="pdftimesaver-btn">Add Project</button>
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

.client-meta a {
    color: #0b6bcb;
    text-decoration: underline;
    text-underline-offset: 2px;
}

.client-meta a:hover {
    color: #094d92;
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
    const clientId = <?php echo json_encode((string)($client['id'] ?? '')); ?>;
    const displayNameInput = document.getElementById('client-display-name');
    const companyInput = document.getElementById('client-company');
    const profileForm = document.querySelector('form[action*="actions/update-client-profile"]');
    const profileStatus = document.getElementById('client-profile-status');
    let autosaveTimer = null;
    let autosaveInFlight = false;
    let autosaveQueued = false;
    let allowDuplicateDisplayNameOnce = false;
    const lastSavedProfile = {
        displayName: <?php echo json_encode((string)($client['displayName'] ?? '')); ?>,
        company: <?php echo json_encode((string)($client['company'] ?? '')); ?>
    };

    function setProfileStatus(message, isError) {
        if (!profileStatus) return;
        profileStatus.textContent = message || '';
        profileStatus.style.color = isError ? '#b91c1c' : '#0f766e';
    }

    function collectProfilePayload() {
        const payload = {
            clientId: clientId,
            displayName: displayNameInput ? String(displayNameInput.value || '').trim() : '',
            email: '',
            phone: '',
            company: companyInput ? String(companyInput.value || '').trim() : '',
            address: '',
            notes: '',
            customFields: {},
            systemClientFields: {}
        };
        if (!profileForm) return payload;
        const formData = new FormData(profileForm);
        formData.forEach(function (value, key) {
            const val = String(value || '');
            if (key === 'email') payload.email = val;
            else if (key === 'phone') payload.phone = val;
            else if (key === 'address') payload.address = val;
            else if (key === 'notes') payload.notes = val;
            else if (key === 'company') payload.company = val;
            else if (key.startsWith('customFields[') && key.endsWith(']')) {
                const fieldId = key.substring(13, key.length - 1);
                if (fieldId) payload.customFields[fieldId] = val;
            } else if (key.startsWith('systemClientFields[') && key.endsWith(']')) {
                const fieldId = key.substring(19, key.length - 1);
                if (fieldId) payload.systemClientFields[fieldId] = val;
            }
        });

        profileForm.querySelectorAll('input[type="checkbox"][name^="customFields["]').forEach(function (el) {
            const fieldId = el.name.substring(13, el.name.length - 1);
            if (!fieldId) return;
            payload.customFields[fieldId] = el.checked ? '1' : '';
        });
        profileForm.querySelectorAll('input[type="checkbox"][name^="systemClientFields["]').forEach(function (el) {
            const fieldId = el.name.substring(19, el.name.length - 1);
            if (!fieldId) return;
            payload.systemClientFields[fieldId] = el.checked ? '1' : '';
        });
        return payload;
    }

    async function runAutosave() {
        if (!profileForm || autosaveInFlight) {
            autosaveQueued = true;
            return;
        }
        const displayName = (displayNameInput ? displayNameInput.value : '').trim();
        const companyName = (companyInput ? companyInput.value : '').trim();
        // Revert if both are empty: avoids a nameless client (brief: clearing Display Name must not remove the client).
        if (!displayName && !companyName) {
            if (displayNameInput) {
                displayNameInput.value = lastSavedProfile.displayName;
            }
            if (companyInput) {
                companyInput.value = lastSavedProfile.company;
            }
            setProfileStatus('Enter a display name or company name. Restored previous values.', true);
            return;
        }

        autosaveInFlight = true;
        setProfileStatus('Saving...');
        try {
            const payload = collectProfilePayload();
            if (allowDuplicateDisplayNameOnce) {
                payload.allowDuplicateDisplayName = true;
            }
            const response = await fetch('?route=api/client/update-profile-autosave', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const result = await response.json().catch(function () { return null; });
            if (result && result.code === 'duplicate_display_name' && response.status === 409 && !allowDuplicateDisplayNameOnce) {
                const ok = window.confirm('This client name already exists. Are you sure you want more than one client with this name?');
                if (ok) {
                    allowDuplicateDisplayNameOnce = true;
                    autosaveInFlight = false;
                    if (autosaveQueued) {
                        autosaveQueued = false;
                    }
                    void runAutosave();
                    return;
                }
                if (displayNameInput) {
                    displayNameInput.value = lastSavedProfile.displayName;
                }
                if (companyInput) {
                    companyInput.value = lastSavedProfile.company;
                }
                setProfileStatus('Save cancelled. Restored previous name.', true);
                autosaveInFlight = false;
                if (autosaveQueued) {
                    autosaveQueued = false;
                    void runAutosave();
                }
                return;
            }
            if (!response.ok || !result || !result.success) {
                throw new Error((result && result.error) ? result.error : 'Autosave failed');
            }
            allowDuplicateDisplayNameOnce = false;
            const saved = result.client && typeof result.client === 'object' ? result.client : null;
            if (saved) {
                lastSavedProfile.displayName = String(saved.displayName != null ? saved.displayName : '');
                lastSavedProfile.company = String(saved.company != null ? saved.company : '');
            }
            setProfileStatus('Saved.');
        } catch (error) {
            allowDuplicateDisplayNameOnce = false;
            setProfileStatus(error.message || String(error), true);
        } finally {
            autosaveInFlight = false;
            if (autosaveQueued) {
                autosaveQueued = false;
                void runAutosave();
            }
        }
    }

    function scheduleAutosave(immediate) {
        if (!profileForm) return;
        if (autosaveTimer) {
            clearTimeout(autosaveTimer);
            autosaveTimer = null;
        }
        if (immediate) {
            void runAutosave();
            return;
        }
        autosaveTimer = setTimeout(function () {
            autosaveTimer = null;
            void runAutosave();
        }, 450);
    }

    if (profileForm) {
        profileForm.addEventListener('submit', function(event) {
            event.preventDefault();
            scheduleAutosave(true);
        });
        profileForm.addEventListener('input', function() { scheduleAutosave(false); });
        profileForm.addEventListener('change', function() { scheduleAutosave(true); });
        profileForm.addEventListener('focusout', function() { scheduleAutosave(true); });
    }

    window.addEventListener('beforeunload', function() {
        var displayName = (displayNameInput ? displayNameInput.value : '').trim();
        var companyName = (companyInput ? companyInput.value : '').trim();
        if (!displayName && !companyName) {
            if (displayNameInput) {
                displayNameInput.value = lastSavedProfile.displayName;
            }
            if (companyInput) {
                companyInput.value = lastSavedProfile.company;
            }
        }
        if (profileForm) {
            scheduleAutosave(true);
        }
    });

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
                fetch('?route=actions/delete-project', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ projectId, clientId }).toString()
                })
                .then(r => r.json())
                .then(j => {
                    if (!j || !j.success) {
                        throw new Error((j && j.message) || 'Delete failed');
                    }
                    window.location.href = j.redirect || ('?route=client&id=' + encodeURIComponent(clientId));
                })
                .catch(err => alert(err.message || String(err)));
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


