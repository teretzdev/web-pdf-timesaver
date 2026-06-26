<?php
/**
 * Universal Font Manager
 * Manages font settings for all PDFs with hierarchy: field > template > fieldType > default
 */

declare(strict_types=1);

namespace WebPdfTimeSaver\Mvp;

class FontManager {
    private static ?array $config = null;
    private static ?array $templateOverrides = null;
    
    /**
     * Get font configuration
     */
    private static function getConfig(): array {
        if (self::$config === null) {
            $configFile = __DIR__ . '/../../config/fonts.php';
            if (file_exists($configFile)) {
                self::$config = require $configFile;
            } else {
                // Fallback defaults
                self::$config = [
                    'defaults' => [
                        'fontFamily' => 'Arial',
                        'fontSize' => 10,
                        'fontStyle' => '',
                    ],
                    'fieldTypes' => [],
                    'templates' => [],
                ];
            }
        }
        return self::$config;
    }
    
    /**
     * Get font settings for a field with hierarchy
     * Priority: field position > template override > field type > defaults
     */
    public static function getFontSettings(
        array $fieldPosition = [],
        ?string $templateId = null,
        ?string $fieldType = null
    ): array {
        $config = self::getConfig();
        
        // Start with defaults
        $font = [
            'fontFamily' => $config['defaults']['fontFamily'] ?? 'Arial',
            'fontSize' => $config['defaults']['fontSize'] ?? 10,
            'fontStyle' => $config['defaults']['fontStyle'] ?? '',
        ];
        
        // Apply field type defaults
        if ($fieldType && isset($config['fieldTypes'][$fieldType])) {
            $typeConfig = $config['fieldTypes'][$fieldType];
            $font['fontFamily'] = $typeConfig['fontFamily'] ?? $font['fontFamily'];
            $font['fontSize'] = $typeConfig['fontSize'] ?? $font['fontSize'];
            $font['fontStyle'] = $typeConfig['fontStyle'] ?? $font['fontStyle'];
        }
        
        // Apply template overrides
        if ($templateId && isset($config['templates'][$templateId])) {
            $templateConfig = $config['templates'][$templateId];
            $font['fontFamily'] = $templateConfig['fontFamily'] ?? $font['fontFamily'];
            $font['fontSize'] = $templateConfig['fontSize'] ?? $font['fontSize'];
            $font['fontStyle'] = $templateConfig['fontStyle'] ?? $font['fontStyle'];
        }
        
        // Apply field-specific overrides (highest priority)
        if (!empty($fieldPosition)) {
            $font['fontFamily'] = $fieldPosition['fontFamily'] ?? $font['fontFamily'];
            $font['fontSize'] = $fieldPosition['fontSize'] ?? $font['fontSize'];
            $font['fontStyle'] = $fieldPosition['fontStyle'] ?? $font['fontStyle'];
        }
        
        return $font;
    }
    
    /**
     * Apply font to PDF object
     */
    public static function applyFont($pdf, array $fontSettings): void {
        $fontFamily = $fontSettings['fontFamily'] ?? 'Arial';
        $fontSize = (int)($fontSettings['fontSize'] ?? 10);
        $fontStyle = (string)($fontSettings['fontStyle'] ?? '');
        
        $pdf->SetFont($fontFamily, $fontStyle, $fontSize);
        
        // Apply font color if specified
        if (isset($fontSettings['fontColor']) && is_array($fontSettings['fontColor'])) {
            $color = $fontSettings['fontColor'];
            $pdf->SetTextColor(
                (int)($color[0] ?? 0),
                (int)($color[1] ?? 0),
                (int)($color[2] ?? 0)
            );
        }
    }
    
    /**
     * Get default font for a field type
     */
    public static function getDefaultFontForType(string $fieldType): array {
        $config = self::getConfig();
        
        if (isset($config['fieldTypes'][$fieldType])) {
            return $config['fieldTypes'][$fieldType];
        }
        
        return $config['defaults'] ?? [
            'fontFamily' => 'Arial',
            'fontSize' => 10,
            'fontStyle' => '',
        ];
    }
    
    /**
     * Get template font override
     */
    public static function getTemplateFont(?string $templateId): ?array {
        if (!$templateId) {
            return null;
        }
        
        $config = self::getConfig();
        return $config['templates'][$templateId] ?? null;
    }
    
    /**
     * Get all available fonts
     */
    public static function getAvailableFonts(): array {
        $config = self::getConfig();
        return $config['availableFonts'] ?? ['Arial', 'Helvetica', 'Times', 'Courier', 'Symbol', 'ZapfDingbats'];
    }
    
    /**
     * Get font presets
     */
    public static function getPresets(): array {
        $config = self::getConfig();
        return $config['presets'] ?? [];
    }
    
    /**
     * Apply preset to field position
     */
    public static function applyPreset(string $presetName, array &$fieldPosition): void {
        $config = self::getConfig();
        
        if (!isset($config['presets'][$presetName])) {
            return;
        }
        
        $preset = $config['presets'][$presetName];
        $fieldPosition['fontFamily'] = $preset['fontFamily'] ?? $fieldPosition['fontFamily'] ?? 'Arial';
        $fieldPosition['fontSize'] = $preset['fontSize'] ?? $fieldPosition['fontSize'] ?? 10;
        $fieldPosition['fontStyle'] = $preset['fontStyle'] ?? $fieldPosition['fontStyle'] ?? '';
    }
    
    /**
     * Update global defaults
     */
    public static function updateDefaults(array $defaults): bool {
        $configFile = __DIR__ . '/../../config/fonts.php';
        $config = self::getConfig();
        
        $config['defaults'] = array_merge($config['defaults'], $defaults);
        
        // Write updated config
        $content = "<?php\n/**\n * Universal Font Configuration\n * Auto-generated - do not edit manually\n */\n\nreturn " . var_export($config, true) . ";\n";
        
        return file_put_contents($configFile, $content) !== false;
    }
    
    /**
     * Update template font override
     */
    public static function updateTemplateFont(string $templateId, array $fontSettings): bool {
        $configFile = __DIR__ . '/../../config/fonts.php';
        $config = self::getConfig();
        
        if (!isset($config['templates'])) {
            $config['templates'] = [];
        }
        
        $config['templates'][$templateId] = array_merge(
            $config['templates'][$templateId] ?? [],
            $fontSettings
        );
        
        // Write updated config
        $content = "<?php\n/**\n * Universal Font Configuration\n * Auto-generated - do not edit manually\n */\n\nreturn " . var_export($config, true) . ";\n";
        
        return file_put_contents($configFile, $content) !== false;
    }
    
    /**
     * Update field type font
     */
    public static function updateFieldTypeFont(string $fieldType, array $fontSettings): bool {
        $configFile = __DIR__ . '/../../config/fonts.php';
        $config = self::getConfig();
        
        if (!isset($config['fieldTypes'])) {
            $config['fieldTypes'] = [];
        }
        
        $config['fieldTypes'][$fieldType] = array_merge(
            $config['fieldTypes'][$fieldType] ?? [],
            $fontSettings
        );
        
        // Write updated config
        $content = "<?php\n/**\n * Universal Font Configuration\n * Auto-generated - do not edit manually\n */\n\nreturn " . var_export($config, true) . ";\n";
        
        return file_put_contents($configFile, $content) !== false;
    }
    
    /**
     * Infer field type from field name
     */
    public static function inferFieldType(string $fieldName): ?string {
        $fieldNameLower = strtolower($fieldName);
        
        // Common patterns
        if (preg_match('/(name|firstname|lastname|fullname)$/i', $fieldName)) {
            return 'name';
        }
        if (preg_match('/(address|street|city|state|zip)$/i', $fieldName)) {
            return 'address';
        }
        if (preg_match('/(phone|telephone|mobile)$/i', $fieldName)) {
            return 'phone';
        }
        if (preg_match('/(email|e-mail)$/i', $fieldName)) {
            return 'email';
        }
        if (preg_match('/(date|dob|birthdate)$/i', $fieldName)) {
            return 'date';
        }
        if (preg_match('/(number|num|id|ssn)$/i', $fieldName)) {
            return 'number';
        }
        if (preg_match('/(signature|sign)$/i', $fieldName)) {
            return 'signature';
        }
        if (preg_match('/(checkbox|check|yes|no)$/i', $fieldName)) {
            return 'checkbox';
        }
        
        return 'text'; // Default
    }
}

