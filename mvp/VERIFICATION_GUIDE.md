# Position Verification Guide - 100% Accuracy Verification

This guide explains how to verify that text layer positions are 100% accurate in your generated PDFs.

## Overview

The verification system provides three ways to verify positions:

1. **Command Line Tool** - Automated verification via CLI
2. **Web Interface** - User-friendly browser-based verification
3. **Programmatic API** - Use in your own scripts

## Quick Start

### Option 1: Web Interface (Recommended)

1. Navigate to: `http://localhost/Web-PDFTimeSaver/mvp/verify-positions-web.php`
2. Select your template ID
3. Optionally provide a PDF path (or leave empty to generate a test PDF)
4. Click "Run Verification"
5. Review the results:
   - Overall accuracy percentage
   - Fields matched vs mismatched
   - Visual overlay showing expected positions
   - Detailed JSON report

### Option 2: Command Line

```bash
# Verify with auto-generated test PDF
php mvp/verify-positions.php t_fl100_gc120

# Verify a specific PDF
php mvp/verify-positions.php t_fl100_gc120 uploads/my_pdf.pdf
```

## How It Works

The verification system:

1. **Loads Expected Positions** - Reads from `data/{template_id}_positions.json`
2. **Generates Test PDF** - Creates a PDF with known test data (or uses provided PDF)
3. **Extracts Actual Positions** - Parses the generated PDF to find where text actually appears
4. **Compares Positions** - Compares expected vs actual with 2mm tolerance
5. **Generates Report** - Creates detailed accuracy report and visual overlay

## Verification Criteria

### Accuracy Levels

- **PASS (≥95%)** - All positions within 2mm tolerance
- **WARNING (80-94%)** - Some positions off by 2-4mm
- **FAIL (<80%)** - Significant position errors

### What Gets Verified

- ✅ X coordinate accuracy
- ✅ Y coordinate accuracy  
- ✅ Text appears at expected location
- ✅ Field values match expected text
- ✅ No missing fields
- ✅ No extra unexpected text

## Understanding Results

### Overall Accuracy

The percentage of fields that match expected positions within tolerance.

### Status Badge

- 🟢 **PASS** - All good, positions are accurate
- 🟡 **WARNING** - Minor issues, review recommended
- 🔴 **FAIL** - Significant problems, positions need fixing

### Issues Found

Each issue shows:
- **Field name** - Which field has the problem
- **Expected position** - Where it should be (x, y in mm)
- **Actual position** - Where it actually is
- **Difference** - How far off (in mm)
- **Severity** - Critical or Warning

## Visual Overlay

The visual overlay shows:
- PDF background image
- Green boxes indicating expected field positions
- Field labels on each box

**How to use:**
1. Open the overlay HTML file in your browser
2. Compare green boxes with actual text in the PDF
3. If boxes don't align with text, positions need adjustment

## Detailed Report

The JSON report contains:
- Timestamp of verification
- PDF path used
- Overall accuracy metrics
- List of all matches with differences
- List of all issues with details
- Summary status

## Fixing Position Issues

If verification finds issues:

1. **Note the field name** from the issues list
2. **Check the difference** - how many mm off
3. **Open the position JSON file** - `data/{template_id}_positions.json`
4. **Adjust coordinates**:
   - If text is too far right: decrease `x`
   - If text is too far left: increase `x`
   - If text is too low: decrease `y`
   - If text is too high: increase `y`
5. **Re-run verification** to confirm fix

## Example: Fixing a Position

```
Issue: Field 'attorney_name': Expected (70.00, 95.00) but found (72.50, 95.00) - difference: 2.50mm
```

This means the text is 2.5mm too far to the right. Fix:
- Open `data/t_fl100_gc120_positions.json`
- Find `attorney_name`
- Change `"x": 70` to `"x": 67.5` (move left by 2.5mm)
- Save and re-verify

## Programmatic Usage

```php
use WebPdfTimeSaver\Mvp\PositionVerifier;

$verifier = new PositionVerifier();

$report = $verifier->verifyPdfPositions(
    $pdfPath,
    $expectedPositions,
    $fieldValues
);

if ($report['overallAccuracy'] >= 95) {
    echo "✅ Positions are accurate!";
} else {
    echo "❌ Issues found: " . count($report['issues']);
}
```

## Troubleshooting

### "Position file not found"
- Ensure `data/{template_id}_positions.json` exists
- Check template ID spelling

### "PDF file not found"
- Verify PDF path is correct
- Check file permissions

### "Text not found in PDF"
- Field value might be empty
- Text extraction might have failed
- Check PDF is not corrupted

### Low Accuracy
- Verify coordinate system (should be mm, top-left origin)
- Check page dimensions match expected (US Letter: 215.9mm × 279.4mm)
- Ensure positions were saved correctly

## Best Practices

1. **Run verification after any position changes**
2. **Use visual overlay for manual confirmation**
3. **Keep accuracy above 95%** for production
4. **Document any intentional deviations** from expected positions
5. **Re-verify after template updates**

## Integration with CI/CD

Add to your test suite:

```bash
#!/bin/bash
php mvp/verify-positions.php t_fl100_gc120
if [ $? -ne 0 ]; then
    echo "Position verification failed!"
    exit 1
fi
```

## Support

For issues or questions:
- Check logs: `logs/position_verification.log`
- Review detailed JSON report
- Use visual overlay for manual inspection

---

**Remember**: Verification ensures your PDFs are accurate. Always verify before deploying to production!

