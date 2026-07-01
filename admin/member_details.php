<?php
// member_details.php
session_start();
require_once 'helpers.php'; // 
// بررسی فعال بودن session
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// بررسی دسترسی ادمین
if (empty($_SESSION['admin_id']) || empty($_SESSION['admin_username'])) {
    http_response_code(403);
    die("<h3 style='text-align:center;margin-top:50px;color:#dc3545;font-family:Tahoma;'>⛔ دسترسی غیرمجاز - لطفا ابتدا وارد شوید</h3>");
}

// بررسی و اعتبارسنجی ID عضو
$member_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($member_id <= 0) {
    http_response_code(400);
    die("<h3 style='text-align:center;margin-top:50px;color:#dc3545;font-family:Tahoma;'>⚠️ شناسه عضو نامعتبر است</h3>");
}

// اتصال به دیتابیس با مدیریت خطا
try {
    $mysqli = new mysqli('localhost', 'maskanhb_maskansazan', 'NJ)B*$@y3R9P0Oyg', 'maskanhb_maskansazan');
    
    if ($mysqli->connect_error) {
        throw new Exception("خطا در اتصال به دیتابیس: " . $mysqli->connect_error);
    }
    
    $mysqli->set_charset("utf8mb4");
    
    // دریافت اطلاعات کامل عضو
    $stmt = $mysqli->prepare("SELECT * FROM members WHERE id = ?");
    if (!$stmt) {
        throw new Exception("خطا در آماده‌سازی کوئری: " . $mysqli->error);
    }
    
    $stmt->bind_param("i", $member_id);
    
    if (!$stmt->execute()) {
        throw new Exception("خطا در اجرای کوئری: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $member = $result->fetch_assoc();
    
    $stmt->close();
    $mysqli->close();
    
    if (!$member) {
        http_response_code(404);
        die("<h3 style='text-align:center;margin-top:50px;color:#dc3545;font-family:Tahoma;'>👤 عضو مورد نظر یافت نشد</h3>");
    }
    
} catch (Exception $e) {
    // ذخیره خطا در لاگ
    error_log("[" . date('Y-m-d H:i:s') . "] Database Error in member_details.php: " . $e->getMessage());
    
    // نمایش پیام خطا به کاربر
    header('Content-Type: text/html; charset=utf-8');
    die("<h3 style='text-align:center;margin-top:50px;color:#dc3545;font-family:Tahoma;'>⚠️ خطای سیستمی - لطفا با پشتیبانی تماس بگیرید<br><small>کد خطا: " . time() . "</small></h3>");
}

/**
 * تابع اصلاح مسیر تصاویر برای نمایش صحیح
 */
function fixImagePath($imagePath, $fieldName = '') {
    // اگر خالی است
    if (empty($imagePath) || trim($imagePath) === '' || $imagePath === 'NULL') {
        return null;
    }
    
    // حذف فاصله‌های اضافی
    $imagePath = trim($imagePath);
    
    // اگر مسیر کامل است (شامل http:// یا https://)
    if (strpos($imagePath, 'http://') === 0 || strpos($imagePath, 'https://') === 0) {
        return $imagePath;
    }
    
    // حذف ./ از ابتدای مسیر
    if (strpos($imagePath, './') === 0) {
        $imagePath = substr($imagePath, 2);
    }
    
    // اطمینان از شروع مسیر با /
    if (strpos($imagePath, '/') !== 0) {
        $imagePath = '/' . $imagePath;
    }
    
    // ساخت URL کامل
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    // لیست مسیرهای ممکن برای تست
    $possiblePaths = [
        $imagePath,
        '/uploads' . $imagePath,
        '/public/uploads' . $imagePath,
        '/home/maskanhb/public_html' . $imagePath,
        '/home/maskanhb/public_html/uploads' . $imagePath
    ];
    
    // اگر مسیر پایه وجود دارد، آن را هم اضافه کن
    if (isset($_SERVER['DOCUMENT_ROOT'])) {
        $docRoot = rtrim($_SERVER['DOCUMENT_ROOT'], '/');
        $possiblePaths[] = str_replace($docRoot, '', $imagePath);
    }
    
    // تست هر مسیر
    foreach ($possiblePaths as $path) {
        // ایجاد URL کامل
        $fullUrl = $protocol . $host . $path;
        
        // تست URL با استفاده از file_get_contents (برای سرعت بیشتر)
        $headers = @get_headers($fullUrl);
        if ($headers && strpos($headers[0], '200')) {
            return $fullUrl;
        }
        
        // همچنین تست وجود فایل در سرور
        if (isset($_SERVER['DOCUMENT_ROOT'])) {
            $localPath = $_SERVER['DOCUMENT_ROOT'] . $path;
            if (file_exists($localPath) && is_file($localPath)) {
                return $fullUrl;
            }
        }
    }
    
    // اگر هیچ کدام کار نکرد، لاگ خطا و برگشت null
    error_log("Image not found for field $fieldName: $imagePath");
    return null;
}


/**
 * تابع دریافت وضعیت به فارسی
 */
function getStatusText($status) {
    if (empty($status)) {
        return 'نامشخص';
    }
    
    $status = strtolower(trim($status));
    $statusMap = [
        'verified' => '✅ تأیید شده',
        'approved' => '✅ تأیید شده',
        'pending' => '⏳ در انتظار',
        'review' => '⏳ در حال بررسی',
        'rejected' => '❌ رد شده',
        'blocked' => '🚫 مسدود شده',
        'active' => '✅ فعال',
        'inactive' => '⏸️ غیرفعال'
    ];
    
    return $statusMap[$status] ?? htmlspecialchars($status, ENT_QUOTES, 'UTF-8');
}

/**
 * تابع دریافت رنگ وضعیت
 */
function getStatusColor($status) {
    if (empty($status)) {
        return '#6b7280';
    }
    
    $status = strtolower(trim($status));
    $colorMap = [
        'verified' => '#10b981',
        'approved' => '#10b981',
        'pending' => '#f59e0b',
        'review' => '#f59e0b',
        'rejected' => '#ef4444',
        'blocked' => '#ef4444',
        'active' => '#10b981',
        'inactive' => '#6b7280'
    ];
    
    return $colorMap[$status] ?? '#6b7280';
}

/**
 * تابع بررسی و نمایش تصویر امن
 */
function displayImage($imagePath, $alt = 'تصویر', $title = '', $class = 'img-fluid rounded', $width = 200, $maxHeight = 150) {
    if (empty($imagePath)) {
        $escapedAlt = htmlspecialchars($alt, ENT_QUOTES, 'UTF-8');
        return '
        <div class="no-image-placeholder" style="width: ' . $width . 'px; height: ' . $maxHeight . 'px; 
              background: linear-gradient(135deg, #f1f5f9, #e2e8f0); 
              border-radius: 8px; display: flex; flex-direction: column; 
              align-items: center; justify-content: center; color: #94a3b8; border: 1px dashed #cbd5e1;">
            <i class="bi bi-image" style="font-size: 2rem; margin-bottom: 10px;"></i>
            <span style="font-size: 0.85rem;">' . $escapedAlt . '</span>
            <small style="font-size: 0.7rem; margin-top: 5px; color: #94a3b8;">بدون تصویر</small>
        </div>';
    }
    
    $cleanPath = htmlspecialchars($imagePath, ENT_QUOTES, 'UTF-8');
    $cleanAlt = htmlspecialchars($alt, ENT_QUOTES, 'UTF-8');
    $cleanTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    
    // تولید کد HTML برای تصویر
    return '
    <div class="image-wrapper" style="position: relative; width: ' . $width . 'px; height: ' . $maxHeight . 'px; overflow: hidden; border-radius: 8px; border: 1px solid #e2e8f0;">
        <img src="' . $cleanPath . '" 
             alt="' . $cleanAlt . '" 
             title="' . $cleanTitle . '" 
             class="' . $class . '"
             style="width: 100%; height: 100%; object-fit: contain; cursor: pointer; padding: 5px;"
             loading="lazy"
             onclick="showImageModal(\'' . $cleanPath . '\', \'' . $cleanAlt . '\')"
             onerror="this.onerror=null; this.style.display=\'none\'; this.nextElementSibling.style.display=\'flex\';">
        <div class="image-fallback" style="display: none; width: 100%; height: 100%; flex-direction: column; align-items: center; justify-content: center; background: linear-gradient(135deg, #f1f5f9, #e2e8f0); color: #94a3b8; border-radius: 8px;">
            <i class="bi bi-image" style="font-size: 1.5rem; margin-bottom: 5px;"></i>
            <span style="font-size: 0.8rem;">' . $cleanAlt . '</span>
            <small style="font-size: 0.65rem; margin-top: 3px; color: #94a3b8;">خطا در بارگذاری</small>
        </div>
        <div class="image-overlay" style="position: absolute; top: 0; right: 0; width: 100%; height: 100%; background: rgba(0,0,0,0); transition: background 0.3s; display: flex; align-items: center; justify-content: center; opacity: 0;">
            <i class="bi bi-zoom-in" style="color: white; font-size: 1.5rem;"></i>
        </div>
    </div>
    <script>
        document.currentScript.parentElement.querySelector(\'.image-wrapper\').addEventListener(\'mouseenter\', function(e) {
            this.querySelector(\'.image-overlay\').style.background = \'rgba(0,0,0,0.5)\';
            this.querySelector(\'.image-overlay\').style.opacity = \'1\';
        });
        document.currentScript.parentElement.querySelector(\'.image-wrapper\').addEventListener(\'mouseleave\', function(e) {
            this.querySelector(\'.image-overlay\').style.background = \'rgba(0,0,0,0)\';
            this.querySelector(\'.image-overlay\').style.opacity = \'0\';
        });
    </script>';
}
function displayGalleryImage($imagePath, $alt = 'تصویر', $title = '') {
    if (empty($imagePath)) {
        $escapedAlt = htmlspecialchars($alt, ENT_QUOTES, 'UTF-8');
        return '
        <div class="no-image" style="width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #adb5bd; padding: 20px; text-align: center;">
            <i class="bi bi-image" style="font-size: 2.5rem; opacity: 0.5; margin-bottom: 10px;"></i>
            <span style="font-weight: 500;">' . $escapedAlt . '</span>
            <small style="font-size: 0.85rem; margin-top: 5px; opacity: 0.7;">آپلود نشده</small>
        </div>';
    }
    
    $cleanPath = htmlspecialchars($imagePath, ENT_QUOTES, 'UTF-8');
    $cleanAlt = htmlspecialchars($alt, ENT_QUOTES, 'UTF-8');
    $cleanTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    
    return '
    <img src="' . $cleanPath . '" 
         alt="' . $cleanAlt . '" 
         title="' . $cleanTitle . '" 
         class="img-fluid rounded"
         style="width: 100%; height: 100%; object-fit: contain; cursor: pointer; padding: 10px;"
         loading="lazy"
         onclick="showImageModal(\'' . $cleanPath . '\', \'' . $cleanAlt . '\')"
         onerror="this.onerror=null; this.style.display=\'none\'; this.parentElement.innerHTML=\'<div class=\"no-image\" style=\"width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #adb5bd; padding: 20px; text-align: center;\"><i class=\"bi bi-image\" style=\"font-size: 2.5rem; opacity: 0.5; margin-bottom: 10px;\"></i><span style=\"font-weight: 500;\">' . $cleanAlt . '</span><small style=\"font-size: 0.85rem; margin-top: 5px; opacity: 0.7;\">خطا در بارگذاری</small></div>\';">
    <div class="image-hover-overlay" style="position: absolute; top: 0; right: 0; width: 100%; height: 100%; background: rgba(67, 97, 238, 0); display: flex; align-items: center; justify-content: center; opacity: 0; transition: all 0.3s; pointer-events: none;">
        <i class="bi bi-zoom-in" style="color: white; font-size: 2rem;"></i>
    </div>';
}
$imageFields = [
    'national_card_image',
    'birth_certificate_image', 
    'check1_image',
    'check2_image',
    'check3_image'
];

// اصلاح مسیرها با تابع بهبود یافته
foreach ($imageFields as $field) {
    if (isset($member[$field]) && !empty(trim($member[$field]))) {
        // لاگ مسیر اصلی برای دیباگ
        error_log("Processing image field '$field': " . $member[$field]);
        
        $member[$field] = fixImagePath($member[$field], $field);
        
        // لاگ مسیر اصلاح شده
        error_log("Fixed path for '$field': " . ($member[$field] ?? 'NULL'));
    } else {
        $member[$field] = null;
    }
}


/**
 * تابع نمایش اطلاعات به صورت امن
 */
function displayInfo($value, $default = '---') {
    if (empty($value) || trim($value) === '') {
        $escapedDefault = htmlspecialchars($default, ENT_QUOTES, 'UTF-8');
        return '<span class="text-muted">' . $escapedDefault . '</span>';
    }
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

/**
 * تابع فرمت‌دهی تاریخ
 */
function formatDate($date, $format = 'Y/m/d H:i') {
    if (empty($date) || $date == '0000-00-00 00:00:00' || $date == '0000-00-00') {
        return '---';
    }
    
    try {
        $dateTime = new DateTime($date);
        return $dateTime->format($format);
    } catch (Exception $e) {
        return htmlspecialchars($date, ENT_QUOTES, 'UTF-8');
    }
}

/**
 * تابع فرمت کردن مبلغ (اضافه کردن جداکننده هزارگان)
 */
function formatAmount($amount, $default = '---') {
    if (empty($amount) || !is_numeric($amount) || $amount == 0) {
        return displayInfo($default);
    }
    
    // تبدیل به عدد
    $amount = floatval($amount);
    
    // فرمت با جداکننده هزارگان فارسی
    $formatted = number_format($amount, 0, '.', '،');
    
    return htmlspecialchars($formatted, ENT_QUOTES, 'UTF-8') . ' تومان';
}

// اصلاح مسیر تصاویر با بررسی کامل
$imageFields = [
    'national_card_image',
    'birth_certificate_image', 
    'check1_image',
    'check2_image',
    'check3_image'
];

foreach ($imageFields as $field) {
    if (isset($member[$field])) {
        $member[$field] = fixImagePath($member[$field], $field);
    } else {
        $member[$field] = null;
    }
}

// تنظیم متغیرهای امن
$member_name = displayInfo(($member['first_name'] ?? '') . ' ' . ($member['last_name'] ?? ''));
$national_id = displayInfo($member['national_id'] ?? '');
$mobile = displayInfo($member['mobile'] ?? '');
$status_text = getStatusText($member['status'] ?? 'pending');
$status_color = getStatusColor($member['status'] ?? 'pending');

// تنظیم متغیرهای امن دیگر
$birth_certificate_number = displayInfo($member['birth_certificate_number'] ?? '');
$father_name = displayInfo($member['father_name'] ?? '');
$birth_date_fa = displayInfo($member['birth_date_fa'] ?? '');
$phone = displayInfo($member['phone'] ?? '');
$address = displayInfo($member['address'] ?? '');
$created_at = formatDate($member['created_at'] ?? '');
$updated_at = formatDate($member['updated_at'] ?? '');

// اطلاعات چک‌ها
$checkInfo = [];
for ($i = 1; $i <= 3; $i++) {
    $checkInfo[$i] = [
        'number' => displayInfo($member["check{$i}_number"] ?? ''),
        'amount' => formatAmount($member["check{$i}_amount"] ?? ''),
        'bank' => displayInfo($member["check{$i}_bank"] ?? ''),
        'date' => displayInfo($member["check{$i}_date"] ?? ''),
        'image' => $member["check{$i}_image"] ?? null,
        'has_data' => !empty($member["check{$i}_number"]) || !empty($member["check{$i}_amount"]) || 
                      !empty($member["check{$i}_bank"]) || !empty($member["check{$i}_date"])
    ];
}

// آمار چک‌ها
$totalCheques = 0;
$totalAmount = 0;
for ($i = 1; $i <= 3; $i++) {
    if (!empty($member["check{$i}_number"]) && $member["check{$i}_amount"] > 0) {
        $totalCheques++;
        $totalAmount += floatval($member["check{$i}_amount"] ?? 0);
    }
}

// ========== تغییرات اضافه شده ==========

// استایل CSS اضافی برای بخش چک‌ها
$additionalStyles = '
<style>
    /* استایل‌های جدید برای بخش چک‌ها */
    .cheque-section {
        background: white;
        border-radius: 15px;
        padding: 25px;
        margin-bottom: 25px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        border: 1px solid #e9ecef;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .cheque-section:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
    }
    
    .cheque-section::before {
        content: \'\';
        position: absolute;
        top: 0;
        right: 0;
        width: 5px;
        height: 100%;
        background: #f59e0b;
    }
    
    .cheque-card {
        background: linear-gradient(135deg, #fff9db, #ffec99);
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 15px;
        border: 1px solid #ffd43b;
        transition: all 0.3s;
    }
    
    .cheque-card:hover {
        background: linear-gradient(135deg, #ffec99, #ffd43b);
        transform: translateY(-3px);
    }
    
    .cheque-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 2px dashed #ffd43b;
    }
    
    .cheque-number {
        font-size: 1.1rem;
        font-weight: 700;
        color: #f59e0b;
        background: rgba(245, 158, 11, 0.1);
        padding: 5px 15px;
        border-radius: 20px;
    }
    
    .cheque-details {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 15px;
    }
    
    .cheque-detail-item {
        background: white;
        padding: 12px;
        border-radius: 8px;
        border: 1px solid #e9ecef;
    }
    
    .cheque-detail-label {
        font-size: 0.85rem;
        color: #6c757d;
        margin-bottom: 5px;
    }
    
    .cheque-detail-value {
        font-size: 1rem;
        font-weight: 600;
        color: #212529;
    }
    
    .cheque-image-container {
        margin-top: 15px;
        text-align: center;
    }
    
    .cheque-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }
    
    .stat-item {
        background: white;
        padding: 15px;
        border-radius: 10px;
        text-align: center;
        box-shadow: 0 3px 10px rgba(0,0,0,0.05);
    }
    
    .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: #f59e0b;
        margin-bottom: 5px;
    }
    
    .stat-label {
        font-size: 0.9rem;
        color: #6c757d;
    }
    
    .no-cheque-data {
        text-align: center;
        padding: 40px 20px;
        color: #6c757d;
    }
    
    .no-cheque-data i {
        font-size: 3rem;
        margin-bottom: 15px;
        opacity: 0.5;
    }
    
    @media (max-width: 768px) {
        .cheque-details {
            grid-template-columns: 1fr;
        }
        
        .cheque-stats {
            grid-template-columns: 1fr;
        }
    }
</style>
';
$imageStyles = '
<style>
    /* استایل‌های جدید برای بهبود نمایش تصاویر */
    .image-container {
        position: relative;
        width: 100%;
        height: 200px;
        overflow: hidden;
        background: #f8f9fa;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #e9ecef;
    }
    
    .image-container img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
        transition: transform 0.5s;
        cursor: pointer;
        padding: 10px;
    }
    
    .image-container:hover img {
        transform: scale(1.05);
    }
    
    .image-container:hover .image-hover-overlay {
        background: rgba(67, 97, 238, 0.3);
        opacity: 1;
    }
    
    .image-hover-overlay {
        position: absolute;
        top: 0;
        right: 0;
        width: 100%;
        height: 100%;
        background: rgba(67, 97, 238, 0);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: all 0.3s;
        pointer-events: none;
        border-radius: 8px;
    }
    
    .image-hover-overlay i {
        color: white;
        font-size: 2rem;
        text-shadow: 0 2px 4px rgba(0,0,0,0.3);
    }
    
    .no-image-improved {
        width: 100%;
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: #adb5bd;
        padding: 20px;
        text-align: center;
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        border-radius: 8px;
        border: 2px dashed #dee2e6;
    }
    
    .no-image-improved i {
        font-size: 2.5rem;
        opacity: 0.5;
        margin-bottom: 10px;
    }
    
    .no-image-improved span {
        font-weight: 500;
        margin-bottom: 5px;
    }
    
    .no-image-improved small {
        font-size: 0.85rem;
        opacity: 0.7;
    }
    
    /* بهبود نمایش تصاویر در چک‌ها */
    .cheque-image-container .image-wrapper {
        max-width: 250px;
        margin: 0 auto;
    }
    
    /* استایل برای تصاویر در مودال */
    .modal-image-container {
        width: 100%;
        height: 70vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f8f9fa;
        border-radius: 10px;
        overflow: hidden;
    }
    
    .modal-image-container img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }
</style>
';

// ========== پایان تغییرات ==========
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>جزئیات عضو - <?= $member_name ?></title>
    
    <!-- Bootstrap 5 RTL -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet" crossorigin="anonymous">
    
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet" crossorigin="anonymous">
    
    <!-- Font Vazirmatn (فونت فارسی) -->
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet" crossorigin="anonymous">
    
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3a0ca3;
            --success: #4cc9f0;
            --info: #4895ef;
            --warning: #f72585;
            --light: #f8f9fa;
            --dark: #212529;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Vazirmatn', 'Tahoma', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 20px;
            line-height: 1.6;
            color: #333;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Header Styles */
        .profile-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            color: white;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(67, 97, 238, 0.3);
        }
        
        .profile-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 30px 30px;
            opacity: 0.1;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: white;
            border: 4px solid rgba(255, 255, 255, 0.3);
            margin-left: 20px;
            transition: transform 0.3s;
        }
        
        .profile-avatar:hover {
            transform: scale(1.05);
        }
        
        .member-id-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        /* Card Styles */
        .info-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .info-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        }
        
        .info-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 5px;
            height: 100%;
            background: var(--primary);
        }
        
        .card-title {
            color: var(--primary);
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e9ecef;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .card-title i {
            font-size: 1.3rem;
        }
        
        /* Detail Items */
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f8f9fa;
            transition: background 0.2s;
        }
        
        .detail-row:hover {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 12px 10px;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            color: #6c757d;
            font-weight: 500;
            min-width: 200px;
        }
        
        .detail-value {
            color: #212529;
            font-weight: 600;
            text-align: left;
            flex: 1;
        }
        
        /* Status Badge */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            color: white;
        }
        
        /* Image Gallery */
        .image-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .image-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
            border: 1px solid #e9ecef;
            height: 280px;
            display: flex;
            flex-direction: column;
        }
        
        .image-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }
        
        .image-container {
            flex: 1;
            width: 100%;
            height: 200px;
            overflow: hidden;
            position: relative;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .image-container img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            transition: transform 0.5s;
            cursor: pointer;
            padding: 10px;
            max-height: 200px;
        }
        
        .image-container:hover img {
            transform: scale(1.05);
        }
        
        .image-title {
            padding: 15px;
            text-align: center;
            background: white;
            font-weight: 600;
            color: #495057;
            border-top: 1px solid #e9ecef;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .no-image {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #adb5bd;
            gap: 10px;
            padding: 20px;
            text-align: center;
        }
        
        .no-image i {
            font-size: 2.5rem;
            opacity: 0.5;
        }
        
        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn-custom {
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            border: none;
            font-size: 1rem;
            cursor: pointer;
            text-decoration: none;
        }
        
        .btn-primary-custom {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
        }
        
        .btn-primary-custom:hover {
            background: linear-gradient(135deg, var(--secondary), var(--primary));
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.4);
            color: white;
        }
        
        .btn-secondary-custom {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary-custom:hover {
            background: #5a6268;
            transform: translateY(-3px);
            color: white;
        }
        
        .btn-outline-primary {
            border: 2px solid var(--primary);
            color: var(--primary);
            background: transparent;
        }
        
        .btn-outline-primary:hover {
            background: var(--primary);
            color: white;
        }
        
        /* Modal */
        .modal-image {
            max-width: 100%;
            max-height: 70vh;
            border-radius: 10px;
            margin: 0 auto;
            display: block;
            object-fit: contain;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .profile-header {
                padding: 20px;
            }
            
            .profile-avatar {
                width: 80px;
                height: 80px;
                font-size: 2rem;
                margin: 0 auto 15px;
            }
            
            .detail-row {
                flex-direction: column;
                gap: 5px;
            }
            
            .detail-label {
                min-width: auto;
                font-size: 0.9rem;
            }
            
            .image-gallery {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
            
            .image-card {
                height: 250px;
            }
            
            .image-container {
                height: 170px;
            }
            
            .image-container img {
                max-height: 170px;
            }
            
            .action-buttons {
                flex-direction: column;
                width: 100%;
            }
            
            .btn-custom {
                width: 100%;
                justify-content: center;
            }
        }
        
        @media print {
            body {
                background: white !important;
                padding: 10px !important;
            }
            
            .action-buttons,
            .btn-custom,
            .profile-header::before,
            .profile-avatar:hover,
            .image-container:hover img,
            .btn-custom:hover {
                display: none !important;
                transform: none !important;
            }
            
            .info-card {
                box-shadow: none !important;
                border: 1px solid #ddd !important;
                page-break-inside: avoid;
            }
            
            .image-gallery {
                display: block !important;
            }
            
            .image-card {
                margin-bottom: 20px;
                page-break-inside: avoid;
                box-shadow: none !important;
                border: 1px solid #ddd !important;
                height: auto;
            }
            
            .profile-header {
                box-shadow: none !important;
                border: 1px solid #ddd !important;
            }
        }
    </style>
    
    <!-- ========== اضافه کردن استایل‌های جدید ========== -->
    <?= $additionalStyles ?>
     <?= $imageStyles ?>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="profile-header">
            <div class="row align-items-center">
                <div class="col-md-auto text-center text-md-start mb-3 mb-md-0">
                    <div class="profile-avatar">
                        <i class="bi bi-person-fill"></i>
                    </div>
                </div>
                <div class="col-md">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h1 class="h2 mb-2"><?= $member_name ?></h1>
                            <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                                <?php if($national_id !== '---'): ?>
                                    <span class="member-id-badge">
                                        <i class="bi bi-credit-card me-1"></i> کدملی: <?= $national_id ?>
                                    </span>
                                <?php endif; ?>
                                
                                <?php if($mobile !== '---'): ?>
                                    <span class="member-id-badge">
                                        <i class="bi bi-phone me-1"></i> موبایل: <?= $mobile ?>
                                    </span>
                                <?php endif; ?>
                                
                                <span class="status-badge" style="background-color: <?= $status_color ?>;">
                                    <i class="bi bi-circle-fill" style="font-size: 0.6rem;"></i>
                                    <?= $status_text ?>
                                </span>
                            </div>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-light text-dark">
                                <i class="bi bi-hash me-1"></i> شناسه: #<?= $member_id ?>
                            </span>
                        </div>
                    </div>
                    <div class="text-muted">
                        <i class="bi bi-calendar-check me-1"></i>
                        تاریخ عضویت: <?= $created_at ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Stats -->
        <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 25px;">
            <div class="stat-card" style="background: white; border-radius: 12px; padding: 20px; text-align: center; box-shadow: 0 3px 10px rgba(0,0,0,0.08); transition: transform 0.3s;">
                <div class="stat-number" style="font-size: 2rem; font-weight: 700; color: var(--primary); margin-bottom: 5px;"><?= $national_id ?></div>
                <div class="stat-label" style="font-size: 0.9rem; color: #6c757d;">کدملی</div>
            </div>
            
            <div class="stat-card" style="background: white; border-radius: 12px; padding: 20px; text-align: center; box-shadow: 0 3px 10px rgba(0,0,0,0.08); transition: transform 0.3s;">
                <div class="stat-number" style="font-size: 2rem; font-weight: 700; color: var(--primary); margin-bottom: 5px;"><?= $birth_certificate_number ?></div>
                <div class="stat-label" style="font-size: 0.9rem; color: #6c757d;">شماره شناسنامه</div>
            </div>
            
            <div class="stat-card" style="background: white; border-radius: 12px; padding: 20px; text-align: center; box-shadow: 0 3px 10px rgba(0,0,0,0.08); transition: transform 0.3s;">
                <div class="stat-number" style="font-size: 2rem; font-weight: 700; color: var(--primary); margin-bottom: 5px;"><?= $mobile ?></div>
                <div class="stat-label" style="font-size: 0.9rem; color: #6c757d;">موبایل</div>
            </div>
            
            <!-- ========== آمار چک‌ها ========== -->
            <div class="stat-card" style="background: white; border-radius: 12px; padding: 20px; text-align: center; box-shadow: 0 3px 10px rgba(0,0,0,0.08); transition: transform 0.3s;">
                <div class="stat-number" style="font-size: 2rem; font-weight: 700; color: #f59e0b; margin-bottom: 5px;"><?= $totalCheques ?></div>
                <div class="stat-label" style="font-size: 0.9rem; color: #6c757d;">تعداد چک‌ها</div>
            </div>
            
            <?php if($totalCheques > 0): ?>
            <div class="stat-card" style="background: white; border-radius: 12px; padding: 20px; text-align: center; box-shadow: 0 3px 10px rgba(0,0,0,0.08); transition: transform 0.3s;">
                <div class="stat-number" style="font-size: 2rem; font-weight: 700; color: #10b981; margin-bottom: 5px;">
                    <?= number_format($totalAmount, 0, '.', '،') ?>
                </div>
                <div class="stat-label" style="font-size: 0.9rem; color: #6c757d;">مجموع مبالغ</div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Personal Information -->
        <div class="info-card">
            <h3 class="card-title">
                <i class="bi bi-person-vcard"></i>
                اطلاعات شخصی
            </h3>
            <div class="info-list">
                <div class="detail-row">
                    <span class="detail-label">نام و نام خانوادگی:</span>
                    <span class="detail-value"><?= $member_name ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">نام پدر:</span>
                    <span class="detail-value"><?= $father_name ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">تاریخ تولد:</span>
                    <span class="detail-value"><?= $birth_date_fa ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">وضعیت:</span>
                    <span class="detail-value">
                        <span class="status-badge" style="background-color: <?= $status_color ?>;">
                            <?= $status_text ?>
                        </span>
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Contact Information -->
        <div class="info-card">
            <h3 class="card-title">
                <i class="bi bi-telephone-outbound"></i>
                اطلاعات تماس
            </h3>
            <div class="info-list">
                <div class="detail-row">
                    <span class="detail-label">شماره موبایل:</span>
                    <span class="detail-value"><?= $mobile ?></span>
                </div>
                
                <?php if($phone !== '---'): ?>
                <div class="detail-row">
                    <span class="detail-label">تلفن ثابت:</span>
                    <span class="detail-value"><?= $phone ?></span>
                </div>
                <?php endif; ?>
                
                <?php if($address !== '---'): ?>
                <div class="detail-row">
                    <span class="detail-label">آدرس:</span>
                    <span class="detail-value"><?= $address ?></span>
                </div>
                <?php endif; ?>
                
                <div class="detail-row">
                    <span class="detail-label">تاریخ ثبت‌نام:</span>
                            <span class="detail-value"><?= mysql_to_jalali($member['created_at']) ?></span>
                </div>
                
                <?php if(!empty($member['updated_at']) && $member['updated_at'] !== $member['created_at']): ?>
                <div class="detail-row">
                    <span class="detail-label">آخرین بروزرسانی:</span>
                            <span class="detail-value"><?= mysql_to_jalali($member['updated_at']) ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- ========== بخش جدید: اطلاعات چک‌ها ========== -->
        <div class="cheque-section">
            <h3 class="card-title">
                <i class="bi bi-credit-card"></i>
                اطلاعات چک‌ها
            </h3>
            
            <?php if($totalCheques > 0): ?>
                <!-- آمار چک‌ها -->
                <div class="cheque-stats">
                    <div class="stat-item">
                        <div class="stat-value"><?= $totalCheques ?></div>
                        <div class="stat-label">تعداد چک‌ها</div>
                    </div>
                    
                    <div class="stat-item">
                        <div class="stat-value"><?= number_format($totalAmount, 0, '.', '،') ?></div>
                        <div class="stat-label">مجموع مبالغ (تومان)</div>
                    </div>
                    
                    <?php if($totalCheques > 0): ?>
                    <div class="stat-item">
                        <div class="stat-value"><?= number_format($totalAmount / $totalCheques, 0, '.', '،') ?></div>
                        <div class="stat-label">میانگین مبلغ (تومان)</div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- لیست چک‌ها -->
                <?php for ($i = 1; $i <= 3; $i++): ?>
                    <?php if($checkInfo[$i]['has_data']): ?>
                        <div class="cheque-card">
                            <div class="cheque-header">
                                <div class="cheque-number">
                                    <i class="bi bi-credit-card me-2"></i>
                                    چک شماره <?= $i ?>
                                </div>
                                <?php if($checkInfo[$i]['amount'] !== '---'): ?>
                                    <span class="badge bg-warning" style="font-size: 0.9rem;">
                                        <?= $checkInfo[$i]['amount'] ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="cheque-details">
                                <?php if($checkInfo[$i]['number'] !== '---'): ?>
                                    <div class="cheque-detail-item">
                                        <div class="cheque-detail-label">شماره چک:</div>
                                        <div class="cheque-detail-value"><?= $checkInfo[$i]['number'] ?></div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if($checkInfo[$i]['bank'] !== '---'): ?>
                                    <div class="cheque-detail-item">
                                        <div class="cheque-detail-label">بانک صادرکننده:</div>
                                        <div class="cheque-detail-value"><?= $checkInfo[$i]['bank'] ?></div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if($checkInfo[$i]['date'] !== '---'): ?>
                                    <div class="cheque-detail-item">
                                        <div class="cheque-detail-label">تاریخ چک:</div>
                                        <div class="cheque-detail-value"><?= $checkInfo[$i]['date'] ?></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if(!empty($checkInfo[$i]['image'])): ?>
                                <div class="cheque-image-container">
                                    <div class="mb-2">
                                        <span class="text-muted small">تصویر چک:</span>
                                    </div>
                                    <?= displayImage($checkInfo[$i]['image'], "چک شماره {$i}", "چک شماره {$i}", 'img-fluid rounded', '200') ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endfor; ?>
                
            <?php else: ?>
                <div class="no-cheque-data">
                    <i class="bi bi-credit-card"></i>
                    <h5 style="color: #6c757d; margin-bottom: 10px;">اطلاعات چک‌ها ثبت نشده است</h5>
                    <p style="color: #adb5bd;">این کاربر تاکنون هیچ اطلاعات چکی ثبت نکرده است.</p>
                </div>
            <?php endif; ?>
        </div>
        <!-- ========== پایان بخش جدید ========== -->
        
        <!-- Documents Gallery -->
        <div class="info-card">
            <h3 class="card-title">
                <i class="bi bi-files"></i>
                مدارک و اسناد
            </h3>
            <div class="image-gallery">
                <!-- National Card -->
                <div class="image-card">
                    <div class="image-container">
                        <?php if(!empty($member['national_card_image'])): ?>
                            <?= displayImage($member['national_card_image'], 'کارت ملی', 'کارت ملی', 'img-fluid rounded', '100%') ?>
                        <?php else: ?>
                            <div class="no-image">
                                <i class="bi bi-person-badge"></i>
                                <span>کارت ملی</span>
                                <small class="text-muted">آپلود نشده</small>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="image-title">کارت ملی</div>
                </div>
                
                <!-- Birth Certificate -->
                <div class="image-card">
                    <div class="image-container">
                        <?php if(!empty($member['birth_certificate_image'])): ?>
                            <?= displayImage($member['birth_certificate_image'], 'شناسنامه', 'شناسنامه', 'img-fluid rounded', '100%') ?>
                        <?php else: ?>
                            <div class="no-image">
                                <i class="bi bi-file-text"></i>
                                <span>شناسنامه</span>
                                <small class="text-muted">آپلود نشده</small>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="image-title">شناسنامه</div>
                </div>
                
                <!-- Checks -->
                <?php for($i = 1; $i <= 3; $i++): ?>
                    <?php 
                    $imageField = "check{$i}_image";
                    $imageTitle = "چک شماره {$i}";
                    $imageValue = $member[$imageField] ?? null;
                    ?>
                    <div class="image-card">
                        <div class="image-container">
                            <?php if(!empty($imageValue)): ?>
                                <?= displayImage($imageValue, $imageTitle, $imageTitle, 'img-fluid rounded', '100%') ?>
                            <?php else: ?>
                                <div class="no-image">
                                    <i class="bi bi-credit-card"></i>
                                    <span><?= htmlspecialchars($imageTitle, ENT_QUOTES, 'UTF-8') ?></span>
                                    <small class="text-muted">آپلود نشده</small>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="image-title"><?= htmlspecialchars($imageTitle, ENT_QUOTES, 'UTF-8') ?></div>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="action-buttons">
            <button onclick="window.print()" class="btn btn-custom btn-primary-custom">
                <i class="bi bi-printer"></i>
                چاپ اطلاعات
            </button>
        
            
            <?php if(isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])): ?>
            <a href="<?= htmlspecialchars($_SERVER['HTTP_REFERER'], ENT_QUOTES, 'UTF-8') ?>" class="btn btn-custom btn-outline-primary">
                <i class="bi bi-list-ul"></i>
                لیست اعضا
            </a>
            <?php endif; ?>
        
        </div>
    </div>
    
    <!-- Image Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center p-0">
                    <img id="modalImage" src="" class="modal-image" alt="">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i>بستن
                    </button>
                    <a id="downloadLink" href="#" class="btn btn-primary" download>
                        <i class="bi bi-download me-1"></i>دانلود
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    
    <script>
        // Image Modal Function
        function showImageModal(imageSrc, title) {
            if (!imageSrc || imageSrc.includes('data:image/svg+xml')) {
                alert('تصویری برای نمایش وجود ندارد');
                return;
            }
            
            const modalElement = document.getElementById('imageModal');
            const modal = new bootstrap.Modal(modalElement);
            const modalImage = document.getElementById('modalImage');
            const modalTitle = document.getElementById('modalTitle');
            const downloadLink = document.getElementById('downloadLink');
            
            // نمایش loading
            modalImage.src = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgdmlld0JveD0iMCAwIDEwMCAxMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGNpcmNsZSBjeD0iNTAiIGN5PSI1MCIgcj0iNDAiIHN0cm9rZT0iIzQzNjFlZSIgc3Ryb2tlLXdpZHRoPSI4Ii8+PHBhdGggZD0iTTUwIDI1VjUwTDEwIDc1IiBzdHJva2U9IiM0MzYxZWUiIHN0cm9rZS13aWR0aD0iOCIgc3Ryb2tlLWxpbmVjYXA9InJvdW5kIiBzdHJva2UtbGluZWpvaW49InJvdW5kIi8+PC9zdmc+';
            
            // بارگذاری تصویر
            const img = new Image();
            img.onload = function() {
                modalImage.src = imageSrc;
                modalTitle.textContent = title;
                downloadLink.href = imageSrc;
                downloadLink.download = title.replace(/[^\w\u0600-\u06FF]/g, '_') + '.jpg';
                modal.show();
            };
            
            img.onerror = function() {
                alert('خطا در بارگذاری تصویر');
                modal.hide();
            };
            
            img.src = imageSrc;
        }
        
        // Keyboard Shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl+P برای چاپ
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
            // Escape برای بازگشت
            if (e.key === 'Escape') {
                history.back();
            }
        });
        
        // Page Load Animation
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.info-card, .cheque-section');
            cards.forEach((card, index) => {
                card.style.animationDelay = (index * 0.1) + 's';
            });
        });
        
        // Prevent Image Drag
        document.addEventListener('dragstart', function(e) {
            if (e.target.tagName === 'IMG') {
                e.preventDefault();
            }
        });
        
        // Auto show modal if image fails to load
        document.addEventListener('error', function(e) {
            if (e.target.tagName === 'IMG' && e.target.classList.contains('img-fluid')) {
                console.log('Image failed to load:', e.target.src);
            }
        }, true);
    </script>
</body>
</html>