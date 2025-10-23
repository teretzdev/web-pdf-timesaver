/**
 * Method 1: pdf-lib Direct Extraction
 * Fastest method for unencrypted PDFs with native AcroForm fields
 */

const fs = require('fs');
const { PDFDocument, PDFTextField, PDFCheckBox, PDFRadioGroup, PDFDropdown } = require('pdf-lib');

class PdfLibExtractor {
    constructor() {
        this.name = 'pdf-lib-direct';
        this.mmPerPoint = 0.352778; // Convert points to mm
    }

    getName() {
        return this.name;
    }

    getStatus() {
        return {
            name: this.name,
            available: true,
            description: 'Direct extraction using pdf-lib (fastest for unencrypted PDFs)',
            requirements: ['pdf-lib library']
        };
    }

    async extract(pdfPath) {
        try {
            console.log('   📖 Loading PDF with pdf-lib...');
            const pdfBytes = fs.readFileSync(pdfPath);
            const pdfDoc = await PDFDocument.load(pdfBytes, {
                ignoreEncryption: true,
                updateMetadata: false
            });

            const form = pdfDoc.getForm();
            const fields = form.getFields();
            const pages = pdfDoc.getPages();

            console.log(`   📄 PDF loaded: ${pages.length} pages`);
            console.log(`   📋 Found ${fields.length} form fields`);

            if (fields.length === 0) {
                return {
                    success: false,
                    fields: [],
                    pageCount: pages.length,
                    error: 'No form fields found in PDF'
                };
            }

            const extractedFields = [];
            
            for (let i = 0; i < fields.length; i++) {
                const field = fields[i];
                const fieldData = await this.extractFieldData(field, pages);
                if (fieldData) {
                    extractedFields.push(fieldData);
                }
            }

            console.log(`   ✅ Extracted ${extractedFields.length} fields`);

            return {
                success: true,
                fields: extractedFields,
                pageCount: pages.length
            };

        } catch (error) {
            console.log(`   ❌ pdf-lib extraction failed: ${error.message}`);
            return {
                success: false,
                fields: [],
                pageCount: 0,
                error: error.message
            };
        }
    }

    /**
     * Extract data from a single field
     */
    async extractFieldData(field, pages) {
        try {
            const name = field.getName();
            let type = 'text';
            let page = 1;
            let rect = [0, 0, 100, 20]; // Default

            // Determine field type
            if (field instanceof PDFTextField) {
                type = 'text';
            } else if (field instanceof PDFCheckBox) {
                type = 'checkbox';
            } else if (field instanceof PDFRadioGroup) {
                type = 'radio';
            } else if (field instanceof PDFDropdown) {
                type = 'dropdown';
            }

            // Get position from widget annotations
            try {
                const widgets = field.acroField.getWidgets();
                if (widgets && widgets.length > 0) {
                    const widget = widgets[0];
                    const rectObj = widget.getRectangle();
                    
                    if (rectObj) {
                        rect = [rectObj.x, rectObj.y, rectObj.width, rectObj.height];
                        
                        // Find which page this widget is on
                        for (let i = 0; i < pages.length; i++) {
                            const pageDict = pages[i].node;
                            const annots = pageDict.get(PDFDocument.PDFName.of('Annots'));
                            if (annots) {
                                page = i + 1;
                                break;
                            }
                        }
                    }
                }
            } catch (e) {
                // Use default position
            }

            // Get page height for coordinate conversion
            const pageObj = pages[page - 1] || pages[0];
            const { height: pageHeight } = pageObj.getSize();

            // Convert PDF coordinates (bottom-left origin) to top-left origin
            const x = rect[0];
            const y = pageHeight - rect[1] - rect[3]; // Flip Y
            const width = rect[2];
            const height = rect[3];

            return {
                name: name,
                type: type,
                page: page,
                x: parseFloat((x * this.mmPerPoint).toFixed(2)),
                y: parseFloat((y * this.mmPerPoint).toFixed(2)),
                width: parseFloat((width * this.mmPerPoint).toFixed(2)),
                height: parseFloat((height * this.mmPerPoint).toFixed(2)),
                fontSize: Math.max(7, parseFloat((height * this.mmPerPoint * 0.7).toFixed(1))),
                rect_pdf: rect,
                confidence: 0.95,
                method: this.name
            };

        } catch (error) {
            console.warn(`   ⚠️  Could not extract field: ${error.message}`);
            return null;
        }
    }
}

module.exports = PdfLibExtractor;
