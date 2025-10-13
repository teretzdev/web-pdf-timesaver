# Production Deployment Guide for pdftimesaver.dektopmasters.com

## Current Issue: 500 Internal Server Error

A 500 error on Nginx is typically caused by one of these issues:
1. PHP-FPM not running or misconfigured
2. Incorrect file permissions
3. PHP errors (check error logs)
4. Missing PHP extensions
5. Incorrect Nginx configuration

## Quick Diagnostic Steps

### 1. Check PHP-FPM Status
```bash
# Check if PHP-FPM is running
sudo systemctl status php8.2-fpm
# OR
sudo systemctl status php-fpm

# If not running, start it:
sudo systemctl start php8.2-fpm
sudo systemctl enable php8.2-fpm  # Enable on boot
```

### 2. Check Nginx Error Logs
```bash
# Check the most recent errors
sudo tail -f /var/log/nginx/error.log
sudo tail -f /var/log/nginx/pdftimesaver-error.log

# Look for errors like:
# - "connect() to unix:/var/run/php/php8.2-fpm.sock failed"
# - "File not found"
# - "Permission denied"
```

### 3. Check PHP Error Logs
```bash
sudo tail -f /var/log/php8.2-fpm.log
# OR
sudo tail -f /var/log/php-fpm.log
```

### 4. Verify File Permissions
```bash
# Navigate to your web directory
cd /var/www/pdftimesaver.dektopmasters.com/public_html
# OR wherever your files are located

# Set correct ownership (replace www-data with your web server user)
sudo chown -R www-data:www-data .

# Set correct permissions
sudo find . -type d -exec chmod 755 {} \;
sudo find . -type f -exec chmod 644 {} \;

# Make writable directories
sudo chmod 775 data/ logs/ uploads/ output/
sudo chown -R www-data:www-data data/ logs/ uploads/ output/
```

## Deployment Steps

### Step 1: Identify Your Server Configuration

Run these commands to find your setup:

```bash
# Find PHP-FPM socket or port
sudo netstat -tlnp | grep php
# OR
ls -la /var/run/php/

# Find your PHP version
php -v

# Find your document root
sudo nginx -T | grep root

# Check your web server user
ps aux | grep nginx | head -1
```

### Step 2: Update the Nginx Configuration

Edit the provided `nginx/production-pdftimesaver.conf` file and update:

1. **Document root** (line 18-20):
   ```nginx
   root /var/www/pdftimesaver.dektopmasters.com/public_html;
   ```
   Replace with your actual path.

2. **SSL certificate paths** (lines 27-28):
   ```nginx
   ssl_certificate /etc/ssl/certs/pdftimesaver.dektopmasters.com.crt;
   ssl_certificate_key /etc/ssl/private/pdftimesaver.dektopmasters.com.key;
   ```
   Replace with your actual SSL certificate paths.
   
   If using Let's Encrypt:
   ```nginx
   ssl_certificate /etc/letsencrypt/live/pdftimesaver.dektopmasters.com/fullchain.pem;
   ssl_certificate_key /etc/letsencrypt/live/pdftimesaver.dektopmasters.com/privkey.pem;
   ```

3. **PHP-FPM socket** (multiple locations in the file):
   ```nginx
   fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
   ```
   
   Common alternatives:
   - PHP 8.1: `unix:/var/run/php/php8.1-fpm.sock`
   - PHP 8.0: `unix:/var/run/php/php8.0-fpm.sock`
   - PHP 7.4: `unix:/var/run/php/php7.4-fpm.sock`
   - TCP port: `127.0.0.1:9000`

### Step 3: Install the Configuration

```bash
# Copy the configuration file to Nginx
sudo cp nginx/production-pdftimesaver.conf /etc/nginx/sites-available/pdftimesaver.dektopmasters.com

# Create symbolic link to enable the site
sudo ln -s /etc/nginx/sites-available/pdftimesaver.dektopmasters.com /etc/nginx/sites-enabled/

# Remove default site if needed
sudo rm /etc/nginx/sites-enabled/default

# Test the configuration
sudo nginx -t

# If test passes, reload Nginx
sudo systemctl reload nginx
```

### Step 4: Configure PHP Settings

Create or edit PHP-FPM pool configuration:

```bash
# Edit the www pool (or create a custom one)
sudo nano /etc/php/8.2/fpm/pool.d/www.conf
```

Ensure these settings are present:

```ini
[www]
user = www-data
group = www-data
listen = /var/run/php/php8.2-fpm.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660

pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 500

; PHP settings for PDF processing
php_admin_value[memory_limit] = 512M
php_admin_value[max_execution_time] = 300
php_admin_value[upload_max_filesize] = 50M
php_admin_value[post_max_size] = 50M
php_admin_value[max_input_vars] = 3000
```

Also edit main PHP configuration:

```bash
sudo nano /etc/php/8.2/fpm/php.ini
```

Verify these settings:

```ini
memory_limit = 512M
max_execution_time = 300
upload_max_filesize = 50M
post_max_size = 50M
max_input_vars = 3000

; Error logging
display_errors = Off
log_errors = On
error_log = /var/log/php-fpm-errors.log

; Required extensions
extension=gd
extension=pdo_mysql
extension=mbstring
extension=curl
extension=json
extension=fileinfo
```

Restart PHP-FPM:

```bash
sudo systemctl restart php8.2-fpm
```

### Step 5: Create Required Directories

```bash
# Navigate to your project root
cd /var/www/pdftimesaver.dektopmasters.com/public_html

# Create necessary directories if they don't exist
mkdir -p data logs uploads output tmp

# Set permissions
sudo chown -R www-data:www-data data logs uploads output tmp
sudo chmod 775 data logs uploads output tmp
```

### Step 6: Install PHP Dependencies

```bash
# Install Composer if not already installed
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install project dependencies
cd /var/www/pdftimesaver.dektopmasters.com/public_html
composer install --no-dev --optimize-autoloader
```

### Step 7: Test the Application

```bash
# Test a simple PHP file
echo "<?php phpinfo(); ?>" > test.php
curl https://pdftimesaver.dektopmasters.com/test.php

# Remove test file
rm test.php

# Check the main application
curl -I https://pdftimesaver.dektopmasters.com
```

## Common Issues and Solutions

### Issue: "502 Bad Gateway"
**Cause**: PHP-FPM is not running or Nginx can't connect to it.

**Solution**:
```bash
# Check PHP-FPM status
sudo systemctl status php8.2-fpm

# Check socket exists
ls -la /var/run/php/

# Verify socket path in Nginx config matches actual socket
sudo nginx -T | grep fastcgi_pass
```

### Issue: "File not found" in PHP-FPM logs
**Cause**: Document root is incorrect or file permissions issue.

**Solution**:
```bash
# Verify document root in Nginx config
sudo nginx -T | grep "root.*pdftimesaver"

# Verify index.php exists
ls -la /var/www/pdftimesaver.dektopmasters.com/public_html/index.php

# Check permissions
namei -l /var/www/pdftimesaver.dektopmasters.com/public_html/index.php
```

### Issue: "Permission denied" errors
**Cause**: Incorrect file ownership or permissions.

**Solution**:
```bash
# Reset ownership
sudo chown -R www-data:www-data /var/www/pdftimesaver.dektopmasters.com/public_html

# Reset permissions
sudo find /var/www/pdftimesaver.dektopmasters.com/public_html -type d -exec chmod 755 {} \;
sudo find /var/www/pdftimesaver.dektopmasters.com/public_html -type f -exec chmod 644 {} \;

# Special writable directories
sudo chmod 775 /var/www/pdftimesaver.dektopmasters.com/public_html/{data,logs,uploads,output}
```

### Issue: PHP extensions missing
**Cause**: Required PHP extensions not installed.

**Solution**:
```bash
# Install common extensions for Ubuntu/Debian
sudo apt-get update
sudo apt-get install -y php8.2-fpm php8.2-cli php8.2-common php8.2-mysql php8.2-mbstring php8.2-gd php8.2-curl php8.2-xml php8.2-zip php8.2-fileinfo

# Restart PHP-FPM
sudo systemctl restart php8.2-fpm
```

### Issue: SSL certificate errors
**Cause**: SSL certificate not configured or expired.

**Solution**:
```bash
# If using Let's Encrypt, install certbot
sudo apt-get install certbot python3-certbot-nginx

# Get SSL certificate
sudo certbot --nginx -d pdftimesaver.dektopmasters.com

# Test auto-renewal
sudo certbot renew --dry-run
```

## Verification Checklist

After deployment, verify these work:

- [ ] Homepage loads: `https://pdftimesaver.dektopmasters.com`
- [ ] MVP dashboard loads: `https://pdftimesaver.dektopmasters.com/mvp/?route=dashboard`
- [ ] No errors in Nginx logs: `sudo tail -f /var/log/nginx/pdftimesaver-error.log`
- [ ] No errors in PHP logs: `sudo tail -f /var/log/php8.2-fpm.log`
- [ ] SSL certificate is valid (no browser warnings)
- [ ] File uploads work
- [ ] PDF generation works
- [ ] All writable directories are accessible

## Quick Reference Commands

```bash
# Restart services
sudo systemctl restart nginx
sudo systemctl restart php8.2-fpm

# Check service status
sudo systemctl status nginx
sudo systemctl status php8.2-fpm

# View logs in real-time
sudo tail -f /var/log/nginx/pdftimesaver-error.log
sudo tail -f /var/log/php8.2-fpm.log

# Test Nginx configuration
sudo nginx -t

# Reload Nginx (no downtime)
sudo systemctl reload nginx

# Check what's using port 80/443
sudo netstat -tlnp | grep :80
sudo netstat -tlnp | grep :443
```

## Need More Help?

If you're still getting 500 errors after following this guide:

1. **Collect these logs and share them**:
   ```bash
   sudo tail -50 /var/log/nginx/error.log
   sudo tail -50 /var/log/php8.2-fpm.log
   ```

2. **Run these diagnostic commands**:
   ```bash
   php -v
   sudo nginx -T
   sudo systemctl status php8.2-fpm
   ls -la /var/run/php/
   ```

3. **Check your current configuration**:
   ```bash
   cat /etc/nginx/sites-enabled/pdftimesaver.dektopmasters.com
   ```

## Security Notes for Production

1. **Disable directory listing** - Already configured in the Nginx config
2. **Hide server version** - Already configured with `server_tokens off`
3. **Set proper file permissions** - See Step 6 above
4. **Enable SSL/TLS** - Use Let's Encrypt for free SSL
5. **Set up firewall**:
   ```bash
   sudo ufw allow 80/tcp
   sudo ufw allow 443/tcp
   sudo ufw enable
   ```
6. **Regular updates**:
   ```bash
   sudo apt-get update
   sudo apt-get upgrade
   ```
7. **Monitor logs regularly** - Set up log rotation
8. **Backup regularly** - Backup `data/`, `uploads/`, and database

## Contact

If issues persist, provide:
- Output of: `sudo tail -100 /var/log/nginx/error.log`
- Output of: `php -v`
- Output of: `sudo nginx -T | grep -A 20 "server_name pdftimesaver"`
- Screenshot of browser error (if visible)


