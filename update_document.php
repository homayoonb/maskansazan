<?php
// update_document.php - نسخه نهایی با ساختار واقعی جدول
session_start();
header('Content-Type: application/json');

// اتصال به دیتابیس
$DB_HOST = 'localhost';
$DB_NAME = 'maskansazan';
$DB_USER = 'root';
$DB_PASS = '';

$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($mysqli->connect_error) {
    echo json_encode(['success' => false, 'message' => 'خطا در اتصال به پایگاه داده']);
    exit;
}
$mysqli->set_charset("utf8mb4");

// بررسی CSRF Token (برای امنیت)
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'توکن امنیتی نامعتبر است']);
    exit;
}

// بررسی پارامترهای ورودی
if (!isset($_POST['doc_id']) || !isset($_POST['status'])) {
    echo json_encode(['success' => false, 'message' => 'پارامترهای ضروری ارسال نشده است']);
    exit;
}

$doc_id = (int)$_POST['doc_id'];
$status = $_POST['status']; // verified, rejected, pending

// تبدیل status به فرمت مناسب برای enum در دیتابیس
$statusMap = [
    'verified' => 'verified',
    'rejected' => 'rejected',
    'pending' => 'pending',
    'تایید شده' => 'verified',
    'رد شده' => 'rejected',
    'در انتظار' => 'pending'
];

$dbStatus = $statusMap[strtolower($status)] ?? 'pending';

// کوئری به‌روزرسانی با ستون‌های صحیح جدول
// بر اساس ساختار جدول: verification_status (enum) و verification_date (datetime)
$stmt = $mysqli->prepare("
    UPDATE user_documents 
    SET verification_status = ?, 
        verification_date = NOW(),
        verified_by = 'admin'
    WHERE id = ?
");

if (!$stmt) {
    echo json_encode([
        'success' => false,
        'message' => 'خطا در آماده‌سازی کوئری: ' . $mysqli->error
    ]);
    exit;
}

$stmt->bind_param("si", $dbStatus, $doc_id);

if ($stmt->execute()) {
    $affectedRows = $stmt->affected_rows;

    echo json_encode([
        'success' => true,
        'message' => 'وضعیت مدرک با موفقیت به‌روزرسانی شد',
        'doc_id' => $doc_id,
        'new_status' => $dbStatus,
        'affected_rows' => $affectedRows
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'خطا در به‌روزرسانی: ' . $stmt->error
    ]);
}

$stmt->close();
$mysqli->close();
?>