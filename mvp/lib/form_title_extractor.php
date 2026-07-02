<?php
declare(strict_types=1);

namespace WebPdfTimeSaver\Mvp;

use Smalot\PdfParser\Parser;

/**
 * Extracts a human-readable form title from PDF metadata.
 */
final class FormTitleExtractor
{
    /**
     * @return array{title:string, source:string, confidence:float}
     */
    public static function extractFromPdfMetadata(string $pdfPath, string $templateId = '', string $sourceFileName = ''): array
    {
        $empty = ['title' => '', 'source' => '', 'confidence' => 0.0];
        if ($pdfPath === '' || !is_readable($pdfPath)) {
            return $empty;
        }

        $candidates = [];

        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($pdfPath);
            if (is_object($pdf) && method_exists($pdf, 'getDetails')) {
                $details = (array)$pdf->getDetails();
                foreach (['Title', 'title', 'Subject', 'subject'] as $key) {
                    if (!array_key_exists($key, $details)) {
                        continue;
                    }
                    foreach (self::toScalarCandidates($details[$key]) as $rawValue) {
                        $normalized = self::normalizeTitleCandidate($rawValue);
                        if ($normalized === '') {
                            continue;
                        }
                        $isTitle = stripos($key, 'title') !== false;
                        $candidates[] = [
                            'title' => $normalized,
                            'source' => $isTitle ? 'pdf_metadata_title' : 'pdf_metadata_subject',
                            'confidence' => $isTitle ? 0.95 : 0.72,
                        ];
                    }
                }
            }
        } catch (\Throwable $e) {
            error_log('FormTitleExtractor metadata parse warning: ' . $e->getMessage());
        }

        $raw = @file_get_contents($pdfPath, false, null, 0, 2 * 1024 * 1024);
        if (is_string($raw) && $raw !== '') {
            foreach (self::scanRawMetadataCandidates($raw) as $item) {
                $candidates[] = $item;
            }
        }
        foreach (self::inferTitleFromIdentifiers($templateId, $sourceFileName) as $item) {
            $candidates[] = $item;
        }

        if ($candidates === []) {
            return $empty;
        }

        usort($candidates, static function (array $a, array $b): int {
            $confidenceCmp = (float)$b['confidence'] <=> (float)$a['confidence'];
            if ($confidenceCmp !== 0) {
                return $confidenceCmp;
            }
            return strlen((string)$b['title']) <=> strlen((string)$a['title']);
        });

        $best = $candidates[0];
        $rawTitle = (string)($best['title'] ?? '');
        $identity = self::parseFormIdentityFromTitle($rawTitle, $templateId, $sourceFileName);
        $parsedName = trim((string)($identity['formName'] ?? ''));
        return [
            'title' => $parsedName !== '' ? $parsedName : $rawTitle,
            'source' => (string)($best['source'] ?? ''),
            'confidence' => (float)($best['confidence'] ?? 0.0),
        ];
    }

    /**
     * Split a PDF/stored title into form number and descriptive name (name only, no code prefix).
     *
     * @return array{formNumber:string, formName:string}
     */
    public static function parseFormIdentityFromTitle(string $rawTitle, string $templateId = '', string $sourceFileName = ''): array
    {
        $title = self::normalizeUnicodeDashes(trim($rawTitle));
        if ($title === '') {
            $code = self::extractFormCodeFromIdentifier($templateId);
            if ($code === '') {
                $code = self::extractFormCodeFromIdentifier($sourceFileName);
            }
            if ($code !== '' && isset(self::identifierTitleCatalog()[$code])) {
                return [
                    'formNumber' => $code,
                    'formName' => self::identifierTitleCatalog()[$code],
                ];
            }
            return ['formNumber' => $code, 'formName' => ''];
        }

        $number = '';
        $name = $title;

        if (preg_match('/^([A-Z]{1,4}-\d{1,4})\s*-\s*(.+)$/i', $title, $m) === 1) {
            $number = strtoupper(trim((string)$m[1]));
            $name = trim((string)$m[2]);
        } elseif (preg_match('/^([A-Z]{1,4}-\d{1,4})\s+(.+)$/i', $title, $m) === 1) {
            $number = strtoupper(trim((string)$m[1]));
            $name = trim((string)$m[2]);
        }

        if ($number === '') {
            $number = self::extractFormCodeFromIdentifier($templateId);
            if ($number === '') {
                $number = self::extractFormCodeFromIdentifier($sourceFileName);
            }
        }

        $name = self::normalizeUnicodeDashes($name);
        if ($number !== '' && preg_match('/^' . preg_quote($number, '/') . '\s*-\s*(.+)$/i', $name, $stripMatch) === 1) {
            $name = trim((string)$stripMatch[1]);
        }

        return [
            'formNumber' => $number,
            'formName' => trim($name),
        ];
    }

    public static function normalizeUnicodeDashes(string $value): string
    {
        // Normalize Unicode dashes first, then only add spacing when dash separates words.
        // Keep canonical form codes intact (e.g., FL-100 should not become FL - 100).
        $value = str_replace(["\u{2014}", "\u{2013}"], '-', $value);
        $value = preg_replace('/(?<=\p{L})-(?=\p{L})/u', ' - ', $value) ?? $value;
        $value = preg_replace('/\s+-\s+/u', ' - ', $value) ?? $value;
        $value = trim((string)preg_replace('/\s+/u', ' ', $value));
        return trim($value, " \t-");
    }

    /**
     * @return list<string>
     */
    private static function toScalarCandidates(mixed $value): array
    {
        if (is_string($value) || is_numeric($value)) {
            return [trim((string)$value)];
        }
        if (is_array($value)) {
            $out = [];
            foreach ($value as $item) {
                if (is_string($item) || is_numeric($item)) {
                    $out[] = trim((string)$item);
                }
            }
            return $out;
        }
        return [];
    }

    /**
     * @return list<array{title:string, source:string, confidence:float}>
     */
    private static function scanRawMetadataCandidates(string $rawPdf): array
    {
        $out = [];
        $patterns = [
            ['regex' => '/\/Title\s*\((.*?)\)/s', 'source' => 'raw_pdf_title', 'confidence' => 0.7],
            ['regex' => '/\/Subject\s*\((.*?)\)/s', 'source' => 'raw_pdf_subject', 'confidence' => 0.55],
        ];
        foreach ($patterns as $rule) {
            $matchCount = preg_match_all((string)$rule['regex'], $rawPdf, $matches);
            if (!is_int($matchCount) || $matchCount <= 0) {
                continue;
            }
            foreach ((array)($matches[1] ?? []) as $rawCandidate) {
                $decoded = self::decodePdfLiteralString((string)$rawCandidate);
                $normalized = self::normalizeTitleCandidate($decoded);
                if ($normalized === '') {
                    continue;
                }
                $out[] = [
                    'title' => $normalized,
                    'source' => (string)$rule['source'],
                    'confidence' => (float)$rule['confidence'],
                ];
            }
        }
        return $out;
    }

    private static function decodePdfLiteralString(string $value): string
    {
        $decoded = preg_replace_callback('/\\\\([nrtbf()\\\\])/', static function (array $m): string {
            return match ($m[1]) {
                'n' => "\n",
                'r' => "\r",
                't' => "\t",
                'b' => "\x08",
                'f' => "\x0c",
                '(' => '(',
                ')' => ')',
                '\\' => '\\',
                default => $m[0],
            };
        }, $value);
        return (string)$decoded;
    }

    private static function normalizeTitleCandidate(string $raw): string
    {
        $title = trim($raw);
        if ($title === '') {
            return '';
        }
        $title = preg_replace('/[\x00-\x1F\x7F]/u', ' ', $title) ?? $title;
        $title = html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $title = trim((string)preg_replace('/\s+/u', ' ', $title));
        $title = trim($title, "\"' ");
        $title = preg_replace('/\.pdf$/i', '', $title) ?? $title;
        $title = trim($title);
        if ($title === '' || strlen($title) < 4 || strlen($title) > 180) {
            return '';
        }

        $normalized = strtolower((string)preg_replace('/[^a-z0-9]+/i', '', $title));
        $generic = [
            'document',
            'pdf',
            'untitled',
            'adobeacrobatdocument',
            'form',
        ];
        if ($normalized === '' || in_array($normalized, $generic, true)) {
            return '';
        }
        if (preg_match('/^[a-z]{1,4}[-_ ]?\d{1,4}$/i', $title) === 1) {
            return '';
        }
        if (preg_match('/_[a-f0-9]{8,}$/i', $title) === 1) {
            return '';
        }
        return $title;
    }

    /**
     * @return list<array{title:string, source:string, confidence:float}>
     */
    private static function inferTitleFromIdentifiers(string $templateId, string $sourceFileName): array
    {
        $out = [];
        $code = self::extractFormCodeFromIdentifier($templateId);
        if ($code === '') {
            $code = self::extractFormCodeFromIdentifier($sourceFileName);
        }
        if ($code === '') {
            return $out;
        }
        $catalog = self::identifierTitleCatalog();
        if (isset($catalog[$code])) {
            $out[] = [
                'title' => $catalog[$code],
                'source' => 'identifier_catalog',
                'confidence' => 0.82,
            ];
            return $out;
        }
        $out[] = [
            'title' => $code,
            'source' => 'identifier_code',
            'confidence' => 0.45,
        ];
        return $out;
    }

    private static function extractFormCodeFromIdentifier(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        if (preg_match('/([a-z]{1,4})[-_ ]?(\d{1,4})/i', $value, $m) === 1) {
            return strtoupper((string)$m[1] . '-' . $m[2]);
        }
        return '';
    }

    /**
     * @return array<string, string>
     */
    private static function identifierTitleCatalog(): array
    {
        return [
            'FL-100' => 'Petition - Marriage/Domestic Partnership',
            'FL-150' => 'Income and Expense Declaration',
            'FL-170' => 'Declaration for Default or Uncontested Dissolution',
            'FL-200' => 'Petition to Determine Parental Relationship',
        ];
    }
}

