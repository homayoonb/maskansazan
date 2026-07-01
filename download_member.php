<?php
// download_member.php - نسخه نهایی
session_start();

// ==================== تنظیمات اولیه ====================
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Asia/Tehran');

// ==================== تنظیمات دیتابیس ====================
$servername = "localhost";
$username = "maskanhb_maskansazan";
$password = "NJ)B*$@y3R9P0Oyg";
$dbname = "maskanhb_maskansazan";

// ==================== اتصال به دیتابیس ====================
$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die(showErrorPage(
        "❌ خطا در اتصال به پایگاه داده",
        "خطا: " . $conn->connect_error . "\nلطفا تنظیمات دیتابیس را بررسی کنید."
    ));
}

// ==================== دریافت پارامترها ====================
$member_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$download_type = isset($_GET['download']) ? $_GET['download'] : '';
$search_mode = isset($_GET['search']) ? $_GET['search'] : '';

// ==================== بررسی شناسه عضو ====================
if (!$member_id && $search_mode != 'admin') {
    die(showErrorPage(
        "❌ شناسه عضو نامعتبر",
        "شناسه عضو وارد شده معتبر نیست. لطفا از طریق لینک صحیح وارد شوید."
    ));
}

// ==================== حالت جستجوی مدیر ====================
if ($search_mode == 'admin' && isset($_GET['query'])) {
    searchMembers($conn, $_GET['query']);
    exit;
}

// ==================== دانلود CSV ====================
if ($download_type == 'csv' && $member_id > 0) {
    generateCSV($conn, $member_id);
    exit;
}

// ==================== دانلود Excel ====================
if ($download_type == 'excel' && $member_id > 0) {
    generateExcel($conn, $member_id);
    exit;
}

// ==================== نمایش همه اعضا (مدیر) ====================
if ($search_mode == 'admin' && !isset($_GET['query'])) {
    displayAllMembers($conn);
    exit;
}

// ==================== نمایش اطلاعات عضو ====================
if ($member_id > 0) {
    displayMemberInfo($conn, $member_id);
    exit;
}

// ==================== صفحه پیش‌فرض ====================
showLandingPage();

// ==================== توابع اصلی ====================

/**
 * تولید فایل CSV
 */
function generateCSV($conn, $member_id) {
    try {
        // بررسی وجود جدول
        if (!tableExists($conn, 'members')) {
            throw new Exception("جدول members در دیتابیس وجود ندارد.");
        }

        // دریافت اطلاعات عضو
        $member = getMemberData($conn, $member_id);

        if (!$member) {
            throw new Exception("عضو با کد پیگیری #" . $member_id . " یافت نشد.");
        }

        // آماده سازی داده‌ها
        $data = prepareCSVData($member);

        // نام فایل
        $filename = sprintf(
            'عضو_%s_%s_%s.csv',
            $member['first_name'] ?? 'نامشخص',
            $member['last_name'] ?? 'نامشخص',
            date('Y-m-d_H-i')
        );

        // هدرهای HTTP
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        header('Pragma: public');

        // خروجی CSV
        $output = fopen('php://output', 'w');

        // BOM برای نمایش صحیح فارسی
        fwrite($output, "\xEF\xBB\xBF");

        // نوشتن داده‌ها
        foreach ($data as $row) {
            fputcsv($output, $row, ',', '"');
        }

        fclose($output);

        // لاگ دانلود
        logDownload($member_id, 'csv');

        exit;

    } catch (Exception $e) {
        die(showErrorPage("خطا در تولید CSV", $e->getMessage()));
    }
}

/**
 * تولید فایل Excel
 */
function generateExcel($conn, $member_id) {
    try {
        // بررسی وجود جدول
        if (!tableExists($conn, 'members')) {
            throw new Exception("جدول members در دیتابیس وجود ندارد.");
        }

        // دریافت اطلاعات عضو
        $member = getMemberData($conn, $member_id);

        if (!$member) {
            throw new Exception("عضو با کد پیگیری #" . $member_id . " یافت نشد.");
        }

        // هدرهای HTTP برای Excel
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="عضو_' .
            $member['first_name'] . '_' . $member['last_name'] . '_' .
            date('Y-m-d') . '.xls"');
        header('Cache-Control: max-age=0');
        header('Pragma: public');

        // محتوای HTML برای Excel
        echo '<html dir="rtl"><head><meta charset="UTF-8"></head><body>';
        echo '<table border="1" cellpadding="5" cellspacing="0" width="100%">';

        echo '<tr><th colspan="2" style="background:#4CAF50;color:white;font-size:18px;padding:15px;">اطلاعات عضو</th></tr>';

        $info = prepareExcelData($member);
        foreach ($info as $key => $value) {
            echo '<tr>';
            echo '<td width="30%" style="background:#f2f2f2;font-weight:bold;padding:10px;">' . $key . '</td>';
            echo '<td width="70%" style="padding:10px;">' . $value . '</td>';
            echo '</tr>';
        }

        echo '</table></body></html>';

        // لاگ دانلود
        logDownload($member_id, 'excel');

        exit;

    } catch (Exception $e) {
        die(showErrorPage("خطا در تولید Excel", $e->getMessage()));
    }
}

/**
 * نمایش اطلاعات عضو
 */
function displayMemberInfo($conn, $member_id) {
    try {
        // بررسی وجود جدول
        if (!tableExists($conn, 'members')) {
            throw new Exception("جدول members در دیتابیس وجود ندارد.");
        }

        // دریافت اطلاعات عضو
        $member = getMemberData($conn, $member_id);

        if (!$member) {
            throw new Exception("عضو با کد پیگیری #" . $member_id . " یافت نشد.");
        }

        // نمایش صفحه
        showMemberPage($member);

    } catch (Exception $e) {
        die(showErrorPage("خطا در نمایش اطلاعات", $e->getMessage()));
    }
}

/**
 * نمایش همه اعضا (برای مدیر)
 */
function displayAllMembers($conn) {
    if (!tableExists($conn, 'members')) {
        die(showErrorPage("جدول یافت نشد", "جدول members در دیتابیس وجود ندارد."));
    }

    $sql = "SELECT id, first_name, last_name, national_id, mobile, registration_date, status 
            FROM members ORDER BY id DESC LIMIT 100";

    $result = $conn->query($sql);

    showAdminPage($result);
}

/**
 * جستجوی اعضا
 */
function searchMembers($conn, $query) {
    if (!tableExists($conn, 'members')) {
        die(showErrorPage("جدول یافت نشد", "جدول members در دیتابیس وجود ندارد."));
    }

    $search = $conn->real_escape_string($query);

    $sql = "SELECT id, first_name, last_name, national_id, mobile, registration_date, status 
            FROM members 
            WHERE first_name LIKE '%$search%' 
               OR last_name LIKE '%$search%' 
               OR national_id LIKE '%$search%'
               OR mobile LIKE '%$search%'
            ORDER BY id DESC LIMIT 50";

    $result = $conn->query($sql);

    showAdminPage($result, $query);
}

// ==================== توابع کمکی ====================

/**
 * بررسی وجود جدول
 */
function tableExists($conn, $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    return $result->num_rows > 0;
}

/**
 * دریافت اطلاعات عضو
 */
function getMemberData($conn, $member_id) {
    // لیست ستون‌های ممکن
    $columns = [
        'id', 'first_name', 'last_name', 'national_id', 'birth_certificate_number',
        'father_name', 'birth_date', 'birth_date_fa', 'mobile', 'phone', 'address',
        'national_card_image', 'birth_certificate_image',
        'check1_image', 'check2_image', 'check3_image',
        'registration_date', 'ip_address', 'status', 'created_at'
    ];

    // ساخت کوئری داینامیک
    $sql = "SELECT " . implode(', ', $columns) . " FROM members WHERE id = ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("خطا در آماده‌سازی کوئری: " . $conn->error);
    }

    $stmt->bind_param("i", $member_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        return null;
    }

    return $result->fetch_assoc();
}

/**
 * آماده سازی داده‌های CSV
 */
function prepareCSVData($member) {
    $data = [
        ['سامانه مسکن سازان نقشه برداری', ''],
        ['', ''],
        ['اطلاعات عضو', ''],
        ['کد پیگیری', sprintf('%06d', $member['id'] ?? 0)],
        ['نام', $member['first_name'] ?? 'نامشخص'],
        ['نام خانوادگی', $member['last_name'] ?? 'نامشخص'],
        ['شماره ملی', $member['national_id'] ?? 'ندارد'],
        ['شماره شناسنامه', $member['birth_certificate_number'] ?? 'ندارد'],
        ['نام پدر', $member['father_name'] ?? 'نامشخص'],
        ['تاریخ تولد', $member['birth_date_fa'] ?? 'ندارد'],
        ['تلفن همراه', $member['mobile'] ?? 'ندارد'],
        ['تلفن ثابت', $member['phone'] ?? 'ندارد'],
        ['آدرس', str_replace(["\r\n", "\r", "\n"], " | ", $member['address'] ?? 'ندارد')],
        ['تاریخ ثبت', date('Y/m/d H:i:s', strtotime($member['registration_date']))],
        ['وضعیت', getStatusText($member['status'] ?? 'pending')],
        ['', ''],
        ['تاریخ تولید گزارش', date('Y/m/d H:i:s')],
        ['آدرس سیستم', $_SERVER['HTTP_HOST'] ?? 'localhost']
    ];

    return $data;
}

/**
 * آماده سازی داده‌های Excel
 */
function prepareExcelData($member) {
    return [
        'کد پیگیری' => sprintf('%06d', $member['id'] ?? 0),
        'نام' => $member['first_name'] ?? 'نامشخص',
        'نام خانوادگی' => $member['last_name'] ?? 'نامشخص',
        'شماره ملی' => $member['national_id'] ?? 'ندارد',
        'شماره شناسنامه' => $member['birth_certificate_number'] ?? 'ندارد',
        'نام پدر' => $member['father_name'] ?? 'نامشخص',
        'تاریخ تولد' => $member['birth_date_fa'] ?? 'ندارد',
        'تلفن همراه' => $member['mobile'] ?? 'ندارد',
        'تلفن ثابت' => $member['phone'] ?? 'ندارد',
        'آدرس' => $member['address'] ?? 'ندارد',
        'تاریخ ثبت نام' => date('Y/m/d H:i:s', strtotime($member['registration_date'])),
        'وضعیت' => getStatusText($member['status'] ?? 'pending'),
        'آی‌پی ثبت نام' => $member['ip_address'] ?? 'ندارد'
    ];
}

/**
 * دریافت متن وضعیت
 */
function getStatusText($status) {
    $statuses = [
        'pending' => 'در انتظار تأیید',
        'approved' => 'تأیید شده',
        'rejected' => 'رد شده',
        'active' => 'فعال',
        'inactive' => 'غیرفعال'
    ];

    return $statuses[$status] ?? $status;
}

/**
 * دریافت بدج وضعیت
 */
function getStatusBadge($status) {
    $colors = [
        'pending' => 'warning',
        'approved' => 'success',
        'rejected' => 'danger',
        'active' => 'success',
        'inactive' => 'secondary'
    ];

    $color = $colors[$status] ?? 'secondary';
    $text = getStatusText($status);

    return '<span class="badge bg-' . $color . '">' . $text . '</span>';
}

/**
 * لاگ دانلود
 */
function logDownload($member_id, $type) {
    $log = sprintf(
        "[%s] دانلود %s برای عضو #%s - آی‌پی: %s\n",
        date('Y-m-d H:i:s'),
        strtoupper($type),
        $member_id,
        $_SERVER['REMOTE_ADDR'] ?? 'نامشخص'
    );

    file_put_contents('downloads.log', $log, FILE_APPEND);
}

// ==================== توابع نمایش صفحات ====================

/**
 * صفحه فرود
 */
function showLandingPage() {
    echo '<!DOCTYPE html>
    <html dir="rtl" lang="fa">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>سامانه دانلود اطلاعات اعضا</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                font-family: "Vazirmatn", Tahoma, sans-serif;
            }
            .landing-card {
                background: rgba(255,255,255,0.95);
                border-radius: 20px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                margin-top: 100px;
            }
            .step-box {
                background: #f8f9fa;
                border-radius: 10px;
                padding: 20px;
                margin: 15px 0;
                border-right: 4px solid #3498db;
            }
            .step-number {
                background: #3498db;
                color: white;
                width: 40px;
                height: 40px;
                border-radius: 50%;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                margin-left: 10px;
                font-weight: bold;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <div class="landing-card p-5">
                        <div class="text-center mb-5">
                            <h1 class="text-primary mb-3">سامانه دانلود اطلاعات اعضا</h1>
                            <p class="text-muted">برای دسترسی به اطلاعات عضو، لطفا کد پیگیری خود را وارد کنید</p>
                        </div>
                        
                        <form action="" method="GET" class="mb-5">
                            <div class="input-group input-group-lg">
                                <input type="number" 
                                       name="id" 
                                       class="form-control" 
                                       placeholder="کد پیگیری ۶ رقمی" 
                                       required
                                       min="1"
                                       max="999999">
                                <button class="btn btn-primary" type="submit">
                                    🔍 جستجو
                                </button>
                            </div>
                            <small class="text-muted mt-2 d-block">
                                کد پیگیری را از ایمیل یا پیامک دریافتی کپی کنید
                            </small>
                        </form>
                        
                        <div class="mt-5">
                            <h5 class="mb-4">راهنمای استفاده:</h5>
                            
                            <div class="step-box">
                                <div class="step-number">۱</div>
                                <strong>کد پیگیری خود را وارد کنید</strong>
                                <p class="mb-0 mt-2 text-muted">کد ۶ رقمی که پس از ثبت نام دریافت کرده‌اید</p>
                            </div>
                            
                            <div class="step-box">
                                <div class="step-number">۲</div>
                                <strong>اطلاعات را مشاهده کنید</strong>
                                <p class="mb-0 mt-2 text-muted">تمام اطلاعات ثبت نامی شما نمایش داده می‌شود</p>
                            </div>
                            
                            <div class="step-box">
                                <div class="step-number">۳</div>
                                <strong>دانلود یا چاپ کنید</strong>
                                <p class="mb-0 mt-2 text-muted">می‌توانید اطلاعات را به صورت CSV یا Excel دانلود کنید</p>
                            </div>
                        </div>
                        
                        <div class="text-center mt-5 pt-4 border-top">
                            <a href="index.php" class="btn btn-outline-primary">
                                ← بازگشت به صفحه اصلی
                            </a>
                            <a href="?search=admin" class="btn btn-outline-secondary">
                                ورود مدیر
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>';
}

/**
 * صفحه خطا
 */
function showErrorPage($title, $details) {
    return '<!DOCTYPE html>
    <html dir="rtl" lang="fa">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>خطا - ' . htmlspecialchars($title) . '</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body {
                background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
                font-family: "Vazirmatn", Tahoma, sans-serif;
            }
            .error-card {
                background: white;
                border-radius: 20px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                max-width: 600px;
                width: 100%;
                overflow: hidden;
            }
            .error-icon {
                font-size: 80px;
                text-align: center;
                margin: 20px 0;
            }
            .error-details {
                background: #f9ebea;
                border: 1px solid #f5c6cb;
                border-radius: 10px;
                padding: 20px;
                margin: 20px;
                font-family: monospace;
                font-size: 14px;
                color: #721c24;
                text-align: right;
                direction: ltr;
            }
        </style>
    </head>
    <body>
        <div class="error-card p-4">
            <div class="error-icon">⚠️</div>
            <h1 class="text-center text-danger mb-4">' . htmlspecialchars($title) . '</h1>
            
            <div class="error-details">
                ' . nl2br(htmlspecialchars($details)) . '
            </div>
            
            <div class="text-center mt-4">
                <a href="javascript:history.back()" class="btn btn-secondary mx-2">← بازگشت</a>
                <a href="download_member.php" class="btn btn-primary mx-2">🔍 جستجوی جدید</a>
                <a href="index.php" class="btn btn-outline-primary mx-2">🏠 صفحه اصلی</a>
            </div>
            
            <div class="text-center mt-4 pt-3 border-top">
                <small class="text-muted">
                    تاریخ خطا: ' . date('Y/m/d H:i:s') . ' | 
                    آی‌پی: ' . ($_SERVER['REMOTE_ADDR'] ?? 'نامشخص') . '
                </small>
            </div>
        </div>
    </body>
    </html>';
}

/**
 * صفحه اطلاعات عضو
 */
function showMemberPage($member) {
    // SVG آیکون‌ها
    $icons = [
        'user' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>',
        'phone' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>',
        'home' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>',
        'file' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>',
        'download' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>',
        'printer' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>',
        'calendar' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>',
        'check' => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>'
    ];

    echo '<!DOCTYPE html>
    <html dir="rtl" lang="fa">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>مشخصات عضو - کد پیگیری ' . sprintf('%06d', $member['id']) . '</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            :root {
                --primary-color: #3498db;
                --secondary-color: #2c3e50;
                --success-color: #27ae60;
                --warning-color: #f39c12;
                --danger-color: #e74c3c;
            }
            
            body {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                font-family: "Vazirmatn", Tahoma, sans-serif;
                padding: 20px;
            }
            
            .member-card {
                background: white;
                border-radius: 20px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                overflow: hidden;
                max-width: 1000px;
                margin: 0 auto;
            }
            
            .member-header {
                background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
                color: white;
                padding: 30px;
                text-align: center;
            }
            
            .avatar {
                width: 120px;
                height: 120px;
                background: white;
                border-radius: 50%;
                margin: 0 auto 20px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 48px;
                color: var(--primary-color);
                border: 5px solid rgba(255,255,255,0.3);
            }
            
            .info-section {
                padding: 30px;
                border-bottom: 1px solid #eee;
            }
            
            .info-section:last-child {
                border-bottom: none;
            }
            
            .section-title {
                font-size: 22px;
                color: var(--secondary-color);
                margin-bottom: 25px;
                padding-bottom: 10px;
                border-bottom: 2px solid #f0f0f0;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            
            .info-item {
                background: #f8f9fa;
                border-radius: 10px;
                padding: 20px;
                margin-bottom: 15px;
                transition: all 0.3s;
                border-right: 4px solid var(--primary-color);
            }
            
            .info-item:hover {
                background: #e9ecef;
                transform: translateX(-5px);
            }
            
            .info-label {
                font-weight: bold;
                color: var(--secondary-color);
                margin-bottom: 8px;
                display: flex;
                align-items: center;
                gap: 8px;
                font-size: 16px;
            }
            
            .info-value {
                color: #555;
                font-size: 18px;
                padding-right: 25px;
            }
            
            .badge {
                padding: 8px 15px;
                font-size: 14px;
                border-radius: 20px;
            }
            
            .btn-custom {
                padding: 12px 30px;
                border-radius: 25px;
                font-weight: bold;
                display: inline-flex;
                align-items: center;
                gap: 10px;
                transition: all 0.3s;
                border: none;
                text-decoration: none;
            }
            
            .btn-custom:hover {
                transform: translateY(-3px);
                box-shadow: 0 10px 20px rgba(0,0,0,0.2);
            }
            
            .btn-download {
                background: linear-gradient(90deg, var(--success-color), #219653);
                color: white;
            }
            
            .btn-print {
                background: var(--primary-color);
                color: white;
            }
            
            .btn-excel {
                background: linear-gradient(90deg, #1e7e34, #155724);
                color: white;
            }
            
            /* استایل چاپ */
            @media print {
                body {
                    background: white !important;
                    padding: 0 !important;
                }
                .member-card {
                    box-shadow: none !important;
                    margin: 0 !important;
                }
                .btn-custom, .btn-back {
                    display: none !important;
                }
            }
            
            /* موبایل */
            @media (max-width: 768px) {
                .member-header {
                    padding: 20px;
                }
                
                .avatar {
                    width: 80px;
                    height: 80px;
                    font-size: 36px;
                }
                
                .info-section {
                    padding: 20px;
                }
                
                .btn-custom {
                    width: 100%;
                    margin-bottom: 10px;
                }
            }
        </style>
    </head>
    <body>
        <div class="member-card">
            <!-- هدر -->
            <div class="member-header">
                <div class="avatar">👤</div>
                <h1>' . htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) . '</h1>
                <p class="mb-0" style="font-size: 18px; opacity: 0.9;">
                    کد پیگیری: <strong>' . sprintf('%06d', $member['id']) . '</strong>
                </p>
            </div>
            
            <!-- اطلاعات شخصی -->
            <div class="info-section">
                <h2 class="section-title">' . $icons['user'] . ' اطلاعات شخصی</h2>
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="info-label">' . $icons['file'] . ' شماره ملی</div>
                            <div class="info-value">' . htmlspecialchars($member['national_id'] ?? 'ندارد') . '</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="info-label">' . $icons['file'] . ' شماره شناسنامه</div>
                            <div class="info-value">' . htmlspecialchars($member['birth_certificate_number'] ?? 'ندارد') . '</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="info-label">' . $icons['user'] . ' نام پدر</div>
                            <div class="info-value">' . htmlspecialchars($member['father_name'] ?? 'ندارد') . '</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="info-label">' . $icons['calendar'] . ' تاریخ تولد</div>
                            <div class="info-value">' . htmlspecialchars($member['birth_date_fa'] ?? 'ندارد') . '</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- اطلاعات تماس -->
            <div class="info-section">
                <h2 class="section-title">' . $icons['phone'] . ' اطلاعات تماس</h2>
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="info-label">' . $icons['phone'] . ' تلفن همراه</div>
                            <div class="info-value">' . htmlspecialchars($member['mobile'] ?? 'ندارد') . '</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="info-label">' . $icons['phone'] . ' تلفن ثابت</div>
                            <div class="info-value">' . htmlspecialchars($member['phone'] ?? 'ندارد') . '</div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="info-item">
                            <div class="info-label">' . $icons['home'] . ' آدرس</div>
                            <div class="info-value">' . nl2br(htmlspecialchars($member['address'] ?? 'ندارد')) . '</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- اطلاعات ثبت نام -->
            <div class="info-section">
                <h2 class="section-title">' . $icons['file'] . ' اطلاعات ثبت نام</h2>
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="info-label">' . $icons['calendar'] . ' تاریخ ثبت نام</div>
                            <div class="info-value">' . date('Y/m/d H:i:s', strtotime($member['registration_date'])) . '</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-item">
                            <div class="info-label">' . $icons['check'] . ' وضعیت</div>
                            <div class="info-value">' . getStatusBadge($member['status']) . '</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- دکمه‌های عملیات -->
            <div class="info-section text-center">
                <h2 class="section-title">' . $icons['download'] . ' عملیات</h2>
                <div class="row justify-content-center">
                    <div class="col-md-10">
                        <div class="d-flex flex-wrap justify-content-center gap-3 mb-4">
                            <a href="?id=' . $member['id'] . '&download=csv" 
                               class="btn-custom btn-download"
                               onclick="showLoading()">
                                ' . $icons['download'] . ' دانلود CSV
                            </a>
                            
                            <a href="?id=' . $member['id'] . '&download=excel" 
                               class="btn-custom btn-excel"
                               onclick="showLoading()">
                                📊 دانلود Excel
                            </a>
                            
                            <button onclick="window.print()" class="btn-custom btn-print">
                                ' . $icons['printer'] . ' چاپ اطلاعات
                            </button>
                        </div>
                        
                        <p class="text-muted">
                            <small>فایل‌های CSV و Excel قابل باز شدن در Microsoft Excel, Google Sheets و LibreOffice هستند</small>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- فوتر -->
            <div class="text-center py-3 bg-light">
                <small class="text-muted">
                    تاریخ تولید: ' . date('Y/m/d H:i:s') . ' | 
                    آی‌پی درخواست: ' . ($_SERVER['REMOTE_ADDR'] ?? 'نامشخص') . ' | 
                    سامانه مسکن سازان نقشه‌برداری
                </small>
            </div>
        </div>
        
        <!-- دکمه بازگشت -->
        <div class="text-center mt-4">
            <a href="download_member.php" class="btn btn-outline-primary btn-lg">
                ← بازگشت به صفحه جستجو
            </a>
        </div>
        
        <!-- اسکریپت‌ها -->
        <script>
            function showLoading() {
                const loading = document.createElement("div");
                loading.id = "loading-overlay";
                loading.innerHTML = `
                    <div style="
                        position: fixed;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        background: rgba(0,0,0,0.7);
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        z-index: 9999;
                    ">
                        <div style="
                            background: white;
                            padding: 30px;
                            border-radius: 10px;
                            text-align: center;
                            box-shadow: 0 0 20px rgba(0,0,0,0.3);
                        ">
                            <div style="font-size: 48px; margin-bottom: 20px;">⏳</div>
                            <h3>در حال آماده‌سازی فایل...</h3>
                            <p>لطفا چند لحظه صبر کنید</p>
                        </div>
                    </div>
                `;
                document.body.appendChild(loading);
            }
            
            window.onload = function() {
                console.log("صفحه اطلاعات عضو بارگذاری شد - کد پیگیری: ' . $member['id'] . '");
            };
        </script>
    </body>
    </html>';
}

/**
 * صفحه مدیریت (برای مدیر)
 */
function showAdminPage($result, $search_query = '') {
    echo '<!DOCTYPE html>
    <html dir="rtl" lang="fa">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>مدیریت اعضا - سامانه مسکن سازان</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
        <style>
            body {
                background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
                min-height: 100vh;
                padding: 20px;
                font-family: "Vazirmatn", Tahoma, sans-serif;
            }
            .admin-card {
                background: white;
                border-radius: 15px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                padding: 30px;
                margin-top: 50px;
            }
            .table th {
                background: #3498db;
                color: white;
            }
            .search-box {
                background: #f8f9fa;
                padding: 20px;
                border-radius: 10px;
                margin-bottom: 30px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="admin-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="text-primary">مدیریت اعضا</h1>
                    <a href="download_member.php" class="btn btn-outline-primary">← بازگشت</a>
                </div>
                
                <div class="search-box">
                    <form method="GET" class="row g-3">
                        <input type="hidden" name="search" value="admin">
                        <div class="col-md-8">
                            <input type="text" 
                                   name="query" 
                                   class="form-control form-control-lg" 
                                   placeholder="جستجو بر اساس نام، نام خانوادگی، کدملی یا تلفن..."
                                   value="' . htmlspecialchars($search_query) . '">
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary btn-lg w-100">🔍 جستجو</button>
                        </div>
                    </form>
                </div>';

    if ($result->num_rows > 0) {
        echo '<div class="table-responsive">
                <table class="table table-hover table-striped" id="membersTable">
                    <thead>
                        <tr>
                            <th>کد پیگیری</th>
                            <th>نام</th>
                            <th>نام خانوادگی</th>
                            <th>کد ملی</th>
                            <th>تلفن</th>
                            <th>تاریخ ثبت</th>
                            <th>وضعیت</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>';

        while ($row = $result->fetch_assoc()) {
            echo '<tr>
                    <td>' . sprintf('%06d', $row['id']) . '</td>
                    <td>' . htmlspecialchars($row['first_name']) . '</td>
                    <td>' . htmlspecialchars($row['last_name']) . '</td>
                    <td>' . htmlspecialchars($row['national_id']) . '</td>
                    <td>' . htmlspecialchars($row['mobile']) . '</td>
                    <td>' . date('Y/m/d', strtotime($row['registration_date'])) . '</td>
                    <td>' . getStatusBadge($row['status']) . '</td>
                    <td>
                        <a href="?id=' . $row['id'] . '" class="btn btn-sm btn-info">مشاهده</a>
                        <a href="?id=' . $row['id'] . '&download=csv" class="btn btn-sm btn-success">CSV</a>
                    </td>
                  </tr>';
        }

        echo '</tbody>
                </table>
              </div>';

        echo '<div class="alert alert-info mt-3">
                تعداد نتایج: <strong>' . $result->num_rows . '</strong> مورد یافت شد
              </div>';
    } else {
        echo '<div class="alert alert-warning text-center">
                <h4>❌ هیچ عضوی یافت نشد</h4>
                <p>لطفا عبارت جستجوی خود را تغییر دهید</p>
              </div>';
    }

    echo '<div class="text-center mt-4 pt-3 border-top">
            <a href="?search=admin" class="btn btn-outline-secondary">نمایش همه اعضا</a>
            <a href="download_member.php" class="btn btn-outline-primary">صفحه اصلی</a>
          </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $("#membersTable").DataTable({
                language: {
                    url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/fa.json"
                },
                pageLength: 25,
                order: [[0, "desc"]]
            });
        });
    </script>
    </body>
    </html>';
}
?>