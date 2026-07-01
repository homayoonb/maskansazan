<?php
// users-list.php
// صفحه اختصاصی نمایش لیست کاربران - نسخه اصلاح شده

// ==================== تنظیمات اولیه ====================
session_start();

// بررسی ورود ادمین
// if (empty($_SESSION['user_id']) || empty($_SESSION['username'])) {
//     header('Location: loginAdmin.php');
//     exit;
// }

// فعال‌سازی نمایش خطا برای دیباگ
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// ==================== اتصال به دیتابیس ====================
$db = new mysqli("localhost", "maskanhb_maskansazan", "NJ)B*$@y3R9P0Oyg", "maskanhb_maskansazan");

if ($db->connect_error) {
    die("<div class='container mt-5'>
            <div class='alert alert-danger'>
                <h4>خطا در اتصال به دیتابیس</h4>
                <p>" . htmlspecialchars($db->connect_error) . "</p>
                <p>لطفاً تنظیمات دیتابیس را بررسی کنید.</p>
            </div>
        </div>");
}

$db->set_charset("utf8mb4");

// ==================== دریافت پارامترهای فیلتر ====================
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// ==================== ساخت کوئری داینامیک ====================
$whereConditions = [];
$queryParams = [];
$paramTypes = '';

// فیلتر جستجو
if (!empty($search)) {
    $searchTerm = "%$search%";
    $whereConditions[] = "(first_name LIKE ? OR last_name LIKE ? OR national_code LIKE ? OR phone LIKE ? OR father_name LIKE ?)";
    $queryParams = array_merge($queryParams, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    $paramTypes .= 'sssss';
}

// فیلتر وضعیت
if ($status === 'active') {
    $whereConditions[] = "(main_member = 'اصلی' OR main_member = 'main' OR main_member = '1' OR main_member = 'true')";
} elseif ($status === 'member') {
    $whereConditions[] = "(main_member != 'اصلی' AND main_member != 'main' AND main_member != '1' AND main_member != 'true')";
}

// ساخت WHERE کلاز
$whereClause = '';
if (!empty($whereConditions)) {
    $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
}

// ==================== دریافت لیست کاربران ====================
$users = [];
$totalUsers = 0;
$primaryKeyField = 'id'; // مقدار پیش‌فرض

// اول بررسی کن آیا جدول project_members وجود دارد
$table_check = $db->query("SHOW TABLES LIKE 'project_members'");

if (!$table_check || $table_check->num_rows === 0) {
    $error = "جدول 'project_members' در دیتابیس وجود ندارد!";
} else {
    // ابتدا ساختار جدول را بررسی کنیم
    $structure = $db->query("DESCRIBE project_members");
    $columns = [];
    if ($structure) {
        while ($row = $structure->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
        
        // بررسی کنیم آیا فیلد id وجود دارد
        if (in_array('id', $columns)) {
            $primaryKeyField = 'id';
        } elseif (in_array('member_id', $columns)) {
            $primaryKeyField = 'member_id';
        } elseif (in_array('user_id', $columns)) {
            $primaryKeyField = 'user_id';
        }
    }
    
    // کوئری اصلی برای گرفتن تعداد کل
    $countQuery = "SELECT COUNT(*) as total FROM project_members $whereClause";
    
    if (!empty($queryParams)) {
        $stmt = $db->prepare($countQuery);
        if ($stmt) {
            $stmt->bind_param($paramTypes, ...$queryParams);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $totalUsers = $row['total'];
            $stmt->close();
        }
    } else {
        $result = $db->query($countQuery);
        if ($result) {
            $row = $result->fetch_assoc();
            $totalUsers = $row['total'];
        }
    }

    // کوئری برای گرفتن کاربران با pagination
    $query = "SELECT 
                $primaryKeyField,
                first_name,
                last_name,
                national_code,
                phone,
                project_id,
                main_member,
                share,
                introducer,
                father_name,
                birth_certificate,
                birth_date,
                birth_place
              FROM project_members 
              $whereClause
              ORDER BY $primaryKeyField DESC
              LIMIT ? OFFSET ?";

    // اضافه کردن پارامترهای pagination
    $finalQueryParams = $queryParams;
    $finalParamTypes = $paramTypes . 'ii';
    $finalQueryParams[] = $limit;
    $finalQueryParams[] = $offset;

    if (!empty($queryParams)) {
        $stmt = $db->prepare($query);
        if ($stmt) {
            $stmt->bind_param($finalParamTypes, ...$finalQueryParams);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
            $stmt->close();
        }
    } else {
        // اگر پارامتری نداشتیم
        $finalQuery = "SELECT 
                $primaryKeyField,
                first_name,
                last_name,
                national_code,
                phone,
                project_id,
                main_member,
                share,
                introducer,
                father_name,
                birth_certificate,
                birth_date,
                birth_place
              FROM project_members 
              ORDER BY $primaryKeyField DESC 
              LIMIT $limit OFFSET $offset";
        
        $result = $db->query($finalQuery);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $users[] = $row;
            }
        }
    }
}

// ==================== دریافت لیست پروژه‌ها ====================
$projects = [];
$projects_result = $db->query("SELECT id, name FROM projects ORDER BY name");
if ($projects_result && $projects_result->num_rows > 0) {
    while ($row = $projects_result->fetch_assoc()) {
        $projects[$row['id']] = $row['name'];
    }
}

// بستن اتصال دیتابیس
$db->close();

// ==================== محاسبات آماری ====================
$totalCount = count($users);
$activeCount = 0;
$totalShare = 0;

foreach ($users as $user) {
    $mainMember = strtolower(trim($user['main_member'] ?? ''));
    if (in_array($mainMember, ['اصلی', 'main', '1', 'true'])) {
        $activeCount++;
    }
    $totalShare += floatval($user['share'] ?? 0);
}

$avgShare = $totalCount > 0 ? round($totalShare / $totalCount, 1) : 0;

// ==================== توابع کمکی ====================
function safeEcho($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function formatDate($date) {
    if (!$date || $date == '0000-00-00 00:00:00' || $date == '0000-00-00') return '-';
    return date('Y/m/d', strtotime($date));
}

function formatShare($share) {
    if (!$share || !is_numeric($share)) return '۰٪';
    return number_format($share, 1) . '٪';
}

function getUserAvatar($firstName, $lastName) {
    $firstLetter = mb_substr($firstName ?? '', 0, 1, 'UTF-8');
    if (empty($firstLetter)) {
        $firstLetter = mb_substr($lastName ?? '', 0, 1, 'UTF-8');
    }
    if (empty($firstLetter)) {
        $firstLetter = '?';
    }
    return strtoupper($firstLetter);
}

function getProjectName($projectId, $projects) {
    return $projects[$projectId] ?? 'بدون پروژه';
}

function getUserStatusBadge($mainMember) {
    $mainMember = strtolower(trim($mainMember ?? ''));
    if (in_array($mainMember, ['اصلی', 'main', '1', 'true'])) {
        return '<span class="badge bg-success">اصلی</span>';
    } else {
        return '<span class="badge bg-info">عضو</span>';
    }
}

// محاسبه تعداد صفحات برای pagination
$totalPages = ceil($totalUsers / $limit);
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لیست کاربران - سامانه مسکن‌سازان</title>
    
    <!-- Bootstrap 5 RTL -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #4f46e5;
            --success-color: #10b981;
            --info-color: #0ea5e9;
            --danger-color: #ef4444;
        }
        
        body {
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            font-family: 'Tahoma', 'Segoe UI', sans-serif;
            color: #1e293b;
            min-height: 100vh;
        }
        
        .header {
            background: linear-gradient(135deg, #0f172a, #1e293b);
            color: white;
            padding: 25px 0;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            border-bottom: 4px solid var(--primary-color);
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            border-top: 4px solid var(--primary-color);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-number {
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--primary-color);
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: #64748b;
            font-weight: 500;
        }
        
        .stat-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }
        
        .search-box {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.06);
            border: 1px solid #e2e8f0;
        }
        
        .table-container {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 6px 18px rgba(0,0,0,0.08);
            margin-bottom: 30px;
        }
        
        .table-custom {
            margin: 0;
        }
        
        .table-custom thead {
            background: linear-gradient(135deg, #f1f5f9, #e2e8f0);
        }
        
        .table-custom thead th {
            border: none;
            color: #475569;
            font-weight: 700;
            padding: 18px 15px;
            border-bottom: 2px solid #cbd5e1;
        }
        
        .table-custom tbody tr {
            transition: all 0.2s ease;
        }
        
        .table-custom tbody tr:hover {
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            transform: scale(1.001);
        }
        
        .table-custom tbody td {
            padding: 16px 15px;
            border-color: #f1f5f9;
            vertical-align: middle;
        }
        
        .table-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), #7c3aed);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1rem;
            box-shadow: 0 3px 8px rgba(79, 70, 229, 0.3);
        }
        
        .user-name {
            font-weight: 700;
            color: #1e293b;
        }
        
        .user-detail {
            font-size: 0.85rem;
            color: #64748b;
        }
        
        .badge-status {
            padding: 6px 14px;
            border-radius: 25px;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        
        .share-badge {
            background: linear-gradient(135deg, var(--info-color), #0d9488);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .action-btn {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 0 2px;
            transition: all 0.2s ease;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .empty-state {
            background: white;
            border-radius: 20px;
            padding: 80px 30px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(0,0,0,0.05);
            border: 2px dashed #cbd5e1;
        }
        
        .empty-state-icon {
            font-size: 5rem;
            color: #cbd5e1;
            margin-bottom: 25px;
            opacity: 0.7;
        }
        
        .pagination-container {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        
        .page-link {
            border-radius: 10px !important;
            margin: 0 3px;
            border: 1px solid #e2e8f0;
            color: #475569;
            font-weight: 500;
        }
        
        .page-item.active .page-link {
            background: linear-gradient(135deg, var(--primary-color), #7c3aed);
            border-color: var(--primary-color);
        }
        
        .export-buttons {
            background: white;
            border-radius: 15px;
            padding: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }
        
        @media (max-width: 768px) {
            .header h1 {
                font-size: 1.5rem;
            }
            
            .stat-number {
                font-size: 1.8rem;
            }
            
            .table-responsive {
                font-size: 0.9rem;
            }
            
            .action-btn {
                width: 32px;
                height: 32px;
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>

<!-- هدر -->
<div class="header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="mb-2">
                    <i class="bi bi-people-fill me-2"></i>لیست کاربران سامانه
                </h1>
                <p class="mb-0 text-light opacity-85">
                    <i class="bi bi-info-circle me-1"></i>
                    مدیریت کامل کاربران پروژه‌های مسکن‌سازان - نمایش <?= number_format($totalUsers) ?> کاربر
                </p>
            </div>
            <div class="col-md-4 text-start">
                <div class="d-flex gap-2 justify-content-md-start justify-content-center">
                    <a href="dashboardAdmin.php" class="btn btn-outline-light">
                        <i class="bi bi-arrow-right me-1"></i> بازگشت
                    </a>
                    <a href="add-user.php" class="btn btn-success">
                        <i class="bi bi-plus-lg me-1"></i> کاربر جدید
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <!-- نمایش خطا در صورت عدم وجود جدول -->
    <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <h4 class="alert-heading">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>خطا در بارگذاری اطلاعات
            </h4>
            <p><?= $error ?></p>
            <hr>
            <p class="mb-0">
                لطفاً از وجود جدول 'project_members' در دیتابیس اطمینان حاصل کنید.
            </p>
        </div>
    <?php endif; ?>
    
    <!-- نمایش اطلاعات دیباگ (در صورت نیاز) -->
    <?php if (isset($_GET['debug']) && $_GET['debug'] == 1): ?>
        <div class="alert alert-info">
            <h5>اطلاعات دیباگ:</h5>
            <p>کلید اصلی: <?= $primaryKeyField ?></p>
            <p>تعداد کاربران در دیتابیس: <?= $totalUsers ?></p>
            <p>تعداد کاربران واکشی شده: <?= count($users) ?></p>
            <p>مشاهده 5 کاربر اول:</p>
            <pre><?php 
                $count = 0;
                foreach ($users as $user) {
                    if ($count++ < 5) {
                        print_r($user);
                        echo "\n---\n";
                    }
                }
            ?></pre>
        </div>
    <?php endif; ?>
    
    <!-- کارت‌های آمار -->
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-number"><?= number_format($totalUsers) ?></div>
                        <div class="stat-label">کل کاربران</div>
                    </div>
                    <div class="stat-icon text-primary">
                        <i class="bi bi-people-fill"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-number"><?= number_format($activeCount) ?></div>
                        <div class="stat-label">کاربران اصلی</div>
                    </div>
                    <div class="stat-icon text-success">
                        <i class="bi bi-person-check-fill"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-number"><?= number_format($totalCount - $activeCount) ?></div>
                        <div class="stat-label">اعضای پروژه</div>
                    </div>
                    <div class="stat-icon text-secondary">
                        <i class="bi bi-person-fill"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-number"><?= $avgShare ?>%</div>
                        <div class="stat-label">میانگین سهم</div>
                    </div>
                    <div class="stat-icon text-info">
                        <i class="bi bi-percent"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- باکس جستجو و فیلتر -->
    <div class="search-box">
        <form method="GET" action="" class="row g-3">
            <input type="hidden" name="debug" value="<?= isset($_GET['debug']) ? $_GET['debug'] : 0 ?>">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0">
                        <i class="bi bi-search text-muted"></i>
                    </span>
                    <input type="text" name="search" class="form-control border-start-0" 
                           placeholder="جستجوی نام، نام خانوادگی، کد ملی، تلفن یا نام پدر..." 
                           value="<?= safeEcho($search) ?>">
                </div>
            </div>
            
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">همه وضعیت‌ها</option>
                    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>کاربران اصلی</option>
                    <option value="member" <?= $status === 'member' ? 'selected' : '' ?>>اعضای پروژه</option>
                </select>
            </div>
            
            <div class="col-md-4">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">
                        <i class="bi bi-funnel me-1"></i> اعمال فیلتر
                    </button>
                    <a href="users-list.php" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i>
                    </a>
                    <button type="button" class="btn btn-success" onclick="window.location.href='add-user.php'">
                        <i class="bi bi-plus-lg"></i>
                    </button>
                </div>
            </div>
        </form>
        
        <?php if (!empty($search) || !empty($status)): ?>
            <div class="mt-3">
                <small class="text-muted">
                    <i class="bi bi-info-circle me-1"></i>
                    در حال نمایش نتایج فیلتر شده 
                    <?php if (!empty($search)): ?>
                        | جستجو: "<?= safeEcho($search) ?>"
                    <?php endif; ?>
                    <?php if (!empty($status)): ?>
                        | وضعیت: <?= $status === 'active' ? 'اصلی' : 'عضو' ?>
                    <?php endif; ?>
                </small>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- دکمه‌های خروجی -->
    <div class="export-buttons mb-3">
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary">
                <i class="bi bi-printer me-1"></i> چاپ لیست
            </button>
            <button class="btn btn-outline-success">
                <i class="bi bi-file-earmark-excel me-1"></i> خروجی Excel
            </button>
            <button class="btn btn-outline-danger">
                <i class="bi bi-file-earmark-pdf me-1"></i> خروجی PDF
            </button>
        </div>
    </div>
    
    <!-- جدول کاربران -->
    <?php if (!empty($users)): ?>
        <div class="table-container">
            <div class="table-responsive">
                <table class="table table-custom table-hover">
                    <thead>
                        <tr>
                            <th width="50">#</th>
                            <th>اطلاعات کاربر</th>
                            <th width="100">وضعیت</th>
                            <th width="120">کد ملی</th>
                            <th width="120">تلفن</th>
                            <th width="100">سهم</th>
                            <th>پروژه</th>
                            <th width="140">عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $counter = $offset + 1; ?>
                        <?php foreach ($users as $user): ?>
                            <?php
                            $firstName = $user['first_name'] ?? '';
                            $lastName = $user['last_name'] ?? '';
                            $fullName = trim($firstName . ' ' . $lastName);
                            $nationalCode = $user['national_code'] ?? '';
                            $phone = $user['phone'] ?? '';
                            $share = $user['share'] ?? '';
                            $projectId = $user['project_id'] ?? '';
                            $mainMember = $user['main_member'] ?? '';
                            $fatherName = $user['father_name'] ?? '';
                            $userId = $user[$primaryKeyField] ?? '';
                            
                            $avatarLetter = getUserAvatar($firstName, $lastName);
                            $projectName = getProjectName($projectId, $projects);
                            ?>
                            
                            <tr>
                                <td class="text-muted fw-bold"><?= $counter++ ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="table-avatar me-3">
                                            <?= $avatarLetter ?>
                                        </div>
                                        <div>
                                            <div class="user-name mb-1">
                                                <?= safeEcho($fullName) ?>
                                            </div>
                                            <?php if (!empty($fatherName)): ?>
                                                <div class="user-detail">
                                                    <i class="bi bi-person-vcard me-1"></i>
                                                    <?= safeEcho($fatherName) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td><?= getUserStatusBadge($mainMember) ?></td>
                                <td>
                                    <?php if (!empty($nationalCode)): ?>
                                        <div class="text-center">
                                            <code class="bg-light px-2 py-1 rounded"><?= safeEcho($nationalCode) ?></code>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($phone)): ?>
                                        <a href="tel:<?= safeEcho($phone) ?>" class="text-decoration-none text-dark d-block">
                                            <i class="bi bi-telephone me-1"></i>
                                            <?= safeEcho($phone) ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($share) && is_numeric($share)): ?>
                                        <span class="share-badge"><?= formatShare($share) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <?= safeEcho($projectName) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex justify-content-center">
                                        <a href="?tab=documents&user_id=<?= $userId ?>" 
                                           class="btn btn-info btn-sm action-btn" 
                                           title="مدارک">
                                            <i class="bi bi-files"></i>
                                        </a>
                                        <a href="edit-user.php?id=<?= $userId ?>" 
                                           class="btn btn-warning btn-sm action-btn" 
                                           title="ویرایش">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button class="btn btn-danger btn-sm action-btn" 
                                                onclick="deleteUser(<?= $userId ?>)" 
                                                title="حذف">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination-container mt-3">
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center mb-0">
                        <!-- Previous Button -->
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?<?php
                                $params = $_GET;
                                $params['page'] = $page - 1;
                                echo http_build_query($params);
                            ?>" aria-label="Previous">
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                        
                        <!-- Page Numbers -->
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="?<?php
                                    $params = $_GET;
                                    $params['page'] = $i;
                                    echo http_build_query($params);
                                ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <!-- Next Button -->
                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?<?php
                                $params = $_GET;
                                $params['page'] = $page + 1;
                                echo http_build_query($params);
                            ?>" aria-label="Next">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
                <div class="text-center mt-2">
                    <small class="text-muted">
                        صفحه <?= $page ?> از <?= $totalPages ?> | نمایش <?= count($users) ?> کاربر از <?= number_format($totalUsers) ?> کاربر
                    </small>
                </div>
            </div>
        <?php endif; ?>
        
    <?php else: ?>
        <!-- حالت خالی -->
        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="bi bi-people"></i>
            </div>
            <h3 class="mb-3 text-muted">کاربری یافت نشد</h3>
            <p class="text-muted mb-4">
                <?php if (!empty($search) || !empty($status)): ?>
                    هیچ کاربری مطابق با فیلترهای اعمال شده یافت نشد.
                <?php else: ?>
                    هنوز هیچ کاربری در سیستم ثبت نشده است.
                <?php endif; ?>
            </p>
            <div class="d-flex gap-3 justify-content-center">
                <?php if (!empty($search) || !empty($status)): ?>
                    <a href="users-list.php" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle me-1"></i> حذف فیلتر
                    </a>
                <?php endif; ?>
                <a href="add-user.php" class="btn btn-success btn-lg">
                    <i class="bi bi-plus-lg me-2"></i> افزودن کاربر جدید
                </a>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- دیباگ لینک (برای مدیر) -->
    <div class="text-center mt-4">
        <small class="text-muted">
            <a href="?debug=<?= isset($_GET['debug']) && $_GET['debug'] == 1 ? '0' : '1' ?>" 
               class="text-decoration-none">
                <i class="bi bi-bug"></i> حالت دیباگ: <?= isset($_GET['debug']) && $_GET['debug'] == 1 ? 'غیرفعال' : 'فعال' ?>
            </a>
        </small>
    </div>
</div>

<!-- اسکریپت‌ها -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function deleteUser(userId) {
    if (confirm('⚠️ آیا از حذف این کاربر اطمینان دارید؟\nاین عمل غیرقابل برگشت است.')) {
        fetch('delete-user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'id=' + userId + '&confirm=true'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ کاربر با موفقیت حذف شد.');
                location.reload();
            } else {
                alert('❌ خطا در حذف کاربر: ' + data.message);
            }
        })
        .catch(error => {
            alert('⚠️ خطا در ارتباط با سرور');
            console.error('Error:', error);
        });
    }
}

// فعال‌سازی جستجوی سریع
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.querySelector('input[name="search"]');
    
    // Enter برای جستجو
    searchInput.addEventListener('keyup', function(e) {
        if (e.key === 'Enter') {
            this.form.submit();
        }
    });
    
    // دکمه چاپ
    document.querySelector('.btn-outline-primary').addEventListener('click', function() {
        window.print();
    });
});
</script>
</body>
</html>