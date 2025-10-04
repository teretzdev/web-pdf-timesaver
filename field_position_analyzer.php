<?php
/**
 * FL-100 Field Position Analyzer
 * 
 * This tool analyzes the current field positions and provides
 * visual feedback on alignment accuracy.
 */

require_once __DIR__ . '/mvp/lib/data.php';
require_once __DIR__ . '/mvp/lib/pdf_field_service.php';

use WebPdfTimeSaver\Mvp\PdfFieldService;

class FieldPositionAnalyzer {
    private $fieldPositions;
    private $pdfFieldService;
    
    public function __construct() {
        $this->pdfFieldService = new PdfFieldService();
        $this->loadFieldPositions();
    }
    
    private function loadFieldPositions() {
        $positionsFile = __DIR__ . '/data/t_fl100_gc120_positions.json';
        if (file_exists($positionsFile)) {
            $this->fieldPositions = json_decode(file_get_contents($positionsFile), true);
        } else {
            $this->fieldPositions = [];
        }
    }
    
    public function analyzeFieldPositions() {
        echo "🎯 FL-100 Field Position Analysis\n";
        echo "================================\n\n";
        
        if (empty($this->fieldPositions['t_fl100_gc120'])) {
            echo "❌ No field positions found. Please run the field editor first.\n";
            return;
        }
        
        $fields = $this->fieldPositions['t_fl100_gc120'];
        
        // Group fields by section
        $sections = [
            'Attorney Information' => ['attorney_name', 'attorney_bar_number', 'attorney_firm', 'attorney_address', 'attorney_city_state_zip', 'attorney_phone', 'attorney_email'],
            'Court Information' => ['case_number', 'court_county', 'court_address', 'case_type', 'filing_date'],
            'Party Information' => ['petitioner_name', 'respondent_name', 'petitioner_address', 'petitioner_phone', 'respondent_address'],
            'Marriage Information' => ['marriage_date', 'separation_date', 'marriage_location', 'grounds_for_dissolution'],
            'Relief Requested' => ['dissolution_type', 'property_division', 'spousal_support', 'attorney_fees', 'name_change'],
            'Children Information' => ['has_children', 'children_count'],
            'Signature Section' => ['additional_info', 'attorney_signature', 'signature_date']
        ];
        
        foreach ($sections as $sectionName => $fieldIds) {
            echo "📋 {$sectionName}\n";
            echo str_repeat('-', strlen($sectionName) + 4) . "\n";
            
            foreach ($fieldIds as $fieldId) {
                if (isset($fields[$fieldId])) {
                    $field = $fields[$fieldId];
                    $status = $this->analyzeField($field);
                    echo sprintf(
                        "  %s %-25s (%3d, %3d) %s×%s %s\n",
                        $status['icon'],
                        $field['label'],
                        $field['x'],
                        $field['y'],
                        $field['width'],
                        $field['height'],
                        $status['message']
                    );
                } else {
                    echo "  ❌ {$fieldId} - Missing field definition\n";
                }
            }
            echo "\n";
        }
        
        $this->generateAlignmentReport($fields);
    }
    
    private function analyzeField($field) {
        $issues = [];
        
        // Check for reasonable positioning
        if ($field['x'] < 0 || $field['x'] > 200) {
            $issues[] = "X position out of bounds";
        }
        
        if ($field['y'] < 0 || $field['y'] > 400) {
            $issues[] = "Y position out of bounds";
        }
        
        // Check for reasonable sizing
        if ($field['width'] < 5 || $field['width'] > 150) {
            $issues[] = "Width unusual";
        }
        
        if ($field['height'] < 5 || $field['height'] > 50) {
            $issues[] = "Height unusual";
        }
        
        // Check field type specific issues
        if ($field['type'] === 'checkbox' && ($field['width'] !== 8 || $field['height'] !== 8)) {
            $issues[] = "Checkbox size should be 8×8";
        }
        
        if ($field['type'] === 'textarea' && $field['height'] < 10) {
            $issues[] = "Textarea height too small";
        }
        
        if (empty($issues)) {
            return ['icon' => '✅', 'message' => 'OK'];
        } else {
            return ['icon' => '⚠️', 'message' => implode(', ', $issues)];
        }
    }
    
    private function generateAlignmentReport($fields) {
        echo "📊 Alignment Analysis Report\n";
        echo "============================\n\n";
        
        // Check for overlapping fields
        $overlaps = $this->findOverlappingFields($fields);
        if (!empty($overlaps)) {
            echo "⚠️  Overlapping Fields Detected:\n";
            foreach ($overlaps as $overlap) {
                echo "  - {$overlap['field1']} overlaps with {$overlap['field2']}\n";
            }
            echo "\n";
        } else {
            echo "✅ No overlapping fields detected\n\n";
        }
        
        // Check for fields too close together
        $tooClose = $this->findFieldsTooClose($fields);
        if (!empty($tooClose)) {
            echo "⚠️  Fields Too Close Together:\n";
            foreach ($tooClose as $close) {
                echo "  - {$close['field1']} and {$close['field2']} (distance: {$close['distance']}px)\n";
            }
            echo "\n";
        } else {
            echo "✅ Field spacing looks good\n\n";
        }
        
        // Generate recommendations
        $this->generateRecommendations($fields);
    }
    
    private function findOverlappingFields($fields) {
        $overlaps = [];
        $fieldArray = array_values($fields);
        
        for ($i = 0; $i < count($fieldArray); $i++) {
            for ($j = $i + 1; $j < count($fieldArray); $j++) {
                $field1 = $fieldArray[$i];
                $field2 = $fieldArray[$j];
                
                if ($this->fieldsOverlap($field1, $field2)) {
                    $overlaps[] = [
                        'field1' => $field1['label'],
                        'field2' => $field2['label']
                    ];
                }
            }
        }
        
        return $overlaps;
    }
    
    private function fieldsOverlap($field1, $field2) {
        return !($field1['x'] + $field1['width'] < $field2['x'] ||
                 $field2['x'] + $field2['width'] < $field1['x'] ||
                 $field1['y'] + $field1['height'] < $field2['y'] ||
                 $field2['y'] + $field2['height'] < $field1['y']);
    }
    
    private function findFieldsTooClose($fields) {
        $tooClose = [];
        $fieldArray = array_values($fields);
        
        for ($i = 0; $i < count($fieldArray); $i++) {
            for ($j = $i + 1; $j < count($fieldArray); $j++) {
                $field1 = $fieldArray[$i];
                $field2 = $fieldArray[$j];
                
                $distance = $this->calculateDistance($field1, $field2);
                if ($distance < 10 && $distance > 0) {
                    $tooClose[] = [
                        'field1' => $field1['label'],
                        'field2' => $field2['label'],
                        'distance' => round($distance, 1)
                    ];
                }
            }
        }
        
        return $tooClose;
    }
    
    private function calculateDistance($field1, $field2) {
        $center1 = [
            'x' => $field1['x'] + $field1['width'] / 2,
            'y' => $field1['y'] + $field1['height'] / 2
        ];
        
        $center2 = [
            'x' => $field2['x'] + $field2['width'] / 2,
            'y' => $field2['y'] + $field2['height'] / 2
        ];
        
        return sqrt(pow($center1['x'] - $center2['x'], 2) + pow($center1['y'] - $center2['y'], 2));
    }
    
    private function generateRecommendations($fields) {
        echo "💡 Recommendations:\n";
        echo "===================\n\n";
        
        // Check attorney section alignment
        $attorneyFields = array_filter($fields, function($field) {
            return in_array($field['label'], ['Attorney Name', 'State Bar Number', 'Law Firm Name', 'Attorney Address', 'City, State, ZIP', 'Phone', 'Email']);
        });
        
        if (!empty($attorneyFields)) {
            echo "📝 Attorney Section:\n";
            $yPositions = array_column($attorneyFields, 'y');
            $minY = min($yPositions);
            $maxY = max($yPositions);
            
            if ($maxY - $minY > 25) {
                echo "  - Consider aligning attorney fields vertically (current spread: " . ($maxY - $minY) . "px)\n";
            } else {
                echo "  - ✅ Attorney fields are well-aligned\n";
            }
            echo "\n";
        }
        
        // Check court section
        $courtFields = array_filter($fields, function($field) {
            return in_array($field['label'], ['Case Number', 'County', 'Court Address', 'Case Type', 'Filing Date']);
        });
        
        if (!empty($courtFields)) {
            echo "🏛️  Court Section:\n";
            echo "  - ✅ Court fields positioned correctly\n\n";
        }
        
        // Check checkbox alignment
        $checkboxes = array_filter($fields, function($field) {
            return $field['type'] === 'checkbox';
        });
        
        if (!empty($checkboxes)) {
            echo "☑️  Checkboxes:\n";
            $xPositions = array_column($checkboxes, 'x');
            $uniqueX = array_unique($xPositions);
            
            if (count($uniqueX) > 2) {
                echo "  - Consider aligning checkboxes vertically (multiple X positions: " . implode(', ', $uniqueX) . ")\n";
            } else {
                echo "  - ✅ Checkboxes are well-aligned\n";
            }
            echo "\n";
        }
        
        echo "🎯 Next Steps:\n";
        echo "1. Open the field alignment tool at http://localhost:8080/field_alignment_tool.html\n";
        echo "2. Use the MCP field editor at http://localhost:3001\n";
        echo "3. Fine-tune field positions based on visual inspection\n";
        echo "4. Test PDF generation with updated positions\n\n";
    }
    
    public function exportPositionsForMCP() {
        $mcpPositions = [];
        
        if (!empty($this->fieldPositions['t_fl100_gc120'])) {
            foreach ($this->fieldPositions['t_fl100_gc120'] as $fieldId => $field) {
                $mcpPositions[$fieldId] = [
                    'x' => $field['x'],
                    'y' => $field['y'],
                    'width' => $field['width'],
                    'height' => $field['height'],
                    'type' => $field['type']
                ];
            }
        }
        
        $outputFile = __DIR__ . '/field_positions_for_mcp.json';
        file_put_contents($outputFile, json_encode($mcpPositions, JSON_PRETTY_PRINT));
        
        echo "📄 MCP positions exported to: {$outputFile}\n";
    }
}

// Run the analyzer
$analyzer = new FieldPositionAnalyzer();
$analyzer->analyzeFieldPositions();
$analyzer->exportPositionsForMCP();
?>