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
                        نام عضو <span class="bi-box"><b><?= $member_name?></b></span>
                    </label>
                </div>



            </div>
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
        </main>
    </div>
</div>
<?php
$content = ob_get_clean();
$title = "گروه مسکن سازان";
include '../partials/layout.php';
include '../partials/slider.php';
?>
