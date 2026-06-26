# Pause before hitting production after a local save when Syncthing pushes changes.
# Default 10 seconds (override: -Seconds 20).
param(
    [int]$Seconds = 10
)
if ($Seconds -lt 0) { $Seconds = 0 }
Write-Host "Waiting $Seconds s for Syncthing sync..."
Start-Sleep -Seconds $Seconds
