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
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
<!-- در head صفحه -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
<style>
    *{
        direction: rtl;
    }
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


<div class="container-fluid" >
    <div class="row">

        <main class="col-md-9 col-lg-10 px-md-4">
            <div class="container-fluid px-3"> <!-- کانتینر برای محدود کردن عرض محتوا -->
                <!-- ردیف اول آمار (۳ ستون) -->
                <div class="row text-center mb-3 g-3">
                    <!-- جمع کل واریزی‌ها -->
                    <div class="col-lg-4 col-md-6">
                        <div class="p-3 border rounded bg-white shadow-sm">
                            <label class="fw-bold d-block text-secondary mb-2 small">جمع کل واریزی‌ها</label>
                            <span class="badge bg-light text-success fs-5 border border-success w-100 py-2">
                <?= number_format($total_paid ?? 0) ?> <small class="fs-6">ریال</small>
            </span>
                        </div>
                    </div>

                    <!-- پرداختی این عضو (در واقع همان جمع واریزی است، اما اگر منظور سقف است، مقدار ۱۶.۵ را نشان دهید) -->
                    <div class="col-lg-4 col-md-6">
                        <div class="p-3 border rounded bg-white shadow-sm">
                            <label class="fw-bold d-block text-secondary mb-2 small">پرداختی این عضو</label>
                            <span class="badge bg-light text-primary fs-5 border border-primary w-100 py-2">
                <?= number_format($total_paid ?? 0) ?> <small class="fs-6">ریال</small>
            </span>
                        </div>
                    </div>

                    <!-- جمع اقساط تا تاریخ -->
                    <div class="col-lg-4 col-md-6">
                        <div class="p-3 border rounded bg-white shadow-sm">
                            <?php
                            $target_amount = 16500000000; // ۱۶.۵ میلیارد ریال
                            ?>
                            <label class="fw-bold d-block text-secondary mb-2 small">جمع اقساط تا <span class="text-dark">۱۴۰۵/۰۲/۱۵</span></label>
                            <span class="badge bg-light text-warning fs-5 border border-warning text-dark w-100 py-2">
                <?= number_format($target_amount) ?> <small class="fs-6">ریال</small>
            </span>
                        </div>
                    </div>
                </div>

                <?php
                // محاسبه مابه‌التفاوت واقعی
                $diff = $target_amount - $total_paid;
                ?>

                <!-- ردیف دوم آمار و دکمه -->
                <div class="row text-center mb-4 g-3 align-items-end">

                    <?php if ($diff > 0): ?>
                        <!-- مابه‌التفاوت مثبت است یعنی بدهکار است -->
                        <div class="col-lg-4 col-md-6">
                            <div class="p-3 border rounded bg-white shadow-sm">
                                <label class="fw-bold d-block text-secondary mb-2 small">بدهکار (کسری پرداخت)</label>
                                <span class="badge bg-light text-danger fs-5 border border-danger w-100 py-2">
                    <?= number_format($diff) ?> <small class="fs-6">ریال</small>
                </span>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <div class="p-3 border rounded bg-white shadow-sm">
                                <label class="fw-bold d-block text-secondary mb-2 small">بستانکار (اضافه پرداخت)</label>
                                <span class="badge bg-light text-secondary fs-5 border border-secondary w-100 py-2">
                    0 <small class="fs-6">ریال</small>
                </span>
                            </div>
                        </div>

                    <?php elseif ($diff < 0): ?>
                        <!-- مابه‌التفاوت منفی است یعنی بستانکار است -->
                        <div class="col-lg-4 col-md-6">
                            <div class="p-3 border rounded bg-white shadow-sm">
                                <label class="fw-bold d-block text-secondary mb-2 small">بدهکار (کسری پرداخت)</label>
                                <span class="badge bg-light text-secondary fs-5 border border-secondary w-100 py-2">
                    0 <small class="fs-6">ریال</small>
                </span>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <div class="p-3 border rounded bg-white shadow-sm">
                                <label class="fw-bold d-block text-secondary mb-2 small">بستانکار (اضافه پرداخت)</label>
                                <span class="badge bg-light text-success fs-5 border border-success w-100 py-2">
                    <?= number_format(abs($diff)) ?> <small class="fs-6">ریال</small>
                </span>
                            </div>
                        </div>

                    <?php else: ?>
                        <!-- تسویه کامل -->
                        <div class="col-lg-8 col-md-12">
                            <div class="p-3 border rounded bg-light shadow-sm">
                                <span class="text-primary fw-bold">حساب عضو تسویه می‌باشد.</span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- دکمه خروجی PDF -->
                    <div class="col-lg-4 col-md-12">
                        <div class="p-2">
                            <a
                               class="btn btn-success fw-bold py-3 w-100 shadow-sm d-flex align-items-center justify-content-center">
                                <i class="bi bi-file-earmark-pdf fs-5 me-2"></i> خروجی اقساط پرداخت‌شده
                            </a>
                        </div>
                    </div>
                </div>





                <div class="table-responsive mt-4" >
                    <table class="table table-bordered table-hover shadow-sm align-middle" style="direction: rtl">
                        <thead class="table-light">
                        <tr style="text-align: right">
                            <th>ردیف</th>
                            <th>نام عضو</th>
                            <th>عنوان قسط</th>
                            <th>تاریخ قسط</th>
                            <th>تاریخ واریز</th>
                            <th>مبلغ قسط (ریال)</th>
                            <th>مبلغ پرداختی (ریال)</th>
                            <th>روش پرداخت</th>
                            <th>توضیحات</th>
                            <th>عملیات</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (!empty($rows)): ?>
                            <?php foreach ($rows as $i => $row): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><?= htmlspecialchars($row['member_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($row['installment_title'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="text-nowrap"><?= htmlspecialchars($row['installment_date'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="text-nowrap"><?= htmlspecialchars($row['Deposit_date'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= number_format((float)($row['installment_amount'] ?? 0)) ?></td>
                                    <td><?= number_format((float)($row['paid_amount'] ?? 0)) ?></td>
                                    <td><?= htmlspecialchars($row['payment_method'] ?? '---', ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($row['description'] ?? '---', ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    هیچ قسطی ثبت نشده است.
                                </td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
        </main>
    </div>
</div>

<?php
$content = ob_get_clean();
$title = "گروه مسکن سازان";
include 'partials/layout.php';
?>
