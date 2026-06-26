<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../mvp/lib/fill_service.php';
require __DIR__ . '/../mvp/templates/registry.php';

use WebPdfTimeSaver\Mvp\FillService;
use WebPdfTimeSaver\Mvp\TemplateRegistry;
use Smalot\PdfParser\Parser;

final class PdfGenerationContentTest extends PHPUnit\Framework\TestCase {
    public function testGeneratesPdfWithContent(): void {
        $templates = TemplateRegistry::load();
        $tpl = reset($templates);
        $values = [
            'attorney.name' => 'RON REITSHSTEIN',
            'attorney.firm' => 'YOUNGMAN REITSHSTEIN, PLC',
            'court.branch' => 'STANLEY MOSK COURTHOUSE',
            'petitioner.name' => 'JOHN DOE',
            'respondent.name' => 'JANE DOE'
        ];

        $service = new FillService(__DIR__ . '/../output');
        $result = $service->generateSimplePdf($tpl ?: [], $values);
        $this->assertArrayHasKey('path', $result);
        $this->assertFileExists($result['path']);
        $this->assertGreaterThan(1024, filesize($result['path']));

        $parser = new Parser();
        $pdf = $parser->parseFile($result['path']);
        $text = $pdf->getText();
        foreach ($values as $v) {
            $this->assertTrue(stripos($text, $v) !== false, 'Text not found in PDF: ' . $v);
        }
    }
}


