<?php
// add_check.php
try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=maskanhb_maskansazan;charset=utf8mb4",
        "root",
        '', // در محیط نهایی رمز را به env ببرید
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'خطا در اتصال به پایگاه داده']);
        exit;
    }
    die('<div class="alert alert-danger m-3">خطا در اتصال به پایگاه داده</div>');
}

// -------------------------------------------------------------
// ۲. توابع کمکی پاک‌سازی ورودی‌ها و مبالغ
// -------------------------------------------------------------
if (!function_exists('cleanInput')) {
    function cleanInput($data) {
        if ($data === null) return '';
        return trim((string)$data);
    }
}

if (!function_exists('cleanMoneyToInt')) {
    function cleanMoneyToInt($amount) {
        if (empty($amount)) return 0;

        // تبدیل ارقام فارسی و عربی به انگلیسی
        $persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $arabicDigits  = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $englishDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        $amount = str_replace($persianDigits, $englishDigits, (string)$amount);
        $amount = str_replace($arabicDigits, $englishDigits, $amount);

        // حذف کاما، فاصله و تمام کاراکترهای غیرعددی
        $cleaned = preg_replace('/[^\d]/', '', $amount);
        return !empty($cleaned) ? (float)$cleaned : 0;
    }
}

// -------------------------------------------------------------
// ۳. پردازش درخواست POST
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' ) {

    // تشخیص درخواست AJAX
    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

    try {
        if (!isset($pdo)) {
            throw new Exception("خطا در اتصال به پایگاه داده.");
        }

        $projectId = !empty($_POST['project_id']) ? (int)$_POST['project_id'] : null;

        // آماده‌سازی کوئری با 15 ستون و استفاده از Backtick برای ستون‌های خاص
// ۱. فعال‌سازی Emulate Prepares تا نام ستون‌های حاوی کاراکتر خاص توسط PDO دچار تداخل نشوند
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

        $subchapter          = $pdo->quote(cleanInput($_POST['subchapter'] ?? ''));
        $transaction         = $pdo->quote(cleanInput($_POST['transaction'] ?? ''));
        $cost                = $pdo->quote(cleanInput($_POST['cost'] ?? ''));
        $behalf              = $pdo->quote(cleanInput($_POST['behalf'] ?? ''));
        $recipient           = $pdo->quote(cleanInput($_POST['recipient'] ?? ''));
        $national_id         = $pdo->quote(cleanInput($_POST["recipient's_national_iD"] ?? ''));
        $check_amount        = (int) cleanMoneyToInt($_POST['check_amount'] ?? 0);
        $check_number        = $pdo->quote(cleanInput($_POST['check_number'] ?? ''));
        $check_issuance_date = !empty($_POST['check_issuance_date']) ? $pdo->quote(cleanInput($_POST['check_issuance_date'])) : "NULL";
        $check_due_date      = $pdo->quote(cleanInput($_POST['check_due_date'] ?? ''));
        $bank                = $pdo->quote(cleanInput($_POST['bank'] ?? ''));
        $project             = $pdo->quote(cleanInput($_POST['project'] ?? ''));
        $passed_failed       = $pdo->quote(cleanInput($_POST['passed/failed'] ?? 'پاس نشده'));
        $where_approved      = $pdo->quote(cleanInput($_POST['Where_did_it_get_approved?'] ?? ''));
        $projectIdSafe       = (int) $projectId;

        $sql = "INSERT INTO `check_output` (
            `subchapter`,
            `transaction`,
            `cost`,
            `behalf`,
            `recipient`,
            `recipient's_national_iD`,
            `check_amount`,
            `check_number`,
            `check_issuance_date`,
            `check_due_date`,
            `bank`,
            `project`,
            `passed/failed`,
            `Where_did_it_get_approved?`,
            `project_id`
        ) VALUES (
            $subchapter,
            $transaction,
            $cost,
            $behalf,
            $recipient,
            $national_id,
            $check_amount,
            $check_number,
            $check_issuance_date,
            $check_due_date,
            $bank,
            $project,
            $passed_failed,
            $where_approved,
            $projectIdSafe
        )";

        $pdo->exec($sql);



        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'message' => 'چک با موفقیت در سیستم ثبت شد.'
            ]);
            exit;
        } else {
            $redirectUrl = "dashboardAdmin.php?tab=check_out" . ($projectId ? "&project_id=" . $projectId : "") . "&msg=success";
            header("Location: " . $redirectUrl);
            exit;
        }

    } catch (PDOException $e) {
        $errorMsg = "خطای پایگاه داده: " . $e->getMessage();
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $errorMsg]);
            exit;
        } else {
            die($errorMsg);
        }
    } catch (Exception $e) {
        $errorMsg = "خطای سیستم: " . $e->getMessage();
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $errorMsg]);
            exit;
        } else {
            die($errorMsg);
        }
    }
}
