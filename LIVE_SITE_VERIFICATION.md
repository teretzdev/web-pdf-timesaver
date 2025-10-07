# 🔍 Live Site Verification Checklist for pdftimesaver.desktopmasters.com

## Quick Test Procedure

### Step 1: Basic Access Test
Navigate to these URLs and verify they load:

- [ ] `pdftimesaver.desktopmasters.com/mvp/` - Main dashboard loads
- [ ] `pdftimesaver.desktopmasters.com/mvp/?route=projects` - Projects page loads
- [ ] `pdftimesaver.desktopmasters.com/mvp/?route=templates` - Templates page shows FL-100

### Step 2: Create Test Document
1. [ ] Go to Projects page
2. [ ] Click "Create Project" (or use existing)
3. [ ] Add Document → Select "FL-100" template
4. [ ] Document creates successfully

### Step 3: Test Drafting View
1. [ ] Open the FL-100 document
2. [ ] Look for these buttons:
   - [ ] "Drafting View" button visible
   - [ ] "Edit Draft" button visible
   
3. [ ] Click "Drafting View"
4. [ ] Verify you see:
   - [ ] Progress bar at top (shows 0%)
   - [ ] Left sidebar with 7 sections:
     * Attorney Information
     * Court Information  
     * Parties Information
     * Marriage Information
     * Relief Requested
     * Children
     * Additional Information
   - [ ] Main content area with form fields
   - [ ] Right help sidebar

### Step 4: Test Form Filling
1. [ ] Fill in Attorney Name field
2. [ ] Progress bar increases (should show ~3%)
3. [ ] Fill all Attorney Information fields
4. [ ] Attorney section shows ✓ checkmark
5. [ ] Click "Save & Continue"
6. [ ] Next section (Court Information) loads

### Step 5: Test Navigation
1. [ ] Click "Previous" button - goes back to Attorney
2. [ ] Click on "Marriage Information" in sidebar - jumps to that section
3. [ ] Click "Skip →" - moves to next section

### Step 6: Test Drafting Editor
1. [ ] Go back to document
2. [ ] Click "Edit Draft" button
3. [ ] Verify you see:
   - [ ] Panel list on left
   - [ ] Fields in center
   - [ ] Properties on right
4. [ ] Try dragging a panel (using ⋮⋮ handle)
5. [ ] Click "Add Field" button

## Common Issues & Solutions

### Issue 1: "Drafting View" button not showing
**Check:** Look in `/mvp/views/populate.php` for:
```php
<a href="?route=drafting&pd=<?php echo htmlspecialchars($projectDocument['id']); ?>" class="btn secondary">
    <span class="btn-icon">📝</span>
    <span>Drafting View</span>
</a>
```

### Issue 2: Route not found error
**Check:** In `/mvp/index.php`, ensure these cases exist:
```php
case 'drafting':
case 'drafting-editor':
case 'actions/save-draft-fields':
```

### Issue 3: DraftingManager class not found
**Check:** File exists at `/mvp/lib/drafting_manager.php` and contains:
```php
class DraftingManager {
```

### Issue 4: Directories not writable
**Check:** These directories exist and are writable:
```
/data/panel_configs/
/data/draft_sessions/
/logs/
```

## Quick Debug Test

Add this test file at `pdftimesaver.desktopmasters.com/mvp/debug_test.php`:

```php
<?php
echo "<h2>Drafting System Debug Test</h2>";

// Test 1: Check files
$files = [
    'views/drafting.php',
    'views/drafting-editor.php',
    'lib/drafting_manager.php'
];

echo "<h3>File Check:</h3>";
foreach ($files as $file) {
    $exists = file_exists(__DIR__ . '/' . $file);
    echo $file . ': ' . ($exists ? '✅ EXISTS' : '❌ MISSING') . '<br>';
}

// Test 2: Check routes
echo "<h3>Route Check:</h3>";
$indexContent = file_get_contents(__DIR__ . '/index.php');
$routes = ['drafting', 'drafting-editor', 'save-draft-fields'];
foreach ($routes as $route) {
    $found = strpos($indexContent, "case '$route':") !== false;
    echo $route . ': ' . ($found ? '✅ FOUND' : '❌ MISSING') . '<br>';
}

// Test 3: Check directories
echo "<h3>Directory Check:</h3>";
$dirs = [
    '../data/panel_configs',
    '../data/draft_sessions',
    '../logs'
];
foreach ($dirs as $dir) {
    $exists = is_dir(__DIR__ . '/' . $dir);
    $writable = is_writable(__DIR__ . '/' . $dir);
    echo $dir . ': ' . ($exists ? '✅ EXISTS' : '❌ MISSING');
    if ($exists) {
        echo ' - ' . ($writable ? '✅ WRITABLE' : '❌ NOT WRITABLE');
    }
    echo '<br>';
}

// Test 4: Check class
echo "<h3>Class Check:</h3>";
require_once __DIR__ . '/lib/data.php';
require_once __DIR__ . '/templates/registry.php';
require_once __DIR__ . '/lib/drafting_manager.php';

if (class_exists('WebPdfTimeSaver\Mvp\DraftingManager')) {
    echo "DraftingManager class: ✅ LOADED<br>";
    
    $store = new \WebPdfTimeSaver\Mvp\DataStore(__DIR__ . '/../data/mvp.json');
    $templates = \WebPdfTimeSaver\Mvp\TemplateRegistry::load();
    $dm = new \WebPdfTimeSaver\Mvp\DraftingManager($store, $templates);
    
    echo "Can create instance: ✅ YES<br>";
    
    // Test create session
    $testSession = $dm->createDraftSession('test_doc_id');
    if (isset($testSession['id'])) {
        echo "Can create draft session: ✅ YES<br>";
    } else {
        echo "Can create draft session: ❌ NO - " . ($testSession['error'] ?? 'Unknown error') . "<br>";
    }
} else {
    echo "DraftingManager class: ❌ NOT FOUND<br>";
}

echo "<h3>Summary:</h3>";
echo "<p>If all items show ✅, the drafting system should work correctly.</p>";
echo "<p>If any show ❌, those components need to be fixed.</p>";
?>
```

## Expected Results

When working correctly, you should see:
1. **Progress Bar:** Starts at 0%, increases as you fill fields
2. **Section Status:** Changes from gray ○ to blue ● (active) to green ✓ (complete)
3. **Navigation:** Can move between sections freely
4. **Validation:** Required fields show * and error messages if empty
5. **PDF Generation:** Button appears when progress hits 100%

## Report Back

Let me know which specific tests fail and I can provide targeted fixes!