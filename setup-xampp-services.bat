@echo off
echo Setting up XAMPP services for Windows startup...
echo.

REM Check if running as administrator
net session >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: This script must be run as Administrator
    echo Right-click on this file and select "Run as administrator"
    pause
    exit /b 1
)

REM Check if XAMPP is installed
if not exist "C:\xampp" (
    echo ERROR: XAMPP not found at C:\xampp
    echo Please install XAMPP first from https://www.apachefriends.org/
    pause
    exit /b 1
)

echo XAMPP found at C:\xampp

REM Stop any running XAMPP services first
echo.
echo Stopping existing XAMPP services...
net stop "Apache2.4" >nul 2>&1
net stop "mysql" >nul 2>&1

REM Install Apache as Windows service
echo.
echo Installing Apache as Windows service...
cd /d "C:\xampp\apache\bin"
httpd.exe -k install -n "Apache2.4"
if %errorlevel% neq 0 (
    echo Warning: Apache service installation may have failed
)

REM Install MySQL as Windows service
echo.
echo Installing MySQL as Windows service...
cd /d "C:\xampp\mysql\bin"
mysqld.exe --install mysql --defaults-file="C:\xampp\mysql\bin\my.ini"
if %errorlevel% neq 0 (
    echo Warning: MySQL service installation may have failed
)

REM Set services to start automatically
echo.
echo Configuring services to start automatically at Windows startup...
sc config "Apache2.4" start= auto
sc config "mysql" start= auto

REM Start the services
echo.
echo Starting services...
net start "Apache2.4"
if %errorlevel% neq 0 (
    echo Warning: Failed to start Apache service
) else (
    echo Apache service started successfully
)

net start "mysql"
if %errorlevel% neq 0 (
    echo Warning: Failed to start MySQL service
) else (
    echo MySQL service started successfully
)

REM Copy project to XAMPP htdocs if not already there
if not exist "C:\xampp\htdocs\Web-PDFTimeSaver" (
    echo.
    echo Copying project to XAMPP htdocs...
    xcopy "%~dp0*" "C:\xampp\htdocs\Web-PDFTimeSaver\" /E /I /Y /Q
    echo Project copied to C:\xampp\htdocs\Web-PDFTimeSaver\
)

REM Install composer dependencies
echo.
echo Installing PHP dependencies...
cd /d "C:\xampp\htdocs\Web-PDFTimeSaver"
C:\xampp\php\php.exe composer.phar install

echo.
echo ========================================
echo XAMPP Services Setup Complete!
echo ========================================
echo.
echo Services configured:
echo   - Apache2.4 (auto-start enabled)
echo   - MySQL (auto-start enabled)
echo.
echo Your application is now accessible at:
echo   http://localhost/Web-PDFTimeSaver/
echo   http://localhost/Web-PDFTimeSaver/
echo.
echo Services will automatically start when Windows boots.
echo.
echo To manage services manually:
echo   - Services.msc (Windows Services Manager)
echo   - C:\xampp\xampp-control.exe (XAMPP Control Panel)
echo.
pause
