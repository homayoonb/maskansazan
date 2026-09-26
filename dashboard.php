<?php


// ۱. شروع سشن
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// هشدار: در محیط عملیاتی (سرور اصلی)، این دو خط را حذف یا کامنت کنید
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ۲. بررسی لاگین (هدایت به صفحه لاگین، نه groupmembers)
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: groupmembers.php');
    exit();
}

// ۳. منطق محاسبه زمان آخرین ورود
$currentDate = date('Y-m-d');
if (!isset($_SESSION['last_login_time'])) {
    $_SESSION['last_login_display'] = 'اولین ورود';
    $timeAgo = 'اولین ورود';
} else {
    $_SESSION['last_login_display'] = date('Y/m/d H:i', $_SESSION['last_login_time']);
    $diff = time() - $_SESSION['last_login_time'];

    if ($diff < 60) {
        $timeAgo = 'همین الان';
    } elseif ($diff < 3600) {
        $timeAgo = floor($diff / 60) . ' دقیقه قبل';
    } elseif ($diff < 86400) {
        $timeAgo = floor($diff / 3600) . ' ساعت قبل';
    } else {
        $timeAgo = floor($diff / 86400) . ' روز قبل';
    }
}
$_SESSION['last_login_time'] = time();

ob_start();
?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .nav-tabs {
            border-bottom: 2px solid #ddd;
        }

        .nav-tabs > li > a {
            font-weight: bold;
            color: #444;
            padding: 12px 20px;
        }

        .nav-tabs > li.active > a {
            background-color: #f5f5f5;
            border-top: 3px solid #007bff;
            color: #007bff;
        }
        .tabs-container{
            background:#fff;
            border-radius:12px;
            overflow:hidden;
        }

        /* تب‌ها ثابت */
        .custom-nav{
            position: sticky;
            top: 0;
            z-index: 10;
            background:#fff;
            border-bottom:1px solid #eee;
        }

        /* فقط محتوا اسکرول بخورد */
        .tab-scroll{
            max-height: 70vh;
            overflow-y: auto;
            padding:15px;
        }
        .welcome-text{
            font-size:16px;
            font-weight:500;
            color:#333;
        }

    </style>

    <div class="tabs-container">
        <div class="welcome-text">
            عضو محترم
            <?php echo htmlspecialchars($_SESSION['full_name']); ?> ، خوش آمدید
        </div>
        <ul class="nav nav-tabs custom-nav" role="tablist">
            <li class="active"><a href="#profile" data-toggle="tab">پروفایل</a></li>
            <li>
                <a href="#documents" data-toggle="tab"><i class="fa fa-file-text"></i> مدارک و مستندات</a>
            </li>
            <li>
                <a href="#finance" data-toggle="tab"><i class="fa fa-money"></i> مالی</a>
            </li>
            <li>
                <a href="#ticket" data-toggle="tab"><i class="fa fa-ticket"></i> تیکت</a>
            </li>
        </ul>
        <div class="tab-content custom-content tab-scroll">
            <div id="profile" class="tab-pane fade in active">
                <h4>اطلاعات پروفایل</h4>
                <?php
                if (file_exists('profile_user.php')) {
                    include 'profile_user.php';
                } else {
                    echo '<div class="alert alert-danger text-center">فایل profile_user.php یافت نشد.</div>';
                }
                ?>
            </div>


            <div id="documents" class="tab-pane fade">
                <h4>لیست مدارک</h4>
                <?php
                if (file_exists('documents.php')) {
                    include 'documents.php';
                } else {
                    echo '<div class="alert alert-danger text-center">فایل documents.php یافت نشد.</div>';
                }
                ?>
            </div>

            <div id="finance" class="tab-pane fade">
                <h4>لیست قسط های پرداخت شده</h4>
                <?php
                if (file_exists('member_statements.php')) {
                    include 'member_statements.php';
                } else {
                    echo '<div class="alert alert-danger text-center">فایل member_statements.php یافت نشد.</div>';
                }
                ?>
            </div>

            <div id="ticket" class="tab-pane fade">
                <h4>تیکت</h4>

            </div>
        </div>

    </div>





    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php
$content = ob_get_clean();
require_once 'partials/layout.php';
?>