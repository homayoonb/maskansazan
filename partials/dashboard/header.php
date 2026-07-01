<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}


$user = [
    'FullName' => $_SESSION['full_name'] ?? 'کاربر',
    'MemberType' => $_SESSION['main_member'] ?? 'عضو',
    'NationalCode' => $_SESSION['NationalCode'] ?? '----',
    'PhoneNumber' => $_SESSION['phone'] ?? '09123456789',
    'ProjectShare' => $_SESSION['share'] ?? '150 مترمربع'
];
date_default_timezone_set('Asia/Tehran');

function gregorian_to_jalali($gy, $gm, $gd)
{
    $g_d_m = [0,31,59,90,120,151,181,212,243,273,304,334];
    $gy2 = ($gm > 2)? ($gy + 1) : $gy;
    $days = 355666 + (365 * $gy) + floor(($gy2 + 3) / 4)
            - floor(($gy2 + 99) / 100) + floor(($gy2 + 399) / 400)
            + $gd + $g_d_m[$gm - 1];
    $jy = -1595 + (33 * floor($days / 12053));
    $days %= 12053;
    $jy += 4 * floor($days / 1461);
    $days %= 1461;
    if ($days > 365) { $jy += floor(($days - 1) / 365); $days = ($days - 1) % 365; }
    $jm = ($days < 186) ? 1 + floor($days / 31) : 7 + floor(($days - 186) / 30);
    $jd = 1 + (($days < 186) ? ($days % 31) : (($days - 186) % 30));
    return [$jy, $jm, $jd];
}

list($jy, $jm, $jd) = gregorian_to_jalali(date('Y'), date('m'), date('d'));
$months = ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
$today_jalali = "{$jd} {$months[$jm-1]} {$jy}";
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
        .activity-paragraph{
            font-size:15px;
            padding:10px 0;
            font-family: "Helvetica Neue", Arial, Helvetica, sans-serif;
        }
            .nav-item {
        position: relative;
        list-style: none;
    }
    
    .dropdown-btn {
        background: none;
        border: none;
        padding: 10px 15px;
        font-size: 16px;
        color: black;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .dropdown-btn:hover {
        color: black;
    }
    
    .dropdown-btn .fa-caret-down {
        transition: transform 0.3s;
        font-size: 14px;
    }
    
    .dropdown-btn.active .fa-caret-down {
        transform: rotate(180deg);
    }
    
    .dropdown-container {
        position: absolute;
        top: 100%;
        right: 0;
        background: white;
        min-width: 180px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        border-radius: 5px;
        padding: 8px 0;
        display: none;
        z-index: 1000;
        border: 1px solid #eee;
    }
    
    .dropdown-container.show {
        display: block;
    }
    
    .dropdown-container a {
        display: flex;
        align-items: center;
        padding: 8px 15px;
        color:black;
        text-decoration: none;
        transition: background 0.2s;
        font-size: 14px;
        gap: 10px;
    }
    
    .dropdown-container a:hover {
        background: #f8f9fa;
        color: black;
    }
    
    .dropdown-container a i {
        width: 20px;
        text-align: center;
        font-size: 16px;
    }
        @media (max-width:768px){ .main-content { margin-right:0; } .sidebar{position:relative;width:100%;} }

    </style>
</head>
<body>
<div class="welcome-header">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h2 class="h2" style="text-align: center">خوش آمدید، <?php echo htmlspecialchars($user['FullName']); ?>!</h2>
            <p class="p-lg-1 activity-paragraph" style="text-align: center;font-family: 'Yekan', 'Segoe UI', Tahoma, sans-serif;"    >این پنل برای مدیریت اطلاعات و فعالیت‌های شما در پروژه <?php echo htmlspecialchars($_SESSION['project_name']); ?> طراحی شده است.</p>
        </div>
        <div class="col-md-4 text-md-left">
            <div class="profile-badge">
                <i class="bi bi-calendar-check"></i> امروز: <?php echo $today_jalali; ?>
            </div>
        </div>
    </div>
</div>
