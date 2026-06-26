<?php
declare(strict_types=1);
/**
 * Import court_locations from data/courts_ca.json into MySQL (or refresh JSON snapshot).
 * Usage: php scripts/courts/import_courts_seed.php
 */
require_once __DIR__ . '/../../mvp/lib/logger.php';
require_once __DIR__ . '/../../mvp/lib/data.php';

use WebPdfTimeSaver\Mvp\DataStore;
use WebPdfTimeSaver\Mvp\Logger;

$logger = new Logger();
$store = new DataStore(__DIR__ . '/../../data/mvp.json', $logger);
$path = __DIR__ . '/../../data/courts_ca.json';
if (!is_file($path)) {
    fwrite(STDERR, "Missing $path\n");
    exit(1);
}
$decoded = json_decode((string)file_get_contents($path), true);
$locations = is_array($decoded['locations'] ?? null) ? $decoded['locations'] : [];
if ($locations === []) {
    fwrite(STDERR, "No locations in courts_ca.json\n");
    exit(1);
}
$count = method_exists($store, 'importCourtLocationsSnapshot')
    ? $store->importCourtLocationsSnapshot($locations)
    : 0;
echo "Imported/updated $count court location(s).\n";
