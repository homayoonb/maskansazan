<?php
// test_captcha_final.php
session_start();

echo "<!DOCTYPE html>
<html dir='rtl'>
<head>
    <meta charset='UTF-8'>
    <title>تست کامل سیستم کپچا</title>
    <style>
        body { 
            font-family: Tahoma, Arial, sans-serif; 
            padding: 30px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        .test-box { 
            background: white; 
            padding: 25px; 
            border-radius: 15px; 
            margin: 25px 0;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border: 1px solid #e0e0e0;
        }
        .success { 
            color: #28a745; 
            background: #d4edda;
            padding: 10px;
            border-radius: 5px;
            border-right: 4px solid #28a745;
        }
        .error { 
            color: #dc3545; 
            background: #f8d7da;
            padding: 10px;
            border-radius: 5px;
            border-right: 4px solid #dc3545;
        }
        h1, h2, h3 {
            color: #333;
        }
        h1 {
            text-align: center;
            color: white;
            margin-bottom: 40px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin: 10px 5px;
            text-decoration: none;
            transition: all 0.3s;
        }
        .btn:hover {
            background: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .btn-success {
            background: #28a745;
        }
        .btn-success:hover {
            background: #1e7e34;
        }
        .btn-danger {
            background: #dc3545;
        }
        .btn-danger:hover {
            background: #bd2130;
        }
        .code-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            margin: 15px 0;
            border: 1px solid #dee2e6;
            direction: ltr;
        }
        .info {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 8px;
            border-right: 4px solid #007bff;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class='container'>
    <h1>🧪 تست کامل سیستم کپچا</h1>";
    
// ============================================
// تست 1: اطلاعات سرور و Session
// ============================================
echo "<div class='test-box'>";
echo "<h2>🔍 1. اطلاعات سرور و Session</h2>";

echo "<div class='info'>";
echo "<strong>Session ID:</strong> " . session_id() . "<br>";
echo "<strong>Session Name:</strong> " . session_name() . "<br>";
echo "<strong>Session Status:</strong> " . session_status() . " (2 = فعال)<br>";
echo "<strong>PHP Version:</strong> " . phpversion() . "<br>";
echo "<strong>Server IP:</strong> " . ($_SERVER['SERVER_ADDR'] ?? 'نامشخص') . "<br>";
echo "<strong>Client IP:</strong> " . ($_SERVER['REMOTE_ADDR'] ?? 'نامشخص') . "<br>";
echo "<strong>User Agent:</strong> " . ($_SERVER['HTTP_USER_AGENT'] ?? 'نامشخص') . "<br>";
echo "</div>";

// تست نوشتن در session
$_SESSION['test_time'] = time();
$_SESSION['test_value'] = 'تست موفق';

echo "<div class='code-box'>";
echo "<strong>Session Variables:</strong><br>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
echo "</div>";

echo "</div>";

// ============================================
// تست 2: اتصال به دیتابیس
// ============================================
echo "<div class='test-box'>";
echo "<h2>🗄️ 2. تست اتصال به دیتابیس</h2>";

$servername = "localhost";
$username = "maskanhb_maskansazan";
$password = "NJ)B*$@y3R9P0Oyg";
$dbname = "maskanhb_maskansazan";

$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    echo "<div class='error'>❌ خطا در اتصال به دیتابیس: " . $conn->connect_error . "</div>";
} else {
    echo "<div class='success'>✅ اتصال به دیتابیس موفق</div>";
    
    // بررسی وجود جدول captcha_sessions
    $result = $conn->query("SHOW TABLES LIKE 'captcha_sessions'");
    if ($result->num_rows > 0) {
        echo "<div class='success'>✅ جدول captcha_sessions وجود دارد</div>";
        
        // نمایش ساختار جدول
        $structure = $conn->query("DESCRIBE captcha_sessions");
        echo "<div class='code-box'>";
        echo "<strong>ساختار جدول captcha_sessions:</strong><br>";
        echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = $structure->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . $row['Default'] . "</td>";
            echo "<td>" . $row['Extra'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "</div>";
    } else {
        echo "<div class='error'>❌ جدول captcha_sessions وجود ندارد!</div>";
        echo "<div class='info'>برای ایجاد جدول، این کد SQL را اجرا کنید:</div>";
        echo "<div class='code-box'>";
        echo "CREATE TABLE IF NOT EXISTS `captcha_sessions` (
    `session_id` VARCHAR(128) NOT NULL,
    `captcha_text` VARCHAR(20) NOT NULL,
    `captcha_time` INT NOT NULL,
    `ip_address` VARCHAR(45),
    `user_agent` VARCHAR(255),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`session_id`),
    INDEX `idx_time` (`captcha_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        echo "</div>";
    }
    
    // بستن اتصال
    $conn->close();
}

echo "</div>";

// ============================================
// تست 3: بررسی GD Library
// ============================================
echo "<div class='test-box'>";
echo "<h2>🖼️ 3. بررسی GD Library</h2>";

if (function_exists('gd_info')) {
    $gd_info = gd_info();
    echo "<div class='success'>✅ GD Library فعال است</div>";
    echo "<div class='code-box'>";
    echo "<strong>GD Information:</strong><br>";
    echo "Version: " . $gd_info['GD Version'] . "<br>";
    echo "FreeType Support: " . ($gd_info['FreeType Support'] ? '✅' : '❌') . "<br>";
    echo "PNG Support: " . ($gd_info['PNG Support'] ? '✅' : '❌') . "<br>";
    echo "JPEG Support: " . ($gd_info['JPEG Support'] ? '✅' : '❌') . "<br>";
    echo "GIF Support: " . ($gd_info['GIF Support'] ? '✅' : '❌') . "<br>";
    echo "</div>";
} else {
    echo "<div class='error'>❌ GD Library فعال نیست!</div>";
    echo "<div class='info'>برای فعال‌سازی، با میزبان خود تماس بگیرید.</div>";
}

echo "</div>";

// ============================================
// تست 4: تست نمایش کپچا
// ============================================
echo "<div class='test-box'>";
echo "<h2>🔢 4. تست نمایش کپچا</h2>";

// لیست فایل‌های کپچا موجود
$captcha_files = [
    'captcha_db_fixed.php' => 'فایل جدید کپچا',
    'captcha/captcha.php' => 'فایل قدیمی کپچا',
    'captcha_db.php' => 'فایل دیتابیسی کپچا'
];

foreach ($captcha_files as $file => $description) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "<div class='info' style='margin-bottom: 15px;'>";
        echo "<strong>$description ($file):</strong><br>";
        echo "<img src='$file?t=" . time() . "' alt='کپچا $file' style='border:2px solid #007bff; margin:10px 0; max-width: 300px;'>";
        echo "<br><a href='$file?t=" . time() . "' target='_blank' class='btn'>مشاهده مستقیم</a>";
        echo "</div>";
    } else {
        echo "<div class='error'>❌ فایل $file یافت نشد</div>";
    }
}

echo "</div>";

// ============================================
// تست 5: فرم تست پردازش
// ============================================
echo "<div class='test-box'>";
echo "<h2>📝 5. تست پردازش فرم</h2>";

echo "<div class='info'>";
echo "<strong>تست ارسال فرم:</strong><br>";
echo "<form method='post' action='process_register.php' style='margin-top: 15px;'>
    <input type='text' name='captcha-text' placeholder='کد کپچا را وارد کنید' required 
           style='padding: 12px; width: 200px; border: 2px solid #007bff; border-radius: 5px;'>
    <input type='hidden' name='test_mode' value='1'>
    <button type='submit' class='btn btn-success'>تست ارسال</button>
</form>";
echo "</div>";

echo "<div class='info'>";
echo "<strong>لینک‌های تست:</strong><br>";
echo "<a href='process_register.php?test=1' class='btn'>صفحه پردازش</a> ";
echo "<a href='register.html' class='btn btn-success'>فرم اصلی ثبت نام</a> ";
echo "<a href='index.html' class='btn'>صفحه اصلی</a>";
echo "</div>";

echo "</div>";

// ============================================
// تست 6: بررسی مجوزها و فایل‌ها
// ============================================
echo "<div class='test-box'>";
echo "<h2>📁 6. بررسی فایل‌ها و مجوزها</h2>";

$folders_to_check = [
    __DIR__ . '/sessions' => 'پوشه sessions',
    __DIR__ . '/uploads' => 'پوشه uploads',
    __DIR__ . '/' => 'پوشه ریشه'
];

foreach ($folders_to_check as $folder => $description) {
    if (file_exists($folder)) {
        $perms = substr(sprintf('%o', fileperms($folder)), -4);
        $writable = is_writable($folder);
        
        echo "<div style='margin: 10px 0;'>";
        echo "<strong>$description:</strong> ";
        echo "<span class='" . ($writable ? 'success' : 'error') . "' style='display: inline-block; padding: 5px 10px;'>";
        echo ($writable ? '✅ قابل نوشتن' : '❌ غیرقابل نوشتن');
        echo " (مجوز: $perms)</span>";
        echo "</div>";
    } else {
        echo "<div class='error'>❌ $description وجود ندارد</div>";
    }
}

// بررسی فایل‌های مهم
$important_files = [
    'captcha/config_session.php',
    'captcha/captcha.php',
    'process_register.php'
];

foreach ($important_files as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        $size = filesize(__DIR__ . '/' . $file);
        echo "<div class='success'>✅ فایل $file وجود دارد (حجم: " . round($size/1024, 2) . " KB)</div>";
    } else {
        echo "<div class='error'>❌ فایل $file وجود ندارد!</div>";
    }
}

echo "</div>";

// ============================================
// دکمه‌های عملیات
// ============================================
echo "<div class='test-box' style='text-align: center;'>";
echo "<h2>⚡ عملیات</h2>";
echo "<a href='" . $_SERVER['PHP_SELF'] . "' class='btn'>🔄 بارگذاری مجدد</a> ";
echo "<a href='" . $_SERVER['PHP_SELF'] . "?clear=1' class='btn btn-danger'>🗑️ پاک کردن Session</a> ";
echo "<button onclick='location.reload()' class='btn'>🔄 رفرش صفحه</button>";
echo "</div>";

// پاک کردن session اگر درخواست شده
if (isset($_GET['clear'])) {
    session_destroy();
    echo "<script>alert('Session پاک شد!');</script>";
}

echo "</div>"; // بستن container
echo "</body></html>";
?>