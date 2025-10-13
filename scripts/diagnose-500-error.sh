#!/bin/bash
# Diagnostic script for Web-PDFTimeSaver 500 errors
# Run this on your production server to identify the issue

echo "======================================"
echo "Web-PDFTimeSaver Diagnostic Script"
echo "======================================"
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if running as root or with sudo
if [ "$EUID" -ne 0 ]; then 
    echo -e "${YELLOW}Warning: Not running as root. Some checks may fail.${NC}"
    echo "Consider running: sudo bash diagnose-500-error.sh"
    echo ""
fi

echo "1. Checking PHP-FPM Status..."
echo "================================"
if systemctl is-active --quiet php8.2-fpm; then
    echo -e "${GREEN}✓ PHP 8.2-FPM is running${NC}"
elif systemctl is-active --quiet php8.1-fpm; then
    echo -e "${GREEN}✓ PHP 8.1-FPM is running${NC}"
elif systemctl is-active --quiet php8.0-fpm; then
    echo -e "${GREEN}✓ PHP 8.0-FPM is running${NC}"
elif systemctl is-active --quiet php7.4-fpm; then
    echo -e "${GREEN}✓ PHP 7.4-FPM is running${NC}"
elif systemctl is-active --quiet php-fpm; then
    echo -e "${GREEN}✓ PHP-FPM is running${NC}"
else
    echo -e "${RED}✗ PHP-FPM is NOT running!${NC}"
    echo "  Fix: sudo systemctl start php8.2-fpm"
fi
echo ""

echo "2. Checking Nginx Status..."
echo "================================"
if systemctl is-active --quiet nginx; then
    echo -e "${GREEN}✓ Nginx is running${NC}"
else
    echo -e "${RED}✗ Nginx is NOT running!${NC}"
    echo "  Fix: sudo systemctl start nginx"
fi
echo ""

echo "3. Checking PHP Version..."
echo "================================"
php -v | head -1
echo ""

echo "4. Locating PHP-FPM Socket/Port..."
echo "================================"
if [ -S /var/run/php/php8.2-fpm.sock ]; then
    echo -e "${GREEN}✓ Found: /var/run/php/php8.2-fpm.sock${NC}"
elif [ -S /var/run/php/php8.1-fpm.sock ]; then
    echo -e "${GREEN}✓ Found: /var/run/php/php8.1-fpm.sock${NC}"
elif [ -S /var/run/php/php8.0-fpm.sock ]; then
    echo -e "${GREEN}✓ Found: /var/run/php/php8.0-fpm.sock${NC}"
elif [ -S /var/run/php/php7.4-fpm.sock ]; then
    echo -e "${GREEN}✓ Found: /var/run/php/php7.4-fpm.sock${NC}"
else
    echo -e "${YELLOW}⚠ No PHP-FPM socket found in /var/run/php/${NC}"
    echo "  Checking for TCP port..."
    netstat -tlnp 2>/dev/null | grep 9000 && echo -e "${GREEN}✓ PHP-FPM listening on port 9000${NC}" || echo -e "${RED}✗ PHP-FPM not found on port 9000${NC}"
fi
echo ""

echo "5. Checking Document Root..."
echo "================================"
DOC_ROOT=$(nginx -T 2>/dev/null | grep "root.*pdftimesaver" | head -1 | awk '{print $2}' | tr -d ';')
if [ -n "$DOC_ROOT" ]; then
    echo "Document root: $DOC_ROOT"
    if [ -d "$DOC_ROOT" ]; then
        echo -e "${GREEN}✓ Document root exists${NC}"
        if [ -f "$DOC_ROOT/index.php" ]; then
            echo -e "${GREEN}✓ index.php exists${NC}"
        else
            echo -e "${RED}✗ index.php NOT found in document root${NC}"
        fi
    else
        echo -e "${RED}✗ Document root does NOT exist!${NC}"
    fi
else
    echo -e "${YELLOW}⚠ Could not determine document root from Nginx config${NC}"
fi
echo ""

echo "6. Checking File Permissions..."
echo "================================"
if [ -n "$DOC_ROOT" ] && [ -d "$DOC_ROOT" ]; then
    echo "Checking: $DOC_ROOT"
    ls -ld "$DOC_ROOT" 2>/dev/null
    
    echo ""
    echo "Checking writable directories:"
    for dir in data logs uploads output tmp; do
        if [ -d "$DOC_ROOT/$dir" ]; then
            PERMS=$(stat -c "%a" "$DOC_ROOT/$dir" 2>/dev/null)
            OWNER=$(stat -c "%U:%G" "$DOC_ROOT/$dir" 2>/dev/null)
            if [ "$PERMS" -ge 775 ]; then
                echo -e "  ${GREEN}✓ $dir/ ($PERMS $OWNER)${NC}"
            else
                echo -e "  ${YELLOW}⚠ $dir/ ($PERMS $OWNER) - Should be 775${NC}"
            fi
        else
            echo -e "  ${RED}✗ $dir/ - Directory missing!${NC}"
        fi
    done
else
    echo -e "${YELLOW}⚠ Cannot check permissions (document root unknown)${NC}"
fi
echo ""

echo "7. Checking Required PHP Extensions..."
echo "================================"
for ext in gd mbstring curl json fileinfo; do
    if php -m 2>/dev/null | grep -q "^$ext$"; then
        echo -e "${GREEN}✓ $ext${NC}"
    else
        echo -e "${RED}✗ $ext - NOT installed!${NC}"
    fi
done
echo ""

echo "8. Recent Nginx Error Log (last 10 lines)..."
echo "================================"
if [ -f /var/log/nginx/error.log ]; then
    tail -10 /var/log/nginx/error.log
else
    echo -e "${YELLOW}⚠ Cannot access /var/log/nginx/error.log${NC}"
fi
echo ""

echo "9. Recent PHP-FPM Error Log (last 10 lines)..."
echo "================================"
PHP_LOG=""
for log in /var/log/php8.2-fpm.log /var/log/php8.1-fpm.log /var/log/php-fpm/error.log /var/log/php-fpm.log; do
    if [ -f "$log" ]; then
        PHP_LOG="$log"
        break
    fi
done

if [ -n "$PHP_LOG" ]; then
    tail -10 "$PHP_LOG"
else
    echo -e "${YELLOW}⚠ Cannot find PHP-FPM error log${NC}"
fi
echo ""

echo "10. Nginx Configuration Test..."
echo "================================"
if nginx -t 2>&1; then
    echo -e "${GREEN}✓ Nginx configuration is valid${NC}"
else
    echo -e "${RED}✗ Nginx configuration has errors!${NC}"
fi
echo ""

echo "11. Checking Ports..."
echo "================================"
echo "Port 80 (HTTP):"
netstat -tlnp 2>/dev/null | grep :80 || echo "  Not listening"
echo "Port 443 (HTTPS):"
netstat -tlnp 2>/dev/null | grep :443 || echo "  Not listening"
echo ""

echo "12. Composer Dependencies..."
echo "================================"
if [ -n "$DOC_ROOT" ] && [ -f "$DOC_ROOT/composer.json" ]; then
    if [ -d "$DOC_ROOT/vendor" ]; then
        echo -e "${GREEN}✓ vendor/ directory exists${NC}"
    else
        echo -e "${RED}✗ vendor/ directory missing!${NC}"
        echo "  Fix: cd $DOC_ROOT && composer install"
    fi
else
    echo -e "${YELLOW}⚠ Cannot check (composer.json not found)${NC}"
fi
echo ""

echo "======================================"
echo "Diagnostic Summary"
echo "======================================"
echo ""
echo "Most common 500 error causes:"
echo "1. PHP-FPM not running → sudo systemctl start php8.2-fpm"
echo "2. Wrong PHP-FPM socket in Nginx config"
echo "3. File permission issues → sudo chown -R www-data:www-data $DOC_ROOT"
echo "4. Missing PHP extensions → sudo apt install php8.2-{gd,mbstring,curl,xml}"
echo "5. Missing composer dependencies → composer install"
echo ""
echo "Next steps:"
echo "1. Review the error logs above for specific error messages"
echo "2. Fix any issues marked with ✗ or ⚠"
echo "3. Run: sudo systemctl restart php8.2-fpm nginx"
echo "4. Check https://pdftimesaver.dektopmasters.com again"
echo ""
echo "For detailed instructions, see PRODUCTION_DEPLOYMENT.md"
echo ""


