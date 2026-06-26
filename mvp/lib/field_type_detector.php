<?php
declare(strict_types=1);

namespace WebPdfTimeSaver\Mvp;

/**
 * Field Type Detector
 * Dynamically detects field types from PDF metadata and properties
 */
final class FieldTypeDetector {
    
    /**
     * Detect field type from field data and name
     * 
     * @param array $fieldData Field data from PDF
     * @param string $fieldName Field name
     * @return string Detected field type
     */
    public function detectFieldType(array $fieldData, string $fieldName): string {
        // Strategy 1: Use PDF metadata if available
        if (isset($fieldData['type'])) {
            // If upstream already set an internal type, don't remap it.
            $existing = (string)$fieldData['type'];
            $existingLower = strtolower(trim($existing));
            if (in_array($existingLower, [
                'text', 'checkbox', 'radio', 'select', 'date', 'email', 'phone',
                'number', 'url', 'signature'
            ], true)) {
                return $existingLower;
            }

            // Otherwise, attempt to map PDF /FT types like Tx/Btn/etc.
            $type = $this->mapPdfTypeToInternal($existing);
            if ($type !== 'text') {
                return $type;
            }
        }
        
        // Strategy 2: Infer from field properties
        $type = $this->inferTypeFromProperties($fieldData);
        if ($type !== 'text') {
            return $type;
        }
        
        // Strategy 3: Infer from field name
        $type = $this->inferTypeFromName($fieldName);
        if ($type !== 'text') {
            return $type;
        }
        
        // Default to text
        return 'text';
    }
    
    /**
     * Map PDF field type to internal type
     */
    private function mapPdfTypeToInternal(string $pdfType): string {
        $t = strtolower(trim($pdfType));
        $typeMap = [
            // PDF /FT values (common)
            'text' => 'text',
            'tx' => 'text',
            'button' => 'checkbox',
            'btn' => 'checkbox',
            'choice' => 'select',
            'ch' => 'select',
            'signature' => 'signature',
            'sig' => 'signature',

            // Also accept internal types (defensive)
            'checkbox' => 'checkbox',
            'radio' => 'radio',
            'radiobutton' => 'radio',
            'select' => 'select',
            'date' => 'date',
            'email' => 'email',
            'phone' => 'phone',
            'number' => 'number',
            'url' => 'url',
        ];

        return $typeMap[$t] ?? 'text';
    }
    
    /**
     * Infer type from field properties
     */
    private function inferTypeFromProperties(array $fieldData): string {
        // Check for options (dropdown/select)
        if (isset($fieldData['options']) && is_array($fieldData['options']) && !empty($fieldData['options'])) {
            return 'select';
        }
        
        // Check for boolean-like values (checkbox)
        if (isset($fieldData['value']) && is_bool($fieldData['value'])) {
            return 'checkbox';
        }
        
        // Check field size (small square fields are often checkboxes)
        if (isset($fieldData['width']) && isset($fieldData['height'])) {
            $width = (float)($fieldData['width'] ?? 0);
            $height = (float)($fieldData['height'] ?? 0);
            
            // Checkboxes are usually small and square (court forms may be larger than 10mm)
            if ($width > 0 && $height > 0 && abs($width - $height) < 4 && $width < 14) {
                return 'checkbox';
            }
        }
        
        // Check for maxLength (short fields might be dates, phones, etc.)
        if (isset($fieldData['maxLength']) && $fieldData['maxLength'] < 20) {
            // Could be date, phone, etc. - will be refined by name analysis
        }
        
        return 'text';
    }
    
    /**
     * Infer type from field name
     */
    private function inferTypeFromName(string $fieldName): string {
        $fieldNameLower = strtolower($fieldName);
        
        // Checkbox patterns
        $checkboxPatterns = [
            '/checkbox/i',
            '/cb[^a-z]/i',
            '/check/i',
            '/yesno/i',
            '/truefalse/i'
        ];
        foreach ($checkboxPatterns as $pattern) {
            if (preg_match($pattern, $fieldName)) {
                return 'checkbox';
            }
        }
        
        // Select/dropdown patterns
        $selectPatterns = [
            '/dropdown/i',
            '/select/i',
            '/choice/i',
            '/option/i',
            '/list/i'
        ];
        foreach ($selectPatterns as $pattern) {
            if (preg_match($pattern, $fieldName)) {
                return 'select';
            }
        }
        
        // Date patterns
        $datePatterns = [
            '/date/i',
            '/dob/i',
            '/birthdate/i',
            '/dt[^a-z]/i'
        ];
        foreach ($datePatterns as $pattern) {
            if (preg_match($pattern, $fieldName)) {
                return 'date';
            }
        }
        
        // Email patterns
        $emailPatterns = [
            '/email/i',
            '/e-mail/i',
            '/mail/i'
        ];
        foreach ($emailPatterns as $pattern) {
            if (preg_match($pattern, $fieldName)) {
                return 'email';
            }
        }
        
        // Phone patterns
        $phonePatterns = [
            '/phone/i',
            '/telephone/i',
            '/tel/i',
            '/mobile/i',
            '/cell/i'
        ];
        foreach ($phonePatterns as $pattern) {
            if (preg_match($pattern, $fieldName)) {
                return 'phone';
            }
        }
        
        // Signature patterns
        $signaturePatterns = [
            '/signature/i',
            '/sign/i',
            '/sig/i'
        ];
        foreach ($signaturePatterns as $pattern) {
            if (preg_match($pattern, $fieldName)) {
                return 'signature';
            }
        }
        
        // Number patterns
        $numberPatterns = [
            '/number/i',
            '/num/i',
            '/amount/i',
            '/quantity/i',
            '/qty/i'
        ];
        foreach ($numberPatterns as $pattern) {
            if (preg_match($pattern, $fieldName)) {
                return 'number';
            }
        }
        
        // URL patterns
        $urlPatterns = [
            '/url/i',
            '/website/i',
            '/web/i',
            '/link/i'
        ];
        foreach ($urlPatterns as $pattern) {
            if (preg_match($pattern, $fieldName)) {
                return 'url';
            }
        }
        
        return 'text';
    }
    
    /**
     * Get field validation rules based on type
     */
    public function getValidationRules(string $fieldType): array {
        $rules = [
            'required' => false,
            'pattern' => null,
            'minLength' => null,
            'maxLength' => null,
            'format' => null
        ];
        
        switch ($fieldType) {
            case 'email':
                $rules['pattern'] = '/^[^\s@]+@[^\s@]+\.[^\s@]+$/';
                $rules['format'] = 'email';
                break;
                
            case 'phone':
                $rules['pattern'] = '/^[\d\s\-\+\(\)]+$/';
                $rules['format'] = 'phone';
                $rules['maxLength'] = 20;
                break;
                
            case 'date':
                $rules['format'] = 'date';
                $rules['pattern'] = '/^\d{4}-\d{2}-\d{2}$/';
                break;
                
            case 'url':
                $rules['pattern'] = '/^https?:\/\/.+/';
                $rules['format'] = 'url';
                break;
                
            case 'number':
                $rules['pattern'] = '/^\d+(\.\d+)?$/';
                $rules['format'] = 'number';
                break;
        }
        
        return $rules;
    }
}

