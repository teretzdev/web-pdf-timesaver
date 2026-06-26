# Current Request - Finished Items

Filtered from `docs/checklists/CURRENT_REQUEST_CHECKLIST.md` to include only items with `Status: Done`.

**Filtered on:** 2026-06-24  
**Browser verification:** Completed on production (`https://pdftimesaver.desktopmasters.com/mvp/`)  
**Sync rule used:** 5s -> 10s -> 15s -> 30s  
**Scope catalogue:** `docs/checklists/SCOPE_CATALOGUE.md` (master list + creep flags)

---

### REQ-002

| Field | Content |
|-------|---------|
| **Original request** | Rename [Back to Projects] to [Back] |
| **Interpreted action** | Update button label text from `Back to Projects` to `Back` on Projects-related screens where that label appears. |
| **Confidence** | High |
| **Status** | Done |
| **Resulting actions** | Updated footer button label in `mvp/views/project.php` from `Back to Projects` to `Back`. |

---

### REQ-004

| Field | Content |
|-------|---------|
| **Original request** | What is the [Browse] button being used for? All the projects are visible by default. Search should remove the browse box. [Search] should be the default. (Case in the Project View does this correctly) |
| **Interpreted action** | Remove or repurpose redundant `Browse` control on Projects page, default to search-first UX, and match Case selector behavior used on Project View. |
| **Confidence** | High |
| **Status** | Done |
| **Resulting actions** | Removed Projects `Browse` mode/button and kept search-first flow in `mvp/views/projects.php`. |

---

### REQ-005

| Field | Content |
|-------|---------|
| **Original request** | Change "Matter" to "Project Name" |
| **Interpreted action** | Replace visible UI copy `Matter` with `Project Name` in applicable Projects and Project View labels/headers. |
| **Confidence** | High |
| **Status** | Done |
| **Resulting actions** | Replaced Matter terminology on Projects page (header/count/empty state/table heading) in `mvp/views/projects.php`. |

---

### REQ-006

| Field | Content |
|-------|---------|
| **Original request** | There is no filter system to filter between Active and Completed projects. |
| **Interpreted action** | Add project status filtering UI and logic for `Active` vs `Completed` on Projects list view. |
| **Confidence** | High |
| **Status** | Done |
| **Resulting actions** | Added `Active / Completed / All` status filter UI and filtering logic in `mvp/views/projects.php`. |

---

### REQ-007

| Field | Content |
|-------|---------|
| **Original request** | Make all buttons blue. |
| **Interpreted action** | Apply blue button style consistently to Project View page actions. |
| **Confidence** | High |
| **Status** | Done |
| **Resulting actions** | Applied blue styling to Project View secondary/icon actions in `mvp/views/project.php`. |

---

### REQ-008

| Field | Content |
|-------|---------|
| **Original request** | Rename "CA state courts" to "State" and "U.S. federal courts" to "Federal" (Note: State will be all state courts not just CA) |
| **Interpreted action** | Update court filter labels to `State` and `Federal`, and align data/source assumptions so state means all states, not only California. |
| **Confidence** | High |
| **Status** | Done |
| **Resulting actions** | Renamed UI labels to `State` and `Federal` and updated helper text/tooltips to explicitly indicate statewide scope (not CA-only) in `mvp/views/project.php`. |

---

### REQ-009

| Field | Content |
|-------|---------|
| **Original request** | What is the help icon for? Nothing happens when I hover over it. I like it... but we should put something helpful there as an example... |
| **Interpreted action** | Implement meaningful tooltip/help content and ensure hover/focus behavior works for the Project View help icon. |
| **Confidence** | High |
| **Status** | Done |
| **Resulting actions** | Added fallback tooltip/help text and ensured help icon always has meaningful content in `mvp/views/project.php`. |

---

### REQ-010

| Field | Content |
|-------|---------|
| **Original request** | Fix the order of the fields. For example "Zip" is separated from the address fields. "Street Address" should be under "Branch" and before City. |
| **Interpreted action** | Reorder court/address inputs so street address appears under branch and zip remains grouped with city/state in the address block. |
| **Confidence** | High |
| **Status** | Done |
| **Resulting actions** | Reordered court field rendering priority (branch/address/city/state/zip grouping) in `mvp/views/project.php`. |

---

### REQ-011

| Field | Content |
|-------|---------|
| **Original request** | Make the right panel static like it is on the Form Importer |
| **Interpreted action** | Make Fill Out Forms right sidebar fixed/static to match Form Importer interaction model. |
| **Confidence** | High |
| **Status** | Done |
| **Resulting actions** | Made Fill Out Forms sidebar static/pinned layout in `mvp/views/populate.php`. |

---

### REQ-013

| Field | Content |
|-------|---------|
| **Original request** | It is defaulting to 6 where the form inporter says it is 7. |
| **Interpreted action** | Align default font size baseline between Form Importer and Fill Out Forms (resolve 6 vs 7 mismatch). |
| **Confidence** | High |
| **Status** | Done |
| **Resulting actions** | Updated default fill font baseline from 6 to 7 (`$filloutDefaultFontPx` and `defaultFontPx`) in `mvp/views/populate.php`. |

---

### REQ-015

| Field | Content |
|-------|---------|
| **Original request** | Type a bunch of text.. Text turns red but keeps typing. It should stop when it hits the limit and then turn red. Do not turn red before that.. Text does not step down to continue fitting in form field. It just jumps to super small. |
| **Interpreted action** | Enforce hard input cap at field limit, trigger red warning only at limit, and improve overflow shrink behavior to reduce abrupt font jumps. |
| **Confidence** | High |
| **Status** | Done |
| **Resulting actions** | Added hard cap + red-at-limit handling and tuned auto-shrink to step by one size increment per input event for smoother behavior in `mvp/views/populate.php`. |

---

### REQ-016

| Field | Content |
|-------|---------|
| **Original request** | Font Size Indicator does not change when font shrinks from overtyping. |
| **Interpreted action** | Keep font size indicator synchronized with runtime auto-shrink value during typing/overflow. |
| **Confidence** | High |
| **Status** | Done |
| **Resulting actions** | Synced size indicators to runtime font by updating `refreshFontSizeIndicator()` in `mvp/views/populate.php` and verified against overtyping shrink behavior. |

---

### REQ-017

| Field | Content |
|-------|---------|
| **Original request** | When you hit the limit of text to type in a cell have the text turn red and block the ability to type more. |
| **Interpreted action** | Duplicate/confirm limit behavior request: block additional typing at max length and show red at limit. |
| **Confidence** | High |
| **Status** | Done |
| **Resulting actions** | Implemented max-length block + red warning at limit via `enforceNoOverflow()` logic in `mvp/views/populate.php`. |

---

### REQ-018

| Field | Content |
|-------|---------|
| **Original request** | Remove the [Use This Value] button and just use it. |
| **Interpreted action** | Remove explicit `Use This Value` action and auto-apply selected field value immediately. |
| **Confidence** | High |
| **Status** | Done |
| **Resulting actions** | Removed `Use this value` button and auto-apply selected preset value on dropdown change in `mvp/views/populate.php`. |

---

### REQ-019

| Field | Content |
|-------|---------|
| **Original request** | Remove the [Clear] button |
| **Interpreted action** | Remove `Clear` button from Fill Out Forms controls. |
| **Confidence** | High |
| **Status** | Done |
| **Resulting actions** | Removed right-panel `Clear` control in `mvp/views/populate.php`. |

---

### REQ-020

| Field | Content |
|-------|---------|
| **Original request** | Make the [Remove custom box] button into a trash can. |
| **Interpreted action** | Replace text-style `Remove custom box` control with trash-can icon treatment. |
| **Confidence** | High |
| **Status** | Done |
| **Resulting actions** | Replaced custom box remove text action with trash-can icon button in `mvp/views/populate.php`. |

---

### REQ-021

| Field | Content |
|-------|---------|
| **Original request** | Put the Remove custom box trash can next to, to the right of the [+Add Custom Input Box] button. Make it disabled when not in use. |
| **Interpreted action** | Place trash icon button directly right of add-custom-box button and disable it when no custom box is selected. |
| **Confidence** | High |
| **Status** | Done |
| **Resulting actions** | Moved trash action next to `Add Custom Input Box` and disabled it unless custom box is selected in `mvp/views/populate.php`. |

---

### REQ-022

| Field | Content |
|-------|---------|
| **Original request** | Rename [+Add Custom Input Box] to [Add Custom Input Box] |
| **Interpreted action** | Remove leading plus sign from add custom input box button label. |
| **Confidence** | High |
| **Status** | Done |
| **Resulting actions** | Renamed button label from `+ Add Custom Input Box` to `Add Custom Input Box` in `mvp/views/populate.php`. |

---

### REQ-023

| Field | Content |
|-------|---------|
| **Original request** | [Back] button missing on first form that will take you back to the Project View. |
| **Interpreted action** | Ensure back navigation button is available on the first form and routes to Project View. |
| **Confidence** | High |
| **Status** | Done |
| **Resulting actions** | Back button now always shown and routes to Project View when no previous form exists in `mvp/views/populate.php`; browser-verified by navigation to project page. |

---

### REQ-024

| Field | Content |
|-------|---------|
| **Original request** | Remove [Clear] Button.. (not needed?) |
| **Interpreted action** | Duplicate/confirm `Clear` removal request; keep only once in implementation scope but retain both original entries for traceability. |
| **Confidence** | High |
| **Status** | Done |
| **Resulting actions** | Tracked duplicate clear-button request; implemented through the same clear-control removal in `mvp/views/populate.php`. |

---

### REQ-026

| Field | Content |
|-------|---------|
| **Original request** | Stop pushing the form down every time it says "Saved". maybe not show that at all? |
| **Interpreted action** | Prevent layout shift from saved-status messaging; either make non-displacing toast/status or suppress message entirely. |
| **Confidence** | High |
| **Status** | Done |
| **Resulting actions** | Changed save status to non-displacing toast and suppressed routine `Saved` text shifts in `mvp/views/populate.php`. |

---

### REQ-027

| Field | Content |
|-------|---------|
| **Original request** | The way it scrolls up and down between the header and footer is wonky... Lets compress the header a bit and stop that from happening. make "Document:", "Form:", "Display:" all on one line. |
| **Interpreted action** | Reduce header height and refactor top controls so Document/Form/Display share one row, minimizing scroll jump between header/footer zones. |
| **Confidence** | High |
| **Status** | Done |
| **Resulting actions** | Compressed top area and placed `Document`, `Form`, `Display` controls on one row in `mvp/views/populate.php`. |

---

### REQ-028

| Field | Content |
|-------|---------|
| **Original request** | Change the "Populate Form" line to read "Populate Form - <project name>" |
| **Interpreted action** | Update Fill Out Forms title line to include active project name after dash. |
| **Confidence** | High |
| **Status** | Done |
| **Resulting actions** | Updated heading to `Populate Form - <project name>` in `mvp/views/populate.php` and verified on production. |

---

### REQ-001

| Field | Content |
|-------|---------|
| **Original request** | How many projects do we show at one time before we load more on scroll? |
| **Interpreted action** | Add project list paging on the Projects page: 20 projects per page with numbered pagination (prev/next + page bar). |
| **Confidence** | Medium |
| **Status** | Done |
| **Resulting actions** | Implemented offset paging (20 per page) in `mvp/index.php` and replaced Load More with a numbered pagination bar (prev/next arrows, current page marker, ellipsis for long ranges, “Page X of Y”) in `mvp/views/projects.php`; verified live on production using sync cadence. |

---

### REQ-003

| Field | Content |
|-------|---------|
| **Original request** | Make all the white buttons Blue |
| **Interpreted action** | Apply requested blue styling to secondary/action buttons on Projects page controls. |
| **Confidence** | Medium |
| **Status** | Done |
| **Resulting actions** | Added scoped blue styles for Projects secondary/action buttons and pagination controls (search/clear/view, page numbers, prev/next) in `mvp/views/projects.php`; verified visually on production. |

---

### REQ-012

| Field | Content |
|-------|---------|
| **Original request** | The font size on the form is messed up. |
| **Interpreted action** | Fix Fill Out font sizing inconsistencies between preview and export behavior. |
| **Confidence** | Medium |
| **Status** | Done |
| **Resulting actions** | Updated populate preview fallback sizing to seed from extracted pt metadata and refactored export font resolution in `mvp/lib/pdf_form_filler.php` through shared field metrics so generated PDF sizing follows normalized pt logic. |

---

### REQ-014

| Field | Content |
|-------|---------|
| **Original request** | The 7 on the form importer is much LARGER than 7 on where you edit the form. (Honestly I think the sizing on the form inporter may be messed up) because 7 is typically small. and I think forms are normally between 10 and 11. |
| **Interpreted action** | Centralize font-unit policy and normalization across importer/editor/export/extraction paths. |
| **Confidence** | Medium |
| **Status** | Done |
| **Resulting actions** | Added shared metrics modules (`mvp/lib/field_metrics.php`, `scripts/utils/field-metrics.js`), wired importer preview normalization in `mvp/views/universal_processor.php`, export unit handling in `mvp/lib/pdf_form_filler.php`, and aligned extractor scripts/methods to shared constants/estimation policy. |

---

### REQ-025

| Field | Content |
|-------|---------|
| **Requested at** | 2026-06-24 4:57 PM (UTC-7) |
| **Original request** | In the field selector dropdown I see a category for "Attorney Information" but all the fields under that are kind of garbage.. and there is no tab on the Fields Manager for them and there is no section on the Project View to fill in. |
| **Interpreted action** | Add proper Attorney Information field location in Field Manager, firm attorney roster management, Project View attorney section (court-style snapshot), and wire populate/importer autofill to project attorney values. |
| **Confidence** | High |
| **Status** | Done |
| **Resulting actions** | Implemented full attorney management: **Field Manager** — new wizard step “Attorney Information Fields” (`location: attorney`) with protected fields (name, bar #, firm, address, phone, fax, email) and AttyInfo matching tags in `mvp/views/field_manager.php` + `mvp/lib/data.php`. **Firm Information** — attorney roster DB manager (add/save/remove) at bottom of `mvp/views/firm_defaults.php`; roster stored in `mvp_attorneys` via CRUD in `mvp/lib/data.php`; APIs `api/attorneys/list`, `upsert`, `delete` in `mvp/index.php`. **Project View** — Attorney card between Case and Form Set (select/browse roster, editable fields, autosave) in `mvp/views/project.php`; config keys `attorneyValues`, `selectedAttorneyId`, `selectedAttorneyName` persisted via `actions/save-project-view-config`. **Populate** — project attorney snapshot preferred over firm defaults for Atty* autofill; preset group “Attorney Information Fields” in `buildPopulateManagerPresetGroups()`. Deleting a roster attorney does not remove saved project copies. _Production browser verification completed (create -> attach -> delete roster entry -> reload persistence proof)._

---

### REQ-029

| Field | Content |
|-------|---------|
| **Requested at** | 2026-06-24 (follow-up attorney direction) |
| **Original request** | At bottom of Firm Information, create a DB manager to add/remove Attorneys. Store data in permanent fields mappable in Field Manager as “Attorney Information”. Show attorney on Project View between Case and Form Set (like Court). Copy attorney data into the project so deleting a roster attorney leaves project data intact. |
| **Interpreted action** | Same scope as REQ-025 implementation pass — delivered as one attorney-management feature set. |
| **Confidence** | High |
| **Status** | Done |
| **Resulting actions** | Delivered together with REQ-025; see REQ-025 **Resulting actions** for file/route summary. |

---

## 2026-06-25 Late Verification Rotation

| REQ | Status | Tags | Resulting actions (short) |
|-----|--------|------|---------------------------|
| REQ-031 | Done — Verified Live | `project-view` `tooltips` `court` | Confirmed court help tooltip messaging and placement behavior on production; no duplicate native tooltip. |
| REQ-035 | Done — Verified Live | `populate` `typography` `rendering` | Confirmed smoothing/sharpening declarations are active for populate overlay text on production. |
| REQ-042 | Done — Verified Live | `populate` `typography` `sizing` | Verified Form Management default `15` and Populate overlay field sizes render consistently at `15px` for sampled forms. |
| REQ-052 | Done — Verified Live | `populate` `typography` `rendering` | Confirmed second overlay sharpening pass is deployed/live (`kerning/ligatures/smoothing/text-rendering`). |

---

## 2026-06-25 Rotation Pass

Items rotated out of active checklist after live verification.

| REQ | Status | Tags | Resulting actions (short) |
|-----|--------|------|---------------------------|
| REQ-030 | Done — Verified Live | `projects` `filters` `layout` | Projects toolbar/filter row fixed; status dropdown submits on change. |
| REQ-032 | Done — Verified Live | `project-view` `copy` | Renamed “Additional Forms” to “Selected Forms”. |
| REQ-033 | Done — Verified Live | `project-view` `typography` | Increased primary section heading size. |
| REQ-034 | Done — Verified Live | `project-view` `forms-order` | Moved selected forms list above add-forms controls. |
| REQ-036 | Done — Verified Live | `populate` `sidebar` | Custom Input Box controls pinned at bottom of properties panel. |
| REQ-037 | Done — Verified Live | `populate` `font-resize` `overflow` | Fixed blur/reselect bounce and enforced red+block limit behavior. |
| REQ-038 | Done — Verified Live | `populate` `header` | Standardized top title as `Populate Form - <project>`. |
| REQ-039 | Done — Verified Live | `populate` `mapping` | Removed “General” from saved-field category dropdown. |
| REQ-040 | Done — Verified Live | `populate` `header` `scrolling` | Kept one compact merged header and preview-owned scrolling. |
| REQ-041 | Done — Verified Live | `populate` `export` | Renamed export option to “All Forms Zipped”. |
| REQ-043 | Done — Verified Live | `populate` `checkbox` | Changed fill-out checkbox marker to checkmark (`✓`). |
| REQ-044 | Done — Verified Live | `populate` `all-forms` | Removed All Forms read-only summary box. |
| REQ-045 | Done — Verified Live | `populate` `all-forms` | All Forms mode now stacks interactive pages end-to-end. |
| REQ-046 | Done — Verified Live | `populate` `ui-regression` `header` | Removed duplicate populate header and kept one thin top header. |
| REQ-047 | Done — Verified Live | `populate` `scrolling` `layout` | Left preview scrollbar + footer visibility + no right page scrollbar verified across sizes. |
| REQ-048 | Done — Verified Live | `populate` `sidebar` `ux-density` | Replaced inline help with compact `?` icons; tightened sidebar density. |
| REQ-049 | Done — Verified Live | `populate` `ux` `visual-cue` | Added configurable empty-field shading (`--fillout-empty-field-bg`). |
| REQ-050 | Done — Verified Live | `populate` `mapping` `sidebar` | Persisted mapping pointers and synced dropdown reflection per selected cell. |
| REQ-051 | Done — Verified Live | `populate` `all-forms` `regression` | Fixed all-forms non-current template rendering (`dirname(__DIR__) . '/uploads'`). |

---

## 2026-06-25 7:49 PM Stabilization Rotation

| REQ | Status | Tags | Resulting actions (short) |
|-----|--------|------|---------------------------|
| REQ-007 | Done — Re-verified | `project-view` `buttons` | Expanded Project View button styling to force blue across primary/secondary action buttons in the page scope. |
| REQ-015 | Done — Re-verified | `populate` `overflow` `typing-limit` | Re-enabled progressive step-down fitting while typing and kept hard block at limit with red warning only at overflow threshold. |
| REQ-016 | Done — Re-verified | `populate` `font-indicator` | Refreshed indicator values on initialization and post-fit updates so displayed size tracks runtime shrink behavior. |
| REQ-017 | Done — Re-verified | `populate` `overflow` | Kept hard-limit block behavior (revert to last fitting value) and red-at-limit warning in sync with typing behavior. |
| REQ-026 | Done — Re-verified | `populate` `layout-shift` | Removed top saved-success banner block from Populate to prevent form push-down/jump after save redirects. |

