@echo off
echo Starting Nginx + PHP-FPM for Web-PDFTimeSaver...

REM Create necessary directories
if not exist "nginx\logs" mkdir nginx\logs
if not exist "nginx\run" mkdir nginx\run
if not exist "tmp" mkdir tmp

REM Change to nginx directory
cd /d "%~dp0nginx"

REM Start PHP-FPM
echo Starting PHP-FPM...
start /b php-fpm.exe -c php.ini -y php-fpm.conf

REM Wait a moment for PHP-FPM to start
timeout /t 2 /nobreak >nul

REM Start Nginx
echo Starting Nginx...
nginx.exe -c nginx.conf

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ✓ Nginx + PHP-FPM started successfully!
    echo ✓ Application available at: http://localhost
    echo ✓ MVP Dashboard: http://localhost/mvp/
    echo.
    echo Press any key to stop services...
    pause >nul
    call stop-nginx-php.bat
) else (
    echo.
    echo ✗ Failed to start Nginx
    echo Check nginx\logs\error.log for details
    pause
)
