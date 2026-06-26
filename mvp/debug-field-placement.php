<?php
/**
 * Debug Field Placement Issues
 * Shows which fields are being mapped and which are being skipped
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/lib/pdf_form_filler.php';
require_once __DIR__ . '/lib/fl100_test_data_generator.php';
require_once __DIR__ . '/lib/field_position_loader.php';
require_once __DIR__ . '/lib/field_name_mapper.php';

echo "=== FIELD PLACEMENT DEBUG ANALYSIS ===\n\n";

$loader = new \WebPdfTimeSaver\Mvp\FieldPositionLoader();
$positions = $loader->loadFieldPositions('t_fl100_gc120');

$testData = \WebPdfTimeSaver\Mvp\Fl100TestDataGenerator::generateCompleteTestData();
$mapper = new \WebPdfTimeSaver\Mvp\FieldNameMapper();

echo "Test Data Fields: " . count($testData) . "\n";
echo "Extracted Positions: " . count($positions) . "\n\n";

echo "=== FIELD MAPPING ANALYSIS ===\n\n";

$mappedFields = [];
$unmappedFields = [];
$emptyFields = [];

foreach ($testData as $testField => $value) {
    if (empty($value)) {
        $emptyFields[] = $testField;
        continue;
    }
    
    // Try to find matching position
    $found = false;
    $matchedField = null;
    
    // Direct match
    if (isset($positions[$testField])) {
        $found = true;
        $matchedField = $testField;
    } else {
        // Try mapping
        $normalizedTestKey = strtolower(str_replace(['_', '-', ' '], '', $testField));
        
        // Build search patterns
        $searchPatterns = [];
        if (strpos($normalizedTestKey, 'attorney') !== false) {
            if (strpos($normalizedTestKey, 'name') !== false || strpos($normalizedTestKey, 'for') !== false) {
                $searchPatterns[] = '/attyfor|attorneyname|attorneyfor/i';
            }
            if (strpos($normalizedTestKey, 'phone') !== false || strpos($normalizedTestKey, 'telephone') !== false) {
                $searchPatterns[] = '/telephone|phone|fax/i';
            }
            if (strpos($normalizedTestKey, 'firm') !== false) {
                $searchPatterns[] = '/firm/i';
            }
            if (strpos($normalizedTestKey, 'address') !== false) {
                $searchPatterns[] = '/streetaddress|address/i';
            }
        }
        if (strpos($normalizedTestKey, 'case') !== false && strpos($normalizedTestKey, 'number') !== false) {
            $searchPatterns[] = '/casenumber/i';
        }
        if (strpos($normalizedTestKey, 'party1') !== false || strpos($normalizedTestKey, 'petitioner') !== false) {
            $searchPatterns[] = '/party1|petitioner/i';
        }
        if (strpos($normalizedTestKey, 'party2') !== false || strpos($normalizedTestKey, 'respondent') !== false) {
            $searchPatterns[] = '/party2|respondent/i';
        }
        if (strpos($normalizedTestKey, 'separation') !== false && strpos($normalizedTestKey, 'date') !== false) {
            $searchPatterns[] = '/dateofseparation|separationdate/i';
        }
        
        // Search positions
        foreach ($positions as $posKey => $pos) {
            $normalizedPosKey = strtolower($posKey);
            foreach ($searchPatterns as $pattern) {
                if (preg_match($pattern, $normalizedPosKey)) {
                    $found = true;
                    $matchedField = $posKey;
                    break 2;
                }
            }
        }
    }
    
    if ($found) {
        $mappedFields[$testField] = $matchedField;
    } else {
        $unmappedFields[] = $testField;
    }
}

echo "✅ Mapped Fields (" . count($mappedFields) . "):\n";
foreach ($mappedFields as $testField => $matchedField) {
    $pos = $positions[$matchedField];
    echo "  $testField -> $matchedField (Page {$pos['page']}, X:{$pos['x']}, Y:{$pos['y']})\n";
}

echo "\n❌ Unmapped Fields (" . count($unmappedFields) . "):\n";
foreach ($unmappedFields as $field) {
    echo "  - $field\n";
}

echo "\n⚠️  Empty Fields (" . count($emptyFields) . "):\n";
foreach ($emptyFields as $field) {
    echo "  - $field\n";
}

echo "\n=== EXTRACTED FIELD NAMES (Sample) ===\n";
$sampleFields = array_slice(array_keys($positions), 0, 20);
foreach ($sampleFields as $fieldName) {
    echo "  - $fieldName\n";
}

