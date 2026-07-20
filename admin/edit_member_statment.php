<?php
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
$user_project_id = isset($_GET['user_project_id']) ? (int)$_GET['user_project_id'] : 0;

if ($id <= 0) {
    die('شناسه قسط معتبر نیست.');
}

$success_msg = '';
$error_msg = '';

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

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $installment_title  = trim($_POST['installment_title'] ?? '');
        $installment_date   = trim($_POST['installment_date'] ?? '');
        $installment_amount = trim($_POST['installment_amount'] ?? '');
        $paid_amount        = trim($_POST['paid_amount'] ?? '');
        $payment_method     = trim($_POST['payment_method'] ?? '');
        $description        = trim($_POST['description'] ?? '');

        // حذف جداکننده‌ها از مبالغ
        $installment_amount = str_replace([',', ' '], '', $installment_amount);
        $paid_amount        = str_replace([',', ' '], '', $paid_amount);

        if (
                $installment_title === '' ||
                $installment_date === '' ||
                $installment_amount === '' ||
                $paid_amount === '' ||
                $payment_method === ''
        ) {
            $error_msg = 'لطفاً تمام فیلدهای الزامی را تکمیل کنید.';
        } elseif (!is_numeric($installment_amount) || !is_numeric($paid_amount)) {
            $error_msg = 'مبلغ قسط و مبلغ پرداختی باید عددی باشند.';
        } else {
            $updateStmt = $pdo->prepare("
                UPDATE member_statements
                SET
                    installment_title = :installment_title,
                    installment_date = :installment_date,
                    installment_amount = :installment_amount,
                    paid_amount = :paid_amount,
                    payment_method = :payment_method,
                    description = :description
                WHERE id = :id
                LIMIT 1
            ");

            $updateStmt->execute([
                    ':installment_title'  => $installment_title,
                    ':installment_date'   => $installment_date,
                    ':installment_amount' => $installment_amount,
                    ':paid_amount'        => $paid_amount,
                    ':payment_method'     => $payment_method,
                    ':description'        => $description,
                    ':id'                 => $id
            ]);

            header("Location: member_statements.php?user_project_id=" . urlencode((string)$user_project_id) . "&project_id=" . urlencode((string)$project_id) . "&success=updated");
            exit;
        }
    }

    $stmt = $pdo->prepare("SELECT * FROM member_statements WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $rows = $stmt->fetch();

    if (!$rows) {
        die('رکورد مورد نظر یافت نشد.');
    }

} catch (PDOException $e) {
    die('خطا در دیتابیس: ' . $e->getMessage());
}

ob_start();
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-xl-10 col-lg-11">

            <div class="card border-0 shadow-lg rounded-4 overflow-hidden">

                <div class="bg-primary text-white p-4 p-md-5">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-white text-primary rounded-circle d-flex align-items-center justify-content-center shadow"
                             style="width:70px;height:70px;">
                            <i class="bi bi-cash-coin fs-2"></i>
                        </div>
                        <div>
                            <h2 class="fw-bold mb-1">ویرایش اطلاعات قسط</h2>
                            <p class="mb-0 opacity-75">
                                لطفاً اطلاعات قسط را ویرایش کنید.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="card-body p-4 p-md-5 bg-light">

                    <?php if (!empty($success_msg)): ?>
                        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <?= htmlspecialchars($success_msg, ENT_QUOTES, 'UTF-8') ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($error_msg)): ?>
                        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i>
                            <?= htmlspecialchars($error_msg, ENT_QUOTES, 'UTF-8') ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" id="editMemberForm">

                        <div class="mb-4">
                            <h5 class="fw-bold text-dark border-bottom pb-2 mb-4">
                                <i class="bi bi-info-circle-fill text-primary me-2"></i>
                                اطلاعات قسط
                                <?= htmlspecialchars($rows['member_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                            </h5>
                        </div>

                        <div class="row g-4">

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">عنوان قسط</label>
                                <input
                                        type="text"
                                        name="installment_title"
                                        class="form-control form-control-lg rounded-3"
                                        placeholder="مثلاً قسط اول پروژه"
                                        value="<?= htmlspecialchars($rows['installment_title'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                        required
                                >
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">تاریخ قسط</label>
                                <input
                                        type="text"
                                        name="installment_date"
                                        class="form-control form-control-lg rounded-3"
                                        placeholder="مثلاً 1403/06/15"
                                        value="<?= htmlspecialchars($rows['installment_date'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                        required
                                >
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">مبلغ قسط</label>
                                <div class="input-group input-group-lg">
                                    <input
                                            type="text"
                                            name="installment_amount"
                                            class="form-control rounded-start-3"
                                            placeholder="مثلاً 50000000"
                                            value="<?= htmlspecialchars($rows['installment_amount'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                            required
                                    >
                                    <span class="input-group-text rounded-end-3">ریال</span>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">مبلغ پرداختی</label>
                                <div class="input-group input-group-lg">
                                    <input
                                            type="text"
                                            name="paid_amount"
                                            class="form-control rounded-start-3"
                                            placeholder="مثلاً 25000000"
                                            value="<?= htmlspecialchars($rows['paid_amount'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                            required
                                    >
                                    <span class="input-group-text rounded-end-3">ریال</span>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">روش پرداخت</label>
                                <input
                                        type="text"
                                        name="payment_method"
                                        class="form-control rounded-3"
                                        value="<?= htmlspecialchars($rows['payment_method'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                        required
                                >
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">توضیحات</label>
                                <textarea
                                        name="description"
                                        class="form-control rounded-3"
                                        rows="4"
                                        placeholder="در صورت نیاز توضیحات تکمیلی را وارد کنید..."
                                ><?= htmlspecialchars($rows['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                            </div>
                        </div>

                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-stretch align-items-md-center gap-3 mt-5 pt-4 border-top">
                            <a
                                    href="member_statements.php?user_project_id=<?= (int)$user_project_id ?>&project_id=<?= urlencode((string)$project_id) ?>"
                                    class="btn btn-outline-secondary btn-lg rounded-3 px-4"
                            >
                                <i class="bi bi-arrow-right me-1"></i>
                                لغو و بازگشت
                            </a>

                            <button type="submit" id="submitBtn" class="btn btn-primary btn-lg rounded-3 px-5 shadow-sm">
                                <span id="submitIcon">
                                    <i class="bi bi-check-circle me-1"></i>
                                </span>
                                <span id="submitText">ذخیره تغییرات</span>
                                <span
                                        id="submitSpinner"
                                        class="spinner-border spinner-border-sm ms-2 d-none"
                                        role="status"
                                        aria-hidden="true"
                                ></span>
                            </button>
                        </div>

                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    document.getElementById('editMemberForm').addEventListener('submit', function () {
        const btn = document.getElementById('submitBtn');
        const spinner = document.getElementById('submitSpinner');
        const text = document.getElementById('submitText');

        btn.disabled = true;
        spinner.classList.remove('d-none');
        text.textContent = 'در حال ذخیره...';
    });
</script>

<?php
$content = ob_get_clean();
$title = "ویرایش اطلاعات قسط";
include '../partials/layout.php';
?>
