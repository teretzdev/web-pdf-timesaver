@echo off
REM Test browser upload flow with ensemble extraction
echo Testing Browser Upload Flow with Ensemble Extraction
echo ====================================================
echo.

REM Try to find PHP
set PHP_PATH=
if exist "C:\xampp\php\php.exe" set PHP_PATH=C:\xampp\php\php.exe
if exist "C:\Program Files\PHP\php.exe" set PHP_PATH=C:\Program Files\PHP\php.exe
if exist "C:\php\php.exe" set PHP_PATH=C:\php\php.exe

REM Check if PHP found
if "%PHP_PATH%"=="" (
    echo ERROR: PHP not found in common locations
    echo Please set PHP_PATH environment variable or update this script
    echo.
    echo Trying to use 'php' from PATH...
    php test-browser-flow.php
) else (
    echo Using PHP at: %PHP_PATH%
    echo.
    "%PHP_PATH%" test-browser-flow.php
)

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ========================================
    echo Test completed successfully!
    echo ========================================
    echo.
    echo Check browser-flow-test-output.txt for full results
) else (
    echo.
    echo ========================================
    echo Test failed with error code: %ERRORLEVEL%
    echo ========================================
)

pause

