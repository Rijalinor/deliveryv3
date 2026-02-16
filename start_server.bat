@echo off
title DeliveryV3 - Server Starter
color 0B

echo.
echo ========================================
echo   DeliveryV3 - Starting Servers
echo ========================================
echo.

REM Check if .env exists
if not exist .env (
    echo [ERROR] .env file not found!
    echo Please run install.bat first.
    echo.
    pause
    exit /b 1
)

echo Starting application server and queue worker...
echo.
echo [INFO] Press Ctrl+C to stop all servers
echo.

REM Use PowerShell Start-Process to run in separate windows
powershell -Command "Start-Process cmd -ArgumentList '/k','cd /d %CD% && php artisan serve' -WindowStyle Normal"
timeout /t 2 >nul

powershell -Command "Start-Process cmd -ArgumentList '/k','cd /d %CD% && php artisan queue:work --tries=1' -WindowStyle Normal"
timeout /t 2 >nul

echo.
echo ========================================
echo   Servers Started!
echo ========================================
echo.
echo Application: http://127.0.0.1:8000
echo.
echo Two command windows have been opened:
echo   1. Application Server (php artisan serve)
echo   2. Queue Worker (php artisan queue:work)
echo.
echo Close those windows to stop the servers.
echo.
echo Opening browser in 3 seconds...
timeout /t 3 >nul

start http://127.0.0.1:8000/admin

echo.
echo Press any key to exit this window...
pause >nul
