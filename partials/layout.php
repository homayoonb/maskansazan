<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$baseUrl = '/maskansazan';
$currentPage = basename($_SERVER['PHP_SELF']);

$projectPages = [
        'mehr.php'        => 'مهرپادین',
        'toranj.php'      => 'ترنج',
        'garmdareh1.php'  => 'نقشه‌برداری ۱',
        'garmdareh2.php'  => 'نقشه‌برداری ۲',
        'ordibehesht.php' => 'اردیبهشت',
        'sabat.php'       => 'ساباط',
        'polaris.php'     => 'پلاریس'
];

$breadcrumb = [
        'صفحه اصلی' => $baseUrl . '/index.php'
];

if (array_key_exists($currentPage, $projectPages)) {
    $breadcrumb['پروژه‌ها'] = '';
    $breadcrumb[$projectPages[$currentPage]] = $baseUrl . '/pages/' . $currentPage;
} else {
    $pages = [
            'goals.php'             => 'اهداف',
            'groupIntroduction.php' => 'معرفی گروه',
            'garmdareh1.php'        => 'نقشه‌برداری ۱',
            'garmdareh2.php'        => 'نقشه‌برداری ۲',
            'ordibehesht.php'       => 'اردیبهشت',
            'sabat.php'             => 'ساباط',
            'polaris.php'           => 'پلاریس'
    ];

    if (isset($pages[$currentPage])) {
        $breadcrumb[$pages[$currentPage]] = $baseUrl . '/pages/' . $currentPage;
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? htmlspecialchars($title) : 'سامانه مسکن سازان'; ?></title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="<?= $baseUrl ?>/css/site.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            font-family: 'Vazir', Tahoma, sans-serif;
            background-color: #F4F4FC;
            padding-top: 110px; /* فضای لازم برای هدر ثابت بالای صفحه */
            margin: 0;
        }

        .page-container {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            width: 100%;
        }

        /* ثابت کردن محفظه اسلایدر در پس‌زمینه */
        .fixed-slider-container {
            position: fixed;
            top: 110px; /* شروع دقیقاً از زیر هدر */
            left: 0;
            right: 0;
            height: 440px; /* هم‌اندازه با ارتفاع تعریف‌شده در slider.php */
            z-index: 1; /* زیر منو و روی بادی */
            width: 100%;
        }

        /* محتوای اصلی سایت که روی اسلایدر اسکرول می‌شود */
        .main-content {
            position: relative;
            z-index: 2; /* قرارگیری روی اسلایدر */
            background-color: #F4F4FC; /* جلوگیری از شفاف بودن پس‌زمینه جهت پوشاندن اسلایدر */
            width: 100%;
            /* ایجاد فاصله اولیه در صفحه اصلی تا اسلایدرِ ثابت زیرین دیده شود */
            margin-top: <?= ($currentPage === 'index.php' || $currentPage === '') ? '460px' : '0' ?>;
            padding-bottom: 50px;
            box-shadow: 0 -10px 30px rgba(0, 0, 0, 0.05); /* ایجاد سایه ملایم هنگام عبور روی اسلایدر */
        }

        /* استایل سایدبار عمودی */
        .sidebar {
            width: 280px;
            background-color: #e4f8fc;
            border-left: 2px solid #d0e7ed;
            position: fixed;
            right: 0;
            top: 0;
            bottom: 0;
            z-index: 1050; /* بالاتر از هدر و اسلایدر */
            display: flex;
            flex-direction: column;
            transition: all 0.3s;
        }

        /* سایر استایل‌ها بدون تغییر جهت پایداری قالب */
        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid #c5e1e8;
        }
        .header-logo { width: 100px; height: auto; margin-bottom: 10px; }
        .logo-text { color: #1a2d50; font-weight: bold; }
        .bottom-text { color: #f9b115; display: block; font-size: 1.1rem; }

        .sidebar-nav { padding: 15px 0; overflow-y: auto; }
        .nav-link {
            color: #1a2d50 !important;
            font-weight: 600;
            padding: 12px 20px !important;
            border-right: 4px solid transparent;
            transition: 0.3s;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .nav-link:hover, .nav-link.active {
            background: #d5eff5;
            border-right-color: #f1ad1d;
            color: #0056b3 !important;
        }

        .dropdown-menu {
            position: static !important;
            width: 100%;
            border: none;
            background: rgba(255,255,255,0.5);
            box-shadow: none;
            padding: 0;
            transform: none !important;
        }
        .dropdown-item {
            padding: 10px 35px;
            font-size: 0.9rem;
            color: #1a2d50;
        }
        .dropdown-item:hover { background: #f1ad1d; color: white; }

        .sidebar-footer {
            margin-top: auto;
            padding: 20px;
            border-top: 1px solid #c5e1e8;
        }
        .search-btn-sidebar {
            background: #1a2d50;
            color: white;
            border-radius: 10px;
            width: 100%;
            padding: 10px;
            text-align: center;
            cursor: pointer;
        }

        .custom-breadcrumb {
            background: white;
            padding: 10px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        footer {
            width: 100%;
        }

        /* رسپانسیو برای موبایل */
        @media (max-width: 991px) {
            .sidebar { transform: translateX(100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-right: 0; }
            .mobile-toggle { display: block; }
        }

        @media (min-width: 992px) {
            .mobile-toggle { display: none; }
        }

        @media (max-width: 768px) {
            body { padding-top: 90px; }
            .fixed-slider-container {
                top: 90px;
                height: 230px; /* کاهش ارتفاع اسلایدر در موبایل */
            }
            .main-content {
                margin-top: <?= ($currentPage === 'index.php' || $currentPage === '') ? '250px' : '0' ?>;
                width: 100% !important;
                margin-right: 0 !important;
                padding: 15px !important;
            }

            .sidebar-container {
                width: 100% !important;
                height: auto !important;
                display: flex !important;
                flex-direction: row !important;
                justify-content: space-between !important;
                align-items: center !important;
                padding: 10px 15px !important;
            }

            .content-wrapper {
                width: 100% !important;
                padding: 15px !important;
                box-sizing: border-box;
            }

            .content-wrapper p,
            .content-wrapper div,
            .activity-history-container p {
                position: static !important;
                height: auto !important;
                width: 100% !important;
                margin-bottom: 15px !important;
                line-height: 1.9 !important;
                font-size: 15px !important;
            }

            .activity-history-container {
                position: static !important;
                height: auto !important;
                overflow: visible !important;
            }

            .floating-icon,
            .fab,
            .fixed-bottom-icon {
                display: none !important;
            }
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

<div class="page-container">

    <!-- ۱. هدر ثابت سایت -->
    <?php include 'header.php'; ?>

    <!-- ۲. اسلایدر ثابت پس‌زمینه (فقط در صفحه اصلی) -->
    <?php if ($currentPage === 'index.php' || $currentPage === ''): ?>
        <div class="fixed-slider-container">
            <?php include 'slider.php'; ?>
        </div>
    <?php endif; ?>

    <!-- ۳. محتوای اصلی صفحه (که روی اسلایدر اسکرول می‌شود) -->
    <main class="main-content py-3">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-lg-10">
                    <?= isset($content) ? $content : '<p>محتوایی یافت نشد.</p>'; ?>
                </div>
            </div>
        </div>
    </main>

</div>

<!-- Modal Search -->
<div class="modal fade" id="searchModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body p-4">
                <form action="search.php" method="GET">
                    <div class="input-group">
                        <input type="text" name="q" class="form-control" placeholder="جستجو کنید...">
                        <button class="btn btn-warning" type="submit">جستجو</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= $baseUrl ?>/js/site.js"></script>


</body>
</html>
