<?php
require_once 'backend/config.php';
require_once 'backend/email.php';

if (isLoggedIn()) {
    header('Location: preorder.php');
    exit;
}

$error = '';
$success = '';
$email = $_GET['email'] ?? '';

// 處理驗證碼提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputEmail = cleanInput($_POST['email'] ?? '');
    $code = cleanInput($_POST['code'] ?? '');
    
    if (empty($inputEmail) || empty($code)) {
        $error = '請輸入驗證碼';
    } else {
        $result = verifyCode($pdo, $inputEmail, $code);
        
        if ($result['success']) {
            // 驗證成功，自動登入
            $_SESSION['user_id'] = $result['user']['id'];
            $_SESSION['username'] = $result['user']['username'];
            $_SESSION['email'] = $inputEmail;
            
            header('Location: preorder.php?verified=1');
            exit;
        } else {
            $error = $result['message'];
        }
    }
}

// 處理重新發送驗證碼
if (isset($_GET['resend']) && !empty($email)) {
    try {
        $stmt = $pdo->prepare("SELECT id, username FROM users WHERE email = ? AND email_verified = 0");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            $verificationCode = generateVerificationCode();
            $expiresAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            
            $stmt = $pdo->prepare("UPDATE users SET verification_code = ?, verification_expires = ? WHERE id = ?");
            $stmt->execute([$verificationCode, $expiresAt, $user['id']]);
            
            if (sendVerificationEmail($email, $user['username'], $verificationCode)) {
                $success = '驗證碼已重新發送到您的信箱';
            } else {
                $error = '發送失敗，請稍後再試';
            }
        }
    } catch (PDOException $e) {
        $error = '操作失敗';
    }
}
?>
<!doctype html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>信箱驗證 - 柒柒 chi</title>
    <link rel="icon" type="image/png" href="images/頭貼%20-%20圓形.png">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="cloud cloud--1" aria-hidden="true"></div>
    <div class="cloud cloud--2" aria-hidden="true"></div>
    <div class="cloud cloud--3" aria-hidden="true"></div>
    <div class="cloud cloud--4" aria-hidden="true"></div>
    
    <main class="page">
        <div class="form-container">
            <div class="form-card">
                <h1 class="form-title">📧 信箱驗證</h1>
                
                <div style="background: rgba(59, 130, 246, 0.1); border-left: 4px solid #60a5fa; border-radius: 8px; padding: 16px; margin-bottom: 24px;">
                    <p style="color: #93c5fd; margin: 0; line-height: 1.6;">
                        我們已經發送一封包含 <strong>6 位數驗證碼</strong> 的郵件到<br>
                        <strong style="color: #60a5fa;"><?php echo htmlspecialchars($email); ?></strong>
                    </p>
                    <p style="color: rgba(255,255,255,0.6); font-size: 14px; margin: 12px 0 0 0;">
                        驗證碼有效期限為 15 分鐘
                    </p>
                </div>
                
                <?php if ($error): ?>
                    <div class="form-error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="form-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                    
                    <div class="form-group">
                        <label class="form-label" for="code">請輸入驗證碼</label>
                        <input 
                            type="text" 
                            id="code" 
                            name="code" 
                            class="form-input" 
                            placeholder="000000" 
                            maxlength="6" 
                            pattern="[0-9]{6}"
                            style="font-size: 24px; letter-spacing: 8px; text-align: center; font-weight: bold;"
                            required 
                            autofocus
                        >
                    </div>
                    
                    <button type="submit" class="form-button">驗證並完成註冊</button>
                </form>
                
                <div style="margin-top: 24px; text-align: center;">
                    <p style="color: rgba(255,255,255,0.6); font-size: 14px; margin-bottom: 12px;">
                        沒有收到驗證碼？
                    </p>
                    <a href="?email=<?php echo urlencode($email); ?>&resend=1" 
                       style="color: #60a5fa; text-decoration: none; font-weight: 500; transition: color 0.3s;"
                       onmouseover="this.style.color='#93c5fd'" 
                       onmouseout="this.style.color='#60a5fa'">
                        📨 重新發送驗證碼
                    </a>
                </div>
                
                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.1);">
                    <details style="color: rgba(255,255,255,0.6); font-size: 14px;">
                        <summary style="cursor: pointer; color: #7dd3fc; margin-bottom: 10px;">💡 沒收到郵件？</summary>
                        <ul style="margin: 10px 0; padding-left: 20px; line-height: 1.8;">
                            <li>請檢查 <strong>垃圾郵件</strong> 或 <strong>促銷內容</strong> 資料夾</li>
                            <li>確認信箱地址是否正確</li>
                            <li>郵件可能需要幾分鐘才會送達</li>
                            <li>如果超過 5 分鐘仍未收到，請點擊上方重新發送</li>
                        </ul>
                    </details>
                </div>
                
                <div class="form-link">
                    <a href="register.php">返回註冊頁面</a> | <a href="login.php">已有帳號？登入</a>
                </div>
            </div>
        </div>
    </main>
    
    <script src="script.js"></script>
    <script>
        // 自動格式化驗證碼輸入（只允許數字）
        const codeInput = document.getElementById('code');
        codeInput.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 6);
        });
        
        // 自動提交（當輸入 6 位數時）
        codeInput.addEventListener('input', function(e) {
            if (this.value.length === 6) {
                // 可選：自動提交表單
                // this.form.submit();
            }
        });
    </script>
</body>
</html>
