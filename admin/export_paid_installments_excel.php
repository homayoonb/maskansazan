<?php
// export_paid_installments_excel.php
declare(strict_types=1);
session_start();

// ۱. بررسی دسترسی ادمین
if (empty($_SESSION['admin_id']) || empty($_SESSION['admin_username'])) {
    http_response_code(403);
    die("عدم دسترسی! لطفاً ابتدا وارد پنل مدیریت شوید.");
}

$user_project_id = isset($_GET['user_project_id']) ? (int)$_GET['user_project_id'] : 0;
$project_id      = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;

if ($user_project_id <= 0) {
    die("شناسه عضو نامعتبر است.");
}

// ۲. تابع تبدیل تاریخ و ساعت به شمسی عددی
if (!function_exists('toShamsiNow')) {
    function toShamsiNow($persianDigits = true) {
        try {
            $formatter = new IntlDateFormatter(
                'fa_IR@calendar=persian',
                IntlDateFormatter::FULL,
                IntlDateFormatter::FULL,
                'Asia/Tehran',
                IntlDateFormatter::TRADITIONAL,
                'yyyy/MM/dd HH:mm'
            );
            $formatted = $formatter->format(new DateTime('now', new DateTimeZone('Asia/Tehran')));

            if (!$persianDigits) {
                $fa = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
                $en = ['0','1','2','3','4','5','6','7','8','9'];
                return str_replace($fa, $en, $formatted);
            }
            return $formatted;
        } catch (Exception $e) {
            return date('Y-m-d H:i');
        }
    }
}

try {
    // ۳. اتصال به پایگاه داده
    $pdo = new PDO(
        "mysql:host=localhost;dbname=maskanhb_maskansazan;charset=utf8mb4",
        "root",
        "",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    // ۴. خواندن اطلاعات عضو از users_project
    $stmt = $pdo->prepare("SELECT * FROM users_project WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $user_project_id]);
    $member = $stmt->fetch();

    if (!$member) {
        die("عضو مورد نظر یافت نشد.");
    }

    $nationalCode = $member['national_code'];
    $memberName   = $member['full_name'] ?? $member['name'] ?? 'عضو';

    // ۵. دریافت اقساط پرداخت‌شده عضو بر اساس کد ملی
    $stmt2 = $pdo->prepare("
        SELECT * FROM member_statements 
        WHERE national_code = :national_code 
          AND installment_date IS NOT NULL
          AND installment_date != ''
          AND Deposit_date IS NOT NULL
          AND Deposit_date != ''
        ORDER BY installment_date ASC
    ");
    $stmt2->execute([':national_code' => $nationalCode]);
    $rows = $stmt2->fetchAll();

    // ۶. تنظیم هدرهای HTTP برای دانلود فایل اکسل
    $safeMemberName = preg_replace('/[^\p{L}\p{N}_]+/u', '_', (string)$memberName);
    $fileName = "اقساط_" . $safeMemberName . "_" . date('Ymd_His') . ".csv";

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');

    // درج BOM برای پشتیبانی کامل فارسی در اکسل
    fputs($output, "\xEF\xBB\xBF");

    // سطرهای عنوان، مشخصات و تاریخ صدور شمسی
    fputcsv($output, ['گزارش اقساط پرداخت‌شده عضو']);
    fputcsv($output, ['نام عضو:', $memberName, 'کد ملی:', (string)$nationalCode]);
    fputcsv($output, ['تاریخ و ساعت صدور:', toShamsiNow()]); // ✅ تاریخ صدور شمسی
    fputcsv($output, []); // سطر خالی جهت خوانایی

    // سرستون‌های جدول
    fputcsv($output, [
        'ردیف',
        'نام عضو',
        'عنوان قسط',
        'تاریخ قسط',
        'تاریخ واریز',
        'مبلغ قسط (ریال)',
        'مبلغ پرداختی (ریال)',
        'روش پرداخت',
        'توضیحات'
    ]);

    $totalPaid = 0;
    $totalInstallment = 0;

    // پیمایش سطرها
    foreach ($rows as $index => $row) {
        $instAmount = (float)($row['installment_amount'] ?? 0);
        $paidAmount = (float)($row['paid_amount'] ?? 0);

        $totalInstallment += $instAmount;
        $totalPaid        += $paidAmount;

        fputcsv($output, [
            $index + 1,
            $row['member_name'] ?? $memberName,
            $row['installment_title'] ?? '',
            $row['installment_date'] ?? '',
            $row['Deposit_date'] ?? '',
            $instAmount,
            $paidAmount,
            $row['payment_method'] ?? '---',
            $row['description'] ?? '---'
        ]);
    }

    // سطر جمع کل
    fputcsv($output, []);
    fputcsv($output, [
        'جمع کل',
        '',
        '',
        '',
        '',
        $totalInstallment,
        $totalPaid,
        'ریال',
        ''
    ]);

    // محاسبه وضعیت بدهکاری / بستانکاری (سقف ۱۶.۵ میلیارد ریال)
    $targetAmount = 16500000000;
    $diff = $targetAmount - $totalPaid;

    fputcsv($output, ['سقف مصوب اقساط:', $targetAmount, 'ریال']);
    if ($diff > 0) {
        fputcsv($output, ['وضعیت حساب:', 'بدهکار (کسری پرداخت): ' . $diff . ' ریال']);
    } elseif ($diff < 0) {
        fputcsv($output, ['وضعیت حساب:', 'بستانکار (اضافه پرداخت): ' . abs($diff) . ' ریال']);
    } else {
        fputcsv($output, ['وضعیت حساب:', 'تسویه کامل']);
    }

    fclose($output);
    exit;

} catch (PDOException $e) {
    die("خطای پایگاه داده: " . $e->getMessage());
}
