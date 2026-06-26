<?php
// Debug template generation in web context
require_once __DIR__ . '/templates/registry.php';
use WebPdfTimeSaver\Mvp\TemplateRegistry;

$templateId = 't_fl105_gc120';
echo "<h1>Template Generation Debug</h1>";
echo "<p>Testing template ID: $templateId</p>";

try {
    $template = TemplateRegistry::getTemplate($templateId);
    echo "<h2>✅ Template Generated Successfully!</h2>";
    echo "<p><strong>Template ID:</strong> " . htmlspecialchars($template['id']) . "</p>";
    echo "<p><strong>Template Code:</strong> " . htmlspecialchars($template['code']) . "</p>";
    echo "<p><strong>Template Name:</strong> " . htmlspecialchars($template['name']) . "</p>";
    echo "<p><strong>Panels count:</strong> " . count($template['panels']) . "</p>";
    echo "<p><strong>Fields count:</strong> " . count($template['fields']) . "</p>";
    
    echo "<h3>Panels:</h3><ul>";
    foreach ($template['panels'] as $panel) {
        echo "<li>" . htmlspecialchars($panel['label']) . " (ID: " . htmlspecialchars($panel['id']) . ")</li>";
    }
    echo "</ul>";
    
    echo "<h3>First 20 fields:</h3><ul>";
    $count = 0;
    foreach ($template['fields'] as $field) {
        if ($count >= 20) break;
        echo "<li>" . htmlspecialchars($field['label']) . " (" . htmlspecialchars($field['key']) . ") - " . htmlspecialchars($field['type']) . "</li>";
        $count++;
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error:</h2>";
    echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<h3>Stack Trace:</h3>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

