# Google OAuth 登入設定指南

本指南將幫助您設定 Google OAuth 2.0 登入功能。

## 步驟 1: 建立 Google Cloud 專案

1. 前往 [Google Cloud Console](https://console.cloud.google.com/)
2. 點擊頂部的「選擇專案」，然後點擊「新增專案」
3. 輸入專案名稱（例如：VT Website）
4. 點擊「建立」

## 步驟 2: 啟用 Google+ API

1. 在左側選單中，點擊「API 和服務」 > 「程式庫」
2. 搜尋「Google+ API」
3. 點擊並啟用它

## 步驟 3: 建立 OAuth 2.0 憑證

1. 在左側選單中，點擊「API 和服務」 > 「憑證」
2. 點擊「建立憑證」 > 「OAuth 用戶端 ID」
3. 如果是首次建立，需要先設定「OAuth 同意畫面」：
   - 選擇「外部」作為使用者類型
   - 輸入應用程式名稱（例如：柒柒 chi）
   - 填寫必要資訊
   - 在「授權網域」中添加您的網域（例如：chi157.com）
   - 儲存並繼續

4. 建立 OAuth 用戶端 ID：
   - 應用程式類型：選擇「網頁應用程式」
   - 名稱：輸入識別名稱（例如：VT Website OAuth）
   - 已授權的 JavaScript 來源：
     ```
     https://vtwebsite.chi157.com
     ```
   - 已授權的重新導向 URI：
     ```
     https://vtwebsite.chi157.com/google-callback.php
     ```
   - 點擊「建立」

5. 複製顯示的「用戶端 ID」和「用戶端密鑰」

## 步驟 4: 更新 config.php

打開 `backend/config.php` 檔案，找到以下行：

```php
define('GOOGLE_CLIENT_ID', 'YOUR_GOOGLE_CLIENT_ID');
define('GOOGLE_CLIENT_SECRET', 'YOUR_GOOGLE_CLIENT_SECRET');
```

將 `YOUR_GOOGLE_CLIENT_ID` 和 `YOUR_GOOGLE_CLIENT_SECRET` 替換為您剛才複製的值。

## 步驟 5: 更新資料庫

執行以下 SQL 來更新現有資料庫結構：

```bash
mysql -u root -p vt_website < backend/add_google_auth.sql
```

或者在 phpMyAdmin 或其他資料庫管理工具中執行 `backend/add_google_auth.sql` 檔案中的 SQL 語句。

## 步驟 6: 測試

1. 前往您的網站登入頁面
2. 點擊「使用 Google 登入」按鈕
3. 選擇或登入您的 Google 帳號
4. 授權應用程式存取您的基本資料
5. 應該會自動登入並重定向到預購頁面

## 功能說明

- **新用戶**：首次使用 Google 登入時，系統會自動建立帳號
- **現有用戶**：如果電子郵件已存在，系統會將 Google 帳號綁定到現有帳號
- **頭像**：系統會自動儲存 Google 帳號的頭像
- **免密碼**：使用 Google 登入的用戶不需要設定密碼

## 注意事項

1. 確保您的網站使用 HTTPS（Google OAuth 要求）
2. 重新導向 URI 必須完全匹配（包括 http/https 和結尾的斜線）
3. 在 Google Cloud Console 中，您可以設定 OAuth 同意畫面的樣式和隱私政策
4. 建議定期檢查和更新 OAuth 憑證

## 疑難排解

### 錯誤：redirect_uri_mismatch
- 檢查 `backend/config.php` 中的 `GOOGLE_REDIRECT_URI` 是否與 Google Cloud Console 中設定的完全一致
- 確認網址是 https 而不是 http

### 錯誤：invalid_client
- 檢查 `GOOGLE_CLIENT_ID` 和 `GOOGLE_CLIENT_SECRET` 是否正確
- 確認憑證尚未過期或被刪除

### Google 登入後沒有反應
- 檢查瀏覽器控制台是否有錯誤
- 確認資料庫已正確更新（執行了 add_google_auth.sql）
- 檢查 PHP 錯誤日誌

## 安全性建議

1. 定期更新 OAuth 憑證
2. 不要將 `GOOGLE_CLIENT_SECRET` 提交到公開的版本控制系統
3. 定期檢查 Google Cloud Console 的安全性設定
4. 考慮啟用兩步驟驗證以提高安全性
