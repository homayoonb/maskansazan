<?php
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
            'groupmembers.php'=> 'ورود اعضای گروه',
            'garmdareh1.php'       => 'نقشه‌برداری ۱',
            'garmdareh2.php'       => 'نقشه‌برداری ۲',
            'ordibehesht.php'      => 'اردیبهشت',
            'sabat.php'            => 'ساباط',
            'polaris.php'          => 'پلاریس',
            'mehr.php'          => 'مهرپادین',
            'contact.php'          => 'تماس با ما',
            'peymankaran_taminkonandegan_login.php'          => 'پیمانکاران',
            'loginAdmin.php'          => 'مدیریت',
    ];

    if (isset($pages[$currentPage])) {
        $breadcrumb[$pages[$currentPage]] = $baseUrl . '/pages/' . $currentPage;
    }
}

?>
<!--<div class="custom-breadcrumb px-4 py-2">-->
<!--</div>-->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    /* حالت عمومی */
    .breadcrumb{
        display:flex !important;
        flex-direction:row !important;
        flex-wrap:nowrap !important;
        list-style:none !important;
        background-color:#E4F8FC !important;
        padding:10px 15px !important;
        border-radius:5px;
        margin-bottom:0;
    }

    .breadcrumb-item{
        display:flex !important;
        align-items:center;
        white-space:nowrap;
    }

    .breadcrumb-item.active{
        font-weight:600;
        color:#0d6efd;
    }

    /* موبایل */
    @media (max-width:768px){

        nav[aria-label="breadcrumb"]{
            margin-top:90px;              /* فاصله از هدر */
            width:100%;
            overflow-x:auto;
            overflow-y:hidden;
            -webkit-overflow-scrolling:touch;
        }

        .breadcrumb{
            min-width:max-content;
            font-size:12px;
            padding:8px 10px !important;
        }

        nav[aria-label="breadcrumb"]::-webkit-scrollbar{
            display:none;
        }

    }




</style>
<nav aria-label="breadcrumb" class="mt-2">
    <ol class="breadcrumb" style="background-color: #E4F8FC; padding: 0.75rem 1rem; border-radius: 0.25rem;">
        <?php foreach ($breadcrumb as $name => $link):
            // تشخیص صفحه فعال
            $isActive = (empty($link) || basename($link) == $currentPage);
            ?>
            <li class="breadcrumb-item <?= $isActive ? 'active' : '' ?>" <?= $isActive ? 'aria-current="page"' : '' ?>>

                <?php if (!$isActive && !empty($link)): ?>
                    <a href="<?= htmlspecialchars($link) ?>"><?= htmlspecialchars($name) ?></a>
                <?php else: ?>
                    <?= htmlspecialchars($name) ?>
                <?php endif; ?>

            </li>
        <?php endforeach; ?>
    </ol>
</nav>
