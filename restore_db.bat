@echo off
setlocal enabledelayedexpansion
title Sinalhan Health Center - Secure Database Restore Tool
color 0b

echo ==============================================================
echo       SINALHAN HEALTH CENTER DATABASE RESTORATION UTILITY
echo ==============================================================
echo.
echo WARNING: This tool will overwrite the existing database content.
echo Make sure you have backed up any current data before proceeding.
echo.
echo ==============================================================
echo.

:: 1. Find mysql executable
set MYSQL_CMD=mysql
where mysql >nul 2>nul
if %ERRORLEVEL% neq 0 (
    if exist "C:\xampp\mysql\bin\mysql.exe" (
        set MYSQL_CMD="C:\xampp\mysql\bin\mysql.exe"
    ) else (
        echo [WARNING] MySQL command line client not found in PATH or standard XAMPP folder.
        echo Please enter the absolute path to mysql.exe, or press Ctrl+C to abort.
        set /p MYSQL_CMD="Path to mysql.exe: "
    )
)

:: 2. Input details
set /p DB_USER="Enter MySQL Username (default: root): " || set DB_USER=root
set /p DB_PASS="Enter MySQL Password (press Enter if empty): "

echo.
echo Drag and drop your backup .sql file here, then press Enter:
set /p SQL_FILE=""

:: Clean quotes from file path (if dragged and dropped)
set SQL_FILE=!SQL_FILE:"=!

if not exist "!SQL_FILE!" (
    echo.
    echo [ERROR] The specified SQL file does not exist: "!SQL_FILE!"
    goto end
)

echo.
echo [1/2] Preparing database connection...
if "%DB_PASS%"=="" (
    !MYSQL_CMD! -u %DB_USER% -e "CREATE DATABASE IF NOT EXISTS bhc_sinalhan_db;" >nul 2>&1
) else (
    !MYSQL_CMD! -u %DB_USER% -p%DB_PASS% -e "CREATE DATABASE IF NOT EXISTS bhc_sinalhan_db;" >nul 2>&1
)

if %ERRORLEVEL% neq 0 (
    echo [ERROR] Connection failed. Please check your username, password, or MySQL status.
    goto end
)

echo [2/2] Restoring backup dump file. Please wait, this may take a few moments...
if "%DB_PASS%"=="" (
    !MYSQL_CMD! -u %DB_USER% bhc_sinalhan_db < "!SQL_FILE!"
) else (
    !MYSQL_CMD! -u %DB_USER% -p%DB_PASS% bhc_sinalhan_db < "!SQL_FILE!"
)

if %ERRORLEVEL% equ 0 (
    echo.
    echo ==============================================================
    echo [SUCCESS] Database backup restored successfully.
    echo ==============================================================
) else (
    echo.
    echo [ERROR] Restore failed. The backup file might be corrupted or malformed.
)

:end
echo.
pause
