# What Composer Installs and Why

## Required Packages (Production)

When you run `composer install`, it installs these **required** packages:

### 1. `smalot/pdfparser` (v2.12.1)
- **What it does**: Parses PDF files to extract text, fields, and metadata
- **Why we need it**: Used in `mvp/lib/pdf_field_extractor.php` to:
  - Read PDF files
  - Extract form field names and positions
  - Parse PDF structure
- **Used by**: `PdfFieldExtractor`, `PdfFieldService`

### 2. `setasign/fpdf` (v1.8.2)
- **What it does**: Generates PDF files from scratch using PHP
- **Why we need it**: Base library for PDF generation
- **Used by**: `setasign/fpdi` (dependency)

### 3. `setasign/fpdi` (v2.6.4)
- **What it does**: Manipulates existing PDF files (import pages, fill forms, merge PDFs)
- **Why we need it**: Used in `mvp/lib/pdf_form_filler.php` and `mvp/lib/fill_service.php` to:
  - Fill PDF forms with data
  - Import PDF pages as backgrounds
  - Generate filled PDF documents
- **Used by**: `FillService`, `PdfFormFiller`, `AutomatedVerificationPipeline`

## Development Packages (Optional - Only for Testing)

### 4. `phpunit/phpunit` (v9.6.29)
- **What it does**: PHP testing framework
- **Why we need it**: Only needed if you want to run tests
- **Not needed for**: Production server (can use `--no-dev` flag)

### 5. Dependencies of PHPUnit
- Various packages like `sebastian/*`, `phpunit/*`, etc.
- Only installed if PHPUnit is installed
- Not needed for production

## What Gets Created

When you run `composer install`, it creates:

1. **`vendor/` directory** - Contains all the installed packages
2. **`vendor/autoload.php`** - Auto-loads classes when needed (this is the critical file!)
3. **`composer.lock`** - Locks versions (already exists, gets updated)

## Why This Causes the 500 Error

Your application code requires these libraries:

```php
// In mvp/lib/pdf_field_extractor.php
require_once __DIR__ . '/../../vendor/autoload.php';
use Smalot\PdfParser\Parser;  // ← Needs smalot/pdfparser

// In mvp/lib/fill_service.php  
require_once __DIR__ . '/../../vendor/autoload.php';
use setasign\Fpdi\Fpdi;  // ← Needs setasign/fpdi
```

**On Localhost (XAMPP):**
- ✅ `vendor/autoload.php` exists
- ✅ All packages are installed
- ✅ Application works

**On Production Server:**
- ❌ `vendor/autoload.php` doesn't exist
- ❌ Packages are not installed
- ❌ Application crashes with 500 error when trying to use these classes

## What Happens When You Run `composer install`

```bash
composer install
```

This will:
1. Read `composer.json` to see what packages are needed
2. Download the packages from Packagist (PHP package repository)
3. Install them in the `vendor/` directory
4. Create `vendor/autoload.php` to load these classes automatically
5. Update `composer.lock` with exact versions

## File Sizes

- **smalot/pdfparser**: ~500 KB
- **setasign/fpdf**: ~200 KB  
- **setasign/fpdi**: ~300 KB
- **Total required packages**: ~1 MB
- **With PHPUnit (dev)**: ~10 MB

## Summary

**What you're installing:**
- PDF parsing library (to read PDFs)
- PDF manipulation library (to fill PDFs)
- PDF generation library (to create PDFs)

**Why you need it:**
- Your application uses these libraries to:
  - Extract fields from PDF forms
  - Fill PDF forms with data
  - Generate filled PDF documents

**Without it:**
- Application crashes with 500 error
- Cannot parse PDF files
- Cannot fill PDF forms
- Cannot generate PDF documents

**With it:**
- Application works correctly
- Can parse PDF files
- Can fill PDF forms
- Can generate PDF documents

## Alternative: Don't Use Composer?

You could manually download these libraries, but:
- ❌ More work to set up
- ❌ Harder to update
- ❌ Manual dependency management
- ✅ Composer handles all of this automatically

## Bottom Line

Running `composer install` is **essential** for your application to work. It installs the PDF libraries that your code depends on. Without it, you get a 500 error because PHP can't find the classes your code is trying to use.














