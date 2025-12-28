param(
    [string] $PhpPath = 'php'
)

if (-not (Get-Command $PhpPath -ErrorAction SilentlyContinue)) {
    Write-Host "PHP executable not found in PATH: $PhpPath" -ForegroundColor Yellow
    Write-Host "Install PHP or provide the full path to php.exe via -PhpPath" -ForegroundColor Yellow
    exit 2
}

$files = Get-ChildItem -Path (Join-Path $PSScriptRoot '..') -Recurse -Include *.php -File
$allOk = $true
foreach ($f in $files) {
    Write-Host "Linting $($f.FullName)"
    & $PhpPath -l $f.FullName
    if ($LASTEXITCODE -ne 0) { $allOk = $false }
}

if (-not $allOk) { Write-Host "Some files failed lint." -ForegroundColor Red; exit 1 }
Write-Host "All PHP files lint OK" -ForegroundColor Green
exit 0
