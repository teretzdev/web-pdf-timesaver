# Web-PDFTimeSaver Installation Guide

**Last Updated:** January 16, 2025

---

## 🚀 Quick Reference (5-Minute Install)

```bash
# Prerequisites
- PHP 7.4+ (XAMPP recommended)
- Node.js 14+ (for JavaScript tools)
- Ghostscript (optional, for encrypted PDFs)

# Basic Install
1. Clone/copy repository
2. Run: composer install
3. Run: npm install
4. Set permissions: chmod 755 data logs output uploads
5. Start XAMPP Apache
6. Visit: http://localhost/Web-PDFTimeSaver/mvp/

# Optional Tools
- Install Ghostscript for background generation
- Configure config/app.php for your environment
- Set environment variables if needed

# Verify
Visit: http://localhost/Web-PDFTimeSaver/mvp/?route=dashboard
Check: logs/app.log for errors
```

---

## 📋 Full Installation

### Prerequisites

**Required:**
- PHP 7.4 or higher (tested with PHP 8.0+)
- Web Server (Apache via XAMPP, or Nginx + PHP-FPM)
- Write Permissions for `data/`, `logs/`, `output/`, `uploads/`

**Recommended:**
- Composer (PHP dependency manager)
- Node.js 14+ (for JavaScript PDF tools)
- XAMPP (easiest Windows setup)

**Optional:**
- Ghostscript (for encrypted PDF background generation) - **INSTALLED ✅**
- qpdf (for PDF decryption) - **INSTALLED ✅**

---

### Step 1: Download/Clone Repository

Clone via Git or download ZIP and extract to your web server directory.

---

### Step 2: Install PHP Dependencies

Run `composer install` to install required packages:
- setasign/fpdi ^2.6
- setasign/fpdf ^1.8
- smalot/pdfparser ^2.12

---

### Step 3: Install Node.js Dependencies

Run `npm install` to install JavaScript PDF tools:
- pdf-lib ^1.17.1
- pdfjs-dist ^5.4.296
- canvas ^3.2.0
- pdf-parse ^2.2.3
- puppeteer ^24.23.0

---

### Step 4: Set Directory Permissions

**Linux/Mac:** Run `chmod 755 data logs output uploads`

**Windows:** XAMPP typically handles permissions automatically. For IIS, grant IIS_IUSRS write access to these directories via icacls or File Explorer properties.

**Note:** Windows users with XAMPP typically don't need permission changes.

---

### Step 5: Configure Web Server

#### Option A: XAMPP (Recommended for Windows)

1. Download XAMPP from apachefriends.org
2. Install to `C:\xampp`
3. Copy project to `C:\xampp\htdocs\Web-PDFTimeSaver`
4. Start Apache via XAMPP Control Panel
5. Access: `http://localhost/Web-PDFTimeSaver/mvp/`

#### Option B: Apache Configuration

Create VirtualHost with:
- DocumentRoot pointing to Web-PDFTimeSaver directory
- AllowOverride All enabled
- PHP handler configured
- Directory access granted

See `nginx/nginx.conf` in repository for reference configuration.

#### Option C: PHP Built-in Server (Development Only)

Run `php -S localhost:8000` in project root.

#### Option D: Nginx

Configure PHP-FPM with proper pass settings. See `nginx/nginx.conf` and `nginx/php-fpm.conf` for complete examples.

**Complete configs:** See `nginx/` directory in repository.

---

### Step 6: Configure Application

Edit `config/app.php` with your settings:

**Basic Settings:**
- `env`: 'development' or 'production'
- `debug`: true in dev, false in production
- `paths`: Verify directory paths are correct
- `upload.max_size`: 10MB default
- `upload.allowed_types`: ['application/pdf']

**Environment Variables** (optional):
- `APP_ENV=development|production`
- `APP_DEBUG=1` - Enable debug mode
- `LOG_LEVEL=debug|info|error`
- `MVP_DEBUG_LOG=1` - Verbose logging

**Windows:** Set via System Properties → Environment Variables  
**Linux/Mac:** Export in shell or use .env file

---

### Step 7: Install Optional Tools

#### Ghostscript (for Encrypted PDFs) ✅ INSTALLED

**Status:** Already installed (version 10.00.0 detected)  
**Verify:** Run `gswin64c --version` (Windows) or `gs --version` (Linux)  
**Usage:** Automatically used by the application for PDF to image conversion

#### qpdf (for PDF Decryption) ✅ INSTALLED

**Status:** Already installed (version 12.2.0 detected)  
**Verify:** Run `qpdf --version`  
**Usage:** Automatically used for decrypting encrypted PDFs before field extraction  
**Note:** Both Node.js pipeline and PHP direct methods support qpdf

---

### Step 8: Verify Installation

**Access URLs:**
- Dashboard: `http://localhost/Web-PDFTimeSaver/mvp/?route=dashboard`
- Projects: `http://localhost/Web-PDFTimeSaver/mvp/?route=projects`
- Clients: `http://localhost/Web-PDFTimeSaver/mvp/?route=clients`

**Check Logs:**
- `logs/app.log` - Application logs
- `logs/pdf_debug.log` - PDF processing logs
- PHP error log (server-specific location)

**Test Functionality:**
1. Create a project
2. Add a document
3. Upload a PDF
4. Verify no errors in logs

**Run Tests (Optional):**
`php tests/run_all.php` - Note: Some tests may fail due to template architecture changes

---

### Step 9: Initial Setup (First Run)

The application auto-creates demo data on first run:
- Client: "John Doe"
- Project: "BHBA EVENT (JOHN DOE)"
- Initial document (if templates available)

**Manual Setup:** Navigate to Clients or Projects in the dashboard to create your own structure.

---

## 🔧 Troubleshooting

**Permission Errors:**
- Linux/Mac: `chmod -R 755 data logs output uploads`
- Windows: Grant IIS_IUSRS or your web server user write permissions to these directories

**Composer Issues:**
- Run `composer install --no-interaction` and `composer dump-autoload`

**Node Modules Missing:**
- Run `npm install --legacy-peer-deps` or delete `node_modules` and `package-lock.json` then reinstall

**PDF Upload Errors:**
- Check `uploads/` directory exists and is writable
- Verify PDF format is correct
- Check `logs/app.log` for specific errors

**Ghostscript Not Found:** (Should be installed already)
- Verify installation: Run `gswin64c --version` (should show version 10.00.0+)
- Windows: If missing, download from ghostscript.com and add to PATH
- Linux: Install via `sudo apt-get install ghostscript`

**Dashboard Blank/Errors:**
- View `logs/app.log` for errors
- Verify `data/mvp.json` exists and is valid JSON
- Check PHP error log
- Ensure dependencies installed: `composer show` and `npm list`

**PDF Generation Fails:**
- Check `logs/app.log` and `logs/pdf_debug.log`
- Verify `output/` directory writable
- Increase PHP memory_limit if below 256M

---

## 📁 Directory Structure After Install

```
Web-PDFTimeSaver/
├── vendor/              # Composer dependencies
├── node_modules/        # Node.js dependencies
├── data/
│   ├── mvp.json         # Main database (auto-created)
│   └── *_positions.json # Field positions
├── logs/
│   ├── app.log          # Application logs (auto-created)
│   └── pdf_debug.log    # PDF processing logs
├── output/              # Generated PDFs (auto-created)
├── uploads/             # Uploaded PDFs (auto-created)
├── config/
│   └── app.php          # Configuration
├── mvp/
│   ├── index.php        # Application entry
│   ├── lib/             # Core libraries
│   ├── views/           # UI templates
│   └── templates/       # Template registry
├── tests/               # Test suite
├── scripts/             # Utilities
└── composer.json        # PHP dependencies
```

---

## 🔐 Security Checklist

- Set `debug: false` in `config/app.php` for production
- Set `APP_ENV=production` environment variable
- Verify `.htaccess` file present and configured
- Protect `data/` and `logs/` directories
- Configure file upload size limits
- Disable PHP error display in production
- Set restrictive file permissions on database
- Regular backups of `data/mvp.json`

---

## 🌐 Production Deployment

**Environment:**
- Set `APP_ENV=production`
- Set `debug=false` in `config/app.php`
- Disable PHP error display, enable logging
- Review `.htaccess` security rules
- Use HTTPS
- Configure firewall rules
- Set up CSP headers

**Performance:**
- Enable OPcache
- Configure PHP-FPM pools
- Set up caching (if applicable)
- Enable Gzip compression

**Monitoring:**
- Set up log rotation
- Configure monitoring/alerts
- Regular backups
- Disk space monitoring

**Recommended php.ini Settings:**
- memory_limit = 256M
- upload_max_filesize = 10M
- post_max_size = 11M
- display_errors = Off
- log_errors = On

---

## 📊 Post-Install Verification

**Check Files:**
- `ls -la data/ logs/ output/ uploads/` - directories exist and writable
- `composer show` - dependencies installed
- `npm list` - Node packages installed

**Check Permissions:**
- All four directories (data, logs, output, uploads) must be writable

**Check Versions:**
- `php -v` - Should be 7.4 or higher
- `node --version` - Should be 14 or higher

**Check Optional Tools:**
- `which ghostscript` or `gswin64c` - Ghostscript installed
- `which qpdf` - qpdf installed (optional)

**Access URLs:**
1. Dashboard: `http://localhost/Web-PDFTimeSaver/mvp/?route=dashboard`
2. Projects: `http://localhost/Web-PDFTimeSaver/mvp/?route=projects`
3. Clients: `http://localhost/Web-PDFTimeSaver/mvp/?route=clients`

All should load without errors.

---

## 📞 Getting Help

**Documentation:**
- `README.md` - General information
- `FUNCTIONALITY_DOCUMENTATION.md` - Feature details
- `FUNCTIONALITY_QUICK_REFERENCE.md` - Quick overview
- `QUICK_START_GUIDE.md` - Usage guide

**Logs:**
- `logs/app.log` - Application logs
- `logs/pdf_debug.log` - PDF processing logs
- PHP error log (server-specific)

**Common Issues:** See README.md Troubleshooting section or check logs for specific errors.

---

## ✨ Quick Start After Install

1. Start server (XAMPP Control Panel or `php -S localhost:8000`)
2. Open `http://localhost/Web-PDFTimeSaver/mvp/?route=dashboard`
3. Create first project: Dashboard → Create Project → Add Document → Upload PDF → Generate
4. Monitor logs: `tail -f logs/app.log`

---

**Installation Complete!** 🎉

If everything loads, you're ready to use Web-PDFTimeSaver. See `QUICK_START_GUIDE.md` for usage instructions.
