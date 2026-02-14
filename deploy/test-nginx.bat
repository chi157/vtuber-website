@echo off
echo Testing nginx configuration...
echo.

REM Check if nginx is installed
where nginx >nul 2>nul
if %errorlevel% neq 0 (
    echo ERROR: nginx is not installed or not in PATH
    echo Please install nginx first
    pause
    exit /b 1
)

REM Test nginx configuration
nginx -t -c "%~dp0nginx-flask.conf"
if %errorlevel% neq 0 (
    echo ERROR: nginx configuration test failed
    pause
    exit /b 1
)

echo nginx configuration is valid!
echo.
echo To start nginx with this configuration:
echo nginx -c "%~dp0nginx-flask.conf"
echo.
echo To stop nginx:
echo nginx -s stop
echo.
pause