import * as fs from 'fs';
import * as path from 'path';
import { PDFAnalyzer, PositionAnalysis, FieldPosition } from './pdf-analyzer';

export interface ComparisonResult {
    fieldName: string;
    ourPosition: FieldPosition | null;
    referencePosition: FieldPosition | null;
    expectedPosition: { x: number; y: number };
    analysis: PositionAnalysis | null;
    status: 'match' | 'misaligned' | 'missing' | 'extra';
}

export interface ComparisonReport {
    timestamp: string;
    ourPdf: string;
    referencePdf: string;
    totalFields: number;
    matches: number;
    misalignments: number;
    missing: number;
    extra: number;
    results: ComparisonResult[];
    summary: {
        overallAccuracy: number;
        needsAttention: string[];
        recommendations: string[];
    };
}

export class PositionComparator {
    private analyzer: PDFAnalyzer;
    private readonly tolerance = 2; // 2mm tolerance

    constructor() {
        this.analyzer = new PDFAnalyzer();
    }

    async compareWithReference(
        ourPdfPath: string,
        referencePdfPath: string,
        expectedPositions: Record<string, any>
    ): Promise<ComparisonReport> {
        const fieldMappings = this.getFieldMappings();
        
        // Extract positions from both PDFs
        const ourPositions = await this.analyzer.extractFieldPositions(ourPdfPath, fieldMappings);
        const referencePositions = await this.analyzer.extractFieldPositions(referencePdfPath, fieldMappings);
        
        const results: ComparisonResult[] = [];
        let matches = 0;
        let misalignments = 0;
        let missing = 0;
        let extra = 0;
        
        // Compare each expected field
        for (const [fieldName, expected] of Object.entries(expectedPositions)) {
            const ourPos = ourPositions.find(p => p.fieldName === fieldName);
            const refPos = referencePositions.find(p => p.fieldName === fieldName);
            
            let status: ComparisonResult['status'];
            let analysis: PositionAnalysis | null = null;
            
            if (!ourPos && !refPos) {
                status = 'missing';
                missing++;
            } else if (!ourPos) {
                status = 'missing';
                missing++;
            } else if (!refPos) {
                status = 'extra';
                extra++;
            } else {
                // Both positions exist - check alignment
                const diffX = Math.abs(refPos.x - ourPos.x);
                const diffY = Math.abs(refPos.y - ourPos.y);
                
                if (diffX <= this.tolerance && diffY <= this.tolerance) {
                    status = 'match';
                    matches++;
                } else {
                    status = 'misaligned';
                    misalignments++;
                    
                    analysis = {
                        fieldName,
                        current: { x: ourPos.x, y: ourPos.y },
                        suggested: { x: refPos.x, y: refPos.y },
                        difference: { x: refPos.x - ourPos.x, y: refPos.y - ourPos.y },
                        confidence: Math.min(ourPos.confidence, refPos.confidence)
                    };
                }
            }
            
            results.push({
                fieldName,
                ourPosition: ourPos || null,
                referencePosition: refPos || null,
                expectedPosition: { x: expected.x || 0, y: expected.y || 0 },
                analysis,
                status
            });
        }
        
        // Calculate overall accuracy
        const totalComparable = matches + misalignments;
        const overallAccuracy = totalComparable > 0 ? (matches / totalComparable) * 100 : 0;
        
        // Generate recommendations
        const needsAttention = results
            .filter(r => r.status === 'misaligned')
            .map(r => r.fieldName);
            
        const recommendations = this.generateRecommendations(results);
        
        const report: ComparisonReport = {
            timestamp: new Date().toISOString(),
            ourPdf: ourPdfPath,
            referencePdf: referencePdfPath,
            totalFields: Object.keys(expectedPositions).length,
            matches,
            misalignments,
            missing,
            extra,
            results,
            summary: {
                overallAccuracy,
                needsAttention,
                recommendations
            }
        };
        
        return report;
    }

    private generateRecommendations(results: ComparisonResult[]): string[] {
        const recommendations: string[] = [];
        
        const misalignedFields = results.filter(r => r.status === 'misaligned');
        const missingFields = results.filter(r => r.status === 'missing');
        
        if (misalignedFields.length > 0) {
            recommendations.push(`Adjust ${misalignedFields.length} misaligned fields: ${misalignedFields.map(f => f.fieldName).join(', ')}`);
        }
        
        if (missingFields.length > 0) {
            recommendations.push(`Add ${missingFields.length} missing fields: ${missingFields.map(f => f.fieldName).join(', ')}`);
        }
        
        const criticalMisalignments = misalignedFields.filter(r => 
            r.analysis && (Math.abs(r.analysis.difference.x) > 5 || Math.abs(r.analysis.difference.y) > 5)
        );
        
        if (criticalMisalignments.length > 0) {
            recommendations.push(`Priority: Fix ${criticalMisalignments.length} critical misalignments (>5mm difference)`);
        }
        
        if (results.every(r => r.status === 'match')) {
            recommendations.push('All fields are properly aligned!');
        }
        
        return recommendations;
    }

    private getFieldMappings(): Record<string, string[]> {
        return {
            attorney_name: ['John Michael Smith', 'Attorney Name', 'Name:'],
            attorney_firm: ['Smith & Associates', 'Firm Name', 'Firm:'],
            attorney_bar_number: ['123456', 'Bar Number', 'State Bar'],
            case_number: ['FL-2024-001234', 'Case Number', 'Case No'],
            court_county: ['Los Angeles', 'County', 'Court'],
            petitioner_name: ['Sarah Elizabeth Johnson', 'Petitioner', 'Petitioner Name'],
            respondent_name: ['Michael David Johnson', 'Respondent', 'Respondent Name'],
            petitioner_address: ['123 Main Street', 'Petitioner Address', 'Address'],
            respondent_address: ['456 Oak Avenue', 'Respondent Address'],
            marriage_date: ['Marriage Date', 'Date of Marriage'],
            separation_date: ['Separation Date', 'Date of Separation'],
            marriage_location: ['Marriage Location', 'Location'],
            grounds_for_dissolution: ['Grounds', 'Dissolution', 'Reason'],
            attorney_signature: ['Signature', 'Attorney Signature'],
            signature_date: ['Date', 'Signature Date']
        };
    }

    async saveComparisonReport(report: ComparisonReport, outputPath: string): Promise<void> {
        fs.writeFileSync(outputPath, JSON.stringify(report, null, 2));
    }

    async loadExpectedPositions(positionsPath: string): Promise<Record<string, any>> {
        if (fs.existsSync(positionsPath)) {
            const content = fs.readFileSync(positionsPath, 'utf-8');
            return JSON.parse(content);
        }
        return {};
    }
}
