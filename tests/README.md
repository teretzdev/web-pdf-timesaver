# Comprehensive Test Suite

This directory contains a comprehensive test suite for the Clio Draft application, covering all aspects of functionality, performance, and accessibility.

## Test Suites

### 1. Integration Tests (`integration_client_project_workflow_test.php`)
Tests the complete user journey from client creation to project management.

**Coverage:**
- Client creation workflow
- Client status updates
- Project creation workflow
- Project status updates
- Project name updates
- Project duplication
- Document addition to projects
- Client deletion (cascade to projects and documents)
- Project deletion (cascade to documents)
- Client search functionality
- Client sorting functionality
- Complete end-to-end workflow

### 2. UI Components Tests (`ui_components_test.php`)
Tests all new interface components and their rendering.

**Coverage:**
- Sidebar component rendering
- Client card component rendering
- Clients view rendering
- Client detail view rendering
- Project detail view rendering
- Breadcrumb component rendering
- Loading component rendering
- Keyboard shortcuts component rendering
- Dark mode component rendering
- CSS classes and styling
- JavaScript functionality
- Responsive design classes

### 3. API Routes Tests (`api_routes_test.php`)
Tests all new routes and actions for proper functionality.

**Coverage:**
- Client status update route
- Client deletion route
- Project name update route
- Project status update route
- Project duplication route
- Create client route
- Create project route
- GET route handling
- Route parameter validation
- POST request validation
- Data persistence after route actions
- Error handling for invalid routes
- Concurrent route handling

### 4. Performance Tests (`performance_large_datasets_test.php`)
Tests application performance with large datasets (1000+ records).

**Coverage:**
- Client list loading performance
- Client filtering performance
- Client search performance
- Client sorting performance
- Project loading performance
- Project filtering by client performance
- Document loading performance
- Client creation performance
- Client update performance
- Client deletion performance
- Memory usage with large dataset
- Concurrent operations performance
- Pagination performance
- Data file size and loading time

### 5. Accessibility Tests (`accessibility_test.php`)
Tests keyboard navigation, screen reader compatibility, and WCAG compliance.

**Coverage:**
- Keyboard navigation support
- ARIA attributes and roles
- Screen reader compatibility
- Color contrast and visual accessibility
- Semantic HTML structure
- Error handling and announcements
- Responsive design accessibility
- Skip links and navigation shortcuts
- Form accessibility
- Table accessibility
- Dynamic content accessibility
- Language and internationalization
- Alternative text and media accessibility
- Focus management

### 6. Cross-Browser Compatibility Tests (`cross_browser_compatibility_test.php`)
Tests application compatibility across different browsers and devices.

**Coverage:**
- CSS compatibility across browsers
- JavaScript compatibility across browsers
- HTML5 compatibility
- Responsive design compatibility
- CSS Grid and Flexbox compatibility
- CSS animations and transitions compatibility
- Form validation compatibility
- Accessibility features compatibility
- Image and media compatibility
- JavaScript ES6+ compatibility
- CSS custom properties compatibility
- Browser-specific CSS prefixes
- JavaScript event compatibility
- CSS feature detection
- JavaScript feature detection
- Browser compatibility matrix

### 7. Mobile Responsiveness Tests (`mobile_responsiveness_test.php`)
Tests application responsiveness across different mobile devices and screen sizes.

**Coverage:**
- Viewport meta tag configuration
- Responsive CSS breakpoints
- Touch-friendly interface elements
- Mobile navigation patterns
- Mobile form interactions
- Mobile typography and readability
- Mobile performance optimizations
- Mobile gesture support
- Mobile-specific UI patterns
- Mobile accessibility features
- Mobile data usage optimization
- Mobile orientation handling
- Mobile-specific CSS features
- Mobile form validation
- Mobile performance metrics
- Mobile breakpoint consistency
- Mobile-specific JavaScript features

### 8. Dark Mode Functionality Tests (`dark_mode_functionality_test.php`)
Tests dark mode implementation, theme switching, and persistence.

**Coverage:**
- Dark mode toggle component
- CSS custom properties for theming
- Theme switching functionality
- Theme persistence
- System theme detection
- Theme-specific styling
- Dark mode accessibility
- Dark mode performance
- Dark mode color contrast
- Dark mode animations
- Dark mode state management
- Dark mode error handling
- Dark mode integration with other components
- Dark mode customization
- Dark mode browser compatibility

### 9. Regression PDF Functionality Tests (`regression_pdf_functionality_test.php`)
Tests to ensure existing PDF functionality still works after refactoring.

**Coverage:**
- PDF field extraction functionality
- PDF form filling functionality
- PDF template functionality
- PDF document management functionality
- PDF upload functionality
- PDF output functionality
- PDF field mapping functionality
- PDF data validation functionality
- PDF error handling functionality
- PDF file permissions functionality
- PDF file size functionality
- PDF MIME type functionality
- PDF field types functionality
- PDF field properties functionality
- PDF field validation functionality
- PDF field formatting functionality
- PDF field mapping validation functionality
- PDF output file naming functionality
- PDF field data types functionality

## Running Tests

### Prerequisites
- PHP 7.4 or higher
- PHPUnit 9.5 or higher
- Composer dependencies installed

### Installation
```bash
# Install PHPUnit and dependencies
composer install

# Install PHPUnit globally (optional)
composer global require phpunit/phpunit
```

### Running All Tests
```bash
# Using the comprehensive test runner
php tests/run_comprehensive_tests.php

# Using PHPUnit directly
./vendor/bin/phpunit tests/

# Using PHPUnit with specific configuration
./vendor/bin/phpunit -c tests/phpunit.xml
```

### Running Specific Test Suites
```bash
# List available test suites
php tests/run_comprehensive_tests.php list

# Run specific test suite
php tests/run_comprehensive_tests.php run "Integration Tests"
php tests/run_comprehensive_tests.php run "UI Components Tests"
php tests/run_comprehensive_tests.php run "API Routes Tests"
php tests/run_comprehensive_tests.php run "Performance Tests"
php tests/run_comprehensive_tests.php run "Accessibility Tests"

# Using PHPUnit directly
./vendor/bin/phpunit tests/integration_client_project_workflow_test.php
./vendor/bin/phpunit tests/ui_components_test.php
./vendor/bin/phpunit tests/api_routes_test.php
./vendor/bin/phpunit tests/performance_large_datasets_test.php
./vendor/bin/phpunit tests/accessibility_test.php
```

### Running Individual Tests
```bash
# Run specific test method
./vendor/bin/phpunit --filter testClientCreationWorkflow tests/integration_client_project_workflow_test.php
./vendor/bin/phpunit --filter testSidebarComponent tests/ui_components_test.php
./vendor/bin/phpunit --filter testUpdateClientStatusRoute tests/api_routes_test.php
./vendor/bin/phpunit --filter testClientListLoadingPerformance tests/performance_large_datasets_test.php
./vendor/bin/phpunit --filter testKeyboardNavigationSupport tests/accessibility_test.php
```

## Test Configuration

### PHPUnit Configuration (`phpunit.xml`)
- **Bootstrap**: Uses Composer autoloader
- **Colors**: Enabled for better output readability
- **Stop on Failure**: Disabled to run all tests
- **Memory Limit**: 512MB for performance tests
- **Execution Time**: 300 seconds maximum
- **Coverage**: HTML, text, and XML reports
- **Logging**: JUnit XML and HTML reports

### Test Data
- Tests use isolated test data files
- Test data is automatically cleaned up after each test
- Large datasets are generated for performance testing
- Test data includes realistic client and project information

## Test Results and Reports

### Coverage Reports
- **HTML Coverage**: `tests/coverage/index.html`
- **Text Coverage**: `tests/coverage.txt`
- **XML Coverage**: `tests/coverage.xml`

### Test Results
- **JUnit XML**: `tests/test-results.xml`
- **HTML Report**: `tests/test-results.html`
- **Text Report**: `tests/test-results.txt`

### Performance Metrics
Performance tests include timing measurements and memory usage statistics:
- Client list loading: < 1.0s for 1000 clients
- Client filtering: < 0.5s for 1000 clients
- Client search: < 0.3s for 1000 clients
- Memory usage: < 50MB for large datasets

## Continuous Integration

### GitHub Actions
```yaml
name: Tests
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '7.4'
      - name: Install dependencies
        run: composer install
      - name: Run tests
        run: php tests/run_comprehensive_tests.php
```

### Local Development
```bash
# Run tests before committing
git add .
php tests/run_comprehensive_tests.php
git commit -m "Your commit message"
```

## Troubleshooting

### Common Issues

1. **Memory Limit Exceeded**
   - Increase PHP memory limit in `phpunit.xml`
   - Reduce dataset size in performance tests

2. **Test Timeout**
   - Increase execution time limit in `phpunit.xml`
   - Optimize slow tests

3. **File Permission Issues**
   - Ensure test data files are writable
   - Check directory permissions

4. **Missing Dependencies**
   - Run `composer install`
   - Check PHPUnit version compatibility

### Debug Mode
```bash
# Run tests with verbose output
./vendor/bin/phpunit --verbose tests/

# Run tests with debug output
./vendor/bin/phpunit --debug tests/

# Run tests with stop on failure
./vendor/bin/phpunit --stop-on-failure tests/
```

## Best Practices

### Writing Tests
1. **Isolation**: Each test should be independent
2. **Cleanup**: Always clean up test data
3. **Naming**: Use descriptive test method names
4. **Assertions**: Use specific assertions
5. **Performance**: Keep tests fast and efficient

### Test Data
1. **Realistic**: Use realistic test data
2. **Minimal**: Use minimal required data
3. **Isolated**: Don't share test data between tests
4. **Clean**: Always clean up after tests

### Performance Testing
1. **Baselines**: Establish performance baselines
2. **Monitoring**: Monitor performance over time
3. **Optimization**: Optimize slow operations
4. **Documentation**: Document performance expectations

## Contributing

When adding new features:
1. Write tests for new functionality
2. Update existing tests if needed
3. Ensure all tests pass
4. Update this documentation
5. Consider performance implications
6. Test accessibility features

## Support

For test-related issues:
1. Check this documentation
2. Review test output and error messages
3. Check PHPUnit configuration
4. Verify dependencies are installed
5. Check file permissions and paths













