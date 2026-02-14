<?php
require_once 'backend/config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user = getCurrentUser();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipient_name = cleanInput($_POST['recipient_name'] ?? '');
    $phone = cleanInput($_POST['phone'] ?? '');
    $store_name = cleanInput($_POST['store_name'] ?? '');
    $store_address = cleanInput($_POST['store_address'] ?? '');
    $quantity = (int)($_POST['quantity'] ?? 1);
    $notes = cleanInput($_POST['notes'] ?? '');
    
    // 驗證
    if (empty($recipient_name) || empty($phone) || empty($store_name) || empty($store_address)) {
        $error = '所有欄位都必須填寫';
    } elseif ($quantity < 1) {
        $error = '數量至少為 1';
    } elseif (!isset($_FILES['payment_proof']) || $_FILES['payment_proof']['error'] !== UPLOAD_ERR_OK) {
        $error = '請上傳付款證明';
    } else {
        // 上傳檔案
        $uploadResult = uploadFile($_FILES['payment_proof']);
        
        if (!$uploadResult['success']) {
            $error = $uploadResult['message'];
        } else {
            try {
                $total_price = (100 * $quantity) + 60; // 商品總價 + 固定運費 60
                
                $stmt = $pdo->prepare("
                    INSERT INTO preorders (user_id, username, email, phone, recipient_name, store_name, store_address, quantity, total_price, payment_proof, notes) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");

                // INSERT INTO preorders (user_id, username, email, phone, recipient_name, store_name, store_address, quantity, total_price, payment_proof, notes) 
                //    VALUES (3, '柒柒', 'aa@bb.com', ?, ?, ?, ?, ?, ?, ?, ?)
                
                $stmt->execute([
                    $user['id'],
                    $user['username'],
                    $user['email'],
                    $phone,
                    $recipient_name,
                    $store_name,
                    $store_address,
                    $quantity,
                    $total_price,
                    $uploadResult['filename'],
                    $notes
                ]);
                
                $success = '預購成功！訂單編號：' . $pdo->lastInsertId();
            } catch (PDOException $e) {
                $error = '預購失敗，請稍後再試';
            }
        }
    }
}
?>
<!doctype html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>鑰匙圈預購表單 - 柒柒 chi</title>
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
            <h1 class="form-title">🔑 鑰匙圈預購表單</h1>
            <div class="user-info">
                歡迎，<?php echo htmlspecialchars($user['username']); ?>！
            </div>
            
            <?php if ($error): ?>
                <div class="form-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="form-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="price-info">
                <p>鑰匙圈價格：NT$ 100 / 個</p>
                <p>運費（7-11 賣貨便）：NT$ 60</p>
            </div>
            
            <form method="POST" action="" enctype="multipart/form-data" novalidate>
                <div style="background: rgba(59, 130, 246, 0.1); border-left: 4px solid #60a5fa; border-radius: 8px; padding: 12px 16px; margin-bottom: 20px;">
                    <p style="color: #93c5fd; font-size: 14px; margin: 0;">
                        💡 <strong>提示：</strong>如需修改訂購人姓名或電話，請前往【<a href="profile.php" style="color: #60a5fa; text-decoration: underline;">個人資料</a>】頁面修改
                    </p>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="recipient_name">訂購人姓名 *</label>
                    <input type="text" id="recipient_name" name="recipient_name" class="form-input" value="<?php echo htmlspecialchars($user['username']); ?>" readonly required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="phone">聯絡電話 *</label>
                    <input type="tel" id="phone" name="phone" class="form-input" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" readonly required>
                    <?php if (empty($user['phone'])): ?>
                        <p style="color: #fbbf24; font-size: 12px; margin-top: 6px;">
                            ⚠️ 請先前往<a href="profile.php" style="color: #60a5fa; text-decoration: underline;">個人資料</a>頁面設定手機號碼
                        </p>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="store_name">7-11 門市名稱 *</label>
                    <input type="text" id="store_name" name="store_name" class="form-input" placeholder="例如：台北中山門市" list="store-suggestions" required>
                    <datalist id="store-suggestions">
                        <!-- 常見門市建議 -->
                    </datalist>
                    <a href="https://emap.pcsc.com.tw/" target="_blank" style="display: inline-block; margin-top: 8px; color: #60a5fa; font-size: 14px; text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='#93c5fd'" onmouseout="this.style.color='#60a5fa'">
                        🔍 開啟 7-11 門市查詢系統
                    </a>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="store_address">7-11 門市地址 *</label>
                    <input type="text" id="store_address" name="store_address" class="form-input" placeholder="例如：台北市中山區..." required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="quantity">數量 *</label>
                    <input type="number" id="quantity" name="quantity" class="form-input" min="1" value="1" required>
                </div>
                
                <div class="price-info" style="margin-bottom: 24px;">
                    <p style="font-size: 16px; margin: 4px 0;">計算：<span id="quantity-display">1</span> × NT$ 100 + 運費 NT$ 60</p>
                    <p class="total-price">總金額：NT$ <span id="total-price">160</span></p>
                </div>
                
                <div class="form-group">
                    <label class="form-label">💳 LINE Pay 付款</label>
                    <div style="background: linear-gradient(135deg, rgba(34, 197, 94, 0.1) 0%, rgba(16, 185, 129, 0.1) 100%); border: 2px solid rgba(34, 197, 94, 0.3); border-radius: 12px; padding: 20px; text-align: center;">
                        <p style="color: #6ee7b7; font-size: 16px; margin-bottom: 16px; font-weight: 600;">請掃描以下 QR Code 進行付款</p>
                        <div style="background: white; padding: 16px; border-radius: 12px; display: inline-block; margin-bottom: 16px;">
                            <img src="images/linepayqr.png" alt="LINE Pay QR Code" style="width: 200px; height: 200px; display: block;">
                        </div>
                        <div style="background: rgba(239, 68, 68, 0.15); border: 1.5px solid rgba(239, 68, 68, 0.4); border-radius: 8px; padding: 12px; margin-top: 12px;">
                            <p style="color: #fca5a5; font-size: 14px; margin: 0;">
                                ⚠️ <strong>重要提醒：</strong>付款完成後，請務必截圖付款證明並上傳至下方欄位
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">LINE Pay 付款證明 * (請上傳截圖)</label>
                    
                    <!-- 付款成功示意圖 -->
                    <div style="background: rgba(59, 130, 246, 0.1); border: 2px dashed rgba(96, 165, 250, 0.4); border-radius: 12px; padding: 16px; margin-bottom: 16px; text-align: center;">
                        <p style="color: #93c5fd; font-size: 14px; margin-bottom: 12px; font-weight: 600;">📸 付款成功後請截圖如下畫面：</p>
                        <div style="background: rgba(26, 41, 80, 0.3); padding: 12px; border-radius: 8px; display: inline-block;">
                            <img src="images/付款成功示意圖.png" alt="付款成功示意圖" style="max-width: 100%; height: auto; border-radius: 6px; max-height: 300px;">
                        </div>
                        <p style="color: rgba(255,255,255,0.6); font-size: 12px; margin-top: 8px;">請確保截圖包含付款金額、時間與交易成功訊息</p>
                    </div>
                    
                    <div class="file-upload" id="file-upload-area" onclick="document.getElementById('payment_proof').click()">
                        <p style="color: #7dd3fc; margin-bottom: 8px;">📷 點擊上傳圖片</p>
                        <p style="color: rgba(255,255,255,0.6); font-size: 13px;">支援 JPG、PNG 格式，最大 5MB</p>
                        <p style="color: rgba(255,255,255,0.5); font-size: 12px; margin-top: 4px;">或直接將圖片拖曳到此處</p>
                        <input type="file" id="payment_proof" name="payment_proof" accept="image/jpeg,image/png,image/jpg" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="notes">備註（選填）</label>
                    <textarea id="notes" name="notes" class="form-textarea" placeholder="有任何特殊需求可以在這裡備註..."></textarea>
                </div>
                
                <button type="submit" class="form-button">送出預購訂單</button>
            </form>
            
            <div class="logout-link">
                <a href="logout.php">登出</a> | <a href="keychain.html">返回商品頁</a>
            </div>
        </div>
    </div>
    </main>
    
    <script src="script.js"></script>
    <script>
        <?php if ($success): ?>
        // 订购成功，显示通知并跳转
        alert('✅ <?php echo str_replace("'", "\\'", $success); ?>');
        window.location.href = 'keychain.html';
        <?php endif; ?>
        
        // 更新总金额计算
        function updateTotalPrice() {
            const quantity = parseInt(document.getElementById('quantity').value) || 1;
            const unitPrice = 100;
            const shippingFee = 60;
            const total = (quantity * unitPrice) + shippingFee;
            
            document.getElementById('quantity-display').textContent = quantity;
            document.getElementById('total-price').textContent = total;
        }
        
        // 监听数量变化
        document.getElementById('quantity').addEventListener('input', updateTotalPrice);
        document.getElementById('quantity').addEventListener('change', updateTotalPrice);
        
        // 文件上传提示
        document.getElementById('payment_proof').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name;
            if (fileName) {
                document.querySelector('.file-upload p').textContent = '✅ ' + fileName;
            }
        });
        
        // 拖放文件上传功能
        const uploadArea = document.getElementById('file-upload-area');
        const fileInput = document.getElementById('payment_proof');
        
        // 阻止默认拖放行为
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, preventDefaults, false);
            document.body.addEventListener(eventName, preventDefaults, false);
        });
        
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }
        
        // 拖动时添加高亮效果
        ['dragenter', 'dragover'].forEach(eventName => {
            uploadArea.addEventListener(eventName, highlight, false);
        });
        
        ['dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, unhighlight, false);
        });
        
        function highlight(e) {
            uploadArea.style.borderColor = '#60a5fa';
            uploadArea.style.background = 'rgba(59, 130, 246, 0.1)';
        }
        
        function unhighlight(e) {
            uploadArea.style.borderColor = '';
            uploadArea.style.background = '';
        }
        
        // 处理文件拖放
        uploadArea.addEventListener('drop', handleDrop, false);
        
        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            
            if (files.length > 0) {
                const file = files[0];
                
                // 检查文件类型
                const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
                if (!allowedTypes.includes(file.type)) {
                    alert('❌ 請上傳 JPG 或 PNG 格式的圖片');
                    return;
                }
                
                // 检查文件大小 (5MB)
                if (file.size > 5242880) {
                    alert('❌ 檔案大小超過 5MB，請選擇較小的圖片');
                    return;
                }
                
                // 将文件赋值给 input
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                fileInput.files = dataTransfer.files;
                
                // 显示文件名
                document.querySelector('.file-upload p').textContent = '✅ ' + file.name;
            }
        }
        
        // 表单验证 - 提交前检查
        document.querySelector('form').addEventListener('submit', function(e) {
            e.preventDefault(); // 先阻止提交
            
            const storeName = document.getElementById('store_name').value.trim();
            const storeAddress = document.getElementById('store_address').value.trim();
            const quantity = parseInt(document.getElementById('quantity').value) || 0;
            const paymentProof = document.getElementById('payment_proof').files.length;
            
            // 逐一检查每个字段
            if (!storeName) {
                alert('❌ 請填寫 7-11 門市名稱');
                document.getElementById('store_name').focus();
                return false;
            }
            
            if (!storeAddress) {
                alert('❌ 請填寫 7-11 門市地址');
                document.getElementById('store_address').focus();
                return false;
            }
            
            if (quantity < 1) {
                alert('❌ 請填寫數量（至少為 1）');
                document.getElementById('quantity').focus();
                return false;
            }
            
            if (paymentProof === 0) {
                alert('❌ 請上傳付款證明截圖');
                return false;
            }
            
            // 所有验证通过，提交表单
            this.submit();
        });
    </script>
</body>
</html>
