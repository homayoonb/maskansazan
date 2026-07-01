<?php
// cheque.php
// صفحه اختصاصی مدیریت چک‌ها - نسخه مشابه users-list.php

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
    $whereConditions[] = "(cheque_number LIKE ? OR bank_name LIKE ? OR account_owner LIKE ? OR receiver_name LIKE ?)";
    $queryParams = array_merge($queryParams, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    $paramTypes .= 'ssss';
}

// فیلتر وضعیت
if (!empty($status) && in_array($status, ['pending', 'cleared', 'returned', 'cancelled'])) {
    $whereConditions[] = "status = ?";
    $queryParams[] = $status;
    $paramTypes .= 's';
}

// ساخت WHERE کلاز
$whereClause = '';
if (!empty($whereConditions)) {
    $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
}

// ==================== دریافت لیست چک‌ها ====================
$cheques = [];
$totalCheques = 0;
$primaryKeyField = 'id';

// بررسی وجود جدول cheques
$table_check = $db->query("SHOW TABLES LIKE 'cheque_transactions'");

if (!$table_check || $table_check->num_rows === 0) {
    $error = "جدول 'cheques' در دیتابیس وجود ندارد!";
} else {
    // کوئری اصلی برای گرفتن تعداد کل
    $countQuery = "SELECT COUNT(*) as total FROM cheque_transactions $whereClause";
    
    if (!empty($queryParams)) {
        $stmt = $db->prepare($countQuery);
        if ($stmt) {
            $stmt->bind_param($paramTypes, ...$queryParams);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $totalCheques = $row['total'];
            $stmt->close();
        }
    } else {
        $result = $db->query($countQuery);
        if ($result) {
            $row = $result->fetch_assoc();
            $totalCheques = $row['total'];
        }
    }

    // کوئری برای گرفتن چک‌ها با pagination
    $query = "SELECT *
              FROM cheque_transactions 
              $whereClause
              ORDER BY id DESC
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
                $cheques[] = $row;
            }
            $stmt->close();
        }
    } else {
        // اگر پارامتری نداشتیم
        $finalQuery = "SELECT *
              FROM cheque_transactions 
              ORDER BY id DESC 
              LIMIT $limit OFFSET $offset";
        
        $result = $db->query($finalQuery);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $cheques[] = $row;
            }
        }
    }
}

// بستن اتصال دیتابیس
$db->close();

// ==================== محاسبات آماری ====================
$totalCount = count($cheques);
$pendingAmount = 0;
$clearedAmount = 0;
$returnedAmount = 0;
$totalAmount = 0;

foreach ($cheques as $cheque) {
    $amount = floatval($cheque['amount'] ?? 0);
    $status = $cheque['status'] ?? '';
    
    $totalAmount += $amount;
    
    switch ($status) {
        case 'pending':
            $pendingAmount += $amount;
            break;
        case 'cleared':
            $clearedAmount += $amount;
            break;
        case 'returned':
            $returnedAmount += $amount;
            break;
    }
}

// ==================== توابع کمکی ====================
function safeEcho($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function formatNumber($number) {
    return number_format(floatval($number)) . ' ریال';
}

function formatDate($date) {
    if (!$date || $date == '0000-00-00 00:00:00' || $date == '0000-00-00') return '-';
    return date('Y/m/d', strtotime($date));
}

function getChequeAvatar($chequeNumber) {
    if (empty($chequeNumber)) return 'چ';
    $lastDigit = substr($chequeNumber, -1);
    return is_numeric($lastDigit) ? $lastDigit : substr($chequeNumber, 0, 1);
}

function getStatusBadge($status) {
    $statuses = [
        'pending' => ['label' => 'در انتظار', 'class' => 'warning'],
        'cleared' => ['label' => 'وصول شده', 'class' => 'success'],
        'returned' => ['label' => 'برگشتی', 'class' => 'danger'],
        'cancelled' => ['label' => 'ابطال شده', 'class' => 'secondary']
    ];
    
    $statusInfo = $statuses[$status] ?? ['label' => $status, 'class' => 'dark'];
    return '<span class="badge bg-' . $statusInfo['class'] . ' badge-status">' . $statusInfo['label'] . '</span>';
}

// محاسبه تعداد صفحات برای pagination
$totalPages = ceil($totalCheques / $limit);
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مدیریت چک‌ها - سامانه مسکن‌سازان</title>
    
    <!-- Bootstrap 5 RTL -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #4f46e5;
            --success-color: #10b981;
            --info-color: #0ea5e9;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
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
        
        .badge-status {
            padding: 6px 14px;
            border-radius: 25px;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        
        .amount-badge {
            background: linear-gradient(135deg, var(--info-color), #0d9488);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            font-family: monospace;
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
        
        .detail-item {
            padding: 8px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .detail-item:last-child {
            border-bottom: none;
        }
        
        .detail-label {
            color: #64748b;
            font-weight: 500;
            min-width: 120px;
        }
        
        .detail-value {
            color: #1e293b;
            font-weight: 600;
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
                    <i class="bi bi-credit-card me-2"></i>مدیریت چک‌ها
                </h1>
                <p class="mb-0 text-light opacity-85">
                    <i class="bi bi-info-circle me-1"></i>
                    ثبت، مدیریت و پیگیری چک‌های پروژه‌های مسکن‌سازان
                </p>
            </div>
            <div class="col-md-4 text-start">
                <div class="d-flex gap-2 justify-content-md-start justify-content-center">
                    <a href="dashboardAdmin.php" class="btn btn-outline-light">
                        <i class="bi bi-arrow-right me-1"></i> داشبورد
                    </a>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addChequeModal">
                        <i class="bi bi-plus-lg me-1"></i> چک جدید
                    </button>
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
                لطفاً از وجود جدول 'cheques' در دیتابیس اطمینان حاصل کنید.
            </p>
        </div>
    <?php endif; ?>
    
    <!-- کارت‌های آمار -->
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-number"><?= formatNumber($pendingAmount) ?></div>
                        <div class="stat-label">چک‌های در انتظار</div>
                    </div>
                    <div class="stat-icon text-warning">
                        <i class="bi bi-clock"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-number"><?= formatNumber($clearedAmount) ?></div>
                        <div class="stat-label">چک‌های وصول شده</div>
                    </div>
                    <div class="stat-icon text-success">
                        <i class="bi bi-check-circle"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-number"><?= formatNumber($returnedAmount) ?></div>
                        <div class="stat-label">چک‌های برگشتی</div>
                    </div>
                    <div class="stat-icon text-danger">
                        <i class="bi bi-x-circle"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="stat-number"><?= formatNumber($totalAmount) ?></div>
                        <div class="stat-label">مجموع مبالغ</div>
                    </div>
                    <div class="stat-icon text-primary">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- باکس جستجو و فیلتر -->
    <div class="search-box">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0">
                        <i class="bi bi-search text-muted"></i>
                    </span>
                    <input type="text" name="search" class="form-control border-start-0" 
                           placeholder="جستجوی شماره چک، بانک، صاحب حساب یا دریافت کننده..." 
                           value="<?= safeEcho($search) ?>">
                </div>
            </div>
            
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">همه وضعیت‌ها</option>
                    <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>در انتظار</option>
                    <option value="cleared" <?= $status === 'cleared' ? 'selected' : '' ?>>وصول شده</option>
                    <option value="returned" <?= $status === 'returned' ? 'selected' : '' ?>>برگشتی</option>
                    <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>ابطال شده</option>
                </select>
            </div>
            
            <div class="col-md-4">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">
                        <i class="bi bi-funnel me-1"></i> اعمال فیلتر
                    </button>
                    <a href="cheque.php" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i>
                    </a>
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addChequeModal">
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
                        | وضعیت: <?php 
                            $statusLabels = [
                                'pending' => 'در انتظار',
                                'cleared' => 'وصول شده',
                                'returned' => 'برگشتی',
                                'cancelled' => 'ابطال شده'
                            ];
                            echo $statusLabels[$status] ?? $status;
                        ?>
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
    
    <!-- جدول چک‌ها -->
    <?php if (!empty($cheques)): ?>
        <div class="table-container">
            <div class="table-responsive">
                <table class="table table-custom table-hover">
                    <thead>
                        <tr>
                            <th width="50">#</th>
                            <th>اطلاعات چک</th>
                            <th width="100">تاریخ</th>
                            <th width="120">مبلغ</th>
                            <th width="120">بانک</th>
                            <th width="100">وضعیت</th>
                            <th width="140">عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $counter = $offset + 1; ?>
                        <?php foreach ($cheques as $cheque): ?>
                            <?php
                            $chequeNumber = $cheque['cheque_number'] ?? '';
                            $amount = $cheque['amount'] ?? 0;
                            $bankName = $cheque['bank_name'] ?? '';
                            $accountOwner = $cheque['account_owner'] ?? '';
                            $receiverName = $cheque['receiver_name'] ?? '';
                            $purpose = $cheque['purpose'] ?? '';
                            $status = $cheque['status'] ?? '';
                            $chequeDate = $cheque['formatted_date'] ?? '';
                            $chequeId = $cheque['id'] ?? '';
                            ?>
                            
                            <tr>
                                <td class="text-muted fw-bold"><?= $counter++ ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="table-avatar me-3">
                                            <?= getChequeAvatar($chequeNumber) ?>
                                        </div>
                                        <div>
                                            <div class="user-name mb-1">
                                                <?= safeEcho($chequeNumber) ?>
                                            </div>
                                            <div class="user-detail">
                                                <small class="d-block">
                                                    <i class="bi bi-person me-1"></i>
                                                    <?= safeEcho($accountOwner) ?>
                                                </small>
                                                <?php if (!empty($receiverName)): ?>
                                                    <small class="d-block">
                                                        <i class="bi bi-person-arrow-up me-1"></i>
                                                        <?= safeEcho($receiverName) ?>
                                                    </small>
                                                <?php endif; ?>
                                                <?php if (!empty($purpose)): ?>
                                                    <small class="d-block text-info">
                                                        <i class="bi bi-tag me-1"></i>
                                                        <?= safeEcho($purpose) ?>
                                                    </small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($chequeDate)): ?>
                                        <span class="badge bg-light text-dark border">
                                            <?= safeEcho($chequeDate) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="amount-badge"><?= formatNumber($amount) ?></span>
                                </td>
                                <td>
                                    <?php if (!empty($bankName)): ?>
                                        <span class="text-dark fw-semibold"><?= safeEcho($bankName) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= getStatusBadge($status) ?></td>
                                <td>
                                    <div class="d-flex justify-content-center">
                                        <button class="btn btn-info btn-sm action-btn" 
                                                onclick="viewCheque(<?= $chequeId ?>)" 
                                                title="مشاهده جزئیات">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <?php if ($status === 'pending'): ?>
                                            <button class="btn btn-success btn-sm action-btn" 
                                                    onclick="updateStatus(<?= $chequeId ?>, 'cleared')" 
                                                    title="وصول شده">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                            <button class="btn btn-danger btn-sm action-btn" 
                                                    onclick="updateStatus(<?= $chequeId ?>, 'returned')" 
                                                    title="برگشتی">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        <?php endif; ?>
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
                        صفحه <?= $page ?> از <?= $totalPages ?> | نمایش <?= count($cheques) ?> چک از <?= number_format($totalCheques) ?> چک
                    </small>
                </div>
            </div>
        <?php endif; ?>
        
    <?php else: ?>
        <!-- حالت خالی -->
        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="bi bi-credit-card"></i>
            </div>
            <h3 class="mb-3 text-muted">چکی یافت نشد</h3>
            <p class="text-muted mb-4">
                <?php if (!empty($search) || !empty($status)): ?>
                    هیچ چکی مطابق با فیلترهای اعمال شده یافت نشد.
                <?php else: ?>
                    هنوز هیچ چکی در سیستم ثبت نشده است.
                <?php endif; ?>
            </p>
            <div class="d-flex gap-3 justify-content-center">
                <?php if (!empty($search) || !empty($status)): ?>
                    <a href="cheque.php" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle me-1"></i> حذف فیلتر
                    </a>
                <?php endif; ?>
                <button class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#addChequeModal">
                    <i class="bi bi-plus-lg me-2"></i> ثبت چک جدید
                </button>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- مودال ثبت چک جدید -->
<div class="modal fade" id="addChequeModal" tabindex="-1" aria-labelledby="addChequeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addChequeModalLabel">
                    <i class="bi bi-plus-circle me-2"></i>ثبت چک جدید
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addChequeForm" method="POST" action="process_cheque.php">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="cheque_number" class="form-label">شماره چک *</label>
                            <input type="text" class="form-control" id="cheque_number" name="cheque_number" required>
                        </div>
                        <div class="col-md-6">
                            <label for="cheque_date" class="form-label">تاریخ چک *</label>
                            <input type="date" class="form-control" id="cheque_date" name="cheque_date" required>
                        </div>
                        <div class="col-md-6">
                            <label for="amount" class="form-label">مبلغ (ریال) *</label>
                            <input type="number" class="form-control" id="amount" name="amount" required>
                        </div>
                        <div class="col-md-6">
                            <label for="bank_name" class="form-label">نام بانک *</label>
                            <input type="text" class="form-control" id="bank_name" name="bank_name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="account_owner" class="form-label">صاحب حساب</label>
                            <input type="text" class="form-control" id="account_owner" name="account_owner">
                        </div>
                        <div class="col-md-6">
                            <label for="national_code" class="form-label">کد ملی صاحب حساب</label>
                            <input type="text" class="form-control" id="national_code" name="national_code">
                        </div>
                        <div class="col-md-6">
                            <label for="receiver_name" class="form-label">دریافت کننده</label>
                            <input type="text" class="form-control" id="receiver_name" name="receiver_name">
                        </div>
                        <div class="col-md-6">
                            <label for="receiver_national_code" class="form-label">کد ملی دریافت کننده</label>
                            <input type="text" class="form-control" id="receiver_national_code" name="receiver_national_code">
                        </div>
                        <div class="col-md-12">
                            <label for="purpose" class="form-label">منظور (دلیل صدور چک)</label>
                            <input type="text" class="form-control" id="purpose" name="purpose">
                        </div>
                        <div class="col-md-12">
                            <label for="description" class="form-label">توضیحات</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">لغو</button>
                    <button type="submit" class="btn btn-primary">ثبت چک</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- مودال مشاهده جزئیات چک -->
<div class="modal fade" id="viewChequeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">جزئیات چک</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="chequeDetailsContent">
                <!-- محتوای جزئیات اینجا لود می‌شود -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">بستن</button>
            </div>
        </div>
    </div>
</div>

<!-- اسکریپت‌ها -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // فرم ثبت چک
    $('#addChequeForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: 'process_cheque.php',
            method: 'POST',
            data: $(this).serialize() + '&action=add',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('✅ چک با موفقیت ثبت شد.');
                    $('#addChequeModal').modal('hide');
                    location.reload();
                } else {
                    alert('❌ خطا: ' + response.message);
                }
            },
            error: function() {
                alert('⚠️ خطا در ارتباط با سرور');
            }
        });
    });
});

function viewCheque(chequeId) {
    $.ajax({
        url: '/get_cheque_details.php',
        method: 'GET',
        data: { 
            id: chequeId,
            _token: '<?php echo $_SESSION["token"] ?? ""; ?>' // اگر سیستم CSRF توکن دارید
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                let cheque = response.cheque;
                let html = `
                    <div class="container-fluid">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="detail-item d-flex">
                                    <span class="detail-label">شناسه:</span>
                                    <span class="detail-value">${cheque.id || '-'}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="detail-item d-flex">
                                    <span class="detail-label">شماره چک:</span>
                                    <span class="detail-value fw-bold">${cheque.cheque_number || '-'}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="detail-item d-flex">
                                    <span class="detail-label">تاریخ چک:</span>
                                    <span class="detail-value">${cheque.formatted_date || '-'}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="detail-item d-flex">
                                    <span class="detail-label">مبلغ:</span>
                                    <span class="detail-value fw-bold text-primary">${cheque.amount_formatted || formatNumber(cheque.amount || 0)}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="detail-item d-flex">
                                    <span class="detail-label">بانک:</span>
                                    <span class="detail-value">${cheque.bank_name || '-'}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="detail-item d-flex">
                                    <span class="detail-label">وضعیت:</span>
                                    <span class="detail-value">
                                        <span class="badge ${getStatusClass(cheque.status)}">
                                            ${cheque.status_label || cheque.status}
                                        </span>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="detail-item d-flex">
                                    <span class="detail-label">صاحب حساب:</span>
                                    <span class="detail-value">${cheque.account_owner || '-'}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="detail-item d-flex">
                                    <span class="detail-label">کد ملی صاحب حساب:</span>
                                    <span class="detail-value">${cheque.national_code || '-'}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="detail-item d-flex">
                                    <span class="detail-label">دریافت کننده:</span>
                                    <span class="detail-value">${cheque.receiver_name || '-'}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="detail-item d-flex">
                                    <span class="detail-label">کد ملی دریافت کننده:</span>
                                    <span class="detail-value">${cheque.receiver_national_code || '-'}</span>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="detail-item d-flex">
                                    <span class="detail-label">منظور:</span>
                                    <span class="detail-value">${cheque.purpose || '-'}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="detail-item d-flex">
                                    <span class="detail-label">تاریخ ثبت:</span>
                                    <span class="detail-value">${cheque.created_at_formatted || '-'}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="detail-item d-flex">
                                    <span class="detail-label">آخرین ویرایش:</span>
                                    <span class="detail-value">${cheque.updated_at_formatted || '-'}</span>
                                </div>
                            </div>
                `;
                
                // اضافه کردن بخش توضیحات در صورت وجود
                if (cheque.description) {
                    html += `
                            <div class="col-md-12">
                                <div class="detail-item d-flex flex-column">
                                    <span class="detail-label mb-2">توضیحات:</span>
                                    <div class="bg-light p-3 rounded border">
                                        ${cheque.description}
                                    </div>
                                </div>
                            </div>
                    `;
                }
                
                html += `
                        </div>
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-2"></i>
                                    برای تغییر وضعیت چک می‌توانید از دکمه‌های موجود در جدول استفاده کنید.
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                $('#chequeDetailsContent').html(html);
                $('#viewChequeModal').modal('show');
            } else {
                alert('❌ خطا: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX Error:', error);
            alert('⚠️ خطا در ارتباط با سرور. لطفاً دوباره تلاش کنید.');
        }
    });
}

// تابع اضافی برای کلاس وضعیت
function getStatusClass(status) {
    const classes = {
        'pending': 'bg-warning',
        'cleared': 'bg-success',
        'returned': 'bg-danger',
        'cancelled': 'bg-secondary'
    };
    return classes[status] || 'bg-dark';
}

function editCheque(chequeId) {
    window.location.href = 'edit_cheque.php?id=' + chequeId;
}

function updateStatus(chequeId, status) {
    const statusLabels = {
        'pending': 'در انتظار',
        'cleared': 'وصول شده',
        'returned': 'برگشتی',
        'cancelled': 'ابطال شده'
    };
    
    if (confirm(`آیا از تغییر وضعیت چک به "${statusLabels[status]}" اطمینان دارید؟`)) {
        $.ajax({
            url: 'process_cheque.php',
            method: 'POST',
            data: {
                action: 'update_status',
                id: chequeId,
                status: status
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('✅ وضعیت چک با موفقیت به‌روزرسانی شد.');
                    location.reload();
                } else {
                    alert('❌ خطا: ' + response.message);
                }
            },
            error: function() {
                alert('⚠️ خطا در ارتباط با سرور');
            }
        });
    }
}

function deleteCheque(chequeId) {
    if (confirm('⚠️ آیا از حذف این چک اطمینان دارید؟\nاین عمل غیرقابل برگشت است.')) {
        $.ajax({
            url: 'process_cheque.php',
            method: 'POST',
            data: {
                action: 'delete',
                id: chequeId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert('✅ چک با موفقیت حذف شد.');
                    location.reload();
                } else {
                    alert('❌ خطا: ' + response.message);
                }
            },
            error: function() {
                alert('⚠️ خطا در ارتباط با سرور');
            }
        });
    }
}

// تابع فرمت عدد
function formatNumber(num) {
    return Number(num).toLocaleString('fa-IR') + ' ریال';
}

// تابع تولید بدج وضعیت
function getStatusBadge(status) {
    const statuses = {
        'pending': ['در انتظار', 'warning'],
        'cleared': ['وصول شده', 'success'],
        'returned': ['برگشتی', 'danger'],
        'cancelled': ['ابطال شده', 'secondary']
    };
    
    const statusInfo = statuses[status] || [status, 'dark'];
    return `<span class="badge bg-${statusInfo[1]} badge-status">${statusInfo[0]}</span>`;
}

// فعال‌سازی جستجوی سریع
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.querySelector('input[name="search"]');
    
    // Enter برای جستجو
    if (searchInput) {
        searchInput.addEventListener('keyup', function(e) {
            if (e.key === 'Enter') {
                this.form.submit();
            }
        });
    }
    
    // دکمه چاپ
    document.querySelector('.btn-outline-primary').addEventListener('click', function() {
        window.print();
    });
});
</script>
</body>
</html>