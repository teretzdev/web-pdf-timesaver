<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auto Field Detection Demo</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f6fa;
            color: #2c3e50;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: #007bff;
            color: white;
            padding: 20px;
            text-align: center;
        }
        
        .content {
            padding: 30px;
        }
        
        .upload-area {
            border: 2px dashed #007bff;
            border-radius: 8px;
            padding: 40px;
            text-align: center;
            margin-bottom: 30px;
            background: #f8f9fa;
            transition: all 0.3s ease;
        }
        
        .upload-area:hover {
            background: #e3f2fd;
            border-color: #0056b3;
        }
        
        .upload-area.dragover {
            background: #e3f2fd;
            border-color: #0056b3;
            transform: scale(1.02);
        }
        
        .upload-input {
            display: none;
        }
        
        .upload-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            margin: 10px;
        }
        
        .upload-btn:hover {
            background: #0056b3;
        }
        
        .demo-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            margin: 10px;
        }
        
        .demo-btn:hover {
            background: #218838;
        }
        
        .results {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 6px;
            display: none;
        }
        
        .field-item {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 15px;
            margin: 10px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .field-info {
            flex: 1;
        }
        
        .field-name {
            font-weight: bold;
            color: #007bff;
        }
        
        .field-type {
            color: #6c757d;
            font-size: 14px;
        }
        
        .field-coords {
            color: #495057;
            font-size: 12px;
            font-family: monospace;
        }
        
        .status {
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
            font-weight: bold;
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
        
        .loading {
            text-align: center;
            padding: 20px;
            color: #6c757d;
        }
        
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #007bff;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
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
            <h1>🚀 Auto Field Detection Demo</h1>
            <p>Upload any PDF form to automatically detect fillable fields and their positions</p>
        </div>
        
        <div class="content">
            <div class="upload-area" id="uploadArea">
                <h3>📄 Upload PDF Form</h3>
                <p>Drag and drop a PDF file here or click to browse</p>
                <input type="file" id="pdfFile" class="upload-input" accept=".pdf">
                <button class="upload-btn" onclick="document.getElementById('pdfFile').click()">
                    Choose PDF File
                </button>
                <button class="demo-btn" onclick="loadDemo()">
                    Load W-9 Demo
                </button>
            </div>
            
            <div id="status"></div>
            <div id="results" class="results"></div>
        </div>
    </div>

    <script>
        const uploadArea = document.getElementById('uploadArea');
        const pdfFile = document.getElementById('pdfFile');
        const status = document.getElementById('status');
        const results = document.getElementById('results');

        // Drag and drop functionality
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0 && files[0].type === 'application/pdf') {
                handleFile(files[0]);
            } else {
                showStatus('Please upload a valid PDF file', 'error');
            }
        });

        pdfFile.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                handleFile(e.target.files[0]);
            }
        });

        function handleFile(file) {
            showStatus('Processing PDF...', 'info');
            showLoading();
            
            const formData = new FormData();
            formData.append('pdf_file', file);
            formData.append('template_id', 'demo_' + Date.now());

            fetch('mvp/?route=actions/universal-process', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    showStatus(`✅ Success! Detected ${data.data.fields.length} fillable fields`, 'success');
                    displayResults(data.data);
                } else {
                    showStatus(`❌ Error: ${data.message}`, 'error');
                }
            })
            .catch(error => {
                hideLoading();
                showStatus(`❌ Error: ${error.message}`, 'error');
            });
        }

        function loadDemo() {
            showStatus('Loading W-9 demo...', 'info');
            showLoading();
            
            // Use the existing W-9 file
            fetch('mvp/?route=actions/universal-process', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'pdf_file=uploads/w9.pdf&template_id=demo_w9'
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    showStatus(`✅ Success! Detected ${data.data.fields.length} fillable fields`, 'success');
                    displayResults(data.data);
                } else {
                    showStatus(`❌ Error: ${data.message}`, 'error');
                }
            })
            .catch(error => {
                hideLoading();
                showStatus(`❌ Error: ${error.message}`, 'error');
            });
        }

        function displayResults(data) {
            results.style.display = 'block';
            results.innerHTML = `
                <h3>📋 Detected Fields</h3>
                <p><strong>Method:</strong> ${data.method}</p>
                <p><strong>Background Images:</strong> ${data.backgrounds ? data.backgrounds.length : 0} generated</p>
                <p><strong>Position File:</strong> ${data.position_file || 'Not saved'}</p>
                
                <div id="fieldsList"></div>
            `;
            
            const fieldsList = document.getElementById('fieldsList');
            if (data.fields && data.fields.length > 0) {
                data.fields.forEach(field => {
                    const fieldDiv = document.createElement('div');
                    fieldDiv.className = 'field-item';
                    fieldDiv.innerHTML = `
                        <div class="field-info">
                            <div class="field-name">${field.name || 'Unnamed Field'}</div>
                            <div class="field-type">Type: ${field.type || 'Unknown'}</div>
                            <div class="field-coords">Position: x=${field.x}, y=${field.y}, w=${field.width}, h=${field.height}</div>
                        </div>
                    `;
                    fieldsList.appendChild(fieldDiv);
                });
            } else {
                fieldsList.innerHTML = '<p>No fields detected</p>';
            }
        }

        function showStatus(message, type) {
            status.innerHTML = `<div class="status ${type}">${message}</div>`;
        }

        function showLoading() {
            status.innerHTML = `
                <div class="loading">
                    <div class="spinner"></div>
                    Processing PDF...
                </div>
            `;
        }

        function hideLoading() {
            // Loading will be replaced by status message
        }
    </script>
</body>
</html>
