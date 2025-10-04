<?php
/**
 * Integration Tests for Client-Project Workflows
 * Tests the complete user journey from client creation to project management
 */

require_once __DIR__ . '/../mvp/lib/data.php';
require_once __DIR__ . '/../vendor/autoload.php';

class ClientProjectWorkflowTest extends PHPUnit\Framework\TestCase
{
    private $dataStore;
    private $testDataFile;
    
    protected function setUp(): void
    {
        // Create a test data file
        $this->testDataFile = __DIR__ . '/../data/test_integration.json';
        
        // Initialize test data
        $testData = [
            'clients' => [],
            'projects' => [],
            'projectDocuments' => [],
            'templates' => [
                [
                    'id' => 'test_template_1',
                    'name' => 'Test Template 1',
                    'code' => 'TT1',
                    'createdAt' => date(DATE_ATOM),
                    'updatedAt' => date(DATE_ATOM)
                ]
            ]
        ];
        
        file_put_contents($this->testDataFile, json_encode($testData, JSON_PRETTY_PRINT));
        
        // Initialize data store with test file
        $this->dataStore = new DataStore($this->testDataFile);
    }
    
    protected function tearDown(): void
    {
        // Clean up test data file
        if (file_exists($this->testDataFile)) {
            unlink($this->testDataFile);
        }
    }
    
    /**
     * Test complete client creation workflow
     */
    public function testClientCreationWorkflow()
    {
        // Step 1: Create a new client
        $clientData = [
            'id' => 'test_client_1',
            'displayName' => 'Test Client 1',
            'email' => 'test@example.com',
            'phone' => '555-1234',
            'status' => 'active',
            'createdAt' => date(DATE_ATOM),
            'updatedAt' => date(DATE_ATOM)
        ];
        
        $this->dataStore->createClient($clientData);
        
        // Verify client was created
        $client = $this->dataStore->getClient('test_client_1');
        $this->assertNotNull($client);
        $this->assertEquals('Test Client 1', $client['displayName']);
        $this->assertEquals('test@example.com', $client['email']);
        $this->assertEquals('active', $client['status']);
        
        // Step 2: Verify client appears in clients list
        $clients = $this->dataStore->getClients();
        $this->assertCount(1, $clients);
        $this->assertEquals('Test Client 1', $clients[0]['displayName']);
        
        return $client;
    }
    
    /**
     * Test client status update workflow
     */
    public function testClientStatusUpdateWorkflow()
    {
        // Create a client first
        $client = $this->testClientCreationWorkflow();
        
        // Update client status to archived
        $this->dataStore->updateClientStatus($client['id'], 'archived');
        
        // Verify status was updated
        $updatedClient = $this->dataStore->getClient($client['id']);
        $this->assertEquals('archived', $updatedClient['status']);
        
        // Verify updatedAt timestamp was changed
        $this->assertNotEquals($client['updatedAt'], $updatedClient['updatedAt']);
    }
    
    /**
     * Test project creation workflow
     */
    public function testProjectCreationWorkflow()
    {
        // Create a client first
        $client = $this->testClientCreationWorkflow();
        
        // Step 1: Create a new project
        $projectData = [
            'id' => 'test_project_1',
            'name' => 'Test Project 1',
            'clientId' => $client['id'],
            'status' => 'in_progress',
            'createdAt' => date(DATE_ATOM),
            'updatedAt' => date(DATE_ATOM)
        ];
        
        $this->dataStore->createProject($projectData);
        
        // Verify project was created
        $project = $this->dataStore->getProject('test_project_1');
        $this->assertNotNull($project);
        $this->assertEquals('Test Project 1', $project['name']);
        $this->assertEquals($client['id'], $project['clientId']);
        $this->assertEquals('in_progress', $project['status']);
        
        // Step 2: Verify project appears in client's projects
        $clientProjects = $this->dataStore->getProjectsByClient($client['id']);
        $this->assertCount(1, $clientProjects);
        $this->assertEquals('Test Project 1', $clientProjects[0]['name']);
        
        return $project;
    }
    
    /**
     * Test project status update workflow
     */
    public function testProjectStatusUpdateWorkflow()
    {
        // Create a project first
        $project = $this->testProjectCreationWorkflow();
        
        // Update project status to review
        $this->dataStore->updateProjectStatus($project['id'], 'review');
        
        // Verify status was updated
        $updatedProject = $this->dataStore->getProject($project['id']);
        $this->assertEquals('review', $updatedProject['status']);
        
        // Verify updatedAt timestamp was changed
        $this->assertNotEquals($project['updatedAt'], $updatedProject['updatedAt']);
    }
    
    /**
     * Test project name update workflow
     */
    public function testProjectNameUpdateWorkflow()
    {
        // Create a project first
        $project = $this->testProjectCreationWorkflow();
        
        // Update project name
        $newName = 'Updated Project Name';
        $this->dataStore->updateProjectName($project['id'], $newName);
        
        // Verify name was updated
        $updatedProject = $this->dataStore->getProject($project['id']);
        $this->assertEquals($newName, $updatedProject['name']);
        
        // Verify updatedAt timestamp was changed
        $this->assertNotEquals($project['updatedAt'], $updatedProject['updatedAt']);
    }
    
    /**
     * Test project duplication workflow
     */
    public function testProjectDuplicationWorkflow()
    {
        // Create a project first
        $originalProject = $this->testProjectCreationWorkflow();
        
        // Duplicate the project
        $duplicatedProject = $this->dataStore->duplicateProject($originalProject['id']);
        
        // Verify duplicated project was created
        $this->assertNotNull($duplicatedProject);
        $this->assertNotEquals($originalProject['id'], $duplicatedProject['id']);
        $this->assertEquals($originalProject['name'] . ' (Copy)', $duplicatedProject['name']);
        $this->assertEquals($originalProject['clientId'], $duplicatedProject['clientId']);
        $this->assertEquals('in_progress', $duplicatedProject['status']);
        
        // Verify both projects exist for the client
        $clientProjects = $this->dataStore->getProjectsByClient($originalProject['clientId']);
        $this->assertCount(2, $clientProjects);
    }
    
    /**
     * Test document addition to project workflow
     */
    public function testDocumentAdditionWorkflow()
    {
        // Create a project first
        $project = $this->testProjectCreationWorkflow();
        
        // Add a document to the project
        $documentData = [
            'id' => 'test_document_1',
            'projectId' => $project['id'],
            'templateId' => 'test_template_1',
            'status' => 'in_progress',
            'createdAt' => date(DATE_ATOM),
            'updatedAt' => date(DATE_ATOM)
        ];
        
        $this->dataStore->addDocumentToProject($documentData);
        
        // Verify document was added
        $projectDocuments = $this->dataStore->getProjectDocuments($project['id']);
        $this->assertCount(1, $projectDocuments);
        $this->assertEquals('test_document_1', $projectDocuments[0]['id']);
        $this->assertEquals($project['id'], $projectDocuments[0]['projectId']);
    }
    
    /**
     * Test client deletion workflow (should cascade to projects and documents)
     */
    public function testClientDeletionCascadeWorkflow()
    {
        // Create a client with project and document
        $client = $this->testClientCreationWorkflow();
        $project = $this->testProjectCreationWorkflow();
        $this->testDocumentAdditionWorkflow();
        
        // Verify we have data before deletion
        $this->assertCount(1, $this->dataStore->getClients());
        $this->assertCount(1, $this->dataStore->getProjects());
        $this->assertCount(1, $this->dataStore->getProjectDocuments($project['id']));
        
        // Delete the client
        $this->dataStore->deleteClient($client['id']);
        
        // Verify client was deleted
        $this->assertNull($this->dataStore->getClient($client['id']));
        $this->assertCount(0, $this->dataStore->getClients());
        
        // Verify associated project was deleted
        $this->assertNull($this->dataStore->getProject($project['id']));
        $this->assertCount(0, $this->dataStore->getProjects());
        
        // Verify associated documents were deleted
        $this->assertCount(0, $this->dataStore->getProjectDocuments($project['id']));
    }
    
    /**
     * Test project deletion workflow (should cascade to documents)
     */
    public function testProjectDeletionCascadeWorkflow()
    {
        // Create a project with document
        $project = $this->testProjectCreationWorkflow();
        $this->testDocumentAdditionWorkflow();
        
        // Verify we have data before deletion
        $this->assertCount(1, $this->dataStore->getProjects());
        $this->assertCount(1, $this->dataStore->getProjectDocuments($project['id']));
        
        // Delete the project
        $this->dataStore->deleteProject($project['id']);
        
        // Verify project was deleted
        $this->assertNull($this->dataStore->getProject($project['id']));
        $this->assertCount(0, $this->dataStore->getProjects());
        
        // Verify associated documents were deleted
        $this->assertCount(0, $this->dataStore->getProjectDocuments($project['id']));
    }
    
    /**
     * Test client search functionality
     */
    public function testClientSearchWorkflow()
    {
        // Create multiple clients
        $clients = [
            ['id' => 'client1', 'displayName' => 'John Doe', 'email' => 'john@example.com'],
            ['id' => 'client2', 'displayName' => 'Jane Smith', 'email' => 'jane@example.com'],
            ['id' => 'client3', 'displayName' => 'Bob Johnson', 'email' => 'bob@example.com']
        ];
        
        foreach ($clients as $clientData) {
            $clientData['status'] = 'active';
            $clientData['createdAt'] = date(DATE_ATOM);
            $clientData['updatedAt'] = date(DATE_ATOM);
            $this->dataStore->createClient($clientData);
        }
        
        // Test search by name
        $searchResults = $this->dataStore->searchClients('John');
        $this->assertCount(1, $searchResults);
        $this->assertEquals('John Doe', $searchResults[0]['displayName']);
        
        // Test search by email
        $searchResults = $this->dataStore->searchClients('jane@example.com');
        $this->assertCount(1, $searchResults);
        $this->assertEquals('Jane Smith', $searchResults[0]['displayName']);
        
        // Test case-insensitive search
        $searchResults = $this->dataStore->searchClients('BOB');
        $this->assertCount(1, $searchResults);
        $this->assertEquals('Bob Johnson', $searchResults[0]['displayName']);
    }
    
    /**
     * Test client sorting functionality
     */
    public function testClientSortingWorkflow()
    {
        // Create multiple clients with different names and creation dates
        $clients = [
            ['id' => 'client1', 'displayName' => 'Charlie Brown', 'createdAt' => '2023-01-01T00:00:00Z'],
            ['id' => 'client2', 'displayName' => 'Alice Wonder', 'createdAt' => '2023-01-02T00:00:00Z'],
            ['id' => 'client3', 'displayName' => 'Bob Builder', 'createdAt' => '2023-01-03T00:00:00Z']
        ];
        
        foreach ($clients as $clientData) {
            $clientData['status'] = 'active';
            $clientData['updatedAt'] = date(DATE_ATOM);
            $this->dataStore->createClient($clientData);
        }
        
        // Test sorting by name ascending
        $sortedClients = $this->dataStore->getClientsSorted('name_asc');
        $this->assertEquals('Alice Wonder', $sortedClients[0]['displayName']);
        $this->assertEquals('Bob Builder', $sortedClients[1]['displayName']);
        $this->assertEquals('Charlie Brown', $sortedClients[2]['displayName']);
        
        // Test sorting by name descending
        $sortedClients = $this->dataStore->getClientsSorted('name_desc');
        $this->assertEquals('Charlie Brown', $sortedClients[0]['displayName']);
        $this->assertEquals('Bob Builder', $sortedClients[1]['displayName']);
        $this->assertEquals('Alice Wonder', $sortedClients[2]['displayName']);
        
        // Test sorting by creation date ascending
        $sortedClients = $this->dataStore->getClientsSorted('created_asc');
        $this->assertEquals('Charlie Brown', $sortedClients[0]['displayName']);
        $this->assertEquals('Alice Wonder', $sortedClients[1]['displayName']);
        $this->assertEquals('Bob Builder', $sortedClients[2]['displayName']);
    }
    
    /**
     * Test complete end-to-end workflow
     */
    public function testCompleteEndToEndWorkflow()
    {
        // Step 1: Create a client
        $client = $this->testClientCreationWorkflow();
        
        // Step 2: Create multiple projects for the client
        $project1 = $this->testProjectCreationWorkflow();
        $project2 = $this->dataStore->createProject([
            'id' => 'test_project_2',
            'name' => 'Test Project 2',
            'clientId' => $client['id'],
            'status' => 'review',
            'createdAt' => date(DATE_ATOM),
            'updatedAt' => date(DATE_ATOM)
        ]);
        
        // Step 3: Add documents to projects
        $this->testDocumentAdditionWorkflow();
        $this->dataStore->addDocumentToProject([
            'id' => 'test_document_2',
            'projectId' => $project2['id'],
            'templateId' => 'test_template_1',
            'status' => 'ready_to_sign',
            'createdAt' => date(DATE_ATOM),
            'updatedAt' => date(DATE_ATOM)
        ]);
        
        // Step 4: Update project statuses
        $this->dataStore->updateProjectStatus($project1['id'], 'completed');
        $this->dataStore->updateProjectStatus($project2['id'], 'completed');
        
        // Step 5: Verify final state
        $finalClient = $this->dataStore->getClient($client['id']);
        $this->assertNotNull($finalClient);
        
        $clientProjects = $this->dataStore->getProjectsByClient($client['id']);
        $this->assertCount(2, $clientProjects);
        
        $allCompleted = true;
        foreach ($clientProjects as $project) {
            if ($project['status'] !== 'completed') {
                $allCompleted = false;
                break;
            }
        }
        $this->assertTrue($allCompleted, 'All projects should be completed');
        
        // Step 6: Test project counts in client view
        $projectCount = count($clientProjects);
        $this->assertEquals(2, $projectCount);
        
        // Step 7: Test client status filtering
        $activeClients = array_filter($this->dataStore->getClients(), fn($c) => $c['status'] === 'active');
        $this->assertCount(1, $activeClients);
        
        // Step 8: Archive client
        $this->dataStore->updateClientStatus($client['id'], 'archived');
        $archivedClients = array_filter($this->dataStore->getClients(), fn($c) => $c['status'] === 'archived');
        $this->assertCount(1, $archivedClients);
        
        $activeClients = array_filter($this->dataStore->getClients(), fn($c) => $c['status'] === 'active');
        $this->assertCount(0, $activeClients);
    }
}













