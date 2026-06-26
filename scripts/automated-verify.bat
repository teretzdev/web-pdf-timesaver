@echo off
REM Automated PDF Verification Script for Windows
REM Usage: automated-verify.bat t_fl100_gc120

set TEMPLATE_ID=%1
if "%TEMPLATE_ID%"=="" set TEMPLATE_ID=t_fl100_gc120

set PHP_BIN=php
if exist "C:\xampp\php\php.exe" set PHP_BIN=C:\xampp\php\php.exe
if exist "C:\Program Files\PHP\php.exe" set PHP_BIN=C:\Program Files\PHP\php.exe

echo === AUTOMATED PDF VERIFICATION ===
echo Template: %TEMPLATE_ID%
echo.

cd /d "%~dp0.."
"%PHP_BIN%" mvp\verify-pdf.php %TEMPLATE_ID%

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ✅ VERIFICATION PASSED
) else (
    echo.
    echo ❌ VERIFICATION FAILED
)

exit /b %ERRORLEVEL%

