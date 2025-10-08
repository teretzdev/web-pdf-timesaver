<?php
declare(strict_types=1);

namespace WebPdfTimeSaver\Mvp\FieldFillers;

final class PartyFieldFiller implements FieldFillerInterface {
    
    public function fillFields($pdf, array $data, string $logFile): void {
        file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 DEBUG: Filling party section' . PHP_EOL, FILE_APPEND);
        
        // Petitioner Name - positioned on petitioner name line (nudged)
        if (!empty($data['petitioner_name'])) {
            $pdf->SetXY(58, 148);
            $pdf->Write(0, $data['petitioner_name']);
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 DEBUG: Petitioner at (60, 150): ' . $data['petitioner_name'] . PHP_EOL, FILE_APPEND);
        }
        
        // Respondent Name - positioned on respondent name line (nudged)
        if (!empty($data['respondent_name'])) {
            $pdf->SetXY(58, 156);
            $pdf->Write(0, $data['respondent_name']);
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 DEBUG: Respondent at (60, 160): ' . $data['respondent_name'] . PHP_EOL, FILE_APPEND);
        }
        
        // Petitioner Address - positioned on petitioner address line (nudged)
        if (!empty($data['petitioner_address'])) {
            $pdf->SetXY(53, 166);
            $pdf->Write(0, $data['petitioner_address']);
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 DEBUG: Petitioner address at (55, 170): ' . $data['petitioner_address'] . PHP_EOL, FILE_APPEND);
        }
        
        // Petitioner Phone - positioned on petitioner phone line (nudged)
        if (!empty($data['petitioner_phone'])) {
            $pdf->SetXY(53, 175);
            $pdf->Write(0, $data['petitioner_phone']);
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 DEBUG: Petitioner phone at (55, 180): ' . $data['petitioner_phone'] . PHP_EOL, FILE_APPEND);
        }
        
        // Respondent Address - positioned on respondent address line (nudged)
        if (!empty($data['respondent_address'])) {
            $pdf->SetXY(53, 185);
            $pdf->Write(0, $data['respondent_address']);
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 DEBUG: Respondent address at (55, 190): ' . $data['respondent_address'] . PHP_EOL, FILE_APPEND);
        }
    }
    
    public function getSectionName(): string {
        return 'Party Information';
    }
    
    public function getHandledFields(): array {
        return [
            'petitioner_name',
            'respondent_name',
            'petitioner_address',
            'petitioner_phone',
            'respondent_address'
        ];
    }
}
