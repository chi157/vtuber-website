@echo off
echo 🚀 啟動 VT Website Python 版本 (生產環境)...
echo 📦 檢查 Python 套件...

python -c "import flask, mysql.connector, requests, waitress" 2>nul
if %errorlevel% neq 0 (
    echo 🔧 安裝必要套件...
    pip install flask mysql-connector-python requests waitress
    if %errorlevel% neq 0 (
        echo ❌ 安裝失敗。請手動執行: pip install flask mysql-connector-python requests waitress
        pause
        exit /b 1
    )
)

echo ✅ 套件已安裝

REM 建立日誌目錄
if not exist "logs" mkdir logs

echo 📡 啟動 Waitress 伺服器...
echo 🌐 應用將運行在: http://127.0.0.1:8000
echo 📝 日誌檔案: logs/waitress.log

waitress-serve --host 127.0.0.1 --port 8000 --threads 4 app:application > logs/waitress.log 2>&1