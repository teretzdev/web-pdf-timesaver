<?php
require_once __DIR__ . '/mvp/templates/registry.php';
use WebPdfTimeSaver\Mvp\TemplateRegistry;

echo "Testing template generation for t_fl105_gc120...\n";

try {
    $template = TemplateRegistry::getTemplate('t_fl105_gc120');
    echo "Template generated successfully!\n";
    echo "Template ID: " . $template['id'] . "\n";
    echo "Template Code: " . $template['code'] . "\n";
    echo "Template Name: " . $template['name'] . "\n";
    echo "Panels count: " . count($template['panels']) . "\n";
    echo "Fields count: " . count($template['fields']) . "\n";
    
    echo "\nPanels:\n";
    foreach ($template['panels'] as $panel) {
        echo "- " . $panel['label'] . " (ID: " . $panel['id'] . ")\n";
    }
    
    echo "\nFirst 10 fields:\n";
    $count = 0;
    foreach ($template['fields'] as $field) {
        if ($count >= 10) break;
        echo "- " . $field['label'] . " (" . $field['key'] . ") - " . $field['type'] . "\n";
        $count++;
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
