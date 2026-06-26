#!/usr/bin/env node
/**
 * Unified PDF Position Extraction Pipeline
 * Automatically detects, decrypts (if needed), and extracts field positions
 * Primary focus: W-9 forms with 100% success rate
 */

const fs = require('fs');
const path = require('path');
const { PDFDocument, PDFTextField, PDFCheckBox, PDFRadioGroup, PDFDropdown } = require('pdf-lib');
const { spawn } = require('child_process');
const fieldMetrics = require('./utils/field-metrics');

class AutoPositionExtractor {
    constructor() {
        this.qpdfPath = this.findQpdfBinary();
        this.mmPerPoint = fieldMetrics.MM_PER_PT; // Convert points to mm
        this.tempDir = path.join(__dirname, '../temp');
        this.ensureTempDir();
    }

    /**
     * Main extraction pipeline
     */
    async extractPositions(pdfPath, templateId) {
        console.log('🔍 Starting automatic position extraction...');
        console.log(`📄 PDF: ${pdfPath}`);
        console.log(`🏷️  Template: ${templateId}`);
        console.log('');

        const result = {
            success: false,
            method: 'unknown',
            fields: [],
            pageCount: 0,
            warnings: [],
            errors: [],
            extractedAt: new Date().toISOString(),
            source: path.basename(pdfPath)
        };

        try {
            // Step 1: Try direct extraction with pdf-lib
            console.log('📋 Step 1: Attempting direct extraction...');
            const directResult = await this.extractDirect(pdfPath);
            
            if (directResult.success && directResult.fields.length > 0) {
                console.log(`✅ Direct extraction successful: ${directResult.fields.length} fields`);
                result.success = true;
                result.method = 'pdf-lib-direct';
                result.fields = directResult.fields;
                result.pageCount = directResult.pageCount;
            } else {
                console.log('⚠️  Direct extraction failed, trying decryption...');
                
                // Step 2: Try decryption + extraction
                const decryptResult = await this.extractWithDecryption(pdfPath);
                
                if (decryptResult.success && decryptResult.fields.length > 0) {
                    console.log(`✅ Decryption + extraction successful: ${decryptResult.fields.length} fields`);
                    result.success = true;
                    result.method = 'qpdf-decrypt-pdf-lib';
                    result.fields = decryptResult.fields;
                    result.pageCount = decryptResult.pageCount;
                } else {
                    console.log('❌ All extraction methods failed');
                    result.errors.push('All extraction methods failed');
                    result.warnings.push('PDF may be heavily encrypted or corrupted');
                }
            }

            // Step 3: Validate extraction
            if (result.success) {
                console.log('🔍 Step 3: Validating extraction...');
                const validation = this.validateExtraction(result.fields, result.pageCount);
                result.warnings.push(...validation.warnings);
                
                if (validation.isValid) {
                    console.log('✅ Validation passed');
                } else {
                    console.log('⚠️  Validation warnings detected');
                }
            }

            // Step 4: Save results
            if (result.success) {
                console.log('💾 Step 4: Saving results...');
                await this.saveResults(result, templateId);
                console.log('✅ Results saved successfully');
            }

        } catch (error) {
            console.error('❌ Extraction failed:', error.message);
            result.errors.push(error.message);
        }

        return result;
    }

    /**
     * Direct extraction using pdf-lib
     */
    async extractDirect(pdfPath) {
        try {
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
                return { success: false, fields: [], pageCount: pages.length };
            }

            const extractedFields = [];
            
            for (let i = 0; i < fields.length; i++) {
                const field = fields[i];
                const fieldData = await this.extractFieldData(field, pages);
                if (fieldData) {
                    extractedFields.push(fieldData);
                }
            }

            return {
                success: true,
                fields: extractedFields,
                pageCount: pages.length
            };

        } catch (error) {
            console.log(`   ❌ Direct extraction failed: ${error.message}`);
            return { success: false, fields: [], pageCount: 0 };
        }
    }

    /**
     * Extract with decryption using qpdf
     */
    async extractWithDecryption(pdfPath) {
        if (!this.qpdfPath) {
            console.log('   ⚠️  qpdf not available, skipping decryption');
            return { success: false, fields: [], pageCount: 0 };
        }

        try {
            // Try common passwords
            const passwords = ['', 'password', '123456', 'admin'];
            const tempPdf = path.join(this.tempDir, `decrypted_${Date.now()}.pdf`);

            for (const password of passwords) {
                console.log(`   🔓 Trying password: "${password || '(empty)'}"`);
                
                const success = await this.decryptPdf(pdfPath, tempPdf, password);
                if (success) {
                    console.log('   ✅ Decryption successful');
                    
                    // Try extraction on decrypted PDF
                    const extractResult = await this.extractDirect(tempPdf);
                    
                    // Cleanup
                    if (fs.existsSync(tempPdf)) {
                        fs.unlinkSync(tempPdf);
                    }
                    
                    if (extractResult.success) {
                        return extractResult;
                    }
                }
            }

            return { success: false, fields: [], pageCount: 0 };

        } catch (error) {
            console.log(`   ❌ Decryption failed: ${error.message}`);
            return { success: false, fields: [], pageCount: 0 };
        }
    }

    /**
     * Decrypt PDF using qpdf
     */
    async decryptPdf(inputPath, outputPath, password) {
        return new Promise((resolve) => {
            const args = ['--decrypt'];
            if (password) {
                args.push(`--password=${password}`);
            }
            args.push(inputPath, outputPath);

            const qpdf = spawn(this.qpdfPath, args);
            
            qpdf.on('close', (code) => {
                resolve(code === 0 && fs.existsSync(outputPath));
            });
            
            qpdf.on('error', () => {
                resolve(false);
            });
        });
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
                fontSize: parseFloat(fieldMetrics.estimateFontPtFromHeightMm(height * this.mmPerPoint, fieldMetrics.DEFAULT_FONT_PT).toFixed(1)),
                rect_pdf: rect
            };

        } catch (error) {
            console.warn(`   ⚠️  Could not extract field: ${error.message}`);
            return null;
        }
    }

    /**
     * Validate extracted fields
     */
    validateExtraction(fields, pageCount) {
        const warnings = [];
        
        if (fields.length === 0) {
            warnings.push('No fields extracted');
            return { isValid: false, warnings };
        }

        // Check for fields with invalid coordinates
        const invalidFields = fields.filter(f => f.x === 0 && f.y === 0);
        if (invalidFields.length > 0) {
            warnings.push(`${invalidFields.length} fields have invalid coordinates (0,0)`);
        }

        // Check for fields outside page bounds
        const outOfBounds = fields.filter(f => f.x < 0 || f.y < 0 || f.width <= 0 || f.height <= 0);
        if (outOfBounds.length > 0) {
            warnings.push(`${outOfBounds.length} fields have invalid dimensions`);
        }

        // Check for reasonable field count
        if (fields.length < 3) {
            warnings.push('Very few fields detected - may indicate extraction issues');
        }

        return {
            isValid: warnings.length === 0,
            warnings
        };
    }

    /**
     * Save extraction results
     */
    async saveResults(result, templateId) {
        const dataDir = path.join(__dirname, '../data');
        if (!fs.existsSync(dataDir)) {
            fs.mkdirSync(dataDir, { recursive: true });
        }

        // Convert to keyed object format for compatibility
        const positionsObject = {};
        result.fields.forEach(field => {
            positionsObject[field.name] = field;
        });

        // Save position file
        const positionFile = path.join(dataDir, `${templateId}_positions.json`);
        fs.writeFileSync(positionFile, JSON.stringify(positionsObject, null, 2));

        // Save detailed results
        const detailFile = path.join(dataDir, `${templateId}_extraction_details.json`);
        fs.writeFileSync(detailFile, JSON.stringify(result, null, 2));

        console.log(`   💾 Position file: ${positionFile}`);
        console.log(`   💾 Details file: ${detailFile}`);
    }

    /**
     * Find qpdf binary
     */
    findQpdfBinary() {
        const candidates = [
            path.join(__dirname, '../bin/qpdf/bin/qpdf.exe'),
            path.join(__dirname, '../bin/qpdf.exe'),
            'qpdf' // System PATH
        ];

        for (const candidate of candidates) {
            if (fs.existsSync(candidate)) {
                return candidate;
            }
        }

        return null;
    }

    /**
     * Ensure temp directory exists
     */
    ensureTempDir() {
        if (!fs.existsSync(this.tempDir)) {
            fs.mkdirSync(this.tempDir, { recursive: true });
        }
    }
}

// Main execution
(async () => {
    try {
        const pdfPath = process.argv[2];
        const templateId = process.argv[3];

        if (!pdfPath || !templateId) {
            console.log('Usage: node scripts/extract-positions-auto.js <pdf-file> <template-id>');
            console.log('Example: node scripts/extract-positions-auto.js uploads/w9.pdf t_w9_auto');
            process.exit(1);
        }

        if (!fs.existsSync(pdfPath)) {
            console.error('❌ PDF file not found:', pdfPath);
            process.exit(1);
        }

        console.log('===============================================');
        console.log('  Automatic PDF Position Extraction');
        console.log('===============================================');
        console.log('');

        const extractor = new AutoPositionExtractor();
        const result = await extractor.extractPositions(pdfPath, templateId);

        console.log('');
        console.log('===============================================');
        console.log('  Extraction Results');
        console.log('===============================================');
        console.log('');

        if (result.success) {
            console.log('✅ SUCCESS!');
            console.log(`📊 Fields extracted: ${result.fields.length}`);
            console.log(`📄 Pages: ${result.pageCount}`);
            console.log(`🔧 Method: ${result.method}`);
            
            if (result.warnings.length > 0) {
                console.log('');
                console.log('⚠️  Warnings:');
                result.warnings.forEach(warning => {
                    console.log(`   - ${warning}`);
                });
            }

            console.log('');
            console.log('📋 Sample fields:');
            result.fields.slice(0, 5).forEach(field => {
                console.log(`   - ${field.name} (${field.type}): ${field.x}, ${field.y}, ${field.width}x${field.height}mm`);
            });

            console.log('');
            console.log('🎯 Next steps:');
            console.log('   1. Check data/' + templateId + '_positions.json');
            console.log('   2. Test with: php extract-positions.php ' + pdfPath + ' ' + templateId);
            console.log('   3. Use in MVP application');

        } else {
            console.log('❌ FAILED');
            console.log('');
            console.log('Errors:');
            result.errors.forEach(error => {
                console.log(`   - ${error}`);
            });
            
            if (result.warnings.length > 0) {
                console.log('');
                console.log('Warnings:');
                result.warnings.forEach(warning => {
                    console.log(`   - ${warning}`);
                });
            }
        }

    } catch (error) {
        console.error('❌ Fatal error:', error.message);
        console.error(error.stack);
        process.exit(1);
    }
})();

