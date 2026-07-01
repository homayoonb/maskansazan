// Please see documentation at https://learn.microsoft.com/aspnet/core/client-side/bundling-and-minification
// for details on configuring this project to bundle and minify static web assets.

// Write your JavaScript code.

$(document).ready(function () {
    // راه‌اندازی carousel
    var myCarousel = new bootstrap.Carousel('#mainCarousel', {
        interval: 3000,
        wrap: true,
        pause: false
    });

    // مدیریت کلیک دکمه ورود
    $('#loginButton').click(function () {
        // مخفی کردن اسلایدر با انیمیشن
        $('#mainCarousel').addClass('hidden');

        // نمایش محتوای اصلی
        setTimeout(function () {
            $('#mainContent').addClass('visible');
        }, 500);

        // مخفی کردن دکمه
        $(this).hide();

        // توقف carousel
        myCarousel.pause();
    });


    // افزودن effect برای بهبود UX
    $('input').focus(function () {
        $(this).css('transform', 'scale(1.02)');
    }).blur(function () {
        $(this).css('transform', 'scale(1)');
    });
});

    document.addEventListener('DOMContentLoaded', function() {
    const dropdowns = document.querySelectorAll('.navbar .dropdown-toggle');

    dropdowns.forEach(function(dropdown) {
    dropdown.addEventListener('click', function (e) {
    if (window.innerWidth < 992) {
    e.preventDefault();
    let menu = this.nextElementSibling;

    // بستن سایر منوهای باز
    document.querySelectorAll('.dropdown-menu.show').forEach(m => {
    if (m !== menu) m.classList.remove('show');
});

    // باز یا بسته کردن منوی فعلی
    menu.classList.toggle('show');
}
});
});

    // کلیک بیرون منو => همه بسته شوند
    document.addEventListener('click', function(e) {
    if (!e.target.closest('.navbar')) {
    document.querySelectorAll('.dropdown-menu.show').forEach(m => m.classList.remove('show'));
}
});
});
$(document).ready(function(){
    // موبایل: کلیک روی dropdown-toggle برای باز کردن زیرمنو
    $('.dropdown-toggle').click(function(e){
        if(window.innerWidth < 992){
            e.preventDefault();
            var $submenu = $(this).next('.dropdown-menu');
            $('.dropdown-menu').not($submenu).slideUp();
            $submenu.slideToggle();
        }
    });
});
// نمایش زیرمنو با hover در دسکتاپ
document.querySelectorAll('.navbar .dropdown').forEach(function(drop){
    drop.addEventListener('mouseenter', function(){ if(window.innerWidth>991) this.classList.add('show'); });
    drop.addEventListener('mouseleave', function(){ if(window.innerWidth>991) this.classList.remove('show'); });
});
