<?php
session_start();

if(!isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === false){
    header("location: index.php");
    exit();
}

// داده‌های کاربر (در حالت واقعی از دیتابیس یا session گرفته می‌شوند)
$user = [
        'FullName' => $_SESSION['full_name'] ?? 'کاربر',
        'MemberType' => $_SESSION['MemberType'] ?? 'عضو',
        'NationalCode' => $_SESSION['NationalCode'] ?? '----',
        'PhoneNumber' => $_SESSION['phone'] ?? '09123456789',
        'ProjectShare' => $_SESSION['share'] ?? '150 مترمربع'
];

?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>داشبورد کاربری</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #4e73df;
            --success-color: #1cc88a;
            --info-color: #36b9cc;
            --warning-color: #f6c23e;
            --light-color: #f8f9fc;
            --dark-color: #5a5c69;
        }
        body {
            font-family: 'Vazir', 'Segoe UI', Tahoma, sans-serif;
            background-color: #f8f9fc;
            color: #444;
        }
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(180deg, var(--primary-color) 0%, #224abe 100%);
            color: white;
            position: fixed;
            right: 0;
            top: 0;
            width: 250px;
            padding-top: 20px;
            box-shadow: 5px 0 15px rgba(0,0,0,0.1);
        }
        .main-content {
            margin-right: 250px;
            padding: 20px;
        }
        .navbar {
            background-color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 15px 20px;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            margin-bottom: 20px;
            transition: transform 0.3s;
        }
        .card:hover { transform: translateY(-5px); }
        .card-header { font-weight:bold; background:white; border-radius:10px 10px 0 0; padding:15px 20px; border-bottom:1px solid #e3e6f0; }
        .stat-card { border-left:4px solid; padding:20px; }
        .stat-card.primary { border-left-color: var(--primary-color); }
        .stat-card.success { border-left-color: var(--success-color); }
        .stat-card.info { border-left-color: var(--info-color); }
        .stat-card.warning { border-left-color: var(--warning-color); }
        .icon-circle {
            width: 40px; height: 40px; border-radius:50%;
            display:flex; align-items:center; justify-content:center; color:white; font-size:18px;
        }
        .bg-primary { background-color: var(--primary-color) !important; }
        .bg-success { background-color: var(--success-color) !important; }
        .bg-info { background-color: var(--info-color) !important; }
        .bg-warning { background-color: var(--warning-color) !important; }
        .user-info { padding:20px; text-align:center; border-bottom:1px solid rgba(255,255,255,0.3); }
        .user-avatar { width:80px; height:80px; border-radius:50%; object-fit:cover; border:4px solid rgba(255,255,255,0.3); margin-bottom:15px; }
        .sidebar-menu { list-style:none; padding:0; margin:20px 0; }
        .sidebar-menu li { padding:12px 20px; border-left:4px solid transparent; transition:all 0.3s; }
        .sidebar-menu li:hover { background:rgba(255,255,255,0.1); border-left-color:white; }
        .sidebar-menu a { color:rgba(255,255,255,0.8); text-decoration:none; display:block; }
        .sidebar-menu a:hover { color:white; }
        .sidebar-menu i { margin-left:10px; }
        .welcome-header { background: linear-gradient(90deg, var(--primary-color) 0%, #224abe 100%); color:white; padding:30px; border-radius:10px; margin-bottom:30px; }
        .profile-badge { display:inline-block; padding:5px 15px; border-radius:20px; background-color:rgba(255,255,255,0.2); font-size:14px; margin-top:10px; }
        @media (max-width: 768px) {
            .sidebar { width:100%; position:relative; min-height:auto; }
            .main-content { margin-right:0; }
        }
    </style>
</head>
<body>
<!-- سایدبار -->
<div class="sidebar">
    <div class="user-info">
        <img src="https://via.placeholder.com/80" alt="User Avatar" class="user-avatar">
        <h5><?php echo htmlspecialchars($user['FullName']); ?></h5>
        <div class="profile-badge">نوع عضویت : <?php echo $_SESSION['main_member'] ?></div>
    </div>

    <ul class="sidebar-menu">
        <li><a href="dashboard.php"><i class="bi bi-speedometer2"></i> داشبورد</a></li>
        <li><a href="profile.php"><i class="bi bi-person"></i> پروفایل کاربری</a></li>
        <li><a href="#"><i class="bi bi-building"></i> پروژه‌های من</a></li>
        <li><a href="documents.php"><i class="bi bi-file-earmark-text"></i> مدارک و مستندات</a></li>
        <li><a href="#"><i class="bi bi-credit-card"></i> پرداخت‌ها</a></li>
        <li><a href="tickets.php"><i class="bi bi-chat-left-text"></i> تیکت‌های پشتیبانی</a></li>
        <li><a href="logout.php"><i class="bi bi-box-arrow-left"></i> خروج</a></li>
    </ul>
</div>

<!-- محتوای اصلی -->
<div class="main-content">
    <div class="welcome-header">
        <h2>خوش آمدید، <?php echo htmlspecialchars($user['FullName']); ?>!</h2>
        <p>این پنل برای مدیریت اطلاعات و فعالیت‌های شما در پروژه مهر طراحی شده است.</p>
        <div class="profile-badge">امروز: <?php echo date('Y-m-d'); ?></div>
    </div>

    <!-- کارت‌های آمار -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card primary">
                <div class="row align-items-center">
                    <div class="col-9">
                        <div class="text-xs font-weight-bold">سهم پروژه</div>
                        <div class="h5 mb-0 font-weight-bold"><?php echo htmlspecialchars($user['ProjectShare']); ?></div>
                    </div>
                    <div class="col-3 text-left">
                        <div class="icon-circle bg-primary"><i class="bi bi-house-door"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card success">
                <div class="row align-items-center">
                    <div class="col-9">
                        <div class="text-xs font-weight-bold">وضعیت پرداخت</div>
                        <div class="h5 mb-0 font-weight-bold">75%</div>
                    </div>
                    <div class="col-3 text-left">
                        <div class="icon-circle bg-success"><i class="bi bi-credit-card"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card info">
                <div class="row align-items-center">
                    <div class="col-9">
                        <div class="text-xs font-weight-bold">تیکت‌های فعال</div>
                        <div class="h5 mb-0 font-weight-bold">2</div>
                    </div>
                    <div class="col-3 text-left">
                        <div class="icon-circle bg-info"><i class="bi bi-chat-left-text"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card warning">
                <div class="row align-items-center">
                    <div class="col-9">
                        <div class="text-xs font-weight-bold">پروژه‌های در دست اجرا</div>
                        <div class="h5 mb-0 font-weight-bold">1</div>
                    </div>
                    <div class="col-3 text-left">
                        <div class="icon-circle bg-warning"><i class="bi bi-building"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- اطلاعات کاربر -->
    <div class="row">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">اطلاعات کاربری</div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr><th>نام کامل</th><td><?php echo $_SESSION['full_name'] ?></td></tr>
                        <tr><th>کد ملی</th><td><?php echo htmlspecialchars($user['NationalCode']); ?></td></tr>
                        <tr><th>نوع عضویت</th><td><?php echo $_SESSION['main_member'] ?? 'نامشخص' ?></td></tr>
                        <tr><th>تلفن همراه</th><td><?php echo $_SESSION['phone'] ?></td></tr>
                        <tr><th>سهم در پروژه</th><td><?php echo $_SESSION['share'] ?></td></tr>
                    </table>
                    <button class="btn btn-primary mt-3"><i class="bi bi-pencil-square"></i> ویرایش اطلاعات</button>
                </div>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
