# Automated PDF Verification Pipeline

## Overview
Comprehensive automated verification system for PDF form field extraction and positioning. This pipeline validates:
1. Position file loading
2. Coordinate validity
3. Field name mapping
4. Data placement accuracy
5. Visual debug generation

## Usage

### Via Web Interface
```
http://localhost/Web-PDFTimeSaver/mvp/automated-verify-endpoint.php?template_id=t_fl100_gc120
```

### Via CLI (PHP)
```bash
php mvp/verify-pdf.php t_fl100_gc120
```

### Via Batch Script (Windows)
```cmd
scripts\automated-verify.bat t_fl100_gc120
```

### Via Shell Script (Linux/Mac)
```bash
./scripts/automated-verify.sh t_fl100_gc120
```

## What It Does

### 1. Positions Loaded Test
- Verifies positions file exists and loads correctly
- Checks field count (minimum 10 fields expected)
- Validates required field types are present (attorney, case number, parties)

### 2. Coordinate Validity Test
- Checks for invalid coordinates (NaN, Infinity)
- Validates coordinates are within page bounds (US Letter: 215.9mm × 279.4mm)
- Flags negative coordinates
- Reports out-of-bounds coordinates

### 3. Field Name Mapping Test
- Tests mapping from test data field names to extracted field names
- Validates at least 70% of fields can be mapped
- Reports unmapped fields
- Shows mapping rate percentage

### 4. Data Placement Test
- Generates actual PDF with test data
- Verifies fields are placed correctly
- Checks field count matches expectations

### 5. Visual Debug Generation
- Creates debug PDF with green boxes showing expected positions
- Overlays actual text for visual comparison
- Helps identify positioning issues

## Output

### JSON Report
Located in: `output/verification/report_[template_id]_[timestamp].json`

Contains:
- Overall status (PASS/FAIL)
- Test results for each verification step
- Issues found
- Statistics and metrics
- Paths to generated PDFs

### HTML Report
Located in: `output/verification/report_[template_id]_[timestamp].html`

Visual report with:
- Color-coded status indicators
- Test details
- Issue listings
- Statistics tables
- Links to generated PDFs

### Debug PDF
Located in: `output/verification/debug_[template_id]_[timestamp].pdf`

Visual debug PDF with:
- Green boxes showing expected field positions
- Actual text overlaid at those positions
- Background image for reference

### Log File
Located in: `logs/verification.log`

Detailed log of all verification steps and results.

## Integration

### Add to Extraction Pipeline
After field extraction, automatically run verification:

```php
$extractor = new PdfFieldExtractor();
$result = $extractor->extractAndGenerateBackgrounds($pdfPath, $templateId, $outputDir);

// Automatically verify
$pipeline = new AutomatedVerificationPipeline();
$verifyResults = $pipeline->verify($templateId);

if ($verifyResults['overall_status'] === 'FAIL') {
    // Handle failures
    error_log("Verification failed for $templateId");
    // Log issues, send alerts, etc.
}
```

### CI/CD Integration
Run verification as part of your CI/CD pipeline:

```yaml
# GitHub Actions example
- name: Verify PDF Extraction
  run: |
    php mvp/verify-pdf.php t_fl100_gc120
    if [ $? -ne 0 ]; then
      echo "Verification failed!"
      exit 1
    fi
```

## Requirements

- PHP 7.4+
- FPDF library (via Composer)
- Position files in `data/` directory
- Test data generator available
- PDF form filler available

## Troubleshooting

### "No positions loaded"
- Check that positions file exists: `data/[template_id]_positions.json`
- Verify file is readable
- Check file contains valid JSON

### "Field mapping failed"
- Review field name patterns in `automated_verification_pipeline.php`
- Check that test data field names match expected patterns
- Verify extracted field names in positions file

### "Coordinate issues"
- Check coordinate conversion logic
- Verify page dimensions match (US Letter: 215.9mm × 279.4mm)
- Review extraction method used (pdf-lib, qpdf, etc.)

## Future Enhancements

- [ ] Visual comparison with expected positions
- [ ] OCR-based position verification
- [ ] Automated position correction
- [ ] Multi-template batch verification
- [ ] Email alerts on failures
- [ ] Integration with monitoring systems

