<?php
session_start();

// ✅ فقط ادمین اجازه دارد
if (empty($_SESSION['admin_id']) || empty($_SESSION['admin_username'])) {
    header('Location: loginAdmin.php');
    exit;
}


// ۲. دریافت ID کاربر از URL
$user_project_id = isset($_GET['user_project_id']) ? (int)$_GET['user_project_id'] : 0;
$member_name=isset($_GET['member_name']) ? $_GET['member_name'] : null;
$project_id=isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
if ($user_project_id <= 0) {
    var_dump($_GET);
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
    $id = isset($_GET['user_project_id']) ? (int)$_GET['user_project_id'] : 0;

    if ($id <= 0) {
        die("شناسه کاربر معتبر نیست.");
    }

// ۲. خواندن اطلاعات اصلی عضو از جدول اعضا برای به‌دست آوردن کد ملی (national_code)
    $stmt = $pdo->prepare("SELECT * FROM users_project WHERE id = :id");
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
    ORDER BY   installment_date ASC
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
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <a href="dashboardAdmin.php?project_id=<?= urlencode((string)$project_id) ?>" class="btn btn-secondary">
        <i class="bi bi-arrow-right me-1"></i>
        بازگشت
    </a>

    <a href="create_member_statement.php?user_project_id=<?= (int)$user_project_id ?>&project_id=<?= urlencode((string)$project_id) ?>&member_name=<?= $member_name ?>" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>
        درج قسط جدید
    </a>
</div>

<?php if (isset($_GET['success']) && $_GET['success'] === 'created'): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>
        قسط جدید با موفقیت ثبت شد.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_GET['success']) && $_GET['success'] === 'updated'): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i>
        اطلاعات قسط با موفقیت ویرایش شد.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="container-fluid">
    <div class="row">

        <main class="col-md-9 col-lg-10 px-md-4">
            <div class="row mb-3 align-items-center">
                <div class="col-md-4">
                    <label class="col-form-label">
                        جمع واریز شده ها
                    </label>
                </div>

                <div class="col-md-4">
                    <label class="col-form-label d-flex align-items-center gap-2 text-success fw-bold">
                        <span>
                            <?= number_format($total_paid) ?>
                        </span>
                        <span>ریال</span>
                    </label>
                </div>

                <div class="col-md-4 text-end">
                    <a href="export_paid_installments_admin.php?user_project_id=<?= urlencode((string)$user_project_id) ?>&project_id=<?= urlencode((string)$project_id) ?>"
                       class="btn btn-success">
                        خروجی اقساط پرداخت‌شده
                    </a>
                </div>
            </div>

            <div class="table-responsive mt-4">
                <table class="table table-bordered table-hover shadow-sm align-middle" style="direction: rtl">
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
                                <td><?= number_format((float)($row['installment_amount'] ?? 0)) ?></td>
                                <td><?= number_format((float)($row['paid_amount'] ?? 0)) ?></td>
                                <td><?= htmlspecialchars($row['payment_method'] ?? '---', ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($row['description'] ?? '---', ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <a
                                            target="_blank"
                                            class="btn btn-sm btn-outline-primary"
                                            href="edit_member_statment.php?id=<?= (int)$row['id'] ?>&user_project_id=<?= (int)$user_project_id ?>&project_id=<?= urlencode((string)$project_id) ?>"
                                    >
                                        <i class="bi bi-pencil-square me-1"></i>
                                        ویرایش
                                    </a>
                                    <!-- لینک حذف -->
                                    <a href="delete_statement.php?id=<?= $row['id'] ?>&user_project_id=<?= (int)$user_project_id ?>&project_id=<?= (int)$project_id ?>"
                                       class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('آیا از حذف این قسط مطمئن هستید؟ این عملیات غیرقابل بازگشت است.');">حذف</a>
                                </td>
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
include '../partials/layout.php';
include '../partials/slider.php';
?>
