<?php
// editMember.php - نسخه نهایی
session_start();

// فعال کردن نمایش خطاها
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// تابع تاریخ شمسی
if (!function_exists('jdate')) {
    function jdate($format, $timestamp = '', $timezone = 'Asia/Tehran') {
        if ($timestamp === '') {
            $timestamp = time();
        }
        date_default_timezone_set($timezone);
        $date = date($format, $timestamp);
        
        // تبدیل اعداد انگلیسی به فارسی
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        return str_replace($english, $persian, $date);
    }
}

// بررسی ادمین بودن کاربر
if (empty($_SESSION['admin_id']) || empty($_SESSION['admin_username'])) {
    header('Location: loginAdmin.php');
    exit;
}

// تولید CSRF Token اگر وجود ندارد
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// بررسی وجود ID عضو
if (empty($_GET['id'])) {
    header('Location: dashboardAdmin.php?tab=members');
    exit;
}

$member_id = (int)$_GET['id'];

// اتصال مستقیم به دیتابیس (بدون نیاز به فایل جداگانه)
$host = 'localhost';
$username = 'maskanhb_maskansazan';
$password = 'NJ)B*$@y3R9P0Oyg';
$database = 'maskanhb_maskansazan';

$mysqli = new mysqli($host, $username, $password, $database);

if ($mysqli->connect_error) {
    die('<div class="alert alert-danger m-3">خطا در اتصال به دیتابیس: ' . $mysqli->connect_error . '</div>');
}

$mysqli->set_charset("utf8mb4");

// دریافت اطلاعات عضو
$sql = "SELECT * FROM members WHERE id = ?";
$stmt = $mysqli->prepare($sql);
if (!$stmt) {
    die('<div class="alert alert-danger m-3">خطا در آماده‌سازی کوئری: ' . $mysqli->error . '</div>');
}

$stmt->bind_param("i", $member_id);
if (!$stmt->execute()) {
    die('<div class="alert alert-danger m-3">خطا در اجرای کوئری: ' . $stmt->error . '</div>');
}

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('<div class="alert alert-warning m-3">عضو با شناسه ' . $member_id . ' یافت نشد.</div>');
}

$member = $result->fetch_assoc();
$stmt->close();

// دریافت اسناد عضو
$documents = [];
$docs_sql = "SELECT * FROM member_documents WHERE member_id = ?";
$docs_stmt = $mysqli->prepare($docs_sql);
if ($docs_stmt) {
    $docs_stmt->bind_param("i", $member_id);
    if ($docs_stmt->execute()) {
        $docs_result = $docs_stmt->get_result();
        while ($doc = $docs_result->fetch_assoc()) {
            $documents[] = $doc;
        }
    }
    $docs_stmt->close();
}

// پردازش فرم ویرایش
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // بررسی CSRF Token
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = 'توکن امنیتی نامعتبر است';
        $message_type = 'error';
    } else {
        // دریافت داده‌های فرم
        $full_name = trim($_POST['full_name'] ?? '');
        $national_id = trim($_POST['national_id'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $status = trim($_POST['status'] ?? 'pending');
        
        // اعتبارسنجی
        $errors = [];
        
        if (empty($full_name)) {
            $errors[] = 'نام کامل الزامی است';
        }
        
        if (empty($national_id) || !preg_match('/^\d{10}$/', $national_id)) {
            $errors[] = 'کد ملی باید ۱۰ رقم باشد';
        }
        
        if (empty($phone) || strlen($phone) < 10) {
            $errors[] = 'شماره تلفن معتبر نیست';
        }
    
        
        // اگر خطایی نبود، آپدیت کن
        if (empty($errors)) {
            $update_sql = "UPDATE members SET 
                          first_name = ?, 
                          last_name = ?, 
                          national_id = ?, 
                          phone = ?, 
                          status = ?, 
                          updated_at = NOW() 
                          WHERE id = ?";
            
            $update_stmt = $mysqli->prepare($update_sql);
            if ($update_stmt) {
                $update_stmt->bind_param(
                    "ssssssi", 
                    $full_name, 
                    $national_code, 
                    $phone, 
                    $status, 
                    $member_id
                );
                
                if ($update_stmt->execute()) {
                    $message = 'اطلاعات عضو با موفقیت به‌روزرسانی شد';
                    $message_type = 'success';
                    
                    // بروزرسانی اطلاعات جلسه
                    $member['full_name'] = $full_name;
                    $member['national_id'] = $national_code;
                    $member['phone'] = $phone;
                    $member['status'] = $status;
                } else {
                    $message = 'خطا در به‌روزرسانی اطلاعات: ' . $update_stmt->error;
                    $message_type = 'error';
                }
                
                $update_stmt->close();
            } else {
                $message = 'خطا در آماده‌سازی کوئری آپدیت: ' . $mysqli->error;
                $message_type = 'error';
            }
        } else {
            $message = implode('<br>', $errors);
            $message_type = 'error';
        }
    }
}

// بستن اتصال دیتابیس
$mysqli->close();
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ویرایش عضو -<?php echo htmlspecialchars($member['first_name'].' '.$member['last_name']); ?>
</title>
    
    <!-- بوت‌استرپ 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- بوت‌استرپ آیکون -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- فونت وزیر -->
    <link href="https://v1.fontapi.ir/css/Vazir" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2c3e50;
            --success-color: #27ae60;
            --warning-color: #f39c12;
            --danger-color: #e74c3c;
        }
        
        body {
            font-family: 'Vazir', sans-serif;
            background-color: #f8f9fa;
            padding-top: 20px;
        }
        
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            border: none;
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--secondary-color) 0%, #1a252f 100%);
            color: white;
            border-radius: 10px 10px 0 0 !important;
            font-weight: bold;
            padding: 15px 20px;
            border: none;
        }
        
        .btn-custom {
            background-color: var(--primary-color);
            color: white;
            border-radius: 6px;
            padding: 10px 25px;
            border: none;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .btn-custom:hover {
            background-color: #2980b9;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #dee2e6;
            padding: 12px 15px;
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.25);
            transform: translateY(-1px);
        }
        
        .status-badge {
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-pending { 
            background-color: #fff3cd; 
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .status-approved { 
            background-color: #d4edda; 
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .status-rejected { 
            background-color: #f8d7da; 
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .document-item {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 18px;
            margin-bottom: 15px;
            background: white;
            transition: all 0.3s;
        }
        
        .document-item:hover {
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
            transform: translateY(-3px);
            border-color: var(--primary-color);
        }
        
        .alert-custom {
            border-radius: 10px;
            border: none;
            margin-bottom: 25px;
            padding: 15px 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .profile-image {
            width: 160px;
            height: 160px;
            border-radius: 50%;
            object-fit: cover;
            border: 6px solid white;
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
            transition: all 0.3s;
        }
        
        .profile-image:hover {
            transform: scale(1.05);
        }
        
        .image-container {
            position: relative;
            display: inline-block;
            margin-bottom: 20px;
        }
        
        .image-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.6);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .image-container:hover .image-overlay {
            opacity: 1;
        }
        
        .breadcrumb {
            background-color: white;
            border-radius: 8px;
            padding: 15px 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        
        .breadcrumb-item.active {
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .info-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.2);
        }
        
        .back-link {
            text-decoration: none;
            color: var(--secondary-color);
            font-weight: 500;
            transition: color 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .back-link:hover {
            color: var(--primary-color);
            transform: translateX(-3px);
        }
        
        .modal-content {
            border-radius: 12px;
            border: none;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .btn-download {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            border: none;
            padding: 10px 25px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-download:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(46, 204, 113, 0.3);
            color: white;
        }
        
        .quick-actions .btn {
            padding: 12px;
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .quick-actions .btn:hover {
            transform: translateY(-2px);
        }
        
        .stats-badge {
            font-size: 18px;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 600;
        }
        
        /* انیمیشن‌ها */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-out;
        }
    </style>
</head>
<body>
    <div class="container fade-in">
        <!-- ناوبری -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="dashboardAdmin.php" class="back-link">
                        <i class="bi bi-house-door"></i> داشبورد
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a href="dashboardAdmin.php?tab=members" class="back-link">
                        <i class="bi bi-people"></i> مدیریت اعضا
                    </a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    <i class="bi bi-pencil-square"></i> ویرایش عضو: <?php echo htmlspecialchars($member['first_name'].' '.$member['last_name']); ?>
                </li>
            </ol>
        </nav>
        
        <!-- پیام‌ها -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'danger'; ?> alert-custom alert-dismissible fade show" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bi <?php echo $message_type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill'; ?> me-2"></i>
                    <span><?php echo $message; ?></span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <!-- ستون چپ: فرم ویرایش -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-pencil-square me-2"></i>
                            <span>ویرایش اطلاعات عضو</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" id="editMemberForm">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            
                            <div class="row">
                                <!-- نام کامل -->
                                <div class="col-md-6 mb-4">
                                    <label for="full_name" class="form-label fw-bold">
                                        <i class="bi bi-person me-1"></i> نام کامل
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="full_name" 
                                           name="full_name" 
                                           value="<?php echo htmlspecialchars($member['first_name'].' '.$member['last_name']); ?>" 
                                           required
                                           placeholder="نام و نام خانوادگی را وارد کنید">
                                    <div class="form-text text-muted mt-1">نام کامل عضو را به فارسی وارد کنید</div>
                                </div>
                                
                                <!-- کد ملی -->
                                <div class="col-md-6 mb-4">
                                    <label for="national_id" class="form-label fw-bold">
                                        <i class="bi bi-card-text me-1"></i> کد ملی
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="national_id" 
                                           name="national_id" 
                                           value="<?php echo htmlspecialchars($member['national_id']); ?>" 
                                           required 
                                           maxlength="10"
                                           pattern="\d{10}"
                                           placeholder="کد ملی ۱۰ رقمی">
                                    <div class="form-text text-muted mt-1">۱۰ رقم عددی - بدون خط تیره</div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <!-- تلفن -->
                                <div class="col-md-6 mb-4">
                                    <label for="phone" class="form-label fw-bold">
                                        <i class="bi bi-telephone me-1"></i> شماره تلفن
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="tel" 
                                           class="form-control" 
                                           id="phone" 
                                           name="phone" 
                                           value="<?php echo htmlspecialchars($member['phone']); ?>" 
                                           required
                                           placeholder="مثال: ۰۹۱۲۳۴۵۶۷۸۹">
                                    <div class="form-text text-muted mt-1">شماره همراه یا ثابت با پیش‌شماره</div>
                                </div>
                            
                            </div>
                            
                            <!-- وضعیت -->
                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <label for="status" class="form-label fw-bold">
                                        <i class="bi bi-info-circle me-1"></i> وضعیت عضویت
                                    </label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="pending" <?php echo $member['status'] === 'pending' ? 'selected' : ''; ?>>
                                            در انتظار تأیید
                                        </option>
                                        <option value="approved" <?php echo $member['status'] === 'approved' ? 'selected' : ''; ?>>
                                            تأیید شده
                                        </option>
                                        <option value="rejected" <?php echo $member['status'] === 'rejected' ? 'selected' : ''; ?>>
                                            رد شده
                                        </option>
                                    </select>
                                    <div class="form-text text-muted mt-1">وضعیت فعلی عضو در سیستم</div>
                                </div>
                                
                                <!-- تاریخ عضویت -->
                                <div class="col-md-6 mb-4">
                                    <label class="form-label fw-bold">
                                        <i class="bi bi-calendar-check me-1"></i> تاریخ عضویت
                                    </label>
                                    <div class="input-group">
                                        <input type="text" 
                                               class="form-control" 
                                               value="<?php echo jdate('Y/m/d ساعت H:i', strtotime($member['created_at'])); ?>" 
                                               disabled
                                               style="background-color: #f8f9fa;">
                                        <span class="input-group-text">
                                            <i class="bi bi-clock-history"></i>
                                        </span>
                                    </div>
                                    <div class="form-text text-muted mt-1">تاریخ ثبت‌نام عضو در سیستم</div>
                                </div>
                            </div>
                            
                            <!-- دکمه‌های اقدام -->
                            <div class="d-flex justify-content-between align-items-center mt-5 pt-3 border-top">
                                <a href="dashboardAdmin.php?tab=members" class="btn btn-outline-secondary px-4">
                                    <i class="bi bi-arrow-right me-1"></i> بازگشت به لیست
                                </a>
                                
                                <div class="d-flex gap-3">
                                    <button type="submit" class="btn btn-custom px-4">
                                        <i class="bi bi-check-circle me-1"></i> ذخیره تغییرات
                                    </button>
                                    
                                    <a href="deleteMember.php?id=<?php echo $member_id; ?>" 
                                       class="btn btn-outline-danger px-4"
                                       onclick="return confirm('⚠️ آیا از حذف عضو<?php echo htmlspecialchars($member['first_name'].' '.$member['last_name']); ?>» اطمینان دارید؟ این عمل غیرقابل بازگشت است.')">
                                        <i class="bi bi-trash me-1"></i> حذف عضو
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- بخش اسناد -->
                <div class="card mt-4">
                    <div class="card-header">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-files me-2"></i>
                            <span>اسناد ارسالی عضو</span>
                            <span class="badge bg-primary ms-2"><?php echo count($documents); ?></span>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($documents)): ?>
                            <div class="alert alert-info border-0">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-info-circle-fill me-3 fs-4"></i>
                                    <div>
                                        <h6 class="alert-heading mb-1">هیچ سندی یافت نشد</h6>
                                        <p class="mb-0">این عضو تاکنون هیچ سندی ارسال نکرده است.</p>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="row g-4">
                                <?php foreach ($documents as $index => $doc): ?>
                                    <div class="col-md-6">
                                        <div class="document-item">
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1 fw-bold">
                                                        <i class="bi bi-file-earmark-text text-primary me-2"></i>
                                                        <?php echo htmlspecialchars($doc['document_type']); ?>
                                                    </h6>
                                                    <small class="text-muted d-block">
                                                        <i class="bi bi-calendar me-1"></i>
                                                        <?php echo jdate('Y/m/d', strtotime($doc['created_at'])); ?>
                                                    </small>
                                                </div>
                                                <span class="status-badge status-<?php echo $doc['status']; ?> ms-2">
                                                    <?php 
                                                    $status_text = [
                                                        'pending' => 'در انتظار',
                                                        'approved' => 'تأیید شده',
                                                        'rejected' => 'رد شده'
                                                    ];
                                                    echo $status_text[$doc['status']] ?? $doc['status'];
                                                    ?>
                                                </span>
                                            </div>
                                            
                                            <div class="d-flex gap-2">
                                                <?php if (!empty($doc['file_path'])): ?>
                                                    <button type="button" 
                                                            class="btn btn-outline-primary btn-sm px-3"
                                                            onclick="showImageModal('<?php echo htmlspecialchars($doc['file_path']); ?>', '<?php echo htmlspecialchars($doc['document_type']); ?>')">
                                                        <i class="bi bi-eye me-1"></i> مشاهده
                                                    </button>
                                                    
                                                    <a href="<?php echo htmlspecialchars($doc['file_path']); ?>" 
                                                       class="btn btn-outline-success btn-sm px-3"
                                                       download="سند_<?php echo htmlspecialchars($doc['document_type']) . '_' . $member_id; ?>">
                                                        <i class="bi bi-download me-1"></i> دانلود
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <button type="button" 
                                                        class="btn btn-outline-secondary btn-sm px-3"
                                                        onclick="showDocumentInfo(<?php echo $index; ?>)">
                                                    <i class="bi bi-info-circle me-1"></i> اطلاعات
                                                </button>
                                            </div>
                                            
                                            <!-- اطلاعات مخفی سند -->
                                            <div id="docInfo-<?php echo $index; ?>" class="mt-3 p-3 bg-light rounded d-none">
                                                <small class="text-muted">
                                                    <strong>آدرس فایل:</strong> <?php echo htmlspecialchars($doc['file_path'] ?? 'ندارد'); ?><br>
                                                    <strong>تاریخ بارگذاری:</strong> <?php echo jdate('Y/m/d H:i', strtotime($doc['created_at'])); ?><br>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- ستون راست: اطلاعات کاربر -->
            <div class="col-lg-4">
                <!-- کارت پروفایل -->
                <div class="card mb-4">
                    <div class="card-header">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-person-badge me-2"></i>
                            <span>پروفایل عضو</span>
                        </div>
                    </div>
                    <div class="card-body text-center">
                        <!-- تصویر پروفایل -->
                        <div class="image-container mb-4">
                            <?php 
                            $profile_image = !empty($member['profile_image']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $member['profile_image']) 
                                ? $member['profile_image'] 
                                : 'https://via.placeholder.com/160x160/3498db/ffffff?text=' . urlencode(mb_substr($member['first_name'].' '.$member['last_name'], 0, 1));
                            ?>
                            <img src="<?php echo htmlspecialchars($profile_image); ?>" 
                                 alt="تصویر پروفایل <?php echo htmlspecialchars($member['first_name'].' '.$member['last_name']); ?>
" 
                                 class="profile-image"
                                 onerror="this.src='https://via.placeholder.com/160x160/cccccc/ffffff?text=?'">
                            <div class="image-overlay" onclick="showImageModal('<?php echo htmlspecialchars($profile_image); ?>', 'تصویر پروف<?php echo htmlspecialchars($member['first_name'].' '.$member['last_name']); ?> ?>')">
                                <i class="bi bi-zoom-in text-white fs-3"></i>
                            </div>
                        </div>
                        
                        <h4 class="mb-2 fw-bold"><?php echo htmlspecialchars($member['first_name'].' '.$member['last_name']); ?></h4>
                        <p class="text-muted mb-3">
                            <i class="bi bi-person-vcard me-1"></i>
                            کد عضو: <span class="fw-bold">MEM-<?php echo str_pad($member['id'], 5, '0', STR_PAD_LEFT); ?></span>
                        </p>
                        
                        <!-- وضعیت -->
                        <div class="mb-4">
                            <span class="status-badge status-<?php echo $member['status']; ?> fs-6">
                                <i class="bi <?php 
                                    echo $member['status'] === 'approved' ? 'bi-check-circle-fill' : 
                                           ($member['status'] === 'rejected' ? 'bi-x-circle-fill' : 'bi-clock-fill'); 
                                ?> me-1"></i>
                                <?php 
                                $status_text = [
                                    'pending' => 'در انتظار تأیید',
                                    'approved' => 'عضو تأیید شده',
                                    'rejected' => 'رد شده'
                                ];
                                echo $status_text[$member['status']] ?? $member['status'];
                                ?>
                            </span>
                        </div>
                        
                        <!-- اطلاعات تماس -->
                        <div class="text-start bg-light p-4 rounded-3 mb-4">
                            <h6 class="fw-bold mb-3 text-secondary">
                                <i class="bi bi-info-circle me-2"></i> اطلاعات تماس
                            </h6>
                            
                            <div class="mb-3">
                                <div class="d-flex align-items-center mb-1">
                                    <i class="bi bi-telephone text-primary me-2"></i>
                                    <small class="text-muted">شماره تلفن:</small>
                                </div>
                                <div class="fw-bold ps-4"><?php echo htmlspecialchars($member['phone']); ?></div>
                            </div>
                            
                            
                            <div class="mb-3">
                                <div class="d-flex align-items-center mb-1">
                                    <i class="bi bi-calendar text-primary me-2"></i>
                                    <small class="text-muted">تاریخ عضویت:</small>
                                </div>
                                <div class="fw-bold ps-4"><?php echo jdate('Y/m/d', strtotime($member['created_at'])); ?></div>
                            </div>
                            
                            <?php if ($member['updated_at'] !== $member['created_at']): ?>
                                <div>
                                    <div class="d-flex align-items-center mb-1">
                                        <i class="bi bi-clock-history text-primary me-2"></i>
                                        <small class="text-muted">آخرین ویرایش:</small>
                                    </div>
                                    <div class="fw-bold ps-4"><?php echo jdate('Y/m/d H:i', strtotime($member['updated_at'])); ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- کارت آمار -->
                <div class="card mb-4">
                    <div class="card-header">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-bar-chart me-2"></i>
                            <span>آمار و اطلاعات</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php
                        $approved_docs = array_filter($documents, fn($doc) => $doc['status'] === 'approved');
                        $pending_docs = array_filter($documents, fn($doc) => $doc['status'] === 'pending');
                        $rejected_docs = array_filter($documents, fn($doc) => $doc['status'] === 'rejected');
                        ?>
                        
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-files text-primary me-2"></i>
                                <span>تعداد کل اسناد:</span>
                            </div>
                            <span class="badge bg-primary stats-badge"><?php echo count($documents); ?></span>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-check-circle text-success me-2"></i>
                                <span>اسناد تأیید شده:</span>
                            </div>
                            <span class="badge bg-success stats-badge"><?php echo count($approved_docs); ?></span>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-clock text-warning me-2"></i>
                                <span>اسناد در انتظار:</span>
                            </div>
                            <span class="badge bg-warning text-dark stats-badge"><?php echo count($pending_docs); ?></span>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <i class="bi bi-x-circle text-danger me-2"></i>
                                <span>اسناد رد شده:</span>
                            </div>
                            <span class="badge bg-danger stats-badge"><?php echo count($rejected_docs); ?></span>
                        </div>
                    </div>
                </div>
            
            </div>
        </div>
    </div>
    
    <!-- مودال نمایش تصویر -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageModalTitle">تصویر</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <div class="d-flex justify-content-center align-items-center" style="min-height: 300px;">
                        <div class="spinner-border text-primary" id="imageLoading" style="display: none;"></div>
                        <img id="modalImage" src="" class="img-fluid rounded" alt="" style="max-height: 70vh; display: none;">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-lg me-1"></i> بستن
                    </button>
                    <a href="#" id="downloadImage" class="btn btn-download">
                        <i class="bi bi-download me-1"></i> دانلود تصویر
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- اسکریپت‌ها -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // تابع نمایش مودال تصویر
        function showImageModal(imageSrc, title) {
            const modalElement = document.getElementById('imageModal');
            const modalImage = document.getElementById('modalImage');
            const modalTitle = document.getElementById('imageModalTitle');
            const downloadBtn = document.getElementById('downloadImage');
            const loadingSpinner = document.getElementById('imageLoading');
            
            if (modalElement && modalImage && modalTitle && downloadBtn) {
                // نمایش اسپینر
                if (loadingSpinner) loadingSpinner.style.display = 'block';
                modalImage.style.display = 'none';
                
                // تنظیم عنوان
                modalTitle.textContent = title;
                
                // تنظیم تصویر
                modalImage.onload = function() {
                    if (loadingSpinner) loadingSpinner.style.display = 'none';
                    modalImage.style.display = 'block';
                };
                
                modalImage.onerror = function() {
                    if (loadingSpinner) loadingSpinner.style.display = 'none';
                    modalImage.src = 'https://via.placeholder.com/600x400/cccccc/ffffff?text=خطا+در+بارگذاری+تصویر';
                    modalImage.style.display = 'block';
                    modalImage.alt = 'خطا در بارگذاری تصویر';
                };
                
                modalImage.src = imageSrc;
                modalImage.alt = title;
                
                // تنظیم دکمه دانلود
                downloadBtn.href = imageSrc;
                const fileName = title.replace(/[^آ-یا-یa-zA-Z0-9]/g, '_') + '.jpg';
                downloadBtn.download = fileName;
                downloadBtn.setAttribute('download', fileName);
                
                // نمایش مودال
                const modal = new bootstrap.Modal(modalElement);
                modal.show();
            }
        }
        
        // تابع تغییر وضعیت عضویت
        function changeStatus(newStatus) {
            const statusText = {
                'approved': 'تأیید',
                'rejected': 'رد'
            };
            
           if (confirm(`آیا از ${statusText[newStatus]} عضویت «${'<?php echo htmlspecialchars($member['first_name'].' '.$member['last_name']); ?>'}» اطمینان دارید؟`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';
                
                const statusInput = document.createElement('input');
                statusInput.type = 'hidden';
                statusInput.name = 'status';
                statusInput.value = newStatus;
                
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = 'csrf_token';
                csrfInput.value = '<?php echo $_SESSION['csrf_token']; ?>';
                
                form.appendChild(statusInput);
                form.appendChild(csrfInput);
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // نمایش اطلاعات سند
        function showDocumentInfo(docIndex) {
            const infoDiv = document.getElementById('docInfo-' + docIndex);
            if (infoDiv) {
                infoDiv.classList.toggle('d-none');
            }
        }
        
        // اعتبارسنجی فرم
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('editMemberForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const nationalCode = document.getElementById('national_id');
                    if (nationalCode && !/^\d{10}$/.test(nationalCode.value)) {
                        e.preventDefault();
                        alert('کد ملی باید دقیقاً ۱۰ رقم باشد');
                        nationalCode.focus();
                        return false;
                    }
                    
                    const phone = document.getElementById('phone');
                    if (phone && phone.value.replace(/\D/g, '').length < 10) {
                        e.preventDefault();
                        alert('شماره تلفن معتبر نیست');
                        phone.focus();
                        return false;
                    }
                    
                    
                    return true;
                });
            }
            
            // فرمت خودکار شماره تلفن
            const phoneInput = document.getElementById('phone');
            if (phoneInput) {
                phoneInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length > 0) {
                        if (value.length <= 4) {
                            value = value;
                        } else if (value.length <= 7) {
                            value = value.slice(0, 4) + '-' + value.slice(4);
                        } else if (value.length <= 11) {
                            value = value.slice(0, 4) + '-' + value.slice(4, 7) + '-' + value.slice(7);
                        } else {
                            value = value.slice(0, 11);
                        }
                    }
                    e.target.value = value;
                });
            }
            
            // محدود کردن کد ملی به ۱۰ رقم
            const nationalCodeInput = document.getElementById('national_id');
            if (nationalCodeInput) {
                nationalCodeInput.addEventListener('input', function(e) {
                    e.target.value = e.target.value.replace(/\D/g, '').slice(0, 10);
                });
            }
        });
        
        // تابع AJAX برای تغییر وضعیت
        function updateMemberStatus(memberId, status) {
            if (!confirm('آیا از تغییر وضعیت اطمینان دارید؟')) return;
            
            const formData = new FormData();
            formData.append('member_id', memberId);
            formData.append('status', status);
            formData.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');
            
            fetch('update_member_status.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('خطا: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('خطا در ارتباط با سرور');
            });
        }
    </script>
</body>
</html>