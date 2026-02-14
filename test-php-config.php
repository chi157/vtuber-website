<?php
// 測試 PHP HTTPS 和擴展支持

echo "<h1>PHP 配置檢查</h1>";
echo "<style>body { font-family: Arial; padding: 20px; } .success { color: green; } .error { color: red; } .warning { color: orange; }</style>";

// 檢查 OpenSSL
echo "<h2>1. OpenSSL 擴展</h2>";
if (extension_loaded('openssl')) {
    echo "<p class='success'>✅ OpenSSL 已啟用</p>";
} else {
    echo "<p class='error'>❌ OpenSSL 未啟用</p>";
    echo "<p>請在 php.ini 中啟用：<code>extension=openssl</code></p>";
}

// 檢查 cURL
echo "<h2>2. cURL 擴展</h2>";
if (extension_loaded('curl')) {
    echo "<p class='success'>✅ cURL 已啟用</p>";
} else {
    echo "<p class='warning'>⚠️ cURL 未啟用（可選）</p>";
}

// 檢查 HTTPS wrapper
echo "<h2>3. HTTPS Wrapper</h2>";
$wrappers = stream_get_wrappers();
if (in_array('https', $wrappers)) {
    echo "<p class='success'>✅ HTTPS wrapper 可用</p>";
    
    // 測試 HTTPS 連接
    echo "<h3>測試 HTTPS 連接...</h3>";
    $context = stream_context_create(['http' => ['timeout' => 5]]);
    $result = @file_get_contents('https://www.google.com', false, $context);
    if ($result !== false) {
        echo "<p class='success'>✅ 可以訪問 HTTPS 網站</p>";
    } else {
        echo "<p class='error'>❌ 無法訪問 HTTPS 網站</p>";
    }
} else {
    echo "<p class='error'>❌ HTTPS wrapper 不可用</p>";
}

// 顯示 PHP 配置文件位置
echo "<h2>4. PHP 配置</h2>";
echo "<p><strong>php.ini 位置：</strong></p>";
echo "<pre>" . php_ini_loaded_file() . "</pre>";

echo "<p><strong>已加載的擴展：</strong></p>";
$loaded_extensions = get_loaded_extensions();
sort($loaded_extensions);
echo "<pre>" . implode("\n", $loaded_extensions) . "</pre>";

echo "<hr>";
echo "<h2>解決方案</h2>";
if (!extension_loaded('openssl')) {
    echo "<ol>";
    echo "<li>打開 php.ini 文件（位置見上方）</li>";
    echo "<li>找到 <code>;extension=openssl</code></li>";
    echo "<li>移除前面的分號改為 <code>extension=openssl</code></li>";
    echo "<li>保存並重啟 Web 服務器/PHP</li>";
    echo "<li>重新載入此頁面確認</li>";
    echo "</ol>";
}
?>
