-- 手動執行：為 users 資料表添加 Google OAuth 欄位

USE vt_website;

-- 1. 修改 password 欄位為可選
ALTER TABLE users MODIFY COLUMN password VARCHAR(255) NULL;

-- 2. 添加 google_id 欄位（如果不存在）
ALTER TABLE users ADD COLUMN google_id VARCHAR(255) NULL UNIQUE AFTER password;

-- 3. 添加 avatar 欄位（如果不存在）
ALTER TABLE users ADD COLUMN avatar VARCHAR(255) NULL AFTER google_id;

-- 4. 添加 auth_provider 欄位（如果不存在）
ALTER TABLE users ADD COLUMN auth_provider ENUM('local', 'google') DEFAULT 'local' AFTER avatar;

-- 5. 添加索引
ALTER TABLE users ADD INDEX idx_google_id (google_id);

-- 查看結果
DESCRIBE users;
