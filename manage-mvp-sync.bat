@echo off
REM MVP Sync Management Script
REM Use this to manage the automated sync task

echo MVP Sync Management
echo ==================
echo.
echo 1. Check sync status
echo 2. Run sync now
echo 3. Stop sync task
echo 4. Start sync task
echo 5. View sync logs
echo.

set /p choice="Enter your choice (1-5): "

if "%choice%"=="1" (
    echo Checking sync task status...
    schtasks /query /tn "MVP-Sync" /fo list
) else if "%choice%"=="2" (
    echo Running sync now...
    call "C:\Users\Shadow\Web-PDFTimeSaver\sync-mvp-to-xampp.bat"
) else if "%choice%"=="3" (
    echo Stopping sync task...
    schtasks /end /tn "MVP-Sync"
) else if "%choice%"=="4" (
    echo Starting sync task...
    schtasks /run /tn "MVP-Sync"
) else if "%choice%"=="5" (
    echo Viewing sync logs...
    type "C:\Users\Shadow\Web-PDFTimeSaver\logs\mvp-sync.log"
) else (
    echo Invalid choice!
)

pause
