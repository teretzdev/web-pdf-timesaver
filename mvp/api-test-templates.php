<?php
// Test API endpoint to check template loading
header('Content-Type: application/json');

require_once __DIR__ . '/templates/registry.php';

use WebPdfTimeSaver\Mvp\TemplateRegistry;

try {
    $templates = TemplateRegistry::load();
    
    $simplified = [];
    foreach ($templates as $id => $template) {
        $simplified[] = [
            'id' => $id,
            'code' => $template['code'] ?? 'N/A',
            'name' => $template['name'] ?? 'N/A',
            'fieldCount' => count($template['fields'] ?? []),
            'panelCount' => count($template['panels'] ?? [])
        ];
    }
    
    echo json_encode([
        'success' => true,
        'count' => count($templates),
        'templates' => $simplified
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}

