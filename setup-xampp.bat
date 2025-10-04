@echo off
echo Setting up Web-PDFTimeSaver for XAMPP...
echo.

REM Check if XAMPP is installed
if not exist "C:\xampp" (
    echo ERROR: XAMPP not found at C:\xampp
    echo Please install XAMPP first from https://www.apachefriends.org/
    pause
    exit /b 1
)

echo XAMPP found at C:\xampp

REM Check if PHP is available
if not exist "C:\xampp\php\php.exe" (
    echo ERROR: PHP not found in XAMPP installation
    pause
    exit /b 1
)

echo PHP found: 
C:\xampp\php\php.exe -v

REM Install composer dependencies
echo.
echo Installing PHP dependencies...
C:\xampp\php\php.exe composer.phar install

REM Check if Apache is running
echo.
echo Checking Apache status...
sc query "Apache2.4" >nul 2>&1
if %errorlevel% neq 0 (
    echo Apache service not found. Starting XAMPP Control Panel...
    start "" "C:\xampp\xampp-control.exe"
    echo.
    echo Please start Apache and MySQL from the XAMPP Control Panel
    echo Then access your application at: http://localhost/Web-PDFTimeSaver/
    pause
    exit /b 0
)

REM Check if Apache is running
sc query "Apache2.4" | find "RUNNING" >nul
if %errorlevel% neq 0 (
    echo Apache is not running. Starting Apache...
    net start "Apache2.4"
    if %errorlevel% neq 0 (
        echo Failed to start Apache. Please start it manually from XAMPP Control Panel
        start "" "C:\xampp\xampp-control.exe"
        pause
        exit /b 1
    )
)

echo Apache is running!

REM Check if MySQL is running
sc query "mysql" | find "RUNNING" >nul
if %errorlevel% neq 0 (
    echo MySQL is not running. Starting MySQL...
    net start "mysql"
    if %errorlevel% neq 0 (
        echo Failed to start MySQL. Please start it manually from XAMPP Control Panel
    )
) else (
    echo MySQL is running!
)

echo.
echo Setup complete!
echo.
echo Your application is now accessible at:
echo   http://localhost/Web-PDFTimeSaver/
echo   http://localhost/Web-PDFTimeSaver/mvp/
echo.
echo The project has been copied to C:\xampp\htdocs\Web-PDFTimeSaver\
echo All PHP dependencies are installed and configured.
echo.
echo If you need to start XAMPP services manually, run:
echo   C:\xampp\xampp-control.exe
echo.
echo SUCCESS: Web-PDFTimeSaver is ready to use!
pause
