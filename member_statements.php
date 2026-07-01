<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: groupmember.php'); exit();
}


$user = [
    'FullName'     => $_SESSION['full_name'] ?? '',
    'NationalCode' => $_SESSION['NationalCode'] ?? '',
    'MemberType'   => $_SESSION['main_member'] ?? '',
    'PhoneNumber'  => $_SESSION['phone'] ?? '',
    'ProjectShare' => $_SESSION['share'] ?? '',
    'Address' => $_SESSION['address'] ?? '',
];

try {
    $pdo = new PDO("mysql:host=localhost;dbname=maskanhb_maskansazan;charset=utf8", "root", "");
    $stmt = $pdo->query("SELECT * FROM member_statements where national_code = '$user[NationalCode]'");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_paid = 0; // متغیر برای ذخیره جمع کل
    foreach ($rows as $row) {
        $total_paid += $row['paid_amount']; // جمع زدن مقادیر در هر مرحله از حلقه
    }

} catch (PDOException $e) {
    die("خطا در اتصال: " . $e->getMessage());
}

?>
<style>
    .row {
        display: flex;
        flex-wrap: nowrap; /* جلوگیری از شکستن ردیف */
    }
    main {
        flex-grow: 1;
    }
    table thead tr th{
        text-align: right;
    }
    .bi-box {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 140px;
        padding: 8px 12px;
        border-radius: 10px;
        background: #eafaf1;
        border: 1px solid #b7ebc6;
    }

</style>

<div class="container-fluid">
    <div class="row">

        <!-- بخش محتوای اصلی -->
        <main class="col-md-9 col-lg-10 px-md-4">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="col-form-label">
                       جمع واریز شده ها
                    </label>
                </div>
                <div class="col-md-6">
                    <label class="col-form-label d-flex align-items-center gap-2 text-success fw-bold">
        <span class="bi-box">
            <?= number_format($total_paid) ?>
        </span>
                        <span>ریال</span>
                    </label>
                </div>


            </div>
            <div class="table-responsive mt-4">
                <table class="table table-bordered table-hover shadow-sm">
                    <thead class="table-light">
                    <tr style="text-align: left">
                        <th>ردیف</th>
                        <th>نام عضو</th>
                        <th>عنوان قسط</th>
                        <th>تاریخ قسط</th>
                        <th>مبلغ قسط (ریال)</th>
                        <th>مبلغ پرداختی (ریال)</th>
                        <th>روش پرداخت</th>
                        <th>توضیحات</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rows as $i=> $row): ?>
                        <tr>
                            <td><?= $i+1 ?></td>
                            <td><?= htmlspecialchars($row['member_name']) ?></td>
                            <td><?= htmlspecialchars($row['installment_title']) ?></td>
                            <td class="text-nowrap"><?= htmlspecialchars($row['installment_date']) ?></td>
                            <td><?= number_format($row['installment_amount']) ?></td>
                            <td><?= number_format($row['paid_amount']) ?></td>
                            <td><?= htmlspecialchars($row['payment_method'] ?? '---') ?></td>
                            <td><?= htmlspecialchars($row['description'] ?? '---') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</div>


