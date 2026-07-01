<?php
// view_cheque.php
// تنظیمات دیتابیس
$DB_HOST = 'localhost';
$DB_NAME = 'maskansazan';
$DB_USER = 'root';
$DB_PASS = '';

require_once __DIR__ . '/helpers.php';

// تنظیمات امنیتی سشن
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// بررسی ورود کاربر
if (empty($_SESSION['user_id']) || empty($_SESSION['username'])) {
    header('Location: loginAdmin.php');
    exit;
}

// بررسی وجود ID چک
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('شناسه چک نامعتبر است');
}

$cheque_id = (int)$_GET['id'];

// ایجاد اتصال mysqli
$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($mysqli->connect_error) {
    die("Database connection error: " . $mysqli->connect_error);
}

// گرفتن اطلاعات چک
$stmt = $mysqli->prepare("
    SELECT * 
    FROM cheque_transactions 
    WHERE id = ?
");
$stmt->bind_param("i", $cheque_id);
$stmt->execute();
$result = $stmt->get_result();
$cheque = $result->fetch_assoc();
$stmt->close();

if (!$cheque) {
    die('چک مورد نظر یافت نشد');
}

// محاسبه وضعیت چک
$chequeDate = strtotime($cheque['cheque_date']);
$today = time();
$daysDiff = floor(($chequeDate - $today) / (60 * 60 * 24));

if ($chequeDate < $today) {
    $statusClass = 'badge-overdue';
    $statusText = 'معوقه (' . abs($daysDiff) . ' روز گذشته)';
    $statusIcon = '⏰';
} elseif ($daysDiff <= 7) {
    $statusClass = 'badge-upcoming';
    $statusText = 'نزدیک به سررسید (' . $daysDiff . ' روز مانده)';
    $statusIcon = '📅';
} else {
    $statusClass = 'badge-normal';
    $statusText = 'عادی (' . $daysDiff . ' روز مانده)';
    $statusIcon = '✅';
}

// بررسی وجود فیلدهای اختیاری و استفاده از مقادیر پیش‌فرض
$purpose = $cheque['purpose'] ?? '---';
$receiver_name = $cheque['receiver_name'] ?? '---';
$receiver_national_code = $cheque['receiver_national_code'] ?? '---';
$member_name = $cheque['member_name'] ?? '---';
$account_owner = $cheque['account_owner'] ?? '---';
$national_code = $cheque['national_code'] ?? '---';
$bank_name = $cheque['bank_name'] ?? '---';
$cheque_number = $cheque['cheque_number'] ?? '---';
$amount = $cheque['amount'] ?? 0;

$mysqli->close();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مشاهده چک - <?= htmlspecialchars($cheque_number) ?></title>

    <!-- Bootstrap RTL -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --primary-color: #4f46e5;
            --overdue-color: #ef4444;
            --upcoming-color: #3b82f6;
            --normal-color: #10b981;
        }

        body {
            background: linear-gradient(135deg, #0f172a, #1e293b);
            color: #e2e8f0;
            font-family: Tahoma, sans-serif;
            min-height: 100vh;
            padding: 20px;
        }

        .cheque-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            padding: 30px;
            margin: 20px auto;
            max-width: 800px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            border: 1px solid rgba(255,255,255,0.1);
        }

        .cheque-header {
            border-bottom: 2px solid rgba(255,255,255,0.2);
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .cheque-number {
            font-size: 2.5rem;
            font-weight: bold;
            background: linear-gradient(135deg, #4f46e5, #7c73e6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-align: center;
            margin-bottom: 10px;
        }

        .status-badge {
            padding: 8px 16px;
            border-radius: 25px;
            font-weight: bold;
            font-size: 1.1rem;
            display: inline-block;
            margin: 10px 0;
        }

        .badge-overdue {
            background: var(--overdue-color);
            color: white;
        }

        .badge-upcoming {
            background: var(--upcoming-color);
            color: white;
        }

        .badge-normal {
            background: var(--normal-color);
            color: white;
        }

        .info-section {
            margin-bottom: 25px;
            padding: 20px;
            background: rgba(255,255,255,0.03);
            border-radius: 15px;
            border-right: 4px solid var(--primary-color);
        }

        .info-title {
            color: #94a3b8;
            font-size: 0.9rem;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .info-value {
            color: #f1f5f9;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .amount-display {
            font-size: 2rem;
            font-weight: bold;
            color: #fbbf24;
            text-align: center;
            margin: 20px 0;
            padding: 15px;
            background: rgba(251, 191, 36, 0.1);
            border-radius: 15px;
            border: 2px solid rgba(251, 191, 36, 0.3);
        }

        .action-buttons {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        .btn-print {
            background: linear-gradient(135deg, #4f46e5, #7c73e6);
            border: none;
            padding: 10px 25px;
            border-radius: 10px;
            color: white;
            font-weight: 600;
        }

        .btn-back {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            padding: 10px 25px;
            border-radius: 10px;
            color: #e2e8f0;
            font-weight: 600;
        }

        .section-title {
            color: #94a3b8;
            font-size: 1rem;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .row-gap {
            margin-bottom: 15px;
        }

        .empty-value {
            color: #94a3b8;
            font-style: italic;
        }

        @media print {
            body {
                background: white;
                color: black;
            }

            .cheque-card {
                background: white;
                color: black;
                box-shadow: none;
                border: 1px solid #ddd;
            }

            .btn-print, .btn-back {
                display: none;
            }

            .info-section {
                background: #f8f9fa;
                border: 1px solid #dee2e6;
            }

            .info-title {
                color: #6c757d;
            }

            .info-value {
                color: #212529;
            }
        }

        @media (max-width: 768px) {
            .cheque-card {
                margin: 10px;
                padding: 20px;
            }

            .cheque-number {
                font-size: 2rem;
            }

            .amount-display {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="cheque-card">
        <!-- هدر چک -->
        <div class="cheque-header text-center">
            <h1 class="cheque-number">چک شماره: <?= htmlspecialchars($cheque_number) ?></h1>
            <div class="status-badge <?= $statusClass ?>">
                <?= $statusIcon ?> <?= $statusText ?>
            </div>
        </div>

        <!-- نمایش مبلغ -->
        <div class="amount-display">
            مبلغ: <?= number_format($amount) ?> تومان
        </div>

        <div class="row">
            <!-- اطلاعات اصلی چک -->
            <div class="col-md-6">
                <div class="info-section">
                    <h3 class="section-title">📋 اطلاعات اصلی چک</h3>

                    <div class="row-gap">
                        <div class="info-title">شماره چک</div>
                        <div class="info-value"><?= htmlspecialchars($cheque_number) ?></div>
                    </div>

                    <div class="row-gap">
                        <div class="info-title">تاریخ چک</div>
                        <div class="info-value">
                            <?= date('Y/m/d', $chequeDate) ?>
                        </div>
                    </div>

                    <div class="row-gap">
                        <div class="info-title">بانک صادرکننده</div>
                        <div class="info-value">
                            <?= $bank_name !== '---' ? htmlspecialchars($bank_name) : '<span class="empty-value">تعیین نشده</span>' ?>
                        </div>
                    </div>

                    <div class="row-gap">
                        <div class="info-title">هدف پرداخت</div>
                        <div class="info-value">
                            <?= $purpose !== '---' ? htmlspecialchars($purpose) : '<span class="empty-value">تعیین نشده</span>' ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- اطلاعات صاحب حساب -->
            <div class="col-md-6">
                <div class="info-section">
                    <h3 class="section-title">👤 اطلاعات صاحب حساب</h3>

                    <div class="row-gap">
                        <div class="info-title">نام صاحب حساب</div>
                        <div class="info-value">
                            <?= $account_owner !== '---' ? htmlspecialchars($account_owner) : '<span class="empty-value">تعیین نشده</span>' ?>
                        </div>
                    </div>

                    <div class="row-gap">
                        <div class="info-title">کد ملی</div>
                        <div class="info-value">
                            <?= $national_code !== '---' ? htmlspecialchars($national_code) : '<span class="empty-value">تعیین نشده</span>' ?>
                        </div>
                    </div>

                    <div class="row-gap">
                        <div class="info-title">نام عضو</div>
                        <div class="info-value">
                            <?= $member_name !== '---' ? htmlspecialchars($member_name) : '<span class="empty-value">تعیین نشده</span>' ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- اطلاعات دریافت کننده -->
        <div class="row">
            <div class="col-12">
                <div class="info-section">
                    <h3 class="section-title">🎯 اطلاعات دریافت کننده</h3>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="row-gap">
                                <div class="info-title">نام دریافت کننده</div>
                                <div class="info-value">
                                    <?= $receiver_name !== '---' ? htmlspecialchars($receiver_name) : '<span class="empty-value">تعیین نشده</span>' ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="row-gap">
                                <div class="info-title">کد ملی دریافت کننده</div>
                                <div class="info-value">
                                    <?= $receiver_national_code !== '---' ? htmlspecialchars($receiver_national_code) : '<span class="empty-value">تعیین نشده</span>' ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- وضعیت فعلی -->
        <div class="row">
            <div class="col-12">
                <div class="info-section">
                    <h3 class="section-title">📊 وضعیت فعلی</h3>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="row-gap">
                                <div class="info-title">روزهای باقی مانده</div>
                                <div class="info-value">
                                    <?php if ($daysDiff > 0): ?>
                                        <span class="text-success"><?= $daysDiff ?> روز</span>
                                    <?php else: ?>
                                        <span class="text-danger"><?= abs($daysDiff) ?> روز گذشته</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="row-gap">
                                <div class="info-title">وضعیت</div>
                                <div class="info-value">
                                        <span class="status-badge <?= $statusClass ?>" style="font-size: 0.9rem;">
                                            <?= $statusIcon ?> <?= $statusText ?>
                                        </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row-gap">
                        <div class="info-title">شناسه چک در سیستم</div>
                        <div class="info-value">#<?= $cheque_id ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- دکمه‌های عملیاتی -->
        <div class="action-buttons text-center">
            <button onclick="window.print()" class="btn btn-print me-3">
                <i class="bi bi-printer me-2"></i>چاپ اطلاعات
            </button>
            <button onclick="window.close()" class="btn btn-back">
                <i class="bi bi-x-circle me-2"></i>بستن
            </button>
            <a href="dashboardAdmin.php?tab=cheques" class="btn btn-back ms-3">
                <i class="bi bi-arrow-right me-2"></i>بازگشت به لیست
            </a>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // اضافه کردن قابلیت کلیدهای میانبر
    document.addEventListener('keydown', function(e) {
        // کلید Escape برای بستن پنجره
        if (e.key === 'Escape') {
            window.close();
        }
        // کلید P برای چاپ
        if (e.ctrlKey && e.key === 'p') {
            e.preventDefault();
            window.print();
        }
    });

    // نمایش پیام راهنما
    console.log('راهنما: برای چاپ از Ctrl+P استفاده کنید. برای بستن از Escape استفاده کنید.');
</script>
</body>
</html>