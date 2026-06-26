/**
 * Method 3: PDF.js Text Layer Extraction
 * Analyzes text positions to estimate field locations
 */

const fs = require('fs');
const path = require('path');
const fieldMetrics = require('../utils/field-metrics');

class PdfJsTextExtractor {
    constructor() {
        this.name = 'pdfjs-text-extraction';
        this.mmPerPoint = fieldMetrics.MM_PER_PT;
        this.fieldPatterns = [
            // Common field label patterns
            { pattern: /name/i, offset: { x: 50, y: 0 } },
            { pattern: /address/i, offset: { x: 50, y: 0 } },
            { pattern: /phone/i, offset: { x: 50, y: 0 } },
            { pattern: /email/i, offset: { x: 50, y: 0 } },
            { pattern: /date/i, offset: { x: 50, y: 0 } },
            { pattern: /signature/i, offset: { x: 50, y: 0 } },
            { pattern: /ssn|social security/i, offset: { x: 50, y: 0 } },
            { pattern: /ein|employer identification/i, offset: { x: 50, y: 0 } },
            { pattern: /case number/i, offset: { x: 50, y: 0 } },
            { pattern: /petitioner/i, offset: { x: 50, y: 0 } },
            { pattern: /respondent/i, offset: { x: 50, y: 0 } },
            { pattern: /attorney/i, offset: { x: 50, y: 0 } },
            { pattern: /court/i, offset: { x: 50, y: 0 } },
            { pattern: /county/i, offset: { x: 50, y: 0 } },
            { pattern: /child/i, offset: { x: 50, y: 0 } },
            { pattern: /marriage/i, offset: { x: 50, y: 0 } },
            { pattern: /divorce/i, offset: { x: 50, y: 0 } }
        ];
    }

    getName() {
        return this.name;
    }

    getStatus() {
        return {
            name: this.name,
            available: true,
            description: 'Extract field positions by analyzing text layer patterns',
            requirements: ['PDF.js library']
        };
    }

    async extract(pdfPath) {
        try {
            console.log('   📖 Analyzing PDF text layer...');
            
            // Use PDF.js to extract text with positions
            const textItems = await this.extractTextItemsUsingPdfjs(pdfPath);
            
            if (textItems.length === 0) {
                return {
                    success: false,
                    fields: [],
                    pageCount: 1,
                    error: 'No text found in PDF'
                };
            }

            console.log(`   📝 Found ${textItems.length} text items`);
            
            const fields = this.estimateFieldsFromText(textItems);
            
            console.log(`   ✅ Estimated ${fields.length} field positions`);

            return {
                success: fields.length > 0,
                fields: fields,
                pageCount: Math.max(1, Math.max(...textItems.map(i => i.page || 1)))
            };

        } catch (error) {
            console.log(`   ❌ PDF.js text extraction failed: ${error.message}`);
            return {
                success: false,
                fields: [],
                pageCount: 0,
                error: error.message
            };
        }
    }

    /**
     * Extract text items using PDF.js (Node.js environment)
     */
    async extractTextItemsUsingPdfjs(pdfPath) {
        // Load pdfjs-dist legacy build for Node
        const mod = await import('pdfjs-dist/legacy/build/pdf.mjs');
        const pdfjsLib = mod.default || mod;
        // In Node we don't need a worker; disable it to avoid warnings
        // In Node, avoid worker by disabling it in getDocument options

        const buffer = fs.readFileSync(pdfPath);
        const data = new Uint8Array(buffer);
        const loadingTask = pdfjsLib.getDocument({ data, useWorker: false });
        const pdfDocument = await loadingTask.promise;

        const textItems = [];
        const numPages = pdfDocument.numPages;

        for (let pageNum = 1; pageNum <= numPages; pageNum++) {
            const page = await pdfDocument.getPage(pageNum);
            const viewport = page.getViewport({ scale: 1.0 });
            const textContent = await page.getTextContent();

            for (const item of textContent.items) {
                // item.transform gives text matrix [a, b, c, d, e, f]
                const [a, b, c, d, e, f] = item.transform;
                const fontSize = Math.hypot(a, b);
                const width = item.width || (item.str.length * fontSize * 0.5);
                const height = fontSize;

                // Convert PDF.js coordinate (origin bottom-left) to standard PDF points
                const x = e;
                const yFromBottom = f;
                const y = viewport.height - yFromBottom; // normalize to top-left origin if needed later

                textItems.push({
                    text: item.str,
                    x,
                    y,
                    width,
                    height,
                    page: pageNum
                });
            }
        }

        return textItems;
    }

    /**
     * Estimate field positions based on text patterns
     */
    estimateFieldsFromText(textItems) {
        const fields = [];
        const usedPositions = new Set();

        for (const textItem of textItems) {
            const fieldInfo = this.findFieldPattern(textItem.text);
            
            if (fieldInfo) {
                const fieldName = this.generateFieldName(textItem.text);
                const fieldPosition = this.calculateFieldPosition(textItem, fieldInfo.offset);
                
                // Avoid duplicate positions
                const positionKey = `${fieldPosition.x},${fieldPosition.y}`;
                if (!usedPositions.has(positionKey)) {
                    usedPositions.add(positionKey);
                    
                    fields.push({
                        name: fieldName,
                        type: this.guessFieldType(textItem.text),
                        page: 1,
                        x: parseFloat((fieldPosition.x * this.mmPerPoint).toFixed(2)),
                        y: parseFloat((fieldPosition.y * this.mmPerPoint).toFixed(2)),
                        width: parseFloat((fieldPosition.width * this.mmPerPoint).toFixed(2)),
                        height: parseFloat((fieldPosition.height * this.mmPerPoint).toFixed(2)),
                        fontSize: parseFloat(fieldMetrics.estimateFontPtFromHeightMm(fieldPosition.height * this.mmPerPoint, fieldMetrics.DEFAULT_FONT_PT).toFixed(1)),
                        confidence: 0.70,
                        method: this.name,
                        estimated: true,
                        sourceText: textItem.text
                    });
                }
            }
        }

        return fields;
    }

    /**
     * Find matching field pattern for text
     */
    findFieldPattern(text) {
        for (const pattern of this.fieldPatterns) {
            if (pattern.pattern.test(text)) {
                return pattern;
            }
        }
        return null;
    }

    /**
     * Generate field name from text
     */
    generateFieldName(text) {
        return text.toLowerCase()
            .replace(/[^a-z0-9\s]/g, '')
            .replace(/\s+/g, '_')
            .replace(/^_+|_+$/g, '');
    }

    /**
     * Calculate field position based on text position and offset
     */
    calculateFieldPosition(textItem, offset) {
        return {
            x: textItem.x + textItem.width + offset.x,
            y: textItem.y + offset.y,
            width: 100, // Default field width
            height: 15  // Default field height
        };
    }

    /**
     * Guess field type based on text content
     */
    guessFieldType(text) {
        const lowerText = text.toLowerCase();
        
        if (lowerText.includes('date')) return 'date';
        if (lowerText.includes('signature')) return 'signature';
        if (lowerText.includes('ssn') || lowerText.includes('social security')) return 'ssn';
        if (lowerText.includes('phone')) return 'phone';
        if (lowerText.includes('email')) return 'email';
        if (lowerText.includes('address')) return 'address';
        if (lowerText.includes('number')) return 'number';
        
        return 'text';
    }
}

module.exports = PdfJsTextExtractor;
