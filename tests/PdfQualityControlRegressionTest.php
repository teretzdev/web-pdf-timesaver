<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use WebPdfTimeSaver\Mvp\FillService;
use WebPdfTimeSaver\Mvp\TemplateRegistry;
use setasign\Fpdi\Fpdi;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../mvp/lib/fill_service.php';
require_once __DIR__ . '/../mvp/templates/registry.php';

final class PdfQualityControlRegressionTest extends TestCase {
	public function test_fl100_generation_has_background_and_reasonable_size(): void {
		$templates = TemplateRegistry::load();
		$tpl = $templates['t_fl100_gc120'];
		$values = [
			'attorney_name' => 'John Michael Smith, Esq.',
			'attorney_firm' => 'Smith & Associates Law Firm',
			'attorney_address' => '123 Main Street, Suite 100',
			'attorney_city_state_zip' => 'Los Angeles, CA 90210',
			'attorney_phone' => '(555) 123-4567',
			'attorney_email' => 'jsmith@smithlaw.com',
			'attorney_bar_number' => '123456',
			'petitioner_name' => 'Sarah Elizabeth Johnson',
			'respondent_name' => 'Michael David Johnson',
			'case_number' => 'FL-2024-001234',
		];

		$service = new FillService(__DIR__ . '/../output');
		$result = $service->generateSimplePdf($tpl, $values);
		$this->assertArrayHasKey('path', $result);
		$this->assertFileExists($result['path']);

		$size = filesize($result['path']) ?: 0;
		$this->assertGreaterThan(10000, $size, 'Generated PDF size too small - background likely missing');

		$pages = null;
		try {
			$probe = new Fpdi();
			$pages = $probe->setSourceFile($result['path']);
		} catch (\Throwable $e) {
			$pages = null;
		}
		$this->assertNotNull($pages, 'Unable to read generated PDF');
		$this->assertGreaterThanOrEqual(1, (int)$pages, 'Generated PDF has no pages');

		$gs = $this->findGhostscriptBinary();
		if ($gs === null) {
			$this->markTestSkipped('Ghostscript not found; skipping per-page background raster checks');
		}
		for ($i = 1; $i <= (int)$pages; $i++) {
			$tmpPng = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'qc_page_' . uniqid() . '.png';
			$cmd = '"' . $gs . '" -dSAFER -dNOPAUSE -dBATCH -sDEVICE=png16m -r150 -dFirstPage=' . $i . ' -dLastPage=' . $i . ' -sOutputFile="' . $tmpPng . '" "' . $result['path'] . '" 2>&1';
			$out = [];
			$rc = 0;
			exec($cmd, $out, $rc);
			$this->assertEquals(0, $rc, 'Ghostscript failed for page ' . $i . ': ' . implode(' ', $out));
			$this->assertFileExists($tmpPng, 'Rasterized PNG missing for page ' . $i);
			$pngSize = filesize($tmpPng) ?: 0;
			@unlink($tmpPng);
			$this->assertGreaterThan(5000, $pngSize, 'Rasterized page appears blank/suspiciously small: page ' . $i . ' size=' . $pngSize . ' bytes');
		}

		$logPath = __DIR__ . '/../logs/pdf_debug.log';
		$this->assertFileExists($logPath, 'QC log not found');
		$log = file_get_contents($logPath) ?: '';
		$markers = [
			'Background image applied for page 1',
			'Page 1 background applied',
			'Drawn layout applied for page 1',
			'FL-100 template used as background'
		];
		$found = false;
		foreach ($markers as $m) {
			if (strpos($log, $m) !== false) { $found = true; break; }
		}
		$this->assertTrue($found, 'QC log does not show any background application markers');
	}

	private function findGhostscriptBinary(): ?string {
		$candidates = [
			__DIR__ . '/../gs1000w64.exe',
			'gswin64c',
			'gswin32c',
			'gs',
		];
		foreach ($candidates as $bin) {
			$cmd = strpos($bin, DIRECTORY_SEPARATOR) !== false ? '"' . $bin . '" -v 2>&1' : $bin . ' -v 2>&1';
			$out = [];
			$rc = 0;
			@exec($cmd, $out, $rc);
			if ($rc === 0) { return $bin; }
		}
		return null;
	}
}





