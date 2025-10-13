<?php
declare(strict_types=1);

namespace WebPdfTimeSaver\Mvp;

require_once __DIR__ . '/../../vendor/autoload.php';
use Smalot\PdfParser\Parser;

/**
 * Extract form field positions from PDF AcroForms to auto-generate positioning data
 */
final class PdfFieldExtractor {
    private Parser $parser;
    
    public function __construct() {
        $this->parser = new Parser();
    }
    
    /**
     * Extract field names and positions from a fillable PDF
     * Returns array of [fieldName => ['x' => x, 'y' => y, 'width' => w, 'height' => h, 'page' => p, 'type' => t]]
     */
    public function extractFieldPositions(string $pdfPath): array {
        if (!file_exists($pdfPath)) {
            throw new \RuntimeException("PDF file not found: $pdfPath");
        }
        
        $fields = [];
        
        // Try PDF parser library FIRST - it extracts REAL coordinates from PDF annotations
        try {
            $fields = $this->extractUsingPdfParser($pdfPath);

            if (!empty($fields)) {
                error_log("Successfully extracted " . count($fields) . " fields with positions using PdfParser");
                return $fields;
            } else {
                error_log("PdfParser returned empty fields array");
            }
        } catch (\Exception $e) {
            error_log("PdfParser failed: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine());
            error_log("Stack trace: " . $e->getTraceAsString());
        }

        // Fallback to pdftk (only gets field names, not positions)
        try {
            error_log("Falling back to pdftk for field names only");
            $fields = $this->extractUsingPdftk($pdfPath);
        } catch (\Exception $e) {
            error_log("pdftk also failed: " . $e->getMessage());
        }
        
        return $fields;
    }
    
    /**
     * Extract field names using pdftk
     * NOTE: pdftk only extracts field NAMES and types, NOT coordinates!
     * This is a fallback method when PdfParser fails.
     */
    private function extractUsingPdftk(string $pdfPath): array {
        $fields = [];
        
        // Find pdftk binary
        $pdftk = $this->findPdftkBinary();
        if (!$pdftk) {
            error_log("pdftk binary not found - cannot extract field names");
            return [];
        }
        
        // Run pdftk to dump field data
        // Note: pdftk can extract field NAMES even from password-protected PDFs
        // BUT it does NOT provide field coordinates/positions
        $output = [];
        $returnCode = 0;
        $cmd = "\"{$pdftk}\" \"" . realpath($pdfPath) . "\" dump_data_fields 2>&1";
        exec($cmd, $output, $returnCode);
        
        if ($returnCode !== 0) {
            // Check if it's a password error or other error
            $outputStr = implode("\n", $output);
            if (strpos($outputStr, 'OWNER PASSWORD REQUIRED') !== false) {
                error_log("pdftk: PDF is password protected");
            } else {
                error_log("pdftk failed: " . $outputStr);
            }
            return [];
        }
        
        // Parse pdftk output
        $currentField = null;
        foreach ($output as $line) {
            $line = trim($line);
            
            if (strpos($line, 'FieldName:') === 0) {
                if ($currentField && isset($currentField['name'])) {
                    $fields[$currentField['name']] = $currentField;
                }
                // WARNING: These are DUMMY coordinates - pdftk doesn't provide real positions!
                // You'll need to manually position these fields or use PdfParser instead
                $currentField = [
                    'name' => trim(substr($line, 10)),
                    'type' => 'text',
                    'page' => 1,
                    'x' => 0,  // DUMMY VALUE - pdftk doesn't give coordinates
                    'y' => 0,  // DUMMY VALUE - pdftk doesn't give coordinates
                    'width' => 100,  // DUMMY VALUE
                    'height' => 10,  // DUMMY VALUE
                    'fontSize' => 9
                ];
            } elseif ($currentField && strpos($line, 'FieldType:') === 0) {
                $type = trim(substr($line, 10));
                $currentField['type'] = $this->mapFieldType($type);
            } elseif ($currentField && strpos($line, 'FieldStateOption:') === 0) {
                // For checkboxes/radio buttons
                $currentField['options'][] = trim(substr($line, 17));
            }
        }
        
        // Add last field
        if ($currentField && isset($currentField['name'])) {
            $fields[$currentField['name']] = $currentField;
        }
        
        error_log("pdftk extracted " . count($fields) . " field names (WITHOUT coordinates)");
        return $fields;
    }
    
    /**
     * Extract fields using PDF parser library with position detection
     */
    private function extractUsingPdfParser(string $pdfPath): array {
        $fields = [];
        
        try {
            $pdf = $this->parser->parseFile($pdfPath);
            $pages = $pdf->getPages();
            
            foreach ($pages as $pageNum => $page) {
                $pageNumber = $pageNum + 1;
                
                // Get page height for coordinate conversion
                $pageHeight = 792.0; // Default US Letter height in points
                try {
                    $mediaBox = $page->get('MediaBox');
                    if ($mediaBox) {
                        $mediaBoxContent = $mediaBox->getContent();
                        if (is_array($mediaBoxContent) && count($mediaBoxContent) >= 4) {
                            // Extract numeric value from PDF parser object
                            $heightElement = $mediaBoxContent[3];
                            $pageHeight = is_object($heightElement) ? (float)$heightElement->getContent() : (float)$heightElement;
                        }
                    }
                } catch (\Exception $e) {
                    // Use default if we can't get page height
                }
                
                $annotations = $page->get('Annots');
                
                if (!$annotations) {
                    continue;
                }
                
                // Parse annotations to find form fields
                $annotArray = $annotations->getContent();
                if (!is_array($annotArray)) {
                    continue;
                }
                
                foreach ($annotArray as $annot) {
                    if (!is_object($annot)) {
                        continue;
                    }
                    
                    $fieldName = $annot->get('T');
                    $fieldType = $annot->get('FT');
                    $rect = $annot->get('Rect');
                    
                    if ($fieldName && $fieldType) {
                        $fieldNameStr = $fieldName->getContent();
                        
                        // Extract position from Rect and convert coordinates
                        $position = $this->parseRect($rect, $pageHeight);
                        
                        $fields[$fieldNameStr] = [
                            'name' => $fieldNameStr,
                            'type' => $this->mapFieldType($fieldType->getContent()),
                            'page' => $pageNumber,
                            'x' => $position['x'],
                            'y' => $position['y'],
                            'width' => $position['width'],
                            'height' => $position['height'],
                            'fontSize' => max(8, min(12, (int)$position['height'])) // Estimate font size from field height
                        ];
                    }
                }
            }
            
        } catch (\Exception $e) {
            error_log("PDF Parser extraction failed: " . $e->getMessage());
        }
        
        return $fields;
    }
    
    /**
     * Parse PDF Rect array to get field position
     * Converts from PDF coordinate system (bottom-left origin) to FPDF system (top-left origin)
     */
    private function parseRect($rect, float $pageHeight = 792.0): array {
        $default = ['x' => 0, 'y' => 0, 'width' => 100, 'height' => 10];
        
        if (!$rect) {
            return $default;
        }
        
        $rectContent = $rect->getContent();
        if (!is_array($rectContent) || count($rectContent) < 4) {
            return $default;
        }
        
        // PDF Rect format: [x1 y1 x2 y2] with bottom-left origin
        // Extract numeric values from PDF parser objects
        $x1 = is_object($rectContent[0]) ? (float)$rectContent[0]->getContent() : (float)$rectContent[0];
        $y1 = is_object($rectContent[1]) ? (float)$rectContent[1]->getContent() : (float)$rectContent[1];
        $x2 = is_object($rectContent[2]) ? (float)$rectContent[2]->getContent() : (float)$rectContent[2];
        $y2 = is_object($rectContent[3]) ? (float)$rectContent[3]->getContent() : (float)$rectContent[3];
        
        // Convert PDF points to mm (1 point = 0.352778 mm)
        $pxToMm = 0.352778;
        
        // Width and height in mm
        $width = ($x2 - $x1) * $pxToMm;
        $height = ($y2 - $y1) * $pxToMm;
        
        // Convert coordinates to mm
        $x = $x1 * $pxToMm;
        
        // CRITICAL: Flip Y coordinate from bottom-left to top-left origin
        // PDF uses bottom-left as (0,0), FPDF uses top-left as (0,0)
        // Formula: fpdf_y = page_height - pdf_y - field_height
        $pageHeightMm = $pageHeight * $pxToMm;
        $y = $pageHeightMm - ($y2 * $pxToMm);
        
        return [
            'x' => round($x, 2),
            'y' => round($y, 2),
            'width' => round($width, 2),
            'height' => round($height, 2)
        ];
    }
    
    /**
     * Map PDF field types to our internal types
     */
    private function mapFieldType(string $pdfType): string {
        $typeMap = [
            'Text' => 'text',
            'Tx' => 'text',
            'Button' => 'checkbox',
            'Btn' => 'checkbox',
            'Choice' => 'select',
            'Ch' => 'select',
            'Signature' => 'signature'
        ];
        
        return $typeMap[$pdfType] ?? 'text';
    }
    
    /**
     * Find pdftk binary on system
     */
    private function findPdftkBinary(): ?string {
        $candidates = [
            'pdftk',
            __DIR__ . '/../../pdftk_installer.exe',
            __DIR__ . '/../../pdftk.exe',
            'C:/Program Files/PDFtk/bin/pdftk.exe',
            'C:/Program Files (x86)/PDFtk/bin/pdftk.exe'
        ];
        
        foreach ($candidates as $binary) {
            // Check if it's an absolute path
            if (file_exists($binary)) {
                return $binary;
            }
            
            // Check if it's in PATH
            $output = [];
            $returnCode = 0;
            exec("where $binary 2>&1", $output, $returnCode);
            if ($returnCode === 0 && !empty($output[0])) {
                return trim($output[0]);
            }
        }
        
        return null;
    }
    
    /**
     * Generate position JSON file for a template
     */
    public function generatePositionFile(string $pdfPath, string $templateId, string $outputDir): string {
        $fields = $this->extractFieldPositions($pdfPath);
        
        if (empty($fields)) {
            throw new \RuntimeException("No fields extracted from PDF");
        }
        
        $outputFile = rtrim($outputDir, '/\\') . DIRECTORY_SEPARATOR . $templateId . '_positions.json';
        
        $json = json_encode($fields, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        file_put_contents($outputFile, $json);
        
        return $outputFile;
    }
    
    /**
     * Map PDF field names to template field keys
     * This helps when PDF field names don't match our template exactly
     */
    public function mapFieldNames(array $pdfFields, array $templateFields): array {
        $mappings = [];
        
        foreach ($pdfFields as $pdfFieldName => $pdfField) {
            // Try exact match first
            if (isset($templateFields[$pdfFieldName])) {
                $mappings[$pdfFieldName] = $pdfFieldName;
                continue;
            }
            
            // Try fuzzy matching
            $pdfNormalized = $this->normalizeFieldName($pdfFieldName);
            
            foreach ($templateFields as $templateKey => $templateField) {
                $templateNormalized = $this->normalizeFieldName($templateKey);
                
                if ($pdfNormalized === $templateNormalized) {
                    $mappings[$pdfFieldName] = $templateKey;
                    break;
                }
                
                // Check similarity
                similar_text($pdfNormalized, $templateNormalized, $percent);
                if ($percent > 80) {
                    $mappings[$pdfFieldName] = $templateKey;
                    break;
                }
            }
        }
        
        return $mappings;
    }
    
    /**
     * Normalize field name for comparison
     */
    private function normalizeFieldName(string $name): string {
        // Remove special characters, convert to lowercase
        $normalized = strtolower($name);
        $normalized = preg_replace('/[^a-z0-9]/', '', $normalized);
        return $normalized;
    }
    
    /**
     * Extract fields AND generate background images from password-protected PDF
     * This is the hybrid approach: extract metadata + render as images for overlay
     */
    public function extractAndGenerateBackgrounds(string $pdfPath, string $templateId, string $outputDir): array {
        $result = [
            'fields' => [],
            'backgrounds' => [],
            'positionFile' => null
        ];
        
        // Step 1: Extract field positions (works even with password-protected PDFs sometimes)
        $fields = $this->extractFieldPositions($pdfPath);
        $result['fields'] = $fields;
        
        // Step 2: Generate background images for each page
        $backgrounds = $this->generatePageBackgrounds($pdfPath, $templateId, $outputDir);
        $result['backgrounds'] = $backgrounds;
        
        // Step 3: Generate position file if we got fields
        if (!empty($fields)) {
            $positionFile = $this->generatePositionFile($pdfPath, $templateId, $outputDir);
            $result['positionFile'] = $positionFile;
        }
        
        return $result;
    }
    
    /**
     * Generate background images for each page of the PDF
     */
    public function generatePageBackgrounds(string $pdfPath, string $templateId, string $outputDir): array {
        $backgrounds = [];
        
        // Use Ghostscript to convert PDF pages to images
        $gsBinary = $this->findGhostscriptBinary();
        if (!$gsBinary) {
            error_log("Ghostscript not found - cannot generate background images");
            return [];
        }
        
        // Determine number of pages
        $pageCount = $this->getPageCount($pdfPath);
        
        // Clean template ID (remove t_ prefix)
        $cleanTemplateId = str_replace('t_', '', $templateId);
        $cleanTemplateId = explode('_', $cleanTemplateId)[0];
        
        for ($page = 1; $page <= $pageCount; $page++) {
            $outputFile = rtrim($outputDir, '/\\') . DIRECTORY_SEPARATOR . 
                          "{$cleanTemplateId}_page{$page}_background.png";
            
            // Ghostscript command to render specific page
            $cmd = "\"{$gsBinary}\" -dSAFER -dNOPAUSE -dBATCH -sDEVICE=png16m " .
                   "-r200 -dFirstPage={$page} -dLastPage={$page} " .
                   "-sOutputFile=\"{$outputFile}\" \"" . realpath($pdfPath) . "\" 2>&1";
            
            $output = [];
            $returnCode = 0;
            exec($cmd, $output, $returnCode);
            
            if ($returnCode === 0 && file_exists($outputFile)) {
                $backgrounds[$page] = $outputFile;
                error_log("Generated background for page $page: $outputFile");
            } else {
                error_log("Failed to generate background for page $page: " . implode("\n", $output));
            }
        }
        
        return $backgrounds;
    }
    
    /**
     * Get number of pages in PDF
     */
    private function getPageCount(string $pdfPath): int {
        $pdftk = $this->findPdftkBinary();
        
        if ($pdftk) {
            // Use pdftk to get page count
            $output = [];
            $returnCode = 0;
            $cmd = "\"{$pdftk}\" \"" . realpath($pdfPath) . "\" dump_data 2>&1";
            exec($cmd, $output, $returnCode);
            
            foreach ($output as $line) {
                if (strpos($line, 'NumberOfPages:') === 0) {
                    return (int)trim(substr($line, 14));
                }
            }
        }
        
        // Fallback: try to guess from file (crude method)
        $content = file_get_contents($pdfPath);
        if (preg_match('/\/Count\s+(\d+)/', $content, $matches)) {
            return (int)$matches[1];
        }
        
        // Default to 1 page
        return 1;
    }
    
    /**
     * Find Ghostscript binary
     */
    private function findGhostscriptBinary(): ?string {
        $candidates = [
            'gswin64c',
            'gswin32c',
            'gs',
            __DIR__ . '/../../gs1000w64.exe',
        ];
        
        foreach ($candidates as $bin) {
            $cmd = strpos($bin, DIRECTORY_SEPARATOR) !== false ? 
                   "\"{$bin}\" -v 2>&1" : "{$bin} -v 2>&1";
            $output = [];
            $return = 0;
            @exec($cmd, $output, $return);
            if ($return === 0) {
                return $bin;
            }
        }
        
        return null;
    }
}

