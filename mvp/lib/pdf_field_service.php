<?php
declare(strict_types=1);

namespace WebPdfTimeSaver\Mvp;

require_once __DIR__ . '/../../vendor/autoload.php';
use Smalot\PdfParser\Parser;

final class PdfFieldService {
    
    public function extractFormFields(string $pdfPath): array {
        if (!file_exists($pdfPath)) {
            return [];
        }
        
        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($pdfPath);
            
            // Extract form fields from PDF
            $fields = [];
            
            // Get the PDF content to search for form field names
            $content = $pdf->getText();
            
            // Look for common form field patterns
            $patterns = [
                '/\/T\s*\(([^)]+)\)/',  // /T (fieldname) - field name
                '/\/FT\s*\/Tx/',        // /FT /Tx - text field
                '/\/FT\s*\/Btn/',       // /FT /Btn - button/checkbox
                '/\/FT\s*\/Ch/',        // /FT /Ch - choice/dropdown
            ];
            
            // Extract field names using regex
            preg_match_all('/\/T\s*\(([^)]+)\)/', $content, $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $fieldName) {
                    $fields[] = [
                        'name' => $fieldName,
                        'type' => 'text', // Default to text, could be enhanced
                        'label' => $this->generateLabel($fieldName)
                    ];
                }
            }
            
            // If no fields found with regex, try to extract from AcroForm
            if (empty($fields)) {
                $fields = $this->extractFromAcroForm($pdf);
            }
            
            return $fields;
            
        } catch (\Exception $e) {
            // If parsing fails, return empty array
            return [];
        }
    }
    
    private function extractFromAcroForm($pdf): array {
        $fields = [];
        
        try {
            // Try to get AcroForm data
            $details = $pdf->getDetails();
            
            // Look for form fields in the PDF structure
            if (isset($details['AcroForm'])) {
                // This would need more sophisticated parsing
                // For now, return empty array
            }
            
        } catch (\Exception $e) {
            // Ignore errors
        }
        
        return $fields;
    }
    
    private function generateLabel(string $fieldName): string {
        // Convert field name to a more readable label
        $label = str_replace(['_', '-'], ' ', $fieldName);
        $label = ucwords(strtolower($label));
        return $label;
    }
    
    public function getSamplePdfFields(): array {
        // Return sample form fields for testing when no PDF is available
        return [
            [
                'name' => 'ATTORNEY_NAME',
                'type' => 'text',
                'label' => 'Attorney Name'
            ],
            [
                'name' => 'FIRM_NAME',
                'type' => 'text',
                'label' => 'Firm Name'
            ],
            [
                'name' => 'BAR_NUMBER',
                'type' => 'text',
                'label' => 'Bar Number'
            ],
            [
                'name' => 'COURT_BRANCH',
                'type' => 'choice',
                'label' => 'Court Branch'
            ],
            [
                'name' => 'PETITIONER_NAME',
                'type' => 'text',
                'label' => 'Petitioner Name'
            ],
            [
                'name' => 'RESPONDENT_NAME',
                'type' => 'text',
                'label' => 'Respondent Name'
            ],
            [
                'name' => 'SQUARE',
                'type' => 'text',
                'label' => 'Square'
            ],
            [
                'name' => 'DATE_SIGNED',
                'type' => 'text',
                'label' => 'Date Signed'
            ]
        ];
    }
}















































