@echo off
echo ========================================
echo  AgroHub Equipment Listing Setup
echo ========================================
echo.
echo This script will help you set up the equipment table in your database.
echo.
echo Please make sure XAMPP MySQL is running!
echo.
pause

echo.
echo Opening phpMyAdmin in your browser...
echo.
start http://localhost/phpmyadmin

echo.
echo.
echo ========================================
echo NEXT STEPS:
echo ========================================
echo 1. In phpMyAdmin, select your 'agrohub' database
echo 2. Click on the "SQL" tab
echo 3. Copy and paste the SQL from: php/create_equipment_table.sql
echo 4. Click "Go" to execute
echo.
echo OR you can run this command in Command Prompt:
echo mysql -u root -p agrohub ^< "%~dp0php\create_equipment_table.sql"
echo.
echo After creating the table, you can:
echo - Add equipment from Owner Dashboard
echo - View equipment on Farmer Dashboard (Rent Equipment page)
echo.
pause
