<?php
/**
 * Clear PHP OpCache
 * Run this file to clear server-side PHP caching
 */

// Clear OpCache
if (function_exists('opcache_reset')) {
    $result = opcache_reset();
    echo "OpCache cleared: " . ($result ? "Success" : "Failed") . "\n";
} else {
    echo "OpCache not available\n";
}

// Clear APCu cache if available
if (function_exists('apcu_clear_cache')) {
    apcu_clear_cache();
    echo "APCu cache cleared\n";
}

// Clear realpath cache
clearstatcache(true);
echo "Realpath cache cleared\n";

echo "\nCache clearing complete. Please refresh your browser.\n";
?>

