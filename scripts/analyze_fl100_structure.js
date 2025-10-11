#!/usr/bin/env node

const fs = require('fs');
const path = require('path');

console.log('=== FL-100 Form Structure Analysis ===\n');

// Read current positions
const positionsFile = path.join(__dirname, '../data/t_fl100_gc120_positions.json');
const positions = JSON.parse(fs.readFileSync(positionsFile, 'utf8'));

console.log('Current field positions analysis:\n');

// Analyze field distribution and identify issues
const pageDistribution = {};
const fieldTypes = {};
const coordinates = [];

Object.entries(positions).forEach(([fieldName, config]) => {
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

    coordinates.push({
        name: fieldName,
        page: page,
        x: config.x,
        y: config.y,
        width: config.width,
        height: config.height,
        fontSize: config.fontSize,
        type: type
    });
});

console.log('=== ISSUES IDENTIFIED ===\n');

// Check for common FL-100 form issues
const issues = [];

// 1. Check if fields are too close to page edges
coordinates.forEach(field => {
    if (field.x < 10) {
        issues.push(`Field ${field.name} too close to left edge (x=${field.x})`);
    }
    if (field.y < 10) {
        issues.push(`Field ${field.name} too close to top edge (y=${field.y})`);
    }
    if (field.x + (field.width || 100) > 200) {
        issues.push(`Field ${field.name} too close to right edge (x=${field.x}, width=${field.width || 100})`);
    }
    if (field.y + (field.height || 10) > 280) {
        issues.push(`Field ${field.name} too close to bottom edge (y=${field.y}, height=${field.height || 10})`);
    }
});

// 2. Check for inconsistent spacing
const fieldsByPage = {};
coordinates.forEach(field => {
    if (!fieldsByPage[field.page]) {
        fieldsByPage[field.page] = [];
    }
    fieldsByPage[field.page].push(field);
});

Object.entries(fieldsByPage).forEach(([page, fields]) => {
    // Sort by Y coordinate
    fields.sort((a, b) => a.y - b.y);
    
    for (let i = 0; i < fields.length - 1; i++) {
        const current = fields[i];
        const next = fields[i + 1];
        const spacing = next.y - (current.y + (current.height || 10));
        
        if (spacing < 5) {
            issues.push(`Page ${page}: ${current.name} and ${next.name} too close (spacing=${spacing})`);
        }
        if (spacing > 30) {
            issues.push(`Page ${page}: ${current.name} and ${next.name} too far apart (spacing=${spacing})`);
        }
    }
});

// 3. Check for logical grouping issues
const logicalGroups = {
    'attorney': ['attorney_name', 'attorney_bar_number', 'attorney_firm', 'attorney_address', 'attorney_city_state_zip', 'attorney_phone', 'attorney_email'],
    'court': ['court_county', 'court_address', 'case_type', 'case_number', 'filing_date'],
    'petitioner': ['petitioner_name', 'petitioner_address', 'petitioner_phone'],
    'respondent': ['respondent_name', 'respondent_address'],
    'marriage': ['marriage_date', 'separation_date', 'marriage_location', 'grounds_for_dissolution'],
    'relief': ['property_division', 'spousal_support', 'attorney_fees', 'name_change', 'dissolution_type'],
    'children': ['has_children', 'children_count'],
    'signature': ['attorney_signature', 'signature_date', 'additional_info']
};

Object.entries(logicalGroups).forEach(([groupName, fields]) => {
    const groupFields = fields.filter(field => positions[field]);
    if (groupFields.length === 0) return;
    
    const pages = [...new Set(groupFields.map(field => positions[field].page))];
    if (pages.length > 1) {
        issues.push(`Group '${groupName}' spread across multiple pages: ${pages.join(', ')}`);
    }
    
    // Check if fields in group are properly ordered
    const groupCoords = groupFields.map(field => ({
        name: field,
        ...positions[field]
    })).sort((a, b) => a.y - b.y);
    
    for (let i = 0; i < groupCoords.length - 1; i++) {
        const current = groupCoords[i];
        const next = groupCoords[i + 1];
        const spacing = next.y - (current.y + (current.height || 10));
        
        if (spacing > 20) {
            issues.push(`Group '${groupName}': ${current.name} and ${next.name} too far apart (spacing=${spacing})`);
        }
    }
});

// 4. Check for field type consistency
Object.entries(fieldTypes).forEach(([type, fields]) => {
    if (type === 'checkbox') {
        fields.forEach(field => {
            const config = positions[field];
            if (config.width > 20) {
                issues.push(`Checkbox ${field} has width ${config.width}, should be smaller`);
            }
            if (config.height > 10) {
                issues.push(`Checkbox ${field} has height ${config.height}, should be smaller`);
            }
        });
    }
    
    if (type === 'date') {
        fields.forEach(field => {
            const config = positions[field];
            if (config.width < 40) {
                issues.push(`Date field ${field} has width ${config.width}, should be larger`);
            }
        });
    }
    
    if (type === 'textarea') {
        fields.forEach(field => {
            const config = positions[field];
            if (config.height < 20) {
                issues.push(`Textarea ${field} has height ${config.height}, should be larger`);
            }
        });
    }
});

// Print issues
if (issues.length > 0) {
    console.log('Found ' + issues.length + ' issues:\n');
    issues.forEach((issue, index) => {
        console.log(`${index + 1}. ${issue}`);
    });
} else {
    console.log('No issues found!');
}

console.log('\n=== RECOMMENDATIONS ===\n');

// Generate recommendations based on FL-100 form structure
const recommendations = [];

// Page 1 recommendations
const page1Fields = pageDistribution[1] || [];
if (page1Fields.length > 0) {
    recommendations.push('Page 1 should focus on Attorney and Court information');
    recommendations.push('Attorney fields should be grouped together in the top section');
    recommendations.push('Court fields should be grouped together in the middle section');
    recommendations.push('Case information should be at the bottom');
}

// Page 2 recommendations
const page2Fields = pageDistribution[2] || [];
if (page2Fields.length > 0) {
    recommendations.push('Page 2 should focus on Party information and Marriage details');
    recommendations.push('Petitioner and Respondent information should be grouped together');
    recommendations.push('Relief checkboxes should be grouped together');
    recommendations.push('Marriage details should be in a separate section');
    recommendations.push('Children information should be at the bottom');
}

// Page 3 recommendations
const page3Fields = pageDistribution[3] || [];
if (page3Fields.length > 0) {
    recommendations.push('Page 3 should focus on Additional information and Signatures');
    recommendations.push('Additional info textarea should be at the top');
    recommendations.push('Signatures should be at the bottom');
}

// General recommendations
recommendations.push('Fields should have consistent spacing (10-15 units between fields)');
recommendations.push('Related fields should be grouped together');
recommendations.push('Fields should not be too close to page edges');
recommendations.push('Field sizes should match their expected content');

recommendations.forEach((rec, index) => {
    console.log(`${index + 1}. ${rec}`);
});

console.log('\n=== SUMMARY ===\n');
console.log(`Total fields: ${Object.keys(positions).length}`);
console.log(`Pages: ${Object.keys(pageDistribution).length}`);
console.log(`Issues found: ${issues.length}`);
console.log(`Recommendations: ${recommendations.length}`);

if (issues.length > 0) {
    console.log('\n⚠️  Field positions need adjustment');
} else {
    console.log('\n✅ Field positions look good');
}
