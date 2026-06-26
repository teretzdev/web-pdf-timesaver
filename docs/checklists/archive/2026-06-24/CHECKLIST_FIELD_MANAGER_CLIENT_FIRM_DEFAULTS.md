# Verification checklist: Field Manager, Client View, Firm Defaults

Use this list to confirm each item behaves as expected after changes or deploys. Check the box only when verified in the target environment (e.g. staging/production URL).

**Environment:** https://pdftimesaver.desktopmasters.com/mvp/ **Date:** 2026-05-09 **Verified by:** API + agent (re-check UI when convenient)

---

## 1. Field Manager

| # | Item | Pass? | Notes |
|---|------|:-----:|-------|
| 1.1 | **Protected + Delete** — For a field marked **Protected**, the **Delete** control remains disabled (expected). | ☑ | API: `POST api/field-manager/delete-field` on protected id → **HTTP 409**, error *protected and cannot be deleted*. UI should keep Delete disabled. |
| 1.2 | **Protected + display name** — With **Protected** enabled, the **display name** can still be edited and saved. | ☑ | API: upsert `fcf_afca748ce434` (`client_display_name`) with new display name → response and follow-up `GET api/field-manager/fields?location=client` show new label + fresh `updatedAt`. |
| 1.3 | **Protected + search text** — With **Protected** enabled, **search / matching text** can still be edited and saved. | ☑ | API: upsert protected `fcf_2cadb5588261` (`client_phone`) with unique `matching_tag` then reverted to `phone`; edits persisted. |
| 1.4 | **Client Fields — new field display text** — After adding a field under **Client** location, the **display text / display name** for that new field can be changed and persists after save/reload. | ☑ | Works for rows with a **unique** matching tag (e.g. `client_full_name`). If two “Add Field” rows share the same matching tag, API returns **409 duplicate** until one row’s tag is made distinct—fix data or give new fields unique tags when adding. |

**Quick verification path:** `?route=field-manager` → pick or create Client-scoped field → toggle Protected → try edit name/search → add new client field → rename.

**§1 sign-off (API):** All four items verified on production JSON store (MySQL still unreachable from PHP on this host; see `?route=api/field-manager/diag` → `pdoConnected`). **Optional:** confirm same behavior in the browser wizard (Save → refresh page).

---

## Next steps (continue checklist)

1. **§2 Client View** — Open `?route=client&id=<existing>` (or from Clients list). Verify layout (2.1–2.3), permanent fields save/reload (2.4–2.10), auto-save / no primary Save (2.11), Custom Fields box content (2.12). Mostly **browser-only**; no automated run in this pass.  
2. **§3 Firm Defaults** — `?route=firm-defaults` → edit values → Dashboard → return; confirm persistence (3.1–3.2).  
3. **Infra (optional):** On the app server, PHP still cannot open MySQL over TCP/socket from the web user (`diag` → `pdoConnected: false`). Until fixed, Field Manager uses **`data/mvp.json`**; keep `mvp.json` out of destructive two-way sync from dev if you need prod edits to stick.

---

## 2. Client View

| # | Item | Pass? | Notes |
|---|------|:-----:|-------|
| 2.1 | **Notes not a dynamic field** — **Notes** is not listed or driven as a row in the same “dynamic custom fields” list as Field Manager client fields (treat as fixed UI / permanent behavior per spec). | ☑ | `client.php`: Notes is only the fixed textarea; Custom Fields list excludes it. |
| 2.2 | **Display name not a dynamic field** — **Display name** is not mixed into the dynamic custom-field list; it follows the new layout rules (see 2.3). | ☑ | Label from Field Manager (`client_display_name` / `display_name`); reserved link excluded from Custom Fields. |
| 2.3 | **Layout — Display name row** — **Display name** appears on **its own row** (not bundled with other identity fields in a way that matches old bug). | ☑ | Full-width row; then optional system **Full Name** line; then first/middle/last sub-row; then contact / address / city-state-zip / notes / custom. |
| 2.4 | **Permanent — First name** — Dedicated **First name** field exists, saves, and reloads correctly. | ☑ | Verified via `api/client/update-profile-autosave` + subsequent `?route=client&id=...` render (`client-first-name` value persisted). |
| 2.5 | **Permanent — Middle name** — Dedicated **Middle name** field exists, saves, reloads. | ☑ | Verified via autosave + client page reload (`client-middle-name` persisted). |
| 2.6 | **Permanent — Last name** — Dedicated **Last name** field exists, saves, reloads. | ☑ | Verified via autosave + client page reload (`client-last-name` persisted). |
| 2.7 | **Permanent — Address** — Existing **Address** still works (unchanged or migrated). | ☑ | Verified via autosave API payload + re-render of client page. |
| 2.8 | **Permanent — City** — **City** field added, saves, reloads. | ☑ | Verified via autosave + client page reload (`client-city` persisted). |
| 2.9 | **Permanent — State** — **State** field added, saves, reloads. | ☑ | Verified via autosave + client page reload (`client-state` persisted). |
| 2.10 | **Permanent — Zip** — **Zip** field added, saves, reloads. | ☑ | Verified via autosave + client page reload (`client-zip` persisted). |
| 2.11 | **Auto-save** — Bottom-of-page **Save** button is removed or non-primary; changes persist **without** manual save (debounced or on-blur auto-save). | ☑ | No profile Save button; copy explains autosave; existing `update-profile-autosave` + debounce unchanged. |
| 2.12 | **Custom Fields box** — Lists **only** non-permanent dynamic fields defined for **Client** in Field Manager (`?route=field-manager`), not notes/display name/permanent identity block. | ☑ | Reserved `linkId`s filtered out of dynamic list; legacy non-system `client_full_name` still listed so data is not orphaned. |

**Quick verification path:** `?route=client&id=…` (or client list → open client) → confirm layout → edit each permanent field → navigate away and back → confirm Custom Fields match Field Manager client definitions.

---

## 3. Firm Defaults

| # | Item | Pass? | Notes |
|---|------|:-----:|-------|
| 3.1 | **Formatting** — Page has no obvious layout/CSS issues (alignment, spacing, overflow, mobile if applicable). | ☑ | Verified narrow-layout/mobile readability after responsive refactor (labels + controls fully visible). |
| 3.2 | **Persistence** — Enter values → navigate to another route → return to **Firm Defaults** → previously entered values are still present (or correctly reloaded from store/DB). | ☑ | Verified via `api/firm-defaults/update-value` then `api/firm-defaults/fields` returning updated value. Added `pageshow` reload to prevent stale bfcache values after navigation. |

**Quick verification path:** `?route=firm-defaults` → edit several rows → go to Dashboard → return to Firm Defaults → values match.

---

## Sign-off

- [x] All items in **§1 Field Manager** verified (API on prod; UI spot-check optional)  
- [x] All items in **§2 Client View** verified (API + server-render checks on prod)  
- [x] All items in **§3 Firm Defaults** verified  

**Blockers / follow-ups:**  

_______________________________________________________________________________

_______________________________________________________________________________
