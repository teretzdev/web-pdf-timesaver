# Production Deployment Checklist

## Why Localhost Works But Production Doesn't

### Common Differences:

1. **Composer Dependencies** ✅ FIXED
   - Localhost: Has `vendor/autoload.php` installed
   - Production: Missing `vendor/autoload.php` → Causes 500 error
   - **Solution**: Run `composer install` on server

2. **File Permissions**
   - Localhost: Files are writable by current user
   - Production: Files owned by `www-data` user, may not be writable
   - **Solution**: Set correct permissions

3. **Directory Structure**
   - Localhost: All directories exist
   - Production: Missing `data/`, `logs/`, `uploads/`, `output/` directories
   - **Solution**: Create directories with correct permissions

4. **PHP Extensions**
   - Localhost: All extensions installed
   - Production: May be missing required extensions
   - **Solution**: Install missing extensions

## Deployment Steps

### Syncthing (two-way folder sync)

After saving files locally, wait **10 seconds** before curling or opening the live site so peers can sync (or run `.\scripts\wait-for-syncthing-sync.ps1` / `./scripts/wait-for-syncthing-sync.sh`).

### 1. Upload Files to Server

```bash
# Upload all files to server (excluding vendor if it exists locally)
rsync -avz --exclude 'vendor' --exclude 'node_modules' \
  ./ user@server:/var/www/pdftimesaver.desktopmasters.com/public_html/
```

### 2. Install Composer Dependencies on Server

```bash
# SSH into server
ssh user@server

# Navigate to project directory
cd /var/www/pdftimesaver.desktopmasters.com/public_html

# Install Composer if not installed
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install dependencies
composer install
```

### 3. Create Required Directories

```bash
# Create directories
mkdir -p data logs uploads output tmp

# Set permissions
sudo chown -R www-data:www-data data logs uploads output tmp
sudo chmod 775 data logs uploads output tmp
```

### 4. Set File Permissions

```bash
# Set ownership
sudo chown -R www-data:www-data /var/www/pdftimesaver.desktopmasters.com/public_html

# Set directory permissions
sudo find /var/www/pdftimesaver.desktopmasters.com/public_html -type d -exec chmod 755 {} \;

# Set file permissions
sudo find /var/www/pdftimesaver.desktopmasters.com/public_html -type f -exec chmod 644 {} \;

# Make specific directories writable
sudo chmod 775 data logs uploads output tmp
```

### 5. Verify PHP Extensions

```bash
# Check if required extensions are installed
php -m | grep -E "gd|mbstring|curl|json|fileinfo|pdo|zip"

# Install missing extensions (Ubuntu/Debian)
sudo apt-get update
sudo apt-get install php8.2-gd php8.2-mbstring php8.2-curl php8.2-xml php8.2-zip php8.2-fileinfo
sudo systemctl restart php8.2-fpm
```

### 6. Test the Application

```bash
# Test if the application loads
curl -I https://pdftimesaver.desktopmasters.com

# Check error logs
sudo tail -f /var/log/nginx/error.log
sudo tail -f /var/log/php8.2-fpm.log
```

## Error Messages You'll See

### If Composer Dependencies Are Missing:
- **Error**: "Composer Dependencies Required"
- **Message**: "Missing: vendor/autoload.php"
- **Fix**: Run `composer install`

### If Directories Are Missing:
- **Error**: "Initialization Error"
- **Message**: "Failed to initialize application services"
- **Fix**: Create directories and set permissions

### If File Permissions Are Wrong:
- **Error**: "Permission denied"
- **Message**: Check error logs for specific files
- **Fix**: Set correct ownership and permissions

## Quick Fix Script

Create a deployment script on the server:

```bash
#!/bin/bash
# deploy.sh - Quick deployment script

cd /var/www/pdftimesaver.desktopmasters.com/public_html

# Install Composer dependencies
composer install

# Create directories
mkdir -p data logs uploads output tmp

# Set permissions
sudo chown -R www-data:www-data .
sudo chmod -R 755 .
sudo chmod -R 775 data logs uploads output tmp

# Restart PHP-FPM
sudo systemctl restart php8.2-fpm

# Test
curl -I https://pdftimesaver.desktopmasters.com
```

## Verification

After deployment, verify:

- [ ] `vendor/autoload.php` exists
- [ ] `data/` directory exists and is writable
- [ ] `logs/` directory exists and is writable
- [ ] `uploads/` directory exists and is writable
- [ ] `output/` directory exists and is writable
- [ ] All PHP extensions are installed
- [ ] File permissions are correct
- [ ] Application loads without errors
- [ ] Error logs show no errors

## Common Issues

### Issue: 500 Error - Composer Dependencies
**Solution**: Run `composer install --no-dev --optimize-autoloader`

### Issue: 500 Error - File Permissions
**Solution**: Set ownership to `www-data:www-data` and permissions to `775` for writable directories

### Issue: 500 Error - Missing Directories
**Solution**: Create `data/`, `logs/`, `uploads/`, `output/` directories

### Issue: 500 Error - Missing PHP Extensions
**Solution**: Install required PHP extensions

### Issue: 500 Error - PHP Version Mismatch
**Solution**: Ensure server PHP version matches localhost PHP version

## Next Steps

1. Upload files to server
2. Install Composer dependencies
3. Create required directories
4. Set file permissions
5. Test the application
6. Check error logs if issues persist

