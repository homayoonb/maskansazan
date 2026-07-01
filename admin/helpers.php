<?php
// admin/helpers.php

// تابع CSRF
// function generate_csrf_token() {
//     if (empty($_SESSION['csrf_token']) || empty($_SESSION['csrf_token_time']) || time() - $_SESSION['csrf_token_time'] > 3600) {
//         $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
//         $_SESSION['csrf_token_time'] = time();
//     }
//     return $_SESSION['csrf_token'];
// }

function verify_csrf($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        return false;
    }
    
    // بررسی زمان انقضا
    if (empty($_SESSION['csrf_token_time']) || time() - $_SESSION['csrf_token_time'] > 3600) {
        unset($_SESSION['csrf_token'], $_SESSION['csrf_token_time']);
        return false;
    }
    
    return true;
}

// تابع flash messages
function flash($type, $message) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash'][$type] = $message;
}

function get_flash($type) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (isset($_SESSION['flash'][$type])) {
        $message = $_SESSION['flash'][$type];
        unset($_SESSION['flash'][$type]);
        return $message;
    }
    
    return null;
}

// تابع generate token
function generate_token($length = 32) {
    return bin2hex(random_bytes($length));
}


function gregorian_to_jalali($gy, $gm, $gd, $mod = '')
{
    $g_d_m = array(0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334);
    $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
    $days = 355666 + (365 * $gy) + floor(($gy2 + 3) / 4) - floor(($gy2 + 99) / 100) + floor(($gy2 + 399) / 400) + $gd + $g_d_m[$gm - 1];
    $jy = -1595 + (33 * floor($days / 12053));
    $days %= 12053;
    $jy += 4 * floor($days / 1461);
    $days %= 1461;
    if ($days > 365) {
        $jy += floor(($days - 1) / 365);
        $days = ($days - 1) % 365;
    }
    if ($days < 186) {
        $jm = 1 + floor($days / 31);
        $jd = 1 + ($days % 31);
    } else {
        $jm = 7 + floor(($days - 186) / 30);
        $jd = 1 + (($days - 186) % 30);
    }
    return ($mod == '') ? array($jy, $jm, $jd) : $jy . $mod . $jm . $mod . $jd;
}

function mysql_to_jalali($mysql_date, $separator = '/', $include_time = false)
{
    if (empty($mysql_date) || $mysql_date == '0000-00-00 00:00:00' || $mysql_date == '0000-00-00') {
        return '-';
    }
    
    // جدا کردن تاریخ و زمان
    $date_time = explode(' ', $mysql_date);
    $date = $date_time[0];
    $time = isset($date_time[1]) ? $date_time[1] : '';
    
    // جدا کردن سال، ماه و روز میلادی
    list($gy, $gm, $gd) = explode('-', $date);
    
    // تبدیل به شمسی
    list($jy, $jm, $jd) = gregorian_to_jalali($gy, $gm, $gd);
    
    // فرمت تاریخ شمسی
    $jalali_date = sprintf("%04d{$separator}%02d{$separator}%02d", $jy, $jm, $jd);
    
    // اضافه کردن زمان اگر خواسته شده
    if ($include_time && !empty($time)) {
        $jalali_date .= ' ' . $time;
    }
    
    return $jalali_date;
}

function format_jalali_date($jalali_date, $format = 'Y/m/d')
{
    if (empty($jalali_date) || $jalali_date == '-') {
        return '-';
    }
    
    // اگر تاریخ شمسی است
    if (strpos($jalali_date, '/') !== false) {
        list($y, $m, $d) = explode('/', $jalali_date);
        
        // حذف زمان اگر وجود دارد
        $d = explode(' ', $d)[0];
        
        $formats = array(
            'Y/m/d' => sprintf("%04d/%02d/%02d", $y, $m, $d),
            'y/m/d' => sprintf("%02d/%02d/%02d", $y % 100, $m, $d),
            'd/m/Y' => sprintf("%02d/%02d/%04d", $d, $m, $y),
            'd F Y' => sprintf("%d %s %d", $d, jalali_month_name($m), $y),
            'H:i Y/m/d' => sprintf("%s %04d/%02d/%02d", date('H:i', strtotime($jalali_date)), $y, $m, $d),
        );
        
        return $formats[$format] ?? $jalali_date;
    }
    
    return $jalali_date;
}

function jalali_month_name($month)
{
    $months = array(
        1 => 'فروردین', 2 => 'اردیبهشت', 3 => 'خرداد',
        4 => 'تیر', 5 => 'مرداد', 6 => 'شهریور',
        7 => 'مهر', 8 => 'آبان', 9 => 'آذر',
        10 => 'دی', 11 => 'بهمن', 12 => 'اسفند'
    );
    
    return $months[$month] ?? $month;
}

// if (!function_exists('generateSortableHeader')) {
// function generateSortableHeader($title, $field, $currentSort, $currentOrder, $baseUrl) {
//     // استخراج پارامترها از baseUrl
//     $urlParts = parse_url($baseUrl);
//     $queryParams = [];
    
//     if (isset($urlParts['query'])) {
//         parse_str($urlParts['query'], $queryParams);
//     }
    
//     // حذف پارامترهای مرتب‌سازی قدیمی
//     unset($queryParams['sort']);
//     unset($queryParams['order']);
    
//     // تعیین جهت جدید
//     $newOrder = 'asc';
//     if ($field === $currentSort) {
//         $newOrder = ($currentOrder === 'asc') ? 'desc' : 'asc';
//     }
    
//     // اضافه کردن پارامترهای جدید
//     $queryParams['sort'] = $field;
//     $queryParams['order'] = $newOrder;
    
//     // ساخت URL جدید
//     $newQuery = http_build_query($queryParams);
//     $newUrl = $urlParts['path'] . '?' . $newQuery;
    
//     // آیکون
//     $icon = '';
//     if ($field === $currentSort) {
//         $icon = ($currentOrder === 'asc') ? ' ↑' : ' ↓';
//     }
    
//     return '<a href="' . htmlspecialchars($newUrl) . '" class="text-white text-decoration-none">' . $title . $icon . '</a>';
// }
// }
// توابع مورد نیاز
// function safeEcho($text) {
//     return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
// }

function formatFileSize($bytes, $precision = 2)
{
    if ($bytes <= 0) {
        return '0 B';
    }

    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $pow   = floor(log($bytes, 1024));
    $pow   = min($pow, count($units) - 1);

    $bytes /= pow(1024, $pow);

    return round($bytes, $precision) . ' ' . $units[$pow];
}

function generateSortableHeader($title, $field, $currentSort, $currentOrder, $baseUrl) {
    // استخراج پارامترها از baseUrl
    $urlParts = parse_url($baseUrl);
    $queryParams = [];
    
    if (isset($urlParts['query'])) {
        parse_str($urlParts['query'], $queryParams);
    }
    
    // حذف پارامترهای مرتب‌سازی قدیمی
    unset($queryParams['sort']);
    unset($queryParams['order']);
    
    // تعیین جهت جدید
    $newOrder = 'asc';
    if ($field === $currentSort) {
        $newOrder = ($currentOrder === 'asc') ? 'desc' : 'asc';
    }
    
    // اضافه کردن پارامترهای جدید
    $queryParams['sort'] = $field;
    $queryParams['order'] = $newOrder;
    
    // ساخت URL جدید
    $newQuery = http_build_query($queryParams);
    $newUrl = $urlParts['path'] . '?' . $newQuery;
    
    // آیکون
    $icon = '';
    if ($field === $currentSort) {
        $icon = ($currentOrder === 'asc') ? ' ↑' : ' ↓';
    }
    
    return '<a href="' . htmlspecialchars($newUrl) . '" class="text-white text-decoration-none">' . $title . $icon . '</a>';
}

function getStatusBadge($status) {
    $badgeClass = 'badge bg-';
    $text = '';
    
    switch(strtolower($status ?? '')) {
        case 'approved':
            $badgeClass .= 'success';
            $text = 'تأیید شده';
            break;
        case 'pending':
            $badgeClass .= 'warning';
            $text = 'در انتظار';
            break;
        case 'rejected':
            $badgeClass .= 'danger';
            $text = 'رد شده';
            break;
        default:
            $badgeClass .= 'secondary';
            $text = $status;
    }
    
    return "<span class=\"$badgeClass\">$text</span>";
}
function getTotalCount($mysqli, $query, $params = [], $types = '') {
    $stmt = $mysqli->prepare($query);
    if (!$stmt) {
        return 0;
    }
    
    $total = 0;
    
    if (!empty($params)) {
        $bindParams = [];
        $bindParams[] = &$types;
        
        foreach ($params as $key => $value) {
            $bindParams[] = &$params[$key];
        }
        
        call_user_func_array([$stmt, 'bind_param'], $bindParams);
    }
    
    $stmt->execute();
    $stmt->bind_result($total);
    $stmt->fetch();
    $stmt->close();
    
    return $total;
}

function fetchMembers($mysqli, $query, $params = [], $types = '') {
    $stmt = $mysqli->prepare($query);
    if (!$stmt) {
        return [];
    }
    
    if (!empty($params)) {
        $bindParams = [];
        $bindParams[] = &$types;
        
        foreach ($params as $key => $value) {
            $bindParams[] = &$params[$key];
        }
        
        call_user_func_array([$stmt, 'bind_param'], $bindParams);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $members = [];
    
    while ($row = $result->fetch_assoc()) {
        // پردازش هر سطر
        $row = processMemberRow($row);
        $members[] = $row;
    }
    
    $stmt->close();
    return $members;
}

// helpers.php

/**
 * پردازش هر سطر از داده‌های عضو
 */
function processMemberRow($row) {
    if (empty($row)) {
        return [];
    }
    
    // لیست فیلدهای تصویر
    $imageFields = [
        'national_card_image',
        'birth_certificate_image',
        'check1_image',
        'check2_image',
        'check3_image'
    ];
    
    // لیست فیلدهای متنی فارسی
    $persianFields = [
        'first_name',
        'last_name',
        'father_name',
        'address',
        'check1_bank',
        'check2_bank',
        'check3_bank'
    ];
    
    // اصلاح مسیر تصاویر
    foreach ($imageFields as $field) {
        if (isset($row[$field]) && !empty($row[$field])) {
            $row[$field] = fixImagePath($row[$field]);
        } else {
            $row[$field] = ''; // مقدار خالی اگر تصویر وجود نداشت
        }
    }
    
    // اصلاح متن فارسی
    foreach ($persianFields as $field) {
        if (isset($row[$field]) && !empty($row[$field])) {
            $row[$field] = fixPersianText($row[$field]);
        } else {
            $row[$field] = '';
        }
    }
    
    // تبدیل اعداد برای فیلدهای عددی
    if (isset($row['check1_amount']) && is_numeric($row['check1_amount'])) {
        $row['check1_amount'] = (float)$row['check1_amount'];
    }
    if (isset($row['check2_amount']) && is_numeric($row['check2_amount'])) {
        $row['check2_amount'] = (float)$row['check2_amount'];
    }
    if (isset($row['check3_amount']) && is_numeric($row['check3_amount'])) {
        $row['check3_amount'] = (float)$row['check3_amount'];
    }
    
    return $row;
}

/**
 * اصلاح مسیر تصویر
 */

/**
 * اصلاح متن فارسی
 */
function fixPersianText($text) {
    if (empty($text) || $text === null) {
        return '';
    }
    
    // تبدیل اعداد فارسی به انگلیسی (اگر نیاز باشد)
    $persianNumbers = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $arabicNumbers = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
    $englishNumbers = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    
    $text = str_replace($persianNumbers, $englishNumbers, $text);
    $text = str_replace($arabicNumbers, $englishNumbers, $text);
    
    // حذف فضاهای اضافی
    $text = trim($text);
    
    // decode HTML entities
    $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
    
    // حذف کاراکترهای خاص
    $text = preg_replace('/[\x00-\x1F\x7F]/u', '', $text);
    
    return $text;
}


/**
 * قالب‌بندی تاریخ شمسی
 */
function formatJalaliDate($date) {
    if (empty($date)) {
        return '';
    }
    
    // اگر تاریخ به فرمت YYYY-MM-DD است
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $matches)) {
        return $matches[1] . '/' . $matches[2] . '/' . $matches[3];
    }
    
    // اگر تاریخ به فرمت YYYY/MM/DD است
    if (preg_match('/^(\d{4})\/(\d{2})\/(\d{2})$/', $date, $matches)) {
        return $matches[1] . '/' . $matches[2] . '/' . $matches[3];
    }
    
    return $date;
}



/**
 * فرمت کردن مبلغ
 */
function formatAmount($amount) {
    if (empty($amount) || !is_numeric($amount)) {
        return '0';
    }
    
    return number_format((float)$amount, 0, '.', ',');
}

/**
 * اعتبارسنجی پارامترها
 */
function validateMemberParameters() {
    // دسترسی به متغیرهای GET
    $memberStatusFilter = isset($_GET['member_status']) ? $_GET['member_status'] : '';
    $searchQuery = isset($_GET['search']) ? $_GET['search'] : '';
    $itemsPerPage = isset($_GET['per_page']) ? $_GET['per_page'] : 20;
    $page = isset($_GET['page']) ? $_GET['page'] : 1;
    
    // اعتبارسنجی itemsPerPage
    $itemsPerPage = (int)$itemsPerPage;
    $itemsPerPage = max(1, min(100, $itemsPerPage));
    
    // اعتبارسنجی صفحه
    $page = (int)$page;
    $page = max(1, $page);
    
    // محاسبه offset
    $offset = ($page - 1) * $itemsPerPage;
    
    // پاکسازی searchQuery
    if (!empty($searchQuery)) {
        $searchQuery = trim($searchQuery);
        $searchQuery = strip_tags($searchQuery);
        $searchQuery = htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8');
    }
    
    // پاکسازی status filter
    $allowedStatuses = ['pending', 'approved', 'rejected', 'active', 'inactive', ''];
    if (!in_array($memberStatusFilter, $allowedStatuses)) {
        $memberStatusFilter = '';
    }
    
    return [
        'memberStatusFilter' => $memberStatusFilter,
        'searchQuery' => $searchQuery,
        'itemsPerPage' => $itemsPerPage,
        'page' => $page,
        'offset' => $offset
    ];
}

/**
 * تولید لینک صفحه‌بندی
 */
function generatePagination($totalPages, $currentPage, $baseUrl) {
    if ($totalPages <= 1) {
        return '';
    }
    
    $pagination = '<nav><ul class="pagination justify-content-center">';
    
    // دکمه قبلی
    if ($currentPage > 1) {
        $prevUrl = $baseUrl . "&page=" . ($currentPage - 1);
        $pagination .= '<li class="page-item"><a class="page-link" href="' . $prevUrl . '">&laquo; قبلی</a></li>';
    }
    
    // صفحات
    $startPage = max(1, $currentPage - 2);
    $endPage = min($totalPages, $currentPage + 2);
    
    for ($i = $startPage; $i <= $endPage; $i++) {
        $active = ($i == $currentPage) ? ' active' : '';
        $pageUrl = $baseUrl . "&page=" . $i;
        
        $pagination .= '<li class="page-item' . $active . '"><a class="page-link" href="' . $pageUrl . '">' . $i . '</a></li>';
    }
    
    // دکمه بعدی
    if ($currentPage < $totalPages) {
        $nextUrl = $baseUrl . "&page=" . ($currentPage + 1);
        $pagination .= '<li class="page-item"><a class="page-link" href="' . $nextUrl . '">بعدی &raquo;</a></li>';
    }
    
    $pagination .= '</ul></nav>';
    return $pagination;
}

/**
 * لاگ خطاها
 */
function logError($message, $data = null) {
    $logMessage = "[" . date('Y-m-d H:i:s') . "] " . $message;
    
    if ($data !== null) {
        $logMessage .= " | Data: " . json_encode($data, JSON_UNESCAPED_UNICODE);
    }
    
    error_log($logMessage);
}

/**
 * بررسی اتصال به دیتابیس
 */
function checkDatabaseConnection($mysqli) {
    if (!$mysqli) {
        return false;
    }
    
    if ($mysqli->connect_error) {
        error_log("خطا در اتصال به دیتابیس: " . $mysqli->connect_error);
        return false;
    }
    
    return true;
}
function fixImagePath($imagePath) {
    if (empty($imagePath) || $imagePath === 'null') {
        return '';
    }
    
    // اگر قبلاً URL کامل است
    if (strpos($imagePath, 'http://') === 0 || strpos($imagePath, 'https://') === 0) {
        return $imagePath;
    }
    
    // استخراج نام فایل
    $filename = basename($imagePath);
    
    // ساخت مسیر جدید
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    
    // فرض می‌کنیم تصاویر در پوشه uploads هستند
    return $protocol . $host . '/uploads/' . $filename;
}
function fixImagePath2($filePath) {
    if (empty($filePath) || $filePath === '' || $filePath === 'null') {
        error_log("fixImagePath2: Empty path provided");
        return '';
    }
    
    error_log("fixImagePath2 INPUT: " . $filePath);
    
    // اگر URL کامل است
    if (strpos($filePath, 'http://') === 0 || strpos($filePath, 'https://') === 0) {
        return $filePath;
    }
    
    // استخراج نام فایل
    $filename = basename($filePath);
    error_log("Extracted filename: " . $filename);
    
    // ساخت URL کامل
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
    $baseUrl = $protocol . $_SERVER['HTTP_HOST'];
    
    // **مسیر اصلی: /uploads/documents/**
    $mainPath = '/uploads/documents/' . $filename;
    $mainUrl = $baseUrl . $mainPath;
    
    error_log("Main URL: " . $mainUrl);
    
    // بررسی وجود فایل
    $localPath = $_SERVER['DOCUMENT_ROOT'] . $mainPath;
    
    if (file_exists($localPath)) {
        error_log("✅ File exists: " . $localPath);
        return $mainUrl;
    } else {
        error_log("❌ File not found in main path: " . $localPath);
        
        // تست مسیرهای جایگزین
        $alternativePaths = [
            '/uploads/' . $filename,
            '/admin/uploads/' . $filename,
            '/files/' . $filename,
            '/images/' . $filename
        ];
        
        foreach ($alternativePaths as $altPath) {
            $altLocalPath = $_SERVER['DOCUMENT_ROOT'] . $altPath;
            $altUrl = $baseUrl . $altPath;
            
            if (file_exists($altLocalPath)) {
                error_log("✅ Found in alternative: " . $altLocalPath);
                return $altUrl;
            } else {
                error_log("❌ Not found: " . $altLocalPath);
            }
        }
    }
    
    // اگر در هیچ‌کدام پیدا نشد
    error_log("⚠️ File not found anywhere, returning main URL anyway");
    return $mainUrl;
}

function sortLink($label, $field, $currentSort, $currentOrder, $baseUrl) {
    $order = 'asc';
    $icon = '<i class="bi bi-arrow-down-up text-muted ms-1"></i>';

    if ($currentSort === $field) {
        if ($currentOrder === 'asc') {
            $order = 'desc';
            $icon = '<i class="bi bi-arrow-up ms-1"></i>';
        } else {
            $order = 'asc';
            $icon = '<i class="bi bi-arrow-down ms-1"></i>';
        }
    }

    $url = $baseUrl . "&doc_sort=$field&doc_order=$order";

    return '<a href="'.$url.'" class="text-white text-decoration-none">'
           . $label . $icon .
           '</a>';
}
?>