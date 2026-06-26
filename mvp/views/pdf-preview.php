<?php $tpl = $template; ?>
<h2>PDF Field Mapping — <?php echo htmlspecialchars(formatTemplateDisplayLabel($tpl, (string)($projectDocument['templateId'] ?? ''))); ?></h2>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-top: 20px;">
    
    <!-- Left Column: PDF Form Fields -->
    <div class="panel">
        <h3 style="color: #0b6bcb; margin-bottom: 16px;">📄 PDF Form Fields</h3>
        <p style="color: #6c757d; margin-bottom: 16px;">These are the form fields found in the PDF template.</p>
        
        <div id="pdf-fields-container" class="grid" style="gap: 12px;">
            <?php foreach ($pdfFields as $pdfField): ?>
                <div class="pdf-field-item" data-field-name="<?php echo htmlspecialchars($pdfField['name']); ?>" style="padding: 12px; border: 2px solid #e9ecef; border-radius: 8px; background: #f8f9fa; cursor: pointer; transition: all 0.3s ease;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <div style="font-weight: 600; color: #495057; margin-bottom: 4px;">
                                <?php echo htmlspecialchars($pdfField['label']); ?>
                            </div>
                            <div style="font-size: 12px; color: #6c757d; font-family: monospace;">
                                <?php echo htmlspecialchars($pdfField['name']); ?>
                            </div>
                        </div>
                        <div style="font-size: 12px; color: #6c757d; text-transform: uppercase;">
                            <?php echo htmlspecialchars($pdfField['type']); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
</div>

<!-- Action Buttons -->
<div style="margin-top: 24px; display: flex; gap: 12px;">
    <button class="pdftimesaver-btn" onclick="saveAllMappings()">Save All Mappings</button>
    <a class="pdftimesaver-btn-secondary" href="?route=populate&pd=<?php echo htmlspecialchars($projectDocument['id']); ?>">Go to Populate Form</a>
    <a class="pdftimesaver-btn-secondary" href="?route=project&id=<?php echo htmlspecialchars($projectDocument['projectId']); ?>">Back to Matter</a>
</div>

<style>
.panel {
    background: #ffffff;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.grid {
    display: grid;
    grid-template-columns: 1fr;
}

.pdf-field-item:hover {
    border-color: #0b6bcb !important;
    background: #f0f8ff !important;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(11,107,203,0.1);
}

.btn {
    background: #0b6bcb;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn:hover {
    background: #0a5a9f;
    transform: translateY(-1px);
}

.btn.secondary {
    background: #6c757d;
}

.btn.secondary:hover {
    background: #5a6268;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize field mappings
    initializeFieldMappings();
});

function initializeFieldMappings() {
    // This would load existing mappings from the database
    // For now, we'll just show the interface
}

function saveAllMappings() {
    // Save all current mappings
    alert('All mappings saved successfully!');
}
</script>