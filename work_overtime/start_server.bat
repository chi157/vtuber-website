@echo off
echo 🚀 啟動倒計時伺服器...
echo 📦 檢查 Flask 是否已安裝...

python -c "import flask" 2>nul
if %errorlevel% neq 0 (
    echo 🔧 安裝 Flask...
    pip install flask
    if %errorlevel% neq 0 (
        echo ❌ 安裝 Flask 失敗。請手動執行: pip install flask
        pause
        exit /b 1
    )
)

echo ✅ Flask 已安裝
echo 📡 啟動伺服器...
python countdown_server.py