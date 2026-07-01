<?php
// admin/delete_document.php
session_start();

// ۱. تنظیمات برای دیدن هرگونه خطا
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=utf-8');

// بررسی لاگین
if (empty($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'شما لاگین نیستید.']);
    exit;
}

// بررسی وجود آیدی
if (!isset($_POST['document_id'])) {
    echo json_encode(['success' => false, 'message' => 'شناسه مدرک ارسال نشده است.']);
    echo $_POST['document_id'];
    exit;
}

$document_id = (int)$_POST['document_id'];

// اتصال به دیتابیس (مطابق تنظیمات شما)
$mysqli = new mysqli('localhost', 'root', '', 'maskanhb_maskansazan');
if ($mysqli->connect_error) {
    echo json_encode(['success' => false, 'message' => 'خطای دیتابیس']);
    exit;
}

// مرحله ۱: پیدا کردن فایل
$stmt = $mysqli->prepare("SELECT file_path FROM user_documents WHERE id = ?");
$stmt->bind_param('i', $document_id);
$stmt->execute();
$result = $stmt->get_result();
$doc = $result->fetch_assoc();

if (!$doc) {
    echo json_encode(['success' => false, 'message' => 'مدرک در دیتابیس یافت نشد.']);
    exit;
}

// مرحله ۲: حذف فایل فیزیکی
// توجه: DOCUMENT_ROOT معمولاً به پوشه public_html یا www اشاره دارد
$fullPath = $_SERVER['DOCUMENT_ROOT'] . $doc['file_path'];

if (file_exists($fullPath)) {
    unlink($fullPath); // حذف فایل
}

// مرحله ۳: حذف از دیتابیس
$stmtDelete = $mysqli->prepare("DELETE FROM user_documents WHERE id = ?");
$stmtDelete->bind_param('i', $document_id);

if ($stmtDelete->execute()) {
    echo json_encode(['success' => true, 'message' => 'مدرک با موفقیت حذف شد.']);
} else {
    echo json_encode(['success' => false, 'message' => 'خطا در حذف از دیتابیس.']);
}

$mysqli->close();
?>
