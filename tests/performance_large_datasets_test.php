<?php
/**
 * Performance Tests for Large Datasets
 * Tests application performance with large amounts of data
 */

require_once __DIR__ . '/../lib/data.php';
require_once __DIR__ . '/../vendor/autoload.php';

class PerformanceLargeDatasetsTest extends PHPUnit\Framework\TestCase
{
    private $dataStore;
    private $testDataFile;
    private $largeDatasetSize = 1000; // Number of records to create
    
    protected function setUp(): void
    {
        // Create a test data file
        $this->testDataFile = __DIR__ . '/../data/test_performance.json';
        
        // Initialize with large dataset
        $this->createLargeDataset();
        $this->dataStore = new DataStore($this->testDataFile);
    }
    
    protected function tearDown(): void
    {
        if (file_exists($this->testDataFile)) {
            unlink($this->testDataFile);
        }
    }
    
    /**
     * Create a large dataset for performance testing
     */
    private function createLargeDataset()
    {
        $clients = [];
        $projects = [];
        $projectDocuments = [];
        $templates = [
            [
                'id' => 'template_1',
                'name' => 'Template 1',
                'code' => 'T1',
                'createdAt' => date(DATE_ATOM),
                'updatedAt' => date(DATE_ATOM)
            ],
            [
                'id' => 'template_2',
                'name' => 'Template 2',
                'code' => 'T2',
                'createdAt' => date(DATE_ATOM),
                'updatedAt' => date(DATE_ATOM)
            ]
        ];
        
        // Create large number of clients
        for ($i = 1; $i <= $this->largeDatasetSize; $i++) {
            $clients[] = [
                'id' => "client_$i",
                'displayName' => "Client $i",
                'email' => "client$i@example.com",
                'phone' => "555-" . str_pad($i, 4, '0', STR_PAD_LEFT),
                'status' => $i % 3 === 0 ? 'archived' : 'active',
                'createdAt' => date(DATE_ATOM, strtotime("-$i days")),
                'updatedAt' => date(DATE_ATOM, strtotime("-$i days"))
            ];
            
            // Create 2-5 projects per client
            $projectCount = rand(2, 5);
            for ($j = 1; $j <= $projectCount; $j++) {
                $projectId = "project_{$i}_{$j}";
                $projects[] = [
                    'id' => $projectId,
                    'name' => "Project $j for Client $i",
                    'clientId' => "client_$i",
                    'status' => ['in_progress', 'review', 'completed'][rand(0, 2)],
                    'createdAt' => date(DATE_ATOM, strtotime("-$i days")),
                    'updatedAt' => date(DATE_ATOM, strtotime("-$i days"))
                ];
                
                // Create 1-3 documents per project
                $documentCount = rand(1, 3);
                for ($k = 1; $k <= $documentCount; $k++) {
                    $projectDocuments[] = [
                        'id' => "document_{$i}_{$j}_{$k}",
                        'projectId' => $projectId,
                        'templateId' => $templates[rand(0, 1)]['id'],
                        'status' => ['in_progress', 'ready_to_sign', 'signed'][rand(0, 2)],
                        'createdAt' => date(DATE_ATOM, strtotime("-$i days")),
                        'updatedAt' => date(DATE_ATOM, strtotime("-$i days"))
                    ];
                }
            }
        }
        
        $testData = [
            'clients' => $clients,
            'projects' => $projects,
            'projectDocuments' => $projectDocuments,
            'templates' => $templates
        ];
        
        file_put_contents($this->testDataFile, json_encode($testData, JSON_PRETTY_PRINT));
    }
    
    /**
     * Test client list loading performance
     */
    public function testClientListLoadingPerformance()
    {
        $startTime = microtime(true);
        
        $clients = $this->dataStore->getClients();
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // Verify we got all clients
        $this->assertCount($this->largeDatasetSize, $clients);
        
        // Performance assertion: should load in under 1 second
        $this->assertLessThan(1.0, $executionTime, "Client list loading took {$executionTime}s, expected < 1.0s");
        
        echo "\nClient list loading: {$executionTime}s for {$this->largeDatasetSize} clients\n";
    }
    
    /**
     * Test client filtering performance
     */
    public function testClientFilteringPerformance()
    {
        $startTime = microtime(true);
        
        // Filter active clients
        $activeClients = array_filter($this->dataStore->getClients(), fn($c) => $c['status'] === 'active');
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // Verify filtering worked
        $expectedActiveCount = floor($this->largeDatasetSize * 2 / 3); // 2/3 should be active
        $this->assertGreaterThan($expectedActiveCount * 0.9, count($activeClients));
        $this->assertLessThan($expectedActiveCount * 1.1, count($activeClients));
        
        // Performance assertion: should filter in under 0.5 seconds
        $this->assertLessThan(0.5, $executionTime, "Client filtering took {$executionTime}s, expected < 0.5s");
        
        echo "\nClient filtering: {$executionTime}s for {$this->largeDatasetSize} clients\n";
    }
    
    /**
     * Test client search performance
     */
    public function testClientSearchPerformance()
    {
        $startTime = microtime(true);
        
        // Search for clients with "50" in the name
        $searchResults = array_filter($this->dataStore->getClients(), function($client) {
            return strpos($client['displayName'], '50') !== false;
        });
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // Verify search worked
        $this->assertGreaterThan(0, count($searchResults));
        
        // Performance assertion: should search in under 0.3 seconds
        $this->assertLessThan(0.3, $executionTime, "Client search took {$executionTime}s, expected < 0.3s");
        
        echo "\nClient search: {$executionTime}s for {$this->largeDatasetSize} clients\n";
    }
    
    /**
     * Test client sorting performance
     */
    public function testClientSortingPerformance()
    {
        $startTime = microtime(true);
        
        // Sort clients by name
        $clients = $this->dataStore->getClients();
        usort($clients, function($a, $b) {
            return strcasecmp($a['displayName'], $b['displayName']);
        });
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // Verify sorting worked
        $this->assertCount($this->largeDatasetSize, $clients);
        $this->assertEquals('Client 1', $clients[0]['displayName']);
        
        // Performance assertion: should sort in under 0.5 seconds
        $this->assertLessThan(0.5, $executionTime, "Client sorting took {$executionTime}s, expected < 0.5s");
        
        echo "\nClient sorting: {$executionTime}s for {$this->largeDatasetSize} clients\n";
    }
    
    /**
     * Test project loading performance
     */
    public function testProjectLoadingPerformance()
    {
        $startTime = microtime(true);
        
        $projects = $this->dataStore->getProjects();
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // Verify we got all projects
        $expectedProjectCount = $this->largeDatasetSize * 3.5; // Average 3.5 projects per client
        $this->assertGreaterThan($expectedProjectCount * 0.9, count($projects));
        $this->assertLessThan($expectedProjectCount * 1.1, count($projects));
        
        // Performance assertion: should load in under 2 seconds
        $this->assertLessThan(2.0, $executionTime, "Project loading took {$executionTime}s, expected < 2.0s");
        
        echo "\nProject loading: {$executionTime}s for " . count($projects) . " projects\n";
    }
    
    /**
     * Test project filtering by client performance
     */
    public function testProjectFilteringByClientPerformance()
    {
        $startTime = microtime(true);
        
        // Get projects for a specific client
        $clientId = 'client_500';
        $clientProjects = array_filter($this->dataStore->getProjects(), function($project) use ($clientId) {
            return $project['clientId'] === $clientId;
        });
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // Verify filtering worked
        $this->assertGreaterThan(0, count($clientProjects));
        
        // Performance assertion: should filter in under 0.5 seconds
        $this->assertLessThan(0.5, $executionTime, "Project filtering took {$executionTime}s, expected < 0.5s");
        
        echo "\nProject filtering: {$executionTime}s for client projects\n";
    }
    
    /**
     * Test document loading performance
     */
    public function testDocumentLoadingPerformance()
    {
        $startTime = microtime(true);
        
        $documents = $this->dataStore->getProjectDocuments('project_500_1');
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // Verify we got documents
        $this->assertGreaterThan(0, count($documents));
        
        // Performance assertion: should load in under 0.2 seconds
        $this->assertLessThan(0.2, $executionTime, "Document loading took {$executionTime}s, expected < 0.2s");
        
        echo "\nDocument loading: {$executionTime}s for project documents\n";
    }
    
    /**
     * Test client creation performance with large dataset
     */
    public function testClientCreationPerformance()
    {
        $startTime = microtime(true);
        
        // Create a new client
        $newClient = [
            'id' => 'new_client_' . time(),
            'displayName' => 'New Performance Test Client',
            'email' => 'performance@example.com',
            'phone' => '555-9999',
            'status' => 'active',
            'createdAt' => date(DATE_ATOM),
            'updatedAt' => date(DATE_ATOM)
        ];
        
        $this->dataStore->createClient($newClient);
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // Verify client was created
        $createdClient = $this->dataStore->getClient($newClient['id']);
        $this->assertNotNull($createdClient);
        
        // Performance assertion: should create in under 0.5 seconds
        $this->assertLessThan(0.5, $executionTime, "Client creation took {$executionTime}s, expected < 0.5s");
        
        echo "\nClient creation: {$executionTime}s with large dataset\n";
    }
    
    /**
     * Test client update performance with large dataset
     */
    public function testClientUpdatePerformance()
    {
        $startTime = microtime(true);
        
        // Update a client status
        $this->dataStore->updateClientStatus('client_500', 'archived');
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // Verify update worked
        $updatedClient = $this->dataStore->getClient('client_500');
        $this->assertEquals('archived', $updatedClient['status']);
        
        // Performance assertion: should update in under 0.3 seconds
        $this->assertLessThan(0.3, $executionTime, "Client update took {$executionTime}s, expected < 0.3s");
        
        echo "\nClient update: {$executionTime}s with large dataset\n";
    }
    
    /**
     * Test client deletion performance with large dataset
     */
    public function testClientDeletionPerformance()
    {
        $startTime = microtime(true);
        
        // Delete a client (should cascade to projects and documents)
        $this->dataStore->deleteClient('client_999');
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // Verify deletion worked
        $deletedClient = $this->dataStore->getClient('client_999');
        $this->assertNull($deletedClient);
        
        // Performance assertion: should delete in under 1 second
        $this->assertLessThan(1.0, $executionTime, "Client deletion took {$executionTime}s, expected < 1.0s");
        
        echo "\nClient deletion: {$executionTime}s with large dataset\n";
    }
    
    /**
     * Test memory usage with large dataset
     */
    public function testMemoryUsageWithLargeDataset()
    {
        $initialMemory = memory_get_usage();
        
        // Load all data
        $clients = $this->dataStore->getClients();
        $projects = $this->dataStore->getProjects();
        
        $peakMemory = memory_get_peak_usage();
        $memoryUsed = $peakMemory - $initialMemory;
        $memoryUsedMB = $memoryUsed / 1024 / 1024;
        
        // Memory assertion: should use less than 50MB
        $this->assertLessThan(50 * 1024 * 1024, $memoryUsed, "Memory usage: {$memoryUsedMB}MB, expected < 50MB");
        
        echo "\nMemory usage: {$memoryUsedMB}MB for large dataset\n";
    }
    
    /**
     * Test concurrent operations performance
     */
    public function testConcurrentOperationsPerformance()
    {
        $startTime = microtime(true);
        
        // Simulate concurrent operations
        $operations = [];
        for ($i = 0; $i < 10; $i++) {
            $clientId = "client_" . rand(1, 100);
            $operations[] = function() use ($clientId) {
                return $this->dataStore->getClient($clientId);
            };
        }
        
        // Execute operations
        $results = [];
        foreach ($operations as $operation) {
            $results[] = $operation();
        }
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // Verify all operations completed
        $this->assertCount(10, $results);
        
        // Performance assertion: should complete in under 0.5 seconds
        $this->assertLessThan(0.5, $executionTime, "Concurrent operations took {$executionTime}s, expected < 0.5s");
        
        echo "\nConcurrent operations: {$executionTime}s for 10 operations\n";
    }
    
    /**
     * Test pagination performance (if implemented)
     */
    public function testPaginationPerformance()
    {
        $startTime = microtime(true);
        
        // Simulate pagination by getting a subset of clients
        $allClients = $this->dataStore->getClients();
        $pageSize = 50;
        $page = 5;
        $offset = ($page - 1) * $pageSize;
        $paginatedClients = array_slice($allClients, $offset, $pageSize);
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // Verify pagination worked
        $this->assertCount($pageSize, $paginatedClients);
        
        // Performance assertion: should paginate in under 0.2 seconds
        $this->assertLessThan(0.2, $executionTime, "Pagination took {$executionTime}s, expected < 0.2s");
        
        echo "\nPagination: {$executionTime}s for page 5 of 50 items\n";
    }
    
    /**
     * Test data file size and loading time
     */
    public function testDataFileSizeAndLoadingTime()
    {
        $fileSize = filesize($this->testDataFile);
        $fileSizeMB = $fileSize / 1024 / 1024;
        
        $startTime = microtime(true);
        
        // Load the entire file
        $fileContents = file_get_contents($this->testDataFile);
        $data = json_decode($fileContents, true);
        
        $endTime = microtime(true);
        $loadingTime = $endTime - $startTime;
        
        // Verify data loaded correctly
        $this->assertIsArray($data);
        $this->assertArrayHasKey('clients', $data);
        $this->assertArrayHasKey('projects', $data);
        
        // Performance assertions
        $this->assertLessThan(2.0, $loadingTime, "File loading took {$loadingTime}s, expected < 2.0s");
        $this->assertLessThan(10 * 1024 * 1024, $fileSize, "File size: {$fileSizeMB}MB, expected < 10MB");
        
        echo "\nData file: {$fileSizeMB}MB, loading time: {$loadingTime}s\n";
    }
}













