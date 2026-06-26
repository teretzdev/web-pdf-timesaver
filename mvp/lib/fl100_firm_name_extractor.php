<?php
declare(strict_types=1);

namespace WebPdfTimeSaver\Mvp;

use Smalot\PdfParser\Parser;

/**
 * Reads law-firm / attorney-firm text from FL-100 (and similar) PDFs using
 * extracted field metadata (pdf.js value) or AcroForm V entries via PdfParser.
 */
final class Fl100FirmNameExtractor {
	/**
	 * @param array<string, array<string, mixed>> $keyedFields Field key => metadata from extraction pipeline
	 */
	public static function extractFromKeyedFields(array $keyedFields, ?string $pdfPath = null): string {
		foreach ($keyedFields as $key => $meta) {
			if (!is_array($meta)) {
				continue;
			}
			$names = [
				(string)$key,
				(string)($meta['name'] ?? ''),
				(string)($meta['canonicalName'] ?? ''),
				(string)($meta['originalName'] ?? ''),
			];
			if (!self::nameReferencesFirmField($names)) {
				continue;
			}
			$raw = $meta['value'] ?? $meta['defaultValue'] ?? $meta['fieldValue'] ?? '';
			$norm = self::normalizeScalar($raw);
			if ($norm !== '') {
				return $norm;
			}
		}

		if ($pdfPath !== null && $pdfPath !== '' && is_readable($pdfPath)) {
			$fromPdf = self::extractFromPdfAcroform($pdfPath);
			if ($fromPdf !== '') {
				return $fromPdf;
			}
		}

		return '';
	}

	/**
	 * @param list<string> $names
	 */
	private static function nameReferencesFirmField(array $names): bool {
		foreach ($names as $n) {
			if ($n === '') {
				continue;
			}
			if (preg_match('/attyfirm|firmname|firm_name|attorney\s*firm|attorney_firm/i', $n)) {
				return true;
			}
		}
		return false;
	}

	private static function normalizeScalar(mixed $raw): string {
		if ($raw === null) {
			return '';
		}
		if (is_bool($raw)) {
			return '';
		}
		$s = trim((string)$raw);
		if ($s === '') {
			return '';
		}
		if (strlen($s) > 512) {
			$s = substr($s, 0, 512);
		}
		return $s;
	}

	private static function extractFromPdfAcroform(string $pdfPath): string {
		try {
			$parser = new Parser();
			$pdf = $parser->parseFile($pdfPath);
			foreach ($pdf->getPages() as $page) {
				$annots = $page->get('Annots');
				if (!$annots) {
					continue;
				}
				$content = $annots->getContent();
				if (!is_array($content)) {
					continue;
				}
				foreach ($content as $annot) {
					if (!is_object($annot) || !method_exists($annot, 'get')) {
						continue;
					}
					$t = $annot->get('T');
					if (!$t || !method_exists($t, 'getContent')) {
						continue;
					}
					$fieldName = (string)$t->getContent();
					if (!self::nameReferencesFirmField([$fieldName])) {
						continue;
					}
					$v = $annot->get('V');
					if (!$v || !method_exists($v, 'getContent')) {
						continue;
					}
					$val = $v->getContent();
					$norm = self::normalizeScalar($val);
					if ($norm !== '') {
						return $norm;
					}
				}
			}
		} catch (\Throwable $e) {
			error_log('Fl100FirmNameExtractor: PDF parse failed: ' . $e->getMessage());
		}
		return '';
	}
}
