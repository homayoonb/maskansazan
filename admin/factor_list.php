<?php
// اتصال به دیتابیس (اگر قبلاً در فایل اصلی متصل نشده‌اید)
$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
$pdo = new PDO("mysql:host=localhost;dbname=maskanhb_maskansazan;charset=utf8mb4",
        "root",
        "",
        [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
$stmt = $pdo->prepare("SELECT * FROM expenses ORDER BY id DESC");
$expenses = $stmt->fetchAll();
ob_start()
?>

<div class="card shadow-sm border-0 rounded-3 mt-3">
    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
        <h5 class="mb-0 fw-bold text-primary">مدیریت فاکتورها</h5>
        <a href="add_factor.php?project_id=<?php echo $project_id?>" class="btn btn-primary btn-sm rounded-pill px-3" >
            <i class="bi bi-plus-lg me-1"></i> ثبت فاکتور جدید
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle text-center mb-0">
                <thead class="table-light text-secondary">
                <tr>
                    <th>کد</th>
                    <th>تاریخ</th>
                    <th>مبلغ (تومان)</th>
                    <th>نوع جنس</th>
                    <th>نحوه پرداخت</th>
                    <th>نام فروشگاه</th>
                    <th>نام فروشنده</th>
                    <th>تلفن</th>
                    <th>آدرس</th>
                    <th>تصویر فاکتور</th>
                    <th>شماره فاکتور</th>
                    <th>عملیات</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($expenses as $item): ?>
                    <tr>
                        <td><?= $item['invoice_date'] ?></td>
                        <td class="fw-bold text-dark"><?= number_format($item['amount']) ?></td>
                        <td><?= $item['item_type'] ?></td>
                        <td>
                             <span class="badge rounded-pill <?= $item['payment_method']=='cash' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning' ?>">
                                <?= $item['payment_method'] == 'cash' ? 'نقدی' : ($item['payment_method'] == 'check' ? 'چک' : 'حواله') ?>
                             </span>
                        </td>
                        <td><?= $item['shop_name'] ?></td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary border-0" onclick="openImageModal('uploads/<?= $item['invoice_image'] ?>')">
                                <i class="bi bi-image"></i>
                            </button>
                        </td>
                        <td>
                            <i class="bi bi-three-dots-vertical cursor-pointer"></i>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- مودال تصویر فاکتور (اگر در فایل اصلی نیست اینجا بگذارید) -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <img id="modalImage" src="" class="img-fluid rounded" alt="فاکتور">
        </div>
    </div>
</div>

<script>
    function openImageModal(src) {
        document.getElementById('modalImage').src = src;
        new bootstrap.Modal(document.getElementById('imageModal')).show();
    }
</script>
