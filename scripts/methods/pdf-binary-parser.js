/**
 * PDF Binary Parser
 * Direct PDF binary parsing for widget annotations
 * Extracts AcroForm dictionaries directly from PDF binary structure
 * Bypasses library limitations by parsing PDF structure directly
 */

const fs = require('fs');
const fieldMetrics = require('../utils/field-metrics');

class PdfBinaryParser {
    constructor() {
        this.name = 'pdf-binary-parser';
        this.mmPerPoint = fieldMetrics.MM_PER_PT;
    }

    getName() {
        return this.name;
    }

    getStatus() {
        return {
            name: this.name,
            available: true,
            description: 'Direct PDF binary parsing for AcroForm extraction',
            requirements: ['PDF file with AcroForm structure']
        };
    }

    async extract(pdfPath) {
        try {
            console.log('   🔬 Starting PDF binary parsing...');
            
            const buffer = fs.readFileSync(pdfPath);
            const pageHeightPoints = this.extractPageHeightPoints(buffer);
            const fields = this.parseAcroForm(buffer, pageHeightPoints);
            
            if (fields.length === 0) {
                return {
                    success: false,
                    fields: [],
                    pageCount: 1,
                    error: 'No AcroForm fields found in PDF binary structure'
                };
            }

            console.log(`   ✅ Binary parser extracted ${fields.length} fields`);

            return {
                success: fields.length > 0,
                fields: fields,
                pageCount: this.extractPageCount(buffer)
            };

        } catch (error) {
            console.log(`   ❌ PDF binary parsing failed: ${error.message}`);
            return {
                success: false,
                fields: [],
                pageCount: 0,
                error: error.message
            };
        }
    }

    /**
     * Parse AcroForm from PDF binary
     */
    parseAcroForm(buffer, pageHeightPoints = 792) {
        const fields = [];
        const bufferStr = buffer.toString('binary');
        
        // Find AcroForm dictionary
        const acroFormMatch = bufferStr.match(/\/AcroForm\s+<<[^>]*>>/);
        if (!acroFormMatch) {
            return fields;
        }

        // Extract field references
        // Look for /Fields array
        const fieldsArrayRegex = /\/Fields\s*\[([^\]]+)\]/g;
        let match;
        
        while ((match = fieldsArrayRegex.exec(bufferStr)) !== null) {
            const fieldsRefs = match[1];
            
            // Extract object references (format: X Y R)
            const objRefRegex = /(\d+)\s+(\d+)\s+R/g;
            let objMatch;
            
            while ((objMatch = objRefRegex.exec(fieldsRefs)) !== null) {
                const objNum = parseInt(objMatch[1]);
                const objGen = parseInt(objMatch[2]);
                
                // Try to find and parse the field object
                const fieldObj = this.parseFieldObject(bufferStr, objNum, objGen, pageHeightPoints);
                if (fieldObj) {
                    fields.push(fieldObj);
                }
            }
        }

        // Also look for direct widget annotations in pages
        const widgetFields = this.parseWidgetAnnotations(bufferStr, pageHeightPoints);
        fields.push(...widgetFields);

        return fields;
    }

    /**
     * Parse a field object from PDF
     */
    parseFieldObject(bufferStr, objNum, objGen, pageHeightPoints = 792) {
        // Find object definition: objNum objGen obj
        const objRegex = new RegExp(`${objNum}\\s+${objGen}\\s+obj[\\s\\S]*?endobj`, 'g');
        const objMatch = objRegex.exec(bufferStr);
        
        if (!objMatch) {
            return null;
        }

        const objContent = objMatch[0];
        
        // Extract field name (/T)
        const nameMatch = objContent.match(/\/T\s*\(([^)]+)\)/);
        const fieldName = nameMatch ? this.unescapePdfString(nameMatch[1]) : `field_${objNum}`;
        
        // Extract field type (/FT)
        const typeMatch = objContent.match(/\/FT\s*\/(\w+)/);
        const fieldType = typeMatch ? typeMatch[1] : 'Tx';
        
        // Extract rectangle (/Rect)
        const rectMatch = objContent.match(/\/Rect\s*\[([^\]]+)\]/);
        if (!rectMatch) {
            return null;
        }

        const rectValues = rectMatch[1].trim().split(/\s+/).map(v => parseFloat(v));
        if (rectValues.length < 4) {
            return null;
        }

        const [x1, y1, x2, y2] = rectValues;
        const x = x1 * this.mmPerPoint;
        // PDF coordinates are bottom-left origin. Convert top edge (y2) to top-left origin.
        const y = (pageHeightPoints - y2) * this.mmPerPoint;
        const width = (x2 - x1) * this.mmPerPoint;
        const height = (y2 - y1) * this.mmPerPoint;
        
        // Get page number (simplified - would need to traverse page tree)
        const pageNum = 1; // Default to page 1

        // Map field types
        const typeMap = {
            'Tx': 'text',
            'Btn': 'checkbox',
            'Ch': 'dropdown',
            'Sig': 'signature'
        };

        return {
            name: fieldName,
            type: typeMap[fieldType] || 'text',
            page: pageNum,
            x: parseFloat(x.toFixed(2)),
            y: parseFloat(y.toFixed(2)),
            width: parseFloat(width.toFixed(2)),
            height: parseFloat(height.toFixed(2)),
            fontSize: parseFloat(fieldMetrics.estimateFontPtFromHeightMm(height, fieldMetrics.DEFAULT_FONT_PT).toFixed(1)),
            confidence: 0.82,
            method: this.name
        };
    }

    /**
     * Parse widget annotations directly
     */
    parseWidgetAnnotations(bufferStr, pageHeightPoints = 792) {
        const fields = [];
        
        // Look for annotation dictionaries with /Subtype /Widget
        const widgetRegex = /\/Subtype\s*\/Widget[\s\S]*?\/Rect\s*\[([^\]]+)\]/g;
        let match;
        let fieldIndex = 0;
        
        while ((match = widgetRegex.exec(bufferStr)) !== null) {
            const rectValues = match[1].trim().split(/\s+/).map(v => parseFloat(v));
            if (rectValues.length < 4) continue;

            const [x1, y1, x2, y2] = rectValues;
            const x = x1 * this.mmPerPoint;
            // Convert from PDF bottom-left origin to top-left origin.
            const y = (pageHeightPoints - y2) * this.mmPerPoint;
            const width = (x2 - x1) * this.mmPerPoint;
            const height = (y2 - y1) * this.mmPerPoint;

            fields.push({
                name: `widget_field_${fieldIndex++}`,
                type: 'text',
                page: 1,
                x: parseFloat(x.toFixed(2)),
                y: parseFloat(y.toFixed(2)),
                width: parseFloat(width.toFixed(2)),
                height: parseFloat(height.toFixed(2)),
                fontSize: parseFloat(fieldMetrics.estimateFontPtFromHeightMm(height, fieldMetrics.DEFAULT_FONT_PT).toFixed(1)),
                confidence: 0.75,
                method: this.name
            });
        }

        return fields;
    }

    /**
     * Extract page count from PDF
     */
    extractPageCount(buffer) {
        const bufferStr = buffer.toString('binary');
        
        // Look for /Count in pages dictionary
        const countMatch = bufferStr.match(/\/Count\s+(\d+)/);
        if (countMatch) {
            return parseInt(countMatch[1]);
        }

        // Fallback: count page objects
        const pageObjRegex = /\/Type\s*\/Page\b/g;
        const matches = bufferStr.match(pageObjRegex);
        return matches ? matches.length : 1;
    }

    /**
     * Extract first page height in PDF points from MediaBox.
     */
    extractPageHeightPoints(buffer) {
        const bufferStr = buffer.toString('binary');
        // Typical MediaBox: /MediaBox [0 0 612 792]
        const mediaBoxMatch = bufferStr.match(/\/MediaBox\s*\[\s*([-+]?\d*\.?\d+)\s+([-+]?\d*\.?\d+)\s+([-+]?\d*\.?\d+)\s+([-+]?\d*\.?\d+)\s*\]/);
        if (mediaBoxMatch) {
            const y1 = parseFloat(mediaBoxMatch[2]);
            const y2 = parseFloat(mediaBoxMatch[4]);
            if (Number.isFinite(y1) && Number.isFinite(y2) && y2 > y1) {
                return y2 - y1;
            }
        }
        return 792;
    }

    /**
     * Unescape PDF string (handle octal escapes, etc.)
     */
    unescapePdfString(str) {
        return str
            .replace(/\\([0-7]{1,3})/g, (match, oct) => {
                return String.fromCharCode(parseInt(oct, 8));
            })
            .replace(/\\(.)/g, '$1');
    }
}

module.exports = PdfBinaryParser;

