@echo off
REM Verify all templates in the system
REM Usage: verify-all-templates.bat

set PHP_BIN=php
if exist "C:\xampp\php\php.exe" set PHP_BIN=C:\xampp\php\php.exe
if exist "C:\Program Files\PHP\php.exe" set PHP_BIN=C:\Program Files\PHP\php.exe

echo === VERIFYING ALL TEMPLATES ===
echo.

cd /d "%~dp0.."

for %%f in (data\t_*_positions.json) do (
    set "filename=%%~nf"
    set "template_id=!filename:_positions=!"
    if "!template_id:~0,2!"=="t_" (
        echo ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
        echo Verifying: !template_id!
        echo ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
        "%PHP_BIN%" mvp\verify-pdf.php "!template_id!" 2>&1 | findstr /C:"Overall Status" /C:"Total Tests" /C:"Passed" /C:"Failed" /C:"PASS" /C:"FAIL"
        echo.
    )
)

echo === VERIFICATION COMPLETE ===

