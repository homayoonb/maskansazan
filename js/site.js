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