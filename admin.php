<?php
require_once 'backend/config.php';

// 管理員登入檢查
if (!isAdmin()) {
    // 如果還沒登入，顯示登入表單
    $error = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = cleanInput($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (!empty($username) && !empty($password)) {
            try {
                $stmt = $pdo->prepare("SELECT id, username, password FROM admins WHERE username = ?");
                $stmt->execute([$username]);
                $admin = $stmt->fetch();
                
                if ($admin && password_verify($password, $admin['password'])) {
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_username'] = $admin['username'];
                    header('Location: admin.php');
                    exit;
                } else {
                    $error = '帳號或密碼錯誤';
                }
            } catch (PDOException $e) {
                $error = '登入失敗';
            }
        } else {
            $error = '請輸入帳號和密碼';
        }
    }
    ?>
    <!doctype html>
    <html lang="zh-Hant">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>管理員登入</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body class="admin-login-body">
        <div class="login-box">
            <h1 class="login-title">🔐 管理員登入</h1>
            <?php if ($error): ?>
                <div class="form-error"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">帳號</label>
                    <input type="text" name="username" class="form-input" required>
                </div>
                <div class="form-group">
                    <label class="form-label">密碼</label>
                    <input type="password" name="password" class="form-input" required>
                </div>
                <button type="submit" class="form-button">登入</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// 處理訂單狀態更新
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = cleanInput($_POST['status']);
    
    $stmt = $pdo->prepare("UPDATE preorders SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $order_id]);
}

// 登出
if (isset($_GET['logout'])) {
    unset($_SESSION['admin_id']);
    unset($_SESSION['admin_username']);
    header('Location: admin.php');
    exit;
}

// 取得所有訂單
$filter = $_GET['filter'] ?? 'all';
$sql = "SELECT * FROM preorders";
if ($filter !== 'all') {
    $sql .= " WHERE status = :status";
}
$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
if ($filter !== 'all') {
    $stmt->execute(['status' => $filter]);
} else {
    $stmt->execute();
}
$orders = $stmt->fetchAll();

// 統計
$stats = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
        SUM(CASE WHEN status = 'shipped' THEN 1 ELSE 0 END) as shipped,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(quantity) as total_quantity,
        SUM(quantity * 100) as total_revenue
    FROM preorders
")->fetch();
?>
<!doctype html>
<html lang="zh-Hant">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>訂單管理後台</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="admin-body">
    <div class="admin-container">
        <div class="admin-header">
            <h1 class="admin-title">📊 訂單管理後台</h1>
            <a href="?logout=1" class="logout-btn">登出</a>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">總訂單數</div>
                <div class="stat-value"><?php echo $stats['total']; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">待處理</div>
                <div class="stat-value"><?php echo $stats['pending']; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">已確認</div>
                <div class="stat-value"><?php echo $stats['confirmed']; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">已出貨</div>
                <div class="stat-value"><?php echo $stats['shipped']; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">總鑰匙圈數</div>
                <div class="stat-value"><?php echo $stats['total_quantity']; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">總營收</div>
                <div class="stat-value">$<?php echo number_format($stats['total_revenue']); ?></div>
            </div>
        </div>
        
        <div class="filter-bar">
            <a href="?filter=all" class="filter-btn <?php echo $filter === 'all' ? 'active' : ''; ?>">全部</a>
            <a href="?filter=pending" class="filter-btn <?php echo $filter === 'pending' ? 'active' : ''; ?>">待處理</a>
            <a href="?filter=confirmed" class="filter-btn <?php echo $filter === 'confirmed' ? 'active' : ''; ?>">已確認</a>
            <a href="?filter=shipped" class="filter-btn <?php echo $filter === 'shipped' ? 'active' : ''; ?>">已出貨</a>
            <a href="?filter=completed" class="filter-btn <?php echo $filter === 'completed' ? 'active' : ''; ?>">已完成</a>
            <a href="?filter=cancelled" class="filter-btn <?php echo $filter === 'cancelled' ? 'active' : ''; ?>">已取消</a>
        </div>
        
        <div class="orders-table">
            <table>
                <thead>
                    <tr>
                        <th>訂單編號</th>
                        <th>會員</th>
                        <th>收件人</th>
                        <th>電話</th>
                        <th>門市</th>
                        <th>數量</th>
                        <th>金額</th>
                        <th>付款證明</th>
                        <th>狀態</th>
                        <th>訂購時間</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td>#<?php echo $order['id']; ?></td>
                        <td><?php echo htmlspecialchars($order['username']); ?></td>
                        <td><?php echo htmlspecialchars($order['recipient_name']); ?></td>
                        <td><?php echo htmlspecialchars($order['phone']); ?></td>
                        <td>
                            <?php echo htmlspecialchars($order['store_name']); ?><br>
                            <small style="opacity: 0.7;"><?php echo htmlspecialchars($order['store_address']); ?></small>
                        </td>
                        <td><?php echo $order['quantity']; ?></td>
                        <td>$<?php echo number_format($order['total_price']); ?></td>
                        <td>
                            <?php if ($order['payment_proof']): ?>
                                <a href="uploads/<?php echo $order['payment_proof']; ?>" target="_blank" class="view-img">查看</a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                <?php 
                                    $statusText = [
                                        'pending' => '待處理',
                                        'confirmed' => '已確認',
                                        'shipped' => '已出貨',
                                        'completed' => '已完成',
                                        'cancelled' => '已取消'
                                    ];
                                    echo $statusText[$order['status']];
                                ?>
                            </span>
                        </td>
                        <td><?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?></td>
                        <td>
                            <form method="POST" style="display: inline-flex; align-items: center;">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <select name="status" class="status-select">
                                    <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>待處理</option>
                                    <option value="confirmed" <?php echo $order['status'] === 'confirmed' ? 'selected' : ''; ?>>已確認</option>
                                    <option value="shipped" <?php echo $order['status'] === 'shipped' ? 'selected' : ''; ?>>已出貨</option>
                                    <option value="completed" <?php echo $order['status'] === 'completed' ? 'selected' : ''; ?>>已完成</option>
                                    <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>已取消</option>
                                </select>
                                <button type="submit" name="update_status" class="update-btn">更新</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="11" style="text-align: center; padding: 40px; color: rgba(255,255,255,0.5);">
                            目前沒有訂單
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
