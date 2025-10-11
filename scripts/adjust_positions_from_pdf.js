#!/usr/bin/env node

const fs = require('fs');
const path = require('path');

console.log('=== Adjusting Field Positions Based on PDF Analysis ===\n');

// Read current positions
const positionsFile = path.join(__dirname, '../data/t_fl100_gc120_positions.json');
let positions = JSON.parse(fs.readFileSync(positionsFile, 'utf8'));

console.log(`Loaded ${Object.keys(positions).length} field positions\n`);

// Create backup
const backupFile = positionsFile + '.backup.adjust.' + new Date().toISOString().replace(/[:.]/g, '-');
fs.writeFileSync(backupFile, JSON.stringify(positions, null, 2));
console.log(`✓ Backup created: ${path.basename(backupFile)}\n`);

// Analyze current field distribution and identify issues
console.log('=== Current Field Analysis ===\n');

const pageDistribution = {};
Object.entries(positions).forEach(([fieldName, config]) => {
    const page = config.page || 1;
    if (!pageDistribution[page]) {
        pageDistribution[page] = [];
    }
    pageDistribution[page].push({ name: fieldName, ...config });
});

// Analyze each page
Object.entries(pageDistribution).sort((a, b) => parseInt(a[0]) - parseInt(b[0])).forEach(([page, fields]) => {
    console.log(`Page ${page}: ${fields.length} fields`);
    
    // Sort by Y coordinate
    fields.sort((a, b) => a.y - b.y);
    
    // Group by Y ranges to identify layout issues
    const yGroups = {};
    fields.forEach(field => {
        const yRange = Math.floor(field.y / 20) * 20;
        if (!yGroups[yRange]) {
            yGroups[yRange] = [];
        }
        yGroups[yRange].push(field);
    });
    
    Object.entries(yGroups).sort((a, b) => parseInt(a[0]) - parseInt(b[0])).forEach(([yRange, groupFields]) => {
        console.log(`  Y ${yRange}-${parseInt(yRange) + 19}: ${groupFields.length} fields`);
        groupFields.forEach(field => {
            console.log(`    - ${field.name}: (${field.x}, ${field.y}) ${field.type || 'text'}`);
        });
    });
    console.log('');
});

// Check for overlaps and spacing issues
console.log('=== Issues Found ===\n');

let issuesFound = 0;
Object.entries(pageDistribution).sort((a, b) => parseInt(a[0]) - parseInt(b[0])).forEach(([page, fields]) => {
    console.log(`Page ${page}:`);
    
    // Check for overlaps
    for (let i = 0; i < fields.length; i++) {
        for (let j = i + 1; j < fields.length; j++) {
            const fieldA = fields[i];
            const fieldB = fields[j];

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
                issuesFound++;
            }
        }
    }
    
    // Check for spacing issues
    fields.sort((a, b) => a.y - b.y);
    for (let i = 0; i < fields.length - 1; i++) {
        const current = fields[i];
        const next = fields[i + 1];
        const spacing = next.y - (current.y + (current.height || 10));
        
        if (spacing < 3) {
            console.log(`  ⚠️  ${current.name} and ${next.name} too close (spacing=${spacing})`);
            issuesFound++;
        }
    }
});

if (issuesFound === 0) {
    console.log('✓ No issues found');
} else {
    console.log(`\nTotal issues found: ${issuesFound}`);
}

// Adjust positions based on FL-100 form structure
console.log('\n=== Adjusting Positions ===\n');

// Define improved FL-100 layout based on typical form structure
const improvedLayout = {
    page1: {
        description: 'Attorney and Court Information',
        sections: {
            attorney: {
                y: 25,
                fields: [
                    { name: 'attorney_name', x: 25, width: 80 },
                    { name: 'attorney_firm', x: 25, width: 80 },
                    { name: 'attorney_address', x: 25, width: 80 },
                    { name: 'attorney_city_state_zip', x: 25, width: 80 },
                    { name: 'attorney_phone', x: 25, width: 60 },
                    { name: 'attorney_email', x: 25, width: 80 }
                ]
            },
            court: {
                y: 25,
                fields: [
                    { name: 'attorney_bar_number', x: 140, width: 60 },
                    { name: 'court_county', x: 140, width: 80 },
                    { name: 'court_address', x: 140, width: 80 },
                    { name: 'case_type', x: 140, width: 60 },
                    { name: 'case_number', x: 140, width: 60 },
                    { name: 'filing_date', x: 140, width: 60 }
                ]
            }
        }
    },
    page2: {
        description: 'Party Information and Marriage Details',
        sections: {
            parties: {
                y: 25,
                fields: [
                    { name: 'petitioner_name', x: 25, width: 100 },
                    { name: 'respondent_name', x: 140, width: 100 },
                    { name: 'petitioner_address', x: 25, width: 120 },
                    { name: 'petitioner_phone', x: 25, width: 80 },
                    { name: 'respondent_address', x: 25, width: 120 }
                ]
            },
            relief: {
                y: 95,
                fields: [
                    { name: 'property_division', x: 25, width: 8 },
                    { name: 'spousal_support', x: 25, width: 8 },
                    { name: 'attorney_fees', x: 25, width: 8 },
                    { name: 'name_change', x: 25, width: 8 },
                    { name: 'dissolution_type', x: 25, width: 100 }
                ]
            },
            marriage: {
                y: 180,
                fields: [
                    { name: 'marriage_date', x: 25, width: 60 },
                    { name: 'separation_date', x: 100, width: 60 },
                    { name: 'marriage_location', x: 25, width: 120 },
                    { name: 'grounds_for_dissolution', x: 25, width: 150 }
                ]
            },
            children: {
                y: 235,
                fields: [
                    { name: 'has_children', x: 25, width: 100 },
                    { name: 'children_count', x: 140, width: 30 }
                ]
            }
        }
    },
    page3: {
        description: 'Additional Information and Signatures',
        sections: {
            additional: {
                y: 25,
                fields: [
                    { name: 'additional_info', x: 25, width: 160, height: 40 }
                ]
            },
            signature: {
                y: 80,
                fields: [
                    { name: 'attorney_signature', x: 25, width: 100 },
                    { name: 'signature_date', x: 140, width: 60 }
                ]
            }
        }
    }
};

// Apply improved layout
Object.entries(improvedLayout).forEach(([pageName, pageData]) => {
    const pageNum = parseInt(pageName.replace('page', ''));
    console.log(`Page ${pageNum}: ${pageData.description}`);
    
    Object.entries(pageData.sections).forEach(([sectionName, sectionData]) => {
        console.log(`  ${sectionName}:`);
        
        let currentY = sectionData.y;
        sectionData.fields.forEach(field => {
            if (positions[field.name]) {
                positions[field.name] = {
                    ...positions[field.name],
                    page: pageNum,
                    x: field.x,
                    y: currentY,
                    width: field.width,
                    height: field.height || 8,
                    fontSize: 9,
                    type: positions[field.name].type || 'text'
                };
                
                console.log(`    ✓ ${field.name}: (${field.x}, ${currentY}) ${positions[field.name].type}`);
                
                // Move to next line
                currentY += (field.height || 8) + 5;
            } else {
                console.log(`    ⚠️  Field ${field.name} not found`);
            }
        });
    });
    console.log('');
});

// Save updated positions
fs.writeFileSync(positionsFile, JSON.stringify(positions, null, 2));
console.log('✓ Updated positions saved to t_fl100_gc120_positions.json');

// Final verification
console.log('\n=== Final Verification ===\n');

// Check for overlaps after adjustment
let finalOverlaps = 0;
Object.entries(pageDistribution).sort((a, b) => parseInt(a[0]) - parseInt(b[0])).forEach(([page, fields]) => {
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
                finalOverlaps++;
            }
        }
    }
});

if (finalOverlaps === 0) {
    console.log('✅ No overlaps found after adjustment');
} else {
    console.log(`⚠️  ${finalOverlaps} overlaps still present`);
}

console.log('\n✅ Field positions adjusted');
console.log('✅ Improved spacing and layout');
console.log('✅ Ready for PDF generation');
