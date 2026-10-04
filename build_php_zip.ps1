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

Get-ChildItem -Path (Join-Path $Root "php") -Force | Where-Object { $_.Name -ne ".env" } | ForEach-Object {
    Copy-Item -Path $_.FullName -Destination (Join-Path $Stage $_.Name) -Recurse -Force
}

# Never ship secrets or live data
Remove-Item -Force -ErrorAction SilentlyContinue (Join-Path $Stage ".env")
Get-ChildItem -Path (Join-Path $Stage "data") -Filter "*.db" -ErrorAction SilentlyContinue | Remove-Item -Force
Get-ChildItem -Path (Join-Path $Stage "data") -Filter "*.db-*" -ErrorAction SilentlyContinue | Remove-Item -Force
$uploads = Join-Path $Stage "data\uploads"
if (Test-Path $uploads) { Remove-Item -Recurse -Force $uploads }
Remove-Item -Force -ErrorAction SilentlyContinue (Join-Path $Stage "HOSTINGER.txt")

Get-ChildItem -Path $Out -Filter "FamilyShieldPro-PHP*.zip" -ErrorAction SilentlyContinue | Remove-Item -Force
if (Test-Path $Zip) { Remove-Item -Force $Zip }

# Compress-Archive writes backslash paths. Hostinger and Linux unzip need forward slashes.
Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem
$zipArchive = [System.IO.Compression.ZipFile]::Open($Zip, [System.IO.Compression.ZipArchiveMode]::Create)
try {
    $basePath = (Resolve-Path $Stage).Path
    Get-ChildItem -Path $Stage -Recurse -File -Force | ForEach-Object {
        $rel = $_.FullName.Substring($basePath.Length).TrimStart('\', '/')
        $rel = ($rel -replace '\\', '/')
        [void][System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile(
            $zipArchive,
            $_.FullName,
            $rel,
            [System.IO.Compression.CompressionLevel]::Optimal
        )
    }
}
finally {
    $zipArchive.Dispose()
}

Write-Host "Built v$Version"
Write-Host "  $Zip"
Write-Host ("Size {0:N1} KB" -f ((Get-Item $Zip).Length / 1KB))
Write-Host "Hostinger steps: deploy\HOSTINGER.txt (not in the zip)"
