<?php
declare(strict_types=1);

namespace WebPdfTimeSaver\Mvp\FieldFillers;

final class MarriageFieldFiller implements FieldFillerInterface {
    
    public function fillFields($pdf, array $data, \WebPdfTimeSaver\Mvp\Logger $logger): void {
        $logger->debug('Filling marriage section');
        
        // Marriage Date - positioned on marriage date line (nudged)
        if (!empty($data['marriage_date'])) {
            $pdf->SetXY(53, 197);
            $pdf->Write(0, $data['marriage_date']);
            $logger->debug('Marriage date filled', ['x' => 53, 'y' => 197, 'value' => $data['marriage_date']]);
        }
        
        // Separation Date - positioned on separation date line (nudged)
        if (!empty($data['separation_date'])) {
            $pdf->SetXY(53, 205);
            $pdf->Write(0, $data['separation_date']);
            $logger->debug('Separation date filled', ['x' => 53, 'y' => 205, 'value' => $data['separation_date']]);
        }
        
        // Marriage Location - positioned on marriage location line (nudged)
        if (!empty($data['marriage_location'])) {
            $pdf->SetXY(53, 213);
            $pdf->Write(0, $data['marriage_location']);
            $logger->debug('Marriage location filled', ['x' => 53, 'y' => 213, 'value' => $data['marriage_location']]);
        }
        
        // Grounds for Dissolution - positioned on grounds line (nudged)
        if (!empty($data['grounds_for_dissolution'])) {
            $pdf->SetXY(53, 222);
            $pdf->Write(0, $data['grounds_for_dissolution']);
            $logger->debug('Grounds filled', ['x' => 53, 'y' => 222, 'value' => $data['grounds_for_dissolution']]);
        }
    }
    
    public function getSectionName(): string {
        return 'Marriage Information';
    }
    
    public function getHandledFields(): array {
        return [
            'marriage_date',
            'separation_date',
            'marriage_location',
            'grounds_for_dissolution'
        ];
    }
}
