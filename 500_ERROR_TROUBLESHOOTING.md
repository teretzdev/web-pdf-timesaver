# 500 Error Troubleshooting Guide for pdftimesaver.desktopmasters.com

## Issue Fixed: Domain Typo in Nginx Configuration

**Problem**: The nginx configuration file had a typo - "dektopmasters" instead of "desktopmasters" (missing the 's').

**Solution**: Fixed the domain name in:
- `nginx/production-pdftimesaver.conf`
- `PRODUCTION_DEPLOYMENT.md`
- `scripts/diagnose-500-error.sh`

## Next Steps to Fix 500 Error on Server

### 1. Update Nginx Configuration on Server

The nginx config file has been fixed locally. You need to update it on the server:

```bash
# SSH into your server
ssh user@your-server

# Backup the current config (if it exists)
sudo cp /etc/nginx/sites-available/pdftimesaver.desktopmasters.com /etc/nginx/sites-available/pdftimesaver.desktopmasters.com.backup

# Upload the fixed config file from your local machine
# (Use scp, rsync, or your preferred method)
# Then copy it to the nginx directory:
sudo cp /path/to/fixed/production-pdftimesaver.conf /etc/nginx/sites-available/pdftimesaver.desktopmasters.com

# Test the configuration
sudo nginx -t

# If test passes, reload nginx
sudo systemctl reload nginx
```

### 2. Verify Domain Name Match

Make sure the `server_name` in the nginx config matches your actual domain:
- ✅ Correct: `pdftimesaver.desktopmasters.com`
- ❌ Wrong: `pdftimesaver.dektopmasters.com`

### 3. Check PHP-FPM Status

```bash
# Check if PHP-FPM is running
sudo systemctl status php8.2-fpm
# OR
sudo systemctl status php-fpm

# If not running, start it:
sudo systemctl start php8.2-fpm
sudo systemctl enable php8.2-fpm
```

### 4. Verify PHP-FPM Socket Path

The nginx config uses: `unix:/var/run/php/php8.2-fpm.sock`

Check if this socket exists:
```bash
ls -la /var/run/php/
```

If your PHP version is different (8.1, 8.0, 7.4), update the nginx config:
```nginx
fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;  # Change version as needed
```

Or if using TCP:
```nginx
fastcgi_pass 127.0.0.1:9000;
```

### 5. Check File Permissions

```bash
# Navigate to your document root
cd /var/www/pdftimesaver.desktopmasters.com/public_html
# OR wherever your files are located

# Set correct ownership
sudo chown -R www-data:www-data .

# Set correct permissions
sudo find . -type d -exec chmod 755 {} \;
sudo find . -type f -exec chmod 644 {} \;

# Make writable directories
sudo chmod 775 data/ logs/ uploads/ output/ tmp/
sudo chown -R www-data:www-data data/ logs/ uploads/ output/ tmp/
```

### 6. Check Error Logs

```bash
# Nginx error log
sudo tail -50 /var/log/nginx/error.log
sudo tail -50 /var/log/nginx/pdftimesaver-error.log

# PHP-FPM error log
sudo tail -50 /var/log/php8.2-fpm.log
# OR
sudo tail -50 /var/log/php-fpm.log

# PHP error log (if configured)
sudo tail -50 /var/log/php-fpm-errors.log
```

### 7. Verify Document Root Path

The nginx config uses:
```
root /var/www/pdftimesaver.desktopmasters.com/public_html;
```

Make sure this path exists and contains your files:
```bash
ls -la /var/www/pdftimesaver.desktopmasters.com/public_html/
```

If your files are in a different location, update the `root` directive in the nginx config.

### 8. Check SSL Certificates

If using HTTPS, verify SSL certificates exist:
```bash
# Check certificate paths in nginx config
sudo nginx -T | grep ssl_certificate

# Verify certificates exist
ls -la /etc/ssl/certs/pdftimesaver.desktopmasters.com.crt
ls -la /etc/ssl/private/pdftimesaver.desktopmasters.com.key

# Or if using Let's Encrypt:
ls -la /etc/letsencrypt/live/pdftimesaver.desktopmasters.com/
```

### 9. Test PHP Processing

Create a test PHP file:
```bash
echo "<?php phpinfo(); ?>" > /var/www/pdftimesaver.desktopmasters.com/public_html/test.php
```

Then visit: `https://pdftimesaver.desktopmasters.com/test.php`

If this works, PHP is processing correctly. **Delete the test file after testing:**
```bash
rm /var/www/pdftimesaver.desktopmasters.com/public_html/test.php
```

### 10. Run Diagnostic Script

Use the provided diagnostic script:
```bash
cd /path/to/Web-PDFTimeSaver
bash scripts/diagnose-500-error.sh
```

This will check all common issues automatically.

## Common 500 Error Causes

1. **PHP-FPM not running** → Start PHP-FPM service
2. **Wrong PHP-FPM socket path** → Update nginx config
3. **File permission issues** → Fix ownership and permissions
4. **Missing PHP extensions** → Install required extensions
5. **Incorrect document root** → Update nginx config
6. **PHP syntax errors** → Check PHP error logs
7. **Missing Composer dependencies** → Run `composer install`
8. **Domain name mismatch** → ✅ **FIXED** - was "dektopmasters", now "desktopmasters"

## Quick Fix Checklist

- [ ] Updated nginx config on server with fixed domain name
- [ ] Tested nginx config: `sudo nginx -t`
- [ ] Reloaded nginx: `sudo systemctl reload nginx`
- [ ] PHP-FPM is running: `sudo systemctl status php8.2-fpm`
- [ ] PHP-FPM socket path matches nginx config
- [ ] File permissions are correct (www-data:www-data)
- [ ] Document root path exists and is correct
- [ ] SSL certificates exist (if using HTTPS)
- [ ] Checked error logs for specific errors
- [ ] Tested PHP processing with test.php

## Still Getting 500 Error?

1. **Check the specific error in logs**:
   ```bash
   sudo tail -100 /var/log/nginx/error.log
   sudo tail -100 /var/log/php8.2-fpm.log
   ```

2. **Enable PHP error display temporarily** (for debugging only):
   Edit `/etc/php/8.2/fpm/php.ini`:
   ```ini
   display_errors = On
   display_startup_errors = On
   ```
   Then restart PHP-FPM: `sudo systemctl restart php8.2-fpm`
   
   **Remember to disable this in production!**

3. **Test with curl**:
   ```bash
   curl -I https://pdftimesaver.desktopmasters.com
   curl https://pdftimesaver.desktopmasters.com
   ```

4. **Check if it's a specific route**:
   - Homepage: `https://pdftimesaver.desktopmasters.com`
   - MVP: `https://pdftimesaver.desktopmasters.com/mvp/`
   - API: `https://pdftimesaver.desktopmasters.com/api/`

## Contact

If the issue persists after following these steps, provide:
- Output of: `sudo tail -100 /var/log/nginx/error.log`
- Output of: `sudo tail -100 /var/log/php8.2-fpm.log`
- Output of: `php -v`
- Output of: `sudo nginx -T | grep -A 20 "server_name pdftimesaver.desktopmasters.com"`
- Output of: `sudo systemctl status php8.2-fpm`
- Output of: `ls -la /var/run/php/`

