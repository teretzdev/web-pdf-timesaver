#!/usr/bin/env node
/**
 * JavaScript-based PDF Form Filler
 * Uses pdf-lib to fill PDF forms natively with AcroForm support
 */

const fs = require('fs');
const path = require('path');
const { PDFDocument, PDFTextField, PDFCheckBox, PDFRadioGroup, PDFDropdown } = require('pdf-lib');

async function fillPdfForm(pdfPath, data, outputPath) {
    console.log('📝 Filling PDF form:', pdfPath);
    console.log('📊 Data fields:', Object.keys(data).length);
    
    // Load PDF
    const pdfBytes = fs.readFileSync(pdfPath);
    const pdfDoc = await PDFDocument.load(pdfBytes, {
        ignoreEncryption: true,
        updateMetadata: false
    });
    
    const form = pdfDoc.getForm();
    const fields = form.getFields();
    
    console.log(`📋 Form has ${fields.length} fields`);
    
    let filledCount = 0;
    let errorCount = 0;
    
    // Fill each field
    fields.forEach(field => {
        const fieldName = field.getName();
        const fieldValue = data[fieldName];
        
        if (fieldValue === undefined || fieldValue === null) {
            return; // Skip if no data for this field
        }
        
        try {
            if (field instanceof PDFTextField) {
                field.setText(String(fieldValue));
                filledCount++;
                console.log(`   ✅ ${fieldName} = "${fieldValue}"`);
            } else if (field instanceof PDFCheckBox) {
                if (fieldValue === true || fieldValue === 'Yes' || fieldValue === 'yes' || fieldValue === '1') {
                    field.check();
                } else {
                    field.uncheck();
                }
                filledCount++;
                console.log(`   ✅ ${fieldName} = ${fieldValue ? 'checked' : 'unchecked'}`);
            } else if (field instanceof PDFRadioGroup) {
                field.select(String(fieldValue));
                filledCount++;
                console.log(`   ✅ ${fieldName} = "${fieldValue}"`);
            } else if (field instanceof PDFDropdown) {
                field.select(String(fieldValue));
                filledCount++;
                console.log(`   ✅ ${fieldName} = "${fieldValue}"`);
            }
        } catch (error) {
            errorCount++;
            console.warn(`   ⚠️  Error filling ${fieldName}: ${error.message}`);
        }
    });
    
    // Save filled PDF
    const filledPdfBytes = await pdfDoc.save();
    fs.writeFileSync(outputPath, filledPdfBytes);
    
    return {
        totalFields: fields.length,
        filledFields: filledCount,
        errors: errorCount,
        outputPath: outputPath
    };
}

// Main execution
(async () => {
    try {
        const pdfPath = process.argv[2] || path.join(__dirname, '../uploads/w9.pdf');
        const dataPath = process.argv[3] || null;
        const outputPath = process.argv[4] || path.join(__dirname, '../output/filled_' + path.basename(pdfPath));
        
        console.log('=== PDF Form Filling (pdf-lib) ===\n');
        
        if (!fs.existsSync(pdfPath)) {
            console.error('❌ PDF file not found:', pdfPath);
            process.exit(1);
        }
        
        // Load data
        let data = {};
        if (dataPath && fs.existsSync(dataPath)) {
            data = JSON.parse(fs.readFileSync(dataPath, 'utf8'));
            console.log('📂 Loaded data from:', dataPath);
        } else {
            // Use sample W-9 data
            data = {
                'topmostSubform[0].Page1[0].f1_01[0]': 'John Smith',
                'topmostSubform[0].Page1[0].f1_02[0]': 'Smith Consulting LLC',
                'topmostSubform[0].Page1[0].Boxes3a-b_ReadOrder[0].c1_1[0]': true, // Individual
                'topmostSubform[0].Page1[0].f1_05[0]': '123 Main Street',
                'topmostSubform[0].Page1[0].f1_06[0]': 'Los Angeles, CA 90012',
                'topmostSubform[0].Page1[0].Address_ReadOrder[0].f1_07[0]': '123 Main Street',
                'topmostSubform[0].Page1[0].Address_ReadOrder[0].f1_08[0]': 'Los Angeles, CA 90012',
                'topmostSubform[0].Page1[0].f1_09[0]': '123-45-6789',
                'topmostSubform[0].Page1[0].f1_10[0]': '98-7654321'
            };
            console.log('📝 Using sample W-9 test data');
        }
        
        const result = await fillPdfForm(pdfPath, data, outputPath);
        
        console.log(`\n✅ Form filling complete!`);
        console.log(`📊 Filled ${result.filledFields} / ${result.totalFields} fields`);
        if (result.errors > 0) {
            console.log(`⚠️  ${result.errors} errors`);
        }
        console.log(`💾 Saved to: ${result.outputPath}`);
        console.log(`📄 File size: ${(fs.statSync(result.outputPath).size / 1024).toFixed(2)} KB`);
        
        // Copy to XAMPP
        const xamppOutput = path.join('C:\\xampp\\htdocs\\Web-PDFTimeSaver\\output', path.basename(outputPath));
        fs.copyFileSync(result.outputPath, xamppOutput);
        console.log(`✅ Copied to XAMPP: ${xamppOutput}`);
        console.log(`🌐 View at: http://localhost/Web-PDFTimeSaver/output/${path.basename(outputPath)}`);
        
        console.log('\n✨ Success! PDF form filled natively with AcroForms!');
        
    } catch (error) {
        console.error('❌ Error:', error.message);
        console.error(error.stack);
        process.exit(1);
    }
})();

