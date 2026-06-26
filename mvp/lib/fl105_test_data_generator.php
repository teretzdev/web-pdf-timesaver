<?php
/**
 * FL-105 (GC-120) Test Data Generator
 * Declaration Under Uniform Child Custody Jurisdiction and Enforcement Act (UCCJEA)
 */

namespace WebPdfTimeSaver\Mvp;

class FL105TestDataGenerator {
    public static function generateCompleteTestData(): array {
        return [
            // Case Information
            'attorney_name' => 'Sarah Johnson',
            'attorney_bar_number' => '234567',
            'attorney_firm' => 'Johnson Family Law',
            'attorney_address_street' => '456 Legal Avenue',
            'attorney_address_city' => 'Sample',
            'attorney_address_state' => 'CA',
            'attorney_address_zip' => '90012',
            'attorney_phone' => '(213) 555-7890',
            'attorney_fax' => '(213) 555-7891',
            'attorney_email' => 'sjohnson@johnsonlaw.com',
            
            // Self-represented party
            'party_name' => 'Jennifer Martinez',
            'party_address_street' => '789 Maple Street',
            'party_address_city' => 'Sample',
            'party_address_state' => 'CA',
            'party_address_zip' => '90015',
            'party_phone' => '(213) 555-1234',
            'party_email' => 'jennifer.martinez@email.com',
            
            // Court information
            'superior_court_county' => 'Sample',
            'street_address' => '111 North Hill Street',
            'mailing_address' => '111 North Hill Street',
            'city_zip' => 'Sample',
            'branch_name' => 'Stanley Mosk Courthouse',
            
            // Case details
            'petitioner' => 'Jennifer Martinez',
            'respondent' => 'David Martinez',
            'case_number' => '23STFL12345',
            
            // Children information
            'child_1_name' => 'Emma Martinez',
            'child_1_birthdate' => '03/15/2018',
            'child_1_sex' => 'Female',
            'child_1_current_address' => 'Sample',
            'child_1_period_lived' => 'Birth to present',
            
            'child_2_name' => 'Noah Martinez',
            'child_2_birthdate' => '07/22/2020',
            'child_2_sex' => 'Male',
            'child_2_current_address' => 'Sample',
            'child_2_period_lived' => 'Birth to present',
            
            // Residence history
            'residence_1_address' => 'Sample',
            'residence_1_dates' => '01/2020 to present',
            'residence_1_persons' => 'Jennifer Martinez (mother), Emma Martinez, Noah Martinez',
            
            'residence_2_address' => '321 Oak Drive, Pasadena, CA 91101',
            'residence_2_dates' => '06/2018 to 12/2019',
            'residence_2_persons' => 'Jennifer Martinez, David Martinez, Emma Martinez',
            
            // Person with physical custody
            'custody_person_name' => 'Jennifer Martinez',
            'custody_person_relationship' => 'Mother',
            'custody_person_address' => 'Sample',
            
            // Court proceedings
            'prior_proceedings_none' => false,
            'prior_proceedings_exist' => true,
            'prior_court_name' => 'Sample',
            'prior_case_number' => '22STFL98765',
            'prior_case_type' => 'Dissolution of Marriage',
            
            // Person with custody information
            'person_claiming_custody_none' => true,
            'person_claiming_custody_exists' => false,
            
            // Domestic violence
            'domestic_violence_orders_none' => true,
            'domestic_violence_orders_exist' => false,
            
            // Declaration
            'declarant_name' => 'Jennifer Martinez',
            'declarant_role' => 'Petitioner',
            'declaration_date' => date('m/d/Y'),
            'declaration_location' => 'Sample',
            
            // Signature
            'signature' => 'Jennifer Martinez',
            'signature_date' => date('m/d/Y'),
        ];
    }
    
    public static function generateMinimalTestData(): array {
        return [
            'party_name' => 'Jennifer Martinez',
            'petitioner' => 'Jennifer Martinez',
            'respondent' => 'David Martinez',
            'case_number' => '23STFL12345',
            'child_1_name' => 'Emma Martinez',
            'child_1_birthdate' => '03/15/2018',
            'declarant_name' => 'Jennifer Martinez',
            'signature_date' => date('m/d/Y'),
        ];
    }
}
?>
