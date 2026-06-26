# Form Sets Manager — Request Confirmation Checklist

This checklist is focused only on confirming the new Form Sets Manager requirements.

## Tester info
- Date: 2026-06-04
- Tester: Shadow
- Environment URL: `https://pdftimesaver.desktopmasters.com/mvp`
- Browser: Browser automation verification + manual spot checks

## Requirement confirmations

### 1) New page purpose (default form sets per lawsuit type)
- [x] Confirm page exists and is accessible at `?route=form-sets-manager`.
- [x] Confirm page is used to create and maintain named sets of forms.
- Evidence: Route loads and supports create/edit/set save flow.

### 2) Phase 1 scope (global sets only)
- [x] Confirm built-in global presets are available.
- [x] Confirm saved sets are handled as global (no per-user/per-client scoping in this phase).
- Evidence: `All Global Forms` preset present; sets persisted via global app setting.

### 3) Preset + modify + save-as behavior
- [x] Select a built-in global preset.
- [x] Modify the selected form list (add/remove/reorder at least one item).
- [x] Save using custom set name (example: `Blah blah divorce`).
- [x] Repeat save using preset/default name.
- Evidence: Verified in browser flow with preset apply + reorder + save.

### 4) First page = Form Sets Manager search page
- [x] Confirm first page header/title is `Form Sets Manager`.
- [x] Confirm page offers:
  - `Add Form Set`
  - `Search for Form Set`
  - `Browse Form Sets`
- Evidence: Search page controls visible and functional.

### 5) Listed set opens in edit mode
- [x] Click a listed Form Set from search/browse list.
- [x] Confirm it opens editor view.
- Evidence: Saved set row opens `Edit Form Set` view with loaded data.

### 6) Edit Form Set equals Add Form Set with prefilled data
- [x] Open an existing set in Edit mode.
- [x] Confirm same controls as Add mode are present.
- [x] Confirm data is pre-entered (name + selected forms + order).
- Evidence: Same editor used; name/list/order prefilled on edit.

### 7) Add/Edit form top name input
- [x] In Add Form Set editor, confirm top text input for form set name exists.
- [x] Confirm name can be changed before save.
- Evidence: Name field editable and persisted on save.

### 8) Search for form + per-result actions
- [x] Confirm `Search for form...` input exists.
- [x] Confirm each result has:
  - `View`
  - `Add to List`
- Evidence: Result rows include both actions and function as expected.

### 9) Import Form round-trip and auto-add
- [x] From Form Set editor click `Import Form`.
- [x] Confirm it enters form importer flow.
- [x] Complete import and click `Finished`.
- [x] Confirm return to Form Sets page/editor.
- [x] Confirm imported form is added to selected form list.
- Evidence: Verified return URL contains set context and returns to `form-sets-manager`.

### 10) Change form list order
- [x] In selected forms list, reorder at least one form (`Up`/`Down` or equivalent).
- [x] Save and reopen set.
- [x] Confirm order persisted.
- Evidence: Reorder persisted after save/reopen.

### 11) View ability from selected list
- [x] From selected form list, click `View` on at least one form.
- [x] Confirm form opens successfully in new tab/page.
- Evidence: View opened successfully in new tab.

### 12) Finish behavior
- [x] Click `Finish` from Add/Edit Form Set.
- [x] Confirm changes are saved.
- [x] Confirm user is returned to first/search page.
- Evidence: Finish saves set and returns to search page list.

## Final sign-off
- [x] All 12 requirement confirmations passed.
- [x] Any failed item includes reproduction steps and observed result.
- [x] Ready to submit.
