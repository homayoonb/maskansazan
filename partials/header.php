<?php
$baseUrl = '/maskansazan';
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'سامانه مسکن سازان' ?></title>

    <!-- Bootstrap RTL & Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            /* افزایش ارتفاع هدر برای جا دادن لوگوی بزرگتر */
            --header-height: 200px;
            --sidebar-width: 260px;
        }

        /* هدر نباید روی سایدبار بیاید */
        .top-navbar {
            position: fixed;
            top: 0;
            left: 0;
            /* به جای right: 0، فاصله از راست را برابر عرض سایدبار قرار می‌دهیم */
            right: var(--sidebar-width);
            height: var(--header-height);
            background-color: #E4F8FC;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            z-index: 1050; /* مقدار کمتر از سایدبار در صورت نیاز */
            padding: 0 20px;
            transition: all 0.3s ease;
        }

        /* حذف مارجین‌های اضافی از بردکرامب برای تراز شدن */
        .breadcrumb-wrapper .breadcrumb {
            margin-bottom: 0 !important;
            padding: 0 !important;
            background: transparent !important;
        }

        .header-logo {
            height: 140px !important; /* سایز لوگو بزرگتر شد */
            width: auto;
            transition: all 0.3s ease;
        }

        .text-body {
            border-right: 4px solid #1a4a5e; /* خط ضخیم‌تر برای تناسب */
            padding-right: 15px;
        }

        .top-text {
            font-size: 1.4rem; /* متن کنار لوگو هم کمی بزرگتر شود تا تناسب حفظ شود */
            line-height: 1.3;
        }

        /* اصلاح بدنه سایت برای اینکه محتوا زیر هدر نرود */
        body {
            padding-top: var(--header-height);
            margin: 0;
        }
        /* =========================
   Mobile Responsive Fix
========================= */
        @media (max-width: 768px) {
            :root {
                --header-height: 92px;
                --sidebar-width: 0px;
            }

            body {
                padding-top: var(--header-height) !important;
                overflow-x: hidden;
            }

            .top-navbar {
                position: fixed !important;
                top: 0 !important;
                right: 0 !important;
                left: 0 !important;
                width: 100% !important;
                height: var(--header-height) !important;
                min-height: var(--header-height) !important;
                padding: 6px 58px 6px 10px !important;
                z-index: 1000;
                background-color: #E4F8FC;
            }

            .top-navbar .container-fluid {
                height: 100% !important;
                padding: 0 !important;
                display: flex !important;
                flex-direction: column !important;
                justify-content: center !important;
                gap: 4px;
            }

            .top-navbar .d-flex.justify-content-between.align-items-center.w-100 {
                flex-direction: row !important;
                align-items: center !important;
                justify-content: space-between !important;
                gap: 8px;
            }

            .brand-section {
                flex: 1;
                min-width: 0;
            }

            .navbar-brand {
                display: flex !important;
                flex-direction: row !important;
                align-items: center !important;
                justify-content: flex-start !important;
                width: auto !important;
                max-width: 100%;
                gap: 8px;
            }

            .header-logo {
                height: 48px !important;
                width: 48px !important;
                max-width: 48px !important;
                object-fit: contain;
                flex: 0 0 auto;
            }

            .logo-text {
                margin-right: 0 !important;
                min-width: 0;
            }

            .text-body {
                border-right: 3px solid #1a4a5e !important;
                border-top: 0 !important;
                padding-right: 8px !important;
                padding-top: 0 !important;
                text-align: right !important;
                line-height: 1.15;
            }

            .top-text {
                font-size: 0.82rem !important;
                line-height: 1.15 !important;
                white-space: nowrap;
            }

            .datetime-section {
                flex: 0 0 auto;
                max-width: 145px;
                text-align: left !important;
                font-size: 0.72rem !important;
                line-height: 1.5;
                white-space: normal;
            }

            .top-navbar .d-flex.justify-content-start.w-100 {
                justify-content: flex-start !important;
                min-width: 0;
            }

            .breadcrumb-wrapper {
                width: 100%;
                overflow-x: auto;
                overflow-y: hidden;
                padding-bottom: 2px;
                scrollbar-width: none;
            }

            .breadcrumb-wrapper::-webkit-scrollbar {
                display: none;
            }

            .breadcrumb-wrapper .breadcrumb {
                justify-content: flex-start !important;
                flex-wrap: nowrap !important;
                white-space: nowrap;
                font-size: 0.72rem;
                margin: 0 !important;
            }

            .mobile-menu-toggle {
                top: 12px !important;
                right: 10px !important;
                z-index: 2100 !important;
            }
        }

    </style>


</head>
<body>

<!-- هدر سرتاسری بالای صفحه -->
<!-- هدر سرتاسری بالای صفحه -->
<!-- هدر سرتاسری بالای صفحه -->
<header class="top-navbar">
    <div class="container-fluid h-100 d-flex flex-column justify-content-between py-2">

        <!-- ردیف اول: لوگو (راست) و تاریخ (چپ) -->
        <div class="d-flex justify-content-between align-items-center w-100">



            <!-- بخش راست: لوگو و نام برند -->
            <div class="brand-section">
                <a class="navbar-brand m-0 d-flex align-items-center" href="<?= $baseUrl ?>/index.php">
                    <!-- تنظیم اندازه لوگو برای جلوگیری از بیرون زدگی -->
                    <img src="<?= $baseUrl ?>/images/logo.png" alt="لوگو" class="header-logo" style="height: 90px; width: auto;">
                    <div class="logo-text me-3">
                        <div class="text-body text-start">
                            <span class="top-text">گروه</span><br>
                            <span class="top-text">مسکن سازان</span><br>
                            <span class="top-text">نقشه‌برداری</span>
                        </div>
                    </div>

                </a>


            </div>
            <!-- بخش چپ: تاریخ و زمان (حالا دقیقاً به چپ می‌چسبد) -->
            <div class="datetime-section text-start">
                <?php include 'datetime.php'; ?>
            </div>
        </div>

        <!-- ردیف دوم: Breadcrumb (دقیقاً زیر نام برند در سمت راست) -->
        <div class="d-flex justify-content-start w-100">
            <div class="breadcrumb-wrapper">
                <?php include 'breadcrumb.php'; ?>
            </div>
        </div>

    </div>
</header>




