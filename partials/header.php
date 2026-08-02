<?php
// header.php - هدر شرکت مسکن سازان
$baseUrl = $baseUrl ?? '/maskansazan';
$logoUrl = $logoUrl ?? $baseUrl . '/images/logo.png';
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
        }
        @media (max-width: 991.98px) {
            body { padding-top: 100px; }
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
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .top-bar .date-display { white-space: nowrap; }
        /* والد نوار بالا */

        .topbar{
            display: flex;
            align-items: center;
        }

        /* این گروه بره سمت چپ */
        .right-group{
            margin-right: auto;   /* در RTL یعنی بچسب به چپ */
            display: flex;
            align-items: center;
            gap: 12px;
        }

        /* فقط برای زیبایی تاریخ/ساعت */
        .date-display{
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

        .top-search {
            position: relative;
            width: 200px;
        }
        .top-search input {
            width: 100%;
            background: #000154;
            border: 1px solid rgba(255,255,255,0.2);
            color: #fff;
            border-radius: 20px;
            padding: 4px 12px 4px 30px;
            font-size: 0.8rem;
            outline: none;
        }
        .top-search input::placeholder { color: #d0d0d0; }
        .top-search button {
            position: absolute;
            left: 8px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: 0;
            color: #fff;
            font-size: 0.75rem;
            cursor: pointer;
        }

        /* ===== نوار اصلی ===== */
        .main-nav {
            position: relative;
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            height: 65px;
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 2;
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

        /* ===== لوگو بدون دایره و زمینه سفید ===== */
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
            width: 80px;
            height: 80px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            background: transparent; /* حذف پس‌زمینه سفید */
            border: none;             /* حذف حاشیه */
            border-radius: 50%;
            box-shadow: none;         /* حذف سایه */
            transition: transform 0.2s;
        }
        .center-logo-wrapper:hover .center-logo-circle {
            transform: scale(1.05);
        }
        .center-logo-circle img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain; /* حفظ تناسبات تصویر اصلی بدون برش */
            border-radius: 50%;
        }
        .center-logo-title {
            margin-top: 2px;
            font-size: 0.78rem;
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

        /* ===== حالت موبایل ===== */
        /* ===== حالت موبایل (لوگو سمت چپ، دکمه منو سمت راست) ===== */
        @media (max-width: 991.98px) {
            /* ۱. نوار سورمه‌ای بالا */
            .top-bar {
                height: 38px !important;
                padding: 0 12px !important;
                display: flex !important;
                align-items: center !important;
                justify-content: flex-end !important;
            }
            .top-bar .date-display,
            .top-search {
                display: none !important;
            }
            .btn-complaints-wrapper {
                margin: 0 !important;
            }
            .btn-complaints {
                padding: 3px 12px !important;
                font-size: 0.75rem !important;
            }

            /* ۲. نوار اصلی (معکوس کردن جهت چیدمان) */
            .main-nav {
                height: 52px !important;
                padding: 0 12px !important;
                display: flex !important;
                flex-direction: row-reverse !important; /* معکوس کردن جهت: دکمه راست، لوگو چپ */
                align-items: center !important;
                justify-content: space-between !important;
            }

            .nav-menu {
                display: none !important;
            }

            /* ۳. لوگو و متن شرکت (هدایت به سمت چپ) */
            .center-logo-wrapper {
                position: static !important;
                transform: none !important;
                display: flex !important;
                align-items: center !important;
                gap: 8px !important;
                margin: 0 !important;
                text-decoration: none !important;
            }
            .center-logo-circle {
                width: 32px !important;
                height: 32px !important;
                margin: 0 !important;
                background: transparent !important;
                border: none !important;
                box-shadow: none !important;
                border-radius: 0 !important;
            }
            .center-logo-circle img {
                width: 100% !important;
                height: 100% !important;
                object-fit: contain !important;
            }
            .center-logo-title {
                margin: 0 !important;
                font-size: 0.8rem !important;
                font-weight: 700 !important;
                color: #000154 !important;
                white-space: nowrap !important;
            }

            /* ۴. دکمه همبرگری منو (هدایت به سمت راست) */
            .mobile-btns {
                display: flex !important;
                align-items: center !important;
                margin: 0 !important;
            }
            .mobile-btns button {
                background: none !important;
                border: 0 !important;
                padding: 0 !important;
                margin: 0 !important;
                line-height: 1 !important;
                font-size: 1.4rem !important;
                color: #000154 !important;
                cursor: pointer !important;
            }
        }
    </style>
</head>
<body>

<header class="site-header">
    <!-- نوار بالایی -->
    <div class="top-bar">
        <div class="right-group text-start">
            <span class="date-display"><?php include 'datetime.php'?></span>
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
        <ul class="nav-menu">
            <li><a href="<?= $baseUrl ?>/">صفحه اصلی</a></li>
            <li class="sep">|</li>
            <li><a href="<?= $baseUrl ?>/projects">پروژه‌ها</a></li>
            <li class="sep">|</li>
            <li><a href="<?= $baseUrl ?>/groupmembers.php">اعضا</a></li>
            <li class="sep">|</li>
            <li><a href="<?= $baseUrl ?>/peymankaran_taminkonandegan_login.php">پیمانکاران</a></li>
            <li class="sep">|</li>
            <li><a href="<?= $baseUrl ?>/admin/loginAdmin.php">مدیریت</a></li>
            <li class="sep">|</li>
            <li><a href="<?= $baseUrl ?>/contact.php">ارتباط با ما</a></li>
        </ul>

        <div class="mobile-btns">
            <button type="button" id="mobileMenuBtn" aria-label="منو" aria-controls="mobileDrawer" aria-expanded="false"><i class="fa fa-bars"></i></button>
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
        <li><a href="<?= $baseUrl ?>/projects">پروژه‌ها</a></li>
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