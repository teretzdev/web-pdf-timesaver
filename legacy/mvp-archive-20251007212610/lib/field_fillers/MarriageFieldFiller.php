<?php
declare(strict_types=1);

namespace WebPdfTimeSaver\Mvp\FieldFillers;

final class MarriageFieldFiller implements FieldFillerInterface {
    
    public function fillFields($pdf, array $data, string $logFile): void {
        file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 DEBUG: Filling marriage section' . PHP_EOL, FILE_APPEND);
        
        // Marriage Date - positioned on marriage date line (nudged)
        if (!empty($data['marriage_date'])) {
            $pdf->SetXY(53, 197);
            $pdf->Write(0, $data['marriage_date']);
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 DEBUG: Marriage date at (55, 200): ' . $data['marriage_date'] . PHP_EOL, FILE_APPEND);
        }
        
        // Separation Date - positioned on separation date line (nudged)
        if (!empty($data['separation_date'])) {
            $pdf->SetXY(53, 205);
            $pdf->Write(0, $data['separation_date']);
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 DEBUG: Separation date at (55, 210): ' . $data['separation_date'] . PHP_EOL, FILE_APPEND);
        }
        
        // Marriage Location - positioned on marriage location line (nudged)
        if (!empty($data['marriage_location'])) {
            $pdf->SetXY(53, 213);
            $pdf->Write(0, $data['marriage_location']);
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 DEBUG: Marriage location at (55, 220): ' . $data['marriage_location'] . PHP_EOL, FILE_APPEND);
        }
        
        // Grounds for Dissolution - positioned on grounds line (nudged)
        if (!empty($data['grounds_for_dissolution'])) {
            $pdf->SetXY(53, 222);
            $pdf->Write(0, $data['grounds_for_dissolution']);
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 DEBUG: Grounds at (55, 230): ' . $data['grounds_for_dissolution'] . PHP_EOL, FILE_APPEND);
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
