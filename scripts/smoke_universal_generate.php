<?php
/**
 * CLI smoke test: mirror actions/universal-generate PDF build (no HTTP).
 * Usage: php scripts/smoke_universal_generate.php [template_id]
 */
declare(strict_types=1);

$root = dirname(__DIR__);
$templateId = $argv[1] ?? 't_fl100_gc120';

require_once $root . '/vendor/autoload.php';
require_once $root . '/mvp/lib/pdf_form_filler.php';
require_once $root . '/mvp/lib/logger.php';

$positionsFile = $root . '/data/' . $templateId . '_positions.json';
if (!is_readable($positionsFile)) {
    fwrite(STDERR, "Missing positions file: {$positionsFile}\n");
    exit(1);
}

$positions = json_decode((string) file_get_contents($positionsFile), true);
if (!is_array($positions) || $positions === []) {
    fwrite(STDERR, "Invalid or empty positions JSON\n");
    exit(1);
}

if (array_is_list($positions)) {
    fwrite(STDERR, "Positions are list format; universal-generate normalizes these — use a keyed map template for this smoke test.\n");
    exit(1);
}

$pageCount = 1;
foreach ($positions as $position) {
    if (!is_array($position)) {
        continue;
    }
    $fieldPage = (int) ($position['page'] ?? 1);
    if ($fieldPage > $pageCount) {
        $pageCount = $fieldPage;
    }
}

$logger = new \WebPdfTimeSaver\Mvp\Logger();
$filler = new \WebPdfTimeSaver\Mvp\PdfFormFiller($root . '/output', $root . '/uploads', $logger);
$filler->setContext(['test' => true, 'method' => 'smoke-universal-generate']);

$fillValues = [];
foreach (array_keys($positions) as $fieldKey) {
    if (!is_string($fieldKey) || trim($fieldKey) === '') {
        continue;
    }
    $fillValues[$fieldKey] = 'Smoke';
}

$template = [
    'id' => $templateId,
    'pageCount' => $pageCount,
];

$result = $filler->fillPdfFormWithPositions($template, $fillValues, $templateId);
if (($result['success'] ?? false) !== true || empty($result['filename'])) {
    fwrite(STDERR, 'fillPdfFormWithPositions failed: ' . json_encode($result, JSON_UNESCAPED_SLASHES) . "\n");
    exit(1);
}

$out = $root . '/output/' . $result['filename'];
if (!is_file($out)) {
    fwrite(STDERR, "Output PDF not found at {$out}\n");
    exit(1);
}

echo "OK template={$templateId} file=" . $result['filename'] . ' bytes=' . filesize($out) . "\n";
exit(0);
