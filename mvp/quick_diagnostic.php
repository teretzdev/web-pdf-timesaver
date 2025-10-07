<?php
/**
 * Quick Diagnostic Tool for Drafting System
 * Upload this to pdftimesaver.desktopmasters.com/mvp/quick_diagnostic.php
 */

header('Content-Type: text/html; charset=utf-8');

$results = [];
$hasErrors = false;

// Check 1: Essential Files
$essentialFiles = [
    'views/drafting.php' => 'Drafting view interface',
    'views/drafting-editor.php' => 'Drafting editor interface',
    'lib/drafting_manager.php' => 'Drafting manager class',
    'views/populate.php' => 'Main populate view',
    'index.php' => 'Main router',
    'lib/data.php' => 'Data store class',
    'templates/registry.php' => 'Template registry'
];

foreach ($essentialFiles as $file => $description) {
    $fullPath = __DIR__ . '/' . $file;
    if (file_exists($fullPath)) {
        $results['files'][$file] = ['status' => 'OK', 'desc' => $description];
    } else {
        $results['files'][$file] = ['status' => 'MISSING', 'desc' => $description];
        $hasErrors = true;
    }
}

// Check 2: Routes in index.php
$indexPath = __DIR__ . '/index.php';
if (file_exists($indexPath)) {
    $indexContent = file_get_contents($indexPath);
    $requiredRoutes = [
        'drafting' => "case 'drafting':",
        'drafting-editor' => "case 'drafting-editor':",
        'actions/save-draft-fields' => "case 'actions/save-draft-fields':",
        'actions/save-panel-configuration' => "case 'actions/save-panel-configuration':"
    ];
    
    foreach ($requiredRoutes as $route => $pattern) {
        if (strpos($indexContent, $pattern) !== false) {
            $results['routes'][$route] = 'OK';
        } else {
            $results['routes'][$route] = 'MISSING';
            $hasErrors = true;
        }
    }
}

// Check 3: Directories
$directories = [
    '../data' => 'Main data directory',
    '../data/panel_configs' => 'Panel configurations',
    '../data/draft_sessions' => 'Draft sessions storage',
    '../logs' => 'Log files'
];

foreach ($directories as $dir => $description) {
    $fullPath = __DIR__ . '/' . $dir;
    if (is_dir($fullPath)) {
        $writable = is_writable($fullPath);
        $results['dirs'][$dir] = [
            'exists' => true,
            'writable' => $writable,
            'desc' => $description
        ];
        if (!$writable) $hasErrors = true;
    } else {
        $results['dirs'][$dir] = [
            'exists' => false,
            'writable' => false,
            'desc' => $description
        ];
        $hasErrors = true;
    }
}

// Check 4: Test Class Loading
$classTestResult = [];
try {
    // Try to load required files
    $requiredIncludes = [
        'lib/data.php',
        'templates/registry.php',
        'lib/drafting_manager.php'
    ];
    
    foreach ($requiredIncludes as $inc) {
        if (file_exists(__DIR__ . '/' . $inc)) {
            require_once __DIR__ . '/' . $inc;
        }
    }
    
    // Check if classes exist
    $classTestResult['DataStore'] = class_exists('WebPdfTimeSaver\Mvp\DataStore');
    $classTestResult['DraftingManager'] = class_exists('WebPdfTimeSaver\Mvp\DraftingManager');
    $classTestResult['TemplateRegistry'] = class_exists('WebPdfTimeSaver\Mvp\TemplateRegistry');
    
    // Try to instantiate
    if ($classTestResult['DataStore'] && $classTestResult['DraftingManager'] && $classTestResult['TemplateRegistry']) {
        $store = new \WebPdfTimeSaver\Mvp\DataStore(__DIR__ . '/../data/mvp.json');
        $templates = \WebPdfTimeSaver\Mvp\TemplateRegistry::load();
        $dm = new \WebPdfTimeSaver\Mvp\DraftingManager($store, $templates);
        $classTestResult['Instantiation'] = true;
        
        // Check FL-100 template
        $classTestResult['FL100_Template'] = isset($templates['t_fl100_gc120']);
    }
} catch (Exception $e) {
    $classTestResult['Error'] = $e->getMessage();
    $hasErrors = true;
}

// Check 5: UI Elements in populate.php
$uiElements = [];
if (file_exists(__DIR__ . '/views/populate.php')) {
    $populateContent = file_get_contents(__DIR__ . '/views/populate.php');
    $uiElements['Drafting View Button'] = strpos($populateContent, 'Drafting View') !== false;
    $uiElements['Edit Draft Button'] = strpos($populateContent, 'Edit Draft') !== false;
    $uiElements['route=drafting'] = strpos($populateContent, 'route=drafting') !== false;
    $uiElements['route=drafting-editor'] = strpos($populateContent, 'route=drafting-editor') !== false;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drafting System Diagnostic</title>
    <style>
        body {
            font-family: -apple-system, system-ui, sans-serif;
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
            background: #f5f5f5;
        }
        .header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            margin: 0 0 10px 0;
            color: #333;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            color: white;
        }
        .status-ok { background: #28a745; }
        .status-error { background: #dc3545; }
        .section {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h2 {
            margin-top: 0;
            color: #495057;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            text-align: left;
            padding: 10px;
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }
        td {
            padding: 10px;
            border-bottom: 1px solid #e9ecef;
        }
        .ok { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .actions {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 5px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 500;
        }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #218838; }
        .code {
            background: #f8f9fa;
            padding: 10px;
            border-left: 4px solid #007bff;
            margin: 10px 0;
            font-family: monospace;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🔍 Drafting System Diagnostic</h1>
        <p>Checking all components of the Clio-style drafting implementation...</p>
        <?php if ($hasErrors): ?>
            <span class="status-badge status-error">⚠️ ISSUES DETECTED</span>
        <?php else: ?>
            <span class="status-badge status-ok">✅ ALL SYSTEMS GO</span>
        <?php endif; ?>
    </div>

    <!-- File Check -->
    <div class="section">
        <h2>📁 Essential Files</h2>
        <table>
            <thead>
                <tr>
                    <th>File</th>
                    <th>Description</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results['files'] as $file => $info): ?>
                    <tr>
                        <td><code><?php echo $file; ?></code></td>
                        <td><?php echo $info['desc']; ?></td>
                        <td class="<?php echo $info['status'] === 'OK' ? 'ok' : 'error'; ?>">
                            <?php echo $info['status'] === 'OK' ? '✅ OK' : '❌ MISSING'; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Routes Check -->
    <div class="section">
        <h2>🛣️ Routes Configuration</h2>
        <table>
            <thead>
                <tr>
                    <th>Route</th>
                    <th>Status</th>
                    <th>URL Pattern</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results['routes'] ?? [] as $route => $status): ?>
                    <tr>
                        <td><code><?php echo $route; ?></code></td>
                        <td class="<?php echo $status === 'OK' ? 'ok' : 'error'; ?>">
                            <?php echo $status === 'OK' ? '✅ OK' : '❌ MISSING'; ?>
                        </td>
                        <td><code>?route=<?php echo $route; ?></code></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Directories Check -->
    <div class="section">
        <h2>📂 Required Directories</h2>
        <table>
            <thead>
                <tr>
                    <th>Directory</th>
                    <th>Description</th>
                    <th>Exists</th>
                    <th>Writable</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results['dirs'] ?? [] as $dir => $info): ?>
                    <tr>
                        <td><code><?php echo $dir; ?></code></td>
                        <td><?php echo $info['desc']; ?></td>
                        <td class="<?php echo $info['exists'] ? 'ok' : 'error'; ?>">
                            <?php echo $info['exists'] ? '✅ YES' : '❌ NO'; ?>
                        </td>
                        <td class="<?php echo $info['writable'] ? 'ok' : ($info['exists'] ? 'warning' : 'error'); ?>">
                            <?php echo $info['writable'] ? '✅ YES' : ($info['exists'] ? '⚠️ NO' : '❌ N/A'); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if ($hasErrors && isset($results['dirs'])): ?>
            <div class="code">
                <strong>To fix directory issues, run:</strong><br>
                <?php foreach ($results['dirs'] as $dir => $info): ?>
                    <?php if (!$info['exists']): ?>
                        mkdir -p <?php echo $dir; ?><br>
                    <?php endif; ?>
                    <?php if ($info['exists'] && !$info['writable']): ?>
                        chmod 777 <?php echo $dir; ?><br>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Class Loading Check -->
    <div class="section">
        <h2>⚙️ System Components</h2>
        <table>
            <thead>
                <tr>
                    <th>Component</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($classTestResult as $component => $status): ?>
                    <tr>
                        <td><?php echo $component; ?></td>
                        <td class="<?php echo ($status === true || $status === 'true') ? 'ok' : 'error'; ?>">
                            <?php 
                            if ($component === 'Error') {
                                echo '❌ ' . $status;
                            } else {
                                echo ($status === true || $status === 'true') ? '✅ OK' : '❌ NOT FOUND';
                            }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- UI Elements Check -->
    <div class="section">
        <h2>🎨 UI Elements</h2>
        <table>
            <thead>
                <tr>
                    <th>Element</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($uiElements as $element => $found): ?>
                    <tr>
                        <td><?php echo $element; ?></td>
                        <td class="<?php echo $found ? 'ok' : 'error'; ?>">
                            <?php echo $found ? '✅ FOUND' : '❌ NOT FOUND'; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Quick Actions -->
    <div class="section">
        <h2>🚀 Quick Actions</h2>
        <div class="actions">
            <h3>Test the System:</h3>
            <a href="?route=projects" class="btn">📁 Go to Projects</a>
            <a href="?route=templates" class="btn">📋 View Templates</a>
            <a href="test_drafting.php" class="btn btn-success">🧪 Run Full Tests</a>
            
            <h3>If you have a document ready:</h3>
            <p>Replace [DOC_ID] with your actual document ID:</p>
            <a href="?route=drafting&pd=[DOC_ID]" class="btn">📝 Test Drafting View</a>
            <a href="?route=drafting-editor&id=t_fl100_gc120" class="btn">✏️ Test Drafting Editor</a>
        </div>
    </div>

    <!-- Summary -->
    <div class="section">
        <h2>📊 Summary</h2>
        <?php if (!$hasErrors): ?>
            <p class="ok"><strong>✅ All components are properly installed!</strong></p>
            <p>The drafting system should be fully functional. You can:</p>
            <ol>
                <li>Create a project and add an FL-100 document</li>
                <li>Click "Drafting View" to use the step-by-step interface</li>
                <li>Click "Edit Draft" to configure panels and fields</li>
            </ol>
        <?php else: ?>
            <p class="error"><strong>⚠️ Some components need attention.</strong></p>
            <p>Please fix the issues marked in red above. Common solutions:</p>
            <ul>
                <li>Ensure all files were uploaded correctly</li>
                <li>Check that directories exist and are writable</li>
                <li>Verify the routes are properly configured in index.php</li>
            </ul>
        <?php endif; ?>
    </div>

    <div style="text-align: center; color: #6c757d; padding: 20px;">
        <p>Diagnostic run at: <?php echo date('Y-m-d H:i:s'); ?></p>
    </div>
</body>
</html>