@echo off
REM Automated sync script to keep XAMPP mvp directory in sync with workspace
REM This script runs every 1 minute via Windows Task Scheduler

REM Sync workspace mvp to XAMPP location (completely silent)
robocopy "C:\Users\Shadow\Web-PDFTimeSaver\mvp" "C:\xampp\htdocs\Web-PDFTimeSaver\mvp" /E /R:1 /W:1 /NFL /NDL /NJH /NJS

REM Log to file for monitoring (only on errors)
if %ERRORLEVEL% GTR 7 (
    echo [%date% %time%] Sync error (Exit code: %ERRORLEVEL%) >> "C:\Users\Shadow\Web-PDFTimeSaver\logs\mvp-sync.log"
)
