<?php
require_once 'backend/config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// 先載入用戶資料
$stmt = $pdo->prepare("SELECT id, username, email, phone FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit;
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = cleanInput($_POST['username'] ?? '');
    $phone = cleanInput($_POST['phone'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // 驗證
    if (empty($username)) {
        $error = '使用者名稱不能為空';
    } else {
        try {
            // 檢查使用者名稱是否已被其他人使用
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$username, $user['id']]);
            if ($stmt->fetch()) {
                $error = '此使用者名稱已被使用';
            } else {
                // 如果要修改密碼
                if (!empty($new_password) || !empty($current_password)) {
                    // 必須填寫當前密碼
                    if (empty($current_password)) {
                        $error = '請輸入當前密碼以確認身份';
                    } elseif (empty($new_password)) {
                        $error = '請輸入新密碼';
                    } elseif (strlen($new_password) < 6) {
                        $error = '新密碼至少需要 6 個字元';
                    } elseif ($new_password !== $confirm_password) {
                        $error = '新密碼確認不一致';
                    } else {
                        // 驗證當前密碼
                        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                        $stmt->execute([$user['id']]);
                        $userData = $stmt->fetch();
                        
                        if (!password_verify($current_password, $userData['password'])) {
                            $error = '當前密碼錯誤';
                        } else {
                            // 更新資料包括密碼
                            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                            $stmt = $pdo->prepare("UPDATE users SET username = ?, phone = ?, password = ? WHERE id = ?");
                            $stmt->execute([$username, $phone, $hashed_password, $user['id']]);
                            
                            $_SESSION['username'] = $username;
                            $success = '個人資料已更新（包括密碼）';
                        }
                    }
                } else {
                    // 只更新基本資料
                    try {
                        // 開始事務
                        $pdo->beginTransaction();
                        
                        $stmt = $pdo->prepare("UPDATE users SET username = ?, phone = ? WHERE id = ?");
                        $result = $stmt->execute([$username, $phone, $user['id']]);
                        
                        // 提交事務
                        $pdo->commit();
                        
                        if ($result) {
                            // 更新 Session
                            $_SESSION['username'] = $username;
                            
                            // 立即從資料庫重新查詢以確認
                            $checkStmt = $pdo->prepare("SELECT username, phone FROM users WHERE id = ?");
                            $checkStmt->execute([$user['id']]);
                            $updatedData = $checkStmt->fetch();
                            
                            if ($updatedData && $updatedData['phone'] === $phone) {
                                $success = '個人資料已更新（電話：' . htmlspecialchars($phone) . '）';
                            } else {
                                $error = '更新指令執行成功，但資料庫未反映變更。資料庫電話：' . ($updatedData['phone'] ?? 'null');
                            }
                        } else {
                            $error = '更新執行失敗';
                        }
                    } catch (Exception $e) {
                        // 回滾事務
                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }
                        throw $e;
                    }
                }
                
                // 強制從資料庫重新獲取用戶資料
                if (empty($error)) {
                    // 清除可能的快取
                    $stmt = $pdo->prepare("SELECT id, username, email, phone FROM users WHERE id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $user = $stmt->fetch();
                    
                    if (!$user) {
                        $error = '無法重新載入用戶資料';
                    }
                }
            }
        } catch (PDOException $e) {
            $error = '更新失敗：' . $e->getMessage();
        }
    }
    
    // 更新後重新載入用戶資料
    if (empty($error)) {
        $stmt = $pdo->prepare("SELECT id, username, email, phone FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
    }
}
?>
<!doctype html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>個人資料 - 柒柒 chi</title>
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
                <h1 class="form-title">👤 個人資料</h1>
                
                <?php if ($error): ?>
                    <div class="form-error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="form-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label class="form-label" for="username">使用者名稱</label>
                        <input type="text" id="username" name="username" class="form-input" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="email">電子郵件（不可修改）</label>
                        <input type="email" id="email" name="email" class="form-input" value="<?php echo htmlspecialchars($user['email']); ?>" readonly style="background: rgba(255,255,255,0.05); cursor: not-allowed;">
                        <p style="color: rgba(255,255,255,0.5); font-size: 12px; margin-top: 6px;">
                            💡 電子郵件用於登入識別，無法修改
                        </p>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="phone">手機號碼</label>
                        <input type="tel" id="phone" name="phone" class="form-input" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="請輸入手機號碼">
                        <?php if (empty($user['phone'])): ?>
                            <p style="color: #fbbf24; font-size: 12px; margin-top: 6px;">
                                ⚠️ 您尚未設定手機號碼，預購時需要提供聯絡電話
                            </p>
                        <?php endif; ?>
                    </div>
                    
                    <hr style="border: none; border-top: 1px solid rgba(255,255,255,0.1); margin: 30px 0;">
                    
                    <h3 style="color: #93c5fd; margin-bottom: 20px; font-size: 18px;">🔒 修改密碼（選填）</h3>
                    <p style="color: rgba(255,255,255,0.6); font-size: 14px; margin-bottom: 20px;">
                        如不需要修改密碼，請留空下方欄位
                    </p>
                    
                    <div class="form-group">
                        <label class="form-label" for="current_password">當前密碼</label>
                        <div style="position: relative;">
                            <input type="password" id="current_password" name="current_password" class="form-input" style="padding-right: 50px;" placeholder="若要修改密碼請輸入">
                            <button type="button" id="toggle-current-password" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; font-size: 20px; padding: 5px; color: rgba(255,255,255,0.6); transition: color 0.3s;" onmouseover="this.style.color='rgba(255,255,255,0.9)'" onmouseout="this.style.color='rgba(255,255,255,0.6)'">
                                顯示密碼
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="new_password">新密碼（至少 6 個字元）</label>
                        <div style="position: relative;">
                            <input type="password" id="new_password" name="new_password" class="form-input" style="padding-right: 50px;" placeholder="若要修改密碼請輸入">
                            <button type="button" id="toggle-new-password" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; font-size: 20px; padding: 5px; color: rgba(255,255,255,0.6); transition: color 0.3s;" onmouseover="this.style.color='rgba(255,255,255,0.9)'" onmouseout="this.style.color='rgba(255,255,255,0.6)'">
                                顯示密碼
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="confirm_password">確認新密碼</label>
                        <div style="position: relative;">
                            <input type="password" id="confirm_password" name="confirm_password" class="form-input" style="padding-right: 50px;" placeholder="再次輸入新密碼">
                            <button type="button" id="toggle-confirm-password" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; font-size: 20px; padding: 5px; color: rgba(255,255,255,0.6); transition: color 0.3s;" onmouseover="this.style.color='rgba(255,255,255,0.9)'" onmouseout="this.style.color='rgba(255,255,255,0.6)'">
                                顯示密碼
                            </button>
                        </div>
                    </div>
                    
                    <button type="submit" class="form-button">💾 儲存變更</button>
                </form>
                
                <div class="form-link">
                    <a href="preorder.php">返回預購頁面</a> | <a href="logout.php">登出</a>
                </div>
            </div>
        </div>
    </main>
    
    <script src="script.js"></script>
    <script>
        // 密碼顯示/隱藏切換 - 當前密碼
        const currentPasswordInput = document.getElementById('current_password');
        const toggleCurrentPasswordBtn = document.getElementById('toggle-current-password');
        
        toggleCurrentPasswordBtn.addEventListener('click', function() {
            if (currentPasswordInput.type === 'password') {
                currentPasswordInput.type = 'text';
                toggleCurrentPasswordBtn.textContent = '隱藏密碼';
            } else {
                currentPasswordInput.type = 'password';
                toggleCurrentPasswordBtn.textContent = '顯示密碼';
            }
        });
        
        // 密碼顯示/隱藏切換 - 新密碼
        const newPasswordInput = document.getElementById('new_password');
        const toggleNewPasswordBtn = document.getElementById('toggle-new-password');
        
        toggleNewPasswordBtn.addEventListener('click', function() {
            if (newPasswordInput.type === 'password') {
                newPasswordInput.type = 'text';
                toggleNewPasswordBtn.textContent = '隱藏密碼';
            } else {
                newPasswordInput.type = 'password';
                toggleNewPasswordBtn.textContent = '顯示密碼';
            }
        });
        
        // 密碼顯示/隱藏切換 - 確認密碼
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
