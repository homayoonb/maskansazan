<?php
session_start();
include "config/Database.php";

$error = "";
// اگر قبلا لاگین شده
if (!isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === false) {
    header('Location: index.php');
    exit();
}
// اتصال به دیتابیس
$database = new Database();
$db = $database->connect();
if (!$db) {
    die("Connection to database failed.");
}



// پردازش فرم
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // دریافت و پاکسازی ورودی‌ها
    $national_code = isset($_POST['national_code']) ? trim($_POST['national_code']) : '';
    $id_number   = isset($_POST['id_number']) ? trim($_POST['id_number']) : '';

    if ($national_code === '' ||$id_number === '') {
        $error = "Please provide both National Code and Row Number.<br />";
    } else {

        // اگر ستون row_number0 در دیتابیس عددی است، مطمئن شو مقدار عددی است
        // $row_number0 = (int)$row_number0;

        // آماده‌سازی کوئری امن
        $stmt = $db->prepare(/** @lang text */ "SELECT * FROM maskansazan.project_members WHERE national_code = ? and birth_certificate=? LIMIT 1");
        if (!$stmt) {
            die("Prepare failed: " . $db->error);
        }

        $stmt->bind_param("ss", $national_code, $id_number);
        if (!$stmt->execute()) {
            die("Execute failed: " . $stmt->error);
        }

        $result = $stmt->get_result();
        if (!$result) {
            die("Get result failed: " . $db->error);
        }

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();

            $stored_code = $row['national_code'];
            $isValid = false;

            // بررسی هش یا متن ساده
            if (preg_match('/^\$2[ayb]\$\d{2}\$[\.\/A-Za-z0-9]{53}$/', $stored_code)) {
                // هش bcrypt
                if (password_verify($national_code, $stored_code)) {
                    $isValid = true;
                }
            } else {
                // متن ساده
                if ($national_code === $stored_code) {
                    $isValid = true;
                }
            }

            if ($isValid) {
                $_SESSION['loggedin'] = true;
                $_SESSION['full_name'] = $row['first_name'] . " " . $row['last_name'];
                $_SESSION['main_member'] = $row['main_member'];
                $_SESSION['NationalCode'] = $national_code;
                $_SESSION['declared_share']=$row['declared_share'];
                $_SESSION['share']=$row['share'];
                $_SESSION['phone']=$row['phone'];
                header('Location: dashboard.php');
                exit();
            } else {
                $error = "Invalid National Code.<br />";
            }

        } else {
            $error = "Invalid National Code or Row Number.<br />";
        }

        $stmt->close();
    }
}
?>


<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ورود اعضای گروه</title>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            direction: rtl;
        }
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 450px;
            overflow: hidden;
            animation: fadeIn 0.6s ease-out;
        }
        .login-header {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .login-header h1 { margin: 0; font-size: 24px; font-weight: bold; }
        .login-header p { margin: 10px 0 0 0; opacity: 0.9; }

        .login-form { padding: 30px; }
        .form-group { margin-bottom: 20px; }
        .form-label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; font-size: 14px; }
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }
        .form-control:focus {
            border-color: #4facfe;
            background: white;
            box-shadow: 0 0 0 3px rgba(79,172,254,0.1);
            outline: none;
        }

        .btn-login {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 8px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102,126,234,0.3);
        }

        .login-footer {
            background: #f8f9fa;
            padding: 25px;
            border-top: 1px solid #e9ecef;
            text-align: center;
        }
        .weather-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .weather-text { display: flex; flex-direction: column; gap: 5px; }
        .weather-text span:first-child { font-size: 18px; font-weight: bold; color: #333; }
        .weather-text span:last-child { font-size: 14px; color: #666; }
        .dng-logo {
            width: 50px; height: 50px;
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            color: white;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: bold; font-size: 16px;
        }
        .company-info p { margin: 0; font-size: 12px; color: #666; line-height: 1.6; }
        .group-info { text-align: center; padding: 15px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px; color: white; }
        .group-info h3 { margin: 0; font-size: 16px; font-weight: 600; }

        .text-danger { font-size: 12px; margin-top: 5px; display: block; color: red; }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @media (max-width: 480px) {
            .login-card { margin: 10px; }
            .login-form { padding: 20px; }
            .login-header { padding: 20px; }
            .login-footer { padding: 20px; }
            .weather-info { flex-direction: column; gap: 10px; text-align: center; }
        }
    </style>
</head>
<body>
<div class="login-card">
    <div class="login-header">
        <h1>ورود اعضای پروژه مهر</h1>
        <p>لطفا اطلاعات خود را وارد کنید</p>
    </div>
    <form method="POST" class="login-form">
        <?php
        if($error){
        ?>
                <div class="alert alert-danger"><?php echo $error ?></div>
        <?php
        }
        ?>
        <div class="form-group">
            <label class="form-label">کد ملی</label>
            <input type="text" name="national_code" class="form-control" maxlength="10" required>
        </div>
        <div class="form-group">
            <label class="form-label">شماره شناسنامه</label>
            <input type="text" name="id_number" class="form-control" required>
        </div>
        <div class="form-group">
            <div class="g-recaptcha" data-sitekey=""></div>
        </div>
        <button type="submit" class="btn-login">ورود به سیستم</button>
    </form>
    <div class="login-footer">
        <div class="weather-info">
            <div class="weather-text">
                <span>73°F</span>
                <span>Partly sunny</span>
            </div>
            <div class="dng-logo">DNG</div>
        </div>
        <div class="company-info">
            <p>کلیه حقوق محفوظ و این سامانه متعلق به شرکت مهندسی ساختمان سرا می‌باشد</p>
            <p>info@example.com</p>
        </div>
        <div class="group-info">
            <h3>گروه مهندسی نقشه بردار</h3>
        </div>
    </div>
</div>
</body>
</html>
