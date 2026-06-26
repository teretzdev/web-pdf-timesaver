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
            // Ensure temp directory exists
            const tempDir = path.dirname(outputPath);
            if (!fs.existsSync(tempDir)) {
                fs.mkdirSync(tempDir, { recursive: true });
            }

            const args = ['--decrypt'];
            if (password) {
                args.push(`--password=${password}`);
            }
            args.push(inputPath, outputPath);

            let errorOutput = '';
            // Use execFile for Windows paths with spaces - it handles paths properly
            const { execFile } = require('child_process');
            execFile(this.qpdfPath, args, { 
                shell: false,
                windowsVerbatimArguments: false
            }, (error, stdout, stderr) => {
                if (error) {
                    errorOutput = stderr || error.message || '';
                    if (errorOutput) {
                        console.log(`   ⚠️  qpdf error: ${errorOutput.trim()}`);
                    }
                    resolve(false);
                } else {
                    // Check if output file was created
                    if (fs.existsSync(outputPath)) {
                        resolve(true);
                    } else {
                        console.log(`   ⚠️  qpdf completed but output file not found: ${outputPath}`);
                        resolve(false);
                    }
                }
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
            'qpdf', // System PATH
            'qpdf.exe' // Windows PATH
        ];

        // First check if file exists
        for (const candidate of candidates) {
            if (fs.existsSync(candidate)) {
                console.log(`   ✅ Found qpdf at: ${candidate}`);
                return candidate;
            }
        }

        // If not found by path, try to find it in system PATH
        const { execSync } = require('child_process');
        try {
            // Try to find qpdf in PATH
            const result = execSync('where qpdf 2>nul || which qpdf 2>/dev/null', { encoding: 'utf8', timeout: 2000 });
            const qpdfPath = result.trim().split('\n')[0];
            if (qpdfPath && fs.existsSync(qpdfPath)) {
                console.log(`   ✅ Found qpdf in PATH: ${qpdfPath}`);
                return qpdfPath;
            }
        } catch (e) {
            // qpdf not in PATH
        }

        console.log('   ⚠️  qpdf not found - will skip decryption method');
        return null;
    }
}

module.exports = QpdfDecryptExtractor;
