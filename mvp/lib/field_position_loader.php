<?php

namespace WebPdfTimeSaver\Mvp;

class FieldPositionLoader
{
    private $dataDir;
    
    public function __construct($dataDir = null)
    {
        if ($dataDir) {
            $this->dataDir = $dataDir;
        } else {
            // Use realpath to resolve the actual directory
            $dataDirPath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'data';
            $resolved = realpath($dataDirPath);
            $this->dataDir = $resolved ?: $dataDirPath;
        }
    }
    
    /**
     * Load field positions for a template
     */
    public function loadFieldPositions($template)
    {
        // Try multiple paths to find the positions file
        $pathsToTry = [
            // Resolved dataDir from constructor
            $this->dataDir . DIRECTORY_SEPARATOR . $template . '_positions.json',
            // Direct path from __DIR__
            __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . $template . '_positions.json',
            // Absolute path from current working directory
            dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . $template . '_positions.json',
            // Try using getcwd() if available
            (function_exists('getcwd') ? getcwd() : '') . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . $template . '_positions.json',
            // Try relative to document root
            ($_SERVER['DOCUMENT_ROOT'] ?? '') . DIRECTORY_SEPARATOR . 'Web-PDFTimeSaver' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . $template . '_positions.json',
            // Try absolute path - Windows user directory
            'C:' . DIRECTORY_SEPARATOR . 'Users' . DIRECTORY_SEPARATOR . 'Shadow' . DIRECTORY_SEPARATOR . 'Web-PDFTimeSaver' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . $template . '_positions.json'
        ];
        
        $positionsFile = null;
        foreach ($pathsToTry as $idx => $path) {
            // Skip empty paths
            if (empty($path)) {
                continue;
            }
            
            // Normalize path separators
            $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
            $resolved = realpath($path);
            if ($resolved && file_exists($resolved)) {
                $positionsFile = $resolved;
                error_log("FieldPositionLoader: Found positions file at path #$idx: $positionsFile");
                break;
            }
        }
        
        if (!$positionsFile) {
            error_log("FieldPositionLoader: Positions file NOT FOUND for template: $template");
            $validPaths = array_filter($pathsToTry, function($p) { return !empty($p); });
            error_log("FieldPositionLoader: Tried " . count($validPaths) . " paths");
            // Log all paths for debugging
            foreach ($validPaths as $idx => $p) {
                $resolved = realpath($p);
                $exists = $resolved && file_exists($resolved) ? 'EXISTS' : 'NOT FOUND';
                error_log("FieldPositionLoader:   Path #$idx ($exists): $p");
            }
            return [];
        }
        
        error_log("FieldPositionLoader: Loading positions for template: $template from: $positionsFile");
        $content = file_get_contents($positionsFile);
        $positions = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("FieldPositionLoader: JSON decode error: " . json_last_error_msg());
            return [];
        }
        
        // Load missing fields if available (from OCR/text detection)
        $missingFieldsPath = dirname($positionsFile) . DIRECTORY_SEPARATOR . $template . '_missing_fields.json';
        if (file_exists($missingFieldsPath)) {
            $missingFieldsContent = file_get_contents($missingFieldsPath);
            $missingFields = json_decode($missingFieldsContent, true);
            if (is_array($missingFields) && !empty($missingFields)) {
                // Merge missing fields into positions
                $positions = array_merge($positions ?: [], $missingFields);
                error_log("FieldPositionLoader: Merged " . count($missingFields) . " missing fields for template: $template");
            }
        }
        
        $count = is_array($positions) ? count($positions) : 0;
        error_log("FieldPositionLoader: Loaded $count positions for template: $template");
        
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

