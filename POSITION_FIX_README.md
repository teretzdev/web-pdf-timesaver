# PDF Position Fix Guide

## What Was Fixed

1. **Coordinate System Issue**: The positions were being incorrectly converted from pixels to millimeters when they were already in millimeters. This has been fixed.

2. **Position Data**: Updated the `t_fl100_gc120_positions.json` file with accurate coordinates for FL-100 form fields.

3. **PDF Filling Logic**: Fixed the `fillPdfFormWithPositions()` function to:
   - Use positions directly (no conversion needed)
   - Handle different field types (text, checkbox, date, etc.)
   - Add logging for debugging
   - Use Cell() instead of Write() for better positioning control

## Tools Created

### 1. Position Extractor (`pdf_position_extractor.php`)
Analyzes PDF files to extract form field positions automatically.

### 2. Position Updater (`update_pdf_positions.php`)
```bash
# Generate standard FL-100 positions
php scripts/update_pdf_positions.php generate

# Extract positions from a PDF
php scripts/update_pdf_positions.php extract t_fl100_gc120 /path/to/pdf.pdf

# Update positions (combines extracted + generated)
php scripts/update_pdf_positions.php update
```

### 3. Position Calibrator (`calibrate_pdf_positions.php`)
Creates a visual PDF showing where fields will be placed:
```bash
php scripts/calibrate_pdf_positions.php
```
This creates a PDF with:
- Grid lines every 10mm
- Coordinate labels
- Color-coded field rectangles
- Field names and coordinates
- Legend for field types

### 4. Position Adjuster (`adjust_positions.php`)
Interactive tool for fine-tuning positions:
```bash
# List all fields
php scripts/adjust_positions.php list

# Show specific field details
php scripts/adjust_positions.php show attorney_name

# Set field position
php scripts/adjust_positions.php set attorney_name 20 58 100 5

# Move field by offset
php scripts/adjust_positions.php move attorney_name 5 -2

# Copy position from one field to another
php scripts/adjust_positions.php copy attorney_name attorney_for

# Delete a field
php scripts/adjust_positions.php delete unused_field

# Reset to defaults
php scripts/adjust_positions.php reset

# Export as PHP array
php scripts/adjust_positions.php export
```

### 5. Position Tester (`test_pdf_positions.php`)
Tests the PDF generation with sample data:
```bash
php scripts/test_pdf_positions.php
```

## Position Format

Positions are stored in `/workspace/data/t_fl100_gc120_positions.json` with this structure:
```json
{
  "field_name": {
    "page": 1,           // Page number (1-based)
    "x": 20,             // X coordinate in mm from left
    "y": 58,             // Y coordinate in mm from top
    "width": 100,        // Field width in mm
    "height": 5,         // Field height in mm
    "type": "text",      // Field type: text, checkbox, date, signature, email, phone
    "label": "Field Label"
  }
}
```

## Common FL-100 Field Positions (in mm)

| Field | X | Y | Width | Height | Notes |
|-------|---|---|-------|--------|-------|
| case_number | 142 | 27 | 46 | 6 | Top right corner |
| attorney_name | 20 | 58 | 100 | 5 | Attorney section |
| attorney_bar_number | 125 | 58 | 65 | 5 | Right of attorney name |
| attorney_firm | 20 | 67 | 170 | 5 | Full width |
| attorney_address | 20 | 76 | 170 | 5 | Full width |
| attorney_phone | 20 | 94 | 80 | 5 | Left side |
| attorney_email | 20 | 103 | 170 | 5 | Full width |
| court_county | 50 | 131 | 70 | 5 | After "County of" |
| petitioner_name | 20 | 180 | 170 | 5 | Party section |
| respondent_name | 20 | 189 | 170 | 5 | Below petitioner |

## Debugging

Check `/workspace/logs/pdf_debug.log` for:
- Fields being filled and their positions
- Any errors during PDF generation
- Template loading issues

## Next Steps to Fine-Tune

1. **Generate a calibration PDF** to see current positions:
   ```bash
   php scripts/calibrate_pdf_positions.php
   ```

2. **Review the output** in `/workspace/output/calibration_*.pdf`

3. **Adjust positions** as needed:
   ```bash
   # Example: Move attorney_name field 5mm to the right
   php scripts/adjust_positions.php move attorney_name 5 0
   ```

4. **Test the changes**:
   ```bash
   php scripts/test_pdf_positions.php
   ```

5. **Repeat** until positions are perfect

## Tips for Position Adjustment

- **Use the calibration PDF** as a visual guide
- **Small adjustments**: Move fields by 1-2mm at a time
- **Test frequently**: Generate test PDFs after each adjustment
- **Check alignment**: Ensure fields align with form lines
- **Field widths**: Adjust widths to fit the form's input areas
- **Checkboxes**: Use 4x4mm for standard checkbox size

## Coordinate System

- **Origin**: Top-left corner (0, 0)
- **X-axis**: Increases to the right (0-210mm for A4)
- **Y-axis**: Increases downward (0-297mm for A4)
- **Units**: All measurements in millimeters (mm)

## Field Types

- `text`: Regular text input
- `checkbox`: Checkbox field (renders 'X' when checked)
- `date`: Date field (formatted as MM/DD/YYYY)
- `signature`: Signature area
- `email`: Email address field
- `phone`: Phone number field

The system now properly handles positioning for all field types and renders them correctly on the PDF.