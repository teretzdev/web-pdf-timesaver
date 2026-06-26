# PHP Libraries vs Executable Tools

## Two Different Types of Dependencies

Your project uses **two different types** of dependencies:

### 1. PHP Libraries (Composer Packages) - NOT Executables

These are **PHP code libraries** that run inside PHP:

- ✅ `smalot/pdfparser` - PHP library (runs inside PHP)
- ✅ `setasign/fpdi` - PHP library (runs inside PHP)
- ✅ `setasign/fpdf` - PHP library (runs inside PHP)

**How they work:**
- Installed via `composer install`
- Code runs directly in PHP process
- No external programs needed
- Used like: `new Parser()`, `new Fpdi()`

**Example:**
```php
// This is PHP code running inside PHP
use Smalot\PdfParser\Parser;
$parser = new Parser();
$pdf = $parser->parseFile('file.pdf');
```

### 2. Executable Tools - Command-Line Programs

These are **external executable programs** that PHP calls:

- 🔧 `qpdf` - Command-line executable (external program)
- 🔧 `Ghostscript` (gs) - Command-line executable (external program)
- 🔧 `ImageMagick` (magick) - Command-line executable (external program)
- 🔧 `PDFBox` (Java) - Java executable (external program)

**How they work:**
- Installed separately on the system
- PHP calls them using `exec()`, `shell_exec()`, or `system()`
- Run as separate processes
- Used like: `exec('qpdf --decrypt input.pdf output.pdf')`

**Example:**
```php
// This calls an external program
exec('qpdf --decrypt input.pdf output.pdf', $output, $returnCode);
```

## What Composer Installs

When you run `composer install`, it **ONLY installs PHP libraries**:

✅ **Installs:**
- PHP code libraries (smalot/pdfparser, setasign/fpdi, etc.)
- Creates `vendor/autoload.php`
- Makes classes available to PHP

❌ **Does NOT install:**
- qpdf executable
- Ghostscript executable
- ImageMagick executable
- PDFBox executable

## What You Need to Install Separately

### On Windows (Localhost):
- ✅ qpdf - Already in `bin/qpdf/` or installed on system
- ✅ Ghostscript - Already in `gs1000w64.exe` or installed on system
- ✅ ImageMagick - May be installed on system
- ✅ PDFBox - Already in `bin/pdfbox/pdfbox-app-3.0.1.jar`

### On Linux Server (Production):
You need to install these separately:

```bash
# Install qpdf
sudo apt-get install qpdf

# Install Ghostscript
sudo apt-get install ghostscript

# Install ImageMagick (optional)
sudo apt-get install imagemagick

# PDFBox (Java) - requires Java
sudo apt-get install default-jre
# Then use the JAR file from your project
```

## Why the 500 Error?

The 500 error is **NOT** because of missing executables. It's because:

❌ **Missing PHP libraries** (what Composer installs):
- `vendor/autoload.php` doesn't exist
- PHP classes can't be loaded
- Application crashes immediately

✅ **Executables are optional**:
- Your code tries to use them, but has fallbacks
- If qpdf/Ghostscript are missing, code tries alternatives
- Won't cause 500 error (just won't use those features)

## Code Examples

### PHP Library Usage (Requires Composer):
```php
// This REQUIRES composer install
require_once __DIR__ . '/../../vendor/autoload.php';
use Smalot\PdfParser\Parser;

$parser = new Parser();  // ← PHP class, needs vendor/autoload.php
$pdf = $parser->parseFile('file.pdf');
```

### Executable Tool Usage (Optional):
```php
// This is OPTIONAL - code has fallbacks if qpdf doesn't exist
$qpdfPath = __DIR__ . '/../bin/qpdf/bin/qpdf.bat';
if (file_exists($qpdfPath)) {
    exec("$qpdfPath --decrypt input.pdf output.pdf");  // ← External program
} else {
    // Fallback to PHP-only method
}
```

## Summary

| Type | What It Is | How It's Installed | Required? |
|------|------------|-------------------|-----------|
| **PHP Libraries** | Code that runs in PHP | `composer install` | ✅ **YES** - Causes 500 error if missing |
| **qpdf** | External executable | System package manager | ❌ No - Has fallbacks |
| **Ghostscript** | External executable | System package manager | ❌ No - Has fallbacks |
| **ImageMagick** | External executable | System package manager | ❌ No - Has fallbacks |

## Bottom Line

**Composer installs PHP libraries, NOT executable tools.**

- ✅ **Composer packages** = PHP code libraries (required)
- 🔧 **Executable tools** = External programs (optional, installed separately)

The 500 error is because PHP libraries are missing, not because executables are missing.













