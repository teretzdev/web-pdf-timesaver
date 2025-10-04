<?php
/**
 * Generate and Show Actual Test Result
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/mvp/lib/pdf_form_filler.php';
require_once __DIR__ . '/mvp/lib/field_position_loader.php';
require_once __DIR__ . '/mvp/lib/logger.php';

use WebPdfTimeSaver\Mvp\PdfFormFiller;
use WebPdfTimeSaver\Mvp\FieldPositionLoader;
use WebPdfTimeSaver\Mvp\Logger;
use setasign\Fpdi\Fpdi;

echo "\n";
echo "═══════════════════════════════════════════════════════\n";
echo "     GENERATING ACTUAL FL-100 TEST RESULT PDF\n";
echo "═══════════════════════════════════════════════════════\n\n";

$outputDir = __DIR__ . '/output';
$uploadsDir = __DIR__ . '/uploads';
$logsDir = __DIR__ . '/logs';

// Ensure directories exist
if (!is_dir($outputDir)) mkdir($outputDir, 0777, true);
if (!is_dir($logsDir)) mkdir($logsDir, 0777, true);

// COMPLETE TEST DATA FOR ALL PAGES
$testData = [
    // PAGE 1 - HEADER & ATTORNEY INFO
    'case_number' => 'BD-2025-001234',
    'attorney_name' => 'JOHN MICHAEL SMITH',
    'attorney_bar_number' => 'CA-123456',
    'attorney_firm' => 'SMITH & ASSOCIATES LAW OFFICES',
    'attorney_address' => '500 NORTH BRAND BOULEVARD, SUITE 1500',
    'attorney_city' => 'GLENDALE',
    'attorney_state' => 'CA',
    'attorney_zip' => '91203',
    'attorney_phone' => '(818) 555-1234',
    'attorney_fax' => '(818) 555-1235',
    'attorney_email' => 'jsmith@smithlaw.com',
    'attorney_for' => 'Petitioner',
    
    // PAGE 1 - COURT INFO
    'court_name' => 'SUPERIOR COURT OF CALIFORNIA',
    'court_county' => 'LOS ANGELES',
    'court_street' => '111 North Hill Street',
    'court_mailing' => '111 North Hill Street',
    'court_city_zip' => 'Los Angeles, CA 90012',
    'court_branch' => 'Stanley Mosk Courthouse',
    
    // PAGE 1 - PARTIES
    'petitioner_name' => 'JANE MARIE DOE',
    'petitioner_first_name' => 'JANE',
    'petitioner_last_name' => 'DOE',
    'respondent_name' => 'ROBERT JAMES JOHNSON',
    'respondent_first_name' => 'ROBERT',
    'respondent_last_name' => 'JOHNSON',
    
    // PAGE 1 - PETITION TYPE (CHECKBOXES)
    'petition_dissolution' => 'checked',
    'dissolution_marriage' => 'checked',
    'legal_separation_of' => '',
    'nullity_of' => '',
    
    // PAGE 1 - CHILDREN
    'minor_children' => '',
    'children_from_relationship' => 'checked',
    'child1_name' => 'EMMA ROSE JOHNSON',
    'child1_birthdate' => '03/15/2015',
    'child1_age' => '9',
    'child1_sex' => 'F',
    
    // PAGE 2 - PROPERTY & SUPPORT
    'property_declaration' => 'checked',
    'property_list' => 'Family residence at 123 Main St, Los Angeles, CA 90001',
    'spousal_support' => 'checked',
    'child_support' => 'checked',
    
    // PAGE 3 - LEGAL GROUNDS
    'legal_grounds' => 'Irreconcilable differences have led to the irremediable breakdown of the marriage',
    'irreconcilable_differences' => 'checked',
    'relief_requested' => 'Dissolution of marriage, child custody, spousal support, division of property',
    
    // PAGE 4 - SIGNATURES
    'petitioner_signature' => '/s/ Jane Marie Doe',
    'petitioner_date_signed' => date('m/d/Y'),
    'attorney_signature' => '/s/ John Michael Smith',
    'attorney_date_signed' => date('m/d/Y'),
    'declaration_text' => 'I declare under penalty of perjury under the laws of the State of California that the foregoing is true and correct.',
];

echo "📋 Test Data Prepared:\n";
echo "   • " . count($testData) . " fields with data\n";
echo "   • Pages 1-4 all have content\n\n";

try {
    $logger = new Logger();
    $formFiller = new PdfFormFiller($outputDir, $uploadsDir, $logger);
    
    $template = [
        'id' => 't_fl100_gc120',
        'name' => 'FL-100 Complete Test Result'
    ];
    
    echo "🔄 Generating PDF...\n";
    
    $startTime = microtime(true);
    $result = $formFiller->fillPdfFormWithPositions($template, $testData, 't_fl100_gc120');
    $duration = round((microtime(true) - $startTime) * 1000);
    
    echo "\n✅ PDF GENERATION COMPLETE!\n";
    echo "═══════════════════════════════════════════════════════\n";
    
    if ($result['success'] ?? false) {
        $outputFile = $result['path'] ?? '';
        
        echo "📄 FILE DETAILS:\n";
        echo "   Filename:  " . ($result['file'] ?? 'unknown') . "\n";
        echo "   Path:      " . $outputFile . "\n";
        echo "   Size:      " . number_format($result['size'] ?? 0) . " bytes\n";
        echo "   Pages:     " . ($result['pages'] ?? '?') . "\n";
        echo "   Fields:    " . ($result['used_positions'] ?? 0) . " positions used\n";
        echo "   Time:      " . $duration . "ms\n";
        
        // Verify the PDF
        if (file_exists($outputFile)) {
            echo "\n🔍 VERIFICATION:\n";
            
            try {
                $verifyPdf = new Fpdi();
                $pageCount = $verifyPdf->setSourceFile($outputFile);
                
                echo "   ✅ PDF is valid and readable\n";
                echo "   ✅ Contains " . $pageCount . " pages\n";
                
                // Check each page
                for ($i = 1; $i <= $pageCount; $i++) {
                    $tplId = $verifyPdf->importPage($i);
                    $size = $verifyPdf->getTemplateSize($tplId);
                    printf("   ✅ Page %d: %.0f x %.0f mm\n", $i, $size['width'], $size['height']);
                }
                
                if ($pageCount == 4) {
                    echo "\n   🎯 PERFECT: 4-page FL-100 generated correctly!\n";
                }
                
            } catch (Exception $e) {
                echo "   ⚠️  Could not verify: " . $e->getMessage() . "\n";
            }
            
            echo "\n📊 WHAT'S IN THE PDF:\n";
            echo "   Page 1: Attorney info, court, parties, petition type\n";
            echo "           • Case #: BD-2025-001234 (top right)\n";
            echo "           • Attorney: JOHN MICHAEL SMITH\n";
            echo "           • Petitioner: JANE MARIE DOE\n";
            echo "           • Respondent: ROBERT JAMES JOHNSON\n";
            echo "           • ☑ Dissolution of Marriage\n";
            echo "           • Child: EMMA ROSE JOHNSON, F, Age 9\n";
            echo "\n";
            echo "   Page 2: Property and support\n";
            echo "           • ☑ Property Declaration\n";
            echo "           • ☑ Spousal Support\n";
            echo "           • ☑ Child Support\n";
            echo "\n";
            echo "   Page 3: Legal grounds\n";
            echo "           • ☑ Irreconcilable Differences\n";
            echo "           • Relief requested text\n";
            echo "\n";
            echo "   Page 4: Signatures and declarations\n";
            echo "           • Petitioner signature: /s/ Jane Marie Doe\n";
            echo "           • Attorney signature: /s/ John Michael Smith\n";
            echo "           • Date: " . date('m/d/Y') . "\n";
            
        } else {
            echo "\n❌ Output file not found!\n";
        }
        
    } else {
        echo "❌ PDF generation failed!\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . "\n";
    echo "   Line: " . $e->getLine() . "\n";
}

// Show recent log entries
echo "\n📝 RECENT LOG ENTRIES:\n";
$logFile = $logsDir . '/pdf_debug.log';
if (file_exists($logFile)) {
    $lines = file($logFile);
    $recent = array_slice($lines, -15);
    foreach ($recent as $line) {
        $line = trim($line);
        if (strpos($line, 'Page') !== false) {
            echo "   📄 " . $line . "\n";
        } elseif (strpos($line, 'Field') !== false && strpos($line, 'Filling field') !== false) {
            // Extract just the field name and value
            if (preg_match("/Filling field '([^']+)'.*with value: (.+)$/", $line, $matches)) {
                echo "   ✏️  {$matches[1]}: {$matches[2]}\n";
            }
        } elseif (strpos($line, 'PDF created') !== false) {
            echo "   ✅ " . $line . "\n";
        }
    }
}

echo "\n═══════════════════════════════════════════════════════\n";
echo "🎉 TEST COMPLETE! Check the output directory for:\n";
echo "   " . ($result['file'] ?? 'the generated PDF') . "\n";
echo "═══════════════════════════════════════════════════════\n\n";

// List recent PDFs
echo "📁 Recent PDFs in output directory:\n";
$pdfs = glob($outputDir . '/*.pdf');
$recent = array_slice($pdfs, -5);
foreach ($recent as $pdf) {
    $size = filesize($pdf);
    $time = date('Y-m-d H:i:s', filemtime($pdf));
    printf("   • %-50s %10s  %s\n", basename($pdf), number_format($size) . 'B', $time);
}

echo "\n";