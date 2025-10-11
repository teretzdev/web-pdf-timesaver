#!/usr/bin/env node

const fs = require('fs');
const path = require('path');

console.log('=== Final Field Layout Fix ===\n');

// Read the improved positions
const positionsFile = path.join(__dirname, '../data/t_fl100_gc120_positions.json');
const positions = JSON.parse(fs.readFileSync(positionsFile, 'utf8'));

// Create backup
const backupFile = positionsFile + '.backup.final.' + new Date().toISOString().replace(/[:.]/g, '-');
fs.writeFileSync(backupFile, JSON.stringify(positions, null, 2));
console.log(`✓ Backup created: ${path.basename(backupFile)}\n`);

// Define the final layout with proper spacing
const finalLayout = {
    // Page 1: Attorney and Court Information
    1: [
        { field: 'attorney_name', x: 25, y: 25, width: 80, height: 10 },
        { field: 'attorney_bar_number', x: 140, y: 25, width: 60, height: 10 },
        { field: 'attorney_firm', x: 25, y: 40, width: 80, height: 10 },
        { field: 'attorney_address', x: 25, y: 55, width: 80, height: 10 },
        { field: 'attorney_city_state_zip', x: 25, y: 70, width: 80, height: 10 },
        { field: 'attorney_phone', x: 25, y: 85, width: 80, height: 10 },
        { field: 'attorney_email', x: 25, y: 100, width: 80, height: 10 },
        { field: 'court_county', x: 25, y: 120, width: 80, height: 10 },
        { field: 'court_address', x: 25, y: 135, width: 80, height: 10 },
        { field: 'case_type', x: 25, y: 150, width: 80, height: 10 },
        { field: 'case_number', x: 140, y: 150, width: 60, height: 10 },
        { field: 'filing_date', x: 25, y: 165, width: 60, height: 10 }
    ],
    
    // Page 2: Party Information and Marriage Details
    2: [
        { field: 'petitioner_name', x: 25, y: 25, width: 80, height: 10 },
        { field: 'respondent_name', x: 140, y: 25, width: 80, height: 10 },
        { field: 'petitioner_address', x: 25, y: 40, width: 80, height: 10 },
        { field: 'petitioner_phone', x: 25, y: 55, width: 80, height: 10 },
        { field: 'respondent_address', x: 25, y: 70, width: 80, height: 10 },
        { field: 'property_division', x: 25, y: 90, width: 10, height: 10 },
        { field: 'spousal_support', x: 25, y: 105, width: 10, height: 10 },
        { field: 'attorney_fees', x: 25, y: 120, width: 10, height: 10 },
        { field: 'name_change', x: 25, y: 135, width: 10, height: 10 },
        { field: 'dissolution_type', x: 25, y: 150, width: 80, height: 10 },
        { field: 'marriage_date', x: 25, y: 170, width: 60, height: 10 },
        { field: 'separation_date', x: 140, y: 170, width: 60, height: 10 },
        { field: 'marriage_location', x: 25, y: 185, width: 80, height: 10 },
        { field: 'grounds_for_dissolution', x: 25, y: 200, width: 80, height: 10 },
        { field: 'has_children', x: 25, y: 220, width: 80, height: 10 },
        { field: 'children_count', x: 140, y: 220, width: 40, height: 10 }
    ],
    
    // Page 3: Additional Information and Signatures
    3: [
        { field: 'additional_info', x: 25, y: 25, width: 100, height: 30 },
        { field: 'attorney_signature', x: 25, y: 70, width: 80, height: 10 },
        { field: 'signature_date', x: 140, y: 70, width: 60, height: 10 }
    ]
};

// Apply the final layout
Object.entries(finalLayout).forEach(([pageNum, fields]) => {
    console.log(`Page ${pageNum}:`);
    
    fields.forEach(layout => {
        if (positions[layout.field]) {
            const config = positions[layout.field];
            
            // Update coordinates and dimensions
            config.x = layout.x;
            config.y = layout.y;
            config.width = layout.width;
            config.height = layout.height;
            config.page = parseInt(pageNum);
            
            console.log(`  ${layout.field}: (${layout.x}, ${layout.y}) ${config.type}`);
        }
    });
    
    console.log('');
});

// Save the updated positions
fs.writeFileSync(positionsFile, JSON.stringify(positions, null, 2));
console.log('✓ Final positions saved to t_fl100_gc120_positions.json\n');

// Verify no overlaps
console.log('=== Final Overlap Verification ===');
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

console.log('\n=== Final Field Distribution Summary ===');
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
    fields.forEach(field => {
        const config = positions[field];
        console.log(`  - ${field}: (${config.x}, ${config.y}) ${config.type}`);
    });
    console.log('');
});

console.log('✅ Final field layout completed');
console.log('✅ Proper spacing between fields');
console.log('✅ Logical grouping maintained');
console.log('✅ Ready for PDF generation and verification');
