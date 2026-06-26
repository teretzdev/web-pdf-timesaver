/**
 * Verification Tool: Compare extracted positions with actual PDF form field positions
 * This tool reads the actual PDF and extracts form field positions, then compares
 * them with our extracted positions to verify accuracy.
 */

const fs = require('fs');
const path = require('path');
const { spawn } = require('child_process');
const { PDFDocument } = require('pdf-lib');

const DEFAULT_TOLERANCE_MM = 2.0;
const SAMPLE_LIMIT = 5;

function parseArgs(argv) {
    const positional = [];
    const flags = {};
    
    argv.forEach(arg => {
        if (arg.startsWith('--')) {
            const [rawKey, rawValue] = arg.slice(2).split('=');
            const key = (rawKey || '').trim();
            if (!key) return;
            const value = rawValue === undefined ? true : rawValue.trim();
            flags[key] = value;
        } else {
            positional.push(arg);
        }
    });
    
    return { positional, flags };
}

function formatSampleList(names) {
    if (!names || names.length === 0) {
        return '   (no entries)\n';
    }
    const lines = names.slice(0, SAMPLE_LIMIT).map((name, idx) => `   ${idx + 1}. ${name}`);
    if (names.length > SAMPLE_LIMIT) {
        lines.push(`   ... (+${names.length - SAMPLE_LIMIT} more)`);
    }
    return lines.join('\n') + '\n';
}

function describeNameStyle(name) {
    if (!name) return 'unknown';
    if (/\[[0-9]+\]/.test(name) || name.includes('.')) return 'AcroForm';
    if (/_|-/g.test(name)) return 'template/simple';
    return 'plain';
}

function logNameSamples(label, names) {
    console.log(`🔸 ${label}: ${names.length} fields (style: ${describeNameStyle(names[0])})`);
    console.log(formatSampleList(names));
}

function makeNameVariants(name) {
    if (!name) return [];
    const trimmed = String(name).trim();
    if (!trimmed) return [];
    
    const variants = new Set();
    variants.add(trimmed);
    
    const lower = trimmed.toLowerCase();
    variants.add(lower);
    
    const noIndices = lower.replace(/\[\d+\]/g, '');
    variants.add(noIndices);
    
    const normalizedDots = noIndices.replace(/[^a-z0-9]+/g, '.').replace(/\.+/g, '.');
    variants.add(normalizedDots);
    
    const segments = normalizedDots.split('.').filter(Boolean);
    if (segments.length) {
        variants.add(segments.join('.'));
        variants.add(segments.join('_'));
        variants.add(segments[segments.length - 1]);

        // Handle canonicalized extractor names that preserve index tokens as standalone
        // segments (e.g. fl_100_0_page1_0_...) while AcroForm names may drop [0].
        const withoutNumericSegments = segments.filter(s => !/^\d+$/.test(s));
        if (withoutNumericSegments.length) {
            variants.add(withoutNumericSegments.join('.'));
            variants.add(withoutNumericSegments.join('_'));
            variants.add(withoutNumericSegments[withoutNumericSegments.length - 1]);
        }

        // Normalize common form-code tokenization differences:
        // fl.100 <-> fl100, page.1 <-> page1
        const compacted = withoutNumericSegments
            .map((seg, idx, arr) => {
                if (seg === 'fl' && arr[idx + 1] && /^\d+$/.test(arr[idx + 1])) {
                    return `fl${arr[idx + 1]}`;
                }
                if (seg === 'page' && arr[idx + 1] && /^\d+$/.test(arr[idx + 1])) {
                    return `page${arr[idx + 1]}`;
                }
                return seg;
            })
            .filter((seg, idx, arr) => {
                const prev = arr[idx - 1];
                return !((prev === 'fl' || prev === 'page') && /^\d+$/.test(seg));
            });
        if (compacted.length) {
            variants.add(compacted.join('.'));
            variants.add(compacted.join('_'));
        }
    }
    
    return Array.from(variants).filter(Boolean);
}

function buildAliasIndex(extractedPositions) {
    const aliasMap = new Map();
    for (const key of Object.keys(extractedPositions)) {
        const variants = makeNameVariants(key);
        variants.forEach(variant => {
            if (!aliasMap.has(variant)) {
                aliasMap.set(variant, new Set());
            }
            aliasMap.get(variant).add(key);
        });
    }
    return aliasMap;
}

function findExtractedField(fieldName, extractedPositions, aliasIndex, usedKeys) {
    if (!fieldName) return null;
    
    if (extractedPositions[fieldName] && !usedKeys.has(fieldName)) {
        return { key: fieldName, viaAlias: false };
    }
    
    const variants = makeNameVariants(fieldName);
    for (const variant of variants) {
        const candidates = aliasIndex.get(variant);
        if (!candidates) continue;
        for (const candidate of candidates) {
            if (!usedKeys.has(candidate)) {
                return { key: candidate, viaAlias: true, aliasKey: variant };
            }
        }
    }
    
    return null;
}

function emitReport(summary, format) {
    if (!format) return;
    const total = summary.total ?? (summary.matchCount + summary.mismatchCount + summary.missingInExtracted);
    const payload = {
        ...summary,
        total,
        accuracy: summary.accuracy
    };
    
    if (format === 'json') {
        console.log(JSON.stringify(payload, null, 2));
    } else if (format === 'csv') {
        console.log('metric,value');
        Object.entries(payload).forEach(([key, value]) => {
            console.log(`${key},${value}`);
        });
    } else {
        console.warn(`⚠️  Unknown report format "${format}" - supported: json, csv`);
    }
}

function findQpdfBinary() {
    const candidates = [
        path.join(__dirname, '../bin/qpdf/bin/qpdf.exe'),
        path.join(__dirname, '../bin/qpdf/bin/qpdf.bat'),
        'qpdf',
        'qpdf.exe'
    ];
    
    for (const candidate of candidates) {
        if (fs.existsSync(candidate)) {
            return candidate;
        }
    }
    
    return null;
}

async function decryptPdf(inputPath, outputPath, password = '') {
    return new Promise((resolve) => {
        const qpdfPath = findQpdfBinary();
        if (!qpdfPath) {
            resolve(false);
            return;
        }
        
        const args = ['--decrypt'];
        if (password) {
            args.push(`--password=${password}`);
        }
        args.push(inputPath, outputPath);
        
        // Use cmd.exe for .bat files on Windows
        const isBat = qpdfPath.endsWith('.bat');
        const command = isBat ? 'cmd' : qpdfPath;
        const commandArgs = isBat ? ['/c', qpdfPath, ...args] : args;
        
        const qpdf = spawn(command, commandArgs, { shell: isBat });
        
        qpdf.on('close', (code) => {
            resolve(code === 0 && fs.existsSync(outputPath));
        });
        
        qpdf.on('error', () => {
            resolve(false);
        });
    });
}

async function verifyPositions(pdfPath, positionsPath, options = {}) {
    const {
        tolerance = DEFAULT_TOLERANCE_MM,
        reportFormat = null
    } = options;
    
    console.log('🔍 Verifying field positions...\n');
    
    if (!fs.existsSync(pdfPath)) {
        console.error(`❌ PDF file not found: ${pdfPath}`);
        process.exit(1);
    }
    
    if (!fs.existsSync(positionsPath)) {
        console.error(`❌ Positions file not found: ${positionsPath}`);
        process.exit(1);
    }
    
    // Load extracted positions
    let extractedPositions;
    try {
        extractedPositions = JSON.parse(fs.readFileSync(positionsPath, 'utf8'));
    } catch (error) {
        console.error(`❌ Failed to parse positions file: ${error.message}`);
        process.exit(1);
    }
    
    const extractedKeys = Object.keys(extractedPositions || {});
    if (extractedKeys.length === 0) {
        console.error('❌ Positions file contains no fields. Re-run extraction before verification.');
        process.exit(1);
    }
    
    console.log(`📄 Loaded ${extractedKeys.length} extracted positions from ${positionsPath}\n`);
    logNameSamples('Extracted field names', extractedKeys);
    
    // Decrypt PDF if needed
    let pdfToLoad = pdfPath;
    const tempDir = path.join(__dirname, '../temp');
    if (!fs.existsSync(tempDir)) {
        fs.mkdirSync(tempDir, { recursive: true });
    }
    
    try {
        // Try to load PDF to check if encrypted
        const testBytes = fs.readFileSync(pdfPath);
        await PDFDocument.load(testBytes);
    } catch (error) {
        if (error.message.includes('encrypted')) {
            console.log('🔓 PDF is encrypted, attempting decryption...\n');
            const tempPdf = path.join(tempDir, `decrypted_${Date.now()}.pdf`);
            const commonPasswords = ['', 'password', '123456', 'admin', 'user', 'test'];
            
            let decrypted = false;
            for (const password of commonPasswords) {
                console.log(`   🔑 Trying password: "${password || '(empty)'}"`);
                const success = await decryptPdf(pdfPath, tempPdf, password);
                if (success) {
                    console.log('   ✅ Decryption successful\n');
                    pdfToLoad = tempPdf;
                    decrypted = true;
                    break;
                }
            }
            
            if (!decrypted) {
                console.error('❌ Failed to decrypt PDF');
                process.exit(1);
            }
        } else {
            throw error;
        }
    }
    
    // Load PDF and extract actual form field positions
    const pdfBytes = fs.readFileSync(pdfToLoad);
    const pdfDoc = await PDFDocument.load(pdfBytes);
    
    // Cleanup temp file if created
    if (pdfToLoad !== pdfPath && fs.existsSync(pdfToLoad)) {
        fs.unlinkSync(pdfToLoad);
    }
    const form = pdfDoc.getForm();
    const fields = form.getFields();
    
    console.log(`📋 Found ${fields.length} actual form fields in PDF\n`);
    
    const mmPerPoint = 0.352778; // mm per PDF point
    const actualPositions = {};
    const pages = pdfDoc.getPages();
    
    // Extract actual positions from PDF
    for (const field of fields) {
        try {
            const name = field.getName();
            const widgets = field.acroField.getWidgets();
            
            if (widgets && widgets.length > 0) {
                const widget = widgets[0];
                const { PDFName } = require('pdf-lib');
                
                // Get Rect from widget
                let rectArray = null;
                try {
                    rectArray = widget.dict.lookup(PDFName.of('Rect'));
                } catch (e) {
                    try {
                        const rectObj = widget.getRectangle();
                        if (rectObj) {
                            rectArray = {
                                array: [
                                    { value: rectObj.x },
                                    { value: rectObj.y },
                                    { value: rectObj.x + rectObj.width },
                                    { value: rectObj.y + rectObj.height }
                                ]
                            };
                        }
                    } catch (e2) {
                        continue;
                    }
                }
                
                if (rectArray && rectArray.array && rectArray.array.length >= 4) {
                    const getValue = (v) => {
                        if (v === null || v === undefined) return 0;
                        if (typeof v === 'number') return v;
                        if (typeof v === 'string') return parseFloat(v) || 0;
                        if (v && typeof v === 'object') {
                            if (v.numberValue !== undefined) return v.numberValue;
                            if (typeof v.asNumber === 'function') {
                                try { return v.asNumber(); } catch (e) {}
                            }
                            if (v.value !== undefined) return v.value;
                        }
                        return 0;
                    };
                    
                    const x1 = getValue(rectArray.array[0]);
                    const y1 = getValue(rectArray.array[1]);
                    const x2 = getValue(rectArray.array[2]);
                    const y2 = getValue(rectArray.array[3]);
                    
                    // Find page
                    let page = 1;
                    try {
                        const pageRef = widget.dict.lookup(PDFName.of('P'));
                        if (pageRef) {
                            for (let i = 0; i < pages.length; i++) {
                                if (pages[i].node === pageRef || pages[i].node.ref === pageRef) {
                                    page = i + 1;
                                    break;
                                }
                            }
                        }
                    } catch (e) {
                        page = 1;
                    }
                    
                    const pageObj = pages[page - 1] || pages[0];
                    const { height: pageHeight } = pageObj.getSize();
                    
                    // Convert to top-left origin (same as extraction code)
                    const yBottom = y1 < y2 ? y1 : y2;
                    const yTop = y1 < y2 ? y2 : y1;
                    const x = x1;
                    const y = pageHeight - yTop;
                    
                    // Convert to mm
                    const xMm = parseFloat((x * mmPerPoint).toFixed(2));
                    const yMm = parseFloat((y * mmPerPoint).toFixed(2));
                    const widthMm = parseFloat(((x2 - x1) * mmPerPoint).toFixed(2));
                    const heightMm = parseFloat(((yTop - yBottom) * mmPerPoint).toFixed(2));
                    
                    actualPositions[name] = {
                        name,
                        page,
                        x: xMm,
                        y: yMm,
                        width: widthMm,
                        height: heightMm,
                        rect_pdf: [x1, y1, x2, y2]
                    };
                }
            }
        } catch (e) {
            console.warn(`⚠️  Error extracting position for field ${field.getName()}: ${e.message}`);
        }
    }
    
    const actualKeys = Object.keys(actualPositions);
    if (actualKeys.length === 0) {
        console.error('❌ No form field positions could be read from the PDF. Ensure the PDF contains AcroForm fields.');
        process.exit(1);
    }
    
    console.log(`✅ Extracted ${actualKeys.length} actual positions from PDF\n`);
    logNameSamples('Actual PDF field names', actualKeys);
    
    const extractedStyle = describeNameStyle(extractedKeys[0]);
    const actualStyle = describeNameStyle(actualKeys[0]);
    if (extractedStyle !== actualStyle) {
        console.warn(`⚠️  Field name styles differ (extracted: ${extractedStyle}, actual: ${actualStyle}). Name normalization will attempt to bridge the gap.`);
    }
    
    const aliasIndex = buildAliasIndex(extractedPositions);
    const usedExtractedKeys = new Set();
    const aliasMatches = [];
    
    // Compare extracted vs actual
    console.log('📊 COMPARISON RESULTS:\n');
    
    let matchCount = 0;
    let mismatchCount = 0;
    let missingInExtracted = 0;
    let missingInActual = 0;
    const mismatches = [];
    const matches = [];
    
    // First pass: collect all comparison data
    for (const [fieldName, actual] of Object.entries(actualPositions)) {
        const matchInfo = findExtractedField(fieldName, extractedPositions, aliasIndex, usedExtractedKeys);
        if (!matchInfo) {
            missingInExtracted++;
            mismatches.push({ fieldName, type: 'missing_in_extracted', actual });
            continue;
        }
        
        const extracted = extractedPositions[matchInfo.key];
        usedExtractedKeys.add(matchInfo.key);
        if (matchInfo.viaAlias) {
            aliasMatches.push({
                actualField: fieldName,
                matchedField: matchInfo.key,
                aliasKey: matchInfo.aliasKey
            });
        }
        
        const xDiff = Math.abs((extracted.x || 0) - actual.x);
        const yDiff = Math.abs((extracted.y || 0) - actual.y);
        
        if (xDiff <= tolerance && yDiff <= tolerance) {
            matchCount++;
            matches.push({ fieldName, actual, extracted, xDiff, yDiff });
        } else {
            mismatchCount++;
            mismatches.push({ fieldName, type: 'position_mismatch', actual, extracted, xDiff, yDiff });
        }
    }
    
    // Check for extracted fields not in actual PDF
    for (const fieldName of Object.keys(extractedPositions)) {
        if (usedExtractedKeys.has(fieldName)) {
            continue;
        }
        missingInActual++;
        mismatches.push({ fieldName, type: 'missing_in_actual', extracted: extractedPositions[fieldName] });
    }
    
    // Show summary first
    console.log('='.repeat(80));
    console.log(`\n📈 SUMMARY:`);
    console.log(`   ✅ Matches (within ${tolerance}mm): ${matchCount}`);
    console.log(`   ❌ Position Mismatches: ${mismatchCount}`);
    console.log(`   ⚠️  Missing in Extracted: ${missingInExtracted}`);
    console.log(`   ⚠️  Missing in Actual PDF: ${missingInActual}`);
    const total = matchCount + mismatchCount + missingInExtracted;
    console.log(`   📊 Accuracy: ${total > 0 ? ((matchCount / total) * 100).toFixed(1) : 0}%`);
    console.log('='.repeat(80));
    
    // Show details for mismatches (limit to first 20)
    if (mismatches.length > 0) {
        console.log(`\n❌ MISMATCHES (showing first 20 of ${mismatches.length}):\n`);
        mismatches.slice(0, 20).forEach(m => {
            if (m.type === 'missing_in_extracted') {
                console.log(`❌ ${m.fieldName}:`);
                console.log(`   ACTUAL:   x=${m.actual.x}mm, y=${m.actual.y}mm`);
                console.log(`   EXTRACTED: NOT FOUND\n`);
            } else if (m.type === 'position_mismatch') {
                console.log(`❌ ${m.fieldName}:`);
                console.log(`   ACTUAL:   x=${m.actual.x}mm, y=${m.actual.y}mm`);
                console.log(`   EXTRACTED: x=${m.extracted.x}mm, y=${m.extracted.y}mm`);
                console.log(`   DIFF:     x=${m.xDiff.toFixed(2)}mm, y=${m.yDiff.toFixed(2)}mm (EXCEEDS ${tolerance}mm tolerance)\n`);
            } else if (m.type === 'missing_in_actual') {
                console.log(`⚠️  ${m.fieldName}:`);
                console.log(`   EXTRACTED: x=${m.extracted.x}mm, y=${m.extracted.y}mm`);
                console.log(`   ACTUAL: NOT FOUND IN PDF\n`);
            }
        });
        if (mismatches.length > 20) {
            console.log(`... and ${mismatches.length - 20} more mismatches\n`);
        }
    }
    
    if (aliasMatches.length > 0) {
        console.log(`\n🔁 ALIAS MATCHES (extracted name ↔ actual name):`);
        aliasMatches.slice(0, 20).forEach(m => {
            console.log(`   ${m.matchedField} ↔ ${m.actualField} (alias key: ${m.aliasKey})`);
        });
        if (aliasMatches.length > 20) {
            console.log(`   ... (+${aliasMatches.length - 20} more alias links)\n`);
        } else {
            console.log('');
        }
    }
    
    // Show sample matches (first 5)
    if (matches.length > 0) {
        console.log(`\n✅ SAMPLE MATCHES (showing first 5 of ${matches.length}):\n`);
        matches.slice(0, 5).forEach(m => {
            console.log(`✅ ${m.fieldName}:`);
            console.log(`   ACTUAL:   x=${m.actual.x}mm, y=${m.actual.y}mm`);
            console.log(`   EXTRACTED: x=${m.extracted.x}mm, y=${m.extracted.y}mm`);
            console.log(`   DIFF:     x=${m.xDiff.toFixed(2)}mm, y=${m.yDiff.toFixed(2)}mm\n`);
        });
    }
    
    // Calculate final accuracy (reuse total from above)
    const accuracy = total > 0 ? ((matchCount / total) * 100) : 0;
    
    console.log('\n' + '='.repeat(80));
    console.log(`\n🎯 FINAL ACCURACY: ${accuracy.toFixed(1)}%`);
    console.log(`   Target: 92.0%`);
    console.log(`   Status: ${accuracy >= 92 ? '✅ PASS' : '❌ FAIL'}`);
    console.log('='.repeat(80) + '\n');
    
    emitReport({
        matchCount,
        mismatchCount,
        missingInExtracted,
        missingInActual,
        aliasMatches: aliasMatches.length,
        total,
        tolerance,
        accuracy
    }, reportFormat);
    
    return {
        matchCount,
        mismatchCount,
        missingInExtracted,
        missingInActual,
        aliasMatches: aliasMatches.length,
        total,
        tolerance,
        accuracy
    };
}

// Main execution
if (require.main === module) {
    const rawArgs = process.argv.slice(2);
    const { positional, flags } = parseArgs(rawArgs);
    
    if (positional.length < 2) {
        console.log('Usage: node verify-field-positions.js <pdf-path> <positions-json-path> [--tolerance=mm] [--report=json|csv]');
        console.log('\nExample:');
        console.log('  node verify-field-positions.js uploads/t_fl100_gc120.pdf data/t_fl100_gc120_positions.json --tolerance=1.5 --report=json');
        process.exit(1);
    }
    
    const pdfPath = path.resolve(positional[0]);
    const positionsPath = path.resolve(positional[1]);
    let tolerance = flags.tolerance ? parseFloat(flags.tolerance) : DEFAULT_TOLERANCE_MM;
    if (Number.isNaN(tolerance) || tolerance <= 0) {
        console.warn(`⚠️  Invalid tolerance "${flags.tolerance}" - falling back to ${DEFAULT_TOLERANCE_MM}mm`);
        tolerance = DEFAULT_TOLERANCE_MM;
    }
    
    let reportFormat = typeof flags.report === 'string' ? flags.report.toLowerCase() : null;
    if (reportFormat && !['json', 'csv'].includes(reportFormat)) {
        console.warn(`⚠️  Unknown report format "${reportFormat}" - ignoring`);
        reportFormat = null;
    }
    
    verifyPositions(pdfPath, positionsPath, { tolerance, reportFormat })
        .then(() => {
            process.exit(0);
        })
        .catch(error => {
            console.error('\n❌ Verification failed:', error);
            process.exit(1);
        });
}

module.exports = { verifyPositions };
