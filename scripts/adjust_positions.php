<?php
/**
 * Interactive tool to adjust PDF field positions
 * Usage: php adjust_positions.php [field_name] [x] [y] [width] [height]
 */

require_once __DIR__ . '/../mvp/lib/field_position_loader.php';
use WebPdfTimeSaver\Mvp\FieldPositionLoader;

$dataDir = __DIR__ . '/../data';
$loader = new FieldPositionLoader($dataDir);
$templateId = 't_fl100_gc120';

// Load current positions
$positions = $loader->loadFieldPositions($templateId);

if ($argc == 1) {
    // Show menu
    echo "PDF Position Adjuster\n";
    echo "=====================\n\n";
    echo "Usage:\n";
    echo "  php adjust_positions.php list                              - List all fields\n";
    echo "  php adjust_positions.php show <field>                      - Show field details\n";
    echo "  php adjust_positions.php set <field> <x> <y> <w> <h>      - Set field position\n";
    echo "  php adjust_positions.php move <field> <dx> <dy>           - Move field by offset\n";
    echo "  php adjust_positions.php copy <source> <target>           - Copy position\n";
    echo "  php adjust_positions.php delete <field>                    - Delete field\n";
    echo "  php adjust_positions.php reset                             - Reset to defaults\n";
    echo "  php adjust_positions.php export                            - Export as PHP array\n";
    exit(0);
}

$command = $argv[1] ?? 'list';

switch ($command) {
    case 'list':
        echo "Current field positions:\n";
        echo str_repeat('-', 80) . "\n";
        printf("%-30s | %-8s | %-8s | %-8s | %-8s | %-10s\n", "Field", "X", "Y", "Width", "Height", "Type");
        echo str_repeat('-', 80) . "\n";
        
        foreach ($positions as $field => $info) {
            printf("%-30s | %-8.1f | %-8.1f | %-8.1f | %-8.1f | %-10s\n",
                $field,
                $info['x'] ?? 0,
                $info['y'] ?? 0,
                $info['width'] ?? 0,
                $info['height'] ?? 0,
                $info['type'] ?? 'text'
            );
        }
        echo str_repeat('-', 80) . "\n";
        echo "Total fields: " . count($positions) . "\n";
        break;
        
    case 'show':
        $field = $argv[2] ?? null;
        if (!$field || !isset($positions[$field])) {
            echo "Field not found: $field\n";
            exit(1);
        }
        
        echo "Field: $field\n";
        echo "  X: " . ($positions[$field]['x'] ?? 0) . " mm\n";
        echo "  Y: " . ($positions[$field]['y'] ?? 0) . " mm\n";
        echo "  Width: " . ($positions[$field]['width'] ?? 0) . " mm\n";
        echo "  Height: " . ($positions[$field]['height'] ?? 0) . " mm\n";
        echo "  Type: " . ($positions[$field]['type'] ?? 'text') . "\n";
        echo "  Label: " . ($positions[$field]['label'] ?? '') . "\n";
        echo "  Page: " . ($positions[$field]['page'] ?? 1) . "\n";
        break;
        
    case 'set':
        $field = $argv[2] ?? null;
        $x = (float)($argv[3] ?? 0);
        $y = (float)($argv[4] ?? 0);
        $width = (float)($argv[5] ?? 100);
        $height = (float)($argv[6] ?? 5);
        
        if (!$field) {
            echo "Please provide field name\n";
            exit(1);
        }
        
        // Update or create field
        if (!isset($positions[$field])) {
            $positions[$field] = [
                'type' => 'text',
                'label' => ucwords(str_replace('_', ' ', $field)),
                'page' => 1
            ];
        }
        
        $positions[$field]['x'] = $x;
        $positions[$field]['y'] = $y;
        $positions[$field]['width'] = $width;
        $positions[$field]['height'] = $height;
        
        // Save
        if ($loader->saveFieldPositions($templateId, $positions)) {
            echo "✅ Updated $field: x=$x, y=$y, width=$width, height=$height\n";
        } else {
            echo "❌ Failed to save positions\n";
        }
        break;
        
    case 'move':
        $field = $argv[2] ?? null;
        $dx = (float)($argv[3] ?? 0);
        $dy = (float)($argv[4] ?? 0);
        
        if (!$field || !isset($positions[$field])) {
            echo "Field not found: $field\n";
            exit(1);
        }
        
        $positions[$field]['x'] = ($positions[$field]['x'] ?? 0) + $dx;
        $positions[$field]['y'] = ($positions[$field]['y'] ?? 0) + $dy;
        
        // Save
        if ($loader->saveFieldPositions($templateId, $positions)) {
            echo "✅ Moved $field by ($dx, $dy) to ({$positions[$field]['x']}, {$positions[$field]['y']})\n";
        } else {
            echo "❌ Failed to save positions\n";
        }
        break;
        
    case 'copy':
        $source = $argv[2] ?? null;
        $target = $argv[3] ?? null;
        
        if (!$source || !isset($positions[$source])) {
            echo "Source field not found: $source\n";
            exit(1);
        }
        if (!$target) {
            echo "Please provide target field name\n";
            exit(1);
        }
        
        $positions[$target] = $positions[$source];
        $positions[$target]['label'] = ucwords(str_replace('_', ' ', $target));
        
        // Save
        if ($loader->saveFieldPositions($templateId, $positions)) {
            echo "✅ Copied position from $source to $target\n";
        } else {
            echo "❌ Failed to save positions\n";
        }
        break;
        
    case 'delete':
        $field = $argv[2] ?? null;
        
        if (!$field || !isset($positions[$field])) {
            echo "Field not found: $field\n";
            exit(1);
        }
        
        unset($positions[$field]);
        
        // Save
        if ($loader->saveFieldPositions($templateId, $positions)) {
            echo "✅ Deleted field: $field\n";
        } else {
            echo "❌ Failed to save positions\n";
        }
        break;
        
    case 'reset':
        echo "Are you sure you want to reset all positions to defaults? (yes/no): ";
        $confirm = trim(fgets(STDIN));
        
        if (strtolower($confirm) === 'yes') {
            // Reset to default FL-100 positions
            $defaultPositions = getDefaultFL100Positions();
            
            if ($loader->saveFieldPositions($templateId, $defaultPositions)) {
                echo "✅ Reset to default positions (" . count($defaultPositions) . " fields)\n";
            } else {
                echo "❌ Failed to save positions\n";
            }
        } else {
            echo "Reset cancelled\n";
        }
        break;
        
    case 'export':
        echo "<?php\n\n";
        echo "// FL-100 Field Positions\n";
        echo "\$fl100_positions = " . var_export($positions, true) . ";\n";
        break;
        
    default:
        echo "Unknown command: $command\n";
        echo "Run without arguments to see usage\n";
        exit(1);
}

/**
 * Get default FL-100 positions
 */
function getDefaultFL100Positions() {
    return json_decode(file_get_contents(__DIR__ . '/../data/t_fl100_gc120_positions.json'), true);
}