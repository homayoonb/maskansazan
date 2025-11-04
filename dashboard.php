<?php
session_start();

// بررسی لاگین
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: index.php');
    exit();
}

// داده‌های کاربر
$user = [
        'FullName' => $_SESSION['full_name'] ?? 'کاربر',
        'MemberType' => $_SESSION['main_member'] ?? 'عضو',
        'NationalCode' => $_SESSION['NationalCode'] ?? '----',
        'PhoneNumber' => $_SESSION['phone'] ?? '09123456789',
        'ProjectShare' => $_SESSION['share'] ?? '150 مترمربع'
];

$currentDate = date('Y-m-d');

// نمونه آخرین فعالیت‌ها
$activities = [
        ['title' => 'ورود به سیستم', 'time' => 'امروز، 10:30', 'desc' => 'شما با موفقیت وارد حساب کاربری خود شدید.'],
        ['title' => 'پرداخت قسط', 'time' => 'دیروز، 15:20', 'desc' => 'پرداخت قسط اول پروژه با موفقیت ثبت شد.'],
        ['title' => 'ارسال مدرک', 'time' => 'سه روز پیش، 09:10', 'desc' => 'مدرک شناسایی با موفقیت بارگذاری شد.']
];
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>داشبورد کاربری</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4e73df;
            --success-color: #1cc88a;
            --info-color: #36b9cc;
            --warning-color: #f6c23e;
            --light-color: #f8f9fc;
            --dark-color: #5a5c69;
        }
        body { font-family: 'Vazir', sans-serif; background-color: var(--light-color); margin:0; padding:0; }
        .sidebar { width:250px; background: linear-gradient(180deg, var(--primary-color) 0%, #224abe 100%); color:white; min-height:100vh; position:fixed; right:0; top:0; padding-top:20px; }
        .sidebar .user-info { text-align:center; padding:20px; border-bottom:1px solid rgba(255,255,255,0.3); }
        .sidebar .user-avatar { width:80px; height:80px; border-radius:50%; border:4px solid rgba(255,255,255,0.3); margin-bottom:15px; }
        .sidebar-menu { list-style:none; padding:0; margin:20px 0; }
        .sidebar-menu li { padding:12px 20px; border-left:4px solid transparent; transition:all 0.3s; }
        .sidebar-menu li:hover { background:rgba(255,255,255,0.1); border-left-color:white; }
        .sidebar-menu a { color:rgba(255,255,255,0.8); text-decoration:none; display:block; }
        .sidebar-menu a:hover { color:white; }
        .main-content { margin-right:250px; padding:20px; }
        .welcome-header { background: linear-gradient(90deg, var(--primary-color) 0%, #224abe 100%); color:white; padding:30px; border-radius:10px; margin-bottom:30px; }
        .profile-badge { display:inline-block; padding:5px 15px; border-radius:20px; background-color:rgba(255,255,255,0.2); font-size:14px; margin-top:10px; }
        .card { border:none; border-radius:10px; box-shadow:0 3px 10px rgba(0,0,0,0.08); margin-bottom:20px; }
        .card-header { font-weight:bold; background:white; border-radius:10px 10px 0 0; padding:15px 20px; border-bottom:1px solid #e3e6f0; }
        .stat-card { border-left:4px solid; padding:20px; }
        .stat-card.primary { border-left-color: var(--primary-color); }
        .stat-card.success { border-left-color: var(--success-color); }
        .stat-card.info { border-left-color: var(--info-color); }
        .stat-card.warning { border-left-color: var(--warning-color); }
        .icon-circle { width:40px; height:40px; border-radius:50%; display:flex; align-items:center; justify-content:center; color:white; font-size:18px; }
        .table { width:100%; background:white; border-radius:10px; overflow:hidden; box-shadow:0 3px 10px rgba(0,0,0,0.05); border-collapse:collapse; }
        .table th, .table td { padding:12px 15px; border-bottom:1px solid #e3e6f0; }
        .table th { background:#f8f9fc; font-weight:600; text-align:left; }
        .table tr:last-child td { border-bottom:none; }
        .activity-item { padding:10px 0; border-bottom:1px solid #e3e6f0; }
        .activity-item:last-child { border-bottom:none; }
        @media (max-width:768px){ .main-content { margin-right:0; } .sidebar{position:relative;width:100%;} }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="user-info">
        <img src="https://via.placeholder.com/80" alt="User Avatar" class="user-avatar">
        <h5><?php echo htmlspecialchars($user['FullName']); ?></h5>
        <div class="profile-badge">نوع عضویت: <?php echo htmlspecialchars($user['MemberType']); ?></div>
    </div>
    <ul class="sidebar-menu">
        <li><a href="dashboard.php"><i class="bi bi-speedometer2"></i> داشبورد</a></li>
        <li><a><i class="bi bi-person"></i> پروفایل کاربری</a></li>
        <li><a href="#"><i class="bi bi-building"></i> پروژه‌های من</a></li>
        <li><a href="documents.php"><i class="bi bi-file-earmark-text"></i> مدارک و مستندات</a></li>
        <li><a href="#"><i class="bi bi-credit-card"></i> پرداخت‌ها</a></li>
        <li><a><i class="bi bi-chat-left-text"></i> تیکت‌های پشتیبانی</a></li>
        <li><a href="logout.php"><i class="bi bi-box-arrow-left"></i> خروج</a></li>
    </ul>
</div>

<div class="main-content">

    <div class="welcome-header">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h2>خوش آمدید، <?php echo htmlspecialchars($user['FullName']); ?>!</h2>
                <p>این پنل برای مدیریت اطلاعات و فعالیت‌های شما در پروژه مهر طراحی شده است.</p>
            </div>
            <div class="col-md-4 text-md-left">
                <div class="profile-badge">
                    <i class="bi bi-calendar-check"></i> امروز: <?php echo $currentDate; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- کارت‌ها -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card primary">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-xs font-weight-bold text-primary mb-1">سهم پروژه</div>
                        <div class="h5 mb-0 font-weight-bold"><?php echo htmlspecialchars($user['ProjectShare']); ?></div>
                    </div>
                    <div class="icon-circle bg-primary"><i class="bi bi-house-door"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card success">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-xs font-weight-bold text-success mb-1">وضعیت پرداخت</div>
                        <div class="h5 mb-0 font-weight-bold">75%</div>
                    </div>
                    <div class="icon-circle bg-success"><i class="bi bi-credit-card"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card info">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-xs font-weight-bold text-info mb-1">تیکت‌های فعال</div>
                        <div class="h5 mb-0 font-weight-bold">2</div>
                    </div>
                    <div class="icon-circle bg-info"><i class="bi bi-chat-left-text"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card warning">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-xs font-weight-bold text-warning mb-1">پروژه‌های در دست اجرا</div>
                        <div class="h5 mb-0 font-weight-bold">1</div>
                    </div>
                    <div class="icon-circle bg-warning"><i class="bi bi-building"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- اطلاعات کاربر و فعالیت‌ها -->
    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">اطلاعات کاربری</div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr><th>نام کامل</th><td><?php echo htmlspecialchars($user['FullName']); ?></td></tr>
                        <tr><th>کد ملی</th><td><?php echo htmlspecialchars($user['NationalCode']); ?></td></tr>
                        <tr><th>نوع عضویت</th><td><?php echo htmlspecialchars($user['MemberType']); ?></td></tr>
                        <tr><th>تلفن همراه</th><td><?php echo htmlspecialchars($user['PhoneNumber']); ?></td></tr>
                        <tr><th>سهم در پروژه</th><td><?php echo htmlspecialchars($user['ProjectShare']); ?></td></tr>
                    </table>
                    <button class="btn btn-primary mt-3"><i class="bi bi-pencil-square"></i> ویرایش اطلاعات</button>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">آخرین فعالیت‌ها</div>
                <div class="card-body">
                    <?php foreach($activities as $act): ?>
                        <div class="activity-item">
                            <div class="d-flex justify-content-between">
                                <strong><?php echo $act['title']; ?></strong>
                                <span class="text-muted small"><?php echo $act['time']; ?></span>
                            </div>
                            <p class="mb-0 text-muted"><?php echo $act['desc']; ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
