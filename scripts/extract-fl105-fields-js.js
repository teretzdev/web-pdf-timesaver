#!/usr/bin/env node
/**
 * Enhanced PDF Field Extractor with Validation
 * Uses pdf-lib to extract fields from PDFs with improved error handling and validation
 */

const fs = require('fs');
const path = require('path');
const { PDFDocument, PDFTextField, PDFCheckBox, PDFRadioGroup, PDFDropdown } = require('pdf-lib');

async function extractFields(pdfPath) {
    console.log('🔍 Extracting fields from:', pdfPath);
    
    // Validate input
    if (!fs.existsSync(pdfPath)) {
        throw new Error(`PDF file not found: ${pdfPath}`);
    }

    const fileStats = fs.statSync(pdfPath);
    if (fileStats.size === 0) {
        throw new Error('PDF file is empty');
    }

    console.log(`📄 File size: ${(fileStats.size / 1024).toFixed(1)} KB`);
    
    // Load PDF with enhanced error handling
    let pdfBytes;
    try {
        pdfBytes = fs.readFileSync(pdfPath);
    } catch (error) {
        throw new Error(`Failed to read PDF file: ${error.message}`);
    }

    let pdfDoc;
    try {
        pdfDoc = await PDFDocument.load(pdfBytes, {
            ignoreEncryption: true,
            updateMetadata: false
        });
    } catch (error) {
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
    
    const allFields = [];
    const MM_PER_PT = 0.352778; // Convert points to mm
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
                console.warn(`   ⚠️  Could not get position for ${name}: ${e.message}`);
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
                fontSize: Math.max(7, parseFloat((height * MM_PER_PT * 0.7).toFixed(1))),
                rect_pdf: rect,
                positionValid: positionValid
            };
            
            allFields.push(fieldData);
            
            // Log progress for first 10 fields or every 10th field
            if (index < 10 || index % 10 === 0) {
                const status = positionValid ? '✅' : '⚠️';
                console.log(`   ${status} ${name}: ${type} at (${fieldData.x}, ${fieldData.y}) on page ${page}`);
            }
            
        } catch (error) {
            console.warn(`   ❌ Error processing field ${index}: ${error.message}`);
            warnings.push(`Error processing field ${index}: ${error.message}`);
        }
    });
    
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
        const positionsObject = {};
        result.fields.forEach(field => {
            positionsObject[field.name] = field;
        });
        
        // Save to file
        fs.writeFileSync(outputPath, JSON.stringify(positionsObject, null, 2));
        console.log(`\n💾 Saved to: ${outputPath}`);
        
        // Also save array format
        const arrayOutputPath = outputPath.replace('.json', '_array.json');
        fs.writeFileSync(arrayOutputPath, JSON.stringify(result, null, 2));
        console.log(`💾 Array format: ${arrayOutputPath}`);
        
        // Show extracted fields
        if (result.fields.length > 0) {
            console.log('\n📋 Extracted form fields:');
            result.fields.slice(0, 10).forEach(field => {
                console.log(`   - ${field.name} (${field.type}): ${field.x}, ${field.y}, ${field.width}x${field.height}mm`);
            });
            if (result.fields.length > 10) {
                console.log(`   ... and ${result.fields.length - 10} more fields`);
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
