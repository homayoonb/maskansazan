<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="UTF-8">
<title>ثبت حواله</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/css/persian-datepicker.min.css">

<style>
.card{border-radius:14px}
@media(max-width:576px){
    .card-header{text-align:center}
    .form-control,.form-select{font-size:.9rem}
    .invalid-feedback{font-size:.8rem}
}
@media(min-width:768px){
    .card{max-width:720px;margin:auto}
}
</style>
</head>

<body class="bg-light">

<div class="container my-4">
<a href="dashboard.php" class="btn btn-outline-secondary mb-3 w-100 w-md-auto">
    <i class="bi bi-arrow-right"></i> بازگشت
</a>

<div class="card shadow-sm">
<div class="card-header fw-bold">ثبت حواله بانکی</div>
<div class="card-body">

<form id="transferForm" enctype="multipart/form-data">

<!-- تاریخ -->
<div class="mb-3">
<label class="form-label">تاریخ حواله</label>
<input type="text" id="transfer_date_view" class="form-control">
<input type="hidden" name="transfer_date" id="transfer_date">

</div>

<!-- مبلغ -->
<div class="mb-3">
<label class="form-label">مبلغ (ریال)</label>
<input type="text" name="amount" class="form-control">
</div>

<!-- بانک -->
<div class="mb-3">
<label class="form-label">بانک</label>
<div class="input-group">
<span class="input-group-text"><i id="bankIcon" class="bi bi-bank"></i></span>
<select name="bank_name" id="bankSelect" class="form-select">
<option value="">انتخاب بانک</option>
<option value="melli" data-icon="bi-bank">ملی</option>
<option value="mellat" data-icon="bi-building">ملت</option>
<option value="saderat" data-icon="bi-columns">صادرات</option>
<option value="tejarat" data-icon="bi-cash-stack">تجارت</option>
<option value="sepah" data-icon="bi-shield-lock">سپه</option>
<option value="maskan" data-icon="bi-house-door">مسکن</option>
</select>
</div>
</div>

<!-- حساب -->
<div class="mb-3">
<label class="form-label">حساب مقصد</label>
<input type="text" name="destination_account" class="form-control">
</div>

<!-- تصویر -->
<div class="mb-3">
<label class="form-label">تصویر حواله</label>
<input type="file" name="receipt_image" class="form-control" accept="image/*">
<img id="preview" class="img-fluid mt-2 rounded d-none" style="max-height:180px">
</div>

<!-- توضیح -->
<div class="mb-3">
<label class="form-label">توضیحات</label>
<textarea name="description" class="form-control"></textarea>
</div>

<button id="submitBtn" class="btn btn-success w-100 d-flex justify-content-center align-items-center gap-2">
    <span id="btnText">ثبت حواله</span>
    <span id="spinner" class="spinner-border spinner-border-sm d-none"></span>
</button>

</form>
</div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/persian-date@1.1.0/dist/persian-date.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/js/persian-datepicker.min.js"></script>

<script>
/* Elements */
const form = document.getElementById('transferForm');
const submitBtn = document.getElementById('submitBtn');
const btnText = document.getElementById('btnText');
const spinner = document.getElementById('spinner');
const bankSelect = document.getElementById('bankSelect');
const bankIcon = document.getElementById('bankIcon');
const preview = document.getElementById('preview');

$(function () {

    $('#transfer_date_view').persianDatepicker({
        format: 'YYYY/MM/DD',        // نمایش
        autoClose: true,
        initialValue: false,

        onSelect: function (unixDate) {
            const g = new persianDate(unixDate).toCalendar('gregorian');
            const y = g.year();
            const m = String(g.month()).padStart(2,'0');
            const d = String(g.date()).padStart(2,'0');

            // مقدار واقعی که به PHP میره
            $('#transfer_date').val(`${y}-${m}-${d}`);
        }
    });

});

// بانک → آیکن
bankSelect.onchange = e =>
    bankIcon.className = 'bi ' + (e.target.selectedOptions[0].dataset.icon || 'bi-bank');

// پیش‌نمایش تصویر
document.querySelector('[name=receipt_image]').onchange = e=>{
    preview.src = URL.createObjectURL(e.target.files[0]);
    preview.classList.remove('d-none');
};

// خطاها
function setError(el,msg){
    el.classList.add('is-invalid');
    el.nextElementSibling.textContent = msg;
}
function clearErrors(){
    document.querySelectorAll('.is-invalid').forEach(e=>e.classList.remove('is-invalid'));
}

// ارسال
transferForm.onsubmit = e=>{
e.preventDefault();
clearErrors();

submitBtn.disabled=true;
btnText.textContent='در حال ثبت...';
spinner.classList.remove('d-none');

fetch('transfer_store.php',{method:'POST',body:new FormData(transferForm)})
.then(r=>r.json())
.then(d=>{
submitBtn.disabled=false;
btnText.textContent='ثبت حواله';
spinner.classList.add('d-none');

if(!d.status && d.errors){
Object.entries(d.errors).forEach(([k,v])=>{
const f=document.querySelector(`[name="${k}"]`);
if(f) setError(f,v);
});
return;
}

alert(d.message);
transferForm.reset();
preview.classList.add('d-none');
});
};
</script>

</body>
</html>
