<?php
if (session_status() === PHP_SESSION_NONE) {
    // تنظیم مسیر موقت سشن‌ها در زمپ
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        session_save_path("C:/xampp/tmp");
    }
    ini_set('session.gc_maxlifetime', 1440);
    session_start();
}

// تنظیم تب پیش‌فرض (ورود یا ثبت‌نام)
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'login';
if (!in_array($active_tab, ['login', 'register'])) {
    $active_tab = 'login';
}

$error = $_SESSION['login_error'] ?? '';
$success = $_SESSION['register_success'] ?? '';
unset($_SESSION['login_error']);
unset($_SESSION['register_success']);

// دریافت لیست پروژه‌ها از کلاس دیتابیس
if (!function_exists('getProjectsList')) {
    function getProjectsList($dbConnection) {
        // توجه: این تابع پروژه‌ها را برای منوی کشویی ثبت‌نام واکشی می‌کند.
        // اگر نام جدول پروژه‌های شما تغییر کرده است، مطمئن شوید متد داخلی کلاس دیتابیس اصلاح شده باشد.
        require_once __DIR__ . '/config/Database.php';
        return Database::getProjectsList($dbConnection);
    }
}

// دریافت لیست پروژه‌ها جهت نمایش در فرم ثبت‌نام
$projects = [];
try {
    include_once __DIR__ . '/config/Database.php';
    $database = new Database();
    $db = $database->connect();

    if ($db) {
        $projects = getProjectsList($db);
    } else {
        $error = "خطا در اتصال به دیتابیس. لطفاً تنظیمات را بررسی کنید.";
    }
} catch (Exception $e) {
    // نمایش پیام خطای ساده‌تر و امن‌تر به کاربر نهایی
    $error = "خطا در سیستم: " . htmlspecialchars($e->getMessage());
}

// تشخیص دستگاه برای بهینه‌سازی رابط کاربری
$isMobile = preg_match("/(android|iphone|ipod|blackberry|windows phone)/i", $_SERVER['HTTP_USER_AGENT'] ?? '');

$title = 'مدیریت اعضا (ورود / ثبت‌نام)';
ob_start(); // شروع ذخیره محتوا برای لود در قالب اصلی
?>

<link href="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/css/persian-datepicker.min.css" rel="stylesheet">
<!-- jQuery -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

<!-- Persian Datepicker -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/css/persian-datepicker.min.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">

<style>
    .login-wrapper {
        display: flex;
        justify-content: center;
        align-items: flex-start;
        padding: 20px;
        width: 100%;
    }

    .login-card {
        background: #fff;
        border-radius: 15px;
        box-shadow: 0 0 20px rgba(0,0,0,0.1);
        width: 100%;
        max-width: 800px; /* کنترل عرض فرم */
        overflow: hidden;
        border: 1px solid #e1e1e1;
    }

    .login-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px;
        text-align: center;
    }

    /* استایل تب‌ها */
    .tabs-container {
        display: flex;
        border-bottom: 1px solid #ddd;
        background: #f8f9fa;
    }

    .tab-button {
        flex: 1;
        padding: 15px;
        border: none;
        background: none;
        cursor: pointer;
        font-weight: bold;
        transition: 0.3s;
    }

    .tab-button.active {
        background: #fff;
        color: #764ba2;
        border-bottom: 3px solid #764ba2;
    }

    .tab-content {
        display: none;
        padding: 30px;
    }

    .tab-content.active {
        display: block;
    }
    @keyframes fadeInTab {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }


    /* استایل فرم‌ها */
    .form-group {
        margin-bottom: 20px;
    }

    .form-label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #2d3748;
        font-size: 14px;
        font-family: 'Samim', sans-serif;
    }

    .form-control {
        width: 100%;
        padding: 14px 16px;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        font-size: 16px;
        transition: all 0.3s ease;
        background: #f8fafc;
        font-family: 'Vazirmatn', sans-serif;
        font-weight: 500;
    }

    .form-control:focus {
        border-color: #667eea;
        background: white;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        outline: none;
    }

    .required::after {
        content: " *";
        color: #e74c3c;
    }

    /* استایل دکمه‌ها */
    .btn-login, .submit-btn-register {
        width: 100%;
        background: linear-gradient(135deg, #27ae60, #219653);
        color: white;
        border: none;
        padding: 16px;
        border-radius: 12px;
        cursor: pointer;
        font-size: 16px;
        font-weight: bold;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        font-family: 'Samim', sans-serif;
        margin-top: 10px;
    }

    .btn-login {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .btn-login:hover, .submit-btn-register:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(39, 174, 96, 0.3);
    }

    .btn-login:hover {
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
    }

    /* استایل بخش‌ها */
    .section {
        background: #f8f9fa;
        padding: 25px;
        border-radius: 10px;
        margin-bottom: 30px;
        border-right: 5px solid #3498db;
    }

    .section h3 {
        color: #2c3e50;
        margin-top: 0;
        margin-bottom: 20px;
        font-size: 1.3rem;
    }

    /* استایل بخش چک‌ها */
    .check-section {
        background: #f0f7ff;
        border: 2px solid #cce5ff;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
    }

    .check-section h4 {
        color: #0066cc;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 2px dashed #b3d9ff;
    }

    .check-row {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 15px;
    }

    .check-field {
        flex: 1;
        min-width: 200px;
    }

    /* استایل برای تاریخ‌های چک */
    .date-selector {
        display: flex;
        gap: 10px;
        align-items: center;
        margin-top: 10px;
    }

    .date-selector select {
        flex: 1;
        padding: 10px;
        border: 2px solid #e2e8f0;
        border-radius: 8px;
    }

    .date-converter-btn {
        padding: 10px 15px;
        background: #4CAF50;
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 12px;
    }

    .date-converter-btn:hover {
        background: #45a049;
    }

    .gregorian-date-display {
        background: #f0f0f0;
        padding: 8px 12px;
        border-radius: 6px;
        font-size: 14px;
        margin-top: 8px;
        color: #333;
    }

    @media (max-width: 768px) {
        .check-field {
            min-width: 100%;
        }

        .date-selector {
            flex-direction: column;
        }
    }

    /* استایل پیام‌ها */
    .text-danger, .text-success {
        padding: 12px;
        border-radius: 8px;
        margin-bottom: 20px;
        text-align: center;
        font-weight: bold;
    }

    .text-danger {
        background: #fed7d7;
        color: #e53e3e;
        border: 1px solid #feb2b2;
    }

    .text-success {
        background: #c6f6d5;
        color: #38a169;
        border: 1px solid #9ae6b4;
    }

    .error {
        color: #e74c3c;
        font-size: 0.85rem;
        margin-top: 5px;
        display: none;
    }

    .success {
        color: #27ae60;
        font-size: 0.85rem;
        margin-top: 5px;
        display: none;
    }

    .loading {
        color: #3498db;
        font-size: 0.85rem;
        margin-top: 5px;
        display: none;
    }

    .note {
        color: #7f8c8d;
        font-size: 0.85rem;
        margin-top: 5px;
        font-style: italic;
    }

    /* استایل select */
    .form-select {
        width: 100%;
        padding: 14px 45px 14px 16px;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        font-size: 16px;
        transition: all 0.3s ease;
        background: #f8fafc;
        cursor: pointer;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='%23667eea' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: left 16px center;
        background-size: 16px;
        font-family: 'Vazirmatn', sans-serif;
        font-weight: 500;
    }

    /* استایل checkbox */
    .checkbox-container {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 20px 0;
        padding: 15px;
        background: #f1f8ff;
        border-radius: 8px;
    }

    input[type="checkbox"] {
        width: 20px;
        height: 20px;
        cursor: pointer;
    }

    /* استایل‌های مربوط به تاریخ تولد */
    .date-container {
        background: white;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 15px;
    }

    .date-row {
        display: flex;
        gap: 15px;
        margin-bottom: 15px;
        flex-wrap: wrap;
    }

    .date-field {
        flex: 1;
        min-width: 150px;
    }

    .date-preview {
        background: #f1f8ff;
        border: 2px dashed #3498db;
        border-radius: 12px;
        padding: 15px;
        text-align: center;
        font-size: 1.1rem;
        color: #2c3e50;
        margin-top: 10px;
        font-weight: bold;
    }

    .date-preview .label {
        font-size: 0.9rem;
        color: #7f8c8d;
        margin-bottom: 5px;
    }

    .help-text {
        background: #e8f4fc;
        border-right: 4px solid #3498db;
        padding: 10px 15px;
        margin: 10px 0;
        border-radius: 8px;
        font-size: 0.9rem;
        color: #2c3e50;
    }

    /* انیمیشن‌ها */
    @keyframes fadeInMobile {
        from {
            opacity: 0;
            transform: translateY(20px) scale(0.98);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    /* استایل دسکتاپ */
    @media (min-width: 768px) {
        .login-card {
            max-width: 850px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.15);
        }

        .login-header {
            padding: 40px 35px;
        }

        .login-header h1 {
            font-size: 28px;
        }

        .login-header p {
            font-size: 16px;
            margin-top: 12px;
        }

        .tab-content {
            padding: 35px 30px;
        }

        .form-control {
            padding: 16px 20px;
            font-size: 15px;
        }

        .btn-login, .submit-btn-register {
            padding: 18px;
            font-size: 17px;
            margin-top: 15px;
        }
    }

    /* استایل موبایل */
    @media (max-width: 768px) {
        .back-btn {
            top: 10px;
            right: 10px;
            padding: 8px 12px;
            font-size: 12px;
        }

        .date-row {
            flex-direction: column;
        }

        .date-field {
            min-width: 100%;
        }
    }
    .date-input-container {
        position: relative;
    }
    .date-input-container .datepicker-trigger {
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: #6c757d;
    }
</style>

<div class="login-card">
    <div class="login-header">
        <h1> مدیریت اعضا</h1>
        <p>ورود اعضای موجود | ثبت‌نام اعضای جدید</p>
    </div>

    <!-- تب‌ها -->
    <div class="tabs-container">
        <button class="tab-button <?php echo $active_tab == 'login' ? 'active' : ''; ?>"
                data-tab="login">
            🔐 ورود اعضا
        </button>
        <button class="tab-button <?php echo $active_tab == 'register' ? 'active' : ''; ?>"
                data-tab="register">
            📝 ثبت‌نام جدید
        </button>
    </div>

    <!-- محتوای تب ورود -->
    <div id="login-tab" class="tab-content <?php echo $active_tab == 'login' ? 'active' : ''; ?>">
        <?php if($error): ?>
            <div class="text-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" class="login-form" action="validate.php">
            <input type="hidden" name="action" value="login">
            <!-- بخش انتخاب پروژه -->
            <div class="form-group">
                <label class="form-label required">🏢 پروژه مربوطه:</label>
                <select name="project_id" class="form-select" required>
                    <option value="">-- انتخاب پروژه --</option>
                    <?php foreach ($projects as $project): ?>
                        <option value="<?php echo $project['id']; ?>">
                            🏗️ <?php echo htmlspecialchars($project['name']); ?>
                            <?php if (!empty($project['code'])): ?>
                                - <?php echo htmlspecialchars($project['code']); ?>
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label required">👤 نام کاربری (شماره ملی):</label>
                <input type="text" name="national_code" class="form-control" maxlength="10" required
                       placeholder="شماره ملی خود را وارد کنید">
            </div>

            <div class="form-group">
                <label class="form-label required">🔑 گذرواژه:</label>
                <input type="password" name="password" class="form-control" required
                       placeholder="گذرواژه خود را وارد کنید">
            </div>

            <!-- "من ربات نیستم" -->
            <div class="form-group">
                <div style="display: flex; align-items: center; gap: 10px; padding: 15px; background: #f0f7ff; border-radius: 8px;">
                    <input type="checkbox" id="not_robot_login" name="not_robot" required style="width: 20px; height: 20px;">
                    <label for="not_robot_login" style="font-weight: bold; cursor: pointer;">
                        🤖 من ربات نیستم
                    </label>
                </div>
            </div>

            <button type="submit" class="btn-login">
                🚀 ورود به سیستم
            </button>
        </form>
    </div>

    <!-- محتوای تب ثبت‌نام -->
    <div id="register-tab" class="tab-content <?php echo $active_tab == 'register' ? 'active' : ''; ?>">
        <?php if($success): ?>
            <div class="text-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 col-lg-8">
                    <form id="registrationForm" action="process_register.php" method="POST" enctype="multipart/form-data" class="register-form">
                        <input type="hidden" name="action" value="register">

                        <!-- بخش مشخصات فردی -->
                        <div class="section">
                            <h3><i class="fas fa-id-card"></i> مشخصات فردی</h3>

                            <!-- نام و نام خانوادگی -->
                            <div class="form-group">
                                <label for="first_name" class="required">نام:</label>
                                <input type="text" id="first_name" name="first_name" class="form-control" required>
                                <div class="error" id="first_name_error"></div>
                            </div>

                            <div class="form-group">
                                <label for="last_name" class="required">نام خانوادگی:</label>
                                <input type="text" id="last_name" name="last_name" class="form-control" required>
                                <div class="error" id="last_name_error"></div>
                            </div>

                            <!-- شماره ملی -->
                            <div class="form-group">
                                <label for="national_id" class="required">شماره ملی (۱۰ رقم):</label>
                                <input type="text" id="national_id" name="national_id" class="form-control"
                                       pattern="\d{10}"
                                       maxlength="10"
                                       placeholder="مثال: ۰۰۷۸۶۱۰۶۴۱"
                                       required>
                                <div class="loading" id="national_id_loading">در حال بررسی...</div>
                                <div class="success" id="national_id_success">✓ شماره ملی معتبر است</div>
                                <div class="error" id="national_id_error"></div>
                            </div>

                            <!-- شماره شناسنامه و نام پدر -->
                            <div class="form-group">
                                <label for="birth_certificate_number" class="required">شماره شناسنامه:</label>
                                <input type="text" id="birth_certificate_number" name="birth_certificate_number" class="form-control" required>
                            </div>

                            <div class="form-group">
                                <label for="father_name" class="required">نام پدر:</label>
                                <input type="text" id="father_name" name="father_name" class="form-control" required>
                            </div>

                            <!-- تاریخ تولد -->
                            <div class="form-group">
                                <label class="required">تاریخ تولد (شمسی):</label>
                                <div class="help-text">
                                    <strong>راهنما:</strong> لطفاً تاریخ تولد خود را به درستی انتخاب کنید.
                                </div>

                                <div class="date-container">
                                    <div class="date-row">
                                        <div class="date-field">
                                            <label for="birth_year">سال:</label>
                                            <select id="birth_year" name="birth_year" class="form-select" required>
                                                <option value="">انتخاب سال</option>
                                                <!-- سال‌ها با JavaScript پر می‌شود -->
                                            </select>
                                        </div>

                                        <div class="date-field">
                                            <label for="birth_month">ماه:</label>
                                            <select id="birth_month" name="birth_month" class="form-select" required>
                                                <option value="">انتخاب ماه</option>
                                                <option value="01">فروردین (۱)</option>
                                                <option value="02">اردیبهشت (۲)</option>
                                                <option value="03">خرداد (۳)</option>
                                                <option value="04">تیر (۴)</option>
                                                <option value="05">مرداد (۵)</option>
                                                <option value="06">شهریور (۶)</option>
                                                <option value="07">مهرآفرین (۷)</option>
                                                <option value="08">آبان (۸)</option>
                                                <option value="09">آذر (۹)</option>
                                                <option value="10">دی (۱۰)</option>
                                                <option value="11">بهمن (۱۱)</option>
                                                <option value="12">اسفند (۱۲)</option>
                                            </select>
                                        </div>

                                        <div class="date-field">
                                            <label for="birth_day">روز:</label>
                                            <select id="birth_day" name="birth_day" class="form-select" required>
                                                <option value="">انتخاب روز</option>
                                                <!-- روزها با JavaScript پر می‌شود -->
                                            </select>
                                        </div>
                                    </div>

                                    <div class="date-preview">
                                        <div class="label">تاریخ انتخابی:</div>
                                        <div id="selected_date">--- / -- / --</div>
                                    </div>
                                </div>

                                <input type="hidden" id="birth_date_fa" name="birth_date_fa">
                                <input type="hidden" id="birth_date" name="birth_date">
                            </div>

                            <!-- تلفن‌ها -->
                            <div class="form-group">
                                <label for="mobile" class="required">تلفن همراه:</label>
                                <input type="tel" id="mobile" name="mobile" class="form-control"
                                       pattern="09\d{9}"
                                       placeholder="مثال: ۰۹۱۲۳۴۵۶۷۸۹"
                                       required>
                                <div class="error" id="mobile_error"></div>
                            </div>

                            <div class="form-group">
                                <label for="phone">تلفن ثابت:</label>
                                <input type="tel" id="phone" name="phone" class="form-control"
                                       pattern="\d{8,11}"
                                       placeholder="مثال: ۰۲۱۸۸۵۵۶۶۷۷">
                            </div>

                            <!-- آدرس -->
                            <div class="form-group">
                                <label for="address" class="required">آدرس کامل:</label>
                                <textarea id="address" name="address" rows="4" class="form-control" required></textarea>
                            </div>

                            <div class="form-group">
                                <label for="postalCode" class="required">کد پستی (۱۰ رقم):</label>
                                <input type="text"
                                       id="postalCode"
                                       name="postalCode"
                                       class="form-control"
                                       pattern="\d{10}"
                                       maxlength="10"
                                       placeholder="مثال: ۱۲۳۴۵۶۷۸۹۰"
                                       required>
                                <div class="error" id="postalCode_error"></div>
                            </div>
                        </div>

                        <!-- بخش چک‌ها -->
                        <div class="section">
                            <h3><i class="fas fa-money-check-alt"></i> اطلاعات چک‌ها</h3>
                            <div class="note" style="margin-bottom: 20px;">
                                <strong>توجه:</strong> لطفاً تاریخ چک‌ها را به فرمت میلادی (YYYY-MM-DD) وارد کنید یا از مبدل تاریخ استفاده کنید.
                            </div>

                            <?php for($i = 1; $i <= 3; $i++): ?>
                            <div class="check-section">
                                <h4>چک شماره <?= $i ?></h4>

                                <div class="check-row">
                                    <div class="check-field">
                                        <label class="form-label required">شماره چک:</label>
                                        <input type="text"
                                               name="check<?= $i ?>_number"
                                               class="form-control"
                                               placeholder="شماره چک"
                                               required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label required">تاریخ چک:</label>
                                        <div class="date-input-container">
                                            <input type="text"
                                                   id="check<?= $i ?>_date"
                                                   name="check<?= $i ?>_date"
                                                   class="form-control persian-datepicker"
                                                   placeholder="برای انتخاب تاریخ کلیک کنید"
                                                   autocomplete="off"
                                                   required>
                                            <span class="datepicker-trigger">
                            <i class="bi bi-calendar"></i>
                        </span>
                                        </div>
                                        <div class="note">تاریخ شمسی - فرمت: سال/ماه/روز</div>
                                    </div>

                                    <div class="check-row">
                                        <div class="check-field">
                                            <label class="form-label required">مبلغ چک (ریال):</label>
                                            <input type="number"
                                                   name="check<?= $i ?>_amount"
                                                   class="form-control"
                                                   placeholder="مثال: 10000000"
                                                   min="1"
                                                   required>
                                        </div>

                                        <div class="check-field">
                                            <label class="form-label required">بانک صادرکننده:</label>
                                            <input type="text"
                                                   name="check<?= $i ?>_bank"
                                                   class="form-control"
                                                   placeholder="نام بانک"
                                                   required>
                                        </div>
                                    </div>

                                    <div class="check-row">
                                        <div class="check-field">
                                            <label class="form-label required">تصویر چک:</label>
                                            <input type="file"
                                                   name="check<?= $i ?>_image"
                                                   class="form-control"
                                                   accept=".jpg,.jpeg,.png,.pdf"
                                                   required>
                                            <div class="note">تصویر واضح از چک (JPG, PNG, PDF - حداکثر ۲۰۰KB)</div>
                                        </div>
                                    </div>
                                </div>
                                <?php endfor; ?>
                            </div>

                            <!-- بخش مدارک -->
                            <div class="section">
                                <h3><i class="fas fa-file-upload"></i> بارگذاری مدارک</h3>

                                <!-- کارت ملی و شناسنامه -->
                                <div class="form-group">
                                    <label for="national_card_image" class="required">تصویر کارت ملی:</label>
                                    <input type="file" id="national_card_image" name="national_card_image"
                                           accept=".jpg,.jpeg,.png,.pdf"
                                           required>
                                    <div class="note">عکس واضح از هر دو طرف کارت ملی (JPG, PNG, PDF)</div>
                                </div>

                                <div class="form-group">
                                    <label for="birth_certificate_image" class="required">تصویر شناسنامه:</label>
                                    <input type="file" id="birth_certificate_image" name="birth_certificate_image"
                                           accept=".jpg,.jpeg,.png,.pdf"
                                           required>
                                    <div class="note">صفحه اول و دوم شناسنامه (JPG, PNG, PDF)</div>
                                </div>
                            </div>

                            <!-- بخش تاییدیه‌ها -->
                            <div class="section">
                                <h3><i class="fas fa-check-circle"></i> تاییدیه‌ها</h3>

                                <!-- پروژه مربوطه -->
                                <div class="form-group">
                                    <label for="project_id_reg" class="required">🏢 پروژه مربوطه:</label>
                                    <select name="project_id_reg" id="project_id_reg" class="form-select" >
                                        <option value="1">                                🏗صدرا</option>

                                    </select>
                                </div>

                                <!-- "من ربات نیستم" -->
                                <div class="form-group">
                                    <div style="display: flex; align-items: center; gap: 10px; padding: 15px; background: #f0f7ff; border-radius: 8px;">
                                        <input type="checkbox" id="not_robot" name="not_robot" required style="width: 20px; height: 20px;">
                                        <label for="not_robot" style="font-weight: bold; cursor: pointer;">
                                            🤖 من ربات نیستم
                                        </label>
                                    </div>
                                </div>

                                <!-- تایید اطلاعات -->
                                <div class="form-group">
                                    <div style="display: flex; align-items: flex-start; gap: 10px; padding: 15px; background: #e8f6ef; border-radius: 8px;">
                                        <input type="checkbox" id="confirm_info" name="confirm_info" required style="width: 20px; height: 20px; margin-top: 3px;">
                                        <label for="confirm_info" style="cursor: pointer;">
                                            <strong>✅ تایید اطلاعات</strong><br>
                                            من تمام اطلاعات فوق را تأیید می‌کنم و مسئولیت صحت آن‌ها را می‌پذیرم.
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- دکمه ارسال -->
                            <button type="submit" class="submit-btn-register">
                                <i class="fas fa-paper-plane"></i> ثبت اطلاعات و ارسال مدارک
                            </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<link rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/css/persian-datepicker.min.css">

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/persian-date@1.1.0/dist/persian-date.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/js/persian-datepicker.min.js"></script>

<script>

    $(document).ready(function() {

        console.log("jQuery loaded");
        // فعال‌سازی Persian Datepicker
        $(function () {
            $('.persian-datepicker').persianDatepicker({
                format: 'YYYY/MM/DD',
                autoClose: true,
                initialValue: false,
                persianDigit: true,
                observer: true,
                calendar: {
                    persian: {
                        locale: 'fa',
                        showHint: true,
                        leapYearMode: "algorithmic"
                    }
                },
                toolbox: {
                    calendarSwitch: { enabled: false }
                }
            });
        });

        // اعتبارسنجی فرم
        $('#chequeForm').on('submit', function(e) {
            let isValid = true;

            // بررسی تاریخ‌ها
            $('.persian-datepicker').each(function() {
                const value = $(this).val();
                const regex = /^1[3-4]\d{2}\/(0[1-9]|1[0-2])\/(0[1-9]|[12]\d|3[01])$/;

                if (!value || !regex.test(value)) {
                    isValid = false;
                    $(this).addClass('is-invalid');
                    alert('لطفاً تاریخ شمسی را به درستی وارد کنید. فرمت صحیح: 1402/10/15');
                } else {
                    $(this).removeClass('is-invalid');
                }
            });

            if (!isValid) {
                e.preventDefault();
                return false;
            }

            // تبدیل تاریخ شمسی به میلادی قبل از ارسال (اختیاری)
            $('.persian-datepicker').each(function() {
                const persianDate = $(this).val();
                if (persianDate) {
                    // ایجاد یک فیلد مخفی برای تاریخ میلادی
                    const inputName = $(this).attr('name');
                    const gregorianDate = persianToGregorian(persianDate);

                    // حذف فیلد مخفی قبلی اگر وجود دارد
                    $(`input[name="${inputName}_gregorian"]`).remove();

                    // اضافه کردن فیلد مخفی جدید
                    $(this).after(`<input type="hidden" name="${inputName}_gregorian" value="${gregorianDate}">`);
                }
            });
        });

        // تابع تبدیل تاریخ شمسی به میلادی
        function persianToGregorian(persianDate) {
            const parts = persianDate.split('/');
            if (parts.length !== 3) return '';

            const jy = parseInt(parts[0]);
            const jm = parseInt(parts[1]);
            const jd = parseInt(parts[2]);

            // الگوریتم تبدیل
            const g_days_in_month = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
            const j_days_in_month = [31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29];

            let year = jy - 979;
            let month = jm - 1;
            let day = jd - 1;

            let day_no = 365 * year + parseInt(year / 33) * 8 + parseInt((year % 33 + 3) / 4);

            for (let i = 0; i < month; ++i) {
                day_no += j_days_in_month[i];
            }

            day_no += day;
            day_no += 79;

            let greg_year = 1600 + 400 * parseInt(day_no / 146097);
            day_no %= 146097;

            let leap = true;
            if (day_no >= 36525) {
                day_no--;
                greg_year += 100 * parseInt(day_no / 36524);
                day_no %= 36524;

                if (day_no >= 365) {
                    day_no++;
                } else {
                    leap = false;
                }
            }

            greg_year += 4 * parseInt(day_no / 1461);
            day_no %= 1461;

            if (day_no >= 366) {
                leap = false;
                day_no--;
                greg_year += parseInt(day_no / 365);
                day_no %= 365;
            }

            let greg_month;
            for (greg_month = 0; day_no >= g_days_in_month[greg_month] + (greg_month == 1 && leap ? 1 : 0); greg_month++) {
                day_no -= g_days_in_month[greg_month] + (greg_month == 1 && leap ? 1 : 0);
            }

            const greg_day = day_no + 1;

            return `${greg_year}-${String(greg_month + 1).padStart(2, '0')}-${String(greg_day).padStart(2, '0')}`;
        }
    });
    // مدیریت تب‌ها
    document.addEventListener('DOMContentLoaded', function() {
        const tabButtons = document.querySelectorAll('.tab-button');
        const tabContents = document.querySelectorAll('.tab-content');

        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                const tabId = this.getAttribute('data-tab');

                // حذف کلاس active از همه تب‌ها
                tabButtons.forEach(btn => btn.classList.remove('active'));
                tabContents.forEach(content => content.classList.remove('active'));

                // اضافه کردن کلاس active به تب انتخاب شده
                this.classList.add('active');
                document.getElementById(tabId + '-tab').classList.add('active');

                // ذخیره وضعیت تب در URL
                const url = new URL(window.location);
                url.searchParams.set('tab', tabId);
                window.history.pushState({}, '', url);
            });
        });

        // ==================== تنظیمات تاریخ تولد ====================

        // تنظیمات اولیه
        const currentPersianYear = 1404;

        // ۱. پر کردن سال‌ها (از ۱۳۰۰ تا ۱۴۰۴)
        const yearSelect = document.getElementById('birth_year');
        if (yearSelect) {
            yearSelect.innerHTML = '<option value="">انتخاب سال</option>';
            for (let year = currentPersianYear; year >= 1300; year--) {
                const option = document.createElement('option');
                option.value = year;
                option.textContent = year;
                yearSelect.appendChild(option);
            }
        }

        // ۲. پر کردن روزها (۱ تا ۳۱)
        const daySelect = document.getElementById('birth_day');
        if (daySelect) {
            daySelect.innerHTML = '<option value="">انتخاب روز</option>';
            for (let day = 1; day <= 31; day++) {
                const option = document.createElement('option');
                option.value = day.toString().padStart(2, '0');
                option.textContent = day;
                daySelect.appendChild(option);
            }
        }

        // ۳. نمایش تاریخ انتخابی
        function updateSelectedDate() {
            const year = document.getElementById('birth_year')?.value;
            const month = document.getElementById('birth_month')?.value;
            const day = document.getElementById('birth_day')?.value;
            const selectedDateElement = document.getElementById('selected_date');

            if (year && month && day && selectedDateElement) {
                const monthNames = {
                    '01': 'فروردین', '02': 'اردیبهشت', '03': 'خرداد',
                    '04': 'تیر', '05': 'مرداد', '06': 'شهریور',
                    '07': 'مهر', '08': 'آبان', '09': 'آذر',
                    '10': 'دی', '11': 'بهمن', '12': 'اسفند'
                };

                const displayDateFa = `${day} ${monthNames[month]} ${year}`;
                selectedDateElement.textContent = displayDateFa;

                // ذخیره در فیلدهای مخفی
                const birthDateFa = document.getElementById('birth_date_fa');
                const birthDate = document.getElementById('birth_date');

                if (birthDateFa) birthDateFa.value = `${year}/${month}/${day}`;

                if (birthDate) {
                    const persianDate = `${year}/${month}/${day}`;
                    const gregorianDate = persianToGregorian(persianDate);

                }
            } else if (selectedDateElement) {
                selectedDateElement.textContent = '--- / -- / --';
            }
        }

        // ۴. رویداد تغییر برای تاریخ
        const birthYear = document.getElementById('birth_year');
        const birthMonth = document.getElementById('birth_month');
        const birthDay = document.getElementById('birth_day');

        if (birthYear) birthYear.addEventListener('change', updateSelectedDate);
        if (birthMonth) birthMonth.addEventListener('change', updateSelectedDate);
        if (birthDay) birthDay.addEventListener('change', updateSelectedDate);

        // ==================== تنظیمات تاریخ چک‌ها ====================

        // پر کردن سال‌ها برای مبدل تاریخ چک‌ها
        for (let i = 1; i <= 3; i++) {
            const yearSelect = document.getElementById(`check${i}_year`);
            if (yearSelect) {
                yearSelect.innerHTML = '<option value="">سال</option>';
                for (let year = 1400; year <= 1410; year++) {
                    const option = document.createElement('option');
                    option.value = year;
                    option.textContent = year;
                    yearSelect.appendChild(option);
                }
            }

            const daySelect = document.getElementById(`check${i}_day`);
            if (daySelect) {
                daySelect.innerHTML = '<option value="">روز</option>';
                for (let day = 1; day <= 31; day++) {
                    const option = document.createElement('option');
                    option.value = day.toString().padStart(2, '0');
                    option.textContent = day;
                    daySelect.appendChild(option);
                }
            }
        }

        // ==================== مدیریت ارسال فرم ====================

        // اعتبارسنجی فرم ثبت‌نام هنگام ارسال
        const registrationForm = document.getElementById('registrationForm');
        const submitBtn = document.querySelector('.submit-btn-register');
        const originalBtnText = submitBtn.innerHTML;

        if (registrationForm) {
            registrationForm.addEventListener('submit', function(e) {
                if (validateRegisterForm()) {
                    // نمایش spinner و غیرفعال کردن دکمه
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> در حال پردازش...';
                    submitBtn.disabled = true;
                    submitBtn.style.opacity = '0.7';
                    submitBtn.style.cursor = 'not-allowed';

                    // اجازه ارسال فرم
                    return true;
                } else {
                    e.preventDefault();
                    return false;
                }
            });
        }

        // ==================== اعتبارسنجی‌ها ====================

        // اعتبارسنجی شماره ملی
        const nationalIdInput = document.getElementById('national_id');
        if (nationalIdInput) {
            nationalIdInput.addEventListener('blur', function() {
                validateNationalId(this.value);
            });
        }

        // اعتبارسنجی موبایل
        const mobileInput = document.getElementById('mobile');
        if (mobileInput) {
            mobileInput.addEventListener('blur', function() {
                validateMobile(this.value);
            });
        }

        // اعتبارسنجی کد پستی
        const postalCodeInput = document.getElementById('postalCode');
        if (postalCodeInput) {
            postalCodeInput.addEventListener('input', function() {
                // حذف غیر اعداد
                this.value = this.value.replace(/\D/g, '');
            });

            postalCodeInput.addEventListener('blur', function() {
                validatePostalCode(this.value);
            });
        }
    });

    // ==================== توابع مبدل تاریخ ====================

    // تابع تبدیل تاریخ شمسی به میلادی
    function convertJalaliToGregorian(checkNumber) {
        const year = document.getElementById(`check${checkNumber}_year`).value;
        const month = document.getElementById(`check${checkNumber}_month`).value;
        const day = document.getElementById(`check${checkNumber}_day`).value;

        if (!year || !month || !day) {
            alert('لطفاً سال، ماه و روز تاریخ چک را انتخاب کنید.');
            return;
        }

        // تبدیل ساده شمسی به میلادی
        // سال شمسی = سال میلادی - 621
        const gregorianYear = parseInt(year) + 621;

        // تاریخ میلادی
        const gregorianDate = `${gregorianYear}-${month}-${day}`;

        // اعتبارسنجی تاریخ
        const dateObj = new Date(gregorianDate);
        if (isNaN(dateObj.getTime())) {
            alert('تاریخ وارد شده معتبر نیست.');
            return;
        }

        // نمایش تاریخ میلادی
        document.getElementById(`check${checkNumber}_gregorian_date`).textContent = gregorianDate;
        document.getElementById(`check${checkNumber}_converted_date`).style.display = 'block';

        // پر کردن خودکار فیلد تاریخ
        document.getElementById(`check${checkNumber}_date`).value = gregorianDate;

        // اعتبارسنجی تاریخ
    }

    // ==================== توابع اعتبارسنجی ====================

    // تابع اعتبارسنجی شماره ملی ایرانی
    function isValidIranianNationalId(input) {
        if (!/^\d{10}$/.test(input)) return false;

        const invalidPatterns = [
            '0000000000', '1111111111', '2222222222', '3333333333',
            '4444444444', '5555555555', '6666666666', '7777777777',
            '8888888888', '9999999999'
        ];

        if (invalidPatterns.includes(input)) return false;

        const check = parseInt(input[9]);
        let sum = 0;

        for (let i = 0; i < 9; i++) {
            sum += parseInt(input[i]) * (10 - i);
        }

        const remainder = sum % 11;

        if (remainder < 2) {
            return check === remainder;
        } else {
            return check === (11 - remainder);
        }
    }

    // اعتبارسنجی شماره ملی
    function validateNationalId(nationalId) {
        const loadingEl = document.getElementById('national_id_loading');
        const successEl = document.getElementById('national_id_success');
        const errorEl = document.getElementById('national_id_error');

        if (loadingEl) loadingEl.style.display = 'none';
        if (successEl) successEl.style.display = 'none';
        if (errorEl) errorEl.style.display = 'none';

        if (!nationalId) return;

        if (!/^\d{10}$/.test(nationalId)) {
            showError('national_id_error', 'شماره ملی باید دقیقاً ۱۰ رقم باشد.');
            return;
        }

        if (!isValidIranianNationalId(nationalId)) {
            showError('national_id_error', 'شماره ملی وارد شده معتبر نیست.');
            return;
        }

        if (loadingEl) loadingEl.style.display = 'block';

        setTimeout(() => {
            if (loadingEl) loadingEl.style.display = 'none';
            if (successEl) successEl.style.display = 'block';
        }, 800);
    }

    // اعتبارسنجی موبایل
    function validateMobile(mobile) {
        const errorEl = document.getElementById('mobile_error');

        if (!mobile) {
            if (errorEl) errorEl.style.display = 'none';
            return;
        }

        if (!/^09\d{9}$/.test(mobile)) {
            showError('mobile_error', 'شماره همراه معتبر نیست. فرمت صحیح: ۰۹۱۲۳۴۵۶۷۸۹');
        } else if (errorEl) {
            errorEl.style.display = 'none';
        }
    }

    // اعتبارسنجی کد پستی
    function validatePostalCode(postalCode) {
        const errorEl = document.getElementById('postalCode_error');

        if (!postalCode) {
            if (errorEl) errorEl.style.display = 'none';
            return false;
        }

        if (!/^\d{10}$/.test(postalCode)) {
            showError('postalCode_error', 'کد پستی باید ۱۰ رقم باشد.');
            return false;
        }

        if (errorEl) errorEl.style.display = 'none';
        return true;
    }

    // اعتبارسنجی تاریخ چک

    // اعتبارسنجی تاریخ تولد
    function validateBirthDate() {
        const year = document.getElementById('birth_year')?.value;
        const month = document.getElementById('birth_month')?.value;
        const day = document.getElementById('birth_day')?.value;

        if (!year || !month || !day) {
            alert('لطفاً تاریخ تولد را کامل انتخاب کنید.');
            return false;
        }

        // بررسی اینکه سال منطقی باشد (بین ۱۳۰۰ تا ۱۴۰۴)
        if (parseInt(year) < 1300 || parseInt(year) > 1404) {
            alert('سال تولد باید بین ۱۳۰۰ تا ۱۴۰۴ باشد.');
            return false;
        }

        return true;
    }

    // اعتبارسنجی فایل
    function validateFile(fileInput) {
        const file = fileInput.files[0];

        if (!file) return true;

        const allowedExtensions = ['.jpg', '.jpeg', '.png', '.pdf'];
        const fileName = file.name.toLowerCase();
        const hasValidExtension = allowedExtensions.some(ext => fileName.endsWith(ext));

        if (!hasValidExtension) {
            alert(`فایل "${file.name}" معتبر نیست. فقط فایل‌های JPG, PNG, PDF مجاز هستند.`);
            fileInput.value = '';
            return false;
        }

        const maxSize = 200 * 1024;
        if (file.size > maxSize) {
            alert(`حجم فایل "${file.name}" بیش از ۲۰۰ کیلوبایت است. (حجم فعلی: ${Math.round(file.size/1024)}KB)`);
            fileInput.value = '';
            return false;
        }

        return true;
    }

    // اعتبارسنجی نهایی فرم ثبت‌نام
    function validateRegisterForm() {
        let isValid = true;

        // اعتبارسنجی نام و نام خانوادگی
        const firstName = document.getElementById('first_name')?.value.trim() || '';
        const lastName = document.getElementById('last_name')?.value.trim() || '';

        if (!firstName) {
            showError('first_name_error', 'لطفاً نام خود را وارد کنید.');
            isValid = false;
        }

        if (!lastName) {
            showError('last_name_error', 'لطفاً نام خانوادگی خود را وارد کنید.');
            isValid = false;
        }

        // اعتبارسنجی شماره ملی
        const nationalId = document.getElementById('national_id')?.value || '';
        if (!isValidIranianNationalId(nationalId)) {
            showError('national_id_error', 'شماره ملی معتبر نیست.');
            isValid = false;
        }

        // اعتبارسنجی موبایل
        const mobile = document.getElementById('mobile')?.value || '';
        if (!/^09\d{9}$/.test(mobile)) {
            showError('mobile_error', 'شماره همراه معتبر نیست.');
            isValid = false;
        }

        // اعتبارسنجی کد پستی
        const postalCode = document.getElementById('postalCode')?.value || '';
        if (!/^\d{10}$/.test(postalCode)) {
            showError('postalCode_error', 'کد پستی باید ۱۰ رقم باشد.');
            isValid = false;
        }

        // اعتبارسنجی تاریخ تولد
        if (!validateBirthDate()) {
            isValid = false;
        }

        // اعتبارسنجی اطلاعات چک‌ها
        for (let i = 1; i <= 3; i++) {
            const checkNumber = document.querySelector(`input[name="check${i}_number"]`)?.value.trim() || '';
            const checkDate = document.getElementById(`check${i}_date`)?.value || '';
            const checkAmount = document.querySelector(`input[name="check${i}_amount"]`)?.value || '';
            const checkBank = document.querySelector(`input[name="check${i}_bank"]`)?.value.trim() || '';
            const checkFile = document.querySelector(`input[name="check${i}_image"]`);

            if (!checkNumber || !checkDate || !checkAmount || !checkBank) {
                alert(`لطفاً تمام اطلاعات چک شماره ${i} را کامل کنید.`);
                isValid = false;
                break;
            }
        }

        // اعتبارسنجی فایل‌های اصلی
        const requiredFiles = [
            'national_card_image',
            'birth_certificate_image'
        ];

        for (const fileId of requiredFiles) {
            const fileInput = document.getElementById(fileId);
            if (!fileInput?.files?.[0]) {
                const label = fileId === 'national_card_image' ? 'کارت ملی' : 'شناسنامه';
                alert(`لطفاً فایل ${label} را انتخاب کنید.`);
                isValid = false;
                break;
            }
        }

        // اعتبارسنجی "من ربات نیستم"
        const notRobotCheckbox = document.getElementById('not_robot');
        if (!notRobotCheckbox?.checked) {
            alert('لطفاً تأیید کنید که ربات نیستید.');
            isValid = false;
        }

        // اعتبارسنجی تایید اطلاعات
        const confirmInfo = document.getElementById('confirm_info')?.checked || false;
        if (!confirmInfo) {
            alert('لطفاً تایید اطلاعات را انجام دهید.');
            isValid = false;
        }

        if (!isValid) {
            // اسکرول به اولین خطا
            setTimeout(() => {
                const firstError = document.querySelector('.error[style*="display: block"]');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }, 100);
        }

        return isValid;
    }

    // تابع کمکی برای نمایش خطا
    function showError(elementId, message) {
        const element = document.getElementById(elementId);
        if (element) {
            element.textContent = message;
            element.style.display = 'block';
        }
    }

</script>
<?php
// ۶. پایان ذخیره در بافر و ارسال به لایوت
$content = ob_get_clean();
include 'partials/layout.php';
?>
