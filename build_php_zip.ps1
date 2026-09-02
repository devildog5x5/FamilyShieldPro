# Build FamilyShieldPro-PHP.zip for Hostinger public_html
$ErrorActionPreference = "Stop"
$Root = $PSScriptRoot
$Out = Join-Path $Root "installers"
$Stage = Join-Path $Root "build\phpdrop"
$Zip = Join-Path $Out "FamilyShieldPro-PHP.zip"

New-Item -ItemType Directory -Force -Path $Out | Out-Null
if (Test-Path $Stage) { Remove-Item -Recurse -Force $Stage }
New-Item -ItemType Directory -Force -Path $Stage | Out-Null

Copy-Item -Path (Join-Path $Root "php\*") -Destination $Stage -Recurse -Force

# Never ship secrets or live data
Remove-Item -Force -ErrorAction SilentlyContinue (Join-Path $Stage ".env")
Get-ChildItem -Path (Join-Path $Stage "data") -Filter "*.db" -ErrorAction SilentlyContinue | Remove-Item -Force
Get-ChildItem -Path (Join-Path $Stage "data") -Filter "*.db-*" -ErrorAction SilentlyContinue | Remove-Item -Force
$uploads = Join-Path $Stage "data\uploads"
if (Test-Path $uploads) { Remove-Item -Recurse -Force $uploads }

$readme = @"
Family Shield Pro (OurCircle) — Hostinger
=========================================
1. Unzip ALL of these files into public_html (not a subfolder).
2. Copy .env.example to .env
3. Set APP_SECRET (long random string) and BASE_URL=https://yourdomain.com
4. hPanel → PHP Configuration: PHP 8.2 or 8.3, enable pdo_sqlite
5. Open the site. Demo login (if SHOW_DEMO_LOGIN=1): family@ourcircle.app / password123
6. Back up any existing data/*.db before replacing files on a live site.

SQLite is created at data/ourcircle.db (blocked from the web by .htaccess).
"@
Set-Content -Path (Join-Path $Stage "HOSTINGER.txt") -Value $readme -Encoding UTF8

if (Test-Path $Zip) { Remove-Item $Zip -Force }
Get-ChildItem -Path $Stage -Force | Compress-Archive -DestinationPath $Zip -Force

Write-Host "Built $Zip"
Write-Host ("Size {0:N1} KB" -f ((Get-Item $Zip).Length / 1KB))
