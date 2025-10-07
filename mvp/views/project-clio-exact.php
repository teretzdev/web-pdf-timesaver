<?php
// Exact clone of Clio Draft interface at https://draft.clio.com/panels/edit/
$client = null;
if (!empty($project['clientId']) && $store && method_exists($store, 'getClient')) {
    $client = $store->getClient($project['clientId']);
}
?>

<style>
/* Exact Clio Draft styling */
body {
    background: #f5f5f5;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
}

.clio-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.clio-top-actions {
    display: flex;
    gap: 12px;
    margin-bottom: 16px;
}

.clio-action-btn {
    background: #fff;
    border: 1px solid #ddd;
    padding: 8px 16px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 13px;
    text-decoration: none;
    color: #333;
}

.clio-action-btn:hover {
    background: #f8f8f8;
}

.clio-info-box {
    background: #e8f4f8;
    border-left: 4px solid #5bc0de;
    padding: 12px 16px;
    margin-bottom: 20px;
    font-size: 13px;
    color: #31708f;
}

.clio-client-section {
    background: white;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
}

.clio-client-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 16px;
}

.clio-client-name {
    font-size: 18px;
    font-weight: 600;
    color: #333;
    margin: 0 0 8px 0;
}

.clio-client-details {
    font-size: 13px;
    color: #666;
    line-height: 1.6;
}

.clio-vault-label {
    font-size: 14px;
    font-weight: 600;
    color: #666;
    margin-bottom: 12px;
}

.clio-upload-zone {
    border: 2px dashed #ccc;
    border-radius: 4px;
    padding: 40px 20px;
    text-align: center;
    background: #fafafa;
    color: #999;
    margin-bottom: 20px;
}

.clio-documents-section {
    background: white;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 20px;
}

.clio-documents-header {
    font-size: 14px;
    font-weight: 600;
    color: #666;
    margin-bottom: 16px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.clio-doc-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.clio-doc-item {
    padding: 12px 0;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.clio-doc-item:last-child {
    border-bottom: none;
}

.clio-doc-name {
    font-size: 13px;
    color: #337ab7;
    cursor: pointer;
}

.clio-doc-name:hover {
    text-decoration: underline;
}

.clio-btn {
    background: #5cb85c;
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 3px;
    font-size: 12px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
}

.clio-btn:hover {
    background: #4cae4c;
}

.clio-btn-default {
    background: #fff;
    color: #333;
    border: 1px solid #ccc;
}

.clio-btn-default:hover {
    background: #f8f8f8;
}

.clio-btn-link {
    background: none;
    border: none;
    color: #337ab7;
    padding: 0;
    font-size: 13px;
    cursor: pointer;
}

.clio-btn-link:hover {
    text-decoration: underline;
}
</style>

<div class="clio-container">
    <!-- Top Action Buttons (matching Clio) -->
    <div class="clio-top-actions">
        <button class="clio-action-btn clio-btn-default">Insert</button>
        <button class="clio-action-btn clio-btn-default">Add custom field</button>
        <a href="?route=populate&pd=<?php echo htmlspecialchars($documents[0]['id'] ?? ''); ?>" class="clio-action-btn clio-btn-link">← Back to populate</a>
    </div>

    <!-- Info Box (matching Clio) -->
    <div class="clio-info-box">
        Download/print using the "Download" button. Use the "Sign" button to sign the documents or send them out to collect signatures electronically.
    </div>

    <!-- Client Section (matching Clio) -->
    <div class="clio-client-section">
        <div class="clio-client-header">
            <div>
                <div style="display: flex; gap: 12px; align-items: center; margin-bottom: 16px;">
                    <button class="clio-btn">Sign</button>
                    <button class="clio-btn-default clio-action-btn">Client vault</button>
                    <button class="clio-btn-link">close</button>
                </div>
                
                <?php if (!empty($client)): ?>
                <h2 class="clio-client-name"><?php echo htmlspecialchars(strtoupper($client['displayName'] ?? 'CLIENT')); ?></h2>
                <div class="clio-client-details">
                    <?php if (!empty($client['phone'])): ?>
                    <div><?php echo htmlspecialchars($client['phone']); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($client['email'])): ?>
                    <div><?php echo htmlspecialchars(strtoupper($client['email'])); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($client['address'])): ?>
                    <div><?php echo htmlspecialchars(strtoupper($client['address'])); ?></div>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <h2 class="clio-client-name">PROJECT: <?php echo htmlspecialchars(strtoupper($project['name'])); ?></h2>
                <div class="clio-client-details">
                    <div>No client assigned</div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Vault Upload Area -->
        <div style="margin-top: 20px;">
            <div class="clio-vault-label">Client vault</div>
            <div class="clio-upload-zone">
                <div style="margin-bottom: 12px; color: #999;">No client files</div>
                <div style="font-size: 12px;">
                    To upload files drag them here, or <button class="clio-btn-link">Browse</button>
                </div>
            </div>
        </div>

        <!-- Documents List (matching Clio) -->
        <div style="margin-top: 24px;">
            <div class="clio-documents-header">
                <span>Documents (<?php echo count($documents); ?>)</span>
                <button class="clio-btn-link">Add/Remove</button>
            </div>

            <ul class="clio-doc-list">
                <?php if (empty($documents)): ?>
                    <li style="padding: 20px; text-align: center; color: #999;">
                        No documents added yet
                    </li>
                <?php else: ?>
                    <?php foreach ($documents as $d): ?>
                        <?php $tpl = $templates[$d['templateId']] ?? ['code' => $d['templateId'], 'name' => '']; ?>
                        <li class="clio-doc-item">
                            <div>
                                <a href="?route=populate&pd=<?php echo htmlspecialchars($d['id']); ?>" class="clio-doc-name">
                                    <?php echo htmlspecialchars(($tpl['code'] ?? '') . ' ' . ($tpl['name'] ?? '')); ?>
                                </a>
                            </div>
                            <div style="display: flex; gap: 8px; align-items: center;">
                                <?php if (!empty($d['outputPath'])): ?>
                                    <a href="?route=actions/download&pd=<?php echo htmlspecialchars($d['id']); ?>" class="clio-btn" style="background: #337ab7;">Download</a>
                                <?php endif; ?>
                                <?php if (!empty($d['outputPath']) && empty($d['signedPath'])): ?>
                                    <a href="?route=actions/sign&pd=<?php echo htmlspecialchars($d['id']); ?>" class="clio-btn">Sign</a>
                                <?php endif; ?>
                                <?php if (empty($d['outputPath'])): ?>
                                    <a href="?route=actions/generate&pd=<?php echo htmlspecialchars($d['id']); ?>" class="clio-btn">Generate</a>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>








