<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Google OAuth 診斷工具</title>
    <style>
        body {
            font-family: 'Microsoft YaHei', Arial, sans-serif;
            max-width: 900px;
            margin: 40px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .card {
            background: white;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #4285F4;
            padding-bottom: 10px;
        }
        h2 {
            color: #4285F4;
            margin-top: 0;
        }
        .status {
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            border-left: 4px solid #17a2b8;
        }
        code {
            background: #f4f4f4;
            padding: 3px 8px;
            border-radius: 4px;
            font-family: 'Consolas', monospace;
            font-size: 14px;
            color: #c7254e;
        }
        .copy-box {
            background: #f8f9fa;
            border: 2px dashed #4285F4;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            position: relative;
        }
        .copy-box code {
            background: transparent;
            color: #333;
            font-size: 16px;
            font-weight: bold;
        }
        .copy-btn {
            position: absolute;
            right: 10px;
            top: 10px;
            padding: 8px 15px;
            background: #4285F4;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        .copy-btn:hover {
            background: #357ae8;
        }
        ol {
            line-height: 2;
        }
        ol li {
            margin-bottom: 15px;
        }
        strong {
            color: #4285F4;
        }
        a {
            color: #4285F4;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        .step {
            background: #f8f9fa;
            padding: 10px 15px;
            margin: 10px 0;
            border-left: 4px solid #4285F4;
        }
        .test-button {
            display: inline-block;
            padding: 12px 25px;
            background: #34A853;
            color: white;
            border-radius: 5px;
            text-decoration: none;
            margin-top: 15px;
            font-weight: bold;
        }
        .test-button:hover {
            background: #2d9148;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <?php
    require_once 'backend/config.php';
    
    $currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
    $redirectUri = GOOGLE_REDIRECT_URI;
    $clientId = GOOGLE_CLIENT_ID;
    ?>
    
    <h1>🔍 Google OAuth 配置診斷</h1>
    
    <div class="card">
        <h2>📋 當前配置</h2>
        
        <div class="status info">
            <strong>Client ID:</strong><br>
            <code><?php echo htmlspecialchars($clientId); ?></code>
        </div>
        
        <div class="status info">
            <strong>重定向 URI (Redirect URI):</strong><br>
            <code><?php echo htmlspecialchars($redirectUri); ?></code>
        </div>
        
        <div class="status warning">
            <strong>⚠️ 檢測到的當前網域:</strong><br>
            <code><?php echo htmlspecialchars($currentUrl); ?></code>
            <?php if (strpos($currentUrl, 'localhost') !== false || strpos($currentUrl, '127.0.0.1') !== false): ?>
                <br><br>
                <strong>注意：</strong> 您目前在本地環境。Google OAuth 通常不支援 localhost，除非特別配置。
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card">
        <h2>❌ 錯誤原因</h2>
        <div class="status error">
            <strong>Error 400: redirect_uri_mismatch</strong>
            <br><br>
            這表示您在 Google Cloud Console 中設定的「已授權的重新導向 URI」與您代碼中的不匹配。
        </div>
    </div>
    
    <div class="card">
        <h2>✅ 解決步驟</h2>
        
        <div class="step">
            <strong>步驟 1: 複製正確的重定向 URI</strong>
            <div class="copy-box">
                <code id="redirectUri"><?php echo htmlspecialchars($redirectUri); ?></code>
                <button class="copy-btn" onclick="copyToClipboard('redirectUri')">複製</button>
            </div>
        </div>
        
        <div class="step">
            <strong>步驟 2: 前往 Google Cloud Console</strong>
            <ol>
                <li>訪問 <a href="https://console.cloud.google.com/apis/credentials" target="_blank">Google Cloud Console - API 憑證</a></li>
                <li>選擇您的專案</li>
                <li>找到並點擊您的 OAuth 2.0 客戶端 ID（名稱可能類似 "Web client" 或您自訂的名稱）</li>
            </ol>
        </div>
        
        <div class="step">
            <strong>步驟 3: 添加重定向 URI</strong>
            <ol>
                <li>在「已授權的重新導向 URI」區域，點擊「+ 新增 URI」</li>
                <li>貼上上面複製的 URI：<code><?php echo htmlspecialchars($redirectUri); ?></code></li>
                <li>點擊「儲存」</li>
            </ol>
            <div class="status warning">
                ⚠️ <strong>重要：</strong> URI 必須完全匹配，包括：
                <ul>
                    <li>http 或 https</li>
                    <li>網域名稱</li>
                    <li>完整路徑（包括 /google-callback.php）</li>
                    <li>不能有多餘的斜線</li>
                </ul>
            </div>
        </div>
        
        <div class="step">
            <strong>步驟 4: 可能需要的其他 URI</strong>
            <div class="copy-box">
                <strong>已授權的 JavaScript 來源：</strong><br>
                <code id="jsOrigin"><?php echo htmlspecialchars(SITE_URL); ?></code>
                <button class="copy-btn" onclick="copyToClipboard('jsOrigin')">複製</button>
            </div>
            <p>也建議添加此 URL 到「已授權的 JavaScript 來源」區域</p>
        </div>
    </div>
    
    <div class="card">
        <h2>🧪 測試配置</h2>
        <p>完成上述步驟後，等待 1-2 分鐘讓 Google 更新配置，然後點擊下方按鈕測試：</p>
        
        <?php
        $googleAuthUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
            'client_id' => GOOGLE_CLIENT_ID,
            'redirect_uri' => GOOGLE_REDIRECT_URI,
            'response_type' => 'code',
            'scope' => 'email profile',
            'access_type' => 'online'
        ]);
        ?>
        
        <a href="<?php echo htmlspecialchars($googleAuthUrl); ?>" class="test-button">
            🔐 測試 Google 登入
        </a>
        
        <div class="status info" style="margin-top: 20px;">
            <strong>💡 提示：</strong> 如果仍然失敗，請檢查：
            <ul>
                <li>是否已等待 1-2 分鐘讓配置生效</li>
                <li>URI 是否完全一致（包括大小寫）</li>
                <li>是否在正確的 Google Cloud 專案中</li>
                <li>OAuth 同意畫面是否已設定</li>
            </ul>
        </div>
    </div>
    
    <div class="card">
        <h2>📸 參考截圖配置</h2>
        <div class="status info">
            <strong>在 Google Cloud Console 中應該看起來像這樣：</strong><br><br>
            
            <strong>已授權的 JavaScript 來源：</strong>
            <pre style="background: #f4f4f4; padding: 10px; border-radius: 4px;"><?php echo htmlspecialchars(SITE_URL); ?></pre>
            
            <strong>已授權的重新導向 URI：</strong>
            <pre style="background: #f4f4f4; padding: 10px; border-radius: 4px;"><?php echo htmlspecialchars($redirectUri); ?></pre>
        </div>
    </div>
    
    <script>
        function copyToClipboard(elementId) {
            const element = document.getElementById(elementId);
            const text = element.textContent;
            
            navigator.clipboard.writeText(text).then(function() {
                const btn = element.nextElementSibling;
                const originalText = btn.textContent;
                btn.textContent = '✓ 已複製';
                btn.style.background = '#28a745';
                
                setTimeout(function() {
                    btn.textContent = originalText;
                    btn.style.background = '#4285F4';
                }, 2000);
            }, function(err) {
                alert('複製失敗，請手動複製');
            });
        }
    </script>
</body>
</html>
