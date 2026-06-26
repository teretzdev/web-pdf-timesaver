<?php
/**
 * Universal Font Applier
 * Applies fonts to PDFs without requiring position files
 * Works for any PDF by using field names and types
 */

declare(strict_types=1);

namespace WebPdfTimeSaver\Mvp;

class UniversalFontApplier {
    private FontManager $fontManager;
    
    public function __construct() {
        $this->fontManager = new FontManager();
    }
    
    /**
     * Apply fonts to PDF based on field data
     * Works even without position files - uses field names and types
     */
    public function applyFontToField($pdf, string $fieldName, ?string $fieldType = null, ?string $templateId = null, array $fieldPosition = []): void {
        // Infer field type if not provided
        if (!$fieldType) {
            $fieldType = FontManager::inferFieldType($fieldName);
        }
        
        // Get font settings using hierarchy
        $fontSettings = FontManager::getFontSettings($fieldPosition, $templateId, $fieldType);
        
        // Apply to PDF
        FontManager::applyFont($pdf, $fontSettings);
    }
    
    /**
     * Apply default font to PDF (for text without field context)
     */
    public function applyDefaultFont($pdf, ?string $templateId = null): void {
        $fontSettings = FontManager::getFontSettings([], $templateId, null);
        FontManager::applyFont($pdf, $fontSettings);
    }
    
    /**
     * Process array of fields and apply fonts
     * Useful for bulk processing
     */
    public function applyFontsToFields($pdf, array $fields, ?string $templateId = null): void {
        foreach ($fields as $fieldName => $fieldData) {
            $fieldType = $fieldData['type'] ?? null;
            $position = $fieldData['position'] ?? [];
            
            $this->applyFontToField($pdf, $fieldName, $fieldType, $templateId, $position);
        }
    }
    
    /**
     * Get font settings for preview (HTML/CSS)
     */
    public function getFontStylesForPreview(string $fieldName, ?string $fieldType = null, ?string $templateId = null, array $fieldPosition = []): string {
        $fontSettings = FontManager::getFontSettings($fieldPosition, $templateId, $fieldType);
        
        $styles = [];
        $styles[] = 'font-family: ' . ($fontSettings['fontFamily'] ?? 'Arial');
        $styles[] = 'font-size: ' . ($fontSettings['fontSize'] ?? 10) . 'pt';
        
        if (!empty($fontSettings['fontStyle'])) {
            if (strpos($fontSettings['fontStyle'], 'B') !== false) {
                $styles[] = 'font-weight: bold';
            }
            if (strpos($fontSettings['fontStyle'], 'I') !== false) {
                $styles[] = 'font-style: italic';
            }
        }
        
        return implode('; ', $styles);
    }
}

