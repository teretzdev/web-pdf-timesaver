#!/usr/bin/env node
/**
 * Fill positioned PDF fields directly on source PDF pages using pdf-lib.
 * This keeps the PDF content vector-based and generated text selectable.
 */

const fs = require('fs');
const { PDFDocument, StandardFonts, rgb } = require('pdf-lib');
const fieldMetrics = require('./utils/field-metrics');

const MM_TO_PT = fieldMetrics.PT_PER_MM;

function mmToPt(mm) {
  return Number(mm || 0) * MM_TO_PT;
}

function normalizeBool(value) {
  const v = String(value ?? '').trim().toLowerCase();
  if (v === '') return false;
  return !['0', 'off', 'no', 'false', 'unchecked', 'none', 'null'].includes(v);
}

function fitText(font, text, fontSizePt, maxWidthPt) {
  if (!text) return '';
  if (font.widthOfTextAtSize(text, fontSizePt) <= maxWidthPt) return text;
  let out = text;
  while (out.length > 1 && font.widthOfTextAtSize(out + '…', fontSizePt) > maxWidthPt) {
    out = out.slice(0, -1);
  }
  return out + '…';
}

async function run() {
  const sourcePdf = process.argv[2];
  const assignmentsPath = process.argv[3];
  const outputPdf = process.argv[4];
  if (!sourcePdf || !assignmentsPath || !outputPdf) {
    throw new Error('Usage: node fill-pdf-positions-js.js <source.pdf> <assignments.json> <output.pdf>');
  }
  if (!fs.existsSync(sourcePdf)) throw new Error(`Source PDF not found: ${sourcePdf}`);
  if (!fs.existsSync(assignmentsPath)) throw new Error(`Assignments JSON not found: ${assignmentsPath}`);

  const assignments = JSON.parse(fs.readFileSync(assignmentsPath, 'utf8'));
  if (!Array.isArray(assignments)) throw new Error('Assignments JSON must be an array');

  const pdfDoc = await PDFDocument.load(fs.readFileSync(sourcePdf), {
    ignoreEncryption: true,
    updateMetadata: false,
  });
  const pages = pdfDoc.getPages();
  const regularFont = await pdfDoc.embedFont(StandardFonts.Helvetica);
  const boldFont = await pdfDoc.embedFont(StandardFonts.HelveticaBold);

  let placed = 0;
  for (const row of assignments) {
    const pageNum = Number(row.page || 1);
    const page = pages[pageNum - 1];
    if (!page) continue;

    const xMm = Number(row.x || 0);
    const yMm = Number(row.y || 0);
    const widthMm = Math.max(1, Number(row.width || 10));
    const heightMm = Math.max(1, Number(row.height || 4));
    const type = String(row.fieldType || 'text').toLowerCase();
    const value = row.value;

    const pageHeightPt = page.getHeight();
    const xPt = mmToPt(xMm);
    const boxTopPt = pageHeightPt - mmToPt(yMm);
    const boxHeightPt = mmToPt(heightMm);
    const boxWidthPt = mmToPt(widthMm);

    if (['checkbox', 'check', 'radio', 'radiobutton', 'button', 'btn'].includes(type)) {
      if (normalizeBool(value)) {
        const size = Math.max(7, Math.min(12, boxHeightPt * 0.9));
        const drawY = boxTopPt - size - Math.max(0, (boxHeightPt - size) / 2);
        page.drawText('X', {
          x: xPt + Math.max(0, (boxWidthPt - size) / 2),
          y: drawY,
          size,
          font: boldFont,
          color: rgb(0, 0, 0),
        });
        placed++;
      }
      continue;
    }

    const raw = String(value ?? '').trim();
    if (!raw) continue;

    const preferredPt = Number(row.fontSize || fieldMetrics.DEFAULT_FONT_PT);
    let fontSize = Math.max(fieldMetrics.MIN_FONT_PT, preferredPt);
    const maxHeightSize = Math.max(6, boxHeightPt * 0.9);
    if (fontSize > maxHeightSize) fontSize = maxHeightSize;
    const font = String(row.fontStyle || '').toUpperCase().includes('B') ? boldFont : regularFont;
    const text = fitText(font, raw, fontSize, Math.max(2, boxWidthPt - 2));

    const baseline = boxTopPt - Math.max(fontSize, boxHeightPt * 0.75);
    page.drawText(text, {
      x: xPt + 1,
      y: baseline,
      size: fontSize,
      font,
      color: rgb(0, 0, 0),
    });
    placed++;
  }

  fs.writeFileSync(outputPdf, await pdfDoc.save());
  console.log(JSON.stringify({ success: true, placed, assignments: assignments.length }));
}

run().catch((err) => {
  console.error(JSON.stringify({ success: false, error: err.message }));
  process.exit(1);
});

