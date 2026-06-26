<?php
/**
 * Analyze and improve field name mapping for FL-100
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/lib/field_position_loader.php';
require_once __DIR__ . '/lib/fl100_test_data_generator.php';

echo "=== ANALYZING FIELD MAPPING FOR IMPROVEMENT ===\n\n";

$loader = new \WebPdfTimeSaver\Mvp\FieldPositionLoader();
$positions = $loader->loadFieldPositions('t_fl100_gc120');
$testData = \WebPdfTimeSaver\Mvp\Fl100TestDataGenerator::generateCompleteTestData();

echo "Total positions: " . count($positions) . "\n";
echo "Total test data fields: " . count($testData) . "\n\n";

echo "=== EXTRACTED FIELD NAMES (Sample) ===\n";
$sampleFields = array_slice(array_keys($positions), 0, 30);
foreach ($sampleFields as $field) {
    echo "  - $field\n";
}

echo "\n=== TEST DATA FIELDS ===\n";
foreach ($testData as $key => $value) {
    if (!empty($value)) {
        echo "  - $key: $value\n";
    }
}

echo "\n=== MAPPING ANALYSIS ===\n";

// Build comprehensive mapping patterns
$mappingPatterns = [
    // Attorney fields
    'attorney_name' => ['attyfor', 'attorneyname', 'attyfor_ft'],
    'attorney_firm' => ['attyfirm', 'firmname', 'attyfirm_ft'],
    'attorney_address' => ['attystreet', 'attorneystreet', 'attystreet_ft', 'streetaddress'],
    'attorney_city_state_zip' => ['attycity', 'attystate', 'attyzip', 'attycity_ft', 'attystate_ft', 'attyzip_ft'],
    'attorney_phone' => ['phone', 'telephone', 'phone_ft', 'telephone_ft', 'fax_ft'],
    'attorney_email' => ['email', 'email_ft'],
    'attorney_bar_number' => ['barno', 'barnumber', 'barno_ft'],
    
    // Court fields
    'case_number' => ['casenumber', 'casenumber_ft'],
    'court_county' => ['crtcounty', 'county', 'crtcounty_ft'],
    'court_address' => ['crtstreet', 'courtstreet', 'street_ft'],
    
    // Party fields
    'petitioner_name' => ['party1', 'petitioner', 'party1_ft'],
    'petitioner_address' => ['party1street', 'petitionerstreet', 'petitioneraddress'],
    'petitioner_phone' => ['party1phone', 'petitionerphone'],
    'respondent_name' => ['party2', 'respondent', 'party2_ft'],
    'respondent_address' => ['party2street', 'respondentstreet', 'respondentaddress'],
    
    // Date fields
    'separation_date' => ['dateofseparation', 'separationdate', 'dateofseparation_dt'],
    'marriage_date' => ['marriagedate', 'dateofmarriage'],
    
    // Other fields
    'has_children' => ['children', 'haschildren', 'children_cb'],
];

$mapped = [];
$unmapped = [];

foreach ($testData as $testKey => $testValue) {
    if (empty($testValue)) continue;
    
    $found = false;
    $matchedField = null;
    
    // Try patterns
    if (isset($mappingPatterns[$testKey])) {
        $patterns = $mappingPatterns[$testKey];
        foreach ($patterns as $pattern) {
            foreach ($positions as $posKey => $pos) {
                $normalizedPosKey = strtolower(str_replace(['_', '-', ' ', '[', ']', '.'], '', $posKey));
                if (strpos($normalizedPosKey, strtolower($pattern)) !== false) {
                    $found = true;
                    $matchedField = $posKey;
                    break 2;
                }
            }
        }
    }
    
    // Try direct search in all position names
    if (!$found) {
        $normalizedTestKey = strtolower(str_replace(['_', '-', ' '], '', $testKey));
        $testWords = preg_split('/[^a-z0-9]+/', $normalizedTestKey);
        
        foreach ($positions as $posKey => $pos) {
            $normalizedPosKey = strtolower(str_replace(['_', '-', ' ', '[', ']', '.'], '', $posKey));
            $posWords = preg_split('/[^a-z0-9]+/', $normalizedPosKey);
            
            // Check if key words match
            $commonWords = array_intersect($testWords, $posWords);
            if (count($commonWords) >= 1 && count($commonWords) >= min(count($testWords), count($posWords)) * 0.5) {
                $found = true;
                $matchedField = $posKey;
                break;
            }
        }
    }
    
    if ($found) {
        $mapped[$testKey] = $matchedField;
    } else {
        $unmapped[] = $testKey;
    }
}

echo "\n✅ Mapped Fields (" . count($mapped) . "):\n";
foreach ($mapped as $testKey => $posKey) {
    $pos = $positions[$posKey];
    echo "  $testKey -> $posKey (Page {$pos['page']}, X:{$pos['x']}, Y:{$pos['y']})\n";
}

echo "\n❌ Unmapped Fields (" . count($unmapped) . "):\n";
foreach ($unmapped as $key) {
    echo "  - $key\n";
    // Show similar field names
    $normalizedKey = strtolower(str_replace(['_', '-', ' '], '', $key));
    $keyWords = preg_split('/[^a-z0-9]+/', $normalizedKey);
    echo "    Similar fields:\n";
    $similar = [];
    foreach ($positions as $posKey => $pos) {
        $normalizedPosKey = strtolower(str_replace(['_', '-', ' ', '[', ']', '.'], '', $posKey));
        $posWords = preg_split('/[^a-z0-9]+/', $normalizedPosKey);
        $common = array_intersect($keyWords, $posWords);
        if (count($common) > 0) {
            $similar[] = $posKey;
            if (count($similar) >= 3) break;
        }
    }
    foreach ($similar as $sim) {
        echo "      - $sim\n";
    }
}

