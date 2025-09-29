<?php
declare(strict_types=1);

$tests = [
    __DIR__ . '/mvp_test.php',
    __DIR__ . '/pdf_export_test.php',
    __DIR__ . '/ui_render_test.php',
    __DIR__ . '/projects_ui_test.php',
    __DIR__ . '/actions_flow_test.php',
    __DIR__ . '/registry_schema_test.php',
    __DIR__ . '/dom_assertions_test.php',
    __DIR__ . '/assign_client_test.php',
    __DIR__ . '/client_page_test.php',
];

$overall = 0;
$php = PHP_BINARY ?: 'php';
foreach ($tests as $test) {
    echo "\n==> Running " . basename($test) . "\n";
    passthru(escapeshellarg($php) . ' ' . escapeshellarg($test), $code);
    $overall += (int)$code;
}

echo "\nALL DONE. Exit code: $overall\n";
exit($overall);


