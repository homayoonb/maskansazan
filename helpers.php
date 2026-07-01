<?php
// تابع تولید CSRF Token
function generate_csrf_token() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// تابع بررسی CSRF Token
function verify_csrf($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// تابع نمایش پیام‌های فلش
function flash($type, $message) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash_' . $type] = $message;
}

// تابع خواندن پیام‌های فلش
function get_flash($type) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION['flash_' . $type])) {
        $message = $_SESSION['flash_' . $type];
        unset($_SESSION['flash_' . $type]);
        return $message;
    }
    return null;
}

// تابع تولید توکن تصادفی
function generate_token($length = 32) {
    return bin2hex(random_bytes($length));
}
// تابع برای دریافت کلاس وضعیت مدرک - اصلاح شده
function getDocumentStatusClass($status) {
    switch (strtolower($status)) {
        case 'verified':
        case 'تایید شده':
            return 'badge-verified';
        case 'rejected':
        case 'رد شده':
            return 'badge-rejected';
        case 'pending':
        case 'در انتظار':
        default:
            return 'badge-pending';
    }
}

// تابع برای دریافت متن وضعیت مدرک - اصلاح شده
function getDocumentStatusText($status) {
    switch (strtolower($status)) {
        case 'verified':
        case 'تایید شده':
            return 'تأیید شده';
        case 'rejected':
        case 'رد شده':
            return 'رد شده';
        case 'pending':
        case 'در انتظار':
        default:
            return 'در انتظار بررسی';
    }
}
?>