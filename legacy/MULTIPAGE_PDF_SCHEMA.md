# Multi-Page PDF Form System - Schema & Design

## Position File Format (Reusable for Any PDF Form)

### Schema Definition

```json
{
  "field_key_name": {
    "page": 1,           // Page number (1-based)
    "x": 10.5,          // X coordinate in mm from left
    "y": 25.3,          // Y coordinate in mm from top
    "width": 85,        // Field width in mm (optional)
    "height": 5,        // Field height in mm (optional, for checkboxes)
    "fontSize": 9,      // Font size in points
    "fontStyle": "",    // Font style: "" (normal), "B" (bold), "I" (italic), "BI" (bold italic)
    "type": "text"      // Field type: text, checkbox, date, textarea, select, number
  }
}
```

### Field Type Specifications

**Text Fields**:
```json
{
  "x": 10, "y": 20, "width": 100, "fontSize": 9, "type": "text"
}
```

**Checkbox Fields**:
```json
{
  "x": 15, "y": 50, "width": 3.5, "height": 3.5, "fontSize": 10, "type": "checkbox"
}
```
- Renders "X" mark when value is "1", "yes", "true"

**Date Fields**:
```json
{
  "x": 120, "y": 180, "width": 35, "fontSize": 8, "type": "date"
}
```

**Textarea Fields**:
```json
{
  "x": 25, "y": 200, "width": 165, "fontSize": 7, "type": "textarea"
}
```

## File Naming Convention

**Position Files**: `data/{template_id}_positions.json`
- Example: `data/t_fl100_gc120_positions.json`

**Background Images**: `uploads/{template_id}_page{N}_background.png`
- Example: `uploads/fl100_page1_background.png`
- Example: `uploads/fl100_page2_background.png`

**Template IDs**: Use consistent format
- `t_fl100_gc120` (FL-100 form)
- `t_fl105_gc120` (FL-105 form)
- `t_{form_code}_{variant}`

## Multi-Page Support

### Page Configuration

Each template should define its page count:

```php
// In templates/registry.php
't_fl100_gc120' => [
    'id' => 't_fl100_gc120',
    'code' => 'FL-100',
    'name' => 'Petition—Marriage/Domestic Partnership',
    'pageCount' => 3,  // Add this!
    'panels' => [...],
    'fields' => [...]
]
```

### PDF Generation Logic

```php
// For each page
for ($pageNum = 1; $pageNum <= $pageCount; $pageNum++) {
    // 1. Add page to PDF
    $pdf->AddPage('P', [215.9, 279.4]);
    
    // 2. Apply background image
    $bgImage = "{$uploadDir}/{$templateId}_page{$pageNum}_background.png";
    if (file_exists($bgImage)) {
        $pdf->Image($bgImage, 0, 0, 215.9, 279.4);
    }
    
    // 3. Place fields for this page
    foreach ($positions as $fieldKey => $position) {
        if ($position['page'] == $pageNum && !empty($values[$fieldKey])) {
            placeField($pdf, $fieldKey, $values[$fieldKey], $position);
        }
    }
}
```

## Modular Components

### 1. BackgroundGenerator
**File**: `mvp/lib/background_generator.php`
- `generatePageBackgrounds($pdfPath, $templateId, $pageCount)`
- Generates grayscale PNG for each page
- Saves as `{templateId}_page{N}_background.png`

### 2. PositionLoader  
**File**: `mvp/lib/field_position_loader.php` (already exists)
- `loadFieldPositions($templateId)` - returns positions with page numbers
- `saveFieldPositions($templateId, $positions)`
- `getFieldsForPage($templateId, $pageNum)` - filters by page

### 3. MultiPageFormFiller
**File**: `mvp/lib/multi_page_form_filler.php` (new)
- Extends `PdfFormFiller`
- `fillMultiPageForm($template, $values, $positions)`
- Handles any number of pages
- Places fields on correct pages

### 4. TemplateManager
**File**: `mvp/lib/template_manager.php` (new)
- `getPageCount($templateId)`
- `getBackgroundPath($templateId, $pageNum)`
- `validateTemplate($template)` - ensures all required fields present

## Directory Structure

```
data/
  ├── {template_id}_positions.json    # Field positions (with page numbers)
  └── mvp.json                         # Database

uploads/
  ├── {template_id}.pdf                # Source PDF
  ├── {template_id}_page1_background.png
  ├── {template_id}_page2_background.png
  └── {template_id}_page3_background.png

mvp/lib/
  ├── background_generator.php         # Generate page backgrounds
  ├── field_position_loader.php        # Load/save positions
  ├── multi_page_form_filler.php       # Multi-page PDF generation
  ├── template_manager.php             # Template configuration
  └── pdf_form_filler.php              # Base filler (update for multi-page)
```

## Usage Example

### Define Template
```php
't_fl100_gc120' => [
    'id' => 't_fl100_gc120',
    'code' => 'FL-100',
    'name' => 'Petition—Marriage/Domestic Partnership',
    'pageCount' => 3,
    'sourceFile' => 'fl100.pdf',
    'fields' => [...]
]
```

### Generate Backgrounds
```php
$bgGen = new BackgroundGenerator();
$bgGen->generateAllPages('t_fl100_gc120', 'uploads/fl100.pdf', 3);
```

### Define Positions
```json
{
  "attorney_name": { "page": 1, "x": 8, "y": 28, ... },
  "signature": { "page": 3, "x": 140, "y": 240, ... }
}
```

### Generate PDF
```php
$filler = new MultiPageFormFiller();
$result = $filler->fillMultiPageForm($template, $values);
// Automatically handles all pages, backgrounds, and positioning
```

## Benefits of This Design

1. **Reusable**: Works with any PDF form (FL-100, FL-105, FL-110, etc.)
2. **Maintainable**: Each component has single responsibility
3. **Extensible**: Easy to add new forms or pages
4. **Testable**: Can test each component independently
5. **Clear**: Position files are self-documenting with page numbers

## Next Steps

1. ✅ Generate grayscale backgrounds for all 3 pages
2. ⏭️ Create modular classes (BackgroundGenerator, TemplateManager, MultiPageFormFiller)
3. ⏭️ Update FL-100 positions with correct page numbers
4. ⏭️ Refactor PDF generation to use new modular system
5. ⏭️ Test complete 3-page FL-100 generation

