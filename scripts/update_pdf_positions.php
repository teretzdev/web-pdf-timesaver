<?php
/**
 * Script to extract and update PDF field positions
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../mvp/lib/pdf_position_extractor.php';
require_once __DIR__ . '/../mvp/lib/field_position_loader.php';

use WebPdfTimeSaver\Mvp\PdfPositionExtractor;
use WebPdfTimeSaver\Mvp\FieldPositionLoader;

// Configuration
$uploadsDir = __DIR__ . '/../uploads';
$outputDir = __DIR__ . '/../output';
$dataDir = __DIR__ . '/../data';

echo "PDF Position Updater\n";
echo "====================\n\n";

// Initialize components
$extractor = new PdfPositionExtractor();
$loader = new FieldPositionLoader($dataDir);

// Command line arguments
$action = $argv[1] ?? 'update';
$templateId = $argv[2] ?? 't_fl100_gc120';
$pdfFile = $argv[3] ?? null;

switch ($action) {
    case 'extract':
        // Extract positions from a specific PDF
        if (!$pdfFile) {
            echo "Please provide a PDF file path.\n";
            echo "Usage: php update_pdf_positions.php extract <template_id> <pdf_file>\n";
            exit(1);
        }
        
        if (!file_exists($pdfFile)) {
            echo "PDF file not found: $pdfFile\n";
            exit(1);
        }
        
        echo "Extracting positions from: $pdfFile\n";
        $analysis = $extractor->analyzeDocument($pdfFile);
        
        echo "\nFound " . count($analysis['form_fields']) . " form fields:\n";
        foreach ($analysis['form_fields'] as $fieldName => $info) {
            echo "  - $fieldName: Page {$info['page']}, X={$info['x']}, Y={$info['y']}, Type={$info['type']}\n";
        }
        
        // Save extracted positions
        if (!empty($analysis['form_fields'])) {
            $loader->saveFieldPositions($templateId, $analysis['form_fields']);
            echo "\nPositions saved to: {$dataDir}/{$templateId}_positions.json\n";
        }
        break;
        
    case 'generate':
        // Generate standard FL-100 positions
        echo "Generating standard FL-100 field positions...\n";
        
        $positions = $extractor->generateFL100Positions();
        
        echo "\nGenerated " . count($positions) . " field positions:\n";
        foreach ($positions as $fieldName => $info) {
            echo "  - $fieldName: Page {$info['page']}, X={$info['x']}, Y={$info['y']}\n";
        }
        
        // Save generated positions
        $loader->saveFieldPositions('t_fl100_gc120', $positions);
        echo "\nPositions saved to: {$dataDir}/t_fl100_gc120_positions.json\n";
        break;
        
    case 'update':
    default:
        // Update positions for FL-100 form
        echo "Updating positions for template: $templateId\n\n";
        
        // Look for the FL-100 PDF template
        $fl100Pdf = $uploadsDir . '/fl100.pdf';
        
        if (file_exists($fl100Pdf)) {
            echo "Analyzing FL-100 template: $fl100Pdf\n";
            $analysis = $extractor->analyzeDocument($fl100Pdf);
            
            if (!empty($analysis['form_fields'])) {
                echo "Found " . count($analysis['form_fields']) . " form fields from PDF.\n";
                $positions = $analysis['form_fields'];
            } else {
                echo "No form fields found in PDF. Using generated positions.\n";
                $positions = $extractor->generateFL100Positions();
            }
        } else {
            echo "FL-100 template not found. Using generated positions.\n";
            $positions = $extractor->generateFL100Positions();
        }
        
        // Add any missing standard fields
        $standardFields = $extractor->generateFL100Positions();
        foreach ($standardFields as $fieldName => $fieldInfo) {
            if (!isset($positions[$fieldName])) {
                $positions[$fieldName] = $fieldInfo;
                echo "  Added missing field: $fieldName\n";
            }
        }
        
        // Save updated positions
        $loader->saveFieldPositions($templateId, $positions);
        
        echo "\nPositions updated successfully!\n";
        echo "Saved " . count($positions) . " field positions to: {$dataDir}/{$templateId}_positions.json\n";
        
        // Display summary
        echo "\nField Summary:\n";
        $types = [];
        foreach ($positions as $field => $info) {
            $type = $info['type'] ?? 'text';
            $types[$type] = ($types[$type] ?? 0) + 1;
        }
        foreach ($types as $type => $count) {
            echo "  - $type fields: $count\n";
        }
        break;
}

echo "\nDone!\n";