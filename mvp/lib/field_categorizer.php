<?php
declare(strict_types=1);

namespace WebPdfTimeSaver\Mvp;

require_once __DIR__ . '/field_analyzer.php';

/**
 * Field Categorizer
 * Dynamically categorizes fields using clustering and semantic similarity
 */
final class FieldCategorizer {
    private FieldAnalyzer $analyzer;
    
    public function __construct() {
        $this->analyzer = new FieldAnalyzer();
    }
    
    /**
     * Categorize fields into panels/sections
     * 
     * @param array $fields Extracted fields
     * @return array Categories with fields grouped
     */
    public function categorizeFields(array $fields): array {
        // Step 1: Analyze all fields
        $fieldAnalyses = [];
        foreach ($fields as $fieldName => $fieldData) {
            $fieldAnalyses[$fieldName] = $this->analyzer->analyzeFieldName($fieldName);
        }
        
        // Step 2: Group by semantic category
        $categoryGroups = $this->groupBySemanticCategory($fieldAnalyses);
        
        // Step 3: Refine groups using similarity
        $refinedGroups = $this->refineGroups($categoryGroups, $fieldAnalyses);
        
        // Step 4: Generate panels
        $panels = $this->generatePanels($refinedGroups, $fieldAnalyses);
        
        return $panels;
    }
    
    /**
     * Group fields by semantic category
     */
    private function groupBySemanticCategory(array $fieldAnalyses): array {
        $groups = [];
        
        foreach ($fieldAnalyses as $fieldName => $analysis) {
            $category = $analysis['semanticCategory'];
            
            if (!isset($groups[$category])) {
                $groups[$category] = [];
            }
            
            $groups[$category][$fieldName] = $analysis;
        }
        
        return $groups;
    }
    
    /**
     * Refine groups using similarity clustering
     */
    private function refineGroups(array $categoryGroups, array $fieldAnalyses): array {
        $refinedGroups = [];
        
        foreach ($categoryGroups as $category => $fields) {
            if (count($fields) <= 3) {
                // Small groups stay as-is
                $refinedGroups[$category] = $fields;
                continue;
            }
            
            // For larger groups, try to split by sub-categories
            $subGroups = $this->clusterBySimilarity($fields, $fieldAnalyses);
            
            if (count($subGroups) > 1) {
                // Split into sub-groups
                foreach ($subGroups as $subCategory => $subFields) {
                    $refinedGroups[$subCategory] = $subFields;
                }
            } else {
                // Keep as single group
                $refinedGroups[$category] = $fields;
            }
        }
        
        return $refinedGroups;
    }
    
    /**
     * Cluster fields by similarity
     */
    private function clusterBySimilarity(array $fields, array $fieldAnalyses): array {
        $clusters = [];
        $processed = [];
        
        foreach ($fields as $fieldName => $analysis) {
            if (isset($processed[$fieldName])) {
                continue;
            }
            
            $cluster = [$fieldName => $analysis];
            $processed[$fieldName] = true;
            
            // Find similar fields
            foreach ($fields as $otherFieldName => $otherAnalysis) {
                if (isset($processed[$otherFieldName])) {
                    continue;
                }
                
                $similarity = $this->calculateSimilarity($analysis, $otherAnalysis);
                
                if ($similarity > 0.7) {
                    $cluster[$otherFieldName] = $otherAnalysis;
                    $processed[$otherFieldName] = true;
                }
            }
            
            // Determine cluster name
            $clusterName = $this->determineClusterName($cluster);
            $clusters[$clusterName] = $cluster;
        }
        
        return $clusters;
    }
    
    /**
     * Calculate similarity between two field analyses
     */
    private function calculateSimilarity(array $analysis1, array $analysis2): float {
        $similarity = 0.0;
        $factors = 0;
        
        // Semantic category match
        if ($analysis1['semanticCategory'] === $analysis2['semanticCategory']) {
            $similarity += 0.3;
        }
        $factors += 0.3;
        
        // Token overlap
        $tokens1 = $analysis1['tokens'];
        $tokens2 = $analysis2['tokens'];
        $commonTokens = array_intersect($tokens1, $tokens2);
        $allTokens = array_unique(array_merge($tokens1, $tokens2));
        if (count($allTokens) > 0) {
            $tokenSimilarity = count($commonTokens) / count($allTokens);
            $similarity += $tokenSimilarity * 0.4;
        }
        $factors += 0.4;
        
        // Data type match
        if ($analysis1['dataType'] === $analysis2['dataType']) {
            $similarity += 0.2;
        }
        $factors += 0.2;
        
        // Field purpose match
        if ($analysis1['fieldPurpose'] === $analysis2['fieldPurpose']) {
            $similarity += 0.1;
        }
        $factors += 0.1;
        
        // Normalize by factors
        return $factors > 0 ? $similarity / $factors : 0.0;
    }
    
    /**
     * Determine cluster name from fields
     */
    private function determineClusterName(array $cluster): string {
        if (empty($cluster)) {
            return 'general';
        }
        
        // Get most common semantic category
        $categories = [];
        foreach ($cluster as $analysis) {
            $category = $analysis['semanticCategory'];
            $categories[$category] = ($categories[$category] ?? 0) + 1;
        }
        
        $mostCommonCategory = array_search(max($categories), $categories);
        
        // Get most common tokens
        $allTokens = [];
        foreach ($cluster as $analysis) {
            $allTokens = array_merge($allTokens, $analysis['tokens']);
        }
        
        $tokenCounts = array_count_values($allTokens);
        arsort($tokenCounts);
        $topTokens = array_slice(array_keys($tokenCounts), 0, 2);
        
        // Generate name from category and top tokens
        $name = $mostCommonCategory;
        if (!empty($topTokens)) {
            $name .= '_' . implode('_', $topTokens);
        }
        
        return $this->sanitizeCategoryName($name);
    }
    
    /**
     * Sanitize category name for use as panel ID
     */
    private function sanitizeCategoryName(string $name): string {
        $name = strtolower($name);
        $name = preg_replace('/[^a-z0-9_]/', '_', $name);
        $name = preg_replace('/_+/', '_', $name);
        $name = trim($name, '_');
        
        return $name;
    }
    
    /**
     * Generate panels from refined groups
     */
    private function generatePanels(array $refinedGroups, array $fieldAnalyses): array {
        $panels = [];
        $order = 0;
        
        // Define panel order priority
        $panelPriority = [
            'personal' => 1,
            'contact' => 2,
            'address' => 3,
            'legal' => 4,
            'organization' => 5,
            'identification' => 6,
            'financial' => 7,
            'date' => 8,
            'document' => 9,
            'signature' => 10,
            'general' => 99
        ];
        
        // Sort groups by priority
        uksort($refinedGroups, function($a, $b) use ($panelPriority) {
            $priorityA = $panelPriority[$a] ?? 99;
            $priorityB = $panelPriority[$b] ?? 99;
            return $priorityA <=> $priorityB;
        });
        
        foreach ($refinedGroups as $category => $fields) {
            $panelId = $this->sanitizeCategoryName($category);
            $panelLabel = $this->generatePanelLabel($category, $fields);
            
            $panels[] = [
                'id' => $panelId,
                'label' => $panelLabel,
                'order' => $order++,
                'fieldCount' => count($fields),
                'category' => $category
            ];
        }
        
        return $panels;
    }
    
    /**
     * Generate panel label
     */
    private function generatePanelLabel(string $category, array $fields): string {
        // Use category name as base
        $label = ucwords(str_replace('_', ' ', $category));
        
        // Try to infer better label from field names
        $fieldNames = array_keys($fields);
        if (!empty($fieldNames)) {
            $firstFieldAnalysis = $fields[$fieldNames[0]];
            $tokens = $firstFieldAnalysis['tokens'];
            
            // Look for common prefixes
            $commonPrefixes = [
                'attorney' => 'Attorney Information',
                'atty' => 'Attorney Information',
                'court' => 'Court Information',
                'crt' => 'Court Information',
                'party' => 'Party Information',
                'petitioner' => 'Petitioner Information',
                'respondent' => 'Respondent Information',
                'case' => 'Case Information',
                'child' => 'Child Information',
                'contact' => 'Contact Information',
                'address' => 'Address Information',
                'personal' => 'Personal Information'
            ];
            
            foreach ($commonPrefixes as $prefix => $labelText) {
                if (in_array($prefix, $tokens) || strpos(implode(' ', $tokens), $prefix) !== false) {
                    return $labelText;
                }
            }
        }
        
        return $label;
    }
    
    /**
     * Get fields for a specific category
     */
    public function getFieldsForCategory(array $fields, string $category): array {
        $categoryFields = [];
        
        foreach ($fields as $fieldName => $fieldData) {
            $analysis = $this->analyzer->analyzeFieldName($fieldName);
            if ($analysis['semanticCategory'] === $category) {
                $categoryFields[$fieldName] = $fieldData;
            }
        }
        
        return $categoryFields;
    }
}

