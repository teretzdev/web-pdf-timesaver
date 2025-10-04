<?php
/**
 * Verify FL-100 Setup - Ensures everything is configured correctly
 */

echo "========================================\n";
echo "    FL-100 Setup Verification\n";
echo "========================================\n\n";

$uploadsDir = __DIR__ . '/../uploads';
$outputDir = __DIR__ . '/../output';
$dataDir = __DIR__ . '/../data';
$logsDir = __DIR__ . '/../logs';

// 1. Check directories
echo "📁 Checking directories...\n";
$dirs = [
    'uploads' => $uploadsDir,
    'output' => $outputDir,
    'data' => $dataDir,
    'logs' => $logsDir
];

foreach ($dirs as $name => $path) {
    if (is_dir($path)) {
        echo "   ✅ $name/ exists\n";
    } else {
        echo "   ❌ $name/ missing - creating...\n";
        mkdir($path, 0777, true);
        if (is_dir($path)) {
            echo "      ✅ Created successfully\n";
        }
    }
}

// 2. Check FL-100 template
echo "\n📄 Checking FL-100 template...\n";
$fl100Path = $uploadsDir . '/fl100.pdf';

if (file_exists($fl100Path)) {
    $size = filesize($fl100Path);
    echo "   ✅ FL-100 found: " . number_format($size) . " bytes\n";
    
    // Check if we can read it with FPDI
    try {
        require_once __DIR__ . '/../vendor/autoload.php';
        $pdf = new \setasign\Fpdi\Fpdi();
        $pageCount = $pdf->setSourceFile($fl100Path);
        echo "   ✅ FL-100 is readable: $pageCount pages\n";
        
        if ($pageCount < 4) {
            echo "   ⚠️  WARNING: FL-100 should have 4 pages but has $pageCount\n";
        } elseif ($pageCount == 4) {
            echo "   ✅ FL-100 has correct number of pages (4)\n";
        }
        
        // Check page sizes
        echo "\n   Page dimensions:\n";
        for ($i = 1; $i <= min(4, $pageCount); $i++) {
            $tplId = $pdf->importPage($i);
            $size = $pdf->getTemplateSize($tplId);
            printf("     Page %d: %.1f x %.1f mm (%s)\n", 
                $i, 
                $size['width'], 
                $size['height'],
                $size['orientation'] ?? 'P'
            );
        }
        
    } catch (Exception $e) {
        echo "   ❌ Cannot read FL-100: " . $e->getMessage() . "\n";
        echo "   💡 The PDF might be encrypted. Try using an unencrypted version.\n";
    }
} else {
    echo "   ❌ FL-100 not found at: $fl100Path\n";
    echo "   📥 Please download FL-100 from:\n";
    echo "      https://www.courts.ca.gov/documents/fl100.pdf\n";
    echo "   And save it as: $fl100Path\n";
    
    // Try to download it
    echo "\n   Attempting to download FL-100...\n";
    $url = 'https://www.courts.ca.gov/documents/fl100.pdf';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $pdfContent = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 200 && $pdfContent) {
        file_put_contents($fl100Path, $pdfContent);
        echo "      ✅ Downloaded FL-100 successfully\n";
    } else {
        echo "      ❌ Could not download FL-100 (HTTP $httpCode)\n";
    }
}

// 3. Check positions file
echo "\n📍 Checking field positions...\n";
$positionsFile = $dataDir . '/t_fl100_gc120_positions.json';

if (file_exists($positionsFile)) {
    $positions = json_decode(file_get_contents($positionsFile), true);
    echo "   ✅ Positions file exists: " . count($positions) . " fields defined\n";
    
    // Check pages
    $pages = [];
    foreach ($positions as $field => $info) {
        $page = $info['page'] ?? 1;
        $pages[$page] = ($pages[$page] ?? 0) + 1;
    }
    
    echo "   Field distribution by page:\n";
    for ($i = 1; $i <= 4; $i++) {
        $count = $pages[$i] ?? 0;
        echo "     Page $i: $count fields\n";
    }
    
    if (count($pages) < 2) {
        echo "   ⚠️  Most fields are on page 1. Consider adding fields for other pages.\n";
    }
    
} else {
    echo "   ❌ Positions file not found\n";
    echo "   Run: php scripts/update_pdf_positions.php generate\n";
}

// 4. Check logs
echo "\n📝 Checking logs...\n";
$logFile = $logsDir . '/pdf_debug.log';
if (file_exists($logFile)) {
    $size = filesize($logFile);
    $lines = count(file($logFile));
    echo "   ✅ Debug log exists: " . number_format($size) . " bytes, $lines lines\n";
    
    // Show last few lines
    $lastLines = array_slice(file($logFile), -5);
    if (!empty($lastLines)) {
        echo "   Last 5 log entries:\n";
        foreach ($lastLines as $line) {
            echo "     " . trim($line) . "\n";
        }
    }
} else {
    echo "   📝 No debug log yet (will be created on first use)\n";
}

// 5. Test PDF generation
echo "\n🧪 Testing PDF generation...\n";

try {
    require_once __DIR__ . '/../mvp/lib/pdf_form_filler.php';
    require_once __DIR__ . '/../mvp/lib/field_position_loader.php';
    require_once __DIR__ . '/../mvp/lib/logger.php';
    
    $testData = [
        'case_number' => 'TEST-2025',
        'attorney_name' => 'Test Attorney',
        'petitioner_name' => 'Test Petitioner',
        'respondent_name' => 'Test Respondent',
    ];
    
    $template = ['id' => 't_fl100_gc120', 'name' => 'Test'];
    
    $logger = new \WebPdfTimeSaver\Mvp\Logger();
    $formFiller = new \WebPdfTimeSaver\Mvp\PdfFormFiller($outputDir, $uploadsDir, $logger);
    
    echo "   Generating test PDF...\n";
    $result = $formFiller->fillPdfFormWithPositions($template, $testData, 't_fl100_gc120');
    
    if ($result['success'] ?? false) {
        echo "   ✅ Test PDF generated successfully\n";
        echo "      File: " . ($result['file'] ?? 'unknown') . "\n";
        echo "      Pages: " . ($result['pages'] ?? '?') . "\n";
        echo "      Size: " . number_format($result['size'] ?? 0) . " bytes\n";
        echo "      Fields used: " . ($result['used_positions'] ?? 0) . "\n";
    } else {
        echo "   ❌ Test PDF generation failed\n";
    }
    
} catch (Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// Summary
echo "\n========================================\n";
echo "📊 Summary\n";
echo "========================================\n";

$issues = [];
$warnings = [];
$success = [];

// Check critical items
if (!file_exists($fl100Path)) {
    $issues[] = "FL-100 template missing";
} else {
    $success[] = "FL-100 template present";
}

if (!file_exists($positionsFile)) {
    $issues[] = "Position definitions missing";
} else {
    $success[] = "Field positions defined";
}

if (!empty($success)) {
    echo "\n✅ Working:\n";
    foreach ($success as $item) {
        echo "   • $item\n";
    }
}

if (!empty($warnings)) {
    echo "\n⚠️  Warnings:\n";
    foreach ($warnings as $item) {
        echo "   • $item\n";
    }
}

if (!empty($issues)) {
    echo "\n❌ Issues to fix:\n";
    foreach ($issues as $item) {
        echo "   • $item\n";
    }
    
    echo "\n📋 Next steps:\n";
    if (!file_exists($fl100Path)) {
        echo "   1. Get FL-100.pdf and place in $uploadsDir/\n";
    }
    if (!file_exists($positionsFile)) {
        echo "   2. Run: php scripts/update_pdf_positions.php generate\n";
    }
} else {
    echo "\n✨ Everything is set up correctly!\n";
    echo "\nYou can now:\n";
    echo "   • Run visual inspection: php scripts/visual_inspect_pdf.php\n";
    echo "   • Adjust positions: php scripts/adjust_positions.php\n";
    echo "   • Test PDF generation: php scripts/test_pdf_positions.php\n";
}

echo "\n";