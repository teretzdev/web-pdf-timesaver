<?php
/**
 * Dark Mode Functionality Tests
 * Tests dark mode implementation, theme switching, and persistence
 */

require_once __DIR__ . '/../mvp/lib/data.php';
require_once __DIR__ . '/../vendor/autoload.php';

class DarkModeFunctionalityTest extends PHPUnit\Framework\TestCase
{
    private $dataStore;
    private $testDataFile;
    
    protected function setUp(): void
    {
        // Create a test data file
        $this->testDataFile = __DIR__ . '/../data/test_dark_mode.json';
        
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
     * Test dark mode toggle component
     */
    public function testDarkModeToggleComponent()
    {
        ob_start();
        include __DIR__ . '/../mvp/views/dark_mode.php';
        $darkModeOutput = ob_get_clean();
        
        // Check for dark mode toggle
        $this->assertStringContainsString('dark-mode-toggle', $darkModeOutput, 'Dark mode should have toggle component');
        $this->assertStringContainsString('dark-mode', $darkModeOutput, 'Dark mode should have dark-mode class');
        $this->assertStringContainsString('data-theme', $darkModeOutput, 'Dark mode should have data-theme attribute');
        
        // Check for toggle button
        $this->assertStringContainsString('button', $darkModeOutput, 'Dark mode should have toggle button');
        $this->assertStringContainsString('onclick', $darkModeOutput, 'Dark mode toggle should have onclick handler');
        
        // Check for theme icons
        $this->assertStringContainsString('sun', $darkModeOutput, 'Dark mode should have sun icon for light mode');
        $this->assertStringContainsString('moon', $darkModeOutput, 'Dark mode should have moon icon for dark mode');
    }
    
    /**
     * Test CSS custom properties for theming
     */
    public function testCSSCustomPropertiesForTheming()
    {
        ob_start();
        include __DIR__ . '/../mvp/views/dark_mode.php';
        $darkModeOutput = ob_get_clean();
        
        // Check for CSS custom properties
        $this->assertStringContainsString('--', $darkModeOutput, 'CSS should use custom properties for theming');
        $this->assertStringContainsString('var(', $darkModeOutput, 'CSS should use var() function for custom properties');
        
        // Check for theme-specific variables
        $this->assertStringContainsString('--bg-color', $darkModeOutput, 'CSS should have background color variable');
        $this->assertStringContainsString('--text-color', $darkModeOutput, 'CSS should have text color variable');
        $this->assertStringContainsString('--border-color', $darkModeOutput, 'CSS should have border color variable');
        $this->assertStringContainsString('--accent-color', $darkModeOutput, 'CSS should have accent color variable');
        
        // Check for fallback values
        $this->assertStringContainsString(',', $darkModeOutput, 'CSS custom properties should have fallback values');
    }
    
    /**
     * Test theme switching functionality
     */
    public function testThemeSwitchingFunctionality()
    {
        ob_start();
        include __DIR__ . '/../mvp/views/dark_mode.php';
        $darkModeOutput = ob_get_clean();
        
        // Check for theme switching JavaScript
        $this->assertStringContainsString('toggleTheme', $darkModeOutput, 'JavaScript should have toggleTheme function');
        $this->assertStringContainsString('setTheme', $darkModeOutput, 'JavaScript should have setTheme function');
        $this->assertStringContainsString('getTheme', $darkModeOutput, 'JavaScript should have getTheme function');
        
        // Check for theme state management
        $this->assertStringContainsString('localStorage', $darkModeOutput, 'JavaScript should use localStorage for theme persistence');
        $this->assertStringContainsString('setItem', $darkModeOutput, 'JavaScript should save theme to localStorage');
        $this->assertStringContainsString('getItem', $darkModeOutput, 'JavaScript should load theme from localStorage');
        
        // Check for theme application
        $this->assertStringContainsString('document.documentElement', $darkModeOutput, 'JavaScript should apply theme to document element');
        $this->assertStringContainsString('setAttribute', $darkModeOutput, 'JavaScript should set theme attribute');
    }
    
    /**
     * Test theme persistence
     */
    public function testThemePersistence()
    {
        ob_start();
        include __DIR__ . '/../mvp/views/dark_mode.php';
        $darkModeOutput = ob_get_clean();
        
        // Check for theme persistence
        $this->assertStringContainsString('localStorage', $darkModeOutput, 'Theme should be persisted in localStorage');
        $this->assertStringContainsString('theme', $darkModeOutput, 'Theme should be stored with theme key');
        
        // Check for theme loading on page load
        $this->assertStringContainsString('DOMContentLoaded', $darkModeOutput, 'Theme should be loaded on page load');
        $this->assertStringContainsString('loadTheme', $darkModeOutput, 'JavaScript should have loadTheme function');
        
        // Check for default theme
        $this->assertStringContainsString('light', $darkModeOutput, 'Default theme should be light');
        $this->assertStringContainsString('dark', $darkModeOutput, 'Dark theme should be available');
    }
    
    /**
     * Test system theme detection
     */
    public function testSystemThemeDetection()
    {
        ob_start();
        include __DIR__ . '/../mvp/views/dark_mode.php';
        $darkModeOutput = ob_get_clean();
        
        // Check for system theme detection
        $this->assertStringContainsString('prefers-color-scheme', $darkModeOutput, 'CSS should detect system color scheme preference');
        $this->assertStringContainsString('matchMedia', $darkModeOutput, 'JavaScript should use matchMedia for system theme detection');
        
        // Check for system theme listeners
        $this->assertStringContainsString('addEventListener', $darkModeOutput, 'JavaScript should listen for system theme changes');
        $this->assertStringContainsString('change', $darkModeOutput, 'JavaScript should handle system theme change events');
        
        // Check for system theme fallback
        $this->assertStringContainsString('system', $darkModeOutput, 'System theme should be available as option');
    }
    
    /**
     * Test theme-specific styling
     */
    public function testThemeSpecificStyling()
    {
        // Test sidebar dark mode styling
        ob_start();
        include __DIR__ . '/../mvp/views/sidebar.php';
        $sidebarOutput = ob_get_clean();
        
        // Check for dark mode styles
        $this->assertStringContainsString('[data-theme="dark"]', $sidebarOutput, 'CSS should have dark theme styles');
        $this->assertStringContainsString('[data-theme="light"]', $sidebarOutput, 'CSS should have light theme styles');
        
        // Test client card dark mode styling
        ob_start();
        include __DIR__ . '/../mvp/views/client_card.php';
        $cardOutput = ob_get_clean();
        
        // Check for theme-specific colors
        $this->assertStringContainsString('background-color', $cardOutput, 'CSS should define background colors for themes');
        $this->assertStringContainsString('color', $cardOutput, 'CSS should define text colors for themes');
        $this->assertStringContainsString('border-color', $cardOutput, 'CSS should define border colors for themes');
        
        // Test project detail dark mode styling
        ob_start();
        include __DIR__ . '/../mvp/views/project.php';
        $projectOutput = ob_get_clean();
        
        // Check for theme-specific styling
        $this->assertStringContainsString('--bg-color', $projectOutput, 'CSS should use custom properties for theming');
        $this->assertStringContainsString('--text-color', $projectOutput, 'CSS should use custom properties for theming');
    }
    
    /**
     * Test dark mode accessibility
     */
    public function testDarkModeAccessibility()
    {
        ob_start();
        include __DIR__ . '/../mvp/views/dark_mode.php';
        $darkModeOutput = ob_get_clean();
        
        // Check for accessibility features
        $this->assertStringContainsString('aria-label', $darkModeOutput, 'Dark mode toggle should have aria-label');
        $this->assertStringContainsString('role="button"', $darkModeOutput, 'Dark mode toggle should have button role');
        $this->assertStringContainsString('tabindex', $darkModeOutput, 'Dark mode toggle should be keyboard accessible');
        
        // Check for screen reader support
        $this->assertStringContainsString('sr-only', $darkModeOutput, 'Dark mode should have screen reader only text');
        $this->assertStringContainsString('aria-pressed', $darkModeOutput, 'Dark mode toggle should have aria-pressed state');
        
        // Check for keyboard navigation
        $this->assertStringContainsString('keydown', $darkModeOutput, 'Dark mode toggle should support keyboard navigation');
        $this->assertStringContainsString('Enter', $darkModeOutput, 'Dark mode toggle should respond to Enter key');
        $this->assertStringContainsString('Space', $darkModeOutput, 'Dark mode toggle should respond to Space key');
    }
    
    /**
     * Test dark mode performance
     */
    public function testDarkModePerformance()
    {
        ob_start();
        include __DIR__ . '/../mvp/views/dark_mode.php';
        $darkModeOutput = ob_get_clean();
        
        // Check for performance optimizations
        $this->assertStringContainsString('requestAnimationFrame', $darkModeOutput, 'Theme switching should use requestAnimationFrame for smooth transitions');
        $this->assertStringContainsString('transition', $darkModeOutput, 'CSS should have smooth transitions for theme changes');
        
        // Check for efficient theme switching
        $this->assertStringContainsString('classList', $darkModeOutput, 'JavaScript should use classList for efficient DOM manipulation');
        $this->assertStringContainsString('toggle', $darkModeOutput, 'JavaScript should use toggle for efficient class management');
        
        // Check for minimal reflow
        $this->assertStringContainsString('transform', $darkModeOutput, 'CSS should use transform for minimal reflow');
        $this->assertStringContainsString('opacity', $darkModeOutput, 'CSS should use opacity for smooth transitions');
    }
    
    /**
     * Test dark mode color contrast
     */
    public function testDarkModeColorContrast()
    {
        ob_start();
        include __DIR__ . '/../mvp/views/dark_mode.php';
        $darkModeOutput = ob_get_clean();
        
        // Check for color contrast compliance
        $this->assertStringContainsString('contrast', $darkModeOutput, 'Dark mode should consider color contrast');
        $this->assertStringContainsString('WCAG', $darkModeOutput, 'Dark mode should comply with WCAG guidelines');
        
        // Check for high contrast mode support
        $this->assertStringContainsString('high-contrast', $darkModeOutput, 'Dark mode should support high contrast mode');
        $this->assertStringContainsString('prefers-contrast', $darkModeOutput, 'CSS should detect high contrast preference');
        
        // Check for color contrast ratios
        $this->assertStringContainsString('4.5:1', $darkModeOutput, 'Dark mode should meet 4.5:1 contrast ratio for normal text');
        $this->assertStringContainsString('3:1', $darkModeOutput, 'Dark mode should meet 3:1 contrast ratio for large text');
    }
    
    /**
     * Test dark mode animations
     */
    public function testDarkModeAnimations()
    {
        ob_start();
        include __DIR__ . '/../mvp/views/dark_mode.php';
        $darkModeOutput = ob_get_clean();
        
        // Check for smooth theme transitions
        $this->assertStringContainsString('transition', $darkModeOutput, 'CSS should have smooth transitions for theme changes');
        $this->assertStringContainsString('duration', $darkModeOutput, 'CSS should define transition duration');
        $this->assertStringContainsString('ease', $darkModeOutput, 'CSS should use easing for smooth transitions');
        
        // Check for theme change animations
        $this->assertStringContainsString('@keyframes', $darkModeOutput, 'CSS should have keyframe animations for theme changes');
        $this->assertStringContainsString('fadeIn', $darkModeOutput, 'CSS should have fade in animation');
        $this->assertStringContainsString('fadeOut', $darkModeOutput, 'CSS should have fade out animation');
        
        // Check for reduced motion support
        $this->assertStringContainsString('prefers-reduced-motion', $darkModeOutput, 'CSS should respect reduced motion preference');
        $this->assertStringContainsString('no-preference', $darkModeOutput, 'CSS should handle no motion preference');
    }
    
    /**
     * Test dark mode state management
     */
    public function testDarkModeStateManagement()
    {
        ob_start();
        include __DIR__ . '/../mvp/views/dark_mode.php';
        $darkModeOutput = ob_get_clean();
        
        // Check for state management
        $this->assertStringContainsString('themeState', $darkModeOutput, 'JavaScript should manage theme state');
        $this->assertStringContainsString('currentTheme', $darkModeOutput, 'JavaScript should track current theme');
        $this->assertStringContainsString('previousTheme', $darkModeOutput, 'JavaScript should track previous theme');
        
        // Check for state persistence
        $this->assertStringContainsString('saveState', $darkModeOutput, 'JavaScript should save theme state');
        $this->assertStringContainsString('loadState', $darkModeOutput, 'JavaScript should load theme state');
        $this->assertStringContainsString('resetState', $darkModeOutput, 'JavaScript should reset theme state');
        
        // Check for state validation
        $this->assertStringContainsString('validateTheme', $darkModeOutput, 'JavaScript should validate theme state');
        $this->assertStringContainsString('isValidTheme', $darkModeOutput, 'JavaScript should check if theme is valid');
    }
    
    /**
     * Test dark mode error handling
     */
    public function testDarkModeErrorHandling()
    {
        ob_start();
        include __DIR__ . '/../mvp/views/dark_mode.php';
        $darkModeOutput = ob_get_clean();
        
        // Check for error handling
        $this->assertStringContainsString('try', $darkModeOutput, 'JavaScript should handle errors gracefully');
        $this->assertStringContainsString('catch', $darkModeOutput, 'JavaScript should catch and handle errors');
        $this->assertStringContainsString('finally', $darkModeOutput, 'JavaScript should have finally block for cleanup');
        
        // Check for fallback behavior
        $this->assertStringContainsString('fallback', $darkModeOutput, 'JavaScript should have fallback behavior');
        $this->assertStringContainsString('default', $darkModeOutput, 'JavaScript should use default theme on error');
        $this->assertStringContainsString('light', $darkModeOutput, 'JavaScript should fallback to light theme');
        
        // Check for error logging
        $this->assertStringContainsString('console.error', $darkModeOutput, 'JavaScript should log errors to console');
        $this->assertStringContainsString('console.warn', $darkModeOutput, 'JavaScript should warn about issues');
    }
    
    /**
     * Test dark mode integration with other components
     */
    public function testDarkModeIntegrationWithOtherComponents()
    {
        // Test sidebar integration
        ob_start();
        include __DIR__ . '/../mvp/views/sidebar.php';
        $sidebarOutput = ob_get_clean();
        
        // Check for dark mode integration
        $this->assertStringContainsString('data-theme', $sidebarOutput, 'Sidebar should support dark mode');
        $this->assertStringContainsString('--bg-color', $sidebarOutput, 'Sidebar should use theme variables');
        
        // Test client card integration
        ob_start();
        include __DIR__ . '/../mvp/views/client_card.php';
        $cardOutput = ob_get_clean();
        
        // Check for dark mode integration
        $this->assertStringContainsString('data-theme', $cardOutput, 'Client cards should support dark mode');
        $this->assertStringContainsString('--text-color', $cardOutput, 'Client cards should use theme variables');
        
        // Test project detail integration
        ob_start();
        include __DIR__ . '/../mvp/views/project.php';
        $projectOutput = ob_get_clean();
        
        // Check for dark mode integration
        $this->assertStringContainsString('data-theme', $projectOutput, 'Project details should support dark mode');
        $this->assertStringContainsString('--border-color', $projectOutput, 'Project details should use theme variables');
    }
    
    /**
     * Test dark mode customization
     */
    public function testDarkModeCustomization()
    {
        ob_start();
        include __DIR__ . '/../mvp/views/dark_mode.php';
        $darkModeOutput = ob_get_clean();
        
        // Check for customization options
        $this->assertStringContainsString('custom', $darkModeOutput, 'Dark mode should support customization');
        $this->assertStringContainsString('user-defined', $darkModeOutput, 'Dark mode should support user-defined themes');
        $this->assertStringContainsString('theme-editor', $darkModeOutput, 'Dark mode should have theme editor');
        
        // Check for theme presets
        $this->assertStringContainsString('preset', $darkModeOutput, 'Dark mode should have theme presets');
        $this->assertStringContainsString('default', $darkModeOutput, 'Dark mode should have default preset');
        $this->assertStringContainsString('high-contrast', $darkModeOutput, 'Dark mode should have high contrast preset');
        
        // Check for theme import/export
        $this->assertStringContainsString('import', $darkModeOutput, 'Dark mode should support theme import');
        $this->assertStringContainsString('export', $darkModeOutput, 'Dark mode should support theme export');
        $this->assertStringContainsString('JSON', $darkModeOutput, 'Dark mode should use JSON for theme data');
    }
    
    /**
     * Test dark mode browser compatibility
     */
    public function testDarkModeBrowserCompatibility()
    {
        ob_start();
        include __DIR__ . '/../mvp/views/dark_mode.php';
        $darkModeOutput = ob_get_clean();
        
        // Check for browser compatibility
        $this->assertStringContainsString('feature-detect', $darkModeOutput, 'Dark mode should detect browser features');
        $this->assertStringContainsString('supports', $darkModeOutput, 'Dark mode should check for CSS support');
        $this->assertStringContainsString('fallback', $darkModeOutput, 'Dark mode should have fallback for unsupported browsers');
        
        // Check for polyfills
        $this->assertStringContainsString('polyfill', $darkModeOutput, 'Dark mode should include polyfills for older browsers');
        $this->assertStringContainsString('shim', $darkModeOutput, 'Dark mode should include shims for older browsers');
        
        // Check for graceful degradation
        $this->assertStringContainsString('graceful', $darkModeOutput, 'Dark mode should degrade gracefully');
        $this->assertStringContainsString('progressive', $darkModeOutput, 'Dark mode should use progressive enhancement');
    }
}
