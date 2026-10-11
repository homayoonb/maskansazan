<?php
// add_remittance_out.php
declare(strict_types=1);

try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=maskanhb_maskansazan;charset=utf8mb4",
        "root",
        '', // در محیط نهایی رمز را از طریق env یا config فراخوانی کنید
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'خطا در اتصال به پایگاه داده']);
        exit;
    }
    die('<div class="alert alert-danger m-3">خطا در اتصال به پایگاه داده</div>');
}

// -------------------------------------------------------------
// توابع کمکی پاک‌سازی ورودی‌ها و نرمال‌سازی مبالغ/اعداد
// -------------------------------------------------------------
if (!function_exists('cleanInput')) {
    function cleanInput($data): string {
        if ($data === null) return '';
        return trim((string)$data);
    }
}

if (!function_exists('cleanMoneyToInt')) {
    function cleanMoneyToInt($amount): float {
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
// پردازش درخواست POST
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // تشخیص درخواست AJAX
    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

    try {
        $projectId = !empty($_POST['project_id']) ? (int)$_POST['project_id'] : null;

        // آماده‌سازی و پاک‌سازی داده‌ها
        $branch_code      = cleanInput($_POST['branch_code'] ?? '');
        $branch           = cleanInput($_POST['branch'] ?? '');
        $history          = cleanInput($_POST['history'] ?? '');
        $clock            = cleanInput($_POST['clock'] ?? '');
        $document         = cleanInput($_POST['document'] ?? '');
        $harvest          = cleanMoneyToInt($_POST['harvest'] ?? 0);
        $invoice_number   = cleanInput($_POST['invoice_number'] ?? '');
        $diposit          = cleanMoneyToInt($_POST['diposit'] ?? 0);
        $remainder        = cleanMoneyToInt($_POST['remainder'] ?? 0);
        $beneficiary      = cleanInput($_POST['beneficiary'] ?? '');
        $sheba_cart       = cleanInput($_POST['sheba/cart'] ?? '');
        $behalf           = cleanInput($_POST['behalf'] ?? '');
        $transaction      = cleanInput($_POST['transaction'] ?? '');
        $subchapter       = cleanInput($_POST['subchapter'] ?? '');
        $bank_hesab       = cleanInput($_POST['bank/hesab'] ?? '');
        $voroodi_khoroujy = cleanInput($_POST['voroodi/khoroujy'] ?? 'خروجی');
        $description      = cleanInput($_POST['description'] ?? '');

        // کوئری استاندارد با علامت سوال (?) و بکتیک (Backtick) برای جلوگیری از خطای کاراکترهای خاص
        $sql = "INSERT INTO `remittance_output` (
            `branch_code`,
            `branch`,
            `history`,
            `clock`,
            `document`,
            `harvest`,
            `invoice_number`,
            `diposit`,
            `remainder`,
            `beneficiary`,
            `sheba/cart`,
            `behalf`,
            `transaction`,
            `subchapter`,
            `bank/hesab`,
            `voroodi/khoroujy`,
            `description`,
            `project_id`
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $pdo->prepare($sql);

        // اجرای کوئری با تطبیق دقیق ترتیب ستون‌ها و مقادیر
        $stmt->execute([
            $branch_code,
            $branch,
            $history,
            $clock,
            $document,
            $harvest,
            $invoice_number,
            $diposit,
            $remainder,
            $beneficiary,
            $sheba_cart,
            $behalf,
            $transaction,
            $subchapter,
            $bank_hesab,
            $voroodi_khoroujy,
            $description,
            $projectId
        ]);

        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'message' => 'حواله با موفقیت در سیستم ثبت شد.'
            ]);
            exit;
        } else {
            $redirectUrl = "dashboardAdmin.php?tab=remitance_out" . ($projectId ? "&project_id=" . $projectId : "") . "&msg=success";
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
