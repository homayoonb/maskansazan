<?php
// admin/edit_check_out.php
declare(strict_types=1);

session_start();

header('X-Content-Type-Options: nosniff');

function normalizeDigits(string $value): string
{
    return strtr($value, [
            '۰' => '0',
            '۱' => '1',
            '۲' => '2',
            '۳' => '3',
            '۴' => '4',
            '۵' => '5',
            '۶' => '6',
            '۷' => '7',
            '۸' => '8',
            '۹' => '9',
            '٠' => '0',
            '١' => '1',
            '٢' => '2',
            '٣' => '3',
            '٤' => '4',
            '٥' => '5',
            '٦' => '6',
            '٧' => '7',
            '٨' => '8',
            '٩' => '9',
    ]);
}

function cleanAmount(mixed $value): float
{
    $value = normalizeDigits((string)$value);
    $value = str_replace([',', '،', ' '], '', $value);
    $value = preg_replace('/[^\d.]/', '', $value) ?? '';

    return $value === '' ? 0.0 : (float)$value;
}

function e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// اتصال PDO
try {
    $pdo = new PDO(
            'mysql:host=localhost;dbname=maskanhb_maskansazan;charset=utf8mb4',
            'root',
            '',
            [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
            ]
    );
} catch (PDOException $e) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode(
                [
                        'success' => false,
                        'message' => 'خطا در اتصال به پایگاه داده',
                ],
                JSON_UNESCAPED_UNICODE
        );
        exit;
    }

    http_response_code(500);
    echo '<div class="alert alert-danger m-3">خطا در اتصال به پایگاه داده</div>';
    exit;
}

/*
|--------------------------------------------------------------------------
| پردازش POST
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    try {
        $checkId = (int)($_POST['id'] ?? 0);

        $subchapter = trim((string)($_POST['subchapter'] ?? ''));
        $transaction = trim((string)($_POST['transaction'] ?? ''));
        $recipientNationalId = trim(
                (string)($_POST["recipient's_national_iD"] ?? '')
        );

        $checkAmount = cleanAmount($_POST['check_amount'] ?? '0');
        $checkNumber = trim((string)($_POST['check_number'] ?? ''));
        $checkIssuanceDate = trim(
                (string)($_POST['check_issuance_date'] ?? '')
        );
        $checkDueDate = trim((string)($_POST['check_due_date'] ?? ''));

        $bank = trim((string)($_POST['bank'] ?? ''));
        $behalf = trim((string)($_POST['behalf'] ?? ''));
        $recipient = trim((string)($_POST['recipient'] ?? ''));
        $project = trim((string)($_POST['project'] ?? ''));

        $passedFailed = trim(
                (string)($_POST['passed/failed'] ?? 'نشده')
        );

        $whereApproved = trim(
                (string)($_POST['Where_did_it_get_approved'] ?? '')
        );

        if ($checkId <= 0) {
            echo json_encode(
                    [
                            'success' => false,
                            'message' => 'شناسه چک نامعتبر است.',
                    ],
                    JSON_UNESCAPED_UNICODE
            );
            exit;
        }

        if ($subchapter === '') {
            echo json_encode(
                    [
                            'success' => false,
                            'message' => 'زیرفصل را وارد کنید.',
                    ],
                    JSON_UNESCAPED_UNICODE
            );
            exit;
        }

        if ($transaction === '') {
            echo json_encode(
                    [
                            'success' => false,
                            'message' => 'سرفصل را وارد کنید.',
                    ],
                    JSON_UNESCAPED_UNICODE
            );
            exit;
        }

        if ($checkNumber === '' || $checkDueDate === '' || $checkAmount <= 0) {
            echo json_encode(
                    [
                            'success' => false,
                            'message' => 'شماره چک، تاریخ سررسید و مبلغ معتبر الزامی است.',
                    ],
                    JSON_UNESCAPED_UNICODE
            );
            exit;
        }

        // دریافت رکورد فعلی
        $selectStmt = $pdo->prepare(
                'SELECT `project_id`
             FROM `check_output`
             WHERE `id` = ?
             LIMIT 1'
        );
        $selectStmt->execute([$checkId]);

        $current = $selectStmt->fetch();

        if (!$current) {
            echo json_encode(
                    [
                            'success' => false,
                            'message' => 'چک مورد نظر یافت نشد.',
                    ],
                    JSON_UNESCAPED_UNICODE
            );
            exit;
        }

        $projectId = (int)($current['project_id'] ?? 0);

        /*
         * تمام پارامترها موقعیتی هستند.
         * نام ستون‌های دارای apostrophe و slash با backtick نوشته شده‌اند.
         */
        $sql = "UPDATE `check_output` SET 
            `subchapter`                  = ?,
            `transaction`                 = ?,
            `cost`                        = ?,
            `behalf`                      = ?,
            `recipient`                   = ?,
            `recipient's_national_iD`     = ?,
            `check_amount`                = ?,
            `check_number`                = ?,
            `check_issuance_date`         = ?,
            `check_due_date`              = ?,
            `bank`                        = ?,
            `project`                     = ?,
            `passed/failed`               = ?,
            `Where_did_it_get_approved`   = ?
        WHERE `id` = ?";


        $params = [
                $subchapter,
                $transaction,
                'هزینه',
                $behalf,
                $recipient,
                $recipientNationalId,
                $checkAmount,
                $checkNumber,
                $checkIssuanceDate,
                $checkDueDate,
                $bank,
                $project,
                $passedFailed,
                $whereApproved,
                $checkId // برای WHERE id = ?
        ];

        $updateStmt = $pdo->prepare($sql);
        $updateStmt->execute($params);


        echo json_encode(
                [
                        'success' => true,
                        'message' => 'تغییرات چک با موفقیت ذخیره شد.',
                        'project_id' => $projectId,
                ],
                JSON_UNESCAPED_UNICODE
        );
        exit;
    } catch (Throwable $e) {
        error_log($e->getMessage());

        echo json_encode(
                [
                        'success' => false,
                        'message' => 'خطا در ذخیره اطلاعات چک.',
                ],
                JSON_UNESCAPED_UNICODE
        );
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| دریافت فرم در GET
|--------------------------------------------------------------------------
*/
header('Content-Type: text/html; charset=utf-8');

$checkId = (int)($_GET['id'] ?? 0);

if ($checkId <= 0) {
    echo '<div class="alert alert-danger m-3">شناسه چک نامعتبر است.</div>';
    exit;
}

$selectStmt = $pdo->prepare(
        'SELECT *
     FROM `check_output`
     WHERE `id` = ?
     LIMIT 1'
);
$selectStmt->execute([$checkId]);

$check = $selectStmt->fetch();

if (!$check) {
    echo '<div class="alert alert-warning m-3">چک پیدا نشد.</div>';
    exit;
}

$projectId = (int)($check['project_id'] ?? 0);
?>

<!-- Persian Datepicker CSS -->
<link rel="stylesheet" href="https://unpkg.com/@majidh1/jalalidatepicker/dist/jalalidatepicker.min.css">
<!-- Persian Datepicker JS -->
<script type="text/javascript" src="https://unpkg.com/@majidh1/jalalidatepicker/dist/jalalidatepicker.min.js"></script>
<form id="ajaxEditCheckForm" method="POST">
    <input type="hidden" name="id" value="<?= (int)($check['id'] ?? 0) ?>">
    <input type="hidden" name="project_id" id="currentProjectId"
           value="<?= (int)($check['project_id'] ?? $projectId ?? $_GET['project_id'] ?? 0) ?>">


    <!-- کادر نمایش صرفاً خطاها -->
    <div id="editCheckAlert" class="d-none alert py-2 px-3 small mb-3" role="alert"></div>

    <div class="row g-3">

        <!-- زیرفصل -->
        <div class="col-md-6">
            <label class="form-label small fw-bold" for="subchapter">زیرفصل <span class="text-danger">*</span></label>
            <input type="text" id="subchapter" name="subchapter"
                   class="form-control form-control-sm"
                   value="<?= htmlspecialchars((string)($check['subchapter'] ?? '')) ?>" required>
        </div>

        <!-- سرفصل -->
        <div class="col-md-6">
            <label class="form-label small fw-bold" for="transaction">سرفصل <span class="text-danger">*</span></label>
            <input type="text" id="transaction" name="transaction"
                   class="form-control form-control-sm"
                   value="<?= htmlspecialchars((string)($check['transaction'] ?? '')) ?>" required>
        </div>

        <!-- کد ملی دریافتکننده / شناسه ملی -->
        <div class="col-md-6">
            <label class="form-label small fw-bold" for="recipient_national_id">کد ملی دریافتکننده / شناسه ملی <span class="text-danger">*</span></label>
            <input type="text" id="recipient_national_id" name="recipient's_national_iD"
                   class="form-control form-control-sm text-start font-monospace"
                   dir="ltr" inputmode="numeric" autocomplete="off"
                   value="<?= htmlspecialchars((string)($check["recipient's_national_iD"] ?? '')) ?>" required>
        </div>

        <!-- شماره چک -->
        <div class="col-md-6">
            <label class="form-label small fw-bold" for="check_number">شماره چک</label>
            <input type="text" id="check_number" name="check_number"
                   class="form-control form-control-sm text-start font-monospace"
                   dir="ltr" inputmode="numeric" autocomplete="off"
                   value="<?= htmlspecialchars((string)($check['check_number'] ?? '')) ?>">
        </div>
        <!-- دریافت کننده -->
        <div class="col-md-6">
            <label class="form-label small fw-bold" for="recipient">دریافت کننده</label>
            <input type="text" id="recipient" name="recipient"
                   class="form-control form-control-sm text-start font-monospace"
                   dir="ltr" inputmode="numeric" autocomplete="off"
                   value="<?= htmlspecialchars((string)($check['recipient'] ?? '')) ?>">
        </div>

        <!-- مبلغ چک -->
        <div class="col-md-6">
            <label class="form-label small fw-bold" for="check_amount">مبلغ چک (ریال) <span class="text-danger">*</span></label>
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light text-muted small">ریال</span>
                <input type="text" id="check_amount" name="check_amount"
                       class="form-control text-start fw-bold font-monospace"
                       dir="ltr" inputmode="numeric" autocomplete="off"
                       value="<?= (!empty($check['check_amount']) && (float)$check['check_amount'] > 0) ? number_format((float)$check['check_amount']) : '' ?>"
                       placeholder="0" required>
            </div>
        </div>

        <!-- بانک -->
        <div class="col-md-6">
            <label class="form-label small fw-bold" for="bank">بانک <span class="text-danger">*</span></label>
            <input type="text" id="bank" name="bank"
                   class="form-control form-control-sm"
                   value="<?= htmlspecialchars((string)($check['bank'] ?? '')) ?>" required>
        </div>

        <!-- تاریخ صدور چک -->
        <div class="col-md-6">
            <label class="form-label small fw-bold" for="check_issuance_date">تاریخ صدور چک <span class="text-danger">*</span></label>
            <input type="text" id="check_issuance_date" name="check_issuance_date"
                   class="form-control form-control-sm fw-bold text-success text-start"
                   dir="ltr" autocomplete="off" data-jdp placeholder="1404/01/01"
                   value="<?= htmlspecialchars((string)($check['check_issuance_date'] ?? '')) ?>" required>
        </div>

        <!-- موعد چک -->
        <div class="col-md-6">
            <label class="form-label small fw-bold" for="check_due_date">موعد چک <span class="text-danger">*</span></label>
            <input type="text" id="check_due_date" name="check_due_date" data-jdp
                   class="form-control form-control-sm fw-bold text-success text-start"
                   dir="ltr" autocomplete="off" placeholder="1404/01/01"
                   value="<?= htmlspecialchars((string)($check['check_due_date'] ?? '')) ?>" required>
        </div>

        <!-- بابت -->
        <div class="col-md-6">
            <label class="form-label small fw-bold" for="behalf">بابت <span class="text-danger">*</span></label>
            <input type="text" id="behalf" name="behalf"
                   class="form-control form-control-sm"
                   value="<?= htmlspecialchars((string)($check['behalf'] ?? '')) ?>" required>
        </div>

        <!-- پروژه -->
        <div class="col-md-6">
            <label class="form-label small fw-bold" for="project">پروژه <span class="text-danger">*</span></label>
            <input type="text" id="project" name="project"
                   class="form-control form-control-sm"
                   value="<?= htmlspecialchars((string)($check['project'] ?? '')) ?>" required>
        </div>

        <!-- پاس شده / نشده -->
        <div class="col-md-6">
            <label class="form-label small fw-bold" for="passed_failed">پاس شده / نشده <span class="text-danger">*</span></label>
            <select class="form-select form-select-sm" id="passed_failed" name="passed/failed">
                <option value="نشده" <?= (($check['passed/failed'] ?? '') === 'نشده') ? 'selected' : '' ?>>نشده</option>
                <option value="پاس شده" <?= (($check['passed/failed'] ?? '') === 'پاس شده') ? 'selected' : '' ?>>پاس شده</option>
            </select>
        </div>

        <!-- از کجا پاس شده -->
        <div class="col-md-6">
            <label class="form-label small fw-bold" for="where_approved">از کجا پاس شده؟</label>
            <input type="text" id="where_approved" name="Where_did_it_get_approved"
                   class="form-control form-control-sm"
                   value="<?= htmlspecialchars((string)($check['Where_did_it_get_approved'] ?? '')) ?>">
        </div>

    </div><!-- /.row -->

    <div class="modal-footer border-0 px-0 pb-0 pt-3 mt-3">
        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">انصراف</button>
        <button type="submit" class="btn btn-sm btn-primary" id="btnSaveCheck">
            <i class="bi bi-check2 me-1"></i> ذخیره تغییرات
        </button>
    </div>
</form>


<script>
    (function() {
        const form = document.getElementById('ajaxEditCheckForm');
        if (!form) return;

        // ۴. ثبت تغییرات فرم با AJAX (POST)
        document.addEventListener('submit', function (e) {
            const form = e.target.closest('#ajaxEditCheckForm');
            if (!form) return;

            e.preventDefault();

            const alertBox = form.querySelector('#editCheckAlert');
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalBtnHtml = submitBtn ? submitBtn.innerHTML : '';

            // ریست کادر خطا
            if (alertBox) {
                alertBox.classList.add('d-none');
                alertBox.classList.remove('alert-danger');
                alertBox.innerHTML = '';
            }

            // فعال‌سازی حالت لودینگ دکمه
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> در حال ذخیره...`;
            }

            // ID به صورت خودکار از طریق FormData ارسال می‌شود، نیازی به متغیر خارجی نیست
            const formData = new FormData(form);

            fetch('edit_check_out.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // موفقیت: رفرش مستقیم صفحه
                        window.location.reload();
                    } else {
                        throw new Error(data.message || 'خطا در ثبت اطلاعات.');
                    }
                })
                .catch(err => {
                    if (alertBox) {
                        alertBox.classList.remove('d-none');
                        alertBox.classList.add('alert-danger');
                        alertBox.innerHTML = `<i class="bi bi-exclamation-triangle-fill me-1"></i> ${err.message || 'خطای غیرمنتظره رخ داد.'}`;
                    } else {
                        alert(err.message || 'خطا در برقراری ارتباط');
                    }

                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalBtnHtml;
                    }
                });
        });

    })();
    document.addEventListener("DOMContentLoaded", function () {
        if (typeof jalaliDatepicker !== 'undefined') {
            jalaliDatepicker.startWatch({
                minDate: "attr",
                maxDate: "attr",
                time: false
            });
        } else {
            console.error("فایل جاوااسکریپت jalalidatepicker بارگذاری نشده است.");
        }
    });
</script>
