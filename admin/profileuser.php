<?php
// ۱. اتصال به دیتابیس - مسیر را بر اساس پروژه خودت چک کن
require_once '../config/Database.php';

// ۲. دریافت ID کاربر از URL
$user_id = isset($_GET['user_project_id']) ? (int)$_GET['user_project_id'] : 0;
$memberName = isset($_GET['name']) ? trim($_GET['name']) : '';
if ($user_id <= 0) {
    echo "<pre>پارامترهای دریافتی در URL: ";
    print_r($_GET);
    echo "</pre>";
    die("شناسه کاربر معتبر نیست.");
}

// ۳. کوئری برای گرفتن اطلاعات کاربر
try {
// تغییر $pdo به $conn (یا هر اسمی که در فایل db.php شماست)
    $pdo = new PDO("mysql:host=localhost;dbname=maskanhb_maskansazan;charset=utf8", "root", "");
    $stmt = $pdo->prepare("SELECT * FROM users_project WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    if (!$user) {
        die("کاربر مورد نظر یافت نشد.");
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
    <title>پروفایل <?= htmlspecialchars($user['full_name'] ?? '') ?></title>
    <!-- بوت استرپ نسخه 5.3.3 (RTL) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css">

    <!-- بوت استرپ آیکون (برای نمایش آیکون‌هایی مثل bi-person و غیره) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet" type="text/css" />
    <style>
        /* استایل‌های اختصاصی برای ارتقای ظاهر */
        :root {
            --primary-color: #0d6efd;
        }

        .profile-card {
            background: #ffffff;
            border-radius: 20px !important;
            transition: transform 0.3s ease;
        }

        .profile-header-bg {
            height: 100px;
            background: linear-gradient(135deg, #0d6efd 0%, #0dcaf0 100%);
            margin-bottom: -50px;
        }

        .avatar-circle {
            width: 100px;
            height: 100px;
            background-color: #f8f9fa;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            border-radius: 50%;
            position: relative;
            z-index: 2;
        }

        .bg-light-subtle {
            background-color: #f8f9fa !important;
            border: 1px solid #eee;
        }

        .hover-shadow:hover {
            background-color: #fff !important;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            transform: scale(1.01);
            transition: all 0.2s ease;
        }

        .letter-spacing-1 {
            letter-spacing: 1px;
        }

        .transition-all {
            transition: all 0.3s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .fs-7 {
            font-size: 0.9rem;
        }

        .dir-ltr {
            direction: ltr;
            display: inline-block;
        }

        /* واکنش‌گرایی موبایل */
        @media (max-width: 576px) {
            .profile-card {
                margin: 0 10px;
            }
            .info-item {
                flex-direction: column;
                text-align: center;
                gap: 8px;
            }
        }

    </style>
</head>
<body>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10 col-xl-8">

            <div class="card shadow-sm border-0 rounded-4 overflow-hidden">

                <!-- Header -->
                <div class="bg-primary text-white p-4">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-white bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center"
                                 style="width: 52px; height: 52px;">
                                <i class="bi bi-person-badge fs-4"></i>
                            </div>
                            <div>
                                <h5 class="mb-1 fw-bold"><?= htmlspecialchars($user['full_name'] ?? 'کاربر') ?></h5>
                                <div class="small opacity-75">مشخصات کاربری</div>
                            </div>
                        </div>

                        <a href="dashboardAdmin.php" class="btn btn-light btn-sm fw-semibold">
                            <i class="bi bi-arrow-right me-1"></i> بازگشت
                        </a>
                    </div>
                </div>

                <!-- Body -->
                <div class="card-body p-4 p-md-5">

                    <div class="row g-3">

                        <div class="col-md-6">
                            <div class="border rounded-3 p-3 bg-light h-100">
                                <div class="text-muted small mb-1">
                                    <i class="bi bi-person me-1 text-primary"></i>نام و نام خانوادگی
                                </div>
                                <div class="fw-bold">
                                    <?= htmlspecialchars($user['full_name'] ?? '-') ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="border rounded-3 p-3 bg-light h-100">
                                <div class="text-muted small mb-1">
                                    <i class="bi bi-person me-1 text-primary"></i>نام پدر
                                </div>
                                <div class="fw-bold">
                                    <?= htmlspecialchars($user['father_name'] ?? '-') ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="border rounded-3 p-3 bg-light h-100">
                                <div class="text-muted small mb-1">
                                    <i class="bi bi-card-text me-1 text-primary"></i>کد ملی
                                </div>
                                <div class="fw-bold text-start" dir="ltr">
                                    <?= htmlspecialchars($user['national_code'] ?? '-') ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="border rounded-3 p-3 bg-light h-100">
                                <div class="text-muted small mb-1">
                                    <i class="bi bi-file-earmark-person me-1 text-primary"></i>شماره شناسنامه
                                </div>
                                <div class="fw-bold text-start">
                                    <?= htmlspecialchars($user['birth_certificate_no'] ?? '-') ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="border rounded-3 p-3 bg-light h-100">
                                <div class="text-muted small mb-1">
                                    <i class="bi bi-calendar-event me-1 text-primary"></i>تاریخ تولد
                                </div>
                                <div class="fw-bold text-start" dir="ltr">
                                    <?= htmlspecialchars($user['birth_date'] ?? '-') ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="border rounded-3 p-3 bg-light h-100">
                                <div class="text-muted small mb-1">
                                    <i class="bi bi-phone me-1 text-primary"></i>تلفن همراه
                                </div>
                                <div class="fw-bold text-start" dir="ltr">
                                    <?= htmlspecialchars($user['phone'] ?? '-') ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="border rounded-3 p-3 bg-light h-100">
                                <div class="text-muted small mb-1">
                                    <i class="bi bi-shield-check me-1 text-primary"></i>کد ثنا
                                </div>
                                <div class="fw-bold text-start">
                                    <?= htmlspecialchars($user['sana_code'] ?? '-') ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="border rounded-3 p-3 bg-light h-100">
                                <div class="text-muted small mb-1">
                                    <i class="bi bi-mailbox me-1 text-primary"></i>کد پستی
                                </div>
                                <div class="fw-bold text-start" dir="ltr">
                                    <?= htmlspecialchars($user['postal_code'] ?? '-') ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="border rounded-3 p-3 bg-light">
                                <div class="text-muted small mb-1">
                                    <i class="bi bi-geo-alt me-1 text-primary"></i>آدرس
                                </div>
                                <div class="fw-bold text-start">
                                    <?= nl2br(htmlspecialchars($user['address'] ?? '-')) ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="border rounded-3 p-3 bg-light">
                                <div class="text-muted small mb-1 text-start">
                                    <i class="bi bi-people me-1 text-primary"></i>شرکا
                                </div>
                                <div class="fw-bold">
                                    <?php if (($user['has_partner'] ?? '') === 'دارد'): ?>
                                        <?php
                                        $partners = [
                                                $user['partner_1'] ?? '',
                                                $user['partner_2'] ?? '',
                                                $user['partner_3'] ?? '',
                                                $user['partner_4'] ?? ''
                                        ];
                                        $partners = array_filter($partners, 'trim');
                                        ?>
                                        <?= !empty($partners) ? htmlspecialchars(implode(' ، ', $partners)) : 'ثبت نشده' ?>
                                    <?php else: ?>
                                        ندارد
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="d-flex gap-2 justify-content-start mt-4">
                        <a href="edit_user.php?user_project_id=<?= (int)($user['id'] ?? 0) ?>" class="btn btn-primary">
                            <i class="bi bi-pencil-square me-1"></i> ویرایش
                        </a>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>




<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
<?php
$content = ob_get_clean();
$title = "گروه مسکن سازان";
include '../partials/layout.php';
include '../partials/slider.php';
?>
