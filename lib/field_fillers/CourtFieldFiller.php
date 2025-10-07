<?php
declare(strict_types=1);

namespace WebPdfTimeSaver\Mvp\FieldFillers;

final class CourtFieldFiller implements FieldFillerInterface {
    
    public function fillFields($pdf, array $data, string $logFile): void {
        file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 DEBUG: Filling court section' . PHP_EOL, FILE_APPEND);
        
        // Case Number - Top right box, positioned in the case number field (nudged)
        if (!empty($data['case_number'])) {
            $pdf->SetXY(150, 28);
            $pdf->Write(0, $data['case_number']);
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 DEBUG: Case number at (145, 30): ' . $data['case_number'] . PHP_EOL, FILE_APPEND);
        }
        
        // County - positioned on county line (nudged)
        if (!empty($data['court_county'])) {
            $pdf->SetXY(52, 115);
            $pdf->Write(0, $data['court_county']);
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 DEBUG: County at (55, 110): ' . $data['court_county'] . PHP_EOL, FILE_APPEND);
        }
        
        // Court Address - positioned on court address line (nudged)
        if (!empty($data['court_address'])) {
            $pdf->SetXY(52, 122);
            $pdf->Write(0, $data['court_address']);
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 DEBUG: Court address at (55, 120): ' . $data['court_address'] . PHP_EOL, FILE_APPEND);
        }
        
        // Case Type - positioned on case type line (nudged)
        if (!empty($data['case_type'])) {
            $pdf->SetXY(52, 129);
            $pdf->Write(0, $data['case_type']);
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 DEBUG: Case type at (55, 130): ' . $data['case_type'] . PHP_EOL, FILE_APPEND);
        }
        
        // Filing Date - positioned on filing date line (nudged)
        if (!empty($data['filing_date'])) {
            $pdf->SetXY(52, 136);
            $pdf->Write(0, $data['filing_date']);
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 DEBUG: Filing date at (55, 140): ' . $data['filing_date'] . PHP_EOL, FILE_APPEND);
        }
    }
    
    public function getSectionName(): string {
        return 'Court Information';
    }
    
    public function getHandledFields(): array {
        return [
            'case_number',
            'court_county',
            'court_address', 
            'case_type',
            'filing_date'
        ];
    }
}
