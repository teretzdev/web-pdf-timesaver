<?php
// Dedicated Form Management entrypoint (Phase 1).
// Reuses the shared processor view while forcing Form Management mode.
$viewMode = 'form-management';
$formCustomFields = is_array($formCustomFields ?? null) ? $formCustomFields : [];
require __DIR__ . '/universal_processor.php';
