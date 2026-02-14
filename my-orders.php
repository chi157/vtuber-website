<?php
require_once 'backend/config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user = getCurrentUser();
$message = '';
$error = '';

// 處理取消訂單
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    $order_id = (int)$_POST['order_id'];
    
    try {
        // 檢查訂單是否屬於當前用戶且狀態允許取消
        $stmt = $pdo->prepare("
            SELECT id, status FROM preorders 
            WHERE id = ? AND user_id = ? AND status IN ('pending', 'confirmed')
        ");
        $stmt->execute([$order_id, $user['id']]);
        $order = $stmt->fetch();
        
        if ($order) {
            // 更新訂單狀態為已取消
            $stmt = $pdo->prepare("UPDATE preorders SET status = 'cancelled' WHERE id = ?");
            $stmt->execute([$order_id]);
            $message = '訂單 #' . $order_id . ' 已成功取消';
        } else {
            $error = '無法取消此訂單';
        }
    } catch (PDOException $e) {
        $error = '取消訂單失敗，請稍後再試';
    }
}

// 查詢使用者的訂單
try {
    $stmt = $pdo->prepare("
        SELECT * FROM preorders 
        WHERE user_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$user['id']]);
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    $orders = [];
}

// 狀態翻譯
function getStatusText($status) {
    $statusMap = [
        'pending' => '待處理',
        'confirmed' => '已確認',
        'shipped' => '已出貨',
        'completed' => '已完成',
        'cancelled' => '已取消'
    ];
    return $statusMap[$status] ?? $status;
}

function getStatusColor($status) {
    $colorMap = [
        'pending' => '#fbbf24',
        'confirmed' => '#60a5fa',
        'shipped' => '#a78bfa',
        'completed' => '#34d399',
        'cancelled' => '#f87171'
    ];
    return $colorMap[$status] ?? '#6b7280';
}
?>
<!doctype html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>我的訂單 - 柒柒 chi</title>
    <link rel="icon" type="image/png" href="images/頭貼%20-%20圓形.png">
    <link rel="stylesheet" href="style.css">
    <script src="navbar.js" defer></script>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="cloud cloud--1" aria-hidden="true"></div>
    <div class="cloud cloud--2" aria-hidden="true"></div>
    <div class="cloud cloud--3" aria-hidden="true"></div>
    <div class="cloud cloud--4" aria-hidden="true"></div>
    
    <main class="page">
        <div style="max-width: 1200px; margin: 0 auto; padding: 40px 20px;">
            <h1 style="color: #7dd3fc; font-size: 32px; margin-bottom: 8px;">📦 我的訂單</h1>
            <p style="color: rgba(255,255,255,0.6); margin-bottom: 32px;">
                歡迎，<?php echo htmlspecialchars($user['username']); ?>！
            </p>
            
            <?php if ($message): ?>
                <div style="background: rgba(34, 197, 94, 0.2); border: 1px solid rgba(34, 197, 94, 0.4); color: #6ee7b7; padding: 16px; border-radius: 8px; margin-bottom: 24px;">
                    ✅ <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div style="background: rgba(239, 68, 68, 0.2); border: 1px solid rgba(239, 68, 68, 0.4); color: #fca5a5; padding: 16px; border-radius: 8px; margin-bottom: 24px;">
                    ❌ <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if (empty($orders)): ?>
                <div style="background: linear-gradient(180deg, rgba(35, 58, 94, 0.95) 0%, rgba(46, 67, 114, 0.95) 100%); border: 1.5px solid rgba(58, 123, 213, 0.5); border-radius: 20px; padding: 60px 20px; text-align: center; backdrop-filter: blur(15px); box-shadow: 0 18px 40px rgba(10, 24, 51, 0.4);">
                    <div style="font-size: 64px; margin-bottom: 16px;">📭</div>
                    <h2 style="color: #7dd3fc; font-size: 24px; margin-bottom: 12px;">尚無訂單</h2>
                    <p style="color: rgba(255,255,255,0.6); margin-bottom: 24px;">您還沒有任何預購訂單</p>
                    <a href="keychain.html" style="display: inline-block; padding: 12px 24px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 8px; font-weight: 600; transition: transform 0.3s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                        前往預購
                    </a>
                </div>
            <?php else: ?>
                <div style="display: grid; gap: 20px;">
                    <?php foreach ($orders as $order): ?>
                        <div style="background: linear-gradient(180deg, rgba(35, 58, 94, 0.95) 0%, rgba(46, 67, 114, 0.95) 100%); border: 1.5px solid rgba(58, 123, 213, 0.5); border-radius: 20px; padding: 24px; backdrop-filter: blur(15px); box-shadow: 0 18px 40px rgba(10, 24, 51, 0.4);">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 16px;">
                                <div style="display: flex; align-items: center; gap: 24px; flex: 1; flex-wrap: wrap;">
                                    <div>
                                        <h3 style="color: #7dd3fc; font-size: 18px; margin-bottom: 4px;">
                                            訂單 #<?php echo $order['id']; ?>
                                        </h3>
                                        <p style="color: rgba(255,255,255,0.5); font-size: 13px;">
                                            <?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?>
                                        </p>
                                    </div>
                                    
                                    <div style="height: 40px; width: 1px; background: rgba(125, 211, 252, 0.2);"></div>
                                    
                                    <div>
                                        <p style="color: rgba(255,255,255,0.6); font-size: 12px; margin-bottom: 2px;">收件人</p>
                                        <p style="color: white; font-size: 15px; font-weight: 500;"><?php echo htmlspecialchars($order['recipient_name']); ?></p>
                                    </div>
                                    
                                    <div>
                                        <p style="color: rgba(255,255,255,0.6); font-size: 12px; margin-bottom: 2px;">電話</p>
                                        <p style="color: white; font-size: 15px;"><?php echo htmlspecialchars($order['phone']); ?></p>
                                    </div>
                                    
                                    <div>
                                        <p style="color: rgba(255,255,255,0.6); font-size: 12px; margin-bottom: 2px;">數量</p>
                                        <p style="color: white; font-size: 15px; font-weight: 500;"><?php echo $order['quantity']; ?> 個</p>
                                    </div>
                                    
                                    <div>
                                        <p style="color: rgba(255,255,255,0.6); font-size: 12px; margin-bottom: 2px;">總金額</p>
                                        <p style="color: #34d399; font-size: 18px; font-weight: 600;">NT$ <?php echo number_format($order['total_price']); ?></p>
                                    </div>
                                </div>
                                
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div style="background: <?php echo getStatusColor($order['status']); ?>; color: white; padding: 8px 20px; border-radius: 20px; font-size: 14px; font-weight: 600; white-space: nowrap;">
                                        <?php echo getStatusText($order['status']); ?>
                                    </div>
                                    
                                    <?php if (in_array($order['status'], ['pending', 'confirmed'])): ?>
                                        <form method="POST" action="" onsubmit="return confirm('確定要取消此訂單嗎？\n取消後需等待管理員處理退款');" style="margin: 0;">
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <button type="submit" name="cancel_order" style="padding: 8px 16px; background: rgba(239, 68, 68, 0.2); border: 1px solid rgba(239, 68, 68, 0.4); color: #fca5a5; border-radius: 6px; font-size: 13px; cursor: pointer; transition: all 0.3s; white-space: nowrap;" onmouseover="this.style.background='rgba(239, 68, 68, 0.3)'" onmouseout="this.style.background='rgba(239, 68, 68, 0.2)'">
                                                ❌ 取消
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 24px; flex-wrap: wrap; padding-top: 16px; border-top: 1px solid rgba(125, 211, 252, 0.1);">
                                <div style="flex: 1; min-width: 250px;">
                                    <p style="color: rgba(255,255,255,0.6); font-size: 13px; margin-bottom: 6px;">📍 配送門市</p>
                                    <p style="color: white; font-size: 15px; margin-bottom: 4px; font-weight: 500;">
                                        <?php echo htmlspecialchars($order['store_name']); ?>
                                    </p>
                                    <p style="color: rgba(255,255,255,0.7); font-size: 14px;">
                                        <?php echo htmlspecialchars($order['store_address']); ?>
                                    </p>
                                </div>
                                
                                <?php if (!empty($order['notes'])): ?>
                                <div style="flex: 1; min-width: 200px;">
                                    <p style="color: rgba(255,255,255,0.6); font-size: 13px; margin-bottom: 6px;">📝 備註</p>
                                    <p style="color: rgba(255,255,255,0.8); font-size: 14px;"><?php echo nl2br(htmlspecialchars($order['notes'])); ?></p>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($order['payment_proof']): ?>
                                <div style="min-width: 150px;">
                                    <p style="color: rgba(255,255,255,0.6); font-size: 13px; margin-bottom: 6px;">💳 付款證明</p>
                                    <a href="uploads/<?php echo htmlspecialchars($order['payment_proof']); ?>" target="_blank" style="display: inline-block; padding: 8px 16px; background: rgba(59, 130, 246, 0.2); border: 1px solid rgba(59, 130, 246, 0.4); color: #60a5fa; text-decoration: none; border-radius: 6px; font-size: 14px; transition: all 0.3s; white-space: nowrap;" onmouseover="this.style.background='rgba(59, 130, 246, 0.3)'" onmouseout="this.style.background='rgba(59, 130, 246, 0.2)'">
                                        🖼️ 查看
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <div style="margin-top: 32px; text-align: center;">
                <a href="profile.php" style="color: #60a5fa; text-decoration: none; margin-right: 16px;">← 返回個人資料</a>
                <a href="keychain.html" style="color: #60a5fa; text-decoration: none;">繼續預購</a>
            </div>
        </div>
    </main>
    
    <script src="script.js"></script>
</body>
</html>
