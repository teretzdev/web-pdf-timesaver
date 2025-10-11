<?php
declare(strict_types=1);

namespace WebPdfTimeSaver\Mvp\FieldFillers;

require_once __DIR__ . '/../field_position_loader.php';

final class AttorneyFieldFiller implements FieldFillerInterface {
    private $positionLoader;
    
    public function __construct() {
        $this->positionLoader = new \WebPdfTimeSaver\Mvp\FieldPositionLoader();
    }
    
    public function fillFields($pdf, array $data, \WebPdfTimeSaver\Mvp\Logger $logger): void {
        $logger->debug('Filling attorney section');
        
        $positions = $this->positionLoader->loadFieldPositions('t_fl100_gc120');
        
        // Attorney Name
        if (!empty($data['attorney_name']) && isset($positions['attorney_name'])) {
            $pos = $positions['attorney_name'];
            $pdf->SetFont('Arial', $pos['fontStyle'] ?? '', $pos['fontSize'] ?? 9);
            $pdf->SetXY($pos['x'], $pos['y']);
            $pdf->Cell($pos['width'], 5, $data['attorney_name'], 0, 0, 'L');
            $logger->debug('Attorney name filled', ['x' => $pos['x'], 'y' => $pos['y'], 'value' => $data['attorney_name']]);
        }
        
        // State Bar Number
        if (!empty($data['attorney_bar_number']) && isset($positions['attorney_bar_number'])) {
            $pos = $positions['attorney_bar_number'];
            $pdf->SetFont('Arial', $pos['fontStyle'] ?? '', $pos['fontSize'] ?? 9);
            $pdf->SetXY($pos['x'], $pos['y']);
            $pdf->Cell($pos['width'], 5, $data['attorney_bar_number'], 0, 0, 'L');
            $logger->debug('Bar number filled', ['x' => $pos['x'], 'y' => $pos['y'], 'value' => $data['attorney_bar_number']]);
        }
        
        // Law Firm Name
        if (!empty($data['attorney_firm']) && isset($positions['attorney_firm'])) {
            $pos = $positions['attorney_firm'];
            $pdf->SetFont('Arial', $pos['fontStyle'] ?? '', $pos['fontSize'] ?? 9);
            $pdf->SetXY($pos['x'], $pos['y']);
            $pdf->Cell($pos['width'], 5, $data['attorney_firm'], 0, 0, 'L');
            $logger->debug('Firm name filled', ['x' => $pos['x'], 'y' => $pos['y'], 'value' => $data['attorney_firm']]);
        }
        
        // Address
        if (!empty($data['attorney_address']) && isset($positions['attorney_address'])) {
            $pos = $positions['attorney_address'];
            $pdf->SetFont('Arial', $pos['fontStyle'] ?? '', $pos['fontSize'] ?? 9);
            $pdf->SetXY($pos['x'], $pos['y']);
            $pdf->Cell($pos['width'], 5, $data['attorney_address'], 0, 0, 'L');
            $logger->debug('Address filled', ['x' => $pos['x'], 'y' => $pos['y'], 'value' => $data['attorney_address']]);
        }
        
        // City, State, ZIP
        if (!empty($data['attorney_city_state_zip']) && isset($positions['attorney_city_state_zip'])) {
            $pos = $positions['attorney_city_state_zip'];
            $pdf->SetFont('Arial', $pos['fontStyle'] ?? '', $pos['fontSize'] ?? 9);
            $pdf->SetXY($pos['x'], $pos['y']);
            $pdf->Cell($pos['width'], 5, $data['attorney_city_state_zip'], 0, 0, 'L');
            $logger->debug('City/State/ZIP filled', ['x' => $pos['x'], 'y' => $pos['y'], 'value' => $data['attorney_city_state_zip']]);
        }
        
        // Phone
        if (!empty($data['attorney_phone']) && isset($positions['attorney_phone'])) {
            $pos = $positions['attorney_phone'];
            $pdf->SetFont('Arial', $pos['fontStyle'] ?? '', $pos['fontSize'] ?? 9);
            $pdf->SetXY($pos['x'], $pos['y']);
            $pdf->Cell($pos['width'], 5, $data['attorney_phone'], 0, 0, 'L');
            $logger->debug('Phone filled', ['x' => $pos['x'], 'y' => $pos['y'], 'value' => $data['attorney_phone']]);
        }
        
        // Email
        if (!empty($data['attorney_email']) && isset($positions['attorney_email'])) {
            $pos = $positions['attorney_email'];
            $pdf->SetFont('Arial', $pos['fontStyle'] ?? '', $pos['fontSize'] ?? 9);
            $pdf->SetXY($pos['x'], $pos['y']);
            $pdf->Cell($pos['width'], 5, $data['attorney_email'], 0, 0, 'L');
            $logger->debug('Email filled', ['x' => $pos['x'], 'y' => $pos['y'], 'value' => $data['attorney_email']]);
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
