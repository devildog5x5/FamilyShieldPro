# Create Family Shield Pro test products, prices, Payment Links, and webhook.
# Reads STRIPE_SECRET_KEY from php/.env (must be sk_test_). Never prints the secret.
param(
    [string]$BaseUrl = ""
)
$ErrorActionPreference = "Stop"
$Root = Split-Path -Parent $PSScriptRoot
$EnvFile = Join-Path $Root "php\.env"

if (-not (Test-Path $EnvFile)) {
    throw "Missing $EnvFile — copy php/.env.example to php/.env first."
}

function Read-DotEnv([string]$path) {
    $map = [ordered]@{}
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

function Write-DotEnvKey([string]$path, [string]$key, [string]$value) {
    $lines = Get-Content -Path $path
    $found = $false
    $out = foreach ($line in $lines) {
        if ($line -match ("^\s*" + [regex]::Escape($key) + "\s*=")) {
            $found = $true
            "$key=$value"
        } else {
            $line
        }
    }
    if (-not $found) { $out += "$key=$value" }
    Set-Content -Path $path -Value $out -Encoding UTF8
}

function Invoke-Stripe {
    param(
        [string]$Method,
        [string]$Path,
        [hashtable]$Body = $null
    )
    $headers = @{ Authorization = "Bearer $script:Secret" }
    $uri = "https://api.stripe.com$Path"
    if ($Body) {
        return Invoke-RestMethod -Method $Method -Uri $uri -Headers $headers -Body $Body
    }
    return Invoke-RestMethod -Method $Method -Uri $uri -Headers $headers
}

$envMap = Read-DotEnv $EnvFile
$script:Secret = [string]$envMap["STRIPE_SECRET_KEY"]
if (-not $script:Secret.StartsWith("sk_test_")) {
    throw "php/.env STRIPE_SECRET_KEY must be a test key (sk_test_...). Live keys are not used by this script."
}

if (-not $BaseUrl) {
    $fromEnv = [string]$envMap["BASE_URL"]
    if ($fromEnv -match '^https://' -and $fromEnv -notmatch 'localhost|127\.0\.0\.1') {
        $BaseUrl = $fromEnv
    } else {
        $BaseUrl = "https://sandbox.familyshieldpro.com"
    }
}
$base = $BaseUrl.TrimEnd("/")
$success = "$base/billing/success?session_id={CHECKOUT_SESSION_ID}"
$webhookUrl = "$base/billing/webhook"

Write-Host "Using test mode. Base URL: $base"

$products = Invoke-Stripe GET "/v1/products?limit=100&active=true"
$byName = @{}
foreach ($p in $products.data) { $byName[$p.name] = $p }

function Ensure-Product([string]$name) {
    if ($byName.ContainsKey($name)) { return $byName[$name] }
    $created = Invoke-Stripe POST "/v1/products" @{ name = $name }
    $byName[$name] = $created
    Write-Host "Created product: $name"
    return $created
}

function Ensure-Price($product, [int]$amount, [string]$interval, [string]$existing) {
    if ($existing -and $existing.StartsWith("price_")) {
        try {
            $got = Invoke-Stripe GET "/v1/prices/$existing"
            if ($got -and $got.id) { return $got.id }
        } catch { }
    }
    $list = Invoke-Stripe GET "/v1/prices?product=$($product.id)&active=true&limit=20"
    foreach ($pr in $list.data) {
        if ($pr.unit_amount -eq $amount -and $pr.recurring.interval -eq $interval -and $pr.currency -eq "usd") {
            return $pr.id
        }
    }
    $created = Invoke-Stripe POST "/v1/prices" @{
        product = $product.id
        currency = "usd"
        unit_amount = $amount
        "recurring[interval]" = $interval
    }
    Write-Host "Created $interval price $($created.id)"
    return $created.id
}

function Ensure-PaymentLink([string]$priceId, [string]$existing) {
    if ($existing -and $existing.StartsWith("https://buy.stripe.com/")) {
        return $existing
    }
    $created = Invoke-Stripe POST "/v1/payment_links" @{
        "line_items[0][price]" = $priceId
        "line_items[0][quantity]" = 1
        "after_completion[type]" = "redirect"
        "after_completion[redirect][url]" = $success
    }
    Write-Host "Created Payment Link $($created.url)"
    return $created.url
}

$monthlyProd = Ensure-Product "Family Shield Pro — Monthly"
$yearlyProd = Ensure-Product "Family Shield Pro — Yearly"
$monthlyPrice = Ensure-Price $monthlyProd 1499 "month" ([string]$envMap["STRIPE_PRICE_MONTHLY"])
$yearlyPrice = Ensure-Price $yearlyProd 11999 "year" ([string]$envMap["STRIPE_PRICE_YEARLY"])
$monthlyLink = Ensure-PaymentLink $monthlyPrice ([string]$envMap["STRIPE_PAYMENT_LINK_MONTHLY"])
$yearlyLink = Ensure-PaymentLink $yearlyPrice ([string]$envMap["STRIPE_PAYMENT_LINK_YEARLY"])

$hooks = Invoke-Stripe GET "/v1/webhook_endpoints?limit=20"
$hook = $null
foreach ($h in $hooks.data) {
    if ($h.url -eq $webhookUrl) { $hook = $h; break }
}
$whsec = [string]$envMap["STRIPE_WEBHOOK_SECRET"]
if (-not $hook) {
    $hook = Invoke-Stripe POST "/v1/webhook_endpoints" @{
        url = $webhookUrl
        "enabled_events[0]" = "checkout.session.completed"
        "enabled_events[1]" = "customer.subscription.created"
        "enabled_events[2]" = "customer.subscription.updated"
        "enabled_events[3]" = "customer.subscription.deleted"
    }
    if ($hook.secret) { $whsec = [string]$hook.secret }
    Write-Host "Created webhook endpoint $webhookUrl"
} else {
    Write-Host "Webhook already exists: $webhookUrl"
    if (-not $whsec.StartsWith("whsec_")) {
        Write-Host "Signing secret is only shown when the endpoint is first created. Copy it from Dashboard → Webhooks if .env is still empty."
    }
}

Write-DotEnvKey $EnvFile "STRIPE_PRICE_MONTHLY" $monthlyPrice
Write-DotEnvKey $EnvFile "STRIPE_PRICE_YEARLY" $yearlyPrice
Write-DotEnvKey $EnvFile "STRIPE_PAYMENT_LINK_MONTHLY" $monthlyLink
Write-DotEnvKey $EnvFile "STRIPE_PAYMENT_LINK_YEARLY" $yearlyLink
if ($whsec.StartsWith("whsec_")) {
    Write-DotEnvKey $EnvFile "STRIPE_WEBHOOK_SECRET" $whsec
}

Write-Host ""
Write-Host "Test Payment Links (not secret):"
Write-Host "  Monthly $14.99  $monthlyLink"
Write-Host "  Yearly  $119.99 $yearlyLink"
Write-Host "  Price monthly $monthlyPrice"
Write-Host "  Price yearly  $yearlyPrice"
Write-Host "  Webhook       $webhookUrl"
Write-Host "Copy these Stripe lines onto Hostinger php/.env, then pay with 4242 4242 4242 4242."
