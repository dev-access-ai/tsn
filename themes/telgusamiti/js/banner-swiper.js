/**
 * Banner Swiper Initialization
 * Initializes Swiper slider for main banner
 */
document.addEventListener('DOMContentLoaded', function() {
  if (document.querySelector('.banner-swiper')) {
    var bannerSwiper = new Swiper('.banner-swiper', {
      // Enable loop
      loop: true,
      
      // Autoplay settings
      autoplay: {
        delay: 5000,
        disableOnInteraction: false,
      },
      
      // Navigation arrows
      navigation: {
        nextEl: '.swiper-button-next',
        prevEl: '.swiper-button-prev',
      },
      
      // Effect settings
      effect: 'slide',
      speed: 800,
      
      // Keyboard control
      keyboard: {
        enabled: true,
      },
      
      // Mousewheel control
      mousewheel: false,
    });
  }
});

