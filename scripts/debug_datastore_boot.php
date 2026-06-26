<?php
declare(strict_types=1);
/**
 * CLI: boot DataStore like the app and call form-registry reads (no HTTP redirects).
 */
$root = dirname(__DIR__);
require_once $root . '/vendor/autoload.php';
require_once $root . '/mvp/lib/logger.php';
require_once $root . '/mvp/lib/data.php';

use WebPdfTimeSaver\Mvp\DataStore;
use WebPdfTimeSaver\Mvp\Logger;

$logger = new Logger();
try {
	$store = new DataStore($root . '/data/mvp.json', $logger);
	echo "DataStore OK, mysql=" . ($store->isMysqlPhaseOneConnected() ? 'yes' : 'no') . "\n";
	$list = method_exists($store, 'getGlobalFormTemplates') ? $store->getGlobalFormTemplates() : [];
	echo "getGlobalFormTemplates count=" . count($list) . "\n";
	if ($list !== []) {
		$first = $list[0];
		echo "first templateId=" . ($first['templateId'] ?? '') . " keys=" . implode(',', array_keys($first)) . "\n";
	}
	echo "BOOT_OK\n";
} catch (Throwable $e) {
	fwrite(STDERR, "FAIL: " . $e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine() . "\n");
	exit(1);
}
