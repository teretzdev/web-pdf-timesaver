/**
 * Re-extract positions using the fixed extraction code
 * This script re-runs the ensemble extraction to regenerate position files
 * with corrected coordinate conversion
 */

const fs = require('fs');
const path = require('path');

const QpdfDecryptExtractor = require('./methods/qpdf-decrypt-extractor');
const { spawn } = require('child_process');

// Import the universal field extractor
const UniversalFieldExtractor = require('./universal-field-extractor');

function runVerification(pdfPath, positionsPath) {
    return new Promise((resolve, reject) => {
        console.log('\n🧪 Running verification against source PDF...\n');
        const verifyScript = path.join(__dirname, 'verify-field-positions.js');
        const child = spawn(process.execPath, [verifyScript, pdfPath, positionsPath, '--report=json'], {
            cwd: path.join(__dirname, '..'),
            stdio: ['ignore', 'pipe', 'pipe']
        });
        
        let stdout = '';
        let stderr = '';
        
        child.stdout.on('data', chunk => {
            const text = chunk.toString();
            stdout += text;
            process.stdout.write(text);
        });
        
        child.stderr.on('data', chunk => {
            const text = chunk.toString();
            stderr += text;
            process.stderr.write(text);
        });
        
        child.on('close', code => {
            if (code !== 0) {
                return reject(new Error(`Verification failed with exit code ${code}${stderr ? `: ${stderr.trim()}` : ''}`));
            }
            
            try {
                let jsonStr = '';
                const startIndex = stdout.lastIndexOf('\n{');
                if (startIndex !== -1) {
                    jsonStr = stdout.slice(startIndex + 1).trim();
                } else {
                    const firstBrace = stdout.indexOf('{');
                    if (firstBrace !== -1) {
                        jsonStr = stdout.slice(firstBrace).trim();
                    }
                }
                const report = jsonStr ? JSON.parse(jsonStr) : null;
                resolve(report);
            } catch (error) {
                console.warn('⚠️  Unable to parse verification report JSON:', error.message);
                resolve(null);
            }
        });
    });
}

async function reExtractPositions(pdfPath, outputPath = null) {
    console.log('🔄 Re-extracting positions with fixed coordinate conversion...\n');
    
    if (!fs.existsSync(pdfPath)) {
        console.error(`❌ PDF file not found: ${pdfPath}`);
        process.exit(1);
    }
    
    // Determine output path
    if (!outputPath) {
        const pdfName = path.basename(pdfPath, path.extname(pdfPath));
        const dataDir = path.join(__dirname, '../data');
        if (!fs.existsSync(dataDir)) {
            fs.mkdirSync(dataDir, { recursive: true });
        }
        outputPath = path.join(dataDir, `${pdfName}_positions.json`);
    }
    
    console.log(`📄 Input PDF: ${pdfPath}`);
    console.log(`💾 Output file: ${outputPath}\n`);
    
    try {
        // Run the universal field extractor
        const extractor = new UniversalFieldExtractor();
        // Extract template ID from PDF filename or use default
        const templateId = path.basename(pdfPath, path.extname(pdfPath)).replace(/^t_/, '');
        const result = await extractor.extractPositions(pdfPath, templateId);
        
        if (!result.success) {
            console.error(`❌ Extraction failed: ${result.error}`);
            process.exit(1);
        }
        
        console.log(`\n✅ Extracted ${result.fields.length} fields`);
        
        // Prefer the deduplicated canonical positions saved by the extractor
        const canonicalPath = path.join(__dirname, `../data/${templateId}_positions.json`);
        let sourceFields = result.fields;
        if (fs.existsSync(canonicalPath)) {
            try {
                const canonicalData = JSON.parse(fs.readFileSync(canonicalPath, 'utf8'));
                sourceFields = Object.values(canonicalData).map(field => ({
                    ...field,
                    name: field.originalName || field.name
                }));
                console.log(`\n🧭 Using canonical position file (${Object.keys(canonicalData).length} entries) as source of truth`);
            } catch (error) {
                console.warn(`⚠️  Failed to load canonical positions: ${error.message}. Falling back to in-memory results.`);
            }
        }

        // Convert fields array to object format (field name => field data)
        const positionsObject = {};
        const methodPreference = {
            'qpdf-decrypt-pdf-lib': 0.99,
            'qpdf-decrypt-extraction': 0.98,
            'pdfjs-annotation-extractor': 0.94,
            'pdfbox-extractor': 0.93,
            'pymupdf-extractor': 0.90,
            'enhanced-widget-extractor': 0.89,
            'pdfplumber-extractor': 0.88,
            'pdf-lib-direct': 0.87,
            'pdf-lib-extractor': 0.87,
            'pdf-binary-parser': 0.86,
            'hybrid-visual-detector': 0.82,
            'pdf-extract-kit-wrapper': 0.80,
            'ffdnet-detector': 0.78,
            'template-field-matcher': 0.70,
            'ocr-field-detection': 0.65,
            'pdfjs-text-extraction': 0.20
        };

        const preferenceFor = (field) => {
            if (!field) return 0;
            return methodPreference[field.methodSource] ?? 0.3;
        };

        const isActualPdfName = (name) => /^FL-?\d+/i.test(name);

        const upsertField = (name, field) => {
            if (!name) return;
            const existing = positionsObject[name];
            if (existing) {
                const shouldReplace = preferenceFor(field) > preferenceFor(existing) + 0.01 ||
                    (preferenceFor(field) >= preferenceFor(existing) - 0.01 &&
                        (field.confidence || 0) > (existing.confidence || 0));
                if (!shouldReplace) {
                    return;
                }
            }

            positionsObject[name] = {
                name,
                type: field.type,
                page: field.page,
                x: field.x,
                y: field.y,
                width: field.width,
                height: field.height,
                fontSize: field.fontSize,
                rect_pdf: field.rect_pdf,
                positionValid: field.positionValid !== false,
                method: field.method,
                methodSource: field.methodSource,
                confidence: field.confidence
            };
        };

        sourceFields.forEach(field => {
            const rawNames = [
                field.name,
                field.originalName,
                ...(Array.isArray(field.aliases) ? field.aliases : [])
            ].filter(Boolean);
            const candidateNames = Array.from(new Set(rawNames.filter(isActualPdfName)));
            if (candidateNames.length === 0 && rawNames.length > 0) {
                candidateNames.push(rawNames[0]);
            }
            candidateNames.forEach(name => upsertField(name, field));
        });

        // Override with direct qpdf extraction for maximum accuracy
        console.log('\n🧪 Running dedicated qpdf pass for accuracy overrides...');
        try {
            const qpdfExtractor = new QpdfDecryptExtractor();
            const qpdfResult = await qpdfExtractor.extract(pdfPath);
            if (qpdfResult.success && qpdfResult.fields.length > 0) {
                qpdfResult.fields.forEach(field => {
                    upsertField(field.name, {
                        ...field,
                        method: field.method || 'qpdf-direct',
                        methodSource: 'qpdf-decrypt-pdf-lib'
                    });
                });
                console.log(`   ✅ qpdf override applied to ${qpdfResult.fields.length} fields`);
            } else {
                console.log('   ⚠️  qpdf override skipped - extraction failed');
            }
        } catch (error) {
            console.log(`   ⚠️  qpdf override failed: ${error.message}`);
        }
        
        // Write to output file
        fs.writeFileSync(outputPath, JSON.stringify(positionsObject, null, 2), 'utf8');
        
        console.log(`\n✅ Positions saved to: ${outputPath}`);
        console.log(`\n📊 Summary:`);
        console.log(`   Total fields: ${result.fields.length}`);
        console.log(`   Pages: ${result.pageCount}`);
        
        // Show sample of first few fields
        if (result.fields.length > 0) {
            console.log(`\n📋 Sample fields (first 3):`);
            result.fields.slice(0, 3).forEach(field => {
                console.log(`   - ${field.name}: x=${field.x}mm, y=${field.y}mm, ${field.width}mm x ${field.height}mm`);
            });
        }
        
        try {
            const report = await runVerification(pdfPath, outputPath);
            if (report && typeof report.accuracy === 'number') {
                console.log(`\n📏 Verification accuracy: ${report.accuracy.toFixed(1)}% (Target: 92%)`);
                console.log(`   Matches: ${report.matchCount}, Mismatches: ${report.mismatchCount}, Missing: ${report.missingInExtracted}\n`);
            } else {
                console.log('\n⚠️  Verification completed but no structured report was available.\n');
            }
        } catch (verifyError) {
            console.error('\n❌ Verification step failed:', verifyError.message);
            process.exit(1);
        }
        
        return outputPath;
        
    } catch (error) {
        console.error(`❌ Error during extraction: ${error.message}`);
        console.error(error.stack);
        process.exit(1);
    }
}

// Main execution
if (require.main === module) {
    const args = process.argv.slice(2);
    
    if (args.length === 0) {
        console.log('Usage: node re-extract-positions.js <pdf-path> [output-path]');
        console.log('\nExample:');
        console.log('  node re-extract-positions.js ../data/fl100.pdf');
        console.log('  node re-extract-positions.js ../data/fl100.pdf ../data/fl100_fixed_positions.json');
        process.exit(1);
    }
    
    const pdfPath = path.resolve(args[0]);
    const outputPath = args[1] ? path.resolve(args[1]) : null;
    
    reExtractPositions(pdfPath, outputPath)
        .then(() => {
            console.log('\n✅ Re-extraction complete!');
            process.exit(0);
        })
        .catch(error => {
            console.error('\n❌ Re-extraction failed:', error);
            process.exit(1);
        });
}

module.exports = { reExtractPositions };

