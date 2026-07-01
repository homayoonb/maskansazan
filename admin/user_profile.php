<?php
// admin/userprofile.php - نسخه اصلاح شده با نمایش جزئیات کامل
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// تنظیمات سشن
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0);
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

// توابع کمکی
if (!function_exists('safeEcho')) {
    function safeEcho($value) {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('getStatusBadge')) {
    function getStatusBadge($status) {
        switch(strtolower($status)) {
            case 'active':
            case 'verified':
                return '<span class="badge bg-success">تأیید شده</span>';
            case 'pending':
                return '<span class="badge bg-warning">در انتظار</span>';
            case 'rejected':
            case 'inactive':
                return '<span class="badge bg-danger">رد شده</span>';
            default:
                return '<span class="badge bg-info">' . safeEcho($status) . '</span>';
        }
    }
}

if (!function_exists('getUserStatusBadge')) {
    function getUserStatusBadge($main_member) {
        if ($main_member === 'اصلی' || $main_member === 'main') {
            return '<span class="badge bg-success">اصلی</span>';
        } else {
            return '<span class="badge bg-secondary">عضو</span>';
        }
    }
}

// بررسی ورود ادمین
if (empty($_SESSION['admin_id']) || empty($_SESSION['admin_username'])) {
    header('Location: loginAdmin.php');
    exit;
}

// ========== اتصال به دیتابیس ==========
class SafeDatabase {
    private static $connection = null;
    
    public static function connect() {
        if (self::$connection === null) {
            $DB_HOST = 'localhost';
            $DB_NAME = 'maskanhb_maskansazan';
            $DB_USER = 'root';
            $DB_PASS = '';
            
            self::$connection = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
            
            if (self::$connection->connect_error) {
                error_log("خطا در اتصال به دیتابیس: " . self::$connection->connect_error);
                return false;
            }
            
            self::$connection->set_charset("utf8mb4");
        }
        
        return self::$connection;
    }
}

// اتصال به دیتابیس
$mysqli = SafeDatabase::connect();
if (!$mysqli) {
    die("خطا در اتصال به پایگاه داده. لطفاً با مدیر سیستم تماس بگیرید.");
}

// بررسی ادمین در دیتابیس
$stmt = $mysqli->prepare("SELECT id, username, status FROM admins WHERE id = ? LIMIT 1");
if (!$stmt) {
    die("خطا در آماده‌سازی کوئری");
}

$stmt->bind_param("i", $_SESSION['admin_id']);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result ? $result->fetch_assoc() : null;
$stmt->close();

if (!$admin) {
    session_destroy();
    header('Location: loginAdmin.php');
    exit;
}

// بررسی وضعیت ادمین
$status = strtolower($admin['status'] ?? '');
if ($status !== 'active') {
    session_destroy();
    header('Location: loginAdmin.php');
    exit;
}

// ========== دریافت شناسه کاربر ==========
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
if ($user_id === 0) {
    die("شناسه کاربر نامعتبر است");
}

// ========== دریافت اطلاعات کامل کاربر ==========
$userData = null;
$userQuery = "
     SELECT 
        pm.*,
        p.name AS project_name,
        p.description AS project_description,
        (SELECT COUNT(*) FROM tickets WHERE project_id = pm.id) AS ticket_count,
        (SELECT COUNT(*) FROM cheque_transactions WHERE national_code = pm.national_code) AS cheque_count,
        (SELECT COUNT(*) FROM user_documents WHERE user_id = pm.id) AS document_count
    FROM project_members pm 
    LEFT JOIN projects p ON pm.project_id = p.id 
    WHERE pm.id = ?
    LIMIT 1
";

$stmt = $mysqli->prepare($userQuery);
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $userData = $result->fetch_assoc();
    $stmt->close();
}

// اگر کاربر یافت نشد
if (!$userData) {
    die("کاربر مورد نظر یافت نشد");
}

// ========== دریافت اطلاعات اضافی کاربر ==========

// 1. اطلاعات عضویت سایت (از جدول members)
$siteMemberInfo = null;
$memberQuery = "
    SELECT 
        m.*,
        m.status as member_status,
        m.created_at as member_created_at
    FROM members m 
    WHERE m.national_id = ? 
    LIMIT 1
";

if (!empty($userData['national_code'])) {
    $stmt = $mysqli->prepare($memberQuery);
    if ($stmt) {
        $stmt->bind_param("s", $userData['national_code']);
        $stmt->execute();
        $result = $stmt->get_result();
        $siteMemberInfo = $result->fetch_assoc();
        $stmt->close();
    }
}

// 2. اطلاعات تماس اضافی
$contactInfo = [];
if (!empty($userData['email'])) $contactInfo['ایمیل'] = $userData['email'];
if (!empty($userData['phone'])) $contactInfo['تلفن'] = $userData['phone'];
if (!empty($userData['mobile'])) $contactInfo['موبایل'] = $userData['mobile'];
if (!empty($userData['address'])) $contactInfo['آدرس'] = $userData['address'];

// 3. پروژه‌های کاربر
$userProjects = [];
$projectsQuery = "
    SELECT 
        pm.project_id,
        p.name as project_name,
        p.description as project_description,
        pm.main_member,
        pm.created_at as join_date,
        pm.is_active
    FROM project_members pm 
    LEFT JOIN projects p ON pm.project_id = p.id 
    WHERE pm.id = ? 
    ORDER BY pm.created_at DESC
";

$stmt = $mysqli->prepare($projectsQuery);
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $userProjects[] = $row;
    }
    $stmt->close();
}

// 4. آخرین تیکت‌ها
$userTickets = [];
$ticketsQuery = "
    SELECT 
        t.id,
        t.title,
        t.description,
        t.status,
        t.created_at,
        t.updated_at,
        p.name as project_name
    FROM tickets t 
    LEFT JOIN projects p ON t.project_id = p.id 
    WHERE t.user_id = ? 
    ORDER BY t.created_at DESC 
    LIMIT 5
";

$stmt = $mysqli->prepare($ticketsQuery);
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $userTickets[] = $row;
    }
    $stmt->close();
}

// 5. آخرین چک‌ها
$userCheques = [];
if (!empty($userData['national_code'])) {
    $chequesQuery = "
        SELECT 
            id,
            cheque_number,
            amount,
            cheque_date,
            bank_name,
            status,
            description,
            created_at
        FROM cheque_transactions 
        WHERE national_code = ? 
        ORDER BY cheque_date DESC 
        LIMIT 5
    ";
    
    $stmt = $mysqli->prepare($chequesQuery);
    if ($stmt) {
        $stmt->bind_param("s", $userData['national_code']);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $userCheques[] = $row;
        }
        $stmt->close();
    }
}

// 6. آخرین مدارک
$userDocuments = [];
$documentsQuery = "
    SELECT 
        id,
        document_type,
        file_path,
        verification_status,
        description,
        file_size,
        upload_date
    FROM user_documents 
    WHERE user_id = ? 
    ORDER BY upload_date DESC 
    LIMIT 5
";

$stmt = $mysqli->prepare($documentsQuery);
if (!function_exists('fixImagePath')) {
    function fixImagePath($imagePath) {
        if (empty($imagePath)) {
            return '';
        }
        
        if (strpos($imagePath, 'http://') === 0 || strpos($imagePath, 'https://') === 0) {
            return $imagePath;
        }
        
        $imagePath = trim($imagePath);
        
        if (strpos($imagePath, '/') !== 0) {
            $imagePath = '/' . $imagePath;
        }
        
        $possiblePaths = [
            $imagePath,
            '/uploads' . $imagePath,
            '/public/uploads' . $imagePath,
            '/home/maskanhb/public_html' . $imagePath,
        ];
        
        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
        
        foreach ($possiblePaths as $path) {
            $fullPath = $_SERVER['DOCUMENT_ROOT'] . $path;
            if (file_exists($fullPath)) {
                return $baseUrl . $path;
            }
        }
        
        return $baseUrl . $imagePath;
    }
}
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $row['file_path'] = fixImagePath($row['file_path'] ?? '');
        $userDocuments[] = $row;
    }
    $stmt->close();
}

// 7. فعالیت‌های کاربر (لاگ)
$userActivities = [];
$activitiesQuery = "
    SELECT 
        'ticket' as type,
        'ایجاد تیکت' as action,
        title as description,
        created_at
    FROM tickets 
    WHERE user_id = ? 
    UNION ALL
    SELECT 
        'cheque' as type,
        'ثبت چک' as action,
        CONCAT('چک شماره ', cheque_number, ' - ', amount, ' تومان') as description,
        created_at
    FROM cheque_transactions 
    WHERE national_code = ? 
    UNION ALL
    SELECT 
        'document' as type,
        'آپلود مدرک' as action,
        CONCAT('مدرک ', document_type) as description,
        upload_date as created_at
    FROM user_documents 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT 10
";

if (!empty($userData['national_code'])) {
    $stmt = $mysqli->prepare($activitiesQuery);
    if ($stmt) {
        $stmt->bind_param("isi", $user_id, $userData['national_code'], $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $userActivities[] = $row;
        }
        $stmt->close();
    }
}

// تابع اصلاح مسیر تصاویر
if (!function_exists('fixImagePath')) {
    function fixImagePath($imagePath) {
        if (empty($imagePath)) {
            return '';
        }
        
        if (strpos($imagePath, 'http://') === 0 || strpos($imagePath, 'https://') === 0) {
            return $imagePath;
        }
        
        $imagePath = trim($imagePath);
        
        if (strpos($imagePath, '/') !== 0) {
            $imagePath = '/' . $imagePath;
        }
        
        $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
        
        return $baseUrl . $imagePath;
    }
}

// تابع فرمت حجم فایل
if (!function_exists('formatFileSize')) {
    function formatFileSize($bytes) {
        if ($bytes == 0) return '0 بایت';
        $k = 1024;
        $sizes = ['بایت', 'کیلوبایت', 'مگابایت', 'گیگابایت'];
        $i = floor(log($bytes) / log($k));
        return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
    }
}

// تابع نمایش تاریخ فارسی
if (!function_exists('persianDate')) {
    function persianDate($date) {
        if (empty($date)) return '---';
        return date('Y/m/d H:i', strtotime($date));
    }
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>جزئیات کاربر - سامانه مسکن‌سازان</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4f46e5;
            --secondary-color: #6b7280;
            --bg-card: rgba(255,255,255,0.05);
        }
        
        body {
            background: linear-gradient(180deg,#0f172a,#0b1220);
            color: #e6eef8;
            font-family: Tahoma, sans-serif;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        
        .profile-header {
            background: linear-gradient(135deg, #1e1e2f, #2d2d44);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 20px;
            position: relative;
            overflow: hidden;
        }
        
        .profile-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 200px;
            height: 200px;
            background: linear-gradient(45deg, transparent, rgba(79, 70, 229, 0.1));
            border-radius: 0 0 0 100px;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #4f46e5, #7c73e6);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: white;
            margin-left: 20px;
            border: 4px solid rgba(255,255,255,0.1);
        }
        
        .card-ghost {
            background: var(--bg-card);
            border: none;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            backdrop-filter: blur(10px);
        }
        
        .stats-card {
            text-align: center;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 10px;
            background: rgba(255,255,255,0.1);
            transition: transform 0.3s;
            cursor: pointer;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            background: rgba(79, 70, 229, 0.2);
        }
        
        .stats-number {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 5px;
            color: white;
        }
        
        .stats-label {
            font-size: 0.8rem;
            color: rgba(255,255,255,0.8);
        }
        
        .detail-section {
            margin-bottom: 30px;
        }
        
        .detail-section h5 {
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 10px;
            margin-bottom: 20px;
            color: #e6eef8;
        }
        
        .detail-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .detail-item:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            color: #94a3b8;
            font-weight: 500;
            min-width: 150px;
        }
        
        .detail-value {
            color: #e6eef8;
            font-weight: 600;
            flex: 1;
            text-align: left;
        }
        
        .back-btn {
            background: rgba(255,255,255,0.1);
            border: none;
            color: #e6eef8;
            padding: 10px 20px;
            border-radius: 8px;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }
        
        .back-btn:hover {
            background: var(--primary-color);
            color: white;
            transform: translateX(-5px);
        }
        
        .image-preview {
            width: 80px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
            cursor: pointer;
            transition: transform 0.3s;
            background: rgba(255,255,255,0.1);
        }
        
        .image-preview:hover {
            transform: scale(1.5);
        }
        
        .activity-item {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 10px;
            background: rgba(255,255,255,0.05);
            border-right: 3px solid var(--primary-color);
        }
        
        .activity-type {
            font-size: 0.8rem;
            color: #94a3b8;
        }
        
        .activity-desc {
            font-weight: 500;
            margin: 5px 0;
        }
        
        .activity-time {
            font-size: 0.75rem;
            color: #64748b;
        }
        
        @media (max-width: 768px) {
            .profile-header {
                padding: 20px;
            }
            
            .profile-avatar {
                width: 80px;
                height: 80px;
                font-size: 1.8rem;
            }
            
            .detail-item {
                flex-direction: column;
                gap: 5px;
            }
            
            .detail-label {
                min-width: auto;
            }
        }
    </style>
</head>
<body>

<div class="container-fluid py-4">
    <!-- هدر صفحه -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <a href="dashboardAdmin.php?tab=documents" class="back-btn">
            <i class="bi bi-arrow-right me-2"></i>بازگشت به لیست کاربران
        </a>
        <h3 class="mb-0">جزئیات کاربر</h3>
        <div>
            <span class="badge bg-info">شناسه: <?= $user_id ?></span>
        </div>
    </div>

    <!-- هدر پروفایل -->
    <div class="profile-header">
        <div class="row align-items-center">
            <div class="col-auto">
                <div class="profile-avatar">
                    <i class="bi bi-person-fill"></i>
                </div>
            </div>
            <div class="col">
                <h2 class="mb-2">
                    <?= safeEcho(($userData['first_name'] ?? '') . ' ' . ($userData['last_name'] ?? 'نامشخص')) ?>
                </h2>
                <p class="text-white-50 mb-3">
                    <?php if(!empty($userData['national_code'])): ?>
                        کد ملی: <?= safeEcho($userData['national_code']) ?>
                    <?php endif; ?>
                    <?php if(!empty($userData['phone'])): ?>
                        - تلفن: <?= safeEcho($userData['phone']) ?>
                    <?php endif; ?>
                </p>
                <div class="d-flex gap-3 flex-wrap">
                    <?= getUserStatusBadge($userData['main_member'] ?? '') ?>
                    <span class="badge bg-info">
                        پروژه: <?= safeEcho($userData['project_name'] ?? 'نامشخص') ?>
                    </span>
                    <span class="badge bg-secondary">
                        عضویت از: <?= persianDate($userData['created_at'] ?? '') ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- آمار کلی -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stats-card" onclick="scrollToSection('projects')">
                <div class="stats-number"><?= $userData['ticket_count'] ?? 0 ?></div>
                <div class="stats-label">تعداد تیکت‌ها</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stats-card" onclick="scrollToSection('cheques')">
                <div class="stats-number"><?= $userData['cheque_count'] ?? 0 ?></div>
                <div class="stats-label">تعداد چک‌ها</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stats-card" onclick="scrollToSection('documents')">
                <div class="stats-number"><?= $userData['document_count'] ?? 0 ?></div>
                <div class="stats-label">تعداد مدارک</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stats-card" onclick="scrollToSection('activities')">
                <div class="stats-number"><?= count($userActivities) ?></div>
                <div class="stats-label">فعالیت‌ها</div>
            </div>
        </div>
    </div>

    <!-- بخش‌های اطلاعات -->
    <div class="row">
        <!-- ستون سمت راست - اطلاعات اصلی -->
        <div class="col-lg-8">
            <!-- اطلاعات شخصی -->
            <div class="card-ghost detail-section" id="personal">
                <h5><i class="bi bi-person-circle me-2"></i>اطلاعات شخصی</h5>
                <div class="info-list">
                    <div class="detail-item">
                        <span class="detail-label">نام و نام خانوادگی:</span>
                        <span class="detail-value"><?= safeEcho(($userData['first_name'] ?? '') . ' ' . ($userData['last_name'] ?? 'نامشخص')) ?></span>
                    </div>

                    <?php if(!empty($userData['national_code'])): ?>
                        <div class="detail-item">
                            <span class="detail-label">کد ملی:</span>
                            <span class="detail-value"><?= safeEcho($userData['national_code']) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if(!empty($userData['father_name'])): ?>
                        <div class="detail-item">
                            <span class="detail-label">نام پدر:</span>
                            <span class="detail-value"><?= safeEcho($userData['father_name']) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if(!empty($userData['birth_date'])): ?>
                        <div class="detail-item">
                            <span class="detail-label">تاریخ تولد:</span>
                            <span class="detail-value"><?= safeEcho($userData['birth_date']) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if(isset($userData['created_at'])): ?>
                        <div class="detail-item">
                            <span class="detail-label">تاریخ ثبت:</span>
                            <span class="detail-value"><?= persianDate($userData['created_at']) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- اطلاعات تماس -->
            <?php if(!empty($contactInfo)): ?>
            <div class="card-ghost detail-section" id="contact">
                <h5><i class="bi bi-telephone me-2"></i>اطلاعات تماس</h5>
                <div class="info-list">
                    <?php foreach($contactInfo as $label => $value): ?>
                        <?php if(!empty($value)): ?>
                            <div class="detail-item">
                                <span class="detail-label"><?= safeEcho($label) ?>:</span>
                                <span class="detail-value"><?= safeEcho($value) ?></span>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- اطلاعات عضویت سایت -->
            <?php if($siteMemberInfo): ?>
            <div class="card-ghost detail-section" id="membership">
                <h5><i class="bi bi-shield-check me-2"></i>اطلاعات عضویت در سایت</h5>
                <div class="info-list">
                    <div class="detail-item">
                        <span class="detail-label">وضعیت عضویت:</span>
                        <span class="detail-value"><?= getStatusBadge($siteMemberInfo['member_status'] ?? '') ?></span>
                    </div>

                    <?php if(!empty($siteMemberInfo['national_id'])): ?>
                        <div class="detail-item">
                            <span class="detail-label">کدملی:</span>
                            <span class="detail-value"><?= safeEcho($siteMemberInfo['national_id']) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if(!empty($siteMemberInfo['birth_certificate_number'])): ?>
                        <div class="detail-item">
                            <span class="detail-label">شماره شناسنامه:</span>
                            <span class="detail-value"><?= safeEcho($siteMemberInfo['birth_certificate_number']) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if(!empty($siteMemberInfo['registration_date'])): ?>
                        <div class="detail-item">
                            <span class="detail-label">تاریخ ثبت‌نام:</span>
                            <span class="detail-value"><?= persianDate($siteMemberInfo['registration_date']) ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if(!empty($siteMemberInfo['ip_address'])): ?>
                        <div class="detail-item">
                            <span class="detail-label">آی‌پی ثبت‌نام:</span>
                            <span class="detail-value"><?= safeEcho($siteMemberInfo['ip_address']) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- ستون سمت چپ - وضعیت و سیستم -->
        <div class="col-lg-4">
            <!-- وضعیت سیستم -->
            <div class="card-ghost detail-section">
                <h5><i class="bi bi-gear me-2"></i>وضعیت سیستم</h5>
                <div class="info-list">
                    <div class="detail-item">
                        <span class="detail-label">وضعیت حساب:</span>
                        <span class="detail-value">
                            <?php if(isset($userData['is_active'])): ?>
                                <span class="badge <?= $userData['is_active'] ? 'bg-success' : 'bg-danger' ?>">
                                    <?= $userData['is_active'] ? 'فعال' : 'غیرفعال' ?>
                                </span>
                            <?php else: ?>
                                <span class="badge bg-secondary">نامشخص</span>
                            <?php endif; ?>
                        </span>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">نوع عضویت:</span>
                        <span class="detail-value"><?= getUserStatusBadge($userData['main_member'] ?? '') ?></span>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">پروژه اصلی:</span>
                        <span class="detail-value">
                            <?= safeEcho($userData['project_name'] ?? 'نامشخص') ?>
                            <?php if(!empty($userData['project_id'])): ?>
                                <span class="badge bg-info ms-2">#<?= $userData['project_id'] ?></span>
                            <?php endif; ?>
                        </span>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">تعداد پروژه‌ها:</span>
                        <span class="detail-value"><?= count($userProjects) ?> پروژه</span>
                    </div>
                </div>
            </div>

            <!-- آخرین فعالیت‌ها -->
            <?php if(!empty($userActivities)): ?>
            <div class="card-ghost detail-section" id="activities">
                <h5><i class="bi bi-activity me-2"></i>آخرین فعالیت‌ها</h5>
                <div class="activities-list">
                    <?php foreach($userActivities as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-type">
                                <i class="bi bi-<?= $activity['type'] == 'ticket' ? 'ticket-detailed' : ($activity['type'] == 'cheque' ? 'credit-card' : 'file-earmark') ?> me-1"></i>
                                <?= safeEcho($activity['action']) ?>
                            </div>
                            <div class="activity-desc"><?= safeEcho($activity['description']) ?></div>
                            <div class="activity-time"><?= persianDate($activity['created_at']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- عملیات سریع -->
            <div class="card-ghost">
                <h6 class="mb-3">عملیات سریع</h6>
                <div class="d-grid gap-2">
                    <a href="dashboardAdmin.php?user_id=<?= $user_id ?>&tab=documents" class="btn btn-primary">
                        <i class="bi bi-files me-2"></i>مشاهده مدارک
                    </a>
                    <button class="btn btn-outline-primary" onclick="sendMessageToUser(<?= $user_id ?>)">
                        <i class="bi bi-chat-dots me-2"></i>ارسال پیام
                    </button>
                    <button class="btn btn-outline-warning" onclick="editUserProfile(<?= $user_id ?>)">
                        <i class="bi bi-pencil me-2"></i>ویرایش پروفایل
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- بخش‌های تکی -->
    <div class="row mt-4">
        <!-- آخرین تیکت‌ها -->
        <div class="col-md-6">
            <div class="card-ghost detail-section" id="tickets">
                <h5><i class="bi bi-ticket-detailed me-2"></i>آخرین تیکت‌ها</h5>
                <?php if(empty($userTickets)): ?>
                    <div class="text-center p-3 text-white-50">
                        هیچ تیکتی ثبت نشده است
                    </div>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach($userTickets as $ticket): ?>
                            <div class="list-group-item list-group-item-action bg-dark border-secondary mb-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1"><?= safeEcho($ticket['title']) ?></h6>
                                        <small class="text-white-50"><?= safeEcho($ticket['project_name'] ?? 'نامشخص') ?></small>
                                    </div>
                                    <div>
                                        <?php 
                                        $ticketStatus = $ticket['status'] ?? 'open';
                                        if($ticketStatus === 'closed'): ?>
                                            <span class="badge bg-secondary">بسته</span>
                                        <?php elseif($ticketStatus === 'answered'): ?>
                                            <span class="badge bg-success">پاسخ داده شد</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">باز</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <small class="text-white-50 d-block mt-2"><?= persianDate($ticket['created_at']) ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-center mt-3">
                        <a href="dashboardAdmin.php?user_id=<?= $user_id ?>&tab=tickets" class="btn btn-sm btn-outline-primary">
                            مشاهده همه تیکت‌ها
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- آخرین چک‌ها -->
        <div class="col-md-6">
            <div class="card-ghost detail-section" id="cheques">
                <h5><i class="bi bi-credit-card me-2"></i>آخرین چک‌ها</h5>
                <?php if(empty($userCheques)): ?>
                    <div class="text-center p-3 text-white-50">
                        هیچ چکی ثبت نشده است
                    </div>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach($userCheques as $cheque): ?>
                            <div class="list-group-item list-group-item-action bg-dark border-secondary mb-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">چک <?= safeEcho($cheque['cheque_number']) ?></h6>
                                        <small class="text-white-50"><?= number_format($cheque['amount'] ?? 0) ?> تومان</small>
                                    </div>
                                    <div>
                                        <?php 
                                        $chequeStatus = $cheque['status'] ?? 'pending';
                                        if($chequeStatus === 'paid'): ?>
                                            <span class="badge bg-success">پرداخت شده</span>
                                        <?php elseif($chequeStatus === 'pending'): ?>
                                            <span class="badge bg-warning text-dark">در انتظار</span>
                                        <?php else: ?>
                                            <span class="badge bg-info"><?= safeEcho($chequeStatus) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <small class="text-white-50 d-block mt-2">سررسید: <?= persianDate($cheque['cheque_date']) ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-center mt-3">
                        <a href="dashboardAdmin.php?user_id=<?= $user_id ?>&tab=cheques" class="btn btn-sm btn-outline-primary">
                            مشاهده همه چک‌ها
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- آخرین مدارک -->
        <div class="col-12 mt-4">
            <div class="card-ghost detail-section" id="documents">
                <h5><i class="bi bi-files me-2"></i>آخرین مدارک</h5>
                <?php if(empty($userDocuments)): ?>
                    <div class="text-center p-3 text-white-50">
                        هیچ مدرکی ثبت نشده است
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach($userDocuments as $doc): ?>
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="card bg-dark border-secondary">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div>
                                                <h6 class="card-title">
                                                    <?php 
                                                    $typeLabels = [
                                                        'national_card' => 'کارت ملی',
                                                        'birth_certificate' => 'شناسنامه',
                                                        'cheque' => 'چک',
                                                        'contract' => 'قرارداد',
                                                        'bank_statement' => 'گردش حساب',
                                                        'income_document' => 'مدرک درآمد',
                                                        'address_document' => 'مدرک آدرس',
                                                        'other' => 'سایر'
                                                    ];
                                                    $docType = $doc['document_type'] ?? 'other';
                                                    echo $typeLabels[$docType] ?? $docType;
                                                    ?>
                                                </h6>
                                                <small class="text-white-50"><?= formatFileSize($doc['file_size'] ?? 0) ?></small>
                                            </div>
                                            <div>
                                                <?= getStatusBadge($doc['verification_status'] ?? 'pending') ?>
                                            </div>
                                        </div>
                                        <p class="card-text text-white-50 small"><?= safeEcho($doc['description'] ?? 'بدون توضیح') ?></p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-white-50"><?= persianDate($doc['upload_date']) ?></small>
                                            <div class="btn-group btn-group-sm">
                                                <a href="<?= safeEcho($doc['file_path'] ?? '#') ?>" class="btn btn-outline-primary" download>
                                                    <i class="bi bi-download"></i>
                                                </a>
                                                <button class="btn btn-outline-info" onclick="showDocumentPreview('<?= safeEcho($doc['file_path'] ?? '') ?>', '<?= $typeLabels[$docType] ?? $docType ?>')">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-center mt-3">
                        <a href="dashboardAdmin.php?user_id=<?= $user_id ?>&tab=documents" class="btn btn-outline-primary">
                            <i class="bi bi-folder-plus me-2"></i>مشاهده همه مدارک
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- مودال نمایش تصویر -->
<div class="modal fade" id="imageModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark text-white">
            <div class="modal-header">
                <h5 class="modal-title" id="imageModalTitle">تصویر</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <img id="modalImage" src="" class="modal-image" alt="تصویر" onerror="this.onerror=null; this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iODAwIiBoZWlnaHQ9IjYwMCIgdmlld0JveD0iMCAwIDgwMCA2MDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjgwMCIgaGVpZ2h0PSI2MDAiIGZpbGw9InJnYmEoMjU1LDI1NSwyNTUsMC4xKSIvPjx0ZXh0IHg9IjQwMCIgeT0iMzAwIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMjQiIGZpbGw9InJnYmEoMjU1LDI1NSwyNTUsMC41KSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9IjAuMzVlbSI+SW1hZ2UgTm90IEZvdW5kPC90ZXh0Pjwvc3ZnPg=='">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">بستن</button>
                <a id="downloadImageLink" href="#" class="btn btn-primary" download>
                    <i class="bi bi-download me-1"></i> دانلود
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // تابع نمایش تصویر در مودال
    function showImageModal(imageSrc, title) {
        if (!imageSrc) return;
        document.getElementById('modalImage').src = imageSrc;
        document.getElementById('imageModalTitle').textContent = title;
        document.getElementById('downloadImageLink').href = imageSrc;
        new bootstrap.Modal(document.getElementById('imageModal')).show();
    }
    
    // تابع نمایش پیش‌نمایش مدرک
    function showDocumentPreview(filePath, title) {
        if (!filePath) return;
        
        // بررسی نوع فایل
        const extension = filePath.split('.').pop().toLowerCase();
        const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp'];
        
        if (imageExtensions.includes(extension)) {
            showImageModal(filePath, title);
        } else {
            // برای فایل‌های غیرتصویری، دانلود مستقیم
            window.open(filePath, '_blank');
        }
    }
    
    // تابع اسکرول به بخش خاص
    function scrollToSection(sectionId) {
        const element = document.getElementById(sectionId);
        if (element) {
            element.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }
    
    // تابع ارسال پیام به کاربر
    function sendMessageToUser(userId) {
        const message = prompt('پیام خود را برای کاربر وارد کنید:');
        if (message && message.trim()) {
            alert(`پیام برای کاربر ${userId} ارسال شد:\n${message}`);
            // اینجا می‌توانید کد AJAX برای ارسال پیام اضافه کنید
        }
    }
    
    // تابع ویرایش پروفایل کاربر
    function editUserProfile(userId) {
        if (confirm('آیا مایل به ویرایش پروفایل این کاربر هستید؟')) {
            window.open(`edit_user.php?user_id=${userId}`, '_blank');
        }
    }
    
    // اسکرول به بالا
    function scrollToTop() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    
    // دکمه اسکرول به بالا
    document.addEventListener('DOMContentLoaded', function() {
        // ایجاد دکمه اسکرول به بالا
        const scrollButton = document.createElement('button');
        scrollButton.innerHTML = '<i class="bi bi-chevron-up"></i>';
        scrollButton.className = 'btn btn-primary rounded-circle position-fixed';
        scrollButton.style.bottom = '20px';
        scrollButton.style.left = '20px';
        scrollButton.style.width = '50px';
        scrollButton.style.height = '50px';
        scrollButton.style.zIndex = '1000';
        scrollButton.onclick = scrollToTop;
        document.body.appendChild(scrollButton);
        
        // نمایش/پنهان کردن دکمه
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                scrollButton.style.display = 'block';
            } else {
                scrollButton.style.display = 'none';
            }
        });
    });
</script>
</body>
</html>