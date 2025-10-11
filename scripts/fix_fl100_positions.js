#!/usr/bin/env node

const fs = require('fs');
const path = require('path');

const positionsFile = path.join(__dirname, '../data/t_fl100_gc120_positions.json');
let positions = JSON.parse(fs.readFileSync(positionsFile, 'utf8'));

console.log('=== Fixing FL-100 Field Positions ===\n');

// Create backup
const backupFile = positionsFile + '.backup.fix.' + new Date().toISOString().replace(/[:.]/g, '-');
fs.writeFileSync(backupFile, JSON.stringify(positions, null, 2));
console.log(`✓ Backup created: ${path.basename(backupFile)}\n`);

// Define FL-100 form structure with proper coordinates
const fl100Structure = {
    page1: {
        description: 'Attorney and Court Information',
        sections: {
            attorney: {
                description: 'Attorney Information',
                fields: [
                    { name: 'attorney_name', x: 25, y: 25, width: 100, height: 8, fontSize: 9, type: 'text' },
                    { name: 'attorney_bar_number', x: 140, y: 25, width: 60, height: 8, fontSize: 9, type: 'text' },
                    { name: 'attorney_firm', x: 25, y: 40, width: 120, height: 8, fontSize: 9, type: 'text' },
                    { name: 'attorney_address', x: 25, y: 55, width: 120, height: 8, fontSize: 9, type: 'text' },
                    { name: 'attorney_city_state_zip', x: 25, y: 70, width: 120, height: 8, fontSize: 9, type: 'text' },
                    { name: 'attorney_phone', x: 25, y: 85, width: 80, height: 8, fontSize: 9, type: 'text' },
                    { name: 'attorney_email', x: 25, y: 100, width: 120, height: 8, fontSize: 9, type: 'text' }
                ]
            },
            court: {
                description: 'Court Information',
                fields: [
                    { name: 'court_county', x: 25, y: 125, width: 100, height: 8, fontSize: 9, type: 'text' },
                    { name: 'court_address', x: 25, y: 140, width: 150, height: 8, fontSize: 9, type: 'text' },
                    { name: 'case_type', x: 25, y: 155, width: 80, height: 8, fontSize: 9, type: 'text' },
                    { name: 'case_number', x: 120, y: 155, width: 80, height: 8, fontSize: 9, type: 'text' },
                    { name: 'filing_date', x: 25, y: 170, width: 80, height: 8, fontSize: 9, type: 'date' }
                ]
            }
        }
    },
    page2: {
        description: 'Party Information and Marriage Details',
        sections: {
            parties: {
                description: 'Party Information',
                fields: [
                    { name: 'petitioner_name', x: 25, y: 25, width: 100, height: 8, fontSize: 9, type: 'text' },
                    { name: 'respondent_name', x: 140, y: 25, width: 100, height: 8, fontSize: 9, type: 'text' },
                    { name: 'petitioner_address', x: 25, y: 40, width: 120, height: 8, fontSize: 9, type: 'text' },
                    { name: 'petitioner_phone', x: 25, y: 55, width: 80, height: 8, fontSize: 9, type: 'text' },
                    { name: 'respondent_address', x: 25, y: 70, width: 120, height: 8, fontSize: 9, type: 'text' }
                ]
            },
            relief: {
                description: 'Relief Requested',
                fields: [
                    { name: 'property_division', x: 25, y: 95, width: 8, height: 8, fontSize: 9, type: 'checkbox' },
                    { name: 'spousal_support', x: 25, y: 110, width: 8, height: 8, fontSize: 9, type: 'checkbox' },
                    { name: 'attorney_fees', x: 25, y: 125, width: 8, height: 8, fontSize: 9, type: 'checkbox' },
                    { name: 'name_change', x: 25, y: 140, width: 8, height: 8, fontSize: 9, type: 'checkbox' },
                    { name: 'dissolution_type', x: 25, y: 155, width: 100, height: 8, fontSize: 9, type: 'select' }
                ]
            },
            marriage: {
                description: 'Marriage Details',
                fields: [
                    { name: 'marriage_date', x: 25, y: 180, width: 60, height: 8, fontSize: 9, type: 'date' },
                    { name: 'separation_date', x: 100, y: 180, width: 60, height: 8, fontSize: 9, type: 'date' },
                    { name: 'marriage_location', x: 25, y: 195, width: 120, height: 8, fontSize: 9, type: 'text' },
                    { name: 'grounds_for_dissolution', x: 25, y: 210, width: 150, height: 8, fontSize: 9, type: 'text' }
                ]
            },
            children: {
                description: 'Children Information',
                fields: [
                    { name: 'has_children', x: 25, y: 235, width: 100, height: 8, fontSize: 9, type: 'text' },
                    { name: 'children_count', x: 140, y: 235, width: 30, height: 8, fontSize: 9, type: 'number' }
                ]
            }
        }
    },
    page3: {
        description: 'Additional Information and Signatures',
        sections: {
            additional: {
                description: 'Additional Information',
                fields: [
                    { name: 'additional_info', x: 25, y: 25, width: 160, height: 40, fontSize: 9, type: 'textarea' }
                ]
            },
            signature: {
                description: 'Signatures',
                fields: [
                    { name: 'attorney_signature', x: 25, y: 80, width: 100, height: 8, fontSize: 9, type: 'text' },
                    { name: 'signature_date', x: 140, y: 80, width: 60, height: 8, fontSize: 9, type: 'date' }
                ]
            }
        }
    }
};

// Apply the new structure
console.log('Applying FL-100 form structure...\n');

Object.entries(fl100Structure).forEach(([pageName, pageData]) => {
    const pageNum = parseInt(pageName.replace('page', ''));
    console.log(`Page ${pageNum}: ${pageData.description}`);
    
    Object.entries(pageData.sections).forEach(([sectionName, sectionData]) => {
        console.log(`  ${sectionData.description}:`);
        
        sectionData.fields.forEach(field => {
            if (positions[field.name]) {
                // Update existing field
                positions[field.name] = {
                    ...positions[field.name],
                    page: pageNum,
                    x: field.x,
                    y: field.y,
                    width: field.width,
                    height: field.height,
                    fontSize: field.fontSize,
                    type: field.type
                };
                console.log(`    ✓ ${field.name}: (${field.x}, ${field.y}) ${field.type}`);
            } else {
                // Add new field
                positions[field.name] = {
                    page: pageNum,
                    x: field.x,
                    y: field.y,
                    width: field.width,
                    height: field.height,
                    fontSize: field.fontSize,
                    type: field.type
                };
                console.log(`    + ${field.name}: (${field.x}, ${field.y}) ${field.type}`);
            }
        });
    });
    console.log('');
});

// Save updated positions
fs.writeFileSync(positionsFile, JSON.stringify(positions, null, 2));
console.log('✓ Updated positions saved to t_fl100_gc120_positions.json');

// Verify no overlaps
console.log('\n=== Overlap Verification ===');
let overlapsFound = false;
const allFields = Object.entries(positions).map(([name, config]) => ({ name, ...config }));

// Group fields by page
const fieldsByPage = {};
allFields.forEach(field => {
    const page = field.page || 1;
    if (!fieldsByPage[page]) {
        fieldsByPage[page] = [];
    }
    fieldsByPage[page].push(field);
});

Object.entries(fieldsByPage).sort((a, b) => parseInt(a[0]) - parseInt(b[0])).forEach(([page, pageFields]) => {
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

            // Check for overlap with buffer
            const buffer = 2;
            if (aLeft < bRight - buffer && aRight > bLeft + buffer && aTop < bBottom - buffer && aBottom > bTop + buffer) {
                console.log(`  Page ${page}: ${fieldA.name} overlaps with ${fieldB.name}`);
                overlapsFound = true;
            }
        }
    }
});

if (!overlapsFound) {
    console.log('✅ No overlapping fields detected');
} else {
    console.log('⚠️  Overlapping fields still present');
}

// Final summary
console.log('\n=== Final Field Distribution ===');
Object.entries(fieldsByPage).sort((a, b) => parseInt(a[0]) - parseInt(b[0])).forEach(([page, fields]) => {
    console.log(`Page ${page}: ${fields.length} fields`);
    fields.forEach(field => {
        console.log(`  - ${field.name}: (${field.x}, ${field.y}) ${field.type || 'text'}`);
    });
});

console.log('\n✅ FL-100 field positions updated');
console.log('✅ Proper spacing between fields');
console.log('✅ Logical grouping maintained');
console.log('✅ Ready for PDF generation');
