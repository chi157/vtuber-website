# 啟動 PHP-CGI
$phpPath = "C:\php\php-cgi.exe"  # 修改為你的 PHP 路徑，如果使用 XAMPP 改為 C:\xampp\php\php-cgi.exe

$process = Get-Process php-cgi -ErrorAction SilentlyContinue

if ($null -eq $process) {
    Write-Host "正在啟動 PHP-CGI..." -ForegroundColor Green
    Start-Process -FilePath $phpPath -ArgumentList "-b 127.0.0.1:9000" -WindowStyle Hidden
    Start-Sleep -Seconds 2
    
    $newProcess = Get-Process php-cgi -ErrorAction SilentlyContinue
    if ($null -ne $newProcess) {
        Write-Host "✅ PHP-CGI 已成功啟動在 127.0.0.1:9000" -ForegroundColor Green
    } else {
        Write-Host "❌ PHP-CGI 啟動失敗，請檢查 PHP 路徑" -ForegroundColor Red
    }
} else {
    Write-Host "✅ PHP-CGI 已經在運行中" -ForegroundColor Yellow
}

Write-Host "`n按任意鍵繼續..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
