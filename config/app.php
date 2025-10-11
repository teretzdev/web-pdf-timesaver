<?php
/**
 * Application Configuration
 * 
 * Centralized configuration for the Web-PDFTimeSaver application.
 * Environment-specific settings can be overridden via environment variables.
 */

return [
    // Application Settings
    'app' => [
        'name' => 'Web-PDFTimeSaver',
        'version' => '1.0.0',
        'debug' => getenv('APP_DEBUG') === '1' || getenv('APP_DEBUG') === 'true',
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
        'custom_fields' => true,
        'multi_page_forms' => true,
        'field_validation' => true,
        'auto_save' => false,
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

