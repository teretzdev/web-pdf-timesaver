<?php
/**
 * Error Diagnostics Page
 * Shows diagnostic information when the application encounters errors
 */

// Get error details from GET parameters or session
$errorType = $_GET['type'] ?? 'unknown';
$errorMessage = $_GET['message'] ?? 'An error occurred';
$errorFile = $_GET['file'] ?? '';
$errorLine = $_GET['line'] ?? '';

// Define ALL requirements
$requirements = [
    'PHP Version' => [
        'required' => '7.4 or higher',
        'current' => PHP_VERSION,
        'met' => version_compare(PHP_VERSION, '7.4.0', '>='),
        'fix' => 'Upgrade PHP to version 7.4 or higher'
    ],
    'Composer Dependencies' => [
        'required' => 'vendor/autoload.php',
        'current' => file_exists(__DIR__ . '/../vendor/autoload.php') ? 'Present' : 'MISSING',
        'met' => file_exists(__DIR__ . '/../vendor/autoload.php'),
        'fix' => 'Run: composer install (in project root directory)'
    ],
    'Data Directory' => [
        'required' => 'Must exist and be writable',
        'current' => (file_exists(__DIR__ . '/../data') && is_writable(__DIR__ . '/../data')) ? 'OK' : 'MISSING/NOT WRITABLE',
        'met' => file_exists(__DIR__ . '/../data') && is_writable(__DIR__ . '/../data'),
        'fix' => 'Create directory: mkdir -p ' . dirname(__DIR__) . '/data && chmod 755 ' . dirname(__DIR__) . '/data'
    ],
    'Output Directory' => [
        'required' => 'Must exist and be writable',
        'current' => (file_exists(__DIR__ . '/../output') && is_writable(__DIR__ . '/../output')) ? 'OK' : 'MISSING/NOT WRITABLE',
        'met' => file_exists(__DIR__ . '/../output') && is_writable(__DIR__ . '/../output'),
        'fix' => 'Create directory: mkdir -p ' . dirname(__DIR__) . '/output && chmod 755 ' . dirname(__DIR__) . '/output'
    ],
    'Uploads Directory' => [
        'required' => 'Must exist and be writable',
        'current' => (file_exists(__DIR__ . '/../uploads') && is_writable(__DIR__ . '/../uploads')) ? 'OK' : 'MISSING/NOT WRITABLE',
        'met' => file_exists(__DIR__ . '/../uploads') && is_writable(__DIR__ . '/../uploads'),
        'fix' => 'Create directory: mkdir -p ' . dirname(__DIR__) . '/uploads && chmod 755 ' . dirname(__DIR__) . '/uploads'
    ],
    'JSON Extension' => [
        'required' => 'php-json',
        'current' => extension_loaded('json') ? 'Loaded' : 'MISSING',
        'met' => extension_loaded('json'),
        'fix' => 'Install PHP JSON extension: apt-get install php-json (or equivalent)'
    ],
    'MBString Extension' => [
        'required' => 'php-mbstring',
        'current' => extension_loaded('mbstring') ? 'Loaded' : 'MISSING',
        'met' => extension_loaded('mbstring'),
        'fix' => 'Install PHP MBString extension: apt-get install php-mbstring'
    ],
    'FileInfo Extension' => [
        'required' => 'php-fileinfo',
        'current' => extension_loaded('fileinfo') ? 'Loaded' : 'MISSING',
        'met' => extension_loaded('fileinfo'),
        'fix' => 'Install PHP FileInfo extension: apt-get install php-fileinfo'
    ],
    'GD Extension' => [
        'required' => 'php-gd (for image processing)',
        'current' => extension_loaded('gd') ? 'Loaded' : 'MISSING',
        'met' => extension_loaded('gd'),
        'fix' => 'Install PHP GD extension: apt-get install php-gd'
    ],
    'ZIP Extension' => [
        'required' => 'php-zip (for PDF processing)',
        'current' => extension_loaded('zip') ? 'Loaded' : 'MISSING',
        'met' => extension_loaded('zip'),
        'fix' => 'Install PHP ZIP extension: apt-get install php-zip'
    ],
    'Composer Executable' => [
        'required' => 'composer command available',
        'current' => (shell_exec('which composer 2>/dev/null') !== null) ? 'Available' : 'NOT FOUND',
        'met' => shell_exec('which composer 2>/dev/null') !== null,
        'fix' => 'Install Composer: curl -sS https://getcomposer.org/installer | php && mv composer.phar /usr/local/bin/composer'
    ],
];

// Check critical files for more details
$criticalFiles = [
    'vendor/autoload.php' => __DIR__ . '/../vendor/autoload.php',
    'composer.json' => __DIR__ . '/../composer.json',
    'data/mvp.json' => __DIR__ . '/../data/mvp.json',
];

$fileStatus = [];
foreach ($criticalFiles as $name => $path) {
    $fileStatus[$name] = [
        'path' => $path,
        'exists' => file_exists($path),
        'readable' => file_exists($path) ? is_readable($path) : false,
        'writable' => file_exists($path) ? is_writable($path) : false,
    ];
}

// Collect diagnostic information
$diagnostics = [
    'PHP Version' => PHP_VERSION,
    'PHP SAPI' => php_sapi_name(),
    'Server Software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'Document Root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Not set',
    'Script Name' => $_SERVER['SCRIPT_NAME'] ?? 'Not set',
    'Request URI' => $_SERVER['REQUEST_URI'] ?? 'Not set',
    'Server Name' => $_SERVER['SERVER_NAME'] ?? 'Not set',
    'Script Filename' => $_SERVER['SCRIPT_FILENAME'] ?? 'Not set',
    'PHP Runtime SAPI' => php_sapi_name(),
];

// Find missing requirements
$missingRtequirements = [];
foreach ($requirements as $name => $req) {
    if (!$req['met']) {
        $missingRequirements[$name] = $req;
    }
}

// Check if we can detect base path (standalone check, doesn't require getBasePath function)
$detectedBasePath = 'Error detecting';
try {
    // Try to detect base path independently
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? '';
    if (!empty($scriptName)) {
        $basePath = dirname(dirname($scriptName));
        $basePath = str_replace('\\', '/', $basePath);
        $basePath = rtrim($basePath, '/');
        
        if (empty($basePath) || $basePath === '.' || $basePath === '/' || $basePath === '/mvp') {
            $detectedBasePath = '/';
        } else {
            $detectedBasePath = $basePath . '/';
        }
    } else {
        $detectedBasePath = 'Could not detect from script name';
    }
    
    // Also try via getBasePath if available
    if (function_exists('getBasePath')) {
        $detectedBasePath = getBasePath();
    }
} catch (Exception $e) {
    $detectedBasePath = 'Error: ' . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Error - Diagnostics</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f5f6fa;
            color: #2c3e50;
            line-height: 1.6;
            padding: 20px;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
        }
        
        h1 {
            color: #dc3545;
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .error-type {
            background: #fee;
            border: 2px solid #dc3545;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 30px;
        }
        
        .error-type h2 {
            color: #c00;
            font-size: 18px;
            margin-bottom: 8px;
        }
        
        .error-type p {
            margin: 5px 0;
            color: #333;
        }
        
        .section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .section:last-child {
            border-bottom: none;
        }
        
        .section h3 {
            color: #495057;
            font-size: 20px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #007bff;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .info-label {
            font-weight: 600;
            color: #6c757d;
        }
        
        .info-value {
            color: #212529;
            word-break: break-all;
        }
        
        .status-ok {
            color: #28a745;
            font-weight: 600;
        }
        
        .status-error {
            color: #dc3545;
            font-weight: 600;
        }
        
        .code-block {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 12px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            overflow-x: auto;
            margin-top: 5px;
        }
        
        .actions {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #eee;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-right: 10px;
            margin-bottom: 10px;
        }
        
        .btn:hover {
            background: #0056b3;
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
        
        .warning strong {
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>⚠️ Application Error - Missing Requirements</h1>
        
        <?php if (!empty($missingRequirements)): ?>
        <div class="error-type" style="background: #ffe6e6; border-color: #dc3545; margin-bottom: 20px;">
            <h2 style="color: #c00; font-size: 22px;">🚨 CRITICAL: Missing Requirements</h2>
            <p style="font-size: 16px; margin: 15px 0;"><strong>The following requirements are MISSING and must be fixed:</strong></p>
            <ul style="margin-left: 20px; font-size: 15px;">
                <?php foreach ($missingRequirements as $name => $req): ?>
                    <li style="margin: 10px 0; padding: 10px; background: white; border-left: 4px solid #dc3545; border-radius: 4px;">
                        <strong style="color: #c00;"><?= htmlspecialchars($name) ?></strong><br>
                        <span style="color: #666;">Required: <?= htmlspecialchars($req['required']) ?></span><br>
                        <span style="color: #c00;">Current: <?= htmlspecialchars($req['current']) ?></span><br>
                        <span style="color: #007bff; font-weight: 600;">🔧 Fix: <?= htmlspecialchars($req['fix']) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <div class="error-type">
            <h2>Error Details</h2>
            <p><strong>Error Type:</strong> <?= htmlspecialchars($errorType) ?></p>
            <?php if ($errorMessage !== 'An error occurred'): ?>
                <p><strong>Message:</strong> <?= htmlspecialchars($errorMessage) ?></p>
            <?php endif; ?>
            <?php if ($errorFile): ?>
                <p><strong>File:</strong> <?= htmlspecialchars($errorFile) ?></p>
            <?php endif; ?>
            <?php if ($errorLine): ?>
                <p><strong>Line:</strong> <?= htmlspecialchars($errorLine) ?></p>
            <?php endif; ?>
        </div>
        
        <div class="section">
            <h3>✅ All Requirements Checklist</h3>
            <?php foreach ($requirements as $name => $req): ?>
                <div class="info-grid" style="margin-bottom: 12px; padding: 10px; background: <?= $req['met'] ? '#d4edda' : '#f8d7da' ?>; border-radius: 4px; border-left: 4px solid <?= $req['met'] ? '#28a745' : '#dc3545' ?>;">
                    <div class="info-label" style="font-weight: 700;">
                        <?php if ($req['met']): ?>
                            ✅ <?= htmlspecialchars($name) ?>
                        <?php else: ?>
                            ❌ <?= htmlspecialchars($name) ?>
                        <?php endif; ?>
                    </div>
                    <div class="info-value">
                        <div><strong>Required:</strong> <?= htmlspecialchars($req['required']) ?></div>
                        <div><strong>Current:</strong> <span style="color: <?= $req['met'] ? '#28a745' : '#dc3545' ?>;"><?= htmlspecialchars($req['current']) ?></span></div>
                        <?php if (!$req['met']): ?>
                            <div style="margin-top: 8px; padding: 8px; background: white; border-radius: 4px;">
                                <strong style="color: #007bff;">🔧 How to Fix:</strong><br>
                                <code style="display: block; margin-top: 5px; padding: 5px; background: #f8f9fa;"><?= htmlspecialchars($req['fix']) ?></code>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="section">
            <h3>🔍 Server Information</h3>
            <?php foreach ($diagnostics as $label => $value): ?>
                <div class="info-grid">
                    <div class="info-label"><?= htmlspecialchars(str_replace('_', ' ', ucwords($label, '_'))) ?>:</div>
                    <div class="info-value"><?= htmlspecialchars((string)$value) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="section">
            <h3>📁 Critical Files & Directories</h3>
            <?php foreach ($fileStatus as $name => $status): ?>
                <div class="info-grid">
                    <div class="info-label"><?= htmlspecialchars($name) ?>:</div>
                    <div class="info-value">
                        <span class="<?= $status['exists'] ? 'status-ok' : 'status-error' ?>">
                            <?= $status['exists'] ? '✓ Exists' : '✗ Missing' ?>
                        </span>
                        <?php if ($status['exists']): ?>
                            <br>
                            <span class="<?= $status['readable'] ? 'status-ok' : 'status-error' ?>">
                                <?= $status['readable'] ? '✓ Readable' : '✗ Not Readable' ?>
                            </span>
                            <?php if (is_dir($status['path'])): ?>
                                <span class="<?= $status['writable'] ? 'status-ok' : 'status-error' ?>">
                                    <?= $status['writable'] ? '✓ Writable' : '✗ Not Writable' ?>
                                </span>
                            <?php endif; ?>
                        <?php endif; ?>
                        <div class="code-block"><?= htmlspecialchars($status['path']) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="section">
            <h3>📁 Critical Files Status</h3>
            <?php foreach ($fileStatus as $name => $status): ?>
                <div class="info-grid">
                    <div class="info-label"><?= htmlspecialchars($name) ?>:</div>
                    <div class="info-value">
                        <span class="<?= $status['exists'] ? 'status-ok' : 'status-error' ?>">
                            <?= $status['exists'] ? '✓ Exists' : '✗ MISSING' ?>
                        </span>
                        <?php if ($status['exists']): ?>
                            <br>
                            <span class="<?= $status['readable'] ? 'status-ok' : 'status-error' ?>">
                                <?= $status['readable'] ? '✓ Readable' : '✗ Not Readable' ?>
                            </span>
                            <?php if (is_file($status['path'])): ?>
                                <span class="<?= $status['writable'] ? 'status-ok' : 'status-error' ?>">
                                    <?= $status['writable'] ? '✓ Writable' : '✗ Not Writable' ?>
                                </span>
                            <?php endif; ?>
                        <?php endif; ?>
                        <div class="code-block"><?= htmlspecialchars($status['path']) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="section">
            <h3>🛣️ Path Detection</h3>
            <div class="info-grid">
                <div class="info-label">Detected Base Path:</div>
                <div class="info-value">
                    <span class="status-ok"><?= htmlspecialchars($detectedBasePath) ?></span>
                </div>
            </div>
        </div>
        
        <div class="section">
            <h3>🔧 Complete Installation Steps</h3>
            <div class="warning" style="background: #e7f3ff; border-color: #0066cc;">
                <strong style="color: #004085; font-size: 18px;">Step-by-Step Fix Guide:</strong>
                <ol style="margin-top: 15px; margin-left: 20px; line-height: 1.8;">
                    <li><strong>Install Composer (if missing):</strong><br>
                        <code style="display: block; margin: 5px 0; padding: 8px; background: #f8f9fa;">curl -sS https://getcomposer.org/installer | php && mv composer.phar /usr/local/bin/composer</code>
                    </li>
                    <li><strong>Navigate to project directory:</strong><br>
                        <code style="display: block; margin: 5px 0; padding: 8px; background: #f8f9fa;">cd <?= htmlspecialchars(dirname(__DIR__)) ?></code>
                    </li>
                    <li><strong>Install dependencies:</strong><br>
                        <code style="display: block; margin: 5px 0; padding: 8px; background: #f8f9fa;">composer install</code>
                    </li>
                    <li><strong>Create required directories:</strong><br>
                        <code style="display: block; margin: 5px 0; padding: 8px; background: #f8f9fa;">mkdir -p data output uploads logs && chmod 755 data output uploads logs</code>
                    </li>
                    <li><strong>Install PHP extensions (if missing):</strong><br>
                        <code style="display: block; margin: 5px 0; padding: 8px; background: #f8f9fa;">apt-get install php-json php-mbstring php-fileinfo php-gd php-zip</code>
                        <em style="color: #666;">Or for other systems: yum install php-json php-mbstring php-fileinfo php-gd php-zip</em>
                    </li>
                    <li><strong>Check PHP-FPM is running (for 502 errors):</strong><br>
                        <code style="display: block; margin: 5px 0; padding: 8px; background: #f8f9fa;">systemctl status php-fpm</code>
                        <code style="display: block; margin: 5px 0; padding: 8px; background: #f8f9fa;">systemctl start php-fpm</code>
                    </li>
                    <li><strong>Verify PHP version:</strong><br>
                        <code style="display: block; margin: 5px 0; padding: 8px; background: #f8f9fa;">php -v</code>
                        <em style="color: #666;">Must be PHP 7.4 or higher</em>
                    </li>
                </ol>
            </div>
        </div>
        
        <div class="section">
            <h3>⚠️ Common Error Solutions</h3>
            <div class="warning">
                <ul style="margin-top: 10px; margin-left: 20px; line-height: 1.8;">
                    <li><strong>502 Bad Gateway:</strong> PHP-FPM not running or wrong socket path in nginx config</li>
                    <li><strong>500 Internal Server Error:</strong> Check PHP error logs: <code>tail -f /var/log/php-fpm/error.log</code></li>
                    <li><strong>Permission Denied:</strong> Check file permissions: <code>chmod -R 755 <?= htmlspecialchars(dirname(__DIR__)) ?></code></li>
                    <li><strong>Class not found:</strong> Run <code>composer install</code> to install dependencies</li>
                </ul>
            </div>
        </div>
        
        <div class="actions">
            <a href="?route=dashboard" class="btn">Try Dashboard Again</a>
            <a href="/" class="btn btn-secondary">Go to Homepage</a>
        </div>
        
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; color: #6c757d; font-size: 12px;">
            <p>This diagnostic page provides information to help troubleshoot the application error. 
            Share this information with your system administrator or support team.</p>
        </div>
    </div>
</body>
</html>
