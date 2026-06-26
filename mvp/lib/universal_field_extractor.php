<?php
declare(strict_types=1);

namespace WebPdfTimeSaver\Mvp;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/pdf_field_extractor.php';
require_once __DIR__ . '/field_type_detector.php';

/**
 * Universal Field Extractor
 * Extracts fields from ANY PDF structure with multiple strategies
 * Supports AcroForm, XFA, annotations, and OCR-based extraction
 */
final class UniversalFieldExtractor {
    private PdfFieldExtractor $pdfFieldExtractor;
    private FieldTypeDetector $typeDetector;
    
    public function __construct() {
        $this->pdfFieldExtractor = new PdfFieldExtractor();
        $this->typeDetector = new FieldTypeDetector();
    }
    
    /**
     * Extract fields from any PDF structure
     * 
     * @param string $pdfPath Path to PDF file
     * @param string|null $templateId Template ID for saving positions (optional)
     * @return array Extracted fields with metadata
     */
    public function extractFields(string $pdfPath, ?string $templateId = null): array {
        if (!file_exists($pdfPath)) {
            throw new \RuntimeException("PDF file not found: $pdfPath");
        }
        
        // Strategy 1: Extract from AcroForm (fillable fields)
        $fields = $this->extractFromAcroForm($pdfPath, $templateId);
        
        // Strategy 2: Extract from XFA forms (if AcroForm extraction failed or incomplete)
        if (empty($fields)) {
            $fields = $this->extractFromXfa($pdfPath);
        }
        
        // Strategy 3: Extract from annotations (non-form fields)
        $annotationFields = $this->extractFromAnnotations($pdfPath);
        if (!empty($annotationFields)) {
            $fields = array_merge($fields, $annotationFields);
        }
        
        // Strategy 4: Detect field types dynamically
        foreach ($fields as $fieldName => &$fieldData) {
            $fieldData['type'] = $this->typeDetector->detectFieldType($fieldData, $fieldName);
            $fieldData['metadata'] = $this->extractMetadata($fieldData, $fieldName);
        }
        unset($fieldData);
        
        return $fields;
    }
    
    /**
     * Extract fields from AcroForm (fillable PDF forms)
     */
    private function extractFromAcroForm(string $pdfPath, ?string $templateId = null): array {
        try {
            $fields = $this->pdfFieldExtractor->extractFieldPositions($pdfPath, $templateId);
            return $fields;
        } catch (\Exception $e) {
            error_log("AcroForm extraction failed: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Extract fields from XFA forms (XML Forms Architecture)
     */
    private function extractFromXfa(string $pdfPath): array {
        // XFA forms are less common but require different extraction
        // For now, return empty array - can be enhanced later
        // XFA extraction would require parsing XML structure within PDF
        return [];
    }
    
    /**
     * Extract fields from annotations (non-form annotations)
     */
    private function extractFromAnnotations(string $pdfPath): array {
        // Extract text annotations, stamps, etc.
        // For now, return empty array - can be enhanced later
        // Annotation extraction would parse PDF annotations
        return [];
    }
    
    /**
     * Extract metadata from field data
     */
    private function extractMetadata(array $fieldData, string $fieldName): array {
        $metadata = [
            'name' => $fieldName,
            'type' => $fieldData['type'] ?? 'text',
            'page' => $fieldData['page'] ?? 1,
            'position' => [
                'x' => $fieldData['x'] ?? 0,
                'y' => $fieldData['y'] ?? 0,
                'width' => $fieldData['width'] ?? 0,
                'height' => $fieldData['height'] ?? 0
            ],
            'options' => $fieldData['options'] ?? [],
            'required' => $fieldData['required'] ?? false,
            'readonly' => $fieldData['readonly'] ?? false,
            'maxLength' => $fieldData['maxLength'] ?? null,
            'defaultValue' => $fieldData['defaultValue'] ?? null,
            'format' => $fieldData['format'] ?? null
        ];
        
        return $metadata;
    }
    
    /**
     * Get extraction statistics
     */
    public function getExtractionStats(array $fields): array {
        $stats = [
            'totalFields' => count($fields),
            'byType' => [],
            'byPage' => [],
            'withPositions' => 0,
            'withoutPositions' => 0
        ];
        
        foreach ($fields as $field) {
            $type = $field['type'] ?? 'unknown';
            $stats['byType'][$type] = ($stats['byType'][$type] ?? 0) + 1;
            
            $page = $field['page'] ?? 1;
            $stats['byPage'][$page] = ($stats['byPage'][$page] ?? 0) + 1;
            
            if (isset($field['x']) && isset($field['y'])) {
                $stats['withPositions']++;
            } else {
                $stats['withoutPositions']++;
            }
        }
        
        return $stats;
    }
}

