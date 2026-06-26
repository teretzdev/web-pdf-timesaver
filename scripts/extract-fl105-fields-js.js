#!/usr/bin/env node
/**
 * Enhanced PDF Field Extractor with Progress Bars and Activity Indicators
 * Uses pdf-lib to extract fields from PDFs with improved error handling and validation
 */

const fs = require('fs');
const path = require('path');
const { PDFDocument, PDFTextField, PDFCheckBox, PDFRadioGroup, PDFDropdown } = require('pdf-lib');
const fieldMetrics = require('./utils/field-metrics');

// Simple progress bar implementation
class ProgressBar {
    constructor(total, width = 50) {
        this.total = total;
        this.current = 0;
        this.width = width;
        this.startTime = Date.now();
    }

    update(current, label = '') {
        this.current = current;
        const percentage = Math.round((current / this.total) * 100);
        const filled = Math.round((current / this.total) * this.width);
        const bar = '█'.repeat(filled) + '░'.repeat(this.width - filled);
        const elapsed = ((Date.now() - this.startTime) / 1000).toFixed(1);
        
        process.stdout.write(`\r${label} [${bar}] ${percentage}% (${current}/${this.total}) ${elapsed}s`);
        
        if (current === this.total) {
            console.log(''); // New line when complete
        }
    }

    complete(label = 'Complete') {
        const elapsed = ((Date.now() - this.startTime) / 1000).toFixed(1);
        console.log(`\n✅ ${label} in ${elapsed}s`);
    }
}

// Activity spinner for long operations
class ActivitySpinner {
    constructor(label = 'Processing') {
        this.label = label;
        this.spinner = ['⠋', '⠙', '⠹', '⠸', '⠼', '⠴', '⠦', '⠧', '⠇', '⠏'];
        this.current = 0;
        this.interval = null;
    }

    start() {
        this.interval = setInterval(() => {
            process.stdout.write(`\r${this.spinner[this.current]} ${this.label}`);
            this.current = (this.current + 1) % this.spinner.length;
        }, 100);
    }

    stop(message = 'Done') {
        if (this.interval) {
            clearInterval(this.interval);
            process.stdout.write(`\r✅ ${message}\n`);
        }
    }
}

async function extractFields(pdfPath) {
    console.log('🔍 Starting PDF field extraction...');
    console.log(`📄 Target: ${path.basename(pdfPath)}`);
    
    // Validate input
    if (!fs.existsSync(pdfPath)) {
        throw new Error(`PDF file not found: ${pdfPath}`);
    }

    const fileStats = fs.statSync(pdfPath);
    if (fileStats.size === 0) {
        throw new Error('PDF file is empty');
    }

    console.log(`📊 File size: ${(fileStats.size / 1024).toFixed(1)} KB`);
    
    // Load PDF with enhanced error handling
    const spinner = new ActivitySpinner('Loading PDF');
    spinner.start();
    
    let pdfBytes;
    try {
        pdfBytes = fs.readFileSync(pdfPath);
        spinner.stop('PDF loaded');
    } catch (error) {
        spinner.stop('Failed to read PDF');
        throw new Error(`Failed to read PDF file: ${error.message}`);
    }

    const loadSpinner = new ActivitySpinner('Parsing PDF structure');
    loadSpinner.start();
    
    let pdfDoc;
    try {
        pdfDoc = await PDFDocument.load(pdfBytes, {
            ignoreEncryption: true,
            updateMetadata: false
        });
        loadSpinner.stop('PDF parsed');
    } catch (error) {
        loadSpinner.stop('PDF parsing failed');
        throw new Error(`Failed to load PDF: ${error.message}. PDF may be corrupted or heavily encrypted.`);
    }
    
    const form = pdfDoc.getForm();
    const fields = form.getFields();
    const pages = pdfDoc.getPages();
    
    console.log(`📄 PDF loaded: ${pages.length} pages`);
    console.log(`📋 Found ${fields.length} form fields`);
    
    if (fields.length === 0) {
        console.log('⚠️  No form fields detected. This could mean:');
        console.log('   - PDF is not a fillable form');
        console.log('   - PDF is heavily encrypted');
        console.log('   - PDF uses non-standard form fields');
        return {
            fields: [],
            pageCount: pages.length,
            extractedAt: new Date().toISOString(),
            method: 'pdf-lib',
            source: path.basename(pdfPath),
            warnings: ['No form fields detected']
        };
    }
    
    // Initialize progress bar for field processing
    const progressBar = new ProgressBar(fields.length);
    console.log('\n🔧 Processing form fields...');
    
    const allFields = [];
    const MM_PER_PT = fieldMetrics.MM_PER_PT; // Convert points to mm
    const warnings = [];
    
    // Extract each field with validation
    fields.forEach((field, index) => {
        try {
            const name = field.getName();
            let type = 'text';
            let page = 1;
            let rect = [0, 0, 100, 20]; // Default
            
            // Determine field type
            if (field instanceof PDFTextField) {
                type = 'text';
            } else if (field instanceof PDFCheckBox) {
                type = 'checkbox';
            } else if (field instanceof PDFRadioGroup) {
                type = 'radio';
            } else if (field instanceof PDFDropdown) {
                type = 'dropdown';
            }
            
            // Try to get widget annotations for position
            let positionValid = false;
            try {
                const widgets = field.acroField.getWidgets();
                if (widgets && widgets.length > 0) {
                    const widget = widgets[0];
                    const rectObj = widget.getRectangle();
                    
                    if (rectObj && rectObj.x !== undefined && rectObj.y !== undefined) {
                        rect = [rectObj.x, rectObj.y, rectObj.width, rectObj.height];
                        positionValid = true;
                        
                        // Find which page this widget is on
                        for (let i = 0; i < pages.length; i++) {
                            const pageDict = pages[i].node;
                            const annots = pageDict.get(PDFDocument.PDFName.of('Annots'));
                            if (annots) {
                                page = i + 1;
                                break;
                            }
                        }
                    }
                }
            } catch (e) {
                // Silent warning for position extraction
            }
            
            if (!positionValid) {
                warnings.push(`Field "${name}" has invalid position data`);
            }
            
            // Get page height for coordinate conversion
            const pageObj = pages[page - 1] || pages[0];
            const { height: pageHeight } = pageObj.getSize();
            
            // Convert PDF coordinates (bottom-left origin) to top-left origin
            const x = rect[0];
            const y = pageHeight - rect[1] - rect[3]; // Flip Y
            const width = rect[2];
            const height = rect[3];
            
            // Validate coordinates
            if (x < 0 || y < 0 || width <= 0 || height <= 0) {
                warnings.push(`Field "${name}" has invalid dimensions: ${x}, ${y}, ${width}x${height}`);
            }
            
            const fieldData = {
                name: name,
                type: type,
                page: page,
                x: parseFloat((x * MM_PER_PT).toFixed(2)),
                y: parseFloat((y * MM_PER_PT).toFixed(2)),
                width: parseFloat((width * MM_PER_PT).toFixed(2)),
                height: parseFloat((height * MM_PER_PT).toFixed(2)),
                fontSize: parseFloat(fieldMetrics.estimateFontPtFromHeightMm(height * MM_PER_PT, fieldMetrics.DEFAULT_FONT_PT).toFixed(1)),
                rect_pdf: rect,
                positionValid: positionValid
            };
            
            allFields.push(fieldData);
            
            // Update progress bar
            progressBar.update(index + 1, `Processing field ${index + 1}`);
            
        } catch (error) {
            warnings.push(`Error processing field ${index}: ${error.message}`);
            progressBar.update(index + 1, `Processing field ${index + 1}`);
        }
    });
    
    progressBar.complete('Field processing');
    
    // Final validation
    const validFields = allFields.filter(f => f.positionValid);
    if (validFields.length < allFields.length) {
        warnings.push(`${allFields.length - validFields.length} fields have invalid positions`);
    }
    
    console.log(`\n📊 Extraction Summary:`);
    console.log(`   Total fields: ${allFields.length}`);
    console.log(`   Valid positions: ${validFields.length}`);
    console.log(`   Warnings: ${warnings.length}`);
    
    return {
        fields: allFields,
        pageCount: pages.length,
        extractedAt: new Date().toISOString(),
        method: 'pdf-lib',
        source: path.basename(pdfPath),
        warnings: warnings,
        validFields: validFields.length
    };
}

// Main execution
(async () => {
    try {
        const pdfPath = process.argv[2] || path.join(__dirname, '../uploads/fl105.pdf');
        const outputPath = process.argv[3] || path.join(__dirname, '../data/t_' + path.basename(pdfPath, '.pdf') + '_positions.json');
        
        console.log('=== PDF Field Extraction (pdf-lib) ===\n');
        
        if (!fs.existsSync(pdfPath)) {
            console.error('❌ PDF file not found:', pdfPath);
            process.exit(1);
        }
        
        const result = await extractFields(pdfPath);
        
        console.log(`\n✅ Extraction complete!`);
        console.log(`📊 Total fields extracted: ${result.fields.length}`);
        console.log(`📄 Pages: ${result.pageCount}`);
        console.log(`🔧 Method: ${result.method}`);
        
        // Convert to keyed object format for compatibility
        const saveSpinner = new ActivitySpinner('Saving results');
        saveSpinner.start();
        
        const positionsObject = {};
        result.fields.forEach(field => {
            positionsObject[field.name] = field;
        });
        
        // Save to file
        fs.writeFileSync(outputPath, JSON.stringify(positionsObject, null, 2));
        saveSpinner.stop('Results saved');
        
        // Also save array format
        const arrayOutputPath = outputPath.replace('.json', '_array.json');
        fs.writeFileSync(arrayOutputPath, JSON.stringify(result, null, 2));
        
        console.log(`\n💾 Saved to: ${outputPath}`);
        console.log(`💾 Array format: ${arrayOutputPath}`);
        
        // Show extracted fields
        if (result.fields.length > 0) {
            console.log('\n📋 Sample extracted fields:');
            result.fields.slice(0, 5).forEach(field => {
                const status = field.positionValid ? '✅' : '⚠️';
                console.log(`   ${status} ${field.name} (${field.type}): ${field.x}, ${field.y}, ${field.width}x${field.height}mm`);
            });
            if (result.fields.length > 5) {
                console.log(`   ... and ${result.fields.length - 5} more fields`);
            }
        }
        
        console.log('\n✨ Success! Real form fields extracted with accurate coordinates!');
        console.log('\n🎯 Next steps:');
        console.log('   1. Copy to XAMPP: Copy-Item "' + outputPath + '" "C:\\xampp\\htdocs\\Web-PDFTimeSaver\\data\\"');
        console.log('   2. Test FL-105 form filling: node scripts/process-fl105-with-qpdf.js');
        console.log('   3. Use in PHP: Update fill-fl105-form.php to use these positions');
        
    } catch (error) {
        console.error('❌ Error:', error.message);
        console.error(error.stack);
        process.exit(1);
    }
})();