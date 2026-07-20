<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// بررسی لاگین
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: groupmembers.php');
    exit;
}
$user_project_id = $_GET['user_project_id'] ?? $_SESSION['users_project_id'] ?? 0;
$user_project_id = (int)$user_project_id;

if ($user_project_id <= 0) {
    die("شناسه کاربر یافت نشد.");
}
$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;

$message_status = $_GET['status'] ?? '';
$verification_filter = $_GET['verification_status'] ?? '';
$full_name= $_GET['full_name'] ?? '';
$documentsArray = [];

try {
    $pdo = new PDO(
            "mysql:host=localhost;dbname=maskanhb_maskansazan;charset=utf8mb4",
            "root",
            "",
            [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
    );

    if ($user_project_id > 0) {
        $query = "SELECT * FROM user_documents WHERE user_project_id = ?";
        $params = [$user_project_id];

        if ($project_id > 0) {
            $query .= " AND project_id = ?";
            $params[] = $project_id;
        }

        if (!empty($verification_filter)) {
            $query .= " AND verification_status = ?";
            $params[] = $verification_filter;
        }

        $query .= " ORDER BY upload_date DESC";

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);

        $documentsArray = $stmt->fetchAll();
    }

} catch (PDOException $e) {
    die("خطای اتصال یا دریافت اطلاعات از دیتابیس: " . $e->getMessage());
}

if (!function_exists('formatFileSize')) {
    function formatFileSize($bytes) {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 1) {
            return $bytes . ' bytes';
        } elseif ($bytes == 1) {
            return $bytes . ' byte';
        }

        return '0 bytes';
    }
}
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
<div class="card shadow-sm border-0">
    <!--    --><?php
    //    $user_project_id = (int)($_GET['id'] ?? $_GET['user_project_id'] ?? 0);
    //
    //    // دیباگ موقت
    //    echo '<div style="background:#ffe9a8;padding:10px;margin:10px 0">';
    //    echo 'user_project_id from URL = ' . $user_project_id;
    //    echo '</div>';
    //    ?>

    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold text-primary">
            لیست اسناد کاربر:
            <?php
            echo $full_name;
            ?>
        </h5>

        <div class="badge bg-primary-subtle text-primary rounded-pill px-3">
            تعداد کل: <?= count($documentsArray) ?>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead>
            <tr>
                <th>ردیف</th>
                <th>نام مدرک</th>
                <th>نوع مدرک</th>
                <th>نام فایل</th>
                <th>وضعیت</th>
                <th>عملیات</th>
            </tr>
            </thead>

            <tbody>
            <?php if (!empty($documentsArray)): ?>
                <?php foreach ($documentsArray as $index => $doc): ?>
                    <tr>
                        <td><?= $index + 1 ?></td>

                        <td>
                            <?= htmlspecialchars($doc['document_name'] ?? '-') ?>
                        </td>
                        <?php
                        // دریافت نوع مدرک از ردیف دیتابیس با سوئیچ امن (?? '') برای جلوگیری از هرگونه خطا
                        $docType = $doc['document_type'] ?? '';
                        ?>

                        <td>
                            <?php if ($docType === 'national_card'): ?>
                                کارت ملی
                            <?php elseif ($docType === 'birth_certificate'): ?>
                                شناسنامه
                            <?php elseif ($docType === 'ownership_document'): ?>
                                سند مالکیت
                            <?php elseif ($docType === 'bank_document'): ?>
                                مدرک بانکی
                            <?php elseif ($docType === 'other'): ?>
                                سایر
                            <?php else: ?>
                                نامشخص (<?= htmlspecialchars($docType) ?>)
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($doc['file_path'])): ?>
                                <img
                                        src="/maskansazan/<?= htmlspecialchars(ltrim($doc['file_path'], '/')) ?>"
                                        alt="<?= htmlspecialchars($doc['document_name'] ?? '-') ?>"
                                        style="width:80px; height:80px; object-fit:cover; border-radius:8px;"
                                >
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>


                        <td>
                            <?php
                            $verificationStatus = $doc['verification_status'] ?? 'pending';

                            if ($verificationStatus === 'verified') {
                                echo '<span class="badge bg-success">تأیید شده</span>';
                            } elseif ($verificationStatus === 'rejected') {
                                echo '<span class="badge bg-danger">رد شده</span>';
                            } else {
                                echo '<span class="badge bg-warning text-dark">در انتظار بررسی</span>';
                            }
                            ?>
                        </td>


                        <td>
                            <?php if (!empty($doc['file_path'])): ?>
                                <?php
                                $baseUrl = '/maskansazan';
                                $fileUrl = $baseUrl . '/' . ltrim($doc['file_path'], '/');
                                ?>

                                <a href="<?= htmlspecialchars($fileUrl) ?>"
                                   target="_blank"
                                   class="btn btn-sm btn-info">
                                    مشاهده
                                </a>

                                <a href="<?= htmlspecialchars($fileUrl) ?>"
                                   download
                                   class="btn btn-sm btn-success">
                                    دانلود
                                </a>

                            <?php endif; ?>



                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" class="text-center text-muted">
                        هیچ مدرکی برای این کاربر ثبت نشده است.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>

    </div>

</div>