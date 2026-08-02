
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// مسیر پروژه (اگر در روت هست خالی بگذار)
$baseUrl = '/maskansazan';
$currentPage = basename($_SERVER['PHP_SELF']);

$projectPages = [
        'mehr.php'       => 'مهرپادین',
        'toranj.php'     => 'ترنج',
        'garmdareh1.php' => 'نقشه‌برداری ۱',
        'garmdareh2.php' => 'نقشه‌برداری ۲',
        'ordibehesht.php'=> 'اردیبهشت',
        'sabat.php'      => 'ساباط',
        'polaris.php'    => 'پلاریس'
];

// همیشه صفحه اصلی استارت breadcrumb است
$breadcrumb = [
        'صفحه اصلی' => $baseUrl . '/index.php'
];

// اگر صفحه فعلی یکی از پروژه‌هاست
if (array_key_exists($currentPage, $projectPages)) {

    // سطح دوم → پروژه‌ها
    $breadcrumb['پروژه‌ها'] = ''; // بدون لینک

    // سطح سوم → پروژه مربوطه
    $breadcrumb[$projectPages[$currentPage]] = $baseUrl . '/pages/' . $currentPage;
}

else {
    // صفحات غیر پروژه‌ای
    $pages = [
            'goals.php'            => 'اهداف',
            'groupIntroduction.php'=> 'معرفی گروه',
            'garmdareh1.php'       => 'نقشه‌برداری ۱',
            'garmdareh2.php'       => 'نقشه‌برداری ۲',
            'ordibehesht.php'      => 'اردیبهشت',
            'sabat.php'            => 'ساباط',
            'polaris.php'          => 'پلاریس'
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
        body { font-family: 'Vazir', Tahoma, sans-serif; background-color: #F4F4FC; }

        /* چیدمان اصلی */
        .wrapper { display: flex; min-height: 100vh; }
        .main-content .row {
            margin-left: 0 !important;
            margin-right: 0 !important;
            margin-top: 50px;
        }

        .main-content .col-lg-10 {
            padding-left: 0 !important;
            padding-right: 0 !important;
            margin-left: auto !important;
            margin-right: auto !important;
            margin-top: auto !important;
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
            z-index: 1000;
            display: flex;
            flex-direction: column;
            transition: all 0.3s;
        }


        /* لوگو در سایدبار */
        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid #c5e1e8;
        }
        .header-logo { width: 100px; height: auto; margin-bottom: 10px; }
        .logo-text { color: #1a2d50; font-weight: bold; }
        .bottom-text { color: #f9b115; display: block; font-size: 1.1rem; }

        /* منوی عمودی */
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

        /* زیرمنوها در حالت عمودی */
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

        /* دکمه جستجوی پایین سایدبار */
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

        /* استایل Breadcrumb */
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
        @media (max-width: 768px) {
            .sidebar-container {
                width: 100% !important;
                height: auto !important;
                display: flex !important;
                flex-direction: row !important;
                justify-content: space-between !important;
                align-items: center !important;
                padding: 10px 15px !important;
            }

            .sidebar-container .logo,
            .sidebar-container img {
                max-width: 60px !important;
                height: auto !important;
            }

            .main-content {
                width: 100% !important;
                margin: 0 !important;
                padding: 15px !important;
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
<div class="wrapper">
    <!-- سایدبار که معمولاً در header یا جداگانه است -->
    <?php
       include 'header.php';
    ?>

    <main class="main-content py-4" style="margin-top: 200px;width:100%">
        <div class="container">
            <div class="row col-lg-10">
                <div class="col-lg-10">
                    <?php echo isset($content) ? $content : '<p>محتوایی یافت نشد.</p>'; ?>
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

<script>
    // فقط در صفحه اصلی site.js کامل را بارگیری کن
    <?php if ($currentPage == 'index.php'): ?>
    setTimeout(function() {
        var script = document.createElement('script');
        script.src = '<?= $baseUrl ?>/js/site.js';
        script.onload = function() {
            console.log('site.js با موفقیت بارگیری شد');
        };
        script.onerror = function() {
            console.error('خطا در بارگیری site.js');
        };
        document.body.appendChild(script);
    }, 800);
    <?php else: ?>
    setTimeout(function() {
        if (typeof jQuery !== 'undefined') {
            jQuery(function($) {
                if ($(window).width() < 992) {
                    $('.dropdown-toggle').on('click', function(e) {
                        e.preventDefault();
                        $(this).next('.dropdown-menu').toggleClass('show');
                    });

                    $(document).on('click', function(e) {
                        if (!$(e.target).closest('.dropdown').length) {
                            $('.dropdown-menu').removeClass('show');
                        }
                    });
                } else {
                    $('.navbar .dropdown').hover(
                        function() { $(this).addClass('show').find('.dropdown-menu').addClass('show'); },
                        function() { $(this).removeClass('show').find('.dropdown-menu').removeClass('show'); }
                    );
                }
            });
        }
    }, 300);
    <?php endif; ?>
</script>



<?php if(isset($extraScripts)) echo $extraScripts; ?>
<!-- Modal Search -->

</body>
</html>
