# VT Website - Python 版本

這個專案已經從 PHP 完全遷移到 Python Flask。

## 🚀 快速開始

### 選項 1: 開發環境 (推薦用於測試)

```bash
pip install flask mysql-connector-python requests
python app.py
```

### 選項 2: 生產環境 (使用 Nginx + Gunicorn)

#### 1. 安裝依賴
```bash
pip install flask mysql-connector-python requests gunicorn
```

#### 2. 設定 Nginx
將 `deploy/nginx-flask.conf` 複製到 nginx 配置目錄：

**Linux:**
```bash
sudo cp deploy/nginx-flask.conf /etc/nginx/sites-available/vtwebsite
sudo ln -s /etc/nginx/sites-available/vtwebsite /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

**Windows:**
將 `deploy/nginx-flask.conf` 的內容添加到你的 nginx 配置中。

#### 3. 啟動應用
```bash
# Windows
start_production_server.bat

# Linux
gunicorn --bind 127.0.0.1:8000 --workers 4 app:application
```

網站將運行在 `http://127.0.0.1:80` (通過 nginx)

## 📁 專案結構

```
vt-website-main/
├── app.py                      # 主應用程式
├── backend/
│   ├── config.local.py         # 本地配置
│   └── config.local.py         # 配置範例
├── templates/                  # HTML 模板
├── static/                     # 靜態文件
├── logs/                       # 應用日誌
├── deploy/
│   ├── nginx-flask.conf        # Nginx 配置 (Flask)
│   ├── nginx-php.conf          # Nginx 配置 (PHP)
│   └── nginx-vtwebsite.conf    # 基本 Nginx 配置
├── work_overtime/              # 倒計時功能
├── start_python_server.bat    # 開發環境啟動腳本
└── start_production_server.bat # 生產環境啟動腳本
```

## 📁 專案結構

```
vt-website-main/
├── app.py                 # 主應用程式
├── backend/
│   ├── config.local.py    # 本地配置
│   └── config.local.py    # 配置範例
├── templates/             # HTML 模板
├── static/                # 靜態文件
├── work_overtime/         # 倒計時功能
└── start_python_server.bat
```

## 🔧 已完成的遷移

### ✅ 已完成
- [x] 資料庫連線和配置
- [x] 用戶認證系統 (登入/註冊/登出)
- [x] Google OAuth 整合
- [x] 會話管理
- [x] 郵件驗證系統
- [x] 基本路由和模板
- [x] 倒計時功能 (已存在 Python 版本)

### 🔄 部分完成
- [x] 首頁模板
- [x] 登入/註冊模板
- [ ] 個人資料頁面
- [ ] 訂單管理
- [ ] 管理功能

### ❌ 待完成
- [ ] 完整的模板遷移
- [ ] 檔案上傳功能
- [ ] 完整的錯誤處理

## 🔑 主要變更

1. **後端**: PHP → Python Flask
2. **資料庫**: PDO → mysql-connector-python
3. **模板**: PHP 內嵌 → Jinja2
4. **會話**: PHP sessions → Flask sessions
5. **郵件**: PHPMailer → smtplib

## 🐛 已知問題

- 部分模板尚未完全遷移
- 檔案上傳功能尚未實作
- 管理功能尚未完成

## 📞 支援

如果遇到問題，請檢查：
1. Python 版本 (建議 3.8+)
2. 資料庫連線設定
3. Google OAuth 配置
4. SMTP 郵件設定