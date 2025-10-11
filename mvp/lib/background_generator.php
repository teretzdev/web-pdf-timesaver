<?php
declare(strict_types=1);

namespace WebPdfTimeSaver\Mvp;

/**
 * Modular Background Generator for Multi-Page PDF Forms
 * Reusable for any PDF form (FL-100, FL-105, etc.)
 */
class BackgroundGenerator {
    private string $uploadsDir;
    private ?string $gsBinary = null;
    
    public function __construct(string $uploadsDir = null) {
        $this->uploadsDir = $uploadsDir ?? __DIR__ . '/../../uploads';
        $this->gsBinary = $this->findGhostscript();
    }
    
    /**
     * Generate background images for all pages of a PDF
     * 
     * @param string $templateId Template identifier (e.g., 'fl100', 't_fl100_gc120')
     * @param string $sourcePdf Path to source PDF file
     * @param int $pageCount Number of pages to generate
     * @param bool $grayscale Generate grayscale (true) or color (false)
     * @return array Results for each page ['success' => bool, 'path' => string, 'size' => int]
     */
    public function generateAllPages(string $templateId, string $sourcePdf, int $pageCount, bool $grayscale = true): array {
        if (!file_exists($sourcePdf)) {
            throw new \RuntimeException("Source PDF not found: $sourcePdf");
        }
        
        if (!$this->gsBinary) {
            throw new \RuntimeException("Ghostscript not found. Install Ghostscript or place gs1000w64.exe in project root.");
        }
        
        $results = [];
        
        for ($page = 1; $page <= $pageCount; $page++) {
            $result = $this->generatePageBackground($templateId, $sourcePdf, $page, $grayscale);
            $results[$page] = $result;
        }
        
        return $results;
    }
    
    /**
     * Generate background image for a single page
     * 
     * @param string $templateId Template identifier
     * @param string $sourcePdf Path to source PDF
     * @param int $pageNum Page number (1-based)
     * @param bool $grayscale Generate grayscale image
     * @return array Result with success status, path, and size
     */
    public function generatePageBackground(string $templateId, string $sourcePdf, int $pageNum, bool $grayscale = true): array {
        $cleanTemplateId = $this->cleanTemplateId($templateId);
        $outputFile = $this->uploadsDir . "/{$cleanTemplateId}_page{$pageNum}_background.png";
        
        $device = $grayscale ? 'pnggray' : 'png16m';
        
        $cmd = "\"{$this->gsBinary}\" -dSAFER -dNOPAUSE -dBATCH -sDEVICE={$device} -r200 " .
               "-dFirstPage={$pageNum} -dLastPage={$pageNum} " .
               "-sOutputFile=\"{$outputFile}\" \"{$sourcePdf}\" 2>&1";
        
        $output = [];
        $returnCode = 0;
        exec($cmd, $output, $returnCode);
        
        $success = ($returnCode === 0 && file_exists($outputFile));
        $size = $success ? filesize($outputFile) : 0;
        
        return [
            'success' => $success,
            'path' => $outputFile,
            'size' => $size,
            'page' => $pageNum,
            'error' => $success ? null : implode("\n", $output)
        ];
    }
    
    /**
     * Get path to background image for a specific page
     * 
     * @param string $templateId Template identifier
     * @param int $pageNum Page number
     * @return string|null Path if exists, null otherwise
     */
    public function getBackgroundPath(string $templateId, int $pageNum): ?string {
        $cleanTemplateId = $this->cleanTemplateId($templateId);
        $path = $this->uploadsDir . "/{$cleanTemplateId}_page{$pageNum}_background.png";
        return file_exists($path) ? $path : null;
    }
    
    /**
     * Check if all page backgrounds exist for a template
     * 
     * @param string $templateId Template identifier
     * @param int $pageCount Expected page count
     * @return bool True if all backgrounds exist
     */
    public function hasAllBackgrounds(string $templateId, int $pageCount): bool {
        for ($page = 1; $page <= $pageCount; $page++) {
            if (!$this->getBackgroundPath($templateId, $page)) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Clean template ID to use in filenames
     */
    private function cleanTemplateId(string $templateId): string {
        // Remove 't_' prefix if present
        $cleaned = str_replace('t_', '', $templateId);
        // Replace underscores with nothing (fl100_gc120 -> fl100)
        // Or keep underscores for clarity - your choice
        return $cleaned;
    }
    
    /**
     * Find Ghostscript binary on system
     */
    private function findGhostscript(): ?string {
        $candidates = [
            'gswin64c',
            'gswin32c',
            'gs',
            dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'gs1000w64.exe',
            'C:\\Program Files\\gs\\gs10.00.0\\bin\\gswin64c.exe',
            'C:\\Program Files\\gs\\gs9.56.1\\bin\\gswin64c.exe'
        ];
        
        foreach ($candidates as $candidate) {
            // Check if it's an absolute path
            if (file_exists($candidate)) {
                return $candidate;
            }
            
            // Check if it's in PATH
            exec("where $candidate 2>nul", $output, $returnCode);
            if ($returnCode === 0 && !empty($output)) {
                return $output[0];
            }
        }
        
        return null;
    }
}

