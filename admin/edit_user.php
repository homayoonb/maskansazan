<?php
require_once '../config/Database.php';

// دریافت ID از URL
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($user_id <= 0) {
    die("شناسه کاربر معتبر نیست.");
}

try {
    // اتصال به دیتابیس (اطلاعات خودت را چک کن)
    $pdo = new PDO("mysql:host=localhost;dbname=maskanhb_maskansazan;charset=utf8", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // ۱. دریافت اطلاعات فعلی کاربر برای نمایش در فرم
    $stmt = $pdo->prepare("SELECT * FROM project_members WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        die("کاربر مورد نظر یافت نشد.");
    }

    // ۲. پردازش فرم پس از کلیک روی ذخیره
    $success_msg = "";
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $updateStmt = $pdo->prepare("
            UPDATE project_members 
            SET first_name = ?, last_name = ?, national_code = ?, phone = ?, 
                main_member = ?, share = ?, declared_share = ?, father_name = ?, 
                birth_certificate = ?, birth_date = ?, birth_place = ?, introducer = ?
            WHERE id = ?
        ");

        $updateStmt->execute([
                $_POST['first_name'], $_POST['last_name'], $_POST['national_code'], $_POST['phone'],
                $_POST['main_member'], $_POST['share'], $_POST['declared_share'], $_POST['father_name'],
                $_POST['birth_certificate'], $_POST['birth_date'], $_POST['birth_place'], $_POST['introducer'],
                $user_id
        ]);

        // هدایت به صفحه پروفایل با پیام موفقیت
        header("Location: profileuser.php?id=$user_id&status=updated");
        exit;
    }

} catch (PDOException $e) {
    die("خطا در پایگاه داده: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ویرایش اطلاعات | <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></title>

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

                    <form method="POST">

                        <!-- بخش اطلاعات فردی -->
                        <div class="section-title"><i class="bi bi-info-circle me-2"></i> اطلاعات فردی</div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">نام</label>
                                <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($user['first_name']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">نام خانوادگی</label>
                                <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($user['last_name']) ?>" required>
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
                                <input type="text" name="birth_certificate" class="form-control" value="<?= htmlspecialchars($user['birth_certificate']) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">تاریخ تولد</label>
                                <input type="text" name="birth_date" class="form-control" placeholder="1300-01-01" value="<?= htmlspecialchars($user['birth_date']) ?>">
                            </div>
                        </div>

                        <!-- بخش پروژه و عضویت -->
                        <div class="section-title mt-5"><i class="bi bi-briefcase me-2"></i> اطلاعات پروژه و سهم</div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">نوع عضویت</label>
                                <select name="main_member" class="form-select">
                                    <option value="اصلی" <?= $user['main_member'] == 'اصلی' ? 'selected' : '' ?>>عضو اصلی</option>
                                    <option value="فرعی" <?= $user['main_member'] == 'فرعی' ? 'selected' : '' ?>>عضو فرعی</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">سهم در پروژه</label>
                                <input type="text" name="share" class="form-control" value="<?= htmlspecialchars($user['share']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">معرف</label>
                                <input type="text" name="introducer" class="form-control" value="<?= htmlspecialchars($user['introducer']) ?>">
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-5">
                            <a href="profileuser.php?id=<?= $user_id ?>" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-right me-1"></i> لغو و بازگشت
                            </a>
                            <button type="submit" class="btn btn-primary btn-save shadow-sm">
                                <i class="bi bi-check-circle me-1"></i> ذخیره تغییرات
                            </button>
                        </div>

                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

</body>
</html>
