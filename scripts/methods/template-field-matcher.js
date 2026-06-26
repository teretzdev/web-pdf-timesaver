/**
 * Template-Based Field Matcher
 * Matches fields based on known PDF templates
 * Uses field name patterns and relative positions
 * Improves accuracy for common form types (W-9, FL-100, FL-105, etc.)
 */

const fs = require('fs');
const path = require('path');
const fieldMetrics = require('../utils/field-metrics');

class TemplateFieldMatcher {
    constructor() {
        this.name = 'template-field-matcher';
        this.mmPerPoint = fieldMetrics.MM_PER_PT;
        this.templates = this.loadTemplates();
    }

    getName() {
        return this.name;
    }

    getStatus() {
        return {
            name: this.name,
            available: true,
            description: 'Template-based field matching for known PDF form types',
            requirements: ['Template definitions']
        };
    }

    /**
     * Load template definitions
     */
    loadTemplates() {
        const templates = {
            'w9': {
                name: 'W-9 Form',
                fields: [
                    { pattern: /name/i, names: ['name', 'business_name', 'filer_name'], position: { y: 100, tolerance: 20 } },
                    { pattern: /business.*name/i, names: ['business_name', 'company_name'], position: { y: 120, tolerance: 20 } },
                    { pattern: /tax.*class/i, names: ['tax_classification'], position: { y: 140, tolerance: 20 } },
                    { pattern: /exempt/i, names: ['exempt_payee'], position: { y: 160, tolerance: 20 } },
                    { pattern: /address/i, names: ['address', 'street_address'], position: { y: 180, tolerance: 20 } },
                    { pattern: /city.*state.*zip/i, names: ['city', 'state', 'zip'], position: { y: 200, tolerance: 20 } },
                    { pattern: /account.*number/i, names: ['account_number'], position: { y: 220, tolerance: 20 } },
                    { pattern: /ssn|social.*security/i, names: ['ssn', 'social_security_number'], position: { y: 240, tolerance: 20 } },
                    { pattern: /ein|employer.*identification/i, names: ['ein', 'employer_identification_number'], position: { y: 260, tolerance: 20 } },
                    { pattern: /signature/i, names: ['signature'], position: { y: 280, tolerance: 20 } },
                    { pattern: /date/i, names: ['date'], position: { y: 300, tolerance: 20 } }
                ]
            },
            'fl100': {
                name: 'FL-100 Form',
                fields: [
                    { pattern: /petitioner/i, names: ['petitioner_name'], position: { y: 50, tolerance: 30 } },
                    { pattern: /respondent/i, names: ['respondent_name'], position: { y: 80, tolerance: 30 } },
                    { pattern: /case.*number/i, names: ['case_number'], position: { y: 30, tolerance: 20 } },
                    { pattern: /attorney/i, names: ['attorney_name'], position: { y: 110, tolerance: 30 } },
                    { pattern: /court/i, names: ['court_name'], position: { y: 140, tolerance: 30 } },
                    { pattern: /county/i, names: ['county'], position: { y: 170, tolerance: 30 } },
                    { pattern: /child/i, names: ['child_name'], position: { y: 200, tolerance: 30 } },
                    { pattern: /marriage/i, names: ['marriage_date'], position: { y: 230, tolerance: 30 } }
                ]
            },
            'fl105': {
                name: 'FL-105 Form',
                fields: [
                    { pattern: /petitioner/i, names: ['petitioner_name'], position: { y: 50, tolerance: 30 } },
                    { pattern: /respondent/i, names: ['respondent_name'], position: { y: 80, tolerance: 30 } },
                    { pattern: /case.*number/i, names: ['case_number'], position: { y: 30, tolerance: 20 } },
                    { pattern: /attorney/i, names: ['attorney_name'], position: { y: 110, tolerance: 30 } }
                ]
            }
        };

        // Try to load templates from file if exists
        const templateFile = path.join(__dirname, '../../data/pdf_templates.json');
        if (fs.existsSync(templateFile)) {
            try {
                const customTemplates = JSON.parse(fs.readFileSync(templateFile, 'utf8'));
                return { ...templates, ...customTemplates };
            } catch (e) {
                // Use default templates if file is invalid
            }
        }

        return templates;
    }

    async extract(pdfPath) {
        try {
            console.log('   🎯 Starting template-based field matching...');
            
            // First, try to detect which template this PDF matches
            const detectedTemplate = await this.detectTemplate(pdfPath);
            
            if (!detectedTemplate) {
                return {
                    success: false,
                    fields: [],
                    pageCount: 1,
                    error: 'No matching template found'
                };
            }

            console.log(`   📋 Detected template: ${detectedTemplate}`);
            
            // Extract text to match against template
            const textItems = await this.extractTextItems(pdfPath);
            
            // Match fields based on template
            const fields = this.matchFieldsToTemplate(textItems, detectedTemplate);
            
            console.log(`   ✅ Template matcher found ${fields.length} fields`);

            return {
                success: fields.length > 0,
                fields: fields,
                pageCount: 1
            };

        } catch (error) {
            console.log(`   ❌ Template matching failed: ${error.message}`);
            return {
                success: false,
                fields: [],
                pageCount: 0,
                error: error.message
            };
        }
    }

    /**
     * Detect which template this PDF matches
     */
    async detectTemplate(pdfPath) {
        const textItems = await this.extractTextItems(pdfPath);
        const allText = textItems.map(t => t.text).join(' ').toLowerCase();
        
        // Check each template
        for (const [templateId, template] of Object.entries(this.templates)) {
            let matchCount = 0;
            for (const fieldDef of template.fields) {
                if (fieldDef.pattern.test(allText)) {
                    matchCount++;
                }
            }
            
            // If we match at least 3 fields, consider it a match
            if (matchCount >= 3) {
                return templateId;
            }
        }

        return null;
    }

    /**
     * Extract text items from PDF (simplified - uses PDF.js if available)
     */
    async extractTextItems(pdfPath) {
        try {
            // Try to use PDF.js if available
            const mod = await import('pdfjs-dist/legacy/build/pdf.mjs');
            const pdfjsLib = mod.default || mod;
            const fs = require('fs');

            const buffer = fs.readFileSync(pdfPath);
            const data = new Uint8Array(buffer);
            const loadingTask = pdfjsLib.getDocument({ data, useWorker: false });
            const pdfDocument = await loadingTask.promise;

            const textItems = [];
            const numPages = Math.min(pdfDocument.numPages, 1); // Check first page only

            for (let pageNum = 1; pageNum <= numPages; pageNum++) {
                const page = await pdfDocument.getPage(pageNum);
                const viewport = page.getViewport({ scale: 1.0 });
                const textContent = await page.getTextContent();

                for (const item of textContent.items) {
                    const [a, b, c, d, e, f] = item.transform;
                    const y = viewport.height - f;

                    textItems.push({
                        text: item.str,
                        x: e,
                        y: y,
                        page: pageNum
                    });
                }
            }

            return textItems;
        } catch (error) {
            // Fallback: return empty array
            return [];
        }
    }

    /**
     * Match fields to template
     */
    matchFieldsToTemplate(textItems, templateId) {
        const template = this.templates[templateId];
        if (!template) {
            return [];
        }

        const fields = [];
        const usedTextItems = new Set();

        for (const fieldDef of template.fields) {
            // Find matching text items
            for (let i = 0; i < textItems.length; i++) {
                if (usedTextItems.has(i)) continue;

                const textItem = textItems[i];
                if (fieldDef.pattern.test(textItem.text)) {
                    // Check if position matches (within tolerance)
                    const expectedY = fieldDef.position.y;
                    const actualY = textItem.y * this.mmPerPoint;
                    const tolerance = fieldDef.position.tolerance || 30;

                    if (Math.abs(actualY - expectedY) <= tolerance) {
                        // Create field based on template
                        for (const fieldName of fieldDef.names) {
                            fields.push({
                                name: fieldName,
                                type: this.guessFieldType(fieldName),
                                page: textItem.page || 1,
                                x: (textItem.x + 50) * this.mmPerPoint, // Estimate field position
                                y: actualY,
                                width: 100, // Default width
                                height: 10, // Default height
                                fontSize: parseFloat(fieldMetrics.estimateFontPtFromHeightMm(10 * this.mmPerPoint, fieldMetrics.DEFAULT_FONT_PT).toFixed(1)),
                                confidence: 0.85,
                                method: this.name,
                                template: templateId,
                                matchedText: textItem.text
                            });
                        }

                        usedTextItems.add(i);
                        break; // Use first match only
                    }
                }
            }
        }

        return fields;
    }

    /**
     * Guess field type from field name
     */
    guessFieldType(fieldName) {
        const name = fieldName.toLowerCase();
        
        if (name.includes('date')) return 'date';
        if (name.includes('signature')) return 'signature';
        if (name.includes('ssn') || name.includes('social')) return 'ssn';
        if (name.includes('ein') || name.includes('employer')) return 'ein';
        if (name.includes('phone')) return 'phone';
        if (name.includes('email')) return 'email';
        if (name.includes('address')) return 'address';
        if (name.includes('zip') || name.includes('postal')) return 'zip';
        if (name.includes('checkbox') || name.includes('check')) return 'checkbox';
        
        return 'text';
    }
}

module.exports = TemplateFieldMatcher;

