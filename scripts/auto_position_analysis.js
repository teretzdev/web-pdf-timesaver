#!/usr/bin/env node
/**
 * Automated Position Analysis Workflow
 * Generates FL-100 PDF, extracts positions, compares with expected positions,
 * and generates adjustment suggestions
 */

const PositionExtractor = require('../lib/position-extractor');
const path = require('path');
const fs = require('fs');

async function main() {
    console.log('=== Automated FL-100 Position Analysis ===\n');
    
    const extractor = new PositionExtractor();
    
    try {
        // Run the complete analysis workflow
        const result = await extractor.analyzePositions();
        
        if (result.success) {
            console.log('\n=== Analysis Complete ===');
            console.log(`✓ PDF generated: ${result.pdfPath}`);
            console.log(`✓ Report saved: ${result.reportPath}`);
            
            // Display key findings
            const { comparison } = result;
            console.log(`\nKey Findings:`);
            console.log(`- Overall accuracy: ${comparison.accuracy.toFixed(1)}%`);
            console.log(`- Fields needing adjustment: ${comparison.misalignedFields}`);
            
            if (comparison.misalignedFields > 0) {
                console.log(`\nFields requiring attention:`);
                comparison.differences.forEach(diff => {
                    if (diff.status === 'misaligned') {
                        console.log(`  • ${diff.fieldName}: ${diff.difference.x.toFixed(1)}mm x, ${diff.difference.y.toFixed(1)}mm y offset`);
                    }
                });
                
                console.log(`\nNext Steps:`);
                console.log(`1. Review the detailed report: ${result.reportPath}`);
                console.log(`2. Use the interactive position editor to make adjustments`);
                console.log(`3. Regenerate PDF and re-run analysis`);
            } else {
                console.log(`\n🎉 All fields are properly aligned!`);
                console.log(`No adjustments needed.`);
            }
            
            // Generate MCP automation suggestions
            console.log(`\n=== MCP Automation Suggestions ===`);
            console.log(`To automate reference PDF comparison:`);
            console.log(`1. Use MCP Chrome Bridge to navigate to draft.clio.com`);
            console.log(`2. Fill form with test data and generate PDF`);
            console.log(`3. Use MCP PDF analysis tools to compare positions`);
            console.log(`4. Apply suggested adjustments automatically`);
            
        } else {
            console.error(`\n❌ Analysis failed: ${result.error}`);
            process.exit(1);
        }
        
    } catch (error) {
        console.error(`\n❌ Fatal error: ${error.message}`);
        process.exit(1);
    }
}

// Helper function to create MCP automation script
function createMCPAutomationScript() {
    return `
# MCP Automation Script for FL-100 Position Analysis

## Step 1: Generate Our PDF
\`\`\`javascript
// Use the position extractor to generate our PDF
const result = await extractor.analyzePositions();
console.log('Our PDF generated:', result.pdfPath);
\`\`\`

## Step 2: Test Draft Clio
\`\`\`javascript
// Navigate to draft.clio.com
mcp_chrome-bridge_chrome_navigate({
    url: "http://draft.clio.com"
});

// Fill form fields with test data
mcp_chrome-bridge_chrome_fill_or_select({
    selector: "[name='attorney_name']",
    value: "John Michael Smith, Esq."
});

// Continue filling all fields...
// Generate PDF and download

// Take screenshot for comparison
mcp_chrome-bridge_chrome_screenshot({
    name: "clio-draft-fl100",
    savePng: true,
    fullPage: true
});
\`\`\`

## Step 3: Test PDF TimeSavers
\`\`\`javascript
// Navigate to pdftimesavers.desktopmasters.com
mcp_chrome-bridge_chrome_navigate({
    url: "https://pdftimesavers.desktopmasters.com"
});

// Fill form and generate PDF
// Take screenshot for comparison
\`\`\`

## Step 4: Compare PDFs
\`\`\`javascript
// Use MCP PDF analysis tools
mcp_pdf-reader_read_pdf({
    sources: [
        { path: "output/our-fl100.pdf" },
        { path: "downloads/clio-fl100.pdf" },
        { path: "downloads/pdftimesavers-fl100.pdf" }
    ],
    include_full_text: true,
    include_metadata: true
});

// Compare positions and generate adjustments
\`\`\`

## Step 5: Apply Adjustments
\`\`\`javascript
// Use the interactive position editor
// Or apply adjustments programmatically
\`\`\`
`;
}

// Export for use in other scripts
module.exports = { main, PositionExtractor };

// Run if called directly
if (require.main === module) {
    main().catch(error => {
        console.error('Fatal error:', error);
        process.exit(1);
    });
}
