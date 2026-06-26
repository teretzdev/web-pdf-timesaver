<?php
/**
 * Field Name Mapper
 * Maps extracted field names (full text labels) to simplified reference names
 */

declare(strict_types=1);

namespace WebPdfTimeSaver\Mvp;

class FieldNameMapper {
    
    /**
     * Map extracted field name to reference field name
     * Uses fuzzy matching and pattern recognition
     */
    public static function mapToReference(string $extractedName, array $referenceFields = []): ?string {
        $extractedNormalized = self::normalizeFieldName($extractedName);
        
        // Direct mappings for common fields
        $directMappings = self::getDirectMappings();
        if (isset($directMappings[$extractedNormalized])) {
            return $directMappings[$extractedNormalized];
        }
        
        // Try fuzzy matching against reference fields
        if (!empty($referenceFields)) {
            $bestMatch = self::fuzzyMatch($extractedNormalized, array_keys($referenceFields));
            if ($bestMatch && $bestMatch['score'] > 0.7) {
                return $bestMatch['field'];
            }
        }
        
        // Pattern-based matching
        return self::patternMatch($extractedName);
    }
    
    /**
     * Normalize field name for comparison
     */
    private static function normalizeFieldName(string $name): string {
        // Remove common prefixes/suffixes
        $name = strtolower($name);
        $name = preg_replace('/^(the|a|an)_/', '', $name);
        $name = preg_replace('/_(field|label|text|input)$/', '', $name);
        $name = str_replace(['_', '-', ' '], '', $name);
        return $name;
    }
    
    /**
     * Get direct mappings for known fields
     */
    private static function getDirectMappings(): array {
        return [
            // Court fields
            'superiorcourtofcaliforniacountyof' => 'court_county',
            'courtnumber' => 'case_number',
            'casenumber' => 'case_number',
            
            // Attorney fields
            'attorneyfor' => 'attorney_name',
            'attorneyname' => 'attorney_name',
            'firmname' => 'attorney_firm',
            'attorneyfirm' => 'attorney_firm',
            'attorneybarnumber' => 'attorney_bar_number',
            'barnumber' => 'attorney_bar_number',
            'attorneyaddress' => 'attorney_address',
            'streetaddress' => 'attorney_address', // FL-100: street_address in attorney section
            'attorneycitystatezip' => 'attorney_city_state_zip',
            'attorneyphone' => 'attorney_phone',
            'telephoneno' => 'attorney_phone', // FL-100: telephone_no in attorney section
            'attorneyemail' => 'attorney_email',
            
            // Party fields
            'petitionername' => 'petitioner_name',
            'petitioneraddress' => 'petitioner_address',
            'petitionercitystatezip' => 'petitioner_city_state_zip',
            'petitionerphone' => 'petitioner_phone',
            'petitioneremail' => 'petitioner_email',
            
            'respondentname' => 'respondent_name',
            'respondentaddress' => 'respondent_address',
            'respondentcitystatezip' => 'respondent_city_state_zip',
            'respondentphone' => 'respondent_phone',
            'respondentemail' => 'respondent_email',
            
            // Common fields
            'name' => 'name',
            'streetaddress' => 'street_address',
            'city' => 'city',
            'state' => 'state',
            'zip' => 'zip',
            'zipcode' => 'zip',
            'phone' => 'phone',
            'telephoneno' => 'attorney_phone', // FL-100: telephone_no maps to attorney_phone
            'telephone' => 'attorney_phone',
            'email' => 'email',
            'date' => 'date',
            'signature' => 'signature',
            'dateofbirth' => 'date_of_birth',
            'birthdate' => 'date_of_birth',
            'dateofseparation' => 'separation_date', // FL-100: date_of_separation
            'separationdate' => 'separation_date',
            'ssn' => 'ssn',
            'socialsecuritynumber' => 'ssn',
        ];
    }
    
    /**
     * Fuzzy match against reference field names
     */
    private static function fuzzyMatch(string $extracted, array $referenceFields): ?array {
        $bestMatch = null;
        $bestScore = 0;
        
        // Extract key words from extracted name
        $extractedWords = self::extractKeywords($extracted);
        
        foreach ($referenceFields as $refField) {
            $refNormalized = self::normalizeFieldName($refField);
            $refWords = self::extractKeywords($refField);
            
            // Calculate word overlap
            $wordOverlap = count(array_intersect($extractedWords, $refWords));
            $totalWords = max(count($extractedWords), count($refWords));
            $wordScore = $totalWords > 0 ? ($wordOverlap / $totalWords) : 0;
            
            // Calculate string similarity
            $similarity = self::calculateSimilarity($extracted, $refNormalized);
            
            // Check for substring matches (bidirectional)
            $substringScore = 0;
            if (strlen($refNormalized) > 3 && strpos($extracted, $refNormalized) !== false) {
                $substringScore = 0.9;
            } elseif (strlen($extracted) > 3 && strpos($refNormalized, $extracted) !== false) {
                $substringScore = 0.85;
            }
            
            // Combined score (weighted)
            $combinedScore = max($similarity, $wordScore * 0.8, $substringScore);
            
            if ($combinedScore > $bestScore) {
                $bestScore = $combinedScore;
                $bestMatch = [
                    'field' => $refField,
                    'score' => $combinedScore
                ];
            }
        }
        
        return $bestMatch;
    }
    
    /**
     * Extract keywords from field name
     */
    private static function extractKeywords(string $fieldName): array {
        // Split by common separators
        $parts = preg_split('/[_\-\s]+/', strtolower($fieldName));
        
        // Filter out common stop words
        $stopWords = ['the', 'a', 'an', 'of', 'and', 'or', 'for', 'to', 'in', 'is', 'be', 'has', 'been', 'with'];
        $keywords = array_filter($parts, function($word) use ($stopWords) {
            return strlen($word) > 2 && !in_array($word, $stopWords);
        });
        
        return array_values($keywords);
    }
    
    /**
     * Calculate string similarity (Levenshtein-based)
     */
    private static function calculateSimilarity(string $str1, string $str2): float {
        $maxLen = max(strlen($str1), strlen($str2));
        if ($maxLen === 0) {
            return 1.0;
        }
        
        $distance = levenshtein($str1, $str2);
        return 1.0 - ($distance / $maxLen);
    }
    
    /**
     * Pattern-based matching for common field types
     */
    private static function patternMatch(string $extractedName): ?string {
        $name = strtolower($extractedName);
        
        $patterns = [
            // Attorney patterns (order matters - more specific first)
            '/attorney.*firm|firm.*attorney/i' => 'attorney_firm',
            '/attorney.*bar.*number|bar.*number/i' => 'attorney_bar_number',
            '/attorney.*email/i' => 'attorney_email',
            '/attorney.*phone|attorney.*telephone/i' => 'attorney_phone',
            '/attorney.*address/i' => 'attorney_address',
            '/attorney.*city.*state.*zip/i' => 'attorney_city_state_zip',
            '/attorney.*name|attorney.*for/i' => 'attorney_name',
            
            // Petitioner patterns
            '/petitioner.*email/i' => 'petitioner_email',
            '/petitioner.*phone|petitioner.*telephone/i' => 'petitioner_phone',
            '/petitioner.*address/i' => 'petitioner_address',
            '/petitioner.*city.*state.*zip/i' => 'petitioner_city_state_zip',
            '/petitioner.*name/i' => 'petitioner_name',
            
            // Respondent patterns
            '/respondent.*email/i' => 'respondent_email',
            '/respondent.*phone|respondent.*telephone/i' => 'respondent_phone',
            '/respondent.*address/i' => 'respondent_address',
            '/respondent.*city.*state.*zip/i' => 'respondent_city_state_zip',
            '/respondent.*name/i' => 'respondent_name',
            '/respondent.*lives.*in/i' => 'respondent_lives_in',
            
            // Court patterns
            '/superior.*court.*california.*county|court.*county/i' => 'court_county',
            '/case.*number/i' => 'case_number',
            '/branch.*name/i' => 'branch_name',
            
            // Date patterns
            '/date.*separation|separation.*date/i' => 'separation_date',
            '/date.*marriage|marriage.*date/i' => 'marriage_date',
            '/marriage.*location/i' => 'marriage_location',
            '/date.*birth|birth.*date|birthdate/i' => 'date_of_birth',
            
            // Signature patterns
            '/petitioner.*signature|signature.*petitioner/i' => 'petitioner_signature',
            '/attorney.*signature|signature.*attorney/i' => 'attorney_signature',
            '/signature.*date|date.*signature/i' => 'signature_date',
            '/signature/i' => 'signature',
            
            // Common patterns
            '/street.*address|address.*street/i' => 'street_address',
            '/city.*state.*zip|zip.*code/i' => 'city_state_zip',
            '/telephone.*no|telephone|phone/i' => 'phone',
            '/email/i' => 'email',
            '/social.*security.*number|ssn/i' => 'ssn',
            
            // Children patterns
            '/minor.*children|has.*children|children.*count/i' => 'has_children',
            '/child.*name|childs.*name/i' => 'childs_name',
            '/child.*custody|custody.*children/i' => 'children_details',
            
            // Relief patterns
            '/relief.*requested|petitioner.*requests/i' => 'relief_requested',
            '/child.*support/i' => 'spousal_support',
            '/property.*division|assets.*debts/i' => 'property_division',
            '/attorneys.*fees/i' => 'attorneys_fees',
            
            // Other patterns
            '/additional.*information/i' => 'additional_information',
            '/for.*court.*use.*only/i' => 'for_court_use_only',
        ];
        
        foreach ($patterns as $pattern => $fieldName) {
            if (preg_match($pattern, $extractedName)) {
                return $fieldName;
            }
        }
        
        // Try keyword-based matching
        $keywords = [
            'marriage' => 'marriage_date',
            'separation' => 'separation_date',
            'petitioner' => 'petitioner_name',
            'respondent' => 'respondent_name',
            'attorney' => 'attorney_name',
            'signature' => 'signature',
            'case' => 'case_number',
            'court' => 'court_county',
        ];
        
        foreach ($keywords as $keyword => $fieldName) {
            if (stripos($extractedName, $keyword) !== false) {
                return $fieldName;
            }
        }
        
        return null;
    }
    
    /**
     * Map all extracted fields to reference format
     */
    public static function mapAllFields(array $extractedFields, array $referenceFields = []): array {
        $mapped = [];
        
        foreach ($extractedFields as $extractedName => $fieldData) {
            $referenceName = self::mapToReference($extractedName, $referenceFields);
            
            if ($referenceName) {
                // Use reference name if mapping found
                $mapped[$referenceName] = $fieldData;
                $mapped[$referenceName]['original_name'] = $extractedName;
                $mapped[$referenceName]['mapped'] = true;
            } else {
                // Keep original name if no mapping found
                $mapped[$extractedName] = $fieldData;
                $mapped[$extractedName]['mapped'] = false;
            }
        }
        
        return $mapped;
    }
    
    /**
     * Convert coordinates from mm to points
     */
    public static function convertMmToPoints(array $fieldData): array {
        if (isset($fieldData['x'])) {
            $fieldData['x'] = (float)$fieldData['x'] * 2.834645669;
        }
        if (isset($fieldData['y'])) {
            $fieldData['y'] = (float)$fieldData['y'] * 2.834645669;
        }
        if (isset($fieldData['width'])) {
            $fieldData['width'] = (float)$fieldData['width'] * 2.834645669;
        }
        if (isset($fieldData['height'])) {
            $fieldData['height'] = (float)$fieldData['height'] * 2.834645669;
        }
        return $fieldData;
    }
    
    /**
     * Normalize extracted positions to reference format
     */
    public static function normalizePositions(array $extractedFields, array $referenceFields = []): array {
        $normalized = [];
        
        foreach ($extractedFields as $extractedName => $fieldData) {
            // Map field name
            $referenceName = self::mapToReference($extractedName, $referenceFields) ?? $extractedName;
            
            // Convert coordinates if needed (assume extracted is in mm if values < 100)
            $x = (float)($fieldData['x'] ?? 0);
            $y = (float)($fieldData['y'] ?? 0);
            
        // Convert mm to points if values suggest mm (typical mm range: 0-210 for A4)
        if ($x < 150 && $y < 150) {
            $fieldData = self::convertMmToPoints($fieldData);
        }
        
        // Normalize field structure
        $normalized[$referenceName] = [
            'x' => round($fieldData['x'] ?? 0, 2),
            'y' => round($fieldData['y'] ?? 0, 2),
            'width' => round($fieldData['width'] ?? 100, 2),
            'height' => round($fieldData['height'] ?? 8, 2),
            'type' => $fieldData['type'] ?? 'text',
            'page' => $fieldData['page'] ?? 1,
            'fontSize' => $fieldData['fontSize'] ?? 10,
        ];
        
        // Preserve metadata
        if (isset($fieldData['confidence'])) {
            $normalized[$referenceName]['confidence'] = $fieldData['confidence'];
        }
        if (isset($fieldData['original_name'])) {
            $normalized[$referenceName]['original_name'] = $fieldData['original_name'];
        } else {
            $normalized[$referenceName]['original_name'] = $extractedName;
        }
        $normalized[$referenceName]['mapped'] = ($referenceName !== $extractedName);
    }
    
    return $normalized;
}

/**
 * Create mapping report
 */
public static function createMappingReport(array $extractedFields, array $referenceFields = []): array {
    $report = [
        'total_extracted' => count($extractedFields),
        'total_reference' => count($referenceFields),
        'mapped' => 0,
        'unmapped' => 0,
        'mappings' => [],
        'unmapped_fields' => []
    ];
    
    foreach ($extractedFields as $extractedName => $fieldData) {
        $referenceName = self::mapToReference($extractedName, $referenceFields);
        
        if ($referenceName) {
            $report['mapped']++;
            $report['mappings'][] = [
                'extracted' => $extractedName,
                'reference' => $referenceName,
                'method' => 'auto'
            ];
        } else {
            $report['unmapped']++;
            $report['unmapped_fields'][] = $extractedName;
        }
    }
    
    $report['mapping_rate'] = $report['total_extracted'] > 0 
        ? ($report['mapped'] / $report['total_extracted']) * 100 
        : 0;
    
    return $report;
}
}

