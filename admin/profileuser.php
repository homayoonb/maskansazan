<?php
// ۱. اتصال به دیتابیس - مسیر را بر اساس پروژه خودت چک کن
require_once '../config/Database.php';

// ۲. دریافت ID کاربر از URL
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$memberName = isset($_GET['name']) ? trim($_GET['name']) : '';
var_dump($memberName);
if ($user_id <= 0) {
    die("شناسه کاربر معتبر نیست.");
}

// ۳. کوئری برای گرفتن اطلاعات کاربر
try {
// تغییر $pdo به $conn (یا هر اسمی که در فایل db.php شماست)
    $pdo = new PDO("mysql:host=localhost;dbname=maskanhb_maskansazan;charset=utf8", "root", "");
    $stmt = $pdo->prepare("SELECT * FROM project_members WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        die("کاربر مورد نظر یافت نشد.");
    }
} catch (PDOException $e) {
    die("خطا در پایگاه داده: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>پروفایل <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></title>
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
        <div class="col-lg-7 col-xl-6">
            <div class="card profile-card shadow-lg border-0 rounded-4 overflow-hidden">
                <!-- هدر رنگی پشت آیکون (اختیاری برای زیبایی) -->
                <div class="profile-header-bg"></div>

                <div class="card-body p-4 p-md-5 pt-0">
                    <!-- بخش تصویر و نام -->
                    <div class="text-center profile-avatar-wrapper">
                        <div class="avatar-circle shadow-sm border border-4 border-white mx-auto mb-3">
                            <i class="bi bi-person-fill"></i>
                        </div>
                        <h3 class="fw-bold text-dark mb-1">
                            <?= htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?>
                        </h3>
                    </div>

                    <!-- جدول اطلاعات با استایل جدید -->
                    <div class="info-list mt-4">
                        <div class="info-item d-flex align-items-center justify-content-between p-3 mb-2 rounded-3 bg-light-subtle hover-shadow">
                            <span class="text-muted fs-7"><i class="bi bi-person-badge me-2 text-primary"></i>نام و نام خانوادگی</span>
                            <span class="fw-semibold text-dark"><?= htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?></span>
                        </div>

                        <div class="info-item d-flex align-items-center justify-content-between p-3 mb-2 rounded-3 bg-light-subtle hover-shadow">
                            <span class="text-muted fs-7"><i class="bi bi-card-text me-2 text-primary"></i>کد ملی</span>
                            <span class="fw-bold letter-spacing-1 text-dark"><?= htmlspecialchars($user['national_code'] ?? '-') ?></span>
                        </div>

                        <div class="info-item d-flex align-items-center justify-content-between p-3 mb-2 rounded-3 bg-light-subtle hover-shadow">
                            <span class="text-muted fs-7"><i class="bi bi-people me-2 text-primary"></i>نوع عضویت</span>
                            <span class="badge <?= ($user['main_member'] == 'اصلی') ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-dark' ?> border-0 rounded-pill px-3 py-2">
                                <i class="bi bi-circle-fill me-1 small"></i> <?= htmlspecialchars($user['main_member'] ?? '-') ?>
                            </span>
                        </div>

                        <div class="info-item d-flex align-items-center justify-content-between p-3 mb-2 rounded-3 bg-light-subtle hover-shadow">
                            <span class="text-muted fs-7"><i class="bi bi-telephone me-2 text-primary"></i>تلفن همراه</span>
                            <span class="fw-bold text-dark dir-ltr"><?= htmlspecialchars($user['phone'] ?? '-') ?></span>
                        </div>

                        <div class="info-item d-flex align-items-center justify-content-between p-3 rounded-3 bg-primary-subtle hover-shadow">
                            <span class="text-primary fw-bold fs-7"><i class="bi bi-pie-chart-fill me-2"></i>سهم در پروژه</span>
                            <span class="badge bg-primary text-white rounded-3 px-3 py-2 fs-6 fw-bold"><?= htmlspecialchars($user['share'] ?? '-') ?></span>
                        </div>
                    </div>

                    <!-- دکمه‌ها -->
                    <div class="row g-3 mt-4">
                        <div class="col-6">
                            <a href="dashboardAdmin.php" class="btn btn-outline-secondary w-100 py-2 rounded-3 transition-all">
                                <i class="bi bi-arrow-right me-1"></i> بازگشت
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="edit_user.php?id=<?= $user['id'] ?>" class="btn btn-primary w-100 py-2 rounded-3 shadow-sm transition-all">
                                <i class="bi bi-pencil-square me-1"></i> ویرایش
                            </a>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
