<?php
declare(strict_types=1);

namespace WebPdfTimeSaver\Mvp;

/**
 * PDF Config System
 * Stores PDF-specific configurations and user customizations
 */
final class PdfConfig {
    private string $dataDir;
    
    public function __construct(?string $dataDir = null) {
        $this->dataDir = $dataDir ?: __DIR__ . '/../../data';
        
        // Ensure data directory exists
        if (!is_dir($this->dataDir)) {
            mkdir($this->dataDir, 0755, true);
        }
    }
    
    /**
     * Get configuration for a template
     */
    public function getConfig(string $templateId): array {
        $configFile = $this->dataDir . '/' . $templateId . '_config.json';
        
        if (file_exists($configFile)) {
            $config = json_decode(file_get_contents($configFile), true);
            return $config ?? [];
        }
        
        // Return default configuration
        return $this->getDefaultConfig();
    }
    
    /**
     * Save configuration for a template
     */
    public function saveConfig(string $templateId, array $config): void {
        $configFile = $this->dataDir . '/' . $templateId . '_config.json';
        
        // Merge with existing config
        $existingConfig = $this->getConfig($templateId);
        $mergedConfig = array_merge($existingConfig, $config);
        
        file_put_contents($configFile, json_encode($mergedConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
    
    /**
     * Get default configuration
     */
    private function getDefaultConfig(): array {
        return [
            'fieldMapping' => [],
            'fieldCategories' => [],
            'fieldTypes' => [],
            'validationRules' => [],
            'customLabels' => [],
            'customPlaceholders' => [],
            'fieldOrder' => [],
            'panelOrder' => [],
            'extractionSettings' => [
                'useAcroForm' => true,
                'useXfa' => true,
                'useAnnotations' => false,
                'useOcr' => false
            ],
            'fillingSettings' => [
                'strategy' => 'auto', // 'auto', 'acroform', 'textoverlay', 'both'
                'fontFamily' => 'Arial',
                'fontSize' => 9,
                'fontStyle' => '',
                'textColor' => [0, 0, 0]
            ]
        ];
    }
    
    /**
     * Get field mapping for a template
     */
    public function getFieldMapping(string $templateId): array {
        $config = $this->getConfig($templateId);
        return $config['fieldMapping'] ?? [];
    }
    
    /**
     * Save field mapping for a template
     */
    public function saveFieldMapping(string $templateId, array $mapping): void {
        $config = $this->getConfig($templateId);
        $config['fieldMapping'] = $mapping;
        $this->saveConfig($templateId, $config);
    }
    
    /**
     * Get field categories for a template
     */
    public function getFieldCategories(string $templateId): array {
        $config = $this->getConfig($templateId);
        return $config['fieldCategories'] ?? [];
    }
    
    /**
     * Save field categories for a template
     */
    public function saveFieldCategories(string $templateId, array $categories): void {
        $config = $this->getConfig($templateId);
        $config['fieldCategories'] = $categories;
        $this->saveConfig($templateId, $config);
    }
    
    /**
     * Get custom label for a field
     */
    public function getCustomLabel(string $templateId, string $fieldName): ?string {
        $config = $this->getConfig($templateId);
        return $config['customLabels'][$fieldName] ?? null;
    }
    
    /**
     * Save custom label for a field
     */
    public function saveCustomLabel(string $templateId, string $fieldName, string $label): void {
        $config = $this->getConfig($templateId);
        if (!isset($config['customLabels'])) {
            $config['customLabels'] = [];
        }
        $config['customLabels'][$fieldName] = $label;
        $this->saveConfig($templateId, $config);
    }
    
    /**
     * Get custom placeholder for a field
     */
    public function getCustomPlaceholder(string $templateId, string $fieldName): ?string {
        $config = $this->getConfig($templateId);
        return $config['customPlaceholders'][$fieldName] ?? null;
    }
    
    /**
     * Save custom placeholder for a field
     */
    public function saveCustomPlaceholder(string $templateId, string $fieldName, string $placeholder): void {
        $config = $this->getConfig($templateId);
        if (!isset($config['customPlaceholders'])) {
            $config['customPlaceholders'] = [];
        }
        $config['customPlaceholders'][$fieldName] = $placeholder;
        $this->saveConfig($templateId, $config);
    }
    
    /**
     * Get extraction settings for a template
     */
    public function getExtractionSettings(string $templateId): array {
        $config = $this->getConfig($templateId);
        return $config['extractionSettings'] ?? $this->getDefaultConfig()['extractionSettings'];
    }
    
    /**
     * Save extraction settings for a template
     */
    public function saveExtractionSettings(string $templateId, array $settings): void {
        $config = $this->getConfig($templateId);
        $config['extractionSettings'] = array_merge($this->getDefaultConfig()['extractionSettings'], $settings);
        $this->saveConfig($templateId, $config);
    }
    
    /**
     * Get filling settings for a template
     */
    public function getFillingSettings(string $templateId): array {
        $config = $this->getConfig($templateId);
        return $config['fillingSettings'] ?? $this->getDefaultConfig()['fillingSettings'];
    }
    
    /**
     * Save filling settings for a template
     */
    public function saveFillingSettings(string $templateId, array $settings): void {
        $config = $this->getConfig($templateId);
        $config['fillingSettings'] = array_merge($this->getDefaultConfig()['fillingSettings'], $settings);
        $this->saveConfig($templateId, $config);
    }
    
    /**
     * Delete configuration for a template
     */
    public function deleteConfig(string $templateId): void {
        $configFile = $this->dataDir . '/' . $templateId . '_config.json';
        
        if (file_exists($configFile)) {
            unlink($configFile);
        }
    }
    
    /**
     * Get all template configurations
     */
    public function getAllConfigs(): array {
        $configs = [];
        $files = glob($this->dataDir . '/*_config.json');
        
        foreach ($files as $file) {
            $templateId = basename($file, '_config.json');
            $configs[$templateId] = $this->getConfig($templateId);
        }
        
        return $configs;
    }
}

