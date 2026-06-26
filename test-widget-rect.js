const { PDFDocument, PDFName } = require('pdf-lib');
const fs = require('fs');

(async () => {
    const bytes = fs.readFileSync('C:\\Users\\Shadow\\Web-PDFTimeSaver\\temp\\test_decrypt.pdf');
    const pdfDoc = await PDFDocument.load(bytes);
    const form = pdfDoc.getForm();
    const fields = form.getFields();
    
    // Find a text field
    const textField = fields.find(f => f.getName().includes('AttyFor') || f.getName().includes('AttyName'));
    if (textField) {
        console.log('Field:', textField.getName());
        const widgets = textField.acroField.getWidgets();
        if (widgets.length > 0) {
            const widget = widgets[0];
            
            // Try getRectangle()
            try {
                const rect = widget.getRectangle();
                console.log('\ngetRectangle() result:');
                console.log('  x:', rect.x);
                console.log('  y:', rect.y);
                console.log('  width:', rect.width);
                console.log('  height:', rect.height);
            } catch (e) {
                console.log('getRectangle() failed:', e.message);
            }
            
            // Try direct dict access
            try {
                const rectArray = widget.dict.lookup(PDFName.of('Rect'));
                console.log('\nDirect Rect lookup:');
                if (rectArray && rectArray.array) {
                    const vals = rectArray.array;
                    console.log('  Array length:', vals.length);
                    vals.forEach((v, i) => {
                        const val = v.value !== undefined ? v.value : v;
                        console.log(`  [${i}]:`, val, typeof val);
                    });
                    if (vals.length >= 4) {
                        const x1 = vals[0].value !== undefined ? vals[0].value : vals[0];
                        const y1 = vals[1].value !== undefined ? vals[1].value : vals[1];
                        const x2 = vals[2].value !== undefined ? vals[2].value : vals[2];
                        const y2 = vals[3].value !== undefined ? vals[3].value : vals[3];
                        console.log('\n  PDF Rect format [x1, y1, x2, y2]:');
                        console.log('    x1 (left):', x1);
                        console.log('    y1 (bottom):', y1);
                        console.log('    x2 (right):', x2);
                        console.log('    y2 (top):', y2);
                        console.log('    width:', x2 - x1);
                        console.log('    height:', y2 - y1);
                    }
                }
            } catch (e) {
                console.log('Direct lookup failed:', e.message);
            }
            
            // Check page reference
            try {
                const pageRef = widget.dict.lookup(PDFName.of('P'));
                console.log('\nPage reference:', pageRef);
            } catch (e) {
                console.log('Page lookup failed:', e.message);
            }
        }
    }
    
    // Get page size
    const pages = pdfDoc.getPages();
    if (pages.length > 0) {
        const { width, height } = pages[0].getSize();
        console.log('\nPage 1 size:', width, 'x', height, 'points');
        console.log('Page 1 size:', (width * 0.352778).toFixed(2), 'x', (height * 0.352778).toFixed(2), 'mm');
    }
})();

