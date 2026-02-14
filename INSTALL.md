# 鑰匙圈預購系統安裝說明

## 📋 系統檔案結構

```
vt-website-main/
├── backend/
│   ├── config.php         # 資料庫連線設定
│   └── database.sql       # 資料庫結構
├── uploads/               # 付款證明上傳目錄（需設定權限）
├── register.php           # 使用者註冊頁面
├── login.php             # 使用者登入頁面
├── logout.php            # 登出處理
├── preorder.php          # 預購表單（需登入）
├── admin.php             # 管理後台
└── keychain.html         # 商品展示頁面
```

## 🚀 安裝步驟

### 1. 設定資料庫

1. 開啟 phpMyAdmin 或你的 MySQL 管理工具
2. 執行 `backend/database.sql` 檔案建立資料表
3. 或手動執行以下指令：

```sql
CREATE DATABASE vt_website CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

然後匯入 `backend/database.sql`

### 2. 修改資料庫連線設定

編輯 `backend/config.php`：

```php
define('DB_HOST', 'localhost');        // 你的資料庫主機
define('DB_NAME', 'vt_website');       // 你的資料庫名稱
define('DB_USER', 'root');             // 你的資料庫使用者
define('DB_PASS', '');                 // 你的資料庫密碼
define('SITE_URL', 'https://vtwebsite.chi157.com'); // 你的網站網址
```

### 3. 設定檔案上傳權限

確保 `uploads/` 資料夾有寫入權限：

```bash
chmod 755 uploads/
```

### 4. 管理員帳號

預設管理員帳號：
- 帳號：`admin`
- 密碼：`password`

**重要：登入後請立即修改密碼！**

修改密碼方式：
```php
// 產生新密碼的雜湊值
echo password_hash('你的新密碼', PASSWORD_DEFAULT);

// 然後在資料庫執行：
UPDATE admins SET password = '產生的雜湊值' WHERE username = 'admin';
```

## 📱 使用流程

### 使用者端：

1. 訪問 `keychain.html` 查看商品
2. 點擊「立即預購」→ 導向登入頁面
3. 如果沒有帳號 → 註冊（`register.php`）
4. 登入後 → 填寫預購表單（`preorder.php`）
5. 上傳 LINE Pay 付款證明截圖
6. 送出訂單

### 管理員端：

1. 訪問 `admin.php`
2. 使用管理員帳號登入
3. 查看所有訂單
4. 更新訂單狀態（待處理 → 已確認 → 已出貨 → 已完成）
5. 查看付款證明圖片

## 🔒 安全性建議

1. **修改預設管理員密碼**
2. **HTTPS**：正式環境請使用 HTTPS
3. **檔案上傳**：`uploads/` 資料夾不應該允許執行 PHP
4. **Session**：使用 HTTPS 時啟用 secure cookies（config.php 中修改）
5. **備份**：定期備份資料庫

## ⚙️ 功能特色

✅ 會員註冊/登入系統
✅ 預購表單（含檔案上傳）
✅ 7-11 門市資訊填寫
✅ LINE Pay 付款證明上傳
✅ 管理後台訂單管理
✅ 訂單狀態追蹤
✅ 統計數據展示

## 🐛 常見問題

**Q: 無法上傳檔案？**
A: 檢查 `uploads/` 資料夾權限，確保為 755 或 777

**Q: 資料庫連線失敗？**
A: 檢查 `backend/config.php` 的資料庫設定是否正確

**Q: 登入後顯示空白？**
A: 檢查 PHP 錯誤日誌，確認 session 是否正常啟動

**Q: 圖片無法顯示？**
A: 確認 `uploads/` 資料夾路徑正確，且檔案存在

## 📞 技術支援

如有問題請聯繫柒柒！
