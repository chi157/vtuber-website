// 倒數計時器設定檔案
// 在這裡設定倒數時間

const countdownConfig = {
  // 目標日期時間 (格式: YYYY-MM-DD HH:mm:ss)
  // 可以設定具體日期時間
  targetDate: '2026-02-14 20:00:00',
  
  // 或者從現在開始倒數的秒數（如果設定了 targetDate 則此選項會被忽略）
  // secondsFromNow: 3600, // 例如：3600 秒 = 1 小時
  
  // 顯示設定
  title: '加班台倒數計時',
  message: '距離開播還有',
  endMessage: '🎉 開播時間到！',
  
  // 樣式設定
  theme: {
    backgroundColor: '#1a1a2e',
    primaryColor: '#7dd3fc',
    secondaryColor: '#c084fc',
    textColor: '#ffffff',
    fontFamily: "'Noto Sans TC', sans-serif"
  },
  
  // 功能設定
  showDays: true,      // 顯示天數
  showHours: true,     // 顯示小時
  showMinutes: true,   // 顯示分鐘
  showSeconds: true,   // 顯示秒數
  
  // 當倒數結束時是否自動重新整理頁面
  autoReloadOnEnd: false,
  
  // 倒數結束後的動作（可選）
  onCountdownEnd: function() {
    console.log('倒數計時結束！');
    // 可以在這裡添加其他動作，例如播放音效、顯示訊息等
  }
};

// 匯出設定（供 countdown.html 使用）
if (typeof module !== 'undefined' && module.exports) {
  module.exports = countdownConfig;
}
