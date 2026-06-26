/**
 * FFDNet / CommonForms inspired detector
 * Bridges to a Python helper that can take advantage of deep models when available.
 * Falls back to analytical heuristics when the environment lacks trained weights.
 */

const { spawn } = require('child_process');
const path = require('path');
const fs = require('fs');

class FfdnetDetector {
    constructor() {
        this.name = 'ffdnet-detector';
        this.pythonCommand = this.findPython();
        this.scriptPath = path.join(__dirname, '../utils/ffdnet_field_detector.py');
        this.available = Boolean(this.pythonCommand) && fs.existsSync(this.scriptPath);
    }

    getName() {
        return this.name;
    }

    getStatus() {
        return {
            name: this.name,
            available: this.available,
            description: 'FFDNet/CommonForms prototype detector (Python torch bridge)',
            requirements: [
                'Python 3.8+',
                'PyMuPDF (pip install pymupdf)',
                'PyTorch (pip install torch)',
                'Optional: FFDNet weights (set FFDNET_WEIGHTS=/path/to/model.pt)'
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
                // continue
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
                error: 'Python or helper script unavailable'
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
                        error: errorOutput || `Python exited with code ${code}`
                    });
                }

                try {
                    const result = JSON.parse(output);
                    if (!result || typeof result !== 'object') {
                        throw new Error('Empty response from FFDNet detector');
                    }
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
                        error: `FFDNet detector parse error: ${err.message}`
                    });
                }
            });
        });
    }
}

module.exports = FfdnetDetector;


