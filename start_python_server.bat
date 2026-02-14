@echo off
echo 🚀 啟動 VT Website Python 版本...
echo 📦 檢查 Python 套件...

python -c "import flask, mysql.connector, requests" 2>nul
if %errorlevel% neq 0 (
    echo 🔧 安裝必要套件...
    pip install flask mysql-connector-python requests
    if %errorlevel% neq 0 (
        echo ❌ 安裝失敗。請手動執行: pip install flask mysql-connector-python requests
        pause
        exit /b 1
    )
)

echo ✅ 套件已安裝
echo 📡 啟動 Flask 伺服器...
echo 🌐 網站將運行在: http://127.0.0.1:5000
python app.py