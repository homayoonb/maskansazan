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
    /* ==========================================================================
       ۱. متغیرهای اصلی (Design Tokens)
       ========================================================================== */
    :root {
        --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --success-gradient: linear-gradient(135deg, #27ae60 0%, #219653 100%);
        --primary-color: #667eea;
        --primary-dark: #764ba2;
        --secondary-blue: #3498db;
        --success-color: #27ae60;
        --danger-color: #e74c3c;

        --bg-light: #f8fafc;
        --border-color: #e2e8f0;
        --text-main: #2d3748;
        --text-muted: #718096;

        --font-family: 'Vazirmatn', sans-serif;
        --radius-sm: 8px;
        --radius-md: 12px;
        --radius-lg: 16px;
        --shadow-soft: 0 10px 25px rgba(0, 0, 0, 0.05);
        --shadow-hover: 0 15px 30px rgba(102, 126, 234, 0.2);
    }

    /* ==========================================================================
       ۲. چیدمان اصلی و کارت ورود (Login Card & Wrapper)
       ========================================================================== */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        width: 100%;
        overflow-x: hidden; /* جلوگیری از اسکرول افقی بی‌مورد */
    }
    .container{
        padding-left: 0 !important;
        padding-right: 0 !important;
        margin-left: 0 !important;
        margin-right: 0 !important;
        margin-top: 0 !important;
    }
    .login-wrapper {
        display: flex;
        justify-content: center; /* تراز افقی در مرکز */
        align-items: flex-start;  /* تراز عمودی از بالا */
        min-height: 100vh;      /* برای اینکه صفحه در کل ارتفاع مرورگر در دسترس باشد */
        box-sizing: border-box; /* برای اینکه پدینگ از عرض کلی کم نشود */
        font-family: var(--font-family);

    }

    div.container.row{
        padding-left: 0 !important;
        padding-right: 0 !important;
        margin-left: 0 !important;
        margin-right: 0 !important;
        margin-top: 0 !important;
    }
    /* اطمینان از اینکه کارت بیش از حد به لبه‌ها نچسبد */
    @media (max-width: 576px) {
        .login-wrapper {
            padding: 10px;      /* در موبایل فاصله کمتر */
        }
    }

    .login-card {
        background: #ffffff;
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-soft);
        width: 100%;
        max-width: 850px;
        overflow: hidden;
        border: 1px solid var(--border-color);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .login-header {
        background: var(--primary-gradient);
        color: #ffffff;
        padding: 30px 20px;
        text-align: center;
    }

    .login-header h1 {
        font-size: 1.5rem;
        font-weight: 700;
        margin: 0;
    }

    .login-header p {
        font-size: 0.9rem;
        opacity: 0.9;
        margin-top: 8px;
    }

    /* ==========================================================================
       ۳. تب‌ها (Tabs Navigation)
       ========================================================================== */
    .tabs-container {
        display: flex;
        border-bottom: 1px solid var(--border-color);
        background: #f1f5f9;
    }

    .tab-button {
        flex: 1;
        padding: 16px;
        border: none;
        background: transparent;
        cursor: pointer;
        font-family: var(--font-family);
        font-weight: 600;
        font-size: 0.95rem;
        color: var(--text-muted);
        transition: all 0.3s ease;
    }

    .tab-button:hover {
        color: var(--primary-color);
        background: rgba(255, 255, 255, 0.5);
    }

    .tab-button.active {
        background: #ffffff;
        color: var(--primary-dark);
        border-bottom: 3px solid var(--primary-dark);
    }

    .tab-content {
        display: none;
        padding: 25px;
    }

    .tab-content.active {
        display: block;
        animation: fadeInTab 0.4s ease-out forwards;
    }

    /* ==========================================================================
       ۴. فرم‌ها و ورودی‌ها (Form Controls)
       ========================================================================== */
    .form-group {
        margin-bottom: 20px;
    }

    .form-label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: var(--text-main);
        font-size: 0.9rem;
    }

    .form-control,
    .form-select {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid var(--border-color);
        border-radius: var(--radius-md);
        font-size: 0.95rem;
        font-family: var(--font-family);
        font-weight: 500;
        background: var(--bg-light);
        color: var(--text-main);
        transition: all 0.3s ease;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: var(--primary-color);
        background: #ffffff;
        box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.15);
        outline: none;
    }

    .form-select {
        cursor: pointer;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='20' height='20' viewBox='0 0 24 24' fill='none' stroke='%23667eea' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: left 16px center;
        background-size: 16px;
        padding-left: 45px;
    }

    .required::after {
        content: " *";
        color: var(--danger-color);
    }

    /* ==========================================================================
       ۵. دکمه‌ها (Buttons)
       ========================================================================== */
    .btn-login,
    .submit-btn-register {
        width: 100%;
        border: none;
        padding: 14px 20px;
        border-radius: var(--radius-md);
        cursor: pointer;
        font-size: 1rem;
        font-weight: 700;
        font-family: var(--font-family);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        transition: all 0.3s ease;
        margin-top: 15px;
        color: #ffffff;
    }

    .btn-login {
        background: var(--primary-gradient);
    }

    .submit-btn-register {
        background: var(--success-gradient);
    }

    .btn-login:hover,
    .submit-btn-register:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow-hover);
    }

    .btn-login:active,
    .submit-btn-register:active {
        transform: translateY(0);
    }

    /* ==========================================================================
       ۶. بخش‌ها و کارت‌های فرعی (Sections & Cards)
       ========================================================================== */
    .section {
        background: var(--bg-light);
        padding: 20px;
        border-radius: var(--radius-md);
        margin-bottom: 25px;
        border-right: 4px solid var(--secondary-blue);
    }

    .section h3 {
        color: var(--text-main);
        margin: 0 0 15px 0;
        font-size: 1.15rem;
        font-weight: 700;
    }

    .check-section {
        background: #f0f7ff;
        border: 2px solid #cce5ff;
        border-radius: var(--radius-md);
        padding: 20px;
        margin-bottom: 20px;
    }

    .check-section h4 {
        color: #0066cc;
        margin: 0 0 15px 0;
        padding-bottom: 8px;
        border-bottom: 2px dashed #b3d9ff;
        font-size: 1rem;
    }

    .check-row {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 15px;
    }

    .check-field {
        flex: 1;
        min-width: 220px;
    }

    /* انتخابگر تاریخ و تقویم */
    .date-container {
        background: #ffffff;
        border: 2px solid var(--border-color);
        border-radius: var(--radius-md);
        padding: 15px;
        margin-bottom: 15px;
    }

    .date-row {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }

    .date-field {
        flex: 1;
        min-width: 120px;
    }

    .date-preview {
        background: #f1f8ff;
        border: 2px dashed var(--secondary-blue);
        border-radius: var(--radius-md);
        padding: 12px;
        text-align: center;
        font-size: 1rem;
        color: var(--text-main);
        margin-top: 10px;
        font-weight: 700;
    }

    .date-input-container {
        position: relative;
    }

    .date-input-container .datepicker-trigger {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: var(--text-muted);
    }

    .checkbox-container {
        display: flex;
        align-items: center;
        gap: 10px;
        margin: 20px 0;
        padding: 12px 15px;
        background: #f1f8ff;
        border-radius: var(--radius-sm);
        font-size: 0.9rem;
    }

    input[type="checkbox"] {
        width: 18px;
        height: 18px;
        cursor: pointer;
        accent-color: var(--primary-color);
    }

    /* ==========================================================================
       ۷. پیام‌ها و فیدبک‌ها (Alerts & Feedback)
       ========================================================================== */
    .text-danger,
    .text-success {
        padding: 12px 15px;
        border-radius: var(--radius-sm);
        margin-bottom: 20px;
        text-align: center;
        font-weight: 600;
        font-size: 0.9rem;
    }

    .text-danger {
        background: #fff5f5;
        color: var(--danger-color);
        border: 1px solid #fed7d7;
    }

    .text-success {
        background: #f0fff4;
        color: var(--success-color);
        border: 1px solid #c6f6d5;
    }

    .help-text {
        background: #e8f4fc;
        border-right: 4px solid var(--secondary-blue);
        padding: 10px 15px;
        margin: 10px 0;
        border-radius: var(--radius-sm);
        font-size: 0.85rem;
        color: var(--text-main);
    }

    /* ==========================================================================
       ۸. انیمیشن‌ها (Animations)
       ========================================================================== */
    @keyframes fadeInTab {
        from {
            opacity: 0;
            transform: translateY(8px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* ==========================================================================
       ۹. واکنش‌گرایی (Responsive Design)
       ========================================================================== */

    /* دسکتاپ و نمایشگرهای بزرگ‌تر (>= 768px) */
    @media (min-width: 768px) {
        .login-header {
            padding: 40px 30px;
        }

        .login-header h1 {
            font-size: 1.8rem;
        }

        .tab-content {
            padding: 35px 30px;
        }
    }

    /* تبلت و موبایل (< 768px) */
    @media (max-width: 767.98px) {
        .login-wrapper {
            padding: 10px;
        }

        .login-header {
            padding: 25px 15px;
        }

        .tab-content {
            padding: 20px 15px;
        }

        .check-field,
        .date-field {
            min-width: 100%;
        }

        .date-row {
            flex-direction: column;
            gap: 10px;
        }

        .tabs-container {
            flex-direction: row;
        }

        .tab-button {
            padding: 12px 8px;
            font-size: 0.85rem;
        }
    }
</style>
<div class="container-fluid"   >
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
                <div class="text-danger mb-3"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" class="login-form" action="validate.php">
                <input type="hidden" name="action" value="login">

                <!-- ورودی‌ها -->
                <div class="form-group mb-4">
                    <label class="form-label required">🏢 پروژه مربوطه:</label>
                    <select name="project_id" class="form-select" required>
                        <option value="">-- انتخاب پروژه --</option>
                        <?php foreach ($projects as $project): ?>
                            <option value="<?php echo $project['id']; ?>">
                                🏗️ <?php echo htmlspecialchars($project['name']); ?>
                                <?php if (!empty($project['code'])): ?>
                                    (<?php echo htmlspecialchars($project['code']); ?>)
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group mb-3">
                    <label class="form-label required">👤 نام کاربری (شماره ملی):</label>
                    <input type="text" name="national_code" class="form-control" maxlength="10" required
                           placeholder="شماره ملی خود را وارد کنید">
                </div>

                <div class="form-group mb-3">
                    <label class="form-label required">🔑 گذرواژه:</label>
                    <input type="password" name="password" class="form-control" required
                           placeholder="گذرواژه خود را وارد کنید">
                </div>

                <!-- چک‌باکس ربات - اصلاح ساختار برای جلوگیری از پرش -->
                <div class="form-group my-4">
                    <div class="d-flex align-items-center p-3 bg-light border rounded">
                        <input type="checkbox" id="not_robot_login" name="not_robot" required class="me-2">
                        <label for="not_robot_login" class="mb-0 fw-bold" style="cursor: pointer;">
                            🤖 من ربات نیستم
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn-login w-100 py-2">
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
                    <div class="col-12 col-lg-8" style="width: 100%" >
                        <form id="registrationForm" action="process_register.php" method="POST" enctype="multipart/form-data" class="register-form">
                            <input type="hidden" name="action" value="register">

                            <!-- بخش مشخصات فردی -->
                            <div class="section" >
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
                                <!-- دکمه ارسال -->
                                <button type="submit" class="submit-btn-register">
                                    <i class="fas fa-paper-plane"></i> ثبت اطلاعات و ارسال مدارک
                                </button>
                            </div>


                        </form>
                    </div>
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
