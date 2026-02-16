@echo off
title DeliveryV3 - One-Click Installer
color 0A

echo.
echo ========================================
echo   DeliveryV3 - One-Click Installer
echo ========================================
echo.
echo Starting installation process...
echo.

REM Check if running as Administrator
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo [ERROR] Please run this installer as Administrator!
    echo Right-click on install.bat and select "Run as administrator"
    echo.
    pause
    exit /b 1
)

echo [1/8] Checking prerequisites...
echo.

REM Check PHP
php --version >nul 2>&1
if %errorLevel% neq 0 (
    echo [ERROR] PHP is not installed or not in PATH!
    echo Please install XAMPP with PHP 8.2+ first.
    echo.
    pause
    exit /b 1
)
echo   - PHP found: OK

REM Check Composer
composer --version >nul 2>&1
if %errorLevel% neq 0 (
    echo [ERROR] Composer is not installed or not in PATH!
    echo Please install Composer from https://getcomposer.org
    echo.
    pause
    exit /b 1
)
echo   - Composer found: OK

REM Check Node.js
node --version >nul 2>&1
if %errorLevel% neq 0 (
    echo [ERROR] Node.js is not installed or not in PATH!
    echo Please install Node.js from https://nodejs.org
    echo.
    pause
    exit /b 1
)
echo   - Node.js found: OK

REM Check npm
npm --version >nul 2>&1
if %errorLevel% neq 0 (
    echo [ERROR] npm is not installed!
    echo Please install Node.js from https://nodejs.org
    echo.
    pause
    exit /b 1
)
echo   - npm found: OK

REM Check MySQL
mysql --version >nul 2>&1
if %errorLevel% neq 0 (
    echo [WARNING] MySQL CLI not found in PATH.
    echo Make sure XAMPP MySQL is running!
    echo You may need to create database manually.
    echo.
    timeout /t 3 >nul
) else (
    echo   - MySQL found: OK
)

echo.
echo [2/8] Installing Composer dependencies...
echo.
call composer install --no-interaction --prefer-dist --optimize-autoloader
if %errorLevel% neq 0 (
    echo [ERROR] Composer install failed!
    pause
    exit /b 1
)

echo.
echo [3/8] Installing npm dependencies...
echo.
call npm install
if %errorLevel% neq 0 (
    echo [ERROR] npm install failed!
    pause
    exit /b 1
)

echo.
echo [4/8] Setting up environment file...
echo.
if not exist .env (
    copy .env.example .env
    echo   - Created .env from .env.example
) else (
    echo   - .env already exists, skipping...
)

echo.
echo [5/8] Generating application key...
echo.
php artisan key:generate --force

echo.
echo [6/8] Creating database...
echo.
mysql -u root -e "CREATE DATABASE IF NOT EXISTS deliveryv3;" 2>nul
if %errorLevel% equ 0 (
    echo   - Database 'deliveryv3' created successfully!
) else (
    echo   - Could not auto-create database.
    echo   - Please create 'deliveryv3' database manually in phpMyAdmin.
    echo   - Press any key to continue...
    pause >nul
)

echo.
echo [7/8] Running database migrations...
echo.
php artisan migrate --force
if %errorLevel% neq 0 (
    echo [ERROR] Migration failed!
    echo Please check your database configuration in .env
    pause
    exit /b 1
)

echo.
echo Creating storage link...
php artisan storage:link

echo.
echo [8/8] Building frontend assets...
echo.
call npm run build
if %errorLevel% neq 0 (
    echo [ERROR] Build failed!
    pause
    exit /b 1
)

echo.
echo ========================================
echo   Installation Complete!
echo ========================================
echo.
echo Next steps:
echo   1. Update .env with your ORS_API_KEY
echo   2. Update WAREHOUSE_LAT and WAREHOUSE_LNG in .env
echo   3. Run: start_server.bat to start the application
echo.
echo Or manually run:
echo   php artisan serve
echo   php artisan queue:work
echo.
echo Press any key to exit...
pause >nul
