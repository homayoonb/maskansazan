<?php
function generateBreadcrumb() {
    $path = $_SERVER['REQUEST_URI'];
    $path = trim($path, '/');
    $segments = explode('/', $path);

    $baseUrl = '/';
    $breadcrumbs = [];
    $currentPath = '';

    foreach ($segments as $segment) {
        $currentPath .= '/' . $segment;
        // عناوین فارسی برای صفحات
        $titles = [
            'index.php' => 'صفحه اصلی',
            'groupIntroduction.php' => 'معرفی گروه',
            'goals.php' => 'اهداف',
            'garmdareh1.php' => 'گرمدره ۱',
            'garmdareh2.php' => 'گرمدره ۲',
            'toranj.php' => 'ترنج',
            'ordibehesht.php' => 'اردیبهشت',
            'polaris.php' => 'پلاریس',
            'sabat.php' => 'ساباط',
            'mehr.php' => 'پروژه مهر',
            'groupmembers.php' => 'اعضای پروژه مهر',
        ];

        $title = $titles[$segment] ?? urldecode($segment);
        $breadcrumbs[] = ['title' => $title, 'url' => $baseUrl . $currentPath];
    }

    return $breadcrumbs;
}