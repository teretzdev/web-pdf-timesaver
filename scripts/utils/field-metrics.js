const MM_PER_PT = 0.352778;
const PT_PER_MM = 2.834645669;
const CSS_PX_PER_PT = 96 / 72;
const DEFAULT_FONT_PT = 10;
const MIN_FONT_PT = 6;
const MAX_FONT_PT = 24;

function clamp(value, min, max) {
    return Math.max(min, Math.min(max, value));
}

function asNumber(value, fallback = 0) {
    const n = Number(value);
    return Number.isFinite(n) ? n : fallback;
}

function ptToMm(pt) {
    return asNumber(pt, 0) * MM_PER_PT;
}

function mmToPt(mm) {
    return asNumber(mm, 0) * PT_PER_MM;
}

function ptToCssPx(pt) {
    return asNumber(pt, 0) * CSS_PX_PER_PT;
}

function cssPxToPt(px) {
    return asNumber(px, 0) / CSS_PX_PER_PT;
}

function estimateFontPtFromHeightMm(heightMm, fallbackPt = DEFAULT_FONT_PT) {
    const h = asNumber(heightMm, 0);
    if (h <= 0) {
        return clamp(asNumber(fallbackPt, DEFAULT_FONT_PT), MIN_FONT_PT, MAX_FONT_PT);
    }
    return clamp(h * 0.7, 7, 16);
}

function normalizeImportedFontPt(meta, fallbackPt = DEFAULT_FONT_PT) {
    const field = meta && typeof meta === 'object' ? meta : {};
    const raw = asNumber(field.fontSize, 0);
    const fallback = clamp(asNumber(fallbackPt, DEFAULT_FONT_PT), MIN_FONT_PT, MAX_FONT_PT);
    if (raw <= 0) {
        return fallback;
    }
    const type = String(field.type || field.fieldType || '').toLowerCase();
    if (type === 'checkbox' || type === 'radio') {
        return clamp(raw, 5, 12);
    }
    return clamp(raw, 6, 16);
}

module.exports = {
    MM_PER_PT,
    PT_PER_MM,
    CSS_PX_PER_PT,
    DEFAULT_FONT_PT,
    MIN_FONT_PT,
    MAX_FONT_PT,
    clamp,
    asNumber,
    ptToMm,
    mmToPt,
    ptToCssPx,
    cssPxToPt,
    estimateFontPtFromHeightMm,
    normalizeImportedFontPt,
};
