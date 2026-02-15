# VTuber Viewer Ranking System

🏆 自動追蹤 Twitch 直播觀眾出席、計算連續觀看天數的排行榜系統。

## 功能特色

- ✅ 自動追蹤直播出席
- ✅ 計算連續觀看天數 (Streak)
- ✅ 累計觀看場數統計
- ✅ 網站排行榜顯示
- ✅ Twitch 聊天室指令 (`!rank`, `!top`)
- ✅ 完整 Docker 部署

## 系統架構

```
Twitch Chat
     ↓
Node.js Twitch Bot (tmi.js)
     ↓ REST API
Flask Backend API (SQLAlchemy)
     ↓
PostgreSQL Database
     ↓
Flask 提供排行榜 API
     ↓
網站前端顯示
```

## 技術棧

| 組件 | 技術 |
|------|------|
| Twitch Bot | Node.js 18+, tmi.js, axios |
| Backend API | Python 3.10+, Flask, SQLAlchemy, Flask-Migrate |
| Database | PostgreSQL 15 |
| Reverse Proxy | Nginx |
| 部署 | Docker + Docker Compose |
| HTTPS | Cloudflare |

## 快速開始

### 1. 複製環境變數

```bash
cp .env.example .env
```

### 2. 編輯 .env 檔案

填入以下資訊：

- `POSTGRES_PASSWORD` - 資料庫密碼
- `SECRET_KEY` - Flask 密鑰
- `API_KEY` - 內部 API 認證金鑰
- `TWITCH_BOT_USERNAME` - Bot 的 Twitch 帳號
- `TWITCH_OAUTH_TOKEN` - OAuth Token ([取得方式](https://twitchapps.com/tmi/))
- `TWITCH_CHANNELS` - 要加入的頻道

### 3. 啟動服務

```bash
# 建置並啟動所有服務
docker-compose up -d --build

# 查看日誌
docker-compose logs -f

# 初始化資料庫
docker-compose exec flask-api flask db init
docker-compose exec flask-api flask db migrate -m "Initial migration"
docker-compose exec flask-api flask db upgrade
```

### 4. 驗證服務

```bash
# 檢查健康狀態
curl http://localhost/health

# 檢查排行榜 API
curl http://localhost/api/ranking/streak?limit=5
```

## API 文件

### 記錄出席 (Bot 使用)

```http
POST /api/attendance
X-API-Key: your-api-key

{
    "twitch_user_id": "123456",
    "username": "viewer_name",
    "stream_id": "current_stream_id"
}
```

### 取得連續觀看排行榜

```http
GET /api/ranking/streak?limit=10
```

回應：
```json
[
    {"rank": 1, "username": "aaa", "current_streak": 25},
    {"rank": 2, "username": "bbb", "current_streak": 21}
]
```

### 取得累計場數排行榜

```http
GET /api/ranking/total?limit=10
```

### 查詢個人排名

```http
GET /api/user/{twitch_user_id}
```

回應：
```json
{
    "username": "viewer_name",
    "rank_streak": 5,
    "rank_total": 3,
    "current_streak": 12,
    "max_streak": 18,
    "total_sessions": 38
}
```

### 取得系統統計

```http
GET /api/stats
```

## Twitch Bot 指令

| 指令 | 說明 |
|------|------|
| `!rank` | 查詢自己的排名與統計 |
| `!top` | 顯示前三名 |
| `!mystats` | 顯示詳細個人統計 |
| `!setstream <id>` | 設定 Stream ID (僅限 Mod) |
| `!endstream` | 結束當前場次 (僅限 Mod) |

## 資料庫 Schema

### users 表

| 欄位 | 型別 | 說明 |
|------|------|------|
| id | SERIAL PK | |
| twitch_user_id | VARCHAR UNIQUE | Twitch 用戶 ID |
| username | VARCHAR | 顯示名稱 |
| current_streak | INTEGER | 目前連續天數 |
| max_streak | INTEGER | 最高連續天數 |
| total_sessions | INTEGER | 累計觀看場數 |
| last_attendance_date | TIMESTAMP | 最後出席日期與時間 |

### sessions 表

| 欄位 | 型別 | 說明 |
|------|------|------|
| id | SERIAL PK | |
| twitch_stream_id | VARCHAR UNIQUE | Twitch Stream ID |
| started_at | TIMESTAMP | 開始時間 |
| ended_at | TIMESTAMP | 結束時間 |
| stream_date | TIMESTAMP | 直播日期與時間 |

### attendances 表

| 欄位 | 型別 | 說明 |
|------|------|------|
| id | SERIAL PK | |
| user_id | FK | 關聯 users.id |
| session_id | FK | 關聯 sessions.id |
| created_at | TIMESTAMP | 記錄時間 |

Unique Constraint: `(user_id, session_id)`

## 連續天數計算邏輯

```
若今天出席 AND 昨天出席 → streak +1
若今天出席 AND 昨天未出席 → streak = 1
若今天未出席 → streak = 0
```

- 以 UTC 日期為準
- 當天只要出席任一場 session 即算當日出席
- 同一場重複發言只記錄一次

## 目錄結構

```
ranking_system/
├── docker-compose.yml      # Docker Compose 配置
├── .env.example           # 環境變數範本
├── init.sql               # PostgreSQL 初始化腳本
│
├── flask-api/             # Flask Backend
│   ├── Dockerfile
│   ├── requirements.txt
│   ├── app.py             # 主應用程式
│   ├── models.py          # 資料庫模型
│   ├── config.py          # 配置檔
│   └── wsgi.py            # WSGI 入口
│
├── twitch-bot/            # Node.js Twitch Bot
│   ├── Dockerfile
│   ├── package.json
│   ├── index.js           # Bot 主程式
│   └── .env.example
│
├── nginx/                 # Nginx 配置
│   ├── nginx.conf
│   ├── conf.d/
│   │   └── default.conf
│   └── ssl/               # SSL 憑證
│
└── static/                # 前端靜態檔案
    ├── index.html
    ├── styles.css
    └── ranking.js
```

## 生產環境部署

### 1. 設定 SSL

使用 Cloudflare：
1. 在 Cloudflare 建立 Origin Certificate
2. 將憑證放入 `nginx/ssl/` 目錄
3. 取消註解 `nginx/conf.d/default.conf` 中的 HTTPS 區塊

### 2. 設定防火牆

```bash
# UFW
sudo ufw allow 22
sudo ufw allow 80
sudo ufw allow 443
sudo ufw enable
```

### 3. 部署

```bash
# 拉取最新程式碼
git pull origin main

# 重建並重啟
docker-compose down
docker-compose up -d --build
```

## 開發環境

### 本地開發 Flask API

```bash
cd flask-api
python -m venv venv
source venv/bin/activate  # Windows: venv\Scripts\activate
pip install -r requirements.txt

# 設定環境變數
export DATABASE_URL=postgresql://user:pass@localhost:5432/ranking_db
export FLASK_ENV=development

# 啟動開發伺服器
flask run --debug
```

### 本地開發 Twitch Bot

```bash
cd twitch-bot
npm install

# 複製並編輯 .env
cp .env.example .env

# 啟動開發模式
npm run dev
```

## 疑難排解

### Bot 無法連線到 API

檢查 Docker 網路：
```bash
docker network ls
docker-compose exec twitch-bot ping flask-api
```

### 資料庫連線錯誤

```bash
# 檢查 PostgreSQL 是否正常
docker-compose logs postgres

# 進入資料庫
docker-compose exec postgres psql -U ranking_user -d ranking_db
```

### 重置資料庫

```bash
docker-compose down -v  # 刪除所有 volumes
docker-compose up -d
docker-compose exec flask-api flask db upgrade
```

## License

MIT License
