<?php

namespace WebPdfTimeSaver\Mvp;

require_once __DIR__ . '/field_categorizer.php';
require_once __DIR__ . '/field_analyzer.php';
require_once __DIR__ . '/field_type_detector.php';

/**
 * Dynamic Template Generator
 * Generates templates dynamically from PDF field extraction
 * Uses FieldCategorizer and FieldAnalyzer for dynamic categorization
 */
class DynamicTemplateGenerator
{
    private $dataDir;
    private $positionsDir;
    private FieldCategorizer $categorizer;
    private FieldAnalyzer $analyzer;
    private FieldTypeDetector $typeDetector;
    
    public function __construct($dataDir = null, $positionsDir = null)
    {
        $this->dataDir = $dataDir ?: __DIR__ . '/../../data';
        $this->positionsDir = $positionsDir ?: __DIR__ . '/../../data';
        $this->categorizer = new FieldCategorizer();
        $this->analyzer = new FieldAnalyzer();
        $this->typeDetector = new FieldTypeDetector();
    }
    
    /**
     * Generate a complete template dynamically from PDF field extraction
     */
    public function generateTemplateFromPdf($templateId, $pdfPath = null)
    {
        // Load field positions from extraction
        $positionsFile = $this->positionsDir . '/' . $templateId . '_positions.json';
        
        if (!file_exists($positionsFile)) {
            throw new \Exception("No field positions found for template: $templateId");
        }
        
        $positions = json_decode(file_get_contents($positionsFile), true);
        if (!$positions) {
            throw new \Exception("Invalid positions file for template: $templateId");
        }
        
        // Generate template structure using dynamic categorization
        $template = [
            'id' => $templateId,
            'code' => $this->extractCodeFromTemplateId($templateId),
            'name' => $this->generateTemplateName($templateId, $positions),
            'pageCount' => $this->calculatePageCount($positions),
            'panels' => $this->generatePanels($positions),
            'fields' => $this->generateFields($positions)
        ];
        
        return $template;
    }
    
    /**
     * Generate panels based on dynamic field categorization
     */
    private function generatePanels($positions)
    {
        // Use FieldCategorizer to dynamically categorize fields
        $panels = $this->categorizer->categorizeFields($positions);
        
        return $panels;
    }
    
    /**
     * Generate field definitions from positions
     */
    private function generateFields($positions)
    {
        $fields = [];
        
        // Categorize fields to get panel assignments
        $panels = $this->categorizer->categorizeFields($positions);
        
        // Create panel ID to field mapping
        $panelFieldMap = [];
        foreach ($panels as $panel) {
            $panelId = $panel['id'];
            $category = $panel['category'] ?? 'general';
            $panelFieldMap[$category] = $panelId;
        }
        
        // Analyze each field and generate definition
        foreach ($positions as $fieldName => $fieldData) {
            $analysis = $this->analyzer->analyzeFieldName($fieldName);
            $category = $analysis['semanticCategory'];
            $panelId = $panelFieldMap[$category] ?? 'general';
            
            // Determine field type using FieldTypeDetector
            $fieldType = $this->typeDetector->detectFieldType($fieldData, $fieldName);
            
            // Get validation rules
            $validationRules = $this->typeDetector->getValidationRules($fieldType);
            
            $fields[] = [
                'key' => $this->sanitizeFieldKey($fieldName),
                'label' => $analysis['suggestedLabel'],
                'type' => $fieldType,
                'panelId' => $panelId,
                'required' => $analysis['isRequired'] || ($validationRules['required'] ?? false),
                'placeholder' => $analysis['suggestedPlaceholder'],
                'validation' => $validationRules,
                'metadata' => [
                    'originalName' => $fieldName,
                    'semanticCategory' => $category,
                    'dataType' => $analysis['dataType'],
                    'fieldPurpose' => $analysis['fieldPurpose']
                ],
                'pdfTarget' => [
                    'formField' => $fieldName
                ]
            ];
        }
        
        return $fields;
    }
    
    /**
     * Calculate page count from positions
     */
    private function calculatePageCount($positions)
    {
        $maxPage = 1;
        foreach ($positions as $fieldData) {
            $page = $fieldData['page'] ?? 1;
            $maxPage = max($maxPage, $page);
        }
        return $maxPage;
    }
    
    /**
     * Extract code from template ID (try to infer from PDF or use template ID)
     */
    private function extractCodeFromTemplateId($templateId)
    {
        // Remove 't_' prefix if present
        $code = preg_replace('/^t_/', '', $templateId);
        
        // Try to extract form code (e.g., fl100, fl105, w9)
        if (preg_match('/^([a-z]+\d+)/i', $code, $matches)) {
            $formCode = strtoupper($matches[1]);
            // Add dash if it looks like a form code (e.g., FL100 -> FL-100)
            if (preg_match('/^([A-Z]+)(\d+)$/', $formCode, $formMatches)) {
                return $formMatches[1] . '-' . $formMatches[2];
            }
            return $formCode;
        }
        
        return strtoupper($code);
    }
    
    /**
     * Generate template name from template ID and fields
     */
    private function generateTemplateName($templateId, $positions)
    {
        // Try to infer name from field analysis
        $categories = [];
        foreach ($positions as $fieldName => $fieldData) {
            $analysis = $this->analyzer->analyzeFieldName($fieldName);
            $category = $analysis['semanticCategory'];
            $categories[$category] = ($categories[$category] ?? 0) + 1;
        }
        
        // If we have legal fields, it's likely a legal form
        if (isset($categories['legal']) && $categories['legal'] > 0) {
            $code = $this->extractCodeFromTemplateId($templateId);
            return "$code Form";
        }
        
        // Fallback to template ID
        $name = preg_replace('/^t_/', '', $templateId);
        $name = str_replace(['_', '-'], ' ', $name);
        return ucwords($name);
    }
    
    /**
     * Sanitize field key
     */
    private function sanitizeFieldKey($fieldName)
    {
        // Normalize field name using analyzer
        $analysis = $this->analyzer->analyzeFieldName($fieldName);
        $normalized = $analysis['normalizedName'];
        
        // Use normalized name as key
        return $normalized;
    }
}
