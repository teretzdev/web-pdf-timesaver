<?php

declare(strict_types=1);

namespace WebPdfTimeSaver\Mvp;

class W9TestDataGenerator
{
    /**
     * Generate test data for IRS W-9 form
     * Using actual field names from the W-9 PDF
     */
    public static function generateCompleteTestData(): array
    {
        return [
            // Name field
            'topmostSubform[0].Page1[0].f1_01[0]' => 'John Michael Smith',
            
            // Business name (if different)
            'topmostSubform[0].Page1[0].f1_02[0]' => 'Smith & Associates LLC',
            
            // Check boxes for federal tax classification
            'topmostSubform[0].Page1[0].Boxes3a-b_ReadOrder[0].c1_1[0]' => 'Yes', // Individual/sole proprietor
            'topmostSubform[0].Page1[0].Boxes3a-b_ReadOrder[0].c1_1[1]' => '',
            'topmostSubform[0].Page1[0].Boxes3a-b_ReadOrder[0].c1_1[2]' => '',
            'topmostSubform[0].Page1[0].c1_1[3]' => '',
            'topmostSubform[0].Page1[0].c1_1[4]' => '',
            'topmostSubform[0].Page1[0].c1_1[5]' => '',
            'topmostSubform[0].Page1[0].c1_1[6]' => '',
            
            // Exemptions
            'topmostSubform[0].Page1[0].f1_03[0]' => '',
            'topmostSubform[0].Page1[0].f1_04[0]' => '',
            
            // Address block
            'topmostSubform[0].Page1[0].f1_05[0]' => '1234 Legal Plaza, Suite 500',
            'topmostSubform[0].Page1[0].f1_06[0]' => 'Sample',
            'topmostSubform[0].Page1[0].Address_ReadOrder[0].f1_07[0]' => 'Mail Stop 45',
            'topmostSubform[0].Page1[0].Address_ReadOrder[0].f1_08[0]' => 'Sample',
            'topmostSubform[0].Page1[0].f1_10[0]' => 'Attn: Accounts Payable',
            
            // Part I - Taxpayer Identification Number
            // SSN segments
            'topmostSubform[0].Page1[0].f1_11[0]' => '123',
            'topmostSubform[0].Page1[0].f1_12[0]' => '45',
            'topmostSubform[0].Page1[0].f1_13[0]' => '6789',
            
            // EIN segments
            'topmostSubform[0].Page1[0].f1_14[0]' => '12',
            'topmostSubform[0].Page1[0].f1_15[0]' => '3456789',
            
            // Part II - Certification
            'topmostSubform[0].Page1[0].f1_08[0]' => 'John Michael Smith',
            'topmostSubform[0].Page1[0].f1_09[0]' => date('m/d/Y'),
        ];
    }
    
    /**
     * Get field labels for display
     */
    public static function getFieldLabels(): array
    {
        return [
            'topmostSubform[0].Page1[0].f1_01[0]' => 'Name',
            'topmostSubform[0].Page1[0].f1_02[0]' => 'Business Name',
            'topmostSubform[0].Page1[0].f1_05[0]' => 'Address',
            'topmostSubform[0].Page1[0].f1_06[0]' => 'City, State, ZIP',
            'topmostSubform[0].Page1[0].social[0].TextField2[0]' => 'SSN (Part 1)',
            'topmostSubform[0].Page1[0].social[0].TextField2[1]' => 'SSN (Part 2)',
            'topmostSubform[0].Page1[0].social[0].TextField2[2]' => 'SSN (Part 3)',
            'topmostSubform[0].Page1[0].f1_08[0]' => 'Signature',
            'topmostSubform[0].Page1[0].f1_09[0]' => 'Date',
        ];
    }
}

