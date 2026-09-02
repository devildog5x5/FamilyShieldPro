# Build FamilyShieldPro-PHP-<version>.zip for Hostinger public_html
$ErrorActionPreference = "Stop"
$Root = $PSScriptRoot
$Out = Join-Path $Root "installers"
$Stage = Join-Path $Root "build\phpdrop"
$DbFile = Join-Path $Root "php\src\Db.php"
$DbText = Get-Content -Path $DbFile -Raw
if ($DbText -notmatch "VERSION\s*=\s*'([^']+)'") {
    throw "Could not read VERSION from php/src/Db.php"
}
$Version = $Matches[1]
$ZipName = "FamilyShieldPro-PHP-$Version.zip"
$Zip = Join-Path $Out $ZipName

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
Build: $Version
=========================================
1. Unzip ALL of these files into public_html (not a subfolder).
2. Copy .env.example to .env
3. Set APP_SECRET (long random string) and BASE_URL=https://yourdomain.com
4. hPanel → PHP Configuration: PHP 8.2 or 8.3, enable pdo_sqlite
5. Open the site. Demo login (if SHOW_DEMO_LOGIN=1): family@ourcircle.app / password123
6. Set OPERATOR_EMAIL and OPERATOR_PASSWORD. Forgot password for the circle (/forgot) and operator (/admin/forgot) emails a link, or writes data/password-reset.txt if mail is off.
7. Back up any existing data/*.db before replacing files on a live site.

SQLite is created at data/ourcircle.db (blocked from the web by .htaccess).
"@
Set-Content -Path (Join-Path $Stage "HOSTINGER.txt") -Value $readme -Encoding UTF8

Get-ChildItem -Path $Out -Filter "FamilyShieldPro-PHP*.zip" -ErrorAction SilentlyContinue | Remove-Item -Force
Get-ChildItem -Path $Stage -Force | Compress-Archive -DestinationPath $Zip -Force

Write-Host "Built v$Version"
Write-Host "  $Zip"
Write-Host ("Size {0:N1} KB" -f ((Get-Item $Zip).Length / 1KB))
