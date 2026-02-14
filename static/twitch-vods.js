// Twitch 播放器顯示
const TWITCH_CHANNEL = 'chi1577517';
const TWITCH_VIDEOS_URL = `https://www.twitch.tv/${TWITCH_CHANNEL}/videos?filter=archives&sort=time`;

// 預設 VOD IDs（如果 localStorage 沒有設定的話）
const DEFAULT_VOD_IDS = [
  '2693525200',
];

function displayTwitchPlayer() {
  const container = document.getElementById('twitch-vods-container');
  if (!container) {
    return;
  }

  // 優先使用 localStorage 的 VOD ID，沒有的話使用預設值
  let vodId = DEFAULT_VOD_IDS[0];
  const savedVOD = localStorage.getItem('twitch_vod_id');
  if (savedVOD) {
    vodId = savedVOD;
  }

  if (!vodId) {
    container.innerHTML = `
      <div style="text-align: center;">
        <p class="content-text" style="opacity: 0.7; margin-bottom: 12px;">目前沒有設定的直播錄影</p>
        <a 
          href="${TWITCH_VIDEOS_URL}" 
          target="_blank" 
          rel="noopener noreferrer"
          class="btn"
          style="background: rgba(145, 70, 255, 0.2); border: 1px solid rgba(145, 70, 255, 0.4); text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 16px; border-radius: 12px; color: var(--text);"
        >
          <span>前往 Twitch 查看</span>
        </a>
      </div>
    `;
    return;
  }

  // 顯示設定的 VOD
  const vodsHTML = `
    <div style="margin-bottom: 16px;">
      <div style="position: relative; padding-top: 56.25%; border-radius: 12px; overflow: hidden; background: rgba(26, 41, 80, 0.5);">
        <iframe 
          src="https://player.twitch.tv/?video=${vodId}&parent=vtwebsite.chi157.com&parent=localhost&parent=127.0.0.1&autoplay=false" 
          frameborder="0" 
          allowfullscreen="true" 
          scrolling="no" 
          style="position: absolute; top: 0; left: 0; width: 100%; height: 100%;">
        </iframe>
      </div>
    </div>
  `;
  
  container.innerHTML = `
    <div style="display: grid; gap: 16px;">
      ${vodsHTML}
      <a 
        href="${TWITCH_VIDEOS_URL}" 
        target="_blank" 
        rel="noopener noreferrer"
        class="btn"
        style="background: rgba(145, 70, 255, 0.2); border: 1px solid rgba(145, 70, 255, 0.4); text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 16px; border-radius: 12px; transition: all 0.3s ease; color: var(--text);"
      >
        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
          <path d="M11.571 4.714h1.715v5.143H11.57zm4.715 0H18v5.143h-1.714zM6 0L1.714 4.286v15.428h5.143V24l4.286-4.286h3.428L22.286 12V0zm14.571 11.143l-3.428 3.428h-3.429l-3 3v-3H6.857V1.714h13.714Z"/>
        </svg>
        <span>查看更多直播錄影</span>
      </a>
      
      
    </div>
  `;
}

// 頁面載入時執行
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', displayTwitchPlayer);
} else {
  displayTwitchPlayer();
}
