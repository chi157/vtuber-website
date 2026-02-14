<?php
// 開啟錯誤顯示（調試用）
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'backend/config.php';

// 如果已登入，重定向到預購頁面
if (isLoggedIn()) {
    header('Location: preorder.php');
    exit;
}

// 處理 Google OAuth 回調
if (isset($_GET['code'])) {
    $code = $_GET['code'];
    
    // 交換授權碼以獲取訪問令牌
    $tokenUrl = 'https://oauth2.googleapis.com/token';
    $tokenData = [
        'code' => $code,
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'grant_type' => 'authorization_code'
    ];
    
    // 使用 file_get_contents 代替 cURL
    $options = [
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/x-www-form-urlencoded',
            'content' => http_build_query($tokenData),
            'ignore_errors' => true
        ]
    ];
    
    $context = stream_context_create($options);
    $response = file_get_contents($tokenUrl, false, $context);
    
    // 調試：記錄響應
    error_log("Token Response: " . $response);
    
    // 檢查是否成功
    if ($response !== false) {
        $tokenInfo = json_decode($response, true);
        
        if (isset($tokenInfo['access_token'])) {
            $accessToken = $tokenInfo['access_token'];
            
            // 使用訪問令牌獲取用戶資訊
            $userInfoUrl = 'https://www.googleapis.com/oauth2/v2/userinfo';
            $options = [
                'http' => [
                    'method' => 'GET',
                    'header' => 'Authorization: Bearer ' . $accessToken
                ]
            ];
            
            $context = stream_context_create($options);
            $userInfoResponse = file_get_contents($userInfoUrl, false, $context);
            
            // 調試：記錄用戶信息
            error_log("User Info Response: " . $userInfoResponse);
            
            $userInfo = json_decode($userInfoResponse, true);
            
            if (isset($userInfo['id'])) {
                $googleId = $userInfo['id'];
                $email = $userInfo['email'];
                $name = $userInfo['name'] ?? '';
                $avatar = $userInfo['picture'] ?? '';
                
                try {
                    // 檢查是否已有此 Google 帳號
                    $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE google_id = ?");
                    $stmt->execute([$googleId]);
                    $user = $stmt->fetch();
                    
                    if ($user) {
                        // 已存在的 Google 帳號，直接登入
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['email'] = $user['email'];
                        
                        header('Location: preorder.php');
                        exit;
                    } else {
                        // 檢查電子郵件是否已被使用
                        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                        $stmt->execute([$email]);
                        $existingUser = $stmt->fetch();
                        
                        if ($existingUser) {
                            // 電子郵件已存在，將 Google ID 綁定到現有帳號
                            $stmt = $pdo->prepare("UPDATE users SET google_id = ?, avatar = ?, auth_provider = 'google' WHERE email = ?");
                            $stmt->execute([$googleId, $avatar, $email]);
                            
                            $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE email = ?");
                            $stmt->execute([$email]);
                            $user = $stmt->fetch();
                            
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['username'] = $user['username'];
                            $_SESSION['email'] = $user['email'];
                            
                            header('Location: preorder.php');
                            exit;
                        } else {
                            // 新用戶，創建帳號
                            // 從電子郵件生成用戶名
                            $username = explode('@', $email)[0];
                            $originalUsername = $username;
                            $counter = 1;
                            
                            // 確保用戶名唯一
                            while (true) {
                                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                                $stmt->execute([$username]);
                                if (!$stmt->fetch()) {
                                    break;
                                }
                                $username = $originalUsername . $counter;
                                $counter++;
                            }
                            
                            $stmt = $pdo->prepare("INSERT INTO users (username, email, google_id, avatar, auth_provider) VALUES (?, ?, ?, ?, 'google')");
                            $stmt->execute([$username, $email, $googleId, $avatar]);
                            
                            $_SESSION['user_id'] = $pdo->lastInsertId();
                            $_SESSION['username'] = $username;
                            $_SESSION['email'] = $email;
                            
                            header('Location: preorder.php');
                            exit;
                        }
                    }
                } catch (PDOException $e) {
                    error_log("Google OAuth Database Error: " . $e->getMessage());
                    die("資料庫錯誤: " . $e->getMessage()); // 調試用
                }
            } else {
                error_log("Google OAuth: No user ID in response");
                die("Google 回應中沒有用戶 ID。Response: " . print_r($userInfo, true));
            }
        } else {
            error_log("Google OAuth: No access token in response");
            die("無法獲取 access token。Response: " . $response);
        }
    } else {
        error_log("Google OAuth: Failed to get token response");
        die("無法從 Google 獲取 token");
    }
} else {
    error_log("Google OAuth: No authorization code received");
    die("未收到授權碼。GET 參數: " . print_r($_GET, true));
}

// 如果沒有授權碼或出錯，重定向回登入頁面
header('Location: login.php?error=oauth_failed');
exit;
