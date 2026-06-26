<?php

declare(strict_types=1);

$dataDir = realpath(__DIR__ . '/../data');
if ($dataDir === false || !is_dir($dataDir)) {
    fwrite(STDERR, "Data directory not found.\n");
    exit(1);
}

$files = glob($dataDir . DIRECTORY_SEPARATOR . '*_positions.json') ?: [];

$canonicalize = static function (string $raw, string $fallback = ''): string {
    $value = trim($raw);
    if ($value === '') {
        return trim($fallback);
    }
    $value = preg_replace('/\[(\d+)\]/', '_$1', $value);
    $value = preg_replace('/[^A-Za-z0-9]+/', '_', (string)$value);
    $value = preg_replace('/_+/', '_', (string)$value);
    $value = trim((string)$value, '_');
    if ($value === '') {
        return trim($fallback);
    }
    return $value;
};

$normalizeMap = static function (array $positions) use ($canonicalize): array {
    $out = [];
    $seen = [];

    $storeRow = static function (string $rawKey, array $row) use (&$out, &$seen, $canonicalize): void {
        $baseKey = $canonicalize($rawKey, $rawKey);
        if ($baseKey === '') {
            return;
        }
        $finalKey = $baseKey;
        $suffix = 2;
        while (isset($seen[$finalKey]) && $seen[$finalKey] === true) {
            $finalKey = $baseKey . '_' . $suffix;
            $suffix++;
        }
        $seen[$finalKey] = true;
        $row['canonicalName'] = $finalKey;
        if (!isset($row['name']) || trim((string)$row['name']) === '') {
            $row['name'] = $rawKey;
        }
        $out[$finalKey] = $row;
    };

    if (array_is_list($positions)) {
        foreach ($positions as $idx => $row) {
            if (!is_array($row)) {
                continue;
            }
            $rawName = trim((string)($row['canonicalName'] ?? $row['name'] ?? $row['fieldName'] ?? ('field_' . $idx)));
            if ($rawName === '') {
                $rawName = 'field_' . $idx;
            }
            $storeRow($rawName, $row);
        }
        return $out;
    }

    foreach ($positions as $key => $row) {
        if (!is_array($row)) {
            continue;
        }
        $rawName = is_string($key) ? trim($key) : '';
        if ($rawName === '') {
            $rawName = trim((string)($row['canonicalName'] ?? $row['name'] ?? $row['fieldName'] ?? ''));
        }
        if ($rawName === '') {
            continue;
        }
        $storeRow($rawName, $row);
    }

    return $out;
};

$checked = 0;
$changed = 0;
$changedFiles = [];

foreach ($files as $file) {
    $checked++;
    $raw = @file_get_contents($file);
    if ($raw === false) {
        continue;
    }
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        continue;
    }
    $normalized = $normalizeMap($decoded);
    $newJson = json_encode($normalized, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if (!is_string($newJson)) {
        continue;
    }
    if (trim($raw) !== trim($newJson)) {
        file_put_contents($file, $newJson . PHP_EOL);
        $changed++;
        $changedFiles[] = basename($file);
    }
}

echo 'checked=' . $checked . ' changed=' . $changed . PHP_EOL;
if (!empty($changedFiles)) {
    foreach ($changedFiles as $name) {
        echo $name . PHP_EOL;
    }
}

