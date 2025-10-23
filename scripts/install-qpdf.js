#!/usr/bin/env node
/**
 * qpdf Installer for Windows
 * Downloads and installs qpdf binaries for PDF decryption
 */

const fs = require('fs');
const path = require('path');
const https = require('https');
const { execSync } = require('child_process');

class QpdfInstaller {
    constructor() {
        this.baseDir = path.join(__dirname, '..');
        this.qpdfDir = path.join(this.baseDir, 'bin', 'qpdf');
        this.qpdfBinDir = path.join(this.qpdfDir, 'bin');
        this.qpdfVersion = '11.6.3';
        this.qpdfUrl = `https://github.com/qpdf/qpdf/releases/download/v${this.qpdfVersion}/qpdf-${this.qpdfVersion}-bin-mingw64.zip`;
    }

    async install() {
        console.log('🔧 Installing qpdf for PDF decryption...');
        console.log(`📦 Version: ${this.qpdfVersion}`);
        console.log(`📁 Target: ${this.qpdfDir}`);
        console.log('');

        try {
            // Step 1: Create directories
            await this.createDirectories();
            
            // Step 2: Check if already installed
            if (await this.isInstalled()) {
                console.log('✅ qpdf already installed');
                return true;
            }

            // Step 3: Download qpdf
            console.log('📥 Downloading qpdf...');
            const zipPath = await this.downloadQpdf();
            
            // Step 4: Extract
            console.log('📂 Extracting qpdf...');
            await this.extractQpdf(zipPath);
            
            // Step 5: Verify installation
            if (await this.isInstalled()) {
                console.log('✅ qpdf installed successfully!');
                console.log(`📍 Location: ${this.qpdfBinDir}`);
                return true;
            } else {
                console.log('❌ Installation verification failed');
                return false;
            }

        } catch (error) {
            console.error('❌ Installation failed:', error.message);
            return false;
        }
    }

    async createDirectories() {
        if (!fs.existsSync(this.qpdfDir)) {
            fs.mkdirSync(this.qpdfDir, { recursive: true });
        }
        if (!fs.existsSync(this.qpdfBinDir)) {
            fs.mkdirSync(this.qpdfBinDir, { recursive: true });
        }
    }

    async isInstalled() {
        const qpdfExe = path.join(this.qpdfBinDir, 'qpdf.exe');
        if (!fs.existsSync(qpdfExe)) {
            return false;
        }

        try {
            // Test qpdf by running --version
            const output = execSync(`"${qpdfExe}" --version`, { 
                encoding: 'utf8',
                timeout: 5000 
            });
            return output.includes('qpdf version');
        } catch (error) {
            return false;
        }
    }

    async downloadQpdf() {
        const zipPath = path.join(this.qpdfDir, `qpdf-${this.qpdfVersion}.zip`);
        
        return new Promise((resolve, reject) => {
            const file = fs.createWriteStream(zipPath);
            
            https.get(this.qpdfUrl, (response) => {
                if (response.statusCode !== 200) {
                    reject(new Error(`Download failed: ${response.statusCode}`));
                    return;
                }
                
                response.pipe(file);
                
                file.on('finish', () => {
                    file.close();
                    resolve(zipPath);
                });
                
                file.on('error', (err) => {
                    fs.unlink(zipPath, () => {}); // Delete partial file
                    reject(err);
                });
            }).on('error', reject);
        });
    }

    async extractQpdf(zipPath) {
        // Use PowerShell to extract ZIP (built into Windows)
        const extractCommand = `powershell -Command "Expand-Archive -Path '${zipPath}' -DestinationPath '${this.qpdfDir}' -Force"`;
        
        try {
            execSync(extractCommand, { stdio: 'pipe' });
            
            // Move files from extracted folder to bin directory
            const extractedFolder = path.join(this.qpdfDir, `qpdf-${this.qpdfVersion}-bin-mingw64`);
            if (fs.existsSync(extractedFolder)) {
                // Copy qpdf.exe to bin directory
                const sourceExe = path.join(extractedFolder, 'bin', 'qpdf.exe');
                const targetExe = path.join(this.qpdfBinDir, 'qpdf.exe');
                
                if (fs.existsSync(sourceExe)) {
                    fs.copyFileSync(sourceExe, targetExe);
                }
                
                // Copy DLLs if they exist
                const dllFiles = fs.readdirSync(path.join(extractedFolder, 'bin'))
                    .filter(file => file.endsWith('.dll'));
                
                for (const dll of dllFiles) {
                    const sourceDll = path.join(extractedFolder, 'bin', dll);
                    const targetDll = path.join(this.qpdfBinDir, dll);
                    fs.copyFileSync(sourceDll, targetDll);
                }
                
                // Clean up extracted folder
                fs.rmSync(extractedFolder, { recursive: true, force: true });
            }
            
            // Clean up zip file
            fs.unlinkSync(zipPath);
            
        } catch (error) {
            throw new Error(`Extraction failed: ${error.message}`);
        }
    }

    getQpdfPath() {
        return path.join(this.qpdfBinDir, 'qpdf.exe');
    }
}

// Main execution
(async () => {
    try {
        const installer = new QpdfInstaller();
        const success = await installer.install();
        
        if (success) {
            console.log('');
            console.log('🎉 qpdf installation complete!');
            console.log('');
            console.log('Next steps:');
            console.log('1. Test: node scripts/universal-field-extractor.js uploads/w9.pdf test_w9');
            console.log('2. Try encrypted PDF: node scripts/universal-field-extractor.js uploads/fl100.pdf test_fl100');
            console.log('');
            process.exit(0);
        } else {
            console.log('');
            console.log('❌ qpdf installation failed');
            console.log('');
            console.log('Manual installation:');
            console.log('1. Download from: https://github.com/qpdf/qpdf/releases');
            console.log('2. Extract to: bin/qpdf/bin/qpdf.exe');
            console.log('');
            process.exit(1);
        }
        
    } catch (error) {
        console.error('❌ Fatal error:', error.message);
        process.exit(1);
    }
})();
