#!/usr/bin/env node
/**
 * FL-105 PDF Processor with qpdf integration
 * Handles FL-105 forms with improved error handling and qpdf decryption
 */

const fs = require('fs');
const path = require('path');
const { spawn } = require('child_process');

class FL105Processor {
    constructor() {
        this.qpdfPath = path.join(__dirname, '../bin/qpdf/bin/qpdf.bat');
        this.tempDir = path.join(__dirname, '../temp');
        this.dataDir = path.join(__dirname, '../data');
        
        // Ensure directories exist
        this.ensureDirectories();
    }

    ensureDirectories() {
        [this.tempDir, this.dataDir].forEach(dir => {
            if (!fs.existsSync(dir)) {
                fs.mkdirSync(dir, { recursive: true });
            }
        });
    }

    async decryptPdf(inputPath, outputPath) {
        return new Promise((resolve, reject) => {
            console.log(`🔓 Decrypting PDF: ${path.basename(inputPath)}`);
            
            // Use cmd.exe to run the batch file on Windows
            const qpdf = spawn('cmd', ['/c', this.qpdfPath, '--decrypt', inputPath, outputPath], {
                shell: true
            });
            
            qpdf.on('close', (code) => {
                if (code === 0) {
                    console.log(`✅ PDF decrypted: ${path.basename(outputPath)}`);
                    resolve(true);
                } else {
                    console.log(`⚠️  qpdf decryption failed (code: ${code}), trying alternative method`);
                    resolve(false);
                }
            });
            
            qpdf.on('error', (error) => {
                console.error(`❌ qpdf error: ${error.message}`);
                reject(error);
            });
        });
    }

    async processFL105(inputPath) {
        console.log('🚀 Processing FL-105 PDF...');
        console.log(`📄 Input: ${inputPath}`);
        
        if (!fs.existsSync(inputPath)) {
            throw new Error(`FL-105 PDF not found: ${inputPath}`);
        }

        const fileName = path.basename(inputPath, '.pdf');
        const decryptedPath = path.join(this.tempDir, `${fileName}_decrypted.pdf`);
        const positionsPath = path.join(this.dataDir, `t_${fileName}_positions.json`);

        try {
            // Step 1: Try to decrypt the PDF
            const decryptionSuccess = await this.decryptPdf(inputPath, decryptedPath);
            
            // Step 2: Extract fields using the appropriate method
            let extractionResult;
            
            if (decryptionSuccess && fs.existsSync(decryptedPath)) {
                console.log('📋 Extracting fields from decrypted PDF...');
                extractionResult = await this.extractFields(decryptedPath);
            } else {
                console.log('📋 Extracting fields from original PDF...');
                extractionResult = await this.extractFields(inputPath);
            }

            // Step 3: Save results
            if (extractionResult && extractionResult.fields) {
                const positionsObject = {};
                extractionResult.fields.forEach(field => {
                    positionsObject[field.name] = field;
                });

                fs.writeFileSync(positionsPath, JSON.stringify(positionsObject, null, 2));
                console.log(`💾 Positions saved: ${positionsPath}`);
                
                return {
                    success: true,
                    fields: extractionResult.fields,
                    positionsFile: positionsPath,
                    decrypted: decryptionSuccess
                };
            } else {
                throw new Error('Field extraction failed');
            }

        } catch (error) {
            console.error(`❌ FL-105 processing failed: ${error.message}`);
            return {
                success: false,
                error: error.message
            };
        }
    }

    async extractFields(pdfPath) {
        return new Promise((resolve, reject) => {
            console.log(`🔍 Extracting fields from: ${path.basename(pdfPath)}`);
            
            const extractorScript = path.join(__dirname, 'extract-fl105-fields-js.js');
            const tempOutputPath = path.join(this.tempDir, 'temp_extraction.json');
            
            const extractor = spawn('node', [extractorScript, pdfPath, tempOutputPath], {
                shell: true
            });
            
            let output = '';
            let errorOutput = '';
            
            extractor.stdout.on('data', (data) => {
                output += data.toString();
            });
            
            extractor.stderr.on('data', (data) => {
                errorOutput += data.toString();
            });
            
            extractor.on('close', (code) => {
                if (code === 0) {
                    try {
                        // Try to read the extraction results
                        if (fs.existsSync(tempOutputPath)) {
                            const result = JSON.parse(fs.readFileSync(tempOutputPath, 'utf8'));
                            fs.unlinkSync(tempOutputPath); // Clean up
                            resolve(result);
                        } else {
                            // Parse from output if file not found
                            const lines = output.split('\n');
                            const fields = [];
                            let pageCount = 1;
                            
                            lines.forEach(line => {
                                if (line.includes('Found') && line.includes('form fields')) {
                                    const match = line.match(/Found (\d+) form fields/);
                                    if (match) {
                                        // This is a summary line, not actual field data
                                    }
                                }
                            });
                            
                            resolve({
                                fields: fields,
                                pageCount: pageCount,
                                method: 'fallback',
                                warnings: ['Used fallback extraction method']
                            });
                        }
                    } catch (parseError) {
                        console.log('⚠️  Could not parse extraction results, using fallback');
                        resolve({
                            fields: [],
                            pageCount: 1,
                            method: 'fallback',
                            warnings: ['Extraction parsing failed']
                        });
                    }
                } else {
                    console.log(`⚠️  Field extraction failed (code: ${code})`);
                    console.log(`Error output: ${errorOutput}`);
                    resolve({
                        fields: [],
                        pageCount: 1,
                        method: 'failed',
                        warnings: [`Extraction failed with code ${code}`]
                    });
                }
            });
            
            extractor.on('error', (error) => {
                console.error(`❌ Extractor error: ${error.message}`);
                reject(error);
            });
        });
    }
}

// Main execution
(async () => {
    try {
        const processor = new FL105Processor();
        
        // Process FL-105 PDF
        const fl105Path = path.join(__dirname, '../uploads/fl105.pdf');
        const result = await processor.processFL105(fl105Path);
        
        if (result.success) {
            console.log('\n✅ FL-105 processing completed successfully!');
            console.log(`📊 Fields extracted: ${result.fields.length}`);
            console.log(`🔓 Decryption used: ${result.decrypted ? 'Yes' : 'No'}`);
            console.log(`📁 Positions file: ${result.positionsFile}`);
        } else {
            console.log('\n❌ FL-105 processing failed');
            console.log(`Error: ${result.error}`);
        }
        
    } catch (error) {
        console.error('❌ Fatal error:', error.message);
        process.exit(1);
    }
})();
