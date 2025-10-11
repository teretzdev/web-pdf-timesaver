@echo off
echo Stopping Nginx + PHP-FPM...

REM Change to nginx directory
cd /d "%~dp0nginx"

REM Stop Nginx
echo Stopping Nginx...
nginx.exe -s quit

REM Stop PHP-FPM
echo Stopping PHP-FPM...
taskkill /f /im php-fpm.exe >nul 2>&1

echo ✓ Services stopped
