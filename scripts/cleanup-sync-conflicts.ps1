# Remove all files with 'sync-conflict' in their name under the repository root
Get-ChildItem -Path "$PSScriptRoot\.." -Filter '*sync-conflict*' -Recurse -File | ForEach-Object {
    Write-Host "Removing: $($_.FullName)"
    Remove-Item -Force $_.FullName -ErrorAction SilentlyContinue
}
Write-Host "Cleanup complete." 
