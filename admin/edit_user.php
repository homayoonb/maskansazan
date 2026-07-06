<?php
require_once '../config/Database.php';

// دریافت ID از URL
$user_project_id = isset($_GET['user_project_id']) ? (int)$_GET['user_project_id'] : 0;

if ($user_project_id <= 0) {
    die("شناسه کاربر معتبر نیست.");
}

try {
    // اتصال به دیتابیس (اطلاعات خودت را چک کن)
    $pdo = new PDO("mysql:host=localhost;dbname=maskanhb_maskansazan;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ۱. دریافت اطلاعات فعلی کاربر برای نمایش در فرم
    $stmt = $pdo->prepare("SELECT * FROM users_project WHERE id = ?");
    $stmt->execute([$user_project_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        die("کاربر مورد نظر یافت نشد.");
    }

    // ۲. پردازش فرم پس از کلیک روی ذخیره
    $success_msg = "";
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $has_partner = trim($_POST['has_partner'] ?? 'ندارد');
        $sana_code = trim($_POST['sana_code'] ?? 'ندارد');
        $postal_code = trim($_POST['postal_code'] ?? '');

        // اعتبارسنجی ENUM
        $allowedPartner = ['دارد', 'ندارد'];

        if (!in_array($has_partner, $allowedPartner)) {
            $has_partner = 'ندارد';
        }

        if (!in_array($sana_code, $allowedPartner)) {
            $sana_code = 'ندارد';
        }

// دریافت فیلدهای شریک
        $partner_1 = trim($_POST['partner_1'] ?? '');
        $partner_2 = trim($_POST['partner_2'] ?? '');
        $partner_3 = trim($_POST['partner_3'] ?? '');
        $partner_4 = trim($_POST['partner_4'] ?? '');
        if ($has_partner === 'ندارد') {
            $partner_1 = 'ندارد';
            $partner_2 = 'ندارد';
            $partner_3 = 'ندارد';
            $partner_4 = 'ندارد';
        }
        $updateStmt = $pdo->prepare("
            UPDATE users_project 
            SET full_name = ?, 
                has_partner = ?, 
                father_name = ?, 
                national_code = ?, 
                birth_certificate_no = ?, 
                birth_date = ?, 
                phone = ?, 
                sana_code = ?, 
                address = ?, 
                postal_code = ?, 
                partner_1 = ?, 
                partner_2 = ?,
                partner_3 = ?,
                partner_4 = ?
            WHERE id = ?
        ");

        $updateStmt->execute([
                trim($_POST['full_name'] ?? ''),       // 1. full_name
                $has_partner,                           // 2. has_partner (اعتبارسنجی شده)
                trim($_POST['father_name'] ?? ''),     // 3. father_name
                trim($_POST['national_code'] ?? ''),   // 4. national_code
                trim($_POST['birth_certificate_no'] ?? ''), // 5. birth_certificate_no
                trim($_POST['birth_date'] ?? ''),      // 6. birth_date
                trim($_POST['phone'] ?? ''),           // 7. phone
                $sana_code,                             // 8. sana_code (اعتبارسنجی شده)
                trim($_POST['address'] ?? ''),         // 9. address
                $postal_code,                           // 10. postal_code (اعتبارسنجی شده)
                $partner_1,                             // 11. partner_1
                $partner_2,                             // 12. partner_2
                $partner_3,                             // 13. partner_3
                $partner_4,                             // 14. partner_4
                $user_project_id                        // 15. id (برای WHERE)
        ]);


        // هدایت به صفحه پروفایل با پیام موفقیت
        header("Location: profileuser.php?user_project_id=$user_project_id&status=updated");
        exit;
    }

} catch (PDOException $e) {
    die("خطا در پایگاه داده: " . $e->getMessage());
}
ob_start();
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ویرایش اطلاعات | <?= htmlspecialchars($user['full_name'] ?? '')?></title>

    <!-- Bootstrap RTL -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <style>
        body { background-color: #f4f7f6; font-family: 'Tahoma', sans-serif; }
        .card { border: none; border-radius: 15px; }
        .form-label { font-weight: bold; color: #444; font-size: 0.9rem; }
        .form-control:focus, .form-select:focus { border-color: #0d6efd; box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.1); }
        .btn-save { padding: 10px 30px; border-radius: 10px; font-weight: bold; }
        .section-title { border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 20px; color: #0d6efd; font-size: 1.1rem; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-9">

            <div class="card shadow">
                <div class="card-body p-4 p-md-5">

                    <div class="text-center mb-4">
                        <div class="display-6 mb-2"><i class="bi bi-person-gear text-primary"></i></div>
                        <h2 class="fw-bold">ویرایش اطلاعات عضو</h2>
                        <p class="text-muted">لطفاً اطلاعات مورد نظر را اصلاح و ذخیره کنید.</p>
                    </div>

                    <form method="POST" id="editMemberForm">

                        <!-- بخش اطلاعات فردی -->
                        <div class="section-title"><i class="bi bi-info-circle me-2"></i> اطلاعات فردی</div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">نام و نام خانوادگی</label>
                                <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">کد ملی</label>
                                <input type="text" name="national_code" class="form-control" value="<?= htmlspecialchars($user['national_code']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">تلفن همراه</label>
                                <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone']) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">نام پدر</label>
                                <input type="text" name="father_name" class="form-control" value="<?= htmlspecialchars($user['father_name']) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">شماره شناسنامه</label>
                                <input type="text" name="birth_certificate_no" class="form-control" value="<?= htmlspecialchars($user['birth_certificate_no']) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">تاریخ تولد</label>
                                <input type="text" name="birth_date" class="form-control" placeholder="1300-01-01" value="<?= htmlspecialchars($user['birth_date']) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">آدرس</label>
                                <textarea name="address" class="form-control"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">کد پستی</label>
                                <input
                                        type="number"
                                        name="postal_code"
                                        class="form-control"
                                        maxlength="10"
                                        pattern="\d{10}"
                                        value="<?= htmlspecialchars($user['postal_code'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">شریک</label>
                                <select name="has_partner" class="form-select">
                                    <option value="دارد" <?= $user['has_partner'] == 'دارد' ? 'selected' : '' ?>>دارد</option>
                                    <option value="ندارد" <?= $user['has_partner'] == 'ندارد' ? 'selected' : '' ?>>ندارد</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">کد ثنا</label>
                                <select name="sana_code" class="form-select">
                                    <option value="دارد" <?= $user['sana_code'] == 'دارد' ? 'selected' : '' ?>>دارد</option>
                                    <option value="ندارد" <?= $user['sana_code'] == 'ندارد' ? 'selected' : '' ?>>ندارد</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label name="partner_1" class="form-label">شریک ۱</label>
                                <input type="text" class="form-control" name="partner_1" value="<?= htmlspecialchars($user['partner_1'] ?? '') ?>">




                            </div>
                            <div class="col-md-4">
                                <label class="form-label">شریک 2</label>
                                <input type="text" class="form-control" name="partner_2" value="<?= htmlspecialchars($user['partner_2'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">شریک 3</label>
                                <input type="text" class="form-control" name="partner_3" value="<?= htmlspecialchars($user['partner_3'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">شریک 4</label>
                                <input type="text" class="form-control" name="partner_4" value="<?= htmlspecialchars($user['partner_4'] ?? '') ?>">
                            </div>
                        </div>



                        <div class="d-flex justify-content-between align-items-center mt-5">
                            <a href="profileuser.php?user_project_id=<?= $user_project_id ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-right me-1"></i> لغو و بازگشت
                            </a>
                            <button type="submit" id="submitBtn" class="btn btn-primary btn-save shadow-sm">
                                <span id="submitIcon">
                                    <i class="bi bi-check-circle me-1"></i>
                                </span>

                                <span id="submitText">ذخیره تغییرات</span>

                                <span id="submitSpinner" class="spinner-border spinner-border-sm ms-2 d-none" role="status" aria-hidden="true"></span>
                            </button>

                        </div>

                    </form>
                </div>
            </div>

        </div>
    </div>
</div>
<script>
    document.getElementById('editMemberForm').addEventListener('submit', function () {
        const submitBtn = document.getElementById('submitBtn');
        const submitIcon = document.getElementById('submitIcon');
        const submitText = document.getElementById('submitText');
        const submitSpinner = document.getElementById('submitSpinner');

        submitBtn.disabled = true;
        submitIcon.classList.add('d-none');
        submitText.textContent = 'در حال ذخیره...';
        submitSpinner.classList.remove('d-none');
    });
</script>

</body>
</html>
<?php
$content = ob_get_clean();
$title = "گروه مسکن سازان";
include '../partials/layout.php';
include '../partials/slider.php';
?>
