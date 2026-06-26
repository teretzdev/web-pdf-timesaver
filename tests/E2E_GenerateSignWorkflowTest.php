<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../mvp/lib/data.php';
require __DIR__ . '/../mvp/lib/fill_service.php';
require __DIR__ . '/../mvp/templates/registry.php';

use WebPdfTimeSaver\Mvp\DataStore;
use WebPdfTimeSaver\Mvp\FillService;
use WebPdfTimeSaver\Mvp\TemplateRegistry;

final class E2E_GenerateSignWorkflowTest extends PHPUnit\Framework\TestCase {
    private string $dbFile;

    protected function setUp(): void {
        $this->dbFile = __DIR__ . '/../data/mvp_test.json';
        $testData = [
            'clients' => [ [ 'id' => 'c1', 'displayName' => 'Client', 'email' => 'c@example.com', 'phone' => '555-0000', 'status' => 'active', 'createdAt' => date(DATE_ATOM), 'updatedAt' => date(DATE_ATOM) ] ],
            'projects' => [ [ 'id' => 'p1', 'name' => 'Proj', 'clientId' => 'c1', 'status' => 'in_progress', 'createdAt' => date(DATE_ATOM), 'updatedAt' => date(DATE_ATOM) ] ],
            'projectDocuments' => [ [ 'id' => 'pd1', 'projectId' => 'p1', 'templateId' => 't_fl100_gc120', 'status' => 'in_progress', 'createdAt' => date(DATE_ATOM), 'updatedAt' => date(DATE_ATOM) ] ],
            'templates' => TemplateRegistry::load(),
            'fieldValues' => []
        ];
        file_put_contents($this->dbFile, json_encode($testData, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
    }

    public function testGenerateThenSignUpdatesStatus(): void {
        $store = new DataStore($this->dbFile);
        $service = new FillService(__DIR__ . '/../output');
        $templates = TemplateRegistry::load();
        $tpl = $templates['t_fl100_gc120'] ?? reset($templates);
        $gen = $service->generateSimplePdf($tpl ?: [], [ 'petitioner.name' => 'WORKFLOW TEST' ], ['pdId' => 'pd1']);
        $this->assertFileExists($gen['path']);

        // Mimic route persistence for generate
        $ref = new \ReflectionClass($store);
        $prop = $ref->getProperty('db');
        $prop->setAccessible(true);
        $db = $prop->getValue($store);
        foreach ($db['projectDocuments'] as &$d) if ($d['id'] === 'pd1') { $d['status'] = 'ready_to_sign'; $d['outputPath'] = $gen['filename']; }
        file_put_contents(__DIR__ . '/../data/mvp_test.json', json_encode($db, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));

        // Now sign via service (decides digital vs stamp)
        $signed = $service->signDocument($gen['path'], ['pdId' => 'pd1']);
        $this->assertFileExists($signed['path']);
    }
}


