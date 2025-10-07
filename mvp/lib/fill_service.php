<?php
declare(strict_types=1);

namespace WebPdfTimeSaver\Mvp;

$__autoload = __DIR__ . '/../../vendor/autoload.php';
if (file_exists($__autoload)) {
    require_once $__autoload;
}
require_once __DIR__ . '/pdf_form_filler.php';
require_once __DIR__ . '/logger.php';
use setasign\Fpdi\Fpdi;

final class FillService {
	private string $outputDir;
	private PdfFormFiller $formFiller;
    private Logger $logger;

    public function __construct(string $outputDir = __DIR__ . '/../../output', ?Logger $logger = null) {
		$this->outputDir = $outputDir;
		if (!is_dir($this->outputDir)) { mkdir($this->outputDir, 0777, true); }
        $this->logger = $logger ?? new Logger();
		$this->formFiller = new PdfFormFiller($outputDir, __DIR__ . '/../../uploads', $this->logger);
	}

	public function generateSimplePdf(array $template, array $values, array $context = []): array {
		$pd = $context['pdId'] ?? null;
		$this->formFiller->setContext($context);
		$this->logger->info('PDF generation started', ['pdId' => $pd]);
		$this->logger->debug('Template summary: ' . json_encode(['id' => $template['id'] ?? null]), ['pdId' => $pd]);
		$this->logger->debug('Values (masked): ' . json_encode($this->maskPii($values)), ['pdId' => $pd]);
		$startedAt = microtime(true);
		try {
			// Prefer positioned rendering when positions exist
			$templateId = (string)($template['id'] ?? 't_fl100_gc120');
			$result = $this->formFiller->fillPdfFormWithPositions($template, $values, $templateId);
			$durationMs = (int)round((microtime(true) - $startedAt) * 1000);
			$path = $result['path'] ?? ($result['outputPath'] ?? null);
			$metrics = [];
			if ($path && file_exists($path)) {
				$metrics['sizeBytes'] = filesize($path) ?: 0;
				try {
					$probe = new Fpdi();
					$metrics['pages'] = $probe->setSourceFile($path);
				} catch (\Throwable $e) {
					$metrics['pages'] = null;
				}
			}
			$this->logger->info('PDF generation finished', ['pdId' => $pd, 'durationMs' => $durationMs] + $metrics);
			return $result;
		} catch (\Throwable $e) {
			$durationMs = (int)round((microtime(true) - $startedAt) * 1000);
			$this->logger->error('PDF generation error: ' . $e->getMessage(), ['pdId' => $pd, 'durationMs' => $durationMs]);
			throw $e;
		}
	}

	public function stampSigned(string $inputPath, array $context = []): array {
		$pd = $context['pdId'] ?? null;
		$this->formFiller->setContext($context);
		$this->logger->info('PDF signing started for: ' . $inputPath, ['pdId' => $pd]);
		$startedAt = microtime(true);
		try {
			$result = $this->formFiller->stampSigned($inputPath);
			$durationMs = (int)round((microtime(true) - $startedAt) * 1000);
			$path = $result['path'] ?? ($result['outputPath'] ?? null);
			$metrics = [];
			if ($path && file_exists($path)) {
				$metrics['sizeBytes'] = filesize($path) ?: 0;
				try {
					$probe = new Fpdi();
					$metrics['pages'] = $probe->setSourceFile($path);
				} catch (\Throwable $e) {
					$metrics['pages'] = null;
				}
			}
			$this->logger->info('PDF signing finished', ['pdId' => $pd, 'durationMs' => $durationMs] + $metrics);
			return $result;
		} catch (\Throwable $e) {
			$durationMs = (int)round((microtime(true) - $startedAt) * 1000);
			$this->logger->error('PDF signing error: ' . $e->getMessage(), ['pdId' => $pd, 'durationMs' => $durationMs]);
			throw $e;
		}
	}

	private function maskPii(array $data): array {
		$masked = [];
		foreach ($data as $k => $v) {
			if (!is_string($v)) { $masked[$k] = $v; continue; }
			$val = $v;
			// Mask emails
			$val = preg_replace('/([a-zA-Z0-9_\.\-])([a-zA-Z0-9_\.\-]*)@/','$1***@',$val);
			// Mask phone numbers (digits sequences of length 7-15)
			$val = preg_replace('/\b\+?\d[\d\s\-\.\(\)]{6,14}\d\b/','***-***-****',$val);
			$masked[$k] = $val;
		}
		return $masked;
	}
}


