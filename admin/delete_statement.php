<?php
// شروع سشن و اتصال به دیتابیس (مشابه سایر فایل‌ها)
session_start();

// دریافت و اعتبارسنجی مقادیر
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_project_id = isset($_GET['user_project_id']) ? (int)$_GET['user_project_id'] : 0;
$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;

if ($id > 0) {
    try {
        // استفاده از Prepared Statement برای امنیت
        $pdo = new PDO(
            "mysql:host=localhost;dbname=maskanhb_maskansazan;charset=utf8mb4",
            "root",
            "",
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );;
        $stmt = $pdo->prepare("DELETE FROM member_statements WHERE id = :id");
        $stmt->execute([':id' => $id]);

        // بازگشت به لیست با پیام موفقیت
        header("Location: member_statements.php?user_project_id=$user_project_id&project_id=$project_id&success=deleted");
        exit;
    } catch (PDOException $e) {
        die("خطا در حذف رکورد: " . $e->getMessage());
    }
} else {
    // اگر آیدی نامعتبر بود
    header("Location: member_statements.php?user_project_id=$user_project_id&project_id=$project_id&error=invalid_id");
    exit;
}
