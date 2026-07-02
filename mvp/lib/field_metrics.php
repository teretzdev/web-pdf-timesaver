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

    public static function defaultFontPx(): float
    {
        $cfg = self::fontConfig();
        $value = (float)($cfg['defaults']['fontSize'] ?? $cfg['sizeLimits']['default'] ?? 13);
        return self::clamp($value, self::minFontPx(), self::maxFontPx());
    }

    public static function minFontPx(): float
    {
        $cfg = self::fontConfig();
        return max(1.0, (float)($cfg['sizeLimits']['min'] ?? 8));
    }

    public static function maxFontPx(): float
    {
        $cfg = self::fontConfig();
        return max(self::minFontPx(), (float)($cfg['sizeLimits']['max'] ?? 32));
    }

    /** @deprecated Use defaultFontPx(); kept for PDF engine boundary conversions. */
    public static function defaultFontPt(): float
    {
        return self::cssPxToPt(self::defaultFontPx());
    }

    /** @deprecated Use minFontPx(). */
    public static function minFontPt(): float
    {
        return self::cssPxToPt(self::minFontPx());
    }

    /** @deprecated Use maxFontPx(). */
    public static function maxFontPt(): float
    {
        return self::cssPxToPt(self::maxFontPx());
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

    public static function pxToPt(float $px): float
    {
        return self::cssPxToPt($px);
    }

    public static function normalizeFontPx($rawValue, array $meta = [], ?float $fallback = null): float
    {
        $fallbackPx = $fallback ?? self::defaultFontPx();
        $raw = is_numeric($rawValue) ? (float)$rawValue : 0.0;
        if (!is_finite($raw) || $raw <= 0) {
            return self::clamp($fallbackPx, self::minFontPx(), self::maxFontPx());
        }

        $unit = strtolower(trim((string)($meta['fontSizeUnit'] ?? '')));
        if ($unit === 'px') {
            return self::clamp($raw, self::minFontPx(), self::maxFontPx());
        }

        // Legacy positions stored pt without fontSizeUnit.
        if ($raw <= self::maxFontPt() + 0.01) {
            return self::clamp(self::ptToCssPx($raw), self::minFontPx(), self::maxFontPx());
        }

        return self::clamp($raw, self::minFontPx(), self::maxFontPx());
    }

    public static function estimateFontPxFromHeightMm(float $heightMm, float $fallbackPx = 13.0): float
    {
        if (!is_finite($heightMm) || $heightMm <= 0) {
            return self::clamp($fallbackPx, self::minFontPx(), self::maxFontPx());
        }
        $pt = max(self::cssPxToPt($fallbackPx), self::mmToPt($heightMm * 0.95));
        return self::clamp(self::ptToCssPx($pt), self::minFontPx(), self::maxFontPx());
    }

    /** @deprecated Prefer normalizeFontPx(). */
    public static function estimateFontPtFromHeightMm(float $heightMm, float $fallbackPt = 13.0): float
    {
        return self::cssPxToPt(self::estimateFontPxFromHeightMm($heightMm, self::ptToCssPx($fallbackPt)));
    }

    public static function previewFontPxForField(float $fontPx, float $displayedHeightPx, float $fieldHeightMm): float
    {
        $storedPx = self::clamp($fontPx, self::minFontPx(), self::maxFontPx());
        $fieldMm = max(0.1, $fieldHeightMm);
        $displayPx = max(1.0, $displayedHeightPx);
        $pxPerMm = $displayPx / $fieldMm;
        $pt = self::cssPxToPt($storedPx);
        return max(4.0, self::ptToMm($pt) * $pxPerMm);
    }

    /** @deprecated Prefer previewFontPxForField(). */
    public static function previewPtToPx(float $fontPt, float $displayedHeightPx, float $fieldHeightMm): float
    {
        return self::previewFontPxForField(self::ptToCssPx($fontPt), $displayedHeightPx, $fieldHeightMm);
    }

    /** @deprecated Prefer normalizeFontPx(). */
    public static function normalizeImportedFontPt($rawValue, array $meta = [], ?float $fallback = null): float
    {
        $fallbackPx = $fallback !== null ? self::ptToCssPx((float)$fallback) : null;
        $px = self::normalizeFontPx($rawValue, $meta, $fallbackPx);
        return self::cssPxToPt($px);
    }

    public static function exportFontPtForField(array $position, array $values, string $fieldKey): float
    {
        $fallbackPx = self::normalizeFontPx($position['fontSize'] ?? self::defaultFontPx(), $position, self::defaultFontPx());
        $fallbackPt = self::cssPxToPt($fallbackPx);

        $raw = $values['_font_size__' . $fieldKey] ?? null;
        if ($raw === null || $raw === '') {
            return self::cssPxToPt(self::normalizeFontPx($position['fontSize'] ?? $fallbackPx, $position, $fallbackPx));
        }
        if (!is_numeric($raw)) {
            return $fallbackPt;
        }

        $rawNumber = (float)$raw;
        if (!is_finite($rawNumber) || $rawNumber <= 0) {
            return $fallbackPt;
        }

        // Populate/import UI overrides are stored in CSS px.
        $px = self::normalizeFontPx($rawNumber, ['fontSizeUnit' => 'px'], $fallbackPx);
        return self::cssPxToPt($px);
    }

    public static function exportFontPxForField(array $position, array $values, string $fieldKey): float
    {
        return self::normalizeFontPx(
            $values['_font_size__' . $fieldKey] ?? ($position['fontSize'] ?? self::defaultFontPx()),
            $position,
            self::defaultFontPx()
        );
    }

    public static function jsConfig(): array
    {
        return [
            'MM_PER_PT' => self::MM_PER_PT,
            'PT_PER_MM' => self::PT_PER_MM,
            'CSS_PX_PER_PT' => self::CSS_PX_PER_PT,
            'DEFAULT_FONT_PX' => self::defaultFontPx(),
            'MIN_FONT_PX' => self::minFontPx(),
            'MAX_FONT_PX' => self::maxFontPx(),
            // Legacy keys — px values for backward-compatible JS reads during migration.
            'DEFAULT_FONT_PT' => self::defaultFontPx(),
            'MIN_FONT_PT' => self::minFontPx(),
            'MAX_FONT_PT' => self::maxFontPx(),
            'MIN_PREVIEW_PX' => 4.0,
        ];
    }
}
