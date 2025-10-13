<?php
/**
 * Unlock a password-protected PDF using pdftk
 * Usage: php scripts/unlock-pdf.php <input.pdf> <output.pdf> [password]
 * Example: php scripts/unlock-pdf.php uploads/fl100.pdf uploads/fl100_unlocked.pdf
 */

declare(strict_types=1);

// Check arguments
if ($argc < 3) {
    echo "Usage: php scripts/unlock-pdf.php <input.pdf> <output.pdf> [password]\n";
    echo "Example: php scripts/unlock-pdf.php uploads/fl100.pdf uploads/fl100_unlocked.pdf\n";
    echo "\nIf password is not provided, will attempt to unlock without password.\n";
    exit(1);
}

$inputFile = $argv[1];
$outputFile = $argv[2];
$password = $argv[3] ?? '';

if (!file_exists($inputFile)) {
    echo "Error: Input PDF file not found: $inputFile\n";
    exit(1);
}

// Find pdftk
$pdftk = null;
$candidates = [
    'pdftk',
    __DIR__ . '/../pdftk_installer.exe',
    __DIR__ . '/../pdftk.exe',
    'C:/Program Files/PDFtk/bin/pdftk.exe',
    'C:/Program Files (x86)/PDFtk/bin/pdftk.exe'
];

foreach ($candidates as $binary) {
    if (file_exists($binary)) {
        $pdftk = $binary;
        break;
    }
    
    // Check if it's in PATH
    $output = [];
    $returnCode = 0;
    exec("where $binary 2>&1", $output, $returnCode);
    if ($returnCode === 0 && !empty($output[0])) {
        $pdftk = trim($output[0]);
        break;
    }
}

if (!$pdftk) {
    echo "Error: pdftk not found!\n";
    echo "\nPlease install pdftk:\n";
    echo "  - Run pdftk_installer.exe from the project root\n";
    echo "  - Or download from: https://www.pdflabs.com/tools/pdftk-the-pdf-toolkit/\n";
    exit(1);
}

echo "Using pdftk: $pdftk\n";
echo "Input: $inputFile\n";
echo "Output: $outputFile\n";

if ($password) {
    echo "Using provided password\n";
} else {
    echo "Attempting to unlock without password\n";
}

echo str_repeat("-", 60) . "\n";

// Build pdftk command
if ($password) {
    $cmd = "\"{$pdftk}\" \"" . realpath($inputFile) . "\" input_pw \"$password\" output \"$outputFile\" 2>&1";
} else {
    // Try without password (sometimes PDFs have empty passwords)
    $cmd = "\"{$pdftk}\" \"" . realpath($inputFile) . "\" output \"$outputFile\" 2>&1";
}

$output = [];
$returnCode = 0;
exec($cmd, $output, $returnCode);

if ($returnCode === 0 && file_exists($outputFile)) {
    echo "✓ PDF unlocked successfully!\n";
    echo "  Output: $outputFile\n";
    echo "\nYou can now extract fields from the unlocked PDF:\n";
    echo "  php scripts/extract-pdf-fields.php $outputFile t_your_template_id\n";
} else {
    echo "✗ Failed to unlock PDF\n";
    echo "\npdftk output:\n";
    echo implode("\n", $output) . "\n";
    echo "\nPossible solutions:\n";
    echo "  1. The PDF might require a password - try providing it as the 3rd argument\n";
    echo "  2. Use online tools to remove password:\n";
    echo "     - https://www.ilovepdf.com/unlock_pdf\n";
    echo "     - https://smallpdf.com/unlock-pdf\n";
    echo "  3. The PDF might not be password-protected at all\n";
    exit(1);
}

