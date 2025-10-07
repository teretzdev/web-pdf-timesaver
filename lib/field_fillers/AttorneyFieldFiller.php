<?php
declare(strict_types=1);

namespace WebPdfTimeSaver\Mvp\FieldFillers;

final class AttorneyFieldFiller implements FieldFillerInterface {
    
    public function fillFields($pdf, array $data, string $logFile): void {
        file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 DEBUG: Filling attorney section' . PHP_EOL, FILE_APPEND);
        
        // Use smaller font for tighter line fitting
        $pdf->SetFont('Arial', '', 10);
        
        // Attorney Name - positioned in the attorney name field (reduced top margin)
        if (!empty($data['attorney_name'])) {
            $pdf->SetXY(35, 30);
            $pdf->Write(0, $data['attorney_name']);
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 DEBUG: Attorney name at (50, 32): ' . $data['attorney_name'] . PHP_EOL, FILE_APPEND);
        }
        
        // State Bar Number - positioned in the bar number field (reduced top margin)
        if (!empty($data['attorney_bar_number'])) {
            $pdf->SetXY(145, 30);
            $pdf->Write(0, $data['attorney_bar_number']);
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 DEBUG: Bar number at (155, 32): ' . $data['attorney_bar_number'] . PHP_EOL, FILE_APPEND);
        }
        
        // Law Firm Name - positioned in the firm name field (reduced line spacing)
        if (!empty($data['attorney_firm'])) {
            $pdf->SetXY(35, 35);
            $pdf->Write(0, $data['attorney_firm']);
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 DEBUG: Firm name at (35, 35): ' . $data['attorney_firm'] . PHP_EOL, FILE_APPEND);
        }
        
        // Address - positioned in the address field (tightened line spacing)
        if (!empty($data['attorney_address'])) {
            $pdf->SetXY(35, 40);
            $pdf->Write(0, $data['attorney_address']);
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 DEBUG: Address at (35, 40): ' . $data['attorney_address'] . PHP_EOL, FILE_APPEND);
        }
        
        // City, State, ZIP - positioned in the city/state/zip field (tightened line spacing)
        if (!empty($data['attorney_city_state_zip'])) {
            $pdf->SetXY(35, 45);
            $pdf->Write(0, $data['attorney_city_state_zip']);
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 DEBUG: City/State/ZIP at (35, 45): ' . $data['attorney_city_state_zip'] . PHP_EOL, FILE_APPEND);
        }
        
        // Phone - positioned in the phone field (aligned baseline)
        if (!empty($data['attorney_phone'])) {
            $pdf->SetXY(35, 50);
            $pdf->Write(0, $data['attorney_phone']);
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 DEBUG: Phone at (35, 50): ' . $data['attorney_phone'] . PHP_EOL, FILE_APPEND);
        }
        
        // Email - positioned in the email field (aligned baseline with phone)
        if (!empty($data['attorney_email'])) {
            $pdf->SetXY(120, 50);
            $pdf->Write(0, $data['attorney_email']);
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 DEBUG: Email at (120, 50): ' . $data['attorney_email'] . PHP_EOL, FILE_APPEND);
        }
    }
    
    public function getSectionName(): string {
        return 'Attorney Information';
    }
    
    public function getHandledFields(): array {
        return [
            'attorney_name',
            'attorney_bar_number', 
            'attorney_firm',
            'attorney_address',
            'attorney_city_state_zip',
            'attorney_phone',
            'attorney_email'
        ];
    }
}
