#!/usr/bin/env node

const fs = require('fs');
const path = require('path');

console.log('=== Analyzing Page 1 Top Half ===\n');

// Read current positions
const positionsFile = path.join(__dirname, '../data/t_fl100_gc120_positions.json');
const positions = JSON.parse(fs.readFileSync(positionsFile, 'utf8'));

// Get page 1 fields
const page1Fields = Object.entries(positions)
    .filter(([name, config]) => (config.page || 1) === 1)
    .map(([name, config]) => ({ name, ...config }))
    .sort((a, b) => a.y - b.y);

console.log('Current Page 1 fields (sorted by Y coordinate):\n');
page1Fields.forEach(field => {
    console.log(`${field.name}: (${field.x}, ${field.y}) ${field.type || 'text'}`);
});

// Analyze top half (Y < 150)
const topHalfFields = page1Fields.filter(field => field.y < 150);
console.log(`\nTop half fields (Y < 150): ${topHalfFields.length} fields\n`);

topHalfFields.forEach(field => {
    console.log(`${field.name}: (${field.x}, ${field.y}) ${field.type || 'text'}`);
});

// Check for issues in top half
console.log('\n=== Top Half Analysis ===\n');

// Check spacing
const spacingIssues = [];
for (let i = 0; i < topHalfFields.length - 1; i++) {
    const current = topHalfFields[i];
    const next = topHalfFields[i + 1];
    const spacing = next.y - (current.y + (current.height || 10));
    
    if (spacing < 5) {
        spacingIssues.push(`${current.name} and ${next.name} too close (spacing=${spacing})`);
    }
    if (spacing > 25) {
        spacingIssues.push(`${current.name} and ${next.name} too far apart (spacing=${spacing})`);
    }
}

if (spacingIssues.length > 0) {
    console.log('Spacing issues:');
    spacingIssues.forEach(issue => console.log(`  - ${issue}`));
} else {
    console.log('✓ Spacing looks good');
}

// Check horizontal distribution
const leftColumn = topHalfFields.filter(field => field.x < 100);
const rightColumn = topHalfFields.filter(field => field.x >= 100);

console.log(`\nLeft column (X < 100): ${leftColumn.length} fields`);
leftColumn.forEach(field => console.log(`  - ${field.name}: (${field.x}, ${field.y})`));

console.log(`\nRight column (X >= 100): ${rightColumn.length} fields`);
rightColumn.forEach(field => console.log(`  - ${field.name}: (${field.x}, ${field.y})`));

// Check for overlaps
console.log('\n=== Overlap Check ===');
let overlapsFound = false;
for (let i = 0; i < topHalfFields.length; i++) {
    for (let j = i + 1; j < topHalfFields.length; j++) {
        const fieldA = topHalfFields[i];
        const fieldB = topHalfFields[j];

        const aLeft = fieldA.x;
        const aRight = fieldA.x + (fieldA.width || 100);
        const aTop = fieldA.y;
        const aBottom = fieldA.y + (fieldA.height || 10);

        const bLeft = fieldB.x;
        const bRight = fieldB.x + (fieldB.width || 100);
        const bTop = fieldB.y;
        const bBottom = fieldB.y + (fieldB.height || 10);

        if (aLeft < bRight && aRight > bLeft && aTop < bBottom && aBottom > bTop) {
            console.log(`  ${fieldA.name} overlaps with ${fieldB.name}`);
            overlapsFound = true;
        }
    }
}

if (!overlapsFound) {
    console.log('✓ No overlaps found');
}

// Recommendations for top half
console.log('\n=== Recommendations ===\n');

console.log('For proper FL-100 top half layout:');
console.log('1. Attorney information should be in the top-left section');
console.log('2. Court information should be in the top-right section');
console.log('3. Fields should be grouped logically (attorney vs court)');
console.log('4. Spacing should be consistent (10-15 units)');
console.log('5. Fields should not overlap');
console.log('6. Fields should be within page boundaries');

// Current field types in top half
const fieldTypes = {};
topHalfFields.forEach(field => {
    const type = field.type || 'text';
    if (!fieldTypes[type]) {
        fieldTypes[type] = [];
    }
    fieldTypes[type].push(field.name);
});

console.log('\nField types in top half:');
Object.entries(fieldTypes).forEach(([type, fields]) => {
    console.log(`  ${type}: ${fields.length} fields`);
});

console.log('\n=== Summary ===');
console.log(`Total fields in top half: ${topHalfFields.length}`);
console.log(`Spacing issues: ${spacingIssues.length}`);
console.log(`Overlaps: ${overlapsFound ? 'Yes' : 'No'}`);
console.log(`Left column: ${leftColumn.length} fields`);
console.log(`Right column: ${rightColumn.length} fields}`);
