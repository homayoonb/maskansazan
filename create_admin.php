<?php
// تنظیمات دیتابیس
$host = '127.0.0.1';
$db   = 'maskansazan';
$user = 'root';
$pass = '';

// اتصال به دیتابیس
$mysqli = new mysqli($host, $user, $pass, $db);
if ($mysqli->connect_error) die('Connection failed: ' . $mysqli->connect_error);

// اطلاعات کاربر ادمین
$username = 'admin';      // نام کاربری ادمین
$password = '123456';     // رمز عبور (بعد از تست حتما تغییر بده)
$status   = 'active';

// هش کردن رمز
$hash = password_hash($password, PASSWORD_DEFAULT);

// آماده‌سازی و اجرا
$stmt = $mysqli->prepare("INSERT INTO admins (username, password_hash, status) VALUES (?, ?, ?)");
if (!$stmt) die('Prepare failed: ' . $mysqli->error);

$stmt->bind_param("sss", $username, $hash, $status);

if ($stmt->execute()) {
    echo "کاربر ادمین با موفقیت ساخته شد.\n";
    echo "نام کاربری: $username\n";
    echo "رمز عبور: $password\n";
} else {
    echo "خطا در ایجاد کاربر: " . $stmt->error;
}

$stmt->close();
$mysqli->close();
