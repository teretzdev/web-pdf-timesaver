#!/usr/bin/env node
/**
 * Universal PDF Field Position Extractor
 * 5-tier extraction pipeline with intelligent fallback
 * Handles all PDF types: unencrypted, encrypted, corrupted
 */

const fs = require('fs');
const path = require('path');
const { PDFDocument, PDFTextField, PDFCheckBox, PDFRadioGroup, PDFDropdown } = require('pdf-lib');
const { spawn } = require('child_process');

// Import extraction methods
const PdfLibExtractor = require('./methods/pdf-lib-extractor');
const QpdfDecryptExtractor = require('./methods/qpdf-decrypt-extractor');
const PdfJsTextExtractor = require('./methods/pdfjs-text-extractor');
const PdfJsAnnotationExtractor = require('./methods/pdfjs-annotation-extractor');
const OcrFieldDetector = require('./methods/ocr-field-detector');
const EnhancedWidgetExtractor = require('./methods/enhanced-widget-extractor');
const PyMuPdfExtractor = require('./methods/pymupdf-extractor');
const PdfPlumberExtractor = require('./methods/pdfplumber-extractor');
const PdfBoxExtractor = require('./methods/pdfbox-extractor');
const PdfBinaryParser = require('./methods/pdf-binary-parser');
const TemplateFieldMatcher = require('./methods/template-field-matcher');
const HybridVisualDetector = require('./methods/hybrid-visual-detector');
const FfdnetDetector = require('./methods/ffdnet-detector');
const PdfExtractKitWrapper = require('./methods/pdf-extract-kit-wrapper');
const CoordinateValidator = require('./utils/coordinate-validator');
const fieldMetrics = require('./utils/field-metrics');

const METHOD_PREFERENCE = {
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
    'pdfjs-text-extractor': 0.20
};

const ACROFORM_METHODS = new Set([
    'qpdf-decrypt-pdf-lib',
    'qpdf-decrypt-extraction',
    'pdfjs-annotation-extractor',
    'pdfbox-extractor',
    'pymupdf-extractor',
    'enhanced-widget-extractor',
    'pdfplumber-extractor',
    'pdf-lib-direct',
    'pdf-lib-extractor',
    'pdf-binary-parser'
]);

const VISUAL_METHODS = new Set([
    'pdf-extract-kit-wrapper',
    'ffdnet-detector',
    'ocr-field-detection',
    'hybrid-visual-detector',
    'template-field-matcher',
    'pdfjs-text-extractor'
]);

const CONTROL_FIELD_PATTERNS = [
    /(^|[_\-.])button\d*([_\-.]|$)/i,
    /(^|[_\-.])(print|save|reset|warning)([_\-.]|$)/i,
    /(^|[_\-.])area([_\-.]|$)/i
];

/**
 * Normalize extractor type strings to a small internal set.
 */
function normalizeExtractorFieldType(t) {
    const s = String(t || '').trim().toLowerCase();
    if (!s) return '';
    if (s === 'btn' || s === 'button') return 'checkbox';
    if (s === 'tx') return 'text';
    if (s === 'ch' || s === 'choice' || s === 'dropdown') return 'select';
    return s;
}

/**
 * When merging duplicate detections, the highest-weight method may call a widget
 * "text" while another AcroForm method correctly reports "checkbox". Prefer any
 * non-text AcroForm type over "text" from the cluster leader.
 */
function resolveMergedFieldType(cluster) {
    const normalized = cluster.map((f) => normalizeExtractorFieldType(f.type));
    const pickOrder = ['checkbox', 'radio', 'select', 'signature', 'number', 'date', 'email', 'phone', 'url', 'text'];
    for (const p of pickOrder) {
        if (normalized.includes(p)) {
            return p;
        }
    }
    const first = cluster[0];
    return normalizeExtractorFieldType(first && first.type) || 'text';
}

class UniversalFieldExtractor {
    constructor() {
        // CRITICAL: Put qpdf-decrypt FIRST so we decrypt before trying to extract
        // pdf-lib-direct will fail on encrypted PDFs, so we need decryption first
        // ORDER MATTERS: Most reliable methods first
        this.methods = [
            new QpdfDecryptExtractor(),           // DECRYPT FIRST - handles encrypted PDFs
            new PdfJsAnnotationExtractor(),       // PDF.js annotation extraction (widget annotations)
            new EnhancedWidgetExtractor(),        // Enhanced widget extraction (better positions)
            new PyMuPdfExtractor(),               // PyMuPDF (excellent widget extraction)
            new PdfBoxExtractor(),                // PDFBox (Java-based, excellent extraction)
            new PdfPlumberExtractor(),            // pdfplumber (good form field extraction)
            new HybridVisualDetector(),           // Hybrid visual+structural detection
            new PdfExtractKitWrapper(),           // PDF-Extract-Kit bridge (layout + ML when available)
            new FfdnetDetector(),                 // FFDNet/CommonForms inspired detector
            new PdfLibExtractor(),                // Standard pdf-lib extraction
            new PdfBinaryParser(),                // Direct PDF binary parsing
            new TemplateFieldMatcher(),           // Template-based matching
            new OcrFieldDetector(),               // OCR/layout analysis
            new PdfJsTextExtractor()              // Text extraction (for reference only, low weight)
        ];
        this.validator = new CoordinateValidator();
        this.tempDir = path.join(__dirname, '../temp');
        this.dataDir = path.join(__dirname, '../data');
        this.ensureDirectories();
    }

    /**
     * Main extraction pipeline - tries all methods until one succeeds
     */
    async extractPositions(pdfPath, templateId) {
        console.log('🚀 Universal PDF Field Position Extractor');
        console.log('==========================================');
        console.log(`📄 PDF: ${pdfPath}`);
        console.log(`🏷️  Template: ${templateId}`);
        console.log('');

        const result = {
            success: false,
            method: 'none',
            fields: [],
            pageCount: 0,
            warnings: [],
            errors: [],
            attempts: [],
            extractedAt: new Date().toISOString(),
            source: path.basename(pdfPath)
        };

        try {
            // Validate input
            if (!fs.existsSync(pdfPath)) {
                throw new Error(`PDF file not found: ${pdfPath}`);
            }

            // CHAINED EXTRACTION: Run ALL methods and combine results
            // Phase 1: Run all methods (some in parallel for speed)
            console.log('🔄 Phase 1: Running all detection methods...\n');
            const methodPromises = [];
            const methodResults = [];
            
            for (let i = 0; i < this.methods.length; i++) {
                const method = this.methods[i];
                const methodName = method.getName();
                
                // Run each method (can be parallelized, but keeping sequential for now for better error handling)
                console.log(`🔍 Method ${i + 1}/${this.methods.length}: ${methodName}`);
                
                try {
                    const methodResult = await method.extract(pdfPath);
                    const attemptInfo = {
                        method: methodName,
                        success: methodResult.success,
                        fields: methodResult.fields?.length || 0,
                        error: methodResult.error || null
                    };
                    result.attempts.push(attemptInfo);

                    if (methodResult.success && methodResult.fields.length > 0) {
                        console.log(`   ✅ ${methodName}: ${methodResult.fields.length} fields extracted`);
                        
                        // CRITICAL: Skip text extraction results - they're not form fields!
                        if (methodName === 'pdfjs-text-extraction') {
                            console.log(`   ⚠️  WARNING: Text extraction found ${methodResult.fields.length} text items, but these are NOT form fields!`);
                            console.log(`   ⚠️  Skipping - we need actual form field positions!`);
                            methodResults.push({
                                method: methodName,
                                fields: [], // Don't include text extraction fields
                                pageCount: methodResult.pageCount,
                                skipped: true
                            });
                        } else {
                            methodResults.push({
                                method: methodName,
                                fields: methodResult.fields,
                                pageCount: methodResult.pageCount,
                                skipped: false
                            });
                        }
                    } else {
                        console.log(`   ❌ ${methodName}: ${methodResult.error || 'No fields extracted'}`);
                        methodResults.push({
                            method: methodName,
                            fields: [],
                            pageCount: 0,
                            skipped: false,
                            error: methodResult.error
                        });
                    }
                    
                } catch (error) {
                    console.log(`   ❌ ${methodName} error: ${error.message}`);
                    result.attempts.push({
                        method: methodName,
                        success: false,
                        fields: 0,
                        error: error.message
                    });
                    methodResults.push({
                        method: methodName,
                        fields: [],
                        pageCount: 0,
                        skipped: false,
                        error: error.message
                    });
                }
                
                console.log('');
            }
            
            // Phase 2: Filter to only successful method outputs
            const successfulMethodOutputs = methodResults.filter(m => 
                !m.skipped && m.fields.length > 0
            );
            
            console.log(`📊 Phase 1 Complete: ${successfulMethodOutputs.length}/${this.methods.length} methods succeeded`);
            if (successfulMethodOutputs.length > 0) {
                const totalFields = successfulMethodOutputs.reduce((sum, m) => sum + m.fields.length, 0);
                console.log(`   Total fields extracted: ${totalFields}`);
                console.log(`   Methods: ${successfulMethodOutputs.map(m => m.method).join(', ')}\n`);
            }

            // Phase 2: Intelligent ensemble merging - combine ALL successful methods
            if (successfulMethodOutputs.length > 0) {
                console.log('🔄 Phase 2: Combining all detection methods...\n');
                
                const merged = this.mergeEnsemble(successfulMethodOutputs);
                
                console.log(`   📊 Before merge: ${successfulMethodOutputs.reduce((s, m) => s + m.fields.length, 0)} total fields`);
                console.log(`   📊 After merge: ${merged.fields.length} unique fields`);
                console.log(`   📊 Primary method: ${merged.primaryMethod}\n`);

                // Normalize for maximum positional accuracy
                const normalized = this.validator.normalizeFields(merged.fields, {
                    widthPoints: this.validator.defaultPageWidth,
                    heightPoints: this.validator.defaultPageHeight
                });

                const validation = this.validator.validateFields(normalized, merged.pageCount);

                // Success requires: fields exist AND validation passes AND at least one field
                result.success = normalized.length > 0 && validation.isValid && normalized.length > 0;
                result.method = `ensemble-${merged.primaryMethod}`;
                result.fields = normalized;
                result.pageCount = merged.pageCount;
                result.warnings.push(...validation.warnings);
                result.methodsUsed = successfulMethodOutputs.map(m => m.method);
                result.fieldsPerMethod = {};
                successfulMethodOutputs.forEach(m => {
                    result.fieldsPerMethod[m.method] = m.fields.length;
                });
                if (merged.telemetry && Array.isArray(merged.telemetry)) {
                    result.fieldTelemetry = merged.telemetry;
                }
                
                if (result.success) {
                    console.log(`🎯 Phase 2 Complete: Ensemble extraction successful`);
                    console.log(`   ✅ ${normalized.length} fields detected and validated`);
                    console.log(`   ✅ ${validation.warnings.length} warnings`);
                    console.log(`   ✅ Methods used: ${result.methodsUsed.join(', ')}\n`);
                }
            }

            // If all methods failed, provide manual tool option
            if (!result.success) {
                result.errors.push('All automated extraction methods failed');
                result.warnings.push('Consider using manual position mapper tool');
                console.log('❌ All extraction methods failed');
                console.log('💡 Try manual tool: manual-position-mapper.html');
            }

            // Save results if we have fields (even if success=false due to strict verification)
            if (result.fields && result.fields.length > 0) {
                await this.saveResults(result, templateId);
                console.log('💾 Results saved successfully');

                // Auto verification (headless)
                const verifyStrict = process.env.VERIFY_STRICT === '1';
                const auto = await this.runAutoVerification(pdfPath, templateId);
                if (auto && auto.report && auto.report.summary) {
                    const s = auto.report.summary;
                    console.log('🔎 Auto-Verification Summary:');
                    console.log(`   Fields: ${s.fields}`);
                    console.log(`   Avg Score: ${s.avgScore}`);
                    console.log(`   Warns: ${s.warns}  Fails: ${s.fails}`);
                    console.log(`   Verdict: ${s.pass ? 'PASS' : 'WARN/FAIL'}`);
                    if (verifyStrict && !s.pass) {
                        result.success = false;
                        result.errors.push('Verification did not meet threshold (STRICT)');
                        console.log('❌ STRICT verification failed.');
                    }
                }
            }

        } catch (error) {
            console.error('❌ Fatal error:', error.message);
            result.errors.push(error.message);
        }

        return result;
    }

    /**
     * Merge multiple method outputs into a single high-confidence field list
     */
    mergeEnsemble(methodOutputs) {
        const isLikelyControlField = (field) => {
            const haystacks = [
                field?.canonicalName,
                field?.name,
                ...(Array.isArray(field?.aliases) ? field.aliases : [])
            ]
                .filter(Boolean)
                .map(v => String(v));

            return haystacks.some(text => CONTROL_FIELD_PATTERNS.some(rx => rx.test(text)));
        };

        // Preference order for primary candidates
        // CRITICAL: qpdf-decrypt has HIGHEST weight because it decrypts FIRST, ensuring accurate coordinates
        // CRITICAL: Text extraction has very low weight - it extracts TEXT positions, not FORM FIELD positions!
        const methodWeight = METHOD_PREFERENCE;

        // Aggregate all fields with provenance
        const all = [];
        let maxPages = 0;
        for (const out of methodOutputs) {
            maxPages = Math.max(maxPages, out.pageCount || 0);
            for (const f of out.fields) {
                all.push({ ...f, __method: out.method, __weight: methodWeight[out.method] || 0.5 });
            }
        }

        // Helper utilities for name reconciliation
        const simplifyName = (name) => {
            if (!name) return '';
            return String(name)
                .trim()
                .replace(/\s+/g, '_')
                .replace(/[^a-zA-Z0-9_]/g, '_')
                .replace(/_+/g, '_')
                .replace(/^_+|_+$/g, '');
        };

        const pickCanonicalName = (aliases, fallback) => {
            if (!aliases || aliases.length === 0) return simplifyName(fallback);
            const ranked = aliases
                .map((alias) => ({ raw: alias, simplified: simplifyName(alias) }))
                .filter(item => item.simplified)
                .sort((a, b) => a.simplified.length - b.simplified.length);
            if (ranked.length > 0) {
                return ranked[0].simplified;
            }
            return simplifyName(fallback);
        };

        // IMPROVED CLUSTERING: Better name matching and proximity detection
        const clusters = [];
        const used = new Set();

        const distance = (a, b) => {
            if (a.page !== b.page) return Infinity;
            const dx = a.x - b.x;
            const dy = a.y - b.y;
            return Math.hypot(dx, dy);
        };

        const nameSimilar = (a, b) => {
            const na = String(a.name || '').toLowerCase().replace(/[\[\]0-9]/g, '');
            const nb = String(b.name || '').toLowerCase().replace(/[\[\]0-9]/g, '');
            if (!na || !nb) return false;
            
            // Exact match
            if (na === nb) return true;
            
            // One contains the other (for nested field names)
            if (na.includes(nb) || nb.includes(na)) return true;
            
            // Check if they share significant words (for field names like "Party1_ft" vs "Party1")
            const wordsA = na.split(/[._-]/).filter(w => w.length > 2);
            const wordsB = nb.split(/[._-]/).filter(w => w.length > 2);
            if (wordsA.length > 0 && wordsB.length > 0) {
                const commonWords = wordsA.filter(w => wordsB.includes(w));
                // If they share at least 50% of words, consider them similar
                const similarity = commonWords.length / Math.max(wordsA.length, wordsB.length);
                if (similarity >= 0.5) return true;
            }
            
            return false;
        };

        // CRITICAL FIX: Only merge fields that are TRUE duplicates
        // Require BOTH same name AND same position (within 2mm tolerance)
        // This preserves all unique fields instead of incorrectly merging distinct fields
        const PROX_MM = 2; // Only merge if within 2mm (very strict - true duplicates only)

        for (let i = 0; i < all.length; i++) {
            if (used.has(i)) continue;
            const base = all[i];
            const cluster = [base];
            used.add(i);
            for (let j = i + 1; j < all.length; j++) {
                if (used.has(j)) continue;
                const cand = all[j];
                // CRITICAL: Only merge if BOTH name matches AND position matches
                // This ensures we only merge true duplicates, not distinct fields
                const namesMatch = nameSimilar(base, cand);
                const positionsMatch = distance(base, cand) <= PROX_MM;
                if (namesMatch && positionsMatch) {
                    cluster.push(cand);
                    used.add(j);
                }
            }
            clusters.push(cluster);
        }

        // IMPROVED MERGING: Pick best representative per cluster with weighted averaging
        const telemetry = [];

        const mergedFields = clusters.map(cluster => {
            // Sort by weight (method reliability) then confidence
            const sorted = cluster.sort((a, b) => {
                const weightDiff = (b.__weight || 0) - (a.__weight || 0);
                if (Math.abs(weightDiff) > 0.1) return weightDiff;
                return (b.confidence || 0) - (a.confidence || 0);
            });
            const best = sorted[0];
            const sources = Array.from(new Set(cluster.map(item => item.__method || 'unknown')));
            const nameAliases = Array.from(new Set(cluster.map(item => item.name).filter(Boolean)));
            const canonicalName = pickCanonicalName(nameAliases, best.name);

            // Weighted average of positions (higher weight = more influence)
            const totalWeight = cluster.reduce((sum, f) => sum + (f.__weight || 0.5), 0);
            const weightedAvg = (key) => {
                const sum = cluster.reduce((s, f) => s + (Number(f[key]) || 0) * (f.__weight || 0.5), 0);
                return totalWeight > 0 ? sum / totalWeight : best[key];
            };
            
            // Use best position but average dimensions (more stable)
            const avg = (key) => {
                const values = cluster.map(f => Number(f[key]) || 0).filter(v => v > 0);
                if (values.length === 0) return best[key];
                const sum = values.reduce((s, v) => s + v, 0);
                return sum / values.length;
            };
            
            // Use weighted average for position, simple average for dimensions
            const x = Number(weightedAvg('x').toFixed(2));
            const y = Number(weightedAvg('y').toFixed(2));
            const width = Number.isFinite(avg('width')) ? Number(avg('width').toFixed(2)) : (best.width || 50);
            const height = Number.isFinite(avg('height')) ? Number(avg('height').toFixed(2)) : (best.height || 5);

            // Boost confidence if multiple methods agree
            const agreementBoost = cluster.length > 1 ? Math.min(0.1 * (cluster.length - 1), 0.15) : 0;
            const finalConfidence = Math.min(0.99, Math.max(
                best.confidence || 0.5,
                best.__weight || 0.5,
                (best.confidence || 0.5) + agreementBoost
            ));

            const merged = {
                name: best.name,
                canonicalName,
                type: resolveMergedFieldType(cluster),
                page: best.page,
                x,
                y,
                width,
                height,
                fontSize: best.fontSize || fieldMetrics.estimateFontPtFromHeightMm(height, fieldMetrics.DEFAULT_FONT_PT),
                confidence: Number(finalConfidence.toFixed(2)),
                method: 'ensemble',
                methodSource: best.__method, // Track which method provided this field
                methodsAgreed: cluster.length, // How many methods found this field
                sources,
                aliases: nameAliases,
                agreementRatio: Number((cluster.length / Math.max(1, sources.length)).toFixed(2))
            };
            telemetry.push({
                canonical: merged.name,
                normalized: canonicalName,
                sources,
                aliases: nameAliases,
                agreement: cluster.length,
                confidence: merged.confidence,
                methodLead: best.__method
            });
            return merged;
        });

        // CRITICAL: Filter false positives - only accept visual detections if:
        // 1. At least one high-confidence AcroForm method found fields, OR
        // 2. Visual methods have very high agreement (3+ methods) AND high confidence
        
        // Check if any AcroForm methods found fields
        const hasAcroFormFields = methodOutputs.some(m => 
            ACROFORM_METHODS.has(m.method) && m.fields.length > 0
        );
        
        // Filter merged fields based on strict criteria.
        // If we have any true AcroForm extraction, prefer only those fields so
        // visual/OCR heuristics do not pollute known fillable templates (e.g. FL-100).
        const filteredFields = mergedFields.filter(field => {
            const isFromAcroForm = ACROFORM_METHODS.has(field.methodSource);
            const isFromVisual = VISUAL_METHODS.has(field.methodSource);
            
            // Always accept fields from AcroForm methods
            if (isFromAcroForm) {
                return true;
            }

            // When AcroForm fields exist, reject non-AcroForm candidates.
            // This keeps canonical widget coordinates stable across re-runs.
            if (hasAcroFormFields) {
                return false;
            }
            
            // For visual-only detections:
            if (isFromVisual) {
                // If PDF has NO AcroForm fields, be VERY strict - likely all false positives
                // Require 3+ methods to agree AND high confidence
                return field.methodsAgreed >= 3 && field.confidence >= 0.90;
            }
            
            // Unknown method - require high confidence
            return field.confidence >= 0.80;
        });
        
        // Additional filter: If no AcroForm fields found and only 1-2 visual detections,
        // they're likely false positives - reject them
        if (!hasAcroFormFields && filteredFields.length <= 2) {
            console.log(`   ⚠️  WARNING: Only ${filteredFields.length} visual detections passed strict filtering`);
            console.log(`   ⚠️  PDF has no AcroForm fields - visual detections are likely false positives`);
            console.log(`   ⚠️  Rejecting visual-only detections for accuracy`);
            return { fields: [], pageCount: maxPages, primaryMethod: 'none', telemetry: [] };
        }
        
        // Minimum confidence threshold - reject fields below 0.70
        // Also strip obvious interactive UI controls (print/save/reset buttons)
        // that are not form data fields users expect to fill.
        const highConfidenceFields = filteredFields
            .filter(f => f.confidence >= 0.70)
            .filter(f => !isLikelyControlField(f));
        
        // Determine primary method used (highest-weight method that contributed most fields)
        const counts = {};
        for (const f of highConfidenceFields) {
            // Use best source method in its cluster
            counts[f.methodSource || 'ensemble'] = (counts[f.methodSource || 'ensemble'] || 0) + 1;
        }

        // Fallback primary to best available method in outputs
        const primaryMethod = methodOutputs
            .sort((a, b) => (methodWeight[b.method] || 0.5) - (methodWeight[a.method] || 0.5))
            [0]?.method || 'ensemble';

        return { fields: highConfidenceFields, pageCount: maxPages, primaryMethod, telemetry };
    }

    /**
     * Save extraction results to files
     */
    async saveResults(result, templateId) {
        const preferenceFor = (field) => {
            if (!field) return 0;
            return METHOD_PREFERENCE[field.methodSource] ?? (VISUAL_METHODS.has(field.methodSource) ? 0.35 : 0.25);
        };

        // Convert to keyed object format for compatibility
        const positionsObject = {};
        result.fields.forEach(field => {
            const key = field.canonicalName || field.name;
            const existing = positionsObject[key];
            const existingPref = preferenceFor(existing);
            const newPref = preferenceFor(field);
            
            let shouldReplace = false;
            if (!existing) {
                shouldReplace = true;
            } else if (newPref > existingPref + 0.01) {
                shouldReplace = true;
            } else if (newPref < existingPref - 0.01) {
                shouldReplace = false;
            } else if ((field.confidence || 0) > (existing.confidence || 0) + 0.01) {
                shouldReplace = true;
            } else if ((field.confidence || 0) < (existing.confidence || 0) - 0.01) {
                shouldReplace = false;
            } else if ((field.methodsAgreed || 0) > (existing.methodsAgreed || 0)) {
                shouldReplace = true;
            }
            
            if (shouldReplace) {
                positionsObject[key] = {
                    ...field,
                    originalName: field.name
                };
            } else if (existing && (!existing.aliases || (field.aliases && field.aliases.length > existing.aliases.length))) {
                positionsObject[key].aliases = field.aliases;
            }
        });

        // Save position file
        const positionFile = path.join(this.dataDir, `${templateId}_positions.json`);
        fs.writeFileSync(positionFile, JSON.stringify(positionsObject, null, 2));

        // Save detailed results
        const detailFile = path.join(this.dataDir, `${templateId}_extraction_details.json`);
        fs.writeFileSync(detailFile, JSON.stringify(result, null, 2));

        console.log(`📁 Position file: ${positionFile}`);
        console.log(`📁 Details file: ${detailFile}`);
    }

    /**
     * Run automated verification and return summary
     */
    async runAutoVerification(pdfPath, templateId) {
        return new Promise((resolve) => {
            const positionsFile = path.join(this.dataDir, `${templateId}_positions.json`);
            const scriptPath = path.join(__dirname, 'auto-verify-positions.js');
            if (!fs.existsSync(scriptPath)) {
                resolve(null);
                return;
            }
            const node = process.execPath || 'node';
            const child = spawn(node, [scriptPath, pdfPath, positionsFile, templateId], { stdio: 'inherit' });
            child.on('close', (code) => {
                try {
                    const reportPath = path.join(this.dataDir, `${templateId}_verification_report.json`);
                    if (fs.existsSync(reportPath)) {
                        const report = JSON.parse(fs.readFileSync(reportPath, 'utf8'));
                        resolve({ code, report });
                    } else {
                        resolve({ code, report: null });
                    }
                } catch (e) {
                    resolve({ code, report: null });
                }
            });
        });
    }

    /**
     * Get extraction status for all methods
     */
    getStatus() {
        const status = {
            methods: [],
            overall: {
                available: 0,
                total: this.methods.length
            }
        };

        for (const method of this.methods) {
            const methodStatus = method.getStatus();
            status.methods.push(methodStatus);
            if (methodStatus.available) {
                status.overall.available++;
            }
        }

        return status;
    }

    /**
     * Ensure required directories exist
     */
    ensureDirectories() {
        if (!fs.existsSync(this.tempDir)) {
            fs.mkdirSync(this.tempDir, { recursive: true });
        }
        if (!fs.existsSync(this.dataDir)) {
            fs.mkdirSync(this.dataDir, { recursive: true });
        }
    }
}

// Main execution - only run if this file is executed directly
if (require.main === module) {
(async () => {
    try {
        const pdfPath = process.argv[2];
        const templateId = process.argv[3];

        if (!pdfPath || !templateId) {
            console.log('Usage: node scripts/universal-field-extractor.js <pdf-file> <template-id>');
            console.log('Example: node scripts/universal-field-extractor.js uploads/w9.pdf t_w9_universal');
            console.log('');
            console.log('This tool tries multiple extraction methods:');
            console.log('1. pdf-lib direct extraction (fastest, works for unencrypted PDFs)');
            console.log('2. qpdf decryption + pdf-lib (handles encrypted PDFs)');
            console.log('3. PDF.js text layer analysis (fallback for field detection)');
            console.log('4. OCR visual detection (last resort for corrupted PDFs)');
            console.log('5. Manual tool (interactive positioning)');
            console.log('');
            console.log('Optional: set VERIFY_STRICT=1 to enforce verification pass');
            process.exit(1);
        }

        const extractor = new UniversalFieldExtractor();
        const result = await extractor.extractPositions(pdfPath, templateId);

        console.log('');
        console.log('==========================================');
        console.log('  Extraction Results');
        console.log('==========================================');
        console.log('');

        if (result.success) {
            console.log('✅ SUCCESS!');
            console.log(`📊 Fields extracted: ${result.fields.length}`);
            console.log(`📄 Pages: ${result.pageCount}`);
            console.log(`🔧 Method: ${result.method}`);
            
            if (result.warnings.length > 0) {
                console.log('');
                console.log('⚠️  Warnings:');
                result.warnings.forEach(warning => {
                    console.log(`   - ${warning}`);
                });
            }

            console.log('');
            console.log('📋 Sample fields:');
            result.fields.slice(0, 5).forEach(field => {
                console.log(`   - ${field.name} (${field.type}): ${field.x}, ${field.y}, ${field.width}x${field.height}mm`);
            });

            console.log('');
            console.log('🎯 Next steps:');
            console.log('   1. Check data/' + templateId + '_positions.json');
            console.log('   2. Test with: php extract-positions.php ' + pdfPath + ' ' + templateId);
            console.log('   3. Use in MVP application');

        } else {
            console.log('❌ FAILED');
            console.log('');
            console.log('Attempted methods:');
            result.attempts.forEach(attempt => {
                const status = attempt.success ? '✅' : '❌';
                console.log(`   ${status} ${attempt.method}: ${attempt.fields} fields ${attempt.error ? `(${attempt.error})` : ''}`);
            });
            
            if (result.errors.length > 0) {
                console.log('');
                console.log('Errors:');
                result.errors.forEach(error => {
                    console.log(`   - ${error}`);
                });
            }
            
            if (result.warnings.length > 0) {
                console.log('');
                console.log('Warnings:');
                result.warnings.forEach(warning => {
                    console.log(`   - ${warning}`);
                });
            }

            console.log('');
            console.log('💡 Try manual positioning: manual-position-mapper.html');
            if (process.env.VERIFY_STRICT === '1') {
                process.exit(2);
            }
        }

    } catch (error) {
        console.error('❌ Fatal error:', error.message);
        console.error(error.stack);
        process.exit(1);
    }
})();
}

module.exports = UniversalFieldExtractor;
