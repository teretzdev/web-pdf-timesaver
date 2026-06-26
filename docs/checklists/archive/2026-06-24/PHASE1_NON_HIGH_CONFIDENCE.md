# Phase 1 — Confidence follow-ups (after 2026 alignment pass)

Most items from `PHASE1_NON_HIGH_CONFIDENCE.md` are **addressed in code or docs**. Below is what **still** needs human verification or is **intentional** scope.

## Addressed in product / docs

| Topic | What we did |
|-------|-------------|
| **Form location** | Visible again at **bottom of Form identity** (below search hint), with border separator. **`form_location`** included in position autosave. Search listing / managed form meta / hint include location again. |
| **Insert Field** | Moved to **bottom of the Field properties sidebar** (below Field Editor), full-width button + help text. Same `id`s so existing JS still attaches. |
| **Auto-match / legend** | Preview legend labels + `title` tooltips explain detected vs catalog-linked fields; sidebar note copy clarified. |
| **Add Custom field (assign)** | No separate “Add custom field” control on the assign step (upload vs assign were different features). Re-verify in UI if needed. |
| **Sample Text vs brief** | Documented: `docs/SAMPLE_TEXT_FIELD_TYPE.md`. |
| **Forms Manager vs UP** | Documented: `docs/FORMS_MANAGER_VS_UNIVERSAL_PROCESSOR.md`. |
| **Client — display vs company revert** | Code comment in `client.php` explains why we restore when **both** are empty; company still backfills display when appropriate. |

## Product decisions from user (latest)

| Item | Decision |
|------|----------|
| **Original-request alignment mode** | **Strict match to original request** (`1a`). |
| **Client email at top (`mailto:` vs plain text)** | Resolved to **plain text** for strict original-request scope (no extra enhancement). |

## Still manual / non-code

| Topic | Notes |
|-------|--------|
| **Browser zoom** | Hooks may exist; **confirm** at 75%–150% zoom and a mobile width that overlays track PDF positions. |
| **Verification checklist rows** | Still **N/A** by design in `CURRENT_PHASE1_CHECKLIST.md` — run smoke tests before release. |
| **Client email at top (`mailto:` vs plain text)** | Closed: keep plain text (no extra behavior beyond original brief). |

Historical detail: see git history and earlier `PHASE1_NON_HIGH_CONFIDENCE.md` if you archived it.
