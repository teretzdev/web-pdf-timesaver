# Forms Manager vs Universal Processor (wizard steps)

Both use `mvp/views/universal_processor.php` with different `$viewMode`:

| | **Forms Manager** (`form-management`) | **Universal Processor** (default) |
|---|--------------------------------------|-----------------------------------|
| Route | `?route=form-management` | `?route=universal-processor` (and similar) |
| Step 2 | Field alignment editor only — **no separate “download” step 3 pane**; template JSON / finish actions live on the export block on step 2. | Includes **wizard step 3** (“Download”) with extra actions (e.g. save template JSON bundle). |
| Finish | **`Finished`** uses `finish_redirect` when embedded from another flow; otherwise resets to upload. | **Start over** / back navigation as implemented. |

If you change one flow, regression-test the other because they share one template.
