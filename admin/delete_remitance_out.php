<?php
// admin/delete_remitance_out.php
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
    header('Location: login.php?error=auth');
    exit;
}

// اعتبارسنجی متد ارسال
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($isAjax) {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'متد درخواست نامعتبر است.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    header('Location: dashboardAdmin.php?error=invalid_method');
    exit;
}

// دریافت و اعتبارسنجی اولیه پارامترها خارج از بلوک try (جهت دسترسی تضمینی در catch)
$remitanceOutId = (int)($_POST['remitance_out_id'] ?? $_POST['id'] ?? 0);
$projectId      = (int)($_POST['project_id'] ?? 0);
$targetTab      = !empty($_POST['tab']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', (string)$_POST['tab']) : 'remitance_out';

try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=maskanhb_maskansazan;charset=utf8mb4',
        'root',
        '',
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );

    if ($remitanceOutId <= 0) {
        if ($isAjax) {
            echo json_encode(['success' => false, 'message' => 'شناسه حواله نامعتبر است.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $redirectParams = ['tab' => $targetTab, 'error' => 'invalid_id'];
        if ($projectId > 0) $redirectParams['project_id'] = $projectId;
        header('Location: dashboardAdmin.php?' . http_build_query($redirectParams));
        exit;
    }

    // واکشی مشخصات حواله (شماره سند و ذینفع) قبل از حذف
    $stmtSelect = $pdo->prepare('SELECT `id`, `project_id`, `document`, `beneficiary` FROM `remittance_output` WHERE `id` = ? LIMIT 1');
    $stmtSelect->execute([$remitanceOutId]);
    $remittance = $stmtSelect->fetch();

    if (!$remittance) {
        if ($isAjax) {
            echo json_encode(['success' => false, 'message' => 'حواله مورد نظر یافت نشد یا قبلاً حذف شده است.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $redirectParams = ['tab' => $targetTab, 'error' => 'not_found'];
        if ($projectId > 0) $redirectParams['project_id'] = $projectId;
        header('Location: dashboardAdmin.php?' . http_build_query($redirectParams));
        exit;
    }

    // اگر project_id از فرم ارسال نشده بود، از رکورد دیتابیس خوانده شود
    if ($projectId <= 0 && !empty($remittance['project_id'])) {
        $projectId = (int)$remittance['project_id'];
    }

    // حذف رکورد
    $stmtDelete = $pdo->prepare('DELETE FROM `remittance_output` WHERE `id` = ? LIMIT 1');
    $stmtDelete->execute([$remitanceOutId]);

    // تهیه عنوان و پیام موفقیت
    $titleParts = [];
    if (!empty($remittance['document'])) {
        $titleParts[] = "شماره سند {$remittance['document']}";
    }
    if (!empty($remittance['beneficiary'])) {
        $titleParts[] = "به نام {$remittance['beneficiary']}";
    }
    $titlePart = !empty($titleParts) ? implode(' - ', $titleParts) : "شماره {$remitanceOutId}";

    $successMessage = "حواله {$titlePart} با موفقیت حذف گردید.";
    $_SESSION['flash_success'] = $successMessage;

    if ($isAjax) {
        echo json_encode([
            'success'    => true,
            'message'    => $successMessage,
            'project_id' => $projectId,
            'id'         => $remitanceOutId
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $redirectParams = ['tab' => $targetTab, 'status' => 'deleted'];
    if ($projectId > 0) $redirectParams['project_id'] = $projectId;
    header('Location: dashboardAdmin.php?' . http_build_query($redirectParams));
    exit;

} catch (PDOException $e) {
    error_log('Error in delete_remitance_out: ' . $e->getMessage());

    if ($isAjax) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'خطای دیتابیس در حذف حواله.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $redirectParams = ['tab' => $targetTab, 'error' => 'db_error'];
    if ($projectId > 0) {
        $redirectParams['project_id'] = $projectId;
    }

    header('Location: dashboardAdmin.php?' . http_build_query($redirectParams));
    exit;
}
