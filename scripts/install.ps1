# scripts/install.ps1
# FULL AUTO INSTALL (Windows + XAMPP)
# Run: powershell -ExecutionPolicy Bypass -File .\scripts\install.ps1

$ErrorActionPreference = "Stop"

function Require-Cmd($cmd) {
    if (-not (Get-Command $cmd -ErrorAction SilentlyContinue)) {
        throw "Command not found: $cmd. Install it first, then rerun."
    }
}

function Set-EnvLine($path, $key, $value) {
    $content = Get-Content $path -Raw
    if ($content -match "(?m)^$key=") {
        $content = [regex]::Replace($content, "(?m)^$key=.*$", "$key=$value")
    } else {
        $content = $content + "`r`n$key=$value"
    }
    Set-Content -Path $path -Value $content -Encoding UTF8
}

Write-Host "== deliveryv3 FULL AUTO INSTALL =="

# Check required tools
Require-Cmd "php"
Require-Cmd "composer"
Require-Cmd "npm"

# Move to project root
$projectRoot = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
Set-Location $projectRoot
Write-Host "Project root: $projectRoot"

# 1) Composer deps
Write-Host "`n[1/8] composer install..."
composer install --no-interaction

# 2) .env setup
Write-Host "`n[2/8] Setup .env..."
if (-not (Test-Path ".env")) {
    if (-not (Test-Path ".env.example")) { throw ".env.example not found." }
    Copy-Item ".env.example" ".env"
    Write-Host "Created .env from .env.example"
} else {
    Write-Host ".env already exists (kept)."
}

# 3) Force DB defaults (edit if you need different values)
# You can change these defaults here.
$dbName = "deliveryv3"
$dbUser = "root"
$dbPass = ""     # set if your MySQL has password
$dbHost = "127.0.0.1"
$dbPort = "3306"

Write-Host "`n[3/8] Configure DB settings in .env..."
Set-EnvLine ".env" "DB_CONNECTION" "mysql"
Set-EnvLine ".env" "DB_HOST" $dbHost
Set-EnvLine ".env" "DB_PORT" $dbPort
Set-EnvLine ".env" "DB_DATABASE" $dbName
Set-EnvLine ".env" "DB_USERNAME" $dbUser
Set-EnvLine ".env" "DB_PASSWORD" $dbPass

# 4) Generate key if needed
Write-Host "`n[4/8] Ensure APP_KEY..."
$envText = Get-Content ".env" -Raw
if ($envText -notmatch "(?m)^APP_KEY=base64:") {
    php artisan key:generate --force
} else {
    Write-Host "APP_KEY already set."
}

# 5) Clear caches
Write-Host "`n[5/8] Clearing caches..."
php artisan optimize:clear

# 6) Frontend deps + build
Write-Host "`n[6/8] npm install + build..."
npm install
npm run build

# 7) Create database if possible (auto)
# We'll try to use mysql client (XAMPP provides it) - if not found, we skip.
Write-Host "`n[7/8] Create database (auto if mysql client exists)..."
$mysqlCmd = Get-Command mysql -ErrorAction SilentlyContinue
if ($mysqlCmd) {
    # If DB_PASSWORD empty: no -p
    if ([string]::IsNullOrEmpty($dbPass)) {
        & mysql -u $dbUser -h $dbHost -P $dbPort -e "CREATE DATABASE IF NOT EXISTS \`$dbName\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    } else {
        & mysql -u $dbUser -p$dbPass -h $dbHost -P $dbPort -e "CREATE DATABASE IF NOT EXISTS \`$dbName\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    }
    Write-Host "Database ensured: $dbName"
} else {
    Write-Host "mysql client not found in PATH. Skipping DB auto-create."
    Write-Host "If migrate fails, create DB manually via phpMyAdmin: $dbName"
}

# 8) Migrate + storage link (FULL AUTO)
Write-Host "`n[8/8] Migrate + storage link..."
php artisan migrate --force
php artisan storage:link

Write-Host "`n== DONE =="
Write-Host "Run app: php artisan serve"
Write-Host "Open: http://127.0.0.1:8000"
