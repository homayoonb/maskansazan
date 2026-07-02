<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/SafeDatabase.php';

// گرفتن user_id از URL
$user_id = isset($_GET['id'])
        ? (int)$_GET['id']
        : (isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0);
// status فقط برای پیام‌های سیستم مثل success استفاده شود
$message_status = $_GET['status'] ?? '';

// برای فیلتر وضعیت تأیید، از پارامتر جدا استفاده می‌کنیم
$verification_filter = $_GET['verification_status'] ?? '';

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

    if ($user_id > 0) {
        $query = "SELECT * FROM user_documents WHERE user_id = ?";
        $params = [$user_id];

        // فقط اگر فیلتر تأیید معتبر بود، به کوئری اضافه شود
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

ob_start();
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">

<style>
    body { background-color: #f8f9fa; font-family: 'Tahoma', sans-serif; direction: rtl; }
    .card { border-radius: 15px; }
    .table thead th { background-color: #f1f4f9; color: #555; font-weight: 600; border: none; }
    .btn-sm { border-radius: 8px; }
    .badge { font-weight: 500; }
</style>
<!--<p>USER ID: --><?php //echo $user_id; ?><!--</p>-->
    <a href="dashboardAdmin.php" class="btn btn-secondary" >
        بازگشت
    </a>
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 fw-bold text-primary">آپلود مدرک جدید</h5>
    </div>

    <div class="card-body">
        <?php if (!empty($uploadMessage)): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($uploadMessage) ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['uploaded']) && $_GET['uploaded'] == '1'): ?>
            <div class="alert alert-success">
                مدرک با موفقیت آپلود شد.
            </div>
        <?php endif; ?>

        <form method="post" action="upload_document.php" enctype="multipart/form-data" class="row g-3">
            <input type="hidden" name="user_id" value="<?= (int)$user_id ?>">
<!--            <p style="color:red">-->
<!--                user_id داخل فرم: --><?php //= (int)$user_id ?>
<!--            </p>-->
            <div class="col-md-4">
                <label class="form-label">نام مدرک</label>
                <input type="text" name="document_name" class="form-control" placeholder="مثلاً کارت ملی" required>
            </div>

            <div class="col-md-4">
                <label class="form-label">نوع مدرک</label>
                <select name="document_type" class="form-select" required>
                    <option value="">انتخاب کنید</option>
                    <option value="national_card">کارت ملی</option>
                    <option value="birth_certificate">شناسنامه</option>
                    <option value="ownership_document">سند مالکیت</option>
                    <option value="bank_document">مدرک بانکی</option>
                    <option value="other">سایر</option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">فایل مدرک</label>
                <input type="file" name="document_file" class="form-control" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" required>
            </div>

            <div class="col-12 d-flex justify-content-end">
                <button type="submit" name="upload_document" class="btn btn-primary">
                    <i class="bi bi-upload"></i>
                    آپلود مدرک
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
<!--    --><?php
//    $user_id = (int)($_GET['id'] ?? $_GET['user_id'] ?? 0);
//
//    // دیباگ موقت
//    echo '<div style="background:#ffe9a8;padding:10px;margin:10px 0">';
//    echo 'user_id from URL = ' . $user_id;
//    echo '</div>';
//    ?>

    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold text-primary">
            لیست اسناد کاربر:
            <?php
            // ۱. اولویت با نامی است که از صفحه قبل در URL ارسال شده
            if (isset($_GET['member_name'])) {
                echo htmlspecialchars($_GET['member_name']);
            }
            // ۲. اگر در URL نبود، از سشن استفاده کن (به عنوان راه حل جایگزین)
            elseif (isset($_SESSION['fullName'])) {
                echo htmlspecialchars($_SESSION['fullName']);
            }
            else {
                echo "نامشخص";
            }
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
                <th>حجم فایل</th>
                <th>وضعیت</th>
                <th>تاریخ آپلود</th>
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

                        <td>
                            <?= htmlspecialchars($doc['document_type'] ?? '-') ?>
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
                            $fileSize = isset($doc['file_size']) ? (int)$doc['file_size'] : 0;

                            if ($fileSize > 0) {
                                echo round($fileSize / 1024, 2) . ' KB';
                            } else {
                                echo '-';
                            }
                            ?>
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
                            <?= htmlspecialchars($doc['upload_date'] ?? '-') ?>
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

                            <button type="button"
                                    class="btn btn-sm btn-danger btn-delete-doc"
                                    data-id="<?= (int)$doc['id'] ?>">
                                حذف
                            </button>
                            <?php if (($doc['verification_status'] ?? 'pending') !== 'verified'): ?>
                                <a href="change_document_status.php?id=<?= (int)$doc['id'] ?>&status=verified&user_id=<?= (int)$user_id ?>"
                                   class="btn btn-sm btn-primary"
                                   onclick="return confirm('آیا از تأیید این مدرک مطمئن هستید؟')">
                                    تأیید
                                </a>
                            <?php endif; ?>

                            <?php if (($doc['verification_status'] ?? 'pending') !== 'rejected'): ?>
                                <a href="change_document_status.php?id=<?= (int)$doc['id'] ?>&status=rejected&user_id=<?= (int)$user_id ?>"
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirm('آیا از رد این مدرک مطمئن هستید؟')">
                                    رد
                                </a>
                            <?php endif; ?>
                            <a href="EditDocument.php?id=<?= (int)$doc['id'] ?>"
                               class="btn btn-sm btn-warning">
                                ویرایش
                            </a>

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
<div class="modal fade" id="editDocumentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title">ویرایش مدرک</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <input type="hidden" name="document_id" id="edit_document_id">

                    <div class="mb-3">
                        <label class="form-label">نام مدرک</label>
                        <input type="text" name="document_name" id="edit_document_name" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">نوع مدرک</label>
                        <select name="document_type" id="edit_document_type" class="form-select" required>
                            <option value="">انتخاب کنید</option>
                            <option value="national_card">کارت ملی</option>
                            <option value="birth_certificate">شناسنامه</option>
                            <option value="ownership_document">سند مالکیت</option>
                            <option value="bank_document">مدرک بانکی</option>
                            <option value="other">سایر</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" name="edit_document" class="btn btn-primary">
                        ذخیره تغییرات
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal برای نمایش یادداشت‌های ادمین -->
<div class="modal fade" id="notesModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">یادداشت‌های ادمین</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <textarea id="adminNoteText" class="form-control" rows="4" placeholder="یادداشتی برای این سند بنویسید..."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">بستن</button>
                <button type="button" class="btn btn-primary" onclick="saveAdminNote()">ذخیره یادداشت</button>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    let currentDocId = null;
    const notesModal = new bootstrap.Modal(document.getElementById('notesModal'));

    function showAdminNotes(note, id) {
        currentDocId = id;
        document.getElementById('adminNoteText').value = note;
        notesModal.show();
    }

    function updateStatus(id, status) {
        if(confirm('آیا از تغییر وضعیت این سند مطمئن هستید؟')) {
            window.location.href = `update_doc_status.php?id=${id}&status=${status}&user_id=<?= $user_id ?>`;
        }
    }

    function saveAdminNote() {
        const note = document.getElementById('adminNoteText').value;
        // اینجا می‌توانید از AJAX استفاده کنید یا به یک فایل PHP ارسال کنید
        window.location.href = `save_doc_note.php?id=${currentDocId}&note=${encodeURIComponent(note)}&user_id=<?= $user_id ?>`;
    }

    $(document).on('click', '.btn-delete-doc', function() {
        const docId = $(this).data('id');
        const row = $(this).closest('tr'); // پیدا کردن ردیف جدول برای حذف بصری
        console.log("مقدار docId اینجاست:", docId);

        if (docId === undefined || docId === null || docId === "") {
            alert("خطا: ID مدرک پیدا نشد! کد HTML را چک کنید.");
            return; // اگر آیدی نداشت، درخواست نفرست
        }
        Swal.fire({
            title: 'آیا مطمئن هستید؟',
            text: "این مدرک برای همیشه حذف خواهد شد!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'بله، حذف شود',
            cancelButtonText: 'انصراف'
        }).then((result) => {
            if (result.isConfirmed) {
                // ارسال درخواست به فایلی که فرستادید
                $.ajax({
                    url: 'delete_document.php',
                    type: 'POST',
                    data: { document_id: docId },
                    success: function(response) {

                        if (response.success) {
                            Swal.fire('حذف شد!', response.message, 'success');
                            row.fadeOut(500, function() { $(this).remove(); }); // حذف ردیف از جدول
                        } else {
                            Swal.fire('خطا!', response.message, 'error');
                            console.log(response);
                        }
                    },
                    error: function() {
                        Swal.fire('خطا!', 'ارتباط با سرور برقرار نشد.', 'error');
                    }
                });
            }
        });
    });
    const editDocumentModal = new bootstrap.Modal(document.getElementById('editDocumentModal'));

    function openEditModal(id, name, type) {
        document.getElementById('edit_document_id').value = id;
        document.getElementById('edit_document_name').value = name;
        document.getElementById('edit_document_type').value = type;

        editDocumentModal.show();
    }
</script>

<?php
$content = ob_get_clean();
$title = "گروه مسکن سازان";
include '../partials/layout.php';
include '../partials/slider.php';
?>