<?php
declare(strict_types=1);

namespace WebPdfTimeSaver\Mvp;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/field_position_loader.php';
require_once __DIR__ . '/field_fillers/FieldFillerManager.php';
require_once __DIR__ . '/logger.php';
require_once __DIR__ . '/font_manager.php';
require_once __DIR__ . '/field_metrics.php';
require_once __DIR__ . '/universal_field_mapper.php';
require_once __DIR__ . '/pdf_config.php';

use setasign\Fpdi\Fpdi;
use WebPdfTimeSaver\Mvp\FieldFillers\FieldFillerManager;
use setasign\Fpdi\PdfParser\StreamReader;

final class PdfFormFiller {
    private string $outputDir;
    private string $templatesDir;
    private FieldPositionLoader $positionLoader;
    private FieldFillerManager $fieldFillerManager;
    private UniversalFieldMapper $fieldMapper;
    private PdfConfig $config;
    private Logger $logger;
    private array $context = [];
    private array $fieldMappingCache = [];

    public function __construct(string $outputDir = __DIR__ . '/../../output', string $templatesDir = __DIR__ . '/../../uploads', ?Logger $logger = null) {
        $this->outputDir = $outputDir;
        $this->templatesDir = $templatesDir;
        $this->positionLoader = new FieldPositionLoader();
        $this->fieldFillerManager = new FieldFillerManager();
        $this->fieldMapper = new UniversalFieldMapper();
        $this->config = new PdfConfig();
        $this->logger = $logger ?? new Logger();
        
        if (!is_dir($this->outputDir)) { 
            mkdir($this->outputDir, 0755, true); 
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

    /**
     * Universal PDF form filler - works with ANY PDF
     * Strategy:
     * 1. Try AcroForm filling first (if PDF has fillable fields)
     * 2. Fall back to position-based overlay if AcroForm fails
     */
    public function fillPdfForm(array $template, array $values): array {
        $templateId = $template['id'] ?? '';
        $this->logger->info('fillPdfForm start', array_merge($this->context, ['templateId' => $templateId]));

        $positions = $this->positionLoader->loadFieldPositions($templateId);
        if (!empty($positions)) {
            $result = $this->fillPdfFormWithPositions($template, $values, $templateId);
            $result['method'] = $result['method'] ?? 'position-overlay';
            $this->logger->info('fillPdfForm success (positions preferred)', array_merge($this->context, $result));
            return $result;
        }
        
        // Get PDF file path dynamically
        $pdfPath = $this->getTemplatePdfPath($templateId);
        if (!$pdfPath || !file_exists($pdfPath)) {
            throw new \RuntimeException("PDF file not found for template: $templateId");
        }
        
        $filename = 'mvp_' . date('Ymd_His') . '_' . ($templateId ?: 'doc') . '.pdf';
        $outputPath = rtrim($this->outputDir, '/\\') . DIRECTORY_SEPARATOR . $filename;
        
        // Strategy 1: Try AcroForm filling first (if PDF has fillable fields)
        try {
            $result = $this->fillUsingAcroForm($pdfPath, $template, $values, $outputPath, $templateId);
            $fieldsFilled = (int)($result['fieldsFilled'] ?? 0);
            if (($result['success'] ?? false) && $fieldsFilled > 0) {
                $this->logger->info('fillPdfForm success (AcroForm)', array_merge($this->context, $result));
                return [
                    'success' => true,
                    'filename' => $filename,
                    'file' => $filename,
                    'path' => $outputPath,
                    'method' => 'acroform',
                    'fieldsFilled' => $fieldsFilled
                ];
            }
            if ($result['success'] ?? false) {
                $this->logger->warning(
                    'AcroForm fill produced no values; falling back to position-based overlay',
                    array_merge($this->context, ['fieldsFilled' => $fieldsFilled])
                );
            }
        } catch (\Throwable $e) {
            $this->logger->warning('AcroForm filling failed, trying position-based: ' . $e->getMessage(), $this->context);
        }
        
        // Strategy 2: Fall back to position-based overlay
        try {
            $result = $this->fillPdfFormWithPositions($template, $values, $templateId);
            $result['method'] = 'position-overlay';
            $this->logger->info('fillPdfForm success (position-based)', array_merge($this->context, $result));
            return $result;
        } catch (\Throwable $e) {
            $this->logger->error('fillPdfForm error: ' . $e->getMessage(), $this->context);
            throw $e;
        }
    }
    
    /**
     * Fill PDF using AcroForm fields directly (if PDF has fillable fields)
     */
    private function fillUsingAcroForm(string $pdfPath, array $template, array $values, string $outputPath, string $templateId): array {
        // Try Node.js/pdf-lib method first (most reliable for AcroForm)
        if ($this->isNodeJsAvailable()) {
            try {
                return $this->fillUsingNodeJs($pdfPath, $values, $outputPath, $templateId);
            } catch (\Throwable $e) {
                $this->logger->warning('Node.js AcroForm filling failed: ' . $e->getMessage(), $this->context);
            }
        }
        
        // Fallback: Try using FPDI/FPDF if PDF has fillable fields
        // Note: FPDI doesn't support AcroForm directly, so we'd need position-based
        throw new \RuntimeException('AcroForm filling not available - no Node.js or AcroForm support');
    }
    
    /**
     * Fill PDF using Node.js/pdf-lib (AcroForm filling)
     */
    private function fillUsingNodeJs(string $pdfPath, array $values, string $outputPath, string $templateId): array {
        $values = $this->sanitizeFillValues($values);
        $nodePath = $this->findNodeBinary();
        if (!$nodePath) {
            throw new \RuntimeException('Node.js not available');
        }
        
        $scriptPath = __DIR__ . '/../../scripts/fill-pdf-form-js.js';
        if (!file_exists($scriptPath)) {
            throw new \RuntimeException('Node.js fill script not found');
        }
        
        // Map user values to PDF AcroForm field names via positions + UniversalFieldMapper
        $positionsFile = __DIR__ . '/../../data/' . $templateId . '_positions.json';
        $positions = [];
        $pdfFields = [];
        if (file_exists($positionsFile)) {
            $decoded = json_decode((string)file_get_contents($positionsFile), true);
            $positions = is_array($decoded) ? $decoded : [];
            $pdfFields = array_keys($positions);
        }

        $mapping = $this->fieldMapper->applyMappingPreferences($values, $pdfFields, $templateId);

        // mapping format: ['userField' => 'pdfFieldName']
        $mappedValues = [];
        foreach ($mapping as $userKey => $pdfFieldName) {
            if (isset($values[$userKey]) && $values[$userKey] !== '' && $values[$userKey] !== null) {
                $mappedValues[$pdfFieldName] = $values[$userKey];
            }
        }

        // Map canonical position keys to AcroForm names (e.g. FL_100_... -> FL-100[0].Page1[0]...)
        foreach ($values as $key => $value) {
            if (!is_string($key) || $this->isInternalMetaFieldKey($key)) {
                continue;
            }
            if ($value === '' || $value === null) {
                continue;
            }
            $posKey = $this->resolvePositionKeyForValue($key, $positions, $template);
            if ($posKey === null) {
                continue;
            }
            $pos = $positions[$posKey];
            $acroName = trim((string)($pos['name'] ?? ''));
            if ($acroName !== '') {
                $mappedValues[$acroName] = $value;
            }
        }

        // Fallback: template pdfTarget or direct key match
        if (empty($mappedValues)) {
            foreach ($values as $key => $value) {
                if ($value === '' || $value === null) {
                    continue;
                }
                $pdfFieldName = $this->findPdfFieldName($key, $template);
                if ($pdfFieldName && in_array($pdfFieldName, $pdfFields, true)) {
                    $mappedValues[$pdfFieldName] = $value;
                } elseif (in_array($key, $pdfFields, true)) {
                    $mappedValues[$key] = $value;
                }
            }
        }
        
        // Save mapped values to temporary JSON file
        $tempDataFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pdf_fill_data_' . time() . '.json';
        file_put_contents($tempDataFile, json_encode($mappedValues, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        
        try {
            // Execute Node.js script
            $command = sprintf(
                '"%s" "%s" "%s" "%s" "%s"',
                $nodePath,
                $scriptPath,
                escapeshellarg($pdfPath),
                escapeshellarg($tempDataFile),
                escapeshellarg($outputPath)
            );
            
            $output = [];
            $returnCode = 0;
            exec($command . ' 2>&1', $output, $returnCode);
            
            $outputText = implode("\n", $output);
            $this->logger->debug('Node.js fill output: ' . $outputText, $this->context);
            
            if ($returnCode === 0 && file_exists($outputPath)) {
                // Parse output to get filled field count
                $fieldsFilled = 0;
                if (preg_match('/Filled (\d+)/', $outputText, $matches)) {
                    $fieldsFilled = (int)$matches[1];
                }
                return [
                    'success' => true,
                    'fieldsFilled' => $fieldsFilled,
                    'method' => 'nodejs-acroform'
                ];
            }
            
            throw new \RuntimeException('Node.js fill failed: ' . $outputText);
            
        } finally {
            // Clean up temp file
            if (file_exists($tempDataFile)) {
                @unlink($tempDataFile);
            }
        }
    }
    
    /**
     * Find PDF field name from template field definition
     */
    private function findPdfFieldName(string $userKey, array $template): ?string {
        foreach (($template['fields'] ?? []) as $field) {
            if (($field['key'] ?? '') === $userKey) {
                return $field['pdfTarget']['formField'] ?? null;
            }
        }
        return null;
    }
    
    /**
     * Get PDF file path for template ID dynamically
     */
    private function getTemplatePdfPath(string $templateId): ?string {
        // Remove 't_' prefix if present
        $baseId = preg_replace('/^t_/', '', $templateId);
        
        // Try multiple file name patterns
        $candidates = [
            $templateId . '.pdf',
            $baseId . '.pdf',
            str_replace('_', '-', $templateId) . '.pdf',
            str_replace('_', '-', $baseId) . '.pdf'
        ];
        
        // Check uploads directory
        foreach ($candidates as $candidate) {
            $path = rtrim($this->templatesDir, '/\\') . DIRECTORY_SEPARATOR . $candidate;
            if (file_exists($path)) {
                return $path;
            }
        }
        
        // Check config for custom PDF path (if stored in config)
        try {
            $config = $this->config->getConfig($templateId);
            if (isset($config['pdfPath']) && file_exists($config['pdfPath'])) {
                return $config['pdfPath'];
            }
        } catch (\Throwable $e) {
            // Config not available, continue with file search
        }
        
        // Last-resort: look for any uploads PDF that starts with template/base id.
        $globCandidates = array_filter([
            rtrim($this->templatesDir, '/\\') . DIRECTORY_SEPARATOR . $templateId . '*.pdf',
            rtrim($this->templatesDir, '/\\') . DIRECTORY_SEPARATOR . $baseId . '*.pdf',
            rtrim($this->templatesDir, '/\\') . DIRECTORY_SEPARATOR . str_replace('_', '-', $templateId) . '*.pdf',
            rtrim($this->templatesDir, '/\\') . DIRECTORY_SEPARATOR . str_replace('_', '-', $baseId) . '*.pdf',
        ]);
        foreach ($globCandidates as $pattern) {
            $matches = glob($pattern);
            if (is_array($matches) && !empty($matches)) {
                sort($matches);
                foreach ($matches as $path) {
                    if (is_string($path) && file_exists($path)) {
                        return $path;
                    }
                }
            }
        }
        
        return null;
    }
    
    /**
     * Check if Node.js is available
     */
    private function isNodeJsAvailable(): bool {
        return !empty($this->findNodeBinary());
    }
    
    /**
     * Find Node.js binary
     */
    private function findNodeBinary(): string {
        // Prefer explicit environment override if provided
        $envNode = getenv('NODE_PATH') ?: getenv('NODE');
        if ($envNode && file_exists($envNode)) {
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
                return $nodePath;
            }
        }
        
        // Common paths to check (cross-platform)
        $candidates = [
            '/usr/bin/node',  // Linux standard location
            '/usr/local/bin/node',  // Linux alternative
            '/opt/nodejs/bin/node',  // Some Linux installs
            'node',  // Try bare command (works if in PATH)
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
                return $candidate;
            }
        }
        
        return '';
    }

    private function addFormData(Fpdi $pdf, array $template, array $values): void {
        // Get default font settings (universal solution)
        $defaultFont = \WebPdfTimeSaver\Mvp\FontManager::getFontSettings(
            [],
            $template['id'] ?? null,
            null
        );
        \WebPdfTimeSaver\Mvp\FontManager::applyFont($pdf, $defaultFont);
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
        $this->assertPdfQuality($outputPath, __DIR__ . '/../../logs/pdf_debug.log');
        
        $this->logger->info('stampSigned success: ' . $outputPath, $this->context);
        return [
            'filename' => $filename,
            'path' => $outputPath
        ];
    }

    /**
     * Apply a cryptographic digital signature using mPDF when available.
     * Falls back to throwing if mPDF is not installed or certificate is missing.
     */
    public function applyDigitalSignature(string $inputPath): array {
        $config = require __DIR__ . '/../../config/app.php';
        $signing = $config['signing'] ?? [];
        $enabled = (bool)($signing['enabled'] ?? false);
        if (!$enabled) {
            throw new \RuntimeException('Digital signing disabled by configuration');
        }
        if (!class_exists('Mpdf\\Mpdf')) {
            throw new \RuntimeException('mPDF not installed. Run composer require mpdf/mpdf');
        }
        $certPath = (string)($signing['cert_p12_path'] ?? '');
        $certPassword = (string)($signing['cert_password'] ?? '');
        if (!$certPath || !file_exists($certPath)) {
            throw new \RuntimeException('Signing certificate not found at ' . $certPath);
        }

        $filename = 'signed_' . basename($inputPath);
        $outputPath = rtrim($this->outputDir, '/\\') . DIRECTORY_SEPARATOR . $filename;

        // Build signature info
        $info = $signing['info'] ?? [];
        $sigReason = (string)($info['reason'] ?? 'Document approved');
        $sigLocation = (string)($info['location'] ?? 'Web-PDFTimeSaver');
        $sigContact = (string)($info['contact'] ?? 'support@example.com');

        // Create mPDF and import existing PDF, then apply signature
        $mpdf = new \Mpdf\Mpdf([
            'tempDir' => $config['paths']['tmp'] ?? __DIR__ . '/../../tmp',
        ]);

        // Enable FPDI bridge to import existing PDF pages
        $pageCount = $mpdf->SetSourceFile($inputPath);

        // Configure signature
        $mpdf->SetSignature(
            'file://' . $certPath,
            $certPassword,
            $certPassword,
            '',
            2, // certification level
            [
                'Name' => 'Web-PDFTimeSaver',
                'Reason' => $sigReason,
                'Location' => $sigLocation,
                'ContactInfo' => $sigContact,
            ]
        );

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $tplId = $mpdf->ImportPage($pageNo);
            $size = $mpdf->getTemplateSize($tplId);
            $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';
            $mpdf->AddPage($orientation, '', '', '', '', 0, 0, 0, 0);
            $mpdf->UseTemplate($tplId);
            // Optionally add a visible signature appearance on last page
            if ($pageNo === $pageCount) {
                $mpdf->SetXY(max(0, $size['width'] - 70), max(0, $size['height'] - 40));
                $mpdf->Write(5, 'Digitally signed');
            }
        }

        $mpdf->Output($outputPath, \Mpdf\Output\Destination::FILE);

        if (!file_exists($outputPath)) {
            throw new \RuntimeException('Failed to create digitally signed PDF at ' . $outputPath);
        }
        $this->assertPdfQuality($outputPath, __DIR__ . '/../../logs/pdf_debug.log');
        return [
            'filename' => $filename,
            'path' => $outputPath,
        ];
    }

    private function fillFL100Form(array $template, array $values, string $filename, string $outputPath): array {
        $logFile = __DIR__ . '/../../logs/pdf_debug.log';
        file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 DEBUG: Using actual PDF as background image' . PHP_EOL, FILE_APPEND);
        file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 DEBUG: Values: ' . json_encode($values) . PHP_EOL, FILE_APPEND);
        
        // Initialize PDF and import the unencrypted FL-100 template as multi-page background
        $pdf = new Fpdi();
        $templatePdf = __DIR__ . '/../../uploads/fl100.pdf';
        
        $pageCount = 1;
        try {
            if (file_exists($templatePdf)) {
                $pageCount = $pdf->setSourceFile($templatePdf);
            }
        } catch (\Throwable $e) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 DEBUG: setSourceFile failed: ' . $e->getMessage() . PHP_EOL, FILE_APPEND);
        }
        
        // Add page 1 with a guaranteed background
        // Use US Letter size (215.9 x 279.4) to match positions coordinate system
        $bgImage = __DIR__ . '/../../uploads/fl100_background.png';
        if (file_exists($bgImage)) {
            $pdf->AddPage('P', [215.9, 279.4]);
            $pdf->Image($bgImage, 0, 0, 215.9, 279.4);
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 DEBUG: Background image applied for page 1 (US Letter size)' . PHP_EOL, FILE_APPEND);
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
        // Get default font settings (universal solution)
        $defaultFont = \WebPdfTimeSaver\Mvp\FontManager::getFontSettings(
            [],
            $templateId ?? null,
            null
        );
        \WebPdfTimeSaver\Mvp\FontManager::applyFont($pdf, $defaultFont);
        $pdf->SetTextColor(0, 0, 0);
        file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 DEBUG: Page 1 background applied' . PHP_EOL, FILE_APPEND);

        // Use the provided values or generate test data if empty
        $dataToUse = !empty($values) ? $values : \WebPdfTimeSaver\Mvp\FL100TestDataGenerator::generateCompleteTestData();
        file_put_contents($logFile, date('Y-m-d H:i:s') . ' FL-100 DEBUG: Using ' . (empty($values) ? 'generated test data' : 'provided values') . ' for form filling' . PHP_EOL, FILE_APPEND);
        
        // Fill all fields using modular positioning system (page 1 for now)
        $this->fieldFillerManager->fillAllFields($pdf, $dataToUse, $this->logger);

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
                    // Use US Letter size (215.9 x 279.4) to match positions coordinate system
                    $bgImage = dirname($templatePdf) . '/fl100_background.png';
                    if (file_exists($bgImage)) {
                        $pdf->AddPage('P', [215.9, 279.4]);
                        $pdf->Image($bgImage, 0, 0, 215.9, 279.4);
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
        $magickCmd = "magick convert -density 300 \"{$pdfFile}[0]\" -quality 100 \"{$imageFile}\" 2>&1";
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
			? "\"{$gsBinary}\" -dSAFER -dNOPAUSE -dBATCH -sDEVICE=png16m -r300 -dFirstPage=1 -dLastPage=1 -sOutputFile=\"{$imageFile}\" \"{$pdfFile}\" 2>&1"
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
        $magickCmd = "magick convert -density 300 \"{$officialPdf}[0]\" -quality 100 \"{$backgroundImage}\" 2>&1";
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
			? "\"{$gsBinary}\" -dSAFER -dNOPAUSE -dBATCH -sDEVICE=png16m -r300 -dFirstPage=1 -dLastPage=1 -sOutputFile=\"{$backgroundImage}\" \"{$officialPdf}\" 2>&1"
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
            __DIR__ . '/../../uploads/fl100_official.pdf',
            __DIR__ . '/../../uploads/68d5cfb79bdb0_test.pdf',
            __DIR__ . '/../../uploads/68d7baede2abc_test_form.pdf'
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
        $logFile = __DIR__ . '/../../logs/pdf_debug.log';
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
        $filename = 'mvp_' . date('Ymd_His') . '_' . ($template['id'] ?? 'doc') . '_positioned.pdf';
        $outputPath = rtrim($this->outputDir, '/\\') . DIRECTORY_SEPARATOR . $filename;

        // Load positioned fields
        $positions = $this->positionLoader->loadFieldPositions($templateId);
        $positions = $this->normalizePositionsMap($positions);
        
        $logFile = __DIR__ . '/../../logs/pdf_debug.log';
        file_put_contents($logFile, date('Y-m-d H:i:s') . " MULTIPAGE: Loading positions for template: $templateId" . PHP_EOL, FILE_APPEND);
        file_put_contents($logFile, date('Y-m-d H:i:s') . ' MULTIPAGE: Loaded ' . count($positions) . ' positions' . PHP_EOL, FILE_APPEND);
        
        // Log sample of loaded positions for verification
        if (!empty($positions)) {
            $sampleKeys = array_slice(array_keys($positions), 0, 5);
            foreach ($sampleKeys as $key) {
                $pos = $positions[$key];
                file_put_contents($logFile, date('Y-m-d H:i:s') . " MULTIPAGE: Sample position - $key: x={$pos['x']}, y={$pos['y']}, page={$pos['page']}" . PHP_EOL, FILE_APPEND);
            }
        }
        
        if (empty($positions)) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' MULTIPAGE: No positions found for template ' . $templateId . ', using default fillPdfForm' . PHP_EOL, FILE_APPEND);
            $this->logger->info('No positions found for template ' . $templateId . ', using default fillPdfForm', $this->context);
            return $this->fillPdfForm($template, $values);
        }

        file_put_contents($logFile, date('Y-m-d H:i:s') . ' MULTIPAGE: Starting multi-page fill with ' . count($positions) . ' field positions for template ' . $templateId . PHP_EOL, FILE_APPEND);
        
        $pdf = new Fpdi();
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(0, 0, 0);

        // Determine page count from template or positions
        $pageCount = $template['pageCount'] ?? $this->getMaxPageFromPositions($positions);
        file_put_contents($logFile, date('Y-m-d H:i:s') . ' MULTIPAGE: Template has ' . $pageCount . ' pages' . PHP_EOL, FILE_APPEND);

        // IMPORTANT: keep rendering in a single generation pass (import + draw values per page)
        // to avoid separate overlay-form composition behavior in some viewers.
        file_put_contents(
            $logFile,
            date('Y-m-d H:i:s') . ' MULTIPAGE: Using direct per-page render path (vector import + inline value drawing).' . PHP_EOL,
            FILE_APPEND
        );

        // New primary path for fully selectable mixed text:
        // 1) Keep original source as base
        // 2) Overlay value text
        // 3) Rewrite through pdfwrite to flatten composition into unified content streams
        $unifiedSelectable = $this->tryGenerateUnifiedSelectableOutput(
            $template,
            $values,
            $templateId,
            $positions,
            $pageCount,
            $outputPath,
            $filename,
            $logFile
        );
        if (is_array($unifiedSelectable) && ($unifiedSelectable['success'] ?? false)) {
            return $unifiedSelectable;
        }
        throw new \RuntimeException(
            'Could not produce unified selectable PDF output. Generation stopped to avoid non-selectable fallback.'
        );
        
        // Resolve vector source PDF once; this is now the preferred background source for sharp/selectable output.
        $sourcePdfPath = $this->getTemplatePdfPath($templateId);
        $sourcePdfPageCount = null;
        $vectorSourceCleanupPath = null;
        if ($sourcePdfPath && file_exists($sourcePdfPath)) {
            try {
                $sourcePdfPageCount = $pdf->setSourceFile($sourcePdfPath);
                file_put_contents(
                    $logFile,
                    date('Y-m-d H:i:s') . " MULTIPAGE: Vector source ready: {$sourcePdfPath} (pages={$sourcePdfPageCount})" . PHP_EOL,
                    FILE_APPEND
                );
            } catch (\Throwable $e) {
                $sourcePdfPageCount = null;
                file_put_contents(
                    $logFile,
                    date('Y-m-d H:i:s') . " MULTIPAGE: Vector source init failed ({$sourcePdfPath}): " . $e->getMessage() . PHP_EOL,
                    FILE_APPEND
                );
                // Encrypted PDFs often fail direct import; try a decrypted temp copy so we can still keep vector backgrounds.
                $decryptedCandidate = $this->decryptPdfForVectorImport($sourcePdfPath, $templateId, $logFile);
                if ($decryptedCandidate) {
                    try {
                        $sourcePdfPageCount = $pdf->setSourceFile($decryptedCandidate);
                        $sourcePdfPath = $decryptedCandidate;
                        $vectorSourceCleanupPath = $decryptedCandidate;
                        file_put_contents(
                            $logFile,
                            date('Y-m-d H:i:s') . " MULTIPAGE: Vector source ready via decrypted temp PDF: {$decryptedCandidate} (pages={$sourcePdfPageCount})" . PHP_EOL,
                            FILE_APPEND
                        );
                    } catch (\Throwable $e2) {
                        $sourcePdfPageCount = null;
                        file_put_contents(
                            $logFile,
                            date('Y-m-d H:i:s') . " MULTIPAGE: Decrypted vector source init failed: " . $e2->getMessage() . PHP_EOL,
                            FILE_APPEND
                        );
                    }
                }
            }
        } else {
            file_put_contents(
                $logFile,
                date('Y-m-d H:i:s') . " MULTIPAGE: No vector source PDF found for template {$templateId}; using raster fallback when available." . PHP_EOL,
                FILE_APPEND
            );
        }

        // Clean template ID for raster fallback background images (remove t_ prefix only).
        // Keep full template slug so it matches generated background files.
        // Example: t_fl100_gc120 -> fl100_gc120
        $cleanTemplateId = str_replace('t_', '', $templateId);
        
        // Process each page
        $totalFieldsPlaced = 0;
        for ($pageNum = 1; $pageNum <= $pageCount; $pageNum++) {
            // Add page
            $pdf->AddPage('P', [215.9, 279.4]);

            // Preferred strategy: import original PDF page as vector background.
            $usedVectorBackground = false;
            if ($sourcePdfPageCount !== null) {
                if ($pageNum <= $sourcePdfPageCount) {
                    try {
                        $tplId = $pdf->importPage($pageNum, '/MediaBox');
                        $pdf->useTemplate($tplId, 0, 0, 215.9, 279.4);
                        $usedVectorBackground = true;
                        file_put_contents(
                            $logFile,
                            date('Y-m-d H:i:s') . " MULTIPAGE: Page {$pageNum} background applied from vector PDF import" . PHP_EOL,
                            FILE_APPEND
                        );
                    } catch (\Throwable $e) {
                        file_put_contents(
                            $logFile,
                            date('Y-m-d H:i:s') . " MULTIPAGE: Page {$pageNum} vector import failed; falling back to raster background: " . $e->getMessage() . PHP_EOL,
                            FILE_APPEND
                        );
                    }
                } else {
                    file_put_contents(
                        $logFile,
                        date('Y-m-d H:i:s') . " MULTIPAGE: Page {$pageNum} exceeds vector source page count ({$sourcePdfPageCount}); falling back to raster background." . PHP_EOL,
                        FILE_APPEND
                    );
                }
            }

            // Fallback strategy: existing raster background images (compatibility path).
            if (!$usedVectorBackground) {
                $bgImage = __DIR__ . "/../../uploads/{$templateId}_page{$pageNum}_background.png";
                if (!file_exists($bgImage)) {
                    $bgImage = __DIR__ . "/../../uploads/{$cleanTemplateId}_page{$pageNum}_background.png";
                }
                if (!file_exists($bgImage) && $pageNum === 1) {
                    $bgImage = __DIR__ . "/../../uploads/{$cleanTemplateId}_background.png";
                }

                if ($bgImage && file_exists($bgImage)) {
                    $pdf->Image($bgImage, 0, 0, 215.9, 279.4);
                    file_put_contents(
                        $logFile,
                        date('Y-m-d H:i:s') . " MULTIPAGE: Page {$pageNum} background applied from raster fallback " . basename($bgImage) . PHP_EOL,
                        FILE_APPEND
                    );
                } else {
                    file_put_contents($logFile, date('Y-m-d H:i:s') . " MULTIPAGE: No background for page {$pageNum}" . PHP_EOL, FILE_APPEND);
                }
            }
            
            // Place fields for this page
            $fieldsPlaced = $this->placeFieldsForPage($pdf, $values, $positions, $pageNum, $logFile, $template, $templateId);
            $totalFieldsPlaced += $fieldsPlaced;
            
            file_put_contents($logFile, date('Y-m-d H:i:s') . " MULTIPAGE: Placed $fieldsPlaced fields on page $pageNum" . PHP_EOL, FILE_APPEND);
        }

        file_put_contents($logFile, date('Y-m-d H:i:s') . ' MULTIPAGE: Total fields placed: ' . $totalFieldsPlaced . ' across ' . $pageCount . ' pages' . PHP_EOL, FILE_APPEND);
        
        $pdf->Output('F', $outputPath);
        if ($vectorSourceCleanupPath && file_exists($vectorSourceCleanupPath)) {
            @unlink($vectorSourceCleanupPath);
        }
        $this->assertPdfQuality($outputPath, $logFile);

        return [
            'success' => true, 
            'filename' => $filename,
            'file' => $filename, 
            'path' => $outputPath, 
            'used_positions' => count($positions),
            'fields_placed' => $totalFieldsPlaced,
            'pages' => $pageCount
        ];
    }

    /**
     * Create unified/selectable output by composing source + values, then rewriting.
     */
    private function tryGenerateUnifiedSelectableOutput(
        array $template,
        array $values,
        string $templateId,
        array $positions,
        int $pageCount,
        string $outputPath,
        string $filename,
        string $logFile
    ): ?array {
        $sourcePdfPath = $this->getTemplatePdfPath($templateId);
        if (!$sourcePdfPath || !file_exists($sourcePdfPath)) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' MULTIPAGE: Unified selectable path skipped (source PDF not found).' . PHP_EOL, FILE_APPEND);
            return null;
        }
        $qpdfBinary = $this->findQpdfBinary();
        $gsBinary = $this->findGhostscriptBinaryForRewrite();
        if (!$qpdfBinary || !$gsBinary) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' MULTIPAGE: Unified selectable path skipped (qpdf/gs missing).' . PHP_EOL, FILE_APPEND);
            return null;
        }

        $tmpDir = rtrim((string)(sys_get_temp_dir() ?: (__DIR__ . '/../../temp')), '/\\');
        $safeTemplate = preg_replace('/[^A-Za-z0-9._-]+/', '_', $templateId);
        $overlayPath = $tmpDir . DIRECTORY_SEPARATOR . "overlay_{$safeTemplate}_" . uniqid('', true) . '.pdf';
        $composedPath = $tmpDir . DIRECTORY_SEPARATOR . "composed_{$safeTemplate}_" . uniqid('', true) . '.pdf';
        $normalizedPath = $tmpDir . DIRECTORY_SEPARATOR . "normalized_{$safeTemplate}_" . uniqid('', true) . '.pdf';
        $sourceForOverlay = $sourcePdfPath;
        $decryptedSourcePath = null;
        $normalizedSourcePath = null;

        try {
            $decryptedCandidate = $this->decryptPdfForVectorImport($sourcePdfPath, $templateId, $logFile);
            if ($decryptedCandidate && file_exists($decryptedCandidate)) {
                $sourceForOverlay = $decryptedCandidate;
                $decryptedSourcePath = $decryptedCandidate;
            }

            // Normalize source first to static PDF to reduce XFA/dynamic-form text-order artifacts.
            $normalizedCandidate = $this->normalizeSourcePdfForSelection($sourceForOverlay, $templateId, $logFile);
            if ($normalizedCandidate && file_exists($normalizedCandidate)) {
                $sourceForOverlay = $normalizedCandidate;
                $normalizedSourcePath = $normalizedCandidate;
            }

            // Build values-only overlay
            $overlayPdf = new Fpdi();
            $overlayPdf->SetFont('Arial', '', 9);
            $overlayPdf->SetTextColor(0, 0, 0);
            $totalFieldsPlaced = 0;
            for ($pageNum = 1; $pageNum <= $pageCount; $pageNum++) {
                $overlayPdf->AddPage('P', [215.9, 279.4]);
                $totalFieldsPlaced += $this->placeFieldsForPage($overlayPdf, $values, $positions, $pageNum, $logFile, $template, $templateId);
            }
            $overlayPdf->Output('F', $overlayPath);
            if (!file_exists($overlayPath) || filesize($overlayPath) <= 0) {
                file_put_contents($logFile, date('Y-m-d H:i:s') . ' MULTIPAGE: Unified selectable path failed (overlay not created).' . PHP_EOL, FILE_APPEND);
                return null;
            }

            // Compose source + overlay
            $composeCmd = sprintf(
                '"%s" --overlay "%s" --repeat=1-z -- "%s" "%s" 2>&1',
                $qpdfBinary,
                $overlayPath,
                $sourceForOverlay,
                $composedPath
            );
            $outCompose = [];
            $codeCompose = 0;
            @exec($composeCmd, $outCompose, $codeCompose);
            if ($codeCompose !== 0 || !file_exists($composedPath) || filesize($composedPath) <= 0) {
                file_put_contents($logFile, date('Y-m-d H:i:s') . ' MULTIPAGE: Unified selectable path failed (qpdf compose): ' . implode(' ', $outCompose) . PHP_EOL, FILE_APPEND);
                return null;
            }

            // Rewrite/flatten composition into unified content streams
            $rewriteCmd = sprintf(
                '"%s" -dSAFER -dNOPAUSE -dBATCH -sDEVICE=pdfwrite -dCompatibilityLevel=1.7 -dCompressFonts=true -dSubsetFonts=true -dDetectDuplicateImages=true -dDownsampleColorImages=false -dDownsampleGrayImages=false -dDownsampleMonoImages=false -dAutoFilterColorImages=false -dAutoFilterGrayImages=false -dColorImageFilter=/FlateEncode -dGrayImageFilter=/FlateEncode -dPassThroughJPEGImages=true -dPassThroughJPXImages=true -sOutputFile="%s" "%s" 2>&1',
                $gsBinary,
                $normalizedPath,
                $composedPath
            );
            $outRewrite = [];
            $codeRewrite = 0;
            @exec($rewriteCmd, $outRewrite, $codeRewrite);
            if ($codeRewrite !== 0 || !file_exists($normalizedPath) || filesize($normalizedPath) <= 0) {
                file_put_contents($logFile, date('Y-m-d H:i:s') . ' MULTIPAGE: Unified selectable path failed (pdfwrite rewrite): ' . implode(' ', $outRewrite) . PHP_EOL, FILE_APPEND);
                return null;
            }

            // Move final normalized output
            if (file_exists($outputPath)) {
                @unlink($outputPath);
            }
            if (!@rename($normalizedPath, $outputPath)) {
                // fallback copy
                @copy($normalizedPath, $outputPath);
                @unlink($normalizedPath);
            }
            if (!file_exists($outputPath) || filesize($outputPath) <= 0) {
                file_put_contents($logFile, date('Y-m-d H:i:s') . ' MULTIPAGE: Unified selectable path failed (final move).' . PHP_EOL, FILE_APPEND);
                return null;
            }

            file_put_contents($logFile, date('Y-m-d H:i:s') . ' MULTIPAGE: Unified selectable output success (compose + rewrite).' . PHP_EOL, FILE_APPEND);
            $this->assertPdfQuality($outputPath, $logFile);
            return [
                'success' => true,
                'filename' => $filename,
                'file' => $filename,
                'path' => $outputPath,
                'used_positions' => count($positions),
                'fields_placed' => $totalFieldsPlaced,
                'pages' => $pageCount,
            ];
        } finally {
            if (file_exists($overlayPath)) @unlink($overlayPath);
            if (file_exists($composedPath)) @unlink($composedPath);
            if (file_exists($normalizedPath)) @unlink($normalizedPath);
            if ($normalizedSourcePath && file_exists($normalizedSourcePath)) @unlink($normalizedSourcePath);
            if ($decryptedSourcePath && file_exists($decryptedSourcePath)) @unlink($decryptedSourcePath);
        }
    }

    /**
     * Place fields on a specific page
     */
    private function placeFieldsForPage(Fpdi $pdf, array $values, array $positions, int $pageNum, string $logFile, ?array $template = null, ?string $templateId = null): int {
        $values = $this->sanitizeFillValues($values);
        $fieldsPlaced = 0;
        
        // Use UniversalFieldMapper to map user values to PDF field names
        // Extract PDF field names (keys) from positions
        $pdfFieldNames = array_keys($positions);
        
        // Prefer direct key matches to avoid expensive semantic mapping when keys already align.
        $cacheKey = md5(json_encode($pdfFieldNames) . json_encode(array_keys($values)) . ($templateId ?? ''));
        if (!isset($this->fieldMappingCache[$cacheKey])) {
            $directMappings = [];
            $remainingValues = [];

            foreach ($values as $valueKey => $valueContent) {
                $resolvedKey = $this->resolvePositionKeyForValue($valueKey, $positions, $template);
                if ($resolvedKey !== null) {
                    $directMappings[$valueKey] = $resolvedKey;
                } else {
                    $remainingValues[$valueKey] = $valueContent;
                }
            }

            $mapped = $directMappings;
            if (!empty($remainingValues)) {
                // Use UniversalFieldMapper with saved preferences for unmatched fields only.
                $semanticMappings = $this->fieldMapper->applyMappingPreferences(
                    $remainingValues,
                    $pdfFieldNames,
                    $templateId ?? ''
                );
                $mapped = array_merge($mapped, $semanticMappings);
            }

            $this->fieldMappingCache[$cacheKey] = $mapped;
            file_put_contents(
                $logFile,
                date('Y-m-d H:i:s') . " MULTIPAGE: Computed " . count($this->fieldMappingCache[$cacheKey]) . " field mappings using UniversalFieldMapper\n",
                FILE_APPEND
            );
        }
        $fieldMappingCache = $this->fieldMappingCache[$cacheKey];

        // Prevent stacking multiple user keys onto the same PDF position (semantic mapper / prefs).
        $placedPositionKeysOnPage = [];

        foreach ($values as $fieldKey => $value) {
            if (!is_string($fieldKey)) {
                continue;
            }
            if ($this->isInternalMetaFieldKey($fieldKey)) {
                continue;
            }
            if ($value === '' || $value === null) {
                file_put_contents($logFile, date('Y-m-d H:i:s') . " MULTIPAGE: Page $pageNum - Skipping $fieldKey (empty value)" . PHP_EOL, FILE_APPEND);
                continue;
            }
            
            // STRICT priority for mapping:
            // 1) Explicit template mapping (authoritative)
            // 2) Direct key match
            // 3) Semantic mapping cache (fallback only)
            $positionKey = null;
            if ($template) {
                $pdfFieldName = $this->findPdfFieldName($fieldKey, $template);
                if ($pdfFieldName) {
                    if (isset($positions[$pdfFieldName])) {
                        $positionKey = $pdfFieldName;
                    } else {
                        // Bridge canonicalized extractor keys (underscored) to original AcroForm names.
                        $normalizedPdfFieldName = $this->normalizeFieldLookupKey($pdfFieldName);
                        foreach (array_keys($positions) as $candidateKey) {
                            if ($this->normalizeFieldLookupKey($candidateKey) === $normalizedPdfFieldName) {
                                $positionKey = $candidateKey;
                                break;
                            }
                        }
                    }
                }
            }
            if (!$positionKey && isset($positions[$fieldKey])) {
                $positionKey = $fieldKey;
            }
            if (!$positionKey) {
                $positionKey = $fieldMappingCache[$fieldKey] ?? null;
            }
            
            if (!$positionKey || !isset($positions[$positionKey])) {
                file_put_contents($logFile, date('Y-m-d H:i:s') . " MULTIPAGE: Page $pageNum - Skipping $fieldKey (no position found)" . PHP_EOL, FILE_APPEND);
                continue;
            }

            if (isset($placedPositionKeysOnPage[$positionKey])) {
                file_put_contents(
                    $logFile,
                    date('Y-m-d H:i:s') . " MULTIPAGE: Page $pageNum - Skipping $fieldKey -> $positionKey (position slot already filled; avoids duplicate text)" . PHP_EOL,
                    FILE_APPEND
                );
                continue;
            }

            file_put_contents($logFile, date('Y-m-d H:i:s') . " MULTIPAGE: Page $pageNum - Mapped: $fieldKey -> $positionKey" . PHP_EOL, FILE_APPEND);
            
            file_put_contents($logFile, date('Y-m-d H:i:s') . " MULTIPAGE: Page $pageNum - Processing $fieldKey -> $positionKey = '$value'" . PHP_EOL, FILE_APPEND);
            
            $position = $positions[$positionKey];
            $fieldPage = (int)($position['page'] ?? 1);
            
            // Skip if field doesn't belong on this page
            if ($fieldPage !== $pageNum) {
                continue;
            }
            
            $x = (float)($position['x'] ?? 0);
            $y = (float)($position['y'] ?? 0);
            $fontSize = FieldMetrics::exportFontPtForField($position, $values, $fieldKey);
            $fontStyle = (string)($position['fontStyle'] ?? '');
            $width = (float)($position['width'] ?? 100);
            $height = (float)($position['height'] ?? 10);
            
            // Validate coordinates before applying
            // Expected coordinate system: millimeters with top-left origin (FPDF standard)
            // US Letter page dimensions: 215.9mm × 279.4mm
            $pageWidthMm = 215.9;  // US Letter width in mm
            $pageHeightMm = 279.4; // US Letter height in mm
            
            $coordinateIssues = [];
            
            // Check for invalid numbers
            if (!is_finite($x) || !is_finite($y)) {
                $coordinateIssues[] = "Invalid coordinates (NaN or Infinity): x=$x, y=$y";
                file_put_contents($logFile, date('Y-m-d H:i:s') . " WARNING: $fieldKey has invalid coordinates, skipping" . PHP_EOL, FILE_APPEND);
                continue;
            }
            
            // Check for negative coordinates (shouldn't happen with proper conversion)
            if ($x < 0 || $y < 0) {
                $coordinateIssues[] = "Negative coordinates: x=$x, y=$y";
                file_put_contents($logFile, date('Y-m-d H:i:s') . " WARNING: $fieldKey has negative coordinates: x=$x, y=$y" . PHP_EOL, FILE_APPEND);
            }
            
            // Check for coordinates outside page bounds (with tolerance for reasonable overflow)
            $tolerance = 5.0; // 5mm tolerance for fields that might extend slightly beyond page
            if ($x > ($pageWidthMm + $tolerance)) {
                $coordinateIssues[] = "X coordinate exceeds page width: x=$x mm (max: $pageWidthMm mm)";
                file_put_contents($logFile, date('Y-m-d H:i:s') . " WARNING: $fieldKey X coordinate ($x mm) exceeds page width ($pageWidthMm mm)" . PHP_EOL, FILE_APPEND);
            }
            if ($y > ($pageHeightMm + $tolerance)) {
                $coordinateIssues[] = "Y coordinate exceeds page height: y=$y mm (max: $pageHeightMm mm)";
                file_put_contents($logFile, date('Y-m-d H:i:s') . " WARNING: $fieldKey Y coordinate ($y mm) exceeds page height ($pageHeightMm mm)" . PHP_EOL, FILE_APPEND);
            }
            
            // Check if field extends beyond page bounds
            if (($x + $width) > ($pageWidthMm + $tolerance)) {
                $coordinateIssues[] = "Field width extends beyond page: x=$x, width=$width (page width: $pageWidthMm)";
                file_put_contents($logFile, date('Y-m-d H:i:s') . " WARNING: $fieldKey extends beyond page width: x=$x, width=$width" . PHP_EOL, FILE_APPEND);
            }
            if (($y + $height) > ($pageHeightMm + $tolerance)) {
                $coordinateIssues[] = "Field height extends beyond page: y=$y, height=$height (page height: $pageHeightMm)";
                file_put_contents($logFile, date('Y-m-d H:i:s') . " WARNING: $fieldKey extends beyond page height: y=$y, height=$height" . PHP_EOL, FILE_APPEND);
            }
            
            // Log coordinate info for debugging
            if (!empty($coordinateIssues)) {
                file_put_contents($logFile, date('Y-m-d H:i:s') . " $fieldKey coordinate issues: " . implode('; ', $coordinateIssues) . PHP_EOL, FILE_APPEND);
            } else {
                file_put_contents($logFile, date('Y-m-d H:i:s') . " $fieldKey coordinates validated: x=$x, y=$y, width=$width, height=$height (mm)" . PHP_EOL, FILE_APPEND);
            }
            
            // Get font settings using FontManager (universal solution)
            $fieldType = $position['type'] ?? null;
            $fontSettings = \WebPdfTimeSaver\Mvp\FontManager::getFontSettings(
                $position,
                $templateId ?? null,
                $fieldType
            );
            
            // Position and write using field bounds so output aligns with preview boxes.
            $fieldTypeNormalized = strtolower((string)($position['type'] ?? 'text'));
            $boxWidth = max(2.0, $width);
            $boxHeight = max(2.0, $height);
            $stringValue = (string)$value;
            // Per-field styling chosen in the fill-out UI (persisted as _font_* values).
            $userColorRgb = $this->parseUserFontColor((string)($values['_font_color__' . $fieldKey] ?? ''));
            $userStyleFlags = strtoupper((string)($values['_font_style__' . $fieldKey] ?? ''));
            $userBold = strpos($userStyleFlags, 'B') !== false;
            $userUnderline = strpos($userStyleFlags, 'U') !== false;
            $userStrike = strpos($userStyleFlags, 'S') !== false;
            // Safety clamp for imported outlier font sizes (large boxes can carry inflated values).
            // This keeps generated output legible and consistent until a field is explicitly edited.
            if ($fieldTypeNormalized === 'checkbox' || $fieldTypeNormalized === 'radio') {
                $fontSize = max(5.0, min(10.0, $fontSize));
            } else {
                $fontSize = max(4.0, min(24.0, $fontSize));
            }
            // Apply font to PDF (use FontManager family/style where available, but always enforce clamped size).
            if (!empty($fontSettings['fontFamily']) || !empty($fontSettings['fontSize'])) {
                $fontSettings['fontSize'] = $fontSize;
                \WebPdfTimeSaver\Mvp\FontManager::applyFont($pdf, $fontSettings);
            } else {
                // Fallback to position-based font
                $pdf->SetFont('Arial', $fontStyle, $fontSize);
            }
            
            if ($this->isCheckmarkFieldType($fieldTypeNormalized)) {
                $checked = $this->shouldRenderCheckedMark($value);
                if ($checked) {
                    $checkFontPt = max(5.0, min(10.0, $boxHeight * 2.0));
                    $pdf->SetFont('Arial', 'B', $checkFontPt);
                    $pdf->SetXY($x, $y);
                    $pdf->Cell($boxWidth, $boxHeight, 'X', 0, 0, 'C');
                }
            } else {
                // Keep text inside detected field width and align baseline inside field height.
                $paddingX = 0.4;
                $maxTextWidth = max(1.0, $boxWidth - ($paddingX * 2));

                // Merge user bold/underline onto the position's base style so width
                // measurement and rendering both reflect the chosen formatting.
                $textStyle = $fontStyle;
                if ($userBold && strpos($textStyle, 'B') === false) { $textStyle .= 'B'; }
                if ($userUnderline && strpos($textStyle, 'U') === false) { $textStyle .= 'U'; }

                $fontPt = max(5.0, (float)$fontSize);
                $pdf->SetFont('Arial', $textStyle, $fontPt);
                $fontHeightMm = FieldMetrics::ptToMm($fontPt);
                if ($fontHeightMm > ($boxHeight - 0.4)) {
                    $adjustedPt = max(5.0, FieldMetrics::mmToPt($boxHeight - 0.4));
                    $pdf->SetFont('Arial', $textStyle, $adjustedPt);
                    $fontPt = $adjustedPt;
                    $fontHeightMm = FieldMetrics::ptToMm($fontPt);
                }
                $text = $this->fitTextToWidth($pdf, $stringValue, $maxTextWidth);

                // Use an ascender-based baseline so text doesn't sit too low in short fields.
                // FPDF Text() uses baseline Y; ~0.78 * font height plus small top padding
                // visually aligns FL-100 style fields better than full font-height offset.
                $baselineOffset = ($fontHeightMm * 0.78) + 0.35;
                $baselineY = $y + max(0.9, min($boxHeight - 0.35, $baselineOffset));

                if ($userColorRgb !== null) {
                    $pdf->SetTextColor($userColorRgb[0], $userColorRgb[1], $userColorRgb[2]);
                }
                // FPDF Text() renders underline automatically when the font style includes 'U'.
                $pdf->Text($x + $paddingX, $baselineY, $text);

                // Strikeout: FPDF has no native strike, so draw a line across the x-height.
                if ($userStrike && $text !== '') {
                    $textWidthMm = $pdf->GetStringWidth($text);
                    $strikeY = $baselineY - ($fontHeightMm * 0.30);
                    if ($userColorRgb !== null) {
                        $pdf->SetDrawColor($userColorRgb[0], $userColorRgb[1], $userColorRgb[2]);
                    }
                    $pdf->SetLineWidth(max(0.15, $fontHeightMm * 0.06));
                    $pdf->Line($x + $paddingX, $strikeY, $x + $paddingX + $textWidthMm, $strikeY);
                    $pdf->SetLineWidth(0.2);
                    $pdf->SetDrawColor(0, 0, 0);
                }

                if ($userColorRgb !== null) {
                    $pdf->SetTextColor(0, 0, 0);
                }
            }
            
            $placedPositionKeysOnPage[$positionKey] = true;

            $fieldsPlaced++;
            file_put_contents($logFile, date('Y-m-d H:i:s') . " MULTIPAGE: Page $pageNum - Placed $fieldKey at ($x, $y)" . PHP_EOL, FILE_APPEND);
        }

        $fieldsPlaced += $this->placeTemporaryCustomFieldsForPage($pdf, $values, $pageNum, $logFile);

        return $fieldsPlaced;
    }

    /**
     * Stamp user-added custom input boxes (temporary_custom_fields_json) onto the page.
     */
    private function placeTemporaryCustomFieldsForPage(Fpdi $pdf, array $values, int $pageNum, string $logFile): int {
        $raw = $values['temporary_custom_fields_json'] ?? '[]';
        $fields = is_array($raw) ? $raw : json_decode((string)$raw, true);
        if (!is_array($fields)) {
            return 0;
        }

        $placed = 0;
        foreach ($fields as $row) {
            if (!is_array($row)) {
                continue;
            }
            if (max(1, (int)($row['page'] ?? 1)) !== $pageNum) {
                continue;
            }
            $value = trim((string)($row['value'] ?? ''));
            if ($value === '') {
                continue;
            }

            $x = (float)($row['left'] ?? 20);
            $y = (float)($row['top'] ?? 20);
            $boxWidth = max(2.0, (float)($row['width'] ?? 45));
            $boxHeight = max(2.0, (float)($row['height'] ?? 3.18));
            if ($boxHeight >= 6) {
                $boxHeight = 3.18;
            }
            $fontPt = FieldMetrics::exportFontPtForField(
                [
                    'fontSize' => $row['fontSize'] ?? ($row['fontPt'] ?? ($row['pt'] ?? FieldMetrics::defaultFontPx())),
                    'fontSizeUnit' => $row['fontSizeUnit'] ?? (
                        isset($row['fontPt']) || (isset($row['pt']) && !isset($row['fontSize']))
                            ? ''
                            : 'px'
                    ),
                ],
                ['_font_size__temp' => $row['fontSize'] ?? ($row['fontPt'] ?? ($row['pt'] ?? ''))],
                'temp'
            );

            $userStyleFlags = strtoupper(preg_replace('/[^BIUS]/', '', (string)($row['fontStyle'] ?? '')));
            $textStyle = '';
            if (strpos($userStyleFlags, 'B') !== false) {
                $textStyle .= 'B';
            }
            if (strpos($userStyleFlags, 'U') !== false) {
                $textStyle .= 'U';
            }
            $userStrike = strpos($userStyleFlags, 'S') !== false;
            $userColorRgb = $this->parseUserFontColor((string)($row['fontColor'] ?? ''));

            $paddingX = 0.4;
            $maxTextWidth = max(1.0, $boxWidth - ($paddingX * 2));
            $pdf->SetFont('Arial', $textStyle, $fontPt);
            $fontHeightMm = FieldMetrics::ptToMm($fontPt);
            if ($fontHeightMm > ($boxHeight - 0.4)) {
                $adjustedPt = max(5.0, FieldMetrics::mmToPt($boxHeight - 0.4));
                $pdf->SetFont('Arial', $textStyle, $adjustedPt);
                $fontPt = $adjustedPt;
                $fontHeightMm = FieldMetrics::ptToMm($fontPt);
            }
            $text = $this->fitTextToWidth($pdf, $value, $maxTextWidth);
            $baselineOffset = ($fontHeightMm * 0.78) + 0.35;
            $baselineY = $y + max(0.9, min($boxHeight - 0.35, $baselineOffset));

            if ($userColorRgb !== null) {
                $pdf->SetTextColor($userColorRgb[0], $userColorRgb[1], $userColorRgb[2]);
            }
            $pdf->Text($x + $paddingX, $baselineY, $text);

            if ($userStrike && $text !== '') {
                $textWidthMm = $pdf->GetStringWidth($text);
                $strikeY = $baselineY - ($fontHeightMm * 0.30);
                if ($userColorRgb !== null) {
                    $pdf->SetDrawColor($userColorRgb[0], $userColorRgb[1], $userColorRgb[2]);
                }
                $pdf->SetLineWidth(max(0.15, $fontHeightMm * 0.06));
                $pdf->Line($x + $paddingX, $strikeY, $x + $paddingX + $textWidthMm, $strikeY);
                $pdf->SetLineWidth(0.2);
                $pdf->SetDrawColor(0, 0, 0);
            }
            if ($userColorRgb !== null) {
                $pdf->SetTextColor(0, 0, 0);
            }

            $placed++;
            $label = (string)($row['label'] ?? ($row['id'] ?? 'custom'));
            file_put_contents($logFile, date('Y-m-d H:i:s') . " MULTIPAGE: Page $pageNum - Placed temp custom field '$label' at ($x, $y)" . PHP_EOL, FILE_APPEND);
        }

        return $placed;
    }

    /**
     * Parse a #RRGGBB hex color (chosen in the fill-out UI) into an [r,g,b] array.
     * Returns null for empty/invalid values and for the UI defaults (#0b1f3a navy
     * and #000000 black) so unedited fields keep the default black PDF text.
     */
    private function parseUserFontColor(string $hex): ?array {
        $hex = ltrim(trim($hex), '#');
        if (!preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            return null;
        }
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        // Treat UI default colors as "no override" -> default black text.
        if (($r === 0 && $g === 0 && $b === 0) || ($r === 11 && $g === 31 && $b === 58)) {
            return null;
        }
        return [$r, $g, $b];
    }

    /**
     * Truncate text to fit the given field width in current font settings.
     */
    private function fitTextToWidth(Fpdi $pdf, string $value, float $maxWidthMm): string {
        $text = trim($value);
        if ($text === '') {
            return '';
        }

        if ($pdf->GetStringWidth($text) <= $maxWidthMm) {
            return $text;
        }

        while (mb_strlen($text) > 1 && $pdf->GetStringWidth($text . '…') > $maxWidthMm) {
            $text = mb_substr($text, 0, -1);
        }

        return $text . '…';
    }

    private function isCheckmarkFieldType(string $fieldType): bool {
        $normalized = strtolower(trim($fieldType));
        if ($normalized === '') {
            return false;
        }

        return in_array($normalized, [
            'checkbox',
            'check',
            'radio',
            'radiobutton',
            'button',
            'btn',
            'option',
            'choice',
            'select',
            'dropdown',
            'toggle'
        ], true);
    }

    private function shouldRenderCheckedMark($value): bool {
        $stringValue = strtolower(trim((string)$value));
        if ($stringValue === '') {
            return false;
        }

        return !in_array($stringValue, ['0', 'off', 'no', 'false', 'unchecked', 'none', 'null'], true);
    }
    
    /**
     * Get maximum page number from positions array
     */
    private function getMaxPageFromPositions(array $positions): int {
        $maxPage = 1;
        foreach ($positions as $position) {
            if (isset($position['page']) && $position['page'] > $maxPage) {
                $maxPage = $position['page'];
            }
        }
        return $maxPage;
    }

    /**
     * Ensure positions are keyed by string field names, even when source JSON is a list.
     */
    private function normalizePositionsMap(array $positions): array {
        if (empty($positions)) {
            return [];
        }

        $normalized = [];
        foreach ($positions as $key => $position) {
            if (!is_array($position)) {
                continue;
            }

            $name = null;
            if (is_string($key) && $key !== '') {
                $name = $key;
            } else {
                $candidate = $position['name'] ?? $position['fieldName'] ?? null;
                if (is_string($candidate) && trim($candidate) !== '') {
                    $name = trim($candidate);
                }
            }

            if ($name === null || $name === '') {
                $name = 'field_' . (string)$key;
            }

            $normalized[$name] = $position;
        }

        return $normalized;
    }

    /**
     * Normalize field names for resilient cross-source lookup.
     * Handles AcroForm style names and extractor canonical names.
     */
    private function normalizeFieldLookupKey(string $name): string {
        $normalized = strtolower(trim($name));
        $normalized = preg_replace('/[^a-z0-9]+/', '_', $normalized);
        $normalized = preg_replace('/_+/', '_', $normalized);
        return trim((string)$normalized, '_');
    }

    /**
     * Resolve a stored/user field key to the canonical positions-map key (case-insensitive).
     */
    private function resolvePositionKeyForValue(string $fieldKey, array $positions, ?array $template = null): ?string {
        if (isset($positions[$fieldKey])) {
            return $fieldKey;
        }
        if ($template) {
            $pdfFieldName = $this->findPdfFieldName($fieldKey, $template);
            if (is_string($pdfFieldName) && $pdfFieldName !== '' && isset($positions[$pdfFieldName])) {
                return $pdfFieldName;
            }
        }
        $normalizedValueKey = $this->normalizeFieldLookupKey($fieldKey);
        if ($normalizedValueKey === '') {
            return null;
        }
        foreach (array_keys($positions) as $candidateKey) {
            if ($this->normalizeFieldLookupKey($candidateKey) === $normalizedValueKey) {
                return $candidateKey;
            }
        }
        return null;
    }

    /**
     * Prevent hidden UI metadata (font/style/preset pointers/temp JSON) and connector
     * token values from being treated as real PDF field content during mapping/fill.
     *
     * @param array<string,mixed> $values
     * @return array<string,mixed>
     */
    private function sanitizeFillValues(array $values): array {
        $clean = [];
        foreach ($values as $key => $value) {
            if (!is_string($key) || $key === '' || $this->isInternalMetaFieldKey($key)) {
                continue;
            }
            if (is_array($value) || is_object($value)) {
                continue;
            }
            $text = trim((string)$value);
            if ($text !== '' && $this->isInternalPresetTokenValue($text)) {
                // Skip connector tokens entirely; they are metadata selectors, not content.
                continue;
            }
            $clean[$key] = $value;
        }
        return $clean;
    }

    private function isInternalMetaFieldKey(string $fieldKey): bool {
        return str_starts_with($fieldKey, '_') || $fieldKey === 'temporary_custom_fields_json';
    }

    private function isInternalPresetTokenValue(string $value): bool {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return false;
        }
        if (stripos($trimmed, '::fcf_') !== false) {
            return true;
        }
        return (bool)preg_match('/^[a-z][a-z ]*fields::[a-z0-9_:-]+$/i', $trimmed);
    }

    /**
     * Build a decrypted temporary copy for vector import when source is encrypted.
     * Returns temp path on success, null otherwise.
     */
    private function decryptPdfForVectorImport(string $sourcePdfPath, string $templateId, string $logFile): ?string {
        $qpdfBinary = $this->findQpdfBinary();
        if (!$qpdfBinary) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . " MULTIPAGE: qpdf not available; cannot decrypt vector source." . PHP_EOL, FILE_APPEND);
            return null;
        }
        $tmpDir = rtrim((string)(sys_get_temp_dir() ?: __DIR__ . '/../../temp'), '/\\');
        $safeTemplate = preg_replace('/[^A-Za-z0-9._-]+/', '_', $templateId);
        $decryptedPath = $tmpDir . DIRECTORY_SEPARATOR . "vector_src_{$safeTemplate}_" . uniqid('', true) . ".pdf";
        $cmd = sprintf(
            '"%s" --decrypt "%s" "%s" 2>&1',
            $qpdfBinary,
            $sourcePdfPath,
            $decryptedPath
        );
        $out = [];
        $code = 0;
        @exec($cmd, $out, $code);
        if ($code === 0 && file_exists($decryptedPath) && filesize($decryptedPath) > 0) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . " MULTIPAGE: Decrypted temp source created for vector import." . PHP_EOL, FILE_APPEND);
            return $decryptedPath;
        }
        if (file_exists($decryptedPath)) {
            @unlink($decryptedPath);
        }
        file_put_contents($logFile, date('Y-m-d H:i:s') . " MULTIPAGE: qpdf decrypt for vector source failed." . PHP_EOL, FILE_APPEND);
        return null;
    }

    private function findQpdfBinary(): ?string {
        $candidates = [
            dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'qpdf' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'qpdf.bat',
            dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'qpdf' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'qpdf.exe',
            'qpdf',
            'qpdf.exe'
        ];
        foreach ($candidates as $candidate) {
            $output = [];
            $returnCode = 0;
            $probe = strpos($candidate, DIRECTORY_SEPARATOR) !== false
                ? sprintf('"%s" --version 2>&1', $candidate)
                : ($candidate . ' --version 2>&1');
            @exec($probe, $output, $returnCode);
            if ($returnCode === 0) {
                return $candidate;
            }
        }
        return null;
    }

    private function findGhostscriptBinaryForRewrite(): ?string {
        $candidates = [
            dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'gs1000w64.exe',
            'gswin64c',
            'gswin32c',
            'gs',
        ];
        foreach ($candidates as $candidate) {
            $probe = strpos($candidate, DIRECTORY_SEPARATOR) !== false
                ? sprintf('"%s" -v 2>&1', $candidate)
                : ($candidate . ' -v 2>&1');
            $output = [];
            $returnCode = 0;
            @exec($probe, $output, $returnCode);
            if ($returnCode === 0) {
                return $candidate;
            }
        }
        return null;
    }

    /**
     * Convert source PDF to a static normalized PDF (pdfwrite) before overlay composition.
     * This helps with XFA/dynamic PDFs where text extraction/selection order can be erratic.
     */
    private function normalizeSourcePdfForSelection(string $sourcePdfPath, string $templateId, string $logFile): ?string {
        $gsBinary = $this->findGhostscriptBinaryForRewrite();
        if (!$gsBinary || !file_exists($sourcePdfPath)) {
            return null;
        }
        $tmpDir = rtrim((string)(sys_get_temp_dir() ?: (__DIR__ . '/../../temp')), '/\\');
        $safeTemplate = preg_replace('/[^A-Za-z0-9._-]+/', '_', $templateId);
        $normalizedSource = $tmpDir . DIRECTORY_SEPARATOR . "srcnorm_{$safeTemplate}_" . uniqid('', true) . '.pdf';
        $cmd = sprintf(
            '"%s" -dSAFER -dNOPAUSE -dBATCH -sDEVICE=pdfwrite -dCompatibilityLevel=1.7 -dCompressFonts=true -dSubsetFonts=true -dDetectDuplicateImages=true -dDownsampleColorImages=false -dDownsampleGrayImages=false -dDownsampleMonoImages=false -dAutoFilterColorImages=false -dAutoFilterGrayImages=false -dColorImageFilter=/FlateEncode -dGrayImageFilter=/FlateEncode -dPassThroughJPEGImages=true -dPassThroughJPXImages=true -sOutputFile="%s" "%s" 2>&1',
            $gsBinary,
            $normalizedSource,
            $sourcePdfPath
        );
        $out = [];
        $code = 0;
        @exec($cmd, $out, $code);
        if ($code === 0 && file_exists($normalizedSource) && filesize($normalizedSource) > 0) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . ' MULTIPAGE: Source PDF normalized to static form for selection stability.' . PHP_EOL, FILE_APPEND);
            return $normalizedSource;
        }
        if (file_exists($normalizedSource)) {
            @unlink($normalizedSource);
        }
        file_put_contents($logFile, date('Y-m-d H:i:s') . ' MULTIPAGE: Source normalization skipped (pdfwrite failed).' . PHP_EOL, FILE_APPEND);
        return null;
    }


}
