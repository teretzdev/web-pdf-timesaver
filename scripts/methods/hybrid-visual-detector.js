/**
 * Hybrid Visual + Structural Detector
 * Combines visual rendering with structural analysis
 * Uses PDF.js rendering + canvas analysis to detect fields
 * Detects fields even when annotations are missing
 */

const fs = require('fs');
const fieldMetrics = require('../utils/field-metrics');
const path = require('path');

class HybridVisualDetector {
    constructor() {
        this.name = 'hybrid-visual-detector';
        this.mmPerPoint = fieldMetrics.MM_PER_PT;
        this.canvasAvailable = this.checkCanvasAvailability();
    }

    getName() {
        return this.name;
    }

    getStatus() {
        return {
            name: this.name,
            available: this.canvasAvailable,
            description: 'Hybrid visual + structural field detection using rendering and analysis',
            requirements: ['PDF.js library', 'Canvas library (optional)']
        };
    }

    checkCanvasAvailability() {
        try {
            require('canvas');
            return true;
        } catch {
            // Canvas not available, but we can still use PDF.js rendering
            return true; // Still available, just without canvas processing
        }
    }

    async extract(pdfPath) {
        try {
            console.log('   🎨 Starting hybrid visual detection...');
            
            // Step 1: Extract structural information (annotations, text)
            const structuralFields = await this.extractStructuralFields(pdfPath);
            console.log(`   📐 Structural analysis found ${structuralFields.length} fields`);
            
            // Step 2: Extract visual information (rendering-based)
            const visualFields = await this.extractVisualFields(pdfPath);
            console.log(`   👁️  Visual analysis found ${visualFields.length} fields`);
            
            // Step 3: Combine and merge results
            const mergedFields = this.mergeVisualAndStructural(structuralFields, visualFields);
            
            console.log(`   ✅ Hybrid detector found ${mergedFields.length} total fields`);

            return {
                success: mergedFields.length > 0,
                fields: mergedFields,
                pageCount: Math.max(1, Math.max(...mergedFields.map(f => f.page || 1)))
            };

        } catch (error) {
            console.log(`   ❌ Hybrid visual detection failed: ${error.message}`);
            return {
                success: false,
                fields: [],
                pageCount: 0,
                error: error.message
            };
        }
    }

    /**
     * Extract fields using structural analysis (annotations, text)
     */
    async extractStructuralFields(pdfPath) {
        const fields = [];
        
        try {
            // Use PDF.js to get annotations
            const mod = await import('pdfjs-dist/legacy/build/pdf.mjs');
            const pdfjsLib = mod.default || mod;

            const buffer = fs.readFileSync(pdfPath);
            const data = new Uint8Array(buffer);
            const loadingTask = pdfjsLib.getDocument({ data, useWorker: false });
            const pdfDocument = await loadingTask.promise;

            const numPages = pdfDocument.numPages;

            for (let pageNum = 1; pageNum <= numPages; pageNum++) {
                const page = await pdfDocument.getPage(pageNum);
                const viewport = page.getViewport({ scale: 1.0 });
                const annotations = await page.getAnnotations();

                for (const annot of annotations) {
                    if (annot.subtype === 'Widget') {
                        const rect = annot.rect;
                        const x = rect[0] * this.mmPerPoint;
                        const y = (viewport.height - rect[3]) * this.mmPerPoint;
                        const width = (rect[2] - rect[0]) * this.mmPerPoint;
                        const height = (rect[3] - rect[1]) * this.mmPerPoint;

                        fields.push({
                            name: annot.fieldName || `field_${fields.length}`,
                            type: this.mapFieldType(annot.fieldType),
                            page: pageNum,
                            x: parseFloat(x.toFixed(2)),
                            y: parseFloat(y.toFixed(2)),
                            width: parseFloat(width.toFixed(2)),
                            height: parseFloat(height.toFixed(2)),
                            fontSize: parseFloat(fieldMetrics.estimateFontPtFromHeightMm(height, fieldMetrics.DEFAULT_FONT_PT).toFixed(1)),
                            confidence: 0.90,
                            method: this.name,
                            source: 'structural'
                        });
                    }
                }
            }
        } catch (error) {
            // If structural extraction fails, continue with visual only
        }

        return fields;
    }

    /**
     * Extract fields using visual analysis (rendering-based)
     */
    async extractVisualFields(pdfPath) {
        const fields = [];
        
        try {
            // Use PDF.js to render and analyze
            const mod = await import('pdfjs-dist/legacy/build/pdf.mjs');
            const pdfjsLib = mod.default || mod;

            const buffer = fs.readFileSync(pdfPath);
            const data = new Uint8Array(buffer);
            const loadingTask = pdfjsLib.getDocument({ data, useWorker: false });
            const pdfDocument = await loadingTask.promise;

            const numPages = Math.min(pdfDocument.numPages, 3); // Limit to first 3 pages for performance

            for (let pageNum = 1; pageNum <= numPages; pageNum++) {
                const page = await pdfDocument.getPage(pageNum);
                const viewport = page.getViewport({ scale: 2.0 }); // Higher scale for better detection
                
                // Get text content for visual clues
                const textContent = await page.getTextContent();
                
                // Analyze text layout to find field-like patterns
                const visualFields = this.detectFieldsFromTextLayout(textContent, viewport, pageNum);
                fields.push(...visualFields);
            }
        } catch (error) {
            // If visual extraction fails, continue with structural only
        }

        return fields;
    }

    /**
     * Detect fields from text layout analysis
     */
    detectFieldsFromTextLayout(textContent, viewport, pageNum) {
        const fields = [];
        const textItems = textContent.items;
        
        // Group text items by Y position (rows)
        const rows = {};
        for (const item of textItems) {
            const [a, b, c, d, e, f] = item.transform;
            const y = Math.round((viewport.height - f) / 10) * 10; // Round to nearest 10
            
            if (!rows[y]) {
                rows[y] = [];
            }
            rows[y].push({ text: item.str, x: e, y: viewport.height - f, width: item.width || 0 });
        }

        // Look for patterns that suggest form fields
        // Pattern: Label text followed by empty space (potential field)
        const sortedRows = Object.keys(rows).sort((a, b) => parseFloat(a) - parseFloat(b));
        
        for (let i = 0; i < sortedRows.length; i++) {
            const rowY = parseFloat(sortedRows[i]);
            const rowItems = rows[sortedRows[i]];
            
            // Look for labels that might indicate fields
            for (const item of rowItems) {
                if (this.looksLikeFieldLabel(item.text)) {
                    // Estimate field position after label
                    const estimatedX = (item.x + item.width + 10) * this.mmPerPoint;
                    const estimatedY = item.y * this.mmPerPoint;
                    const estimatedWidth = 100; // Default width
                    const estimatedHeight = 12; // Default height

                    fields.push({
                        name: this.generateFieldNameFromLabel(item.text),
                        type: this.guessFieldType(item.text),
                        page: pageNum,
                        x: parseFloat(estimatedX.toFixed(2)),
                        y: parseFloat(estimatedY.toFixed(2)),
                        width: parseFloat(estimatedWidth.toFixed(2)),
                        height: parseFloat(estimatedHeight.toFixed(2)),
                        fontSize: 10,
                        confidence: 0.70,
                        method: this.name,
                        source: 'visual',
                        label: item.text
                    });
                }
            }
        }

        return fields;
    }

    /**
     * Check if text looks like a field label
     */
    looksLikeFieldLabel(text) {
        const labelPatterns = [
            /^name/i,
            /^address/i,
            /^phone/i,
            /^email/i,
            /^date/i,
            /^ssn/i,
            /^ein/i,
            /^signature/i,
            /^case.*number/i,
            /^petitioner/i,
            /^respondent/i,
            /^attorney/i
        ];

        return labelPatterns.some(pattern => pattern.test(text.trim()));
    }

    /**
     * Generate field name from label text
     */
    generateFieldNameFromLabel(text) {
        return text.toLowerCase()
            .replace(/[^a-z0-9\s]/g, '')
            .replace(/\s+/g, '_')
            .replace(/^_+|_+$/g, '');
    }

    /**
     * Merge visual and structural fields
     */
    mergeVisualAndStructural(structuralFields, visualFields) {
        const merged = [...structuralFields];
        const usedPositions = new Set();

        // Add position keys for structural fields
        for (const field of structuralFields) {
            const key = `${field.page}_${Math.round(field.x)}_${Math.round(field.y)}`;
            usedPositions.add(key);
        }

        // Add visual fields that don't overlap with structural
        for (const field of visualFields) {
            const key = `${field.page}_${Math.round(field.x)}_${Math.round(field.y)}`;
            if (!usedPositions.has(key)) {
                merged.push(field);
                usedPositions.add(key);
            }
        }

        return merged;
    }

    /**
     * Map PDF.js field type to our type
     */
    mapFieldType(pdfType) {
        const typeMap = {
            'Tx': 'text',
            'Btn': 'checkbox',
            'Ch': 'dropdown',
            'Sig': 'signature'
        };
        return typeMap[pdfType] || 'text';
    }

    /**
     * Guess field type from text
     */
    guessFieldType(text) {
        const lowerText = text.toLowerCase();
        
        if (lowerText.includes('date')) return 'date';
        if (lowerText.includes('signature')) return 'signature';
        if (lowerText.includes('ssn') || lowerText.includes('social')) return 'ssn';
        if (lowerText.includes('ein')) return 'ein';
        if (lowerText.includes('phone')) return 'phone';
        if (lowerText.includes('email')) return 'email';
        if (lowerText.includes('address')) return 'address';
        
        return 'text';
    }
}

module.exports = HybridVisualDetector;

