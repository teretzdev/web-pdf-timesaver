<?php
/**
 * FL-100 Field Position Adjuster
 * Interactive tool to adjust field positions
 */

namespace WebPdfTimeSaver\Mvp\PositionAnalysis;

class Fl100PositionAdjuster {
    private $positionsFile;
    private $positions;
    private $backupDir;
    
    public function __construct($positionsFile = null) {
        $this->positionsFile = $positionsFile ?: __DIR__ . '/../../data/t_fl100_gc120_positions.json';
        $this->backupDir = __DIR__ . '/../../data/backups';
        $this->loadPositions();
    }
    
    private function loadPositions() {
        if (!file_exists($this->positionsFile)) {
            throw new \Exception("Positions file not found: " . $this->positionsFile);
        }
        
        $content = file_get_contents($this->positionsFile);
        $this->positions = json_decode($content, true);
        
        if (!$this->positions) {
            throw new \Exception("Invalid positions file format");
        }
    }
    
    /**
     * Create backup before making changes
     */
    public function createBackup() {
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
        
        $backupFile = $this->backupDir . '/t_fl100_gc120_positions_' . date('Y-m-d_H-i-s') . '.json';
        copy($this->positionsFile, $backupFile);
        
        return $backupFile;
    }
    
    /**
     * Adjust field position
     */
    public function adjustField($fieldName, $adjustments) {
        if (!isset($this->positions[$fieldName])) {
            throw new \Exception("Field not found: $fieldName");
        }
        
        $position = &$this->positions[$fieldName];
        
        if (isset($adjustments['x'])) {
            $position['x'] = $adjustments['x'];
        }
        
        if (isset($adjustments['y'])) {
            $position['y'] = $adjustments['y'];
        }
        
        if (isset($adjustments['width'])) {
            $position['width'] = $adjustments['width'];
        }
        
        if (isset($adjustments['height'])) {
            $position['height'] = $adjustments['height'];
        }
        
        if (isset($adjustments['fontSize'])) {
            $position['fontSize'] = $adjustments['fontSize'];
        }
        
        return $position;
    }
    
    /**
     * Bulk adjust multiple fields
     */
    public function bulkAdjust($adjustments) {
        $results = [];
        
        foreach ($adjustments as $fieldName => $fieldAdjustments) {
            try {
                $results[$fieldName] = $this->adjustField($fieldName, $fieldAdjustments);
            } catch (\Exception $e) {
                $results[$fieldName] = ['error' => $e->getMessage()];
            }
        }
        
        return $results;
    }
    
    /**
     * Auto-adjust based on common issues
     */
    public function autoAdjust() {
        $adjustments = [];
        
        foreach ($this->positions as $fieldName => $position) {
            $fieldAdjustments = [];
            
            // Fix negative coordinates
            if ($position['x'] < 0) {
                $fieldAdjustments['x'] = 0;
            }
            if ($position['y'] < 0) {
                $fieldAdjustments['y'] = 0;
            }
            
            // Fix coordinates outside page bounds
            if ($position['x'] > 200) {
                $fieldAdjustments['x'] = 200 - $position['width'];
            }
            if ($position['y'] > 280) {
                $fieldAdjustments['y'] = 280 - $position['height'];
            }
            
            // Fix font size
            if ($position['fontSize'] < 6) {
                $fieldAdjustments['fontSize'] = 8;
            } elseif ($position['fontSize'] > 12) {
                $fieldAdjustments['fontSize'] = 10;
            }
            
            if (!empty($fieldAdjustments)) {
                $adjustments[$fieldName] = $fieldAdjustments;
            }
        }
        
        if (!empty($adjustments)) {
            return $this->bulkAdjust($adjustments);
        }
        
        return [];
    }
    
    /**
     * Save adjusted positions
     */
    public function savePositions() {
        $json = json_encode($this->positions, JSON_PRETTY_PRINT);
        return file_put_contents($this->positionsFile, $json) !== false;
    }
    
    /**
     * Get current positions
     */
    public function getPositions() {
        return $this->positions;
    }
    
    /**
     * Get specific field position
     */
    public function getFieldPosition($fieldName) {
        return $this->positions[$fieldName] ?? null;
    }
}
