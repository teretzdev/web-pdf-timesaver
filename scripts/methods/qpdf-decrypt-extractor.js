/**
 * Method 2: qpdf Decryption + pdf-lib Extraction
 * Handles encrypted PDFs by decrypting them first
 */

const fs = require('fs');
const path = require('path');
const { spawn } = require('child_process');
const PdfLibExtractor = require('./pdf-lib-extractor');

class QpdfDecryptExtractor {
    constructor() {
        this.name = 'qpdf-decrypt-pdf-lib';
        this.qpdfPath = this.findQpdfBinary();
        this.tempDir = path.join(__dirname, '../../temp');
        this.pdfLibExtractor = new PdfLibExtractor();
        this.commonPasswords = ['', 'password', '123456', 'admin', 'user', 'test'];
    }

    getName() {
        return this.name;
    }

    getStatus() {
        return {
            name: this.name,
            available: !!this.qpdfPath,
            description: 'Decrypt encrypted PDFs using qpdf, then extract with pdf-lib',
            requirements: ['qpdf binary', 'pdf-lib library'],
            qpdfPath: this.qpdfPath
        };
    }

    async extract(pdfPath) {
        if (!this.qpdfPath) {
            return {
                success: false,
                fields: [],
                pageCount: 0,
                error: 'qpdf not available'
            };
        }

        try {
            console.log('   🔓 Attempting PDF decryption...');
            
            // Try common passwords
            for (const password of this.commonPasswords) {
                console.log(`   🔑 Trying password: "${password || '(empty)'}"`);
                
                const tempPdf = path.join(this.tempDir, `decrypted_${Date.now()}.pdf`);
                const success = await this.decryptPdf(pdfPath, tempPdf, password);
                
                if (success) {
                    console.log('   ✅ Decryption successful');
                    
                    try {
                        // Extract fields from decrypted PDF
                        const extractResult = await this.pdfLibExtractor.extract(tempPdf);
                        
                        // Cleanup temp file
                        if (fs.existsSync(tempPdf)) {
                            fs.unlinkSync(tempPdf);
                        }
                        
                        if (extractResult.success) {
                            // Mark fields as extracted via decryption
                            extractResult.fields.forEach(field => {
                                field.method = this.name;
                                field.confidence = 0.85;
                                field.decrypted = true;
                            });
                            
                            return extractResult;
                        }
                    } catch (error) {
                        console.log(`   ⚠️  Extraction from decrypted PDF failed: ${error.message}`);
                    }
                }
            }

            return {
                success: false,
                fields: [],
                pageCount: 0,
                error: 'All password attempts failed'
            };

        } catch (error) {
            console.log(`   ❌ qpdf decryption failed: ${error.message}`);
            return {
                success: false,
                fields: [],
                pageCount: 0,
                error: error.message
            };
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
     * Find qpdf binary
     */
    findQpdfBinary() {
        const candidates = [
            path.join(__dirname, '../../bin/qpdf/bin/qpdf.exe'),
            path.join(__dirname, '../../bin/qpdf.exe'),
            'qpdf' // System PATH
        ];

        for (const candidate of candidates) {
            if (fs.existsSync(candidate)) {
                return candidate;
            }
        }

        return null;
    }
}

module.exports = QpdfDecryptExtractor;
