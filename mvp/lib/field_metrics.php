<?php
declare(strict_types=1);

namespace WebPdfTimeSaver\Mvp;

final class FieldMetrics
{
    public const MM_PER_PT = 0.352778;
    public const PT_PER_MM = 2.834645669;
    public const CSS_PX_PER_PT = 96 / 72;

    private static ?array $fontConfig = null;

    private static function fontConfig(): array
    {
        if (self::$fontConfig !== null) {
            return self::$fontConfig;
        }
        $path = dirname(__DIR__, 2) . '/config/fonts.php';
        self::$fontConfig = is_file($path) ? (array)require $path : [];
        return self::$fontConfig;
    }

    public static function defaultFontPt(): float
    {
        $cfg = self::fontConfig();
        $value = (float)($cfg['defaults']['fontSize'] ?? $cfg['sizeLimits']['default'] ?? 10);
        return self::clamp($value, self::minFontPt(), self::maxFontPt());
    }

    public static function minFontPt(): float
    {
        $cfg = self::fontConfig();
        return max(1.0, (float)($cfg['sizeLimits']['min'] ?? 6));
    }

    public static function maxFontPt(): float
    {
        $cfg = self::fontConfig();
        return max(self::minFontPt(), (float)($cfg['sizeLimits']['max'] ?? 24));
    }

    public static function clamp(float $value, float $min, float $max): float
    {
        return max($min, min($max, $value));
    }

    public static function ptToMm(float $pt): float
    {
        return $pt * self::MM_PER_PT;
    }

    public static function mmToPt(float $mm): float
    {
        return $mm * self::PT_PER_MM;
    }

    public static function ptToCssPx(float $pt): float
    {
        return $pt * self::CSS_PX_PER_PT;
    }

    public static function cssPxToPt(float $px): float
    {
        return $px / self::CSS_PX_PER_PT;
    }

    public static function estimateFontPtFromHeightMm(float $heightMm, float $fallbackPt = 10.0): float
    {
        if (!is_finite($heightMm) || $heightMm <= 0) {
            return self::clamp($fallbackPt, self::minFontPt(), self::maxFontPt());
        }
        $pt = $heightMm * 0.7;
        return self::clamp($pt, 7.0, 16.0);
    }

    public static function previewPtToPx(float $fontPt, float $displayedHeightPx, float $fieldHeightMm): float
    {
        $fieldMm = max(0.1, $fieldHeightMm);
        $displayPx = max(1.0, $displayedHeightPx);
        $pxPerMm = $displayPx / $fieldMm;
        return max(4.0, self::ptToMm($fontPt) * $pxPerMm);
    }

    public static function normalizeImportedFontPt($rawValue, array $meta = [], ?float $fallback = null): float
    {
        $fallbackPt = $fallback ?? self::defaultFontPt();
        $raw = is_numeric($rawValue) ? (float)$rawValue : 0.0;
        if (!is_finite($raw) || $raw <= 0) {
            return self::clamp($fallbackPt, self::minFontPt(), self::maxFontPt());
        }
        $type = strtolower((string)($meta['type'] ?? $meta['fieldType'] ?? ''));
        if ($type === 'checkbox' || $type === 'radio') {
            return self::clamp($raw, 5.0, 12.0);
        }
        return self::clamp($raw, 6.0, 16.0);
    }

    public static function exportFontPtForField(array $position, array $values, string $fieldKey): float
    {
        $fallback = self::normalizeImportedFontPt($position['fontSize'] ?? self::defaultFontPt(), $position, self::defaultFontPt());
        $raw = $values['_font_size__' . $fieldKey] ?? null;
        if ($raw === null || $raw === '') {
            return $fallback;
        }
        if (!is_numeric($raw)) {
            return $fallback;
        }

        $rawNumber = (float)$raw;
        if (!is_finite($rawNumber) || $rawNumber <= 0) {
            return $fallback;
        }

        // Legacy populate saves are px. Infer px when value is noticeably above pt range.
        $asPt = $rawNumber;
        if ($rawNumber > self::maxFontPt()) {
            $asPt = self::cssPxToPt($rawNumber);
        }
        return self::normalizeImportedFontPt($asPt, $position, $fallback);
    }

    public static function jsConfig(): array
    {
        return [
            'MM_PER_PT' => self::MM_PER_PT,
            'PT_PER_MM' => self::PT_PER_MM,
            'CSS_PX_PER_PT' => self::CSS_PX_PER_PT,
            'DEFAULT_FONT_PT' => self::defaultFontPt(),
            'MIN_FONT_PT' => self::minFontPt(),
            'MAX_FONT_PT' => self::maxFontPt(),
            'MIN_PREVIEW_PX' => 4.0,
        ];
    }
}
