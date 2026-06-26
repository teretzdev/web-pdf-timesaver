        </div>
    </div>

    <script>
        // Mobile menu toggle functionality
        (function() {
            const menuToggle = document.getElementById('mobile-menu-toggle');
            const sidebar = document.getElementById('pdftimesaver-sidebar');
            const overlay = document.getElementById('mobile-overlay');
            
            if (menuToggle && sidebar && overlay) {
                // Toggle menu
                function toggleMenu() {
                    sidebar.classList.toggle('open');
                    overlay.classList.toggle('active');
                    
                    // Update aria-expanded for accessibility
                    const isOpen = sidebar.classList.contains('open');
                    menuToggle.setAttribute('aria-expanded', isOpen);
                }
                
                // Open/close menu
                menuToggle.addEventListener('click', toggleMenu);
                
                // Close menu when clicking overlay
                overlay.addEventListener('click', toggleMenu);
                
                // Close menu when clicking a nav link (for better UX)
                const navLinks = sidebar.querySelectorAll('.pdftimesaver-sidebar-nav a');
                navLinks.forEach(link => {
                    link.addEventListener('click', function() {
                        if (sidebar.classList.contains('open')) {
                            toggleMenu();
                        }
                    });
                });
                
                // Close menu on escape key
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && sidebar.classList.contains('open')) {
                        toggleMenu();
                    }
                });
            }
        })();
    </script>

    <?php if (isset($_GET['route']) && $_GET['route'] === 'drafting'): ?>
    <script>
        // Drafting Interface JavaScript
        const CLIENT_ID = '<?= htmlspecialchars($clientId ?? '') ?>';
        const PROJECT_ID = '<?= htmlspecialchars($projectId ?? '') ?>';
        const TEMPLATE_ID = '<?= htmlspecialchars($projectDocument['templateId'] ?? '') ?>';
        const BASE_PATH = '<?= htmlspecialchars(function_exists('getBasePath') ? getBasePath() : '/') ?>';

        // Custom field creation state
        let isAddingField = false;
        let pendingFieldConfig = null;
        let fieldPreview = null;

        // Insert Field Modal
        document.getElementById('insert-field-btn').addEventListener('click', function() {
            document.getElementById('insert-field-modal').classList.add('active');
        });

        function closeInsertModal() {
            document.getElementById('insert-field-modal').classList.remove('active');
        }

        function addCustomField(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            const fieldData = {
                label: formData.get('label'),
                type: formData.get('type')
            };
            
            // Store pending field config and enter positioning mode
            pendingFieldConfig = fieldData;
            isAddingField = true;
            
            // Close modal and show positioning instructions
            closeInsertModal();
            
            // Show positioning instructions
            showPositioningInstructions();
            
            // Add click handler to PDF container
            const pdfContainer = document.getElementById('pdf-container');
            pdfContainer.style.cursor = 'crosshair';
            pdfContainer.addEventListener('click', handleFieldPositioning);
            
            e.target.reset();
        }

        function showPositioningInstructions() {
            // Create instruction overlay
            const instructions = document.createElement('div');
            instructions.id = 'positioning-instructions';
            instructions.style.cssText = `
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                background: rgba(0, 0, 0, 0.8);
                color: white;
                padding: 20px;
                border-radius: 8px;
                z-index: 1000;
                text-align: center;
                max-width: 400px;
            `;
            instructions.innerHTML = `
                <h3 style="margin: 0 0 10px 0;">Position Custom Field</h3>
                <p style="margin: 0 0 15px 0;">Click on the PDF where you want to place the "${pendingFieldConfig.label}" field</p>
                <button onclick="cancelFieldPositioning()" style="background: #dc3545; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">Cancel</button>
            `;
            document.body.appendChild(instructions);
        }

        function handleFieldPositioning(e) {
            if (!isAddingField || !pendingFieldConfig) return;
            
            // Get click position relative to PDF container
            const rect = e.currentTarget.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            
            // Default field dimensions based on type
            const dimensions = getFieldDimensions(pendingFieldConfig.type);
            
            // Create field preview
            createFieldPreview(x, y, dimensions.width, dimensions.height);
            
            // Confirm field placement
            showFieldConfirmation(x, y, dimensions.width, dimensions.height);
        }

        function getFieldDimensions(type) {
            const dimensions = {
                text: { width: 200, height: 25 },
                textarea: { width: 300, height: 60 },
                checkbox: { width: 20, height: 20 },
                date: { width: 150, height: 25 },
                number: { width: 100, height: 25 }
            };
            return dimensions[type] || dimensions.text;
        }

        function createFieldPreview(x, y, width, height) {
            // Remove existing preview
            if (fieldPreview) {
                fieldPreview.remove();
            }
            
            // Create preview element
            fieldPreview = document.createElement('div');
            fieldPreview.style.cssText = `
                position: absolute;
                left: ${x}px;
                top: ${y}px;
                width: ${width}px;
                height: ${height}px;
                border: 2px dashed #007bff;
                background: rgba(0, 123, 255, 0.1);
                pointer-events: none;
                z-index: 100;
            `;
            
            document.getElementById('pdf-container').appendChild(fieldPreview);
        }

        function showFieldConfirmation(x, y, width, height) {
            const instructions = document.getElementById('positioning-instructions');
            if (instructions) {
                instructions.innerHTML = `
                    <h3 style="margin: 0 0 10px 0;">Confirm Field Position</h3>
                    <p style="margin: 0 0 15px 0;">Field: "${pendingFieldConfig.label}" (${pendingFieldConfig.type})</p>
                    <p style="margin: 0 0 15px 0;">Position: ${x}, ${y} | Size: ${width} × ${height}</p>
                    <div style="display: flex; gap: 10px; justify-content: center;">
                        <button onclick="confirmFieldPosition(${x}, ${y}, ${width}, ${height})" style="background: #28a745; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">Place Field</button>
                        <button onclick="cancelFieldPositioning()" style="background: #dc3545; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">Cancel</button>
                    </div>
                `;
            }
        }

        async function confirmFieldPosition(x, y, width, height) {
            if (!pendingFieldConfig) return;
            
            try {
                const formData = new FormData();
                formData.append('templateId', TEMPLATE_ID);
                formData.append('label', pendingFieldConfig.label);
                formData.append('type', pendingFieldConfig.type);
                formData.append('x', x);
                formData.append('y', y);
                formData.append('width', width);
                formData.append('height', height);
                
                const response = await fetch('?route=actions/add-custom-field', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    console.log('Custom field added:', data.field);
                    // Reload the page to show the new field
                    window.location.reload();
                } else {
                    alert('Failed to add custom field: ' + (data.error || 'Unknown error'));
                    cancelFieldPositioning();
                }
            } catch (error) {
                console.error('Failed to add custom field:', error);
                alert('Failed to add custom field');
                cancelFieldPositioning();
            }
        }

        function cancelFieldPositioning() {
            isAddingField = false;
            pendingFieldConfig = null;
            
            // Remove preview
            if (fieldPreview) {
                fieldPreview.remove();
                fieldPreview = null;
            }
            
            // Remove instructions
            const instructions = document.getElementById('positioning-instructions');
            if (instructions) {
                instructions.remove();
            }
            
            // Remove click handler and reset cursor
            const pdfContainer = document.getElementById('pdf-container');
            pdfContainer.style.cursor = '';
            pdfContainer.removeEventListener('click', handleFieldPositioning);
        }

        function updateFieldValue(fieldKey, value) {
            // Update the field value in the form data
            const formData = new FormData(document.getElementById('drafting-form'));
            formData.set(fieldKey, value);
            
            // Save the form data
            saveForm();
        }

        // Load client files
        async function loadClientFiles() {
            if (!CLIENT_ID) return;
            
            try {
                const response = await fetch(`?route=actions/list-client-files&clientId=${CLIENT_ID}&projectId=${PROJECT_ID}`);
                const data = await response.json();
                
                if (data.success && data.files) {
                    const fileList = document.getElementById('file-list');
                    fileList.innerHTML = '';
                    
                    if (data.files.length === 0) {
                        fileList.innerHTML = '<li class="file-item" style="color: #6c757d; text-align: center; padding: 20px;">No client files</li>';
                        return;
                    }
                    
                    data.files.forEach(file => {
                        const li = document.createElement('li');
                        li.className = 'file-item';
                        
                        const sizeInKB = (file.size / 1024).toFixed(1);
                        const basePath = typeof BASE_PATH !== 'undefined' ? BASE_PATH : '/';
                        const url = basePath + 'uploads/' + file.filename;
                        
                        li.innerHTML = `
                            <div style="flex: 1;">
                                <a href="${url}" target="_blank" style="color: #1976d2; text-decoration: none; font-weight: 500;">${file.originalName}</a>
                                <div style="font-size: 11px; color: #6c757d; margin-top: 4px;">${sizeInKB} KB</div>
                            </div>
                            <button class="file-delete-btn" onclick="deleteFile('${file.id}')" aria-label="Delete file">&times;</button>
                        `;
                        
                        fileList.appendChild(li);
                    });
                }
            } catch (error) {
                console.error('Failed to load files:', error);
                const fileList = document.getElementById('file-list');
                fileList.innerHTML = '<li class="file-item" style="color: #dc3545; text-align: center; padding: 20px;">Error loading files</li>';
            }
        }

        async function deleteFile(fileId) {
            if (!confirm('Remove this file from the client vault?')) return;
            
            try {
                const formData = new FormData();
                formData.append('fileId', fileId);
                
                const response = await fetch('?route=actions/delete-client-file', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    loadClientFiles();
                } else {
                    alert('Failed to delete file: ' + (data.error || 'Unknown error'));
                }
            } catch (error) {
                console.error('Failed to delete file:', error);
                alert('Failed to delete file');
            }
        }

        // Load files on page load
        if (CLIENT_ID) {
            loadClientFiles();
        }

        // Status Dropdown Update
        document.getElementById('project-status-select').addEventListener('change', function() {
            const projectId = this.getAttribute('data-project-id');
            const status = this.value;
            
            const formData = new FormData();
            formData.append('id', projectId);
            formData.append('status', status);
            
            fetch('?route=actions/update-project-status', {
                method: 'POST',
                body: formData
            }).then(() => {
                // Visual feedback
                const btn = this;
                const originalVal = btn.value;
                btn.value = status;
                btn.style.backgroundColor = '#28a745';
                
                setTimeout(() => {
                    btn.style.backgroundColor = '';
                }, 1000);
            }).catch(err => {
                console.error('Failed to update status:', err);
                alert('Failed to update status');
            });
        });

        // Download Button — serve filled PDF (same pipeline as populate Export → This Form)
        document.getElementById('download-btn').addEventListener('click', function() {
            window.location.href = '?route=actions/export-project-forms&projectId=<?= urlencode((string)($projectId ?? '')) ?>&pd=<?= $pdId ?>&scope=this&format=pdf';
        });

        // Sign Button (disabled until signing workflow is available)
        (function() {
            const signBtn = document.getElementById('sign-btn');
            if (signBtn) {
                signBtn.setAttribute('disabled', 'disabled');
                signBtn.title = 'Digital signing will be available soon';
                signBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                });
            }
        })();

        // File Upload Handling
        const uploadZone = document.getElementById('file-upload-zone');
        const fileInput = document.getElementById('file-input');
        const browseLink = document.getElementById('browse-link');

        browseLink.addEventListener('click', (e) => {
            e.preventDefault();
            fileInput.click();
        });

        uploadZone.addEventListener('click', () => fileInput.click());

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadZone.addEventListener(eventName, preventDefaults, false);
        });

        uploadZone.addEventListener('dragenter', () => uploadZone.classList.add('dragover'));
        uploadZone.addEventListener('dragleave', () => uploadZone.classList.remove('dragover'));
        uploadZone.addEventListener('drop', handleDrop);

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        function handleDrop(e) {
            uploadZone.classList.remove('dragover');
            const files = e.dataTransfer.files;
            handleFiles(files);
        }

        fileInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                handleFiles(this.files);
            }
        });

        async function handleFiles(files) {
            if (!CLIENT_ID || files.length === 0) return;
            
            for (const file of files) {
                const formData = new FormData();
                formData.append('file', file);
                formData.append('clientId', CLIENT_ID);
                formData.append('projectId', PROJECT_ID);
                
                try {
                    const response = await fetch('?route=actions/upload-client-file', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        console.log('File uploaded:', data.file);
                        loadClientFiles();
                    } else {
                        alert('Failed to upload file: ' + file.name);
                    }
                } catch (error) {
                    console.error('Upload error:', error);
                    alert('Failed to upload file: ' + file.name);
                }
            }
        }

        // Close modal when clicking outside
        document.getElementById('insert-field-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeInsertModal();
            }
        });

                // Escape key to close modal
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        closeInsertModal();
                    }
                });

                // Document List Functionality
                async function loadProjectDocuments() {
                    try {
                        const response = await fetch(`?route=actions/get-project-documents&projectId=${PROJECT_ID}`);
                        const data = await response.json();
                        
                        if (data.success && data.documents) {
                            const documentList = document.getElementById('document-list');
                            documentList.innerHTML = '';
                            
                            data.documents.forEach(doc => {
                                const isCurrentDoc = doc.id === '<?= $pdId ?>';
                                const li = document.createElement('li');
                                li.className = `document-item ${isCurrentDoc ? 'active' : ''}`;
                                li.setAttribute('data-document-id', doc.id);
                                
                                li.innerHTML = `
                                    <div class="document-info">
                                        <div class="document-name">${doc.templateName}</div>
                                        <div class="document-status">
                                            <span class="status-indicator status-${doc.status.replace('_', '-')}">
                                                ${doc.status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}
                                            </span>
                                        </div>
                                    </div>
                                    ${!isCurrentDoc ? `
                                        <div class="document-actions">
                                            <a href="?route=drafting&pd=${doc.id}" class="action-link">Edit</a>
                                        </div>
                                    ` : ''}
                                `;
                                
                                documentList.appendChild(li);
                            });
                        }
                    } catch (error) {
                        console.error('Failed to load project documents:', error);
                    }
                }

                // Update document status
                async function updateDocumentStatus(documentId, status) {
                    try {
                        const formData = new FormData();
                        formData.append('documentId', documentId);
                        formData.append('status', status);
                        
                        const response = await fetch('?route=actions/update-document-status', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            console.log('Document status updated:', status);
                            loadProjectDocuments(); // Reload the document list
                        } else {
                            alert('Failed to update document status: ' + (data.error || 'Unknown error'));
                        }
                    } catch (error) {
                        console.error('Failed to update document status:', error);
                        alert('Failed to update document status');
                    }
                }

                // Load documents on page load
                if (PROJECT_ID) {
                    loadProjectDocuments();
                }
            </script>
            <?php endif; ?>
</body>
</html>