<?php
declare(strict_types=1);
/**
 * Import CA statewide superior court building addresses from Judicial Council list.
 *
 * Usage:
 *   php scripts/courts/import_ca_jc_buildings.php
 *   php scripts/courts/import_ca_jc_buildings.php --merge-only
 */
require_once __DIR__ . '/lib/jc_ca_parser.php';
require_once __DIR__ . '/../../mvp/lib/logger.php';
require_once __DIR__ . '/../../mvp/lib/data.php';

use WebPdfTimeSaver\Mvp\DataStore;
use WebPdfTimeSaver\Mvp\Logger;

$mergeOnly = in_array('--merge-only', array_slice($argv ?? [], 1), true);
$root = dirname(__DIR__, 2);
$courtsPath = $root . '/data/courts_ca.json';
$buildingsPath = jc_ca_buildings_path();

if (!is_file($buildingsPath)) {
    fwrite(STDERR, "Missing $buildingsPath\n");
    exit(1);
}

$stateLocations = jc_ca_parse_buildings_file($buildingsPath);
if ($stateLocations === []) {
    fwrite(STDERR, "No JC CA buildings parsed.\n");
    exit(1);
}
fwrite(STDOUT, 'Parsed ' . count($stateLocations) . " statewide building(s).\n");

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
    if (strtolower(trim((string)($row['source'] ?? ''))) === 'jc_ca') {
        continue;
    }
    $id = trim((string)($row['id'] ?? ''));
    if ($id !== '') {
        $byId[$id] = $row;
    }
}
foreach ($stateLocations as $row) {
    $id = trim((string)($row['id'] ?? ''));
    if ($id !== '') {
        $byId[$id] = $row;
    }
}

$merged = ['locations' => array_values($byId)];
usort($merged['locations'], static function (array $a, array $b): int {
    $sys = strcasecmp((string)($a['courtSystem'] ?? 'state'), (string)($b['courtSystem'] ?? 'state'));
    if ($sys !== 0) {
        return $sys;
    }
    $c = strcasecmp((string)($a['county'] ?? ''), (string)($b['county'] ?? ''));
    return $c !== 0 ? $c : strcasecmp((string)($a['courtName'] ?? ''), (string)($b['courtName'] ?? ''));
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
