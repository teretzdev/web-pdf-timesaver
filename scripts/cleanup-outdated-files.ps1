# Cleanup Outdated Files Script
# This script removes outdated files while preserving functionality

Write-Host "=== Outdated Files Cleanup Script ===" -ForegroundColor Cyan
Write-Host "Backup created in: backup_20251110_131524/" -ForegroundColor Green
Write-Host ""

# Get the project root directory
$projectRoot = Split-Path -Parent $PSScriptRoot
Set-Location $projectRoot

# Statistics
$stats = @{
    SyncConflictFiles = 0
    OldTestFiles = 0
    OldOutputFiles = 0
    OldBackupFiles = 0
    TotalDeleted = 0
    TotalSize = 0
}

# Function to safely delete files
function Remove-FilesSafely {
    param(
        [string]$Pattern,
        [string]$Description,
        [bool]$DryRun = $true
    )
    
    Write-Host "`n=== $Description ===" -ForegroundColor Yellow
    $files = Get-ChildItem -Path $projectRoot -Recurse -File -Filter $Pattern -ErrorAction SilentlyContinue | 
        Where-Object { 
            $_.FullName -notlike "*\backup_*" -and 
            $_.FullName -notlike "*\node_modules\*" -and 
            $_.FullName -notlike "*\.git\*" 
        }
    
    $count = $files.Count
    $size = ($files | Measure-Object -Property Length -Sum).Sum
    
    Write-Host "Found: $count files ($([math]::Round($size / 1MB, 2)) MB)" -ForegroundColor White
    
    if ($count -eq 0) {
        Write-Host "No files to delete." -ForegroundColor Gray
        return 0, 0
    }
    
    if ($DryRun) {
        Write-Host "DRY RUN: Would delete $count files" -ForegroundColor Cyan
        if ($count -le 10) {
            $files | ForEach-Object { Write-Host "  - $($_.FullName.Replace($projectRoot, '.'))" -ForegroundColor Gray }
        } else {
            Write-Host "  (Showing first 10 files)" -ForegroundColor Gray
            $files | Select-Object -First 10 | ForEach-Object { Write-Host "  - $($_.FullName.Replace($projectRoot, '.'))" -ForegroundColor Gray }
        }
        return $count, $size
    } else {
        Write-Host "Deleting $count files..." -ForegroundColor Yellow
        $files | Remove-Item -Force -ErrorAction SilentlyContinue
        Write-Host "Deleted: $count files" -ForegroundColor Green
        return $count, $size
    }
}

# Function to remove old test files from root
function Remove-OldTestFiles {
    param([bool]$DryRun = $true)
    
    Write-Host "`n=== Old Test Files in Root ===" -ForegroundColor Yellow
    
    $testPatterns = @(
        "test-*.php",
        "generate-*.php",
        "fill-*.php",
        "download-*.php",
        "extract-*.php",
        "analyze-*.php",
        "correct-*.php",
        "fix-*.php",
        "debug-*.php",
        "demo-*.php",
        "prove-*.php",
        "verify-*.php",
        "check-*.php",
        "execute-*.php",
        "simple-*.php",
        "quick-*.php",
        "real-*.php",
        "comprehensive-*.php",
        "final-*.php"
    )
    
    $excludeFiles = @(
        "index.php",  # Keep root index.php
        "composer.json",  # Keep composer files
        "package.json"  # Keep package.json
    )
    
    $allFiles = @()
    foreach ($pattern in $testPatterns) {
        $files = Get-ChildItem -Path $projectRoot -File -Filter $pattern -ErrorAction SilentlyContinue |
            Where-Object { 
                $_.FullName -notlike "*\backup_*" -and 
                $_.FullName -notlike "*\node_modules\*" -and 
                $_.FullName -notlike "*\.git\*" -and
                $_.FullName -notlike "*\mvp\*" -and
                $_.FullName -notlike "*\tests\*" -and
                $_.FullName -notlike "*\scripts\*" -and
                $_.FullName -notlike "*\legacy\*" -and
                $_.Name -notin $excludeFiles
            }
        $allFiles += $files
    }
    
    # Remove duplicates
    $allFiles = $allFiles | Sort-Object FullName -Unique
    
    $count = $allFiles.Count
    $size = ($allFiles | Measure-Object -Property Length -Sum).Sum
    
    Write-Host "Found: $count test files ($([math]::Round($size / 1MB, 2)) MB)" -ForegroundColor White
    
    if ($count -eq 0) {
        Write-Host "No test files to delete." -ForegroundColor Gray
        return 0, 0
    }
    
    if ($DryRun) {
        Write-Host "DRY RUN: Would delete $count files" -ForegroundColor Cyan
        if ($count -le 20) {
            $allFiles | ForEach-Object { Write-Host "  - $($_.Name)" -ForegroundColor Gray }
        } else {
            Write-Host "  (Showing first 20 files)" -ForegroundColor Gray
            $allFiles | Select-Object -First 20 | ForEach-Object { Write-Host "  - $($_.Name)" -ForegroundColor Gray }
        }
        return $count, $size
    } else {
        Write-Host "Deleting $count files..." -ForegroundColor Yellow
        $allFiles | Remove-Item -Force -ErrorAction SilentlyContinue
        Write-Host "Deleted: $count files" -ForegroundColor Green
        return $count, $size
    }
}

# Function to remove old output PDFs (keep recent ones)
function Remove-OldOutputFiles {
    param([bool]$DryRun = $true, [int]$KeepDays = 30)
    
    Write-Host "`n=== Old Output PDF Files ===" -ForegroundColor Yellow
    
    $cutoffDate = (Get-Date).AddDays(-$KeepDays)
    $outputDir = Join-Path $projectRoot "output"
    
    if (-not (Test-Path $outputDir)) {
        Write-Host "Output directory not found." -ForegroundColor Gray
        return 0, 0
    }
    
    $files = Get-ChildItem -Path $outputDir -File -Filter "*.pdf" -ErrorAction SilentlyContinue |
        Where-Object { 
            $_.LastWriteTime -lt $cutoffDate -and
            $_.Name -notlike "*.sync-conflict*"
        }
    
    $count = $files.Count
    $size = ($files | Measure-Object -Property Length -Sum).Sum
    
    Write-Host "Found: $count old PDF files ($([math]::Round($size / 1MB, 2)) MB)" -ForegroundColor White
    Write-Host "Keeping files newer than $KeepDays days" -ForegroundColor Gray
    
    if ($count -eq 0) {
        Write-Host "No old output files to delete." -ForegroundColor Gray
        return 0, 0
    }
    
    if ($DryRun) {
        Write-Host "DRY RUN: Would delete $count files" -ForegroundColor Cyan
        return $count, $size
    } else {
        Write-Host "Deleting $count files..." -ForegroundColor Yellow
        $files | Remove-Item -Force -ErrorAction SilentlyContinue
        Write-Host "Deleted: $count files" -ForegroundColor Green
        return $count, $size
    }
}

# Main execution
Write-Host "Starting cleanup analysis..." -ForegroundColor Cyan
Write-Host ""

# Dry run first
Write-Host "=== DRY RUN MODE ===" -ForegroundColor Cyan
Write-Host "No files will be deleted. This is a preview." -ForegroundColor Yellow
Write-Host ""

# 1. Sync-conflict files
$count, $size = Remove-FilesSafely -Pattern "*.sync-conflict*" -Description "Sync-Conflict Files" -DryRun $true
$stats.SyncConflictFiles = $count
$stats.TotalSize += $size

# 2. Old test files
$count, $size = Remove-OldTestFiles -DryRun $true
$stats.OldTestFiles = $count
$stats.TotalSize += $size

# 3. Old output files (keep last 30 days)
$count, $size = Remove-OldOutputFiles -DryRun $true -KeepDays 30
$stats.OldOutputFiles = $count
$stats.TotalSize += $size

# Summary
Write-Host "`n=== SUMMARY ===" -ForegroundColor Cyan
Write-Host "Sync-Conflict Files: $($stats.SyncConflictFiles)" -ForegroundColor White
Write-Host "Old Test Files: $($stats.OldTestFiles)" -ForegroundColor White
Write-Host "Old Output Files: $($stats.OldOutputFiles)" -ForegroundColor White
Write-Host "Total Files to Delete: $($stats.SyncConflictFiles + $stats.OldTestFiles + $stats.OldOutputFiles)" -ForegroundColor Yellow
Write-Host "Total Size: $([math]::Round($stats.TotalSize / 1MB, 2)) MB" -ForegroundColor Yellow
Write-Host ""

# Ask for confirmation
Write-Host "To actually delete these files, run:" -ForegroundColor Cyan
Write-Host "  .\scripts\cleanup-outdated-files.ps1 -Execute" -ForegroundColor White
Write-Host ""

# If -Execute parameter is provided, actually delete files
if ($args -contains "-Execute") {
    Write-Host "=== EXECUTION MODE ===" -ForegroundColor Red
    Write-Host "Files will be permanently deleted!" -ForegroundColor Red
    Write-Host ""
    
    $confirmation = Read-Host "Type 'YES' to confirm deletion"
    if ($confirmation -eq "YES") {
        Write-Host "`nDeleting files..." -ForegroundColor Yellow
        
        # 1. Sync-conflict files
        $count, $size = Remove-FilesSafely -Pattern "*.sync-conflict*" -Description "Sync-Conflict Files" -DryRun $false
        $stats.SyncConflictFiles = $count
        
        # 2. Old test files (commented out for safety - uncomment if needed)
        # $count, $size = Remove-OldTestFiles -DryRun $false
        # $stats.OldTestFiles = $count
        
        # 3. Old output files
        # $count, $size = Remove-OldOutputFiles -DryRun $false -KeepDays 30
        # $stats.OldOutputFiles = $count
        
        Write-Host "`n=== CLEANUP COMPLETE ===" -ForegroundColor Green
        Write-Host "Deleted $($stats.SyncConflictFiles) sync-conflict files" -ForegroundColor Green
    } else {
        Write-Host "Cleanup cancelled." -ForegroundColor Yellow
    }
}

Write-Host "`nDone." -ForegroundColor Green

