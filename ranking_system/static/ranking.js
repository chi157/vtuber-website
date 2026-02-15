/**
 * VTuber Viewer Ranking - Frontend JavaScript
 */

// ============================================
// Configuration
// ============================================

const CONFIG = {
    API_BASE_URL: '/api',  // Use relative path (nginx will proxy)
    REFRESH_INTERVAL: 60000,  // Auto refresh every 60 seconds
    DEFAULT_LIMIT: 20
};

// ============================================
// State
// ============================================

let currentTab = 'streak';

// ============================================
// API Functions
// ============================================

async function fetchAPI(endpoint) {
    try {
        const response = await fetch(`${CONFIG.API_BASE_URL}${endpoint}`);
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        return await response.json();
    } catch (error) {
        console.error(`API Error (${endpoint}):`, error);
        return null;
    }
}

async function fetchStats() {
    return await fetchAPI('/stats');
}

async function fetchStreakRanking(limit = CONFIG.DEFAULT_LIMIT) {
    return await fetchAPI(`/ranking/streak?limit=${limit}`);
}

async function fetchTotalRanking(limit = CONFIG.DEFAULT_LIMIT) {
    return await fetchAPI(`/ranking/total?limit=${limit}`);
}

async function fetchMaxStreakRanking(limit = CONFIG.DEFAULT_LIMIT) {
    return await fetchAPI(`/ranking/max-streak?limit=${limit}`);
}

async function fetchUserInfo(userId) {
    return await fetchAPI(`/user/${userId}`);
}

// ============================================
// UI Functions
// ============================================

function formatNumber(num) {
    if (num >= 1000000) {
        return (num / 1000000).toFixed(1) + 'M';
    }
    if (num >= 1000) {
        return (num / 1000).toFixed(1) + 'K';
    }
    return num.toLocaleString();
}

function getRankBadgeClass(rank) {
    if (rank === 1) return 'rank-1';
    if (rank === 2) return 'rank-2';
    if (rank === 3) return 'rank-3';
    return 'rank-other';
}

function createRankingRow(data, valueKey, unit) {
    const isTop3 = data.rank <= 3;
    const rankBadgeClass = getRankBadgeClass(data.rank);
    
    return `
        <tr class="${isTop3 ? 'top-3' : ''}">
            <td class="rank-col">
                <span class="rank-badge ${rankBadgeClass}">
                    ${data.rank <= 3 ? ['🥇', '🥈', '🥉'][data.rank - 1] : data.rank}
                </span>
            </td>
            <td class="name-col">
                <span class="username">${escapeHtml(data.username)}</span>
            </td>
            <td class="score-col">
                <span class="score">${data[valueKey]}</span>
                <span class="score-unit">${unit}</span>
            </td>
        </tr>
    `;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function showLoading(tableBodyId) {
    const tbody = document.getElementById(tableBodyId);
    tbody.innerHTML = '<tr class="loading-row"><td colspan="3">載入中...</td></tr>';
}

function showEmpty(tableBodyId) {
    const tbody = document.getElementById(tableBodyId);
    tbody.innerHTML = '<tr class="empty-row"><td colspan="3">目前沒有排行榜資料</td></tr>';
}

function showError(tableBodyId) {
    const tbody = document.getElementById(tableBodyId);
    tbody.innerHTML = '<tr class="empty-row"><td colspan="3">載入失敗，請稍後再試</td></tr>';
}

// ============================================
// Update Functions
// ============================================

async function updateStats() {
    const stats = await fetchStats();
    
    if (stats) {
        document.getElementById('totalUsers').textContent = formatNumber(stats.total_users);
        document.getElementById('totalSessions').textContent = formatNumber(stats.total_sessions);
        document.getElementById('totalAttendances').textContent = formatNumber(stats.total_attendances);
    }
}

async function updateStreakRanking() {
    const tableBodyId = 'streakTableBody';
    showLoading(tableBodyId);
    
    const data = await fetchStreakRanking();
    
    if (data === null) {
        showError(tableBodyId);
        return;
    }
    
    if (data.length === 0) {
        showEmpty(tableBodyId);
        return;
    }
    
    const tbody = document.getElementById(tableBodyId);
    tbody.innerHTML = data.map(item => 
        createRankingRow(item, 'current_streak', '天')
    ).join('');
}

async function updateTotalRanking() {
    const tableBodyId = 'totalTableBody';
    showLoading(tableBodyId);
    
    const data = await fetchTotalRanking();
    
    if (data === null) {
        showError(tableBodyId);
        return;
    }
    
    if (data.length === 0) {
        showEmpty(tableBodyId);
        return;
    }
    
    const tbody = document.getElementById(tableBodyId);
    tbody.innerHTML = data.map(item => 
        createRankingRow(item, 'total_sessions', '場')
    ).join('');
}

async function updateMaxStreakRanking() {
    const tableBodyId = 'maxStreakTableBody';
    showLoading(tableBodyId);
    
    const data = await fetchMaxStreakRanking();
    
    if (data === null) {
        showError(tableBodyId);
        return;
    }
    
    if (data.length === 0) {
        showEmpty(tableBodyId);
        return;
    }
    
    const tbody = document.getElementById(tableBodyId);
    tbody.innerHTML = data.map(item => 
        createRankingRow(item, 'max_streak', '天')
    ).join('');
}

function updateLastUpdateTime() {
    const now = new Date();
    const timeStr = now.toLocaleTimeString('zh-TW', { 
        hour: '2-digit', 
        minute: '2-digit',
        second: '2-digit'
    });
    document.getElementById('lastUpdate').textContent = timeStr;
}

async function updateAllData() {
    await Promise.all([
        updateStats(),
        updateStreakRanking(),
        updateTotalRanking(),
        updateMaxStreakRanking()
    ]);
    updateLastUpdateTime();
}

// ============================================
// Tab Handling
// ============================================

function switchTab(tabName) {
    currentTab = tabName;
    
    // Update tab buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.tab === tabName);
    });
    
    // Update sections
    document.querySelectorAll('.ranking-section').forEach(section => {
        section.classList.remove('active');
    });
    document.getElementById(`${tabName}-ranking`).classList.add('active');
}

// ============================================
// User Search
// ============================================

async function searchUser() {
    const input = document.getElementById('userIdInput');
    const resultDiv = document.getElementById('userResult');
    const userId = input.value.trim();
    
    if (!userId) {
        resultDiv.className = 'user-result error';
        resultDiv.innerHTML = '請輸入 Twitch User ID';
        return;
    }
    
    resultDiv.className = 'user-result';
    resultDiv.innerHTML = '查詢中...';
    
    const data = await fetchUserInfo(userId);
    
    if (!data) {
        resultDiv.className = 'user-result error';
        resultDiv.innerHTML = '找不到此用戶，請確認 User ID 是否正確';
        return;
    }
    
    if (data.error) {
        resultDiv.className = 'user-result error';
        resultDiv.innerHTML = data.error;
        return;
    }
    
    resultDiv.className = 'user-result';
    resultDiv.innerHTML = `
        <h3 style="margin-bottom: 1rem;">👤 ${escapeHtml(data.username)}</h3>
        <div class="user-stats">
            <div class="user-stat">
                <span class="user-stat-value">#${data.rank_streak}</span>
                <span class="user-stat-label">連續排名</span>
            </div>
            <div class="user-stat">
                <span class="user-stat-value">${data.current_streak}</span>
                <span class="user-stat-label">連續天數</span>
            </div>
            <div class="user-stat">
                <span class="user-stat-value">${data.max_streak}</span>
                <span class="user-stat-label">最高紀錄</span>
            </div>
            <div class="user-stat">
                <span class="user-stat-value">#${data.rank_total}</span>
                <span class="user-stat-label">累計排名</span>
            </div>
            <div class="user-stat">
                <span class="user-stat-value">${data.total_sessions}</span>
                <span class="user-stat-label">累計場數</span>
            </div>
        </div>
    `;
}

// ============================================
// Event Listeners
// ============================================

document.addEventListener('DOMContentLoaded', () => {
    // Initial load
    updateAllData();
    
    // Tab click handlers
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            switchTab(btn.dataset.tab);
        });
    });
    
    // Search button
    document.getElementById('searchBtn').addEventListener('click', searchUser);
    
    // Search on Enter key
    document.getElementById('userIdInput').addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            searchUser();
        }
    });
    
    // Auto refresh
    setInterval(updateAllData, CONFIG.REFRESH_INTERVAL);
});
