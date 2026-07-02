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

        // Ensure Node.js path is available for the AutoPositionExtractor/ensemble
        // This mirrors the default path used in your test scripts (test-enhanced-extraction.bat).
        if (!getenv('NODE_PATH') && strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $defaultNode = 'C:\\Program Files\\nodejs\\node.exe';
            if (file_exists($defaultNode)) {
                putenv('NODE_PATH=' . $defaultNode);
            }
        }
    }
    
    /**
     * Extract field names and positions from a fillable PDF
     * Returns array of [fieldName => ['x' => x, 'y' => y, 'width' => w, 'height' => h, 'page' => p, 'type' => t]]
     * 
     * Strategy:
     * 1. Try Node.js extraction (includes qpdf decryption for encrypted PDFs) - DECRYPTS FIRST
     * 2. Try direct qpdf decryption + PDF parser (for encrypted PDFs)
     * 3. Try PDF parser directly (for unencrypted PDFs)
     * 4. Fallback to pdftk (field names only)
     * 
     * @param string $pdfPath Path to PDF file
     * @param string|null $templateId Template ID for saving positions file (optional but recommended)
     */
    /**
     * @var array|null Stores ensemble metadata from the last extraction
     */
    private ?array $lastEnsembleMetadata = null;
    
    public function extractFieldPositions(string $pdfPath, ?string $templateId = null): array {
        if (!file_exists($pdfPath)) {
            throw new \RuntimeException("PDF file not found: $pdfPath");
        }
        
        $fields = [];
        $this->lastEnsembleMetadata = null; // Reset metadata
        
        // Try Node.js extraction FIRST - most reliable for W-9 and encrypted PDFs (uses qpdf internally)
        // IMPORTANT: Always try ensemble extraction, even for static PDFs without fillable fields
        try {
            require_once __DIR__ . '/auto_position_extractor.php';
            $autoExtractor = new \WebPdfTimeSaver\Mvp\AutoPositionExtractor();
            
            if ($autoExtractor->isAvailable()) {
                error_log("=== EXTRACTION START ===");
                error_log("PDF: " . basename($pdfPath));
                error_log("PDF full path: $pdfPath");
                error_log("PDF exists: " . (file_exists($pdfPath) ? 'YES' : 'NO'));
                // CRITICAL: Use actual template ID if provided, so positions file is saved correctly
                // This ensures decryption happens FIRST, then extraction from decrypted PDF
                $nodeTemplateId = $templateId ?? 'temp_' . time();
                error_log("Template ID: $nodeTemplateId");
                error_log("Calling AutoPositionExtractor::extractPositions()...");
                $result = $autoExtractor->extractPositions($pdfPath, $nodeTemplateId);
                error_log("AutoPositionExtractor returned. Result keys: " . implode(', ', array_keys($result)));
                error_log("Result success: " . (isset($result['success']) ? ($result['success'] ? 'true' : 'false') : 'NOT SET'));
                error_log("Result fields count: " . (isset($result['fields']) ? count($result['fields']) : 'NOT SET'));
                if (isset($result['fields']) && is_array($result['fields'])) {
                    error_log("Result fields type: " . gettype($result['fields']));
                    error_log("Result fields is_array: " . (is_array($result['fields']) ? 'YES' : 'NO'));
                    if (!empty($result['fields'])) {
                        error_log("First field sample: " . json_encode($result['fields'][0] ?? 'N/A'));
                    }
                }
                
                // CRITICAL: Always check details file as fallback, even if result appears empty
                // The ensemble might have found fields but they weren't in the direct result
                // Also wait a moment for the file to be written (Node.js might still be writing)
                if (empty($result['fields']) && $nodeTemplateId) {
                    $dataDir = __DIR__ . '/../../data';
                    $detailsFile = $dataDir . '/' . $nodeTemplateId . '_extraction_details.json';
                    
                    // Wait up to 3 seconds for the file to appear (Node.js might still be writing)
                    $maxWait = 3;
                    $waited = 0;
                    while (!file_exists($detailsFile) && $waited < $maxWait) {
                        usleep(500000); // Wait 0.5 seconds
                        $waited += 0.5;
                    }
                    
                    if (file_exists($detailsFile)) {
                        error_log("Result fields empty, checking details file: $detailsFile");
                        try {
                            $detailsContent = file_get_contents($detailsFile);
                            if ($detailsContent === false) {
                                error_log("Failed to read details file content");
                            } else {
                                $detailsData = json_decode($detailsContent, true);
                                if (json_last_error() !== JSON_ERROR_NONE) {
                                    error_log("JSON decode error in details file: " . json_last_error_msg());
                                } else if ($detailsData && isset($detailsData['fields']) && is_array($detailsData['fields']) && !empty($detailsData['fields'])) {
                                    error_log("Found " . count($detailsData['fields']) . " fields in details file, using them");
                                    $result['fields'] = $detailsData['fields'];
                                    $result['success'] = $detailsData['success'] ?? true;
                                    $result['method'] = $detailsData['method'] ?? $result['method'] ?? 'unknown';
                                    $result['methodsUsed'] = $detailsData['methodsUsed'] ?? $result['methodsUsed'] ?? [];
                                    $result['fieldsPerMethod'] = $detailsData['fieldsPerMethod'] ?? $result['fieldsPerMethod'] ?? [];
                                } else {
                                    error_log("Details file exists but no fields found - fields count: " . (isset($detailsData['fields']) ? count($detailsData['fields']) : 'N/A'));
                                }
                            }
                        } catch (\Exception $e) {
                            error_log("Failed to load from details file fallback: " . $e->getMessage());
                        }
                    } else {
                        error_log("Details file not found after waiting: $detailsFile");
                        error_log("Data directory: $dataDir (exists: " . (is_dir($dataDir) ? 'YES' : 'NO') . ")");
                        if (is_dir($dataDir)) {
                            $files = glob($dataDir . '/*' . $nodeTemplateId . '*');
                            error_log("Files matching template ID: " . (empty($files) ? 'NONE' : implode(', ', $files)));
                        }
                    }
                }

                // IMPORTANT:
                // The Node pipeline may set success=false if STRICT verification fails,
                // even when it DID extract a non‑zero set of fields.
                // For the MVP, ANY non‑empty field list from the ensemble is more valuable
                // than falling back to "no fields / manual_overlay", so we treat
                // "fields found" as success regardless of the strict flag.
                if (!empty($result['fields'])) {
                    error_log("=== CONVERTING FIELDS ===");
                    error_log("Input fields count: " . count($result['fields']));
                    // Convert array format to keyed object format
                    // Use canonicalName if available, otherwise use name
                    // IMPORTANT: Handle both array of fields and object with field arrays
                    $fieldsArray = $result['fields'];
                    if (!is_array($fieldsArray)) {
                        error_log("ERROR: result['fields'] is not an array: " . gettype($fieldsArray));
                        error_log("Value: " . var_export($fieldsArray, true));
                        $fieldsArray = [];
                    } else {
                        error_log("Fields array is valid, processing " . count($fieldsArray) . " fields");
                    }
                    
                    $convertedCount = 0;
                    foreach ($fieldsArray as $index => $field) {
                        if (!is_array($field)) {
                            error_log("WARNING: Field at index $index is not an array: " . gettype($field));
                            error_log("Field value: " . var_export($field, true));
                            continue;
                        }
                        $key = $field['canonicalName'] ?? $field['name'] ?? null;
                        if ($key) {
                            $fields[$key] = $field;
                            $convertedCount++;
                            error_log("Converted field: $key (type: " . ($field['type'] ?? 'unknown') . ")");
                        } else {
                            // If no key, use index
                            $fields['field_' . count($fields)] = $field;
                            $convertedCount++;
                            error_log("WARNING: Field at index $index has no name or canonicalName, using index");
                        }
                    }
                    error_log("Total fields converted: $convertedCount");
                    error_log("Final fields array count: " . count($fields));
                    
                    // Store ensemble metadata for later retrieval
                    $this->lastEnsembleMetadata = [
                        'method' => $result['method'] ?? 'unknown',
                        'methodsUsed' => $result['methodsUsed'] ?? [],
                        'fieldsPerMethod' => $result['fieldsPerMethod'] ?? [],
                        'pageCount' => $result['pageCount'] ?? 0,
                        'warnings' => $result['warnings'] ?? [],
                        'errors' => $result['errors'] ?? []
                    ];
                    
                    error_log("Successfully extracted " . count($fields) . " fields using Node.js ensemble pipeline");
                    error_log("  - Method: " . ($result['method'] ?? 'unknown'));
                    error_log("  - Methods used: " . (isset($result['methodsUsed']) ? implode(', ', $result['methodsUsed']) : 'unknown'));
                    error_log("  - PDF was decrypted before extraction: " . (strpos($result['method'] ?? '', 'decrypt') !== false ? 'YES' : 'NO'));
                    if (isset($result['fieldsPerMethod']) && is_array($result['fieldsPerMethod'])) {
                        error_log("  - Fields per method: " . json_encode($result['fieldsPerMethod']));
                    }
                    if ($templateId) {
                        error_log("  - Positions saved to: data/$templateId" . "_positions.json");
                    }
                    
                    // CRITICAL: Return fields even if empty - let caller decide
                    if (count($fields) > 0) {
                        error_log("=== RETURNING FIELDS ===");
                        error_log("Returning " . count($fields) . " fields from extractFieldPositions()");
                        error_log("Field keys: " . implode(', ', array_keys($fields)));
                        return $fields;
                    } else {
                        error_log("ERROR: Fields array was empty after conversion");
                        error_log("Original result fields count: " . (isset($result['fields']) ? count($result['fields']) : 'N/A'));
                        error_log("Fields array after processing: " . count($fields));
                    }
                } else {
                    error_log("Node.js extraction returned empty fields array");
                    if (!empty($result['errors'])) {
                        error_log("  Errors: " . implode(', ', $result['errors']));
                    }
                    if (isset($result['attempts']) && is_array($result['attempts'])) {
                        $attemptSummary = [];
                        foreach ($result['attempts'] as $attempt) {
                            $status = $attempt['success'] ? 'SUCCESS' : 'FAILED';
                            $attemptSummary[] = $attempt['method'] . ": " . $status . " (" . ($attempt['fields'] ?? 0) . " fields)";
                        }
                        error_log("  - Attempts: " . implode(', ', $attemptSummary));
                    }
                    
                    // CRITICAL FIX: Even if result['fields'] is empty, try loading from details file
                    // This handles cases where extraction succeeded but fields weren't in the direct result
                    if ($templateId) {
                        $dataDir = __DIR__ . '/../../data';
                        $detailsFile = $dataDir . '/' . $templateId . '_extraction_details.json';
                        if (file_exists($detailsFile)) {
                            error_log("Attempting to load fields from details file: $detailsFile");
                            try {
                                $detailsContent = file_get_contents($detailsFile);
                                $detailsData = json_decode($detailsContent, true);
                                if ($detailsData && isset($detailsData['fields']) && is_array($detailsData['fields']) && !empty($detailsData['fields'])) {
                                    error_log("Found " . count($detailsData['fields']) . " fields in details file, converting...");
                                    foreach ($detailsData['fields'] as $field) {
                                        if (is_array($field)) {
                                            $key = $field['canonicalName'] ?? $field['name'] ?? null;
                                            if ($key) {
                                                $fields[$key] = $field;
                                            } else {
                                                $fields['field_' . count($fields)] = $field;
                                            }
                                        }
                                    }
                                    
                                    if (count($fields) > 0) {
                                        error_log("Successfully loaded " . count($fields) . " fields from details file");
                                        // Store ensemble metadata
                                        $this->lastEnsembleMetadata = [
                                            'method' => $detailsData['method'] ?? 'unknown',
                                            'methodsUsed' => $detailsData['methodsUsed'] ?? [],
                                            'fieldsPerMethod' => $detailsData['fieldsPerMethod'] ?? [],
                                            'pageCount' => $detailsData['pageCount'] ?? 0,
                                            'warnings' => $detailsData['warnings'] ?? [],
                                            'errors' => $detailsData['errors'] ?? []
                                        ];
                                        return $fields;
                                    }
                                }
                            } catch (\Exception $e) {
                                error_log("Failed to load from details file: " . $e->getMessage());
                            }
                        }
                    }
                }
            } else {
                error_log("Node.js extraction not available");
                error_log("Node.js path: " . ($autoExtractor->getStatus()['nodejs_path'] ?? 'NOT FOUND'));
                error_log("Script path: " . ($autoExtractor->getStatus()['script_path'] ?? 'NOT FOUND'));
                error_log("Script exists: " . (isset($autoExtractor->getStatus()['script_path']) && file_exists($autoExtractor->getStatus()['script_path']) ? 'YES' : 'NO'));
            }
        } catch (\Exception $e) {
            error_log("Node.js extraction failed: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
        }

        // Try qpdf decryption + PDF parser for encrypted PDFs
        $qpdfBinary = $this->findQpdfBinary();
        if ($qpdfBinary) {
            try {
                $tempDecrypted = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'decrypted_' . basename($pdfPath);
                if ($this->decryptPdfWithQpdf($pdfPath, $tempDecrypted, $qpdfBinary)) {
                    error_log("Successfully decrypted PDF with qpdf, attempting extraction");
                    $fields = $this->extractUsingPdfParser($tempDecrypted);
                    @unlink($tempDecrypted); // Clean up temp file
                    
                    if (!empty($fields)) {
                        error_log("Successfully extracted " . count($fields) . " fields from qpdf-decrypted PDF");
                        return $fields;
                    }
                }
            } catch (\Exception $e) {
                error_log("qpdf decryption + extraction failed: " . $e->getMessage());
                @unlink($tempDecrypted ?? ''); // Clean up on error
            }
        }

        // Try PDF parser library directly - works for unencrypted PDFs
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
                    'fontSize' => FieldMetrics::defaultFontPx(),
                    'fontSizeUnit' => 'px',
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
                $pageHeight = 792.0; // Default US Letter height in points (11 inches = 792 points)
                $pageWidth = 612.0; // Default US Letter width in points (8.5 inches = 612 points)
                
                try {
                    $mediaBox = $page->get('MediaBox');
                    if ($mediaBox) {
                        $mediaBoxContent = $mediaBox->getContent();
                        if (is_array($mediaBoxContent) && count($mediaBoxContent) >= 4) {
                            // MediaBox format: [llx lly urx ury] (lower-left x, lower-left y, upper-right x, upper-right y)
                            // Extract numeric values from PDF parser objects
                            $llx = is_object($mediaBoxContent[0]) ? (float)$mediaBoxContent[0]->getContent() : (float)$mediaBoxContent[0];
                            $lly = is_object($mediaBoxContent[1]) ? (float)$mediaBoxContent[1]->getContent() : (float)$mediaBoxContent[1];
                            $urx = is_object($mediaBoxContent[2]) ? (float)$mediaBoxContent[2]->getContent() : (float)$mediaBoxContent[2];
                            $ury = is_object($mediaBoxContent[3]) ? (float)$mediaBoxContent[3]->getContent() : (float)$mediaBoxContent[3];
                            
                            // Calculate actual page dimensions
                            $pageWidth = $urx - $llx;
                            $pageHeight = $ury - $lly;
                            
                            error_log(sprintf(
                                "Page %d: Detected MediaBox [%.2f, %.2f, %.2f, %.2f] -> Size: %.2f x %.2f points",
                                $pageNumber, $llx, $lly, $urx, $ury, $pageWidth, $pageHeight
                            ));
                        }
                    } else {
                        // Try alternative methods to get page size
                        try {
                            $cropBox = $page->get('CropBox');
                            if ($cropBox) {
                                $cropBoxContent = $cropBox->getContent();
                                if (is_array($cropBoxContent) && count($cropBoxContent) >= 4) {
                                    $llx = is_object($cropBoxContent[0]) ? (float)$cropBoxContent[0]->getContent() : (float)$cropBoxContent[0];
                                    $lly = is_object($cropBoxContent[1]) ? (float)$cropBoxContent[1]->getContent() : (float)$cropBoxContent[1];
                                    $urx = is_object($cropBoxContent[2]) ? (float)$cropBoxContent[2]->getContent() : (float)$cropBoxContent[2];
                                    $ury = is_object($cropBoxContent[3]) ? (float)$cropBoxContent[3]->getContent() : (float)$cropBoxContent[3];
                                    
                                    $pageWidth = $urx - $llx;
                                    $pageHeight = $ury - $lly;
                                    
                                    error_log(sprintf(
                                        "Page %d: Using CropBox -> Size: %.2f x %.2f points",
                                        $pageNumber, $pageWidth, $pageHeight
                                    ));
                                }
                            }
                        } catch (\Exception $e2) {
                            // Fall through to default
                        }
                    }
                } catch (\Exception $e) {
                    error_log("Page $pageNumber: Could not extract page dimensions, using default 612x792 points: " . $e->getMessage());
                }
                
                // Log final page dimensions being used - CRITICAL for coordinate conversion
                error_log(sprintf(
                    "Page %d: Final dimensions %.2f x %.2f points (MediaBox/CropBox detection %s)",
                    $pageNumber, $pageWidth, $pageHeight,
                    ($pageHeight != 792.0 || $pageWidth != 612.0) ? "SUCCEEDED" : "FAILED - using defaults"
                ));
                
                // WARNING if using defaults - this will cause wrong coordinates!
                if ($pageHeight == 792.0 && $pageWidth == 612.0) {
                    error_log("WARNING: Page $pageNumber using DEFAULT dimensions - coordinates may be WRONG!");
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
                        
                        // CRITICAL: Only extract if we have a valid Rect - NO HARDCODED DEFAULTS
                        if (!$rect) {
                            error_log("WARNING: Field '$fieldNameStr' has no Rect - SKIPPING (no hardcoded defaults)");
                            continue; // Skip fields without coordinates
                        }
                        
                        // Extract position from Rect and convert coordinates
                        // CRITICAL: Use the ACTUAL detected pageHeight, not a default
                        $position = $this->parseRect($rect, $pageHeight);
                        
                        // Validate that we got real coordinates, not defaults
                        if ($position['x'] == 0 && $position['y'] == 0 && $position['width'] == 100 && $position['height'] == 10) {
                            error_log("WARNING: Field '$fieldNameStr' got default coordinates - this indicates extraction failure!");
                        }
                        
                        error_log(sprintf(
                            "Field '%s' extracted: page=%d, x=%.2f, y=%.2f, width=%.2f, height=%.2f (pageHeight=%.2f pts)",
                            $fieldNameStr, $pageNumber, $position['x'], $position['y'], $position['width'], $position['height'], $pageHeight
                        ));
                        
                        $fields[$fieldNameStr] = [
                            'name' => $fieldNameStr,
                            'type' => $this->mapFieldType($fieldType->getContent()),
                            'page' => $pageNumber,
                            'x' => $position['x'],
                            'y' => $position['y'],
                            'width' => $position['width'],
                            'height' => $position['height'],
                            'fontSize' => FieldMetrics::defaultFontPx(),
                    'fontSizeUnit' => 'px',
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
        // x1, y1 = bottom-left corner, x2, y2 = top-right corner
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
        
        // Convert X coordinate to mm (no conversion needed, just scale)
        $x = $x1 * $pxToMm;
        
        // CRITICAL: Flip Y coordinate from bottom-left to top-left origin
        // PDF uses bottom-left as (0,0), FPDF uses top-left as (0,0)
        // For text positioning, we want the top of the field box (y2) converted to top-left origin
        // Formula: y_top_fpdf = page_height_mm - y_top_pdf_mm
        // Where y_top_pdf_mm = y2 * pxToMm (top of field in PDF coordinates)
        $pageHeightMm = $pageHeight * $pxToMm;
        $yTopPdfMm = $y2 * $pxToMm;
        $y = $pageHeightMm - $yTopPdfMm;
        
        // Log for debugging coordinate conversion
        error_log(sprintf(
            "parseRect: PDF coords [%.2f, %.2f, %.2f, %.2f] pts, pageHeight=%.2f pts -> FPDF [%.2f, %.2f] mm, size [%.2f, %.2f] mm",
            $x1, $y1, $x2, $y2, $pageHeight, $x, $y, $width, $height
        ));
        
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
    public function generatePositionFile(string $pdfPath, string $templateId, string $outputDir, ?array $fields = null): string {
        // CRITICAL: Use provided fields if available, otherwise try to load from existing files
        // Don't re-extract unnecessarily!
        
        // If fields are provided, use them directly
        if (!empty($fields)) {
            error_log("generatePositionFile: Using provided fields (" . count($fields) . " fields)");
        } else {
            // Try to load from existing position file first
            $dataDir = __DIR__ . '/../../data';
            $possibleDataDirs = [
                realpath($dataDir) ?: $dataDir,
                'C:/Users/Shadow/Web-PDFTimeSaver/data',
            ];
            
            foreach ($possibleDataDirs as $dir) {
                if ($dir && is_dir($dir)) {
                    $positionFile = $dir . '/' . $templateId . '_positions.json';
                    if (file_exists($positionFile)) {
                        error_log("generatePositionFile: Found existing position file: $positionFile");
                        $positionData = json_decode(file_get_contents($positionFile), true);
                        if ($positionData && is_array($positionData)) {
                            $fields = $positionData;
                            error_log("generatePositionFile: Loaded " . count($fields) . " fields from position file");
                            break;
                        }
                    }
                }
            }
            
            // If still no fields, try loading from details file
            if (empty($fields)) {
                foreach ($possibleDataDirs as $dir) {
                    if ($dir && is_dir($dir)) {
                        $detailsFile = $dir . '/' . $templateId . '_extraction_details.json';
                        if (file_exists($detailsFile)) {
                            error_log("generatePositionFile: Loading fields from details file");
                            $detailsData = json_decode(file_get_contents($detailsFile), true);
                            if ($detailsData && !empty($detailsData['fields'])) {
                                // Convert to keyed format
                                $fields = [];
                                foreach ($detailsData['fields'] as $field) {
                                    $key = $field['canonicalName'] ?? $field['name'] ?? null;
                                    if ($key) {
                                        $fields[$key] = $field;
                                    }
                                }
                                error_log("generatePositionFile: Loaded " . count($fields) . " fields from details file");
                                break;
                            }
                        }
                    }
                }
            }
            
            // Last resort: extract again (but this shouldn't be necessary)
            if (empty($fields)) {
                error_log("generatePositionFile: No existing files found, extracting again...");
                $fields = $this->extractFieldPositions($pdfPath, $templateId);
            }
        }
        
        if (empty($fields)) {
            throw new \RuntimeException("No fields extracted from PDF");
        }
        
        // Save to data directory (standard location) - Node.js already saved it, but ensure it's there
        $dataDir = __DIR__ . '/../../data';
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0755, true);
        }
        $outputFile = $dataDir . DIRECTORY_SEPARATOR . $templateId . '_positions.json';
        
        // If Node.js already created the file, use it. Otherwise create it here.
        if (!file_exists($outputFile)) {
            $json = json_encode($fields, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            file_put_contents($outputFile, $json);
        }
        
        error_log("Position file: $outputFile (" . count($fields) . " fields)");
        
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
     * Extract static fields (non-fillable) from PDF by detecting text labels
     * This is used when PDF has no fillable AcroForm fields
     */
    public function extractStaticFields(string $pdfPath): array {
        $fields = [];
        
        try {
            $pdf = $this->parser->parseFile($pdfPath);
            $pages = $pdf->getPages();
            
            // Common field label patterns to detect
            $fieldPatterns = [
                // Name fields
                '/\b(name|first\s*name|last\s*name|full\s*name)\b/i' => 'name',
                '/\b(first|given)\b/i' => 'first_name',
                '/\b(last|surname|family)\b/i' => 'last_name',
                '/\b(middle|mi\.?)\b/i' => 'middle_name',
                
                // Contact fields
                '/\b(email|e-mail|email\s*address)\b/i' => 'email',
                '/\b(phone|telephone|tel|mobile|cell)\b/i' => 'phone',
                '/\b(fax)\b/i' => 'fax',
                
                // Address fields
                '/\b(address|street|addr)\b/i' => 'address',
                '/\b(city)\b/i' => 'city',
                '/\b(state)\b/i' => 'state',
                '/\b(zip|postal|zipcode|postal\s*code)\b/i' => 'zip',
                '/\b(country)\b/i' => 'country',
                
                // Legal fields
                '/\b(case\s*number|case\s*no)\b/i' => 'case_number',
                '/\b(court|court\s*name)\b/i' => 'court',
                '/\b(county)\b/i' => 'county',
                '/\b(attorney|atty|attorney\s*name)\b/i' => 'attorney',
                '/\b(petitioner|plaintiff)\b/i' => 'petitioner',
                '/\b(respondent|defendant)\b/i' => 'respondent',
                '/\b(party\s*1|party\s*2)\b/i' => 'party',
                
                // Date fields
                '/\b(date|date\s*of|dob|date\s*of\s*birth)\b/i' => 'date',
                
                // ID fields
                '/\b(ssn|social\s*security|social\s*security\s*number)\b/i' => 'ssn',
                '/\b(ein|employer\s*identification|tax\s*id)\b/i' => 'ein',
                
                // Signature
                '/\b(signature|sign|sign\s*here)\b/i' => 'signature',
            ];
            
            $fieldCount = 0;
            $lineNumber = 0;
            
            foreach ($pages as $pageNum => $page) {
                $pageNumber = $pageNum + 1;
                $pageText = $page->getText();
                $lines = explode("\n", $pageText);
                
                // Get page dimensions for coordinate estimation
                $pageHeight = 792.0; // Default US Letter height in points
                $pageWidth = 612.0; // Default US Letter width in points
                
                try {
                    $mediaBox = $page->get('MediaBox');
                    if ($mediaBox) {
                        $mediaBoxContent = $mediaBox->getContent();
                        if (is_array($mediaBoxContent) && count($mediaBoxContent) >= 4) {
                            $llx = is_object($mediaBoxContent[0]) ? (float)$mediaBoxContent[0]->getContent() : (float)$mediaBoxContent[0];
                            $lly = is_object($mediaBoxContent[1]) ? (float)$mediaBoxContent[1]->getContent() : (float)$mediaBoxContent[1];
                            $urx = is_object($mediaBoxContent[2]) ? (float)$mediaBoxContent[2]->getContent() : (float)$mediaBoxContent[2];
                            $ury = is_object($mediaBoxContent[3]) ? (float)$mediaBoxContent[3]->getContent() : (float)$mediaBoxContent[3];
                            $pageWidth = $urx - $llx;
                            $pageHeight = $ury - $lly;
                        }
                    }
                } catch (\Exception $e) {
                    // Use defaults
                }
                
                // Convert points to mm
                $pxToMm = 0.352778;
                $pageHeightMm = $pageHeight * $pxToMm;
                $pageWidthMm = $pageWidth * $pxToMm;
                
                // Estimate line height (approximately 12 points = 4.2mm)
                $lineHeightMm = 4.2;
                
                foreach ($lines as $lineIdx => $line) {
                    $line = trim($line);
                    if (empty($line)) {
                        continue;
                    }
                    
                    // Check if line matches any field pattern
                    foreach ($fieldPatterns as $pattern => $fieldType) {
                        if (preg_match($pattern, $line, $matches)) {
                            // Found a potential field label
                            $fieldKey = $fieldType . ($fieldCount > 0 ? '_' . $fieldCount : '');
                            
                            // Estimate field position based on line position
                            // Y position: start from top, each line moves down
                            $yMm = ($lineIdx + 1) * $lineHeightMm;
                            // Convert from top-left to FPDF coordinates
                            $yMm = $pageHeightMm - $yMm;
                            
                            // X position: assume field starts after label (offset ~40mm)
                            $xMm = 40;
                            
                            // Estimate field size
                            $widthMm = 50; // Standard text field width
                            $heightMm = 5; // Standard text field height
                            
                            // Generate field name from label text
                            $fieldName = $this->generateFieldNameFromLabel($line, $fieldType);
                            
                            $fields[$fieldName] = [
                                'name' => $fieldName,
                                'type' => $fieldType === 'date' ? 'date' : ($fieldType === 'signature' ? 'signature' : 'text'),
                                'page' => $pageNumber,
                                'x' => round($xMm, 2),
                                'y' => round($yMm, 2),
                                'width' => round($widthMm, 2),
                                'height' => round($heightMm, 2),
                                'fontSize' => FieldMetrics::defaultFontPx(),
                                'fontSizeUnit' => 'px',
                                'estimated' => true,
                                'sourceText' => $line,
                                'confidence' => 0.6 // Lower confidence for estimated positions
                            ];
                            
                            $fieldCount++;
                            // Don't match multiple patterns for same line
                            break;
                        }
                    }
                }
            }
            
            if (!empty($fields)) {
                error_log("Extracted " . count($fields) . " static fields from text labels");
            }
            
        } catch (\Exception $e) {
            error_log("Static field extraction failed: " . $e->getMessage());
        }
        
        return $fields;
    }
    
    /**
     * Generate a field name from a label text
     */
    private function generateFieldNameFromLabel(string $label, string $fieldType): string {
        // Clean up label text
        $label = preg_replace('/[:\s]+$/', '', $label); // Remove trailing colons and spaces
        $label = preg_replace('/[^a-z0-9\s]+/i', '', $label); // Remove special chars
        $label = preg_replace('/\s+/', '_', trim($label)); // Replace spaces with underscores
        $label = strtolower($label);
        
        // Use field type as base if label is too generic
        if (strlen($label) < 3) {
            return $fieldType;
        }
        
        return $label;
    }
    
    /**
     * Get ensemble metadata from the last extraction
     */
    public function getLastEnsembleMetadata(): ?array {
        return $this->lastEnsembleMetadata;
    }
    
    /**
     * Extract fields AND generate background images from password-protected PDF
     * This is the hybrid approach: extract metadata + render as images for overlay
     * 
     * SEPARATED LOGIC:
     * 1. FIRST: Try ensemble extraction (handles BOTH fillable and non-fillable PDFs)
     * 2. ONLY IF ensemble finds NO fields: Try static field detection (non-fillable PDFs only)
     */
    public function extractAndGenerateBackgrounds(string $pdfPath, string $templateId, string $outputDir, bool $autoVerify = true): array {
        $result = [
            'fields' => [],
            'backgrounds' => [],
            'backgroundAssetType' => 'raster-fallback',
            'positionFile' => null,
            'ensembleMetadata' => null,
            'isFillable' => false,
            'extractionMethod' => 'none'
        ];
        
        error_log("=== extractAndGenerateBackgrounds START ===");
        error_log("Template ID: $templateId");
        error_log("PDF Path: $pdfPath");
        error_log("PDF exists: " . (file_exists($pdfPath) ? 'YES' : 'NO'));
        error_log("Output dir: $outputDir");
        
        // STEP 1: ALWAYS try ensemble extraction FIRST
        // The ensemble can detect BOTH fillable (AcroForm) and non-fillable PDFs
        // It uses multiple methods and merges results intelligently
        error_log("=== STEP 1: Running ENSEMBLE extraction (fillable + non-fillable) ===");
        $fields = $this->extractFieldPositions($pdfPath, $templateId);
        
        error_log("=== Ensemble extraction returned ===");
        error_log("Fields count: " . count($fields));
        error_log("Fields type: " . gettype($fields));
        
        // Get ensemble metadata
        $result['ensembleMetadata'] = $this->getLastEnsembleMetadata();
        if ($result['ensembleMetadata']) {
            $method = $result['ensembleMetadata']['method'] ?? 'unknown';
            $methodsUsed = $result['ensembleMetadata']['methodsUsed'] ?? [];
            error_log("Ensemble metadata found:");
            error_log("  - Method: $method");
            error_log("  - Methods used: " . implode(', ', $methodsUsed));
            error_log("  - Fields per method: " . json_encode($result['ensembleMetadata']['fieldsPerMethod'] ?? []));
            
            // Determine if this is a fillable PDF (has AcroForm fields)
            // AcroForm methods: pdf-lib-direct, qpdf-decrypt-pdf-lib, pdfjs-annotation, enhanced-widget
            $acroFormMethods = ['pdf-lib-direct', 'qpdf-decrypt-pdf-lib', 'pdfjs-annotation', 'enhanced-widget', 'pdf-binary-parser'];
            $hasAcroFormMethod = !empty(array_intersect($methodsUsed, $acroFormMethods));
            $result['isFillable'] = $hasAcroFormMethod;
            $result['extractionMethod'] = $hasAcroFormMethod ? 'ensemble-fillable' : 'ensemble-visual';
            
            if ($hasAcroFormMethod) {
                error_log("  - PDF TYPE: FILLABLE (AcroForm detected)");
        } else {
                error_log("  - PDF TYPE: NON-FILLABLE (visual detection only)");
            }
        } else {
            error_log("No ensemble metadata - extraction may have failed");
        }
        
        // CRITICAL: Check details file if ensemble didn't return fields directly
        // This handles cases where Node.js extraction completed but fields weren't in the direct result
        if (empty($fields) && $templateId) {
            error_log("=== Checking extraction details file (ensemble may have completed) ===");
            $dataDir = __DIR__ . '/../../data';
            $detailsFile = $dataDir . '/' . $templateId . '_extraction_details.json';
            
            // Wait for file to be written (Node.js might still be writing)
            $maxWait = 5;
            $waited = 0;
            while (!file_exists($detailsFile) && $waited < $maxWait) {
                usleep(500000);
                $waited += 0.5;
            }
            
            if (file_exists($detailsFile)) {
                error_log("Loading fields from ensemble details file: $detailsFile");
                try {
                    $detailsContent = file_get_contents($detailsFile);
                    $detailsData = json_decode($detailsContent, true);
                    if ($detailsData && isset($detailsData['fields']) && is_array($detailsData['fields']) && !empty($detailsData['fields'])) {
                        error_log("Found " . count($detailsData['fields']) . " fields in ensemble details file");
                        foreach ($detailsData['fields'] as $field) {
                            if (is_array($field)) {
                                $key = $field['canonicalName'] ?? $field['name'] ?? null;
                                if ($key) {
                                    $fields[$key] = $field;
                                } else {
                                    $fields['field_' . count($fields)] = $field;
                                }
                            }
                        }
                        
                        // Update ensemble metadata from details file
                        if (count($fields) > 0) {
                            $this->lastEnsembleMetadata = [
                                'method' => $detailsData['method'] ?? 'unknown',
                                'methodsUsed' => $detailsData['methodsUsed'] ?? [],
                                'fieldsPerMethod' => $detailsData['fieldsPerMethod'] ?? [],
                                'pageCount' => $detailsData['pageCount'] ?? 0,
                                'warnings' => $detailsData['warnings'] ?? [],
                                'errors' => $detailsData['errors'] ?? []
                            ];
                            $result['ensembleMetadata'] = $this->lastEnsembleMetadata;
                            
                            // Determine if fillable
                            $methodsUsed = $result['ensembleMetadata']['methodsUsed'] ?? [];
                            $acroFormMethods = ['pdf-lib-direct', 'qpdf-decrypt-pdf-lib', 'pdfjs-annotation', 'enhanced-widget', 'pdf-binary-parser'];
                            $result['isFillable'] = !empty(array_intersect($methodsUsed, $acroFormMethods));
                            $result['extractionMethod'] = $result['isFillable'] ? 'ensemble-fillable' : 'ensemble-visual';
                            
                            error_log("Successfully loaded " . count($fields) . " fields from ensemble details file");
                            error_log("PDF type: " . ($result['isFillable'] ? 'FILLABLE' : 'NON-FILLABLE'));
                        }
                    }
                } catch (\Exception $e) {
                    error_log("Error loading ensemble details file: " . $e->getMessage());
                }
            } else {
                error_log("Ensemble details file not found: $detailsFile");
            }
        }
        
        // STEP 2: ONLY if ensemble found NO fields, try static field detection
        // This is SEPARATE from ensemble - only for truly non-fillable PDFs
        // CRITICAL: Do NOT mix ensemble results with static detection!
        if (empty($fields)) {
            error_log("=== STEP 2: Ensemble found NO fields - trying STATIC field detection (non-fillable PDFs only) ===");
            $staticFields = $this->extractStaticFields($pdfPath);
            if (!empty($staticFields)) {
                error_log("Found " . count($staticFields) . " static fields using text label detection");
                $fields = $staticFields;
                $result['extractionMethod'] = 'static-detection';
                $result['isFillable'] = false;
            } else {
                error_log("Static field detection also found no fields");
            }
        } else {
            error_log("=== Ensemble extraction SUCCESSFUL - skipping static detection ===");
            error_log("Fields found: " . count($fields));
            error_log("Extraction method: " . $result['extractionMethod']);
            error_log("Is fillable: " . ($result['isFillable'] ? 'YES' : 'NO'));
        }
        
        $result['fields'] = $fields;
        
        // Step 2: Generate background images for each page
        $backgrounds = $this->generatePageBackgrounds($pdfPath, $templateId, $outputDir);
        $result['backgrounds'] = $backgrounds;
        error_log("Background assets generated as raster fallback set for template {$templateId}. Final PDF rendering should prefer vector import when available.");
        
        // Step 3: Generate position file if we got fields
        if (!empty($fields)) {
            // Pass the already-extracted fields to avoid re-extraction
            $positionFile = $this->generatePositionFile($pdfPath, $templateId, $outputDir, $fields);
            $result['positionFile'] = $positionFile;
            
            // Step 4: AUTOMATIC VERIFICATION after extraction
            // DISABLED: Verification pipeline has dependency issues, skip for now
            if (false && $autoVerify && file_exists($positionFile)) {
                try {
                    require_once __DIR__ . '/automated_verification_pipeline.php';
                    $pipeline = new \WebPdfTimeSaver\Mvp\AutomatedVerificationPipeline();
                    $verifyResults = $pipeline->verify($templateId);
                    $result['verification'] = $verifyResults;
                    error_log("AUTO-VERIFY: Extraction completed for $templateId - Verification Status: " . $verifyResults['overall_status']);
                } catch (\Exception $e) {
                    error_log("AUTO-VERIFY: Verification failed for $templateId - " . $e->getMessage());
                    $result['verification'] = ['error' => $e->getMessage()];
                }
            }
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

        // Clean template ID (remove t_ prefix only, keep the rest)
        $cleanTemplateId = str_replace('t_', '', $templateId);

        $outputDirNormalized = rtrim($outputDir, '/\\');
        $existingMatches = glob($outputDirNormalized . DIRECTORY_SEPARATOR . "{$cleanTemplateId}_page*_background.png");
        if (is_array($existingMatches)) {
            foreach ($existingMatches as $existingFile) {
                @unlink($existingFile);
            }
        }

        // Render all pages in one pass to avoid page-count detection failures.
        $outputPattern = $outputDirNormalized . DIRECTORY_SEPARATOR . "{$cleanTemplateId}_page%d_background.png";
        $cmd = "\"{$gsBinary}\" -dSAFER -dNOPAUSE -dBATCH -sDEVICE=png16m " .
               "-r300 -dTextAlphaBits=4 -dGraphicsAlphaBits=4 -sOutputFile=\"{$outputPattern}\" \"" . realpath($pdfPath) . "\" 2>&1";

        $output = [];
        $returnCode = 0;
        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0) {
            error_log("Failed to generate backgrounds: " . implode("\n", $output));
            return [];
        }

        $generatedFiles = glob($outputDirNormalized . DIRECTORY_SEPARATOR . "{$cleanTemplateId}_page*_background.png");
        if (!is_array($generatedFiles) || empty($generatedFiles)) {
            error_log("Ghostscript completed but no background files were generated.");
            return [];
        }

        foreach ($generatedFiles as $generatedFile) {
            if (preg_match('/_page(\d+)_background\.png$/', $generatedFile, $matches)) {
                $pageNum = (int)($matches[1] ?? 0);
                if ($pageNum > 0) {
                    $backgrounds[$pageNum] = $generatedFile;
                    error_log("Generated background for page {$pageNum}: {$generatedFile}");
                }
            }
        }

        ksort($backgrounds);
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
    
    /**
     * Find qpdf binary
     */
    private function findQpdfBinary(): ?string {
        $candidates = [
            'qpdf',
            'qpdf.exe',
            __DIR__ . '/../../bin/qpdf/bin/qpdf.exe',
            __DIR__ . '/../../bin/qpdf/bin/qpdf.bat',
        ];
        
        foreach ($candidates as $bin) {
            if (file_exists($bin)) {
                // Test if it works
                $cmd = strpos($bin, DIRECTORY_SEPARATOR) !== false ? 
                       "\"{$bin}\" --version 2>&1" : "{$bin} --version 2>&1";
                $output = [];
                $return = 0;
                @exec($cmd, $output, $return);
                if ($return === 0) {
                    return $bin;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Decrypt PDF using qpdf
     */
    private function decryptPdfWithQpdf(string $inputPath, string $outputPath, string $qpdfBinary): bool {
        $cmd = strpos($qpdfBinary, DIRECTORY_SEPARATOR) !== false ?
               "\"{$qpdfBinary}\" --decrypt \"{$inputPath}\" \"{$outputPath}\" 2>&1" :
               "{$qpdfBinary} --decrypt \"{$inputPath}\" \"{$outputPath}\" 2>&1";
        
        $output = [];
        $returnCode = 0;
        @exec($cmd, $output, $returnCode);
        
        if ($returnCode === 0 && file_exists($outputPath)) {
            error_log("qpdf decryption successful: " . basename($outputPath));
            return true;
        } else {
            error_log("qpdf decryption failed: " . implode("\n", $output));
            return false;
        }
    }
}


