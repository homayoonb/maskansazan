<?php
session_start();

// تنظیمات خطا
error_reporting(E_ALL);
ini_set('display_errors', 1);

function redirect_with_error($msg, $tab = 'login') {
    $_SESSION['login_error'] = $msg;
    header("Location: groupmembers.php?tab=" . urlencode((string)$tab));
    exit();
}

// بررسی POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_with_error("درخواست نامعتبر.");
}

// دریافت اطلاعات
$national_code = trim($_POST['national_code'] ?? '');
$password = trim($_POST['password'] ?? '');
$project_id = trim($_POST['project_id'] ?? '');
$not_robot = isset($_POST['not_robot']);

// اعتبارسنجی
if (empty($national_code) || empty($password) || empty($project_id)) {
    redirect_with_error("لطفاً همه فیلدها را پر کنید.");
}

if (!preg_match('/^[0-9]{10}$/', $national_code)) {
    redirect_with_error("کد ملی باید 10 رقم باشد.");
}

if (!is_numeric($project_id)) {
    redirect_with_error("پروژه انتخاب شده معتبر نیست.");
}

if (!$not_robot) {
    redirect_with_error("لطفاً تأیید کنید که ربات نیستید.");
}

// اتصال به دیتابیس
try {

    $pdo = new PDO(
        'mysql:host=localhost;dbname=maskanhb_maskansazan;charset=utf8mb4',
        'root',
        '',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    $stmt = $pdo->prepare("
        SELECT pm.*, p.name as project_name
        FROM project_members pm
        LEFT JOIN projects p ON pm.project_id = p.id
        WHERE pm.national_code = :national_code
        AND pm.project_id = :project_id
        LIMIT 1
    ");

    $stmt->execute([
        ':national_code' => $national_code,
        ':project_id' => (int)$project_id
    ]);

    $user = $stmt->fetch();

    if ($user) {

        if ($user['national_code'] === $password) {

            $_SESSION['loggedin'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['full_name'] = ($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '');
            $_SESSION['first_name'] = $user['first_name'] ?? '';
            $_SESSION['last_name'] = $user['last_name'] ?? '';
            $_SESSION['phone'] = $user['phone'] ?? '';
            $_SESSION['NationalCode'] = $user['national_code'] ?? '';
            $_SESSION['main_member'] = $user['main_member'] ?? 'عضو';
            $_SESSION['share'] = $user['share'] ?? 'نامشخص';
            $_SESSION['project_id'] = $user['project_id'];
            $_SESSION['project_name'] = $user['project_name'] ?? 'پروژه';
            $_SESSION['Address'] = $user['address'] ?? 'آدرس';

            unset($_SESSION['login_error']);

            header('Location: dashboard.php');
            exit();

        } else {

            redirect_with_error("رمز عبور اشتباه است.");
        }

    } else {

        $stmt2 = $pdo->prepare("
            SELECT p.name
            FROM project_members pm
            JOIN projects p ON pm.project_id = p.id
            WHERE pm.national_code = :national_code
        ");

        $stmt2->execute([':national_code' => $national_code]);
        $projects = $stmt2->fetchAll(PDO::FETCH_COLUMN);

        if (count($projects) > 0) {

            $projects_list = implode('، ', $projects);
            redirect_with_error("به این پروژه دسترسی ندارید. شما در پروژه‌های زیر عضو هستید: " . $projects_list);

        } else {

            redirect_with_error("کاربری با این کد ملی یافت نشد.");
        }
    }

} catch (PDOException $e) {

    error_log($e->getMessage());
    redirect_with_error("خطا در سیستم. لطفاً مجدداً تلاش کنید.");
}
?>
