# Check Family Shield Pro Stripe .env and write a local log (no secrets).
# php/.env on this PC, and optionally the live sandbox version.
$ErrorActionPreference = "Stop"
$Root = Split-Path -Parent $PSScriptRoot
$EnvFile = Join-Path $Root "php\.env"
$Log = Join-Path $Root "stripe-check.log"
$DataLog = Join-Path $Root "php\data\stripe-check.txt"

function Read-DotEnv([string]$path) {
    $map = [ordered]@{}
    if (-not (Test-Path $path)) { return $map }
    Get-Content -Path $path | ForEach-Object {
        $line = $_.TrimEnd()
        if ($line -eq "" -or $line.StartsWith("#") -or -not $line.Contains("=")) { return }
        $i = $line.IndexOf("=")
        $k = $line.Substring(0, $i).Trim()
        $v = $line.Substring($i + 1).Trim()
        if ($k) { $map[$k] = $v }
    }
    return $map
}

function Test-Set([string]$v, [string]$prefix, [int]$minLen) {
    if (-not $v -or $v.Contains("...")) { return $false }
    if ($prefix -and -not $v.StartsWith($prefix)) { return $false }
    return $v.Length -ge $minLen
}

function Mask([string]$id) {
    if (-not $id -or $id.Length -le 10) { return "(set)" }
    return $id.Substring(0, 8) + "..." + $id.Substring($id.Length - 4)
}

$missing = New-Object System.Collections.Generic.List[string]
$ok = New-Object System.Collections.Generic.List[string]
$warn = New-Object System.Collections.Generic.List[string]

$ok.Add("Local check of $EnvFile")

if (-not (Test-Path $EnvFile)) {
    $missing.Add("php/.env does not exist")
    $envMap = @{}
} else {
    $envMap = Read-DotEnv $EnvFile
}

$base = [string]$envMap["BASE_URL"]
$secret = [string]$envMap["STRIPE_SECRET_KEY"]
$pk = [string]$envMap["STRIPE_PUBLISHABLE_KEY"]
$monthly = [string]$envMap["STRIPE_PRICE_MONTHLY"]
$yearly = [string]$envMap["STRIPE_PRICE_YEARLY"]
$wh = [string]$envMap["STRIPE_WEBHOOK_SECRET"]
$linkM = [string]$envMap["STRIPE_PAYMENT_LINK_MONTHLY"]
$linkY = [string]$envMap["STRIPE_PAYMENT_LINK_YEARLY"]

if (-not $base) { $missing.Add("BASE_URL in .env") }
elseif ($base -match "localhost|127\.0\.0\.1") { $warn.Add("BASE_URL is localhost. Sandbox webhooks need https://sandbox.familyshieldpro.com") }
else { $ok.Add("BASE_URL is set ($base)") }

if (-not (Test-Set $secret "sk_" 20)) { $missing.Add("STRIPE_SECRET_KEY (sk_test_... for sandbox)") }
elseif ($secret.StartsWith("sk_live_")) { $warn.Add("STRIPE_SECRET_KEY is a live key. Sandbox should use sk_test_."); $ok.Add("STRIPE_SECRET_KEY is set (live)") }
elseif ($secret.StartsWith("sk_test_")) { $ok.Add("STRIPE_SECRET_KEY is set (test)") }

if (-not (Test-Set $pk "pk_" 20)) { $warn.Add("STRIPE_PUBLISHABLE_KEY is empty") }
elseif ($pk.StartsWith("pk_test_")) { $ok.Add("STRIPE_PUBLISHABLE_KEY is set (test)") }
else { $ok.Add("STRIPE_PUBLISHABLE_KEY is set") }

if (-not (Test-Set $monthly "price_" 20)) { $missing.Add("STRIPE_PRICE_MONTHLY (price_... for `$14.99/month)") }
else { $ok.Add("STRIPE_PRICE_MONTHLY is set ($(Mask $monthly))") }
if (-not (Test-Set $yearly "price_" 20)) { $missing.Add("STRIPE_PRICE_YEARLY (price_... for `$119.99/year)") }
else { $ok.Add("STRIPE_PRICE_YEARLY is set ($(Mask $yearly))") }

if (Test-Set $wh "whsec_" 16) { $ok.Add("STRIPE_WEBHOOK_SECRET is set") }
else { $missing.Add("STRIPE_WEBHOOK_SECRET (whsec_... from the sandbox webhook endpoint)") }

$hasLinks = ($linkM -match "^https://buy\.stripe\.com/") -and ($linkY -match "^https://buy\.stripe\.com/")
if ($hasLinks) { $ok.Add("Payment Links are set (optional fallback)") }
else { $warn.Add("Payment Links are empty. Not required when Checkout prices are set.") }

if (Test-Set $secret "sk_" 20) {
    $headers = @{ Authorization = "Bearer $secret" }
    function Get-Stripe([string]$Path) {
        return Invoke-RestMethod -Method GET -Uri "https://api.stripe.com$Path" -Headers $headers
    }
    function Check-Price([string]$id, [int]$amount, [string]$interval, [string]$label) {
        try {
            $got = Get-Stripe "/v1/prices/$id"
            $gotAmount = [int]$got.unit_amount
            $gotInterval = [string]$got.recurring.interval
            if ($got.livemode) { $script:warn.Add("$label price is live-mode. Sandbox should use test mode.") }
            if ($gotAmount -eq $amount -and $gotInterval -eq $interval) {
                $script:ok.Add("$label price matches in Stripe ($(Mask $id))")
            } else {
                $script:missing.Add("$label price in Stripe is $gotAmount cents / $gotInterval - need $amount / $interval")
            }
        } catch {
            $script:missing.Add("Stripe rejected STRIPE_PRICE_$($label.ToUpper()) ($($_.Exception.Message))")
        }
    }
    if (Test-Set $monthly "price_" 20) { Check-Price $monthly 1499 "month" "monthly" }
    if (Test-Set $yearly "price_" 20) { Check-Price $yearly 11999 "year" "yearly" }
    $hookUrl = ($base.TrimEnd("/")) + "/billing/webhook"
    if ($base -match "localhost|127\.0\.0\.1" -or -not $base) {
        $hookUrl = "https://sandbox.familyshieldpro.com/billing/webhook"
        $warn.Add("Webhook URL checked against sandbox because local BASE_URL cannot receive Stripe events")
    }
    try {
        $hooks = Get-Stripe "/v1/webhook_endpoints?limit=20"
        $found = $false
        foreach ($h in $hooks.data) {
            if ($h.url -eq $hookUrl) {
                $found = $true
                if ($h.status -eq "disabled") { $missing.Add("Stripe webhook exists but is disabled: $hookUrl") }
                else { $ok.Add("Stripe webhook endpoint exists for $hookUrl") }
            }
        }
        if (-not $found) { $missing.Add("No Stripe webhook endpoint for $hookUrl") }
    } catch {
        $warn.Add("Could not list Stripe webhook endpoints ($($_.Exception.Message))")
    }
} else {
    $warn.Add("Skipped live Stripe API checks until STRIPE_SECRET_KEY is set.")
}

try {
    $hz = Invoke-RestMethod -Uri "https://sandbox.familyshieldpro.com/healthz" -TimeoutSec 20
    $ver = [string]$hz.version
    if ($ver) {
        $ok.Add("Live sandbox /healthz version $ver")
        $parts = $ver.Split(".")
        $minor = 0
        $patch = 0
        if ($parts.Count -ge 2) { [void][int]::TryParse($parts[1], [ref]$minor) }
        if ($parts.Count -ge 3) { [void][int]::TryParse($parts[2], [ref]$patch) }
        if ($minor -lt 3 -or ($minor -eq 3 -and $patch -lt 12)) {
            $missing.Add("Sandbox is still v$ver. Upload v1.3.12+ so Plans can send the owner to Stripe Checkout (prices in .env are not enough on 1.3.11).")
        }
    }
} catch {
    $warn.Add("Could not read https://sandbox.familyshieldpro.com/healthz")
}

$ready = $missing.Count -eq 0
$lines = New-Object System.Collections.Generic.List[string]
$lines.Add("Family Shield Pro Stripe check")
$lines.Add("Generated: $(Get-Date -Format o)")
$lines.Add("No secret keys are written here.")
$lines.Add("")
$lines.Add($(if ($ready) { "RESULT: Ready to test payments" } else { "RESULT: Not ready - see MISSING" }))
$lines.Add("")
$lines.Add("MISSING")
if ($missing.Count -eq 0) { $lines.Add("- (none)") } else { foreach ($m in $missing) { $lines.Add("- $m") } }
$lines.Add("")
$lines.Add("OK")
foreach ($m in $ok) { $lines.Add("- $m") }
$lines.Add("")
$lines.Add("NOTES")
if ($warn.Count -eq 0) { $lines.Add("- (none)") } else { foreach ($m in $warn) { $lines.Add("- $m") } }
$lines.Add("")
$lines.Add("Test card: 4242 4242 4242 4242")
$body = ($lines -join "`r`n") + "`r`n"
Set-Content -Path $Log -Value $body -Encoding UTF8
$dataDir = Split-Path $DataLog
if (-not (Test-Path $dataDir)) { New-Item -ItemType Directory -Path $dataDir | Out-Null }
Set-Content -Path $DataLog -Value $body -Encoding UTF8
Write-Host $body
Write-Host "Wrote $Log"
Write-Host "Wrote $DataLog"
