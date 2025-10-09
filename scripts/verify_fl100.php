#!/usr/bin/env php
<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../mvp/templates/registry.php';
require_once __DIR__ . '/../mvp/lib/fill_service.php';
require_once __DIR__ . '/../mvp/lib/fl100_test_data_generator.php';

use WebPdfTimeSaver\Mvp\TemplateRegistry;
use WebPdfTimeSaver\Mvp\FillService;
use WebPdfTimeSaver\Mvp\FL100TestDataGenerator;
use setasign\Fpdi\Fpdi;

function findGhostscriptBinary(): ?string {
    $candidates = [ 'gs', 'gswin64c', 'gswin32c' ];
    foreach ($candidates as $bin) {
        $cmd = $bin . ' -v 2>&1';
        $out = [];
        $rc = 0;
        @exec($cmd, $out, $rc);
        if ($rc === 0) { return $bin; }
    }
    return null;
}

function ensureDir(string $dir): void { if (!is_dir($dir)) { @mkdir($dir, 0777, true); } }

// Allow passing an existing PDF via --pdf=/abs/path.pdf to rasterize only
$cliPdf = null;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--pdf=')) {
        $cliPdf = substr($arg, 6);
        break;
    }
}

if ($cliPdf !== null) {
    $path = $cliPdf;
    if (!file_exists($path)) { fwrite(STDERR, "Provided --pdf file not found: {$path}\n"); exit(1); }
} else {
    $templates = TemplateRegistry::load();
    $tpl = $templates['t_fl100_gc120'] ?? null;
    if (!$tpl) { fwrite(STDERR, "FL-100 template not found.\n"); exit(1); }

    $service = new FillService(__DIR__ . '/../output');
    $testData = FL100TestDataGenerator::generateCompleteTestData();
    $result = $service->generateSimplePdf($tpl, $testData);
    $path = $result['path'] ?? null;
    if (!$path || !file_exists($path)) { fwrite(STDERR, "PDF generation failed or file missing.\n"); exit(1); }
}

$probe = new Fpdi();
$pages = $probe->setSourceFile($path);

$shotsDir = __DIR__ . '/../output/screenshots';
ensureDir($shotsDir);

$gs = findGhostscriptBinary();
if ($gs === null) {
    fwrite(STDOUT, "Ghostscript not found; skipping rasterization.\n");
    fwrite(STDOUT, json_encode([ 'pdf' => $path, 'pages' => $pages, 'screenshots' => [] ], JSON_PRETTY_PRINT) . "\n");
    exit(0);
}

$screens = [];
for ($i = 1; $i <= (int)$pages; $i++) {
    $png = $shotsDir . '/fl100_' . date('Ymd_His') . '_p' . $i . '.png';
    $cmd = sprintf('%s -dSAFER -dNOPAUSE -dBATCH -sDEVICE=png16m -r200 -dFirstPage=%d -dLastPage=%d -sOutputFile="%s" "%s" 2>&1',
        $gs,
        $i,
        $i,
        $png,
        $path
    );
    $out = [];
    $rc = 0;
    exec($cmd, $out, $rc);
    if ($rc !== 0 || !file_exists($png)) {
        fwrite(STDERR, "Rasterization failed for page {$i}: " . implode(' ', $out) . "\n");
        continue;
    }
    $screens[] = $png;
}

fwrite(STDOUT, json_encode([ 'pdf' => $path, 'pages' => $pages, 'screenshots' => $screens ], JSON_PRETTY_PRINT) . "\n");
