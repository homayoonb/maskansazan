<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = 'localhost';
$db   = 'maskanhb_maskansazan';
$user = 'maskanhb_maskansazan';
$pass = 'NJ)B*$@y3R9P0Oyg';

echo "<h3>دیباگ سیستم لاگین</h3>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    echo "<strong>ورودی کاربر:</strong><br>";
    echo "نام کاربری: $username<br>";
    echo "رمز عبور: $password<br><hr>";
    
    $mysqli = new mysqli($host, $user, $pass, $db);
    
    if ($mysqli->connect_error) {
        echo "❌ خطای اتصال دیتابیس: " . $mysqli->connect_error;
    } else {
        echo "✅ اتصال به دیتابیس موفق بود.<br>";
        
        $stmt = $mysqli->prepare("SELECT id, username, password_hash, status FROM admins WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        
        echo "<strong>نتایج جستجو در دیتابیس:</strong><br>";
        if ($user) {
            echo "✅ کاربر پیدا شد:<br>";
            echo "- ID: " . $user['id'] . "<br>";
            echo "- Username: " . $user['username'] . "<br>";
            echo "- Status: " . $user['status'] . "<br>";
            echo "- Password Hash: " . $user['password_hash'] . "<br><br>";
            
            // تست verify
            echo "<strong>تست password_verify:</strong><br>";
            $verify_result = password_verify($password, $user['password_hash']);
            echo "password_verify(\$password, \$hash) = " . ($verify_result ? 'TRUE' : 'FALSE') . "<br>";
            
            // تست hash
            echo "<strong>تولید hash از رمز ورودی:</strong><br>";
            $new_hash = password_hash($password, PASSWORD_DEFAULT);
            echo "hash جدید: $new_hash<br><br>";
            
            if ($verify_result && strtolower($user['status']) === 'active') {
                echo "🎉 <strong style='color:green;'>لاگین موفق!</strong><br>";
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                echo "Session تنظیم شد. می‌توانید به <a href='dashboardAdmin.php'>داشبورد</a> بروید.";
            } else {
                echo "❌ <strong style='color:red;'>لاگین ناموفق!</strong><br>";
                if (strtolower($user['status']) !== 'active') {
                    echo "دلیل: حساب کاربری غیرفعال است.<br>";
                } else {
                    echo "دلیل: رمز عبور اشتباه است.<br>";
                }
            }
        } else {
            echo "❌ کاربر با نام کاربری '$username' پیدا نشد.<br>";
        }
        
        $mysqli->close();
    }
}
?>

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>دیباگ لاگین</title>
</head>
<body>
    <hr>
    <h4>فرم تست لاگین:</h4>
    <form method="POST">
        نام کاربری: <input type="text" name="username" value="admin"><br><br>
        رمز عبور: <input type="password" name="password"><br><br>
        <button type="submit">تست لاگین</button>
    </form>
    
    <hr>
    <h4>راهنمای عیب‌یابی:</h4>
    <ol>
        <li>رمز عبور فعلی admin را وارد کنید</li>
        <li>اگر password_verify = FALSE باشد، باید رمز را تغییر دهید</li>
        <li>برای تغییر رمز، hash جدید تولید و در دیتابیس آپدیت کنید</li>
    </ol>
</body>
</html>