<?php
/**
 * Standalone Error Checker - Can run independently without dependencies
 * Access directly: https://pdftimesaver.desktopmasters.com/error-check.php
 */

// Get error details from GET parameters
$errorType = $_GET['type'] ?? 'Application Error';
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
        'current' => file_exists(__DIR__ . '/vendor/autoload.php') ? 'Present' : 'MISSING',
        'met' => file_exists(__DIR__ . '/vendor/autoload.php'),
        'fix' => 'Run: cd ' . htmlspecialchars(__DIR__) . ' && composer install'
    ],
    'Data Directory' => [
        'required' => 'Must exist and be writable',
        'path' => __DIR__ . '/data',
        'current' => (file_exists(__DIR__ . '/data') && is_writable(__DIR__ . '/data')) ? 'OK' : 'MISSING/NOT WRITABLE',
        'met' => file_exists(__DIR__ . '/data') && is_writable(__DIR__ . '/data'),
        'fix' => 'Run: mkdir -p ' . htmlspecialchars(__DIR__) . '/data && chmod 755 ' . htmlspecialchars(__DIR__) . '/data'
    ],
    'Output Directory' => [
        'required' => 'Must exist and be writable',
        'path' => __DIR__ . '/output',
        'current' => (file_exists(__DIR__ . '/output') && is_writable(__DIR__ . '/output')) ? 'OK' : 'MISSING/NOT WRITABLE',
        'met' => file_exists(__DIR__ . '/output') && is_writable(__DIR__ . '/output'),
        'fix' => 'Run: mkdir -p ' . htmlspecialchars(__DIR__) . '/output && chmod 755 ' . htmlspecialchars(__DIR__) . '/output'
    ],
    'Uploads Directory' => [
        'required' => 'Must exist and be writable',
        'path' => __DIR__ . '/uploads',
        'current' => (file_exists(__DIR__ . '/uploads') && is_writable(__DIR__ . '/uploads')) ? 'OK' : 'MISSING/NOT WRITABLE',
        'met' => file_exists(__DIR__ . '/uploads') && is_writable(__DIR__ . '/uploads'),
        'fix' => 'Run: mkdir -p ' . htmlspecialchars(__DIR__) . '/uploads && chmod 755 ' . htmlspecialchars(__DIR__) . '/uploads'
    ],
    'JSON Extension' => [
        'required' => 'php-json',
        'current' => extension_loaded('json') ? 'Loaded' : 'MISSING',
        'met' => extension_loaded('json'),
        'fix' => 'Install: apt-get install php-json (or: yum install php-json)'
    ],
    'MBString Extension' => [
        'required' => 'php-mbstring',
        'current' => extension_loaded('mbstring') ? 'Loaded' : 'MISSING',
        'met' => extension_loaded('mbstring'),
        'fix' => 'Install: apt-get install php-mbstring (or: yum install php-mbstring)'
    ],
    'FileInfo Extension' => [
        'required' => 'php-fileinfo',
        'current' => extension_loaded('fileinfo') ? 'Loaded' : 'MISSING',
        'met' => extension_loaded('fileinfo'),
        'fix' => 'Install: apt-get install php-fileinfo (or: yum install php-fileinfo)'
    ],
    'GD Extension' => [
        'required' => 'php-gd (for image processing)',
        'current' => extension_loaded('gd') ? 'Loaded' : 'MISSING',
        'met' => extension_loaded('gd'),
        'fix' => 'Install: apt-get install php-gd (or: yum install php-gd)'
    ],
    'ZIP Extension' => [
        'required' => 'php-zip (for PDF processing)',
        'current' => extension_loaded('zip') ? 'Loaded' : 'MISSING',
        'met' => extension_loaded('zip'),
        'fix' => 'Install: apt-get install php-zip (or: yum install php-zip)'
    ],
    'Web PHP Runtime' => [
        'required' => 'Any web-capable PHP SAPI (Apache/FPM/CGI)',
        'current' => php_sapi_name(),
        'met' => in_array(php_sapi_name(), ['fpm-fcgi', 'apache2handler', 'cgi-fcgi', 'litespeed'], true),
        'fix' => 'Configure web server to use PHP-FPM, Apache mod_php, or CGI/FastCGI for HTTP requests'
    ],
];

// Check critical files
$criticalFiles = [
    'vendor/autoload.php' => __DIR__ . '/vendor/autoload.php',
    'composer.json' => __DIR__ . '/composer.json',
    'mvp/index.php' => __DIR__ . '/mvp/index.php',
    'data/mvp.json' => __DIR__ . '/data/mvp.json',
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

// Find missing requirements
$missingRequirements = [];
foreach ($requirements as $name => $req) {
    if (!$req['met']) {
        $missingRequirements[$name] = $req;
    }
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
    'Script Filename' => __FILE__,
    'Current Directory' => __DIR__,
    'File Permissions' => substr(sprintf('%o', fileperms(__FILE__)), -4),
];

// Try to detect base path
$scriptName = $_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? '';
$detectedBasePath = '/';
if (!empty($scriptName)) {
    $basePath = dirname($scriptName);
    $basePath = str_replace('\\', '/', $basePath);
    $basePath = rtrim($basePath, '/');
    if (!empty($basePath) && $basePath !== '.' && $basePath !== '/') {
        $detectedBasePath = $basePath . '/';
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Error - Requirements Check</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: #f5f6fa;
            color: #2c3e50;
            line-height: 1.6;
            padding: 20px;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
        }
        h1 {
            color: #dc3545;
            margin-bottom: 10px;
            font-size: 32px;
        }
        .critical-alert {
            background: #ffe6e6;
            border: 3px solid #dc3545;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 30px;
        }
        .critical-alert h2 {
            color: #c00;
            font-size: 24px;
            margin-bottom: 15px;
        }
        .critical-alert ul {
            margin-left: 25px;
            font-size: 16px;
        }
        .critical-alert li {
            margin: 12px 0;
            padding: 12px;
            background: white;
            border-left: 4px solid #dc3545;
            border-radius: 4px;
        }
        .error-details {
            background: #fee;
            border: 2px solid #dc3545;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 30px;
        }
        .section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #eee;
        }
        .section:last-child { border-bottom: none; }
        .section h3 {
            color: #495057;
            font-size: 22px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 3px solid #007bff;
        }
        .requirement-item {
            margin-bottom: 15px;
            padding: 15px;
            border-radius: 6px;
            border-left: 5px solid;
        }
        .requirement-item.ok {
            background: #d4edda;
            border-color: #28a745;
        }
        .requirement-item.missing {
            background: #f8d7da;
            border-color: #dc3545;
        }
        .req-header {
            font-weight: 700;
            font-size: 16px;
            margin-bottom: 8px;
        }
        .req-details {
            margin: 8px 0;
            color: #495057;
        }
        .fix-code {
            display: block;
            margin-top: 10px;
            padding: 10px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            color: #007bff;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 12px;
            margin-bottom: 12px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        .info-label {
            font-weight: 600;
            color: #6c757d;
        }
        .code-block {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 8px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            margin-top: 5px;
            word-break: break-all;
        }
        .status-ok { color: #28a745; font-weight: 600; }
        .status-error { color: #dc3545; font-weight: 600; }
        .warning-box {
            background: #fff3cd;
            border: 2px solid #ffc107;
            padding: 20px;
            border-radius: 5px;
            margin-top: 20px;
        }
        .guide-box {
            background: #e7f3ff;
            border: 2px solid #0066cc;
            padding: 20px;
            border-radius: 5px;
            margin-top: 20px;
        }
        .guide-box strong {
            color: #004085;
            font-size: 18px;
            display: block;
            margin-bottom: 15px;
        }
        ol { margin-left: 25px; line-height: 2; }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin: 10px 10px 10px 0;
            font-weight: 600;
        }
        .btn:hover { background: #0056b3; }
        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #5a6268; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚨 Application Error - Requirements Check</h1>
        
        <?php if (!empty($missingRequirements)): ?>
        <div class="critical-alert">
            <h2>❌ CRITICAL: Missing Requirements (<?= count($missingRequirements) ?> items)</h2>
            <p style="font-size: 18px; margin-bottom: 15px;"><strong>The following requirements MUST be fixed before the application will work:</strong></p>
            <ul>
                <?php foreach ($missingRequirements as $name => $req): ?>
                    <li>
                        <strong style="color: #c00; font-size: 17px;">❌ <?= htmlspecialchars($name) ?></strong><br>
                        <span style="color: #666;">Required: <?= htmlspecialchars($req['required']) ?></span><br>
                        <span style="color: #c00; font-weight: 600;">Current Status: <?= htmlspecialchars($req['current']) ?></span><br>
                        <?php if (isset($req['path'])): ?>
                            <span style="color: #666;">Path: <code><?= htmlspecialchars($req['path']) ?></code></span><br>
                        <?php endif; ?>
                        <span style="color: #007bff; font-weight: 700; display: block; margin-top: 8px;">🔧 FIX IT:</span>
                        <code class="fix-code"><?= htmlspecialchars($req['fix']) ?></code>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php else: ?>
        <div class="critical-alert" style="background: #d4edda; border-color: #28a745;">
            <h2 style="color: #155724;">✅ All Requirements Met!</h2>
            <p>All system requirements are satisfied. If you're still seeing errors, check the error details below.</p>
        </div>
        <?php endif; ?>
        
        <div class="error-details">
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
            <h3>✅ Complete Requirements Checklist</h3>
            <?php foreach ($requirements as $name => $req): ?>
                <div class="requirement-item <?= $req['met'] ? 'ok' : 'missing' ?>">
                    <div class="req-header">
                        <?php if ($req['met']): ?>
                            ✅ <?= htmlspecialchars($name) ?>
                        <?php else: ?>
                            ❌ <?= htmlspecialchars($name) ?>
                        <?php endif; ?>
                    </div>
                    <div class="req-details">
                        <strong>Required:</strong> <?= htmlspecialchars($req['required']) ?><br>
                        <strong>Current:</strong> <span class="<?= $req['met'] ? 'status-ok' : 'status-error' ?>"><?= htmlspecialchars($req['current']) ?></span>
                        <?php if (isset($req['path'])): ?>
                            <br><strong>Path:</strong> <code><?= htmlspecialchars($req['path']) ?></code>
                        <?php endif; ?>
                    </div>
                    <?php if (!$req['met']): ?>
                        <div style="margin-top: 12px; padding: 12px; background: white; border-radius: 4px; border: 2px solid #007bff;">
                            <strong style="color: #007bff; display: block; margin-bottom: 8px;">🔧 How to Fix:</strong>
                            <code class="fix-code"><?= htmlspecialchars($req['fix']) ?></code>
                        </div>
                    <?php endif; ?>
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
            <h3>🔍 Server Diagnostic Information</h3>
            <?php foreach ($diagnostics as $label => $value): ?>
                <div class="info-grid">
                    <div class="info-label"><?= htmlspecialchars($label) ?>:</div>
                    <div class="info-value"><?= htmlspecialchars((string)$value) ?></div>
                </div>
            <?php endforeach; ?>
            <div class="info-grid">
                <div class="info-label">Detected Base Path:</div>
                <div class="info-value">
                    <span class="status-ok"><?= htmlspecialchars($detectedBasePath) ?></span>
                </div>
            </div>
        </div>
        
        <div class="section">
            <h3>🔧 Complete Installation & Fix Guide</h3>
            <div class="guide-box">
                <strong>Step-by-Step Fix Instructions:</strong>
                <ol>
                    <li><strong>SSH into your server</strong></li>
                    <li><strong>Install Composer (if missing):</strong><br>
                        <code class="fix-code">curl -sS https://getcomposer.org/installer | php && mv composer.phar /usr/local/bin/composer && chmod +x /usr/local/bin/composer</code>
                    </li>
                    <li><strong>Navigate to project directory:</strong><br>
                        <code class="fix-code">cd <?= htmlspecialchars(__DIR__) ?></code>
                    </li>
                    <li><strong>Install PHP dependencies:</strong><br>
                        <code class="fix-code">composer install --no-dev --optimize-autoloader</code>
                    </li>
                    <li><strong>Create required directories:</strong><br>
                        <code class="fix-code">mkdir -p data output uploads logs && chmod 755 data output uploads logs</code>
                    </li>
                    <li><strong>Install missing PHP extensions:</strong><br>
                        <code class="fix-code">apt-get update && apt-get install -y php-json php-mbstring php-fileinfo php-gd php-zip</code><br>
                        <em style="color: #666;">Or for CentOS/RHEL: yum install -y php-json php-mbstring php-fileinfo php-gd php-zip</em>
                    </li>
                    <li><strong>Check PHP-FPM status (for 502 errors):</strong><br>
                        <code class="fix-code">systemctl status php-fpm</code><br>
                        <code class="fix-code">systemctl start php-fpm</code><br>
                        <code class="fix-code">systemctl enable php-fpm</code>
                    </li>
                    <li><strong>Check PHP-FPM socket path matches nginx config:</strong><br>
                        <code class="fix-code">ls -la /var/run/php/php*-fpm.sock</code><br>
                        <em style="color: #666;">Check nginx config file and ensure fastcgi_pass matches this socket path</em>
                    </li>
                    <li><strong>Restart services:</strong><br>
                        <code class="fix-code">systemctl restart php-fpm && systemctl restart nginx</code>
                    </li>
                    <li><strong>Check error logs:</strong><br>
                        <code class="fix-code">tail -f /var/log/php-fpm/error.log</code><br>
                        <code class="fix-code">tail -f /var/log/nginx/error.log</code>
                    </li>
                </ol>
            </div>
        </div>
        
        <div class="section">
            <h3>⚠️ Common Error Solutions</h3>
            <div class="warning-box">
                <ul style="margin-left: 20px; line-height: 2;">
                    <li><strong>502 Bad Gateway:</strong><br>
                        PHP-FPM not running or wrong socket path. Check: <code>systemctl status php-fpm</code><br>
                        Verify socket exists: <code>ls -la /var/run/php/php*-fpm.sock</code><br>
                        Check nginx config matches: <code>grep fastcgi_pass /etc/nginx/sites-enabled/*</code>
                    </li>
                    <li><strong>500 Internal Server Error:</strong><br>
                        Check PHP error logs: <code>tail -50 /var/log/php-fpm/error.log</code><br>
                        Check nginx error log: <code>tail -50 /var/log/nginx/error.log</code>
                    </li>
                    <li><strong>Permission Denied:</strong><br>
                        Fix permissions: <code>chmod -R 755 <?= htmlspecialchars(__DIR__) ?></code><br>
                        Fix ownership if needed: <code>chown -R www-data:www-data <?= htmlspecialchars(__DIR__) ?></code>
                    </li>
                    <li><strong>Class not found / Composer error:</strong><br>
                        Run: <code>cd <?= htmlspecialchars(__DIR__) ?> && composer install</code>
                    </li>
                    <li><strong>Directory not writable:</strong><br>
                        Fix: <code>chmod 755 data output uploads logs</code>
                    </li>
                </ul>
            </div>
        </div>
        
        <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #eee; text-align: center;">
            <a href="/mvp/?route=dashboard" class="btn">Try Application Again</a>
            <a href="/" class="btn btn-secondary">Go to Homepage</a>
        </div>
        
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; color: #6c757d; font-size: 13px;">
            <p><strong>Note:</strong> This diagnostic page checks all system requirements. Copy the commands above and run them on your server via SSH to fix any missing requirements.</p>
            <p style="margin-top: 10px;">Share this page URL with your system administrator: <code><?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $_SERVER['REQUEST_URI'] ?? '' ?></code></p>
        </div>
    </div>
</body>
</html>
