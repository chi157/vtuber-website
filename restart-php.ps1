# 重啟 PHP-CGI
Write-Host "正在停止 PHP-CGI..." -ForegroundColor Yellow

# 停止所有 PHP-CGI 進程
Get-Process php-cgi -ErrorAction SilentlyContinue | Stop-Process -Force

Start-Sleep -Seconds 1

# 重新啟動
Write-Host "正在重新啟動 PHP-CGI..." -ForegroundColor Green
$phpPath = "C:\php\php-cgi.exe"
Start-Process -FilePath $phpPath -ArgumentList "-b 127.0.0.1:9000" -WindowStyle Hidden

Start-Sleep -Seconds 2

# 檢查是否成功
$process = Get-Process php-cgi -ErrorAction SilentlyContinue
if ($null -ne $process) {
    Write-Host "✅ PHP-CGI 已成功重啟！" -ForegroundColor Green
} else {
    Write-Host "❌ PHP-CGI 重啟失敗" -ForegroundColor Red
}

Write-Host "`n按任意鍵繼續..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
