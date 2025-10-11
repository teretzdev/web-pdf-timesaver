<?php
declare(strict_types=1);

namespace WebPdfTimeSaver\Mvp\FieldFillers;

require_once __DIR__ . '/../field_position_loader.php';

final class ChildrenFieldFiller implements FieldFillerInterface {
    private $positionLoader;
    
    public function __construct() {
        $this->positionLoader = new \WebPdfTimeSaver\Mvp\FieldPositionLoader();
    }
    
    public function fillFields($pdf, array $data, \WebPdfTimeSaver\Mvp\Logger $logger): void {
        $logger->debug('Filling children section');
        
        $positions = $this->positionLoader->loadFieldPositions('t_fl100_gc120');
        
        // Has Children checkbox
        if (!empty($data['has_children']) && isset($positions['has_children'])) {
            $pos = $positions['has_children'];
            $pdf->SetFont('Arial', '', $pos['fontSize'] ?? 9);
            $pdf->SetXY($pos['x'], $pos['y']);
            $pdf->Cell($pos['width'], 5, 'X', 0, 0, 'C');
            $logger->debug('Has children checkbox filled', ['x' => $pos['x'], 'y' => $pos['y']]);
        }
        
        // Children count - always place this field regardless of has_children value
        if (isset($positions['children_count'])) {
            $pos = $positions['children_count'];
            $pdf->SetFont('Arial', '', $pos['fontSize'] ?? 9);
            $pdf->SetXY($pos['x'], $pos['y']);
            $childrenCount = !empty($data['children_count']) ? $data['children_count'] : '0';
            $pdf->Cell($pos['width'], 5, $childrenCount, 0, 0, 'L');
            $logger->debug('Children count filled', ['x' => $pos['x'], 'y' => $pos['y'], 'value' => $childrenCount]);
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
