<?php
/**
 * Comprehensive Test Suite for Universal PDF Field Extraction
 * Tests all extraction methods on W-9, FL-100, FL-105, and other PDFs
 */

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/mvp/lib/pdf_field_extractor.php';
require_once __DIR__ . '/mvp/lib/auto_position_extractor.php';

use WebPdfTimeSaver\Mvp\PdfFieldExtractor;
use WebPdfTimeSaver\Mvp\AutoPositionExtractor;

class ExtractionTestSuite {
    private $testResults = [];
    private $extractor;
    private $autoExtractor;
    
    public function __construct() {
        $this->extractor = new PdfFieldExtractor();
        $this->autoExtractor = new AutoPositionExtractor();
    }
    
    /**
     * Run all tests
     */
    public function runAllTests() {
        echo "🧪 Universal PDF Field Extraction Test Suite\n";
        echo "============================================\n\n";
        
        $this->testSystemStatus();
        $this->testW9Extraction();
        $this->testFl100Extraction();
        $this->testFl105Extraction();
        $this->testCorruptedPdf();
        $this->testEncryptedPdf();
        
        $this->generateReport();
    }
    
    /**
     * Test system status and availability
     */
    private function testSystemStatus() {
        echo "🔍 Testing System Status...\n";
        
        $status = $this->autoExtractor->getStatus();
        
        $this->testResults['system'] = [
            'nodejs_available' => $status['nodejs_available'],
            'script_available' => $status['script_available'],
            'qpdf_available' => $status['qpdf_available'] ?? false
        ];
        
        echo "   Node.js: " . ($status['nodejs_available'] ? "✅ Available" : "❌ Not found") . "\n";
        echo "   Script: " . ($status['script_available'] ? "✅ Available" : "❌ Not found") . "\n";
        echo "   qpdf: " . ($status['qpdf_available'] ? "✅ Available" : "⚠️  Not found") . "\n";
        echo "\n";
    }
    
    /**
     * Test W-9 extraction (should use Method 1: pdf-lib direct)
     */
    private function testW9Extraction() {
        echo "📄 Testing W-9 Extraction...\n";
        
        $pdfPath = __DIR__ . '/uploads/w9.pdf';
        if (!file_exists($pdfPath)) {
            echo "   ⚠️  W-9 PDF not found, skipping test\n\n";
            $this->testResults['w9'] = ['status' => 'skipped', 'reason' => 'PDF not found'];
            return;
        }
        
        try {
            $result = $this->autoExtractor->extractPositions($pdfPath, 'test_w9');
            
            $this->testResults['w9'] = [
                'status' => $result['success'] ? 'success' : 'failed',
                'method' => $result['method'] ?? 'none',
                'fields' => count($result['fields'] ?? []),
                'pageCount' => $result['pageCount'] ?? 0,
                'errors' => $result['errors'] ?? []
            ];
            
            if ($result['success']) {
                echo "   ✅ SUCCESS: {$result['fields']} fields extracted using {$result['method']}\n";
                echo "   📊 Pages: {$result['pageCount']}\n";
                
                // Show sample fields
                if (!empty($result['fields'])) {
                    echo "   📋 Sample fields:\n";
                    foreach (array_slice($result['fields'], 0, 3) as $field) {
                        echo "      - {$field['name']} ({$field['type']}): {$field['x']}, {$field['y']}\n";
                    }
                }
            } else {
                echo "   ❌ FAILED: " . implode(', ', $result['errors'] ?? ['Unknown error']) . "\n";
            }
            
        } catch (\Exception $e) {
            echo "   ❌ ERROR: " . $e->getMessage() . "\n";
            $this->testResults['w9'] = ['status' => 'error', 'error' => $e->getMessage()];
        }
        
        echo "\n";
    }
    
    /**
     * Test FL-100 extraction (should use Method 2: qpdf + pdf-lib)
     */
    private function testFl100Extraction() {
        echo "📄 Testing FL-100 Extraction...\n";
        
        $pdfPath = __DIR__ . '/uploads/fl100.pdf';
        if (!file_exists($pdfPath)) {
            echo "   ⚠️  FL-100 PDF not found, skipping test\n\n";
            $this->testResults['fl100'] = ['status' => 'skipped', 'reason' => 'PDF not found'];
            return;
        }
        
        try {
            $result = $this->autoExtractor->extractPositions($pdfPath, 'test_fl100');
            
            $this->testResults['fl100'] = [
                'status' => $result['success'] ? 'success' : 'failed',
                'method' => $result['method'] ?? 'none',
                'fields' => count($result['fields'] ?? []),
                'pageCount' => $result['pageCount'] ?? 0,
                'errors' => $result['errors'] ?? []
            ];
            
            if ($result['success']) {
                echo "   ✅ SUCCESS: {$result['fields']} fields extracted using {$result['method']}\n";
                echo "   📊 Pages: {$result['pageCount']}\n";
                
                // Show sample fields
                if (!empty($result['fields'])) {
                    echo "   📋 Sample fields:\n";
                    foreach (array_slice($result['fields'], 0, 3) as $field) {
                        echo "      - {$field['name']} ({$field['type']}): {$field['x']}, {$field['y']}\n";
                    }
                }
            } else {
                echo "   ❌ FAILED: " . implode(', ', $result['errors'] ?? ['Unknown error']) . "\n";
                echo "   💡 This is expected for encrypted PDFs without qpdf\n";
            }
            
        } catch (\Exception $e) {
            echo "   ❌ ERROR: " . $e->getMessage() . "\n";
            $this->testResults['fl100'] = ['status' => 'error', 'error' => $e->getMessage()];
        }
        
        echo "\n";
    }
    
    /**
     * Test FL-105 extraction (should use Method 2: qpdf + pdf-lib)
     */
    private function testFl105Extraction() {
        echo "📄 Testing FL-105 Extraction...\n";
        
        $pdfPath = __DIR__ . '/uploads/fl105.pdf';
        if (!file_exists($pdfPath)) {
            echo "   ⚠️  FL-105 PDF not found, skipping test\n\n";
            $this->testResults['fl105'] = ['status' => 'skipped', 'reason' => 'PDF not found'];
            return;
        }
        
        try {
            $result = $this->autoExtractor->extractPositions($pdfPath, 'test_fl105');
            
            $this->testResults['fl105'] = [
                'status' => $result['success'] ? 'success' : 'failed',
                'method' => $result['method'] ?? 'none',
                'fields' => count($result['fields'] ?? []),
                'pageCount' => $result['pageCount'] ?? 0,
                'errors' => $result['errors'] ?? []
            ];
            
            if ($result['success']) {
                echo "   ✅ SUCCESS: {$result['fields']} fields extracted using {$result['method']}\n";
                echo "   📊 Pages: {$result['pageCount']}\n";
                
                // Show sample fields
                if (!empty($result['fields'])) {
                    echo "   📋 Sample fields:\n";
                    foreach (array_slice($result['fields'], 0, 3) as $field) {
                        echo "      - {$field['name']} ({$field['type']}): {$field['x']}, {$field['y']}\n";
                    }
                }
            } else {
                echo "   ❌ FAILED: " . implode(', ', $result['errors'] ?? ['Unknown error']) . "\n";
                echo "   💡 This is expected for encrypted PDFs without qpdf\n";
            }
            
        } catch (\Exception $e) {
            echo "   ❌ ERROR: " . $e->getMessage() . "\n";
            $this->testResults['fl105'] = ['status' => 'error', 'error' => $e->getMessage()];
        }
        
        echo "\n";
    }
    
    /**
     * Test corrupted PDF handling
     */
    private function testCorruptedPdf() {
        echo "📄 Testing Corrupted PDF Handling...\n";
        
        // Create a test corrupted PDF (empty file)
        $corruptedPath = __DIR__ . '/temp/corrupted_test.pdf';
        $tempDir = dirname($corruptedPath);
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        
        file_put_contents($corruptedPath, 'This is not a valid PDF');
        
        try {
            $result = $this->autoExtractor->extractPositions($corruptedPath, 'test_corrupted');
            
            $this->testResults['corrupted'] = [
                'status' => $result['success'] ? 'unexpected_success' : 'expected_failure',
                'method' => $result['method'] ?? 'none',
                'fields' => count($result['fields'] ?? []),
                'errors' => $result['errors'] ?? []
            ];
            
            if ($result['success']) {
                echo "   ⚠️  UNEXPECTED: Corrupted PDF was processed successfully\n";
            } else {
                echo "   ✅ EXPECTED: Corrupted PDF failed gracefully\n";
                echo "   📝 Error: " . implode(', ', $result['errors'] ?? ['Unknown error']) . "\n";
            }
            
        } catch (\Exception $e) {
            echo "   ✅ EXPECTED: Corrupted PDF threw exception\n";
            echo "   📝 Error: " . $e->getMessage() . "\n";
            $this->testResults['corrupted'] = ['status' => 'expected_error', 'error' => $e->getMessage()];
        }
        
        // Clean up
        if (file_exists($corruptedPath)) {
            unlink($corruptedPath);
        }
        
        echo "\n";
    }
    
    /**
     * Test encrypted PDF without password
     */
    private function testEncryptedPdf() {
        echo "📄 Testing Encrypted PDF (No Password)...\n";
        
        // This test assumes we have an encrypted PDF without a known password
        $encryptedPath = __DIR__ . '/uploads/encrypted_test.pdf';
        if (!file_exists($encryptedPath)) {
            echo "   ⚠️  Encrypted test PDF not found, skipping test\n\n";
            $this->testResults['encrypted'] = ['status' => 'skipped', 'reason' => 'PDF not found'];
            return;
        }
        
        try {
            $result = $this->autoExtractor->extractPositions($encryptedPath, 'test_encrypted');
            
            $this->testResults['encrypted'] = [
                'status' => $result['success'] ? 'unexpected_success' : 'expected_failure',
                'method' => $result['method'] ?? 'none',
                'fields' => count($result['fields'] ?? []),
                'errors' => $result['errors'] ?? []
            ];
            
            if ($result['success']) {
                echo "   ⚠️  UNEXPECTED: Encrypted PDF was processed successfully\n";
            } else {
                echo "   ✅ EXPECTED: Encrypted PDF failed gracefully\n";
                echo "   📝 Error: " . implode(', ', $result['errors'] ?? ['Unknown error']) . "\n";
            }
            
        } catch (\Exception $e) {
            echo "   ✅ EXPECTED: Encrypted PDF threw exception\n";
            echo "   📝 Error: " . $e->getMessage() . "\n";
            $this->testResults['encrypted'] = ['status' => 'expected_error', 'error' => $e->getMessage()];
        }
        
        echo "\n";
    }
    
    /**
     * Generate comprehensive test report
     */
    private function generateReport() {
        echo "📊 Test Results Summary\n";
        echo "======================\n\n";
        
        $totalTests = 0;
        $passedTests = 0;
        $failedTests = 0;
        $skippedTests = 0;
        
        foreach ($this->testResults as $testName => $result) {
            $totalTests++;
            
            switch ($result['status']) {
                case 'success':
                case 'expected_failure':
                case 'expected_error':
                    $passedTests++;
                    $status = '✅ PASS';
                    break;
                case 'failed':
                case 'unexpected_success':
                    $failedTests++;
                    $status = '❌ FAIL';
                    break;
                case 'skipped':
                    $skippedTests++;
                    $status = '⏭️  SKIP';
                    break;
                default:
                    $status = '❓ UNKNOWN';
            }
            
            echo sprintf("%-15s %s", ucfirst($testName) . ':', $status);
            
            if (isset($result['method'])) {
                echo " ({$result['method']})";
            }
            if (isset($result['fields'])) {
                echo " [{$result['fields']} fields]";
            }
            if (isset($result['reason'])) {
                echo " - {$result['reason']}";
            }
            
            echo "\n";
        }
        
        echo "\n";
        echo "Overall Results:\n";
        echo "  Total Tests: $totalTests\n";
        echo "  Passed: $passedTests\n";
        echo "  Failed: $failedTests\n";
        echo "  Skipped: $skippedTests\n";
        echo "  Success Rate: " . round(($passedTests / $totalTests) * 100, 1) . "%\n\n";
        
        // Recommendations
        echo "💡 Recommendations:\n";
        
        if (!$this->testResults['system']['nodejs_available']) {
            echo "  - Install Node.js for universal extraction capabilities\n";
        }
        
        if (!$this->testResults['system']['qpdf_available']) {
            echo "  - Install qpdf for encrypted PDF support\n";
        }
        
        if ($this->testResults['w9']['status'] === 'success') {
            echo "  - W-9 extraction working perfectly ✅\n";
        }
        
        if ($this->testResults['fl100']['status'] === 'failed' && !$this->testResults['system']['qpdf_available']) {
            echo "  - Install qpdf to enable FL-100/FL-105 extraction\n";
        }
        
        echo "\n";
        
        // Save detailed results
        $this->saveDetailedResults();
    }
    
    /**
     * Save detailed test results to file
     */
    private function saveDetailedResults() {
        $resultsFile = __DIR__ . '/logs/extraction_test_results_' . date('Y-m-d_H-i-s') . '.json';
        $logsDir = dirname($resultsFile);
        
        if (!file_exists($logsDir)) {
            mkdir($logsDir, 0755, true);
        }
        
        $detailedResults = [
            'timestamp' => date('c'),
            'system' => $this->testResults,
            'summary' => [
                'total_tests' => count($this->testResults),
                'passed' => array_filter($this->testResults, fn($r) => in_array($r['status'], ['success', 'expected_failure', 'expected_error'])),
                'failed' => array_filter($this->testResults, fn($r) => in_array($r['status'], ['failed', 'unexpected_success'])),
                'skipped' => array_filter($this->testResults, fn($r) => $r['status'] === 'skipped')
            ]
        ];
        
        file_put_contents($resultsFile, json_encode($detailedResults, JSON_PRETTY_PRINT));
        echo "📁 Detailed results saved to: $resultsFile\n";
    }
}

// Run tests if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $testSuite = new ExtractionTestSuite();
    $testSuite->runAllTests();
}
