<?php
// upload_document.php - نسخه نهایی بهبود یافته
session_start();
header('Content-Type: application/json; charset=utf-8');

// لاگ برای دیباگ
error_log("Upload document request received");
error_log("POST data: " . print_r($_POST, true));
error_log("FILES data: " . print_r($_FILES, true));

// بررسی CSRF
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    error_log("CSRF token validation failed");
    echo json_encode(['success' => false, 'message' => 'توکن امنیتی نامعتبر است']);
    exit;
}

// بررسی فیلدهای ضروری
if (empty($_POST['user_id']) || empty($_POST['document_type'])) {
    echo json_encode(['success' => false, 'message' => 'فیلدهای ضروری ارسال نشده‌اند']);
    exit;
}

// اتصال به دیتابیس
$mysqli = new mysqli('localhost', 'root', '', 'maskansazan');
if ($mysqli->connect_error) {
    echo json_encode(['success' => false, 'message' => 'خطا در اتصال به دیتابیس']);
    exit;
}
$mysqli->set_charset('utf8mb4');

// دریافت و پردازش داده‌ها با بررسی وجود کلیدها
$user_id = (int)$_POST['user_id'];
$document_type = $mysqli->real_escape_string(trim($_POST['document_type']));
$description = $mysqli->real_escape_string(trim($_POST['description'] ?? ''));

// ========== رفع خطای document_name ==========
$document_name = $mysqli->real_escape_string(
    !empty(trim($_POST['document_name'] ?? ''))
        ? trim($_POST['document_name'])
        : $document_type
);
// ========== پایان رفع خطا ==========

// بررسی فایل
if (!isset($_FILES['document_file']) || $_FILES['document_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'خطا در آپلود فایل']);
    exit;
}

$file = $_FILES['document_file'];

// بررسی نوع فایل
$allowed_types = [
    'image/jpeg',
    'image/jpg',
    'image/png',
    'image/gif',
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
];

if (!in_array($file['type'], $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'نوع فایل نامعتبر. فقط تصویر، PDF و Word مجاز است']);
    exit;
}

// بررسی حجم فایل (حداکثر 5MB)
if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'حجم فایل باید کمتر از 5 مگابایت باشد']);
    exit;
}

// ایجاد پوشه و ذخیره فایل
$upload_dir = '../uploads/documents/' . $user_id . '/';
if (!file_exists($upload_dir)) {
    if (!mkdir($upload_dir, 0777, true)) {
        echo json_encode(['success' => false, 'message' => 'خطا در ایجاد پوشه آپلود']);
        exit;
    }
}

// ایجاد نام فایل امن
$file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$safe_filename = preg_replace('/[^a-zA-Z0-9._-]/', '', pathinfo($file['name'], PATHINFO_FILENAME));
$new_filename = 'doc_' . $user_id . '_' . time() . '_' . uniqid() . '.' . $file_ext;
$file_path = $upload_dir . $new_filename;

// آپلود فایل
if (!move_uploaded_file($file['tmp_name'], $file_path)) {
    echo json_encode(['success' => false, 'message' => 'خطا در ذخیره فایل روی سرور']);
    exit;
}

// ذخیره در دیتابیس
$stmt = $mysqli->prepare("
    INSERT INTO user_documents 
    (user_id, document_name, document_type, description, file_path, file_name, file_size, file_type, upload_date, verification_status) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'pending')
");

$original_filename = $mysqli->real_escape_string($file['name']);
$file_type_db = $mysqli->real_escape_string($file['type']);
$file_size = $file['size'];

if (!$stmt) {
    unlink($file_path);
    echo json_encode(['success' => false, 'message' => 'خطا در آماده‌سازی کوئری: ' . $mysqli->error]);
    exit;
}

$stmt->bind_param(
    "isssssis",
    $user_id,
    $document_name,
    $document_type,
    $description,
    $file_path,
    $original_filename,
    $file_size,
    $file_type_db
);

if ($stmt->execute()) {
    $document_id = $stmt->insert_id;

    // تبدیل مسیر به مسیر نسبی برای نمایش بهتر
    $relative_path = str_replace('../', '', $file_path);

    echo json_encode([
        'success' => true,
        'message' => 'مدرک با موفقیت آپلود شد',
        'document_id' => $document_id,
        'file_path' => $relative_path,
        'document_name' => $document_name,
        'upload_date' => date('Y-m-d H:i:s')
    ]);
} else {
    // حذف فایل آپلود شده در صورت خطا در دیتابیس
    unlink($file_path);
    echo json_encode([
        'success' => false,
        'message' => 'خطا در ذخیره اطلاعات در دیتابیس: ' . $stmt->error
    ]);
}

$stmt->close();
$mysqli->close();
?>