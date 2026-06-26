<?php
/**
 * Universal Font Settings Page
 * Manage font configuration for all PDFs
 */

require_once __DIR__ . '/../lib/font_manager.php';

use WebPdfTimeSaver\Mvp\FontManager;

// Load current configuration
$config = require __DIR__ . '/../../config/fonts.php';
$availableFonts = FontManager::getAvailableFonts();
$presets = FontManager::getPresets();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_defaults') {
        $defaults = [
            'fontFamily' => $_POST['fontFamily'] ?? 'Arial',
            'fontSize' => (int)($_POST['fontSize'] ?? 10),
            'fontStyle' => $_POST['fontStyle'] ?? '',
        ];
        FontManager::updateDefaults($defaults);
        $success = 'Global defaults updated successfully!';
    } elseif ($action === 'update_field_type') {
        $fieldType = $_POST['fieldType'] ?? '';
        $fontSettings = [
            'fontFamily' => $_POST['fontFamily'] ?? 'Arial',
            'fontSize' => (int)($_POST['fontSize'] ?? 10),
            'fontStyle' => $_POST['fontStyle'] ?? '',
        ];
        FontManager::updateFieldTypeFont($fieldType, $fontSettings);
        $success = "Field type '{$fieldType}' font updated!";
    } elseif ($action === 'update_template') {
        $templateId = $_POST['templateId'] ?? '';
        $fontSettings = [
            'fontFamily' => $_POST['fontFamily'] ?? 'Arial',
            'fontSize' => (int)($_POST['fontSize'] ?? 10),
            'fontStyle' => $_POST['fontStyle'] ?? '',
        ];
        FontManager::updateTemplateFont($templateId, $fontSettings);
        $success = "Template '{$templateId}' font updated!";
    }
    
    // Reload config after update
    $config = require __DIR__ . '/../../config/fonts.php';
}

$defaults = $config['defaults'] ?? ['fontFamily' => 'Arial', 'fontSize' => 10, 'fontStyle' => ''];
$fieldTypes = $config['fieldTypes'] ?? [];
$templates = $config['templates'] ?? [];

// Get all templates from registry
require_once __DIR__ . '/../templates/registry.php';
try {
    $allTemplates = \WebPdfTimeSaver\Mvp\TemplateRegistry::getAllTemplates();
} catch (\Throwable $e) {
    $allTemplates = [];
}
?>

<div class="panel">
    <h2>🎨 Universal Font Settings</h2>
    <p style="color: #6c757d; margin-bottom: 24px;">
        Configure fonts for all PDFs. Settings apply universally across all templates unless overridden.
    </p>
    
    <?php if (isset($success)): ?>
        <div style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 12px; border-radius: 6px; margin-bottom: 20px;">
            ✅ <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>
    
    <!-- Global Defaults -->
    <div style="background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 20px; margin-bottom: 24px;">
        <h3 style="margin-top: 0; color: #495057;">🌐 Global Defaults</h3>
        <p style="color: #6c757d; font-size: 14px; margin-bottom: 16px;">
            These settings apply to <strong>all PDFs</strong> by default. Used when no other font settings are specified.
        </p>
        
        <form method="POST" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
            <input type="hidden" name="action" value="update_defaults">
            
            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #495057;">Font Family</label>
                <select name="fontFamily" style="width: 100%; padding: 8px; border: 1px solid #ced4da; border-radius: 4px;">
                    <?php foreach ($availableFonts as $font): ?>
                        <option value="<?php echo htmlspecialchars($font); ?>" <?php echo ($defaults['fontFamily'] ?? 'Arial') === $font ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($font); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #495057;">Font Size</label>
                <input type="number" name="fontSize" value="<?php echo htmlspecialchars($defaults['fontSize'] ?? 10); ?>" 
                       min="6" max="24" style="width: 100%; padding: 8px; border: 1px solid #ced4da; border-radius: 4px;">
            </div>
            
            <div>
                <label style="display: block; font-weight: 600; margin-bottom: 6px; color: #495057;">Font Style</label>
                <select name="fontStyle" style="width: 100%; padding: 8px; border: 1px solid #ced4da; border-radius: 4px;">
                    <option value="" <?php echo empty($defaults['fontStyle']) ? 'selected' : ''; ?>>Regular</option>
                    <option value="B" <?php echo ($defaults['fontStyle'] ?? '') === 'B' ? 'selected' : ''; ?>>Bold</option>
                    <option value="I" <?php echo ($defaults['fontStyle'] ?? '') === 'I' ? 'selected' : ''; ?>>Italic</option>
                    <option value="BI" <?php echo ($defaults['fontStyle'] ?? '') === 'BI' ? 'selected' : ''; ?>>Bold Italic</option>
                </select>
            </div>
            
            <div style="display: flex; align-items: flex-end;">
                <button type="submit" class="pdftimesaver-btn" style="width: 100%;">Update Defaults</button>
            </div>
        </form>
    </div>
    
    <!-- Field Type Fonts -->
    <div style="background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 20px; margin-bottom: 24px;">
        <h3 style="margin-top: 0; color: #495057;">📝 Field Type Fonts</h3>
        <p style="color: #6c757d; font-size: 14px; margin-bottom: 16px;">
            Fonts automatically applied based on field type (inferred from field names). Applies to <strong>all PDFs</strong>.
        </p>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px;">
            <?php 
            $fieldTypeLabels = [
                'name' => 'Name Fields',
                'address' => 'Address Fields',
                'phone' => 'Phone Fields',
                'email' => 'Email Fields',
                'date' => 'Date Fields',
                'number' => 'Number Fields',
                'text' => 'Text Fields',
                'checkbox' => 'Checkbox Fields',
                'signature' => 'Signature Fields',
            ];
            
            foreach ($fieldTypes as $fieldType => $settings): 
                $label = $fieldTypeLabels[$fieldType] ?? ucfirst($fieldType);
            ?>
                <div style="background: white; border: 1px solid #dee2e6; border-radius: 6px; padding: 16px;">
                    <h4 style="margin-top: 0; margin-bottom: 12px; color: #495057;"><?php echo htmlspecialchars($label); ?></h4>
                    <form method="POST" style="display: grid; gap: 12px;">
                        <input type="hidden" name="action" value="update_field_type">
                        <input type="hidden" name="fieldType" value="<?php echo htmlspecialchars($fieldType); ?>">
                        
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px; color: #6c757d;">Font</label>
                            <select name="fontFamily" style="width: 100%; padding: 6px; border: 1px solid #ced4da; border-radius: 4px; font-size: 13px;">
                                <?php foreach ($availableFonts as $font): ?>
                                    <option value="<?php echo htmlspecialchars($font); ?>" <?php echo ($settings['fontFamily'] ?? 'Arial') === $font ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($font); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                            <div>
                                <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px; color: #6c757d;">Size</label>
                                <input type="number" name="fontSize" value="<?php echo htmlspecialchars($settings['fontSize'] ?? 10); ?>" 
                                       min="6" max="24" style="width: 100%; padding: 6px; border: 1px solid #ced4da; border-radius: 4px; font-size: 13px;">
                            </div>
                            
                            <div>
                                <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px; color: #6c757d;">Style</label>
                                <select name="fontStyle" style="width: 100%; padding: 6px; border: 1px solid #ced4da; border-radius: 4px; font-size: 13px;">
                                    <option value="" <?php echo empty($settings['fontStyle']) ? 'selected' : ''; ?>>Regular</option>
                                    <option value="B" <?php echo ($settings['fontStyle'] ?? '') === 'B' ? 'selected' : ''; ?>>Bold</option>
                                    <option value="I" <?php echo ($settings['fontStyle'] ?? '') === 'I' ? 'selected' : ''; ?>>Italic</option>
                                    <option value="BI" <?php echo ($settings['fontStyle'] ?? '') === 'BI' ? 'selected' : ''; ?>>Bold Italic</option>
                                </select>
                            </div>
                        </div>
                        
                        <button type="submit" class="pdftimesaver-btn-secondary" style="width: 100%;">Update</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Template Overrides -->
    <div style="background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 20px;">
        <h3 style="margin-top: 0; color: #495057;">📄 Template Overrides</h3>
        <p style="color: #6c757d; font-size: 14px; margin-bottom: 16px;">
            Override fonts for specific templates. These settings take precedence over global defaults and field types.
        </p>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px;">
            <?php foreach ($allTemplates as $templateId => $template): ?>
                <?php 
                $templateSettings = $templates[$templateId] ?? null;
                $hasOverride = $templateSettings !== null;
                ?>
                <div style="background: white; border: 1px solid #dee2e6; border-radius: 6px; padding: 16px; <?php echo $hasOverride ? 'border-left: 4px solid #007bff;' : ''; ?>">
                    <h4 style="margin-top: 0; margin-bottom: 8px; color: #495057;">
                        <?php echo htmlspecialchars($template['name'] ?? $templateId); ?>
                        <?php if ($hasOverride): ?>
                            <span style="font-size: 12px; color: #007bff; font-weight: normal;">(Overridden)</span>
                        <?php endif; ?>
                    </h4>
                    <p style="font-size: 12px; color: #6c757d; margin-bottom: 12px;">
                        <?php echo htmlspecialchars($templateId); ?>
                    </p>
                    
                    <form method="POST" style="display: grid; gap: 12px;">
                        <input type="hidden" name="action" value="update_template">
                        <input type="hidden" name="templateId" value="<?php echo htmlspecialchars($templateId); ?>">
                        
                        <div>
                            <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px; color: #6c757d;">Font</label>
                            <select name="fontFamily" style="width: 100%; padding: 6px; border: 1px solid #ced4da; border-radius: 4px; font-size: 13px;">
                                <?php foreach ($availableFonts as $font): ?>
                                    <option value="<?php echo htmlspecialchars($font); ?>" <?php echo ($templateSettings['fontFamily'] ?? $defaults['fontFamily'] ?? 'Arial') === $font ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($font); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                            <div>
                                <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px; color: #6c757d;">Size</label>
                                <input type="number" name="fontSize" value="<?php echo htmlspecialchars($templateSettings['fontSize'] ?? $defaults['fontSize'] ?? 10); ?>" 
                                       min="6" max="24" style="width: 100%; padding: 6px; border: 1px solid #ced4da; border-radius: 4px; font-size: 13px;">
                            </div>
                            
                            <div>
                                <label style="display: block; font-size: 12px; font-weight: 600; margin-bottom: 4px; color: #6c757d;">Style</label>
                                <select name="fontStyle" style="width: 100%; padding: 6px; border: 1px solid #ced4da; border-radius: 4px; font-size: 13px;">
                                    <option value="" <?php echo empty($templateSettings['fontStyle'] ?? $defaults['fontStyle'] ?? '') ? 'selected' : ''; ?>>Regular</option>
                                    <option value="B" <?php echo ($templateSettings['fontStyle'] ?? $defaults['fontStyle'] ?? '') === 'B' ? 'selected' : ''; ?>>Bold</option>
                                    <option value="I" <?php echo ($templateSettings['fontStyle'] ?? $defaults['fontStyle'] ?? '') === 'I' ? 'selected' : ''; ?>>Italic</option>
                                    <option value="BI" <?php echo ($templateSettings['fontStyle'] ?? $defaults['fontStyle'] ?? '') === 'BI' ? 'selected' : ''; ?>>Bold Italic</option>
                                </select>
                            </div>
                        </div>
                        
                        <button type="submit" class="pdftimesaver-btn-secondary" style="width: 100%;">
                            <?php echo $hasOverride ? 'Update Override' : 'Set Override'; ?>
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Info Box -->
    <div style="background: #e7f3ff; border: 1px solid #b3d9ff; border-radius: 8px; padding: 16px; margin-top: 24px;">
        <h4 style="margin-top: 0; color: #004085;">💡 How It Works</h4>
        <ul style="margin: 0; padding-left: 20px; color: #004085;">
            <li><strong>Global Defaults:</strong> Apply to all PDFs when no other settings exist</li>
            <li><strong>Field Type Fonts:</strong> Automatically applied based on field name patterns (e.g., "name" → name type)</li>
            <li><strong>Template Overrides:</strong> Override fonts for specific templates</li>
            <li><strong>Field Position:</strong> Individual field fonts (set in Visual Field Editor) take highest priority</li>
        </ul>
        <p style="margin-bottom: 0; margin-top: 12px; color: #004085;">
            <strong>Priority Order:</strong> Field Position → Template Override → Field Type → Global Default
        </p>
    </div>
</div>

