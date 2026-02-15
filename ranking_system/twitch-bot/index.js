/**
 * VTuber Viewer Ranking System - Twitch Bot
 * 
 * 功能：
 * 1. 監聽聊天室訊息，記錄觀眾出席
 * 2. !rank - 查詢個人排名
 * 3. !top - 顯示前三名
 */

require('dotenv').config();
const tmi = require('tmi.js');
const axios = require('axios');

// ============================================
// 配置
// ============================================

const config = {
    // Twitch Bot 設定
    twitch: {
        username: process.env.TWITCH_BOT_USERNAME,
        token: process.env.TWITCH_OAUTH_TOKEN,  // oauth:xxxxx 格式
        channels: process.env.TWITCH_CHANNELS?.split(',') || []
    },
    
    // Flask API 設定
    api: {
        baseUrl: process.env.API_BASE_URL || 'http://flask-api:5000',
        key: process.env.API_KEY || 'your-api-key-here'
    },
    
    // 目前的 Stream ID（由 EventSub 或手動設定）
    currentStreamId: process.env.CURRENT_STREAM_ID || null
};

// API 客戶端
const apiClient = axios.create({
    baseURL: config.api.baseUrl,
    timeout: 5000,
    headers: {
        'Content-Type': 'application/json',
        'X-API-Key': config.api.key
    }
});

// 追蹤目前的 Stream ID
let currentStreamId = config.currentStreamId;

// 用於防止短時間內重複呼叫 API（同一用戶）
const recentMessages = new Map();
const MESSAGE_COOLDOWN = 60000; // 60 秒冷卻

// ============================================
// Twitch Client 初始化
// ============================================

const client = new tmi.Client({
    options: { debug: process.env.DEBUG === 'true' },
    connection: {
        reconnect: true,
        secure: true
    },
    identity: {
        username: config.twitch.username,
        password: config.twitch.token
    },
    channels: config.twitch.channels
});

// ============================================
// 輔助函數
// ============================================

/**
 * 記錄出席到 Flask API
 */
async function recordAttendance(userId, username, streamId) {
    try {
        const response = await apiClient.post('/api/attendance', {
            twitch_user_id: userId,
            username: username,
            stream_id: streamId
        });
        
        console.log(`[Attendance] Recorded: ${username} (${userId}) - ${response.data.status}`);
        return response.data;
    } catch (error) {
        console.error(`[Attendance] Error for ${username}:`, error.message);
        return null;
    }
}

/**
 * 取得用戶排名資訊
 */
async function getUserRank(userId) {
    try {
        const response = await apiClient.get(`/api/user/${userId}`);
        return response.data;
    } catch (error) {
        if (error.response?.status === 404) {
            return null;
        }
        console.error(`[GetUser] Error:`, error.message);
        return null;
    }
}

/**
 * 取得排行榜前 N 名
 */
async function getTopRanking(limit = 3) {
    try {
        const response = await apiClient.get(`/api/ranking/streak?limit=${limit}`);
        return response.data;
    } catch (error) {
        console.error(`[GetRanking] Error:`, error.message);
        return [];
    }
}

/**
 * 設定新的 Stream ID
 */
async function startNewStream(streamId) {
    try {
        const response = await apiClient.post('/api/session/start', {
            stream_id: streamId
        });
        
        currentStreamId = streamId;
        console.log(`[Session] Started new stream: ${streamId}`);
        return response.data;
    } catch (error) {
        console.error(`[Session] Error starting stream:`, error.message);
        return null;
    }
}

/**
 * 結束當前 Stream
 */
async function endCurrentStream() {
    if (!currentStreamId) return;
    
    try {
        const response = await apiClient.post('/api/session/end', {
            stream_id: currentStreamId
        });
        
        console.log(`[Session] Ended stream: ${currentStreamId}`);
        currentStreamId = null;
        return response.data;
    } catch (error) {
        console.error(`[Session] Error ending stream:`, error.message);
        return null;
    }
}

/**
 * 檢查是否應該處理此訊息（防止過於頻繁的 API 呼叫）
 */
function shouldProcessMessage(userId) {
    const lastTime = recentMessages.get(userId);
    const now = Date.now();
    
    if (lastTime && (now - lastTime) < MESSAGE_COOLDOWN) {
        return false;
    }
    
    recentMessages.set(userId, now);
    return true;
}

/**
 * 清理過期的訊息記錄
 */
function cleanupRecentMessages() {
    const now = Date.now();
    for (const [userId, time] of recentMessages.entries()) {
        if (now - time > MESSAGE_COOLDOWN * 2) {
            recentMessages.delete(userId);
        }
    }
}

// 每分鐘清理一次
setInterval(cleanupRecentMessages, 60000);

// ============================================
// 事件處理
// ============================================

// 連線成功
client.on('connected', (address, port) => {
    console.log(`[Bot] Connected to ${address}:${port}`);
    console.log(`[Bot] Channels: ${config.twitch.channels.join(', ')}`);
    
    if (currentStreamId) {
        console.log(`[Bot] Using Stream ID: ${currentStreamId}`);
    } else {
        console.log(`[Bot] Warning: No Stream ID set. Use !setstream <id> (mod only) or set CURRENT_STREAM_ID env`);
    }
});

// 接收訊息
client.on('message', async (channel, tags, message, self) => {
    // 忽略 Bot 自己的訊息
    if (self) return;
    
    const userId = tags['user-id'];
    const username = tags['display-name'] || tags.username;
    const isMod = tags.mod || tags.badges?.broadcaster === '1';
    const isBroadcaster = tags.badges?.broadcaster === '1';
    
    // ============================================
    // 指令處理
    // ============================================
    
    const trimmedMessage = message.trim().toLowerCase();
    
    // !rank - 查詢個人排名
    if (trimmedMessage === '!rank') {
        const userData = await getUserRank(userId);
        
        if (!userData) {
            client.say(channel, `@${username} 你還沒有觀看紀錄喔！開始在聊天室互動吧！`);
            return;
        }
        
        client.say(channel, 
            `@${username} 你目前排名第 ${userData.rank_streak} 名 🏆 | ` +
            `連續觀看 ${userData.current_streak} 天 🔥 | ` +
            `累計觀看 ${userData.total_sessions} 場 📺`
        );
        return;
    }
    
    // !top - 顯示前三名
    if (trimmedMessage === '!top') {
        const ranking = await getTopRanking(3);
        
        if (ranking.length === 0) {
            client.say(channel, '目前還沒有排行榜資料！');
            return;
        }
        
        const medals = ['🥇', '🥈', '🥉'];
        const rankText = ranking.map((user, index) => 
            `${medals[index]} ${user.username} - ${user.current_streak}天`
        ).join(' | ');
        
        client.say(channel, `🏆 連續觀看排行榜: ${rankText}`);
        return;
    }
    
    // !mystats - 詳細個人統計
    if (trimmedMessage === '!mystats') {
        const userData = await getUserRank(userId);
        
        if (!userData) {
            client.say(channel, `@${username} 你還沒有觀看紀錄喔！`);
            return;
        }
        
        client.say(channel,
            `@${username} 📊 你的統計: ` +
            `連續觀看 ${userData.current_streak} 天 | ` +
            `最高連續 ${userData.max_streak} 天 | ` +
            `累計觀看 ${userData.total_sessions} 場 | ` +
            `連續排名 #${userData.rank_streak} | ` +
            `累計排名 #${userData.rank_total}`
        );
        return;
    }
    
    // !setstream <stream_id> - 設定 Stream ID（僅限 Mod/主播）
    if (trimmedMessage.startsWith('!setstream ') && (isMod || isBroadcaster)) {
        const streamId = message.trim().split(' ')[1];
        if (streamId) {
            await startNewStream(streamId);
            client.say(channel, `@${username} ✅ 已設定 Stream ID: ${streamId}`);
        }
        return;
    }
    
    // !endstream - 結束當前 Stream（僅限 Mod/主播）
    if (trimmedMessage === '!endstream' && (isMod || isBroadcaster)) {
        await endCurrentStream();
        client.say(channel, `@${username} ✅ 已結束當前直播場次`);
        return;
    }
    
    // ============================================
    // 出席記錄
    // ============================================
    
    // 如果沒有設定 Stream ID，不記錄出席
    if (!currentStreamId) {
        return;
    }
    
    // 檢查是否需要處理（防止過於頻繁）
    if (!shouldProcessMessage(userId)) {
        return;
    }
    
    // 記錄出席（非同步，不阻塞）
    recordAttendance(userId, username, currentStreamId);
});

// 錯誤處理
client.on('disconnected', (reason) => {
    console.log(`[Bot] Disconnected: ${reason}`);
});

// ============================================
// 啟動
// ============================================

console.log('[Bot] Starting VTuber Viewer Ranking Bot...');
client.connect().catch(err => {
    console.error('[Bot] Connection error:', err);
    process.exit(1);
});

// 優雅關閉
process.on('SIGINT', async () => {
    console.log('[Bot] Shutting down...');
    if (currentStreamId) {
        await endCurrentStream();
    }
    client.disconnect();
    process.exit(0);
});

process.on('SIGTERM', async () => {
    console.log('[Bot] Received SIGTERM...');
    if (currentStreamId) {
        await endCurrentStream();
    }
    client.disconnect();
    process.exit(0);
});
