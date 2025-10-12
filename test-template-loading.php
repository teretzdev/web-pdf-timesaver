<?php
header('Content-Type: text/html; charset=utf-8');

echo "<h1>Template Loading Test</h1>";
echo "<pre>";

try {
    require_once __DIR__ . '/mvp/templates/registry.php';
    
    $templates = \WebPdfTimeSaver\Mvp\TemplateRegistry::load();
    
    echo "Templates loaded successfully!\n";
    echo "Total templates: " . count($templates) . "\n\n";
    
    foreach ($templates as $id => $template) {
        echo "Template ID: {$id}\n";
        echo "  Code: " . ($template['code'] ?? 'N/A') . "\n";
        echo "  Name: " . ($template['name'] ?? 'N/A') . "\n";
        echo "  Fields: " . count($template['fields'] ?? []) . "\n";
        echo "  Panels: " . count($template['panels'] ?? []) . "\n";
        echo "\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

echo "</pre>";
?>

