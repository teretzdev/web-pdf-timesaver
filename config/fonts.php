<?php
/**
 * Universal Font Configuration
 * Applies to all PDFs unless overridden at template or field level
 */

return [
    // Canonical unit policy for preview/extraction/export (stored/displayed in CSS px).
    'units' => [
        'canonical' => 'px',
        'mmPerPt' => 0.352778,
        'ptPerMm' => 2.834645669,
        'cssPxPerPt' => 96 / 72,
    ],
    // Default font settings for all PDFs
    'defaults' => [
        'fontFamily' => 'Arial',
        'fontSize' => 13,
        'fontStyle' => '',
        'fontColor' => [0, 0, 0], // RGB: black
    ],
    
    // Font presets (can be selected and applied)
    'presets' => [
        'standard' => [
            'name' => 'Standard',
            'fontFamily' => 'Arial',
            'fontSize' => 10,
            'fontStyle' => '',
        ],
        'bold' => [
            'name' => 'Bold',
            'fontFamily' => 'Arial',
            'fontSize' => 10,
            'fontStyle' => 'B',
        ],
        'small' => [
            'name' => 'Small Text',
            'fontFamily' => 'Arial',
            'fontSize' => 8,
            'fontStyle' => '',
        ],
        'large' => [
            'name' => 'Large Text',
            'fontFamily' => 'Arial',
            'fontSize' => 12,
            'fontStyle' => '',
        ],
        'times' => [
            'name' => 'Times Roman',
            'fontFamily' => 'Times',
            'fontSize' => 10,
            'fontStyle' => '',
        ],
        'courier' => [
            'name' => 'Courier',
            'fontFamily' => 'Courier',
            'fontSize' => 10,
            'fontStyle' => '',
        ],
    ],
    
    // Template-specific font overrides (optional)
    'templates' => [
        // Example:
        // 't_fl100_gc120' => [
        //     'fontFamily' => 'Times',
        //     'fontSize' => 9,
        // ],
    ],
    
    // Field-type specific fonts (applies to all PDFs)
    'fieldTypes' => [
        'name' => [
            'fontFamily' => 'Arial',
            'fontSize' => 10,
            'fontStyle' => '',
        ],
        'address' => [
            'fontFamily' => 'Arial',
            'fontSize' => 9,
            'fontStyle' => '',
        ],
        'phone' => [
            'fontFamily' => 'Courier',
            'fontSize' => 10,
            'fontStyle' => '',
        ],
        'email' => [
            'fontFamily' => 'Courier',
            'fontSize' => 9,
            'fontStyle' => '',
        ],
        'date' => [
            'fontFamily' => 'Courier',
            'fontSize' => 10,
            'fontStyle' => '',
        ],
        'number' => [
            'fontFamily' => 'Courier',
            'fontSize' => 10,
            'fontStyle' => '',
        ],
        'text' => [
            'fontFamily' => 'Arial',
            'fontSize' => 10,
            'fontStyle' => '',
        ],
        'checkbox' => [
            'fontFamily' => 'Arial',
            'fontSize' => 12,
            'fontStyle' => 'B',
        ],
        'signature' => [
            'fontFamily' => 'Times',
            'fontSize' => 10,
            'fontStyle' => 'I',
        ],
    ],
    
    // Available fonts (FPDF standard fonts)
    'availableFonts' => [
        'Arial',
        'Helvetica',
        'Times',
        'Courier',
        'Symbol',
        'ZapfDingbats',
    ],
    
    // Font size limits (CSS px)
    'sizeLimits' => [
        'min' => 8,
        'max' => 32,
        'default' => 13,
    ],
];

