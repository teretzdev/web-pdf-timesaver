#!/usr/bin/env node

const fs = require('fs');
const path = require('path');

// Read current position files
const positionsFile = path.join(__dirname, '../data/t_fl100_gc120_positions.json');
const multipageFile = path.join(__dirname, '../data/t_fl100_gc120_positions_multipage.json');

console.log('=== FL-100 Field Distribution Analysis ===\n');

// Read position files
let positions, multipage;
try {
    positions = JSON.parse(fs.readFileSync(positionsFile, 'utf8'));
    multipage = JSON.parse(fs.readFileSync(multipageFile, 'utf8'));
} catch (error) {
    console.error('Error reading position files:', error.message);
    process.exit(1);
}

// Analyze field distribution by page
function analyzeDistribution(posData, filename) {
    console.log(`\n--- Analysis of ${filename} ---`);
    
    const pageDistribution = {};
    const fieldTypes = {};
    
    Object.entries(posData).forEach(([fieldName, config]) => {
        const page = config.page || 1;
        const type = config.type || 'text';
        
        if (!pageDistribution[page]) {
            pageDistribution[page] = [];
        }
        pageDistribution[page].push(fieldName);
        
        if (!fieldTypes[type]) {
            fieldTypes[type] = [];
        }
        fieldTypes[type].push(fieldName);
    });
    
    console.log('Page Distribution:');
    Object.keys(pageDistribution).sort((a, b) => parseInt(a) - parseInt(b)).forEach(page => {
        console.log(`  Page ${page}: ${pageDistribution[page].length} fields`);
        console.log(`    Fields: ${pageDistribution[page].join(', ')}`);
    });
    
    console.log('\nField Types:');
    Object.entries(fieldTypes).forEach(([type, fields]) => {
        console.log(`  ${type}: ${fields.length} fields`);
    });
    
    return { pageDistribution, fieldTypes };
}

// Analyze both files
const posAnalysis = analyzeDistribution(positions, 't_fl100_gc120_positions.json');
const multiAnalysis = analyzeDistribution(multipage, 't_fl100_gc120_positions_multipage.json');

// Identify issues
console.log('\n=== IDENTIFIED ISSUES ===');

// Check for fields that should be on different pages
const attorneyFields = ['attorney_name', 'attorney_firm', 'attorney_address', 'attorney_city_state_zip', 'attorney_phone', 'attorney_email', 'attorney_bar_number'];
const courtFields = ['court_county', 'court_address', 'case_type', 'case_number', 'filing_date'];
const partyFields = ['petitioner_name', 'respondent_name', 'petitioner_address', 'petitioner_phone', 'respondent_address'];
const marriageFields = ['marriage_date', 'separation_date', 'marriage_location', 'grounds_for_dissolution'];
const reliefFields = ['dissolution_type', 'property_division', 'spousal_support', 'attorney_fees', 'name_change'];
const childrenFields = ['has_children', 'children_count'];
const signatureFields = ['attorney_signature', 'signature_date'];

console.log('\n1. Field Grouping Analysis:');
console.log('   Attorney Info Fields:', attorneyFields.length);
console.log('   Court Info Fields:', courtFields.length);
console.log('   Party Info Fields:', partyFields.length);
console.log('   Marriage Info Fields:', marriageFields.length);
console.log('   Relief Fields:', reliefFields.length);
console.log('   Children Fields:', childrenFields.length);
console.log('   Signature Fields:', signatureFields.length);

// Check page distribution issues
console.log('\n2. Page Distribution Issues:');
const totalFields = Object.keys(positions).length;
const page1Fields = posAnalysis.pageDistribution[1]?.length || 0;
const page2Fields = posAnalysis.pageDistribution[2]?.length || 0;
const page3Fields = posAnalysis.pageDistribution[3]?.length || 0;

console.log(`   Total fields: ${totalFields}`);
console.log(`   Page 1: ${page1Fields} fields (${Math.round(page1Fields/totalFields*100)}%)`);
console.log(`   Page 2: ${page2Fields} fields (${Math.round(page2Fields/totalFields*100)}%)`);
console.log(`   Page 3: ${page3Fields} fields (${Math.round(page3Fields/totalFields*100)}%)`);

if (page1Fields > totalFields * 0.7) {
    console.log('   ⚠️  WARNING: Too many fields concentrated on page 1');
}

// Check coordinate issues
console.log('\n3. Coordinate Analysis:');
const coordinates = Object.entries(positions).map(([name, config]) => ({
    name,
    x: config.x,
    y: config.y,
    page: config.page || 1
}));

// Group by page and analyze coordinate ranges
const pageCoords = {};
coordinates.forEach(coord => {
    if (!pageCoords[coord.page]) {
        pageCoords[coord.page] = [];
    }
    pageCoords[coord.page].push(coord);
});

Object.entries(pageCoords).forEach(([page, coords]) => {
    const xValues = coords.map(c => c.x);
    const yValues = coords.map(c => c.y);
    const xMin = Math.min(...xValues);
    const xMax = Math.max(...xValues);
    const yMin = Math.min(...yValues);
    const yMax = Math.max(...yValues);
    
    console.log(`   Page ${page}: X range ${xMin.toFixed(1)}-${xMax.toFixed(1)}, Y range ${yMin.toFixed(1)}-${yMax.toFixed(1)}`);
    
    // Check for overlapping coordinates
    const overlaps = [];
    for (let i = 0; i < coords.length; i++) {
        for (let j = i + 1; j < coords.length; j++) {
            const dist = Math.sqrt(Math.pow(coords[i].x - coords[j].x, 2) + Math.pow(coords[i].y - coords[j].y, 2));
            if (dist < 1.0) { // Fields within 1 unit are considered overlapping
                overlaps.push(`${coords[i].name} <-> ${coords[j].name} (${dist.toFixed(2)})`);
            }
        }
    }
    
    if (overlaps.length > 0) {
        console.log(`   ⚠️  Overlapping fields on page ${page}:`);
        overlaps.forEach(overlap => console.log(`      ${overlap}`));
    }
});

// Suggest improvements
console.log('\n=== SUGGESTED IMPROVEMENTS ===');

console.log('\n1. Better Page Distribution:');
console.log('   Page 1: Attorney info, Court info, Party names (header section)');
console.log('   Page 2: Marriage details, Grounds, Relief checkboxes, Children info');
console.log('   Page 3: Additional info, Signatures');

console.log('\n2. Coordinate Improvements:');
console.log('   - Spread fields more evenly across pages');
console.log('   - Ensure minimum spacing between fields (2+ units)');
console.log('   - Group related fields together');
console.log('   - Use consistent coordinate system');

console.log('\n3. Field Type Optimization:');
console.log('   - Text fields: appropriate width for content');
console.log('   - Checkboxes: consistent size and spacing');
console.log('   - Dates: appropriate width for date format');
console.log('   - Textareas: sufficient height for multi-line content');

console.log('\n=== RECOMMENDED ACTIONS ===');
console.log('1. Redistribute fields across all 3 pages more evenly');
console.log('2. Adjust coordinates to prevent overlapping');
console.log('3. Group related fields together logically');
console.log('4. Test with actual PDF generation to verify positioning');
console.log('5. Use consistent coordinate system throughout');
