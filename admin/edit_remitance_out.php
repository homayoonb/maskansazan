<?php
// admin/edit_remitance_out.php
declare(strict_types=1);

session_start();

header('X-Content-Type-Options: nosniff');

function normalizeDigits(string $value): string
{
    return strtr($value, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
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
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
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
        echo json_encode([
                'success' => false,
                'message' => 'خطا در اتصال به پایگاه داده'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code(500);
    echo '<div class="alert alert-danger m-3">خطا در اتصال به پایگاه داده</div>';
    exit;
}

/*
|--------------------------------------------------------------------------
| ۱. پردازش ثبت ویرایش (POST)
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    try {
        $remitanceOutId = (int)($_POST['id'] ?? 0);

        if ($remitanceOutId <= 0) {
            echo json_encode(['success' => false, 'message' => 'شناسه حواله نامعتبر است.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // دریافت و اعتبارسنجی مقادیر فرم
        $subchapter         = trim((string)($_POST['subchapter'] ?? ''));
        $transaction        = trim((string)($_POST['transaction'] ?? ''));
        $branchCode         = trim((string)($_POST['branch_code'] ?? ''));
        $branch             = trim((string)($_POST['branch'] ?? ''));
        $history            = trim((string)($_POST['history'] ?? ''));
        $clock              = trim((string)($_POST['clock'] ?? ''));
        $document           = trim((string)($_POST['document'] ?? ''));
        $invoiceNumber      = trim((string)($_POST['invoice_number'] ?? ''));
        $harvest            = cleanAmount($_POST['harvest'] ?? '0');
        $diposit            = cleanAmount($_POST['diposit'] ?? '0');
        $remainder          = cleanAmount($_POST['remainder'] ?? '0');
        $beneficiary        = trim((string)($_POST['beneficiary'] ?? ''));
        $shebaCart          = trim((string)($_POST['sheba/cart'] ?? ''));
        $behalf             = trim((string)($_POST['behalf'] ?? ''));
        $bankHesab          = trim((string)($_POST['bank/hesab'] ?? ''));
        $voroodiKhoroujy    = trim((string)($_POST['voroodi/khoroujy'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));

        if ($history === '' || $harvest <= 0) {
            echo json_encode([
                    'success' => false,
                    'message' => 'وارد کردن تاریخ و مبلغ برداشت (بزرگتر از صفر) الزامی است.'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // بررسی وجود رکورد در جدول remittance_output
        $selectStmt = $pdo->prepare('SELECT `project_id` FROM `remittance_output` WHERE `id` = ? LIMIT 1');
        $selectStmt->execute([$remitanceOutId]);
        $current = $selectStmt->fetch();

        if (!$current) {
            echo json_encode(['success' => false, 'message' => 'حواله مورد نظر یافت نشد.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $projectId = (int)($current['project_id'] ?? 0);

        // به‌روزرسانی جدول حواله خروجی
        $sql = "UPDATE `remittance_output` SET 
            `branch_code`               = ?,
            `branch`                    = ?,
            `history`                   = ?,
            `clock`                     = ?,
            `document`                  = ?,
            `invoice_number`            = ?,
            `harvest`                   = ?,
            `diposit`                   = ?,
            `remainder`                 = ?,
            `beneficiary`               = ?,
            `sheba/cart`                = ?,
            `behalf`                    = ?,
            `transaction`               = ?,
            `subchapter`                = ?,
            `bank/hesab`                = ?,
            `voroodi/khoroujy`          = ?,
            `description`               = ?
        WHERE `id` = ?";

        $params = [
                $branchCode,
                $branch,
                $history,
                $clock,
                $document,
                $invoiceNumber,
                $harvest,
                $diposit,
                $remainder,
                $beneficiary,
                $shebaCart,
                $behalf,
                $transaction,
                $subchapter,
                $bankHesab,
                $voroodiKhoroujy,
                $description,
                $remitanceOutId
        ];

        $updateStmt = $pdo->prepare($sql);
        $updateStmt->execute($params);

        echo json_encode([
                'success'    => true,
                'message'    => 'تغییرات حواله با موفقیت ذخیره شد.',
                'project_id' => $projectId,
        ], JSON_UNESCAPED_UNICODE);
        exit;

    } catch (Throwable $e) {
        error_log("Error updating remittance: " . $e->getMessage());
        echo json_encode([
                'success' => false,
                'message' => 'خطا در ذخیره اطلاعات حواله.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| ۲. واکشی فرم ویرایش (GET داخل مودال)
|--------------------------------------------------------------------------
*/
header('Content-Type: text/html; charset=utf-8');

$remitanceOutId = (int)($_GET['id'] ?? 0);

if ($remitanceOutId <= 0) {
    echo '<div class="alert alert-danger mb-0">شناسه حواله نامعتبر است.</div>';
    exit;
}

$selectStmt = $pdo->prepare('SELECT * FROM `remittance_output` WHERE `id` = ? LIMIT 1');
$selectStmt->execute([$remitanceOutId]);
$remitanceOut = $selectStmt->fetch();

if (!$remitanceOut) {
    echo '<div class="alert alert-warning mb-0">اطلاعات حواله یافت نشد.</div>';
    exit;
}

$projectId = (int)($remitanceOut['project_id'] ?? ($_GET['project_id'] ?? 0));
?>


<form id="ajaxEditRemitanceOutForm">
    <!-- پیام خطا یا موفقیت -->
    <div id="editRemitanceAlert" class="alert d-none mb-3" role="alert"></div>

    <!-- فیلدهای مخفی آی‌دی -->
    <input type="hidden" name="id" value="<?= (int)$remitanceOut['id'] ?>">
    <input type="hidden" name="project_id" value="<?= (int)$projectId ?>">

    <div class="row g-3">

        <!-- کد شعبه -->
        <div class="col-md-6">
            <label for="remit_branch_code" class="form-label fw-medium text-secondary">
                کد شعبه <span class="text-danger">*</span>
            </label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-folder2-open text-muted"></i></span>
                <input type="text"
                       id="remit_branch_code"
                       name="branch_code"
                       class="form-control"
                       placeholder="مثال: ۱۰۲۵"
                       dir="ltr"
                       inputmode="numeric"
                       value="<?= htmlspecialchars((string)($remitanceOut['branch_code'] ?? '')) ?>"
                       required>
            </div>
        </div>

        <!-- شعبه -->
        <div class="col-md-6">
            <label for="remit_branch" class="form-label fw-medium text-secondary">
                شعبه <span class="text-danger">*</span>
            </label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-tag text-muted"></i></span>
                <input type="text"
                       name="branch"
                       id="remit_branch"
                       class="form-control"
                       placeholder="مثال: مرکزی، آزادی"
                       value="<?= htmlspecialchars((string)($remitanceOut['branch'] ?? '')) ?>"
                       required>
            </div>
        </div>

        <!-- تاریخ -->
        <div class="col-md-6">
            <label for="remit_history" class="form-label fw-medium text-secondary">تاریخ</label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-calendar3 text-muted"></i></span>
                <input type="text"
                       id="remit_history"
                       name="history"
                       class="form-control text-start pwt-datepicker font-monospace"
                       data-jdp
                       dir="ltr"
                       value="<?= htmlspecialchars((string)($remitanceOut['history'] ?? '')) ?>"
                       placeholder="۱۴۰۳/۰۱/۰۱">
            </div>
        </div>

        <!-- ساعت -->
        <div class="col-md-6">
            <label for="remit_clock" class="form-label fw-medium text-secondary">ساعت</label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-clock text-muted"></i></span>
                <input type="text"
                       id="remit_clock"
                       name="clock"
                       class="form-control text-start font-monospace"
                       dir="ltr"
                       value="<?= htmlspecialchars((string)($remitanceOut['clock'] ?? '')) ?>"
                       placeholder="مثال: 14:30">
            </div>
        </div>

        <!-- سند -->
        <div class="col-md-6">
            <label for="remit_document" class="form-label fw-medium text-secondary">شماره سند</label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-file-earmark-text text-muted"></i></span>
                <input type="text"
                       id="remit_document"
                       name="document"
                       class="form-control text-start font-monospace"
                       dir="ltr"
                       value="<?= htmlspecialchars((string)($remitanceOut['document'] ?? '')) ?>"
                       placeholder="مثال: ۱۲۳۴۵">
            </div>
        </div>

        <!-- برداشت (ریال) -->
        <div class="col-md-6">
            <label for="remit_harvest" class="form-label fw-medium text-secondary">
                مبلغ برداشت (ریال) <span class="text-danger">*</span>
            </label>
            <?php
            $rawHarvest = str_replace([',', '،', ' '], '', (string)($remitanceOut['harvest'] ?? ''));
            $harvestFloat = is_numeric($rawHarvest) ? (float)$rawHarvest : 0.0;
            ?>

            <div class="input-group">
                <span class="input-group-text bg-light">ریال</span>
                <input type="text"
                       name="harvest"
                       id="remit_harvest"
                       class="form-control text-start fw-bold font-monospace text-danger"
                       dir="ltr"
                       inputmode="decimal"
                       placeholder="۰.۰۰۰"
                       value="<?= $harvestFloat > 0 ? number_format($harvestFloat, 3, '.', ',') : '' ?>"
                       oninput="formatCurrency(this)"
                       required>
            </div>

        </div>

        <!-- شماره قبض -->
        <div class="col-md-6">
            <label for="remit_invoice_number" class="form-label fw-medium text-secondary">
                شماره قبض <span class="text-danger">*</span>
            </label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-receipt text-muted"></i></span>
                <input type="text"
                       id="remit_invoice_number"
                       name="invoice_number"
                       class="form-control text-start font-monospace"
                       dir="ltr"
                       inputmode="numeric"
                       placeholder="مثال: ۹۸۷۶۵۴۳۲۱"
                       value="<?= htmlspecialchars((string)($remitanceOut['invoice_number'] ?? '')) ?>"
                       required>
            </div>
        </div>

        <!-- واریز -->
        <div class="col-md-3">
            <label for="remit_diposit" class="form-label fw-medium text-secondary">واریز</label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-arrow-down-left-circle text-muted"></i></span>
                <input type="text"
                       id="remit_diposit"
                       name="diposit"
                       class="form-control"
                       dir="ltr"
                       value="<?= !empty($remitanceOut['diposit']) ? number_format((float)$remitanceOut['diposit']) : ''  ?>"
                       inputmode="numeric"
                       oninput="formatCurrency(this)"
                       placeholder="۰">
            </div>
        </div>

        <!-- مانده -->
        <div class="col-md-3">
            <label for="remit_remainder" class="form-label fw-medium text-secondary">مانده</label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-wallet2 text-muted"></i></span>
                <input type="text"
                       id="remit_remainder"
                       name="remainder"
                       class="form-control"
                       dir="ltr"
                       value="<?= !empty($remitanceOut['remainder']) ? number_format((float)$remitanceOut['remainder']) : ''  ?>"
                       inputmode="numeric"
                       oninput="formatCurrency(this)"
                       placeholder="۰">
            </div>
        </div>

        <!-- ذینفع -->
        <div class="col-md-6">
            <label for="remit_beneficiary" class="form-label fw-medium text-secondary">ذینفع</label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-person text-muted"></i></span>
                <input type="text"
                       id="remit_beneficiary"
                       name="beneficiary"
                       class="form-control"
                       value="<?= htmlspecialchars((string)($remitanceOut['beneficiary'] ?? '')) ?>"
                       placeholder="نام شخص یا شرکت دریافت‌کننده">
            </div>
        </div>

        <!-- شبا / کارت -->
        <div class="col-md-6">
            <label for="remit_sheba_cart" class="form-label fw-medium text-secondary">شبا / کارت</label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-credit-card-2-front text-muted"></i></span>
                <input type="text"
                       id="remit_sheba_cart"
                       name="sheba/cart"
                       class="form-control text-start font-monospace"
                       dir="ltr"
                       value="<?= htmlspecialchars((string)($remitanceOut['sheba/cart'] ?? '')) ?>"
                       placeholder="IR... یا ۱۶ رقمی">
            </div>
        </div>

        <!-- بابت -->
        <div class="col-md-6">
            <label for="remit_behalf" class="form-label fw-medium text-secondary">بابت</label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-chat-left-dots text-muted"></i></span>
                <input type="text"
                       id="remit_behalf"
                       name="behalf"
                       class="form-control"
                       value="<?= htmlspecialchars((string)($remitanceOut['behalf'] ?? '')) ?>"
                       placeholder="مثال: پیش‌پرداخت، تسویه فاکتور">
            </div>
        </div>

        <!-- سرفصل -->
        <div class="col-md-6">
            <label for="remit_transaction" class="form-label fw-medium text-secondary">سرفصل</label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-bookmark text-muted"></i></span>
                <input type="text"
                       id="remit_transaction"
                       name="transaction"
                       class="form-control"
                       value="<?= htmlspecialchars((string)($remitanceOut['transaction'] ?? '')) ?>"
                       placeholder="مثال: خرید مصالح، تأسیسات">
            </div>
        </div>

        <!-- زیرفصل -->
        <div class="col-md-6">
            <label for="remit_subchapter" class="form-label fw-medium text-secondary">زیرفصل</label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-diagram-3 text-muted"></i></span>
                <input type="text"
                       id="remit_subchapter"
                       name="subchapter"
                       class="form-control"
                       value="<?= htmlspecialchars((string)($remitanceOut['subchapter'] ?? '')) ?>"
                       placeholder="مثال: میلگرد، بتن">
            </div>
        </div>

        <!-- بانک/حساب -->
        <div class="col-md-6">
            <label for="remit_bank_hesab" class="form-label fw-medium text-secondary">بانک / حساب</label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-bank text-muted"></i></span>
                <input type="text"
                       id="remit_bank_hesab"
                       name="bank/hesab"
                       class="form-control"
                       value="<?= htmlspecialchars((string)($remitanceOut['bank/hesab'] ?? '')) ?>"
                       placeholder="مثال: حساب جاری بانک ملت">
            </div>
        </div>

        <!-- ورودی/خروجی -->
        <div class="col-md-6">
            <label for="remit_voroodi_khoroujy" class="form-label fw-medium text-secondary">ورودی / خروجی</label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-arrow-left-right text-muted"></i></span>
                <input type="text"
                       id="remit_voroodi_khoroujy"
                       name="voroodi/khoroujy"
                       value="<?= htmlspecialchars((string)($remitanceOut['voroodi/khoroujy'] ?? '')) ?>"
                       class="form-control"
                       placeholder="مثال: خروجی">
            </div>
        </div>

        <!-- توضیحات -->
        <div class="col-12">
            <label for="remit_description" class="form-label fw-medium text-secondary">توضیحات</label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="bi bi-card-text text-muted"></i></span>
                <textarea id="remit_description"
                          name="description"
                          class="form-control"
                          rows="2"
                          placeholder="توضیحات تکمیلی حواله..."><?= htmlspecialchars((string)($remitanceOut['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
        </div>


    </div>

    <!-- فوتر با دکمه‌های اکشن -->
    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
        <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">انصراف</button>
        <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-check-lg me-1"></i> ذخیره تغییرات
        </button>
    </div>
</form>

<script>
    // فرمت عدد با جداکننده هزارگان و حفظ حداکثر ۳ رقم اعشار
    function formatHarvestValue(input) {
        // ۱) تبدیل ارقام فارسی/عربی به انگلیسی
        let val = input.value.replace(/[۰-۹]/g, d => String('۰۱۲۳۴۵۶۷۸۹'.indexOf(d)))
            .replace(/[٠-٩]/g, d => String('٠١٢٣٤٥٦٧٨٩'.indexOf(d)));

        // ۲) حذف هر کاراکتری غیر از رقم و نقطه
        val = val.replace(/[^0-9.]/g, '');

        // ۳) فقط اجازه یک نقطه
        const firstDot = val.indexOf('.');
        if (firstDot !== -1) {
            val = val.slice(0, firstDot + 1) + val.slice(firstDot + 1).replace(/\./g, '');
        }

        const parts = val.split('.');
        const intPart = parts[0];
        let decPart = parts[1];

        // ۴) سه‌رقم‌سه‌رقم کردن بخش صحیح
        const formattedInt = intPart ? intPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',') : '';

        // ۵) حفظ بخش اعشار تا ۳ رقم (بدون حذف صفرهای سمت راست کاربر هنگام تایپ)
        if (decPart !== undefined) {
            decPart = decPart.substring(0, 3);
            input.value = (formattedInt || '0') + '.' + decPart;
        } else {
            input.value = formattedInt;
        }
    }

    // رویداد تایپ کاربر
    document.addEventListener('input', function (e) {
        if (e.target && e.target.id === 'remit_harvest') {
            const input = e.target;
            const cursorAtEnd = input.selectionStart === input.value.length;
            formatHarvestValue(input);
            if (cursorAtEnd) {
                input.setSelectionRange(input.value.length, input.value.length);
            }
        }
    });
</script>

