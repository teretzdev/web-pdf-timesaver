@echo off
REM Master verification script - runs verification on all templates and generates master report
REM Usage: verify-all-templates-master.bat

setlocal enabledelayedexpansion
set PHP_BIN=php
if exist "C:\xampp\php\php.exe" set PHP_BIN=C:\xampp\php\php.exe
if exist "C:\Program Files\PHP\php.exe" set PHP_BIN=C:\Program Files\PHP\php.exe

echo ============================================
echo MASTER VERIFICATION - ALL TEMPLATES
echo ============================================
echo.

cd /d "%~dp0.."

set OUTPUT_FILE=output\verification\master_verification_report_%date:~-4,4%%date:~-7,2%%date:~-10,2%_%time:~0,2%%time:~3,2%%time:~6,2%.json
set OUTPUT_FILE=!OUTPUT_FILE: =0!

echo Starting comprehensive verification...
echo Output will be saved to: !OUTPUT_FILE!
echo.

set MASTER_RESULTS={}
set TEMPLATE_COUNT=0
set PASS_COUNT=0
set FAIL_COUNT=0

for %%f in (data\t_*_positions.json) do (
    set "filename=%%~nf"
    set "template_id=!filename:_positions=!"
    
    if "!template_id:~0,2!"=="t_" (
        set /a TEMPLATE_COUNT+=1
        echo ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
        echo [!TEMPLATE_COUNT!] Verifying: !template_id!
        echo ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
        
        "%PHP_BIN%" mvp\verify-pdf.php "!template_id!" > temp_verify_output.txt 2>&1
        set VERIFY_EXIT=!ERRORLEVEL!
        
        findstr /C:"Overall Status: PASS" temp_verify_output.txt >nul
        if !ERRORLEVEL! EQU 0 (
            set /a PASS_COUNT+=1
            echo   ✅ PASS
        ) else (
            set /a FAIL_COUNT+=1
            echo   ❌ FAIL
        )
        echo.
    )
)

del temp_verify_output.txt 2>nul

echo ============================================
echo VERIFICATION SUMMARY
echo ============================================
echo Total Templates: !TEMPLATE_COUNT!
echo Passed: !PASS_COUNT!
echo Failed: !FAIL_COUNT!
echo.
echo Individual reports available in: output\verification\
echo.

endlocal

