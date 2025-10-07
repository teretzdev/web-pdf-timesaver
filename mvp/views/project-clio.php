<?php
// Enhanced project view matching Clio Draft interface
$client = null;
if (!empty($project['clientId']) && $store && method_exists($store, 'getClient')) {
    $client = $store->getClient($project['clientId']);
}
?>

<style>
.clio-interface {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

.clio-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 2px solid #e9ecef;
}

.clio-back-link {
    color: #0b6bcb;
    text-decoration: none;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
}

.clio-back-link:hover {
    text-decoration: underline;
}

.clio-action-buttons {
    display: flex;
    gap: 12px;
}

.clio-btn-primary {
    background: #0b6bcb;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
}

.clio-btn-primary:hover {
    background: #0a5a9f;
}

.clio-btn-secondary {
    background: #6c757d;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
}

.clio-btn-secondary:hover {
    background: #5a6268;
}

.clio-client-panel {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 24px;
    border-radius: 12px;
    margin-bottom: 24px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.clio-client-info h2 {
    margin: 0 0 16px 0;
    font-size: 28px;
    font-weight: 700;
}

.clio-client-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 16px;
}

.clio-client-detail-item {
    display: flex;
    align-items: center;
    gap: 8px;
}

.clio-document-list {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    margin-bottom: 24px;
}

.clio-document-item {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 12px;
    transition: all 0.3s ease;
    cursor: pointer;
}

.clio-document-item:hover {
    border-color: #0b6bcb;
    box-shadow: 0 4px 12px rgba(11,107,203,0.1);
    transform: translateY(-2px);
}

.clio-document-item.selected {
    border-color: #0b6bcb;
    border-width: 2px;
    background: #f0f8ff;
}

.clio-document-title {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 8px;
    font-size: 16px;
}

.clio-document-actions {
    display: flex;
    gap: 8px;
    margin-top: 12px;
}

.clio-vault-section {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.clio-upload-area {
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    padding: 40px;
    text-align: center;
    background: #f8f9fa;
    transition: all 0.3s ease;
}

.clio-upload-area:hover {
    border-color: #0b6bcb;
    background: #f0f8ff;
}

.clio-status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-in-progress {
    background: #fff3cd;
    color: #856404;
}

.status-ready-to-sign {
    background: #d1ecf1;
    color: #0c5460;
}

.status-signed {
    background: #d4edda;
    color: #155724;
}
</style>

<div class="clio-interface">
    <!-- Header with Back Button and Actions -->
    <div class="clio-header">
        <div>
            <a href="?route=projects" class="clio-back-link">
                <span>←</span>
                <span>Back to Projects</span>
            </a>
        </div>
        <div class="clio-action-buttons">
            <button class="clio-btn-secondary" onclick="showAddDocumentModal()">Add Document</button>
            <?php if (!empty($client)): ?>
                <a href="?route=client&id=<?php echo htmlspecialchars($client['id']); ?>" class="clio-btn-secondary">View Client</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Client Information Panel (Similar to Clio) -->
    <?php if (!empty($client)): ?>
    <div class="clio-client-panel">
        <div class="clio-client-info">
            <h2><?php echo htmlspecialchars($client['displayName'] ?? 'Unknown Client'); ?></h2>
            <div class="clio-client-details">
                <?php if (!empty($client['phone'])): ?>
                <div class="clio-client-detail-item">
                    <span>📞</span>
                    <span><?php echo htmlspecialchars($client['phone']); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($client['email'])): ?>
                <div class="clio-client-detail-item">
                    <span>📧</span>
                    <span><?php echo htmlspecialchars($client['email']); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($client['address'])): ?>
                <div class="clio-client-detail-item">
                    <span>📍</span>
                    <span><?php echo htmlspecialchars($client['address']); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Documents Section (Similar to Clio Draft) -->
    <div class="clio-document-list">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; font-size: 20px; font-weight: 700;">Documents (<?php echo count($documents); ?>)</h3>
            <div>
                <button class="clio-btn-secondary" style="font-size: 14px; padding: 8px 16px;">Add/Remove</button>
            </div>
        </div>

        <div style="color: #6c757d; margin-bottom: 16px; padding: 12px; background: #f0f8ff; border-left: 4px solid #0b6bcb; border-radius: 4px;">
            💡 <strong>Tip:</strong> Download/print using the "Download" button. Use the "Sign" button to sign the documents or send them out to collect signatures electronically.
        </div>

        <?php if (empty($documents)): ?>
            <div style="text-align: center; padding: 40px; color: #6c757d;">
                <p style="font-size: 18px; margin-bottom: 12px;">No documents yet</p>
                <p>Click "Add Document" to get started</p>
            </div>
        <?php else: ?>
            <?php foreach ($documents as $d): ?>
                <?php $tpl = $templates[$d['templateId']] ?? ['code' => $d['templateId'], 'name' => '']; ?>
                <div class="clio-document-item" data-document-id="<?php echo htmlspecialchars($d['id']); ?>">
                    <div class="clio-document-title">
                        <?php echo htmlspecialchars(($tpl['code'] ?? '') . ' — ' . ($tpl['name'] ?? '')); ?>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span class="clio-status-badge status-<?php echo str_replace('_', '-', $d['status'] ?? 'in-progress'); ?>">
                            <?php echo str_replace('_', ' ', $d['status'] ?? 'in_progress'); ?>
                        </span>
                        <div style="color: #6c757d; font-size: 12px;">
                            Created: <?php echo date('M j, Y', strtotime($d['createdAt'] ?? 'now')); ?>
                        </div>
                    </div>
                    <div class="clio-document-actions">
                        <a href="?route=populate&pd=<?php echo htmlspecialchars($d['id']); ?>" class="clio-btn-secondary" style="padding: 6px 16px; font-size: 13px;">
                            ✏️ Edit Fields
                        </a>
                        <a href="?route=pdf-preview&pd=<?php echo htmlspecialchars($d['id']); ?>" class="clio-btn-secondary" style="padding: 6px 16px; font-size: 13px;">
                            🔧 Map Fields
                        </a>
                        <?php if (!empty($d['outputPath'])): ?>
                            <a href="?route=actions/download&pd=<?php echo htmlspecialchars($d['id']); ?>" class="clio-btn-primary" style="padding: 6px 16px; font-size: 13px;">
                                ⬇️ Download
                            </a>
                        <?php else: ?>
                            <a href="?route=actions/generate&pd=<?php echo htmlspecialchars($d['id']); ?>" class="clio-btn-primary" style="padding: 6px 16px; font-size: 13px;">
                                📄 Generate PDF
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($d['outputPath']) && empty($d['signedPath'])): ?>
                            <a href="?route=actions/sign&pd=<?php echo htmlspecialchars($d['id']); ?>" class="clio-btn-secondary" style="padding: 6px 16px; font-size: 13px;">
                                ✍️ Sign
                            </a>
                        <?php elseif (!empty($d['signedPath'])): ?>
                            <a href="?route=actions/download-signed&pd=<?php echo htmlspecialchars($d['id']); ?>" class="clio-btn-primary" style="padding: 6px 16px; font-size: 13px;">
                                ⬇️ Download Signed
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Client Vault Section (Similar to Clio) -->
    <div class="clio-vault-section">
        <h3 style="margin: 0 0 16px 0; font-size: 18px; font-weight: 700;">Client Vault</h3>
        <?php if (!empty($client)): ?>
            <p style="color: #6c757d; margin-bottom: 16px;">
                Upload files for <?php echo htmlspecialchars($client['displayName']); ?>
            </p>
        <?php endif; ?>
        
        <div class="clio-upload-area">
            <div style="font-size: 48px; margin-bottom: 16px;">📁</div>
            <p style="font-size: 16px; font-weight: 600; margin-bottom: 8px;">Drop files here to upload</p>
            <p style="color: #6c757d; font-size: 14px; margin-bottom: 16px;">or</p>
            <button class="clio-btn-primary">Browse Files</button>
            <p style="color: #6c757d; font-size: 12px; margin-top: 12px;">
                Supported formats: PDF, DOC, DOCX, JPG, PNG
            </p>
        </div>
    </div>
</div>

<script>
function showAddDocumentModal() {
    const modal = document.createElement('div');
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
    `;
    
    modal.innerHTML = `
        <div style="background: white; padding: 32px; border-radius: 12px; width: 500px; max-width: 90vw;">
            <h2 style="margin: 0 0 24px 0; font-size: 24px; color: #2c3e50;">Add Document</h2>
            <form method="POST" action="?route=actions/add-document">
                <input type="hidden" name="projectId" value="<?php echo htmlspecialchars($project['id']); ?>">
                
                <div style="margin-bottom: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2c3e50;">Select Template:</label>
                    <select name="templateId" required style="width: 100%; padding: 12px; border: 1px solid #dee2e6; border-radius: 6px; font-size: 14px;">
                        <?php foreach ($templates as $tpl): ?>
                            <option value="<?php echo htmlspecialchars($tpl['id']); ?>">
                                <?php echo htmlspecialchars($tpl['code'] . ' — ' . $tpl['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div style="display: flex; gap: 12px; justify-content: flex-end;">
                    <button type="button" class="clio-btn-secondary" onclick="this.closest('div[style*=fixed]').remove()">Cancel</button>
                    <button type="submit" class="clio-btn-primary">Add Document</button>
                </div>
            </form>
        </div>
    `;
    
    document.body.appendChild(modal);
}

// Document selection
document.addEventListener('DOMContentLoaded', function() {
    const documents = document.querySelectorAll('.clio-document-item');
    documents.forEach(doc => {
        doc.addEventListener('click', function(e) {
            // Don't select if clicking on action buttons
            if (e.target.closest('a, button')) return;
            
            // Toggle selection
            documents.forEach(d => d.classList.remove('selected'));
            this.classList.add('selected');
        });
    });
});
</script>







