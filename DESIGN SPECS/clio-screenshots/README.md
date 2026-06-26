# Capture Clio screenshots (screen capture)
# Run WHILE Browser MCP has each page open and you are logged into Clio.
# Agent navigates each URL via Browser MCP, then captures the Chrome window.
#
# Preferred (auto-foregrounds + maximizes the Chrome window, so it won't grab
# the IDE by mistake):
#   powershell -File scripts/capture-chrome.ps1 -OutputPath "DESIGN SPECS/clio-screenshots/XX-name.png"
#
# Fallback (captures whatever is on the primary monitor — make sure Chrome is in front):
#   powershell -File scripts/save-screen-png.ps1 -OutputPath "DESIGN SPECS/clio-screenshots/XX-name.png"

| File | URL | Status |
|------|-----|--------|
| 01-clients-active.png | /clients/active/ | |
| 02-clients-projects.png | /clients/projects/ | |
| 03-clients-create.png | /clients/create/ | |
| 04-clients-archived.png | /clients/archived/ | |
| 05-project-overview.png | /clients/project/info/ | |
| 06-project-edit.png | /clients/project/edit/ | |
| 07-project-signatures.png | /clients/project/signatures/ | |
| 08-panels-populate.png | /panels/populate/ | Re-captured 2026-06-19 — real populate panel (grouped data form) |
| 09-panels-edit.png | /panels/edit/ | Re-captured 2026-06-19 — real drafting/edit panel (PDF overlay + Add custom field) |
| 10-account-profile.png | /clients/settings/account/profile/ | |
| 11-account-security.png | /clients/settings/account/account/ | |
| 12-account-organization.png | /clients/settings/account/organization/ | |
| 13-subscription-team.png | /clients/settings/subscription/licenses/ | |
| 14-subscription-plan.png | /clients/settings/subscription/plans/ | |
| 15-subscription-billing.png | /clients/settings/subscription/billings/ | |
| 16-subscription-payment.png | /clients/settings/subscription/card/ | |
| 17-form-libraries.png | /clients/settings/form-libraries/ | |
| 18-integrations.png | /clients/settings/integrations/ | |
| 19-support.png | /clients/support/ | |
| 20-sort-menu.png | /clients/active/ (click Sort by first) | |
| 21-select-court.png | /panels/populate/ (click Select court first) | |

**Note:** Full-desktop capture — keep the Clio Chrome window maximized and in front.
`capture-chrome.ps1` does this automatically; `save-screen-png.ps1` does not.

## Panel reference notes (from 2026-06-19 re-capture)

- **Populate (`08`)** = pure data entry. One centered single-column card, logical
  fields grouped by section (Attorney / Client / Court / Other). Each input shows a
  `×N` badge = how many PDF spots that one value fills (dedup autopopulate). No font
  controls, no color, no custom-field placement, no PDF preview, no export controls.
  Actions: `← Back to select documents`, `Go to drafting →`.
- **Drafting / Edit (`09`)** = the PDF overlay editor. Left rail `Insert → Add custom
  field`; center shows the live PDF with populated values stamped in; right rail
  `Documents (N)` list with `Add/Remove` to switch forms; bottom `← Back to populate`
  + status dropdown (In progress / Review / Completed); top `Sign` / `Download`.
- Clio splits the work: **Populate = data**, **Drafting/Edit = visual layer + custom
  fields + sign + download.**
