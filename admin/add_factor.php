<?php
// دریافت شناسه پروژه از URL یا POST
$project_id = (int)($_POST['project_id'] ?? $_GET['project_id'] ?? 0);
date_default_timezone_set('Asia/Tehran');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_invoice'])) {

    // تعریف تابع تبدیل ارقام (اگر قبلاً تعریف نشده باشد)
    if (!function_exists('toEnglishDigits')) {
        function toEnglishDigits($string) {
            $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
            $arabic  = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
            $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

            $string = str_replace($persian, $english, (string)$string);
            return str_replace($arabic, $english, $string);
        }
    }
    // ۲. دریافت و پاکسازی داده‌ها
    $project_id        = (int)($_POST['project_id'] ?? 0);
    $invoice_number    = trim($_POST['invoice_number'] ?? '');
    $invoice_date      = trim($_POST['invoice_date'] ?? '');

    // پاکسازی مبلغ و قیمت (حذف کاما انگلیسی، ویرگول فارسی و فاصله)
    $amount_raw        = trim($_POST['amount'] ?? '0');
    $amount            = (float)str_replace([',', '،', ' '], '', $amount_raw);

    $gheymat_raw       = trim($_POST['gheymat'] ?? '');
    $gheymat           = ($gheymat_raw !== '') ? (float)str_replace([',', '،', ' '], '', $gheymat_raw) : null;

    $count_raw = trim((string)($_POST['count'] ?? ''));
    if ($count_raw !== '') {
        $count_clean = toEnglishDigits($count_raw);
        $count_clean = str_replace([',', '،', ' '], '', $count_clean);
        $count = is_numeric($count_clean) ? (float)$count_clean : null;
    } else {
        $count = null;
    }

    $item_type         = trim($_POST['item_type'] ?? '');
    $vendor_name       = trim($_POST['vendor_name'] ?? '');
    $shop_name         = trim($_POST['shop_name'] ?? '');
    $payment_method    = trim($_POST['payment_method'] ?? 'نقدی');
    $pardakhtkonnandeh = trim($_POST['pardakhtkonnandeh'] ?? '');
    $phone             = trim($_POST['phone'] ?? '');
    $address           = trim($_POST['address'] ?? '');

    // ۳. مدیریت آپلود تصویر فاکتور
    $image_db_name = null;

    if (isset($_FILES['invoice_image']) && $_FILES['invoice_image']['error'] === UPLOAD_ERR_OK) {
        // مسیر فیزیکی در ریشه پروژه (C:\xampp\htdocs\maskansazan\uploads\invoices\)
        $upload_dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'invoices' . DIRECTORY_SEPARATOR;

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_ext = strtolower(pathinfo($_FILES['invoice_image']['name'], PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];

        if (in_array($file_ext, $allowed_exts, true)) {
            $image_db_name = time() . '_' . rand(1000, 9999) . '.' . $file_ext;
            $target_path = $upload_dir . $image_db_name;

            if (!move_uploaded_file($_FILES['invoice_image']['tmp_name'], $target_path)) {
                die("خطا در آپلود فایل به سرور.");
            }
        } else {
            die("فرمت فایل غیرمجاز است. فقط JPG, PNG, WEBP و PDF مجاز هستند.");
        }
    }

    // ۴. درج اطلاعات در دیتابیس
    try {
        $pdo = new PDO(
                "mysql:host=localhost;dbname=maskanhb_maskansazan;charset=utf8mb4",
                "root",
                "",
                [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
        );

        $sql = "INSERT INTO `expenses` 
        (
            `project_id`, 
            `invoice_number`, 
            `invoice_date`, 
            `amount`, 
            `payment_method`, 
            `item_type`, 
            `vendor_name`, 
            `shop_name`, 
            `phone`, 
            `address`, 
            `count`,
            `pardakhtkonnandeh`,
            `gheymat`,
            `invoice_image`
        ) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $pdo->prepare($sql);

        // دقیقاً ۱۴ مقدار با همان ترتیب ستون‌های بالا:
        $result = $stmt->execute([
                $project_id,        // 1. project_id
                $invoice_number,    // 2. invoice_number
                $invoice_date,      // 3. invoice_date
                $amount,            // 4. amount
                $payment_method,    // 5. payment_method
                $item_type,         // 6. item_type
                $vendor_name,       // 7. vendor_name
                $shop_name,         // 8. shop_name
                $phone,             // 9. phone
                $address,           // 10. address
                $count,             // 11. count
                $pardakhtkonnandeh, // 12. pardakhtkonnandeh
                $gheymat,           // 13. gheymat
                $image_db_name      // 14. invoice_image
        ]);

        if ($result) {
            // ۵. انتقال به صفحه لیست با شناسه پروژه
            header("Location: dashboardAdmin.php?tab=expenses&project_id=" . $project_id . "&status=success");
            exit();
        }

    } catch (PDOException $e) {
        die("خطا در ذخیره‌سازی دیتابیس: " . $e->getMessage());
    }
}
ob_start();
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
                <input type="hidden" name="project_id" value="<?= htmlspecialchars((string)($project_id ?: ($_GET['project_id'] ?? ''))) ?>">
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
                            <input type="text" name="invoice_date" data-jdp placeholder="۱۴۰۳/۰۱/۰۱" class="form-control">
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
                            <option value="حواله">حواله</option>
                        </select>
                    </div>

                    <!-- تلفن تماس -->
                    <div class="col-md-4">
                        <label for="phone" class="form-label fw-semibold text-secondary">تلفن تماس</label>
                        <input type="tel" class="form-control text-start" id="phone" name="phone" placeholder="مثال: ۰۲۱۱۲۳۴۵۶۷۸" dir="ltr">
                    </div>
                    <!-- تعداد -->
                    <div class="col-md-4">
                        <label for="count" class="form-label fw-semibold text-secondary">تعداد</label>
                        <input type="text" class="form-control" id="count" name="count" >
                    </div>
                    <!-- قیمت -->
                    <div class="col-md-4">
                        <label for="gheymat" class="form-label fw-semibold text-secondary">قیمت</label>
                        <input type="text" class="form-control" id="gheymat" name="gheymat" required placeholder="مثال: ۵,۰۰۰,۰۰۰" onkeyup="formatCurrency(this)">
                    </div>
                    <!-- پرداخت کننده -->
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">پرداخت کننده</label>
                        <select class="form-select form-select-sm" name="pardakhtkonnandeh">
                            <option value="" disabled selected>انتخاب کنید...</option>
                            <option value="افشارنیا">افشارنیا</option>
                            <option value="بلندیان">بلندیان</option>
                            <option value="حاجی قاسمی">حاجی قاسمی</option>
                            <option value="سایر">سایر</option>
                        </select>
                    </div>
                    <!-- تصویر فاکتور / سند -->
                    <div class="col-md-4">
                        <label for="invoice_image" class="form-label fw-semibold text-secondary">بارگذاری تصویر فاکتور</label>
                        <input type="file" class="form-control" id="invoice_image" name="invoice_image" accept="image/*,application/pdf">
                        <div class="form-text">فرمت‌های مجاز: JPG, PNG, PDF (حداکثر 1 مگابایت)</div>
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

    document.addEventListener("DOMContentLoaded", function () {
        if (typeof jalaliDatepicker !== 'undefined') {
            jalaliDatepicker.startWatch({
                minDate: "attr",
                maxDate: "attr",
                time: false
            });
        } else {
            console.error("فایل جاوااسکریپت jalalidatepicker بارگذاری نشده است.");
        }
    });

</script>


</body>
</html>
<?php
$content = ob_get_clean();
$title = "ایجاد فارکتور جدید";
include '../partials/layout.php';
?>
