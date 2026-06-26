const { PDFDocument } = require('pdf-lib');
const fs = require('fs');

(async () => {
    try {
        const decryptedPath = 'C:\\Users\\Shadow\\Web-PDFTimeSaver\\temp\\test_decrypt.pdf';
        const bytes = fs.readFileSync(decryptedPath);
        const pdfDoc = await PDFDocument.load(bytes);
        const form = pdfDoc.getForm();
        const fields = form.getFields();
        
        console.log('✅ PDF loaded successfully');
        console.log(`   Total form fields: ${fields.length}`);
        
        if (fields.length > 0) {
            console.log('   First 10 field names:');
            fields.slice(0, 10).forEach((f, i) => {
                console.log(`      ${i+1}. ${f.getName()} (${f.constructor.name})`);
            });
        } else {
            console.log('   ⚠️  NO FORM FIELDS FOUND!');
            console.log('   This PDF might not have AcroForm fields.');
            console.log('   It might use XFA forms or have fields in a different format.');
        }
    } catch (e) {
        console.error('❌ Error:', e.message);
        console.error(e.stack);
    }
})();

