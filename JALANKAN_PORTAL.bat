@echo off
setlocal enabledelayedexpansion
title NEXUS CAMPUS PORTAL - LAUNCHER
color 0B

echo ======================================================================
echo          * NEXUS CAMPUS PORTAL - ONE-CLICK LAUNCHER *
echo ======================================================================
echo.

:: 1. Verifikasi Database MySQL XAMPP
set "MYSQL_CMD=C:\xampp\mysql\bin\mysql.exe"
if exist "%MYSQL_CMD%" (
    echo [OK] MySQL XAMPP terdeteksi. Memastikan database aktif...
    "%MYSQL_CMD%" -u root -e "SHOW DATABASES LIKE 'nexus_campus';" > "%temp%\chk_db.txt" 2>nul
    findstr /i "nexus_campus" "%temp%\chk_db.txt" >nul 2>nul
    if !errorlevel! equ 0 (
        echo [OK] Database 'nexus_campus' sudah siap dan terisi data!
    ) else (
        echo [INFO] Mengimpor schema database/schema.sql ke MySQL...
        "%MYSQL_CMD%" -u root < "%~dp0database\schema.sql" 2>nul
        if !errorlevel! equ 0 (
            echo [OK] Database 'nexus_campus' berhasil diimpor!
        ) else (
            echo [WARNING] Pastikan service MySQL di XAMPP Control Panel sudah START.
        )
    )
    if exist "%temp%\chk_db.txt" del "%temp%\chk_db.txt"
)

:: 2. Tentukan Mode Jalankan (Apache port 8080 vs PHP Server port 8000)
netstat -ano | findstr /R /C:":8080 .*LISTENING" >nul 2>nul
if !errorlevel! equ 0 (
    echo.
    echo ======================================================================
    echo   [INFO] Apache XAMPP aktif di Port 8080!
    echo   Membuka portal via Apache: http://localhost:8080/kampus-portal/
    echo ======================================================================
    start "" "http://localhost:8080/kampus-portal/"
    echo.
    echo Tekan tombol apa saja untuk menutup launcher ini...
    pause >nul
    exit /b
)

:: 3. Fallback: PHP Built-in Server Port 8000
set "PHP_CMD=C:\xampp\php\php.exe"
if not exist "%PHP_CMD%" (
    where php >nul 2>nul
    if !errorlevel! equ 0 set "PHP_CMD=php"
)

if "%PHP_CMD%"=="" (
    color 0C
    echo [ERROR] PHP tidak ditemukan di sistem!
    pause
    exit /b
)

echo.
echo ======================================================================
echo   Menjalankan PHP Built-in Server di: http://localhost:8000
echo   Browser akan terbuka otomatis...
echo   (Tekan CTRL+C untuk mematikan server)
echo ======================================================================
echo.

start "" cmd /c "timeout /t 2 /nobreak >nul & start http://localhost:8000"
cd /d "%~dp0"
"%PHP_CMD%" -S localhost:8000
