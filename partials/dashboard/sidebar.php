<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

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
];
?>
<style>
    .user-avatar {
        width: 90px;
        height: 90px;
        border-radius: 50%;
        background-color: rgba(255,255,255,0.2);
        margin: 0 auto 10px;
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
</style>
<div class="sidebar">
<!--    <div class="user-info">-->
<!--        <div class="user-avatar bg-white text-primary d-flex align-items-center justify-content-center" style="align-content: center;">-->
<!--            <i class="bi bi-person-circle" style="font-size: 60px"></i>-->
<!--        </div>-->
<!--        <h5>--><?php //echo htmlspecialchars($user['FullName']); ?><!--</h5>-->
<!--        <div class="profile-badge"> عضویت: --><?php //echo htmlspecialchars($_SESSION['project_name']); ?><!--</div>-->
<!--    </div>-->
    <ul class="sidebar-menu">
        <li><a href="/maskansazan/dashboard.php"><i class="bi bi-speedometer2"></i> داشبورد</a></li>
        <li><a href="/maskansazan/profileuser.php"><i class="bi bi-person"></i> پروفایل کاربری</a></li>
        <li><a href="/maskansazan/documents.php"><i class="bi bi-file-earmark-text"></i> مدارک و مستندات</a></li>
<li class="nav-item dropdown" id="payments-dropdown">
    <button class="btn btn-link nav-link d-flex align-items-center justify-content-between w-100 text-decoration-none" 
            type="button" 
            data-bs-toggle="collapse" 
            data-bs-target="#payments-menu" 
            aria-expanded="false" 
            aria-controls="payments-menu">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-credit-card"></i>
            <span>پرداخت‌ها</span>
        </div>
        <i class="bi bi-plus dropdown-icon"></i>
    </button>
    <div class="collapse" id="payments-menu">
        <div class="dropdown-custom-menu">
            <a href="/maskansazan/member_statements.php"><i class="bi bi-cash-stack"></i>مالی</a>
        </div>
    </div>
</li>

        <li><a href="/maskansazan/ticket.php"><i class="bi bi-chat-left-text"></i> تیکت‌های پشتیبانی</a></li>
        <li><a href="/maskansazan/logout.php"><i class="bi bi-box-arrow-left"></i> خروج</a></li>
    </ul>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>


<script>
document.addEventListener('DOMContentLoaded', function() {
    const paymentsMenu = document.getElementById('payments-menu');
    
    paymentsMenu.addEventListener('show.bs.collapse', function() {
        const icon = document.querySelector('#payments-dropdown .dropdown-icon');
        icon.classList.remove('bi-plus');
        icon.classList.add('bi-dash');
    });
    
    paymentsMenu.addEventListener('hide.bs.collapse', function() {
        const icon = document.querySelector('#payments-dropdown .dropdown-icon');
        icon.classList.remove('bi-dash');
        icon.classList.add('bi-plus');
    });
});
</script>