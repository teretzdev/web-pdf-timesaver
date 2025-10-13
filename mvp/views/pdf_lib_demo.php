<?php require 'layout_header.php'; ?>

<style>
    .demo-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .demo-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-top: 20px;
    }
    
    .demo-panel {
        background: white;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    
    .demo-panel h3 {
        margin: 0 0 15px 0;
        color: #667eea;
    }
    
    .upload-zone {
        border: 3px dashed #667eea;
        border-radius: 8px;
        padding: 40px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
        margin-bottom: 20px;
    }
    
    .upload-zone:hover {
        background: #f0f4ff;
        border-color: #5568d3;
    }
    
    .upload-zone.active {
        background: #d4edda;
        border-color: #28a745;
    }
    
    .btn-primary {
        background: #667eea;
        color: white;
        padding: 12px 24px;
        border: none;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .btn-primary:hover {
        background: #5568d3;
        transform: translateY(-2px);
    }
    
    .btn-primary:disabled {
        background: #ccc;
        cursor: not-allowed;
        transform: none;
    }
    
    #pdfCanvas {
        width: 100%;
        border: 1px solid #ddd;
        border-radius: 8px;
        display: none;
    }
    
    .field-list {
        max-height: 400px;
        overflow-y: auto;
        border: 1px solid #e1e4e8;
        border-radius: 6px;
        padding: 10px;
    }
    
    .field-item {
        padding: 8px;
        margin: 5px 0;
        background: #f8f9fa;
        border-radius: 4px;
        font-size: 13px;
    }
    
    .field-item strong {
        color: #667eea;
    }
    
    .status {
        padding: 12px;
        border-radius: 6px;
        margin: 10px 0;
        display: none;
    }
    
    .status.success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    .status.error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    
    .status.info {
        background: #d1ecf1;
        color: #0c5460;
        border: 1px solid #bee5eb;
    }
</style>

<div class="demo-container">
    <h1>🚀 PDF-Lib + PDF.js Demo</h1>
    <p style="color: #666; margin-bottom: 30px;">Upload a fillable PDF to automatically detect fields and fill them</p>
    
    <div class="demo-grid">
        <!-- Left Panel: Upload & Detection -->
        <div class="demo-panel">
            <h3>1. Upload & Detect Fields</h3>
            
            <div class="upload-zone" id="uploadZone">
                <div style="font-size: 48px; margin-bottom: 10px;">📄</div>
                <p><strong>Drop PDF here or click to upload</strong></p>
                <p style="font-size: 12px; color: #666;">Any fillable PDF form</p>
            </div>
            
            <input type="file" id="pdfInput" accept=".pdf" style="display: none;" />
            
            <div id="statusMessage" class="status"></div>
            
            <button id="analyzeBtn" class="btn-primary" disabled>
                🔍 Analyze PDF Fields
            </button>
            
            <div id="fieldsList" class="field-list" style="margin-top: 20px; display: none;"></div>
        </div>
        
        <!-- Right Panel: Preview & Fill -->
        <div class="demo-panel">
            <h3>2. Preview & Auto-Fill</h3>
            
            <canvas id="pdfCanvas"></canvas>
            
            <div id="fillControls" style="display: none; margin-top: 20px;">
                <button id="fillBtn" class="btn-primary">
                    ✍️ Auto-Fill with Test Data
                </button>
                
                <button id="downloadBtn" class="btn-primary" style="margin-left: 10px; display: none;">
                    📥 Download Filled PDF
                </button>
            </div>
            
            <div id="fillStatus" class="status"></div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script type="module">
    import { PDFDocument } from 'https://cdn.jsdelivr.net/npm/pdf-lib@1.17.1/+esm';
    
    let currentPdfBytes = null;
    let currentPdfDoc = null;
    let detectedFields = [];
    let filledPdfBytes = null;
    
    const uploadZone = document.getElementById('uploadZone');
    const pdfInput = document.getElementById('pdfInput');
    const analyzeBtn = document.getElementById('analyzeBtn');
    const fillBtn = document.getElementById('fillBtn');
    const downloadBtn = document.getElementById('downloadBtn');
    const canvas = document.getElementById('pdfCanvas');
    const statusMessage = document.getElementById('statusMessage');
    const fillStatus = document.getElementById('fillStatus');
    const fieldsList = document.getElementById('fieldsList');
    const fillControls = document.getElementById('fillControls');
    
    // Setup PDF.js
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
    
    // Upload zone handlers
    uploadZone.addEventListener('click', () => pdfInput.click());
    
    uploadZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadZone.classList.add('active');
    });
    
    uploadZone.addEventListener('dragleave', () => {
        uploadZone.classList.remove('active');
    });
    
    uploadZone.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadZone.classList.remove('active');
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            handleFile(files[0]);
        }
    });
    
    pdfInput.addEventListener('change', (e) => {
        if (e.target.files.length > 0) {
            handleFile(e.target.files[0]);
        }
    });
    
    async function handleFile(file) {
        if (!file.type.includes('pdf')) {
            showStatus(statusMessage, 'error', 'Please upload a PDF file');
            return;
        }
        
        showStatus(statusMessage, 'info', `Loading ${file.name}...`);
        
        try {
            const arrayBuffer = await file.arrayBuffer();
            currentPdfBytes = new Uint8Array(arrayBuffer);
            
            // Render first page with PDF.js
            await renderPdfPreview(arrayBuffer);
            
            analyzeBtn.disabled = false;
            showStatus(statusMessage, 'success', `✅ PDF loaded: ${file.name} (${(file.size / 1024).toFixed(1)} KB)`);
            
        } catch (error) {
            showStatus(statusMessage, 'error', `❌ Error loading PDF: ${error.message}`);
        }
    }
    
    async function renderPdfPreview(arrayBuffer) {
        const pdf = await pdfjsLib.getDocument({ data: arrayBuffer }).promise;
        const page = await pdf.getPage(1);
        
        const viewport = page.getViewport({ scale: 1.5 });
        const context = canvas.getContext('2d');
        canvas.height = viewport.height;
        canvas.width = viewport.width;
        canvas.style.display = 'block';
        
        await page.render({
            canvasContext: context,
            viewport: viewport
        }).promise;
    }
    
    analyzeBtn.addEventListener('click', async () => {
        analyzeBtn.disabled = true;
        analyzeBtn.textContent = '⏳ Analyzing...';
        showStatus(statusMessage, 'info', 'Detecting form fields...');
        
        try {
            currentPdfDoc = await PDFDocument.load(currentPdfBytes);
            const form = currentPdfDoc.getForm();
            const fields = form.getFields();
            
            detectedFields = [];
            
            fields.forEach(field => {
                const fieldType = field.constructor.name;
                const fieldName = field.getName();
                
                detectedFields.push({
                    name: fieldName,
                    type: fieldType,
                    field: field
                });
            });
            
            if (detectedFields.length > 0) {
                showStatus(statusMessage, 'success', `✅ Found ${detectedFields.length} fillable fields!`);
                displayFields();
                fillControls.style.display = 'block';
            } else {
                showStatus(statusMessage, 'error', '⚠️ No fillable fields detected (blank form)');
            }
            
        } catch (error) {
            showStatus(statusMessage, 'error', `❌ Analysis error: ${error.message}`);
        } finally {
            analyzeBtn.disabled = false;
            analyzeBtn.textContent = '🔍 Analyze PDF Fields';
        }
    });
    
    function displayFields() {
        fieldsList.style.display = 'block';
        fieldsList.innerHTML = '<h4 style="margin: 0 0 10px 0;">Detected Fields:</h4>';
        
        detectedFields.forEach((field, index) => {
            const fieldItem = document.createElement('div');
            fieldItem.className = 'field-item';
            fieldItem.innerHTML = `
                <strong>[${index + 1}]</strong> ${field.name}
                <br><small>Type: ${field.type}</small>
            `;
            fieldsList.appendChild(fieldItem);
        });
    }
    
    fillBtn.addEventListener('click', async () => {
        fillBtn.disabled = true;
        fillBtn.textContent = '⏳ Filling...';
        showStatus(fillStatus, 'info', 'Auto-filling form with test data...');
        
        try {
            const form = currentPdfDoc.getForm();
            let filledCount = 0;
            
            detectedFields.forEach(field => {
                try {
                    const fieldType = field.type;
                    const fieldName = field.name;
                    
                    // Generate test data based on field name
                    let testValue = generateTestData(fieldName);
                    
                    if (fieldType === 'PDFTextField') {
                        field.field.setText(testValue);
                        filledCount++;
                    } else if (fieldType === 'PDFCheckBox') {
                        if (Math.random() > 0.5) {
                            field.field.check();
                            filledCount++;
                        }
                    } else if (fieldType === 'PDFDropdown') {
                        const options = field.field.getOptions();
                        if (options.length > 0) {
                            field.field.select(options[0]);
                            filledCount++;
                        }
                    }
                } catch (err) {
                    console.warn(`Could not fill field ${field.name}:`, err);
                }
            });
            
            // Save filled PDF
            filledPdfBytes = await currentPdfDoc.save();
            
            showStatus(fillStatus, 'success', `✅ Filled ${filledCount} fields successfully!`);
            downloadBtn.style.display = 'inline-block';
            
        } catch (error) {
            showStatus(fillStatus, 'error', `❌ Fill error: ${error.message}`);
        } finally {
            fillBtn.disabled = false;
            fillBtn.textContent = '✍️ Auto-Fill with Test Data';
        }
    });
    
    downloadBtn.addEventListener('click', () => {
        const blob = new Blob([filledPdfBytes], { type: 'application/pdf' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'filled-form-' + Date.now() + '.pdf';
        a.click();
        URL.revokeObjectURL(url);
        
        showStatus(fillStatus, 'success', '✅ PDF downloaded!');
    });
    
    function generateTestData(fieldName) {
        const name = fieldName.toLowerCase();
        
        if (name.includes('name') && name.includes('first')) return 'John';
        if (name.includes('name') && name.includes('last')) return 'Smith';
        if (name.includes('name')) return 'John Smith';
        if (name.includes('email')) return 'john.smith@example.com';
        if (name.includes('phone') || name.includes('tel')) return '(555) 123-4567';
        if (name.includes('address') && name.includes('street')) return '123 Main Street';
        if (name.includes('address')) return '123 Main St, Los Angeles, CA 90210';
        if (name.includes('city')) return 'Los Angeles';
        if (name.includes('state')) return 'CA';
        if (name.includes('zip')) return '90210';
        if (name.includes('date')) return new Date().toLocaleDateString();
        if (name.includes('ssn') || name.includes('social')) return '***-**-1234';
        if (name.includes('ein') || name.includes('tax')) return '**-*******';
        
        return `Test ${fieldName}`;
    }
    
    function showStatus(element, type, message) {
        element.className = `status ${type}`;
        element.textContent = message;
        element.style.display = 'block';
    }
</script>

<?php require 'layout_footer.php'; ?>


