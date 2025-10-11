#!/usr/bin/env node

const fs = require('fs');
const path = require('path');

// Read current positions
const positionsFile = path.join(__dirname, '../data/t_fl100_gc120_positions.json');
const positions = JSON.parse(fs.readFileSync(positionsFile, 'utf8'));

console.log('=== FL-100 Field Position Fixer ===\n');

// Define field groups and their ideal page distribution
const fieldGroups = {
    // Page 1: Header information (top of form)
    page1: {
        description: 'Attorney and Court Information',
        fields: [
            'attorney_name', 'attorney_bar_number', 'attorney_firm', 
            'attorney_address', 'attorney_city_state_zip', 'attorney_phone', 'attorney_email',
            'court_county', 'court_address', 'case_type', 'case_number', 'filing_date'
        ],
        coordinates: {
            // Attorney section (left side)
            attorney_name: { x: 8, y: 28, width: 95, fontSize: 9, type: 'text', fontStyle: 'B' },
            attorney_bar_number: { x: 140, y: 28, width: 40, fontSize: 9, type: 'text' },
            attorney_firm: { x: 8, y: 33, width: 95, fontSize: 9, type: 'text' },
            attorney_address: { x: 8, y: 38, width: 95, fontSize: 9, type: 'text' },
            attorney_city_state_zip: { x: 8, y: 43, width: 95, fontSize: 9, type: 'text' },
            attorney_phone: { x: 8, y: 48, width: 60, fontSize: 9, type: 'text' },
            attorney_email: { x: 8, y: 58, width: 95, fontSize: 9, type: 'text' },
            
            // Court section (center-right)
            court_county: { x: 80, y: 77, width: 65, fontSize: 10, fontStyle: 'B', type: 'text' },
            court_address: { x: 25, y: 84, width: 110, fontSize: 8, type: 'text' },
            case_type: { x: 25, y: 94, width: 90, fontSize: 7, type: 'text' },
            filing_date: { x: 120, y: 94, width: 35, fontSize: 7, type: 'text' },
            case_number: { x: 172, y: 119, width: 35, fontSize: 9, fontStyle: 'B', type: 'text' }
        }
    },
    
    // Page 2: Party information and marriage details
    page2: {
        description: 'Party Information and Marriage Details',
        fields: [
            'petitioner_name', 'respondent_name', 'petitioner_address', 'petitioner_phone', 'respondent_address',
            'marriage_date', 'separation_date', 'marriage_location', 'grounds_for_dissolution',
            'dissolution_type', 'property_division', 'spousal_support', 'attorney_fees', 'name_change',
            'has_children', 'children_count'
        ],
        coordinates: {
            // Party information
            petitioner_name: { x: 25, y: 109, width: 125, fontSize: 9, fontStyle: 'B', type: 'text' },
            respondent_name: { x: 25, y: 115, width: 125, fontSize: 9, fontStyle: 'B', type: 'text' },
            petitioner_address: { x: 25, y: 122, width: 125, fontSize: 7, type: 'text' },
            petitioner_phone: { x: 25, y: 127, width: 60, fontSize: 7, type: 'text' },
            respondent_address: { x: 25, y: 134, width: 125, fontSize: 7, type: 'text' },
            
            // Relief checkboxes
            dissolution_type: { x: 65, y: 143, width: 70, fontSize: 8, type: 'select' },
            property_division: { x: 16, y: 143, width: 3.5, height: 3.5, fontSize: 9, type: 'checkbox' },
            spousal_support: { x: 16, y: 150, width: 3.5, height: 3.5, fontSize: 9, type: 'checkbox' },
            attorney_fees: { x: 16, y: 157, width: 3.5, height: 3.5, fontSize: 9, type: 'checkbox' },
            name_change: { x: 51, y: 169, width: 3.5, height: 3.5, fontSize: 9, type: 'checkbox' },
            
            // Marriage details
            marriage_date: { x: 120, y: 177, width: 35, fontSize: 8, type: 'date' },
            separation_date: { x: 165, y: 177, width: 30, fontSize: 8, type: 'date' },
            marriage_location: { x: 85, y: 186, width: 80, fontSize: 7, type: 'text' },
            grounds_for_dissolution: { x: 40, y: 196, width: 100, fontSize: 7, type: 'text' },
            
            // Children information
            has_children: { x: 70, y: 218, width: 20, fontSize: 8, type: 'text' },
            children_count: { x: 90, y: 223, width: 10, fontSize: 8, type: 'number' }
        }
    },
    
    // Page 3: Additional information and signatures
    page3: {
        description: 'Additional Information and Signatures',
        fields: [
            'additional_info', 'attorney_signature', 'signature_date'
        ],
        coordinates: {
            additional_info: { x: 25, y: 240, width: 165, fontSize: 7, type: 'textarea' },
            attorney_signature: { x: 140, y: 240, width: 60, fontSize: 9, fontStyle: 'I', type: 'text' },
            signature_date: { x: 25, y: 235, width: 50, fontSize: 8, type: 'date' }
        }
    }
};

// Create improved positions
const improvedPositions = {};

// Process each page group
Object.entries(fieldGroups).forEach(([pageKey, group]) => {
    const pageNum = parseInt(pageKey.replace('page', ''));
    
    console.log(`Processing ${group.description} (Page ${pageNum})...`);
    
    group.fields.forEach(fieldName => {
        if (positions[fieldName]) {
            // Use improved coordinates if available, otherwise keep original with page update
            const improvedCoord = group.coordinates[fieldName];
            if (improvedCoord) {
                improvedPositions[fieldName] = {
                    ...improvedCoord,
                    page: pageNum
                };
                console.log(`  ✓ ${fieldName}: Page ${pageNum}, X: ${improvedCoord.x}, Y: ${improvedCoord.y}`);
            } else {
                // Keep original coordinates but update page
                improvedPositions[fieldName] = {
                    ...positions[fieldName],
                    page: pageNum
                };
                console.log(`  ✓ ${fieldName}: Page ${pageNum} (original coordinates)`);
            }
        } else {
            console.log(`  ⚠️  Field ${fieldName} not found in original positions`);
        }
    });
});

// Add any fields that weren't in our groups
Object.keys(positions).forEach(fieldName => {
    if (!improvedPositions[fieldName]) {
        console.log(`  ⚠️  Unmapped field: ${fieldName} - keeping original`);
        improvedPositions[fieldName] = positions[fieldName];
    }
});

// Create backup of original file
const backupFile = positionsFile + '.backup.' + new Date().toISOString().replace(/[:.]/g, '-');
fs.writeFileSync(backupFile, JSON.stringify(positions, null, 2));
console.log(`\n✓ Backup created: ${path.basename(backupFile)}`);

// Write improved positions
fs.writeFileSync(positionsFile, JSON.stringify(improvedPositions, null, 2));
console.log(`✓ Improved positions saved to: ${path.basename(positionsFile)}`);

// Generate summary report
console.log('\n=== IMPROVEMENT SUMMARY ===');

const pageCounts = {};
Object.values(improvedPositions).forEach(field => {
    const page = field.page || 1;
    pageCounts[page] = (pageCounts[page] || 0) + 1;
});

console.log('\nNew Page Distribution:');
Object.keys(pageCounts).sort((a, b) => parseInt(a) - parseInt(b)).forEach(page => {
    const count = pageCounts[page];
    const percentage = Math.round((count / Object.keys(improvedPositions).length) * 100);
    console.log(`  Page ${page}: ${count} fields (${percentage}%)`);
});

// Check for coordinate improvements
console.log('\nCoordinate Improvements:');
Object.entries(fieldGroups).forEach(([pageKey, group]) => {
    const pageNum = parseInt(pageKey.replace('page', ''));
    const pageFields = Object.entries(improvedPositions).filter(([name, config]) => config.page === pageNum);
    
    if (pageFields.length > 0) {
        const xValues = pageFields.map(([name, config]) => config.x).filter(x => typeof x === 'number');
        const yValues = pageFields.map(([name, config]) => config.y).filter(y => typeof y === 'number');
        
        if (xValues.length > 0 && yValues.length > 0) {
            const xMin = Math.min(...xValues);
            const xMax = Math.max(...xValues);
            const yMin = Math.min(...yValues);
            const yMax = Math.max(...yValues);
            
            console.log(`  Page ${pageNum}: X range ${xMin.toFixed(1)}-${xMax.toFixed(1)}, Y range ${yMin.toFixed(1)}-${yMax.toFixed(1)}`);
        }
    }
});

console.log('\n=== NEXT STEPS ===');
console.log('1. Test the improved positions with PDF generation');
console.log('2. Verify field alignment in the generated PDF');
console.log('3. Adjust coordinates if needed based on visual inspection');
console.log('4. Update multipage positions file if needed');
console.log('5. Test with both http://draft.clio.com and https://pdftimesavers.desktopmasters.com');

console.log('\n✓ Field position fix completed!');
