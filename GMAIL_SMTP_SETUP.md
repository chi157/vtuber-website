# Gmail SMTP 設定教學

## 步驟 1：啟用 Gmail 兩步驟驗證

1. 前往 Google 帳戶設定：https://myaccount.google.com/security
2. 點選「兩步驟驗證」
3. 按照指示完成設定

## 步驟 2：建立應用程式密碼

1. 前往：https://myaccount.google.com/apppasswords
2. 選擇應用程式：選擇「郵件」
3. 選擇裝置：選擇「Windows 電腦」
4. 點選「產生」
5. **複製顯示的 16 位數密碼**（例如：abcd efgh ijkl mnop）

## 步驟 3：修改 email.php 設定

開啟 `backend/email.php`，找到以下行並修改：

```php
define('SMTP_USERNAME', 'your-email@gmail.com'); // 改為您的 Gmail 地址
define('SMTP_PASSWORD', 'your-app-password');    // 改為剛才產生的 16 位數密碼（移除空格）
define('SMTP_FROM_EMAIL', 'your-email@gmail.com'); // 改為您的 Gmail 地址
```

**範例：**
```php
define('SMTP_USERNAME', 'chi157example@gmail.com');
define('SMTP_PASSWORD', 'abcdefghijklmnop'); // 注意：移除空格
define('SMTP_FROM_EMAIL', 'chi157example@gmail.com');
```

## 測試

修改完成後，重新註冊帳號測試是否能收到驗證郵件。

## 常見問題

**Q: 發送失敗？**
- 確認兩步驟驗證已啟用
- 確認使用的是「應用程式密碼」，不是 Gmail 登入密碼
- 應用程式密碼移除所有空格

**Q: Gmail 每日發送限制？**
- 免費 Gmail：每天最多 500 封
- Google Workspace：每天最多 2000 封
