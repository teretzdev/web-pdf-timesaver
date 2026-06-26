/**
 * PyMuPDF (fitz) Field Extractor
 * Uses PyMuPDF Python library for better widget annotation extraction
 * Note: This requires Python with PyMuPDF installed
 */

const { spawn } = require('child_process');
const path = require('path');
const fs = require('fs');
const fieldMetrics = require('../utils/field-metrics');

class PyMuPdfExtractor {
    constructor() {
        this.name = 'pymupdf-extractor';
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
            description: 'PyMuPDF (fitz) widget annotation extraction via Python',
            requirements: ['Python 3.7+', 'PyMuPDF (pip install pymupdf)']
        };
    }

    checkPythonAvailability() {
        try {
            // Check if python or python3 is available
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
            console.log('   🐍 Starting PyMuPDF extraction...');
            
            // Create Python script for extraction
            const pythonScript = this.createExtractionScript();
            const scriptPath = path.join(__dirname, '../../temp/pymupdf_extract.py');
            
            // Ensure temp directory exists
            const tempDir = path.dirname(scriptPath);
            if (!fs.existsSync(tempDir)) {
                fs.mkdirSync(tempDir, { recursive: true });
            }
            
            fs.writeFileSync(scriptPath, pythonScript);
            
            // Run Python script
            const result = await this.runPythonScript(scriptPath, pdfPath);
            
            // Clean up
            if (fs.existsSync(scriptPath)) {
                fs.unlinkSync(scriptPath);
            }
            
            if (result.success) {
                console.log(`   ✅ PyMuPDF extracted ${result.fields.length} fields`);
            } else {
                console.log(`   ❌ PyMuPDF extraction failed: ${result.error}`);
            }
            
            return result;

        } catch (error) {
            console.log(`   ❌ PyMuPDF extraction failed: ${error.message}`);
            return {
                success: false,
                fields: [],
                pageCount: 0,
                error: error.message
            };
        }
    }

    createExtractionScript() {
        return `import fitz  # PyMuPDF
import json
import sys

def extract_form_fields(pdf_path):
    try:
        doc = fitz.open(pdf_path)
        fields = []
        page_count = len(doc)
        
        for page_num in range(page_count):
            page = doc[page_num]
            page_height = page.rect.height
            
            # Get widget annotations (form fields)
            for widget in page.widgets():
                try:
                    field_name = widget.field_name
                    if not field_name:
                        field_name = f'field_{len(fields)}'
                    
                    # Convert PDF coordinates to mm
                    # PDF uses bottom-left origin, convert to top-left
                    rect = widget.rect
                    x_mm = rect.x0 * ${fieldMetrics.MM_PER_PT}  # Convert points to mm
                    y_mm = (page_height - rect.y1) * ${fieldMetrics.MM_PER_PT}  # Flip Y axis
                    width_mm = (rect.x1 - rect.x0) * ${fieldMetrics.MM_PER_PT}
                    height_mm = (rect.y1 - rect.y0) * ${fieldMetrics.MM_PER_PT}
                    
                    # Map field types
                    field_type_str = widget.field_type_string or 'text'
                    type_map = {
                        'text': 'text',
                        'button': 'checkbox',
                        'checkbox': 'checkbox',
                        'radio': 'radio',
                        'choice': 'dropdown',
                        'signature': 'signature'
                    }
                    field_type = type_map.get(field_type_str.lower(), 'text')
                    
                    field_info = {
                        'name': field_name,
                        'type': field_type,
                        'page': page_num + 1,
                        'x': round(x_mm, 2),
                        'y': round(y_mm, 2),
                        'width': round(width_mm, 2),
                        'height': round(height_mm, 2),
                        'fontSize': max(7, min(16, round(height_mm * 0.7, 1))),
                        'confidence': 0.95,
                        'method': 'pymupdf-extractor',
                        'value': widget.field_value or ''
                    }
                    
                    fields.append(field_info)
                except Exception as e:
                    # Skip widgets that cause errors
                    continue
        
        doc.close()
        return {
            'success': len(fields) > 0,
            'fields': fields,
            'pageCount': page_count
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
    if not pdf_path:
        print(json.dumps({'success': False, 'error': 'No PDF path provided'}))
    else:
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
                            error: `Failed to parse output: ${e.message}`
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

module.exports = PyMuPdfExtractor;

