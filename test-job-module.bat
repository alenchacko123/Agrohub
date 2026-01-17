@echo off
echo ===================================
echo  AgroHub Job Module - Quick Test
echo ===================================
echo.
echo Starting XAMPP services...
echo.

REM Check if XAMPP is installed
if not exist "C:\xampp\mysql\bin\mysql.exe" (
    echo ERROR: XAMPP not found in C:\xampp\
    echo Please make sure XAMPP is installed.
    pause
    exit /b
)

echo Testing database connection...
C:\xampp\mysql\bin\mysql.exe -u root -e "SELECT 'Database OK' as Status;" agrohub 2>nul
if errorlevel 1 (
    echo ERROR: Cannot connect to database
    echo Please make sure MySQL is running in XAMPP
    pause
    exit /b
) else (
    echo [OK] Database connection successful
)

echo.
echo Checking tables...
C:\xampp\mysql\bin\mysql.exe -u root -e "SELECT COUNT(*) as table_count FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'agrohub';" agrohub
echo.

echo ===================================
echo  Opening pages in your browser...
echo ===================================
echo.

REM Open navigation hub
start http://localhost/Agrohub/job-module-nav.html

echo.
echo Pages opened! Check your browser.
echo.
echo Quick Links:
echo - Navigation Hub: http://localhost/Agrohub/job-module-nav.html
echo - System Test: http://localhost/Agrohub/system-test.php
echo - Worker Signup: http://localhost/Agrohub/signup-worker.html
echo - Worker Login: http://localhost/Agrohub/login-worker.html
echo - Job Portal: http://localhost/Agrohub/job-portal.html
echo.
pause
