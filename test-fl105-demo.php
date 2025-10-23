<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FL-105 Form Filling Demo</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 20px;
            background: #f5f6fa;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 30px;
            text-align: center;
        }
        .section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            margin: 10px 5px;
        }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #218838; }
        .btn-info { background: #17a2b8; }
        .btn-info:hover { background: #138496; }
        .status {
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
        }
        .status.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .status.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .status.info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .field-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .field-item {
            background: white;
            padding: 12px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
        }
        .field-label {
            font-weight: bold;
            color: #495057;
            margin-bottom: 5px;
        }
        .field-value {
            color: #212529;
        }
        .preview-container {
            margin: 20px 0;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            background: white;
        }
        canvas {
            max-width: 100%;
            border: 1px solid #ccc;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .loading {
            text-align: center;
            padding: 40px;
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #007bff;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📋 FL-105/GC-120 Form Filling Demo</h1>
            <p>Declaration Under Uniform Child Custody Jurisdiction and Enforcement Act (UCCJEA)</p>
        </div>

        <div class="section">
            <h2>🚀 Quick Actions</h2>
            <button class="btn btn-success" onclick="autoFillForm()">Auto-Fill with Test Data</button>
            <button class="btn btn-info" onclick="extractFields()">Extract Form Fields</button>
            <button class="btn" onclick="generatePDF()">Generate Filled PDF</button>
            <button class="btn" onclick="viewVisualEditor()">Open Visual Editor</button>
        </div>

        <div id="status"></div>
        
        <div id="testData" class="section" style="display: none;">
            <h3>📝 Test Data</h3>
            <div class="field-grid" id="testDataGrid"></div>
        </div>

        <div id="extractedFields" class="section" style="display: none;">
            <h3>🔍 Extracted Fields</h3>
            <div id="fieldsInfo"></div>
            <div class="field-grid" id="fieldsGrid"></div>
        </div>

        <div id="preview" class="preview-container" style="display: none;">
            <h3>👁️ PDF Preview</h3>
            <canvas id="pdfCanvas"></canvas>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script>
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

        let testData = {};
        let extractedData = {};

        function showStatus(message, type = 'info') {
            const statusDiv = document.getElementById('status');
            statusDiv.innerHTML = `<div class="status ${type}">${message}</div>`;
        }

        function showLoading(message = 'Processing...') {
            const statusDiv = document.getElementById('status');
            statusDiv.innerHTML = `
                <div class="loading">
                    <div class="spinner"></div>
                    <p>${message}</p>
                </div>
            `;
        }

        async function autoFillForm() {
            showLoading('Loading test data...');
            
            try {
                // Load FL-105 test data
                const response = await fetch('mvp/lib/fl105_test_data_generator.php');
                const text = await response.text();
                
                // Since it's a PHP class, we need to load it via a helper
                const dataResponse = await fetch('get-fl105-test-data.php');
                const data = await dataResponse.json();
                
                testData = data;
                
                // Display test data
                const grid = document.getElementById('testDataGrid');
                grid.innerHTML = '';
                
                Object.entries(testData).forEach(([key, value]) => {
                    const item = document.createElement('div');
                    item.className = 'field-item';
                    item.innerHTML = `
                        <div class="field-label">${key.replace(/_/g, ' ').toUpperCase()}</div>
                        <div class="field-value">${value}</div>
                    `;
                    grid.appendChild(item);
                });
                
                document.getElementById('testData').style.display = 'block';
                showStatus(`✅ Loaded ${Object.keys(testData).length} test data fields`, 'success');
                
            } catch (error) {
                showStatus(`❌ Error loading test data: ${error.message}`, 'error');
            }
        }

        async function extractFields() {
            showLoading('Extracting form fields from FL-105...');
            
            try {
                const formData = new FormData();
                formData.append('template_id', 'fl105');
                formData.append('pdf_file', 'uploads/fl105.pdf');
                
                const response = await fetch('mvp/?route=actions/universal-process', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    extractedData = result.data;
                    
                    document.getElementById('fieldsInfo').innerHTML = `
                        <div class="status info">
                            <strong>Extraction Method:</strong> ${result.data.method}<br>
                            <strong>Fields Detected:</strong> ${result.data.fields ? result.data.fields.length : 0}<br>
                            <strong>Background Images:</strong> ${result.data.backgrounds ? result.data.backgrounds.length : 0}
                        </div>
                    `;
                    
                    const grid = document.getElementById('fieldsGrid');
                    grid.innerHTML = '';
                    
                    if (result.data.fields && result.data.fields.length > 0) {
                        result.data.fields.forEach(field => {
                            const item = document.createElement('div');
                            item.className = 'field-item';
                            item.innerHTML = `
                                <div class="field-label">${field.name || 'Unnamed'}</div>
                                <div class="field-value">
                                    Type: ${field.type || 'unknown'}<br>
                                    Position: (${field.x}, ${field.y})<br>
                                    Size: ${field.width} × ${field.height}
                                </div>
                            `;
                            grid.appendChild(item);
                        });
                    } else {
                        grid.innerHTML = '<p>No fields extracted. This may be a password-protected PDF or image-based form.</p>';
                    }
                    
                    document.getElementById('extractedFields').style.display = 'block';
                    showStatus('✅ Field extraction complete', 'success');
                    
                } else {
                    showStatus(`❌ Extraction failed: ${result.message}`, 'error');
                }
                
            } catch (error) {
                showStatus(`❌ Error: ${error.message}`, 'error');
            }
        }

        async function generatePDF() {
            if (Object.keys(testData).length === 0) {
                showStatus('⚠️ Please load test data first (click "Auto-Fill with Test Data")', 'error');
                return;
            }
            
            showLoading('Generating filled PDF...');
            
            try {
                const formData = new FormData();
                formData.append('template_id', 'fl105');
                formData.append('action', 'fill');
                formData.append('data', JSON.stringify(testData));
                
                const response = await fetch('fill-fl105-form.php', {
                    method: 'POST',
                    body: formData
                });
                
                if (response.ok) {
                    const blob = await response.blob();
                    const url = URL.createObjectURL(blob);
                    
                    // Create download link
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'FL-105_filled.pdf';
                    a.click();
                    
                    // Preview the PDF
                    await previewPDF(url);
                    
                    showStatus('✅ PDF generated and downloaded', 'success');
                } else {
                    showStatus('❌ Failed to generate PDF', 'error');
                }
                
            } catch (error) {
                showStatus(`❌ Error: ${error.message}`, 'error');
            }
        }

        async function previewPDF(url) {
            const canvas = document.getElementById('pdfCanvas');
            const ctx = canvas.getContext('2d');
            
            try {
                const loadingTask = pdfjsLib.getDocument(url);
                const pdf = await loadingTask.promise;
                const page = await pdf.getPage(1);
                
                const viewport = page.getViewport({ scale: 1.5 });
                canvas.width = viewport.width;
                canvas.height = viewport.height;
                
                await page.render({
                    canvasContext: ctx,
                    viewport: viewport
                }).promise;
                
                document.getElementById('preview').style.display = 'block';
                
            } catch (error) {
                console.error('Preview error:', error);
            }
        }

        function viewVisualEditor() {
            window.open('mvp/visual-field-editor.php?template=fl105', '_blank');
        }

        // Auto-load test data on page load
        window.addEventListener('load', () => {
            showStatus('ℹ️ Click "Auto-Fill with Test Data" to begin', 'info');
        });
    </script>
</body>
</html>
