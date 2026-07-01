<?php
session_start();
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: groupmember.php'); exit();
}
$user = [
        'FullName'     => $_SESSION['full_name'] ?? '',
        'NationalCode' => $_SESSION['NationalCode'] ?? '',
        'MemberType'   => $_SESSION['main_member'] ?? '',
        'PhoneNumber'  => $_SESSION['phone'] ?? '',
        'ProjectShare' => $_SESSION['share'] ?? '',
        'Address' => $_SESSION['address'] ?? '',
];

?>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>داشبورد کاربری</title>
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

<div class="profile-card">
    <div class="profile-header">
    </div>
    <div class="profile-body mt-4">
        <table class="table table-hover table-borderless align-middle align-items-center mb-0" style="width:100%;text-align:center;">
            <tbody>
            <tr>
                <th style="text-align: center"><i class="bi bi-person"></i> نام ونام خانوادگی</th>
                <td><?php echo htmlspecialchars($user['FullName']); ?></td>
            </tr>
            <tr>
                <th style="text-align: center"><i class="bi bi-person-badge"></i> کد ملی</th>
                <td><?php echo htmlspecialchars($user['NationalCode']); ?></td>
            </tr>
            <tr>
                <th style="text-align: center"><i class="bi bi-telephone"></i> تلفن همراه</th>
                <td><?php echo htmlspecialchars($user['PhoneNumber']); ?></td>
            </tr>
            <tr>
                <th style="text-align: center"><i class="bi bi-people"></i> نوع عضویت</th>
                <td><?php echo htmlspecialchars($user['MemberType']); ?></td>
            </tr>

            <tr>
                <th style="text-align: center"><i class="bi bi-pie-chart"></i> سهم در پروژه</th>
                <td><?php echo htmlspecialchars($user['ProjectShare']); ?></td>
            </tr>

            <tr>
                <th style="text-align: center"><i class="bi bi-address"></i> آدرس </th>
                <td><?php echo htmlspecialchars($user['Address'] ?? ''); ?></td>
            </tr>
            </tbody>
        </table>
    </div>
</div>
