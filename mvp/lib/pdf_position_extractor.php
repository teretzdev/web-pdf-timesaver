<?php
declare(strict_types=1);

namespace WebPdfTimeSaver\Mvp;

require_once __DIR__ . '/../../vendor/autoload.php';

use Smalot\PdfParser\Parser;
use setasign\Fpdi\Fpdi;

/**
 * Extract precise positions of form fields and text elements from PDFs
 */
class PdfPositionExtractor {
    private Parser $parser;
    private array $fieldPositions = [];
    
    public function __construct() {
        $this->parser = new Parser();
    }
    
    /**
     * Extract all form field positions from a PDF
     */
    public function extractFormFieldPositions(string $pdfPath): array {
        if (!file_exists($pdfPath)) {
            throw new \Exception("PDF file not found: $pdfPath");
        }
        
        try {
            // Parse the PDF
            $pdf = $this->parser->parseFile($pdfPath);
            $pages = $pdf->getPages();
            
            $positions = [];
            
            // Extract form fields from the PDF structure
            foreach ($pages as $pageNum => $page) {
                $pageNumber = $pageNum + 1;
                
                // Get page dimensions
                $details = $page->getDetails();
                $pageHeight = $details['MediaBox'][3] ?? 792; // Default to letter size
                $pageWidth = $details['MediaBox'][2] ?? 612;
                
                // Extract text positions using getDataTm
                $textData = $page->getDataTm();
                foreach ($textData as $text) {
                    if (isset($text[0]) && is_array($text[0])) {
                        foreach ($text[0] as $textElement) {
                            if (isset($textElement['c']) && trim($textElement['c']) !== '') {
                                $content = trim($textElement['c']);
                                
                                // Look for field indicators
                                if ($this->isFieldIndicator($content)) {
                                    $fieldName = $this->extractFieldName($content);
                                    if ($fieldName) {
                                        $x = $textElement['x'] ?? 0;
                                        $y = $pageHeight - ($textElement['y'] ?? 0); // Convert to top-down
                                        
                                        $positions[$fieldName] = [
                                            'page' => $pageNumber,
                                            'x' => round($x, 2),
                                            'y' => round($y, 2),
                                            'width' => 100, // Default width
                                            'height' => 10, // Default height
                                            'label' => $content,
                                            'type' => $this->determineFieldType($content)
                                        ];
                                    }
                                }
                            }
                        }
                    }
                }
                
                // Try to extract AcroForm fields if available
                $this->extractAcroFormFields($pdf, $positions, $pageNumber, $pageHeight);
            }
            
            return $positions;
            
        } catch (\Exception $e) {
            error_log("Error extracting PDF positions: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Extract AcroForm fields from PDF
     */
    private function extractAcroFormFields($pdf, &$positions, int $pageNumber, float $pageHeight): void {
        try {
            $catalog = $pdf->getObjectById($pdf->getTrailer()['Root']);
            if (!$catalog) return;
            
            // Look for AcroForm
            if (isset($catalog['AcroForm'])) {
                $acroForm = $pdf->getObjectById($catalog['AcroForm']);
                if (isset($acroForm['Fields'])) {
                    $fields = $acroForm['Fields'];
                    foreach ($fields as $fieldRef) {
                        $field = $pdf->getObjectById($fieldRef);
                        if ($field) {
                            $this->extractFieldInfo($field, $positions, $pageNumber, $pageHeight);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // Silently handle if AcroForm is not available
        }
    }
    
    /**
     * Extract field information from a field object
     */
    private function extractFieldInfo($field, &$positions, int $pageNumber, float $pageHeight): void {
        $fieldName = $field['T'] ?? null;
        if (!$fieldName) return;
        
        // Clean field name
        $fieldName = trim($fieldName, '()');
        $fieldName = str_replace(['(', ')'], '', $fieldName);
        
        // Get rectangle coordinates
        if (isset($field['Rect']) && is_array($field['Rect'])) {
            $rect = $field['Rect'];
            $x = $rect[0] ?? 0;
            $y = $rect[1] ?? 0;
            $x2 = $rect[2] ?? $x + 100;
            $y2 = $rect[3] ?? $y + 10;
            
            $positions[$fieldName] = [
                'page' => $pageNumber,
                'x' => round($x, 2),
                'y' => round($pageHeight - $y2, 2), // Convert to top-down
                'width' => round($x2 - $x, 2),
                'height' => round($y2 - $y, 2),
                'type' => $this->getFieldType($field),
                'label' => $fieldName
            ];
        }
    }
    
    /**
     * Determine field type from field object
     */
    private function getFieldType($field): string {
        if (isset($field['FT'])) {
            switch ($field['FT']) {
                case '/Tx': return 'text';
                case '/Btn': return isset($field['Ff']) && ($field['Ff'] & 65536) ? 'radio' : 'checkbox';
                case '/Ch': return 'choice';
                case '/Sig': return 'signature';
            }
        }
        return 'text';
    }
    
    /**
     * Check if text is a field indicator
     */
    private function isFieldIndicator(string $text): bool {
        $indicators = [
            'name:', 'Name:', 'NAME:',
            'date:', 'Date:', 'DATE:',
            'case number:', 'Case Number:', 'CASE NUMBER:',
            'petitioner:', 'Petitioner:', 'PETITIONER:',
            'respondent:', 'Respondent:', 'RESPONDENT:',
            'attorney:', 'Attorney:', 'ATTORNEY:',
            'address:', 'Address:', 'ADDRESS:',
            'phone:', 'Phone:', 'PHONE:',
            'email:', 'Email:', 'EMAIL:',
            'state bar', 'State Bar', 'bar number', 'Bar Number',
            'firm name', 'Firm Name', 'FIRM NAME',
            'city', 'City', 'state', 'State', 'zip', 'ZIP',
            'signature', 'Signature', 'SIGNATURE'
        ];
        
        $lowerText = strtolower($text);
        foreach ($indicators as $indicator) {
            if (stripos($lowerText, strtolower($indicator)) !== false) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Extract field name from label text
     */
    private function extractFieldName(string $text): ?string {
        // Map common labels to field names
        $mappings = [
            'case number' => 'case_number',
            'petitioner' => 'petitioner_name',
            'respondent' => 'respondent_name',
            'attorney name' => 'attorney_name',
            'attorney or party' => 'attorney_name',
            'firm name' => 'attorney_firm',
            'state bar' => 'attorney_bar_number',
            'bar number' => 'attorney_bar_number',
            'address' => 'attorney_address',
            'city' => 'attorney_city',
            'state' => 'attorney_state',
            'zip' => 'attorney_zip',
            'phone' => 'attorney_phone',
            'email' => 'attorney_email',
            'signature' => 'signature',
            'date' => 'date_signed',
            'child name' => 'child_name',
            'birth date' => 'child_birthdate',
            'sex' => 'child_sex'
        ];
        
        $lowerText = strtolower(trim($text));
        foreach ($mappings as $pattern => $fieldName) {
            if (stripos($lowerText, $pattern) !== false) {
                return $fieldName;
            }
        }
        
        // Generate field name from text
        $fieldName = preg_replace('/[^a-z0-9]+/i', '_', $lowerText);
        $fieldName = trim($fieldName, '_');
        return $fieldName ?: null;
    }
    
    /**
     * Determine field type from content
     */
    private function determineFieldType(string $content): string {
        $lowerContent = strtolower($content);
        
        if (strpos($lowerContent, 'signature') !== false) {
            return 'signature';
        } elseif (strpos($lowerContent, 'date') !== false) {
            return 'date';
        } elseif (strpos($lowerContent, 'email') !== false) {
            return 'email';
        } elseif (strpos($lowerContent, 'phone') !== false) {
            return 'phone';
        } elseif (strpos($lowerContent, '☐') !== false || strpos($lowerContent, '□') !== false) {
            return 'checkbox';
        } elseif (strpos($lowerContent, '○') !== false || strpos($lowerContent, '◯') !== false) {
            return 'radio';
        }
        
        return 'text';
    }
    
    /**
     * Extract positions using FPDI for better coordinate detection
     */
    public function extractWithFpdi(string $pdfPath): array {
        $positions = [];
        
        try {
            $pdf = new Fpdi();
            $pageCount = $pdf->setSourceFile($pdfPath);
            
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $tplIdx = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($tplIdx);
                
                // Store page dimensions for reference
                $positions["_page_{$pageNo}_dimensions"] = [
                    'width' => round($size['width'], 2),
                    'height' => round($size['height'], 2),
                    'orientation' => $size['orientation'] ?? 'P'
                ];
            }
        } catch (\Exception $e) {
            error_log("FPDI extraction failed: " . $e->getMessage());
        }
        
        return $positions;
    }
    
    /**
     * Analyze and extract all positions from a PDF
     */
    public function analyzeDocument(string $pdfPath): array {
        $result = [
            'form_fields' => $this->extractFormFieldPositions($pdfPath),
            'page_info' => $this->extractWithFpdi($pdfPath),
            'extraction_date' => date('Y-m-d H:i:s'),
            'pdf_file' => basename($pdfPath)
        ];
        
        return $result;
    }
    
    /**
     * Generate FL-100 specific field positions based on standard layout
     */
    public function generateFL100Positions(): array {
        // These are approximate positions for FL-100 form fields in millimeters
        // Based on standard California FL-100 form layout
        return [
            'case_number' => [
                'page' => 1,
                'x' => 145,
                'y' => 25,
                'width' => 45,
                'height' => 7,
                'type' => 'text',
                'label' => 'Case Number'
            ],
            'attorney_name' => [
                'page' => 1,
                'x' => 35,
                'y' => 56,
                'width' => 85,
                'height' => 5,
                'type' => 'text',
                'label' => 'Attorney Name'
            ],
            'attorney_bar_number' => [
                'page' => 1,
                'x' => 155,
                'y' => 56,
                'width' => 35,
                'height' => 5,
                'type' => 'text',
                'label' => 'State Bar Number'
            ],
            'attorney_firm' => [
                'page' => 1,
                'x' => 45,
                'y' => 66,
                'width' => 145,
                'height' => 5,
                'type' => 'text',
                'label' => 'Firm Name'
            ],
            'attorney_address' => [
                'page' => 1,
                'x' => 40,
                'y' => 76,
                'width' => 150,
                'height' => 5,
                'type' => 'text',
                'label' => 'Street Address'
            ],
            'attorney_city_state_zip' => [
                'page' => 1,
                'x' => 55,
                'y' => 86,
                'width' => 135,
                'height' => 5,
                'type' => 'text',
                'label' => 'City, State, ZIP'
            ],
            'attorney_phone' => [
                'page' => 1,
                'x' => 35,
                'y' => 96,
                'width' => 65,
                'height' => 5,
                'type' => 'phone',
                'label' => 'Telephone'
            ],
            'attorney_email' => [
                'page' => 1,
                'x' => 125,
                'y' => 96,
                'width' => 65,
                'height' => 5,
                'type' => 'email',
                'label' => 'E-Mail'
            ],
            'court_county' => [
                'page' => 1,
                'x' => 50,
                'y' => 126,
                'width' => 70,
                'height' => 5,
                'type' => 'text',
                'label' => 'County'
            ],
            'petitioner_name' => [
                'page' => 1,
                'x' => 55,
                'y' => 146,
                'width' => 135,
                'height' => 5,
                'type' => 'text',
                'label' => 'Petitioner'
            ],
            'respondent_name' => [
                'page' => 1,
                'x' => 55,
                'y' => 161,
                'width' => 135,
                'height' => 5,
                'type' => 'text',
                'label' => 'Respondent'
            ],
            'dissolution_marriage' => [
                'page' => 1,
                'x' => 30,
                'y' => 215,
                'width' => 5,
                'height' => 5,
                'type' => 'checkbox',
                'label' => 'Dissolution of Marriage'
            ],
            'legal_separation' => [
                'page' => 1,
                'x' => 30,
                'y' => 225,
                'width' => 5,
                'height' => 5,
                'type' => 'checkbox',
                'label' => 'Legal Separation'
            ],
            'nullity_marriage' => [
                'page' => 1,
                'x' => 30,
                'y' => 235,
                'width' => 5,
                'height' => 5,
                'type' => 'checkbox',
                'label' => 'Nullity of Marriage'
            ]
        ];
    }
}