# Field Manager: Sample text (`sample_text`)

The original Phase 1 brief asked to store sample-oriented content under **Field Manager → Client** (and Case) instead of only on the upload step.

**Implementation:** a field type **`sample_text`** (label **Sample text**) plus a **Sample text** column. Values are stored in the catalog row’s **`value_text`** (`value` in the JSON store). That string participates in the same catalog / PDF matching pipeline as other types when a template field links to that custom field.

**Using it:** `?route=field-manager` → Client or Case steps → set **Field type** to **Sample text** and fill **Sample text**; auto-save persists the row.

This is slightly more structured than a single generic “sample” column on every field, but it matches “custom info” and test/export behavior.
