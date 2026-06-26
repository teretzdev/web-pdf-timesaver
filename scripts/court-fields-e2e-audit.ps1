# Court Fields E2E audit runner (API + export PDF text). Requires Java + pdfbox jar.
param(
    [string]$Base = 'https://pdftimesaver.desktopmasters.com/mvp',
    [string]$StateProject = 'p_4eaab879384a',
    [string]$StatePd = 'pd_1cd93067f643',
    [string]$FederalProject = 'p_f38c0bd04bf1',
    [string]$FederalPd = 'pd_2d30c63dd4e9'
)

$ErrorActionPreference = 'Stop'
$base = $Base.TrimEnd('/')
$pdfbox = Join-Path (Split-Path $PSScriptRoot -Parent) 'bin\pdfbox\pdfbox-app-3.0.1.jar'
$tmp = Join-Path $env:TEMP "court-e2e-$PID"
New-Item -ItemType Directory -Force -Path $tmp | Out-Null

$pass = 0; $fail = 0

function Assert($ok, $name, $detail = '') {
    if ($ok) { $script:pass++ } else { $script:fail++ }
    $icon = if ($ok) { 'PASS' } else { 'FAIL' }
    $line = "[$icon] $name"
    if ($detail) { $line += " - $detail" }
    Write-Host $line
}

function Get-Json($url) {
    return Invoke-RestMethod -Uri $url -Headers @{ Accept = 'application/json' } -UseBasicParsing
}

function Export-PdfText($projectId, $pdId, $label) {
    $pdf = Join-Path $tmp "$label.pdf"
    $txt = Join-Path $tmp "$label.txt"
    $url = "$base/?route=actions/export-project-forms&projectId=$projectId&pd=$pdId&scope=this&format=pdf"
    Invoke-WebRequest -Uri $url -OutFile $pdf -UseBasicParsing | Out-Null
    if ((Get-Item $pdf).Length -lt 1000) { throw "Export not a PDF: $url" }
    if (-not (Test-Path $pdfbox)) { throw "pdfbox jar missing: $pdfbox" }
    & java -jar $pdfbox export:text -i $pdf -o $txt 2>$null | Out-Null
    return Get-Content $txt -Raw
}

Write-Host "Court Fields E2E Audit (PowerShell)"
Write-Host "Base: $base`n"

# API federal
$fed = Get-Json "$base/?route=api/courts/search&q=Central+District+California&limit=10&system=federal"
Assert ($fed.success -eq $true) 'Federal API success'
Assert ($fed.results.Count -ge 1) 'Federal API has results'
Assert (@($fed.results | Where-Object { $_.courtSystem -ne 'federal' }).Count -eq 0) 'Federal API all federal'
Assert (@($fed.results | Where-Object { ($_.street + $_.courtName) -match '255.*Temple' }).Count -ge 1) 'Federal API includes 255 E Temple'

# API state
$st = Get-Json "$base/?route=api/courts/search&q=Stanley+Mosk&limit=10&system=state"
Assert ($st.results.Count -ge 1) 'State API Stanley Mosk'
Assert (@($st.results | Where-Object { $_.courtName -match 'Stanley Mosk' }).Count -ge 1) 'State API names Mosk'

# Cross-filter
$cross = Get-Json "$base/?route=api/courts/search&q=Stanley+Mosk&limit=10&system=federal"
Assert (@($cross.results | Where-Object { $_.courtName -match 'Stanley Mosk' }).Count -eq 0) 'Mosk absent from federal filter'

# State export matrix
$stateText = Export-PdfText $StateProject $StatePd 'state'
foreach ($needle in @('Jordan Q. Tester','298765','Youngman','Merlin Kirkpatrick','Stanley Mosk','111 North Hill','E2E-JUN24-001','Agent verified')) {
    Assert ($stateText -match [regex]::Escape($needle)) "State PDF contains: $needle"
}
foreach ($bad in @('Central District of California','255 East Temple')) {
    Assert ($stateText -notmatch [regex]::Escape($bad)) "State PDF excludes: $bad"
}

# Federal export matrix
$fedText = Export-PdfText $FederalProject $FederalPd 'federal'
foreach ($needle in @('Central District of California','255 East Temple Street','E2E-FED-001','Jordan Q. Tester','Merlin Kirkpatrick','Youngman')) {
    Assert ($fedText -match [regex]::Escape($needle)) "Federal PDF contains: $needle"
}
foreach ($bad in @('Stanley Mosk Courthouse','111 North Hill Street','E2E-JUN24-001')) {
    Assert ($fedText -notmatch [regex]::Escape($bad)) "Federal PDF excludes: $bad"
}

Write-Host "`nPassed: $pass | Failed: $fail"
if ($fail -gt 0) { exit 1 }
