-- 為現有的 users 資料表添加 Google OAuth 支援
-- 執行此 SQL 來更新現有資料庫

USE vt_website;

-- 修改 password 欄位為可選（Google 登入不需要密碼）
ALTER TABLE users MODIFY password VARCHAR(255) NULL;

-- 添加 Google ID 欄位
ALTER TABLE users ADD COLUMN IF NOT EXISTS google_id VARCHAR(255) UNIQUE AFTER password;

-- 添加頭像欄位
ALTER TABLE users ADD COLUMN IF NOT EXISTS avatar VARCHAR(255) AFTER google_id;

-- 添加認證提供者欄位
ALTER TABLE users ADD COLUMN IF NOT EXISTS auth_provider ENUM('local', 'google') DEFAULT 'local' AFTER avatar;

-- 添加索引
ALTER TABLE users ADD INDEX IF NOT EXISTS idx_google_id (google_id);
