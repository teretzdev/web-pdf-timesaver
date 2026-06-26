<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../mvp/lib/fill_service.php';
require __DIR__ . '/../mvp/templates/registry.php';

use WebPdfTimeSaver\Mvp\FillService;
use WebPdfTimeSaver\Mvp\TemplateRegistry;
use Smalot\PdfParser\Parser;

final class PdfVisualStampSigningTest extends PHPUnit\Framework\TestCase {
    public function testStampSignedAddsMarker(): void {
        $templates = TemplateRegistry::load();
        $tpl = reset($templates);
        $service = new FillService(__DIR__ . '/../output');
        $gen = $service->generateSimplePdf($tpl ?: [], [ 'petitioner.name' => 'ALICE TEST' ]);
        $this->assertFileExists($gen['path']);

        $signed = $service->stampSigned($gen['path']);
        $this->assertFileExists($signed['path']);
        $this->assertGreaterThan(filesize($gen['path']), filesize($signed['path']));

        $parser = new Parser();
        $pdf = $parser->parseFile($signed['path']);
        $text = $pdf->getText();
        $this->assertTrue(stripos($text, 'ELECTRONICALLY SIGNED') !== false);
    }
}


