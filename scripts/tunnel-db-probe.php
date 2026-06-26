<?php
/** CLI: verify PHP → tunnel → MySQL (run from repo root: php scripts/tunnel-db-probe.php) */
declare(strict_types=1);

require dirname(__DIR__) . '/config/db_env.php';
wpts_apply_db_local_overrides();
$p = wpts_mysql_params();

if ($p['disabled'] || $p['incomplete']) {
	fwrite(STDERR, "DB disabled or incomplete credentials.\n");
	exit(1);
}

$dsn = sprintf(
	'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
	$p['host'],
	$p['port'],
	$p['database']
);

try {
	$pdo = new PDO($dsn, $p['user'], $p['password'], [
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
	]);
	$row = $pdo->query('SELECT 1 AS ok, DATABASE() AS db')->fetch(PDO::FETCH_ASSOC);
	$n = (int) $pdo->query(
		"SELECT COUNT(*) FROM information_schema.tables
		 WHERE table_schema = DATABASE() AND table_name LIKE 'mvp_%'"
	)->fetchColumn();
	echo json_encode([
		'success' => true,
		'host' => $p['host'],
		'port' => $p['port'],
		'database' => $row['db'] ?? null,
		'mvp_table_count' => $n,
	], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
	exit(0);
} catch (Throwable $e) {
	fwrite(STDERR, 'Connection failed: ' . $e->getMessage() . PHP_EOL);
	exit(1);
}
