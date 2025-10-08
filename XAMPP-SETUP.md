# XAMPP Setup Guide for Web-PDFTimeSaver

This guide will help you set up the Web-PDFTimeSaver project to work with XAMPP on Windows.

## Prerequisites

- XAMPP installed (download from https://www.apachefriends.org/)
- Windows operating system

## Quick Setup

1. **Run the setup script:**
   ```cmd
   setup-xampp.bat
   ```

2. **Or manually follow these steps:**

### Step 1: Start XAMPP Services

1. Open XAMPP Control Panel:
   ```
   C:\xampp\xampp-control.exe
   ```

2. Start Apache and MySQL services by clicking "Start" next to each service.

### Step 2: Copy Project to XAMPP

1. Copy your project folder to the XAMPP htdocs directory:
   ```
   C:\xampp\htdocs\Web-PDFTimeSaver\
   ```

2. Or create a symbolic link (recommended for development):
   ```cmd
   mklink /D "C:\xampp\htdocs\Web-PDFTimeSaver" "C:\Users\Shadow\Web-PDFTimeSaver"
   ```

### Step 3: Install Dependencies

1. Open Command Prompt in your project directory
2. Run:
   ```cmd
   C:\xampp\php\php.exe composer.phar install
   ```

### Step 4: Test the Setup

1. Open your web browser
2. Navigate to: `http://localhost/Web-PDFTimeSaver/`
3. You should see the Web-PDFTimeSaver application

## Access URLs

- **Main Application:** http://localhost/Web-PDFTimeSaver/
- **App Interface:** http://localhost/Web-PDFTimeSaver/
- **Setup Test:** http://localhost/Web-PDFTimeSaver/test-setup.php

## Configuration Files

### .htaccess
The project includes an optimized `.htaccess` file with:
- URL rewriting for clean URLs
- Security headers
- File access restrictions
- PHP configuration settings

### PHP Settings
The following PHP settings are configured:
- Upload limit: 10MB
- Post limit: 10MB
- Execution time: 300 seconds
- Error reporting: E_ALL (logged, not displayed)

## Troubleshooting

### Apache Won't Start
1. Check if port 80 is in use:
   ```cmd
   netstat -an | findstr :80
   ```
2. Change Apache port in XAMPP Control Panel if needed
3. Run XAMPP Control Panel as Administrator

### PHP Errors
1. Check PHP error logs in: `C:\xampp\apache\logs\error.log`
2. Enable error display temporarily by editing `.htaccess`:
   ```
   php_flag display_errors On
   ```

### File Permissions
1. Ensure these directories are writable:
   - `data/`
   - `logs/`
   - `output/`
   - `uploads/`

### Composer Issues
1. Update Composer:
   ```cmd
   C:\xampp\php\php.exe composer.phar self-update
   ```
2. Clear Composer cache:
   ```cmd
   C:\xampp\php\php.exe composer.phar clear-cache
   ```

## Development Tips

1. **Use symbolic links** to avoid copying files between directories
2. **Enable XAMPP's error logging** for debugging
3. **Use the test script** (`test-setup.php`) to verify your setup
4. **Check the logs** in the `logs/` directory for application errors

## Security Notes

- The `.htaccess` file restricts access to sensitive files
- Data directory is protected from web access
- Vendor directory is protected from web access
- Security headers are enabled when mod_headers is available

## Support

If you encounter issues:
1. Run the test script: `http://localhost/Web-PDFTimeSaver/test-setup.php`
2. Check XAMPP error logs
3. Verify all services are running in XAMPP Control Panel
4. Ensure file permissions are correct

