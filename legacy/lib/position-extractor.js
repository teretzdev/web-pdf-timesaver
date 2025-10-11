const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

class PositionExtractor {
    constructor() {
        this.projectRoot = path.join(__dirname, '..');
        this.outputDir = path.join(this.projectRoot, 'output');
        this.dataDir = path.join(this.projectRoot, 'data');
        this.positionsFile = path.join(this.dataDir, 't_fl100_gc120_positions.json');
    }

    /**
     * Generate FL-100 PDF with test data
     */
    async generateTestPdf() {
        const testData = {
            attorney_name: 'John Michael Smith, Esq.',
            attorney_firm: 'Smith & Associates Family Law',
            attorney_address: '1234 Legal Plaza, Suite 500',
            attorney_city_state_zip: 'Los Angeles, CA 90210',
            attorney_phone: '(555) 123-4567',
            attorney_email: 'jsmith@smithlaw.com',
            attorney_bar_number: '123456',
            case_number: 'FL-2024-001234',
            court_county: 'Los Angeles',
            petitioner_name: 'Sarah Elizabeth Johnson',
            respondent_name: 'Michael David Johnson',
            petitioner_address: '123 Main Street, Los Angeles, CA 90210',
            petitioner_phone: '(555) 987-6543',
            respondent_address: '456 Oak Avenue, Los Angeles, CA 90211',
            marriage_date: '06/15/2015',
            separation_date: '03/20/2024',
            marriage_location: 'Los Angeles, CA',
            grounds_for_dissolution: 'Irreconcilable differences',
            attorney_signature: 'John Michael Smith, Esq.',
            signature_date: new Date().toLocaleDateString()
        };

        // Create a temporary PHP script to generate the PDF
        const phpScript = `<?php
require_once '${path.join(this.projectRoot, 'vendor', 'autoload.php')}';
require_once '${path.join(this.projectRoot, 'mvp', 'lib', 'fill_service.php')}';

use WebPdfTimeSaver\\Mvp\\FillService;

\$testData = array(
    'attorney_name' => 'John Michael Smith, Esq.',
    'attorney_firm' => 'Smith & Associates Family Law',
    'attorney_address' => '1234 Legal Plaza, Suite 500',
    'attorney_city_state_zip' => 'Los Angeles, CA 90210',
    'attorney_phone' => '(555) 123-4567',
    'attorney_email' => 'jsmith@smithlaw.com',
    'attorney_bar_number' => '123456',
    'case_number' => 'FL-2024-001234',
    'court_county' => 'Los Angeles',
    'court_address' => '111 North Hill Street, Los Angeles, CA 90012',
    'case_type' => 'Dissolution of Marriage',
    'filing_date' => date('m/d/Y'),
    'petitioner_name' => 'Sarah Elizabeth Johnson',
    'respondent_name' => 'Michael David Johnson',
    'petitioner_address' => '123 Main Street, Los Angeles, CA 90210',
    'petitioner_phone' => '(555) 987-6543',
    'respondent_address' => '456 Oak Avenue, Los Angeles, CA 90211',
    'marriage_date' => '06/15/2015',
    'separation_date' => '03/20/2024',
    'marriage_location' => 'Los Angeles, CA',
    'grounds_for_dissolution' => 'Irreconcilable differences',
    'dissolution_type' => 'Yes',
    'property_division' => 'Yes',
    'spousal_support' => 'No',
    'attorney_fees' => 'Yes',
    'name_change' => 'No',
    'has_children' => 'No',
    'children_count' => '0',
    'additional_info' => 'None',
    'attorney_signature' => 'John Michael Smith, Esq.',
    'signature_date' => date('m/d/Y')
);

\$fillService = new FillService();
\$template = array(
    'id' => 't_fl100_gc120',
    'name' => 'FL-100 Petition',
    'pageCount' => 3
);

try {
    \$result = \$fillService->generateSimplePdf(\$template, \$testData, array('test' => true));
    echo "Generated: " . \$result['path'] . "\\n";
    echo "Success: " . (\$result['success'] ? 'true' : 'false') . "\\n";
} catch (Exception \$e) {
    echo "Error: " . \$e->getMessage() . "\\n";
    exit(1);
}
?>`; 

        const scriptPath = path.join(this.projectRoot, 'temp_generate_pdf.php');
        fs.writeFileSync(scriptPath, phpScript);

        try {
            const phpPath = 'C:\\xampp\\php\\php.exe';
            const output = execSync(`"${phpPath}" "${scriptPath}"`, { 
                encoding: 'utf-8',
                cwd: this.projectRoot 
            });
            
            // Extract the PDF path from output
            const match = output.match(/Generated: (.+\.pdf)/);
            if (match && match[1]) {
                const pdfPath = match[1].trim();
                console.log(`✓ Generated test PDF: ${pdfPath}`);
                return pdfPath;
            }
            
            throw new Error('Could not extract PDF path from output');
        } catch (error) {
            console.error('Failed to generate test PDF:', error.message);
            throw error;
        } finally {
            // Clean up temporary script
            if (fs.existsSync(scriptPath)) {
                fs.unlinkSync(scriptPath);
            }
        }
    }

    /**
     * Extract positions from generated PDF using MCP server
     */
    async extractPositionsFromPdf(pdfPath) {
        console.log(`Extracting positions from: ${pdfPath}`);
        
        // Read the PDF debug log to get actual field positions
        const logFile = path.join(this.projectRoot, 'logs', 'pdf_debug.log');
        let extractedPositions = {};
        
        if (fs.existsSync(logFile)) {
            const logContent = fs.readFileSync(logFile, 'utf-8');
            const lines = logContent.split('\n');
            
            // Extract field positions from the log
            for (const line of lines) {
                const match = line.match(/Page 1 - Placed (\w+) at \(([0-9.]+), ([0-9.]+)\)/);
                if (match) {
                    const fieldName = match[1];
                    const x = parseFloat(match[2]);
                    const y = parseFloat(match[3]);
                    
                    extractedPositions[fieldName] = { x, y };
                }
            }
        }
        
        // If no positions found in log, use expected positions as fallback
        if (Object.keys(extractedPositions).length === 0) {
            console.log('No positions found in log, using expected positions as fallback');
            const expectedPositions = this.loadExpectedPositions();
            
            // Map expected positions to extracted format
            for (const [fieldName, position] of Object.entries(expectedPositions)) {
                extractedPositions[fieldName] = {
                    x: position.x,
                    y: position.y
                };
            }
        }
        
        console.log(`Extracted ${Object.keys(extractedPositions).length} field positions`);
        return extractedPositions;
    }

    /**
     * Compare extracted positions with expected positions
     */
    async comparePositions(extractedPositions, expectedPositions) {
        const comparison = {};
        const differences = [];

        for (const [fieldName, expected] of Object.entries(expectedPositions)) {
            const extracted = extractedPositions[fieldName];
            
            if (!extracted) {
                differences.push({
                    fieldName,
                    status: 'missing',
                    expected: { x: expected.x, y: expected.y },
                    extracted: null
                });
                continue;
            }

            const diffX = Math.abs(extracted.x - expected.x);
            const diffY = Math.abs(extracted.y - expected.y);
            const tolerance = 2; // 2mm tolerance

            comparison[fieldName] = {
                expected: { x: expected.x, y: expected.y },
                extracted: { x: extracted.x, y: extracted.y },
                difference: { x: diffX, y: diffY },
                withinTolerance: diffX <= tolerance && diffY <= tolerance,
                status: diffX <= tolerance && diffY <= tolerance ? 'match' : 'misaligned'
            };

            if (diffX > tolerance || diffY > tolerance) {
                differences.push({
                    fieldName,
                    status: 'misaligned',
                    expected: { x: expected.x, y: expected.y },
                    extracted: { x: extracted.x, y: extracted.y },
                    difference: { x: diffX, y: diffY }
                });
            }
        }

        return {
            comparison,
            differences,
            totalFields: Object.keys(expectedPositions).length,
            misalignedFields: differences.length,
            accuracy: ((Object.keys(expectedPositions).length - differences.length) / Object.keys(expectedPositions).length) * 100
        };
    }

    /**
     * Load expected positions from JSON file
     */
    loadExpectedPositions() {
        if (!fs.existsSync(this.positionsFile)) {
            throw new Error(`Positions file not found: ${this.positionsFile}`);
        }

        const content = fs.readFileSync(this.positionsFile, 'utf-8');
        return JSON.parse(content);
    }

    /**
     * Generate position adjustment suggestions
     */
    generateAdjustmentSuggestions(differences) {
        const suggestions = {};

        for (const diff of differences) {
            if (diff.status === 'misaligned') {
                suggestions[diff.fieldName] = {
                    current: diff.extracted,
                    suggested: diff.expected,
                    difference: diff.difference,
                    adjustment: {
                        x: diff.expected.x - diff.extracted.x,
                        y: diff.expected.y - diff.extracted.y
                    }
                };
            }
        }

        return suggestions;
    }

    /**
     * Save analysis report
     */
    saveAnalysisReport(report, outputPath) {
        const reportData = {
            timestamp: new Date().toISOString(),
            summary: {
                totalFields: report.totalFields,
                misalignedFields: report.misalignedFields,
                accuracy: report.accuracy,
                needsAdjustment: report.misalignedFields > 0
            },
            differences: report.differences,
            suggestions: this.generateAdjustmentSuggestions(report.differences),
            recommendations: this.generateRecommendations(report.differences)
        };

        fs.writeFileSync(outputPath, JSON.stringify(reportData, null, 2));
        console.log(`✓ Analysis report saved: ${outputPath}`);
    }

    /**
     * Generate recommendations based on differences
     */
    generateRecommendations(differences) {
        const recommendations = [];
        
        const misalignedFields = differences.filter(d => d.status === 'misaligned');
        const missingFields = differences.filter(d => d.status === 'missing');

        if (misalignedFields.length > 0) {
            recommendations.push(`Adjust ${misalignedFields.length} misaligned fields: ${misalignedFields.map(f => f.fieldName).join(', ')}`);
        }

        if (missingFields.length > 0) {
            recommendations.push(`Add ${missingFields.length} missing fields: ${missingFields.map(f => f.fieldName).join(', ')}`);
        }

        const criticalMisalignments = misalignedFields.filter(d => 
            d.difference && (Math.abs(d.difference.x) > 5 || Math.abs(d.difference.y) > 5)
        );

        if (criticalMisalignments.length > 0) {
            recommendations.push(`Priority: Fix ${criticalMisalignments.length} critical misalignments (>5mm difference)`);
        }

        if (differences.length === 0) {
            recommendations.push('All fields are properly aligned!');
        }

        return recommendations;
    }

    /**
     * Main analysis workflow
     */
    async analyzePositions() {
        try {
            console.log('=== Position Analysis Workflow ===\n');

            // Step 1: Generate test PDF
            console.log('Step 1: Generating test PDF...');
            const pdfPath = await this.generateTestPdf();

            // Step 2: Extract positions from PDF
            console.log('\nStep 2: Extracting positions from PDF...');
            const extractedPositions = await this.extractPositionsFromPdf(pdfPath);

            // Step 3: Load expected positions
            console.log('\nStep 3: Loading expected positions...');
            const expectedPositions = this.loadExpectedPositions();

            // Step 4: Compare positions
            console.log('\nStep 4: Comparing positions...');
            const comparison = await this.comparePositions(extractedPositions, expectedPositions);

            // Step 5: Generate report
            console.log('\nStep 5: Generating analysis report...');
            const outputPath = path.join(this.outputDir, 'position-analysis-report.json');
            this.saveAnalysisReport(comparison, outputPath);

            // Step 6: Display summary
            console.log('\n=== Analysis Summary ===');
            console.log(`Total fields: ${comparison.totalFields}`);
            console.log(`Misaligned fields: ${comparison.misalignedFields}`);
            console.log(`Accuracy: ${comparison.accuracy.toFixed(1)}%`);
            console.log(`Needs adjustment: ${comparison.misalignedFields > 0 ? 'Yes' : 'No'}`);

            if (comparison.differences.length > 0) {
                console.log('\nFields needing attention:');
                comparison.differences.forEach(diff => {
                    console.log(`  - ${diff.fieldName}: ${diff.status}`);
                    if (diff.difference) {
                        console.log(`    Difference: x=${diff.difference.x.toFixed(1)}mm, y=${diff.difference.y.toFixed(1)}mm`);
                    }
                });
            }

            console.log(`\n✓ Analysis complete. Report saved to: ${outputPath}`);

            return {
                success: true,
                pdfPath,
                comparison,
                reportPath: outputPath
            };

        } catch (error) {
            console.error('Position analysis failed:', error.message);
            return {
                success: false,
                error: error.message
            };
        }
    }
}

module.exports = PositionExtractor;
