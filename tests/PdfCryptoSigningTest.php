<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../mvp/lib/fill_service.php';
require __DIR__ . '/../mvp/templates/registry.php';

use WebPdfTimeSaver\Mvp\FillService;
use WebPdfTimeSaver\Mvp\TemplateRegistry;

final class PdfCryptoSigningTest extends PHPUnit\Framework\TestCase {
    public function testDigitalSignatureWhenEnabled(): void {
        // Skip if mPDF not installed
        if (!class_exists('Mpdf\\Mpdf')) {
            $this->markTestSkipped('mPDF not installed');
        }
        $config = require __DIR__ . '/../config/app.php';
        if (empty($config['signing']['enabled'])) {
            $this->markTestSkipped('Digital signing disabled');
        }
        $cert = (string)($config['signing']['cert_p12_path'] ?? '');
        if (!$cert || !file_exists($cert)) {
            $this->markTestSkipped('Signing certificate not found');
        }

        $templates = TemplateRegistry::load();
        $tpl = reset($templates);
        $service = new FillService(__DIR__ . '/../output');
        $gen = $service->generateSimplePdf($tpl ?: [], [ 'petitioner.name' => 'CRYPTO TEST' ]);
        $this->assertFileExists($gen['path']);

        // Use the new signDocument which prefers digital
        $signed = $service->signDocument($gen['path']);
        $this->assertFileExists($signed['path']);
        $this->assertGreaterThan(0, filesize($signed['path']));

        // Basic check for signature objects in raw content
        $raw = file_get_contents($signed['path']);
        $this->assertTrue(strpos($raw, '/Sig') !== false || strpos($raw, '/AcroForm') !== false);
    }
}


