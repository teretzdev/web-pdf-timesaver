const { PDFDocument, PDFName } = require('pdf-lib');
const fs = require('fs');

(async () => {
    const bytes = fs.readFileSync('C:\\Users\\Shadow\\Web-PDFTimeSaver\\temp\\test_decrypt.pdf');
    const pdfDoc = await PDFDocument.load(bytes);
    const form = pdfDoc.getForm();
    const fields = form.getFields();
    
    const textField = fields.find(f => f.getName().includes('AttyFor') || f.getName().includes('AttyName'));
    if (textField) {
        console.log('Field:', textField.getName());
        const widgets = textField.acroField.getWidgets();
        if (widgets.length > 0) {
            const widget = widgets[0];
            const rectArray = widget.dict.lookup(PDFName.of('Rect'));
            
            console.log('\nRect array type:', rectArray.constructor.name);
            console.log('Array length:', rectArray.array.length);
            
            const getValue = (v) => {
                if (v === null || v === undefined) return 0;
                if (typeof v === 'number') return v;
                if (typeof v === 'string') return parseFloat(v) || 0;
                if (v && typeof v === 'object') {
                    if (v.numberValue !== undefined) return v.numberValue;
                    if (v.value !== undefined) return v.value;
                    if (typeof v === 'function') {
                        try {
                            const result = v();
                            return typeof result === 'number' ? result : (result?.numberValue || result?.value || 0);
                        } catch (e) {
                            return 0;
                        }
                    }
                }
                return 0;
            };
            
            console.log('\nExtracted values:');
            rectArray.array.forEach((v, i) => {
                const val = getValue(v);
                console.log(`  [${i}]:`, val, `(type: ${typeof v}, constructor: ${v?.constructor?.name})`);
            });
        }
    }
})();

