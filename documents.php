<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// بررسی لاگین
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'] ?? 0;

// اتصال به دیتابیس
$db = new mysqli('localhost', 'root', '', 'maskanhb_maskansazan');
$db->set_charset("utf8mb4");

$stmt = $db->prepare("
    SELECT 
        d.id,
        d.document_type,
        d.file_path,
        d.file_name,
        d.description,
        d.verification_status,
        d.upload_date
    FROM user_documents d 
    WHERE d.user_id = ?
    ORDER BY d.upload_date DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$documentsArray = [];
$site_url = 'https://' . $_SERVER['HTTP_HOST'];
$server_root = $_SERVER['DOCUMENT_ROOT'];

while ($doc = $result->fetch_assoc()) {
    // اصلاح مسیر فایل
    if (!empty($doc['file_path'])) {
        $original_path = $doc['file_path'];
        
        // **راه‌حل اصلی: تبدیل مسیر ../uploads/ به مسیر مستقیم**
        if (strpos($original_path, '../uploads/') === 0) {
            // روش ۱: تبدیل به مسیر واقعی سرور
            $absolute_path = str_replace('../uploads/', '/home/maskanhb/uploads/', $original_path);
            
            // روش ۲: ایجاد URL مستقیم برای دانلود
            $web_path = str_replace('../uploads/', '/uploads/', $original_path);
            $direct_url = $site_url . $web_path;
            
            // روش ۳: استفاده از فایل دانلود میانی (بهترین راه از نظر امنیت)
            $download_url = "download.php?id=" . $doc['id'];
            
        } elseif (strpos($original_path, '/uploads/') === 0) {
            // اگر مسیر درست است
            $absolute_path = $server_root . $original_path;
            $direct_url = $site_url . $original_path;
            $download_url = $direct_url;
        } else {
            // سایر موارد
            $absolute_path = $server_root . '/' . ltrim($original_path, '/');
            $direct_url = $site_url . '/' . ltrim($original_path, '/');
            $download_url = $direct_url;
        }
        
        // بررسی وجود فایل
        $doc['file_exists'] = file_exists($absolute_path);
        $doc['absolute_path'] = $absolute_path;
        $doc['direct_url'] = $direct_url ?? '';
        $doc['download_url'] = $download_url ?? '';
        $doc['original_path'] = $original_path;
        
    } else {
        $doc['file_exists'] = false;
    }
    
    $documentsArray[] = $doc;
}
$stmt->close();
$db->close();
?>
<style>
    .document-card {
        border: 1px solid #e0e0e0;
        border-radius: 12px;
        transition: all 0.3s ease;
        height: 100%;
    }
    
    .document-card:hover {
        border-color: #4f46e5;
        box-shadow: 0 8px 25px rgba(79, 70, 229, 0.1);
        transform: translateY(-2px);
    }
    
    .document-icon {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
    }
    
    .btn-download {
        background: linear-gradient(135deg, #10b981, #059669);
        border: none;
        border-radius: 8px;
        padding: 10px 15px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .btn-download:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
        background: linear-gradient(135deg, #059669, #047857);
        color: white;
    }
    
    .document-info {
        border-top: 1px solid #f0f0f0;
        padding-top: 15px;
    }
    
    .text-muted {
        color: #6c757d !important;
    }
</style>
<div class="container mt-4">
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-folder2-open me-2"></i>
                            مدارک من
                        </h5>
                    </div>
                </div>
                
                <div class="card-body">
                    <?php if(empty($documentsArray)): ?>
                        <div class="text-center py-5">
                            <div class="mb-4">
                                <i class="bi bi-folder-x display-1 text-muted"></i>
                            </div>
                            <h5 class="mb-3">مدرکی یافت نشد</h5>
                            <p class="text-muted mb-4">هنوز مدرکی برای شما ثبت نشده است.</p>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach($documentsArray as $doc): ?>
                                <div class="col-md-6 col-lg-4 mb-4">
                                    <div class="card h-100 border-0 shadow-sm document-card">
                                        <div class="card-body p-3">
                                            <!-- پیش‌نمایش فایل -->
                                            <div class="file-preview">
                                                <?php
                                                $ext = strtolower(pathinfo($doc['original_path'] ?? '', PATHINFO_EXTENSION));
                                                $icon = 'bi-file-earmark';
                                                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) $icon = 'bi-file-image';
                                                elseif ($ext === 'pdf') $icon = 'bi-file-pdf';
                                                elseif (in_array($ext, ['doc', 'docx'])) $icon = 'bi-file-word';
                                                elseif (in_array($ext, ['xls', 'xlsx'])) $icon = 'bi-file-excel';
                                                ?>
                                                <i class="bi <?= $icon ?> file-icon"></i>
                                            </div>
                                            
                                            <!-- نوع مدرک -->
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <h6 class="mb-0 text-truncate" title="<?= htmlspecialchars($doc['document_type'] ?? 'مدرک') ?>">
                                                    <i class="bi bi-file-earmark-text me-2"></i>
                                                    <?= htmlspecialchars($doc['document_type'] ?? 'مدرک') ?>
                                                </h6>
                                                <?php if($doc['verification_status'] === 'verified'): ?>
                                                    <span class="badge bg-success status-badge">
                                                        <i class="bi bi-check-circle me-1"></i> تأیید شده
                                                    </span>
                                                <?php elseif($doc['verification_status'] === 'rejected'): ?>
                                                    <span class="badge bg-danger status-badge">
                                                        <i class="bi bi-x-circle me-1"></i> رد شده
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark status-badge">
                                                        <i class="bi bi-clock me-1"></i> در انتظار
                                                    </span>
                                                <?php endif ?>
                                            </div>
                                            
                                            <!-- اطلاعات فایل -->
                                            <div class="mb-3">
                                                <small class="text-muted d-block mb-1">
                                                    <i class="bi bi-calendar me-1"></i>
                                                    تاریخ آپلود: <?= date('Y/m/d', strtotime($doc['upload_date'] ?? 'now')) ?>
                                                </small>
                                                
                                                <?php if(!empty($doc['description'])): ?>
                                                    <p class="small text-muted mb-2 mt-2">
                                                        <i class="bi bi-card-text me-1"></i>
                                                        <?= htmlspecialchars(mb_substr($doc['description'], 0, 60)) . (mb_strlen($doc['description']) > 60 ? '...' : '') ?>
                                                    </p>
                                                <?php endif; ?>
                                                
                                                <!-- نمایش وضعیت فایل -->
                                                <?php if(!empty($doc['original_path'])): ?>
                                                    <div class="mt-2">
                                                        <?php if($doc['file_exists']): ?>
                                                            <span class="badge bg-success">
                                                                <i class="bi bi-check-circle me-1"></i> فایل سالم
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger">
                                                                <i class="bi bi-exclamation-circle me-1"></i> فایل گم شده
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <!-- دکمه‌های اقدام -->
                                            <div class="d-grid gap-2">
                                                <?php if(!empty($doc['file_path']) && $doc['file_exists']): ?>
                                                    <!-- دو دکمه: مشاهده و دانلود -->
                                                    <div class="row g-2">
                                                        <div class="col-6">
                                                            <a href="<?= htmlspecialchars($doc['download_url']) ?>" 
                                                               class="btn btn-view w-100"
                                                               target="_blank">
                                                                <i class="bi bi-eye me-1"></i> مشاهده
                                                            </a>
                                                        </div>
                                                        <div class="col-6">
                                                            <a href="<?= htmlspecialchars($doc['download_url']) ?>" 
                                                               download
                                                               class="btn btn-download w-100">
                                                                <i class="bi bi-download me-1"></i> دانلود
                                                            </a>
                                                        </div>
                                                    </div>
                                                    
                                                    <!-- اطلاعات فنی (برای دیباگ) -->
                                                    <small class="text-muted d-block mt-2 text-center">
                                                        <i class="bi bi-info-circle me-1"></i>
                                                        <?= htmlspecialchars(pathinfo($doc['original_path'], PATHINFO_EXTENSION)) ?>
                                                    </small>
                                                    
                                                <?php elseif(!empty($doc['file_path'])): ?>
                                                    <!-- فایل یافت نشد -->
                                                    <div class="alert alert-warning p-2 mb-2">
                                                        <i class="bi bi-exclamation-triangle me-1"></i>
                                                        <small>فایل در سرور یافت نشد</small>
                                                    </div>
                                                    <form action="reupload.php" method="post" enctype="multipart/form-data">
                                                        <input type="hidden" name="doc_id" value="<?= $doc['id'] ?>">
                                                        <div class="input-group input-group-sm">
                                                            <input type="file" name="document" class="form-control" required>
                                                            <button type="submit" class="btn btn-warning">
                                                                <i class="bi bi-upload"></i>
                                                            </button>
                                                        </div>
                                                    </form>
                                                <?php else: ?>
                                                    <button class="btn btn-outline-secondary w-100" disabled>
                                                        <i class="bi bi-exclamation-triangle me-2"></i>
                                                        فایل موجود نیست
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <!-- فوتر کارت -->
                                        <div class="card-footer bg-transparent border-top-0 pt-0">
                                            <small class="text-muted d-block text-truncate" title="<?= htmlspecialchars($doc['original_path'] ?? '') ?>">
                                                <i class="bi bi-folder me-1"></i>
                                                <?= htmlspecialchars(basename($doc['original_path'] ?? '')) ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- اطلاعات آماری -->
                        <div class="mt-4 pt-3 border-top">
                            <div class="row text-center">
                                <div class="col-md-3 col-6 mb-3">
                                    <div class="p-3 bg-light rounded">
                                        <h5 class="mb-1 text-primary"><?= count($documentsArray) ?></h5>
                                        <small class="text-muted">کل مدارک</small>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6 mb-3">
                                    <div class="p-3 bg-light rounded">
                                        <h5 class="mb-1 text-success">
                                            <?= count(array_filter($documentsArray, fn($d) => $d['verification_status'] === 'verified')) ?>
                                        </h5>
                                        <small class="text-muted">تأیید شده</small>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6 mb-3">
                                    <div class="p-3 bg-light rounded">
                                        <h5 class="mb-1 text-warning">
                                            <?= count(array_filter($documentsArray, fn($d) => $d['verification_status'] === 'pending' || empty($d['verification_status']))) ?>
                                        </h5>
                                        <small class="text-muted">در انتظار</small>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6 mb-3">
                                    <div class="p-3 bg-light rounded">
                                        <h5 class="mb-1 text-danger">
                                            <?= count(array_filter($documentsArray, fn($d) => !$d['file_exists'])) ?>
                                        </h5>
                                        <small class="text-muted">فایل‌های گم شده</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>