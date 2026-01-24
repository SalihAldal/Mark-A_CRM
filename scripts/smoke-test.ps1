$ErrorActionPreference = 'Stop'

function Write-Step($msg) {
  Write-Host "[SMOKE] $msg"
}

function Wait-HttpOk($url, $headers, $timeoutSeconds = 20) {
  $start = Get-Date
  while (((Get-Date) - $start).TotalSeconds -lt $timeoutSeconds) {
    try {
      $r = Invoke-WebRequest -Uri $url -Headers $headers -UseBasicParsing -TimeoutSec 5
      if ($r.StatusCode -ge 200 -and $r.StatusCode -lt 500) { return $true }
    } catch {
      Start-Sleep -Milliseconds 400
    }
  }
  return $false
}

$root = Resolve-Path (Join-Path $PSScriptRoot "..")
$laravel = Join-Path $root "apps/laravel"
$gateway = Join-Path $root "apps/realtime-gateway"

Write-Step "Repo: $root"

# --- Laravel hazırlık (MySQL + SQL import zorunlu) ---
Set-Location $laravel

if (-not (Test-Path "vendor/autoload.php")) {
  throw "Laravel vendor yok. Önce README'deki composer install adımlarını çalıştır."
}

New-Item -ItemType Directory -Force "bootstrap/cache" | Out-Null
New-Item -ItemType Directory -Force "storage/framework/cache" | Out-Null
New-Item -ItemType Directory -Force "storage/framework/sessions" | Out-Null
New-Item -ItemType Directory -Force "storage/framework/views" | Out-Null
New-Item -ItemType Directory -Force "storage/logs" | Out-Null

if (-not (Test-Path ".env")) {
  Write-Step ".env yok -> env.example kopyalanıyor"
  Copy-Item ".env.example" ".env" -Force
  throw ".env oluşturuldu. Şimdi .env içindeki MySQL ayarlarını doldur ve phpMyAdmin ile SQL import (schema/indexes/seed) yap."
}

php artisan key:generate --ansi -n | Out-Null
php artisan optimize:clear | Out-Null

if (-not (Test-Path "public/build/manifest.json")) {
  Write-Step "Vite build yok -> npm install + npm run build"
  if (-not (Test-Path "node_modules")) { npm install | Out-Null }
  npm run build | Out-Null
}

# --- Gateway hazırlık ---
Set-Location $gateway
if (-not (Test-Path "dist/server.js")) {
  Write-Step "Gateway dist yok -> npm install + npm run build"
  if (-not (Test-Path "node_modules")) { npm install | Out-Null }
  npm run build | Out-Null
}

$laravelProc = $null
$gatewayProc = $null

try {
  Write-Step "Gateway başlatılıyor :4000"
  $gatewayProc = Start-Process -FilePath "node" -ArgumentList "dist/server.js" -WorkingDirectory $gateway -PassThru -WindowStyle Hidden

  Write-Step "Laravel serve başlatılıyor :8000"
  $laravelProc = Start-Process -FilePath "php" -ArgumentList "artisan serve --host=127.0.0.1 --port=8000" -WorkingDirectory $laravel -PassThru -WindowStyle Hidden

  $base = "http://127.0.0.1:8000"

  $tenantHeaders = @{ Host = "tenant1.localhost" }
  $superHeaders = @{ Host = "superadmin.localhost" }

  if (-not (Wait-HttpOk "$base/login" $tenantHeaders 25)) {
    throw "Laravel /login hazır değil. Not: Bu smoke-test migration kullanmaz; DB SQL import (schema/indexes/seed) yapılmış olmalı."
  }

  # --- Tenant smoke ---
  Write-Step "Tenant smoke: login -> panel -> realtime/token -> logout"
  $sess = New-Object Microsoft.PowerShell.Commands.WebRequestSession
  $login = Invoke-WebRequest -Uri "$base/login" -Headers $tenantHeaders -WebSession $sess -UseBasicParsing
  $csrf = [regex]::Match($login.Content, 'name=\"_token\" value=\"([^\"]+)\"').Groups[1].Value
  if (-not $csrf) { throw "CSRF token bulunamadı (tenant login)" }

  Invoke-WebRequest -Uri "$base/login" -Headers $tenantHeaders -Method Post -WebSession $sess -Body @{ email="admin@tenant1.local"; password="password"; _token=$csrf } -MaximumRedirection 0 -ErrorAction SilentlyContinue | Out-Null

  $panel = Invoke-WebRequest -Uri "$base/panel" -Headers $tenantHeaders -WebSession $sess -UseBasicParsing
  if ($panel.StatusCode -ne 200) { throw "Tenant panel status $($panel.StatusCode)" }

  $rt = Invoke-WebRequest -Uri "$base/realtime/token" -Headers $tenantHeaders -WebSession $sess -UseBasicParsing
  if ($rt.StatusCode -ne 200) { throw "Tenant realtime token status $($rt.StatusCode)" }

  $logoutPage = Invoke-WebRequest -Uri "$base/panel" -Headers $tenantHeaders -WebSession $sess -UseBasicParsing
  $csrf2 = [regex]::Match($logoutPage.Content, 'name=\"_token\" value=\"([^\"]+)\"').Groups[1].Value
  Invoke-WebRequest -Uri "$base/logout" -Headers $tenantHeaders -Method Post -WebSession $sess -Body @{ _token=$csrf2 } -MaximumRedirection 0 -ErrorAction SilentlyContinue | Out-Null

  # --- Super smoke ---
  Write-Step "Super smoke: login -> /super"
  $sess2 = New-Object Microsoft.PowerShell.Commands.WebRequestSession
  $login2 = Invoke-WebRequest -Uri "$base/login" -Headers $superHeaders -WebSession $sess2 -UseBasicParsing
  $csrfS = [regex]::Match($login2.Content, 'name=\"_token\" value=\"([^\"]+)\"').Groups[1].Value
  if (-not $csrfS) { throw "CSRF token bulunamadı (super login)" }

  Invoke-WebRequest -Uri "$base/login" -Headers $superHeaders -Method Post -WebSession $sess2 -Body @{ email="super@marka.local"; password="password"; _token=$csrfS } -MaximumRedirection 0 -ErrorAction SilentlyContinue | Out-Null

  $super = Invoke-WebRequest -Uri "$base/super" -Headers $superHeaders -WebSession $sess2 -UseBasicParsing
  if ($super.StatusCode -ne 200) { throw "Super panel status $($super.StatusCode)" }

  Write-Host "SMOKE_OK"
} finally {
  if ($laravelProc -and -not $laravelProc.HasExited) {
    Stop-Process -Id $laravelProc.Id -Force -ErrorAction SilentlyContinue
  }
  if ($gatewayProc -and -not $gatewayProc.HasExited) {
    Stop-Process -Id $gatewayProc.Id -Force -ErrorAction SilentlyContinue
  }
}


