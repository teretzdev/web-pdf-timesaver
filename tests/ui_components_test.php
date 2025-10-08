<?php
/**
 * UI Components Test
 * Tests all new interface components and their interactions
 */

require_once __DIR__ . '/../lib/data.php';
require_once __DIR__ . '/../vendor/autoload.php';

class UIComponentsTest extends PHPUnit\Framework\TestCase
{
    private $dataStore;
    private $testDataFile;
    
    protected function setUp(): void
    {
        // Create a test data file
        $this->testDataFile = __DIR__ . '/../data/test_ui.json';
        
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
                ],
                [
                    'id' => 'test_client_2',
                    'displayName' => 'Test Client 2',
                    'email' => 'test2@example.com',
                    'phone' => '555-0002',
                    'status' => 'archived',
                    'createdAt' => '2023-01-02T00:00:00Z',
                    'updatedAt' => '2023-01-02T00:00:00Z'
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
                ],
                [
                    'id' => 'test_project_2',
                    'name' => 'Test Project 2',
                    'clientId' => 'test_client_1',
                    'status' => 'completed',
                    'createdAt' => '2023-01-02T00:00:00Z',
                    'updatedAt' => '2023-01-02T00:00:00Z'
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
        if (file_exists($this->testDataFile)) {
            unlink($this->testDataFile);
        }
    }
    
    /**
     * Test sidebar component rendering
     */
    public function testSidebarComponent()
    {
        // Capture output from sidebar
        ob_start();
        include __DIR__ . '/../views/sidebar.php';
        $sidebarOutput = ob_get_clean();
        
        // Test sidebar structure
        $this->assertStringContainsString('sidebar', $sidebarOutput);
        $this->assertStringContainsString('sidebar-header', $sidebarOutput);
        $this->assertStringContainsString('sidebar-nav', $sidebarOutput);
        $this->assertStringContainsString('nav-section', $sidebarOutput);
        
        // Test navigation links
        $this->assertStringContainsString('route=clients', $sidebarOutput);
        $this->assertStringContainsString('route=projects', $sidebarOutput);
        $this->assertStringContainsString('route=templates', $sidebarOutput);
        $this->assertStringContainsString('route=support', $sidebarOutput);
        
        // Test organization dropdown
        $this->assertStringContainsString('YOUNGMAN REITSHTEIN, PLC', $sidebarOutput);
        $this->assertStringContainsString('organization-item', $sidebarOutput);
        $this->assertStringContainsString('organization-submenu', $sidebarOutput);
        $this->assertStringContainsString('Organization settings', $sidebarOutput);
        
        // Test CSS classes
        $this->assertStringContainsString('dropdown-arrow', $sidebarOutput);
        $this->assertStringContainsString('organization-link', $sidebarOutput);
    }
    
    /**
     * Test client card component rendering
     */
    public function testClientCardComponent()
    {
        $client = $this->dataStore->getClient('test_client_1');
        $projectCount = count($this->dataStore->getProjectsByClient($client['id']));
        
        // Capture output from client card
        ob_start();
        include __DIR__ . '/../views/client_card.php';
        renderClientCard($client, $projectCount, $client['updatedAt']);
        $cardOutput = ob_get_clean();
        
        // Test card structure
        $this->assertStringContainsString('client-card', $cardOutput);
        $this->assertStringContainsString('client-card-content', $cardOutput);
        $this->assertStringContainsString('client-info', $cardOutput);
        $this->assertStringContainsString('client-actions', $cardOutput);
        
        // Test client information
        $this->assertStringContainsString('Test Client 1', $cardOutput);
        $this->assertStringContainsString('test1@example.com', $cardOutput);
        $this->assertStringContainsString('555-0001', $cardOutput);
        
        // Test action buttons
        $this->assertStringContainsString('Manage user access', $cardOutput);
        $this->assertStringContainsString('Delete client', $cardOutput);
        $this->assertStringContainsString('manage-access', $cardOutput);
        $this->assertStringContainsString('delete-client', $cardOutput);
        
        // Test status dropdown
        $this->assertStringContainsString('status-select', $cardOutput);
        $this->assertStringContainsString('Active', $cardOutput);
        $this->assertStringContainsString('Archived', $cardOutput);
        
        // Test data attributes
        $this->assertStringContainsString('data-client-id="test_client_1"', $cardOutput);
    }
    
    /**
     * Test clients view rendering
     */
    public function testClientsView()
    {
        // Set up GET parameters for testing
        $_GET['route'] = 'clients';
        $_GET['status'] = 'active';
        $_GET['search'] = '';
        $_GET['sort'] = 'name_asc';
        
        // Capture output from clients view
        ob_start();
        include __DIR__ . '/../views/clients.php';
        $clientsOutput = ob_get_clean();
        
        // Test header structure
        $this->assertStringContainsString('clients-header', $clientsOutput);
        $this->assertStringContainsString('clients-filters', $clientsOutput);
        $this->assertStringContainsString('clients-actions', $clientsOutput);
        
        // Test search functionality
        $this->assertStringContainsString('client-search', $clientsOutput);
        $this->assertStringContainsString('Search clients', $clientsOutput);
        
        // Test status tabs
        $this->assertStringContainsString('status-tabs', $clientsOutput);
        $this->assertStringContainsString('Active (1)', $clientsOutput);
        $this->assertStringContainsString('Archived (1)', $clientsOutput);
        
        // Test sorting dropdown
        $this->assertStringContainsString('sort-dropdown', $clientsOutput);
        $this->assertStringContainsString('Sort by', $clientsOutput);
        $this->assertStringContainsString('Last Name', $clientsOutput);
        $this->assertStringContainsString('Number of projects', $clientsOutput);
        $this->assertStringContainsString('Last modified', $clientsOutput);
        $this->assertStringContainsString('Project status', $clientsOutput);
        $this->assertStringContainsString('Clio contacts', $clientsOutput);
        
        // Test add client button
        $this->assertStringContainsString('Add new client', $clientsOutput);
        $this->assertStringContainsString('add-client-btn', $clientsOutput);
        
        // Test client list
        $this->assertStringContainsString('clients-list', $clientsOutput);
        $this->assertStringContainsString('clients-content', $clientsOutput);
    }
    
    /**
     * Test client detail view rendering
     */
    public function testClientDetailView()
    {
        $client = $this->dataStore->getClient('test_client_1');
        $projects = $this->dataStore->getProjectsByClient($client['id']);
        
        // Capture output from client detail view
        ob_start();
        include __DIR__ . '/../views/client.php';
        $clientDetailOutput = ob_get_clean();
        
        // Test header structure
        $this->assertStringContainsString('client-header', $clientDetailOutput);
        $this->assertStringContainsString('client-info', $clientDetailOutput);
        $this->assertStringContainsString('client-actions', $clientDetailOutput);
        
        // Test client name as clickable link
        $this->assertStringContainsString('client-name-link', $clientDetailOutput);
        $this->assertStringContainsString('Test Client 1', $clientDetailOutput);
        
        // Test client metadata
        $this->assertStringContainsString('test1@example.com', $clientDetailOutput);
        $this->assertStringContainsString('555-0001', $clientDetailOutput);
        
        // Test tab navigation
        $this->assertStringContainsString('client-tabs', $clientDetailOutput);
        $this->assertStringContainsString('tab-nav', $clientDetailOutput);
        $this->assertStringContainsString('Projects (2)', $clientDetailOutput);
        $this->assertStringContainsString('Client vault', $clientDetailOutput);
        $this->assertStringContainsString('Profile', $clientDetailOutput);
        $this->assertStringContainsString('Notes (0)', $clientDetailOutput);
        
        // Test projects section
        $this->assertStringContainsString('projects-section', $clientDetailOutput);
        $this->assertStringContainsString('projects-header', $clientDetailOutput);
        $this->assertStringContainsString('projects-list', $clientDetailOutput);
        
        // Test project cards
        $this->assertStringContainsString('project-card', $clientDetailOutput);
        $this->assertStringContainsString('Test Project 1', $clientDetailOutput);
        $this->assertStringContainsString('Test Project 2', $clientDetailOutput);
        
        // Test project status dropdowns
        $this->assertStringContainsString('In progress', $clientDetailOutput);
        $this->assertStringContainsString('Review', $clientDetailOutput);
        $this->assertStringContainsString('Completed', $clientDetailOutput);
        
        // Test delete project buttons
        $this->assertStringContainsString('Delete project', $clientDetailOutput);
        
        // Test add project button
        $this->assertStringContainsString('Add new project', $clientDetailOutput);
        $this->assertStringContainsString('add-project-btn', $clientDetailOutput);
    }
    
    /**
     * Test project detail view rendering
     */
    public function testProjectDetailView()
    {
        $project = $this->dataStore->getProject('test_project_1');
        $client = $this->dataStore->getClient($project['clientId']);
        
        // Capture output from project detail view
        ob_start();
        include __DIR__ . '/../views/project.php';
        $projectDetailOutput = ob_get_clean();
        
        // Test header structure
        $this->assertStringContainsString('project-header', $projectDetailOutput);
        $this->assertStringContainsString('project-info', $projectDetailOutput);
        $this->assertStringContainsString('project-actions', $projectDetailOutput);
        
        // Test project name section
        $this->assertStringContainsString('project-name-section', $projectDetailOutput);
        $this->assertStringContainsString('project-name', $projectDetailOutput);
        $this->assertStringContainsString('Test Project 1', $projectDetailOutput);
        
        // Test edit link
        $this->assertStringContainsString('edit-link', $projectDetailOutput);
        $this->assertStringContainsString('Edit', $projectDetailOutput);
        $this->assertStringContainsString('edit-project-name', $projectDetailOutput);
        
        // Test client link
        $this->assertStringContainsString('client-link', $projectDetailOutput);
        $this->assertStringContainsString('Test Client 1', $projectDetailOutput);
        
        // Test duplicate button
        $this->assertStringContainsString('Duplicate', $projectDetailOutput);
        
        // Test tab navigation
        $this->assertStringContainsString('project-tabs', $projectDetailOutput);
        $this->assertStringContainsString('Overview', $projectDetailOutput);
        $this->assertStringContainsString('Signed documents', $projectDetailOutput);
        
        // Test project summary
        $this->assertStringContainsString('project-summary', $projectDetailOutput);
        $this->assertStringContainsString('summary-section', $projectDetailOutput);
        $this->assertStringContainsString('summary-item', $projectDetailOutput);
        
        // Test status dropdown
        $this->assertStringContainsString('In progress', $projectDetailOutput);
        $this->assertStringContainsString('Review', $projectDetailOutput);
        $this->assertStringContainsString('Completed', $projectDetailOutput);
        
        // Test responsible attorney
        $this->assertStringContainsString('YOUNGMAN REITSHTEIN, PLC', $projectDetailOutput);
        
        // Test go to populate button
        $this->assertStringContainsString('populate-action', $projectDetailOutput);
        $this->assertStringContainsString('Go to populate →', $projectDetailOutput);
        $this->assertStringContainsString('go-to-populate', $projectDetailOutput);
        $this->assertStringContainsString('btn-large', $projectDetailOutput);
    }
    
    /**
     * Test breadcrumb component rendering
     */
    public function testBreadcrumbComponent()
    {
        $client = $this->dataStore->getClient('test_client_1');
        $project = $this->dataStore->getProject('test_project_1');
        
        // Test client breadcrumb
        ob_start();
        renderBreadcrumb('client', ['client' => $client]);
        $clientBreadcrumb = ob_get_clean();
        
        $this->assertStringContainsString('breadcrumb-nav', $clientBreadcrumb);
        $this->assertStringContainsString('breadcrumb-list', $clientBreadcrumb);
        $this->assertStringContainsString('Clients', $clientBreadcrumb);
        $this->assertStringContainsString('Test Client 1', $clientBreadcrumb);
        
        // Test project breadcrumb
        ob_start();
        renderBreadcrumb('project', ['project' => $project, 'client' => $client]);
        $projectBreadcrumb = ob_get_clean();
        
        $this->assertStringContainsString('Clients', $projectBreadcrumb);
        $this->assertStringContainsString('Test Client 1', $projectBreadcrumb);
        $this->assertStringContainsString('Test Project 1', $projectBreadcrumb);
    }
    
    /**
     * Test loading component rendering
     */
    public function testLoadingComponent()
    {
        ob_start();
        include __DIR__ . '/../views/loading.php';
        $loadingOutput = ob_get_clean();
        
        $this->assertStringContainsString('global-loading-overlay', $loadingOutput);
        $this->assertStringContainsString('spin', $loadingOutput);
        $this->assertStringContainsString('@keyframes spin', $loadingOutput);
    }
    
    /**
     * Test keyboard shortcuts component rendering
     */
    public function testKeyboardShortcutsComponent()
    {
        ob_start();
        include __DIR__ . '/../views/keyboard_shortcuts.php';
        $shortcutsOutput = ob_get_clean();
        
        $this->assertStringContainsString('keyboard-shortcuts-modal', $shortcutsOutput);
        $this->assertStringContainsString('Keyboard Shortcuts', $shortcutsOutput);
        $this->assertStringContainsString('Navigation', $shortcutsOutput);
        $this->assertStringContainsString('Global', $shortcutsOutput);
        $this->assertStringContainsString('Clients', $shortcutsOutput);
        $this->assertStringContainsString('Projects', $shortcutsOutput);
        $this->assertStringContainsString('Templates', $shortcutsOutput);
        $this->assertStringContainsString('Support', $shortcutsOutput);
    }
    
    /**
     * Test dark mode component rendering
     */
    public function testDarkModeComponent()
    {
        ob_start();
        include __DIR__ . '/../views/dark_mode.php';
        $darkModeOutput = ob_get_clean();
        
        $this->assertStringContainsString('dark-mode-toggle', $darkModeOutput);
        $this->assertStringContainsString('dark-mode', $darkModeOutput);
        $this->assertStringContainsString('data-theme', $darkModeOutput);
    }
    
    /**
     * Test CSS classes and styling
     */
    public function testCSSClassesAndStyling()
    {
        // Test sidebar CSS
        ob_start();
        include __DIR__ . '/../views/sidebar.php';
        $sidebarOutput = ob_get_clean();
        
        $this->assertStringContainsString('organization-item', $sidebarOutput);
        $this->assertStringContainsString('organization-link', $sidebarOutput);
        $this->assertStringContainsString('dropdown-arrow', $sidebarOutput);
        $this->assertStringContainsString('organization-submenu', $sidebarOutput);
        
        // Test client card CSS
        ob_start();
        include __DIR__ . '/../views/client_card.php';
        $cardOutput = ob_get_clean();
        
        $this->assertStringContainsString('client-card', $cardOutput);
        $this->assertStringContainsString('client-card-content', $cardOutput);
        $this->assertStringContainsString('client-info', $cardOutput);
        $this->assertStringContainsString('client-actions', $cardOutput);
        $this->assertStringContainsString('btn-secondary', $cardOutput);
        $this->assertStringContainsString('btn-danger', $cardOutput);
        
        // Test clients view CSS
        ob_start();
        include __DIR__ . '/../views/clients.php';
        $clientsOutput = ob_get_clean();
        
        $this->assertStringContainsString('sort-dropdown', $clientsOutput);
        $this->assertStringContainsString('sort-trigger', $clientsOutput);
        $this->assertStringContainsString('sort-options', $clientsOutput);
        $this->assertStringContainsString('status-tabs', $clientsOutput);
        $this->assertStringContainsString('status-tab', $clientsOutput);
        
        // Test project detail CSS
        ob_start();
        include __DIR__ . '/../views/project.php';
        $projectOutput = ob_get_clean();
        
        $this->assertStringContainsString('project-name-section', $projectOutput);
        $this->assertStringContainsString('edit-link', $projectOutput);
        $this->assertStringContainsString('project-tabs', $projectOutput);
        $this->assertStringContainsString('tab-nav', $projectOutput);
        $this->assertStringContainsString('project-summary', $projectOutput);
        $this->assertStringContainsString('summary-section', $projectOutput);
        $this->assertStringContainsString('populate-action', $projectOutput);
        $this->assertStringContainsString('btn-large', $projectOutput);
    }
    
    /**
     * Test JavaScript functionality
     */
    public function testJavaScriptFunctionality()
    {
        // Test client card JavaScript
        ob_start();
        include __DIR__ . '/../views/client_card.php';
        $cardOutput = ob_get_clean();
        
        $this->assertStringContainsString('addEventListener', $cardOutput);
        $this->assertStringContainsString('DOMContentLoaded', $cardOutput);
        $this->assertStringContainsString('manage-access', $cardOutput);
        $this->assertStringContainsString('delete-client', $cardOutput);
        $this->assertStringContainsString('status-select', $cardOutput);
        
        // Test project detail JavaScript
        ob_start();
        include __DIR__ . '/../views/project.php';
        $projectOutput = ob_get_clean();
        
        $this->assertStringContainsString('go-to-populate', $projectOutput);
        $this->assertStringContainsString('edit-project-name', $projectOutput);
        $this->assertStringContainsString('status-select', $projectOutput);
        $this->assertStringContainsString('keydown', $projectOutput);
        $this->assertStringContainsString('blur', $projectOutput);
        
        // Test keyboard shortcuts JavaScript
        ob_start();
        include __DIR__ . '/../views/keyboard_shortcuts.php';
        $shortcutsOutput = ob_get_clean();
        
        $this->assertStringContainsString('keydown', $shortcutsOutput);
        $this->assertStringContainsString('Escape', $shortcutsOutput);
        $this->assertStringContainsString('preventDefault', $shortcutsOutput);
    }
    
    /**
     * Test responsive design classes
     */
    public function testResponsiveDesign()
    {
        // Test sidebar responsive classes
        ob_start();
        include __DIR__ . '/../views/sidebar.php';
        $sidebarOutput = ob_get_clean();
        
        // Test client card responsive classes
        ob_start();
        include __DIR__ . '/../views/client_card.php';
        $cardOutput = ob_get_clean();
        
        $this->assertStringContainsString('@media', $cardOutput);
        $this->assertStringContainsString('max-width: 768px', $cardOutput);
        $this->assertStringContainsString('flex-direction: column', $cardOutput);
        
        // Test project detail responsive classes
        ob_start();
        include __DIR__ . '/../views/project.php';
        $projectOutput = ob_get_clean();
        
        $this->assertStringContainsString('@media', $projectOutput);
        $this->assertStringContainsString('max-width: 768px', $projectOutput);
        $this->assertStringContainsString('flex-direction: column', $projectOutput);
    }
}













