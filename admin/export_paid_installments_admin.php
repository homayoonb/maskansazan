<?php
session_start();

if (empty($_SESSION['admin_id']) || empty($_SESSION['admin_username'])) {
    header('Location: loginAdmin.php');
    exit;
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/jdf.php';

$user_project_id = isset($_GET['user_project_id']) ? (int)$_GET['user_project_id'] : 0;

if ($user_project_id <= 0) {
    die("شناسه کاربر معتبر نیست.");
}

try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=maskanhb_maskansazan;charset=utf8mb4",
        "root",
        "",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    // دریافت اطلاعات کاربر
    $stmt = $pdo->prepare("SELECT * FROM users_project WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $user_project_id]);
    $member = $stmt->fetch();

    if (!$member) {
        die("عضو مورد نظر یافت نشد.");
    }

    $nationalCode = $member['national_code'] ?? '';
    $memberName   = $member['full_name'] ?? ($member['member_name'] ?? '---');

    // دریافت تراکنش‌ها
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

    $total_paid = 0;
    $total_installment_amount = 0;

    foreach ($rows as $row) {
        $total_paid += (float)($row['paid_amount'] ?? 0);
        $total_installment_amount += (float)($row['installment_amount'] ?? 0);
    }

// پاکسازی رشته مبلغ ثابت مصوب و تبدیل به عدد شناور جهت محاسبات
    $target_installment_amount = (float)str_replace(',', '', '16,500,000,000');

// محاسبه مابه‌التفاوت مطابق با منطق داشبورد
    $items = $target_installment_amount - $total_paid;

// تعیین کسری، اضافه پرداخت و وضعیت نهایی
    if ($items > 0) {
        // مبلغ مصوب از پرداختی بیشتر است => کسری پرداخت داریم
        $deficit = $items;
        $surplus = 0;
        $paymentStatus = 'بدهکار';
    } elseif ($items < 0) {
        // پرداختی از مبلغ مصوب بیشتر است => اضافه پرداخت داریم
        $deficit = 0;
        $surplus = abs($items);
        $paymentStatus = 'بستانکار';
    } else {
        // مابه‌التفاوت صفر است => تسویه شده
        $deficit = 0;
        $surplus = 0;
        $paymentStatus = 'تسویه شده';
    }


    $html = '
    <html lang="fa" dir="rtl">
    <head>
        <meta charset="UTF-8">
        <style>
            body {
                font-family: "dejavusans", sans-serif;
                direction: rtl;
                text-align: right;
                font-size: 11px;
                color: #222;
            }
            h2 {
                text-align: center;
                margin: 0;
                font-size: 16px;
                color: #111;
            }
            h4 {
                text-align: center;
                margin: 5px 0 15px 0;
                font-size: 12px;
                color: #666;
            }
            .info-table {
                width: 100%;
                margin-bottom: 15px;
                border: none;
            }
            .info-table td {
                border: none;
                padding: 4px 0;
            }
            .summary-table {
                width: 100%;
                border-collapse: collapse;
                margin: 10px 0 20px 0;
            }
            .summary-table td {
                border: 1px solid #ccc;
                padding: 10px;
                text-align: center;
                font-size: 12px;
                font-weight: bold;
            }
            .bg-green {
                background-color: #eafaf1;
                color: #198754;
            }
            .bg-red {
                background-color: #fdeaea;
                color: #dc3545;
            }
            .bg-blue {
                background-color: #eaf2ff;
                color: #0d6efd;
            }
            .bg-yellow {
                background-color: #fff8e1;
                color: #b8860b;
            }
            .bg-gray {
                background-color: #f5f5f5;
                color: #444;
            }
            table.data-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 10px;
            }
            table.data-table th, table.data-table td {
                border: 1px solid #ccc;
                padding: 8px;
                text-align: center;
            }
            table.data-table th {
                background-color: #f5f5f5;
                font-weight: bold;
            }
        </style>
    </head>
    <body>
        <h2>گزارش اقساط پرداخت‌شده</h2>
        <h4>پنل مدیریت</h4>

        <table class="info-table">
            <tr>
                <td><strong>نام عضو:</strong> ' . htmlspecialchars($memberName) . '</td>
                <td><strong>کد ملی:</strong> ' . htmlspecialchars($nationalCode) . '</td>
                <td style="text-align: left;"><strong>تاریخ گزارش:</strong> ' . jdate('Y/m/d') . '</td>
            </tr>
        </table>

        <table class="summary-table">
            <tr>
                <td class="bg-green">جمع کل واریزی<br>' . number_format($total_paid) . ' ریال</td>
                <td class="bg-yellow">جمع اقساط تا تاریخ ۱۴۰۵/۰۲/۱۵<br>' . number_format($target_installment_amount) . ' ریال</td>
                <td class="bg-red">بدهکار<br>' . number_format($deficit) . ' ریال</td>
                <td class="bg-blue">بستانکار<br>' . number_format($surplus) . ' ریال</td>
                <td class="bg-gray">وضعیت<br>' . $paymentStatus . '</td>
            </tr>
        </table>


        <table class="data-table">
            <thead>
                <tr>
                    <th width="5%">ردیف</th>
                    <th width="15%">نام عضو</th>
                    <th width="15%">عنوان قسط</th>
                    <th width="12%">تاریخ قسط</th>
                    <th width="15%">مبلغ قسط (ریال)</th>
                    <th width="15%">مبلغ پرداختی (ریال)</th>
                    <th width="11%">روش پرداخت</th>
                    <th width="12%">توضیحات</th>
                    <th width="12%">تاریخ واریز</th>
                </tr>
            </thead>
            <tbody>';
    if (!empty($rows)) {
        foreach ($rows as $i => $row) {
            $html .= '
                <tr>
                    <td>' . ($i + 1) . '</td>
                    <td>' . htmlspecialchars($row['member_name'] ?? '') . '</td>
                    <td>' . htmlspecialchars($row['installment_title'] ?? '') . '</td>
                    <td>' . htmlspecialchars($row['installment_date'] ?? '') . '</td>
                    <td>' . number_format((float)($row['installment_amount'] ?? 0)) . '</td>
                    <td>' . number_format((float)($row['paid_amount'] ?? 0)) . '</td>
                    <td>' . htmlspecialchars($row['payment_method'] ?? '---') . '</td>
                    <td>' . htmlspecialchars($row['description'] ?? '---') . '</td>
                    <td>' . htmlspecialchars($row['Deposit_date'] ?? '---') . '</td>
                </tr>';
        }
    } else {
        $html .= '<tr><td colspan="9">هیچ اطلاعاتی یافت نشد.</td></tr>';
    }

    $html .= '
            </tbody>
        </table>
    </body>
    </html>';

    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4-L',
        'default_font' => 'dejavusans',
        'margin_top' => 12,
        'margin_bottom' => 12,
        'margin_left' => 10,
        'margin_right' => 10,
    ]);

    $mpdf->SetDirectionality('rtl');
    $mpdf->WriteHTML($html);

    $safeFileName = 'paid_installments_' . preg_replace('/[^0-9]/', '', $nationalCode) . '.pdf';
    $mpdf->Output($safeFileName, \Mpdf\Output\Destination::DOWNLOAD);
    exit;

} catch (Exception $e) {
    die("خطا در سیستم تولید PDF: " . $e->getMessage());
}
