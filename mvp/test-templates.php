<?php
// Test template loading
require_once __DIR__ . '/templates/registry.php';

use WebPdfTimeSaver\Mvp\TemplateRegistry;

$templates = TemplateRegistry::load();

echo "Templates loaded: " . count($templates) . "\n\n";

foreach ($templates as $id => $template) {
    echo "ID: " . $id . "\n";
    echo "Code: " . ($template['code'] ?? 'N/A') . "\n";
    echo "Name: " . ($template['name'] ?? 'N/A') . "\n";
    echo "Fields: " . count($template['fields'] ?? []) . "\n";
    echo "---\n";
}

