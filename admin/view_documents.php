<?php
// admin/view_documents.php
session_start();

// بررسی دسترسی ادمین
if (empty($_SESSION['admin_id'])) {
    header('Location: loginAdmin.php');
    exit;
}

// include کلاس SafeDatabase
require_once 'SafeDatabase.php';

// $adminId = $_SESSION['admin_id'];

// اتصال به دیتابیس
$mysqli = SafeDatabase::connect();
if (!$mysqli) {
    die("خطا در اتصال به پایگاه داده.");
}

// ========== دریافت پارامترها ==========
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$document_id = isset($_GET['document_id']) ? (int)$_GET['document_id'] : 0;
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$type_filter = isset($_GET['type']) ? $_GET['type'] : '';

// ========== توابع کمکی ==========
function formatFileSize($bytes) {
    if ($bytes == 0) return "0 بایت";
    
    $units = ['بایت', 'کیلوبایت', 'مگابایت', 'گیگابایت'];
    $i = floor(log($bytes, 1024));
    $size = round($bytes / pow(1024, $i), 2);
    
    return $size . ' ' . $units[$i];
}

function getStatusBadge($status) {
    switch ($status) {
        case 'verified':
        case 'تایید شده':
            return '<span class="badge bg-success">تأیید شده</span>';
        case 'rejected':
        case 'رد شده':
            return '<span class="badge bg-danger">رد شده</span>';
        case 'pending':
        case 'در انتظار':
        default:
            return '<span class="badge bg-warning">در انتظار</span>';
    }
}

function getDocumentTypeText($type) {
    $types = [
        'identity' => 'مدارک شناسایی',
        'certificate' => 'گواهی‌نامه‌ها',
        'contract' => 'قراردادها',
        'financial' => 'مدارک مالی',
        'educational' => 'مدارک تحصیلی',
        'property' => 'مدارک ملکی',
        'other' => 'سایر'
    ];
    return $types[$type] ?? $type;
}

function getFileIcon($file_type) {
    if (strpos($file_type, 'image/') === 0) {
        return '<i class="bi bi-file-earmark-image text-primary"></i>';
    } elseif ($file_type === 'application/pdf') {
        return '<i class="bi bi-file-earmark-pdf text-danger"></i>';
    } elseif (strpos($file_type, 'text/') === 0) {
        return '<i class="bi bi-file-earmark-text text-info"></i>';
    } elseif (strpos($file_type, 'application/vnd.ms-excel') !== false || 
               strpos($file_type, 'spreadsheet') !== false) {
        return '<i class="bi bi-file-earmark-excel text-success"></i>';
    } elseif (strpos($file_type, 'application/vnd.ms-word') !== false || 
               strpos($file_type, 'document') !== false) {
        return '<i class="bi bi-file-earmark-word text-primary"></i>';
    } else {
        return '<i class="bi bi-file-earmark"></i>';
    }
}

// ========== دریافت اطلاعات کاربر ==========
$user_info = [];
if ($user_id > 0) {
    $stmt = $mysqli->prepare("
        SELECT 
            id, 
            first_name,
            last_name,
            CONCAT(first_name, ' ', last_name) as full_name,
            national_code, 
            phone,
            email,
            main_member,
            project_id
        FROM project_members 
        WHERE id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_info = $result->fetch_assoc() ?: [];
    $stmt->close();
}

// ========== دریافت نام پروژه ==========
$project_name = '';
if (!empty($user_info['project_id'])) {
    $stmt = $mysqli->prepare("SELECT name FROM projects WHERE id = ?");
    $stmt->bind_param("i", $user_info['project_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $project = $result->fetch_assoc();
    $project_name = $project['name'] ?? '';
    $stmt->close();
}

// ========== دریافت مدارک کاربر ==========
$documents = [];
$total_documents = 0;
$pending_count = 0;
$verified_count = 0;
$rejected_count = 0;

if ($user_id > 0) {
    // کوئری اصلی برای دریافت مدارک
    $query = "
        SELECT 
            ud.id,
            ud.user_id,
            ud.document_name,
            ud.document_type,
            ud.description,
            ud.file_path,
            ud.file_name,
            ud.file_size,
            ud.file_type,
            ud.upload_date,
            ud.verification_status,
            ud.admin_notes,
            ud.uploaded_by_admin,
            ud.verified_by,
            ud.verification_date,
            au.username as uploaded_by_name,
            av.username as verified_by_name
        FROM user_documents ud
        LEFT JOIN admins au ON ud.uploaded_by_admin = au.id
        LEFT JOIN admins av ON ud.verified_by = av.id
        WHERE ud.user_id = ?
    ";
    
    // اضافه کردن فیلترها
    $params = [$user_id];
    $types = "i";
    
    if ($status_filter && in_array($status_filter, ['pending', 'verified', 'rejected', 'در انتظار', 'تایید شده', 'رد شده'])) {
        $query .= " AND ud.verification_status = ?";
        $params[] = $status_filter;
        $types .= "s";
    }
    
    if ($type_filter) {
        $query .= " AND ud.document_type = ?";
        $params[] = $type_filter;
        $types .= "s";
    }
    
    $query .= " ORDER BY ud.upload_date DESC";
    
    $stmt = $mysqli->prepare($query);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $documents[] = $row;
    }
    $stmt->close();
    
    // آمار مدارک
    $stats_query = "
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN verification_status IN ('pending', 'در انتظار') THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN verification_status IN ('verified', 'تایید شده') THEN 1 ELSE 0 END) as verified,
            SUM(CASE WHEN verification_status IN ('rejected', 'رد شده') THEN 1 ELSE 0 END) as rejected
        FROM user_documents 
        WHERE user_id = ?
    ";
    
    $stmt = $mysqli->prepare($stats_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stats_result = $stmt->get_result();
    $stats = $stats_result->fetch_assoc();
    
    if ($stats) {
        $total_documents = $stats['total'];
        $pending_count = $stats['pending'];
        $verified_count = $stats['verified'];
        $rejected_count = $stats['rejected'];
    }
    $stmt->close();
}

// ========== دریافت جزئیات یک مدرک خاص ==========
$document_details = [];
if ($document_id > 0) {
    $query = "
        SELECT 
            ud.*,
            pm.first_name,
            pm.last_name,
            pm.national_code,
            pm.phone,
            pm.email,
            au.username as uploaded_by_name,
            av.username as verified_by_name
        FROM user_documents ud
        LEFT JOIN project_members pm ON ud.user_id = pm.id
        LEFT JOIN admins au ON ud.uploaded_by_admin = au.id
        LEFT JOIN admins av ON ud.verified_by = av.id
        WHERE ud.id = ?
    ";
    
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("i", $document_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $document_details = $result->fetch_assoc() ?: [];
    $stmt->close();
}

// ========== عملیات تغییر وضعیت ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'change_status' && isset($_POST['document_id'])) {
        $doc_id = (int)$_POST['document_id'];
        $status = $mysqli->real_escape_string($_POST['status']);
        $notes = $mysqli->real_escape_string($_POST['admin_notes'] ?? '');
        
        $stmt = $mysqli->prepare("
            UPDATE user_documents 
            SET verification_status = ?,
                admin_notes = ?,
                verified_by = ?,
                verification_date = NOW()
            WHERE id = ?
        ");
        
        $stmt->bind_param("ssii", $status, $notes, $adminId, $doc_id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "وضعیت مدرک با موفقیت تغییر یافت.";
        } else {
            $_SESSION['error_message'] = "خطا در تغییر وضعیت مدرک.";
        }
        
        $stmt->close();
        
        // بازگشت به همان صفحه
        $redirect_url = "view_documents.php?user_id=" . $user_id;
        if ($document_id > 0) {
            $redirect_url .= "&document_id=" . $document_id;
        }
        header("Location: " . $redirect_url);
        exit;
    }
    
    if ($_POST['action'] === 'delete_document' && isset($_POST['document_id'])) {
        $doc_id = (int)$_POST['document_id'];
        
        // دریافت مسیر فایل
        $stmt = $mysqli->prepare("SELECT file_path FROM user_documents WHERE id = ?");
        $stmt->bind_param("i", $doc_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $doc = $result->fetch_assoc();
        $stmt->close();
        
        if ($doc && !empty($doc['file_path'])) {
            // حذف فایل فیزیکی
            $filePath = $_SERVER['DOCUMENT_ROOT'] . $doc['file_path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
        
        // حذف از دیتابیس
        $stmt = $mysqli->prepare("DELETE FROM user_documents WHERE id = ?");
        $stmt->bind_param("i", $doc_id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "مدرک با موفقیت حذف شد.";
        } else {
            $_SESSION['error_message'] = "خطا در حذف مدرک.";
        }
        
        $stmt->close();
        
        header("Location: view_documents.php?user_id=" . $user_id);
        exit;
    }
}

// دریافت پیام‌های سشن
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مدارک کاربر - <?php echo htmlspecialchars($user_info['full_name'] ?? 'سیستم مدیریت'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        body {
            font-family: 'Vazir', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            padding-top: 20px;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            border: none;
        }
        .card-header {
            background-color: #2c3e50;
            color: white;
            border-radius: 10px 10px 0 0 !important;
            padding: 15px 20px;
        }
        .user-info-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .stat-box {
            text-align: center;
            padding: 15px;
            border-radius: 8px;
            margin: 5px;
            color: white;
        }
        .stat-total { background-color: #3498db; }
        .stat-pending { background-color: #f39c12; }
        .stat-verified { background-color: #27ae60; }
        .stat-rejected { background-color: #e74c3c; }
        .table th {
            background-color: #f8f9fa;
            color: #2c3e50;
            font-weight: 600;
        }
        .badge-pending { background-color: #ffc107; color: black; }
        .badge-verified { background-color: #28a745; color: white; }
        .badge-rejected { background-color: #dc3545; color: white; }
        .file-preview-img {
            max-width: 80px;
            max-height: 80px;
            cursor: pointer;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 3px;
            background: white;
        }
        .action-buttons .btn {
            margin: 2px;
            padding: 3px 8px;
            font-size: 0.85rem;
        }
        .document-details-card {
            background-color: #f8f9fa;
            border-left: 4px solid #3498db;
            padding: 20px;
            border-radius: 8px;
        }
        .back-link {
            color: #2c3e50;
            text-decoration: none;
            font-weight: 500;
        }
        .back-link:hover {
            color: #3498db;
        }
        .filter-badge {
            cursor: pointer;
            margin: 2px;
        }
        .no-documents {
            text-align: center;
            padding: 40px 20px;
            color: #6c757d;
        }
        .no-documents i {
            font-size: 4rem;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- پیام‌های موفقیت/خطا -->
        <?php if(!empty($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i> <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <?php if(!empty($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-circle"></i> <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <!-- هدر و ناوبری -->
        <div class="row mb-4">
            <div class="col-md-8">
                <a href="dashboardAdmin.php?tab=users" class="back-link">
                    <i class="bi bi-arrow-right"></i> بازگشت به لیست کاربران
                </a>
                <h1 class="h3 mt-2">
                    <i class="bi bi-folder-check"></i> 
                    مدارک کاربر
                    <?php if(!empty($user_info)): ?>
                    <small class="text-muted">- <?php echo htmlspecialchars($user_info['full_name']); ?></small>
                    <?php endif; ?>
                </h1>
            </div>
            <div class="col-md-4 text-md-end">
                <?php if($user_id > 0): ?>
                <a href="upload_document.php?user_id=<?php echo $user_id; ?>" class="btn btn-primary">
                    <i class="bi bi-cloud-upload"></i> آپلود مدرک جدید
                </a>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if(!empty($user_info)): ?>
        <!-- اطلاعات کاربر -->
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">اطلاعات کاربر</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <p><strong>نام کامل:</strong><br>
                        <?php echo htmlspecialchars($user_info['full_name']); ?></p>
                    </div>
                    <div class="col-md-3">
                        <p><strong>کدملی:</strong><br>
                        <?php echo htmlspecialchars($user_info['national_code']); ?></p>
                    </div>
                    <div class="col-md-3">
                        <p><strong>تلفن:</strong><br>
                        <?php echo htmlspecialchars($user_info['phone']); ?></p>
                    </div>
                    <div class="col-md-3">
                        <p><strong>پروژه:</strong><br>
                        <?php echo htmlspecialchars($project_name ?: 'بدون پروژه'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- آمار مدارک -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-box stat-total">
                    <h4><i class="bi bi-folder"></i> <?php echo $total_documents; ?></h4>
                    <p>کل مدارک</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-box stat-pending">
                    <h4><i class="bi bi-clock"></i> <?php echo $pending_count; ?></h4>
                    <p>در انتظار بررسی</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-box stat-verified">
                    <h4><i class="bi bi-check-circle"></i> <?php echo $verified_count; ?></h4>
                    <p>تأیید شده</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-box stat-rejected">
                    <h4><i class="bi bi-x-circle"></i> <?php echo $rejected_count; ?></h4>
                    <p>رد شده</p>
                </div>
            </div>
        </div>
        
        <!-- فیلترها -->
        <div class="card mb-4">
            <div class="card-body">
                <h6 class="mb-3">فیلتر مدارک:</h6>
                <div class="d-flex flex-wrap">
                    <a href="view_documents.php?user_id=<?php echo $user_id; ?>" 
                       class="btn btn-sm <?php echo empty($status_filter) && empty($type_filter) ? 'btn-primary' : 'btn-outline-primary'; ?> filter-badge">
                       همه مدارک
                    </a>
                    
                    <!-- فیلتر وضعیت -->
                    <span class="mx-2 text-muted">وضعیت:</span>
                    <a href="view_documents.php?user_id=<?php echo $user_id; ?>&status=pending" 
                       class="btn btn-sm <?php echo $status_filter == 'pending' ? 'btn-warning' : 'btn-outline-warning'; ?> filter-badge">
                       در انتظار
                    </a>
                    <a href="view_documents.php?user_id=<?php echo $user_id; ?>&status=verified" 
                       class="btn btn-sm <?php echo $status_filter == 'verified' ? 'btn-success' : 'btn-outline-success'; ?> filter-badge">
                       تأیید شده
                    </a>
                    <a href="view_documents.php?user_id=<?php echo $user_id; ?>&status=rejected" 
                       class="btn btn-sm <?php echo $status_filter == 'rejected' ? 'btn-danger' : 'btn-outline-danger'; ?> filter-badge">
                       رد شده
                    </a>
                    
                    <!-- فیلتر نوع -->
                    <span class="mx-2 text-muted">نوع:</span>
                    <?php 
                    $document_types = ['identity', 'certificate', 'contract', 'financial', 'educational', 'property', 'other'];
                    foreach($document_types as $type): 
                    ?>
                    <a href="view_documents.php?user_id=<?php echo $user_id; ?>&type=<?php echo $type; ?>" 
                       class="btn btn-sm <?php echo $type_filter == $type ? 'btn-info' : 'btn-outline-info'; ?> filter-badge">
                       <?php echo getDocumentTypeText($type); ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- جدول مدارک -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">لیست مدارک</h5>
                <span class="badge bg-secondary"><?php echo count($documents); ?> مدرک</span>
            </div>
            <div class="card-body p-0">
                <?php if(empty($documents)): ?>
                <div class="no-documents">
                    <i class="bi bi-folder-x"></i>
                    <h5>مدرکی یافت نشد</h5>
                    <p class="text-muted">هیچ مدرکی برای این کاربر ثبت نشده است.</p>
                    <a href="upload_document.php?user_id=<?php echo $user_id; ?>" class="btn btn-primary mt-2">
                        <i class="bi bi-cloud-upload"></i> اولین مدرک را آپلود کنید
                    </a>
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th width="50">#</th>
                                <th>نام مدرک</th>
                                <th>نوع</th>
                                <th>فایل</th>
                                <th>حجم</th>
                                <th>تاریخ آپلود</th>
                                <th>وضعیت</th>
                                <th width="180">عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($documents as $index => $doc): ?>
                            <tr <?php echo ($document_id == $doc['id']) ? 'class="table-active"' : ''; ?>>
                                <td><?php echo $index + 1; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($doc['document_name']); ?></strong>
                                    <?php if(!empty($doc['description'])): ?>
                                    <br><small class="text-muted"><?php echo htmlspecialchars(mb_substr($doc['description'], 0, 50)) . (mb_strlen($doc['description']) > 50 ? '...' : ''); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo getDocumentTypeText($doc['document_type']); ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php echo getFileIcon($doc['file_type']); ?>
                                        <span class="ms-2">
                                            <?php echo htmlspecialchars($doc['file_name']); ?>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($doc['file_type']); ?></small>
                                        </span>
                                    </div>
                                </td>
                                <td><?php echo formatFileSize($doc['file_size']); ?></td>
                                <td>
                                    <?php 
                                    $upload_date = new DateTime($doc['upload_date']);
                                    echo $upload_date->format('Y/m/d H:i');
                                    ?>
                                </td>
                                <td><?php echo getStatusBadge($doc['verification_status']); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="view_documents.php?user_id=<?php echo $user_id; ?>&document_id=<?php echo $doc['id']; ?>" 
                                           class="btn btn-sm btn-info" title="مشاهده جزئیات">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if(!empty($doc['file_path'])): ?>
                                        <a href="<?php echo htmlspecialchars($doc['file_path']); ?>" 
                                           class="btn btn-sm btn-success" download title="دانلود">
                                            <i class="bi bi-download"></i>
                                        </a>
                                        <?php endif; ?>
                                        <button onclick="changeStatus(<?php echo $doc['id']; ?>, '<?php echo $doc['verification_status']; ?>')" 
                                                class="btn btn-sm btn-warning" title="تغییر وضعیت">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button onclick="deleteDocument(<?php echo $doc['id']; ?>)" 
                                                class="btn btn-sm btn-danger" title="حذف">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- جزئیات مدرک انتخاب شده -->
        <?php if(!empty($document_details)): ?>
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-info-circle"></i> جزئیات مدرک
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-bordered">
                            <tr>
                                <th width="40%">نام مدرک:</th>
                                <td><?php echo htmlspecialchars($document_details['document_name']); ?></td>
                            </tr>
                            <tr>
                                <th>نوع مدرک:</th>
                                <td><?php echo getDocumentTypeText($document_details['document_type']); ?></td>
                            </tr>
                            <tr>
                                <th>نام فایل:</th>
                                <td><?php echo htmlspecialchars($document_details['file_name']); ?></td>
                            </tr>
                            <tr>
                                <th>نوع فایل:</th>
                                <td><?php echo htmlspecialchars($document_details['file_type']); ?></td>
                            </tr>
                            <tr>
                                <th>حجم فایل:</th>
                                <td><?php echo formatFileSize($document_details['file_size']); ?></td>
                            </tr>
                            <tr>
                                <th>تاریخ آپلود:</th>
                                <td>
                                    <?php 
                                    $upload_date = new DateTime($document_details['upload_date']);
                                    echo $upload_date->format('Y/m/d H:i');
                                    ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-bordered">
                            <tr>
                                <th width="40%">وضعیت:</th>
                                <td><?php echo getStatusBadge($document_details['verification_status']); ?></td>
                            </tr>
                            <tr>
                                <th>آپلود توسط:</th>
                                <td><?php echo htmlspecialchars($document_details['uploaded_by_name'] ?? 'کاربر'); ?></td>
                            </tr>
                            <tr>
                                <th>تأیید توسط:</th>
                                <td><?php echo htmlspecialchars($document_details['verified_by_name'] ?? '---'); ?></td>
                            </tr>
                            <tr>
                                <th>تاریخ تأیید:</th>
                                <td>
                                    <?php 
                                    if (!empty($document_details['verification_date'])) {
                                        $verify_date = new DateTime($document_details['verification_date']);
                                        echo $verify_date->format('Y/m/d H:i');
                                    } else {
                                        echo '---';
                                    }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th>کاربر:</th>
                                <td>
                                    <?php 
                                    echo htmlspecialchars($document_details['first_name'] . ' ' . $document_details['last_name']);
                                    echo '<br><small class="text-muted">' . htmlspecialchars($document_details['national_code']) . '</small>';
                                    ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <?php if(!empty($document_details['description'])): ?>
                <div class="mb-3">
                    <strong>توضیحات:</strong>
                    <div class="border rounded p-3 bg-light mt-2">
                        <?php echo nl2br(htmlspecialchars($document_details['description'])); ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if(!empty($document_details['admin_notes'])): ?>
                <div class="mb-3">
                    <strong>یادداشت ادمین:</strong>
                    <div class="border rounded p-3 bg-light mt-2">
                        <?php echo nl2br(htmlspecialchars($document_details['admin_notes'])); ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- پیش‌نمایش فایل -->
                <?php if(!empty($document_details['file_path'])): ?>
                <div class="mb-4">
                    <strong>پیش‌نمایش:</strong>
                    <div class="mt-2">
                        <?php if(strpos($document_details['file_type'], 'image/') === 0): ?>
                        <img src="<?php echo htmlspecialchars($document_details['file_path']); ?>" 
                             class="img-fluid rounded border" 
                             style="max-height: 400px; cursor: pointer;"
                             onclick="openImageModal('<?php echo htmlspecialchars($document_details['file_path']); ?>')"
                             alt="پیش‌نمایش">
                        <?php elseif($document_details['file_type'] === 'application/pdf'): ?>
                        <div class="border rounded p-4 text-center">
                            <i class="bi bi-file-earmark-pdf text-danger" style="font-size: 4rem;"></i>
                            <p class="mt-2">فایل PDF</p>
                            <a href="<?php echo htmlspecialchars($document_details['file_path']); ?>" 
                               target="_blank" class="btn btn-sm btn-primary">
                                <i class="bi bi-eye"></i> مشاهده در پنجره جدید
                            </a>
                        </div>
                        <?php else: ?>
                        <div class="border rounded p-4 text-center">
                            <?php echo getFileIcon($document_details['file_type']); ?>
                            <p class="mt-2"><?php echo htmlspecialchars($document_details['file_type']); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="text-center">
                    <?php if(!empty($document_details['file_path'])): ?>
                    <a href="<?php echo htmlspecialchars($document_details['file_path']); ?>" 
                       class="btn btn-primary" download>
                        <i class="bi bi-download"></i> دانلود فایل
                    </a>
                    <?php endif; ?>
                    <a href="view_documents.php?user_id=<?php echo $user_id; ?>" class="btn btn-secondary">
                        <i class="bi bi-arrow-right"></i> بازگشت به لیست
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php else: ?>
        <!-- اگر کاربری انتخاب نشده -->
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="bi bi-person-x text-muted" style="font-size: 4rem;"></i>
                <h4 class="mt-3">کاربری انتخاب نشده است</h4>
                <p class="text-muted">لطفاً از لیست کاربران، یک کاربر را انتخاب کنید.</p>
                <a href="dashboardAdmin.php?tab=users" class="btn btn-primary mt-2">
                    <i class="bi bi-arrow-right"></i> انتخاب کاربر از لیست
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- مودال تغییر وضعیت -->
    <div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">تغییر وضعیت مدرک</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="change_status">
                    <input type="hidden" id="modalDocumentId" name="document_id">
                    <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                    
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="status" class="form-label">وضعیت جدید</label>
                            <select id="status" name="status" class="form-select" required>
                                <option value="pending">در انتظار بررسی</option>
                                <option value="verified">تأیید شده</option>
                                <option value="rejected">رد شده</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="admin_notes" class="form-label">یادداشت ادمین (اختیاری)</label>
                            <textarea id="admin_notes" name="admin_notes" class="form-control" rows="3" 
                                      placeholder="علت تغییر وضعیت را اینجا بنویسید..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                        <button type="submit" class="btn btn-primary">ذخیره تغییرات</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- مودال حذف -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">حذف مدرک</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="action" value="delete_document">
                    <input type="hidden" id="deleteDocumentId" name="document_id">
                    <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                    
                    <div class="modal-body">
                        <div class="text-center">
                            <i class="bi bi-exclamation-triangle text-danger" style="font-size: 3rem;"></i>
                            <h5 class="mt-3">آیا از حذف این مدرک اطمینان دارید؟</h5>
                            <p class="text-muted">این عمل قابل بازگشت نیست و فایل مربوطه نیز حذف خواهد شد.</p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                        <button type="submit" class="btn btn-danger">حذف مدرک</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- مودال نمایش تصویر -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">مشاهده تصویر</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="modalImage" src="" class="img-fluid" alt="تصویر مدرک">
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // باز کردن مودال تصویر
        function openImageModal(imageSrc) {
            document.getElementById('modalImage').src = imageSrc;
            var imageModal = new bootstrap.Modal(document.getElementById('imageModal'));
            imageModal.show();
        }
        
        // تغییر وضعیت
        function changeStatus(documentId, currentStatus) {
            document.getElementById('modalDocumentId').value = documentId;
            document.getElementById('status').value = currentStatus;
            
            var statusModal = new bootstrap.Modal(document.getElementById('statusModal'));
            statusModal.show();
        }
        
        // حذف مدرک
        function deleteDocument(documentId) {
            document.getElementById('deleteDocumentId').value = documentId;
            
            var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
            deleteModal.show();
        }
        
        // بستن خودکار پیام‌های Alert بعد از 5 ثانیه
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                var bsAlert = new bootstrap.Alert(alert);
                setTimeout(function() {
                    bsAlert.close();
                }, 5000);
            });
        }, 3000);
        
        // اسکرول به جزئیات مدرک اگر انتخاب شده
        <?php if($document_id > 0): ?>
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                var element = document.querySelector('.table-active');
                if (element) {
                    element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }, 500);
        });
        <?php endif; ?>
    </script>
</body>
</html>