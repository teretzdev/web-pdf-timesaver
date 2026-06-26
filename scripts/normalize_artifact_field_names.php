<?php

declare(strict_types=1);

$dataDir = realpath(__DIR__ . '/../data');
if ($dataDir === false || !is_dir($dataDir)) {
    fwrite(STDERR, "Data directory not found.\n");
    exit(1);
}

$patterns = [
    $dataDir . DIRECTORY_SEPARATOR . '*_extraction_details.json',
    $dataDir . DIRECTORY_SEPARATOR . '*_verification_report.json',
];

$files = [];
foreach ($patterns as $pattern) {
    $matches = glob($pattern) ?: [];
    foreach ($matches as $file) {
        $files[$file] = true;
    }
}
$files = array_keys($files);
sort($files, SORT_STRING);

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

$normalizeRows = static function (array $rows) use ($canonicalize): array {
    $out = [];
    foreach ($rows as $idx => $row) {
        if (!is_array($row)) {
            $out[] = $row;
            continue;
        }
        $source = trim((string)($row['canonicalName'] ?? $row['name'] ?? $row['fieldName'] ?? ('field_' . $idx)));
        $canon = $canonicalize($source, 'field_' . $idx);
        if ($canon !== '') {
            $existingName = trim((string)($row['name'] ?? ''));
            if ($existingName !== '' && $existingName !== $canon && !isset($row['rawName'])) {
                $row['rawName'] = $existingName;
            }
            $row['name'] = $canon;
            $row['canonicalName'] = $canon;
        }
        $out[] = $row;
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

    $original = $decoded;
    if (isset($decoded['fields']) && is_array($decoded['fields'])) {
        $decoded['fields'] = $normalizeRows($decoded['fields']);
    }
    if (isset($decoded['results']) && is_array($decoded['results'])) {
        $decoded['results'] = $normalizeRows($decoded['results']);
    }

    $newJson = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if (!is_string($newJson)) {
        continue;
    }
    if ($decoded !== $original || trim($newJson) !== trim($raw)) {
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

