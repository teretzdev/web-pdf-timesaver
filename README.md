# PDF Form Filler - Time Saver

A web-based application that allows you to upload PDF files, fill in form fields, and download the completed document.

## MVP (Clio Draft-inspired)

- Entry point: `mvp/index.php`
- Data: JSON store at `data/mvp.json`
- Templates: `mvp/templates/registry.php`
- Generate & Download: from Project → Generate → Download

## Tests

Run with XAMPP PHP:

```
C:\xampp\php\php.exe tests\run_all.php
```

To remove leftover sync-conflict files (Windows PowerShell):

```powershell
.\scripts\cleanup-sync-conflicts.ps1
```

Includes:
- Datastore CRUD
- Template registry
- PDF generation
- PDF export content verification

## Features

- 📄 **PDF Upload**: Upload PDF files up to 10MB
- 👁️ **PDF Preview**: View the uploaded PDF in the browser
- 📝 **Form Filling**: Fill in form fields with a user-friendly interface
- 💾 **Download**: Download the completed PDF with filled form data
- 🎨 **Modern UI**: Beautiful, responsive design with drag-and-drop upload
- ⚡ **Fast Processing**: Quick upload and processing

## Requirements

- PHP 7.4 or higher
- Web server (Apache, Nginx, or PHP built-in server)
- Write permissions for `uploads/` and `output/` directories

## Installation

1. Clone or download this repository
2. Ensure PHP is installed on your system
3. Make sure the `uploads/` and `output/` directories are writable
4. Start a web server pointing to this directory

### Using PHP Built-in Server (Development)

```bash
php -S localhost:8000
```

Then open your browser and go to `http://localhost:8000`

### Using Apache/Nginx

1. Copy the files to your web server directory
2. Ensure mod_rewrite is enabled (for Apache)
3. Set proper permissions for the `uploads/` and `output/` directories
4. Access the application through your web browser

## Usage

1. **Upload PDF**: Click the upload area or drag and drop a PDF file
2. **Preview**: The PDF will be displayed in the preview area
3. **Fill Form**: Use the form fields on the left to enter your data
4. **Download**: Click "Fill PDF & Download" to get your completed document

## File Structure

```
Web-PDFTimeSaver/
├── index.php                 # Root entry (redirects to mvp/index.php)
├── .htaccess                 # Apache security and configuration
├── composer.json             # PHP dependencies
├── package.json              # Node dependencies
├── config/                   # Centralized configuration
│   └── app.php               # Application settings
├── mvp/                      # Main MVP application
│   ├── index.php             # Router and entry point
│   ├── lib/                  # Business logic and services
│   ├── templates/            # Template registry
│   └── views/                # UI views
├── legacy/                   # Archived test and analysis tools
├── data/                     # JSON data storage (protected)
├── logs/                     # Application logs (protected)
├── uploads/                  # Uploaded PDF templates
├── output/                   # Generated PDF documents
├── tests/                    # Test suite
├── vendor/                   # Composer dependencies (protected)
└── README.md                 # This file
```

## Configuration

### Application Configuration
The main configuration file is `config/app.php`. You can customize:
- File upload limits and allowed types
- PDF processing settings
- Logging configuration
- Security settings
- Feature flags

### Environment Variables
Override configuration via environment variables:
- `APP_DEBUG=1` - Enable debug mode
- `APP_ENV=development` - Set environment (development/production)
- `LOG_LEVEL=debug` - Set logging level (debug/info/error)
- `SEED_DEMO=1` - Seed demo data on first run

### Server Configuration
- `.htaccess` - Apache security headers and access restrictions
- `nginx/nginx.conf` - Nginx configuration (if using Nginx)
- `nginx/php-fpm.conf` - PHP-FPM settings (if using Nginx)

## Security Notes

### File Security
- Uploaded files are validated for type and size (10MB max)
- File names are sanitized to prevent directory traversal attacks
- Only PDF files are accepted

### Directory Protection
The `.htaccess` file protects sensitive directories:
- `/data` - JSON data storage
- `/logs` - Application logs
- `/vendor` - Composer dependencies
- `/config` - Configuration files

### Security Headers
The application implements security headers:
- `X-Frame-Options: SAMEORIGIN` - Prevents clickjacking
- `X-XSS-Protection: 1; mode=block` - XSS protection
- `X-Content-Type-Options: nosniff` - MIME type sniffing protection
- `Referrer-Policy: strict-origin-when-cross-origin` - Referrer control

### File Permissions
Recommended directory permissions:
- Directories: `0755` (rwxr-xr-x)
- Files: `0644` (rw-r--r--)
- Writable directories (`data/`, `logs/`, `output/`, `uploads/`): `0755` with web server write access

### Best Practices
- Keep sensitive data files (.json, .log, .conf) outside web root when possible
- Use environment variables for sensitive configuration (see `config/app.php`)
- Regularly review and rotate log files
- Keep dependencies updated: `composer update` and `npm update`

## Troubleshooting

### Common Issues

1. **Upload fails**
   - Check file size (max 10MB configured in `config/app.php`)
   - Verify file type is PDF only
   - Check `uploads/` directory permissions (should be writable)

2. **Permission errors**
   - Ensure these directories are writable: `data/`, `logs/`, `output/`, `uploads/`
   - On Linux/Mac: `chmod 755 data logs output uploads`
   - On Windows: Give write permissions to the web server user

3. **PDF generation fails**
   - Check `logs/app.log` and `logs/pdf_debug.log` for error details
   - Verify PDF template exists in `uploads/` directory
   - Ensure enough disk space in `output/` directory

4. **JSON syntax errors**
   - Validate JSON files in `data/` directory
   - Use online JSON validators or `php -r "json_decode(file_get_contents('file.json'));"`
   - Check for trailing commas or duplicate keys

5. **Session/login issues**
   - Clear browser cookies and cache
   - Check session directory permissions
   - Verify session timeout in `config/app.php`

### Error Messages

- **"Only PDF files are allowed"**: Upload a valid PDF file
- **"File size must be less than 10MB"**: Reduce file size or adjust limit in `config/app.php`
- **"Failed to upload file"**: Check directory permissions and disk space
- **"No PDF file in session"**: Upload a PDF file before filling
- **"JSON syntax error"**: Fix malformed JSON in data files
- **"Permission denied"**: Adjust file/directory permissions (see Security Notes)

### Debug Mode

Enable debug mode for detailed error information:
1. Set `APP_DEBUG=1` environment variable, or
2. Edit `config/app.php`: `'debug' => true`
3. Check `logs/app.log` for detailed error traces

**Warning**: Disable debug mode in production!

### Legacy Files

Old test and analysis tools have been moved to the `/legacy` directory. These include:
- Field position measurement tools
- PDF comparison utilities
- Analysis scripts
- Test generators

These files are kept for reference but are not part of the main application.

## Development

This application uses a simplified PDF processing approach for demonstration purposes. For production use with actual PDF form filling, consider integrating with libraries like:

- FPDI (setasign/fpdi)
- TCPDF
- mPDF
- DomPDF

## License

This project is open source and available under the MIT License.

## Support

For issues or questions, please check the troubleshooting section above or create an issue in the project repository.

