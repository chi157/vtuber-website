// 圖片輪播功能
class EventCarousel {
  constructor() {
    this.currentSlide = 0;
    this.slides = [];
    this.autoPlayInterval = null;
    this.init();
  }

  init() {
    // 活動圖片列表（從 images/events 資料夾）
    this.slides = [
      { src: 'images/events/免費從零開始學Python1.jpg', alt: '免費從零開始學Python 活動 1' },
      { src: 'images/events/免費從零開始學Python2.jpg', alt: '免費從零開始學Python 活動 2' },
      { src: 'images/events/免費從零開始學Python3.jpg', alt: '免費從零開始學Python 活動 3' },
      { src: 'images/events/連續15HR直播挑戰.jpg', alt: '連續15小時直播挑戰' }
    ];

    this.render();
    this.startAutoPlay();
  }

  render() {
    const container = document.getElementById('event-carousel-container');
    if (!container) return;

    const slidesHTML = this.slides.map((slide, index) => `
      <div class="carousel-slide ${index === 0 ? 'active' : ''}" data-index="${index}">
        <img src="${slide.src}" alt="${slide.alt}" loading="lazy">
      </div>
    `).join('');

    const dotsHTML = this.slides.map((_, index) => `
      <button class="carousel-dot ${index === 0 ? 'active' : ''}" data-index="${index}" aria-label="切換到第 ${index + 1} 張圖片"></button>
    `).join('');

    container.innerHTML = `
      <div class="carousel-wrapper">
        <div class="carousel-slides">
          ${slidesHTML}
        </div>
        
        <button class="carousel-btn carousel-prev" aria-label="上一張">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="15 18 9 12 15 6"></polyline>
          </svg>
        </button>
        
        <button class="carousel-btn carousel-next" aria-label="下一張">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="9 18 15 12 9 6"></polyline>
          </svg>
        </button>
        
        <div class="carousel-dots">
          ${dotsHTML}
        </div>
      </div>
    `;

    this.attachEvents();
  }

  attachEvents() {
    const prevBtn = document.querySelector('.carousel-prev');
    const nextBtn = document.querySelector('.carousel-next');
    const dots = document.querySelectorAll('.carousel-dot');

    if (prevBtn) prevBtn.addEventListener('click', () => this.prev());
    if (nextBtn) nextBtn.addEventListener('click', () => this.next());
    
    dots.forEach(dot => {
      dot.addEventListener('click', (e) => {
        const index = parseInt(e.target.dataset.index);
        this.goToSlide(index);
      });
    });
  }

  goToSlide(index) {
    this.currentSlide = index;
    
    const slides = document.querySelectorAll('.carousel-slide');
    const dots = document.querySelectorAll('.carousel-dot');
    
    slides.forEach((slide, i) => {
      slide.classList.toggle('active', i === index);
    });
    
    dots.forEach((dot, i) => {
      dot.classList.toggle('active', i === index);
    });

    this.resetAutoPlay();
  }

  next() {
    this.currentSlide = (this.currentSlide + 1) % this.slides.length;
    this.goToSlide(this.currentSlide);
  }

  prev() {
    this.currentSlide = (this.currentSlide - 1 + this.slides.length) % this.slides.length;
    this.goToSlide(this.currentSlide);
  }

  startAutoPlay() {
    this.autoPlayInterval = setInterval(() => this.next(), 5000); // 每5秒切換
  }

  resetAutoPlay() {
    clearInterval(this.autoPlayInterval);
    this.startAutoPlay();
  }
}

// 頁面載入時初始化輪播
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => new EventCarousel());
} else {
  new EventCarousel();
}
