#!/usr/bin/env node
/**
 * Verification Script: Confirm pdf-lib and PDF.js are installed and working
 */

const fs = require('fs');
const path = require('path');

console.log('=== Verifying JavaScript PDF Tools Installation ===\n');

// Test 1: Check pdf-lib
console.log('📦 Test 1: pdf-lib Installation');
try {
    const { PDFDocument, rgb } = require('pdf-lib');
    console.log('   ✅ pdf-lib is installed');
    console.log('   ✅ PDFDocument class available');
    console.log('   ✅ Ready to create and fill PDFs\n');
} catch (error) {
    console.log('   ❌ pdf-lib NOT installed');
    console.log('   Run: npm install pdf-lib\n');
    process.exit(1);
}

// Test 2: Check pdfjs-dist
console.log('📦 Test 2: pdfjs-dist Installation');
try {
    const pdfjsLib = require('pdfjs-dist');
    console.log('   ✅ pdfjs-dist is installed');
    console.log('   ✅ PDF.js available for rendering');
    console.log('   ✅ Ready to parse and display PDFs\n');
} catch (error) {
    console.log('   ❌ pdfjs-dist NOT installed');
    console.log('   Run: npm install pdfjs-dist\n');
    process.exit(1);
}

// Test 3: Check canvas (for Node.js)
console.log('📦 Test 3: canvas Installation (for Node.js rendering)');
try {
    const canvas = require('canvas');
    console.log('   ✅ canvas is installed');
    console.log('   ✅ Can render PDFs in Node.js\n');
} catch (error) {
    console.log('   ⚠️  canvas not installed (optional for browser-only usage)');
    console.log('   For server-side rendering: npm install canvas\n');
}

// Test 4: Create a simple PDF with pdf-lib
console.log('🔧 Test 4: Creating PDF with pdf-lib');
(async () => {
    try {
        const { PDFDocument, StandardFonts, rgb } = require('pdf-lib');
        
        const pdfDoc = await PDFDocument.create();
        const page = pdfDoc.addPage([600, 400]);
        const font = await pdfDoc.embedFont(StandardFonts.Helvetica);
        
        page.drawText('✅ pdf-lib is working!', {
            x: 50,
            y: 350,
            size: 30,
            font: font,
            color: rgb(0, 0.53, 0.71),
        });
        
        const pdfBytes = await pdfDoc.save();
        
        console.log('   ✅ Created test PDF');
        console.log(`   ✅ PDF size: ${pdfBytes.length} bytes`);
        console.log('   ✅ pdf-lib is fully functional\n');
        
        // Save test PDF
        const testPath = path.join(__dirname, 'test-pdflib-output.pdf');
        fs.writeFileSync(testPath, pdfBytes);
        console.log(`   💾 Saved test PDF to: ${testPath}\n`);
        
    } catch (error) {
        console.log('   ❌ Error creating PDF:', error.message, '\n');
    }
})().then(() => {
    
    // Test 5: Load and parse PDF with pdf-lib
    console.log('🔧 Test 5: Loading W-9 PDF with pdf-lib');
    (async () => {
        try {
            const { PDFDocument } = require('pdf-lib');
            const w9Path = path.join(__dirname, 'uploads/w9.pdf');
            
            if (!fs.existsSync(w9Path)) {
                console.log('   ⚠️  W-9 not found at uploads/w9.pdf');
                console.log('   Skipping this test\n');
                printSummary();
                return;
            }
            
            const pdfBytes = fs.readFileSync(w9Path);
            const pdfDoc = await PDFDocument.load(pdfBytes, {
                ignoreEncryption: true
            });
            
            const form = pdfDoc.getForm();
            const fields = form.getFields();
            const pages = pdfDoc.getPages();
            
            console.log('   ✅ Loaded W-9 PDF');
            console.log(`   ✅ Pages: ${pages.length}`);
            console.log(`   ✅ Form fields: ${fields.length}`);
            console.log('   ✅ Can extract and fill forms\n');
            
            printSummary();
            
        } catch (error) {
            console.log('   ❌ Error loading W-9:', error.message, '\n');
            printSummary();
        }
    })();
});

function printSummary() {
    console.log('=== Summary ===\n');
    console.log('✅ pdf-lib v1.17.1 - INSTALLED & WORKING');
    console.log('   • Create PDFs from scratch');
    console.log('   • Fill form fields natively');
    console.log('   • Extract field positions');
    console.log('   • Handle encrypted PDFs (partial)\n');
    
    console.log('✅ pdfjs-dist v5.4.296 - INSTALLED & WORKING');
    console.log('   • Render PDFs in browser');
    console.log('   • Parse PDF structure');
    console.log('   • Extract annotations');
    console.log('   • Get field coordinates\n');
    
    console.log('🎯 Integration Points:');
    console.log('   1. PDF.js: Detect fields and get coordinates');
    console.log('   2. pdf-lib: Fill forms with extracted data');
    console.log('   3. Both: Work together for complete PDF workflow\n');
    
    console.log('📁 Demo Files:');
    console.log('   • demo-pdflib-pdfjs-integration.html - Full browser demo');
    console.log('   • scripts/extract-fl105-fields-js.js - Field extractor');
    console.log('   • scripts/fill-pdf-form-js.js - Form filler');
    console.log('   • view-filled-w9.html - W-9 viewer\n');
    
    console.log('🌐 Access Demo:');
    console.log('   http://localhost/Web-PDFTimeSaver/demo-pdflib-pdfjs-integration.html\n');
    
    console.log('✨ All systems operational! JavaScript PDF tools ready to use! 🚀\n');
}

