import * as fs from 'fs';
import * as path from 'path';
const pdfParse = require('pdf-parse');
import { PDFDocument } from 'pdf-lib';

export interface TextItem {
    text: string;
    x: number;
    y: number;
    width: number;
    height: number;
    fontSize?: number;
    fontName?: string;
}

export interface FieldPosition {
    fieldName: string;
    text: string;
    x: number;
    y: number;
    width: number;
    height: number;
    page: number;
    confidence: number;
}

export interface PositionAnalysis {
    fieldName: string;
    current: { x: number; y: number };
    suggested: { x: number; y: number };
    difference: { x: number; y: number };
    confidence: number;
}

export class PDFAnalyzer {
    private readonly mmToPoint = 2.834645669; // 1mm = 2.834645669 points
    private readonly tolerance = 2; // 2mm tolerance

    async readPDF(filePath: string): Promise<{ text: string; items: TextItem[] }> {
        try {
            const dataBuffer = fs.readFileSync(filePath);
            const pdfData = await pdfParse(dataBuffer);
            
            // Extract text items with positions
            const items: TextItem[] = [];
            
            // Parse the text content to extract positions
            // This is a simplified approach - in production you'd use a more sophisticated parser
            const lines = pdfData.text.split('\n');
            let currentY = 0;
            
            for (const line of lines) {
                if (line.trim()) {
                    items.push({
                        text: line.trim(),
                        x: 0, // Simplified - would need actual PDF parsing
                        y: currentY,
                        width: line.length * 6, // Approximate
                        height: 12,
                        fontSize: 12
                    });
                    currentY += 15;
                }
            }
            
            return {
                text: pdfData.text,
                items
            };
        } catch (error) {
            throw new Error(`Failed to read PDF: ${error instanceof Error ? error.message : String(error)}`);
        }
    }

    async extractFieldPositions(filePath: string, fieldMappings: Record<string, string[]>): Promise<FieldPosition[]> {
        const { text, items } = await this.readPDF(filePath);
        const positions: FieldPosition[] = [];
        
        for (const [fieldName, searchTerms] of Object.entries(fieldMappings)) {
            for (const term of searchTerms) {
                const item = items.find(item => 
                    item.text.toLowerCase().includes(term.toLowerCase())
                );
                
                if (item) {
                    positions.push({
                        fieldName,
                        text: item.text,
                        x: item.x / this.mmToPoint, // Convert to mm
                        y: item.y / this.mmToPoint,
                        width: item.width / this.mmToPoint,
                        height: item.height / this.mmToPoint,
                        page: 1, // Simplified - would need page detection
                        confidence: 0.8
                    });
                    break;
                }
            }
        }
        
        return positions;
    }

    async comparePositions(
        ourPdfPath: string, 
        referencePdfPath: string, 
        expectedPositions: Record<string, any>
    ): Promise<PositionAnalysis[]> {
        const fieldMappings = this.getFieldMappings();
        
        const ourPositions = await this.extractFieldPositions(ourPdfPath, fieldMappings);
        const referencePositions = await this.extractFieldPositions(referencePdfPath, fieldMappings);
        
        const analysis: PositionAnalysis[] = [];
        
        for (const [fieldName, expected] of Object.entries(expectedPositions)) {
            const ourPos = ourPositions.find(p => p.fieldName === fieldName);
            const refPos = referencePositions.find(p => p.fieldName === fieldName);
            
            if (ourPos && refPos) {
                const diffX = refPos.x - ourPos.x;
                const diffY = refPos.y - ourPos.y;
                
                // Only suggest changes if difference exceeds tolerance
                if (Math.abs(diffX) > this.tolerance || Math.abs(diffY) > this.tolerance) {
                    analysis.push({
                        fieldName,
                        current: { x: ourPos.x, y: ourPos.y },
                        suggested: { x: refPos.x, y: refPos.y },
                        difference: { x: diffX, y: diffY },
                        confidence: Math.min(ourPos.confidence, refPos.confidence)
                    });
                }
            }
        }
        
        return analysis;
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

    async generateAnalysisReport(
        ourPdfPath: string,
        referencePdfPath: string,
        expectedPositions: Record<string, any>,
        outputPath: string
    ): Promise<void> {
        const analysis = await this.comparePositions(ourPdfPath, referencePdfPath, expectedPositions);
        
        const report = {
            timestamp: new Date().toISOString(),
            ourPdf: ourPdfPath,
            referencePdf: referencePdfPath,
            totalFields: Object.keys(expectedPositions).length,
            misalignedFields: analysis.length,
            tolerance: this.tolerance,
            analysis: analysis,
            summary: {
                needsAdjustment: analysis.length > 0,
                criticalIssues: analysis.filter(a => Math.abs(a.difference.x) > 5 || Math.abs(a.difference.y) > 5).length,
                minorIssues: analysis.filter(a => Math.abs(a.difference.x) <= 5 && Math.abs(a.difference.y) <= 5).length
            }
        };
        
        fs.writeFileSync(outputPath, JSON.stringify(report, null, 2));
    }
}
