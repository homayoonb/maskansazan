<?php
// captcha_db_fixed.php
require_once 'captcha/config_session.php';
session_start();

// اتصال به دیتابیس
$servername = "localhost";
$username = "maskanhb_maskansazan";
$password = "NJ)B*$@y3R9P0Oyg";
$dbname = "maskanhb_maskansazan";

$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("Connection failed");
}

// جلوگیری از کش
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// تولید کد تصادفی
$length = 6;
$chars = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ'; // بدون صفر، یک، O، I برای جلوگیری از اشتباه
$captcha_text = '';
for ($i = 0; $i < $length; $i++) {
    $captcha_text .= $chars[rand(0, strlen($chars) - 1)];
}

// ذخیره در دیتابیس با استفاده از session_id
$session_id = session_id();
$captcha_time = time();
$ip_address = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

// لاگ برای دیباگ
$log_file = __DIR__ . '/captcha_debug.log';
file_put_contents($log_file, 
    "[" . date('Y-m-d H:i:s') . "] " .
    "CAPTCHA_GENERATED - " .
    "Session ID: $session_id - " .
    "Captcha: $captcha_text - " .
    "Time: $captcha_time - " .
    "IP: $ip_address\n", 
    FILE_APPEND
);

// حذف کدهای قدیمی (بیش از 15 دقیقه)
$expiry_time = time() - 900;
$conn->query("DELETE FROM captcha_sessions WHERE captcha_time < $expiry_time");

// ذخیره کد جدید
$stmt = $conn->prepare("INSERT INTO captcha_sessions (session_id, captcha_text, captcha_time, ip_address) 
                        VALUES (?, ?, ?, ?) 
                        ON DUPLICATE KEY UPDATE 
                        captcha_text = VALUES(captcha_text), 
                        captcha_time = VALUES(captcha_time)");
$stmt->bind_param("ssis", $session_id, $captcha_text, $captcha_time, $ip_address);
$stmt->execute();
$stmt->close();

// همچنین در session معمولی هم ذخیره کن (به عنوان پشتیبان)
$_SESSION['captcha_text'] = $captcha_text;
$_SESSION['captcha_time'] = $captcha_time;

// ایجاد تصویر کپچا
$width = 180;
$height = 60;

if (function_exists('gd_info')) {
    $image = imagecreatetruecolor($width, $height);
    
    // رنگ‌ها
    $bg_color = imagecolorallocate($image, 245, 245, 245);
    $text_color = imagecolorallocate($image, 30, 30, 30);
    $noise_color = imagecolorallocate($image, 200, 200, 200);
    
    // پس‌زمینه
    imagefilledrectangle($image, 0, 0, $width, $height, $bg_color);
    
    // نویز نقطه‌ای
    for ($i = 0; $i < 300; $i++) {
        imagesetpixel($image, rand(0, $width), rand(0, $height), $noise_color);
    }
    
    // خطوط نویز
    for ($i = 0; $i < 10; $i++) {
        imageline($image, 
            rand(0, $width), rand(0, $height),
            rand(0, $width), rand(0, $height),
            $noise_color
        );
    }
    
    // نوشتن متن با فونت بزرگتر و واضح
    $font_size = 5; // فونت GD
    $text_width = imagefontwidth($font_size) * strlen($captcha_text);
    $text_height = imagefontheight($font_size);
    $x = ($width - $text_width) / 2;
    $y = ($height - $text_height) / 2 + 5;
    
    imagestring($image, $font_size, $x, $y, $captcha_text, $text_color);
    
    // اضافه کردن خط زیر متن برای خوانایی بیشتر
    imageline($image, $x, $y + $text_height + 5, $x + $text_width, $y + $text_height + 5, $text_color);
    
    header("Content-Type: image/png");
    imagepng($image);
    imagedestroy($image);
} else {
    // اگر GD نبود - نمایش متن ساده
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html>
    <html dir='rtl'>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Tahoma; text-align: center; padding: 40px; background: #f5f5f5; }
            .captcha-container { 
                display: inline-block; 
                padding: 30px 50px; 
                background: white; 
                border: 3px solid #4CAF50;
                border-radius: 15px;
                box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            }
            .captcha-code { 
                font-size: 32px; 
                font-weight: bold; 
                letter-spacing: 8px; 
                color: #2196F3;
                margin: 20px 0;
                padding: 15px;
                background: #E3F2FD;
                border-radius: 8px;
            }
            .instructions {
                color: #666;
                margin-top: 20px;
                font-size: 16px;
            }
        </style>
    </head>
    <body>
        <div class='captcha-container'>
            <h2>کد امنیتی</h2>
            <div class='captcha-code'>$captcha_text</div>
            <div class='instructions'>
                کد بالا را در فیلد مربوطه وارد کنید<br>
                این کد تا ۱۵ دقیقه معتبر است
            </div>
        </div>
    </body>
    </html>";
}

$conn->close();
?>