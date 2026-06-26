# Draft.clio.com vs PDFTimeSaver — Feature & UI Comparison

**Audit date:** June 18, 2026  
**Clio baseline:** `DESIGN SPECS/draft-clio-complete-audit.md` (January 2025 live audit)  
**PDFTimeSaver baseline:** Current codebase (`mvp/views/*`, production at `pdftimesaver.desktopmasters.com/mvp`)  
**Live Browser MCP session:** ✅ **Completed** June 18–19, 2026 — signed-in firm **YOUNGMAN REITSHTEIN, PLC**. Sample matter: **Lenny Alvarez → Case Initiation - Dissolution** (7 documents). **Completion pass June 19:** Security, Plan/Billing/Payment subscription tabs, project edit, interactive flows (§18 in deep dive).

**Primary UI reference:** `DESIGN SPECS/DRAFT_CLIO_UI_DEEP_DIVE.md` (complete audit matrix §19)

**UI deep dive (all pages):** See [`DRAFT_CLIO_UI_DEEP_DIVE.md`](DRAFT_CLIO_UI_DEEP_DIVE.md) for page-by-page layout, components, shells, and alignment matrix.

---

## Executive summary

PDFTimeSaver mirrors Clio Draft’s **multi-stage workflow** (client → project → populate → draft → export/sign). Live inspection confirms Clio uses **two distinct fill UX modes**: **panel-based populate** (`/panels/populate/`) then **visual PDF overlay editing** (`/panels/edit/`). PDFTimeSaver collapses interactive PDF fill into **populate**, which is a deliberate UX difference—not a missing feature.

The **#1 parity gap** remains **electronic signatures** (Clio Sign is live; ours is disabled). PDFTimeSaver **leads** on form authoring (import, extraction, form sets, field manager) and on **populate-stage export / multi-form modes**.

| Area | Clio Draft (live) | PDFTimeSaver | Gap severity |
|------|-------------------|--------------|--------------|
| Client list & search | Card list, 460 active / 176 archived | Table + filters | Low |
| Global Projects view | All / In progress / Review / Completed tabs | Projects list + status column | Low |
| Project hub | Overview + Signed documents tabs; **Go to populate →** | Wizard Project View + form table | Medium |
| **Populate stage** | **Labeled panel fields only** (+ “Repeated N times”) | **Interactive PDF overlay** (+ panel fallback) | **Different design** |
| **Draft stage** | **PDF overlay editor** + Insert + Sign + Download | iframe PDF + custom overlay; Sign **disabled** | Medium–**High** |
| E-signatures | Sign button + Signed documents tab + history | UI only | **High** |
| Client vault | Client tab + drafting header link | Drafting sidebar + client vault UI | Low |
| Admin / templates | Hidden from nav | Forms Manager, Form Sets, etc. in sidebar | N/A (we expose more) |
| Navigation | Dark sidebar (lists) + workflow header (populate/edit) | Light sidebar everywhere | Medium |

---

## 1. Information architecture & navigation

### Clio Draft (`draft.clio.com`) — live June 2026

- **Primary hierarchy:** Clients → Projects → Project overview → Populate → Draft (edit) → Sign/Download.
- **List-page navigation:** **Dark left sidebar** (~same pattern as us): **Clients**, **Projects**; footer: Help, Team, firm name dropdown.
- **Workflow-page navigation:** **Top banner** with logo, **client name link**, chevron, **project name link**, **Client vault** shortcut, user avatar (initials).
- **URL patterns (verified live):**
  - `/clients/active/` — active clients (460)
  - `/clients/archived/` — archived clients (176)
  - `/clients/projects/` — all firm projects with status filters
  - `/clients/info/projects/` — projects for current client
  - `/clients/info/vault/` — client file vault
  - `/clients/info/profile/` — client profile
  - `/clients/info/notes/` — client notes
  - `/clients/project/info/` — project overview (documents + summary)
  - `/clients/project/signatures/` — signed document packages
  - `/panels/populate/` — panel-based data entry
  - `/panels/edit/` — visual PDF drafting

### PDFTimeSaver (`/mvp/?route=…`)

- **Primary hierarchy:** Dashboard → Clients → Matters (Projects) → Fill Out Forms → Drafting.
- **Navigation chrome:** Fixed **200px left sidebar** (`layout_header.php`); no global top app bar; page titles inside content cards.
- **Sidebar routes:**
  - Dashboard, Clients, Projects
  - Settings (forms, documents, fonts)
  - Forms Manager, Form Sets Manager, Field Manager, Firm Information *(admin — no Clio Draft equivalent exposed to end users)*

### Differences

| Element | Clio | PDFTimeSaver |
|---------|------|--------------|
| Entry point after login | Active clients grid | Dashboard with matter/client/document counts |
| Terminology | Client, Project | Client, Matter/Project (mixed copy: “All Matters”, “Back to Matter”) |
| Admin tools in nav | Not visible in Draft UX | Forms Manager, Form Sets, Field Manager, Firm Defaults always in sidebar |
| Breadcrumbs | Header links (client → project → doc) | Partial: drafting has breadcrumb; populate uses “Back to Matter” button |
| Mobile | Responsive (desktop-first forms) | Sidebar collapse + mobile menu toggle |

**Recommendation:** Add stage-aware breadcrumbs on populate and Project View to match Clio’s “where am I” clarity without removing the sidebar.

---

## 2. Client management

### Clio Draft (live)

- **Clients** (`/clients/active/`):
  - Tabs: **Active (460)** | **Archived (176)**
  - Toolbar: search (“Search clients”), **Sort by** dropdown, **Add new client** (blue primary)
  - Rows: bordered **cards** with person icon, client name, **“N Projects, last modified on MM/DD/YY”**
  - Per-row **Active/Archived** status dropdown + kebab menu (⋮)
- **Client detail sub-nav:** Projects | Client vault | Profile | Notes
- **Client vault:** drag/drop upload, “No client files” empty state when empty

### PDFTimeSaver (`clients.php`, `client.php`)

- **Clients table** with:
  - Search box (name/email)
  - **Active / Archived** segmented control with counts
  - Sort options (name, projects, modified, status)
  - Inline **Add New Client** form (display name, email, phone, etc.)
- **Client detail** (`client.php`):
  - Profile fields driven by Field Manager (display name, email, phone, company, address, custom fields)
  - Linked matters list
  - Archive/delete actions

### Parity & gaps

| Feature | Clio (live) | PDFTimeSaver | Notes |
|---------|-------------|--------------|-------|
| Active client list | ✅ Card list | ✅ Table | Clio shows modified date on every row |
| Archived clients | ✅ Separate URL/tab | ✅ Active/Archived tabs | |
| Search | ✅ “Search clients” | ✅ Name/email search | |
| Per-row archive toggle | ✅ Active/Archived dropdown on row | ❌ | We use Actions column / client detail |
| Client notes | ✅ Notes tab | ❌ | Not in our client detail |
| Client profile tab | ✅ Dedicated Profile tab | ✅ Inline on `client.php` | |
| Client vault | ✅ Client tab + drafting header | ✅ Drafting panel | |
| Sort | ✅ “Sort by” link | ✅ Multiple sort keys | |

---

## 3. Projects / matters & document assembly

### Clio Draft (live)

- **Global projects** (`/clients/projects/`):
  - Filter tabs: **All projects (4871)** | **In progress (3520)** | **Review (32)** | **Completed (1319)**
  - Each row: project name, **“Client: {name}”**, last modified, inline status dropdown
- **Client projects** (`/clients/info/projects/`):
  - Search, Sort, **Add new project**
  - Rows with **In progress / Review / Completed** dropdown per project
- **Project overview** (`/clients/project/info/`) — sample: *Case Initiation - Dissolution*:
  - Header: client link › project title
  - Tabs: **Overview** | **Signed documents**
  - **Duplicate** project button
  - **Summary** card: Edit link, status dropdown, project name, **Responsible attorney** (e.g. Ava Jahanvash)
  - **Documents** section: **+ Add/remove documents**, list of 7 docs (FL-100, FL-110, RA-010, etc.)
  - Primary CTA: **“Go to populate →”** (not “Go to drafting”)
- **Signed documents** (`/clients/project/signatures/`):
  - Lists signature packages (“Petition, Summons… sent on 2025-09-15 **Closed**”)
  - Copy: “To sign new documents click on the **Sign** button in the drafting area.”

### PDFTimeSaver (`projects.php`, `project.php`)

- **Projects list (`projects.php`):**
  - “All Matters” table: Matter, Client, Status, Last Modified, Actions
  - Search, Browse mode, **New Project**
  - Complete/Reopen on list **hidden** (recent change); View only
- **Project View (`project.php`)** — wizard-style setup:
  1. Select **client** (whole-row picker)
  2. Enter **case number** + case library field values
  3. Choose **Form Set** (bundled templates)
  4. **Additional Forms** beyond the set
  5. Ordered form list with per-row actions → **Fill Out Forms**
  - Autosave on config changes; trash icon to delete project
  - Footer **Back / Next** between setup sections

### Differences

| Feature | Clio (live) | PDFTimeSaver |
|---------|-------------|--------------|
| Global project list w/ status filters | ✅ Dedicated nav item | ⚠️ Status column only |
| Responsible attorney on project | ✅ Summary field | ❌ Not on Project View |
| Duplicate project | ✅ Button | ❌ |
| Signed documents tab | ✅ With send history | ❌ |
| Document assembly | + Add/remove on overview | Form Set + Additional Forms wizard |
| Entry to fill workflow | **Go to populate →** | **Fill Out Forms** per row |
| Project creation | Add new project (client context) | New Project + client picker in wizard |

**Gap:** Clio’s project page is a **single hub** (documents + status + go draft). Our Project View is a **setup wizard** then a form table — closer for Phase 1 but less “one screen” than Clio.

---

## 4. Fill out forms (Populate stage)

### Clio Draft (`/panels/populate/`) — **live**

- **NOT a type-on-PDF interface** at this stage — strictly **labeled panel fields** in a scrollable form.
- Top workflow bar: **← Back to select documents** | **Go to drafting →**
- Same workflow header as edit: client › project, Client vault link.
- Fields grouped with labels; many show **“×N Repeated N times”** (one value maps to N PDF instances).
- Live sample fields: Attorney Name, Law Firm Name, State Bar #, address block, Client/Defendant/Petitioner/Respondent names, **Select court** button, Court Branch, Case Number, County, etc.
- Firm defaults pre-filled (YOUNGMAN REITSHTEIN, PLC; Ava Jahanvash; bar #330371; etc.).

### PDFTimeSaver (`populate.php`)

### PDFTimeSaver (`populate.php`)

- **Two rendering modes:**
  1. **Interactive PDF preview** — type on rendered page backgrounds; overlay fields positioned from extraction JSON; **autosave** (~900ms debounce).
  2. **Panel fallback** — grouped fields with pagination / focused view when no preview assets.
- **Right sidebar (Field properties)** — added June 2026: label, font size +/- when field selected (Clio importer parity subset).
- **Display modes:** Single Form Mode / All Forms Mode.
- **Footer action bar** (Merlin spec):
  - Scope: This Form / All Forms
  - **Export** (pdf for single; merged for all)
  - **Next** / **Complete** (mark document) / **Finish** (return to Project View)
- **Temp custom fields** on preview (purple dashed boxes, draggable).
- Top: form selector dropdown, Save Form, Back Form, Back to Matter.

### Comparison — populate stage

| Feature | Clio (live) | PDFTimeSaver |
|---------|-------------|--------------|
| Primary fill UX | **Panel fields only** | **Interactive PDF overlay** (when positions exist) |
| Type-on-PDF | ❌ At populate; ✅ at **edit** | ✅ At populate |
| Repeated-field mapping UI | ✅ “×7 Repeated 7 times” labels | ⚠️ Hidden in mapping layer |
| Court picker | ✅ “Select court” button | Case library text fields |
| Navigation out | **Go to drafting →** | Export / Next / Complete / Finish |
| Back link label | **Back to select documents** | Back to Matter / Back Form |
| Client vault access | Header link on populate | Not on populate header |
| Autosave | Implied | ✅ Explicit AJAX |
| Multi-form modes | Per-project doc set | Single Form / All Forms + scoped Export |
| Field properties sidebar | ❌ Not on populate | ✅ Right panel (font size) |

**Important architectural difference:** Clio separates **data entry (populate)** from **visual PDF editing (edit)**. We merged visual fill into populate, which may be **better for users** but **does not match Clio’s two-step mental model**. Consider offering a “Panel mode” toggle defaulting to match Clio for firms migrating from Draft.

---

## 5. Drafting / edit stage

### Clio Draft (`/panels/edit/`) — **live**

- **Visual PDF editor:** rendered form image with **absolute-positioned textboxes** over the PDF (FL-100 visible in session).
- Left strip: **Insert** heading, **Add custom field** (+ icon).
- Top actions: **← Back to populate** | **Sign** | **Download** | status dropdown (In progress / Review / Completed).
- Right sidebar: **Documents (7)** list + **Add/Remove** — same docs as project overview.
- Client vault accessible from workflow header (not visible in snapshot body but linked in banner).
- Sign and Download are **active** (not disabled).

### PDFTimeSaver (`drafting.php`)

### PDFTimeSaver (`drafting.php`)

- **Header:** Matches Clio pattern closely (`pdftimesaver-drafting-header`).
  - ← Back to populate | Insert | breadcrumb (client → project → form)
  - Status dropdown: in_progress / review / completed
  - Download | Sign
- **Layout:** Document list panel | PDF iframe + custom field overlay | Client vault panel
- **Insert custom field** modal (text, textarea, checkbox, date, number).
- **Download:** triggers `?route=actions/generate&pd=…`
- **Sign:** **disabled** — `title="Digital signing will be available soon"` (`layout_footer.php`)

### Comparison

| Feature | Clio (live) | PDFTimeSaver |
|---------|-------------|--------------|
| PDF visual edit | ✅ Native overlay on form scan | ✅ iframe + custom field overlay |
| Insert custom field | ✅ Left Insert panel | ✅ Insert button + modal |
| Document switcher | ✅ Right sidebar (7 docs) | ✅ Left document list panel |
| Sign | ✅ **Active** | ❌ **Disabled** |
| Download | ✅ Header | ✅ Header (triggers generate) |
| Signed docs history | ✅ Project **Signed documents** tab | ❌ |
| Status in header | ✅ Dropdown | ✅ Dropdown |

**Critical gap:** Electronic signatures. Clio Draft’s Sign button is a core end-stage action; ours is placeholder UI only.

---

## 6. Export, merge, and download

### Clio Draft

- Download from drafting header; combined PDF output; print-oriented.
- Sign path for executed documents.

### PDFTimeSaver

- **Populate export:** Footer Export with scope (this form PDF vs merged all forms).
- **Generate action:** `actions/generate` produces output PDF; download routes for signed/unsigned output.
- **Drafting Download:** Regenerate/download from header.
- Automated verification tooling (internal QA — not in Clio).

| Capability | Clio | PDFTimeSaver |
|------------|------|--------------|
| Single form PDF | ✅ | ✅ |
| Merged multi-form PDF | ✅ | ✅ |
| Export from fill stage | ❌ (draft stage) | ✅ |
| Signed PDF output path | ✅ | ⚠️ Route exists; signing not wired |

---

## 7. Status & progress tracking

### Clio Draft

- Project/document status: **In progress**, **Review**, **Completed**.
- Visible in project detail and drafting header dropdown.

### PDFTimeSaver

- **Project status** dropdown in drafting header (same three values).
- **Document status** updated via Fill Out **Complete** button (`actions/update-document-status`).
- Projects list shows status column; list-level Complete/Reopen temporarily hidden.

| Feature | Clio | PDFTimeSaver |
|---------|------|--------------|
| Three-state model | ✅ | ✅ |
| Per-document complete | ✅ | ✅ Fill Out Complete |
| List bulk complete | Unknown | Hidden in UI (backend remains) |

---

## 8. Form templates & admin (PDFTimeSaver-only surface)

These are **not** end-user Clio Draft screens but are required to operate a Clio-like practice in our product:

| Tool | Route | Purpose |
|------|-------|---------|
| Universal Processor | `universal-processor` | Import PDF, extract fields, map positions, Field properties sidebar |
| Form Management | `form-management` | Template CRUD |
| Form Sets Manager | `form-sets-manager` | Bundle forms for matters (FL family sets, etc.) |
| Field Manager | `field-manager` | Client/case/firm field definitions |
| Firm Defaults | `firm-defaults` | Firm-wide default values |
| Alias Manager | `alias-manager` | Field alias mapping |
| Font Settings | `font-settings` | Typography defaults |

**Clio equivalent:** Managed inside Clio’s product/template library — not exposed as a customer-facing “Forms Manager.”

**Implication:** Our sidebar exposes **operator complexity** Clio hides. Consider role-based nav (attorney vs admin) for Phase 2.

---

## 9. Visual design & UI components

### Clio Draft (observed patterns)

- Professional legal SaaS aesthetic; top bar; white content; card/grid clients.
- Consistent primary actions in header (Download, Sign).
- Form fields: consistent sizing; multi-column where needed.
- Desktop-first long-form scrolling.

### PDFTimeSaver

- **Design system** (`layout_header.php`, `UI_STYLES_REFERENCE.md`):
  - Sidebar: `#f8f9fa`, 200px, blue active state `#007bff`
  - Cards: `.pdftimesaver-card`, workflow progress chips
  - Buttons: tiers — `.pdftimesaver-btn-action` (42px), `.pdftimesaver-btn-sm` / icon (30px)
  - Forms: `.pdftimesaver-input`, `.pdftimesaver-form-group`
- **Populate preview:** Gray `#eef2f7` canvas, blue overlay fields, sticky field properties panel (300px).
- **Drafting:** Dedicated header bar CSS mirroring Clio spec comments in code.

### UI differences to close

| Pattern | Clio | PDFTimeSaver | Action |
|---------|------|--------------|--------|
| Global header | Top bar everywhere | Sidebar only | Optional thin top bar on workflow pages |
| Primary blue | Clio brand | `#007bff` / `#2563eb` mix | Unify primary palette |
| Button placement | Header-right actions | Populate footer bar + scattered saves | Aligned with Merlin footer spec ✅ |
| Empty states | Minimal | Emoji + helper text | OK for MVP |
| Field properties panel | On importer | On populate preview ✅ | Done June 2026 |

---

## 10. Workflow journey (side-by-side)

```
CLIO DRAFT (live)                    PDFTIMESAVER
───────────────                      ────────────
/clients/active/                     Dashboard or Clients
        │                                    │
        ▼                                    ▼
/clients/info/projects/              Client detail → matters
  (or /clients/projects/ global)            │
        │                                    ▼
/clients/project/info/               Project View (wizard setup)
  Overview | Signed documents               │
  [Go to populate →]                        │
        │                                    ▼
/panels/populate/                    populate.php
  PANEL FIELDS ONLY                         INTERACTIVE PDF (+ panels)
  [Go to drafting →]                        Export / Next / Complete / Finish
        │                                    │
        ▼                                    ▼
/panels/edit/                        drafting.php
  PDF OVERLAY EDITOR                        iframe + overlay
  Sign ✅ | Download ✅                     Sign ❌ | Download ✅
        │                                    │
        ▼                                    ▼
/clients/project/signatures/         (no equivalent yet)
  signature package history
```

---

## 11. Family law forms (FL-100 ecosystem)

### Clio Draft (prior audit)

- Strong FL family law library: FL-150, FL-142, FL-141, FL-100-related bundles.
- Documents added per project from template list.
- Large field count (266+ on complex forms).

### PDFTimeSaver

- FL-100 / FL-105 / FL-150 extraction pipeline with qpdf; position JSON in `data/`.
- Form Sets can mirror Clio bundles.
- Interactive populate depends on extracted positions + page backgrounds.
- Overlay fill for encrypted/complex PDFs; native fill where supported.

| Form | Clio | PDFTimeSaver |
|------|------|--------------|
| FL-100 | ✅ | ✅ Enhanced extraction |
| FL-105 | ✅ | ✅ |
| FL-150 / FL-142 / FL-141 | ✅ In library | ⚠️ As imported templates / sets |
| W-9 | N/A | ✅ 100% auto extraction |

---

## 12. Priority gap list (for Phase 1 parity)

### P0 — Blockers for Clio parity

1. **Electronic signatures** — Clio has live Sign + Signed documents tab with package history; ours disabled.
2. **Signed documents view** — Add project tab or page for sent/completed signature packages.

### P1 — UX parity (informed by live audit)

3. **Split fill UX optional mode** — Clio populate = panels only; consider default panel mode for migrating users.
4. **“Go to populate” naming** — Our Project View uses “Fill Out Forms”; align CTA language if desired.
5. **Global Projects filters** — Clio has All / In progress / Review / Completed tabs at `/clients/projects/`.
6. **Responsible attorney** field on project summary.
7. **Duplicate project** action.
8. **Client Notes** tab.
9. **Per-row client Active/Archived** dropdown on clients list.
10. **Repeated-field indicator** — Show “maps to N places” like Clio’s “×7 Repeated” in populate panels.

### P2 — Polish

11. Dark/light sidebar consistency — Clio list pages use dark nav; workflow pages use white + top bar.
12. Client vault link on populate workflow header.
13. Terminology pass (Matter vs Project).
14. Role-based sidebar hiding admin tools.

### Already strong / ahead of Clio Draft

- PDF import + field extraction + visual position editor
- Form Sets Manager
- Interactive populate with autosave and font controls
- All Forms Mode + scoped export
- Firm/case/client field mapping infrastructure
- Automated verification / QA tooling

---

## 14. Live audit log (Browser MCP — June 18, 2026)

**Session:** Chrome Browser MCP, firm **YOUNGMAN REITSHTEIN, PLC**, user initials **MK**.  
**Sample client/project:** Lenny Alvarez → **Case Initiation - Dissolution** (7 documents).

### 14.1 Clients (`/clients/active/`)

- **460** active, **176** archived clients.
- UI pattern: vertical **card rows** (not data table), each with person icon.
- First row example: `"Tammy" Thao Thanh Nguyen` — 12 Projects, modified 04/27/26.
- Controls: search box, Sort by, Add new client (primary blue button).
- Per-client **Active/Archived** dropdown inline (unique vs our archive-in-actions pattern).

### 14.2 Global Projects (`/clients/projects/`)

- Scale: **4871** total projects (**3520** in progress, **32** review, **1319** completed).
- Row format: `{Project name} Client: {Client name}, last modified on {date}`.
- Status editable inline via dropdown on each row.

### 14.3 Client → Projects (`/clients/info/projects/`)

- Sub-nav: Projects (2) | Client vault | Profile | Notes (0).
- Lenny Alvarez sample: **Case Initiation - Dissolution**, **Request Dismissal** — both In progress.

### 14.4 Project Overview (`/clients/project/info/`)

- Tabs: Overview | Signed documents.
- Summary: Status, Project name, **Responsible attorney: Ava Jahanvash**.
- Documents: FL-100 [Without Children], FL-110 Summons, Certificate of Assignment, RA-010 notices, etc.
- CTA: **Go to populate →** (confirmed — not “Go to drafting” on this screen).

### 14.5 Populate (`/panels/populate/`)

- **Panel-only** data entry — no PDF preview at this step.
- Navigation: ← Back to select documents | **Go to drafting →**.
- Pre-filled firm/attorney defaults from firm profile.
- Field repetition UX: `×7 Repeated\a 7 times` on Attorney Name, Defendant Name, etc.
- Court selection uses **Select court** button (not free text only).

### 14.6 Draft / Edit (`/panels/edit/`)

- Full **PDF overlay** editing (textboxes positioned on form image).
- Toolbar: Insert / Add custom field | Back to populate | **Sign** | **Download** | status.
- Documents sidebar lists all 7 project documents with Add/Remove.
- This is where Clio puts type-on-PDF — **not** on populate.

### 14.7 Signed Documents (`/clients/project/signatures/`)

- Package: “Petition, Summons, and Supporting Docs for Signature” — To Lenny Alvarez, Ava Jahanvash — sent 2025-09-15 — **Closed**.
- Instruction text points users to Sign in drafting for new sends.

### 14.8 Client Vault (`/clients/info/vault/`)

- Empty state: “No client files” — drag here or Browse.

---

## Appendix A — Live session capture table

| Page | URL | Captured | Key delta vs PDFTimeSaver |
|------|-----|----------|---------------------------|
| Active clients | `/clients/active/` | ✅ | Card list + per-row archive dropdown; we use table |
| Global projects | `/clients/projects/` | ✅ | Status filter tabs with counts; we lack global filtered view |
| Client projects | `/clients/info/projects/` | ✅ | Client sub-tabs (Vault, Profile, Notes) |
| Project overview | `/clients/project/info/` | ✅ | **Go to populate**; Responsible attorney; Duplicate |
| Populate | `/panels/populate/` | ✅ | **Panels only** — we use PDF overlay at fill stage |
| Draft edit | `/panels/edit/` | ✅ | PDF overlay + **live Sign** |
| Signed docs | `/clients/project/signatures/` | ✅ | Signature history — **we have no equivalent** |
| Client vault | `/clients/info/vault/` | ✅ | Same concept as our drafting vault |

---

## Appendix B — Key PDFTimeSaver files

| Stage | File |
|-------|------|
| Layout / nav | `mvp/views/layout_header.php` |
| Dashboard | `mvp/views/dashboard.php` |
| Clients | `mvp/views/clients.php`, `client.php` |
| Projects | `mvp/views/projects.php`, `project.php` |
| Fill out | `mvp/views/populate.php` |
| Drafting | `mvp/views/drafting.php` |
| Form sets | `mvp/views/form_sets_manager.php` |
| Import / map | `mvp/views/universal_processor.php` |
| Prior Clio audit | `DESIGN SPECS/draft-clio-complete-audit.md` |

---

*End of comparison document.*
