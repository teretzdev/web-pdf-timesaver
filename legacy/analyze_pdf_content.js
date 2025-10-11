const fs = require('fs');
const pdfParse = require('pdf-parse').default || require('pdf-parse');

async function analyzePDF(pdfPath) {
    const dataBuffer = fs.readFileSync(pdfPath);
    
    try {
        const data = await pdfParse(dataBuffer);
        
        console.log('=== PDF ANALYSIS ===\n');
        console.log(`File: ${pdfPath}`);
        console.log(`Pages: ${data.numpages}`);
        console.log(`PDF Version: ${data.version}`);
        console.log(`Info: ${JSON.stringify(data.info, null, 2)}`);
        console.log(`Metadata: ${JSON.stringify(data.metadata, null, 2)}`);
        console.log('\n=== TEXT CONTENT ===\n');
        console.log(data.text);
        
        return data;
    } catch (error) {
        console.error('Error parsing PDF:', error.message);
        throw error;
    }
}

// Analyze both the original and generated PDFs
async function comparePDFs() {
    console.log('\n');
    console.log('='.repeat(80));
    console.log('ORIGINAL FL-100 PDF');
    console.log('='.repeat(80));
    await analyzePDF('uploads/fl100.pdf');
    
    console.log('\n\n');
    console.log('='.repeat(80));
    console.log('GENERATED PDF WITH POSITIONED FIELDS');
    console.log('='.repeat(80));
    const generatedFiles = fs.readdirSync('output').filter(f => f.includes('positioned'));
    if (generatedFiles.length > 0) {
        const latestFile = generatedFiles[generatedFiles.length - 1];
        await analyzePDF(`output/${latestFile}`);
    } else {
        console.log('No positioned PDF found in output directory');
    }
}

comparePDFs().catch(console.error);
