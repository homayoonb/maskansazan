<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: loginAdmin.php'); exit();
}

$nationalCode = $_SESSION['NationalCode'] ?? '';
if ($nationalCode === '') { die('کد ملی نامعتبر است'); }

$pdo = new PDO(
    "mysql:host=localhost;dbname=maskanhb_maskansazan;charset=utf8mb4",
    "root",
    "",
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);

$sql = "SELECT member_name, installment_title, installment_date,
               installment_amount, paid_amount, payment_method, description
        FROM member_statements
        WHERE national_code = :nc
          AND paid_amount > 0
        ORDER BY installment_date DESC, id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([':nc' => $nationalCode]);
$rows = $stmt->fetchAll();

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="paid_installments_'.$nationalCode.'.csv"');

// برای اینکه اکسل فارسی را درست باز کند:
echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');
fputcsv($out, ['نام عضو','عنوان قسط','تاریخ قسط','مبلغ قسط','مبلغ پرداختی','روش پرداخت','توضیحات']);

foreach ($rows as $r) {
    fputcsv($out, [
        $r['member_name'] ?? '',
        $r['installment_title'] ?? '',
        $r['installment_date'] ?? '',
        $r['installment_amount'] ?? '',
        $r['paid_amount'] ?? '',
        $r['payment_method'] ?? '',
        $r['description'] ?? '',
    ]);
}
fclose($out);
exit;
