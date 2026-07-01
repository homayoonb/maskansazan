<?php
session_start();

// ✅ فقط ادمین اجازه دارد
if (!isset($_SESSION['admin_id'])) {
    die("دسترسی غیرمجاز");
}

// ۲. دریافت ID کاربر از URL
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$member_name=isset($_GET['member_name']) ? $_GET['member_name'] : null;
if ($user_id <= 0) {
    die("شناسه کاربر معتبر نیست.");
}
try {
    $pdo = new PDO(
            "mysql:host=localhost;dbname=maskanhb_maskansazan;charset=utf8mb4",
            "root",
            "",
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // ۱. دریافت شناسه عضو از URL
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($id <= 0) {
        die("شناسه کاربر معتبر نیست.");
    }

// ۲. خواندن اطلاعات اصلی عضو از جدول اعضا برای به‌دست آوردن کد ملی (national_code)
    $stmt = $pdo->prepare("SELECT * FROM project_members WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$member) {
        die("عضو مورد نظر یافت نشد.");
    }

// ۳. استخراج کد ملی عضو
    $nationalCode = $member['national_code'];
// اگر نام ستون کد ملی در جدول اعضا متفاوت است (مثلاً national_id)، نام آن را در بالا جایگزین کنید.
// ۴. دریافت لیست ریز تراکنش‌ها و اقساط مالی بر اساس کد ملی (national_code)
    $stmt2 = $pdo->prepare("
    SELECT * FROM member_statements 
    WHERE national_code = :national_code 
    ORDER BY installment_date ASC
");
    $stmt2->execute([':national_code' => $nationalCode]);
    $rows = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    $total_paid = 0; // متغیر برای ذخیره جمع کل
    foreach ($rows as $row) {
        $total_paid += $row['paid_amount']; // جمع زدن مقادیر در هر مرحله از حلقه
    }
} catch (PDOException $e) {
    die("خطا در اتصال: " . $e->getMessage());
}
ob_start();
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
                <table class="table table-bordered table-hover shadow-sm" style="direction: rtl">
                    <thead class="table-light">
                    <tr style="text-align: right">
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
    <a href="dashboardAdmin.php" class="btn btn-secondary" >
        بازگشت
    </a>
</div>
<?php
$content = ob_get_clean();
$title = "گروه مسکن سازان";
include '../partials/layout.php';
include '../partials/slider.php';
?>
