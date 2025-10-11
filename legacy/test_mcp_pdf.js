#!/usr/bin/env node
/**
 * Direct MCP PDF Analysis Test
 */

const fs = require('fs');
const path = require('path');

// Test the MCP server directly
async function testMCPPdfAnalysis() {
    console.log('=== Direct MCP PDF Analysis Test ===\n');

    const pdfPath = path.resolve('output/mvp_20251009_205754_t_fl100_gc120_positioned.pdf');
    const expectedPositionsPath = path.resolve('data/t_fl100_gc120_positions.json');

    console.log(`PDF Path: ${pdfPath}`);
    console.log(`Expected Positions: ${expectedPositionsPath}`);
    console.log(`PDF exists: ${fs.existsSync(pdfPath)}`);
    console.log(`Expected positions exists: ${fs.existsSync(expectedPositionsPath)}`);

    if (!fs.existsSync(pdfPath)) {
        console.error('❌ PDF file not found');
        return;
    }

    if (!fs.existsSync(expectedPositionsPath)) {
        console.error('❌ Expected positions file not found');
        return;
    }

    // Read the PDF file as buffer
    const pdfBuffer = fs.readFileSync(pdfPath);
    console.log(`PDF size: ${pdfBuffer.length} bytes`);

    // Read expected positions
    const expectedPositions = JSON.parse(fs.readFileSync(expectedPositionsPath, 'utf8'));
    console.log(`Expected fields: ${Object.keys(expectedPositions).length}`);

    // Try to use pdf-parse directly
    try {
        const pdfParse = require('./mcp-server/node_modules/pdf-parse');
        const pdfData = await pdfParse(pdfBuffer);
        
        console.log('\n=== PDF Content Analysis ===');
        console.log(`Text length: ${pdfData.text.length} characters`);
        console.log(`Pages: ${pdfData.numpages}`);
        console.log(`Info: ${JSON.stringify(pdfData.info, null, 2)}`);
        
        // Show first 500 characters of text
        console.log('\n=== Text Preview ===');
        console.log(pdfData.text.substring(0, 500));
        
        // Look for specific field content
        console.log('\n=== Field Content Analysis ===');
        const fieldChecks = [
            'attorney_name',
            'petitioner_name', 
            'respondent_name',
            'case_number',
            'marriage_date',
            'attorney_signature'
        ];

        for (const field of fieldChecks) {
            const expected = expectedPositions[field];
            if (expected) {
                console.log(`${field}: Expected at page ${expected.page}, (${expected.x}, ${expected.y})`);
                
                // Look for field content in text
                const fieldKeywords = field.split('_');
                const found = fieldKeywords.some(keyword => 
                    pdfData.text.toLowerCase().includes(keyword.toLowerCase())
                );
                console.log(`  Found in text: ${found}`);
            }
        }

    } catch (error) {
        console.error('❌ PDF parsing failed:', error.message);
    }
}

testMCPPdfAnalysis().catch(console.error);
