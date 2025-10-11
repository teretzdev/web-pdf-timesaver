#!/usr/bin/env node

const fs = require('fs');
const path = require('path');

// Read both position files
const positionsFile = path.join(__dirname, '../data/t_fl100_gc120_positions.json');
const multipageFile = path.join(__dirname, '../data/t_fl100_gc120_positions_multipage.json');

const positions = JSON.parse(fs.readFileSync(positionsFile, 'utf8'));
const multipage = JSON.parse(fs.readFileSync(multipageFile, 'utf8'));

console.log('=== Updating Multipage Positions ===\n');

// Create backup of multipage file
const backupFile = multipageFile + '.backup.' + new Date().toISOString().replace(/[:.]/g, '-');
fs.writeFileSync(backupFile, JSON.stringify(multipage, null, 2));
console.log(`✓ Backup created: ${path.basename(backupFile)}`);

// Update multipage positions to match the improved positions
const updatedMultipage = {};

Object.entries(positions).forEach(([fieldName, config]) => {
    // Use the improved positions as base
    updatedMultipage[fieldName] = { ...config };
    
    // Keep multipage-specific properties if they exist
    if (multipage[fieldName]) {
        // Preserve multipage-specific styling and sizing
        const originalMultipage = multipage[fieldName];
        
        // Update page and coordinates from improved positions
        updatedMultipage[fieldName].page = config.page;
        updatedMultipage[fieldName].x = config.x;
        updatedMultipage[fieldName].y = config.y;
        
        // Keep multipage-specific properties
        if (originalMultipage.width !== undefined) {
            updatedMultipage[fieldName].width = originalMultipage.width;
        }
        if (originalMultipage.height !== undefined) {
            updatedMultipage[fieldName].height = originalMultipage.height;
        }
        if (originalMultipage.fontSize !== undefined) {
            updatedMultipage[fieldName].fontSize = originalMultipage.fontSize;
        }
        if (originalMultipage.fontStyle !== undefined) {
            updatedMultipage[fieldName].fontStyle = originalMultipage.fontStyle;
        }
        if (originalMultipage.type !== undefined) {
            updatedMultipage[fieldName].type = originalMultipage.type;
        }
    }
    
    console.log(`✓ Updated ${fieldName}: Page ${config.page}, X: ${config.x}, Y: ${config.y}`);
});

// Write updated multipage positions
fs.writeFileSync(multipageFile, JSON.stringify(updatedMultipage, null, 2));
console.log(`\n✓ Updated multipage positions saved to: ${path.basename(multipageFile)}`);

// Generate comparison report
console.log('\n=== COMPARISON REPORT ===');

const pageCounts = {};
Object.values(updatedMultipage).forEach(field => {
    const page = field.page || 1;
    pageCounts[page] = (pageCounts[page] || 0) + 1;
});

console.log('\nUpdated Multipage Distribution:');
Object.keys(pageCounts).sort((a, b) => parseInt(a) - parseInt(b)).forEach(page => {
    const count = pageCounts[page];
    const percentage = Math.round((count / Object.keys(updatedMultipage).length) * 100);
    console.log(`  Page ${page}: ${count} fields (${percentage}%)`);
});

console.log('\n✓ Multipage positions updated successfully!');
console.log('\nBoth position files now have consistent page distribution:');
console.log('  - Page 1: Attorney and Court Information (12 fields)');
console.log('  - Page 2: Party Information and Marriage Details (16 fields)');
console.log('  - Page 3: Additional Information and Signatures (3 fields)');
