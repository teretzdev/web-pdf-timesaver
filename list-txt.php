<?php
/**
 * List .txt files starting from filesystem root (caps at 2 results).
 * Outputs plain text with one file per line.
 */

header('Content-Type: text/plain; charset=utf-8');

$root = (PHP_OS_FAMILY === 'Windows') ? 'C:\\' : '/';
$results = [];
$max = 2;

try {
    $iter = new DirectoryIterator($root);
    foreach ($iter as $fileInfo) {
        try {
            if ($fileInfo->isDot() || !$fileInfo->isFile()) {
                continue;
            }
            if (strtolower($fileInfo->getExtension()) !== 'txt') {
                continue;
            }
            $real = realpath($fileInfo->getPathname());
            if ($real === false) {
                continue;
            }
            $results[] = $real;
            if (count($results) >= $max) {
                break;
            }
        } catch (Throwable $e) {
            // Ignore unreadable entries
        }
    }
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage();
    exit;
}

echo implode(PHP_EOL, $results);

// Extra diagnostics for Linux: show node locations, since client asked.
if (PHP_OS_FAMILY !== 'Windows') {
    echo PHP_EOL, "--- node check ---", PHP_EOL;
    $whichNode = trim((string)shell_exec('which node 2>/dev/null'));
    if ($whichNode !== '') {
        echo "which node: $whichNode", PHP_EOL;
    } else {
        echo "which node: (not found)", PHP_EOL;
    }

    $lsNode = shell_exec('ls -hal /usr/bin/node* 2>/dev/null');
    if ($lsNode) {
        echo "ls -hal /usr/bin/node*: ", PHP_EOL, trim($lsNode), PHP_EOL;
    } else {
        echo "ls -hal /usr/bin/node*: (no matches)", PHP_EOL;
    }

    $exists = file_exists('/usr/bin/node') ? 'yes' : 'no';
    echo "file_exists(/usr/bin/node): $exists", PHP_EOL;

    echo "--- ghostscript check ---", PHP_EOL;
    $whichGs = trim((string)shell_exec('which gs 2>/dev/null'));
    echo $whichGs !== '' ? "which gs: $whichGs" . PHP_EOL : "which gs: (not found)" . PHP_EOL;

    $lsGs = shell_exec('ls -hal /usr/bin/gs* 2>/dev/null');
    echo $lsGs ? "ls -hal /usr/bin/gs*: " . PHP_EOL . trim($lsGs) . PHP_EOL : "ls -hal /usr/bin/gs*: (no matches)" . PHP_EOL;

    $gsVersion = [];
    $rc = 0;
    @exec('gs -v 2>&1', $gsVersion, $rc);
    echo ($rc === 0 && !empty($gsVersion)) ? "gs -v: " . $gsVersion[0] . PHP_EOL : "gs -v: (not available)" . PHP_EOL;
}
exit;

