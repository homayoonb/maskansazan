<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

/* فقط درخواست Ajax */
if (!isset($_GET['ajax'])) {
    exit;
}

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/helpers.php';

$db   = new Database();
$conn = $db->connect();

/* ===============================
   ورودی‌ها
================================ */
$user_id = (int)($_GET['user_id'] ?? 0);
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = max(1, (int)($_GET['per_page'] ?? 10));
$search  = trim($_GET['search'] ?? '');
$status  = $_GET['status'] ?? '';
$sort    = $_GET['doc_sort'] ?? 'upload_date';
$order   = ($_GET['doc_order'] === 'asc') ? 'ASC' : 'DESC';

/* ===============================
   ستون‌های مجاز مرتب‌سازی
================================ */
$allowedSort = ['id','document_type','file_size','upload_date','verification_status'];
$sort = in_array($sort, $allowedSort, true) ? $sort : 'upload_date';

/* ===============================
   صفحه‌بندی
================================ */
$offset = ($page - 1) * $perPage;

/* ===============================
   WHERE پایه
================================ */
$where  = "WHERE user_id = ?";
$params = [$user_id];
$types  = "i";

/* جستجو */
if ($search !== '') {
    $where   .= " AND (document_type LIKE ? OR description LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $types   .= "ss";
}

/* فیلتر وضعیت */
if ($status !== '') {
    $where   .= " AND verification_status = ?";
    $params[] = $status;
    $types   .= "s";
}

/* ===============================
   COUNT (برای pagination)
================================ */
$countSql = "SELECT COUNT(*) FROM user_documents $where";
$stmt = $conn->prepare($countSql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$stmt->bind_result($totalRows);
$stmt->fetch();
$stmt->close();

$totalPages = max(1, ceil($totalRows / $perPage));

/* ===============================
   DATA
================================ */
$sql = "
    SELECT
        id,
        document_type,
        description,
        file_path,
        file_size,
        upload_date,
        verification_status
    FROM user_documents
    $where
    ORDER BY $sort $order
    LIMIT ?, ?
";

$paramsData = $params;
$typesData  = $types . "ii";
$paramsData[] = $offset;
$paramsData[] = $perPage;

$stmt = $conn->prepare($sql);
$stmt->bind_param($typesData, ...$paramsData);
$stmt->execute();
$result = $stmt->get_result();
$documents = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* ===============================
   ROWS
================================ */
ob_start();

if (empty($documents)):
?>
<tr>
    <td colspan="8" class="text-center text-muted py-4">
        مدرکی یافت نشد
    </td>
</tr>
<?php
else:
foreach ($documents as $doc):
    $filePath = !empty($doc['file_path']) ? fixImagePath2($doc['file_path']) : '';
    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $isImage = in_array($ext, ['jpg','jpeg','png','gif','bmp','webp'], true);
    $extension = strtolower(pathinfo($filePath ?? '', PATHINFO_EXTENSION));

$imageExt = ['jpg','jpeg','png','gif','webp'];
$pdfExt   = ['pdf'];
$wordExt  = ['doc','docx'];
$excelExt = ['xls','xlsx'];
$zipExt   = ['zip','rar','7z'];
$textExt  = ['txt','csv','log'];

?>
<tr>
    <td><?= safeEcho($doc['id']) ?></td>

    <td><?= safeEcho($doc['document_type']) ?></td>

    <!-- فایل -->
<td>
<?php if ($filePath): ?>

    <?php if (in_array($extension, $imageExt)): ?>
        <!-- تصویر -->
        <a href="<?= safeEcho($filePath) ?>" download>
            <img src="<?= safeEcho($filePath) ?>"
                 class="image-preview"
                 title="دانلود تصویر">
        </a>

    <?php elseif (in_array($extension, $pdfExt)): ?>
        <!-- PDF -->
        <a href="<?= safeEcho($filePath) ?>" target="_blank"
           class="btn btn-outline-danger btn-sm">
            <i class="bi bi-file-earmark-pdf"></i> PDF
        </a>

    <?php elseif (in_array($extension, $wordExt)): ?>
        <!-- Word -->
        <a href="<?= safeEcho($filePath) ?>" download
           class="btn btn-outline-primary btn-sm">
            <i class="bi bi-file-earmark-word"></i> Word
        </a>

    <?php elseif (in_array($extension, $excelExt)): ?>
        <!-- Excel -->
        <a href="<?= safeEcho($filePath) ?>" download
           class="btn btn-outline-success btn-sm">
            <i class="bi bi-file-earmark-excel"></i> Excel
        </a>

    <?php elseif (in_array($extension, $zipExt)): ?>
        <!-- Zip -->
        <a href="<?= safeEcho($filePath) ?>" download
           class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-file-earmark-zip"></i> ZIP
        </a>

    <?php elseif (in_array($extension, $textExt)): ?>
        <!-- Text -->
        <a href="<?= safeEcho($filePath) ?>" target="_blank"
           class="btn btn-outline-light btn-sm">
            <i class="bi bi-file-earmark-text"></i> متن
        </a>

    <?php else: ?>
        <!-- سایر -->
        <a href="<?= safeEcho($filePath) ?>" download
           class="btn btn-outline-info btn-sm">
            <i class="bi bi-file-earmark"></i> دانلود
        </a>
    <?php endif; ?>

<?php else: ?>
    <span class="text-muted">—</span>
<?php endif; ?>
</td>


    <!-- توضیحات -->
    <td>
        <small class="text-white-50">
            <?= safeEcho(mb_substr($doc['description'] ?? '', 0, 50)) ?>
        </small>
    </td>

    <!-- حجم -->
    <td>
        <small><?= formatFileSize((int)$doc['file_size']) ?></small>
    </td>

    <!-- وضعیت -->
    <td>
        <?php if ($doc['verification_status'] === 'verified'): ?>
            <span class="badge bg-success">تأیید شده</span>
        <?php elseif ($doc['verification_status'] === 'rejected'): ?>
            <span class="badge bg-danger">رد شده</span>
        <?php else: ?>
            <span class="badge bg-warning">در انتظار</span>
        <?php endif; ?>
    </td>

    <!-- تاریخ -->
    <td>
        <small class="text-white-50">
            <?= safeEcho(substr($doc['upload_date'], 0, 10)) ?>
        </small>
    </td>

    <!-- عملیات -->
    <td>
        <div class="d-flex gap-1">
            <button type="button" class="btn btn-outline-success btn-sm"
                onclick="updateDocumentStatus(<?= (int)$doc['id'] ?>,'verified')">
                <i class="bi bi-check-lg"></i>
            </button>

            <button type="button" class="btn btn-outline-danger btn-sm"
                onclick="updateDocumentStatus(<?= (int)$doc['id'] ?>,'rejected')">
                <i class="bi bi-x-lg"></i>
            </button>

            <button type="button" class="btn btn-outline-danger btn-sm"
                onclick="deleteDocument(<?= (int)$doc['id'] ?>)">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    </td>
</tr>
<?php
endforeach;
endif;

$rowsHtml = ob_get_clean();

/* ===============================
   PAGINATION HTML
================================ */
$paginationHtml = '';

if ($totalPages > 1) {
    $paginationHtml .= '<nav><ul class="pagination justify-content-center">';

    for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++) {
        $active = ($i === $page) ? ' active' : '';
        $paginationHtml .= "
            <li class='page-item{$active}'>
                <a class='page-link' href='javascript:void(0)'
                   onclick='goToPage({$i})'>{$i}</a>
            </li>";
    }

    $paginationHtml .= '</ul></nav>';
}

/* ===============================
   OUTPUT
================================ */
echo $rowsHtml;
echo '<!--PAGINATION-->';
echo $paginationHtml;
exit;
