<?php
require_once 'backend/config.php';

if (!isLoggedIn()) {
    die('請先登入');
}

echo "<h2>🔍 資料庫診斷工具</h2>";
echo "<hr>";

$user_id = $_SESSION['user_id'];

// 1. 檢查資料表結構
echo "<h3>1️⃣ users 資料表結構</h3>";
try {
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; margin-bottom: 20px;'>";
    echo "<tr><th style='padding: 8px;'>欄位名稱</th><th style='padding: 8px;'>類型</th><th style='padding: 8px;'>允許 NULL</th><th style='padding: 8px;'>預設值</th></tr>";
    
    $hasPhone = false;
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td style='padding: 8px;'><strong>" . $col['Field'] . "</strong></td>";
        echo "<td style='padding: 8px;'>" . $col['Type'] . "</td>";
        echo "<td style='padding: 8px;'>" . $col['Null'] . "</td>";
        echo "<td style='padding: 8px;'>" . ($col['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
        
        if ($col['Field'] === 'phone') {
            $hasPhone = true;
        }
    }
    echo "</table>";
    
    if ($hasPhone) {
        echo "✅ <strong style='color: green;'>phone 欄位存在</strong><br><br>";
    } else {
        echo "❌ <strong style='color: red;'>phone 欄位不存在！需要執行 ALTER TABLE</strong><br><br>";
    }
    
} catch (PDOException $e) {
    echo "錯誤：" . $e->getMessage() . "<br><br>";
}

// 2. 查詢當前用戶資料
echo "<h3>2️⃣ 當前用戶資料（user_id = {$user_id}）</h3>";
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "<table border='1' style='border-collapse: collapse; margin-bottom: 20px;'>";
        foreach ($user as $key => $value) {
            if (!is_numeric($key)) {
                echo "<tr>";
                echo "<td style='padding: 8px;'><strong>{$key}</strong></td>";
                echo "<td style='padding: 8px;'>" . ($value ?? '<em style="color: red;">NULL</em>') . "</td>";
                echo "</tr>";
            }
        }
        echo "</table>";
    } else {
        echo "❌ 找不到用戶資料<br><br>";
    }
} catch (PDOException $e) {
    echo "錯誤：" . $e->getMessage() . "<br><br>";
}

// 3. 測試更新
echo "<h3>3️⃣ 測試更新（測試電話：0912345678）</h3>";
try {
    $testPhone = '0912345678';
    
    $stmt = $pdo->prepare("UPDATE users SET phone = ? WHERE id = ?");
    $result = $stmt->execute([$testPhone, $user_id]);
    
    echo "執行結果：" . ($result ? '✅ 成功' : '❌ 失敗') . "<br>";
    echo "影響行數：<strong>" . $stmt->rowCount() . "</strong><br><br>";
    
    // 立即查詢確認
    $checkStmt = $pdo->prepare("SELECT phone FROM users WHERE id = ?");
    $checkStmt->execute([$user_id]);
    $checkData = $checkStmt->fetch();
    
    echo "資料庫中的電話：<strong>" . ($checkData['phone'] ?? 'NULL') . "</strong><br><br>";
    
    if ($checkData['phone'] === $testPhone) {
        echo "✅ <strong style='color: green;'>更新成功！</strong><br>";
    } else {
        echo "❌ <strong style='color: red;'>更新失敗，資料庫未反映變更</strong><br>";
    }
    
} catch (PDOException $e) {
    echo "錯誤：" . $e->getMessage() . "<br><br>";
}

// 4. PDO 設定檢查
echo "<h3>4️⃣ PDO 連線設定</h3>";
echo "資料庫主機：" . DB_HOST . "<br>";
echo "資料庫名稱：" . DB_NAME . "<br>";
echo "字元編碼：" . DB_CHARSET . "<br>";
echo "自動提交：" . ($pdo->getAttribute(PDO::ATTR_AUTOCOMMIT) ? '✅ 啟用' : '❌ 停用') . "<br>";
echo "錯誤模式：" . $pdo->getAttribute(PDO::ATTR_ERRMODE) . "<br>";

echo "<hr>";
echo "<a href='profile.php'>← 返回個人資料</a>";
?>
