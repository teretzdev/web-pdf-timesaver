#!/usr/bin/env node
/**
 * Automated field position verification (headless)
 * - Uses PDF.js to read text items and validate field placements
 * - Scores proximity to likely labels, page bounds, overlap, and size sanity
 * - Emits a JSON report with overall pass/fail and per-field diagnostics
 *
 * Usage:
 *   node scripts/auto-verify-positions.js <pdf-file> <positions-json> [templateId]
 *
 * Output:
 *   data/<templateId>_verification_report.json (or derived from positions file name)
 */

const fs = require('fs');
const path = require('path');
const { pathToFileURL } = require('url');

function readJson(p) { return JSON.parse(fs.readFileSync(p, 'utf8')); }

function inferFieldsArray(objOrArr) {
    if (Array.isArray(objOrArr)) return objOrArr;
    if (objOrArr && typeof objOrArr === 'object') return Object.values(objOrArr);
    return [];
}

async function loadPdfTextItems(pdfPath) {
    const mod = await import('pdfjs-dist/legacy/build/pdf.mjs');
    const pdfjs = mod.default || mod;
    const pdfjsDistPath = path.dirname(require.resolve('pdfjs-dist/package.json'));
    const standardFontDataUrl = pathToFileURL(path.join(pdfjsDistPath, 'standard_fonts') + path.sep).href;
    // Disable worker in Node
    const buffer = fs.readFileSync(pdfPath);
    const data = new Uint8Array(buffer);
    const pdf = await pdfjs.getDocument({
        data,
        useWorker: false,
        standardFontDataUrl
    }).promise;
    const byPage = new Map();

    for (let pageNum = 1; pageNum <= pdf.numPages; pageNum++) {
        const page = await pdf.getPage(pageNum);
        const viewport = page.getViewport({ scale: 1.0 });
        const textContent = await page.getTextContent();
        const items = [];
        for (const it of textContent.items) {
            const [a, b, c, d, e, f] = it.transform;
            const fontSize = Math.hypot(a, b);
            const width = it.width || (it.str.length * fontSize * 0.5);
            const height = fontSize;
            const x = e;
            const yFromBottom = f;
            const y = viewport.height - yFromBottom; // top-origin
            items.push({ text: it.str, x, y, width, height, page: pageNum });
        }
        byPage.set(pageNum, { items, viewport });
    }
    return byPage;
}

function mmToPoints(mm) { return mm / 0.352778; }

function distance(a, b) {
    const dx = a.x - b.x;
    const dy = a.y - b.y;
    return Math.hypot(dx, dy);
}

function computeLabelScore(field, pageItems) {
    // Heuristic: look for nearby text items that look like labels for the field
    const labelHints = [
        'name', 'address', 'email', 'phone', 'date', 'signature', 'case', 'number', 'petitioner', 'respondent', 'ssn'
    ];
    const page = field.page || 1;
    const items = (pageItems.get(page) || {}).items || [];

    // Convert field mm to points (top-origin assumed already)
    const fx = mmToPoints(field.x);
    const fy = mmToPoints(field.y);
    const neighborhood = items
        .filter(it => Math.abs(it.y - fy) < 60 && Math.abs(it.x - fx) < 240);

    let best = 0;
    for (const it of neighborhood) {
        const text = String(it.text || '').toLowerCase();
        for (const hint of labelHints) {
            if (text.includes(hint)) {
                // closer and left-of/above gets higher weight
                const d = distance({ x: fx, y: fy }, it);
                const dirBonus = (it.x <= fx ? 1.0 : 0.8) * (it.y <= fy ? 1.0 : 0.9);
                const score = Math.max(0, 1 - (d / 300)) * dirBonus; // normalized 0..1 roughly
                if (score > best) best = score;
            }
        }
    }
    return best; // 0..1
}

function computeBoundsScore(field, pageSizeMm = { width: 215.9, height: 279.4 }) {
    const pad = 2; // mm
    const inBounds =
        field.x >= pad && field.y >= pad &&
        (field.x + field.width) <= (pageSizeMm.width - pad) &&
        (field.y + field.height) <= (pageSizeMm.height - pad);
    return inBounds ? 1 : 0;
}

function overlaps(a, b) {
    return !(a.x + a.width <= b.x || b.x + b.width <= a.x || a.y + a.height <= b.y || b.y + b.height <= a.y);
}

function computeOverlapPenalty(field, fieldsOnSamePage) {
    let overlapCount = 0;
    for (const other of fieldsOnSamePage) {
        if (other === field) continue;
        if (overlaps(field, other)) overlapCount++;
    }
    if (overlapCount === 0) return 1;
    if (overlapCount === 1) return 0.6;
    return 0.3; // heavy penalty if many overlaps
}

function computeSizeSanity(field) {
    const kind = String(field.type || '').toLowerCase();
    const width = Number(field.width || 0);
    const height = Number(field.height || 0);

    if (kind === 'checkbox' || kind === 'radio') {
        if (width >= 2 && width <= 8 && height >= 2 && height <= 8) return 1;
        if (width >= 1.5 && width <= 10 && height >= 1.5 && height <= 10) return 0.8;
        return 0.5;
    }

    if (width >= 8 && width <= 180 && height >= 2.5 && height <= 25) return 1;
    if (width >= 4 && width <= 220 && height >= 2 && height <= 40) return 0.8;
    return 0.5;
}

function overallScore(field, components) {
    // Weighted average; de-emphasize label for checkbox/radio.
    const { label, bounds, overlap, size } = components;
    const kind = String(field.type || '').toLowerCase();
    let wLabel = 0.45;
    let wBounds = 0.3;
    let wOverlap = 0.15;
    let wSize = 0.1;

    if (kind === 'checkbox' || kind === 'radio') {
        wLabel = 0.1;
        wBounds = 0.45;
        wOverlap = 0.25;
        wSize = 0.2;
    } else if (label === 0) {
        // Some text fields do not have nearby text labels.
        wLabel = 0.2;
        wBounds = 0.45;
        wOverlap = 0.2;
        wSize = 0.15;
    }

    return wLabel * label + wBounds * bounds + wOverlap * overlap + wSize * size;
}

function computeVerdict(field, score, components) {
    const confidence = Number(field.confidence || 0);
    const geometryStrong = components.bounds >= 1 && components.overlap >= 1 && components.size >= 1;
    // Some valid fields (especially long free-text areas) have weak label proximity.
    // If geometry and extraction confidence are strong, treat as pass.
    if (score >= 0.75 || (score >= 0.6 && geometryStrong && confidence >= 0.95)) {
        return 'pass';
    }
    if (score >= 0.6) {
        return 'warn';
    }
    return 'fail';
}

async function main() {
    const pdfPath = process.argv[2];
    const positionsPath = process.argv[3];
    const templateIdArg = process.argv[4];

    if (!pdfPath || !positionsPath) {
        console.log('Usage: node scripts/auto-verify-positions.js <pdf-file> <positions-json> [templateId]');
        process.exit(1);
    }
    if (!fs.existsSync(pdfPath)) {
        console.error('❌ PDF not found:', pdfPath);
        process.exit(1);
    }
    if (!fs.existsSync(positionsPath)) {
        console.error('❌ Positions JSON not found:', positionsPath);
        process.exit(1);
    }

    const templateId = templateIdArg || path.basename(positionsPath, path.extname(positionsPath)).replace(/_positions$/, '');
    const fields = inferFieldsArray(readJson(positionsPath));
    const pageItems = await loadPdfTextItems(pdfPath);

    const perField = [];
    const byPage = new Map();
    for (const f of fields) {
        const page = f.page || 1;
        if (!byPage.has(page)) byPage.set(page, []);
        byPage.get(page).push(f);
    }

    for (const f of fields) {
        const comps = {
            label: computeLabelScore(f, pageItems),
            bounds: computeBoundsScore(f),
            overlap: computeOverlapPenalty(f, byPage.get(f.page || 1) || []),
            size: computeSizeSanity(f)
        };
        const score = Number(overallScore(f, comps).toFixed(3));
        perField.push({
            name: f.name,
            page: f.page || 1,
            x: f.x, y: f.y, width: f.width, height: f.height,
            type: f.type,
            confidence: f.confidence,
            score,
            components: comps,
            verdict: computeVerdict(f, score, comps)
        });
    }

    const avgScore = perField.reduce((s, r) => s + r.score, 0) / Math.max(1, perField.length);
    const fails = perField.filter(r => r.verdict === 'fail').length;
    const warns = perField.filter(r => r.verdict === 'warn').length;
    const pass = avgScore >= 0.75 && fails === 0;

    const report = {
        templateId,
        pdf: path.basename(pdfPath),
        positionsFile: path.basename(positionsPath),
        generatedAt: new Date().toISOString(),
        summary: {
            fields: perField.length,
            avgScore: Number(avgScore.toFixed(3)),
            fails,
            warns,
            pass
        },
        results: perField
    };

    const outDir = path.join('data');
    if (!fs.existsSync(outDir)) fs.mkdirSync(outDir, { recursive: true });
    const outPath = path.join(outDir, `${templateId}_verification_report.json`);
    fs.writeFileSync(outPath, JSON.stringify(report, null, 2));
    console.log(pass ? '✅ Verification PASS' : '⚠️  Verification WARN/FAIL');
    console.log('📄 Report:', outPath);
}

main().catch(err => { console.error('❌ Verification error:', err); process.exit(1); });


