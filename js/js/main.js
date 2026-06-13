document.addEventListener('DOMContentLoaded', function () {
    
    let autoplayTimer = null;
    let isUserInteracting = false;
    
    const swiper = new Swiper('.productSwiper', {
        slidesPerView: 4,
        slidesPerGroup: 1,
        spaceBetween: 30,
        speed: 600,
        loop: true,
        
        autoplay: false,
        
        navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
        },
        
        pagination: {
            el: '.swiper-pagination',
            clickable: true,
        },
        
        breakpoints: {
            320: {
                slidesPerView: 1,
                spaceBetween: 15
            },
            640: {
                slidesPerView: 2,
                spaceBetween: 20
            },
            992: {
                slidesPerView: 3,
                spaceBetween: 25
            },
            1200: {
                slidesPerView: 4,
                slidesPerGroup: 1,
                spaceBetween: 30
            }
        }
    });
    
    function startAutoplay() {
        if (autoplayTimer) {
            clearTimeout(autoplayTimer);
        }
        
        autoplayTimer = setTimeout(() => {
            if (!isUserInteracting) {
                swiper.slideNext();
                startAutoplay();
            }
        }, 2000);
    }
    
    function resetAutoplay() {
        if (autoplayTimer) {
            clearTimeout(autoplayTimer);
        }
        startAutoplay();
    }
    
    startAutoplay();
    const swiperContainer = document.querySelector('.productSwiper');
    
    if (swiperContainer) {
        swiperContainer.addEventListener('mouseenter', () => {
            isUserInteracting = true;
            if (autoplayTimer) {
                clearTimeout(autoplayTimer);
            }
        });
        
        swiperContainer.addEventListener('mouseleave', () => {
            isUserInteracting = false;
            startAutoplay();
        });
    }
    
    const nextButton = document.querySelector('.swiper-button-next');
    const prevButton = document.querySelector('.swiper-button-prev');
    const pagination = document.querySelector('.swiper-pagination');
    
    if (nextButton) {
        nextButton.addEventListener('click', () => resetAutoplay());
    }
    
    if (prevButton) {
        prevButton.addEventListener('click', () => resetAutoplay());
    }
    
    if (pagination) {
        pagination.addEventListener('click', () => resetAutoplay());
    }
    
    swiper.on('slideChange', () => {
        resetAutoplay();
    });
});