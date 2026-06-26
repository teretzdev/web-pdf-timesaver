#!/usr/bin/env node
/**
 * FL-105 PDF Processor with qpdf integration and Progress Indicators
 * Handles FL-105 forms with improved error handling, qpdf decryption, and visual feedback
 */

const fs = require('fs');
const path = require('path');
const { spawn } = require('child_process');

// Activity spinner for long operations
class ActivitySpinner {
    constructor(label = 'Processing') {
        this.label = label;
        this.spinner = ['⠋', '⠙', '⠹', '⠸', '⠼', '⠴', '⠦', '⠧', '⠇', '⠏'];
        this.current = 0;
        this.interval = null;
    }

    start() {
        this.interval = setInterval(() => {
            process.stdout.write(`\r${this.spinner[this.current]} ${this.label}`);
            this.current = (this.current + 1) % this.spinner.length;
        }, 100);
    }

    stop(message = 'Done') {
        if (this.interval) {
            clearInterval(this.interval);
            process.stdout.write(`\r✅ ${message}\n`);
        }
    }
}

class FL105Processor {
    constructor() {
        this.qpdfPath = path.join(__dirname, '../bin/qpdf/bin/qpdf.bat');
        this.tempDir = path.join(__dirname, '../temp');
        this.dataDir = path.join(__dirname, '../data');
        
        // Ensure directories exist
        this.ensureDirectories();
    }

    ensureDirectories() {
        console.log('📁 Checking directories...');
        [this.tempDir, this.dataDir].forEach(dir => {
            if (!fs.existsSync(dir)) {
                fs.mkdirSync(dir, { recursive: true });
                console.log(`   Created: ${path.basename(dir)}`);
            }
        });
    }

    async decryptPdf(inputPath, outputPath) {
        return new Promise((resolve, reject) => {
            console.log(`🔓 Starting PDF decryption...`);
            console.log(`   Input: ${path.basename(inputPath)}`);
            console.log(`   Output: ${path.basename(outputPath)}`);
            
            const spinner = new ActivitySpinner('Decrypting PDF with qpdf');
            spinner.start();
            
            // Use cmd.exe to run the batch file on Windows
            const qpdf = spawn('cmd', ['/c', this.qpdfPath, '--decrypt', inputPath, outputPath], {
                shell: true
            });
            
            qpdf.on('close', (code) => {
                if (code === 0) {
                    spinner.stop('PDF decrypted successfully');
                    resolve(true);
                } else {
                    spinner.stop('qpdf decryption failed');
                    console.log(`⚠️  qpdf failed (exit code: ${code}), trying alternative method`);
                    resolve(false);
                }
            });
            
            qpdf.on('error', (error) => {
                spinner.stop('qpdf error occurred');
                console.error(`❌ qpdf error: ${error.message}`);
                reject(error);
            });
        });
    }

    async processFL105(inputPath) {
        console.log('🚀 Starting FL-105 PDF Processing...');
        console.log(`📄 Input file: ${path.basename(inputPath)}`);
        console.log(`📁 Working directory: ${this.tempDir}`);
        
        if (!fs.existsSync(inputPath)) {
            throw new Error(`FL-105 PDF not found: ${inputPath}`);
        }

        const fileName = path.basename(inputPath, '.pdf');
        const decryptedPath = path.join(this.tempDir, `${fileName}_decrypted.pdf`);
        const positionsPath = path.join(this.dataDir, `t_${fileName}_positions.json`);

        try {
            // Step 1: Try to decrypt the PDF
            console.log('\n🔓 Step 1: PDF Decryption');
            const decryptionSuccess = await this.decryptPdf(inputPath, decryptedPath);
            
            // Step 2: Extract fields using the appropriate method
            console.log('\n📋 Step 2: Field Extraction');
            let extractionResult;
            
            if (decryptionSuccess && fs.existsSync(decryptedPath)) {
                console.log('   Using decrypted PDF for field extraction...');
                extractionResult = await this.extractFields(decryptedPath);
            } else {
                console.log('   Using original PDF for field extraction...');
                extractionResult = await this.extractFields(inputPath);
            }

            // Step 3: Save results
            console.log('\n💾 Step 3: Saving Results');
            if (extractionResult && extractionResult.fields) {
                const saveSpinner = new ActivitySpinner('Saving field positions');
                saveSpinner.start();
                
                const positionsObject = {};
                extractionResult.fields.forEach(field => {
                    positionsObject[field.name] = field;
                });

                fs.writeFileSync(positionsPath, JSON.stringify(positionsObject, null, 2));
                saveSpinner.stop('Field positions saved');
                
                console.log(`📁 Positions file: ${positionsPath}`);
                
                return {
                    success: true,
                    fields: extractionResult.fields,
                    positionsFile: positionsPath,
                    decrypted: decryptionSuccess
                };
            } else {
                throw new Error('Field extraction failed - no fields found');
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
                const text = data.toString();
                output += text;
                // Show progress from the extractor script
                if (text.includes('Processing field') || text.includes('Complete')) {
                    process.stdout.write(text);
                }
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
                            
                            // Extract field count from output
                            const fieldCountMatch = output.match(/Total fields extracted: (\d+)/);
                            if (fieldCountMatch) {
                                console.log(`📊 Extracted ${fieldCountMatch[1]} fields`);
                            }
                            
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
                    console.log(`⚠️  Field extraction failed (exit code: ${code})`);
                    if (errorOutput) {
                        console.log(`Error details: ${errorOutput}`);
                    }
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
        console.log('=== FL-105 PDF Processor with qpdf Integration ===\n');
        
        const processor = new FL105Processor();
        
        // Process FL-105 PDF
        const fl105Path = path.join(__dirname, '../uploads/fl105.pdf');
        
        if (!fs.existsSync(fl105Path)) {
            console.error(`❌ FL-105 PDF not found: ${fl105Path}`);
            console.log('Please ensure fl105.pdf is in the uploads directory');
            process.exit(1);
        }
        
        const result = await processor.processFL105(fl105Path);
        
        console.log('\n📊 Final Results:');
        if (result.success) {
            console.log('✅ FL-105 processing completed successfully!');
            console.log(`📊 Fields extracted: ${result.fields.length}`);
            console.log(`🔓 Decryption used: ${result.decrypted ? 'Yes' : 'No'}`);
            console.log(`📁 Positions file: ${result.positionsFile}`);
            
            // Show sample fields
            if (result.fields.length > 0) {
                console.log('\n📋 Sample extracted fields:');
                result.fields.slice(0, 3).forEach(field => {
                    const status = field.positionValid ? '✅' : '⚠️';
                    console.log(`   ${status} ${field.name} (${field.type})`);
                });
                if (result.fields.length > 3) {
                    console.log(`   ... and ${result.fields.length - 3} more fields`);
                }
            }
            
            console.log('\n🎯 Next steps:');
            console.log('   1. Copy positions to XAMPP: Copy-Item "' + result.positionsFile + '" "C:\\xampp\\htdocs\\Web-PDFTimeSaver\\data\\"');
            console.log('   2. Test in browser: Navigate to populate form');
            console.log('   3. Verify all fields are available in the dynamic template');
            
        } else {
            console.log('❌ FL-105 processing failed');
            console.log(`Error: ${result.error}`);
        }
        
    } catch (error) {
        console.error('❌ Fatal error:', error.message);
        process.exit(1);
    }
})();