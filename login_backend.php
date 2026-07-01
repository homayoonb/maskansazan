<?php
require_once 'helpers.php';

// تنظیمات امنیتی سشن
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// تنظیمات دیتابیس
$host = 'localhost';
$db   = 'maskansazan';
$user = 'root';
$pass = '';

define('REMEMBER_SECRET', 'یک_کلید_خیلی_سخت_و_سری_بگذارید');
$error = 'نام کاربری یا رمز عبور اشتباه است.';

// اتصال دیتابیس
$mysqli = new mysqli($host, $user, $pass, $db);
if ($mysqli->connect_error) {
    error_log('Database connection error: ' . $mysqli->connect_error);
    flash('error', 'خطایی رخ داده است، دوباره تلاش کنید.');
    header('Location: loginAdmin.php');
    exit;
}

// تابع کمکی برای مدیریت خطاها
function handle_login_error($message, $mysqli = null) {
    error_log('Login Error: ' . $message);
    flash('error', $message);
    if ($mysqli) {
        $mysqli->close();
    }
    header('Location: loginAdmin.php');
    exit;
}

// تابع کمکی برای لاگین attempts
function log_login_attempt($mysqli, $username, $success) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt = $mysqli->prepare("INSERT INTO login_attempts (ip, username, successful) VALUES (?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("ssi", $ip, $username, $success);
        $stmt->execute();
        $stmt->close();
    }
}

// تابع بررسی محدودیت تلاش لاگین
function check_login_attempts($mysqli, $username) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt = $mysqli->prepare("SELECT COUNT(*) as attempts FROM login_attempts WHERE ip = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE) AND successful = 0");
    if ($stmt) {
        $stmt->bind_param("s", $ip);
        $stmt->execute();
        $result = $stmt->get_result();
        $attempts = $result->fetch_assoc()['attempts'];
        $stmt->close();

        if ($attempts >= 5) {
            log_login_attempt($mysqli, $username, false);
            handle_login_error('تعداد تلاش‌های شما بیش از حد مجاز است. لطفاً 15 دقیقه دیگر تلاش کنید.', $mysqli);
        }
    }
}

// ورود خودکار با کوکی
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    $rawToken = $_COOKIE['remember_token'];
    $hashToken = hash_hmac('sha256', $rawToken, REMEMBER_SECRET);

    $stmt = $mysqli->prepare("SELECT id, username, status FROM admins WHERE remember_token_hash = ? AND remember_token_expiry > NOW() LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("s", $hashToken);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user && strtolower($user['status']) === 'active') {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];

            // تنظیمات امن‌تر برای کوکی
            $cookieOptions = [
                'expires' => time() + 60*60*24*30,
                'path' => '/',
                'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                'httponly' => true,
                'samesite' => 'Strict'
            ];

            setcookie('remember_token', $rawToken, $cookieOptions);

            $mysqli->close();
            header('Location: dashboardAdmin.php');
            exit;
        } else {
            // حذف کوکی نامعتبر
            setcookie('remember_token', '', [
                'expires' => time() - 3600,
                'path' => '/',
                'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
        }
    }
}

// بررسی POST فرم
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    $csrf = $_POST['csrf_token'] ?? '';

    // بررسی CSRF Token
    if (!verify_csrf($csrf)) {
        log_login_attempt($mysqli, $username, false);
        handle_login_error('توکن CSRF نامعتبر است.', $mysqli);
    }

    // بررسی فیلدهای خالی
    if ($username === '' || $password === '') {
        log_login_attempt($mysqli, $username, false);
        handle_login_error('لطفاً نام کاربری و رمز عبور را وارد کنید.', $mysqli);
    }

    // بررسی محدودیت تلاش لاگین
    check_login_attempts($mysqli, $username);

    // جستجوی کاربر در دیتابیس
    $stmt = $mysqli->prepare("SELECT * FROM admins WHERE username = ? LIMIT 1");
    if (!$stmt) {
        log_login_attempt($mysqli, $username, false);
        handle_login_error('خطایی رخ داده است، دوباره تلاش کنید.', $mysqli);
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    $status = strtolower($user['status'] ?? '');

    // بررسی وجود کاربر، وضعیت فعال و صحت رمز عبور
    if ($user && $status === 'active' && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];

        // لاگین موفق
        log_login_attempt($mysqli, $username, true);

        // ایجاد توکن یادآوری در صورت درخواست کاربر
        if ($remember) {
            $rawToken = generate_token();
            $hashToken = hash_hmac('sha256', $rawToken, REMEMBER_SECRET);

            $stmt2 = $mysqli->prepare("UPDATE admins SET remember_token_hash = ?, remember_token_expiry = DATE_ADD(NOW(), INTERVAL 30 DAY) WHERE id = ?");
            if ($stmt2) {
                $stmt2->bind_param("si", $hashToken, $user['id']);
                $stmt2->execute();
                $stmt2->close();

                // تنظیمات امن‌تر برای کوکی
                $cookieOptions = [
                    'expires' => time() + 60*60*24*30,
                    'path' => '/',
                    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
                    'httponly' => true,
                    'samesite' => 'Strict'
                ];

                setcookie('remember_token', $rawToken, $cookieOptions);
            } else {
                error_log('Remember token update failed: ' . $mysqli->error);
            }
        }

        $mysqli->close();
        session_write_close();
        header('Location: dashboardAdmin.php');
        exit;
    } else {
        // تاخیر برای جلوگیری از brute force
        sleep(2);

        // لاگین ناموفق
        log_login_attempt($mysqli, $username, false);
        $mysqli->close();
        flash('error', $error);
        header('Location: loginAdmin.php');
        exit;
    }
}

$mysqli->close();
?>