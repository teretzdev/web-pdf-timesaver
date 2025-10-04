<?php
/**
 * Comprehensive Test Runner
 * Executes all test suites and provides detailed reporting
 */

require_once __DIR__ . '/../vendor/autoload.php';

class ComprehensiveTestRunner
{
    private $testSuites = [];
    private $results = [];
    private $startTime;
    
    public function __construct()
    {
        $this->startTime = microtime(true);
        $this->initializeTestSuites();
    }
    
    private function initializeTestSuites()
    {
        $this->testSuites = [
            'Integration Tests' => [
                'file' => __DIR__ . '/integration_client_project_workflow_test.php',
                'class' => 'ClientProjectWorkflowTest',
                'description' => 'Tests complete client-project workflows and data integrity'
            ],
            'UI Components Tests' => [
                'file' => __DIR__ . '/ui_components_test.php',
                'class' => 'UIComponentsTest',
                'description' => 'Tests all new interface components and their rendering'
            ],
            'API Routes Tests' => [
                'file' => __DIR__ . '/api_routes_test.php',
                'class' => 'APIRoutesTest',
                'description' => 'Tests all new routes and actions for proper functionality'
            ],
            'Performance Tests' => [
                'file' => __DIR__ . '/performance_large_datasets_test.php',
                'class' => 'PerformanceLargeDatasetsTest',
                'description' => 'Tests application performance with large datasets'
            ],
            'Accessibility Tests' => [
                'file' => __DIR__ . '/accessibility_test.php',
                'class' => 'AccessibilityTest',
                'description' => 'Tests keyboard navigation, screen reader compatibility, and WCAG compliance'
            ],
            'Cross-Browser Compatibility Tests' => [
                'file' => __DIR__ . '/cross_browser_compatibility_test.php',
                'class' => 'CrossBrowserCompatibilityTest',
                'description' => 'Tests application compatibility across different browsers and devices'
            ],
            'Mobile Responsiveness Tests' => [
                'file' => __DIR__ . '/mobile_responsiveness_test.php',
                'class' => 'MobileResponsivenessTest',
                'description' => 'Tests application responsiveness across different mobile devices and screen sizes'
            ],
            'Dark Mode Functionality Tests' => [
                'file' => __DIR__ . '/dark_mode_functionality_test.php',
                'class' => 'DarkModeFunctionalityTest',
                'description' => 'Tests dark mode implementation, theme switching, and persistence'
            ],
            'Regression PDF Functionality Tests' => [
                'file' => __DIR__ . '/regression_pdf_functionality_test.php',
                'class' => 'RegressionPDFFunctionalityTest',
                'description' => 'Tests to ensure existing PDF functionality still works after refactoring'
            ]
        ];
    }
    
    public function runAllTests()
    {
        echo "🧪 COMPREHENSIVE TEST SUITE EXECUTION\n";
        echo "=====================================\n\n";
        
        $totalTests = 0;
        $totalPassed = 0;
        $totalFailed = 0;
        $totalSkipped = 0;
        
        foreach ($this->testSuites as $suiteName => $suiteInfo) {
            echo "📋 Running {$suiteName}...\n";
            echo "   {$suiteInfo['description']}\n";
            
            $suiteResult = $this->runTestSuite($suiteName, $suiteInfo);
            
            $totalTests += $suiteResult['total'];
            $totalPassed += $suiteResult['passed'];
            $totalFailed += $suiteResult['failed'];
            $totalSkipped += $suiteResult['skipped'];
            
            $this->results[$suiteName] = $suiteResult;
            
            echo "   ✅ Passed: {$suiteResult['passed']}\n";
            echo "   ❌ Failed: {$suiteResult['failed']}\n";
            echo "   ⏭️  Skipped: {$suiteResult['skipped']}\n";
            echo "   ⏱️  Time: {$suiteResult['time']}s\n\n";
        }
        
        $this->generateSummaryReport($totalTests, $totalPassed, $totalFailed, $totalSkipped);
        $this->generateDetailedReport();
        
        return [
            'total' => $totalTests,
            'passed' => $totalPassed,
            'failed' => $totalFailed,
            'skipped' => $totalSkipped,
            'success_rate' => $totalTests > 0 ? ($totalPassed / $totalTests) * 100 : 0
        ];
    }
    
    private function runTestSuite($suiteName, $suiteInfo)
    {
        $startTime = microtime(true);
        
        try {
            // Check if test file exists
            if (!file_exists($suiteInfo['file'])) {
                throw new Exception("Test file not found: {$suiteInfo['file']}");
            }
            
            // Include the test file
            require_once $suiteInfo['file'];
            
            // Check if test class exists
            if (!class_exists($suiteInfo['class'])) {
                throw new Exception("Test class not found: {$suiteInfo['class']}");
            }
            
            // Create test suite
            $suite = new PHPUnit\Framework\TestSuite($suiteInfo['class']);
            
            // Create test result
            $result = new PHPUnit\Framework\TestResult();
            
            // Add listeners for detailed output
            $result->addListener(new TestResultPrinter());
            
            // Run the test suite
            $suite->run($result);
            
            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;
            
            return [
                'total' => $result->count(),
                'passed' => $result->count() - $result->failureCount() - $result->errorCount(),
                'failed' => $result->failureCount() + $result->errorCount(),
                'skipped' => 0, // PHPUnit doesn't easily provide skipped count
                'time' => round($executionTime, 3),
                'result' => $result
            ];
            
        } catch (Exception $e) {
            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;
            
            echo "   ❌ Error: " . $e->getMessage() . "\n";
            
            return [
                'total' => 0,
                'passed' => 0,
                'failed' => 1,
                'skipped' => 0,
                'time' => round($executionTime, 3),
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function generateSummaryReport($totalTests, $totalPassed, $totalFailed, $totalSkipped)
    {
        $endTime = microtime(true);
        $totalTime = $endTime - $this->startTime;
        $successRate = $totalTests > 0 ? ($totalPassed / $totalTests) * 100 : 0;
        
        echo "📊 TEST EXECUTION SUMMARY\n";
        echo "========================\n";
        echo "Total Tests: {$totalTests}\n";
        echo "✅ Passed: {$totalPassed}\n";
        echo "❌ Failed: {$totalFailed}\n";
        echo "⏭️  Skipped: {$totalSkipped}\n";
        echo "📈 Success Rate: " . round($successRate, 2) . "%\n";
        echo "⏱️  Total Time: " . round($totalTime, 3) . "s\n\n";
        
        if ($totalFailed === 0) {
            echo "🎉 ALL TESTS PASSED! 🎉\n";
            echo "The application is ready for production deployment.\n\n";
        } else {
            echo "⚠️  SOME TESTS FAILED\n";
            echo "Please review the detailed report below and fix the issues.\n\n";
        }
    }
    
    private function generateDetailedReport()
    {
        echo "📋 DETAILED TEST REPORT\n";
        echo "=======================\n\n";
        
        foreach ($this->results as $suiteName => $result) {
            echo "🔍 {$suiteName}\n";
            echo str_repeat('-', strlen($suiteName) + 3) . "\n";
            
            if (isset($result['error'])) {
                echo "❌ Error: {$result['error']}\n\n";
                continue;
            }
            
            if ($result['failed'] > 0 && isset($result['result'])) {
                $failures = $result['result']->failures();
                $errors = $result['result']->errors();
                
                foreach ($failures as $failure) {
                    echo "❌ Failure: " . $failure->getTestName() . "\n";
                    echo "   " . $failure->getExceptionAsString() . "\n\n";
                }
                
                foreach ($errors as $error) {
                    echo "❌ Error: " . $error->getTestName() . "\n";
                    echo "   " . $error->getExceptionAsString() . "\n\n";
                }
            } else {
                echo "✅ All tests passed successfully!\n\n";
            }
        }
    }
    
    public function runSpecificTestSuite($suiteName)
    {
        if (!isset($this->testSuites[$suiteName])) {
            throw new Exception("Test suite not found: {$suiteName}");
        }
        
        echo "🧪 Running {$suiteName}...\n\n";
        
        $result = $this->runTestSuite($suiteName, $this->testSuites[$suiteName]);
        
        echo "📊 Results:\n";
        echo "✅ Passed: {$result['passed']}\n";
        echo "❌ Failed: {$result['failed']}\n";
        echo "⏱️  Time: {$result['time']}s\n\n";
        
        return $result;
    }
    
    public function listAvailableTestSuites()
    {
        echo "📋 Available Test Suites:\n";
        echo "========================\n\n";
        
        foreach ($this->testSuites as $suiteName => $suiteInfo) {
            echo "• {$suiteName}\n";
            echo "  {$suiteInfo['description']}\n\n";
        }
    }
}

/**
 * Test Result Printer for detailed output
 */
class TestResultPrinter implements PHPUnit\Framework\TestListener
{
    public function addError(PHPUnit\Framework\Test $test, Throwable $t, float $time): void
    {
        echo "   ❌ ERROR: " . $test->getName() . " - " . $t->getMessage() . "\n";
    }
    
    public function addFailure(PHPUnit\Framework\Test $test, PHPUnit\Framework\AssertionFailedError $e, float $time): void
    {
        echo "   ❌ FAILURE: " . $test->getName() . " - " . $e->getMessage() . "\n";
    }
    
    public function addIncompleteTest(PHPUnit\Framework\Test $test, Throwable $t, float $time): void
    {
        echo "   ⏭️  INCOMPLETE: " . $test->getName() . "\n";
    }
    
    public function addRiskyTest(PHPUnit\Framework\Test $test, Throwable $t, float $time): void
    {
        echo "   ⚠️  RISKY: " . $test->getName() . "\n";
    }
    
    public function addSkippedTest(PHPUnit\Framework\Test $test, Throwable $t, float $time): void
    {
        echo "   ⏭️  SKIPPED: " . $test->getName() . "\n";
    }
    
    public function addWarning(PHPUnit\Framework\Test $test, PHPUnit\Framework\Warning $e, float $time): void
    {
        echo "   ⚠️  WARNING: " . $test->getName() . " - " . $e->getMessage() . "\n";
    }
    
    public function startTest(PHPUnit\Framework\Test $test): void
    {
        echo "   🧪 Running: " . $test->getName() . "\n";
    }
    
    public function endTest(PHPUnit\Framework\Test $test, float $time): void
    {
        echo "   ✅ Completed: " . $test->getName() . " (" . round($time, 3) . "s)\n";
    }
    
    public function startTestSuite(PHPUnit\Framework\TestSuite $suite): void
    {
        // Implementation not needed for this use case
    }
    
    public function endTestSuite(PHPUnit\Framework\TestSuite $suite): void
    {
        // Implementation not needed for this use case
    }
}

// Command line interface
if (php_sapi_name() === 'cli') {
    $runner = new ComprehensiveTestRunner();
    
    $args = $argv ?? [];
    
    if (count($args) > 1) {
        $command = $args[1];
        
        switch ($command) {
            case 'list':
                $runner->listAvailableTestSuites();
                break;
                
            case 'run':
                if (count($args) > 2) {
                    $suiteName = $args[2];
                    try {
                        $runner->runSpecificTestSuite($suiteName);
                    } catch (Exception $e) {
                        echo "❌ Error: " . $e->getMessage() . "\n";
                        exit(1);
                    }
                } else {
                    $runner->runAllTests();
                }
                break;
                
            default:
                echo "Usage: php run_comprehensive_tests.php [list|run] [suite_name]\n";
                echo "  list - List available test suites\n";
                echo "  run - Run all tests or specific suite\n";
                break;
        }
    } else {
        $runner->runAllTests();
    }
}













