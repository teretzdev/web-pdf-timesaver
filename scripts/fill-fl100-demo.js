#!/usr/bin/env node
/**
 * FL-100 Form Filler with Real qpdf Integration
 * Demonstrates filling FL-100 with test data using real field positions
 */

const fs = require('fs');
const path = require('path');
const { PDFDocument, PDFTextField, PDFCheckBox, PDFRadioGroup, PDFDropdown } = require('pdf-lib');

class FL100FormFiller {
    constructor() {
        this.fl100Path = path.join(__dirname, '../temp/fl100_final_test.pdf');
        this.positionsPath = path.join(__dirname, '../data/t_fl100_real_qpdf_positions.json');
        this.outputPath = path.join(__dirname, '../output/FL-100_FILLED_DEMO.pdf');
        
        this.ensureDirectories();
    }

    ensureDirectories() {
        const outputDir = path.join(__dirname, '../output');
        if (!fs.existsSync(outputDir)) {
            fs.mkdirSync(outputDir, { recursive: true });
        }
    }

    loadFieldPositions() {
        if (!fs.existsSync(this.positionsPath)) {
            throw new Error('FL-100 field positions not found. Run extraction first.');
        }
        
        const positions = JSON.parse(fs.readFileSync(this.positionsPath, 'utf8'));
        console.log(`📋 Loaded ${Object.keys(positions).length} field positions`);
        return positions;
    }

    generateTestData() {
        return {
            // Case Information
            'FL-100[0].Page2[0].CaseNumber[0].CaseNumber_ft[0]': 'FL-2025-001234',
            'FL-100[0].Page3[0].CaseNumber[0].CaseNumber_ft[0]': 'FL-2025-001234',
            
            // Petitioner Information
            'FL-100[0].Page2[0].Parties[0].Party1_ft[0]': 'Sarah Elizabeth Johnson',
            'FL-100[0].Page3[0].Parties[0].Party1_ft[0]': 'Sarah Elizabeth Johnson',
            
            // Respondent Information  
            'FL-100[0].Page2[0].Parties[0].Party2_ft[0]': 'Michael David Johnson',
            'FL-100[0].Page3[0].Parties[0].Party2_ft[0]': 'Michael David Johnson',
            
            // Petitioner Name
            'FL-100[0].Page3[0].PrintPetitionerName_tf[0]': 'Sarah Elizabeth Johnson',
            
            // Attorney Information
            'FL-100[0].Page3[0].PrintPetitionerAttorneyName_tf[0]': 'John Michael Smith, Esq.',
            
            // Marriage Information
            'FL-100[0].Page1[0].DatePartnersSeparated_dt[0]': '03/20/2024',
            
            // Petitioner Residence
            'FL-100[0].Page1[0].PetitionersResidence_tf[0]': 'Los Angeles, California',
            
            // Respondent Residence
            'FL-100[0].Page1[0].RespondentsResidence_tf[0]': 'Los Angeles, California',
            
            // Child Information
            'FL-100[0].Page1[0].MinorChildren_sf[0].Child3Age_tf[0]': '12',
            
            // Separate Property
            'FL-100[0].Page2[0].ConfirmSeparateProperty_sf[0].SeparatePropertyList4_tf[0]': 'Family home at 123 Main Street',
            
            // Other Requests
            'FL-100[0].Page3[0].SpecifyOtherRequests_tf[0]': 'Petitioner requests restoration of former name: Sarah Elizabeth Williams',
            
            // Former Name
            'FL-100[0].Page3[0].SpecifyFormerName_tf[0]': 'Sarah Elizabeth Williams',
            
            // Signature Date
            'FL-100[0].Page3[0].SigDate[0]': '01/22/2025',
            'FL-100[0].Page3[0].SigDate[1]': '01/22/2025'
        };
    }

    async fillForm() {
        console.log('🚀 FL-100 Form Filling Demo with Real qpdf Integration');
        console.log('='.repeat(60));
        
        try {
            // Step 1: Load field positions
            console.log('\n📋 Step 1: Loading field positions...');
            const positions = this.loadFieldPositions();
            
            // Step 2: Generate test data
            console.log('\n📝 Step 2: Generating realistic test data...');
            const testData = this.generateTestData();
            console.log(`✅ Generated ${Object.keys(testData).length} test data fields`);
            
            // Step 3: Load PDF
            console.log('\n📄 Step 3: Loading qpdf-decrypted FL-100 PDF...');
            if (!fs.existsSync(this.fl100Path)) {
                throw new Error('FL-100 PDF not found. Run qpdf decryption first.');
            }
            
            const pdfBytes = fs.readFileSync(this.fl100Path);
            const pdfDoc = await PDFDocument.load(pdfBytes);
            const form = pdfDoc.getForm();
            const fields = form.getFields();
            
            console.log(`✅ PDF loaded: ${pdfDoc.getPageCount()} pages`);
            console.log(`📋 Found ${fields.length} form fields`);
            
            // Step 4: Fill form fields
            console.log('\n✏️  Step 4: Filling form fields...');
            let filledCount = 0;
            
            for (const field of fields) {
                const fieldName = field.getName();
                const testValue = testData[fieldName];
                
                if (testValue) {
                    try {
                        if (field instanceof PDFTextField) {
                            field.setText(testValue);
                            filledCount++;
                            console.log(`   ✅ ${fieldName}: "${testValue}"`);
                        } else if (field instanceof PDFCheckBox) {
                            // For checkboxes, we'll check them if they're in our test data
                            if (testValue === 'true' || testValue === true) {
                                field.check();
                                filledCount++;
                                console.log(`   ✅ ${fieldName}: checked`);
                            }
                        }
                    } catch (error) {
                        console.log(`   ⚠️  ${fieldName}: ${error.message}`);
                    }
                }
            }
            
            console.log(`\n📊 Filled ${filledCount} fields successfully`);
            
            // Step 5: Save filled PDF
            console.log('\n💾 Step 5: Saving filled PDF...');
            const filledPdfBytes = await pdfDoc.save();
            fs.writeFileSync(this.outputPath, filledPdfBytes);
            
            const fileSize = (fs.statSync(this.outputPath).size / 1024).toFixed(1);
            console.log(`✅ Filled PDF saved: ${this.outputPath}`);
            console.log(`📄 File size: ${fileSize} KB`);
            
            // Step 6: Summary
            console.log('\n🎉 FL-100 Form Filling Complete!');
            console.log('='.repeat(40));
            console.log(`📋 Total fields in PDF: ${fields.length}`);
            console.log(`✏️  Fields filled: ${filledCount}`);
            console.log(`📄 Output file: ${path.basename(this.outputPath)}`);
            console.log(`🔧 Method: Real qpdf decryption + pdf-lib filling`);
            
            return {
                success: true,
                filledFields: filledCount,
                totalFields: fields.length,
                outputFile: this.outputPath
            };
            
        } catch (error) {
            console.error('\n❌ Error:', error.message);
            return {
                success: false,
                error: error.message
            };
        }
    }
}

// Main execution
(async () => {
    try {
        const filler = new FL100FormFiller();
        const result = await filler.fillForm();
        
        if (result.success) {
            console.log('\n✨ Success! FL-100 form filled with real test data!');
            console.log(`📁 Open the filled PDF: ${result.outputFile}`);
        } else {
            console.log('\n❌ Form filling failed');
            process.exit(1);
        }
        
    } catch (error) {
        console.error('❌ Fatal error:', error.message);
        process.exit(1);
    }
})();
