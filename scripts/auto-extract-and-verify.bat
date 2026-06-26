@echo off
REM Automatic Extraction and Verification
REM Usage: auto-extract-and-verify.bat [template_id] [pdf_path]

setlocal enabledelayedexpansion

set PHP_BIN=php
if exist "C:\xampp\php\php.exe" set PHP_BIN=C:\xampp\php\php.exe

set TEMPLATE_ID=%~1
if "%TEMPLATE_ID%"=="" set TEMPLATE_ID=t_fl100_gc120

set PDF_PATH=%~2
if "%PDF_PATH%"=="" set PDF_PATH=uploads\fl100.pdf

echo ============================================
echo AUTOMATIC EXTRACTION AND VERIFICATION
echo ============================================
echo.
echo Template: %TEMPLATE_ID%
echo PDF: %PDF_PATH%
echo.

cd /d "%~dp0.."

if not exist "%PDF_PATH%" (
    echo ERROR: PDF file not found: %PDF_PATH%
    exit /b 1
)

echo Step 1: Extracting fields...
"%PHP_BIN%" -r "require 'vendor/autoload.php'; require 'mvp/lib/pdf_field_extractor.php'; require 'mvp/lib/automated_verification_pipeline.php'; require 'mvp/lib/field_position_loader.php'; require 'mvp/lib/fl100_test_data_generator.php'; require 'mvp/lib/field_name_mapper.php'; require 'mvp/lib/pdf_form_filler.php'; require 'mvp/lib/position_debug_generator.php'; \$extractor = new \WebPdfTimeSaver\Mvp\PdfFieldExtractor(); \$result = \$extractor->extractAndGenerateBackgrounds('%PDF_PATH%', '%TEMPLATE_ID%', 'uploads', true); echo 'Extraction completed: ' . count(\$result['fields']) . ' fields' . PHP_EOL; if (isset(\$result['verification'])) { echo 'Verification: ' . \$result['verification']['overall_status'] . PHP_EOL; }"

echo.
echo Step 2: Running full verification...
"%PHP_BIN%" mvp\verify-pdf.php %TEMPLATE_ID%

echo.
echo ============================================
echo EXTRACTION AND VERIFICATION COMPLETE
echo ============================================

endlocal

