<?php
// update_member_status.php
session_start();

// در محیط production این خطوط را غیرفعال کنید
if ($_SERVER['SERVER_NAME'] !== 'localhost') {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
} else {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

// لاگ کردن اطلاعات دریافتی (با فیلتر کردن داده‌های حساس)
$logData = $_POST;
if (isset($logData['password'])) unset($logData['password']);
error_log("Received POST data: " . print_r($logData, true));

// بررسی CSRF token با session_regenerate_id بعد از استفاده
if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'توکن امنیتی نامعتبر']);
    exit;
}
// رفرش توکن بعد از استفاده
unset($_SESSION['csrf_token']);

// بررسی ادمین بودن کاربر
if (empty($_SESSION['admin_id']) || empty($_SESSION['admin_username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'دسترسی غیرمجاز']);
    exit;
}

// بررسی متد درخواست
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'متد غیرمجاز']);
    exit;
}

// بررسی پارامترها
if (empty($_POST['member_id']) || empty($_POST['status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'پارامترهای ناقص']);
    exit;
}

$member_id = (int)$_POST['member_id'];
$status = trim($_POST['status']);

// بررسی مقدار member_id
if ($member_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'شناسه عضو نامعتبر']);
    exit;
}

// وضعیت‌های مجاز
$allowed_statuses = [
    'pending' => 'در انتظار',
    'approved' => 'تایید شده',
    'rejected' => 'رد شده'
];

// تبدیل وضعیت‌های فارسی به انگلیسی
$status_mapping = [
    'تایید شده' => 'approved',
    'تأیید شده' => 'approved',
    'رد شده' => 'rejected',
    'در انتظار' => 'pending',
    'منتظر' => 'pending'
];

$status_lower = strtolower($status);
if (isset($status_mapping[$status])) {
    $status_lower = $status_mapping[$status];
}

// بررسی وضعیت
if (!in_array($status_lower, array_keys($allowed_statuses))) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'وضعیت نامعتبر',
        'allowed_statuses' => $allowed_statuses
    ]);
    exit;
}

// اتصال به دیتابیس با try-catch
try {
    $mysqli = new mysqli('localhost', 'maskanhb_maskansazan', 'NJ)B*$@y3R9P0Oyg', 'maskanhb_maskansazan');

    if ($mysqli->connect_error) {
        throw new Exception("خطا در اتصال به دیتابیس: " . $mysqli->connect_error);
    }

    $mysqli->set_charset("utf8mb4");

    // شروع تراکنش
    $mysqli->begin_transaction();

    // بررسی وجود عضو
    $check_stmt = $mysqli->prepare("SELECT id, status as current_status FROM members WHERE id = ?");
    if (!$check_stmt) {
        throw new Exception("خطا در آماده‌سازی کوئری بررسی: " . $mysqli->error);
    }

    $check_stmt->bind_param("i", $member_id);
    if (!$check_stmt->execute()) {
        throw new Exception("خطا در اجرای کوئری بررسی: " . $check_stmt->error);
    }

    $check_result = $check_stmt->get_result();
    if ($check_result->num_rows === 0) {
        throw new Exception("عضو یافت نشد");
    }

    $member_data = $check_result->fetch_assoc();
    $check_stmt->close();

    // اگر وضعیت تغییری نکرده باشد
    if ($member_data['current_status'] === $status_lower) {
        $mysqli->rollback();
        $mysqli->close();
        echo json_encode([
            'success' => false,
            'message' => 'وضعیت عضو قبلاً ' . ($allowed_statuses[$status_lower] ?? $status_lower) . ' است'
        ]);
        exit;
    }

    // آپدیت وضعیت عضو
    $stmt = $mysqli->prepare("UPDATE members SET status = ?, updated_at = NOW() WHERE id = ?");
    if (!$stmt) {
        throw new Exception("خطا در آماده‌سازی کوئری آپدیت: " . $mysqli->error);
    }

    $stmt->bind_param("si", $status_lower, $member_id);

    if (!$stmt->execute()) {
        throw new Exception("خطا در اجرای کوئری آپدیت: " . $stmt->error);
    }

    // لاگ کردن تغییر وضعیت
    $log_stmt = $mysqli->prepare("
        INSERT INTO member_status_logs 
        (member_id, old_status, new_status, admin_id, changed_at) 
        VALUES (?, ?, ?, ?, NOW())
    ");

    if ($log_stmt) {
        $log_stmt->bind_param("issi",
            $member_id,
            $member_data['current_status'],
            $status_lower,
            $_SESSION['admin_id']
        );
        $log_stmt->execute();
        $log_stmt->close();
    }

    // کامیت تراکنش
    $mysqli->commit();

    echo json_encode([
        'success' => true,
        'message' => 'وضعیت عضو با موفقیت به ' . ($allowed_statuses[$status_lower] ?? $status_lower) . ' تغییر یافت',
        'new_status' => $status_lower,
        'persian_status' => $allowed_statuses[$status_lower] ?? $status_lower
    ]);

    $stmt->close();
    $mysqli->close();

} catch (Exception $e) {
    // در صورت خطا، رول‌بک کن
    if (isset($mysqli)) {
        $mysqli->rollback();
        $mysqli->close();
    }

    http_response_code(500);
    error_log("Error in update_member_status: " . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => 'خطا در پردازش درخواست: ' . $e->getMessage()
    ]);
    exit;
}
?>