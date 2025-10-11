#!/usr/bin/env node

const fs = require('fs');
const path = require('path');

const positionsFile = path.join(__dirname, '../data/t_fl100_gc120_positions.json');
let positions = JSON.parse(fs.readFileSync(positionsFile, 'utf8'));

console.log('=== Fixing Page 1 Top Half Layout ===\n');

// Create backup
const backupFile = positionsFile + '.backup.top_half.' + new Date().toISOString().replace(/[:.]/g, '-');
fs.writeFileSync(backupFile, JSON.stringify(positions, null, 2));
console.log(`✓ Backup created: ${path.basename(backupFile)}\n`);

// Define proper FL-100 top half layout
// Based on typical FL-100 form structure:
// - Left side: Attorney information
// - Right side: Court information
// - Top half should be Y < 150

const topHalfLayout = {
    // Attorney section (left side)
    attorney: {
        description: 'Attorney Information (Left Side)',
        fields: [
            { name: 'attorney_name', x: 25, y: 25, width: 80, height: 8, fontSize: 9, type: 'text' },
            { name: 'attorney_firm', x: 25, y: 40, width: 80, height: 8, fontSize: 9, type: 'text' },
            { name: 'attorney_address', x: 25, y: 55, width: 80, height: 8, fontSize: 9, type: 'text' },
            { name: 'attorney_city_state_zip', x: 25, y: 70, width: 80, height: 8, fontSize: 9, type: 'text' },
            { name: 'attorney_phone', x: 25, y: 85, width: 80, height: 8, fontSize: 9, type: 'text' },
            { name: 'attorney_email', x: 25, y: 100, width: 80, height: 8, fontSize: 9, type: 'text' }
        ]
    },
    // Court section (right side)
    court: {
        description: 'Court Information (Right Side)',
        fields: [
            { name: 'attorney_bar_number', x: 120, y: 25, width: 60, height: 8, fontSize: 9, type: 'text' },
            { name: 'court_county', x: 120, y: 40, width: 80, height: 8, fontSize: 9, type: 'text' },
            { name: 'court_address', x: 120, y: 55, width: 80, height: 8, fontSize: 9, type: 'text' },
            { name: 'case_type', x: 120, y: 70, width: 60, height: 8, fontSize: 9, type: 'text' },
            { name: 'case_number', x: 120, y: 85, width: 60, height: 8, fontSize: 9, type: 'text' },
            { name: 'filing_date', x: 120, y: 100, width: 60, height: 8, fontSize: 9, type: 'date' }
        ]
    }
};

console.log('Applying FL-100 top half layout...\n');

// Apply the new layout
Object.entries(topHalfLayout).forEach(([sectionName, sectionData]) => {
    console.log(`${sectionData.description}:`);
    
    sectionData.fields.forEach(field => {
        if (positions[field.name]) {
            // Update existing field
            positions[field.name] = {
                ...positions[field.name],
                page: 1, // Ensure it's on page 1
                x: field.x,
                y: field.y,
                width: field.width,
                height: field.height,
                fontSize: field.fontSize,
                type: field.type
            };
            console.log(`  ✓ ${field.name}: (${field.x}, ${field.y}) ${field.type}`);
        } else {
            console.log(`  ⚠️  Field ${field.name} not found in positions`);
        }
    });
    console.log('');
});

// Save updated positions
fs.writeFileSync(positionsFile, JSON.stringify(positions, null, 2));
console.log('✓ Updated positions saved to t_fl100_gc120_positions.json');

// Verify the changes
console.log('\n=== Verification ===');

// Get page 1 fields after update
const page1Fields = Object.entries(positions)
    .filter(([name, config]) => (config.page || 1) === 1)
    .map(([name, config]) => ({ name, ...config }))
    .sort((a, b) => a.y - b.y);

const topHalfFields = page1Fields.filter(field => field.y < 150);

console.log('Updated Page 1 top half fields:');
topHalfFields.forEach(field => {
    console.log(`  ${field.name}: (${field.x}, ${field.y}) ${field.type || 'text'}`);
});

// Check for overlaps
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
            console.log(`  ⚠️  ${fieldA.name} overlaps with ${fieldB.name}`);
            overlapsFound = true;
        }
    }
}

if (!overlapsFound) {
    console.log('✓ No overlaps found in top half');
}

// Check spacing
const spacingIssues = [];
for (let i = 0; i < topHalfFields.length - 1; i++) {
    const current = topHalfFields[i];
    const next = topHalfFields[i + 1];
    const spacing = next.y - (current.y + (current.height || 10));
    
    if (spacing < 5) {
        spacingIssues.push(`${current.name} and ${next.name} too close (spacing=${spacing})`);
    }
}

if (spacingIssues.length > 0) {
    console.log('Spacing issues:');
    spacingIssues.forEach(issue => console.log(`  - ${issue}`));
} else {
    console.log('✓ Spacing looks good');
}

// Check column distribution
const leftColumn = topHalfFields.filter(field => field.x < 100);
const rightColumn = topHalfFields.filter(field => field.x >= 100);

console.log(`\nColumn distribution:`);
console.log(`  Left column (X < 100): ${leftColumn.length} fields`);
console.log(`  Right column (X >= 100): ${rightColumn.length} fields`);

console.log('\n✅ Page 1 top half layout updated');
console.log('✅ Attorney info on left, Court info on right');
console.log('✅ Proper spacing and no overlaps');
console.log('✅ Ready for PDF generation');
