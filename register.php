<?php
require_once 'backend/config.php';
require_once 'backend/email.php';

if (isLoggedIn()) {
    header('Location: preorder.php');
    exit;
}

$error = '';

// 檢查 OAuth 錯誤
if (isset($_GET['error']) && $_GET['error'] === 'oauth_failed') {
    $error = 'Google 註冊失敗，請稍後再試';
}

// 生成 Google OAuth URL
$googleAuthUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
    'client_id' => GOOGLE_CLIENT_ID,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'response_type' => 'code',
    'scope' => 'email profile',
    'access_type' => 'online'
]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = cleanInput($_POST['username'] ?? '');
    $email = cleanInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $phone = cleanInput($_POST['phone'] ?? '');
    
    // 驗證
    if (empty($username) || empty($email) || empty($password) || empty($phone)) {
        $error = '所有欄位都必須填寫';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '電子郵件格式不正確';
    } elseif (strlen($password) < 6) {
        $error = '密碼至少需要 6 個字元';
    } elseif ($password !== $confirm_password) {
        $error = '密碼確認不一致';
    } else {
        try {
            // 檢查使用者名稱是否已存在
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            if ($stmt->fetch()) {
                $error = '使用者名稱或電子郵件已被使用';
            } else {
                // 生成驗證碼
                $verificationCode = generateVerificationCode();
                $expiresAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                
                // 新增使用者（未驗證狀態）
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    INSERT INTO users (username, email, password, phone, email_verified, verification_code, verification_expires) 
                    VALUES (?, ?, ?, ?, 0, ?, ?)
                ");
                $stmt->execute([$username, $email, $hashed_password, $phone, $verificationCode, $expiresAt]);
                
                // 發送驗證郵件
                if (sendVerificationEmail($email, $username, $verificationCode)) {
                    // 跳轉到驗證頁面
                    header('Location: verify-email.php?email=' . urlencode($email));
                    exit;
                } else {
                    // 發送失敗，刪除剛建立的帳號
                    $stmt = $pdo->prepare("DELETE FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    $error = '發送驗證郵件失敗，請稍後再試或聯繫管理員';
                }
            }
        } catch (PDOException $e) {
            $error = '註冊失敗，請稍後再試';
        }
    }
}
?>
<!doctype html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>註冊 - 柒柒 chi</title>
    <link rel="icon" type="image/png" href="images/頭貼%20-%20圓形.png">
    <link rel="stylesheet" href="style.css">
    <script src="navbar.js" defer></script>
</head>
<body>
    <div class="cloud cloud--1" aria-hidden="true"></div>
    <div class="cloud cloud--2" aria-hidden="true"></div>
    <div class="cloud cloud--3" aria-hidden="true"></div>
    <div class="cloud cloud--4" aria-hidden="true"></div>
    
    <main class="page">
        <div class="form-container">
        <div class="form-card">
            <h1 class="form-title">註冊帳號</h1>
            
            <?php if ($error): ?>
                <div class="form-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label" for="username">使用者名稱</label>
                    <input type="text" id="username" name="username" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="email">電子郵件</label>
                    <input type="email" id="email" name="email" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="phone">手機號碼</label>
                    <input type="tel" id="phone" name="phone" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="password">密碼（至少 6 個字元）</label>
                    <div style="position: relative;">
                        <input type="password" id="password" name="password" class="form-input" style="padding-right: 50px;" required>
                        <button type="button" id="toggle-password" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; font-size: 20px; padding: 5px; color: rgba(255,255,255,0.6); transition: color 0.3s;" onmouseover="this.style.color='rgba(255,255,255,0.9)'" onmouseout="this.style.color='rgba(255,255,255,0.6)'">
                            顯示密碼
                        </button>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="confirm_password">確認密碼</label>
                    <div style="position: relative;">
                        <input type="password" id="confirm_password" name="confirm_password" class="form-input" style="padding-right: 50px;" required>
                        <button type="button" id="toggle-confirm-password" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; font-size: 20px; padding: 5px; color: rgba(255,255,255,0.6); transition: color 0.3s;" onmouseover="this.style.color='rgba(255,255,255,0.9)'" onmouseout="this.style.color='rgba(255,255,255,0.6)'">
                            顯示密碼
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="form-button">註冊</button>
            </form>
            
            <div style="margin: 20px 0; text-align: center; position: relative;">
                <div style="height: 1px; background: rgba(255,255,255,0.2);"></div>
                <span style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #1a1a2e; padding: 0 15px; color: rgba(255,255,255,0.5); font-size: 14px;">或</span>
            </div>
            
            <a href="<?php echo htmlspecialchars($googleAuthUrl); ?>" class="google-login-button" style="display: flex; align-items: center; justify-content: center; gap: 10px; padding: 12px; background: white; color: #444; border-radius: 8px; text-decoration: none; font-weight: 500; transition: all 0.3s; margin-bottom: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);" onmouseover="this.style.boxShadow='0 4px 8px rgba(0,0,0,0.2)'; this.style.transform='translateY(-2px)'" onmouseout="this.style.boxShadow='0 2px 4px rgba(0,0,0,0.1)'; this.style.transform='translateY(0)'">
                <svg width="20" height="20" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
                使用 Google 註冊
            </a>
            
            <div class="form-link">
                已經有帳號了？<a href="login.php">登入</a>
            </div>
        </div>
    </div>
    </main>
    
    <script src="script.js"></script>
    <script>
        // 密碼顯示/隱藏切換
        const passwordInput = document.getElementById('password');
        const togglePasswordBtn = document.getElementById('toggle-password');
        
        togglePasswordBtn.addEventListener('click', function() {
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                togglePasswordBtn.textContent = '隱藏密碼';
            } else {
                passwordInput.type = 'password';
                togglePasswordBtn.textContent = '顯示密碼';
            }
        });
        
        // 確認密碼顯示/隱藏切換
        const confirmPasswordInput = document.getElementById('confirm_password');
        const toggleConfirmPasswordBtn = document.getElementById('toggle-confirm-password');
        
        toggleConfirmPasswordBtn.addEventListener('click', function() {
            if (confirmPasswordInput.type === 'password') {
                confirmPasswordInput.type = 'text';
                toggleConfirmPasswordBtn.textContent = '隱藏密碼';
            } else {
                confirmPasswordInput.type = 'password';
                toggleConfirmPasswordBtn.textContent = '顯示密碼';
            }
        });
    </script>
</body>
</html>
