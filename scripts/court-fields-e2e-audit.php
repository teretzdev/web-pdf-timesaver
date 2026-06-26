<?php
/**
 * Court Fields E2E audit — state vs federal search API + export PDF text checks.
 *
 * Usage:
 *   php scripts/court-fields-e2e-audit.php
 *   COURT_E2E_BASE=https://pdftimesaver.desktopmasters.com/mvp/ php scripts/court-fields-e2e-audit.php
 *
 * Optional env:
 *   COURT_E2E_STATE_PD=pd_1cd93067f643
 *   COURT_E2E_STATE_PROJECT=p_4eaab879384a
 *   COURT_E2E_FEDERAL_PD=<pd after federal project setup>
 *   COURT_E2E_FEDERAL_PROJECT=<project id>
 *   PDFBOX_JAR=path/to/pdfbox-app-3.0.1.jar
 */

declare(strict_types=1);

$base = rtrim((string)(getenv('COURT_E2E_BASE') ?: 'https://pdftimesaver.desktopmasters.com/mvp'), '/') . '/';
$pdfboxJar = (string)(getenv('PDFBOX_JAR') ?: dirname(__DIR__) . '/bin/pdfbox/pdfbox-app-3.0.1.jar');

$statePd = (string)(getenv('COURT_E2E_STATE_PD') ?: 'pd_1cd93067f643');
$stateProject = (string)(getenv('COURT_E2E_STATE_PROJECT') ?: 'p_4eaab879384a');
$fedPd = trim((string)(getenv('COURT_E2E_FEDERAL_PD') ?: 'pd_2d30c63dd4e9'));
$fedProject = trim((string)(getenv('COURT_E2E_FEDERAL_PROJECT') ?: 'p_f38c0bd04bf1'));

$passed = 0;
$failed = 0;
$skipped = 0;

function audit_line(string $status, string $name, string $detail = ''): void
{
    $icon = $status === 'PASS' ? '✓' : ($status === 'SKIP' ? '○' : '✗');
    $msg = "[$icon $status] $name";
    if ($detail !== '') {
        $msg .= " — $detail";
    }
    echo $msg . PHP_EOL;
}

function audit_assert(bool $ok, string $name, string $failDetail = ''): void
{
    global $passed, $failed;
    if ($ok) {
        $passed++;
        audit_line('PASS', $name);
        return;
    }
    $failed++;
    audit_line('FAIL', $name, $failDetail);
}

function audit_skip(string $name, string $reason): void
{
    global $skipped;
    $skipped++;
    audit_line('SKIP', $name, $reason);
}

function http_get_json(string $url): array
{
    $ctx = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "Accept: application/json\r\n",
            'timeout' => 45,
            'ignore_errors' => true,
        ],
    ]);
    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false) {
        return ['_error' => 'HTTP request failed'];
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : ['_error' => 'Invalid JSON', '_raw' => substr($raw, 0, 200)];
}

function http_download(string $url, string $dest): bool
{
    $ctx = stream_context_create(['http' => ['timeout' => 120, 'ignore_errors' => true]]);
    $raw = @file_get_contents($url, false, $ctx);
    if ($raw === false || strlen($raw) < 100) {
        return false;
    }
    if (strncmp($raw, '%PDF', 4) !== 0) {
        return false;
    }
    return file_put_contents($dest, $raw) !== false;
}

function pdf_to_text(string $pdfPath, string $txtPath, string $pdfboxJar): bool
{
    if (!is_file($pdfboxJar)) {
        return false;
    }
    $cmd = sprintf(
        'java -jar %s export:text -i %s -o %s 2>NUL',
        escapeshellarg($pdfboxJar),
        escapeshellarg($pdfPath),
        escapeshellarg($txtPath)
    );
    exec($cmd, $out, $code);
    return $code === 0 && is_file($txtPath) && filesize($txtPath) > 0;
}

function text_has_all(string $haystack, array $needles): array
{
    $missing = [];
    foreach ($needles as $n) {
        if ($n === '') {
            continue;
        }
        if (stripos($haystack, $n) === false) {
            $missing[] = $n;
        }
    }
    return $missing;
}

function text_has_none(string $haystack, array $forbidden): array
{
    $found = [];
    foreach ($forbidden as $n) {
        if ($n !== '' && stripos($haystack, $n) !== false) {
            $found[] = $n;
        }
    }
    return $found;
}

echo "Court Fields E2E Audit\n";
echo str_repeat('=', 60) . PHP_EOL;
echo "Base: $base\n\n";

// --- API: federal search ---
$fedSearch = http_get_json($base . '?route=api/courts/search&q=Central+District+California&limit=10&system=federal');
audit_assert(($fedSearch['success'] ?? false) === true, 'API federal search responds success');
$fedResults = is_array($fedSearch['results'] ?? null) ? $fedSearch['results'] : [];
audit_assert(count($fedResults) >= 1, 'API federal search returns ≥1 result', 'count=' . count($fedResults));

$allFederal = true;
$hasTemple = false;
foreach ($fedResults as $row) {
    if (!is_array($row)) {
        continue;
    }
    if (strtolower((string)($row['courtSystem'] ?? '')) !== 'federal') {
        $allFederal = false;
    }
    $blob = strtolower(implode(' ', [
        (string)($row['street'] ?? ''),
        (string)($row['courtName'] ?? ''),
    ]));
    if (strpos($blob, '255') !== false && strpos($blob, 'temple') !== false) {
        $hasTemple = true;
    }
}
audit_assert($allFederal, 'API federal results are all courtSystem=federal');
audit_assert($hasTemple, 'API federal search includes 255 E Temple Street division');

// --- API: state search ---
$stateSearch = http_get_json($base . '?route=api/courts/search&q=Stanley+Mosk&limit=10&system=state');
$stateResults = is_array($stateSearch['results'] ?? null) ? $stateSearch['results'] : [];
audit_assert(count($stateResults) >= 1, 'API state search Stanley Mosk returns ≥1 result');

$hasMosk = false;
$allState = true;
foreach ($stateResults as $row) {
    if (!is_array($row)) {
        continue;
    }
    if (strtolower((string)($row['courtSystem'] ?? 'state')) !== 'state') {
        $allState = false;
    }
    if (stripos((string)($row['courtName'] ?? ''), 'Stanley Mosk') !== false) {
        $hasMosk = true;
    }
}
audit_assert($allState, 'API state results are all courtSystem=state');
audit_assert($hasMosk, 'API state search includes Stanley Mosk Courthouse');

// --- API: cross-contamination ---
$fedOnly = http_get_json($base . '?route=api/courts/search&q=Stanley+Mosk&limit=10&system=federal');
$fedOnlyResults = is_array($fedOnly['results'] ?? null) ? $fedOnly['results'] : [];
$moskInFederal = false;
foreach ($fedOnlyResults as $row) {
    if (is_array($row) && stripos((string)($row['courtName'] ?? ''), 'Stanley Mosk') !== false) {
        $moskInFederal = true;
    }
}
audit_assert(!$moskInFederal, 'Stanley Mosk does not appear in federal-filtered search');

// --- Export: state project FL-100 ---
$tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'court-e2e-' . getmypid();
@mkdir($tmpDir, 0755, true);

$statePdf = $tmpDir . DIRECTORY_SEPARATOR . 'state-fl100.pdf';
$stateTxt = $tmpDir . DIRECTORY_SEPARATOR . 'state-fl100.txt';
$stateUrl = $base . '?route=actions/export-project-forms&projectId=' . rawurlencode($stateProject)
    . '&pd=' . rawurlencode($statePd) . '&scope=this&format=pdf';

audit_assert(http_download($stateUrl, $statePdf), 'State project exports valid PDF', $stateUrl);

if (is_file($statePdf)) {
    if (pdf_to_text($statePdf, $stateTxt, $pdfboxJar)) {
        $stateText = (string)file_get_contents($stateTxt);
        $stateNeed = [
            'Jordan Q. Tester',
            '298765',
            'Youngman Reitshtein',
            'Merlin Kirkpatrick',
            'Stanley Mosk Courthouse',
            '111 North Hill Street',
            'Los Angeles, CA 90012',
            'E2E-JUN24-001',
        ];
        $stateMissing = text_has_all($stateText, $stateNeed);
        audit_assert($stateMissing === [], 'State export PDF contains caption autofill', implode(', ', $stateMissing));

        $stateForbidden = text_has_none($stateText, ['Central District of California', '255 East Temple']);
        audit_assert($stateForbidden === [], 'State export PDF has no federal court strings', implode(', ', $stateForbidden));
    } else {
        audit_skip('State export PDF text extraction', 'pdfbox not available');
    }
}

// --- Export: federal project (optional until configured) ---
if ($fedPd !== '' && $fedProject !== '') {
    $fedPdf = $tmpDir . DIRECTORY_SEPARATOR . 'fed-fl100.pdf';
    $fedTxt = $tmpDir . DIRECTORY_SEPARATOR . 'fed-fl100.txt';
    $fedUrl = $base . '?route=actions/export-project-forms&projectId=' . rawurlencode($fedProject)
        . '&pd=' . rawurlencode($fedPd) . '&scope=this&format=pdf';
    audit_assert(http_download($fedUrl, $fedPdf), 'Federal project exports valid PDF', $fedUrl);
    if (is_file($fedPdf) && pdf_to_text($fedPdf, $fedTxt, $pdfboxJar)) {
        $fedText = (string)file_get_contents($fedTxt);
        $fedNeed = [
            'Central District of California',
            '255 East Temple Street',
            'Los Angeles, CA 90012',
            'Jordan Q. Tester',
            'Merlin Kirkpatrick',
        ];
        $fedMissing = text_has_all($fedText, $fedNeed);
        audit_assert($fedMissing === [], 'Federal export PDF contains federal court + firm/client', implode(', ', $fedMissing));

        $fedForbidden = text_has_none($fedText, ['Stanley Mosk Courthouse', '111 North Hill Street']);
        audit_assert($fedForbidden === [], 'Federal export PDF has no state court strings', implode(', ', $fedForbidden));

        // Federal: county field on FL-100 should be empty — not "Los Angeles" superior county
        if (preg_match('/SUPERIOR COURT OF CALIFORNIA.*?COUNTY OF:\s*(\S+)/s', $fedText, $m)) {
            $countyVal = trim($m[1]);
            audit_assert($countyVal === '' || stripos($countyVal, 'central') !== false,
                'Federal FL-100 county caption not state superior county',
                'found county token: ' . $countyVal);
        } else {
            audit_skip('Federal FL-100 county caption parse', 'pattern not in extracted text');
        }
    }
} else {
    audit_skip('Federal project export checks', 'Set COURT_E2E_FEDERAL_PD and COURT_E2E_FEDERAL_PROJECT');
}

echo PHP_EOL . str_repeat('=', 60) . PHP_EOL;
echo "Passed: $passed | Failed: $failed | Skipped: $skipped" . PHP_EOL;
exit($failed > 0 ? 1 : 0);
