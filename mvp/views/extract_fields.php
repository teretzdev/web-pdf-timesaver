<div class="pdftimesaver-card">
    <h2 style="margin: 0 0 20px 0; color: #2c3e50; font-size: 24px; font-weight: 700;">
        PDF Field Extractor
    </h2>
    <p style="color: #6c757d; margin-bottom: 24px;">
        Upload a fillable PDF to automatically extract form field positions. This will generate a position file
        that can be used for automatic form filling.
    </p>
</div>

<?php if (isset($error)): ?>
    <div class="pdftimesaver-card" style="background: #f8d7da; color: #721c24; margin-bottom: 16px;">
        ❌ <?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<?php if (isset($success)): ?>
    <div class="pdftimesaver-card" style="background: #d4edda; color: #155724; margin-bottom: 16px;">
        ✅ <?php echo htmlspecialchars($success); ?>
    </div>
<?php endif; ?>

<?php if (isset($fields) && !empty($fields)): ?>
    <div class="pdftimesaver-card">
        <h3 style="margin: 0 0 16px 0; color: #2c3e50; font-size: 18px;">
            Extracted <?php echo count($fields); ?> Form Fields
        </h3>
        
        <div class="table-responsive">
            <table class="pdftimesaver-table">
                <thead>
                    <tr>
                        <th>Field Name</th>
                        <th>Type</th>
                        <th>Page</th>
                        <th>Position (X, Y)</th>
                        <th>Size (W × H)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($fields as $fieldName => $fieldData): ?>
                        <tr>
                            <td style="font-family: 'Courier New', monospace; font-size: 13px;">
                                <?php echo htmlspecialchars($fieldName); ?>
                            </td>
                            <td>
                                <span class="pdftimesaver-status pdftimesaver-status-active">
                                    <?php echo htmlspecialchars($fieldData['type']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars((string)$fieldData['page']); ?></td>
                            <td>
                                <?php echo sprintf('(%.1f, %.1f)', $fieldData['x'], $fieldData['y']); ?>
                            </td>
                            <td>
                                <?php echo sprintf('%.1f × %.1f', $fieldData['width'], $fieldData['height']); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if (isset($positionFile)): ?>
            <div style="margin-top: 20px; padding: 16px; background: #e7f3ff; border-left: 4px solid #007bff;">
                <strong>✅ Position file generated:</strong><br>
                <code style="font-size: 13px; color: #333;"><?php echo htmlspecialchars($positionFile); ?></code>
            </div>
        <?php endif; ?>
        
        <?php if (isset($backgrounds) && !empty($backgrounds)): ?>
            <div style="margin-top: 16px; padding: 16px; background: #d4edda; border-left: 4px solid #28a745;">
                <strong>✅ Background images generated:</strong><br>
                <?php foreach ($backgrounds as $page => $bgFile): ?>
                    <div style="margin-top: 8px;">
                        <code style="font-size: 13px; color: #333;">Page <?php echo $page; ?>: <?php echo htmlspecialchars(basename($bgFile)); ?></code>
                    </div>
                <?php endforeach; ?>
                <div style="margin-top: 12px; padding: 12px; background: #fff; border-radius: 4px;">
                    <small style="color: #666;">
                        These background images will be used as the PDF template. Your form fields will be overlaid on top using the extracted positions.
                    </small>
                </div>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="pdftimesaver-card">
    <form method="post" action="?route=actions/extract-pdf-fields" enctype="multipart/form-data" id="extract-form">
        <div class="pdftimesaver-form-group">
            <label class="pdftimesaver-form-label">Select or Upload PDF</label>
            <div style="margin-bottom: 12px;">
                <label style="display: block; margin-bottom: 8px; font-weight: 600;">Option 1: Select Existing PDF</label>
                <select id="existing_pdf" class="pdftimesaver-input" style="width: 100%;">
                    <option value="">-- Select an existing PDF --</option>
                </select>
                <small style="color: #6c757d; display: block; margin-top: 4px;">
                    Choose a PDF that's already been uploaded
                </small>
            </div>
            <div style="margin-top: 16px; margin-bottom: 12px; text-align: center; color: #6c757d;">
                <strong>OR</strong>
            </div>
            <div>
                <label style="display: block; margin-bottom: 8px; font-weight: 600;">Option 2: Upload New PDF</label>
                <input type="file" id="pdf_file" name="pdf_file" accept=".pdf" class="pdftimesaver-input">
                <small style="color: #6c757d; display: block; margin-top: 4px;">
                    Upload a new PDF file. Works with fillable forms (AcroForm) or static PDFs (for manual positioning).
                </small>
            </div>
        </div>
        
        <div class="pdftimesaver-form-group">
            <label class="pdftimesaver-form-label" for="template_id">Template ID</label>
            <input type="text" id="template_id" name="template_id" 
                   placeholder="e.g., t_fl100_gc120" required class="pdftimesaver-input"
                   pattern="[a-z0-9_]+" title="Only lowercase letters, numbers, and underscores">
            <small style="color: #6c757d; display: block; margin-top: 4px;">
                A unique identifier for this template (e.g., t_fl100_gc120, t_fl105_gc120)
            </small>
        </div>
        
        <div class="button-group" style="display: flex; gap: 12px; margin-top: 24px;">
            <button type="submit" class="pdftimesaver-btn">
                Extract Fields
            </button>
            <a href="?route=dashboard" class="pdftimesaver-btn-secondary">
                Cancel
            </a>
        </div>
    </form>
</div>

<div class="pdftimesaver-card">
    <h3 style="margin: 0 0 16px 0; color: #2c3e50; font-size: 18px;">How It Works</h3>
    <ol style="color: #6c757d; line-height: 1.8; padding-left: 24px;">
        <li>Upload a fillable PDF that contains form fields (created in Adobe Acrobat or similar)</li>
        <li>The system will automatically detect all form field names and their positions</li>
        <li>A position file will be generated in the <code>data/</code> directory</li>
        <li>You can then use this template for automatic form filling with perfect field placement</li>
    </ol>
    
    <div style="margin-top: 16px; padding: 12px; background: #fff3cd; border-left: 4px solid #ffc107;">
        <strong>Note:</strong> If the PDF is password-protected, you'll need to remove the password first.
        You can use online tools or pdftk to unlock the PDF.
    </div>
</div>

<script>
// Universal PDF selector - works for any PDF
(function() {
    const form = document.getElementById('extract-form');
    const existingSelect = document.getElementById('existing_pdf');
    const fileInput = document.getElementById('pdf_file');
    
    // Load existing PDFs
    fetch('?route=actions/list-pdfs')
        .then(response => response.json())
        .then(data => {
            if (data.pdfs && data.pdfs.length > 0) {
                data.pdfs.forEach(pdf => {
                    const option = document.createElement('option');
                    option.value = pdf.path;
                    option.textContent = pdf.name;
                    existingSelect.appendChild(option);
                });
                
                // Auto-select fl100.pdf if template ID is t_fl100_gc120
                const templateInput = document.getElementById('template_id');
                function autoSelectFl100() {
                    if (templateInput && templateInput.value === 't_fl100_gc120') {
                        const fl100Option = Array.from(existingSelect.options).find(opt => opt.textContent === 'fl100.pdf');
                        if (fl100Option && existingSelect.value !== fl100Option.value) {
                            existingSelect.value = fl100Option.value;
                            existingSelect.dispatchEvent(new Event('change'));
                        }
                    }
                }
                // Check immediately
                autoSelectFl100();
                // Also listen for changes to template ID
                if (templateInput) {
                    templateInput.addEventListener('input', autoSelectFl100);
                    templateInput.addEventListener('change', autoSelectFl100);
                }
            }
        })
        .catch(err => console.error('Error loading PDFs:', err));
    
    // Handle existing PDF selection
    existingSelect.addEventListener('change', function() {
        if (this.value) {
            fileInput.removeAttribute('required');
            // Create hidden input with selected PDF path
            let hiddenInput = document.getElementById('selected_pdf_path');
            if (!hiddenInput) {
                hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.id = 'selected_pdf_path';
                hiddenInput.name = 'selected_pdf_path';
                form.appendChild(hiddenInput);
            }
            hiddenInput.value = this.value;
            console.log('Selected PDF:', this.value);
        } else {
            fileInput.setAttribute('required', 'required');
            const hiddenInput = document.getElementById('selected_pdf_path');
            if (hiddenInput) hiddenInput.remove();
        }
    });
    
    // Also trigger on input event for better compatibility
    existingSelect.addEventListener('input', function() {
        this.dispatchEvent(new Event('change'));
    });
    
    // Handle file upload
    fileInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            existingSelect.value = '';
            const hiddenInput = document.getElementById('selected_pdf_path');
            if (hiddenInput) hiddenInput.remove();
        }
    });
    
    // Form validation
    form.addEventListener('submit', function(e) {
        const hasFile = fileInput.files.length > 0;
        const hasSelected = existingSelect.value !== '';
        
        if (!hasFile && !hasSelected) {
            e.preventDefault();
            alert('Please either select an existing PDF or upload a new one.');
            return false;
        }
    });
})();
</script>

