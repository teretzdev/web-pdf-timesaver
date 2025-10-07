<?php

namespace WebPdfTimeSaver\Mvp;

class FieldPositionLoader
{
    private $dataDir;
    
    public function __construct($dataDir = null)
    {
        $this->dataDir = $dataDir ?: __DIR__ . '/../data';
    }
    
    /**
     * Load field positions for a template
     */
    public function loadFieldPositions($template)
    {
        $positionsFile = $this->dataDir . '/' . $template . '_positions.json';
        
        if (!file_exists($positionsFile)) {
            return [];
        }
        
        $content = file_get_contents($positionsFile);
        $positions = json_decode($content, true);
        
        return $positions ?: [];
    }
    
    /**
     * Save field positions for a template
     */
    public function saveFieldPositions($template, $positions)
    {
        $positionsFile = $this->dataDir . '/' . $template . '_positions.json';
        
        // Ensure directory exists
        $dir = dirname($positionsFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        $json = json_encode($positions, JSON_PRETTY_PRINT);
        return file_put_contents($positionsFile, $json) !== false;
    }
    
    /**
     * Get position for a specific field
     */
    public function getFieldPosition($template, $fieldKey)
    {
        $positions = $this->loadFieldPositions($template);
        return $positions[$fieldKey] ?? null;
    }
    
    /**
     * Set position for a specific field
     */
    public function setFieldPosition($template, $fieldKey, $x, $y, $width = null, $height = null)
    {
        $positions = $this->loadFieldPositions($template);
        $positions[$fieldKey] = [
            'x' => $x,
            'y' => $y,
            'width' => $width,
            'height' => $height
        ];
        
        return $this->saveFieldPositions($template, $positions);
    }
    
    /**
     * Check if positions exist for a template
     */
    public function hasPositions($template)
    {
        $positionsFile = $this->dataDir . '/' . $template . '_positions.json';
        return file_exists($positionsFile);
    }
    
    /**
     * Get all templates with saved positions
     */
    public function getTemplatesWithPositions()
    {
        $templates = [];
        $files = glob($this->dataDir . '/*_positions.json');
        
        foreach ($files as $file) {
            $filename = basename($file);
            $template = str_replace('_positions.json', '', $filename);
            $templates[] = $template;
        }
        
        return $templates;
    }
}

