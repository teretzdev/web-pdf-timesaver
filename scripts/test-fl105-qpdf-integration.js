#!/usr/bin/env node
/**
 * FL-105 Complete Test Suite with qpdf Integration
 * Tests FL-105 form processing with qpdf decryption
 */

const fs = require('fs');
const path = require('path');
const { spawn } = require('child_process');

class FL105TestSuite {
    constructor() {
        this.qpdfPath = path.join(__dirname, '../bin/qpdf/bin/qpdf.bat');
        this.fl105Path = path.join(__dirname, '../uploads/fl105.pdf');
        this.tempDir = path.join(__dirname, '../temp');
        this.dataDir = path.join(__dirname, '../data');
        
        this.ensureDirectories();
    }

    ensureDirectories() {
        [this.tempDir, this.dataDir].forEach(dir => {
            if (!fs.existsSync(dir)) {
                fs.mkdirSync(dir, { recursive: true });
            }
        });
    }

    async runTest(testName, testFunction) {
        console.log(`\n🧪 Running test: ${testName}`);
        console.log('='.repeat(50));
        
        try {
            const result = await testFunction();
            console.log(`✅ ${testName}: PASSED`);
            return result;
        } catch (error) {
            console.log(`❌ ${testName}: FAILED`);
            console.log(`   Error: ${error.message}`);
            return null;
        }
    }

    async testQpdfInstallation() {
        return new Promise((resolve, reject) => {
            if (!fs.existsSync(this.qpdfPath)) {
                throw new Error('qpdf not found at expected location');
            }

            const qpdf = spawn('cmd', ['/c', `"${this.qpdfPath}"`, '--version'], { shell: true });
            
            let output = '';
            qpdf.stdout.on('data', (data) => output += data.toString());
            qpdf.stderr.on('data', (data) => output += data.toString());
            
            qpdf.on('close', (code) => {
                if (code === 0 || output.includes('qpdf version')) {
                    resolve('qpdf is installed and working');
                } else {
                    reject(new Error(`qpdf version check failed with code ${code}`));
                }
            });
            
            qpdf.on('error', reject);
        });
    }

    async testFL105Decryption() {
        return new Promise((resolve, reject) => {
            if (!fs.existsSync(this.fl105Path)) {
                throw new Error('FL-105 PDF not found');
            }

            const decryptedPath = path.join(this.tempDir, 'fl105_test_decrypted.pdf');
            const qpdf = spawn('cmd', ['/c', `"${this.qpdfPath}"`, '--decrypt', `"${this.fl105Path}"`, `"${decryptedPath}"`], { shell: true });
            
            let output = '';
            qpdf.stdout.on('data', (data) => output += data.toString());
            qpdf.stderr.on('data', (data) => output += data.toString());
            
            qpdf.on('close', (code) => {
                if (code === 0 && fs.existsSync(decryptedPath)) {
                    const stats = fs.statSync(decryptedPath);
                    resolve(`FL-105 decrypted successfully (${(stats.size / 1024).toFixed(1)} KB)`);
                } else {
                    reject(new Error('FL-105 decryption failed'));
                }
            });
            
            qpdf.on('error', reject);
        });
    }

    async testFL105FieldExtraction() {
        return new Promise((resolve, reject) => {
            const extractorScript = path.join(__dirname, 'extract-fl105-fields-js.js');
            const outputPath = path.join(this.dataDir, 't_fl105_test_extraction.json');
            
            const extractor = spawn('node', [extractorScript, this.fl105Path, outputPath], { shell: true });
            
            let output = '';
            extractor.stdout.on('data', (data) => output += data.toString());
            extractor.stderr.on('data', (data) => output += data.toString());
            
            extractor.on('close', (code) => {
                if (code === 0) {
                    resolve('Field extraction completed (check output for details)');
                } else {
                    reject(new Error(`Field extraction failed with code ${code}`));
                }
            });
            
            extractor.on('error', reject);
        });
    }

    async testFL105FormFilling() {
        // Test the PHP form filler with qpdf integration
        return new Promise((resolve, reject) => {
            const testData = {
                'attorney_name': 'John Doe',
                'attorney_firm': 'Doe & Associates',
                'attorney_bar': '123456',
                'petitioner_name': 'Jane Smith',
                'respondent_name': 'Bob Smith'
            };

            const phpScript = path.join(__dirname, '../fill-fl105-form.php');
            const php = spawn('php', [phpScript], { shell: true });
            
            // Send test data to PHP script
            php.stdin.write(JSON.stringify(testData));
            php.stdin.end();
            
            let output = '';
            php.stdout.on('data', (data) => output += data.toString());
            php.stderr.on('data', (data) => output += data.toString());
            
            php.on('close', (code) => {
                if (code === 0) {
                    resolve('FL-105 form filling test completed');
                } else {
                    reject(new Error(`Form filling failed with code ${code}`));
                }
            });
            
            php.on('error', reject);
        });
    }

    async runAllTests() {
        console.log('🚀 FL-105 Test Suite with qpdf Integration');
        console.log('='.repeat(60));
        
        const results = {};
        
        // Test 1: qpdf Installation
        results.qpdfInstallation = await this.runTest('qpdf Installation', () => this.testQpdfInstallation());
        
        // Test 2: FL-105 Decryption
        results.fl105Decryption = await this.runTest('FL-105 Decryption', () => this.testFL105Decryption());
        
        // Test 3: Field Extraction
        results.fieldExtraction = await this.runTest('Field Extraction', () => this.testFL105FieldExtraction());
        
        // Test 4: Form Filling
        results.formFilling = await this.runTest('Form Filling', () => this.testFL105FormFilling());
        
        // Summary
        console.log('\n📊 Test Summary');
        console.log('='.repeat(30));
        
        const passedTests = Object.values(results).filter(result => result !== null).length;
        const totalTests = Object.keys(results).length;
        
        console.log(`✅ Passed: ${passedTests}/${totalTests}`);
        console.log(`❌ Failed: ${totalTests - passedTests}/${totalTests}`);
        
        if (passedTests === totalTests) {
            console.log('\n🎉 All tests passed! FL-105 with qpdf integration is working correctly.');
        } else {
            console.log('\n⚠️  Some tests failed. Check the output above for details.');
        }
        
        return results;
    }
}

// Main execution
(async () => {
    try {
        const testSuite = new FL105TestSuite();
        await testSuite.runAllTests();
    } catch (error) {
        console.error('❌ Test suite failed:', error.message);
        process.exit(1);
    }
})();
