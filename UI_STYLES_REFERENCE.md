# PDFTimeSaver — UI Styles Reference

**Live source:** `mvp/views/layout_header.php` (embedded `<style>` block)  
**Last synced:** 2026-06-18  
**Purpose:** Single reference for approved sizes, class names, and usage. When changing global styles, update `layout_header.php` first, then sync this document.

Related checklists that approved these sizes:
- `CURRENT_PHASE1_CHECKLIST.md` — Back / Export / Finish same height (42px); Export reduced from oversized
- Form Sets Manager sign-off — 30px icon controls, footer Back/Finish paired actions

---

## Design tokens

| Token | Value | Used for |
|-------|-------|----------|
| Primary blue | `#007bff` | `.pdftimesaver-btn`, sidebar active, focus rings |
| Primary hover | `#0056b3` | Button hover |
| Secondary text | `#555` | Secondary buttons, table body |
| Page background | `#f5f6fa` | `body` |
| Card border | `#ddd` / `#e5e7eb` | Cards, tables |
| Form help text | `#64748b` | `.wpts-form-help` |
| Title text | `#1f2937` / `#2c3e50` | Headings |
| Danger border | `#fecaca` | Delete buttons |
| Danger text | `#b91c1c` | Delete buttons |
| Form Sets primary (page override) | `#2563eb` | `.form-sets-btn.primary` only |
| Universal processor purple | `#667eea` | `.browse-btn` only (form importer) |

**Typography:** `-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, …` · base `14px` · form titles `20px` · page titles often inline `24px` (not yet centralized)

---

## Button size tiers (canonical)

Compose classes — base + modifier:

| Tier | Classes | Height | When to use |
|------|---------|--------|-------------|
| **Action** | `.pdftimesaver-btn-action` (+ `.pdftimesaver-btn` or `.pdftimesaver-btn-secondary`) | 42px | Footer nav: Back, Next, Finish, Export |
| **Default** | `.pdftimesaver-btn` / `.pdftimesaver-btn-secondary` | 36px min | Toolbar: Browse, Search, Add, Import |
| **Small** | `.pdftimesaver-btn-sm` | 30px min | Table row: View, Complete, Edit |
| **Icon** | `.pdftimesaver-icon-btn` (+ secondary/primary) | 30×30px | View / Up / Down / Remove / Add in lists |
| **Delete** | `.pdftimesaver-delete-btn` | 36×36px | Trash-can delete (project, form set, form) |
| **Search** | `.pdftimesaver-search-btn` | 36×36px | Magnifying-glass submit beside search input |
| **Large CTA** | `.pdftimesaver-btn-large` | ~44px | Hero only (e.g. “Go to drafting”) — **not** footer actions |

**Aliases (legacy — prefer canonical names in new markup):**
- `.wizard-action-btn` → same as `.pdftimesaver-btn-action`
- `.form-sets-icon-btn` → same sizing as `.pdftimesaver-icon-btn`
- `.form-delete-icon-btn` → same as `.pdftimesaver-delete-btn`
- `.project-form-icon-btn` → removed from pages; use `.pdftimesaver-icon-btn`

### HTML examples

```html
<!-- Footer -->
<button class="pdftimesaver-btn-secondary pdftimesaver-btn-action">Back to Projects</button>
<button class="pdftimesaver-btn pdftimesaver-btn-action">Next</button>

<!-- Toolbar -->
<button class="pdftimesaver-btn-secondary">Browse</button>
<button class="pdftimesaver-btn-secondary pdftimesaver-search-btn" aria-label="Search">&#128269;</button>

<!-- Table row -->
<a class="pdftimesaver-btn-secondary pdftimesaver-btn-sm">View</a>

<!-- Icon control -->
<button class="pdftimesaver-icon-btn pdftimesaver-btn-secondary" aria-label="Remove">&#10005;</button>
<button class="pdftimesaver-icon-btn pdftimesaver-btn" aria-label="Added">&#10003;</button>

<!-- Delete -->
<button class="pdftimesaver-delete-btn" aria-label="Delete project">&#128465;</button>

<!-- Button group -->
<div class="button-group">
  <button class="pdftimesaver-btn-secondary">Cancel</button>
  <button class="pdftimesaver-btn pdftimesaver-btn-action">Finish</button>
</div>
```

### Copied CSS — buttons

```css
/* Default toolbar button */
.pdftimesaver-btn,
.pdftimesaver-btn-secondary {
    background: #007bff;
    color: #ffffff;
    border: none;
    padding: 8px 14px;
    border-radius: 3px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-sizing: border-box;
    line-height: 1.25;
    min-height: 36px;
    gap: 6px;
}
.pdftimesaver-btn:hover { background: #0056b3; }
.pdftimesaver-btn-secondary {
    background: #ffffff;
    color: #555;
    border: 1px solid #ccc;
}
.pdftimesaver-btn-secondary:hover { background: #f5f5f5; }

/* Compact table / list row actions */
.pdftimesaver-btn-sm,
.pdftimesaver-btn.pdftimesaver-btn-sm,
.pdftimesaver-btn-secondary.pdftimesaver-btn-sm {
    padding: 6px 12px;
    font-size: 12px;
    min-height: 30px;
}

/* Footer / wizard nav — 42px (approved) */
.pdftimesaver-btn-action,
.wizard-action-btn {
    height: 42px;
    min-height: 42px;
    padding: 0 24px !important;
    font-size: 15px !important;
    font-weight: 600;
    line-height: 1;
    border-radius: 6px;
}

/* 30×30 icon controls */
.pdftimesaver-icon-btn,
.form-sets-icon-btn,
.project-form-icon-btn {
    min-width: 30px;
    width: 30px;
    height: 30px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    line-height: 1;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    background: #eef2f7;
    color: #111827;
    cursor: pointer;
    text-decoration: none;
    box-sizing: border-box;
}
.pdftimesaver-btn.pdftimesaver-icon-btn,
.pdftimesaver-btn-secondary.pdftimesaver-icon-btn,
.form-sets-icon-btn {
    min-height: 30px;
    height: 30px;
    width: 30px;
    padding: 0;
}
.pdftimesaver-icon-btn.pdftimesaver-btn,
.form-sets-icon-btn.primary {
    background: #007bff;
    color: #ffffff;
    border-color: #007bff;
}

/* Delete trash control — 36×36 */
.pdftimesaver-delete-btn,
.form-delete-icon-btn {
    width: 36px;
    height: 36px;
    min-width: 36px;
    padding: 0;
    border: 1px solid #fecaca;
    border-radius: 8px;
    background: #fff1f2;
    color: #b91c1c;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 16px;
    line-height: 1;
    box-sizing: border-box;
    text-decoration: none;
}
.pdftimesaver-delete-btn:hover:not(:disabled),
.form-delete-icon-btn:hover:not(:disabled) { background: #ffe4e6; }

/* Search magnifier — 36×36 */
.pdftimesaver-search-btn {
    min-width: 36px;
    width: 36px;
    height: 36px;
    min-height: 36px;
    padding: 0;
    font-size: 16px;
    line-height: 1;
}

.button-group {
    display: flex;
    gap: 12px;
    align-items: center;
    flex-wrap: wrap;
}

/* Hero CTA only — not footer nav */
.pdftimesaver-btn-large {
    padding: 12px 24px;
    font-size: 16px;
    font-weight: 600;
    border-radius: 6px;
    transition: all 0.3s ease;
}
```

### Mobile (≤768px)

Touch targets bump to **44px** for default, action, icon, delete, and search buttons. See `layout_header.php` `@media (max-width: 768px)`.

---

## Layout & shell

```css
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    background: #f5f6fa;
    color: #2c3e50;
    line-height: 1.4;
    font-size: 14px;
}
.pdftimesaver-sidebar { width: 200px; /* fixed left nav */ }
.pdftimesaver-main-content { margin-left: 200px; min-height: 100vh; }
.pdftimesaver-content-body { padding: 20px; }
.pdftimesaver-content-header { padding: 15px 20px; border-bottom: 1px solid #ddd; }
```

Sidebar auto-collapses to 64px on desktop for routes: `form-management`, `form-sets-manager`, `universal-processor`, `field-manager`, `firm-defaults`.

---

## Cards, tables, inputs

### Copied CSS — core components

```css
.pdftimesaver-card {
    background: #ffffff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 15px;
    margin-bottom: 15px;
}

.pdftimesaver-table {
    width: 100%;
    border-collapse: collapse;
    background: #ffffff;
    border: 1px solid #ddd;
    border-radius: 4px;
}
.pdftimesaver-table th {
    background: #f5f5f5;
    padding: 10px 12px;
    text-align: left;
    font-weight: 500;
    color: #333;
    font-size: 13px;
    border-bottom: 1px solid #ddd;
}
.pdftimesaver-table td {
    padding: 10px 12px;
    border-bottom: 1px solid #eee;
    color: #555;
    font-size: 14px;
}
.pdftimesaver-table tr:hover { background: #f9f9f9; }

.table-responsive {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}

.pdftimesaver-input {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    font-size: 14px;
    transition: all 0.2s ease;
}
.pdftimesaver-input:focus {
    outline: none;
    border-color: #1976d2;
    box-shadow: 0 0 0 2px rgba(25, 118, 210, 0.2);
}

.pdftimesaver-form-label {
    display: block;
    margin-bottom: 6px;
    font-weight: 600;
    color: #2c3e50;
    font-size: 14px;
}
```

### Form layout (WPTS pattern)

```css
.wpts-form-shell {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 16px;
}
.wpts-form-title { margin: 0 0 6px 0; color: #1f2937; font-size: 20px; font-weight: 700; }
.wpts-form-help { margin: 0 0 14px 0; color: #64748b; font-size: 13px; line-height: 1.45; }
.wpts-form-grid { display: grid; gap: 12px; }
.wpts-form-grid-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
.wpts-form-grid-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
.wpts-form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding-top: 12px;
    border-top: 1px solid #e5e7eb;
    margin-top: 8px;
}
.wpts-section-box {
    border: 1px solid #dbe4ef;
    border-left: 4px solid #4f46e5;
    background: #f8fbff;
    border-radius: 10px;
    padding: 12px;
}
.wpts-selectable-row { cursor: pointer; }
.wpts-selectable-row:hover td { background: #eef2f7; }
```

### Status badges

```css
.pdftimesaver-status { padding: 2px 6px; border-radius: 3px; font-size: 11px; font-weight: 500; }
.pdftimesaver-status-active { background: #d4edda; color: #155724; }
.pdftimesaver-status-archived { background: #f8d7da; color: #721c24; }
.pdftimesaver-status-in-progress { background: #d1ecf1; color: #0c5460; }
```

Note: `.status-completed` exists separately inside drafting styles — not unified with `.pdftimesaver-status-*` yet.

### Messages

```css
.success-message { background: #d4edda; color: #155724; padding: 12px 16px; border-radius: 6px; border: 1px solid #c3e6cb; }
.error-message { background: #f8d7da; color: #721c24; padding: 12px 16px; border-radius: 6px; border: 1px solid #f5c6cb; }
```

---

## Page-specific styles (not in global sheet)

These remain in individual view files. Prefer migrating repeated patterns into `layout_header.php`.

| File | Local system | Notes |
|------|--------------|-------|
| `universal_processor.php` | `.browse-btn` (purple), wizard panes, field editor | `.browse-btn` + `.wizard-action-btn` for footer; colors intentionally different |
| `form_sets_manager.php` | `.form-sets-*` layout/table; `.form-sets-btn` colors only | Sizing now from global; `#2563eb` primary override |
| `project.php` | `.project-view-*`, `.project-footer-actions` | Layout only; buttons use global classes |
| `projects.php` | `.projects-toolbar`, `.projects-search-input` width | Search input fixed 280px |
| `populate.php` | `.fillout-*` interactive PDF overlay | Large block; page-specific by design |
| `field_manager.php`, `alias_manager.php`, `firm_defaults.php` | Own grids/tables | Partial overlap with WPTS |
| `clients.php` | Inline status tab toggle styles | Candidate for `.pdftimesaver-tab-toggle` |
| `font-settings.php` | Inline `<select>` / input styles | Should use `.pdftimesaver-input` |
| `layout_footer.php` | JS-injected button `style=` attributes | Hardcoded 8px 16px buttons |

---

## CSS audit (2026-06-18)

### ✅ Centralized and consistent

- Button height tiers (30 / 36 / 42px) in `layout_header.php`
- Table row actions using `.pdftimesaver-btn-sm` on projects, clients, documents, activities
- Footer actions using `.pdftimesaver-btn-action` on project view, form sets, form importer
- Icon controls using `.pdftimesaver-icon-btn` on project view and form sets
- Delete controls using `.pdftimesaver-delete-btn`
- Form layout primitives (`.wpts-form-*`) used on project view, clients, form sets shell

### ⚠️ High priority — should fix next

1. **Four primary blues in use**
   - Global `#007bff`, form sets `#2563eb`, universal `.browse-btn` `#667eea`, links/focus `#1976d2`
   - **Recommendation:** Pick one primary (`#007bff` or `#2563eb`) for all new work; map `.browse-btn` and `.form-sets-btn.primary` to global primary in a follow-up pass.

2. **Duplicate input styling**
   - `.pdftimesaver-input` (global) vs `.form-sets-input` vs inline `padding: 6px/8px; border: 1px solid #ced4da` in `font-settings.php`
   - **Recommendation:** Add `.pdftimesaver-select` alias; replace inline selects in font-settings.

3. **Inline page titles repeated ~15 times**
   - Pattern: `h2 style="margin: 0 0 6px 0; color: #2c3e50; font-size: 24px; font-weight: 700;"`
   - **Recommendation:** Add `.pdftimesaver-page-title` and `.pdftimesaver-page-subtitle` to global CSS.

4. **Empty-state blocks duplicated**
   - Same structure in projects, clients, documents, activities, reports (emoji + h3 + p + centered padding)
   - **Recommendation:** Add `.pdftimesaver-empty-state`, `.pdftimesaver-empty-icon`, `.pdftimesaver-empty-title`.

5. **`layout_footer.php` JS buttons bypass design system**
   - Field placement Cancel/Place use raw `padding: 8px 16px` inline styles
   - **Recommendation:** Use `.pdftimesaver-btn` / `.pdftimesaver-btn-secondary` in template strings.

6. **Danger / destructive text buttons not centralized**
   - Clients delete, client.php delete use inline `border-color: #fecaca; color: #b91c1c`
   - **Recommendation:** Add `.pdftimesaver-btn-danger` (text button, not icon).

### ⚠️ Medium priority

7. **Legacy class aliases still in global CSS**
   - `.wizard-action-btn`, `.form-sets-icon-btn`, `.project-form-icon-btn` bloat selectors
   - **Recommendation:** Grep and remove aliases once all markup uses canonical names.

8. **`.pdftimesaver-btn-large` vs `.pdftimesaver-btn-action` confusion**
   - Populate “Go to drafting” still uses `-large`; Rescan uses `-action` (correct)
   - **Recommendation:** Document-only is done; optionally downgrade “Go to drafting” to `-action` for consistency.

9. **Status badge naming split**
   - `.pdftimesaver-status-in-progress` vs drafting `.status-completed` vs projects PHP `str_replace('_', '-', $status)`
   - **Recommendation:** Single `.pdftimesaver-status-*` set including `completed`.

10. **28 view files with local `<style>` blocks**
    - No extracted `.css` file; all CSS lives inline in PHP
    - **Recommendation:** Long-term: extract `mvp/assets/pdftimesaver.css` and link from `layout_header.php`; keep page-specific overrides minimal.

11. **Clients status filter tabs**
    - Custom inline flex/tab styling not reusable
    - **Recommendation:** `.pdftimesaver-segmented-control` + `.is-active`.

12. **Universal processor `.browse-btn` sizing**
    - Base `.browse-btn` is `padding: 12px 24px` (not 36px min-height) until combined with `.wizard-action-btn`
    - Works today but fragile if used alone
    - **Recommendation:** Make `.browse-btn` extend `.pdftimesaver-btn` or drop `.browse-btn` for global classes + page color modifier.

### ℹ️ Low priority / acceptable for now

- Drafting interface styles (~200 lines in layout_header) — scoped to drafting route
- Populate `.fillout-*` overlay CSS — highly specialized
- Diagnostic/demo pages (`pdf_lib_demo`, `automated_verification`) — dev-only styling
- `[style*="display: flex"] { flex-wrap: wrap; }` mobile rule — broad; can cause unexpected wraps

---

## Migration checklist (for future passes)

- [ ] Add `.pdftimesaver-page-title`, `.pdftimesaver-page-subtitle`, `.pdftimesaver-empty-state`
- [ ] Add `.pdftimesaver-btn-danger`, `.pdftimesaver-select`
- [ ] Unify primary color to one hex across browse-btn, form-sets-btn, global btn
- [ ] Replace inline empty states on list pages
- [ ] Replace font-settings inline input/select styles with global classes
- [ ] Fix layout_footer.php JS button templates
- [ ] Remove legacy aliases after markup migration
- [ ] Extract `mvp/assets/pdftimesaver.css` from layout_header (optional structural improvement)
- [ ] Add `.pdftimesaver-status-completed` and align drafting status classes

---

## File index — where styles live

| Location | Role |
|----------|------|
| `mvp/views/layout_header.php` | **Global design system** (~430 lines of component CSS + drafting + responsive) |
| `mvp/views/layout_footer.php` | Modal/JS injected styles (non-compliant buttons) |
| `mvp/views/universal_processor.php` | Form importer wizard + field editor (~1000+ lines CSS) |
| `mvp/views/form_sets_manager.php` | Form sets layout + color overrides |
| `mvp/views/populate.php` | Fill-out forms interactive preview |
| `mvp/views/project.php` | Project view layout |
| `mvp/views/projects.php` | Projects list toolbar width |
| `DESIGN SPECS/ui-components-reference.md` | Clio reference (external product spec — not our implementation CSS) |

**This document** (`UI_STYLES_REFERENCE.md`) is the human-readable mirror of our implementation styles plus audit notes. Update it whenever global button or form primitives change.
