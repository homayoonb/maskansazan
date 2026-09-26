<?php
// header.php - هدر شرکت مسکن سازان
$baseUrl = $baseUrl ?? '/maskansazan';
$logoUrl = $logoUrl ?? $baseUrl . '/images/logo.jpg';
$showSlider = $showSlider ?? true; // در صورت نیاز در صفحات دیگر می‌توانید false کنید
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'سامانه مسکن سازان' ?></title>

    <!-- Bootstrap RTL & FontAwesome -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            font-family: system-ui, -apple-system, "Segoe UI", Tahoma, sans-serif;
            background: #f8fafc;
            margin: 0;
            padding-top: 110px; /* ایجاد فضای کافی برای هدر ثابت دسکتاپ (45px + 65px) */
        }

        .site-header {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 1050;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            background: #fff;
        }

        /* ===== نوار بالایی سورمه‌ای ===== */
        .top-bar {
            background: #000282;
            color: #fff;
            height: 45px;
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            font-size: 0.85rem;
            position: relative;
            z-index: 1;
        }
        .top-bar * { color: #fff; }

        .top-bar .right-group {
            margin-right: auto; /* در RTL یعنی چسبیدن به سمت چپ */
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .date-display {
            white-space: nowrap;
        }

        .btn-complaints {
            display: inline-block;
            background: #ff0000;
            color: #fff;
            border-radius: 999px;
            padding: 4px 16px;
            font-weight: 700;
            font-size: 0.78rem;
            text-decoration: none;
            white-space: nowrap;
            transition: background 0.2s;
        }
        .btn-complaints:hover { background: #cc0000; color: #fff; }

        /* ===== نوار اصلی منو ===== */
        .main-nav {
            position: relative;
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            height: 65px;
            padding: 0 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            direction: rtl;
            width: 100%;
            z-index: 2;
        }

        .nav-right-group,
        .left-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .nav-menu {
            display: flex;
            align-items: center;
            gap: 14px;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .nav-menu a {
            color: #2b2b2b;
            font-size: 0.88rem;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.2s;
        }
        .nav-menu a:hover { color: #000282; }
        .nav-menu .sep { color: #cbd5e1; font-size: 0.8rem; }

        /* ===== لوگوی وسط ===== */
        .center-logo-wrapper {
            position: absolute;
            left: 50%;
            top: 6px;
            transform: translateX(-50%);
            z-index: 99;
            text-align: center;
            text-decoration: none;
        }
        .center-logo-circle {
            width: 75px;
            height: 75px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            border-radius: 50%;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .center-logo-wrapper:hover .center-logo-circle {
            transform: scale(1.05);
        }
        .center-logo-circle img {
            max-width: 85%;
            max-height: 85%;
            object-fit: contain;
            border-radius: 50%;
        }
        .center-logo-title {
            margin-top: 2px;
            font-size: 0.75rem;
            font-weight: 800;
            color: #000154;
            white-space: nowrap;
        }

        .mobile-btns { display: none; gap: 8px; }
        .mobile-btns button {
            background: none;
            border: 0;
            color: #000154;
            font-size: 1.35rem;
            padding: 4px;
            cursor: pointer;
        }

        /* ===== منوی کشویی موبایل ===== */
        .mobile-drawer {
            position: fixed;
            top: 0;
            right: -300px;
            width: 280px;
            max-width: 85vw;
            height: 100vh;
            background: #fff;
            z-index: 1100;
            box-shadow: -4px 0 15px rgba(0,0,0,0.15);
            transition: right 0.3s ease;
            display: flex;
            flex-direction: column;
            padding: 20px 0;
        }
        .mobile-drawer.open { right: 0; }
        .mobile-drawer .drawer-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px 15px;
            border-bottom: 1px solid #eee;
            margin-bottom: 10px;
        }
        .mobile-drawer .drawer-title {
            font-weight: 800;
            color: #000154;
            font-size: 1rem;
        }
        .mobile-drawer .drawer-close {
            background: none;
            border: 0;
            font-size: 1.4rem;
            color: #333;
            cursor: pointer;
        }
        .mobile-drawer ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .mobile-drawer ul li a {
            display: block;
            padding: 12px 20px;
            color: #2b2b2b;
            font-size: 0.95rem;
            font-weight: 600;
            text-decoration: none;
            border-bottom: 1px solid #f2f2f2;
        }
        .mobile-drawer ul li a:hover {
            background: #f8fafc;
            color: #000282;
        }
        .mobile-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.45);
            z-index: 1099;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }
        .mobile-backdrop.open {
            opacity: 1;
            pointer-events: auto;
        }
        body.no-scroll { overflow: hidden; }

        /* ===== حالت ریسپانسیو موبایل و تبلت ===== */
        @media (max-width: 991.98px) {
            body { padding-top: 90px; }

            .top-bar {
                height: 38px !important;
                padding: 0 12px !important;
                justify-content: flex-end !important;
            }
            .top-bar .date-display {
                display: none !important;
            }

            .main-nav {
                height: 52px !important;
                padding: 0 12px !important;
                flex-direction: row-reverse !important;
            }

            .nav-menu {
                display: none !important;
            }

            .center-logo-wrapper {
                position: static !important;
                transform: none !important;
                display: flex !important;
                align-items: center !important;
                gap: 8px !important;
                margin: 0 !important;
            }
            .center-logo-circle {
                width: 36px !important;
                height: 36px !important;
                box-shadow: none !important;
            }
            .center-logo-title {
                margin: 0 !important;
                font-size: 0.8rem !important;
            }

            .mobile-btns {
                display: flex !important;
            }
        }
    </style>
</head>
<body>

<header class="site-header">
    <!-- نوار بالایی -->
    <div class="top-bar">
        <div class="right-group text-start">
            <span class="date-display"><?php include 'datetime.php'; ?></span>
        </div>
    </div>

    <!-- لوگوی مستقیم و شفاف -->
    <a href="<?= $baseUrl ?>/" class="center-logo-wrapper">
        <div class="center-logo-circle">
            <img src="<?= $logoUrl ?>" alt="لوگو شرکت مسکن سازان">
        </div>
        <div class="center-logo-title">گروه مسکن سازان نقشه برداری</div>
    </a>

    <!-- نوار اصلی منو -->
    <nav class="main-nav">
        <!-- بخش راست: منوی سمت راست -->
        <div class="nav-right-group" style="margin-right: 220px">
            <ul class="nav-menu">
                <li><a href="<?= $baseUrl ?>/">صفحه اصلی</a></li>
                <li class="sep">|</li>
                <li><a href="<?= $baseUrl ?>/projects.php">پروژه‌ها</a></li>
                <li class="sep">|</li>
                <li><a href="<?= $baseUrl ?>/groupmembers.php">اعضا</a></li>
            </ul>
        </div>

        <!-- بخش چپ: منوی سمت چپ -->
        <ul class="nav-menu left-section" style="margin-left: 220px">
            <li><a href="<?= $baseUrl ?>/peymankaran_taminkonandegan_login.php">پیمانکاران</a></li>
            <li class="sep">|</li>
            <li><a href="<?= $baseUrl ?>/admin/loginAdmin.php">مدیریت</a></li>
            <li class="sep">|</li>
            <li><a href="<?= $baseUrl ?>/contact.php">ارتباط با ما</a></li>
        </ul>

        <div class="mobile-btns">
            <button type="button" id="mobileMenuBtn" aria-label="منو" aria-controls="mobileDrawer" aria-expanded="false">
                <i class="fa fa-bars"></i>
            </button>
        </div>
    </nav>
</header>

<!-- منوی کشویی موبایل -->
<aside class="mobile-drawer" id="mobileDrawer" aria-hidden="true">
    <div class="drawer-header">
        <span class="drawer-title">منو</span>
        <button type="button" class="drawer-close" id="drawerCloseBtn" aria-label="بستن">
            <i class="fa fa-times"></i>
        </button>
    </div>
    <ul>
        <li><a href="<?= $baseUrl ?>/">صفحه اصلی</a></li>
        <li><a href="<?= $baseUrl ?>/projects.php">پروژه‌ها</a></li>
        <li><a href="<?= $baseUrl ?>/groupmembers.php">اعضا</a></li>
        <li><a href="<?= $baseUrl ?>/peymankaran_taminkonandegan_login.php">پیمانکاران</a></li>
        <li><a href="<?= $baseUrl ?>/admin/loginAdmin.php">مدیریت</a></li>
        <li><a href="<?= $baseUrl ?>/contact.php">ارتباط با ما</a></li>
    </ul>
</aside>
<div class="mobile-backdrop" id="mobileBackdrop"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    (function () {
        var menuBtn = document.getElementById('mobileMenuBtn');
        var drawer = document.getElementById('mobileDrawer');
        var backdrop = document.getElementById('mobileBackdrop');
        var closeBtn = document.getElementById('drawerCloseBtn');

        function openDrawer() {
            drawer.classList.add('open');
            backdrop.classList.add('open');
            document.body.classList.add('no-scroll');
            drawer.setAttribute('aria-hidden', 'false');
            menuBtn.setAttribute('aria-expanded', 'true');
        }
        function closeDrawer() {
            drawer.classList.remove('open');
            backdrop.classList.remove('open');
            document.body.classList.remove('no-scroll');
            drawer.setAttribute('aria-hidden', 'true');
            menuBtn.setAttribute('aria-expanded', 'false');
        }

        if (menuBtn) menuBtn.addEventListener('click', openDrawer);
        if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
        if (backdrop) backdrop.addEventListener('click', closeDrawer);
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeDrawer();
        });
    })();
</script>
