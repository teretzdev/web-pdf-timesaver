<?php
declare(strict_types=1);

require __DIR__ . '/../mvp/lib/field_metrics.php';

use WebPdfTimeSaver\Mvp\FieldMetrics;

$failures = 0;
function ok(bool $cond, string $msg): void
{
    global $failures;
    if (!$cond) {
        echo "FAIL: $msg\n";
        $failures++;
    } else {
        echo '.';
    }
}

ok(abs(FieldMetrics::defaultFontPx() - 13.0) < 0.01, 'defaultFontPx should be 13');

$storedPx = FieldMetrics::normalizeFontPx(13, ['fontSizeUnit' => 'px', 'fontSizeSource' => 'user']);
ok(abs($storedPx - 13.0) < 0.01, '13 px with user source stays 13');

$legacyPt = FieldMetrics::normalizeFontPx(9.75, [], 13.0);
ok(abs($legacyPt - 13.0) < 0.01, 'legacy 9.75 pt without unit converts to 13 px');

$exportPt = FieldMetrics::exportFontPtForField(
    ['fontSize' => 13, 'fontSizeUnit' => 'px'],
    [],
    'attorney_name'
);
ok(abs($exportPt - 9.75) < 0.05, 'export converts 13 px to ~9.75 pt');

$overridePt = FieldMetrics::exportFontPtForField(
    ['fontSize' => 13, 'fontSizeUnit' => 'px'],
    ['_font_size__attorney_name' => 13],
    'attorney_name'
);
ok(abs($overridePt - 9.75) < 0.05, 'export override in px converts to ~9.75 pt');

$js = FieldMetrics::jsConfig();
ok(isset($js['DEFAULT_FONT_PX']) && (int)$js['DEFAULT_FONT_PX'] === 13, 'jsConfig exposes DEFAULT_FONT_PX=13');
ok(isset($js['MIN_FONT_PX']) && (int)$js['MIN_FONT_PX'] === 8, 'jsConfig exposes MIN_FONT_PX=8');
ok(isset($js['MAX_FONT_PX']) && (int)$js['MAX_FONT_PX'] === 32, 'jsConfig exposes MAX_FONT_PX=32');

echo "\n\n" . ($failures === 0 ? 'FONT METRICS PX TEST PASSED' : (string)$failures . ' FAILURES') . "\n";
exit($failures === 0 ? 0 : 1);
