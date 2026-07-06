<?php
require_once('SafeDatabase.php');
$mysqli = SafeDatabase::connect();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['upload_document'])) {
    die("دسترسی غیرمجاز.");
}

// دریافت اطلاعات ارسالی از فرم
$user_project_id = isset($_POST['user_project_id']) ? (int)$_POST['user_project_id'] : 0;
$project_id      = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
$docName         = trim($_POST['document_name'] ?? '');
$docType         = trim($_POST['document_type'] ?? '');
$full_name       = trim($_POST['full_name'] ?? ''); // <--- اضافه شده: دریافت نام کاربر از فرم

if ($user_project_id <= 0 || $docName === '' || $docType === '' || $project_id <= 0) {
    echo "<pre>";
    echo "اطلاعات فرم ناقص است.\n\n";
    echo "user_project_id: ";
    var_dump($user_project_id);
    echo "project_id: ";
    var_dump($project_id);
    echo "document_name: ";
    var_dump($docName);
    echo "document_type: ";
    var_dump($docType);
    echo "full_name: ";
    var_dump($full_name);
    echo "file: ";
    var_dump($_FILES['document_file'] ?? null);
    echo "</pre>";
    exit;
}

if (!isset($_FILES['document_file']) || $_FILES['document_file']['error'] !== UPLOAD_ERR_OK) {
    die("خطا در فایل ارسالی.");
}

$fileTmpPath      = $_FILES['document_file']['tmp_name'];
$originalFileName = $_FILES['document_file']['name'];
$fileSize         = $_FILES['document_file']['size'];
$fileExtension    = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));

$uploadFileDir = '../uploads/documents/';
if (!is_dir($uploadFileDir)) {
    mkdir($uploadFileDir, 0777, true);
}

$newFileName = time() . '_' . md5($originalFileName) . '.' . $fileExtension;
$dest_path   = $uploadFileDir . $newFileName;

if (move_uploaded_file($fileTmpPath, $dest_path)) {
    $db_path = 'uploads/documents/' . $newFileName;

    // ثبت اطلاعات سند در دیتابیس
    $sql = "INSERT INTO user_documents 
            (project_id, user_project_id, document_name, document_type, file_path, file_name, file_size, file_type, upload_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";

    $stmt = $mysqli->prepare($sql);

    if (!$stmt) {
        unlink($dest_path); // حذف فایل آپلود شده در صورت خطا در پایگاه داده
        die("خطا در آماده‌سازی کوئری: " . $mysqli->error);
    }

    $stmt->bind_param(
        "iissssis",
        $project_id,
        $user_project_id,
        $docName,
        $docType,
        $db_path,
        $originalFileName,
        $fileSize,
        $fileExtension
    );

    if ($stmt->execute()) {
        // <--- تغییر یافته: ارسال full_name انکود شده به صفحه showDocuments.php
        $redirectUrl = "showDocuments.php?user_project_id={$user_project_id}&project_id={$project_id}&full_name=" . urlencode($full_name);
        header("Location: " . $redirectUrl);
        exit;
    } else {
        unlink($dest_path); // حذف فایل آپلود شده در صورت خطا در ثبت دیتابیس
        die("خطا در ثبت اطلاعات در دیتابیس: " . $stmt->error);
    }
} else {
    die("خطا در آپلود فایل روی سرور.");
}
