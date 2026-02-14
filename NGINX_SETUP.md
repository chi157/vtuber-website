# Nginx PHP 配置指南

## 📋 在你的伺服器上需要做的事

### 1. 安裝 PHP 和 PHP-FPM（如果還沒裝）

```bash
# Ubuntu/Debian
sudo apt update
sudo apt install php php-fpm php-mysql php-mbstring php-xml php-gd

# 檢查 PHP 版本
php --version

# 確認 PHP-FPM 運行中
sudo systemctl status php8.1-fpm  # 版本號可能不同，用你安裝的版本
```

### 2. 修改 Nginx 配置

找到你的 nginx 配置檔（應該在 `deploy/nginx-vtwebsite.conf`），加入 PHP 支援：

```nginx
server {
    listen 80;
    server_name vtwebsite.chi157.com;
    
    # 網站根目錄
    root /path/to/vt-website-main;
    index index.html index.php;
    
    # 靜態檔案
    location / {
        try_files $uri $uri/ =404;
    }
    
    # PHP 處理
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;  # 根據你的 PHP 版本調整
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    # 禁止訪問 uploads 資料夾中的 .php 檔案（安全性）
    location ~* ^/uploads/.*\.(php|php3|php4|php5|phtml)$ {
        deny all;
    }
    
    # 禁止訪問 backend 資料夾（安全性）
    location ~ ^/backend/ {
        deny all;
    }
    
    # SSL 配置（如果有的話）
    # listen 443 ssl;
    # ssl_certificate /path/to/cert.pem;
    # ssl_certificate_key /path/to/key.pem;
}
```

### 3. 測試 Nginx 配置

```bash
# 測試配置是否正確
sudo nginx -t

# 重新載入 Nginx
sudo systemctl reload nginx
```

### 4. 設定檔案權限

```bash
# 切換到網站目錄
cd /path/to/vt-website-main

# 設定 uploads 資料夾權限
sudo mkdir -p uploads
sudo chown -R www-data:www-data uploads
sudo chmod 755 uploads

# 如果需要，調整整個網站的擁有者
sudo chown -R www-data:www-data .
```

### 5. 設定資料庫

在你的 MySQL 伺服器執行：

```bash
# 登入 MySQL
mysql -u root -p

# 執行 SQL 檔案
source /path/to/vt-website-main/backend/database.sql;

# 或直接貼上 SQL 內容
```

### 6. 修改資料庫連線資訊

編輯 `backend/config.php`：

```php
define('DB_HOST', 'localhost');     // 你的資料庫主機
define('DB_NAME', 'vt_website');    // 資料庫名稱
define('DB_USER', 'your_db_user');  // 你的資料庫使用者
define('DB_PASS', 'your_db_pass');  // 你的資料庫密碼
```

### 7. 測試 PHP 是否正常運作

創建測試檔案：

```bash
echo "<?php phpinfo(); ?>" > /path/to/vt-website-main/test.php
```

然後訪問：
```
https://vtwebsite.chi157.com/test.php
```

如果看到 PHP 資訊頁面就成功了！記得刪除測試檔案：
```bash
rm /path/to/vt-website-main/test.php
```

### 8. 測試預購系統

訪問：
- 商品頁面：`https://vtwebsite.chi157.com/keychain.html`
- 登入頁面：`https://vtwebsite.chi157.com/login.php`
- 管理後台：`https://vtwebsite.chi157.com/admin.php`

### 9. 安全性檢查清單

✅ 修改管理員密碼
✅ 確認 `backend/` 資料夾無法從外部訪問
✅ 確認 `uploads/` 資料夾無法執行 PHP
✅ 啟用 HTTPS（SSL）
✅ 定期備份資料庫

## 🔧 常見問題

**Q: 404 Not Found 訪問 .php 檔案？**
A: 檢查 nginx 是否正確配置 PHP-FPM

**Q: 500 Internal Server Error？**
A: 查看 nginx 錯誤日誌：
```bash
sudo tail -f /var/log/nginx/error.log
```

**Q: 無法上傳檔案？**
A: 檢查 uploads 資料夾權限和 PHP upload_max_filesize 設定

**Q: 資料庫連線失敗？**
A: 檢查 backend/config.php 的資料庫連線設定

## 📞 需要協助？

如果遇到問題，提供以下資訊：
1. Nginx 錯誤日誌
2. PHP 版本
3. 錯誤訊息截圖
