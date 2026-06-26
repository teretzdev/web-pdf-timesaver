<?php
declare(strict_types=1);

/**
 * FL-100 Position Iteration Tool
 *
 * Repeatable loop for tuning FL-100 field placement:
 *  1) regenerate positions from uploads/fl-100.pdf
 *  2) run automated verification
 *  3) generate debug overlay PDF
 *  4) optionally nudge matching fields by dx/dy
 *
 * Usage examples:
 *   php mvp/tools/fl100_position_loop.php cycle
 *   php mvp/tools/fl100_position_loop.php regen
 *   php mvp/tools/fl100_position_loop.php verify
 *   php mvp/tools/fl100_position_loop.php debug
 *   php mvp/tools/fl100_position_loop.php nudge --match="CaseNumber" --dx=1.2 --dy=-0.8
 */

$root = realpath(__DIR__ . '/../../');
if ($root === false) {
    fwrite(STDERR, "Unable to resolve project root.\n");
    exit(1);
}

require_once $root . '/vendor/autoload.php';
require_once $root . '/mvp/lib/pdf_field_extractor.php';
require_once $root . '/mvp/lib/position_debug_generator.php';

use WebPdfTimeSaver\Mvp\PdfFieldExtractor;
use WebPdfTimeSaver\Mvp\PositionDebugGenerator;

const TEMPLATE_ID = 't_fl100_gc120';

$command = $argv[1] ?? 'cycle';
$args = parseArgs(array_slice($argv, 2));

try {
    switch ($command) {
        case 'regen':
            runRegeneration($root);
            break;
        case 'verify':
            runVerification($root);
            break;
        case 'debug':
            runDebugPdf($root);
            break;
        case 'nudge':
            runNudge($root, $args);
            break;
        case 'cycle':
            runRegeneration($root);
            runVerification($root);
            runDebugPdf($root);
            break;
        default:
            printUsage();
            exit(1);
    }
} catch (Throwable $e) {
    fwrite(STDERR, "ERROR: " . $e->getMessage() . PHP_EOL);
    exit(1);
}

exit(0);

/**
 * @param array<int, string> $raw
 * @return array<string, string>
 */
function parseArgs(array $raw): array
{
    $out = [];
    foreach ($raw as $token) {
        if (strncmp($token, '--', 2) !== 0) {
            continue;
        }
        $pair = substr($token, 2);
        $eqPos = strpos($pair, '=');
        if ($eqPos === false) {
            $out[$pair] = '1';
            continue;
        }
        $k = substr($pair, 0, $eqPos);
        $v = substr($pair, $eqPos + 1);
        $out[$k] = $v;
    }
    return $out;
}

function runRegeneration(string $root): void
{
    $sourceCandidates = [
        $root . '/uploads/fl-100.pdf',
        $root . '/uploads/fl100.pdf',
        $root . '/uploads/FL-100.pdf',
    ];
    $sourcePdf = '';
    foreach ($sourceCandidates as $candidate) {
        if (is_file($candidate)) {
            $sourcePdf = $candidate;
            break;
        }
    }
    $targetPdf = $root . '/uploads/' . TEMPLATE_ID . '.pdf';
    if ($sourcePdf === '') {
        throw new RuntimeException('Source PDF missing in uploads/. Expected one of: fl-100.pdf, fl100.pdf, FL-100.pdf');
    }
    if (!@copy($sourcePdf, $targetPdf)) {
        throw new RuntimeException("Failed copying source PDF to {$targetPdf}");
    }

    $extractor = new PdfFieldExtractor();
    $result = $extractor->extractAndGenerateBackgrounds($targetPdf, TEMPLATE_ID, $root . '/uploads');
    $fields = is_array($result['fields'] ?? null) ? $result['fields'] : [];
    $backgrounds = is_array($result['backgrounds'] ?? null) ? $result['backgrounds'] : [];
    $positionFile = (string)($result['positionFile'] ?? ($root . '/data/' . TEMPLATE_ID . '_positions.json'));

    echo "Regen complete" . PHP_EOL;
    echo "- Template: " . TEMPLATE_ID . PHP_EOL;
    echo "- Fields: " . count($fields) . PHP_EOL;
    echo "- Backgrounds: " . count($backgrounds) . PHP_EOL;
    echo "- Positions file: " . $positionFile . PHP_EOL;
}

function runVerification(string $root): void
{
    $pdfPath = $root . '/uploads/' . TEMPLATE_ID . '.pdf';
    $positionsPath = $root . '/data/' . TEMPLATE_ID . '_positions.json';
    $scriptPath = $root . '/scripts/auto-verify-positions.js';

    if (!is_file($pdfPath)) {
        throw new RuntimeException("Template PDF missing: {$pdfPath}");
    }
    if (!is_file($positionsPath)) {
        throw new RuntimeException("Positions file missing: {$positionsPath}");
    }
    if (!is_file($scriptPath)) {
        throw new RuntimeException("Verification script missing: {$scriptPath}");
    }

    $cmd = 'node ' .
        escapeshellarg($scriptPath) . ' ' .
        escapeshellarg($pdfPath) . ' ' .
        escapeshellarg($positionsPath) . ' ' .
        escapeshellarg(TEMPLATE_ID);

    $out = [];
    $code = 0;
    exec($cmd . ' 2>&1', $out, $code);

    echo "Verification command exit code: {$code}" . PHP_EOL;
    foreach ($out as $line) {
        echo $line . PHP_EOL;
    }

    $reportPath = $root . '/data/' . TEMPLATE_ID . '_verification_report.json';
    if (is_file($reportPath)) {
        $report = json_decode((string)file_get_contents($reportPath), true);
        $summary = is_array($report['summary'] ?? null) ? $report['summary'] : [];
        $avg = (string)($summary['avgScore'] ?? 'n/a');
        $fails = (string)($summary['fails'] ?? 'n/a');
        $warns = (string)($summary['warns'] ?? 'n/a');
        echo "Verification summary: avg={$avg}, fails={$fails}, warns={$warns}" . PHP_EOL;

        $results = is_array($report['results'] ?? null) ? $report['results'] : [];
        $failRows = array_values(array_filter($results, static function ($row): bool {
            return is_array($row) && (($row['verdict'] ?? '') === 'fail');
        }));
        if (!empty($failRows)) {
            usort($failRows, static function (array $a, array $b): int {
                return ((float)($a['score'] ?? 0)) <=> ((float)($b['score'] ?? 0));
            });
            $top = array_slice($failRows, 0, 12);
            echo "Top failing fields (lowest score first):" . PHP_EOL;
            foreach ($top as $row) {
                $name = (string)($row['name'] ?? 'unknown');
                $score = number_format((float)($row['score'] ?? 0), 3);
                $x = (string)($row['x'] ?? '');
                $y = (string)($row['y'] ?? '');
                echo "  - {$name} | score={$score} | x={$x}, y={$y}" . PHP_EOL;
            }
        }
    }
}

function runDebugPdf(string $root): void
{
    $positionsPath = $root . '/data/' . TEMPLATE_ID . '_positions.json';
    if (!is_file($positionsPath)) {
        throw new RuntimeException("Positions file missing: {$positionsPath}");
    }

    $positions = json_decode((string)file_get_contents($positionsPath), true);
    if (!is_array($positions) || empty($positions)) {
        throw new RuntimeException('Positions file invalid or empty.');
    }

    $values = [];
    $i = 1;
    foreach ($positions as $key => $pos) {
        if (!is_array($pos)) {
            continue;
        }
        $fieldType = strtolower((string)($pos['type'] ?? 'text'));
        if ($fieldType === 'checkbox') {
            $values[(string)$key] = '1';
            continue;
        }
        $values[(string)$key] = 'F' . str_pad((string)$i, 3, '0', STR_PAD_LEFT);
        $i++;
    }

    $debugOutputPath = $root . '/output/' . TEMPLATE_ID . '_position_debug.pdf';
    $generator = new PositionDebugGenerator();
    $generator->generateDebugPdf(TEMPLATE_ID, $positions, $values, $debugOutputPath);
    echo "Debug overlay generated: {$debugOutputPath}" . PHP_EOL;
}

/**
 * Applies dx/dy shift to matching keys in positions file.
 * --match is a case-insensitive regex fragment.
 */
function runNudge(string $root, array $args): void
{
    $match = (string)($args['match'] ?? '');
    $dx = (float)($args['dx'] ?? 0);
    $dy = (float)($args['dy'] ?? 0);
    if ($match === '') {
        throw new RuntimeException('nudge requires --match=<regex-fragment>');
    }
    if ($dx == 0.0 && $dy == 0.0) {
        throw new RuntimeException('nudge requires non-zero --dx and/or --dy');
    }

    $positionsPath = $root . '/data/' . TEMPLATE_ID . '_positions.json';
    if (!is_file($positionsPath)) {
        throw new RuntimeException("Positions file missing: {$positionsPath}");
    }

    $positions = json_decode((string)file_get_contents($positionsPath), true);
    if (!is_array($positions)) {
        throw new RuntimeException('Positions file invalid.');
    }

    $regex = '/' . str_replace('/', '\/', $match) . '/i';
    $updated = 0;

    foreach ($positions as $key => &$pos) {
        if (!is_array($pos)) {
            continue;
        }
        $haystack = (string)$key . ' ' . (string)($pos['name'] ?? '') . ' ' . (string)($pos['canonicalName'] ?? '');
        if (@preg_match($regex, $haystack) !== 1) {
            continue;
        }
        $oldX = (float)($pos['x'] ?? 0);
        $oldY = (float)($pos['y'] ?? 0);
        $pos['x'] = round($oldX + $dx, 2);
        $pos['y'] = round($oldY + $dy, 2);
        $updated++;
    }
    unset($pos);

    $backupPath = $positionsPath . '.backup.nudge.' . date('Ymd_His');
    @copy($positionsPath, $backupPath);
    file_put_contents($positionsPath, json_encode($positions, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

    echo "Nudge applied" . PHP_EOL;
    echo "- Match: {$match}" . PHP_EOL;
    echo "- dx: {$dx}, dy: {$dy}" . PHP_EOL;
    echo "- Fields updated: {$updated}" . PHP_EOL;
    echo "- Backup: {$backupPath}" . PHP_EOL;
}

function printUsage(): void
{
    echo "FL-100 Position Iteration Tool\n";
    echo "Commands:\n";
    echo "  regen                         Regenerate positions/backgrounds from uploads/fl-100.pdf\n";
    echo "  verify                        Run automated verification for current positions\n";
    echo "  debug                         Generate debug overlay PDF in output/\n";
    echo "  cycle                         Run regen + verify + debug\n";
    echo "  nudge --match=... --dx=... --dy=...\n";
}
