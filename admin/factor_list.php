<?php
// اتصال به دیتابیس (اگر قبلاً در فایل اصلی متصل نشده‌اید)
$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
$pdo = new PDO("mysql:host=localhost;dbname=maskanhb_maskansazan;charset=utf8mb4",
        "root",
        '',
        [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
// ۱. آماده‌سازی کوئری با علامت مساوی قبل از نام پارامتر
$stmt = $pdo->prepare("SELECT * FROM `expenses` WHERE project_id = :project_id ORDER BY id DESC");

// ۲. اجرا همراه با ارسال مقدار پارامتر
$stmt->execute([
        ':project_id' => $project_id
]);

// ۳. دریافت نتایج
$expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

function toShamsi($dateStr) {
    if (empty($dateStr) || $dateStr === '0000-00-00') {
        return '-';
    }

    // اگر اکستنشن intl فعال بود
    if (class_exists('IntlDateFormatter')) {
        $timestamp = strtotime($dateStr);
        $formatter = new IntlDateFormatter(
                'fa_IR@calendar=persian',
                IntlDateFormatter::FULL,
                IntlDateFormatter::NONE,
                'Asia/Tehran',
                IntlDateFormatter::TRADITIONAL,
                'yyyy/MM/dd'
        );
        return $formatter->format($timestamp);
    }

    // راهکار جایگزین در صورت غیرفعال بودن intl (جلوگیری از توقف برنامه)
    return $dateStr;
}


ob_start()
?>
<style>
    .table-responsive {
        overflow: visible !important;
    }

</style>
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
                    <th>شماره فاکتور</th>
                    <th>تاریخ فاکتور</th>
                    <th>نوع کالا</th>
                    <th>تعداد</th>
                    <th>قیمت</th>
                    <th>مبلغ فاکتور (ریال)</th>
                    <th>نام فروشنده</th>
                    <th>نام فروشگاه</th>
                    <th>نحوه پرداخت</th>


                    <th>تصویر فاکتور</th>
                    <th>پرداخت کننده</th>

                    <th>عملیات</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($expenses as $i => $item): ?>
                    <tr>
                        <td><?= $i+=1 ?></td>
                        <td>
                            <?= $item['invoice_number'] ?>
                        </td>
                        <td><?= toShamsi($item['invoice_date']) ?></td>
                        <td><?= $item['item_type'] ?></td>
                        <td><?= number_format((float)($item['count'] ?? 0)) ?></td>
                        <td><?= number_format((float)($item['gheymat'] ?? 0)) ?></td>
                        <td class="fw-bold text-dark"><?= number_format((float)($item['amount'] ?? 0)) ?></td>







                        <td><?= $item['vendor_name'] ?></td>
                        <td><?= $item['shop_name'] ?></td>
                        <td>
                            <?php
                            $methods = [
                                    'نقدی'  => 'نقدی',
                                    'چک'    => 'چک',
                                    'حواله' => 'حواله',
                            ];
                            echo htmlspecialchars($methods[$item['payment_method']] ?? 'نامشخص');
                            ?>
                        </td>
                        <td>
                            <?php if (!empty($item['invoice_image'])): ?>
                                <?php
                                $fileName = basename((string) $item['invoice_image']);

                                // مسیر فیزیکی روی دیسک (بررسی ریشه پروژه و سپس داخل admin)
                                $diskPathRoot = dirname(__DIR__) . '/uploads/invoices/' . $fileName;
                                $diskPathAdmin = __DIR__ . '/uploads/invoices/' . $fileName;

                                if (file_exists($diskPathRoot)) {
                                    $filePath = '../uploads/invoices/' . rawurlencode($fileName);
                                } elseif (file_exists($diskPathAdmin)) {
                                    $filePath = 'uploads/invoices/' . rawurlencode($fileName);
                                } else {
                                    $filePath = null; // فایل اصلاً روی دیسک موجود نیست
                                }

                                $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                                ?>

                                <?php if ($filePath && $ext === 'pdf'): ?>
                                    <a href="<?= htmlspecialchars($filePath, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener" class="btn btn-sm btn-outline-danger border-0" title="مشاهده فایل PDF">
                                        <i class="bi bi-file-earmark-pdf fs-5"></i>
                                    </a>
                                <?php elseif ($filePath && in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)): ?>
                                    <button type="button" class="btn btn-sm btn-outline-primary border-0" title="مشاهده تصویر فاکتور" onclick="openImageModal(<?= htmlspecialchars(json_encode($filePath, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>)">
                                        <i class="bi bi-image fs-5"></i>
                                    </button>
                                <?php else: ?>
                                    <span class="text-danger small" title="فایل روی سرور یافت نشد"><i class="bi bi-exclamation-triangle"></i> فاقد فایل</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted small"><i class="bi bi-dash"></i></span>
                            <?php endif; ?>

                        </td>


                        <td>
                            <?= $item['pardakhtkonnandeh'] ?>
                        </td>


                        <td class="text-center">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light border-0 rounded-circle p-1"
                                        type="button"
                                        data-bs-toggle="dropdown"
                                        data-bs-boundary="window"
                                        aria-expanded="false"
                                        style="width: 32px; height: 32px;">
                                    <i class="bi bi-three-dots-vertical text-secondary"></i>
                                </button>

                                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 small">
                                    <button type="button"
                                            class="dropdown-item py-2 btn-open-invoice"
                                            data-id="<?= (int)($item['id'] ?? $item['expense_id'] ?? $item['factor_id'] ?? 0) ?>"
                                            data-project-id="<?= (int)($item['project_id'] ?? $project_id ?? 0) ?>">
                                        <i class="bi bi-receipt text-primary me-2"></i> مشاهده فاکتور (کد: <?= $item['id'] ?? 'خالی' ?>)
                                    </button>



                                    <!-- ویرایش -->
                                    <li>
                                        <a class="dropdown-item py-2 btn-edit-invoice"
                                           href="javascript:void(0)"
                                           data-id="<?= (int)$item['id'] ?>"
                                           data-project-id="<?= (int)($item['project_id'] ?? ($project_id ?? 0)) ?>">
                                            <i class="bi bi-pencil-square text-warning me-2"></i> ویرایش
                                        </a>

                                    </li>

                                    <li><hr class="dropdown-divider my-1"></li>

                                    <!-- باز کردن مودال حذف -->
                                    <li>
                                        <button type="button"
                                                class="dropdown-item py-2 text-danger"
                                                data-bs-toggle="modal"
                                                data-bs-target="#deleteInvoiceModal"
                                                data-invoice-id="<?= (int)$item['id'] ?>"
                                                data-project-id="<?= (int)$item['project_id'] ?>"
                                                data-invoice-info="شماره <?= htmlspecialchars($item['invoice_number']) ?> (<?= htmlspecialchars($item['vendor_name'] ?: $item['shop_name']) ?>)">
                                            <i class="bi bi-trash3 text-danger me-2"></i> حذف فاکتور
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </td>


                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- مودال تصویر فاکتور (اگر در فایل اصلی نیست اینجا بگذارید) -->
<!-- مودال نمایش تصویر فاکتور -->
<div class="modal fade" id="imagePreviewModal" tabindex="-1" aria-labelledby="imagePreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow border-0">
            <div class="modal-header py-2 bg-light">
                <h6 class="modal-title fw-bold" id="imagePreviewModalLabel">
                    <i class="bi bi-image me-1 text-primary"></i> مشاهده تصویر فاکتور
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-3">
                <img id="modalPreviewImg" src="" alt="تصویر فاکتور" class="img-fluid rounded shadow-sm" style="max-height: 75vh; object-fit: contain;">
            </div>
            <div class="modal-footer py-2 bg-light d-flex justify-content-between">
                <a id="modalDownloadBtn" href="" target="_blank" download class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-download me-1"></i> دانلود / باز کردن در تب جدید
                </a>
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">بستن</button>
            </div>
        </div>
    </div>
</div>
<!-- مودال تایید حذف فاکتور -->
<div class="modal fade" id="deleteInvoiceModal" tabindex="-1" aria-labelledby="deleteInvoiceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
            </div>

            <form action="delete_invoice.php" method="POST">
                <div class="modal-body text-center pt-0 px-4">
                    <!-- آیکون هشدار متحرک/بزرگ -->
                    <div class="mb-3 text-danger">
                        <i class="bi bi-exclamation-triangle-fill" style="font-size: 3.5rem;"></i>
                    </div>

                    <h5 class="modal-title fw-bold text-dark mb-2" id="deleteInvoiceModalLabel">حذف فاکتور هزینه</h5>

                    <p class="text-muted mb-3">
                        آیا از حذف فاکتور <strong id="modalInvoiceText" class="text-danger"></strong> اطمینان دارید؟
                    </p>

                    <div class="alert alert-warning py-2 px-3 small text-start border-0 bg-warning-subtle">
                        <i class="bi bi-info-circle-fill text-warning me-1"></i>
                        <span>با حذف این فاکتور، تمام اطلاعات و تصویر ضمیمه آن به صورت کامل حذف شده و قابل بازیابی نخواهد بود.</span>
                    </div>

                    <!-- فیلدهای مخفی برای ارسال به بک‌اند -->
                    <input type="hidden" name="id" id="modalInvoiceId" value="">
                    <input type="hidden" name="project_id" id="modalProjectId" value="">
                </div>

                <div class="modal-footer border-top-0 justify-content-center gap-2 pb-4">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">انصراف</button>
                    <button type="submit" id="btnConfirmDelete" class="btn btn-danger px-4 shadow-sm">
                        <span id="btnDeleteSpinner" class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true"></span>
                        <i id="btnDeleteIcon" class="bi bi-trash3 me-1"></i>
                        <span id="btnDeleteText">بله، حذف شود</span>
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>
<!-- مودال نمایش فاکتور -->
<div class="modal fade" id="viewInvoiceModal" tabindex="-1" aria-labelledby="viewInvoiceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title fs-6 fw-bold" id="viewInvoiceModalLabel">
                    <i class="bi bi-receipt me-1 text-primary"></i> جزئیات فاکتور
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
            </div>

            <div class="modal-body p-4" id="viewInvoiceModalBody">
                <!-- اسپینر پیش‌فرض در زمان لودینگ -->
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">در حال بارگذاری...</span>
                    </div>
                    <p class="text-muted mt-2 small">در حال دریافت اطلاعات فاکتور...</p>
                </div>
            </div>

            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">بستن</button>
            </div>
        </div>
    </div>
</div>
<!-- مودال ویرایش فاکتور -->
<div class="modal fade" id="editInvoiceModal" tabindex="-1" aria-labelledby="editInvoiceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-light py-2">
                <h6 class="modal-title fw-bold" id="editInvoiceModalLabel">
                    <i class="bi bi-pencil-square text-primary me-2"></i> ویرایش اطلاعات فاکتور
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="بستن"></button>
            </div>
            <div class="modal-body p-4" id="editInvoiceModalBody">
                <!-- محتوای فرم از طریق AJAX بارگذاری می‌شود -->
            </div>
        </div>
    </div>
</div>

<script>
    // فرمت سه‌رقم سه‌رقم مبلغ
    function formatCurrency(input) {
        let val = input.value.replace(/[^0-9]/g, '');
        input.value = val ? parseInt(val, 10).toLocaleString('en-US') : '';
    }

    document.addEventListener('DOMContentLoaded', function () {
        const modalEl = document.getElementById('editInvoiceModal');
        const modalBody = document.getElementById('editInvoiceModalBody');
        if (!modalEl || !modalBody) return;

        const editModal = bootstrap.Modal.getOrCreateInstance(modalEl);

        // ۱. کلیک دکمه ویرایش و بارگذاری فرم از edit_invoice_modal.php
        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.btn-edit-invoice');
            if (!btn) return;

            e.preventDefault();

            const invoiceId = btn.dataset.id || btn.getAttribute('data-id');
            if (!invoiceId) {
                alert('شناسه فاکتور نامعتبر است.');
                return;
            }

            modalBody.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status"></div>
                <div class="text-muted small mt-2">در حال دریافت اطلاعات فاکتور...</div>
            </div>
        `;
            editModal.show();

            fetch(`/maskansazan/admin/edit_invoice.php?id=${encodeURIComponent(invoiceId)}`)
                .then(res => {
                    if (!res.ok) throw new Error('خطا در دریافت اطلاعات فاکتور (' + res.status + ')');
                    return res.text();
                })
                .then(html => {
                    modalBody.innerHTML = html;
                })
                .catch(err => {
                    modalBody.innerHTML = `<div class="alert alert-danger mb-0">${err.message}</div>`;
                });
        });

        // ۲. ارسال فرم ویرایش با AJAX و ذخیره فاکتور
        // استفاده از Event Delegation تا در صورت لود فرم با AJAX هم کار کند
        $(document).on('submit', '#ajaxEditInvoiceForm', function (e) {
            e.preventDefault();

            const form = this;
            const submitBtn = form.querySelector('#btnSaveInvoice') || form.querySelector('button[type="submit"]');
            const alertBox = form.querySelector('#editInvoiceAlert') || document.getElementById('editInvoiceAlert');

            // غیرفعال کردن دکمه برای جلوگیری از چند بار کلیک
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> در حال ذخیره...';
            }

            if (alertBox) {
                alertBox.className = 'd-none alert mb-3';
                alertBox.textContent = '';
            }

            // ساخت داده‌های فرم با پشتیبانی از آپلود فایل
            const formData = new FormData(form);

            fetch('edit_invoice.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(async response => {
                    const text = await response.text();
                    try {
                        return JSON.parse(text);
                    } catch (err) {
                        // اگر خروجی سرور به جای JSON خطای متنی/HTML بود
                        console.error("پاسخ نامعتبر از سرور:", text);
                        throw new Error(text || 'خطای ناشناخته از سرور');
                    }
                })
                .then(data => {
                    if (data.success) {
                        if (alertBox) {
                            alertBox.className = 'alert alert-success mb-3';
                            alertBox.textContent = data.message || 'فاکتور با موفقیت ویرایش شد.';
                        }

                        // بستن مودال و رفرش بعد از ۱ ثانیه
                        setTimeout(() => {
                            const modalEl = document.getElementById('editInvoiceModal');
                            if (modalEl) {
                                const modal = bootstrap.Modal.getInstance(modalEl);
                                if (modal) modal.hide();
                            }
                            location.reload(); // یا به‌روزرسانی جدول
                        }, 1000);
                    } else {
                        if (alertBox) {
                            alertBox.className = 'alert alert-danger mb-3';
                            alertBox.textContent = data.message || 'خطا در ثبت اطلاعات.';
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    if (alertBox) {
                        alertBox.className = 'alert alert-danger mb-3';
                        alertBox.textContent = 'خطا در برقراری ارتباط با سرور.';
                    }
                })
                .finally(() => {
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="bi bi-check2 me-1"></i> ذخیره تغییرات';
                    }
                });
        });



    });



    function openImageModal(imageSrc) {
        const modalImg = document.getElementById('modalPreviewImg');
        const downloadBtn = document.getElementById('modalDownloadBtn');

        // ست کردن آدرس عکس
        modalImg.src = imageSrc;
        if (downloadBtn) {
            downloadBtn.href = imageSrc;
        }

        // نمایش مودال با استفاده از بوت‌استرپ ۵
        const modalElement = document.getElementById('imagePreviewModal');
        const modalInstance = bootstrap.Modal.getOrCreateInstance(modalElement);
        modalInstance.show();
    }

    document.addEventListener('DOMContentLoaded', function () {
        const invoiceModalEl = document.getElementById('viewInvoiceModal');
        const invoiceModalBody = document.getElementById('viewInvoiceModalBody');
        if (!invoiceModalEl || !invoiceModalBody) return;

        const invoiceModal = bootstrap.Modal.getOrCreateInstance(invoiceModalEl);

        // قالب لودر پیش‌فرض
        const defaultLoaderHtml = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">در حال بارگذاری...</span>
            </div>
            <p class="text-muted mt-2 small">در حال دریافت اطلاعات فاکتور...</p>
        </div>
    `;

        document.addEventListener('click', function (e) {
            // پیدا کردن دقیق المنتی که کلاس btn-open-invoice دارد
            const btn = e.target.closest('.btn-open-invoice');
            if (!btn) return;

            e.preventDefault();

            // خواندن شناسه با متد استاندارد dataset
            const rawId = btn.dataset.id || btn.getAttribute('data-id');
            const rawProjectId = btn.dataset.projectId || btn.getAttribute('data-project-id') || 0;

            console.log("Invoice ID clicked:", rawId); // در Console مرورگر نمایش داده می‌شود

            const invoiceId = parseInt(rawId, 10);
            const projectId = parseInt(rawProjectId, 10) || 0;

            if (isNaN(invoiceId) || invoiceId <= 0) {
                invoiceModalBody.innerHTML = `
                <div class="alert alert-warning d-flex align-items-center mb-0">
                    <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                    <div>شناسه فاکتور نامعتبر است (مقدار دریافتی: ${rawId || 'خالی'}).</div>
                </div>
            `;
                invoiceModal.show();
                return;
            }

            invoiceModalBody.innerHTML = defaultLoaderHtml;
            invoiceModal.show();

            const url = `view_invoice.php?id=${invoiceId}&project_id=${projectId}&ajax=1`;

            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('کد خطای سرور: ' + response.status);
                    }
                    return response.text();
                })
                .then(html => {
                    invoiceModalBody.innerHTML = html;
                })
                .catch(err => {
                    invoiceModalBody.innerHTML = `
                <div class="alert alert-danger d-flex align-items-center mb-0">
                    <i class="bi bi-x-circle-fill me-2 fs-5"></i>
                    <div>خطا در دریافت اطلاعات: ${err.message}</div>
                </div>
            `;
                });
        });

        invoiceModalEl.addEventListener('hidden.bs.modal', function () {
            invoiceModalBody.innerHTML = defaultLoaderHtml;
        });
    });


    document.addEventListener("DOMContentLoaded", function () {
        const deleteModal = document.getElementById('deleteInvoiceModal');

        if (deleteModal) {
            const deleteForm = deleteModal.querySelector('form');

            // ۱. زمان باز شدن مودال: فقط جایگذاری مقادیر و ریست دکمه
            deleteModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                if (!button) return;

                // دریافت دیتا از دکمه
                const invoiceId   = button.getAttribute('data-invoice-id');
                const projectId   = button.getAttribute('data-project-id');
                const invoiceInfo = button.getAttribute('data-invoice-info');

                // پر کردن فیلدها
                const inputInvoiceId = document.getElementById('modalInvoiceId');
                const inputProjectId = document.getElementById('modalProjectId');
                const textInvoice    = document.getElementById('modalInvoiceText');

                if (inputInvoiceId) inputInvoiceId.value = invoiceId || '';
                if (inputProjectId) inputProjectId.value = projectId || '';
                if (textInvoice)    textInvoice.textContent = invoiceInfo ? `«${invoiceInfo}»` : '';

                // ریست کردن وضعیت دکمه در صورت باز شدن مجدد مودال
                const btn = document.getElementById('btnConfirmDelete') || document.getElementById('btnSubmitInvoice');
                const spinner = document.getElementById('btnDeleteSpinner') || document.getElementById('btnSpinner');
                const icon = document.getElementById('btnDeleteIcon');
                const text = document.getElementById('btnDeleteText');

                if (btn) {
                    btn.disabled = false;
                    btn.style.pointerEvents = 'auto';
                }
                if (spinner) spinner.classList.add('d-none');
                if (icon) icon.classList.remove('d-none');
                if (text) text.textContent = 'بله، حذف شود';
            });

            // ۲. زمان سابمیت فرم حذف: فعال‌سازی اسپینر و دیسبل کردن دکمه
            if (deleteForm) {
                deleteForm.addEventListener('submit', function () {
                    const btn = document.getElementById('btnConfirmDelete') || document.getElementById('btnSubmitInvoice');
                    const spinner = document.getElementById('btnDeleteSpinner') || document.getElementById('btnSpinner');
                    const icon = document.getElementById('btnDeleteIcon');
                    const text = document.getElementById('btnDeleteText');

                    if (btn) {
                        if (spinner) spinner.classList.remove('d-none');
                        if (icon) icon.classList.add('d-none');
                        if (text) text.textContent = 'در حال حذف...';

                        // تاخیر کوتاه برای جلوگیری از لغو ارسال POST مرورگر
                        setTimeout(function () {
                            btn.disabled = true;
                            btn.style.pointerEvents = 'none';
                        }, 10);
                    }
                });
            }
        }
    });

</script>

