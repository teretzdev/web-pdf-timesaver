<?php
/**
 * Missing Field Locator
 * Uses text extraction to find missing fields that aren't form fields
 */

declare(strict_types=1);

namespace WebPdfTimeSaver\Mvp;

class MissingFieldLocator {
    
    /**
     * Locate missing fields by searching for text labels near expected positions
     * Returns array of field positions based on text detection
     */
    public static function locateMissingFields(string $templateId, array $existingPositions): array {
        $missingFields = [];
        
        // Get PDF path
        $pdfPath = self::getPdfPath($templateId);
        if (!$pdfPath || !file_exists($pdfPath)) {
            return $missingFields;
        }
        
        // Use Node.js text extraction to find text labels
        $textItems = self::extractTextLabels($pdfPath);
        
        // Map of test data field names to search patterns
        $fieldPatterns = [
            'petitioner_phone' => ['petitioner.*phone', 'phone.*petitioner', 'telephone.*petitioner'],
            'filing_date' => ['filing.*date', 'date.*filed', 'file.*date'],
            'marriage_location' => ['marriage.*location', 'location.*marriage', 'married.*in', 'married.*at'],
            'grounds_for_dissolution' => ['grounds', 'irreconcilable', 'reason.*dissolution'],
        ];
        
        foreach ($fieldPatterns as $fieldName => $patterns) {
            // Check if field already exists
            $found = false;
            foreach ($existingPositions as $posKey => $pos) {
                $normalizedKey = strtolower(str_replace(['_', '-', ' ', '[', ']', '.'], '', $posKey));
                $normalizedField = strtolower(str_replace(['_', '-', ' '], '', $fieldName));
                if (strpos($normalizedKey, $normalizedField) !== false) {
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                // Search text items for labels matching patterns
                $position = self::findFieldByLabel($textItems, $patterns, $fieldName);
                if ($position) {
                    $missingFields[$fieldName] = $position;
                }
            }
        }
        
        return $missingFields;
    }
    
    /**
     * Extract text labels from PDF using Node.js
     */
    private static function extractTextLabels(string $pdfPath): array {
        $nodePath = self::findNodePath();
        if (!$nodePath) {
            return [];
        }
        
        $scriptPath = __DIR__ . '/../../scripts/methods/pdfjs-text-extractor.js';
        if (!file_exists($scriptPath)) {
            return [];
        }
        
        // Run Node.js text extraction
        $command = escapeshellarg($nodePath) . ' ' . escapeshellarg($scriptPath) . ' ' . escapeshellarg($pdfPath);
        $output = shell_exec($command . ' 2>&1');
        
        // Parse JSON output if available
        if ($output && preg_match('/\{.*\}/s', $output, $matches)) {
            $data = json_decode($matches[0], true);
            return $data['textItems'] ?? [];
        }
        
        return [];
    }
    
    /**
     * Find field position by searching for label text
     */
    private static function findFieldByLabel(array $textItems, array $patterns, string $fieldName): ?array {
        foreach ($textItems as $item) {
            $text = strtolower($item['text'] ?? '');
            
            foreach ($patterns as $pattern) {
                if (preg_match('/' . $pattern . '/i', $text)) {
                    // Found label - field should be nearby (usually to the right or below)
                    // Estimate field position based on label position
                    return [
                        'name' => $fieldName,
                        'type' => 'text',
                        'page' => $item['page'] ?? 1,
                        'x' => ($item['x'] ?? 0) + ($item['width'] ?? 50), // Field usually to the right of label
                        'y' => $item['y'] ?? 0,
                        'width' => 60, // Estimated width
                        'height' => 5,
                        'fontSize' => 9,
                        'confidence' => 0.7,
                        'method' => 'text-detection',
                        'estimated' => true
                    ];
                }
            }
        }
        
        return null;
    }
    
    /**
     * Get PDF path for template
     */
    private static function getPdfPath(string $templateId): ?string {
        $templateMap = [
            't_fl100_gc120' => 'fl100.pdf',
            't_fl105_gc120' => 'fl100.pdf',
        ];
        
        $filename = $templateMap[$templateId] ?? null;
        if (!$filename) {
            return null;
        }
        
        $pdfPath = __DIR__ . '/../../uploads/' . $filename;
        if (file_exists($pdfPath)) {
            return $pdfPath;
        }
        
        return null;
    }
    
    /**
     * Find Node.js executable path
     */
    private static function findNodePath(): ?string {
        $paths = [
            'C:\\Program Files\\nodejs\\node.exe',
            'C:\\Program Files (x86)\\nodejs\\node.exe',
            'node', // Try PATH
        ];
        
        foreach ($paths as $path) {
            if ($path === 'node') {
                $which = shell_exec('where node 2>nul');
                if ($which) {
                    return trim($which);
                }
            } else {
                if (file_exists($path)) {
                    return $path;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Merge missing fields into existing positions
     */
    public static function mergeMissingFields(array $existingPositions, array $missingFields): array {
        foreach ($missingFields as $fieldName => $position) {
            // Use a synthetic field name that won't conflict
            $syntheticName = 'missing_' . $fieldName;
            $existingPositions[$syntheticName] = $position;
        }
        
        return $existingPositions;
    }
}

