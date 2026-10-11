<?php
// admin/loginAdmin.php - نسخه نهایی بدون نیاز به helpers.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// شروع سشن
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

// اطلاعات دیتابیس هاست
$DB_HOST = '127.0.0.1';
$DB_NAME = 'maskanhb_maskansazan';
$DB_USER = 'root';
$DB_PASS = '';

// اگر کاربر از قبل لاگین کرده باشد
if (!empty($_SESSION['admin_id'])) {
    header('Location: dashboardAdmin.php');
    exit;
}

$error = '';
$success = '';

// تابع‌های کمکی
if (!function_exists('generate_csrf_token')) {
    function generate_csrf_token() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('verify_csrf')) {
    function verify_csrf($token) {
        if (!isset($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }

        // استفاده از hash_equals برای امنیت
        if (function_exists('hash_equals')) {
            return hash_equals($_SESSION['csrf_token'], $token);
        }

        // اگر hash_equals وجود ندارد
        return $_SESSION['csrf_token'] === $token;
    }
}

// تابع ساده‌تر تشخیص ربات
function verify_human($post_data) {
    // فقط بررسی تیک "من ربات نیستم"
    return !empty($post_data['human_check']);
}

// اگر فرم لاگین ارسال شده باشد
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    $csrf = $_POST['csrf_token'] ?? '';
    $human_check = isset($_POST['human_check']);

    // دیباگ
    error_log("Login attempt - Username: $username");
    error_log("CSRF POST: " . (empty($csrf) ? 'EMPTY' : 'SET'));
    error_log("CSRF SESSION: " . (isset($_SESSION['csrf_token']) ? 'SET' : 'NOT SET'));

    if (empty($username) || empty($password)) {
        $error = "نام کاربری و رمز عبور را وارد کنید";
    } else if (!verify_csrf($csrf)) {
        $error = "توکن امنیتی نامعتبر است. لطفاً صفحه را رفرش کنید (F5) و دوباره تلاش کنید.";
        // ایجاد توکن جدید
        unset($_SESSION['csrf_token']);
        generate_csrf_token();
    } else if (!verify_human(['human_check' => $human_check])) {
        $error = "لطفاً تأیید کنید که ربات نیستید";
    } else {
        try {
            // برای لوکال معمولاً 127.0.0.1 سریع‌تر و مطمئن‌تر از localhost است
            $host = ($DB_HOST === 'localhost') ? '127.0.0.1' : $DB_HOST;

            $mysqli = new mysqli($host, $DB_USER, $DB_PASS, $DB_NAME);
            $mysqli->set_charset("utf8mb4");
        } catch (mysqli_sql_exception $e) {
            // اگر در اتصال مشکلی بود، بدون کرش کردن اسکریپت پیام مناسب نشان داده شود
            die("خطا در اتصال به پایگاه داده: لطفاً مطمئن شوید سرویس MySQL در XAMPP روشن (Start) است.<br><small dir='ltr'>" . htmlspecialchars($e->getMessage()) . "</small>");
        }

        if ($mysqli->connect_error) {
            $error = "خطا در اتصال به پایگاه داده";
            error_log("DB Connection Error: " . $mysqli->connect_error);
        } else {
            $mysqli->set_charset("utf8mb4");

            // بررسی وجود جدول admins
            $table_check = $mysqli->query("SHOW TABLES LIKE 'admins'");

            if ($table_check && $table_check->num_rows == 0) {
                // ایجاد جدول admins با ستون password (نه password_hash)
                $create_table = "CREATE TABLE IF NOT EXISTS admins (
                    id INT(11) NOT NULL AUTO_INCREMENT,
                    username VARCHAR(100) NOT NULL,
                    password VARCHAR(255) NOT NULL,
                    status ENUM('active', 'inactive') DEFAULT 'active',
                    remember_token VARCHAR(255) NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    UNIQUE KEY username (username)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

                if ($mysqli->query($create_table)) {
                    error_log("Table 'admins' created successfully");

                    // ایجاد کاربر ادمین پیشفرض
                    $default_password = password_hash('admin123', PASSWORD_DEFAULT);
                    $insert_admin = "INSERT INTO admins (username, password, status) 
                                    VALUES ('admin', '$default_password', 'active')";

                    if ($mysqli->query($insert_admin)) {
                        $success = "سیستم راه‌اندازی شد. از نام کاربری 'admin' و رمز عبور 'admin123' استفاده کنید.";
                        error_log("Default admin user created");
                    } else {
                        error_log("Error creating admin user: " . $mysqli->error);
                    }
                } else {
                    error_log("Error creating table: " . $mysqli->error);
                }
            }

            // جستجوی ادمین - منطبق با ساختار جدول (ستون password)
            $stmt = $mysqli->prepare("SELECT id, username, password_hash, status FROM admins WHERE username = ? LIMIT 1");

            if ($stmt) {
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result && $result->num_rows === 0) {
                    $error = "نام کاربری یا رمز عبور اشتباه است";
                    error_log("User not found: $username");
                } else if ($result && $result->num_rows > 0) {
                    $admin = $result->fetch_assoc();

                    // بررسی رمز عبور
                    $password_correct = false;

                    if (isset($admin['password_hash']) && password_verify($password, $admin['password_hash'])) {
                        $password_correct = true;
                        error_log("Password verified successfully for user: $username");
                    }
                    // پشتیبانی از رمز قدیمی plain text
                    elseif (isset($admin['password_hash']) && $password === $admin['password_hash']) {
                        $password_correct = true;
                        error_log("Plain text password matched for user: $username");

                        // تبدیل رمز قدیمی به hash
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $update_stmt = $mysqli->prepare("UPDATE admins SET password_hash = ? WHERE id = ?");
                        if ($update_stmt) {
                            $update_stmt->bind_param("si", $hashed_password, $admin['id']);
                            $update_stmt->execute();
                            $update_stmt->close();
                            error_log("Password hashed for user: $username");
                        }
                    }

                    if ($password_correct) {
                        // بررسی وضعیت کاربر
                        $status = isset($admin['status']) ? strtolower($admin['status']) : 'inactive';

                        if ($status !== 'active') {
                            $error = "حساب کاربری شما غیرفعال است";
                            error_log("User account is not active: $username");
                        } else {
                            // ثبت اطلاعات در سشن
                            $_SESSION['admin_id'] = $admin['id'];
                            $_SESSION['admin_username'] = $admin['username'];
                            $_SESSION['admin_login_time'] = time();

                            error_log("Login successful for user: $username, ID: {$admin['id']}");

                            // حذف توکن CSRF قدیمی
                            unset($_SESSION['csrf_token']);
                            unset($_SESSION['form_start_time']);

                            // اگر کاربر گزینه "مرا به خاطر بسپار" را انتخاب کرده
                            if ($remember) {
                                $token = bin2hex(random_bytes(32));

// محاسبه تاریخ انقضا برای ۳۰ روز بعد
                                $expires_time = time() + (60 * 60 * 24 * 30);
                                $expiry_date = date('Y-m-d H:i:s', $expires_time);

// هش کردن توکن برای امنیت بیشتر و هماهنگی با نام فیلد remember_token_hash
                                $token_hash = hash('sha256', $token);

                                $update_token = $mysqli->prepare("UPDATE admins SET remember_token_hash = ?, remember_token_expiry = ? WHERE id = ?");
                                if ($update_token) {
                                    $update_token->bind_param("ssi", $token_hash, $expiry_date, $admin['id']);
                                    $update_token->execute();
                                    $update_token->close();

                                    // ذخیره کوکی به مدت 30 روز (path را روی '/' یا '/admin/' بسته به نیاز پروژه بگذارید)
                                    setcookie('admin_remember_token', $token, [
                                            'expires' => $expires_time,
                                            'path' => '/',
                                            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                                            'httponly' => true,
                                            'samesite' => 'Lax'
                                    ]);

                                    setcookie('admin_id', $admin['id'], [
                                            'expires' => $expires_time,
                                            'path' => '/',
                                            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                                            'httponly' => true,
                                            'samesite' => 'Lax'
                                    ]);

                                    error_log("Remember me token set for user: $username");
                                }

                            }

                            $stmt->close();
                            $mysqli->close();

                            // ریدایرکت به داشبورد
                            header('Location: dashboardAdmin.php');
                            exit;
                        }
                    } else {
                        $error = "نام کاربری یا رمز عبور اشتباه است";
                        error_log("Password incorrect for user: $username");
                    }
                }

                $stmt->close();
            } else {
                $error = "خطا در آماده‌سازی کوئری";
                error_log("Prepare statement failed: " . $mysqli->error);
            }
            $isMobile = preg_match("/(android|iphone|ipod|blackberry|windows phone)/i", $_SERVER['HTTP_USER_AGENT']);
            $mysqli->close();
        }
    }
}

// ایجاد توکن CSRF جدید
$csrf_token = generate_csrf_token();

// ذخیره زمان شروع فرم
$_SESSION['form_start_time'] = time();

// لاگ برای دیباگ
error_log("Page loaded - CSRF Token: $csrf_token");
$title = 'مدیریت ';
ob_start(); // شروع ذخیره محتوا
?>
<!-- ✅ نسخه اصلاح‌شده (تمیز + بدون تداخل کلاس‌ها + RTL درست + دکمه برگشت کاربردی) -->
<style>
    :root{
        --brand1:#6a11cb;
        --brand2:#2575fc;
        --radius:18px;
    }

    /* صفحه */
    .admin-login-page{
        min-height:100vh;
        display:flex;
        align-items:center;
        justify-content:center;
        background:#f8f9fa;
        padding:24px 12px;
        position:relative; /* برای دکمه برگشت */
        direction: rtl;
        text-align: right;
    }

    /* کارت */
    .admin-login-card{
        width:100%;
        max-width:100%;                 /* ✅ عرض مناسب دسکتاپ */
        background:#fff;
        border-radius:var(--radius);
        box-shadow:0 18px 40px rgba(0,0,0,.12);
        overflow:hidden;
        border:1px solid rgba(0,0,0,.06);
    }

    .admin-login-header{
        background: linear-gradient(135deg, var(--brand1) 0%, var(--brand2) 100%);
        color:#fff;
        text-align:center;
        padding:28px 18px;
    }
    .admin-login-header h1{
        font-size:1.3rem;
        margin:0;
        font-weight:800;
    }

    .admin-login-body{
        padding:28px 22px;
    }
    @media (min-width:768px){
        .admin-login-body{ padding:36px 34px; }
    }

    /* دکمه برگشت */
    .back-to-home{
        position:absolute;
        top:18px;
        right:18px;
        z-index:10;
    }
    .back-btn{
        display:inline-flex;
        align-items:center;
        gap:8px;
        background:linear-gradient(135deg,#ed8936 0%,#dd6b20 100%);
        color:#fff;
        text-decoration:none;
        padding:10px 14px;
        border-radius:12px;
        font-weight:700;
        font-size:14px;
        box-shadow:0 6px 18px rgba(237,137,54,.25);
        border:0;
    }
    .back-btn:hover{ color:#fff; filter:brightness(.98); }
    @media (max-width:480px){
        .back-to-home{ position:static; margin:0 auto 14px; display:block; width:fit-content; }
    }

    /* فرم */
    .form-label{ font-weight:700; color:#111827; }
    .form-control{
        border-radius:14px;
        padding:14px 16px;
        border:2px solid #e5e7eb;
        background:#f9fafb;
    }
    .form-control:focus{
        border-color: var(--brand2);
        box-shadow: 0 0 0 .2rem rgba(37,117,252,.15);
        background:#fff;
    }

    /* ورودی رمز + دکمه نمایش */
    .password-group .btn{
        border-radius:14px;
        border:2px solid #e5e7eb;
        background:#fff;
    }
    /* چون RTL هستیم، برای InputGroup بهتره گردی‌ها درست ست بشه */
    .password-group .form-control{
        border-left:0;               /* سمت چپ در RTL می‌چسبد به دکمه */
        border-top-left-radius:0;
        border-bottom-left-radius:0;
    }
    .password-group .btn{
        border-right:0;
        border-top-right-radius:0;
        border-bottom-right-radius:0;
    }

    .form-check{
        direction: rtl;
        text-align: right;
    }

    /* بخش "من ربات نیستم" */
    .human-check{
        background:#f8fafc;
        border:2px solid #e5e7eb;
        border-radius:14px;
        padding:14px 16px;
        display:flex;
        align-items:center;
        gap:12px;
    }
    .human-check .form-check-input{
        width:22px;
        height:22px;
        margin:0;
        cursor:pointer;
        accent-color: var(--brand2);
    }
    .human-check label{
        cursor:pointer;
        font-weight:800;
        color: var(--brand2);
        margin:0;
    }

    /* دکمه ورود */
    .btn-admin-login{
        background: linear-gradient(135deg, var(--brand1) 0%, var(--brand2) 100%);
        border:0;
        border-radius:14px;
        padding:14px 16px;
        font-weight:800;
    }

    .admin-login-footer{
        margin-top:26px;
        padding-top:18px;
        border-top:1px solid #eef2f7;
        color:#6b7280;
        font-size:.85rem;
        text-align:center;
    }
</style>

<!-- کانتینر والد کل صفحه: تراز عمودی و افقی کامل در وسط صفحه به همراه پدینگ جانبی برای موبایل -->
<div class="admin-login-page d-flex justify-content-center align-items-center min-vh-100 px-3">

    <!-- کارت ورود: عرض متغیر و بهینه (در موبایل تمام‌عرض و در دسکتاپ حداکثر ۴۸۰ پیکسل) -->
    <div class="admin-login-card shadow-lg" style="width: 100%; max-width: 480px; border-radius: 16px; overflow: hidden;">

        <div class="admin-login-header text-center py-4 text-white" style="background: var(--primary-gradient, linear-gradient(135deg, #667eea 0%, #764ba2 100%));">
            <h1 class="fs-4 fw-bold m-0">پنل مدیریت مسکن‌سازان</h1>
        </div>

        <div class="admin-login-body p-4 bg-white">

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger d-flex align-items-center border-0 shadow-sm mb-4" id="errorAlert">
                    <span class="ms-2">⚠️</span>
                    <div><?= htmlspecialchars($error) ?></div>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success d-flex align-items-center border-0 shadow-sm mb-4" id="successAlert">
                    <span class="ms-2">✅</span>
                    <div><?= htmlspecialchars($success) ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="loginForm" novalidate>
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '') ?>">

                <!-- نام کاربری -->
                <div class="mb-4">
                    <label class="form-label fw-semibold" for="username">👤 نام کاربری</label>
                    <input
                            type="text"
                            name="username"
                            id="username"
                            class="form-control form-control-lg shadow-sm"
                            placeholder="نام کاربری خود را وارد کنید"
                            required
                            autocomplete="username"
                            autofocus
                            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                    >
                </div>

                <!-- رمز عبور -->
                <div class="mb-4 password-group">
                    <label class="form-label fw-semibold" for="password">🔒 رمز عبور</label>
                    <div class="input-group">
                        <button
                                class="btn btn-outline-secondary shadow-sm"
                                type="button"
                                id="togglePassword"
                                aria-label="نمایش/مخفی کردن رمز"
                                style="border-top-right-radius: 0; border-bottom-right-radius: 0;"
                        >👁️</button>

                        <input
                                type="password"
                                name="password"
                                id="password"
                                class="form-control form-control-lg shadow-sm"
                                placeholder="رمز عبور خود را وارد کنید"
                                required
                                autocomplete="current-password"
                                style="border-top-left-radius: 0; border-bottom-left-radius: 0;"
                        >
                    </div>
                </div>

                <!-- مرا به خاطر بسپار -->
                <div class="form-check mb-4 d-flex align-items-center gap-2">
                    <input class="form-check-input m-0" type="checkbox" id="remember" name="remember" style="width: 18px; height: 18px;">
                    <label class="form-check-label text-muted fs-7" for="remember" style="cursor: pointer; user-select: none;">
                        مرا به خاطر بسپار (۳۰ روز)
                    </label>
                </div>

                <!-- من ربات نیستم -->
                <div class="mb-4">
                    <div class="human-check d-flex align-items-center gap-2 p-3 bg-light border rounded-3">
                        <input type="checkbox" id="human_check" name="human_check" required class="form-check-input m-0" style="width: 20px; height: 20px; cursor: pointer;">
                        <label for="human_check" class="form-check-label fw-bold m-0" style="cursor: pointer; user-select: none;">🤖 من ربات نیستم</label>
                    </div>
                </div>

                <!-- دکمه ورود -->
                <button type="submit" class="btn btn-admin-login btn-lg w-100 shadow-sm py-3 text-white fw-bold" id="loginBtn" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;">
                    <span id="btnText">ورود به پنل مدیریت</span>
                    <span id="btnSpinner" class="spinner-border spinner-border-sm ms-2" style="display:none;" role="status" aria-hidden="true"></span>
                </button>

                <div class="admin-login-footer text-center text-muted mt-4 fs-7 border-top pt-3">
                    <div class="mb-1">© <?= date('Y') ?> مسکن‌سازان</div>
                    <div class="opacity-75">نسخه ۲.۰ | پشتیبانی فنی</div>
                </div>
            </form>

        </div>
    </div>
</div>


<script>
    // نمایش/مخفی کردن رمز
    (function () {
        const btn = document.getElementById('togglePassword');
        const input = document.getElementById('password');
        if (!btn || !input) return;

        btn.addEventListener('click', function () {
            input.type = (input.type === 'password') ? 'text' : 'password';
        });
    })();

    // اسپینر روی دکمه هنگام ارسال (اختیاری)
    (function () {
        const form = document.getElementById('loginForm');
        const btn = document.getElementById('loginBtn');
        const spinner = document.getElementById('btnSpinner');
        const text = document.getElementById('btnText');

        if (!form || !btn) return;

        form.addEventListener('submit', function () {
            btn.disabled = true;
            if (spinner) spinner.style.display = 'inline-block';
            if (text) text.textContent = 'در حال ورود...';
        });
    })();
</script>

<?php
// ۶. پایان ذخیره در بافر و ارسال به لایوت
$content = ob_get_clean();
include '../partials/layout.php';
?>
