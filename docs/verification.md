# Verification Workflow

This guide explains how to re‑run the universal field extractor, verify the coordinates it produced, and interpret the accuracy report. Follow these steps before shipping any template or after modifying extraction logic.

## Phase 1 strict-alignment smoke (2026-05-19)

Scope: spot-check original-request UX changes after confidence-alignment fixes.

### Auto-verified via live fetch (`https://pdftimesaver.desktopmasters.com`)

- `?route=form-management`
  - Title is **Forms Manager**.
  - Wizard shows **Search form** + **Align & fill**.
  - Upload drop zone copy is `Click here to browse or drag and drop a form into this box.`
  - **Form identity** includes `Form Number`, `Form Name`, global font controls.
  - **Form location** is visible under Form identity with helper text.
  - Bottom controls include Export mode `Test form (sample values) | Actual form (live values)`, `Export PDF`, and `Finished`.

- `?route=client&id=<sample>`
  - Header shows back link + `Delete Client`.
  - Top phone is clickable (`tel:`).
  - `Projects (0)` explanation hint appears on page.
  - `Upload PDF Template` and `Field Mappings` links are absent from header.

### Manual checks still required (browser interaction)

1. **Zoom stability (required)**
   - Open `?route=form-management`, load a form, then test browser zoom at 75%, 100%, 125%, 150%.
   - Drag a few fields at each zoom, save, switch pages/reload, confirm overlays stay aligned.

2. **Duplicate-name prompt**
   - On `?route=client&id=...`, set display name to an existing client name.
   - Confirm Yes/No prompt behavior: No restores previous name; Yes saves.

3. **Display-name deletion guard**
   - Clear display name (and company) and blur field.
   - Confirm values restore and client is not removed.

4. **Insert field placement**
   - In alignment step, confirm `Insert field` is at the bottom of the right properties sidebar, not in top toolbar.

## 1. Re‑extract field positions

```
node scripts/re-extract-positions.js <pdf-path> [output-json]
```

Example:

```
node scripts/re-extract-positions.js uploads/t_fl100_gc120.pdf data/t_fl100_gc120_positions.json
```

This regenerates the `<template>_positions.json` file using the current ensemble pipeline (pdf-lib, qpdf decrypt, etc.).

## 2. Run the verifier

```
node scripts/verify-field-positions.js <pdf-path> <positions-json> [--tolerance=MM] [--report=json|csv]
```

Example with tighter tolerance and JSON summary:

```
node scripts/verify-field-positions.js uploads/t_fl100_gc120.pdf data/t_fl100_gc120_positions.json --tolerance=1.5 --report=json
```

### What the verifier prints

- **Sample field names** from both the PDF and the extracted JSON (first 5 entries). If the styles look different (AcroForm vs template names) the tool warns and attempts alias matching automatically.
- **Summary block**: counts for matches, position mismatches, missing fields (in extracted and in PDF), overall accuracy, and the tolerance used.
- **Alias matches**: shows which template keys were paired with PDF field names when they differ.
- **Detailed mismatches**: first 20 differences with actual vs extracted coordinates and the mm delta.
- **Final accuracy banner**: Always printed before returning to the shell, e.g. `🎯 FINAL ACCURACY: 94.3% (Target 92%)`.
- **Optional machine-readable report** when `--report=json|csv` is provided for CI dashboards.

## 3. Interpreting accuracy

- **Target**: ≥ 92% accuracy (matches / total). The total is `matches + mismatches + missing_in_extracted`.
- **Missing in extracted**: PDF field exists but JSON lacks it → rerun extraction or adjust dedupe logic.
- **Missing in actual**: JSON has extra/template-only fields that are not in the PDF → ensure they are mapped correctly.
- **Position mismatch**: Field exists but deviates > tolerance mm → inspect `rect_pdf`, coordinate conversion, or viewer scaling.

## 4. Common recovery steps

1. **Field names differ**: Use the alias log to map template names to actual PDF names. Update extraction or mapping code accordingly.
2. **Many missing fields**: Re-run the ensemble (`scripts/re-extract-positions.js`) and confirm `scripts/utils/coordinate-validator.js` is not stripping coordinates.
3. **Large Y-offsets**: Verify `pdf-lib-extractor.js` is using `pageHeight - yTop` and that no extra conversion runs in PHP.
4. **Encrypted PDFs**: The verifier auto-tries qpdf with common passwords. Add new passwords to the list if needed.

## 5. Keeping history clean

- Commit the updated `data/<template>_positions.json` only after the verifier reports ≥92% accuracy.
- Include the verifier output (or JSON report) in PR descriptions for traceability.

