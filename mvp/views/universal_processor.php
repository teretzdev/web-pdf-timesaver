<?php require 'layout_header.php'; ?>

<style>
    .processor-container {
        max-width: 1000px;
        margin: 0 auto;
    }
    
    .hero-section {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 40px;
        border-radius: 12px;
        margin-bottom: 30px;
        text-align: center;
    }
    
    .hero-section h1 {
        margin: 0 0 10px 0;
        font-size: 32px;
    }
    
    .hero-section p {
        margin: 0;
        font-size: 18px;
        opacity: 0.9;
    }
    
    .upload-section {
        background: white;
        padding: 40px;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }
    
    .upload-area {
        border: 3px dashed #667eea;
        border-radius: 12px;
        padding: 60px 40px;
        text-align: center;
        background: #f8f9fa;
        transition: all 0.3s;
        cursor: pointer;
        margin: 20px 0;
    }
    
    .upload-area:hover {
        border-color: #764ba2;
        background: #e7f3ff;
    }
    
    .upload-area.dragover {
        background: #d4edda;
        border-color: #28a745;
        transform: scale(1.02);
    }
    
    .upload-area.file-selected {
        border-color: #28a745;
        background: #d4edda;
    }
    
    .upload-icon {
        font-size: 64px;
        margin-bottom: 20px;
    }
    
    .file-input {
        display: none;
    }
    
    .template-id-input {
        width: 100%;
        padding: 12px;
        border: 2px solid #e1e4e8;
        border-radius: 6px;
        font-size: 14px;
        margin-bottom: 20px;
    }
    
    .submit-btn {
        width: 100%;
        padding: 16px;
        background: #667eea;
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 18px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .submit-btn:hover:not(:disabled) {
        background: #5568d3;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }
    
    .submit-btn:disabled {
        background: #ccc;
        cursor: not-allowed;
    }
    
    .results-section {
        background: white;
        padding: 40px;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        display: none;
    }
    
    .results-section.success {
        border-left: 5px solid #28a745;
    }
    
    .results-section.error {
        border-left: 5px solid #dc3545;
    }
    
    .loading {
        text-align: center;
        padding: 40px;
    }
    
    .spinner {
        border: 4px solid #f3f3f3;
        border-top: 4px solid #667eea;
        border-radius: 50%;
        width: 50px;
        height: 50px;
        animation: spin 1s linear infinite;
        margin: 0 auto 20px;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .field-table {
        width: 100%;
        border-collapse: collapse;
        margin: 20px 0;
        font-size: 13px;
    }
    
    .field-table th {
        background: #667eea;
        color: white;
        padding: 12px;
        text-align: left;
        font-weight: 600;
    }
    
    .field-table td {
        padding: 10px 12px;
        border-bottom: 1px solid #e1e4e8;
    }
    
    .field-table tr:hover {
        background: #f8f9fa;
    }
    
    .badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
    }
    
    .badge-success {
        background: #d4edda;
        color: #155724;
    }
    
    .badge-warning {
        background: #fff3cd;
        color: #856404;
    }
    
    .badge-info {
        background: #d1ecf1;
        color: #0c5460;
    }
    
    .stat-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin: 20px 0;
    }
    
    .stat-card {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        text-align: center;
    }
    
    .stat-value {
        font-size: 32px;
        font-weight: 700;
        color: #667eea;
        margin: 10px 0;
    }
    
    .stat-label {
        font-size: 14px;
        color: #666;
    }
    
    .json-output {
        background: #f4f4f4;
        padding: 15px;
        border-radius: 6px;
        overflow-x: auto;
        font-size: 12px;
        font-family: 'Courier New', monospace;
        margin-top: 20px;
    }
    
    .feature-list {
        list-style: none;
        padding: 0;
        margin: 20px 0;
    }
    
    .feature-list li {
        padding: 10px 0 10px 30px;
        position: relative;
    }
    
    .feature-list li:before {
        content: "✓";
        position: absolute;
        left: 0;
        color: #28a745;
        font-weight: bold;
        font-size: 18px;
    }
</style>

<div class="processor-container">
    <div class="hero-section">
        <div class="upload-icon">🤖</div>
        <h1>Universal PDF Form Processor</h1>
        <p>Auto-detect fillable fields from ANY PDF form - supports hundreds of different forms</p>
    </div>
    
    <div class="upload-section">
        <h2>How It Works</h2>
        <ul class="feature-list">
            <li><strong>Upload any PDF form</strong> - Court forms, legal documents, business forms, etc.</li>
            <li><strong>Auto-detection</strong> - Automatically finds fillable form fields and their exact positions</li>
            <li><strong>Coordinate extraction</strong> - Extracts X, Y, width, height, and field types</li>
            <li><strong>Background generation</strong> - Creates high-quality background images for overlay</li>
            <li><strong>Smart fallback</strong> - Works with encrypted PDFs using manual positioning</li>
            <li><strong>Scalable</strong> - Process hundreds of different forms without manual configuration</li>
        </ul>
        
        <form id="processorForm" enctype="multipart/form-data">
            <input 
                type="text" 
                name="template_id" 
                class="template-id-input" 
                placeholder="Template ID (optional - auto-generated if empty)" 
            />
            
            <div class="upload-area" id="uploadArea">
                <div class="upload-icon">📄</div>
                <h3 style="margin-bottom: 10px;">Drop PDF here or click to upload</h3>
                <p style="color: #666; margin: 0;">Any court form, legal document, or fillable PDF</p>
                <p id="fileName" style="margin-top: 15px; color: #28a745; font-weight: 600;"></p>
            </div>
            
            <input 
                type="file" 
                id="pdfFile" 
                name="pdf_file" 
                accept=".pdf" 
                required 
                class="file-input"
            />
            
            <button type="submit" class="submit-btn" id="submitBtn">
                🔍 Analyze & Extract Fields
            </button>
        </form>
    </div>
    
    <div class="results-section" id="results"></div>
</div>

<script>
const uploadArea = document.getElementById('uploadArea');
const pdfFile = document.getElementById('pdfFile');
const fileName = document.getElementById('fileName');
const form = document.getElementById('processorForm');
const results = document.getElementById('results');
const submitBtn = document.getElementById('submitBtn');

// Click to upload
uploadArea.addEventListener('click', () => pdfFile.click());

// Drag and drop
['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    uploadArea.addEventListener(eventName, preventDefaults, false);
});

function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
}

['dragenter', 'dragover'].forEach(eventName => {
    uploadArea.addEventListener(eventName, () => uploadArea.classList.add('dragover'), false);
});

['dragleave', 'drop'].forEach(eventName => {
    uploadArea.addEventListener(eventName, () => uploadArea.classList.remove('dragover'), false);
});

uploadArea.addEventListener('drop', function(e) {
    const dt = e.dataTransfer;
    const files = dt.files;
    pdfFile.files = files;
    handleFileSelect(files[0]);
});

pdfFile.addEventListener('change', function(e) {
    if (e.target.files[0]) {
        handleFileSelect(e.target.files[0]);
    }
});

function handleFileSelect(file) {
    if (file) {
        fileName.textContent = '✅ ' + file.name;
        uploadArea.classList.add('file-selected');
    }
}

form.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    submitBtn.disabled = true;
    submitBtn.textContent = '⏳ Processing...';
    
    results.style.display = 'block';
    results.className = 'results-section';
    results.innerHTML = `
        <div class="loading">
            <div class="spinner"></div>
            <p>Analyzing PDF and extracting field positions...</p>
        </div>
    `;
    
    const formData = new FormData(form);
    
    try {
        const response = await fetch('?route=actions/universal-process', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            results.className = 'results-section success';
            
            let html = `
                <h2>✅ ${data.message}</h2>
                <p><strong>Method:</strong> <span class="badge ${data.data.method === 'autofill' ? 'badge-success' : 'badge-warning'}">${data.data.method.toUpperCase()}</span></p>
            `;
            
            // Stats
            if (data.data.fields) {
                const fieldCount = Object.keys(data.data.fields).length;
                html += `
                    <div class="stat-grid">
                        <div class="stat-card">
                            <div class="stat-label">Fields Detected</div>
                            <div class="stat-value">${data.data.field_count || fieldCount}</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-label">Background Pages</div>
                            <div class="stat-value">${data.data.backgrounds || 0}</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-label">Template ID</div>
                            <div class="stat-value" style="font-size: 18px;">${data.data.template_id}</div>
                        </div>
                    </div>
                `;
                
                // Field types
                if (data.data.field_types) {
                    html += '<h3>Field Types:</h3><p>';
                    for (const [type, count] of Object.entries(data.data.field_types)) {
                        html += `<span class="badge badge-info">${type}: ${count}</span> `;
                    }
                    html += '</p>';
                }
                
                // Field table (first 20)
                const fieldsArray = Object.entries(data.data.fields).slice(0, 20);
                html += `
                    <h3>Extracted Fields (showing first ${fieldsArray.length}):</h3>
                    <table class="field-table">
                        <thead>
                            <tr>
                                <th>Field Name</th>
                                <th>Type</th>
                                <th>Page</th>
                                <th>Position (mm)</th>
                                <th>Size (mm)</th>
                            </tr>
                        </thead>
                        <tbody>
                `;
                
                fieldsArray.forEach(([name, field]) => {
                    html += `
                        <tr>
                            <td><strong>${name}</strong></td>
                            <td><span class="badge badge-success">${field.type}</span></td>
                            <td>${field.page}</td>
                            <td>X: ${field.x.toFixed(1)}, Y: ${field.y.toFixed(1)}</td>
                            <td>W: ${field.width.toFixed(1)}, H: ${field.height.toFixed(1)}</td>
                        </tr>
                    `;
                });
                
                html += '</tbody></table>';
                
                if (Object.keys(data.data.fields).length > 20) {
                    html += `<p style="text-align:center;color:#666;">... and ${Object.keys(data.data.fields).length - 20} more fields</p>`;
                }
            }
            
            // Position file location
            if (data.data.position_file) {
                html += `<p style="margin-top: 20px;"><strong>📄 Position file saved:</strong> <code>${data.data.position_file}</code></p>`;
            }
            
            // Next steps
            html += `
                <h3 style="margin-top: 30px;">Next Steps:</h3>
                <ul class="feature-list">
                    <li>Use the extracted positions to automatically fill forms</li>
                    <li>Map your data fields to the PDF field names</li>
                    <li>Generate filled PDFs using the position data</li>
                    <li>Fine-tune positions using the Visual Field Editor</li>
                </ul>
            `;
            
            // Full JSON output
            html += `
                <h3 style="margin-top: 30px;">Full Response Data:</h3>
                <div class="json-output">${JSON.stringify(data, null, 2)}</div>
            `;
            
            results.innerHTML = html;
        } else {
            throw new Error(data.message || 'Unknown error');
        }
    } catch (error) {
        results.className = 'results-section error';
        results.innerHTML = `
            <h2>❌ Error</h2>
            <p>${error.message}</p>
            <p style="margin-top: 20px;">This could mean:</p>
            <ul>
                <li>The PDF is corrupted or invalid</li>
                <li>The PDF format is not supported</li>
                <li>Server configuration issue (check PHP extensions)</li>
            </ul>
        `;
    } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = '🔍 Analyze & Extract Fields';
    }
});
</script>

<?php require 'layout_footer.php'; ?>


