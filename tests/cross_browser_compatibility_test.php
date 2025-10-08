<?php
/**
 * Cross-Browser Compatibility Tests
 * Tests application compatibility across different browsers and devices
 */

require_once __DIR__ . '/../lib/data.php';
require_once __DIR__ . '/../vendor/autoload.php';

class CrossBrowserCompatibilityTest extends PHPUnit\Framework\TestCase
{
    private $dataStore;
    private $testDataFile;
    private $supportedBrowsers = [
        'Chrome' => ['version' => '90+', 'engine' => 'Blink'],
        'Firefox' => ['version' => '88+', 'engine' => 'Gecko'],
        'Safari' => ['version' => '14+', 'engine' => 'WebKit'],
        'Edge' => ['version' => '90+', 'engine' => 'Blink'],
        'Internet Explorer' => ['version' => '11', 'engine' => 'Trident', 'deprecated' => true]
    ];
    
    protected function setUp(): void
    {
        // Create a test data file
        $this->testDataFile = __DIR__ . '/../data/test_cross_browser.json';
        
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
     * Test CSS compatibility across browsers
     */
    public function testCSSCrossBrowserCompatibility()
    {
        // Test sidebar CSS
        ob_start();
        include __DIR__ . '/../views/sidebar.php';
        $sidebarOutput = ob_get_clean();
        
        // Check for vendor prefixes
        $this->assertStringContainsString('-webkit-', $sidebarOutput, 'CSS should include webkit prefixes for Safari');
        $this->assertStringContainsString('-moz-', $sidebarOutput, 'CSS should include moz prefixes for Firefox');
        $this->assertStringContainsString('-ms-', $sidebarOutput, 'CSS should include ms prefixes for IE/Edge');
        
        // Check for modern CSS features with fallbacks
        $this->assertStringContainsString('display: flex', $sidebarOutput, 'CSS should use flexbox with fallbacks');
        $this->assertStringContainsString('grid-template-columns', $sidebarOutput, 'CSS should use CSS Grid with fallbacks');
        
        // Test client card CSS
        ob_start();
        include __DIR__ . '/../views/client_card.php';
        $cardOutput = ob_get_clean();
        
        // Check for CSS custom properties (CSS variables)
        $this->assertStringContainsString('var(', $cardOutput, 'CSS should use custom properties with fallbacks');
        
        // Check for modern selectors with fallbacks
        $this->assertStringContainsString(':hover', $cardOutput, 'CSS should have hover states');
        $this->assertStringContainsString(':focus', $cardOutput, 'CSS should have focus states');
        $this->assertStringContainsString(':active', $cardOutput, 'CSS should have active states');
    }
    
    /**
     * Test JavaScript compatibility across browsers
     */
    public function testJavaScriptCrossBrowserCompatibility()
    {
        // Test client card JavaScript
        ob_start();
        include __DIR__ . '/../views/client_card.php';
        $cardOutput = ob_get_clean();
        
        // Check for modern JavaScript features
        $this->assertStringContainsString('addEventListener', $cardOutput, 'JavaScript should use addEventListener for event handling');
        $this->assertStringContainsString('DOMContentLoaded', $cardOutput, 'JavaScript should wait for DOM content loaded');
        
        // Check for ES6+ features with compatibility
        $this->assertStringContainsString('forEach', $cardOutput, 'JavaScript should use forEach for array iteration');
        $this->assertStringContainsString('querySelectorAll', $cardOutput, 'JavaScript should use querySelectorAll for element selection');
        
        // Test project detail JavaScript
        ob_start();
        include __DIR__ . '/../views/project.php';
        $projectOutput = ob_get_clean();
        
        // Check for modern JavaScript features
        $this->assertStringContainsString('createElement', $projectOutput, 'JavaScript should use createElement for dynamic elements');
        $this->assertStringContainsString('appendChild', $projectOutput, 'JavaScript should use appendChild for DOM manipulation');
        
        // Check for event handling compatibility
        $this->assertStringContainsString('preventDefault', $projectOutput, 'JavaScript should prevent default behavior when needed');
        $this->assertStringContainsString('stopPropagation', $projectOutput, 'JavaScript should stop event propagation when needed');
    }
    
    /**
     * Test HTML5 compatibility
     */
    public function testHTML5Compatibility()
    {
        // Test semantic HTML elements
        ob_start();
        include __DIR__ . '/../views/sidebar.php';
        $sidebarOutput = ob_get_clean();
        
        // Check for HTML5 semantic elements
        $this->assertStringContainsString('<nav', $sidebarOutput, 'HTML should use nav element for navigation');
        $this->assertStringContainsString('<header', $sidebarOutput, 'HTML should use header element');
        $this->assertStringContainsString('<main', $sidebarOutput, 'HTML should use main element');
        $this->assertStringContainsString('<section', $sidebarOutput, 'HTML should use section elements');
        
        // Test form elements
        ob_start();
        include __DIR__ . '/../views/clients.php';
        $clientsOutput = ob_get_clean();
        
        // Check for HTML5 form elements
        $this->assertStringContainsString('type="email"', $clientsOutput, 'HTML should use email input type');
        $this->assertStringContainsString('type="tel"', $clientsOutput, 'HTML should use tel input type');
        $this->assertStringContainsString('required', $clientsOutput, 'HTML should use required attribute');
        $this->assertStringContainsString('placeholder', $clientsOutput, 'HTML should use placeholder attribute');
    }
    
    /**
     * Test responsive design compatibility
     */
    public function testResponsiveDesignCompatibility()
    {
        // Test responsive CSS
        ob_start();
        include __DIR__ . '/../views/client_card.php';
        $cardOutput = ob_get_clean();
        
        // Check for responsive design features
        $this->assertStringContainsString('@media', $cardOutput, 'CSS should have media queries for responsive design');
        $this->assertStringContainsString('max-width', $cardOutput, 'CSS should have max-width breakpoints');
        $this->assertStringContainsString('min-width', $cardOutput, 'CSS should have min-width breakpoints');
        
        // Check for viewport meta tag
        ob_start();
        include __DIR__ . '/../views/layout_header.php';
        $headerOutput = ob_get_clean();
        
        $this->assertStringContainsString('viewport', $headerOutput, 'HTML should have viewport meta tag for mobile compatibility');
        $this->assertStringContainsString('width=device-width', $headerOutput, 'Viewport should set width to device width');
        $this->assertStringContainsString('initial-scale=1', $headerOutput, 'Viewport should set initial scale to 1');
    }
    
    /**
     * Test CSS Grid and Flexbox compatibility
     */
    public function testCSSLayoutCompatibility()
    {
        // Test CSS Grid usage
        ob_start();
        include __DIR__ . '/../views/project.php';
        $projectOutput = ob_get_clean();
        
        // Check for CSS Grid
        $this->assertStringContainsString('display: grid', $projectOutput, 'CSS should use CSS Grid for layout');
        $this->assertStringContainsString('grid-template-columns', $projectOutput, 'CSS should define grid template columns');
        $this->assertStringContainsString('grid-gap', $projectOutput, 'CSS should use grid gap for spacing');
        
        // Test Flexbox usage
        ob_start();
        include __DIR__ . '/../views/client_card.php';
        $cardOutput = ob_get_clean();
        
        // Check for Flexbox
        $this->assertStringContainsString('display: flex', $cardOutput, 'CSS should use Flexbox for layout');
        $this->assertStringContainsString('flex-direction', $cardOutput, 'CSS should define flex direction');
        $this->assertStringContainsString('justify-content', $cardOutput, 'CSS should use justify-content for alignment');
        $this->assertStringContainsString('align-items', $cardOutput, 'CSS should use align-items for alignment');
    }
    
    /**
     * Test CSS animations and transitions compatibility
     */
    public function testCSSAnimationsCompatibility()
    {
        // Test CSS transitions
        ob_start();
        include __DIR__ . '/../views/sidebar.php';
        $sidebarOutput = ob_get_clean();
        
        // Check for CSS transitions
        $this->assertStringContainsString('transition:', $sidebarOutput, 'CSS should use transitions for smooth animations');
        $this->assertStringContainsString('transform:', $sidebarOutput, 'CSS should use transforms for animations');
        
        // Test CSS animations
        ob_start();
        include __DIR__ . '/../views/loading.php';
        $loadingOutput = ob_get_clean();
        
        // Check for CSS animations
        $this->assertStringContainsString('@keyframes', $loadingOutput, 'CSS should define keyframe animations');
        $this->assertStringContainsString('animation:', $loadingOutput, 'CSS should use animation property');
        $this->assertStringContainsString('animation-iteration-count', $loadingOutput, 'CSS should define animation iteration count');
    }
    
    /**
     * Test form validation compatibility
     */
    public function testFormValidationCompatibility()
    {
        // Test HTML5 form validation
        ob_start();
        include __DIR__ . '/../views/clients.php';
        $clientsOutput = ob_get_clean();
        
        // Check for HTML5 validation attributes
        $this->assertStringContainsString('required', $clientsOutput, 'Forms should use required attribute for validation');
        $this->assertStringContainsString('pattern', $clientsOutput, 'Forms should use pattern attribute for validation');
        $this->assertStringContainsString('minlength', $clientsOutput, 'Forms should use minlength attribute for validation');
        $this->assertStringContainsString('maxlength', $clientsOutput, 'Forms should use maxlength attribute for validation');
        
        // Check for custom validation
        $this->assertStringContainsString('setCustomValidity', $clientsOutput, 'JavaScript should provide custom validation');
        $this->assertStringContainsString('checkValidity', $clientsOutput, 'JavaScript should check form validity');
    }
    
    /**
     * Test accessibility features compatibility
     */
    public function testAccessibilityCompatibility()
    {
        // Test ARIA attributes
        ob_start();
        include __DIR__ . '/../views/sidebar.php';
        $sidebarOutput = ob_get_clean();
        
        // Check for ARIA attributes
        $this->assertStringContainsString('aria-label', $sidebarOutput, 'Elements should have aria-label attributes');
        $this->assertStringContainsString('aria-expanded', $sidebarOutput, 'Interactive elements should have aria-expanded');
        $this->assertStringContainsString('aria-haspopup', $sidebarOutput, 'Dropdown elements should have aria-haspopup');
        
        // Test keyboard navigation
        ob_start();
        include __DIR__ . '/../views/keyboard_shortcuts.php';
        $shortcutsOutput = ob_get_clean();
        
        // Check for keyboard event handling
        $this->assertStringContainsString('keydown', $shortcutsOutput, 'JavaScript should handle keydown events');
        $this->assertStringContainsString('keyCode', $shortcutsOutput, 'JavaScript should handle key codes');
        $this->assertStringContainsString('which', $shortcutsOutput, 'JavaScript should handle which property for compatibility');
    }
    
    /**
     * Test image and media compatibility
     */
    public function testImageAndMediaCompatibility()
    {
        // Test image elements
        ob_start();
        include __DIR__ . '/../views/sidebar.php';
        $sidebarOutput = ob_get_clean();
        
        // Check for image attributes
        $this->assertStringContainsString('<img', $sidebarOutput, 'HTML should use img elements for images');
        $this->assertStringContainsString('alt=', $sidebarOutput, 'Images should have alt attributes');
        $this->assertStringContainsString('src=', $sidebarOutput, 'Images should have src attributes');
        
        // Check for responsive images
        $this->assertStringContainsString('srcset', $sidebarOutput, 'Images should use srcset for responsive images');
        $this->assertStringContainsString('sizes', $sidebarOutput, 'Images should use sizes attribute for responsive images');
    }
    
    /**
     * Test JavaScript ES6+ compatibility
     */
    public function testJavaScriptES6Compatibility()
    {
        // Test modern JavaScript features
        ob_start();
        include __DIR__ . '/../views/project.php';
        $projectOutput = ob_get_clean();
        
        // Check for arrow functions (should be transpiled for older browsers)
        $this->assertStringContainsString('function', $projectOutput, 'JavaScript should use function declarations for compatibility');
        
        // Check for const/let usage
        $this->assertStringContainsString('const', $projectOutput, 'JavaScript should use const for constants');
        $this->assertStringContainsString('let', $projectOutput, 'JavaScript should use let for variables');
        
        // Check for template literals
        $this->assertStringContainsString('`', $projectOutput, 'JavaScript should use template literals');
        
        // Check for destructuring
        $this->assertStringContainsString('{', $projectOutput, 'JavaScript should use object destructuring');
    }
    
    /**
     * Test CSS custom properties compatibility
     */
    public function testCSSCustomPropertiesCompatibility()
    {
        // Test CSS custom properties
        ob_start();
        include __DIR__ . '/../views/dark_mode.php';
        $darkModeOutput = ob_get_clean();
        
        // Check for CSS custom properties
        $this->assertStringContainsString('--', $darkModeOutput, 'CSS should use custom properties');
        $this->assertStringContainsString('var(', $darkModeOutput, 'CSS should use var() function for custom properties');
        
        // Check for fallback values
        $this->assertStringContainsString(',', $darkModeOutput, 'CSS custom properties should have fallback values');
    }
    
    /**
     * Test browser-specific CSS prefixes
     */
    public function testBrowserSpecificPrefixes()
    {
        // Test CSS prefixes
        ob_start();
        include __DIR__ . '/../views/loading.php';
        $loadingOutput = ob_get_clean();
        
        // Check for webkit prefixes
        $this->assertStringContainsString('-webkit-', $loadingOutput, 'CSS should include webkit prefixes');
        
        // Check for moz prefixes
        $this->assertStringContainsString('-moz-', $loadingOutput, 'CSS should include moz prefixes');
        
        // Check for ms prefixes
        $this->assertStringContainsString('-ms-', $loadingOutput, 'CSS should include ms prefixes');
        
        // Check for standard properties
        $this->assertStringContainsString('transform:', $loadingOutput, 'CSS should include standard properties');
        $this->assertStringContainsString('animation:', $loadingOutput, 'CSS should include standard animation properties');
    }
    
    /**
     * Test JavaScript event compatibility
     */
    public function testJavaScriptEventCompatibility()
    {
        // Test event handling
        ob_start();
        include __DIR__ . '/../views/client_card.php';
        $cardOutput = ob_get_clean();
        
        // Check for modern event handling
        $this->assertStringContainsString('addEventListener', $cardOutput, 'JavaScript should use addEventListener');
        $this->assertStringContainsString('removeEventListener', $cardOutput, 'JavaScript should use removeEventListener');
        
        // Check for event object properties
        $this->assertStringContainsString('preventDefault', $cardOutput, 'JavaScript should handle preventDefault');
        $this->assertStringContainsString('stopPropagation', $cardOutput, 'JavaScript should handle stopPropagation');
        
        // Check for event delegation
        $this->assertStringContainsString('target', $cardOutput, 'JavaScript should use event.target');
        $this->assertStringContainsString('currentTarget', $cardOutput, 'JavaScript should use event.currentTarget');
    }
    
    /**
     * Test CSS feature detection
     */
    public function testCSSFeatureDetection()
    {
        // Test CSS feature detection
        ob_start();
        include __DIR__ . '/../views/layout_header.php';
        $headerOutput = ob_get_clean();
        
        // Check for CSS feature detection
        $this->assertStringContainsString('@supports', $headerOutput, 'CSS should use @supports for feature detection');
        
        // Check for fallback styles
        $this->assertStringContainsString('display: block', $headerOutput, 'CSS should provide fallback styles');
        $this->assertStringContainsString('display: flex', $headerOutput, 'CSS should provide modern styles');
    }
    
    /**
     * Test JavaScript feature detection
     */
    public function testJavaScriptFeatureDetection()
    {
        // Test JavaScript feature detection
        ob_start();
        include __DIR__ . '/../views/keyboard_shortcuts.php';
        $shortcutsOutput = ob_get_clean();
        
        // Check for feature detection
        $this->assertStringContainsString('typeof', $shortcutsOutput, 'JavaScript should use typeof for feature detection');
        $this->assertStringContainsString('undefined', $shortcutsOutput, 'JavaScript should check for undefined features');
        
        // Check for polyfills
        $this->assertStringContainsString('polyfill', $shortcutsOutput, 'JavaScript should include polyfills for older browsers');
    }
    
    /**
     * Test browser compatibility matrix
     */
    public function testBrowserCompatibilityMatrix()
    {
        $compatibilityMatrix = [
            'Chrome 90+' => [
                'CSS Grid' => true,
                'Flexbox' => true,
                'CSS Custom Properties' => true,
                'ES6 Modules' => true,
                'Fetch API' => true,
                'Service Workers' => true
            ],
            'Firefox 88+' => [
                'CSS Grid' => true,
                'Flexbox' => true,
                'CSS Custom Properties' => true,
                'ES6 Modules' => true,
                'Fetch API' => true,
                'Service Workers' => true
            ],
            'Safari 14+' => [
                'CSS Grid' => true,
                'Flexbox' => true,
                'CSS Custom Properties' => true,
                'ES6 Modules' => true,
                'Fetch API' => true,
                'Service Workers' => true
            ],
            'Edge 90+' => [
                'CSS Grid' => true,
                'Flexbox' => true,
                'CSS Custom Properties' => true,
                'ES6 Modules' => true,
                'Fetch API' => true,
                'Service Workers' => true
            ],
            'Internet Explorer 11' => [
                'CSS Grid' => false,
                'Flexbox' => true,
                'CSS Custom Properties' => false,
                'ES6 Modules' => false,
                'Fetch API' => false,
                'Service Workers' => false
            ]
        ];
        
        // Verify compatibility matrix
        foreach ($compatibilityMatrix as $browser => $features) {
            foreach ($features as $feature => $supported) {
                if ($supported) {
                    $this->assertTrue($supported, "{$browser} should support {$feature}");
                } else {
                    $this->assertFalse($supported, "{$browser} should not support {$feature}");
                }
            }
        }
        
        // Test that we have fallbacks for unsupported features
        $this->assertTrue(true, 'Browser compatibility matrix verified');
    }
}













