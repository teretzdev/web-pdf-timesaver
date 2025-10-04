<?php
declare(strict_types=1);

namespace WebPdfTimeSaver\Mvp\FieldFillers;

final class ReliefFieldFiller implements FieldFillerInterface {
    
    public function fillFields($pdf, array $data, string $logFile): void {
        file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 DEBUG: Filling relief section' . PHP_EOL, FILE_APPEND);
        
        // Dissolution Type checkbox - positioned on dissolution checkbox (nudged)
        if (!empty($data['dissolution_type'])) {
            $pdf->SetXY(28, 238);
            $pdf->Write(0, 'X');
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 DEBUG: Dissolution type checkbox at (30, 240)' . PHP_EOL, FILE_APPEND);
        }
        
        // Property Division checkbox - positioned on property division checkbox (nudged)
        if (!empty($data['property_division'])) {
            $pdf->SetXY(28, 247);
            $pdf->Write(0, 'X');
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 DEBUG: Property division checkbox at (30, 250)' . PHP_EOL, FILE_APPEND);
        }
        
        // Spousal Support checkbox - positioned on spousal support checkbox (nudged)
        if (!empty($data['spousal_support'])) {
            $pdf->SetXY(28, 256);
            $pdf->Write(0, 'X');
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 DEBUG: Spousal support checkbox at (30, 260)' . PHP_EOL, FILE_APPEND);
        }
        
        // Attorney Fees checkbox - positioned on attorney fees checkbox (nudged)
        if (!empty($data['attorney_fees'])) {
            $pdf->SetXY(28, 265);
            $pdf->Write(0, 'X');
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 DEBUG: Attorney fees checkbox at (30, 270)' . PHP_EOL, FILE_APPEND);
        }
        
        // Name Change checkbox - positioned on name change checkbox (nudged)
        if (!empty($data['name_change'])) {
            $pdf->SetXY(28, 274);
            $pdf->Write(0, 'X');
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 DEBUG: Name change checkbox at (30, 280)' . PHP_EOL, FILE_APPEND);
        }
    }
    
    public function getSectionName(): string {
        return 'Relief Requested';
    }
    
    public function getHandledFields(): array {
        return [
            'dissolution_type',
            'property_division',
            'spousal_support',
            'attorney_fees',
            'name_change'
        ];
    }
}
