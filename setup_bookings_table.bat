@echo off
echo Creating bookings table in the database...
echo.

REM Update these if your MySQL installation is different
set MYSQL_PATH="C:\xampp\mysql\bin\mysql.exe"
set DB_NAME=agrohub
set DB_USER=root
set DB_PASS=

REM Run the SQL file
%MYSQL_PATH% -u %DB_USER% %DB_NAME% < sql\create_bookings_table.sql

if %ERRORLEVEL% EQU 0 (
    echo.
    echo ========================================
    echo SUCCESS! Bookings table created.
    echo ========================================
    echo.
) else (
    echo.
    echo ========================================
    echo ERROR! Failed to create bookings table.
    echo Please check your MySQL settings.
    echo ========================================
    echo.
)

pause
