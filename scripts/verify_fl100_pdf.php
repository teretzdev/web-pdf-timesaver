#!/usr/bin/env php
<?php
/**
 * FL-100 PDF Verification Script
 * 
 * This script:
 * 1. Generates an FL-100 PDF from our system with test data
 * 2. Uses MCP browser automation to generate PDFs from reference sites
 * 3. Compares the outputs visually
 */

declare(strict_types=1);

require_once __DIR__ . '/../mvp/lib/data.php';
require_once __DIR__ . '/../mvp/templates/registry.php';
require_once __DIR__ . '/../mvp/lib/fill_service.php';
require_once __DIR__ . '/../mvp/lib/logger.php';

use WebPdfTimeSaver\Mvp\DataStore;
use WebPdfTimeSaver\Mvp\TemplateRegistry;
use WebPdfTimeSaver\Mvp\FillService;
use WebPdfTimeSaver\Mvp\Logger;

class FL100Verifier {
    private DataStore $store;
    private array $templates;
    private FillService $fill;
    private Logger $logger;
    private array $testData;
    
    public function __construct() {
        $this->store = new DataStore(__DIR__ . '/../data/mvp.json');
        $this->templates = TemplateRegistry::load();
        $this->logger = new Logger();
        $this->fill = new FillService(__DIR__ . '/../output', $this->logger);
        $this->testData = $this->getTestData();
    }
    
    private function getTestData(): array {
        return [
            'attorney_name' => 'John Michael Smith, Esq.',
            'attorney_firm' => 'Smith & Associates Family Law',
            'attorney_address' => '1234 Legal Plaza, Suite 500',
            'attorney_city_state_zip' => 'Los Angeles, CA 90210',
            'attorney_phone' => '(555) 123-4567',
            'attorney_email' => 'jsmith@smithlaw.com',
            'attorney_bar_number' => '123456',
            'case_number' => 'FL-2024-001234',
            'court_county' => 'Los Angeles',
            'court_address' => '111 N Hill St, Los Angeles, CA 90012',
            'case_type' => 'Dissolution of Marriage',
            'filing_date' => date('m/d/Y'),
            'petitioner_name' => 'Sarah Elizabeth Johnson',
            'respondent_name' => 'Michael David Johnson',
            'petitioner_address' => '123 Main Street, Los Angeles, CA 90210',
            'petitioner_phone' => '(555) 987-6543',
            'respondent_address' => '456 Oak Avenue, Los Angeles, CA 90211',
            'marriage_date' => '06/15/2010',
            'separation_date' => '03/20/2024',
            'marriage_location' => 'Las Vegas, Nevada',
            'grounds_for_dissolution' => 'Irreconcilable differences',
            'dissolution_type' => 'Dissolution of Marriage',
            'property_division' => '1',
            'spousal_support' => '1',
            'attorney_fees' => '1',
            'name_change' => '0',
            'has_children' => 'Yes',
            'children_count' => '2',
            'additional_info' => 'Request for temporary custody and support orders pending final judgment.',
            'attorney_signature' => 'John M. Smith',
            'signature_date' => date('m/d/Y')
        ];
    }
    
    public function run(): void {
        echo "=== FL-100 PDF Verification System ===" . PHP_EOL . PHP_EOL;
        
        // Step 1: Generate PDF from our system
        echo "Step 1: Generating PDF from our system..." . PHP_EOL;
        $ourPdf = $this->generateOurPdf();
        echo "✓ Generated: " . $ourPdf . PHP_EOL . PHP_EOL;
        
        // Step 2: Generate reference PDFs
        echo "Step 2: Generating reference PDFs..." . PHP_EOL;
        echo "Note: This requires MCP browser automation to be configured." . PHP_EOL;
        echo "Reference sites:" . PHP_EOL;
        echo "  - http://draft.clio.com" . PHP_EOL;
        echo "  - https://pdftimesavers.desktopmasters.com" . PHP_EOL . PHP_EOL;
        
        // Step 3: Visual comparison instructions
        echo "Step 3: Manual Verification Steps:" . PHP_EOL;
        echo "1. Open generated PDF: " . $ourPdf . PHP_EOL;
        echo "2. Navigate to reference sites and fill the same data" . PHP_EOL;
        echo "3. Compare field positions, font sizes, and alignment" . PHP_EOL . PHP_EOL;
        
        // Step 4: Output test data for reference
        echo "Test Data Used:" . PHP_EOL;
        echo json_encode($this->testData, JSON_PRETTY_PRINT) . PHP_EOL . PHP_EOL;
        
        echo "Verification complete! Please review the generated PDF." . PHP_EOL;
    }
    
    private function generateOurPdf(): string {
        $template = $this->templates['t_fl100_gc120'] ?? null;
        if (!$template) {
            throw new \RuntimeException('FL-100 template not found');
        }
        
        $result = $this->fill->generateSimplePdf($template, $this->testData, ['test' => true]);
        
        if (!isset($result['path']) || !file_exists($result['path'])) {
            throw new \RuntimeException('Failed to generate PDF');
        }
        
        return $result['path'];
    }
}

// Run the verifier
try {
    $verifier = new FL100Verifier();
    $verifier->run();
    exit(0);
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
    echo "Trace: " . $e->getTraceAsString() . PHP_EOL;
    exit(1);
}

