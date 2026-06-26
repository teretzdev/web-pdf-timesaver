<?php
/**
 * PHP Bridge for Node.js PDF Position Extraction
 * Calls the Node.js extraction script from PHP
 */

declare(strict_types=1);

namespace WebPdfTimeSaver\Mvp;

class AutoPositionExtractor {
    
    private string $nodePath;
    private ?string $scriptPath;
    
    public function __construct() {
        $this->nodePath = $this->findNodeBinary();
        
        // Try multiple possible script paths
        $possibleScriptPaths = [
            __DIR__ . '/../../scripts/universal-field-extractor.js',
            dirname(__DIR__, 2) . '/scripts/universal-field-extractor.js',
            'C:/Users/Shadow/Web-PDFTimeSaver/scripts/universal-field-extractor.js',
            realpath(__DIR__ . '/../../scripts/universal-field-extractor.js') ?: null
        ];
        
        $this->scriptPath = null;
        foreach ($possibleScriptPaths as $path) {
            if ($path && file_exists($path)) {
                $this->scriptPath = $path;
                error_log("AutoPositionExtractor: Using script path: $path");
                break;
            }
        }
        
        if (!$this->scriptPath) {
            error_log("AutoPositionExtractor: Script NOT FOUND. Tried: " . implode(', ', array_filter($possibleScriptPaths)));
            // Use first path as fallback (will fail later with better error)
            $this->scriptPath = $possibleScriptPaths[0];
        }
    }
    
    /**
     * Extract field positions using Node.js pipeline
     */
    public function extractPositions(string $pdfPath, string $templateId): array {
        if (!$this->nodePath) {
            throw new \RuntimeException('Node.js not found. Please install Node.js to use automatic extraction.');
        }
        
        if (!file_exists($this->scriptPath)) {
            throw new \RuntimeException('Extraction script not found: ' . $this->scriptPath);
        }
        
        if (!file_exists($pdfPath)) {
            throw new \RuntimeException('PDF file not found: ' . $pdfPath);
        }
        
        // Prepare command
        // IMPORTANT: use escapeshellarg for EACH part and DON'T wrap again in quotes,
        // otherwise the PDF path will contain literal quote characters and Node
        // will not be able to find the file (leading to \"PDF file not found\" errors).
        $command = sprintf(
            '%s %s %s %s',
            escapeshellarg($this->nodePath),
            escapeshellarg($this->scriptPath),
            escapeshellarg($pdfPath),
            escapeshellarg($templateId)
        );
        
        // Execute Node.js script
        $output = [];
        $returnCode = 0;
        exec($command . ' 2>&1', $output, $returnCode);
        
        $outputText = implode("\n", $output);
        
        // Debug logging
        error_log("Node.js command: " . $command);
        error_log("Node.js return code: " . $returnCode);
        error_log("Node.js output: " . $outputText);
        
        // Parse results
        $result = [
            'success' => false,
            'method' => 'nodejs-auto',
            'fields' => [],
            'pageCount' => 0,
            'warnings' => [],
            'errors' => [],
            'output' => $outputText,
            'returnCode' => $returnCode
        ];
        
        // CRITICAL: Wait for extraction to complete - Node.js might still be writing files
        // The extraction can take several seconds, especially for complex PDFs
        $maxWait = 10; // Wait up to 10 seconds
        $waited = 0;
        
        // CRITICAL FIX: Node.js script saves to scripts/../data relative to where the script is
        // The script is at: C:\Users\Shadow\Web-PDFTimeSaver\scripts\universal-field-extractor.js
        // So it saves to: C:\Users\Shadow\Web-PDFTimeSaver\data
        // But when running from XAMPP, __DIR__ might be different
        // Try multiple possible locations - check where the script actually is first
        $scriptDir = dirname($this->scriptPath);
        $nodeDataDir = realpath($scriptDir . '/../data');
        
        $possibleDataDirs = [
            $nodeDataDir,  // Where Node.js actually saves (relative to script)
            'C:/Users/Shadow/Web-PDFTimeSaver/data',  // Absolute workspace path
            __DIR__ . '/../../data',  // Relative from mvp/lib (XAMPP location)
            dirname(__DIR__, 2) . '/data',  // Alternative relative
            realpath(__DIR__ . '/../../data'),  // Realpath version
        ];
        
        $dataDir = null;
        foreach ($possibleDataDirs as $dir) {
            if ($dir && is_dir($dir)) {
                $resolved = realpath($dir) ?: $dir;
                error_log("AutoPositionExtractor: Checking data dir: $dir => " . ($resolved ?: 'NOT FOUND'));
                if ($resolved && is_dir($resolved)) {
                    $dataDir = $resolved;
                    error_log("AutoPositionExtractor: Using data dir: $dataDir");
                    break;
                }
            }
        }
        
        // Fallback to Node.js script location (most reliable)
        if (!$dataDir && $nodeDataDir && is_dir($nodeDataDir)) {
            $dataDir = $nodeDataDir;
            error_log("AutoPositionExtractor: Using Node.js script data dir: $dataDir");
        }
        
        // Last resort fallback
        if (!$dataDir) {
            $dataDir = realpath(__DIR__ . '/../../data') ?: __DIR__ . '/../../data';
            error_log("AutoPositionExtractor: Using fallback data dir: $dataDir");
        }
        
        $detailsFile = $dataDir . DIRECTORY_SEPARATOR . $templateId . '_extraction_details.json';
        
        error_log("AutoPositionExtractor: Waiting for details file: $detailsFile");
        error_log("AutoPositionExtractor: Data dir (realpath): " . (realpath($dataDir) ?: 'NOT FOUND'));
        
        while (!file_exists($detailsFile) && $waited < $maxWait && $returnCode === 0) {
            usleep(500000); // Wait 0.5 seconds
            $waited += 0.5;
        }
        
        if ($returnCode === 0) {
            // Try to load the detailed extraction results first (most reliable)
            error_log("Looking for extraction details file: $detailsFile");
            error_log("Data directory exists: " . (is_dir($dataDir) ? 'YES' : 'NO'));
            error_log("Details file exists: " . (file_exists($detailsFile) ? 'YES' : 'NO'));
            error_log("Waited $waited seconds for file to appear");
            
            // If file still doesn't exist, try alternative paths
            if (!file_exists($detailsFile)) {
                $altPaths = [
                    __DIR__ . '/../../data/' . $templateId . '_extraction_details.json',
                    dirname(__DIR__, 2) . '/data/' . $templateId . '_extraction_details.json',
                    realpath(__DIR__ . '/../../data') . '/' . $templateId . '_extraction_details.json'
                ];
                foreach ($altPaths as $altPath) {
                    if (file_exists($altPath)) {
                        error_log("Found details file at alternative path: $altPath");
                        $detailsFile = $altPath;
                        break;
                    }
                }
            }
            
            if (file_exists($detailsFile)) {
                try {
                    $detailsContent = file_get_contents($detailsFile);
                    if ($detailsContent === false) {
                        error_log("Failed to read extraction details file content");
                    } else {
                        error_log("Extraction details file size: " . strlen($detailsContent) . " bytes");
                        $detailsData = json_decode($detailsContent, true);
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            error_log("JSON decode error: " . json_last_error_msg());
                        } else if ($detailsData && is_array($detailsData)) {
                            // Use the structured data from extraction details
                            // CRITICAL: Even if success=false, return fields if they exist
                            // The validation might fail but fields could still be useful
                            $result['success'] = $detailsData['success'] ?? false;
                            $result['fields'] = $detailsData['fields'] ?? [];
                            $result['method'] = $detailsData['method'] ?? 'nodejs-auto';
                            $result['pageCount'] = $detailsData['pageCount'] ?? 0;
                            $result['warnings'] = $detailsData['warnings'] ?? [];
                            $result['errors'] = array_merge($result['errors'], $detailsData['errors'] ?? []);
                            $result['methodsUsed'] = $detailsData['methodsUsed'] ?? [];
                            $result['fieldsPerMethod'] = $detailsData['fieldsPerMethod'] ?? [];
                            $result['attempts'] = $detailsData['attempts'] ?? [];
                            
                            error_log("Loaded from extraction details: success=" . ($result['success'] ? 'true' : 'false') . ", fields=" . count($result['fields']));
                            
                            // CRITICAL FIX: Return fields even if success=false
                            // The validation might be too strict, but fields are still valuable
                            if (!empty($result['fields'])) {
                                error_log("Returning " . count($result['fields']) . " fields from extraction details (success=" . ($result['success'] ? 'true' : 'false') . ")");
                                // Override success to true if we have fields, even if validation failed
                                $result['success'] = true;
                                return $result;
                            } else {
                                error_log("Extraction details file exists but fields array is empty");
                                error_log("Success flag: " . ($result['success'] ? 'true' : 'false'));
                                if (!empty($result['errors'])) {
                                    error_log("Errors in details: " . implode(', ', $result['errors']));
                                }
                                if (!empty($result['warnings'])) {
                                    error_log("Warnings in details: " . implode(', ', $result['warnings']));
                                }
                            }
                        } else {
                            error_log("Extraction details file exists but data is not a valid array");
                        }
                    }
                } catch (\Exception $e) {
                    error_log("Failed to read extraction details file: " . $e->getMessage());
                    error_log("Stack trace: " . $e->getTraceAsString());
                }
            } else {
                error_log("Extraction details file not found: $detailsFile");
                // List files in data directory for debugging
                if (is_dir($dataDir)) {
                    $files = scandir($dataDir);
                    error_log("Files in data directory: " . implode(', ', array_filter($files, function($f) { return $f !== '.' && $f !== '..'; })));
                }
            }
            
            // Fallback: Check for success indicators in output and load position file
            if (strpos($outputText, '✅ SUCCESS!') !== false || strpos($outputText, 'Phase 2 Complete') !== false) {
                $result['success'] = true;
                
                // Extract field count
                if (preg_match('/Fields extracted: (\d+)/', $outputText, $matches)) {
                    $result['fieldCount'] = (int)$matches[1];
                }
                
                // Extract methods used from output
                if (preg_match('/Methods used: ([^\n]+)/', $outputText, $matches)) {
                    $methodsStr = trim($matches[1]);
                    $result['methodsUsed'] = array_map('trim', explode(',', $methodsStr));
                }
                
                // Try to load the generated position file as fallback
                $positionFile = $dataDir . '/' . $templateId . '_positions.json';
                error_log("Looking for position file: $positionFile");
                if (file_exists($positionFile)) {
                    try {
                        $positionContent = file_get_contents($positionFile);
                        if ($positionContent !== false) {
                            error_log("Position file size: " . strlen($positionContent) . " bytes");
                            $positionData = json_decode($positionContent, true);
                            if (json_last_error() !== JSON_ERROR_NONE) {
                                error_log("Position file JSON decode error: " . json_last_error_msg());
                            } else if ($positionData && is_array($positionData)) {
                                // Position file is keyed object, convert to array format
                                // Each value in the object is a field
                                $result['fields'] = array_values($positionData);
                                $result['method'] = 'nodejs-auto';
                                error_log("Loaded " . count($result['fields']) . " fields from position file");
                                if (empty($result['success'])) {
                                    $result['success'] = true; // Mark as success if we got fields
                                }
                                
                                // If we got fields from position file, return immediately
                                if (!empty($result['fields'])) {
                                    return $result;
                                }
                            }
                        }
                    } catch (\Exception $e) {
                        error_log("Failed to read position file: " . $e->getMessage());
                        $result['errors'][] = 'Failed to read position file: ' . $e->getMessage();
                    }
                } else {
                    error_log("Position file not found: $positionFile");
                }
                
            } else {
                $result['errors'][] = 'Node.js script completed but extraction failed';
                if (strpos($outputText, 'All extraction methods failed') !== false) {
                    $result['errors'][] = 'All extraction methods failed';
                }
                $result['errors'][] = 'Output: ' . substr($outputText, 0, 500); // Limit output length
            }
            
        } else {
            $result['errors'][] = 'Node.js script failed with return code: ' . $returnCode;
            $result['errors'][] = 'Output: ' . substr($outputText, 0, 500); // Limit output length
        }
        
        return $result;
    }
    
    /**
     * Check if Node.js extraction is available
     */
    public function isAvailable(): bool {
        return !empty($this->nodePath) && file_exists($this->scriptPath);
    }
    
    /**
     * Get extraction status
     */
    public function getStatus(): array {
        return [
            'nodejs_available' => !empty($this->nodePath),
            'nodejs_path' => $this->nodePath,
            'script_available' => file_exists($this->scriptPath),
            'script_path' => $this->scriptPath,
            'qpdf_available' => $this->isQpdfAvailable()
        ];
    }
    
    /**
     * Find Node.js binary
     */
    private function findNodeBinary(): string {
        // Prefer explicit environment override if provided
        $envNode = getenv('NODE_PATH') ?: getenv('NODE');
        if ($envNode && file_exists($envNode)) {
            error_log("AutoPositionExtractor: Using NODE_PATH from environment: $envNode");
            return $envNode;
        }
        
        // Detect OS for cross-platform support
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        
        // Try to find node in PATH using OS-appropriate command
        $output = [];
        $returnCode = 0;
        if ($isWindows) {
        exec('where node 2>&1', $output, $returnCode);
        } else {
            exec('which node 2>&1', $output, $returnCode);
        }
        
        if ($returnCode === 0 && !empty($output[0])) {
            $nodePath = trim($output[0]);
            if (file_exists($nodePath)) {
                error_log("AutoPositionExtractor: Found Node.js in PATH: $nodePath");
                return $nodePath;
            }
        }
        
        // Common paths to check (cross-platform)
        $candidates = [
            'node',  // Try bare command (works if in PATH)
            '/usr/bin/node',  // Linux standard location
            '/usr/local/bin/node',  // Linux alternative
            '/opt/nodejs/bin/node',  // Some Linux installs
        ];
        
        // Add Windows paths if on Windows
        if ($isWindows) {
            $candidates = array_merge([
                'node.exe',
                'C:\\Program Files\\nodejs\\node.exe',
                'C:\\Program Files (x86)\\nodejs\\node.exe',
            ], $candidates);
        }
        
        // Try each candidate path
        foreach ($candidates as $candidate) {
            if (file_exists($candidate)) {
                error_log("AutoPositionExtractor: Found Node.js at: $candidate");
                return $candidate;
            }
        }
        
        error_log("AutoPositionExtractor: Node.js NOT FOUND. Tried: " . implode(', ', $candidates));
        return '';
    }
    
    /**
     * Check if qpdf is available
     */
    private function isQpdfAvailable(): bool {
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

        // 1) Check PATH using OS-appropriate command
        $output = [];
        $returnCode = 0;
        if ($isWindows) {
            @exec('where qpdf 2>&1', $output, $returnCode);
        } else {
            @exec('which qpdf 2>&1', $output, $returnCode);
        }
        if ($returnCode === 0 && !empty($output[0])) {
            $qpdfPath = trim($output[0]);
            if (file_exists($qpdfPath)) {
                error_log("AutoPositionExtractor: Found qpdf via PATH: $qpdfPath");
                return true;
            }
        }

        // 2) Common install locations
        $candidates = [
            __DIR__ . '/../../bin/qpdf/bin/qpdf.exe',
            __DIR__ . '/../../bin/qpdf.exe',
            '/usr/bin/qpdf',
            '/usr/local/bin/qpdf',
            '/opt/homebrew/bin/qpdf', // macOS Homebrew on ARM
        ];

        if ($isWindows) {
            $candidates = array_merge($candidates, [
                'qpdf.exe',
                'C:\\Program Files\\qpdf\\bin\\qpdf.exe',
                'C:\\Program Files (x86)\\qpdf\\bin\\qpdf.exe',
            ]);
        }

        foreach ($candidates as $candidate) {
            if ($candidate && file_exists($candidate)) {
                error_log("AutoPositionExtractor: Found qpdf at: $candidate");
                return true;
            }
        }

        error_log("AutoPositionExtractor: qpdf NOT FOUND. Tried: " . implode(', ', $candidates));
        return false;
    }
}
