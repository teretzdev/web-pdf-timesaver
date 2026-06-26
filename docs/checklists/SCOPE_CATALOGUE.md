# Scope catalogue — all tracked work

**Purpose:** Single inventory of everything we recorded as requested, verified, or shipped — across archived checklists and the 2026-06-24 REQ batch. Use this to audit **scope creep**: items marked ⚠️ or 🔶 went beyond the literal ask or grew into subsystem work.

**Last consolidated:** 2026-06-25  
**Primary sources:**
- `docs/checklists/CURRENT_REQUEST_FINISHED.md` (REQ-001 … REQ-029)
- `docs/checklists/archive/2026-06-24/*`
- `docs/checklists/archive/2026-06-24/phase1-checklist.txt`

**Active tracker:** `CURRENT_REQUEST_CHECKLIST.md` (empty — all batch items finished)

---

## How to read this

| Column | Meaning |
|--------|---------|
| **ID** | Stable catalogue id (prefix = source checklist) |
| **Source** | Where the item was first logged |
| **Status** | Done · Open · Skipped · Infra |
| **Scope** | ✅ explicit / in-spec · ⚠️ expanded interpretation · 🔶 creep suspect · 📋 ops/docs |
| **REQ** | Link to REQ-* id when applicable |
| **Notes** | What shipped or what to question |

### Scope tags (quick)

| Tag | Use when |
|-----|----------|
| ✅ | User (or phase spec) clearly asked for this |
| ⚠️ | Reasonable fix but **larger** than the words of the request |
| 🔶 | **Suspect** — duplicate, inferred, or major subsystem not spelled out in original brief |
| ⏸ | Skipped or still open |
| 📋 | Deployment / verification / docs, not product feature |

---

## Summary by area

| Area | Done (approx.) | Open | Creep flags (🔶/⚠️) |
|------|----------------|------|---------------------|
| Form Importer / Forms Manager | 25+ | 2 | 3 |
| Form Sets Manager | 12 | 0 | 0 |
| Projects list | 8 | 0 | 2 |
| Project View | 35+ | 0 | 4 |
| Fill Out Forms (Populate) | 40+ | 0 | 6 |
| Field Manager / Client / Firm | 20+ | 0 | 2 |
| Attorney management | 1 feature set | 0 | 2 |
| Infra / deploy | — | — | — |

---

## A. 2026-06-24 request batch (REQ-*)

Canonical detail: `CURRENT_REQUEST_FINISHED.md`. Below is the **audit view**.

| REQ | Area | Original ask (short) | Scope | Status | Creep / notes |
|-----|------|-------------------|-------|--------|----------------|
| REQ-001 | Projects | How many projects before load more? | ⚠️ | Done | Became **20/page numbered pagination** (not just an answer). |
| REQ-002 | Project View | Rename Back to Projects → Back | ✅ | Done | Label only. |
| REQ-003 | Projects | Make white buttons blue | ✅ | Done | Projects page only. |
| REQ-004 | Projects | Remove Browse; search default | ✅ | Done | Aligns with UIB-PRJ-01 later pass (Browse removed again). |
| REQ-005 | Projects | Matter → Project Name | ✅ | Done | Copy change. |
| REQ-006 | Projects | Active vs Completed filter | ✅ | Done | New filter UI. |
| REQ-007 | Project View | Make all buttons blue | ⚠️ | Done | Overlaps REQ-003; “all buttons” is vague — applied to **Project View** actions. |
| REQ-008 | Project View | State / Federal court labels | ✅ | Done | + tooltip scope (all states, not CA-only). |
| REQ-009 | Project View | Help icon tooltips | ✅ | Done | |
| REQ-010 | Project View | Court field order | ✅ | Done | |
| REQ-011 | Populate | Static right panel | ✅ | Done | Match Form Importer. |
| REQ-012 | Populate / export | Font size messed up | ⚠️ | Done | Touched **preview + pdf_form_filler export**, not populate-only. |
| REQ-013 | Populate | Default 6 vs 7 | ✅ | Done | Single constant change. |
| REQ-014 | Importer / Populate / export | 7pt looks wrong everywhere | 🔶 | Done | **Shared metrics subsystem** (`field_metrics.php`, JS module, extractor alignment) — largest engineering expansion in batch. |
| REQ-015 | Populate | Red/overflow/shrink behavior | ✅ | Done | |
| REQ-016 | Populate | Font size indicator sync | ✅ | Done | |
| REQ-017 | Populate | Red + block at limit | ✅ | Done | Duplicate of REQ-015 (kept for traceability). |
| REQ-018 | Populate | Remove Use This Value | ✅ | Done | Auto-apply on select. |
| REQ-019 | Populate | Remove Clear | ✅ | Done | |
| REQ-020 | Populate | Remove custom box → trash icon | ✅ | Done | |
| REQ-021 | Populate | Trash next to Add Custom Input | ✅ | Done | |
| REQ-022 | Populate | Drop + from button label | ✅ | Done | |
| REQ-023 | Populate | Back on first form → Project View | ✅ | Done | |
| REQ-024 | Populate | Remove Clear (again) | ✅ | Done | Duplicate of REQ-019. |
| REQ-025 | Field Mgr / Firm / Project / Populate | Fix garbage Attorney Information category | 🔶 | Done | Started as **dropdown cleanup**; shipped as **full attorney stack** (see § G). |
| REQ-026 | Populate | Saved message layout shift | ✅ | Done | Top saved banner removed; status handling kept non-displacing to prevent layout jump. |
| REQ-027 | Populate | Compress header; one-line Document/Form/Display | ✅ | Done | |
| REQ-028 | Populate | Title includes project name | ✅ | Done | |
| REQ-029 | Firm / Project | Attorney DB + Project snapshot | ✅ | Done | User-directed follow-up; merged with REQ-025 delivery. |

### REQ batch — likely scope creep (review these)

1. **REQ-001** — Question → full pagination product change.  
2. **REQ-007 + REQ-003** — Two “blue buttons” entries; confirm both pages were intended.  
3. **REQ-012 + REQ-014** — Font complaints → cross-app metrics architecture.  
4. **REQ-025 + REQ-029** — Complaint about dropdown → Field Manager step + `mvp_attorneys` + APIs + Project View card + autofill pipeline.  
5. **REQ-017 / REQ-024** — Duplicate tickets; no extra code, but noise in tracking.

---

## B. Form Importer / Forms Manager

**Source:** `archive/2026-06-24/CURRENT_PHASE1_CHECKLIST.md`, `phase1-checklist.txt`, `PHASE1_NON_HIGH_CONFIDENCE.md`

| ID | Item | Scope | Status | REQ | Notes |
|----|------|-------|--------|-----|-------|
| P1-01 | Export opens in browser + Adobe | ✅ | **Open** | — | Re-checked live: export route still returns `Content-Disposition: attachment` for this-form PDF, so browser-inline expectation remains unmet. |
| P1-02 | Fix oversized text in large boxes after import | ✅ | **Open** | — | Reopened: user-provided screenshot shows oversized/clipped court text; patched populate font seeding + legacy all-15 reset, pending post-sync production verification. |
| P1-03 | Add New Field drop preview anywhere on page | ✅ | Done | — | |
| P1-04 | Remove duplicate Selected: in right panel | ✅ | Done | — | |
| P1-05 | Remove standalone Export label | ✅ | Done | — | |
| P1-06 | Back / Export / Finish same height | ✅ | Done | — | |
| P1-07 | Smaller Export PDF button | ✅ | Done | — | |
| P1-08 | Remove whitespace under Test/Actual dropdown | ✅ | Done | — | |
| P1-09 | Finish resets Select/Upload state | ✅ | Done | — | |
| P1-10 | Finish saves form info to DB | ✅ | Done | — | |
| P1-11 | Add New Form on dedicated page | ✅ | Done | — | |
| P1-12 | Wizard Upload → Align → Values → Export → Finished | ✅ | Done | — | phase1-checklist.txt |
| P1-13 | Custom fields table scaffold (Firm/Client/Case) | ✅ | Done | — | **Attorney location added later** — not in original P1 table. |
| P1-14 | Drag-drop field overlays + save positions | ✅ | Done | — | |
| P1-15 | Test vs Actual export routes | ✅ | Done | — | |
| P1-16 | MySQL entity migration / DataStore dual-write | ⚠️ | Done | — | Infra breadth beyond UI checklist. |
| P1-17 | Insert Field at bottom of properties sidebar | ✅ | Done | — | PHASE1_NON_HIGH_CONFIDENCE |
| P1-18 | Form location on Form identity + autosave | ✅ | Done | — | |
| P1-19 | Browser zoom 75–150% overlay alignment | ✅ | **Open** | — | Still pending explicit manual browser zoom pass at 75/100/125/150. |
| P1-20 | Sample Text field type | 📋 | Done | — | `docs/SAMPLE_TEXT_FIELD_TYPE.md` |
| P1-21 | Forms Manager vs Universal Processor split | 📋 | Done | — | `docs/FORMS_MANAGER_VS_UNIVERSAL_PROCESSOR.md` |

---

## C. Form Sets Manager

**Source:** `archive/2026-06-24/FORM_SETS_SIGNOFF_CHECKLIST.md` — all ✅ Done (2026-06-04 sign-off)

| ID | Item | Scope | Status |
|----|------|-------|--------|
| FSM-01 | Page at `?route=form-sets-manager` | ✅ | Done |
| FSM-02 | Phase 1 global sets only | ✅ | Done |
| FSM-03 | Preset + modify + save-as | ✅ | Done |
| FSM-04 | Search page: Add / Search / Browse | ✅ | Done |
| FSM-05 | Listed set opens edit mode | ✅ | Done |
| FSM-06 | Edit = Add with prefilled data | ✅ | Done |
| FSM-07 | Top name input on Add/Edit | ✅ | Done |
| FSM-08 | Search forms + View / Add to List | ✅ | Done |
| FSM-09 | Import Form round-trip auto-add | ✅ | Done |
| FSM-10 | Reorder + persist order | ✅ | Done |
| FSM-11 | View from selected list | ✅ | Done |
| FSM-12 | Finish saves + return to search | ✅ | Done |

**Creep note:** None flagged — tightly matched written requirements.

---

## D. Projects list

**Sources:** `PROJECTS_CHECKLIST.md`, `PROJECTS_UI_BUGS_CHECKLIST.md`, REQ batch

| ID | Item | Scope | Status | REQ | Notes |
|----|------|-------|--------|-----|-------|
| PRJ-01 | Search for project | ✅ | Done | — | |
| PRJ-02 | Browse lists all projects | ✅ | Done → **removed** | REQ-004 | Scope changed mid-stream: Browse was built, then removed per REQ-004. |
| PRJ-03 | New Project flow | ✅ | Done | — | |
| PRJ-04 | Open project from list | ✅ | Done | — | |
| PRJ-05 | Remove Back to Dashboard | ✅ | Done | — | UIB |
| PRJ-06 | Search as magnifying-glass button | ✅ | Done | — | UIB |
| PRJ-07 | Fix search box width | ✅ | Done | — | UIB |
| PRJ-08 | Matter → Project Name | ✅ | Done | REQ-005 | |
| PRJ-09 | Active / Completed / All filter | ✅ | Done | REQ-006 | |
| PRJ-10 | Blue secondary buttons + pagination | ✅ | Done | REQ-003, REQ-001 | |
| PRJ-11 | 20 per page numbered pagination | ⚠️ | Done | REQ-001 | See REQ-001 creep note. |
| PRJ-12 | [Complete] button purpose | ✅ | Done | — | Added Projects-page guidance text clarifying Active/Completed state usage to remove ambiguity around completion behavior. |

---

## E. Project View

**Sources:** `PROJECTS_CHECKLIST.md`, `PROJECTS_UI_BUGS_CHECKLIST.md`, REQ batch

| ID | Item | Scope | Status | REQ | Notes |
|----|------|-------|--------|-----|-------|
| PV-01 | Project Name editable + autosave | ✅ | Done | — | |
| PV-02 | Client Select/Change/Edit + returnTo | ✅ | Done | — | |
| PV-03 | Case Select/Change + case library reuse | ✅ | Done | — | |
| PV-04 | Case number top input + live summary | ✅ | Done | — | |
| PV-05 | Court section (search, State/Federal, fields) | ✅ | Done | REQ-008–010 | |
| PV-06 | Form Set search/browse/select | ✅ | Done | — | |
| PV-07 | Additional Forms append/reorder/remove | ✅ | Done | — | |
| PV-08 | Next → populate when client+case+form set | ✅ | Done | — | Gating relaxed vs early “must save doc” rule. |
| PV-09 | Delete project (trash) | ✅ | Done | — | UIB |
| PV-10 | Empty title on new project | ✅ | Done | — | UIB |
| PV-11 | Remove Sync Name | ✅ | Done | — | UIB |
| PV-12 | Links → button styling | ✅ | Done | REQ-007 | |
| PV-13 | Whole-row selectable (client/case/form set) | ✅ | Done | — | UIB |
| PV-14 | Rename section Additional Forms | ✅ | Done | — | UIB |
| PV-15 | Remove explicit Save; autosave-as-you-go | ⚠️ | Done | — | **Architectural** — UIB merged save into Back; large behavior change. |
| PV-16 | Back + Next on same line; Back label | ✅ | Done | REQ-002 | |
| PV-17 | Remove Additional Project Fields | ✅ | Done | — | Not in user outline — removal = scope alignment. |
| PV-18 | Sidebar: Projects only (no duplicate Project View) | ✅ | Done | — | |
| PV-19 | **Attorney** section (roster pick + snapshot) | 🔶 | Done | REQ-025, REQ-029 | **Not in June PROJECTS_CHECKLIST** — added 2026-06-24. |
| PV-20 | Help tooltips on court controls | ✅ | Done | REQ-009 | |

---

## F. Fill Out Forms (Populate)

**Sources:** `PROJECTS_CHECKLIST.md` (incl. Expanded Request), REQ batch

| ID | Item | Scope | Status | REQ | Notes |
|----|------|-------|--------|-----|-------|
| POP-01 | Form renders with project context | ✅ | Done | — | Was broken; fixed in re-audit. |
| POP-02 | Title header + form dropdown | ✅ | Done | — | |
| POP-03 | Next/Back between forms | ✅ | Done | — | |
| POP-04 | All-forms display mode | ⚠️ | Done | — | Expanded Request — confirm still wanted. |
| POP-05 | Manual font size + auto-shrink 1pt steps | ✅ | Done | REQ-015, 016 | |
| POP-06 | Red overflow at minimum size | ✅ | Done | REQ-015, 017 | |
| POP-07 | Temporary custom fields add/move/resize | ⚠️ | Done | — | Expanded Request — **Clio Populate has no equivalent** (known divergence). |
| POP-08 | Autosave on type/blur | ✅ | Done | — | |
| POP-09 | Export This Form / All Forms + PDF/ZIP/merged | 🔶 | Done | — | **Large subsystem** in Expanded Request; verify against Clio (export in action bar only). |
| POP-10 | Static right sidebar | ✅ | Done | REQ-011 | |
| POP-11 | Default font 7pt | ✅ | Done | REQ-013 | |
| POP-12 | Shared font metrics with importer/export | 🔶 | Done | REQ-012, 014 | |
| POP-13 | Remove Use This Value / Clear | ✅ | Done | REQ-018, 019, 024 | |
| POP-14 | Custom box trash + placement | ✅ | Done | REQ-020, 021, 022 | |
| POP-15 | Back on first form → Project View | ✅ | Done | REQ-023 | |
| POP-16 | Non-displacing save status | ✅ | Done | REQ-026 | Top saved banner removed + non-displacing save feedback. |
| POP-17 | Compressed header; Document/Form/Display one row | ✅ | Done | REQ-027 | |
| POP-18 | Title: Populate Form - {project name} | ✅ | Done | REQ-028 | |
| POP-19 | Attorney Information preset group | ⚠️ | Done | REQ-025 | Uses Field Manager `attorney` location. |
| POP-20 | Single merged populate header (no duplicate title card) | ✅ | Done | REQ-040 | Guardrail: never stack a second in-page populate header under the global header; keep one compact merged header area only. |
| POP-21 | Sidebar density pass (compact palette + remove field key + `?` tooltips) | ✅ | Done | REQ-048 | Removed inline help blocks and color code text, added compact help icons, and tightened color swatches into one row. |
| POP-22 | All Forms mode parity with Single mode layout | ⚠️ | Done | REQ-045 | Removed top list chrome and switched all-forms rendering to stacked interactive pages end-to-end in template order; verified live. |
| POP-23 | Empty-field shading in Populate preview | ✅ | Done | REQ-049 | Added configurable empty-field highlight (`--fillout-empty-field-bg`) for unfilled text/checkbox overlays. |
| POP-24 | Connect-to-saved-field pointer persistence + dropdown reflection | ✅ | Done | REQ-050 | Added per-field mapping pointer persistence and field-selection sync for sidebar mapping dropdowns. |
| POP-25 | All Forms non-current template rendering regression fix | ✅ | Done | REQ-051 | Corrected populate all-mode uploads path resolution (`mvp/views` -> `mvp/uploads`) so all project templates render, not just current form. |
| POP-26 | Overlay text sharpening pass #2 | ✅ | Done | REQ-052 | Applied additional typography rendering tuning for overlay inputs and verified live on production. |

---

## G. Attorney management (cross-cutting)

**Sources:** REQ-025, REQ-029, user follow-up conversation

| ID | Component | Scope | Status | Notes |
|----|-----------|-------|--------|-------|
| ATTY-01 | Field Manager step: Attorney Information Fields | ✅ | Done | User-directed 2026-06-24. |
| ATTY-02 | Protected attorney fields + matching tags | ✅ | Done | |
| ATTY-03 | Firm Information roster UI (add/save/remove) | ✅ | Done | |
| ATTY-04 | `mvp_attorneys` storage + CRUD + APIs | ⚠️ | Done | **Heavier than “fix dropdown”** — full entity layer. |
| ATTY-05 | Project View card + config snapshot | ✅ | Done | |
| ATTY-06 | Populate autofill prefers project attorney | ✅ | Done | |
| ATTY-07 | Production browser verify | ✅ | Done — Verified Live | Production proof completed: created attorney, saved to project snapshot, deleted from roster, reloaded project, confirmed attorney snapshot persisted while roster entry stayed removed. |

**Creep summary:** Original REQ-025 was UI/data-quality on a **dropdown category**. Delivered a **mini-CRM for attorneys** — justified by later explicit direction (REQ-029), but 🔶 relative to first message.

---

## H. Field Manager, Client View, Firm Defaults

**Source:** `CHECKLIST_FIELD_MANAGER_CLIENT_FIRM_DEFAULTS.md`

| ID | Item | Scope | Status | Notes |
|----|------|-------|--------|-------|
| FMC-1.1 | Protected fields cannot delete | ✅ | Done | API 409 |
| FMC-1.2 | Protected: edit display name | ✅ | Done | |
| FMC-1.3 | Protected: edit matching tag | ✅ | Done | |
| FMC-1.4 | Client new field display name persists | ✅ | Done | |
| FMC-2.1–2.12 | Client View layout + permanent fields + autosave | ✅ | Done | See archive §2 table |
| FMC-3.1–3.2 | Firm Defaults layout + persistence | ✅ | Done | |
| FMC-4.1 | **Attorney** Field Manager location | 🔶 | Done | **Post-dates** May 2025 FMC sign-off; extends P1-13 scaffold. |
| FMC-4.2 | Firm Defaults attorney roster section | 🔶 | Done | REQ-029 |

---

## I. Infrastructure & verification (not product scope)

| ID | Item | Scope | Status | Source |
|----|------|-------|--------|--------|
| INF-01 | Syncthing 5/10/15/30s verify rule | 📋 | Active | CURRENT_REQUEST_CHECKLIST |
| INF-02 | Production deploy checklist | 📋 | Reference | DEPLOYMENT_CHECKLIST.md |
| INF-03 | MySQL PDO vs JSON fallback | 📋 | Ongoing | FMC sign-off, phase1-checklist |

---

## J. Master index: REQ → catalogue IDs

| REQ | Catalogue IDs |
|-----|----------------|
| REQ-001 | PRJ-11 |
| REQ-002 | PV-16 |
| REQ-003 | PRJ-10 |
| REQ-004 | PRJ-02 |
| REQ-005 | PRJ-08 |
| REQ-006 | PRJ-09 |
| REQ-007 | PV-12 |
| REQ-008–010 | PV-05 |
| REQ-011 | POP-10 |
| REQ-012 | POP-12, P1-* |
| REQ-013 | POP-11 |
| REQ-014 | POP-12, P1-* |
| REQ-015–017 | POP-05, POP-06 |
| REQ-018–024 | POP-13, POP-14 |
| REQ-023 | POP-15 |
| REQ-025–029 | ATTY-*, FMC-4.*, PV-19, POP-19 |
| REQ-026–028 | POP-16, POP-17, POP-18 |
| REQ-045 | POP-22 |
| REQ-049 | POP-23 |
| REQ-050 | POP-24 |
| REQ-051 | POP-25 |
| REQ-052 | POP-26 |

---

## K. Scope creep review checklist (for you)

Use this short pass when reviewing new work:

- [ ] **Was it in an archived checklist or REQ with ✅ user ask?** If not, flag 🔶.
- [ ] **Does it duplicate an existing item?** (e.g. blue buttons ×2, Clear ×2, attorney REQ-025 vs 029.)
- [ ] **Does it match Clio scope?** Populate custom fields, export ZIP/merged, extra workflow chrome = known divergences — see `.cursor/rules/clio-scope-reference.mdc`.
- [ ] **Is it a question that became a feature?** (REQ-001 pagination.)
- [ ] **Is it a bug report that became architecture?** (REQ-014 metrics modules; ATTY-04 entity layer.)
- [ ] **Populate header check:** one merged top header only (`Populate Form - <project name>`); no duplicate in-page `h2` title card.
- [ ] **Open items still on the books:** P1-01, P1-02, P1-19.

---

## L. File map (where to update next)

| File | Role |
|------|------|
| `SCOPE_CATALOGUE.md` | **This file** — master audit list |
| `CURRENT_REQUEST_FINISHED.md` | REQ batch detail + resulting actions |
| `CURRENT_REQUEST_CHECKLIST.md` | Active items only (empty) |
| `archive/2026-06-24/*` | Historical checklists (frozen) |
| `archive/README.md` | Index into archive + catalogue |

When new work lands: add a row here with Scope tag, link REQ if applicable, then append to FINISHED or CHECKLIST.
