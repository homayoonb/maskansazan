<?php
// download.php
session_start();

// بررسی لاگین
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}

$user_id = $_SESSION['user_id'] ?? 0;
$document_id = $_GET['id'] ?? 0;

if (empty($document_id)) {
    die("شناسه مدرک مشخص نشده است.");
}

// اتصال به دیتابیس
$db = new mysqli('localhost', 'maskanhb_maskansazan', 'NJ)B*$@y3R9P0Oyg', 'maskanhb_maskansazan');
$db->set_charset("utf8mb4");

// بررسی مالکیت مدرک
$stmt = $db->prepare("
    SELECT file_path, file_name 
    FROM user_documents 
    WHERE id = ? AND user_id = ?
    LIMIT 1
");
$stmt->bind_param("ii", $document_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("مدرک یافت نشد یا دسترسی ندارید.");
}

$document = $result->fetch_assoc();
$file_path = $document['file_path'];
$file_name = $document['file_name'];

$stmt->close();
$db->close();

// بررسی وجود فایل
$full_path = $_SERVER['DOCUMENT_ROOT'] . $file_path;
if (!file_exists($full_path)) {
    die("فایل موجود نیست.");
}

// تعیین MIME Type بر اساس پسوند
$extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
$mime_types = [
    'html' => 'text/html',
    'htm' => 'text/html',
    'pdf' => 'application/pdf',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'xls' => 'application/vnd.ms-excel',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
];

$content_type = $mime_types[$extension] ?? 'application/octet-stream';

// ارسال هدرهای مناسب
header('Content-Type: ' . $content_type);
header('Content-Disposition: inline; filename="' . basename($file_name) . '"');
header('Content-Length: ' . filesize($file_path));
header('Cache-Control: must-revalidate');
header('Pragma: public');

// خروجی فایل
readfile($file_path);
exit;
?>