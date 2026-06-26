<?php
/**
 * Application Configuration
 * 
 * Centralized configuration for the Web-PDFTimeSaver application.
 * Environment-specific settings can be overridden via environment variables.
 */

require_once __DIR__ . '/db_env.php';
wpts_apply_db_local_overrides();

return [
    // Application Settings
    'app' => [
        'name' => 'Web-PDFTimeSaver',
        'version' => '1.0.0',
        'debug' => getenv('APP_DEBUG') === '1' || getenv('APP_DEBUG') === 'true',
        // Lab HTML routes (mvp/index.php): enable with APP_DEBUG, MVP_DEV_ROUTES=1, or APP_ENV=local|dev|development
        'env' => getenv('APP_ENV') ?: 'production',
    ],

    // Path Configuration
    'paths' => [
        'root' => dirname(__DIR__),
        'data' => dirname(__DIR__) . '/data',
        'logs' => dirname(__DIR__) . '/logs',
        'output' => dirname(__DIR__) . '/output',
        'uploads' => dirname(__DIR__) . '/uploads',
        'templates' => dirname(__DIR__) . '/uploads',
        'tmp' => dirname(__DIR__) . '/tmp',
    ],

    // Database/Storage Settings
    'storage' => [
        'datafile' => dirname(__DIR__) . '/data/mvp.json',
        'db' => [
            'enabled' => getenv('DB_ENABLED') === false ? true : (getenv('DB_ENABLED') === '1' || getenv('DB_ENABLED') === 'true'),
            'driver' => getenv('DB_DRIVER') ?: 'mysql',
            'host' => (static function (): string {
                $default = 'LawDocumentManager.com';
                $h = trim((string)(getenv('DB_HOST') ?: ''));
                if ($h === '') {
                    return $default;
                }
                return strcasecmp($h, 'MySQL') === 0 ? $default : $h;
            })(),
            'port' => (int)(getenv('DB_PORT') ?: 3306),
            'database' => getenv('DB_NAME') ?: 'LawDocumentManager.com',
            'username' => getenv('DB_USER') ?: 'ldm',
            'password' => getenv('DB_PASSWORD') ?: '3294459786827563',
        ],
        /** Phase 1 alignment scope; override with env FIRM_ID */
        'firm_id' => getenv('FIRM_ID') ?: 'default_firm',
    ],

    // File Upload Settings
    'upload' => [
        'max_size' => 10 * 1024 * 1024, // 10MB in bytes
        'allowed_types' => ['pdf'],
        'allowed_mime_types' => ['application/pdf'],
    ],

    // PDF Processing Settings
    'pdf' => [
        'max_pages' => 50,
        'default_font' => 'Arial',
        'default_font_size' => 9,
        'quality_check' => true,
        'min_file_size' => 1024, // 1KB minimum
    ],

    // Logging Settings
    'logging' => [
        'enabled' => true,
        'level' => getenv('LOG_LEVEL') ?: 'info', // debug, info, error
        'max_file_size' => 1024 * 1024, // 1MB
        'max_files' => 3, // Number of rotated log files to keep
        'path' => dirname(__DIR__) . '/logs/app.log',
        'pdf_debug_log' => dirname(__DIR__) . '/logs/pdf_debug.log',
    ],

    // Security Settings
    'security' => [
        'session_timeout' => 3600, // 1 hour in seconds
        'csrf_protection' => true,
        'sanitize_filenames' => true,
        'allowed_origins' => ['*'], // CORS origins
    ],

    // Performance Settings
    'performance' => [
        'cache_enabled' => true,
        'cache_ttl' => 3600, // 1 hour
        'max_execution_time' => 300, // 5 minutes
        'memory_limit' => '256M',
    ],

    // Feature Flags
    'features' => [
        'pdf_signing' => true,
        'custom_fields' => false,
        'multi_page_forms' => true,
        'field_validation' => true,
        'auto_save' => false,
    ],

    // PDF Signing Settings (digital signing via mPDF)
    'signing' => [
        'enabled' => getenv('PDF_SIGNING_ENABLED') === '1' || getenv('PDF_SIGNING_ENABLED') === 'true',
        'cert_p12_path' => getenv('PDF_SIGNING_CERT_P12') ?: dirname(__DIR__) . '/certs/test.p12',
        'cert_password' => getenv('PDF_SIGNING_CERT_PASSWORD') ?: '',
        'info' => [
            'reason' => getenv('PDF_SIGNING_REASON') ?: 'Document approved',
            'location' => getenv('PDF_SIGNING_LOCATION') ?: 'Web-PDFTimeSaver',
            'contact' => getenv('PDF_SIGNING_CONTACT') ?: 'support@example.com',
        ],
    ],

    // Template Settings
    'templates' => [
        'default_template' => 't_fl100_gc120',
        'position_data_path' => dirname(__DIR__) . '/data',
    ],

    // MVP-specific Settings
    'mvp' => [
        'seed_demo_data' => getenv('SEED_DEMO') === '1' || getenv('SEED_DEMO') === 'true',
        'projects_per_page' => 20,
        'documents_per_page' => 50,
    ],
];

