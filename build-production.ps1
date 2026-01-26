# Quick Build Script for demosystem.ct.ws Production APK
# This script builds your APK with the production API URL configured

Write-Host "`n🚀 Building Production APK for demosystem.ct.ws`n" -ForegroundColor Cyan

$productionUrl = "https://demoserver.alwaysdata.net/attendance_api/"

# Navigate to attendance directory
$attendanceDir = Join-Path $PSScriptRoot "attendance"

if (-not (Test-Path $attendanceDir)) {
    Write-Host "❌ Error: attendance directory not found!" -ForegroundColor Red
    Write-Host "Make sure to run this script from the project root directory.`n"
    exit 1
}

Set-Location $attendanceDir

Write-Host "📍 Production API URL: $productionUrl`n" -ForegroundColor Green

# Clean previous builds
Write-Host "🧹 Cleaning previous builds..." -ForegroundColor Cyan
flutter clean | Out-Null

# Get dependencies
Write-Host "📦 Getting dependencies..." -ForegroundColor Cyan
flutter pub get

# Build APK
Write-Host "`n🔨 Building production release APK...`n" -ForegroundColor Cyan
Write-Host "This may take a few minutes. Please wait...`n" -ForegroundColor Yellow

flutter build apk --release --dart-define=API_BASE_URL="$productionUrl"

# Check if build was successful
$apkPath = Join-Path $attendanceDir "build\app\outputs\flutter-apk\app-release.apk"

if (Test-Path $apkPath) {
    $apkSize = (Get-Item $apkPath).Length / 1MB
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    
    Write-Host "`n" + "="*60 -ForegroundColor Green
    Write-Host "✅ SUCCESS! Production APK Built Successfully!" -ForegroundColor Green
    Write-Host "="*60 + "`n" -ForegroundColor Green
    
    Write-Host "📱 APK Details:" -ForegroundColor Cyan
    Write-Host "   Location: $apkPath" -ForegroundColor White
    Write-Host "   Size: $([math]::Round($apkSize, 2)) MB" -ForegroundColor White
    Write-Host "   Built: $timestamp" -ForegroundColor White
    Write-Host "   API URL: $productionUrl`n" -ForegroundColor White
    
    Write-Host "📋 Next Steps:" -ForegroundColor Yellow
    Write-Host "   1. Install APK on your Android device" -ForegroundColor White
    Write-Host "   2. Test login and all features" -ForegroundColor White
    Write-Host "   3. Distribute via Firebase or Play Store" -ForegroundColor White
    Write-Host "   4. See DEPLOYMENT-STATUS.md for checklist`n" -ForegroundColor White
    
    # Ask if user wants to open the folder
    $openFolder = Read-Host "Open APK folder? (y/n)"
    if ($openFolder -eq "y") {
        explorer (Split-Path $apkPath)
    }
    
    Write-Host "`n✨ Production build complete!`n" -ForegroundColor Green
}
else {
    Write-Host "`n" + "="*60 -ForegroundColor Red
    Write-Host "❌ Build Failed!" -ForegroundColor Red
    Write-Host "="*60 + "`n" -ForegroundColor Red
    Write-Host "Check the error messages above for details.`n" -ForegroundColor Yellow
    exit 1
}
