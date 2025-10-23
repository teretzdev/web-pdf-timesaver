<?php
/**
 * PHP Bridge for Node.js PDF Position Extraction
 * Calls the Node.js extraction script from PHP
 */

declare(strict_types=1);

namespace WebPdfTimeSaver\Mvp;

class AutoPositionExtractor {
    
    private string $nodePath;
    private string $scriptPath;
    
    public function __construct() {
        $this->nodePath = $this->findNodeBinary();
        $this->scriptPath = __DIR__ . '/../../scripts/universal-field-extractor.js';
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
        $command = sprintf(
            '"%s" "%s" "%s" "%s"',
            $this->nodePath,
            $this->scriptPath,
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
        
        if ($returnCode === 0) {
            // Check for success indicators in output
            if (strpos($outputText, '✅ SUCCESS!') !== false) {
                $result['success'] = true;
                
                // Extract field count
                if (preg_match('/Fields extracted: (\d+)/', $outputText, $matches)) {
                    $result['fieldCount'] = (int)$matches[1];
                }
                
                // Try to load the generated position file
                $positionFile = __DIR__ . '/../../data/' . $templateId . '_positions.json';
                if (file_exists($positionFile)) {
                    try {
                        $positionData = json_decode(file_get_contents($positionFile), true);
                        if ($positionData && is_array($positionData)) {
                            // Convert keyed object to array format
                            $result['fields'] = array_values($positionData);
                            $result['method'] = 'nodejs-auto';
                        }
                    } catch (\Exception $e) {
                        $result['errors'][] = 'Failed to read position file: ' . $e->getMessage();
                    }
                }
                
            } else {
                $result['errors'][] = 'Node.js script completed but extraction failed';
                $result['errors'][] = 'Output: ' . $outputText;
            }
            
        } else {
            $result['errors'][] = 'Node.js script failed with return code: ' . $returnCode;
            $result['errors'][] = 'Output: ' . $outputText;
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
        $candidates = [
            'node',
            'node.exe',
            'C:\\Program Files\\nodejs\\node.exe',
            'C:\\Program Files (x86)\\nodejs\\node.exe'
        ];
        
        foreach ($candidates as $candidate) {
            // Check if it's an absolute path
            if (file_exists($candidate)) {
                return $candidate;
            }
            
            // Check if it's in PATH
            $output = [];
            $returnCode = 0;
            exec("where $candidate 2>&1", $output, $returnCode);
            if ($returnCode === 0 && !empty($output[0])) {
                return trim($output[0]);
            }
        }
        
        return '';
    }
    
    /**
     * Check if qpdf is available
     */
    private function isQpdfAvailable(): bool {
        $candidates = [
            __DIR__ . '/../../bin/qpdf/bin/qpdf.exe',
            __DIR__ . '/../../bin/qpdf.exe',
            'qpdf'
        ];
        
        foreach ($candidates as $candidate) {
            if (file_exists($candidate)) {
                return true;
            }
        }
        
        return false;
    }
}
