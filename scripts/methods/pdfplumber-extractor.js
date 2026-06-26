/**
 * pdfplumber Field Extractor
 * Uses pdfplumber Python library for form field extraction
 * Note: This requires Python with pdfplumber installed
 */

const { spawn } = require('child_process');
const path = require('path');
const fs = require('fs');
const fieldMetrics = require('../utils/field-metrics');

class PdfPlumberExtractor {
    constructor() {
        this.name = 'pdfplumber-extractor';
        this.mmPerPoint = fieldMetrics.MM_PER_PT;
        this.pythonAvailable = this.checkPythonAvailability();
    }

    getName() {
        return this.name;
    }

    getStatus() {
        return {
            name: this.name,
            available: this.pythonAvailable,
            description: 'pdfplumber form field extraction via Python',
            requirements: ['Python 3.7+', 'pdfplumber (pip install pdfplumber)']
        };
    }

    checkPythonAvailability() {
        try {
            const { execSync } = require('child_process');
            try {
                execSync('python --version', { stdio: 'ignore' });
                return true;
            } catch {
                try {
                    execSync('python3 --version', { stdio: 'ignore' });
                    return true;
                } catch {
                    return false;
                }
            }
        } catch {
            return false;
        }
    }

    async extract(pdfPath) {
        if (!this.pythonAvailable) {
            return {
                success: false,
                fields: [],
                pageCount: 0,
                error: 'Python not available'
            };
        }

        try {
            console.log('   📄 Starting pdfplumber extraction...');
            
            const pythonScript = this.createExtractionScript(pdfPath);
            const scriptPath = path.join(__dirname, '../../temp/pdfplumber_extract.py');
            
            const tempDir = path.dirname(scriptPath);
            if (!fs.existsSync(tempDir)) {
                fs.mkdirSync(tempDir, { recursive: true });
            }
            
            fs.writeFileSync(scriptPath, pythonScript);
            
            const result = await this.runPythonScript(scriptPath, pdfPath);
            
            if (fs.existsSync(scriptPath)) {
                fs.unlinkSync(scriptPath);
            }
            
            if (result.success) {
                console.log(`   ✅ pdfplumber extracted ${result.fields.length} fields`);
            } else {
                console.log(`   ❌ pdfplumber extraction failed: ${result.error}`);
            }
            
            return result;

        } catch (error) {
            console.log(`   ❌ pdfplumber extraction failed: ${error.message}`);
            return {
                success: false,
                fields: [],
                pageCount: 0,
                error: error.message
            };
        }
    }

    createExtractionScript() {
        return `
import pdfplumber
import json
import sys

def extract_form_fields(pdf_path):
    try:
        fields = []
        
        with pdfplumber.open(pdf_path) as pdf:
            for page_num, page in enumerate(pdf.pages):
                # Get form fields (annotations)
                if hasattr(page, 'annotations'):
                    for annot in page.annots:
                        if annot.get('subtype') == 'Widget':
                            # Extract field information
                            rect = annot.get('rect', [0, 0, 0, 0])
                            
                            # pdfplumber uses bottom-left origin
                            x0, y0, x1, y1 = rect
                            
                            # Convert to mm (points to mm)
                            x_mm = x0 * ${fieldMetrics.MM_PER_PT}
                            y_mm = (page.height - y1) * ${fieldMetrics.MM_PER_PT}  # Flip Y
                            width_mm = (x1 - x0) * ${fieldMetrics.MM_PER_PT}
                            height_mm = (y1 - y0) * ${fieldMetrics.MM_PER_PT}
                            
                            field_info = {
                                'name': annot.get('T', f'field_{len(fields)}'),
                                'type': 'text',  # pdfplumber doesn't always provide type
                                'page': page_num + 1,
                                'x': round(x_mm, 2),
                                'y': round(y_mm, 2),
                                'width': round(width_mm, 2),
                                'height': round(height_mm, 2),
                                'fontSize': max(7, min(16, round(height_mm * 0.7, 1))),
                                'confidence': 0.90,
                                'method': 'pdfplumber-extractor'
                            }
                            
                            fields.append(field_info)
                
                # Also try to extract form fields using pdfplumber's form extraction
                # This is a fallback method
                try:
                    form_fields = page.extract_form_fields()
                    if form_fields:
                        for field_name, field_value in form_fields.items():
                            # Try to find corresponding annotation
                            found = False
                            for field in fields:
                                if field['name'] == field_name:
                                    field['value'] = field_value
                                    found = True
                                    break
                            
                            if not found:
                                # Create new field entry
                                fields.append({
                                    'name': field_name,
                                    'type': 'text',
                                    'page': page_num + 1,
                                    'x': 0,
                                    'y': 0,
                                    'width': 50,
                                    'height': 5,
                                    'fontSize': max(7, min(16, round(5 * 0.7, 1))),
                                    'confidence': 0.70,
                                    'method': 'pdfplumber-extractor',
                                    'value': field_value,
                                    'estimated': True
                                })
                except:
                    pass  # Form extraction not always available
        
        return {
            'success': len(fields) > 0,
            'fields': fields,
            'pageCount': len(pdf.pages) if 'pdf' in locals() else 0
        }
    except Exception as e:
        return {
            'success': False,
            'fields': [],
            'pageCount': 0,
            'error': str(e)
        }

if __name__ == '__main__':
    pdf_path = sys.argv[1] if len(sys.argv) > 1 else ''
    result = extract_form_fields(pdf_path)
    print(json.dumps(result))
`;
    }

    async runPythonScript(scriptPath, pdfPath) {
        return new Promise((resolve) => {
            const pythonCmd = this.getPythonCommand();
            const python = spawn(pythonCmd, [scriptPath, pdfPath]);
            
            let output = '';
            let error = '';
            
            python.stdout.on('data', (data) => {
                output += data.toString();
            });
            
            python.stderr.on('data', (data) => {
                error += data.toString();
            });
            
            python.on('close', (code) => {
                if (code === 0 && output) {
                    try {
                        const result = JSON.parse(output);
                        resolve(result);
                    } catch (e) {
                        resolve({
                            success: false,
                            fields: [],
                            pageCount: 0,
                            error: `Failed to parse output: ${e.message}. Output: ${output.substring(0, 200)}`
                        });
                    }
                } else {
                    resolve({
                        success: false,
                        fields: [],
                        pageCount: 0,
                        error: error || `Python script exited with code ${code}`
                    });
                }
            });
        });
    }

    getPythonCommand() {
        try {
            const { execSync } = require('child_process');
            try {
                execSync('python --version', { stdio: 'ignore' });
                return 'python';
            } catch {
                return 'python3';
            }
        } catch {
            return 'python3';
        }
    }
}

module.exports = PdfPlumberExtractor;

