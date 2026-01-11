/**
 * Community Swiper Initialization
 * Initializes Swiper slider for our-community-section
 */
document.addEventListener('DOMContentLoaded', function() {
  if (document.querySelector('.community-swiper')) {
    var communitySwiper = new Swiper('.community-swiper', {
      // Enable loop
      loop: true,
      
      // Slides per view - responsive
      slidesPerView: 1,
      spaceBetween: 20,
      
      // Breakpoints for responsive design
      breakpoints: {
        640: {
          slidesPerView: 2,
          spaceBetween: 20,
        },
        768: {
          slidesPerView: 3,
          spaceBetween: 20,
        },
        1024: {
          slidesPerView: 4,
          spaceBetween: 20,
        },
      },
      
      // Autoplay settings
      autoplay: {
        delay: 3000,
        disableOnInteraction: false,
      },
      
      // Navigation arrows
      navigation: {
        nextEl: '.swiper-button-next',
        prevEl: '.swiper-button-prev',
      },
      
      // Pagination
      pagination: {
        el: '.swiper-pagination',
        clickable: true,
      },
      
      // Effect settings
      effect: 'slide',
      speed: 600,
      
      // Keyboard control
      keyboard: {
        enabled: true,
      },
      
      // Mousewheel control
      mousewheel: false,
    });
  }
});

