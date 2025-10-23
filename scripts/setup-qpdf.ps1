# Setup qpdf for PDF decryption
# Downloads and configures qpdf portable for Windows

$ErrorActionPreference = "Stop"

# Configuration
$QPDF_VERSION = "12.3.0"
$QPDF_URL = "https://github.com/qpdf/qpdf/releases/download/v${QPDF_VERSION}/qpdf-${QPDF_VERSION}-x64.msi"
$PROJECT_ROOT = Split-Path -Parent $PSScriptRoot
$BIN_DIR = Join-Path $PROJECT_ROOT "bin"
$QPDF_ZIP = Join-Path $BIN_DIR "qpdf.zip"
$QPDF_EXTRACT = Join-Path $BIN_DIR "qpdf-extract"
$QPDF_FINAL = Join-Path $BIN_DIR "qpdf"

Write-Host "================================================" -ForegroundColor Cyan
Write-Host "  qpdf Setup Script for Windows" -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan
Write-Host ""

# Create bin directory
if (-not (Test-Path $BIN_DIR)) {
    Write-Host "Creating bin directory..." -ForegroundColor Yellow
    New-Item -ItemType Directory -Path $BIN_DIR | Out-Null
}

# Check if qpdf already exists
$qpdfExe = Join-Path $QPDF_FINAL "bin\qpdf.exe"
if (Test-Path $qpdfExe) {
    Write-Host "qpdf is already installed!" -ForegroundColor Green
    Write-Host "Location: $qpdfExe" -ForegroundColor Gray
    
    # Test it
    $version = & $qpdfExe --version 2>&1 | Select-Object -First 1
    Write-Host "Version: $version" -ForegroundColor Green
    Write-Host ""
    Write-Host "To reinstall, delete the folder: $QPDF_FINAL" -ForegroundColor Gray
    exit 0
}

Write-Host "Downloading qpdf v${QPDF_VERSION}..." -ForegroundColor Yellow
Write-Host "URL: $QPDF_URL" -ForegroundColor Gray

try {
    # Download qpdf
    Invoke-WebRequest -Uri $QPDF_URL -OutFile $QPDF_ZIP -UseBasicParsing
    Write-Host "Download complete!" -ForegroundColor Green
    
    # Extract
    Write-Host "Extracting qpdf..." -ForegroundColor Yellow
    Expand-Archive -Path $QPDF_ZIP -DestinationPath $QPDF_EXTRACT -Force
    
    # Find the extracted folder (it includes version number)
    $extractedFolder = Get-ChildItem -Path $QPDF_EXTRACT -Directory | Select-Object -First 1
    
    # Move to final location
    Write-Host "Installing to bin/qpdf..." -ForegroundColor Yellow
    Move-Item -Path $extractedFolder.FullName -Destination $QPDF_FINAL -Force
    
    # Cleanup
    Write-Host "Cleaning up..." -ForegroundColor Yellow
    Remove-Item $QPDF_ZIP -Force
    Remove-Item $QPDF_EXTRACT -Force -Recurse
    
    Write-Host ""
    Write-Host "================================================" -ForegroundColor Green
    Write-Host "  qpdf installed successfully!" -ForegroundColor Green
    Write-Host "================================================" -ForegroundColor Green
    Write-Host ""
    Write-Host "Location: $qpdfExe" -ForegroundColor Gray
    
    # Test installation
    Write-Host ""
    Write-Host "Testing qpdf..." -ForegroundColor Yellow
    $version = & $qpdfExe --version 2>&1 | Select-Object -First 1
    Write-Host "Version: $version" -ForegroundColor Green
    
    Write-Host ""
    Write-Host "Next steps:" -ForegroundColor Cyan
    Write-Host "  1. Run: node scripts/extract-positions-auto.js" -ForegroundColor White
    Write-Host "  2. Test: node scripts/extract-fl105-fields-js.js uploads/w9.pdf" -ForegroundColor White
    Write-Host ""
    
} catch {
    Write-Host ""
    Write-Host "ERROR: Failed to install qpdf" -ForegroundColor Red
    Write-Host $_.Exception.Message -ForegroundColor Red
    Write-Host ""
    Write-Host "Manual installation:" -ForegroundColor Yellow
    Write-Host "  1. Download: $QPDF_URL" -ForegroundColor White
    Write-Host "  2. Extract to: $QPDF_FINAL" -ForegroundColor White
    Write-Host "  3. Make sure bin\qpdf.exe exists" -ForegroundColor White
    exit 1
}

