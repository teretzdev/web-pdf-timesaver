-- form_templates.form_location — run on the SAME database your app uses (see config/db_env.php).
-- If you already have detected_firm_name, run ONLY the ALTER in section A.
-- "Duplicate column name" means that step is already applied — skip it.

-- -----------------------------------------------------------------------------
-- A) Add form_location (most common if detected_firm_name already exists)
-- -----------------------------------------------------------------------------
ALTER TABLE form_templates
  ADD COLUMN form_location VARCHAR(1024) NOT NULL DEFAULT '' AFTER detected_firm_name;

-- -----------------------------------------------------------------------------
-- B) Only if your table is old and truly lacks detected_firm_name (run before A; skip if duplicate error)
-- -----------------------------------------------------------------------------
-- ALTER TABLE form_templates
--   ADD COLUMN detected_firm_name VARCHAR(512) NOT NULL DEFAULT '' AFTER source_file_name;

-- -----------------------------------------------------------------------------
-- Verify (each should return one row)
-- -----------------------------------------------------------------------------
-- SHOW COLUMNS FROM form_templates LIKE 'detected_firm_name';
-- SHOW COLUMNS FROM form_templates LIKE 'form_location';
-- SELECT DATABASE();
