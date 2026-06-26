<?php
// Dedicated Add New Form entrypoint.
// Reuses the shared processor view while forcing Form New mode.
$viewMode = 'form-new';
$formCustomFields = is_array($formCustomFields ?? null) ? $formCustomFields : [];
require __DIR__ . '/universal_processor.php';
