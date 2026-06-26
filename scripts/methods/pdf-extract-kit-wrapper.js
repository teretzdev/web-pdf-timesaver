/**
 * PDF-Extract-Kit bridge wrapper
 * Executes a Python helper that prefers the official toolkit if present, otherwise
 * uses vector + text heuristics to approximate layout-driven detection.
 */

const { spawn } = require('child_process');
const path = require('path');
const fs = require('fs');

class PdfExtractKitWrapper {
    constructor() {
        this.name = 'pdf-extract-kit-wrapper';
        this.pythonCommand = this.findPython();
        this.scriptPath = path.join(__dirname, '../utils/pdf_extract_kit_bridge.py');
        this.available = Boolean(this.pythonCommand) && fs.existsSync(this.scriptPath);
    }

    getName() {
        return this.name;
    }

    getStatus() {
        return {
            name: this.name,
            available: this.available,
            description: 'PDF-Extract-Kit bridge (Python) – uses official toolkit when installed',
            requirements: [
                'Python 3.8+',
                'PyMuPDF (pip install pymupdf)',
                'Optional: pdf-extract-kit (pip install pdf-extract-kit)',
                'Optional: pdf2image, Pillow for raster heuristics'
            ]
        };
    }

    findPython() {
        const { spawnSync } = require('child_process');
        const candidates = ['python', 'python3'];
        for (const candidate of candidates) {
            try {
                const res = spawnSync(candidate, ['--version'], { stdio: 'ignore' });
                if (res.status === 0) {
                    return candidate;
                }
            } catch {
                // continue scanning
            }
        }
        return null;
    }

    async extract(pdfPath) {
        if (!this.available) {
            return {
                success: false,
                fields: [],
                pageCount: 0,
                error: 'Python or pdf-extract-kit bridge unavailable'
            };
        }

        return new Promise((resolve) => {
            const args = [this.scriptPath, pdfPath];
            const child = spawn(this.pythonCommand, args);

            let output = '';
            let errorOutput = '';

            child.stdout.on('data', (data) => {
                output += data.toString();
            });

            child.stderr.on('data', (data) => {
                errorOutput += data.toString();
            });

            child.on('close', (code) => {
                if (code !== 0) {
                    return resolve({
                        success: false,
                        fields: [],
                        pageCount: 0,
                        error: errorOutput || `pdf-extract-kit helper exited with code ${code}`
                    });
                }

                try {
                    const result = JSON.parse(output);
                    resolve({
                        success: Boolean(result.success),
                        fields: Array.isArray(result.fields) ? result.fields : [],
                        pageCount: result.pageCount || 0,
                        error: result.error || null,
                        meta: result.meta || {}
                    });
                } catch (err) {
                    resolve({
                        success: false,
                        fields: [],
                        pageCount: 0,
                        error: `pdf-extract-kit bridge parse error: ${err.message}`
                    });
                }
            });
        });
    }
}

module.exports = PdfExtractKitWrapper;


