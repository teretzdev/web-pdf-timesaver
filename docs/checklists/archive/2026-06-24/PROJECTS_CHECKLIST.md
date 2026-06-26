# PROJECTS Checklist

## Scope Lock (Corrected)

- [x] Upgrade existing pages: `?route=projects` and `?route=project` (no new replacement routes).
- [x] Reuse existing fill flow via `?route=populate`.
- [x] Keep Case data on the project record/config (no separate Case entity).
- [x] Add/update quicklinks in sidebar (`layout_header.php`) only.
- [x] Treat work as 3 parts: `Projects`, `Project View`, and `Fill Out Forms Page`.

## Projects Page

- [x] `Search for project` control is present and functional. (RE-VERIFIED server-side: 13 matters unfiltered -> 0 matters for a non-matching query. Earlier "fail" was a browser snapshot-timing artifact, not a bug.)
- [x] `Browse` control is present and explicitly lists all projects.
- [x] `New Project` control is present and opens blank project creation flow.
- [x] Selecting any project opens `?route=project&id=<projectId>`.
- [x] Search results and browse results are deterministic and do not conflict.

## Project View Page

- [x] Header and explanation text are present.
- [x] `Project Name` input is present and editable.
- [x] `Client` section supports `Select/Change` flow.
- [x] `Client` section supports `Edit` (opens existing client view when selected).
- [x] Client picker supports search and selection.
- [x] Client picker has path to `Add Client` flow.
- [x] `Case` section supports `Select/Change` flow.
- [x] Case editor has top `Case Number` input.
- [x] Case dynamic fields can be edited and saved.
- [x] `Form Set` section supports `Search` and `Browse`.
- [x] Form set selection applies project form list.
- [x] Additional forms can be appended to project list.
- [x] Forms list supports icon actions: `View`, `Up`, `Down`, `Remove`.
- [x] Form order changes persist through save.
- [x] Project-specific custom fields can be added.
- [x] Project-specific custom fields can be edited.

## Fill Out Forms Transition

- [x] `Next: Fill Out Forms` exists on Project View.
- [x] `Next` routes to `populate` flow when requirements are met.
- [x] `Next` becomes available once everything is SELECTED (per user spec). Re-marked: the "must save first" criterion was self-imposed, not in the user outline. Gating now = client + case number + form set + at least one ordered template.
  - [x] Client selected
  - [x] Case number entered (summary now updates live as you type)
  - [x] Form set selected
  - [~] "Saved project document exists" — relaxed; not required by user spec (server materializes the doc on open).

## Strict Validation Rules

- [x] No implicit auto-creation of project documents on page load.
- [x] Save is blocked when case number is missing.
- [x] Save is blocked when form set is missing.
- [x] Save succeeds when required setup data is valid.

## Quicklinks

- [x] Sidebar includes `Projects`.
- [x] Sidebar includes `Project View` quicklink.
- [x] `Project View` quicklink resolves to project selection path (`?route=projects`).

## End-to-End Signoff

- [x] Go to `Projects` page.
- [x] Use `Browse` to list all projects.
- [x] Use `Search` to filter and verify expected project appears. (RE-VERIFIED server-side.)
- [x] Create a new project using `New Project`.
- [x] In Project View, select a client.
- [x] Enter case number.
- [x] Choose a form set.
- [x] Save project setup successfully.
- [x] Confirm `Next` is enabled.
- [x] Click `Next` and confirm transition into `populate` flow.

## Fill Out Forms Page (Dedicated Scope)

- [x] Fill Out Forms page shows the project’s selected/ordered forms list.
- [x] Each form opens in the fill workflow with project context (client/case/form-set derived data).
- [x] Saving on Fill Out Forms persists values and status for the current project document.
- [x] Navigation between forms in the selected order works as expected.
- [x] Returning to Project View preserves form completion/progress state.
- [x] End-to-end from Project View `Next` -> Fill Out Forms -> Save -> back flow is verified.

## Fill Out Forms Page (Expanded Request)

- [x] Form title info is listed above each form.
- [x] Single-form mode supports `Next`/`Back` navigation between forms.
- [x] Top dropdown allows direct selection of which form is displayed.
- [x] Optional all-forms end-to-end display mode is available (or explicitly deferred with decision).
- [x] User can manually change field text size in Fill Out Forms.
- [x] As typed content fills field bounds, text size auto-shrinks by 1pt steps to minimum.
- [x] When minimum text size is reached and overflow still occurs, overflow warning style is applied (currently red).
- [x] User can add temporary custom fields in Fill Out Forms.
- [x] Temporary custom fields support resize and move interactions.
- [x] Form data autosaves while typing or on field blur reliably.
- [x] Export controls include scope dropdown: `This Form` / `All Forms`.
- [x] Export controls include format dropdown/options for:
  - [x] Individual PDF export
  - [x] All project PDFs as ZIP
  - [x] One merged PDF containing all forms/pages
- [x] Single `Export` action executes selected scope + format combination.
- [x] Default export scope is `This Form` until last form is active.
- [x] On last form, default export scope switches to `All Forms`.
- [x] End-to-end export validation:
  - [x] `This Form` exports current form only
  - [x] `All Forms` ZIP contains expected per-form PDFs
  - [x] `All Forms` merged PDF contains all pages in project order

## Reported Issues (User Review — June 15)

Source: user review of Project View / Fill Out flow. Captured as checklist first (per process), status reflects real verification.

- [x] Fill-out form is missing entirely — RESOLVED: was caused by the broken `Next` path; populate now renders the real form (verified 103 fields, e.g. Attorney Information section). NOTE: confirm with user this meant the input form, not a visual PDF preview.
- [x] Editing a client returns back to the project afterward (returnTo flow).
- [x] Case is searchable / selectable so case data can be reused.
- [x] Remove "Additional Project Fields" (was not in user outline).
- [x] "Forms in Project" reworked to live under Form Set selection (form set + additional forms).
- [x] Sidebar shows only "Projects" (removed duplicate "Project View").

## Live Re-Audit Log (June 16)

Full browser re-audit run because prior `[x]` marks proved unreliable. Outcome:

- Projects page: search (re-verified server-side), browse, new-project-direct-open, open-existing — all PASS.
- Project View: header, name, client select/change/persist, add-client path, client edit returnTo, case select/change + top case number, case search/reuse, "Additional Project Fields" removed, form set search/browse + populate list, sidebar shows only "Projects" — all PASS.
- Project View fixes this pass: case summary now updates live as the number is typed/selected; `Next` gating relaxed to match user spec (selection-based, not save-based).
- Fill Out page: form renders (103 fields), title header, Next/Back, top form dropdown, all-forms mode, manual text size, add/move/resize temp fields, autosave (reload-persist), export scope + format dropdowns + single Export, smart pagination/Focused View on large panels — all PASS.
- The 5 automation-limited items are now LIVE-VERIFIED via measurement (computed font-size, CSS class/color, select values, save+reload, alert capture):
  - [x] Auto-shrink/grow font: 14px -> 8.95px on fill (200 chars) -> 11.24px on clear.
  - [x] Red overflow warning at min size: 8px + class `populate-overflow-warning` + color rgb(217,48,37).
  - [x] Last-form export scope auto-switch: non-last = 'this', last form = 'all'.
  - [x] Append additional forms + order persists after save+reload.
  - [x] Save validation: missing case -> "Case number is required."; missing form set -> "Select a form set before saving." Both block the save.

## Final Status

- [x] Project/Project View/Fill Out flows re-audited live; failures triaged and corrected.
- [x] All previously automation-limited items confirmed via live measurement.
