@echo off
REM ============================================================
REM  AKHLAK360 - Setup Script (Windows / XAMPP / Laragon)
REM  Mengotomatisasi: cek MySQL, buat DB, import schema + seed
REM ============================================================
setlocal enabledelayedexpansion

REM Default config (bisa di-override via env var)
if "%DB_HOST%"=="" set DB_HOST=localhost
if "%DB_PORT%"=="" set DB_PORT=3307
if "%DB_USER%"=="" set DB_USER=root
if "%DB_NAME%"=="" set DB_NAME=akhlak360

REM Cari mysql.exe di path umum XAMPP/Laragon/MAMP
set MYSQL_BIN=mysql
where %MYSQL_BIN% >nul 2>&1
if %errorlevel% neq 0 (
  if exist "C:\xampp\mysql\bin\mysql.exe" (
    set "MYSQL_BIN=C:\xampp\mysql\bin\mysql.exe"
  ) else if exist "C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysql.exe" (
    set "MYSQL_BIN=C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysql.exe"
  ) else (
    for /f "delims=" %%i in ('dir /b /s "C:\laragon\bin\mysql\*\bin\mysql.exe" 2^>nul') do set "MYSQL_BIN=%%i"
  )
  if "!MYSQL_BIN!"=="mysql" (
    echo [ERROR] mysql.exe tidak ditemukan di PATH.
    echo   - Aktifkan MySQL di XAMPP/Laragon control panel
    echo   - Atau tambahkan folder bin MySQL ke PATH
    echo   - Atau set MYSQL_BIN langsung: set MYSQL_BIN=C:\path\to\mysql.exe
    pause
    exit /b 1
  )
)

echo.
echo ============================================================
echo   AKHLAK360 - Setup Script (Windows)
echo   MySQL: %DB_HOST%:%DB_PORT% as %DB_USER%
echo   Using: %MYSQL_BIN%
echo ============================================================
echo.

REM Cek file SQL di folder script ini
set "SCRIPT_DIR=%~dp0"
set "SCHEMA_FILE=%SCRIPT_DIR%akhlak360.sql"
set "SEED_FILE=%SCRIPT_DIR%seed_data.sql"

if not exist "%SCHEMA_FILE%" (
  echo [ERROR] File %SCHEMA_FILE% tidak ditemukan.
  pause
  exit /b 1
)
if not exist "%SEED_FILE%" (
  echo [ERROR] File %SEED_FILE% tidak ditemukan.
  pause
  exit /b 1
)

REM Prompt password
if "%DB_PASS%"=="" (
  set /p DB_PASS="Masukkan password MySQL untuk user '%DB_USER%' (Enter jika kosong): "
)

REM Step 1: Cek koneksi
echo.
echo [1/4] Cek koneksi MySQL di %DB_HOST%:%DB_PORT%...
"%MYSQL_BIN%" -h %DB_HOST% -P %DB_PORT% -u %DB_USER% -p%DB_PASS% -e "SELECT 1" >nul 2>&1
if %errorlevel% neq 0 (
  echo   [FAIL] Tidak bisa konek ke MySQL.
  echo     - Pastikan MySQL berjalan di XAMPP/Laragon control panel
  echo     - Cek port: %DB_PORT% ^| Set DB_PORT=3306 jika MySQL di port 3306
  pause
  exit /b 1
)
echo   [OK] Koneksi MySQL berhasil

REM Step 2: Buat database
echo.
echo [2/4] Buat database '%DB_NAME%' jika belum ada...
"%MYSQL_BIN%" -h %DB_HOST% -P %DB_PORT% -u %DB_USER% -p%DB_PASS% -e "CREATE DATABASE IF NOT EXISTS %DB_NAME% CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
if %errorlevel% neq 0 (
  echo   [FAIL] Gagal membuat database.
  pause
  exit /b 1
)
echo   [OK] Database '%DB_NAME%' siap

REM Step 3: Import schema
echo.
echo [3/4] Import schema dari akhlak360.sql...
"%MYSQL_BIN%" -h %DB_HOST% -P %DB_PORT% -u %DB_USER% -p%DB_PASS% %DB_NAME% < "%SCHEMA_FILE%"
if %errorlevel% neq 0 (
  echo   [FAIL] Gagal import schema.
  pause
  exit /b 1
)
echo   [OK] Schema di-import (12 tabel + seed master)

REM Step 4: Import seed
echo.
echo [4/4] Import seed data dari seed_data.sql...
"%MYSQL_BIN%" -h %DB_HOST% -P %DB_PORT% -u %DB_USER% -p%DB_PASS% %DB_NAME% < "%SEED_FILE%"
if %errorlevel% neq 0 (
  echo   [FAIL] Gagal import seed data.
  pause
  exit /b 1
)
echo   [OK] Seed data di-import (16 user demo)

echo.
echo ============================================================
echo   [SUCCESS] SETUP SELESAI!
echo ============================================================
echo   Database : %DB_NAME%
echo.
echo   Login Demo (password: password123):
echo     Karyawan : ahmad.fauzi@energinusantara.co.id
echo     Manager  : hendra@energinusantara.co.id
echo     Admin HRD: admin.hrd@energinusantara.co.id
echo     Admin IT : admin.it@energinusantara.co.id
echo.
echo   Selanjutnya:
echo     1. Pastikan folder ini ada di web root
echo        XAMPP: C:\xampp\htdocs\akhlak360-php
echo        Laragon: C:\laragon\www\akhlak360-php
echo     2. Buka http://localhost/akhlak360-php/dbtest.php untuk verifikasi
echo     3. Buka http://localhost/akhlak360-php/ untuk login
echo ============================================================
echo.
pause
