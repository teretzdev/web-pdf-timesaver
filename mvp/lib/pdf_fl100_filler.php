<?php
declare(strict_types=1);

namespace WebPdfTimeSaver\Mvp;

require_once __DIR__ . '/../../vendor/autoload.php';

use setasign\Fpdi\Fpdi;

/**
 * Specialized FL-100 PDF Form Filler with precise positioning
 */
class FL100PdfFiller {
    private string $outputDir;
    private string $templatePath;
    private array $fieldPositions;
    
    public function __construct(string $outputDir = null) {
        $this->outputDir = $outputDir ?? __DIR__ . '/../../output';
        $this->templatePath = __DIR__ . '/../../uploads/fl100_official.pdf';
        
        // Load field positions
        $positionsFile = __DIR__ . '/../../data/fl100_field_positions.json';
        if (file_exists($positionsFile)) {
            $data = json_decode(file_get_contents($positionsFile), true);
            $this->fieldPositions = $data['field_positions'] ?? [];
        } else {
            $this->fieldPositions = [];
        }
        
        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0777, true);
        }
    }
    
    /**
     * Fill FL-100 form with provided data
     */
    public function fillForm(array $formData, string $outputFilename = null): array {
        if (!file_exists($this->templatePath)) {
            // Try alternative path
            $this->templatePath = __DIR__ . '/../../uploads/fl100.pdf';
            if (!file_exists($this->templatePath)) {
                throw new \Exception("FL-100 template PDF not found. Please upload fl100_official.pdf to /workspace/uploads/");
            }
        }
        
        $pdf = new Fpdi();
        $pdf->SetAutoPageBreak(false);
        
        // Import the template
        $pageCount = $pdf->setSourceFile($this->templatePath);
        
        // Process Page 1
        if ($pageCount >= 1) {
            $tplId = $pdf->importPage(1);
            $pdf->AddPage('P', 'Letter');
            $pdf->useTemplate($tplId, 0, 0);
            
            // Fill Page 1 fields
            $this->fillPage1Fields($pdf, $formData);
        }
        
        // Process Page 2
        if ($pageCount >= 2) {
            $tplId = $pdf->importPage(2);
            $pdf->AddPage('P', 'Letter');
            $pdf->useTemplate($tplId, 0, 0);
            
            // Fill Page 2 fields
            $this->fillPage2Fields($pdf, $formData);
        }
        
        // Generate output filename
        if (!$outputFilename) {
            $outputFilename = 'fl100_filled_' . date('Ymd_His') . '.pdf';
        }
        
        $outputPath = $this->outputDir . '/' . $outputFilename;
        $pdf->Output($outputPath, 'F');
        
        return [
            'success' => true,
            'path' => $outputPath,
            'filename' => $outputFilename,
            'size' => filesize($outputPath)
        ];
    }
    
    /**
     * Fill Page 1 fields with precise positioning
     */
    private function fillPage1Fields(Fpdi $pdf, array $data): void {
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetTextColor(0, 0, 0);
        
        // Attorney Information Section
        if (!empty($data['attorney_name'])) {
            $pdf->SetXY(75, 95);
            $pdf->Cell(250, 12, $data['attorney_name'], 0, 0, 'L');
        }
        
        if (!empty($data['attorney_bar_number'])) {
            $pdf->SetXY(340, 95);
            $pdf->Cell(100, 12, $data['attorney_bar_number'], 0, 0, 'L');
        }
        
        if (!empty($data['attorney_firm'])) {
            $pdf->SetXY(75, 110);
            $pdf->Cell(365, 12, $data['attorney_firm'], 0, 0, 'L');
        }
        
        if (!empty($data['attorney_street']) || !empty($data['attorney_address'])) {
            $pdf->SetXY(75, 125);
            $pdf->Cell(365, 12, $data['attorney_street'] ?? $data['attorney_address'], 0, 0, 'L');
        }
        
        // City, State, ZIP on same line
        $cityStateZip = '';
        if (!empty($data['attorney_city'])) {
            $cityStateZip = $data['attorney_city'];
        }
        if (!empty($data['attorney_state'])) {
            $cityStateZip .= ($cityStateZip ? ', ' : '') . $data['attorney_state'];
        }
        if (!empty($data['attorney_zip'])) {
            $cityStateZip .= ' ' . $data['attorney_zip'];
        }
        if (!empty($data['attorney_city_state_zip'])) {
            $cityStateZip = $data['attorney_city_state_zip'];
        }
        if ($cityStateZip) {
            $pdf->SetXY(75, 140);
            $pdf->Cell(270, 12, $cityStateZip, 0, 0, 'L');
        }
        
        if (!empty($data['attorney_phone'])) {
            $pdf->SetXY(75, 155);
            $pdf->Cell(120, 12, $data['attorney_phone'], 0, 0, 'L');
        }
        
        if (!empty($data['attorney_fax'])) {
            $pdf->SetXY(200, 155);
            $pdf->Cell(120, 12, $data['attorney_fax'], 0, 0, 'L');
        }
        
        if (!empty($data['attorney_email'])) {
            $pdf->SetXY(75, 170);
            $pdf->Cell(365, 12, $data['attorney_email'], 0, 0, 'L');
        }
        
        if (!empty($data['attorney_for'])) {
            $pdf->SetXY(75, 185);
            $pdf->Cell(365, 12, $data['attorney_for'], 0, 0, 'L');
        }
        
        // Court Information Section
        if (!empty($data['court_county'])) {
            $pdf->SetXY(180, 225);
            $pdf->Cell(260, 12, $data['court_county'], 0, 0, 'L');
        }
        
        if (!empty($data['court_street']) || !empty($data['court_address'])) {
            $pdf->SetXY(75, 240);
            $pdf->Cell(365, 12, $data['court_street'] ?? $data['court_address'], 0, 0, 'L');
        }
        
        if (!empty($data['court_mailing'])) {
            $pdf->SetXY(75, 255);
            $pdf->Cell(365, 12, $data['court_mailing'], 0, 0, 'L');
        }
        
        if (!empty($data['court_city_zip'])) {
            $pdf->SetXY(75, 270);
            $pdf->Cell(365, 12, $data['court_city_zip'], 0, 0, 'L');
        }
        
        if (!empty($data['court_branch'])) {
            $pdf->SetXY(75, 285);
            $pdf->Cell(365, 12, $data['court_branch'], 0, 0, 'L');
        }
        
        // Parties Section
        if (!empty($data['petitioner_name'])) {
            $pdf->SetXY(75, 330);
            $pdf->Cell(200, 12, $data['petitioner_name'], 0, 0, 'L');
        }
        
        if (!empty($data['respondent_name'])) {
            $pdf->SetXY(75, 350);
            $pdf->Cell(200, 12, $data['respondent_name'], 0, 0, 'L');
        }
        
        if (!empty($data['case_number'])) {
            $pdf->SetXY(350, 330);
            $pdf->Cell(120, 12, $data['case_number'], 0, 0, 'L');
        }
        
        // Petition For - Checkboxes
        $pdf->SetFont('ZapfDingbats', '', 12);
        
        if (!empty($data['petition_dissolution_marriage']) && $data['petition_dissolution_marriage'] == '1') {
            $pdf->SetXY(72, 402);
            $pdf->Cell(10, 10, '4', 0, 0, 'L'); // Checkmark symbol
        }
        
        if (!empty($data['petition_dissolution_partnership']) && $data['petition_dissolution_partnership'] == '1') {
            $pdf->SetXY(72, 417);
            $pdf->Cell(10, 10, '4', 0, 0, 'L');
        }
        
        if (!empty($data['petition_legal_separation_marriage']) && $data['petition_legal_separation_marriage'] == '1') {
            $pdf->SetXY(287, 402);
            $pdf->Cell(10, 10, '4', 0, 0, 'L');
        }
        
        if (!empty($data['petition_legal_separation_partnership']) && $data['petition_legal_separation_partnership'] == '1') {
            $pdf->SetXY(287, 417);
            $pdf->Cell(10, 10, '4', 0, 0, 'L');
        }
        
        if (!empty($data['petition_nullity_marriage']) && $data['petition_nullity_marriage'] == '1') {
            $pdf->SetXY(72, 437);
            $pdf->Cell(10, 10, '4', 0, 0, 'L');
        }
        
        if (!empty($data['petition_nullity_partnership']) && $data['petition_nullity_partnership'] == '1') {
            $pdf->SetXY(287, 437);
            $pdf->Cell(10, 10, '4', 0, 0, 'L');
        }
        
        // Legal Relationship - Checkboxes
        if (!empty($data['we_are_married']) && $data['we_are_married'] == '1') {
            $pdf->SetXY(72, 482);
            $pdf->Cell(10, 10, '4', 0, 0, 'L');
        }
        
        if (!empty($data['we_are_domestic_partners']) && $data['we_are_domestic_partners'] == '1') {
            $pdf->SetXY(72, 497);
            $pdf->Cell(10, 10, '4', 0, 0, 'L');
        }
        
        // Residence Requirements - Checkboxes  
        if (!empty($data['petitioner_resident']) && $data['petitioner_resident'] == '1') {
            $pdf->SetXY(92, 557);
            $pdf->Cell(10, 10, '4', 0, 0, 'L');
        }
        
        if (!empty($data['respondent_resident']) && $data['respondent_resident'] == '1') {
            $pdf->SetXY(92, 572);
            $pdf->Cell(10, 10, '4', 0, 0, 'L');
        }
        
        // Statistical Facts - Back to regular font
        $pdf->SetFont('Helvetica', '', 10);
        
        // Marriage date
        if (!empty($data['marriage_date'])) {
            // Parse the date if it's in various formats
            $dateStr = $data['marriage_date'];
            if (strpos($dateStr, '-') !== false) {
                // YYYY-MM-DD format
                $parts = explode('-', $dateStr);
                $pdf->SetXY(160, 647);
                $pdf->Cell(25, 12, $parts[1], 0, 0, 'C'); // Month
                $pdf->SetXY(190, 647);
                $pdf->Cell(25, 12, $parts[2], 0, 0, 'C'); // Day
                $pdf->SetXY(220, 647);
                $pdf->Cell(40, 12, $parts[0], 0, 0, 'C'); // Year
            } else {
                // Display as-is if different format
                $pdf->SetXY(160, 647);
                $pdf->Cell(100, 12, $dateStr, 0, 0, 'L');
            }
        } else {
            // Use individual fields if available
            if (!empty($data['date_married_month'])) {
                $pdf->SetXY(160, 647);
                $pdf->Cell(25, 12, $data['date_married_month'], 0, 0, 'C');
            }
            if (!empty($data['date_married_day'])) {
                $pdf->SetXY(190, 647);
                $pdf->Cell(25, 12, $data['date_married_day'], 0, 0, 'C');
            }
            if (!empty($data['date_married_year'])) {
                $pdf->SetXY(220, 647);
                $pdf->Cell(40, 12, $data['date_married_year'], 0, 0, 'C');
            }
        }
        
        // Separation date
        if (!empty($data['separation_date'])) {
            $dateStr = $data['separation_date'];
            if (strpos($dateStr, '-') !== false) {
                $parts = explode('-', $dateStr);
                $pdf->SetXY(160, 667);
                $pdf->Cell(25, 12, $parts[1], 0, 0, 'C'); // Month
                $pdf->SetXY(190, 667);
                $pdf->Cell(25, 12, $parts[2], 0, 0, 'C'); // Day
                $pdf->SetXY(220, 667);
                $pdf->Cell(40, 12, $parts[0], 0, 0, 'C'); // Year
            } else {
                $pdf->SetXY(160, 667);
                $pdf->Cell(100, 12, $dateStr, 0, 0, 'L');
            }
        } else {
            if (!empty($data['date_separated_month'])) {
                $pdf->SetXY(160, 667);
                $pdf->Cell(25, 12, $data['date_separated_month'], 0, 0, 'C');
            }
            if (!empty($data['date_separated_day'])) {
                $pdf->SetXY(190, 667);
                $pdf->Cell(25, 12, $data['date_separated_day'], 0, 0, 'C');
            }
            if (!empty($data['date_separated_year'])) {
                $pdf->SetXY(220, 667);
                $pdf->Cell(40, 12, $data['date_separated_year'], 0, 0, 'C');
            }
        }
        
        // Time from marriage to separation
        if (!empty($data['time_from_marriage_years'])) {
            $pdf->SetXY(240, 687);
            $pdf->Cell(30, 12, $data['time_from_marriage_years'], 0, 0, 'C');
        }
        if (!empty($data['time_from_marriage_months'])) {
            $pdf->SetXY(300, 687);
            $pdf->Cell(30, 12, $data['time_from_marriage_months'], 0, 0, 'C');
        }
    }
    
    /**
     * Fill Page 2 fields with precise positioning
     */
    private function fillPage2Fields(Fpdi $pdf, array $data): void {
        $pdf->SetFont('Helvetica', '', 10);
        $pdf->SetTextColor(0, 0, 0);
        
        // Case number on page 2
        if (!empty($data['case_number'])) {
            $pdf->SetXY(350, 60);
            $pdf->Cell(120, 12, $data['case_number'], 0, 0, 'L');
        }
        
        // Minor Children Section - Checkboxes
        $pdf->SetFont('ZapfDingbats', '', 12);
        
        if (!empty($data['no_minor_children']) && $data['no_minor_children'] == '1') {
            $pdf->SetXY(92, 92);
            $pdf->Cell(10, 10, '4', 0, 0, 'L');
        }
        
        if (!empty($data['minor_children_of_petitioner_respondent']) && $data['minor_children_of_petitioner_respondent'] == '1') {
            $pdf->SetXY(92, 107);
            $pdf->Cell(10, 10, '4', 0, 0, 'L');
        }
        
        // Children information - Back to regular font
        $pdf->SetFont('Helvetica', '', 10);
        
        if (!empty($data['child1_name'])) {
            $pdf->SetXY(120, 127);
            $pdf->Cell(180, 12, $data['child1_name'], 0, 0, 'L');
        }
        if (!empty($data['child1_birthdate'])) {
            $pdf->SetXY(310, 127);
            $pdf->Cell(70, 12, $data['child1_birthdate'], 0, 0, 'L');
        }
        if (!empty($data['child1_age'])) {
            $pdf->SetXY(390, 127);
            $pdf->Cell(30, 12, $data['child1_age'], 0, 0, 'C');
        }
        if (!empty($data['child1_sex'])) {
            $pdf->SetXY(430, 127);
            $pdf->Cell(20, 12, $data['child1_sex'], 0, 0, 'C');
        }
        
        if (!empty($data['child2_name'])) {
            $pdf->SetXY(120, 142);
            $pdf->Cell(180, 12, $data['child2_name'], 0, 0, 'L');
        }
        if (!empty($data['child2_birthdate'])) {
            $pdf->SetXY(310, 142);
            $pdf->Cell(70, 12, $data['child2_birthdate'], 0, 0, 'L');
        }
        
        // Pregnancy status - Checkboxes
        $pdf->SetFont('ZapfDingbats', '', 12);
        
        if (!empty($data['pregnant_no']) && $data['pregnant_no'] == '1') {
            $pdf->SetXY(92, 192);
            $pdf->Cell(10, 10, '4', 0, 0, 'L');
        }
        
        if (!empty($data['pregnant_yes']) && $data['pregnant_yes'] == '1') {
            $pdf->SetXY(92, 207);
            $pdf->Cell(10, 10, '4', 0, 0, 'L');
        }
        
        // Legal Grounds - Checkboxes
        if (!empty($data['grounds_divorce']) && $data['grounds_divorce'] == 'irreconcilable differences') {
            $pdf->SetXY(112, 252);
            $pdf->Cell(10, 10, '4', 0, 0, 'L');
        }
        
        if (!empty($data['grounds_divorce']) && $data['grounds_divorce'] == 'incurable insanity') {
            $pdf->SetXY(112, 267);
            $pdf->Cell(10, 10, '4', 0, 0, 'L');
        }
        
        // Petitioner Requests Section - Checkboxes for various relief
        if (!empty($data['child_custody_to_petitioner']) && $data['child_custody_to_petitioner'] == '1') {
            $pdf->SetXY(112, 337);
            $pdf->Cell(10, 10, '4', 0, 0, 'L');
        }
        
        if (!empty($data['child_custody_to_respondent']) && $data['child_custody_to_respondent'] == '1') {
            $pdf->SetXY(197, 337);
            $pdf->Cell(10, 10, '4', 0, 0, 'L');
        }
        
        if (!empty($data['child_visitation_granted']) && $data['child_visitation_granted'] == '1') {
            $pdf->SetXY(92, 357);
            $pdf->Cell(10, 10, '4', 0, 0, 'L');
        }
        
        if (!empty($data['child_visitation_petitioner']) && $data['child_visitation_petitioner'] == '1') {
            $pdf->SetXY(112, 372);
            $pdf->Cell(10, 10, '4', 0, 0, 'L');
        }
        
        if (!empty($data['child_visitation_respondent']) && $data['child_visitation_respondent'] == '1') {
            $pdf->SetXY(197, 372);
            $pdf->Cell(10, 10, '4', 0, 0, 'L');
        }
        
        if (!empty($data['spousal_support_petitioner']) && $data['spousal_support_petitioner'] == '1') {
            $pdf->SetXY(112, 422);
            $pdf->Cell(10, 10, '4', 0, 0, 'L');
        }
        
        if (!empty($data['spousal_support_respondent']) && $data['spousal_support_respondent'] == '1') {
            $pdf->SetXY(197, 422);
            $pdf->Cell(10, 10, '4', 0, 0, 'L');
        }
        
        if (!empty($data['property_rights_determination']) && $data['property_rights_determination'] == '1' || 
            !empty($data['property_division']) && $data['property_division'] == '1') {
            $pdf->SetXY(92, 467);
            $pdf->Cell(10, 10, '4', 0, 0, 'L');
        }
        
        if (!empty($data['attorney_fees_petitioner']) && $data['attorney_fees_petitioner'] == '1' ||
            !empty($data['attorney_fees']) && $data['attorney_fees'] == '1') {
            $pdf->SetXY(112, 487);
            $pdf->Cell(10, 10, '4', 0, 0, 'L');
        }
        
        if (!empty($data['restore_name']) && $data['restore_name'] == '1' ||
            !empty($data['name_change']) && $data['name_change'] == '1') {
            $pdf->SetXY(92, 512);
            $pdf->Cell(10, 10, '4', 0, 0, 'L');
        }
        
        // Former name - Text field
        $pdf->SetFont('Helvetica', '', 10);
        
        if (!empty($data['former_name'])) {
            $pdf->SetXY(120, 527);
            $pdf->Cell(320, 12, $data['former_name'], 0, 0, 'L');
        }
        
        // Other relief text
        if (!empty($data['other_relief']) || !empty($data['additional_info'])) {
            $text = $data['other_relief'] ?? $data['additional_info'];
            $pdf->SetXY(95, 552);
            // Use MultiCell for longer text
            $pdf->MultiCell(400, 5, $text, 0, 'L');
        }
        
        // Signature section at bottom
        if (!empty($data['signature_date']) || !empty($data['petitioner_signature_date'])) {
            $pdf->SetXY(75, 687);
            $pdf->Cell(100, 12, $data['signature_date'] ?? $data['petitioner_signature_date'], 0, 0, 'L');
        }
        
        if (!empty($data['attorney_signature'])) {
            $pdf->SetXY(250, 727);
            $pdf->Cell(200, 20, $data['attorney_signature'], 0, 0, 'L');
        }
        
        if (!empty($data['attorney_signature_date'])) {
            $pdf->SetXY(75, 727);
            $pdf->Cell(100, 12, $data['attorney_signature_date'], 0, 0, 'L');
        }
    }
}