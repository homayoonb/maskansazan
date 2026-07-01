<style>
    .sidebar-overlay,
    .mobile-menu-toggle {
        display: none;
    }

    @media (max-width: 768px) {
        body {
            overflow-x: hidden;
        }

        .sidebar {
            /* تغییرات اصلی برای اتوماتیک شدن */
            right: 0 !important;
            left: auto !important;
            width: 85% !important;        /* به جای پیکسل ثابت، ۸۵٪ عرض صفحه */
            max-width: 320px !important;  /* محدودیت برای گوشی‌های خیلی بزرگ */
            height: 100vh !important;

            transform: translateX(100%) !important;
            transition: transform 0.3s ease !important;
            z-index: 2000 !important;

            /* مدیریت اسکرول داخلی */
            overflow-y: auto;
            overflow-x: hidden;
        }

        .sidebar.is-open {
            transform: translateX(0) !important;
        }

        /* اطمینان از اینکه محتوای داخلی سایدبار بیرون نمی‌زند */
        .sidebar .navbar-nav,
        .sidebar .nav {
            width: 100% !important;
        }

        .sidebar .nav-link {
            white-space: normal !important; /* اگر متن طولانی بود، بشکند */
        }

        /* تنظیم دکمه همبرگری برای باز ماندن جای درست */
        .mobile-menu-toggle {
            display: inline-flex !important;
            position: fixed;
            top: 15px;
            right: 15px;
            z-index: 2100;
            transition: right 0.3s ease; /* انیمیشن نرم حرکت دکمه */
        }

        /* وقتی سایدبار باز است، دکمه جابه‌جا شود */
        body.sidebar-open .mobile-menu-toggle {
            right: calc(85% - 50px); /* دکمه لبه سایدبار می‌ماند */
            background: #dc3545;
        }

        .sidebar-overlay.is-open {
            display: block !important;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.45);
            z-index: 1990;
        }
    }


</style>
<button class="mobile-menu-toggle" type="button" id="mobileSidebarToggle">
    <i class="fa fa-bars"></i>
</button>

<div class="sidebar-overlay" id="sidebarOverlay"></div>
<aside class="sidebar shadow" id="sidebar">
    <?php
      include 'navbar.php';
    ?>

</aside>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const sidebar = document.getElementById('sidebar');
        const toggle = document.getElementById('mobileSidebarToggle');
        const overlay = document.getElementById('sidebarOverlay');

        if (!sidebar || !toggle || !overlay) return;

        function openSidebar() {
            sidebar.classList.add('is-open');
            overlay.classList.add('is-open');
            document.body.classList.add('sidebar-open');
        }

        function closeSidebar() {
            sidebar.classList.remove('is-open');
            overlay.classList.remove('is-open');
            document.body.classList.remove('sidebar-open');
        }

        toggle.addEventListener('click', openSidebar);
        overlay.addEventListener('click', closeSidebar);

        sidebar.querySelectorAll('a[href]:not([data-bs-toggle="collapse"])').forEach(function (link) {
            link.addEventListener('click', closeSidebar);
        });
    });
</script>
