<?php
// captcha/check_captcha.php
session_start();
header('Content-Type: application/json; charset=utf-8');

function validateCaptcha($user_input) {
    if (!isset($_SESSION['captcha_text']) || !isset($_SESSION['captcha_time'])) {
        return [
            'valid' => false,
            'message' => 'کد امنیتی یافت نشد',
            'refresh' => true
        ];
    }

    // بررسی زمان انقضا
    $captcha_time = $_SESSION['captcha_time'];
    if (time() - $captcha_time > 300) {
        unset($_SESSION['captcha_text'], $_SESSION['captcha_time'], $_SESSION['captcha_used']);
        return [
            'valid' => false,
            'message' => 'کد امنیتی منقضی شده است',
            'refresh' => true
        ];
    }

    // بررسی استفاده مجدد
    if (isset($_SESSION['captcha_used']) && $_SESSION['captcha_used'] === true) {
        return [
            'valid' => false,
            'message' => 'کد امنیتی قبلاً استفاده شده است',
            'refresh' => true
        ];
    }

    $user_input = strtolower(trim($user_input));
    $captcha = strtolower($_SESSION['captcha_text']);

    if ($user_input === $captcha) {
        // علامت گذاری به عنوان استفاده شده
        $_SESSION['captcha_used'] = true;
        return [
            'valid' => true,
            'message' => 'کد امنیتی صحیح است'
        ];
    } else {
        // در صورت اشتباه، کپچای جدیدی تولید کنید
        unset($_SESSION['captcha_text'], $_SESSION['captcha_time'], $_SESSION['captcha_used']);
        return [
            'valid' => false,
            'message' => 'کد امنیتی اشتباه است',
            'refresh' => true
        ];
    }
}

// اگر درخواست POST باشد
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $captcha_input = $_POST['captcha-text'] ?? '';
    $result = validateCaptcha($captcha_input);

    echo json_encode($result);
    exit;
}

// اگر درخواست GET باشد (برای تست)
echo json_encode([
    'status' => 'ready',
    'message' => 'سیستم کپچا آماده است'
]);
?>