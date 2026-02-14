<?php
require_once 'backend/config.php';

echo "<h1>正在更新資料庫...</h1>";
echo "<style>body { font-family: Arial; padding: 20px; } .success { color: green; } .error { color: red; }</style>";

try {
    echo "<h2>檢查並添加欄位...</h2>";
    
    // 1. 修改 password 為可選
    try {
        $pdo->exec("ALTER TABLE users MODIFY COLUMN password VARCHAR(255) NULL");
        echo "<p class='success'>✅ password 欄位已設為可選</p>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') === false) {
            echo "<p class='error'>⚠️ password: " . $e->getMessage() . "</p>";
        }
    }
    
    // 2. 添加 google_id
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN google_id VARCHAR(255) NULL UNIQUE AFTER password");
        echo "<p class='success'>✅ 已添加 google_id 欄位</p>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "<p class='success'>✅ google_id 欄位已存在</p>";
        } else {
            echo "<p class='error'>❌ google_id: " . $e->getMessage() . "</p>";
        }
    }
    
    // 3. 添加 avatar
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN avatar VARCHAR(255) NULL AFTER google_id");
        echo "<p class='success'>✅ 已添加 avatar 欄位</p>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "<p class='success'>✅ avatar 欄位已存在</p>";
        } else {
            echo "<p class='error'>❌ avatar: " . $e->getMessage() . "</p>";
        }
    }
    
    // 4. 添加 auth_provider
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN auth_provider ENUM('local', 'google') DEFAULT 'local' AFTER avatar");
        echo "<p class='success'>✅ 已添加 auth_provider 欄位</p>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "<p class='success'>✅ auth_provider 欄位已存在</p>";
        } else {
            echo "<p class='error'>❌ auth_provider: " . $e->getMessage() . "</p>";
        }
    }
    
    // 5. 添加索引
    try {
        $pdo->exec("ALTER TABLE users ADD INDEX idx_google_id (google_id)");
        echo "<p class='success'>✅ 已添加 google_id 索引</p>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key') !== false) {
            echo "<p class='success'>✅ google_id 索引已存在</p>";
        } else {
            echo "<p class='error'>⚠️ 索引: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<h2>資料表結構：</h2>";
    $stmt = $pdo->query("DESCRIBE users");
    $columns = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>欄位</th><th>類型</th><th>可為 NULL</th><th>預設值</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2 class='success'>🎉 資料庫更新完成！</h2>";
    echo "<p>現在可以使用 Google 登入功能了。</p>";
    echo "<p><a href='login.php'>前往登入頁面</a></p>";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "<h2 class='error'>❌ 更新失敗</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
