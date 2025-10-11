/**
 * Position Editor JavaScript Library
 * Handles PDF rendering, field positioning, and MCP integration
 */

class PositionEditor {
    constructor() {
        this.canvas = null;
        this.ctx = null;
        this.currentPdf = null;
        this.fieldPositions = {};
        this.selectedField = null;
        this.zoomLevel = 1;
        this.fieldMarkers = new Map();
        this.mcpClient = null;
    }

    /**
     * Initialize the position editor
     */
    async initialize(canvasId) {
        this.canvas = document.getElementById(canvasId);
        this.ctx = this.canvas.getContext('2d');
        
        // Load field positions
        await this.loadFieldPositions();
        
        // Set up event listeners
        this.setupEventListeners();
        
        // Initialize MCP client
        this.initializeMCPClient();
        
        console.log('Position editor initialized');
    }

    /**
     * Load field positions from JSON file
     */
    async loadFieldPositions() {
        try {
            const response = await fetch('data/t_fl100_gc120_positions.json');
            if (response.ok) {
                this.fieldPositions = await response.json();
                console.log('Field positions loaded:', Object.keys(this.fieldPositions).length);
            } else {
                console.error('Failed to load field positions');
            }
        } catch (error) {
            console.error('Error loading field positions:', error);
        }
    }

    /**
     * Set up event listeners
     */
    setupEventListeners() {
        // Canvas click handler
        this.canvas.addEventListener('click', (event) => {
            this.handleCanvasClick(event);
        });

        // Canvas mouse move handler for hover effects
        this.canvas.addEventListener('mousemove', (event) => {
            this.handleCanvasHover(event);
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', (event) => {
            this.handleKeyboard(event);
        });
    }

    /**
     * Initialize MCP client for PDF analysis
     */
    initializeMCPClient() {
        // This would connect to the MCP server
        // For now, we'll use a mock implementation
        this.mcpClient = {
            async readPdf(filePath) {
                console.log('MCP: Reading PDF', filePath);
                return { success: true, text: 'Mock PDF content' };
            },
            
            async comparePdfs(ourPdf, referencePdf) {
                console.log('MCP: Comparing PDFs');
                return { success: true, differences: [] };
            },
            
            async analyzePositions(pdfPath, expectedPositions) {
                console.log('MCP: Analyzing positions');
                return { success: true, adjustments: [] };
            }
        };
    }

    /**
     * Load PDF file
     */
    async loadPdf(filePath) {
        try {
            // In a real implementation, this would use PDF.js or similar
            // For now, we'll create a mock PDF
            this.renderMockPdf();
            
            // Create field markers
            this.createFieldMarkers();
            
            console.log('PDF loaded:', filePath);
            return true;
        } catch (error) {
            console.error('Error loading PDF:', error);
            return false;
        }
    }

    /**
     * Render mock PDF (placeholder implementation)
     */
    renderMockPdf() {
        const canvas = this.canvas;
        const ctx = this.ctx;
        
        // Set canvas size
        canvas.width = 800;
        canvas.height = 1000;
        
        // Draw background
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        
        // Draw form content
        ctx.fillStyle = '#000000';
        ctx.font = '16px Arial';
        ctx.fillText('FL-100 Petition Form', 50, 50);
        
        // Draw form sections
        this.drawFormSection('Attorney Information', 50, 100);
        this.drawFormSection('Case Information', 50, 200);
        this.drawFormSection('Party Information', 50, 300);
        this.drawFormSection('Marriage Information', 50, 400);
        this.drawFormSection('Relief Requested', 50, 500);
        this.drawFormSection('Signature', 50, 600);
    }

    /**
     * Draw a form section
     */
    drawFormSection(title, x, y) {
        const ctx = this.ctx;
        
        // Section title
        ctx.font = '14px Arial';
        ctx.fillStyle = '#2c3e50';
        ctx.fillText(title, x, y);
        
        // Underline
        ctx.strokeStyle = '#2c3e50';
        ctx.lineWidth = 1;
        ctx.beginPath();
        ctx.moveTo(x, y + 5);
        ctx.lineTo(x + ctx.measureText(title).width, y + 5);
        ctx.stroke();
        
        // Form fields
        ctx.font = '12px Arial';
        ctx.fillStyle = '#666666';
        ctx.fillText('Field 1: _______________', x + 20, y + 25);
        ctx.fillText('Field 2: _______________', x + 20, y + 45);
        ctx.fillText('Field 3: _______________', x + 20, y + 65);
    }

    /**
     * Create field markers on the canvas
     */
    createFieldMarkers() {
        // Clear existing markers
        this.fieldMarkers.clear();
        
        // Create markers for each field
        Object.entries(this.fieldPositions).forEach(([fieldName, position]) => {
            const marker = this.createFieldMarker(fieldName, position);
            this.fieldMarkers.set(fieldName, marker);
        });
    }

    /**
     * Create a single field marker
     */
    createFieldMarker(fieldName, position) {
        const marker = {
            fieldName,
            position,
            element: null,
            isSelected: false,
            isHovered: false
        };
        
        // In a real implementation, this would create DOM elements
        // For now, we'll just store the marker data
        return marker;
    }

    /**
     * Handle canvas click
     */
    handleCanvasClick(event) {
        const rect = this.canvas.getBoundingClientRect();
        const x = (event.clientX - rect.left) / this.zoomLevel;
        const y = (event.clientY - rect.top) / this.zoomLevel;
        
        // Convert pixels to millimeters
        const mmX = (x / this.canvas.width) * 215.9; // A4 width in mm
        const mmY = (y / this.canvas.height) * 279.4; // A4 height in mm
        
        // Find closest field marker
        const closestField = this.findClosestField(mmX, mmY);
        
        if (closestField) {
            this.selectField(closestField);
        } else if (this.selectedField) {
            // Update selected field position
            this.updateFieldPosition(this.selectedField, mmX, mmY);
        }
    }

    /**
     * Handle canvas hover
     */
    handleCanvasHover(event) {
        const rect = this.canvas.getBoundingClientRect();
        const x = (event.clientX - rect.left) / this.zoomLevel;
        const y = (event.clientY - rect.top) / this.zoomLevel;
        
        const mmX = (x / this.canvas.width) * 215.9;
        const mmY = (y / this.canvas.height) * 279.4;
        
        const closestField = this.findClosestField(mmX, mmY);
        
        // Update hover state
        this.fieldMarkers.forEach((marker, fieldName) => {
            marker.isHovered = (fieldName === closestField);
        });
        
        this.renderFieldMarkers();
    }

    /**
     * Find closest field to given coordinates
     */
    findClosestField(x, y) {
        let closestField = null;
        let minDistance = Infinity;
        
        this.fieldMarkers.forEach((marker, fieldName) => {
            const distance = Math.sqrt(
                Math.pow(marker.position.x - x, 2) + 
                Math.pow(marker.position.y - y, 2)
            );
            
            if (distance < minDistance && distance < 10) { // 10mm threshold
                minDistance = distance;
                closestField = fieldName;
            }
        });
        
        return closestField;
    }

    /**
     * Select a field
     */
    selectField(fieldName) {
        // Deselect previous field
        if (this.selectedField) {
            const prevMarker = this.fieldMarkers.get(this.selectedField);
            if (prevMarker) {
                prevMarker.isSelected = false;
            }
        }
        
        // Select new field
        this.selectedField = fieldName;
        const marker = this.fieldMarkers.get(fieldName);
        if (marker) {
            marker.isSelected = true;
        }
        
        // Update UI
        this.updateFieldControls(fieldName);
        this.renderFieldMarkers();
        
        console.log('Selected field:', fieldName);
    }

    /**
     * Update field position
     */
    updateFieldPosition(fieldName, x, y) {
        const marker = this.fieldMarkers.get(fieldName);
        if (marker) {
            marker.position.x = x;
            marker.position.y = y;
            this.fieldPositions[fieldName].x = x;
            this.fieldPositions[fieldName].y = y;
            
            this.updateFieldControls(fieldName);
            this.renderFieldMarkers();
            
            console.log(`Updated ${fieldName} position: ${x.toFixed(1)}mm, ${y.toFixed(1)}mm`);
        }
    }

    /**
     * Update field controls in the UI
     */
    updateFieldControls(fieldName) {
        const position = this.fieldPositions[fieldName];
        
        // Update input fields
        const xInput = document.getElementById('x-position');
        const yInput = document.getElementById('y-position');
        const widthInput = document.getElementById('field-width');
        const fontSizeInput = document.getElementById('font-size');
        
        if (xInput) xInput.value = position.x.toFixed(1);
        if (yInput) yInput.value = position.y.toFixed(1);
        if (widthInput) widthInput.value = position.width || 0;
        if (fontSizeInput) fontSizeInput.value = position.fontSize || 9;
    }

    /**
     * Render field markers on canvas
     */
    renderFieldMarkers() {
        const ctx = this.ctx;
        
        this.fieldMarkers.forEach((marker, fieldName) => {
            const x = (marker.position.x / 215.9) * this.canvas.width * this.zoomLevel;
            const y = (marker.position.y / 279.4) * this.canvas.height * this.zoomLevel;
            
            // Set marker color based on state
            if (marker.isSelected) {
                ctx.fillStyle = '#3498db';
            } else if (marker.isHovered) {
                ctx.fillStyle = '#e74c3c';
            } else {
                ctx.fillStyle = '#95a5a6';
            }
            
            // Draw marker
            ctx.beginPath();
            ctx.arc(x, y, 4, 0, 2 * Math.PI);
            ctx.fill();
            
            // Draw border
            ctx.strokeStyle = '#ffffff';
            ctx.lineWidth = 2;
            ctx.stroke();
        });
    }

    /**
     * Handle keyboard shortcuts
     */
    handleKeyboard(event) {
        switch (event.key) {
            case 'Delete':
            case 'Backspace':
                if (this.selectedField) {
                    this.deleteField(this.selectedField);
                }
                break;
            case 'Escape':
                this.deselectField();
                break;
            case 's':
                if (event.ctrlKey) {
                    event.preventDefault();
                    this.savePositions();
                }
                break;
        }
    }

    /**
     * Delete a field
     */
    deleteField(fieldName) {
        if (confirm(`Delete field "${fieldName}"?`)) {
            delete this.fieldPositions[fieldName];
            this.fieldMarkers.delete(fieldName);
            this.selectedField = null;
            this.renderFieldMarkers();
            console.log('Deleted field:', fieldName);
        }
    }

    /**
     * Deselect current field
     */
    deselectField() {
        if (this.selectedField) {
            const marker = this.fieldMarkers.get(this.selectedField);
            if (marker) {
                marker.isSelected = false;
            }
            this.selectedField = null;
            this.renderFieldMarkers();
        }
    }

    /**
     * Save positions to server
     */
    async savePositions() {
        try {
            const response = await fetch('api/positions/update', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(this.fieldPositions)
            });
            
            if (response.ok) {
                console.log('Positions saved successfully');
                return true;
            } else {
                console.error('Failed to save positions');
                return false;
            }
        } catch (error) {
            console.error('Error saving positions:', error);
            return false;
        }
    }

    /**
     * Analyze positions using MCP
     */
    async analyzePositions() {
        try {
            const result = await this.mcpClient.analyzePositions(
                'output/test.pdf',
                this.fieldPositions
            );
            
            if (result.success) {
                console.log('Position analysis complete:', result.adjustments);
                return result.adjustments;
            } else {
                console.error('Position analysis failed');
                return [];
            }
        } catch (error) {
            console.error('Error analyzing positions:', error);
            return [];
        }
    }

    /**
     * Compare with reference PDF
     */
    async compareWithReference(referencePdfPath) {
        try {
            const result = await this.mcpClient.comparePdfs(
                'output/test.pdf',
                referencePdfPath
            );
            
            if (result.success) {
                console.log('PDF comparison complete:', result.differences);
                return result.differences;
            } else {
                console.error('PDF comparison failed');
                return [];
            }
        } catch (error) {
            console.error('Error comparing PDFs:', error);
            return [];
        }
    }

    /**
     * Zoom controls
     */
    zoomIn() {
        this.zoomLevel = Math.min(this.zoomLevel * 1.2, 3);
        this.updateZoom();
    }

    zoomOut() {
        this.zoomLevel = Math.max(this.zoomLevel / 1.2, 0.5);
        this.updateZoom();
    }

    updateZoom() {
        this.canvas.style.transform = `scale(${this.zoomLevel})`;
        this.canvas.style.transformOrigin = 'top left';
        this.renderFieldMarkers();
    }

    /**
     * Get current field positions
     */
    getFieldPositions() {
        return this.fieldPositions;
    }

    /**
     * Set field positions
     */
    setFieldPositions(positions) {
        this.fieldPositions = positions;
        this.createFieldMarkers();
    }
}

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PositionEditor;
}
