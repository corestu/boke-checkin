@echo off
setlocal enabledelayedexpansion

echo.
echo ========================================
echo   Bo.ke Checkin - WordPress Plugin Packager
echo ========================================
echo.

set "PLUGIN_DIR=%~dp0"
set "PLUGIN_DIR=%PLUGIN_DIR:~0,-1%"
set "PLUGIN_NAME=boke-checkin"

for %%I in ("%PLUGIN_DIR%") do set "PARENT_DIR=%%~dpI"
set "PARENT_DIR=%PARENT_DIR:~0,-1%"

set "OUTPUT_PATH=%PARENT_DIR%\%PLUGIN_NAME%.zip"
set "STAGE_ROOT=%PLUGIN_DIR%\.pack-%PLUGIN_NAME%"
set "STAGE_PLUGIN_DIR=%STAGE_ROOT%\plugin\%PLUGIN_NAME%"

if exist "%OUTPUT_PATH%" del /f /q "%OUTPUT_PATH%"
if exist "%STAGE_ROOT%" rmdir /s /q "%STAGE_ROOT%"

mkdir "%STAGE_PLUGIN_DIR%" || exit /b 1

echo [1/3] Copying files...
robocopy "%PLUGIN_DIR%" "%STAGE_PLUGIN_DIR%" /E /XA:SH ^
    /XD ".git" ".github" ".idea" ".vscode" "node_modules" ".pack-*" ".claude" ".trae" ^
    /XF "*.zip" "package.bat" ".gitignore" "*.log" ".DS_Store" "Thumbs.db" ".*"

if %ERRORLEVEL% GTR 7 (
    echo robocopy failed with exit code %ERRORLEVEL%
    goto :cleanup_error
)

echo [2/3] Creating zip archive...
set "ZIP_SOURCE=%STAGE_ROOT%\plugin"
set "ZIP_DEST=%OUTPUT_PATH%"

powershell -NoProfile -ExecutionPolicy Bypass -Command ^
    "$ErrorActionPreference = 'Stop';" ^
    "Add-Type -AssemblyName System.IO.Compression;" ^
    "Add-Type -AssemblyName System.IO.Compression.FileSystem;" ^
    "$source = $env:ZIP_SOURCE;" ^
    "$dest = $env:ZIP_DEST;" ^
    "$encoding = [System.Text.Encoding]::UTF8;" ^
    "$stream = [System.IO.File]::Open($dest, [System.IO.FileMode]::CreateNew);" ^
    "try {" ^
    "    $zip = [System.IO.Compression.ZipArchive]::new($stream, [System.IO.Compression.ZipArchiveMode]::Create, $false, $encoding);" ^
    "    try {" ^
    "        Get-ChildItem -LiteralPath $source -Recurse -File | ForEach-Object {" ^
    "            $relative = $_.FullName.Substring($source.Length).TrimStart('\');" ^
    "            $entryName = $relative -replace '\\', '/';" ^
    "            [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, $_.FullName, $entryName, [System.IO.Compression.CompressionLevel]::Optimal) | Out-Null;" ^
    "        }" ^
    "    } finally {" ^
    "        $zip.Dispose();" ^
    "    }" ^
    "} finally {" ^
    "    $stream.Dispose();" ^
    "}"

if %ERRORLEVEL% NEQ 0 (
    echo zip failed with exit code %ERRORLEVEL%
    goto :cleanup_error
)

echo [3/3] Cleaning up...
rmdir /s /q "%STAGE_ROOT%"

echo.
echo ========================================
echo SUCCESS! Package created at:
echo %OUTPUT_PATH%
echo ========================================
echo.
endlocal
pause
exit /b 0

:cleanup_error
rmdir /s /q "%STAGE_ROOT%" >nul 2>nul
endlocal
pause
exit /b 1
