<?php
/**
 * FL-100 Field Position Analyzer
 * Analyzes current positioning and identifies issues
 */

namespace WebPdfTimeSaver\Mvp\PositionAnalysis;

class Fl100PositionAnalyzer {
    private $positionsFile;
    private $positions;
    
    public function __construct($positionsFile = null) {
        $this->positionsFile = $positionsFile ?: __DIR__ . '/../../data/t_fl100_gc120_positions.json';
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
     * Analyze current positioning issues
     */
    public function analyzeIssues() {
        $issues = [];
        
        foreach ($this->positions as $fieldName => $position) {
            $issue = $this->analyzeFieldPosition($fieldName, $position);
            if ($issue) {
                $issues[$fieldName] = $issue;
            }
        }
        
        return $issues;
    }
    
    private function analyzeFieldPosition($fieldName, $position) {
        $issues = [];
        
        // Check for common positioning problems
        if ($position['x'] < 0 || $position['y'] < 0) {
            $issues[] = "Negative coordinates";
        }
        
        if ($position['x'] > 200 || $position['y'] > 280) {
            $issues[] = "Coordinates outside page bounds";
        }
        
        if ($position['fontSize'] < 6 || $position['fontSize'] > 12) {
            $issues[] = "Font size may be inappropriate";
        }
        
        // Check for overlapping fields
        $overlaps = $this->checkOverlaps($fieldName, $position);
        if ($overlaps) {
            $issues[] = "Overlaps with: " . implode(', ', $overlaps);
        }
        
        return empty($issues) ? null : $issues;
    }
    
    private function checkOverlaps($fieldName, $position) {
        $overlaps = [];
        
        foreach ($this->positions as $otherField => $otherPos) {
            if ($otherField === $fieldName) continue;
            
            if ($this->rectanglesOverlap($position, $otherPos)) {
                $overlaps[] = $otherField;
            }
        }
        
        return $overlaps;
    }
    
    private function rectanglesOverlap($rect1, $rect2) {
        return !($rect1['x'] + $rect1['width'] < $rect2['x'] ||
                $rect2['x'] + $rect2['width'] < $rect1['x'] ||
                $rect1['y'] + $rect1['height'] < $rect2['y'] ||
                $rect2['y'] + $rect2['height'] < $rect1['y']);
    }
    
    /**
     * Generate positioning report
     */
    public function generateReport() {
        $issues = $this->analyzeIssues();
        
        $report = [
            'total_fields' => count($this->positions),
            'fields_with_issues' => count($issues),
            'issues_by_type' => $this->categorizeIssues($issues),
            'detailed_issues' => $issues,
            'recommendations' => $this->generateRecommendations($issues)
        ];
        
        return $report;
    }
    
    private function categorizeIssues($issues) {
        $categories = [
            'coordinate_issues' => 0,
            'overlap_issues' => 0,
            'font_size_issues' => 0
        ];
        
        foreach ($issues as $fieldIssues) {
            foreach ($fieldIssues as $issue) {
                if (strpos($issue, 'coordinates') !== false) {
                    $categories['coordinate_issues']++;
                } elseif (strpos($issue, 'Overlaps') !== false) {
                    $categories['overlap_issues']++;
                } elseif (strpos($issue, 'Font size') !== false) {
                    $categories['font_size_issues']++;
                }
            }
        }
        
        return $categories;
    }
    
    private function generateRecommendations($issues) {
        $recommendations = [];
        
        if (!empty($issues)) {
            $recommendations[] = "Run position adjustment tool to fix coordinate issues";
            $recommendations[] = "Review overlapping fields and adjust spacing";
            $recommendations[] = "Test with actual form data to verify positioning";
        } else {
            $recommendations[] = "Positions look good - ready for testing";
        }
        
        return $recommendations;
    }
}
