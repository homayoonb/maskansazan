<?php
// فرض بر این است که آی‌دی پروژه را از URL می‌گیریم
$project_id = $_GET['project_id'] ?? 0;
date_default_timezone_set('Asia/Tehran');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_invoice'])) {

    // ۲. دریافت و پاکسازی داده‌ها (Prevent SQL Injection)
    $project_id     = filter_var($_POST['project_id'], FILTER_SANITIZE_NUMBER_INT);
    $invoice_number = htmlspecialchars($_POST['invoice_number']);
    $invoice_date   = htmlspecialchars($_POST['invoice_date']); // تاریخ شمسی از تقویم
    $amount         = str_replace(',', '', $_POST['amount']); // حذف کاما اگر کاربر وارد کرده باشد
    $item_type      = htmlspecialchars($_POST['item_type']);
    $shop_name      = htmlspecialchars($_POST['shop_name']);
    $payment_method = $_POST['payment_method'];
    $phone          = htmlspecialchars($_POST['phone']);
    $address        = htmlspecialchars($_POST['address']);

    // ۳. مدیریت آپلود تصویر فاکتور
    $image_db_name = null; // نامی که در دیتابیس ذخیره می‌شود

    if (isset($_FILES['invoice_image']) && $_FILES['invoice_image']['error'] == 0) {
        $upload_dir = "uploads/invoices/";

        // ایجاد پوشه اگر وجود نداشت
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_ext = strtolower(pathinfo($_FILES['invoice_image']['name'], PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'pdf'];

        if (in_array($file_ext, $allowed_exts)) {
            // ساخت نام منحصر به فرد برای فایل: (زمان_رندوم.پسوند)
            $image_db_name = time() . '_' . rand(1000, 9999) . '.' . $file_ext;
            $target_path = $upload_dir . $image_db_name;

            if (!move_uploaded_file($_FILES['invoice_image']['tmp_name'], $target_path)) {
                die("خطا در آپلود فایل به سرور.");
            }
        } else {
            die("فرمت فایل غیرمجاز است. فقط JPG, PNG, PDF مجاز هستند.");
        }
    }

    // ۴. درج اطلاعات در دیتابیس با استفاده از Prepared Statements
    try {
        $sql = "INSERT INTO expenses 
                (invoice_number, invoice_date, amount, payment_method,item_type,vendor_name,shop_name,ph) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $pdo = new PDO("mysql:host=localhost;dbname=maskanhb_maskansazan;charset=utf8mb4",
                "root",
                "",
                [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);;
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
                $invoice_number,
                $invoice_date,
                $amount,
                $item_type,
                $shop_name,
                $payment_method,
                $phone,
                $address,
                $image_db_name
        ]);

        if ($result) {
            // ۵. انتقال به صفحه لیست با پیام موفقیت
            header("Location: dashboardAdmin.php?tab=invoices&status=success");
            exit();
        }

    } catch (PDOException $e) {
        // در صورت بروز خطا در دیتابیس
        die("خطا در ذخیره‌سازی دیتابیس: " . $e->getMessage());
    }

}
ob_start()
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>ثبت فاکتور جدید</title>
    <!-- Persian Datepicker CSS -->
    <link rel="stylesheet" href="https://unpkg.com/@majidh1/jalalidatepicker/dist/jalalidatepicker.min.css">
    <!-- Persian Datepicker JS -->
    <script type="text/javascript" src="https://unpkg.com/@majidh1/jalalidatepicker/dist/jalalidatepicker.min.js"></script>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Tahoma', sans-serif; }
        .form-card { border: none; border-radius: 15px; shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1); }
        .form-label { font-weight: bold; color: #555; font-size: 0.9rem; }
    </style>
</head>
<body>
<a href="dashboardAdmin.php?tab=expenses&project_id=<?= $project_id ?>" class="btn btn-sm btn-light text-primary fw-bold">
    <i class="bi bi-arrow-right me-1"></i> بازگشت به لیست فاکتورها
</a>
<!-- add_invoice.php -->
<div class="container-fluid py-4">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-primary text-white d-flex align-items-center justify-content-between py-3">
            <h5 class="card-title mb-0 fs-6 fw-bold">
                <i class="bi bi-file-earmark-plus-fill me-2"></i> ثبت فاکتور مالی جدید (هزینه‌ها)
            </h5>

        </div>
        <div class="card-body p-4">
            <!-- توجه: enctype برای ارسال فایل تصویر فاکتور الزامی است -->
            <form action="" method="POST" enctype="multipart/form-data">

                <div class="row g-3">

                    <!-- شماره فاکتور -->
                    <div class="col-md-4">
                        <label for="invoice_number" class="form-label fw-semibold text-secondary">شماره فاکتور</label>
                        <input type="text" class="form-control" id="invoice_number" name="invoice_number" required placeholder="مثال: ۱۲۳۴۵">
                    </div>

                    <!-- تاریخ فاکتور (شمسی) -->
                    <div class="col-md-4">
                        <label for="invoice_date" class="form-label fw-semibold text-secondary">تاریخ فاکتور</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-calendar3"></i></span>
                            <input type="text" class="form-control jalali-datepicker" id="invoice_date" name="invoice_date" required placeholder="۱۴۰۲/۰۱/۰۱" autocomplete="off">
                        </div>
                    </div>

                    <!-- مبلغ فاکتور (ریال) -->
                    <div class="col-md-4">
                        <label for="amount" class="form-label fw-semibold text-secondary">مبلغ فاکتور (ریال)</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="amount" name="amount" required placeholder="مثال: ۵,۰۰۰,۰۰۰" onkeyup="formatCurrency(this)">
                            <span class="input-group-text bg-light">ریال</span>
                        </div>
                    </div>

                    <!-- بابت (نوع کالا/خدمات) -->
                    <div class="col-md-4">
                        <label for="item_type" class="form-label fw-semibold text-secondary">بابت (نوع کالا / خدمات)</label>
                        <input type="text" class="form-control" id="item_type" name="item_type" required placeholder="مثال: خرید سیمان، دستمزد گچ‌کار">
                    </div>

                    <!-- نام فروشنده / مسئول -->
                    <div class="col-md-4">
                        <label for="vendor_name" class="form-label fw-semibold text-secondary">نام فروشنده / مسئول</label>
                        <input type="text" class="form-control" id="vendor_name" name="vendor_name" placeholder="مثال: علی رضایی">
                    </div>

                    <!-- نام فروشگاه / شرکت -->
                    <div class="col-md-4">
                        <label for="shop_name" class="form-label fw-semibold text-secondary">نام فروشگاه / شرکت</label>
                        <input type="text" class="form-control" id="shop_name" name="shop_name" required placeholder="مثال: مصالح ساختمانی البرز">
                    </div>

                    <!-- روش پرداخت -->
                    <div class="col-md-4">
                        <label for="payment_method" class="form-label fw-semibold text-secondary">روش پرداخت</label>
                        <select class="form-select" id="payment_method" name="payment_method" required>
                            <option value="" disabled selected>انتخاب کنید...</option>
                            <option value="نقدی">نقدی</option>
                            <option value="چک">چک</option>
                            <option value="حواله/کارت به کارت">حواله / کارت به کارت</option>
                        </select>
                    </div>

                    <!-- تلفن تماس -->
                    <div class="col-md-4">
                        <label for="phone" class="form-label fw-semibold text-secondary">تلفن تماس</label>
                        <input type="tel" class="form-control text-start" id="phone" name="phone" placeholder="مثال: ۰۲۱۱۲۳۴۵۶۷۸" dir="ltr">
                    </div>

                    <!-- تصویر فاکتور / سند -->
                    <div class="col-md-4">
                        <label for="invoice_image" class="form-label fw-semibold text-secondary">بارگذاری تصویر فاکتور</label>
                        <input type="file" class="form-control" id="invoice_image" name="invoice_image" accept="image/*,application/pdf">
                        <div class="form-text">فرمت‌های مجاز: JPG, PNG, PDF (حداکثر ۵ مگابایت)</div>
                    </div>

                    <!-- آدرس فروشگاه / محل خرید -->
                    <div class="col-md-12">
                        <label for="address" class="form-label fw-semibold text-secondary">آدرس فروشگاه / شرکت</label>
                        <textarea class="form-control" id="address" name="address" rows="2" placeholder="آدرس دقیق را در صورت نیاز وارد کنید..."></textarea>
                    </div>

                </div>

                <hr class="my-4 text-muted">

                <!-- دکمه‌های فرم -->
                <div class="d-flex justify-content-end gap-2">
                    <button type="submit" name="submit_invoice" class="btn btn-primary px-4">
                        <i class="bi bi-check-circle me-1"></i> ثبت و ذخیره فاکتور
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>




<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- اسکریپت‌های کمکی برای بهبود تجربه کاربری فرم -->
<script>
    // ۱. فرمت سه رقم سه رقم مبلغ هنگام تایپ
    function formatCurrency(input) {
        let value = input.value.replace(/\D/g, "");
        input.value = value.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    // ۲. فعال‌سازی تقویم شمسی با روش استاندارد و بدون خطا
    document.addEventListener("DOMContentLoaded", function() {
        // روش اول: مقداردهی عمومی نسخه رایج jalaliDatepicker
        if (typeof jalaliDatepicker !== 'undefined') {
            try {
                if (typeof jalaliDatepicker.start === 'function') {
                    jalaliDatepicker.start({
                        persianDigits: true,
                        showTodayBtn: true,
                        selectToday: true
                    });
                } else {
                    // روش جایگزین در صورت تفاوت در ساختار شیء کتابخانه
                    jalaliDatepicker({
                        selector: ".jalali-datepicker",
                        persianDigits: true
                    });
                }
            } catch (e) {
                console.warn("خطا در راه‌اندازی jalaliDatepicker: ", e);
            }
        }
        // پشتیبانی از کتابخانه جایگزین kamadatepicker در صورت فعال بودن در سیستم شما
        else if (typeof kamaDatepicker !== 'undefined') {
            kamaDatepicker('invoice_date', {
                buttonsColor: "red",
                forceFarsiDigits: true,
                markToday: true,
                gotoToday: true
            });
        }
    });
</script>


</body>
</html>
<?php
$content = ob_get_clean();
$title = "ایجاد فارکتور جدید";
include '../partials/layout.php';
include '../partials/slider.php';
?>
