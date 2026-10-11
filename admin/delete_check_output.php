<?php
// delete_check.php
declare(strict_types=1);
session_start();

$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'));

if ($isAjax) {
    header('Content-Type: application/json; charset=utf-8');
}

// بررسی احراز هویت
if (empty($_SESSION['admin_id']) && empty($_SESSION['admin_username'])) {
    if ($isAjax) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'عدم دسترسی! لطفاً مجدداً وارد پنل شوید.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    header("Location: login.php?error=auth");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($isAjax) {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'متد درخواست نامعتبر است.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    header("Location: dashboardAdmin.php?error=invalid_method");
    exit;
}

try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=maskanhb_maskansazan;charset=utf8mb4",
        "root",
        '',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    $checkId = (int)($_POST['check_id'] ?? $_POST['id'] ?? 0);
    $projectId = (int)($_POST['project_id'] ?? 0);
    $targetTab = !empty($_POST['tab']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['tab']) : 'check_out';

    if ($checkId <= 0) {
        if ($isAjax) {
            echo json_encode(['success' => false, 'message' => 'شناسه چک نامعتبر است.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        header("Location: dashboardAdmin.php?project_id={$projectId}&tab={$targetTab}&error=invalid_id");
        exit;
    }

    // واکشی شماره چک جهت نمایش در پیام
    $stmtSelect = $pdo->prepare("SELECT check_number FROM check_output WHERE id = :id LIMIT 1");
    $stmtSelect->execute([':id' => $checkId]);
    $checkNumber = $stmtSelect->fetchColumn();

    $title = $checkNumber ? "شماره {$checkNumber}" : "شناسه {$checkId}";

    // حذف رکورد از جدول
    $stmtDelete = $pdo->prepare("DELETE FROM check_output WHERE id = :id LIMIT 1");
    $stmtDelete->execute([':id' => $checkId]);

    // ذخیره پیام در سشن (برای نمایش در کادر سبز بالای لیست چک‌ها)
    $successMessage = "چک {$title} با موفقیت حذف گردید.";
    $_SESSION['flash_success'] = $successMessage;

    if ($isAjax) {
        echo json_encode([
            'success' => true,
            'message' => $successMessage
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // هدایت مجدد به همان تب و پروژه
    header("Location: dashboardAdmin.php?project_id={$projectId}&tab={$targetTab}&status=deleted");
    exit;

} catch (PDOException $e) {
    if ($isAjax) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'خطای دیتابیس در حذف چک: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    header("Location: dashboardAdmin.php?tab=check_out&error=db_error");
    exit;
}
