<?php
declare(strict_types=1);

/**
 * Normalize a template label for redundancy checks (alphanumeric only, lowercase).
 */
function normalizeTemplateLabelKey(string $value): string
{
    return (string)preg_replace('/[^a-z0-9]/', '', strtolower($value));
}

/**
 * Extract a clean form code such as FL-100 from a template id slug.
 */
function extractFormCodeFromTemplateId(string $templateId): string
{
    $templateId = trim(preg_replace('/^t_/', '', $templateId) ?? $templateId);
    if ($templateId === '') {
        return '';
    }

    if (preg_match('/([a-z]{1,4})[-_](\d{1,4})/i', $templateId, $matches) === 1) {
        return strtoupper((string)$matches[1] . '-' . $matches[2]);
    }

    if (preg_match('/^([a-z]+\d+)/i', $templateId, $matches) === 1) {
        $formCode = strtoupper((string)$matches[1]);
        if (preg_match('/^([A-Z]+)(\d+)$/', $formCode, $formMatches) === 1) {
            return (string)$formMatches[1] . '-' . (string)$formMatches[2];
        }
        return $formCode;
    }

    return '';
}

/**
 * Build a single human-readable template label without repeating code + name.
 */
function formatTemplateDisplayLabel(?array $template, string $templateId = '', ?string $storedFormName = null): string
{
    $templateId = trim($templateId !== '' ? $templateId : (string)($template['id'] ?? ''));
    $storedFormName = $storedFormName !== null ? trim($storedFormName) : '';

    if ($storedFormName !== '' && strcasecmp($storedFormName, $templateId) !== 0) {
        return $storedFormName;
    }

    $code = trim((string)($template['code'] ?? ''));
    $name = trim((string)($template['name'] ?? ''));

    if ($code === '' && $name === '') {
        $formCode = extractFormCodeFromTemplateId($templateId);
        if ($formCode !== '') {
            return $formCode;
        }
        return $templateId !== '' ? $templateId : 'Document';
    }

    if ($code === '') {
        return $name;
    }
    if ($name === '') {
        return $code;
    }

    $normalizedCode = normalizeTemplateLabelKey($code);
    $normalizedName = normalizeTemplateLabelKey($name);
    $redundant = $normalizedCode === $normalizedName
        || ($normalizedCode !== '' && str_starts_with($normalizedName, $normalizedCode))
        || ($normalizedName !== '' && str_starts_with($normalizedCode, $normalizedName));

    if ($redundant) {
        $formCode = extractFormCodeFromTemplateId($templateId);
        if ($formCode !== '') {
            return $formCode;
        }
        if (preg_match('/\bform$/i', $name) === 1) {
            return $name;
        }
        return $code;
    }

    if (stripos($name, $code) !== false) {
        return $name;
    }

    $spacedCode = str_replace(['_', '-'], ' ', $code);
    if (stripos($name, $spacedCode) !== false) {
        return $name;
    }

    return $code . ' — ' . $name;
}

/**
 * Normalize a matched form-code prefix to a stable family key (fl100 / fl-100 → fl_100).
 */
function normalizeFormFamilyKey(string $familyPrefix): string
{
    $familyPrefix = strtolower(str_replace('-', '_', trim($familyPrefix)));
    if (preg_match('/^fl_?(\d{2,3})$/', $familyPrefix, $matches) === 1) {
        return 'fl_' . $matches[1];
    }
    if (preg_match('/^w_?(\d{1,2})$/', $familyPrefix, $matches) === 1) {
        return 'w_' . $matches[1];
    }
    return $familyPrefix;
}

/**
 * Group key for templates that are the same form under different slugs (fl-100_* vs fl100_*).
 */
function resolveFormTemplateFamily(string $templateId): string
{
    $normalized = strtolower(trim($templateId));
    if ($normalized === '') {
        return '';
    }
    if (preg_match('/^(fl[_-]?\d{2,3})/i', $normalized, $matches) === 1) {
        return normalizeFormFamilyKey((string)$matches[1]);
    }
    if (preg_match('/^(w[_-]?\d{1,2})/i', $normalized, $matches) === 1) {
        return normalizeFormFamilyKey((string)$matches[1]);
    }
    if (preg_match('/^([a-z]+[_-]?\d{1,3})/i', $normalized, $matches) === 1) {
        return normalizeFormFamilyKey((string)$matches[1]);
    }
    return '';
}

/**
 * Prefer hyphenated form codes (fl-100_…) over legacy slugs (fl100_…).
 */
function templateIdCanonicalScore(string $templateId, string $preferredTemplateId = ''): int
{
    $templateId = strtolower(trim($templateId));
    $preferredTemplateId = strtolower(trim($preferredTemplateId));
    if ($preferredTemplateId !== '' && $templateId === $preferredTemplateId) {
        return 1000;
    }
    $score = 0;
    if (preg_match('/[a-z]-\d+/i', $templateId) === 1) {
        $score += 20;
    }
    if (preg_match('/_[a-f0-9]{8,}$/i', $templateId) === 1) {
        $score += 5;
    }
    return $score - (int)floor(strlen($templateId) / 12);
}

/**
 * Pick the canonical template id from two candidates in the same form family.
 */
function preferCanonicalTemplateId(string $a, string $b, string $preferredTemplateId = ''): string
{
    return templateIdCanonicalScore($a, $preferredTemplateId) >= templateIdCanonicalScore($b, $preferredTemplateId) ? $a : $b;
}

/**
 * Remove duplicate template ids that map to the same form family; keep the canonical slug.
 *
 * @param array<int, string> $templateIds
 * @return array<int, string>
 */
function dedupeTemplateIdsByFamily(array $templateIds, array $preferredOrder = []): array
{
    $preferredByFamily = [];
    foreach ($preferredOrder as $tid) {
        $tid = trim((string)$tid);
        if ($tid === '') {
            continue;
        }
        $family = resolveFormTemplateFamily($tid);
        if ($family !== '' && !isset($preferredByFamily[$family])) {
            $preferredByFamily[$family] = $tid;
        }
    }

    $out = [];
    $winnerByFamily = [];
    foreach ($templateIds as $raw) {
        $tid = trim((string)$raw);
        if ($tid === '') {
            continue;
        }
        $family = resolveFormTemplateFamily($tid);
        if ($family === '') {
            $out[] = $tid;
            continue;
        }
        $preferred = (string)($preferredByFamily[$family] ?? '');
        if (!isset($winnerByFamily[$family])) {
            $winnerByFamily[$family] = $tid;
            continue;
        }
        $winnerByFamily[$family] = preferCanonicalTemplateId($winnerByFamily[$family], $tid, $preferred);
    }

    $seenFamilies = [];
    foreach ($templateIds as $raw) {
        $tid = trim((string)$raw);
        if ($tid === '') {
            continue;
        }
        $family = resolveFormTemplateFamily($tid);
        if ($family === '') {
            if (!in_array($tid, $out, true)) {
                $out[] = $tid;
            }
            continue;
        }
        if (isset($seenFamilies[$family])) {
            continue;
        }
        $seenFamilies[$family] = true;
        $out[] = $winnerByFamily[$family] ?? $tid;
    }

    return array_values($out);
}
