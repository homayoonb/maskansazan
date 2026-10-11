<?php
// admin/view_remitance_out.php
declare(strict_types=1);

session_start();

// بررسی نشست ورود
if (empty($_SESSION['admin_id']) && empty($_SESSION['admin_username'])) {
    http_response_code(403);
    die('<div class="alert alert-danger mb-0">دسترسی غیرمجاز است. لطفاً مجدداً وارد سیستم شوید.</div>');
}

$remitance_out_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$project_id       = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
$isAjax           = isset($_GET['ajax']) && $_GET['ajax'] === '1';

if ($remitance_out_id <= 0) {
    http_response_code(400);
    die('<div class="alert alert-warning mb-0"><i class="bi bi-exclamation-triangle-fill me-2"></i>شناسه حواله نامعتبر است.</div>');
}

if (!function_exists('toShamsi')) {
    function toShamsi($dateStr): string {
        if (empty($dateStr) || $dateStr === '0000-00-00') {
            return '---';
        }

        // اگر تاریخ در دیتابیس از قبل با فرمت شمسی ذخیره شده باشد (مثلاً 1403/02/15)
        if (preg_match('/^1[34]\d{2}\/\d{1,2}\/\d{1,2}$/', (string)$dateStr)) {
            return (string)$dateStr;
        }

        $timestamp = strtotime((string)$dateStr);
        if (!$timestamp) {
            return (string)$dateStr;
        }

        if (class_exists('IntlDateFormatter')) {
            $formatter = new IntlDateFormatter(
                    'fa_IR@calendar=persian',
                    IntlDateFormatter::FULL,
                    IntlDateFormatter::NONE,
                    'Asia/Tehran',
                    IntlDateFormatter::TRADITIONAL,
                    'yyyy/MM/dd'
            );
            return (string)$formatter->format($timestamp);
        }

        return date('Y/m/d', $timestamp);
    }
}

try {
    $pdo = new PDO(
            "mysql:host=localhost;dbname=maskanhb_maskansazan;charset=utf8mb4",
            "root",
            '',
            [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
    );

    $sql = "
        SELECT e.*, p.name AS project_name 
        FROM `remittance_output` AS e
        LEFT JOIN `projects` AS p ON e.project_id = p.id
        WHERE e.id = :id
    ";
    $params = [':id' => $remitance_out_id];

    if ($project_id > 0) {
        $sql .= " AND e.project_id = :project_id";
        $params[':project_id'] = $project_id;
    }
    $sql .= " LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $remitance_out = $stmt->fetch();

    if (!$remitance_out) {
        die('<div class="alert alert-info mb-0"><i class="bi bi-info-circle me-2"></i>اطلاعات حواله یافت نشد.</div>');
    }

} catch (PDOException $e) {
    http_response_code(500);
    die('<div class="alert alert-danger mb-0"><i class="bi bi-x-circle-fill me-2"></i>خطای دیتابیس: ' . htmlspecialchars($e->getMessage()) . '</div>');
}

// مقدار مبلغ حواله (بررسی harvest، cost یا amount)
$rawAmount = $remitance_out['harvest'] ?? $remitance_out['cost'] ?? $remitance_out['amount'] ?? $remitance_out['diposit'] ?? null;
$formattedAmount = '---';

if ($rawAmount !== null && $rawAmount !== '') {
    // تبدیل ارقام فارسی به انگلیسی و حذف کاراکترهای اضافی به جز عدد و نقطه
    $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    $cleanStr = str_replace($persian, $english, (string)$rawAmount);
    $cleanStr = preg_replace('/[^\d.]/', '', $cleanStr);

    if ($cleanStr !== '' && is_numeric($cleanStr)) {
        $num = (float)$cleanStr;

        // فرمت‌دهی با ۳ رقم اعشار و حذف صفرهای زائد انتهای اعشار
        $formatted = number_format($num, 3, '.', ',');
        $formatted = rtrim(rtrim($formatted, '0'), '.');

        // در صورت صفر بودن مقدار
        if ($formatted === '') {
            $formatted = '0';
        }

        $formattedAmount = '<span class="font-monospace fw-bold text-success fs-5">' . htmlspecialchars($formatted) . '</span> <small class="text-muted">ریال</small>';
    }
}
// فرمت‌دهی مبلغ مانده (remainder)
$rawRemainder = $remitance_out['remainder'] ?? null;
$formattedRemainder = '---';

if ($rawRemainder !== null && $rawRemainder !== '') {
    $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    $cleanRemainder = str_replace($persian, $english, (string)$rawRemainder);
    $cleanRemainder = preg_replace('/[^\d.]/', '', $cleanRemainder);

    if ($cleanRemainder !== '' && is_numeric($cleanRemainder)) {
        $remNum = (float)$cleanRemainder;
        $formattedRem = number_format($remNum, 3, '.', ',');
        $formattedRem = rtrim(rtrim($formattedRem, '0'), '.');
        if ($formattedRem === '') {
            $formattedRem = '0';
        }
        $formattedRemainder = '<span class="font-monospace fw-bold text-dark">' . htmlspecialchars($formattedRem) . '</span> <small class="text-muted">ریال</small>';
    }
}


// استخراج ستون شبا/کارت با در نظر گرفتن کاراکتر اسلش
$shebaCart = $remitance_out['sheba/cart'] ?? $remitance_out['sheba_cart'] ?? '---';
?>

<?php if (!$isAjax): ?>
    <!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>حواله شماره <?= htmlspecialchars((string)($remitance_out['document'] ?? $remitance_out['id'] ?? '---')) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body class="bg-light p-4">
<div class="container bg-white p-4 rounded shadow-sm" style="max-width: 850px;">
    <?php endif; ?>

    <!-- بدنه چک برای مودال -->
    <div class="remittance-container">
        <!-- هدر سرفصل و زیرفصل -->
        <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">
            <div>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 fs-6">
                <i class="bi bi-tag me-1"></i> سرفصل: <?= htmlspecialchars((string)($remitance_out['transaction'] ?: '---')) ?>
            </span>
            </div>
            <div class="text-muted small">
                <i class="bi bi-folder2-open me-1"></i> زیرفصل:
                <strong class="text-dark"><?= htmlspecialchars((string)($remitance_out['subchapter'] ?: '---')) ?></strong>
            </div>
        </div>

        <div class="row g-3">
            <!-- شماره سند / پیگیری -->
            <div class="col-md-6">
                <div class="p-3 bg-light rounded border h-100">
                    <div class="text-muted small">سند:</div>
                    <div class="fw-bold text-dark mt-1 font-monospace" dir="ltr">
                        <?= htmlspecialchars((string)($remitance_out['document'] ?: '---')) ?>
                    </div>
                </div>
            </div>

            <!-- مبلغ حواله -->
            <div class="col-md-6">
                <div class="p-3 bg-light rounded border h-100">
                    <div class="text-muted small">مبلغ برداشت:</div>
                    <div class="fw-bold text-success mt-1" dir="ltr">
                        <?= $formattedAmount ?>
                    </div>
                </div>
            </div>

            <!-- ذینفع -->
            <div class="col-md-6">
                <div class="p-3 bg-light rounded border h-100">
                    <div class="text-muted small">ذینفع:</div>
                    <div class="fw-bold text-primary mt-1">
                        <i class="bi bi-person me-1"></i>
                        <?= htmlspecialchars((string)($remitance_out['beneficiary'] ?: '---')) ?>
                    </div>
                </div>
            </div>

            <!-- تاریخ حواله -->
            <div class="col-md-6">
                <div class="p-3 bg-light rounded border h-100">
                    <div class="text-muted small">تاریخ :</div>
                    <div class="fw-bold text-dark mt-1 font-monospace">
                        <?= htmlspecialchars(toShamsi($remitance_out['history'] ?? '')) ?>
                    </div>
                </div>
            </div>

            <!-- بانک  -->
            <div class="col-md-6">
                <div class="p-3 bg-light rounded border h-100">
                    <div class="text-muted small">بانک:</div>
                    <div class="fw-bold text-dark mt-1">
                        <i class="bi bi-bank me-1 text-secondary"></i>
                        <?= htmlspecialchars((string)($remitance_out['bank/hesab'] ?: '---')) ?>
                    </div>
                </div>
            </div>

            <!-- شماره شبا / کارت -->
            <div class="col-md-6">
                <div class="p-3 bg-light rounded border h-100">
                    <div class="text-muted small">شماره شبا / کارت:</div>
                    <div class="fw-bold text-dark mt-1 font-monospace" dir="ltr">
                        <?= htmlspecialchars((string)($remitance_out['sheba/cart'] ?? $remitance_out['sheba_cart'] ?? '---')) ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="p-3 bg-light rounded border h-100">
                    <div class="text-muted small">کد شعبه:</div>
                    <div class="fw-bold text-dark mt-1 font-monospace" dir="ltr">
                        <?= htmlspecialchars((string)($remitance_out['branch_code'] ?? $remitance_out['branch_code'] ?? '---')) ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="p-3 bg-light rounded border h-100">
                    <div class="text-muted small"> شعبه:</div>
                    <div class="fw-bold text-dark mt-1 font-monospace" dir="ltr">
                        <?= htmlspecialchars((string)($remitance_out['branch'] ?? '---')) ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="p-3 bg-light rounded border h-100">
                    <div class="text-muted small"> ساعت:</div>
                    <div class="fw-bold text-dark mt-1 font-monospace" dir="ltr">
                        <?= htmlspecialchars((string)($remitance_out['clock'] ?? '---')) ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="p-3 bg-light rounded border h-100">
                    <div class="text-muted small"> شماره قبض:</div>
                    <div class="fw-bold text-dark mt-1 font-monospace" dir="ltr">
                        <?= htmlspecialchars((string)($remitance_out['invoice_number'] ?? '---')) ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="p-3 bg-light rounded border h-100">
                    <div class="text-muted small"> واریز:</div>
                    <div class="fw-bold text-dark mt-1 font-monospace" dir="ltr">
                        <?= htmlspecialchars((string)($remitance_out['diposit'] ?? '---')) ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="p-3 bg-light rounded border h-100">
                    <div class="text-muted small"> مانده:</div>
                    <div class="fw-bold text-dark mt-1 font-monospace" dir="ltr">
                        <?= $formattedRemainder ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="p-3 bg-light rounded border h-100">
                    <div class="text-muted small">بابت:</div>
                    <div class="fw-bold text-dark mt-1 font-monospace" dir="ltr">
                        <?= htmlspecialchars((string)($remitance_out['behalf'] ?? '---')) ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="p-3 bg-light rounded border h-100">
                    <div class="text-muted small">ورودی/خروجی:</div>
                    <div class="fw-bold text-dark mt-1 font-monospace" dir="ltr">
                        <?= htmlspecialchars((string)($remitance_out['voroodi/khoroujy'] ?? '---')) ?>
                    </div>
                </div>
            </div>

            <!-- نام پروژه -->
            <div class="col-md-12">
                <div class="p-3 bg-light rounded border">
                    <div class="text-muted small">پروژه:</div>
                    <div class="fw-bold text-dark mt-1">
                        <i class="bi bi-building me-1 text-primary"></i>
                        <?= htmlspecialchars((string)($remitance_out['project_name'] ?: ($remitance_out['project'] ?: '---'))) ?>
                    </div>
                </div>
            </div>

            <!-- شرح و توضیحات -->
            <div class="col-12">
                <div class="p-3 bg-light rounded border">
                    <div class="text-muted small">شرح و توضیحات:</div>
                    <div class="text-dark mt-1 text-wrap" style="white-space: pre-line;">
                        <?= !empty($remitance_out['description'])
                                ? nl2br(htmlspecialchars((string)$remitance_out['description']))
                                : '<span class="text-muted small">توضیحاتی ثبت نشده است.</span>'
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <?php if (!$isAjax): ?>
</div>
</body>
</html>
<?php endif; ?>
