<?php
declare(strict_types=1);

function jc_ca_buildings_path(): string {
    return dirname(__DIR__, 3) . '/data/jc_ca_buildings.txt';
}

function jc_ca_slug(string $name, string $city): string {
    $s = strtolower(trim($name . ' ' . $city));
    $s = preg_replace('/[^a-z0-9]+/', '_', $s) ?? '';
    return 'cl_jc_' . substr(trim($s, '_') ?: 'court', 0, 48);
}

/**
 * Parse Judicial Council pipe-table rows from jc_ca_buildings.txt
 *
 * @return array<int, array<string, mixed>>
 */
function jc_ca_parse_buildings_file(string $path): array {
    if (!is_file($path)) {
        return [];
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES) ?: [];
    $locations = [];
    $seen = [];
    foreach ($lines as $line) {
        $row = jc_ca_parse_building_line($line);
        if ($row === null) {
            continue;
        }
        $id = (string)($row['id'] ?? '');
        if ($id === '' || isset($seen[$id])) {
            continue;
        }
        $seen[$id] = true;
        $locations[] = $row;
    }
    return $locations;
}

/** @return array<string, mixed>|null */
function jc_ca_parse_building_line(string $line): ?array {
    $line = trim($line);
    if ($line === '' || strpos($line, '|') !== 0) {
        return null;
    }
    $parts = array_map(static fn($p) => trim($p), explode('|', $line));
    // Drop leading empty segment from leading |
    if (isset($parts[0]) && $parts[0] === '') {
        array_shift($parts);
    }

    $zipIdx = null;
    foreach ($parts as $i => $part) {
        if (preg_match('/^\d{5}$/', $part)) {
            $zipIdx = $i;
            break;
        }
    }
    if ($zipIdx === null || $zipIdx < 2) {
        return null;
    }

    $zip = $parts[$zipIdx];
    $city = $parts[$zipIdx - 1] ?? '';
    $county = $parts[$zipIdx + 1] ?? '';
    if ($city === '' || $county === '' || !preg_match('/^[A-Za-z .\'\-]+$/', $county)) {
        return null;
    }

    $buildingId = preg_replace('/[^A-Za-z0-9\-*]/', '', (string)($parts[0] ?? ''));
    $name = '';
    $street = '';
    for ($i = 1; $i < $zipIdx - 1; $i++) {
        $part = $parts[$i];
        if ($part === '' || preg_match('/^(BANCRO|NCRO|SRO)$/i', $part)) {
            continue;
        }
        if ($name === '' && strlen($part) > 2 && !preg_match('/^\d+\s/', $part)) {
            $name = $part;
            continue;
        }
        if (jc_ca_looks_like_street($part)) {
            $street = $part;
        }
    }
    if ($name === '' || $street === '') {
        return null;
    }

    $county = preg_replace('/\s*County\s*$/i', '', $county) ?? $county;

    return [
        'id' => jc_ca_slug($name, $city),
        'courtSystem' => 'state',
        'state' => 'CA',
        'county' => $county,
        'courtName' => $name,
        'street' => $street,
        'mailingAddress' => $street,
        'city' => $city,
        'stateCode' => 'CA',
        'zip' => $zip,
        'phone' => '',
        'source' => 'jc_ca',
        'sourceId' => $buildingId,
        'departments' => [],
    ];
}

function jc_ca_looks_like_street(string $part): bool {
    if (preg_match('/\d/', $part) && preg_match('/(St\.?|Street|Ave\.?|Avenue|Blvd\.?|Boulevard|Drive|Dr\.?|Way|Road|Rd\.?|Plaza|Pass|Pkwy\.?|Highway|Hwy\.?|Homestead|Mission|Grant|Center|Union|Brown|Texas|Kansas|Church|Aguajito|Sonoma|Willow|Camino|Real|Broadway|Route)/i', $part)) {
        return true;
    }
    return preg_match('/^\d+\s+[NSEW]?\.?\s*[\w\s\.\-\']{3,}$/i', $part) === 1;
}
