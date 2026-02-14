// 載入導航欄
(function() {
  // 自動偵測 navbar 的路徑
  // 如果在子目錄中（例如 work_overtime/），需要使用 ../navbar.html
  let navbarPath = 'navbar.html';
  const currentPath = window.location.pathname;
  let isInSubdir = false;
  
  // 檢查是否在子目錄中
  if (currentPath.includes('/work_overtime/') || currentPath.includes('/courses/') || currentPath.includes('/events/')) {
    navbarPath = '../navbar.html';
    isInSubdir = true;
  }
  
  // 載入 navbar.html 到頁面中
  fetch(navbarPath)
    .then(response => response.text())
    .then(data => {
      // 將導航欄插入到 body 的最前面
      document.body.insertAdjacentHTML('afterbegin', data);
      
      // 如果在子目錄中，修正導覽列中的連結路徑
      if (isInSubdir) {
        fixNavbarLinks();
      }
      
      // 初始化導航欄功能
      initNavbar();
    })
    .catch(error => console.error('載入導航欄失敗:', error));
})();

// 修正導覽列連結路徑（針對子目錄頁面）
function fixNavbarLinks() {
  const navbar = document.querySelector('.navbar');
  if (!navbar) return;
  
  // 取得所有連結
  const links = navbar.querySelectorAll('a[href]');
  links.forEach(link => {
    const href = link.getAttribute('href');
    
    // 跳過外部連結和錨點
    if (href.startsWith('http') || href.startsWith('#')) return;
    
    // 如果連結已經是 ../ 開頭，跳過
    if (href.startsWith('../')) return;
    
    // 如果是 courses/ 開頭，改為當前目錄
    if (href.startsWith('courses/')) {
      link.setAttribute('href', href.replace('courses/', ''));
    } else {
      // 其他連結加上 ../
      link.setAttribute('href', '../' + href);
    }
  });
  
  // 修正圖片路徑
  const images = navbar.querySelectorAll('img[src]');
  images.forEach(img => {
    const src = img.getAttribute('src');
    if (!src.startsWith('http') && !src.startsWith('../')) {
      img.setAttribute('src', '../' + src);
    }
  });
}

// 導航欄功能初始化
function initNavbar() {
  const navbarToggle = document.getElementById('navbarToggle');
  const navbarMenu = document.getElementById('navbarMenu');

  if (navbarToggle && navbarMenu) {
    // 漢堡選單切換
    navbarToggle.addEventListener('click', function() {
      navbarToggle.classList.toggle('active');
      navbarMenu.classList.toggle('active');
    });

    // 點擊選單項目後關閉選單
    const navbarLinks = navbarMenu.querySelectorAll('.navbar-link');
    navbarLinks.forEach(link => {
      link.addEventListener('click', function() {
        navbarToggle.classList.remove('active');
        navbarMenu.classList.remove('active');
      });
    });

    // 點擊選單外部關閉選單
    document.addEventListener('click', function(event) {
      const isClickInside = navbarToggle.contains(event.target) || navbarMenu.contains(event.target);
      if (!isClickInside && navbarMenu.classList.contains('active')) {
        navbarToggle.classList.remove('active');
        navbarMenu.classList.remove('active');
      }
    });
  }
}
