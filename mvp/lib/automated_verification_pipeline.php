<?php
/**
 * Automated PDF Verification Pipeline
 * 
 * Comprehensive automated verification system that:
 * 1. Generates test PDFs with extracted positions
 * 2. Creates debug PDFs with visual markers
 * 3. Validates coordinate accuracy
 * 4. Verifies field name mapping
 * 5. Generates verification reports
 * 6. Provides pass/fail status
 */

declare(strict_types=1);

namespace WebPdfTimeSaver\Mvp;

use setasign\Fpdi\Fpdi;

class AutomatedVerificationPipeline {
    
    private $outputDir;
    private $logFile;
    private $results = [];
    
    public function __construct(string $outputDir = null) {
        $this->outputDir = $outputDir ?? __DIR__ . '/../../output/verification';
        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }
        $this->logFile = __DIR__ . '/../../logs/verification.log';
    }
    
    /**
     * Run full automated verification pipeline
     */
    public function verify(string $templateId, array $testData = null, bool $autoFix = false): array {
        $this->log("=== STARTING AUTOMATED VERIFICATION FOR: $templateId ===");
        
        $results = [
            'template_id' => $templateId,
            'timestamp' => date('Y-m-d H:i:s'),
            'tests' => [],
            'overall_status' => 'PASS',
            'summary' => [],
            'auto_fixes_applied' => []
        ];
        
        // Step 1: Load positions
        $positionsResult = $this->verifyPositionsLoaded($templateId);
        $results['tests']['positions_loaded'] = $positionsResult;
        if (!$positionsResult['passed']) {
            $results['overall_status'] = 'FAIL';
            return $results;
        }
        $positions = $positionsResult['positions'];
        
        // Step 2: Validate position coordinates
        $coordinatesResult = $this->verifyCoordinates($positions);
        $results['tests']['coordinates'] = $coordinatesResult;
        if (!$coordinatesResult['passed']) {
            $results['overall_status'] = 'FAIL';
        }
        
        // Step 3: Verify field name mapping
        $mappingResult = $this->verifyFieldMapping($templateId, $positions, $testData);
        $results['tests']['field_mapping'] = $mappingResult;
        if (!$mappingResult['passed']) {
            $results['overall_status'] = 'FAIL';
        }
        
        // Step 4: Generate test PDF and verify data placement
        $placementResult = $this->verifyDataPlacement($templateId, $positions, $testData);
        $results['tests']['data_placement'] = $placementResult;
        if (!$placementResult['passed']) {
            $results['overall_status'] = 'FAIL';
        }
        
        // Step 5: Generate visual debug PDF
        $visualResult = $this->generateVisualDebug($templateId, $positions, $testData);
        $results['tests']['visual_debug'] = $visualResult;
        
        // Summary - Calculate BEFORE generating report
        $totalTests = count($results['tests']);
        $passed = 0;
        $failed = 0;
        foreach ($results['tests'] as $test) {
            if (isset($test['passed']) && $test['passed']) {
                $passed++;
            } else {
                $failed++;
            }
        }
        
        $results['summary'] = [
            'total_tests' => $totalTests,
            'passed' => $passed,
            'failed' => $failed,
            'overall_status' => $results['overall_status']
        ];
        
        // Step 6: Generate verification report (now summary is available)
        $reportResult = $this->generateReport($templateId, $results);
        $results['report'] = $reportResult;
        
        $this->log("=== VERIFICATION COMPLETE: {$results['overall_status']} ===");
        
        return $results;
    }
    
    /**
     * Verify positions are loaded correctly
     */
    private function verifyPositionsLoaded(string $templateId): array {
        $this->log("Verifying positions loaded for: $templateId");
        
        $loader = new FieldPositionLoader();
        $positions = $loader->loadFieldPositions($templateId);
        
        $result = [
            'test' => 'positions_loaded',
            'passed' => false,
            'message' => '',
            'positions' => $positions,
            'issues' => []
        ];
        
        if (empty($positions)) {
            $result['message'] = 'No positions loaded';
            $result['issues'][] = 'Positions file not found or empty';
            $this->log("  ❌ FAIL: No positions loaded");
            return $result;
        }
        
        $fieldCount = count($positions);
        $result['field_count'] = $fieldCount;
        
        if ($fieldCount < 10) {
            $result['issues'][] = "Only $fieldCount fields loaded (expected at least 10)";
            $this->log("  ⚠️  WARNING: Only $fieldCount fields loaded");
        }
        
        // Check for required fields
        $requiredPatterns = [
            '/attyfor|attorney/i',
            '/casenumber/i',
            '/party1|party2|petitioner|respondent/i'
        ];
        
        $foundRequired = [];
        foreach ($positions as $fieldName => $pos) {
            foreach ($requiredPatterns as $pattern) {
                if (preg_match($pattern, $fieldName)) {
                    $foundRequired[] = $fieldName;
                    break;
                }
            }
        }
        
        if (count($foundRequired) < 2) {
            $result['issues'][] = 'Missing required field types (attorney, case number, parties)';
        }
        
        $result['passed'] = true;
        $result['message'] = "Loaded $fieldCount fields";
        $this->log("  ✅ PASS: Loaded $fieldCount fields");
        
        return $result;
    }
    
    /**
     * Verify coordinate validity
     */
    private function verifyCoordinates(array $positions): array {
        $this->log("Verifying coordinate validity");
        
        $result = [
            'test' => 'coordinates',
            'passed' => true,
            'issues' => [],
            'statistics' => []
        ];
        
        $pageWidthMm = 215.9; // US Letter
        $pageHeightMm = 279.4;
        $tolerance = 5.0;
        
        $invalidCoords = 0;
        $outOfBounds = 0;
        $negativeCoords = 0;
        $missingCoords = 0;
        
        foreach ($positions as $fieldName => $pos) {
            $x = (float)($pos['x'] ?? null);
            $y = (float)($pos['y'] ?? null);
            $page = (int)($pos['page'] ?? 1);
            
            // Check for missing coordinates
            if ($x === null || $y === null) {
                $missingCoords++;
                $result['issues'][] = "$fieldName: Missing coordinates";
                continue;
            }
            
            // Check for invalid numbers
            if (!is_finite($x) || !is_finite($y)) {
                $invalidCoords++;
                $result['issues'][] = "$fieldName: Invalid coordinates (NaN/Infinity)";
                continue;
            }
            
            // Check for negative coordinates
            if ($x < 0 || $y < 0) {
                $negativeCoords++;
                $result['issues'][] = "$fieldName: Negative coordinates (x=$x, y=$y)";
            }
            
            // Check for out of bounds
            if ($x > ($pageWidthMm + $tolerance)) {
                $outOfBounds++;
                $result['issues'][] = "$fieldName: X coordinate out of bounds ($x > $pageWidthMm)";
            }
            if ($y > ($pageHeightMm + $tolerance)) {
                $outOfBounds++;
                $result['issues'][] = "$fieldName: Y coordinate out of bounds ($y > $pageHeightMm)";
            }
        }
        
        $result['statistics'] = [
            'total_fields' => count($positions),
            'invalid_coords' => $invalidCoords,
            'negative_coords' => $negativeCoords,
            'out_of_bounds' => $outOfBounds,
            'missing_coords' => $missingCoords
        ];
        
        if ($invalidCoords > 0 || $missingCoords > 0 || $negativeCoords > count($positions) * 0.1) {
            $result['passed'] = false;
            $this->log("  ❌ FAIL: Coordinate issues found");
        } else {
            $this->log("  ✅ PASS: Coordinates valid");
        }
        
        return $result;
    }
    
    /**
     * Verify field name mapping
     */
    private function verifyFieldMapping(string $templateId, array $positions, array $testData = null): array {
        $this->log("Verifying field name mapping");
        
        $result = [
            'test' => 'field_mapping',
            'passed' => true,
            'mapped_fields' => [],
            'unmapped_fields' => [],
            'issues' => []
        ];
        
        if (empty($testData)) {
            if (!class_exists('WebPdfTimeSaver\Mvp\FL100TestDataGenerator')) {
                require_once __DIR__ . '/fl100_test_data_generator.php';
            }
            $testData = FL100TestDataGenerator::generateCompleteTestData();
        }
        
        if (!class_exists('WebPdfTimeSaver\Mvp\FieldNameMapper')) {
            require_once __DIR__ . '/field_name_mapper.php';
        }
        
        $mapper = new FieldNameMapper();
        $mappedCount = 0;
        $unmappedCount = 0;
        
        foreach ($testData as $testField => $value) {
            if (empty($value)) {
                continue;
            }
            
            // Try to find matching position
            $found = false;
            $matchedField = null;
            
            // Direct match
            if (isset($positions[$testField])) {
                $found = true;
                $matchedField = $testField;
            } else {
                // Pattern matching (same logic as pdf_form_filler)
                $normalizedTestKey = strtolower(str_replace(['_', '-', ' '], '', $testField));
                $searchPatterns = $this->buildSearchPatterns($normalizedTestKey);
                
                foreach ($positions as $posKey => $pos) {
                    $normalizedPosKey = strtolower($posKey);
                    foreach ($searchPatterns as $pattern) {
                        if (preg_match($pattern, $normalizedPosKey)) {
                            $found = true;
                            $matchedField = $posKey;
                            break 2;
                        }
                    }
                }
            }
            
            if ($found) {
                $mappedCount++;
                $result['mapped_fields'][] = [
                    'test_field' => $testField,
                    'matched_field' => $matchedField
                ];
            } else {
                $unmappedCount++;
                $result['unmapped_fields'][] = $testField;
                $result['issues'][] = "Test field '$testField' has no matching position";
            }
        }
        
        $mappingRate = count($testData) > 0 ? ($mappedCount / count($testData)) * 100 : 0;
        $result['mapping_rate'] = $mappingRate;
        $result['mapped_count'] = $mappedCount;
        $result['unmapped_count'] = $unmappedCount;
        
        // Fail if less than 70% of fields are mapped
        if ($mappingRate < 70) {
            $result['passed'] = false;
            $this->log("  ❌ FAIL: Only $mappingRate% of fields mapped ($mappedCount/" . count($testData) . ")");
        } else {
            $this->log("  ✅ PASS: $mappingRate% of fields mapped ($mappedCount/" . count($testData) . ")");
        }
        
        return $result;
    }
    
    /**
     * Build search patterns for field matching
     */
    private function buildSearchPatterns(string $normalizedTestKey): array {
        $patterns = [];
        
        if (strpos($normalizedTestKey, 'attorney') !== false) {
            if (strpos($normalizedTestKey, 'name') !== false || strpos($normalizedTestKey, 'for') !== false) {
                $patterns[] = '/attyfor|attorneyname|attorneyfor/i';
            }
            if (strpos($normalizedTestKey, 'phone') !== false || strpos($normalizedTestKey, 'telephone') !== false) {
                $patterns[] = '/telephone|phone|fax/i';
            }
            if (strpos($normalizedTestKey, 'firm') !== false) {
                $patterns[] = '/firm/i';
            }
            if (strpos($normalizedTestKey, 'address') !== false) {
                $patterns[] = '/streetaddress|address/i';
            }
            if (strpos($normalizedTestKey, 'bar') !== false) {
                $patterns[] = '/barnumber|bar/i';
            }
        }
        if (strpos($normalizedTestKey, 'case') !== false && strpos($normalizedTestKey, 'number') !== false) {
            $patterns[] = '/casenumber/i';
            $patterns[] = '/case.*number/i';
        }
        if (strpos($normalizedTestKey, 'party1') !== false || strpos($normalizedTestKey, 'petitioner') !== false) {
            $patterns[] = '/party1/i';
            $patterns[] = '/petitioner/i';
        }
        if (strpos($normalizedTestKey, 'party2') !== false || strpos($normalizedTestKey, 'respondent') !== false) {
            $patterns[] = '/party2/i';
            $patterns[] = '/respondent/i';
        }
                if (strpos($normalizedTestKey, 'separation') !== false && strpos($normalizedTestKey, 'date') !== false) {
            $patterns[] = '/dateofseparation|separationdate/i';
        }
        if (strpos($normalizedTestKey, 'city') !== false || strpos($normalizedTestKey, 'state') !== false || strpos($normalizedTestKey, 'zip') !== false) {
            $patterns[] = '/city|state|zip/i';
        }
        if (strpos($normalizedTestKey, 'email') !== false) {
            $patterns[] = '/email/i';
        }
        if (strpos($normalizedTestKey, 'county') !== false || strpos($normalizedTestKey, 'court') !== false) {
            $patterns[] = '/county|court/i';
        }
        if (strpos($normalizedTestKey, 'marriage') !== false) {
            $patterns[] = '/marriage/i';
        }
        if (strpos($normalizedTestKey, 'signature') !== false) {
            $patterns[] = '/signature/i';
        }
        if (strpos($normalizedTestKey, 'children') !== false) {
            $patterns[] = '/children|child/i';
        }
        if (strpos($normalizedTestKey, 'division') !== false || strpos($normalizedTestKey, 'property') !== false) {
            $patterns[] = '/property|division/i';
        }
        if (strpos($normalizedTestKey, 'support') !== false || strpos($normalizedTestKey, 'spousal') !== false) {
            $patterns[] = '/support|spousal/i';
        }
        
        return $patterns;
    }
    
    /**
     * Verify data placement by generating PDF and checking
     */
    private function verifyDataPlacement(string $templateId, array $positions, array $testData = null): array {
        $this->log("Verifying data placement");
        
        $result = [
            'test' => 'data_placement',
            'passed' => false,
            'pdf_path' => null,
            'fields_placed' => 0,
            'issues' => []
        ];
        
        try {
            if (empty($testData)) {
                if (!class_exists('WebPdfTimeSaver\Mvp\FL100TestDataGenerator')) {
                    require_once __DIR__ . '/fl100_test_data_generator.php';
                }
                $testData = FL100TestDataGenerator::generateCompleteTestData();
            }
            
            if (!class_exists('WebPdfTimeSaver\Mvp\PdfFormFiller')) {
                require_once __DIR__ . '/pdf_form_filler.php';
            }
            
            $filler = new PdfFormFiller();
            $template = ['id' => $templateId, 'pageCount' => 3];
            
            $output = $filler->fillPdfFormWithPositions($template, $testData, $templateId);
            
            if (isset($output['path']) && file_exists($output['path'])) {
                $result['pdf_path'] = $output['path'];
                $result['fields_placed'] = $output['fields_placed'] ?? 0;
                $result['passed'] = $result['fields_placed'] > 0;
                $this->log("  ✅ PASS: PDF generated with {$result['fields_placed']} fields placed");
            } else {
                $result['issues'][] = 'PDF generation failed';
                $this->log("  ❌ FAIL: PDF generation failed");
            }
        } catch (\Exception $e) {
            $result['issues'][] = $e->getMessage();
            $this->log("  ❌ FAIL: Exception: " . $e->getMessage());
        }
        
        return $result;
    }
    
    /**
     * Generate visual debug PDF with markers
     */
    private function generateVisualDebug(string $templateId, array $positions, array $testData = null): array {
        $this->log("Generating visual debug PDF");
        
        $result = [
            'test' => 'visual_debug',
            'passed' => false,
            'debug_pdf_path' => null
        ];
        
        try {
            if (!class_exists('WebPdfTimeSaver\Mvp\PositionDebugGenerator')) {
                require_once __DIR__ . '/position_debug_generator.php';
            }
            
            $debugGenerator = new PositionDebugGenerator();
            $timestamp = date('Ymd_His');
            $debugPdf = $this->outputDir . '/debug_' . $templateId . '_' . $timestamp . '.pdf';
            
            if (empty($testData)) {
                if (!class_exists('WebPdfTimeSaver\Mvp\FL100TestDataGenerator')) {
                    require_once __DIR__ . '/fl100_test_data_generator.php';
                }
                $testData = FL100TestDataGenerator::generateCompleteTestData();
            }
            
            $debugGenerator->generateDebugPdf($templateId, $positions, $testData, $debugPdf);
            
            if (file_exists($debugPdf)) {
                $result['debug_pdf_path'] = $debugPdf;
                $result['passed'] = true;
                $this->log("  ✅ PASS: Debug PDF generated");
            } else {
                $this->log("  ❌ FAIL: Debug PDF not created");
            }
        } catch (\Exception $e) {
            $this->log("  ❌ FAIL: Exception: " . $e->getMessage());
        }
        
        return $result;
    }
    
    /**
     * Generate verification report
     */
    private function generateReport(string $templateId, array $results): array {
        $this->log("Generating verification report");
        
        $reportPath = $this->outputDir . '/report_' . $templateId . '_' . date('Ymd_His') . '.json';
        $htmlReportPath = $this->outputDir . '/report_' . $templateId . '_' . date('Ymd_His') . '.html';
        
        // Save JSON report
        file_put_contents($reportPath, json_encode($results, JSON_PRETTY_PRINT));
        
        // Generate HTML report
        $html = $this->generateHtmlReport($results);
        file_put_contents($htmlReportPath, $html);
        
        return [
            'json_path' => $reportPath,
            'html_path' => $htmlReportPath
        ];
    }
    
    /**
     * Generate HTML report
     */
    private function generateHtmlReport(array $results): string {
        $statusClass = $results['overall_status'] === 'PASS' ? 'pass' : 'fail';
        $statusIcon = $results['overall_status'] === 'PASS' ? '✅' : '❌';
        
        $html = "<!DOCTYPE html><html><head><title>Verification Report - {$results['template_id']}</title>";
        $html .= "<style>
            body { font-family: Arial, sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; }
            .status { font-size: 24px; font-weight: bold; padding: 10px; margin: 20px 0; }
            .status.pass { background: #d4edda; color: #155724; }
            .status.fail { background: #f8d7da; color: #721c24; }
            .test { border: 1px solid #ddd; margin: 10px 0; padding: 15px; }
            .test.passed { background: #d4edda; }
            .test.failed { background: #f8d7da; }
            .test h3 { margin-top: 0; }
            .issues { background: #fff3cd; padding: 10px; margin: 10px 0; }
            .issues ul { margin: 5px 0; }
            table { width: 100%; border-collapse: collapse; margin: 10px 0; }
            th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
            th { background: #f0f0f0; }
        </style></head><body>";
        
        $html .= "<h1>Verification Report: {$results['template_id']}</h1>";
        $html .= "<div class='status $statusClass'>$statusIcon Overall Status: {$results['overall_status']}</div>";
        
        $html .= "<h2>Summary</h2>";
        $html .= "<p>Total Tests: {$results['summary']['total_tests']}</p>";
        $html .= "<p>Passed: {$results['summary']['passed']}</p>";
        $html .= "<p>Failed: {$results['summary']['failed']}</p>";
        
        $html .= "<h2>Test Results</h2>";
        foreach ($results['tests'] as $testName => $testResult) {
            $testClass = ($testResult['passed'] ?? false) ? 'passed' : 'failed';
            $testIcon = ($testResult['passed'] ?? false) ? '✅' : '❌';
            $html .= "<div class='test $testClass'>";
            $html .= "<h3>$testIcon " . ucfirst(str_replace('_', ' ', $testName)) . "</h3>";
            
            if (isset($testResult['message'])) {
                $html .= "<p><strong>Message:</strong> {$testResult['message']}</p>";
            }
            
            if (!empty($testResult['issues'])) {
                $html .= "<div class='issues'><strong>Issues:</strong><ul>";
                foreach ($testResult['issues'] as $issue) {
                    $html .= "<li>" . htmlspecialchars($issue) . "</li>";
                }
                $html .= "</ul></div>";
            }
            
            if (isset($testResult['statistics'])) {
                $html .= "<table><tr><th>Metric</th><th>Value</th></tr>";
                foreach ($testResult['statistics'] as $key => $value) {
                    $html .= "<tr><td>" . ucfirst(str_replace('_', ' ', $key)) . "</td><td>$value</td></tr>";
                }
                $html .= "</table>";
            }
            
            $html .= "</div>";
        }
        
        $html .= "</body></html>";
        return $html;
    }
    
    /**
     * Log message
     */
    private function log(string $message): void {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message" . PHP_EOL;
        file_put_contents($this->logFile, $logMessage, FILE_APPEND);
        error_log($logMessage);
    }
}

