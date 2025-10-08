<?php
/**
 * Accessibility Tests
 * Tests keyboard navigation, screen reader compatibility, and WCAG compliance
 */

require_once __DIR__ . '/../lib/data.php';
require_once __DIR__ . '/../vendor/autoload.php';

class AccessibilityTest extends PHPUnit\Framework\TestCase
{
    private $dataStore;
    private $testDataFile;
    
    protected function setUp(): void
    {
        // Create a test data file
        $this->testDataFile = __DIR__ . '/../data/test_accessibility.json';
        
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
            'projects' => [],
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
     * Test keyboard navigation support
     */
    public function testKeyboardNavigationSupport()
    {
        // Test sidebar navigation
        ob_start();
        include __DIR__ . '/../views/sidebar.php';
        $sidebarOutput = ob_get_clean();
        
        // Check for keyboard navigation attributes
        $this->assertStringContainsString('tabindex', $sidebarOutput, 'Sidebar should have tabindex for keyboard navigation');
        
        // Test client card keyboard navigation
        ob_start();
        include __DIR__ . '/../views/client_card.php';
        $cardOutput = ob_get_clean();
        
        // Check for keyboard event handlers
        $this->assertStringContainsString('keydown', $cardOutput, 'Client cards should support keydown events');
        $this->assertStringContainsString('addEventListener', $cardOutput, 'Client cards should have event listeners');
        
        // Test keyboard shortcuts
        ob_start();
        include __DIR__ . '/../views/keyboard_shortcuts.php';
        $shortcutsOutput = ob_get_clean();
        
        // Check for keyboard shortcut handlers
        $this->assertStringContainsString('keydown', $shortcutsOutput, 'Keyboard shortcuts should be implemented');
        $this->assertStringContainsString('Escape', $shortcutsOutput, 'Escape key should be handled');
        $this->assertStringContainsString('preventDefault', $shortcutsOutput, 'Default key behavior should be prevented when needed');
    }
    
    /**
     * Test ARIA attributes and roles
     */
    public function testARIAAttributesAndRoles()
    {
        // Test sidebar ARIA attributes
        ob_start();
        include __DIR__ . '/../views/sidebar.php';
        $sidebarOutput = ob_get_clean();
        
        // Check for navigation role
        $this->assertStringContainsString('<nav', $sidebarOutput, 'Sidebar should use nav element for navigation');
        
        // Test client card ARIA attributes
        ob_start();
        include __DIR__ . '/../views/client_card.php';
        $cardOutput = ob_get_clean();
        
        // Check for button roles and labels
        $this->assertStringContainsString('button', $cardOutput, 'Interactive elements should be buttons');
        $this->assertStringContainsString('aria-label', $cardOutput, 'Buttons should have aria-label attributes');
        
        // Test modal ARIA attributes
        ob_start();
        include __DIR__ . '/../views/clients.php';
        $clientsOutput = ob_get_clean();
        
        // Check for modal attributes
        $this->assertStringContainsString('modal', $clientsOutput, 'Modals should have modal role');
        $this->assertStringContainsString('aria-hidden', $clientsOutput, 'Modals should have aria-hidden attributes');
    }
    
    /**
     * Test screen reader compatibility
     */
    public function testScreenReaderCompatibility()
    {
        // Test form labels
        ob_start();
        include __DIR__ . '/../views/clients.php';
        $clientsOutput = ob_get_clean();
        
        // Check for proper form labels
        $this->assertStringContainsString('<label', $clientsOutput, 'Forms should have proper labels');
        $this->assertStringContainsString('for=', $clientsOutput, 'Labels should be associated with form elements');
        
        // Test input accessibility
        $this->assertStringContainsString('placeholder', $clientsOutput, 'Inputs should have placeholder text');
        $this->assertStringContainsString('required', $clientsOutput, 'Required fields should be marked');
        
        // Test project detail view accessibility
        ob_start();
        include __DIR__ . '/../views/project.php';
        $projectOutput = ob_get_clean();
        
        // Check for proper heading structure
        $this->assertStringContainsString('<h1', $projectOutput, 'Page should have h1 heading');
        $this->assertStringContainsString('<h2', $projectOutput, 'Page should have h2 headings');
        $this->assertStringContainsString('<h3', $projectOutput, 'Page should have h3 headings');
        
        // Check for alt text on images
        $this->assertStringContainsString('alt=', $projectOutput, 'Images should have alt text');
    }
    
    /**
     * Test color contrast and visual accessibility
     */
    public function testColorContrastAndVisualAccessibility()
    {
        // Test CSS for color contrast
        ob_start();
        include __DIR__ . '/../views/client_card.php';
        $cardOutput = ob_get_clean();
        
        // Check for color definitions
        $this->assertStringContainsString('color:', $cardOutput, 'CSS should define text colors');
        $this->assertStringContainsString('background:', $cardOutput, 'CSS should define background colors');
        
        // Test focus indicators
        $this->assertStringContainsString(':focus', $cardOutput, 'CSS should have focus indicators');
        $this->assertStringContainsString('outline', $cardOutput, 'Focus should have outline styles');
        
        // Test hover states
        $this->assertStringContainsString(':hover', $cardOutput, 'CSS should have hover states');
        
        // Test button accessibility
        ob_start();
        include __DIR__ . '/../views/project.php';
        $projectOutput = ob_get_clean();
        
        // Check for button styling
        $this->assertStringContainsString('btn', $projectOutput, 'Buttons should have proper CSS classes');
        $this->assertStringContainsString('cursor: pointer', $projectOutput, 'Interactive elements should have pointer cursor');
    }
    
    /**
     * Test semantic HTML structure
     */
    public function testSemanticHTMLStructure()
    {
        // Test sidebar semantic structure
        ob_start();
        include __DIR__ . '/../views/sidebar.php';
        $sidebarOutput = ob_get_clean();
        
        // Check for semantic elements
        $this->assertStringContainsString('<nav', $sidebarOutput, 'Navigation should use nav element');
        $this->assertStringContainsString('<ul', $sidebarOutput, 'Lists should use ul/ol elements');
        $this->assertStringContainsString('<li', $sidebarOutput, 'List items should use li elements');
        
        // Test client detail semantic structure
        ob_start();
        include __DIR__ . '/../views/client.php';
        $clientOutput = ob_get_clean();
        
        // Check for proper heading hierarchy
        $this->assertStringContainsString('<h1', $clientOutput, 'Page should have h1 heading');
        $this->assertStringContainsString('<h2', $clientOutput, 'Page should have h2 headings');
        
        // Check for section elements
        $this->assertStringContainsString('section', $clientOutput, 'Content should be organized in sections');
        
        // Test form semantic structure
        ob_start();
        include __DIR__ . '/../views/clients.php';
        $clientsOutput = ob_get_clean();
        
        // Check for form elements
        $this->assertStringContainsString('<form', $clientsOutput, 'Forms should use form elements');
        $this->assertStringContainsString('<input', $clientsOutput, 'Inputs should use input elements');
        $this->assertStringContainsString('<select', $clientsOutput, 'Dropdowns should use select elements');
        $this->assertStringContainsString('<button', $clientsOutput, 'Buttons should use button elements');
    }
    
    /**
     * Test error handling and announcements
     */
    public function testErrorHandlingAndAnnouncements()
    {
        // Test error handler component
        ob_start();
        include __DIR__ . '/../views/error_handler.php';
        $errorOutput = ob_get_clean();
        
        // Check for error announcement attributes
        $this->assertStringContainsString('aria-live', $errorOutput, 'Error messages should have aria-live attributes');
        $this->assertStringContainsString('role="alert"', $errorOutput, 'Error messages should have alert role');
        
        // Test loading states accessibility
        ob_start();
        include __DIR__ . '/../views/loading.php';
        $loadingOutput = ob_get_clean();
        
        // Check for loading announcements
        $this->assertStringContainsString('aria-live', $loadingOutput, 'Loading states should have aria-live attributes');
        $this->assertStringContainsString('aria-label', $loadingOutput, 'Loading indicators should have aria-label');
    }
    
    /**
     * Test responsive design accessibility
     */
    public function testResponsiveDesignAccessibility()
    {
        // Test responsive CSS
        ob_start();
        include __DIR__ . '/../views/client_card.php';
        $cardOutput = ob_get_clean();
        
        // Check for responsive design
        $this->assertStringContainsString('@media', $cardOutput, 'CSS should have media queries for responsive design');
        $this->assertStringContainsString('max-width', $cardOutput, 'CSS should have max-width breakpoints');
        
        // Test mobile accessibility
        ob_start();
        include __DIR__ . '/../views/project.php';
        $projectOutput = ob_get_clean();
        
        // Check for touch-friendly sizing
        $this->assertStringContainsString('min-height', $projectOutput, 'Touch targets should have minimum height');
        $this->assertStringContainsString('min-width', $projectOutput, 'Touch targets should have minimum width');
    }
    
    /**
     * Test skip links and navigation shortcuts
     */
    public function testSkipLinksAndNavigationShortcuts()
    {
        // Test keyboard shortcuts component
        ob_start();
        include __DIR__ . '/../views/keyboard_shortcuts.php';
        $shortcutsOutput = ob_get_clean();
        
        // Check for skip links
        $this->assertStringContainsString('skip', $shortcutsOutput, 'Page should have skip links');
        
        // Check for keyboard shortcuts documentation
        $this->assertStringContainsString('Keyboard Shortcuts', $shortcutsOutput, 'Keyboard shortcuts should be documented');
        $this->assertStringContainsString('Navigation', $shortcutsOutput, 'Navigation shortcuts should be listed');
        
        // Test main content accessibility
        ob_start();
        include __DIR__ . '/../views/layout_header.php';
        $headerOutput = ob_get_clean();
        
        // Check for main content landmark
        $this->assertStringContainsString('main', $headerOutput, 'Page should have main content landmark');
    }
    
    /**
     * Test form accessibility
     */
    public function testFormAccessibility()
    {
        // Test client creation form
        ob_start();
        include __DIR__ . '/../views/clients.php';
        $clientsOutput = ob_get_clean();
        
        // Check for form accessibility
        $this->assertStringContainsString('<label', $clientsOutput, 'Form inputs should have labels');
        $this->assertStringContainsString('for=', $clientsOutput, 'Labels should be associated with inputs');
        $this->assertStringContainsString('required', $clientsOutput, 'Required fields should be marked');
        $this->assertStringContainsString('aria-required', $clientsOutput, 'Required fields should have aria-required');
        
        // Test fieldset and legend usage
        $this->assertStringContainsString('<fieldset', $clientsOutput, 'Related form fields should be grouped in fieldsets');
        $this->assertStringContainsString('<legend', $clientsOutput, 'Fieldsets should have legends');
    }
    
    /**
     * Test table accessibility
     */
    public function testTableAccessibility()
    {
        // Test project documents table
        ob_start();
        include __DIR__ . '/../views/project.php';
        $projectOutput = ob_get_clean();
        
        // Check for table accessibility
        $this->assertStringContainsString('<table', $projectOutput, 'Data should be in tables');
        $this->assertStringContainsString('<thead', $projectOutput, 'Tables should have headers');
        $this->assertStringContainsString('<th', $projectOutput, 'Table headers should use th elements');
        $this->assertStringContainsString('<tbody', $projectOutput, 'Tables should have body sections');
        
        // Check for table captions
        $this->assertStringContainsString('<caption', $projectOutput, 'Tables should have captions');
        
        // Check for scope attributes
        $this->assertStringContainsString('scope=', $projectOutput, 'Table headers should have scope attributes');
    }
    
    /**
     * Test dynamic content accessibility
     */
    public function testDynamicContentAccessibility()
    {
        // Test modal accessibility
        ob_start();
        include __DIR__ . '/../views/clients.php';
        $clientsOutput = ob_get_clean();
        
        // Check for modal accessibility
        $this->assertStringContainsString('role="dialog"', $clientsOutput, 'Modals should have dialog role');
        $this->assertStringContainsString('aria-modal', $clientsOutput, 'Modals should have aria-modal attribute');
        $this->assertStringContainsString('aria-labelledby', $clientsOutput, 'Modals should be labelled');
        
        // Test dropdown accessibility
        ob_start();
        include __DIR__ . '/../views/sidebar.php';
        $sidebarOutput = ob_get_clean();
        
        // Check for dropdown accessibility
        $this->assertStringContainsString('aria-expanded', $sidebarOutput, 'Dropdowns should have aria-expanded');
        $this->assertStringContainsString('aria-haspopup', $sidebarOutput, 'Dropdowns should have aria-haspopup');
    }
    
    /**
     * Test language and internationalization
     */
    public function testLanguageAndInternationalization()
    {
        // Test HTML lang attribute
        ob_start();
        include __DIR__ . '/../views/layout_header.php';
        $headerOutput = ob_get_clean();
        
        // Check for language declaration
        $this->assertStringContainsString('lang=', $headerOutput, 'HTML should have lang attribute');
        
        // Test text direction support
        $this->assertStringContainsString('dir=', $headerOutput, 'HTML should support text direction');
    }
    
    /**
     * Test alternative text and media accessibility
     */
    public function testAlternativeTextAndMediaAccessibility()
    {
        // Test image accessibility
        ob_start();
        include __DIR__ . '/../views/sidebar.php';
        $sidebarOutput = ob_get_clean();
        
        // Check for alt text
        $this->assertStringContainsString('alt=', $sidebarOutput, 'Images should have alt text');
        
        // Test logo accessibility
        $this->assertStringContainsString('logo', $sidebarOutput, 'Logo should be properly identified');
        
        // Test icon accessibility
        ob_start();
        include __DIR__ . '/../views/client_card.php';
        $cardOutput = ob_get_clean();
        
        // Check for icon accessibility
        $this->assertStringContainsString('aria-hidden', $cardOutput, 'Decorative icons should be hidden from screen readers');
    }
    
    /**
     * Test focus management
     */
    public function testFocusManagement()
    {
        // Test focus management in modals
        ob_start();
        include __DIR__ . '/../views/clients.php';
        $clientsOutput = ob_get_clean();
        
        // Check for focus management
        $this->assertStringContainsString('focus', $clientsOutput, 'JavaScript should handle focus management');
        
        // Test tab order
        ob_start();
        include __DIR__ . '/../views/project.php';
        $projectOutput = ob_get_clean();
        
        // Check for tabindex management
        $this->assertStringContainsString('tabindex', $projectOutput, 'Elements should have proper tab order');
    }
}













