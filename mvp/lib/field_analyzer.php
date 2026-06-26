<?php
declare(strict_types=1);

namespace WebPdfTimeSaver\Mvp;

/**
 * Field Analyzer
 * Performs semantic analysis of field names to infer meaning and relationships
 */
final class FieldAnalyzer {
    
    /**
     * Analyze field name to extract semantic meaning
     * 
     * @param string $fieldName Field name
     * @return array Analysis results
     */
    public function analyzeFieldName(string $fieldName): array {
        $analysis = [
            'originalName' => $fieldName,
            'normalizedName' => $this->normalizeFieldName($fieldName),
            'tokens' => $this->tokenize($fieldName),
            'semanticCategory' => $this->detectSemanticCategory($fieldName),
            'fieldPurpose' => $this->detectFieldPurpose($fieldName),
            'dataType' => $this->detectDataType($fieldName),
            'isRequired' => $this->isRequiredField($fieldName),
            'isPersonalInfo' => $this->isPersonalInfo($fieldName),
            'isContactInfo' => $this->isContactInfo($fieldName),
            'isAddressInfo' => $this->isAddressInfo($fieldName),
            'suggestedLabel' => $this->generateLabel($fieldName),
            'suggestedPlaceholder' => $this->generatePlaceholder($fieldName)
        ];
        
        return $analysis;
    }
    
    /**
     * Normalize field name for comparison
     */
    private function normalizeFieldName(string $fieldName): string {
        // Remove common prefixes/suffixes
        $normalized = $fieldName;
        $normalized = preg_replace('/^[A-Z0-9]+\[0\]\./', '', $normalized); // Remove form prefixes like "FL-100[0]."
        $normalized = preg_replace('/\[0\]$/', '', $normalized); // Remove array indices
        $normalized = preg_replace('/_ft$|_cb$|_dt$|_tf$|_sf$/i', '', $normalized); // Remove type suffixes
        $normalized = preg_replace('/[^a-zA-Z0-9]/', '_', $normalized); // Replace special chars with underscore
        $normalized = strtolower($normalized);
        $normalized = preg_replace('/_+/', '_', $normalized); // Collapse multiple underscores
        $normalized = trim($normalized, '_');
        
        return $normalized;
    }
    
    /**
     * Tokenize field name into meaningful parts
     */
    private function tokenize(string $fieldName): array {
        $normalized = $this->normalizeFieldName($fieldName);
        
        // Split on underscores and camelCase
        $tokens = preg_split('/[_\s]+/', $normalized);
        
        // Also split camelCase
        $camelCaseTokens = [];
        foreach ($tokens as $token) {
            $camelCaseTokens = array_merge($camelCaseTokens, preg_split('/(?=[A-Z])/', $token));
        }
        
        // Filter out empty tokens and short tokens
        $tokens = array_filter($camelCaseTokens, function($token) {
            return strlen($token) > 1;
        });
        
        return array_values($tokens);
    }
    
    /**
     * Detect semantic category
     */
    private function detectSemanticCategory(string $fieldName): string {
        $fieldNameLower = strtolower($fieldName);
        
        // Personal information
        if (preg_match('/\b(name|first|last|middle|full|person|individual|human)\b/i', $fieldName)) {
            return 'personal';
        }
        
        // Contact information
        if (preg_match('/\b(email|phone|telephone|tel|mobile|cell|fax|contact)\b/i', $fieldName)) {
            return 'contact';
        }
        
        // Address information
        if (preg_match('/\b(address|street|city|state|zip|postal|country|location)\b/i', $fieldName)) {
            return 'address';
        }
        
        // Date/time information
        if (preg_match('/\b(date|time|dob|birth|age|year|month|day)\b/i', $fieldName)) {
            return 'date';
        }
        
        // Financial information
        if (preg_match('/\b(amount|price|cost|fee|payment|salary|income|revenue)\b/i', $fieldName)) {
            return 'financial';
        }
        
        // Identification
        if (preg_match('/\b(id|ssn|ein|tax|license|number|code|identifier)\b/i', $fieldName)) {
            return 'identification';
        }
        
        // Legal/Judicial
        if (preg_match('/\b(case|court|attorney|lawyer|legal|judge|jurisdiction)\b/i', $fieldName)) {
            return 'legal';
        }
        
        // Organization
        if (preg_match('/\b(company|organization|org|firm|business|corp|llc)\b/i', $fieldName)) {
            return 'organization';
        }
        
        // Document
        if (preg_match('/\b(document|file|attachment|upload)\b/i', $fieldName)) {
            return 'document';
        }
        
        // Signature
        if (preg_match('/\b(signature|sign|sig)\b/i', $fieldName)) {
            return 'signature';
        }
        
        return 'general';
    }
    
    /**
     * Detect field purpose
     */
    private function detectFieldPurpose(string $fieldName): string {
        $fieldNameLower = strtolower($fieldName);
        
        // Input fields
        if (preg_match('/\b(input|enter|fill|provide|supply)\b/i', $fieldName)) {
            return 'input';
        }
        
        // Display fields
        if (preg_match('/\b(display|show|view|read|only)\b/i', $fieldName)) {
            return 'display';
        }
        
        // Selection fields
        if (preg_match('/\b(select|choose|pick|option)\b/i', $fieldName)) {
            return 'selection';
        }
        
        // Confirmation fields
        if (preg_match('/\b(confirm|verify|check|validate)\b/i', $fieldName)) {
            return 'confirmation';
        }
        
        return 'input';
    }
    
    /**
     * Detect data type
     */
    private function detectDataType(string $fieldName): string {
        $fieldNameLower = strtolower($fieldName);
        
        if (preg_match('/\b(date|time|dob|birth)\b/i', $fieldName)) {
            return 'date';
        }
        
        if (preg_match('/\b(email|e-mail)\b/i', $fieldName)) {
            return 'email';
        }
        
        if (preg_match('/\b(phone|telephone|tel|mobile|cell)\b/i', $fieldName)) {
            return 'phone';
        }
        
        if (preg_match('/\b(url|website|web|link)\b/i', $fieldName)) {
            return 'url';
        }
        
        if (preg_match('/\b(number|num|amount|quantity|qty|count)\b/i', $fieldName)) {
            return 'number';
        }
        
        if (preg_match('/\b(currency|money|price|cost|fee)\b/i', $fieldName)) {
            return 'currency';
        }
        
        if (preg_match('/\b(boolean|bool|yes|no|true|false)\b/i', $fieldName)) {
            return 'boolean';
        }
        
        return 'string';
    }
    
    /**
     * Check if field is required
     */
    private function isRequiredField(string $fieldName): bool {
        $fieldNameLower = strtolower($fieldName);
        
        // Required patterns
        $requiredPatterns = [
            '/\brequired\b/i',
            '/\bmandatory\b/i',
            '/\bessential\b/i',
            '/\b(.*)\*$/i', // Ends with asterisk
            '/\b(name|email|address|phone)\b/i' // Common required fields
        ];
        
        foreach ($requiredPatterns as $pattern) {
            if (preg_match($pattern, $fieldName)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if field contains personal information
     */
    private function isPersonalInfo(string $fieldName): bool {
        $fieldNameLower = strtolower($fieldName);
        
        $personalPatterns = [
            '/\b(name|first|last|middle|full|person|individual|human|dob|birth|age|ssn|social)\b/i'
        ];
        
        foreach ($personalPatterns as $pattern) {
            if (preg_match($pattern, $fieldName)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if field contains contact information
     */
    private function isContactInfo(string $fieldName): bool {
        $fieldNameLower = strtolower($fieldName);
        
        $contactPatterns = [
            '/\b(email|phone|telephone|tel|mobile|cell|fax|contact)\b/i'
        ];
        
        foreach ($contactPatterns as $pattern) {
            if (preg_match($pattern, $fieldName)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if field contains address information
     */
    private function isAddressInfo(string $fieldName): bool {
        $fieldNameLower = strtolower($fieldName);
        
        $addressPatterns = [
            '/\b(address|street|city|state|zip|postal|country|location)\b/i'
        ];
        
        foreach ($addressPatterns as $pattern) {
            if (preg_match($pattern, $fieldName)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Generate human-readable label
     */
    private function generateLabel(string $fieldName): string {
        $normalized = $this->normalizeFieldName($fieldName);
        
        // Replace underscores with spaces
        $label = str_replace('_', ' ', $normalized);
        
        // Convert camelCase to Title Case
        $label = preg_replace('/([a-z])([A-Z])/', '$1 $2', $label);
        
        // Capitalize first letter of each word
        $label = ucwords(strtolower($label));
        
        // Clean up common abbreviations
        $label = str_replace([' Atty ', ' Crt ', ' Party '], [' Attorney ', ' Court ', ' Party '], $label);
        $label = str_replace([' Fl ', ' Gc '], [' FL-', ' GC-'], $label);
        
        return trim($label);
    }
    
    /**
     * Generate placeholder text
     */
    private function generatePlaceholder(string $fieldName): string {
        // Must not call analyzeFieldName() here — that method invokes generatePlaceholder()
        // and would recurse until Xdebug/stack exhaustion.
        $label = $this->generateLabel($fieldName);
        $dataType = $this->detectDataType($fieldName);
        
        $placeholders = [
            'email' => 'Enter email address',
            'phone' => 'Enter phone number',
            'date' => 'Enter date',
            'url' => 'Enter website URL',
            'number' => 'Enter number',
            'currency' => 'Enter amount',
            'string' => "Enter $label"
        ];
        
        return $placeholders[$dataType] ?? "Enter $label";
    }
    
    /**
     * Analyze multiple fields to find relationships
     */
    public function analyzeFieldRelationships(array $fields): array {
        $relationships = [];
        
        foreach ($fields as $fieldName => $fieldData) {
            $analysis = $this->analyzeFieldName($fieldName);
            $category = $analysis['semanticCategory'];
            
            if (!isset($relationships[$category])) {
                $relationships[$category] = [];
            }
            
            $relationships[$category][$fieldName] = $analysis;
        }
        
        return $relationships;
    }
}

