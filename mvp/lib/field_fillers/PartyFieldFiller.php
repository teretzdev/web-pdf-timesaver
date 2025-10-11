<?php
declare(strict_types=1);

namespace WebPdfTimeSaver\Mvp\FieldFillers;

require_once __DIR__ . '/../field_position_loader.php';

final class PartyFieldFiller implements FieldFillerInterface {
    private $positionLoader;
    
    public function __construct() {
        $this->positionLoader = new \WebPdfTimeSaver\Mvp\FieldPositionLoader();
    }
    
    public function fillFields($pdf, array $data, \WebPdfTimeSaver\Mvp\Logger $logger): void {
        $logger->debug('Filling party section');
        
        $positions = $this->positionLoader->loadFieldPositions('t_fl100_gc120');
        
        // Petitioner Name
        if (!empty($data['petitioner_name']) && isset($positions['petitioner_name'])) {
            $pos = $positions['petitioner_name'];
            $pdf->SetFont('Arial', $pos['fontStyle'] ?? '', $pos['fontSize'] ?? 9);
            $pdf->SetXY($pos['x'], $pos['y']);
            $pdf->Cell($pos['width'], 5, $data['petitioner_name'], 0, 0, 'L');
            $logger->debug('Petitioner filled', ['x' => $pos['x'], 'y' => $pos['y'], 'value' => $data['petitioner_name']]);
        }
        
        // Respondent Name
        if (!empty($data['respondent_name']) && isset($positions['respondent_name'])) {
            $pos = $positions['respondent_name'];
            $pdf->SetFont('Arial', $pos['fontStyle'] ?? '', $pos['fontSize'] ?? 9);
            $pdf->SetXY($pos['x'], $pos['y']);
            $pdf->Cell($pos['width'], 5, $data['respondent_name'], 0, 0, 'L');
            $logger->debug('Respondent filled', ['x' => $pos['x'], 'y' => $pos['y'], 'value' => $data['respondent_name']]);
        }
        
        // Petitioner Address
        if (!empty($data['petitioner_address']) && isset($positions['petitioner_address'])) {
            $pos = $positions['petitioner_address'];
            $pdf->SetFont('Arial', $pos['fontStyle'] ?? '', $pos['fontSize'] ?? 9);
            $pdf->SetXY($pos['x'], $pos['y']);
            $pdf->Cell($pos['width'], 5, $data['petitioner_address'], 0, 0, 'L');
            $logger->debug('Petitioner address filled', ['x' => $pos['x'], 'y' => $pos['y'], 'value' => $data['petitioner_address']]);
        }
        
        // Petitioner Phone
        if (!empty($data['petitioner_phone']) && isset($positions['petitioner_phone'])) {
            $pos = $positions['petitioner_phone'];
            $pdf->SetFont('Arial', $pos['fontStyle'] ?? '', $pos['fontSize'] ?? 9);
            $pdf->SetXY($pos['x'], $pos['y']);
            $pdf->Cell($pos['width'], 5, $data['petitioner_phone'], 0, 0, 'L');
            $logger->debug('Petitioner phone filled', ['x' => $pos['x'], 'y' => $pos['y'], 'value' => $data['petitioner_phone']]);
        }
        
        // Respondent Address
        if (!empty($data['respondent_address']) && isset($positions['respondent_address'])) {
            $pos = $positions['respondent_address'];
            $pdf->SetFont('Arial', $pos['fontStyle'] ?? '', $pos['fontSize'] ?? 9);
            $pdf->SetXY($pos['x'], $pos['y']);
            $pdf->Cell($pos['width'], 5, $data['respondent_address'], 0, 0, 'L');
            $logger->debug('Respondent address filled', ['x' => $pos['x'], 'y' => $pos['y'], 'value' => $data['respondent_address']]);
        }
    }
    
    public function getSectionName(): string {
        return 'Party Information';
    }
    
    public function getHandledFields(): array {
        return [
            'petitioner_name',
            'respondent_name',
            'petitioner_address',
            'petitioner_phone',
            'respondent_address'
        ];
    }
}
