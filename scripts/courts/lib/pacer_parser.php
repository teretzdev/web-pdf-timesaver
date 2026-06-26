<?php
declare(strict_types=1);

const PACER_COURTS_JSON_URL = 'https://pacer.uscourts.gov/file-case/court-cmecf-lookup/data.json';

function pacer_fetch_json(): array {
    $ctx = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => implode("\r\n", [
                'User-Agent: Mozilla/5.0 (compatible; PDFTimeSaver/1.0; +https://pdftimesaver.local)',
                'Accept: application/json',
            ]),
            'timeout' => 60,
            'follow_location' => 1,
        ],
    ]);
    $raw = @file_get_contents(PACER_COURTS_JSON_URL, false, $ctx);
    if (!is_string($raw) || trim($raw) === '') {
        return [];
    }
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return [];
    }
    return is_array($decoded['data'] ?? null) ? $decoded['data'] : [];
}

/** @return array{street: string, city: string, stateCode: string, zip: string} */
function pacer_parse_address(string $address): array {
    $out = ['street' => '', 'city' => '', 'stateCode' => 'US', 'zip' => ''];
    $address = trim(preg_replace('/\s+/', ' ', $address) ?? '');
    if ($address === '') {
        return $out;
    }
    if (preg_match('/,\s*([A-Za-z .]+),\s*([A-Z]{2})\s+(\d{5}(?:-\d{4})?)\s*$/', $address, $m)) {
        $out['city'] = trim($m[1]);
        $out['stateCode'] = strtoupper(trim($m[2]));
        $out['zip'] = trim($m[3]);
        $out['street'] = trim(substr($address, 0, -strlen($m[0])));
        $out['street'] = trim(preg_replace('/^[^,]+,\s*/', '', $out['street']) ?? $out['street']);
        return $out;
    }
    $out['street'] = $address;
    return $out;
}

function pacer_slug(string $s): string {
    $s = strtolower(trim($s));
    $s = preg_replace('/[^a-z0-9]+/', '_', $s) ?? '';
    return trim($s, '_') ?: 'court';
}

/**
 * @param array<int, array<string, mixed>> $data
 * @return array<int, array<string, mixed>>
 */
function pacer_parse_federal_locations(array $data): array {
    $locations = [];
    foreach ($data as $court) {
        if (!is_array($court)) {
            continue;
        }
        $courtId = trim((string)($court['court_id'] ?? ''));
        if ($courtId === '' || $courtId === '00PCL') {
            continue;
        }
        $parentName = trim((string)($court['court_name'] ?? $court['title'] ?? ''));
        $locRows = is_array($court['locations'] ?? null) ? $court['locations'] : [];
        if ($locRows === []) {
            continue;
        }
        $idx = 0;
        foreach ($locRows as $loc) {
            if (!is_array($loc)) {
                continue;
            }
            $name = trim((string)($loc['name'] ?? ''));
            if ($name === '') {
                $name = $parentName;
            }
            $addrRaw = trim((string)($loc['address'] ?? ''));
            if ($addrRaw === '') {
                continue;
            }
            $addr = pacer_parse_address($addrRaw);
            $slug = pacer_slug($courtId . '_' . $name . '_' . $idx);
            $idx++;
            $locations[] = [
                'id' => 'cf_' . substr($slug, 0, 56),
                'courtSystem' => 'federal',
                'state' => $addr['stateCode'] !== 'US' ? $addr['stateCode'] : 'US',
                'county' => '',
                'courtName' => $name,
                'street' => $addr['street'] !== '' ? $addr['street'] : $addrRaw,
                'mailingAddress' => $addr['street'] !== '' ? $addr['street'] : $addrRaw,
                'city' => $addr['city'],
                'stateCode' => $addr['stateCode'] !== 'US' ? $addr['stateCode'] : '',
                'zip' => $addr['zip'],
                'phone' => trim((string)($loc['phone'] ?? '')),
                'source' => 'pacer',
                'sourceId' => $courtId,
                'departments' => [],
            ];
        }
    }
    return $locations;
}
