@echo off
echo ========================================
echo VT Website Production Deployment Check
echo ========================================
echo.

REM Check Python
echo [1/6] Checking Python...
python --version >nul 2>nul
if %errorlevel% neq 0 (
    echo ERROR: Python is not installed or not in PATH
    goto :error
)
python --version
echo.

REM Check required packages
echo [2/6] Checking Python packages...
python -c "import flask, mysql.connector, waitress" >nul 2>nul
if %errorlevel% neq 0 (
    echo ERROR: Required Python packages are not installed
    echo Run: pip install flask mysql-connector-python requests waitress
    goto :error
)
echo All required packages are installed.
echo.

REM Check Flask app
echo [3/6] Testing Flask app import...
python -c "import app; print('Flask app imported successfully')" >nul 2>nul
if %errorlevel% neq 0 (
    echo ERROR: Cannot import Flask app
    goto :error
)
echo Flask app imported successfully.
echo.

REM Check nginx
echo [4/6] Checking nginx...
where nginx >nul 2>nul
if %errorlevel% neq 0 (
    echo ERROR: nginx is not installed or not in PATH
    goto :error
)
nginx -v
echo.

REM Test nginx config
echo [5/6] Testing nginx configuration...
nginx -t -c "%~dp0nginx-flask.conf" >nul 2>nul
if %errorlevel% neq 0 (
    echo ERROR: nginx configuration is invalid
    goto :error
)
echo nginx configuration is valid.
echo.

REM Check database connection (optional)
echo [6/6] Checking database connection...
python -c "
import mysql.connector
try:
    conn = mysql.connector.connect(host='localhost', database='vt_website', user='root', password='123456789', charset='utf8mb4')
    conn.close()
    print('Database connection successful')
except Exception as e:
    print('WARNING: Database connection failed -', str(e))
    print('Make sure MySQL is running and config is correct')
"
echo.

echo ========================================
echo All checks passed! Ready for deployment.
echo ========================================
echo.
echo Next steps:
echo 1. Start Flask app: start_production_server.bat
echo 2. Start nginx: nginx -c deploy/nginx-flask.conf
echo 3. Test: http://vtwebsite.chi157.com
echo.
pause
exit /b 0

:error
echo.
echo ========================================
echo DEPLOYMENT CHECK FAILED
echo ========================================
echo Please fix the errors above and try again.
echo.
pause
exit /b 1