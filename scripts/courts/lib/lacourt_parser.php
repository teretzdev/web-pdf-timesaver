<?php
declare(strict_types=1);

/**
 * Parse LA Superior Court "Courtrooms By Location" HTML into location records.
 */

function lacourt_slug(string $courtName): string {
    $s = strtolower(trim($courtName));
    $s = preg_replace('/[^a-z0-9]+/', '_', $s) ?? '';
    $s = trim($s, '_');
    if ($s === '') {
        $s = 'court';
    }
    return 'cl_la_' . substr($s, 0, 48);
}

function lacourt_dept_slug(string $locationId, string $department): string {
    $d = strtolower(trim($department));
    $d = preg_replace('/[^a-z0-9]+/', '_', $d) ?? '';
    $d = trim($d, '_');
    if ($d === '') {
        $d = 'dept';
    }
    return 'cd_' . substr(preg_replace('/[^a-zA-Z0-9_-]/', '', $locationId) ?? 'cl', 0, 20) . '_' . substr($d, 0, 24);
}

/** @return array<string, array<string, string>> */
function lacourt_load_address_map(string $path): array {
    if (!is_file($path)) {
        return [];
    }
    $decoded = json_decode((string)file_get_contents($path), true);
    return is_array($decoded) ? $decoded : [];
}

function lacourt_normalize_court_name(string $name): string {
    return preg_replace('/\s+/', ' ', trim($name)) ?? '';
}

/** @return array<string, string> */
function lacourt_lookup_address(array $addressMap, string $courtName): array {
    $name = lacourt_normalize_court_name($courtName);
    if (isset($addressMap[$name]) && is_array($addressMap[$name])) {
        return $addressMap[$name];
    }
    foreach ($addressMap as $key => $row) {
        if (!is_array($row)) {
            continue;
        }
        if (stripos($name, (string)$key) !== false || stripos((string)$key, $name) !== false) {
            return $row;
        }
    }
    return [];
}

function lacourt_fetch_html(string $url): string {
    $ctx = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => implode("\r\n", [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept: text/html,application/xhtml+xml',
                'Accept-Language: en-US,en;q=0.9',
            ]),
            'timeout' => 30,
            'follow_location' => 1,
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ]);
    $html = @file_get_contents($url, false, $ctx);
    return is_string($html) ? $html : '';
}

/**
 * @return array<int, array<string, mixed>>
 */
function lacourt_parse_courtrooms_html(string $html, array $addressMap = []): array {
    if (trim($html) === '') {
        return [];
    }

    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $loaded = $dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
    libxml_clear_errors();
    if (!$loaded) {
        return [];
    }

    $xpath = new DOMXPath($dom);
    $tables = $xpath->query("//table[contains(concat(' ', normalize-space(@class), ' '), ' commontable ')]");
    if (!$tables) {
        return [];
    }

    $locations = [];
    foreach ($tables as $table) {
        if (!$table instanceof DOMElement) {
            continue;
        }
        $rows = $xpath->query('.//tr', $table);
        if (!$rows || $rows->length < 2) {
            continue;
        }
        $firstRow = $rows->item(0);
        if (!$firstRow instanceof DOMElement) {
            continue;
        }
        $courtName = lacourt_normalize_court_name(trim($firstRow->textContent ?? ''));
        if ($courtName === '' || stripos($courtName, 'dept') === 0) {
            continue;
        }

        $addr = lacourt_lookup_address($addressMap, $courtName);
        $locationId = lacourt_slug($courtName);
        $street = trim((string)($addr['street'] ?? ''));
        $city = trim((string)($addr['city'] ?? 'Los Angeles'));
        $zip = trim((string)($addr['zip'] ?? ''));
        $phone = trim((string)($addr['phone'] ?? ''));

        $departments = [];
        for ($i = 2; $i < $rows->length; $i++) {
            $row = $rows->item($i);
            if (!$row instanceof DOMElement) {
                continue;
            }
            $cells = $xpath->query('.//td', $row);
            if (!$cells || $cells->length < 4) {
                continue;
            }
            $dept = trim($cells->item(0)?->textContent ?? '');
            $floor = trim($cells->item(1)?->textContent ?? '');
            $room = trim($cells->item(2)?->textContent ?? '');
            $deptPhone = trim($cells->item(4)?->textContent ?? ($cells->item(3)?->textContent ?? ''));
            if ($dept === '') {
                continue;
            }
            $departments[] = [
                'id' => lacourt_dept_slug($locationId, $dept),
                'department' => $dept,
                'floor' => $floor,
                'room' => $room,
                'phone' => $deptPhone,
                'source' => 'lacourt',
            ];
        }

        $locations[] = [
            'id' => $locationId,
            'courtSystem' => 'state',
            'state' => 'CA',
            'county' => 'Los Angeles',
            'courtName' => $courtName,
            'street' => $street,
            'mailingAddress' => $street,
            'city' => $city,
            'stateCode' => 'CA',
            'zip' => $zip,
            'phone' => $phone,
            'source' => 'lacourt',
            'departments' => $departments,
        ];
    }

    return $locations;
}
