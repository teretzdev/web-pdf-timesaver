<?php
/**
 * Write current in-memory MVP entity arrays to data/mvp.json (MySQL is still canonical when PDO is on).
 * Use for offline backup / git; re-run after tunnel + bootstrap if you want a fresh file snapshot.
 */
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/mvp/lib/logger.php';
require_once $root . '/mvp/lib/data.php';

use WebPdfTimeSaver\Mvp\DataStore;
use WebPdfTimeSaver\Mvp\Logger;

$path = $root . '/data/mvp.json';
$store = new DataStore($path, new Logger($root . '/logs/app.log'));

if (!$store->isMysqlPhaseOneConnected()) {
	fwrite(STDERR, "MySQL not connected; snapshot reflects JSON-only state.\n");
}

$store->exportMvpEntitySnapshotToJsonFile();
echo "Wrote entity snapshot to {$path}\n";
