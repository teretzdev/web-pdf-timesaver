<?php
/**
 * Master Verification Report Generator
 * Generates comprehensive report for all templates
 */

declare(strict_types=1);

namespace WebPdfTimeSaver\Mvp;

class MasterVerificationReport {
    
    private $dataDir;
    private $outputDir;
    
    public function __construct() {
        $this->dataDir = __DIR__ . '/../../data';
        $this->outputDir = __DIR__ . '/../../output/verification';
        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }
    }
    
    /**
     * Generate master report for all templates
     */
    public function generateMasterReport(): array {
        $templates = $this->findAllTemplates();
        $results = [
            'generated_at' => date('Y-m-d H:i:s'),
            'total_templates' => count($templates),
            'templates' => [],
            'summary' => [
                'total' => 0,
                'passed' => 0,
                'failed' => 0,
                'by_status' => []
            ]
        ];
        
        if (!class_exists('WebPdfTimeSaver\Mvp\AutomatedVerificationPipeline')) {
            require_once __DIR__ . '/field_position_loader.php';
            require_once __DIR__ . '/fl100_test_data_generator.php';
            require_once __DIR__ . '/field_name_mapper.php';
            require_once __DIR__ . '/pdf_form_filler.php';
            require_once __DIR__ . '/position_debug_generator.php';
            require_once __DIR__ . '/automated_verification_pipeline.php';
        }
        
        $pipeline = new \WebPdfTimeSaver\Mvp\AutomatedVerificationPipeline();
        
        foreach ($templates as $templateId) {
            try {
                $verifyResults = $pipeline->verify($templateId);
                $results['templates'][$templateId] = [
                    'status' => $verifyResults['overall_status'],
                    'tests_passed' => $verifyResults['summary']['passed'] ?? 0,
                    'tests_failed' => $verifyResults['summary']['failed'] ?? 0,
                    'field_count' => count($verifyResults['tests']['positions_loaded']['positions'] ?? []),
                    'mapping_rate' => $verifyResults['tests']['field_mapping']['mapping_rate'] ?? 0,
                    'report_path' => $verifyResults['report']['html_path'] ?? null
                ];
                
                $results['summary']['total']++;
                if ($verifyResults['overall_status'] === 'PASS') {
                    $results['summary']['passed']++;
                } else {
                    $results['summary']['failed']++;
                }
            } catch (\Exception $e) {
                $results['templates'][$templateId] = [
                    'status' => 'ERROR',
                    'error' => $e->getMessage()
                ];
                $results['summary']['failed']++;
            }
        }
        
        // Generate HTML master report
        $htmlPath = $this->outputDir . '/master_report_' . date('Ymd_His') . '.html';
        $this->generateHtmlMasterReport($results, $htmlPath);
        
        // Save JSON
        $jsonPath = $this->outputDir . '/master_report_' . date('Ymd_His') . '.json';
        file_put_contents($jsonPath, json_encode($results, JSON_PRETTY_PRINT));
        
        return [
            'results' => $results,
            'html_path' => $htmlPath,
            'json_path' => $jsonPath
        ];
    }
    
    /**
     * Find all templates with position files
     */
    private function findAllTemplates(): array {
        $templates = [];
        $files = glob($this->dataDir . '/t_*_positions.json');
        
        foreach ($files as $file) {
            $basename = basename($file, '_positions.json');
            if (strpos($basename, 't_') === 0) {
                $templates[] = $basename;
            }
        }
        
        return array_unique($templates);
    }
    
    /**
     * Generate HTML master report
     */
    private function generateHtmlMasterReport(array $results, string $htmlPath): void {
        $html = "<!DOCTYPE html><html><head><title>Master Verification Report</title>";
        $html .= "<style>
            body { font-family: Arial, sans-serif; max-width: 1400px; margin: 20px auto; padding: 20px; }
            .header { background: #f0f0f0; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
            .summary { display: flex; gap: 20px; margin: 20px 0; }
            .summary-card { flex: 1; padding: 15px; border-radius: 5px; text-align: center; }
            .summary-card.total { background: #e3f2fd; }
            .summary-card.passed { background: #d4edda; color: #155724; }
            .summary-card.failed { background: #f8d7da; color: #721c24; }
            table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
            th { background: #f0f0f0; font-weight: bold; }
            .status-pass { color: #155724; font-weight: bold; }
            .status-fail { color: #721c24; font-weight: bold; }
            .status-error { color: #856404; font-weight: bold; }
        </style></head><body>";
        
        $html .= "<div class='header'><h1>Master Verification Report</h1>";
        $html .= "<p>Generated: " . $results['generated_at'] . "</p></div>";
        
        $html .= "<div class='summary'>";
        $html .= "<div class='summary-card total'><h2>" . $results['summary']['total'] . "</h2><p>Total Templates</p></div>";
        $html .= "<div class='summary-card passed'><h2>" . $results['summary']['passed'] . "</h2><p>Passed</p></div>";
        $html .= "<div class='summary-card failed'><h2>" . $results['summary']['failed'] . "</h2><p>Failed</p></div>";
        $html .= "</div>";
        
        $html .= "<table><thead><tr>";
        $html .= "<th>Template ID</th><th>Status</th><th>Fields</th><th>Mapping Rate</th><th>Tests Passed</th><th>Tests Failed</th><th>Report</th>";
        $html .= "</tr></thead><tbody>";
        
        foreach ($results['templates'] as $templateId => $template) {
            $statusClass = 'status-' . strtolower($template['status']);
            $html .= "<tr>";
            $html .= "<td><strong>$templateId</strong></td>";
            $html .= "<td class='$statusClass'>" . $template['status'] . "</td>";
            $html .= "<td>" . ($template['field_count'] ?? 0) . "</td>";
            $html .= "<td>" . number_format($template['mapping_rate'] ?? 0, 1) . "%</td>";
            $html .= "<td>" . ($template['tests_passed'] ?? 0) . "</td>";
            $html .= "<td>" . ($template['tests_failed'] ?? 0) . "</td>";
            $html .= "<td>";
            if (!empty($template['report_path'])) {
                $html .= "<a href='" . htmlspecialchars($template['report_path']) . "' target='_blank'>View Report</a>";
            }
            $html .= "</td>";
            $html .= "</tr>";
        }
        
        $html .= "</tbody></table></body></html>";
        
        file_put_contents($htmlPath, $html);
    }
}

