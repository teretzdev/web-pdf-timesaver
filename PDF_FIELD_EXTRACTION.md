# PDF Field Extraction - Auto-Position Detection

## Overview

Instead of manually positioning fields, you can now automatically extract field positions from fillable PDFs! This feature detects all form fields in a PDF and generates position data that can be used for automatic form filling.

## How It Works

1. **Upload a fillable PDF** - The PDF must contain AcroForm fields (created in Adobe Acrobat or similar)
2. **System extracts field names and positions** - Uses pdftk or PDF parser to detect all fields
3. **Position file is generated** - Saves field positions to `data/{template_id}_positions.json`
4. **Use for auto-filling** - The existing form filling system automatically uses these positions

## Usage Methods

### Method 1: Web Interface

1. Navigate to **🔧 Field Extractor** in the sidebar
2. Upload your fillable PDF
3. Enter a template ID (e.g., `t_fl100_gc120`)
4. Click "Extract Fields"
5. Position file is automatically generated!

### Method 2: Command Line

```bash
php scripts/extract-pdf-fields.php uploads/your-form.pdf t_your_template_id
```

Example:
```bash
php scripts/extract-pdf-fields.php uploads/fl100.pdf t_fl100_gc120
```

## Requirements

- **pdftk** (recommended) or PDF Parser library
- PDF must contain fillable form fields (AcroForm)
- PDF must NOT be password-protected

## Installing pdftk

### Windows
Already included! The `pdftk_installer.exe` is in your project root.

To install globally:
1. Run `pdftk_installer.exe`
2. Follow installation wizard
3. Add to PATH if needed

### Alternative: Remove PDF Password

If your PDF is password-protected, you can remove the password:

```bash
# Using pdftk (if you know the password)
pdftk protected.pdf input_pw PROMPT output unlocked.pdf

# Or use online tools:
# - https://www.ilovepdf.com/unlock_pdf
# - https://smallpdf.com/unlock-pdf
```

## Output Format

The generated position file (`data/{template_id}_positions.json`) contains:

```json
{
  "attorney_name": {
    "name": "attorney_name",
    "type": "text",
    "page": 1,
    "x": 50.5,
    "y": 120.3,
    "width": 150.0,
    "height": 12.0
  },
  "case_number": {
    "name": "case_number",
    "type": "text",
    "page": 1,
    "x": 400.0,
    "y": 65.0,
    "width": 100.0,
    "height": 15.0
  }
}
```

## Benefits

✅ **No manual positioning** - Automatically detects field locations  
✅ **Accurate placement** - Uses exact coordinates from PDF  
✅ **Faster setup** - Convert any fillable PDF in seconds  
✅ **Easy updates** - Re-extract if PDF changes  
✅ **Works with existing system** - Uses your current overlay approach

## Troubleshooting

### "No fields found in PDF"

**Possible causes:**
1. PDF is password-protected → Remove password first
2. PDF has no fillable fields → Must be a fillable form (not just a scanned document)
3. pdftk not installed → Install pdftk or use web interface

### "PDF is password protected"

**Solution:**
```bash
# Remove password using pdftk
pdftk protected.pdf input_pw PROMPT output unlocked.pdf

# Then extract fields from unlocked.pdf
php scripts/extract-pdf-fields.php unlocked.pdf t_your_template
```

### "pdftk not found"

**Solution:**
1. Run `pdftk_installer.exe` from project root
2. Or download from: https://www.pdflabs.com/tools/pdftk-the-pdf-toolkit/
3. Make sure it's in your system PATH

### Fields detected but positions are wrong

**Cause:** PDF coordinate system uses bottom-left origin

**Solution:** The system should auto-convert. If not, you may need to manually adjust the Y-axis in the generated JSON file.

## Field Name Mapping

If PDF field names don't match your template fields exactly, you can manually edit the generated JSON file to map them:

```json
{
  "PDF_FIELD_NAME": {
    "name": "your_template_field_key",
    ...
  }
}
```

Or the system will attempt fuzzy matching automatically.

## Next Steps

After extracting fields:

1. ✅ Position file is saved to `data/`
2. ✅ System automatically loads positions when filling forms
3. ✅ Test by creating a document with the template
4. ✅ If positions need adjustment, edit the JSON file manually

## Example Workflow

```bash
# 1. Extract fields from PDF
php scripts/extract-pdf-fields.php uploads/fl100.pdf t_fl100_gc120

# 2. View the generated file
cat data/t_fl100_gc120_positions.json

# 3. Use in your application
# Navigate to a project → Add document → Select template → Fill form
# Fields will be automatically positioned!
```

## Notes

- The first time you use a template, it may take a moment to load positions
- Position files are cached for performance
- You can manually edit position files if needed
- Supports multi-page PDFs - each field knows which page it belongs to

## Support

If you encounter issues:
1. Check that pdftk is installed: `pdftk --version`
2. Verify PDF has fillable fields (open in Adobe Reader and try filling)
3. Check logs in `logs/pdf_debug.log`
4. Try the web interface instead of command line (or vice versa)

