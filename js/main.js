document.addEventListener('DOMContentLoaded', function () {
    
    // карусель
    const swiper = new Swiper('.productSwiper', {
        slidesPerView: 4,
        spaceBetween: 30,
        speed: 600,
        loop: true,
        
        // автопрокрутка
        autoplay: {
            delay: 3000,
            disableOnInteraction: false,
        },
        
        navigation: {
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
        },
        
        // pagination: {
        //     el: '.swiper-pagination',
        //     clickable: true,
        // },
        
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
                spaceBetween: 30
            }
        }
    });
    
    // Кнопки в корзину
    document.querySelectorAll('.btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const card = this.closest('.product-card');
            if (card) {
                const name = card.querySelector('h3').textContent;
                // alert('✅ Товар "' + name + '" добавлен в корзину!');
            }
        });
    });
    
    // Бургер
    const burger = document.getElementById('burger');
    const mainMenu = document.getElementById('mainMenu');
    
    if (burger && mainMenu) {
        burger.addEventListener('click', function() {
            mainMenu.classList.toggle('active');
            burger.textContent = mainMenu.classList.contains('active') ? '✕' : '☰';
        });
    }
});