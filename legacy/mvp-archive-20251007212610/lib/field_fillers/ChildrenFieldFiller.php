<?php
declare(strict_types=1);

namespace WebPdfTimeSaver\Mvp\FieldFillers;

final class ChildrenFieldFiller implements FieldFillerInterface {
    
    public function fillFields($pdf, array $data, string $logFile): void {
        file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 DEBUG: Filling children section' . PHP_EOL, FILE_APPEND);
        
        // Has Children checkbox - positioned on no children checkbox (nudged)
        if (!empty($data['has_children']) && $data['has_children'] === 'No') {
            $pdf->SetXY(28, 286);
            $pdf->Write(0, 'X');
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 DEBUG: No children checkbox at (30, 290)' . PHP_EOL, FILE_APPEND);
        } elseif (!empty($data['children_count'])) {
            // Children count - positioned on children count line
            $pdf->SetXY(53, 295);
            $pdf->Write(0, $data['children_count']);
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 DEBUG: Children count at (55, 300): ' . $data['children_count'] . PHP_EOL, FILE_APPEND);
        }
    }
    
    public function getSectionName(): string {
        return 'Children Information';
    }
    
    public function getHandledFields(): array {
        return [
            'has_children',
            'children_count'
        ];
    }
}
