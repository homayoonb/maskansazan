<?php
session_start();
require_once __DIR__ . '/SafeDatabase.php';

// دریافت و اعتبارسنجی مقادیر ورودی
$document_id     = (int)($_GET['id'] ?? 0);
$user_project_id = (int)($_GET['user_project_id'] ?? 0);
$project_id      = (int)($_GET['project_id'] ?? 0);
$status          = $_GET['status'] ?? '';
$full_name       = $_GET['full_name'] ?? '';

$allowedStatuses = ['verified', 'rejected', 'pending'];

if ($document_id <= 0 || $user_project_id <= 0 || !in_array($status, $allowedStatuses, true)) {
    die('درخواست نامعتبر است.');
}

try {
    // پیشنهاد می‌شود از شیء ساخته شده در SafeDatabase استفاده کنید.
    // اما در صورتی که ساختار آن متفاوت است، کد اتصال زیر اصلاح شده است:
    $pdo = new PDO(
        "mysql:host=localhost;dbname=maskanhb_maskansazan;charset=utf8mb4",
        "root",
        "",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    $stmt = $pdo->prepare("
        UPDATE user_documents
        SET verification_status = :status
        WHERE id = :id
    ");

    $stmt->execute([
        ':status' => $status,
        ':id'     => $document_id
    ]);

    // ساخت آدرس بازگشت با رعایت قوانین URL و انکود کردن حروف فارسی
    $redirectUrl = "showDocuments.php?user_project_id=" . $user_project_id
        . "&status=updated"
        . "&project_id=" . $project_id
        . "&full_name=" . urlencode($full_name);

    header("Location: " . $redirectUrl);
    exit;

} catch (PDOException $e) {
    die('خطا در بروزرسانی وضعیت مدرک: ' . $e->getMessage());
}
