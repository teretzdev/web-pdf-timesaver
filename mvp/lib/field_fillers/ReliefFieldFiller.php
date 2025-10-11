<?php
declare(strict_types=1);

namespace WebPdfTimeSaver\Mvp\FieldFillers;

require_once __DIR__ . '/../field_position_loader.php';

final class ReliefFieldFiller implements FieldFillerInterface {
    private $positionLoader;
    
    public function __construct() {
        $this->positionLoader = new \WebPdfTimeSaver\Mvp\FieldPositionLoader();
    }
    
    public function fillFields($pdf, array $data, \WebPdfTimeSaver\Mvp\Logger $logger): void {
        $logger->debug('Filling relief section');
        
        $positions = $this->positionLoader->loadFieldPositions('t_fl100_gc120');
        
        // Dissolution Type checkbox
        if (!empty($data['dissolution_type']) && isset($positions['dissolution_type'])) {
            $pos = $positions['dissolution_type'];
            $pdf->SetFont('Arial', '', $pos['fontSize'] ?? 9);
            $pdf->SetXY($pos['x'], $pos['y']);
            $pdf->Cell($pos['width'], 5, 'X', 0, 0, 'C');
            $logger->debug('Dissolution type checkbox filled', ['x' => $pos['x'], 'y' => $pos['y']]);
        }
        
        // Property Division checkbox
        if (!empty($data['property_division']) && isset($positions['property_division'])) {
            $pos = $positions['property_division'];
            $pdf->SetFont('Arial', '', $pos['fontSize'] ?? 9);
            $pdf->SetXY($pos['x'], $pos['y']);
            $pdf->Cell($pos['width'], 5, 'X', 0, 0, 'C');
            $logger->debug('Property division checkbox filled', ['x' => $pos['x'], 'y' => $pos['y']]);
        }
        
        // Spousal Support checkbox
        if (!empty($data['spousal_support']) && isset($positions['spousal_support'])) {
            $pos = $positions['spousal_support'];
            $pdf->SetFont('Arial', '', $pos['fontSize'] ?? 9);
            $pdf->SetXY($pos['x'], $pos['y']);
            $pdf->Cell($pos['width'], 5, 'X', 0, 0, 'C');
            $logger->debug('Spousal support checkbox filled', ['x' => $pos['x'], 'y' => $pos['y']]);
        }
        
        // Attorney Fees checkbox
        if (!empty($data['attorney_fees']) && isset($positions['attorney_fees'])) {
            $pos = $positions['attorney_fees'];
            $pdf->SetFont('Arial', '', $pos['fontSize'] ?? 9);
            $pdf->SetXY($pos['x'], $pos['y']);
            $pdf->Cell($pos['width'], 5, 'X', 0, 0, 'C');
            $logger->debug('Attorney fees checkbox filled', ['x' => $pos['x'], 'y' => $pos['y']]);
        }
        
        // Name Change checkbox
        if (!empty($data['name_change']) && isset($positions['name_change'])) {
            $pos = $positions['name_change'];
            $pdf->SetFont('Arial', '', $pos['fontSize'] ?? 9);
            $pdf->SetXY($pos['x'], $pos['y']);
            $pdf->Cell($pos['width'], 5, 'X', 0, 0, 'C');
            $logger->debug('Name change checkbox filled', ['x' => $pos['x'], 'y' => $pos['y']]);
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
