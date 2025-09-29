<?php
declare(strict_types=1);

namespace WebPdfTimeSaver\Mvp;

require_once __DIR__ . '/../../vendor/autoload.php';
use FPDF;

final class FillService {
	private string $outputDir;

	public function __construct(string $outputDir = __DIR__ . '/../../output') {
		$this->outputDir = $outputDir;
		if (!is_dir($this->outputDir)) { mkdir($this->outputDir, 0777, true); }
	}

	public function generateSimplePdf(array $template, array $values): array {
		$filename = 'mvp_' . date('Ymd_His') . '_' . ($template['id'] ?? 'doc') . '.pdf';
		$path = rtrim($this->outputDir, '/\\') . DIRECTORY_SEPARATOR . $filename;

		$pdf = new FPDF();
		$pdf->AddPage();
		$pdf->SetFont('Arial', 'B', 16);
		$pdf->Cell(0, 10, ($template['code'] ?? 'Document') . ' — ' . ($template['name'] ?? ''), 0, 1);
		$pdf->SetFont('Arial', '', 12);

		foreach (($template['fields'] ?? []) as $field) {
			$key = $field['key'];
			$label = $field['label'] ?? $key;
			$val = (string)($values[$key] ?? '');
			$pdf->MultiCell(0, 8, $label . ': ' . $val);
		}

		// Attempt to write and verify
		$pdf->Output('F', $path);
		if (!file_exists($path)) {
			throw new \RuntimeException('Failed to generate PDF at ' . $path);
		}
		return [ 'filename' => $filename, 'path' => $path ];
	}

	public function stampSigned(string $inputPath): array {
		$filename = 'signed_' . basename($inputPath);
		$path = rtrim($this->outputDir, '/\\') . DIRECTORY_SEPARATOR . $filename;
		$pdf = new FPDF();
		$pdf->AddPage();
		$pdf->SetFont('Arial', 'B', 30);
		$pdf->SetTextColor(0, 128, 0);
		$pdf->Cell(0, 20, 'SIGNED', 0, 1, 'C');
		$pdf->SetFont('Arial', '', 12);
		$pdf->MultiCell(0, 8, 'This is a placeholder e-signature for MVP.');
		$pdf->Output('F', $path);
		if (!file_exists($path)) {
			throw new \RuntimeException('Failed to create signed PDF at ' . $path);
		}
		return [ 'filename' => $filename, 'path' => $path ];
	}
}


