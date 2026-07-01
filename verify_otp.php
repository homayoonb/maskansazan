<?php
// verify_otp.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['otp_sent'])) {
    header("Location: groupmembers.php");
    exit;
}

include 'config/Database.php';
include 'OTPManager.php';

$database = new Database();
$db = $database->connect();
if (!$db) {
    die("ارتباط با دیتابیس برقرار نشد.");
}

$otp_manager = new OTPManager($db);

$error = '';
$success = '';

// بررسی انقضای OTP
if (time() > $_SESSION['otp_expire']) {
    $error = "کد تایید منقضی شده است. لطفا مجددا وارد شوید";
    session_destroy();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['verify_otp'])) {
        $otp_code = trim($_POST['otp_code'] ?? '');

        if (empty($otp_code)) {
            $error = "لطفا کد تایید را وارد کنید";
        } else {
            $user_id = $_SESSION['otp_user_id'];

            if ($otp_manager->verifyOTP($user_id, $otp_code)) {
                // ورود موفق
                $user_data = $_SESSION['otp_user_data'];

                $_SESSION['loggedin'] = true;
                $_SESSION['project_id'] = $_SESSION['otp_user_data']['project_id'];
                $_SESSION['project_name'] = $_SESSION['otp_user_data']['project_name'];
                $_SESSION['full_name'] = $user_data['first_name'] . " " . $user_data['last_name'];
                $_SESSION['main_member'] = $user_data['main_member'];
                $_SESSION['NationalCode'] = $_SESSION['otp_national_code'];
                $_SESSION['declared_share'] = $user_data['declared_share'];
                $_SESSION['share'] = $user_data['share'];
                $_SESSION['phone'] = $user_data['phone'];

                // پاکسازی session OTP
                unset($_SESSION['otp_sent'], $_SESSION['otp_user_id'], $_SESSION['otp_national_code'],
                        $_SESSION['otp_user_data'], $_SESSION['otp_expire'], $_SESSION['otp_attempts']);

                header("Location: dashboard.php");
                exit;
            } else {
                $_SESSION['otp_attempts']++;
                $attempts_left = MAX_OTP_ATTEMPTS - $_SESSION['otp_attempts'];

                if ($_SESSION['otp_attempts'] >= MAX_OTP_ATTEMPTS) {
                    $error = "تعداد تلاش‌های شما بیش از حد مجاز است. لطفا مجددا وارد شوید";
                    session_destroy();
                } else {
                    $error = "کد تایید نادرست است. {$attempts_left} تلاش باقی مانده";
                }
            }
        }
    }

    if (isset($_POST['resend_otp'])) {
        // ارسال مجدد OTP
        $otp = $otp_manager->generateOTP($_SESSION['otp_user_id']);

        if ($otp && $otp_manager->sendOTP($_SESSION['otp_user_data']['phone'], $otp)) {
            $_SESSION['otp_expire'] = time() + OTP_EXPIRE;
            $_SESSION['otp_attempts'] = 0;
            $success = "کد تایید جدید ارسال شد";
        } else {
            $error = "خطا در ارسال کد تایید";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fa">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تایید دو مرحله‌ای - پروژه مهر</title>
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
        .otp-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 450px;
            overflow: hidden;
            animation: fadeIn 0.6s ease-out;
        }
        .otp-header {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .otp-header h1 { margin: 0; font-size: 24px; font-weight: bold; }
        .otp-header p { margin: 10px 0 0 0; opacity: 0.9; }
        .otp-form { padding: 30px; }
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
            text-align: center;
            font-size: 18px;
            letter-spacing: 8px;
        }
        .form-control:focus {
            border-color: #4facfe;
            background: white;
            box-shadow: 0 0 0 3px rgba(79,172,254,0.1);
            outline: none;
        }
        .btn-verify {
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
            margin-bottom: 15px;
        }
        .btn-verify:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102,126,234,0.3);
        }
        .btn-resend {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            border: none;
            border-radius: 8px;
            color: white;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .btn-resend:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(245,87,108,0.3);
        }
        .text-danger {
            font-size: 12px;
            margin-top: 5px;
            display: block;
            color: red;
            text-align: center;
        }
        .text-success {
            font-size: 12px;
            margin-top: 5px;
            display: block;
            color: green;
            text-align: center;
        }
        .timer {
            text-align: center;
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
            direction: ltr;
        }
        .back-btn {
            position: absolute;
            top: 25px;
            right: 25px;
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 8px 25px rgba(139, 92, 246, 0.3);
            text-decoration: none;
            z-index: 10;
        }
        .back-btn:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 12px 35px rgba(139, 92, 246, 0.4);
            background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @media (max-width: 480px) {
            .otp-card { margin: 10px; }
            .otp-form { padding: 20px; }
            .otp-header { padding: 20px; }
        }
    </style>
</head>
<body>
<a href="groupmembers.php" class="back-btn">
    <i class="bi bi-arrow-right"></i>
    بازگشت به صفحه ورود
</a>

<div class="otp-card">
    <div class="otp-header">
        <h1>تایید دو مرحله‌ای</h1>
        <p>کد تایید به شماره شما ارسال شد</p>
    </div>

    <form method="POST" class="otp-form">
        <?php if($error): ?>
            <div class="text-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if($success): ?>
            <div class="text-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="form-group">
            <label class="form-label">کد تایید ۶ رقمی</label>
            <input type="text" name="otp_code" class="form-control" maxlength="6" required
                   pattern="[0-9]{6}" title="لطفا یک کد ۶ رقمی وارد کنید" placeholder="------">
        </div>

        <div class="timer" id="timer">
            زمان باقی‌مانده: <span id="countdown">۱۰:۰۰</span>
        </div>

        <button type="submit" name="verify_otp" class="btn-verify">تایید و ورود</button>
        <button type="submit" name="resend_otp" class="btn-resend">ارسال مجدد کد</button>
    </form>
</div>

<script>
    // تایمر معکوس برای OTP
    let timeLeft = 600; // 10 minutes in seconds
    const timerElement = document.getElementById('countdown');

    function updateTimer() {
        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;
        timerElement.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

        if (timeLeft > 0) {
            timeLeft--;
            setTimeout(updateTimer, 1000);
        } else {
            timerElement.textContent = "منقضی شده";
            timerElement.style.color = "red";
        }
    }

    updateTimer();
</script>
</body>
</html>