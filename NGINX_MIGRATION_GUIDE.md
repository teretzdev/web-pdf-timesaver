# Nginx Migration Guide

## Overview
This guide covers migrating from XAMPP to nginx + PHP-FPM for the Web-PDFTimeSaver application.

## Prerequisites
- Windows 10/11
- Administrator privileges
- Current XAMPP installation (for reference)

## Installation Steps

### 1. Download Required Software

#### Nginx for Windows
- Download from: https://nginx.org/en/download.html
- Choose the latest stable version
- Extract to `C:\nginx\`

#### PHP 8.x for Windows
- Download from: https://windows.php.net/download/
- Choose Thread Safe build with PHP-FPM
- Extract to `C:\php\`

### 2. Configuration Files

The following configuration files have been created:

#### `nginx/nginx.conf`
- Main nginx configuration
- Document root: `C:\Users\Shadow\Web-PDFTimeSaver`
- PHP processing via FastCGI on port 9000
- Static file handling
- URL rewriting for MVP application

#### `nginx/php-fpm.conf`
- PHP-FPM pool configuration
- Listens on 127.0.0.1:9000
- Dynamic process management
- Error logging
- Memory and execution time limits for PDF generation

### 3. Startup Scripts

#### `start-nginx-php.bat`
- Starts PHP-FPM first
- Waits for PHP-FPM to initialize
- Starts nginx
- Provides status feedback

#### `stop-nginx-php.bat`
- Gracefully stops nginx
- Terminates PHP-FPM processes
- Cleans up resources

## Migration Process

### Step 1: Stop XAMPP Services
```batch
# Stop Apache and MySQL in XAMPP Control Panel
# Or use command line:
net stop Apache2.4
net stop mysql
```

### Step 2: Install nginx and PHP
1. Extract nginx to `C:\nginx\`
2. Extract PHP to `C:\php\`
3. Copy configuration files to respective directories

### Step 3: Configure PHP Extensions
Copy required PHP extensions to `C:\php\ext\`:
- php_pdo.dll
- php_gd.dll
- php_mbstring.dll
- php_curl.dll
- php_openssl.dll

### Step 4: Update PHP Configuration
Edit `C:\php\php.ini`:
```ini
extension_dir = "C:\php\ext"
extension=pdo
extension=gd
extension=mbstring
extension=curl
extension=openssl

memory_limit = 512M
max_execution_time = 300
upload_max_filesize = 50M
post_max_size = 50M
max_input_vars = 3000

date.timezone = "America/Los_Angeles"
```

### Step 5: Test Configuration
```batch
# Test PHP-FPM configuration
C:\php\php-fpm.exe -t

# Test nginx configuration
C:\nginx\nginx.exe -t
```

### Step 6: Start Services
```batch
# Use the provided startup script
start-nginx-php.bat
```

## Verification

### Check Services
```batch
# Check if nginx is running
tasklist | findstr nginx

# Check if PHP-FPM is running
tasklist | findstr php-fpm

# Check ports
netstat -an | findstr :80
netstat -an | findstr :9000
```

### Test Application
1. Open browser to `http://localhost`
2. Verify MVP dashboard loads
3. Test PDF generation
4. Check error logs if issues occur

## Troubleshooting

### Common Issues

#### Port 80 Already in Use
```batch
# Find process using port 80
netstat -ano | findstr :80

# Kill the process (replace PID)
taskkill /PID <PID> /F
```

#### PHP-FPM Won't Start
- Check `C:\php\php-fpm.conf` syntax
- Verify PHP extensions are available
- Check error logs in `C:\php\log\`

#### Nginx Won't Start
- Check `C:\nginx\nginx.conf` syntax
- Verify document root exists
- Check error logs in `C:\nginx\logs\`

#### PHP Files Not Processing
- Verify FastCGI configuration
- Check PHP-FPM is running on port 9000
- Verify file permissions

### Log Files
- Nginx access: `C:\nginx\logs\access.log`
- Nginx error: `C:\nginx\logs\error.log`
- PHP-FPM: `C:\php\log\php-fpm.log`
- PHP errors: `C:\php\log\php_errors.log`

## Performance Optimization

### Nginx Optimizations
```nginx
# Enable gzip compression
gzip on;
gzip_types text/plain text/css application/json application/javascript;

# Set cache headers for static files
location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg)$ {
    expires 1y;
    add_header Cache-Control "public, immutable";
}
```

### PHP-FPM Optimizations
```ini
; Adjust based on server resources
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 1000
```

## Security Considerations

### Nginx Security Headers
```nginx
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header X-Content-Type-Options "nosniff" always;
```

### File Access Restrictions
```nginx
# Deny access to sensitive files
location ~ /\. {
    deny all;
}

location ~ \.(log|conf|json)$ {
    deny all;
}
```

## Rollback Plan

If issues occur, you can rollback to XAMPP:

1. Stop nginx and PHP-FPM
2. Start XAMPP services
3. Verify application works
4. Investigate and fix nginx configuration
5. Retry migration

## Maintenance

### Regular Tasks
- Monitor log files for errors
- Update nginx and PHP versions
- Backup configuration files
- Monitor resource usage

### Updates
- Download new nginx version
- Extract to new directory
- Copy configuration files
- Test before switching

## Support

For issues specific to this migration:
1. Check log files first
2. Verify configuration syntax
3. Test individual components
4. Check Windows Event Viewer
5. Consult nginx and PHP documentation

## Success Criteria

Migration is successful when:
- ✅ Nginx serves PHP application correctly
- ✅ MVP dashboard loads without errors
- ✅ PDF generation works
- ✅ All existing functionality preserved
- ✅ Performance is equal or better than XAMPP
- ✅ Error logging is working
- ✅ Services start/stop reliably
