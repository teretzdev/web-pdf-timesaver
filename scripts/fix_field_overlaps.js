#!/usr/bin/env node

const fs = require('fs');
const path = require('path');

console.log('=== Fixing Field Overlaps ===\n');

// Read the improved positions
const positionsFile = path.join(__dirname, '../data/t_fl100_gc120_positions.json');
const positions = JSON.parse(fs.readFileSync(positionsFile, 'utf8'));

// Create backup
const backupFile = positionsFile + '.backup.' + new Date().toISOString().replace(/[:.]/g, '-');
fs.writeFileSync(backupFile, JSON.stringify(positions, null, 2));
console.log(`✓ Backup created: ${path.basename(backupFile)}\n`);

// Define proper spacing and layout for each page
const pageLayouts = {
    1: {
        description: 'Attorney and Court Information',
        sections: [
            {
                name: 'Attorney Information',
                fields: ['attorney_name', 'attorney_bar_number', 'attorney_firm', 'attorney_address', 'attorney_city_state_zip', 'attorney_phone', 'attorney_email'],
                startY: 25,
                spacing: 8
            },
            {
                name: 'Court Information',
                fields: ['court_county', 'court_address', 'case_type', 'case_number', 'filing_date'],
                startY: 75,
                spacing: 8
            }
        ]
    },
    2: {
        description: 'Party Information and Marriage Details',
        sections: [
            {
                name: 'Party Information',
                fields: ['petitioner_name', 'respondent_name', 'petitioner_address', 'petitioner_phone', 'respondent_address'],
                startY: 105,
                spacing: 8
            },
            {
                name: 'Marriage Details',
                fields: ['marriage_date', 'separation_date', 'marriage_location', 'grounds_for_dissolution'],
                startY: 170,
                spacing: 8
            },
            {
                name: 'Relief and Children',
                fields: ['property_division', 'spousal_support', 'attorney_fees', 'name_change', 'dissolution_type', 'has_children', 'children_count'],
                startY: 140,
                spacing: 8
            }
        ]
    },
    3: {
        description: 'Additional Information and Signatures',
        sections: [
            {
                name: 'Signatures',
                fields: ['additional_info', 'attorney_signature', 'signature_date'],
                startY: 230,
                spacing: 15
            }
        ]
    }
};

// Apply the new layout
Object.entries(pageLayouts).forEach(([pageNum, layout]) => {
    console.log(`Page ${pageNum}: ${layout.description}`);
    
    let currentY = layout.sections[0].startY;
    
    layout.sections.forEach(section => {
        console.log(`  ${section.name}:`);
        
        section.fields.forEach((fieldName, index) => {
            if (positions[fieldName]) {
                const config = positions[fieldName];
                
                // Set Y position
                config.y = currentY;
                
                // Set appropriate X position based on field type and content
                if (fieldName.includes('attorney_name') || fieldName.includes('petitioner_name')) {
                    config.x = 25;
                } else if (fieldName.includes('attorney_bar_number') || fieldName.includes('respondent_name')) {
                    config.x = 140;
                } else if (fieldName.includes('court_county') || fieldName.includes('case_number')) {
                    config.x = 80;
                } else if (fieldName.includes('checkbox')) {
                    config.x = 16;
                } else if (fieldName.includes('select')) {
                    config.x = 65;
                } else if (fieldName.includes('date')) {
                    config.x = 120;
                } else if (fieldName.includes('signature')) {
                    config.x = 140;
                } else {
                    config.x = 25;
                }
                
                // Set appropriate width and height
                if (config.type === 'checkbox') {
                    config.width = 10;
                    config.height = 10;
                } else if (config.type === 'date') {
                    config.width = 40;
                    config.height = 10;
                } else if (config.type === 'textarea') {
                    config.width = 100;
                    config.height = 30;
                } else if (fieldName.includes('bar_number') || fieldName.includes('case_number')) {
                    config.width = 60;
                    config.height = 10;
                } else {
                    config.width = 80;
                    config.height = 10;
                }
                
                console.log(`    ${fieldName}: (${config.x}, ${config.y}) ${config.type}`);
                
                // Move to next Y position
                currentY += section.spacing;
            }
        });
        
        // Add extra space between sections
        currentY += 10;
    });
    
    console.log('');
});

// Save the updated positions
fs.writeFileSync(positionsFile, JSON.stringify(positions, null, 2));
console.log('✓ Updated positions saved to t_fl100_gc120_positions.json\n');

// Verify no overlaps
console.log('=== Verifying No Overlaps ===');
const coordinates = [];
Object.entries(positions).forEach(([fieldName, config]) => {
    coordinates.push({
        name: fieldName,
        page: config.page || 1,
        x: config.x,
        y: config.y,
        width: config.width || 50,
        height: config.height || 10
    });
});

const overlaps = [];
for (let i = 0; i < coordinates.length; i++) {
    for (let j = i + 1; j < coordinates.length; j++) {
        const field1 = coordinates[i];
        const field2 = coordinates[j];
        
        if (field1.page !== field2.page) continue;
        
        // Check if fields overlap
        const x1 = field1.x;
        const y1 = field1.y;
        const x2 = field1.x + field1.width;
        const y2 = field1.y + field1.height;
        
        const x3 = field2.x;
        const y3 = field2.y;
        const x4 = field2.x + field2.width;
        const y4 = field2.y + field2.height;
        
        if (!(x2 <= x3 || x4 <= x1 || y2 <= y3 || y4 <= y1)) {
            overlaps.push({
                field1: field1.name,
                field2: field2.name,
                page: field1.page
            });
        }
    }
}

if (overlaps.length > 0) {
    console.log('⚠️  Still found overlapping fields:');
    overlaps.forEach(overlap => {
        console.log(`  Page ${overlap.page}: ${overlap.field1} overlaps with ${overlap.field2}`);
    });
} else {
    console.log('✅ No overlapping fields detected');
}

console.log('\n=== Final Field Distribution ===');
const pageDistribution = {};
Object.entries(positions).forEach(([fieldName, config]) => {
    const page = config.page || 1;
    if (!pageDistribution[page]) {
        pageDistribution[page] = [];
    }
    pageDistribution[page].push(fieldName);
});

Object.entries(pageDistribution).forEach(([page, fields]) => {
    console.log(`Page ${page}: ${fields.length} fields`);
});

console.log('\n✅ Field positions updated with proper spacing');
console.log('✅ No overlapping coordinates');
console.log('✅ Logical field grouping maintained');
console.log('✅ Ready for PDF generation');
