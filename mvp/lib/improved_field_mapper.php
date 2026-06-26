<?php
/**
 * Improved Field Name Mapper
 * Uses Levenshtein distance, pattern matching, and comprehensive field dictionaries
 * Based on best practices from fuzzy matching libraries
 */

declare(strict_types=1);

namespace WebPdfTimeSaver\Mvp;

class ImprovedFieldMapper {
    
    /**
     * Comprehensive field name mappings for FL-100
     * Maps PDF field name patterns to test data field names
     */
    private static array $fieldMappings = [
        // Attorney fields - ORDER MATTERS: more specific patterns first
        'attyfor_ft' => 'attorney_name',
        'attyfor' => 'attorney_name',
        'attorneyname' => 'attorney_name',
        'attyname_ft' => 'attorney_name',
        'attyname' => 'attorney_name',
        'attyinfo.*name' => 'attorney_name',
        'attyfirm_ft' => 'attorney_firm',
        'attyfirm' => 'attorney_firm',
        'firmname' => 'attorney_firm',
        'p1caption.*attystreet_ft' => 'attorney_address_street',
        'attystreet_ft' => 'attorney_address',
        'p1caption.*attystreet' => 'attorney_address_street',
        'attystreet' => 'attorney_address',
        'attyaddress' => 'attorney_address',
        'p1caption.*attycity_ft' => 'attorney_address_city',
        'attycity_ft' => 'attorney_city_state_zip', // Note: city/state/zip might be separate
        'p1caption.*attystate_ft' => 'attorney_address_state',
        'p1caption.*attyzip_ft' => 'attorney_address_zip',
        'attyzip_ft' => 'attorney_city_state_zip',
        'attycity' => 'attorney_city_state_zip',
        'attyzip' => 'attorney_city_state_zip',
        // CRITICAL: attorney phone patterns - ORDER MATTERS: most specific first
        // Must match Phone_ft in AttyInfo section - this is the real field
        'attyinfo.*phone_ft' => 'attorney_phone', // Pattern: AttyInfo.Phone_ft
        'captionp1_sf.*attyinfo.*phone_ft' => 'attorney_phone', // Full path pattern
        'attyinfo.*phone' => 'attorney_phone',
        'telephone_ft' => 'attorney_phone',
        'fax_ft' => 'attorney_phone', // Sometimes fax is used for phone
        'attyinfo.*fax' => 'attorney_fax',
        'attyinfo.*email' => 'attorney_email',
        // DO NOT include generic 'phone_ft' here - it will match wrong fields
        'email_ft' => 'attorney_email',
        'email' => 'attorney_email',
        'barno_ft' => 'attorney_bar_number',
        'barnumber' => 'attorney_bar_number',
        'bar' => 'attorney_bar_number',
        
        // Court fields
        'casenumber_ft' => 'case_number',
        'casenumber' => 'case_number',
        'caseno' => 'case_number',
        'caseno0' => 'case_number',
        'p1caption.*caseno' => 'case_number',
        'p1caption.*crtcounty' => 'superior_court_county',
        'crtcounty0' => 'superior_court_county',
        'crtcounty_ft' => 'court_county',
        'crtcounty' => 'court_county',
        'county' => 'court_county',
        'street_ft' => 'court_address', // CourtInfo.Street_ft
        'crtstreet' => 'court_address',
        'p1caption.*crtstreet' => 'street_address',
        'crtstreet0' => 'street_address',
        'courtstreet' => 'court_address',
        'p1caption.*crtmailingadd' => 'mailing_address',
        'crtmailingadd' => 'mailing_address',
        'crtmailingadd0' => 'mailing_address',
        'p1caption.*crtcityzip' => 'city_zip',
        'crtcityzip' => 'city_zip',
        'crtcityzip0' => 'city_zip',
        'p1caption.*crtbranch' => 'branch_name',
        'crtbranch' => 'branch_name',
        'crtbranch0' => 'branch_name',
        'branch_ft' => 'court_branch',
        
        // Party fields - FL-100 structure is complex
        // CRITICAL: petitioner_name - DO NOT use residence field, it's for address!
        'guardianparty.*party5' => 'party_name',
        'guardianparty0party5' => 'party_name',
        'probateparty.*party1' => 'petitioner',
        'probateparty0party1' => 'petitioner',
        'party1_ft' => 'petitioner_name',
        'party1' => 'petitioner_name',
        'printpetitionername_tf' => 'petitioner_name', // Page 3: PrintPetitionerName_tf
        // NOTE: If Party1_ft doesn't exist, petitioner_name will be unmapped (better than wrong field)
        'petitionersresidence_tf' => 'petitioner_address', // Page 1: PetitionersResidence_tf - for ADDRESS only!
        'probateparty.*party2' => 'respondent',
        'probateparty0party2' => 'respondent',
        'party2_ft' => 'respondent_name',
        'party2' => 'respondent_name',
        'respondent' => 'respondent_name',
        'respondentsresidence_tf' => 'respondent_address', // Page 1: RespondentsResidence_tf
        
        // Missing fields (from OCR/text detection) - ONLY use these if real fields don't exist
        'missing_petitioner_phone' => 'petitioner_phone', // Only if no Party1Phone found
        'missing_filing_date' => 'filing_date',
        'missing_marriage_location' => 'marriage_location',
        'missing_grounds_for_dissolution' => 'grounds_for_dissolution',
        
        // Date fields
        'dateofmarriage_dt' => 'marriage_date',
        'marriagedate' => 'marriage_date',
        'dateofseparation_dt' => 'separation_date',
        'separationdate' => 'separation_date',
        'dateofmarriage' => 'marriage_date',
        'dateofseparation' => 'separation_date',
        
        // Form type checkboxes (dissolution type) - map case_type to dissolution checkbox
        'dissolutionof_cb' => 'dissolution_type', // FL-100[0].Page1[0].CaptionP1_sf[0].FormTitle[0].DissolutionOf_cb[0]
        'marriage_cb' => 'dissolution_type', // Marriage checkbox
        'domesticpartnership_cb' => 'dissolution_type',
        'nullityof_cb' => 'dissolution_type',
        'dissolutionof' => 'case_type', // Also map to case_type
        'marriage_cb' => 'case_type',
        
        // Relief requested checkboxes
        'property' => 'property_division',
        'support' => 'spousal_support',
        'childsupport_ft' => 'spousal_support', // ChildSupport_ft might be for spousal support
        'endjurixresupport' => 'spousal_support',
        'fees' => 'attorney_fees',
        'feesandcost_cb' => 'attorney_fees', // FeesAndCost_cb
        'namechange' => 'name_change',
        'name_change' => 'name_change',
        'specifyformername_tf' => 'name_change', // SpecifyFormerName_tf
        'formername' => 'name_change',
        
        // Children checkboxes
        'children_cb' => 'has_children',
        'haschildren' => 'has_children',
        'therearenominorchildren_cb' => 'has_children',
        'nominorchildren' => 'has_children',
        'list6.*name6a' => 'child_1_name',
        'list6.*name6b' => 'child_2_name',
        'list6.*child6a' => 'child_1_current_address',
        'list6.*child6b' => 'child_2_current_address',
        'name6a0' => 'child_1_name',
        'name6b0' => 'child_2_name',
        'child6a0' => 'child_1_current_address',
        'child6b0' => 'child_2_current_address',
        
        // Signature fields
        'sigdate' => 'signature_date',
        'popdec.*sigdate' => 'signature_date',
        'popdec.*printname' => 'declarant_name',
        'printname' => 'signature',
        'popdec0sigdate0' => 'signature_date',
        'popdec0printname0' => 'declarant_name',
        'printname0' => 'signature',
        'signature' => 'attorney_signature',
        'printpetitionerattorneyname_tf' => 'attorney_signature',
        
        // Additional info fields
        'additional' => 'additional_info',
        'specifyotherrequests_tf' => 'additional_info',
        'otherrequests' => 'additional_info',
        
        // Marriage location
        'marriagelocation' => 'marriage_location',
        'location' => 'marriage_location',
        
        // Grounds
        'grounds' => 'grounds_for_dissolution',
        'irreconcilable' => 'grounds_for_dissolution',
    ];
    
    /**
     * Direct hint strings (normalized substrings) for stubborn fields
     */
    private static array $directPdfFieldHints = [
        'attorney_name' => ['attyinfoattyname'],
        'attorney_bar_number' => ['attyinfobarno'],
        'attorney_firm' => ['attyinfoattyfirm'],
        'attorney_address_street' => ['attyinfoattystreet'],
        'attorney_address_city' => ['attyinfoattycity'],
        'attorney_address_state' => ['attyinfoattystate'],
        'attorney_address_zip' => ['attyinfoattyzip'],
        'attorney_phone' => ['attyinfophone'],
        'attorney_fax' => ['attyinfofax'],
        'attorney_email' => ['attyinfoemail'],
        'party_name' => ['guardianpartyparty'],
        'superior_court_county' => ['crtinfocrtcounty'],
        'street_address' => ['crtinfocrtstreet'],
        'mailing_address' => ['crtinfocrtmailingadd'],
        'city_zip' => ['crtinfocrtcityzip'],
        'branch_name' => ['crtinfocrtbranch'],
        'petitioner' => ['probatepartyparty1'],
        'respondent' => ['probatepartyparty2'],
        'case_number' => ['casenocasnumber'],
        'child_1_name' => ['name6a'],
        'child_1_current_address' => ['child6a'],
        'child_2_name' => ['name6b'],
        'child_2_current_address' => ['child6b'],
        'declarant_name' => ['popdecprintname'],
        'signature' => ['popdecprintname'],
        'signature_date' => ['popdecsigdate'],
    ];
    
    /**
     * Map a test data field name to a PDF field position
     * Returns the best matching PDF field name or null
     */
    public static function mapToPdfField(string $testFieldName, array $pdfFields): ?string {
        // Step 1: Try reverse lookup in fieldMappings (PDF pattern -> test field name)
        // Find all PDF patterns that map to this test field
        $patternsToTry = [];
        foreach (self::$fieldMappings as $pdfPattern => $mappedTestField) {
            if ($mappedTestField === $testFieldName) {
                $patternsToTry[] = $pdfPattern;
            }
        }
        
        // CRITICAL: For fields that have conflicts (like petitioner_name vs petitioner_address),
        // prioritize more specific patterns first
        usort($patternsToTry, function($a, $b) {
            // Longer patterns are usually more specific
            $lenA = strlen($a);
            $lenB = strlen($b);
            if ($lenA !== $lenB) {
                return $lenB - $lenA; // Descending order
            }
            // If same length, prioritize patterns with section names (like "attyinfo")
            $hasSectionA = strpos($a, 'info') !== false || strpos($a, 'section') !== false;
            $hasSectionB = strpos($b, 'info') !== false || strpos($b, 'section') !== false;
            if ($hasSectionA && !$hasSectionB) return -1;
            if (!$hasSectionA && $hasSectionB) return 1;
            return 0;
        });
        
        // CRITICAL: For attorney_phone, check Phone_ft first before trying patterns
        if ($testFieldName === 'attorney_phone') {
            // Look for Phone_ft in AttyInfo section first
            foreach ($pdfFields as $pdfField => $fieldData) {
                if (strpos($pdfField, 'Phone_ft') !== false && strpos($pdfField, 'AttyInfo') !== false) {
                    return $pdfField; // Perfect match - return immediately
                }
            }
        }
        
        // Try each pattern (most specific first)
        foreach ($patternsToTry as $pattern) {
            $bestMatch = self::findBestMatch($pattern, $pdfFields);
            if ($bestMatch) {
                return $bestMatch;
            }
        }
        
        // Step 2: Try pattern matching against PDF field names
        $bestMatch = self::patternMatch($testFieldName, $pdfFields);
        if ($bestMatch) {
            return $bestMatch;
        }
        
        // Step 3: Try fuzzy matching with Levenshtein distance (lower threshold for better coverage)
        $bestMatch = self::fuzzyMatch($testFieldName, $pdfFields);
        if ($bestMatch && $bestMatch['score'] > 0.5) { // Lowered from 0.6
            return $bestMatch['field'];
        }
        
        // Step 4: direct hint fallback (template-specific identifiers)
        if (isset(self::$directPdfFieldHints[$testFieldName])) {
            $normalizedFields = [];
            foreach ($pdfFields as $pdfField => $_data) {
                $normalizedFields[$pdfField] = self::normalizeFieldName($pdfField);
            }
            
            foreach (self::$directPdfFieldHints[$testFieldName] as $hint) {
                $hintNormalized = self::normalizeFieldName($hint);
                if ($hintNormalized === '') {
                    continue;
                }
                
                foreach ($normalizedFields as $pdfField => $normalizedName) {
                    if (strpos($normalizedName, $hintNormalized) !== false) {
                        return $pdfField;
                    }
                }
            }
        }
        
        return null;
    }
    
    /**
     * Normalize field name for matching
     */
    private static function normalizeFieldName(string $name): string {
        // Remove common prefixes/suffixes
        $name = strtolower($name);
        $name = preg_replace('/^(the|a|an)_/', '', $name);
        $name = preg_replace('/_(field|label|text|input|ft|cb|dt)$/', '', $name);
        $name = str_replace(['_', '-', ' ', '[', ']', '.', '0'], '', $name);
        return $name;
    }
    
    /**
     * Extract key parts from field name
     */
    private static function extractKeyParts(string $name): array {
        $normalized = self::normalizeFieldName($name);
        // Split on camelCase or numbers
        $parts = preg_split('/(?=[A-Z])|(?<=\d)(?=\D)|(?<=\D)(?=\d)/', $normalized);
        $parts = array_filter($parts, fn($p) => strlen($p) > 1);
        return array_values($parts);
    }
    
    /**
     * Find best match using pattern
     * Prefers text fields over checkboxes when matching name fields
     */
    private static function findBestMatch(string $pattern, array $pdfFields): ?string {
        $patternNormalized = self::normalizeFieldName($pattern);
        $bestMatch = null;
        $bestScore = 0;
        
        // Determine if we're looking for a name field (should prefer text fields, not checkboxes)
        $isNameField = strpos($patternNormalized, 'name') !== false || 
                       strpos($patternNormalized, 'party') !== false ||
                       strpos($patternNormalized, 'petitioner') !== false ||
                       strpos($patternNormalized, 'respondent') !== false;
        
        foreach ($pdfFields as $pdfField => $fieldData) {
            $pdfNormalized = self::normalizeFieldName($pdfField);
            $fieldType = $fieldData['type'] ?? 'text';
            $isMissingField = strpos($pdfField, 'missing_') === 0;
            
            // CRITICAL: For attorney phone, skip missing fields and non-AttyInfo fields
            if (strpos($patternNormalized, 'attorney') !== false && strpos($patternNormalized, 'phone') !== false) {
                if ($isMissingField || strpos($pdfField, 'AttyInfo') === false) {
                    continue; // Skip - not the right field
                }
            }
            
            // Skip checkboxes for name fields (names should be text fields)
            if ($isNameField && $fieldType === 'checkbox') {
                continue;
            }
            
            // Exact match (highest priority) - check normalized versions
            if ($pdfNormalized === $patternNormalized) {
                return $pdfField;
            }
            
            // Extract last part of PDF field name (after last dot) for better matching
            $pdfLastPart = self::normalizeFieldName(basename(str_replace('.', '/', $pdfField)));
            if ($pdfLastPart === $patternNormalized) {
                return $pdfField;
            }
            
            // For attorney phone, prioritize Phone_ft over Fax_ft
            if (strpos($patternNormalized, 'attorney') !== false && strpos($patternNormalized, 'phone') !== false) {
                if (strpos($pdfField, 'AttyInfo') !== false) {
                    if (strpos($pdfField, 'Phone_ft') !== false) {
                        return $pdfField; // Perfect match - Phone_ft in AttyInfo
                    }
                    // Only use Fax_ft if Phone_ft doesn't exist
                    if (strpos($pdfField, 'Fax_ft') !== false) {
                        // Check if Phone_ft exists in fields - if so, skip Fax_ft
                        $hasPhoneFt = false;
                        foreach ($pdfFields as $otherField => $otherData) {
                            if (strpos($otherField, 'Phone_ft') !== false && strpos($otherField, 'AttyInfo') !== false) {
                                $hasPhoneFt = true;
                                break;
                            }
                        }
                        if (!$hasPhoneFt) {
                            // No Phone_ft found, use Fax_ft as fallback
                            continue; // Will be matched later if no Phone_ft
                        }
                    }
                }
            }
            
            // Ends with pattern (common for _ft, _cb suffixes)
            if (strlen($patternNormalized) > 0 && substr($pdfNormalized, -strlen($patternNormalized)) === $patternNormalized) {
                $score = 0.9;
                // Boost score for text fields when matching name fields
                if ($isNameField && $fieldType === 'text') {
                    $score = 0.95;
                }
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestMatch = $pdfField;
                }
            }
            
            // Contains pattern anywhere in the field name
            if (strpos($pdfNormalized, $patternNormalized) !== false) {
                $score = strlen($patternNormalized) / max(strlen($pdfNormalized), 1);
                // Boost score for text fields when matching name fields
                if ($isNameField && $fieldType === 'text') {
                    $score += 0.1;
                }
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestMatch = $pdfField;
                }
            }
            
            // Also check last part
            if (strpos($pdfLastPart, $patternNormalized) !== false) {
                $score = strlen($patternNormalized) / max(strlen($pdfLastPart), 1);
                // Boost score for text fields when matching name fields
                if ($isNameField && $fieldType === 'text') {
                    $score += 0.1;
                }
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestMatch = $pdfField;
                }
            }
        }
        
        return $bestMatch;
    }
    
    /**
     * Pattern-based matching
     */
    private static function patternMatch(string $testField, array $pdfFields): ?string {
        $testParts = self::extractKeyParts($testField);
        $testNormalized = self::normalizeFieldName($testField);
        
        $bestMatch = null;
        $bestScore = 0;
        
        // Build search patterns based on test field
        $patterns = self::buildSearchPatterns($testNormalized, $testParts);
        
        foreach (array_keys($pdfFields) as $pdfField) {
            $pdfNormalized = self::normalizeFieldName($pdfField);
            $pdfParts = self::extractKeyParts($pdfField);
            
            // Try each pattern
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $pdfNormalized)) {
                    // Calculate overlap score
                    $overlap = count(array_intersect($testParts, $pdfParts));
                    $totalParts = max(count($testParts), count($pdfParts));
                    $score = $totalParts > 0 ? ($overlap / $totalParts) : 0;
                    
                    if ($score > $bestScore && $score > 0.5) {
                        $bestScore = $score;
                        $bestMatch = $pdfField;
                    }
                    break;
                }
            }
        }
        
        return $bestMatch;
    }
    
    /**
     * Build search patterns for a field
     */
    private static function buildSearchPatterns(string $normalized, array $parts): array {
        $patterns = [];
        
        // Attorney fields
        if (strpos($normalized, 'attorney') !== false || strpos($normalized, 'atty') !== false) {
            if (strpos($normalized, 'name') !== false || strpos($normalized, 'for') !== false) {
                $patterns[] = '/attyfor/i';
                $patterns[] = '/attorneyname/i';
            }
            if (strpos($normalized, 'firm') !== false) {
                $patterns[] = '/attyfirm|firmname/i';
            }
            if (strpos($normalized, 'address') !== false || strpos($normalized, 'street') !== false) {
                $patterns[] = '/attystreet|attyaddress/i';
            }
            if (strpos($normalized, 'city') !== false || strpos($normalized, 'zip') !== false || strpos($normalized, 'state') !== false) {
                $patterns[] = '/attycity|attyzip|attystate/i';
            }
            if (strpos($normalized, 'phone') !== false || strpos($normalized, 'telephone') !== false) {
                $patterns[] = '/phone|telephone|fax/i';
            }
            if (strpos($normalized, 'email') !== false) {
                $patterns[] = '/email/i';
            }
            if (strpos($normalized, 'bar') !== false) {
                $patterns[] = '/barno|barnumber|bar/i';
            }
        }
        
        // Court fields
        if (strpos($normalized, 'court') !== false || strpos($normalized, 'crt') !== false) {
            if (strpos($normalized, 'county') !== false) {
                $patterns[] = '/crtcounty|county/i';
            }
            if (strpos($normalized, 'address') !== false || strpos($normalized, 'street') !== false) {
                $patterns[] = '/crtstreet|street|courtstreet/i';
            }
        }
        
        // Case number
        if (strpos($normalized, 'case') !== false && strpos($normalized, 'number') !== false) {
            $patterns[] = '/casenumber/i';
        }
        
        // Party fields - Enhanced to find residence fields
        if (strpos($normalized, 'party1') !== false || strpos($normalized, 'petitioner') !== false) {
            if (strpos($normalized, 'name') !== false) {
                // For name fields, ONLY look for name-specific fields (NOT residence/address!)
                $patterns[] = '/party1_ft|printpetitionername|party1[^0-9]/i';
                // Explicitly EXCLUDE residence fields for name matching
            }
            if (strpos($normalized, 'address') !== false || strpos($normalized, 'residence') !== false) {
                // For address/residence fields, ONLY look for address/residence fields
                $patterns[] = '/petitionersresidence|petitioner.*residence/i';
                // Explicitly EXCLUDE name fields for address matching
            }
            if (strpos($normalized, 'phone') !== false) {
                // For petitioner phone, look for phone fields in party1/petitioner sections
                $patterns[] = '/party1.*phone|petitioner.*phone/i';
                // Fallback to missing field if not found
                $patterns[] = '/missing_petitioner_phone/i';
            }
            // General patterns only if not specifically looking for name, address, or phone
            if (strpos($normalized, 'name') === false && strpos($normalized, 'address') === false && strpos($normalized, 'residence') === false && strpos($normalized, 'phone') === false) {
                $patterns[] = '/party1|petitioner/i';
            }
        }
        if (strpos($normalized, 'party2') !== false || strpos($normalized, 'respondent') !== false) {
            if (strpos($normalized, 'name') !== false) {
                // For name fields, ONLY look for name-specific fields
                $patterns[] = '/party2_ft|party2[^0-9]/i';
            }
            if (strpos($normalized, 'address') !== false || strpos($normalized, 'residence') !== false) {
                // For address/residence fields, ONLY look for address/residence fields
                $patterns[] = '/respondentsresidence|respondent.*residence/i';
            }
            // General patterns only if not specifically looking for name or address
            if (strpos($normalized, 'name') === false && strpos($normalized, 'address') === false && strpos($normalized, 'residence') === false) {
                $patterns[] = '/party2|respondent/i';
            }
        }
        
        // Date fields
        if (strpos($normalized, 'marriage') !== false && strpos($normalized, 'date') !== false) {
            $patterns[] = '/dateofmarriage|marriagedate/i';
        }
        if (strpos($normalized, 'separation') !== false && strpos($normalized, 'date') !== false) {
            $patterns[] = '/dateofseparation|separationdate/i';
        }
        if (strpos($normalized, 'signature') !== false && strpos($normalized, 'date') !== false) {
            $patterns[] = '/sigdate|signature.*date/i';
        }
        if (strpos($normalized, 'filing') !== false && strpos($normalized, 'date') !== false) {
            $patterns[] = '/filingdate|filedate/i';
        }
        
        // Form type / dissolution type / case_type
        if (strpos($normalized, 'dissolution') !== false || strpos($normalized, 'casetype') !== false || strpos($normalized, 'casetype') !== false) {
            $patterns[] = '/dissolutionof|marriage_cb|domesticpartnership|nullityof/i';
        }
        
        // Relief requested
        if (strpos($normalized, 'property') !== false && strpos($normalized, 'division') !== false) {
            $patterns[] = '/property.*division|division.*property|commquasiproperty|listproperty/i';
        }
        if (strpos($normalized, 'spousal') !== false || (strpos($normalized, 'support') !== false && strpos($normalized, 'spousal') === false && strpos($normalized, 'child') === false)) {
            $patterns[] = '/support|spousal.*support|childsupport|endjurixresupport/i';
        }
        if (strpos($normalized, 'attorney') !== false && strpos($normalized, 'fees') !== false) {
            $patterns[] = '/attorney.*fees|fees|feesandcost/i';
        }
        if ((strpos($normalized, 'name') !== false && strpos($normalized, 'change') !== false) || strpos($normalized, 'namechange') !== false) {
            $patterns[] = '/namechange|name.*change|specifyformername|formername|restore.*name/i';
        }
        
        // Children
        if (strpos($normalized, 'children') !== false || strpos($normalized, 'haschildren') !== false) {
            $patterns[] = '/children|haschildren|nominorchildren|therearenominorchildren/i';
        }
        
        // Additional info
        if (strpos($normalized, 'additional') !== false || strpos($normalized, 'info') !== false) {
            $patterns[] = '/additional|otherrequests|specifyother|additionalinfo/i';
        }
        
        // Signature
        if (strpos($normalized, 'signature') !== false && strpos($normalized, 'date') === false) {
            $patterns[] = '/signature|printpetitionerattorneyname/i';
        }
        
        // Marriage location
        if (strpos($normalized, 'marriage') !== false && strpos($normalized, 'location') !== false) {
            $patterns[] = '/marriagelocation|location/i';
        }
        
        // Grounds
        if (strpos($normalized, 'grounds') !== false || strpos($normalized, 'dissolution') !== false) {
            $patterns[] = '/grounds|irreconcilable/i';
        }
        
        return $patterns;
    }
    
    /**
     * Fuzzy matching using Levenshtein distance
     */
    private static function fuzzyMatch(string $testField, array $pdfFields): ?array {
        $testNormalized = self::normalizeFieldName($testField);
        $testParts = self::extractKeyParts($testField);
        
        $bestMatch = null;
        $bestScore = 0;
        
        foreach (array_keys($pdfFields) as $pdfField) {
            $pdfNormalized = self::normalizeFieldName($pdfField);
            $pdfParts = self::extractKeyParts($pdfField);
            
            // Calculate multiple similarity metrics
            $levenshteinScore = self::levenshteinSimilarity($testNormalized, $pdfNormalized);
            $wordOverlapScore = self::wordOverlap($testParts, $pdfParts);
            $substringScore = self::substringMatch($testNormalized, $pdfNormalized);
            
            // Combined score (weighted)
            $combinedScore = ($levenshteinScore * 0.4) + ($wordOverlapScore * 0.4) + ($substringScore * 0.2);
            
            if ($combinedScore > $bestScore) {
                $bestScore = $combinedScore;
                $bestMatch = [
                    'field' => $pdfField,
                    'score' => $combinedScore,
                    'levenshtein' => $levenshteinScore,
                    'word_overlap' => $wordOverlapScore,
                    'substring' => $substringScore
                ];
            }
        }
        
        return $bestMatch;
    }
    
    /**
     * Calculate Levenshtein similarity (0-1, where 1 is identical)
     */
    private static function levenshteinSimilarity(string $str1, string $str2): float {
        $maxLen = max(strlen($str1), strlen($str2));
        if ($maxLen === 0) {
            return 1.0;
        }
        
        $distance = levenshtein($str1, $str2);
        return 1 - ($distance / $maxLen);
    }
    
    /**
     * Calculate word overlap score
     */
    private static function wordOverlap(array $words1, array $words2): float {
        if (empty($words1) || empty($words2)) {
            return 0.0;
        }
        
        $intersection = array_intersect($words1, $words2);
        $union = array_unique(array_merge($words1, $words2));
        
        return count($union) > 0 ? (count($intersection) / count($union)) : 0.0;
    }
    
    /**
     * Calculate substring match score
     */
    private static function substringMatch(string $str1, string $str2): float {
        $len1 = strlen($str1);
        $len2 = strlen($str2);
        
        if ($len1 === 0 || $len2 === 0) {
            return 0.0;
        }
        
        // Check if one contains the other
        if (strpos($str2, $str1) !== false) {
            return min(1.0, $len1 / $len2);
        }
        if (strpos($str1, $str2) !== false) {
            return min(1.0, $len2 / $len1);
        }
        
        // Check for common substring
        $longestCommon = self::longestCommonSubstring($str1, $str2);
        $maxLen = max($len1, $len2);
        
        return $maxLen > 0 ? (strlen($longestCommon) / $maxLen) : 0.0;
    }
    
    /**
     * Find longest common substring
     */
    private static function longestCommonSubstring(string $str1, string $str2): string {
        $len1 = strlen($str1);
        $len2 = strlen($str2);
        $longest = '';
        
        for ($i = 0; $i < $len1; $i++) {
            for ($j = $i + 1; $j <= $len1; $j++) {
                $substr = substr($str1, $i, $j - $i);
                if (strpos($str2, $substr) !== false && strlen($substr) > strlen($longest)) {
                    $longest = $substr;
                }
            }
        }
        
        return $longest;
    }
    
    /**
     * Map all test data fields to PDF fields
     */
    public static function mapAllFields(array $testData, array $pdfFields): array {
        $mapping = [];
        
        foreach ($testData as $testField => $value) {
            if (empty($value)) {
                continue;
            }
            
            $pdfField = self::mapToPdfField($testField, $pdfFields);
            if ($pdfField) {
                $mapping[$testField] = $pdfField;
            }
        }
        
        return $mapping;
    }
}

