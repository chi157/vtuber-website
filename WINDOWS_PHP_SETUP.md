# Windows PHP 安裝與配置指南

## 📦 方法 1：手動安裝 PHP（推薦）

### 1. 下載 PHP

1. 前往 PHP 官方下載頁面：
   https://windows.php.net/download/

2. 下載 **Thread Safe** 版本（例如：`php-8.2.x-Win32-vs16-x64.zip`）

3. 解壓縮到 `C:\php`

### 2. 配置 PHP

```powershell
# 複製配置檔
Copy-Item C:\php\php.ini-development C:\php\php.ini

# 編輯 php.ini，啟用以下擴充功能（移除前面的 ;）
# extension=mysqli
# extension=pdo_mysql
# extension=mbstring
# extension=gd
# extension=fileinfo
```

### 3. 設定環境變數

```powershell
# 將 PHP 加入 PATH
[Environment]::SetEnvironmentVariable("Path", $env:Path + ";C:\php", "Machine")

# 重新開啟 PowerShell 後測試
php --version
```

### 4. 測試 PHP 是否正常

```powershell
# 創建測試檔案
cd D:\chi157\vt-website-main\vt-website-main
echo "<?php phpinfo(); ?>" > test.php

# 啟動 PHP 內建伺服器測試
php -S localhost:9000
# 然後訪問 http://localhost:9000/test.php
```

---

## 🔧 方法 2：使用 XAMPP（更簡單）

### 1. 下載 XAMPP

前往：https://www.apachefriends.org/download.html

下載並安裝 XAMPP（包含 PHP + MySQL + phpMyAdmin）

### 2. 啟動服務

1. 開啟 XAMPP Control Panel
2. 啟動 MySQL
3. 不需要啟動 Apache（因為你已經有 nginx）

### 3. 使用 XAMPP 的 PHP

```powershell
# 假設 XAMPP 安裝在 C:\xampp
# 設定環境變數指向 XAMPP 的 PHP
[Environment]::SetEnvironmentVariable("Path", $env:Path + ";C:\xampp\php", "Machine")

# 重新開啟 PowerShell 後測試
php --version
```

---

## 🌐 配置 Nginx 支援 PHP

### 1. 啟動 PHP-CGI

在背景執行 PHP-CGI：

```powershell
# 方法 1：使用 PHP 內建 CGI
Start-Process -FilePath "C:\php\php-cgi.exe" -ArgumentList "-b 127.0.0.1:9000" -WindowStyle Hidden

# 或使用 XAMPP 的 PHP
Start-Process -FilePath "C:\xampp\php\php-cgi.exe" -ArgumentList "-b 127.0.0.1:9000" -WindowStyle Hidden
```

### 2. 修改 Nginx 配置

編輯你的 nginx 配置檔（`deploy/nginx-vtwebsite.conf`）：

```nginx
server {
    listen 80;
    server_name vtwebsite.chi157.com;
    
    root D:/chi157/vt-website-main/vt-website-main;
    index index.html index.php;
    
    # 靜態檔案
    location / {
        try_files $uri $uri/ =404;
    }
    
    # PHP 處理（Windows 版本）
    location ~ \.php$ {
        fastcgi_pass   127.0.0.1:9000;
        fastcgi_index  index.php;
        fastcgi_param  SCRIPT_FILENAME  $document_root$fastcgi_script_name;
        include        fastcgi_params;
    }
    
    # 安全設定
    location ~ ^/backend/ {
        deny all;
    }
    
    location ~* ^/uploads/.*\.(php|php3|php4|php5|phtml)$ {
        deny all;
    }
}
```

### 3. 重新載入 Nginx

```powershell
# 測試配置
nginx -t

# 重新載入（如果 nginx 是服務）
nginx -s reload

# 或重啟 nginx
Stop-Process -Name nginx -Force
Start-Process nginx
```

---

## 💾 配置 MySQL

### 如果使用 XAMPP：

1. 使用 XAMPP Control Panel 啟動 MySQL
2. 訪問 http://localhost/phpmyadmin
3. 匯入 `backend/database.sql`

### 如果獨立安裝 MySQL：

1. 下載：https://dev.mysql.com/downloads/installer/
2. 安裝 MySQL Server
3. 使用 MySQL Workbench 或命令列匯入資料庫

---

## 🚀 啟動腳本（自動化）

創建 `start-php-cgi.ps1`：

```powershell
# 啟動 PHP-CGI
$phpPath = "C:\php\php-cgi.exe"  # 或 C:\xampp\php\php-cgi.exe
$process = Get-Process php-cgi -ErrorAction SilentlyContinue

if ($null -eq $process) {
    Write-Host "Starting PHP-CGI..."
    Start-Process -FilePath $phpPath -ArgumentList "-b 127.0.0.1:9000" -WindowStyle Hidden
    Write-Host "PHP-CGI started on port 9000"
} else {
    Write-Host "PHP-CGI is already running"
}
```

執行：
```powershell
powershell -ExecutionPolicy Bypass -File start-php-cgi.ps1
```

---

## ✅ 測試清單

1. ✅ PHP 已安裝：`php --version`
2. ✅ PHP-CGI 運行中：`netstat -ano | findstr :9000`
3. ✅ Nginx 配置正確：`nginx -t`
4. ✅ MySQL 運行中
5. ✅ 訪問測試頁面：`http://vtwebsite.chi157.com/test.php`

---

## 🔍 問題排查

**Q: PHP-CGI 無法啟動？**
```powershell
# 檢查是否已在運行
Get-Process php-cgi

# 檢查端口是否被占用
netstat -ano | findstr :9000
```

**Q: Nginx 顯示 502 Bad Gateway？**
- 確認 PHP-CGI 正在運行
- 確認 nginx 配置中的 fastcgi_pass 地址正確

**Q: 無法連接資料庫？**
- 檢查 `backend/config.php` 的資料庫設定
- 確認 MySQL 服務正在運行
