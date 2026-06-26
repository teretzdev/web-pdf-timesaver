<?php
declare(strict_types=1);

namespace WebPdfTimeSaver\Mvp;

require_once __DIR__ . '/field_analyzer.php';

/**
 * Universal Field Mapper
 * Maps fields using semantic similarity matching instead of hard-coded patterns
 */
final class UniversalFieldMapper {
    private FieldAnalyzer $analyzer;
    private array $mappingCache = [];
    
    public function __construct() {
        $this->analyzer = new FieldAnalyzer();
    }
    
    /**
     * Map user field names to PDF field names
     * 
     * @param array $userFields User-provided field names and values
     * @param array $pdfFields Extracted PDF fields
     * @return array Mapping from user fields to PDF fields
     */
    public function mapFields(array $userFields, array $pdfFields): array {
        $mapping = [];
        $normalizedPdfFields = $this->normalizePdfFieldMap($pdfFields);
        
        // Analyze all PDF fields once
        $pdfFieldAnalyses = [];
        foreach ($normalizedPdfFields as $pdfFieldName => $pdfFieldData) {
            $pdfFieldAnalyses[$pdfFieldName] = $this->analyzer->analyzeFieldName($pdfFieldName);
        }
        
        // Map each user field to best matching PDF field (each PDF slot used at most once).
        foreach ($userFields as $userFieldName => $userFieldValue) {
            if (empty($userFieldValue)) {
                continue; // Skip empty fields
            }

            $bestMatch = $this->findBestMatch($userFieldName, $pdfFieldAnalyses, $normalizedPdfFields);

            if ($bestMatch) {
                $mapping[$userFieldName] = $bestMatch;
                unset($normalizedPdfFields[$bestMatch], $pdfFieldAnalyses[$bestMatch]);
            }
        }

        return $mapping;
    }
    
    /**
     * Find best matching PDF field for a user field
     */
    private function findBestMatch(string $userFieldName, array $pdfFieldAnalyses, array $pdfFields): ?string {
        // Check cache first
        $cacheKey = md5($userFieldName . json_encode(array_keys($pdfFields)));
        if (isset($this->mappingCache[$cacheKey])) {
            return $this->mappingCache[$cacheKey];
        }
        
        // Analyze user field
        $userFieldAnalysis = $this->analyzer->analyzeFieldName($userFieldName);
        
        $bestMatch = null;
        $bestScore = 0.0;
        
        // Calculate similarity with each PDF field
        foreach ($pdfFieldAnalyses as $pdfFieldName => $pdfFieldAnalysis) {
            $similarity = $this->calculateFieldSimilarity($userFieldAnalysis, $pdfFieldAnalysis, $userFieldName, $pdfFieldName);
            
            if ($similarity > $bestScore) {
                $bestScore = $similarity;
                $bestMatch = $pdfFieldName;
            }
        }
        
        // Only return match if similarity is above threshold
        if ($bestScore >= 0.5) {
            $this->mappingCache[$cacheKey] = $bestMatch;
            return $bestMatch;
        }
        
        return null;
    }
    
    /**
     * Calculate similarity between user field and PDF field
     */
    private function calculateFieldSimilarity(
        array $userAnalysis,
        array $pdfAnalysis,
        string $userFieldName,
        string $pdfFieldName
    ): float {
        $similarity = 0.0;
        $totalWeight = 0.0;
        
        // 1. Exact name match (highest weight)
        $userNormalized = $userAnalysis['normalizedName'];
        $pdfNormalized = $pdfAnalysis['normalizedName'];
        
        if ($userNormalized === $pdfNormalized) {
            return 1.0; // Perfect match
        }
        
        // 2. Token overlap (high weight)
        $userTokens = $userAnalysis['tokens'];
        $pdfTokens = $pdfAnalysis['tokens'];
        $commonTokens = array_intersect($userTokens, $pdfTokens);
        $allTokens = array_unique(array_merge($userTokens, $pdfTokens));
        
        if (count($allTokens) > 0) {
            $tokenSimilarity = count($commonTokens) / count($allTokens);
            $similarity += $tokenSimilarity * 0.4;
            $totalWeight += 0.4;
        }
        
        // 3. Semantic category match (medium weight)
        if ($userAnalysis['semanticCategory'] === $pdfAnalysis['semanticCategory']) {
            $similarity += 0.3;
        }
        $totalWeight += 0.3;
        
        // 4. Data type match (medium weight)
        if ($userAnalysis['dataType'] === $pdfAnalysis['dataType']) {
            $similarity += 0.2;
        }
        $totalWeight += 0.2;
        
        // 5. Substring match (low weight)
        if (strpos($pdfNormalized, $userNormalized) !== false || strpos($userNormalized, $pdfNormalized) !== false) {
            $substringSimilarity = min(strlen($userNormalized), strlen($pdfNormalized)) / max(strlen($userNormalized), strlen($pdfNormalized));
            $similarity += $substringSimilarity * 0.1;
            $totalWeight += 0.1;
        }
        
        // Normalize by total weight
        if ($totalWeight > 0) {
            $similarity = $similarity / $totalWeight;
        }
        
        return $similarity;
    }
    
    /**
     * Map all fields at once
     */
    public function mapAllFields(array $userFields, array $pdfFields): array {
        return $this->mapFields($userFields, $pdfFields);
    }
    
    /**
     * Get mapping suggestions for a field
     */
    public function getMappingSuggestions(string $userFieldName, array $pdfFields, int $limit = 5): array {
        $normalizedPdfFields = $this->normalizePdfFieldMap($pdfFields);
        $pdfFieldAnalyses = [];
        foreach ($normalizedPdfFields as $pdfFieldName => $pdfFieldData) {
            $pdfFieldAnalyses[$pdfFieldName] = $this->analyzer->analyzeFieldName($pdfFieldName);
        }
        
        $userFieldAnalysis = $this->analyzer->analyzeFieldName($userFieldName);
        
        $suggestions = [];
        foreach ($pdfFieldAnalyses as $pdfFieldName => $pdfFieldAnalysis) {
            $similarity = $this->calculateFieldSimilarity($userFieldAnalysis, $pdfFieldAnalysis, $userFieldName, $pdfFieldName);
            
            $suggestions[] = [
                'pdfFieldName' => $pdfFieldName,
                'similarity' => $similarity,
                'analysis' => $pdfFieldAnalysis
            ];
        }
        
        // Sort by similarity descending
        usort($suggestions, function($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });
        
        // Return top suggestions
        return array_slice($suggestions, 0, $limit);
    }

    /**
     * Normalize PDF fields into a map keyed by string field name.
     * Supports both legacy lists and keyed maps.
     */
    private function normalizePdfFieldMap(array $pdfFields): array {
        $normalized = [];
        foreach ($pdfFields as $key => $value) {
            $fieldName = null;

            if (is_string($key) && $key !== '') {
                $fieldName = $key;
            } elseif (is_string($value) && trim($value) !== '') {
                $fieldName = trim($value);
            } elseif (is_array($value)) {
                $candidate = $value['name'] ?? $value['fieldName'] ?? null;
                if (is_string($candidate) && trim($candidate) !== '') {
                    $fieldName = trim($candidate);
                }
            }

            if ($fieldName === null || $fieldName === '') {
                $fieldName = 'field_' . (string)$key;
            }

            $normalized[$fieldName] = $value;
        }

        return $normalized;
    }
    
    /**
     * Clear mapping cache
     */
    public function clearCache(): void {
        $this->mappingCache = [];
    }
    
    /**
     * Load mapping preferences from storage
     */
    public function loadMappingPreferences(string $templateId): array {
        $preferencesFile = __DIR__ . '/../../data/' . $templateId . '_field_mappings.json';
        
        if (file_exists($preferencesFile)) {
            $preferences = json_decode(file_get_contents($preferencesFile), true);
            return $preferences ?? [];
        }
        
        return [];
    }
    
    /**
     * Save mapping preferences to storage
     */
    public function saveMappingPreferences(string $templateId, array $mappings): void {
        $dataDir = __DIR__ . '/../../data';
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0755, true);
        }
        
        $preferencesFile = $dataDir . '/' . $templateId . '_field_mappings.json';
        file_put_contents($preferencesFile, json_encode($mappings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
    
    /**
     * Apply saved mapping preferences
     */
    public function applyMappingPreferences(array $userFields, array $pdfFields, string $templateId): array {
        $preferences = $this->loadMappingPreferences($templateId);
        
        if (empty($preferences)) {
            // No preferences, use automatic mapping
            return $this->mapFields($userFields, $pdfFields);
        }
        
        $mapping = [];
        $preferencePdfTargetsUsed = [];

        // Apply saved preferences first (one PDF slot per target — skip conflicting duplicates).
        foreach ($preferences as $userField => $pdfField) {
            if (!isset($userFields[$userField]) || !isset($pdfFields[$pdfField])) {
                continue;
            }
            if (isset($preferencePdfTargetsUsed[$pdfField])) {
                continue;
            }
            $mapping[$userField] = $pdfField;
            $preferencePdfTargetsUsed[$pdfField] = true;
        }

        // Auto-map only user keys not covered by preferences; exclude PDF targets already taken.
        $remainingUserFields = array_diff_key($userFields, $mapping);
        $usedPdfTargets = array_values($mapping);
        $remainingPdfFields = [];
        foreach ($pdfFields as $pdfKey => $pdfData) {
            if (!in_array($pdfKey, $usedPdfTargets, true)) {
                $remainingPdfFields[$pdfKey] = $pdfData;
            }
        }
        $remainingMappings = $this->mapFields($remainingUserFields, $remainingPdfFields);
        $mapping = array_merge($mapping, $remainingMappings);

        return $mapping;
    }
}

