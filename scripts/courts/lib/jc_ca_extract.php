<?php
declare(strict_types=1);

const JC_CA_BUILDINGS_PDF_URL = 'https://courts.ca.gov/system/files/solicitation-request-document/rfp-fs-sp-2019-03-jp-attachment-2-regional-building-list-addendum-1.pdf';

/**
 * @return array<int, array{id: string, name: string, street: string, city: string, zip: string, county: string, region: string}>
 */
function jc_ca_extract_from_text(string $text): array {
    $rows = [];
    $seen = [];

    foreach (preg_split('/\r\n|\n|\r/', $text) ?: [] as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }

        if (str_starts_with($line, '|')) {
            $parsed = jc_ca_parse_markdown_table_row($line);
            if ($parsed !== null) {
                jc_ca_collect_row($rows, $seen, $parsed);
            }
            continue;
        }

        foreach (jc_ca_parse_dense_rows($line) as $parsed) {
            jc_ca_collect_row($rows, $seen, $parsed);
        }
    }

    usort($rows, static function (array $a, array $b): int {
        $c = strcasecmp($a['county'], $b['county']);
        return $c !== 0 ? $c : strcasecmp($a['name'], $b['name']);
    });

    return $rows;
}

/** @param array<int, array<string, string>> $rows */
function jc_ca_collect_row(array &$rows, array &$seen, array $parsed): void {
    if (!jc_ca_row_is_useful($parsed)) {
        return;
    }
    $key = strtolower($parsed['id'] . '|' . $parsed['name'] . '|' . $parsed['street'] . '|' . $parsed['city']);
    if (isset($seen[$key])) {
        return;
    }
    $seen[$key] = true;
    $rows[] = $parsed;
}

/** @param array<string, string> $row */
function jc_ca_row_is_useful(array $row): bool {
    if (strtolower(trim($row['county'])) === 'los angeles') {
        return false;
    }
    if (!preg_match('/^\d{5}$/', $row['zip'])) {
        return false;
    }
    if (trim($row['name']) === '' || trim($row['street']) === '' || trim($row['city']) === '') {
        return false;
    }
    $name = strtolower($row['name']);
    if (preg_match('/\b(parking structure|parking lot|parking\b|payroll|finance-hr|ocit\b|records center|file unit|storage\b|camp storage|modular \d|trailer\b|jury assembly trailer|building record for|probation center|psychiatric|juvenile hall|juvenile justice center|jury assembly bldg|administration bldg|credit union|swing space)/i', $name)) {
        return false;
    }
    return true;
}

/** @return array{id: string, name: string, street: string, city: string, zip: string, county: string, region: string}|null */
function jc_ca_parse_markdown_table_row(string $line): ?array {
    if (preg_match('/^\|\s*---/', $line)) {
        return null;
    }
    $parts = array_values(array_filter(
        array_map(static fn($p) => trim($p), explode('|', $line)),
        static fn($p) => $p !== ''
    ));
    if ($parts === []) {
        return null;
    }

    $id = jc_ca_normalize_id((string)($parts[0] ?? ''));
    if ($id === null) {
        return null;
    }

    $zipIdx = null;
    foreach ($parts as $i => $part) {
        if (preg_match('/^\d{5}$/', $part)) {
            $zipIdx = $i;
            break;
        }
    }
    if ($zipIdx === null || $zipIdx < 3) {
        return null;
    }

    $zip = $parts[$zipIdx];
    $city = $parts[$zipIdx - 1] ?? '';
    $county = preg_replace('/\s*County\s*$/i', '', (string)($parts[$zipIdx + 1] ?? '')) ?? '';
    if ($city === '' || $county === '' || !preg_match('/^[A-Za-z .\'\-]+$/', $county)) {
        return null;
    }

    $region = '';
    for ($i = $zipIdx + 2; $i < count($parts); $i++) {
        $part = strtoupper($parts[$i]);
        if (in_array($part, ['BANCRO', 'NCRO', 'SRO'], true)) {
            $region = $part;
            break;
        }
    }

    $name = '';
    $street = '';
    for ($i = 1; $i < $zipIdx - 1; $i++) {
        $part = $parts[$i];
        if (preg_match('/^BANCRO$|^NCRO$|^SRO$/i', $part)) {
            continue;
        }
        if ($name === '' && strlen($part) > 2 && !preg_match('/^\d+\s/', $part) && !jc_ca_looks_like_street($part)) {
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

    return [
        'id' => $id,
        'name' => $name,
        'street' => $street,
        'city' => $city,
        'zip' => $zip,
        'county' => $county,
        'region' => $region,
    ];
}

function jc_ca_parse_dense_segment(string $segment): ?array {
    if (!preg_match('/^(\d{2}-[A-Z0-9*]+)\s+(.+?)\s+(\d{5})\s+([A-Za-z .\'\-]+)\s+(BANCRO|NCRO|SRO)\b/', $segment, $m)) {
        return null;
    }
    $id = jc_ca_normalize_id($m[1]);
    if ($id === null) {
        return null;
    }
    $mid = trim($m[2]);
    $streetPattern = '/(\d[\w\s\.,#\'\-\/]*(?:St\.?|Street|Ave\.?|Avenue|Blvd\.?|Boulevard|Drive|Dr\.?|Way|Road|Rd\.?|Plaza|Pass|Pkwy\.?|Parkway|Highway|Hwy\.?|Route|Homestead|Mission|Grant|Center|Union|Brown|Texas|Kansas|Church|Aguajito|Sonoma|Willow|Padre|Walnut|Hill|Main|Broadway|Courthouse|Camino|Real)[\w\s\.,#\'\-\/]*)/i';
    if (!preg_match($streetPattern, $mid, $sm)) {
        return null;
    }
    $street = trim($sm[1]);
    $name = trim(substr($mid, 0, (int)strpos($mid, $sm[1])));
    $city = trim(substr($mid, (int)strpos($mid, $sm[1]) + strlen($sm[1])));
    if ($name === '' || $city === '' || $street === '') {
        return null;
    }
    return [
        'id' => $id,
        'name' => $name,
        'street' => $street,
        'city' => $city,
        'zip' => $m[3],
        'county' => preg_replace('/\s*County\s*$/i', '', trim($m[4])) ?? trim($m[4]),
        'region' => strtoupper(trim($m[5])),
    ];
}

/** @return array<int, array{id: string, name: string, street: string, city: string, zip: string, county: string, region: string}> */
function jc_ca_parse_dense_rows(string $line): array {
    if (str_starts_with($line, '|') || !preg_match('/\b(BANCRO|NCRO|SRO)\b/', $line)) {
        return [];
    }
    $rows = [];
    if (!preg_match_all('/(\d{2}-[A-Z0-9*]+)\s+.+?\s+\d{5}\s+[A-Za-z .\'\-]+\s+(?:BANCRO|NCRO|SRO)\b/', $line, $matches, PREG_OFFSET_CAPTURE)) {
        return [];
    }
    foreach ($matches[0] as [$segment, $offset]) {
        $parsed = jc_ca_parse_dense_segment($segment);
        if ($parsed !== null) {
            $rows[] = $parsed;
        }
    }
    return $rows;
}

function jc_ca_looks_like_street(string $part): bool {
    if (preg_match('/\d/', $part) && preg_match('/(St\.?|Street|Ave\.?|Avenue|Blvd\.?|Boulevard|Drive|Dr\.?|Way|Road|Rd\.?|Plaza|Pass|Pkwy\.?|Highway|Hwy\.?|Homestead|Mission|Grant|Center|Union|Brown|Texas|Kansas|Church|Aguajito|Sonoma|Willow|Camino|Real|Broadway|Route|Courthouse)/i', $part)) {
        return true;
    }
    return preg_match('/^\d+\s+[NSEW]?\.?\s*[\w\s\.\-\']{3,}$/i', $part) === 1;
}

function jc_ca_normalize_id(string $raw): ?string {
    $raw = trim($raw);
    if (!preg_match('/^\d{2}-[A-Z0-9*]+$/', $raw)) {
        return null;
    }
    return str_replace('*', '', $raw);
}

function jc_ca_fetch_pdf_text(): string {
    $ctx = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => implode("\r\n", [
                'User-Agent: Mozilla/5.0 (compatible; PDFTimeSaver/1.0)',
                'Accept: application/pdf,*/*',
            ]),
            'timeout' => 120,
            'follow_location' => 1,
        ],
    ]);
    $pdf = @file_get_contents(JC_CA_BUILDINGS_PDF_URL, false, $ctx);
    if (!is_string($pdf) || $pdf === '') {
        return '';
    }

    $tmpPdf = tempnam(sys_get_temp_dir(), 'jc_ca_') . '.pdf';
    $tmpTxt = tempnam(sys_get_temp_dir(), 'jc_ca_') . '.txt';
    file_put_contents($tmpPdf, $pdf);

    $text = '';
    foreach (['pdftotext', 'pdftotext.exe'] as $bin) {
        $cmd = escapeshellarg($bin) . ' -layout ' . escapeshellarg($tmpPdf) . ' ' . escapeshellarg($tmpTxt) . ' 2>NUL';
        @exec($cmd, $out, $code);
        if ($code === 0 && is_file($tmpTxt)) {
            $text = (string)file_get_contents($tmpTxt);
            break;
        }
    }

    @unlink($tmpPdf);
    @unlink($tmpTxt);

    return trim($text);
}

/** @param array<int, array{id: string, name: string, street: string, city: string, zip: string, county: string, region: string}> $rows */
function jc_ca_rows_to_pipe_file(array $rows): string {
    $lines = [
        '# Judicial Council of California — Regional Building List',
        '# Source: ' . JC_CA_BUILDINGS_PDF_URL,
        '# Generated: ' . gmdate('Y-m-d'),
        '# Los Angeles County locations excluded (lacourt.ca.gov provides dept/room data).',
        '# Format: | JCC ID | Facility Name | Address | City | Zip | County | Region |',
        '',
    ];
    foreach ($rows as $row) {
        $lines[] = sprintf(
            '| %s | %s | %s | %s | %s | %s | %s |',
            $row['id'],
            $row['name'],
            $row['street'],
            $row['city'],
            $row['zip'],
            $row['county'],
            $row['region'] !== '' ? $row['region'] : 'CA'
        );
    }
    return implode("\n", $lines) . "\n";
}
