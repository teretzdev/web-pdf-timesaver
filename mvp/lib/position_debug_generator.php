<?php
/**
 * Position Debug Generator - Actually Useful Verification
 * 
 * Generates a PDF with visual markers showing where text SHOULD be,
 * so you can visually compare with where text ACTUALLY appears.
 * This is much more reliable than trying to extract text positions.
 */

declare(strict_types=1);

namespace WebPdfTimeSaver\Mvp;

use setasign\Fpdi\Fpdi;

class PositionDebugGenerator {
    
    /**
     * Generate a debug PDF showing expected positions as visual markers
     * 
     * @param string $templateId Template ID
     * @param array $positions Expected positions
     * @param array $values Field values to fill
     * @param string $outputPath Output PDF path
     * @return string Path to generated debug PDF
     */
    public function generateDebugPdf(string $templateId, array $positions, array $values, string $outputPath): string {
        // Ensure output directory exists
        $outputDir = dirname($outputPath);
        if (!is_dir($outputDir)) {
            if (!@mkdir($outputDir, 0755, true) && !is_dir($outputDir)) {
                throw new \RuntimeException("Failed to create output directory: $outputDir");
            }
        }
        
        // Load template PDF as background - use EXACT same method as pdf_form_filler
        $templateFile = $this->getTemplateFile($templateId);
        
        $pdf = new Fpdi();
        
        // First try background image (same as pdf_form_filler does)
        // BUT use US Letter size (215.9 x 279.4) to match actual PDF generation
        $bgImage = __DIR__ . '/../../uploads/fl100_background.png';
        if ($templateId === 't_fl100_gc120' && file_exists($bgImage)) {
            $pdf->AddPage('P', [215.9, 279.4]); // US Letter size to match positions
            $pdf->Image($bgImage, 0, 0, 215.9, 279.4); // Scale to US Letter
            error_log("PositionDebugGenerator: Using fl100_background.png as background (US Letter size)");
        } elseif ($templateFile && file_exists($templateFile)) {
            // Fallback to PDF template (same as pdf_form_filler)
            try {
                $pageCount = $pdf->setSourceFile($templateFile);
                if ($pageCount > 0) {
                    $tplId = $pdf->importPage(1);
                    $size = $pdf->getTemplateSize($tplId);
                    if ($size && isset($size['width']) && isset($size['height'])) {
                        $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';
                        $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                        $pdf->useTemplate($tplId, 0, 0, $size['width'], $size['height']);
                        error_log("PositionDebugGenerator: Using PDF template as background from: $templateFile");
                    } else {
                        throw new \Exception("Could not get template size");
                    }
                } else {
                    throw new \Exception("Template file has no pages");
                }
            } catch (\Exception $e) {
                // Fallback to blank page
                $pdf->AddPage('P', [215.9, 279.4]);
                error_log("PositionDebugGenerator: ERROR loading template: " . $e->getMessage());
            }
        } else {
            // Use US Letter size as last resort
            $pdf->AddPage('P', [215.9, 279.4]);
            error_log("PositionDebugGenerator: No background found, using blank page");
        }
        
        // Draw expected positions as colored boxes
        foreach ($positions as $fieldName => $position) {
            if (empty($values[$fieldName] ?? '')) {
                continue; // Skip empty fields
            }
            
            $x = (float)($position['x'] ?? 0);
            $y = (float)($position['y'] ?? 0);
            $width = (float)($position['width'] ?? 100);
            $height = (float)($position['height'] ?? 10);
            
            // Draw expected position box (green outline)
            $pdf->SetDrawColor(40, 167, 69); // Green
            $pdf->SetLineWidth(0.5);
            $pdf->Rect($x, $y, $width, $height);
            
            // Draw label
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->SetTextColor(40, 167, 69);
            $pdf->SetXY($x, $y - 5);
            $pdf->Cell($width, 5, $fieldName, 0, 0, 'L');
        }
        
        // Now draw actual text on top (so you can see if it aligns)
        $pdf->SetTextColor(0, 0, 0); // Black text
        $pdf->SetFont('Arial', '', 9);
        
        foreach ($positions as $fieldName => $position) {
            $value = $values[$fieldName] ?? '';
            if (empty($value)) {
                continue;
            }
            
            $x = (float)($position['x'] ?? 0);
            $y = (float)($position['y'] ?? 0);
            $width = (float)($position['width'] ?? 100);
            
            // Apply font settings if available
            if (isset($position['fontSize'])) {
                $pdf->SetFont('Arial', $position['fontStyle'] ?? '', (int)$position['fontSize']);
            }
            
            // Draw actual text
            $pdf->SetXY($x, $y);
            $pdf->Cell($width, 5, (string)$value, 0, 0, 'L');
        }
        
        // Save - ensure directory exists
        $outputDir = dirname($outputPath);
        if (!is_dir($outputDir)) {
            if (!mkdir($outputDir, 0755, true)) {
                throw new \RuntimeException("Failed to create output directory: $outputDir");
            }
        }
        
        // Save PDF
        try {
            $pdf->Output('F', $outputPath);
        } catch (\Exception $e) {
            throw new \RuntimeException("Failed to save PDF: " . $e->getMessage() . " at $outputPath");
        }
        
        if (!file_exists($outputPath)) {
            throw new \RuntimeException("PDF file was not created at: $outputPath");
        }
        
        return $outputPath;
    }
    
    /**
     * Generate comparison PDF: side-by-side expected vs actual
     */
    public function generateComparisonPdf(string $templateId, array $positions, array $values, string $actualPdfPath, string $outputPath): string {
        $pdf = new Fpdi();
        
        // Page 1: Expected positions (with boxes)
        $this->addExpectedPage($pdf, $templateId, $positions, $values);
        
        // Page 2: Actual PDF (imported)
        if (file_exists($actualPdfPath)) {
            try {
                $pdf->setSourceFile($actualPdfPath);
                $tplId = $pdf->importPage(1);
                $size = $pdf->getTemplateSize($tplId);
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($tplId);
            } catch (\Exception $e) {
                // Skip if can't import
            }
        }
        
        $pdf->Output('F', $outputPath);
        return $outputPath;
    }
    
    private function addExpectedPage(Fpdi $pdf, string $templateId, array $positions, array $values): void {
        // Use same background method as main generator
        $templateFile = $this->getTemplateFile($templateId);
        
        // First try background image (same as pdf_form_filler does)
        // Use US Letter size (215.9 x 279.4) to match actual PDF generation
        $bgImage = __DIR__ . '/../../uploads/fl100_background.png';
        if ($templateId === 't_fl100_gc120' && file_exists($bgImage)) {
            $pdf->AddPage('P', [215.9, 279.4]); // US Letter size to match positions
            $pdf->Image($bgImage, 0, 0, 215.9, 279.4); // Scale to US Letter
        } elseif ($templateFile && file_exists($templateFile)) {
            try {
                $pageCount = $pdf->setSourceFile($templateFile);
                if ($pageCount > 0) {
                    $tplId = $pdf->importPage(1);
                    $size = $pdf->getTemplateSize($tplId);
                    if ($size && isset($size['width']) && isset($size['height'])) {
                        $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';
                        $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                        $pdf->useTemplate($tplId, 0, 0, $size['width'], $size['height']);
                    } else {
                        throw new \Exception("Could not get template size");
                    }
                } else {
                    throw new \Exception("Template file has no pages");
                }
            } catch (\Exception $e) {
                $pdf->AddPage('P', [215.9, 279.4]);
            }
        } else {
            $pdf->AddPage('P', [215.9, 279.4]);
        }
        
        // Draw expected positions
        foreach ($positions as $fieldName => $position) {
            if (empty($values[$fieldName] ?? '')) continue;
            
            $x = (float)($position['x'] ?? 0);
            $y = (float)($position['y'] ?? 0);
            $width = (float)($position['width'] ?? 100);
            $height = (float)($position['height'] ?? 10);
            
            // Green box for expected
            $pdf->SetDrawColor(40, 167, 69);
            $pdf->SetLineWidth(0.5);
            $pdf->Rect($x, $y, $width, $height);
            
            // Label
            $pdf->SetFont('Arial', 'B', 7);
            $pdf->SetTextColor(40, 167, 69);
            $pdf->SetXY($x, $y - 4);
            $pdf->Cell($width, 4, $fieldName, 0, 0, 'L');
        }
    }
    
    private function getTemplateFile(string $templateId): ?string {
        $templateMap = [
            't_fl100_gc120' => 'fl100.pdf',
            't_fl105_gc120' => 'fl105.pdf',
        ];
        
        $templateFile = $templateMap[$templateId] ?? null;
        if (!$templateFile) {
            error_log("PositionDebugGenerator: Unknown template ID: $templateId");
            return null;
        }
        
        // Try multiple possible paths
        $possiblePaths = [
            __DIR__ . '/../../uploads/' . $templateFile,
            __DIR__ . '/../uploads/' . $templateFile,
            realpath(__DIR__ . '/../../uploads/' . $templateFile),
        ];
        
        foreach ($possiblePaths as $path) {
            if ($path && file_exists($path)) {
                error_log("PositionDebugGenerator: Found template at: $path");
                return $path;
            }
        }
        
        error_log("PositionDebugGenerator: Template file not found: $templateFile (checked: " . implode(', ', $possiblePaths) . ")");
        return null;
    }
}

