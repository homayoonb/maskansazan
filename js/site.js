document.addEventListener('DOMContentLoaded', function () {
    // راه‌اندازی اسلایدر اصلی
    const carouselEl = document.getElementById('mainHeroCarousel');

    let myCarousel = null;

    if (carouselEl && typeof bootstrap !== 'undefined' && bootstrap.Carousel) {
        myCarousel = bootstrap.Carousel.getOrCreateInstance(carouselEl, {
            interval: 4500,
            wrap: true,
            pause: 'hover',
            keyboard: true,
            touch: true
        });
    }

    // مدیریت دکمه ورود
    const loginButton = document.getElementById('loginButton');
    if (loginButton) {
        loginButton.addEventListener('click', function () {
            if (carouselEl) {
                carouselEl.classList.add('hidden');
            }

            const mainContent = document.getElementById('mainContent');
            if (mainContent) {
                setTimeout(function () {
                    mainContent.classList.add('visible');
                }, 500);
            }

            this.style.display = 'none';

            if (myCarousel) {
                myCarousel.pause();
            }
        });
    }

    // افکت ورودی‌ها
    document.querySelectorAll('input').forEach(function (input) {
        input.addEventListener('focus', function () {
            this.style.transform = 'scale(1.02)';
            this.style.transition = 'transform 0.2s ease';
        });

        input.addEventListener('blur', function () {
            this.style.transform = 'scale(1)';
        });
    });

    // dropdown موبایل
    document.querySelectorAll('.navbar .dropdown-toggle').forEach(function (toggle) {
        toggle.addEventListener('click', function (e) {
            if (window.innerWidth < 992) {
                e.preventDefault();

                const menu = this.nextElementSibling;
                if (!menu || !menu.classList.contains('dropdown-menu')) return;

                document.querySelectorAll('.navbar .dropdown-menu.show').forEach(function (openMenu) {
                    if (openMenu !== menu) {
                        openMenu.classList.remove('show');
                    }
                });

                menu.classList.toggle('show');
            }
        });
    });

    // بستن منو با کلیک بیرون
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.navbar')) {
            document.querySelectorAll('.navbar .dropdown-menu.show').forEach(function (menu) {
                menu.classList.remove('show');
            });
        }
    });

    // hover دسکتاپ
    document.querySelectorAll('.navbar .dropdown').forEach(function (drop) {
        drop.addEventListener('mouseenter', function () {
            if (window.innerWidth > 991) {
                this.classList.add('show');
                const menu = this.querySelector('.dropdown-menu');
                if (menu) menu.classList.add('show');
            }
        });

        drop.addEventListener('mouseleave', function () {
            if (window.innerWidth > 991) {
                this.classList.remove('show');
                const menu = this.querySelector('.dropdown-menu');
                if (menu) menu.classList.remove('show');
            }
        });
    });
});
