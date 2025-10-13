# Auto-Fill Field Detection - Quick Start Guide

## 🎯 What's New

You can now **automatically detect field positions** from any fillable PDF! No more manual coordinate mapping - just upload your PDF and go!

## 🚀 Quick Start

### Option 1: Web Interface (Easiest)

1. Open your browser and navigate to: `http://localhost/mvp/?route=extract-fields`
2. Click **"🔧 Field Extractor"** in the sidebar
3. Upload your fillable PDF
4. Enter a template ID (e.g., `t_fl100_gc120`)
5. Click "Extract Fields"
6. Done! The system now knows where all fields are located

### Option 2: Command Line

```bash
# First, unlock your PDF if it's password-protected
php scripts/unlock-pdf.php uploads/fl100.pdf uploads/fl100_unlocked.pdf

# Then extract field positions
php scripts/extract-pdf-fields.php uploads/fl100_unlocked.pdf t_fl100_gc120
```

## 📋 For Your FL-100 Form (Password-Protected PDFs)

**NEW: Hybrid Approach!** Even if your PDF is password-protected, we can still:
1. ✅ Extract field positions from the PDF metadata
2. ✅ Render PDF as background images
3. ✅ Overlay text using auto-detected positions

### Quick Method (All-in-One):

Just upload your password-protected PDF directly:

```bash
# Use the web interface - it handles everything automatically!
# Go to: http://localhost/mvp/?route=extract-fields
# Upload your FL-100.pdf (even if password-protected)
# The system will:
#   - Extract field names and positions
#   - Generate background images for each page
#   - Create position file
#   - Save the template
```

**OR via command line:**

```bash
php scripts/extract-pdf-fields.php uploads/fl100.pdf t_fl100_gc120
```

The system will automatically:
1. ✅ Try to extract field metadata (even with password)
2. ✅ Convert PDF pages to background images using Ghostscript
3. ✅ Generate position file for overlay

### Test It

1. Go to a project
2. Add a new document using the `t_fl100_gc120` template
3. Fill out the form
4. Generate PDF
5. Fields will be automatically placed using the detected positions + background images!

## 🎨 How It Works

```
┌─────────────────┐
│ Fillable PDF    │
│ (with AcroForm  │
│  field names)   │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Field Extractor │
│ - Detects names │
│ - Gets positions│
│ - Maps to pages │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Position JSON   │
│ t_xxx_pos.json  │
│ in data/        │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Form Filling    │
│ Uses detected   │
│ positions       │
└─────────────────┘
```

## 📁 Files Created

After extraction, you'll have:

```
data/
  └── t_fl100_gc120_positions.json  ← Auto-generated position file
```

This file contains:
- Field names from the PDF
- Exact X,Y coordinates
- Page numbers
- Field types (text, checkbox, etc.)

## 🔍 Verify Extraction

After extracting, check the results:

```bash
# View the generated position file
cat data/t_fl100_gc120_positions.json

# Or use the web interface - it shows a table of all detected fields
```

## 🛠️ Troubleshooting

### "No fields found"
- ✅ Make sure PDF is unlocked (not password-protected)
- ✅ Verify PDF has fillable fields (open in Adobe Reader and try typing)
- ✅ Check if pdftk is installed: `pdftk --version`

### "Password protected"
- ✅ Use `scripts/unlock-pdf.php` to remove password
- ✅ Or use online PDF unlock tools

### "pdftk not found"
- ✅ Run `pdftk_installer.exe` from project root
- ✅ Restart your terminal after installation

## 💡 Pro Tips

1. **Test with a simple PDF first** - Use one of the test forms in `uploads/`

2. **Check field names** - The PDF field names should match your template field keys (or system will attempt auto-mapping)

3. **Multi-page support** - Each field knows which page it's on!

4. **Edit if needed** - You can manually edit the JSON file if positions need tweaking

5. **Re-extract anytime** - If your PDF changes, just run extraction again

## 📊 Example Output

When extraction succeeds, you'll see:

```
Found 45 form fields:

  attorney_name              Type: text       Page: 1  Pos: (50.5, 120.3)
  attorney_firm              Type: text       Page: 1  Pos: (50.5, 140.8)
  case_number                Type: text       Page: 1  Pos: (400.0, 65.0)
  petitioner_name            Type: text       Page: 1  Pos: (50.5, 180.0)
  ...

✓ Position file generated: data/t_fl100_gc120_positions.json

You can now use this template with auto-detected field positions!
```

## 🎯 Next Steps

After extraction:

1. ✅ Create a test document with the template
2. ✅ Fill out the form
3. ✅ Generate PDF and verify field placement
4. ✅ Adjust positions in JSON if needed (rare)
5. ✅ Enjoy automatic form filling!

## 🆘 Need Help?

See the full documentation: `PDF_FIELD_EXTRACTION.md`

Or check the logs: `logs/pdf_debug.log`

---

**Ready to try it? Start here:**
```bash
# For FL-100 form
php scripts/unlock-pdf.php uploads/fl100.pdf uploads/fl100_unlocked.pdf
php scripts/extract-pdf-fields.php uploads/fl100_unlocked.pdf t_fl100_gc120
```

Then test by creating a document in the web interface! 🎉

