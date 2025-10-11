<?php
declare(strict_types=1);

namespace WebPdfTimeSaver\Mvp\FieldFillers;

require_once __DIR__ . '/../field_position_loader.php';

final class CourtFieldFiller implements FieldFillerInterface {
    private $positionLoader;
    
    public function __construct() {
        $this->positionLoader = new \WebPdfTimeSaver\Mvp\FieldPositionLoader();
    }
    
    public function fillFields($pdf, array $data, \WebPdfTimeSaver\Mvp\Logger $logger): void {
        $logger->debug('Filling court section');
        
        $positions = $this->positionLoader->loadFieldPositions('t_fl100_gc120');
        
        // Case Number
        if (!empty($data['case_number']) && isset($positions['case_number'])) {
            $pos = $positions['case_number'];
            $pdf->SetFont('Arial', $pos['fontStyle'] ?? '', $pos['fontSize'] ?? 9);
            $pdf->SetXY($pos['x'], $pos['y']);
            $pdf->Cell($pos['width'], 5, $data['case_number'], 0, 0, 'L');
            $logger->debug('Case number filled', ['x' => $pos['x'], 'y' => $pos['y'], 'value' => $data['case_number']]);
        }
        
        // County
        if (!empty($data['court_county']) && isset($positions['court_county'])) {
            $pos = $positions['court_county'];
            $pdf->SetFont('Arial', $pos['fontStyle'] ?? '', $pos['fontSize'] ?? 9);
            $pdf->SetXY($pos['x'], $pos['y']);
            $pdf->Cell($pos['width'], 5, $data['court_county'], 0, 0, 'L');
            $logger->debug('County filled', ['x' => $pos['x'], 'y' => $pos['y'], 'value' => $data['court_county']]);
        }
        
        // Court Address
        if (!empty($data['court_address']) && isset($positions['court_address'])) {
            $pos = $positions['court_address'];
            $pdf->SetFont('Arial', $pos['fontStyle'] ?? '', $pos['fontSize'] ?? 9);
            $pdf->SetXY($pos['x'], $pos['y']);
            $pdf->Cell($pos['width'], 5, $data['court_address'], 0, 0, 'L');
            $logger->debug('Court address filled', ['x' => $pos['x'], 'y' => $pos['y'], 'value' => $data['court_address']]);
        }
        
        // Case Type
        if (!empty($data['case_type']) && isset($positions['case_type'])) {
            $pos = $positions['case_type'];
            $pdf->SetFont('Arial', $pos['fontStyle'] ?? '', $pos['fontSize'] ?? 9);
            $pdf->SetXY($pos['x'], $pos['y']);
            $pdf->Cell($pos['width'], 5, $data['case_type'], 0, 0, 'L');
            $logger->debug('Case type filled', ['x' => $pos['x'], 'y' => $pos['y'], 'value' => $data['case_type']]);
        }
        
        // Filing Date
        if (!empty($data['filing_date']) && isset($positions['filing_date'])) {
            $pos = $positions['filing_date'];
            $pdf->SetFont('Arial', $pos['fontStyle'] ?? '', $pos['fontSize'] ?? 9);
            $pdf->SetXY($pos['x'], $pos['y']);
            $pdf->Cell($pos['width'], 5, $data['filing_date'], 0, 0, 'L');
            $logger->debug('Filing date filled', ['x' => $pos['x'], 'y' => $pos['y'], 'value' => $data['filing_date']]);
        }
        
        // Additional Info
        if (!empty($data['additional_info']) && isset($positions['additional_info'])) {
            $pos = $positions['additional_info'];
            $pdf->SetFont('Arial', $pos['fontStyle'] ?? '', $pos['fontSize'] ?? 9);
            $pdf->SetXY($pos['x'], $pos['y']);
            $pdf->Cell($pos['width'], 5, $data['additional_info'], 0, 0, 'L');
            $logger->debug('Additional info filled', ['x' => $pos['x'], 'y' => $pos['y'], 'value' => $data['additional_info']]);
        }
    }
    
    public function getSectionName(): string {
        return 'Court Information';
    }
    
    public function getHandledFields(): array {
        return [
            'case_number',
            'court_county',
            'court_address', 
            'case_type',
            'filing_date',
            'additional_info'
        ];
    }
}
