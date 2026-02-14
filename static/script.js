(function(){
  const cloud1 = document.querySelector('.cloud--1');
  const cloud2 = document.querySelector('.cloud--2');
  const cloud3 = document.querySelector('.cloud--3');
  const cloud4 = document.querySelector('.cloud--4');

  function setupCloud(el, yJitter, widthPercent, heightPercent){
    const windowWidth = window.innerWidth;
    const windowHeight = window.innerHeight;
    const cloudWidthPx = (widthPercent / 100) * windowWidth;
    const cloudHeightPx = (heightPercent / 100) * windowHeight;
    // 一開始隨機分布在整個畫面
    const randomLeftPx = Math.random() * (windowWidth - cloudWidthPx);
    const randomTopPx = Math.random() * (windowHeight - cloudHeightPx);
    el.style.left = randomLeftPx + 'px';
    el.style.top = randomTopPx + 'px';
    el.style.transform = 'translate(0, 0)';
    const start = performance.now();
    const speed = 0.5; // 加快速度
    function frame(now){
      const newLeft = parseFloat(el.style.left) + speed;
      if (newLeft > windowWidth + cloudWidthPx) {
        // 超出右邊，移除元素
        el.remove();
        return;
      } else {
        el.style.left = newLeft + 'px';
      }
      const y = Math.sin((now - start) / 2000) * yJitter;
      el.style.transform = `translate(0, ${y}px)`;
      requestAnimationFrame(frame);
    }
    requestAnimationFrame(frame);
  }

  // ensure clouds are visible after images load / on resize adjust sizes via CSS
  window.addEventListener('load', ()=>{
    // 創建星星
    // 左邊星星
    for (let i = 0; i < 20; i++) {
      const star = document.createElement('div');
      star.className = 'star';
      star.style.left = Math.random() * 50 + '%';
      star.style.top = Math.random() * 100 + '%';
      star.style.width = '2px';
      star.style.height = '2px';
      star.style.background = 'white';
      star.style.borderRadius = '50%';
      star.style.position = 'fixed';
      star.style.zIndex = '0';
      star.style.pointerEvents = 'none';
      star.style.opacity = Math.random() * 0.8 + 0.2;
      document.body.appendChild(star);
      // 閃爍效果
      setInterval(() => {
        star.style.opacity = Math.random() * 0.8 + 0.2;
      }, Math.random() * 2000 + 1000);
    }
    // 右邊星星
    for (let i = 0; i < 20; i++) {
      const star = document.createElement('div');
      star.className = 'star';
      star.style.left = (50 + Math.random() * 50) + '%';
      star.style.top = Math.random() * 100 + '%';
      star.style.width = '2px';
      star.style.height = '2px';
      star.style.background = 'white';
      star.style.borderRadius = '50%';
      star.style.position = 'fixed';
      star.style.zIndex = '0';
      star.style.pointerEvents = 'none';
      star.style.opacity = Math.random() * 0.8 + 0.2;
      document.body.appendChild(star);
      // 閃爍效果
      setInterval(() => {
        star.style.opacity = Math.random() * 0.8 + 0.2;
      }, Math.random() * 2000 + 1000);
    }

    if(cloud1) setupCloud(cloud1, 6, 64, 32);
    if(cloud2) setupCloud(cloud2, 10, 56, 30);
    if(cloud3) setupCloud(cloud3, 8, 50, 25);
    if(cloud4) setupCloud(cloud4, 12, 45, 28);

    // 每 5 秒創建一個新的雲朵
    setInterval(() => {
      const newCloud = document.createElement('div');
      newCloud.className = 'cloud';
      newCloud.setAttribute('aria-hidden', 'true');
      document.body.appendChild(newCloud);
      // 隨機樣式
      const styles = [
        {widthPercent: 64, heightPercent: 32, yJitter: 6, bg: 'images/cloud1.png', opacity: 0.18, filter: 'blur(2px) saturate(105%)'},
        {widthPercent: 56, heightPercent: 30, yJitter: 10, bg: 'images/cloud2.png', opacity: 0.14, filter: 'blur(3px) saturate(100%)'},
        {widthPercent: 50, heightPercent: 25, yJitter: 8, bg: 'images/cloud1.png', opacity: 0.12, filter: 'blur(1px) saturate(110%)'},
        {widthPercent: 45, heightPercent: 28, yJitter: 12, bg: 'images/cloud2.png', opacity: 0.16, filter: 'blur(2px) saturate(95%)'}
      ];
      const style = styles[Math.floor(Math.random() * styles.length)];
      newCloud.style.width = style.widthPercent + 'vw';
      newCloud.style.height = style.heightPercent + 'vh';
      newCloud.style.backgroundImage = `url("${style.bg}")`;
      newCloud.style.opacity = style.opacity;
      newCloud.style.filter = style.filter;
      newCloud.style.position = 'fixed';
      newCloud.style.pointerEvents = 'none';
      newCloud.style.zIndex = '1';
      newCloud.style.backgroundRepeat = 'no-repeat';
      newCloud.style.backgroundSize = 'contain';
      newCloud.style.willChange = 'transform, opacity';
      setupCloud(newCloud, style.yJitter, style.widthPercent, style.heightPercent);
    }, 5000);
  });

  // pause animations when tab not visible to save CPU
  document.addEventListener('visibilitychange', ()=>{
    if(document.hidden){
      // remove transforms to stop heavy repaints
      // (animations will continue but the browser throttles rAF)
    }
  });
})();
