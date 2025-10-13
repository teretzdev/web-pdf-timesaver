<?php
/**
 * Interactive Position Adjuster - Test and fine-tune field positions
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/lib/pdf_field_extractor.php';
require_once __DIR__ . '/lib/pdf_form_filler.php';
require_once __DIR__ . '/lib/logger.php';
require_once __DIR__ . '/templates/registry.php';
require_once __DIR__ . '/lib/fl100_test_data_generator.php';

use WebPdfTimeSaver\Mvp\PdfFieldExtractor;
use WebPdfTimeSaver\Mvp\PdfFormFiller;
use WebPdfTimeSaver\Mvp\Logger;
use WebPdfTimeSaver\Mvp\TemplateRegistry;

$action = $_GET['action'] ?? 'adjust';
$pdfFile = __DIR__ . '/../uploads/fl100.pdf';
$templateId = 't_fl100_gc120';
$positionsFile = __DIR__ . '/../data/' . $templateId . '_positions.json';

// Handle actions
if ($action === 'regenerate') {
    // Re-extract positions from PDF
    $extractor = new PdfFieldExtractor();
    $result = $extractor->extractAndGenerateBackgrounds($pdfFile, $templateId, __DIR__ . '/../uploads');
    header('Location: ?action=adjust&msg=regenerated');
    exit;
}

if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Save adjusted positions
    $positions = json_decode($_POST['positions'] ?? '{}', true);
    if ($positions) {
        file_put_contents($positionsFile, json_encode($positions, JSON_PRETTY_PRINT));
        header('Location: ?action=generate&msg=saved');
        exit;
    }
}

if ($action === 'generate') {
    // Generate PDF with current positions
    $templates = TemplateRegistry::load();
    $template = $templates[$templateId] ?? null;
    $testData = \WebPdfTimeSaver\Mvp\FL100TestDataGenerator::generateCompleteTestData();
    
    $logger = new Logger();
    $filler = new PdfFormFiller(__DIR__ . '/../output', __DIR__ . '/../uploads', $logger);
    $filler->setContext(['test' => true, 'method' => 'position-adjustment']);
    
    $generatedPdf = $filler->fillPdfFormWithPositions($template, $testData, $templateId);
    $pdfUrl = '../output/' . $generatedPdf['filename'];
    
    // Redirect to view PDF
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Generated PDF</title>
        <style>
            body { margin: 0; padding: 20px; font-family: system-ui; background: #f0f0f0; }
            .container { max-width: 1400px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
            .toolbar { margin-bottom: 20px; padding: 15px; background: #007bff; color: white; border-radius: 6px; display: flex; gap: 15px; align-items: center; }
            .btn { padding: 10px 20px; background: white; color: #007bff; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; font-weight: 600; }
            .btn:hover { background: #f0f0f0; }
            iframe { width: 100%; height: calc(100vh - 180px); border: 1px solid #ddd; border-radius: 4px; }
            .msg { padding: 10px; background: #d4edda; color: #155724; border-radius: 4px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="toolbar">
                <span style="flex: 1;">📄 Generated PDF Preview</span>
                <a href="?action=adjust" class="btn">⬅️ Back to Adjuster</a>
                <a href="?action=generate" class="btn">🔄 Regenerate</a>
                <a href="<?php echo $pdfUrl; ?>" target="_blank" class="btn">↗️ Open Full</a>
            </div>
            <?php if (isset($_GET['msg']) && $_GET['msg'] === 'saved'): ?>
                <div class="msg">✅ Positions saved! PDF regenerated.</div>
            <?php endif; ?>
            <iframe src="<?php echo $pdfUrl; ?>#zoom=125"></iframe>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Load current positions
$positions = [];
if (file_exists($positionsFile)) {
    $positions = json_decode(file_get_contents($positionsFile), true) ?? [];
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Position Adjuster</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1600px; margin: 0 auto; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px; }
        .header h1 { margin-bottom: 10px; }
        .toolbar { padding: 15px 25px; background: #f8f9fa; border-bottom: 1px solid #dee2e6; display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .btn { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; text-decoration: none; display: inline-block; transition: all 0.2s; }
        .btn:hover { background: #0056b3; transform: translateY(-1px); }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #218838; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #545b62; }
        
        .content { display: grid; grid-template-columns: 400px 1fr; min-height: calc(100vh - 200px); }
        .sidebar { background: #f8f9fa; border-right: 1px solid #dee2e6; overflow-y: auto; max-height: calc(100vh - 200px); }
        .field-list { padding: 15px; }
        .field-item { padding: 12px; background: white; margin-bottom: 10px; border-radius: 6px; border-left: 4px solid #007bff; cursor: pointer; transition: all 0.2s; }
        .field-item:hover { background: #e7f3ff; transform: translateX(3px); }
        .field-item.active { background: #e7f3ff; border-left-color: #28a745; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .field-name { font-weight: 600; color: #333; margin-bottom: 5px; }
        .field-coords { font-size: 12px; color: #666; font-family: monospace; }
        
        .editor { padding: 25px; overflow-y: auto; }
        .editor-header { margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #dee2e6; }
        .editor-header h2 { color: #333; margin-bottom: 5px; }
        .editor-header .subtitle { color: #666; font-size: 14px; }
        
        .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 25px; }
        .form-group { display: flex; flex-direction: column; }
        .form-group label { font-weight: 600; color: #495057; margin-bottom: 8px; font-size: 14px; }
        .form-group input, .form-group select { padding: 10px; border: 2px solid #ced4da; border-radius: 6px; font-size: 14px; transition: border-color 0.2s; }
        .form-group input:focus, .form-group select:focus { outline: none; border-color: #007bff; }
        
        .slider-group { margin-bottom: 20px; }
        .slider-group label { font-weight: 600; color: #495057; margin-bottom: 8px; display: flex; justify-content: space-between; align-items: center; }
        .slider-group input[type="range"] { width: 100%; height: 8px; border-radius: 4px; background: #dee2e6; outline: none; }
        .slider-group input[type="range"]::-webkit-slider-thumb { appearance: none; width: 20px; height: 20px; border-radius: 50%; background: #007bff; cursor: pointer; }
        
        .actions { display: flex; gap: 10px; margin-top: 25px; padding-top: 20px; border-top: 2px solid #dee2e6; }
        
        .msg { padding: 12px; margin-bottom: 20px; border-radius: 6px; font-weight: 500; }
        .msg-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .msg-info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        
        .search-box { margin-bottom: 15px; padding: 10px; }
        .search-box input { width: 100%; padding: 10px; border: 2px solid #ced4da; border-radius: 6px; font-size: 14px; }
        
        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 20px; }
        .stat-card { padding: 15px; background: #f8f9fa; border-radius: 6px; text-align: center; border: 2px solid #dee2e6; }
        .stat-card .label { font-size: 11px; color: #666; text-transform: uppercase; letter-spacing: 0.5px; }
        .stat-card .value { font-size: 24px; font-weight: bold; color: #007bff; margin-top: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎯 Position Adjuster</h1>
            <p>Fine-tune field positions and verify with live PDF preview</p>
        </div>
        
        <div class="toolbar">
            <button onclick="location.href='?action=regenerate'" class="btn btn-secondary">🔄 Re-Extract from PDF</button>
            <button onclick="generatePDF()" class="btn btn-success">▶️ Generate & Preview PDF</button>
            <button onclick="savePositions()" class="btn">💾 Save Positions</button>
            <span style="flex: 1;"></span>
            <span style="color: #666; font-size: 14px;" id="field-count"><?php echo count($positions); ?> fields</span>
        </div>
        
        <?php if (isset($_GET['msg'])): ?>
            <div style="padding: 15px;">
                <?php if ($_GET['msg'] === 'regenerated'): ?>
                    <div class="msg msg-success">✅ Positions regenerated from PDF!</div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="content">
            <div class="sidebar">
                <div class="search-box">
                    <input type="text" id="search" placeholder="🔍 Search fields..." onkeyup="filterFields()">
                </div>
                <div class="field-list" id="field-list">
                    <?php foreach ($positions as $key => $pos): ?>
                        <div class="field-item" data-key="<?php echo htmlspecialchars($key); ?>" onclick="selectField('<?php echo htmlspecialchars($key); ?>')">
                            <div class="field-name"><?php echo htmlspecialchars($key); ?></div>
                            <div class="field-coords">
                                Page <?php echo $pos['page'] ?? 1; ?> • 
                                X: <?php echo $pos['x']; ?> • 
                                Y: <?php echo $pos['y']; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="editor">
                <div class="editor-header">
                    <h2>✏️ Edit Field Position</h2>
                    <p class="subtitle">Select a field from the left to edit its position</p>
                </div>
                
                <div class="stats">
                    <div class="stat-card">
                        <div class="label">Selected Field</div>
                        <div class="value" id="current-field-name" style="font-size: 14px;">None</div>
                    </div>
                    <div class="stat-card">
                        <div class="label">Page</div>
                        <div class="value" id="current-page">-</div>
                    </div>
                    <div class="stat-card">
                        <div class="label">Type</div>
                        <div class="value" id="current-type" style="font-size: 16px;">-</div>
                    </div>
                </div>
                
                <div id="editor-form" style="display: none;">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>X Position (horizontal)</label>
                            <input type="number" id="edit-x" step="0.1" onchange="updateField()">
                        </div>
                        <div class="form-group">
                            <label>Y Position (vertical)</label>
                            <input type="number" id="edit-y" step="0.1" onchange="updateField()">
                        </div>
                        <div class="form-group">
                            <label>Width</label>
                            <input type="number" id="edit-width" step="0.1" onchange="updateField()">
                        </div>
                        <div class="form-group">
                            <label>Height</label>
                            <input type="number" id="edit-height" step="0.1" onchange="updateField()">
                        </div>
                        <div class="form-group">
                            <label>Font Size</label>
                            <input type="number" id="edit-font-size" min="6" max="18" onchange="updateField()">
                        </div>
                        <div class="form-group">
                            <label>Page</label>
                            <input type="number" id="edit-page" min="1" max="10" onchange="updateField()">
                        </div>
                    </div>
                    
                    <div class="slider-group">
                        <label>
                            <span>Fine Tune X</span>
                            <span id="slider-x-val">0</span>
                        </label>
                        <input type="range" id="slider-x" min="-10" max="10" step="0.5" value="0" oninput="adjustX(this.value)">
                    </div>
                    
                    <div class="slider-group">
                        <label>
                            <span>Fine Tune Y</span>
                            <span id="slider-y-val">0</span>
                        </label>
                        <input type="range" id="slider-y" min="-10" max="10" step="0.5" value="0" oninput="adjustY(this.value)">
                    </div>
                    
                    <div class="actions">
                        <button onclick="generatePDF()" class="btn btn-success">👁️ Preview Changes</button>
                        <button onclick="resetField()" class="btn btn-secondary">↩️ Reset</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        let positions = <?php echo json_encode($positions); ?>;
        let currentField = null;
        let originalPositions = JSON.parse(JSON.stringify(positions));
        
        function selectField(key) {
            currentField = key;
            const pos = positions[key];
            
            // Update UI
            document.querySelectorAll('.field-item').forEach(el => el.classList.remove('active'));
            document.querySelector(`[data-key="${key}"]`).classList.add('active');
            
            // Show editor
            document.getElementById('editor-form').style.display = 'block';
            
            // Populate fields
            document.getElementById('edit-x').value = pos.x;
            document.getElementById('edit-y').value = pos.y;
            document.getElementById('edit-width').value = pos.width;
            document.getElementById('edit-height').value = pos.height;
            document.getElementById('edit-font-size').value = pos.fontSize || 9;
            document.getElementById('edit-page').value = pos.page || 1;
            
            // Reset sliders
            document.getElementById('slider-x').value = 0;
            document.getElementById('slider-y').value = 0;
            document.getElementById('slider-x-val').textContent = '0';
            document.getElementById('slider-y-val').textContent = '0';
            
            // Update stats
            document.getElementById('current-field-name').textContent = key;
            document.getElementById('current-page').textContent = pos.page || 1;
            document.getElementById('current-type').textContent = pos.type || 'text';
        }
        
        function updateField() {
            if (!currentField) return;
            
            positions[currentField].x = parseFloat(document.getElementById('edit-x').value);
            positions[currentField].y = parseFloat(document.getElementById('edit-y').value);
            positions[currentField].width = parseFloat(document.getElementById('edit-width').value);
            positions[currentField].height = parseFloat(document.getElementById('edit-height').value);
            positions[currentField].fontSize = parseInt(document.getElementById('edit-font-size').value);
            positions[currentField].page = parseInt(document.getElementById('edit-page').value);
            
            // Update field item display
            const item = document.querySelector(`[data-key="${currentField}"] .field-coords`);
            item.textContent = `Page ${positions[currentField].page} • X: ${positions[currentField].x} • Y: ${positions[currentField].y}`;
        }
        
        function adjustX(delta) {
            if (!currentField) return;
            const baseX = originalPositions[currentField].x;
            const newX = baseX + parseFloat(delta);
            document.getElementById('edit-x').value = newX.toFixed(1);
            document.getElementById('slider-x-val').textContent = delta;
            updateField();
        }
        
        function adjustY(delta) {
            if (!currentField) return;
            const baseY = originalPositions[currentField].y;
            const newY = baseY + parseFloat(delta);
            document.getElementById('edit-y').value = newY.toFixed(1);
            document.getElementById('slider-y-val').textContent = delta;
            updateField();
        }
        
        function resetField() {
            if (!currentField) return;
            positions[currentField] = JSON.parse(JSON.stringify(originalPositions[currentField]));
            selectField(currentField);
        }
        
        function savePositions() {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '?action=save';
            
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'positions';
            input.value = JSON.stringify(positions);
            
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }
        
        function generatePDF() {
            // Save first, then generate
            savePositions();
        }
        
        function filterFields() {
            const search = document.getElementById('search').value.toLowerCase();
            document.querySelectorAll('.field-item').forEach(item => {
                const text = item.textContent.toLowerCase();
                item.style.display = text.includes(search) ? 'block' : 'none';
            });
        }
    </script>
</body>
</html>

