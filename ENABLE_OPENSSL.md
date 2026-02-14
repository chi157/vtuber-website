# 啟用 PHP OpenSSL 擴展

## 問題
無法使用 HTTPS，錯誤：`Unable to find the wrapper "https"`

## 解決方案（Windows）

### 方法 1：修改 php.ini（推薦）

1. **找到 php.ini 文件位置**
   
   在 PowerShell 執行：
   ```powershell
   php --ini
   ```
   
   或查看常見位置：
   - `C:\php\php.ini`
   - `C:\xampp\php\php.ini`
   - `C:\wamp\bin\php\phpX.X.X\php.ini`

2. **編輯 php.ini**
   
   用記事本或 VS Code 打開 php.ini，找到以下行：
   
   ```ini
   ;extension=openssl
   ```
   
   移除前面的分號 `;` 改為：
   
   ```ini
   extension=openssl
   ```

3. **檢查 extension_dir**
   
   在 php.ini 中確認有這行（通常在文件前面）：
   
   ```ini
   extension_dir = "ext"
   ```
   
   或絕對路徑：
   ```ini
   extension_dir = "C:/php/ext"
   ```

4. **重啟 Web 服務器**
   
   - 如果使用 PHP 內建服務器：關閉並重新啟動
   - 如果使用 Apache：重啟 Apache
   - 如果使用 nginx + PHP-CGI：重啟 PHP 進程

### 方法 2：檢查 php.ini-development

如果找不到 php.ini，可能需要從範本創建：

```powershell
# 進入 PHP 安裝目錄
cd C:\php

# 複製範本
copy php.ini-development php.ini
```

然後按照方法 1 編輯。

## 驗證是否成功

在 PowerShell 執行：
```powershell
php -m | Select-String openssl
```

如果看到 "openssl"，表示成功啟用。

## 測試 HTTPS 支持

創建測試文件 `test-https.php`：
```php
<?php
$result = file_get_contents('https://www.google.com');
if ($result !== false) {
    echo "✅ HTTPS 可用！";
} else {
    echo "❌ HTTPS 仍然不可用";
}
?>
```

執行：
```powershell
php test-https.php
```

## 如果仍然失敗

可能還需要啟用這些擴展：
```ini
extension=openssl
extension=curl
extension=mysqli
```

## 重要提示

啟用 openssl 後，所有 HTTPS 功能都會正常工作，包括：
- Google OAuth
- API 調用
- HTTPS 網站抓取
