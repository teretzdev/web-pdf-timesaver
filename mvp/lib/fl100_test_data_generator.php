<?php
declare(strict_types=1);

namespace WebPdfTimeSaver\Mvp;

/**
 * Comprehensive FL-100 Test Data Generator
 * Ensures NO NULL FIELDS in FL-100 form filling
 */
class FL100TestDataGenerator {
    
    /**
     * Generate comprehensive test data for FL-100 form
     * Returns array with ALL fields populated - NO NULL VALUES
     */
    public static function generateCompleteTestData(): array {
        return [
            // Attorney Information - Complete
            'attorney_name' => 'John Michael Smith, Esq.',
            'attorney_firm' => 'Smith & Associates Family Law',
            'attorney_address' => '1234 Legal Plaza, Suite 500',
            'attorney_city_state_zip' => 'Los Angeles, CA 90210',
            'attorney_phone' => '(555) 123-4567',
            'attorney_email' => 'jsmith@smithlaw.com',
            'attorney_bar_number' => '123456',
            
            // Court Information - Complete
            'case_number' => 'FL-2024-001234',
            'court_county' => 'Los Angeles',
            'court_address' => '111 N Hill St, Los Angeles, CA 90012',
            'case_type' => 'Dissolution of Marriage',
            'filing_date' => date('m/d/Y'),
            
            // Parties Information - Complete
            'petitioner_name' => 'Sarah Elizabeth Johnson',
            'respondent_name' => 'Michael David Johnson',
            'petitioner_address' => '123 Main Street, Los Angeles, CA 90210',
            'petitioner_phone' => '(555) 987-6543',
            'respondent_address' => '456 Oak Avenue, Los Angeles, CA 90211',
            
            // Marriage Information - Complete
            'marriage_date' => 'June 15, 2010',
            'separation_date' => 'March 20, 2024',
            'marriage_location' => 'Las Vegas, Nevada',
            'grounds_for_dissolution' => 'Irreconcilable differences',
            'dissolution_type' => 'Dissolution of Marriage',
            
            // Relief Requested - All checked
            'property_division' => 'Yes',
            'spousal_support' => 'Yes',
            'attorney_fees' => 'Yes',
            'name_change' => 'Yes',
            
            // Children Information - Complete
            'has_children' => 'No',
            'children_count' => '0',
            
            // Additional Information - Complete
            'additional_info' => 'Petitioner requests dissolution of marriage based on irreconcilable differences. All community property should be divided equally between the parties.',
            'attorney_signature' => 'John Michael Smith, Attorney at Law',
            'signature_date' => date('m/d/Y')
        ];
    }
    
    /**
     * Generate alternative test data scenarios
     */
    public static function generateAlternativeTestData(): array {
        return [
            // Attorney Information - Alternative
            'attorney_name' => 'Maria Elena Rodriguez, Esq.',
            'attorney_firm' => 'Rodriguez & Partners LLP',
            'attorney_address' => '5678 Justice Boulevard, Floor 12',
            'attorney_city_state_zip' => 'San Francisco, CA 94102',
            'attorney_phone' => '(415) 555-7890',
            'attorney_email' => 'mrodriguez@rodriguezlaw.com',
            'attorney_bar_number' => '789012',
            
            // Court Information - Alternative
            'case_number' => 'FL-2024-005678',
            'court_county' => 'San Francisco',
            'court_address' => '400 McAllister St, San Francisco, CA 94102',
            'case_type' => 'Legal Separation',
            'filing_date' => date('m/d/Y'),
            
            // Parties Information - Alternative
            'petitioner_name' => 'Jennifer Marie Thompson',
            'respondent_name' => 'Robert James Thompson',
            'petitioner_address' => '789 Pine Street, San Francisco, CA 94108',
            'petitioner_phone' => '(415) 555-1234',
            'respondent_address' => '321 Market Street, San Francisco, CA 94105',
            
            // Marriage Information - Alternative
            'marriage_date' => 'August 22, 2015',
            'separation_date' => 'January 10, 2024',
            'marriage_location' => 'San Francisco, California',
            'grounds_for_dissolution' => 'Incapacity to consent',
            'dissolution_type' => 'Legal Separation',
            
            // Relief Requested - Alternative selections
            'property_division' => 'Yes',
            'spousal_support' => 'No',
            'attorney_fees' => 'Yes',
            'name_change' => 'No',
            
            // Children Information - Alternative
            'has_children' => 'Yes',
            'children_count' => '2',
            
            // Additional Information - Alternative
            'additional_info' => 'Petitioner requests legal separation due to respondent\'s incapacity to consent to marriage. Petitioner seeks custody of minor children and equitable division of community property.',
            'attorney_signature' => 'Maria Elena Rodriguez, Attorney at Law',
            'signature_date' => date('m/d/Y')
        ];
    }
    
    /**
     * Validate that all required fields are populated
     */
    public static function validateCompleteData(array $data): array {
        $requiredFields = [
            'attorney_name', 'attorney_firm', 'attorney_address', 'attorney_city_state_zip',
            'attorney_phone', 'attorney_email', 'attorney_bar_number',
            'case_number', 'court_county', 'court_address', 'case_type', 'filing_date',
            'petitioner_name', 'respondent_name', 'petitioner_address', 'petitioner_phone',
            'respondent_address', 'marriage_date', 'separation_date', 'marriage_location',
            'grounds_for_dissolution', 'dissolution_type', 'property_division',
            'spousal_support', 'attorney_fees', 'name_change', 'has_children',
            'children_count', 'additional_info', 'attorney_signature', 'signature_date'
        ];
        
        $missingFields = [];
        $nullFields = [];
        
        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $data)) {
                $missingFields[] = $field;
            } elseif (is_null($data[$field]) || $data[$field] === '') {
                $nullFields[] = $field;
            }
        }
        
        return [
            'is_complete' => empty($missingFields) && empty($nullFields),
            'missing_fields' => $missingFields,
            'null_fields' => $nullFields,
            'total_fields' => count($requiredFields),
            'populated_fields' => count($requiredFields) - count($missingFields) - count($nullFields)
        ];
    }
    
    /**
     * Generate test data for specific field groups
     */
    public static function generateFieldGroupData(string $group): array {
        switch ($group) {
            case 'attorney':
                return [
                    'attorney_name' => 'John Michael Smith, Esq.',
                    'attorney_firm' => 'Smith & Associates Family Law',
                    'attorney_address' => '1234 Legal Plaza, Suite 500',
                    'attorney_city_state_zip' => 'Los Angeles, CA 90210',
                    'attorney_phone' => '(555) 123-4567',
                    'attorney_email' => 'jsmith@smithlaw.com',
                    'attorney_bar_number' => '123456'
                ];
                
            case 'court':
                return [
                    'case_number' => 'FL-2024-001234',
                    'court_county' => 'Los Angeles',
                    'court_address' => '111 N Hill St, Los Angeles, CA 90012',
                    'case_type' => 'Dissolution of Marriage',
                    'filing_date' => date('m/d/Y')
                ];
                
            case 'parties':
                return [
                    'petitioner_name' => 'Sarah Elizabeth Johnson',
                    'respondent_name' => 'Michael David Johnson',
                    'petitioner_address' => '123 Main Street, Los Angeles, CA 90210',
                    'petitioner_phone' => '(555) 987-6543',
                    'respondent_address' => '456 Oak Avenue, Los Angeles, CA 90211'
                ];
                
            case 'marriage':
                return [
                    'marriage_date' => 'June 15, 2010',
                    'separation_date' => 'March 20, 2024',
                    'marriage_location' => 'Las Vegas, Nevada',
                    'grounds_for_dissolution' => 'Irreconcilable differences',
                    'dissolution_type' => 'Dissolution of Marriage'
                ];
                
            case 'relief':
                return [
                    'property_division' => 'Yes',
                    'spousal_support' => 'Yes',
                    'attorney_fees' => 'Yes',
                    'name_change' => 'Yes'
                ];
                
            case 'children':
                return [
                    'has_children' => 'No',
                    'children_count' => '0'
                ];
                
            case 'additional':
                return [
                    'additional_info' => 'Petitioner requests dissolution of marriage based on irreconcilable differences. All community property should be divided equally between the parties.',
                    'attorney_signature' => 'John Michael Smith, Attorney at Law',
                    'signature_date' => date('m/d/Y')
                ];
                
            default:
                return [];
        }
    }
}
