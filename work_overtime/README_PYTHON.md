# 倒計時器 - Python 版本

這個版本使用 Python Flask 替代了原來的 PHP CGI 伺服器，提供更穩定和易於管理的後端服務。

## 功能特點

- ✅ 使用 Python Flask 作為後端伺服器
- ✅ 支援倒計時設定儲存和載入
- ✅ 支援暫停和加時功能
- ✅ 自動 CORS 支援
- ✅ JSON 資料持久化

## 快速開始

### 1. 安裝依賴

確保您已安裝 Python 3.x，然後安裝 Flask：

```bash
pip install flask
```

### 2. 啟動伺服器

#### 方式一：使用批次檔案（推薦）
雙擊 `start_server.bat` 檔案，它會自動檢查並安裝 Flask，然後啟動伺服器。

#### 方式二：手動啟動
```bash
python countdown_server.py
```

伺服器將運行在 `http://127.0.0.1:5000`

### 3. 開啟應用

- **倒計時頁面**：開啟 `countdown.html`
- **控制面板**：開啟 `countdown-control.html`

## API 端點

- `GET /get-countdown` - 獲取倒計時設定
- `POST /save-countdown` - 儲存倒計時設定

## 資料儲存

設定會儲存在 `countdown-data.json` 檔案中，與原 PHP 版本相容。

## 故障排除

### 埠被佔用
如果 5000 埠被佔用，可以修改 `countdown_server.py` 中的埠號：

```python
app.run(host='127.0.0.1', port=8000, debug=True)  # 改為 8000 或其他埠
```

### Flask 安裝失敗
如果 `pip install flask` 失敗，請嘗試：

```bash
python -m pip install --upgrade pip
pip install flask
```

或使用系統套件管理器：

```bash
# Ubuntu/Debian
sudo apt install python3-flask

# macOS
brew install flask
```

## 從 PHP 版本遷移

這個 Python 版本完全相容原 PHP 版本的資料格式和前端功能。您可以直接使用現有的 `countdown-data.json` 檔案。