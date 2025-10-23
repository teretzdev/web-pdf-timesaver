#!/usr/bin/env node
/**
 * Universal PDF Field Position Extractor
 * 5-tier extraction pipeline with intelligent fallback
 * Handles all PDF types: unencrypted, encrypted, corrupted
 */

const fs = require('fs');
const path = require('path');
const { PDFDocument, PDFTextField, PDFCheckBox, PDFRadioGroup, PDFDropdown } = require('pdf-lib');
const { spawn } = require('child_process');

// Import extraction methods
const PdfLibExtractor = require('./methods/pdf-lib-extractor');
const QpdfDecryptExtractor = require('./methods/qpdf-decrypt-extractor');
const PdfJsTextExtractor = require('./methods/pdfjs-text-extractor');
const OcrFieldDetector = require('./methods/ocr-field-detector');
const CoordinateValidator = require('./utils/coordinate-validator');

class UniversalFieldExtractor {
    constructor() {
        this.methods = [
            new PdfLibExtractor(),
            new QpdfDecryptExtractor(),
            new PdfJsTextExtractor(),
            new OcrFieldDetector()
        ];
        this.validator = new CoordinateValidator();
        this.tempDir = path.join(__dirname, '../temp');
        this.dataDir = path.join(__dirname, '../data');
        this.ensureDirectories();
    }

    /**
     * Main extraction pipeline - tries all methods until one succeeds
     */
    async extractPositions(pdfPath, templateId) {
        console.log('🚀 Universal PDF Field Position Extractor');
        console.log('==========================================');
        console.log(`📄 PDF: ${pdfPath}`);
        console.log(`🏷️  Template: ${templateId}`);
        console.log('');

        const result = {
            success: false,
            method: 'none',
            fields: [],
            pageCount: 0,
            warnings: [],
            errors: [],
            attempts: [],
            extractedAt: new Date().toISOString(),
            source: path.basename(pdfPath)
        };

        try {
            // Validate input
            if (!fs.existsSync(pdfPath)) {
                throw new Error(`PDF file not found: ${pdfPath}`);
            }

            // Try each extraction method in order
            for (let i = 0; i < this.methods.length; i++) {
                const method = this.methods[i];
                const methodName = method.getName();
                
                console.log(`🔍 Method ${i + 1}: ${methodName}`);
                
                try {
                    const methodResult = await method.extract(pdfPath);
                    result.attempts.push({
                        method: methodName,
                        success: methodResult.success,
                        fields: methodResult.fields?.length || 0,
                        error: methodResult.error || null
                    });

                    if (methodResult.success && methodResult.fields.length > 0) {
                        console.log(`✅ ${methodName} succeeded: ${methodResult.fields.length} fields`);
                        
                        // Validate extracted fields
                        const validation = this.validator.validateFields(methodResult.fields, methodResult.pageCount);
                        
                        if (validation.isValid || validation.warnings.length === 0) {
                            result.success = true;
                            result.method = methodName;
                            result.fields = methodResult.fields;
                            result.pageCount = methodResult.pageCount;
                            result.warnings.push(...validation.warnings);
                            
                            console.log(`🎯 Extraction successful using ${methodName}`);
                            break;
                        } else {
                            console.log(`⚠️  ${methodName} succeeded but validation failed`);
                            result.warnings.push(`Method ${methodName} produced invalid coordinates`);
                        }
                    } else {
                        console.log(`❌ ${methodName} failed: ${methodResult.error || 'No fields extracted'}`);
                    }
                    
                } catch (error) {
                    console.log(`❌ ${methodName} error: ${error.message}`);
                    result.attempts.push({
                        method: methodName,
                        success: false,
                        fields: 0,
                        error: error.message
                    });
                }
                
                console.log('');
            }

            // If all methods failed, provide manual tool option
            if (!result.success) {
                result.errors.push('All automated extraction methods failed');
                result.warnings.push('Consider using manual position mapper tool');
                console.log('❌ All extraction methods failed');
                console.log('💡 Try manual tool: manual-position-mapper.html');
            }

            // Save results if successful
            if (result.success) {
                await this.saveResults(result, templateId);
                console.log('💾 Results saved successfully');
            }

        } catch (error) {
            console.error('❌ Fatal error:', error.message);
            result.errors.push(error.message);
        }

        return result;
    }

    /**
     * Save extraction results to files
     */
    async saveResults(result, templateId) {
        // Convert to keyed object format for compatibility
        const positionsObject = {};
        result.fields.forEach(field => {
            positionsObject[field.name] = field;
        });

        // Save position file
        const positionFile = path.join(this.dataDir, `${templateId}_positions.json`);
        fs.writeFileSync(positionFile, JSON.stringify(positionsObject, null, 2));

        // Save detailed results
        const detailFile = path.join(this.dataDir, `${templateId}_extraction_details.json`);
        fs.writeFileSync(detailFile, JSON.stringify(result, null, 2));

        console.log(`📁 Position file: ${positionFile}`);
        console.log(`📁 Details file: ${detailFile}`);
    }

    /**
     * Get extraction status for all methods
     */
    getStatus() {
        const status = {
            methods: [],
            overall: {
                available: 0,
                total: this.methods.length
            }
        };

        for (const method of this.methods) {
            const methodStatus = method.getStatus();
            status.methods.push(methodStatus);
            if (methodStatus.available) {
                status.overall.available++;
            }
        }

        return status;
    }

    /**
     * Ensure required directories exist
     */
    ensureDirectories() {
        if (!fs.existsSync(this.tempDir)) {
            fs.mkdirSync(this.tempDir, { recursive: true });
        }
        if (!fs.existsSync(this.dataDir)) {
            fs.mkdirSync(this.dataDir, { recursive: true });
        }
    }
}

// Main execution
(async () => {
    try {
        const pdfPath = process.argv[2];
        const templateId = process.argv[3];

        if (!pdfPath || !templateId) {
            console.log('Usage: node scripts/universal-field-extractor.js <pdf-file> <template-id>');
            console.log('Example: node scripts/universal-field-extractor.js uploads/w9.pdf t_w9_universal');
            console.log('');
            console.log('This tool tries multiple extraction methods:');
            console.log('1. pdf-lib direct extraction (fastest, works for unencrypted PDFs)');
            console.log('2. qpdf decryption + pdf-lib (handles encrypted PDFs)');
            console.log('3. PDF.js text layer analysis (fallback for field detection)');
            console.log('4. OCR visual detection (last resort for corrupted PDFs)');
            console.log('5. Manual tool (interactive positioning)');
            process.exit(1);
        }

        const extractor = new UniversalFieldExtractor();
        const result = await extractor.extractPositions(pdfPath, templateId);

        console.log('');
        console.log('==========================================');
        console.log('  Extraction Results');
        console.log('==========================================');
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
            console.log('Attempted methods:');
            result.attempts.forEach(attempt => {
                const status = attempt.success ? '✅' : '❌';
                console.log(`   ${status} ${attempt.method}: ${attempt.fields} fields ${attempt.error ? `(${attempt.error})` : ''}`);
            });
            
            if (result.errors.length > 0) {
                console.log('');
                console.log('Errors:');
                result.errors.forEach(error => {
                    console.log(`   - ${error}`);
                });
            }
            
            if (result.warnings.length > 0) {
                console.log('');
                console.log('Warnings:');
                result.warnings.forEach(warning => {
                    console.log(`   - ${warning}`);
                });
            }

            console.log('');
            console.log('💡 Try manual positioning: manual-position-mapper.html');
        }

    } catch (error) {
        console.error('❌ Fatal error:', error.message);
        console.error(error.stack);
        process.exit(1);
    }
})();

module.exports = UniversalFieldExtractor;
