/**
 * Method 1: pdf-lib Direct Extraction
 * Fastest method for unencrypted PDFs with native AcroForm fields
 */

const fs = require('fs');
const { PDFDocument, PDFTextField, PDFCheckBox, PDFRadioGroup, PDFDropdown } = require('pdf-lib');
const fieldMetrics = require('../utils/field-metrics');

class PdfLibExtractor {
    constructor() {
        this.name = 'pdf-lib-direct';
        this.mmPerPoint = fieldMetrics.MM_PER_PT; // Convert points to mm
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
            
            // CRITICAL: Check if PDF is encrypted before loading
            // If encrypted, we should use qpdf-decrypt first (which is now method 1)
            // Only use ignoreEncryption as last resort - it can cause coordinate issues
            let pdfDoc;
            try {
                pdfDoc = await PDFDocument.load(pdfBytes, {
                    ignoreEncryption: false,  // Try without ignoring encryption first
                    updateMetadata: false
                });
            } catch (encError) {
                // If PDF is encrypted, this method should not be used
                // Let qpdf-decrypt handle it instead
                if (encError.message && (encError.message.includes('encrypt') || encError.message.includes('password'))) {
                    console.log('   ⚠️  PDF is encrypted - skipping pdf-lib-direct (use qpdf-decrypt method instead)');
                    return {
                        success: false,
                        fields: [],
                        pageCount: 0,
                        error: 'PDF is encrypted - use qpdf-decrypt method first'
                    };
                }
                // For other errors, try with ignoreEncryption as fallback
                console.log('   ⚠️  Trying with ignoreEncryption enabled (may have coordinate issues)');
                pdfDoc = await PDFDocument.load(pdfBytes, {
                    ignoreEncryption: true,
                    updateMetadata: false
                });
            }

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
            let rect = null; // NO DEFAULT - must extract from PDF

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

            // Get position from widget annotations - CRITICAL: must succeed or skip field
            try {
                const widgets = field.acroField.getWidgets();
                if (widgets && widgets.length > 0) {
                    const widget = widgets[0];
                    
                    // CRITICAL: Use raw Rect array from PDF dict - getRectangle() may use wrong coordinate system
                    const { PDFName, PDFNumber } = require('pdf-lib');
                    let rectArray = null;
                    
                    try {
                        rectArray = widget.dict.lookup(PDFName.of('Rect'));
                    } catch (e) {
                        try {
                            rectArray = widget.dict.get(PDFName.of('Rect'));
                        } catch (e2) {
                            // Try getRectangle as fallback
                            try {
                                const rectObj = widget.getRectangle();
                                if (rectObj) {
                                    rectArray = {
                                        array: [
                                            { value: rectObj.x },
                                            { value: rectObj.y },
                                            { value: rectObj.x + rectObj.width },
                                            { value: rectObj.y + rectObj.height }
                                        ]
                                    };
                                }
                            } catch (e3) {
                                console.warn(`   ⚠️  Field '${name}': Cannot access Rect - SKIPPING`);
                                return null;
                            }
                        }
                    }
                    
                    if (rectArray && rectArray.array && rectArray.array.length >= 4) {
                        // PDF Rect format: [x1, y1, x2, y2] where:
                        // (x1, y1) = bottom-left corner
                        // (x2, y2) = top-right corner
                        // All coordinates are in PDF points with bottom-left origin
                        const getValue = (v) => {
                            if (v === null || v === undefined) return 0;
                            if (typeof v === 'number') return v;
                            if (typeof v === 'string') return parseFloat(v) || 0;
                            // Handle PDFNumber objects from pdf-lib
                            if (v && typeof v === 'object') {
                                // PDFNumber has numberValue property
                                if (v.numberValue !== undefined && typeof v.numberValue === 'number') {
                                    return v.numberValue;
                                }
                                // Try asNumber() method if available
                                if (typeof v.asNumber === 'function') {
                                    try {
                                        return v.asNumber();
                                    } catch (e) {
                                        // Ignore
                                    }
                                }
                                // Try value property
                                if (v.value !== undefined && typeof v.value === 'number') {
                                    return v.value;
                                }
                                // Try calling as function (some PDF objects are callable)
                                if (typeof v === 'function') {
                                    try {
                                        const result = v();
                                        if (typeof result === 'number') return result;
                                        if (result?.numberValue !== undefined) return result.numberValue;
                                        if (result?.value !== undefined) return result.value;
                                    } catch (e) {
                                        // Ignore
                                    }
                                }
                            }
                            return 0;
                        };
                        
                        const x1 = getValue(rectArray.array[0]);
                        const y1 = getValue(rectArray.array[1]); // Bottom Y
                        const x2 = getValue(rectArray.array[2]);
                        const y2 = getValue(rectArray.array[3]); // Top Y
                        
                        // Validate extracted values
                        if (isNaN(x1) || isNaN(y1) || isNaN(x2) || isNaN(y2) || x1 === 0 && y1 === 0 && x2 === 0 && y2 === 0) {
                            console.warn(`   ⚠️  Field '${name}': Invalid Rect values [${x1}, ${y1}, ${x2}, ${y2}] - SKIPPING`);
                            return null;
                        }
                        
                        // Store as [x1, y1, x2, y2] for proper conversion
                        rect = [x1, y1, x2, y2];
                        
                        // Find which page this widget is on by checking P (Page) reference
                        try {
                            const { PDFName } = require('pdf-lib');
                            const pageRef = widget.dict.lookup(PDFName.of('P'));
                            if (pageRef) {
                                // Find page index by comparing page object references
                                for (let i = 0; i < pages.length; i++) {
                                    if (pages[i].node === pageRef || pages[i].node.ref === pageRef) {
                                        page = i + 1;
                                        break;
                                    }
                                }
                            }
                        } catch (e) {
                            // Fallback: assume page 1
                            page = 1;
                        }
                    } else {
                        console.warn(`   ⚠️  Field '${name}': Could not extract Rect array from widget - SKIPPING`);
                        return null;
                    }
                } else {
                    console.warn(`   ⚠️  Field '${name}': No widgets found - SKIPPING (no hardcoded defaults)`);
                    return null; // Skip fields without widgets
                }
            } catch (e) {
                console.warn(`   ⚠️  Field '${name}': Error extracting position: ${e.message} - SKIPPING (no hardcoded defaults)`);
                return null; // Skip fields that fail extraction - NO HARDCODED DEFAULTS
            }
            
            // CRITICAL: If we don't have a valid rect, skip this field
            if (!rect || rect[0] === undefined || rect[1] === undefined) {
                console.warn(`   ⚠️  Field '${name}': Invalid rect - SKIPPING`);
                return null;
            }

            // Get page height for coordinate conversion - MUST use actual page size
            const pageObj = pages[page - 1] || pages[0];
            if (!pageObj) {
                console.warn(`   ⚠️  Field '${name}': Could not find page ${page} - SKIPPING`);
                return null;
            }
            
            const { width: pageWidth, height: pageHeight } = pageObj.getSize();
            
            // Log actual page dimensions for debugging
            console.log(`   📐 Field '${name}': Page ${page} size = ${pageWidth.toFixed(2)} x ${pageHeight.toFixed(2)} points`);

            // Convert PDF coordinates (bottom-left origin) to top-left origin
            // PDF rect format: [x1, y1, x2, y2] where:
            // (x1, y1) = bottom-left corner, (x2, y2) = top-right corner
            // All in PDF points with bottom-left origin
            // NOTE: Some PDFs may have y1 > y2, meaning y1 is actually the top edge
            const x1 = rect[0]; // Left edge
            const y1 = rect[1];
            const x2 = rect[2]; // Right edge
            const y2 = rect[3];
            
            // Determine which y value is top and which is bottom
            // In standard PDF: y1 is bottom, y2 is top (so y1 < y2)
            // But some PDFs have them swapped: y1 is top, y2 is bottom (so y1 > y2)
            const yBottom = y1 < y2 ? y1 : y2;
            const yTop = y1 < y2 ? y2 : y1;
            
            const width = x2 - x1;
            const height = yTop - yBottom;
            
            // For FPDF (top-left origin), we need:
            // x = x1 (stays same)
            // y = pageHeight - yTop (flip Y, use TOP edge)
            const x = x1;
            const y = pageHeight - yTop;

            // Convert to millimeters
            const xMm = parseFloat((x * this.mmPerPoint).toFixed(2));
            const yMm = parseFloat((y * this.mmPerPoint).toFixed(2));
            const widthMm = parseFloat((width * this.mmPerPoint).toFixed(2));
            const heightMm = parseFloat((height * this.mmPerPoint).toFixed(2));

            console.log(`   📍 Field '${name}': PDF rect [${x1.toFixed(2)}, ${y1.toFixed(2)}, ${x2.toFixed(2)}, ${y2.toFixed(2)}] -> FPDF [${xMm}, ${yMm}] mm, size [${widthMm}, ${heightMm}] mm`);

            return {
                name: name,
                type: type,
                page: page,
                x: xMm,
                y: yMm,
                width: widthMm,
                height: heightMm,
                fontSize: parseFloat(fieldMetrics.estimateFontPtFromHeightMm(heightMm, fieldMetrics.DEFAULT_FONT_PT).toFixed(1)),
                rect_pdf: [x1, y1, x2, y2], // Store original PDF coordinates
                pageWidth: parseFloat((pageWidth * this.mmPerPoint).toFixed(2)),
                pageHeight: parseFloat((pageHeight * this.mmPerPoint).toFixed(2)),
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
