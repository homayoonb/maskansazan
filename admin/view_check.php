<?php
// admin/view_check.php
declare(strict_types=1);
session_start();

$check_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
$isAjax = isset($_GET['ajax']) && $_GET['ajax'] == '1';

if ($check_id <= 0) {
    die('<div class="alert alert-warning mb-0">شناسه چک نامعتبر است.</div>');
}

if (!function_exists('toShamsi')) {
    function toShamsi($dateStr) {
        if (empty($dateStr) || $dateStr === '0000-00-00') {
            return '---';
        }

        // در صورتی که خود دیتابیس تاریخ شمسی مثل 1402/05/10 ذخیره کرده باشد
        if (preg_match('/^1[34]\d{2}\/\d{1,2}\/\d{1,2}$/', (string)$dateStr)) {
            return $dateStr;
        }

        $timestamp = strtotime((string)$dateStr);
        if (!$timestamp) {
            return (string)$dateStr;
        }

        // اگر اکستنشن intl فعال باشد
        if (class_exists('IntlDateFormatter')) {
            $formatter = new IntlDateFormatter(
                    'fa_IR@calendar=persian',
                    IntlDateFormatter::FULL,
                    IntlDateFormatter::NONE,
                    'Asia/Tehran',
                    IntlDateFormatter::TRADITIONAL,
                    'yyyy/MM/dd'
            );
            return $formatter->format($timestamp);
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
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
    );

    $sql = "
        SELECT e.*, p.name AS project_name 
        FROM `check_output` AS e
        LEFT JOIN `projects` AS p ON e.project_id = p.id
        WHERE e.id = :id
    ";
    $params = [':id' => $check_id];

    if ($project_id > 0) {
        $sql .= " AND e.project_id = :project_id";
        $params[':project_id'] = $project_id;
    }
    $sql .= " LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $check = $stmt->fetch();

    if (!$check) {
        die('<div class="alert alert-info mb-0">اطلاعات چک یافت نشد.</div>');
    }

} catch (PDOException $e) {
    die('<div class="alert alert-danger mb-0">خطای اتصال به دیتابیس: ' . htmlspecialchars($e->getMessage()) . '</div>');
}

// فرمت دهی مبلغ
$formattedAmount = !empty($check['check_amount']) && is_numeric($check['check_amount'])
        ? number_format((float)$check['check_amount']) . ' <small class="text-muted">ریال</small>'
        : htmlspecialchars((string)($check['check_amount'] ?? '---'));
?>

<?php if (!$isAjax): ?>
    <!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>چک شماره <?= htmlspecialchars((string)($check['check_number'] ?? $check['id'])) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body class="bg-light p-4">
<div class="container bg-white p-4 rounded shadow-sm" style="max-width: 850px;">
    <?php endif; ?>

    <!-- بدنه چک برای مودال -->
    <div class="check-container">
        <!-- هدر سرفصل و زیرفصل -->
        <div class="d-flex justify-content-between align-items-center border-bottom pb-3 mb-3">
            <div>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 fs-6">
                    <i class="bi bi-tag me-1"></i> سرفصل: <?= htmlspecialchars((string)($check['transaction'] ?: '---')) ?>
                </span>
            </div>
            <div class="text-muted small">
                <i class="bi bi-folder2-open me-1"></i> زیرفصل:
                <strong class="text-dark"><?= htmlspecialchars((string)($check['subchapter'] ?: '---')) ?></strong>
            </div>
        </div>

        <div class="row g-3">
            <!-- بابت -->
            <div class="col-md-6">
                <div class="p-3 bg-light rounded border h-100">
                    <div class="text-muted small">بابت:</div>
                    <div class="fw-bold text-dark mt-1"><?= htmlspecialchars((string)($check['behalf'] ?: '---')) ?></div>
                </div>
            </div>

            <!-- دریافت کننده -->
            <div class="col-md-6">
                <div class="p-3 bg-light rounded border h-100">
                    <div class="text-muted small">دریافت کننده:</div>
                    <div class="fw-bold text-dark mt-1">
                        <?= htmlspecialchars((string)($check['recipient'] ?: '---')) ?>
                    </div>
                </div>
            </div>

            <!-- کدملی دریافت کننده -->
            <div class="col-md-6">
                <div class="p-3 bg-light rounded border h-100">
                    <div class="text-muted small">کد ملی / شناسه ملی دریافت‌کننده:</div>
                    <div class="fw-bold text-dark mt-1" dir="ltr">
                        <?= htmlspecialchars((string)($check["recipient's_national_iD"] ?: '---')) ?>
                    </div>
                </div>
            </div>

            <!-- مبلغ چک -->
            <div class="col-md-6">
                <div class="p-3 bg-light rounded border h-100">
                    <div class="text-muted small">مبلغ چک:</div>
                    <div class="fw-bold text-success mt-1" dir="ltr">
                        <?= $formattedAmount ?>
                    </div>
                </div>
            </div>

            <!-- شماره چک -->
            <div class="col-md-6">
                <div class="p-3 bg-light rounded border h-100">
                    <div class="text-muted small"> شماره چک:</div>
                    <div class="fw-bold text-dark mt-1" dir="ltr">
                        <?= htmlspecialchars((string)($check['check_number'] ?: '---')) ?>
                    </div>
                </div>
            </div>

            <!-- بانک -->
            <div class="col-md-6">
                <div class="p-3 bg-light rounded border h-100">
                    <div class="text-muted small">بانک :</div>
                    <div class="fw-bold text-dark mt-1">
                        <?= htmlspecialchars((string)($check['bank'] ?: '---')) ?>
                    </div>
                </div>
            </div>

            <!-- تاریخ صدور -->
            <div class="col-md-6">
                <div class="p-3 bg-light rounded border h-100">
                    <div class="text-muted small">تاریخ صدور چک:</div>
                    <div class="fw-bold text-dark mt-1">
                        <?= htmlspecialchars(toShamsi($check['check_issuance_date'])) ?>
                    </div>
                </div>
            </div>

            <!-- تاریخ سررسید -->
            <div class="col-md-6">
                <div class="p-3 bg-light rounded border h-100">
                    <div class="text-muted small">موعد  چک:</div>
                    <div class="fw-bold text-dark mt-1">
                        <?= htmlspecialchars(toShamsi($check['check_due_date'])) ?>
                    </div>
                </div>
            </div>

            <!-- وضعیت پاس شدن -->
            <div class="col-md-6">
                <div class="p-3 bg-light rounded border h-100">
                    <div class="text-muted small">وضعیت پاس چک:</div>
                    <div class="mt-1">
                        <?php
                        $status = trim((string)($check['passed/failed'] ?? ''));
                        if (in_array($status, ['پاس شد', 'پاس شده', 'وصول شده', 'passed'], true)):
                            ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                    <i class="bi bi-check-circle me-1"></i> پاس شد
                </span>
                        <?php elseif (in_array($status, ['پاس نشد', 'برگشت خورده', 'failed'], true)): ?>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">
                    <i class="bi bi-x-circle me-1"></i> پاس نشد
                </span>
                        <?php else: ?>
                            <span class="badge bg-warning-subtle text-dark border border-warning-subtle px-2 py-1">
                    <i class="bi bi-clock-history me-1"></i> <?= htmlspecialchars($status ?: 'نامشخص / در انتظار') ?>
                </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>


            <!-- محل تایید / خرج چک -->
            <div class="col-md-6">
                <div class="p-3 bg-light rounded border h-100">
                    <div class="text-muted small">از کجا پاس شد</div>
                    <div class="fw-bold text-dark mt-1">
                        <?= htmlspecialchars((string)($check['Where_did_it_get_approved'] ?: '---')) ?>
                    </div>
                </div>
            </div>

            <!-- نام پروژه -->
            <div class="col-md-12">
                <div class="p-3 bg-light rounded border">
                    <div class="text-muted small">پروژه :</div>
                    <div class="fw-bold text-primary mt-1">
                        <i class="bi bi-building me-1"></i>
                        <?= htmlspecialchars((string)($check['project_name'] ?: ($check['project'] ?: '---'))) ?>
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
