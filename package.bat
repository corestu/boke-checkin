@echo off
chcp 65001 >nul 2>&1
setlocal enabledelayedexpansion

echo.
echo ========================================
echo   Bo.ke Checkin - WordPress Plugin Packager
echo ========================================
echo.

REM Get current directory
set "PLUGIN_DIR=%~dp0"
set "PLUGIN_NAME=boke-checkin"
set "OUTPUT_FILE=%PLUGIN_DIR%..\%PLUGIN_NAME%.zip"

REM Delete old zip file if exists
if exist "%OUTPUT_FILE%" (
    echo [1/3] Removing old zip file...
    del "%OUTPUT_FILE%"
)

REM Create zip package using PowerShell
echo [2/3] Creating zip archive...
powershell -NoProfile -Command "Compress-Archive -Path '%PLUGIN_DIR%*' -DestinationPath '%OUTPUT_FILE%' -Force"

REM Check result
if %errorlevel% equ 0 (
    echo [3/3] Done!
    echo.
    echo ========================================
    echo   SUCCESS! Package created at:
    echo   %OUTPUT_FILE%
    echo ========================================
    echo.
    echo To install in WordPress:
    echo   1. Login to WordPress admin
    echo   2. Go to Plugins - Add New
    echo   3. Click "Upload Plugin"
    echo   4. Select "%PLUGIN_NAME%.zip"
    echo   5. Click "Install" then "Activate"
    echo.
) else (
    echo.
    echo ========================================
    echo   ERROR: Failed to create zip package
    echo ========================================
    echo.
)

echo Press any key to exit...
pause >nul
