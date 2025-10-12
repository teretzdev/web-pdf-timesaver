<?php
/**
 * Comprehensive cache clearing script
 * Visit this page to clear all caches: http://localhost/Web-PDFTimeSaver/mvp/clear-all-caches.php
 */

echo "<html><head><title>Cache Clearing</title></head><body>";
echo "<h1>Cache Clearing Results</h1>";

// 1. Clear OpCache
if (function_exists('opcache_reset')) {
    $result = opcache_reset();
    echo "<p>✓ OpCache cleared: " . ($result ? "<strong>Success</strong>" : "<em>Failed</em>") . "</p>";
} else {
    echo "<p>⚠ OpCache not available</p>";
}

// 2. Clear APCu cache if available
if (function_exists('apcu_clear_cache')) {
    apcu_clear_cache();
    echo "<p>✓ APCu cache cleared</p>";
} else {
    echo "<p>⚠ APCu not available</p>";
}

// 3. Clear realpath cache
clearstatcache(true);
echo "<p>✓ Realpath cache cleared</p>";

// 4. Test template loading
require_once __DIR__ . '/templates/registry.php';
use WebPdfTimeSaver\Mvp\TemplateRegistry;

try {
    $templates = TemplateRegistry::load();
    echo "<p>✓ Templates loaded successfully: <strong>" . count($templates) . " templates</strong></p>";
    
    echo "<h2>Available Templates:</h2>";
    echo "<ul>";
    foreach ($templates as $id => $template) {
        echo "<li><strong>" . htmlspecialchars($template['code'] ?? 'N/A') . "</strong> - " 
           . htmlspecialchars($template['name'] ?? 'N/A') 
           . " (" . count($template['fields'] ?? []) . " fields)</li>";
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Error loading templates: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<h2>Next Steps:</h2>";
echo "<ol>";
echo "<li>Clear your browser cache (Ctrl+Shift+Delete or Ctrl+F5)</li>";
echo "<li><a href='?route=templates'>Visit the Templates Page</a></li>";
echo "<li>If you still don't see FL-100, check the list above to confirm it's loaded</li>";
echo "</ol>";

echo "</body></html>";

