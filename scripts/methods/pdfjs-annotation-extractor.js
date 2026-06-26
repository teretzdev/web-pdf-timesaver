/**
 * PDF.js Annotation Extractor
 * Extracts widget annotations (form fields) directly from PDF.js
 * Better than text extraction as it finds actual form field annotations
 */

const fs = require('fs');
const fieldMetrics = require('../utils/field-metrics');

class PdfJsAnnotationExtractor {
    constructor() {
        this.name = 'pdfjs-annotation-extractor';
        this.mmPerPoint = fieldMetrics.MM_PER_PT;
    }

    getName() {
        return this.name;
    }

    getStatus() {
        return {
            name: this.name,
            available: true,
            description: 'Extract widget annotations (form fields) directly from PDF.js',
            requirements: ['PDF.js library']
        };
    }

    async extract(pdfPath) {
        try {
            console.log('   🔍 Extracting annotations using PDF.js...');
            
            const annotations = await this.extractAnnotationsUsingPdfjs(pdfPath);
            
            if (annotations.length === 0) {
                return {
                    success: false,
                    fields: [],
                    pageCount: 1,
                    error: 'No annotations found in PDF'
                };
            }

            console.log(`   📋 Found ${annotations.length} annotations`);
            
            const fields = this.processAnnotations(annotations);
            
            console.log(`   ✅ Extracted ${fields.length} form fields from annotations`);

            return {
                success: fields.length > 0,
                fields: fields,
                pageCount: Math.max(1, Math.max(...annotations.map(a => a.page || 1)))
            };

        } catch (error) {
            console.log(`   ❌ PDF.js annotation extraction failed: ${error.message}`);
            return {
                success: false,
                fields: [],
                pageCount: 0,
                error: error.message
            };
        }
    }

    /**
     * Extract annotations using PDF.js
     */
    async extractAnnotationsUsingPdfjs(pdfPath) {
        const mod = await import('pdfjs-dist/legacy/build/pdf.mjs');
        const pdfjsLib = mod.default || mod;

        const buffer = fs.readFileSync(pdfPath);
        const data = new Uint8Array(buffer);
        const loadingTask = pdfjsLib.getDocument({ data, useWorker: false });
        const pdfDocument = await loadingTask.promise;

        const annotations = [];
        const numPages = pdfDocument.numPages;

        for (let pageNum = 1; pageNum <= numPages; pageNum++) {
            const page = await pdfDocument.getPage(pageNum);
            const viewport = page.getViewport({ scale: 1.0 });
            const pageAnnotations = await page.getAnnotations();

            for (const annot of pageAnnotations) {
                // Focus on Widget annotations (form fields)
                if (annot.subtype === 'Widget') {
                    const rect = annot.rect;
                    
                    // Convert coordinates from bottom-left origin to top-left
                    const x = rect[0];
                    const yFromBottom = rect[1];
                    const y = viewport.height - rect[3]; // Top of annotation
                    const width = rect[2] - rect[0];
                    const height = rect[3] - rect[1];

                    annotations.push({
                        fieldName: annot.fieldName || annot.id || `field_${annotations.length}`,
                        fieldType: annot.fieldType || 'text',
                        fieldValue: annot.fieldValue || '',
                        page: pageNum,
                        x: x,
                        y: y,
                        width: width,
                        height: height,
                        rect: rect,
                        viewportHeight: viewport.height,
                        annotation: annot
                    });
                }
            }
        }

        return annotations;
    }

    /**
     * PDF /FT Btn covers checkboxes, radio groups, and pushbuttons. Use fieldFlags
     * when present (pdf.js: fieldFlags on widget annotations).
     */
    mapWidgetFieldType(annot, widthMm, heightMm) {
        if (annot && (annot.checkBox === true || annot.checkbox === true)) {
            return 'checkbox';
        }
        const raw = String(annot.fieldType || annot.fieldTypeName || '').replace(/^\//, '').trim();
        const ft = raw.length ? raw.charAt(0).toUpperCase() + raw.slice(1).toLowerCase() : '';
        const flags = Number(annot.fieldFlags ?? annot.flags ?? 0);
        const PushButton = 1 << 16;
        const Radio = 1 << 15;

        if (ft === 'Btn') {
            if (flags & PushButton) {
                return 'text';
            }
            if (flags & Radio) {
                return 'radio';
            }
            return 'checkbox';
        }

        const typeMap = {
            Tx: 'text',
            Btn: 'checkbox',
            Ch: 'select',
            Sig: 'signature'
        };
        if (ft && typeMap[ft]) {
            return typeMap[ft];
        }

        // Some PDFs omit /FT on widgets; small square annotations are usually checkboxes.
        if (widthMm > 0 && heightMm > 0 && widthMm < 7 && heightMm < 7 && Math.abs(widthMm - heightMm) < 2) {
            return 'checkbox';
        }

        return 'text';
    }

    /** Build normalized field objects for the ensemble pipeline. */
    processAnnotations(annotations) {
        const fields = [];

        for (const annot of annotations) {
            // Convert PDF points to mm
            const x_mm = annot.x * this.mmPerPoint;
            const y_mm = annot.y * this.mmPerPoint;
            const width_mm = annot.width * this.mmPerPoint;
            const height_mm = annot.height * this.mmPerPoint;

            const fieldType = this.mapWidgetFieldType(annot.annotation || annot, width_mm, height_mm);

            fields.push({
                name: annot.fieldName,
                type: fieldType,
                page: annot.page,
                x: parseFloat(x_mm.toFixed(2)),
                y: parseFloat(y_mm.toFixed(2)),
                width: parseFloat(width_mm.toFixed(2)),
                height: parseFloat(height_mm.toFixed(2)),
                fontSize: parseFloat(fieldMetrics.estimateFontPtFromHeightMm(height_mm, fieldMetrics.DEFAULT_FONT_PT).toFixed(1)),
                confidence: 0.88,
                method: this.name,
                value: annot.fieldValue || undefined
            });
        }

        return fields;
    }
}

module.exports = PdfJsAnnotationExtractor;

