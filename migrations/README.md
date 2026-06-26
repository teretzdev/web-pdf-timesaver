# SQL migrations (manual)

Run these against your app database when automated `ALTER` from PHP is blocked (e.g. DB user lacks `ALTER` during HTTP requests) or you prefer ops-managed schema.

| File | Purpose |
|------|---------|
| `add_form_templates_form_location.sql` | Adds `form_templates.form_location` (and `detected_firm_name` if missing). Matches `mvp/lib/data.php` Phase 1 registry. |

After running, **restart PHP** (or clear OPcache) so `mvp/lib/data.php` changes load, then open Forms Manager again.

The app detects the column via `information_schema`, with fallbacks to `SHOW COLUMNS` / a probe `SELECT` if your DB user cannot read `information_schema`.

If the page still fails, confirm **`SELECT DATABASE()`** in the SQL client matches the app’s configured database, and check the **`message=`** parameter on the `error-check.php` URL for the real exception text.
