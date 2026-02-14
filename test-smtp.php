<?php
/**
 * 測試 SMTP 連線和郵件發送
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/vendor/PHPMailer/src/Exception.php';
require_once __DIR__ . '/vendor/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/vendor/PHPMailer/src/SMTP.php';

// SMTP 設定
$smtp_host = 'smtp.gmail.com';
$smtp_port = 587;
$smtp_username = 'aacindy1026@gmail.com';
$smtp_password = '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'; // 這看起來不對，應該是 16 位數的應用程式密碼
$test_email = 'aacindy1026@gmail.com'; // 測試收件者

echo "<h2>📧 SMTP 測試工具</h2>";
echo "<hr>";

$mail = new PHPMailer(true);

try {
    // 啟用詳細偵錯輸出
    $mail->SMTPDebug = 2; // 顯示詳細錯誤
    $mail->Debugoutput = 'html';
    
    echo "<h3>1️⃣ 設定 SMTP...</h3>";
    $mail->isSMTP();
    $mail->Host = $smtp_host;
    $mail->SMTPAuth = true;
    $mail->Username = $smtp_username;
    $mail->Password = $smtp_password;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = $smtp_port;
    $mail->CharSet = 'UTF-8';
    echo "✅ SMTP 設定完成<br><br>";
    
    echo "<h3>2️⃣ 設定郵件內容...</h3>";
    $mail->setFrom($smtp_username, '柒柒 chi');
    $mail->addAddress($test_email, '測試用戶');
    $mail->Subject = '測試郵件 - ' . date('Y-m-d H:i:s');
    $mail->Body = '<h1>測試成功！</h1><p>如果您收到這封郵件，表示 SMTP 設定正確。</p>';
    $mail->isHTML(true);
    echo "✅ 郵件內容設定完成<br><br>";
    
    echo "<h3>3️⃣ 發送郵件...</h3>";
    $mail->send();
    
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<strong>✅ 測試成功！</strong><br>";
    echo "郵件已成功發送到 {$test_email}<br>";
    echo "請檢查您的收件匣（也檢查垃圾郵件）";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<strong>❌ 發送失敗</strong><br><br>";
    echo "<strong>錯誤訊息：</strong><br>";
    echo nl2br(htmlspecialchars($mail->ErrorInfo));
    echo "</div>";
    
    echo "<div style='background: #fff3cd; border: 1px solid #ffeeba; color: #856404; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<strong>💡 常見問題：</strong><br><br>";
    echo "<ol>";
    echo "<li><strong>密碼格式錯誤</strong><br>";
    echo "您的密碼看起來像是 OAuth Client Secret（GOCSPX-...）<br>";
    echo "應該使用 Gmail <strong>應用程式密碼</strong>（16 位數字母，例如：abcdefghijklmnop）<br>";
    echo "取得方式：<a href='https://myaccount.google.com/apppasswords' target='_blank'>https://myaccount.google.com/apppasswords</a><br><br></li>";
    
    echo "<li><strong>兩步驟驗證未啟用</strong><br>";
    echo "Gmail 應用程式密碼需要先啟用兩步驟驗證<br>";
    echo "設定位置：<a href='https://myaccount.google.com/security' target='_blank'>https://myaccount.google.com/security</a><br><br></li>";
    
    echo "<li><strong>安全性設定</strong><br>";
    echo "Gmail 可能封鎖了不安全的應用程式<br>";
    echo "請確認已使用應用程式密碼，而非帳號密碼</li>";
    echo "</ol>";
    echo "</div>";
}

echo "<hr>";
echo "<h3>🔧 設定檢查</h3>";
echo "<table style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #f8f9fa;'><td style='padding: 8px; border: 1px solid #dee2e6;'><strong>SMTP 主機</strong></td><td style='padding: 8px; border: 1px solid #dee2e6;'>{$smtp_host}</td></tr>";
echo "<tr><td style='padding: 8px; border: 1px solid #dee2e6;'><strong>SMTP 連接埠</strong></td><td style='padding: 8px; border: 1px solid #dee2e6;'>{$smtp_port}</td></tr>";
echo "<tr style='background: #f8f9fa;'><td style='padding: 8px; border: 1px solid #dee2e6;'><strong>使用者名稱</strong></td><td style='padding: 8px; border: 1px solid #dee2e6;'>{$smtp_username}</td></tr>";
echo "<tr><td style='padding: 8px; border: 1px solid #dee2e6;'><strong>密碼格式</strong></td><td style='padding: 8px; border: 1px solid #dee2e6;'>";
if (preg_match('/^[a-z]{16}$/', $smtp_password)) {
    echo "✅ 正確（16 位小寫字母）";
} elseif (strpos($smtp_password, 'GOCSPX-') === 0) {
    echo "❌ <strong>錯誤！這是 OAuth Client Secret，不是應用程式密碼</strong>";
} else {
    echo "⚠️ 格式不符（應為 16 位字母數字，無空格）";
}
echo "</td></tr>";
echo "<tr style='background: #f8f9fa;'><td style='padding: 8px; border: 1px solid #dee2e6;'><strong>收件者</strong></td><td style='padding: 8px; border: 1px solid #dee2e6;'>{$test_email}</td></tr>";
echo "</table>";
?>
