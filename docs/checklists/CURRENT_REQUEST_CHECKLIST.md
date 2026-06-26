# Request tracking checklist (active items only)

This file now contains only non-finished items. Finished items were moved to `docs/checklists/CURRENT_REQUEST_FINISHED.md`.

**Started:** 2026-06-25  
**Environment:** https://pdftimesaver.desktopmasters.com/mvp/  
**Request batch timestamp:** 2026-06-25 12:50 PM (UTC-7)  
**Test project / context:** Projects, Project View, Fill Out Forms bug pass (new bug batch)  
**Sync verification rule (user):** after save, verify production at 5s, then 10s, then 15s, then 30s; if still not live, defer and retry next pass.

---

## Active Items

No open active items in this batch after live verification pass.

### 2026-06-25 Late verification closure

| REQ | Status | Verification outcome |
|-----|--------|----------------------|
| REQ-031 | Done — Verified Live | Court help icons render custom tooltip help text without duplicate native title tooltip; placement adjusted to avoid pointer overlap. |
| REQ-035 | Done — Verified Live | Populate overlay text smoothing/sharpening rule is live on production. |
| REQ-042 | Done — Verified Live | Import/fill sizing consistency verified: Form Management global size is `15` and Populate overlays resolve to `15px` on sampled project forms. |
| REQ-052 | Done — Verified Live | Second sharpening pass is live (`kerning/ligatures/smoothing/text-rendering` declarations active in computed style). |

**Scope audit:** Full cross-checklist inventory → `docs/checklists/SCOPE_CATALOGUE.md`.

## Latest bag/tag pass (2026-06-25 PM)
_Late verification pass complete; newly verified items were rotated to `CURRENT_REQUEST_FINISHED.md`._

## Latest bag/tag pass (2026-06-25 7:49 PM)
_Final stabilization patch complete; no new open items. Overflow/font indicator/save-banner fixes were rotated to `CURRENT_REQUEST_FINISHED.md`._

## Populate header guardrail (do-not-regress)

- On `?route=populate`, keep a **single top header only**. Do not render a second in-page title card/header block.
- Header title must be `Populate Form - <project name>` in `.pdftimesaver-content-title`.
- Keep `Document / Form / Display` in one compact row directly below the top title.
- Verification step before sign-off: confirm no secondary `<h2>` populate title exists and visually confirm one merged header area in production.
