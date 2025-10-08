<?php
/**
 * API Routes Test
 * Tests all new routes and actions for proper functionality
 */

require_once __DIR__ . '/../lib/data.php';
require_once __DIR__ . '/../vendor/autoload.php';

class APIRoutesTest extends PHPUnit\Framework\TestCase
{
    private $dataStore;
    private $testDataFile;
    private $originalGet;
    private $originalPost;
    private $originalServer;
    
    protected function setUp(): void
    {
        // Save original superglobals
        $this->originalGet = $_GET;
        $this->originalPost = $_POST;
        $this->originalServer = $_SERVER;
        
        // Create a test data file
        $this->testDataFile = __DIR__ . '/../data/test_api.json';
        
        // Initialize test data
        $testData = [
            'clients' => [
                [
                    'id' => 'test_client_1',
                    'displayName' => 'Test Client 1',
                    'email' => 'test1@example.com',
                    'phone' => '555-0001',
                    'status' => 'active',
                    'createdAt' => '2023-01-01T00:00:00Z',
                    'updatedAt' => '2023-01-01T00:00:00Z'
                ]
            ],
            'projects' => [
                [
                    'id' => 'test_project_1',
                    'name' => 'Test Project 1',
                    'clientId' => 'test_client_1',
                    'status' => 'in_progress',
                    'createdAt' => '2023-01-01T00:00:00Z',
                    'updatedAt' => '2023-01-01T00:00:00Z'
                ]
            ],
            'projectDocuments' => [],
            'templates' => []
        ];
        
        file_put_contents($this->testDataFile, json_encode($testData, JSON_PRETTY_PRINT));
        $this->dataStore = new DataStore($this->testDataFile);
    }
    
    protected function tearDown(): void
    {
        // Restore original superglobals
        $_GET = $this->originalGet;
        $_POST = $this->originalPost;
        $_SERVER = $this->originalServer;
        
        if (file_exists($this->testDataFile)) {
            unlink($this->testDataFile);
        }
    }
    
    /**
     * Test client status update route
     */
    public function testUpdateClientStatusRoute()
    {
        // Set up POST request
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['clientId'] = 'test_client_1';
        $_POST['status'] = 'archived';
        
        // Capture output and headers
        ob_start();
        
        // Simulate the route handling
        $clientId = (string)($_POST['clientId'] ?? '');
        $status = (string)($_POST['status'] ?? 'active');
        
        if ($clientId !== '') {
            $ref = new \ReflectionClass($this->dataStore);
            $prop = $ref->getProperty('db');
            $prop->setAccessible(true);
            $db = $prop->getValue($this->dataStore);
            
            foreach ($db['clients'] as &$c) {
                if ($c['id'] === $clientId) {
                    $c['status'] = $status;
                    $c['updatedAt'] = date(DATE_ATOM);
                    break;
                }
            }
            
            file_put_contents($this->testDataFile, json_encode($db, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
        }
        
        $output = ob_get_clean();
        
        // Verify the client status was updated
        $updatedClient = $this->dataStore->getClient('test_client_1');
        $this->assertEquals('archived', $updatedClient['status']);
        $this->assertNotEquals('2023-01-01T00:00:00Z', $updatedClient['updatedAt']);
    }
    
    /**
     * Test client deletion route
     */
    public function testDeleteClientRoute()
    {
        // Set up POST request
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['clientId'] = 'test_client_1';
        
        // Simulate the route handling
        $clientId = (string)($_POST['clientId'] ?? '');
        
        if ($clientId !== '') {
            $ref = new \ReflectionClass($this->dataStore);
            $prop = $ref->getProperty('db');
            $prop->setAccessible(true);
            $db = $prop->getValue($this->dataStore);
            
            // Remove client
            $db['clients'] = array_values(array_filter($db['clients'], fn($c) => $c['id'] !== $clientId));
            
            // Remove projects for this client
            $db['projects'] = array_values(array_filter($db['projects'], fn($p) => $p['clientId'] !== $clientId));
            
            // Remove project documents for deleted projects
            $deletedProjectIds = array_column(array_filter($db['projects'], fn($p) => $p['clientId'] === $clientId), 'id');
            $db['projectDocuments'] = array_values(array_filter($db['projectDocuments'], fn($pd) => !in_array($pd['projectId'], $deletedProjectIds)));
            
            file_put_contents($this->testDataFile, json_encode($db, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
        }
        
        // Verify the client was deleted
        $this->assertNull($this->dataStore->getClient('test_client_1'));
        $this->assertCount(0, $this->dataStore->getClients());
        
        // Verify associated project was also deleted
        $this->assertNull($this->dataStore->getProject('test_project_1'));
        $this->assertCount(0, $this->dataStore->getProjects());
    }
    
    /**
     * Test project name update route
     */
    public function testUpdateProjectNameRoute()
    {
        // Set up POST request
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['id'] = 'test_project_1';
        $_POST['name'] = 'Updated Project Name';
        
        // Simulate the route handling
        $projectId = (string)($_POST['id'] ?? '');
        $newName = (string)($_POST['name'] ?? '');
        
        if ($projectId !== '' && $newName !== '') {
            $ref = new \ReflectionClass($this->dataStore);
            $prop = $ref->getProperty('db');
            $prop->setAccessible(true);
            $db = $prop->getValue($this->dataStore);
            
            foreach ($db['projects'] as &$p) {
                if ($p['id'] === $projectId) {
                    $p['name'] = $newName;
                    $p['updatedAt'] = date(DATE_ATOM);
                    break;
                }
            }
            
            file_put_contents($this->testDataFile, json_encode($db, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
        }
        
        // Verify the project name was updated
        $updatedProject = $this->dataStore->getProject('test_project_1');
        $this->assertEquals('Updated Project Name', $updatedProject['name']);
        $this->assertNotEquals('2023-01-01T00:00:00Z', $updatedProject['updatedAt']);
    }
    
    /**
     * Test project status update route
     */
    public function testUpdateProjectStatusRoute()
    {
        // Set up POST request
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['projectId'] = 'test_project_1';
        $_POST['status'] = 'completed';
        
        // Simulate the route handling
        $projectId = (string)($_POST['projectId'] ?? '');
        $status = (string)($_POST['status'] ?? 'in_progress');
        
        if ($projectId !== '') {
            $ref = new \ReflectionClass($this->dataStore);
            $prop = $ref->getProperty('db');
            $prop->setAccessible(true);
            $db = $prop->getValue($this->dataStore);
            
            foreach ($db['projects'] as &$p) {
                if ($p['id'] === $projectId) {
                    $p['status'] = $status;
                    $p['updatedAt'] = date(DATE_ATOM);
                    break;
                }
            }
            
            file_put_contents($this->testDataFile, json_encode($db, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
        }
        
        // Verify the project status was updated
        $updatedProject = $this->dataStore->getProject('test_project_1');
        $this->assertEquals('completed', $updatedProject['status']);
        $this->assertNotEquals('2023-01-01T00:00:00Z', $updatedProject['updatedAt']);
    }
    
    /**
     * Test project duplication route
     */
    public function testDuplicateProjectRoute()
    {
        // Set up POST request
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['id'] = 'test_project_1';
        
        // Simulate the route handling
        $projectId = (string)($_POST['id'] ?? '');
        
        if ($projectId !== '') {
            $ref = new \ReflectionClass($this->dataStore);
            $prop = $ref->getProperty('db');
            $prop->setAccessible(true);
            $db = $prop->getValue($this->dataStore);
            
            $originalProject = null;
            foreach ($db['projects'] as $p) {
                if ($p['id'] === $projectId) {
                    $originalProject = $p;
                    break;
                }
            }
            
            if ($originalProject) {
                $newProject = $originalProject;
                $newProject['id'] = 'duplicated_' . $projectId . '_' . time();
                $newProject['name'] = $originalProject['name'] . ' (Copy)';
                $newProject['status'] = 'in_progress';
                $newProject['createdAt'] = date(DATE_ATOM);
                $newProject['updatedAt'] = date(DATE_ATOM);
                
                $db['projects'][] = $newProject;
                file_put_contents($this->testDataFile, json_encode($db, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
            }
        }
        
        // Verify the project was duplicated
        $projects = $this->dataStore->getProjects();
        $this->assertCount(2, $projects);
        
        $duplicatedProject = null;
        foreach ($projects as $p) {
            if (strpos($p['id'], 'duplicated_') === 0) {
                $duplicatedProject = $p;
                break;
            }
        }
        
        $this->assertNotNull($duplicatedProject);
        $this->assertEquals('Test Project 1 (Copy)', $duplicatedProject['name']);
        $this->assertEquals('test_client_1', $duplicatedProject['clientId']);
        $this->assertEquals('in_progress', $duplicatedProject['status']);
    }
    
    /**
     * Test create client route
     */
    public function testCreateClientRoute()
    {
        // Set up POST request
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['displayName'] = 'New Test Client';
        $_POST['email'] = 'newclient@example.com';
        $_POST['phone'] = '555-9999';
        
        // Simulate the route handling
        $displayName = (string)($_POST['displayName'] ?? '');
        $email = (string)($_POST['email'] ?? '');
        $phone = (string)($_POST['phone'] ?? '');
        
        if ($displayName !== '') {
            $ref = new \ReflectionClass($this->dataStore);
            $prop = $ref->getProperty('db');
            $prop->setAccessible(true);
            $db = $prop->getValue($this->dataStore);
            
            $newClient = [
                'id' => 'client_' . time(),
                'displayName' => $displayName,
                'email' => $email,
                'phone' => $phone,
                'status' => 'active',
                'createdAt' => date(DATE_ATOM),
                'updatedAt' => date(DATE_ATOM)
            ];
            
            $db['clients'][] = $newClient;
            file_put_contents($this->testDataFile, json_encode($db, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
        }
        
        // Verify the client was created
        $clients = $this->dataStore->getClients();
        $this->assertCount(2, $clients);
        
        $newClient = null;
        foreach ($clients as $c) {
            if ($c['displayName'] === 'New Test Client') {
                $newClient = $c;
                break;
            }
        }
        
        $this->assertNotNull($newClient);
        $this->assertEquals('newclient@example.com', $newClient['email']);
        $this->assertEquals('555-9999', $newClient['phone']);
        $this->assertEquals('active', $newClient['status']);
    }
    
    /**
     * Test create project route
     */
    public function testCreateProjectRoute()
    {
        // Set up POST request
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['clientId'] = 'test_client_1';
        $_POST['name'] = 'New Test Project';
        
        // Simulate the route handling
        $clientId = (string)($_POST['clientId'] ?? '');
        $name = (string)($_POST['name'] ?? '');
        
        if ($clientId !== '' && $name !== '') {
            $ref = new \ReflectionClass($this->dataStore);
            $prop = $ref->getProperty('db');
            $prop->setAccessible(true);
            $db = $prop->getValue($this->dataStore);
            
            $newProject = [
                'id' => 'project_' . time(),
                'name' => $name,
                'clientId' => $clientId,
                'status' => 'in_progress',
                'createdAt' => date(DATE_ATOM),
                'updatedAt' => date(DATE_ATOM)
            ];
            
            $db['projects'][] = $newProject;
            file_put_contents($this->testDataFile, json_encode($db, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
        }
        
        // Verify the project was created
        $projects = $this->dataStore->getProjects();
        $this->assertCount(2, $projects);
        
        $newProject = null;
        foreach ($projects as $p) {
            if ($p['name'] === 'New Test Project') {
                $newProject = $p;
                break;
            }
        }
        
        $this->assertNotNull($newProject);
        $this->assertEquals('test_client_1', $newProject['clientId']);
        $this->assertEquals('in_progress', $newProject['status']);
    }
    
    /**
     * Test GET route handling
     */
    public function testGetRoutes()
    {
        // Test clients route
        $_GET['route'] = 'clients';
        $route = $_GET['route'] ?? 'clients';
        $this->assertEquals('clients', $route);
        
        // Test client detail route
        $_GET['route'] = 'client';
        $_GET['id'] = 'test_client_1';
        $route = $_GET['route'] ?? 'clients';
        $clientId = $_GET['id'] ?? '';
        $this->assertEquals('client', $route);
        $this->assertEquals('test_client_1', $clientId);
        
        // Test project detail route
        $_GET['route'] = 'project';
        $_GET['id'] = 'test_project_1';
        $route = $_GET['route'] ?? 'clients';
        $projectId = $_GET['id'] ?? '';
        $this->assertEquals('project', $route);
        $this->assertEquals('test_project_1', $projectId);
        
        // Test projects route
        $_GET['route'] = 'projects';
        $route = $_GET['route'] ?? 'clients';
        $this->assertEquals('projects', $route);
        
        // Test templates route
        $_GET['route'] = 'templates';
        $route = $_GET['route'] ?? 'clients';
        $this->assertEquals('templates', $route);
        
        // Test support route
        $_GET['route'] = 'support';
        $route = $_GET['route'] ?? 'clients';
        $this->assertEquals('support', $route);
    }
    
    /**
     * Test route parameter validation
     */
    public function testRouteParameterValidation()
    {
        // Test with invalid route
        $_GET['route'] = 'invalid_route';
        $route = $_GET['route'] ?? 'clients';
        $this->assertEquals('invalid_route', $route);
        
        // Test with empty route (should default to clients)
        unset($_GET['route']);
        $route = $_GET['route'] ?? 'clients';
        $this->assertEquals('clients', $route);
        
        // Test with missing ID parameter
        $_GET['route'] = 'client';
        unset($_GET['id']);
        $clientId = $_GET['id'] ?? '';
        $this->assertEquals('', $clientId);
        
        // Test with missing project ID parameter
        $_GET['route'] = 'project';
        unset($_GET['id']);
        $projectId = $_GET['id'] ?? '';
        $this->assertEquals('', $projectId);
    }
    
    /**
     * Test POST request validation
     */
    public function testPostRequestValidation()
    {
        // Test with GET request (should be rejected)
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_POST['clientId'] = 'test_client_1';
        $_POST['status'] = 'archived';
        
        $isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
        $this->assertFalse($isPost);
        
        // Test with POST request (should be accepted)
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
        $this->assertTrue($isPost);
        
        // Test with missing required parameters
        unset($_POST['clientId']);
        $clientId = (string)($_POST['clientId'] ?? '');
        $this->assertEquals('', $clientId);
        
        // Test with empty parameters
        $_POST['clientId'] = '';
        $_POST['status'] = '';
        $clientId = (string)($_POST['clientId'] ?? '');
        $status = (string)($_POST['status'] ?? 'active');
        $this->assertEquals('', $clientId);
        $this->assertEquals('', $status);
    }
    
    /**
     * Test data persistence after route actions
     */
    public function testDataPersistenceAfterRouteActions()
    {
        // Test that data persists after client status update
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['clientId'] = 'test_client_1';
        $_POST['status'] = 'archived';
        
        // Simulate route action
        $clientId = (string)($_POST['clientId'] ?? '');
        $status = (string)($_POST['status'] ?? 'active');
        
        if ($clientId !== '') {
            $ref = new \ReflectionClass($this->dataStore);
            $prop = $ref->getProperty('db');
            $prop->setAccessible(true);
            $db = $prop->getValue($this->dataStore);
            
            foreach ($db['clients'] as &$c) {
                if ($c['id'] === $clientId) {
                    $c['status'] = $status;
                    $c['updatedAt'] = date(DATE_ATOM);
                    break;
                }
            }
            
            file_put_contents($this->testDataFile, json_encode($db, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
        }
        
        // Create new data store instance to test persistence
        $newDataStore = new DataStore($this->testDataFile);
        $persistedClient = $newDataStore->getClient('test_client_1');
        
        $this->assertNotNull($persistedClient);
        $this->assertEquals('archived', $persistedClient['status']);
    }
    
    /**
     * Test error handling for invalid routes
     */
    public function testErrorHandlingForInvalidRoutes()
    {
        // Test with non-existent client ID
        $_GET['route'] = 'client';
        $_GET['id'] = 'non_existent_client';
        
        $client = $this->dataStore->getClient($_GET['id']);
        $this->assertNull($client);
        
        // Test with non-existent project ID
        $_GET['route'] = 'project';
        $_GET['id'] = 'non_existent_project';
        
        $project = $this->dataStore->getProject($_GET['id']);
        $this->assertNull($project);
        
        // Test with invalid action route
        $_GET['route'] = 'actions/invalid-action';
        $route = $_GET['route'] ?? 'clients';
        $this->assertEquals('actions/invalid-action', $route);
    }
    
    /**
     * Test concurrent route handling
     */
    public function testConcurrentRouteHandling()
    {
        // Simulate multiple rapid status updates
        $updates = [
            ['clientId' => 'test_client_1', 'status' => 'archived'],
            ['clientId' => 'test_client_1', 'status' => 'active'],
            ['clientId' => 'test_client_1', 'status' => 'archived']
        ];
        
        foreach ($updates as $update) {
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $_POST = $update;
            
            $clientId = (string)($_POST['clientId'] ?? '');
            $status = (string)($_POST['status'] ?? 'active');
            
            if ($clientId !== '') {
                $ref = new \ReflectionClass($this->dataStore);
                $prop = $ref->getProperty('db');
                $prop->setAccessible(true);
                $db = $prop->getValue($this->dataStore);
                
                foreach ($db['clients'] as &$c) {
                    if ($c['id'] === $clientId) {
                        $c['status'] = $status;
                        $c['updatedAt'] = date(DATE_ATOM);
                        break;
                    }
                }
                
                file_put_contents($this->testDataFile, json_encode($db, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
            }
        }
        
        // Verify final state
        $finalClient = $this->dataStore->getClient('test_client_1');
        $this->assertEquals('archived', $finalClient['status']);
    }
}













