# Projects / Project View — UI Bug Checklist

Source: Merlin (Desktop Masters), Wed Jun 17 5:55 PM.
Scope: UI/UX fixes only. Do NOT implement until each item is confirmed — this file is the checklist only.

---

## Projects (list page)

- [ ] **[Complete] button — clarify purpose.** (SKIPPED per request — left as-is, not answered.)
- [x] **Remove "Back to Dashboard".**
- [x] **Make [Browse] a button** (now a real `<button>` in a GET form).
- [x] **Move "Search" to the right of the search box** as a magnifying-glass icon button.
- [x] **Fix search box resizing** — input now a fixed width (`flex: 0 0 auto`).

---

## Project View page

- [x] **Empty title on new project** — new projects are created with an empty name (no "Untitled Project"); help text kept.
- [x] **Add delete control** — trash-can icon (top-right) deletes the project via the delete endpoint.
- [x] **"Sync Name" — removed.** Redundant now that setup autosaves the name.
- [x] **Convert text links to buttons** — Edit/Search/Browse/Change are all button-styled controls.

### Client section
- [x] **Whole-row selectable** — entire client row is clickable to select.

### Case section
- [x] **Whole-row selectable** — entire case row is clickable to select (also fixed a filtered-index selection bug).

### Form Set section
- [x] **Whole-row selectable** — entire form-set row is clickable to select.

### Forms / actions
- [x] **Rename section** — now "Additional Forms".
- [x] **Merge save + back** — single `[Back to Projects]` button; the page autosaves as you go (name, case, form set, forms), so the explicit Save button was removed.
- [x] **Reposition Back** — `[Back to Projects]` is on the same line as `[Next]`, to its left.
- [x] **Rename Next** — now just `[Next]`.

---

### Notes / open questions
- The Case and Form Set "whole-row selectable" items were written by Merlin as "when choosing the client" (copy/paste); interpreted here as the case row and form-set row respectively.
- Merging save into "Back to Projects" depends on confirming that project setup truly autosaves as you go (name, client, case, form set). Verify before removing the explicit save.
