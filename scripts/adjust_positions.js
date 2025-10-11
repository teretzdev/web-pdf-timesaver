#!/usr/bin/env node
/**
 * Position Adjustment Script
 * Updates field positions based on analysis results to improve accuracy
 */

const fs = require('fs');
const path = require('path');

class PositionAdjuster {
    constructor() {
        this.projectRoot = path.join(__dirname, '..');
        this.positionsFile = path.join(this.projectRoot, 'data', 't_fl100_gc120_positions.json');
        this.analysisFile = path.join(this.projectRoot, 'output', 'position-analysis-report.json');
    }

    /**
     * Load current positions
     */
    loadPositions() {
        if (!fs.existsSync(this.positionsFile)) {
            throw new Error('Positions file not found: ' + this.positionsFile);
        }
        return JSON.parse(fs.readFileSync(this.positionsFile, 'utf-8'));
    }

    /**
     * Load analysis report
     */
    loadAnalysis() {
        if (!fs.existsSync(this.analysisFile)) {
            throw new Error('Analysis file not found: ' + this.analysisFile);
        }
        return JSON.parse(fs.readFileSync(this.analysisFile, 'utf-8'));
    }

    /**
     * Adjust positions based on analysis
     */
    adjustPositions() {
        const positions = this.loadPositions();
        const analysis = this.loadAnalysis();
        
        console.log('=== Position Adjustment ===');
        console.log(`Current accuracy: ${analysis.summary.accuracy.toFixed(1)}%`);
        console.log(`Fields needing adjustment: ${analysis.summary.misalignedFields}`);
        
        // Create backup
        const backupFile = this.positionsFile + '.backup.' + new Date().toISOString().replace(/[:.]/g, '-');
        fs.writeFileSync(backupFile, JSON.stringify(positions, null, 2));
        console.log(`✓ Backup created: ${backupFile}`);
        
        let adjustmentsMade = 0;
        
        // Adjust missing fields by adding them with estimated positions
        const missingFields = analysis.differences.filter(d => d.status === 'missing');
        
        for (const diff of missingFields) {
            const fieldName = diff.fieldName;
            const expectedPos = diff.expected;
            
            // Add missing field with estimated position
            if (!positions[fieldName]) {
                positions[fieldName] = {
                    page: 1,
                    x: expectedPos.x,
                    y: expectedPos.y,
                    width: this.getFieldWidth(fieldName),
                    fontSize: this.getFieldFontSize(fieldName),
                    type: this.getFieldType(fieldName),
                    fontStyle: this.getFieldFontStyle(fieldName)
                };
                
                console.log(`✓ Added missing field: ${fieldName} at (${expectedPos.x}, ${expectedPos.y})`);
                adjustmentsMade++;
            }
        }
        
        // Fine-tune existing field positions
        const existingFields = Object.keys(positions);
        const adjustments = this.getPositionAdjustments();
        
        for (const [fieldName, adjustment] of Object.entries(adjustments)) {
            if (positions[fieldName]) {
                positions[fieldName].x += adjustment.x;
                positions[fieldName].y += adjustment.y;
                
                console.log(`✓ Adjusted ${fieldName}: x+${adjustment.x}, y+${adjustment.y}`);
                adjustmentsMade++;
            }
        }
        
        // Save updated positions
        fs.writeFileSync(this.positionsFile, JSON.stringify(positions, null, 2));
        
        console.log(`\n=== Adjustment Complete ===`);
        console.log(`✓ ${adjustmentsMade} adjustments made`);
        console.log(`✓ Positions saved to: ${this.positionsFile}`);
        
        return positions;
    }

    /**
     * Get field width based on field type
     */
    getFieldWidth(fieldName) {
        const widthMap = {
            attorney_name: 12.7,
            attorney_firm: 15.2,
            attorney_address: 15.2,
            attorney_city_state_zip: 15.2,
            attorney_phone: 7.6,
            attorney_email: 10.2,
            attorney_bar_number: 6.3,
            case_number: 6.3,
            court_county: 12.7,
            court_address: 15.2,
            case_type: 12.7,
            filing_date: 10.2,
            petitioner_name: 12.7,
            respondent_name: 12.7,
            petitioner_address: 15.2,
            petitioner_phone: 10.2,
            respondent_address: 15.2,
            marriage_date: 10.2,
            separation_date: 10.2,
            marriage_location: 12.7,
            grounds_for_dissolution: 15.2,
            attorney_signature: 12.7,
            signature_date: 10.2,
            children_count: 2.5,
            additional_info: 15.2
        };
        
        return widthMap[fieldName] || 10.0;
    }

    /**
     * Get field font size
     */
    getFieldFontSize(fieldName) {
        const fontSizeMap = {
            attorney_name: 9,
            attorney_firm: 9,
            attorney_address: 9,
            attorney_city_state_zip: 9,
            attorney_phone: 9,
            attorney_email: 9,
            attorney_bar_number: 9,
            case_number: 9,
            court_county: 9,
            court_address: 9,
            case_type: 9,
            filing_date: 9,
            petitioner_name: 9,
            respondent_name: 9,
            petitioner_address: 9,
            petitioner_phone: 9,
            respondent_address: 9,
            marriage_date: 9,
            separation_date: 9,
            marriage_location: 9,
            grounds_for_dissolution: 9,
            attorney_signature: 9,
            signature_date: 9,
            children_count: 9,
            additional_info: 9
        };
        
        return fontSizeMap[fieldName] || 9;
    }

    /**
     * Get field type
     */
    getFieldType(fieldName) {
        const checkboxFields = [
            'dissolution_type', 'property_division', 'spousal_support', 
            'attorney_fees', 'name_change', 'has_children'
        ];
        
        if (checkboxFields.includes(fieldName)) {
            return 'checkbox';
        }
        
        if (fieldName === 'additional_info') {
            return 'textarea';
        }
        
        return 'text';
    }

    /**
     * Get field font style
     */
    getFieldFontStyle(fieldName) {
        const boldFields = ['attorney_name', 'petitioner_name', 'respondent_name'];
        return boldFields.includes(fieldName) ? 'B' : '';
    }

    /**
     * Get position adjustments based on analysis
     */
    getPositionAdjustments() {
        return {
            // Fine-tune positions based on common misalignments
            attorney_name: { x: 0.5, y: 0 },
            attorney_firm: { x: 0.3, y: 0 },
            case_number: { x: -0.2, y: 0 },
            court_county: { x: 0.4, y: 0 },
            petitioner_name: { x: 0.2, y: 0 },
            respondent_name: { x: 0.2, y: 0 }
        };
    }

    /**
     * Generate improved PDF with adjusted positions
     */
    async generateImprovedPdf() {
        console.log('\n=== Generating Improved PDF ===');
        
        const PositionExtractor = require('../lib/position-extractor');
        const extractor = new PositionExtractor();
        
        try {
            // Generate PDF with adjusted positions
            const pdfPath = await extractor.generateTestPdf();
            console.log(`✓ Improved PDF generated: ${pdfPath}`);
            
            // Run analysis on improved PDF
            const result = await extractor.analyzePositions();
            
            if (result.success) {
                console.log(`\n=== Improved Analysis Results ===`);
                console.log(`Accuracy: ${result.comparison.accuracy.toFixed(1)}%`);
                console.log(`Misaligned fields: ${result.comparison.misalignedFields}`);
                
                if (result.comparison.accuracy > 90) {
                    console.log('🎉 Excellent! Accuracy above 90%');
                } else if (result.comparison.accuracy > 75) {
                    console.log('✅ Good! Accuracy above 75%');
                } else {
                    console.log('⚠️  Still needs improvement');
                }
                
                return result;
            } else {
                console.error('❌ Analysis failed:', result.error);
                return null;
            }
            
        } catch (error) {
            console.error('❌ PDF generation failed:', error.message);
            return null;
        }
    }

    /**
     * Main adjustment workflow
     */
    async runAdjustmentWorkflow() {
        try {
            console.log('=== FL-100 Position Adjustment Workflow ===\n');
            
            // Step 1: Adjust positions
            const adjustedPositions = this.adjustPositions();
            
            // Step 2: Generate improved PDF
            const result = await this.generateImprovedPdf();
            
            if (result) {
                console.log('\n=== Workflow Complete ===');
                console.log('✓ Positions adjusted');
                console.log('✓ Improved PDF generated');
                console.log('✓ Analysis completed');
                
                return {
                    success: true,
                    accuracy: result.comparison.accuracy,
                    misalignedFields: result.comparison.misalignedFields,
                    pdfPath: result.pdfPath,
                    reportPath: result.reportPath
                };
            } else {
                return {
                    success: false,
                    error: 'PDF generation or analysis failed'
                };
            }
            
        } catch (error) {
            console.error('❌ Adjustment workflow failed:', error.message);
            return {
                success: false,
                error: error.message
            };
        }
    }
}

// Export for use in other scripts
module.exports = PositionAdjuster;

// Run if called directly
if (require.main === module) {
    const adjuster = new PositionAdjuster();
    adjuster.runAdjustmentWorkflow().then(result => {
        if (result.success) {
            console.log('\n🎉 Position adjustment workflow completed successfully!');
            process.exit(0);
        } else {
            console.error('\n❌ Position adjustment workflow failed:', result.error);
            process.exit(1);
        }
    }).catch(error => {
        console.error('Fatal error:', error);
        process.exit(1);
    });
}
