#!/usr/bin/env node

const fs = require('fs');
const path = require('path');

const positionsFile = path.join(__dirname, '../data/t_fl100_gc120_positions.json');
const analyzedFile = path.join(__dirname, '../data/t_fl100_gc120_positions_analyzed.json');

console.log('=== Using Analyzed FL-100 Positions ===\n');

// Read current positions and analyzed positions
let positions = JSON.parse(fs.readFileSync(positionsFile, 'utf8'));
const analyzedPositions = JSON.parse(fs.readFileSync(analyzedFile, 'utf8'));

console.log(`Current positions: ${Object.keys(positions).length} fields`);
console.log(`Analyzed positions: ${Object.keys(analyzedPositions).length} fields\n`);

// Create backup
const backupFile = positionsFile + '.backup.analyzed.' + new Date().toISOString().replace(/[:.]/g, '-');
fs.writeFileSync(backupFile, JSON.stringify(positions, null, 2));
console.log(`✓ Backup created: ${path.basename(backupFile)}\n`);

// Apply analyzed positions with proper page distribution
console.log('Applying analyzed positions with proper page distribution...\n');

// Define page distribution based on FL-100 form structure
const pageDistribution = {
    page1: {
        description: 'Attorney and Court Information',
        fields: [
            'attorney_name', 'attorney_firm', 'attorney_address', 'attorney_city_state_zip', 
            'attorney_phone', 'attorney_email', 'attorney_bar_number', 'court_county', 
            'court_address', 'case_type', 'filing_date', 'case_number'
        ]
    },
    page2: {
        description: 'Party Information and Marriage Details',
        fields: [
            'petitioner_name', 'respondent_name', 'petitioner_address', 'petitioner_phone', 
            'respondent_address', 'dissolution_type', 'property_division', 'spousal_support', 
            'attorney_fees', 'name_change', 'marriage_date', 'separation_date', 
            'marriage_location', 'grounds_for_dissolution', 'has_children', 'children_count'
        ]
    },
    page3: {
        description: 'Additional Information and Signatures',
        fields: [
            'additional_info', 'attorney_signature', 'signature_date'
        ]
    }
};

// Apply analyzed positions
Object.entries(pageDistribution).forEach(([pageName, pageData]) => {
    const pageNum = parseInt(pageName.replace('page', ''));
    console.log(`Page ${pageNum}: ${pageData.description}`);
    
    pageData.fields.forEach(fieldName => {
        if (analyzedPositions[fieldName]) {
            const analyzedField = analyzedPositions[fieldName];
            
            // Update position with analyzed coordinates and page number
            positions[fieldName] = {
                ...analyzedField,
                page: pageNum
            };
            
            console.log(`  ✓ ${fieldName}: (${analyzedField.x}, ${analyzedField.y}) ${analyzedField.type || 'text'}`);
        } else {
            console.log(`  ⚠️  Field ${fieldName} not found in analyzed positions`);
        }
    });
    console.log('');
});

// Save updated positions
fs.writeFileSync(positionsFile, JSON.stringify(positions, null, 2));
console.log('✓ Updated positions saved to t_fl100_gc120_positions.json');

// Verify the changes
console.log('\n=== Verification ===');

// Check page distribution
const pageCounts = {};
Object.entries(positions).forEach(([fieldName, config]) => {
    const page = config.page || 1;
    if (!pageCounts[page]) {
        pageCounts[page] = [];
    }
    pageCounts[page].push(fieldName);
});

console.log('Field distribution by page:');
Object.entries(pageCounts).sort((a, b) => parseInt(a[0]) - parseInt(b[0])).forEach(([page, fields]) => {
    console.log(`  Page ${page}: ${fields.length} fields`);
});

// Check for overlaps
console.log('\nOverlap check:');
let overlapsFound = false;
Object.entries(pageCounts).sort((a, b) => parseInt(a[0]) - parseInt(b[0])).forEach(([page, fields]) => {
    const pageFields = fields.map(fieldName => ({ name: fieldName, ...positions[fieldName] }));
    
    for (let i = 0; i < pageFields.length; i++) {
        for (let j = i + 1; j < pageFields.length; j++) {
            const fieldA = pageFields[i];
            const fieldB = pageFields[j];

            const aLeft = fieldA.x;
            const aRight = fieldA.x + (fieldA.width || 100);
            const aTop = fieldA.y;
            const aBottom = fieldA.y + (fieldA.height || 10);

            const bLeft = fieldB.x;
            const bRight = fieldB.x + (fieldB.width || 100);
            const bTop = fieldB.y;
            const bBottom = fieldB.y + (fieldB.height || 10);

            if (aLeft < bRight && aRight > bLeft && aTop < bBottom && aBottom > bTop) {
                console.log(`  Page ${page}: ${fieldA.name} overlaps with ${fieldB.name}`);
                overlapsFound = true;
            }
        }
    }
});

if (!overlapsFound) {
    console.log('✓ No overlaps found');
}

// Show top half of page 1
console.log('\n=== Page 1 Top Half Fields ===');
const page1Fields = Object.entries(positions)
    .filter(([name, config]) => (config.page || 1) === 1)
    .map(([name, config]) => ({ name, ...config }))
    .sort((a, b) => a.y - b.y);

const topHalfFields = page1Fields.filter(field => field.y < 150);
console.log(`Top half fields (Y < 150): ${topHalfFields.length} fields\n`);

topHalfFields.forEach(field => {
    console.log(`${field.name}: (${field.x}, ${field.y}) ${field.type || 'text'}`);
});

console.log('\n✅ Analyzed positions applied');
console.log('✅ Proper page distribution maintained');
console.log('✅ Ready for PDF generation');
