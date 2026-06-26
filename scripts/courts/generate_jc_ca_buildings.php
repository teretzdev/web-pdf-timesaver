<?php
declare(strict_types=1);
/**
 * Generate data/jc_ca_buildings.txt from the Judicial Council Regional Building List PDF.
 *
 * Usage:
 *   python scripts/courts/generate_jc_ca_buildings.py --from-text=path/to/pdf_extract.txt
 *   php scripts/courts/generate_jc_ca_buildings.php --from-text=path/to/pdf_extract.txt
 *
 * Source PDF:
 *   https://courts.ca.gov/system/files/solicitation-request-document/rfp-fs-sp-2019-03-jp-attachment-2-regional-building-list-addendum-1.pdf
 */
require_once __DIR__ . '/lib/jc_ca_extract.php';
require_once __DIR__ . '/lib/jc_ca_parser.php';

$root = dirname(__DIR__, 2);
$outPath = $root . '/data/jc_ca_buildings.txt';
$fromText = '';
$args = array_values(array_slice($argv ?? [], 1));
foreach ($args as $idx => $arg) {
    if (str_starts_with($arg, '--from-text=')) {
        $fromText = substr($arg, 11);
    } elseif ($arg === '--from-text') {
        $fromText = (string)($args[$idx + 1] ?? '');
    }
}

$text = '';
if ($fromText !== '' && is_file($fromText)) {
    $text = (string)file_get_contents($fromText);
    fwrite(STDOUT, "Using local text: $fromText\n");
} else {
    fwrite(STDOUT, 'Fetching PDF from courts.ca.gov…' . "\n");
    $text = jc_ca_fetch_pdf_text();
}

if (trim($text) === '') {
    fwrite(STDERR, "Could not extract PDF text. Install pdftotext (poppler) or pass --from-text=path\n");
    exit(1);
}

$rows = jc_ca_extract_from_text($text);
if ($rows === []) {
    fwrite(STDERR, "No building rows extracted.\n");
    exit(1);
}

$content = jc_ca_rows_to_pipe_file($rows);
if (@file_put_contents($outPath, $content) === false) {
    fwrite(STDERR, "Failed to write $outPath\n");
    exit(1);
}

$parsed = jc_ca_parse_buildings_file($outPath);
fwrite(STDOUT, 'Extracted ' . count($rows) . ' row(s), parser validated ' . count($parsed) . " location(s).\n");
fwrite(STDOUT, "Wrote $outPath\n");
