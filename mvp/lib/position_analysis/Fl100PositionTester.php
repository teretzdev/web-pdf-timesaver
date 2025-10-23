<?php
/**
 * FL-100 Position Testing Tool
 * Test field positions with visual feedback
 */

require_once __DIR__ . '/Fl100PositionAnalyzer.php';
require_once __DIR__ . '/Fl100PositionAdjuster.php';

use WebPdfTimeSaver\Mvp\PositionAnalysis\Fl100PositionAnalyzer;
use WebPdfTimeSaver\Mvp\PositionAnalysis\Fl100PositionAdjuster;

class Fl100PositionTester {
    private $analyzer;
    private $adjuster;
    
    public function __construct() {
        $this->analyzer = new Fl100PositionAnalyzer();
        $this->adjuster = new Fl100PositionAdjuster();
    }
    
    /**
     * Run comprehensive position analysis
     */
    public function runAnalysis() {
        echo "🔍 FL-100 Position Analysis\n";
        echo "==========================\n\n";
        
        $report = $this->analyzer->generateReport();
        
        echo "📊 Summary:\n";
        echo "   Total fields: " . $report['total_fields'] . "\n";
        echo "   Fields with issues: " . $report['fields_with_issues'] . "\n";
        echo "   Issues by type:\n";
        foreach ($report['issues_by_type'] as $type => $count) {
            echo "     • " . ucfirst(str_replace('_', ' ', $type)) . ": $count\n";
        }
        
        echo "\n📋 Detailed Issues:\n";
        foreach ($report['detailed_issues'] as $field => $issues) {
            echo "   $field:\n";
            foreach ($issues as $issue) {
                echo "     - $issue\n";
            }
        }
        
        echo "\n💡 Recommendations:\n";
        foreach ($report['recommendations'] as $recommendation) {
            echo "   • $recommendation\n";
        }
        
        return $report;
    }
    
    /**
     * Test specific field positioning
     */
    public function testFieldPosition($fieldName) {
        $position = $this->adjuster->getFieldPosition($fieldName);
        
        if (!$position) {
            echo "❌ Field not found: $fieldName\n";
            return false;
        }
        
        echo "🎯 Testing field: $fieldName\n";
        echo "   Position: ({$position['x']}, {$position['y']})\n";
        echo "   Size: {$position['width']} x {$position['height']}\n";
        echo "   Font size: {$position['fontSize']}\n";
        echo "   Type: {$position['type']}\n";
        echo "   Page: {$position['page']}\n";
        
        // Check for issues
        $issues = [];
        if ($position['x'] < 0 || $position['y'] < 0) {
            $issues[] = "Negative coordinates";
        }
        if ($position['x'] > 200 || $position['y'] > 280) {
            $issues[] = "Outside page bounds";
        }
        
        if (empty($issues)) {
            echo "   ✅ Position looks good\n";
        } else {
            echo "   ⚠️ Issues: " . implode(', ', $issues) . "\n";
        }
        
        return $position;
    }
    
    /**
     * Generate test PDF with position markers
     */
    public function generateTestPdf() {
        echo "📄 Generating test PDF with position markers...\n";
        
        // This would generate a PDF with visual markers showing field positions
        // For now, just return the positions for manual review
        $positions = $this->adjuster->getPositions();
        
        echo "   Fields to review:\n";
        foreach ($positions as $field => $pos) {
            echo "   • $field: ({$pos['x']}, {$pos['y']}) - {$pos['type']}\n";
        }
        
        return $positions;
    }
}

// CLI usage
if (php_sapi_name() === 'cli') {
    $tester = new Fl100PositionTester();
    
    if (isset($argv[1])) {
        switch ($argv[1]) {
            case 'analyze':
                $tester->runAnalysis();
                break;
            case 'test':
                $fieldName = $argv[2] ?? 'attorney_name';
                $tester->testFieldPosition($fieldName);
                break;
            case 'pdf':
                $tester->generateTestPdf();
                break;
            default:
                echo "Usage: php Fl100PositionTester.php [analyze|test|pdf]\n";
        }
    } else {
        $tester->runAnalysis();
    }
}
