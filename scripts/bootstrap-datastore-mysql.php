<?php
/** CLI: instantiate DataStore once → CREATE TABLE for Phase 1 + mvp_* (tunnel/db.local must work). */
declare(strict_types=1);

$root = dirname(__DIR__);
require_once $root . '/mvp/lib/logger.php';
require_once $root . '/mvp/lib/data.php';

use WebPdfTimeSaver\Mvp\DataStore;
use WebPdfTimeSaver\Mvp\Logger;

$dataPath = $root . '/data/mvp.json';
$logger = new Logger($root . '/logs/app.log');

try {
	$store = new DataStore($dataPath, $logger);
	$ok = $store->isMysqlPhaseOneConnected();
	echo json_encode([
		'success' => true,
		'mysql_phase1_connected' => $ok,
		'data_path' => $dataPath,
	], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
	exit($ok ? 0 : 2);
} catch (Throwable $e) {
	fwrite(STDERR, $e->getMessage() . PHP_EOL);
	exit(1);
}
