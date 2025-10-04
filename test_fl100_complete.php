<?php
/**
 * Complete FL-100 Test - Verifies positions and background are correct
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
echo "╔════════════════════════════════════════════╗\n";
echo "║        FL-100 COMPLETE TEST SUITE         ║\n";
echo "╚════════════════════════════════════════════╝\n";
echo "\n";

// Configuration
$uploadsDir = __DIR__ . '/uploads';
$outputDir = __DIR__ . '/output';
$dataDir = __DIR__ . '/data';
$logsDir = __DIR__ . '/logs';

// Ensure directories exist
foreach ([$outputDir, $logsDir] as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

// Step 1: Verify FL-100 Template
echo "STEP 1: Verifying FL-100 Template\n";
echo "──────────────────────────────────\n";

$fl100Path = $uploadsDir . '/fl100.pdf';
if (!file_exists($fl100Path)) {
    echo "❌ FL-100 template not found at: $fl100Path\n";
    echo "   Please place fl100.pdf in the uploads directory\n";
    exit(1);
}

echo "✅ FL-100 template found\n";

// Check template properties
try {
    $pdfCheck = new Fpdi();
    $pageCount = $pdfCheck->setSourceFile($fl100Path);
    $fileSize = filesize($fl100Path);
    
    echo "   📄 Pages: $pageCount\n";
    echo "   📦 Size: " . number_format($fileSize) . " bytes\n";
    
    if ($pageCount < 4) {
        echo "   ⚠️  WARNING: FL-100 should have 4 pages\n";
    }
    
    // Check each page dimension
    for ($i = 1; $i <= min($pageCount, 4); $i++) {
        $tplId = $pdfCheck->importPage($i);
        $size = $pdfCheck->getTemplateSize($tplId);
        printf("   Page %d: %.0f x %.0f mm\n", $i, $size['width'], $size['height']);
    }
} catch (Exception $e) {
    echo "❌ Error reading FL-100: " . $e->getMessage() . "\n";
}

echo "\n";

// Step 2: Verify Positions
echo "STEP 2: Verifying Field Positions\n";
echo "──────────────────────────────────\n";

$loader = new FieldPositionLoader($dataDir);
$positions = $loader->loadFieldPositions('t_fl100_gc120');

if (empty($positions)) {
    echo "❌ No field positions defined\n";
    exit(1);
}

echo "✅ " . count($positions) . " field positions loaded\n";

// Count by page
$pageCount = [];
$typeCount = [];
foreach ($positions as $field => $info) {
    $page = $info['page'] ?? 1;
    $type = $info['type'] ?? 'text';
    $pageCount[$page] = ($pageCount[$page] ?? 0) + 1;
    $typeCount[$type] = ($typeCount[$type] ?? 0) + 1;
}

echo "   Distribution by page:\n";
for ($i = 1; $i <= 4; $i++) {
    echo "     Page $i: " . ($pageCount[$i] ?? 0) . " fields\n";
}

echo "   Field types:\n";
foreach ($typeCount as $type => $count) {
    echo "     • $type: $count\n";
}

echo "\n";

// Step 3: Generate Test PDF with All Data
echo "STEP 3: Generating Complete Test PDF\n";
echo "──────────────────────────────────\n";

// Complete test data for all fields
$testData = [
    // Attorney Information (Page 1, top)
    'attorney_name' => 'JOHN MICHAEL SMITH',
    'attorney_bar_number' => '123456',
    'attorney_firm' => 'SMITH & ASSOCIATES LAW OFFICES',
    'attorney_address' => '500 NORTH BRAND BOULEVARD, SUITE 1500',
    'attorney_city' => 'GLENDALE',
    'attorney_state' => 'CA',
    'attorney_zip' => '91203',
    'attorney_phone' => '(818) 555-1234',
    'attorney_fax' => '(818) 555-1235',
    'attorney_email' => 'jsmith@smithlaw.com',
    'attorney_for' => 'Petitioner',
    
    // Court Information (Page 1, middle)
    'court_name' => 'SUPERIOR COURT OF CALIFORNIA',
    'court_county' => 'LOS ANGELES',
    'court_street' => '111 North Hill Street',
    'court_mailing' => '111 North Hill Street',
    'court_city_zip' => 'Los Angeles, CA 90012',
    'court_branch' => 'Stanley Mosk Courthouse',
    
    // Case Information (Page 1, top right)
    'case_number' => 'BD-2025-001234',
    
    // Parties (Page 1, middle)
    'petitioner_name' => 'JANE MARIE DOE',
    'petitioner_first_name' => 'JANE',
    'petitioner_last_name' => 'DOE',
    'respondent_name' => 'ROBERT JAMES JOHNSON',
    'respondent_first_name' => 'ROBERT',
    'respondent_last_name' => 'JOHNSON',
    
    // Petition Type (Page 1, checkboxes)
    'petition_dissolution' => 'checked',
    'dissolution_marriage' => 'checked',
    
    // Children Information (Page 1, bottom)
    'minor_children' => '',
    'children_from_relationship' => 'checked',
    'child1_name' => 'EMMA ROSE JOHNSON',
    'child1_birthdate' => '03/15/2015',
    'child1_age' => '9',
    'child1_sex' => 'F',
    
    // Additional test data for other pages
    'date_signed' => date('m/d/Y'),
    'signature' => '/s/ Jane M. Doe',
];

try {
    $logger = new Logger();
    $formFiller = new PdfFormFiller($outputDir, $uploadsDir, $logger);
    
    $template = [
        'id' => 't_fl100_gc120',
        'name' => 'FL-100 Complete Test'
    ];
    
    echo "Generating PDF with " . count($testData) . " fields...\n";
    
    $result = $formFiller->fillPdfFormWithPositions($template, $testData, 't_fl100_gc120');
    
    if ($result['success'] ?? false) {
        echo "✅ PDF generated successfully!\n";
        echo "   📁 File: " . ($result['file'] ?? 'unknown') . "\n";
        echo "   📄 Pages: " . ($result['pages'] ?? '?') . "\n";
        echo "   📦 Size: " . number_format($result['size'] ?? 0) . " bytes\n";
        echo "   ✏️  Fields filled: " . ($result['used_positions'] ?? 0) . "\n";
        echo "   📍 Path: " . ($result['path'] ?? 'unknown') . "\n";
        
        $outputFile = $result['path'] ?? '';
        
        // Verify the output
        if (file_exists($outputFile)) {
            try {
                $verifyPdf = new Fpdi();
                $verifyPages = $verifyPdf->setSourceFile($outputFile);
                echo "\n   Verification:\n";
                echo "   ✅ Output PDF is valid\n";
                echo "   ✅ Has $verifyPages pages\n";
                
                if ($verifyPages != 4) {
                    echo "   ⚠️  Expected 4 pages but got $verifyPages\n";
                }
            } catch (Exception $e) {
                echo "   ❌ Cannot verify output: " . $e->getMessage() . "\n";
            }
        }
    } else {
        echo "❌ PDF generation failed\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "   " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n";

// Step 4: Check Debug Log
echo "STEP 4: Checking Debug Log\n";
echo "──────────────────────────────────\n";

$logFile = $logsDir . '/pdf_debug.log';
if (file_exists($logFile)) {
    $logLines = file($logFile);
    $recentLines = array_slice($logLines, -20);
    
    echo "📝 Recent log entries:\n";
    foreach ($recentLines as $line) {
        $line = trim($line);
        if (strpos($line, 'ERROR') !== false || strpos($line, 'error') !== false) {
            echo "   ❌ $line\n";
        } elseif (strpos($line, 'WARNING') !== false || strpos($line, 'WARN') !== false) {
            echo "   ⚠️  $line\n";
        } elseif (strpos($line, 'Page') !== false || strpos($line, 'Field') !== false) {
            echo "   📄 $line\n";
        } elseif (strpos($line, 'PDF created') !== false || strpos($line, 'success') !== false) {
            echo "   ✅ $line\n";
        }
    }
} else {
    echo "📝 No debug log found\n";
}

echo "\n";

// Summary
echo "╔════════════════════════════════════════════╗\n";
echo "║                  SUMMARY                   ║\n";
echo "╚════════════════════════════════════════════╝\n";
echo "\n";

$issues = [];
$success = [];

// Check critical components
if (file_exists($fl100Path)) {
    $success[] = "FL-100 template present";
} else {
    $issues[] = "FL-100 template missing";
}

if (!empty($positions)) {
    $success[] = count($positions) . " field positions defined";
} else {
    $issues[] = "No field positions";
}

if (isset($result['success']) && $result['success']) {
    $success[] = "PDF generation working";
} else {
    $issues[] = "PDF generation failed";
}

if (isset($result['pages']) && $result['pages'] == 4) {
    $success[] = "4-page PDF created";
} elseif (isset($result['pages'])) {
    $issues[] = "PDF has " . $result['pages'] . " pages instead of 4";
}

echo "✅ Working:\n";
foreach ($success as $item) {
    echo "   • $item\n";
}

if (!empty($issues)) {
    echo "\n❌ Issues:\n";
    foreach ($issues as $item) {
        echo "   • $item\n";
    }
}

echo "\n";
echo "📋 Next Steps:\n";
echo "   1. Review the generated PDF in: $outputDir\n";
echo "   2. Check if field positions are correct\n";
echo "   3. Use adjust_positions.php to fine-tune if needed\n";
echo "   4. Run visual_inspect_pdf.php for detailed analysis\n";

echo "\n✨ Test complete!\n\n";