<?php
require_once('SafeDatabase.php');
$mysqli = SafeDatabase::connect();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['upload_document'])) {
    die("دسترسی غیرمجاز.");
}

$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$docName = trim($_POST['document_name'] ?? '');
$docType = trim($_POST['document_type'] ?? '');

if ($user_id <= 0 || $docName === '' || $docType === '') {
    echo "<pre>";
    echo "اطلاعات فرم ناقص است.\n\n";
    echo "user_id: ";
    var_dump($user_id);
    echo "document_name: ";
    var_dump($docName);
    echo "document_type: ";
    var_dump($docType);
    echo "file: ";
    var_dump($_FILES['document_file'] ?? null);
    echo "</pre>";
    exit;
}

if (!isset($_FILES['document_file']) || $_FILES['document_file']['error'] !== UPLOAD_ERR_OK) {
    die("خطا در فایل ارسالی.");
}

$fileTmpPath = $_FILES['document_file']['tmp_name'];
$originalFileName = $_FILES['document_file']['name'];
$fileSize = $_FILES['document_file']['size'];
$fileExtension = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));

$uploadFileDir = '../uploads/documents/';
if (!is_dir($uploadFileDir)) {
    mkdir($uploadFileDir, 0777, true);
}

$newFileName = time() . '_' . md5($originalFileName) . '.' . $fileExtension;
$dest_path = $uploadFileDir . $newFileName;

if (move_uploaded_file($fileTmpPath, $dest_path)) {
    $db_path = 'uploads/documents/' . $newFileName;

    // اضافه کردن ستون file_type به کوئری برای رفع خطا
    $sql = "INSERT INTO user_documents 
            (user_id, document_name, document_type, file_path, file_name, file_size, file_type, upload_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";

    $stmt = $mysqli->prepare($sql);

    if (!$stmt) {
        die("خطا در آماده‌سازی: " . $mysqli->error);
    }

    // ترتیب پارامترها: i s s s s i s
    // (user_id, document_name, document_type, file_path, file_name, file_size, file_type)
    $stmt->bind_param("issssis",
        $user_id,
        $docName,
        $docType,
        $db_path,
        $originalFileName,
        $fileSize,
        $fileExtension
    );

    if ($stmt->execute()) {
        header("Location: showDocuments.php?user_id=$user_id");
        exit;
    } else {
        unlink($dest_path); // حذف فایل در صورت خطا در دیتابیس
        die("خطا در ثبت اطلاعات در دیتابیس: " . $stmt->error);
    }
} else {
    die("خطا در آپلود فایل.");
}
