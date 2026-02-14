# Nginx + Flask 配置指南

## 📋 概述

這個指南說明如何將 Python Flask 應用與 Nginx 搭配使用，提供生產環境的部署方案。

## 🏗️ 架構

```
Internet → Nginx (Port 80) → Waitress/Gunicorn (Port 8000) → Flask App
```

- **Nginx**: 處理靜態文件、SSL、負載平衡
- **Waitress** (Windows): WSGI 伺服器，運行 Flask 應用
- **Gunicorn** (Linux/Mac): WSGI 伺服器，運行 Flask 應用
- **Flask**: Web 應用框架

## ⚙️ 配置步驟

### 1. 安裝依賴

#### Windows
```bash
pip install flask mysql-connector-python requests waitress
```

#### Linux/Mac
```bash
pip install flask mysql-connector-python requests gunicorn
```

#### Nginx 安裝
```bash
# Ubuntu/Debian:
sudo apt install nginx

# CentOS/RHEL:
sudo yum install nginx

# Windows: 下載並安裝 Nginx for Windows
```

### 2. 配置 Nginx

#### Linux
```bash
# 複製配置
sudo cp deploy/nginx-flask.conf /etc/nginx/sites-available/vtwebsite

# 建立符號連結
sudo ln -s /etc/nginx/sites-available/vtwebsite /etc/nginx/sites-enabled/

# 測試配置
sudo nginx -t

# 重新載入
sudo systemctl reload nginx
```

#### Windows
1. 開啟 `nginx.conf` (通常在 `C:\nginx\conf\nginx.conf`)
2. 在 `http` 區塊中添加 `deploy/nginx-flask.conf` 的內容
3. 重新啟動 Nginx

### 2.5 測試 nginx 配置

在啟動 nginx 之前，先測試配置是否正確：

```bash
# Windows
deploy\test-nginx.bat

# 或手動測試
nginx -t -c deploy/nginx-flask.conf
```

### 3. 啟動 Flask 應用

#### Windows (使用 Waitress)
```bash
# 使用提供的腳本
start_production_server.bat

# 或手動啟動
waitress-serve --host 127.0.0.1 --port 8000 app:application
```

#### Linux/Mac (使用 Gunicorn)
```bash
# 使用 Gunicorn
gunicorn --bind 127.0.0.1:8000 --workers 4 app:application

# 或使用 Systemd 服務
sudo cp deploy/vtwebsite.service /etc/systemd/system/
sudo systemctl enable vtwebsite
sudo systemctl start vtwebsite
```

### 4. 驗證部署

```bash
# 運行完整部署檢查 (推薦)
deploy\check-deployment.bat

# 或手動檢查各組件
# 檢查 Nginx 狀態
sudo systemctl status nginx

# 檢查 Gunicorn 進程
ps aux | grep gunicorn

# 測試網站
curl http://localhost
```

## 🔧 配置說明

### Nginx 配置詳解

```nginx
server {
    listen 80;
    server_name vtwebsite.chi157.com;

    # 靜態檔案 - 直接由 Nginx 處理，提高效能
    location /static/ {
        alias /path/to/your/static/files/;
        expires 30d;  # 快取 30 天
        add_header Cache-Control "public";
    }

    # 動態內容 - 代理到 Flask
    location / {
        proxy_pass http://127.0.0.1:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

### Gunicorn 配置

```bash
# 基本啟動
gunicorn --bind 127.0.0.1:8000 app:application

# 生產環境建議
gunicorn \
    --bind 127.0.0.1:8000 \
    --workers 4 \
    --worker-class sync \
    --max-requests 1000 \
    --timeout 30 \
    --access-logfile logs/access.log \
    --error-logfile logs/error.log \
    app:application
```

## 🔒 安全性配置

### 1. SSL/TLS (HTTPS)

```nginx
server {
    listen 443 ssl http2;
    server_name vtwebsite.chi157.com;

    ssl_certificate /path/to/your/certificate.crt;
    ssl_certificate_key /path/to/your/private.key;

    # SSL 配置
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES128-GCM-SHA256:ECDHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;

    # 其餘配置與 HTTP 相同
    location /static/ {
        # ...
    }

    location / {
        # ...
    }
}

# HTTP 重定向到 HTTPS
server {
    listen 80;
    server_name vtwebsite.chi157.com;
    return 301 https://$server_name$request_uri;
}
```

### 2. 防火牆

```bash
# 只允許 80 和 443 埠
sudo ufw allow 80
sudo ufw allow 443
sudo ufw --force enable
```

### 3. 檔案權限

```bash
# 設定適當的權限
sudo chown -R www-data:www-data /var/www/vt-website-main
sudo chmod -R 755 /var/www/vt-website-main
sudo chmod -R 777 /var/www/vt-website-main/uploads  # 上傳目錄
```

## 📊 效能優化

### 1. Gunicorn 工作進程數

```bash
# 計算公式: (CPU 核心數 * 2) + 1
# 例如 4 核心 CPU: --workers 9
gunicorn --workers 9 --bind 127.0.0.1:8000 app:application
```

### 2. Nginx 快取

```nginx
# 靜態檔案快取
location ~* \.(css|js|png|jpg|jpeg|gif|svg|ico|webp|woff|woff2|ttf|eot)$ {
    expires 1y;
    add_header Cache-Control "public, immutable";
}

# API 回應快取
location /api/ {
    proxy_cache my_cache;
    proxy_cache_valid 200 10m;
    proxy_cache_valid 404 1m;
}
```

### 3. 壓縮

```nginx
# 啟用 gzip 壓縮
gzip on;
gzip_vary on;
gzip_min_length 1024;
gzip_types text/plain text/css text/xml text/javascript application/javascript application/xml+rss application/json;
```

## 🔍 故障排除

### 常見問題

#### 1. 502 Bad Gateway
```
原因: Flask 應用沒有運行
解決: 檢查 Gunicorn 狀態，重新啟動應用
```

#### 2. 靜態檔案 404
```
原因: Nginx 路徑配置錯誤
解決: 檢查 alias 路徑和權限
```

#### 3. 記憶體不足
```
原因: 太多工作進程
解決: 減少 --workers 數量
```

### 日誌檢查

```bash
# Nginx 日誌
sudo tail -f /var/log/nginx/error.log
sudo tail -f /var/log/nginx/access.log

# Gunicorn 日誌
tail -f logs/error.log
tail -f logs/access.log
```

### 測試命令

```bash
# 測試 Nginx 配置
sudo nginx -t

# 測試 Flask 應用
curl http://127.0.0.1:8000

# 測試完整流程
curl -I http://your-domain.com
```

## 📚 進階配置

### 負載平衡

```nginx
upstream flask_app {
    server 127.0.0.1:8000;
    server 127.0.0.1:8001;
    server 127.0.0.1:8002;
}

server {
    location / {
        proxy_pass http://flask_app;
    }
}
```

### 監控

```bash
# 安裝監控工具
pip install gunicorn[gevent]  # 或使用其他 worker 類型

# 啟動時添加監控
gunicorn --statsd-host localhost:8125 --bind 127.0.0.1:8000 app:application
```

## 🎯 總結

這種 Nginx + Gunicorn + Flask 的架構提供了：

- ✅ 高效能靜態檔案處理
- ✅ SSL/TLS 支援
- ✅ 負載平衡和擴展性
- ✅ 安全性強化
- ✅ 生產環境就緒

按照此指南配置，你將擁有一個穩定且高效的生產環境部署。