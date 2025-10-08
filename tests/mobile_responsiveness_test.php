<?php
/**
 * Mobile Responsiveness Tests
 * Tests application responsiveness across different mobile devices and screen sizes
 */

require_once __DIR__ . '/../lib/data.php';
require_once __DIR__ . '/../vendor/autoload.php';

class MobileResponsivenessTest extends PHPUnit\Framework\TestCase
{
    private $dataStore;
    private $testDataFile;
    private $mobileBreakpoints = [
        'mobile-small' => ['width' => 320, 'height' => 568], // iPhone SE
        'mobile-medium' => ['width' => 375, 'height' => 667], // iPhone 8
        'mobile-large' => ['width' => 414, 'height' => 896], // iPhone 11 Pro Max
        'tablet-portrait' => ['width' => 768, 'height' => 1024], // iPad
        'tablet-landscape' => ['width' => 1024, 'height' => 768], // iPad landscape
        'desktop-small' => ['width' => 1280, 'height' => 720], // Small desktop
        'desktop-large' => ['width' => 1920, 'height' => 1080] // Large desktop
    ];
    
    protected function setUp(): void
    {
        // Create a test data file
        $this->testDataFile = __DIR__ . '/../data/test_mobile.json';
        
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
     * Test viewport meta tag configuration
     */
    public function testViewportMetaTagConfiguration()
    {
        ob_start();
        include __DIR__ . '/../views/layout_header.php';
        $headerOutput = ob_get_clean();
        
        // Check for viewport meta tag
        $this->assertStringContainsString('viewport', $headerOutput, 'HTML should include viewport meta tag');
        $this->assertStringContainsString('width=device-width', $headerOutput, 'Viewport should set width to device width');
        $this->assertStringContainsString('initial-scale=1', $headerOutput, 'Viewport should set initial scale to 1');
        $this->assertStringContainsString('maximum-scale=1', $headerOutput, 'Viewport should set maximum scale to 1');
        $this->assertStringContainsString('user-scalable=no', $headerOutput, 'Viewport should disable user scaling');
    }
    
    /**
     * Test responsive CSS breakpoints
     */
    public function testResponsiveCSSBreakpoints()
    {
        // Test sidebar responsive CSS
        ob_start();
        include __DIR__ . '/../views/sidebar.php';
        $sidebarOutput = ob_get_clean();
        
        // Check for media queries
        $this->assertStringContainsString('@media', $sidebarOutput, 'CSS should include media queries for responsive design');
        $this->assertStringContainsString('max-width', $sidebarOutput, 'CSS should have max-width breakpoints');
        $this->assertStringContainsString('min-width', $sidebarOutput, 'CSS should have min-width breakpoints');
        
        // Test client card responsive CSS
        ob_start();
        include __DIR__ . '/../views/client_card.php';
        $cardOutput = ob_get_clean();
        
        // Check for mobile-specific styles
        $this->assertStringContainsString('768px', $cardOutput, 'CSS should have mobile breakpoint at 768px');
        $this->assertStringContainsString('flex-direction: column', $cardOutput, 'CSS should stack elements vertically on mobile');
        
        // Test project detail responsive CSS
        ob_start();
        include __DIR__ . '/../views/project.php';
        $projectOutput = ob_get_clean();
        
        // Check for responsive layout
        $this->assertStringContainsString('@media', $projectOutput, 'CSS should include media queries');
        $this->assertStringContainsString('flex-direction: column', $projectOutput, 'CSS should stack elements vertically on mobile');
    }
    
    /**
     * Test touch-friendly interface elements
     */
    public function testTouchFriendlyInterfaceElements()
    {
        // Test button sizing
        ob_start();
        include __DIR__ . '/../views/client_card.php';
        $cardOutput = ob_get_clean();
        
        // Check for touch-friendly button sizes
        $this->assertStringContainsString('min-height: 44px', $cardOutput, 'Buttons should have minimum height of 44px for touch');
        $this->assertStringContainsString('min-width: 44px', $cardOutput, 'Buttons should have minimum width of 44px for touch');
        
        // Test input field sizing
        ob_start();
        include __DIR__ . '/../views/clients.php';
        $clientsOutput = ob_get_clean();
        
        // Check for touch-friendly input sizes
        $this->assertStringContainsString('min-height: 44px', $clientsOutput, 'Input fields should have minimum height of 44px for touch');
        $this->assertStringContainsString('font-size: 16px', $clientsOutput, 'Input fields should have font size of 16px to prevent zoom on iOS');
        
        // Test link spacing
        $this->assertStringContainsString('padding: 12px', $clientsOutput, 'Links should have adequate padding for touch');
    }
    
    /**
     * Test mobile navigation patterns
     */
    public function testMobileNavigationPatterns()
    {
        // Test sidebar mobile behavior
        ob_start();
        include __DIR__ . '/../views/sidebar.php';
        $sidebarOutput = ob_get_clean();
        
        // Check for mobile navigation
        $this->assertStringContainsString('@media', $sidebarOutput, 'Sidebar should have mobile styles');
        $this->assertStringContainsString('position: fixed', $sidebarOutput, 'Sidebar should be fixed on mobile');
        $this->assertStringContainsString('z-index', $sidebarOutput, 'Sidebar should have proper z-index for mobile overlay');
        
        // Test hamburger menu (if implemented)
        $this->assertStringContainsString('menu', $sidebarOutput, 'Mobile should have menu toggle');
        $this->assertStringContainsString('toggle', $sidebarOutput, 'Mobile should have toggle functionality');
    }
    
    /**
     * Test mobile form interactions
     */
    public function testMobileFormInteractions()
    {
        // Test form mobile behavior
        ob_start();
        include __DIR__ . '/../views/clients.php';
        $clientsOutput = ob_get_clean();
        
        // Check for mobile form optimizations
        $this->assertStringContainsString('inputmode', $clientsOutput, 'Inputs should have inputmode for mobile keyboards');
        $this->assertStringContainsString('autocomplete', $clientsOutput, 'Inputs should have autocomplete for mobile');
        $this->assertStringContainsString('autocapitalize', $clientsOutput, 'Inputs should have autocapitalize for mobile');
        
        // Check for mobile-specific input types
        $this->assertStringContainsString('type="email"', $clientsOutput, 'Email inputs should use email type for mobile keyboards');
        $this->assertStringContainsString('type="tel"', $clientsOutput, 'Phone inputs should use tel type for mobile keyboards');
    }
    
    /**
     * Test mobile typography and readability
     */
    public function testMobileTypographyAndReadability()
    {
        // Test typography scaling
        ob_start();
        include __DIR__ . '/../views/client_card.php';
        $cardOutput = ob_get_clean();
        
        // Check for readable font sizes
        $this->assertStringContainsString('font-size: 16px', $cardOutput, 'Base font size should be 16px for mobile readability');
        $this->assertStringContainsString('line-height: 1.5', $cardOutput, 'Line height should be 1.5 for mobile readability');
        
        // Test heading hierarchy
        ob_start();
        include __DIR__ . '/../views/project.php';
        $projectOutput = ob_get_clean();
        
        // Check for proper heading sizes
        $this->assertStringContainsString('h1', $projectOutput, 'Page should have h1 heading');
        $this->assertStringContainsString('h2', $projectOutput, 'Page should have h2 headings');
        $this->assertStringContainsString('h3', $projectOutput, 'Page should have h3 headings');
    }
    
    /**
     * Test mobile performance optimizations
     */
    public function testMobilePerformanceOptimizations()
    {
        // Test image optimization
        ob_start();
        include __DIR__ . '/../views/sidebar.php';
        $sidebarOutput = ob_get_clean();
        
        // Check for responsive images
        $this->assertStringContainsString('srcset', $sidebarOutput, 'Images should use srcset for responsive loading');
        $this->assertStringContainsString('sizes', $sidebarOutput, 'Images should use sizes attribute for responsive loading');
        
        // Test lazy loading
        $this->assertStringContainsString('loading="lazy"', $sidebarOutput, 'Images should use lazy loading for mobile performance');
        
        // Test CSS optimization
        ob_start();
        include __DIR__ . '/../views/client_card.php';
        $cardOutput = ob_get_clean();
        
        // Check for efficient CSS
        $this->assertStringContainsString('transform', $cardOutput, 'CSS should use transform for animations (GPU accelerated)');
        $this->assertStringContainsString('will-change', $cardOutput, 'CSS should use will-change for performance hints');
    }
    
    /**
     * Test mobile gesture support
     */
    public function testMobileGestureSupport()
    {
        // Test touch event handling
        ob_start();
        include __DIR__ . '/../views/client_card.php';
        $cardOutput = ob_get_clean();
        
        // Check for touch event support
        $this->assertStringContainsString('touchstart', $cardOutput, 'JavaScript should handle touchstart events');
        $this->assertStringContainsString('touchend', $cardOutput, 'JavaScript should handle touchend events');
        $this->assertStringContainsString('touchmove', $cardOutput, 'JavaScript should handle touchmove events');
        
        // Test swipe gestures
        $this->assertStringContainsString('swipe', $cardOutput, 'JavaScript should support swipe gestures');
        $this->assertStringContainsString('gesture', $cardOutput, 'JavaScript should support gesture events');
    }
    
    /**
     * Test mobile-specific UI patterns
     */
    public function testMobileSpecificUIPatterns()
    {
        // Test modal mobile behavior
        ob_start();
        include __DIR__ . '/../views/clients.php';
        $clientsOutput = ob_get_clean();
        
        // Check for mobile modal patterns
        $this->assertStringContainsString('modal', $clientsOutput, 'Mobile should have modal patterns');
        $this->assertStringContainsString('fullscreen', $clientsOutput, 'Modals should be fullscreen on mobile');
        $this->assertStringContainsString('backdrop', $clientsOutput, 'Modals should have backdrop on mobile');
        
        // Test mobile-specific buttons
        $this->assertStringContainsString('floating', $clientsOutput, 'Mobile should have floating action buttons');
        $this->assertStringContainsString('fab', $clientsOutput, 'Mobile should have FAB (Floating Action Button)');
    }
    
    /**
     * Test mobile accessibility features
     */
    public function testMobileAccessibilityFeatures()
    {
        // Test mobile accessibility
        ob_start();
        include __DIR__ . '/../views/sidebar.php';
        $sidebarOutput = ob_get_clean();
        
        // Check for mobile accessibility
        $this->assertStringContainsString('aria-label', $sidebarOutput, 'Mobile elements should have aria-label');
        $this->assertStringContainsString('role="button"', $sidebarOutput, 'Mobile interactive elements should have button role');
        $this->assertStringContainsString('tabindex', $sidebarOutput, 'Mobile elements should have proper tabindex');
        
        // Test mobile screen reader support
        ob_start();
        include __DIR__ . '/../views/client_card.php';
        $cardOutput = ob_get_clean();
        
        // Check for screen reader support
        $this->assertStringContainsString('aria-describedby', $cardOutput, 'Mobile elements should have aria-describedby');
        $this->assertStringContainsString('aria-expanded', $cardOutput, 'Mobile collapsible elements should have aria-expanded');
    }
    
    /**
     * Test mobile data usage optimization
     */
    public function testMobileDataUsageOptimization()
    {
        // Test data usage optimization
        ob_start();
        include __DIR__ . '/../views/loading.php';
        $loadingOutput = ob_get_clean();
        
        // Check for loading optimization
        $this->assertStringContainsString('loading', $loadingOutput, 'Mobile should have loading states');
        $this->assertStringContainsString('skeleton', $loadingOutput, 'Mobile should use skeleton loading');
        $this->assertStringContainsString('placeholder', $loadingOutput, 'Mobile should use placeholders');
        
        // Test progressive loading
        ob_start();
        include __DIR__ . '/../views/clients.php';
        $clientsOutput = ob_get_clean();
        
        // Check for progressive loading
        $this->assertStringContainsString('lazy', $clientsOutput, 'Mobile should use lazy loading');
        $this->assertStringContainsString('defer', $clientsOutput, 'Mobile should defer non-critical resources');
    }
    
    /**
     * Test mobile orientation handling
     */
    public function testMobileOrientationHandling()
    {
        // Test orientation handling
        ob_start();
        include __DIR__ . '/../views/layout_header.php';
        $headerOutput = ob_get_clean();
        
        // Check for orientation support
        $this->assertStringContainsString('orientation', $headerOutput, 'CSS should handle orientation changes');
        $this->assertStringContainsString('portrait', $headerOutput, 'CSS should have portrait orientation styles');
        $this->assertStringContainsString('landscape', $headerOutput, 'CSS should have landscape orientation styles');
        
        // Test orientation JavaScript
        ob_start();
        include __DIR__ . '/../views/keyboard_shortcuts.php';
        $shortcutsOutput = ob_get_clean();
        
        // Check for orientation event handling
        $this->assertStringContainsString('orientationchange', $shortcutsOutput, 'JavaScript should handle orientation changes');
        $this->assertStringContainsString('resize', $shortcutsOutput, 'JavaScript should handle resize events');
    }
    
    /**
     * Test mobile-specific CSS features
     */
    public function testMobileSpecificCSSFeatures()
    {
        // Test mobile CSS features
        ob_start();
        include __DIR__ . '/../views/client_card.php';
        $cardOutput = ob_get_clean();
        
        // Check for mobile-specific CSS
        $this->assertStringContainsString('-webkit-tap-highlight-color', $cardOutput, 'CSS should disable tap highlight on mobile');
        $this->assertStringContainsString('-webkit-touch-callout', $cardOutput, 'CSS should disable touch callout on mobile');
        $this->assertStringContainsString('-webkit-user-select', $cardOutput, 'CSS should control user selection on mobile');
        
        // Test mobile scrolling
        $this->assertStringContainsString('-webkit-overflow-scrolling', $cardOutput, 'CSS should enable smooth scrolling on mobile');
        $this->assertStringContainsString('overscroll-behavior', $cardOutput, 'CSS should control overscroll behavior on mobile');
    }
    
    /**
     * Test mobile form validation
     */
    public function testMobileFormValidation()
    {
        // Test mobile form validation
        ob_start();
        include __DIR__ . '/../views/clients.php';
        $clientsOutput = ob_get_clean();
        
        // Check for mobile form validation
        $this->assertStringContainsString('novalidate', $clientsOutput, 'Forms should use novalidate for custom validation');
        $this->assertStringContainsString('pattern', $clientsOutput, 'Inputs should use pattern for mobile validation');
        $this->assertStringContainsString('title', $clientsOutput, 'Inputs should have title for mobile validation messages');
        
        // Test mobile-specific validation
        $this->assertStringContainsString('inputmode', $clientsOutput, 'Inputs should have inputmode for mobile keyboards');
        $this->assertStringContainsString('autocomplete', $clientsOutput, 'Inputs should have autocomplete for mobile');
    }
    
    /**
     * Test mobile performance metrics
     */
    public function testMobilePerformanceMetrics()
    {
        // Test mobile performance
        $performanceMetrics = [
            'First Contentful Paint' => '< 1.5s',
            'Largest Contentful Paint' => '< 2.5s',
            'First Input Delay' => '< 100ms',
            'Cumulative Layout Shift' => '< 0.1',
            'Time to Interactive' => '< 3.5s'
        ];
        
        // Verify performance metrics are defined
        foreach ($performanceMetrics as $metric => $target) {
            $this->assertNotEmpty($target, "Performance metric {$metric} should have a target value");
        }
        
        // Test mobile-specific optimizations
        ob_start();
        include __DIR__ . '/../views/loading.php';
        $loadingOutput = ob_get_clean();
        
        // Check for performance optimizations
        $this->assertStringContainsString('preload', $loadingOutput, 'Mobile should use preload for critical resources');
        $this->assertStringContainsString('prefetch', $loadingOutput, 'Mobile should use prefetch for non-critical resources');
    }
    
    /**
     * Test mobile breakpoint consistency
     */
    public function testMobileBreakpointConsistency()
    {
        $expectedBreakpoints = [
            'mobile-small' => '320px',
            'mobile-medium' => '375px',
            'mobile-large' => '414px',
            'tablet-portrait' => '768px',
            'tablet-landscape' => '1024px',
            'desktop-small' => '1280px',
            'desktop-large' => '1920px'
        ];
        
        // Test that breakpoints are consistently used
        ob_start();
        include __DIR__ . '/../views/client_card.php';
        $cardOutput = ob_get_clean();
        
        // Check for consistent breakpoint usage
        $this->assertStringContainsString('768px', $cardOutput, 'CSS should use consistent mobile breakpoint');
        $this->assertStringContainsString('1024px', $cardOutput, 'CSS should use consistent tablet breakpoint');
        
        // Verify breakpoint values
        foreach ($expectedBreakpoints as $breakpoint => $value) {
            $this->assertNotEmpty($value, "Breakpoint {$breakpoint} should have a defined value");
        }
    }
    
    /**
     * Test mobile-specific JavaScript features
     */
    public function testMobileSpecificJavaScriptFeatures()
    {
        // Test mobile JavaScript features
        ob_start();
        include __DIR__ . '/../views/keyboard_shortcuts.php';
        $shortcutsOutput = ob_get_clean();
        
        // Check for mobile JavaScript features
        $this->assertStringContainsString('touchstart', $shortcutsOutput, 'JavaScript should handle touch events');
        $this->assertStringContainsString('touchend', $shortcutsOutput, 'JavaScript should handle touch events');
        $this->assertStringContainsString('touchmove', $shortcutsOutput, 'JavaScript should handle touch events');
        
        // Test mobile-specific APIs
        $this->assertStringContainsString('navigator', $shortcutsOutput, 'JavaScript should use navigator API for mobile detection');
        $this->assertStringContainsString('userAgent', $shortcutsOutput, 'JavaScript should detect mobile user agents');
    }
}
