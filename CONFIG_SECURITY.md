# 配置文件安全管理說明

## 📁 配置文件結構

為了保護敏感資訊（如資料庫密碼、Google OAuth 憑證），我們採用了以下配置文件結構：

```
backend/
├── config.php          # 主配置文件（自動載入 config.local.php）
├── config.example.php  # 配置範例文件
└── config.local.php    # 本地配置（包含真實憑證，不提交到 Git）
```

## 🔒 工作原理

1. **config.php** - 主配置文件
   - 自動檢查並載入 `config.local.php`（如果存在）
   - 包含預設/示例配置
   - **可以安全提交到 Git**

2. **config.local.php** - 本地配置（您的真實憑證在這裡）
   - 包含實際的資料庫密碼和 OAuth 憑證
   - 已在 `.gitignore` 中，**不會被提交到 Git**
   - 每個開發者/服務器都有自己的版本

3. **config.example.php** - 配置範例
   - 顯示所有需要配置的項目
   - 新團隊成員可以複製此文件開始配置

## 📝 首次設定步驟

1. **複製範例文件**：
   ```bash
   cp backend/config.example.php backend/config.local.php
   ```

2. **編輯 config.local.php**，填入您的實際憑證：
   - 資料庫密碼
   - Google OAuth Client ID
   - Google OAuth Client Secret

3. **不要將 config.local.php 提交到 Git**（已在 .gitignore 中）

## ✅ 優點

- ✅ 敏感資訊不會被提交到 Git
- ✅ 每個環境可以有不同的配置
- ✅ 團隊成員不會互相覆蓋配置
- ✅ 符合安全最佳實踐
- ✅ GitHub 不會阻止推送

## 🚨 重要提醒

**您的真實憑證已經保存在 `backend/config.local.php` 中**，網站會繼續正常運作。

只是現在您的敏感資訊不會被上傳到 GitHub 了！

## 📋 檢查清單

- [x] `.gitignore` 已創建
- [x] `config.local.php` 包含您的真實憑證
- [x] `config.php` 已更新為自動載入 config.local.php
- [x] `config.example.php` 已創建供參考
- [x] 代碼已推送到 GitHub（不含敏感資訊）

## 🔄 部署到生產環境

當您部署到生產服務器時：

1. 上傳所有代碼文件（不包括 config.local.php）
2. 在服務器上手動創建 `backend/config.local.php`
3. 填入生產環境的憑證
4. 確保文件權限正確（建議 600 或 640）

## 💡 提示

如果您不小心提交了敏感資訊到 Git，GitHub 會檢測到並阻止推送（就像剛才那樣）。這是一個安全功能，保護您的憑證不被公開。
