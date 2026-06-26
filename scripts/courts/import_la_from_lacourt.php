<?php
declare(strict_types=1);
/**
 * Import LA Superior Court locations + departments from lacourt.ca.gov.
 *
 * Usage:
 *   php scripts/courts/import_la_from_lacourt.php
 *   php scripts/courts/import_la_from_lacourt.php --html=path/to/Courtrooms.html
 *   php scripts/courts/import_la_from_lacourt.php --merge-only   # refresh courts_ca.json only
 */
require_once __DIR__ . '/lib/lacourt_parser.php';
require_once __DIR__ . '/../../mvp/lib/logger.php';
require_once __DIR__ . '/../../mvp/lib/data.php';

use WebPdfTimeSaver\Mvp\DataStore;
use WebPdfTimeSaver\Mvp\Logger;

const LACOURT_URL = 'https://www.lacourt.ca.gov/courtroom/UI/Courtrooms.aspx';

$args = array_slice($argv ?? [], 1);
$htmlPath = '';
$mergeOnly = false;
foreach ($args as $arg) {
    if (str_starts_with($arg, '--html=')) {
        $htmlPath = substr($arg, 7);
    } elseif ($arg === '--merge-only') {
        $mergeOnly = true;
    }
}

$root = dirname(__DIR__, 2);
$addressMapPath = $root . '/data/lacourt_addresses.json';
$courtsPath = $root . '/data/courts_ca.json';
$addressMap = lacourt_load_address_map($addressMapPath);

$html = '';
if ($htmlPath !== '' && is_file($htmlPath)) {
    $html = (string)file_get_contents($htmlPath);
    fwrite(STDOUT, "Using local HTML: $htmlPath\n");
} else {
    fwrite(STDOUT, "Fetching " . LACOURT_URL . " …\n");
    $html = lacourt_fetch_html(LACOURT_URL);
}

if (trim($html) === '') {
    fwrite(STDERR, "Could not load lacourt HTML. Save the page manually and pass --html=path\n");
    exit(1);
}

$laLocations = lacourt_parse_courtrooms_html($html, $addressMap);
if ($laLocations === []) {
    fwrite(STDERR, "No LA court locations parsed from HTML.\n");
    exit(1);
}

foreach ($laLocations as &$row) {
    $row['courtSystem'] = 'state';
    $row['source'] = 'lacourt';
}
unset($row);

$deptTotal = 0;
foreach ($laLocations as $loc) {
    $deptTotal += count((array)($loc['departments'] ?? []));
}
fwrite(STDOUT, 'Parsed ' . count($laLocations) . " LA location(s), $deptTotal department(s).\n");

$existing = ['locations' => []];
if (is_file($courtsPath)) {
    $decoded = json_decode((string)file_get_contents($courtsPath), true);
    if (is_array($decoded)) {
        $existing = $decoded;
    }
}

$byId = [];
foreach ((array)($existing['locations'] ?? []) as $row) {
    if (!is_array($row)) {
        continue;
    }
    if (strtolower(trim((string)($row['source'] ?? ''))) === 'lacourt') {
        continue;
    }
    $id = trim((string)($row['id'] ?? ''));
    if ($id !== '') {
        $byId[$id] = $row;
    }
}
foreach ($laLocations as $row) {
    $id = trim((string)($row['id'] ?? ''));
    if ($id !== '') {
        $byId[$id] = $row;
    }
}

$merged = ['locations' => array_values($byId)];
usort($merged['locations'], static function (array $a, array $b): int {
    $c = strcasecmp((string)($a['county'] ?? ''), (string)($b['county'] ?? ''));
    if ($c !== 0) {
        return $c;
    }
    return strcasecmp((string)($a['courtName'] ?? ''), (string)($b['courtName'] ?? ''));
});

$written = @file_put_contents(
    $courtsPath,
    json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
);
if ($written === false) {
    fwrite(STDERR, "Failed to write $courtsPath\n");
    exit(1);
}
fwrite(STDOUT, 'Wrote ' . count($merged['locations']) . " total location(s) to $courtsPath\n");

if ($mergeOnly) {
    exit(0);
}

$logger = new Logger();
$store = new DataStore($root . '/data/mvp.json', $logger);
if (!method_exists($store, 'importCourtLocationsSnapshot')) {
    fwrite(STDERR, "DataStore::importCourtLocationsSnapshot not available.\n");
    exit(1);
}
$count = $store->importCourtLocationsSnapshot($merged['locations']);
fwrite(STDOUT, "Imported/updated $count court location(s) in database snapshot.\n");
