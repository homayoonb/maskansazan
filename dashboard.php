<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// بررسی لاگین
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: groupmembers.php');
    exit();
}

// داده‌های کاربر
$user = [
    'FullName' => $_SESSION['full_name'] ?? 'کاربر',
    'FirstName' => $_SESSION['first_name'] ?? '',
    'LastName' => $_SESSION['last_name'] ?? '',
    'MemberType' => $_SESSION['main_member'] ?? 'عضو',
    'NationalCode' => $_SESSION['NationalCode'] ?? '----',
    'PhoneNumber' => $_SESSION['phone'] ?? '09123456789',
    'ProjectShare' => $_SESSION['share'] ?? '150 مترمربع',
    'ProjectID' => $_SESSION['project_id'] ?? '',
    'ProjectName' => $_SESSION['project_name'] ?? 'پروژه',
    'Address' => $_SESSION['address'] ?? '',
];

$currentDate = date('Y-m-d');

// ذخیره زمان ورود در session
if (!isset($_SESSION['last_login_time'])) {
    $_SESSION['last_login_time'] = time();
    $_SESSION['last_login_display'] = 'اولین ورود';
} else {
    // تبدیل timestamp به تاریخ فارسی
    $_SESSION['last_login_display'] = date('Y/m/d H:i', $_SESSION['last_login_time']);
}

// محاسبه زمان گذشته از آخرین ورود
$timeAgo = '';
if (isset($_SESSION['last_login_time'])) {
    $diff = time() - $_SESSION['last_login_time'];
    if ($diff < 60) {
        $timeAgo = 'همین الان';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        $timeAgo = $minutes . ' دقیقه قبل';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        $timeAgo = $hours . ' ساعت قبل';
    } else {
        $days = floor($diff / 86400);
        $timeAgo = $days . ' روز قبل';
    }
}

// بروزرسانی زمان ورود برای دفعه بعد
$_SESSION['last_login_time'] = time();
$user = [
        'FullName'     => $_SESSION['full_name'] ?? '',
        'NationalCode' => $_SESSION['NationalCode'] ?? '',
        'MemberType'   => $_SESSION['main_member'] ?? '',
        'PhoneNumber'  => $_SESSION['phone'] ?? '',
        'ProjectShare' => $_SESSION['share'] ?? '',
        'Address' => $_SESSION['address'] ?? '',
];

ob_start();
?>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
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
        <?php echo htmlspecialchars($user['FullName']); ?> ، خوش آمدید
    </div>
    <br>
    <br>
    <!-- تب ها -->
    <ul class="nav nav-tabs custom-nav" role="tablist">
        <li class="active">
            <a href="#profile" data-toggle="tab"><i class="fa fa-user"></i> پروفایل</a>
        </li>
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

    <!-- محتوای تب ها -->
    <div class="tab-content custom-content tab-scroll">
        <div id="profile" class="tab-pane fade in active">
            <h4>اطلاعات پروفایل</h4>
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
                            <th style="text-align: center"><i class="bi bi-address"></i> آدرس </th>
                            <td><?php echo htmlspecialchars($user['Address'] ?? ''); ?></td>
                        </tr>

                        <tr>
                            <th style="text-align: center"><i class="bi bi-pie-chart"></i> سهم در پروژه</th>
                            <td><?php echo htmlspecialchars($user['ProjectShare']); ?></td>
                        </tr>


                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div id="documents" class="tab-pane fade">
            <h4>لیست مدارک</h4>
            <?php
               include 'documents.php'
            ?>
        </div>
        <div id="finance" class="tab-pane fade">
            <h4>لیست قست های پرداخت شده</h4>
            <?php
             include 'member_statements.php';
            ?>
        </div>
        <div id="ticket" class="tab-pane fade">
            <h4>تیکت</h4>
            <?php
               include 'ticket.php'
            ?>
        </div>
    </div>
</div>






<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<?php
$content = ob_get_clean();
include 'partials/layout.php';
include 'partials/footer.php'
?>

<!-- کارت‌ها -->
<!--    <div class="row">-->
<!--        <div class="col-xl-3 col-md-6 mb-4">-->
<!--            <div class="card stat-card primary">-->
<!--                <div class="card-body d-flex justify-content-between align-items-center">-->
<!--                    <div>-->
<!--                        <div class="text-xs font-weight-bold text-primary mb-1">سهم پروژه</div>-->
<!--                        <div class="h5 mb-0 font-weight-bold">--><?php //echo htmlspecialchars($user['ProjectShare']); ?><!--</div>-->
<!--                    </div>-->
<!--                    <div class="icon-circle bg-primary"><i class="bi bi-house-door"></i></div>-->
<!--                </div>-->
<!--            </div>-->
<!--        </div>-->
<!--        <div class="col-xl-3 col-md-6 mb-4">-->
<!--            <div class="card stat-card success">-->
<!--                <div class="card-body d-flex justify-content-between align-items-center">-->
<!--                    <div>-->
<!--                        <div class="text-xs font-weight-bold text-success mb-1">وضعیت پرداخت</div>-->
<!--                        <div class="h5 mb-0 font-weight-bold">75%</div>-->
<!--                    </div>-->
<!--                    <div class="icon-circle bg-success"><i class="bi bi-credit-card"></i></div>-->
<!--                </div>-->
<!--            </div>-->
<!--        </div>-->
<!--        <div class="col-xl-3 col-md-6 mb-4">-->
<!--            <div class="card stat-card info">-->
<!--                <div class="card-body d-flex justify-content-between align-items-center">-->
<!--                    <div>-->
<!--                        <div class="text-xs font-weight-bold text-info mb-1">تیکت‌های فعال</div>-->
<!--                        <div class="h5 mb-0 font-weight-bold">2</div>-->
<!--                    </div>-->
<!--                    <div class="icon-circle bg-info"><i class="bi bi-chat-left-text"></i></div>-->
<!--                </div>-->
<!--            </div>-->
<!--        </div>-->
<!--    </div>-->
<!---->
<!--    <!-- اطلاعات کاربر و فعالیت‌ها -->-->
<!--    <div class="row">-->
<!---->
<!--        <div class="col-lg-6">-->
<!--            <div class="card">-->
<!--                <div class="card-header">آخرین فعالیت‌ها</div>-->
<!--                <div class="card-body">-->
<!--                    --><?php //foreach($activities as $act): ?>
<!--                        <div class="activity-item">-->
<!--                            <div class="d-flex justify-content-between">-->
<!--                                <strong>--><?php //echo $act['title']; ?><!--</strong>-->
<!--                                <span class="text-muted small">--><?php //echo htmlspecialchars($_SESSION['last_login_display']); ?><!--</span>-->
<!--                            </div>-->
<!--                            <p class="mb-0 text-muted">--><?php //echo $act['desc']; ?><!--</p>-->
<!--                        </div>-->
<!--                    --><?php //endforeach; ?>
<!--                </div>-->
<!--            </div>-->
<!--        </div>-->
<!--    </div>-->