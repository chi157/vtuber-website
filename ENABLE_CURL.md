# 啟用 PHP cURL 擴展

## Windows 系統

1. **找到 php.ini 文件**
   - 通常位於：`C:\php\php.ini` 或 PHP 安裝目錄
   - 或執行：`php --ini` 查看位置

2. **編輯 php.ini**
   - 找到這一行：`;extension=curl`
   - 移除前面的分號 `;` 變成：`extension=curl`
   - 如果找不到，直接添加：`extension=curl`

3. **重啟 Web 服務器**
   - 如果使用 Apache：重啟 Apache
   - 如果使用 nginx + php-cgi：重啟 PHP 進程
   - 如果使用 PHP 內建服務器：重啟服務器

4. **驗證是否成功**
   - 創建測試文件：`<?php phpinfo(); ?>`
   - 搜尋 "curl" 確認已啟用

## 快速檢查命令

在終端執行：
```powershell
php -m | Select-String curl
```

如果看到 "curl"，表示已啟用。
