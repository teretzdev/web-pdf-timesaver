<?php
// Minimal MVP router (non-breaking; lives under /mvp)

declare(strict_types=1);

// Enable error reporting for logging (errors are not displayed to users)
error_reporting(E_ALL);
ini_set('display_errors', '0'); // Don't display errors to users
ini_set('log_errors', '1');

// When running under PHP's built-in dev server (php -S ... index.php), let it serve
// existing static assets (uploads, images, css, js) directly instead of routing them
// through this script. No-op under Apache/nginx (SAPI is never 'cli-server' there).
if (php_sapi_name() === 'cli-server') {
    $reqPath = parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
    if (is_string($reqPath) && $reqPath !== '/' && preg_match('/\.(png|jpe?g|gif|svg|webp|css|js|ico|woff2?|ttf|map|pdf)$/i', $reqPath)) {
        $staticFile = __DIR__ . $reqPath;
        if (is_file($staticFile)) {
            return false; // serve the requested file as-is
        }
    }
}

// Set up shutdown function to catch fatal errors (set_error_handler can't catch fatal errors)
register_shutdown_function(function() {
    try {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            @error_log("Fatal error: " . $error['message'] . " in " . $error['file'] . " on line " . $error['line']);
            
            if (isset($_SERVER['HTTP_HOST'])) {
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $baseUrl = $protocol . '://' . $_SERVER['HTTP_HOST'];
                $url = $baseUrl . '/error-check.php?type=' . urlencode('Fatal Error') . 
                       '&message=' . urlencode($error['message']) . 
                       '&file=' . urlencode($error['file']) . 
                       '&line=' . urlencode((string)$error['line']);
                
                if (!headers_sent()) {
                    @header('Location: ' . $url);
                    @header('HTTP/1.1 302 Found');
                } else {
                    echo '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($url, ENT_QUOTES) . '"></head><body>Redirecting...</body></html>';
                }
            }
        }
    } catch (Throwable $e) {
        // If shutdown function itself errors, just log it
        @error_log("Shutdown function error: " . $e->getMessage());
    }
});

// Set up exception handler for uncaught exceptions
set_exception_handler(function($exception) {
    try {
        @error_log("Uncaught exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine());
        
        if (isset($_SERVER['HTTP_HOST'])) {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $baseUrl = $protocol . '://' . $_SERVER['HTTP_HOST'];
            $url = $baseUrl . '/error-check.php?type=' . urlencode('Uncaught Exception') . 
                   '&message=' . urlencode($exception->getMessage()) . 
                   '&file=' . urlencode($exception->getFile()) . 
                   '&line=' . urlencode((string)$exception->getLine());
            
            if (!headers_sent()) {
                @header('Location: ' . $url);
            } else {
                echo '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($url, ENT_QUOTES) . '"></head><body>Redirecting...</body></html>';
            }
        }
    } catch (Throwable $e) {
        @error_log("Exception handler error: " . $e->getMessage());
    }
    exit;
});

// Set basic no-cache headers (only if headers not sent yet)
if (!headers_sent()) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
}

// Check for Composer dependencies (vendor/autoload.php)
$vendorAutoload = __DIR__ . '/../vendor/autoload.php';
if (!file_exists($vendorAutoload)) {
    // Log error and redirect to diagnostics
    error_log('ERROR: Composer dependencies not installed. Missing: ' . $vendorAutoload);
    
    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    
    // Use root-level error-check.php
    $url = $baseUrl . '/error-check.php?type=Missing Dependencies&message=' . urlencode('Composer dependencies not installed. Missing: vendor/autoload.php');
    
    if (!headers_sent()) {
        header('Location: ' . $url);
    } else {
        echo '<script>window.location.href="' . htmlspecialchars($url, ENT_QUOTES) . '";</script>';
    }
    exit;
}

// If we can't load core files, show diagnostics
try {
    // Load core files with error handling
} catch (Throwable $e) {
    error_log('ERROR loading core files: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    
    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $requestUri = $_SERVER['REQUEST_URI'] ?? '/mvp/';
    $path = dirname(parse_url($requestUri, PHP_URL_PATH));
    if ($path === '/mvp' || $path === '/') {
        $diagnosticsUrl = '/mvp/views/error_diagnostics.php';
    } else {
        $diagnosticsUrl = $path . '/views/error_diagnostics.php';
    }
    
    header('Location: ' . $baseUrl . $diagnosticsUrl . '?type=Core Files Error&message=' . urlencode($e->getMessage()) . '&file=' . urlencode($e->getFile()) . '&line=' . urlencode((string)$e->getLine()));
    exit;
}

// Fallback error page if redirect fails
if (false) {
    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');
    die('
    <!DOCTYPE html>
    <html>
    <head>
        <title>Setup Required</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
            .error { background: #fee; border: 2px solid #f00; padding: 20px; border-radius: 5px; }
            h1 { color: #c00; }
            code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; }
        </style>
    </head>
    <body>
        <div class="error">
            <h1>Composer Dependencies Required</h1>
            <p>The application requires Composer dependencies to be installed.</p>
            <p><strong>Missing:</strong> <code>vendor/autoload.php</code></p>
            <h2>To fix this, run on the server:</h2>
            <pre>cd /var/www/pdftimesaver.desktopmasters.com/public_html
composer install</pre>
            <p>Or if Composer is not installed:</p>
            <pre>curl -sS https://getcomposer.org/installer | php
php composer.phar install</pre>
        </div>
    </body>
    </html>');
}

// Load core files with error handling
try {
    require_once __DIR__ . '/lib/logger.php';
    require_once __DIR__ . '/lib/data.php';
    require_once __DIR__ . '/templates/registry.php';
    
    // These files require vendor/autoload.php, so load them after checking
    require_once __DIR__ . '/lib/fill_service.php';
    require_once __DIR__ . '/lib/pdf_field_service.php';
    require_once __DIR__ . '/lib/file_manager.php';
    require_once __DIR__ . '/lib/custom_field_manager.php';
    require_once __DIR__ . '/lib/template_label.php';
} catch (Throwable $e) {
    error_log('ERROR loading core files: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    
    // Redirect to diagnostics page (use root-level error-check.php)
    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $url = $baseUrl . '/error-check.php?type=Core Files Error&message=' . urlencode($e->getMessage()) . '&file=' . urlencode($e->getFile()) . '&line=' . urlencode((string)$e->getLine());
    
    if (!headers_sent()) {
        header('Location: ' . $url);
    } else {
        echo '<script>window.location.href="' . htmlspecialchars($url, ENT_QUOTES) . '";</script>';
    }
    exit;
}

use WebPdfTimeSaver\Mvp\DataStore;
use WebPdfTimeSaver\Mvp\TemplateRegistry;
use WebPdfTimeSaver\Mvp\FillService;
use WebPdfTimeSaver\Mvp\PdfFieldService;
use WebPdfTimeSaver\Mvp\FileManager;
use WebPdfTimeSaver\Mvp\CustomFieldManager;

// Initialize services with error handling
try {
    // Initialize logger FIRST so it can be passed to all services
    $logger = new \WebPdfTimeSaver\Mvp\Logger();
    
    // Ensure data directory exists and is writable
    $dataDir = __DIR__ . '/../data';
    if (!is_dir($dataDir)) {
        @mkdir($dataDir, 0755, true);
    }
    if (!is_writable($dataDir)) {
        error_log('WARNING: Data directory is not writable: ' . $dataDir);
    }
    
    $store = new DataStore(__DIR__ . '/../data/mvp.json', $logger);
    
    // Ensure output directory exists
    $outputDir = __DIR__ . '/../output';
    if (!is_dir($outputDir)) {
        @mkdir($outputDir, 0755, true);
    }
    
    // Ensure uploads directory exists
    $uploadsDir = __DIR__ . '/../uploads';
    if (!is_dir($uploadsDir)) {
        @mkdir($uploadsDir, 0755, true);
    }
    
    $templates = TemplateRegistry::getAllTemplates();
    $fill = new FillService($outputDir, $logger);
    $pdfFieldService = new PdfFieldService();
    $fileManager = new FileManager($store, $uploadsDir, $logger);
    $customFieldManager = new CustomFieldManager($store, $logger);
} catch (Throwable $e) {
    error_log('ERROR initializing services: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    
    // Redirect to diagnostics page (use root-level error-check.php)
    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $url = $baseUrl . '/error-check.php?type=Initialization Error&message=' . urlencode($e->getMessage()) . '&file=' . urlencode($e->getFile()) . '&line=' . urlencode((string)$e->getLine());
    
    if (!headers_sent()) {
        header('Location: ' . $url);
    } else {
        echo '<script>window.location.href="' . htmlspecialchars($url, ENT_QUOTES) . '";</script>';
    }
    exit;
}

// Toggle verbose debug logging with env MVP_DEBUG_LOG=1
$isDebug = getenv('MVP_DEBUG_LOG') === '1';

/**
 * Lab / diagnostic HTML routes (debug PDF pages, test runners). Disabled on typical production.
 * Enable with APP_DEBUG=1, MVP_DEV_ROUTES=1, or APP_ENV=local|dev|development.
 */
function mvpDevHtmlRoutesEnabled(): bool {
    $truthy = static function ($v): bool {
        if ($v === false) {
            return false;
        }
        if ($v === null || $v === '') {
            return false;
        }
        $v = strtolower(trim($v));
        return in_array($v, ['1', 'true', 'yes', 'on'], true);
    };
    if ($truthy(getenv('APP_DEBUG')) || $truthy(getenv('MVP_DEV_ROUTES'))) {
        return true;
    }
    $env = strtolower(trim((string)(getenv('APP_ENV') ?: '')));
    return in_array($env, ['local', 'dev', 'development'], true);
}

function mvpAbortUnlessDevHtmlRoutes(): void {
    if (mvpDevHtmlRoutesEnabled()) {
        return;
    }
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Not found';
    exit;
}

/**
 * Strip filesystem paths from universal diagnostics JSON when lab routes are off (typical production).
 * Keeps booleans/checks so Form Management still shows pass/fail without leaking server layout.
 */
function mvpRedactUniversalDiagnosticsStatus(array $status): array {
    if (mvpDevHtmlRoutesEnabled()) {
        return $status;
    }
    if (isset($status['composer']) && is_array($status['composer'])) {
        unset($status['composer']['autoload_path']);
    }
    if (!empty($status['paths']) && is_array($status['paths'])) {
        foreach ($status['paths'] as $key => $info) {
            if (is_array($info)) {
                $status['paths'][$key]['path'] = '(hidden)';
            }
        }
    }
    if (!empty($status['node']) && is_array($status['node'])) {
        $status['node']['path'] = null;
    }
    if (isset($status['auto_extractor_error'])) {
        $status['auto_extractor_error'] = 'Hidden in production (enable MVP_DEV_ROUTES or APP_DEBUG for details).';
    }
    if (!empty($status['phase1_mysql']) && is_array($status['phase1_mysql']) && array_key_exists('firm_id', $status['phase1_mysql'])) {
        $status['phase1_mysql']['firm_id'] = '(hidden)';
    }
    return $status;
}

/**
 * Resolve a PDF selected from uploads: accepts basename (preferred) or legacy absolute path under uploads.
 */
function resolveUploadsPdfSelection(string $raw, string $uploadsDirReal): ?string {
    $uploadsDirReal = realpath($uploadsDirReal);
    if ($uploadsDirReal === false || !is_dir($uploadsDirReal)) {
        return null;
    }
    $trimmed = trim($raw);
    if ($trimmed === '') {
        return null;
    }
    $basename = basename(str_replace('\\', '/', $trimmed));
    if ($basename === '' || $basename === '.' || $basename === '..') {
        return null;
    }
    if (!preg_match('/\.pdf$/i', $basename)) {
        return null;
    }
    $candidate = $uploadsDirReal . DIRECTORY_SEPARATOR . $basename;
    $resolved = realpath($candidate);
    if ($resolved !== false && is_file($resolved)) {
        $u = strtolower($uploadsDirReal);
        $r = strtolower($resolved);
        if (strncmp($r, $u, strlen($u)) === 0) {
            return $resolved;
        }
    }
    $legacy = realpath($trimmed);
    if ($legacy !== false && is_file($legacy)) {
        $u = strtolower($uploadsDirReal);
        $l = strtolower($legacy);
        if (strncmp($l, $u, strlen($u)) === 0) {
            return $legacy;
        }
    }
    return null;
}

/**
 * Ensure an asset is publicly reachable under mvp/uploads and return web path.
 */
function ensureMvpPublicUploadAsset(string $sourceAbsPath, string $preferredName = ''): ?string {
    $source = realpath($sourceAbsPath);
    if ($source === false || !is_file($source)) {
        return null;
    }
    $sourceDir = realpath(dirname($source));
    if ($sourceDir === false) {
        return null;
    }
    $uploadsRoot = realpath(__DIR__ . '/../uploads');
    if ($uploadsRoot === false || !is_dir($uploadsRoot)) {
        return null;
    }
    $u = strtolower($uploadsRoot);
    $s = strtolower($source);
    if (strncmp($s, $u, strlen($u)) !== 0) {
        return null;
    }

    $targetDir = __DIR__ . '/uploads';
    if (!is_dir($targetDir) && !@mkdir($targetDir, 0777, true) && !is_dir($targetDir)) {
        return null;
    }

    $base = trim($preferredName) !== '' ? basename($preferredName) : basename($source);
    if ($base === '' || $base === '.' || $base === '..') {
        return null;
    }
    $target = $targetDir . DIRECTORY_SEPARATOR . $base;

    $copyNeeded = !is_file($target);
    if (!$copyNeeded) {
        $srcMtime = @filemtime($source) ?: 0;
        $dstMtime = @filemtime($target) ?: 0;
        $srcSize = @filesize($source) ?: 0;
        $dstSize = @filesize($target) ?: 0;
        if ($srcMtime > $dstMtime || $srcSize !== $dstSize) {
            $copyNeeded = true;
        }
    }
    if ($copyNeeded) {
        if (!@copy($source, $target)) {
            return null;
        }
    }

    return 'uploads/' . $base;
}

// Input validation and sanitization helpers
function sanitizeString(string $input, int $maxLength = 255): string {
    $sanitized = strip_tags(trim($input));
    return mb_substr($sanitized, 0, $maxLength);
}

function sanitizeId(string $input): string {
    // IDs should only contain alphanumeric, underscore, and hyphen
    return preg_replace('/[^a-zA-Z0-9_-]/', '', $input);
}

function canonicalizePdfFieldKey(string $raw, string $fallback = ''): string {
    $value = trim($raw);
    if ($value === '') {
        return trim($fallback);
    }
    $value = preg_replace('/\[(\d+)\]/', '_$1', $value);
    $value = preg_replace('/[^A-Za-z0-9]+/', '_', (string)$value);
    $value = preg_replace('/_+/', '_', (string)$value);
    $value = trim((string)$value, '_');
    if ($value === '') {
        return trim($fallback);
    }
    return $value;
}

function validateEmail(string $email): string {
    $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '';
}

function validatePhone(string $phone): string {
    // Remove all non-numeric characters except + and spaces
    return preg_replace('/[^0-9+\s()-]/', '', $phone);
}

function validateDate(string $date): string {
    // Basic date validation
    $timestamp = strtotime($date);
    return $timestamp !== false ? date('Y-m-d', $timestamp) : '';
}

function validateRoute(string $route): string {
    // Routes: lowercase letters, numbers, slashes, hyphens, underscores
    $sanitized = preg_replace('/[^a-z0-9\/_-]/', '', strtolower($route));
    return mb_substr($sanitized, 0, 100);
}

/** Phase 1 / multi-tenant: firm scope for alignment rows. Override with env FIRM_ID. */
function resolveCurrentFirmId(): string {
    $raw = trim((string)(getenv('FIRM_ID') ?: ''));
    if ($raw === '') {
        return 'default_firm';
    }
    $id = sanitizeId($raw);
    return $id !== '' ? $id : 'default_firm';
}

function sanitizeFlashMessage(string $message, int $maxLength = 220): string {
    $clean = trim(strip_tags($message));
    return mb_substr($clean, 0, $maxLength);
}

function buildRouteUrl(string $route, array $params = []): string {
    $query = array_merge(['route' => $route], $params);
    return '?' . http_build_query($query);
}

function redirectToRoute(string $route, array $params = []): void {
    header('Location: ' . buildRouteUrl($route, $params));
    exit;
}

function resolveOutputFilePath(string $filename): ?string {
    $safeName = basename(trim($filename));
    if ($safeName === '') {
        return null;
    }

    $outputDir = realpath(__DIR__ . '/../output');
    if ($outputDir === false || !is_dir($outputDir)) {
        return null;
    }

    $candidatePath = $outputDir . DIRECTORY_SEPARATOR . $safeName;
    if (!is_file($candidatePath)) {
        return null;
    }

    return $candidatePath;
}

/**
 * Guard against leaking internal preset connector tokens into user-facing form output.
 * These values are metadata-like selectors (e.g. "Court Information Fields::fcf_xxx"),
 * not real caption content, and should never be rendered/exported as field text.
 */
function isInternalPresetTokenValue(string $value): bool {
    $trimmed = trim($value);
    if ($trimmed === '') {
        return false;
    }
    if (stripos($trimmed, '::fcf_') !== false) {
        return true;
    }
    return (bool)preg_match('/^[a-z][a-z ]*fields::[a-z0-9_:-]+$/i', $trimmed);
}

/**
 * Remove internal preset connector tokens from plain field values.
 *
 * @param array<string,mixed> $values
 * @return array<string,mixed>
 */
function sanitizeRenderableFieldValues(array $values): array {
    foreach ($values as $key => $rawValue) {
        if (!is_string($key) || $key === '' || strncmp($key, '_', 1) === 0) {
            continue;
        }
        $value = is_scalar($rawValue) ? trim((string)$rawValue) : '';
        if ($value !== '' && isInternalPresetTokenValue($value)) {
            $values[$key] = '';
        }
    }
    return $values;
}

/** @return array<int, array{key:string,label:string}> */
function loadTemplateFieldKeysFast(string $templateId): array {
	$file = __DIR__ . '/../data/' . $templateId . '_positions.json';
	if (!is_file($file)) {
		return [];
	}
	$raw = @file_get_contents($file);
	if ($raw === false) {
		return [];
	}
	$data = json_decode($raw, true);
	if (!is_array($data)) {
		return [];
	}
	$fields = [];
	foreach ($data as $k => $v) {
		$key = is_string($k) ? $k : ((is_array($v) && isset($v['name'])) ? (string)$v['name'] : '');
		if ($key === '') { continue; }
		$fields[] = ['key' => $key, 'label' => $key];
	}
	return $fields;
}

function buildUniversalTestDataFromFieldNames(array $fieldNames): array {
    $data = [];

    foreach ($fieldNames as $name) {
        $fieldName = (string)$name;
        $lower = strtolower($fieldName);

        if (strpos($lower, 'checkbox') !== false || strpos($lower, 'check') !== false || strpos($lower, 'box') !== false) {
            $data[$fieldName] = '1';
        } else {
            $data[$fieldName] = 'Sample';
        }
    }

    return $data;
}

function isUniversalDemoValueProvided($value): bool {
    if ($value === null) {
        return false;
    }

    if (is_string($value)) {
        return trim($value) !== '';
    }

    if (is_array($value)) {
        return !empty($value);
    }

    return true;
}

/**
 * Write structured logs for universal export attempts.
 *
 * @param mixed $loggerInstance
 * @param array<string, mixed> $context
 */
function logUniversalGenerateEvent($loggerInstance, string $level, string $message, array $context = []): void {
    $context['at'] = date('c');
    $line = '[universal-generate] ' . $message . ' ' . json_encode($context, JSON_UNESCAPED_SLASHES);

    if ($loggerInstance && is_object($loggerInstance) && method_exists($loggerInstance, $level)) {
        try {
            $loggerInstance->{$level}('universal-generate: ' . $message, $context);
        } catch (\Throwable $e) {
            // Fall through to error_log below.
        }
    }
    @error_log($line);
}

/**
 * Merge DB custom-field catalog values onto PDF field keys (must mirror universal_processor.js).
 * Explicit customFieldLinkId wins; then alias-entry fallback with scope/priority/component filters.
 *
 * @param array<string, mixed> $fillValues
 * @param array<string, array<string, mixed>> $positions
 * @param array<int, array<string, mixed>> $catalogRows
 * @param array<int, array<string, mixed>>|null $explainRows
 * @return array<string, mixed>
 */
/**
 * Build autofill values for the populate form. Maps the well-known California
 * form field segments (AttyName, AttyFirm, BarNo, AttyStreet, Party1,
 * CaseNumber, ...) to data already on file: Firm Information defaults, the
 * matter's client, and the project case number.
 *
 * Returns [ templateFieldKey => value ] containing only resolvable, non-empty
 * values. The caller decides whether to apply them (only to empty fields, so
 * user-entered data is never overwritten). Wrapped in try/catch so a data
 * problem can never break the populate page.
 *
 * @return array<string,string>
 */
function computePopulateAutofillValues($store, array $projDoc, array $template): array {
    $out = [];
    try {
        $norm = static function (string $s): string {
            return (string)preg_replace('/[^a-z0-9]+/', '', strtolower(trim($s)));
        };

        // Firm Information values, keyed by normalized linkId and matchingTag so
        // whatever the user configured (firm_name, attorney_name, bar_number,
        // phone, email, address, ...) is resolvable by concept.
        $firmVals = [];
        if (method_exists($store, 'getFirmDefaultFields')) {
            foreach ((array)$store->getFirmDefaultFields() as $r) {
                if (!is_array($r)) { continue; }
                $v = trim((string)($r['value'] ?? ''));
                if ($v === '' || isInternalPresetTokenValue($v)) { continue; }
                $indexFirmKey = static function (string $key) use (&$firmVals, $norm, $v): void {
                    $nk = $norm($key);
                    if ($nk !== '' && !isset($firmVals[$nk])) {
                        $firmVals[$nk] = $v;
                    }
                };
                foreach ([(string)($r['linkId'] ?? ''), (string)($r['matchingTag'] ?? '')] as $k) {
                    $indexFirmKey($k);
                }
                // Wildcard tags (e.g. AttyInfo*AttyFirm, AttyInfo_?_Email) — index each segment
                // so populate autofill can resolve Field Manager tags to PDF field concepts.
                $tagRaw = trim((string)($r['matchingTag'] ?? ''));
                if ($tagRaw !== '') {
                    foreach (preg_split('/[\*\?\#\_]+/', $tagRaw, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $seg) {
                        $indexFirmKey((string)$seg);
                    }
                }
                $displayName = trim((string)($r['displayName'] ?? ''));
                if ($displayName !== '') {
                    $indexFirmKey($displayName);
                }
            }
        }
        $firm = static function (array $cands) use ($firmVals): string {
            foreach ($cands as $c) {
                if (isset($firmVals[$c]) && $firmVals[$c] !== '') { return $firmVals[$c]; }
            }
            return '';
        };

        // Matter / client data.
        $clientName = '';
        $projectId = (string)($projDoc['projectId'] ?? '');
        $project = (method_exists($store, 'getProject') && $projectId !== '') ? $store->getProject($projectId) : null;
        if (is_array($project) && !empty($project['clientId']) && method_exists($store, 'getClient')) {
            $client = $store->getClient((string)$project['clientId']);
            if (is_array($client)) {
                $clientName = trim((string)($client['displayName'] ?? ''));
            }
        }

        // Case number and court values (project view config).
        $caseNumber = '';
        $courtVals = [];
        if (method_exists($store, 'getProjectViewConfig') && $projectId !== '') {
            $cfg = (array)$store->getProjectViewConfig($projectId);
            $caseNumber = trim((string)($cfg['caseNumber'] ?? ''));
            if (isInternalPresetTokenValue($caseNumber)) {
                $caseNumber = '';
            }
            $courtValuesRaw = is_array($cfg['courtValues'] ?? null) ? $cfg['courtValues'] : [];
            if (method_exists($store, 'getFieldManagerCustomFields')) {
                foreach ((array)$store->getFieldManagerCustomFields('court') as $r) {
                    if (!is_array($r)) { continue; }
                    $fid = (string)($r['id'] ?? '');
                    $v = trim((string)($courtValuesRaw[$fid] ?? ''));
                    if ($v === '' || isInternalPresetTokenValue($v)) { continue; }
                    foreach ([(string)($r['linkId'] ?? ''), (string)($r['matchingTag'] ?? '')] as $k) {
                        $nk = $norm($k);
                        if ($nk !== '' && !isset($courtVals[$nk])) { $courtVals[$nk] = $v; }
                    }
                }
            }
        }
        $court = static function (array $cands) use ($courtVals): string {
            foreach ($cands as $c) {
                if (isset($courtVals[$c]) && $courtVals[$c] !== '') { return $courtVals[$c]; }
            }
            return '';
        };

        // Attorney values (project snapshot — preferred over firm defaults for Atty* fields).
        $attorneyVals = [];
        if (method_exists($store, 'getProjectViewConfig') && $projectId !== '') {
            $cfgAttorney = (array)$store->getProjectViewConfig($projectId);
            $attorneyValuesRaw = is_array($cfgAttorney['attorneyValues'] ?? null) ? $cfgAttorney['attorneyValues'] : [];
            if (method_exists($store, 'getFieldManagerCustomFields')) {
                foreach ((array)$store->getFieldManagerCustomFields('attorney') as $r) {
                    if (!is_array($r)) { continue; }
                    $fid = (string)($r['id'] ?? '');
                    $v = trim((string)($attorneyValuesRaw[$fid] ?? ''));
                    if ($v === '' || isInternalPresetTokenValue($v)) { continue; }
                    foreach ([(string)($r['linkId'] ?? ''), (string)($r['matchingTag'] ?? '')] as $k) {
                        $nk = $norm($k);
                        if ($nk !== '' && !isset($attorneyVals[$nk])) { $attorneyVals[$nk] = $v; }
                    }
                    $tagRaw = trim((string)($r['matchingTag'] ?? ''));
                    if ($tagRaw !== '') {
                        foreach (preg_split('/[\*\?\#\_]+/', $tagRaw, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $seg) {
                            $nk = $norm((string)$seg);
                            if ($nk !== '' && !isset($attorneyVals[$nk])) { $attorneyVals[$nk] = $v; }
                        }
                    }
                    $displayName = trim((string)($r['displayName'] ?? ''));
                    if ($displayName !== '') {
                        $nk = $norm($displayName);
                        if ($nk !== '' && !isset($attorneyVals[$nk])) { $attorneyVals[$nk] = $v; }
                    }
                }
            }
        }
        $attorney = static function (array $cands) use ($attorneyVals, $firm): string {
            foreach ($cands as $c) {
                if (isset($attorneyVals[$c]) && $attorneyVals[$c] !== '') { return $attorneyVals[$c]; }
            }
            return $firm($cands);
        };
        $attorneyOnly = static function (array $cands) use ($attorneyVals): string {
            foreach ($cands as $c) {
                if (isset($attorneyVals[$c]) && $attorneyVals[$c] !== '') { return $attorneyVals[$c]; }
            }
            return '';
        };
        $firmFirst = static function (array $cands) use ($firmVals, $attorneyVals): string {
            foreach ($cands as $c) {
                if (isset($firmVals[$c]) && $firmVals[$c] !== '') { return $firmVals[$c]; }
            }
            foreach ($cands as $c) {
                if (isset($attorneyVals[$c]) && $attorneyVals[$c] !== '') { return $attorneyVals[$c]; }
            }
            return '';
        };

        foreach ((array)($template['fields'] ?? []) as $fld) {
            if (!is_array($fld)) { continue; }
            if ((string)($fld['type'] ?? 'text') === 'checkbox') { continue; }
            $fk = (string)($fld['key'] ?? '');
            if ($fk === '') { continue; }
            $orig = (string)($fld['metadata']['originalName'] ?? '');
            $hay = $norm($orig !== '' ? $orig : $fk);
            if ($hay === '') { continue; }
            $has = static function (string $needle) use ($hay): bool {
                return $needle !== '' && strpos($hay, $needle) !== false;
            };

            $val = '';
            if ($has('attyfirm')) {
                $val = $attorney(['attyfirm', 'attyinfoattyfirm', 'firmname', 'firm', 'attorneyfirm', 'lawfirm']);
            } elseif ($has('attyname')) {
                $val = $attorneyOnly(['attyname', 'attorneyname', 'attorney']);
            } elseif ($has('barno')) {
                $val = $attorneyOnly(['barno', 'barnumber', 'attorneybarnumber', 'statebarnumber', 'statebarno']);
            } elseif ($has('attystreet')) {
                $val = $firmFirst(['attystreet', 'firmstreet', 'attorneystreet', 'firmaddress', 'street', 'address']);
            } elseif ($has('attycity')) {
                $val = $firmFirst(['attycity', 'firmcity', 'attorneycity', 'city']);
            } elseif ($has('attystate')) {
                $val = $firmFirst(['attystate', 'firmstate', 'attorneystate', 'state']);
            } elseif ($has('attyzip')) {
                $val = $firmFirst(['attyzip', 'firmzip', 'attorneyzip', 'zip', 'zipcode']);
            } elseif ($has('attyinfo') && $has('phone')) {
                $val = $attorney(['attyinfophone', 'firmphone', 'attorneyphone', 'phone', 'telephone']);
            } elseif ($has('attyinfo') && $has('fax')) {
                $val = $attorney(['attyinfofax', 'firmfax', 'attorneyfax', 'fax']);
            } elseif ($has('attyinfo') && $has('email')) {
                $val = $attorney(['attyinfoemail', 'firmemail', 'attorneyemail', 'email']);
            } elseif ($has('party1')) {
                $val = $clientName;
            } elseif ($has('casenumber')) {
                $val = $caseNumber;
            } elseif ($has('crtcounty')) {
                $val = $court(['crtcounty', 'county', 'courtcounty']);
            } elseif ($has('courtinfo')) {
                // Court caption fields — scope to CourtInfo so generic tokens (zip, city, street)
                // do not bleed into attorney or party fields on the same form.
                if ($has('courtname') || ($has('court') && $has('name'))) {
                    $val = $court(['courtname', 'name']);
                } elseif ($has('mailingadd')) {
                    $val = $court(['mailingadd', 'mailingaddress', 'courtmailingaddress']);
                } elseif ($has('cityzip')) {
                    $val = $court(['cityzip', 'citystatezip']);
                } elseif ($has('courtcity') || $has('city')) {
                    $val = $court(['courtcity', 'city']);
                } elseif ($has('courtstate') || $has('state')) {
                    $val = $court(['courtstate', 'state', 'statecode']);
                } elseif ($has('courtzip') || $has('zip')) {
                    $val = $court(['courtzip', 'zipcode', 'zip']);
                } elseif ($has('courtphone') || $has('phone')) {
                    $val = $court(['courtphone', 'phone']);
                } elseif ($has('branch')) {
                    $val = $court(['branch', 'courtbranch', 'department']);
                } elseif ($has('department') || $has('dept')) {
                    $val = $court(['courtdept', 'department', 'dept']);
                } elseif ($has('courtroom') || $has('room')) {
                    $val = $court(['courtroom', 'room']);
                } elseif ($has('courtfloor') || $has('floor')) {
                    $val = $court(['courtfloor', 'floor']);
                } elseif ($has('street')) {
                    $val = $court(['street', 'courtstreet', 'courtaddress']);
                }
            } elseif ($has('mailingadd')) {
                $val = $court(['mailingadd', 'mailingaddress', 'courtmailingaddress']);
            } elseif ($has('cityzip')) {
                $val = $court(['cityzip', 'citystatezip']);
            } elseif ($has('branch') && !$has('atty')) {
                $val = $court(['branch', 'courtbranch', 'department']);
            } elseif ($has('street') && !$has('atty')) {
                $val = $court(['street', 'courtstreet', 'courtaddress']);
            }

            if (trim((string)$val) !== '') {
                $out[$fk] = (string)$val;
            }
        }
    } catch (\Throwable $e) {
        return [];
    }
    return $out;
}

/**
 * Whether a template field is filled from Project View / Firm / client caption data
 * (re-applied on each load so project edits propagate to populate & export).
 */
function isProjectCaptionAutofillField(array $template, string $fieldKey): bool {
    foreach ((array)($template['fields'] ?? []) as $fld) {
        if (!is_array($fld)) { continue; }
        if ((string)($fld['key'] ?? '') !== $fieldKey) { continue; }
        $orig = (string)($fld['metadata']['originalName'] ?? '');
        $fk = (string)($fld['key'] ?? '');
        $hay = strtolower((string)preg_replace('/[^a-z0-9]+/', '', $orig !== '' ? $orig : $fk));
        if ($hay === '') { return false; }
        if (strpos($hay, 'courtinfo') !== false) { return true; }
        if (strpos($hay, 'casenumber') !== false) { return true; }
        if (strpos($hay, 'attyinfo') !== false) { return true; }
        if (strpos($hay, 'party1') !== false && strpos($hay, 'party2') === false) { return true; }
        return false;
    }
    return false;
}

/**
 * Merge stored document values with populate autofill. Empty fields are filled;
 * project-caption fields (court, case, firm, client) always take current project data.
 */
function mergeFieldValuesWithAutofill($store, array $projDoc, array $template, array $storedValues): array {
    $merged = sanitizeRenderableFieldValues($storedValues);
    $autofillValues = computePopulateAutofillValues($store, $projDoc, $template ?: []);
    foreach ($autofillValues as $afKey => $afVal) {
        $afVal = trim((string)$afVal);
        if (isInternalPresetTokenValue($afVal)) {
            $afVal = '';
        }
        if ($afVal === '') { continue; }
        $existing = trim((string)($merged[$afKey] ?? ''));
        if ($existing === '' || isProjectCaptionAutofillField($template ?: [], (string)$afKey)) {
            $merged[$afKey] = $afVal;
        }
    }
    return $merged;
}

/**
 * Field Manager preset groups for populate "Connect to saved field" (firm / client / court / case).
 *
 * @return array<string, array<int, array{key:string,label:string,value:string,linkId:string,matchingTag:string}>>
 */
function buildPopulateManagerPresetGroups($store, array $projDoc): array {
    $groups = [];
    $locTitles = [
        'firm' => 'Firm Fields',
        'attorney' => 'Attorney Information Fields',
        'client' => 'Client Fields',
        'court' => 'Court Information Fields',
        'case' => 'Case Fields',
    ];
    if (!method_exists($store, 'getFieldManagerCustomFields')) {
        return $groups;
    }

    $projectId = (string)($projDoc['projectId'] ?? '');
    $cfg = ($projectId !== '' && method_exists($store, 'getProjectViewConfig'))
        ? (array)$store->getProjectViewConfig($projectId)
        : [];
    $courtValues = is_array($cfg['courtValues'] ?? null) ? $cfg['courtValues'] : [];
    $attorneyValues = is_array($cfg['attorneyValues'] ?? null) ? $cfg['attorneyValues'] : [];
    $caseValues = is_array($cfg['caseValues'] ?? null) ? $cfg['caseValues'] : [];
    $caseNumber = trim((string)($cfg['caseNumber'] ?? ''));

    $client = null;
    $clientCustom = [];
    if ($projectId !== '' && method_exists($store, 'getProject')) {
        $project = $store->getProject($projectId);
        if (is_array($project) && !empty($project['clientId']) && method_exists($store, 'getClient')) {
            $clientId = (string)$project['clientId'];
            $client = $store->getClient($clientId);
            if (method_exists($store, 'getClientCustomFieldValues')) {
                $clientCustom = (array)$store->getClientCustomFieldValues($clientId);
            }
        }
    }

    $firmByLink = [];
    if (method_exists($store, 'getFirmDefaultFields')) {
        foreach ((array)$store->getFirmDefaultFields() as $r) {
            if (!is_array($r)) { continue; }
            $lid = strtolower(trim((string)($r['linkId'] ?? '')));
            if ($lid !== '') {
                $firmByLink[$lid] = trim((string)($r['value'] ?? ''));
            }
        }
    }

    $clientStd = [
        'client_display_name' => 'displayName',
        'display_name' => 'displayName',
        'client_email' => 'email',
        'email' => 'email',
        'client_phone' => 'phone',
        'phone' => 'phone',
        'client_company' => 'company',
        'company' => 'company',
        'client_address' => 'address',
        'address' => 'address',
    ];

    foreach (['firm', 'attorney', 'client', 'court', 'case'] as $loc) {
        $list = [];
        foreach ((array)$store->getFieldManagerCustomFields($loc) as $r) {
            if (!is_array($r)) { continue; }
            $fid = (string)($r['id'] ?? '');
            $label = trim((string)($r['displayName'] ?? ''));
            $linkId = strtolower(trim((string)($r['linkId'] ?? '')));
            if ($label === '' || $fid === '') { continue; }

            $val = '';
            if ($loc === 'firm') {
                $val = $firmByLink[$linkId] ?? '';
            } elseif ($loc === 'attorney') {
                $val = trim((string)($attorneyValues[$fid] ?? ''));
            } elseif ($loc === 'court') {
                $val = trim((string)($courtValues[$fid] ?? ''));
            } elseif ($loc === 'case') {
                if ($linkId === 'case_number' || stripos($label, 'case number') !== false) {
                    $val = $caseNumber;
                } else {
                    $val = trim((string)($caseValues[$fid] ?? ''));
                }
            } elseif ($loc === 'client') {
                if (is_array($client)) {
                    if (isset($clientStd[$linkId])) {
                        $val = trim((string)($client[$clientStd[$linkId]] ?? ''));
                    }
                    if ($val === '' && isset($clientCustom[$fid])) {
                        $val = trim((string)$clientCustom[$fid]);
                    }
                    if ($val === '' && $linkId !== '' && isset($clientCustom[$linkId])) {
                        $val = trim((string)$clientCustom[$linkId]);
                    }
                }
            }

            $list[] = [
                'key' => $fid,
                'label' => $label,
                'value' => $val,
                'linkId' => $linkId,
                'matchingTag' => trim((string)($r['matchingTag'] ?? '')),
            ];
        }
        if (!empty($list)) {
            $groups[$locTitles[$loc]] = $list;
        }
    }

    return $groups;
}

function applyUniversalCustomFieldOverrides(
    array $fillValues,
    array $positions,
    array $catalogRows,
    string $matchingMode = 'exact',
    array $aliasEntries = [],
    string $templateId = '',
    ?array &$explainRows = null
): array {
    $matchingMode = strtolower(trim($matchingMode));
    if (!in_array($matchingMode, ['exact', 'regex'], true)) {
        $matchingMode = 'exact';
    }
    $templateId = strtolower(trim($templateId));
    $templateFamily = resolveFormTemplateFamily($templateId);
    $resolvePositionPage = static function (array $position): int {
        $raw = $position['page'] ?? $position['pageNumber'] ?? 1;
        $page = is_numeric($raw) ? (int)$raw : 1;
        return $page > 0 ? $page : 1;
    };
    $maxTemplatePage = 1;
    foreach ($positions as $positionRow) {
        if (!is_array($positionRow)) {
            continue;
        }
        $pageNum = $resolvePositionPage($positionRow);
        if ($pageNum > $maxTemplatePage) {
            $maxTemplatePage = $pageNum;
        }
    }
    $extractFieldNumberInfo = static function (string $fieldKey): array {
        $raw = strtolower(trim($fieldKey));
        if ($raw === '') {
            return ['number' => 0, 'series' => ''];
        }
        preg_match_all('/\d+/', $raw, $m);
        $tokens = is_array($m[0] ?? null) ? $m[0] : [];
        if ($tokens === []) {
            return ['number' => 0, 'series' => preg_replace('/[^a-z]+/', '_', $raw)];
        }
        $number = (int)end($tokens);
        if ($number < 0) {
            $number = 0;
        }
        $series = preg_replace('/\d+/', '#', $raw);
        $series = preg_replace('/[^a-z#]+/', '_', (string)$series);
        $series = trim((string)$series, '_');
        return ['number' => $number, 'series' => $series];
    };
    $maxNumberBySeries = [];
    foreach ($positions as $fieldKeyCandidate => $positionRow) {
        if (!is_string($fieldKeyCandidate)) {
            continue;
        }
        $info = $extractFieldNumberInfo($fieldKeyCandidate);
        $series = (string)($info['series'] ?? '');
        $number = is_numeric($info['number'] ?? null) ? (int)$info['number'] : 0;
        if ($series === '' || $number < 1) {
            continue;
        }
        $existing = $maxNumberBySeries[$series] ?? 0;
        if ($number > $existing) {
            $maxNumberBySeries[$series] = $number;
        }
    }
    $byLink = [];
    foreach ($catalogRows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $lid = trim((string)($row['linkId'] ?? ''));
        if ($lid === '') {
            continue;
        }
        $byLink[$lid] = trim((string)($row['value'] ?? ''));
    }
    $normalizeMatchingToken = static function (string $raw): string {
        $v = strtolower(trim($raw));
        $v = preg_replace('/[^a-z0-9]+/', '_', $v);
        return trim((string)$v, '_');
    };
    $normalizeMatchingPattern = static function (string $raw): string {
        $v = strtolower(trim($raw));
        $v = preg_replace('/[^a-z0-9*?#]+/', '_', $v);
        $v = trim((string)$v, '_');
        return $v;
    };
    $wildcardPatternToRegex = static function (string $pattern): string {
        $escaped = preg_quote($pattern, '~');
        $escaped = str_replace('\*', '.*', $escaped);
        $escaped = str_replace('\?', '.', $escaped);
        $escaped = str_replace('\#', '\d+', $escaped);
        return '~^' . $escaped . '$~i';
    };
    $fieldManagerTagMatches = static function (string $candidate, string $tag, string $mode) use ($normalizeMatchingToken, $normalizeMatchingPattern, $wildcardPatternToRegex): bool {
        $rawCandidate = trim($candidate);
        $rawTag = trim($tag);
        if ($rawCandidate === '' || $rawTag === '') {
            return false;
        }
        $mode = strtolower(trim($mode));
        if ($mode === 'regex') {
            set_error_handler(static fn() => true);
            $ok = @preg_match('~' . str_replace('~', '\~', $rawTag) . '~i', $rawCandidate) === 1;
            restore_error_handler();
            if ($ok) {
                return true;
            }
        }
        $candidateNorm = $normalizeMatchingToken($rawCandidate);
        $tagNorm = $normalizeMatchingToken($rawTag);
        $hasWildcard = preg_match('/[*?#]/', $rawTag) === 1;
        if ($hasWildcard) {
            $patternRaw = $wildcardPatternToRegex(strtolower($rawTag));
            if (preg_match($patternRaw, strtolower($rawCandidate)) === 1) {
                return true;
            }
            $patternNorm = $wildcardPatternToRegex($normalizeMatchingPattern($rawTag));
            if ($candidateNorm !== '' && preg_match($patternNorm, $candidateNorm) === 1) {
                return true;
            }
        }
        if ($candidateNorm === '' || $tagNorm === '') {
            return false;
        }
        if ($candidateNorm === $tagNorm || strpos($candidateNorm, $tagNorm) !== false) {
            return true;
        }
        $tokens = array_values(array_filter(explode('_', $tagNorm), static fn($t) => $t !== ''));
        if ($tokens === []) {
            return false;
        }
        foreach ($tokens as $token) {
            if (strpos($candidateNorm, $token) === false) {
                return false;
            }
        }
        return true;
    };
    $resolvePositionComponentType = static function (array $position): string {
        $raw = strtolower(trim((string)($position['type'] ?? $position['fieldType'] ?? '')));
        if ($raw !== '') {
            if (strpos($raw, 'check') !== false || strpos($raw, 'radio') !== false || strpos($raw, 'option') !== false || strpos($raw, 'choice') !== false) {
                return 'checkable';
            }
            if (strpos($raw, 'textarea') !== false || strpos($raw, 'multi') !== false) {
                return 'textarea';
            }
        }
        return 'text';
    };
    $resolveCatalogComponentType = static function (array $row): string {
        $raw = strtolower(trim((string)($row['fieldType'] ?? $row['field_type'] ?? 'text')));
        if ($raw === '') {
            return 'any';
        }
        if ($raw === 'sample_text') {
            $raw = 'text';
        }
        if (strpos($raw, 'check') !== false || strpos($raw, 'radio') !== false || strpos($raw, 'bool') !== false) {
            return 'checkable';
        }
        if (strpos($raw, 'textarea') !== false || strpos($raw, 'multi') !== false) {
            return 'textarea';
        }
        return 'text';
    };
    $matchingTagMatch = static function (
        string $fieldKey,
        array $position,
        array $catalogRows,
        array $byLink,
        string $matchingMode,
        array &$matchInfo
    ) use ($fieldManagerTagMatches, $resolvePositionComponentType, $resolveCatalogComponentType, $normalizeMatchingToken): ?string {
        $matchInfo = [];
        $raw = trim($fieldKey);
        if ($raw === '') {
            return null;
        }
        $actualComponent = $resolvePositionComponentType($position);
        $candidates = [];
        $candidates[] = $raw;
        $extra = [
            (string)($position['name'] ?? ''),
            (string)($position['canonicalName'] ?? ''),
            (string)($position['fieldName'] ?? ''),
            (string)($position['label'] ?? ''),
        ];
        foreach ($extra as $candidate) {
            $candidate = trim($candidate);
            if ($candidate !== '') {
                $candidates[] = $candidate;
            }
        }
        $best = null;
        foreach ($catalogRows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $linkId = trim((string)($row['linkId'] ?? ''));
            if ($linkId === '' || !array_key_exists($linkId, $byLink)) {
                continue;
            }
            $tag = trim((string)($row['matchingTag'] ?? $row['matching_tag'] ?? $linkId));
            if ($tag === '') {
                continue;
            }
            $targetComponent = $resolveCatalogComponentType($row);
            if ($targetComponent !== 'any') {
                if ($targetComponent === 'text' && !in_array($actualComponent, ['text', 'textarea'], true)) {
                    continue;
                }
                if ($targetComponent !== 'text' && $targetComponent !== $actualComponent) {
                    continue;
                }
            }
            $didMatch = false;
            foreach ($candidates as $candidate) {
                if ($fieldManagerTagMatches($candidate, $tag, $matchingMode)) {
                    $didMatch = true;
                    break;
                }
            }
            if (!$didMatch) {
                continue;
            }
            $tagNorm = $normalizeMatchingToken($tag);
            $tokenCount = $tagNorm === '' ? 0 : count(array_filter(explode('_', $tagNorm), static fn($t) => $t !== ''));
            $score = strlen($tagNorm) * 10 + ($tokenCount * 25);
            $candidate = [
                'score' => $score,
                'linkId' => $linkId,
                'matchingTag' => $tag,
                'componentType' => $targetComponent,
            ];
            if ($best === null || $candidate['score'] > $best['score']) {
                $best = $candidate;
            }
        }
        if ($best === null) {
            return null;
        }
        $matchInfo = [
            'linkId' => (string)$best['linkId'],
            'matchingTag' => (string)$best['matchingTag'],
            'componentType' => (string)$best['componentType'],
            'reason' => 'matching_tag_text',
        ];
        return (string)$best['linkId'];
    };
    $aliasSupportsComponent = static function (array $entry, array $position) use ($resolvePositionComponentType): bool {
        $target = strtolower(trim((string)($entry['componentType'] ?? $entry['component_type'] ?? 'any')));
        if (!in_array($target, ['any', 'text', 'textarea', 'checkable'], true)) {
            $target = 'any';
        }
        if ($target === 'any') {
            return true;
        }
        $actual = $resolvePositionComponentType($position);
        if ($target === 'text') {
            return in_array($actual, ['text', 'textarea'], true);
        }
        return $actual === $target;
    };
    $aliasSupportsScope = static function (array $entry, string $templateId, string $templateFamily): bool {
        $scopeType = strtolower(trim((string)($entry['scopeType'] ?? $entry['scope_type'] ?? 'global')));
        $scopeValue = strtolower(trim((string)($entry['scopeValue'] ?? $entry['scope_value'] ?? '')));
        if (!in_array($scopeType, ['global', 'form_family', 'template'], true)) {
            $scopeType = 'global';
        }
        if ($scopeType === 'global') {
            return true;
        }
        if ($scopeValue === '') {
            return false;
        }
        if ($scopeType === 'template') {
            return $templateId !== '' && $scopeValue === $templateId;
        }
        return $templateFamily !== '' && $scopeValue === $templateFamily;
    };
    $aliasSupportsPage = static function (array $entry, int $fieldPage, int $maxTemplatePage): bool {
        $pageMode = strtolower(trim((string)($entry['pageMode'] ?? $entry['page_mode'] ?? 'any')));
        if (!in_array($pageMode, ['any', 'first', 'last', 'only', 'except'], true)) {
            $pageMode = 'any';
        }
        if ($pageMode === 'any') {
            return true;
        }
        if ($pageMode === 'first') {
            return $fieldPage === 1;
        }
        if ($pageMode === 'last') {
            return $fieldPage === max(1, $maxTemplatePage);
        }
        $rawPageValue = strtolower(trim((string)($entry['pageValue'] ?? $entry['page_value'] ?? '')));
        $tokens = preg_split('/[^0-9]+/', $rawPageValue) ?: [];
        $pages = [];
        foreach ($tokens as $token) {
            if ($token === '') {
                continue;
            }
            $n = (int)$token;
            if ($n < 1 || $n > 9999) {
                continue;
            }
            $pages[$n] = true;
        }
        if ($pages === []) {
            return true;
        }
        $isListed = isset($pages[$fieldPage]);
        if ($pageMode === 'only') {
            return $isListed;
        }
        return !$isListed;
    };
    $aliasSupportsNumber = static function (array $entry, int $fieldNumber, string $fieldSeries, array $maxNumberBySeries): bool {
        $numberMode = strtolower(trim((string)($entry['numberMode'] ?? $entry['number_mode'] ?? 'any')));
        if (!in_array($numberMode, ['any', 'first', 'last', 'only', 'except'], true)) {
            $numberMode = 'any';
        }
        if ($numberMode === 'any') {
            return true;
        }
        if ($fieldNumber < 1) {
            return true;
        }
        if ($numberMode === 'first') {
            return $fieldNumber === 1;
        }
        if ($numberMode === 'last') {
            $max = is_numeric($maxNumberBySeries[$fieldSeries] ?? null) ? (int)$maxNumberBySeries[$fieldSeries] : 0;
            if ($max < 1) {
                return true;
            }
            return $fieldNumber === $max;
        }
        $rawNumberValue = strtolower(trim((string)($entry['numberValue'] ?? $entry['number_value'] ?? '')));
        $tokens = preg_split('/[^0-9]+/', $rawNumberValue) ?: [];
        $numbers = [];
        foreach ($tokens as $token) {
            if ($token === '') {
                continue;
            }
            $n = (int)$token;
            if ($n < 1 || $n > 9999) {
                continue;
            }
            $numbers[$n] = true;
        }
        if ($numbers === []) {
            return true;
        }
        $isListed = isset($numbers[$fieldNumber]);
        if ($numberMode === 'only') {
            return $isListed;
        }
        return !$isListed;
    };
    $normalizeAliasPriority = static function ($value): int {
        $n = is_numeric($value) ? (int)$value : 100;
        if ($n < 1) {
            return 1;
        }
        if ($n > 9999) {
            return 9999;
        }
        return $n;
    };
    $eligibleAliases = [];
    foreach ($aliasEntries as $entry) {
        if (!is_array($entry) || empty($entry['enabled'])) {
            continue;
        }
        $entry['priority'] = $normalizeAliasPriority($entry['priority'] ?? null);
        $entry['scopeType'] = strtolower(trim((string)($entry['scopeType'] ?? $entry['scope_type'] ?? 'global')));
        if (!in_array($entry['scopeType'], ['global', 'form_family', 'template'], true)) {
            $entry['scopeType'] = 'global';
        }
        $entry['scopeValue'] = strtolower(trim((string)($entry['scopeValue'] ?? $entry['scope_value'] ?? '')));
        if ($entry['scopeType'] === 'global') {
            $entry['scopeValue'] = '';
        }
        $entry['pageMode'] = strtolower(trim((string)($entry['pageMode'] ?? $entry['page_mode'] ?? 'any')));
        if (!in_array($entry['pageMode'], ['any', 'first', 'last', 'only', 'except'], true)) {
            $entry['pageMode'] = 'any';
        }
        $entry['pageValue'] = strtolower(trim((string)($entry['pageValue'] ?? $entry['page_value'] ?? '')));
        if (!in_array($entry['pageMode'], ['only', 'except'], true)) {
            $entry['pageValue'] = '';
        }
        $entry['numberMode'] = strtolower(trim((string)($entry['numberMode'] ?? $entry['number_mode'] ?? 'any')));
        if (!in_array($entry['numberMode'], ['any', 'first', 'last', 'only', 'except'], true)) {
            $entry['numberMode'] = 'any';
        }
        $entry['numberValue'] = strtolower(trim((string)($entry['numberValue'] ?? $entry['number_value'] ?? '')));
        if (!in_array($entry['numberMode'], ['only', 'except'], true)) {
            $entry['numberValue'] = '';
        }
        $eligibleAliases[] = $entry;
    }
    usort($eligibleAliases, static function (array $a, array $b): int {
        $pa = (int)($a['priority'] ?? 100);
        $pb = (int)($b['priority'] ?? 100);
        if ($pa !== $pb) {
            return $pa <=> $pb;
        }
        return strcmp((string)($a['id'] ?? ''), (string)($b['id'] ?? ''));
    });
    $aliasMatch = static function (
        string $fieldKey,
        array $position,
        array $aliasEntries,
        array $byLink,
        string $templateId,
        string $templateFamily,
        array &$matchInfo
    ) use ($aliasSupportsComponent, $aliasSupportsScope, $aliasSupportsPage, $aliasSupportsNumber, $resolvePositionPage, $maxTemplatePage, $extractFieldNumberInfo, $maxNumberBySeries): ?string {
        $matchInfo = [];
        $raw = trim($fieldKey);
        if ($raw === '' || empty($aliasEntries)) {
            return null;
        }
        foreach ($aliasEntries as $entry) {
            $linkId = trim((string)($entry['linkId'] ?? ''));
            $pattern = trim((string)($entry['pattern'] ?? ''));
            if ($linkId === '' || $pattern === '') {
                continue;
            }
            if (!array_key_exists($linkId, $byLink)) {
                continue;
            }
            if (!$aliasSupportsScope($entry, $templateId, $templateFamily)) {
                continue;
            }
            if (!$aliasSupportsPage($entry, $resolvePositionPage($position), $maxTemplatePage)) {
                continue;
            }
            $numberInfo = $extractFieldNumberInfo($raw);
            $fieldNumber = is_numeric($numberInfo['number'] ?? null) ? (int)$numberInfo['number'] : 0;
            $fieldSeries = (string)($numberInfo['series'] ?? '');
            if (!$aliasSupportsNumber($entry, $fieldNumber, $fieldSeries, $maxNumberBySeries)) {
                continue;
            }
            if (!$aliasSupportsComponent($entry, $position)) {
                continue;
            }
            set_error_handler(static fn() => true);
            $ok = @preg_match('~' . str_replace('~', '\~', $pattern) . '~i', $raw) === 1;
            restore_error_handler();
            if (!$ok) {
                continue;
            }
            if (!empty($entry['requiresValue']) && trim((string)($byLink[$linkId] ?? '')) === '') {
                continue;
            }
            $matchInfo = [
                'aliasId' => (string)($entry['id'] ?? ''),
                'priority' => (int)($entry['priority'] ?? 100),
                'scopeType' => (string)($entry['scopeType'] ?? 'global'),
                'scopeValue' => (string)($entry['scopeValue'] ?? ''),
                'pageMode' => (string)($entry['pageMode'] ?? 'any'),
                'pageValue' => (string)($entry['pageValue'] ?? ''),
                'numberMode' => (string)($entry['numberMode'] ?? 'any'),
                'numberValue' => (string)($entry['numberValue'] ?? ''),
                'componentType' => (string)($entry['componentType'] ?? 'any'),
                'pattern' => $pattern,
                'reason' => 'alias_pattern_match',
            ];
            return $linkId;
        }
        return null;
    };
    $explain = [];
    $out = $fillValues;
    foreach ($positions as $fieldKey => $pos) {
        if (!is_string($fieldKey) || !is_array($pos)) {
            continue;
        }
        $linkId = trim((string)($pos['customFieldLinkId'] ?? ''));
        $val = null;
        $explainRow = [
            'fieldKey' => $fieldKey,
            'source' => '',
            'linkId' => '',
            'aliasId' => '',
            'manualOverride' => false,
            'overriddenAliasId' => '',
            'priority' => null,
            'scopeType' => '',
            'scopeValue' => '',
            'pageMode' => '',
            'pageValue' => '',
            'numberMode' => '',
            'numberValue' => '',
            'componentType' => '',
            'pattern' => '',
            'reason' => '',
            'appliedValue' => false,
        ];
        if ($linkId !== '') {
            $explainRow['source'] = 'explicit';
            $explainRow['linkId'] = $linkId;
            $explainRow['reason'] = 'explicit_customFieldLinkId';
        }
        if ($linkId === '') {
            $matchInfo = [];
            $autoLinkId = $matchingTagMatch($fieldKey, $pos, $catalogRows, $byLink, $matchingMode, $matchInfo);
            if ($autoLinkId !== null) {
                $linkId = $autoLinkId;
                $explainRow['source'] = 'matching_tag';
                $explainRow['linkId'] = $autoLinkId;
                $explainRow['componentType'] = (string)($matchInfo['componentType'] ?? '');
                $explainRow['reason'] = (string)($matchInfo['reason'] ?? 'matching_tag_text');
            }
        }
        if ($linkId !== '' && isset($byLink[$linkId]) && $byLink[$linkId] !== '') {
            $val = $byLink[$linkId];
        }
        if ($val !== null && $val !== '') {
            $out[$fieldKey] = $val;
            $explainRow['appliedValue'] = true;
        }
        if ($explainRow['source'] !== '') {
            $explain[] = $explainRow;
        }
    }
    if (is_array($explainRows)) {
        $explainRows = $explain;
    }
    return $out;
}

/**
 * Drop extra project documents that share the same form family (e.g. fl-100_* and fl100_*).
 * Keeps the canonical template slug and returns a deduped template order.
 *
 * @return array<int, string>
 */
function pruneDuplicateFamilyProjectDocuments(object $store, string $projectId, array $preferredOrder = []): array
{
    $preferredOrder = dedupeTemplateIdsByFamily($preferredOrder);
    if (!method_exists($store, 'getProjectDocuments') || !method_exists($store, 'deleteProjectDocument')) {
        return $preferredOrder;
    }

    $preferredByFamily = [];
    foreach ($preferredOrder as $tid) {
        $tid = sanitizeId((string)$tid);
        if ($tid === '') {
            continue;
        }
        $family = resolveFormTemplateFamily($tid);
        if ($family !== '' && !isset($preferredByFamily[$family])) {
            $preferredByFamily[$family] = $tid;
        }
    }

    $docs = (array)$store->getProjectDocuments($projectId);
    $byFamily = [];
    foreach ($docs as $doc) {
        if (!is_array($doc)) {
            continue;
        }
        $tid = sanitizeId((string)($doc['templateId'] ?? ''));
        if ($tid === '') {
            continue;
        }
        $family = resolveFormTemplateFamily($tid);
        if ($family === '') {
            continue;
        }
        if (!isset($byFamily[$family])) {
            $byFamily[$family] = [];
        }
        $byFamily[$family][] = $doc;
    }

    $canonicalByFamily = [];
    foreach ($byFamily as $family => $familyDocs) {
        if (count($familyDocs) <= 1) {
            $canonicalByFamily[$family] = sanitizeId((string)($familyDocs[0]['templateId'] ?? ''));
            continue;
        }
        $preferredTid = sanitizeId((string)($preferredByFamily[$family] ?? ''));
        $winner = $familyDocs[0];
        foreach ($familyDocs as $doc) {
            $winnerTid = sanitizeId((string)($winner['templateId'] ?? ''));
            $docTid = sanitizeId((string)($doc['templateId'] ?? ''));
            $keepTid = preferCanonicalTemplateId($winnerTid, $docTid, $preferredTid);
            if ($keepTid === $docTid) {
                $loserId = (string)($winner['id'] ?? '');
                if ($loserId !== '') {
                    $store->deleteProjectDocument($loserId);
                }
                $winner = $doc;
            } else {
                $loserId = (string)($doc['id'] ?? '');
                if ($loserId !== '') {
                    $store->deleteProjectDocument($loserId);
                }
            }
        }
        $canonicalByFamily[$family] = sanitizeId((string)($winner['templateId'] ?? ''));
    }

    if ($preferredOrder !== [] && method_exists($store, 'setProjectViewConfig') && method_exists($store, 'getProjectViewConfig')) {
        $remapped = [];
        foreach ($preferredOrder as $tid) {
            $tid = sanitizeId((string)$tid);
            if ($tid === '') {
                continue;
            }
            $family = resolveFormTemplateFamily($tid);
            if ($family !== '' && isset($canonicalByFamily[$family]) && $canonicalByFamily[$family] !== '') {
                $tid = $canonicalByFamily[$family];
            }
            $remapped[] = $tid;
        }
        $preferredOrder = dedupeTemplateIdsByFamily($remapped);
        $cfg = (array)$store->getProjectViewConfig($projectId);
        $cfg['templateOrder'] = $preferredOrder;
        $store->setProjectViewConfig($projectId, $cfg);
    }

    return $preferredOrder;
}

/**
 * @param array<int, array<string, mixed>> $matchExplain
 */
function persistFormImporterAliasStatsFromExplain(object $store, array $matchExplain): void {
    if (!method_exists($store, 'getFormImporterAliases') || !method_exists($store, 'setFormImporterAliases')) {
        return;
    }
    $aliases = $store->getFormImporterAliases();
    if (!is_array($aliases) || $aliases === []) {
        return;
    }
    $byId = [];
    foreach ($aliases as $idx => $row) {
        if (!is_array($row)) {
            continue;
        }
        $id = trim((string)($row['id'] ?? ''));
        if ($id !== '') {
            $byId[$id] = $idx;
        }
    }
    $changed = false;
    $now = date(DATE_ATOM);
    foreach ($matchExplain as $row) {
        if (!is_array($row)) {
            continue;
        }
        $source = strtolower(trim((string)($row['source'] ?? '')));
        if ($source === 'alias' && !empty($row['appliedValue'])) {
            $aliasId = trim((string)($row['aliasId'] ?? ''));
            if ($aliasId !== '' && array_key_exists($aliasId, $byId)) {
                $idx = (int)$byId[$aliasId];
                if (is_array($aliases[$idx])) {
                    $stats = is_array($aliases[$idx]['stats'] ?? null) ? $aliases[$idx]['stats'] : [];
                    $hits = is_numeric($stats['hits'] ?? null) ? max(0, (int)$stats['hits']) : 0;
                    $manualOverrides = is_numeric($stats['manualOverrides'] ?? null) ? max(0, (int)$stats['manualOverrides']) : 0;
                    $aliases[$idx]['stats'] = [
                        'hits' => $hits + 1,
                        'manualOverrides' => $manualOverrides,
                        'lastMatchedAt' => $now,
                    ];
                    $changed = true;
                }
            }
        }

        if (!empty($row['manualOverride'])) {
            $overriddenAliasId = trim((string)($row['overriddenAliasId'] ?? ''));
            if ($overriddenAliasId !== '' && array_key_exists($overriddenAliasId, $byId)) {
                $idx = (int)$byId[$overriddenAliasId];
                if (is_array($aliases[$idx])) {
                    $stats = is_array($aliases[$idx]['stats'] ?? null) ? $aliases[$idx]['stats'] : [];
                    $hits = is_numeric($stats['hits'] ?? null) ? max(0, (int)$stats['hits']) : 0;
                    $manualOverrides = is_numeric($stats['manualOverrides'] ?? null) ? max(0, (int)$stats['manualOverrides']) : 0;
                    $aliases[$idx]['stats'] = [
                        'hits' => $hits,
                        'manualOverrides' => $manualOverrides + 1,
                        'lastMatchedAt' => trim((string)($stats['lastMatchedAt'] ?? '')),
                    ];
                    $changed = true;
                }
            }
        }
    }
    if ($changed) {
        $store->setFormImporterAliases($aliases);
    }
}

/**
 * Download a single JSON bundle: positions, PDF path, background image paths.
 */
function handleTemplateFinishBundleDownload(): void {
    header('Content-Type: application/json; charset=utf-8');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }
    $templateId = sanitizeId((string)($_POST['template_id'] ?? ''));
    if ($templateId === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'template_id required']);
        exit;
    }
    $dataDir = realpath(__DIR__ . '/../data');
    $uploadsDir = realpath(__DIR__ . '/../uploads');
    if ($dataDir === false || $uploadsDir === false) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server paths not found']);
        exit;
    }
    $positionsFile = $dataDir . DIRECTORY_SEPARATOR . $templateId . '_positions.json';
    if (!is_file($positionsFile)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Positions file not found for this template.']);
        exit;
    }
    $positions = json_decode((string)file_get_contents($positionsFile), true);
    if (!is_array($positions)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid positions JSON']);
        exit;
    }
    $pdfBasename = $templateId . '.pdf';
    $pdfAbs = $uploadsDir . DIRECTORY_SEPARATOR . $pdfBasename;
    $bgPattern = $uploadsDir . DIRECTORY_SEPARATOR . $templateId . '_page*_background.png';
    $bgFiles = glob($bgPattern) ?: [];
    sort($bgFiles, SORT_NATURAL);
    $backgroundsRel = [];
    foreach ($bgFiles as $abs) {
        $backgroundsRel[] = 'uploads/' . basename((string)$abs);
    }
    $bundle = [
        'version' => 1,
        'templateId' => $templateId,
        'exportedAt' => gmdate('c'),
        'pdfFile' => 'uploads/' . $pdfBasename,
        'pdfFileExists' => is_file($pdfAbs),
        'positions' => $positions,
        'backgroundImages' => $backgroundsRel,
    ];
    $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $templateId) . '_template_bundle.json';
    header('Content-Disposition: attachment; filename="' . $safeName . '"');
    echo json_encode($bundle, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Stream a stored template PDF for Form Manager "Modify" flow.
 */
function handleFormTemplatePdfDownload(): void {
    $templateId = sanitizeId((string)($_GET['template_id'] ?? ''));
    $sourceFile = trim((string)($_GET['source_file'] ?? ''));
    $uploadsDir = realpath(__DIR__ . '/../uploads');
    if ($uploadsDir === false || !is_dir($uploadsDir)) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Uploads directory not found.';
        exit;
    }
    if ($templateId === '' && $sourceFile === '') {
        http_response_code(400);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'template_id or source_file is required.';
        exit;
    }

    $candidates = [];
    if ($sourceFile !== '') {
        $candidates[] = $sourceFile;
    }
    if ($templateId !== '') {
        $candidates[] = $templateId . '.pdf';
    }

    $resolvedPath = null;
    foreach ($candidates as $candidate) {
        $path = resolveUploadsPdfSelection((string)$candidate, $uploadsDir);
        if ($path !== null && is_file($path)) {
            $resolvedPath = $path;
            break;
        }
    }

    if ($resolvedPath === null) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Template PDF not found.';
        exit;
    }

    $downloadName = basename((string)$resolvedPath);
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $downloadName) . '"');
    readfile($resolvedPath);
    exit;
}

/**
 * Load stored template fields/backgrounds so Modify can open editor without re-upload.
 */
function splitFormIdentityForEditor(string $templateId, string $storedFormName): array {
    require_once __DIR__ . '/lib/form_title_extractor.php';
    $identity = \WebPdfTimeSaver\Mvp\FormTitleExtractor::parseFormIdentityFromTitle($storedFormName, $templateId, '');
    return [
        'formNumber' => (string)($identity['formNumber'] ?? ''),
        'formName' => (string)($identity['formName'] ?? ''),
    ];
}

/**
 * Resolve canonical form identity for storage using extractor + identifier catalog fallback.
 *
 * @return array{formNumber:string, formName:string, combinedName:string}
 */
function resolveFormIdentityForStorage(
    string $templateId,
    string $formNameInput,
    string $sourceFileName = '',
    string $formNumberInput = ''
): array {
    require_once __DIR__ . '/lib/form_title_extractor.php';
    $rawName = trim($formNameInput);
    $source = trim($sourceFileName);
    $explicitNumber = strtoupper(trim($formNumberInput));

    $identity = \WebPdfTimeSaver\Mvp\FormTitleExtractor::parseFormIdentityFromTitle($rawName, $templateId, $source);
    $resolvedNumber = $explicitNumber !== ''
        ? $explicitNumber
        : trim((string)($identity['formNumber'] ?? ''));
    $resolvedName = trim((string)($identity['formName'] ?? ''));
    if ($resolvedName === '' && $rawName !== '') {
        $resolvedName = $rawName;
    }

    // Guaranteed fallback path for imports with missing/blank metadata titles.
    if ($resolvedName === '') {
        $fallback = \WebPdfTimeSaver\Mvp\FormTitleExtractor::parseFormIdentityFromTitle('', $templateId, $source);
        if ($resolvedNumber === '') {
            $resolvedNumber = trim((string)($fallback['formNumber'] ?? ''));
        }
        $resolvedName = trim((string)($fallback['formName'] ?? ''));
    }

    if ($resolvedNumber === '' && $resolvedName === '') {
        return [
            'formNumber' => '',
            'formName' => '',
            'combinedName' => $templateId,
        ];
    }

    if ($resolvedNumber === '') {
        return [
            'formNumber' => '',
            'formName' => $resolvedName,
            'combinedName' => $resolvedName,
        ];
    }

    $alreadyPrefixed = preg_match('/^' . preg_quote($resolvedNumber, '/') . '(?:\s*-\s*|\s+|$)/i', $resolvedName) === 1;
    $combinedName = ($resolvedName === '' || $alreadyPrefixed)
        ? ($resolvedName !== '' ? $resolvedName : $resolvedNumber)
        : ($resolvedNumber . ' - ' . $resolvedName);

    return [
        'formNumber' => $resolvedNumber,
        'formName' => $resolvedName,
        'combinedName' => $combinedName,
    ];
}

function handleFormTemplateEditorData(): void {
    global $store;
    header('Content-Type: application/json; charset=utf-8');
    $templateId = sanitizeId((string)($_GET['template_id'] ?? ''));
    if ($templateId === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'template_id required']);
        exit;
    }
    $dataDir = realpath(__DIR__ . '/../data');
    $uploadsDir = realpath(__DIR__ . '/../uploads');
    if ($dataDir === false || $uploadsDir === false || !is_dir($dataDir) || !is_dir($uploadsDir)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server paths not found']);
        exit;
    }

    $positionsFile = $dataDir . DIRECTORY_SEPARATOR . $templateId . '_positions.json';
    if (!is_file($positionsFile)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Positions file not found']);
        exit;
    }
    $positions = json_decode((string)file_get_contents($positionsFile), true);
    if (!is_array($positions)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid positions JSON']);
        exit;
    }

    $bgPattern = $uploadsDir . DIRECTORY_SEPARATOR . $templateId . '_page*_background.png';
    $bgFiles = glob($bgPattern) ?: [];
    sort($bgFiles, SORT_NATURAL);
    $backgroundPaths = [];
    foreach ($bgFiles as $abs) {
        $base = basename((string)$abs);
        $page = 1;
        if (preg_match('/_page(\d+)_background\.png$/i', $base, $m) === 1) {
            $p = (int)($m[1] ?? 1);
            if ($p > 0) {
                $page = $p;
            }
        }
        $published = ensureMvpPublicUploadAsset((string)$abs, $base);
        if ($published === null) {
            $published = '?route=actions/uploads-asset&file=' . rawurlencode($base);
        }
        $backgroundPaths[(string)$page] = $published;
    }

    $detectedFirmName = '';
    $storedFormName = '';
    $storedFormLocation = '';
    if ($store && method_exists($store, 'getGlobalFormTemplate')) {
        $row = $store->getGlobalFormTemplate($templateId);
        if (is_array($row)) {
            $detectedFirmName = trim((string)($row['detectedFirmName'] ?? ''));
            $storedFormName = trim((string)($row['formName'] ?? ''));
            $storedFormLocation = trim((string)($row['formLocation'] ?? ''));
        }
    }
    $identity = splitFormIdentityForEditor($templateId, $storedFormName);

    echo json_encode([
        'success' => true,
        'message' => 'Loaded saved template data.',
        'data' => [
            'template_id' => $templateId,
            'fields' => $positions,
            'background_paths' => $backgroundPaths,
            'detected_firm_name' => $detectedFirmName,
            'form_name' => (string)($identity['formName'] ?? ''),
            'form_number' => (string)($identity['formNumber'] ?? ''),
            'form_location' => $storedFormLocation,
        ],
    ], JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Stream a file from uploads directory by basename.
 */
function handleUploadsAssetDownload(): void {
    $file = trim((string)($_GET['file'] ?? ''));
    if ($file === '') {
        http_response_code(400);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'file required';
        exit;
    }
    $uploadsDir = realpath(__DIR__ . '/../uploads');
    if ($uploadsDir === false || !is_dir($uploadsDir)) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'uploads not found';
        exit;
    }
    $basename = basename(str_replace('\\', '/', $file));
    if ($basename === '' || $basename === '.' || $basename === '..') {
        http_response_code(400);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'invalid file';
        exit;
    }
    $resolved = realpath($uploadsDir . DIRECTORY_SEPARATOR . $basename);
    if ($resolved === false || !is_file($resolved)) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'file not found';
        exit;
    }
    $u = strtolower($uploadsDir);
    $r = strtolower($resolved);
    if (strncmp($r, $u, strlen($u)) !== 0) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'forbidden';
        exit;
    }

    $ext = strtolower((string)pathinfo($resolved, PATHINFO_EXTENSION));
    $mime = match ($ext) {
        'png' => 'image/png',
        'jpg', 'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
        'pdf' => 'application/pdf',
        default => 'application/octet-stream',
    };
    header('Content-Type: ' . $mime);
    header('Content-Disposition: inline; filename="' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $basename) . '"');
    readfile($resolved);
    exit;
}

function extractUniversalFieldOptions(array $position): array {
    $candidateKeys = ['options', 'optionValues', 'values', 'choices', 'items', 'exportValues'];
    $options = [];

    foreach ($candidateKeys as $key) {
        if (!array_key_exists($key, $position)) {
            continue;
        }
        $raw = $position[$key];

        if (!is_array($raw)) {
            if (is_string($raw) && trim($raw) !== '') {
                $options[] = trim($raw);
            }
            continue;
        }

        foreach ($raw as $itemKey => $itemValue) {
            if (is_string($itemValue)) {
                $candidate = trim($itemValue);
                if ($candidate !== '') {
                    $options[] = $candidate;
                }
                continue;
            }

            if (is_array($itemValue)) {
                $nested = trim((string)($itemValue['value'] ?? $itemValue['name'] ?? $itemValue['label'] ?? ''));
                if ($nested !== '') {
                    $options[] = $nested;
                }
                continue;
            }

            if (is_string($itemKey) && trim($itemKey) !== '') {
                $options[] = trim($itemKey);
            }
        }
    }

    return array_values(array_unique($options));
}

function buildUniversalDemoValueForField(string $fieldName, ?array $position, int $counter): string {
    $lower = strtolower($fieldName);
    $type = strtolower(trim((string)($position['type'] ?? $position['fieldType'] ?? '')));

    if (in_array($type, ['checkbox', 'check', 'radio', 'radiobutton', 'option', 'choice', 'select', 'dropdown'], true)) {
        $options = extractUniversalFieldOptions($position ?? []);
        foreach ($options as $option) {
            $optionLower = strtolower($option);
            if ($optionLower !== '' && $optionLower !== 'off' && $optionLower !== 'no' && $optionLower !== 'false' && $optionLower !== '0') {
                return $option;
            }
        }
        return '1';
    }

    if (strpos($lower, 'checkbox') !== false || strpos($lower, 'check') !== false || strpos($lower, 'box') !== false) {
        return '1';
    }
    return 'Sample';
}

function buildUniversalTestDataFromPositions(array $positions): array {
    $data = [];
    $counter = 1;

    foreach ($positions as $key => $position) {
        if (!is_array($position)) {
            continue;
        }

        $name = is_string($key) ? trim($key) : '';
        if ($name === '') {
            $name = trim((string)($position['name'] ?? $position['fieldName'] ?? ''));
        }
        if ($name === '') {
            $name = 'field_' . $counter;
        }

        $data[$name] = buildUniversalDemoValueForField($name, $position, $counter);
        $counter++;
    }

    return $data;
}

/**
 * Get the base path of the application dynamically
 * Determines the base path from the current request URI or script name
 * Returns path like '/Web-PDFTimeSaver/' or '/' depending on installation
 * Safe fallback to '/' if detection fails
 */
function getBasePath(): string {
    try {
        // Get script name (e.g., '/Web-PDFTimeSaver/mvp/index.php' or '/mvp/index.php')
        $scriptName = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
        
        // Fallback if SCRIPT_NAME is not available
        if (empty($scriptName)) {
            $scriptName = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '/mvp/index.php';
        }
        
        // Extract base path from script name
        $basePath = dirname(dirname($scriptName));
        
        // Normalize: convert '\' to '/' for Windows
        $basePath = str_replace('\\', '/', $basePath);
        
        // Remove trailing slashes
        $basePath = rtrim($basePath, '/');
        
        // If we're at root level (script is at /mvp/index.php), return '/'
        if (empty($basePath) || $basePath === '.' || $basePath === '/' || $basePath === '/mvp') {
            return '/';
        }
        
        // Ensure base path ends with a slash (PHP 7.x compatible check)
        if ($basePath !== '/' && strlen($basePath) > 0 && substr($basePath, -1) !== '/') {
            $basePath .= '/';
        }
        
        return $basePath;
    } catch (\Exception $e) {
        // Safe fallback - return root path if anything goes wrong
        error_log('getBasePath() error: ' . $e->getMessage());
        return '/';
    }
}

/**
 * When re-uploading a PDF, extraction returns fresh geometry but drops user-edited
 * values. Merge metadata from an existing positions file (same template id).
 *
 * @param array<string, array<string, mixed>> $extractedFields
 * @param array<string, array<string, mixed>> $savedByKey
 * @return array<string, array<string, mixed>>
 */
function mergeSavedAlignmentIntoExtractedFields(array $extractedFields, array $savedByKey): array {
    if ($savedByKey === []) {
        return $extractedFields;
    }
    $metaKeys = [
        'defaultValue', 'label', 'customFieldLinkId', 'customFieldLocation',
        'type', 'fieldType', 'fontFamily', 'fontSize', 'fontColor', 'fontStyle',
        'isBold', 'isItalic', 'isUnderline', 'isStrikethrough',
    ];
    foreach ($extractedFields as $key => &$field) {
        if (!is_array($field)) {
            continue;
        }
        $saved = null;
        $candidates = [
            (string)$key,
            (string)($field['name'] ?? ''),
            (string)($field['canonicalName'] ?? ''),
        ];
        foreach ($candidates as $c) {
            $candidateSet = [
                trim($c),
                canonicalizePdfFieldKey((string)$c),
            ];
            foreach ($candidateSet as $lookupKey) {
                if ($lookupKey !== '' && isset($savedByKey[$lookupKey]) && is_array($savedByKey[$lookupKey])) {
                    $saved = $savedByKey[$lookupKey];
                    break 2;
                }
            }
        }
        if ($saved === null) {
            continue;
        }
        foreach ($metaKeys as $prop) {
            if (!array_key_exists($prop, $saved)) {
                continue;
            }
            $field[$prop] = $saved[$prop];
        }
        foreach (['x', 'y', 'width', 'height', 'page'] as $geom) {
            if (!array_key_exists($geom, $saved)) {
                continue;
            }
            $g = $saved[$geom];
            if (is_numeric($g)) {
                $field[$geom] = 0 + $g;
            }
        }
    }
    unset($field);
    return $extractedFields;
}

/**
 * Normalize positions object keys to canonical underscore format.
 *
 * @param array<string|int, mixed> $positions
 * @return array<string, array<string, mixed>>
 */
function normalizePositionsFieldMap(array $positions): array {
    $out = [];
    $seen = [];

    $storeRow = static function (string $rawKey, array $row) use (&$out, &$seen): void {
        $baseKey = canonicalizePdfFieldKey($rawKey, $rawKey);
        if ($baseKey === '') {
            return;
        }
        $finalKey = $baseKey;
        $suffix = 2;
        while (isset($seen[$finalKey]) && $seen[$finalKey] === true) {
            $finalKey = $baseKey . '_' . $suffix;
            $suffix++;
        }
        $seen[$finalKey] = true;
        $row['canonicalName'] = $finalKey;
        if (!isset($row['name']) || trim((string)$row['name']) === '') {
            $row['name'] = $rawKey;
        }
        $out[$finalKey] = $row;
    };

    if (array_is_list($positions)) {
        foreach ($positions as $idx => $row) {
            if (!is_array($row)) {
                continue;
            }
            $rawName = trim((string)($row['canonicalName'] ?? $row['name'] ?? $row['fieldName'] ?? ('field_' . $idx)));
            if ($rawName === '') {
                $rawName = 'field_' . $idx;
            }
            $storeRow($rawName, $row);
        }
        return $out;
    }

    foreach ($positions as $key => $row) {
        if (!is_array($row)) {
            continue;
        }
        $rawName = '';
        if (is_string($key)) {
            $rawName = trim($key);
        }
        if ($rawName === '') {
            $rawName = trim((string)($row['canonicalName'] ?? $row['name'] ?? $row['fieldName'] ?? ''));
        }
        if ($rawName === '') {
            continue;
        }
        $storeRow($rawName, $row);
    }

    return $out;
}

function handleUniversalProcess(): void {
    global $store;
    header('Content-Type: application/json');
    
    $response = ['success' => false, 'message' => '', 'data' => []];
    
    try {
        $uploadedFile = null;
        $tmpPath = '';
        $selectedPath = '';
        $sourceOriginalName = '';
        if (isset($_FILES['pdf_file']) && (int)($_FILES['pdf_file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            $uploadedFile = $_FILES['pdf_file'];
            $tmpPath = (string)($uploadedFile['tmp_name'] ?? '');
            $sourceOriginalName = (string)($uploadedFile['name'] ?? '');
        } else {
            $selectedRaw = trim((string)($_POST['selected_pdf_path'] ?? ''));
            if ($selectedRaw === '') {
                throw new Exception('Please select a PDF file to upload');
            }
            $uploadsDir = realpath(__DIR__ . '/../uploads');
            if ($uploadsDir === false) {
                throw new Exception('Uploads directory not available');
            }
            $resolvedSelected = resolveUploadsPdfSelection($selectedRaw, $uploadsDir);
            if ($resolvedSelected === null) {
                throw new Exception('Invalid selected PDF file');
            }
            $selectedPath = $resolvedSelected;
            $tmpPath = $selectedPath;
            $sourceOriginalName = basename($selectedPath);
            $uploadedFile = [
                'name' => $sourceOriginalName,
                'tmp_name' => $selectedPath,
            ];
        }
        if ($tmpPath === '' || !is_file($tmpPath)) {
            throw new Exception('Selected PDF source is unavailable');
        }

        require_once __DIR__ . '/lib/pdf_field_extractor.php';

        // Determine template ID
        $rawTemplateId = trim((string)($_POST['template_id'] ?? ''));
        if ($rawTemplateId !== '') {
            // Use user-specified template ID
            $templateId = sanitizeId($rawTemplateId);
        } else {
            // Auto-generate a stable template ID based on PDF content
            // This ensures the same form gets the same ID when re-uploaded
            $baseName = pathinfo($sourceOriginalName, PATHINFO_FILENAME);
            $cleanBase = preg_replace('/[^a-z0-9_-]+/i', '_', strtolower($baseName)) ?: 'template';

            // Hash PDF contents for stability; use short prefix to keep IDs readable
            $fileHash = @sha1_file($tmpPath) ?: bin2hex(random_bytes(8));
            $hashPrefix = substr($fileHash, 0, 10);

            $templateId = sanitizeId($cleanBase . '_' . $hashPrefix);
        }

        $permanentPath = __DIR__ . '/../uploads/' . $templateId . '.pdf';
        
        // Save selected/uploaded PDF into canonical template path
        if ($selectedPath !== '') {
            $sourceReal = realpath($selectedPath);
            $targetReal = realpath($permanentPath);
            if ($sourceReal === false || ($targetReal !== false && strcasecmp($sourceReal, $targetReal) === 0)) {
                // No copy needed (already canonical path), continue.
            } elseif (!copy($selectedPath, $permanentPath)) {
                throw new Exception('Failed to save selected PDF');
            }
        } else {
            if (!move_uploaded_file($tmpPath, $permanentPath)) {
                throw new Exception('Failed to save PDF');
            }
        }
        
        $response['data']['pdf_saved'] = $permanentPath;
        $response['data']['template_id'] = $templateId;
        require_once __DIR__ . '/lib/form_title_extractor.php';
        $detectedFormTitleInfo = \WebPdfTimeSaver\Mvp\FormTitleExtractor::extractFromPdfMetadata(
            $permanentPath,
            $templateId,
            $sourceOriginalName
        );
        $detectedFormTitle = trim((string)($detectedFormTitleInfo['title'] ?? ''));
        $detectedFormTitleConfidence = (float)($detectedFormTitleInfo['confidence'] ?? 0.0);
        $detectedFormTitleSource = trim((string)($detectedFormTitleInfo['source'] ?? ''));
        $parsedIdentity = \WebPdfTimeSaver\Mvp\FormTitleExtractor::parseFormIdentityFromTitle(
            $detectedFormTitle,
            $templateId,
            $sourceOriginalName
        );
        $parsedFormNumber = trim((string)($parsedIdentity['formNumber'] ?? ''));
        $parsedFormName = trim((string)($parsedIdentity['formName'] ?? ''));
        if ($detectedFormTitle !== '') {
            $response['data']['detected_form_title'] = $detectedFormTitle;
            $response['data']['detected_form_title_source'] = $detectedFormTitleSource;
            $response['data']['detected_form_title_confidence'] = $detectedFormTitleConfidence;
        }
        if ($parsedFormNumber !== '') {
            $response['data']['form_number'] = $parsedFormNumber;
        }
        if ($parsedFormName !== '' && $detectedFormTitleConfidence >= 0.70) {
            $response['data']['form_name'] = $parsedFormName;
        } elseif ($parsedFormName !== '' && $detectedFormTitle !== '') {
            $response['data']['form_name'] = $parsedFormName;
        }
        
        // STEP 1: Try to detect if PDF has fillable fields
        $parser = new \Smalot\PdfParser\Parser();
        $hasFields = false;
        $fieldCount = 0;
        
        try {
            $pdf = $parser->parseFile($permanentPath);
            $pages = $pdf->getPages();
            
            foreach ($pages as $page) {
                $annotations = $page->get('Annots');
                if ($annotations) {
                    $annotArray = $annotations->getContent();
                    if (is_array($annotArray)) {
                        foreach ($annotArray as $annot) {
                            if (is_object($annot) && $annot->get('T')) {
                                $fieldCount++;
                                $hasFields = true;
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            $response['data']['parser_error'] = $e->getMessage();
        }
        
        $response['data']['has_fillable_fields'] = $hasFields;
        $response['data']['field_count'] = $fieldCount;
        
        // STEP 2: Extract field positions
        // IMPORTANT: Always try ensemble extraction, even if PdfParser found no fillable fields
        // The ensemble can detect fields using visual methods even in static PDFs
        error_log("=== handleUniversalProcess: EXTRACTION START ===");
        error_log("Template ID: $templateId");
        error_log("PDF path: $permanentPath");
        error_log("PDF exists: " . (file_exists($permanentPath) ? 'YES' : 'NO'));
        error_log("Uploads dir: " . __DIR__ . '/../uploads');
        
        $extractor = new \WebPdfTimeSaver\Mvp\PdfFieldExtractor();
        error_log("Calling extractAndGenerateBackgrounds()...");
        $extractResult = $extractor->extractAndGenerateBackgrounds(
            $permanentPath,
            $templateId,
            __DIR__ . '/../uploads'
        );
        
        error_log("=== handleUniversalProcess: extractAndGenerateBackgrounds() returned ===");
        error_log("Extract result keys: " . implode(', ', array_keys($extractResult)));
        
        $fields = $extractResult['fields'] ?? [];
        $positionsFile = __DIR__ . '/../data/' . $templateId . '_positions.json';
        if (!empty($fields) && is_readable($positionsFile)) {
            $savedRaw = json_decode((string)file_get_contents($positionsFile), true);
            if (is_array($savedRaw) && $savedRaw !== []) {
                $savedRaw = normalizePositionsFieldMap($savedRaw);
                $fields = mergeSavedAlignmentIntoExtractedFields($fields, $savedRaw);
            }
        }
        $backgrounds = $extractResult['backgrounds'] ?? [];
        $ensembleMetadata = $extractResult['ensembleMetadata'] ?? null;
        
        error_log("Fields count: " . count($fields));
        error_log("Fields type: " . gettype($fields));
        error_log("Backgrounds count: " . count($backgrounds));
        error_log("Ensemble metadata: " . ($ensembleMetadata ? 'PRESENT' : 'NULL'));
        
        if (!empty($fields)) {
            error_log("Field keys: " . implode(', ', array_keys($fields)));
        } else {
            error_log("WARNING: Fields array is empty!");
            error_log("Extract result fields: " . (isset($extractResult['fields']) ? count($extractResult['fields']) : 'NOT SET'));
        }
        
        if ($ensembleMetadata) {
            error_log("Ensemble method: " . ($ensembleMetadata['method'] ?? 'unknown'));
            error_log("Methods used: " . count($ensembleMetadata['methodsUsed'] ?? []));
        }
        
        // STEP 3: Analyze results
        // Check if ensemble found fields even if PdfParser didn't detect fillable fields
        if (!empty($fields)) {
            // Check if fields are static (estimated) or fillable
            $hasEstimatedFields = false;
            foreach ($fields as $field) {
                if (!empty($field['estimated'])) {
                    $hasEstimatedFields = true;
                    break;
                }
            }
            
            // SUCCESS: Auto-detected fields with positions
            $response['success'] = true;
            if ($hasEstimatedFields) {
                $response['message'] = "Detected " . count($fields) . " static fields using text label recognition!";
                $response['data']['method'] = 'static_detection';
            } else {
                // Check if ensemble was used
                $isEnsemble = $ensembleMetadata && 
                             (strpos($ensembleMetadata['method'] ?? '', 'ensemble') !== false || 
                              !empty($ensembleMetadata['methodsUsed']));
                
                if ($isEnsemble) {
                    $methodsCount = count($ensembleMetadata['methodsUsed'] ?? []);
                    $response['message'] = "Auto-detected " . count($fields) . " fillable fields using ensemble (" . $methodsCount . " methods)!";
                } else {
                    $response['message'] = "Auto-detected " . count($fields) . " fillable fields!";
                }
                $response['data']['method'] = 'autofill';
            }
            $response['data']['fields'] = $fields;
            $response['data']['field_count'] = count($fields);
            $response['data']['backgrounds'] = count($backgrounds);
            $response['data']['background_paths'] = $backgrounds; // Include actual paths for preview
            $response['data']['position_file'] = $extractResult['positionFile'];
            
            // Include ensemble metadata if available
            if ($ensembleMetadata) {
                $response['data']['ensemble'] = [
                    'method' => $ensembleMetadata['method'] ?? 'unknown',
                    'methodsUsed' => $ensembleMetadata['methodsUsed'] ?? [],
                    'fieldsPerMethod' => $ensembleMetadata['fieldsPerMethod'] ?? [],
                    'pageCount' => $ensembleMetadata['pageCount'] ?? 0
                ];
            }
            
            // Analyze field types
            $typeCount = [];
            foreach ($fields as $field) {
                $type = $field['type'] ?? 'unknown';
                $typeCount[$type] = ($typeCount[$type] ?? 0) + 1;
            }
            $response['data']['field_types'] = $typeCount;
            
            // Add note about estimated fields
            if ($hasEstimatedFields) {
                $response['data']['note'] = 'These field positions are estimated from text labels. You may need to fine-tune positions using the Visual Field Editor.';
            }
            
        } elseif (count($backgrounds) > 0) {
            // PARTIAL: No fields but backgrounds generated
            $response['success'] = true;
            $response['message'] = "PDF is encrypted/blank. Generated " . count($backgrounds) . " background images for manual positioning.";
            $response['data']['method'] = 'manual_overlay';
            $response['data']['backgrounds'] = count($backgrounds);
            $response['data']['background_paths'] = $backgrounds; // Include actual paths for preview
            
        } else {
            throw new Exception('Could not extract fields or generate backgrounds from PDF');
        }

        $detectedFirmName = '';
        if (!empty($response['success']) && isset($permanentPath) && is_readable($permanentPath)) {
            require_once __DIR__ . '/lib/fl100_firm_name_extractor.php';
            $detectedFirmName = \WebPdfTimeSaver\Mvp\Fl100FirmNameExtractor::extractFromKeyedFields(
                is_array($fields) ? $fields : [],
                $permanentPath
            );
        }
        if (!empty($response['success']) && $detectedFirmName !== '') {
            $response['data']['detected_firm_name'] = $detectedFirmName;
        }

        if (!empty($response['success']) && !empty($response['data']['template_id']) && $store && method_exists($store, 'registerClientTemplate')) {
            try {
                $cid = sanitizeId((string)($_POST['client_id'] ?? ''));
                if ($cid !== '') {
                    $label = sanitizeString((string)($_POST['template_label'] ?? ''), 200);
                    $store->registerClientTemplate($cid, (string)$response['data']['template_id'], $label);
                }
            } catch (\Throwable $e) {
                error_log('handleUniversalProcess registerClientTemplate warning: ' . $e->getMessage());
                $response['data']['registry_warning'] = 'Template was uploaded, but optional client-template registration failed.';
            }
        }

        if (!empty($response['success']) && !empty($response['data']['template_id']) && $store && method_exists($store, 'getGlobalFormTemplate')) {
            try {
                $templateIdForRegistry = (string)$response['data']['template_id'];
                $reg = $store->getGlobalFormTemplate($templateIdForRegistry);
                $response['data']['registry_exists'] = $reg !== null;
                $response['data']['registry_pending_finish'] = $reg === null;
                $response['data']['source_file_name'] = (string)($sourceOriginalName !== '' ? $sourceOriginalName : ($uploadedFile['name'] ?? ''));
                if (method_exists($store, 'isMysqlPhaseOneConnected')) {
                    $response['data']['phase1_mysql_connected'] = $store->isMysqlPhaseOneConnected();
                }
                if ($reg !== null) {
                    $response['data']['registered_form_name'] = (string)($reg['formName'] ?? '');
                    $response['data']['form_location'] = trim((string)($reg['formLocation'] ?? ''));
                    if ($detectedFirmName === '' && trim((string)($reg['detectedFirmName'] ?? '')) !== '') {
                        $response['data']['detected_firm_name'] = trim((string)$reg['detectedFirmName']);
                    }
                }
            } catch (\Throwable $e) {
                error_log('handleUniversalProcess getGlobalFormTemplate warning: ' . $e->getMessage());
                $response['data']['registry_warning'] = 'Template uploaded, but pending registry status could not be determined.';
            }
        }
        
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    exit;
}

/**
 * Lightweight diagnostics endpoint so the Universal Processor UI
 * can report missing server requirements (Node.js, qpdf, permissions, etc.).
 */
function handleUniversalDiagnostics(): void {
    global $store;
    header('Content-Type: application/json');
    
    try {
        $status = [];
        $requirements = [];
        
        $status['server_time'] = date(DATE_ATOM);
        $status['php'] = [
            'version' => PHP_VERSION,
            'version_ok' => version_compare(PHP_VERSION, '8.0', '>='),
            'sapi' => PHP_SAPI
        ];
        if (!$status['php']['version_ok']) {
            $requirements[] = 'PHP 8.0 or higher is required.';
        }
        
        $composerPath = __DIR__ . '/../vendor/autoload.php';
        $status['composer'] = [
            'autoload_present' => file_exists($composerPath),
            'autoload_path' => $composerPath
        ];
        if (!$status['composer']['autoload_present']) {
            $requirements[] = 'Composer dependencies are missing (vendor/autoload.php not found).';
        }
        
        $directories = [
            'uploads' => realpath(__DIR__ . '/../uploads') ?: __DIR__ . '/../uploads',
            'data' => realpath(__DIR__ . '/../data') ?: __DIR__ . '/../data',
            'output' => realpath(__DIR__ . '/../output') ?: __DIR__ . '/../output',
            'temp' => sys_get_temp_dir()
        ];
        $status['paths'] = [];
        foreach ($directories as $label => $path) {
            $writable = is_dir($path) ? is_writable($path) : false;
            $status['paths'][$label] = [
                'path' => $path,
                'exists' => is_dir($path),
                'writable' => $writable
            ];
            if (!$status['paths'][$label]['exists'] || !$writable) {
                $requirements[] = strtoupper($label) . ' directory must exist and be writable.';
            }
        }
        
        $requiredExtensions = ['json', 'mbstring', 'openssl'];
        $status['extensions'] = [];
        foreach ($requiredExtensions as $ext) {
            $loaded = extension_loaded($ext);
            $status['extensions'][] = ['name' => $ext, 'loaded' => $loaded];
            if (!$loaded) {
                $requirements[] = "PHP extension '{$ext}' must be enabled.";
            }
        }
        
        $disabledFunctions = array_map('trim', explode(',', (string) ini_get('disable_functions')));
        $functionChecks = ['exec', 'shell_exec', 'proc_open'];
        $status['functions'] = [];
        foreach ($functionChecks as $fn) {
            $enabled = function_exists($fn) && !in_array($fn, $disabledFunctions, true);
            $status['functions'][] = ['name' => $fn, 'enabled' => $enabled];
            if ($fn === 'exec' && !$enabled) {
                $requirements[] = 'PHP function exec() must be enabled to run Node/qpdf commands.';
            }
        }
        
        $status['node'] = [
            'available' => false,
            'path' => null,
            'version' => null,
            'script_available' => false
        ];
        $status['qpdf'] = [
            'available' => false
        ];
        
        try {
            require_once __DIR__ . '/lib/auto_position_extractor.php';
            $auto = new \WebPdfTimeSaver\Mvp\AutoPositionExtractor();
            $autoStatus = $auto->getStatus();
            
            $status['node']['available'] = !empty($autoStatus['nodejs_available']);
            $status['node']['path'] = $autoStatus['nodejs_path'] ?? null;
            $status['node']['script_available'] = !empty($autoStatus['script_available']);
            if ($status['node']['available'] && $status['node']['path'] && function_exists('exec') && !in_array('exec', $disabledFunctions, true)) {
                $nodeVersionOutput = [];
                @exec(escapeshellarg($status['node']['path']) . ' --version 2>&1', $nodeVersionOutput);
                if (!empty($nodeVersionOutput[0])) {
                    $status['node']['version'] = trim($nodeVersionOutput[0]);
                }
            }
            if (!$status['node']['available']) {
                $requirements[] = 'Node.js is required for ensemble extraction.';
            }
            if (!$status['node']['script_available']) {
                $requirements[] = 'universal-field-extractor.js script could not be found.';
            }
            
            $status['qpdf']['available'] = !empty($autoStatus['qpdf_available']);
            if (!$status['qpdf']['available']) {
                $requirements[] = 'qpdf binary not found (needed to decrypt PDFs).';
            }
        } catch (\Throwable $e) {
            $status['auto_extractor_error'] = $e->getMessage();
            $requirements[] = 'AutoPositionExtractor failed to initialize: ' . $e->getMessage();
        }

        $status['pdo_mysql'] = [
            'extension_loaded' => extension_loaded('pdo_mysql'),
        ];
        if (!$status['pdo_mysql']['extension_loaded']) {
            $requirements[] = 'PHP extension pdo_mysql should be enabled for Phase 1 MySQL storage.';
        }
        if ($store && method_exists($store, 'isMysqlPhaseOneConnected')) {
            // Phase 1 checklist: forms/firm/custom fields + workspace entities share PDO; see DESIGN SPECS/phase1-checklist.txt
            $status['phase1_mysql'] = [
                'connected' => $store->isMysqlPhaseOneConnected(),
                'firm_id' => resolveCurrentFirmId(),
                'mvp_entities' => $store->isMysqlPhaseOneConnected() ? 'mysql' : 'json',
            ];
            if (method_exists($store, 'getMvpEntityCountsFromDb')) {
                $counts = $store->getMvpEntityCountsFromDb();
                if ($counts !== null) {
                    $status['phase1_mysql']['entity_counts'] = $counts;
                }
            }
        }

        $status = mvpRedactUniversalDiagnosticsStatus($status);
        
        echo json_encode([
            'success' => true,
            'status' => $status,
            'requirements' => array_values(array_unique($requirements))
        ], JSON_PRETTY_PRINT);
        
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ], JSON_PRETTY_PRINT);
    }
    
    exit;
}

$route = validateRoute($_GET['route'] ?? 'dashboard');

// Handle API routes
if (strpos($route, 'api/') === 0) {
    $apiRoute = substr($route, 4); // Remove 'api/' prefix
    
    switch ($apiRoute) {
        case 'firm-defaults/fields':
            handleFirmDefaultsFields();
            break;
        case 'firm-defaults/update-value':
            handleFirmDefaultsUpdateValue();
            break;
        case 'firm-defaults/matching-mode':
            handleFirmDefaultsMatchingMode();
            break;
        case 'attorneys/list':
            handleAttorneysList();
            break;
        case 'attorneys/upsert':
            handleAttorneysUpsert();
            break;
        case 'attorneys/delete':
            handleAttorneysDelete();
            break;
        case 'field-manager/fields':
            handleFieldManagerFields();
            break;
        case 'field-manager/upsert-field':
            handleFieldManagerUpsertField();
            break;
        case 'field-manager/delete-field':
            handleFieldManagerDeleteField();
            break;
        case 'field-manager/diag':
            handleFieldManagerDiag();
            break;
        case 'client/update-profile-autosave':
            handleClientProfileAutosave();
            break;
        case 'form-management/upsert-custom-field':
            handleFormManagementUpsertCustomField();
            break;
        case 'form-management/delete-template':
            handleFormManagementDeleteTemplate();
            break;
        case 'form-management/finalize-template':
            handleFormManagementFinalizeTemplate();
            break;
        case 'form-management/cleanup-database':
            handleFormManagementCleanupDatabase();
            break;
        case 'form-sets/list':
            handleFormSetsList();
            break;
        case 'form-sets/upsert':
            handleFormSetsUpsert();
            break;
        case 'form-sets/delete':
            handleFormSetsDelete();
            break;
        case 'form-importer/aliases':
            handleFormImporterAliases();
            break;
        case 'form-importer/upsert-alias':
            handleFormImporterUpsertAlias();
            break;
        case 'form-importer/delete-alias':
            handleFormImporterDeleteAlias();
            break;
        case 'form-importer/match-explain':
            handleFormImporterMatchExplain();
            break;
        case 'form-importer/suggest-alias':
            handleFormImporterSuggestAlias();
            break;
        case 'form-importer/numbering-hints':
            handleFormImporterNumberingHints();
            break;
        case 'form-template/meta':
            handleFormTemplateMeta();
            break;
        case 'courts/search':
            handleCourtsSearch();
            break;
        case 'courts/reimport-la':
            handleCourtsReimportLa();
            break;
        case 'courts/reimport-federal':
            handleCourtsReimportFederal();
            break;
        case 'courts/reimport-ca-statewide':
            handleCourtsReimportCaStatewide();
            break;
        case 'courts/reimport-all':
            handleCourtsReimportAll();
            break;
        case 'positions/update':
            handlePositionUpdate();
            break;
        default:
            http_response_code(404);
            echo json_encode(['error' => 'API endpoint not found']);
            exit;
    }
    exit;
}

function render(string $view, array $vars = []): void {
	global $store, $templates, $fill, $pdfFieldService, $logger, $fileManager, $customFieldManager;
	$vars['store'] = $store;
	if (!array_key_exists('templates', $vars)) {
		$vars['templates'] = $templates;
	}
	$vars['fill'] = $fill;
	$vars['pdfFieldService'] = $pdfFieldService;
	$vars['logger'] = $logger;
	$vars['fileManager'] = $fileManager;
	$vars['customFieldManager'] = $customFieldManager;
	extract($vars);
	include __DIR__ . '/views/layout_header.php';
	include __DIR__ . "/views/{$view}.php";
	include __DIR__ . '/views/layout_footer.php';
}

function handleFormTemplateMeta(): void {
    global $store;
    header('Content-Type: application/json; charset=utf-8');
    $tid = sanitizeId((string)($_GET['template_id'] ?? ''));
    if ($tid === '' || !$store || !method_exists($store, 'getGlobalFormTemplate')) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'template_id required or store unavailable'], JSON_UNESCAPED_SLASHES);
        return;
    }
    $row = $store->getGlobalFormTemplate($tid);
    echo json_encode([
        'success' => true,
        'template' => $row,
    ], JSON_UNESCAPED_SLASHES);
}

function handleFormManagementFinalizeTemplate(): void {
    global $store;
    header('Content-Type: application/json; charset=utf-8');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed'], JSON_UNESCAPED_SLASHES);
        return;
    }
    if (!$store || !method_exists($store, 'upsertGlobalFormTemplate')) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Store unavailable'], JSON_UNESCAPED_SLASHES);
        return;
    }

    $raw = file_get_contents('php://input');
    $decoded = json_decode((string)$raw, true);
    if (!is_array($decoded)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid JSON body'], JSON_UNESCAPED_SLASHES);
        return;
    }

    $templateId = sanitizeId((string)($decoded['template_id'] ?? ''));
    if ($templateId === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'template_id required'], JSON_UNESCAPED_SLASHES);
        return;
    }

    $formNumber = strtoupper(trim((string)($decoded['form_number'] ?? '')));
    $formName = trim((string)($decoded['form_name'] ?? ''));
    $formLocation = trim((string)($decoded['form_location'] ?? ''));
    $sourceFileNameInput = trim((string)($decoded['source_file_name'] ?? ''));
    $detectedFirmNameInput = trim((string)($decoded['detected_firm_name'] ?? ''));

    $existing = method_exists($store, 'getGlobalFormTemplate')
        ? $store->getGlobalFormTemplate($templateId)
        : null;

    $sourceFileName = $sourceFileNameInput !== ''
        ? $sourceFileNameInput
        : (is_array($existing) ? trim((string)($existing['sourceFileName'] ?? '')) : '');
    $detectedFirmName = $detectedFirmNameInput !== ''
        ? $detectedFirmNameInput
        : (is_array($existing) ? trim((string)($existing['detectedFirmName'] ?? '')) : '');
    $resolvedLocation = $formLocation !== ''
        ? $formLocation
        : (is_array($existing) ? trim((string)($existing['formLocation'] ?? '')) : '');

    $seedName = $formName !== ''
        ? $formName
        : (is_array($existing) ? trim((string)($existing['formName'] ?? '')) : '');
    $resolvedIdentity = resolveFormIdentityForStorage($templateId, $seedName, $sourceFileName, $formNumber);
    $resolvedName = $resolvedIdentity['combinedName'];

    try {
        $store->upsertGlobalFormTemplate($templateId, $resolvedName, $sourceFileName, $detectedFirmName, $resolvedLocation);
        $saved = method_exists($store, 'getGlobalFormTemplate')
            ? $store->getGlobalFormTemplate($templateId)
            : null;
        echo json_encode([
            'success' => true,
            'template_id' => $templateId,
            'form_number' => (string)($resolvedIdentity['formNumber'] ?? ''),
            'form_name' => (string)($resolvedIdentity['formName'] ?? ''),
            'form_location' => $resolvedLocation,
            'template' => $saved,
        ], JSON_UNESCAPED_SLASHES);
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to finalize template: ' . $e->getMessage(),
        ], JSON_UNESCAPED_SLASHES);
    }
}

function handleFormManagementUpsertCustomField(): void {
    global $store;
    header('Content-Type: application/json');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        return;
    }
    $raw = file_get_contents('php://input');
    $decoded = json_decode((string)$raw, true);
    if (!is_array($decoded)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid JSON body']);
        return;
    }
    $linkId = preg_replace('/[^a-z0-9_]/', '_', strtolower(trim((string)($decoded['link_id'] ?? ''))));
    $displayName = sanitizeString((string)($decoded['display_name'] ?? ''), 255);
    $location = strtolower(trim((string)($decoded['location'] ?? 'firm')));
    if (!in_array($location, ['firm', 'client', 'court', 'case'], true)) {
        $location = 'firm';
    }
    $value = (string)($decoded['value'] ?? '');
    if ($linkId === '' || $displayName === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'link_id and display_name are required']);
        return;
    }
    if (!$store || !method_exists($store, 'upsertFormCustomFieldRow')) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Store not available']);
        return;
    }
    // Same matching_tag rules as upsertFormCustomFieldRow when tag is omitted: avoid clashes in this location.
    $effectiveMatching = $linkId;
    if (method_exists($store, 'getFormCustomFields')) {
        foreach ($store->getFormCustomFields() as $r) {
            if (strtolower(trim((string)($r['linkId'] ?? ''))) !== $linkId) {
                continue;
            }
            $mt = trim((string)($r['matchingTag'] ?? ''));
            if ($mt !== '') {
                $effectiveMatching = $mt;
            }
            break;
        }
    }
    $dup = fieldManagerFindDuplicateMatchingTag($store, $location, $effectiveMatching, '', $linkId);
    if ($dup !== null) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'error' => 'Another field already uses this matching tag for this section. Change link id or resolve the duplicate in Field Manager.',
        ]);
        return;
    }
    try {
        $store->upsertFormCustomFieldRow($linkId, $displayName, $location, $value);
        echo json_encode([
            'success' => true,
            'catalog' => method_exists($store, 'getFormCustomFields') ? $store->getFormCustomFields() : [],
        ], JSON_UNESCAPED_SLASHES);
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function handleFormManagementDeleteTemplate(): void {
    global $store;
    header('Content-Type: application/json; charset=utf-8');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        return;
    }
    if (!$store || !method_exists($store, 'deleteGlobalFormTemplate')) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Store not available']);
        return;
    }
    $decoded = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($decoded)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid JSON body']);
        return;
    }
    $templateId = sanitizeId((string)($decoded['template_id'] ?? ''));
    if ($templateId === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'template_id required']);
        return;
    }
    try {
        $templateMeta = method_exists($store, 'getGlobalFormTemplate')
            ? (array)($store->getGlobalFormTemplate($templateId) ?? [])
            : [];
        $sourceFileName = basename((string)($templateMeta['sourceFileName'] ?? ''));

        $deleted = (bool)$store->deleteGlobalFormTemplate($templateId);
        $normalizeTemplateToken = static function (string $raw): string {
            return strtolower((string)preg_replace('/[^a-z0-9]+/i', '', trim($raw)));
        };
        $deleteTemplateArtifacts = static function (string $dir, string $tid, bool $allowNormalized) use ($normalizeTemplateToken): void {
            $baseDir = realpath($dir);
            if ($baseDir === false || !is_dir($baseDir)) {
                return;
            }
            $safeTid = basename($tid);
            $normTid = $normalizeTemplateToken($safeTid);
            $names = [
                $safeTid . '_positions.json',
                $safeTid . '_config.json',
                $safeTid . '_extraction_details.json',
                $safeTid . '_verification_report.json',
            ];
            foreach ($names as $name) {
                $candidate = $baseDir . DIRECTORY_SEPARATOR . $name;
                if (is_file($candidate)) {
                    @unlink($candidate);
                }
            }
            if (!$allowNormalized || $normTid === '') {
                return;
            }
            foreach ((array)(glob($baseDir . DIRECTORY_SEPARATOR . '*.json') ?: []) as $path) {
                $name = basename((string)$path);
                if (!preg_match('/^(.+?)_(positions|config|extraction_details|verification_report)\.json$/', $name, $m)) {
                    continue;
                }
                $token = $normalizeTemplateToken((string)($m[1] ?? ''));
                if ($token !== '' && $token === $normTid && is_file($path)) {
                    @unlink($path);
                }
            }
        };
        $deleteBackgrounds = static function (string $uploadsDir, string $tid, bool $allowNormalized) use ($normalizeTemplateToken): void {
            $baseDir = realpath($uploadsDir);
            if ($baseDir === false || !is_dir($baseDir)) {
                return;
            }
            $normTid = $normalizeTemplateToken($tid);
            foreach ((array)(glob($baseDir . DIRECTORY_SEPARATOR . '*_page*_background.png') ?: []) as $path) {
                $name = basename((string)$path);
                if (preg_match('/^(.*)_page\d+_background\.png$/i', $name, $m) !== 1) {
                    continue;
                }
                $prefix = (string)($m[1] ?? '');
                $isExact = strcasecmp($prefix, $tid) === 0;
                $isNorm = $allowNormalized && $normTid !== '' && $normalizeTemplateToken($prefix) === $normTid;
                if (($isExact || $isNorm) && is_file($path)) {
                    @unlink($path);
                }
            }
        };
        $deleteTemplatePdfFiles = static function (string $uploadsDir, string $tid, bool $allowNormalized) use ($normalizeTemplateToken): void {
            $baseDir = realpath($uploadsDir);
            if ($baseDir === false || !is_dir($baseDir)) {
                return;
            }
            $safeTid = basename($tid);
            $normTid = $normalizeTemplateToken($safeTid);
            $exact = $baseDir . DIRECTORY_SEPARATOR . $safeTid . '.pdf';
            if (is_file($exact)) {
                @unlink($exact);
            }
            if (!$allowNormalized || $normTid === '') {
                return;
            }
            foreach ((array)(glob($baseDir . DIRECTORY_SEPARATOR . '*.pdf') ?: []) as $path) {
                $name = basename((string)$path);
                if (preg_match('/^(.+)\.pdf$/i', $name, $m) !== 1) {
                    continue;
                }
                $prefix = (string)($m[1] ?? '');
                if ($normalizeTemplateToken($prefix) === $normTid && is_file($path)) {
                    @unlink($path);
                }
            }
        };

        $remainingTemplates = method_exists($store, 'getGlobalFormTemplates')
            ? (array)$store->getGlobalFormTemplates()
            : [];
        $normTid = $normalizeTemplateToken($templateId);
        $hasSiblingNormalizedTemplate = false;
        $hasSiblingSourceFile = false;
        foreach ($remainingTemplates as $row) {
            if (!is_array($row)) { continue; }
            $rid = sanitizeId((string)($row['templateId'] ?? ''));
            if ($rid === '' || $rid === $templateId) { continue; }
            if ($normTid !== '' && $normalizeTemplateToken($rid) === $normTid) {
                $hasSiblingNormalizedTemplate = true;
            }
            $rowSource = basename((string)($row['sourceFileName'] ?? ''));
            if ($sourceFileName !== '' && $rowSource !== '' && strcasecmp($rowSource, $sourceFileName) === 0) {
                $hasSiblingSourceFile = true;
            }
        }

        $deleteTemplateArtifacts(__DIR__ . '/../data', $templateId, !$hasSiblingNormalizedTemplate);
        $deleteTemplateArtifacts(__DIR__ . '/data', $templateId, !$hasSiblingNormalizedTemplate);
        $deleteBackgrounds(__DIR__ . '/uploads', $templateId, !$hasSiblingNormalizedTemplate);
        $deleteBackgrounds(__DIR__ . '/../uploads', $templateId, !$hasSiblingNormalizedTemplate);
        $deleteTemplatePdfFiles(__DIR__ . '/uploads', $templateId, !$hasSiblingNormalizedTemplate);
        $deleteTemplatePdfFiles(__DIR__ . '/../uploads', $templateId, !$hasSiblingNormalizedTemplate);

        if ($sourceFileName !== '' && !$hasSiblingSourceFile) {
            $sourceCandidates = [
                __DIR__ . '/../uploads/' . $sourceFileName,
                __DIR__ . '/uploads/' . $sourceFileName,
            ];
            foreach ($sourceCandidates as $candidate) {
                if (is_file($candidate)) {
                    @unlink($candidate);
                }
            }
        }
        echo json_encode([
            'success' => true,
            'deleted' => $deleted,
            'template_id' => $templateId,
        ], JSON_UNESCAPED_SLASHES);
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function handleFormManagementCleanupDatabase(): void {
    global $store;
    header('Content-Type: application/json; charset=utf-8');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        return;
    }
    if (!$store || !method_exists($store, 'cleanupGlobalFormDatabase')) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Store not available']);
        return;
    }
    try {
        $summary = (array)$store->cleanupGlobalFormDatabase();
        echo json_encode([
            'success' => true,
            'summary' => $summary,
        ], JSON_UNESCAPED_SLASHES);
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
        ]);
    }
}

function handleFormImporterNumberingHints(): void {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $templateId = sanitizeId((string)($_GET['template_id'] ?? ''));
        $dataDir = realpath(__DIR__ . '/../data');
        if ($dataDir === false || !is_dir($dataDir)) {
            throw new \RuntimeException('Data directory not found');
        }
        $positionsFile = '';
        if ($templateId !== '') {
            $candidate = $dataDir . DIRECTORY_SEPARATOR . $templateId . '_positions.json';
            if (is_file($candidate)) {
                $positionsFile = $candidate;
            }
        }
        if ($positionsFile === '') {
            $candidates = glob($dataDir . DIRECTORY_SEPARATOR . '*_positions.json') ?: [];
            if ($candidates !== []) {
                usort($candidates, static function (string $a, string $b): int {
                    return filemtime($b) <=> filemtime($a);
                });
                $positionsFile = (string)$candidates[0];
                $base = basename($positionsFile);
                if (preg_match('/^(.+)_positions\.json$/', $base, $m) === 1) {
                    $templateId = sanitizeId((string)($m[1] ?? ''));
                }
            }
        }
        if ($positionsFile === '' || !is_file($positionsFile)) {
            echo json_encode(['success' => true, 'templateId' => $templateId, 'hints' => []], JSON_UNESCAPED_SLASHES);
            return;
        }
        $decoded = json_decode((string)file_get_contents($positionsFile), true);
        if (!is_array($decoded)) {
            echo json_encode(['success' => true, 'templateId' => $templateId, 'hints' => []], JSON_UNESCAPED_SLASHES);
            return;
        }
        $fieldKeys = [];
        if (array_is_list($decoded)) {
            foreach ($decoded as $idx => $row) {
                if (!is_array($row)) {
                    continue;
                }
                $name = trim((string)($row['canonicalName'] ?? $row['name'] ?? $row['fieldName'] ?? ('field_' . $idx)));
                $name = canonicalizePdfFieldKey($name, 'field_' . $idx);
                if ($name !== '') {
                    $fieldKeys[] = $name;
                }
            }
        } else {
            foreach ($decoded as $key => $row) {
                if (!is_string($key)) {
                    continue;
                }
                $name = canonicalizePdfFieldKey(trim($key));
                if ($name === '' && is_array($row)) {
                    $name = canonicalizePdfFieldKey((string)($row['canonicalName'] ?? $row['name'] ?? $row['fieldName'] ?? ''));
                }
                if ($name !== '') {
                    $fieldKeys[] = $name;
                }
            }
        }
        $seriesStats = [];
        foreach ($fieldKeys as $fieldKey) {
            $normalized = strtolower(trim((string)$fieldKey));
            if ($normalized === '') {
                continue;
            }
            preg_match_all('/\d+/', $normalized, $m);
            $nums = is_array($m[0] ?? null) ? $m[0] : [];
            if ($nums === []) {
                continue;
            }
            $number = (int)end($nums);
            if ($number < 1 || $number > 9999) {
                continue;
            }
            $series = preg_replace('/\d+/', '#', $normalized);
            $series = preg_replace('/[^a-z#]+/', '_', (string)$series);
            $series = trim((string)$series, '_');
            if ($series === '') {
                continue;
            }
            if (!isset($seriesStats[$series])) {
                $seriesStats[$series] = [
                    'numbers' => [],
                    'examples' => [],
                ];
            }
            $seriesStats[$series]['numbers'][$number] = true;
            if (count($seriesStats[$series]['examples']) < 2) {
                $seriesStats[$series]['examples'][] = $fieldKey;
            }
        }
        $hints = [];
        foreach ($seriesStats as $series => $stat) {
            $numbers = array_map('intval', array_keys(is_array($stat['numbers']) ? $stat['numbers'] : []));
            sort($numbers, SORT_NUMERIC);
            if ($numbers === []) {
                continue;
            }
            $hints[] = [
                'series' => (string)$series,
                'count' => count($numbers),
                'min' => $numbers[0],
                'max' => $numbers[count($numbers) - 1],
                'numbers' => array_slice($numbers, 0, 8),
                'examples' => array_values(array_map('strval', is_array($stat['examples']) ? $stat['examples'] : [])),
            ];
        }
        usort($hints, static function (array $a, array $b): int {
            $ca = (int)($a['count'] ?? 0);
            $cb = (int)($b['count'] ?? 0);
            if ($ca !== $cb) {
                return $cb <=> $ca;
            }
            return strcmp((string)($a['series'] ?? ''), (string)($b['series'] ?? ''));
        });
        echo json_encode([
            'success' => true,
            'templateId' => $templateId,
            'hints' => array_slice($hints, 0, 6),
        ], JSON_UNESCAPED_SLASHES);
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

/**
 * @return array<int, array<string, mixed>>
 */
function enrichedFormImporterAliases(object $store): array {
    $aliases = method_exists($store, 'getFormImporterAliases') ? $store->getFormImporterAliases() : [];
    $catalog = method_exists($store, 'getFormCustomFields') ? $store->getFormCustomFields() : [];
    $catalogByLink = [];
    foreach ($catalog as $row) {
        if (!is_array($row)) {
            continue;
        }
        $linkId = strtolower(trim((string)($row['linkId'] ?? '')));
        if ($linkId === '' || isset($catalogByLink[$linkId])) {
            continue;
        }
        $catalogByLink[$linkId] = $row;
    }
    $out = [];
    foreach ($aliases as $row) {
        if (!is_array($row)) {
            continue;
        }
        $linkId = strtolower(trim((string)($row['linkId'] ?? '')));
        $linked = $catalogByLink[$linkId] ?? null;
        $out[] = [
            'id' => (string)($row['id'] ?? ''),
            'linkId' => $linkId,
            'pattern' => (string)($row['pattern'] ?? ''),
            'componentType' => strtolower(trim((string)($row['componentType'] ?? 'any'))),
            'priority' => is_numeric($row['priority'] ?? null) ? (int)$row['priority'] : 100,
            'scopeType' => strtolower(trim((string)($row['scopeType'] ?? 'global'))),
            'scopeValue' => strtolower(trim((string)($row['scopeValue'] ?? ''))),
            'pageMode' => strtolower(trim((string)($row['pageMode'] ?? 'any'))),
            'pageValue' => strtolower(trim((string)($row['pageValue'] ?? ''))),
            'numberMode' => strtolower(trim((string)($row['numberMode'] ?? 'any'))),
            'numberValue' => strtolower(trim((string)($row['numberValue'] ?? ''))),
            'requiresValue' => !empty($row['requiresValue']),
            'enabled' => !array_key_exists('enabled', $row) || !empty($row['enabled']),
            'description' => (string)($row['description'] ?? ''),
            'stats' => is_array($row['stats'] ?? null) ? $row['stats'] : ['hits' => 0, 'manualOverrides' => 0, 'lastMatchedAt' => ''],
            'linkedField' => is_array($linked) ? [
                'displayName' => (string)($linked['displayName'] ?? $linkId),
                'location' => strtolower((string)($linked['location'] ?? '')),
                'value' => (string)($linked['value'] ?? ''),
                'fieldType' => strtolower((string)($linked['fieldType'] ?? 'text')),
            ] : null,
        ];
    }
    return $out;
}

function handleFormImporterAliases(): void {
    global $store;
    header('Content-Type: application/json; charset=utf-8');
    if (!$store || !method_exists($store, 'getFormImporterAliases')) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Store not available']);
        return;
    }
    echo json_encode([
        'success' => true,
        'aliases' => enrichedFormImporterAliases($store),
        'catalog' => method_exists($store, 'getFormCustomFields') ? $store->getFormCustomFields() : [],
    ], JSON_UNESCAPED_SLASHES);
}

function handleFormImporterUpsertAlias(): void {
    global $store;
    header('Content-Type: application/json; charset=utf-8');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        return;
    }
    if (!$store || !method_exists($store, 'getFormImporterAliases') || !method_exists($store, 'setFormImporterAliases')) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Store not available']);
        return;
    }
    $decoded = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($decoded)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid JSON body']);
        return;
    }
    $id = preg_replace('/[^a-zA-Z0-9_-]/', '', trim((string)($decoded['id'] ?? '')));
    $linkId = preg_replace('/[^a-z0-9_]/', '_', strtolower(trim((string)($decoded['link_id'] ?? ''))));
    $pattern = trim((string)($decoded['pattern'] ?? ''));
    $componentType = strtolower(trim((string)($decoded['component_type'] ?? 'any')));
    $priority = is_numeric($decoded['priority'] ?? null) ? (int)$decoded['priority'] : 100;
    if ($priority < 1) {
        $priority = 1;
    } elseif ($priority > 9999) {
        $priority = 9999;
    }
    $scopeType = strtolower(trim((string)($decoded['scope_type'] ?? 'global')));
    if (!in_array($scopeType, ['global', 'form_family', 'template'], true)) {
        $scopeType = 'global';
    }
    $scopeValue = strtolower(trim((string)($decoded['scope_value'] ?? '')));
    $scopeValue = preg_replace('/[^a-z0-9_-]/', '_', $scopeValue);
    $scopeValue = trim((string)$scopeValue, '_');
    $pageMode = strtolower(trim((string)($decoded['page_mode'] ?? 'any')));
    if (!in_array($pageMode, ['any', 'first', 'last', 'only', 'except'], true)) {
        $pageMode = 'any';
    }
    $rawPageValue = strtolower(trim((string)($decoded['page_value'] ?? '')));
    $pageTokens = preg_split('/[^0-9]+/', $rawPageValue) ?: [];
    $pageNumbers = [];
    foreach ($pageTokens as $token) {
        if ($token === '') {
            continue;
        }
        $n = (int)$token;
        if ($n < 1 || $n > 9999) {
            continue;
        }
        $pageNumbers[$n] = true;
    }
    $pageValue = implode(',', array_keys($pageNumbers));
    $numberMode = strtolower(trim((string)($decoded['number_mode'] ?? 'any')));
    if (!in_array($numberMode, ['any', 'first', 'last', 'only', 'except'], true)) {
        $numberMode = 'any';
    }
    $rawNumberValue = strtolower(trim((string)($decoded['number_value'] ?? '')));
    $numberTokens = preg_split('/[^0-9]+/', $rawNumberValue) ?: [];
    $numberValues = [];
    foreach ($numberTokens as $token) {
        if ($token === '') {
            continue;
        }
        $n = (int)$token;
        if ($n < 1 || $n > 9999) {
            continue;
        }
        $numberValues[$n] = true;
    }
    $numberValue = implode(',', array_keys($numberValues));
    $requiresValue = !empty($decoded['requires_value']);
    $enabled = !array_key_exists('enabled', $decoded) || !empty($decoded['enabled']);
    $description = sanitizeString((string)($decoded['description'] ?? ''), 255);
    if (!in_array($componentType, ['any', 'text', 'textarea', 'checkable'], true)) {
        $componentType = 'any';
    }
    if ($scopeType === 'global') {
        $scopeValue = '';
    }
    if ($scopeType !== 'global' && $scopeValue === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'scope_value is required when scope_type is not global']);
        return;
    }
    if (in_array($pageMode, ['only', 'except'], true) && $pageValue === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'page_value is required when page_mode is only or except']);
        return;
    }
    if (!in_array($pageMode, ['only', 'except'], true)) {
        $pageValue = '';
    }
    if (in_array($numberMode, ['only', 'except'], true) && $numberValue === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'number_value is required when number_mode is only or except']);
        return;
    }
    if (!in_array($numberMode, ['only', 'except'], true)) {
        $numberValue = '';
    }
    if ($linkId === '' || $pattern === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'link_id and pattern are required']);
        return;
    }
    $aliases = $store->getFormImporterAliases();
    if (!is_array($aliases)) {
        $aliases = [];
    }
    if ($id === '') {
        $slugifyAliasId = static function (string $value): string {
            $v = strtolower(trim($value));
            $v = preg_replace('/[^a-z0-9]+/', '_', $v);
            $v = trim((string)$v, '_');
            $v = substr($v, 0, 48);
            return $v !== '' ? $v : 'entry';
        };
        $source = sanitizeString((string)($decoded['description'] ?? ''), 255);
        if ($source === '') {
            $source = $linkId !== '' ? $linkId : 'entry';
        }
        $baseId = 'alias_' . $slugifyAliasId($source);
        $used = [];
        foreach ($aliases as $row) {
            if (!is_array($row)) {
                continue;
            }
            $existingId = strtolower(trim((string)($row['id'] ?? '')));
            if ($existingId !== '') {
                $used[$existingId] = true;
            }
        }
        $candidate = strtolower($baseId);
        if (!isset($used[$candidate])) {
            $id = $candidate;
        } else {
            $suffix = 2;
            while ($suffix < 10000) {
                $next = $candidate . '_' . $suffix;
                if (!isset($used[$next])) {
                    $id = $next;
                    break;
                }
                $suffix++;
            }
            if ($id === '') {
                $id = $candidate . '_' . date('His');
            }
        }
    }
    $updated = false;
    foreach ($aliases as &$row) {
        if (!is_array($row) || (string)($row['id'] ?? '') !== $id) {
            continue;
        }
        $row['linkId'] = $linkId;
        $row['pattern'] = $pattern;
        $row['componentType'] = $componentType;
        $row['priority'] = $priority;
        $row['scopeType'] = $scopeType;
        $row['scopeValue'] = $scopeValue;
        $row['pageMode'] = $pageMode;
        $row['pageValue'] = $pageValue;
        $row['numberMode'] = $numberMode;
        $row['numberValue'] = $numberValue;
        $row['requiresValue'] = $requiresValue;
        $row['enabled'] = $enabled;
        $row['description'] = $description;
        $row['stats'] = is_array($row['stats'] ?? null) ? $row['stats'] : ['hits' => 0, 'manualOverrides' => 0, 'lastMatchedAt' => ''];
        $updated = true;
        break;
    }
    unset($row);
    if (!$updated) {
        $aliases[] = [
            'id' => $id,
            'linkId' => $linkId,
            'pattern' => $pattern,
            'componentType' => $componentType,
            'priority' => $priority,
            'scopeType' => $scopeType,
            'scopeValue' => $scopeValue,
            'pageMode' => $pageMode,
            'pageValue' => $pageValue,
            'numberMode' => $numberMode,
            'numberValue' => $numberValue,
            'requiresValue' => $requiresValue,
            'enabled' => $enabled,
            'description' => $description,
            'stats' => ['hits' => 0, 'manualOverrides' => 0, 'lastMatchedAt' => ''],
        ];
    }
    $store->setFormImporterAliases($aliases);
    echo json_encode([
        'success' => true,
        'aliases' => enrichedFormImporterAliases($store),
    ], JSON_UNESCAPED_SLASHES);
}

function handleFormImporterDeleteAlias(): void {
    global $store;
    header('Content-Type: application/json; charset=utf-8');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        return;
    }
    if (!$store || !method_exists($store, 'getFormImporterAliases') || !method_exists($store, 'setFormImporterAliases')) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Store not available']);
        return;
    }
    $decoded = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($decoded)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid JSON body']);
        return;
    }
    $id = preg_replace('/[^a-zA-Z0-9_-]/', '', trim((string)($decoded['id'] ?? '')));
    if ($id === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'id is required']);
        return;
    }
    $aliases = $store->getFormImporterAliases();
    if (!is_array($aliases)) {
        $aliases = [];
    }
    $aliases = array_values(array_filter($aliases, static function ($row) use ($id): bool {
        return is_array($row) && (string)($row['id'] ?? '') !== $id;
    }));
    $store->setFormImporterAliases($aliases);
    echo json_encode([
        'success' => true,
        'aliases' => enrichedFormImporterAliases($store),
    ], JSON_UNESCAPED_SLASHES);
}

function handleFormImporterMatchExplain(): void {
    global $store;
    header('Content-Type: application/json; charset=utf-8');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        return;
    }
    if (!$store || !method_exists($store, 'getFormCustomFields')) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Store not available']);
        return;
    }
    $decoded = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($decoded)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid JSON body']);
        return;
    }
    $templateId = sanitizeId((string)($decoded['template_id'] ?? ''));
    $positionsRaw = $decoded['positions'] ?? [];
    if (!is_array($positionsRaw)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'positions must be an object']);
        return;
    }
    $positions = [];
    foreach ($positionsRaw as $key => $pos) {
        if (!is_string($key) || trim($key) === '' || !is_array($pos)) {
            continue;
        }
        $positions[$key] = $pos;
    }
    $explain = [];
    applyUniversalCustomFieldOverrides(
        [],
        $positions,
        $store->getFormCustomFields(),
        method_exists($store, 'getFormImporterMatchingMode') ? $store->getFormImporterMatchingMode() : 'exact',
        method_exists($store, 'getFormImporterAliases') ? $store->getFormImporterAliases() : [],
        $templateId,
        $explain
    );
    echo json_encode([
        'success' => true,
        'templateId' => $templateId,
        'templateFamily' => resolveFormTemplateFamily($templateId),
        'explain' => $explain,
    ], JSON_UNESCAPED_SLASHES);
}

function handleFormImporterSuggestAlias(): void {
    global $store;
    header('Content-Type: application/json; charset=utf-8');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        return;
    }
    if (!$store || !method_exists($store, 'getFormImporterAliases') || !method_exists($store, 'setFormImporterAliases')) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Store not available']);
        return;
    }
    $decoded = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($decoded)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid JSON body']);
        return;
    }
    $fieldKey = trim((string)($decoded['field_key'] ?? ''));
    $linkId = preg_replace('/[^a-z0-9_]/', '_', strtolower(trim((string)($decoded['link_id'] ?? ''))));
    $templateId = sanitizeId((string)($decoded['template_id'] ?? ''));
    $componentType = strtolower(trim((string)($decoded['component_type'] ?? 'text')));
    if (!in_array($componentType, ['any', 'text', 'textarea', 'checkable'], true)) {
        $componentType = 'text';
    }
    if ($fieldKey === '' || $linkId === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'field_key and link_id are required']);
        return;
    }
    $tokens = preg_split('/[^a-z0-9]+/i', strtolower($fieldKey)) ?: [];
    $tokens = array_values(array_filter(array_map(static fn($t) => trim((string)$t), $tokens), static fn($t) => $t !== ''));
    if (empty($tokens)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Could not derive tokens from field_key']);
        return;
    }
    $escaped = array_map(static fn($t) => preg_quote((string)$t, '~'), $tokens);
    $pattern = '(?:' . implode('[^a-z0-9]*', $escaped) . ')';
    $baseId = 'alias_' . substr(preg_replace('/[^a-z0-9_]/', '_', strtolower($linkId . '_' . $tokens[0])), 0, 48);
    $aliases = $store->getFormImporterAliases();
    if (!is_array($aliases)) {
        $aliases = [];
    }
    $used = [];
    foreach ($aliases as $row) {
        if (!is_array($row)) {
            continue;
        }
        $id = strtolower(trim((string)($row['id'] ?? '')));
        if ($id !== '') {
            $used[$id] = true;
        }
    }
    $id = $baseId !== '' ? $baseId : 'alias_suggested';
    $counter = 2;
    while (isset($used[$id]) && $counter < 9999) {
        $id = $baseId . '_' . $counter;
        $counter++;
    }
    $scopeType = $templateId !== '' ? 'template' : 'global';
    $scopeValue = $templateId !== '' ? strtolower($templateId) : '';
    $aliases[] = [
        'id' => $id,
        'linkId' => $linkId,
        'pattern' => $pattern,
        'componentType' => $componentType,
        'priority' => 200,
        'scopeType' => $scopeType,
        'scopeValue' => $scopeValue,
        'pageMode' => 'any',
        'pageValue' => '',
        'numberMode' => 'any',
        'numberValue' => '',
        'requiresValue' => false,
        'enabled' => false,
        'description' => sanitizeString((string)($decoded['description'] ?? ('Suggested from manual map: ' . $fieldKey)), 255),
        'stats' => ['hits' => 0, 'manualOverrides' => 0, 'lastMatchedAt' => ''],
    ];
    $store->setFormImporterAliases($aliases);
    echo json_encode([
        'success' => true,
        'aliases' => enrichedFormImporterAliases($store),
        'suggestedId' => $id,
    ], JSON_UNESCAPED_SLASHES);
}

function handleFirmDefaultsFields(): void {
    global $store;
    header('Content-Type: application/json; charset=utf-8');
    if (!$store || !method_exists($store, 'getFirmDefaultFields')) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Store not available']);
        return;
    }
    $rows = $store->getFirmDefaultFields();
    $mode = method_exists($store, 'getFormImporterMatchingMode')
        ? $store->getFormImporterMatchingMode()
        : 'exact';
    echo json_encode([
        'success' => true,
        'matchingMode' => $mode,
        'fieldTypes' => fieldManagerAllowedTypes(),
        'fields' => array_map(static function (array $row): array {
            return [
                'id' => (string)($row['id'] ?? ''),
                'displayName' => (string)($row['displayName'] ?? ''),
                'fieldType' => strtolower((string)($row['fieldType'] ?? 'text')),
                'matchingTag' => (string)($row['matchingTag'] ?? ($row['linkId'] ?? '')),
                'value' => (string)($row['value'] ?? ''),
                'location' => 'firm',
            ];
        }, $rows),
    ], JSON_UNESCAPED_SLASHES);
}

function handleFirmDefaultsUpdateValue(): void {
    global $store;
    header('Content-Type: application/json; charset=utf-8');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        return;
    }
    if (!$store || !method_exists($store, 'updateFormCustomFieldValueById')) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Store not available']);
        return;
    }
    $decoded = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($decoded)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid JSON body']);
        return;
    }
    $id = sanitizeId((string)($decoded['id'] ?? ''));
    if ($id === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'id is required']);
        return;
    }
    $value = (string)($decoded['value'] ?? '');
    try {
        $row = $store->updateFormCustomFieldValueById($id, $value);
        if (!$row || strtolower((string)($row['location'] ?? 'firm')) !== 'firm') {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Firm field not found']);
            return;
        }
        echo json_encode([
            'success' => true,
            'field' => [
                'id' => (string)($row['id'] ?? ''),
                'displayName' => (string)($row['displayName'] ?? ''),
                'fieldType' => strtolower((string)($row['fieldType'] ?? 'text')),
                'matchingTag' => (string)($row['matchingTag'] ?? ($row['linkId'] ?? '')),
                'value' => (string)($row['value'] ?? ''),
                'location' => 'firm',
            ],
            'fields' => method_exists($store, 'getFirmDefaultFields') ? $store->getFirmDefaultFields() : [],
        ], JSON_UNESCAPED_SLASHES);
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

/** @return array<string, mixed> */
function inferAttorneyDisplayName(array $fieldValues, array $fieldRows, string $storedName = ''): string {
    $best = '';
    $bestScore = -100000;
    foreach ($fieldRows as $fieldRow) {
        if (!is_array($fieldRow)) {
            continue;
        }
        $fid = sanitizeId((string)($fieldRow['id'] ?? ''));
        if ($fid === '') {
            continue;
        }
        $value = trim((string)($fieldValues[$fid] ?? ''));
        if ($value === '') {
            continue;
        }
        $linkId = strtolower(trim((string)($fieldRow['linkId'] ?? '')));
        $display = strtolower(trim((string)($fieldRow['displayName'] ?? $fieldRow['label'] ?? '')));
        $matchingTag = strtolower(trim((string)($fieldRow['matchingTag'] ?? '')));
        $score = 0;
        if ($linkId === 'attorney_name') { $score += 1000; }
        if (strpos($linkId, 'attorney_name') !== false) { $score += 900; }
        if (strpos($matchingTag, 'attyname') !== false || strpos($matchingTag, 'attorneyname') !== false) { $score += 850; }
        if (strpos($display, 'attorney') !== false && strpos($display, 'name') !== false) { $score += 800; }
        if (preg_match('/(^|_)name($|_)/', $linkId)) { $score += 300; }
        if (strpos($display, 'name') !== false) { $score += 200; }
        if (strpos($linkId, 'bar') !== false || strpos($display, 'bar') !== false) { $score -= 700; }
        if (
            strpos($linkId, 'phone') !== false ||
            strpos($linkId, 'fax') !== false ||
            strpos($linkId, 'email') !== false ||
            strpos($linkId, 'zip') !== false
        ) {
            $score -= 500;
        }
        if ($score > $bestScore) {
            $bestScore = $score;
            $best = $value;
        }
    }
    if ($best !== '') {
        return $best;
    }
    foreach (['attorney_name', 'attorney', 'name'] as $key) {
        $value = trim((string)($fieldValues[$key] ?? ''));
        if ($value !== '') {
            return $value;
        }
    }
    $storedName = trim($storedName);
    if ($storedName !== '') {
        return $storedName;
    }
    foreach ($fieldValues as $value) {
        $text = trim((string)$value);
        if ($text !== '') {
            return $text;
        }
    }
    return 'Untitled Attorney';
}

/** @return array<string, mixed> */
function formatAttorneyApiRow(array $row, array $fieldRows): array {
    $fieldValues = is_array($row['fieldValues'] ?? null) ? $row['fieldValues'] : [];
    $fields = [];
    foreach ($fieldRows as $fieldRow) {
        if (!is_array($fieldRow)) {
            continue;
        }
        $fid = sanitizeId((string)($fieldRow['id'] ?? ''));
        if ($fid === '') {
            continue;
        }
        $fields[] = [
            'id' => $fid,
            'linkId' => (string)($fieldRow['linkId'] ?? ''),
            'displayName' => (string)($fieldRow['displayName'] ?? ''),
            'fieldType' => strtolower((string)($fieldRow['fieldType'] ?? 'text')),
            'value' => (string)($fieldValues[$fid] ?? ''),
        ];
    }
    return [
        'id' => (string)($row['id'] ?? ''),
        'displayName' => inferAttorneyDisplayName($fieldValues, $fieldRows, (string)($row['displayName'] ?? '')),
        'fieldValues' => $fieldValues,
        'fields' => $fields,
        'createdAt' => (string)($row['createdAt'] ?? ''),
        'updatedAt' => (string)($row['updatedAt'] ?? ''),
    ];
}

function handleAttorneysList(): void {
    global $store;
    header('Content-Type: application/json; charset=utf-8');
    if (!$store || !method_exists($store, 'getAttorneys')) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Store not available']);
        return;
    }
    $fieldRows = method_exists($store, 'getFieldManagerCustomFields') ? $store->getFieldManagerCustomFields('attorney') : [];
    $rows = array_map(static function (array $row) use ($fieldRows): array {
        return formatAttorneyApiRow($row, $fieldRows);
    }, (array)$store->getAttorneys());
    echo json_encode(['success' => true, 'attorneys' => $rows, 'fieldRows' => $fieldRows], JSON_UNESCAPED_SLASHES);
}

function handleAttorneysUpsert(): void {
    global $store;
    header('Content-Type: application/json; charset=utf-8');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        return;
    }
    if (!$store || !method_exists($store, 'createAttorney')) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Store not available']);
        return;
    }
    $decoded = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($decoded)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid JSON body']);
        return;
    }
    $id = sanitizeId((string)($decoded['id'] ?? ''));
    $fieldValuesRaw = is_array($decoded['fieldValues'] ?? null) ? $decoded['fieldValues'] : [];
    $fieldValues = [];
    foreach ($fieldValuesRaw as $k => $v) {
        $key = sanitizeId((string)$k);
        if ($key === '') {
            continue;
        }
        $fieldValues[$key] = sanitizeString((string)$v, 1000);
    }
    try {
        if ($id !== '' && method_exists($store, 'updateAttorney')) {
            $row = $store->updateAttorney($id, $fieldValues);
            if (!$row) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Attorney not found']);
                return;
            }
        } else {
            $row = $store->createAttorney($fieldValues);
        }
        $fieldRows = method_exists($store, 'getFieldManagerCustomFields') ? $store->getFieldManagerCustomFields('attorney') : [];
        echo json_encode(['success' => true, 'attorney' => formatAttorneyApiRow($row, $fieldRows)], JSON_UNESCAPED_SLASHES);
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function handleAttorneysDelete(): void {
    global $store;
    header('Content-Type: application/json; charset=utf-8');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        return;
    }
    if (!$store || !method_exists($store, 'deleteAttorney')) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Store not available']);
        return;
    }
    $decoded = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($decoded)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid JSON body']);
        return;
    }
    $id = sanitizeId((string)($decoded['id'] ?? ''));
    if ($id === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'id is required']);
        return;
    }
    if (!$store->deleteAttorney($id)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Attorney not found']);
        return;
    }
    echo json_encode(['success' => true]);
}

function handleFirmDefaultsMatchingMode(): void {
    global $store;
    header('Content-Type: application/json; charset=utf-8');
    if (!$store || !method_exists($store, 'getFormImporterMatchingMode')) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Store not available']);
        return;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        echo json_encode([
            'success' => true,
            'matchingMode' => $store->getFormImporterMatchingMode(),
        ], JSON_UNESCAPED_SLASHES);
        return;
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        return;
    }
    if (!method_exists($store, 'setFormImporterMatchingMode')) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Store cannot set matching mode']);
        return;
    }
    $decoded = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($decoded)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid JSON body']);
        return;
    }
    $mode = strtolower(trim((string)($decoded['matching_mode'] ?? 'exact')));
    if (!in_array($mode, ['exact', 'regex'], true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'matching_mode must be exact or regex']);
        return;
    }
    echo json_encode([
        'success' => true,
        'matchingMode' => $store->setFormImporterMatchingMode($mode),
    ], JSON_UNESCAPED_SLASHES);
}

/**
 * @return array<int, string>
 */
function fieldManagerAllowedTypes(): array {
    return ['text', 'number', 'date', 'checkbox', 'select', 'email', 'phone'];
}

/**
 * @return array<string, array<int, array<string, mixed>>>
 */
function groupedFieldManagerFields(): array {
    global $store;
    $all = method_exists($store, 'getFieldManagerCustomFields')
        ? $store->getFieldManagerCustomFields()
        : (method_exists($store, 'getFormCustomFields') ? $store->getFormCustomFields() : []);
    $grouped = ['firm' => [], 'client' => [], 'court' => [], 'case' => []];
    foreach ($all as $row) {
        $location = strtolower((string)($row['location'] ?? 'firm'));
        if (!array_key_exists($location, $grouped)) {
            continue;
        }
        $grouped[$location][] = [
            'id' => (string)($row['id'] ?? ''),
            'linkId' => (string)($row['linkId'] ?? ''),
            'displayName' => (string)($row['displayName'] ?? ''),
            'fieldType' => (static function (string $t): string {
                $raw = strtolower(trim($t));
                return $raw === 'sample_text' ? 'text' : ($raw !== '' ? $raw : 'text');
            })((string)($row['fieldType'] ?? 'text')),
            'matchingTag' => (string)($row['matchingTag'] ?? ($row['linkId'] ?? '')),
            'location' => $location,
            'isSystem' => !empty($row['isSystem']),
            'sampleText' => (string)($row['value'] ?? ''),
            // Keep creation metadata for deterministic sorting, then remove before response.
            '__createdAt' => (string)($row['createdAt'] ?? ''),
        ];
    }
    foreach (array_keys($grouped) as $locationKey) {
        usort($grouped[$locationKey], static function (array $a, array $b): int {
            $aSystem = !empty($a['isSystem']);
            $bSystem = !empty($b['isSystem']);
            if ($aSystem !== $bSystem) {
                return $aSystem ? -1 : 1;
            }
            $aTs = strtotime((string)($a['__createdAt'] ?? '')) ?: 0;
            $bTs = strtotime((string)($b['__createdAt'] ?? '')) ?: 0;
            if ($aTs !== $bTs) {
                return $aTs <=> $bTs;
            }
            return strcmp((string)($a['id'] ?? ''), (string)($b['id'] ?? ''));
        });
        foreach ($grouped[$locationKey] as &$row) {
            unset($row['__createdAt']);
        }
        unset($row);
    }
    return $grouped;
}

/** @return array<int, array<string, mixed>> */
function groupedGlobalFormTemplatesForSets(): array {
    global $store;
    $rows = method_exists($store, 'getGlobalFormTemplates') ? $store->getGlobalFormTemplates() : [];
    $out = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $tid = sanitizeId((string)($row['templateId'] ?? ''));
        if ($tid === '') {
            continue;
        }
        $out[] = [
            'templateId' => $tid,
            'formName' => trim((string)($row['formName'] ?? $tid)),
            'sourceFileName' => trim((string)($row['sourceFileName'] ?? '')),
            'formLocation' => trim((string)($row['formLocation'] ?? '')),
            'scope' => trim((string)($row['scope'] ?? 'global')),
        ];
    }
    usort($out, static function (array $a, array $b): int {
        return strcasecmp((string)($a['formName'] ?? ''), (string)($b['formName'] ?? ''));
    });
    return $out;
}

function handleFormSetsList(): void {
    global $store;
    header('Content-Type: application/json; charset=utf-8');
    if (!$store || !method_exists($store, 'getGlobalFormSets')) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Store not available']);
        return;
    }
    echo json_encode([
        'success' => true,
        'formSets' => $store->getGlobalFormSets(),
        'presets' => method_exists($store, 'getGlobalFormSetPresets') ? $store->getGlobalFormSetPresets() : [],
        'forms' => groupedGlobalFormTemplatesForSets(),
    ], JSON_UNESCAPED_SLASHES);
}

function handleFormSetsUpsert(): void {
    global $store;
    header('Content-Type: application/json; charset=utf-8');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        return;
    }
    if (!$store || !method_exists($store, 'upsertGlobalFormSet') || !method_exists($store, 'getGlobalFormSets')) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Store not available']);
        return;
    }
    $decoded = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($decoded)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid JSON body']);
        return;
    }
    $id = sanitizeId((string)($decoded['id'] ?? ''));
    $name = sanitizeString((string)($decoded['name'] ?? ''), 255);
    $templateIds = [];
    foreach ((array)($decoded['template_ids'] ?? []) as $value) {
        $tid = sanitizeId((string)$value);
        if ($tid !== '') {
            $templateIds[] = $tid;
        }
    }
    if (trim($name) === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Form set name is required.']);
        return;
    }
    $nameNorm = mb_strtolower(trim($name));
    foreach ((array)$store->getGlobalFormSets() as $row) {
        if (!is_array($row)) { continue; }
        $rowId = sanitizeId((string)($row['id'] ?? ''));
        if ($id !== '' && $rowId === $id) {
            continue;
        }
        $rowNameNorm = mb_strtolower(trim((string)($row['name'] ?? '')));
        if ($rowNameNorm !== '' && $rowNameNorm === $nameNorm) {
            http_response_code(409);
            echo json_encode(['success' => false, 'error' => 'A form set with that name already exists.']);
            return;
        }
    }
    $uniqueTemplateIds = array_values(array_unique($templateIds));
    $row = $store->upsertGlobalFormSet($name, $uniqueTemplateIds, $id, false);
    echo json_encode([
        'success' => true,
        'formSet' => $row,
        'formSets' => $store->getGlobalFormSets(),
    ], JSON_UNESCAPED_SLASHES);
}

function handleFormSetsDelete(): void {
    global $store;
    header('Content-Type: application/json; charset=utf-8');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        return;
    }
    if (!$store || !method_exists($store, 'deleteGlobalFormSet') || !method_exists($store, 'getGlobalFormSets')) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Store not available']);
        return;
    }
    $decoded = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($decoded)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid JSON body']);
        return;
    }
    $id = sanitizeId((string)($decoded['id'] ?? ''));
    if ($id === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Form set id is required.']);
        return;
    }
    $deleted = (bool)$store->deleteGlobalFormSet($id);
    if (!$deleted) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Form set not found or cannot be deleted.']);
        return;
    }
    echo json_encode([
        'success' => true,
        'id' => $id,
        'formSets' => $store->getGlobalFormSets(),
    ], JSON_UNESCAPED_SLASHES);
}

function courtDirectorySourceMeta(): array {
    return [
        'state' => [
            'label' => 'California state (superior) courts',
            'tooltip' => 'California trial courts. Los Angeles locations include department and room numbers from the LA Superior Court public directory (lacourt.ca.gov). Other counties use Judicial Council building addresses (courts.ca.gov facility lists) — addresses only, not department/room.',
            'sources' => [
                ['name' => 'LA Superior Court', 'url' => 'https://www.lacourt.ca.gov/courtroom/UI/Courtrooms.aspx'],
                ['name' => 'Judicial Council of CA', 'url' => 'https://courts.ca.gov/'],
            ],
        ],
        'federal' => [
            'label' => 'U.S. federal courts',
            'tooltip' => 'U.S. federal district, bankruptcy, and appellate division offices. Addresses from the official PACER CM/ECF Court Lookup (pacer.uscourts.gov). Division office addresses only — not individual courtroom numbers.',
            'sources' => [
                ['name' => 'PACER CM/ECF Court Lookup', 'url' => 'https://pacer.uscourts.gov/file-case/court-cmecf-lookup/data.json'],
            ],
        ],
    ];
}

function courtsDirectoryPath(): string {
    return dirname(__DIR__) . '/data/courts_ca.json';
}

/** @return array{locations: array<int, array<string, mixed>>} */
function loadCourtsDirectoryFile(): array {
    $path = courtsDirectoryPath();
    if (!is_file($path)) {
        return ['locations' => []];
    }
    $decoded = json_decode((string)file_get_contents($path), true);
    if (!is_array($decoded)) {
        return ['locations' => []];
    }
    return ['locations' => is_array($decoded['locations'] ?? null) ? $decoded['locations'] : []];
}

/**
 * @param array<int, array<string, mixed>> $incoming
 * @param callable(array<string, mixed>): bool $shouldReplaceExisting
 * @return array{locations: array<int, array<string, mixed>>}
 */
function mergeCourtsDirectory(array $incoming, callable $shouldReplaceExisting): array {
    $existing = loadCourtsDirectoryFile();
    $byId = [];
    foreach ((array)($existing['locations'] ?? []) as $row) {
        if (!is_array($row)) {
            continue;
        }
        if ($shouldReplaceExisting($row)) {
            continue;
        }
        $id = trim((string)($row['id'] ?? ''));
        if ($id !== '') {
            $byId[$id] = $row;
        }
    }
    foreach ($incoming as $row) {
        if (!is_array($row)) {
            continue;
        }
        $id = trim((string)($row['id'] ?? ''));
        if ($id !== '') {
            $byId[$id] = $row;
        }
    }
    $merged = array_values($byId);
    usort($merged, static function (array $a, array $b): int {
        $sys = strcasecmp((string)($a['courtSystem'] ?? 'state'), (string)($b['courtSystem'] ?? 'state'));
        if ($sys !== 0) {
            return $sys;
        }
        $c = strcasecmp((string)($a['county'] ?? ''), (string)($b['county'] ?? ''));
        return $c !== 0 ? $c : strcasecmp((string)($a['courtName'] ?? ''), (string)($b['courtName'] ?? ''));
    });
    return ['locations' => $merged];
}

function handleCourtsSearch(): void {
    global $store;
    header('Content-Type: application/json; charset=utf-8');
    if (!$store || !method_exists($store, 'searchCourts')) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Store not available']);
        return;
    }
    $q = sanitizeString((string)($_GET['q'] ?? ''), 200);
    $limit = (int)($_GET['limit'] ?? 25);
    $system = sanitizeString((string)($_GET['system'] ?? ''), 16);
    $results = $store->searchCourts($q, $limit, $system);
    echo json_encode([
        'success' => true,
        'query' => $q,
        'system' => $system,
        'sourceMeta' => courtDirectorySourceMeta(),
        'results' => $results,
    ], JSON_UNESCAPED_SLASHES);
}

function handleCourtsReimportLa(): void {
    global $store;
    header('Content-Type: application/json; charset=utf-8');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'POST required']);
        return;
    }
    if (!$store || !method_exists($store, 'importCourtLocationsSnapshot')) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Store not available']);
        return;
    }
    $parserPath = dirname(__DIR__) . '/scripts/courts/lib/lacourt_parser.php';
    if (!is_file($parserPath)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'LA court parser missing']);
        return;
    }
    require_once $parserPath;

    $root = dirname(__DIR__);
    $addressMapPath = $root . '/data/lacourt_addresses.json';
    $courtsPath = courtsDirectoryPath();
    $addressMap = lacourt_load_address_map($addressMapPath);

    $html = trim((string)($_POST['html'] ?? ''));
    if ($html === '') {
        $html = lacourt_fetch_html('https://www.lacourt.ca.gov/courtroom/UI/Courtrooms.aspx');
    }
    if (trim($html) === '') {
        http_response_code(502);
        echo json_encode(['success' => false, 'error' => 'Could not fetch lacourt.ca.gov (403 or network)']);
        return;
    }

    $laLocations = lacourt_parse_courtrooms_html($html, $addressMap);
    if ($laLocations === []) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'No locations parsed from lacourt HTML']);
        return;
    }

    foreach ($laLocations as &$row) {
        $row['courtSystem'] = 'state';
        $row['source'] = 'lacourt';
    }
    unset($row);

    $merged = mergeCourtsDirectory($laLocations, static function (array $row): bool {
        return strtolower(trim((string)($row['source'] ?? ''))) === 'lacourt';
    });

    @file_put_contents(
        $courtsPath,
        json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
    );
    $imported = $store->importCourtLocationsSnapshot($merged['locations']);

    $deptTotal = 0;
    foreach ($laLocations as $loc) {
        $deptTotal += count((array)($loc['departments'] ?? []));
    }

    echo json_encode([
        'success' => true,
        'laLocations' => count($laLocations),
        'laDepartments' => $deptTotal,
        'totalLocations' => count($merged['locations']),
        'imported' => $imported,
    ], JSON_UNESCAPED_SLASHES);
}

function handleCourtsReimportFederal(): void {
    global $store;
    header('Content-Type: application/json; charset=utf-8');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'POST required']);
        return;
    }
    if (!$store || !method_exists($store, 'importCourtLocationsSnapshot')) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Store not available']);
        return;
    }
    $parserPath = dirname(__DIR__) . '/scripts/courts/lib/pacer_parser.php';
    if (!is_file($parserPath)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'PACER parser missing']);
        return;
    }
    require_once $parserPath;
    $data = pacer_fetch_json();
    if ($data === []) {
        http_response_code(502);
        echo json_encode(['success' => false, 'error' => 'Could not fetch PACER court directory JSON']);
        return;
    }
    $federalLocations = pacer_parse_federal_locations($data);
    if ($federalLocations === []) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'No federal locations parsed from PACER data']);
        return;
    }
    $merged = mergeCourtsDirectory($federalLocations, static function (array $row): bool {
        return strtolower(trim((string)($row['courtSystem'] ?? ''))) === 'federal';
    });
    @file_put_contents(
        courtsDirectoryPath(),
        json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
    );
    $imported = $store->importCourtLocationsSnapshot($merged['locations']);
    echo json_encode([
        'success' => true,
        'federalLocations' => count($federalLocations),
        'totalLocations' => count($merged['locations']),
        'imported' => $imported,
    ], JSON_UNESCAPED_SLASHES);
}

function handleCourtsReimportCaStatewide(): void {
    global $store;
    header('Content-Type: application/json; charset=utf-8');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'POST required']);
        return;
    }
    if (!$store || !method_exists($store, 'importCourtLocationsSnapshot')) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Store not available']);
        return;
    }
    $parserPath = dirname(__DIR__) . '/scripts/courts/lib/jc_ca_parser.php';
    if (!is_file($parserPath)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'JC CA parser missing']);
        return;
    }
    require_once $parserPath;
    $stateLocations = jc_ca_parse_buildings_file(jc_ca_buildings_path());
    if ($stateLocations === []) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'No JC CA buildings parsed — check data/jc_ca_buildings.txt']);
        return;
    }
    $merged = mergeCourtsDirectory($stateLocations, static function (array $row): bool {
        return strtolower(trim((string)($row['source'] ?? ''))) === 'jc_ca';
    });
    @file_put_contents(
        courtsDirectoryPath(),
        json_encode($merged, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
    );
    $imported = $store->importCourtLocationsSnapshot($merged['locations']);
    echo json_encode([
        'success' => true,
        'statewideLocations' => count($stateLocations),
        'totalLocations' => count($merged['locations']),
        'imported' => $imported,
    ], JSON_UNESCAPED_SLASHES);
}

function handleCourtsReimportAll(): void {
    global $store;
    header('Content-Type: application/json; charset=utf-8');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'POST required']);
        return;
    }
    $results = [];
    foreach (['handleCourtsReimportCaStatewide', 'handleCourtsReimportLa', 'handleCourtsReimportFederal'] as $fn) {
        ob_start();
        $fn();
        $raw = (string)ob_get_clean();
        $decoded = json_decode($raw, true);
        $results[$fn] = is_array($decoded) ? $decoded : ['success' => false, 'raw' => $raw];
    }
    $allOk = true;
    foreach ($results as $row) {
        if (empty($row['success'])) {
            $allOk = false;
            break;
        }
    }
    echo json_encode([
        'success' => $allOk,
        'steps' => $results,
    ], JSON_UNESCAPED_SLASHES);
}

function handleFieldManagerFields(): void {
    global $store;
    header('Content-Type: application/json; charset=utf-8');
    if (!$store || !method_exists($store, 'getFieldManagerCustomFields')) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Store not available']);
        return;
    }
    $location = strtolower(trim((string)($_GET['location'] ?? '')));
    $grouped = groupedFieldManagerFields();
    if ($location !== '' && array_key_exists($location, $grouped)) {
        echo json_encode([
            'success' => true,
            'fieldTypes' => fieldManagerAllowedTypes(),
            'fields' => $grouped[$location],
            'location' => $location,
        ], JSON_UNESCAPED_SLASHES);
        return;
    }
    echo json_encode([
        'success' => true,
        'fieldTypes' => fieldManagerAllowedTypes(),
        'fieldsByLocation' => $grouped,
    ], JSON_UNESCAPED_SLASHES);
}

/**
 * @return array<string, mixed>|null Existing row that already uses this matching tag in the same location.
 */
function fieldManagerFindDuplicateMatchingTag(object $store, string $location, string $matchingTag, string $excludeFieldId = '', string $excludeLinkId = ''): ?array {
    $want = strtolower(trim($matchingTag));
    if ($want === '') {
        return null;
    }
    if (!method_exists($store, 'getFieldManagerCustomFields')) {
        return null;
    }
    $excludeLinkNorm = strtolower(trim($excludeLinkId));
    foreach ($store->getFieldManagerCustomFields($location) as $row) {
        $rid = (string)($row['id'] ?? '');
        if ($excludeFieldId !== '' && $rid === $excludeFieldId) {
            continue;
        }
        if ($excludeLinkNorm !== '' && strtolower(trim((string)($row['linkId'] ?? ''))) === $excludeLinkNorm) {
            continue;
        }
        $existing = strtolower(trim((string)($row['matchingTag'] ?? '')));
        if ($existing === $want) {
            return $row;
        }
    }
    return null;
}

function handleFieldManagerUpsertField(): void {
    global $store;
    header('Content-Type: application/json; charset=utf-8');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        return;
    }
    if (!$store || !method_exists($store, 'upsertFieldManagerCustomField')) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Store not available']);
        return;
    }
    $decoded = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($decoded)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid JSON body']);
        return;
    }
    $displayName = sanitizeString((string)($decoded['display_name'] ?? ''), 255);
    $fieldType = strtolower(trim((string)($decoded['field_type'] ?? '')));
    $matchingTag = sanitizeString((string)($decoded['matching_tag'] ?? ''), 255);
    $location = strtolower(trim((string)($decoded['location'] ?? '')));
    $id = sanitizeId((string)($decoded['id'] ?? ''));
    $linkIdHint = preg_replace('/[^a-z0-9_]/', '_', strtolower(trim((string)($decoded['link_id'] ?? ''))));
    if ($id === '' && $linkIdHint !== '' && method_exists($store, 'getFormCustomFields')) {
        foreach ($store->getFormCustomFields() as $candidate) {
            if (strtolower(trim((string)($candidate['location'] ?? ''))) !== $location) {
                continue;
            }
            if (strtolower(trim((string)($candidate['linkId'] ?? ''))) === $linkIdHint) {
                $id = sanitizeId((string)($candidate['id'] ?? ''));
                break;
            }
        }
    }
    if ($displayName === '' || $matchingTag === '' || $fieldType === '' || $location === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'display_name, field_type, matching_tag, and location are required']);
        return;
    }
    if (!in_array($location, ['firm', 'client', 'court', 'case'], true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid location']);
        return;
    }
    if (!in_array($fieldType, fieldManagerAllowedTypes(), true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid field_type']);
        return;
    }
    $dup = fieldManagerFindDuplicateMatchingTag($store, $location, $matchingTag, $id);
    if ($dup !== null) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'error' => 'Another field already uses this matching tag for this section. Choose a different matching tag.',
        ]);
        return;
    }
    if ($id !== '' && method_exists($store, 'getFormCustomFields')) {
        $idFound = false;
        foreach ($store->getFormCustomFields() as $candidate) {
            if ((string)($candidate['id'] ?? '') !== $id) {
                continue;
            }
            $idFound = true;
            $existingLoc = strtolower(trim((string)($candidate['location'] ?? '')));
            if ($existingLoc !== '' && $existingLoc !== $location) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Location does not match this field.']);
                return;
            }
            break;
        }
        if (!$idFound) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Field not found for that id.']);
            return;
        }
    }
    try {
        // Single path for new + updated rows (including protected/system): UPDATE by id preserves link_id and is_system.
        $catalogSampleValue = null;
        if (array_key_exists('sample_text', $decoded)) {
            $catalogSampleValue = sanitizeString((string)($decoded['sample_text'] ?? ''), 8000);
        }
        $row = $store->upsertFieldManagerCustomField($displayName, $fieldType, $matchingTag, $location, $id, $catalogSampleValue);
        echo json_encode([
            'success' => true,
            'field' => $row,
            'fieldTypes' => fieldManagerAllowedTypes(),
            'fieldsByLocation' => groupedFieldManagerFields(),
        ], JSON_UNESCAPED_SLASHES);
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function handleFieldManagerDeleteField(): void {
    global $store;
    header('Content-Type: application/json; charset=utf-8');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        return;
    }
    if (!$store || !method_exists($store, 'deleteFieldManagerCustomField')) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Store not available']);
        return;
    }
    $decoded = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($decoded)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid JSON body']);
        return;
    }
    $id = sanitizeId((string)($decoded['id'] ?? ''));
    if ($id === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'id is required']);
        return;
    }
    if (method_exists($store, 'getFormCustomFields')) {
        $found = null;
        foreach ($store->getFormCustomFields() as $row) {
            if ((string)($row['id'] ?? '') === $id) {
                $found = $row;
                break;
            }
        }
        if ($found === null) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Field not found.',
                'fieldTypes' => fieldManagerAllowedTypes(),
                'fieldsByLocation' => groupedFieldManagerFields(),
            ], JSON_UNESCAPED_SLASHES);
            return;
        }
        if (!empty($found['isSystem'])) {
            http_response_code(409);
            echo json_encode([
                'success' => false,
                'error' => 'This field is protected and cannot be deleted.',
                'fieldTypes' => fieldManagerAllowedTypes(),
                'fieldsByLocation' => groupedFieldManagerFields(),
            ], JSON_UNESCAPED_SLASHES);
            return;
        }
    }
    try {
        $deleted = $store->deleteFieldManagerCustomField($id);
        if (!$deleted) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Field not found.',
                'fieldTypes' => fieldManagerAllowedTypes(),
                'fieldsByLocation' => groupedFieldManagerFields(),
            ], JSON_UNESCAPED_SLASHES);
            return;
        }
        echo json_encode([
            'success' => true,
            'fieldTypes' => fieldManagerAllowedTypes(),
            'fieldsByLocation' => groupedFieldManagerFields(),
        ], JSON_UNESCAPED_SLASHES);
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

/**
 * Diagnostic endpoint: GET ?route=api/field-manager/diag&id=<fcf_id>
 * Returns whether MySQL is being used by DataStore, the entity row counts (when PDO is connected),
 * and — if id is provided — both the raw SELECT row and the row as exposed by getFormCustomFields().
 * The two should agree on display_name / updated_at; if they diverge we know reads vs writes are split.
 */
function handleFieldManagerDiag(): void {
    global $store;
    header('Content-Type: application/json; charset=utf-8');
    $id = sanitizeId((string)($_GET['id'] ?? ''));
    $out = [
        'success' => true,
        'pdoConnected' => false,
        'database' => null,
        'counts' => null,
        'storeClass' => $store ? get_class($store) : null,
    ];
    try {
        if (function_exists('wpts_mysql_params')) {
            $p = wpts_mysql_params();
            $out['mysqlParams'] = [
                'host' => $p['host'],
                'port' => $p['port'],
                'database' => $p['database'],
                'user' => $p['user'],
                'disabled' => $p['disabled'],
                'incomplete' => $p['incomplete'],
                'passwordSet' => $p['password'] !== '',
            ];
            $out['database'] = $p['database'];
            $envEnabled = getenv('DB_ENABLED');
            $envRequired = getenv('DB_REQUIRED');
            $out['env'] = [
                'DB_HOST' => getenv('DB_HOST') !== false ? getenv('DB_HOST') : null,
                'DB_PORT' => getenv('DB_PORT') !== false ? getenv('DB_PORT') : null,
                'DB_NAME' => getenv('DB_NAME') !== false ? getenv('DB_NAME') : null,
                'DB_USER' => getenv('DB_USER') !== false ? getenv('DB_USER') : null,
                'DB_PASSWORD_SET' => getenv('DB_PASSWORD') !== false && getenv('DB_PASSWORD') !== '',
                'DB_ENABLED' => $envEnabled !== false ? $envEnabled : null,
                'DB_REQUIRED' => $envRequired !== false ? $envRequired : null,
            ];
            // Live PDO probes (independent of DataStore's cached state). Try the configured TCP target,
            // common alt TCP endpoints, and standard Unix socket paths so we can see which one (if any)
            // the production MariaDB actually accepts.
            $pdoOpts = [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_TIMEOUT => 3,
            ];
            $candidates = [];
            if (!$p['disabled'] && !$p['incomplete']) {
                $candidates['configured'] = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $p['host'], $p['port'], $p['database']);
                foreach (['localhost', '127.0.0.1', 'MySQL', 'mysql', 'mariadb', 'db'] as $hostAlias) {
                    $candidates['host:' . $hostAlias] = sprintf(
                        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                        $hostAlias,
                        (int)$p['port'] > 0 ? (int)$p['port'] : 3306,
                        $p['database']
                    );
                }
                foreach ([
                    (string)ini_get('pdo_mysql.default_socket'),
                    (string)ini_get('mysqli.default_socket'),
                    '/var/run/mysqld/mysqld.sock',
                    '/var/lib/mysql/mysql.sock',
                    '/tmp/mysql.sock',
                    '/run/mysqld/mysqld.sock',
                    '/var/run/mysql/mysql.sock',
                ] as $sock) {
                    $sock = trim((string)$sock);
                    if ($sock === '') {
                        continue;
                    }
                    $candidates['socket:' . $sock] = sprintf('mysql:unix_socket=%s;dbname=%s;charset=utf8mb4', $sock, $p['database']);
                }
            }
            $pdoProbes = [];
            foreach ($candidates as $label => $dsn) {
                $probeRes = ['dsn' => $dsn, 'connected' => false, 'error' => null];
                try {
                    $probe = new \PDO($dsn, $p['user'], $p['password'], $pdoOpts);
                    $probeRes['connected'] = true;
                    $probeRes['serverVersion'] = (string)$probe->getAttribute(\PDO::ATTR_SERVER_VERSION);
                    unset($probe);
                } catch (\Throwable $e) {
                    $probeRes['error'] = $e->getMessage();
                }
                $pdoProbes[$label] = $probeRes;
            }
            // Search the filesystem for any *.sock/*.mysql clues PHP can read.
            $socketHints = [];
            foreach ([
                '/var/run/mysqld', '/var/lib/mysql', '/tmp', '/run/mysqld', '/var/run/mysql',
                '/run', '/var/run', '/var/lib/mariadb', '/var/run/mariadb', '/run/mariadb',
                '/home/jjames', '/home/jjames/tmp',
            ] as $dir) {
                if (is_dir($dir)) {
                    foreach (@scandir($dir) ?: [] as $entry) {
                        if (in_array($entry, ['.', '..'], true)) continue;
                        if (preg_match('/sock|mysql|maria/i', (string)$entry)) {
                            $full = rtrim($dir, '/') . '/' . $entry;
                            $socketHints[] = $full . (is_dir($full) ? ' (dir)' : '');
                        }
                    }
                }
            }
            // Read MariaDB/MySQL config files for socket/bind-address/port hints.
            $cfgHints = [];
            $cfgFiles = [
                '/etc/my.cnf', '/etc/mysql/my.cnf', '/etc/mysql/mariadb.cnf',
                '/etc/my.cnf.d/server.cnf', '/etc/my.cnf.d/mariadb-server.cnf',
                '/etc/mysql/conf.d/mysql.cnf', '/etc/mysql/mariadb.conf.d/50-server.cnf',
                getenv('HOME') ? rtrim((string)getenv('HOME'), '/') . '/.my.cnf' : null,
                '/home/jjames/.my.cnf',
                '/var/web/desktopmasters.com/.my.cnf',
                '/var/web/desktopmasters.com/pdftimesaver/.my.cnf',
            ];
            foreach (array_filter(array_unique($cfgFiles)) as $cfg) {
                if (!is_file($cfg) || !is_readable($cfg)) {
                    continue;
                }
                $body = (string)@file_get_contents($cfg);
                if ($body === '') {
                    continue;
                }
                $matches = [];
                if (preg_match_all('/^\s*(socket|bind-address|port|host)\s*=\s*(\S+)/mi', $body, $m)) {
                    foreach ($m[0] as $i => $line) {
                        $matches[] = trim($line);
                    }
                }
                $cfgHints[$cfg] = $matches !== [] ? $matches : '(file readable but no socket/host/port lines)';
            }
            $firstConnected = null;
            foreach ($pdoProbes as $label => $probeRes) {
                if (!empty($probeRes['connected'])) {
                    $firstConnected = $label;
                    break;
                }
            }
            $out['pdoProbes'] = $pdoProbes;
            $out['socketCandidatesOnDisk'] = $socketHints;
            $out['mariadbConfigHints'] = $cfgHints;
            $out['phpHome'] = getenv('HOME') ?: null;
            $out['pdoProbe'] = $firstConnected !== null
                ? ($pdoProbes[$firstConnected] ?? null)
                : ($pdoProbes['configured'] ?? null);
        }
        if ($store && method_exists($store, 'getDataPath')) {
            $path = $store->getDataPath();
            $exists = is_file($path);
            $out['dataFile'] = [
                'path' => $path,
                'exists' => $exists,
                'readable' => $exists ? is_readable($path) : null,
                'writable' => $exists ? is_writable($path) : null,
                'sizeBytes' => $exists ? @filesize($path) : null,
                'mtime' => $exists ? @date(DATE_ATOM, (int)@filemtime($path)) : null,
                'parentWritable' => is_writable(dirname($path)),
                'phpUser' => function_exists('posix_getpwuid') && function_exists('posix_geteuid')
                    ? (posix_getpwuid(posix_geteuid())['name'] ?? null)
                    : (function_exists('get_current_user') ? get_current_user() : null),
            ];
        }
        if ($store && method_exists($store, 'getMvpEntityCountsFromDb')) {
            $counts = $store->getMvpEntityCountsFromDb();
            $out['pdoConnected'] = $counts !== null;
            $out['counts'] = $counts;
        }
        if ($store && method_exists($store, 'diagnoseFormCustomFieldRow') && $id !== '') {
            $out['rowById'] = $store->diagnoseFormCustomFieldRow($id);
        }
        if ($store && method_exists($store, 'getFormCustomFields')) {
            if ($id !== '') {
                $matched = null;
                foreach ($store->getFormCustomFields() as $row) {
                    if ((string)($row['id'] ?? '') === $id) {
                        $matched = $row;
                        break;
                    }
                }
                $out['rowFromGetFormCustomFields'] = $matched;
            }
        }
        echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

/**
 * @return array{allowedCustomIds: array<int, string>, allowedSystemIds: array<int, string>, checkboxIds: array<int, string>}
 */
function clientCustomFieldPermissions(): array {
    global $store;
    $allowedSystemLinks = [
        'client_full_name',
        'client_first_name',
        'client_middle_name',
        'client_last_name',
        'client_city',
        'client_state',
        'client_zip',
    ];
    $allowedCustomIds = [];
    $allowedSystemIds = [];
    $checkboxIds = [];
    $fieldRows = method_exists($store, 'getFieldManagerCustomFields') ? $store->getFieldManagerCustomFields('client') : [];
    foreach ($fieldRows as $fieldRow) {
        $fieldId = sanitizeId((string)($fieldRow['id'] ?? ''));
        if ($fieldId === '') {
            continue;
        }
        $fieldType = strtolower(trim((string)($fieldRow['fieldType'] ?? 'text')));
        if ($fieldType === 'checkbox') {
            $checkboxIds[] = $fieldId;
        }
        if (!empty($fieldRow['isSystem'])) {
            $linkId = strtolower(trim((string)($fieldRow['linkId'] ?? '')));
            if (in_array($linkId, $allowedSystemLinks, true)) {
                $allowedSystemIds[] = $fieldId;
            }
            continue;
        }
        $allowedCustomIds[] = $fieldId;
    }
    return [
        'allowedCustomIds' => array_values(array_unique($allowedCustomIds)),
        'allowedSystemIds' => array_values(array_unique($allowedSystemIds)),
        'checkboxIds' => array_values(array_unique($checkboxIds)),
    ];
}

function normalizePostedClientFieldValues(array $source, array $allowedIds, array $checkboxIds): array {
    $out = [];
    foreach ($allowedIds as $allowedId) {
        $id = sanitizeId((string)$allowedId);
        if ($id === '') {
            continue;
        }
        if (array_key_exists($id, $source)) {
            $out[$id] = sanitizeString((string)$source[$id], 8000);
            continue;
        }
        if (in_array($id, $checkboxIds, true)) {
            $out[$id] = '';
        }
    }
    return $out;
}

function normalizeClientDisplayNameKey(string $value): string {
    $collapsed = preg_replace('/\s+/', ' ', $value);
    return strtolower(trim((string)($collapsed ?? $value)));
}

/**
 * @param object $store Datastore with getClients()
 * @return array{id: string, displayName: string}|null
 */
function findOtherClientWithNormalizedDisplayName($store, string $excludeClientId, string $displayName): ?array {
    if ($excludeClientId === '' || !method_exists($store, 'getClients')) {
        return null;
    }
    $target = normalizeClientDisplayNameKey($displayName);
    if ($target === '') {
        return null;
    }
    foreach ((array)$store->getClients() as $row) {
        if (!is_array($row)) {
            continue;
        }
        $id = (string)($row['id'] ?? '');
        if ($id === '' || $id === $excludeClientId) {
            continue;
        }
        $other = normalizeClientDisplayNameKey((string)($row['displayName'] ?? ''));
        if ($other === $target) {
            return ['id' => $id, 'displayName' => (string)($row['displayName'] ?? '')];
        }
    }
    return null;
}

function persistClientProfileAndFields(string $clientId, string $displayName, string $email, string $phone, string $company, string $address, string $notes, array $customFieldsInput = [], array $systemFieldsInput = []): bool {
    global $store;
    if ($clientId === '' || !method_exists($store, 'updateClient')) {
        return false;
    }
    $updated = $store->updateClient($clientId, $displayName, $email, $phone, $company, $address, $notes);
    if (!$updated) {
        return false;
    }
    if (method_exists($store, 'saveClientCustomFieldValues')) {
        $perm = clientCustomFieldPermissions();
        $customValues = normalizePostedClientFieldValues(
            is_array($customFieldsInput) ? $customFieldsInput : [],
            $perm['allowedCustomIds'],
            $perm['checkboxIds']
        );
        $systemValues = normalizePostedClientFieldValues(
            is_array($systemFieldsInput) ? $systemFieldsInput : [],
            $perm['allowedSystemIds'],
            $perm['checkboxIds']
        );
        $existing = method_exists($store, 'getClientCustomFieldValues')
            ? $store->getClientCustomFieldValues($clientId)
            : [];
        $merged = is_array($existing) ? $existing : [];
        foreach (array_merge($customValues, $systemValues) as $fieldId => $value) {
            $merged[$fieldId] = (string)$value;
        }
        $store->saveClientCustomFieldValues($clientId, $merged);
    }
    return true;
}

function handleClientProfileAutosave(): void {
    global $store;
    header('Content-Type: application/json; charset=utf-8');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        return;
    }
    $decoded = json_decode((string)file_get_contents('php://input'), true);
    if (!is_array($decoded)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid JSON body']);
        return;
    }
    $clientId = sanitizeId((string)($decoded['clientId'] ?? ''));
    $displayName = sanitizeString((string)($decoded['displayName'] ?? ''), 200);
    $email = validateEmail((string)($decoded['email'] ?? ''));
    $phone = validatePhone((string)($decoded['phone'] ?? ''));
    $company = sanitizeString((string)($decoded['company'] ?? ''), 200);
    $address = sanitizeString((string)($decoded['address'] ?? ''), 300);
    $notes = sanitizeString((string)($decoded['notes'] ?? ''), 1000);
    if ($clientId === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'clientId is required.']);
        return;
    }
    if ($displayName === '' && $company !== '') {
        $displayName = $company;
    }
    if ($displayName === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Enter a display name or company name.']);
        return;
    }
    $allowDuplicate = filter_var($decoded['allowDuplicateDisplayName'] ?? false, FILTER_VALIDATE_BOOLEAN);
    if (!$allowDuplicate) {
        $dup = findOtherClientWithNormalizedDisplayName($store, $clientId, $displayName);
        if ($dup !== null) {
            http_response_code(409);
            echo json_encode([
                'success' => false,
                'code' => 'duplicate_display_name',
                'error' => 'This client name already exists. Are you sure you want more than one client with this name?',
            ], JSON_UNESCAPED_SLASHES);
            return;
        }
    }
    $ok = persistClientProfileAndFields(
        $clientId,
        $displayName,
        $email,
        $phone,
        $company,
        $address,
        $notes,
        is_array($decoded['customFields'] ?? null) ? $decoded['customFields'] : [],
        is_array($decoded['systemClientFields'] ?? null) ? $decoded['systemClientFields'] : []
    );
    if (!$ok) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to save client profile.']);
        return;
    }
    echo json_encode([
        'success' => true,
        'client' => method_exists($store, 'getClient') ? $store->getClient($clientId) : null,
    ], JSON_UNESCAPED_SLASHES);
}

function handlePositionUpdate(): void {
    global $store, $logger;
    header('Content-Type: application/json');
    $requestId = 'pu_' . date('Ymd_His') . '_' . substr(md5((string)microtime(true) . (string)mt_rand()), 0, 8);
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }
    
    $input = file_get_contents('php://input');
    $decoded = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        logUniversalGenerateEvent($logger ?? null, 'error', 'positions-update-invalid-json', [
            'requestId' => $requestId,
            'jsonError' => json_last_error_msg(),
            'inputBytes' => strlen((string)$input),
        ]);
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON', 'requestId' => $requestId]);
        return;
    }
    
    $templateId = '';
    $positions = null;
    $firmId = resolveCurrentFirmId();
    $formNumber = '';
    $formName = '';
    /** null = client omitted key; string = explicit value (may be empty) */
    $formLocationIn = null;
    if (is_array($decoded) && isset($decoded['positions']) && is_array($decoded['positions'])) {
        $positions = $decoded['positions'];
        $tid = trim((string)($decoded['template_id'] ?? ''));
        if ($tid !== '') {
            $templateId = sanitizeId($tid);
        }
        $fid = trim((string)($decoded['firm_id'] ?? ''));
        if ($fid !== '') {
            $san = sanitizeId($fid);
            if ($san !== '') {
                $firmId = $san;
            }
        }
        $formNumber = strtoupper(trim((string)($decoded['form_number'] ?? '')));
        $formName = trim((string)($decoded['form_name'] ?? ''));
        if (array_key_exists('form_location', $decoded)) {
            $formLocationIn = trim((string)$decoded['form_location']);
        }
    }
    
    if ($templateId === '' || !is_array($positions)) {
        logUniversalGenerateEvent($logger ?? null, 'error', 'positions-update-invalid-body', [
            'requestId' => $requestId,
            'templateId' => $templateId,
            'hasPositions' => is_array($positions),
        ]);
        http_response_code(400);
        echo json_encode(['error' => 'Body must be JSON object: {template_id, positions}', 'requestId' => $requestId]);
        return;
    }
    
    try {
        logUniversalGenerateEvent($logger ?? null, 'info', 'positions-update-start', [
            'requestId' => $requestId,
            'templateId' => $templateId,
            'firmId' => $firmId,
            'inputBytes' => strlen((string)$input),
            'positionCountIncoming' => count($positions),
        ]);
        $dataDir = realpath(__DIR__ . '/../data');
        if ($dataDir === false) {
            throw new \RuntimeException('Data directory not found');
        }
        $positionsFile = $dataDir . DIRECTORY_SEPARATOR . $templateId . '_positions.json';
        $normalizedPositions = normalizePositionsFieldMap($positions);
        $encodedPositions = json_encode($normalizedPositions, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($encodedPositions === false) {
            throw new \RuntimeException('Failed to encode positions JSON: ' . json_last_error_msg());
        }
        
        // Backup current positions
        if (file_exists($positionsFile)) {
            $backupFile = $positionsFile . '.backup.' . date('Y-m-d_H-i-s');
            copy($positionsFile, $backupFile);
        }
        
        // Save new positions
        $result = file_put_contents($positionsFile, $encodedPositions);
        
        if ($result === false) {
            throw new Exception('Failed to write positions file');
        }
        
        // Log the update
        $logger = new \WebPdfTimeSaver\Mvp\Logger();
        $logger->info('Position update', [
            'templateId' => $templateId,
            'fieldCount' => count($normalizedPositions),
            'timestamp' => date('Y-m-d H:i:s')
        ]);

        if ($store && method_exists($store, 'saveFirmFieldAlignment')) {
            $store->saveFirmFieldAlignment($firmId, $templateId, $normalizedPositions);
        }
        $existingRegistry = ($store && method_exists($store, 'getGlobalFormTemplate'))
            ? $store->getGlobalFormTemplate($templateId)
            : null;
        $locToSave = '';
        if ($formLocationIn !== null) {
            $locToSave = $formLocationIn;
        } elseif (is_array($existingRegistry)) {
            $locToSave = trim((string)($existingRegistry['formLocation'] ?? ''));
        }
        if (is_array($existingRegistry) && $store && method_exists($store, 'upsertGlobalFormTemplate') && method_exists($store, 'getGlobalFormTemplate')) {
            $existing = $existingRegistry;
            $sourceFileName = is_array($existing) ? (string)($existing['sourceFileName'] ?? '') : '';
            $detectedFirmName = is_array($existing) ? (string)($existing['detectedFirmName'] ?? '') : '';
            $seedName = $formName !== '' ? $formName : (is_array($existing) ? (string)($existing['formName'] ?? '') : '');
            $resolvedIdentity = resolveFormIdentityForStorage($templateId, trim($seedName), trim($sourceFileName), $formNumber);
            $resolvedName = (string)($resolvedIdentity['combinedName'] ?? $templateId);
            $store->upsertGlobalFormTemplate($templateId, $resolvedName, $sourceFileName, $detectedFirmName, $locToSave);
            $formNumber = (string)($resolvedIdentity['formNumber'] ?? $formNumber);
            $formName = (string)($resolvedIdentity['formName'] ?? $formName);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Positions updated successfully',
            'template_id' => $templateId,
            'firm_id' => $firmId,
            'fieldCount' => count($normalizedPositions),
            'timestamp' => date('Y-m-d H:i:s'),
            'requestId' => $requestId,
            'form_number' => $formNumber,
            'form_name' => $formName,
            'form_location' => $formLocationIn !== null ? $formLocationIn : $locToSave,
        ]);
        
    } catch (\Throwable $e) {
        logUniversalGenerateEvent($logger ?? null, 'error', 'positions-update-failed', [
            'requestId' => $requestId,
            'templateId' => $templateId,
            'firmId' => $firmId,
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'traceTop' => substr((string)$e->getTraceAsString(), 0, 2000),
        ]);
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage(), 'requestId' => $requestId]);
    }
}

// Seed demo data for easy navigation when empty
try {
    $needsSeed = count($store->getProjects()) === 0;
} catch (\Throwable $e) { $needsSeed = false; }
if ($needsSeed) {
    // Create a demo client and project with one document
    $client = method_exists($store, 'createClient') ? $store->createClient('John Doe', 'john@example.com', '(555) 123-4567') : null;
    $proj = $store->createProject('BHBA EVENT (JOHN DOE)');
    $tplId = array_key_first($templates);
    if ($tplId) { $doc = $store->addProjectDocument($proj['id'], (string)$tplId); }
}

switch ($route) {
case 'dashboard':
	$projects = $store->getProjects();
	$clients = method_exists($store, 'getClients') ? $store->getClients() : [];
	$recentDocuments = [];
	foreach ($projects as $project) {
		$projectId = sanitizeId((string)($project['id'] ?? ''));
		$cfg = [];
		if ($projectId !== '' && method_exists($store, 'getProjectViewConfig')) {
			$cfg = (array)$store->getProjectViewConfig($projectId);
			pruneDuplicateFamilyProjectDocuments($store, $projectId, (array)($cfg['templateOrder'] ?? []));
		}
		$docs = $store->getProjectDocuments($project['id']);
		$templateOrder = dedupeTemplateIdsByFamily((array)($cfg['templateOrder'] ?? []));
		$orderFamilies = [];
		foreach ($templateOrder as $orderTid) {
			$orderFamily = resolveFormTemplateFamily((string)$orderTid);
			if ($orderFamily !== '') {
				$orderFamilies[$orderFamily] = true;
			}
		}
		$seenFamilies = [];
		foreach ($docs as $doc) {
			$tid = sanitizeId((string)($doc['templateId'] ?? ''));
			$family = resolveFormTemplateFamily($tid);
			if ($templateOrder !== [] && $family !== '' && !isset($orderFamilies[$family])) {
				continue;
			}
			if ($family !== '' && isset($seenFamilies[$family])) {
				continue;
			}
			if ($family !== '') {
				$seenFamilies[$family] = true;
			}
			$doc['project'] = $project;
			$recentDocuments[] = $doc;
		}
	}
	usort($recentDocuments, function($a, $b) {
		return strtotime($b['createdAt'] ?? '') <=> strtotime($a['createdAt'] ?? '');
	});
	$recentDocuments = array_slice($recentDocuments, 0, 5);
	render('dashboard', [ 'projects' => $projects, 'clients' => $clients, 'recentDocuments' => $recentDocuments, 'templates' => $templates ]);
	break;

case 'projects':
    $projects = $store->getProjects();
    $q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
    $status = strtolower(trim((string)($_GET['status'] ?? 'active')));
    if (!in_array($status, ['active', 'completed', 'all'], true)) {
        $status = 'active';
    }
    $sort = isset($_GET['sort']) ? (string)$_GET['sort'] : 'updated_desc';
    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = 20;

    $projects = array_values(array_filter($projects, static function($p) use ($status): bool {
        if ($status === 'all') {
            return true;
        }
        $raw = strtolower(trim((string)($p['status'] ?? 'in_progress')));
        $isCompleted = strpos($raw, 'complete') !== false;
        return $status === 'completed' ? $isCompleted : !$isCompleted;
    }));

    if ($q !== '') {
        $projects = array_values(array_filter($projects, function($p) use ($q) {
            return stripos($p['name'] ?? '', $q) !== false;
        }));
    }
    usort($projects, function($a, $b) use ($sort) {
        $an = strtolower($a['name'] ?? '');
        $bn = strtolower($b['name'] ?? '');
        $au = strtotime($a['updatedAt'] ?? $a['createdAt'] ?? 'now');
        $bu = strtotime($b['updatedAt'] ?? $b['createdAt'] ?? 'now');
        switch ($sort) {
            case 'name_asc': return $an <=> $bn;
            case 'name_desc': return $bn <=> $an;
            case 'updated_asc': return $au <=> $bu;
            case 'updated_desc': default: return $bu <=> $au;
        }
    });
    $totalFiltered = count($projects);
    $totalPages = max(1, (int)ceil($totalFiltered / $perPage));
    if ($page > $totalPages) {
        $page = $totalPages;
    }
    $offset = ($page - 1) * $perPage;
    $projectsPage = array_slice($projects, $offset, $perPage);

    render('projects', [
        'projects' => $projectsPage,
        'filters' => [ 'q' => $q, 'status' => $status, 'sort' => $sort ],
        'totalProjectsFiltered' => $totalFiltered,
        'pagination' => [
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
            'totalItems' => $totalFiltered,
        ],
    ]);
    break;

	case 'clients':
		$clients = method_exists($store, 'getClients') ? $store->getClients() : [];
		render('clients', [ 'clients' => $clients ]);
		break;

	case 'client-data':
		header('Location: ?route=clients');
		exit;


	case 'client':
		$cid = sanitizeId((string)($_GET['id'] ?? ''));
		$client = method_exists($store, 'getClient') ? $store->getClient($cid) : null;
		if (!$client) { header('Location: ?route=clients'); exit; }
		$projects = method_exists($store, 'getProjectsByClient') ? $store->getProjectsByClient($cid) : [];
		$clientFieldRows = method_exists($store, 'getFieldManagerCustomFields') ? $store->getFieldManagerCustomFields('client') : [];
		$clientCustomFieldValues = method_exists($store, 'getClientCustomFieldValues') ? $store->getClientCustomFieldValues($cid) : [];
		render('client', [
			'client' => $client,
			'projects' => $projects,
			'templates' => $templates,
			'clientFieldRows' => $clientFieldRows,
			'clientCustomFieldValues' => $clientCustomFieldValues
		]);
		break;

	case 'client-mapping':
		$cid = sanitizeId((string)($_GET['id'] ?? ''));
		$templateId = sanitizeId((string)($_GET['templateId'] ?? ''));
		$client = method_exists($store, 'getClient') ? $store->getClient($cid) : null;
		if (!$client) { header('Location: ?route=clients'); exit; }
		$availableTemplates = method_exists($store, 'filterTemplatesByClientVisibility')
			? $store->filterTemplatesByClientVisibility($templates, $cid)
			: $templates;
		if ($templateId === '' && !empty($availableTemplates)) {
			$templateId = (string)array_key_first($availableTemplates);
		}
		$template = ['fields' => $templateId !== '' ? loadTemplateFieldKeysFast($templateId) : []];
		$mapping = ($templateId !== '' && method_exists($store, 'getClientFieldMapping'))
			? $store->getClientFieldMapping($cid, $templateId)
			: [];
		render('client_mapping', [
			'client' => $client,
			'templateId' => $templateId,
			'availableTemplates' => $availableTemplates,
			'template' => $template,
			'mapping' => $mapping,
		]);
		break;

	case 'project':
		$id = sanitizeId((string)($_GET['id'] ?? ''));
		$project = $store->getProject($id);
		if (!$project) {
			header('HTTP/1.1 404 Not Found');
			echo 'Project not found';
			exit;
		}
		$docs = $store->getProjectDocuments($id);
		usort($docs, function($a, $b) { return strtotime($b['createdAt'] ?? '') <=> strtotime($a['createdAt'] ?? ''); });
		$allClients = method_exists($store, 'getClients') ? $store->getClients() : [];
		$projectTemplates = $templates;
		if (method_exists($store, 'filterTemplatesByClientVisibility')) {
			$projectTemplates = $store->filterTemplatesByClientVisibility($templates, $project['clientId'] ?? null);
		}
		$projectViewConfig = method_exists($store, 'getProjectViewConfig') ? $store->getProjectViewConfig($id) : [];
		$prunedOrder = pruneDuplicateFamilyProjectDocuments(
			$store,
			$id,
			is_array($projectViewConfig['templateOrder'] ?? null) ? (array)$projectViewConfig['templateOrder'] : []
		);
		if ($prunedOrder !== [] && is_array($projectViewConfig)) {
			$projectViewConfig['templateOrder'] = $prunedOrder;
		}
		$formSets = method_exists($store, 'getGlobalFormSets') ? $store->getGlobalFormSets() : [];
		$globalFormsRaw = method_exists($store, 'getGlobalFormTemplates') ? $store->getGlobalFormTemplates() : [];
		$globalForms = [];
		$seenFormFamilies = [];
		foreach ($globalFormsRaw as $row) {
			if (!is_array($row)) {
				continue;
			}
			$tid = sanitizeId((string)($row['templateId'] ?? ''));
			if ($tid === '') {
				continue;
			}
			$family = resolveFormTemplateFamily($tid);
			if ($family !== '') {
				if (isset($seenFormFamilies[$family])) {
					$existingTid = $seenFormFamilies[$family];
					$keepTid = preferCanonicalTemplateId($existingTid, $tid);
					if ($keepTid === $existingTid) {
						continue;
					}
					foreach ($globalForms as $idx => $existingRow) {
						if (sanitizeId((string)($existingRow['templateId'] ?? '')) === $existingTid) {
							$globalForms[$idx] = $row;
							break;
						}
					}
					$seenFormFamilies[$family] = $keepTid;
					continue;
				}
				$seenFormFamilies[$family] = $tid;
			}
			$globalForms[] = $row;
		}
		$clientFieldRows = method_exists($store, 'getFieldManagerCustomFields') ? $store->getFieldManagerCustomFields('client') : [];
		$attorneyFieldRows = method_exists($store, 'getFieldManagerCustomFields') ? $store->getFieldManagerCustomFields('attorney') : [];
		$attorneyRoster = [];
		if (method_exists($store, 'getAttorneys')) {
			$attorneyRoster = array_map(static function (array $row) use ($attorneyFieldRows): array {
				return formatAttorneyApiRow($row, $attorneyFieldRows);
			}, (array)$store->getAttorneys());
		}
		$courtFieldRows = method_exists($store, 'getFieldManagerCustomFields') ? $store->getFieldManagerCustomFields('court') : [];
		$caseFieldRows = method_exists($store, 'getFieldManagerCustomFields') ? $store->getFieldManagerCustomFields('case') : [];
		$caseLibrary = [];
		if (method_exists($store, 'getProjects') && method_exists($store, 'getProjectViewConfig')) {
			$allProjects = (array)$store->getProjects();
			foreach ($allProjects as $p) {
				if (!is_array($p)) {
					continue;
				}
				$pid = sanitizeId((string)($p['id'] ?? ''));
				if ($pid === '' || $pid === $id) {
					continue;
				}
				$cfg = (array)$store->getProjectViewConfig($pid);
				$caseNo = trim((string)($cfg['caseNumber'] ?? ''));
				if ($caseNo === '') {
					continue;
				}
				$caseValues = is_array($cfg['caseValues'] ?? null) ? $cfg['caseValues'] : [];
				$normalizedCaseValues = [];
				foreach ($caseValues as $k => $v) {
					$key = sanitizeId((string)$k);
					if ($key === '') {
						continue;
					}
					$normalizedCaseValues[$key] = sanitizeString((string)$v, 500);
				}
				$caseLibrary[] = [
					'projectId' => $pid,
					'projectName' => sanitizeString((string)($p['name'] ?? 'Project'), 200),
					'caseNumber' => sanitizeString($caseNo, 255),
					'caseValues' => $normalizedCaseValues,
				];
			}
		}
		render('project', [
			'project' => $project,
			'documents' => $docs,
			'templates' => $projectTemplates,
			'clients' => $allClients,
			'projectViewConfig' => $projectViewConfig,
			'formSets' => $formSets,
			'globalForms' => $globalForms,
			'clientFieldRows' => $clientFieldRows,
			'attorneyFieldRows' => $attorneyFieldRows,
			'attorneyRoster' => $attorneyRoster,
			'courtFieldRows' => $courtFieldRows,
			'caseFieldRows' => $caseFieldRows,
			'caseLibrary' => $caseLibrary,
			'courtSourceMeta' => courtDirectorySourceMeta(),
		]);
		break;

	case 'actions/update-project-status':
		$isAjax = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
		// Where to send the user back to after a normal (non-AJAX) form submit.
		$refererRaw = (string)($_SERVER['HTTP_REFERER'] ?? '');
		$backTo = '?route=projects';
		if ($refererRaw !== '') {
			$qPos = strpos($refererRaw, '?');
			$query = $qPos !== false ? substr($refererRaw, $qPos) : '';
			if (strpos($query, 'route=projects') !== false) {
				$backTo = $query;
			}
		}
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			if ($isAjax) { http_response_code(405); echo json_encode(['error' => 'Method not allowed']); exit; }
			header('Location: ' . $backTo);
			exit;
		}
		$id = (string)($_POST['id'] ?? '');
		$status = (string)($_POST['status'] ?? 'in_progress');

		// Validate status
		$validStatuses = ['in_progress', 'review', 'completed'];
		if (!in_array($status, $validStatuses)) {
			if ($isAjax) { http_response_code(400); echo json_encode(['error' => 'Invalid status']); exit; }
			header('Location: ' . $backTo . (strpos($backTo, '?') === 0 ? '&' : '?') . 'error=' . urlencode('Invalid status.'));
			exit;
		}

		$updated = method_exists($store, 'updateProjectStatus')
			? $store->updateProjectStatus($id, $status)
			: false;

		if ($isAjax) {
			if ($updated) {
				header('Content-Type: application/json');
				echo json_encode(['success' => true, 'status' => $status]);
			} else {
				http_response_code(404);
				echo json_encode(['error' => 'Project not found']);
			}
			exit;
		}

		$sep = (strpos($backTo, '?') === 0) ? '&' : '?';
		if ($updated) {
			$label = $status === 'completed' ? 'Project marked complete.' : 'Project reopened.';
			header('Location: ' . $backTo . $sep . 'success=' . urlencode($label));
		} else {
			header('Location: ' . $backTo . $sep . 'error=' . urlencode('Project not found.'));
		}
		exit;

	case 'populate':
        $logFile = __DIR__ . '/../logs/pdf_debug.log';
        $pdId = sanitizeId((string)($_GET['pd'] ?? ''));
        if ($isDebug) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' POPULATE: Accessing populate form for PD ID: ' . $pdId . PHP_EOL, FILE_APPEND);
        }
		
		$projDoc = $store->getProjectDocumentById($pdId);
        if (!$projDoc) {
            if ($isDebug) {
                file_put_contents($logFile, date('Y-m-d H:i:s') . ' POPULATE: Project document not found' . PHP_EOL, FILE_APPEND);
            }
			header('HTTP/1.1 404 Not Found');
			echo 'Document not found';
			exit;
		}
		
        // Load template dynamically from PDF field extraction
        $template = TemplateRegistry::getTemplate($projDoc['templateId']);
        if ($isDebug) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' POPULATE: Template ID: ' . ($projDoc['templateId'] ?? 'NONE') . PHP_EOL, FILE_APPEND);
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' POPULATE: Template loaded dynamically: ' . ($template ? 'YES' : 'NO') . PHP_EOL, FILE_APPEND);
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' POPULATE: Template fields count: ' . count($template['fields'] ?? []) . PHP_EOL, FILE_APPEND);
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' POPULATE: Template panels count: ' . count($template['panels'] ?? []) . PHP_EOL, FILE_APPEND);
        }
		
        $values = $store->getFieldValues($pdId);

        // Autofill from Firm / client / case / court; project-caption fields always refresh.
        $values = mergeFieldValuesWithAutofill($store, $projDoc, $template ?: [], $values);

		$projectViewConfig = method_exists($store, 'getProjectViewConfig')
			? (array)$store->getProjectViewConfig((string)$projDoc['projectId'])
			: [];
		$templateOrder = [];
		foreach ((array)($projectViewConfig['templateOrder'] ?? []) as $value) {
			$tid = sanitizeId((string)$value);
			if ($tid !== '' && !in_array($tid, $templateOrder, true)) {
				$templateOrder[] = $tid;
			}
		}
		$templateOrder = pruneDuplicateFamilyProjectDocuments($store, (string)$projDoc['projectId'], $templateOrder);
		$projectDocuments = $store->getProjectDocuments((string)$projDoc['projectId']);
		$docsByTemplate = [];
		foreach ($projectDocuments as $doc) {
			if (!is_array($doc)) {
				continue;
			}
			$tid = sanitizeId((string)($doc['templateId'] ?? ''));
			if ($tid === '') {
				continue;
			}
			if (!isset($docsByTemplate[$tid])) {
				$docsByTemplate[$tid] = [];
			}
			$docsByTemplate[$tid][] = $doc;
		}
		$orderedProjectDocuments = [];
		$usedDocIds = [];
		$usedFamilies = [];
		foreach ($templateOrder as $tid) {
			foreach ((array)($docsByTemplate[$tid] ?? []) as $doc) {
				$docId = (string)($doc['id'] ?? '');
				if ($docId === '' || isset($usedDocIds[$docId])) {
					continue;
				}
				$family = resolveFormTemplateFamily($tid);
				if ($family !== '' && isset($usedFamilies[$family])) {
					continue;
				}
				if ($family !== '') {
					$usedFamilies[$family] = true;
				}
				$usedDocIds[$docId] = true;
				$orderedProjectDocuments[] = $doc;
			}
		}
		// If templateOrder is stale/incomplete, append remaining project docs so
		// Populate All Forms can still render the full project set.
		foreach ($projectDocuments as $doc) {
			if (!is_array($doc)) {
				continue;
			}
			$docId = (string)($doc['id'] ?? '');
			if ($docId === '' || isset($usedDocIds[$docId])) {
				continue;
			}
			$tid = sanitizeId((string)($doc['templateId'] ?? ''));
			if ($tid === '') {
				continue;
			}
			$family = resolveFormTemplateFamily($tid);
			if ($family !== '' && isset($usedFamilies[$family])) {
				continue;
			}
			if ($family !== '') {
				$usedFamilies[$family] = true;
			}
			$usedDocIds[$docId] = true;
			$orderedProjectDocuments[] = $doc;
		}
		$projectDocumentsWithTemplates = [];
		foreach ($orderedProjectDocuments as $doc) {
			$docTemplate = TemplateRegistry::getTemplate((string)($doc['templateId'] ?? ''));
			$projectDocumentsWithTemplates[] = [
				'doc' => $doc,
				'template' => $docTemplate,
				'isCurrent' => ((string)($doc['id'] ?? '') === (string)$pdId),
			];
		}
        
        if ($isDebug) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' POPULATE: Rendering populate form with values: ' . json_encode($values) . PHP_EOL, FILE_APPEND);
        }

		// --- Interactive PDF preview data (page backgrounds + per-field mm positions) ---
		$templateIdForPreview = (string)($projDoc['templateId'] ?? '');
		$previewBackgrounds = [];
		$fieldPositions = [];
		$previewPageCount = max(1, (int)($template['pageCount'] ?? 1));
		$populatePreviewStatus = [
			'templateId' => $templateIdForPreview,
			'missingAssets' => false,
			'missingReasons' => [],
			'positionsFileExists' => false,
			'positionsLoadedCount' => 0,
			'backgroundCount' => 0,
		];
		$normKey = static function (string $s): string {
			return strtolower(preg_replace('/[^a-z0-9]/i', '', $s));
		};
		$positionsPath = __DIR__ . '/../data/' . $templateIdForPreview . '_positions.json';
		$populatePreviewStatus['positionsFileExists'] = is_file($positionsPath);
		if (is_file($positionsPath)) {
			$rawPos = json_decode((string)file_get_contents($positionsPath), true);
			if (is_array($rawPos)) {
				$posByNorm = [];
				$maxPage = 1;
				foreach ($rawPos as $pKey => $pRow) {
					if (!is_array($pRow)) { continue; }
					$page = max(1, (int)($pRow['page'] ?? 1));
					$maxPage = max($maxPage, $page);
					$entry = [
						'page' => $page,
						'x' => (float)($pRow['x'] ?? 0),
						'y' => (float)($pRow['y'] ?? 0),
						'width' => (float)($pRow['width'] ?? 0),
						'height' => (float)($pRow['height'] ?? 0),
						'fontSize' => (float)($pRow['fontSize'] ?? 9),
						'type' => (string)($pRow['type'] ?? 'text'),
					];
					$orig = (string)($pRow['originalName'] ?? ($pRow['name'] ?? $pKey));
					$posByNorm[$normKey($orig)] = $entry;
					$posByNorm[$normKey((string)$pKey)] = $entry;
				}
				$previewPageCount = max($previewPageCount, $maxPage);
				foreach ((array)($template['fields'] ?? []) as $fld) {
					$fk = (string)($fld['key'] ?? '');
					if ($fk === '') { continue; }
					$orig = (string)($fld['metadata']['originalName'] ?? '');
					if ($orig !== '' && isset($posByNorm[$normKey($orig)])) {
						$fieldPositions[$fk] = $posByNorm[$normKey($orig)];
					} elseif (isset($posByNorm[$normKey($fk)])) {
						$fieldPositions[$fk] = $posByNorm[$normKey($fk)];
					}
				}
			}
		}
		$populatePreviewStatus['positionsLoadedCount'] = count($fieldPositions);
		$uploadsDir = __DIR__ . '/uploads';
		$normTpl = $normKey($templateIdForPreview);
		if (is_dir($uploadsDir)) {
			foreach ((array)(glob($uploadsDir . '/*_page*_background.png') ?: []) as $bgPath) {
				$base = basename((string)$bgPath);
				if (preg_match('/^(.*)_page(\d+)_background\.png$/i', $base, $m) === 1) {
					if ($normKey($m[1]) === $normTpl) {
						$previewBackgrounds[(int)$m[2]] = 'uploads/' . $base;
					}
				}
			}
			ksort($previewBackgrounds);
		}
		$populatePreviewStatus['backgroundCount'] = count($previewBackgrounds);
		$missingReasons = [];
		if (!is_file($positionsPath)) {
			$missingReasons[] = 'Field positions file is missing.';
		} elseif (empty($fieldPositions)) {
			$missingReasons[] = 'Field positions could not be loaded.';
		}
		if (empty($previewBackgrounds)) {
			$missingReasons[] = 'Preview background image files are missing.';
		}
		$populatePreviewStatus['missingReasons'] = $missingReasons;
		$populatePreviewStatus['missingAssets'] = !empty($missingReasons);

        // Render populate form
        render('populate', [
			'projectDocument' => $projDoc,
			'template' => $template,
			'values' => $values,
			'projectDocumentsWithTemplates' => $projectDocumentsWithTemplates,
			'previewBackgrounds' => $previewBackgrounds,
			'fieldPositions' => $fieldPositions,
			'previewPageCount' => $previewPageCount,
			'populatePreviewStatus' => $populatePreviewStatus,
			'populateManagerPresetGroups' => buildPopulateManagerPresetGroups($store, $projDoc),
		]);
		break;

	case 'drafting':
		$pdId = sanitizeId((string)($_GET['pd'] ?? ''));
		$projDoc = $store->getProjectDocumentById($pdId);
		if (!$projDoc) {
			header('HTTP/1.1 404 Not Found');
			echo 'Document not found';
			exit;
		}
		
		// Load template dynamically
		$template = TemplateRegistry::getTemplate($projDoc['templateId']);
		$values = mergeFieldValuesWithAutofill($store, $projDoc, $template ?: [], $store->getFieldValues($pdId));
		$projectViewConfig = method_exists($store, 'getProjectViewConfig')
			? (array)$store->getProjectViewConfig((string)$projDoc['projectId'])
			: [];
		$templateOrder = [];
		foreach ((array)($projectViewConfig['templateOrder'] ?? []) as $value) {
			$tid = sanitizeId((string)$value);
			if ($tid !== '' && !in_array($tid, $templateOrder, true)) {
				$templateOrder[] = $tid;
			}
		}
		$templateOrder = pruneDuplicateFamilyProjectDocuments($store, (string)$projDoc['projectId'], $templateOrder);
		$projectDocuments = $store->getProjectDocuments($projDoc['projectId']);
		$docsByTemplate = [];
		foreach ($projectDocuments as $doc) {
			if (!is_array($doc)) {
				continue;
			}
			$tid = sanitizeId((string)($doc['templateId'] ?? ''));
			if ($tid === '') {
				continue;
			}
			if (!isset($docsByTemplate[$tid])) {
				$docsByTemplate[$tid] = [];
			}
			$docsByTemplate[$tid][] = $doc;
		}
		$orderedProjectDocuments = [];
		$usedDocIds = [];
		$usedFamilies = [];
		foreach ($templateOrder as $tid) {
			foreach ((array)($docsByTemplate[$tid] ?? []) as $doc) {
				$docId = (string)($doc['id'] ?? '');
				if ($docId === '' || isset($usedDocIds[$docId])) {
					continue;
				}
				$family = resolveFormTemplateFamily($tid);
				if ($family !== '' && isset($usedFamilies[$family])) {
					continue;
				}
				if ($family !== '') {
					$usedFamilies[$family] = true;
				}
				$usedDocIds[$docId] = true;
				$orderedProjectDocuments[] = $doc;
			}
		}
		// Keep Drafting in sync with Populate ordering behavior when templateOrder
		// does not include every project document yet.
		foreach ($projectDocuments as $doc) {
			if (!is_array($doc)) {
				continue;
			}
			$docId = (string)($doc['id'] ?? '');
			if ($docId === '' || isset($usedDocIds[$docId])) {
				continue;
			}
			$tid = sanitizeId((string)($doc['templateId'] ?? ''));
			if ($tid === '') {
				continue;
			}
			$family = resolveFormTemplateFamily($tid);
			if ($family !== '' && isset($usedFamilies[$family])) {
				continue;
			}
			if ($family !== '') {
				$usedFamilies[$family] = true;
			}
			$usedDocIds[$docId] = true;
			$orderedProjectDocuments[] = $doc;
		}
		$projectDocumentsWithTemplates = [];
		foreach ($orderedProjectDocuments as $doc) {
			$docTemplate = TemplateRegistry::getTemplate($doc['templateId']);
			$projectDocumentsWithTemplates[] = [
				'doc' => $doc,
				'template' => $docTemplate
			];
		}
		
		render('drafting', [ 
			'projectDocument' => $projDoc, 
			'template' => $template, 
			'values' => $values,
			'projectDocumentsWithTemplates' => $projectDocumentsWithTemplates
		]);
		break;

	case 'populate_test':
		// Legacy alias: view `populate_test.php` was removed; behave like `populate` for the same document.
		$pdId = sanitizeId((string)($_GET['pd'] ?? ''));
		$projDoc = $store->getProjectDocumentById($pdId);
		if (!$projDoc) {
			header('HTTP/1.1 404 Not Found');
			echo 'Document not found';
			exit;
		}
		$template = TemplateRegistry::getTemplate($projDoc['templateId']);
		$values = mergeFieldValuesWithAutofill($store, $projDoc, $template ?: [], $store->getFieldValues($pdId));
		render('populate', [ 'projectDocument' => $projDoc, 'template' => $template, 'values' => $values ]);
		break;

	case 'actions/create-project':
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ?route=projects'); exit; }
		$name = sanitizeString((string)($_POST['name'] ?? ''), 200);
		$clientId = sanitizeId((string)($_POST['clientId'] ?? ''));
		$project = $clientId !== '' && method_exists($store, 'createProjectForClient') ? $store->createProjectForClient($clientId, $name) : $store->createProject($name);
		if (method_exists($store, 'recordActivity')) {
			$store->recordActivity('project_created', 'Project created', ['projectId' => $project['id'] ?? '', 'name' => $name, 'clientId' => $clientId]);
		}
		header('Location: ?route=project&id=' . urlencode($project['id']));
		exit;

	case 'actions/add-document':
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ?route=projects'); exit; }
		$projectId = sanitizeId((string)($_POST['projectId'] ?? ''));
		$templateId = sanitizeId((string)($_POST['templateId'] ?? ''));
		$newDoc = $store->addProjectDocument($projectId, $templateId);
		if (!empty($newDoc['id'])) {
			$project = $store->getProject($projectId);
			$client = null;
			if ($project && !empty($project['clientId']) && method_exists($store, 'getClient')) {
				$client = $store->getClient((string)$project['clientId']);
			}
			if ($client) {
				$values = $store->getFieldValues((string)$newDoc['id']);
				$merged = $values;

				// Deterministic client->template mapping wins when configured.
				if (method_exists($store, 'getClientFieldMapping')) {
					$fieldMapping = $store->getClientFieldMapping((string)$project['clientId'], $templateId);
					foreach ($fieldMapping as $fieldKey => $clientProp) {
						$existing = trim((string)($merged[$fieldKey] ?? ''));
						$mappedValue = trim((string)($client[$clientProp] ?? ''));
						if ($existing === '' && $mappedValue !== '') {
							$merged[$fieldKey] = $mappedValue;
						}
					}
				}

				foreach ($values as $k => $v) {
					$key = strtolower((string)$k);
					$cur = trim((string)$v);
					if ($cur !== '') { continue; }
					if ((strpos($key, 'email') !== false) && !empty($client['email'])) {
						$merged[$k] = (string)$client['email'];
					} elseif ((strpos($key, 'phone') !== false || strpos($key, 'tel') !== false) && !empty($client['phone'])) {
						$merged[$k] = (string)$client['phone'];
					} elseif (strpos($key, 'name') !== false && !empty($client['displayName'])) {
						$merged[$k] = (string)$client['displayName'];
					}
				}
				if ($merged !== $values) {
					$store->saveFieldValues((string)$newDoc['id'], $merged);
				}
			}
			if (method_exists($store, 'recordActivity')) {
				$store->recordActivity('document_added', 'Document added to project', [
					'projectId' => $projectId,
					'projectDocumentId' => $newDoc['id'],
					'templateId' => $templateId
				]);
			}
		}
		header('Location: ?route=project&id=' . urlencode($projectId));
		exit;

	case 'actions/create-client':
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ?route=clients'); exit; }
		$displayName = sanitizeString((string)($_POST['displayName'] ?? ''), 200);
		$email = validateEmail((string)($_POST['email'] ?? ''));
		$phone = validatePhone((string)($_POST['phone'] ?? ''));
		$company = sanitizeString((string)($_POST['company'] ?? ''), 200);
		if ($displayName === '' && $company !== '') {
			$displayName = $company;
		}
		if ($displayName !== '' && method_exists($store, 'createClient')) {
			$normalize = static function (string $value): string {
				$collapsed = preg_replace('/\s+/', ' ', $value);
				return strtolower(trim((string)($collapsed ?? $value)));
			};
			$normalizePhone = static function (string $value): string {
				$digits = preg_replace('/\D+/', '', $value);
				return (string)($digits ?? '');
			};
			$normDisplayName = $normalize($displayName);
			$normEmail = $normalize($email);
			$normPhone = $normalizePhone($phone);
			$normCompany = $normalize($company);
			$existingDuplicate = null;
			if (method_exists($store, 'getClients')) {
				foreach ((array)$store->getClients() as $existingClient) {
					$existingName = $normalize((string)($existingClient['displayName'] ?? ''));
					$existingEmail = $normalize((string)($existingClient['email'] ?? ''));
					$existingPhone = $normalizePhone((string)($existingClient['phone'] ?? ''));
					$existingCompany = $normalize((string)($existingClient['company'] ?? ''));
					$sameName = ($normDisplayName !== '' && $existingName === $normDisplayName);
					$sameEmail = ($normEmail === '' || $existingEmail === $normEmail);
					$samePhone = ($normPhone === '' || $existingPhone === $normPhone);
					$sameCompany = ($normCompany === '' || $existingCompany === $normCompany);
					if (!($sameName && $sameEmail && $samePhone && $sameCompany)) {
						continue;
					}
					$createdAtRaw = (string)($existingClient['createdAt'] ?? '');
					$createdTs = strtotime($createdAtRaw);
					if ($createdTs === false || (time() - $createdTs) > 120) {
						continue;
					}
					$existingDuplicate = $existingClient;
					break;
				}
			}
			if (is_array($existingDuplicate)) {
				header('Location: ?route=clients&success=' . urlencode('Client already added.'));
				exit;
			}
			$client = $store->createClient($displayName, $email, $phone, $company, '', '');
			if (method_exists($store, 'recordActivity')) {
				$store->recordActivity('client_created', 'Client created', ['clientId' => $client['id'] ?? '', 'displayName' => $displayName]);
			}
		} else {
			header('Location: ?route=clients&error=' . urlencode('Enter a client name or company name.'));
			exit;
		}
		header('Location: ?route=clients');
		exit;

	case 'actions/update-client-status':
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ?route=clients'); exit; }
		$clientId = (string)($_POST['clientId'] ?? '');
		$status = (string)($_POST['status'] ?? 'active');
		if ($clientId !== '') {
			if (method_exists($store, 'updateClientStatus')) {
				$store->updateClientStatus($clientId, $status);
			}
		}
		header('Location: ?route=clients');
		exit;

	case 'actions/delete-client':
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ?route=clients'); exit; }
		$clientId = (string)($_POST['clientId'] ?? '');
		if ($clientId !== '') {
			if (method_exists($store, 'deleteClientDeep')) {
				$store->deleteClientDeep($clientId);
			}
		}
		header('Location: ?route=clients');
		exit;

	case 'actions/update-project-name':
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ?route=projects'); exit; }
		$projectId = (string)($_POST['id'] ?? '');
		$newName = trim((string)($_POST['name'] ?? ''));
		if ($projectId !== '' && $newName !== '') { $store->updateProjectName($projectId, $newName); }
		header('Location: ?route=project&id=' . urlencode($projectId));
		exit;

	case 'actions/assign-client':
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ?route=projects'); exit; }
		$projectId = (string)($_POST['projectId'] ?? '');
		$clientId = (string)($_POST['clientId'] ?? '');
		if ($projectId !== '' && $clientId !== '' && method_exists($store, 'assignClientToProject')) { $store->assignClientToProject($projectId, $clientId); }
		header('Location: ?route=project&id=' . urlencode($projectId));
		exit;

	case 'actions/save-project-view-config':
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ?route=projects'); exit; }
		$projectId = sanitizeId((string)($_POST['projectId'] ?? ''));
		if ($projectId === '') { header('Location: ?route=projects'); exit; }
		$project = $store->getProject($projectId);
		if (!$project) { header('Location: ?route=projects'); exit; }

		// Persist the project name as part of the main save so users don't have to
		// click a separate button (and don't silently lose the name on reload).
		$postedName = sanitizeString((string)($_POST['projectName'] ?? ''), 200);
		if (trim($postedName) !== '' && method_exists($store, 'updateProjectName')) {
			$store->updateProjectName($projectId, trim($postedName));
		}

		$selectedFormSetId = sanitizeId((string)($_POST['selectedFormSetId'] ?? ''));
		$caseNumber = sanitizeString((string)($_POST['caseNumber'] ?? ''), 255);
		$caseValuesRaw = trim((string)($_POST['caseValuesJson'] ?? ''));
		$courtValuesRaw = trim((string)($_POST['courtValuesJson'] ?? ''));
		$attorneyValuesRaw = trim((string)($_POST['attorneyValuesJson'] ?? ''));
		$selectedAttorneyId = sanitizeId((string)($_POST['selectedAttorneyId'] ?? ''));
		$selectedAttorneyName = sanitizeString((string)($_POST['selectedAttorneyName'] ?? ''), 255);
		$selectedCourtLocationId = sanitizeId((string)($_POST['selectedCourtLocationId'] ?? ''));
		$selectedCourtDepartmentId = sanitizeId((string)($_POST['selectedCourtDepartmentId'] ?? ''));
		$selectedCourtName = sanitizeString((string)($_POST['selectedCourtName'] ?? ''), 255);
		$selectedCourtSystemRaw = strtolower(trim((string)($_POST['selectedCourtSystem'] ?? 'state')));
		$selectedCourtSystem = in_array($selectedCourtSystemRaw, ['state', 'federal'], true) ? $selectedCourtSystemRaw : 'state';
		$projectFieldRowsRaw = trim((string)($_POST['projectFieldRowsJson'] ?? ''));
		$projectFieldValuesRaw = trim((string)($_POST['projectFieldValuesJson'] ?? ''));
		$additionalTemplateIdsRaw = trim((string)($_POST['additionalTemplateIdsJson'] ?? ''));
		$templateOrderRaw = trim((string)($_POST['templateOrderJson'] ?? ''));

		$decodeAssoc = static function (string $raw): array {
			if ($raw === '') { return []; }
			$decoded = json_decode($raw, true);
			return is_array($decoded) ? $decoded : [];
		};
		$decodeList = static function (string $raw): array {
			if ($raw === '') { return []; }
			$decoded = json_decode($raw, true);
			return is_array($decoded) ? array_values($decoded) : [];
		};

		$caseValues = $decodeAssoc($caseValuesRaw);
		$courtValues = $decodeAssoc($courtValuesRaw);
		$attorneyValues = $decodeAssoc($attorneyValuesRaw);
		$projectFieldRowsInput = $decodeList($projectFieldRowsRaw);
		$projectFieldValues = $decodeAssoc($projectFieldValuesRaw);
		$additionalTemplateIdsInput = $decodeList($additionalTemplateIdsRaw);
		$templateOrderInput = $decodeList($templateOrderRaw);

		$normalizeId = static function (string $value): string {
			return preg_replace('/[^a-zA-Z0-9_-]/', '', trim($value));
		};
		$normalizeText = static function (string $value, int $max = 255): string {
			return sanitizeString($value, $max);
		};

		$sanitizedCaseValues = [];
		foreach ($caseValues as $k => $v) {
			$key = $normalizeId((string)$k);
			if ($key === '') { continue; }
			$sanitizedCaseValues[$key] = $normalizeText((string)$v, 1000);
		}

		$sanitizedCourtValues = [];
		foreach ($courtValues as $k => $v) {
			$key = $normalizeId((string)$k);
			if ($key === '') { continue; }
			$sanitizedCourtValues[$key] = $normalizeText((string)$v, 1000);
		}

		$sanitizedAttorneyValues = [];
		foreach ($attorneyValues as $k => $v) {
			$key = $normalizeId((string)$k);
			if ($key === '') { continue; }
			$sanitizedAttorneyValues[$key] = $normalizeText((string)$v, 1000);
		}

		$sanitizedProjectFieldRows = [];
		foreach ($projectFieldRowsInput as $row) {
			if (!is_array($row)) { continue; }
			$id = $normalizeId((string)($row['id'] ?? ''));
			$label = $normalizeText((string)($row['label'] ?? ''), 120);
			if ($id === '' || $label === '') { continue; }
			$sanitizedProjectFieldRows[] = [
				'id' => $id,
				'label' => $label,
			];
		}

		$sanitizedProjectFieldValues = [];
		foreach ($projectFieldValues as $k => $v) {
			$key = $normalizeId((string)$k);
			if ($key === '') { continue; }
			$sanitizedProjectFieldValues[$key] = $normalizeText((string)$v, 1000);
		}

		$sanitizeTemplateList = static function (array $values) use ($normalizeId): array {
			$out = [];
			$seen = [];
			foreach ($values as $value) {
				$tid = $normalizeId((string)$value);
				if ($tid === '' || isset($seen[$tid])) { continue; }
				$seen[$tid] = true;
				$out[] = $tid;
			}
			return $out;
		};

		$additionalTemplateIds = $sanitizeTemplateList($additionalTemplateIdsInput);
		$templateOrder = $sanitizeTemplateList($templateOrderInput);
		// Treat posted template order as authoritative. This allows removing
		// individual forms even when a form set is selected.
		$templateOrder = $sanitizeTemplateList(array_merge($templateOrder, $additionalTemplateIds));
		$templateOrder = dedupeTemplateIdsByFamily($templateOrder);

		$configPayload = [
			'selectedFormSetId' => $selectedFormSetId,
			'caseNumber' => $caseNumber,
			'caseValues' => $sanitizedCaseValues,
			'courtValues' => $sanitizedCourtValues,
			'attorneyValues' => $sanitizedAttorneyValues,
			'selectedAttorneyId' => $selectedAttorneyId,
			'selectedAttorneyName' => $selectedAttorneyName,
			'selectedCourtLocationId' => $selectedCourtLocationId,
			'selectedCourtDepartmentId' => $selectedCourtDepartmentId,
			'selectedCourtName' => $selectedCourtName,
			'selectedCourtSystem' => $selectedCourtSystem,
			'projectFieldRows' => $sanitizedProjectFieldRows,
			'projectFieldValues' => $sanitizedProjectFieldValues,
			'additionalTemplateIds' => $additionalTemplateIds,
			'templateOrder' => $templateOrder,
		];

		if (method_exists($store, 'saveProjectViewConfig')) {
			$store->saveProjectViewConfig($projectId, $configPayload);
		}
		// Explicitly materialize project documents on valid save.
		// This avoids implicit creation on page-load while ensuring Next can proceed.
		if (method_exists($store, 'getProjectDocuments') && method_exists($store, 'addProjectDocument')) {
			$existingDocs = (array)$store->getProjectDocuments($projectId);
			$existingTemplateIds = [];
			foreach ($existingDocs as $docRow) {
				if (!is_array($docRow)) {
					continue;
				}
				$tid = sanitizeId((string)($docRow['templateId'] ?? ''));
				if ($tid !== '') {
					$existingTemplateIds[$tid] = true;
				}
			}
			foreach ($templateOrder as $tid) {
				$tid = sanitizeId((string)$tid);
				if ($tid === '' || isset($existingTemplateIds[$tid])) {
					continue;
				}
				$store->addProjectDocument($projectId, $tid);
				$existingTemplateIds[$tid] = true;
			}
			$templateOrder = pruneDuplicateFamilyProjectDocuments($store, $projectId, $templateOrder);
		}

		// Autosave path: the Project View saves silently as the user works, so an
		// AJAX request just confirms success without a redirect or notice.
		if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
			header('Content-Type: application/json');
			echo json_encode(['success' => true]);
			exit;
		}

		// The config (including the case number) is now saved regardless of
		// completeness, so partial progress is never lost. We only tell the user
		// what is still required before they can proceed to filling out forms.
		$missing = [];
		if (trim($caseNumber) === '') { $missing[] = 'case number'; }
		if (empty($templateOrder)) { $missing[] = 'at least one form'; }
		if (!empty($missing)) {
			header('Location: ?route=project&id=' . urlencode($projectId) . '&error=' . urlencode('Saved. Still required before continuing: ' . implode(', ', $missing) . '.'));
			exit;
		}
		header('Location: ?route=project&id=' . urlencode($projectId) . '&success=' . urlencode('Project setup saved.'));
		exit;

	case 'actions/open-project-form':
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ?route=projects'); exit; }
		$projectId = sanitizeId((string)($_POST['projectId'] ?? ''));
		$templateId = sanitizeId((string)($_POST['templateId'] ?? ''));
		if ($projectId === '') {
			header('Location: ?route=projects&error=' . urlencode('Missing project.'));
			exit;
		}
		$project = $store->getProject($projectId);
		if (!$project) {
			header('Location: ?route=projects&error=' . urlencode('Project not found.'));
			exit;
		}

		// Accept the full selected order from the Next button so all selected forms are
		// materialized even when the user did not click "Save Project Setup" first.
		$postedOrder = [];
		$postedOrderRaw = (string)($_POST['templateOrderJson'] ?? '');
		if ($postedOrderRaw !== '') {
			$decodedOrder = json_decode($postedOrderRaw, true);
			if (is_array($decodedOrder)) {
				foreach ($decodedOrder as $tidRaw) {
					$tid = sanitizeId((string)$tidRaw);
					if ($tid !== '' && !in_array($tid, $postedOrder, true)) {
						$postedOrder[] = $tid;
					}
				}
			}
		}
		if (!empty($postedOrder)) {
			if (method_exists($store, 'getProjectViewConfig') && method_exists($store, 'saveProjectViewConfig')) {
				$existingCfg = (array)$store->getProjectViewConfig($projectId);
				$existingOrder = [];
				foreach ((array)($existingCfg['templateOrder'] ?? []) as $tidRaw) {
					$tid = sanitizeId((string)$tidRaw);
					if ($tid !== '') { $existingOrder[$tid] = true; }
				}
				$orderChanged = false;
				foreach ($postedOrder as $tid) {
					if (!isset($existingOrder[$tid])) { $orderChanged = true; break; }
				}
				if ($orderChanged || empty($existingCfg['templateOrder'])) {
					$existingCfg['templateOrder'] = $postedOrder;
					$postedSet = sanitizeId((string)($_POST['selectedFormSetId'] ?? ''));
					if ($postedSet !== '') { $existingCfg['selectedFormSetId'] = $postedSet; }
					$store->saveProjectViewConfig($projectId, $existingCfg);
				}
			}
			// Materialize a project document for each selected template (skip existing).
			if (method_exists($store, 'getProjectDocuments') && method_exists($store, 'addProjectDocument')) {
				$existingTemplateIds = [];
				foreach ((array)$store->getProjectDocuments($projectId) as $docRow) {
					if (!is_array($docRow)) { continue; }
					$tid = sanitizeId((string)($docRow['templateId'] ?? ''));
					if ($tid !== '') { $existingTemplateIds[$tid] = true; }
				}
				foreach ($postedOrder as $tid) {
					if (isset($existingTemplateIds[$tid])) { continue; }
					$store->addProjectDocument($projectId, $tid);
					$existingTemplateIds[$tid] = true;
				}
			}
			if ($templateId === '') {
				$templateId = $postedOrder[0];
			}
		}

		if ($templateId === '' && method_exists($store, 'getProjectViewConfig')) {
			$cfg = (array)$store->getProjectViewConfig($projectId);
			foreach ((array)($cfg['templateOrder'] ?? []) as $tidRaw) {
				$tid = sanitizeId((string)$tidRaw);
				if ($tid !== '') {
					$templateId = $tid;
					break;
				}
			}
		}
		if ($templateId === '' && method_exists($store, 'getProjectDocuments')) {
			foreach ((array)$store->getProjectDocuments($projectId) as $docRow) {
				if (!is_array($docRow)) {
					continue;
				}
				$tid = sanitizeId((string)($docRow['templateId'] ?? ''));
				if ($tid !== '') {
					$templateId = $tid;
					break;
				}
			}
		}
		if ($templateId === '') {
			header('Location: ?route=project&id=' . urlencode($projectId) . '&error=' . urlencode('No forms are available for this project yet.'));
			exit;
		}
		$doc = method_exists($store, 'findProjectDocumentByTemplateId')
			? $store->findProjectDocumentByTemplateId($projectId, $templateId)
			: null;
		if (!is_array($doc) && method_exists($store, 'addProjectDocument')) {
			$doc = $store->addProjectDocument($projectId, $templateId);
		}
		$pdId = is_array($doc) ? sanitizeId((string)($doc['id'] ?? '')) : '';
		if ($pdId === '') {
			header('Location: ?route=project&id=' . urlencode($projectId) . '&error=' . urlencode('Unable to open form.'));
			exit;
		}
		header('Location: ?route=populate&pd=' . urlencode($pdId));
		exit;

	case 'actions/update-client-profile':
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ?route=clients'); exit; }
		$clientId = sanitizeId((string)($_POST['clientId'] ?? ''));
		$returnToRaw = (string)($_POST['returnTo'] ?? '');
		$returnTo = (strpos($returnToRaw, '?route=') === 0) ? $returnToRaw : '';
		$clientRedirect = $returnTo !== '' ? $returnTo : ('?route=client&id=' . urlencode($clientId));
		$displayName = sanitizeString((string)($_POST['displayName'] ?? ''));
		$email = validateEmail((string)($_POST['email'] ?? ''));
		$phone = validatePhone((string)($_POST['phone'] ?? ''));
		$company = sanitizeString((string)($_POST['company'] ?? ''), 200);
		$address = sanitizeString((string)($_POST['address'] ?? ''), 300);
		$notes = sanitizeString((string)($_POST['notes'] ?? ''), 1000);
		if ($displayName === '' && $company !== '') {
			$displayName = $company;
		}
		if ($clientId !== '' && $displayName !== '') {
			$dup = findOtherClientWithNormalizedDisplayName($store, $clientId, $displayName);
			if ($dup !== null) {
				header('Location: ' . $clientRedirect . '&error=' . urlencode('A client with this display name already exists. Choose a different name (or save from the browser with the duplicate-name prompt).'));
				exit;
			}
			$saved = persistClientProfileAndFields(
				$clientId,
				$displayName,
				$email,
				$phone,
				$company,
				$address,
				$notes,
				is_array($_POST['customFields'] ?? null) ? $_POST['customFields'] : [],
				is_array($_POST['systemClientFields'] ?? null) ? $_POST['systemClientFields'] : []
			);
			if (!$saved) {
				header('Location: ' . $clientRedirect . '&error=' . urlencode('Failed to save client profile.'));
				exit;
			}
			if (method_exists($store, 'recordActivity')) {
				$store->recordActivity('client_updated', 'Client profile updated', ['clientId' => $clientId, 'displayName' => $displayName]);
			}
		} else {
			header('Location: ' . $clientRedirect . '&error=' . urlencode('Enter a client name or company name before leaving this page.'));
			exit;
		}
		header('Location: ' . $clientRedirect);
		exit;

	case 'actions/delete-client-custom-field':
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ?route=clients'); exit; }
		$clientId = sanitizeId((string)($_POST['clientId'] ?? ''));
		$fieldId = sanitizeId((string)($_POST['fieldId'] ?? ''));
		if ($clientId !== '' && $fieldId !== '' && method_exists($store, 'deleteFieldManagerCustomField')) {
			$deleted = $store->deleteFieldManagerCustomField($fieldId);
			if (!$deleted) {
				header('Location: ?route=client&id=' . urlencode($clientId) . '&error=' . urlencode('This field is protected and cannot be deleted.'));
				exit;
			}
		}
		header('Location: ?route=client&id=' . urlencode($clientId));
		exit;

	case 'actions/save-client-field-mapping':
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ?route=clients'); exit; }
		$clientId = sanitizeId((string)($_POST['clientId'] ?? ''));
		$templateId = sanitizeId((string)($_POST['templateId'] ?? ''));
		$mapping = $_POST['mapping'] ?? [];
		if (!is_array($mapping)) { $mapping = []; }
		if ($clientId !== '' && $templateId !== '' && method_exists($store, 'saveClientFieldMapping')) {
			$store->saveClientFieldMapping($clientId, $templateId, $mapping);
			if (method_exists($store, 'recordActivity')) {
				$store->recordActivity('client_mapping_saved', 'Client field mapping saved', ['clientId' => $clientId, 'templateId' => $templateId]);
			}
		}
		header('Location: ?route=client-mapping&id=' . urlencode($clientId) . '&templateId=' . urlencode($templateId));
		exit;

	case 'actions/save-fields':
        $logFile = __DIR__ . '/../logs/pdf_debug.log';
        $contentType = strtolower((string)($_SERVER['CONTENT_TYPE'] ?? ''));
        $isJsonSave = strpos($contentType, 'application/json') !== false;
        $jsonPayload = [];
        if ($isJsonSave) {
            $rawBody = (string)file_get_contents('php://input');
            $decodedBody = json_decode($rawBody, true);
            if (is_array($decodedBody)) {
                $jsonPayload = $decodedBody;
            }
        }
        $isAjaxSave = (
            ($isJsonSave && !empty($jsonPayload))
            || (!empty($_POST['ajax']) && (string)$_POST['ajax'] === '1')
            || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (isset($_SERVER['HTTP_ACCEPT']) && strpos(strtolower((string)$_SERVER['HTTP_ACCEPT']), 'application/json') !== false)
        );
        if ($isDebug) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' SAVE FIELDS: Request method: ' . $_SERVER['REQUEST_METHOD'] . PHP_EOL, FILE_APPEND);
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' SAVE FIELDS: POST data: ' . json_encode($_POST) . PHP_EOL, FILE_APPEND);
        }
		
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') { 
            if ($isDebug) {
                file_put_contents($logFile, date('Y-m-d H:i:s') . ' SAVE FIELDS: Not POST request, redirecting' . PHP_EOL, FILE_APPEND);
            }
			header('Location: ?route=projects'); 
			exit; 
		}
		
		$pdId = sanitizeId((string)($jsonPayload['projectDocumentId'] ?? ($_POST['projectDocumentId'] ?? '')));
        if ($isDebug) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' SAVE FIELDS: PD ID: ' . $pdId . PHP_EOL, FILE_APPEND);
        }
		$pdDoc = $pdId !== '' ? $store->getProjectDocumentById($pdId) : null;
		if ($pdDoc === null) {
			if ($isAjaxSave) {
				http_response_code(400);
				header('Content-Type: application/json');
				echo json_encode(['success' => false, 'message' => 'Invalid project document id']);
				exit;
			}
			header('Location: ?route=projects&error=' . urlencode('Invalid document id.'));
			exit;
		}
		
		if ($isJsonSave && is_array($jsonPayload['values'] ?? null)) {
			$data = $jsonPayload['values'];
			unset($data['projectDocumentId'], $data['ajax']);
		} else {
			$data = $_POST;
			unset($data['projectDocumentId'], $data['ajax']);
		}
        $data = sanitizeRenderableFieldValues((array)$data);
        if ($isDebug) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' SAVE FIELDS: Data to save: ' . json_encode($data) . PHP_EOL, FILE_APPEND);
        }
		
		$store->saveFieldValues($pdId, $data);
		if (method_exists($store, 'recordActivity')) {
			$store->recordActivity('document_fields_saved', 'Document field values saved', [
				'projectDocumentId' => $pdId,
				'fieldCount' => count($data),
				'projectId' => (string)($pdDoc['projectId'] ?? '')
			]);
		}
        if ($isDebug) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' SAVE FIELDS: Values saved successfully' . PHP_EOL, FILE_APPEND);
        }

		if ($isAjaxSave) {
			header('Content-Type: application/json');
			echo json_encode(['success' => true, 'projectDocumentId' => $pdId]);
			exit;
		}
		
		header('Location: ?route=populate&pd=' . urlencode($pdId) . '&saved=1');
		exit;

	case 'actions/generate':
		$pdId = sanitizeId((string)($_GET['pd'] ?? ''));
		$projDoc = $store->getProjectDocumentById($pdId);
		if (!$projDoc) {
            redirectToRoute('dashboard', ['error' => sanitizeFlashMessage('Document not found.')]);
        }
		$template = TemplateRegistry::getTemplate($projDoc['templateId']);
		$values = mergeFieldValuesWithAutofill($store, $projDoc, $template ?: [], $store->getFieldValues($pdId));
		
		// Debug: Log what we're working with
		$logFile = __DIR__ . '/../logs/pdf_debug.log';
        if ($isDebug) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' GENERATE DEBUG: PD ID: ' . $pdId . PHP_EOL, FILE_APPEND);
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' GENERATE DEBUG: Template: ' . json_encode($template) . PHP_EOL, FILE_APPEND);
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' GENERATE DEBUG: Values: ' . json_encode($values) . PHP_EOL, FILE_APPEND);
        }
		
        try {
            $result = $fill->generateSimplePdf($template ?? [], $values, ['pdId' => $pdId]);
            $logger->info('actions/generate success: ' . json_encode($result), ['pdId' => $pdId]);
		} catch (\Throwable $e) {
            error_log('PDF generation failed for pd=' . $pdId . ' : ' . $e->getMessage());
            $logger->error('PDF generation failed for pd=' . $pdId . ' : ' . $e->getMessage(), ['pdId' => $pdId]);
            redirectToRoute('populate', [
                'pd' => $pdId,
                'error' => sanitizeFlashMessage('PDF generation failed. Verify dependencies and template configuration.')
            ]);
		}
		// persist path and status
		$projDoc['status'] = 'ready_to_sign';
		$projDoc['outputPath'] = $result['filename']; // Store relative path only
		if (method_exists($store, 'updateProjectDocument')) {
			$store->updateProjectDocument($pdId, [
				'status' => 'ready_to_sign',
				'outputPath' => (string)$result['filename'],
			]);
		}
		if (method_exists($store, 'recordActivity')) {
			$store->recordActivity('pdf_generated', 'PDF generated', [
				'projectDocumentId' => $pdId,
				'projectId' => (string)($projDoc['projectId'] ?? ''),
				'templateId' => (string)($projDoc['templateId'] ?? ''),
				'file' => (string)($result['filename'] ?? '')
			]);
		}
        redirectToRoute('drafting', ['pd' => $pdId, 'success' => sanitizeFlashMessage('PDF generated successfully.')]);

	case 'preview':
		$pdId = (string)($_GET['pd'] ?? '');
		$projDoc = $store->getProjectDocumentById($pdId);
		if (!$projDoc) {
			header('HTTP/1.1 404 Not Found');
			echo 'Document not found';
			exit;
		}
		$template = TemplateRegistry::getTemplate($projDoc['templateId']);
		$values = mergeFieldValuesWithAutofill($store, $projDoc, $template ?: [], $store->getFieldValues($pdId));
		render('preview', [ 'projectDocument' => $projDoc, 'template' => $template, 'values' => $values ]);
		break;

	case 'pdf-preview':
		$pdId = (string)($_GET['pd'] ?? '');
		$projDoc = $store->getProjectDocumentById($pdId);
		if (!$projDoc) {
			header('HTTP/1.1 404 Not Found');
			echo 'Document not found';
			exit;
		}
		$template = TemplateRegistry::getTemplate($projDoc['templateId']);
		$values = mergeFieldValuesWithAutofill($store, $projDoc, $template ?: [], $store->getFieldValues($pdId));
		
		// Get PDF form fields (for now using sample data)
		$pdfFields = $pdfFieldService->getSamplePdfFields();
		
		render('pdf-preview', [ 
			'projectDocument' => $projDoc, 
			'template' => $template, 
			'values' => $values,
			'pdfFields' => $pdfFields
		]);
		break;

	case 'actions/download':
		$pdId = (string)($_GET['pd'] ?? '');
		$projDoc = $store->getProjectDocumentById($pdId);
		if (!is_array($projDoc)) {
            redirectToRoute('documents', ['error' => sanitizeFlashMessage('Document not found.')]);
		}
		$filename = $projDoc['outputPath'] ?? '';
		
		// Debug: Log download attempt
		error_log("Download Debug - PD ID: " . $pdId);
		error_log("Filename: " . $filename);
		error_log("Project Document: " . json_encode($projDoc));
		
		if (!$filename) {
			error_log("No filename found for document");
            redirectToRoute('documents', ['error' => sanitizeFlashMessage('No generated PDF found for this document.')]);
		}

        $path = resolveOutputFilePath($filename);
		if ($path === null) {
			error_log("Download failed - file not found in output directory. Filename: " . $filename);
            redirectToRoute('documents', ['error' => sanitizeFlashMessage('Generated PDF file is missing. Please regenerate the document.')]);
		}
		
		header('Content-Type: application/pdf');
		// Check if this is for iframe display (from preview page)
		$isInline = isset($_GET['inline']) || (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'preview') !== false);
		if ($isInline) {
			header('Content-Disposition: inline; filename="' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename) . '"');
		} else {
		header('Content-Disposition: attachment; filename="' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename) . '"');
		if (method_exists($store, 'recordActivity')) {
			$store->recordActivity('pdf_downloaded', 'Generated PDF downloaded', [
				'projectDocumentId' => $pdId,
				'projectId' => (string)($projDoc['projectId'] ?? ''),
				'file' => (string)$filename
			]);
		}
		}
		readfile($path);
		exit;

	case 'actions/serve-pdf':
		// Simple PDF serving route for iframe display
		$pdId = (string)($_GET['pd'] ?? '');
		$projDoc = $store->getProjectDocumentById($pdId);
		if (!is_array($projDoc)) {
			http_response_code(404);
			echo 'Document not found';
			exit;
		}
		$filename = $projDoc['outputPath'] ?? '';
		
		if (!$filename) {
			http_response_code(404);
			echo 'PDF not found';
			exit;
		}
		
		$path = resolveOutputFilePath($filename);
		if ($path === null) {
			error_log("SERVE-PDF: file not found in output directory. Filename: " . $filename);
			http_response_code(404);
			echo 'PDF file not found';
			exit;
		}
		
		header('Content-Type: application/pdf');
		header('Content-Disposition: inline; filename="' . basename($filename) . '"');
		readfile($path);
		exit;

	case 'actions/download-signed':
		$pdId = (string)($_GET['pd'] ?? '');
		$projDoc = $store->getProjectDocumentById($pdId);
		if (!is_array($projDoc)) {
            redirectToRoute('documents', ['error' => sanitizeFlashMessage('Document not found.')]);
		}
		$filename = $projDoc['signedPath'] ?? '';
		
		if (!$filename) { 
            redirectToRoute('documents', ['error' => sanitizeFlashMessage('No signed PDF found for this document.')]);
		}
		
		$path = resolveOutputFilePath($filename);
		if ($path === null) {
            redirectToRoute('documents', ['error' => sanitizeFlashMessage('Signed PDF file is missing. Please re-sign the document.')]);
		}
		
		header('Content-Type: application/pdf');
		header('Content-Disposition: attachment; filename="' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename) . '"');
		if (method_exists($store, 'recordActivity')) {
			$store->recordActivity('signed_pdf_downloaded', 'Signed PDF downloaded', [
				'projectDocumentId' => $pdId,
				'projectId' => (string)($projDoc['projectId'] ?? ''),
				'file' => (string)$filename
			]);
		}
		readfile($path);
		exit;

	case 'actions/export-project-forms':
		$projectId = sanitizeId((string)($_GET['projectId'] ?? ''));
		$currentPdId = sanitizeId((string)($_GET['pd'] ?? ''));
		$scope = strtolower(trim((string)($_GET['scope'] ?? 'this')));
		$format = strtolower(trim((string)($_GET['format'] ?? 'pdf')));
		if (!in_array($scope, ['this', 'all'], true)) {
			$scope = 'this';
		}
		if (!in_array($format, ['pdf', 'zip', 'merged'], true)) {
			$format = 'pdf';
		}
		if ($projectId === '' && $currentPdId !== '') {
			$currentDoc = $store->getProjectDocumentById($currentPdId);
			if (is_array($currentDoc)) {
				$projectId = sanitizeId((string)($currentDoc['projectId'] ?? ''));
			}
		}
		if ($projectId === '' && $currentPdId === '') {
			redirectToRoute('projects', ['error' => sanitizeFlashMessage('Invalid export request.')]);
		}

		$docs = [];
		if ($scope === 'this' && $currentPdId !== '') {
			$doc = $store->getProjectDocumentById($currentPdId);
			if (is_array($doc)) {
				$docs[] = $doc;
			}
		} elseif ($projectId !== '') {
			$allDocs = [];
			foreach ((array)$store->getProjectDocuments($projectId) as $doc) {
				if (is_array($doc)) {
					$allDocs[] = $doc;
				}
			}
			$projectViewConfig = method_exists($store, 'getProjectViewConfig')
				? (array)$store->getProjectViewConfig($projectId)
				: [];
			$templateOrder = [];
			foreach ((array)($projectViewConfig['templateOrder'] ?? []) as $value) {
				$tid = sanitizeId((string)$value);
				if ($tid !== '' && !in_array($tid, $templateOrder, true)) {
					$templateOrder[] = $tid;
				}
			}
			$docsByTemplate = [];
			foreach ($allDocs as $doc) {
				$tid = sanitizeId((string)($doc['templateId'] ?? ''));
				if ($tid === '') {
					continue;
				}
				if (!isset($docsByTemplate[$tid])) {
					$docsByTemplate[$tid] = [];
				}
				$docsByTemplate[$tid][] = $doc;
			}
			$usedDocIds = [];
			foreach ($templateOrder as $tid) {
				foreach ((array)($docsByTemplate[$tid] ?? []) as $doc) {
					$docId = (string)($doc['id'] ?? '');
					if ($docId === '' || isset($usedDocIds[$docId])) {
						continue;
					}
					$usedDocIds[$docId] = true;
					$docs[] = $doc;
				}
			}
			foreach ($allDocs as $doc) {
				$docId = (string)($doc['id'] ?? '');
				if ($docId === '' || isset($usedDocIds[$docId])) {
					continue;
				}
				$usedDocIds[$docId] = true;
				$docs[] = $doc;
			}
		}

		if (empty($docs)) {
			$fallbackRoute = $currentPdId !== '' ? 'populate' : 'project';
			$fallbackParams = $currentPdId !== '' ? ['pd' => $currentPdId] : ['id' => $projectId];
			$fallbackParams['error'] = sanitizeFlashMessage('No project forms available for export.');
			redirectToRoute($fallbackRoute, $fallbackParams);
		}

		$generatedFiles = [];
		$createdTempFiles = [];
		foreach ($docs as $doc) {
			$pdId = sanitizeId((string)($doc['id'] ?? ''));
			if ($pdId === '') {
				continue;
			}
			$template = TemplateRegistry::getTemplate((string)($doc['templateId'] ?? ''));
			$values = mergeFieldValuesWithAutofill($store, $doc, $template ?: [], $store->getFieldValues($pdId));
			if (!is_array($template) || empty($template)) {
				continue;
			}
			try {
				$result = $fill->generateSimplePdf($template, $values, ['pdId' => $pdId]);
				$path = resolveOutputFilePath((string)($result['filename'] ?? ''));
				if ($path && is_file($path)) {
					$generatedFiles[$pdId] = $path;
					if (method_exists($store, 'updateProjectDocument')) {
						$store->updateProjectDocument($pdId, [
							'status' => 'ready_to_sign',
							'outputPath' => (string)($result['filename'] ?? ''),
						]);
					}
				}
			} catch (\Throwable $e) {
				@error_log('Export generate failed for pd=' . $pdId . ': ' . $e->getMessage());
			}
		}

		if (empty($generatedFiles)) {
			$fallbackRoute = $currentPdId !== '' ? 'populate' : 'project';
			$fallbackParams = $currentPdId !== '' ? ['pd' => $currentPdId] : ['id' => $projectId];
			$fallbackParams['error'] = sanitizeFlashMessage('Unable to generate export files.');
			redirectToRoute($fallbackRoute, $fallbackParams);
		}

		$downloadNameBase = $projectId !== '' ? ('project_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $projectId)) : 'project_export';
		$selectedFiles = [];
		foreach ($docs as $doc) {
			$pdId = sanitizeId((string)($doc['id'] ?? ''));
			if ($pdId !== '' && isset($generatedFiles[$pdId])) {
				$selectedFiles[] = [
					'doc' => $doc,
					'path' => $generatedFiles[$pdId],
				];
			}
		}

		if (empty($selectedFiles)) {
			$fallbackRoute = $currentPdId !== '' ? 'populate' : 'project';
			$fallbackParams = $currentPdId !== '' ? ['pd' => $currentPdId] : ['id' => $projectId];
			$fallbackParams['error'] = sanitizeFlashMessage('No generated files available for selected export.');
			redirectToRoute($fallbackRoute, $fallbackParams);
		}

		$sendFileResponse = static function (string $path, string $downloadName, string $contentType = 'application/pdf') use (&$createdTempFiles): void {
			if (!is_file($path)) {
				http_response_code(404);
				echo 'Export file missing';
				exit;
			}
			header('Content-Type: ' . $contentType);
			header('Content-Disposition: attachment; filename="' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $downloadName) . '"');
			header('Content-Length: ' . (string)filesize($path));
			readfile($path);
			foreach ($createdTempFiles as $tmpPath) {
				if (is_file($tmpPath)) {
					@unlink($tmpPath);
				}
			}
			exit;
		};

		if ($format === 'pdf' && $scope === 'this') {
			$single = $selectedFiles[0]['path'];
			$singleDoc = is_array($selectedFiles[0]['doc']) ? $selectedFiles[0]['doc'] : [];
			$template = TemplateRegistry::getTemplate((string)($singleDoc['templateId'] ?? ''));
			$code = trim((string)($template['code'] ?? 'form'));
			$sendFileResponse($single, $code . '.pdf', 'application/pdf');
		}

		if ($format === 'merged') {
			$tmpMerged = tempnam(sys_get_temp_dir(), 'mvp-merged-');
			if ($tmpMerged === false) {
				$fallbackRoute = $currentPdId !== '' ? 'populate' : 'project';
				$fallbackParams = $currentPdId !== '' ? ['pd' => $currentPdId] : ['id' => $projectId];
				$fallbackParams['error'] = sanitizeFlashMessage('Failed creating merged export file.');
				redirectToRoute($fallbackRoute, $fallbackParams);
			}
			$mergedPath = $tmpMerged . '.pdf';
			@rename($tmpMerged, $mergedPath);
			$createdTempFiles[] = $mergedPath;
			try {
				$pdf = new \setasign\Fpdi\Fpdi();
				foreach ($selectedFiles as $item) {
					$path = (string)($item['path'] ?? '');
					if ($path === '' || !is_file($path)) {
						continue;
					}
					$pageCount = $pdf->setSourceFile($path);
					for ($p = 1; $p <= $pageCount; $p++) {
						$tplId = $pdf->importPage($p);
						$size = $pdf->getTemplateSize($tplId);
						$orientation = ((float)$size['width'] > (float)$size['height']) ? 'L' : 'P';
						$pdf->AddPage($orientation, [(float)$size['width'], (float)$size['height']]);
						$pdf->useTemplate($tplId);
					}
				}
				$pdf->Output('F', $mergedPath);
				$sendFileResponse($mergedPath, $downloadNameBase . '_merged.pdf', 'application/pdf');
			} catch (\Throwable $e) {
				@error_log('Merged export FPDI failed: ' . $e->getMessage());
				$mergeInputPaths = [];
				foreach ($selectedFiles as $item) {
					$path = (string)($item['path'] ?? '');
					if ($path !== '' && is_file($path)) {
						$mergeInputPaths[] = $path;
					}
				}
				$mergedByQpdf = false;
				if (!empty($mergeInputPaths)) {
					$qpdfBins = ['qpdf', 'qpdf.exe', __DIR__ . '/../bin/qpdf/bin/qpdf.bat', __DIR__ . '/../bin/qpdf/bin/qpdf.exe'];
					foreach ($qpdfBins as $qpdfBin) {
						$cmd = $qpdfBin . ' --empty --pages';
						foreach ($mergeInputPaths as $path) {
							$cmd .= ' ' . escapeshellarg($path);
						}
						$cmd .= ' -- ' . escapeshellarg($mergedPath);
						$out = [];
						$exit = 1;
						@exec($cmd . ' 2>&1', $out, $exit);
						if ($exit === 0 && is_file($mergedPath) && filesize($mergedPath) > 0) {
							$mergedByQpdf = true;
							break;
						}
					}
				}
				if ($mergedByQpdf) {
					$sendFileResponse($mergedPath, $downloadNameBase . '_merged.pdf', 'application/pdf');
				}
				$gsBins = ['gs', 'gswin64c', 'gswin32c'];
				$mergedByGs = false;
				foreach ($gsBins as $gsBin) {
					if ($mergedByGs) {
						break;
					}
					$cmd = $gsBin
						. ' -q -dSAFER -dBATCH -dNOPAUSE -sDEVICE=pdfwrite -dCompatibilityLevel=1.7'
						. ' -dCompressFonts=true -dSubsetFonts=true -dDetectDuplicateImages=true'
						. ' -dDownsampleColorImages=false -dDownsampleGrayImages=false -dDownsampleMonoImages=false'
						. ' -dAutoFilterColorImages=false -dAutoFilterGrayImages=false'
						. ' -dColorImageFilter=/FlateEncode -dGrayImageFilter=/FlateEncode'
						. ' -dPassThroughJPEGImages=true -dPassThroughJPXImages=true'
						. ' -sOutputFile=' . escapeshellarg($mergedPath);
					foreach ($mergeInputPaths as $path) {
						$cmd .= ' ' . escapeshellarg($path);
					}
					$out = [];
					$exit = 1;
					@exec($cmd . ' 2>&1', $out, $exit);
					if ($exit === 0 && is_file($mergedPath) && filesize($mergedPath) > 0) {
						$mergedByGs = true;
						break;
					}
				}
				if ($mergedByGs) {
					$sendFileResponse($mergedPath, $downloadNameBase . '_merged.pdf', 'application/pdf');
				}
				$fallbackRoute = $currentPdId !== '' ? 'populate' : 'project';
				$fallbackParams = $currentPdId !== '' ? ['pd' => $currentPdId] : ['id' => $projectId];
				$fallbackParams['error'] = sanitizeFlashMessage('Merged export failed.');
				redirectToRoute($fallbackRoute, $fallbackParams);
			}
		}

		// Default: ZIP bundle (or explicit zip)
		$tmpZip = tempnam(sys_get_temp_dir(), 'mvp-zip-');
		if ($tmpZip === false) {
			$fallbackRoute = $currentPdId !== '' ? 'populate' : 'project';
			$fallbackParams = $currentPdId !== '' ? ['pd' => $currentPdId] : ['id' => $projectId];
			$fallbackParams['error'] = sanitizeFlashMessage('Failed creating zip export file.');
			redirectToRoute($fallbackRoute, $fallbackParams);
		}
		$zipPath = $tmpZip . '.zip';
		@rename($tmpZip, $zipPath);
		$createdTempFiles[] = $zipPath;
		$zip = new \ZipArchive();
		if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
			$fallbackRoute = $currentPdId !== '' ? 'populate' : 'project';
			$fallbackParams = $currentPdId !== '' ? ['pd' => $currentPdId] : ['id' => $projectId];
			$fallbackParams['error'] = sanitizeFlashMessage('Could not open zip file for export.');
			redirectToRoute($fallbackRoute, $fallbackParams);
		}
		foreach ($selectedFiles as $idx => $item) {
			$doc = is_array($item['doc']) ? $item['doc'] : [];
			$path = (string)($item['path'] ?? '');
			if ($path === '' || !is_file($path)) {
				continue;
			}
			$template = TemplateRegistry::getTemplate((string)($doc['templateId'] ?? ''));
			$code = trim((string)($template['code'] ?? ('form_' . ($idx + 1))));
			$entryName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $code) . '.pdf';
			$zip->addFile($path, $entryName);
		}
		$zip->close();
		$sendFileResponse($zipPath, $downloadNameBase . '.zip', 'application/zip');

	case 'actions/update-doc-status':
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ?route=projects'); exit; }
		$pdId = (string)($_POST['id'] ?? '');
		$status = (string)($_POST['status'] ?? 'in_progress');
		$doc = $store->getProjectDocumentById($pdId);
		$projectId = (string)($doc['projectId'] ?? '');
		if ($pdId !== '' && method_exists($store, 'updateProjectDocument')) {
			$store->updateProjectDocument($pdId, ['status' => $status]);
		}
		header('Location: ?route=project&id=' . urlencode($projectId));
		exit;

	case 'actions/remove-document':
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ?route=projects'); exit; }
		$pdId = (string)($_POST['id'] ?? '');
		$doc = $store->getProjectDocumentById($pdId);
		$projectId = $doc['projectId'] ?? '';
		if ($pdId !== '') { $store->deleteProjectDocument($pdId); }
		header('Location: ?route=project&id=' . urlencode($projectId));
		exit;

	case 'actions/duplicate-project':
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ?route=projects'); exit; }
		$projectId = (string)($_POST['id'] ?? '');
		$copy = $store->duplicateProjectDeep($projectId);
		$redirectId = $copy['id'] ?? '';
		header('Location: ?route=project&id=' . urlencode($redirectId !== '' ? $redirectId : $projectId));
		exit;

	case 'actions/delete-project':
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			http_response_code(405);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Method not allowed']);
			exit;
		}
		$projectId = sanitizeId((string)($_POST['projectId'] ?? ''));
		$redirectClientId = sanitizeId((string)($_POST['clientId'] ?? ''));
		if ($projectId !== '' && method_exists($store, 'deleteProjectDeep')) {
			$store->deleteProjectDeep($projectId);
			if (method_exists($store, 'recordActivity')) {
				$store->recordActivity('project_deleted', 'Project deleted', ['projectId' => $projectId, 'clientId' => $redirectClientId]);
			}
		}
		header('Content-Type: application/json');
		echo json_encode([
			'success' => true,
			'redirect' => $redirectClientId !== '' ? ('?route=client&id=' . urlencode($redirectClientId)) : '?route=projects'
		]);
		exit;

	case 'actions/upload-client-file':
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			http_response_code(405);
			echo json_encode(['error' => 'Method not allowed']);
			exit;
		}
		header('Content-Type: application/json');
		
		if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
			http_response_code(400);
			echo json_encode(['error' => 'No file uploaded']);
			exit;
		}
		
		$clientId = sanitizeId((string)($_POST['clientId'] ?? ''));
		$projectId = sanitizeId((string)($_POST['projectId'] ?? ''));
		
		$uploadedFile = $fileManager->uploadClientFile($clientId, $projectId ?: null, $_FILES['file']);
		
		if ($uploadedFile) {
			echo json_encode(['success' => true, 'file' => $uploadedFile]);
		} else {
			http_response_code(500);
			echo json_encode(['error' => 'Failed to upload file']);
		}
		exit;

	case 'actions/delete-client-file':
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			http_response_code(405);
			echo json_encode(['error' => 'Method not allowed']);
			exit;
		}
		header('Content-Type: application/json');
		
		$fileId = sanitizeId((string)($_POST['fileId'] ?? ''));
		
		if ($fileManager->deleteClientFile($fileId)) {
			echo json_encode(['success' => true]);
		} else {
			http_response_code(404);
			echo json_encode(['error' => 'File not found']);
		}
		exit;

	case 'actions/list-client-files':
		header('Content-Type: application/json');
		
		$clientId = sanitizeId((string)($_GET['clientId'] ?? ''));
		$projectId = sanitizeId((string)($_GET['projectId'] ?? ''));
		
		$files = $fileManager->getClientFiles($clientId, $projectId ?: null);
		
		echo json_encode(['success' => true, 'files' => $files]);
		exit;

	case 'actions/add-custom-field':
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			http_response_code(405);
			echo json_encode(['error' => 'Method not allowed']);
			exit;
		}
		header('Content-Type: application/json');
		
		$templateId = sanitizeId((string)($_POST['templateId'] ?? ''));
		$label = sanitizeString((string)($_POST['label'] ?? ''));
		$type = sanitizeString((string)($_POST['type'] ?? 'text'));
		$x = (int)($_POST['x'] ?? 100);
		$y = (int)($_POST['y'] ?? 100);
		$width = (int)($_POST['width'] ?? 200);
		$height = (int)($_POST['height'] ?? 25);
		
		if (empty($templateId) || empty($label)) {
			http_response_code(400);
			echo json_encode(['error' => 'Template ID and label are required']);
			exit;
		}
		
		$fieldConfig = [
			'label' => $label,
			'type' => $type,
			'x' => $x,
			'y' => $y,
			'width' => $width,
			'height' => $height
		];
		
		$field = $customFieldManager->addCustomField($templateId, $fieldConfig);
		
		if ($field) {
			echo json_encode(['success' => true, 'field' => $field]);
		} else {
			http_response_code(500);
			echo json_encode(['error' => 'Failed to add custom field']);
		}
		exit;

	case 'actions/remove-custom-field':
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			http_response_code(405);
			echo json_encode(['error' => 'Method not allowed']);
			exit;
		}
		header('Content-Type: application/json');
		
		$templateId = sanitizeId((string)($_POST['templateId'] ?? ''));
		$fieldKey = sanitizeString((string)($_POST['fieldKey'] ?? ''));
		
		if (empty($templateId) || empty($fieldKey)) {
			http_response_code(400);
			echo json_encode(['error' => 'Template ID and field key are required']);
			exit;
		}
		
		if ($customFieldManager->removeCustomField($templateId, $fieldKey)) {
			echo json_encode(['success' => true]);
		} else {
			http_response_code(404);
			echo json_encode(['error' => 'Field not found']);
		}
		exit;

	case 'actions/update-field-position':
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			http_response_code(405);
			echo json_encode(['error' => 'Method not allowed']);
			exit;
		}
		header('Content-Type: application/json');
		
		$templateId = sanitizeId((string)($_POST['templateId'] ?? ''));
		$fieldKey = sanitizeString((string)($_POST['fieldKey'] ?? ''));
		$x = (int)($_POST['x'] ?? 0);
		$y = (int)($_POST['y'] ?? 0);
		$width = (int)($_POST['width'] ?? 200);
		$height = (int)($_POST['height'] ?? 25);
		
		if (empty($templateId) || empty($fieldKey)) {
			http_response_code(400);
			echo json_encode(['error' => 'Template ID and field key are required']);
			exit;
		}
		
		if ($customFieldManager->updateFieldPosition($templateId, $fieldKey, $x, $y, $width, $height)) {
			echo json_encode(['success' => true]);
		} else {
			http_response_code(404);
			echo json_encode(['error' => 'Field not found']);
		}
		exit;

	case 'actions/get-project-documents':
		header('Content-Type: application/json');
		
		$projectId = sanitizeId((string)($_GET['projectId'] ?? ''));
		if (empty($projectId)) {
			http_response_code(400);
			echo json_encode(['error' => 'Project ID required']);
			exit;
		}
		
		try {
			$documents = $store->getProjectDocuments($projectId);
			$documentsWithTemplates = [];
			
			foreach ($documents as $doc) {
				$template = TemplateRegistry::getTemplate($doc['templateId']);
				$documentsWithTemplates[] = [
					'id' => $doc['id'],
					'templateId' => $doc['templateId'],
					'templateName' => formatTemplateDisplayLabel($template, (string)($doc['templateId'] ?? '')),
					'status' => $doc['status'] ?? 'in_progress',
					'createdAt' => $doc['createdAt'] ?? '',
					'outputPath' => $doc['outputPath'] ?? null
				];
			}
			
			echo json_encode(['success' => true, 'documents' => $documentsWithTemplates]);
		} catch (Exception $e) {
			http_response_code(500);
			echo json_encode(['error' => $e->getMessage()]);
		}
		exit;

        case 'actions/rescan-fields':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                exit;
            }
            header('Content-Type: application/json');
            
            $projectDocumentId = sanitizeId((string)($_POST['projectDocumentId'] ?? ''));
            if (empty($projectDocumentId)) {
                http_response_code(400);
                echo json_encode(['error' => 'Project Document ID required']);
                exit;
            }
            
            try {
                $projectDocument = $store->getProjectDocumentById($projectDocumentId);
                if (!$projectDocument) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Project document not found']);
                    exit;
                }
                
                $template = TemplateRegistry::getTemplate($projectDocument['templateId']);
                if (!$template) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Template not found']);
                    exit;
                }
                
                // Get the PDF file path
                $pdfPath = $template['pdfPath'] ?? '';
                if (empty($pdfPath) || !file_exists($pdfPath)) {
                    http_response_code(404);
                    echo json_encode(['error' => 'PDF file not found']);
                    exit;
                }
                
                // Use the PDF field extractor to get field positions
                require_once __DIR__ . '/lib/pdf_field_extractor.php';
                $extractor = new \WebPdfTimeSaver\Mvp\PdfFieldExtractor();
                $fields = $extractor->extractFieldPositions($pdfPath);
                
                if (empty($fields)) {
                    echo json_encode(['error' => 'No fields found in PDF']);
                    exit;
                }
                
                // Generate/update the positions file
                $dataDir = __DIR__ . '/../data';
                $positionFile = $dataDir . '/' . $projectDocument['templateId'] . '_positions.json';
                
                // Ensure data directory exists
                if (!is_dir($dataDir)) {
                    mkdir($dataDir, 0755, true);
                }
                
                // Save positions to file
                $json = json_encode($fields, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                file_put_contents($positionFile, $json);
                
                // Note: We don't add new field values here because:
                // 1. The positions file contains the actual PDF field names (complex)
                // 2. The stored field values use simplified names (simple)
                // 3. These are two different naming systems that need to be kept separate
                // 4. The positions file is used for positioning, the stored values for form data
                
                $fieldsAdded = 0; // No new field values added
                
                echo json_encode([
                    'success' => true,
                    'fieldsCount' => count($fields),
                    'fieldsAdded' => $fieldsAdded,
                    'positionFile' => $positionFile,
                    'message' => "Found " . count($fields) . " fields with positions, added " . $fieldsAdded . " new fields"
                ]);
                
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['error' => $e->getMessage()]);
            }
            exit;

        case 'actions/update-document-status':
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			http_response_code(405);
			echo json_encode(['error' => 'Method not allowed']);
			exit;
		}
		header('Content-Type: application/json');
		
		$documentId = sanitizeId((string)($_POST['documentId'] ?? ''));
		$status = sanitizeString((string)($_POST['status'] ?? ''));
		
		if (empty($documentId) || empty($status)) {
			http_response_code(400);
			echo json_encode(['error' => 'Document ID and status required']);
			exit;
		}
		
		$validStatuses = ['in_progress', 'review', 'completed', 'ready_to_sign'];
		if (!in_array($status, $validStatuses)) {
			http_response_code(400);
			echo json_encode(['error' => 'Invalid status']);
			exit;
		}
		
		try {
			$updated = method_exists($store, 'updateProjectDocument')
				? $store->updateProjectDocument($documentId, ['status' => $status])
				: false;
			
			if ($updated) {
				echo json_encode(['success' => true]);
			} else {
				http_response_code(404);
				echo json_encode(['error' => 'Document not found']);
			}
		} catch (Exception $e) {
			http_response_code(500);
			echo json_encode(['error' => $e->getMessage()]);
		}
		exit;

	case 'actions/sign':
		$pdId = (string)($_GET['pd'] ?? '');
		$projDoc = $store->getProjectDocumentById($pdId);
		if (!$projDoc) {
            redirectToRoute('documents', ['error' => sanitizeFlashMessage('Document not found for signing.')]);
        }
		$filename = $projDoc['outputPath'] ?? '';
		if (!$filename) {
            redirectToRoute('documents', ['error' => sanitizeFlashMessage('Generate the PDF before signing.')]);
        }
		
		$path = resolveOutputFilePath($filename);
		if ($path === null) {
            redirectToRoute('documents', ['error' => sanitizeFlashMessage('Generated PDF file is missing. Please regenerate before signing.')]);
        }
		try {
			$result = $fill->signDocument($path, ['pdId' => $pdId]);
		} catch (\Throwable $e) {
			$logger->error('PDF signing failed for pd=' . $pdId . ' path=' . $path . ' : ' . $e->getMessage());
            redirectToRoute('documents', ['error' => sanitizeFlashMessage('Signing failed. Check configuration and try again.')]);
		}
		if (method_exists($store, 'updateProjectDocument')) {
			$store->updateProjectDocument($pdId, [
				'status' => 'signed',
				'signedPath' => (string)$result['filename'],
			]);
		}
		if (method_exists($store, 'recordActivity')) {
			$store->recordActivity('document_signed', 'Document signed', [
				'projectDocumentId' => $pdId,
				'projectId' => (string)($projDoc['projectId'] ?? ''),
				'signedFile' => (string)($result['filename'] ?? '')
			]);
		}
        redirectToRoute('documents', ['success' => sanitizeFlashMessage('Document signed successfully.')]);


	case 'documents':
		$params = [];
		foreach (['error', 'success', 'saved', 'from', 'pd', 'id'] as $key) {
			if (!isset($_GET[$key])) {
				continue;
			}
			$params[$key] = sanitizeFlashMessage((string)$_GET[$key]);
		}
		redirectToRoute('forms', $params);
		exit;

	case 'forms':
		// Get all documents across all projects
		$allDocuments = [];
		$projects = $store->getProjects();
		foreach ($projects as $project) {
			$docs = $store->getProjectDocuments($project['id']);
			foreach ($docs as $doc) {
				$doc['project'] = $project;
				$doc['client'] = null;
				if (!empty($project['clientId']) && method_exists($store, 'getClient')) {
					$doc['client'] = $store->getClient($project['clientId']);
				}
				$allDocuments[] = $doc;
			}
		}
		// Sort by creation date (newest first)
		usort($allDocuments, function($a, $b) {
			return strtotime($b['createdAt'] ?? '') <=> strtotime($a['createdAt'] ?? '');
		});
		render('documents', [ 'documents' => $allDocuments, 'templates' => $templates ]);
		break;

	case 'templates':
		$allTemplates = TemplateRegistry::getAllTemplates();
		render('templates', [ 'templates' => $allTemplates ]);
		break;

	case 'template-edit':
		$templateId = (string)($_GET['id'] ?? '');
		$template = TemplateRegistry::getTemplate($templateId);
		if (!$template) {
			header('Location: ?route=templates');
			exit;
		}
		render('template-edit', [ 'template' => $template, 'templateId' => $templateId ]);
		break;

	case 'activities':
		$activities = method_exists($store, 'getRecentActivities') ? $store->getRecentActivities(100) : [];
		render('activities', ['activities' => $activities]);
		break;

	case 'bills':
		render('bills');
		break;

	case 'reports':
		render('reports');
		break;

	case 'settings':
		render('settings');
		break;

	case 'font-settings':
		render('font-settings');
		break;

	case 'support':
		render('support');
		break;

	case 'actions/list-pdfs':
		// List available PDFs for universal selection
		header('Content-Type: application/json');
		$uploadsDir = __DIR__ . '/../uploads';
		$pdfs = [];
		
		if (is_dir($uploadsDir)) {
			$files = glob($uploadsDir . '/*.pdf');
			foreach ($files as $file) {
				$base = basename($file);
				$pdfs[] = [
					'name' => $base,
					'path' => $base,
					'size' => filesize($file),
					'modified' => filemtime($file)
				];
			}
		}
		
		echo json_encode(['pdfs' => $pdfs]);
		exit;
	
	case 'test-regenerate-fl100':
		mvpAbortUnlessDevHtmlRoutes();
		// Workaround test route: Auto-regenerate FL-100 positions
		require_once __DIR__ . '/lib/pdf_field_extractor.php';
		
		$templateId = 't_fl100_gc120';
		$pdfFile = __DIR__ . '/../uploads/fl100.pdf';
		
		if (!file_exists($pdfFile)) {
			die('Error: FL-100 PDF not found at: ' . $pdfFile);
		}
		
		// Delete old positions
		$oldPositionsFile = __DIR__ . '/../data/' . $templateId . '_positions.json';
		if (file_exists($oldPositionsFile)) {
			unlink($oldPositionsFile);
		}
		
		// Copy PDF
		$targetFile = __DIR__ . '/../uploads/' . $templateId . '.pdf';
		copy($pdfFile, $targetFile);
		
		// Extract
		$extractor = new \WebPdfTimeSaver\Mvp\PdfFieldExtractor();
		$result = $extractor->extractAndGenerateBackgrounds($targetFile, $templateId, __DIR__ . '/../uploads');
		
		$fields = $result['fields'] ?? [];
		$backgrounds = $result['backgrounds'] ?? [];
		$positionFile = $result['positionFile'] ?? null;
		
		// Show results
		$success = 'Successfully extracted ' . count($fields) . ' fields and generated ' . count($backgrounds) . ' background images.';
		if ($positionFile) {
			$success .= ' Position file: ' . basename($positionFile);
		}
		header('Location: ?route=extract-fields&success=' . urlencode($success));
		exit;
	
	case 'test-w9-extract':
		mvpAbortUnlessDevHtmlRoutes();
		// Test route: Extract W-9 fields and generate background
		require_once __DIR__ . '/lib/pdf_field_extractor.php';
		
		$templateId = 't_w9';
		$pdfFile = __DIR__ . '/../uploads/w9.pdf';
		
		if (!file_exists($pdfFile)) {
			die('Error: W-9 PDF not found at: ' . $pdfFile);
		}
		
		// Delete old positions
		$oldPositionsFile = __DIR__ . '/../data/' . $templateId . '_positions.json';
		if (file_exists($oldPositionsFile)) {
			unlink($oldPositionsFile);
		}
		
		// Copy PDF
		$targetFile = __DIR__ . '/../uploads/' . $templateId . '.pdf';
		copy($pdfFile, $targetFile);
		
		// Extract
		$extractor = new \WebPdfTimeSaver\Mvp\PdfFieldExtractor();
		$result = $extractor->extractAndGenerateBackgrounds($targetFile, $templateId, __DIR__ . '/../uploads');
		
		$fields = $result['fields'] ?? [];
		$backgrounds = $result['backgrounds'] ?? [];
		$positionFile = $result['positionFile'] ?? null;
		
		// Show results
		$success = 'Successfully extracted ' . count($fields) . ' fields and generated ' . count($backgrounds) . ' background images.';
		if ($positionFile) {
			$success .= ' Position file: ' . basename($positionFile);
		}
		header('Location: ?route=extract-fields&success=' . urlencode($success));
		exit;
	
	case 'test-w9-fill':
		mvpAbortUnlessDevHtmlRoutes();
		// Test route: Fill W-9 with test data
		require_once __DIR__ . '/lib/pdf_form_filler.php';
		
		$templateId = 't_w9';
		$templateFile = __DIR__ . '/../uploads/' . $templateId . '.pdf';
		
		if (!file_exists($templateFile)) {
			die('Error: W-9 template not found. Run test-w9-extract first.');
		}
		
		// Test data for W-9 using actual field names from positions file
		$testData = [
			'topmostSubform[0].Page1[0].f1_01[0]' => 'Sample',
			'topmostSubform[0].Page1[0].f1_02[0]' => 'Sample',
			'topmostSubform[0].Page1[0].Boxes3a-b_ReadOrder[0].f1_03[0]' => '123',
			'topmostSubform[0].Page1[0].Boxes3a-b_ReadOrder[0].f1_04[0]' => 'Sample',
			'topmostSubform[0].Page1[0].f1_05[0]' => '45',
			'topmostSubform[0].Page1[0].f1_06[0]' => '6789',
			'topmostSubform[0].Page1[0].Address_ReadOrder[0].f1_07[0]' => 'Sample',
			'topmostSubform[0].Page1[0].Address_ReadOrder[0].f1_08[0]' => 'Sample',
			'topmostSubform[0].Page1[0].f1_09[0]' => 'Sample',
			'topmostSubform[0].Page1[0].f1_10[0]' => 'Sample',
			'topmostSubform[0].Page1[0].f1_11[0]' => '12',
			'topmostSubform[0].Page1[0].f1_12[0]' => '34',
			'topmostSubform[0].Page1[0].f1_13[0]' => '5678',
			'topmostSubform[0].Page1[0].f1_14[0]' => '12',
			'topmostSubform[0].Page1[0].f1_15[0]' => '3456789'
		];
		
		// Create template array as expected by fillPdfFormWithPositions
		$template = [
			'id' => $templateId,
			'pageCount' => 1
		];
		
		try {
			$filler = new \WebPdfTimeSaver\Mvp\PdfFormFiller();
			$result = $filler->fillPdfFormWithPositions($template, $testData, $templateId);
		} catch (\Throwable $e) {
			die('Error generating W-9 PDF: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
		}
		
		if ($result && isset($result['path']) && file_exists($result['path'])) {
			// Output PDF directly instead of redirecting
			header('Content-Type: application/pdf');
			header('Content-Disposition: inline; filename="w9_filled_test.pdf"');
			header('Content-Length: ' . filesize($result['path']));
			readfile($result['path']);
			exit;
		} else {
			$error = $result['error'] ?? 'Unknown error';
			if (isset($result['path']) && !file_exists($result['path'])) {
				$error .= ' (File not found: ' . $result['path'] . ')';
			}
			die('Error: Failed to fill W-9 form. ' . $error);
		}
	
	case 'extract-fields':
		render('extract_fields');
		break;

	case 'test-autofill':
		mvpAbortUnlessDevHtmlRoutes();
		render('test_autofill');
		break;

	case 'universal-processor':
		header('Location: ?route=form-management');
		exit;

	case 'form-management':
		if ($store && method_exists($store, 'cleanupGlobalFormDatabase')) {
			try { $store->cleanupGlobalFormDatabase(); } catch (\Throwable $e) { /* best-effort */ }
		}
		$clientsList = method_exists($store, 'getClients') ? $store->getClients() : [];
		$formCustomFields = method_exists($store, 'getFormCustomFields') ? $store->getFormCustomFields() : [];
		$customFieldMatchingMode = method_exists($store, 'getFormImporterMatchingMode') ? $store->getFormImporterMatchingMode() : 'exact';
		$formImporterAliases = method_exists($store, 'getFormImporterAliases') ? $store->getFormImporterAliases() : [];
		$managedFormTemplates = method_exists($store, 'getGlobalFormTemplates') ? $store->getGlobalFormTemplates() : [];
		$templatesById = [];
		foreach ($managedFormTemplates as $row) {
			if (!is_array($row)) {
				continue;
			}
			$tid = sanitizeId((string)($row['templateId'] ?? ''));
			if ($tid === '') {
				continue;
			}
			$row['templateId'] = $tid;
			$templatesById[$tid] = $row;
		}
		// Intentionally do not synthesize list entries from *_positions.json.
		// Search Forms should list only concrete template records, not stale file artifacts.
		$managedFormTemplates = array_values($templatesById);
		usort($managedFormTemplates, static function (array $a, array $b): int {
			return strcmp((string)($a['templateId'] ?? ''), (string)($b['templateId'] ?? ''));
		});
		$prefillTemplateId = sanitizeId((string)($_GET['template_id'] ?? $_GET['template'] ?? ''));
		$prefillTemplateRecord = null;
		if ($prefillTemplateId !== '' && $store && method_exists($store, 'getGlobalFormTemplate')) {
			$prefillTemplateRecord = $store->getGlobalFormTemplate($prefillTemplateId);
		}
		render('form_management', [
			'clients' => $clientsList,
			'prefillClientId' => sanitizeId((string)($_GET['client'] ?? '')),
			'formCustomFields' => $formCustomFields,
			'customFieldMatchingMode' => $customFieldMatchingMode,
			'formImporterAliases' => $formImporterAliases,
			'currentFirmId' => resolveCurrentFirmId(),
			'managedFormTemplates' => $managedFormTemplates,
			'prefillTemplateId' => $prefillTemplateId,
			'prefillTemplateRecord' => $prefillTemplateRecord,
		]);
		break;

	case 'form-new':
		if ($store && method_exists($store, 'cleanupGlobalFormDatabase')) {
			try { $store->cleanupGlobalFormDatabase(); } catch (\Throwable $e) { /* best-effort */ }
		}
		$clientsList = method_exists($store, 'getClients') ? $store->getClients() : [];
		$formCustomFields = method_exists($store, 'getFormCustomFields') ? $store->getFormCustomFields() : [];
		$customFieldMatchingMode = method_exists($store, 'getFormImporterMatchingMode') ? $store->getFormImporterMatchingMode() : 'exact';
		$formImporterAliases = method_exists($store, 'getFormImporterAliases') ? $store->getFormImporterAliases() : [];
		$managedFormTemplates = method_exists($store, 'getGlobalFormTemplates') ? $store->getGlobalFormTemplates() : [];
		$templatesById = [];
		foreach ($managedFormTemplates as $row) {
			if (!is_array($row)) {
				continue;
			}
			$tid = sanitizeId((string)($row['templateId'] ?? ''));
			if ($tid === '') {
				continue;
			}
			$row['templateId'] = $tid;
			$templatesById[$tid] = $row;
		}
		// Intentionally do not synthesize list entries from *_positions.json.
		// Search Forms should list only concrete template records, not stale file artifacts.
		$managedFormTemplates = array_values($templatesById);
		usort($managedFormTemplates, static function (array $a, array $b): int {
			return strcmp((string)($a['templateId'] ?? ''), (string)($b['templateId'] ?? ''));
		});
		$prefillTemplateId = sanitizeId((string)($_GET['template_id'] ?? $_GET['template'] ?? ''));
		$prefillTemplateRecord = null;
		if ($prefillTemplateId !== '' && $store && method_exists($store, 'getGlobalFormTemplate')) {
			$prefillTemplateRecord = $store->getGlobalFormTemplate($prefillTemplateId);
		}
		render('form_new', [
			'clients' => $clientsList,
			'prefillClientId' => sanitizeId((string)($_GET['client'] ?? '')),
			'formCustomFields' => $formCustomFields,
			'customFieldMatchingMode' => $customFieldMatchingMode,
			'formImporterAliases' => $formImporterAliases,
			'currentFirmId' => resolveCurrentFirmId(),
			'managedFormTemplates' => $managedFormTemplates,
			'prefillTemplateId' => $prefillTemplateId,
			'prefillTemplateRecord' => $prefillTemplateRecord,
		]);
		break;

	case 'form-sets-manager':
		$formSetPrefillId = sanitizeId((string)($_GET['set_id'] ?? ''));
		$formSetImportTemplate = sanitizeId((string)($_GET['imported_template_id'] ?? ''));
		render('form_sets_manager', [
			'prefillFormSetId' => $formSetPrefillId,
			'importedTemplateId' => $formSetImportTemplate,
		]);
		break;

	case 'field-manager':
		$fieldManagerFields = method_exists($store, 'getFieldManagerCustomFields') ? $store->getFieldManagerCustomFields() : [];
		render('field_manager', [
			'fieldManagerFields' => $fieldManagerFields,
			'fieldTypes' => fieldManagerAllowedTypes(),
		]);
		break;

	case 'alias-manager':
		header('Location: ?route=field-manager');
		exit;

	case 'firm-defaults':
		$firmDefaultFields = method_exists($store, 'getFirmDefaultFields') ? $store->getFirmDefaultFields() : [];
		$firmDefaultMatchingMode = method_exists($store, 'getFormImporterMatchingMode') ? $store->getFormImporterMatchingMode() : 'exact';
		$attorneyFieldRows = method_exists($store, 'getFieldManagerCustomFields') ? $store->getFieldManagerCustomFields('attorney') : [];
		$attorneyRosterInitial = [];
		if (method_exists($store, 'getAttorneys')) {
			$attorneyRosterInitial = array_map(static function (array $row) use ($attorneyFieldRows): array {
				return formatAttorneyApiRow($row, $attorneyFieldRows);
			}, (array)$store->getAttorneys());
		}
		render('firm_defaults', [
			'firmDefaultFields' => $firmDefaultFields,
			'firmDefaultMatchingMode' => $firmDefaultMatchingMode,
			'attorneyFieldRows' => $attorneyFieldRows,
			'attorneyRosterInitial' => $attorneyRosterInitial,
		]);
		break;
		
	case 'actions/universal-diagnostics':
		handleUniversalDiagnostics();
		break;

	case 'diagnostics':
		render('diagnostics');
		break;
		
	case 'debug-extraction':
		mvpAbortUnlessDevHtmlRoutes();
		require_once __DIR__ . '/views/debug_extraction.php';
		break;
		
	case 'test-extraction-results':
		mvpAbortUnlessDevHtmlRoutes();
		require_once __DIR__ . '/views/test_extraction_results.php';
		break;
		
	case 'show-preview-working':
		mvpAbortUnlessDevHtmlRoutes();
		require_once __DIR__ . '/views/show_preview_working.php';
		break;
	
	case 'pdf-lib-demo':
		mvpAbortUnlessDevHtmlRoutes();
		render('pdf_lib_demo');
		break;
	
	case 'actions/universal-process':
		handleUniversalProcess();
		break;

    case 'actions/universal-generate-test':
        mvpAbortUnlessDevHtmlRoutes();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        header('Content-Type: application/json');

        try {
            $templateId = sanitizeId((string)($_POST['template_id'] ?? ''));
            if ($templateId === '') {
                throw new \RuntimeException('Template ID is required.');
            }

            $positionsFile = __DIR__ . '/../data/' . $templateId . '_positions.json';
            if (!file_exists($positionsFile)) {
                throw new \RuntimeException('Positions file not found for template: ' . $templateId);
            }

            $positions = json_decode((string)file_get_contents($positionsFile), true);
            if (!is_array($positions) || empty($positions)) {
                throw new \RuntimeException('Positions data is empty or invalid.');
            }

            // Normalize legacy list-format positions into keyed map format expected by filler/mappers.
            if (array_is_list($positions)) {
                $normalized = [];
                foreach ($positions as $idx => $position) {
                    if (!is_array($position)) {
                        continue;
                    }
                    $name = (string)($position['name'] ?? $position['fieldName'] ?? ('field_' . $idx));
                    $name = trim($name);
                    if ($name === '') {
                        $name = 'field_' . $idx;
                    }
                    $normalized[$name] = $position;
                }

                if (empty($normalized)) {
                    throw new \RuntimeException('Could not normalize positions to named field map.');
                }

                $positions = $normalized;
                file_put_contents($positionsFile, json_encode($positions, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            }

            $testData = buildUniversalTestDataFromPositions($positions);

            $pageCount = 1;
            foreach ($positions as $position) {
                $fieldPage = (int)($position['page'] ?? 1);
                if ($fieldPage > $pageCount) {
                    $pageCount = $fieldPage;
                }
            }

            require_once __DIR__ . '/lib/pdf_form_filler.php';
            $filler = new \WebPdfTimeSaver\Mvp\PdfFormFiller(__DIR__ . '/../output', __DIR__ . '/../uploads', $logger);
            $filler->setContext(['test' => true, 'method' => 'universal-processor']);

            $template = [
                'id' => $templateId,
                'pageCount' => $pageCount
            ];

            $result = $filler->fillPdfFormWithPositions($template, $testData, $templateId);
            if (($result['success'] ?? false) !== true || empty($result['filename'])) {
                throw new \RuntimeException('Failed to generate filled PDF.');
            }

            echo json_encode([
                'success' => true,
                'message' => 'Test form PDF generated successfully.',
                'file' => $result['filename'],
                'downloadUrl' => '?route=actions/download-test-pdf&file=' . urlencode((string)$result['filename']),
                'fieldsPlaced' => $result['fields_placed'] ?? null,
                'pages' => $result['pages'] ?? $pageCount
            ]);
        } catch (\Throwable $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;

	case 'actions/universal-generate':
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			http_response_code(405);
			header('Content-Type: application/json');
			echo json_encode(['success' => false, 'message' => 'Method not allowed']);
			exit;
		}

		header('Content-Type: application/json');
        $requestId = 'ug_' . date('Ymd_His') . '_' . substr(md5((string)microtime(true) . (string)mt_rand()), 0, 8);

		try {
			$templateId = sanitizeId((string)($_POST['template_id'] ?? ''));
			if ($templateId === '') {
				throw new \RuntimeException('Template ID is required.');
			}
            logUniversalGenerateEvent($logger ?? null, 'info', 'request-start', [
                'requestId' => $requestId,
                'templateId' => $templateId,
                'showSampleData' => (string)($_POST['show_sample_data'] ?? ''),
                'valuesBytes' => strlen((string)($_POST['values'] ?? '')),
                'remoteAddr' => (string)($_SERVER['REMOTE_ADDR'] ?? ''),
                'userAgent' => substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 180),
            ]);

			$positionsFile = __DIR__ . '/../data/' . $templateId . '_positions.json';
			if (!file_exists($positionsFile)) {
				throw new \RuntimeException('Positions file not found for template: ' . $templateId);
			}

			$positions = json_decode((string)file_get_contents($positionsFile), true);
			if (!is_array($positions) || empty($positions)) {
				throw new \RuntimeException('Positions data is empty or invalid.');
			}

			if (array_is_list($positions)) {
				$normalized = [];
				foreach ($positions as $idx => $position) {
					if (!is_array($position)) {
						continue;
					}
					$name = (string)($position['name'] ?? $position['fieldName'] ?? ('field_' . $idx));
					$name = trim($name);
					if ($name === '') {
						$name = 'field_' . $idx;
					}
					$normalized[$name] = $position;
				}
				if (empty($normalized)) {
					throw new \RuntimeException('Could not normalize positions to named field map.');
				}
				$positions = $normalized;
				file_put_contents($positionsFile, json_encode($positions, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
			}

			$userValues = json_decode((string)($_POST['values'] ?? '{}'), true);
			if (!is_array($userValues)) {
				$userValues = [];
			}
            $userValuesCount = count($userValues);
			$fillValues = [];
			foreach ($userValues as $fieldKey => $userValue) {
				if (!is_string($fieldKey) || trim($fieldKey) === '') {
					continue;
				}
				if (!isUniversalDemoValueProvided($userValue)) {
					continue;
				}
				$fillValues[$fieldKey] = is_string($userValue) ? trim($userValue) : $userValue;
			}

            $matchExplain = [];
			if (isset($store) && $store && method_exists($store, 'getFormCustomFields')) {
				$matchingMode = method_exists($store, 'getFormImporterMatchingMode') ? $store->getFormImporterMatchingMode() : 'exact';
				$aliasEntries = method_exists($store, 'getFormImporterAliases') ? $store->getFormImporterAliases() : [];
				$fillValues = applyUniversalCustomFieldOverrides(
                    $fillValues,
                    $positions,
                    $store->getFormCustomFields(),
                    $matchingMode,
                    $aliasEntries,
                    $templateId,
                    $matchExplain
                );
                persistFormImporterAliasStatsFromExplain($store, $matchExplain);
			}

			$pageCount = 1;
			foreach ($positions as $position) {
				$fieldPage = (int)($position['page'] ?? 1);
				if ($fieldPage > $pageCount) {
					$pageCount = $fieldPage;
				}
			}

			require_once __DIR__ . '/lib/pdf_form_filler.php';
			$filler = new \WebPdfTimeSaver\Mvp\PdfFormFiller(__DIR__ . '/../output', __DIR__ . '/../uploads', $logger);
			$filler->setContext(['test' => false, 'method' => 'universal-processor']);

			$template = [
				'id' => $templateId,
				'pageCount' => $pageCount
			];

			$result = $filler->fillPdfFormWithPositions($template, $fillValues, $templateId);
			if (($result['success'] ?? false) !== true || empty($result['filename'])) {
				throw new \RuntimeException('Failed to generate filled PDF.');
			}
            logUniversalGenerateEvent($logger ?? null, 'info', 'request-success', [
                'requestId' => $requestId,
                'templateId' => $templateId,
                'positionsCount' => count($positions),
                'userValuesCount' => $userValuesCount,
                'fillValuesCount' => count($fillValues),
                'outputFile' => (string)($result['filename'] ?? ''),
                'fieldsPlaced' => (int)($result['fields_placed'] ?? 0),
                'pages' => (int)($result['pages'] ?? $pageCount),
            ]);

			echo json_encode([
				'success' => true,
				'message' => 'Actual form PDF generated successfully.',
				'file' => $result['filename'],
				'downloadUrl' => '?route=actions/download-universal-pdf&file=' . urlencode((string)$result['filename']),
				'fieldsPlaced' => $result['fields_placed'] ?? null,
				'pages' => $result['pages'] ?? $pageCount,
                'matchExplain' => $matchExplain,
                'requestId' => $requestId,
			]);
		} catch (\Throwable $e) {
            logUniversalGenerateEvent($logger ?? null, 'error', 'request-failed', [
                'requestId' => $requestId,
                'templateId' => isset($templateId) ? (string)$templateId : '',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'traceTop' => substr((string)$e->getTraceAsString(), 0, 2000),
                'positionsFileExists' => isset($positionsFile) ? file_exists((string)$positionsFile) : false,
            ]);
			http_response_code(400);
			echo json_encode([
				'success' => false,
				'message' => $e->getMessage(),
                'requestId' => $requestId,
			]);
		}
		exit;

	case 'actions/extract-pdf-fields':
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ?route=extract-fields'); exit; }
		
		require_once __DIR__ . '/lib/pdf_field_extractor.php';
		
		$error = null;
		$success = null;
		$fields = [];
		$positionFile = null;
		
		try {
			$templateId = sanitizeId((string)($_POST['template_id'] ?? ''));
			if (empty($templateId)) {
				throw new \Exception('Please provide a valid template ID');
			}
			
			// Universal solution: Handle both uploaded file OR selected existing PDF
			$permanentFile = null;
			
			// Option 1: Selected existing PDF (basename only from list-pdfs; legacy absolute paths still accepted)
			if (!empty($_POST['selected_pdf_path'])) {
				$uploadsDir = realpath(__DIR__ . '/../uploads');
				if ($uploadsDir === false) {
					throw new \Exception('Uploads directory not available');
				}
				$selectedPath = resolveUploadsPdfSelection((string)$_POST['selected_pdf_path'], $uploadsDir);
				
				if ($selectedPath !== null) {
					$permanentFile = __DIR__ . '/../uploads/' . $templateId . '.pdf';
					if (!copy($selectedPath, $permanentFile)) {
						throw new \Exception('Failed to copy selected PDF file');
					}
				} else {
					throw new \Exception('Invalid PDF file selected');
				}
			}
			// Option 2: Uploaded new file
			elseif (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
				$uploadedFile = $_FILES['pdf_file'];
				$permanentFile = __DIR__ . '/../uploads/' . $templateId . '.pdf';
				
				if (!move_uploaded_file($uploadedFile['tmp_name'], $permanentFile)) {
					throw new \Exception('Failed to upload file');
				}
			} else {
				throw new \Exception('Please either select an existing PDF or upload a new one');
			}
			
			// Delete old positions file for this template (fresh start)
			$oldPositionsFile = __DIR__ . '/../data/' . $templateId . '_positions.json';
			if (file_exists($oldPositionsFile)) {
				unlink($oldPositionsFile);
			}
			
			// Use hybrid approach: extract fields AND generate background images
			$extractor = new \WebPdfTimeSaver\Mvp\PdfFieldExtractor();
			$result = $extractor->extractAndGenerateBackgrounds(
				$permanentFile, 
				$templateId, 
				__DIR__ . '/uploads'  // Save backgrounds in mvp/uploads for Populate preview
			);
			
			$fields = $result['fields'];
			$backgrounds = $result['backgrounds'];
			$positionFile = $result['positionFile'];
			
			if (empty($fields)) {
				// Even if no fields extracted, backgrounds might still be generated
				if (!empty($backgrounds)) {
					$success = 'Generated ' . count($backgrounds) . ' background images. ' .
					          'Field extraction failed (PDF may be encrypted), but you can still use manual positioning.';
				} else {
					throw new \Exception('Failed to extract fields or generate backgrounds. PDF may be corrupted or incompatible.');
				}
			} else {
				$successMsg = 'Successfully extracted ' . count($fields) . ' fields';
				if (!empty($backgrounds)) {
					$successMsg .= ' and generated ' . count($backgrounds) . ' background images';
				}
				$successMsg .= '!';
				$success = $successMsg;
			}
			
		} catch (\Exception $e) {
			$error = $e->getMessage();
		}
		
		render('extract_fields', [
			'error' => $error,
			'success' => $success,
			'fields' => $fields,
			'positionFile' => $positionFile,
			'backgrounds' => $backgrounds ?? []
		]);
		exit;

	case 'actions/test-autofill':
		mvpAbortUnlessDevHtmlRoutes();
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ?route=test-autofill'); exit; }
		
		require_once __DIR__ . '/lib/pdf_field_extractor.php';
		require_once __DIR__ . '/lib/pdf_form_filler.php';
		
		$error = null;
		$success = null;
		$extractionResult = null;
		$generatedPdf = null;
		
		try {
			$pdfFile = __DIR__ . '/../uploads/fl100.pdf';
			$templateId = 't_fl100_gc120';
			
			if (!file_exists($pdfFile)) {
				throw new \Exception('FL-100 PDF not found at: ' . $pdfFile);
			}
			
			$logger->info('Test autofill: Starting extraction', ['file' => $pdfFile]);
			
			// Step 1: Extract fields and generate backgrounds
			$extractor = new \WebPdfTimeSaver\Mvp\PdfFieldExtractor();
			$extractionResult = $extractor->extractAndGenerateBackgrounds(
				$pdfFile,
				$templateId,
				__DIR__ . '/uploads'
			);
			
			$logger->info('Test autofill: Extraction complete', [
				'fields' => count($extractionResult['fields']),
				'backgrounds' => count($extractionResult['backgrounds'])
			]);
			
			// Step 2: Create test data
			require_once __DIR__ . '/lib/fl100_test_data_generator.php';
			$testData = \WebPdfTimeSaver\Mvp\FL100TestDataGenerator::generateCompleteTestData();
			
			// Step 3: Load the template
			$template = $templates[$templateId] ?? null;
			if (!$template) {
				throw new \Exception('Template not found: ' . $templateId);
			}
			
			// Step 4: Generate PDF using positioned fields
			$filler = new \WebPdfTimeSaver\Mvp\PdfFormFiller(__DIR__ . '/../output', __DIR__ . '/../uploads', $logger);
			$filler->setContext(['test' => true, 'method' => 'hybrid-autofill']);
			
			// Use the new method with extracted positions
			$generatedPdf = $filler->fillPdfFormWithPositions($template, $testData, $templateId);
			
			$logger->info('Test autofill: PDF generated', $generatedPdf);
			
			$success = 'Successfully generated test PDF using auto-detected field positions!';
			
		} catch (\Exception $e) {
			$error = $e->getMessage();
			$logger->error('Test autofill failed: ' . $e->getMessage());
		}
		
		render('test_autofill', [
			'error' => $error,
			'success' => $success,
			'extractionResult' => $extractionResult,
			'generatedPdf' => $generatedPdf
		]);
		exit;

	case 'actions/download-test-pdf':
		mvpAbortUnlessDevHtmlRoutes();
		$filename = $_GET['file'] ?? '';
		if (!$filename) {
			header('Location: ?route=test-autofill');
			exit;
		}
		
		$outputDir = realpath(__DIR__ . '/../output');
		$path = $outputDir . DIRECTORY_SEPARATOR . basename($filename);
		
		if (!file_exists($path)) {
			header('Location: ?route=test-autofill');
			exit;
		}
		
		header('Content-Type: application/pdf');
		header('Content-Disposition: attachment; filename="' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename) . '"');
		readfile($path);
		exit;

	case 'actions/download-universal-pdf':
		$filename = $_GET['file'] ?? '';
		if (!$filename) {
			http_response_code(400);
			echo 'Missing file parameter';
			exit;
		}

		$path = resolveOutputFilePath((string)$filename);
		if ($path === null || !file_exists($path)) {
			http_response_code(404);
			echo 'File not found';
			exit;
		}

		header('Content-Type: application/pdf');
		header('Content-Disposition: attachment; filename="' . preg_replace('/[^a-zA-Z0-9._-]/', '_', (string)$filename) . '"');
		readfile($path);
		exit;

	case 'actions/template-finish-bundle':
		handleTemplateFinishBundleDownload();
		break;

	case 'actions/form-template-pdf':
		handleFormTemplatePdfDownload();
		break;

	case 'actions/form-template-editor-data':
		handleFormTemplateEditorData();
		break;

	case 'actions/uploads-asset':
		handleUploadsAssetDownload();
		break;

	case 'verification':
		mvpAbortUnlessDevHtmlRoutes();
		render('automated_verification');
		break;
	
	case 'automated-verify':
		mvpAbortUnlessDevHtmlRoutes();
		require_once __DIR__ . '/lib/automated_verification_pipeline.php';
		require_once __DIR__ . '/lib/fl100_test_data_generator.php';
		
		$templateId = sanitizeId((string)($_GET['template_id'] ?? 't_fl100_gc120'));
		
		$pipeline = new \WebPdfTimeSaver\Mvp\AutomatedVerificationPipeline();
		$results = $pipeline->verify($templateId);
		
		header('Content-Type: application/json');
		echo json_encode($results, JSON_PRETTY_PRINT);
		exit;

	default:
		header('Location: ?route=dashboard');
		exit;
}


