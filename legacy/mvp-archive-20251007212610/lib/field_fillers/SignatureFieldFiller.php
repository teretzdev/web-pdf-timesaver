<?php
declare(strict_types=1);

namespace WebPdfTimeSaver\Mvp\FieldFillers;

final class SignatureFieldFiller implements FieldFillerInterface {
    
    public function fillFields($pdf, array $data, string $logFile): void {
        file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 DEBUG: Filling signature section' . PHP_EOL, FILE_APPEND);
        
        // Additional Information - positioned on additional info line (nudged)
        if (!empty($data['additional_info'])) {
            $pdf->SetXY(53, 305);
            $pdf->Write(0, $data['additional_info']);
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 DEBUG: Additional info at (55, 310): ' . $data['additional_info'] . PHP_EOL, FILE_APPEND);
        }
        
        // Attorney Signature - positioned on attorney signature line (nudged)
        if (!empty($data['attorney_signature'])) {
            $pdf->SetXY(53, 325);
            $pdf->Write(0, $data['attorney_signature']);
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 DEBUG: Attorney signature at (55, 330): ' . $data['attorney_signature'] . PHP_EOL, FILE_APPEND);
        }
        
        // Signature Date - positioned on signature date line (nudged)
        if (!empty($data['signature_date'])) {
            $pdf->SetXY(53, 336);
            $pdf->Write(0, $data['signature_date']);
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 DEBUG: Signature date at (55, 340): ' . $data['signature_date'] . PHP_EOL, FILE_APPEND);
        }
    }
    
    public function getSectionName(): string {
        return 'Signature Section';
    }
    
    public function getHandledFields(): array {
        return [
            'additional_info',
            'attorney_signature',
            'signature_date'
        ];
    }
}
