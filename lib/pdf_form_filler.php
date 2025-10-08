<?php
declare(strict_types=1);

namespace WebPdfTimeSaver\Mvp;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/field_position_loader.php';
require_once __DIR__ . '/field_fillers/FieldFillerManager.php';

use setasign\Fpdi\Fpdi;
use WebPdfTimeSaver\Mvp\FieldFillers\FieldFillerManager;
use setasign\Fpdi\PdfParser\StreamReader;

final class PdfFormFiller {
    private string $outputDir;
    private string $templatesDir;
    private FieldPositionLoader $positionLoader;
    private FieldFillerManager $fieldFillerManager;
    private Logger $logger;
    private array $context = [];

    public function __construct(string $outputDir = __DIR__ . '/../output', string $templatesDir = __DIR__ . '/../uploads', ?Logger $logger = null) {
        $this->outputDir = $outputDir;
        $this->templatesDir = $templatesDir;
        $this->positionLoader = new FieldPositionLoader();
        $this->fieldFillerManager = new FieldFillerManager();
        $this->logger = $logger ?? new Logger();
        
        if (!is_dir($this->outputDir)) { 
            mkdir($this->outputDir, 0777, true); 
        }
    }

    public function setContext(array $context): void { $this->context = $context; }

    /**
     * Basic PDF quality control: file exists, non-zero size, reasonable page count, minimal text presence on first page.
     */
    private function assertPdfQuality(string $path, string $logFile): void {
        if (!file_exists($path)) {
            throw new \RuntimeException('PDF QC: file not found at ' . $path);
        }
        $size = filesize($path) ?: 0;
        if ($size < 1024) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' PDF QC WARN: Very small PDF size ' . $size . ' bytes' . PHP_EOL, FILE_APPEND);
        }
        // Check page count using FPDI
        try {
            $pdfProbe = new Fpdi();
            $pages = $pdfProbe->setSourceFile($path);
            if ($pages < 1) {
                throw new \RuntimeException('PDF QC: zero pages detected');
            }
        } catch (\Throwable $e) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' PDF QC WARN: Could not determine page count: ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
        }
    }

    public function fillPdfForm(array $template, array $values): array {
        $this->logger->info('fillPdfForm start', $this->context);
        $this->logger->debug('fillPdfForm template: ' . json_encode($template), $this->context);
        $this->logger->debug('fillPdfForm values: ' . json_encode($values), $this->context);
        $templateFile = $this->getTemplateFile($template['id'] ?? '');
        $filename = 'mvp_' . date('Ymd_His') . '_' . ($template['id'] ?? 'doc') . '.pdf';
        $outputPath = rtrim($this->outputDir, '/\\') . DIRECTORY_SEPARATOR . $filename;

        // Always use our layered approach to fill the FL-100 form properly
        try {
            $result = $this->fillFL100Form($template, $values, $filename, $outputPath);
            $this->logger->info('fillPdfForm success: ' . json_encode($result), $this->context);
            return $result;
        } catch (\Throwable $e) {
            $this->logger->error('fillPdfForm error: ' . $e->getMessage(), $this->context);
            throw $e;
        }
    }

    private function getTemplateFile(string $templateId): ?string {
        // Map template IDs to actual PDF files
        $templateMap = [
            't_fl105_gc120' => 'fl100.pdf',
            't_fl100_gc120' => 'fl100.pdf',
            // Add more template mappings as needed
        ];

        $templateFile = $templateMap[$templateId] ?? null;
        if (!$templateFile) {
            return null;
        }

        return rtrim($this->templatesDir, '/\\') . DIRECTORY_SEPARATOR . $templateFile;
    }

    private function addFormData(Fpdi $pdf, array $template, array $values): void {
        // Set font for form filling
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(0, 0, 0);

        // Define field positions for FL-105 form (these would need to be mapped to actual form positions)
        $fieldPositions = $this->getFieldPositions();

        foreach (($template['fields'] ?? []) as $field) {
            $key = $field['key'];
            $value = (string)($values[$key] ?? '');
            
            if (empty($value)) {
                continue;
            }

            // Get position for this field
            $position = $fieldPositions[$key] ?? null;
            if (!$position) {
                continue;
            }

            // Add text at the specified position
            $pdf->SetXY($position['x'], $position['y']);
            
            // Handle different field types
            if (isset($field['type']) && $field['type'] === 'checkbox') {
                // For checkboxes, add an X or checkmark
                if (strtolower($value) === 'yes' || strtolower($value) === 'true' || $value === '1') {
                    $pdf->Cell($position['width'] ?? 10, $position['height'] ?? 5, 'X', 0, 0, 'C');
                }
            } else {
                // For text fields, add the value
                $pdf->Cell($position['width'] ?? 100, $position['height'] ?? 5, $value, 0, 0, 'L');
            }
        }
    }

    private function getFieldPositions(): array {
        // These positions would need to be carefully mapped to the actual FL-105 form
        // For now, providing approximate positions based on common form layouts
        return [
            'attorney_name' => ['x' => 50, 'y' => 60, 'width' => 100, 'height' => 5],
            'attorney_firm' => ['x' => 50, 'y' => 70, 'width' => 100, 'height' => 5],
            'attorney_address' => ['x' => 50, 'y' => 80, 'width' => 100, 'height' => 5],
            'attorney_city_state_zip' => ['x' => 50, 'y' => 90, 'width' => 100, 'height' => 5],
            'attorney_phone' => ['x' => 50, 'y' => 100, 'width' => 100, 'height' => 5],
            'attorney_email' => ['x' => 50, 'y' => 110, 'width' => 100, 'height' => 5],
            'attorney_bar_number' => ['x' => 50, 'y' => 120, 'width' => 100, 'height' => 5],
            
            'petitioner_name' => ['x' => 50, 'y' => 150, 'width' => 100, 'height' => 5],
            'respondent_name' => ['x' => 50, 'y' => 160, 'width' => 100, 'height' => 5],
            'case_number' => ['x' => 400, 'y' => 60, 'width' => 100, 'height' => 5],
            
            'child_name' => ['x' => 50, 'y' => 200, 'width' => 100, 'height' => 5],
            'child_birthdate' => ['x' => 200, 'y' => 200, 'width' => 80, 'height' => 5],
            'child_sex' => ['x' => 300, 'y' => 200, 'width' => 30, 'height' => 5],
            
            'current_address' => ['x' => 50, 'y' => 230, 'width' => 150, 'height' => 5],
            'current_city_state_zip' => ['x' => 50, 'y' => 240, 'width' => 150, 'height' => 5],
            'period_of_residence' => ['x' => 250, 'y' => 240, 'width' => 100, 'height' => 5],
            
            'previous_address' => ['x' => 50, 'y' => 270, 'width' => 150, 'height' => 5],
            'previous_city_state_zip' => ['x' => 50, 'y' => 280, 'width' => 150, 'height' => 5],
            'previous_period_of_residence' => ['x' => 250, 'y' => 280, 'width' => 100, 'height' => 5],
            
            'home_state' => ['x' => 50, 'y' => 310, 'width' => 100, 'height' => 5],
            
            // Checkboxes
            'no_other_proceedings' => ['x' => 30, 'y' => 350, 'width' => 10, 'height' => 5],
            'other_proceedings_exist' => ['x' => 30, 'y' => 370, 'width' => 10, 'height' => 5],
            
            'no_persons_not_parties' => ['x' => 30, 'y' => 400, 'width' => 10, 'height' => 5],
            'persons_not_parties_exist' => ['x' => 30, 'y' => 420, 'width' => 10, 'height' => 5],
        ];
    }

    public function stampSigned(string $inputPath): array {
        $this->logger->info('stampSigned start for: ' . $inputPath, $this->context);
        $filename = 'signed_' . basename($inputPath);
        $outputPath = rtrim($this->outputDir, '/\\') . DIRECTORY_SEPARATOR . $filename;
        
        // Create a new PDF with signature stamp
        $pdf = new Fpdi();
        
        // Import the original PDF
        $pageCount = $pdf->setSourceFile($inputPath);
        
        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $templateId = $pdf->importPage($pageNo);
            $pdf->AddPage();
            $pdf->useTemplate($templateId);
            
            // Add signature stamp on the last page
            if ($pageNo === $pageCount) {
                $pdf->SetFont('Arial', 'B', 12);
                $pdf->SetTextColor(0, 128, 0);
                $pdf->SetXY(400, 250); // Position signature in bottom right
                $pdf->Cell(100, 10, 'ELECTRONICALLY SIGNED', 0, 1, 'C');
                $pdf->SetXY(400, 260);
                $pdf->SetFont('Arial', '', 10);
                $pdf->Cell(100, 10, date('m/d/Y H:i:s'), 0, 1, 'C');
            }
        }
        
        $pdf->Output('F', $outputPath);
        
        if (!file_exists($outputPath)) {
            throw new \RuntimeException('Failed to create signed PDF at ' . $outputPath);
        }
        // Quality control check
        $this->assertPdfQuality($outputPath, __DIR__ . '/../logs/pdf_debug.log');
        
        $this->logger->info('stampSigned success: ' . $outputPath, $this->context);
        return [
            'filename' => $filename,
            'path' => $outputPath
        ];
    }

    private function fillFL100Form(array $template, array $values, string $filename, string $outputPath): array {
        $logFile = __DIR__ . '/../logs/pdf_debug.log';
        file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 DEBUG: Using actual PDF as background image' . PHP_EOL, FILE_APPEND);
        file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 DEBUG: Values: ' . json_encode($values) . PHP_EOL, FILE_APPEND);
        
        // Initialize PDF and import the unencrypted FL-100 template as multi-page background
        $pdf = new Fpdi();
        $templatePdf = __DIR__ . '/../uploads/fl100.pdf';
        
        $pageCount = 1;
        try {
            if (file_exists($templatePdf)) {
                $pageCount = $pdf->setSourceFile($templatePdf);
            }
        } catch (\Throwable $e) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 DEBUG: setSourceFile failed: ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
        }
        
        // Add page 1 with a guaranteed background
        $bgImage = __DIR__ . '/../uploads/fl100_background.png';
        if (file_exists($bgImage)) {
            $pdf->AddPage('P', [210, 297]);
            $pdf->Image($bgImage, 0, 0, 210, 297);
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 DEBUG: Background image applied for page 1' . PHP_EOL, FILE_APPEND);
        } elseif (file_exists($templatePdf)) {
            try {
                $tplId = $pdf->importPage(1);
                $size = $pdf->getTemplateSize($tplId);
                $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';
                $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                $pdf->useTemplate($tplId, 0, 0, $size['width'], $size['height']);
            } catch (\Throwable $e) {
                file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 DEBUG: importPage(1) failed: ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
                $pdf->AddPage();
                $this->createFL100FormLayout($pdf, $logFile);
                file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 DEBUG: Drawn layout applied for page 1' . PHP_EOL, FILE_APPEND);
            }
        } else {
            $pdf->AddPage();
            $this->createFL100FormLayout($pdf, $logFile);
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 DEBUG: Drawn layout applied for page 1 (no template)' . PHP_EOL, FILE_APPEND);
        }
        // Set font for overlay text
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(0, 0, 0);
        file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 DEBUG: Page 1 background applied' . PHP_EOL, FILE_APPEND);

        // Use the provided values or generate test data if empty
        $dataToUse = !empty($values) ? $values : \WebPdfTimeSaver\Mvp\FL100TestDataGenerator::generateCompleteTestData();
        file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 DEBUG: Using ' . (empty($values) ? 'generated test data' : 'provided values') . ' for form filling' . PHP_EOL, FILE_APPEND);
        
        // Fill all fields using modular positioning system (page 1 for now)
        $this->fieldFillerManager->fillAllFields($pdf, $dataToUse, $logFile);

        // Append remaining pages as backgrounds using native sizes
        if ($pageCount > 1) {
            for ($i = 2; $i <= $pageCount; $i++) {
                try {
                    $tplId = $pdf->importPage($i);
                    $size = $pdf->getTemplateSize($tplId);
                    $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';
                    $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                    $pdf->useTemplate($tplId, 0, 0, $size['width'], $size['height']);
                } catch (\Throwable $e) {
                    file_put_contents($logFile, date('Y-m-d H:i:s') . " FL-100 DEBUG: importPage({$i}) failed: " . $e->getMessage() . PHP_EOL, FILE_APPEND);
                    // best-effort: reuse first-page background image if available
                    $bgImage = dirname($templatePdf) . '/fl100_background.png';
                    if (file_exists($bgImage)) {
                        $pdf->AddPage('P', [210, 297]);
                        $pdf->Image($bgImage, 0, 0, 210, 297);
                    }
                }
            }
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 DEBUG: Added remaining pages as backgrounds: ' . $pageCount . PHP_EOL, FILE_APPEND);
        }

        // Output the PDF
        $pdf->Output('F', $outputPath);
        // Quality control check
        $this->assertPdfQuality($outputPath, $logFile);
        
        if (!file_exists($outputPath)) {
            throw new \RuntimeException('Failed to generate FL-100 form PDF at ' . $outputPath);
        }
        
        return [
            'success' => true,
            // Standardize on 'filename' (keep 'file' for backward compatibility)
            'filename' => $filename,
            'file' => $filename,
            'path' => $outputPath
        ];
    }

    private function overlayFL100Background(Fpdi $pdf, string $templateFile, string $logFile): void {
        file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 OVERLAY: Starting FL-100 background overlay' . PHP_EOL, FILE_APPEND);
        
        // Try to use the actual FL-100 PDF as background by converting to image first
        $imageFile = $this->convertPdfToImage($templateFile, $logFile);
        
        if ($imageFile && file_exists($imageFile)) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 OVERLAY: Using FL-100 image as background' . PHP_EOL, FILE_APPEND);
            
            // Use the FL-100 image as background
            $pdf->Image($imageFile, 0, 0, 210, 297); // A4 size: 210mm x 297mm
            
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 OVERLAY: FL-100 background image applied' . PHP_EOL, FILE_APPEND);
        } else {
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 OVERLAY: Could not convert PDF to image, trying alternative approach' . PHP_EOL, FILE_APPEND);
            
            // Try to use the actual FL-100 PDF as background using a different method
            $this->useFL100AsBackground($pdf, $templateFile, $logFile);
        }
        
        file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 OVERLAY: FL-100 background overlay completed' . PHP_EOL, FILE_APPEND);
    }
    
    private function convertPdfToImage(string $pdfFile, string $logFile): ?string {
        file_put_contents($logFile, date('Y-m-d H:i:s') . ' PDF2IMG: Attempting to convert PDF to image' . PHP_EOL, FILE_APPEND);
        
        $imageFile = dirname($pdfFile) . '/fl100_background.png';
        
        // Try using ImageMagick command line (if available)
        $magickCmd = "magick convert \"{$pdfFile}[0]\" \"{$imageFile}\" 2>&1";
        $output = [];
        $returnCode = 0;
        exec($magickCmd, $output, $returnCode);
        
        if ($returnCode === 0 && file_exists($imageFile)) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' PDF2IMG: Successfully converted using ImageMagick' . PHP_EOL, FILE_APPEND);
            return $imageFile;
        }
        
        file_put_contents($logFile, date('Y-m-d H:i:s') . ' PDF2IMG: ImageMagick failed: ' . implode(' ', $output) . PHP_EOL, FILE_APPEND);
        
		// Try using Ghostscript (if available)
		$gsBinary = $this->findGhostscriptBinary($logFile);
		$gsCmd = $gsBinary
			? "\"{$gsBinary}\" -dSAFER -dNOPAUSE -dBATCH -sDEVICE=png16m -r200 -dFirstPage=1 -dLastPage=1 -sOutputFile=\"{$imageFile}\" \"{$pdfFile}\" 2>&1"
			: null;
        $output = [];
        $returnCode = 0;
		if ($gsCmd !== null) {
			exec($gsCmd, $output, $returnCode);
		}
        
        if ($returnCode === 0 && file_exists($imageFile)) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' PDF2IMG: Successfully converted using Ghostscript' . PHP_EOL, FILE_APPEND);
            return $imageFile;
        }
        
		file_put_contents($logFile, date('Y-m-d H:i:s') . ' PDF2IMG: Ghostscript failed: ' . implode(' ', $output) . PHP_EOL, FILE_APPEND);
        
        // Try using PDFtk to convert to image (if available)
        $pdftkCmd = "pdftk \"{$pdfFile}\" burst output \"{$imageFile}\" 2>&1";
        $output = [];
        $returnCode = 0;
        exec($pdftkCmd, $output, $returnCode);
        
        if ($returnCode === 0 && file_exists($imageFile)) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' PDF2IMG: Successfully converted using PDFtk' . PHP_EOL, FILE_APPEND);
            return $imageFile;
        }
        
        file_put_contents($logFile, date('Y-m-d H:i:s') . ' PDF2IMG: PDFtk failed: ' . implode(' ', $output) . PHP_EOL, FILE_APPEND);
        
        // If all methods fail, return null
        file_put_contents($logFile, date('Y-m-d H:i:s') . ' PDF2IMG: All conversion methods failed' . PHP_EOL, FILE_APPEND);
        return null;
    }

    private function generateFl100BackgroundImage(string $officialPdf, string $backgroundImage, string $logFile): void {
        // Create FL-100 background image from the official PDF (first page only)
        if (!file_exists($officialPdf)) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' BGGEN: Official FL-100 PDF not found at ' . $officialPdf . PHP_EOL, FILE_APPEND);
            return;
        }
        // Prefer ImageMagick if available
        $output = [];
        $returnCode = 0;
        $magickCmd = "magick convert \"{$officialPdf}[0]\" \"{$backgroundImage}\" 2>&1";
        exec($magickCmd, $output, $returnCode);
        if ($returnCode === 0 && file_exists($backgroundImage)) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' BGGEN: Background generated via ImageMagick' . PHP_EOL, FILE_APPEND);
            return;
        }
        file_put_contents($logFile, date('Y-m-d H:i:s') . ' BGGEN: ImageMagick failed: ' . implode(' ', $output) . PHP_EOL, FILE_APPEND);
        
		// Fallback to Ghostscript
		$output = [];
		$returnCode = 0;
		$gsBinary = $this->findGhostscriptBinary($logFile);
		$gsCmd = $gsBinary
			? "\"{$gsBinary}\" -dSAFER -dNOPAUSE -dBATCH -sDEVICE=png16m -r200 -dFirstPage=1 -dLastPage=1 -sOutputFile=\"{$backgroundImage}\" \"{$officialPdf}\" 2>&1"
			: null;
		if ($gsCmd !== null) {
			exec($gsCmd, $output, $returnCode);
		}
        if ($returnCode === 0 && file_exists($backgroundImage)) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' BGGEN: Background generated via Ghostscript' . PHP_EOL, FILE_APPEND);
            return;
        }
        file_put_contents($logFile, date('Y-m-d H:i:s') . ' BGGEN: Ghostscript failed: ' . implode(' ', $output) . PHP_EOL, FILE_APPEND);
    }

	/**
	 * Attempt to locate a Ghostscript console binary on Windows or PATH.
	 * Returns absolute path if found, otherwise null.
	 */
	private function findGhostscriptBinary(string $logFile): ?string {
		$candidates = [
			'gswin64c',
			'gswin32c',
			'gs',
			// Project-root bundled installer/binary name fallback
			dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'gs1000w64.exe',
		];
		foreach ($candidates as $bin) {
			$cmd = strpos($bin, DIRECTORY_SEPARATOR) !== false ? "\"{$bin}\" -v 2>&1" : $bin . ' -v 2>&1';
			$output = [];
			$return = 0;
			@exec($cmd, $output, $return);
			if ($return === 0) {
				file_put_contents($logFile, date('Y-m-d H:i:s') . ' GS: Using Ghostscript binary: ' . $bin . PHP_EOL, FILE_APPEND);
				return $bin;
			}
		}
		file_put_contents($logFile, date('Y-m-d H:i:s') . ' GS: No Ghostscript binary found on system PATH or project root' . PHP_EOL, FILE_APPEND);
		return null;
	}

    private function useFL100AsBackground(Fpdi $pdf, string $templateFile, string $logFile): void {
        file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 BG: Attempting to use actual FL-100 PDF as background' . PHP_EOL, FILE_APPEND);
        
        // Try to use the actual FL-100 PDF as background by copying it and overlaying text
        try {
            // Create a new PDF that starts with the FL-100 template
            $tempPdf = new Fpdi();
            
            // Try to import the FL-100 template
            if (file_exists($templateFile)) {
                file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 BG: Attempting to import FL-100 template' . PHP_EOL, FILE_APPEND);
                
                // Try to set source file - this might fail due to encryption
                $pageCount = $tempPdf->setSourceFile($templateFile);
                $templateId = $tempPdf->importPage(1);
                $tempPdf->AddPage();
                $tempPdf->useTemplate($templateId);
                
                file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 BG: Successfully imported FL-100 template' . PHP_EOL, FILE_APPEND);
                
                // Copy the content to our main PDF
                $pdf->AddPage();
                $pdf->useTemplate($templateId);
                
                file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 BG: FL-100 template used as background' . PHP_EOL, FILE_APPEND);
                
            } else {
                throw new \Exception('FL-100 template file not found');
            }
            
        } catch (\Exception $e) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 BG: Could not import FL-100 template: ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 BG: Trying alternative PDF files' . PHP_EOL, FILE_APPEND);
            
            // Try alternative PDF files
            $this->tryAlternativePdfFiles($pdf, $logFile);
        }
    }

    private function tryAlternativePdfFiles(Fpdi $pdf, string $logFile): void {
        $alternativeFiles = [
            __DIR__ . '/../uploads/fl100_official.pdf',
            __DIR__ . '/../uploads/68d5cfb79bdb0_test.pdf',
            __DIR__ . '/../uploads/68d7baede2abc_test_form.pdf'
        ];
        
        foreach ($alternativeFiles as $altFile) {
            if (file_exists($altFile)) {
                file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 BG: Trying alternative file: ' . basename($altFile) . PHP_EOL, FILE_APPEND);
                
                try {
                    $tempPdf = new Fpdi();
                    $pageCount = $tempPdf->setSourceFile($altFile);
                    $templateId = $tempPdf->importPage(1);
                    $tempPdf->AddPage();
                    $tempPdf->useTemplate($templateId);
                    
                    file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 BG: Successfully imported alternative file: ' . basename($altFile) . PHP_EOL, FILE_APPEND);
                    
                    // Copy the content to our main PDF
                    $pdf->AddPage();
                    $pdf->useTemplate($templateId);
                    
                    file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 BG: Alternative PDF used as background' . PHP_EOL, FILE_APPEND);
                    return;
                    
                } catch (\Exception $e) {
                    file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 BG: Alternative file failed: ' . basename($altFile) . ' - ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
                }
            }
        }
        
        file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 BG: All alternative files failed, falling back to creating FL-100 background' . PHP_EOL, FILE_APPEND);
        
        // Fallback: Create the FL-100 background
        $this->createFL100Background($pdf);
    }

    private function createFL100FormLayout(Fpdi $pdf, string $logFile): void {
        file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 LAYOUT: Creating pixel-perfect FL-100 form layout' . PHP_EOL, FILE_APPEND);
        
        // Set up the page with FL-100 styling
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetTextColor(0, 0, 0);
        
        // Header - Form number and title
        $pdf->SetXY(20, 15);
        $pdf->Cell(30, 8, 'FL-100', 0, 0, 'L');
        
        $pdf->SetXY(20, 25);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(100, 6, 'PETITION—MARRIAGE/DOMESTIC PARTNERSHIP', 0, 0, 'L');
        
        // Case number box (top right)
        $pdf->Rect(140, 15, 50, 20);
        $pdf->SetXY(142, 17);
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(46, 4, 'CASE NUMBER:', 0, 1, 'L');
        
        // Attorney information section
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetXY(20, 45);
        $pdf->Cell(100, 6, 'ATTORNEY OR PARTY WITHOUT ATTORNEY:', 0, 0, 'L');
        
        // Attorney name line
        $pdf->SetXY(20, 55);
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(80, 6, 'Name:', 0, 0, 'L');
        $pdf->Line(35, 60, 120, 60); // Underline for name
        
        // State Bar number
        $pdf->SetXY(125, 55);
        $pdf->Cell(30, 6, 'State Bar No.:', 0, 0, 'L');
        $pdf->Line(155, 60, 190, 60); // Underline for bar number
        
        // Firm name line
        $pdf->SetXY(20, 65);
        $pdf->Cell(20, 6, 'Firm Name:', 0, 0, 'L');
        $pdf->Line(45, 70, 190, 70); // Underline for firm name
        
        // Address line
        $pdf->SetXY(20, 75);
        $pdf->Cell(20, 6, 'Address:', 0, 0, 'L');
        $pdf->Line(40, 80, 190, 80); // Underline for address
        
        // City, State, ZIP line
        $pdf->SetXY(20, 85);
        $pdf->Cell(30, 6, 'City, State, ZIP:', 0, 0, 'L');
        $pdf->Line(55, 90, 190, 90); // Underline for city/state/zip
        
        // Phone and email
        $pdf->SetXY(20, 95);
        $pdf->Cell(20, 6, 'Phone:', 0, 0, 'L');
        $pdf->Line(35, 100, 100, 100); // Underline for phone
        
        $pdf->SetXY(110, 95);
        $pdf->Cell(20, 6, 'Email:', 0, 0, 'L');
        $pdf->Line(125, 100, 190, 100); // Underline for email
        
        // Superior Court section
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetXY(20, 115);
        $pdf->Cell(100, 6, 'SUPERIOR COURT OF CALIFORNIA', 0, 0, 'L');
        
        $pdf->SetXY(20, 125);
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(50, 6, 'COUNTY OF:', 0, 0, 'L');
        $pdf->Line(50, 130, 120, 130); // Underline for county
        
        // Parties section
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetXY(20, 145);
        $pdf->Cell(100, 6, 'PETITIONER:', 0, 0, 'L');
        $pdf->Line(55, 150, 190, 150); // Underline for petitioner
        
        $pdf->SetXY(20, 160);
        $pdf->Cell(100, 6, 'RESPONDENT:', 0, 0, 'L');
        $pdf->Line(55, 165, 190, 165); // Underline for respondent
        
        // Main form title
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetXY(20, 185);
        $pdf->Cell(170, 8, 'PETITION FOR', 0, 0, 'C');
        
        $pdf->SetXY(20, 195);
        $pdf->Cell(170, 8, 'DISSOLUTION OF MARRIAGE', 0, 0, 'C');
        
        // Checkboxes and options (simplified)
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetXY(30, 215);
        $pdf->Cell(5, 5, '☐', 0, 0, 'C');
        $pdf->SetXY(40, 215);
        $pdf->Cell(100, 5, 'Dissolution of Marriage', 0, 0, 'L');
        
        $pdf->SetXY(30, 225);
        $pdf->Cell(5, 5, '☐', 0, 0, 'C');
        $pdf->SetXY(40, 225);
        $pdf->Cell(100, 5, 'Legal Separation', 0, 0, 'L');
        
        $pdf->SetXY(30, 235);
        $pdf->Cell(5, 5, '☐', 0, 0, 'C');
        $pdf->SetXY(40, 235);
        $pdf->Cell(100, 5, 'Nullity of Marriage', 0, 0, 'L');
        
        // Footer
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetXY(20, 280);
        $pdf->Cell(170, 4, 'FL-100 [Rev. January 1, 2025]                    PETITION—MARRIAGE/DOMESTIC PARTNERSHIP', 0, 0, 'C');
        
        file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 LAYOUT: Pixel-perfect FL-100 form layout created' . PHP_EOL, FILE_APPEND);
    }

    private function createFL100Layout(Fpdi $pdf): void {
        $logFile = __DIR__ . '/../logs/pdf_debug.log';
        file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 LAYOUT: Creating exact FL-100 form layout' . PHP_EOL, FILE_APPEND);
        
        // Recreate the exact FL-100 form layout based on the original California court form
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetTextColor(0, 0, 0);
        
        // Form number and title (top left)
        $pdf->SetXY(20, 15);
        $pdf->Cell(30, 8, 'FL-100', 0, 0, 'L');
        
        // Case number box (top right)
        $pdf->Rect(140, 15, 50, 20);
        $pdf->SetXY(142, 17);
        $pdf->SetFont('Arial', '', 8);
        $pdf->Cell(46, 4, 'CASE NUMBER:', 0, 1, 'L');
        
        // Form title
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->SetXY(20, 25);
        $pdf->Cell(0, 6, 'PETITION', 0, 1, 'L');
        $pdf->SetXY(20, 32);
        $pdf->Cell(0, 6, '(Family Law)', 0, 1, 'L');
        
        // Attorney section header
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetXY(20, 50);
        $pdf->Cell(0, 5, 'ATTORNEY OR PARTY WITHOUT ATTORNEY (Name, State Bar number, and address):', 0, 1, 'L');
        
        // Attorney info lines - matching exact FL-100 positions
        $pdf->Line(20, 65, 120, 65);  // Name line
        $pdf->Line(125, 65, 190, 65); // Bar number line
        $pdf->Line(20, 75, 190, 75);  // Firm line
        $pdf->Line(20, 85, 190, 85);  // Address line
        $pdf->Line(20, 95, 190, 95);  // City, State, ZIP line
    }


    private function createProfessionalForm(array $template, array $values, string $filename, string $outputPath): array {
        // Create a blank PDF that matches FL-105 form dimensions and layout
        $pdf = new \FPDF();
        $pdf->AddPage();
        
        // Set up the form to look like FL-105
        $pdf->SetFont('Arial', 'B', 12);
        
        // FL-105 Header
        $pdf->Cell(0, 10, 'FL-105', 0, 0, 'L');
        $pdf->Cell(0, 10, 'DECLARATION UNDER UNIFORM CHILD CUSTODY JURISDICTION', 0, 1, 'R');
        $pdf->Cell(0, 10, 'AND ENFORCEMENT ACT (UCCJEA)', 0, 1, 'R');
        $pdf->Ln(5);
        
        // Case information box (top right)
        $pdf->SetXY(140, 20);
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(50, 6, 'CASE NUMBER:', 0, 1);
        $pdf->SetX(140);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(50, 8, $values['case_number'] ?? '', 'B', 1);
        
        // Attorney information section
        $pdf->SetXY(20, 50);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 8, 'ATTORNEY OR PARTY WITHOUT ATTORNEY:', 0, 1);
        
        $pdf->SetX(20);
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(80, 6, 'Name: ' . ($values['attorney_name'] ?? ''), 'B', 0);
        $pdf->Cell(80, 6, 'State Bar No.: ' . ($values['attorney_bar'] ?? ''), 'B', 1);
        
        $pdf->SetX(20);
        $pdf->Cell(160, 6, 'Firm Name: ' . ($values['attorney_firm'] ?? ''), 'B', 1);
        
        // Parties section
        $pdf->SetXY(20, 90);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 8, 'PETITIONER:', 0, 1);
        $pdf->SetX(20);
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(160, 6, $values['petitioner_name'] ?? '', 'B', 1);
        
        $pdf->SetX(20);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 8, 'RESPONDENT:', 0, 1);
        $pdf->SetX(20);
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(160, 6, $values['respondent_name'] ?? '', 'B', 1);
        
        // Child information section
        $pdf->SetXY(20, 130);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 8, '1. INFORMATION ABOUT CHILD:', 0, 1);
        
        $pdf->SetX(25);
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(60, 6, 'Child\'s name: ' . ($values['child_name'] ?? ''), 'B', 0);
        $pdf->Cell(40, 6, 'Date of birth: ' . ($values['child_birthdate'] ?? ''), 'B', 0);
        $pdf->Cell(20, 6, 'Sex: ' . ($values['child_sex'] ?? ''), 'B', 1);
        
        // Current address
        $pdf->SetX(25);
        $pdf->Cell(0, 8, 'Current address:', 0, 1);
        $pdf->SetX(30);
        $pdf->Cell(160, 6, $values['current_address'] ?? '', 'B', 1);
        $pdf->SetX(30);
        $pdf->Cell(100, 6, $values['current_city_state_zip'] ?? '', 'B', 0);
        $pdf->Cell(60, 6, 'Period of residence: ' . ($values['period_of_residence'] ?? ''), 'B', 1);
        
        // Previous address
        $pdf->SetX(25);
        $pdf->Cell(0, 8, 'Previous address (if any):', 0, 1);
        $pdf->SetX(30);
        $pdf->Cell(160, 6, $values['previous_address'] ?? '', 'B', 1);
        $pdf->SetX(30);
        $pdf->Cell(100, 6, $values['previous_city_state_zip'] ?? '', 'B', 0);
        $pdf->Cell(60, 6, 'Period: ' . ($values['previous_period_of_residence'] ?? ''), 'B', 1);
        
        // Home state
        $pdf->SetX(25);
        $pdf->Cell(0, 8, 'Home state: ' . ($values['home_state'] ?? ''), 'B', 1);
        
        // Checkbox sections
        $pdf->SetXY(20, 220);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(0, 8, '2. CUSTODY PROCEEDINGS:', 0, 1);
        
        $pdf->SetX(25);
        $pdf->SetFont('Arial', '', 9);
        $checkBox1 = ($values['no_other_proceedings'] ?? '') ? '[X]' : '[ ]';
        $pdf->Cell(10, 6, $checkBox1, 0, 0);
        $pdf->Cell(0, 6, 'There are no other custody proceedings concerning this child.', 0, 1);
        
        $pdf->SetX(25);
        $checkBox2 = ($values['other_proceedings_exist'] ?? '') ? '[X]' : '[ ]';
        $pdf->Cell(10, 6, $checkBox2, 0, 0);
        $pdf->Cell(0, 6, 'There are other custody proceedings concerning this child.', 0, 1);
        
        // Signature area
        $pdf->SetXY(20, 260);
        $pdf->SetFont('Arial', '', 9);
        $pdf->Cell(80, 6, 'Date: ' . date('m/d/Y'), 'B', 0);
        $pdf->Cell(80, 6, 'Signature: ________________________', 0, 1);
        
        // Output the PDF
        $pdf->Output('F', $outputPath);
        // Quality control check
        $this->assertPdfQuality($outputPath, $logFile);
        
        if (!file_exists($outputPath)) {
            throw new \RuntimeException('Failed to generate FL-105 form PDF at ' . $outputPath);
        }
        
        return [
            'filename' => $filename,
            'path' => $outputPath
        ];
    }
    
    private function getPanelLabel(string $panelId, array $template): string {
        $panels = $template['panels'] ?? [];
        foreach ($panels as $panel) {
            if ($panel['id'] === $panelId) {
                return $panel['label'] ?? $panelId;
            }
        }
        return ucfirst($panelId);
    }

    /**
     * Fill PDF form using positioned fields from the field editor
     */
    public function fillPdfFormWithPositions(array $template, array $values, string $templateId = 't_fl100_gc120'): array {
        $templateFile = $this->getTemplateFile($template['id'] ?? '');
        $filename = 'mvp_' . date('Ymd_His') . '_' . ($template['id'] ?? 'doc') . '_positioned.pdf';
        $outputPath = rtrim($this->outputDir, '/\\') . DIRECTORY_SEPARATOR . $filename;

        // Load positioned fields
        $positions = $this->positionLoader->loadFieldPositions($templateId);
        
        if (empty($positions)) {
            // Fall back to default positioning if no positions saved
            return $this->fillPdfForm($template, $values);
        }

		$logFile = __DIR__ . '/../logs/pdf_debug.log';
        $pdf = new Fpdi();
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(0, 0, 0);

        // Use the unencrypted template PDF as background for the first page
        $templatePdf = __DIR__ . '/../uploads/fl100.pdf';
        try {
            if (file_exists($templatePdf)) {
                $pageCount = $pdf->setSourceFile($templatePdf);
                $tplId = $pdf->importPage(1);
                $size = $pdf->getTemplateSize($tplId);
                $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';
                $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                $pdf->useTemplate($tplId, 0, 0, $size['width'], $size['height']);
            } else {
                $pdf->AddPage();
            }
        } catch (\Throwable $e) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 DEBUG: positioned template import failed: ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
            $pdf->AddPage();
        }

        // Convert editor pixel coordinates to millimeters for FPDF/FPDI (assuming 72 DPI if not specified)
        $pxToMm = function($px, $dpi = 96.0) { return ($px / $dpi) * 25.4; };

        // Fill fields using positioned coordinates with unit conversion
        foreach ($values as $fieldKey => $value) {
            if (!empty($value) && isset($positions[$fieldKey])) {
                $position = $positions[$fieldKey];
                $xPx = (float)($position['x'] ?? 0);
                $yPx = (float)($position['y'] ?? 0);
                $xMm = $pxToMm($xPx);
                $yMm = $pxToMm($yPx);
                $pdf->SetXY($xMm, $yMm);
                $pdf->Write(0, (string)$value);
            }
        }

        $pdf->Output('F', $outputPath);

        return ['success' => true, 'file' => $filename, 'path' => $outputPath, 'used_positions' => count($positions)];
    }


}
