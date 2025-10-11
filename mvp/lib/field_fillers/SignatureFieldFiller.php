<?php
declare(strict_types=1);

namespace WebPdfTimeSaver\Mvp\FieldFillers;

final class SignatureFieldFiller implements FieldFillerInterface {
    
    public function fillFields($pdf, array $data, \WebPdfTimeSaver\Mvp\Logger $logger): void {
        $logger->debug('Filling signature section');
        
        // Additional Information - positioned on additional info line (nudged)
        if (!empty($data['additional_info'])) {
            $pdf->SetXY(53, 305);
            $pdf->Write(0, $data['additional_info']);
            $logger->debug('Additional info filled', ['x' => 53, 'y' => 305, 'value' => $data['additional_info']]);
        }
        
        // Attorney Signature - positioned on attorney signature line (nudged)
        if (!empty($data['attorney_signature'])) {
            $pdf->SetXY(53, 325);
            $pdf->Write(0, $data['attorney_signature']);
            $logger->debug('Attorney signature filled', ['x' => 53, 'y' => 325, 'value' => $data['attorney_signature']]);
        }
        
        // Signature Date - positioned on signature date line (nudged)
        if (!empty($data['signature_date'])) {
            $pdf->SetXY(53, 336);
            $pdf->Write(0, $data['signature_date']);
            $logger->debug('Signature date filled', ['x' => 53, 'y' => 336, 'value' => $data['signature_date']]);
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
