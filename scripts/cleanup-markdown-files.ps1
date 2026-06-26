# Cleanup Outdated Markdown Files Script
# This script identifies and removes outdated .md files

Write-Host "=== Markdown Files Cleanup ===" -ForegroundColor Cyan
Write-Host ""

$projectRoot = Split-Path -Parent $PSScriptRoot
Set-Location $projectRoot

# Essential files to KEEP (never delete)
$essentialFiles = @(
    "README.md",
    "XAMPP-SETUP.md",
    "INSTALLATION.md",
    "QUICK_START_GUIDE.md",
    "FUNCTIONALITY_DOCUMENTATION.md",
    "FUNCTIONALITY_QUICK_REFERENCE.md",
    "AUTOMATED_VERIFICATION_README.md",
    "PRODUCTION_DEPLOYMENT.md"
)

# Files in subdirectories to keep
$essentialPaths = @(
    "mcp-server\README.md",
    "mcp-field-editor\README.md",
    "tests\README.md",
    ".github\copilot-instructions.md",
    "mvp\VERIFICATION_GUIDE.md",
    "mvp\VERIFICATION_SUMMARY.md",
    "DESIGN SPECS\*.md"
)

# Outdated/Historical files to DELETE (implementation milestones, completed tasks)
$outdatedFiles = @(
    "IMPLEMENTATION_COMPLETE_2025-10-11.md",
    "PLAN_COMPLETE.md",
    "CODEBASE_FIXES_SUMMARY.md",
    "ANSWER-FL100-QUESTION.md",
    "AUTOFILL_QUICKSTART.md",
    "COMPLETE-TOOLS-ANALYSIS-2025.md",
    "FL105-QPDF-INTEGRATION-COMPLETE.md",
    "FL105-SETUP-COMPLETE.md",
    "HYBRID_APPROACH.md",
    "JAVASCRIPT-UPGRADE-COMPLETE.md",
    "JS-TOOLS-CONFIRMED.md",
    "PDF-TOOLS-UPGRADE-2025.md",
    "QPDF-INTEGRATION-SUCCESS.md",
    "READY-TO-USE.md",
    "SUREFIRE_EXTRACTION_COMPLETE.md",
    "TOOLS-QUICK-REFERENCE-2025.md",
    "QPDF_GHOSTSCRIPT_INTEGRATION.md",
    "TEST_RESULTS_QPDF_GHOSTSCRIPT.md",
    "INTEGRATION_SUMMARY.md",
    "TESTING_COMPLETE_SUMMARY.md",
    "POSITION_ACCURACY_ANALYSIS.md",
    "POSITION_ACCURACY_EXPLANATION.md",
    "FONT_SELECTION_FEATURE.md",
    "UNIVERSAL_FONT_SYSTEM.md",
    "UNIVERSAL_FONT_SYSTEM_COMPLETE.md",
    "FL100_GENERATION_RESULTS.md",
    "POSITION_FIX_SUMMARY.md",
    "FINAL_RESULT_SUMMARY.md",
    "ENHANCED_DETECTION_METHODS.md",
    "NGINX_MIGRATION_GUIDE.md",
    "PDF_FIELD_EXTRACTION.md"
)

# Get all .md files
$allMdFiles = Get-ChildItem -Path $projectRoot -Recurse -File -Filter "*.md" -ErrorAction SilentlyContinue | 
    Where-Object { 
        $_.FullName -notlike "*\backup_*" -and 
        $_.FullName -notlike "*\node_modules\*" -and 
        $_.FullName -notlike "*\.git\*" -and 
        $_.FullName -notlike "*\vendor\*" -and
        $_.FullName -notlike "*\legacy\*"  # Keep legacy folder files
    }

Write-Host "Found $($allMdFiles.Count) total .md files" -ForegroundColor White
Write-Host ""

# Identify files to delete
$filesToDelete = @()
$filesToKeep = @()

foreach ($file in $allMdFiles) {
    $relativePath = $file.FullName.Replace($projectRoot + "\", "").Replace("\", "/")
    $fileName = $file.Name
    
    # Check if it's an essential file
    $isEssential = $false
    foreach ($essential in $essentialFiles) {
        if ($fileName -eq $essential) {
            $isEssential = $true
            break
        }
    }
    
    # Check if it's in an essential path
    if (-not $isEssential) {
        foreach ($path in $essentialPaths) {
            if ($relativePath -like $path -or $file.FullName -like "*\$path") {
                $isEssential = $true
                break
            }
        }
    }
    
    # Check if it's in legacy folder (keep those)
    if ($file.FullName -like "*\legacy\*") {
        $isEssential = $true
    }
    
    # Check if it's in Design SPECS (keep those)
    if ($file.FullName -like "*\DESIGN SPECS\*") {
        $isEssential = $true
    }
    
    # Check if it's outdated
    $isOutdated = $false
    if (-not $isEssential) {
        foreach ($outdated in $outdatedFiles) {
            if ($fileName -eq $outdated) {
                $isOutdated = $true
                break
            }
        }
    }
    
    if ($isEssential) {
        $filesToKeep += $file
    } elseif ($isOutdated) {
        $filesToDelete += $file
    } else {
        # Unknown file - keep it for safety
        $filesToKeep += $file
        Write-Host "  KEEP (unknown): $relativePath" -ForegroundColor Yellow
    }
}

Write-Host "=== Analysis Results ===" -ForegroundColor Cyan
Write-Host "Files to KEEP: $($filesToKeep.Count)" -ForegroundColor Green
Write-Host "Files to DELETE: $($filesToDelete.Count)" -ForegroundColor Yellow
Write-Host ""

if ($filesToDelete.Count -gt 0) {
    $totalSize = ($filesToDelete | Measure-Object -Property Length -Sum).Sum
    Write-Host "Total size to free: $([math]::Round($totalSize / 1KB, 2)) KB" -ForegroundColor White
    Write-Host ""
    
    Write-Host "=== Files to be DELETED ===" -ForegroundColor Yellow
    foreach ($file in $filesToDelete) {
        $relativePath = $file.FullName.Replace($projectRoot + "\", "")
        Write-Host "  - $relativePath" -ForegroundColor Gray
    }
    Write-Host ""
    
    # Dry run by default
    if ($args -contains "-Execute") {
        Write-Host "=== EXECUTING DELETION ===" -ForegroundColor Red
        $deleted = 0
        $errors = 0
        
        foreach ($file in $filesToDelete) {
            try {
                Remove-Item -Path $file.FullName -Force -ErrorAction Stop
                $deleted++
            } catch {
                $errors++
                Write-Host "  ERROR: Could not delete $($file.Name)" -ForegroundColor Red
            }
        }
        
        Write-Host ""
        Write-Host "=== Cleanup Complete ===" -ForegroundColor Green
        Write-Host "Deleted: $deleted files" -ForegroundColor Green
        Write-Host "Errors: $errors files" -ForegroundColor $(if ($errors -eq 0) { "Green" } else { "Red" })
    } else {
        Write-Host "=== DRY RUN MODE ===" -ForegroundColor Cyan
        Write-Host "No files were deleted. To actually delete, run:" -ForegroundColor Yellow
        Write-Host "  .\scripts\cleanup-markdown-files.ps1 -Execute" -ForegroundColor White
    }
} else {
    Write-Host "No outdated files found to delete." -ForegroundColor Green
}

Write-Host ""

