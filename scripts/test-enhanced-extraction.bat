@echo off
REM Test enhanced PDF field extraction with new methods
echo Testing Enhanced PDF Field Extraction
echo ======================================
echo.

set NODE_PATH=C:\Program Files\nodejs\node.exe
if not exist "%NODE_PATH%" (
    echo ERROR: Node.js not found at %NODE_PATH%
    echo Please install Node.js or update NODE_PATH in this script
    pause
    exit /b 1
)

set PDF_PATH=uploads\fl100.pdf
set TEMPLATE_ID=t_fl100_gc120

if not exist "%PDF_PATH%" (
    echo ERROR: PDF file not found at %PDF_PATH%
    pause
    exit /b 1
)

echo PDF: %PDF_PATH%
echo Template: %TEMPLATE_ID%
echo.

echo Running enhanced extraction...
"%NODE_PATH%" scripts\universal-field-extractor.js "%PDF_PATH%" "%TEMPLATE_ID%"

echo.
echo ========================================
echo Extraction complete!
echo ========================================
echo.
echo Check data\%TEMPLATE_ID%_positions.json for results
pause

