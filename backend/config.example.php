<?php
// 配置文件範例
// 複製此文件為 config.local.php 並填入您的實際憑證
// 注意：此文件只定義配置常數，不包含函數或數據庫連接代碼

// 資料庫連線設定
define('DB_HOST', 'localhost');
define('DB_NAME', 'vt_website');
define('DB_USER', 'root');
define('DB_PASS', '');  // 請填入您的資料庫密碼
define('DB_CHARSET', 'utf8mb4');

// 網站設定
define('SITE_URL', 'https://vtwebsite.chi157.com');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');

// Google OAuth 設定
// 請到 https://console.cloud.google.com/ 建立 OAuth 2.0 憑證
define('GOOGLE_CLIENT_ID', 'YOUR_GOOGLE_CLIENT_ID');
define('GOOGLE_CLIENT_SECRET', 'YOUR_GOOGLE_CLIENT_SECRET');
define('GOOGLE_REDIRECT_URI', SITE_URL . '/google-callback.php');
