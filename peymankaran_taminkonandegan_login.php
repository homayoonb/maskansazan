<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// شروع سشن
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

// اطلاعات دیتابیس هاست
$DB_HOST = 'localhost';
$DB_NAME = 'maskanhb_maskansazan';
$DB_USER = 'maskanhb_maskansazan';
$DB_PASS = 'NJ)B*$@y3R9P0Oyg';

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
        $mysqli = @new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

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
                        $update_stmt = $mysqli->prepare("UPDATE admins SET password = ? WHERE id = ?");
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

                                $update_token = $mysqli->prepare("UPDATE admins SET remember_token = ? WHERE id = ?");
                                if ($update_token) {
                                    $update_token->bind_param("si", $token, $admin['id']);
                                    $update_token->execute();
                                    $update_token->close();

                                    // ذخیره کوکی به مدت 30 روز
                                    setcookie('admin_remember_token', $token, [
                                            'expires' => time() + 60*60*24*30,
                                            'path' => '/admin/',
                                            'secure' => isset($_SERVER['HTTPS']),
                                            'httponly' => true,
                                            'samesite' => 'Lax'
                                    ]);

                                    setcookie('admin_id', $admin['id'], [
                                            'expires' => time() + 60*60*24*30,
                                            'path' => '/admin/',
                                            'secure' => isset($_SERVER['HTTPS']),
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
$title = 'پیمانکاران\تامین کنندگان ';
ob_start(); // شروع ذخیره محتوا
?>
<style>



    .login-container {
        width: 100%;
        max-width: 450px;
        animation: fadeIn 0.5s ease;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .login-card {
        background: rgba(255, 255, 255, 0.95);
        border-radius: 20px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        overflow: hidden;
    }

    .login-header {
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        color: white;
        padding: 35px 20px;
        text-align: center;
    }

    .login-header h1 {
        font-size: 24px;
        margin-bottom: 10px;
        font-weight: bold;
    }

    .login-header p {
        opacity: 0.9;
        font-size: 14px;
    }

    .login-body {
        padding: 40px 30px;
    }

    .alert {
        border-radius: 12px;
        margin-bottom: 25px;
        padding: 15px 20px;
        border: none;
        text-align: center;
        font-size: 14px;
    }

    .alert-danger {
        background: #fee;
        color: #c00;
        border-left: 4px solid #f00;
    }

    .alert-success {
        background: #efffef;
        color: #090;
        border-left: 4px solid #0a0;
    }

    .form-group {
        margin-bottom: 25px;
    }

    .form-label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #333;
        font-size: 14px;
    }

    .form-control {
        width: 100%;
        padding: 14px 20px;
        border: 2px solid #e0e0e0;
        border-radius: 12px;
        font-size: 16px;
        transition: all 0.3s;
        background: #f9f9f9;
    }

    .form-control:focus {
        border-color: #4f46e5;
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        outline: none;
        background: white;
    }

    .form-control::placeholder {
        color: #aaa;
    }

    .password-group {
        position: relative;
    }

    .toggle-password {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #666;
        cursor: pointer;
        font-size: 18px;
        padding: 5px;
    }

    .checkbox-group {
        display: flex;
        align-items: center;
        margin-bottom: 25px;
        gap: 10px;
    }

    .checkbox-group input[type="checkbox"] {
        width: 20px;
        height: 20px;
        cursor: pointer;
        accent-color: #4f46e5;
    }

    .checkbox-group label {
        color: #555;
        cursor: pointer;
        font-size: 14px;
    }

    .human-check-section {
        background: #f8f9fa;
        border: 2px solid #e9ecef;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 25px;
    }

    .human-check-box {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .robot-icon {
        font-size: 32px;
        color: #4f46e5;
    }

    .human-check-text {
        flex-grow: 1;
    }

    .human-check-text h4 {
        color: #333;
        margin-bottom: 5px;
        font-size: 16px;
    }

    .human-check-text p {
        color: #666;
        font-size: 13px;
        margin: 0;
    }

    .btn-login {
        width: 100%;
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        color: white;
        border: none;
        border-radius: 12px;
        padding: 16px;
        font-size: 16px;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s;
        margin-top: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .btn-login:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(79, 70, 229, 0.3);
    }

    .btn-login:disabled {
        background: #95a5a6;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }

    .footer {
        text-align: center;
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid #eee;
        color: #888;
        font-size: 12px;
    }

    @media (max-width: 480px) {
        .login-body {
            padding: 30px 20px;
        }

        .login-header {
            padding: 25px 15px;
        }

        .login-header h1 {
            font-size: 20px;
        }



    }
    /* دکمه بازگشت به صفحه اصلی - سمت راست */

</style>
<div class="container-fluid d-flex justify-content-center align-items-center min-vh-100 bg-light">
    <!-- با تغییر col-md-8 به col-md-10 می‌توانید باز هم کادر را بزرگتر کنید -->
    <div class="card shadow-lg border-0 rounded-4 overflow-hidden col-12 col-md-8 col-lg-6">

        <!-- هدر با گرادیان و رنگ سازمانی -->
        <div class="card-header border-0 text-white text-center py-4" style="background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);">
            <h1 class="h3 mb-0 fw-bold">پنل پیمانکاران/تامین کنندگان</h1>
        </div>

        <div class="card-body p-4 p-md-5">
            <?php if ($error): ?>
                <div class="alert alert-danger d-flex align-items-center border-0 shadow-sm mb-4" id="errorAlert">
                    <span class="me-2">⚠️</span>
                    <div><?php echo htmlspecialchars($error); ?></div>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success d-flex align-items-center border-0 shadow-sm mb-4" id="successAlert">
                    <span class="me-2">✅</span>
                    <div><?php echo htmlspecialchars($success); ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="loginForm">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                <!-- نام کاربری -->
                <div class="mb-4">
                    <label class="form-label fw-bold text-dark">👤 نام کاربری</label>
                    <input type="text"
                           name="username"
                           id="username"
                           class="form-control form-control-lg border-2 shadow-sm"
                           placeholder="نام کاربری خود را وارد کنید"
                           required
                           autocomplete="username"
                           autofocus
                           value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>

                <!-- رمز عبور -->
                <div class="mb-4">
                    <label class="form-label fw-bold text-dark">🔒 رمز عبور</label>
                    <div class="input-group">
                        <input type="password"
                               name="password"
                               id="password"
                               class="form-control form-control-lg border-2 border-end-0 shadow-sm"
                               placeholder="رمز عبور خود را وارد کنید"
                               required
                               autocomplete="current-password">
                        <button class="btn btn-outline-secondary border-2 border-start-0 bg-white shadow-sm" type="button" id="togglePassword">
                            👁️
                        </button>
                    </div>
                </div>

                <!-- مرا به خاطر بسپار -->
                <div class="form-check mb-4">
                    <input class="form-check-input" type="checkbox" id="remember" name="remember">
                    <label class="form-check-label text-muted" for="remember">
                        مرا به خاطر بسپار (30 روز)
                    </label>
                </div>

                <!-- بخش تشخیص ربات -->
                <div class="mb-4">
                    <div class="d-flex align-items-center gap-3 p-3 bg-light border rounded-3 shadow-sm">
                        <input type="checkbox" id="human_check" name="human_check" required class="form-check-input" style="width: 22px; height: 22px; cursor: pointer;">
                        <label for="human_check" class="form-check-label fw-bold text-primary mb-0" style="cursor: pointer;">
                            🤖 من ربات نیستم
                        </label>
                    </div>
                </div>

                <!-- دکمه ورود بزرگ -->
                <button type="submit" class="btn btn-primary btn-lg w-100 py-3 shadow-sm fw-bold border-0" id="loginBtn" style="background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);">
                    <span id="btnText">ورود به پنل پیمانکاران/تامین کنندگان</span>
                    <span id="btnSpinner" class="spinner-border spinner-border-sm ms-2" style="display:none;" role="status"></span>
                </button>

                <!-- فوتر اختصاصی فرم -->
                <div class="mt-5 text-center text-muted small border-top pt-4">
                    <p class="mb-1">© <?php echo date('Y'); ?> مسکن‌سازان</p>
                    <p>نسخه ۲.۰ | در صورت مشکل با پشتیبانی تماس بگیرید</p>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// ۶. پایان ذخیره در بافر و ارسال به لایوت
$content = ob_get_clean();
include 'partials/layout.php';
?>
